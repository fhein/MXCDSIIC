<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Template_Manager;
use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class BackendArticleSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;

    private $basePath = 'backend/mxc_dropship_innocigs';
    
    private $registry = null;
    private $services;
    private $log;

    public function __construct()
    {
        $this->services = MxcDropshipInnocigs::getServices();
        $this->log = $this->services->get('logger');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
//            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onBackendArticlePostDispatch',
//            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'onBackendArticleListPostDispatch',
//            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDsiArticleInnocigs' => 'onGetControllerPathMxcDsiArticleInnocigs',
//            'Shopware_Controllers_Frontend_Detail::indexAction::after' => 'onFrontendDetailIndexAfter',
//            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.list_product_service' => 'onFrontendDecorateListProduct',

        ];
    }

    public function onFrontendDecorateListProduct(Enlight_Event_EventArgs $args)
    {

    }

     public function onFrontendDetailIndexAfter(Enlight_Event_EventArgs $args)
     {

     }

    public function onGetControllerBackendPathMxcDsiArticleInnocigs(Enlight_Event_EventArgs $args) {

        return MxcDropshipInnocigs::PLUGIN_DIR . '/Controllers/Backend/MxcDsiArticleInnocigs.php';
    }

    /**
     * Overwrite and manage the backend extjs-resources
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendArticlePostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $actionName = $args->getSubject()->Request()->getActionName();

        if ($actionName === 'load') {

            $view->extendsTemplate($this->basePath . 'article/model/detail.js.sn');
            $view->extendsTemplate($this->basePath . 'article/view/detail/window.js');
            $view->extendsTemplate($this->basePath . 'article/view/detail/base.js');

            $view->extendsTemplate($this->basePath . 'article/view/variant/detail.js');
            $view->extendsTemplate($this->basePath . 'article/view/variant/list.js');

            // $view->extendsTemplate($this->basePath . 'article/controller/detail.js');

        }
        if ($actionName !== 'index') {
//         $view->assign('DCInnoCigs', $this->getLabel() . ' / ' . $source['namelong']);
//         $view->assign('DcOverwritePurchaseprice', Shopware()->Config()->get('dc_overwrite_purchaseprice'));
        }
    }

    // @todo: This applies the dropship marker to the variant list in the article details
    // (when I finally found out to which event this has to be attached)
    public function onBackendArticleVariantListPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        $actionName = $args->getRequest()->getActionName();

        if ($actionName === 'filter') {
            $articleList = $view->getAssign('data');

            // Check if dropship is configured
            foreach ($articleList as &$article) {
                if ($article['Attribute_mxcDsiIcRegistered'] != null) {
                    $article['mxc_dsi_ic_dropship'] = 1;
                    //$article['mxc_dsi_ic_dropship'] = $this->getArticleListDecoration($article['Detail_id']);
                }
            }

            // Overwrite position data
            $view->clearAssign('data');
            $view->assign(
                ['data' => $articleList]
            );
        }
    }

    public function onBackendArticleListPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        $actionName = $args->getRequest()->getActionName();

        if ($actionName === 'load') {
            $view->extendsTemplate($this->basePath . 'article_list/view/main/grid.js');
        }

        if ($actionName === 'filter') {
            $articleList = $view->getAssign('data');

            // Check if dropship is configured
            foreach ($articleList as &$article) {

                if ($article['Attribute_mxcDsiIcRegistered'] === '1') {
                    $article['mxc_dsi_ic_dropship'] = $this->getArticleListDecoration($article['Detail_id']);
                }
            }

            // Overwrite position data
            $view->clearAssign('data');
            $view->assign(
                ['data' => $articleList]
            );
        }
    }

    protected function setupArticle(&$article)
    {
        $settings = $this->getRegistry()->getSettings($article['Detail_id']);
        if (! $settings) return;
        $article['productnumber'] = $settings['productnumber'];
        $article['productname'] = $settings['productname'];
        $article['purchaseprice'] = $settings['purchaseprice'];
        $article['retailprice'] = $settings['uvp'];
        $article['instock'] = $settings['instock'];
        $article['active'] = $settings['active'];
        $article['preferownstock'] = $settings['preferownstock'];
    }

    protected function getArticleListDecoration(int $detailId) {
        $settings = $this->getRegistry()->getSettings($detailId);
        if ($settings === false) return null;
        $this->log->debug(var_export($settings, true));

        if ($settings['mxc_dsi_ic_active'] === '0') {
            $color = 'red';
        } elseif ($settings['mxc_dsi_ic_preferownstock'] === '1') {
            $color = 'orange';
        } else {
            $color = 'limegreen';
        }

        return sprintf(
            '<div style="width:16px;height:16px;background:%s;color:white;margin: 0 auto;'
            .'text-align:center;border-radius: 3px;padding-top: 0;" title="Dropship-Artikel">&nbsp;</div>',
            $color);
    }

    protected function getRegistry() {
        if ($this->registry === null) {
            $this->registry = $this->services->get(ArticleRegistry::class);
        }
        return $this->registry;
    }
}
