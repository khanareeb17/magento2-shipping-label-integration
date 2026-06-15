<?php

namespace AkStackPro\ShippingLabelIntegration\Model\Services;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Tracking
{
    /**
     * @var OrderInterface
     */
    protected $orderFactory;

    /**
     * @var ShipmentNotifier
     */
    protected $shipmentFactory;
    
    /**
     * @var Order
     */
    protected $orderModel;
    
    /**
     * @var TrackFactory
     */
    protected $trackFactory;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * Class constructor.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $orderFactory
     * @param \Magento\Sales\Model\Convert\Order $orderModel
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param UrlInterface $url
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderInterface $orderFactory,
        \Magento\Sales\Model\Convert\Order $orderModel,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentFactory,
        \Psr\Log\LoggerInterface $logger,
        ManagerInterface $messageManager,
        UrlInterface $url
        
    )
    {
        $this->orderFactory = $orderFactory;
        $this->orderModel = $orderModel;
        $this->trackFactory = $trackFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->url = $url;
      
    }

    /**
     * Create a shipment for an order and update shipment information with tracking details.
     *
     * @param array $result The tracking result data
     * @param string $orderIncrementID The order increment ID
     * @param string $tobeSend Shipping label information
     *
     * @return string|null The URL to view the order or null on error
     */
    public function execute(array $result, string $orderIncrementID, string $tobeSend)
    {
        $order = $this->orderFactory->loadByIncrementId($orderIncrementID);

        if($order->hasInvoices()) {
            if($order->canShip()) {
                $shipment = $this->orderModel->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {    
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->orderModel->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }
        
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                try {
                    // $this->logger->info('tracking file log result: ');
                    // $this->logger->info(print_r($result, true));
                    
                    if (isset($result['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'])) {
                        $trackingNumber = $result['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'];
                    
                        $trackingId = [
                            'carrier_code' => 'ups',
                            'title' => 'United Parcel Service',
                            'number' => $trackingNumber,
                        ];
                    } else {
                        $trackingId = [
                            'carrier_code' => 'ups',
                            'title' => 'United Parcel Service',
                            'number' => "98765412Areeb",
                        ];
                    }
                    // $this->logger->info(print_r($trackingId));
                    $track = $this->trackFactory->create()->addData($trackingId);
                    $shipment->addTrack($track)->save();
                    $shipment->save();
                    $shipment->getOrder()->save();

                    $shipment->setData('shipping_label', $tobeSend);

                    // Send email
                    $this->shipmentFactory->notify($shipment);
                    $shipment->save();

                    $this->messageManager->addSuccessMessage(__('Shipment has been created successfully.'));
                    $redirectUrl = $this->url->getUrl('sales/order/view', ['order_id' => $order->getId()]);
                    return $this->url->setUrl($redirectUrl);
        
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                }
            } else {
                $this->logger->info('You cannot create a shipment for order: ' . $orderNumber);
            }
        } else {
            $this->logger->info('Invoice is not created for order: ' . $orderNumber);
        }
    }

    /**
     * Create a shipment for an order and update shipment information with tracking details.
     *
     * @param array $result The tracking result data
     * @param string $orderIncrementID The order increment ID
     * @param string $tobeSend Shipping label information
     *
     * @return string|null The URL to view the order or null on error
     */
    public function executeEndicia(array $result, string $orderIncrementID, string $tobeSend)
    {
        $order = $this->orderFactory->loadByIncrementId($orderIncrementID);

        if($order->hasInvoices()) {
            if($order->canShip()) {
                $shipment = $this->orderModel->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {    
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->orderModel->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }
        
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                try {
                    if (isset($result['tracking_number'])) {
                        $trackingNumber = $result['tracking_number'];
                    
                        $trackingId = [
                            'carrier_code' => 'usps',
                            'title' => 'United States Postal Service',
                            'number' => $trackingNumber,
                        ];
                    } else {
                        $trackingId = [
                            'carrier_code' => 'ups',
                            'title' => 'United Parcel Service',
                            'number' => "98765412Areeb",
                        ];
                    }
                    $track = $this->trackFactory->create()->addData($trackingId);
                    $shipment->addTrack($track)->save();
                    $shipment->save();
                    $shipment->getOrder()->save();

                    $shipment->setData('shipping_label', $tobeSend);

                    // Send email
                    $this->shipmentFactory->notify($shipment);
                    $shipment->save();

                    $this->messageManager->addSuccessMessage(__('Shipment has been created successfully.'));
                    $redirectUrl = $this->url->getUrl('sales/order/view', ['order_id' => $order->getId()]);
                    return $this->url->setUrl($redirectUrl);
        
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                }
            } else {
                $this->logger->info('You cannot create a shipment for order: ' . $orderNumber);
            }
        } else {
            $this->logger->info('Invoice is not created for order: ' . $orderNumber);
        }
    }
}
