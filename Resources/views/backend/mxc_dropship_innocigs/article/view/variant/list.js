//{namespace name=backend/article/view/main}
//{block name="backend/article/view/variant/list" append}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.variant.List', {
  override: 'Shopware.apps.Article.view.variant.List',

  getColumns: function () {
    let me = this;
    let columns = me.callOverridden(arguments);

    return Ext.Array.insert(columns, 0, [{
      header: 'IC',
      width: 30,
      sortable: false,
      renderer: me.isInnocigsDropshipProduct
    }]);
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
