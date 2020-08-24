<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\Exception\DropshipOrderException;
use SimpleXMLElement;
use Shopware_Components_Config;

class DropshipOrder implements ClassConfigAwareInterface, ModelManagerAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    const RECIPIENT_COMPANY_TOO_LONG = 100;
    const RECIPIENT_COMPANY2_TOO_LONG = 101;
    const RECIPIENT_FIRST_NAME_TOO_SHORT = 102;
    const RECIPIENT_LAST_NAME_TOO_SHORT = 103;
    const RECIPIENT_NAME_TOO_LONG = 104;
    const RECIPIENT_STREET_ADDRESS_TOO_SHORT = 105;
    const RECIPIENT_STREET_ADDRESS_TOO_LONG = 106;
    const RECIPIENT_ZIP_TOO_SHORT = 107;
    const RECIPIENT_CITY_TOO_SHORT = 108;

    protected $errorMessages = [
        self::RECIPIENT_COMPANY_TOO_LONG         => 'Der Firmenname darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_COMPANY2_TOO_LONG        => 'Der Firmenname 2 darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_FIRST_NAME_TOO_SHORT     => 'Der Vorname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_LAST_NAME_TOO_SHORT      => 'Der Nachname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_NAME_TOO_LONG            => 'Vorname und Nachname dürfen zusammen nicht mehr als 34 Zeichen enthalten.',
        self::RECIPIENT_STREET_ADDRESS_TOO_SHORT => 'Die Straße mit Hausnummer muss mindestens aus fünf Zeichen bestehen.',
        self::RECIPIENT_STREET_ADDRESS_TOO_LONG  => 'Die Straße mit Hausnummer darf höchstens aus 35 Zeichen bestehen.',
        self::RECIPIENT_ZIP_TOO_SHORT            => 'Die Postleitzahl muss mindestens aus vier Zeichen bestehen.',
        self::RECIPIENT_CITY_TOO_SHORT           => 'Die Stadt muss mindestens aus drei Zeichen bestehen.',
    ];

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

    public function create(string $orderNumber, array $shippingAddress)
    {
        $this->recipient = null;
        $this->originator = null;
        $this->positions = [];

        $config = $this->classConfig['originator'];

        $this->setOriginator(
            $this->config->get('mxc_dsi_ic_company'),
            $this->config->get('mxc_dsi_ic_department'),
            $this->config->get('mxc_dsi_ic_first_name'),
            $this->config->get('mxc_dsi_ic_last_name'),
            $this->config->get('mxc_dsi_ic_street'),
            $this->config->get('mxc_dsi_ic_zip'),
            $this->config->get('mxc_dsi_ic_city'),
            $this->config->get('mxc_dsi_ic_country_code'),
            $this->config->get('mxc_dsi_ic_mail'),
            $this->config->get('mxc_dsi_ic_phone')
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
            'LASTNAME'       => ucFirst($shippingaddress['lastame']),
            'STREET_ADDRESS' => ucFirst($shippingaddress['street']),
            'CITY'           => ucFirst($shippingaddress['city']),
            'POSTCODE'       => $shippingaddress['zipcode'],
            'COUNTRY_CODE'   => $shippingaddress['iso'],
        ];

        $errors = $this->validateRecipient();
        if (! empty($errors)) {
            throw DropshipOrderException::fromInvalidRecipientAdress($errors);
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
            throw DropshipOrderException::fromInvalidRecipientAdress($errors);
        }
    }

    protected function getAddressError($code)
    {
        return [
            'code' => $code,
            'msg' => $this->errorMessages[$code],
        ];
    }

    protected function validateRecipient()
    {
        $errors = [];
        if (strlen($this->recipient['COMPANY']) > 30) {
            $errors[] = $this->getAddressError(self::RECIPIENT_COMPANY_TOO_LONG);
        }

        if (strlen($this->recipient['COMPANY2']) > 30) {
            $errors[] = $this->getAddressError(self::RECIPIENT_COMPANY2_TOO_LONG);
        }

        $firstName = $this->recipient['FIRSTNAME'];
        if (strlen($firstName) < 2) {
            $errors[] = $this->getAddressError(self::RECIPIENT_FIRST_NAME_TOO_SHORT);
        }

        $lastName = $this->recipient['LASTNAME'];
        if (strlen($lastName) < 2) {
            $errors[] = $this->getAddressError(self::RECIPIENT_LAST_NAME_TOO_SHORT);
        }

        if (strlen($firstName.$lastName) > 34) {
            $errors[] = $this->getAddressError(self::RECIPIENT_NAME_TOO_LONG);
        }

        if (strlen($this->recipient['STREET_ADDRESS']) > 35) {
            $errors[] = $this->getAddressError(self::RECIPIENT_STREET_ADDRESS_TOO_LONG);
        }

        if (strlen($this->recipient['STREET_ADDRESS']) < 5) {
            $errors[] = $this->getAddressError(self::RECIPIENT_STREET_ADDRESS_TOO_SHORT);
        }

        if (strlen($this->recipient['POSTCODE']) < 4) {
            $errors[] = $this->getAddressError(self::RECIPIENT_ZIP_TOO_SHORT);
        }

        if (strlen($this->recipient['CITY']) < 3) {
            $errors[] = $this->getAddressError(self::RECIPIENT_CITY_TOO_SHORT);
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
        $position['errorcode'] = $code;
        $position['errormessage'] = $msg;
    }

    protected function validateOrderPositions()
    {
        $stockInfo = [];
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
            } catch (ApiException $e) {
                $code = $e->getCode();
                if ($code === ApiException::INNOCIGS_ERRORS) {
                    $errors = $e->getInnocigsErrors();
                    // if there is more than one error, we can not handle that
                    if (count($errors) > 1) {
                        throw $e;
                    }
                    $message = $errors[0]['MESSAGE'];

                    // handle unknown product errors
                    if (
                        $code >= ApiException::PRODUCT_UNKNOWN_1
                        && $code <= ApiException::PRODUCT_UNKNOWN_4
                    ) {
                        $this->setPositionError($position, DropshipOrderException::PRODUCT_UNKNOWN, $message);
                        $result = false;
                    } elseif ( // handle product not available errors
                        $code == ApiException::PRODUCT_NOT_AVAILABLE_1
                        || $code == ApiException::PRODUCT_NOT_AVAILABLE_2
                    ) {
                        $this->setPositionError($position, DropshipOrderException::PRODUCT_NOT_AVAILABLE, $message);
                        $result = false;
                    } elseif ($code == ApiException::PRODUCT_NUMBER_MISSING) {
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
        } catch (ApiException $e) {
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