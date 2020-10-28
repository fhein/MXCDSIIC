<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Order\Status;
use Shopware_Components_Config;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

class BackendOrderSubscriber implements SubscriberInterface
{

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Logger */
    private $log;

    /** @var Shopware_Components_Config  */
    private $config;

    protected $panels;

    public function __construct()
    {
        $this->log = MxcDropshipInnocigs::getServices()->get('logger');
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order'                      => 'onBackendOrderPostDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDropshipInnocigs'  => 'onGetControllerPath',
        ];
    }

    public function onGetControllerPath(Enlight_Event_EventArgs $args)
    {
        return MxcDropshipInnocigs::PLUGIN_DIR . '/Controllers/Backend/MxcDropshipInnocigs.php';

    }

    protected function getPanels()
    {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }

    // this is the backend gui
    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getRequest();
        $action = $request->getActionName();

        if ($action == 'save') return;
        $view = $args->getSubject()->View();
        if ($action == 'getList') {
            $orderList = $view->getAssign('data');
            foreach ($orderList as &$order) {
                $bullet = $this->getBullet($order);
                $order['mxcbc_dsi_bullet_background_color'] = $bullet['background'];
                $order['mxcbc_dsi_bullet_title'] = $bullet['message'] ?? '';
            }
            $view->clearAssign('data');
            $view->assign('data', $orderList);
        }

        $view->extendsTemplate('backend/mxc_dropship_innocigs/order/view/detail/overview.js');
        $view->extendsTemplate('backend/mxc_dropship_innocigs/order/view/list/list.js');
    }

    public function getBullet(array $order)
    {
        $attr = $this->db->fetchAll(
            'SELECT * from s_order_attributes oa WHERE oa.orderID = :orderId',
            ['orderId' => $order['id']]
        )[0];
        $panels = $this->getPanels();
        $status = $attr['mxcbc_dsi_ic_status'];
        $message = $attr['mxcbc_dsi_ic_message'];
        $paymentStatus = $order['cleared'];
        if ($paymentStatus == Status::PAYMENT_STATE_COMPLETELY_PAID && $status == DropshipManager::DROPSHIP_STATUS_OPEN) {
            return $panels['DROPSHIP_SCHEDULED'];
        }
        $panel = $panels[$status];
        $panel['message'] = $message;
        return $panel;
    }
}