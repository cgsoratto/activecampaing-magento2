<?php

namespace CarlosSoratto\ActiveCampaign\Api\Orders;

class Order
{
    protected $_api;
    public function __construct(
        \CarlosSoratto\ActiveCampaign\Helper\Api $api
    ) {
        $this->_api = $api;
    }


    public function infoOrder($externalid)
    {
        try {
            return json_decode($this->_api->_apiCall('ecomOrders?filters[externalid]=' . $externalid.'&filters[connectionid]=1'), true);
        } catch (Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
