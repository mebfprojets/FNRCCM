window.SIRAH   || (window.SIRAH={});
SIRAH.basePath         = SIRAH.basePath || (SIRAH.basePath="/sirahpro/myTpl/sirahpro/");
SIRAH.debug            = SIRAH.debug || 1;
SIRAH.util             = SIRAH.util || {};
SIRAH.anim             = SIRAH.anim || {};
SIRAH.USER             = SIRAH.USER || {};
SIRAH.MYPROFILE        = SIRAH.MYPROFILE || {};
SIRAH.error            = SIRAH.error || {};
SIRAH.error.stack      = SIRAH.error.stack || [];
SIRAH.request          = SIRAH.request || {};
SIRAH.languages        = SIRAH.languages || {};
SIRAH.languages.default= "fr";
SIRAH.languages.string = {
		                   "ERROR"     : {"fr" : "erreur"},
		                   "SUCCESS"   : {"fr" : "succes"},
		                   "WARNING"   : {"fr" : "alerte"},
		                   "NOTICE"    : {"fr" : "notification"},
		                   "INFOS"     : {"fr" : "informations"},
		                   "MESSAGE"   : {"fr" : "Message"}
                             };
SIRAH.validators   = {};
SIRAH.filters      = {};
SIRAH.dialog       = {};
SIRAH.util.inArray = function(item, array)
{
    for( var i = 0; i < array.length; i++){
        if( item==array[i] ){
            return true;
        }
    }
    return false;
};
SIRAH.util.arrayKeyExists = function(itemKey, array){return (itemKey in array);};
SIRAH.util.isset   = function(obj){	return (SIRAH.util.isUndefined(obj));};
SIRAH.util.isEmpty =function(string) 
{	 
	return (
	        string == undefined ||
	        string == "undefined" ||
	        string === '' ||  
	        string  === null || 
	        string === false || 
	        string.length === 0 
	       );
} ;
SIRAH.util.isUndefined = function(obj){return (typeof obj === "undefined");};
SIRAH.util.isBoolean   = function(obj){return (jQuery.type(obj) == "boolean");};
SIRAH.util.isNumber    = function(obj){return (jQuery.type(obj) == "number");};
SIRAH.util.isFunction  = function(obj ) {return ( jQuery.type(obj) == "function");};
SIRAH.util.isArray     = function(obj ) {return (jQuery.type(obj) == "array" );};
SIRAH.util.isString    = function(obj){return (jQuery.type(obj) == "string" );};
SIRAH.util.isDate      = function(obj){	return (jQuery.type(obj) == "date" );};
SIRAH.util.isRegExp    = function(obj){	return toString.call(obj) === "[object RegExp]";};
SIRAH.util.isSelectorString = function(obj){
	if( SIRAH.util.isString(obj) ) {
		var reg = new RegExp("^#(.+)");
		return reg.test(obj);
	}
	return false;
};

SIRAH.util.getSelectorFromString = function(obj)
{
 if( SIRAH.util.isString( obj ) ){
	if( SIRAH.util.isSelectorString(obj) ){
		var reg         = new RegExp("(#)", "g");
		var strSelector = obj.replace(reg , "");
		var selector    = jQuery("#"+strSelector);
		return selector;
	}
	return jQuery("#"+obj);
 } 
   return obj;
};

