<?php

namespace AkStackPro\ShippingLabelIntegration\Model\Response;

use Magento\Framework\DataObject as Validate;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Validator
{
    public const HTTP_STATUS_OK = 200;

    /** @var DataObjectFactory  */
    private $dataObjectFactory;

    /** @var Parser  */
    private $parser;

    /** @var LoggerInterface  */
    private $logger;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param Parser $parser
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        Parser $parser,
        LoggerInterface $logger
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->parser = $parser;
        $this->logger = $logger;
    }

    /**
     * Validate Sports South API result
     *
     * @param Curl $result
     * @return Validate
     */
    public function execute(Curl $result): Validate
    {
        $validate = $this->dataObjectFactory->create();
        $validate->setData('valid', false);
        if ($result->getStatus() === self::HTTP_STATUS_OK) {
            $validate->setData('valid', true);
        } else {
            $data = $this->parser->execute($result);
            $this->logger->error(
                'Sports South API result has following issue.',
                $data
            );
            $validate->setData('error', $data);
        }
        return $validate;
    }
}
