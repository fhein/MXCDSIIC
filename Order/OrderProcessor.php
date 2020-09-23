<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Log\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Exception\DropshipException;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class OrderProcessor implements AugmentedObject
{
    use ClassConfigAwareTrait;
    use DatabaseAwareTrait;
    use LoggerAwareTrait;

    /** @var DropshipOrder */
    protected $dropshipOrder;

    /** @var DropshipLogger */
    protected $dropshipLogger;

    /** @var ApiClient */
    protected $client;

    /** @var string */
    protected $supplier;

    protected $shippingAddress;

    public function __construct(DropshipOrder $dropshipOrder, DropshipLogger $dropshipLog, ApiClient $client)
    {
        $this->dropshipOrder = $dropshipOrder;
        $this->dropshipLogger = $dropshipLog;
        $this->client = $client;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    // $order is a join of s_order and s_order_attributes, order id is $order['orderID']
    public function processOrder(array $order)
    {
        $orderId = $order['orderID'];
        // get all order details to be ordered from InnoCigs
        $details = $this->getOrderDetails($orderId);
        // we have nothing to do on this order so we report OK
        if (empty($details)) {
            return;
        }

        $this->shippingAddress = $this->getShippingAddress($orderId);
        try {
            $this->validateShippingAddress($this->shippingAddress);
            $this->dropshipOrder->create($order['ordernumber'], $this->shippingAddress);
            $this->addOrderDetails($order['ordernumber'], $details);
            $result = $this->dropshipOrder->send();
            $this->setOrderStatus(
                $orderId,
                $result['status'] == 'OK' ? DropshipManager::ORDER_STATUS_SENT : DropshipManager::ORDER_STATUS_ERROR,
                $result['message'],
                $result['dropshipId'],
                $result['dropshipOrderId']
            );
        } catch (DropshipException $e) {
            $this->handleOrderException($e, $order);
        }
    }

    public function handleOrderException(DropshipException $e, array $order)
    {
        $code = $e->getCode();
        $context = $this->classConfig['error_responses'][$code];
        $context['orderNumber'] = $order['ordernumber'];
        switch ($code) {
            case DropshipException::ORDER_DROPSHIP_NOK:
                $context['info'] = $e->getDropshipInfo();
                $context['errors'] = $e->getSupplierErrors();
                break;
            case DropshipException::MODULE_API_SUPPLIER_ERRORS:
                $context['errors'] = $e->getSupplierErrors();
                break;
            case DropshipException::ORDER_POSITIONS_ERROR:
                $context['errors'] = $e->getPositionErrors();
                break;
            case DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR:
                $context['errors'] = $e->getAddressErrors();
                $context['shippingaddress'] = $this->shippingAddress;
                break;
            default:
                // we cover all cases, so this is just sanitary
                throw $e;
        }
        $this->dropshipLog($order, $context);
        $this->sendMail($context);
    }

    public function sendMail($context)
    {
        $context['mailBody'] = str_replace('{$orderNumber}', $context['orderNumber'], $context['mailBody']);
        $dsMail = Shopware()->TemplateMail()->createMail($context['mailTemplate'], $context);
        $dsMail->addTo('support@vapee.de');
        $dsMail->clearFrom();
        $dsMail->setFrom('info@vapee.de', 'vapee.de Dropship');
        if (isset($context['mailSubject'])) {
            $subject = str_replace('{$orderNumber}', $context['orderNumber'], $context['mailSubject']);
            $dsMail->clearSubject();
            $dsMail->setSubject($subject);
        }
        $dsMail->send();
    }

    // collect all address validation errors and throw if any
    protected function validateShippingAddress(array $address)
    {
        $errors = [];
        if (strlen($address['company']) > 30) {
            $errors[] = DropshipException::RECIPIENT_COMPANY_TOO_LONG;
        }
        if (strlen($address['department']) > 30) {
            $errors[] = DropshipException::RECIPIENT_COMPANY2_TOO_LONG;
        }
        $firstName = $address['firstname'];
        if (strlen($firstName) < 2) {
            $errors[] = DropshipException::RECIPIENT_FIRST_NAME_TOO_SHORT;
        }
        $lastName = $address['lastname'];
        if (strlen($lastName) < 2) {
            $errors[] = DropshipException::RECIPIENT_LAST_NAME_TOO_SHORT;
        }
        if (strlen($firstName . $lastName) > 34) {
            $errors[] = DropshipException::RECIPIENT_NAME_TOO_LONG;
        }
        if (strlen($address['street']) > 35) {
            $errors[] = DropshipException::RECIPIENT_STREET_ADDRESS_TOO_LONG;
        }
        if (strlen($address['street']) < 5) {
            $errors[] = DropshipException::RECIPIENT_STREET_ADDRESS_TOO_SHORT;
        }
        if (strlen($address['zipcode']) < 4) {
            $errors[] = DropshipException::RECIPIENT_ZIP_TOO_SHORT;
        }
        if (strlen($address['city']) < 3) {
            $errors[] = DropshipException::RECIPIENT_CITY_TOO_SHORT;
        }
        if (strlen($address['iso']) !== 2) {
            $errors[] = DropshipException::RECIPIENT_INVALID_COUNTRY_CODE;
        }
        // ***!*** DEBUG
//        for ($i = 2201; $i < 2211; $i++) {
//            $errors[] = $i;
//        }
        // ***!***

        if (! empty($errors)) {
            throw ApiException::fromInvalidRecipientAddress($errors);
        }
    }

    protected function addOrderDetails(string $orderNumber, array $details)
    {
        $errors = [];

        // collect the errors for all details and throw if any
        $pos = 0;
        foreach ($details as $detail) {
            $valid = true;
            $productNumber = $this->getProductNumber($detail['articleDetailID']);
            $quantity = $detail['quantity'];
            $detailId = $detail['detailID'];

            // error preset
            $error = [
                'detailId'          => $detailId,
                'orderNumber'       => $orderNumber,
                'productNumber'     => $productNumber,
                'quantity'          => $quantity,
                'severity'          => DropshipLogger::ERR,
            ];

            // check if detail is a known product and in stock
            try {
                // throws on API and InnCigs errors
                $instock = $this->client->getStockInfo($productNumber);
                // ***!*** DEBUG pos
                if ($instock == 0 || $pos %2 == 0) {
                    $error['code'] = DropshipException::PRODUCT_OUT_OF_STOCK;
                    $error['message'] = sprintf('Produkt %s nicht auf Lager.', $productNumber);

                    $valid = false;
                    $errors[] = $error;
                } elseif ($instock < $quantity || $pos % 2 == 1) {
                    $error['code'] = DropshipException::POSITION_EXCEEDS_STOCK;
                    $error['message'] = sprintf('Lagerbestand für Produkt %s (%u) kleiner als %u.',
                        $productNumber,
                        $instock,
                        $quantity
                    );
                    $valid = false;
                    $errors[] = $error;
                }
                // ***!***
            } catch (DropshipException $e) {
                $code = $e->getCode();
                if ($code === DropshipException::MODULE_API_SUPPLIER_ERRORS) {
                    $error = array_merge($error, $e->getSupplierErrors()[0]);
                    $valid = false;
                    $errors[] = $error;
                } else {
                    // if we do not have supplier errors we have a general API error
                    // so a more general error handling is required
                    throw $e;
                }
            }

            if ($valid && empty($errors)) {
                $this->dropshipOrder->addPosition($productNumber, $quantity);
            }
            $pos++;
        }
        if (! empty($errors)) {
            throw ApiException::fromInvalidOrderPositions($errors);
        }
    }

    protected function postProcessOrder(array $result)
    {

    }

    // note: this is a draft for logging without position data;
    protected function dropshipLog(array $order, array $response)
    {
        $this->dropshipLogger->log(
            $response['severity'],
            $this->supplier,
            $response['message'],
            $order['ordernumber']
        );
        $errors = $response['errors'];
        if (empty($errors)) return;
        foreach ($errors as $error) {
            $this->dropshipLogger->log(
                $response['severity'],
                $this->supplier,
                $error['message'],
                $order['ordernumber'],
                $error['productNumber'],
                $error['quantity']
            );
        }
    }

    public function getShippingAddress(int $orderId)
    {
        return $this->db->fetchRow('
            SELECT sa.*, c.countryiso as iso FROM s_order_shippingaddress sa 
            LEFT JOIN s_core_countries c ON c.id = sa.countryID
            WHERE sa.orderID = ?',
            [$orderId]
        );
    }

    private function getOrderDetails(int $orderId)
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order_details od
            LEFT JOIN s_order_details_attributes oda ON oda.detailID = od.id
            WHERE od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ', ['orderId' => $orderId, 'supplier' => $this->supplier]);
    }

    private function getProductNumber(int $articleDetailId)
    {
        return $this->db->fetchOne('
            SELECT aa.mxcbc_dsi_ic_productnumber FROM s_articles_attributes aa
            WHERE aa.articledetailsID = :articleDetailId
        ', ['articleDetailId' => $articleDetailId]);
    }

    private function setOrderDetailStatus(int $detailId, int $code, string $message)
    {
        $this->db->executeUpdate('
        UPDATE s_order_details_attributes oda
        SET
            oda.mxcbc_dsi_message = :message,
            oda.mxcbc_dsi_status  = :code,
        WHERE 
            oda.detailID = :detailId
        ', ['detailId' => $detailId, 'code' => $code, 'message' => $message]);

    }

    protected function setOrderStatus(
        int $orderId,
        int $status,
        string $message,
        string $dropshipId = null,
        string $supplierOrderId = null
    ) {
        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_status         = :status,
                oa.mxcbc_dsi_message        = :message,
                oa.mxcbc_dsi_order_id       = :orderId,
                oa.mxcbc_dsi_dropship_id    = :dropshipId,
                oa.mxcbc_dsi_date           = :date
            WHERE                
                oa.orderID = :id
            ', [
                'status'     => $status,
                'message'    => $message,
                'orderId'    => $supplierOrderId,
                'dropshipId' => $dropshipId,
                'date'       => date('d.m.Y H:i:s'),
                'id'         => $orderId,
            ]
        );

    }

    protected function setDropshipStatus(int $orderId, $result)
    {
        $status = $result['status'] = 'OK' ? DropshipManager::ORDER_STATUS_SENT : DropshipManager::ORDER_STATUS_ERROR;
        $this->setOrderStatus(
            $orderId,
            $status,
            $result['message'],
            $result['dropshipId'],
            $result['orderId']
        );
    }
}

// Companion junk (postProcessOrder)

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
//    }
