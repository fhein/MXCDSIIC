<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use SimpleXMLElement;

// This class is used by OrderProcessor to create the XML Request for ApiClient->sendOrder

class DropshipOrder
{
    private $positions;
    private $orderNumber;
    private $originator;
    private $recipient;

    public function create(string $orderNumber, array $originator, array $shippingAddress)
    {
        $this->orderNumber = $orderNumber;
        $this->originator = $originator;
        $this->recipient = null;
        $this->positions = [];

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
}