SIRAH.util.toString = function(str)
{
	if(typeof str == "String"){
		var obj = new String(str);
		return obj;
	} else if(SIRAH.util.isArray(str) || SIRAH.util.isNumber(str)){
		var obj = new String(str.toString());
		return obj;
	} else if(toString.call(str) === "[object String]"){
		return str;
	} 
	return str;
};
SIRAH.util.strtolower = function(str){str = SIRAH.util.toString(str);return str.toLowerCase();};
SIRAH.util.strtoupper = function(str){str  = SIRAH.util.toString(str);return str.toUpperCase();};
SIRAH.util.text = function(str,language)
{
	 var strToUpper  =  SIRAH.util.strtoupper(str);
	 language        =  (!SIRAH.util.isEmpty(language)) ? language : SIRAH.languages.default;
	 if(SIRAH.util.arrayKeyExists(strToUpper,SIRAH.languages.string)){
		 if( SIRAH.util.arrayKeyExists(language,SIRAH.languages.string[strToUpper])){
			 return SIRAH.languages.string[strToUpper][language];
		 }
	 }
	 return str;
};
SIRAH.error.raise = function( selector , messages , type )
{
    selector             = SIRAH.util.getSelectorFromString(selector);
    messages             = (null===messages || SIRAH.util.isEmpty(messages)) ? SIRAH.error.stack : messages;    
    var messagesSelector = jQuery("<div></div>").attr("id","sirah-page-message").addClass("sirah-message").addClass("text-error").addClass('alert-content');
    if(SIRAH.util.isString(messages)){
       var strMessage  = messages;
       messages        = [];
       messages.push(strMessage);
    }
    if(jQuery.isArray(messages) && !SIRAH.util.isEmpty(messages)){    	
         jQuery.each(messages,function(sKey , sValue)
         {	
            var msgType     = (SIRAH.util.isEmpty(type)) ? "error" : type;
            var msgSelector = jQuery("<div></div>");
            var sMsg        = sValue;							  
			if(SIRAH.util.isString(sKey)){msgType = SIRAH.util.strtolower(sKey);}
			if(SIRAH.util.isUndefined(selector)){SIRAH.error.stack.push(sMsg);}
			var errorSelectorClasses   = ["alert","alert-block"];
			switch(msgType){
			   case "error":
			   case "erreur":
			   case "danger":
			   default:
			     errorSelectorClasses.push("alert-danger");
			     break;
			   case "message":
			   case "info":
			   case "information": 
			     errorSelectorClasses.push("alert-info");
			     break;
			   case "success":
			   case "succes":
			     errorSelectorClasses.push("alert-success");
			     break;
			  case "warning":
			     errorSelectorClasses.push("alert-warning");
			     break;   				
			}			
		  jQuery.each(errorSelectorClasses,function(sKeyClass,sValueClass){msgSelector.addClass(sValueClass);});              
          var errorRemoveIcon = jQuery("<i class='glyph-icon alert-close-btn icon-remove'></i>").css({cursor:"pointer"})
		                             .addClass("close").bind("click",function(e){e.preventDefault();msgSelector.fadeOut("slow");});
          msgSelector.append("<strong>"+SIRAH.util.strtoupper(SIRAH.util.text(msgType))+" : </strong> "+SIRAH.util.text(sMsg))
		             .append(errorRemoveIcon);                     
          messagesSelector.append(msgSelector);
         });        
    } 
    if ( !SIRAH.util.isUndefined(selector.attr("id")) ) {
		  if( parseInt(selector.height()) > 300 ) {
		      var selectorPosition = selector.offset();
			  messageSelectorTop   = selectorPosition.top + (parseInt(selector.height())/2);
			  messageSelectorLeft  = selectorPosition.left + 30;
			  messageSelectorWidth = parseInt(selector.width()) - 30;
		      messagesSelector.attr('style','position:absolute;display:none;top:'+messageSelectorTop+'px;left:'+messageSelectorLeft+'px;width:'+messageSelectorWidth+'px; z-index:6000')
			                  .appendTo("body");
	          messagesSelector.fadeToggle({duration:1000,easing:"swing",done:function(){messagesSelector.fadeToggle({duration:30000});}});
		  } else {
			  messagesSelector.prependTo(selector);
		  }		  
	}    
};

