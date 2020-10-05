<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\Api\ApiClient;
use DateTime;
use MxcDropshipInnocigs\Exception\ApiException;
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
            if ($order['mxcbc_dsi_ic_status'] == DropshipManager::ORDER_STATUS_TRACKING_DATA) {
                return $this->handleTrackingInfo();
            }
            $trackingInfo = $this->getTrackingInfo();
            return $this->handleTrackingInfo($trackingInfo);
        } catch (Throwable $e) {
            [$status, $message] = $dropshipManager->handleDropshipException(
                $this->supplier,
                'updateTrackingData',
                $e,
                true,
                $order
            );
            return $this->dropshipStatus->setOrderStatus($order['orderID'], $status, $message);
        }
    }

    protected function handleTrackingInfo(array $trackingInfo = null)
    {
        if ($trackingInfo === null) {
            // if we do not have tracking infos we return the current order status
            return [
                'status'  => $this->order['mxcbc_dsi_ic_status'],
                'message' => $this->order['mxcbc_dsi_ic_message'],
            ];
        }

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
        $result =  $this->dropshipStatus->setOrderStatus($orderId, $status, $message);
        $this->dropshipManager->notifyStatus($context, $this->order);
        return $result;
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
        $date = new DateTime($this->order['mxcbc_dsi_ic_date']);
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
        return $this->trackingDataByOrderCache[$this->order['ordernumber']];
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

    protected function setTrackingData(array $trackingInfo)
    {
        $trackings = $trackingInfo['trackings'];
        $trackingIds = array_column($trackings, 'trackingId');
        $carriers = array_column($trackings, 'carrier');
        $this->setOrderTrackingData($trackingIds, $carriers);

    }

    protected function setOrderTrackingData(array $trackingIds, array $carriers)
    {
        $swTrackingIds = $this->order['trackingcode'];
        if (empty($swTrackingIds)) {
            $swTrackingIds = $trackingIds;
        } else {
            $swTrackingIds = array_map('trim', explode(',', $swTrackingIds));
            $swTrackingIds = array_unique(array_merge($swTrackingIds, $trackingIds));
        }

        $this->db->executeUpdate('
            UPDATE 
                s_order o
            INNER JOIN
                s_order_attributes oa ON oa.orderID = o.id
            SET
                o.trackingcode               = :trackingCode,
                oa.mxcbc_dsi_ic_tracking_ids = :trackingIds,
                oa.mxcbc_dsi_ic_carriers     = :carriers
            WHERE                
                o.id = :id
            ', [
                'trackingCode'  => implode(', ', $swTrackingIds),
                'trackingIds'   => implode(', ', $trackingIds),
                'carriers'      => implode(', ', $carriers),
                'id'            => $this->order['orderID'],
            ]
        );
    }
}