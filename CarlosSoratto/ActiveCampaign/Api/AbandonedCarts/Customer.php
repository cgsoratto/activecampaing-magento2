<?php

namespace CarlosSoratto\ActiveCampaign\Api\AbandonedCarts;

class Customer
{
    protected $_api;
    public function __construct(
        \CarlosSoratto\ActiveCampaign\Helper\Api $api
    ) {
        $this->_api = $api;
    }

    public function createContact($customerData)
    {
        $params = [
            "contact" => [
                "email" => $customerData->getCustomerEmail(),
                "firstName" => $customerData->getCustomerFirstname(),
                "lastName" => $customerData->getCustomerLastname(),
                "status" => 0
            ]
        ];

        return $this->_api->createContact($params);
    }

    public function createCommerceContact($activeCampaignId, $email)
    {
        $params = [
            "ecomCustomer" => [
                "connectionid" => 1,
                "externalid" => $activeCampaignId,
                "email" => $email,
                "acceptsMarketing" => 0
            ]
        ];

        $activeCampaignCommerceId = $this->_api->createEcommerceCustomer($params);
        return $activeCampaignCommerceId;
    }
}
