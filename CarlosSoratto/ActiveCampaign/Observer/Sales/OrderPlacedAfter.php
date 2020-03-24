<?php
namespace CarlosSoratto\ActiveCampaign\Observer\Sales;

class OrderPlacedAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $_logger;
    protected $_api;
    protected $_apiOrdersCustomer;
    protected $_customerRepository;
    protected $_apiAddress;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\Orders\Customer $apiOrdersCustomer,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Address $apiAddress,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_apiAddress = $apiAddress;
        $this->_apiOrdersCustomer = $apiOrdersCustomer;
        $this->_customerRepository = $customerRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        try {
            $order = $observer->getEvent()->getOrder();
            $quoteAddress = $order->getBillingAddress();

            /**
             * Register Contact
             */

            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($order->getCustomerEmail());

            if (!$activeCampaignId) {
                $activeCampaignId = $this->_apiOrdersCustomer->createContact($quoteAddress);
            }

            /**
             * Register Commerce Contact
             */

            $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $order->getCustomerEmail());

            if (!$activeCampaignCommerceId) {
                $activeCampaignCommerceId = $this->_apiOrdersCustomer->createCommerceContact($activeCampaignId, $order->getCustomerEmail());
            }

            if (!$activeCampaignCommerceId || !$activeCampaignId) {
                return;
            }

            /**
             * Add Telephone to Contact
             */

            if ($quoteAddress->getTelephone()) {
                $this->_apiAddress->addPhoneCustomer($order->getCustomerEmail(), $quoteAddress->getTelephone());
            }

            /**
             * Add Address to Contact
             */

            $this->_apiAddress->addAddressCustomer($quoteAddress, $activeCampaignId);

            /**
             * Add Order to Contact
             */

            $params = [
                "ecomOrder" => [
                    "connectionid" => 1,
                    "source" => 1,
                    "externalid" => $order->getQuoteId(),
                    "email" => $order->getCustomerEmail(),
                    "orderNumber" => $order->getIncrementId(),
                    "orderDate" => date('Y-m-dTH:i:s+00:00'), //"2019-03-13T17:41:39-04:00"
                    "shippingMethod" => $order->getShippingMethod(),
                    "totalPrice" => $order->getBaseGrandTotal()*100,
                    "currency" => $order->getBaseCurrencyCode(),
                    "customerid" => $activeCampaignCommerceId,
                    "orderProducts" => $this->_api->getOrderProducts($order)
                ]
            ];

            $this->_api->createEcommerceOrder($params);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
