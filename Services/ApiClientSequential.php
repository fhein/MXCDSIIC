<?php

namespace MxcDropshipInnocigs\Services;

use DateTime;
use DOMElement;
use Exception;
use MxcCommons\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Exception\ApiException;
use XMLReader;

//use DOMDocument;
//use MxcCommons\Http\Client;
//use MxcCommons\Http\Exception\RuntimeException as ZendClientException;
//use MxcCommons\Http\Response;

class ApiClientSequential
{
    /**
     * @var string $apiEntry
     */
    protected $apiEntry;

    /**
     * @var string $authUrl
     */
    protected $authUrl;

    /**
     * @var XMLReader $reader;
     */
    protected $reader = null;


    protected $import = [];
    /** @var LoggerInterface $log */
    protected $log;

    protected $apiDataLogPath = "";
    protected $apiDataRawLogPath = "";

    public function __construct(Credentials $credentials, LoggerInterface $log)
    {
        $this->log = $log;
        $this->apiEntry = 'https://www.innocigs.com/xmlapi/api.php';
        $this->authUrl = $this->apiEntry . '?cid=' . $credentials->getUser() . '&auth=' . $credentials->getPassword();
    }

    /**
     * @param string $model
     * @return array
     */
    public function getItemInfo($model)
    {
        $cmd = $this->authUrl . "&command=product&model=" . $model;

        $this->import = [];
        $this->readXML($cmd);
        return $this->import;
    }

