<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use DateTime;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;

class TrackingDataProcessor implements AugmentedObject
{
    use DatabaseAwareTrait;

    protected $client;
    protected $supplier;

    protected $trackingDataByDateCache;
    protected $trackingDataByOrderCache;

    protected $receiverKeyReplace = [
        'COMPANY'        => 'company',
        'COMPANY2'       => 'company2',
        'FIRSTNAME'      => 'firstName',
        'LASTNAME'       => 'lastName',
        'STREET_ADDRESS' => 'streetAddress',
        'POSTCODE'       => 'zipCode',
        'CITY'           => 'city',
        'COUNTRY_CODE'   => 'countryCode',
    ];

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    public function updateTrackingData(array $order)
    {
        try {
            $trackingInfo = $this->getTrackingInfo($order);
        } catch (Throwable $e) {

        }
    }

    protected function getTrackingInfo(array $order)
    {
        $orderNumber = $order['ordernumber'];
        $info = $this->trackingDataByOrderCache[$orderNumber] ?? $this->getTrackingData($order);
        $this->trackingDataByOrderCache[$orderNumber] = $info;
        return $info;
    }

    protected function getTrackingData(array $order)
    {
        $orderId = $order['orderID'];
        $orderNumber = $order['ordernumber'];
        $details = $this->getOrderDetails($orderId);
        $detail = $details[0];

        $date = $detail['mxcbc_dsi_date'];
        $date = new DateTime($date);
        $date = $date->format('Y-m-d');

        // this caching prevents multiple API calls for the same date
        $data = $this->trackingDataByDateCache[$date] ?? $this->client->getTrackingData($date);
        $this->trackingDataByDateCache[$date] = $data;

        $this->processTrackingRawData($data);
        return $this->trackingDataByOrderCache[$orderNumber];
    }

    protected function processTrackingRawData(array $data)
    {
        $this->processCancellations($data['CANCELLATION']);
        $this->processTrackingData($data['TRACKING']);
    }

    protected function processTrackingData(array $data)
    {
        if (isset($data['DROPSHIP']['DROPSHIP_ID'])) {
            $temp['DROPSHIP'][0] = $data['DROPSHIP'];
            $data = $temp;
        }
        $data = $data['DROPSHIP'];
        foreach ($data as $trackingData) {
            $trackings = $this->processTrackings($trackingData['TRACKINGS']);
            $orderNumber = $trackingData['ORDERS_NUMBER'];
            $this->trackingDataByOrderCache[$orderNumber] = [
                'dropshipId'    => $trackingData['DROPSHIP_ID'],
                'orderNumber'   => $orderNumber,
                'trackings'     => $trackings,
                'status'        => DropshipManager::ORDER_STATUS_TRACKING_DATA,
            ];
        }
    }

    protected function processTrackings(array $data)
    {
        if (isset($data['TRACKINGINFO']['CARRIER'])) {
            $temp['TRACKINGINFO'][0] = $data['TRACKINGINFO'];
            $data = $temp;
        }
        $data = $data['TRACKINGINFO'];
        $trackingInfos = [];
        foreach ($data as $trackingInfo) {
            $trackingInfos[] = [
                'carrier'    => $trackingInfo['CARRIER'],
                'trackingId' => $trackingInfo['CODE'],
                'receiver'   => $this->getTrackingReceiver($trackingInfo),
            ];
        }
        return $trackingInfos;
    }

    protected function getTrackingReceiver(array $trackingInfo)
    {
        $receiver = [];
        foreach ($trackingInfo['RECEIVER'] as $key => $value) {
            $receiver[$this->receiverKeyReplace[$key]] = $value;
        }
        return $receiver;
    }

    protected function processCancellations(array $data)
    {
        if (empty($data)) {
            return;
        }
        if (isset($data['DROPSHIP']['DROPSHIP_ID'])) {
            $temp['DROPSHIP'][0] = $data['DROPSHIP'];
            $data = $temp;
        }
        $data = $data['DROPSHIP'];
        foreach ($data as $cancellation) {
            $orderNumber = $cancellation['ORDERS_NUMBER'];
            $this->trackingDataByOrderCache[$orderNumber] = [
                'dropshipId'  => $cancellation['DROPSHIP_ID'],
                'orderNumber' => $orderNumber,
                'message'     => $cancellation['MESSAGE'],
                'status'      => DropshipManager::ORDER_STATUS_CANCELLED,
            ];
        }
    }

    private function getOrderDetails(int $orderId)
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order_details od
            LEFT JOIN s_order_details_attributes oda ON oda.detailID = od.id
            WHERE od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ', ['orderId' => $orderId, 'supplier' => $this->supplier]);
    }
}