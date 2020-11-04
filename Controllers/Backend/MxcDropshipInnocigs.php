<?php

use MxcDropship\Dropship\DropshipManager;
use Shopware\Components\CSRFWhitelistAware;
use MxcDropship\MxcDropship;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Backend_MxcDropshipInnocigs extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private $statusPanelTemplate = '<div style="width:100%%;text-align:center;background:%s;color:%s;padding:5px;">%s</div>';

    protected $db;

    protected $panels;
    public function getWhitelistedCSRFActions()
    {
        return [
            'getDropshipStatusPanel'
        ];
    }

    protected function getDropshipStatusPanel($status, string $message = null, string $color = null)
    {
        $panel = $this->getPanels()[$status];
        $this->view->assign([
            'panel' => sprintf(
                $this->statusPanelTemplate,
                $color ?? $panel['background'],
                $panel['text'],
                $message ?? $panel['message'])
        ]);
        return null;
    }

    public function getDropshipStatusPanelAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $attr = $this->getOrderStatusInfo($orderId);
        $status = $attr['dropshipStatus'];
        $paymentStatus = $attr['paymentStatus'];
        $message = null;

        if ($status == DropshipManager::DROPSHIP_STATUS_OPEN && $paymentStatus == Status::PAYMENT_STATE_COMPLETELY_PAID) {
            $status = 'DROPSHIP_SCHEDULED';
        } else {
            $message = $attr['message'];
        }
        return $this->getDropshipStatusPanel($status, $message);
    }

    protected function getOrderStatusInfo(int $orderId)
    {
        return $this->getDb()->fetchAll('
            SELECT
                o.status as orderStatus,
                o.cleared as paymentStatus,
                oa.mxcbc_dsi_ordertype as orderType,
                oa.mxcbc_dsi_ic_status as dropshipStatus,
                oa.mxcbc_dsi_ic_message as message
                
            FROM s_order o
            LEFT JOIN s_order_attributes oa ON o.id = oa.orderID
            WHERE o.id = :orderId
        ', ['orderId' => $orderId])[0];
    }

    protected function getDb() {
        return $this->db ?? $this->db = MxcDropship::getServices()->get('db');
    }

    protected function getPanels() {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }
}
