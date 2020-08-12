<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcDropshipIntegrator\MxcDropshipIntegrator;        // @todo: Gegenseitige Abhängigkeit der Module
use Shopware\Models\Country\Country;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Shipping;
use SimpleXMLElement;

class DropshipOrder implements ClassConfigAwareInterface
{
    use ClassConfigAwareTrait;

    private $positions = [];
    private $orderNumber;
    private $originator;
    private $recipient;

    public function create(Order $order)
    {
        /** @var Shipping $shippingAddress */
        $shipping = $order->getShipping();
        /** @var Country $country */
        $country = $shipping->getCountry();
        $countryCode = $country->getIso();

        $this->orderNumber = $order->getNumber();

        $this->setRecipient(
            $shipping->getCompany(),
            $shipping->getDepartment(),
            $shipping->getFirstName(),
            $shipping->getLastName(),
            $shipping->getStreet(),
            $shipping->getZipCode(),
            $shipping->getCity(),
            $countryCode
        );

        $config = $this->classConfig['originator'];

        $this->setOriginator(
            $config['company'],
            $config['company2'],
            $config['firstname'],
            $config['lastname'],
            $config['street'],
            $config['zipcode'],
            $config['city'],
            $config['countrycode'],
            $config['email'],
            $config['phone']
        );

        $this->addPosition( 'product1', 2);
        $this->addPosition( 'product2', 4);
    }

    public function addPosition(string $productnumber, int $quantity) {
        $this->positions[] = [
            'PRODUCT' => [
                'PRODUCTS_MODEL' => $productnumber,
                'QUANTITY' => $quantity
            ]
        ];
    }

    public function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /** Use fields as from s_order_shippingaddress
     * @param array $recipient
     */
    public function setRecipientFromArray(array $recipient)
    {
        $this->recipient = [
            'COMPANY' => $recipient['company'],
            'COMPANY2' => $recipient['department'],
            'FIRSTNAME' => $recipient['firstname'],
            'LASTNAME' => $recipient['lastame'],
            'STREET_ADDRESS' => $recipient['street'],
            'POSTCODE' => $recipient['zipcode'],
            'CITY' => $recipient['city'],
            'COUNTRY_CODE' => $recipient['countryCode'],
        ];
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
    )
    {
        $this->recipient = [
            'COMPANY' => $company,
            'COMPANY2' => $company2,
            'FIRSTNAME' => $firstName,
            'LASTNAME' => $lastName,
            'STREET_ADDRESS' => $streetAddress,
            'POSTCODE' => $zipCode,
            'CITY' => $city,
            'COUNTRY_CODE' => $countryCode,
        ];
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
    )
    {
        $this->originator = [
            'COMPANY' => $company,
            'COMPANY2' => $company2,
            'FIRSTNAME' => $firstName,
            'LASTNAME' => $lastName,
            'STREET_ADDRESS' => $streetAddress,
            'POSTCODE' => $zipCode,
            'CITY' => $city,
            'COUNTRY_CODE' => $countryCode,
            'EMAIL' => $email,
            'TELEPHONE' => $phoneNumber,
        ];
    }

    public function getXmlRequest(bool $pretty = false)
    {
        $data = [
            'ORDERS_NUMBER' => $this->orderNumber,
            'SHIPPER' => $this->originator,
            'RECEIVER' => $this->recipient,
        ];

        $dropship = [
            'DATA' => $data,
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
    protected function toXml( $data, &$xml ) {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if (is_numeric($key)) {
                    $this->toXml($value, $xml);
                } else {
                    $this->toXml($value, $xml->addChild($key));
                }
            } else {
                $xml->addChild("$key","$value");
            }
        }
    }

    public function test(bool $pretty = true)
    {
        $this->setOriginator(
            'vapee.de',
            '',
            'Frank',
            'Hein',
            'Am Weißen Stein 1',
            '41541',
            'Dormagen',
            'DE',
            'info@vapee.de',
            ''
        );

        $this->setRecipient(
            '',
            '',
            'Ramona',
            'Ortelbach',
            'Auf der Straße 2',
            '12345',
            'Wunschhausen',
            'DE'
        );

        $this->addPosition('Produkt', 2);
        $this->addPosition('Anderes Produkt', 4);

        $this->setOrderNumber('9999');

        return $this->getXmlRequest($pretty);
    }
}