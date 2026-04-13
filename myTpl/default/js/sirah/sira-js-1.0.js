//Initialisation les tooltips


var enableBouton=function(){

           $( "input:submit,a.bouton" ).button();  

   };

var putdeleteIcone=function(element,iconePath){

     var icone=$("<img />");
     icone.attr('src',iconePath);
     icone.css('width','12px');
     var span=$('<span></span>');
     span.css('cursor','pointer');
     span.attr('class','cell-ico-suppr');
     span.append(icone);
     element.append(span);

  };

  

   //Cette fonction est obselete, utiliser plutot selectionnerTout
var checkAll=function(object){
   var checkAll  = $(object+' input[name=\'checkAll\']');
   var checkList = $(object+' input:checkbox') ; 
   checkAll.click(function()
   {
       if(this.checked==false){
               this.checked=true;
           }
        else{
              this.checked=false;
          }
       checkList.each(function(){
         if(this.checked==false){
               this.checked=true;
           }
        else{
              this.checked=false;
          }
       });
     });
};




//La fonction modifier est depreciee, c'est preferable d'utiliser cette fonction
var edit=function(){
               var controller=$('input.url').val(); 
               var  url=controller+'/modifier';
               var  form=document.siraForm;
               if(form===undefined || form===null){
                          form=document.createElement('FORM');
                          form.setAttribute('action',url);                          
                }
               if(form.selections===undefined ){
                     siraAlert('impossible de recuperer les elements selectionnes !!','ERREUR');
                     return;
                }
              if(form.selections===0){
                     siraAlert('Aucune ligne n\'a ete selectionnee !!','ERREUR');
                     return;
                }
             var checked=$('.siraForm input:checked');
               if(checked.length > 1){
                  siraAlert('Vous devrez selectionner une seule ligne !!!','ERREUR');
                   return;
                }
             form.submit();   
              
          };


var siraAlert=function(msg,titre)
{
        if(titre===undefined){
             titre=" ERREUR DU SYSTEME ";
         }
         var alertDlg=$("<table><tr></tr></table>");
         var baseUri=getBaseUrl();
         var alerter=$("<td><img src='"+baseUri+"/icones/32px/icone-dialog-alert.png' /></td><td>"+msg+"</td>");
         alertDlg.append(alerter); 
         alertDlg.dialog({
         title:titre,
         modal:true,         
         draggable:true,
         minHeight:80,
         buttons:{
          'Ok':function(){
               $(this).dialog('close');
             }
          }
        });

   };

var siraFillPanel=function(uri,panelid,dataFormat){

         var panelData=null;
         if(null===uri && arguments[3]!==undefined){
                panelData=arguments[3];
             }
          if(null!==uri){
         
           var xhr=$.ajax({
                         url:uri,
                         dataType:dataFormat,
                         async:false,
                         type:'GET',
                         data:{}
                         });
          xhr.success(function(data){
               
                if(data.error!==undefined){
                     siraAlert(data.error);
                     return;
                  }
                   panelData=data;
                });
            }
      $('#'+panelid).html(panelData);

   };


var supprimer=function(el,url,remove,checkList,params){
      var tab=[];
      checkList.each(function(){     
         tab.push($(this).val());
       });        
      var tabString=tab.join(',');
      if(tabString==''){
         var cantDelete=$("<table><tr></tr></table>").css({width:'auto',height:'auto'});
         var baseUri=getBaseUrl();
         var alerter=$("<td><img src='"+baseUri+"/icones/32px/icone-dialog-alert.png' /></td><td> Vous devez selectionner au moins une ligne</td>");
         cantDelete.append(alerter); 
         cantDelete.dialog({
         title:'Impossible d\'effectuer la suppression ',         
         resizable:false,
         draggable:false,
         modal:true,
         minHeight:80,
         buttons:{
          'Ok':function(){
               $(this).dialog('close');
             }
          }
        });
     }
   else{  
//On effectue une requete post
$.post(url,params,
               function(data,textStatus){
                  if(data.error!==undefined){                        
                                 var errorBox=$('<div></div>');
                                 errorBox.css('color','red');
                                 errorBox.append(data.error);
                                 $(this).append(errorBox);
                      }
                   else{
                    //On supprime les differentes listes concernees
                     checkList.each(function(){  
                     var checkId=$(this).val();
                     $('tr').remove(remove+checkId);
                             });
                     el.dialog('close');       
                       }
                     },
                 'json');

      }
  };


