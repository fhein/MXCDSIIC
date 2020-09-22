<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Log\LoggerAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Exception\DropshipOrderException;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class OrderProcessor implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use LoggerAwareTrait;


    /** @var DropshipOrder */
    protected $dropshipOrder;

    /** @var OrderErrorHandler*/
    protected $errorHandler;

    /** @var DropshipLogger */
    protected $dropshipLog;

    protected $supplier;

    public function __construct(DropshipOrder $dropshipOrder, DropshipLogger $dropshipLog, OrderErrorHandler $errorHandler)
    {
        $this->dropshipOrder = $dropshipOrder;
        $this->errorHandler = $errorHandler;
        $this->dropshipLog = $dropshipLog;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    // $order is a join of s_order and s_order_attributes, order id is $order['orderID']
    public function processOrder(array $order)
    {
        $result = [DropshipManager::STATUS_OK, []];
        $orderId = $order['orderID'];
        $sql = '
            SELECT * FROM s_order_details od
            LEFT JOIN s_order_details_attributes oda ON oda.detailID = od.id
            WHERE od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ';
        $details = $this->db->fetchAll($sql, ['orderId' => $orderId, 'supplier' => $this->supplier]);
        // we have nothing to do on this order so we report OK
        if (empty($details)) return $result;

        $shippingAddress = $this->getShippingAddress($orderId);
        try {
            $this->dropshipOrder->create($shippingAddress);

            foreach ($details as $detail) {
                $sql = '
                    SELECT aa.mxcbc_dsi_ic_productnumber FROM s_articles_attributes aa
                    WHERE aa.articledetailsID = :articleDetailId
                ';
                $productNumber = $this->db->fetchOne($sql, ['articleDetailId' => $detail['articleDetailID']]);
                $this->dropshipOrder->addPosition($productNumber, $detail['quantity']);
            }
            $info = $this->dropshipOrder->send();
        } catch (DropshipOrderException $e) {
            $result = $this->errorHandler->handleOrderException($e, $order);
        }
        $this->postProcessOrder($result);
        return $result;

    }

    // Loop through order details and get detail id, product number and ordered amount
    protected function getOrderPositions(array $details)
    {
        $dropshipPositions = [];
        foreach ($details as $detail) {
            if ($detail['mxcbc_dsi_ic_status'] != 'OK') {
                if ($detail['mxcbc_dsi_supplier'] !== $this->supplier) {
                    continue;
                }
                $dropshipPositions[] = [
                    'id'            => $detail['id'],
                    'productnumber' => $detail['mxcbc_dsi_ic_productnumber'],
                    'quantity'      => $detail['quantity'],
                ];
            }
        }
        return $dropshipPositions;
    }

    protected function postProcessOrder(array $result)
    {
//        if (Shopware()->Config()->get('dc_mail_send') || $errorCode) {
//            $mail = Shopware()->Models()->getRepository('\Shopware\Models\Mail\Mail')->findOneBy(['name' => 'DC_DROPSHIP_ORDER']);
//            if ($mail) {
//
//                $context = [
//                    'status'      => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'],
//                    'orderNumber' => $fullOrder['ordernumber'],
//                    'articles'    => $dropshipPositions['ic'],
//                    'orderInfo'   => $orderInfo
//                ];
//                $mail = Shopware()->TemplateMail()->createMail('DC_DROPSHIP_ORDER', $context);
//                $mail->addTo(Shopware()->Config()->Mail);
//
//                $dcMailRecipients = $this->getConfigCcRecipients();
//                if (! empty($dcMailRecipients)) {
//                    foreach ($dcMailRecipients as $recipient) {
//                        $mail->addCc($recipient);
//                    }
//                }
//
//                $mail->send();
//            }
//        }
//
//        if (! empty($errorCodeListForEmail)) {
//
//            $mail = Shopware()->Mail();
//            $mail->IsHTML(0);
//            $mail->From = Shopware()->Config()->Mail;
//            $mail->FromName = Shopware()->Config()->Mail;
//            $mail->Subject = 'Fehler beim Übermitteln von Aufträgen an Innocigs';
//            $mail->Body = $errorCodeListForEmail;
//
//            $dcMailRecipients = $this->getConfigCcErrorReciepents();
//            if (! empty($dcMailRecipients)) {
//                foreach ($dcMailRecipients as $recipient) {
//                    $mail->addCc($recipient);
//                }
//            }
//
//            $mail->ClearAddresses();
//            $mail->AddAddress(Shopware()->Config()->Mail, Shopware()->Config()->Mail);
//            $mail->Send();
//        }
    }



    private function getShippingAddress($orderId)
    {
        return $this->db->fetchRow('
            SELECT sa.*, c.countryiso as iso FROM s_order_shippingaddress sa 
            LEFT JOIN s_core_countries c ON c.id = sa.countryID
            WHERE sa.orderID = ?',
            [$orderId]
        );
    }

    // write the dropship order result information to all order positions
    private function updateDropshipInfo($positions, $info)
    {
        $ids = array_column($positions, 'id');
        return $this->db->Query('
          UPDATE
            s_order_details_attributes
          SET
            mxcbc_dsi_date = :date,
            mxcbc_dsi_status = :status,
            mxcbc_dsi_message = :message,
            mxcbc_dsi_id = :dropshipId,
            mxcbc_dsi_order_id = :orderId,
            mxcbc_dsi_infos = :info
          WHERE
            id IN (:ids)
        ', [
            'ids'        => $ids,
            'dropshipId' => $info['dropshipId'],
            'orderId'    => $info['orderId'],
            'date'       => date('d.m.Y H:i:s'),
            'status'     => $info['status'],
            'message'    => $info['message'],
            'info'       => $info['info']
        ]);
    }


}
