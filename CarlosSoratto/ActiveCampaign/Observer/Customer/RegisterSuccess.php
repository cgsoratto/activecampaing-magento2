<?php
namespace CarlosSoratto\ActiveCampaign\Observer\Customer;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{
    protected $_logger;
    protected $_api;
    protected $_apiCustomer;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Customer $apiCustomer
    ) {
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_apiCustomer = $apiCustomer;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        try {
            $customerData = $observer->getEvent()->getCustomer();

            $isSubscribed = $observer->getEvent()->getAccountController()->getRequest()->getParam('is_subscribed');


            /**
             * Register Contact
             */

            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($customerData->getEmail());

            if (!$activeCampaignId) {
                $activeCampaignId = $this->_apiCustomer->createContact($customerData);
            }

            /**
             * Register Commerce Contact
             */

            $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $customerData->getEmail());

            if (!$activeCampaignCommerceId) {
                $this->_apiCustomer->createCommerceContact($activeCampaignId, $customerData->getEmail(), $isSubscribed);
            }

//            $this->_api->addContactList($idContact, 3);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
