<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Services;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipIntegrator\Dropship\ArticleRegistryInterface;
use MxcDropshipIntegrator\Models\Variant;

class ArticleRegistry implements ModelManagerAwareInterface, LoggerAwareInterface, ArticleRegistryInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    const NO_ERROR                      = 0;
    const ERROR_DUPLICATE_REGISTRATION  = 1;
    const ERROR_PRODUCT_UNKNOWN         = 2;
    const ERROR_INVALID_ARGUMENT        = 3;

    private $fields = [
        'mxc_dsi_ic_registered'     => false,
        'mxc_dsi_ic_status'         => null,
        'mxc_dsi_ic_active'         => null,
        'mxc_dsi_ic_preferownstock' => null,
        'mxc_dsi_ic_productnumber'  => null,
        'mxc_dsi_ic_productname'    => null,
        'mxc_dsi_ic_purchaseprice'  => null,
        'mxc_dsi_ic_retailprice'    => null,
        'mxc_dsi_ic_instock'        => null,
    ];

    private $select;
    private $client;

    private $db;

    public function __construct(ApiClient $client, Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        if ($client === null) {
            throw \MxcDropshipInnocigs\Exception\InvalidArgumentException::fromInvalidObject(
                ApiClient::class,
                $client
            );
        }
        if ($db === null) {
            throw InvalidArgumentException::fromInvalidObject(
                Enlight_Components_Db_Adapter_Pdo_Mysql::class,
                $db);
        }

        $this->client = $client;
        $this->db = $db;

        $this->select = implode(', ', array_keys($this->fields));
    }

    public function configureDropship(Variant $variant, int $stockInfo, bool $active = true)
    {
        $detail = $variant->getDetail();
        if (! $detail) return;

        $data = [
            'mxc_dsi_ic_productnumber'  => $variant->getIcNumber(),
            'mxc_dsi_ic_productname'    => $variant->getName(),
            'mxc_dsi_ic_purchaseprice'  => $variant->getPurchasePrice(),
            'mxc_dsi_ic_retailprice'    => round($variant->getRecommendedRetailPrice(), 2),
            'mxc_dsi_ic_instock'        => $stockInfo[$variant->getIcNumber()] ?? 0,
            'mxc_dsi_ic_preferownstock' => false,
            'mxc_dsi_ic_active'         => true,
            'mxc_dsi_ic_status'         => ArticleRegistry::NO_ERROR,
            'mxc_dsi_ic_registered'     => true,
        ];
        $this->updateSettings($detail->getId(), $data);

    }


    public function register(int $detailId, string $productNumber, bool $active, bool $preferOwnStock)
    {
        if (empty($productNumber)) return [self::ERROR_INVALID_ARGUMENT, $this->fields];

        // reject registration if another detail is already registered for the given product number
        $sql = sprintf(
            'SELECT %s FROM s_articles_attributes WHERE articledetailsid != %s',
            $this->select,
            $detailId
        );
        if (! $this->db->fetchAll($sql)) return [self::ERROR_DUPLICATE_REGISTRATION, $this->fields];

        $info = $this->client->getItemInfo($productNumber);
        if (empty($info)) return [self::ERROR_PRODUCT_UNKNOWN, null];

        $info = $info[$productNumber];
        $data = [
            'mxc_dsi_ic_productnumber'  => $info['model'],
            'mxc_dsi_ic_productname'    => $info['name'],
            'mxc_dsi_ic_purchaseprice'  => StringTool::toFloat($info['purchasePrice']),
            'mxc_dsi_ic_retailprice'    => StringTool::toFloat($info['recommendedRetailPrice']),
            'mxc_dsi_ic_instock'        => $this->client->getStockInfo($productNumber),
            'mxc_dsi_ic_preferownstock' => $preferOwnStock,
            'mxc_dsi_ic_active'         => $active,
            'mxc_dsi_ic_status'         => self::NO_ERROR,
            'mxc_dsi_ic_registered'     => true,
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