<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Database\SchemaManager;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Xml\HttpReader;
use MxcDropshipInnocigs\Xml\XmlReader;
use MxcDropshipInnocigs\Xml\ResponseToArray;

class ImportClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $apiClient = $container->get(ApiClient::class);
        $schemaManager = $container->get(SchemaManager::class);
        $xmlReader = $container->get(XmlReader::class);
        $httpReader = $container->get(HttpReader::class);
        return new ImportClient($schemaManager, $apiClient, $xmlReader, $httpReader);
    }
}