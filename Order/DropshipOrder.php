<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use SimpleXMLElement;

// This class is used by OrderProcessor to create the XML Request for ApiClient->sendOrder

class DropshipOrder implements AugmentedObject
{
    use ClassConfigAwareTrait;

    private $positions;
    private $orderNumber;
    private $originator;
    private $recipient;

    private $cost;

    public function create(string $orderNumber, array $originator, array $shippingAddress)
    {
        $this->orderNumber = $orderNumber;
        $this->originator = $originator;
        $this->recipient = null;
        $this->positions = [];
        $this->cost = $this->classConfig['cost']['dropship']['base'];

        $this->recipient = [
            'COMPANY'        => $shippingAddress['company'],
            'COMPANY2'       => $shippingAddress['department'],
            'FIRSTNAME'      => ucFirst($shippingAddress['firstname']),
            'LASTNAME'       => ucFirst($shippingAddress['lastname']),
            'STREET_ADDRESS' => ucFirst($shippingAddress['street']),
            'CITY'           => ucFirst($shippingAddress['city']),
            'POSTCODE'       => $shippingAddress['zipcode'],
            'COUNTRY_CODE'   => $shippingAddress['iso'],
        ];
    }

    public function addPosition(string $productnumber, int $quantity, float $purchasePrice)
    {
        $this->positions[] = [
            'PRODUCT' => [
                'PRODUCTS_MODEL' => $productnumber,
                'QUANTITY'       => $quantity,
            ],
        ];
        $this->cost += $this->classConfig['cost']['dropship']['line'];
        $this->cost += $quantity * ($this->classConfig['cost']['dropship']['pick'] + $purchasePrice);
    }

    public function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
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

    public function getCost()
    {
        return $this->cost;
    }
}