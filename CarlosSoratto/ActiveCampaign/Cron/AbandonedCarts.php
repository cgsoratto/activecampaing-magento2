<?php
namespace CarlosSoratto\ActiveCampaign\Cron;

class AbandonedCarts
{
    protected $_logger;
    protected $_api;
    protected $_connection;
    protected $_quoteRepository;
    protected $_apiAbandonedCartsCustomer;
    protected $_apiAbandonedCartsOrder;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\AbandonedCarts\Customer $apiAbandonedCartsCustomer,
        \CarlosSoratto\ActiveCampaign\Api\AbandonedCarts\Order $apiAbandonedCartsOrder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_connection = $resourceConnection->getConnection();
        $this->_quoteRepository = $quoteRepository;
        $this->_apiAbandonedCartsCustomer = $apiAbandonedCartsCustomer;
        $this->_apiAbandonedCartsOrder = $apiAbandonedCartsOrder;
    }

    public function execute()
    {
        $carts = $this->_connection->fetchAll("select * from quote where is_active = 1 and date(created_at) = date(NOW() - INTERVAL 1 DAY) and items_count > 0  AND customer_email IS NOT NULL AND customer_id IS NOT NULL");
        foreach ($carts as $cart) {
            $quote = $this->_quoteRepository->get($cart['entity_id']);
            if ($quote) {
                $resultOrder = $this->_apiAbandonedCartsOrder->infoOrder($quote->getId());
                if (isset($resultOrder['ecomOrders']) && isset($resultOrder['ecomOrders'][0]['id'])) {
                    $this->_logger->info("Cart Abandoned Already Created (CRON): " . $quote->getCustomerEmail() . " - " . $resultOrder['ecomOrders'][0]['id'] . " - " . $quote->getId());
                    continue;
                }

                /**
                 * Register Contact
                 */

                $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($quote->getCustomerEmail());

                if (!$activeCampaignId) {
                    $activeCampaignId = $this->_apiAbandonedCartsCustomer->createContact($quote);
                }

                /**
                 * Register Commerce Contact
                 */

                $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $quote->getCustomerEmail());

                if (!$activeCampaignCommerceId) {
                    $activeCampaignCommerceId = $this->_apiAbandonedCartsCustomer->createCommerceContact($activeCampaignId, $quote->getCustomerEmail());
                }

                /**
                 * Register Abandoned Cart
                 */

                $this->_api->createEcommerceAbandonedCart($quote, $activeCampaignId, $activeCampaignCommerceId, $quote->getCustomerEmail());
            }
        }
        return $this;
    }
}
