//{namespace name=backend/article/view/main}
//{block name="backend/article/view/variant/list" append}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.variant.List', {
  override: 'Shopware.apps.Article.view.variant.List',

  getColumns: function () {
    let me = this;
    let columns = me.callOverridden(arguments);

    Ext.Array.push(columns, {
      header: 'DSI',
      width: 30,
      renderer: me.isDropshipProduct
    });

    return columns;
  },

  isDropshipProduct: function(value, metaData, record) {
    // let active = record.data.mxc_dsi_ic_active;
    // let preferOwnStock = record.data.mxc_dsi_ic_preferownstock;
    // let color = null;
    // debugger;
    //
    // if (!active) {
    //   color = 'red';
    // } else if (preferOwnStock) {
    //   color = 'orange';
    // } else {
    //   color = 'limegreen';
    // }
    // return '<div style="width:16px;height:16px;background:' + color + ';color:white;margin:0 auto;'
    //         + 'text-align:center;border-radius:3px;padding-top:0;</div>';

    if (record.data.mxc_dsi_ic_active)
      return record.raw.mxc_dsi_ic_dropship;
  }
});
//{/block}
