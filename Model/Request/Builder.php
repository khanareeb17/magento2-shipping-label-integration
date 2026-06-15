<?php 

namespace AkStackPro\ShippingLabelIntegration\Model\Request;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use AkStackPro\ShippingLabelIntegration\Model\Config;
use AkStackPro\ShippingLabelIntegration\Model\Request\CurlRequest;
use AkStackPro\ShippingLabelIntegration\Model\Response\Validator;
use AkStackPro\ShippingLabelIntegration\Model\Request\GetOAuthToken;
use AkStackPro\ShippingLabelIntegration\Helper\Data;
use Magento\Sales\Model\OrderFactory;
use AkStackPro\ShippingLabelIntegration\Model\Services\Tracking;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;




/**
 * Class Builder
 *
 * This class is responsible for building and sending requests to Sport South's API.
 *
 */
class Builder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CurlRequest
     */
    private $curlRequest;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var GetOAuthToken
     */
    private $getOAuthToken;

    /**
     * @var Data
     */
    protected $serviceHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepositoryInterface;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Tracking
     */
    protected $tracking;
    
    /**
     * Builder constructor.
     *
     * @param Config $config The configuration object
     * @param LoggerInterface $logger The logger interface
     * @param CurlRequest $curlRequest The cURL request handler
     * @param Validator $validator The data validator
     * @param GetOAuthToken $getOAuthToken The OAuth token retrieval service
     * @param Data $serviceHelper The service helper
     * @param OrderFactory $orderFactory The order factory
     * @param Tracking $tracking The tracking service
     * @param OrderRepositoryInterface $orderRepositoryInterface The order repository interface
     */
    public function __construct(
        Config $config, 
        LoggerInterface $logger, 
        CurlRequest $curlRequest, 
        Validator $validator, 
        GetOAuthToken $getOAuthToken,
        Data $serviceHelper,
        OrderFactory $orderFactory,
        Tracking $tracking,
        OrderRepositoryInterface $orderRepositoryInterface,
        )
        {
            $this->config = $config;
            $this->logger = $logger;
            $this->curlRequest = $curlRequest;
            $this->validator = $validator;
            $this->getOAuthToken = $getOAuthToken;
            $this->serviceHelper = $serviceHelper;
            $this->orderFactory = $orderFactory;
            $this->tracking = $tracking;
            $this->orderRepositoryInterface = $orderRepositoryInterface;
        }
   
    /**
     * Build and send a UPS shipping rate request.
     *
     * @return Curl|null The cURL response or null on error
     */
    public function requestBuilder(): ?Curl
    {
        $apiEndpoint = $this->config->getUpsWorldshipEndPoint() . '/' . 'rating/v1/shop';
        if ($apiEndpoint === null) {
                return null;
            }
        $oauthToken = $this->getOAuthToken->execute();
        $orderID = $this->serviceHelper->getOrderIdFromRequest();
        $shippingDetails = $this->getShippingAddressByOrderId($orderID);

        $requestPayload = array(
            "RateRequest" => array(
                "Shipment" => array(
                    "Shipper" => array(
                        "Name" =>  $this->serviceHelper->getStoreName(),
                        "ShipperNumber" => $this->config->getUpsWorldshipAccountNumber(),
                        "Address" => array(
                            "AddressLine" => array(
                                $this->serviceHelper->getStoreStreetAddress()
                            ),
                            "City" => $this->serviceHelper->getStoreCity(),
                            "StateProvinceCode" => $this->serviceHelper->getStoreState(),
                            "PostalCode" => $this->serviceHelper->getStoreZipCode(),
                            "CountryCode" => "US"
                        )
                    ),
                    "ShipTo" => array(
                        "Name" => $shippingDetails['firstname'] ?? '',
                        "Address" => array(
                            "AddressLine" => array(
                                "AddressLine" => $shippingDetails['street'] ?? ''
                            ),
                            "City" => $shippingDetails['city'] ?? '',
                            "StateProvinceCode" => $this->serviceHelper->getRegionCodeFromNumericValue($shippingDetails['region_id']) ?? '',
                            "PostalCode" => $shippingDetails['postcode'] ?? '',
                            "CountryCode" => "US"
                        )
                    ),
                    "PaymentDetails" => array(
                        "ShipmentCharge" => array(
                            "Type" => "01",
                            "BillShipper" => array(
                                "AccountNumber" => $this->config->getUpsWorldshipAccountNumber()
                            )
                        )
                    ),
                    "Package" => array(
                        "SimpleRate" => array(
                            "Description" => "SimpleRateDescription",
                            "Code" => "XS"
                        ),
                        "PackagingType" => array(
                            "Code" => "02",
                            "Description" => "Packaging"
                        )
                    )
                )
            )
        );
        
        $requestBody = json_encode($requestPayload);
        
        $vars = [
            'api_endpoint' => $apiEndpoint,
            'auth_token' => sprintf('Bearer %s', $oauthToken),
            'content_type' => 'application/json',
            'payload' => $requestBody
        ];
        try {
            $result = $this->curlRequest->makePostRequest($vars);

            if ($this->config->isDebugEnable()) {
                $this->logger->info('Request Status: ' . $result->getStatus());
                $this->logger->info($result->getBody());
            }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return null;
    }

    /**
     * Generate a UPS shipping label based on provided data and return the cURL response.
     *
     * @param array $data Data for label generation
     * @return Curl|null The cURL response or null on error
     */
    public function generateLabel(array $data = []): ?Curl
    {
        $apiEndpoint = $this->config->getUpsWorldshipEndPoint() . '/' . 'shipments/v1/ship';
        if ($apiEndpoint === null) {
                return null;
            }
        $oauthToken = $this->getOAuthToken->execute();
        $shippingDetails = $this->getShippingAddressByOrderId($data['orderId']);

        $requestPayload_label = [
                    "ShipmentRequest" => [
                        "Request" => [
                            "RequestOption" => "nonvalidate"
                        ],
                        "Shipment" => [
                            "Shipper" => [
                                "Name" => $this->serviceHelper->getStoreName(),
                                "TaxIdentificationNumber" => $this->serviceHelper->getStoreVat(),
                                "Phone" => [
                                    "Number" => $this->serviceHelper->getStorePhone(),
                                    "Extension" => " "
                                ],
                                "ShipperNumber" => $this->config->getUpsWorldshipAccountNumber(),
                                "Address" => [
                                    "AddressLine" => [$this->serviceHelper->getStoreStreetAddress()],
                                    "City" => $this->serviceHelper->getStoreCity(),
                                    "StateProvinceCode" => $this->serviceHelper->getStoreState(),
                                    "PostalCode" => $this->serviceHelper->getStoreZipCode(),
                                    "CountryCode" => "US"
                                ]
                            ],
                            "ShipTo" => [
                                "Name" => $shippingDetails['firstname'] . ' ' . $shippingDetails['lastname'],
                                "AttentionName" => $shippingDetails['company'] ?? '',
                                "Phone" => [
                                    "Number" => $shippingDetails['telephone'] ?? ''
                                ],
                                "Address" => [
                                    "AddressLine" => $shippingDetails['street'] ?? '',
                                    "City" => $shippingDetails['city'] ?? '',
                                    "StateProvinceCode" => $this->serviceHelper->getRegionCodeFromNumericValue($shippingDetails['region_id']) ?? '',
                                    "PostalCode" => $shippingDetails['postcode'] ?? '',
                                    "CountryCode" => "US"
                                ],
                                "Residential" => " "
                            ],
                            "PaymentInformation" => [
                                "ShipmentCharge" => [
                                    "Type" => "01",
                                    "BillShipper" => [
                                        "AccountNumber" => $this->config->getUpsWorldshipAccountNumber()
                                    ]
                                ]
                            ],
                            "Service" => [
                                "Code" => $data['selectedRate'],
                                "Description" => $this->serviceHelper->getServiceDescription((String)$data['selectedRate'])
                            ],
                            "Package" => [
                                "Description" => " ",
                                "Packaging" => [
                                    "Code" => "02",
                                    "Description" => "Nails"
                                ],
                                "Dimensions" => [
                                    "UnitOfMeasurement" => [
                                        "Code" => "IN",
                                        "Description" => "Inches"
                                    ],
                                    "Length" => (string)$data['length'],
                                    "Width" => (string)$data['width'],
                                    "Height" => (string)$data['height']                                
                                ],
                                "PackageWeight" => [
                                    "UnitOfMeasurement" => [
                                        "Code" => "LBS",
                                        "Description" => "Pounds"
                                    ],
                                    "Weight" => (string)$data['weight']
                                ]
                            ]
                        ],
                        "LabelSpecification" => [
                            "LabelImageFormat" => [
                                "Code" => "GIF",
                                "Description" => "GIF"
                            ],
                            "HTTPUserAgent" => "Mozilla/4.5"
                        ]
                    ]
                ];

        $requestBody = json_encode($requestPayload_label);

        $vars = [
            'api_endpoint' => $apiEndpoint,
            'auth_token' => sprintf('Bearer %s', $oauthToken),
            'content_type' => 'application/json',
            'payload' => $requestBody
        ];
        try {
            $result = $this->curlRequest->makePostRequest($vars);
            
            if ($this->config->isDebugEnable()) {
                $this->logger->info("Request Body" . $requestBody);
                $this->logger->info('Response Status: ' . $result->getStatus());
                $this->logger->info('Response: ' . $result->getBody());
            }
            $orderIncrementID = $this->getOrderIncrementId((String)$data['orderId']);
            $testvar = json_decode($result->getBody(), true);

            $testShippingResponse = $result->getBody();
            $decoded_testShippingResponse = json_decode($testShippingResponse, true);
            $graphic_decode = base64_decode((String)$decoded_testShippingResponse['ShipmentResponse']['ShipmentResults']['PackageResults']['ShippingLabel']['GraphicImage']);

            $this->tracking->execute($testvar, $orderIncrementID, $graphic_decode);
            return $result;

        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return null;
    }

    /**
     * Get shipping details for a specific order.
     *
     * @param int $orderId
     * @return array
     */
    public function getShippingAddressByOrderId($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        if ($order->getId()) {
            $shippingAddress = $order->getShippingAddress()->getData();
            $shippingAddress_encoded = json_encode($shippingAddress);
           
            $shippingAddress_decoded = json_decode($shippingAddress_encoded, true);
            // $this->logger->info(print_r($shippingAddress_decoded, true));
            return $shippingAddress_decoded;
        }
        return null;
    }

    /**
     * Get the increment ID of an order by its ID.
     *
     * @param int $orderId The order ID
     * @return string|null The order's increment ID or null if not found
     */
    public function getOrderIncrementId($orderId)
    {
        try {
            $order = $this->orderRepositoryInterface->get($orderId);
            $incrementId = $order->getIncrementId();
            return $incrementId;
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Request Builder for specified endpoints.
     *
     * @param string $requestType The type of request to send.
     * @return Curl|null The result data OR null OR failure.
     */
    public function endiciaRatesRequestBuilder(): ?Curl
    {
        $currentDate = date("Y-m-d");
        $apiEndpoint = $this->config->getEndiciaApiEndPoint().'rates';
        if ($apiEndpoint === null) {
                return null;
            }
        $oauthToken = $this->getOAuthToken->executeEndicia();
        $orderID = $this->serviceHelper->getOrderIdFromRequest();
        $shippingDetails = $this->getShippingAddressByOrderId($orderID);

        $requestPayload = array(
            "from_address" => array(
                "company_name" => $this->serviceHelper->getStoreName(),
                "name" => $this->serviceHelper->getStoreName(),
                "address_line1" => $this->serviceHelper->getStoreStreetAddress(),
                "city" => $this->serviceHelper->getStoreCity(),
                "state_province" => $this->serviceHelper->getStoreState(),
                "postal_code" => $this->serviceHelper->getStoreZipCode(),
                "country_code" => "US",
                "phone" => $this->serviceHelper->getStorePhone()
            ),
            "to_address" => array(
                "name" => $shippingDetails['firstname'] ?? '',
                "address_line1" => $shippingDetails['street'] ?? '',
                "city" => $shippingDetails['city'] ?? '',
                "state_province" => $this->serviceHelper->getRegionCodeFromNumericValue($shippingDetails['region_id']) ?? '',
                "postal_code" => $shippingDetails['postcode'] ?? '',
                "country_code" => "US",
            ),
            "service_type" => "",
            "ship_date" => $currentDate,
        );            
                                
        $requestBody = json_encode($requestPayload);

        $vars = [
            'api_endpoint' => $apiEndpoint,
            'auth_token' => sprintf('Bearer %s', $oauthToken),
            'content_type' => 'application/json',
            'payload' => $requestBody
        ];
        try {
            $result = $this->curlRequest->makePostRequest($vars);
            if ($this->config->isDebugEnable()) {
                $this->logger->info('Request Status: ' . $result->getStatus());
                $this->logger->info($result->getBody());
            }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return null;
    }
    
    /**
     * Request Builder for specified endpoints.
     *
     * @param string $requestType The type of request to send.
     * @return Curl|null The result data OR null OR failure.
     */
    public function endiciaCreateLabelBuilder(array $data = []): ?Curl
    {
        $apiEndpoint = $this->config->getEndiciaApiEndPoint().'labels';
        $currentDate = date("Y-m-d");
        if ($apiEndpoint === null) {
                return null;
            }
        $oauthToken = $this->getOAuthToken->executeEndicia();
        $shippingDetails = $this->getShippingAddressByOrderId($data['orderId']);

        $requestPayload = array(
            "from_address" => array(
                "company_name" => $this->serviceHelper->getStoreName(),
                "name" => $this->serviceHelper->getStoreName(),
                "address_line1" => $this->serviceHelper->getStoreStreetAddress(),
                "city" => $this->serviceHelper->getStoreCity(),
                "state_province" => $this->serviceHelper->getStoreState(),
                "postal_code" => $this->serviceHelper->getStoreZipCode(),
                "country_code" => "US",
                "phone" => $this->serviceHelper->getStorePhone()
            ),
            "return_address" => array(
                "company_name" => $this->serviceHelper->getStoreName(),
                "name" => $this->serviceHelper->getStoreName(),
                "address_line1" => $this->serviceHelper->getStoreStreetAddress(),
                "city" => $this->serviceHelper->getStoreCity(),
                "state_province" => $this->serviceHelper->getStoreState(),
                "postal_code" => $this->serviceHelper->getStoreZipCode(),
                "country_code" => "US",
                "phone" => $this->serviceHelper->getStorePhone()
            ),
            "to_address" => array(
                "company_name" => $shippingDetails['company'] ?? '',
                "name" => $shippingDetails['firstname'] . ' ' . $shippingDetails['lastname'],
                "address_line1" => $shippingDetails['street'] ?? '',
                "city" => $shippingDetails['city'] ?? '',
                "state_province" => $this->serviceHelper->getRegionCodeFromNumericValue($shippingDetails['region_id']) ?? '',
                "postal_code" => $shippingDetails['postcode'] ?? '',
                "country_code" => "US",
                "phone" => $shippingDetails['telephone'] ?? '',
                "email" => $shippingDetails['email'] ?? ''
            ),
            "service_type" => "usps_ground_advantage",
            "package" => array(
                "packaging_type" => "package",
                "weight" => (string)$data['weight'],
                "weight_unit" => "pound",
                "length" => (string)$data['length'],
                "width" => (string)$data['width'],
                "height" => (string)$data['height'],
                "dimension_unit" => "inch"
            ),
            "delivery_confirmation_type" => "tracking",
            "ship_date" => $currentDate,
            "is_return_label" => false,
            "label_options" => array(
                "label_size" => "4x8.25-doctab",
                "label_format" => "png",
                "label_logo_image_id" => 0,
                "label_output_type" => "url"
            ),
            "is_test_label" => false
        );
                
        $requestBody = json_encode($requestPayload);

        $vars = [
            'api_endpoint' => $apiEndpoint,
            'auth_token' => sprintf('Bearer %s', $oauthToken),
            'content_type' => 'application/json',
            'payload' => $requestBody
        ];
        try {
            $result = $this->curlRequest->makeEndiciaPostRequest($vars, $this->serviceHelper->generateUUID());

            if ($this->config->isDebugEnable()) {
                $this->logger->info("Request Body" . $requestBody);
                $this->logger->info('Response Status: ' . $result->getStatus());
                $this->logger->info('Response: ' . $result->getBody());
            }

            $orderIncrementID = $this->getOrderIncrementId((string)$data['orderId']);
            $testvar = json_decode($result->getBody(), true);
            $testShippingResponse = $result->getBody();
            $decoded_testShippingResponse = json_decode($testShippingResponse, true);
            $pdfUrl = $decoded_testShippingResponse["labels"][0]["href"];
            
            $graphic_decode = $this->downloadAndConvertToBase64($pdfUrl);

            $this->tracking->executeEndicia($testvar, $orderIncrementID, $graphic_decode);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return null;
    }

    public function downloadAndConvertToBase64(string $fileUrl) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $ioFile = $objectManager->get('Magento\Framework\Filesystem\Io\File');
        $fileSystem = $objectManager->get('Magento\Framework\Filesystem');
        $currentStamp = date("Y-m-d H:i:s");
    
        // Replace with your file URL and local file path
        $localFilePath = 'endicia_labels/' . 'Label-'.$currentStamp.'.pdf'; // Specify the path where you want to save the downloaded file

        // Download the file
        $ioFile->checkAndCreateFolder(dirname($fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($localFilePath)));
        $ioFile->write($fileSystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath($localFilePath), file_get_contents($fileUrl));

        // Read the downloaded PDF file into a binary string
        $pdfContent = $ioFile->read($fileSystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath($localFilePath));

        // Convert the binary content to a base64 encoded string
        $base64PDF = base64_encode($pdfContent);
        $finalDecode = base64_decode($base64PDF);

        return $finalDecode;
    }

    public function endiciaRatesSelect(): array
    {
        $apiEndpoint = $this->config->getEndiciaApiEndPoint();
        if ($apiEndpoint === '' || $this->config->getEndiciaClientID() === ''
            || $this->config->getEndiciaSecretID() === '' || $this->config->getEndiciaRefreshToken() === ''
        ) {
            return [];
        }

        $currentDate = date("Y-m-d");
        $apiEndpoint .= 'rates';
        $oauthToken = $this->getOAuthToken->executeEndicia();
        if ($oauthToken === '') {
            return [];
        }

        $requestPayload = array(
            "from_address" => array(
                "company_name" => $this->serviceHelper->getStoreName(),
                "name" => $this->serviceHelper->getStoreName(),
                "address_line1" => $this->serviceHelper->getStoreStreetAddress(),
                "city" => $this->serviceHelper->getStoreCity(),
                "state_province" => $this->serviceHelper->getStoreState(),
                "postal_code" => $this->serviceHelper->getStoreZipCode(),
                "country_code" => "US",
                "phone" => $this->serviceHelper->getStorePhone()
            ),
            "to_address" => array(
                "company_name" => $this->serviceHelper->getStoreName(),
                "name" => $this->serviceHelper->getStoreName(),
                "address_line1" => $this->serviceHelper->getStoreStreetAddress(),
                "city" => $this->serviceHelper->getStoreCity(),
                "state_province" => $this->serviceHelper->getStoreState(),
                "postal_code" => $this->serviceHelper->getStoreZipCode(),
                "country_code" => "US",
                "phone" => $this->serviceHelper->getStorePhone()
            ),
            "service_type" => "",
            "ship_date" => $currentDate,
        );            
        
        $requestBody = json_encode($requestPayload);

        $vars = [
            'api_endpoint' => $apiEndpoint,
            'auth_token' => sprintf('Bearer %s', $oauthToken),
            'content_type' => 'application/json',
            'payload' => $requestBody
        ];
        try {
            $result = $this->curlRequest->makePostRequest($vars);
            $result = json_decode($result->getBody(), true);

            if (!is_array($result)) {
                return [];
            }

            $mailClasses = [];
            foreach ($result as $values) {
                if (!is_array($values) || !isset($values['service_type'], $values['packaging_type'])) {
                    continue;
                }
                $mailClasses[] = $values['service_type'] . '-' . $values['packaging_type'];
            }

            return $mailClasses;
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return [];
    }
}