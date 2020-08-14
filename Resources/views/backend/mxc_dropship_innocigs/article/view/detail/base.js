//{namespace name=backend/article/view/main}
//{block name="backend/article/view/detail/base"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.MxcDropshipInnocigs.article.view.detail.Base', {
  extend:'Ext.form.FieldSet',

  /**
   * The Ext.container.Container.layout for the fieldset's immediate child items.
   * @object
   */
  layout: 'column',

  /**
   * List of short aliases for class names. Most useful for defining xtypes for widgets.
   * @string
   */
  alias:'widget.article-mxc-innocigs-field-set',

  /**
   * Set css class for this component
   * @string
   */
  cls: Ext.baseCSSPrefix + 'article-base-field-set',

  /**
   * Contains the field set defaults.
   */
  defaults: {
    labelWidth: 155
  },

  initComponent:function () {
    let me = this;
    me.title = 'maxence Dropship Integrator / InnoCigs';
    me.items = me.createElements();
    me.callParent(arguments);
  },

  /**
   * Creates the both containers for the field set
   * to display the form fields in two columns.
   *
   * @return Ext.container.Container[] Contains the left and right container
   */
  createElements:function () {
    let leftContainer, rightContainer, me = this;

    me.createMxcControls();

    leftContainer = Ext.create('Ext.container.Container', {
      columnWidth:0.5,
      defaults: {
        labelWidth: 155,
        anchor: '100%'
      },
      padding: '0 20 0 0',
      layout: 'anchor',
      border:false,
      items:[
        me.mxc_dsi_ic_productname,
        me.mxc_dsi_ic_purchaseprice,
        me.mxc_dsi_ic_instock,
      ]
    });

     rightContainer = Ext.create('Ext.container.Container', {
      columnWidth:0.5,
      layout: 'anchor',
      defaults: {
        labelWidth: 155,
        anchor: '100%'
      },
      border:false,
      items:[
        me.mxc_dsi_ic_productnumber,
        me.mxc_dsi_ic_retailprice,
        me.mxc_dsi_ic_preferownstock,
        me.mxc_dsi_ic_active
      ]
    });

    me.createButtons();

    return [
      leftContainer,
      rightContainer,
      me.mxc_dsi_ic_savebutton,
      me.mxc_dsi_ic_removebutton
    ] ;
  },

  createMxcControls: function() {
    let me = this;

    me.mxc_dsi_ic_active = Ext.create('Ext.form.field.Checkbox', {
      name: 'attribute[mxc_dsi_ic_active]',
      fieldLabel: 'Aktivieren',
      inputValue: true,
      uncheckedValue: false,
      labelWidth: 155
    });

    me.mxc_dsi_ic_productnumber = Ext.create('Ext.form.field.Text', {
      name: 'attribute[mxc_dsi_ic_productnumber]',
      fieldLabel: 'Artikelnummer',
      labelWidth: 155
    });

    me.mxc_dsi_ic_purchaseprice = Ext.create('Ext.form.field.Text', {
      name: 'attribute[mxc_dsi_ic_purchaseprice]',
      readOnly: true,
      fieldLabel: 'Einkaufspreis',
      labelWidth: 155
    });

    me.mxc_dsi_ic_instock = Ext.create('Ext.form.field.Text', {
      name: 'attribute[mxc_dsi_ic_instock]',
      decimalPrecision: 0,
      readOnly: true,
      fieldLabel: 'Bestand',
      labelWidth: 155
    });

    me.mxc_dsi_ic_preferownstock = Ext.create('Ext.form.field.Checkbox', {
      name: 'attribute[mxc_dsi_ic_preferownstock]',
      fieldLabel: 'Eigenes Lager bevorzugen',
      inputValue: true,
      uncheckedValue: false,
      labelWidth: 155
    });

    me.mxc_dsi_ic_productname = Ext.create('Ext.form.field.Text', {
      name: 'attribute[mxc_dsi_ic_productname]',
      readOnly: true,
      fieldLabel: 'Artikelbezeichnung',
      labelWidth: 155,
      width: 450
    });

    me.mxc_dsi_ic_retailprice = Ext.create('Ext.form.field.Text', {
      name: 'attribute[mxc_dsi_ic_retailprice]',
      readOnly: true,
      fieldLabel: 'Unverbindliche Preisempfehlung',
      labelWidth: 155
    });
  },

  createButtons: function() {
    let me = this;

    me.mxc_dsi_ic_savebutton = Ext.create('Ext.button.Button', {
      text: 'Übernehmen',
      cls: 'primary',
      style : {
        'float' : 'right'
      },
      listeners: {
        click: function(editor, e) {
          let params = {
            detailId: me.detailId,
            productNumber: me.mxc_dsi_ic_productnumber.getValue(),
            active: me.mxc_dsi_ic_active.getValue() ? 1 : 0,
            preferOwnStock: me.mxc_dsi_ic_preferownstock.getValue() ? 1 : 0,
          };
          me.onMxcDsiInnocigsRegister(params);
        }
      }
    });

    me.mxc_dsi_ic_removebutton = Ext.create('Ext.button.Button', {
      text: 'Löschen',
      cls: 'secondary',
      style : {
        'float' : 'right'
      },
      listeners: {
        click: function(editor, e) {

          let productNumber = me.mxc_dsi_ic_productnumber.getValue();
          if (productNumber === '') return;
          if (me.detailId == null) {
            Shopware.Notification.createGrowlMessage('Fehler', 'Sie haben einen neuen Artikel angelegt aber nicht nicht gespeichert. Sie können einen Dropshipping-Artikel erst hinzufügen, sobald Sie den Artikel gespeichert haben.', 'MxcDropshipInnocigs');
            return;

          }
          me.onMxcDsiInnocigsUnregister({ detailId: me.detailId })

        }
      }
    });
  },

  onMxcDsiInnocigsRegister: function (params) {
    let me = this;

    if (params.detailId == null) {
      Shopware.Notification.createGrowlMessage('Fehler', 'Sie haben einen neuen Artikel angelegt aber nicht nicht gespeichert. Sie können einen Dropshipping-Artikel erst hinzufügen, sobald Sie den Artikel gespeichert haben.', 'MxcDropshipInnocigs');
    }

    if (params.productNumber === '') {
      Shopware.Notification.createGrowlMessage('Fehler', 'Bitte geben Sie eine Artikelnummer an', 'MxcDropshipInnocigs');
      me.ordernumber.focus();
      return;
    }

    me.mainWindow.setLoading(true);
    Ext.Ajax.request({
      method: 'POST',
      url: '{url controller=MxcDsiArticleInnocigs action=register}',
      params: params,
      success: function(responseData, request) {
        let response = Ext.JSON.decode(responseData.responseText);
        me.mainWindow.setLoading(false);
        if (response.success === false) {
          if (response.info !== '') {
            Shopware.Notification.createGrowlMessage(response.info.title, response.info.message, 'MxcDropshipInnocigs');
          }
        } else {
          let overwritePurchaseprice = true;
          if (overwritePurchaseprice) {
            document.getElementsByName('mainDetail[purchasePrice]')[0].value = response.datamxc_dsi_ic_purchaseprice;
          }

          me.mxc_dsi_ic_productname.setValue(response.data.mxc_dsi_ic_productname);
          me.mxc_dsi_ic_purchaseprice.setValue(response.data.mxc_dsi_ic_purchaseprice);
          me.mxc_dsi_ic_retailprice.setValue(response.data.mxc_dsi_ic_retailprice);
          me.mxc_dsi_ic_instock.setValue(response.data.mxc_dsi_ic_instock);
          me.mxc_dsi_ic_active.setValue(response.data.mxc_dsi_ic_active);
          me.mxc_dsi_ic_preferownstock.setValue(response.data.mxc_dsi_ic_preferownstock);
          Shopware.Notification.createGrowlMessage('Erfolg', 'Dropship erfolgreich registriert.', 'MxcDropshipInnocigs');
        }
      },
      failure: function(responseData, request) {
        me.mainWindow.setLoading(false);
        Shopware.Notification.createGrowlMessage('Fehler', 'Daten konnten nicht gespeichert werden.', 'MxcDropshipInnocigs');
      }
    });
  },

  onMxcDsiInnocigsUnregister: function (params) {
    let me = this;

    me.mainWindow.setLoading(true);
    Ext.Ajax.request({
      method: 'POST',
      url: '{url controller=MxcDsiArticleInnocigs action=unregister}',
      params: params,
      success: function(responseData, request) {
        let response = Ext.JSON.decode(responseData.responseText);
        me.mainWindow.setLoading(false);

        me.mxc_dsi_ic_active.setValue(0);
        me.mxc_dsi_ic_preferownstock.setValue(0);
        me.mxc_dsi_ic_productnumber.setValue('');
        me.mxc_dsi_ic_productname.setValue('');
        me.mxc_dsi_ic_purchaseprice.setValue('');
        me.mxc_dsi_ic_retailprice.setValue('');
        me.mxc_dsi_ic_instock.setValue('');

        Shopware.Notification.createGrowlMessage('Erfolgreich', 'Dropship Konfiguration für InnoCigs gelöscht.', 'MxcDropshipInnocigs');
      },
      failure: function(responseData, request) {
        me.mainWindow.setLoading(false);
        Shopware.Notification.createGrowlMessage('Fehler', 'Daten konnten nicht gespeichert werden.', 'MxcDropshipInnocigs');
      }
    });
  },

  onMxcDsiInnocigsSettings: function(params) {
    let me = this;
    Ext.Ajax.request({
      url: '{url controller=MxcDsiArticleInnocigs action=getSettings}',
      params: params,
      success: function(responseData, request) {
        let response = Ext.JSON.decode(responseData.responseText);
        if (response.success) {
          me.mxc_dsi_ic_active.setValue(response.data.mxc_dsi_ic_active);
          me.mxc_dsi_ic_preferownstock.setValue(response.data.mxc_dsi_ic_preferownstock);
          me.mxc_dsi_ic_productnumber.setValue(response.data.mxc_dsi_ic_productnumber);
          me.mxc_dsi_ic_productname.setValue(response.data.mxc_dsi_ic_productname);
          me.mxc_dsi_ic_purchaseprice.setValue(response.data.mxc_dsi_ic_purchaseprice);
          me.mxc_dsi_ic_retailprice.setValue(response.data.mxc_dsi_ic_retailprice);
          me.mxc_dsi_ic_instock.setValue(response.data.mxc_dsi_ic_instock);
        }
      }
    });
  },
});
//{/block}
