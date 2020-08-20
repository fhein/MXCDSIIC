<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
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

        $services = MxcDropshipInnocigs::getServices();
        $log = $services->get('logger');
        $result = true;

        try {
            UpdatePrices::run();
            ApplyPriceRules::run();
        } catch (Throwable $e) {
            $this->log->except($e, false, false);
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Update prices cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }
}
