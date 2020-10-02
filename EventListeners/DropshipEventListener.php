<?php

namespace MxcDropshipInnocigs\EventListeners;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\EventManager\SharedEventManagerInterface;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropshipInnocigs\Jobs\UpdateStock;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Order\OrderProcessor;
use MxcDropshipInnocigs\Order\TrackingDataProcessor;
use Shopware\Models\Order\Order;
use Throwable;

class DropshipEventListener implements AugmentedObject
{
    use ServicesAwareTrait;

    public function attach(SharedEventManagerInterface $sharedEvents) {
        $sharedEvents->attach(DropshipManager::class, 'updatePrices', [$this, 'onUpdatePrices']);
        $sharedEvents->attach(DropshipManager::class, 'updateStock', [$this, 'onUpdateStock']);
        $sharedEvents->attach(DropshipManager::class, 'sendOrder', [$this, 'onSendOrder']);
        $sharedEvents->attach(DropshipManager::class, 'updateTrackingData', [$this, 'onUpdateTrackingData']);
    }

    public function onUpdatePrices(EventInterface $e)
    {
        // may throw
        $this->services->get(UpdatePrices::class)->run();
        return [
            'code' => DropshipManager::NO_ERROR,
            'supplier' => MxcDropshipInnocigs::getModule()->getName(),
            'message' => 'Prices successfully updated.'
        ];
    }

    public function onUpdateStock(EventInterface $e)
    {
        // may throw
        $this->services->get(UpdateStock::class)->run();
        return [
            'code' => DropshipManager::NO_ERROR,
            'supplier' => MxcDropshipInnocigs::getModule()->getName(),
            'message' => 'Stock successfully updated.'
        ];
    }

    public function onSendOrder(EventInterface $e)
    {
        /** @var OrderProcessor $processor */
        $processor = $this->services->get(OrderProcessor::class);
        // return the order's new mxcbc_dsi_ic_status
        return $processor->sendOrder($e->getParam('order'), $e->getTarget());
    }

    public function onUpdateTrackingData(EventInterface $e)
    {
        $order = $e->getParam('order');
        /** @var TrackingDataProcessor $processor */
        $processor = $this->services->get(TrackingDataProcessor::class);
        return $processor->updateTrackingData($e->getParam('order'), $e->getTarget());
    }
}