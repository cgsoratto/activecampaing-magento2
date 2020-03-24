<?php

namespace CarlosSoratto\ActiveCampaign\Controller\Adminhtml\System;

use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\ObjectManager;

class AddListAc extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    protected $_logger;

    protected $fileFactory;

    protected $storeManager;

    protected $helperApi;

    protected $_connection;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CarlosSoratto\ActiveCampaign\Helper\Api $helperApi,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_connection = $resource->getConnection();
        $this->storeManager = $storeManager;
        $this->fileFactory = $fileFactory;
        $this->helperApi = $helperApi;
        $this->_logger = $logger;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->getCollection();

        foreach ($customerObj as $customerObjdata) {
            $activecampaignId = $this->helperApi->getCustomerActiveCampaignId($customerObjdata->getId());

            if (!$activecampaignId) {
                continue;
            }

            $this->helperApi->addContactList($activecampaignId, 3);
            $this->_logger->info('Customer added list: ' . $activecampaignId . "_" . $customerObjdata->getEmail());
        }
        $redirectFactory = ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\RedirectFactory');
        $resultRedirect = $redirectFactory->create();
        $resultRedirect->setPath('admin/system_config/edit/section/ac_settings/');
        return $resultRedirect;
    }
}
