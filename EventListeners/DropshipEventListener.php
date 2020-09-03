<?php

namespace MxcDropshipInnocigs\EventListeners;

use MxcCommons\EventManager\SharedEventManagerInterface;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropshipInnocigs\Jobs\UpdateStock;
use Throwable;

class DropshipEventListener implements AugmentedObject
{
    use ServicesAwareTrait;

    public function attach(SharedEventManagerInterface $sharedEvents) {
        $sharedEvents->attach(DropshipManager::class, 'updatePrices', [$this, 'onUpdatePrices']);
        $sharedEvents->attach(DropshipManager::class, 'updateStock', [$this, 'onUpdateStock']);
    }

    public function onUpdatePrices($e)
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

    public function onUpdateStock($e)
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

}