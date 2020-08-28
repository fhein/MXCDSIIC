<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Plugin\Plugin;

class DropshippersCompanion implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var bool */
    protected $companionPresent;

    /** @var ApiClient */
    private $apiClient;

    /** @var array */
    private $stockInfo;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function configureDropship(Variant $variant, int $stockInfo, bool $active = true)
    {
        if (! $this->validate()) return;

        $detail = $variant->getDetail();
        if (! $detail) return;

//        // @todo: $attribute null happens but it should not
//        $attribute = $detail->getAttribute();
//        if (! $attribute) return;

        ArticleTool::setDetailAttribute($detail, 'dc_ic_ordernumber', $variant->getIcNumber());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_articlename', $variant->getName());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', $variant->getPurchasePrice());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_retail_price', $variant->getRecommendedRetailPrice());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', $stockInfo);
        ArticleTool::setDetailAttribute($detail, 'dc_ic_active', $active);
    }

    /**
     * Check if the Dropshipper's Companion for InnoCigs Shopware plugin is installed or not.
     * If installed, check if the required APIs provided by the companion plugin are present.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if ($this->companionPresent === null) {
            $this->companionPresent = (null != $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs']));
        }
        if (! $this->companionPresent) {
            $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
        }
        return $this->companionPresent;
    }

    protected function getStockInfo()
    {
        return $this->stockInfo ?? $this->stockInfo = $this->apiClient->getStockInfo();
    }
}