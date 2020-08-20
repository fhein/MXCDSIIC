<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Subscribers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Template_Manager;
use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Order\Order;
use Throwable;
use Zend_Db_Adapter_Exception;

class BackendOrderSubscriber implements SubscriberInterface
{
    private $services;

    /** @var EntityManager */
    private $modelManager;
    private $orderDetailsAttributesRepository = null;

    public function __construct()
    {
        $this->services = MxcDropshipInnocigs::getServices();
        $this->modelManager = $this->services->get('models');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Order::class . '::postPersist'   => 'onOrderPostPersist',
            Order::class . '::postUpdate'    => 'onOrderPostUpdate',
//            'Enlight_Controller_Action_PostDispatch_Backend_Order'          => 'onBackendOrderPostDispatch',
//            'Shopware_Modules_Order_SaveOrder_ProcessDetails'               => 'onSaveOrderProcessDetails',
//            'Shopware_Controllers_Backend_Order::savePositionAction::after' => 'onSavePositionActionAfter',
//            'Shopware_Controllers_Backend_Order::saveAction::after'         => 'onSavePositionAfter',

        ];
    }

    public function onOrderPostPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $name = 'unknown';
        if ($entity instanceof Order)
        {
            $name = 'Order';
        }
    }

    public function onOrderPostUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $name = 'unknown';
        if ($entity instanceof Order)
        {
            $name = 'Order';
        }
    }

    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
