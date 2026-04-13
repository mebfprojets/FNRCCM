// JavaScript Document
SIRAH.MYPROFILE.insertCarreer  = function( row ,lnkView , lnkDelete, deleteIds ) {
         if( SIRAH.util.isUndefined( row.carreerid ) ) {
              return;
         }
         var carreerId   = parseInt(row.carreerid), entreprise = row.entreprise,date = row.date, profession = row.profession;        
         var carreerItem = jQuery("<a></a>").addClass("active").addClass("list-group-item").attr("id","carreerId-"+carreerId);
         var closeSpan   = jQuery("<span id=\"closeId-"+carreerId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemCarreer\"></span>")
                                  .css("cursor","pointer")
                                  .bind("click", function(event){
                                         var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette expérience ? </div>")
												                       .attr("id","dialogDeleteItem").addClass("alertRow");
                                         dialogDeleteItem.dialog2({
             		                                                title : "Confirmer cette action",
             		                                                autoOpen: false,
             		                                                removeOnClose: true,
             		                                                closeOnEscape: false, 
                                                                    closeOnOverlayClick: true,
             		                                                showCloseHandle:true,
             		                                                initialLoadText: "Chargement..." });   
                                       dialogDeleteItem.dialog2("addButton","Oui",{primary:true,click:function(){
             		                                                               jQuery.ajax({
             		                                                                             url      : lnkDelete+'/'+deleteIds+'/'+carreerId,
             		                                                                             async    : false,
			                                                                                     dataType :"json",
             		                                                                             data     : {},
             		                                                                             type     : "GET" ,
             		                                                                             success  : function(data) {
             		                                                                          if( data.error !== undefined) {
                                                                                                  SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                              return;
             		                                                                           } else {
             		                                                                            SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                            jQuery("#carreerId-"+carreerId).remove();
             		                                                                            setTimeout( function() {
             		             		                                                              jQuery("#carreerId-"+carreerId).remove();
             		                                                                                  jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },2000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                      dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click : function(){jQuery(this).dialog2("close");}});                                      dialogDeleteItem.dialog2("open"); });
	   var itemHeading    = jQuery("<h4></h4>").addClass("list-group-item-heading").append( profession );
	   var entrepriseText = jQuery("<p></p>").addClass("list-group-item-text").append( entreprise );
	   var periodeText    = jQuery("<p></p>").addClass("list-group-item-text").append("<small>"+date+"</small>");
	   carreerItem.append( itemHeading ).append(entrepriseText).append( periodeText ).append( closeSpan );
       jQuery(".carreerList").eq(0).prepend( carreerItem );jQuery("#carreerId-0").remove();
    };
SIRAH.MYPROFILE.insertFormation  = function( row, lnkView , lnkDelete , deleteIds ) {
          if( SIRAH.util.isUndefined( row.formationid ) ) {
              return;
         }
         var formationId   = row.formationid, date = row.date,universite= row.universite,intitule = row.intitule;         
         var formationItem = jQuery("<a></a>").addClass("active").addClass("list-group-item").attr("id","formationId-"+formationId);
         var closeSpan     = jQuery("<span id=\"closeId-"+formationId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemFormation\"></div>")
                                                  .css("cursor", "pointer")
                                                  .bind("click", function( event ){
                                                  var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette formation ? </div>")
												                               .attr("id","dialogDeleteItem");
                                                         dialogDeleteItem.dialog2({
             		                                                                title : "Confirmer cette action.",
             		                                                                autoOpen: false,
             		                                                                removeOnClose: true,
             		                                                                closeOnEscape: false, 
                                                                                    closeOnOverlayClick: true,
             		                                                                showCloseHandle:true,
             		                                                                initialLoadText: "Chargement..."            		                       
             		                                                                                       });   
                                                         dialogDeleteItem.dialog2("addButton","Oui",{ primary : true , click : 
             		                                                              function(){
             		                                                                         jQuery.ajax({
             		                                                                                       url      : +'/'+deleteIds+'/'+formationId,
             		                                                                                       async    : false,
			                                                                                               dataType :"json",
             		                                                                                       data     : {},
             		                                                                                       type     : "GET" ,
             		                                                                                       success  : function( data ) {
             		                                                                                               if( data.error !== undefined) {
                                                                                          SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                                                   return;
             		                                                                                                } else {
             		                                                                       SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                       jQuery("#formationId-"+formationId).remove();
             		                                                                                   setTimeout(function(){
             		             		                                                                    jQuery("#formationId-"+formationId).remove();
             		                                                                                        jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },2000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                                 dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click : function(){jQuery(this).dialog2("close");}});                                                 dialogDeleteItem.dialog2("open"); });                                                                        
       var itemHeading    = jQuery("<h4></h4>").addClass("list-group-item-heading").append( row.intitule );
	   var entrepriseText = jQuery("<p></p>").addClass("list-group-item-text").append( universite );
	   var periodeText    = jQuery("<p></p>").addClass("list-group-item-text").append("<small>"+date+"</small>");
	   formationItem.append( itemHeading ).append(entrepriseText).append( periodeText ).append( closeSpan );
       jQuery(".formationList").eq(0).prepend( formationItem );jQuery("#formationId-0").remove();
    };
SIRAH.MYPROFILE.insertProject  = function( row, lnkDelete , deleteIds ) {
          if( SIRAH.util.isUndefined( row.projectid ) ) {
              return;
         }
         var projectId     = row.projectid, date = row.date,entreprise= row.entreprise,theme = row.theme;         
         var projectItem   = jQuery("<a></a>").addClass("active").addClass("list-group-item").attr("id","projectId-"+projectId );
         var closeSpan     = jQuery("<span id=\"closeId-"+projectId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemProject\"></span>")
                                                  .css("cursor", "pointer")
                                                  .bind("click", function( event ){
                                                         var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette formation ? </div>").attr("id","dialogDeleteItem");
                                                         dialogDeleteItem.dialog2({
             		                                                                title : "Confirmer cette action.",
             		                                                                autoOpen: false,
             		                                                                removeOnClose: true,
             		                                                                closeOnEscape: false, 
                                                                                    closeOnOverlayClick: true,
             		                                                                showCloseHandle:true,
             		                                                                initialLoadText: "Chargement..."            		                       
             		                                                                                       });   
                                                         dialogDeleteItem.dialog2("addButton","Oui",{ primary : true , click : 
             		                                                              function(){
             		                                                                         jQuery.ajax({
             		                                                                                       url      : lnkDelete+'/'+deleteIds+'/'+projectId,
             		                                                                                       async    : false,
			                                                                                               dataType :"json",
             		                                                                                       data     : {},
             		                                                                                       type     : "GET" ,
             		                                                                                       success  : function( data ) {
             		                                                                                               if( data.error !== undefined) {
                                                                                   SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                                                   return;
             		                                                                                                } else {
             		                                                                SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                                  jQuery("#projectId-"+projectId).remove();
             		                                                                                   setTimeout(function(){
             		             		                                             jQuery("#projectId-"+projectId).remove();
             		                                                                 jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },2000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                                  dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click:function(){jQuery(this).dialog2("close");}});                                                  dialogDeleteItem.dialog2("open"); });                                                                        
       var itemHeading    = jQuery("<h4></h4>").addClass("list-group-item-heading").append( theme );
	   var entrepriseText = jQuery("<p></p>").addClass("list-group-item-text").append( entreprise );
	   var periodeText    = jQuery("<p></p>").addClass("list-group-item-text").append("<small>"+date+"</small>");
	   projectItem.append( itemHeading ).append(entrepriseText).append( periodeText ).append( closeSpan );
       jQuery(".projectList").eq(0).prepend( projectItem );jQuery("#projectId-0").remove();
    };
SIRAH.MYPROFILE.insertLanguage  = function( row, lnkDelete  , deleteIds) {
          if( SIRAH.util.isUndefined( row.languages ) ) {
              return;
         }
		 var languages  = row.languages;
		 jQuery.each( languages , function( languageId , language ) {
				 var languageItem = jQuery("<a></a>").addClass("active").addClass("list-group-item").attr("id","languageId-"+languageId);
				 var langueLabel  = language.label;
                 var closeSpan    = jQuery("<span id=\"closeId-"+languageId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemLanguage\"></span>")
                                          .css("cursor", "pointer")
                                          .bind("click", function( event ){
                                                         var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette langue ? </div>")
														                             .attr("id","dialogDeleteItem");
                                                         dialogDeleteItem.dialog2({
             		                                                                title : "Confirmer cette action",
             		                                                                autoOpen: false,
             		                                                                removeOnClose: true,

             		                                                                closeOnEscape: false, 
                                                                                    closeOnOverlayClick: true,
             		                                                                showCloseHandle:true,
             		                                                                initialLoadText: "Chargement..."            		                       
             		                                                                                       });   
                                                         dialogDeleteItem.dialog2("addButton","Oui",{ primary : true , click : 
             		                                                              function(){
             		                                                                         jQuery.ajax({
             		                                                                                       url      : lnkDelete+'/'+deleteIds+'/'+languageId,
             		                                                                                       async    : false,
			                                                                                               dataType :"json",
             		                                                                                       data     : {},
             		                                                                                       type     : "GET" ,
             		                                                                                       success  : function( data ) {
             		                                                                                               if( data.error !== undefined) {
                                                                                              SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                                                   return;
             		                                                                                                } else {
             		                                                                          SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                                  jQuery("#languageId-"+languageId).remove();
             		                                                                                   setTimeout(function(){
             		             		                                                                    jQuery("#languageId-"+languageId).remove();
             		                                                                                        jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },2000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                                   dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click : function(){jQuery(this).dialog2("close");}});                                                   dialogDeleteItem.dialog2("open"); });                                                                        
       var itemHeading      = jQuery("<h4></h4>").addClass("list-group-item-heading").append( langueLabel );
	   var levelText        = jQuery("<p></p>").addClass("list-group-item-text").append("Niveau : "+language.niveau);
	   var appreciationText = jQuery("<p></p>").addClass("list-group-item-text").append(language.appreciation);
	   languageItem.append( itemHeading ).append( levelText ).append( appreciationText ).append( closeSpan );
       jQuery(".languageList").eq(0).prepend( languageItem );jQuery("#languageId-0").remove();					   
		 });      
};

SIRAH.MYPROFILE.insertCompetence  = function( row, lnkDelete  ) {
          if( SIRAH.util.isUndefined( row.competences ) ) {
              return;
         }
		 var competences  = row.competences;
		 jQuery.each( competences , function( competenceId , competence ) {
				 var competenceItem   = jQuery("<a></a>").addClass("active").addClass("list-group-item").attr("id","competenceId-"+competenceId);
				 var competenceLabel  = competence.profession;
                 var closeSpan    = jQuery("<span id=\"closeId-"+competenceId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemCompetence\"></span>")
                                          .css("cursor", "pointer")
                                          .bind("click", function( event ){
                                                var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette compétence ? </div>").attr("id","dialogDeleteItem");
                                                dialogDeleteItem.dialog2({
             		                                                       title : "Confirmer cette action",
             		                                                       autoOpen: false,
             		                                                       removeOnClose: true,
             		                                                       closeOnEscape: false, 
                                                                           closeOnOverlayClick: true,
             		                                                       showCloseHandle:true,
             		                                                       initialLoadText: "Chargement..."            		                       
             		                                                                                       });   
                                                dialogDeleteItem.dialog2("addButton","Oui",{ primary : true , click : 
             		                                                     function(){
             		                                                                 jQuery.ajax({
             		                                                                               url      : lnkDelete,
             		                                                                               async    : false,
			                                                                                       dataType :"json",
             		                                                                               data     : {"ids":competenceId},
             		                                                                               type     : "GET" ,
             		                                                                               success  : function( data ) {
             		                                                                                   if( data.error !== undefined) {
                                                                                                           SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                                                   return;
             		                                                                                   } else {
             		                                                                      SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                                  jQuery("#competenceId-"+competenceId).remove();
             		                                                                                   setTimeout(function(){
             		             		                                                                            jQuery("#competenceId-"+competenceId).remove();
             		                                                                                                jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },1000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                                    dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click : function(){jQuery(this).dialog2("close");}});                                                    dialogDeleteItem.dialog2("open"); });                                                                        
       var itemHeading       = jQuery("<h4></h4>").addClass("list-group-item-heading").append( competenceLabel );
	   var levelText         = jQuery("<p></p>").addClass("list-group-item-text").append("Niveau : "+competence.level);
	   var appreciationText  = jQuery("<p></p>").addClass("list-group-item-text").append(competence.appreciation);
	   competenceItem.append( itemHeading ).append( levelText ).append( appreciationText ).append( closeSpan );
       jQuery(".competenceList").eq(0).prepend( languageItem );	jQuery("#competenceId-0").remove();					   
		 });      
};

SIRAH.MYPROFILE.insertCertification = function( row, lnkView , lnkDelete , deleteIds ) {
          if( SIRAH.util.isUndefined( row.certificationid ) ) {
              return;
         }
         var certificationId   = row.certificationid, date = row.date,entreprise= row.entreprise,keyword = row.keyword;         
         var certificationItem = jQuery("<a></a>").addClass("list-group-item").addClass("active").attr("id","certificationId-"+certificationId);
         var closeSpan     = jQuery("<span id=\"closeId-"+certificationId+"\" class=\"glyphicon glyphicon-remove pull-left pull-top closeItemCertification\"></span>")
                                                  .css("cursor", "pointer")
                                                  .bind("click", function( event ){
                                            var dialogDeleteItem = jQuery("<div> Etes-vous sur de supprimer cette certification? </div>").attr("id","dialogDeleteItem");
                                                         dialogDeleteItem.dialog2({
             		                                                                title : "Confirmer cette action.",
             		                                                                autoOpen: false,
             		                                                                removeOnClose: true,
             		                                                                closeOnEscape: false, 
                                                                                    closeOnOverlayClick: true,
             		                                                                showCloseHandle:true,
             		                                                                initialLoadText: "Chargement..."            		                       
             		                                                                                       });   
                                                         dialogDeleteItem.dialog2("addButton","Oui",{ primary : true , click : 
             		                                                              function(){
             		                                                                         jQuery.ajax({
             		                                                                                       url      : lnkDelete+'/'+deleteIds+'/'+certificationId,
             		                                                                                       async    : false,
			                                                                                               dataType :"json",
             		                                                                                       data     : {},
             		                                                                                       type     : "GET" ,
             		                                                                                       success  : function( data ) {
             		                                                                                               if( data.error !== undefined) {
                                                                                               SIRAH.error.raise(jQuery("#dialogDeleteItem") , data.error);
             		                                                                                                   return;
             		                                                                                                } else {
             		                                                                           SIRAH.error.raise(jQuery("#dialogDeleteItem"), data.success , "success");
             		                                                                                  jQuery("#certificationId-"+certificationId).remove();
             		                                                                                   setTimeout(function(){
             		             		                                                       jQuery("#certificationId-"+certificationId).remove();
             		                                                                           jQuery("#dialogDeleteItem").dialog2("close");
             		                                                                                                                        },2000);
             		                                                                                                                    }
             		                                                                                                                }
             		                                                                                                           });     		
                                                                                                                           }}); 
                                                  dialogDeleteItem.dialog2("addButton","Annuler",{primary: false,click : function(){jQuery(this).dialog2("close");}});                                                  dialogDeleteItem.dialog2("open"); });                                                                        
       var itemHeading    = jQuery("<h4></h4>").addClass("list-group-item-heading").append( row.keyword );
	   var entrepriseText = jQuery("<p></p>").addClass("list-group-item-text").append( entreprise );
	   var periodeText    = jQuery("<p></p>").addClass("list-group-item-text").append("<small>"+date+"</small>");
	   certificationItem.append( itemHeading ).append(entrepriseText).append( periodeText ).append( closeSpan );
       jQuery(".certificationList").eq(0).prepend( certificationItem );jQuery("#certificationId-0").remove();
    };
	
SIRAH.MYPROFILE.insertCvDocument = function( document , lnkDelete , deleteIds ) {
	if( SIRAH.util.isUndefined( document.documentid ) ) {
              return;
    }
    jQuery('#documentId-0').remove();
    var documentId   = document.documentid;
    var fileSize     = parseInt( document.size );
    var documentItem = jQuery('<li></li>').addClass('active').addClass('list-group-item').attr('id','documentId-'+documentId);
    var deleteIcon   = jQuery('<span id=\'closeId-'+documentId+'\' title=\'Supprimer le document\' class=\'glyphicon glyphicon-remove closeItemCvdoc \'> </span>')
	                         .addClass('pull-left').addClass('pull-top')
                             .css('cursor', 'pointer')
                             .bind('click', function( event ) {
                                   event.preventDefault();
                                   var dialogDeleteItem  = jQuery('<div> Etes-vous sur de supprimer ce document ? </div>').attr('dialogDeleteItem');
                                   dialogDeleteItem.dialog2({
                                                              title           : 'Confirmer cette action',
                                                              autoOpen        : false,
                                                              removeOnClose   : true,
                                                              closeOnEscape   : false,
                                                              closeOnOverlayClick : true,
                                                              showCloseHandle : true,
                                                              initialLoadText : 'Chargement...' });
                                   dialogDeleteItem.dialog2('addButton', 'Oui',{primary:true,click :
                                                            function( ev ) {
                                                               ev.preventDefault();
                                                               jQuery.ajax({
                                                                             url      : lnkDelete+'/'+deleteIds+'/'+documentId,
                                                                             async    : false,
                                                                             dataType : 'json',
                                                                             data     : {},
                                                                             type     : 'GET',
                                                                             success  : function( data ) {
                                                                                       if ( data.error !== undefined ) {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.error);
                                                                                            return;
                                                                                       } else if( data.success ) {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.success);
                                                                                            jQuery('#documentId-'+documentId).remove();
                                                                                            setTimeout(
                                                                                               function(){
                                                                                                           jQuery('#documentId-'+documentId).remove();
             		                                                                                       jQuery('#dialogDeleteItem').dialog2('close');
                                                                                                                   }, 2000);
                                                                                            return;
                                                                                       } else {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data);
                                                                                            return;
                                                                                       }
                                                                             }                                                                            
                                                               });//Fin de la requete ajax
                                                            }
                                  }); //Fin du bouton Oui
                                  dialogDeleteItem.dialog2('addButton','Annuler',{primary:false,click:function(){jQuery(this).dialog2('close');}});                                                  
                                  dialogDeleteItem.dialog2('open');                    
                            });
       var itemRow         = jQuery('<div/>').addClass('row');
       var documentIcone   = jQuery('<span/>').addClass(document.icone);
       var documentLibelle = jQuery('<div/>').addClass('col-md-9').addClass('documentLibelle').append( " "+document.filename ).prepend( documentIcone );
       var documentSize    = jQuery('<div/>').addClass('col-md-2').addClass('documentSize').append( document.size );
       var documentCloseCol= jQuery('<div/>').addClass('col-md-1').append( deleteIcon );
       
       itemRow.append( documentLibelle ).append( documentSize ).append( documentCloseCol );
       documentItem.append(itemRow);
       jQuery('#cvDocuments').prepend( documentItem );
};
SIRAH.MYPROFILE.insertLetterDocument = function( document , lnkDelete , deleteIds ) {
	if( SIRAH.util.isUndefined( document.documentid ) ) {
              return;
    }
    jQuery('#documentId-0').remove();
    var documentId   = document.documentid;
    var fileSize     = parseInt( document.size );
    var documentItem = jQuery('<li></li>').addClass('active').addClass('list-group-item').attr('id','documentId-'+documentId);
    var deleteIcon   = jQuery('<span id=\'closeId-'+documentId+'\' title=\'Supprimer le document\' class=\'glyphicon glyphicon-remove closeItemLetterdoc \'> </span>')
	                         .addClass('pull-left').addClass('pull-top')
                             .css('cursor', 'pointer')
                             .bind('click', function( event ) {
                                   event.preventDefault();
                                   var dialogDeleteItem  = jQuery('<div> Etes-vous sur de supprimer ce document ? </div>').attr('dialogDeleteItem');
                                   dialogDeleteItem.dialog2({
                                                              title           : 'Confirmer cette action',
                                                              autoOpen        : false,
                                                              removeOnClose   : true,
                                                              closeOnEscape   : false,
                                                              closeOnOverlayClick : true,
                                                              showCloseHandle : true,
                                                              initialLoadText : 'Chargement...' });
                                   dialogDeleteItem.dialog2('addButton', 'Oui',{primary:true,click :
                                                            function( ev ) {
                                                               ev.preventDefault();
                                                               jQuery.ajax({
                                                                             url      : lnkDelete+'/'+deleteIds+'/'+documentId,
                                                                             async    : false,
                                                                             dataType : 'json',
                                                                             data     : {},
                                                                             type     : 'GET',
                                                                             success  : function( data ) {
                                                                                       if ( data.error !== undefined ) {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.error);
                                                                                            return;
                                                                                       } else if( data.success ) {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.success);
                                                                                            jQuery('#documentId-'+documentId).remove();
                                                                                            setTimeout(
                                                                                               function(){
                                                                                                           jQuery('#documentId-'+documentId).remove();
             		                                                                                       jQuery('#dialogDeleteItem').dialog2('close');
                                                                                                                   }, 2000);
                                                                                            return;
                                                                                       } else {
                                                                                            SIRAH.error.raise(jQuery('#dialogDeleteItem') , data);
                                                                                            return;
                                                                                       }
                                                                             }                                                                            
                                                               });//Fin de la requete ajax
                                                            }
                                  }); //Fin du bouton Oui
                                  dialogDeleteItem.dialog2('addButton','Annuler',{primary:false,click:function(){jQuery(this).dialog2('close');}});                                                  
                                  dialogDeleteItem.dialog2('open');                    
                            });
       var itemRow         = jQuery('<div/>').addClass('row');
       var documentIcone   = jQuery('<span/>').addClass(document.icone);
       var documentLibelle = jQuery('<div/>').addClass('col-md-9').addClass('documentLibelle').append( " "+document.filename ).prepend( documentIcone );
       var documentSize    = jQuery('<div/>').addClass('col-md-2').addClass('documentSize').append( document.size );
       var documentCloseCol= jQuery('<div/>').addClass('col-md-1').append( deleteIcon );
       
       itemRow.append( documentLibelle ).append( documentSize ).append( documentCloseCol );
       documentItem.append(itemRow);
       jQuery('#letterDocuments').prepend( documentItem );
};
SIRAH.MYPROFILE.insertTags = function( checkedTags, lnkDelete , deleteId ) {
	  jQuery.each( checkedTags , function( tagId , tagRow ) {
             		var  itemRow  = jQuery('<li></li>').attr('id' , 'tagId-'+tagId );
             		var spanUnlink= jQuery('<span></span>').attr('title', 'Supprimer ce tag')
             		             		                   .attr('id'   , 'removetagId-'+tagId )
             		             		                   .attr('href' , '#general')
             		             		                   .css('cursor', 'pointer')
             		             		                   .addClass('delTag')
             		             		                   .addClass('glyphicon').addClass('glyphicon-remove')
             		             		                   .addClass('pull-right').addClass('pull-top')
             		             		                   .bind('click' , function( event ) {
             		             		                         event.preventDefault();
                                                                 var dialogDeleteItem = jQuery('<div> Etes-vous sur de supprimer ce tag ? </div>').attr('id','dialogDeleteItem');
                                                                 dialogDeleteItem.dialog2({
             		                                                                         title  :'Confirmer cette action',
																							 autoOpen: false,
             		                                                                         removeOnClose : true,
             		                                                                         closeOnEscape : false, 
                                                                                             closeOnOverlayClick: true,
             		                                                                         showCloseHandle    : true,
             		                                                                         initialLoadText    : 'Chargement...' });   
                                                                 dialogDeleteItem.dialog2('addButton','Oui' , { primary : true , 
																						  click : function( ) {
             		                                                                               jQuery.ajax({
             		                                                                                             url      : lnkDelete,
             		                                                                                             async    : false,
			                                                                                                     dataType :'json',
             		                                                                                             data     : {'tags[]' : tagId},
             		                                                                                             type     : 'GET' ,
             		                                                                                             success  : function(data) {
             		                                                                              if( data.error !== undefined ) {
                                                                                                       SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.error);
             		                                                                                   return;
             		                                                                               } else {
             		                                                                                   SIRAH.error.raise(jQuery('#dialogDeleteItem'), data.success , 'success');
             		                                                                                   jQuery('#tagId-'+tagId).remove();
             		                                                                                   setTimeout( function( ) {
																													jQuery('#tagId-'+tagId).remove();
																													jQuery('#dialogDeleteItem').dialog2('close');
																													} , 2500);
             		                                                                                       }
             		                                                                                    }
             		                                                                               });     		
                                                                                            }}); 
                            dialogDeleteItem.dialog2('addButton','Annuler', {primary: false,click : function(){jQuery(this).dialog2('close');}});              		
                            dialogDeleteItem.dialog2('open'); 
             		             		}); //Fin de la suppression du tag
             		       var libelleRow = jQuery('<a></a>').attr("href", "#").append( tagRow.tag ).append( spanUnlink );            		       
             		       itemRow.append( libelleRow );
             		       jQuery('#tagList').append( itemRow );  
		});
};
SIRAH.MYPROFILE.insertDomaines = function( checkedDomaines, lnkDelete , deleteId ) {
	  jQuery.each( checkedDomaines , function( domaineId , domaineRow ) {
             		var  itemRow  = jQuery('<li></li>').attr('id' , 'domaineId-'+domaineId );
             		var unlinkRow = jQuery('<div></div>').addClass('col-md-1' );
             		var spanUnlink= jQuery('<span></span>').attr('title', 'Supprimer ce domaine')
             		             		                   .attr('id'   , 'removedomaineId-'+domaineId )
             		             		                   .attr('href' , '#general')
             		             		                   .css('cursor', 'pointer')
             		             		                   .addClass('delDomaine')
             		             		                   .addClass('glyphicon').addClass('glyphicon-remove')
             		             		                   .addClass('pull-right').addClass('pull-top')
             		             		                   .bind('click' , function( event ) {
             		             		                         event.preventDefault();
                                                 var dialogDeleteItem = jQuery('<div> Etes-vous sur de supprimer ce secteur d\'activités ? </div>').attr('id','dialogDeleteItem');
                                                                 dialogDeleteItem.dialog2({
             		                                                                         title  :'Confirmer cette action',
																							 autoOpen: false,
             		                                                                         removeOnClose : true,
             		                                                                         closeOnEscape : false, 
                                                                                             closeOnOverlayClick: true,
             		                                                                         showCloseHandle    : true,
             		                                                                         initialLoadText    : 'Chargement...' });   
                                                                 dialogDeleteItem.dialog2('addButton','Oui' , { primary : true , 
																						  click : function( ) {
             		                                                                               jQuery.ajax({
             		                                                                                             url      : lnkDelete,
             		                                                                                             async    : false,
			                                                                                                     dataType :'json',
             		                                                                                             data     : {'domaines[]': domaineId},
             		                                                                                             type     : 'GET' ,
             		                                                                                             success  : function(data) {
             		                                                                              if( data.error !== undefined ) {
                                                                                                       SIRAH.error.raise(jQuery('#dialogDeleteItem') , data.error);
             		                                                                                   return;
             		                                                                               } else {
             		                                                                                   SIRAH.error.raise(jQuery('#dialogDeleteItem'), data.success , 'success');
             		                                                                                   jQuery('#domaineId-'+domaineId).remove();
             		                                                                                   setTimeout( function( ) {
																													jQuery('#domaineId-'+domaineId).remove();
																													jQuery('#dialogDeleteItem').dialog2('close');
																													} , 2500);
             		                                                                                       }
             		                                                                                    }
             		                                                                               });     		
                                                                                            }}); 
                           dialogDeleteItem.dialog2('addButton','Annuler', {primary: false,click : function(){jQuery(this).dialog2('close');}});              		
                           dialogDeleteItem.dialog2('open'); 
             		             		}); //Fin de la suppression du domaine
             		       var libelleRow = jQuery('<a></a>').attr("href", "#").append( domaineRow.tag ).append( spanUnlink );            		       
             		       itemRow.append( libelleRow );  
             		       jQuery('#domaineList').append( itemRow );  
		});
};