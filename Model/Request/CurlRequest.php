<?php declare(strict_types=1);

namespace AkStackPro\ShippingLabelIntegration\Model\Request;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;

class CurlRequest
{
    /** @var CurlFactory */
    private $curlFactory;

    /**
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        CurlFactory $curlFactory,
    ) {
        $this->curlFactory = $curlFactory;
    }

     /**
     * @param array $vars
     * @return Curl
     */
    public function makePostRequest(array $vars): Curl
    {
        $curl = $this->curlFactory->create();
        $curl->addHeader("Content-Type", $vars['content_type']);
        if (isset($vars['auth_token'])) {
            $curl->addHeader("Authorization", $vars['auth_token']);
        }
        $curl->post(
            $vars['api_endpoint'],
            $vars['payload']
        );
        return $curl;
    }

    /**
     * @param array $vars
     * @param string $idempotencyKey
     * @return Curl
     */
    public function makeEndiciaPostRequest(array $vars, string $idempotencyKey): Curl
    {
        $curl = $this->curlFactory->create();
        $curl->addHeader("Content-Type", $vars['content_type']);
        if (isset($vars['auth_token'])) {
            $curl->addHeader("Authorization", $vars['auth_token']);
        }
        $curl->addHeader("Idempotency-Key", $idempotencyKey); // Add Idempotency-Key header
        $curl->post(
            $vars['api_endpoint'],
            $vars['payload']
        );
        return $curl;
    }

    /**
     * @param array $vars
     * @return Curl
     */
    public function makeAccessRequest(array $contentType, array $endpoint, array $payload): Curl
    {
        $curl = $this->curlFactory->create();
        $curl->addHeader("Content-Type", $contentType['content_type']);
        $curl->post(
            $endpoint['api_endpoint'],
            $payload
        );
        return $curl;
    }

}