var supprimerPhotos=function(url,fileName,path,supprDiv){

var dlgBox=$("<div id='sureDlg'></div>").append("Voulez vous vraiment supprimer cette photo ?");
dlgBox.dialog({
   title:'Etes vous sur de vouloir supprimer la photo ? ',
   modal:true,
   buttons:{
   'Oui':function(){
   loadingContent(dlgBox);
   $.post(url,{'fileName':fileName,'path':path},

               function(data,textStatus){

                 if(data.error!==undefined){
                     siraAlert("Une erreur a été constatée dans cette requete :"+data.error,"Erreur de suppression de photo");

                  }
                else if(data.success!==undefined){
                      supprDiv.remove();
                      loadingDestroy();
                      $('#sureDlg').dialog('destroy');
                      $('#sureDlg').remove();
                 }
               else{
                   siraAlert("Aucune réponse n'a été recue depuis le serveur :","Erreur de suppression de photo");
                 }
       },
    'json'
          );
      },
    'Non':function(){
        $(this).dialog('close');

   }
 }
 });
};
var enableTooltip=function(){

$(".toolTip").each(function(){ 
      $(this).qtip({
      content: $(this).attr("rel"),     
         position: {
            corner: {
               target: 'bottomLeft', 
               tooltip: 'topRight'
            },
            adjust: {
               screen: false
            }
         },        
       show: { 
            when: 'mouseover', 
            solo: true 
         },
       hide:'unfocus',
         style:{
          tip:true,
          border:{
             radius:2,
             width:0
            },
          name:'light'
          }
               }); 

   });
};

var sira_isFloat=function (val) {
    if(!val || (typeof val != "string" || val.constructor != String)) {
      return(false);
    }
    var isNumber = !isNaN(new Number(val));
    if(isNumber) {
      if(val.indexOf('.') != -1) {
        return(true);
      } else {
        return(false);
      }
    } else {
      return(false);
    }
  }
;
var sira_isUnsignedInt=function(s) {
  return (s.toString().search(/^[0-9]+$/) == 0);
};

var sira_isInt=function(n){

     return (n.toString().search(/^-?[0-9]+$/) == 0);

  };


var transfererFichier=function(filelementid,dossier,uploadUrl,fileIntput,fileName,resultId,loader){
if(loader===undefined){

loader=$("<div id='loading'></div>");
$('#'+resultId).append(loader);
}
$.ajaxFileUpload({
				url:uploadUrl,
				secureuri:false,
				fileElementId:filelementid,
				dataType: 'json',
                                ppost:{'dossier':dossier,'fileInputName':fileIntput,'fileName':fileName},
				beforeSend:function(){
					loader.css({'display' : 'block'});
				},
				complete:function()
				{
					loader.css({'display' : 'none'});
				},				
				success: function (data, status)
				{
					if(typeof(data.error) != 'undefined')
					{
						if(data.error != '')
						{
							siraAlert('Une erreur s\'est produite: '+data.error);
						}
                                                 else if(data.warning){
                                                     siraAlert(data.warning,'IMPOSSIBLE');

                                                  }
                                                  else
						{
					    $('#'+resultId).html('<font color=\'green\'>'+data.msg+'</font>');
                                            
						}
					}
				},
				error: function (data, status, e)
				{
					siraAlert('Une erreur s\'est produite: '+e);
				  }
			});

  };


