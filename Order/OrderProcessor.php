<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Exception\DropshipException;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;

class OrderProcessor implements AugmentedObject
{
    use DatabaseAwareTrait;
    use LoggerAwareTrait;

    /** @var DropshipOrder */
    protected $dropshipOrder;

    /** @var ApiClient */
    protected $client;

    /** @var DropshipStatus */
    protected $dropshipStatus;

    /** @var string */
    protected $supplier;

    /** @var array */
    protected $details;

    public function __construct(DropshipOrder $dropshipOrder, ApiClient $client, DropshipStatus $dropshipStatus)
    {
        $this->dropshipOrder = $dropshipOrder;
        $this->client = $client;
        $this->dropshipStatus = $dropshipStatus;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    public function sendOrder(array $order, $dropshipManager)
    {
        /** @var DropshipManager $dropshipManager */
        $shippingAddress = [];
        $orderId = $order['orderID'];
        try {
            // if this order was already sent to InnoCigs (but possibly not to other suppliers)
            // we do nothing and return the current status
            if ($order['mxcbc_dsi_ic_status'] != DropshipManager::ORDER_STATUS_OPEN) {
                return [
                    'status' => $order['mxcbc_dsi_ic_status'],
                    'message' => $order['mxcbc_dsi_ic_message']
                ];
            }
            // get all order details to be ordered from InnoCigs
            $details = $dropshipManager->getSupplierOrderDetails($this->supplier, $orderId);
            // We return true only if we actually successfully send an order to InnoCigs.
            // If the order does not contain any InnoCigs products, we have nothing to do.
            if (empty($details)) return null;
            $this->details = $details;

            $shippingAddress = $this->getShippingAddress($orderId);
            $originator = $dropshipManager->getOriginator();
            $this->validateShippingAddress($shippingAddress);

            $this->dropshipOrder->create($order['ordernumber'], $originator, $shippingAddress);
            $this->addOrderDetails($order['ordernumber']);
            $request = $this->dropshipOrder->getXmlRequest(true);
            $this->log->debug('Order Request:');
            $this->log->debug(var_export($request, true));
            $result = $this->client->sendOrder($request);
            return $this->dropshipStatus->orderSuccessfullySent($order, $result);
        } catch (Throwable $e) {
            [$status, $message] = $dropshipManager->handleDropshipException(
                $this->supplier,
                'sendOrder',
                $e,
                true,
                $order,
                $shippingAddress
            );
            return $this->dropshipStatus->setOrderStatus($orderId, $status, $message);
        }
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

    protected function addOrderDetails(string $orderNumber)
    {
        $errors = [];

        // collect the errors for all details and throw if any
        $pos = 0;
        $valid = true;
        foreach ($this->details as $detail) {
            $productInfo = $this->getProductInfo($detail['articleDetailID']);
            $productNumber = $productInfo['productNumber'];
            $quantity = $detail['quantity'];
            $detailId = $detail['detailID'];

            // error preset
            $error = [
                'detailId'      => $detailId,
                'orderNumber'   => $orderNumber,
                'productNumber' => $productNumber,
                'purchasePrice' => $productInfo['purchasePrice'],
                'quantity'      => $quantity,
                'instock'       => null,
            ];

            // check if detail is a known product and in stock
            try {
                // throws on API and InnCigs errors
                $instock = $this->client->getStockInfo($productNumber);
                $error['instock'] = $instock;
                // ***!*** DEBUG pos
                if ($instock == 0) {
//                if ($instock == 0 || $pos == 0) {
                    $error['code'] = DropshipException::PRODUCT_OUT_OF_STOCK;
                    $error['message'] = 'Nicht auf Lager.';
                    $error['severity'] = DropshipLogger::ERR;
                    $valid = false;
                    $errors[] = $error;
                } elseif ($instock < $quantity) {
//                } elseif ($instock < $quantity || $pos == 1) {
                    $error['code'] = DropshipException::POSITION_EXCEEDS_STOCK;
                    $error['message'] = 'Lagerbestand zu gering.';
                    $error['severity'] = DropshipLogger::ERR;
                    $valid = false;
                    $errors[] = $error;
                } else {
                    $error['message'] = 'Position ok.';
                    $error['code'] = DropshipManager::NO_ERROR;
                    $error['severity'] = DropshipLogger::NOTICE;
                    $errors[] = $error;
                    $this->dropshipOrder->addPosition($productNumber, $quantity);
                }
                // ***!***
            } catch (DropshipException $e) {
                $code = $e->getCode();
                if ($code === DropshipException::MODULE_API_SUPPLIER_ERRORS) {
                    $error = array_merge($error, $e->getSupplierErrors()[0]);
                    $errors[] = $error;
                    $valid = false;
                } else {
                    // if we do not have supplier errors we have a general API error
                    // which requires a more general error handling
                    throw $e;
                }
            }
            $pos++;
        }

        $this->setOrderDetailPositionStatus($errors);

        if (! $valid) {
            throw ApiException::fromInvalidOrderPositions($errors);
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

    private function getProductInfo(int $articleDetailId)
    {
        return $this->db->fetchAll('
            SELECT 
                aa.mxcbc_dsi_ic_productnumber as productNumber,
                aa.mxcbc_dsi_ic_purchaseprice as purchasePrice 
            FROM 
                s_articles_attributes aa
            WHERE 
                aa.articledetailsID = :articleDetailId
        ', ['articleDetailId' => $articleDetailId])[0];
    }

    private function setOrderDetailPositionStatus(array $errors)
    {
        foreach ($errors as $status) {
            $this->db->executeUpdate('
                UPDATE s_order_details_attributes oda
                SET
                    oda.mxcbc_dsi_message       = :message,
                    oda.mxcbc_dsi_status        = :code,
                    oda.mxcbc_dsi_instock       = :instock,
                    oda.mxcbc_dsi_purchaseprice = :purchasePrice
                WHERE 
                    oda.detailID = :detailId
            ', [
                'detailId'      => $status['detailId'],
                'code'          => $status['code'],
                'message'       => $status['message'],
                'purchasePrice' => $status['purchasePrice'],
                'instock'       => $status['instock'],
            ]);
        }
    }
}