SIRAH.request.send = function(formSelector , url , params)
{	
	 formSelector        = SIRAH.util.getSelectorFromString(formSelector);
	 if(SIRAH.util.isUndefined(formSelector) || (SIRAH.util.isEmpty(url) && SIRAH.util.isEmpty(formSelector.attr("action")))){
		 SIRAH.error.raise(formSelector,"Formulaire Invalide");
		 return;
	 }	 
	 if(SIRAH.util.isEmpty(url)){
		 url  = formSelector.attr("action");
	 }
	 var basePath       = SIRAH.basePath;
	 var spinnerImg     = basePath +"assets-minified/images/spinner/loader-dark.gif";
	 var patientezBox   = jQuery("<div class='form-group' id='patientez'><img src="+spinnerImg+" /><strong> Patientez...</strong></div>").css("text-align","center");
	 var defaultData    = formSelector.serialize();
	 var defaultParams  = { data        : defaultData,
			                 contentType : "application/x-www-form-urlencoded",
			                 async       : false,
			                 dataType    :'json',
			                 beforeSend  : function(){formSelector.prepend(patientezBox);jQuery("#sirah-page-message").fadeOut("fast");},
			                 error       : function(data){
			                	 SIRAH.error.raise(formSelector , data ,"error");
                       		     return data;			                	 
			                 },
                             success    : function(data){
                            	  jQuery('#patientez').fadeOut("slow");
                            	  if(!SIRAH.util.isEmpty(data.error)){
                            		  SIRAH.error.raise(formSelector,data.error,"error");
                            		  return data;
                            	  } else if(!SIRAH.util.isEmpty(data.success)){
                            		  SIRAH.error.raise(formSelector , data.success,"success");
                            		  return data;
                            	  }	else if(undefined !== data.reload) {
                            		  var reloadurl  = (SIRAH.util.isEmpty(data.newurl) || (SIRAH.util.isUndefined(data.newurl))) ? url : data.newurl;
                                      document.location.href  =  reloadurl ;
                                  } else {
                            		  SIRAH.error.raise(formSelector,"Aucune reponse valide n'a été retournée par le serveur","error");
                            		  return data;
                            	  }		                	 
			                 },
                             complete : function(data){}
			                };
	 params              = jQuery.extend(true,defaultParams,params);
	 params.url          = url;	 
	 return jQuery.ajax(params);	 	 	
};

SIRAH.request.post = function(formSelector , url , params){
	if(SIRAH.util.isEmpty(params)){
		params           = {};
	}
	 params.type         = "POST";	 
	 SIRAH.request.send(formSelector , url , params);	
};
SIRAH.request.get = function(formSelector , url , params){
	if(SIRAH.util.isEmpty(params)){
		params           = {};
	}
	 params.type         = "GET";	 
	 SIRAH.request.send(formSelector,url,params);	
};
SIRAH.validators.isEmailAddress = function(str){
	return  /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(str);
};
SIRAH.validators.isDate           = function(str){
		return !/Invalid|NaN/.test(new Date(str).toString());
};
SIRAH.validators.isEmptyString    = function(str){
	return  SIRAH.util.isEmpty(str);
};
SIRAH.validators.isUrl            = function(str){
	return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(str);
};
SIRAH.validators.isNumber = function(str){return /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(str);};

SIRAH.dialog.open   = function() {};       
SIRAH.dialog.alert  = function(message , title) {	
	if(SIRAH.util.isEmpty(title)){
        title=" ERREUR DU SYSTEME ";
	}
    var alertRow = jQuery("<div id=\"alertRow\" class=\"row \"></div>");
var alertMsg = jQuery("<div class='alert alert-danger'><div class='bg-red alert-icon'><i class='glyph-icon icon-warning'></i></div><div class='alert-content text-center'> "+message+" </div></div>");
    alertRow.append(alertMsg);   
 jQuery.fn.dialog2.helpers.alert(alertRow,{title:title,closeOnOverlayClick:false,ok:false,cancel:false,buttons:{},close:function(){jQuery(this).dialog2('close'); } });
	return;
};
SIRAH.dialog.confirm   = function(message , title , confirmHandle , declineHandle) {	
	if(SIRAH.util.isEmpty(title)){
        title = " Confirmez cette action ";
	}
	if(!jQuery.isFunction(confirmHandle)) {
		confirmHandle = function(element , params) {
			element.dialog2('close');
			return;
		};
	}
	if(!jQuery.isFunction(declineHandle)) {
		declineHandle = function(element , params) {
			element.dialog2('close');
			return;
		};
	}
    var confirmRow   = jQuery("<div class='row row-fluid'></div>");
    var basePath     = SIRAH.basePath;
    var confirmImage = jQuery("<div class='column' style='width:25%;'><img src='"+basePath+"images/icones/48x48/icone-dialog-error.png' /></div>");
    var confirmMsg   = jQuery("<div class='column' style='width:72%;'><p class='dialog-alert'> "+message+" </p></div>");
    confirmRow.append(confirmImage);
    confirmRow.append(confirmMsg);   
    jQuery.fn.dialog2.helpers.confirm(confirmRow ,{ title          : title , 
    	                                            confirm        : function(){ confirmHandle(jQuery(this),{});} ,
    	                                            decline        : function(){ declineHandle(jQuery(this),{});} ,
    	                                            buttonLabelYes : 'Oui',
    	                                            buttonLabelNo  : 'Non'
                                                  });
	return;
};

