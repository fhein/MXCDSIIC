<?php

namespace MxcDropshipInnocigs\Services;

use DateTime;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Xml\HttpReader;
use MxcDropshipInnocigs\Xml\XmlReader;

class ApiClient implements AugmentedObject
{
    use LoggerAwareTrait;

    protected $apiEntry;
    protected $authUrl;

    protected $xmlReader;
    protected $httpReader;

    public function __construct(Credentials $credentials, XmlReader $xmlReader, HttpReader $httpReader)
    {
        $this->apiEntry = 'https://www.innocigs.com/xmlapi/api.php';
        $this->authUrl = $this->apiEntry . '?cid=' . $credentials->getUser() . '&auth=' . $credentials->getPassword();
        $this->xmlReader = $xmlReader;
        $this->httpReader = $httpReader;
    }

    public function getProduct($model, $flat = true, $sequential = true)
    {
        $cmd = $this->authUrl . "&command=product&model=" . $model;
        return $sequential
            ? $this->xmlReader->readModelsFromUri($cmd, $flat)
            : $this->httpReader->readModels($cmd, $flat);
    }

    public function getProducts(bool $flat, bool $includeDescriptions, bool $sequential = true)
    {
        $cmd = $this->authUrl . '&command=products';
        if ($includeDescriptions) {
            $cmd .= '&type=extended';
        }
        return $sequential
            ? $this->xmlReader->readModelsFromUri($cmd, $flat)
            : $this->httpReader->readModels($cmd, $flat);
    }

    // note: we currently support one dropship order per request (the InnoCigs API supports a list of dropship)
    public function sendOrder($xmlRequest)
    {
        $cmd = $this->authUrl . '&command=dropship&xml=' . urlencode($xmlRequest);
        $data = $this->httpReader->readXml($cmd);
        return $data['DROPSHIPPING']['DROPSHIP'];
    }

    public function getTrackingData($date = null)
    {
        if (! $date instanceof DateTime) {
            $date = (new DateTime())->format('Y-m-d');
        }

        $cmd = $this->authUrl . '&command=tracking&day=' . $date;
        return $this->httpReader->readXml($cmd);
    }

    public function getStockInfo(string $model)
    {
        $cmd = $this->authUrl . '&command=quantity&model=' . urlencode($model);
        $data = $this->httpReader->readXml($cmd);
        return $data['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    public function getAllStockInfo()
    {
        $cmd = $this->authUrl . '&command=quantity_all';
        $data = $this->httpReader->readXml($cmd);

        $stockInfo = [];
        foreach ($data['QUANTITIES']['PRODUCT'] as $record) {
            $stockInfo[$record['PRODUCTS_MODEL']] = $record['QUANTITY'];
        }
        return $stockInfo;
    }

    public function getXmlReader() {
        return $this->xmlReader;
    }
}
