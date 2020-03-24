<?php

namespace CarlosSoratto\ActiveCampaign\Api\Customer;

class Address
{
    protected $_api;
    protected $_logger;

    public function __construct(
        \CarlosSoratto\ActiveCampaign\Helper\Api $api,
        \CarlosSoratto\ActiveCampaign\Logger\Logger $logger
    ) {
        $this->_api = $api;
        $this->_logger = $logger;
    }

    public function addPhoneCustomer($email, $telephone)
    {
        $customerData = [
            "contact" => [
                'email' => $email,
                'phone' => $telephone
            ]
        ];

        try {
            $this->_api->_apiCall('contact/sync', 'POST', $customerData);
            $this->_logger->info("Add Phone Customer: " . $telephone . " - " . $email);
        } catch (Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    public function addAddressCustomer($customerAddress, $activeCampaignId)
    {
        $address =  implode(', ', $customerAddress->getStreet()) . ', ' . $customerAddress->getCity() . ', ' . $customerAddress->getRegion() . ', ' . $customerAddress->getPostcode(). ', ' . $customerAddress->getCountryId();
        $this->_api->addCustomFieldsValuesContact($activeCampaignId, 1, $address);
    }
}
