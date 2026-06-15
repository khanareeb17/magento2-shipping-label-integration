<?php

namespace AkStackPro\ShippingLabelIntegration\Model\Response;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Parser
{
    /** @var SerializerInterface  */
    private $serializer;

    /** @var LoggerInterface  */
    private $logger;

    /**
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Parse API Result
     *
     * @param Curl $result
     * @return array
     */
    public function execute(Curl $result): array
    {
        $data = [];
        try {
            $data = $result->getBody();

            if (preg_match('/<string[^>]*>(.*?)<\/string>/s', $data, $matches)) {
                $innerXml = $matches[1];
                $innerXml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $innerXml);
                $data = new \SimpleXMLElement($innerXml);
                $data = $this->serializer->unserialize($this->serializer->serialize((array)$data));
            }
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'File' => $e->getFile(),
                    'Trace' => $e->getTrace()
                ]
            );
        }
        return $data;
    }

    /**
     * Get the 'I' and 'Q' values from the XML response and return them as an associative array.
     *
     * @param Curl $result The Curl object containing the API response.
     * @return array containing key & value pairs. Default values of null are returned
     */
    public function getInventory(Curl $result): array
    {
        $inventoryData = $this->execute($result);
        
        if (isset($inventoryData['NewDataSet']['Table'])) {
            $inventoryValue = $inventoryData['NewDataSet']['Table']['I'] ?? null;
            $quantityValue = $inventoryData['NewDataSet']['Table']['Q'] ?? null;
    
            return ['sku' => $inventoryValue, 'quantity_onhand' => $quantityValue];
        }
        return ['sku' => null, 'quantity_onhand' => null];
    }    
}