SIRAH.dialog.open    = function(content, options , type) {	
var defaultOptions={autoOpen:false,closeOnOverlayClick:true,removeOnClose:true,showCloseHandle:true,initialLoadText:"Chargement...",
                    closeOnEscape:true, beforeSend: null} ;
	options         = jQuery.extend(true,defaultOptions , options);
	var  dialogBox  = jQuery("<div id='modalBoxDialog' class='modal "+type+"' style=\"display: none;\">"+
                                "<div class='modal-header loading'>"+
                                  "<a href='#' class='close'></a>"+
                                     "<span class='loader'>"+options.title+"</span><h1></h1>"+
                                "</div>"+
                                "<div class='modal-body'>"+content+"</div>"+
                                "<div class='modal-footer'> </div>"+
                                "</div>");    
	dialogBox.dialog2("open" , options);
};

SIRAH.dialog.openUrl = function(url , options) {		
	var defaultOptions = {content : url} ;
	options            = jQuery.extend(true,defaultOptions,options);
	SIRAH.dialog.open("" , options) ;
};

(function($){
	$.fn.btnLoading = function( settings ) {
	  var config = {text: "Patientez...",on:true,icon:"icon-busy"};
      if ( settings ) {$.extend( config , settings);}        
	  var on = config.on,loadingText = config.text, icon=config.icon;	  
	  if( on == true ) {
		  jQuery(document).data("loadingBtnText", this.text() );
		  jQuery(document).data("loadingBtnIcon", this.find(".glyphicon").attr("class") );
		  this.addClass("disabled").attr("disabled","disabled").html(" "+loadingText).prepend(jQuery("<i/> ").addClass(icon));
	  } else if( on == false ) {
		  this.removeClass("disabled").removeAttr("disabled").html(jQuery(document).data("loadingBtnText"));
		  if( !SIRAH.util.isEmpty( jQuery(document).data("loadingBtnIcon") )) {
			  this.prepend(jQuery("<i/>").addClass(jQuery(document).data("loadingBtnIcon")));
		  }
	  }
	 return; 
	};
})(jQuery);
(function($){
	$.fn.loadingOn = function(){
		var spinnerContainer  = jQuery('#spinnerContainer');
		var el                = jQuery(this);
        if( spinnerContainer.attr("id") == undefined ){
			spinnerContainer        = jQuery("<div/>").addClass("spinnerContainer").addClass('enabled').attr('id','spinnerContainer');
			spinnerContainerBox     = jQuery("<div/>").addClass("spinnerBox").addClass('spinner').attr('id','spinnerBox');
			spinnerContainerBounce1 = jQuery("<div/>").addClass("bounce1");
			spinnerContainerBounce2 = jQuery("<div/>").addClass("bounce2");
			spinnerContainerBounce3 = jQuery("<div/>").addClass("bounce2");
			spinnerContainerBox.append(spinnerContainerBounce1).append(spinnerContainerBounce2).append(spinnerContainerBounce3);
			spinnerContainer.append(jQuery("<span/>").html("Patientez...")).append(spinnerContainerBox);
		}
        /*el.addClass('spinnerParentBackdrop');	*/	
		spinnerContainer.prependTo(el);
		spinnerContainer.addClass("enabled").removeClass("disabled");		
	};
	$.fn.loadingOff = function(){
		var spinnerContainer  = jQuery('#spinnerContainer');
		var el                = this;
		spinnerContainer.addClass("disabled").removeClass("enabled");
        jQuery("#maincontent").append(spinnerContainer);
        el.removeClass('spinnerParentBackdrop');			
	};
})(jQuery);

