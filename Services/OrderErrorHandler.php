<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Log\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\Exception\DropshipOrderException;
use MxcDropshipIntegrator\Dropship\DropshipLogger;

class OrderErrorHandler implements AugmentedObject
{
    use LoggerAwareTrait;

    const ORDER_HALT = 1;
    const ORDER_RETRY = 2;

    protected $errorResponses = [
        ApiException::NO_RESPONSE => [
            'message'           => 'InnoCigs server does not respond. Order not transmitted. Will attempt again.',
            'severity'          => DropshipLogger::ERR,
            'action'            => self::ORDER_RETRY,
        ],
        ApiException::JSON_ENCODE => [
            'message'           => 'Failed to decode response from InnoCigs. Order status unknown. Please contact InnoCigs. Order halted.',
            'severity'          => DropshipLogger::ERR,
            'action'            => self::ORDER_RETRY,
        ],
        ApiException::INVALID_XML_DATA => [
            'message'           => 'InnoCigs response is malformed and invalid XML. Order status unknown. Please contact InnoCigs. Order halted.',
            'severity'          => DropshipLogger::ERR,
            'action'            => self::ORDER_RETRY,
        ],
        ApiException::HTTP_STATUS => [
            'message'           => 'API call failed. InnoCigs HTTP status: %s. Order not transmitted. Will attempt again.',
            'severity'          => DropshipLogger::ERR,
            'action'            => self::ORDER_RETRY,
        ]
    ];

    /** @var DropshipOrder */
    protected $dropshipOrder;

    /** @var DropshipLogger */
    protected $dropshipLog;

    /** @var array  */
    protected $order = [];

    public function __construct(DropshipOrder $dropshipOrder, DropshipLogger $dropshipLog)
    {
        $this->dropshipLog = $dropshipLog;
        // note: because DropshipOrder is a shared service it will hold the last state
        // when handleOrderException() is called
        $this->dropshipOrder = $dropshipOrder;
    }

    public function handleOrderException(DropshipOrderException $e, array $order)
    {
        $this->order = $order;
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
                throw $e;
        }
    }

    protected function handleApiException(ApiException $e)
    {
        $code = $e->getCode();
        $response = $this->errorResponses[$code];

        // if the code is HTTP status we have to sprintf the HTTP status to the log message
        if ($code === ApiException::HTTP_STATUS) {
            $response['message'] = sprintf($response['message'], $e->getHttpStatus());
        }

        $this->log($response);
    }

    // note: this is a draft for logging without position data;
    protected function log(array $response)
    {
        $this->dropshipLog->log(
            $response['severity'],
            'InnoCigs',
            $response['message'],
            $this->order['ordernumber']
        );

    }

}

//   Dropshipper's companion junk
//
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
