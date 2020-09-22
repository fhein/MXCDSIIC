<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\Exception\DropshipOrderException;
use SimpleXMLElement;
use Shopware_Components_Config;
use MxcDropshipInnocigs\Api\ApiClient;

class DropshipOrder implements AugmentedObject
{
    // Auftrag noch nicht überträgen
    const ORDER_STATUS_OPEN         = 0;
    // Auftrag erfolgreich übertragen, warten auf Tracking-Daten
    const ORDER_STATUS_TRANSFERRED  = 1;
    // Trackingdaten empfangen, Dropshipauftrag abgeschlossen
    const ORDER_STATUS_CLOSED       = 2;
    // Auftrag konnte nicht übertragen werden, Auftrag wird ignoriert, manuelles Eingreifen erforderlich
    const ORDER_STATUS_ERROR        = 99;

    // All documented InnoCigs API errors
    const LOGIN_FAILED                  = 10000;
    const INVALID_XML                   = 10001;
    const NO_DROPSHIP_DATA              = 10002;
    const DROPSHIP_DATA_INCOMPLETE      = 10003;
    const UNKNOWN_API_FUNCTION          = 10004;
    const MISSING_ORIGINATOR            = 10005;
    const INVALID_ORIGINATOR            = 10006;
    const PAYMENT_LOCKED                = 10007;
    const PAYMENT_LIMIT_EXCEEDED        = 10008;
    const XML_ALREADY_UPLOADED          = 20000;
    const DROPSHIP_DATA_X_INCOMPLETE    = 20001;
    const ORIGINATOR_DATA_X_MISSING     = 20002;
    const ORIGINATOR_DATA_X_INCOMPLETE  = 20003;
    const RECIPIENT_DATA_X_MISSING      = 20004;
    const RECIPIENT_DATA_X_INCOMPLETE   = 20005;
    const DROPSHIP_WITHOUT_PRODUCTS     = 20006;
    const PRODUCT_DEFINITION_ERROR_1    = 20007;
    const PRODUCT_DEFINITION_ERROR_2    = 20008;
    const PRODUCT_DEFINITION_ERROR_3    = 20009;
    const MISSING_ORDERNUMBER           = 20010;
    const DUPLICATE_ORDERNUMBER         = 20011;
    const ADDRESS_DATA_ERROR            = 20012;
    const PRODUCT_NUMBER_MISSING        = 30000;
    const PRODUCT_NOT_AVAILABLE_1       = 30001;
    const PRODUCT_NOT_AVAILABLE_2       = 30002;
    const PRODUCT_UNKNOWN_1             = 30003;
    const PRODUCT_UNKNOWN_2             = 30004;
    const PRODUCT_UNKNOWN_3             = 30005;
    const PRODUCT_UNKNOWN_4             = 30006;
    const NOT_ONE_ORDER                 = 40001;
    const HEAD_DATA_MISSING             = 40002;
    const DELIVERY_ADDRESS_INVALID_1    = 40004;
    const DELIVERY_ADDRESS_INVALID_2    = 40005;
    const ORDER_NUMBER_INVALID_1        = 40006;
    const ORDER_NUMBER_INVALID_2        = 40007;
    const ORDER_POSITION_ERROR_1        = 40010;
    const ORDER_POSITION_ERROR_2        = 40011;
    const ORDER_POSITION_ERROR_3        = 40012;
    const ORDER_POSITION_ERROR_4        = 40013;
    const ORDER_POSITION_ERROR_5        = 40014;
    const ORDER_POSITION_ERROR_6        = 40015;
    const ORDER_POSITION_ERROR_7        = 40016;
    const ORDER_POSITION_ERROR_8        = 40017;
    const ORDER_POSITION_ERROR_9        = 40018;
    const ORDER_POSITION_ERROR_10       = 40019;
    const TOO_MANY_API_ACCESSES         = 50000;
    const MAINTENANCE                   = 50001;

    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    private $positions = [];
    private $orderNumber;
    private $originator;
    private $recipient;
    private $recipientErrors;

    /** @var Shopware_Components_Config */
    private $config;

    private $client;

