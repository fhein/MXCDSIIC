<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipIntegrator\Mapping\ImportPriceMapper;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

/**
 * This job pulls the Inncigs purchase and recommended retail prices and updates
 * the the products and variants accordingly
 */
class UpdatePrices implements AugmentedObject
{
    use LoggerAwareTrait;

    /** @var ImportClient  */
    protected $client;

    /** @var ImportPriceMapper  */
    protected $mapper;

    public function __construct(ImportClient $client, ImportPriceMapper $mapper)
    {
        $this->mapper = $mapper;
        $this->client = $client;
    }

    public function run()
    {
        $this->mapper->import($this->client->importFromApi(false, true));
        $this->log->info('Price update job completed.');
    }
}