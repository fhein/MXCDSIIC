//{block name="backend/article/controller/detail" append}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.controller.Detail', {
    override: 'Shopware.apps.Article.controller.Detail',

    // currently not registered with backend

    onSaveArticle: function(win, article, options) {
        let me = this,
            originalCallback = options.callback;

        let customCallbackMxcDsi = function(newArticle, success) {
            Ext.callback(originalCallback, this, arguments);

            let productNumber = me.getDetailForm().getForm().getFieldValues()['attribute[productnumber]'];
            let active = me.getDetailForm().getForm().getFieldValues()['attribute[active]'];
            let preferOwnStock = me.DetailForm().getForm().getFieldValues()['attribute[preferownstock'];
            active = active ? 1 : 0;
            preferOwnStock = preferOwnStock ? 1 : 0;

            Ext.Ajax.request({
                method: 'POST',
                url: '{url controller=MxcDsiArticleInnocigs action=register}',
                params: {
                    articleId: newArticle.get('mainDetailId'),
                    productNumber: productNumber,
                    active: active,
                    preferOwnStock: preferOwnStock
                },
                success: function(responseData, request) {
                    var response = Ext.JSON.decode(responseData.responseText);
                    if (!response.success) {
                        Shopware.Notification.createGrowlMessage(response.info.title, response.info.message, 'MxcDropshipInnocigs');
                    } else {
                        me.getDetailForm().getForm().findField('attribute[instock]').setValue(response.data.instock);
                    }
                },
                failure: function(responseData, request) {
                    Shopware.Notification.createGrowlMessage('Fehler', 'InnoCigs-Werte konnten nicht gespeichert werden.', 'MxcDropshipInnocigs');
                    console.log(responseData);
                }
            });
        };

        if (!options.callback || options.callback.toString() !== customCallbackMxcDsi.toString()) {
            options.callback = customCallbackMxcDsi;
        }

        me.callParent([win, article, options]);
    }
});
//{/block}