SIRAH.loading     = {};
SIRAH.loading.on  = function( container ){	
	    var el    = container;
		var spinnerContainer  = jQuery('#spinnerContainer');
        if( spinnerContainer.attr("id") == undefined ){
			spinnerContainer        = jQuery("<div/>").addClass("spinnerContainer").addClass('enabled').attr('id','spinnerContainer');
			spinnerContainerBox     = jQuery("<div/>").addClass("spinnerBox").addClass('spinner').attr('id','spinnerBox');
			spinnerContainerBounce1 = jQuery("<div/>").addClass("bounce1");
			spinnerContainerBounce2 = jQuery("<div/>").addClass("bounce2");
			spinnerContainerBounce3 = jQuery("<div/>").addClass("bounce2");
			spinnerContainerBox.append(spinnerContainerBounce1).append(spinnerContainerBounce2).append(spinnerContainerBounce3);
			spinnerContainer.append(jQuery("<span/>").html("Patientez...")).append(spinnerContainerBox);
		}
        /*el.addClass('spinnerParentBackdrop');	*/	
		spinnerContainer.prependTo(el);
		spinnerContainer.addClass("enabled").removeClass("disabled");	    
    return spinnerContainer;
};
SIRAH.loading.off = function(remove) {
	var spinnerContainer  = jQuery('#spinnerContainer');
	spinnerContainer.addClass("disabled").removeClass("enabled");
    jQuery("#maincontent").append(spinnerContainer);		
} ;

(function($){$.fn.iconhide=function(){this.attr("style","color:transparent !important;");};})(jQuery);
(function($){$.fn.iconshow=function(){this.css("color", this.closest("a").css("color"));};})(jQuery);
(function($) {
    $.fn.shorten = function (settings) {   
        var config = {showChars:300,ellipsesText: "...",moreText: "Voir plus",lessText: "Reduire"};
        if (settings) {
            $.extend(config, settings);
        }         
        $(document).off("click", ".morelink");      
        $(document).on({click: function () {
                var $this = $(this);
                if ($this.hasClass("less")) {
                    $this.removeClass("less");
                    $this.html(config.moreText);
                } else {
                    $this.addClass("less");
                    $this.html(config.lessText);
                }
                $this.parent().prev().toggle();
                $this.prev().toggle();
                return false;
            }
        }, ".morelink");
        return this.each(function () {
            var $this = $(this);
            if($this.hasClass("shortened")) return;            
            $this.addClass("shortened");
            var content = $this.html();
            if (content.length > config.showChars) {
                var c = content.substr(0, config.showChars);
                var h = content.substr(config.showChars, content.length - config.showChars);
                var html = c + '<span class="moreellipses">' + config.ellipsesText + ' </span><span class="morecontent"><span>' + h + '</span> <a href="#" class="morelink">' + config.moreText + '</a></span>';
                $this.html(html);
                $(".morecontent span").hide();
            }
        });        
    }; 
 })(jQuery);
