<?php

namespace CarlosSoratto\ActiveCampaign\Controller\Adminhtml\System;

use Magento\Framework\App\ObjectManager;

class SyncNews extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    protected $customer;
    protected $_api;
    protected $_logger;
    protected $_apiCustomer;
    protected $_apiSubscriber;
    protected $_subscriberCollectionFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Customer\Model\Customer $customer,
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Customer $apiCustomer,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Subscriber $apiSubscriber,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        $sectionChecker
    ) {
        $this->customer = $customer;
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_apiCustomer = $apiCustomer;
        $this->_apiSubscriber = $apiSubscriber;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    public function execute()
    {
        $checkSubscriber = $this->_subscriberCollectionFactory->create()->useOnlyCustomers();

        foreach ($checkSubscriber as $subscriber) {
            $this->_logger->info("ID Customer Commerce: " . $subscriber->getSubscriberId());
            $emailCustomer = $subscriber->getSubscriberEmail();
            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($emailCustomer);

            if (!$activeCampaignId) {
                $this->_logger->info("ID Customer (NOT FOUND): " . $emailCustomer);
                continue;
            }

            $infoCampaignCommerceId = $this->_api->infoCustomerCommerce($activeCampaignId, $emailCustomer);
            if (!$infoCampaignCommerceId || !isset($infoCampaignCommerceId['ecomCustomers'][0])) {
                $this->_logger->info("ID Commerce Customer (NOT FOUND): " . $emailCustomer);
                continue;
            }

            $acceptsMarketing = $infoCampaignCommerceId['ecomCustomers'][0]['acceptsMarketing'];

            if (!$acceptsMarketing) {
                $this->_apiSubscriber->addSubscriber($infoCampaignCommerceId['ecomCustomers'][0]['id'], $emailCustomer);
            } else {
                $this->_logger->info("ID Customer Commerce (ACCEPTS MARKETING OK): " . $emailCustomer);
            }
        }

        $redirectFactory = ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\RedirectFactory');
        $resultRedirect = $redirectFactory->create();
        $resultRedirect->setPath('admin/system_config/edit/section/ac_settings/');
        return $resultRedirect;
    }
}
