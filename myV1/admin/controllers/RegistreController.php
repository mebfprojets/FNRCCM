<?php

class Admin_RegistreController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Liste des registres de type Personnes Physiques"  ;
		
		$model              = $this->getModel("registre");
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
		
		$filters            = array("libelle"=> null,"numero" => null, "domaineid" => null, "localiteid", "annee" => 0);		
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
		$this->view->title                   = "Enregistrer un registre de type `Personnes Physiques`";
		
		$model                               = $this->getModel("registre");
		$modelExploitant                     = $this->getModel("exploitant");
		$modelDomaine                        = $this->getModel("domaine");
		$modelLocalite                       = $this->getModel("localite");
		
		$registreDefaultData                 = $model->getEmptyData();
		$exploitantDefaultData               = $modelExploitant->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$defaultData                         = array_merge( $exploitantDefaultData, $registreDefaultData );
		$defaultData["date_year"]            = date("Y");
		$defaultData["date_month"]           = null;
		$defaultData["date_day"]             = null;
		$defaultData["date_naissance_year"]  = null;
		$defaultData["date_naissance_month"] = null;
		$defaultData["date_naissance_day"]   = null;
		$errorMessages                       = array();
		
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$registre_data                   = array_merge( $registreDefaultData  , array_intersect_key( $postData ,  $registreDefaultData   ));
			$exploitant_data                 = array_merge( $exploitantDefaultData, array_intersect_key( $postData ,  $exploitantDefaultData ));
			$me                              = Sirah_Fabric::getUser();
			$userTable                       = $me->getTable();
			$dbAdapter                       = $userTable->getAdapter();
			$prefixName                      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                    =  new Zend_Filter();
			$stringFilter->addFilter(           new Zend_Filter_StringTrim());
			$stringFilter->addFilter(           new Zend_Filter_StripTags());
				
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
			if(!$strNotEmptyValidator->isValid( $exploitant_data["nom"] ) || !$strNotEmptyValidator->isValid( $exploitant_data["prenom"] )  ) {
				$errorMessages[]             = " Veuillez entrer un nom de famille et/ou prénom valide pour l'exploitant";
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
			
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;			
			$registre_data["type"]           = 1;
			$registre_data["category"]       = $stringFilter->filter( $registre_data["category"]     );
			$registre_data["description"]    = $stringFilter->filter( $registre_data["description"]  );
			$registre_data["creatorid"]      = $me->userid;
			$registre_data["creationdate"]   = time();	
			$registre_data["updateduserid"]  = 0;
			$registre_data["updatedate"]     = 0;
			$registre_data["domaineid"]      = intval( $registre_data["domaineid"] ) ;
			
			$docUpload                       = new Zend_File_Transfer();
			$docUpload->addValidator("Count"    , false , 3 );
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->addValidator("Size"     , false , array("max" => "10MB"));
			$docUpload->addValidator("FilesSize", false , array("max" => "10MB"));
			if( !$docUpload->isUploaded("docmini") ) {
				$errorMessages[]             = "Le document formulaire n'a pas été fourni";
			}
			if( !$docUpload->isUploaded("docoriginal")) {
				$errorMessages[]             = "Le document complet n'a pas été fourni";
			}			
			if( !count( $errorMessages  )) {
				if(   $dbAdapter->insert( $prefixName . "rccm_registre", $registre_data) ) {
					  $registreid            = $dbAdapter->lastInsertId();					  
					  //On enregistre les informations de l'exploitant					  
					  $dateNaissanceYear                = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
					  $dateNaissanceMonth               = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
					  $dateNaissanceDay                 = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
					  $dateNaissance                    = sprintf("%04d-%02d-%s", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					  $zendDateNaissance                = ( Zend_Date::isDate( $dateNaissance, "YYYY-MM-dd" ) ) ? new Zend_Date( $dateNaissance, Zend_Date::DATES, "en_US") : null;
					  
					  $exploitant_data["datenaissance"] = ( null != $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::TIMESTAMP) : 0;
					  $exploitant_data["lieunaissance"] = $stringFilter->filter( $exploitant_data["lieunaissance"]  );
					  $exploitant_data["marital_status"]= $stringFilter->filter( $exploitant_data["marital_status"] );
					  $exploitant_data["nom"]           = $stringFilter->filter( $exploitant_data["nom"] );
					  $exploitant_data["prenom"]        = $stringFilter->filter( $exploitant_data["prenom"]  );
					  $exploitant_data["adresse"]       = $stringFilter->filter( $exploitant_data["adresse"] );
					  $exploitant_data["city"]          = 0;
					  $exploitant_data["country"]       = "BF";
					  $exploitant_data["email"]         = "";
					  $exploitant_data["telephone"]     = $stringFilter->filter( $exploitant_data["telephone"] );
					  $exploitant_data["structure"]     = "";
					  $exploitant_data["creatorid"]     = $me->userid;
					  $exploitant_data["creationdate"]  = time();
					  $exploitant_data["updateduserid"] = 0;
					  $exploitant_data["updatedate"]    = 0;
					  
					  if( $dbAdapter->insert( $prefixName . "rccm_registre_exploitants", $exploitant_data ) ) {
					  	  $exploitantid                 = $dbAdapter->lastInsertId();
					  	  if( $dbAdapter->insert( $prefixName . "rccm_registre_physique", array("registreid" => $registreid, "exploitantid" => $exploitantid ))) {
					  	  	
					  	  	   //On essaie d'enregistrer les documents du registre
					  	  	  $modelDocument                  = $this->getModel("document");
					  	  	  $cleanRegistreNumero            = preg_replace("/\s/", "", $registre_data["numero"] );
					  	  	  $miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini";
					  	  	  $orginalDocPathroot             = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
					  	  	  
					  	  	  $documentData                   = array();
					  	  	  $documentData["userid"]         = $me->userid;
					  	  	  $documentData["category"]       = 1;
					  	  	  $documentData["resource"]       = "registrephysique";
					  	  	  $documentData["resourceid"]     = 0;
					  	  	  $documentData["filedescription"]= $registre_data["numero"];
					  	  	  $documentData["filemetadata"]   = "";
					  	  	  $documentData["creationdate"]   = time();
					  	  	  $documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	  	  $searchIvalidStr          = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					  	  	  $replace                  = array ('e','a','i','u','o','n','y','c','-','','-');					  	  	  
					  	  	  $miniDocPathFilename      = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docmini"    , false ));
					  	  	  $originalDocPathFilename  = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docoriginal", false ));
					  	  	  $miniDocPath              = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					  	  	  $originalDocPath          = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;
					  	  	  $docUpload->addFilter("Rename", array("target" => $miniDocPath    , "overwrite" => true), "docmini");
					  	  	  
					  	  	  if( !$docUpload->isUploaded("docmini") ) {
					  	  	  	   $errorMessages[]     = "Le mini document(formulaire) n'a pas été transféré";
					  	  	  } else {
					  	  	  	   $docUpload->receive("docmini");
					  	  	  	   if( $docUpload->isReceived( "docmini") ) {
					  	  	  	   	   $miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
					  	  	  	   	   $miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
					  	  	  	   	   $miniFileSize                     = $docUpload->getFileSize("docmini");
					  	  	  	   	   $miniDocumentData                 = $documentData;
					  	  	  	   	   $miniFilename                     = $registre_data["numero"]."_formulaire";
					  	  	  	   	   $miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename, $me->userid );
					  	  	  	   	   $miniDocumentData["filepath"]     = $miniDocPath ;
					  	  	  	   	   $miniDocumentData["access"]       = 0 ;
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
					  	  	  if( !$docUpload->isUploaded("docoriginal") ) {
					  	  	  	$errorMessages[]     = "Le  document complet n'a pas été transféré";
					  	  	  } else {
					  	  	  	$docUpload->addFilter("Rename", array("target" => $originalDocPath, "overwrite" => true), "docoriginal");
					  	  	  	$docUpload->receive("docoriginal");
					  	  	  	if( $docUpload->isReceived("docoriginal") ) {
					  	  	  		$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
					  	  	  		$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
					  	  	  		$originalFileSize                     = $docUpload->getFileSize("docoriginal");
					  	  	  		$originalDocumentData                 = $documentData;
					  	  	  		$originalFilename                     = $registre_data["numero"]."_complet";
					  	  	  		$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename , $me->userid );
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
					  	  	  		  echo ZendX_JQuery::encodeJson(array("success" => "Les informations du registre de type physique ont été enregistrées avec succès"));
					  	  	  		  exit;
					  	  	  	   }
					  	  	  	      $this->setRedirect("Les informations du registre de type physique ont été enregistrées avec succès", "success" );
					  	  	  	      $this->redirect("admin/registre/infos/id/" . $registreid );					  	  	  	
					  	  	  }					  	  	  					  	  	  					  	  	  					  	  	
					  	  } else {
					  	  	$errorMessages[]= " Les informations du registre ont été partiellement enregistrées, veuillez reprendre l'opération";
					  	  }
					  } else {
					  	$errorMessages[]    = " Les informations de l'exploitant n'ont pas été enregistrées, veuillez reprendre l'opération";
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
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id", 0)));
		
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
		$modelPhysique         = $this->getModel("registrephysique");
		$modelExploitant       = $this->getModel("exploitant");
		$modelDomaine          = $this->getModel("domaine");
		$modelLocalite         = $this->getModel("localite");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$physique              = $modelPhysique->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$physique ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registre/list");
		}
		$domaines            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$exploitant          = $modelExploitant->findRow( $physique->exploitantid , "exploitantid", null , false );		
		$registreData        = $registre->toArray();
		$physiqueData        = $physique->toArray();
		$exploitantData      = $exploitant->toArray();
		$defaultData         = array_merge( $exploitantData, $physiqueData, $registreData );
		$errorMessages       = array();  
		
		$defaultData["date_year"]            = date("Y", $registre->date);
		$defaultData["date_month"]           = date("m", $registre->date);
		$defaultData["date_day"]             = date("d", $registre->date);
		$defaultData["date_naissance_year"]  = date("Y", $exploitant->datenaissance);
		$defaultData["date_naissance_month"] = date("m", $exploitant->datenaissance);
		$defaultData["date_naissance_day"]   = date("d", $exploitant->datenaissance);
		
		if( $this->_request->isPost()) {
			$postData             = $this->_request->getPost();
			$update_registre_data = array_merge( $registreData, array_intersect_key( $postData,  $registreData) );
			$update_physique_data = array_merge( $physiqueData, array_intersect_key( $postData,  $physiqueData) );
			$update_exploitant_data = array_merge($exploitantData, array_intersect_key( $postData,  $exploitantData) );
			$me                   = Sirah_Fabric::getUser();
			$userTable            = $me->getTable();
			$dbAdapter            = $userTable->getAdapter();
			$prefixName           = $userTable->info("namePrefix");
				
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
			if(!$strNotEmptyValidator->isValid( $update_exploitant_data["nom"] ) || !$strNotEmptyValidator->isValid( $update_exploitant_data["prenom"] )  ) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour l'exploitant";
			}
			if( !intval( $update_registre_data["localiteid"] ) || !isset( $localites[$update_registre_data["localiteid"]]) ) {
				$errorMessages[]                     = "Veuillez sélectionner une localité valide";
			} else {
				$update_registre_data["localiteid"]  = intval( $update_registre_data["localiteid"] ) ;
			}
			 
			$dateYear                                = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                               = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                                 = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";
			$dateString                              = sprintf("%04d-%02d-%s", $dateYear, $dateMonth, $dateDay );
			$zendDate                                = ( Zend_Date::isDate( $dateString , "YYYY-MM-dd" ) ) ? new Zend_Date( $dateString, Zend_Date::DATES, "en_US") : null;
				
			$update_registre_data["domaineid"]       = intval( $update_registre_data["domaineid"] ) ;
			$update_registre_data["date"]            = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["type"]            = 1;
			$update_registre_data["description"]     = $stringFilter->filter( $update_registre_data["description"]  );
			$update_registre_data["updateduserid"]   = $me->userid;
			$update_registre_data["updatedate"]      = time();
			
			
			//On enregistre les informations de l'exploitant
			$dateNaissanceYear                       = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
			$dateNaissanceMonth                      = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay                        = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
			$dateNaissance                           = sprintf("%04d-%02d-%s", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$zendDateNaissance                       = ( Zend_Date::isDate( $dateNaissance, "YYYY-MM-dd" ) ) ? new Zend_Date( $dateNaissance, Zend_Date::DATES, "en_US") : null;
				
			$update_exploitant_data["datenaissance"] = ( null != $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::TIMESTAMP) : 0;
			$update_exploitant_data["lieunaissance"] = $stringFilter->filter( $update_exploitant_data["lieunaissance"]  );
			$update_exploitant_data["marital_status"]= $stringFilter->filter( $update_exploitant_data["marital_status"] );
			$update_exploitant_data["nom"]           = $stringFilter->filter( $update_exploitant_data["nom"] );
			$update_exploitant_data["prenom"]        = $stringFilter->filter( $update_exploitant_data["prenom"]  );
			$update_exploitant_data["adresse"]       = $stringFilter->filter( $update_exploitant_data["adresse"] );
			$update_exploitant_data["city"]          = 0;
			$update_exploitant_data["country"]       = "BF";
			$update_exploitant_data["email"]         = "";
			$update_exploitant_data["telephone"]     = $stringFilter->filter( $update_exploitant_data["telephone"] );
			$update_exploitant_data["structure"]     = "";						
			$update_exploitant_data["updateduserid"]= $me->userid;
			$update_exploitant_data["updatedate"]   = time();
			
			$docUpload                              = new Zend_File_Transfer();
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->setOptions(array("ignoreNoFile" => true));
			
			if(isset(  $update_registre_data["registreid"])) {
				unset( $update_registre_data["registreid"] );
			}
			if(isset(  $update_exploitant_data["exploitantid"])) {
				unset( $update_exploitant_data["exploitantid"] );
			}
			 
			$registre->setFromArray(   $update_registre_data );
			$exploitant->setFromArray( $update_exploitant_data );
			if(empty($errorMessages)) {
				if( $registre->save() && $exploitant->save() ) {
					
					//On essaie d'enregistrer les documents du registre
					$modelDocument                  = $this->getModel("document");
					$cleanRegistreNumero            = preg_replace("/\s/", "", $update_registre_data["numero"] );
					$miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini";
					$orginalDocPathroot             = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
					 
					$documentData                   = array();
					$documentData["userid"]         = $me->userid;
					$documentData["category"]       = 1;
					$documentData["resource"]       = "registrephysique";
					$documentData["resourceid"]     = 0;
					$documentData["filedescription"]= $registre_data["numero"];
					$documentData["filemetadata"]   = "";
					$documentData["creationdate"]   = time();
					$documentData["creatoruserid"]  = $me->userid;
					 
					$searchIvalidStr                = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace                        = array ('e','a','i','u','o','n','y','c','-','','-');
					$miniDocPathFilename            = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docmini"    , false ));
					$originalDocPathFilename        = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docoriginal", false ));
					$miniDocPath                    = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					$originalDocPath                = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;					
					$docUpload->addFilter("Rename", array("target"=> $miniDocPath,"overwrite" => true), "docmini");
										 
					if( $docUpload->isUploaded("docmini") ) {						
						$docUpload->receive("docmini");						
						if( $docUpload->isReceived( "docmini") ) {
							$miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
							$miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
							$miniFileSize                     = $docUpload->getFileSize("docmini");
							$miniDocumentData                 = $documentData;
							$miniFilename                     = $cleanRegistreNumero."_formulaire";
							$miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename , $me->userid );
							$miniDocumentData["filepath"]     = $miniDocPath ;
							$miniDocumentData["access"]       = 0;
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
						}  
					}
					$docUpload->addFilter("Rename", array("target"=> $originalDocPath,"overwrite" => true), "docoriginal");
					if( $docUpload->isUploaded("docoriginal") ) {
						$docUpload->receive("docoriginal");
						if( $docUpload->isReceived("docoriginal") ) {
							$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
							$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
							$originalFileSize                     = $docUpload->getFileSize("docoriginal");
							$originalDocumentData                 = $documentData;
							$originalFilename                     = $cleanRegistreNumero."_complet";
							$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename , $me->userid );
							$originalDocumentData["filepath"]     = $originalDocPath;
							$originalDocumentData["access"]       = 6;
							$originalDocumentData["filextension"] = $originalDocExtension;
							$originalDocumentData["filesize"]     = floatval( $originalFileSize );
							
							$dbAdapter->delete( $prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							$dbAdapter->delete( $prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
							
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData) ) {
								$documentid                     = $dbAdapter->lastInsertId();
								$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
							} else {
								$errorMessages[]                = "Les informations du document complet ont été partiellement enregistrées";
							}
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
						$this->redirect("admin/registre/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("admin/registre/infos/id/".$localiteid);
				}
			} else {
				    $defaultData   = array_merge( $update_physique_data, $update_exploitant_data, $update_registre_data, $postData );				
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
		$registreid              = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));				
		$model                   = $this->getModel("registre");
 	
		$registre                = ( $registreid ) ?  $model->findRow($registreid,"registreid", null, false) : null;
		if(!$registre ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registre/list");
		}
		if( $registre->type == 1 ) {
			$this->redirect("admin/registrephysique/infos/registreid/".$registreid);
		} elseif( $registre->type == 2 ) {
			$this->redirect("admin/registremoral/infos/registreid/".$registreid);
		} elseif( $registre->type == 3) {
			$this->redirect("admin/registresuretes/infos/registreid/".$registreid);
		} elseif( $registre->type == 4) {
			$this->redirect("admin/registremodifications/infos/registreid/".$registreid);
		}
		$exploitant                = $modelExploitant->findRow( $physique->exploitantid , "exploitantid", null , false );		
		$registreData              = $registre->toArray();
		$physiqueData              = $physique->toArray();
		$exploitantData            = $exploitant->toArray();
		$defaultData               = array_merge( $exploitantData, $physiqueData, $registreData );
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->registreid    = $registreid;
		$this->view->exploitant    = $exploitant;
		$this->view->domaine       = $registre->findParentRow("Table_Domaines");
		$this->view->localite      = $registre->findParentRow("Table_Localites");
		$this->view->documents     = $registre->documents();
		$this->view->modifications = $registre->modifications();
		$this->view->suretes       = $registre->suretes();
		$this->view->title         = sprintf("Les informations du registre numero %s", $registre->numero);
		$this->view->columns       = array("left");	
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
						$dbAdapter->delete($prefixName."rccm_registre_suretes"      , "registreid=".$id);
						$dbAdapter->delete($prefixName."rccm_registre_modifications", "registreid=".$id);
						$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$id);						
						$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
						$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$id);
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
			$this->redirect("admin/registre/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registre/list");
		}
	}
}