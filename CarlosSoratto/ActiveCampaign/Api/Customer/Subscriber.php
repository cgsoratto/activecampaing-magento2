<?php

namespace CarlosSoratto\ActiveCampaign\Api\Customer;

class Subscriber
{
    protected $_api;
    public function __construct(
        \CarlosSoratto\ActiveCampaign\Helper\Api $api
    ) {
        $this->_api = $api;
    }

    public function addSubscriber($infoCampaignCommerceId, $emailCustomer)
    {
        $params = [
            "ecomCustomer" => [
                "acceptsMarketing" => 1,
            ]
        ];
        return $this->_api->updateEcommerceCustomer($infoCampaignCommerceId, $params, $emailCustomer);
    }
}
