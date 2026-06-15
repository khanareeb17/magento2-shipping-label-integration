<?php

namespace AkStackPro\ShippingLabelIntegration\Model\Request;

use Magento\Framework\FlagManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use AkStackPro\ShippingLabelIntegration\Model\Config;

class GetOAuthToken
{
    public const TOKEN_TIME_OUT_SEC = 14399;
    public const ENDICIA_TOKEN_TIME_OUT_SEC = 900;
    public const HTTP_STATUS_OK = 200;
    public const FLAG_CODE_UPS_OAUTH_TOKEN = 'ups_oauth_token';
    public const FLAG_CODE_ENDICIA_OAUTH_TOKEN = 'endicia_oauth_token';

    /** @var TimezoneInterface  */
    private $dateTime;

    /** @var FlagManager  */
    private $flagManager;

    /** @var CurlRequest  */
    private $curlRequest;

    /** @var SerializerInterface  */
    private $serializer;

    /** @var Config  */
    private $config;

    /** @var LoggerInterface  */
    private $logger;

    /**
     * @param TimezoneInterface $dateTime
     * @param FlagManager $flagManager
     * @param CurlRequest $curlRequest
     * @param SerializerInterface $serializer
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        TimezoneInterface $dateTime,
        FlagManager $flagManager,
        CurlRequest $curlRequest,
        SerializerInterface $serializer,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->dateTime = $dateTime;
        $this->flagManager = $flagManager;
        $this->curlRequest = $curlRequest;
        $this->serializer = $serializer;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Get oAuth Token
     *
     * @return string
     */
    public function execute(): string
    {
        $tokenData = [];
        try {
            $tokenData = $this->flagManager->getFlagData(self::FLAG_CODE_UPS_OAUTH_TOKEN) ?: [];
            
            $currentTimeStamp = $this->dateTime->date()->getTimestamp();
            $lastTimeStamp = $tokenData['timestamp'] ?? 0;
            if (($currentTimeStamp - $lastTimeStamp) > self::TOKEN_TIME_OUT_SEC) {
                $vars = [
                    'api_endpoint' => $this->config->getUpsWorldshipAccessTokenURL(),
                    'content_type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                    'auth_token' => sprintf('Basic %s', base64_encode(
                        sprintf('%s:%s', $this->config->getUpsWorldshipClientID(), $this->config->getUpsWorldshipSecretID())
                    )),
                    'payload' => 'grant_type=client_credentials'
                ];
                $curl = $this->curlRequest->makePostRequest($vars);
                $result = $curl->getBody();
                $result = $this->serializer->unserialize($result);
                $this->logger->info('This is the token ' . json_encode($result));
                if ($curl->getStatus() === self::HTTP_STATUS_OK) {
                    $tokenData['token'] = $result['access_token'];
                    $tokenData['timestamp'] = $this->dateTime->date()->getTimestamp();
                    $this->flagManager->saveFlag(self::FLAG_CODE_UPS_OAUTH_TOKEN, $tokenData);
                } else {
                    $this->logger->error(
                        'Error while generating oAuth token for oracle API. ' . ($result['error_description'] ?? '')
                    );
                }
            }
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
        return $tokenData['token'] ?? '';
    }

        /**
     * Get oAuth Token
     *
     * @return string
     */
    public function executeEndicia(): string
    {
        $tokenData = [];
        try {
            $tokenData = $this->flagManager->getFlagData(self::FLAG_CODE_ENDICIA_OAUTH_TOKEN) ?: [];
            
            $currentTimeStamp = $this->dateTime->date()->getTimestamp();
            $lastTimeStamp = $tokenData['timestamp'] ?? 0;
            //if (($currentTimeStamp - $lastTimeStamp) > self::ENDICIA_TOKEN_TIME_OUT_SEC) {
                $contentType = [
                    'content_type' => 'application/x-www-form-urlencoded;charset=UTF-8'
                ];
                $endpoint = [
                    'api_endpoint' => $this->config->getEndiciaAccessTokenURL()
                ];
                $payload = [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->config->getEndiciaRefreshToken(),
                    'client_id' => $this->config->getEndiciaClientID(),
                    'client_secret' => $this->config->getEndiciaSecretID()
                ];
                $curl = $this->curlRequest->makeAccessRequest($contentType, $endpoint, $payload);
                $result = $curl->getBody();
                $result = $this->serializer->unserialize($result);
                $this->logger->info('This is the token ' . json_encode($result));
                if ($curl->getStatus() === self::HTTP_STATUS_OK) {
                    $tokenData['token'] = $result['access_token'];
                    $tokenData['timestamp'] = $this->dateTime->date()->getTimestamp();
                    $this->flagManager->saveFlag(self::FLAG_CODE_ENDICIA_OAUTH_TOKEN, $tokenData);
                } else {
                    $this->logger->error(
                        'Error while generating oAuth token for Endicia API. ' . ($result['error_description'] ?? '')
                    );
                }
            //}
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
        return $tokenData['token'] ?? '';
    }
}
