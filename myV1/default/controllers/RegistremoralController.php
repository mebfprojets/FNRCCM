<?php

class RegistremoralController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Liste des registres de type Personnes Morales"  ;
		
		$model              = $this->getModel("registremorale");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		
		$registres          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;		
		
		$filters            = array("libelle"=> null,"numero" => null, "domaineid" => null,"localiteid","annee" => null, "denomination" => null);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$registres             = $model->getList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->domaines  = $modelDomaine->getSelectListe( "Selectionnez un domaine"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title                   = "Enregistrer un registre de type `Personnes Morales`";
		
		$model                               = $this->getModel("registre");
		$modelEntreprise                     = $this->getModel("entreprise");
		$modelDomaine                        = $this->getModel("domaine");
		$modelLocalite                       = $this->getModel("localite");
		
		$registreDefaultData                 = $model->getEmptyData();
		$entrepriseDefaultData               = $modelEntreprise->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$defaultData                         = array_merge( $entrepriseDefaultData, $registreDefaultData );
		$defaultData["date_year"]            = date("Y");
		$defaultData["date_month"]           = null;
		$defaultData["date_day"]             = null;
		$errorMessages                       = array();
		
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$registre_data     = array_merge( $registreDefaultData  , array_intersect_key( $postData ,  $registreDefaultData   ));
			$entreprise_data   = array_merge( $entrepriseDefaultData, array_intersect_key( $postData ,  $entrepriseDefaultData ));
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator           = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
			$numero                         = $stringFilter->filter( $registre_data["numero"]  );
			$libelle                        = $stringFilter->filter( $registre_data["libelle"] );
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]             = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false )) {
				$errorMessages[]             = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]             = " Veuillez entrer un nom commercial valide pour ce registre";
			} else {
				$registre_data["libelle"]    = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $postData["administrateur"] )   ) {
				$errorMessages[]             = " Veuillez entrer nom & prénom du gérant";
			}  
			if( !intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]]) ) {
				$errorMessages[]             = "Veuillez sélectionner une localité valide";
			} else {
				$registre_data["localiteid"] = intval( $registre_data["localiteid"] ) ;
			}			
			$dateYear                        = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                       = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                         = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			$dateString                      = sprintf("%04d-%02d-%s", $dateYear, $dateMonth, $dateDay );
			$zendDate                        = ( Zend_Date::isDate( $dateString , "YYYY-MM-dd" ) ) ? new Zend_Date( $dateString, Zend_Date::DATES, "en_US") : null;
			
			$registre_data["domaineid"]      = intval( $registre_data["domaineid"] ) ;
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;			
			$registre_data["type"]           = 2;
			$registre_data["category"]       = $stringFilter->filter( $registre_data["category"]     );
			$registre_data["description"]    = $stringFilter->filter( $registre_data["description"]  );
			$registre_data["creatorid"]      = $me->userid;
			$registre_data["creationdate"]   = time();	
			$registre_data["updateduserid"]  = 0;
			$registre_data["updatedate"]     = 0;
			
			$docUpload                       = new Zend_File_Transfer();
			$docUpload->addValidator("Count"    , false , 2 );
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->addValidator("Size"     , false , array("max" => "10MB"));
			$docUpload->addValidator("FilesSize", false , array("max" => "10MB"));
			if( !$docUpload->isUploaded("doc_mini") ) {
				$errorMessages[]             = "Le document formulaire n'a pas été fourni";
			}
			if( !$docUpload->isUploaded("doc_original")) {
				$errorMessages[]             = "Le document complet n'a pas été fourni";
			}			
			if( !count( $errorMessages  )) {
				if($dbAdapter->insert( $prefixName . "rccm_registre", $registre_data) ) {
				   $registreid                 = $dbAdapter->lastInsertId();					  
					  //On enregistre les informations de l'entreprise	
				   $entreprise_data["libelle"]        = $stringFilter->filter($registre_data["libelle"]);
				   $entreprise_data["adresse"]       = $stringFilter->filter($entreprise_data["adresse"]);
				   $entreprise_data["email"]          = $stringFilter->filter($entreprise_data["email"])	;
				   $entreprise_data["phone1"]         = $stringFilter->filter($entreprise_data["phone1"])	;
				   $entreprise_data["phone2"]         = "";
				   $entreprise_data["siteweb"]        = "";
				   $entreprise_data["country"]        = "";
				   $entreprise_data["zip"]            = "";
				   $entreprise_data["city"]           = 0;
				   if( !empty($registre_data["numero"] ) ) {
				   	   $pageKey                       = preg_replace('/\s+/', '-', strtolower( $registre_data["numero"] ) );
				   	   $entreprise_data["pagekey"]    = $pageKey;
				   }				   				   
				   $entreprise_data["responsable"]    = ( isset( $postData["administrateur"] ))? $stringFilter->filter( $postData["administrateur"]) : "";
				   $entreprise_data["capital"]        = ( isset( $postData["capital"]        ))? floatval( $postData["capital"] ) : 0;
				   $entreprise_data["nbemployes_min"] = ( isset( $postData["nbemployes_min"] ))? intval( $postData["nbemployes_min"]) : 0;
				   $entreprise_data["nbemployes_max"] = ( isset( $postData["nbemployes_max"] ))? intval( $postData["nbemployes_max"]) : 0;
				   $entreprise_data["datecreation"]   = $registre_data["date"];
				   $entreprise_data["presentation"]   = "";
				   $entreprise_data["region"]         = 0;
				   $entreprise_data["groupid"]        = 1;
				   $entreprise_data["responsableid"]  = 0;
				   $entreprise_data["reference"]      = $registre_data["numero"];
				   $entreprise_data["creatorid"]      = $me->userid;
				   $entreprise_data["creationdate"]   = time();
				   $entreprise_data["updateduserid"]  = 0;
				   $entreprise_data["updatedate"]     = 0;				   
					  
				   if(    $dbAdapter->insert( $prefixName . "rccm_registre_entreprises", $entreprise_data ) ) {
					  	  $entrepriseid               = $dbAdapter->lastInsertId();
					  	  if( $dbAdapter->insert( $prefixName . "rccm_registre_moral",array("registreid" => $registreid,"entrepriseid" => $entrepriseid,"administrateur" => $entreprise_data["responsable"] ))) {
					  	  	
					  	  	   //On essaie d'enregistrer les documents du registre
					  	  	  $modelDocument                  = $this->getModel("document");
					  	  	  $cleanRegistreNumero            = preg_replace("/\s/", "", $registre_data["numero"] );
					  	  	  $miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "mini";
					  	  	  $orginalDocPathroot             = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "original";
					  	  	  
					  	  	  $documentData                   = array();
					  	  	  $documentData["userid"]         = $me->userid;
					  	  	  $documentData["category"]       = 2;
					  	  	  $documentData["access"]         = 0;
					  	  	  $documentData["resource"]       = "registremoral";
					  	  	  $documentData["resourceid"]     = 0;
					  	  	  $documentData["filedescription"]= $registre_data["numero"];
					  	  	  $documentData["filemetadata"]   = "";
					  	  	  $documentData["creationdate"]   = time();
					  	  	  $documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	  	  $searchIvalidStr          = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					  	  	  $replace                  = array ('e','a','i','u','o','n','y','c','-','','-');					  	  	  
					  	  	  $miniDocPathFilename      = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("doc_mini"    , false ));
					  	  	  $originalDocPathFilename  = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("doc_original", false ));
					  	  	  $miniDocPath              = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					  	  	  $originalDocPath          = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;
					  	  	  $docUpload->addFilter("Rename", array("target" => $miniDocPath    , "overwrite" => true), "doc_mini");					  	  	  
					  	  	  
					  	  	  if( !$docUpload->isUploaded("doc_mini") ) {
					  	  	  	   $errorMessages[]     = "Le mini document(formulaire) n'a pas été transféré";
					  	  	  } else {
					  	  	  	   $docUpload->receive("doc_mini");
					  	  	  	   if( $docUpload->isReceived( "doc_mini") ) {
					  	  	  	   	   $miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
					  	  	  	   	   $miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
					  	  	  	   	   $miniFileSize                     = $docUpload->getFileSize("doc_mini");
					  	  	  	   	   $miniDocumentData                 = $documentData;
					  	  	  	   	   $miniDocumentData["filename"]     = $modelDocument->rename( $registre_data["numero"]."_mini", $me->userid );
					  	  	  	   	   $miniDocumentData["filepath"]     = $miniDocPath ;
					  	  	  	   	   $miniDocumentData["filextension"] = $miniDocExtension;
					  	  	  	   	   $miniDocumentData["filesize"]     = floatval( $miniFileSize );
					  	  	  	   	   if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
					  	  	  	   	   	   $documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	   	   $dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 0 ));
					  	  	  	   	   } else {
					  	  	  	   	   	   $errorMessages[]              = "Les informations du mini document ont été partiellement enregistrées";
					  	  	  	   	   }					  	  	  	   	
					  	  	  	   } else {
					  	  	  	   	   $errorMessages[] = "Le mini document(formulaire) n'a pas été reçu par le serveur";
					  	  	  	   }
					  	  	  }	
					  	  	  if( !$docUpload->isUploaded("doc_original") ) {
					  	  	  	$errorMessages[]     = "Le  document complet n'a pas été transféré";
					  	  	  } else {
					  	  	  	$docUpload->addFilter("Rename", array("target" => $originalDocPath, "overwrite" => true), "doc_original");
					  	  	  	$docUpload->receive("doc_original");
					  	  	  	if( $docUpload->isReceived("doc_original") ) {
					  	  	  		$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
					  	  	  		$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
					  	  	  		$originalFileSize                     = $docUpload->getFileSize("doc_original");
					  	  	  		$originalDocumentData                 = $documentData;
					  	  	  		$originalDocumentData["filename"]     = $modelDocument->rename( $registre_data["numero"]."_complet", $me->userid );
					  	  	  		$originalDocumentData["filepath"]     = $originalDocPath;
					  	  	  		$originalDocumentData["access"]       = 6;
					  	  	  		$originalDocumentData["filextension"] = $originalDocExtension;
					  	  	  		$originalDocumentData["filesize"]     = floatval( $originalFileSize );
					  	  	  		if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData) ) {
					  	  	  			$documentid                       = $dbAdapter->lastInsertId();
					  	  	  			$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
					  	  	  		} else {
					  	  	  			$errorMessages[]                  = "Les informations du document complet ont été partiellement enregistrées";
					  	  	  		}					  	  	  	
					  	  	  	} else {
					  	  	  		    $errorMessages[]                  = "Le document complet n'a pas été reçu par le serveur";
					  	  	  	}
					  	  	  }				  	  	  
					  	  	  if( !count( $errorMessages ) ) {
					  	  	  	  if( $this->_request->isXmlHttpRequest() ) {
					  	  	  		  $this->_helper->viewRenderer->setNoRender(true);
					  	  	  		  $this->_helper->layout->disableLayout(true);
					  	  	  		  echo ZendX_JQuery::encodeJson(array("success" => "Les informations du registre de type moral ont été enregistrées avec succès"));
					  	  	  		  exit;
					  	  	  	   }
					  	  	  	      $this->setRedirect("Les informations du registre de type moral ont été enregistrées avec succès", "success" );
					  	  	  	      $this->redirect("registremoral/infos/id/" . $registreid );					  	  	  	
					  	  	  }					  	  	  					  	  	  					  	  	  					  	  	
					  	  } else {
					  	  	$errorMessages[]= " Les informations du registre ont été partiellement enregistrées, veuillez reprendre l'opération";
					  	  }
					  } else {
					  	$errorMessages[]    = " Les informations de l'entreprise n'ont pas été enregistrées, veuillez reprendre l'opération";
					  }					  					  					 					
				}  else {
					    $errorMessages[]    = " Les informations du registre n'ont pas été enregistrées, veuillez reprendre l'opération";
				}
			} 
			            $defaultData        = array_merge( $defaultData , $postData );
		}		
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data      = $defaultData;
		$this->view->domaines  = $domaines;
		$this->view->localites = $localites;
	}
	
	
	public function editAction()
	{
		$this->view->title = " Mettre à jour les informations du registre ";
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));
		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model               = $this->getModel("registre");
		$modelMorale         = $this->getModel("registremorale");
		$modelEntreprise     = $this->getModel("entreprise");
		$modelDomaine        = $this->getModel("domaine");
		$modelLocalite       = $this->getModel("localite");
 	
		$registre            = $model->findRow( $registreid, "registreid" , null , false);
		$moral               = $modelMorale->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$moral ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registremoral/list");
		}
		$domaines            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$entreprise          = $modelEntreprise->findRow( $moral->entrepriseid , "entrepriseid", null , false );		
		$registreData        = $registre->toArray();
		$moralData           = $moral->toArray();
		$entrepriseData      = $entreprise->toArray();
		$defaultData         = array_merge( $entrepriseData, $moralData, $registreData );
		$errorMessages       = array();  
		
		$defaultData["date_year"]   = date("Y", $registre->date);
		$defaultData["date_month"]  = date("m", $registre->date);
		$defaultData["date_day"]    = date("d", $registre->date);		
		if( $this->_request->isPost()) {
			$postData               = $this->_request->getPost();
			$update_registre_data   = array_merge( $registreData, array_intersect_key(  $postData,  $registreData) );
			$update_entreprise_data = array_merge($entrepriseData, array_intersect_key( $postData,  $entrepriseData) );
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$numero               = $stringFilter->filter( $update_registre_data["numero"]  );
			$libelle              = $stringFilter->filter( $update_registre_data["libelle"] );
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]                     = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false ) && ( $registre->numero != $numero ) ) {
				$errorMessages[]                     = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$update_registre_data["numero"]      = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                     = " Veuillez entrer un nom commercial valide pour ce registre";
			} else {
				$update_registre_data["libelle"]     = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $update_entreprise_data["nom"] ) || !$strNotEmptyValidator->isValid( $update_entreprise_data["prenom"] )  ) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour l'entreprise";
			}
			if( !intval( $update_registre_data["localiteid"] ) || !isset( $localites[$update_registre_data["localiteid"]]) ) {
				$errorMessages[]                     = "Veuillez sélectionner une localité valide";
			} else {
				$update_registre_data["localiteid"]  = intval( $update_registre_data["localiteid"] ) ;
			}
			if( !intval( $update_registre_data["domaineid"] ) || !isset( $domaines[$update_registre_data["domaineid"]]) ) {
				$errorMessages[]                     = "Veuillez sélectionner un secteur d'activité valide";
			} else {
				$update_registre_data["domaineid"]   = intval( $update_registre_data["domaineid"] ) ;
			}
			$dateYear                                = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                               = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                                 = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";
			$dateString                              = sprintf("%04d-%02d-%s", $dateYear, $dateMonth, $dateDay );
			$zendDate                                = ( Zend_Date::isDate( $dateString , "YYYY-MM-dd" ) ) ? new Zend_Date( $dateString, Zend_Date::DATES, "en_US") : null;
				
			$update_registre_data["date"]            = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["type"]            = 1;
			$update_registre_data["description"]     = $stringFilter->filter( $update_registre_data["description"]  );
			$update_registre_data["updateduserid"]   = $me->userid;
			$update_registre_data["updatedate"]      = time();
			
			
			//On enregistre les informations de l'entreprise
			$update_entreprise_data["libelle"]       = $stringFilter->filter( $update_registre_data["libelle"]);
			$update_entreprise_data["adresse"]       = $stringFilter->filter( $update_entreprise_data["adresse"]);
			$update_entreprise_data["phone1"]        = $stringFilter->filter( $update_entreprise_data["phone1"])	;				   				   
			$update_entreprise_data["responsable"]   = (isset($postData["administrateur"] ))? $stringFilter->filter( $postData["administrateur"]):$update_entreprise_data["responsable"];
			$update_entreprise_data["capital"]       = (isset($postData["capital"]        ))? floatval($postData["capital"] ) : $update_entreprise_data["capital"];
			$update_entreprise_data["nbemployes_min"]= (isset($postData["nbemployes_min"] ))? intval(  $postData["nbemployes_min"]) : $update_entreprise_data["nbemployes_min"];
			$update_entreprise_data["nbemployes_max"]= (isset($postData["nbemployes_max"] ))? intval(  $postData["nbemployes_max"]) : $update_entreprise_data["nbemployes_max"];
			$update_entreprise_data["datecreation"]  = $update_registre_data["date"];						
			$update_entreprise_data["updateduserid"] = $me->userid;
			$update_entreprise_data["updatedate"]    = time();
			
			$docUpload                               = new Zend_File_Transfer();
			$docUpload->addValidator("Count"    , false , 2 );
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->addValidator("Size"     , false , array("max" => "10MB"));
			$docUpload->addValidator("FilesSize", false , array("max" => "10MB"));
			if(!$docUpload->isUploaded("doc_mini") ) {
				$errorMessages[]             = "Le document formulaire n'a pas été fourni";
			}
			if(!$docUpload->isUploaded("doc_original")) {
				$errorMessages[]             = "Le document complet n'a pas été fourni";
			}			 
			$registre->setFromArray(   $update_registre_data );
			$entreprise->setFromArray( $update_entreprise_data );
			if(empty($errorMessages)) {
				if( $registre->save() && $entreprise->save() ) {
					
					//On essaie d'enregistrer les documents du registre
					$modelDocument                  = $this->getModel("document");
					$cleanRegistreNumero            = preg_replace("/\s/", "", $update_registre_data["numero"] );
					$miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "mini";
					  	  	  $orginalDocPathroot   = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "original";
					 
					$documentData                   = array();
					$documentData["userid"]         = $me->userid;
					$documentData["category"]       = 1;
					$documentData["resource"]       = "registremorale";
					$documentData["resourceid"]     = 0;
					$documentData["access"]         = 0;
					$documentData["filedescription"]= $registre_data["numero"];
					$documentData["filemetadata"]   = "";
					$documentData["creationdate"]   = time();
					$documentData["creatoruserid"]  = $me->userid;
					 
					$searchIvalidStr                = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace                        = array ('e','a','i','u','o','n','y','c','-','','-');
					$miniDocPathFilename            = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("doc_mini"    , false ));
					$originalDocPathFilename        = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("doc_original", false ));
					$miniDocPath                    = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					$originalDocPath                = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;
					$docUpload->addFilter("Rename", array("target" => $miniDocPath    , "overwrite" => true), "doc_mini");										 
					if( !$docUpload->isUploaded("doc_mini") ) {
						$errorMessages[]     = "Le mini document(formulaire) n'a pas été transféré";
					} else {
						$docUpload->receive("doc_mini");
						if( $docUpload->isReceived( "doc_mini") ) {
							$miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
							$miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
							$miniFileSize                     = $docUpload->getFileSize("doc_mini");
							$miniDocumentData                 = $documentData;
							$miniDocumentData["filename"]     = $modelDocument->rename( $registre_data["numero"]."_mini", $me->userid );
							$miniDocumentData["filepath"]     = $miniDocPath ;
							$miniDocumentData["filextension"] = $miniDocExtension;
							$miniDocumentData["filesize"]     = floatval( $miniFileSize );
							
							$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
							$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
							
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
								$documentid                   = $dbAdapter->lastInsertId();
								$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 0));
							} else {
								$errorMessages[]              = "Les informations du mini document ont été partiellement enregistrées";
							}
						} else {
							$errorMessages[] = "Le mini document(formulaire) n'a pas été reçu par le serveur";
						}
					}
					if( !$docUpload->isUploaded("doc_original") ) {
						$errorMessages[]     = "Le  document complet n'a pas été transféré";
					} else {
						$docUpload->addFilter("Rename", array("target" => $originalDocPath, "overwrite" => true), "doc_original");
						$docUpload->receive("doc_original");
						if( $docUpload->isReceived("doc_original") ) {
							$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
							$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
							$originalFileSize                     = $docUpload->getFileSize("doc_original");
							$originalDocumentData                 = $documentData;
							$originalDocumentData["filename"]     = $modelDocument->rename( $registre_data["numero"]."_complet", $me->userid );
							$originalDocumentData["filepath"]     = $originalDocPath;
							$originalDocumentData["access"]       = 6;
							$originalDocumentData["filextension"] = $originalDocExtension;
							$originalDocumentData["filesize"]     = floatval( $originalFileSize );
							
							$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
							
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData) ) {
								$documentid                     = $dbAdapter->lastInsertId();
								$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
							} else {
								$errorMessages[]                = "Les informations du document complet ont été partiellement enregistrées";
							}
						} else {
							    $errorMessages[]                = "Le document complet n'a pas été reçu par le serveur";
						}
					}					
					if( !count( $errorMessages ) ) {
						if( $this->_request->isXmlHttpRequest()) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							$jsonErrorArray            = $update_data;
							$jsonErrorArray["success"] = "Les informations du registre ont été mises à jour avec succès";
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect("Les informations du registre ont été mises à jour avec succès", "success" );
						$this->redirect("registremoral/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("registremoral/list" );
				}
			} else {
				    $defaultData   = array_merge( $update_moral_data, $update_entreprise_data, $update_registre_data );				
			}					
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data        = $defaultData;
		$this->view->localiteid  = $localiteid;
		$this->view->domaines    = $domaines;
		$this->view->localites   = $localites;
	}	
 		
		
	public function infosAction()
	{		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");
		$modelMorale           = $this->getModel("registremorale");
		$modelEntreprise       = $this->getModel("entreprise");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$moral                 = $modelMorale->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$moral ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registremoral/list");
		}
		$entreprise            = $modelEntreprise->findRow( $moral->entrepriseid , "entrepriseid", null , false );		
		$registreData          = $registre->toArray();
		$moralData             = $moral->toArray();
		$entrepriseData        = $entreprise->toArray();
		$defaultData           = array_merge( $entrepriseData, $registreData );
		$this->view->data      = $defaultData;
		$this->view->registre  = $registre;
		$this->view->registreid= $registreid;
		$this->view->entreprise= $entreprise;
		$this->view->domaine   = $registre->findParentRow("Table_Domaines");
		$this->view->localite  = $registre->findParentRow("Table_Localites");
		$this->view->documents = $registre->documents();
		$this->view->title     = sprintf("Les informations du registre numero %s", $registre->numero);
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("registre");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$registre                 = $model->findRow( $id , "registreid" , null , false );
				if( $registre  ) {
					if(!$registre->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée : Le registre id#$id n'a pas été supprimé";
					} else {
						$dbAdapter->delete($prefixName."rccm_registre_moral"      , array("registreid=".$id ));						
						$dbAdapter->delete($prefixName."rccm_registre_entreprises", "entrepriseid IN (SELECT entrepriseid FROM ".$prefixName."rccm_registre_moral     WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"   , "documentid   IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
						$dbAdapter->delete($prefixName."rccm_registre_documents"  , array("registreid=".$id ));
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le registre #$id ";
				}
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("registremoral/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("registremoral/list");
		}
	}
}