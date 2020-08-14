<?php


namespace MxcDropshipInnocigs\Subscribers;


class CheckoutSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::ajaxCartAction::after' => 'onFrontendCheckoutAjaxCartAfter',
            'Shopware_Controllers_Frontend_Checkout::ajaxAddArticleCartAction::after' => 'onFrontendCheckoutAjaxAddArticleCartAfter',
            'Shopware_Controllers_Frontend_Checkout::cartAction::after' => 'onFrontendCheckoutCartAfter',
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after' => 'onFrontendCheckoutConfirmAfter',
            'sBasket::sCheckBasketQuantities::replace' => 'onCheckBasketQuantities',
        ];
    }

    public function onFrontendCheckoutAjaxCartAfter()
    {

    }

    public function onFrontendCheckoutAjaxAddArticleCartAfter()
    {

    }

    public function onFrontendCheckoutCartAfter()
    {

    }

    public function onFrontendCheckoutConfirmAfter()
    {

    }

    public function onCheckBasketQuantities()
    {

    }

}