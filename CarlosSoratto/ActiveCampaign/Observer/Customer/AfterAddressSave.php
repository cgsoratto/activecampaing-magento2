<?php
namespace CarlosSoratto\ActiveCampaign\Observer\Customer;

class AfterAddressSave implements \Magento\Framework\Event\ObserverInterface
{
    protected $_logger;
    protected $_customer;
    protected $_api;
    protected $_apiCustomer;
    protected $_apiAddress;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \Magento\Customer\Model\Customer $customer,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Address $apiAddress,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Customer $apiCustomer
    ) {
        $this->_logger = $logger;
        $this->_customer = $customer;
        $this->_api = $api;
        $this->_apiCustomer = $apiCustomer;
        $this->_apiAddress = $apiAddress;
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
            $customerAddress = $observer->getEvent()->getCustomerAddress();
            $customerData = $customerAddress->getCustomer();

            /**
             * Register Contact
             */

            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($customerData->getEmail());

            if(!$activeCampaignId){
                $activeCampaignId = $this->_apiCustomer->createContact($customerData);
            }

            /**
             * Register Commerce Contact
             */

            $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $customerData->getEmail());

            if(!$activeCampaignCommerceId){
                $this->_apiCustomer->createCommerceContact($activeCampaignId, $customerData->getEmail());
            }

            /**
             * Add Telephone to Contact
             */

            if ($customerAddress->getTelephone()) {
                $this->_apiAddress->addPhoneCustomer($customerData->getEmail(), $customerAddress->getTelephone());
            }

            /**
             * Add Address to Contact
             */

            $this->_apiAddress->addAddressCustomer($customerAddress, $activeCampaignId);

//            $this->_api->addContactList($idContact, 3);
            $this->_logger->info('Customer address saved: ' . $activeCampaignId . "_" . $customerData->getEmail());
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