    public function __construct(Shopware_Components_Config $config, ApiClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    public function create(array $shippingAddress)
    {
        $this->recipient = null;
        $this->originator = null;
        $this->positions = [];

        $this->setOriginator(
            $this->config->get('mxcbc_dsi_ic_company', 'vapee.de') ?? '',
            $this->config->get('mxcbc_dsi_ic_department', 'maxence operations gmbh') ?? '',
            $this->config->get('mxcbc_dsi_ic_first_name') ?? '',
            $this->config->get('mxcbc_dsi_ic_last_name') ?? '',
            $this->config->get('mxcbc_dsi_ic_street', 'Am Weißen Stein 1') ?? '',
            $this->config->get('mxcbc_dsi_ic_zip', '41541') ?? '',
            $this->config->get('mxcbc_dsi_ic_city', 'Dormagen') ?? '',
            $this->config->get('mxcbc_dsi_ic_country_code', 'DE') ?? '',
            $this->config->get('mxcbc_dsi_ic_mail') ?? '',
            $this->config->get('mxcbc_dsi_ic_phone') ?? ''
        );

        $this->setRecipientFromArray($shippingAddress);
    }

    public function addPosition(string $productnumber, int $quantity)
    {
        $this->positions[] = [
            'PRODUCT' => [
                'PRODUCTS_MODEL' => $productnumber,
                'QUANTITY'       => $quantity,
            ],
        ];
    }


    public function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /** Use fields as from s_order_shippingaddress
     *  Supply additional entry 'iso' for the country iso code
     * @param array $shippingaddress
     */
    public function setRecipientFromArray(array $shippingaddress)
    {
        $this->recipient = [
            'COMPANY'        => ucFirst($shippingaddress['company']),
            'COMPANY2'       => ucFirst($shippingaddress['department']),
            'FIRSTNAME'      => ucFirst($shippingaddress['firstname']),
            'LASTNAME'       => ucFirst($shippingaddress['lastname']),
            'STREET_ADDRESS' => ucFirst($shippingaddress['street']),
            'CITY'           => ucFirst($shippingaddress['city']),
            'POSTCODE'       => $shippingaddress['zipcode'],
            'COUNTRY_CODE'   => $shippingaddress['iso'],
        ];

        $errors = $this->validateRecipient();
        if (! empty($errors)) {
            throw DropshipOrderException::fromInvalidRecipientAddress($errors);
        }
    }

    public function setRecipient(
        string $company,
        string $company2,
        string $firstName,
        string $lastName,
        string $streetAddress,
        string $zipCode,
        string $city,
        string $countryCode
    ) {
        $this->recipient = [
            'COMPANY'        => ucFirst($company),
            'COMPANY2'       => ucFirst($company2),
            'FIRSTNAME'      => ucFirst($firstName),
            'LASTNAME'       => ucFirst($lastName),
            'STREET_ADDRESS' => ucFirst($streetAddress),
            'CITY'           => ucFirst($city),
            'POSTCODE'       => $zipCode,
            'COUNTRY_CODE'   => $countryCode,
        ];

        $errors = $this->validateRecipient();

        if (! empty($errors)) {
            throw DropshipOrderException::fromInvalidRecipientAddress($errors);
        }
    }

    protected function validateRecipient()
    {
        $errors = [];
        if (strlen($this->recipient['COMPANY']) > 30) {
            $errors[] = DropshipOrderException::RECIPIENT_COMPANY_TOO_LONG;
        }

        if (strlen($this->recipient['COMPANY2']) > 30) {
            $errors[] = DropshipOrderException::RECIPIENT_COMPANY2_TOO_LONG;
        }
        
        $firstName = $this->recipient['FIRSTNAME'];
        if (strlen($firstName) < 2) {
            $errors[] = DropshipOrderException::RECIPIENT_FIRST_NAME_TOO_SHORT;
        }

        $lastName = $this->recipient['LASTNAME'];
        if (strlen($lastName) < 2) {
            $errors[] = DropshipOrderException::RECIPIENT_LAST_NAME_TOO_SHORT;
        }

        if (strlen($firstName.$lastName) > 34) {
            $errors[] = DropshipOrderException::RECIPIENT_NAME_TOO_LONG;
        }

        if (strlen($this->recipient['STREET_ADDRESS']) > 35) {
            $errors[] = DropshipOrderException::RECIPIENT_STREET_ADDRESS_TOO_LONG;
        }

        if (strlen($this->recipient['STREET_ADDRESS']) < 5) {
            $errors[] = DropshipOrderException::RECIPIENT_STREET_ADDRESS_TOO_SHORT;
        }

        if (strlen($this->recipient['POSTCODE']) < 4) {
            $errors[] = DropshipOrderException::RECIPIENT_ZIP_TOO_SHORT;
        }

        if (strlen($this->recipient['CITY']) < 3) {
            $errors[] = DropshipOrderException::RECIPIENT_CITY_TOO_SHORT;
        }
        return $errors;
    }

    public function setOriginator(
        string $company,
        string $company2,
        string $firstName,
        string $lastName,
        string $streetAddress,
        string $zipCode,
        string $city,
        string $countryCode,
        string $email = '',
        string $phoneNumber = ''
    ) {
        $this->originator = [
            'COMPANY'        => $company,
            'COMPANY2'       => $company2,
            'FIRSTNAME'      => $firstName,
            'LASTNAME'       => $lastName,
            'STREET_ADDRESS' => $streetAddress,
            'POSTCODE'       => $zipCode,
            'CITY'           => $city,
            'COUNTRY_CODE'   => $countryCode,
            'EMAIL'          => $email,
            'TELEPHONE'      => $phoneNumber,
        ];
    }

    public function getXmlRequest(bool $pretty = false)
    {
        $data = [
            'ORDERS_NUMBER' => $this->orderNumber,
            'SHIPPER'       => $this->originator,
            'RECEIVER'      => $this->recipient,
        ];

        $dropship = [
            'DATA'     => $data,
            'PRODUCTS' => $this->positions,
        ];

        $request['DROPSHIPPING']['DROPSHIP'] = $dropship;

        $xml = new SimpleXMLElement('<INNOCIGS_API_REQUEST/>');
        $this->toXml($request, $xml);

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = $pretty;
        $result = $dom->saveXML();
        $result = preg_replace('~<\?xml version="\d.\d"\?>\n~', '', $result);

        return $result;
    }

    // Recursively convert array structure to xml
    protected function toXml($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $this->toXml($value, $xml);
                } else {
                    $this->toXml($value, $xml->addChild($key));
                }
            } else {
                $xml->addChild("$key", "$value");
            }
        }
    }

    protected function setPositionError(&$position, $code, $msg)
    {
        $position['CODE'] = $code;
        $position['MESSAGE'] = $msg;
    }

    protected function validateOrderPositions()
    {
        $result = true;
        foreach ($this->positions as &$position) {
            try {
                $instock = $this->client->getStockInfo($position['PRODUCTS_MODEL']);
                if ($instock == 0) {
                    $this->setPositionError($position, DropshipOrderException::PRODUCT_OUT_OF_STOCK, 'Product out of stock.');
                    $position['errorcode'];
                } elseif ($instock < $position['QUANTITY']) {
                    $this->setPositionError($position, DropshipOrderException::POSITION_EXCEEDS_STOCK, 'Position exceeds stock.');
                }
            } catch (DropshipException $e) {
                $code = $e->getCode();
                if ($code === DropshipException::MODULE_API_SUPPLIER_ERRORS) {
                    $errors = $e->getInnocigsErrors();
                    // if there is more than one error, we can not handle that
                    if (count($errors) > 1) {
                        throw $e;
                    }
                    $message = $errors[0]['MESSAGE'];

                    // handle unknown product errors
                    if (
                        $code >= self::PRODUCT_UNKNOWN_1
                        && $code <= self::PRODUCT_UNKNOWN_4
                    ) {
                        $this->setPositionError($position, DropshipOrderException::PRODUCT_UNKNOWN, $message);
                        $result = false;
                    } elseif ( // handle product not available errors
                        $code == self::PRODUCT_NOT_AVAILABLE_1
                        || $code == self::PRODUCT_NOT_AVAILABLE_2
                    ) {
                        $this->setPositionError($position, DropshipOrderException::PRODUCT_NOT_AVAILABLE, $message);
                        $result = false;
                    } elseif ($code == self::PRODUCT_NUMBER_MISSING) {
                        $this->setPositionError($position, DropshipOrderException::PRODUCT_NUMBER_MISSING, $message);
                        $result = false;
                    }
                }
            }
        }
        return $result;
    }

    public function send()
    {
        $positionsValid = $this->validateOrderPositions();
        if (! $positionsValid) {
            throw DropshipOrderException::fromInvalidOrderPositions($this->positions);
        }
        try {
            $data = $this->client->sendOrder($this->getXmlRequest());
            $errors = @$data['ERRORS'] ?? [];
            if ($data['status'] == 'NOK') {
                throw DropshipOrderException::fromDropshipNOK($errors, $data);
            }
            if (! empty($errors)) {
                throw DropshipOrderException::fromInnocigsErrors($errors['ERRORS']);
            }
        } catch (DropshipException $e) {
            if ($e->getCode() === DropshipException::MODULE_API_SUPPLIER_ERRORS) {
                throw DropshipOrderException::fromInnocigsErrors($e->getSupplierErrors());
            }
            throw DropshipOrderException::fromApiException($e);
        }
    }

    public function test(bool $pretty = true)
    {
        $this->setOriginator(
            'vapee.de',
            'maxence operations gmbh',
            'Frank',
            'Hein',
            'Am Weißen Stein 1',
            '41541',
            'Dormagen',
            'DE',
            'info@vapee.de',
            '+49-2133-259925'
        );

        $this->setOrderNumber('9999');

        return $this->getXmlRequest($pretty);
    }

    public function getRecipientErrors() {
        return $this->recipientErrors;
    }
}