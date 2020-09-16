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

    protected $supplierId;

    public function __construct(DropshipOrder $dropshipOrder, DropshipLogger $dropshipLog, OrderErrorHandler $errorHandler)
    {
        $this->dropshipOrder = $dropshipOrder;
        $this->errorHandler = $errorHandler;
        $this->dropshipLog = $dropshipLog;
        $this->supplierId = MxcDropshipInnocigs::getModule()->getId();
    }

    // The $order array describes a new order which is paid, so drophip order needs to get send
    protected function processOrder(string $orderNumber, array $order)
    {
        $result = [DropshipManager::NO_ERROR, []];
        $orderId = $order['orderID'];
        $details = $order['details'];
        $shippingAddress = $this->getShippingAddress($orderId);

        // get all order positions which are scheduled for InnoCigs
        $positions = $this->getOrderPositions($details);
        if (empty($positions)) {
            return [ DropshipManager::NO_ERROR, []];
        }

        try {
            // throws if the shipping address does not comply to the InnoCigs address spec
            $this->dropshipOrder->create($shippingAddress);
            foreach ($positions as $position) {
                $this->dropshipOrder->addPosition(
                    $position['productnumber'],
                    $position['quantity']
                );
            }
            // throws on API errors and order position validation errors
            $info = $this->dropshipOrder->send();
            $this->updateDropshipInfo($positions, $info);
        } catch (DropshipOrderException $e) {
            $this->errorHandler->handleOrderException($e, $order);
        }

        $this->postProcessOrder();
        return $result;
    }

    // Loop through order details and get detail id, product number and ordered amount
    protected function getOrderPositions(array $details)
    {
        $dropshipPositions = [];
        foreach ($details as $detail) {
            if ($detail['mxcbc_dsi_ic_status'] != 'OK') {
                if ($detail['mxcbc_dsi_supplier'] !== $this->supplierId) {
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

    protected function postProcessOrder()
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
