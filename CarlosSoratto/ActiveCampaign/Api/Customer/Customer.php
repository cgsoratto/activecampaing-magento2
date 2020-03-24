<?php

namespace CarlosSoratto\ActiveCampaign\Api\Customer;

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
                "email" => $customerData->getEmail(),
                "firstName" => $customerData->getFirstname(),
                "lastName" => $customerData->getLastname(),
                "status" => 0
            ]
        ];
        return $this->_api->createContact($params);
    }

    public function createContactOnlyEmail($customerEmail)
    {
        $params = [
            "contact" => [
                "email" => $customerEmail,
                "status" => 0
            ]
        ];
        return $this->_api->createContact($params);
    }

    public function createCommerceContact($activeCampaignId, $email, $acceptsMarketing = false)
    {
        $params = [
            "ecomCustomer" => [
                "connectionid" => 1,
                "externalid" => $activeCampaignId,
                "email" => $email,
                "acceptsMarketing" => $acceptsMarketing
            ]
        ];

        return $this->_api->createEcommerceCustomer($params);
    }
}
