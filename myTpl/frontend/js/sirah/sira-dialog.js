
(function($){
var baseUri=getBaseUrl();
$.widget("ui.siraDialog",$.ui.dialog,{

   options:{
       titre:'Sira',
       url:null,
       dataType:'html',
       dataTransport:'POST',
       sendData:{},
       largeur:600,
       hauteur:160,
       cacherFermer:false,
       position:'center',
       modal:true,
       classe:'dialog',
       redimensionable:false,
       deplacable:true,
       avecAjax:false,
       texteFermeture:'Fermer',
       loaderSrc:baseUri+'/icones/ajax-loader.gif'       
      },

     _create:function(){
            $.ui.dialog.prototype._create.apply(this, arguments);
            var self = this;
            this._setOption("closeText",this.options.texteFermeture);
            this._setOption("modal", this.options.modal);
            this._setOption("title", this.options.titre);
            this._setOption("dialogClass", this.options.classe);
            this._setOption("closeOnEscape", false);
            this._setOption("resizable", this.options.redimensionable);
            this._setOption("draggable", this.options.deplacable);
            this._setOption("minWidth",this.options.largeur);
            this._setOption("minHeight",this.options.hauteur);
            this._setOption("position",this.options.position);
            if(this.options.avecAjax){
               self.loadData();
            }
        },

    loadData:function(){   
     var el=this.element;
      this.loading();
        $.ajax({
             url:this.options.url,
             dataType:this.options.dataType,
             type:this.options.dataTransport,
             data:this.options.sendData,         
             error:function(data){

                 el.html("<font color='red'>"+data.error+"</font>");

              },
             success:function(data){
                //$('div').remove("#loading");
                el.html(data);
             },
            complete:function(msg){

             
             }
          });
      },
     loading:function(){
     var el=this.element;
     var loader=$("<div id='loading'><img src='"+this.options.loaderSrc+"'  /></div>");      
     el.append(loader);
     var top=$('#loading').height()/2;
     var left=$('#loading').width()/2;
      $('div#loading img').css({
          position:'absolute',
          top:top+'px',
          left:left+'px',
         });
      },

      destroy:function(){            
            $.ui.dialog.prototype.destroy.apply(this, arguments);
            
        },
        close:function(){
           
             var el=this.element;
            el.html("");
            $.ui.dialog.prototype.close.apply(this, arguments);
        },
        open:function(){
            $([document, window]).unbind('keydown.dialog-overlay');
            if(this.options.cacherFermer){
                $('.ui-dialog-titlebar-close').hide();
             }
            $.ui.dialog.prototype.open.apply(this, arguments);

        },

        _setOption:function(key, value){        
            $.ui.dialog.prototype._setOption.apply(this, arguments);

        }

   });
})(jQuery);
