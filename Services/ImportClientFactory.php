<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Database\SchemaManager;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ImportClientFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $apiClient = $container->get(ApiClient::class);
        $apiClientSeq = $container->get(ApiClientSequential::class);
        $schemaManager = $container->get(SchemaManager::class);
        $client = new ImportClient($schemaManager, $apiClient, $apiClientSeq);
        return $this->augment($container, $client);
    }
}