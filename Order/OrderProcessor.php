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
use Throwable;

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
    public function sendOrder(array $order)
    {
        $orderId = $order['orderID'];
        // get all order details to be ordered from InnoCigs
        $details = $this->getOrderDetails($orderId);
        // We return true only if we actually successfully send an order to InnoCigs.
        // If the order does not contain any InnoCigs products, we have nothing to do.
        if (empty($details)) return false;

        $this->shippingAddress = $this->getShippingAddress($orderId);
        try {
            $this->validateShippingAddress($this->shippingAddress);
            $this->dropshipOrder->create($order['ordernumber'], $this->shippingAddress);
            $this->addOrderDetails($order['ordernumber'], $details);
            $result = $this->dropshipOrder->send();
            $context = $this->classConfig['error_context']['ORDER_SUCCESS'];
            $this->dropshipLog($order, $context);
            $this->sendMail($order, $context);

            // if we get here, order was sent successfully
            $this->setOrderStatus(
                $order['orderID'],
                DropshipManager::ORDER_STATUS_SENT,
                $result['message'],
                $result['dropshipId'],
                $result['dropshipOrderId']
            );
        } catch (DropshipException $e) {
            $this->handleOrderException($e, $order);
            return false;
        }
        // catch all exceptions which we might not have covered yet
        catch (Throwable $e) {
            $this->handleUnknownException($e, $order);
            return false;
        }
        return true;
    }

    public function handleOrderException(DropshipException $e, array $order)
    {
        $code = $e->getCode();
        $context = $this->classConfig['error_context'][$code];
        $context['orderNumber'] = $order['ordernumber'];
        switch ($code) {
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
            case DropshipException::MODULE_API_XML_ERROR:
                $context['errors'] = $e->getXmlErrors();
                break;
            case DropshipException::MODULE_API_FAILURE:
                $context['errors'] = $e->getApiErrors();
                break;
            default:
                $context = $this->classConfig['error_context']['UNKNOWN_ERROR'];
                $context['errors'] = [['code' => $e->getCode(), 'message' => $e->getMessage()]];
        }
        $this->dropshipLog($order, $context);
        $this->sendMail($order, $context);
        $this->setOrderStatus($order['orderID'], $context['status'], $context['message']);
    }

    public function handleUnknownException(Throwable $e, array $order)
    {
        $context = $this->classConfig['error_context']['UNKNOWN_ERROR'];
        $context['errors'] = [['code' => $e->getCode(), 'message' => $e->getMessage()]];
        $this->dropshipLog($order, $context);
        $this->sendMail($order, $context);
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
        $valid = true;
        foreach ($details as $detail) {
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
                'instock'           => null,
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
                    $error['message'] = sprintf('Produkt %s nicht auf Lager.', $productNumber);
                    $valid = false;
                    $errors[] = $error;
                } elseif ($instock < $quantity) {
//                } elseif ($instock < $quantity || $pos == 1) {
                    $error['code'] = DropshipException::POSITION_EXCEEDS_STOCK;
                    $error['message'] = sprintf('Lagerbestand für Produkt %s (%u) kleiner als %u.',
                        $productNumber,
                        $instock,
                        $quantity
                    );
                    $valid = false;
                    $errors[] = $error;
                } else {
                    $error['message'] = sprintf('Produkt %s: OK', $productNumber);
                    $error['code'] = DropshipManager::NO_ERROR;
                    $errors[] = $error;
                    $this->dropshipOrder->addPosition($productNumber, $quantity);
                }
                // ***!***
            } catch (DropshipException $e) {
                $code = $e->getCode();
                if ($code === DropshipException::MODULE_API_SUPPLIER_ERRORS) {
                    $error = array_merge($error, $e->getSupplierErrors()[0]);
                    $valid = false;
                    $errors[$detail] = $error;
                } else {
                    // if we do not have supplier errors we have a general API error
                    // so a more general error handling is required
                    throw $e;
                }
            }
            $pos++;
        }
        foreach ($errors as $status) {
            $this->setOrderDetailStatus($status);
        }
        if (! $valid) {
            throw ApiException::fromInvalidOrderPositions($errors);
        }
    }

    public function sendMail($order, $context)
    {
        $orderNumber = $order['ordernumber'];
        $context['mailBody'] = str_replace('{$orderNumber}', $orderNumber, $context['mailBody']);
        $dsMail = Shopware()->TemplateMail()->createMail($context['mailTemplate'], $context);
        $dsMail->addTo('support@vapee.de');
        $dsMail->clearFrom();
        $dsMail->setFrom('info@vapee.de', 'vapee.de Dropship');
        if (isset($context['mailSubject'])) {
            $subject = str_replace('{$orderNumber}', $orderNumber, $context['mailSubject']);
            $dsMail->clearSubject();
            $dsMail->setSubject($subject);
        }
        $dsMail->send();
    }

    protected function dropshipLog(array $order, array $response)
    {
        $this->dropshipLogger->log(
            $response['severity'],
            $this->supplier,
            $response['message'],
            $order['orderID'],
            $order['ordernumber']
        );
        $errors = $response['errors'];
        if (empty($errors)) return;
        foreach ($errors as $error) {
            $this->dropshipLogger->log(
                $response['severity'],
                $this->supplier,
                $error['message'],
                $order['orderID'],
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

    private function setOrderDetailStatus(array $status)
    {
        $this->db->executeUpdate('
            UPDATE s_order_details_attributes oda
            SET
                oda.mxcbc_dsi_message = :message,
                oda.mxcbc_dsi_status  = :code,
                oda.mxcbc_dsi_instock = :instock
            WHERE 
                oda.detailID = :detailId
        ', [
            'detailId' => $status['detailId'],
            'code' => $status['code'],
            'message' => $status['message'],
            'instock' => $status['instock']
        ]);
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
}
