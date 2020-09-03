<?php

namespace MxcDropshipInnocigs\Api\Xml;

use MxcDropshipInnocigs\Exception\ApiException;
use XMLReader as PhpXmlReader;
use DOMElement;

class XmlReader
{
    private $rta;
    /** @var PhpXmlReader  */
    private $reader;

    public function __construct(ResponseToArray $rta)
    {
        $this->rta = $rta;
        $this->reader = new PhpXmlReader();
    }

    // can be used for both urls and files
    public function readModelsFromUri($cmd, bool $flat = true)
    {
        $this->reader->open($cmd);
        return $this->readModels($this->reader, $flat);
    }

    public function readModelsFromXML(string $xml, bool $flat = true)
    {
        $reader = $this->reader->XML($xml);
        return $this->readModels($this->reader, $flat);
    }

    protected function readModels(PhpXmlReader $reader, bool $flat): array
    {
        $import = [];
        $this->rta->createXMLLogs();
        $reader->read();
        $xml = $reader->readOuterXml();
        $this->rta->logXMLSequential($xml);

        // if this is an error reponse from InnoCigs xmlToArray will throw
        if (strpos($xml, '<ERRORS>') !== false) {
            $this->rta->xmlToArray($xml);
        }

        // move to the first <product /> node
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        while ($reader->read() && $reader->name !== 'PRODUCT') ;

        // now that we're at the right depth, hop to the next <product/> until the end of the tree
        while ($reader->name === 'PRODUCT') {
            $model = $reader->expand();
            if (! $model instanceof DOMElement) {
                throw ApiException::fromInvalidXML();
            }
            $item = $this->rta->modelToArray($model);

            if ($flat) {
                $import[$item['model']] = $item;
            } else {
                $import[$item['master']][$item['model']] = $item;
            }

            $reader->next('PRODUCT');
        }
        return $import;
    }
}