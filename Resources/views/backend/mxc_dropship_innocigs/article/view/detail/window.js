//{block name="backend/article/view/detail/window"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.detail.Window', {
    override: 'Shopware.apps.Article.view.detail.Window',

    createBaseTab: function() {
        let me = this;
        let panelTab = me.callParent(arguments);
        me.innocigsFieldSet = Ext.create('Shopware.apps.MxcDropshipInnocigs.article.view.detail.Base');
        me.detailForm.insert(1, me.innocigsFieldSet);
        return panelTab;
    },

    onStoresLoaded: function() {
        let me = this;
        me.callParent(arguments);
        let detailId = me.article.data.mainDetailId;
        me.innocigsFieldSet.detailId = detailId;
        me.innocigsFieldSet.mainWindow = me;
        me.innocigsFieldSet.onMxcDsiInnocigsSettings({ detailId: detailId })
    }
});
//{/block}