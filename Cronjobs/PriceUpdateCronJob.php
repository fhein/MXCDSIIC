<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipIntegrator\Jobs\UpdateInnocigsPrices;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Throwable;

class PriceUpdateCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcInnocigsPriceUpdate' => 'onUpdatePrices',
        ];
    }

    public function onUpdatePrices(/** @noinspection PhpUnusedParameterInspection */$job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropshipIntegrator::getServices();
        $log = $services->get('logger');
        $result = true;

        try {
            UpdateInnocigsPrices::run();
            ApplyPriceRules::run();
        } catch (Throwable $e) {
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Update prices cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->error($msg);

        return $result;
    }
}
