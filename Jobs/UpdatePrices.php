<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\TaxTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Order\DropshipStatus;
use MxcDropshipIntegrator\Models\Variant;
use Throwable;

/**
 * This job pulls the Inncigs purchase and recommended retail prices and updates
 * the the products and variants accordingly
 */
class UpdatePrices implements AugmentedObject
{
    use LoggerAwareTrait;
    use DatabaseAwareTrait;
    use ModelManagerAwareTrait;

    protected $apiClient;

    protected $companion;

    /** @var DropshipManager */
    protected $dropshipManager;

    protected $variantRepository;

    protected $dropshipImportPresent = false;

    protected $vatFactor;

    protected $supplier;

    public function __construct(ApiClient $apiClient, DropshippersCompanion $companion, DropshipManager $dropshipManager)
    {
        $this->apiClient = $apiClient;
        $this->companion = $companion;
        $this->dropshipManager = $dropshipManager;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
        $this->vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
    }

    // called when augmentation is done
    public function init()
    {
        if (class_exists(Variant::class)) {
            $this->dropshipImportPresent = true;
            $this->variantRepository = $this->modelManager->getRepository(Variant::class);
        }
    }

    public function run()
    {
        try {
            throw new \RuntimeException('Some error.');
            $modelPrices = $this->apiClient->getPrices();
            $detailPrices = $this->companion->validate() ? $this->companion->getDetailPrices() : $this->getDetailPrices();
            $changes = false;
            foreach ($detailPrices as $number => $detailPrice) {
                // if we still have a product that InnoCigs removed already we do not have a model price
                if (! isset($modelPrices[$number]['purchasePrice'])) continue;

                $currentPurchasePrice = $detailPrice['purchasePrice'];
                $newPurchasePrice = $modelPrices[$number]['purchasePrice'];

                if ($newPurchasePrice != $currentPurchasePrice) {
                    $changes = true;
                    $id = $detailPrice['id'];
                    $retailPrice = $modelPrices[$number]['recommendedRetailPrice'];
                    $this->setDetailPrices($id, $newPurchasePrice, $retailPrice);
                    $this->log->debug('Adjusted detail prices: '. $number);
                    if (! $this->dropshipImportPresent) continue;
                    $this->setVariantPrices($number, $newPurchasePrice, $retailPrice);
                    $this->log->debug('Adjusted variant prices:' . $number);
                }
            }
            if ($this->dropshipImportPresent && $changes) {
                $this->modelManager->flush();
            }
            $this->log->info('Price update job successfully completed.');
            return true;
        } catch (Throwable $e) {
            $this->dropshipManager->handleDropshipException($this->supplier, 'updatePrices', $e, true);
            return false;
        }
    }

    protected function getDetailPrices()
    {
        $this->log->debug('Companion not called.');
        $sql = '
            SELECT 
                d.id,
                d.purchaseprice,
                a.mxcbc_dsi_ic_productnumber as number 
            FROM s_articles_details d
            LEFT JOIN s_articles_attributes a ON d.id = a.articledetailsID  
            WHERE a.mxcbc_dsi_ic_productnumber IS NOT NULL
        ';
        $data = $this->db->fetchAll($sql);
        $details = [];
        foreach ($data as $item) {
            $details[$item['number']] = [
                'id' => $item['id'],
                'purchasePrice' => $item['purchaseprice'],
            ];
        }
        return $details;
    }

    protected function setDetailPrices(int $detailId, string $purchasePrice, string $retailPrice)
    {
        $purchasePrice = floatval($purchasePrice);
        $retailPrice = floatval($retailPrice);
        $sql = '
            UPDATE s_articles_details d
            LEFT JOIN s_articles_attributes a ON d.id = a.articledetailsID
            SET
                d.purchaseprice = :purchasePrice,
                a.mxcbc_dsi_ic_purchaseprice = :purchasePrice,
                a.mxcbc_dsi_ic_retailprice = :retailPrice
            WHERE 
                d.id = :id
        ';
        $this->db->query(
            $sql,
            [ 'id' => $detailId,
              'purchasePrice' => $purchasePrice,
              'retailPrice' => $retailPrice / $this->vatFactor,
            ]
        );

        if (! $this->companion->validate()) return;

        // update Dropshipper's Companion info also
        $sql = '
            UPDATE s_articles_attributes a
            SET
                a.dc_ic_purchasing_price = :purchasePrice,
                a.dc_ic_retail_price = :retailPrice
            WHERE 
                a.articledetailsID = :id
        ';
        $this->db->query(
            $sql,
            [ 'id' => $detailId,
              'purchasePrice' => $purchasePrice,
              'retailPrice' => $retailPrice
            ]
        );
    }

    protected function setVariantPrices(string $icNumber, string $purchasePrice, string $retailPrice)
    {
        if ($this->variantRepository === null) return;
        /** @var Variant $variant */
        $variant = $this->variantRepository->findOneBy(['icNumber' => $icNumber]);
        if ($variant === null) return;

        $purchasePrice = floatval($purchasePrice);
        $retailPrice = floatval($retailPrice);
        $retailPrice /= $this->vatFactor;
        $variant->setPurchasePrice($purchasePrice);
        $variant->setRecommendedRetailPrice($retailPrice);
    }
}