var DELAY = 10, clicks = 0, timer = null;
jQuery(document).ready(function(){ 
jQuery('table > tbody > tr > td').on("click",function(){
	clicks++;
	var el  = jQuery(this);
	if( clicks === 1 ) {
		timer = setTimeout(function() {el.closest("tr").find("input").eq(0).iCheck("toggle");clicks = 0;}, DELAY );
	} else {
		clearTimeout(timer);
		el.find("a").eq(0).trigger("click");
		clicks = 0;  
	}	
}).on("dblclick",function(event){event.preventDefault();});
jQuery('table > tbody > tr > td').dblclick();
jQuery('a[href="#"]').click(function(event) {event.preventDefault();});
jQuery('table > tbody > tr > td > input, table > thead > tr > th > input').iCheck({checkboxClass:'icheckbox_minimal-green',radioClass: 'radio_minimal-green'});
jQuery('#checkAll').closest("div").attr("title", "Tout sélectionner").attr("data-toggle","tooltip");
jQuery('input:checkbox').closest("div").eq(0).attr("title", "Sélectionner cet élement").attr("data-toggle","tooltip");
jQuery('#checkAll').on('ifChecked', function(event){
	jQuery('.sirah-ui-table').find('input').iCheck('check');
	jQuery('.sirah-ui-table').find('tbody > tr').addClass('checkedRow').removeClass("unCheckedRow");
	jQuery(this).trigger('checkAll');
});
jQuery('#checkAll').on('ifUnchecked', function(event){
	jQuery('.sirah-ui-table').find('input').iCheck('uncheck');
	jQuery('.sirah-ui-table').find('tbody > tr').addClass('unCheckedRow').removeClass("checkedRow");
	jQuery(this).trigger('unCheckAll');
});
jQuery('.sirah-ui-table').find('input').on("ifChecked",function( event ){
	if( jQuery(this).attr('name') !== "checkAll") {
		jQuery(this).trigger('clickRow');
		jQuery(this).closest('div').closest('td').closest('tr').addClass('checkedRow').removeClass("unCheckedRow");
	}
});
jQuery('.sirah-ui-table').find('input').on("ifUnchecked",function( event ){
	if( jQuery(this).attr('name') !== "checkAll") {
		jQuery(this).trigger('unClickRow');
		jQuery(this).closest('div').closest('td').closest('tr').addClass('unCheckedRow').removeClass("checkedRow");
	}
});
  var scrollTop = jQuery(document).scrollTop();
  if( scrollTop > 0){
	  jQuery('.toolsBar').removeClass('navbar-static-top').addClass('navbar-fixed-top').addClass('fixedToTop').removeClass('navDefaultOffsset');
	  jQuery('.toolsBar').width(jQuery("#page-content").width());
  }
  jQuery(document).scroll(function(e){
    var scrollTop = jQuery(document).scrollTop();
    if(scrollTop > 0){
		jQuery('.toolsBar').width(jQuery("#page-content").width());
        jQuery('.toolsBar').removeClass('navbar-static-top').addClass('navbar-fixed-top').addClass('fixedToTop').removeClass('navDefaultOffsset');
        var checkBoxRow  = (jQuery('.sirah-ui-table').find("th").eq(0).find("div").length >= 0) ? jQuery('.sirah-ui-table').find("th").eq(0).find("div").eq(0) : jQuery("#checkAll");		
        if( jQuery("#checkboxBtn").attr("id") !== undefined) {
			var checkBoxBtn  = jQuery("#checkboxBtn");
		} else {
			var checkBoxBtn  = jQuery("<li></li>").attr("id", "checkboxBtn");
		}		
		checkBoxRow.appendTo(checkBoxBtn);
		checkBoxBtn.prependTo(jQuery(".toolsbar-nav").eq(0));
		jQuery("#checkAll").iCheck({checkboxClass:'icheckbox_minimal-orange',radioClass: 'radio_minimal-orange'});
        jQuery('#checkAll').on('ifChecked', function(event){
	            jQuery('.sirah-ui-table').find('input').iCheck('check');
	            jQuery('.sirah-ui-table').find('tbody > tr').addClass('checkedRow').removeClass("unCheckedRow");
	            jQuery(this).trigger('checkAll');
        });
        jQuery('#checkAll').on('ifUnchecked', function(event){
	         jQuery('.sirah-ui-table').find('input').iCheck('uncheck');
	         jQuery('.sirah-ui-table').find('tbody > tr').addClass('unCheckedRow').removeClass("checkedRow");
	         jQuery(this).trigger('unCheckAll');
        });	
	} else {
        jQuery('.toolsBar').removeClass('fixedToTop').removeClass('navbar-fixed-top').removeClass('navDefaultOffsset').addClass('navbar-static-top');
        if( jQuery('#checkboxBtn').attr("id") !== undefined)  {
			var checkBoxRow  = (jQuery('#checkboxBtn').find("div").attr("class") !== undefined ) ? jQuery('#checkboxBtn').find("div").eq(0) : jQuery("#checkAll");
			jQuery('.sirah-ui-table').find("thead").find("tr").find("th").eq(0).empty();
			checkBoxRow.appendTo(jQuery('.sirah-ui-table').find("thead").find("tr").find("th").eq(0));
			jQuery('#checkboxBtn').empty();			
		}
        jQuery("#checkAll").iCheck({checkboxClass:'icheckbox_minimal-green',radioClass: 'radio_minimal-green'});
        jQuery('#checkAll').on('ifChecked', function(event){
	            jQuery('.sirah-ui-table').find('input').iCheck('check');
	            jQuery('.sirah-ui-table').find('tbody > tr').addClass('checkedRow').removeClass("unCheckedRow");
	            jQuery(this).trigger('checkAll');
        });
        jQuery('#checkAll').on('ifUnchecked', function(event){
	         jQuery('.sirah-ui-table').find('input').iCheck('uncheck');
	         jQuery('.sirah-ui-table').find('tbody > tr').addClass('unCheckedRow').removeClass("checkedRow");
	        jQuery(this).trigger('unCheckAll');
        });
	}
 });
jQuery('a[href="#"]').click(function(event) {event.preventDefault();});

jQuery(document).delegate(".modal", "dialog2.before-open", function() { 
		  var windowWidth   = parseInt(jQuery(window).width());
		  var windowHeight  = parseInt(jQuery(window).height());
		  var elementWidth  = parseInt(jQuery(this).width());
		  var elementHeight = parseInt(jQuery(this).height());
		  var marginLeft    = parseInt(( windowWidth - elementWidth ) / 2 );
		  jQuery(this).css('margin-left', marginLeft);
	   });
      jQuery( window ).resize(function() {});
	  jQuery(".dialogViewInfos").click(function(event){
	       event.preventDefault();
	       var viewLink  = jQuery(this).attr("href");
	       var viewTitle = jQuery(this).attr("title");
	       var dialogViewInfos = jQuery("<div></div>").attr('id','dialogViewInfos').addClass("modal-medium");             		 
	       dialogViewInfos.dialog2({title:viewTitle,content:viewLink,autoOpen:false,modalClass:'modal-wide',removeOnClose:true,closeOnEscape:false,       
		                            closeOnOverlayClick: true, showCloseHandle:true,initialLoadText: 'Chargement...'});        		
	      dialogViewInfos.dialog2('addButton','Fermer',{primary:true,click:function(){jQuery(this).dialog2('close');}});              		
	      dialogViewInfos.dialog2('open');          
	    });
});

