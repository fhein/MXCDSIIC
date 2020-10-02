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

    /** @var ApiClient */
    protected $client;

    /** @var DropshipManager */
    protected $dropshipManager;

    /** @var string */
    protected $supplier;

    /** @var string */
    protected $dropshipTime;

    protected $trackingLinks = [
        'DHL' => '<a href="https://nolp.dhl.de/nextt-online-public/de/search?piececode={$trackingId}">{$trackingId}</a>',
        'GLS' => '<a href="https://www.gls-pakete.de/sendungsverfolgung?trackingNumber={$trackingId}">{$trackingId}</a>',
        'UPS' => '<a href="https://www.ups.com/track?loc=de_DE&tracknum={$trackingId}">{$trackingId}</a>',
    ];


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

    public function updateTrackingData(array $order, $dropshipManager)
    {
        $this->dropshipManager = $dropshipManager;
        try {
            $details = $this->dropshipManager->getSupplierOrderDetails($this->supplier, $order['orderID']);
            if (empty ($details)) return null;
            // if we already have tracking infos for this order
            $status = $order['mxcbc_dsi_ic_status'];
            // @todo: check if null is ok, maybe we must return [ 'status' => ORDER_STATUS_TRACKING_DATA, 'message' => 'Ok.']
            if ($status == DropshipManager::ORDER_STATUS_TRACKING_DATA) return null;
            // because all InnoCigs details hold the same dropship date we need the first detail only
            $this->dropshipTime = $details[0]['mxcbc_dsi_date'];
            $trackingInfo = $this->getTrackingInfo($order);
            $context = $this->dropshipManager->getNotificationContext($this->supplier, $order, $trackingInfo['contextId']);
            if (isset($trackingInfo['trackings']))
            {
                $context['trackings'] = $trackingInfo['trackings'];
            }
            $this->dropshipManager->notifyStatus($order, $context);
        } catch (Throwable $e) {

        }
    }

    protected function getTrackingInfo(array $order)
    {
        $orderNumber = $order['ordernumber'];

        $info = $this->trackingDataByOrderCache[$orderNumber];
        if (! empty($info)) return $info;

        $info = $this->getTrackingData($order);
        if ($info !== null) {
            $this->trackingDataByOrderCache[$orderNumber] = $info;
        }
        return $info;
    }

    protected function getTrackingData(array $order)
    {
        $orderNumber = $order['ordernumber'];

        $date = new DateTime($this->dropshipTime);
        $date = $date->format('Y-m-d');

        // if we already processed tracking data for the given $date tracking info for this order would be available in
        // $this->trackingDataByOrderCache if it was available
        $data = $this->trackingDataByDateCache[$date];
        if ($data !== null) return null;

        // this caching prevents multiple API calls for the same date
        // and sets up $this->trackingDataByOrderCache for all orders of that date
        $data = $this->client->getTrackingData($date);
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
                'contextId'     => 'ORDER_TRACKING_DATA',
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
            $trackingId = $trackingInfo['CODE'];
            $carrier = $trackingInfo['CARRIER'];
            $trackingLink = $trackingId;
            $link = $this->trackingLinks[$carrier];
            if ($link !== null) {
                $trackingLink = str_replace('{$trackingId}', $trackingId, $link);
            }
            $trackingInfos[] = [
                'carrier'    => $carrier,
                'trackingId' => $trackingId,
                'trackingLink' => $trackingLink,
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
                'contextId'   => 'ORDER_CANCELLED',
            ];
        }
    }
}