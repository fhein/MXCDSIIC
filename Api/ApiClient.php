<?php

namespace MxcDropshipInnocigs\Api;

use DateTime;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Api\Xml\HttpReader;
use MxcDropshipInnocigs\Api\Xml\XmlReader;

class ApiClient implements AugmentedObject
{
    use LoggerAwareTrait;

    protected $apiEntry;
    protected $authUrl;

    protected $xmlReader;
    protected $httpReader;

    // cache for product list without descriptions
    protected $productsCache;
    // cache for product list with descriptions
    protected $productsCacheExtended;
    // cache for stock list
    protected $stockInfoCache;

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

    // note: product list gets cached to prevent multiple downloads per request
    public function getProducts(bool $flat, bool $includeDescriptions, bool $sequential = true)
    {
        $cmd = $this->authUrl . '&command=products';
        if ($this->productsCacheExtended !== null) return $this->productsCacheExtended;
        if ($includeDescriptions) {
            $cmd .= '&type=extended';
        } else {
            if ($this->productsCache !== null) return $this->productsCache;
        }
        $result =  $sequential
            ? $this->xmlReader->readModelsFromUri($cmd, $flat)
            : $this->httpReader->readModels($cmd, $flat);

        if ($includeDescriptions) {
            $this->productsCacheExtended = $result;
        } else {
            $this->productsCache = $result;
        }
        return $result;
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

    public function getStockInfo(string $model = null)
    {
        if ($model === null) return $this->getAllStockInfo();

        $cmd = $this->authUrl . '&command=quantity&model=' . urlencode($model);
        $data = $this->httpReader->readXml($cmd);
        return $data['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    // note: result gets cached to prevent multiple downloads per request
    protected function getAllStockInfo()
    {
        if ($this->stockInfoCache !== null) return $this->stockInfoCache;

        $cmd = $this->authUrl . '&command=quantity_all';
        $data = $this->httpReader->readXml($cmd);

        $stockInfo = [];
        foreach ($data['QUANTITIES']['PRODUCT'] as $record) {
            $stockInfo[$record['PRODUCTS_MODEL']] = $record['QUANTITY'];
        }
        $this->stockInfoCache = $stockInfo;
        return $stockInfo;
    }

    public function getXmlReader() {
        return $this->xmlReader;
    }
}