function disableButtons(buttons) {
	 jQuery.each(buttons , function(index , value){
		  if(!SIRAH.util.isEmpty(jQuery('#'+value).attr('id'))){
		     jQuery('#'+value).addClass('disabled').addClass('disabledLink').attr('disabled','disabled');
		     return;
		  }
		});
}
function enableButtons(buttons) {
	jQuery.each(buttons , function(index , value){
	  if(!SIRAH.util.isEmpty(jQuery('#'+value).attr('id'))){
	     jQuery('#'+value).removeClass('btn-disabled').removeClass('disabled').removeClass('disabledLink').removeAttr('disabled');
	     return;
	  }
	});
}
function checkall(selector) {
    var  checkboxList  = jQuery("input[type='checkbox']");
    if( selector){
    	checkboxList   = selector.find(jQuery("input[type='checkbox']"));
    }
    checkboxList.each(function(){
    	if(!this.checked) {
    		this.checked = true;
    		jQuery(this).prop("checked", true);
			jQuery.uniform.update( jQuery(this) );
    	}
    });
}
function checknone(selector) {
	var  checkboxList   = jQuery("input[type='checkbox']");
	 if(selector){
	     checkboxList   = selector.find(jQuery("input[type='checkbox']"));
	  }
    checkboxList.each(function(){
    	if(this.checked) {
    		this.checked = false;
    		jQuery(this).prop("checked", false);
			jQuery.uniform.update( jQuery(this) );
    	}
    });
}