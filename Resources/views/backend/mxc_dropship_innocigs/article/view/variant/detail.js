//{block name="backend/article/view/variant/detail"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.variant.Detail', {
    override: 'Shopware.apps.Article.view.variant.Detail',

    createItems: function() {

        let me = this,
            panelTab = me.callParent(arguments);

        me.innocigsFieldSet = Ext.create('Shopware.apps.MxcDropshipInnocigs.article.view.detail.Base');
        me.innocigsFieldSet.detailId = me.record.data.id;
        me.innocigsFieldSet.mainWindow = me;
        me.innocigsFieldSet.onMxcDsiInnocigsSettings({ detailId: me.record.data.id})
        me.formPanel.insert(2, me.innocigsFieldSet);
        return panelTab;
    },
});
//{/block}