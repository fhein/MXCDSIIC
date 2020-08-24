<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Log\LoggerAwareInterface;
use MxcCommons\Log\LoggerAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareInterface;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\Exception\DropshipOrderException;
use MxcDropshipIntegrator\Dropship\DropshipManager;
use Shopware\Models\Order\Status;

class OrderProcessor implements ModelManagerAwareInterface, DatabaseAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use LoggerAwareTrait;

    /** @var DropshipOrder */
    protected $dropshipOrder;

    public function __construct(DropshipOrder $dropshipOrder)
    {
        $this->dropshipOrder = $dropshipOrder;
    }

    // The $order array describes a new order which is paid, so drophip order needs to get send
    protected function processOrder(string $orderNumber, array $order)
    {
        $result = [DropshipManager::NO_ERROR, []];
        $orderId = $order['orderID'];
        $details = $order['details'];
        $shippingAddress = $this->getShippingAddress($orderId);

        // get all order positions which are scheduled for InnoCigs
        $dropshipPositions = $this->getOrderPositions($details);
        if (empty($dropshipPositions)) {
            return [ DropshipManager::NO_ERROR, []];
        }

        try {
            // throws if the shipping address does not comply to the InnoCigs address spec
            $this->dropshipOrder->create($order['ordernumber'], $shippingAddress);
            foreach ($dropshipPositions as $position) {
                $this->dropshipOrder->addPosition(
                    $position['productnumber'],
                    $position['quantity']
                );
            };
            // throws on API errors and order position validation errors
            $info = $this->dropshipOrder->send();
            $this->updateDropshipInfo($dropshipPositions, $info);
        } catch (DropshipOrderException $e) {
            $this->handleDropshipOrderException($e);
        }

        $this->postProcessOrder();
        return $result;
    }

    // Loop through order details and get detail id, product number and ordered amount
    protected function getOrderPositions(array $details)
    {
        $dropshipPositions = [];
        foreach ($details as $detail) {
            if ($detail['mxc_dsi_ic_status'] != 'OK') {
                if ($detail['mxc_dsi_supplier'] !== DropshipManager::SUPPLIER_INNOCIGS) {
                    continue;
                }
                $dropshipPositions[] = [
                    'id'            => $detail['id'],
                    'productnumber' => $detail['mxc_dsi_ic_productnumber'],
                    'quantity'      => $detail['quantity'],
                ];
            }
        }
        return $dropshipPositions;
    }

    protected function postProcessOrder()
    {
        if (Shopware()->Config()->get('dc_mail_send') || $errorCode) {
            $mail = Shopware()->Models()->getRepository('\Shopware\Models\Mail\Mail')->findOneBy(['name' => 'DC_DROPSHIP_ORDER']);
            if ($mail) {

                $context = [
                    'status'      => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'],
                    'orderNumber' => $fullOrder['ordernumber'],
                    'articles'    => $dropshipPositions['ic'],
                    'orderInfo'   => $orderInfo
                ];
                $mail = Shopware()->TemplateMail()->createMail('DC_DROPSHIP_ORDER', $context);
                $mail->addTo(Shopware()->Config()->Mail);

                $dcMailRecipients = $this->getConfigCcRecipients();
                if (! empty($dcMailRecipients)) {
                    foreach ($dcMailRecipients as $recipient) {
                        $mail->addCc($recipient);
                    }
                }

                $mail->send();
            }
        }

        if (! empty($errorCodeListForEmail)) {

            $mail = Shopware()->Mail();
            $mail->IsHTML(0);
            $mail->From = Shopware()->Config()->Mail;
            $mail->FromName = Shopware()->Config()->Mail;
            $mail->Subject = 'Fehler beim Übermitteln von Aufträgen an Innocigs';
            $mail->Body = $errorCodeListForEmail;

            $dcMailRecipients = $this->getConfigCcErrorReciepents();
            if (! empty($dcMailRecipients)) {
                foreach ($dcMailRecipients as $recipient) {
                    $mail->addCc($recipient);
                }
            }

            $mail->ClearAddresses();
            $mail->AddAddress(Shopware()->Config()->Mail, Shopware()->Config()->Mail);
            $mail->Send();
        }
    }

    protected function handleDropshipOrderException(DropshipOrderException $e)
    {
        switch ($e->getCode()) {
            case DropshipOrderException::INNOCIGS_ERRORS:
                break;
            case DropshipOrderException::DROPSHIP_NOK:
                break;
            case DropshipOrderException::POSITIONS_ERROR:
                break;
            case DropshipOrderException::RECIPIENT_ADDRESS_ERROR:
                break;
            case DropshipOrderException::API_EXCEPTION:
                break;
            case DropshipOrderException::POSITION_EXCEEDS_STOCK:
                break;
            case DropshipOrderException::PRODUCT_NUMBER_MISSING:
                break;
            case DropshipOrderException::PRODUCT_NOT_AVAILABLE:
                break;
            case DropshipOrderException::PRODUCT_UNKNOWN:
                break;
            case DropshipOrderException::PRODUCT_OUT_OF_STOCK:
                break;
            default:
                break;
        }

        if (isset($dropshipInfo['ERRORS']['ERROR'])) {
            $errorCodeListForEmail .= 'Bestellnummer: ' . $fullOrder['ordernumber'] . PHP_EOL . 'ErrorCode: ' . $dropshipInfo['ERRORS']['ERROR']['CODE'] . PHP_EOL . $dropshipInfo['ERRORS']['ERROR']['MESSAGE'] . PHP_EOL . '------' . PHP_EOL;
            $processedOrderNumbers .= 'NOK: ' . $fullOrder['ordernumber'] . PHP_EOL;
        } else {
            if ($dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'] == 'NOK') {
                $errorCode = $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ERRORS']['ERROR']['CODE'];
                $errorMessage = $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ERRORS']['ERROR']['MESSAGE'];
                $orderInfoMessage = $errorCode . '|' . $errorMessage;
                $processedOrderNumbers .= 'NOK: ' . $fullOrder['ordernumber'] . PHP_EOL;
            } else {
                $processedOrderNumbers .= 'OK: ' . $fullOrder['ordernumber'] . PHP_EOL;
            }

            $orderInfo = [
                'status'     => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'],
                'message'    => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['MESSAGE'],
                'dropshipId' => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['DROPSHIP_ID'],
                'orderId'    => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ORDERS_ID'],
                'info'       => $orderInfoMessage,
                'date'       => date('d.m.Y H:i:s')
            ];
            if (! $errorCode) {
                $this->setDropshipStatus($fullOrder['ordernumber'], 100);
                $this->setOrderStatusForArticle($fullOrder['ordernumber'], self::STATE_TRANSFER_ARTICLE);
            } else {
                $this->setDropshipStatus($fullOrder['ordernumber'], -100);
            }

        }
    }

    protected function handleApiException(ApiException $e)
    {
        switch($e->getCode()) {
            case ApiException::LOGIN_FAILED:
                $msg = 'Die Anmeldung bei der InnoCigs Dropship API ist fehlgeschlagen. Bitte überprüfen Sie '
                    . 'den Benutzernamen und das Passwort.';
                break;
            case ApiException::INVALID_XML:
                $msg = 'Das an InnoCigs übertragene XML ist fehlerhaft.';
                break;
            case ApiException::NO_DROPSHIP_DATA:
                $msg = 'Keine Dropship Daten vorhanden.';
                break;
            case ApiException::DROPSHIP_DATA_INCOMPLETE:
                $msg = 'Die überträgenen Dropship Daten sind unvollständig.';
                break;
            case ApiException::UNKNOWN_API_FUNCTION:
                $msg = 'Die aufgerufenen API Funktion existiert nicht.';
                break;
            case ApiException::MISSING_ORIGINATOR:
                $msg = 'Fehlende Absenderadresse.';
                break;
            case ApiException::INVALID_ORIGINATOR:
                $msg = 'Ungültige Absenderadresse.';
                break;
            case ApiException::PAYMENT_LOCKED:
                $msg = 'Ungültige Absenderadresse.';
                break;
            case ApiException::PAYMENT_LIMIT_EXCEEDED:
                $msg = 'Der Dropship-Auftrag wurde von InnoCigs abgelehnt, da Ihr Kreditrahmen ausgeschöpft ist.';
                break;
            case ApiException::XML_ALREADY_UPLOADED:
                $msg = 'Die XML Daten wurden bereits hochgeladen.';
                break;
            case ApiException::DROPSHIP_DATA_X_INCOMPLETE:
                $msg = 'Die Dropsip Daten sind unvollständig.';
                break;
            case ApiException::ORIGINATOR_DATA_X_MISSING:
                $msg = 'Fehlende Absenderadresse.';
                break;
            case ApiException::ORIGINATOR_DATA_X_INCOMPLETE:
                $msg = 'Absenderadresse unvollständig.';
                break;
            case ApiException::RECIPIENT_DATA_X_MISSING:
                $msg = 'Fehlende Empfängeradresse.';
                break;
            case ApiException::RECIPIENT_DATA_X_INCOMPLETE:
                $msg = 'Unvollständige Empfängeradresse.';
                break;
            case ApiException::DROPSHIP_WITHOUT_PRODUCTS:
                $msg = 'Dropship-Auftrag ohne Bestellpositionen.';
                break;

            case ApiException::ORDER_POSITION_ERROR_1:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_2:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_3:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_4:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_5:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_6:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_7:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_8:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_9:
                // intentional fall through
            case ApiException::ORDER_POSITION_ERROR_10:
                // intentional fall through
            case ApiException::PRODUCT_DEFINITION_ERROR_1:
                // intentional fall through
            case ApiException::PRODUCT_DEFINITION_ERROR_2:
                // intentional fall through
            case ApiException::PRODUCT_DEFINITION_ERROR_3:
                $msg = 'Fehler in einer Bestellposition.';
                break;

            case ApiException::MISSING_ORDERNUMBER:
                $msg = 'Fehlende Bestellnummer.';
                break;
            case ApiException::DUPLICATE_ORDERNUMBER:
                $msg = 'Die übermittelte Bestellnummer wurde bereits verwendet.';
                break;
            case ApiException::ADDRESS_DATA_ERROR:
                $msg = 'Fehler in den Adressdaten.';
                break;
            case ApiException::PRODUCT_NUMBER_MISSING:
                $msg = 'Fehlende Produktnummer.';
                break;
            case ApiException::PRODUCT_NOT_AVAILABLE_1:
                // intentional fall through
            case ApiException::PRODUCT_NOT_AVAILABLE_2:
                $msg = 'Produkt ist nicht verfügbar.';
                break;
            case ApiException::PRODUCT_UNKNOWN_1:
                // intentional fall through
            case ApiException::PRODUCT_UNKNOWN_2:
                // intentional fall through
            case ApiException::PRODUCT_UNKNOWN_3:
                // intentional fall through
            case ApiException::PRODUCT_UNKNOWN_4:
                $msg = 'Unbekanntes Produkt.';
                break;
            case ApiException::NOT_ONE_ORDER:
                $msg = 'Mehr als ein Auftrag in der Bestellung.';
                break;
            case ApiException::HEAD_DATA_MISSING:
                $msg = 'Fehlende Kopfdaten.';
                break;
            case ApiException::DELIVERY_ADDRESS_INVALID_1:
                // intentional fall through
            case ApiException::DELIVERY_ADDRESS_INVALID_2:
                $msg = 'Ungültige Lieferadresse.';
                break;

            case ApiException::ORDER_NUMBER_INVALID_1:
                // intentional fall through
            case ApiException::ORDER_NUMBER_INVALID_2:
                $msg = 'Ungültige Bestellnummer.';
                break;

            case ApiException::TOO_MANY_API_ACCESSES:
                $msg = 'API-Aufruf wegen zu vieler API-Zugriffe abgelehnt.';
                break;
            case ApiException::MAINTENANCE:
                $msg = 'Zugriff nicht möglich, weil sich die InnoCigs-API im Wartungsmodus befindet';
                break;
        }
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
            mxc_dsi_date = :date,
            mxc_dsi_status = :status,
            mxc_dsi_message = :message,
            mxc_dsi_id = :dropshipId,
            mxc_dsi_order_id = :orderId,
            mxc_dsi_infos = :info
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
