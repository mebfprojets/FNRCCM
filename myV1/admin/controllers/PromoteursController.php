<?php

class Admin_PromoteursController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{		
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}					
		$this->view->title        = "Gestion des promoteurs";
		$promoteurs               = array();
		$promoteursListePaginator = null;
		$model                    = $this->getModel("promoteur");
		$modelIdentiteType        = $this->getModel("usageridentitetype");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]         ))? intval($params["page"])          : 1;
		$pageSize                 = (isset($params["maxitems"]     ))? intval($params["maxitems"])      : NB_ELEMENTS_PAGE;	
		$generalFilter            = (isset($params["generalfilter"]))? $stringFilter->filter($params["generalfilter"]) : (isset($params["searchq"])?$stringFilter->filter($params["searchq"]) : "");
		$filters                  = array("searchQ"=>$generalFilter,"lastname"=>null,"firstname"=>null,"name"=>null,"email"=> null,"telephone"=> null,"numidentite"=>null,"identitetypeid"=>0);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue ) {
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( empty( $filters["name"]) && (!empty($filters["lastname"] ) || !empty( $filters["firstname"] ))) {
			$filters["name"]         = trim(sprintf("%s %s", $filters["lastname"],$filters["firstname"]));
		}	
        //print_r($filters); die();		
		$promoteurs                  = $model->getList($filters , $pageNum , $pageSize );
		$promoteursListePaginator    = $model->getListPaginator($filters);
	
		if(null !== $promoteursListePaginator ) {
			$promoteursListePaginator->setCurrentPageNumber($pageNum);
			$promoteursListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->promoteurs      = $promoteurs;
		$this->view->filters         = $filters;
		$this->view->pageNum         = $pageNum;
		$this->view->pageSize        = $pageSize;
		$this->view->paginator       = $promoteursListePaginator;
		$this->view->identiteTypes   = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		$this->view->maxitems        = $pageSize;
		$this->view->columns         = array("left");
		
	}
	
		
	public function createAction()
	{
		$this->view->title                        = "Enregistrer un nouveau promoteur";
		$model                                    = $this->getModel("promoteur");
		$modelIdentite                            = $this->getModel("usageridentite");
		$modelIdentiteType                        = $this->getModel("usageridentitetype");
		$modelCountry                             = $this->getModel("country");
		
		$promoteurDefaultData                     = $model->getEmptyData();
		$promoteurDefaultData["country"]          = $country           = $promoteurDefaultData["nationalite"] = $this->_getParam("country", "BF");
		$promoteurDefaultData["identitetype"]     = $identitetypeid    = intval($this->_getParam("typeidentity",$this->_getParam("identite", 0)));
		
		$errorMessages            = $promoteurData= $pieceIdentityData = array();
		
		
		$this->view->countries    = $countries    = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes= $identiteTypes= $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );	
		
		if( $this->_request->isPost() ) {			
			$postData                             = $this->_request->getPost();
			$defaultData                          = $model->getEmptyData();
			$defaultIdentityData                  = $modelIdentite->getEmptyData();
			$promoteurFormData                    = array_intersect_key( $postData  , $defaultData );
			$promoteurData                        = $insert_data = array_merge( $defaultData,  $promoteurFormData   );
			$pieceIdentityData                    = array_merge($defaultIdentityData, array_intersect_key($postData,$defaultIdentityData));
			
			$me                                   = Sirah_Fabric::getUser();
			$modelTable                           = $model->getTable();
			$dbAdapter                            = $modelTable->getAdapter();
			$prefixName                           = $modelTable->info("namePrefix");
			$tableName                            = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                       = new Zend_Filter();
			$stringFilter->addFilter(             new Zend_Filter_StringTrim());
			$stringFilter->addFilter(             new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator               = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator                     = new Sirah_Validateur_Email();

            $promoteurData["name"]              = (isset( $postData["name"]        ))? $stringFilter->filter($postData["name"])              : "";
			$promoteurData["country"]           = (isset( $postData["country"]     ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : "BF");
            $countryCallingCode                 = (!empty($promoteurData["country"]))? $modelCountry->callingCode($promoteurData["country"]) : '00226';			
            
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]                = "Adresse invalide : veuillez sélectionner un pays valide";
			} else {
				$formatPhoneNumber              = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $promoteurData["telephone"] ));
				$validPhoneNumberPattern        ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$errorMessages[]            = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone du pays selectionné";
				} else {
					$promoteurData["telephone"] = $formatPhoneNumber;
				}
			}
			if( $emailValidator->isValid($promoteurData["email"] ) ) {
				if( $existantClient             =  $model->findRow(trim($promoteurData["email"]), "email", null , false )) {
					$errorMessages[]            = sprintf(" Un promoteur du nom de %s %s utilise cette adresse email %s", $existantClient->lastname, $existantClient->firstname, $promoteurData["email"] );
				} else {
					$promoteurData["email"]     = $stringFilter->filter($promoteurData["email"]);
				}
			} else {
				    $promoteurData["email"]     = sprintf("%s@siraah.net", time());
			}				 
			if(!$strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$errorMessages[]                = " Veuillez entrer un numéro de téléphone valide";
			} elseif($existantClient            = $model->findRow(trim($promoteurData["telephone"]), "telephone", null , false ) ) {
				$errorMessages[]                = sprintf(" Un promoteur du nom de %s %s existe déjà avec ce numéro de téléphone %s", $existantClient->lastname, $existantClient->firstname, $promoteurData["telephone"] );
		    } else {
				$promoteurData["telephone"]     = $stringFilter->filter($promoteurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($promoteurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"] = $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s)";
			} else {
				$promoteurData["firstname"]     = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille";
			} else {
				$promoteurData["lastname"]      = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || (( $promoteurData["sexe"] != "M" )  && ( $promoteurData["sexe"] != "F" ))) {
				$errorMessages[]                = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
				$promoteurData["sexe"]          = $stringFilter->filter( $promoteurData["sexe"] );
			}					
			//On vérifie les informations de la référence de la pièce d'identité
			$zendIdentityDate                   = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
					                                                  "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
					                                                  "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
			$postData["date_etablissement"]     = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
			if(!intval($postData["identitetype"]) || !isset($identiteTypes[$postData["identitetype"]])) {
				$errorMessages[]                = "Veuillez renseigner le type de pièce d'identité";
			} else {
				$pieceIdentityData["typeid"]    = intval($postData["identitetype"]);
			}
			if(!$strNotEmptyValidator->isValid($postData["numero"]) ) {
				$errorMessages[]                = "Veuillez renseigner le numéro d'identité du promoteur";
			} else {
				$pieceIdentityData["numero"]    = $stringFilter->filter( $postData["numero"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["organisme_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
				$errorMessages[]                         = "Veuillez renseigner le lieu d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["lieu_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
				$errorMessages[]                         = "Veuillez renseigner la date d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["date_etablissement"] = $stringFilter->filter( $postData["date_etablissement"] );
			}
			$promoteurData["identityid"]        = 0;
			$promoteurData["numidentite"]       = "";
			$promoteurData["name"]              = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
			$promoteurData["nationalite"]       = $stringFilter->filter($promoteurData["country"] );
			$promoteurData["adresse"]           = $stringFilter->filter($promoteurData["adresse"] );
			$promoteurData["profession"]        = $stringFilter->filter($promoteurData["profession"] );
			$promoteurData["lieunaissance"]     = (isset($postData["lieunaissance"]))? $stringFilter->filter($postData["lieunaissance"] ) : "";
			$promoteurData["datenaissance"]     = (isset($postData["datenaissance"]))? $stringFilter->filter($postData["datenaissance"] ) : "";
			$promoteurData["creationdate"]      = time();
			$promoteurData["creatorid"]         = $me->userid;
			$promoteurData["updatedate"]        = 0;
			$promoteurData["updateduserid"]     = 0;
			
			if( empty( $errorMessages ) ){
				$promoteurEmptyData                         = $model->getEmptyData();
				$insert_data                                = array_intersect_key( $promoteurData , $promoteurEmptyData );
				if( $dbAdapter->insert( $tableName , $insert_data ) )	{
					$promoteurid                            = $dbAdapter->lastInsertId();
					$promoteurRow                           = $model->findRow($promoteurid, "promoteurid", null, false);
					
					if( $promoteurRow ) {
						$pieceIdentityData["creationdate"]  = time();
						$pieceIdentityData["creatorid"]     = $me->userid;
						$pieceIdentityData["updatedate"]    = 0;
						$pieceIdentityData["updateduserid"] = 0;
						$dbAdapter->delete(     $prefixName."reservation_promoteurs_identite", array("numero=?"=>$pieceIdentityData["numero"],"typeid=?"=>$pieceIdentityData["typeid"]));
						if( $dbAdapter->insert( $prefixName."reservation_promoteurs_identite", $pieceIdentityData)  ) {
							$identityid                     = $dbAdapter->lastInsertId();
							$numidentite                    = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);
							$promoteurRow->identityid       = $insert_data["identityid"]  = $identityid;
							$promoteurRow->numidentite      = $insert_data["numidentite"] = $numidentite;
							$promoteurRow->save();
						}
					}				
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);

						$jsonData                           = $insert_data;
						$jsonData["promoteurid"]            = $promoteurid;
						$jsonData["numero_identite"]        = $pieceIdentityData["numero"];
						$jsonData["identitetypeid"]         = $pieceIdentityData["typeid"];
						$jsonData["date_etablissement"]     = $pieceIdentityData["date_etablissement"];
						$jsonData["lieu_etablissement"]     = $pieceIdentityData["lieu_etablissement"];
						$jsonData["organisme_etablissement"]= $pieceIdentityData["organisme_etablissement"];
						$jsonData["success"]                = "Les informations du promoteur ont été enregistrées avec succès ";
						echo ZendX_JQuery::encodeJson($jsonData);
						exit;
					}
					$this->setRedirect("Les informations du promoteur ont été enregistrées avec succès", "success");
					$this->redirect("admin/promoteurs/infos/id/". $promoteurid );
				}	else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Les informations du promoteur n'ont pas été enregistrées pour des raisons inconnues"));
						exit;
					}
					$this->setRedirect("Les informations du promoteur n'ont pas été enregistrées pour des raisons inconnues", "error");
					$this->redirect("admin/promoteurs/list/");
				}		
			} else {
				$promoteurDefaultData   = array_merge($promoteurDefaultData,$postData, $promoteurData, $pieceIdentityData);
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $message) {
						 $this->_helper->Message->addMessage($message , "error") ;
				}
			}
		}	
		$this->view->data           = $promoteurDefaultData;
	}
	
	public function editAction()
	{
		$model                      = $this->getModel("promoteur");
		$modelIdentite              = $this->getModel("usageridentite");
		$modelIdentiteType          = $this->getModel("usageridentitetype");
		$modelCountry               = $this->getModel("country");
		$promoteurid                = intval($this->_getParam("id", $this->_getParam("promoteurid", 0 )));
		$promoteur                  = $promoteurRow = $model->findRow( $promoteurid , "promoteurid", null , false );
		if(!$promoteur ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> sprintf("Aucun promoteur valide n'a été retrouvé avec l'identifiant %s " , $promoteurid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun promoteur valide n'a été retrouvé avec l'identifiant %s " , $promoteurid ) , "error");
			$this->redirect("admin/promoteurs/list");
		}
		$promoteurIdentity                = ( $promoteur->identityid )?$modelIdentite->findRow($promoteur->identityid, "identityid", null, false ) : null;
		$promoteurIdentityData            = ( $promoteurIdentity     )?$promoteurIdentity->toArray() : $modelIdentite->getEmptyData();
		$promoteurIdentityId              = ( $promoteur->identityid )?$promoteur->identityid        : 0;
		$defaultData                      = $promoteur->toArray();
        $defaultPromoteurData             = array_merge($defaultData, $promoteurIdentityData);
		
		$this->view->countries            = $countries     = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );
        $this->view->identiteTypes        = $identiteTypes = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );	
		$errorMessages                    = array();
	
		if( $this->_request->isPost()  )    {
			$postData                     = $this->_request->getPost();	 
			$promoteurFormData            = array_intersect_key( $postData  , $defaultData );
			$promoteurData                = $update_data = array_merge( $defaultData,  $promoteurFormData   );
			$pieceIdentityData            = array_merge($defaultIdentityData, array_intersect_key($postData,$defaultIdentityData));
						
			$me                           = Sirah_Fabric::getUser();
			
			$modelTable                   = $model->getTable();
			$dbAdapter                    = $modelTable->getAdapter();
			$prefixName                   = $modelTable->info("namePrefix");			
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                       = new Zend_Filter();
			$stringFilter->addFilter(             new Zend_Filter_StringTrim());
			$stringFilter->addFilter(             new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator               = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator                     = new Sirah_Validateur_Email();

            $promoteurData["name"]              = (isset( $postData["name"]        ))? $stringFilter->filter($postData["name"])              : $defaultPromoteurData["name"];
			$promoteurData["country"]           = (isset( $postData["country"]     ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : $defaultData["nationalite"]);
            $countryCallingCode                 = (!empty($promoteurData["country"]))? $modelCountry->callingCode($promoteurData["country"]) : '00226';			
            
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]                = "Adresse invalide : veuillez sélectionner un pays valide";
			} else {
				$formatPhoneNumber              = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $promoteurData["telephone"] ));
				$validPhoneNumberPattern        ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$errorMessages[]            = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone du pays selectionné";
				} else {
					$promoteurData["telephone"] = $formatPhoneNumber;
				}
			}
			$existantClientByEmail              = ($strNotEmptyValidator->isValid($promoteurData["email"]))?$model->findRow(trim($promoteurData["email"]), "email", null , false ) : null;	
            $emailExists                        = ($existantClientByEmail)?($existantClientByEmail->promoteurid==$promoteurid) : false;
			if( $emailValidator->isValid($promoteurData["email"] ) ) {
				if( $emailExists ) {
					$errorMessages[]            = sprintf(" Un promoteur du nom de %s %s utilise cette adresse email %s", $existantClient->lastname, $existantClient->firstname, $promoteurData["email"] );
				} else {
					$promoteurData["email"]     = $stringFilter->filter($promoteurData["email"]);
				}
			} else {
				    $promoteurData["email"]     = sprintf("%s@siraah.net", time());
			}		
            $existantClientByTelephone          = ($strNotEmptyValidator->isValid($promoteurData["telephone"]))?$model->findRow(trim($promoteurData["telephone"]), "telephone", null , false ) : null;	
            $phoneExists                        = ($existantClientByTelephone)?($existantClientByTelephone->promoteurid==$promoteurid) : false;			
			if(!$strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$errorMessages[]                = " Veuillez entrer un numéro de téléphone valide";
			} elseif($phoneExists) {
				$errorMessages[]                = sprintf(" Un promoteur du nom de %s %s existe déjà avec ce numéro de téléphone %s", $existantClient->lastname, $existantClient->firstname, $promoteurData["telephone"] );
		    } else {
				$promoteurData["telephone"]     = $stringFilter->filter($promoteurData["telephone"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"] = $fullNameArray[1];
				}
			}			
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s)";
			} else {
				$promoteurData["firstname"]     = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille";
			} else {
				$promoteurData["lastname"]      = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || ( ( $promoteurData["sexe"] != "M" )  && ( $promoteurData["sexe"] != "F" ) ) ) {
					$errorMessages[]            = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
					$promoteurData["sexe"]      = $stringFilter->filter( $promoteurData["sexe"] );
			}					
			//On vérifie les informations de la référence de la pièce d'identité
			$zendIdentityDate                   = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
					                                                  "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
					                                                  "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
			$postData["date_etablissement"]     = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
			if(!intval($postData["identitetype"]) || !isset($identiteTypes[$postData["identitetype"]])) {
				$errorMessages[]                = "Veuillez renseigner le type de pièce d'identité";
			} else {
				$pieceIdentityData["typeid"]    = intval($postData["identitetype"]);
			}
			if(!$strNotEmptyValidator->isValid($postData["numero"]) ) {
				$errorMessages[]                = "Veuillez renseigner le numéro d'identité du promoteur";
			} else {
				$pieceIdentityData["numero"]    = $stringFilter->filter( $postData["numero"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["organisme_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
				$errorMessages[]                         = "Veuillez renseigner le lieu d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["lieu_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
				$errorMessages[]                         = "Veuillez renseigner la date d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["date_etablissement"] = $stringFilter->filter( $postData["date_etablissement"] );
			}
			$promoteurData["identityid"]        = $promoteurRow->identityid;
			$promoteurData["name"]              = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
			$promoteurData["nationalite"]       = (isset($promoteurData["nationalite"]  ))? $stringFilter->filter($promoteurData["nationalite"] )  : $defaultData["nationalite"];
			$promoteurData["adresse"]           = (isset($promoteurData["adresse"]      ))? $stringFilter->filter($promoteurData["adresse"] )      : $defaultData["adresse"];
			$promoteurData["profession"]        = (isset($promoteurData["profession"]   ))? $stringFilter->filter($promoteurData["profession"]   ) : $defaultData["profession"];
			$promoteurData["lieunaissance"]     = (isset($promoteurData["lieunaissance"]))? $stringFilter->filter($promoteurData["lieunaissance"]) : $defaultData["lieunaissance"];
			$promoteurData["datenaissance"]     = (isset($promoteurData["datenaissance"]))? $stringFilter->filter($promoteurData["datenaissance"]) : $defaultData["datenaissance"];
			$promoteurData["updateduserid"]     = $me->userid;
			$promoteurData["updatedate"]        = time();
			if( isset($promoteurData["promoteurid"])) {
				unset($promoteurData["promoteurid"]);
			}	
			$defaultData                        = $promoteurData;
			//on sauvegarde la table
			$promoteur->setFromArray( $promoteurData );
			if( empty( $errorMessages ) ) {
				if( $promoteur->save() ) {
					if( $promoteurIdentity && $promoteurIdentityId) {
						if( isset($pieceIdentityData["identityid"])) {
							unset($pieceIdentityData["identityid"]);
						}
						$pieceIdentityData["updatedate"]    = time();
						$pieceIdentityData["updateduserid"] = $me->userid;
						$dbAdapter->update($prefixName."reservation_promoteurs_identite", $pieceIdentityData, array("identityid=?"=>$identityid));
					} else {
						$pieceIdentityData["creationdate"]  = time();
						$pieceIdentityData["creatorid"]     = $me->userid;
						$pieceIdentityData["updatedate"]    = 0;
						$pieceIdentityData["updateduserid"] = 0;
						
						if( $dbAdapter->insert($prefixName."reservation_promoteurs_identite", $pieceIdentityData)) {
							$identityid                     = $dbAdapter->lastInsertId();							
						}
					}
					$numidentite                    = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);
					$promoteur->numidentite         = $numidentite;
					$promoteur->save();
					
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("success" => "Les informations du promoteur ont été mises à jour avec succès"));
						exit;
					}
					$this->setRedirect("Les informations du promoteur ont été mises à jour avec succès", "success");
					$this->redirect("admin/promoteurs/infos/promoteurid/".$promoteurid );
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("error" => "Aucune modification n'a été faite dans les informations du promoteur"));
						exit;
					}
					$this->setRedirect("Aucune modification n'a été faite dans les informations du promoteur", "error");
					$this->redirect("admin/promoteurs/infos/promoteurid/".$promoteurid );
				}
			} else {
				$promoteurDefaultData   = array_merge($postData, $promoteurDefaultData, $promoteurData, $pieceIdentityData);
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					  $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$defaultPromoteurData["sexe"] = (!empty($defaultPromoteurData["sexe"]))? $defaultPromoteurData["sexe"] : "M";
		$this->view->data             = $defaultPromoteurData;
		$this->view->promoteurid      = $promoteurid;
		$this->view->title            = sprintf("Mettre à jour les informations du promoteur %s %s", $promoteur->lastname, $promoteur->firstname );
		$this->render("edit")	;
	}
	
	
	public function deleteAction()
	{
		$promoteurids     = $this->_getParam("promoteurids", $this->_getParam("ids", array()));
		$errorMessages = array();
		$model         = $this->getModel("promoteur");
		$me            = Sirah_Fabric::getUser();
		$modelTable     = $me->getTable();
		$dbAdapter     = $modelTable->getAdapter();
		$prefixName    = $modelTable->info("namePrefix");
		
		if(!is_array( $promoteurids ) ) {
			$promoteurids = explode(",", $promoteurids );
		}
		if( empty( $promoteurids ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/promoteurs/list");
		}
	    if( count(  $promoteurids ) ) {
			foreach($promoteurids as $id) {
					$row            = $model->findRow( $id , "promoteurid" , null , false );
					 
					if( $row ) {
						$identityid = $row->identityid;
						if(!$row->delete()) {
							$errorMessages[]  = "Erreur de la base de donnée : le promoteur id#$id n'a pas été supprimé ";
						} else {
							$dbAdapter->delete( $prefixName."reservation_promoteurs_identite"   , array("identityid=?"=>$identityid));
							$dbAdapter->delete( $prefixName."reservation_demandes"              , array("promoteurid=?"=>$id));
							$dbAdapter->delete( $prefixName."reservation_demandes_reservations" , array("promoteurid=?"=>$id));
							$dbAdapter->delete( $prefixName."reservation_demandes_verifications", array("promoteurid=?"=>$id));
						}
					} else {
							$errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le promoteur id #$id ";
					}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}
		if(!empty($errorMessages)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage ) {
				$this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les promoteurs selectionnés ont été supprimés avec succès" ));
				exit;
			}
			$this->setRedirect("Les promoteurs selectionnés ont été supprimés avec succès" , "success");
		}
		$this->redirect("admin/promoteurs/list");		
	}
	
	
    public function infosAction()
	{
		$this->_helper->layout->setLayout("default");
		
		$model                      = $this->getModel("promoteur");
		$modelIdentite              = $this->getModel("usageridentite");
		$modelIdentiteType          = $this->getModel("usageridentitetype");
		$modelCountry               = $this->getModel("country");
	    $errorMessages              = array();
		$me                         = Sirah_Fabric::getUser();
		$promoteurid                = intval($this->_getParam("id", $this->_getParam("promoteurid" , 0 )));
		$promoteur                  = $model->findRow( $promoteurid , "promoteurid", null , false );
		if(!$promoteur )  {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => sprintf("Aucun promoteur valide n'a été retrouvé avec l'identifiant %s " , $promoteurid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun promoteur valide n'a été retrouvé avec l'identifiant %s " , $promoteurid ) , "error");
			$this->redirect("admin/promoteurs/list");
		}				
		$this->view->promoteur      = $promoteur;
		$this->view->promoteurid    = $promoteurid;
		$this->view->identite       = $promoteur->identite();
        $this->view->demandes       = $promoteur->demandes( $promoteurid);
		$this->view->documents      = $promoteur->documents($promoteurid);
		$this->view->nationalite    = $promoteur->findParentRow("Table_Countries");
		$this->view->title          = sprintf("Les informations de %s %s " , $promoteur->lastname , $promoteur->firstname);
		$this->view->columns        = array("right", "left");		
	} 	
}