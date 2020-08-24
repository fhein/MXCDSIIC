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
use MxcDropshipIntegrator\Dropship\DropshipManager;
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
        'mxcbc_dsi_ic_registered'     => false,
        'mxcbc_dsi_ic_status'         => null,
        'mxcbc_dsi_ic_active'         => null,
        'mxcbc_dsi_ic_delivery' => null,
        'mxcbc_dsi_ic_productnumber'  => null,
        'mxcbc_dsi_ic_productname'    => null,
        'mxcbc_dsi_ic_purchaseprice'  => null,
        'mxcbc_dsi_ic_retailprice'    => null,
        'mxcbc_dsi_ic_instock'        => null,
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
            'mxcbc_dsi_ic_productnumber'  => $variant->getIcNumber(),
            'mxcbc_dsi_ic_productname'    => $variant->getName(),
            'mxcbc_dsi_ic_purchaseprice'  => $variant->getPurchasePrice(),
            'mxcbc_dsi_ic_retailprice'    => round($variant->getRecommendedRetailPrice(), 2),
            'mxcbc_dsi_ic_instock'        => $stockInfo[$variant->getIcNumber()] ?? 0,
            'mxcbc_dsi_ic_delivery'       => DropshipManager::DELIVERY_DROPSHIP_ONLY,
            'mxcbc_dsi_ic_active'         => true,
            'mxcbc_dsi_ic_status'         => ArticleRegistry::NO_ERROR,
            'mxcbc_dsi_ic_registered'     => true,
        ];
        $this->updateSettings($detail->getId(), $data);
    }


    public function register(int $detailId, string $productNumber, bool $active, int $delivery)
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
            'mxcbc_dsi_ic_productnumber'  => $info['model'],
            'mxcbc_dsi_ic_productname'    => $info['name'],
            'mxcbc_dsi_ic_purchaseprice'  => StringTool::toFloat($info['purchasePrice']),
            'mxcbc_dsi_ic_retailprice'    => StringTool::toFloat($info['recommendedRetailPrice']),
            'mxcbc_dsi_ic_instock'        => $this->client->getStockInfo($productNumber),
            'mxcbc_dsi_ic_delivery'       => $delivery,
            'mxcbc_dsi_ic_active'         => $active,
            'mxcbc_dsi_ic_status'         => self::NO_ERROR,
            'mxcbc_dsi_ic_registered'     => true,
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