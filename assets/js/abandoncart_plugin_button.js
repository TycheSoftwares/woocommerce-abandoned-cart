/**
 * This function will add the new button in the tinymce editor.
 * It will add all the mergecode of the plugin.
 * @function wcal_filter_mce_plugin
 */
(function() {

  /**
   * This function will add the new button in the tinymce editor.
   * It will add all the mergecode of the plugin.
   * @return array buttons
   * @since: 2.6 
   */
	tinymce.PluginManager.add('abandoncart', function(editor, url) {

	editor.addButton('abandoncart', {
        type: 'menubutton',
        text: false,
        icon: "abandoncart_email_variables",
        menu: [
               {
                   text: 'Customer First Name',
                   value: '{{customer.firstname}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
                   text: 'Customer Last Name',
                   value: '{{customer.lastname}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
                   text: 'Customer Full Name',
                   value: '{{customer.fullname}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
                   text: 'Product Information/Cart Content',
                   value: '{{products.cart}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
                   text: 'Cart Link',
                   value: '{{cart.link}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
                   text: 'Date when Cart was abandoned',
                   value: '{{cart.abandoned_date}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               },
               {
            	   text: 'Unsubscribe Link',
                   value: '{{cart.unsubscribe}}',
                   onclick: function() {
                       editor.insertContent(this.value());
                   }
               }
               
           ]
    });

});

})();