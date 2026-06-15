<?php

namespace AkStackPro\ShippingLabelIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;

class Data
{
    public const STORE_NAME = 'general/store_information/name';
    public const STORE_PHONE = 'general/store_information/phone';
    public const STORE_STATE = 'general/store_information/region_id';
    public const STORE_ZIP_CODE = 'general/store_information/postcode';
    public const STORE_CITY = 'general/store_information/city';
    public const STORE_STREET_ADDRESS = 'general/store_information/street_line1';
    public const STORE_STREET_ADDRESS_LINE_TWO = 'general/store_information/street_line2';
    public const STORE_VAT = 'general/store_information/merchant_vat_number';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RequestInterface
     */
    protected $request;

     /**
     * Mapping of service codes to their descriptions.
     *
     * @var array
     */
    protected $serviceCodeMap = [
        "14" => "UPS Next Day Air Early",
        "01" => "UPS Next Day Air",
        "13" => "UPS Next Day Air Saver",
        "59" => "UPS 2nd Day Air A.M.",
        "02" => "UPS 2nd Day Air",
        "12" => "UPS 3 Day Select",
        "03" => "UPS Ground",
        "11" => "UPS Standard",
        "07" => "UPS Worldwide Express",
        "54" => "UPS Worldwide Express Plus",
        "08" => "UPS Worldwide Expedited",
        "65" => "UPS Worldwide Saver",
        "96" => "UPS Worldwide Express Freight",
        "82" => "UPS Today Standard",
        "83" => "UPS Today Dedicated Courier",
        "84" => "UPS Today Intercity",
        "85" => "UPS Today Express",
        "86" => "UPS Today Express Saver",
        "70" => "UPS Access Point Economy",
    ];

    protected $numericToRegionMapping = [
        '1' => 'AL',
        '2' => 'AK',
        '3' => 'AS',
        '4' => 'AZ',
        '5' => 'AR',
        '6' => 'AF',
        '7' => 'AA',
        '8' => 'AC',
        '9' => 'AE',
        '10' => 'AM',
        '11' => 'AP',
        '12' => 'CA',
        '13' => 'CO',
        '14' => 'CT',
        '15' => 'DE',
        '16' => 'DC',
        '17' => 'FM',
        '18' => 'FL',
        '19' => 'GA',
        '20' => 'GU',
        '21' => 'HI',
        '22' => 'ID',
        '23' => 'IL',
        '24' => 'IN',
        '25' => 'IA',
        '26' => 'KS',
        '27' => 'KY',
        '28' => 'LA',
        '29' => 'ME',
        '30' => 'MH',
        '31' => 'MD',
        '32' => 'MA',
        '33' => 'MI',
        '34' => 'MN',
        '35' => 'MS',
        '36' => 'MO',
        '37' => 'MT',
        '38' => 'NE',
        '39' => 'NV',
        '40' => 'NH',
        '41' => 'NJ',
        '42' => 'NM',
        '43' => 'NY',
        '44' => 'NC',
        '45' => 'ND',
        '46' => 'MP',
        '47' => 'OH',
        '48' => 'OK',
        '49' => 'OR',
        '50' => 'PW',
        '51' => 'PA',
        '52' => 'PR',
        '53' => 'RI',
        '54' => 'SC',
        '55' => 'SD',
        '56' => 'TN',
        '57' => 'TX',
        '58' => 'UT',
        '59' => 'VT',
        '60' => 'VI',
        '61' => 'VA',
        '62' => 'WA',
        '63' => 'WV',
        '64' => 'WI',
        '65' => 'WY',
    ];
    
    /**
     * Constructor for initializing the class.
     *
     * @param LoggerInterface $logger A logger instance for logging purposes.
     * @param ScopeConfigInterface $scopeConfig Configuration scope interface.
     * @param RequestInterface $request The request interface for HTTP requests.
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
    * Get the service description for a given service code.
    *
    * @param string $serviceCode
    * @return string
    */
    public function getServiceDescription($serviceCode)
    {
        return (string) $this->serviceCodeMap[$serviceCode] ?? '';
    }

    /**
     * Retrieve store name
     *
     * @return string|null
     */
    public function getStoreName()
    {
        return (String) $this->scopeConfig->getValue(
            self::STORE_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store phone number
     *
     * @return string|null
     */
    public function getStorePhone()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_PHONE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store state/region
     *
     * @return string|null
     */
    public function getStoreState()
    {
        $numericStateValue = $this->scopeConfig->getValue(
            self::STORE_STATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $this->getRegionCodeFromNumericValue($numericStateValue);
    }

    /**
     * Map numeric state value to a region code.
     *
     * @param string $numericStateValue Numeric state value.
     *
     * @return string|null Region code or null if not found.
     */
    public function getRegionCodeFromNumericValue($numericStateValue)
    {
        if (isset($this->numericToRegionMapping[$numericStateValue])) {
            return $this->numericToRegionMapping[$numericStateValue];
        }

        return null;
    }

    /**
     * Retrieve store zip code
     *
     * @return string|null
     */
    public function getStoreZipCode()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_ZIP_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store city
     *
     * @return string|null
     */
    public function getStoreCity()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_CITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store street address
     *
     * @return string|null
     */
    public function getStoreStreetAddress()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_STREET_ADDRESS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store street address line two (if available)
     *
     * @return string|null
     */
    public function getStoreStreetAddressLineTwo()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_STREET_ADDRESS_LINE_TWO,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve store VAT number
     *
     * @return string|null
     */
    public function getStoreVat()
    {
        return (string) $this->scopeConfig->getValue(
            self::STORE_VAT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

     /**
     * Get the order ID from the request.
     *
     * @return string|null
     */
    public function getOrderIdFromRequest()
    {
        return (String)$this->request->getParam('order_id');
    }

    /**
     * Generate a version-4 UUID.
     *
     * @return string|null
     */
    public function generateUUID() {
        $data = random_bytes(16);
        
        // Set the version (4) and variant (2)
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // Set the version bits
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // Set the variant bits

        // Format as a UUID
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuid;
    }
}
