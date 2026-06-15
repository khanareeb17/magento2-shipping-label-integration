<?php 

namespace AkStackPro\ShippingLabelIntegration\Block\Adminhtml\Order\Shipment;

use Magento\Framework\View\Element\Template;
use AkStackPro\ShippingLabelIntegration\Model\Request\Builder;
use AkStackPro\ShippingLabelIntegration\Helper\Data;
use AkStackPro\ShippingLabelIntegration\Model\Config;
use Psr\Log\LoggerInterface;

class NewShipment extends Template
{
    /**
     * @var Builder
     */
    private $apiBuilder;

    /**
     * @var Data
     */
    private $serviceHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Builder $apiBuilder
     * @param Data $serviceHelper
     * @param Config $config
     * @param array $data
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Builder $apiBuilder,
        Data $serviceHelper,
        Config $config,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiBuilder = $apiBuilder;
        $this->serviceHelper = $serviceHelper;
        $this->config = $config;
        $this->logger = $logger;
    }
    
    /**
     * Get shipping rates from UPS API.
     *
     * @return array List of shipping rates or an empty array.
     * @throws \Exception If an error occurs during the API request.
     */
    public function getShippingRates()
    {
        $shippingRates = [];
        
        try {
            if ($this->config->isActive()) {
                $response = $this->apiBuilder->requestBuilder();
                
                if ($response) {
                    $responseJson = $response->getBody();
                    $responseArray = json_decode($responseJson, true);
                    
                    if (isset($responseArray['RateResponse']['RatedShipment'])) {
                        $ratedShipments = $responseArray['RateResponse']['RatedShipment'];
                        
                        foreach ($ratedShipments as $ratedShipment) {
                            $serviceCode = $ratedShipment['Service']['Code'];
                            $serviceDescription = $this->serviceHelper->getServiceDescription($serviceCode);
                            $totalCharges = $ratedShipment['TotalCharges']['MonetaryValue'];
                            
                            $shippingRates[] = [
                                'service_code' => $serviceCode,
                                'service_description' => $serviceDescription,
                                'total_charges' => $totalCharges,
                            ];
                        }
                    }
                } else {
                    $this->logError('Empty response received from UPS API.');
                }
            } else {
                $this->logError('Module is not enabled to fetch shipping rates from UPS.');
            }
        } catch (\Exception $e) {
            $this->logError('Error while fetching shipping rates: ' . $e->getMessage());
        }
        
        return $shippingRates;
    }

    /**
     * Log an error message if debugging is enabled.
     *
     * @param string $message The error message to log.
     */
    private function logError($message)
    {
        if ($this->config->isDebugEnable()) {
            $this->logger->error($message);
        }
    }

    /**
     * Check if the module is active.
     *
     * @return bool True if the module is active, false otherwise.
     */
    public function isModuleActive(): bool
    {
        return $this->config->isActive();
    }


    /**
     * Get shipping rates from UPS API.
     *
     * @return array List of shipping rates or an empty array.
     * @throws \Exception If an error occurs during the API request.
     */
    public function getEndiciaShippingRates()
    {
        $endiciaMailClassesString = trim($this->config->getEndiciaMailClasses());
        $endiciaMailClassesArray = $endiciaMailClassesString !== ''
            ? array_filter(array_map('trim', explode(',', $endiciaMailClassesString)))
            : [];

        $desiredArray = [];

        foreach ($endiciaMailClassesArray as $item) {
            $parts = explode('-', $item, 2);
            if (count($parts) < 2) {
                continue;
            }

            [$serviceType, $packagingType] = $parts;

            if (!isset($desiredArray[$serviceType])) {
                $desiredArray[$serviceType] = [];
            }

            $desiredArray[$serviceType][] = trim($packagingType);
        }

        $shippingRates = [];
        $filterRates = !empty($desiredArray);

        try {
            if ($this->config->isActive()) {
                $response = $this->apiBuilder->endiciaRatesRequestBuilder();

                if ($response) {
                    $responseArray = json_decode($response->getBody(), true);

                    if (!is_array($responseArray)) {
                        return $shippingRates;
                    }

                    foreach ($responseArray as $item) {
                        if (!is_array($item)
                            || !isset($item['service_type'], $item['packaging_type'], $item['shipment_cost']['total_amount'])
                        ) {
                            continue;
                        }

                        $serviceType = $item['service_type'];
                        $packagingType = $item['packaging_type'];

                        if ($filterRates
                            && (!isset($desiredArray[$serviceType])
                                || !in_array($packagingType, $desiredArray[$serviceType], true))
                        ) {
                            continue;
                        }

                        $shippingRates[] = [
                            'service_type' => $this->formatString($serviceType, false),
                            'packaging_type' => $this->formatString($packagingType, false),
                            'total_amount' => $item['shipment_cost']['total_amount'],
                        ];
                    }
                } else {
                    $this->logError('Empty response received from Endicia API.');
                }
            } else {
                $this->logError('Module is not enabled to fetch shipping rates from Endicia.');
            }
        } catch (\Exception $e) {
            $this->logError('Error while fetching Endicia shipping rates: ' . $e->getMessage());
        }

        return $shippingRates;
    }
    
    
    /**
     * Format a string by replacing underscores with spaces,
     * capitalizing the first letter of each word, and optionally
     * removing spaces.
     *
     * @param string $inputString The input string to be formatted.
     * @param bool $removeSpaces Whether to remove spaces or not.
     *
     * @return string The formatted string.
     */
    public function formatString($inputString, $removeSpaces = true) {
        // Replace underscores with spaces
        $string = str_replace('_', ' ', $inputString);

        // Capitalize the first letter of each word
        $string = ucwords($string);

        if ($removeSpaces) {
            // Remove spaces if needed
            $string = str_replace(' ', '', $string);
        }

        return $string;
    }
}
