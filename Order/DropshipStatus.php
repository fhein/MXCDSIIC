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

    // set order status and dropship data
    public function setOrderStatus(array $order, array $context)
    {
        // if a recoverable error occured, we just update the status message but leave the order status
        // in order to enable an automatic retry
        $status = $context['recoverable'] ? $order['mxcbc_dsi_ic_status'] : $context['status'];
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
                'message'    => $context['message'],
                'id'         => $order['orderID'],
            ]
        );
        return $context;
    }

    public function setOrderDetailStatus(int $orderId, int $status, string $message)
    {
        $details = $this->dropshipManager->getSupplierOrderDetails($this->supplier, $orderId);
        $detailIds = array_column($details, 'detailID');
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