<?php declare(strict_types=1);

namespace AkStackPro\ShippingLabelIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    // Module Configuration
    public const SHIPPINGLABELINTEGRATION_MODULE_ACTIVE_STATUS = 'shippinglabelintegration/module_configuration/enabled';
    public const SHIPPINGLABELINTEGRATION_API_DEBUG = 'shippinglabelintegration/module_configuration/debug';


    // Endicia Configuration
    public const ENDICIA_API_END_POINT = 'shippinglabelintegration/endicia_config/endicia_api_endpoint';
    public const ENDICIA_CLIENT_ID = 'shippinglabelintegration/endicia_config/endicia_client_id';
    public const ENDICIA_SECRET_ID = 'shippinglabelintegration/endicia_config/endicia_secret_id';
    public const ENDICIA_AUTH_URL = 'shippinglabelintegration/endicia_config/endicia_auth_url';
    public const ENDICIA_ACCESS_TOKEN_URL = 'shippinglabelintegration/endicia_config/endicia_access_token_url';
    public const ENDICIA_CALLBACK_URL = 'shippinglabelintegration/endicia_config/endicia_callback_url';
    public const ENDICIA_REFRESH_TOKEN = 'shippinglabelintegration/endicia_config/endicia_refresh_token';
    public const ENDICIA_MAIL_CLASSES = 'shippinglabelintegration/endicia_config/endicia_mail_classes';

    // UPS Shipping integration
    public const UPSWORLDSHIP_API_END_POINT = 'shippinglabelintegration/ups_worldship_config/ups_worldship_api_endpoint';
    public const UPSWORLDSHIP_CLIENT_ID = 'shippinglabelintegration/ups_worldship_config/ups_worldship_client_id';
    public const UPSWORLDSHIP_SECRET_ID = 'shippinglabelintegration/ups_worldship_config/ups_worldship_secret_id';
    public const UPSWORLDSHIP_ACCESS_TOKEN_URL = 'shippinglabelintegration/ups_worldship_config/ups_worldship_access_token_url';
    public const UPSWORLDSHIP_ACCOUNT_NUMBER = 'shippinglabelintegration/ups_worldship_config/ups_worldship_account_number';
  
  

    /** @var EncryptorInterface */
    protected $_encryptor;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * 
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->_encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
      }

    /**
     * Return SHIPPING LABEL INTEGRATION Module Active status
     *
     * @return bool
     * @throws \Exception
     */
    public function isActive(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::SHIPPINGLABELINTEGRATION_MODULE_ACTIVE_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return Shipping Label Integration Debug status
     *
     * @return bool
     * @throws \Exception
     */
    public function isDebugEnable(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::SHIPPINGLABELINTEGRATION_API_DEBUG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Endicia API end point
     *
     * @return string
     */
    public function getEndiciaApiEndPoint(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ENDICIA_API_END_POINT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Endicia Mail Classes from configuration
     *
     * @return string
     */
    public function getEndiciaMailClasses(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ENDICIA_MAIL_CLASSES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Client ID from Endicia configuration
     *
     * @return string
     */
    public function getEndiciaClientID(): string
    { 
         $clientID = (String) $this->scopeConfig->getValue(
            self::ENDICIA_CLIENT_ID,
            ScopeInterface::SCOPE_STORE
        );
        
        return $this->_encryptor->decrypt($clientID);
    }

    /**
     * Get Secret ID from Endicia configuration
     *
     * @return string
     */
    public function getEndiciaSecretID(): string
    { 
         $secretID = (String) $this->scopeConfig->getValue(
            self::ENDICIA_SECRET_ID,
            ScopeInterface::SCOPE_STORE
        );
        
        return $this->_encryptor->decrypt($secretID);
    }

    /**
     * Get Auth Url from Endicia configuration
     *
     * @return string
     */
    public function getEndiciaAuthUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ENDICIA_AUTH_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Access Token URL from Endicia configuration
     *
     * @return string
     */
    public function getEndiciaAccessTokenURL(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ENDICIA_ACCESS_TOKEN_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Call bacl URL from Endicia configuration
     *
     * @return string
     */
    public function getEndiciaCallbackUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::ENDICIA_CALLBACK_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Refresh Token Endicia configuration
     *
     * @return string
     */
    public function getEndiciaRefreshToken(): string
    {
        $refreshToken = (string) $this->scopeConfig->getValue(
            self::ENDICIA_REFRESH_TOKEN,
            ScopeInterface::SCOPE_STORE
        );
        
        return $this->_encryptor->decrypt($refreshToken);
    }

    /**
     * Get Ups Worldship API end point
     *
     * @return string
     */
    public function getUpsWorldshipEndPoint(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::UPSWORLDSHIP_API_END_POINT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Client ID from Ups Worldship configuration
     *
     * @return string
     */
    public function getUpsWorldshipClientID(): string
    { 
         $clientID = (String) $this->scopeConfig->getValue(
            self::UPSWORLDSHIP_CLIENT_ID,
            ScopeInterface::SCOPE_STORE
        );
        
        return $this->_encryptor->decrypt($clientID);
    }

    /**
     * Get Secret ID from Ups Worldship configuration
     *
     * @return string
     */
    public function getUpsWorldshipSecretID(): string
    { 
         $secretID = (String) $this->scopeConfig->getValue(
            self::UPSWORLDSHIP_SECRET_ID,
            ScopeInterface::SCOPE_STORE
        );
        
        return $this->_encryptor->decrypt($secretID);
    }

    /**
     * Get Access Token URL from Ups Worldship configuration
     *
     * @return string
     */
    public function getUpsWorldshipAccessTokenURL(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::UPSWORLDSHIP_ACCESS_TOKEN_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Account Number from Ups Worldship configuration
     *
     * @return string
     */
    public function getUpsWorldshipAccountNumber(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::UPSWORLDSHIP_ACCOUNT_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
    }
}
