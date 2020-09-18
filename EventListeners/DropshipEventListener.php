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
use MxcDropshipInnocigs\Stock\StockInfo;
use Throwable;

class DropshipEventListener implements AugmentedObject
{
    use ServicesAwareTrait;

    public function attach(SharedEventManagerInterface $sharedEvents) {
        $sharedEvents->attach(DropshipManager::class, 'updatePrices', [$this, 'onUpdatePrices']);
        $sharedEvents->attach(DropshipManager::class, 'updateStock', [$this, 'onUpdateStock']);
        $sharedEvents->attach(DropshipManager::class, 'getStockInfo', [$this, 'onGetStockInfo']);
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

    public function onGetStockInfo(EventInterface $e)
    {
        $params = $e->getParams();
        /** @var StockInfo $stockInfo */
        $stockInfo = $this->services->get(StockInfo::class);
        $stockInfo = $stockInfo->getStock($params['attr']);
        if ($params['stopIfAvailable'] == true && $stockInfo['instock'] > 0) {
            $e->stopPropagation(true);
        }
        return $stockInfo;
    }
}