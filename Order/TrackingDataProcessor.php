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

    /** @var array */
    protected $order;

    /** @var DropshipStatus */
    protected $dropshipStatus;

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

    public function __construct(ApiClient $client, DropshipStatus $dropshipStatus)
    {
        $this->client = $client;
        $this->dropshipStatus = $dropshipStatus;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    public function updateTrackingData(array $order, $dropshipManager)
    {
        $this->dropshipManager = $dropshipManager;
        $this->order = $order;
        try {
            if ($dropshipManager->getSupplierOrderDetailsCount($this->supplier, $order['orderID']) == 0) return null;
            // if InnoCigs tracking info was already processed we have nothing to do
            if ($order['mxcbc_dsi_ic_status'] == DropshipManager::DROPSHIP_STATUS_CLOSED) return null;
            $trackingInfo = $this->getTrackingInfo();
            return $this->handleTrackingInfo($trackingInfo);
        } catch (Throwable $e) {
            $context = $dropshipManager->handleDropshipException(
                $this->supplier,
                'updateTrackingData',
                $e,
                true,
                $order
            );
            return $this->dropshipStatus->setOrderStatus($order, $context);
        }
    }

    protected function handleTrackingInfo(array $trackingInfo = null)
    {
        if ($trackingInfo === null) return null;

        $contextId = $trackingInfo['contextId'];
        $context = $this->dropshipManager->getNotificationContext($this->supplier, 'updateTrackingData', $contextId, $this->order);
        if (isset($trackingInfo['trackings']))
        {
            $context['trackings'] = $trackingInfo['trackings'];
            $this->setTrackingData($trackingInfo);
        }

        $status = $context['status'];
        $message = $context['message'];
        $orderId = $this->order['orderID'];
        $this->dropshipStatus->setOrderDetailStatus($orderId, $status, $message);
        $this->dropshipStatus->setOrderStatus($this->order, $context);
        $this->dropshipManager->notifyStatus($context, $this->order);
        return $context;
    }

    protected function getTrackingInfo()
    {
        $orderNumber = $this->order['ordernumber'];

        $info = $this->trackingDataByOrderCache[$orderNumber];
        if (! empty($info)) return $info;

        $info = $this->getTrackingData();
        if ($info !== null) {
            $this->trackingDataByOrderCache[$orderNumber] = $info;
        }
        return $info;
    }

    protected function getTrackingData()
    {
        $date = new DateTime();
        $date = $date->format('Y-m-d');

        // this caching prevents multiple API calls for the same date
        $data = $this->trackingDataByDateCache[$date];
        if ($data === null) {
            // this caching prevents multiple API calls for the same date
            // and sets up $this->trackingDataByOrderCache for all orders of that date
            $data = $this->client->getTrackingData($date);
            $this->trackingDataByDateCache[$date] = $data;
        }

        $this->processTrackingRawData($data);
        return $this->trackingDataByOrderCache[$this->order['ordernumber']];
    }

    protected function processTrackingRawData(array $data)
    {
        $this->processCancellations($data['CANCELLATION']);
        $this->processTrackingData($data['TRACKING']);
    }

    protected function processTrackingData(?array $data)
    {
        if (empty($data)) return;
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
                'status'        => DropshipManager::DROPSHIP_STATUS_CLOSED,
                'contextId'     => 'STATUS_SUCCESS',
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
        $trackings = [];
        foreach ($data as $trackingInfo) {
            $trackingId = $trackingInfo['CODE'];
            $carrier = $trackingInfo['CARRIER'];
            $trackingLink = $trackingId;
            $link = $this->trackingLinks[$carrier];
            if ($link !== null) {
                $trackingLink = str_replace('{$trackingId}', $trackingId, $link);
            }
            $trackings[] = [
                'carrier'    => $carrier,
                'trackingId' => $trackingId,
                'trackingLink' => $trackingLink,
                'receiver'   => $this->getTrackingReceiver($trackingInfo),
            ];
        }
        return $trackings;
    }

    protected function getTrackingReceiver(array $trackingInfo)
    {
        $receiver = [];
        foreach ($trackingInfo['RECEIVER'] as $key => $value) {
            $receiver[$this->receiverKeyReplace[$key]] = $value;
        }
        return $receiver;
    }

    protected function processCancellations(?array $data)
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
                'status'      => DropshipManager::DROPSHIP_STATUS_CANCELLED,
                'contextId'   => 'ORDER_CANCELLED',
            ];
        }
    }

    protected function setTrackingData(array $trackingInfo)
    {
        $trackings = $trackingInfo['trackings'];
        $trackingIds = array_column($trackings, 'trackingId');
        $carriers = array_column($trackings, 'carrier');

        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_ic_tracking_ids = :trackingIds,
                oa.mxcbc_dsi_ic_carriers     = :carriers
            WHERE                
                oa.orderID = :id
            ', [
                'trackingIds'   => implode(', ', $trackingIds),
                'carriers'      => implode(', ', $carriers),
                'id'            => $this->order['orderID'],
            ]
        );
    }

    // retrieve an array containing the InnoCigs tracking ids
    // note: we ignore the carriers here because Shopware does not support multiple carriers
    public function getTrackingIds(array $order, $dropshipManager)
    {
        $trackings = $this->db->fetchRow('
            SELECT 
                oa.mxcbc_dsi_ic_tracking_ids as ids
            FROM
                s_order_attributes oa
            WHERE
                oa.orderID  = :id
        ', ['id' => $order['orderID']]);
        $trackingIds = $trackings['ids'];
        if (empty($trackingIds)) return [];
        return array_map('trim', explode(',', $trackingIds));
    }
}