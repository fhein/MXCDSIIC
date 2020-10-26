<?php

namespace MxcDropshipInnocigs\EventListeners;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\EventManager\EventManagerInterface;
use MxcCommons\EventManager\ListenerAggregateInterface;
use MxcCommons\EventManager\ListenerAggregateTrait;
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

class DropshipEventListener implements AugmentedObject, ListenerAggregateInterface
{
    use ServicesAwareTrait;
    use ListenerAggregateTrait;

    public function attach(EventManagerInterface $events, $priority = 1) {
        $this->listeners[] = $events->attach('updatePrices', [$this, 'onUpdatePrices'], $priority);
        $this->listeners[] = $events->attach('updateStock', [$this, 'onUpdateStock'], $priority);
        $this->listeners[] = $events->attach('sendOrder', [$this, 'onSendOrder'], $priority);
        $this->listeners[] = $events->attach('updateTrackingData', [$this, 'onUpdateTrackingData'], $priority);
        $this->listeners[] = $events->attach('getTrackingIds', [$this, 'onGetTrackingIds'], $priority);
        $this->listeners[] = $events->attach('initOrder', [$this, 'onInitOrder'], $priority);
    }

    public function onUpdatePrices(EventInterface $e)
    {
        return $this->services->get(UpdatePrices::class)->run();
    }

    public function onUpdateStock(EventInterface $e)
    {
        return $this->services->get(UpdateStock::class)->run();
    }

    public function onInitOrder(EventInterface $e)
    {
        /** @var OrderProcessor $processor */
        $processor = $this->services->get(OrderProcessor::class);
        // set order's initial status
        return $processor->initOrder($e->getParam('order'), $e->getParam('resetError'), $e->getTarget());
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
        /** @var TrackingDataProcessor $processor */
        $processor = $this->services->get(TrackingDataProcessor::class);
        return $processor->updateTrackingData($e->getParam('order'), $e->getTarget());
    }

    public function onGetTrackingIds(EventInterface $e)
    {
        /** @var TrackingDataProcessor $processor */
        $processor = $this->services->get(TrackingDataProcessor::class);
        return $processor->getTrackingIds($e->getParam('order'), $e->getTarget());
    }
}