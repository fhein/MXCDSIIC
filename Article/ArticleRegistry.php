<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Article;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipInnocigs\Api\ApiClient;

class ArticleRegistry implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use LoggerAwareTrait;

    const NO_ERROR                      = 0;
    const ERROR_DUPLICATE_REGISTRATION  = 1;
    const ERROR_PRODUCT_UNKNOWN         = 2;
    const ERROR_INVALID_ARGUMENT        = 3;

    private $select;
    private $supplier;

    public function init()
    {
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
    }

    // @todo: mxcbc_dsi_mode should be set somewhere else, because it is not Innocigs specific
    private $fields = [
        'mxcbc_dsi_mode'              => null,
        'mxcbc_dsi_supplier'          => null,
        'mxcbc_dsi_ic_registered'     => false,
        'mxcbc_dsi_ic_active'         => false,
        'mxcbc_dsi_ic_productnumber'  => null,
        'mxcbc_dsi_ic_productname'    => null,
        'mxcbc_dsi_ic_purchaseprice'  => null,
        'mxcbc_dsi_ic_retailprice'    => null,
        'mxcbc_dsi_ic_instock'        => null,
    ];

    private $client;

    public function __construct(ApiClient $client)
    {
        if ($client === null) {
            throw InvalidArgumentException::fromInvalidObject(
                ApiClient::class,
                $client
            );
        }
        $this->client = $client;
        $this->select = implode(', ', array_keys($this->fields));
    }

    public function configureDropship(
        Variant $variant,
        int $stockInfo,
        bool $active = true,
        int $deliveryMode = DropshipManager::MODE_DROPSHIP_ONLY
    ) {
        $detail = $variant->getDetail();
        if (! $detail) return;

        $data = [
            'mxcbc_dsi_ic_productnumber'  => $variant->getIcNumber(),
            'mxcbc_dsi_ic_productname'    => $variant->getName(),
            'mxcbc_dsi_ic_purchaseprice'  => $variant->getPurchasePrice(),
            'mxcbc_dsi_ic_retailprice'    => round($variant->getRecommendedRetailPrice(), 2),
            'mxcbc_dsi_ic_instock'        => $stockInfo,
            'mxcbc_dsi_mode'              => $deliveryMode,
            'mxcbc_dsi_supplier'          => $this->supplier,
            'mxcbc_dsi_ic_active'         => $active,
            'mxcbc_dsi_ic_registered'     => true,
        ];
        $this->updateSettings($detail->getId(), $data);
    }

    public function register(int $detailId, string $productNumber, int $deliveryMode)
    {
        if (empty($productNumber)) return [self::ERROR_INVALID_ARGUMENT, $this->fields];

        // reject registration if another detail is already registered for the given product number
        $sql = sprintf(
            'SELECT %s FROM s_articles_attributes WHERE articledetailsid != %s',
            $this->select,
            $detailId
        );
        if (! $this->db->fetchAll($sql)) return [self::ERROR_DUPLICATE_REGISTRATION, $this->fields];

        $info = $this->client->getProduct($productNumber);
        if (empty($info)) return [self::ERROR_PRODUCT_UNKNOWN, null];

        $info = $info[$productNumber];
        $data = [
            'mxcbc_dsi_ic_productnumber'  => $info['model'],
            'mxcbc_dsi_ic_productname'    => $info['name'],
            'mxcbc_dsi_ic_purchaseprice'  => StringTool::toFloat($info['purchasePrice']),
            'mxcbc_dsi_ic_retailprice'    => StringTool::toFloat($info['recommendedRetailPrice']),
            'mxcbc_dsi_ic_instock'        => $this->client->getStockInfo($productNumber),
            'mxcbc_dsi_supplier'          => $this->supplier,
            'mxcbc_dsi_ic_registered'     => true,
            // should be moved to MxcDropship context, because it is not Innocigs specific
            'mxcbc_dsi_mode'              => $deliveryMode,
        ];

        $this->updateSettings($detailId, $data);

        return [self::NO_ERROR, $data];
    }

    public function getSettings(int $detailId)
    {
        $sql = sprintf(
            'SELECT %s FROM s_articles_attributes WHERE articledetailsid = %s',
            $this->select,
            $detailId
        );
        return $this->db->fetchRow($sql);
    }

    public function unregister(int $detailId)
    {
        $this->updateSettings($detailId, $this->fields);
    }

    public function updateSettings(int $id, array $entries)
    {
        $entries = StringTool::dbQuote($entries);
        $entries = array_map(function($key, $value) { return $key . '=' . $value; }, array_keys($entries), $entries);
        $entries = implode(', ', $entries);

        $sql = sprintf(
            'UPDATE s_articles_attributes SET %s WHERE articledetailsID = %u',
            $entries,
            $id
        );
        $this->db->query($sql);
    }
}