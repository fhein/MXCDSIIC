//{block name="backend/article/view/variant/detail"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.variant.Detail', {
    override: 'Shopware.apps.Article.view.variant.Detail',

    createItems: function() {

        let me = this,
            panelTab = me.callParent(arguments);

        me.innocigsFieldSet = Ext.create('Shopware.apps.MxcDsiDropship.innocigs.article.view.detail.Base');
        me.innocigsFieldSet.detailId = me.record.data.id;
        me.innocigsFieldSet.mainWindow = me;
        me.formPanel.insert(5, me.innocigsFieldSet);

        debugger;
        return panelTab;
    },

    onAfterRender: function(me){
        debugger;
        me.innocigsFieldSet.detailId = me.record.data.id;
        me.innocigsFieldSet.mainWindow = me;
        me.innocigsFieldSet.onMxcDsiInnocigsSettings({ detailId: detailId })
    }

    // onStoresLoaded: function() {
    //     let me = this;
    //     me.callParent(arguments);
    //     let detailId = me.record.data.id;
    //     me.innocigsFieldSet.detailId = detailId;
    //     me.innocigsFieldSet.mainWindow = me;
    //     me.innocigsFieldSet.onMxcDsiInnocigsSettings({ detailId: detailId })
    // }

});
//{/block}