<?php

namespace MxcDropshipInnocigs\Api\Xml;

use DOMDocument;
use DOMElement;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Exception\ApiException;

class ResponseToArray implements AugmentedObject
{
    use LoggerAwareTrait;

    protected $logEnabled = false;
    protected $logPath;
    protected $logPathRow;

    public function modelToArray(DOMElement $model): array
    {
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
            if (! $attribute instanceof DOMElement) {
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
        return $item;
    }

    public function xmlToArray($xml) {
        $this->logXML($xml);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml, 'SimpleXmlElement', LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            $errors = libxml_get_errors();
            $this->logXmlErrors($errors);
            $dump = Shopware()->DocPath() . 'var/log/invalid-innocigs-api-response-' . date('Y-m-d-H-i-s') . '.txt';
            file_put_contents($dump, $xml);
            $this->log->err('Invalid InnoCigs API response dumped to ' . $dump);
            throw ApiException::fromInvalidXML();
        }
        $json = json_encode($xml);
        if ($json === false) throw ApiException::fromJsonEncode();
        $result = json_decode($json, true);
        if ($result === false) throw ApiException::fromJsonDecode();
        $errors = $response['ERRORS'] ?? null;
        if ($errors) throw ApiException::fromInnocigsErrors($errors);
        return $result;
    }

    protected function getNodeValue(DOMElement $model, string $tagName)
    {
        $element = $model->getElementsByTagName($tagName)->item(0);
        if ($element) {
            return $element->nodeValue;
        }
        return null;
    }

    public function createXMLLogs(){
        $reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (file_exists($reportDir) && !is_dir($reportDir)) {
            unlink($reportDir);
        }
        if (!is_dir($reportDir)) {
            mkdir($reportDir);
        }

        $this->logPath = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data.xml';
        $this->logPathRow = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data_raw.xml';

        file_put_contents($this->logPath, "");
        file_put_contents($this->logPathRow, "");
    }

    protected function logXMLErrors(array $errors)
    {
        foreach ($errors as $error) {
            $msg = str_replace(PHP_EOL, '', $error->message);
            $this->log->crit(sprintf(
                'XML Error: %s, line: %s, column: %s',
                $msg,
                $error->line,
                $error->column));
        }
    }

    public function logXMLSequential($xmlLine){

        $pretty = tidy_repair_string($xmlLine, ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);

        file_put_contents($this->logPath, $pretty, FILE_APPEND | LOCK_EX);
        file_put_contents($this->logPathRow, $xmlLine, FILE_APPEND | LOCK_EX);
    }

    public function logXML($xml)
    {
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadXML($xml);
        $dom->formatOutput = true;
        $pretty = $dom->saveXML();

        $reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (file_exists($reportDir) && ! is_dir($reportDir)) {
            unlink($reportDir);
        }
        if (! is_dir($reportDir)) {
            mkdir($reportDir);
        }

        $fn = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data.xml';
        file_put_contents($fn, $pretty);
        $fn = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data_raw.xml';
        file_put_contents($fn, $xml);
    }

}