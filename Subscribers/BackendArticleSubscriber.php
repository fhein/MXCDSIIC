<?php

namespace MxcDropshipInnocigs\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class BackendArticleSubscriber implements SubscriberInterface
{
    private $bullets = [
        DropshipManager::MODE_OWNSTOCK_ONLY => [
            'color' => 'PaleVioletRed',
            'text'  => 'Lieferung ausschließlich aus eigenem Lager'
        ],
        DropshipManager::MODE_PREFER_OWNSTOCK => [
            'color' => 'LightSteelBlue',
            'text'  => 'Dropship und eigenes Lager, eigenes Lager bevorzugt',
        ],
        DropshipManager::MODE_PREFER_DROPSHIP => [
            'color' => 'CornFlowerBlue',
            'text'  => 'Dropship und eigenes Lager, Dropship bevorzugt',
        ],
        DropshipManager::MODE_DROPSHIP_ONLY => [
            'color' => 'DarkSeaGreen',
            'text'  => 'Lieferung ausschließlich per Dropship',
        ]
    ];

    private $basePath = 'backend/mxc_dropship_innocigs/';
    
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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onBackendArticlePostDispatch',
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'onBackendArticleListPostDispatch',
        ];
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

        switch ($actionName) {
            case 'save':
                return;
            case 'load':
                $view->extendsTemplate($this->basePath . 'article/view/variant/list.js');
                break;
            case 'detailList':
                $articleList = $view->getAssign('data');

                // Check if dropship is configured
                foreach ($articleList as &$article) {
                    $deliveryMode = $article['attribute']['mxcbcDsiMode'];
                    $registered = $article['attribute']['mxcbcDsiIcRegistered'];
                    if ($registered) {
                        $bullet = $this->bullets[$deliveryMode];
                        $article['mxcbc_dsi_ic_bullet_color'] = $bullet['color'];
                        $article['mxcbc_dsi_ic_bullet_title'] = $bullet['text'];
                    }
                }
                $view->clearAssign('data');
                $view->assign(
                    ['data' => $articleList]
                );
                break;
        }
    }

    public function onBackendArticleListPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();

        $actionName = $args->getRequest()->getActionName();

        switch ($actionName) {
            case 'save':
                return;
            case 'load':
                $view->extendsTemplate($this->basePath . 'article_list/view/main/grid.js');
                break;
            case 'filter':
                $articleList = $view->getAssign('data');

                // Check if dropship is configured
                foreach ($articleList as &$article) {
                    $deliveryMode = $article['Attribute_mxcbcDsiMode'];
                    $registered = $article['Attribute_mxcbcDsiIcRegistered'];
                    if ($registered) {
                        $bullet = $this->bullets[$deliveryMode];
                        $article['mxcbc_dsi_ic_bullet_color'] = $bullet['color'];
                        $article['mxcbc_dsi_ic_bullet_title'] = $bullet['text'];
                    }
                }
                $view->clearAssign('data');
                $view->assign(
                    ['data' => $articleList]
                );
                break;
        }
    }
}