//        switch ($args->getRequest()->getActionName()) {
//            case 'save':
//                return true;
//                break;
//            default:
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
//                // Add tempolate-dir
//                $view = $args->getSubject()->View();
//                $view->addTemplateDir(
//                    $this->Path() . 'Views/'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/store/dc_sources.js'
//                );
//
//                // Extends the extJS-templates
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/detail/overview.js'
//                );
//
//                // Extends the extJS-templates
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/model/position.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/detail/position.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/list/list.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/model/order.js'
//                );
//                $this->__logger('return: ' . $args->getReturn());
//                break;
//        }
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onSaveOrderProcessDetails(Enlight_Event_EventArgs $args)
    {
//        $this->__logger(__METHOD__ . '#' . __LINE__);
//        $order = $args->getSubject();
//
//        // Iterate the basket
//        foreach ($args->details as $key => $val) {
//            $sArticle = $val['additional_details'];
//            if (isset($val['instock'])) {
//                // Find if any dropship article has stock-value
//                $stockList = $this->getDetailedCompanionSourceStockInfo($sArticle);
//                // If dropship stock-value has item, get the item with max instock
//                if (!empty($stockList)) {
//
//                    // Get OrderDetailsId
//                    //$orderDetailsId = $this->getOrderDetailsId(
//                    //	$args->orderId,
//                    //	$val['ordernumber']
//                    //);
//                    $orderDetailsId = $val['orderDetailId'];
//
//                    $orderID = $this->getOrderIdByOrderDetailsId(
//                        $orderDetailsId,
//                        $val['ordernumber']
//                    );
//
//                    // Get the highest stock
//                    $maxKey = max(array_keys($stockList));
//
//                    // Get Config-Reader
//                    $configReader = new ConfigReader();
//                    $companionSources = $configReader->getConfig();
//
//                    foreach ($companionSources as $source) {
//                        if ($source['nameshort'] == $stockList[$maxKey]['source']) {
//                            $sourceNameLong = $source['namelong'];
//                            $sourceNameShort = $source['nameshort'];
//                            break;
//                        }
//                    }
//
//                    // Save which source has the highest stock
//                    $this->setOrderDetailsSource(
//                        $orderDetailsId,
//                        $sourceNameLong,
//                        $sourceNameShort,
//                        $stockList[$maxKey]['stock']
//                    );
//
//                    // If Auto-Dropship.
//                    if (Shopware()->Config()->get('dc_auto_order') && Shopware()->Config()->get('dc_ic_pickware_warehousename') == '') {
//                        Shopware()->Db()->Query("
//							UPDATE s_articles_details
//							SET sales = sales - :quantity,
//								instock = instock + :quantity
//							WHERE ordernumber = :number",
//                            array(':quantity' => $val['quantity'], ':number' => $val['ordernumber'])
//                        );
//                    }
//
//                    // Set marker that order is a dropshipping-order
//                    $this->setOrderAsDropship(
//                        $orderID
//                    );
//
//                    if (!empty($order->sUserData['additional']['payment']['id'])) {
//                        $paymentID = $order->sUserData['additional']['payment']['id'];
//                    } else {
//                        $paymentID = $order->sUserData['additional']['user']['paymentID'];
//                    }
//
//                    $paymentData = Shopware()->Modules()->Admin()->sGetPaymentMeanById(
//                        $paymentID,
//                        Shopware()->Modules()->Admin()->sGetUserData()
//                    );
//
//                    if (Shopware()->Config()->get('dc_mail_send')) {
//                        $mail = Shopware()->Models()->getRepository('\Shopware\Models\Mail\Mail')->findOneBy(array('name' => 'DC_ORDER'));
//                        if ($mail) {
//
//                            $context = array(
//                                'orderNumber' => $order->sOrderNumber,
//                                'dc_auto_order' => Shopware()->Config()->get('dc_auto_order'),
//                                'payment' => array(
//                                    'name' => $paymentData['name'],
//                                    'description' => $paymentData['description']
//                                )
//                            );
//
//                            $mail = Shopware()->TemplateMail()->createMail('DC_ORDER', $context);
//                            $mail->addTo(Shopware()->Config()->get('mail'));
//
//                            $dcMailRecipients = $this->getConfigCcRecipients();
//                            if (!empty($dcMailRecipients)) {
//                                foreach ($dcMailRecipients as $recipient) {
//                                    $mail->addCc($recipient);
//                                }
//                            }
//
//                            $mail->send();
//                        }
//                    }
//                }
//            }
//        }
    }

    /**
     * Save the position data (dropshipper-name & dropshipper-quantity)
     *
     * @param Enlight_Hook_HookArgs $args
     */
    public function onSavePositionActionAfter(Enlight_Hook_HookArgs $args)
    {
/*        try {
            $suppliers = @$this->services->get('config')['drophip']['suppliers'];
            if (empty('suppliers')) return;
            $suppliers = array_filter($suppliers, 'strtolower');

            $params = $args->getSubject()->Request()->getParams();

            $id = $params['id'];
            $pSupplier = $params['mxc_dsi_supplier'];
            $pInStock = $params['mxc_dsi_instock'];
            $pPurchasePrice = $params['mxc_dsi_purchaseprice'];
            $this->setOrderDetailSupplier($id, $pSupplier, $pInStock, $pPurchasePrice);
        } catch (Throwable $t) {
            // @todo
        }*/

    }

    /**
     * @param Enlight_Hook_HookArgs $args
     */
    public function onSaveActionAfter(Enlight_Hook_HookArgs $args)
    {
//        $this->__logger(__METHOD__ . '#' . __LINE__);
//        $orderId = $args->getSubject()->Request()->getParam('id');
//        $dcDropshipActive = $args->getSubject()->Request()->getParam('dc_dropship_active');
//        $dcDropshipStatus = $args->getSubject()->Request()->getParam('dc_dropship_status');
//
//        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId);
//        if ($order->getPaymentStatus()->getName() == self::PAYMENT_COMPLETELY_PAID) {
//            $this->setOrderAsDropship($orderId);
//        }
//
//        Shopware()->Db()->Query('
//			UPDATE
//            	s_order_attributes
//			SET
//            	dc_dropship_active = :dcDropshipActive,
//				dc_dropship_status = :dcDropshipStatus
//			WHERE
//				orderID = :id
//		', array(
//            'id' => $orderId,
//            'dcDropshipActive' => $dcDropshipActive,
//            'dcDropshipStatus' => $dcDropshipStatus
//        ));
    }

    /**
     * @param $id
     * @param $supplier
     * @param $instock
     * @param $purchasePrice
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Zend_Db_Adapter_Exception
     */
    private function setOrderDetailSupplierById(
        $id,
        $supplier,
        $instock,
        $purchasePrice
    )
    {
        $attr = $this->getOrderDetailsAttributes($id);
        $this->modelManager->persist($attr);

        $attr->setDetailId($id);
        $attr->setSupplier($supplier);
        $attr->setInstock($instock);
        $attr->setPurchasePrice($purchasePrice);
        $this->modelManager->flush();

        $linkAttr = 'mxc_dsi_link';
        $sql = sprintf('UPDATE s_order_details_attributes SET %s = :attrId WHERE id = :id', $linkAttr);
        return Shopware()->Db()->query($sql, ['id' => $id, 'attrId' => $attr->getId()]);
    }

    private function getOrderDetailsAttributesRepository()
    {
        if ($this->orderDetailsAttributesRepository === null) {
            $this->orderDetailsAttributesRepository = $this->modelManager->getRepository(OrderDetailAttributes::class);
        }
        return $this->orderDetailsAttributesRepository;
    }

    public function getOrderDetailsAttributes(int $detailId)
    {
        $attr = $this->getOrderDetailsAttributesRepository()->findBy(['detailId' => $detailId]);
        if ($attr === null) {
            $attr = new OrderDetailAttributes();
            $this->modelManager->persist($attr);
        }
        return $attr;
    }


}