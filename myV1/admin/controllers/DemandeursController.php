<?php

class Admin_DemandeursController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{		
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}					
		$this->view->title        = "Gestion des demandeurs";
		$demandeurs               = array();
		$demandeursListePaginator = null;
		$model                    = $this->getModel("demandeur");
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
		$demandeurs                  = $model->getList($filters , $pageNum , $pageSize );
		$demandeursListePaginator    = $model->getListPaginator($filters);
	
		if(null !== $demandeursListePaginator ) {
			$demandeursListePaginator->setCurrentPageNumber($pageNum);
			$demandeursListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->demandeurs      = $demandeurs;
		$this->view->filters         = $filters;
		$this->view->pageNum         = $pageNum;
		$this->view->pageSize        = $pageSize;
		$this->view->paginator       = $demandeursListePaginator;
		$this->view->identiteTypes   = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		$this->view->maxitems        = $pageSize;
		$this->view->columns         = array("left");
		
	}
	
		
	public function createAction()
	{
		$this->view->title                        = "Enregistrer un nouveau demandeur";
		$model                                    = $this->getModel("demandeur");
		$modelIdentite                            = $this->getModel("usageridentite");
		$modelIdentiteType                        = $this->getModel("usageridentitetype");
		$modelCountry                             = $this->getModel("country");
		
		$demandeurDefaultData                     = $model->getEmptyData();
		$demandeurDefaultData["country"]          = $country           = $demandeurDefaultData["nationalite"] = $this->_getParam("country", "bf");
		$demandeurDefaultData["identitetype"]     = $identitetypeid    = intval($this->_getParam("typeidentity",$this->_getParam("identite", 0)));
		
		$errorMessages            = $demandeurData= $pieceIdentityData = array();
		
		
		$this->view->countries    = $countries    = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes= $identiteTypes= $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );	
		
		if( $this->_request->isPost() ) {			
			$postData                             = $this->_request->getPost();
			$defaultData                          = $model->getEmptyData();
			$defaultIdentityData                  = $modelIdentite->getEmptyData();
			$demandeurFormData                    = array_intersect_key( $postData  , $defaultData );
			$demandeurData                        = $insert_data = array_merge( $defaultData,  $demandeurFormData   );
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

            $demandeurData["name"]              = (isset( $postData["name"]        ))? $stringFilter->filter($postData["name"])              : "";
			$demandeurData["country"]           = (isset( $postData["country"]     ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : "BF");
            $countryCallingCode                 = (!empty($demandeurData["country"]))? $modelCountry->callingCode($demandeurData["country"]) : '00226';			
            
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]                = "Adresse invalide : veuillez sélectionner un pays valide";
			} else {
				$formatPhoneNumber              = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $demandeurData["telephone"] ));
				$validPhoneNumberPattern        ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$errorMessages[]            = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone du pays selectionné";
				} else {
					$demandeurData["telephone"] = $formatPhoneNumber;
				}
			}
			if( $emailValidator->isValid($demandeurData["email"] ) ) {
				if( $existantClient             =  $model->findRow(trim($demandeurData["email"]), "email", null , false )) {
					$errorMessages[]            = sprintf(" Un demandeur du nom de %s %s utilise cette adresse email %s", $existantClient->lastname, $existantClient->firstname, $demandeurData["email"] );
				} else {
					$demandeurData["email"]     = $stringFilter->filter($demandeurData["email"]);
				}
			} else {
				    $demandeurData["email"]     = sprintf("%s@siraah.net", time());
			}				 
			if(!$strNotEmptyValidator->isValid($demandeurData["telephone"])) {
				$errorMessages[]                = " Veuillez entrer un numéro de téléphone valide";
			} elseif($existantClient            = $model->findRow(trim($demandeurData["telephone"]), "telephone", null , false ) ) {
				$errorMessages[]                = sprintf(" Un demandeur du nom de %s %s existe déjà avec ce numéro de téléphone %s", $existantClient->lastname, $existantClient->firstname, $demandeurData["telephone"] );
		    } else {
				$demandeurData["telephone"]     = $stringFilter->filter($demandeurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($demandeurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($demandeurData["name"]);
				if( isset($fullNameArray[0])) {
					$demandeurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$demandeurData["firstname"] = $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s)";
			} else {
				$demandeurData["firstname"]     = $stringFilter->filter($demandeurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille";
			} else {
				$demandeurData["lastname"]      = $stringFilter->filter($demandeurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["sexe"]) || (( $demandeurData["sexe"] != "M" )  && ( $demandeurData["sexe"] != "F" ))) {
				$errorMessages[]                = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
				$demandeurData["sexe"]          = $stringFilter->filter( $demandeurData["sexe"] );
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
				$errorMessages[]                = "Veuillez renseigner le numéro d'identité du demandeur";
			} else {
				$pieceIdentityData["numero"]    = $stringFilter->filter( $postData["numero"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["organisme_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
				$errorMessages[]                         = "Veuillez renseigner le lieu d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["lieu_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
				$errorMessages[]                         = "Veuillez renseigner la date d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["date_etablissement"] = $stringFilter->filter( $postData["date_etablissement"] );
			}
			$demandeurData["identityid"]        = 0;
			$demandeurData["numidentite"]       = "";
			$demandeurData["name"]              = sprintf("%s %s", $demandeurData["lastname"], $demandeurData["firstname"]);
			$demandeurData["nationalite"]       = $stringFilter->filter($demandeurData["country"] );
			$demandeurData["adresse"]           = $stringFilter->filter($demandeurData["adresse"] );
			$demandeurData["profession"]        = $stringFilter->filter($demandeurData["profession"] );
			$demandeurData["lieunaissance"]     = (isset($postData["lieunaissance"]))? $stringFilter->filter($postData["lieunaissance"] ) : "";
			$demandeurData["datenaissance"]     = "0000-00-00 00:00:00";
			$demandeurData["creationdate"]      = time();
			$demandeurData["creatorid"]         = $me->userid;
			$demandeurData["updatedate"]        = 0;
			$demandeurData["updateduserid"]     = 0;
			
			if( empty( $errorMessages ) ){
				$demandeurEmptyData                         = $model->getEmptyData();
				$insert_data                                = array_intersect_key( $demandeurData , $demandeurEmptyData );
				if( $dbAdapter->insert( $tableName , $insert_data ) )	{
					$demandeurid                            = $dbAdapter->lastInsertId();
					$demandeurRow                           = $model->findRow($demandeurid, "demandeurid", null, false);
					
					if( $demandeurRow ) {
						$pieceIdentityData["creationdate"]  = time();
						$pieceIdentityData["creatorid"]     = $me->userid;
						$pieceIdentityData["updatedate"]    = 0;
						$pieceIdentityData["updateduserid"] = 0;
						$dbAdapter->delete(     $prefixName."reservation_demandeurs_identite", array("numero=?"=>$pieceIdentityData["numero"],"typeid=?"=>$pieceIdentityData["typeid"]));
						if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $pieceIdentityData)  ) {
							$identityid                     = $dbAdapter->lastInsertId();
							$numidentite                    = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);
							$demandeurRow->identityid       = $insert_data["identityid"]  = $identityid;
							$demandeurRow->numidentite      = $insert_data["numidentite"] = $numidentite;
							$demandeurRow->save();
						}
					}				
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);

						$jsonData                           = $insert_data;
						$jsonData["demandeurid"]            = $demandeurid;
						$jsonData["numero_identite"]        = $jsonData["identitenumero"]     = $pieceIdentityData["numero"];
						$jsonData["identitetypeid"]         = $pieceIdentityData["typeid"];
						$jsonData["date_etablissement"]     = $jsonData["identite_date"]      = $postData["date_etablissement"];
						$jsonData["lieu_etablissement"]     = $jsonData["identite_lieu"]      = $pieceIdentityData["lieu_etablissement"];
						$jsonData["organisme_etablissement"]= $jsonData["identite_organisme"] = $pieceIdentityData["organisme_etablissement"];
						$jsonData["success"]                = "Les informations du demandeur ont été enregistrées avec succès ";
						echo ZendX_JQuery::encodeJson($jsonData);
						exit;
					}
					$this->setRedirect("Les informations du demandeur ont été enregistrées avec succès", "success");
					$this->redirect("admin/demandeurs/infos/id/". $demandeurid );
				}	else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Les informations du demandeur n'ont pas été enregistrées pour des raisons inconnues"));
						exit;
					}
					$this->setRedirect("Les informations du demandeur n'ont pas été enregistrées pour des raisons inconnues", "error");
					$this->redirect("admin/demandeurs/list/");
				}		
			} else {
				$demandeurDefaultData   = array_merge($demandeurDefaultData,$postData, $demandeurData, $pieceIdentityData);
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
		$this->view->data           = $demandeurDefaultData;
	}
	
	public function editAction()
	{
		$model                      = $this->getModel("demandeur");
		$modelIdentite              = $this->getModel("usageridentite");
		$modelIdentiteType          = $this->getModel("usageridentitetype");
		$modelCountry               = $this->getModel("country");
		$demandeurid                = intval($this->_getParam("id", $this->_getParam("demandeurid", 0 )));
		$demandeur                  = $demandeurRow = $model->findRow( $demandeurid , "demandeurid", null , false );
		if(!$demandeur ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> sprintf("Aucun demandeur valide n'a été retrouvé avec l'identifiant %s " , $demandeurid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun demandeur valide n'a été retrouvé avec l'identifiant %s " , $demandeurid ) , "error");
			$this->redirect("admin/demandeurs/list");
		}
		$demandeurIdentity                = ( $demandeur->identityid )?$modelIdentite->findRow($demandeur->identityid, "identityid", null, false ) : null;
		$demandeurIdentityData            = ( $demandeurIdentity     )?$demandeurIdentity->toArray() : $modelIdentite->getEmptyData();
		$demandeurIdentityId              = ( $demandeur->identityid )?$demandeur->identityid        : 0;
		$defaultData                      = $demandeur->toArray();
        $defaultDemandeurData             = array_merge($defaultData, $demandeurIdentityData);
		
		$this->view->countries            = $countries     = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );
        $this->view->identiteTypes        = $identiteTypes = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );	
		$errorMessages                    = array();
	
		if( $this->_request->isPost()  )    {
			$postData                     = $this->_request->getPost();	 
			$demandeurFormData            = array_intersect_key( $postData  , $defaultData );
			$demandeurData                = $update_data = array_merge( $defaultData,  $demandeurFormData   );
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

            $demandeurData["name"]              = (isset( $postData["name"]        ))? $stringFilter->filter($postData["name"])              : $defaultDemandeurData["name"];
			$demandeurData["country"]           = (isset( $postData["country"]     ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : $defaultData["nationalite"]);
            $countryCallingCode                 = (!empty($demandeurData["country"]))? $modelCountry->callingCode($demandeurData["country"]) : '00226';			
            
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]                = "Adresse invalide : veuillez sélectionner un pays valide";
			} else {
				$formatPhoneNumber              = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $demandeurData["telephone"] ));
				$validPhoneNumberPattern        ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$errorMessages[]            = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone du pays selectionné";
				} else {
					$demandeurData["telephone"] = $formatPhoneNumber;
				}
			}
			$existantClientByEmail              = ($strNotEmptyValidator->isValid($demandeurData["email"]))?$model->findRow(trim($demandeurData["email"]), "email", null , false ) : null;	
            $emailExists                        = ($existantClientByEmail)?($existantClientByEmail->demandeurid==$demandeurid) : false;
			if( $emailValidator->isValid($demandeurData["email"] ) ) {
				if( $emailExists ) {
					$errorMessages[]            = sprintf(" Un demandeur du nom de %s %s utilise cette adresse email %s", $existantClient->lastname, $existantClient->firstname, $demandeurData["email"] );
				} else {
					$demandeurData["email"]     = $stringFilter->filter($demandeurData["email"]);
				}
			} else {
				    $demandeurData["email"]     = sprintf("%s@siraah.net", time());
			}		
            $existantClientByTelephone          = ($strNotEmptyValidator->isValid($demandeurData["telephone"]))?$model->findRow(trim($demandeurData["telephone"]), "telephone", null , false ) : null;	
            $phoneExists                        = ($existantClientByTelephone)?($existantClientByTelephone->demandeurid==$demandeurid) : false;			
			if(!$strNotEmptyValidator->isValid($demandeurData["telephone"])) {
				$errorMessages[]                = " Veuillez entrer un numéro de téléphone valide";
			} elseif($phoneExists) {
				$errorMessages[]                = sprintf(" Un demandeur du nom de %s %s existe déjà avec ce numéro de téléphone %s", $existantClient->lastname, $existantClient->firstname, $demandeurData["telephone"] );
		    } else {
				$demandeurData["telephone"]     = $stringFilter->filter($demandeurData["telephone"]);
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["name"])) {
				$fullNameArray                  = Sirah_Functions_String::split_name($demandeurData["name"]);
				if( isset($fullNameArray[0])) {
					$demandeurData["lastname"]  = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$demandeurData["firstname"] = $fullNameArray[1];
				}
			}			
			if(!$strNotEmptyValidator->isValid($demandeurData["firstname"])) {
				$errorMessages[]                = "Veuillez saisir le(s) prénom(s)";
			} else {
				$demandeurData["firstname"]     = $stringFilter->filter($demandeurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["lastname"])) {
				$errorMessages[]                = "Veuillez saisir le nom de famille";
			} else {
				$demandeurData["lastname"]      = $stringFilter->filter($demandeurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($demandeurData["sexe"]) || ( ( $demandeurData["sexe"] != "M" )  && ( $demandeurData["sexe"] != "F" ) ) ) {
					$errorMessages[]            = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
					$demandeurData["sexe"]      = $stringFilter->filter( $demandeurData["sexe"] );
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
				$errorMessages[]                = "Veuillez renseigner le numéro d'identité du demandeur";
			} else {
				$pieceIdentityData["numero"]    = $stringFilter->filter( $postData["numero"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["organisme_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
				$errorMessages[]                         = "Veuillez renseigner le lieu d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["lieu_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
				$errorMessages[]                         = "Veuillez renseigner la date d'étalissement de la pièce d'identité du demandeur";
			} else {
				$pieceIdentityData["date_etablissement"] = $stringFilter->filter( $postData["date_etablissement"] );
			}
			$demandeurData["identityid"]        = $demandeurRow->identityid;
			$demandeurData["name"]              = sprintf("%s %s", $demandeurData["lastname"], $demandeurData["firstname"]);
			$demandeurData["nationalite"]       = (isset($demandeurData["nationalite"]  ))? $stringFilter->filter($demandeurData["nationalite"] )  : $defaultData["nationalite"];
			$demandeurData["adresse"]           = (isset($demandeurData["adresse"]      ))? $stringFilter->filter($demandeurData["adresse"] )      : $defaultData["adresse"];
			$demandeurData["profession"]        = (isset($demandeurData["profession"]   ))? $stringFilter->filter($demandeurData["profession"]   ) : $defaultData["profession"];
			$demandeurData["lieunaissance"]     = (isset($demandeurData["lieunaissance"]))? $stringFilter->filter($demandeurData["lieunaissance"]) : $defaultData["lieunaissance"];
			$demandeurData["datenaissance"]     = (isset($demandeurData["datenaissance"]))? $stringFilter->filter($demandeurData["datenaissance"]) : $defaultData["datenaissance"];
			$demandeurData["updateduserid"]     = $me->userid;
			$demandeurData["updatedate"]        = time();
			if( isset($demandeurData["demandeurid"])) {
				unset($demandeurData["demandeurid"]);
			}	
			$defaultData                        = $demandeurData;
			//on sauvegarde la table
			$demandeur->setFromArray( $demandeurData );
			if( empty( $errorMessages ) ) {
				if( $demandeur->save() ) {
					if( $demandeurIdentity && $demandeurIdentityId) {
						if( isset($pieceIdentityData["identityid"])) {
							unset($pieceIdentityData["identityid"]);
						}
						$pieceIdentityData["updatedate"]    = time();
						$pieceIdentityData["updateduserid"] = $me->userid;
						$dbAdapter->update($prefixName."reservation_demandeurs_identite", $pieceIdentityData, array("identityid=?"=>$identityid));
					} else {
						$pieceIdentityData["creationdate"]  = time();
						$pieceIdentityData["creatorid"]     = $me->userid;
						$pieceIdentityData["updatedate"]    = 0;
						$pieceIdentityData["updateduserid"] = 0;
						
						if( $dbAdapter->insert($prefixName."reservation_demandeurs_identite", $pieceIdentityData)) {
							$identityid                     = $dbAdapter->lastInsertId();							
						}
					}
					$numidentite                    = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);
					$demandeur->numidentite         = $numidentite;
					$demandeur->save();
					
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("success" => "Les informations du demandeur ont été mises à jour avec succès"));
						exit;
					}
					$this->setRedirect("Les informations du demandeur ont été mises à jour avec succès", "success");
					$this->redirect("admin/demandeurs/infos/demandeurid/".$demandeurid );
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("error" => "Aucune modification n'a été faite dans les informations du demandeur"));
						exit;
					}
					$this->setRedirect("Aucune modification n'a été faite dans les informations du demandeur", "error");
					$this->redirect("admin/demandeurs/infos/demandeurid/".$demandeurid );
				}
			} else {
				$demandeurDefaultData   = array_merge($postData, $demandeurDefaultData, $demandeurData, $pieceIdentityData);
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
		$defaultDemandeurData["sexe"] = (!empty($defaultDemandeurData["sexe"]))? $defaultDemandeurData["sexe"] : "M";
		$this->view->data             = $defaultDemandeurData;
		$this->view->demandeurid      = $demandeurid;
		$this->view->title            = sprintf("Mettre à jour les informations du demandeur %s %s", $demandeur->lastname, $demandeur->firstname );
		$this->render("edit")	;
	}
	
	
	public function deleteAction()
	{
		$demandeurids     = $this->_getParam("demandeurids", $this->_getParam("ids", array()));
		$errorMessages = array();
		$model         = $this->getModel("demandeur");
		$me            = Sirah_Fabric::getUser();
		$modelTable     = $me->getTable();
		$dbAdapter     = $modelTable->getAdapter();
		$prefixName    = $modelTable->info("namePrefix");
		
		if(!is_array( $demandeurids ) ) {
			$demandeurids = explode(",", $demandeurids );
		}
		if( empty( $demandeurids ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/demandeurs/list");
		}
	    if( count(  $demandeurids ) ) {
			foreach($demandeurids as $id) {
					$row            = $model->findRow( $id , "demandeurid" , null , false );
					 
					if( $row ) {
						$identityid = $row->identityid;
						if(!$row->delete()) {
							$errorMessages[]  = "Erreur de la base de donnée : le demandeur id#$id n'a pas été supprimé ";
						} else {
							$dbAdapter->delete( $prefixName."reservation_demandeurs_identite"   , array("identityid=?"=>$identityid));
							$dbAdapter->delete( $prefixName."reservation_demandes"              , array("demandeurid=?"=>$id));
							$dbAdapter->delete( $prefixName."reservation_demandes_reservations" , array("demandeurid=?"=>$id));
							$dbAdapter->delete( $prefixName."reservation_demandes_verifications", array("demandeurid=?"=>$id));
						}
					} else {
							$errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le demandeur id #$id ";
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
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les demandeurs selectionnés ont été supprimés avec succès" ));
				exit;
			}
			$this->setRedirect("Les demandeurs selectionnés ont été supprimés avec succès" , "success");
		}
		$this->redirect("admin/demandeurs/list");		
	}
	
	
    public function infosAction()
	{
		$this->_helper->layout->setLayout("default");
		
		$model                      = $this->getModel("demandeur");
		$modelIdentite              = $this->getModel("usageridentite");
		$modelIdentiteType          = $this->getModel("usageridentitetype");
		$modelCountry               = $this->getModel("country");
	    $errorMessages              = array();
		$me                         = Sirah_Fabric::getUser();
		$demandeurid                = intval($this->_getParam("id", $this->_getParam("demandeurid" , 0 )));
		$demandeur                  = $model->findRow( $demandeurid , "demandeurid", null , false );
		if(!$demandeur )  {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => sprintf("Aucun demandeur valide n'a été retrouvé avec l'identifiant %s " , $demandeurid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun demandeur valide n'a été retrouvé avec l'identifiant %s " , $demandeurid ) , "error");
			$this->redirect("admin/demandeurs/list");
		}				
		$this->view->demandeur      = $demandeur;
		$this->view->demandeurid    = $demandeurid;
		$this->view->identite       = $demandeur->identite();
        $this->view->demandes       = $demandeur->demandes( $demandeurid);
		$this->view->documents      = $demandeur->documents($demandeurid);
		$this->view->nationalite    = $demandeur->findParentRow("Table_Countries");
		$this->view->title          = sprintf("Les informations de %s %s " , $demandeur->lastname , $demandeur->firstname);
		$this->view->columns        = array("right", "left");		
	} 	
}