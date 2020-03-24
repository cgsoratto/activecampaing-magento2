<?php

namespace CarlosSoratto\ActiveCampaign\Controller\Adminhtml\System;

use Magento\Framework\App\ObjectManager;

class SyncOrders extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    protected $_api;

    protected $_apiOrdersCustomer;

    protected $_apiOrdersOrder;

    protected $_orderCollectionFactory;

    protected $_logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \CarlosSoratto\ActiveCampaign\Api\Orders\Customer $apiOrdersCustomer,
        \CarlosSoratto\ActiveCampaign\Api\Orders\Order $apiOrdersOrder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        $sectionChecker
    ) {
        $this->_api = $api;
        $this->_logger = $logger;
        $this->_apiOrdersCustomer = $apiOrdersCustomer;
        $this->_apiOrdersOrder = $apiOrdersOrder;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    public function execute()
    {
        $orders = $this->_orderCollectionFactory->create();

        foreach ($orders as $order) {
            $resultOrder = $this->_apiOrdersOrder->infoOrder($order->getQuoteId());
            if (isset($resultOrder['ecomOrders']) && isset($resultOrder['ecomOrders'][0]['id'])) {
                $this->_logger->info("Order Already Created: " . $order->getCustomerEmail() . " - " . $resultOrder['ecomOrders'][0]['id'] . " - " . $order->getQuoteId() . " - " . $order->getIncrementId());
                continue;
            }

            /**
             * Register Contact
             */

            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($order->getCustomerEmail());

            if (!$activeCampaignId) {
                $activeCampaignId = $this->_apiOrdersCustomer->createContact($order->getBillingAddress());
            }

            /**
             * Register Commerce Contact
             */

            $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $order->getCustomerEmail());

            if (!$activeCampaignCommerceId) {
                $activeCampaignCommerceId = $this->_apiOrdersCustomer->createCommerceContact($activeCampaignId, $order->getCustomerEmail());
            }

            if (!$activeCampaignCommerceId || !$activeCampaignId) {
                continue;
            }

            /**
             * Register Order
             */

            $params = [
                "ecomOrder" => [
                    "connectionid" => 1,
                    "source" => 1,
                    "externalid" => $order->getQuoteId(),
                    "email" => $order->getCustomerEmail(),
                    "orderNumber" => $order->getIncrementId(),
                    "orderDate" => date('Y-m-dTH:i:s+00:00'),
                    "shippingMethod" => $order->getShippingMethod(),
                    "totalPrice" => $order->getBaseGrandTotal()*100,
                    "currency" => $order->getBaseCurrencyCode(),
                    "customerid" => $activeCampaignCommerceId,
                    "orderProducts" => $this->_api->getOrderProducts($order)
                ]
            ];
            $this->_api->createEcommerceOrder($params);
        }

        $redirectFactory = ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\RedirectFactory');
        $resultRedirect = $redirectFactory->create();
        $resultRedirect->setPath('admin/system_config/edit/section/ac_settings/');
        return $resultRedirect;
    }
}
