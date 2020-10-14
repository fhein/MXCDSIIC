//{block name="backend/order/view/list/list" append}
Ext.define('Shopware.apps.MxcDsiInnocigs.order.view.list.List', {

  override: 'Shopware.apps.Order.view.list.List',

  getColumns: function () {
    let me = this;

    let columns = me.callOverridden(arguments);
    return Ext.Array.insert(columns, 1, [{
      header: 'IC',
      width: 30,
      sortable: false,
      renderer: me.getMxcDsiColumn
    }]);
  },

  getMxcDsiColumn: function (value, metaData, record) {
    let background = record.raw.mxcbc_dsi_bullet_background_color;
    let title = record.raw.mxcbc_dsi_bullet_title;
    if (background === undefined) return '<div>&nbsp</div>';
    return '<div style="width:16px;height:16px;background:' + background
      + ';color:white;margin: 0 auto;text-align:center;border-radius: 4px;padding-top: 2px;" ' +
      'title="' + title +'">&nbsp</div>';
  },
});
//{/block}