<?php

namespace MxcDropshipInnocigs\Stock;

use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class StockInfo
{
    protected $client;
    protected $supplier;

    protected $stockCache;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    // query the current stock of a detail configured for InnoCigs dropship
    // $attr is an array containing the article_attributes or compatible
    // queries InnoCigs API if $live = true, consider performance impact
    public function getStock(array $attr, bool $live = false)
    {
        $mode = $attr['mxcbc_dsi_mode'] ?? DropshipManager::MODE_OWNSTOCK_ONLY;
        $stockInfo = [
            'supplier' => $this->supplier,
            'mode' => $mode,
        ];
        $productNumber = $attr['mxcbc_dsi_ic_productnumber'];
        if (empty($productNumber)) {
            $instock = $attr['instock'];
        } else {
            $instock = $mode == DropshipManager::MODE_OWNSTOCK_ONLY ? $attr['instock'] : $attr['mxcbc_dsi_ic_instock'];
            if ($mode != DropshipManager::MODE_OWNSTOCK_ONLY && $live) {
                $cachedStock = @$this->stockCache[$productNumber];
                $instock = $cachedStock ?? $this->client->getStockInfo($productNumber);
                $this->stockCache[$productNumber] = $instock;
            }
        }
        $stockInfo['instock'] = $instock;
        return $stockInfo;
    }
}