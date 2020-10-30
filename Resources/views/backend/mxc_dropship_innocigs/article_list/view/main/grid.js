//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.MxcDropshipInnocigs.ArticleList.view.main.Grid', {
    override: 'Shopware.apps.ArticleList.view.main.Grid',

    getColumns: function () {
        let me = this;
        let columns = me.callOverridden(arguments);
        columns.push({
            header: 'IC',
            width: 30,
            sortable: false,
            renderer: me.isInnocigsDropshipProduct
        });
        return columns;
    },

    isInnocigsDropshipProduct: function(value, metaData, record) {
        let background = record.raw.mxcbc_dsi_ic_bullet_color;
        let title = record.raw.mxcbc_dsi_ic_bullet_title;
        if (background === undefined) return '<div>&nbsp</div>';
        return '<div style="width:16px;height:16px;background:' + background
          + ';color:white;margin: 0 auto;text-align:center;border-radius: 4px;padding-top: 2px;" ' +
          'title="' + title +'">&nbsp</div>';
    },
});
//{/block}
