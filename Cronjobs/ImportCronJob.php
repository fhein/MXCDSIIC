<?php

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipIntegrator\Dropship\SupplierRegistry;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

class ImportCronJob implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcInnocigsImport' => 'onImportCronJob'
        ];
    }

    public function onImportCronJob(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        /** @var SupplierRegistry $registry */
        $registry = MxcDropshipIntegrator::getServices()->get(SupplierRegistry::class);
        $importClient = $registry->getService(SupplierRegistry::SUPPLIER_INNOCIGS, 'ImportClient');
        $importClient->import(true);

        return true;
    }
}