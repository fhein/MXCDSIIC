
/**
 * Shopware Store - Innocigs Dropship Information
 * used to load a single dropship information
 */
//{block name="backend/mxcdsi/store/innocigs"}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.store.Innocigs', {
  /**
   * Extend for the standard ExtJS 4
   * @string
   */
  extend:'Ext.data.Store',
  /**
   * Define the used model for this store
   * @string
   */
  model:'Shopware.apps.MxcDsi.model.Innocigs',
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
    url:'{url controller="MxcDsiArticle" action="getSettings"}',

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
