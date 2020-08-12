<?php

namespace MxcDropshipInnocigs\Services;

use DateTime;
use DOMDocument;
use DOMElement;
use Exception;
use MxcCommons\Plugin\Service\LoggerInterface;
use MxcDropshipIntegrator\Exception\ApiException;
use MxcCommons\Http\Client;
use MxcCommons\Http\Exception\RuntimeException as ZendClientException;
use MxcCommons\Http\Response;

class ApiClient
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
     * @var Client $client
     */
    protected $client = null;

    /** @var LoggerInterface $log */
    protected $log;

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
        return $this->modelsToArray($this->send($cmd)->getBody(), true);
    }

    // --- api functions

    /**
     * @param bool $flat
     * @param bool $includeDescriptions
     * @return array
     */
    public function getItemList(bool $flat, bool $includeDescriptions)
    {
        $cmd = $this->authUrl . '&command=products';
        if ($includeDescriptions) {
            $cmd .= '&type=extended';
        }
        return $this->modelsToArray($this->send($cmd)->getBody(), $flat);
    }

    public function sendOrder($xmlRequest) {
        $cmd = $this->authUrl . '&command=dropship&xml=' . urlencode($xmlRequest);
        return $this->modelsToArray($this->send($cmd)->getBody(), false);
    }

    /**
     * @param DateTime $date
     * @return array
     * @throws Exception
     */
    public function getTrackingData($date = null)
    {
        if (!$date instanceof DateTime) {
            $date = (new DateTime())->format('Y-m-d');
        }
        $cmd = $this->authUrl . '&command=tracking&day=' . $date;
        return $this->xmlToArray($this->send($cmd)->getBody());
    }

    public function getStockInfo(string $model)
    {
        $cmd = $this->authUrl . '&command=quantity&model=' . urlencode($model);
        $data = $this->xmlToArray($this->send($cmd)->getBody());
        return $data['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    public function getAllStockInfo()
    {
        $cmd = $this->authUrl . '&command=quantity_all';
        $data = $this->xmlToArray($this->send($cmd)->getBody());
        $stockInfo = [];
        foreach($data['QUANTITIES']['PRODUCT'] as $record) {
            $stockInfo[$record['PRODUCTS_MODEL']] = $record['QUANTITY'];
        }
        return $stockInfo;
    }

    //--- api implementation

    public function modelsToArray(string $xml, bool $flat = false): ?array
    {
        //$xml = preg_replace('~\& ~', '&amp; ', $xml);
        $this->checkXmlResult($xml);
        $this->logXML($xml);
        $dom = new DOMDocument();
        $result = $dom->loadXML($xml);
        if ($result === false) {
            throw new ApiException('InnoCigs API: <br/>Invalid XML data received.');
        }
        $models = $dom->getElementsByTagName('PRODUCT');
        /** @var DOMElement $model */
        $import = [];
        foreach ($models as $model) {
            $item = [];
            $item['category']               = $this->getNodeValue($model, 'CATEGORY');
            $item['model']                  = $this->getNodeValue($model, 'MODEL');
            $item['master']                 = $this->getNodeValue($model, 'MASTER');
            $item['ean']                    = $this->getNodeValue($model, 'EAN');
            $item['name']                   = $this->getNodeValue($model, 'NAME');
            $item['productName']            = $this->getNodeValue($model, 'PARENT_NAME');
            $item['purchasePrice']          = $this->getNodeValue($model, 'PRODUCTS_PRICE');
            $item['recommendedRetailPrice'] = $this->getNodeValue($model, 'PRODUCTS_PRICE_RECOMMENDED');
            $item['manufacturer']           = $this->getNodeValue($model, 'MANUFACTURER');
            $item['manual']                 = $this->getNodeValue($model, 'PRODUCTS_MANUAL');
            $item['description']            = $this->getNodeValue($model, 'DESCRIPTION');
            $item['image']                  = $this->getNodeValue($model, 'PRODUCTS_IMAGE');

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

            if ($flat) {
                $import[$item['model']] = $item;
            } else {
                $import[$item['master']][$item['model']] = $item;
            }
        }
        return $import;
    }

    /**
     * @param string $xml
     * @return array
     */
    public function xmlToArray(string $xml): array
    {
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

    protected function checkXmlResult(string $xml)
    {
        if (strpos($xml, '<ERRORS>') !== false) {
            $this->xmlToArray($xml);
        }
    }


    protected function logXML($xml)
    {
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadXML($xml);
        $dom->formatOutput = true;
        $pretty = $dom->saveXML();

        $reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (file_exists($reportDir) && !is_dir($reportDir)) {
            unlink($reportDir);
        }
        if (!is_dir($reportDir)) {
            mkdir($reportDir);
        }

        $fn = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data.xml';
        file_put_contents($fn, $pretty);
        $fn = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs/api_data_raw.xml';
        file_put_contents($fn, $xml);
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
     * @return Response
     */
    protected function send($cmd)
    {
        $client = $this->getClient();
        $client->setUri($cmd);
        try {
            $response = $client->send();
            if (!$response->isSuccess()) {
                throw new ApiException('InnoCigs API: <br/>' . 'HTTP status: ' . $response->getStatusCode());
            }
            return $response;
        } catch (ZendClientException $e) {
            // no response or response empty
            throw new ApiException($e->getMessage());
        }
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client(
                "",
                [
                    'maxredirects' => 0,
                    'timeout'      => 30,
                    'useragent'    => 'maxence',
                ]
            );
        }
        return $this->client;
    }
}
