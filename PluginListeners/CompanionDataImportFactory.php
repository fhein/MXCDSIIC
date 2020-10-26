<?php

namespace MxcDropshipInnocigs\PluginListeners;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;

class CompanionDataImportFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $registry = $container->get(ArticleRegistry::class);
        $companion = $container->get(DropshippersCompanion::class);
        return new CompanionDataImport($companion, $registry);
    }
}