<?php
namespace CarlosSoratto\ActiveCampaign\Helper;

class Api
{
    protected $_categoryCollectionFactory;
    protected $_connection;
    protected $_logger;
    protected $_customerRepositoryInterface;
    protected $scopeConfig;
    protected $_productRepository;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->_connection = $resource->getConnection();
        $this->_logger = $logger;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->_productRepository = $productRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    private function getApiUrl()
    {
        return $this->scopeConfig->getValue('ac_settings/general/ac_url_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getApiToken()
    {
        return $this->scopeConfig->getValue('ac_settings/general/ac_api_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function _apiCall($call, $type = 'GET', $params = null)
    {
        $ch = curl_init($this->getApiUrl() . $call);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
            'Api-Token: ' . $this->getApiToken()
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($type === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        if ($type === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        $jsonData = curl_exec($ch);
        return $jsonData;
    }

    public function createContact($customerData)
    {
        $result = '';
        try {
            $result = json_decode($this->_apiCall('contact/sync', 'POST', $customerData), true);
            $this->_logger->info("Add Customer: " . $customerData['contact']['email'] . " - " . $result['contact']['id']);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return $result['contact']['id'];
    }

    public function createContactGuest($customerData)
    {
        $params = [
            "contact" => [
                "email" => $customerData['email'],
                "firstName" => $customerData['firstName'],
                "lastName" => $customerData['lastName'],
                'phone' => $customerData['phone'],
                "status" => 0
            ]
        ];

        try {
            $result = json_decode($this->_apiCall('contact/sync', 'POST', $params), true);
            $this->_logger->info("Add Customer Guest: " . $customerData['email'] . " - " . $result['contact']['id']);
            return $result['contact']['id'];
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return false;
    }

    public function addCustomFieldsValuesContact($idContact, $field, $value)
    {
        $params = [
            "fieldValue" => [
                "contact" => $idContact,
                "field" => $field,
                "value" => $value
            ]
        ];
        try {
            $this->_apiCall('fieldValues', 'POST', $params);
            $this->_logger->info('Field custom saved: ' . $idContact . " - " . " - " . $value);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    public function createEcommerceCustomer($params)
    {
        $activeCampaignCommerceId = '';
        try {
            $result = json_decode($this->_apiCall('ecomCustomers', 'POST', $params), true);
            $this->_logger->info("Add Customer Commerce: " . $result['ecomCustomer']['email'] . " - " . $result['ecomCustomer']['id']);
            $activeCampaignCommerceId = $result['ecomCustomer']['id'];
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return $activeCampaignCommerceId;
    }

    public function updateEcommerceCustomer($activeCampaignCommerceId, $params, $emailCustomer)
    {
        try {
            $this->_apiCall('ecomCustomers/' . $activeCampaignCommerceId, 'PUT', $params);
            $this->_logger->info("Upt Customer Commerce: " . $emailCustomer . " - " . $activeCampaignCommerceId);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return $activeCampaignCommerceId;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function createEcommerceOrder($params)
    {
        try {
            $this->_apiCall('ecomOrders/', 'POST', $params);
            $this->_logger->info("Order Created: " . $params['ecomOrder']['orderNumber'] . " - " . $params['ecomOrder']['externalid'] . " - " . $params['ecomOrder']['email'] . " - " . $params['ecomOrder']['customerid']);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     *
     * @param \Magento\Sales\Model\Quote $quote
     */
    public function createEcommerceAbandonedCart($quote, $activeCampaignId, $activeCampaignCommerceId, $emailCustomer)
    {
        try {
            if (!$activeCampaignId || !$activeCampaignCommerceId || !$emailCustomer) {
                return;
            }
            $params = [
                "ecomOrder" => [
                    "connectionid" => 1,
                    "source" => 1,
                    "externalcheckoutid" => $quote->getId(),
                    "email" => $emailCustomer,
                    "externalCreatedDate" => date('Y-m-dTH:i:s+00:00', strtotime($quote->getCreatedAt())),
                    "abandonedDate" => date('Y-m-dTH:i:s+00:00', strtotime($quote->getUpdatedAt())),
                    "totalPrice" => $quote->getBaseGrandTotal()*100,
                    "currency" => $quote->getBaseCurrencyCode(),
                    "customerid" => $activeCampaignCommerceId,
                    "orderProducts" => $this->getQuoteProducts($quote)
                ]
            ];
            $this->_apiCall('ecomOrders/', 'POST', $params);
            $this->_logger->info("Cart Abandoned Created: " . $emailCustomer . " - " . $activeCampaignCommerceId);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    public function getProductBySku($id)
    {
        return $this->_productRepository->get($id);
    }

    public function getOrderProducts($order)
    {
        $items = $order->getAllVisibleItems();
        $products = [];
        foreach ($items as $item) {
            if ($item->getBasePriceInclTax() < 1) {
                continue;
            }
            $categoriesProduct = [];
            $product = $this->getProductBySku($item->getSku());
            $categoryIds = $product->getCategoryIds();
            $categories = $this->getCategoryCollection()->addAttributeToFilter('entity_id', $categoryIds);

            foreach ($categories as $category) {
                $categoriesProduct[$category->getLevel()] = $category->getName();
            }

            $products[] = [
                "externalid" => $item->getSku(),
                "name" => $item->getName(),
                "price" => $item->getBasePriceInclTax()*100,
                "quantity" => $item->getQtyOrdered(),
                "category" => (count($categoriesProduct) > 0 && max(array_keys($categoriesProduct))) ? $categoriesProduct[max(array_keys($categoriesProduct))] : ""
            ];
        }
        return $products;
    }

    public function getQuoteProducts($quote)
    {
        $items = $quote->getAllVisibleItems();
        $products = [];
        foreach ($items as $item) {
            $categoriesProduct = [];
            $product = $this->getProductBySku($item->getSku());
            $categoryIds = $product->getCategoryIds();
            $categories = $this->getCategoryCollection()->addAttributeToFilter('entity_id', $categoryIds);

            foreach ($categories as $category) {
                $categoriesProduct[$category->getLevel()] = $category->getName();
            }

            $helperImport = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Helper\Image');
            $imageUrl = $helperImport->init($product, 'product_page_image_small')
                ->setImageFile($product->getSmallImage())
                ->getUrl();

            $products[] = [
                "externalid" => $item->getSku(),
                "name" => $item->getName(),
                "price" => $item->getBasePriceInclTax()*100,
                "quantity" => $item->getQty(),
                "category" => (count($categoriesProduct) > 0 && max(array_keys($categoriesProduct))) ? $categoriesProduct[max(array_keys($categoriesProduct))] : "",
                "productUrl" => $product->getProductUrl(),
                "imageUrl" => $imageUrl
            ];
        }
        return $products;
    }

    public function addContactList($activecampaignId, $listId)
    {
        $params = [
            "contactList" => [
                "list" => $listId,
                "contact" => $activecampaignId,
                "status" => 1
            ]
        ];
        $this->_apiCall('contactLists/', 'POST', $params);
    }

    public function getCategoryCollection($isActive = true, $level = false, $sortBy = false, $pageSize = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }

        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }

        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }

        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }

        return $collection;
    }

    public function infoCustomerCommerce($customerId, $email)
    {
        try {
            return json_decode($this->_apiCall('ecomCustomers?filters[externalid]=' . $customerId . '&filters[email]=' . $email), true);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    public function getIdActiveCampaignCustomer($email)
    {
        $activecampaignId = '';
        $resultCustomer = json_decode($this->_apiCall('contacts?&filters[email]=' . $email), true);
        if (isset($resultCustomer['contacts'][0]['id']) && $resultCustomer['contacts'][0]['id']) {
            $activecampaignId = $resultCustomer['contacts'][0]['id'];
        }

        return $activecampaignId;
    }

    public function getIdActiveCampaignCustomerCommerce($activecampaignId, $email)
    {
        $activeCampaignCommerceId = '';
        $resultCustomerCommerce = json_decode($this->_apiCall('ecomCustomers?filters[externalid]=' . $activecampaignId . '&filters[email]=' . $email), true);
        if (isset($resultCustomerCommerce['ecomCustomers'][0]['id']) && $resultCustomerCommerce['ecomCustomers'][0]['id']) {
            $activeCampaignCommerceId = $resultCustomerCommerce['ecomCustomers'][0]['id'];
        }

        return $activeCampaignCommerceId;
    }

    public function infoDeal($title)
    {
        try {
            return json_decode($this->_apiCall('deals?&filters[title]=' . $title), true);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    public function addDeal($deal)
    {
        return  json_decode($this->_apiCall('deals/', 'POST', $deal), true);
    }

    public function uptDeal($idDeal, $deal)
    {
        return  json_decode($this->_apiCall('deals/' . $idDeal, 'PUT', $deal), true);
    }

    public function addDealCustomField($dealCustomField)
    {
        return  $this->_apiCall('dealCustomFieldData/', 'POST', $dealCustomField);
    }
}
