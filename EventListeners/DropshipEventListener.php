<?php

namespace MxcDropshipInnocigs\EventListeners;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\EventManager\SharedEventManagerInterface;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropshipInnocigs\Jobs\UpdateStock;
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
        $result = [
            'code' => DropshipManager::NO_ERROR,
            'supplierId' => DropshipManager::SUPPLIER_INNOCIGS,
            'message' => 'Prices successfully updated.'
        ];
        try {
            $this->services->get(UpdatePrices::class)->run();
        } catch (Throwable $e) {
            $result['code'] = $e->getCode();
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function onUpdateStock(EventInterface $e)
    {
        $result = [
            'code' => DropshipManager::NO_ERROR,
            'supplierId' => DropshipManager::SUPPLIER_INNOCIGS,
            'message' => 'Stock successfully updated.'
        ];
        try {
            $this->services->get(UpdateStock::class)->run();
        } catch (Throwable $e) {
            $result['code'] = $e->getCode();
            $result['message'] = $e->getMessage();
        }
        return $result;
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