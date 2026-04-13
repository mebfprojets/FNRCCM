window.SIRAH   || (window.SIRAH={});

SIRAH.basePath         = SIRAH.basePath || (SIRAH.basePath="/myTpl/default/");
SIRAH.debug            = SIRAH.debug || 1;
SIRAH.util             = SIRAH.util || {};
SIRAH.anim             = SIRAH.anim || {};
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
        if(item==array[i]){
            return true;
        }
    }
    return false;
};

SIRAH.util.arrayKeyExists = function(itemKey, array)
{
    return (itemKey in array);
};

SIRAH.util.isset   = function(obj)
{	
	return (SIRAH.util.isUndefined(obj));
};

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

SIRAH.util.isUndefined = function(obj)
{	
	return (typeof obj === "undefined");
};

SIRAH.util.isBoolean = function(obj)
{	
	return (jQuery.type(obj) == "boolean");
};

SIRAH.util.isNumber = function(obj)
{
	return (jQuery.type(obj) == "number");
};

SIRAH.util.isFunction = function( obj ) 
{
    return ( jQuery.type(obj) == "function");
};

SIRAH.util.isArray = function( obj ) 
{
    return (jQuery.type(obj) == "array" );
};

SIRAH.util.isString = function(obj)
{
	return (jQuery.type(obj) == "string" );
};

SIRAH.util.isDate = function(obj)
{	
	return (jQuery.type(obj) == "date" );
};

SIRAH.util.isRegExp = function(obj){	
	return toString.call(obj) === "[object RegExp]";
};

