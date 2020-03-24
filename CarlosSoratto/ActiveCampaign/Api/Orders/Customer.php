<?php

namespace CarlosSoratto\ActiveCampaign\Api\Orders;

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
                "phone" => $customerData->getTelephone(),
                "status" => 0
            ]
        ];

        $activecampaignId = $this->_api->createContact($params);
        $address =  implode(', ', $customerData->getStreet()) . ', ' . $customerData->getCity() . ', ' . $customerData->getRegion() . ', ' . $customerData->getPostcode(). ', ' . $customerData->getCountryId();
        $this->_api->addCustomFieldsValuesContact($activecampaignId, 1, $address);

        return $activecampaignId;
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

        return $this->_api->createEcommerceCustomer($params);
    }
}