    public function addModelToArray($model){

        $test = $model instanceof DOMElement;
        if ($test === false) {
            throw new ApiException('InnoCigs API: <br/>Invalid XML data received.');
        }
        /** @var DOMElement $model */
        $item = [];
        $item['category'] = $this->getNodeValue($model, 'CATEGORY');
        $item['model'] = $this->getNodeValue($model, 'MODEL');
        $item['master'] = $this->getNodeValue($model, 'MASTER');
        $item['ean'] = $this->getNodeValue($model, 'EAN');
        $item['name'] = $this->getNodeValue($model, 'NAME');
        $item['productName'] = $this->getNodeValue($model, 'PARENT_NAME');
        $item['purchasePrice'] = $this->getNodeValue($model, 'PRODUCTS_PRICE');
        $item['recommendedRetailPrice'] = $this->getNodeValue($model, 'PRODUCTS_PRICE_RECOMMENDED');
        $item['manufacturer'] = $this->getNodeValue($model, 'MANUFACTURER');
        $item['manual'] = $this->getNodeValue($model, 'PRODUCTS_MANUAL');
        $item['description'] = $this->getNodeValue($model, 'DESCRIPTION');
        $item['image'] = $this->getNodeValue($model, 'PRODUCTS_IMAGE');

        $attributes = $model->getElementsByTagName('PRODUCTS_ATTRIBUTES')->item(0)->childNodes;
        /** @var DOMElement $attribute */
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof DOMElement) {
                continue;
            }
            $item['options'][$attribute->tagName] = $attribute->nodeValue;
        }

        $item['images'] = [];
        /** @var DOMElement $addlImages */
        $addlImages = $model->getElementsByTagName('PRODUCTS_IMAGE_ADDITIONAL')->item(0);
        if ($addlImages) {
            $images = $addlImages->getElementsByTagName('IMAGE');
            foreach ($images as $image) {
                $item['images'][] = $image->nodeValue;
            }
        }

        /** @var DOMElement $vpe */
        $vpe = $model->getElementsByTagName('VPE')->item(0);
        if ($vpe) {
            $item['content'] = $this->getNodeValue($vpe, 'CONTENT');
            $item['unit'] = $this->getNodeValue($vpe, 'UNIT');
        }

        $this->import[$item['master']][$item['model']] = $item;
    }

    protected function checkXmlResult(string $xml)
    {
        if (strpos($xml, '<ERRORS>') !== false) {
            $this->xmlToArray($xml);
        }
    }

    /**
     * @param string $xml
     * @return array
     */
    public function xmlToArray(string $xml): array
    {
        $this->logXML($xml);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml, 'SimpleXmlElement', LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            $errors = libxml_get_errors();
            $this->logXmlErrors($errors);
            $dump = Shopware()->DocPath() . 'var/log/invalid-innocigs-api-response-' . date('Y-m-d-H-i-s') . '.txt';
            file_put_contents($dump, $xml);
            $this->log->err('Invalid InnoCigs API response dumped to ' . $dump);
            throw new ApiException('InnoCigs API: <br/>Invalid XML data received. See log file for details.');
        }
        $json = json_encode($xml);
        if ($json === false) {
            throw new ApiException('InnoCigs API: <br/>Failed to encode XML data to JSON.');
        }
        $result = json_decode($json, true);
        if ($result === false) {
            throw new ApiException('InnoCigs API: <br/>Failed to decode JSON data to XML.');
        }
        $this->checkArrayResult($result);
        return $result;
    }

    protected function createXMLLogs(){
        $reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (file_exists($reportDir) && !is_dir($reportDir)) {
            unlink($reportDir);
        }
        if (!is_dir($reportDir)) {
            mkdir($reportDir);
        }

        $this->apiDataLogPath = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data.xml';
        $this->apiDataRawLogPath = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data_raw.xml';

        file_put_contents($this->apiDataLogPath, "");
        file_put_contents($this->apiDataRawLogPath, "");
    }

    protected function logXML($xmlLine){

        $pretty = tidy_repair_string($xmlLine, ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);

        file_put_contents($this->apiDataLogPath, $pretty, FILE_APPEND | LOCK_EX);
        file_put_contents($this->apiDataRawLogPath, $xmlLine, FILE_APPEND | LOCK_EX);
    }

    protected function logXMLErrors(array $errors)
    {
        foreach ($errors as $error) {
            $msg = str_replace(PHP_EOL, '', $error->message);
            $this->log->err(sprintf(
                'XML Error: %s, line: %s, column: %s',
                $msg,
                $error->line,
                $error->column));
        }
    }

    protected function checkArrayResult(array $response)
    {
        $error = $response['ERRORS']['ERROR'] ?? null;
        if ($error) {
            throw new ApiException('InnoCigs API: <br/>' . $error['MESSAGE']);
        }
    }

    protected function getNodeValue(DOMElement $model, string $tagName)
    {
        $element = $model->getElementsByTagName($tagName)->item(0);
        if ($element) {
            return $element->nodeValue;
        }
        return null;
    }

    /**
     * @param string $cmd
     */
    protected function readXML($cmd)
    {
        $this->createXMLLogs();

        $reader = new XMLReader;
        $reader->open($cmd);

        $reader->read();
        $xml = $reader->readOuterXml();
        $this->logXML($xml);
        $this->checkXmlResult($xml);
        // move to the first <product /> node
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        while ($reader->read() && $reader->name !== 'PRODUCT');

        // now that we're at the right depth, hop to the next <product/> until the end of the tree
        while ($reader->name === 'PRODUCT')
        {
            //$this->logXMLLine($reader->readOuterXml(), $reader->expand());
            $this->addModelToArray($reader->expand());
            $reader->next('PRODUCT');
        }
    }


    /**
     * @param string $fileName
     * @return array
     */
    public function getItemListfromFile(string $fileName)
    {
        $this->import = [];
        $this->readXML($fileName);
        return $this->import;
    }

    /**
     * @param bool $includeDescriptions
     * @return array
     */
    public function getItemList(bool $includeDescriptions)
    {
        $cmd = $this->authUrl . '&command=products';
        if ($includeDescriptions) {
            $cmd .= '&type=extended';
        }
        $this->import = [];
        $this->readXML($cmd);
        return $this->import;
    }

    /**
     * @param $date
     * @return array
     */
    public function getTrackingData($date = null)
    {
        if (!$date instanceof DateTime) {
            $date = (new DateTime())->format('Y-m-d');
        }
        $cmd = $this->authUrl . '&command=tracking&day=' . $date;
        $this->import = [];
        $this->readXML($cmd);
        return $this->import;
    }

    public function getStockInfo(string $model)
    {
        $cmd = $this->authUrl . '&command=quantity&model=' . urlencode($model);
        $this->import = [];
        $this->readXML($cmd);
        return $this->import['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    public function getAllStockInfo()
    {
        $cmd = $this->authUrl . '&command=quantity_all';
        $this->import = [];
        $this->readXML($cmd);

        $data = $this->import;
        $stockInfo = [];
        foreach($data['QUANTITIES']['PRODUCT'] as $record) {
            $stockInfo[$record['PRODUCTS_MODEL']] = $record['QUANTITY'];
        }
        return $stockInfo;
    }

    protected function dumpXML($xml)
    {
        $fn = Shopware()->DocPath() . '/var/log/mxc_dropship_innocigs/raw_data_' . date('Y-m-d-H-i-s') . '.xml';
        file_put_contents($fn, $xml);
    }
}
