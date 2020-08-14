<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcDropshipInnocigs\Services\ImportClient;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipIntegrator\Mapping\ImportPriceMapper;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

/**
 * This job pulls the Inncigs purchase and recommended retail prices and updates
 * the the products and variants accordingly
 */
class UpdatePrices
{
    public static function run()
    {
        $client = MxcDropshipInnocigs::getServices()->get(ImportClient::class);
        $mapper = MxcDropshipIntegrator::getServices()->get(ImportPriceMapper::class);
        $mapper->import($client->import(false));
    }
}