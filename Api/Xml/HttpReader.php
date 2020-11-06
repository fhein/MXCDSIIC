<?php

namespace MxcDropshipInnocigs\Api\Xml;

use MxcCommons\Http\Client;
use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\Exception\ApiException;
use DOMDocument;
use DOMElement;
use Throwable;

class HttpReader
{
    protected $rta;
    protected $client;

    public function __construct(ResponseToArray $rta)
    {
        $this->rta = $rta;
    }

    public function readModels(string $cmd, bool $flat = true): array
    {
        $xml = $this->send($cmd)->getBody();
        return $this->readModelsFromXml($xml, $flat);
    }

    public function readModelsFromXml(string $xml, bool $flat = true): array
    {
        // if this is an error reponse from InnoCigs xmlToArray will throw
        if (strpos($xml, '<ERRORS>') !== false) {
            $this->rta->xmlToArray($xml);
        }

        $this->rta->createXMLLogs();
        $this->rta->logXML($xml);
        $dom = new DOMDocument();
        $result = $dom->loadXML($xml);
        if ($result === false) {
            throw ApiException::fromXmlError(DropshipException::XML_INVALID);
        }
        $models = $dom->getElementsByTagName('PRODUCT');

        // this is a workaround because the InnoCigs API does not return an error if the queried productnumber
        // does not exist
        if (count($models) == 0) {
            throw ApiException::fromEmptyProductInfo();
        }
        /** @var DOMElement $model */
        $import = [];
        foreach ($models as $model) {
            $item = $this->rta->modelToArray($model);

            if ($flat) {
                $import[$item['model']] = $item;
            } else {
                $import[$item['master']][$item['model']] = $item;
            }
        }
        return $import;
    }

    public function readXml2(string $xml) {
        return $this->rta->xmlToArray($xml);
    }

    public function readXml(string $cmd): array
    {
        $xml = $this->send($cmd)->getBody();
        return $this->rta->xmlToArray($xml);
    }

    protected function send($cmd)
    {
        $client = $this->getClient();
        $client->setUri($cmd);
        try {
            $response = $client->send(); // may throw
            if (! $response->isSuccess()) {
                throw ApiException::fromHttpStatus($response->getStatusCode());
            }
        } catch (Throwable $e) {
            throw ApiException::fromClientException($e);
        }
        return $response;
    }

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