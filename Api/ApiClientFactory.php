<?php

namespace MxcDropshipInnocigs\Api;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\Xml\HttpReader;
use MxcDropshipInnocigs\Api\Xml\XmlReader;

class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $credentials = $container->get(Credentials::class);
        $readerHttp = $container->get(HttpReader::class);
        $readerXml = $container->get(XmlReader::class);
        return new ApiClient($credentials, $readerXml, $readerHttp);
    }
}