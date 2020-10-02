<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
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
            $panels = $this->getPanels();
            $orderList = $view->getAssign('data');
            foreach ($orderList as &$order) {
                $attr = $this->db->fetchAll(
                    'SELECT * from s_order_attributes oa WHERE oa.orderID = :orderId',
                    ['orderId' => $order['id']]
                )[0];
                $panels = $this->getPanels();
                $status = $attr['mxcbc_dsi_ic_status'];
                $color = $panels[$status]['background'];
                $order['mxcbc_dsi_bullet_background_color'] = $color;
                $order['mxcbc_dsi_bullet_title'] = $panels[$status]['message'];
            }
            $view->clearAssign('data');
            $view->assign('data', $orderList);
        }

        $view->extendsTemplate('backend/mxc_dropship_innocigs/order/view/detail/overview.js');
        $view->extendsTemplate('backend/mxc_dropship_innocigs/order/view/list/list.js');


//
//                $buttonStatus = 1;
//                $buttonDisabled = false;
//                $view = $args->getSubject()->View();
//                $orderList = $view->getAssign('data');
//
//                // Check here if dropship-article exist
//                foreach ($orderList as &$order) {
//                    foreach ($order['details'] as $details_key => $details_value) {
//
//                        $attribute = Shopware()->Db()->fetchRow('
//                          SELECT
//                              *
//                          FROM
//                              s_order_details_attributes
//                          WHERE
//                              detailID = ?
//                          ', array($order['details'][$details_key]['id'])
//                        );
//
//                        $order['details'][$details_key]['attribute'] = $attribute;
//
//                        $orderDropshipStatus = $this->getOrderDropshipStatus($order['id']);
//                        $orderDropshipIsActive = $this->getOrderDropshipIsActive($order['id']);
//
//                        $order['dc_dropship_status'] = $orderDropshipStatus;
//                        $order['dc_dropship_active'] = $orderDropshipIsActive;
//
//                        $fullOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($order['id']);
//
//                        if (Shopware()->Config()->get('dc_auto_order')) {
//
//                            if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                                $showEditor = true;
//                                $buttonDisabled = true;
//                                $buttonStatus = 1;
//                            } else if ($orderDropshipIsActive == 1) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            }
//
//                            if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            }
//
//                            if ($orderDropshipStatus == -100) {
//                                $buttonDisabled = false;
//                                $showEditor = true;
//                                $buttonStatus = 3;
//                            }
//
//                        } else {
//
//                            if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            } else {
//
//                                if ($orderDropshipIsActive == 1) {
//                                    $showEditor = true;
//                                    $buttonDisabled = true;
//                                    $buttonStatus = 0;
//                                }
//
//                                if ($orderDropshipIsActive == 0) {
//                                    $buttonDisabled = false;
//                                    $showEditor = true;
//                                    $buttonStatus = 1;
//                                }
//
//                                if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                                    $buttonDisabled = true;
//                                    $buttonStatus = 0;
//                                }
//
//                                if ($orderDropshipStatus == -100) {
//                                    $buttonDisabled = false;
//                                    $showEditor = true;
//                                    $buttonStatus = 3;
//                                }
//                            }
//                        }
//
//
//                        if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                            $bulletColor = 'darkorange';
//                        } else if ($orderDropshipIsActive == 1) {
//                            $bulletColor = 'limegreen';
//                        } else if ($orderDropshipIsActive == 0) {
//                            $bulletColor = 'darkorange';
//                        }
//
//                        if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                            $bulletColor = $orderDropshipStatus == 100 ? 'limegreen' : 'dodgerblue';
//                        }
//
//                        if ($orderDropshipIsActive == 1 && $orderDropshipStatus == 200) {
//                            $bulletColor = '#ff0090';
//                        }
//
//                        if ($orderDropshipStatus == -100) {
//                            $bulletColor = 'red';
//                        }
//
//                        if (!empty($order['details'][$details_key]['attribute']['dc_name_short'])) {
//                            $order['is_dropship'] = '<div style="width:16px;height:16px;background:' . $bulletColor . ';color:white;margin: 0 auto;text-align:center;border-radius: 7px;padding-top: 2px;" title="Bestellung mit Dropshipping Artikel">&nbsp;</div>';
//                        }
//
//                        if ($buttonStatus == 1) {
//                            $order['dcUrl'] = './dc/markOrderAsDropship';
//                            $order['dcButtonText'] = 'Dropshipping-Bestellung aufgeben';
//                        } else if ($buttonStatus == 3) {
//                            $order['dcUrl'] = './dc/renewOrderAsDropship';
//                            $order['dcButtonText'] = 'Dropshipping-Bestellung erneut übermitteln';
//                        }
//
//                        $order['viewDCOrderButtonDisabled'] = $buttonDisabled;
//                        $order['viewDCOrderButton'] = $buttonStatus;
//                        $order['viewDCShowEditor'] = $showEditor;
//                    }
//                }
//
//                // Overwrite position data
//                $view->clearAssign('data');
//                $view->assign(
//                    array('data' => $orderList)
//                );
//
//                $view = $args->getSubject()->View();
//                $view->addTemplateDir($this->Path() . 'Views/');
//
//                $view->extendsTemplate('backend/dcompanion/order/store/dc_sources.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/detail/overview.js');
//                $view->extendsTemplate('backend/dcompanion/order/model/position.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/detail/position.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/list/list.js');
//                $view->extendsTemplate('backend/dcompanion/order/model/order.js');
//                break;
    }
}