var replaceAll=function(txt,src,rep)
{

   if(sira_isEmptyVal(txt) || sira_isFloat(txt) || sira_isInt(txt))
   {
      return txt;
       }
   offset=txt.toLowerCase().indexOf(src.toLowerCase());
  while(offset!=-1)
  {
    txt=txt.substring(0,offset)+rep+txt.substring(offset+src.length,txt.length);
    offset=txt.indexOf(src,offset+rep.length+1);
     }
   return txt;
  };


var formatVal=function(valeur,format){

         if(sira_isEmptyVal(valeur))
         {
            valeur  = 0;
             }

       switch(format)
       {
          case "int":
                      valeur   = parseInt(valeur);
                      break;
          case "float":
                       valeur   = parseFloat(valeur);
                      break;
             }

         return valeur;
   };

var siraPost=function(){
            var Formulaire=arguments[0];
            var asyncVal=arguments[1];
            var table=arguments[2];
            var updateTable=parseInt(arguments[3]);
            if(updateTable!==0 && updateTable!==1){
               updateTable=1;
             }
            if(asyncVal !=true && asyncVal !=false){
               asyncVal=false;
             }
            if(!table  || table===undefined){
               table=$("#siraTable");
             }
            var oForm={};
            if(typeof(Formulaire)=='object'){
   
              oForm=Formulaire;

            }
             else{
               
                oForm=$(Formulaire);

             }
            var sData="";
            if(!oForm){

             siraAlert(" Impossible de recuperer ce formulaire ","ERREUR");

            }
             else{
                     
                     sData=oForm.serialize();
                     var returnData={};
                     var sUrl=oForm.attr("action");
                     var sType="POST";
                     $("#message-succes").remove();
                $.ajax({
                        type:sType,
                        url:sUrl,
                        data:sData,
                        async:asyncVal,
                        dataType:'json',
						beforeSend:function(){
						
						     loadingContent(oForm);
						},
                        error:function(data){
						         loadingDestroy();
                                 var messageBox=$("<div id='message'></div>");
                                  messageBox.attr("id","message-error");
                                  messageBox.append(data);

                         },
                        success:function(data){
						       loadingDestroy();
                              var messageBox=$("<div id='message'></div>");
                              if(data.error!==undefined){
                                  returnData=null;
                                  messageBox.attr("id","message-error");
                                  messageBox.append(data.error);
                             }
                              else if(data.success!==undefined){
                                  returnData=data;
                                  messageBox.attr("id","message-succes");
                                  messageBox.append(data.success);
                                  var ligne=data.ligne;
                             if(ligne!==undefined && table!==undefined && (updateTable)){
 
                                   table.addRow(ligne);
                                }

                           }
                            else if(data.alerte){
                                   returnData=data;
                                  return data;
                              }                            
                           else{
                               returnData=data;
                               messageBox.append("Aucune erreur valide n'a ete renvoyee par le serveur");

                           }
                         
                          oForm.prepend(messageBox);
                          oForm.find("input:text").each(function(){
                          $(this).val("");

                           });
                           
                         }                      
                      });     
                return returnData;
            }

};
var defineProfilPhoto=function(url,photoName,fullpath,el,fromDiv)
{

     $.post(url,{'photo':photoName},
               function(data,textStatus){
                 if(data.error!==undefined){

                     siraAlert("Une erreur s'est produite","ERREUR");

               }
               else if(data.success!==undefined){
                  fromDiv.remove();
                  el.children("img").eq(0).attr("src",fullpath);
              }
              else{

                  siraAlert(" Aucune reponse n'a ete retournee par le serveur ");
              }
          },
        'json'
   );
};

