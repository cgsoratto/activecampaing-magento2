<?php

namespace CarlosSoratto\ActiveCampaign\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class NewsletterObserver implements ObserverInterface
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

    public function execute(Observer $observer)
    {
        $email = $observer->getEvent()->getSubscriber()->getSubscriberEmail();
        if (!$email) {
            return false;
        }

        $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($email);

        if (!$activeCampaignId) {
            $activeCampaignId = $this->_apiCustomer->createContactOnlyEmail($email);
        }

        $activeCampaignCommerceId = $this->_api->getIdActiveCampaignCustomerCommerce($activeCampaignId, $email);

        if (!$activeCampaignCommerceId) {
            $this->_apiCustomer->createCommerceContact($activeCampaignId, $email, true);
        }

        $this->_logger->info("Add Customer Newsletter: " . $email);
    }
}
