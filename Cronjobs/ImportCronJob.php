<?php

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Services\ImportClient;

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
        $client = MxcDropshipInnocigs::getServices()->get(ImportClient::class);
        $client->import(true);

        return true;
    }
}