<?php

namespace CarlosSoratto\ActiveCampaign\Controller\Adminhtml\System;

use Magento\Framework\App\ObjectManager;

class SyncCustomers extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    protected $customer;
    protected $_api;
    protected $_apiCustomer;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Customer\Model\Customer $customer,
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Api\Customer\Customer $apiCustomer,
        $sectionChecker
    ) {
        $this->customer = $customer;
        $this->_api = $api;
        $this->_apiCustomer = $apiCustomer;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    public function execute()
    {
        $customerObj = $this->customer
            ->getCollection();

        foreach ($customerObj as $customerObjdata) {
            $activeCampaignId = $this->_api->getIdActiveCampaignCustomer($customerObjdata->getEmail());

            if (!$activeCampaignId) {
                $this->_apiCustomer->createContact($customerObjdata);
            }
        }

        $redirectFactory = ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\RedirectFactory');
        $resultRedirect = $redirectFactory->create();
        $resultRedirect->setPath('admin/system_config/edit/section/ac_settings/');
        return $resultRedirect;
    }
}