var addPhotoProfilBlock=function(filename,path,urlSuppr,urlDefine){
var td=$(".sira-liste-photos-profils");
var divphotobloc=$("<div  class='photo-bloc' id='photo-"+filename+"'></div>");
var photomini=path+'mini/'+filename;
var photoblocimg=$('<div></div>').addClass('photo-bloc-img');
var phototools=$("<div class='photo-bloc-tools sira-buttonset'></div>");
var  img=$("<img src='"+photomini+"' >").attr('title','Voir la photo en grand');
var supprLink=$("<a style='font-size:9px;'  class='sira-button ui-state-default ui-corner-all supprimerPhoto'>Supprimer</a>");
var profilLink=$("<a style='font-size:9px;' class='sira-button ui-state-default ui-corner-all profil'>Photo de profil</a>");
supprLink.click(function(){
   supprimerPhotos(urlSuppr,filename,path,divphotobloc);
  });
profilLink.click(function(){
defineProfilPhoto(urlDefine,filename,photomini,$('.myphoto'),divphotobloc);
  });
phototools.append(profilLink);
phototools.append(supprLink);
photoblocimg.append(img);
divphotobloc.append(photoblocimg);
divphotobloc.append(phototools);
td.append(divphotobloc);
};
var loadingContent=function(container){
                                    var baseUri=getBaseUrl();
                                    var loaderSrc=baseUri+"/icones/ajax-loader.gif";
                              if(!container || container===undefined){
                                      container=$("#contenu");
                                 }
                                  var texte=arguments[1];
                                  var textBox=$("<div></div>").css({'display':'inline','width':'auto','padding':0,'height':'auto'});
                                 
                                  var loaderWidth=container.width()+10;
                                  var loaderHeight=container.height()+10;
                                  var containerOffset=container.offset();
                                  var loaderMarginLeft=(containerOffset.left);
                                  var loaderMarginTop=(containerOffset.top);
                                  var loaderBox=$("#loaderBox");
                                                       loaderBox.css({ "position":"absolute",
                                                                       "width":loaderWidth,
                                                                       "height":loaderHeight,
                                                                       "left":loaderMarginLeft,
                                                                       "top":loaderMarginTop,
                                                                       "display":"block",
                                                                       "background":"white",
                                                                       "opacity":0.7
                                                                          });
                                  loaderBox.css({'filter' : 'alpha(opacity=70)'});
                                  var img =$("<img src='"+loaderSrc+"'  />");
                                  img.attr("width",22);
                                  img.css({"margin-top":loaderHeight/2});
                                  loaderBox.html(img);
                                  if(texte!==undefined && (texte!=='')){
                                    textBox.append(texte);
                                    textBox.css({"margin-top":loaderHeight/2});
                                    loaderBox.prepend(textBox);
                                  }
                                   loaderBox.show();
                                  return loaderBox;

 };

var loadingDestroy=function(){

            $('#loaderBox').hide();
            

};

var sira_isEmptyVal=function(mixed_var) {

 
return (
        mixed_var == undefined ||  
        mixed_var === '' ||  
        mixed_var  === null || 
        mixed_var === false || 
        mixed_var.length === 0
       );

} ;

var createMsgBox=function(container,msg,type)
{
      var messageBox=$('<div></div>');
      messageBox.attr('id',type);
      messageBox.html(msg);
      container.prepend(messageBox);
  };

var ajouter=function(url,formId){
    var formulaire=document.getElementById(formId);
    if(!formulaire){
       formulaire=$('<form></form>');
       formulaire.attr('id',formId);
       $('body').append(formulaire);
    }
    formulaire.attr('action',url);
    formulaire.submit();
};

