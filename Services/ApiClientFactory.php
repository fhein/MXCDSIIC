<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Xml\HttpReader;
use MxcDropshipInnocigs\Xml\XmlReader;

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