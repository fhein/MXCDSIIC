<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;

// To avoid code duplication this class provides two functions
// which are used by both OrderProcessor and TrackingDataProcessor

class DropshipStatus implements AugmentedObject
{
    use DatabaseAwareTrait;

    protected $supplier;

    /** @var DropshipManager */
    protected $dropshipManager;

    public function __construct(DropshipManager $dropshipManager)
    {
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
        $this->dropshipManager = $dropshipManager;
    }

    public function handleDropshipException(
        string $caller,
        Throwable $e,
        bool $sendMail,
        array $order = null,
        array $shippingAddress = null
    ) {
        if (! $e instanceof DropshipException) {
            $context = $this->dropshipManager->getNotificationContext($this->supplier, $caller, 'UNKNOWN_ERROR',
                $order);
            $message = $e->getMessage();
            $context['errors'] = [['code' => $e->getCode(), 'message' => $message]];
            $this->dropshipManager->notifyStatus($context, $order, $sendMail);
            $status = DropshipManager::ORDER_STATUS_UNKNOWN_ERROR;
            return $order !== null ? $this->setOrderStatus($order['orderID'], $status, $message) : null;
        }

        $code = $e->getCode();
        $context = $this->dropshipManager->getNotificationContext($this->supplier, $caller, $code, $order);
        switch ($code) {
            case DropshipException::MODULE_API_SUPPLIER_ERRORS:
                $context['errors'] = $e->getSupplierErrors();
                break;
            case DropshipException::ORDER_POSITIONS_ERROR:
                $context['errors'] = $e->getPositionErrors();
                break;
            case DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR:
                $context['errors'] = $e->getAddressErrors();
                $context['shippingaddress'] = $shippingAddress;
                break;
            case DropshipException::MODULE_API_XML_ERROR:
                $context['errors'] = $e->getXmlErrors();
                break;
            case DropshipException::MODULE_API_ERROR:
                $context['errors'] = $e->getApiErrors();
                break;
            default:
                $context = $this->dropshipManager->getNotificationContext($this->supplier, $caller, 'UNKNOWN_ERROR', $order);
                $context['errors'] = [['code' => $e->getCode(), 'message' => $e->getMessage()]];
        }
        $status = $context['status'];
        $message = $context['message'];
        $result = null;
        if ($order !== null) {
            $result = $this->setOrderStatus($order['orderID'], $status, $message);
        }
        $this->dropshipManager->notifyStatus($context, $order, $sendMail);
        return $result;
    }

    // set order status and dropship data
    public function setOrderStatus(
        int $orderId,
        int $status,
        string $message
    ) {

        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_ic_status       = :status,
                oa.mxcbc_dsi_ic_message      = :message
            WHERE                
                oa.orderID = :id
            ', [
                'status'     => $status,
                'message'    => $message,
                'id'         => $orderId,
            ]
        );

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    public function orderSuccessfullySent(array $order, array $data)
    {
        $orderId = $order['orderID'];
        $status = DropshipManager::ORDER_STATUS_SENT;
        $message = $data['message'];

        $this->setOrderDetailStatus($orderId, $status, $message);

        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_ic_status       = :status,
                oa.mxcbc_dsi_ic_message      = :message,
                oa.mxcbc_dsi_ic_dropship_id  = :dropshipId,
                oa.mxcbc_dsi_ic_date         = :date,
                oa.mxcbc_dsi_ic_order_id     = :orderId
            WHERE                
                oa.orderID = :id
            ', [
                'status'     => $status,
                'message'    => $message,
                'dropshipId' => $data['dropshipId'],
                'orderId'    => $data['supplierOrderId'],
                'date'       => date('d.m.Y H:i:s'),
                'id'         => $orderId,
            ]
        );

        $this->dropshipManager->notifyOrderSuccessfullySent($this->supplier, 'sendOrder', $order);

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    public function setOrderDetailStatus(int $orderId, int $status, string $message)
    {
        $details = $this->dropshipManager->getSupplierOrderDetails($this->supplier, $orderId);
        $detailIds = array_column($details, ['detailID']);
        $this->db->executeUpdate('
            UPDATE 
                s_order_details_attributes oda
            SET
                oda.mxcbc_dsi_status  = :status,
                oda.mxcbc_dsi_message = :message
            WHERE                
                oda.detailID IN (:detailIds)
            ', [
                'status'   => $status,
                'message'  => $message,
                'detailIds' => implode(',', $detailIds),
            ]
        );
    }
}