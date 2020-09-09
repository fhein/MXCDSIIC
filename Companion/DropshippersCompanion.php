<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Companion;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Plugin\Plugin;
use MxcDropshipInnocigs\Api\ApiClient;

class DropshippersCompanion implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;
    use DatabaseAwareTrait;

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

    public function getDetailPrices() {
        $sql = '
            SELECT 
                d.id,
                d.purchaseprice,
                aa.dc_ic_ordernumber as number 
            FROM s_articles_details d
            LEFT JOIN s_articles_attributes aa ON d.id = aa.articledetailsID  
            WHERE aa.dc_ic_ordernumber IS NOT NULL
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
}