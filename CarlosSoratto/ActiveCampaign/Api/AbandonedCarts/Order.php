<?php

namespace CarlosSoratto\ActiveCampaign\Api\AbandonedCarts;

class Order
{
    protected $_api;
    public function __construct(
        \CarlosSoratto\ActiveCampaign\Helper\Api $api
    ) {
        $this->_api = $api;
    }

    public function infoOrder($externalcheckoutid)
    {
        try {
            return json_decode($this->_api->_apiCall('ecomOrders?filters[externalcheckoutid]=' . $externalcheckoutid . '&filters[connectionid]=1'), true);
        } catch (Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
