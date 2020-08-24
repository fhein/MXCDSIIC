<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class DropshipOrderFactory extends AugmentedObjectFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = Shopware()->Config();
        $apiClient = $container->get(ApiClient::class);
        return $this->augment($container, new DropshipOrder($config, $apiClient));
    }
}