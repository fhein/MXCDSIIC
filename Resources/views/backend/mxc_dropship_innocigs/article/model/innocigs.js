// Innocigs Dropship Configuration

 //{block name="backend/mxcdsi/model/innocigs"}
Ext.define('Shopware.apps.MxcDropshipInnocigs.model.Innocigs', {
  /**
   * Extends the standard Ext Model
   * @string
   */
  extend: 'Shopware.data.Model',

  configure: function() {
    return {
      controller: 'MxcDsiArticle',
      detail: 'Shopware.apps.article'
    }
  },

  /**
   * The fields used for this model
   * @array
   */
  fields: [
    //{block name="backend/mxcdsi/model/innocigs/fields"}{/block}
    { name: 'productNumber', type: 'string', useNull: true },
    { name: 'productName', type: 'string', useNull: true },
    { name: 'purchasePrice', type: 'float', useNull: true },
    { name: 'retailPrice', type: 'float', useNull: true },
    { name: 'instock', type: 'integer', useNull: true },
    { name: 'active', type: 'integer', useNull: true },
    { name: 'preferOwnStock', type: 'integer', useNull: true },

  ],
  /**
   * Configure the data communication
   * @object
   */
  proxy:{
    /**
     * Set proxy type to ajax
     * @string
     */
    type:'ajax',

    /**
     * Configure the url mapping for the different
     * store operations based on
     * @object
     */
    api: {
      create: '{url action="register"}',
      update: '{url action="register"}',
      destroy: '{url action="unregister"}'
    },

    /**
     * Configure the data reader
     * @object
     */
    reader:{
      type:'json',
      root:'data',
      totalProperty:'total'
    }
  }

});
//{/block}