//Cette fonction est obsetlete, preferez la fonction edit
var modifier=function(url,formId,checkName,params){
var checkList=$('#'+formId+' input:checked');
if(checkList.length==0){
    siraAlert("Vous devez selectionner une ligne","ERREUR");
  }
else if(checkList.length > 1){
   siraAlert(" Vous devez selectionner une seule ligne ","ERREUR");
}
if(checkList.length >0  && checkList.length <=1){
var formulaire=document.getElementById('adminForm');
if(!formulaire){
    formulaire=$('<form></form>');
    formulaire.attr('id',formId);
    $('body').append(formulaire);
    }
checkValue=$(checkList[0]).val();
var checkInput=$("<input />");
checkInput.attr('type','hidden');
checkInput.attr('name',checkName);
checkInput.attr('value',checkValue);
formulaire.append(checkInput);

if(params.length>0){
params.each(function(i){
  var paramName=params[i].nom;
  var paramValue=params[i].val;
  var input =$('<input  />');
  input.attr('type','hidden');
  input.attr('name',paramName);
  input.attr('value',paramValue);
  formulaire.append(input);
  });
}

formulaire.attr('action',url);
formulaire.submit();
}
};

$(document).ready(function()
{

      var baseUri=getBaseUrl();
      var alertIcone=baseUri+"/icones/32px/icone-dialog-alert.png";
    $('.dialog').find('input').keypress(function(e) {
	if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
		$(this).parent().parent().parent().parent().find('.ui-dialog-buttonpane').find('button:first').click(); 
		return false;
	  }
       });
	   
   $(window).keypress(function(e) {
    if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
	    $('.ui-dialog-buttonpane:last').find('button:first').hover();
        $('.ui-dialog-buttonpane:last').find('button:first').click();
        return false;
        }
     });


    
      
      $('.suppr').click(function(){
            var form=$('form#siraForm');
            if(form===undefined  || form===null){
                   var box=$("<div></div>") ;
                   box.text(" Impossible d'effectuer cette operation, des donnees sont manquantes ");
                   box.dialog({"title":" Echec "});
                   }
              else{                                   
                   var url=$("input[name='url']").val();
                   if(url===undefined){
                               siraAlert(" L'url de suppression est indefinie ","ERREUR");
                     }
                  var elements=form.find("input[name='id[]']:checked");                 
                  var tab=[];
                    elements.each(function(){     
                        tab.push($(this).val());
                    });        
                 var tabString=tab.join(',');
                 if(tabString==''){
                      siraAlert("Aucune ligne n'a été selectionnée","ERREUR");
                      return;
                  }
                 url=url+"/supprimer/id/"+tabString;
   var confirmBox=$('<table><tr></tr></table>').css({width:'auto',height:'auto'});
   var alerter=$('<td id=\'alert\'><img src="'+alertIcone+'" /></td><td>Etes vous sur d\'effectuer cette action ? Elle supprimera definitivement de la base de données, les elements selectionnés </td>');
      confirmBox.append(alerter);
      confirmBox.dialog({
         title:'Confirmez cette action ',
         modal:true,
         draggable:false,
         resizable:false,
         minHeight:40,
         buttons:{
             'Oui':function(){                   
                   $.get(url,{},
               function(data,textStatus){
                  if(data.error!==undefined){                        
                                 var errorBox=$("<div></div>");
                                 errorBox.css('color','red');
                                 confirmBox.dialog('close');
                                 errorBox.html(data.error);
                                 errorBox.dialog({'title':'Erreur transmise par le serveur',
                                                  'modal':true,
                                                  'buttons':{
                                                          'OK':function(){
                                                                $(this).dialog('close');
                                                                 }
                                                             }
                                                   });
                      }
                   else{
                    //On supprime les differentes listes concernes
                     elements.each(function(){  
                     var checkId=$(this).val();
                         $('tr').remove('#id-'+checkId);
                             });
                     confirmBox.dialog('close'); 
                    }
                  },
                 'json');         
               
              },

             'Non':function(){
              $(this).dialog('close');

             }
           }
        });
                 
                }
            });

$('.ajouter,.enregistrer,.sauvegarder').click(function(){

          $('#siraForm').submit();

  }); 
      $('.annuler').click(function(){

        history.back();

    });
});


