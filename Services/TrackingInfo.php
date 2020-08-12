<?php

namespace MxcDropshipInnocigs\Services;

class TrackingInfo
{
    private $dropshipId;
    private $dropshipOrderNumber;
    private $carrier;
    private $trackingId;
    private $receiver;

    public function __construct(array $trackingInfo)
    {
        $this->dropshipId = $trackingInfo['DROPSHIP_ID'];
        $this->dropshipOrderNumber = $trackingInfo['ORDERS_NUMBER'];
        $info = $trackingInfo['TRACKINGS']['TRACKINGINFO'];
        $this->carrier = $info['CARRIER'];
        $this->trackingId = $info['CODE'];
        $this->receiver = $info['RECEIVER'];
    }

    public function getDropshipOrderNumber()
    {
        return $this->dropshipOrderNumber;
    }

    public function getDropshipId()
    {
        return $this->dropshipId;
    }

    public function getMailContext()
    {
        $cursor = $this->receiver['COMPANY'];
        $company = !is_array($cursor) ? $cursor : '';

        $cursor = $this->receiver['COMPANY2'];
        $company2 = !is_array($cursor) ? $cursor : '';

        return [
            'orderNumber'   => $this->dropshipOrderNumber,
            'dropshipId'    => $this->dropshipId,
            'carrier'       => $this->carrier,
            'trackingId'    => $this->trackingId,
            'company'       => $company,
            'company2'      => $company2,
            'firstName'     => $this->receiver['FIRSTNAME'],
            'lastName'      => $this->receiver['LASTNAME'],
            'streetAddress' => $this->receiver['STREET_ADDRESS'],
            'zipCode'       => $this->receiver['POSTCODE'],
            'city'          => $this->receiver['CITY'],
            'countryCode'   => $this->receiver['COUNTRY_CODE'],
        ];
    }
}