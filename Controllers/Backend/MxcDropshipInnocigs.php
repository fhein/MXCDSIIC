<?php

use MxcDropship\Dropship\DropshipManager;
use Shopware\Components\CSRFWhitelistAware;
use MxcDropship\MxcDropship;
use Shopware\Models\Order\Status;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use Shopware\Models\Article\Detail;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class Shopware_Controllers_Backend_MxcDropshipInnocigs extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private $statusPanelTemplate = '<div style="width:100%%;text-align:center;background:%s;color:%s;padding:5px;">%s</div>';

    protected $db;

    protected $panels;

    private $services;

    //////////////////////////////////////////////////////////////////////////
    ///  Order status

    public function getWhitelistedCSRFActions()
    {
        return [
            'getDropshipStatusPanel',
            'register',
            'unregister',
            'getSettings'
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
        $paymentAccepted = $paymentStatus == Status::PAYMENT_STATE_COMPLETELY_PAID
            || $paymentStatus = Status::PAYMENT_STATE_PARTIALLY_INVOICED;

        if ($paymentAccepted && $status == DropshipManager::DROPSHIP_STATUS_OPEN) {
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

    //////////////////////////////////////////////////////////////////////////
    ///  Article registration

    public function registerAction()
    {
        try {
            $services = $this->getServices();
            $log = $services->get('logger');

            /** @var ArticleRegistry $registry */
            $registry = $services->get(ArticleRegistry::class);

            $request = $this->Request();
            $deliveryMode = $request->getParam('deliveryMode', DropshipManager::MODE_DROPSHIP_ONLY);
            $productNumber = trim($request->getParam('productNumber', null));
            $detailId = $request->getParam('detailId', null);

            [$result, $settings] = $registry->register($detailId, $productNumber, $deliveryMode);

            switch ($result) {
                case ArticleRegistry::NO_ERROR:
                    $this->View()->assign(['success' => true, 'data' => $settings]);
                    break;

                case ArticleRegistry::ERROR_PRODUCT_UNKNOWN:
                    $message = 'Unbekanntes Produkt: ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case ArticleRegistry::ERROR_DUPLICATE_REGISTRATION:
                    $message = 'Doppelte Registrierung für ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case ArticleRegistry::ERROR_INVALID_ARGUMENT:
                    $this->View()->assign(['success' => false, 'info' => []]);
                    break;

                default:
                    $message = 'Unbekannter Fehler.';
                    $this->View()->assign(['success' => false, 'info' => ['title' => 'Fehler', 'message' => $message]]);
                    break;
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function getSettingsAction()
    {
        try {
            $detailId = $this->Request()->getParam('detailId', null);

            $services = $this->getServices();
            /** @var ArticleRegistry $registry */
            $registry = $services->get(ArticleRegistry::class);

            $settings = $registry->getSettings($detailId);

            $this->View()->assign(['success' => true, 'data' => $settings]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function unregisterAction()
    {
        $request = $this->Request();
        $detailId = $request->getParam('detailId', null);
        /** @var Detail $detail */

        $services = $this->getServices();
        /** @var ArticleRegistry $registry */
        $registry = $services->get(ArticleRegistry::class);
        $registry->unregister($detailId);
        $message = 'Dropship registration deleted.';
        $this->View()->assign(['success' => true, 'info' => ['title' => 'Erfolg', 'message' => $message]]);
    }

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $this->getServices()->get('logger')->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'info' => ['title' => 'Exception', 'message' => $e->getMessage()]]);
    }

    protected function getServices() {
        return $this->services ?? $this->services = MxcDropshipInnocigs::getServices();
    }
}
