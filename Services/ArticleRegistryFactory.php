<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class ArticleRegistryFactory extends AugmentedObjectFactory
{
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
        $db = Shopware()->Db();
        $client = $container->get(ApiClient::class);
        return $this->augment($container, new ArticleRegistry($client, $db));
    }

}