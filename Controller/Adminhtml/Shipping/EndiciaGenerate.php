<?php
namespace AkStackPro\ShippingLabelIntegration\Controller\Adminhtml\Shipping;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use AkStackPro\ShippingLabelIntegration\Model\Request\Builder;

class EndiciaGenerate extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * Controller class constructor.
     *
     * @param Context $context The context object
     * @param JsonFactory $jsonResultFactory The JSON result factory
     * @param LoggerInterface $logger The logger interface
     * @param Builder $builder The builder object
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        LoggerInterface $logger,
        Builder $builder
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->builder = $builder;
    }

    /**
     * Execute the controller action to generate a shipping label based on AJAX data.
     *
     * @return \Magento\Framework\Controller\Result\Json JSON result containing success status and response data
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $data = $this->getRequest()->getPost();
        $response = [];
    
        if (!empty($data)) {
            $shipmentResponse = $this->builder->endiciaCreateLabelBuilder($data->getArrayCopy());
            $encoded_shipmentResponse = json_encode($shipmentResponse);
            $decodededede_shipmentResponse = json_decode($encoded_shipmentResponse, true);

            $response = [
                'success' => true,
                'message' => 'Label generated successfully',
                'shipmentResponse' => $shipmentResponse,
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid data received',
            ];
        }
        return $result->setData($response);
    }
}
