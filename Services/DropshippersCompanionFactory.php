<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class DropshippersCompanionFactory implements FactoryInterface
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
        return $this->augment($container, new DropshippersCompanion($apiClient));
    }
}