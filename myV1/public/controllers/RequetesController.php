<?php

require 'vendor/autoload.php';

use \ReCaptcha\ReCaptcha as gReCaptcha;
use \ReCaptcha\RequestMethod\CurlPost as gReCaptchaMethodPost;

class RequetesController extends Sirah_Controller_Default
{
	protected $_member  = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		$application    = new Zend_Session_Namespace("erccmapp");
		$me             = $loggedInUser = Sirah_Fabric::getUser();
		 
		if(!$me->isOPERATEURS() && !$me->isPARTENAIRES() && !$me->isPARTNERS() && !$me->isPROMOTEURS() && !$me->isPromoteurs() && !$me->isDirecteurs()
			&& ($actionName!="verify")) {
			$returnToUrl= (!empty($actionName))?sprintf("public/requetes/%s", $actionName) : "public/requetes/verify";
			$application->returnToUrl = $returnToUrl;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Vous n'êtes pas autorisé à accéder à cette page" ));
				exit;
			}
			$this->redirect("public/members/login");
		}  
		$model                   = $this->getModel("member");
		$accountMember           = $model->fromuser($me->userid);
		if(!$accountMember && ($actionName!="verify")) {
			$returnToUrl= (!empty($actionName))?sprintf("public/requetes/%s", $actionName) : "public/requetes/verify";
			$application->returnToUrl = $returnToUrl;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
			$this->redirect("public/members/login");
		}
		$memberid                = $accountMember->memberid;
		$member                  = $model->findRow( $memberid , "memberid", null , false );
		if(!$member && ($actionName!="verify")) {
			$returnToUrl= (!empty($actionName))?sprintf("public/requetes/%s", $actionName) : "public/requetes/verify";
			$application->returnToUrl = $returnToUrl;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
			$this->redirect("public/members/login");	
		}
		$this->_member  = $member;		
		parent::init();
	}	
	
	public function verifyAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}
		$this->view->title       = "Vérifier la disponibilité d'un nom commercial";
		$application             = new Zend_Session_Namespace("erccmapp");
		$errorMessages           = $informationMessages = array();
		$params                  = $this->_request->getParams();
		$keywords                = $query = (isset($params["keywords"]))?$params["keywords"] : "";
		if(!$this->_request->isPost()) {
			$keywords            = $query = urldecode($params["keywords"]);
		}
		$available               = 0;
		$similarites             = $similariteActivities = $registres = array();
		$verificationStateStore  = new Zend_Session_Namespace("Statestore");
		if(!isset($verificationStateStore->verificationstate)) {
			$verificationStateStore->verificationstate    = array("availables"=>array(),"unavailables"=>array());
			$verificationStateStore->verificationCounter  = 0;
		}
		if(!isset($verificationStateStore->verificationCounter)){
			$verificationStateStore->verificationCounter  = 0;
		}
		if( $this->_request->isPost()  ) {
			$model               = $this->getModel("demande");
			$modelRegistre       = $this->getModel("registre");
			$modelEntreprise     = $this->getModel("demandentreprise");
			$modelBlacklist      = $this->getModel("demandeblacklist");
			
			$modelTable          = $model->getTable();
			$dbAdapter           = $modelTable->getAdapter();
			$tablePrefix         = $modelTable->info("namePrefix");
			
			$ipaddress = $guestIp= Sirah_Functions::getIpAddress();
			if( $modelRegistre->hasBlasklisted($ipaddress)) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=> "Vous n'êtes pas autorisé à cette page"));
					exit;
				}
				$this->setRedirect("Vous n'êtes pas autorisé à cette page" , "error");
				$this->redirect("public/index/index");
			}
			if( isset($verificationStateStore->verificationCounter) && ($verificationStateStore->verificationCounter > 200)) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=> "Vous avez déjà fait 150 vérifications. Veuillez patienter quelques jours"));
					exit;
				}
				$this->setRedirect("Vous avez déjà fait 200 vérifications. Veuillez patienter quelques jours" , "error");
				$this->redirect("public/index/index");
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter        =   new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			$postData            = $this->_request->getPost();
			$available           = 1;
			$foundRegistres      = array();
			$keywords            = $query    = (isset($postData["keywords"]))?strip_tags($postData["keywords"]) : "";
		    $query               = $keywords = substr($keywords,0,250);
			if( empty($keywords) ) {
				$errorMessages[] = "Veuillez saisir des mots clés du nom commercial";
			}
			if( empty($errorMessages) ) {				
				$keywords        = preg_replace("/[\-_,;]/"," ",$keywords);
				$cleanKeywords   = preg_replace("/ SARL| SA| FASO| INTERNATIONAL|INTERNATIONAL|SOCIETE|ETS |Etablissement| Holding|Etablissements| BANK| CORPORATE| GROUP|GROUP /i","",Sirah_Functions_String::cleanUtf8($keywords));
				
				$cleanKeywords   = preg_replace("/[#|\^@\*\"]/","",$cleanKeywords);
				
				$registres       = $rows        = $modelRegistre->basicList(array("searchQ"=>$cleanKeywords,"types" => array(1,2,3,4)), 1,100);				
				$searchKey       = "VERIFICATION : ".$keywords;
				if(!$modelRegistre->hasSearched( $ipaddress, $searchKey)) {					
					$searchLog                  = array();
					$searchLog["ipaddress"]     = $ipaddress;
					$searchLog["searchkey"]     = $searchKey;
					$searchLog["searchresults"] = 0;
					$searchLog["creationdate"]  = time();
					$searchLog["creatorid"]     = 1;
					$searchLog["searchresults"] = count($rows);
					$dbAdapter->delete( $tablePrefix."rccm_registre_search", array("ipaddress=?"=>$ipaddress,"searchkey=?"=>$searchLog["searchkey"]));
					$dbAdapter->insert( $tablePrefix."rccm_registre_search", $searchLog);			
				}
				$verificationStateStore->verificationCounter++;
				//var_dump($cleanKeywords); die();
				//var_dump($registres); die();
				if( count($registres) ) {
					$i           = 0;					
					foreach( $registres as $registre ) {						   
						     $foundRegistreLib             = str_replace($registre["numero"],"",$registre["libelle"]);
						     
							 $registre["libelle"]          = $foundRegistreLib;
							 $foundWords                   = preg_split("/[;,\s\-]+/i",trim($foundRegistreLib));
							 
						     if( strtolower($foundRegistreLib)==strtolower(trim($cleanKeywords))) {
								 $foundRegistres[$i]       = $registre;
								 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$foundRegistreLib);
							     $similariteActivities[$i] = $registre["description"];
								 $available                = 0;
								 $errorMessages[]          = sprintf("Le nom commercial %s n'est pas disponible. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.", $query);
							     break;
							 } elseif((count($foundWords)>=2) && (count($foundWords)<4) && (strtolower($foundWords[0])==strtolower(trim($cleanKeywords)))){
                                 $available                = 0;								 
							     $foundRegistres[$i]       = $registre;
								 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$foundRegistreLib);
							     $similariteActivities[$i] = $registre["description"];
								 $errorMessages[]          = sprintf("Le nom commercial %s ne semble pas disponible. Quelques entreprises portent une partie de ce nom dans leur nom commercial", $query);
							     break;
					         } elseif((count($foundWords)>=2) && (count($foundWords)<4) && (strtolower($foundWords[1])==strtolower(trim($cleanKeywords)))){
								 $available                = 0;
								 $foundRegistres[$i]       = $registre;
								 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$foundRegistreLib);
							     $similariteActivities[$i] = $registre["description"];
								 $errorMessages[]          = sprintf("Le nom commercial %s ne semble pas disponible. Quelques entreprises portent une partie de ce nom dans leur nom commercial", $query);
								 //$informationMessages[$i]  = sprintf("Le nom commercial %s ne semble pas disponible. Quelques entreprises portent une partie de ce nom dans leur nom commercial", $query);
					         } elseif(false!==stripos($modelRegistre->cleanName($foundRegistreLib),sprintf("%s ",$cleanKeywords))) {
								 $available                = 0;
								 $foundRegistres[$i]       = $registre;
								 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$foundRegistreLib);
							     $similariteActivities[$i] = $registre["description"];
								 $errorMessages[]          = sprintf("Le nom commercial %s ne semble pas disponible. Quelques entreprises portent une partie de ce nom dans leur nom commercial", $query);
								 //$informationMessages[$i]  = sprintf("Le nom commercial %s ne semble pas disponible. Il semble avoir quelques noms similaires", $query);
							 } else {
								 $available                = 0;
								 $foundRegistres[$i]       = $registre;
								 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$foundRegistreLib);
							     $similariteActivities[$i] = $registre["description"];
								 $informationMessages[]    = sprintf("Le nom commercial %s ne semble pas disponible. Quelques ressemblances avec des entreprises existantes", $query);								 
							 } 								 
							 $i++;
					}
					/*if(!count($foundRegistres)) {
						$foundRegistres[0]      = $registre[0];
						$informationMessages[0] = "Quelques entreprises portent une partie de ce nom. Nous vous recommandons de vous rendre dans une juridiction proche pour approfondir la vérification.";
					}*/
				} else {
					$reservedEntreprises               = $modelEntreprise->getList(array("libelle"=>$cleanKeywords,"reserved"=>1), 1,60);
					$i                                 = 0;
					if( count(   $reservedEntreprises) ) {
						foreach( $reservedEntreprises as $reservedEntreprise ) {
							     $foundRegistreLib     = $reservedEntreprise["libelle"] = $reservedEntreprise["nomcommercial"];
								 $foundRegistreSigle   = (!empty($reservedEntreprise["sigle"]))?$reservedEntreprise["sigle"] : "";
								 if(($foundRegistreLib== $query) || (false!==stripos($keywords,$foundRegistreLib)) ) {
									 $registres[$i]    = $reservedEntreprise;								 
									 $available        = 0;
									 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Un nom similaire reservé a été trouvé : %s.",$query, $foundRegistreLib);
									 break;
								 } elseif(!empty($foundRegistreSigle) && (false!==stripos($foundRegistreSigle,$keywords))) {
									 $registres[$i]    = $reservedEntreprise;
									 $available        = 0;
									 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Le sigle %s apparait dans le nom commercial à réserver.",$query,$foundRegistreSigle);
									 break;
								 }
								 $i++;
						}
					} else {
						$blacklisted = $modelBlacklist->getList(array("libelle"=>$cleanKeywords), 1,50); 
						if( count(   $blacklisted)) {
							$i       = 0;
							foreach( $blacklisted as $item ) {
								     $keywordsCleaned     = $keywords;
								     if(($item["libelle"]== $cleanKeywords) || (false!==stripos($keywordsCleaned,$item["libelle"]))) {
										 $available       = 0;
										 $registres[$i]   = $item;	
										 $errorMessages[] = sprintf("Le nom commercial %s n'est pas autorisé. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.",$query);
										 break;
									 }
									 $i++;
							}
						}
					}
				}	
                if( $available ) {
					$verificationStateStore->verificationstate["availables"][0] = $query;
				}					
			}
			if( count( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages) ));
					exit;
				}
				foreach( $errorMessages as $key => $errorMessage ) {
						 $type = (is_numeric($key)) ? "error" : $key;
						 $this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			} elseif( count($informationMessages) )  {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("info" => implode(" ; ",$informationMessages) ));
					exit;
				}
				foreach( $informationMessages as $message ) {
						 $this->getHelper("Message")->addMessage($message,"info");
				}
			}  else {
				if( isset($application->returnToUrl) && !empty($application->returnToUrl)){
					$returnToUrl              = $application->returnToUrl;
					$application->returnToUrl = "";
					$this->redirect($returnToUrl);
				}
			}
		}
		$this->view->keywords      = $keywords;
		$this->view->similarites   = $similarites;
		$this->view->activities    = $similariteActivities;
		$this->view->registres     = $foundRegistres;
		$this->view->errorMessages = $errorMessages;
		$this->view->infoMessages  = $informationMessages;
		$this->view->available     = $available;
	}
	
	
	public function reservationAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}
		$this->view->title       = "Réserver un nom commercial";
		$errorMessages           = $informationMessages = array();
		$params                  = $this->_request->getParams();
		$postData                = $this->_request->getPost();
		$keywords                = $query = (isset($params["keywords"]))?$params["keywords"] : (isset($params["query"])?urldecode($params["query"]) : "");
		$available               = 0;
		$similarites             = $similariteActivities = $registres = array();
		$verificationStateStore  = new Zend_Session_Namespace("Statestore");
		if(!isset($verificationStateStore->verificationstate)) {
			$verificationStateStore->verificationstate = array("availables"=>array(),"unavailables"=>array());
		}
		if( empty($keywords) && isset($verificationStateStore->verificationstate["availables"][0]) ) {
			$keywords            = $query = $verificationStateStore->verificationstate["availables"][0];
		}
		if( empty($keywords)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Veuillez procéder d'abord à la vérification du nom commercial"));
				exit;
			}
			//$this->setRedirect("Veuillez procéder d'abord à la vérification du nom commercial", "error");
			$this->redirect("public/requetes/verify");
		}
		
		$availables              = $verificationStateStore->verificationstate["availables"];
		$reservationKey          = array_search($keywords,$availables);
		if( false==$reservationKey && !in_array($keywords,$availables)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Veuillez procéder d'abord à la vérification du nom commercial"));
				exit;
			}
			$this->setRedirect("Veuillez procéder d'abord à la vérification du nom commercial", "error");
			$this->redirect("public/requetes/verify");
		}
		$me                              = Sirah_Fabric::getUser();
		$model                           = $this->getModel("demande");
		$modelRegistre                   = $this->getModel("registre");
		$modelEntreprise                 = $this->getModel("demandentreprise");
		$modelSource                     = $this->getModel("demandeverificationsource");
		$modelBlacklist                  = $this->getModel("demandeblacklist");
		$modelEntrepriseForme            = $this->getModel("entrepriseforme");
		$modelDomaine                    = $this->getModel("domaine");
		$modelLocalite                   = $this->getModel("localite");
		$modelMember                     = $this->getModel("member");
        $modelDemandeur                  = $this->getModel("demandeur");
		$modelPromoteur                  = $this->getModel("promoteur");
		$modelIdentite                   = $this->getModel("usageridentite");
		$modelIdentiteType               = $this->getModel("usageridentitetype");
		$modelCountry                    = $this->getModel("country");
		
		$errorMessages                   = array();
		$myData                          = $me->getData();
		$demandeurFromAccount            = $modelDemandeur->findRow($me->userid,"accountid", null,false);
		$demandeurid                     = ($demandeurFromAccount)?$demandeurFromAccount->demandeurid : 0;
		$demandeurEmptyData              = ($demandeurFromAccount)?$demandeurFromAccount->toArray()   : $modelDemandeur->getEmptyData();
		$promoteurEmptyData              = $defaultPromoteurData = $modelPromoteur->getEmptyData();
		$demandeurData                   = ($demandeurFromAccount)?$demandeurFromAccount->toArray()   : array_merge($demandeurEmptyData,$myData);
		$demandeurData["country"]        = $country              = $demandeurData["nationalite"] = $this->_getParam("country", "bf");
		
		$demandeEmptyData                = $model->getEmptyData();
		$demandeEmptyData["numero"]      = $model->reference();
		$defaultData                     = array_merge($demandeEmptyData,$myData, $demandeurData);
		$defaultData["localiteid"]       = intval($this->_getParam("localiteid" , 0));
		$defaultData["demandeurid"]      = $demandeurid;
		$defaultData["promoteurid"]      = $promoteurid  = intval($this->_getParam("promoteurid" , 0));
		$defaultData["entrepriseid"]     = $entrepriseid = intval($this->_getParam("entrepriseid", 0));
		$defaultData["disponible"]       = 0;
		$defaultData["expired"]          = 0;
		$defaultData["date_year"]        = date("Y");
		$defaultData["date_month"]       = date("m");
		$defaultData["date_day"]         = date("d");
		$defaultData["keywords"]         = $defaultData["nomcommercial"] = $defaultData["denomination"] = $keywords;
		$defaultData["telephone"]        = $me->get("phone1");
		$defaultData["adresse"]          = $me->get("address");
		$DemandeurIdentity               = null;
		$DemandeurIdentityData           = $modelIdentite->getEmptyData();
		if(!intval($demandeurid)) {
			$defaultData["demandeur_date_etablissement_day"]       = $defaultData["demandeur_date_etablissement_month"] = $defaultData["demandeur_date_etablissement_year"] = 0;
		    $defaultData["demandeur_date_etablissement"]           = "";
			$defaultData["demandeur_organisme_etablissement"]      = "";
			$defaultData["demandeur_lieu_etablissement"]           = "";
			$defaultData["demandeur_numidentite"]                  = "";
			$defaultData["demandeur_identitetype"]                 = 1;
		}elseif( $demandeurFromAccount && isset($demandeurFromAccount->identityid)) {
			$DemandeurIdentity                                     = $modelIdentite->findRow($demandeurFromAccount->identityid,"identityid",null,false);
			$DemandeurIdentityData                                 =($DemandeurIdentity)?$DemandeurIdentity->toArray() : array();
			 
			$defaultData["demandeur_organisme_etablissement"]      =($DemandeurIdentity)? $DemandeurIdentity->organisme_etablissement                : "";
		    $defaultData["demandeur_lieu_etablissement"]           =($DemandeurIdentity)? $DemandeurIdentity->lieu_etablissement                     : "";
		    $defaultData["demandeur_date_etablissement"]           =($DemandeurIdentity)? $DemandeurIdentity->date_etablissement                     : "";
			$defaultData["demandeur_identite_numero"]              = $defaultData["numidentite"]  = ($DemandeurIdentity)?$DemandeurIdentity->numero : "";
		    $defaultData["demandeur_identitetype"]                 = $defaultData["identitetype"] = ($DemandeurIdentity)?$DemandeurIdentity->typeid : 1;
		    $defaultData["demandeur_numidentite"]                  = $defaultData["numidentite"];
			if( $DemandeurIdentity && !empty($defaultData["demandeur_date_etablissement"])) {
				$zendDateEtablissement                             = new Zend_Date($DemandeurIdentity->date_etablissement,"YYYY-MM-dd");
			    $defaultData["demandeur_date_etablissement_year"]  = ($zendDateEtablissement)? $zendDateEtablissement->get(Zend_Date::YEAR) : 0;
			    $defaultData["demandeur_date_etablissement_month"] = ($zendDateEtablissement)? $zendDateEtablissement->toString("MM")       : 0;
			    $defaultData["demandeur_date_etablissement_day"]   = ($zendDateEtablissement)? $zendDateEtablissement->toString("dd")       : 0;			    					
			}
			$promoteurRow                                          = $modelPromoteur->findRow( $demandeurFromAccount->identityid,"identityid",null,false);
			if( $promoteurRow && !isset($postData["date_etablissement"]) ) {
				$promoteurData                                     = $promoteurRow->toArray();
				$defaultData                                       = (isset($promoteurData["lastname"]))?array_merge($defaultData,$promoteurData) : $defaultData;
				$defaultData["date_etablissement"]                 = $defaultData["demandeur_date_etablissement"];
				$defaultData["date_etablissement_year"]            = $defaultData["demandeur_date_etablissement_year"];
				$defaultData["date_etablissement_month"]           = $defaultData["demandeur_date_etablissement_month"];
				$defaultData["date_etablissement_day"]             = $defaultData["demandeur_date_etablissement_day"];
				$defaultData["organisme_etablissement"]            = $defaultData["demandeur_organisme_etablissement"];
				$defaultData["lieu_etablissement"]                 = $defaultData["demandeur_lieu_etablissement"];
				$defaultData["numidentite"]                        = $defaultData["demandeur_identite_numero"];
			}
			if( isset($defaultData["identityid"]) ) {
				unset($defaultData["identityid"]);
			}
		}
		
		$this->view->demandeurid         = $demandeurid;
		$this->view->keywords            = $keywords;
		$this->view->formes              = $formes                 = $modelEntrepriseForme->getSelectListe("Selectionnez une forme juridique"        , array("formid"    , "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines            = $domaines               = $modelDomaine->getSelectListe(        "Sélectionnez un secteur d'activité"      , array("domaineid" , "libelle"), array() , null , null , false );
		$this->view->localites           = $localites              = $modelLocalite->getSelectListe(       "Sélectionnez une juridiction"            , array("localiteid", "libelle"), array() ,null , null , false );
		$this->view->countries           = $countries              = $modelCountry->getSelectListe(        "Selectionnez un pays"                    , array("code"      , "libelle"), array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes       = $identiteTypes          = $modelIdentiteType->getSelectListe(   "Selectionnez un type de pièce d'identité", array("typeid"    , "libelle"), array() , null , null , false );
		
		if( $this->_request->isPost()  ) {
			$postData                    = array_merge($defaultData, $this->_request->getPost());
			$demandeurData               = array_merge($demandeurEmptyData,array_intersect_key($postData,$demandeurEmptyData));
			$insert_data                 = $demandeData           = array_merge($demandeEmptyData,array_intersect_key($postData,$demandeEmptyData));
			$defaultIdentityData         = $modelIdentite->getEmptyData();
			$defaultEntrepriseData       = $modelEntreprise->getEmptyData();
 
			$entrepriseData              = array_merge($defaultEntrepriseData, array_intersect_key($postData, $defaultEntrepriseData));
			$pieceIdentityData           = array_merge($defaultIdentityData  , array_intersect_key($postData, $defaultIdentityData ));			
			$promoteurData               = array_merge($defaultPromoteurData , array_intersect_key($postData, $defaultPromoteurData));
					
			$modelTable                  = $model->getTable();
			$dbAdapter                   = $modelTable->getAdapter();
			$prefixName                  = $modelTable->info("namePrefix");
			$tableName                   = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator              = new Sirah_Validateur_Email();
			
			/*
			$gReCaptchaResponse	         = (isset($postData["g-recaptcha-response"]))? $postData["g-recaptcha-response"]  : null;
			if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
				$errorMessages[]         =  "Veuillez valider le captcha de sécurité";
			} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse)) {
				$recaptcha               = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
				$recaptchaResponse       = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
				if(!$recaptchaResponse->isSuccess()) {
					$errorMessages[]     = "Captcha Invalide !";
				}
			}	*/		
			if( isset($pieceIdentityData["identityid"])){
				unset($pieceIdentityData["identityid"]);
			}
			$insert_data["numero"]       = $model->reference(); 
			$pieceIdentityData["numero"] = (isset($postData["numidentite"]) && !empty($postData["numidentite"]))? $stringFilter->filter($postData["numidentite"]) : $defaultData["numidentite"];
			$insert_data["demandeurid"]  = $demandeurid;
			$insert_data["promoteurid"]  = (isset($postData["promoteurid"] ))? intval($postData["promoteurid"])             : $defaultData["promoteurid"];
			$insert_data["entrepriseid"] = (isset($postData["entrepriseid"]))? intval($postData["entrepriseid"])            : 0;
			$insert_data["localiteid"]   = (isset($postData["localiteid"]  ))? intval($postData["localiteid"])              : 0;
			$insert_data["keywords"]	 = (isset($postData["keywords"]    ))? $stringFilter->filter($postData["keywords"]) : $keywords;
			$insert_data["registreid"]   = 0;
			$insert_data["periodid"]     = 0;			
			$insert_data["libelle"]      = "";			
			$insert_data["objet"]        = "";
			$insert_data["typeid"]       = (isset($postData["personne_morale"]) && (intval($postData["personne_morale"])==1))? 2 : 1;
			$insert_data["statutid"]     = 7;
			$insert_data["date"]         = $insert_data["creationdate"] = time();
			if(!isset($postData["demandeurid"]) && intval($demandeurid)) {
				$postData["demandeurid"] = $insert_data["demandeurid"]  = $demandeurid;
			}		
            if( isset($postData["demandeur_date_etablissement"])   && Zend_Date::isDate( $postData["demandeur_date_etablissement"],"dd/mm/YYYY")) {
			    $zendDateDemandeur                                      = new Zend_Date( $postData["demandeur_date_etablissement"],"dd/mm/YYYY");
				if( $zendDateDemandeur ) {
					$postData["demandeur_date_etablissement_year"]      = $zendDateDemandeur->get(Zend_Date::YEAR);
					$postData["demandeur_date_etablissement_month"]     = $zendDateDemandeur->toString("MM");
					$postData["demandeur_date_etablissement_day"]       = $zendDateDemandeur->toString("dd");
					$postData["demandeur_date_etablissement"]           = $zendDateDemandeur->toString("YYYY-MM-dd");
				}
			}				
			if( isset($postData["date_etablissement"]) && Zend_Date::isDate($postData["date_etablissement"],"dd/mm/YYYY")) {
			    $zendDatePromoteur                               = new Zend_Date( $postData["date_etablissement"],"dd/mm/YYYY");
				if( $zendDatePromoteur ) {
					$postData["date_etablissement_year"]         = $zendDatePromoteur->get(Zend_Date::YEAR);
					$postData["date_etablissement_month"]        = $zendDatePromoteur->toString("MM");
					$postData["date_etablissement_day"]          = $zendDatePromoteur->toString("dd");
					$postData["date_etablissement"]              = $zendDatePromoteur->toString("YYYY-MM-dd");
				}
			}
			if(!intval($demandeurid) || !intval($insert_data["demandeurid"])) {
				$DemandeurIdentityData                           = $pieceIdentityData;
				$demandeurIdentityId                             = 0;
				$postData["demandeur_identitetype"]              = (isset($postData["demandeur_identitetype"]            ))? $postData["demandeur_identitetype"]              : $defaultData["demandeur_identitetype"];
				$postData["demandeur_numidentite"]               = (isset($postData["demandeur_numidentite"]             ))? $postData["demandeur_numidentite"]               : $defaultData["demandeur_numidentite"];
				$postData["demandeur_lieu_etablissement"]        = (isset($postData["demandeur_lieu_etablissement"]      ))? $postData["demandeur_lieu_etablissement"]        : $defaultData["demandeur_lieu_etablissement"];
				$postData["demandeur_organisme_etablissement"]   = (isset($postData["demandeur_organisme_etablissement"] ))? $postData["demandeur_organisme_etablissement"]   : $defaultData["demandeur_organisme_etablissement"];
				$postData["demandeur_date_etablissement"]        = (isset($postData["demandeur_date_etablissement"]      ))? $postData["demandeur_date_etablissement"]        : $defaultData["demandeur_date_etablissement"];
				$postData["demandeur_date_etablissement_year"]   = (isset($postData["demandeur_date_etablissement_year"] ))? $postData["demandeur_date_etablissement_year"]   : $defaultData["demandeur_date_etablissement_year"];
				$postData["demandeur_date_etablissement_month"]  = (isset($postData["demandeur_date_etablissement_month"]))? $postData["demandeur_date_etablissement_month"]  : $defaultData["demandeur_date_etablissement_month"];
				$postData["demandeur_date_etablissement_day"]    = (isset($postData["demandeur_date_etablissement_day"]  ))? $postData["demandeur_date_etablissement_day"]    : $defaultData["demandeur_date_etablissement_day"];
				
				if((empty($postData["demandeur_date_etablissement"]) || !Zend_Date($postData["demandeur_date_etablissement"],"YYYY-MM-dd")) && isset($postData["demandeur_date_etablissement_year"]) && isset($postData["demandeur_date_etablissement_month"]) && isset($postData["demandeur_date_etablissement_day"])) {
				    $zendIdentityDate                            = new Zend_Date(array("year" => intval($postData["demandeur_date_etablissement_year"]),
															                           "month"=> intval($postData["demandeur_date_etablissement_month"]),
															                           "day"  => intval($postData["demandeur_date_etablissement_day"])));						
				    $postData["demandeur_date_etablissement"]    = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : $defaultData["demandeur_date_etablissement"];
				}				
				if( isset($identiteTypes[$postData["demandeur_identitetype"]]) && intval($postData["demandeur_identitetype"])) {
					$DemandeurIdentityData["typeid"]             = intval($postData["demandeur_identitetype"]);
				} else {
					$errorMessages[]                             = "Veuillez sélectionner votre type de pièce";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_numidentite"]) ) {
					$DemandeurIdentityData["numero"]             = $stringFilter->filter( $postData["demandeur_numidentite"] );
				} else {
					$errorMessages[]                             = "Veuillez saisir le numéro de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_organisme_etablissement"]) ) {
					$DemandeurIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["demandeur_organisme_etablissement"] );
				} else {
					$errorMessages[]                             = "Veuillez saisir l'organisme d'établissement de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_lieu_etablissement"]) ) {
					$DemandeurIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["demandeur_lieu_etablissement"] );
				} else {
					$errorMessages[]                             = "Veuillez saisir le lieu d'établissement de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_date_etablissement"]) && Zend_Date::isDate($postData["demandeur_date_etablissement"], "YYYY-MM-dd")) {
					$DemandeurIdentityData["date_etablissement"] = $stringFilter->filter( $postData["demandeur_date_etablissement"] );
				} else {
					$errorMessages[]                             = "Veuillez saisir la date d'établissement de votre carte d'identité/passport";
				}
				if( empty($errorMessages) ) {		
                    if(!empty($DemandeurIdentityData["date_etablissement"]) && Zend_Date::isDate($DemandeurIdentityData["date_etablissement"], "YYYY-MM-dd") && ($foundDemandeurIdentity=$modelIdentite->getRow($DemandeurIdentityData))) {
						$demandeurIdentityId                     = $foundDemandeurIdentity->identityid;
					} elseif($foundDemandeurIdentity             = $modelIdentite->findRow(strip_tags($DemandeurIdentityData["numero"]),"numero",null,false)) {
					    if(( $foundDemandeurIdentity->date_etablissement==$DemandeurIdentityData["date_etablissement"]) && ($foundDemandeurIdentity->organisme_etablissement==$DemandeurIdentityData["organisme_etablissement"]) &&
						   ( $foundDemandeurIdentity->lieu_etablissement==$DemandeurIdentityData["lieu_etablissement"])) {
							 $demandeurIdentityId                = $foundDemandeurIdentity->identityid;
						}
					}
					if(!intval($demandeurIdentityId)) {
						$cleanIdentityData                       = array_intersect_key($DemandeurIdentityData,$defaultIdentityData);
						$cleanIdentityData["creationdate"]       = time();
						$cleanIdentityData["creatorid"]          = $me->userid;
						$cleanIdentityData["updatedate"]         = $cleanIdentityData["updateduserid"] = 0;
						if( isset($cleanIdentityData["identityid"]) ) {
							unset($cleanIdentityData["identityid"]);
						}
						try {
							if( $dbAdapter->insert($prefixName."reservation_demandeurs_identite",$cleanIdentityData)) {
								$demandeurIdentityId             = $dbAdapter->lastInsertId();
							}
						} catch(Exception $e ) {
							$errorMessages[]                     = sprintf("Erreur Technique : les informations de l'identité du demandeur n'ont pas été enregistrées");
						}						
					}
                    if(!intval($demandeurIdentityId)) {
						$errorMessages[]                         = "Les informations de votre pièce d'identité n'ont pas pu être enregistrées";
					} else {
						if( $foundDemandeurByIdentity            = $modelDemandeur->findRow($demandeurIdentityId,"identityid",null,false)) {
							if(($me->lastname!=$foundDemandeurByIdentity->lastname) || ($me->firstname!= $foundDemandeurByIdentity->firstname)) {
							    $errorMessages[]                 = sprintf("Les références de la pièce d'identité du demandeur ne correspondent pas à votre identité.");
							}
						}
						if( empty($errorMessages) ) {
							$accountid                           = $me->userid;
							$memberName                          = sprintf("%s %s", $me->lastname,$me->firstname);
							$memberRow                           = $modelMember->findRow($accountid,"accountid",null,false);
							$accountData                         = $me->getData();
							if( $memberRow ) {
								$memberData                      = array_merge($accountData,$memberRow->toArray());
							} else {
								$memberData                      = $accountData;
							}
							$demandeurData                       = array_merge($demandeurEmptyData,array_intersect_key($memberData,$demandeurEmptyData));
							$demandeurData["name"]               = $memberName;
							$demandeurData["lastname"]           = $me->lastname;
							$demandeurData["firstname"]          = $me->firstname;
							$demandeurData["datenaissance"]      = (isset( $memberData["birthday"]         ))? $memberData["birthday"] : "";
							$demandeurData["lieunaissance"]      = (isset( $memberData["birthaddress"]     ))? $memberData["birthaddress"] : "";
							$demandeurData["telephone"]          = (!empty($memberData["tel2"]             ))? sprintf("%s/%s",$memberData["tel1"],$memberData["tel2"]) : sprintf("%s",$memberData["tel1"]);
							$demandeurData["adresse"]            = (isset( $memberData["address"]          ))? $memberData["address"]  : "";
							$demandeurData["profession"]         = (isset( $memberData["fonction"]         ))? $memberData["fonction"] : "";
							$demandeurData["numidentite"]        = (isset($DemandeurIdentityData["numero"] ))? sprintf("%s n° %s du %s par %s %s",$identiteTypes[$postData["demandeur_identitetype"]],$DemandeurIdentityData["numero"],$DemandeurIdentityData["date_etablissement"],$DemandeurIdentityData["organisme_etablissement"],$DemandeurIdentityData["lieu_etablissement"]) : "";
							$demandeurData["identityid"]         = $demandeurIdentityId;
							$demandeurData["accountid"]          = intval($accountid);
							$demandeurData["creationdate"]       = time();
							$demandeurData["creatorid"]          = 1;
							$demandeurData["updatedate"]         = 0;
							$demandeurData["updateduserid"]      = 0;
							$cleanDemandeurData                  = array_intersect_key($demandeurData,$demandeurEmptyData);
							try {
								$dbAdapter->delete(     $prefixName."reservation_demandeurs", array("identityid=?"=>$demandeurIdentityId));
								if(!$dbAdapter->insert( $prefixName."reservation_demandeurs", $cleanDemandeurData) )	{
									$errorMessages[]             = "Vos informations en tant que demandeur n'ont pas pu être enregistrées";
								} else {
									$demandeurid                 = $insert_data["demandeurid"] = $postData["demandeurid"] = $dbAdapter->lastInsertId();
									$dbAdapter->update( $prefixName."rccm_members", array("passport"=>$demandeurData["numidentite"]), array("accountid=?"=>intval($accountid)));
								}
							} catch(Exception $e ) {
								$errorMessages[]                 = sprintf("Les informations du demandeur n'ont pas pu être enregistrées");
							}							
						}
					}						
				}
			}			
            //On vérifie les informations de la référence de la pièce d'identité du promoteur
			if(!isset($postData["date_etablissement"]) || empty($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"],"YYYY-MM-dd")) {
			    $zendIdentityDate                             = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
													                                "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
													                                "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
			    $postData["date_etablissement"]               = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : $defaultData["date_etablissement"];
			}			
			if(!isset($identiteTypes[$postData["identitetype"]]) || !intval($postData["identitetype"]) ) {
				$errorMessages[]                              = "Veuillez renseigner le type de carte d'identité du promoteur";
			} else {
				$pieceIdentityData["typeid"]                  = intval($postData["identitetype"]);
			}
			if( isset($postData["numidentite"])) {
				$pieceIdentityData["numero"]                  = $postData["numidentite"];
			}
			if(!$strNotEmptyValidator->isValid($pieceIdentityData["numero"]) ) {
				$errorMessages[]                              = "Veuillez renseigner le numéro de la pièce d'identité du promoteur";
			}  
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                              = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["organisme_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
				$errorMessages[]                              = "Veuillez renseigner le lieu d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["lieu_etablissement"]      = $stringFilter->filter( $postData["lieu_etablissement"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["date_etablissement"]) || !Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
				$errorMessages[]                              = "Veuillez renseigner la date d'étalissement de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["date_etablissement"]      = $stringFilter->filter( $postData["date_etablissement"] );
			}
			$promoteurData["name"]                            = (isset($postData["name"]         ))? $stringFilter->filter($postData["name"])              : "";
			$promoteurData["country"]                         = (isset($postData["country"]      ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : "BF");
			$countryCallingCode                               = (!empty($promoteurData["country"]))? $modelCountry->callingCode($promoteurData["country"]) : '00226';			
			
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]                              = "Adresse invalide : veuillez sélectionner un pays de résidence valide";
			} else {
				$formatPhoneNumber                            = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $promoteurData["telephone"] ));
				$promoteurData["telephone"]                   = $formatPhoneNumber;
			}
			if( $emailValidator->isValid($promoteurData["email"])) {
				if( $existantClient                           = $modelPromoteur->findRow(trim($promoteurData["email"]), "email", null , false )) {
					$promoteurData["email"]                   = sprintf("%s@fichiernationalrccm.bf", time());
				} else {
					$promoteurData["email"]                   = $stringFilter->filter($promoteurData["email"]);
				}
			} else {
					$promoteurData["email"]                   = sprintf("%s@fichiernationalrccm.bf", time());
			}				 
			if(!$strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$errorMessages[]                              = " Veuillez entrer un numéro de téléphone valide";
			} elseif($existantPromoteur                       = $modelPromoteur->findRow(trim($promoteurData["telephone"]),"telephone",null, false ) ) {
				$promoteurid                                  = $existantPromoteur->promoteurid;
			} else {
				$promoteurData["telephone"]                   = $stringFilter->filter($promoteurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($promoteurData["name"]) && empty($promoteurData["lastname"])  && empty($promoteurData["firstname"])) {
				$fullNameArray                                = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"]                = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"]               = $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]                              = "Veuillez saisir le(s) prénom(s)";
			} else {
				$promoteurData["firstname"]                   = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]                              = "Veuillez saisir le nom de famille";
			} else {
				$promoteurData["lastname"]                    = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || (($promoteurData["sexe"]!="M")  && ($promoteurData["sexe"]!="F"))) {
				$errorMessages[]                              = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
				$promoteurData["sexe"]                        = $stringFilter->filter( $promoteurData["sexe"] );
			}
			if( empty($errorMessages)) {	
			    $selectIdentity                               = $dbAdapter->select()->from($prefixName."reservation_demandeurs_identite")
					                                                                ->where("numero=?",strip_tags($pieceIdentityData["numero"]))
																	                ->where("typeid=?",strip_tags($pieceIdentityData["typeid"]))
																					->where("organisme_etablissement=?",strip_tags($pieceIdentityData["organisme_etablissement"]))
																					->where("lieu_etablissement=?"     ,strip_tags($pieceIdentityData["lieu_etablissement"]))
													                                ->where("date_etablissement=?"     ,strip_tags($pieceIdentityData["date_etablissement"]));
				$foundIdentity                          = $dbAdapter->fetchRow($selectIdentity, array(), Zend_DB::FETCH_ASSOC);
                if( count($foundIdentity) && isset($foundIdentity["identityid"])) {
					$identityid                         = $promoteurData["identityid"]  = $foundIdentity["identityid"];
					$numidentite                        = $promoteurData["numidentite"] = sprintf("%s n° %s du %s par %s %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);	
				} else {
					$pieceIdentityData["creationdate"]  = time();
					$pieceIdentityData["creatorid"]     = $me->userid;
					$pieceIdentityData["updatedate"]    = 0;
					$pieceIdentityData["updateduserid"] = 0;
					if( isset($pieceIdentityData["identityid"]) ) {
						unset($pieceIdentityData["identityid"]);
					}
					try {
						if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $pieceIdentityData)  ) {
							$identityid                 = $promoteurData["identityid"]  = $dbAdapter->lastInsertId();
							$numidentite                = $promoteurData["numidentite"] = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]],$pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);					
						}
					} catch(Exception $e) {
						$errorMessages[]                = sprintf("Erreur Technique : les informations de l'identité du demandeur n'ont pas pu être enregistrées ");
					}
				}			
				//On s'assure qu'il n'y a pas usurpation d'identité de la part du promoteur
				if( $foundPromoteurByIdentity           = $modelPromoteur->findRow($identityid,"identityid",null,false)) {
					if(($foundPromoteurByIdentity->lastname!=$promoteurData["lastname"]) || ($foundPromoteurByIdentity->firstname!=$promoteurData["firstname"])) {
						$errorMessages[]                = sprintf("Les références de la pièce d'identité correspondent à celles d'un autre promoteur différent de %s %s", $promoteurData["lastname"],$promoteurData["firstname"]);
					}
				}
				if( empty($errorMessages) ) {
					$promoteurData["name"]                  = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
					$promoteurData["nationalite"]           = $stringFilter->filter($promoteurData["country"] );
					$promoteurData["adresse"]               = $stringFilter->filter($promoteurData["adresse"] );
					$promoteurData["profession"]            = $stringFilter->filter($promoteurData["profession"] );
					$promoteurData["avatar"]                = "";
					$promoteurData["creationdate"]          = time();
					$promoteurData["creatorid"]             = $me->userid;
					$promoteurData["updatedate"]            = 0;
					$promoteurData["updateduserid"]         = 0;
					if( $promoteurRow      = $modelPromoteur->findRow($promoteurData["numidentite"],"numidentite",null,false)) {
						$promoteurid                        = $insert_data["promoteurid"] = $promoteurRow->promoteurid;
					} elseif($promoteurRow = $modelPromoteur->findRow($promoteurData["telephone"],"telephone",null,false)) {
						$promoteurid                        = $insert_data["promoteurid"] = $promoteurRow->promoteurid;
					}  
					if( intval($promoteurid) && $promoteurRow) {
						/*if( isset($promoteurData["promoteurid"])) {
							$promoteurid            = intval($promoteurData["promoteurid"]);
							unset($promoteurData["promoteurid"]);
						}*/
						$promoteurData["updatedate"]        = time();
						$promoteurData["updateduserid"]     = $me->userid;
						$oldPromoteurData                   = $promoteurRow->toArray();
						$clean_promoteur_data               = array_merge($oldPromoteurData,array_intersect_key($promoteurData,$defaultPromoteurData));
						if( array_key_exists("promoteurid",$clean_promoteur_data) ) {
							unset($clean_promoteur_data["promoteurid"]);
						}
						if( isset($clean_promoteur_data["promoteurid"])) {
							unset($clean_promoteur_data["promoteurid"]);
						}
						//$promoteurRow->setFromArray($clean_promoteur_data);
						//$promoteurRow->save();
						//var_dump($clean_promoteur_data); die();
						try {
							$dbAdapter->update($prefixName."reservation_promoteurs",$clean_promoteur_data, array("promoteurid=?"=>intval($promoteurid)));
						} catch(Exception $e) {
							$errorMessages[]                = sprintf("Une erreur technique s'est produite dans la mise à jour des informations du promoteur");
						}						
					} else {
						try {
							if( isset($promoteurData["promoteurid"])) {
								unset($promoteurData["promoteurid"]);
							}
							$clean_promoteur_data           = array_intersect_key($promoteurData,$defaultPromoteurData);
							if( array_key_exists("promoteurid",$clean_promoteur_data) ) {
								$clean_promoteur_data["promoteurid"] = 0;
							}
							if( $dbAdapter->insert($prefixName."reservation_promoteurs",$clean_promoteur_data)) {
								$insert_data["promoteurid"] = $promoteurid = $dbAdapter->lastInsertId();
							} else {
								$errorMessages[]            = "Les informations du promoteur sont manquantes";
							}
						} catch(Exception $e ) {
						}						
					}
					$insert_data["promoteurid"]             = $promoteurid;
				}				
			}			 
			if(!intval($insert_data["promoteurid"]) && empty($errorMessages)) {
				$errorMessages[]                            = "Les informations du promoteur n'ont pas été renseignées";
			}
			if( empty( $insert_data["keywords"])) {
				$insert_data["keywords"]                    = $insert_data["objet"];
			} 	
            if(!isset($domaines[$postData["domaineid"]]) || !intval($postData["domaineid"])) {
				$errorMessages[]                            = "Veuillez préciser le secteur d'activité de l'entreprise";
			}
			if(!isset($localites[$postData["localiteid"]]) || !intval($postData["localiteid"])) {
				$errorMessages[]                            = "Veuillez sélectionner la juridiction/localité associée à votre demande";
			}
            if(!isset($formes[$postData["formid"]]) || !intval($postData["formid"])) {
				$errorMessages[]                            = "Veuillez renseigner une forme juridique de l'entreprise";
			}
			if( empty($errorMessages)) {
				$entrepriseRow                              = null;
				$entrepriseData["demandeid"]                = 0;			
				$entrepriseData["demandeurid"]              = $insert_data["demandeurid"];
				$entrepriseData["promoteurid"]              = $insert_data["promoteurid"];
				$entrepriseData["responsable"]              = $promoteurData["name"];
				$entrepriseData["catid"]                    = 0;
				$entrepriseData["localiteid"]               = $insert_data["localiteid"];
				$entrepriseData["domaineid"]                = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : 0;
				$entrepriseData["formid"]                   = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : 0;
				$entrepriseData["country"]                  = (isset($postData["country"]  ))? $stringFilter->filter($postData["country"])  : "BF";
				$entrepriseData["city"]                     = (isset($postData["city"]     ))? $stringFilter->filter($postData["city"])     : "OUA";
				$entrepriseData["address"]                  = (isset($postData["address"]  ))? $stringFilter->filter($postData["address"])  : $promoteurData["adresse"];
				$entrepriseData["activite"]                 = (isset($postData["activite"] ))? $stringFilter->filter($postData["activite"]) : "";
				$entrepriseData["numrccm"]                  = (isset($postData["numrccm"]  ))? $stringFilter->filter($postData["numrccm"])  : "";
				$entrepriseData["numcnss"]                  = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"])  : "";
				$entrepriseData["numifu"]                   = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"])   : "";
				$entrepriseData["telephone"]                = (isset($postData["telephone"]))? $stringFilter->filter($postData["telephone"]): "";
				$entrepriseData["email"]                    = (isset($postData["email"]    ))? $stringFilter->filter($postData["email"])    : "";
				$entrepriseData["reserved"]                 = 0;
				$entrepriseData["blacklisted"]              = 0;
				$entrepriseData["datecreation"]             =  $entrepriseData["datefermeture"] = "";
				$entrepriseData["creationdate"]             =  time();
				$entrepriseData["creatorid"]                =  $me->userid;
				$entrepriseData["updateduserid"]            =  $entrepriseData["updatedate"]    = 0;
				if( isset($postData["nomcommercial"]) && $strNotEmptyValidator->isValid($postData["nomcommercial"])) {
					$entrepriseData["nomcommercial"]        = $insert_data["objet"] = $stringFilter->filter($postData["nomcommercial"]);
				} else {
					$errorMessages[]                        = "Veuillez saisir le nom commercial de l'entreprise";
				}
				if( isset($postData["sigle"]) && $strNotEmptyValidator->isValid($postData["sigle"])) {
					$entrepriseData["sigle"]                = $stringFilter->filter($postData["sigle"]);
					$insert_data["objet"]                   = $insert_data["objet"]."(".$entrepriseData["sigle"].")";
				}
				if( empty( $errorMessages )) {
					if( intval($entrepriseid)) {
						if( isset($entrepriseData["entrepriseid"])) {
							unset($entrepriseData["entrepriseid"]);
						}
						try {
							$dbAdapter->update( $prefixName."reservation_demandes_entreprises",$entrepriseData, array("entrepriseid=?"=>intval($entrepriseid)));
						} catch(Exception $e) {
						}						
					} else {
						try {
							if( $dbAdapter->insert($prefixName."reservation_demandes_entreprises", $entrepriseData)) {
								$insert_data["entrepriseid"]   = $dbAdapter->lastInsertId();
								$entrepriseRow                 = $modelEntreprise->findRow($insert_data["entrepriseid"],"entrepriseid", null, false );
							} else {
								$errorMessages[]               = "Veuillez saisir les informations de l'entreprise";
							}
						} catch(Exception $e ) {
						}						
					}				
				}
				$insert_data["periodstart"]     = $insert_data["date"];			
				$insert_data["periodend"]       = $insert_data["periodstart"] + (3*24*3600);		
				$insert_data["personne_morale"] = (isset($postData["personne_morale"]))? intval($postData["personne_morale"])             : 0;
				$insert_data["observations"]    = (isset($postData["observations"]   ))? $stringFilter->filter($postData["observations"]) : "";
				$insert_data["expired"]         = 0;
				$insert_data["rejected"]        = 0;
				$insert_data["disponible"]      = 0;
				$insert_data["reject"]          = 0;
				$insert_data["motif_rejet"]     = "";
				$insert_data["creatorid"]       = $me->userid;
				$insert_data["creationdate"]    = time();	
				$insert_data["updatedate"]      = $insert_data["updateduserid"] = 0;
				
				if( empty($errorMessages)) {
					$insert_data["libelle"]     = sprintf("Demande de réservation du nom commercial %s", $insert_data["objet"] );
					$emptyData                  = $model->getEmptyData();
					$clean_insert_data          = array_intersect_key( $insert_data, $emptyData);
					$dataInserted               = false;
					try {
						$dataInserted           = $dbAdapter->insert($tableName, $clean_insert_data);
					} catch(Exception $e ) {
						$dataInserted           = false;
					}
					if( $dataInserted ) {
						$demandeid                         = $dbAdapter->lastInsertId();								
						if( $entrepriseRow ) {
							$entrepriseRow->demandeid      = $demandeid;
							$entrepriseRow->save();
						}
						$verification_data                 = array("verificationid"=>$demandeid,"demandeurid"=>$insert_data["demandeurid"],"disponible"=>0,"sources"=>"","taux_disponibilite"=>0);
						$verification_data["creatorid"]    = $me->userid;
						$verification_data["creationdate"] = time();
						$verification_data["updatedate"]   = $verification_data["updateduserid"] = 0;
						$dbAdapter->delete(    $prefixName."reservation_demandes_verifications", array("verificationid=?"=>$demandeid));
						if( $dbAdapter->insert($prefixName."reservation_demandes_verifications", $verification_data )) {
							$successMessage                = "Votre demande de réservation a été enregistrée avec succès. Veuillez procéder au paiement pour valider la réservation.";
							
							//On supprime la clé de reservation dans le store
							$verificationStateStoreArray   = $verificationStateStore->verificationstate;
         
							if( false!=$reservationKey && isset($verificationStateStoreArray["availables"][$reservationKey])) {
								$verificationStateStoreArray["availables"][$reservationKey] = null;
							} elseif(isset($verificationStateStoreArray["availables"][0])) {
								unset($verificationStateStoreArray["availables"][0]);
							}
							if( $this->_request->isXmlHttpRequest() ) {
								$this->_helper->viewRenderer->setNoRender(true);
								$this->_helper->layout->disableLayout(true);
								echo ZendX_JQuery::encodeJson(array("success"=>$successMessage));
								exit;
							}
							$application                   = new Zend_Session_Namespace("erccmapp");
							if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
								$application->returnToUrl  = "";
							}
							$this->setRedirect($successMessage, "success" );
							$this->redirect("public/ordercart/addrequest/demandeid/".$demandeid);
						}										
					}  else {
						if( $this->_request->isXmlHttpRequest() ) {						
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement de la demande a echoué"));
							exit;
						}
						$this->setRedirect("L'enregistrement de la demande a echoué", "error");
						$this->redirect("public/reservations/list")	;
					}
				}                 				
			}
			
			if( count($errorMessages)) {				
				$defaultData  = array_merge( $defaultData, $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
						 $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		//print_r($errorMessages); die();
		$this->view->title        = sprintf("Réservation du nom commercial <strong><u>`%s`</u></strong>", $keywords); 
		$this->view->data         = $defaultData;
		$this->view->identitydata = $DemandeurIdentityData;
	}
	
	public function listAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}
		$this->view->title     = "Mes demandes de vérifications et de réservations denoms commerciaux";
		
		$model                 = $this->getModel("demande");	
        $modelStatut           = $this->getModel("demandestatut");
		$me                    = Sirah_Fabric::getUser();
		
		$demandes              = array();
		$paginator             = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10;
		
		$filters               = array("page"=>$pageNum,"maxitems"=>$pageSize,"searchQ"=>null,"numero"=>null,"promoteurid"=>0,"typeid"=>0,"statutid"=>0,"expired"=>4,"disponible"=>4,"date"=>null,"promoteurname"=>null,"nomcommercial"=>null);
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$filters["creatorid"]  = $me->userid;
		$demandes              = $model->getList($filters,$pageNum, $pageSize);
		$paginator             = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->demandes  = $demandes;
		$this->view->statuts   = $modelStatut->getSelectListe("Sélectionnez un statut", array("statutid","libelle"), array(),null,null,false ); 
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $this->view->maxitems = $pageSize;
	}
	
	
	public function editAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}
		$demandeid                     = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}				
		$model                    = $modelDemande = $this->getModel("demande");	
		$modelEntreprise          = $this->getModel("demandentreprise");
		$modelRetry               = $this->getModel("demanderetry");
		
		$demande                  = $model->findRow($demandeid,"demandeid", null, false );	
        $demandeEntreprise        = $modelEntreprise->findRow($demandeid,"demandeid", null, false );		
		if(!$demande || !$demandeEntreprise) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		$application             = new Zend_Session_Namespace("erccmapp");
		$params                  = $this->_request->getParams();
		$postData                = $this->_request->getPost();
		$available               = 0;
		$similarites             = $similariteActivities = $registres = array();
		$verificationStateStore  = new Zend_Session_Namespace("Statestore");
	 
		if(($demande->statutid == 4) || ($demande->statutid==8) || ($demande->statutid==1)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Vous ne pouvez pas modifier les informations de cette demande"));
				exit;
			}
			$this->setRedirect("Vous ne pouvez pas modifier les informations de cette demande", "error");
			$this->redirect("public/requetes/infos?demandeid=".$demandeid);
		}
		$me                                          = Sirah_Fabric::getUser();
		
 
		$modelMember                                 = $this->getModel("member");
        $modelDemandeur                              = $this->getModel("demandeur");
		$modelPromoteur                              = $this->getModel("promoteur");
		$promoteurData                               = array("name"=>"","numidentite"=>"","lastname"=>"","firstname"=>"","promoteurid"=>intval($demande->promoteurid));
		if( intval($demande->promoteurid)) {
			$promoteurRow                            = $modelPromoteur->findRow(intval($demande->promoteurid),"promoteurid",null,false);
		    if( intval($promoteurRow) ) {
				$promoteurData                       = $promoteurRow->toArray();
			}			
		}
 
        $lastRetry                                   = $model->lastretry($demandeid,"array");
		$entrepriseData                              = $demandeEntreprise->toArray();
		$defaultData                                 = $demande->toArray();
		$defaultData["ancien_nom"]                   = (isset($lastRetry["nouveau_nom"]          ))? $lastRetry["nouveau_nom"]           : $defaultData["keywords"];
		$defaultData["ancien_denomination"]          = (isset($lastRetry["nouveau_denomination"] ))? $lastRetry["nouveau_denomination"]  : $defaultData["denomination"];
		$defaultData["ancien_sigle"]                 = (isset($lastRetry["nouveau_sigle"]        ))? $lastRetry["nouveau_sigle"]         : $defaultData["sigle"];
		$defaultData["nouveau_nom"]                  = $defaultKeywords;
		$defaultData["nouveau_denomination"]         = (isset($params["denomination"]            ))? $params["denomination"]             : "";
		$defaultData["nouveau_sigle"]                = (isset($params["sigle"]                   ))? $params["sigle"]                    : "";
		$errorMessages                               = array();
		$emptyData                                   = array("demandeid"=>$demandeid,"demandeurid"=>$demande->demandeurid,"promoteurid"=>$demande->promoteurid,"date"=>time(),"creationdate"=>time(),"creatorid"=>0,"updatedate"=>0,"updateduserid"=>0,
		                                                     "ancien_nom"=>"","nouveau_nom"=>"","nouveau_denomination"=>"","nouveau_sigle"=>"","ancien_sigle"=>"","ancien_denomination"=>"","processed"=>0,"validated"=>0,"nbreEssais"=>0);
		$defaultData                                 = array_merge($emptyData, $defaultData, $entrepriseData);
		if( $this->_request->isPost() ) {
			$postData                                = $this->_request->getPost();
			$insert_data                             = array_intersect_key($postData, $emptyData);
			
			$modelTable                              = $model->getTable();
			$dbAdapter                               = $modelTable->getAdapter();
			$prefixName                              = $modelTable->info("namePrefix");
			$tableName                               = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                            = new Zend_Filter();
			$stringFilter->addFilter(                  new Zend_Filter_StringTrim());
			$stringFilter->addFilter(                  new Zend_Filter_StripTags());
			$strNotEmptyValidator                    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));			
			
			$insert_data["demandeid"]                = $demandeid;
			$insert_data["demandeurid"]              = $defaultData["demandeurid"];
			$insert_data["promoteurid"]              = $defaultData["promoteurid"];
			$insert_data["ancien_nom"]               = (isset($postData["ancien_nom"]          ))? $stringFilter->filter($postData["ancien_nom"])            : $defaultData["ancien_nom"];
		    $insert_data["ancien_denomination"]      = (isset($postData["ancien_denomination"] ))? $stringFilter->filter($postData["ancien_denomination"])   : $defaultData["ancien_denomination"];
			$insert_data["ancien_sigle"]             = (isset($postData["ancien_sigle"]        ))? $stringFilter->filter($postData["ancien_sigle"])          : $defaultData["ancien_sigle"];
			$insert_data["nouveau_nom"]              = (isset($postData["nouveau_nom"]         ))? $stringFilter->filter($postData["nouveau_nom"])           : $defaultData["nouveau_nom"];
		    $insert_data["nouveau_denomination"]     = (isset($postData["nouveau_denomination"]))? $stringFilter->filter($postData["nouveau_denomination"])  : $defaultData["nouveau_denomination"];
			$insert_data["nouveau_sigle"]            = (isset($postData["nouveau_sigle"]       ))? $stringFilter->filter($postData["nouveau_sigle"])         : $defaultData["nouveau_sigle"];
		    
			$insert_data["processed"]                = 0;
			$insert_data["validated"]                = 0;
			$insert_data["nbreEssais"]               = 1;
			$insert_data["date"]                     = $insert_data["creationdate"] = time();
			$insert_data["creatorid"]                = $me->userid;
			$insert_data["updateduserid"]            = $insert_data["updatedate"]   = 0;
			
			if( empty($insert_data["nouveau_nom"]) ) {
				$errorMessages[]                     = "Veuillez saisir un nom commercial valide";
			} elseif(($insert_data["nouveau_nom"]==$insert_data["ancien_nom"]) || ($insert_data["nouveau_nom"]==$demande->objet)) {
				$errorMessages[]                     = "Veuillez saisir un nouveau nom commercial différent de l'ancien";
			} else {
				if( $foundRetry = $modelRetry->findRow($insert_data["nouveau_nom"],"nouveau_nom",null,false)) {
					$errorMessages[]                 = sprintf("Le nom commercial %s existe déjà.", $insert_data["nouveau_nom"]);              
			    }
			}			
			if( empty($insert_data["nouveau_denomination"]) ) {
				$insert_data["nouveau_denomination"] = $insert_data["nouveau_nom"];
			}
			if( empty($insert_data["nouveau_sigle"]) ) {
				$insert_data["nouveau_sigle"]        = $insert_data["ancien_sigle"];
			}
			$foundDemandes                           = (!empty($insert_data["nouveau_nom"]))?$modelDemande->getList(array("searchQ"=>$insert_data["nouveau_nom"],"statutid"=>4)) : array();
			if( count($foundDemandes) ) {
				$errorMessages[]                     = sprintf("Des demandes de réservations existantes reservées portent le nom commercial %s", $insert_data["nouveau_nom"]);
			}
			if( empty($errorMessages) ) {
				$clean_insert_data                   = array_intersect_key( $insert_data, $emptyData);
				try {
					$nbreEssais                      = $model->countretries($demandeid);
					if( intval($nbreEssais) ) {
						$nbreEssais++;
					} else {
						$nbreEssais                     = 1;
					}
					$insert_data["nbreEssais"]          = $nbreEssais;
					if( $dbAdapter->insert( $prefixName."reservation_demandes_retries", $clean_insert_data)) {
						if(!empty($insert_data["nouveau_sigle"])) {
							$insert_data["nouveau_nom"] = sprintf("%s(%s)", $insert_data["nouveau_nom"], $insert_data["nouveau_sigle"]);
						}						
						$updatedData                    = array("statutid"=>8,"retries"=>$nbreEssais,"objet"=>$insert_data["nouveau_nom"],"denomination"=>$insert_data["nouveau_denomination"],"keywords"=>$insert_data["nouveau_nom"],"expired"=>0);
						$updatedData["libelle"]         = sprintf("Nouvel essai de réservation du nom commercial %s",$insert_data["nouveau_nom"]);
						$updatedData["updatedate"]      = time();
						$updatedData["updateduserid"]   = $me->userid;
						$updatedData["observations"]    = sprintf("Cette demande a été réessayée %d fois et pour la dernière fois le %s", $nbreEssais, date("d/m/Y"));
						$entrepriseUpdatedData          = array("nomcommercial"=>$insert_data["nouveau_nom"],"denomination"=>$insert_data["nouveau_denomination"]);
						if( isset($postData["domaineid"]) && (intval($postData["domaineid"])!=$entrepriseData["domaineid"])) {
							$entrepriseUpdatedData["domaineid"] = intval($postData["domaineid"]);
						}
						if( isset($postData["formid"])    && (intval($postData["formid"])!=$entrepriseData["formid"])) {
							$entrepriseUpdatedData["formid"]    = intval($postData["formid"]);
						}
						$dbAdapter->update( $prefixName."reservation_demandes"            , $updatedData          , array("demandeid=?"=>$demandeid));
						$dbAdapter->update( $prefixName."reservation_demandes_entreprises", $entrepriseUpdatedData, array("demandeid=?"=>$demandeid));
					} else {
						$errorMessages[]             = sprintf("Votre requête n'a pas pu être enregistrée.");
					}
				} catch(Exception $e ) {
					$errorMessages[]                 = sprintf("Une erreur technique a été détectée.");
				}				
			}
			if( count($errorMessages)) {				
				$defaultData                         = array_merge( $defaultData, $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
						 $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data                        = $defaultData;
		$this->view->lastretry                   = $lastRetry;
		$this->view->demandeid                   = $demandeid;
	}
	
	public function retryAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	     
		$this->view->title             = "Réessayer un nouveau nom commercial";
		$demandeid                     = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}				
		$model                    = $modelDemande = $this->getModel("demande");	
		$modelEntreprise          = $this->getModel("demandentreprise");
		$modelEntrepriseForme     = $this->getModel("entrepriseforme");
		$modelDomaine             = $this->getModel("domaine");
		$modelLocalite            = $this->getModel("localite");
		$modelRetry               = $this->getModel("demanderetry");
		
		$demande                  = $model->findRow($demandeid,"demandeid", null, false );	
        $demandeEntreprise        = $modelEntreprise->findRow($demandeid,"demandeid", null, false );		
		if(!$demande || !$demandeEntreprise) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		$application             = new Zend_Session_Namespace("erccmapp");
		$params                  = $this->_request->getParams();
		$postData                = $this->_request->getPost();
		$keywords                = $query = (isset($params["keywords"]))?$params["keywords"] : (isset($params["query"])?urldecode($params["query"]) : "");
		$available               = 0;
		$similarites             = $similariteActivities = $registres = array();
		$verificationStateStore  = new Zend_Session_Namespace("Statestore");
		if(!isset($verificationStateStore->verificationstate)) {
			$verificationStateStore->verificationstate   = array("availables"=>array(),"unavailables"=>array());
		}
		if( empty($keywords) && isset($verificationStateStore->verificationstate["availables"][0]) ) {
			$keywords            = $query = $params["keywords"] = $verificationStateStore->verificationstate["availables"][0];
		}
		if( empty($keywords)) {
			$application->returnToUrl     = sprintf("public/requetes/retry/demandeid/".$demandeid);
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Veuillez procéder d'abord à la vérification du nouveau nom commercial"));
				exit;
			}
			$this->setRedirect("Veuillez procéder d'abord à la vérification du nouveau nom commercial", "error");
			$this->redirect("public/requetes/verify");
		}		
		$availables              = $verificationStateStore->verificationstate["availables"];
		$reservationKey          = array_search($keywords,$availables);
		if( false==$reservationKey && !in_array($keywords,$availables)) {
			$application->returnToUrl = sprintf("public/requetes/retry/demandeid/".$demandeid);
			$defaultKeywords     = urlencode($demande->objet);
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Veuillez procéder d'abord à la vérification du nouveau nom commercial"));
				exit;
			}
			$this->setRedirect("Veuillez procéder d'abord à la vérification du nouveau nom commercial", "error");
			$this->redirect("public/requetes/verify?keywords=".$defaultKeywords);
		}
		if( $demande->statutid==8 ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Une proposition de nom commercial soumise est en cours de traitement"));
				exit;
			}
			$this->setRedirect("Une proposition de nom commercial soumise est en cours de traitement", "error");
			$this->redirect("public/requetes/infos?demandeid=".$demandeid);
		}
		if(($demande->statutid==1) || ($demande->statutid==4) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Vous ne pouvez pas éditer cette demande car elle semble déjà traitée ou soumise à un traitement"));
				exit;
			}
			$this->setRedirect("Vous ne pouvez pas éditer cette demande car elle semble déjà traitée ou soumise à un traitement", "error");
			$this->redirect("public/requetes/infos?demandeid=".$demandeid);
		}
		$defaultKeywords                             = (isset($params["keywords"]) && !empty($params["keywords"]))?$params["keywords"] : ((isset($params["query"]) && !empty($params["query"]))?urldecode($params["query"]) : $defaultKeywords);
		$me                                          = Sirah_Fabric::getUser();
		 
		$modelMember                                 = $this->getModel("member");
        $modelDemandeur                              = $this->getModel("demandeur");
		$modelPromoteur                              = $this->getModel("promoteur");
		$promoteurid                                 = intval($demande->promoteurid);
		$demandeurid                                 = intval($demande->demandeurid);
		$promoteurData                               = array("name"=>"","numidentite"=>"","lastname"=>"","firstname"=>"","promoteurid"=>$promoteurid);
		if( intval($promoteurid)) {
			$promoteurRow                            = $modelPromoteur->findRow($promoteurid,"promoteurid",null,false);
		    if( intval($promoteurRow) ) {
				$promoteurData                       = $promoteurRow->toArray();
			}			
		}
        $lastRetry                                   = $model->lastretry($demandeid,"array");
		$entrepriseData                              = $demandeEntreprise->toArray();
		$defaultData                                 = $demande->toArray();
		$defaultData["ancien_nom"]                   = (isset($lastRetry["nouveau_nom"]          ))? $lastRetry["nouveau_nom"]           : $defaultData["keywords"];
		$defaultData["ancien_denomination"]          = (isset($lastRetry["nouveau_denomination"] ))? $lastRetry["nouveau_denomination"]  : $defaultData["denomination"];
		$defaultData["ancien_sigle"]                 = (isset($lastRetry["nouveau_sigle"]        ))? $lastRetry["nouveau_sigle"]         : $defaultData["sigle"];
		$defaultData["nouveau_nom"]                  = $defaultKeywords;
		$defaultData["nouveau_denomination"]         = (isset($params["denomination"]) && !empty($params["denomination"]))? $params["denomination"] : $defaultKeywords;
		$defaultData["nouveau_sigle"]                = (isset($params["sigle"]                   ))? $params["sigle"]                    : "";
		$errorMessages                               = array();
		$emptyData                                   = array("demandeid"=>$demandeid,"demandeurid"=>$demandeurid,"promoteurid"=>$promoteurid,"date"=>time(),"creationdate"=>time(),"creatorid"=>0,"updatedate"=>0,"updateduserid"=>0,
		                                                     "ancien_nom"=>"","nouveau_nom"=>"","nouveau_denomination"=>"","nouveau_sigle"=>"","ancien_sigle"=>"","ancien_denomination"=>"","processed"=>0,"validated"=>0,"nbreEssais"=>0);
		$defaultData                                 = array_merge($emptyData, $defaultData, $entrepriseData);
		if( $this->_request->isPost() ) {
			$postData                                = $this->_request->getPost();
			$insert_data                             = array_intersect_key($postData, $emptyData);
			
			$modelTable                              = $model->getTable();
			$dbAdapter                               = $modelTable->getAdapter();
			$prefixName                              = $modelTable->info("namePrefix");
			$tableName                               = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                            = new Zend_Filter();
			$stringFilter->addFilter(                  new Zend_Filter_StringTrim());
			$stringFilter->addFilter(                  new Zend_Filter_StripTags());
			$strNotEmptyValidator                    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));			
			
			$insert_data["demandeid"]                = $demandeid;
			$insert_data["demandeurid"]              = $defaultData["demandeurid"];
			$insert_data["promoteurid"]              = $defaultData["promoteurid"];
			$insert_data["ancien_nom"]               = (isset($postData["ancien_nom"]          ))? $stringFilter->filter($postData["ancien_nom"])            : $defaultData["ancien_nom"];
		    $insert_data["ancien_denomination"]      = (isset($postData["ancien_denomination"] ))? $stringFilter->filter($postData["ancien_denomination"])   : $defaultData["ancien_denomination"];
			$insert_data["ancien_sigle"]             = (isset($postData["ancien_sigle"]        ))? $stringFilter->filter($postData["ancien_sigle"])          : $defaultData["ancien_sigle"];
			$insert_data["nouveau_nom"]              = (isset($postData["nouveau_nom"]         ))? $stringFilter->filter($postData["nouveau_nom"])           : $defaultData["nouveau_nom"];
		    $insert_data["nouveau_denomination"]     = (isset($postData["nouveau_denomination"]))? $stringFilter->filter($postData["nouveau_denomination"])  : $defaultData["nouveau_denomination"];
			$insert_data["nouveau_sigle"]            = (isset($postData["nouveau_sigle"]       ))? $stringFilter->filter($postData["nouveau_sigle"])         : $defaultData["nouveau_sigle"];
		    
			$insert_data["processed"]                = 0;
			$insert_data["validated"]                = 0;
			$insert_data["nbreEssais"]               = 1;
			$insert_data["date"]                     = $insert_data["creationdate"] = time();
			$insert_data["creatorid"]                = $me->userid;
			$insert_data["updateduserid"]            = $insert_data["updatedate"]   = 0;
			
			if( empty($insert_data["nouveau_nom"]) ) {
				$errorMessages[]                        = "Veuillez saisir un nom commercial valide";
			} elseif(($insert_data["nouveau_nom"]==$insert_data["ancien_nom"]) || ($insert_data["nouveau_nom"]==$demande->objet)) {
				$errorMessages[]                        = "Veuillez saisir un nouveau nom commercial différent de l'ancien";
			} else {
				if( $foundRetry = $modelRetry->findRow($insert_data["nouveau_nom"],"nouveau_nom",null,false)) {
					$errorMessages[]                    = sprintf("Le nom commercial %s existe déjà.", $insert_data["nouveau_nom"]);              
			    }
			}			
			if( empty($insert_data["nouveau_denomination"]) ) {
				$insert_data["nouveau_denomination"]    = $insert_data["nouveau_nom"];
			}
			if( empty($insert_data["nouveau_sigle"]) ) {
				$insert_data["nouveau_sigle"]           = $insert_data["ancien_sigle"];
			}
			$foundDemandes                              = (!empty($insert_data["nouveau_nom"]))?$modelDemande->getList(array("searchQ"=>$insert_data["nouveau_nom"],"statutid"=>4)) : array();
			if( count($foundDemandes) ) {
				$errorMessages[]                        = sprintf("Des demandes de réservations existantes reservées portent le nom commercial %s", $insert_data["nouveau_nom"]);
			}
			if( empty($errorMessages) ) {
				$clean_insert_data                      = array_intersect_key( $insert_data, $emptyData);
				try {
					$nbreEssais                         = $model->countretries($demandeid);
					if( intval($nbreEssais) ) {
						$nbreEssais++;
					} else {
						$nbreEssais                     = 1;
					}
					$insert_data["nbreEssais"]          = $nbreEssais;
					if( $dbAdapter->insert( $prefixName."reservation_demandes_retries", $clean_insert_data)) {
						if(!empty($insert_data["nouveau_sigle"])) {
							$insert_data["nouveau_nom"] = sprintf("%s(%s)", $insert_data["nouveau_nom"], $insert_data["nouveau_sigle"]);
						}						
						$updatedData                    = array("statutid"=>8,"retries"=>$nbreEssais,"objet"=>$insert_data["nouveau_nom"],"denomination"=>$insert_data["nouveau_denomination"],"keywords"=>$insert_data["nouveau_nom"],"expired"=>0);
						$updatedData["libelle"]         = sprintf("Nouvel essai de réservation du nom commercial %s",$insert_data["nouveau_nom"]);
						$updatedData["updatedate"]      = time();
						$updatedData["updateduserid"]   = $me->userid;
						$updatedData["observations"]    = sprintf("Cette demande a été réessayée %d fois et pour la dernière fois le %s", $nbreEssais, date("d/m/Y"));
						$entrepriseUpdatedData          = array("nomcommercial"=>$insert_data["nouveau_nom"],"denomination"=>$insert_data["nouveau_denomination"]);
						if( isset($postData["domaineid"]) && (intval($postData["domaineid"])!=$entrepriseData["domaineid"])) {
							$entrepriseUpdatedData["domaineid"] = intval($postData["domaineid"]);
						}
						if( isset($postData["formid"])    && (intval($postData["formid"])!=$entrepriseData["formid"])) {
							$entrepriseUpdatedData["formid"]    = intval($postData["formid"]);
						}
						$dbAdapter->update( $prefixName."reservation_demandes"            , $updatedData          , array("demandeid=?"=>$demandeid));
						$dbAdapter->update( $prefixName."reservation_demandes_entreprises", $entrepriseUpdatedData, array("demandeid=?"=>$demandeid));
					} else {
						$errorMessages[]             = sprintf("Votre requête n'a pas pu être enregistrée.");
					}
				} catch(Exception $e ) {
					$errorMessages[]                 = sprintf("Une erreur technique a été détectée.");
				}				
			}
			if( count($errorMessages)) {				
				$defaultData                         = array_merge( $defaultData, $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
						 $this->_helper->Message->addMessage($message) ;
				}
			} else {
				$successMessage                  = sprintf("Votre nouveau nom commercial et dénomination sociale ont été mises à jour avec succès. Votre demande sera à nouveau transmise à l'agent compétent pour validation. Le traitement sera fait dans un délai de 48H au maximum.");
			    if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success"=>$successMessage));
					exit;
				}
				$this->setRedirect($successMessage, "success" );
				$this->redirect("public/requetes/infos/demandeid/".$demandeid);
			}
		}
		$this->view->data                        = $defaultData;
		$this->view->promoteurData               = $promoteurData;
		$this->view->lastretry                   = $lastRetry;
		$this->view->demandeid                   = $demandeid;
		$this->view->demandeurid                 = $demandeurid;
		$this->view->promoteurid                 = $promoteurid;
		$this->view->formes     = $formes        = $modelEntrepriseForme->getSelectListe("Selectionnez une forme juridique"        , array("formid"    , "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines   = $domaines      = $modelDomaine->getSelectListe(        "Sélectionnez un secteur d'activité"      , array("domaineid" , "libelle"), array() , null , null , false );
		$this->view->localites  = $localites     = $modelLocalite->getSelectListe(       "Sélectionnez une juridiction"            , array("localiteid", "libelle"), array() ,null , null , false );
		
	}
	
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}
		$demandeid                  = intval($this->_getParam("demandeid", $this->_getParam("id" ,0)));		
		if(!$demandeid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}				
		$model                         = $this->getModel("demande");	
		$modelEntrepriseForme          = $this->getModel("entrepriseforme");
		$modelDomaine                  = $this->getModel("domaine");
        $modelDemandeur                = $this->getModel("demandeur");
		$modelPromoteur                = $this->getModel("promoteur");
		$modelIdentiteType             = $this->getModel("usageridentitetype");
		
		$demande                       = $model->findRow($demandeid,"demandeid", null, false );		
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		$me                            = Sirah_Fabric::getUser();
		if( $demande->creatorid != $me->userid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Vous n'êtes pas autorisé à consulter les informations de cette demande"));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à consulter les informations de cette demande" , "error");
			$this->redirect("public/requetes/list");
		}
 		
		$demandeurid                   = $demande->demandeurid;
		$entrepriseid                  = $demande->entrepriseid;
		$promoteurid                   = $demande->promoteurid;		
		$demandeurRow                  = $demande->demandeur( $demandeurid);
		$promoteurRow                  = $demande->promoteur( $promoteurid);
		$entrepriseRow                 = $demande->entreprise($demandeid  );
		
        $this->view->demande           = $demande;
        $this->view->demandeid         = $demandeid;
        $this->view->demandeurid       = $demandeurid;
		$this->view->entrepriseid      = $entrepriseid;
        $this->view->promoteurIdentite = ( $promoteurRow->identityid)?$modelPromoteur->identite($promoteurRow->identityid) : null;		
        $this->view->demandeur         = $demandeurRow;	
        $this->view->promoteur         = $promoteurRow;	
        $this->view->entreprise        = $entrepriseRow;
		$this->view->domaineActivite   = ($entrepriseRow && $entrepriseRow->domaineid)? $modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
		$this->view->formeJuridique    = ($entrepriseRow && $entrepriseRow->formid   )? $modelEntrepriseForme->findRow($entrepriseRow->formid,"formid", null, false) : null;
		$this->view->statut            = $demande->findParentRow("Table_Demandestatuts");
		$this->view->localite          = $demande->findParentRow("Table_Localites");
		$this->view->documents         = $demande->documents($demandeid);
		$this->view->identiteTypes     = $identiteTypes  = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );		
		
		$typeOfDocument                = "default";
		$demandeState                  = "default";
		switch(intval($demande->statutid)) {
			case 0:
			case 1:
			default: 
			    $typeOfDocument        = "default";
		        $demandeState          = "default";
			break;
			case 2:
			    $typeOfDocument        = "disponibilite";
		        $demandeState          = "verified";
			break;
			case 3:
			    $typeOfDocument        = "indisponibilite";
				$demandeState          = "indisponiblite";
				break;
			case 4:
			    $typeOfDocument        = "reservation";
				$demandeState          = "reserved";
				break;
			case 5:
			    $typeOfDocument        = "rejet";
				$demandeState          = "rejected";
				break;
			case 6:
			    $typeOfDocument        = "rejet";
				$demandeState          = "canceled";
				break;
		}
        $this->view->state             = $demandeState;
		$this->view->documentype       = $typeOfDocument;
        $this->view->title             = ( $demande )? sprintf("Les informations de la demande %s ", $demande->libelle)	: "Les informations d'une demande";	
	} 	
	 
	
	
	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$demandeid             = intval($this->_getParam("demandeid", $this->_getParam("id" , 0)));
		if(!$demandeid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		$model                 = $modelDemande = $this->getModel("demande");
		$modelRequest          = $this->getModel("demanderequest");
		$modelRegistre         = $this->getModel("registre");
	
		$demande               = $model->findRow($demandeid, "demandeid" , null , false);
		$me                    = Sirah_Fabric::getUser();
		if(!$demande ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/requetes/list");
		}
		if( $demande->creatorid != $me->userid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Vous n'êtes pas autorisé à consulter cette information."));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à consulter cette information." , "error");
			$this->redirect("public/requetes/list");
		}
		$documents               = $demande->documents($demandeid);
		if(!count( $documents )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Le document auquel vous souhaitez accéder est indisponible."));
				exit;
			}
			$this->setRedirect("Le document auquel vous souhaitez accéder est indisponible.", "error");
			$this->redirect("public/requetes/infos/demandeid/".$demandeid);
		}
		$filename                = $documents[0]["filepath"];
		if( !file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'existe plus sur le serveur" , "error");
			$this->redirect("public/requetes/list");
		}
		$documentExtension        = strtolower( Sirah_Filesystem::getFilextension( $filename ) );
		$contentType              = "application/octet-stream";
		switch( $documentExtension ) {
			case "doc" :
			case "docx":
				$contentType      = "application/msword";
				break;
			case "pdf" :
				$contentType      = "application/pdf";
				break;
			case "xls":
			case "xlsx":
				$contentType      = "application/excel";
				break;
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
			case "bmp":
				$contentType      = "image/*";
				break;
			default:
				$contentType      = "application/octet-stream";
		}
		header('Content-Description: File Transfer');
		header('Content-Type: '.$contentType );
		header('Content-Disposition: attachment; filename='.basename( $filename ) );
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize( $filename ) );
		
		if( $content = ob_get_clean() ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Des entetes HTTP ont déjà été transmises" ) );
				exit;
			}
			echo "Des entetes HTTP ont déjà été transmises";
			exit;
		}
		flush();
		@readfile( $filename );
		exit;
	}
	 
	public function createAction()
	{
		$this->view->title    = "DEMANDE DE RENSEIGNEMENTS AU FN RCCM";

		$model                = $this->getModel("registre");
		$modelRequest         = $this->getModel("requete");
	    $defaultSession       = new Zend_Session_Namespace("INSCRIPTION");
	    $defaultData          = $modelRequest->getEmptyData();
		$registreid           = intval($this->_getParam("registreid", $this->_getParam("id", 0)));
	    $errorMessages        = array();
	    $typesDocuments       = $modelRequest->typedocuments();
		if(!$defaultSession->initialised ) {
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}		
		if( $this->_request->isPost() )  {
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ) {
				$defaultSession->token = Sirah_User_Helper::getToken(25).time();
				$defaultSession        = new Zend_Session_Namespace("captchacheck");
				$defaultSession->checks= 0;
				$urlDone               = $this->_helper->HttpUri(array("controller"=>"requetes", "action" => "create", "module" => "public"));
				$urlSecurityCheck      = $this->_helper->HttpUri(array("controller"=>"securitycheck", "action" => "captcha", "module" => "public", "params" => array("done" => $urlDone , "token" => $defaultSession->token )));
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide", "reload" => true, "newurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide","error");
				$this->redirect($urlSecurityCheck);
			}
			
			$postData            = $this->_request->getPost();					
			$me                  = Sirah_Fabric::getUser();
			$userTable           = $me->getTable();
			$dbAdapter           = $userTable->getAdapter();
			$prefixName          = $userTable->info("namePrefix");
			$stringFilter        = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$insert_data         = array();
		    $registreid          = (isset( $postData["registreid"] )) ? intval( $postData["registreid"] ) : 0;
			$registre            = null;
			if(!( $registre      = $model->findRow( $registreid, "registreid", null , false ) ) )   {
				$errorMessages[] = "Veuillez selectionner un registre valide";
			} else {
				$insert_data["registreid"]   = $registreid;
			}  		
		    $typedocumentVal                 = intval( $postData["typedocument"] );
			if( !$typedocumentVal || !isset( $typesDocuments[$typedocumentVal] )) {
				$errorMessages[]             = "Veuillez selectionner un type de document valide";
			} else {
				$insert_data["typedocument"] = $typedocumentVal; 
			}  
			$listRequests                    = $modelRequest->getList();
			$insert_data["requestoken"]      = sprintf("ReQ-%06d", (count( $listRequests) + 1) );
			$insert_data["userid"]           = $insert_data["creatorid"] = $me->userid;
			$insert_data["description"]      = ( isset( $postData["description"] )) ? $stringFilter->filter($postData["description"]) : "";
			$insert_data["creationdate"]     = time();
			$insert_data["updatedate"]       = 0;
			$insert_data["updateduserid"]    = 0;
			$insert_data["validated"]        = 0;
			$insert_data["accepted"]         = 0;

			$searchRequest                   = $dbAdapter->select()->from(array("R" => $prefixName ."rccm_access_requests"),array("R.requestid"))
			                                                       ->where("R.userid = ?", $me->userid)
			                                                       ->where("R.typedocument = ?", $typedocumentVal)
																   ->where("R.registreid = ?", $registreid);
			if( count( $dbAdapter->fetchAll( $searchRequest )) ) {
				$errorMessages[]             = "Vous aviez déjà emis une demande d'accès portant sur ce document";
			}			
			if( empty( $errorMessages ) && isset( $registre->numero ) ) {
				if( $dbAdapter->insert( $prefixName . "rccm_access_requests", $insert_data ) ) {
					$config               = Sirah_Fabric::getConfig();
					$mailer               = Sirah_Fabric::getMailer();
				
					$defaultToEmail       = $config["resources"]["mail"]["defaultFrom"]["email"];
					$defaultToName        = $config["resources"]["mail"]["defaultFrom"]["name"];
				
					$typeDocument         = (isset( $typesDocuments[$typedocumentVal] )) ? $typesDocuments[$typedocumentVal] : "document complet";
					$clientMsg            = "<p> Bonjour Mr/Mrs ".$me->lastname." ".$me->firstname." <br/> </p>
					                         <p> Votre requête d'accès au type de document ".$typeDocument." du RCCM N° ".$registre->numero." a été enregistrée avec succès. <br/> </p>
					                         <p> Nous donnerons une suite dans un délai de 72 Heures .</p>
					                         <p> Nous vous remercions pour l'interêt à cette plateforme. </p>
					                         <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
					$msgSubject           = "Votre requête sur la plateforme du FNRCCM BF";
					$msgPartialData       = array("subject"        =>  $msgSubject,
							                      "message"        =>  $clientMsg,
							                      "logoMsg"        =>  APPLICATION_STRUCTURE_LOGO,
							                      "replyToEmail"   =>  $defaultToEmail,
							                      "replyToName"    =>  $defaultToName,
							                      "replyToTel"     =>  "",
							                      "replyToSiteWeb" =>  "http://www.fichiernationalrccm.com/about",
							                      "toName"         =>  sprintf("%s %s", $me->lastname, $me->firstname),
							                      "toEmail"        =>  $me->email );
					$msgBody        = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
					$mailer->setFrom( $defaultToEmail , "FNRCCM ");
					$mailer->setSubject( $msgSubject );
					$mailer->addTo( $me->email , stripslashes( $me->lastname ) );
					$mailer->setBodyHtml( $msgBody );
					try{
						$mailer->send();
					} catch(Exception $e) {
						$errorMessages[]     = "Nous avons tenté de vous transmettre un email sans succès, votre compte de messagerie semble inaccessible, vérifiez votre connexion internet et reprenez l'opération.";
						if( APPLICATION_DEBUG ) {
							$errorMessages[] = " Informations de débogages : ".$e->getMessage();
						}
					}					
					//On envoie un message à l'administrateur
					$adminMsg                = "<p> Bonjour Mr/Mrs ".$defaultToName."  <br/> </p>
					                            <p> Une requête de demande d'accès portant sur le document ".$typeDocument." du RCCM numéro ".$registre->numero." a été formulée.
					                              Merci de prendre en charge le traitement de cette demande dans un délai de 72 Heures 
					                              <a title=\"Cliquez sur ce lien\" href=\"".$linkLogin."\">  en cliquant sur ce lien </a>. </p>
					                            <p> <b><i> Cordialement, </i></b>  Message automatisé </p>";
					$msgSubject              = sprintf("FNRCCM : Nouvelle requête de demande d'accès au RCCM N° %s", $registre->numero);
					$msgPartialData          = array("subject"        =>  $msgSubject,
							                         "message"        =>  $adminMsg,
							                         "logoMsg"        =>  APPLICATION_STRUCTURE_LOGO,
							                         "replyToEmail"   =>  $defaultToEmail,
							                         "replyToName"    =>  $defaultToName,
							                         "replyToTel"     =>  "",
							                         "replyToSiteWeb" =>  "http://www.fichiernationalrccm.com/about",
							                         "toName"         =>  $defaultToName,
							                         "toEmail"        =>  $defaultToEmail );
					$msgBody                 = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
					
					$cloneMailer             = new Zend_Mail("UTF-8");
					$cloneMailer->setFrom( $defaultToEmail, "FNRCCM ");
					$cloneMailer->setSubject( $msgSubject );
					$cloneMailer->addTo( $defaultToEmail, stripslashes( $defaultToName ) );
					$cloneMailer->setBodyHtml( $msgBody );
					try{
						$cloneMailer->send();
					} catch(Exception $e) {
						 
					}
				}	//Fin de l'enregistrement de la requete					
			}
			if( empty( $errorMessages )) {
				$this->setRedirect(sprintf("Votre demande d'accès au registre numéro %s a été enregistrée avec succès", $registre->numero) , "success");
				$this->redirect("public/account/dashboard");
			}
			$defaultData  = $postData;
		}
		if( count( $errorMessages )) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" " , $messages )));
				exit;
			}
			$viewMessage = "";
			foreach( $errorMessages as $message) {
				     $viewMessage  .= $message;
			}
			$this->view->messages   = $viewMessage;
		}								
		if(!isset($defaultSession->token)){
			$defaultSession->token  = Sirah_User_Helper::getToken(25).time();
		}
		$this->view->token          = $defaultSession->token;
		$this->view->data           = $defaultData;
		$this->view->typesdocuments = $modelRequest->typedocuments();
	}
	
	 
	
}