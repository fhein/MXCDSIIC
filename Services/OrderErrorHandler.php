<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Log\LoggerAwareInterface;
use MxcCommons\Log\LoggerAwareTrait;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\Exception\DropshipOrderException;

class OrderErrorHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function handleOrderException(DropshipOrderException $e, array $order)
    {
        switch ($e->getCode()) {
            case DropshipOrderException::INNOCIGS_ERROR:
                $errors = $e->getInnocigsErrors();
                break;
            case DropshipOrderException::DROPSHIP_NOK:
                $errors = $e->getInnocigsErrors();
                $info = $e->getDropshipInfo();
                break;
            case DropshipOrderException::POSITIONS_ERROR:
                $errors = $e->getPositions();
                break;
            case DropshipOrderException::RECIPIENT_ADDRESS_ERROR:
                $errors = $e->getAddressErrors();
                break;
            case DropshipOrderException::API_EXCEPTION:
                /** @var ApiException $apiException */
                $apiException = $e->getPrevious();
                $errors = $this->handleApiException($apiException);
                break;
            default:
                // we cover all cases, so this is just sanitary
                throw($e);
                break;
        }

//        if (isset($dropshipInfo['ERRORS']['ERROR'])) {
//            $errorCodeListForEmail .= 'Bestellnummer: ' . $fullOrder['ordernumber'] . PHP_EOL . 'ErrorCode: ' . $dropshipInfo['ERRORS']['ERROR']['CODE'] . PHP_EOL . $dropshipInfo['ERRORS']['ERROR']['MESSAGE'] . PHP_EOL . '------' . PHP_EOL;
//            $processedOrderNumbers .= 'NOK: ' . $fullOrder['ordernumber'] . PHP_EOL;
//        } else {
//            if ($dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'] == 'NOK') {
//                $errorCode = $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ERRORS']['ERROR']['CODE'];
//                $errorMessage = $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ERRORS']['ERROR']['MESSAGE'];
//                $orderInfoMessage = $errorCode . '|' . $errorMessage;
//                $processedOrderNumbers .= 'NOK: ' . $fullOrder['ordernumber'] . PHP_EOL;
//            } else {
//                $processedOrderNumbers .= 'OK: ' . $fullOrder['ordernumber'] . PHP_EOL;
//            }
//
//            $orderInfo = [
//                'status'     => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['STATUS'],
//                'message'    => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['MESSAGE'],
//                'dropshipId' => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['DROPSHIP_ID'],
//                'orderId'    => $dropshipInfo['DROPSHIPPING']['DROPSHIP']['ORDERS_ID'],
//                'info'       => $orderInfoMessage,
//                'date'       => date('d.m.Y H:i:s')
//            ];
//            if (! $errorCode) {
//                $this->setDropshipStatus($fullOrder['ordernumber'], 100);
//                $this->setOrderStatusForArticle($fullOrder['ordernumber'], self::STATE_TRANSFER_ARTICLE);
//            } else {
//                $this->setDropshipStatus($fullOrder['ordernumber'], -100);
//            }
//
//        }
    }

    protected function handleApiException(ApiException $e)
    {
        $errors = [];
        switch($e->getCode()) {
            case ApiException::NO_RESPONSE:
                break;
            case ApiException::JSON_DECODE:
                break;
            case ApiException::JSON_ENCODE:
                break;
            case ApiException::INVALID_XML_DATA:
                break;
            case ApiException::HTTP_STATUS:
                break;
        }
        return $errors;
    }

}