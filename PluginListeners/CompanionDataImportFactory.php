<?php

namespace MxcDropshipInnocigs\PluginListeners;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Mail\MailManager;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;

class CompanionDataImportFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
        $registry = $dropshipManager->getService('InnoCigs', 'ArticleRegistry');
        $companion = $container->get(DropshippersCompanion::class);
        $mailManager = $container->get(MailManager::class);
        return new CompanionDataImport($companion, $registry);
    }
}