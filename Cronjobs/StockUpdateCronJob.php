<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use DateTime;
use DateTimeInterface;
use Enlight\Event\SubscriberInterface;
use MxcCommons\Plugin\Service\LoggerInterface;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Services\ApiClient;
use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcDropshipIntegrator\Models\Product;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;
use Throwable;

class StockUpdateCronJob implements SubscriberInterface
{
    protected $log = null;

    protected $companionPresent = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcInnocigsTrackingDataUpdateStockUpdate' => 'onStockUpdate',
        ];
    }

    public function onStockUpdate(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $services = MxcDropshipInnocigs::getServices();
        /** @var LoggerInterface $log */
        $this->log = $services->get('logger');
        $this->modelManager = $services->get('models');
        $result = true;

        if (! $this->isCompanionInstalled()) {
            $this->log->info('Update stock cronjob: Companion is not installed.');
        }

        $start = date('d-m-Y H:i:s');

        try {
            $this->updateStockInfo();
            $this->syncReleaseDates();
            $this->unsetOutdatedReleaseDates();
        } catch (Throwable $e) {
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $this->log->info($msg) : $this->log->error($msg);

        return $result;
    }

    /** Return the release date of a Shopware article
     * @param Article $article
     * @return DateTimeInterface|null
     */
    protected function getArticleReleaseDate(Article $article)
    {
        $releaseDate = null;
        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $releaseDate = $detail->getReleaseDate();
            if ($releaseDate !== null) break;
        }
        return $releaseDate;
    }

    /**
     * Write release date to all details belonging to an article
     * @param Article $article
     * @param DateTime $releaseDate
     */
    protected function setArticleReleaseDate(Article $article, DateTime $releaseDate)
    {
        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detail->setReleaseDate($releaseDate);
        }
    }

    /** Get all products with release date set. Get associated article.
     *  Set article release date if not set already.
     *  Pullback article release date to product if article release date is set
     */
    protected function syncReleaseDates()
    {
        $products = $this->modelManager->getRepository(Product::class)->getProductsWithReleaseDate();
        /** @var Product $product */
        foreach ($products as $product) {
            /** @var Article $article */
            $article = $product->getArticle();
            if ($article === null) continue;
            $articleReleaseDate = $this->getArticleReleaseDate($article);
            if ($articleReleaseDate === null) {
                $productReleaseDate = $product->getReleaseDate();
                if (! empty($productReleaseDate)) {
                   $releaseDate = DateTime::createFromFormat('d.m.Y H:i:s', $product->getReleaseDate() . ' 00:00:00');
                    if (!$releaseDate instanceof DateTime) {
                        $this->log->warn('Wrong release date string: ' . $product->getName() . ', string: ' . $productReleaseDate);
                    } else {
                        $this->setArticleReleaseDate($article, $releaseDate);
                        $this->log->info('Setting release date of ' . $article->getName() . ' to ' . $product->getReleaseDate());
                    }
                }
            } else {
                $releaseDate = $articleReleaseDate->format('d.m.Y');
                if ($product->getReleaseDate() != $releaseDate) {
                    $product->setReleaseDate($releaseDate);
                    $this->log->info('Pulling back release date of ' . $article->getName() . ': ' . $releaseDate);
                }
            }
        }
        $this->modelManager->flush();
    }

    // unset the (future) release date for articles in stock
    protected function unsetOutdatedReleaseDates() {
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        $productRepository = $this->modelManager->getRepository(Product::class);
        /** @var Article $article */
        foreach ($articles as $article) {
            $details = $article->getDetails();
            /** @var Detail $detail */

            // check if any of the details has an release date !== null
            $releaseDate = null;
            foreach ($details as $detail) {
                $releaseDate = $detail->getReleaseDate();
                if ($releaseDate !== null) break;
            }
            // skip article if there is no release date
            if ($releaseDate === null) continue;

            // determine if a quantity of any of the details is in stock
            $instock = 0;
            foreach ($details as $detail) {
                $attr = ArticleTool::getDetailAttributes($detail);
                $instock += $attr['mxc_dsi_ic_instock'];
            }
            // unset the release date if the any of the product's variants is in stock
            if ($instock !== 0) {
                $this->log->info('Unsetting release dates of '. $article->getName());
                $releaseDate = null;
                $productRepository->getProduct($article)->setReleaseDate($releaseDate);
            } else {
                // if the product is still not in stock and the releasedate is in the past
                // set the release date 3 days in the future
                $now = new DateTime();
                if ($now > $releaseDate) {
                    $this->log->info('Promoting the release date of ' . $article->getName());
                    $releaseDate = new DateTime('+3 days');
                }
            }

            // sync release dates if the article is still out of stock
            // unset release date if at least one variant is in stock
            foreach ($details as $detail) {
                $detail->setReleaseDate($releaseDate);
            }
        }
        $this->modelManager->flush();
    }

    protected function updateStockInfo()
    {
        /** @var ApiClient $apiClient */
        $services = MxcDropshipInnocigs::getServices();
        $apiClient = $services->get(ApiClient::class);
        /** @var ArticleRegistry $registry */
        $registry = $services->get(ArticleRegistry::class);

        $info = $apiClient->getItemList(true, false);
        $stockInfo = $apiClient->getAllStockInfo();
        $details = $this->modelManager->getRepository(Detail::class)->findAll();

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detailId = $detail->getId();
            $settings = $registry->getSettings($detailId);
            $productNumber = $settings['mxc_dsi_ic_productnumber'];
            if (empty($productNumber)) continue;
            if ($info[$productNumber] === null) continue;

            // record from InnoCigs available
            $purchasePrice = StringTool::toFloat($info[$productNumber]['purchasePrice']);
            $retailPrice = StringTool::tofloat($info[$productNumber]['recommendedRetailPrice']);
            $instock = intval($stockInfo[$productNumber] ?? 0);

            // vapee dropship attributes
            $settings['mxc_dsi_ic_purchaseprice'] = $purchasePrice;
            $settings['mxc_dsi_ic_retailprice'] = $retailPrice;
            $settings['mxc_dsi_ic_instock'] = $instock;
            $registry->updateSettings($detailId, $settings);

            // For now we override shopware's purchase price @todo: Configurable?
            $detail->setPurchasePrice($purchasePrice);

            // dropshippers companion attributes (legacy dropship support)
            if (! $this->isCompanionInstalled()) continue;

            ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', $info[$productNumber]['purchasePrice']);
            ArticleTool::setDetailAttribute($detail, 'dc_ic_retail_price', $info[$productNumber]['recommendedRetailPrice']);
            ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', $instock);

        }
    }

    protected function isCompanionInstalled() {
        if ($this->companionPresent === null) {
            $this->companionPresent = (null != $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs']));
        }
        return $this->companionPresent;
    }
}