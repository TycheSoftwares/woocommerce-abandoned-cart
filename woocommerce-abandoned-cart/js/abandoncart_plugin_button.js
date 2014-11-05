(
        function(){
       
                tinymce.create(
                        "tinymce.plugins.abandoncart",
                        {
                                init: function(d,e) {},
                                createControl:function(d,e)
                                {
                               
                                        if(d=="abandoncart_email_variables"){
                                       
                                                d=e.createMenuButton( "abandoncart_email_variables",{
                                                        title:"Custom Fields",
                                                        icons:false
                                                        });
                                                       
                                                        var a=this;d.onRenderMenu.add(function(c,b){
                                                               
                                                               
                                                                a.addImmediate(b,"Customer First Name", '{{customer.firstname}}');
                                                                a.addImmediate(b,"Customer Last Name", '{{customer.lastname}}');
                                                                a.addImmediate(b,"Customer Full Name", '{{customer.fullname}}');
                                                                
																b.addSeparator();
                                                               
                                                        });
                                                return d
                                       
                                        } // End IF Statement
                                       
                                        return null
                                },
               
                                addImmediate:function(d,e,a){d.add({title:e,onclick:function(){tinyMCE.activeEditor.execCommand( "mceInsertContent",false,a)}})}
                               
                        }
                );
               
                tinymce.PluginManager.add( "abandoncart", tinymce.plugins.abandoncart);
        }
)();