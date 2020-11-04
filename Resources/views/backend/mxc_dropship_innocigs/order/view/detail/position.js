//{block name="backend/order/view/detail/position"}
{$smarty.block.parent}

Ext.define('Shopware.apps.TestPlugin.view.detail.Position', {

  override: 'Shopware.apps.Order.view.detail.Position',

  getColumns: function(grid) {
    let me = this;
    let columns = me.callOverridden(arguments);


    return columns;
  }

});

//{/block}