SIRAH.util.isSelectorString = function(obj)
{
	if(SIRAH.util.isString(obj)){
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

SIRAH.util.strtolower = function(str){
	str  = SIRAH.util.toString(str);
	return str.toLowerCase();
};

SIRAH.util.strtoupper = function(str){
	str  = SIRAH.util.toString(str);
	return str.toUpperCase();
};

SIRAH.util.text = function(str,language)
{
	 var strToUpper  =  SIRAH.util.strtoupper(str);
	 language        =  (!SIRAH.util.isEmpty(language)) ? language : SIRAH.languages.default;
	 if(SIRAH.util.arrayKeyExists(strToUpper,SIRAH.languages.string)){
		 if(SIRAH.util.arrayKeyExists(language,SIRAH.languages.string[strToUpper])){
			 return SIRAH.languages.string[strToUpper][language];
		 }
	 }
	 return str;
};
SIRAH.error.raise = function( selector , messages , type )
{
    selector             = SIRAH.util.getSelectorFromString(selector);
    messages             = (null===messages || SIRAH.util.isEmpty(messages)) ? SIRAH.error.stack : messages;    
    var messagesSelector = jQuery("<div> </div>").attr("id","sirah-page-message")
                                                 .addClass("sirah-message")
                                                 .addClass("text-error");
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
			if(SIRAH.util.isString(sKey)){
			   msgType  = SIRAH.util.strtolower(sKey);
			}
			if(SIRAH.util.isUndefined(selector)){
				SIRAH.error.stack.push(sMsg);
			}
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
			
			jQuery.each(errorSelectorClasses,function(sKeyClass,sValueClass)
            {
                msgSelector.addClass(sValueClass);
              });              
           var errorRemoveIcon = jQuery("<i class='glyphicon glyphicon-remove'></i>").addClass("close")
                                                                      .bind("click",function(e)
                                                                       {
                                                                         e.preventDefault();
                                                                         msgSelector.fadeOut("slow");
                                                                        });
           msgSelector.append(errorRemoveIcon)
                      .append("<strong>"+SIRAH.util.strtoupper(SIRAH.util.text(msgType))+"  !! : </strong> "+SIRAH.util.text(sMsg));
                      
           messagesSelector.append(msgSelector);
         });        
    } 
    if(!SIRAH.util.isUndefined(selector.attr("id"))){
    	selector.prepend(messagesSelector); 
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
	 var patientezBox    = jQuery("<div class='control-group' id='patientez'> <strong> Patientez... </strong></div>").css("text-align","center");
	 var defaultData     = formSelector.serialize();
	 var defaultParams   = { data        : defaultData,
			                 contentType : "application/x-www-form-urlencoded",
			                 async       : false,
			                 dataType    :'json',
			                 beforeSend  : function(){
			                	 formSelector.prepend(patientezBox);
                            	 jQuery("#sirah-page-message").fadeOut("fast");
			                 },
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
SIRAH.validators.isNumber         = function(str){
		return /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(str);
};

SIRAH.dialog.open   = function() {
	
};       
SIRAH.dialog.alert   = function(message , title) {	
	if(SIRAH.util.isEmpty(title)){
        title=" ERREUR DU SYSTEME ";
	}
    var alertRow   = jQuery("<div class='row row-fluid'></div>");
    var basePath   = SIRAH.basePath;
    var alertImage = jQuery("<div class='column' style='width:25%;'><img src='"+basePath+"images/icones/48x48/icone-dialog-error.png' /></div>");
    var alertMsg   = jQuery("<div class='column' style='width:72%;'> <p class='dialog-alert'> "+message+" </p></div>");
    alertRow.append(alertImage);
    alertRow.append(alertMsg);   
    jQuery.fn.dialog2.helpers.alert(alertRow , {
    	                                        title:title, 
    	                                        closeOnOverlayClick: false, 
    	                                        ok:false, cancel:false, 
    	                                        buttons:{},
    	                                        close: function() { jQuery(this).dialog2('close'); } });
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
	var defaultOptions = {
                           autoOpen: false, 
                           closeOnOverlayClick: true, 
                           removeOnClose: true, 
                           showCloseHandle: true, 
                           initialLoadText: "Chargement...", 
                           closeOnEscape: true, 
	                       beforeSend: null
                          } ;
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

SIRAH.loading        = {};
SIRAH.loading.on     = function( container ){	
	var basePath     = SIRAH.basePath;
    var loadingImg   = basePath+"/images/ajaxloading.gif";
    if( !container ){
         container       = jQuery("#maincontent");
    }
    var loadingMsg       = arguments[1];
    var MsgBox           = jQuery("<div></div>").css({'width':'auto' , 'padding':0 , 'height':'20px'}).addClass('row');
    var loaderWidth      = parseInt(container.width()+5);
    var loaderHeight     = parseInt(container.height()+5);
    var containerOffset  = container.offset();
    var loaderMarginLeft = parseInt(parseInt(containerOffset.left)+10);
    var loaderMarginTop  = parseInt(parseInt(containerOffset.top)-20);
    var loaderBox        = jQuery("#loadingBox");
    if( !loaderBox ) {
    	 loaderBox       = jQuery("<div/>").addClass("loadingBox").attr('id' , 'loadingBox');
    }
    if( !loaderBox.is(".loadingBox")) {
    	 loaderBox.addClass("loadingBox");
    }
    if( !container.has('div#loadingBox') ) {
    	 container.append( loaderBox );
    } 
    loaderBox.html('');
    var imgBoxTop        = loaderHeight/2;
    if( !SIRAH.util.isEmpty(loadingMsg)){
    	MsgBox.append(loadingMsg);
    	MsgBox.css({"margin-top":loaderHeight/2 , "text-align" : "center" , "margin-left" : 10  });
    	loaderBox.prepend(MsgBox);
    	imgBoxTop  = 15;
     }
    loaderBox.css({"margin-left":loaderMarginLeft+"px"});
    loaderBox.css({"margin-top":loaderMarginTop+"px"});
    loaderBox.width(loaderWidth); loaderBox.height(loaderHeight);
    loaderBox.css({ "position":"absolute" , "background":"white" , "opacity":0.7});
    loaderBox.css({'filter' : 'alpha(opacity=80)'});
    loaderBox.css({"z-index" : "2000" , "display":"block"})
    var img  =  jQuery( "<div id='loaderImg' style='width:25px; height:auto;' class='row'> <img src='"+loadingImg+"'  /> </div> ");
    img.attr("width" , 22).css({"margin-top" : imgBoxTop , "margin-left" : ( loaderWidth/2  + 52) });
    loaderBox.append(img);
    loaderBox.show();
    return loaderBox;
};
SIRAH.loading.off = function(remove) {
	if( false!= remove && true != remove ) {
		remove  = false;
	}
	jQuery('#loadingBox').hide(); 
	jQuery('.loadingBox').hide();
} ;

jQuery(document).ready(function(){
	  jQuery('.dialogViewInfos').click(function(event){
	       event.preventDefault();
	       var viewLink  = jQuery(this).attr('href');
	       var viewTitle = jQuery(this).attr('title');
	       var dialogViewInfos = jQuery('<div></div>').attr('id','dialogViewInfos').addClass('modal-medium');             		 
	       dialogViewInfos.dialog2({
	             		         title : viewTitle,
	             		         content : viewLink ,
	             		         autoOpen: false,
	             		         modalClass:'modal-wide',
	             		         removeOnClose: true,
	             		         closeOnEscape: false, 
	                             closeOnOverlayClick: true,
	             		         showCloseHandle:true,
	             		         initialLoadText: 'Chargement...'            		                       
	             		        });        		
	      dialogViewInfos.dialog2('addButton','Ok' ,   {primary: false,click : function(){jQuery(this).dialog2('close');}});              		
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
    if(selector){
    	checkboxList   = selector.find(jQuery("input[type='checkbox']"));
    }
    checkboxList.each(function(){
    	if(!this.checked) {
    		this.checked = true;
    		jQuery(this).attr("checked","checked");
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
    		jQuery(this).removeAttr("checked","checked");
    	}
    });
}