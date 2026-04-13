<?php

class DemandesController extends Sirah_Controller_Default
{
	protected $_member  = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		$application    = new Zend_Session_Namespace("erccmapp");
		$me             = $loggedInUser = Sirah_Fabric::getUser();
		 
		if(!$me->isOPERATEURS() && !$me->isPARTENAIRES() && !$me->isPARTNERS() && !$me->isPROMOTEURS() && !$me->isPromoteurs() && !$me->isDirecteurs()) {
			$returnToUrl= (!empty($actionName))?sprintf("public/demandes/%s", $actionName) : "public/demandes/verify";
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
		if(!$accountMember ) {
			$returnToUrl= (!empty($actionName))?sprintf("public/demandes/%s", $actionName) : "public/demandes/verify";
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
		if(!$member ) {
			$returnToUrl= (!empty($actionName))?sprintf("public/demandes/%s", $actionName) : "public/demandes/verify";
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
		$errorMessages           = $informationMessages = array();
		$params                  = $this->_request->getParams();
		$keywords                = $query = (isset($params["keywords"]))?$params["keywords"] : "";
		$available               = 0;
		$similarites             = $similariteActivities = $registres = array();
		$verificationStateStore  = new Zend_Session_Namespace("Statestore");
		if(!isset($verificationStateStore->verificationstate)) {
			$verificationStateStore->verificationstate = array("availables"=>array(),"unavailables"=>array());
		}
		if( $this->_request->isPost()  ) {
			$model               = $this->getModel("demande");
			$modelRegistre       = $this->getModel("registre");
			$modelEntreprise     = $this->getModel("demandentreprise");
			$modelBlacklist      = $this->getModel("demandeblacklist");
			
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter        =   new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			$postData            = $this->_request->getPost();
			$available           = 1;
			
			$keywords            = $query = (isset($postData["keywords"]) && !empty($postData["keywords"] ))?$modelRegistre->cleanName(strip_tags($postData["keywords"])) : "";
		    if( empty($keywords) ) {
				$errorMessages[] = "Veuillez saisir des mots clés du nom commercial";
			}
			if( empty($errorMessages) ) {
				$registres       = $modelRegistre->basicList(array("searchQ"=>$keywords,"types" => array(1,2,3,4)), 1,15);
				if( count($registres) ) {
					$i           = 0;
					foreach( $registres as $registre ) {
						     $foundRegistreLib         = $modelRegistre->cleanName($registre["libelle"],$registre["numero"]);
							 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$registre["libelle"] );
							 $similariteActivities[$i] = $registre["description"];
							 $registres[$i]            = $registre;
							 if((false!==stripos($keywords,$foundRegistreLib)) || ($foundRegistreLib==$keywords) || ($registre["libelle"]==$keywords)) {
								 $available            = 0;
								 $errorMessages[]      = sprintf("Le nom commercial %s ne semble pas disponible. Une entreprise %s a ete trouve avec le numero RCCM: %s", $query, $foundRegistreLib,$registre["numero"]);
								 break;
							 }							
							 $informationMessages[0]   = "Quelques noms commerciaux similaires ont été trouvés. Nous vous recommandons de vous rendre dans une juridiction proche pour approfondir la vérification.";
						     
							 $i++;
					}
				} else {
					$reservedEntreprises  = $modelEntreprise->getList(array("libelle"=>$keywords,"reserved"=>1), 1,15);
					if( count(   $reservedEntreprises) ) {
						foreach( $reservedEntreprises as $reservedEntreprise ) {
							     $foundRegistreLib     = $reservedEntreprise["nomcommercial"];
								 $foundRegistreSigle   = (!empty($reservedEntreprise["sigle"]))?$modelRegistre->cleanName($reservedEntreprise["sigle"]) : "";
								 if( $foundRegistreLib== $query ) {
									 $available        = 0;
									 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Un nom similaire reservé a été trouvé : %s.",$query, $foundRegistreLib);
									 break;
								 } elseif(!empty($foundRegistreSigle) && (false!==stripos($foundRegistreSigle,$keywords))) {
									 $available        = 0;
									 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Le sigle %s apparait dans le nom commercial à réserver.",$query,$foundRegistreSigle);
									 break;
								 }
						}
					} else {
						$blacklisted = $modelBlacklist->getList(array("searchQ"=>$keywords), 1,100);
						if( count(   $blacklisted)) {
							$i       = 0;
							foreach( $blacklisted as $item ) {
								     $foundBlackListLibelle  = $item["libelle"];
									 if( false!==stripos($keywords,$foundBlackListLibelle)) {
										 $available          = 0;
										 $errorMessages[]    = sprintf("Le nom commercial %s n'est pas autorisé. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.",$query);
										 break;
									 }
							}
						}
					}
				}	
                if( $available ) {
					$verificationStateStore->verificationstate["availables"][] = $query;
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
			}
		}
		$this->view->keywords      = $keywords;
		$this->view->similarites   = $similarites;
		$this->view->activities    = $similariteActivities;
		$this->view->registres     = $registres;
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
		if(!intval($demandeurid)) {
			$defaultData["demandeur_date_etablissement_day"]  = $defaultData["demandeur_date_etablissement_month"] = $defaultData["demandeur_date_etablissement_year"] = 0;
		    $defaultData["demandeur_date_etablissement"]      = "";
			$defaultData["demandeur_organisme_etablissement"] = "";
			$defaultData["demandeur_lieu_etablissement"]      = "";
			$defaultData["demandeur_numidentite"]             = "";
			$defaultData["demandeur_identitetype"]            = 1;
		}
		
		$this->view->demandeurid         = $demandeurid;
		$this->view->keywords            = $keywords;
		$this->view->formes              = $formes        = $modelEntrepriseForme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines            = $domaines      = $modelDomaine->getSelectListe(        "Sélectionnez un secteur d'activité"      , array("domaineid" , "libelle"),array() , null , null , false );
		$this->view->localites           = $localites     = $modelLocalite->getSelectListe(       "Sélectionnez une juridiction"            , array("localiteid", "libelle"),array() ,null , null , false );
		$this->view->countries           = $countries     = $modelCountry->getSelectListe(        "Selectionnez un pays"                    , array("code"      , "libelle"),array("orders"=> array("libelle ASC")), null , null , false );       
		$this->view->identiteTypes       = $identiteTypes = $modelIdentiteType->getSelectListe(   "Selectionnez un type de pièce d'identité", array("typeid"    , "libelle"),array() , null , null , false );
		
		if( $this->_request->isPost()  ) {
			$postData                    = $this->_request->getPost();
			$demandeurData               = array_merge($demandeurEmptyData,array_intersect_key($postData,$demandeurEmptyData));
			$insert_data                 = $demandeData = array_merge($demandeEmptyData,array_intersect_key($postData,$demandeEmptyData));
			$defaultIdentityData         = $modelIdentite->getEmptyData();
			$defaultEntrepriseData       = $modelEntreprise->getEmptyData();
 
			$entrepriseData              = array_merge($defaultEntrepriseData,array_intersect_key($postData,$defaultEntrepriseData));
			$pieceIdentityData           = array_merge($defaultIdentityData  ,array_intersect_key($postData,$defaultIdentityData ));			
			$promoteurData               = array_merge($defaultPromoteurData ,array_intersect_key($postData,$defaultPromoteurData));
			
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
			
			$insert_data["numero"]       = $model->reference();
			$insert_data["demandeurid"]  = $demandeurid;
			$insert_data["promoteurid"]  = (isset($postData["promoteurid"] ))?intval($postData["promoteurid"])             : 0;
			$insert_data["entrepriseid"] = (isset($postData["entrepriseid"]))?intval($postData["entrepriseid"])            : 0;
			$insert_data["localiteid"]   = (isset($postData["localiteid"]  ))?intval($postData["localiteid"])              : 0;
			$insert_data["keywords"]	 = (isset($postData["keywords"]    ))?$stringFilter->filter($postData["keywords"]) : $keywords;
			$insert_data["registreid"]   = 0;
			$insert_data["periodid"]     = 0;			
			$insert_data["libelle"]      = "";			
			$insert_data["objet"]        = "";
			$insert_data["typeid"]       = 1;
			$insert_data["statutid"]     = 7;
			$insert_data["date"]         = $insert_data["creationdate"] = time();
			if(!isset($postData["demandeurid"]) && intval($demandeurid)) {
				$postData["demandeurid"] = $insert_data["demandeurid"]  = $demandeurid;
			}					
			if(!intval($demandeurid) || !intval($insert_data["demandeurid"])) {
				$DemandeurIdentityData   = $pieceIdentityData;
				$demandeurIdentityId     = 0;
				$postData["demandeur_identitetype"]            = (isset($postData["demandeur_identitetype"]))?$postData["demandeur_identitetype"] : 0;
				$postData["demandeur_numidentite"]             = (isset($postData["demandeur_numidentite"] ))?$postData["demandeur_numidentite"]  : "";
				$postData["demandeur_lieu_etablissement"]      = (isset($postData["demandeur_lieu_etablissement"]))?$postData["demandeur_lieu_etablissement"]  : "";
				$postData["demandeur_organisme_etablissement"] = (isset($postData["demandeur_organisme_etablissement"] ))?$postData["demandeur_organisme_etablissement"]  : "";
				$zendIdentityDate                              = new Zend_Date(array("year" => (isset($postData["demandeur_date_etablissement_year"] ))? intval($postData["demandeur_date_etablissement_year"]) : 0,
															                         "month"=> (isset($postData["demandeur_date_etablissement_month"]))? intval($postData["demandeur_date_etablissement_month"]): 0,
															                         "day"  => (isset($postData["demandeur_date_etablissement_day"]  ))? intval($postData["demandeur_date_etablissement_day"])  : 0 ) );						
				$postData["demandeur_date_etablissement"]      = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
				if( intval($postData["demandeur_identitetype"]) && isset($identiteTypes[$postData["demandeur_identitetype"]])) {
					$DemandeurIdentityData["typeid"]           = intval($postData["demandeur_identitetype"]);
				} else {
					$errorMessages[]     = "Veuillez sélectionner le type de pièce que vous utilisez";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_numidentite"]) ) {
					$DemandeurIdentityData["numero"] = $stringFilter->filter( $postData["demandeur_numidentite"] );
				} else {
					$errorMessages[]     = "Veuillez saisir le numéro de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_organisme_etablissement"]) ) {
					$DemandeurIdentityData["organisme_etablissement"] = $stringFilter->filter( $postData["demandeur_organisme_etablissement"] );
				} else {
					$errorMessages[]     = "Veuillez saisir l'organisme d'établissement de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_lieu_etablissement"]) ) {
					$DemandeurIdentityData["lieu_etablissement"] = $stringFilter->filter( $postData["demandeur_lieu_etablissement"] );
				} else {
					$errorMessages[]     = "Veuillez saisir le lieu d'établissement de votre carte d'identité/passport";
				}
				if( $strNotEmptyValidator->isValid($postData["demandeur_date_etablissement"]) && Zend_Date::isDate($postData["demandeur_date_etablissement"], "YYYY-MM-dd")) {
					$DemandeurIdentityData["date_etablissement"] = $stringFilter->filter( $postData["demandeur_date_etablissement"] );
				} else {
					$errorMessages[]     = "Veuillez saisir la date d'établissement de votre carte d'identité/passport";
				}
				if( empty($errorMessages) ) {					
					if( $foundDemandeurIdentity = $modelIdentite->findRow(strip_tags($DemandeurIdentityData["numero"]),"numero",null,false)) {
					    if(($foundDemandeurIdentity->date_etablissement==$DemandeurIdentityData["date_etablissement"]) &&
						   ($foundDemandeurIdentity->lieu_etablissement==$DemandeurIdentityData["lieu_etablissement"])) {
							$demandeurIdentityId= $foundDemandeurIdentity->identityid;
						}
					}
					if(!intval($demandeurIdentityId)) {
						$cleanIdentityData                 = array_intersect_key($DemandeurIdentityData,$defaultIdentityData);
						$cleanIdentityData["creationdate"] = time();
						$cleanIdentityData["creatorid"]    = $me->userid;
						$cleanIdentityData["updatedate"]   = $cleanIdentityData["updateduserid"] = 0;
						if( $dbAdapter->insert($prefixName."reservation_demandeurs_identite",$cleanIdentityData)) {
							$demandeurIdentityId           = $dbAdapter->lastInsertId();
						}
					}
                    if(!intval($demandeurIdentityId)) {
						$errorMessages[]                   = "Les informations de votre pièce d'identité n'ont pas pu être enregistrées";
					} else {
						$accountid                         = $me->userid;
						$memberName                        = sprintf("%s %s", $me->lastname,$me->firstname);
						$memberRow                         = $modelMember->findRow($accountid,"accountid",null,false);
						$accountData                       = $me->getData();
						if( $memberRow ) {
							$memberData                    = array_merge($accountData,$memberRow->toArray());
						} else {
							$memberData                    = $me->getData();
						}
						$demandeurData                     = array_merge($demandeurEmptyData,array_intersect_key($memberData,$demandeurEmptyData));
						$demandeurData["name"]             = $memberName;
						$demandeurData["lastname"]         = $me->lastname;
						$demandeurData["firstname"]        = $me->firstname;
						$demandeurData["datenaissance"]    = (isset( $memberData["birthday"]    ))? $memberData["birthday"] : "";
						$demandeurData["lieunaissance"]    = (isset( $memberData["birthaddress"]))? $memberData["birthaddress"] : "";
						$demandeurData["telephone"]        = (!empty($memberData["tel2"]))?sprintf("%s/%s",$memberData["tel1"],$memberData["tel2"]) : sprintf("%s",$memberData["tel1"]);
						$demandeurData["adresse"]          = (isset( $memberData["address"]     ))? $memberData["address"]  : "";
						$demandeurData["profession"]       = (isset( $memberData["fonction"]    ))? $memberData["fonction"] : "";
						$demandeurData["numidentite"]      = (isset($DemandeurIdentityData["numero"]))?sprintf("%s n° %s du %s par %s %s",$identiteTypes[$postData["demandeur_identitetype"]],$DemandeurIdentityData["numero"],$DemandeurIdentityData["date_etablissement"],$DemandeurIdentityData["organisme_etablissement"],$DemandeurIdentityData["lieu_etablissement"]) : "";
						$demandeurData["identityid"]       = $demandeurIdentityId;
						$demandeurData["accountid"]        = intval($accountid);
						$demandeurData["creationdate"]     = time();
						$demandeurData["creatorid"]        = 1;
						$demandeurData["updatedate"]       = 0;
						$demandeurData["updateduserid"]    = 0;
						$cleanDemandeurData                = array_intersect_key($demandeurData,$demandeurEmptyData);
						if(!$dbAdapter->insert($prefixName."reservation_demandeurs",$cleanDemandeurData) )	{
							$errorMessages[]               = "Vos informations en tant que demandeur n'ont pas pu être enregistrées";
						} else {
							$demandeurid                   = $insert_data["demandeurid"] = $postData["demandeurid"] = $dbAdapter->lastInsertId();
						    $dbAdapter->update($prefixName."rccm_members", array("passport"=>$demandeurData["numidentite"]), array("accountid=?"=>intval($accountid)));
						}
					}						
				}
			}			
            //On vérifie les informations de la référence de la pièce d'identité du promoteur
			$zendIdentityDate  = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
													 "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
													 "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
			$postData["date_etablissement"]     = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
			if(!intval($postData["identitetype"]) || !isset($identiteTypes[$postData["identitetype"]])) {
				$errorMessages[]                = "Veuillez renseigner le type de carte d'identité du promoteur";
			} else {
				$pieceIdentityData["typeid"]    = intval($postData["identitetype"]);
			}
			if(!isset($postData["numero"]) && isset($postData["numidentite"])) {
				$postData["numero"]             = $postData["numidentite"];
			}
			if(!$strNotEmptyValidator->isValid($postData["numero"]) ) {
				$errorMessages[]                = "Veuillez renseigner le numéro de la pièce d'identité du promoteur";
			} else {
				$pieceIdentityData["numero"]    = $stringFilter->filter( $postData["numero"] );
			}
			if(!$strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
				$errorMessages[]                = "Veuillez renseigner l'organisme d'étalissement de la pièce d'identité du promoteur";
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
			$promoteurData["name"]             = (isset($postData["name"]         ))? $stringFilter->filter($postData["name"])              : "";
			$promoteurData["country"]          = (isset($postData["country"]      ))? $stringFilter->filter($postData["country"])           : ((isset($postData["nationalite"]))?$postData["nationalite"] : "BF");
			$countryCallingCode                = (!empty($promoteurData["country"]))? $modelCountry->callingCode($promoteurData["country"]) : '00226';			
			
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]               = "Adresse invalide : veuillez sélectionner un pays de résidence valide";
			} else {
				$formatPhoneNumber             = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $promoteurData["telephone"] ));
				$validPhoneNumberPattern       = "/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$promoteurData["telephone"]= $formatPhoneNumber;
					//$errorMessages[]           = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone du pays selectionné";
				} else {
					$promoteurData["telephone"]= $formatPhoneNumber;
				}
			}
			if( $emailValidator->isValid($promoteurData["email"])) {
				if( $existantClient            = $modelPromoteur->findRow(trim($promoteurData["email"]), "email", null , false )) {
					$promoteurData["email"]    = sprintf("%s@siraah.net", time());
				} else {
					$promoteurData["email"]    = $stringFilter->filter($promoteurData["email"]);
				}
			} else {
					$promoteurData["email"]    = sprintf("%s@siraah.net", time());
			}				 
			if(!$strNotEmptyValidator->isValid($promoteurData["telephone"])) {
				$errorMessages[]               = " Veuillez entrer un numéro de téléphone valide";
			} elseif($existantClient           = $modelPromoteur->findRow(trim($promoteurData["telephone"]), "telephone", null , false ) ) {
				$promoteurid                   = $existantClient->promoteurid;
			} else {
				$promoteurData["telephone"]    = $stringFilter->filter($promoteurData["telephone"]);
			}
			if( $strNotEmptyValidator->isValid($promoteurData["name"])) {
				$fullNameArray                 = Sirah_Functions_String::split_name($promoteurData["name"]);
				if( isset($fullNameArray[0])) {
					$promoteurData["lastname"] = $fullNameArray[0];
				}
				if( isset($fullNameArray[1])) {
					$promoteurData["firstname"]= $fullNameArray[1];
				}
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["firstname"])) {
				$errorMessages[]               = "Veuillez saisir le(s) prénom(s)";
			} else {
				$promoteurData["firstname"]    = $stringFilter->filter($promoteurData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["lastname"])) {
				$errorMessages[]               = "Veuillez saisir le nom de famille";
			} else {
				$promoteurData["lastname"]     = $stringFilter->filter($promoteurData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($promoteurData["sexe"]) || (($promoteurData["sexe"] != "M")  && ( $promoteurData["sexe"] != "F" ))) {
				$errorMessages[]               = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
				$promoteurData["sexe"]         = $stringFilter->filter( $promoteurData["sexe"] );
			}
			if( empty($errorMessages)) {	
			    $selectIdentity = $dbAdapter->select()->from($prefixName."reservation_demandeurs_identite")
					                                  ->where("numero=?",$pieceIdentityData["numero"])
													  ->where("date_etablissement=?",$pieceIdentityData["date_etablissement"]);
				$foundIdentity  = $dbAdapter->fetchRow($selectIdentity, array(), Zend_DB::FETCH_ASSOC);
                if( count($foundIdentity) && isset($foundIdentity["identityid"])) {
					$identityid                         = $promoteurData["identityid"]  = $foundIdentity["identityid"];
					$numidentite                        = $promoteurData["numidentite"] = sprintf("%s n° %s du %s par %s %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);	
				} else {
					$pieceIdentityData["creationdate"]  = time();
					$pieceIdentityData["creatorid"]     = $me->userid;
					$pieceIdentityData["updatedate"]    = 0;
					$pieceIdentityData["updateduserid"] = 0;
					$dbAdapter->delete(     $prefixName."reservation_demandeurs_identite", array("numero=?"=>$pieceIdentityData["numero"],"typeid=?"=>$pieceIdentityData["typeid"]));
					if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $pieceIdentityData)  ) {
						$identityid                     = $promoteurData["identityid"]  = $dbAdapter->lastInsertId();
						$numidentite                    = $promoteurData["numidentite"] = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);					
					}
				}			
				$promoteurData["name"]          = sprintf("%s %s", $promoteurData["lastname"], $promoteurData["firstname"]);
				$promoteurData["nationalite"]   = $stringFilter->filter($promoteurData["country"] );
				$promoteurData["adresse"]       = $stringFilter->filter($promoteurData["adresse"] );
				$promoteurData["profession"]    = $stringFilter->filter($promoteurData["profession"] );
				$promoteurData["avatar"]        = "";
				$promoteurData["creationdate"]  = time();
				$promoteurData["creatorid"]     = $me->userid;
				$promoteurData["updatedate"]    = 0;
				$promoteurData["updateduserid"] = 0;
				if( $promoteurRow = $modelPromoteur->findRow($promoteurData["numidentite"],"numidentite",null,false)) {
					$promoteurid                = $insert_data["promoteurid"] = $promoteurRow->promoteurid;
				} elseif($promoteurRow = $modelPromoteur->findRow($promoteurData["telephone"],"telephone",null,false)) {
					$promoteurid                = $insert_data["promoteurid"] = $promoteurRow->promoteurid;
				}  
                if( intval($promoteurid)) {
					if( isset($promoteurData["promoteurid"])) {
						unset($promoteurData["promoteurid"]);
					}
					$promoteurData["updatedate"]    = time();
				    $promoteurData["updateduserid"] = $me->userid;
					$clean_promoteur_data           = array_intersect_key($promoteurData,$defaultPromoteurData);
					$dbAdapter->update($prefixName."reservation_promoteurs",$clean_promoteur_data, array("promoteurid=?"=>intval($promoteurid)));
				} else {
					$clean_promoteur_data           = array_intersect_key($promoteurData,$defaultPromoteurData);
					if( $dbAdapter->insert($prefixName."reservation_promoteurs",$clean_promoteur_data)) {
						$insert_data["promoteurid"] = $promoteurid = $dbAdapter->lastInsertId();
					} else {
						$errorMessages[]            = "Les informations du promoteur sont manquantes";
					}
				}
				$insert_data["promoteurid"]         = $promoteurid;
			}			 
			if(!intval($insert_data["promoteurid"])) {
				$errorMessages[]                    = "Les informations du promoteur n'ont pas été renseignées";
			}
			if( empty( $insert_data["keywords"])) {
				$errorMessages[]         = "Veuillez renseigner les mots clés de recherche des noms similaires";
			} 	
            if(!isset($domaines[$postData["domaineid"]]) || !intval($postData["domaineid"])) {
				$errorMessages[]         = "Veuillez préciser le secteur d'activité de l'entreprise";
			}
			if(!isset($localites[$postData["localiteid"]]) || !intval($postData["localiteid"])) {
				$errorMessages[]         = "Veuillez sélectionner la juridiction/localité associée à votre demande";
			}
            if(!isset($formes[$postData["formid"]]) || !intval($postData["formid"])) {
				$errorMessages[]         = "Veuillez préciser la forme juridique de l'entreprise";
			}
			if( empty($errorMessages)) {
				$entrepriseRow                      = null;
				$entrepriseData["demandeid"]        = 0;			
				$entrepriseData["demandeurid"]      = $insert_data["demandeurid"];
				$entrepriseData["promoteurid"]      = $insert_data["promoteurid"];
				$entrepriseData["responsable"]      = $promoteurData["name"];
				$entrepriseData["catid"]            = 0;
				$entrepriseData["localiteid"]       = $insert_data["localiteid"];
				$entrepriseData["domaineid"]        = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : 0;
				$entrepriseData["formid"]           = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : 0;
				$entrepriseData["country"]          = (isset($postData["country"]  ))? $stringFilter->filter($postData["country"])  : "BF";
				$entrepriseData["city"]             = (isset($postData["city"]     ))? $stringFilter->filter($postData["city"])     : "OUA";
				$entrepriseData["address"]          = (isset($postData["address"]  ))? $stringFilter->filter($postData["address"])  : $promoteurData["adresse"];
				$entrepriseData["activite"]         = (isset($postData["activite"] ))? $stringFilter->filter($postData["activite"]) : "";
				$entrepriseData["numrccm"]          = (isset($postData["numrccm"]  ))? $stringFilter->filter($postData["numrccm"])  : "";
				$entrepriseData["numcnss"]          = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"])  : "";
				$entrepriseData["numifu"]           = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"])   : "";
				$entrepriseData["telephone"]        = (isset($postData["telephone"]))? $stringFilter->filter($postData["telephone"]): "";
				$entrepriseData["email"]            = (isset($postData["email"]    ))? $stringFilter->filter($postData["email"])    : "";
				$entrepriseData["denomination"]     = (isset($postData["denomination"]))? $stringFilter->filter($postData["denomination"]) : "";
				$entrepriseData["reserved"]         = 0;
				$entrepriseData["blacklisted"]      = 0;
				$entrepriseData["datecreation"]     =  $entrepriseData["datefermeture"] = "";
				$entrepriseData["creationdate"]     =  time();
				$entrepriseData["creatorid"]        =  $me->userid;
				$entrepriseData["updateduserid"]    =  $entrepriseData["updatedate"]    = 0;
				if( isset($postData["nomcommercial"]) && $strNotEmptyValidator->isValid($postData["nomcommercial"])) {
					$entrepriseData["nomcommercial"]= $insert_data["objet"] = $stringFilter->filter($postData["nomcommercial"]);
				} else {
					$errorMessages[]                = "Veuillez saisir le nom commercial de l'entreprise";
				}
				if( isset($postData["sigle"]) && $strNotEmptyValidator->isValid($postData["sigle"])) {
					$entrepriseData["sigle"]        = $stringFilter->filter($postData["sigle"]);
					$insert_data["objet"]           = $insert_data["objet"]."(".$entrepriseData["sigle"].")";
				}
				if( empty( $errorMessages )) {
					if( intval($entrepriseid)) {
						if( isset($entrepriseData["entrepriseid"])) {
							unset($entrepriseData["entrepriseid"]);
						}
						$dbAdapter->update($prefixName."reservation_demandes_entreprises",$entrepriseData, array("entrepriseid=?"=>intval($entrepriseid)));
					} else {
						if( $dbAdapter->insert($prefixName."reservation_demandes_entreprises", $entrepriseData)) {
							$insert_data["entrepriseid"] = $dbAdapter->lastInsertId();
							$entrepriseRow               = $modelEntreprise->findRow($insert_data["entrepriseid"],"entrepriseid", null, false );
						} else {
							$errorMessages[]             = "Veuillez saisir les informations de l'entreprise";
						}
					}				
				}
				$insert_data["periodstart"]     = $insert_data["date"];			
				$insert_data["periodend"]       = $insert_data["periodstart"] + (3*24*3600);		
				$insert_data["personne_morale"] = (isset($postData["personne_morale"]))? intval($postData["personne_morale"])             : 0;
				$insert_data["observations"]    = (isset($postData["observations"]   ))? $stringFilter->filter($postData["observations"]) : "";
				$insert_data["denomination"]    = (isset($postData["denomination"]   ))? $stringFilter->filter($postData["denomination"]) : "";
				$insert_data["expired"]         = 0;
				$insert_data["rejected"]        = 0;
				$insert_data["disponible"]      = 0;
				$insert_data["reject"]          = 0;
				$insert_data["motif_rejet"]     = "";
				$insert_data["creatorid"]       = $me->userid;
				$insert_data["creationdate"]    = time();	
				$insert_data["updatedate"]      = $insert_data["updateduserid"] = 0;
				
				if( empty($insert_data["denomination"]) ) {
					$insert_data["denomination"]= $insert_data["objet"];
				}
				if( empty($errorMessages)) {
					$insert_data["libelle"]     = sprintf("Demande de réservation du nom commercial %s", $insert_data["objet"] );
					$emptyData                  = $model->getEmptyData();
					$clean_insert_data          = array_intersect_key( $insert_data, $emptyData);
					if( $dbAdapter->insert( $tableName, $clean_insert_data) ) {
						$demandeid              = $dbAdapter->lastInsertId();		
						
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
							$successMessage                = "La demande de réservation a été enregistrée avec succès. Elle sera prise en charge dans un délai de 24H maximum.";
							
							//On supprime la clé de reservation dans le store
							$verificationStateStoreArray   = $verificationStateStore->verificationstate;

							if( false!=$reservationKey && isset($verificationStateStoreArray["availables"][$reservationKey])) {
								$verificationStateStoreArray["availables"][$reservationKey] = null;
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
		}
		$this->view->title = sprintf("Réservation du nom commercial <strong><u>`%s`</u></strong>", $keywords); 
		$this->view->data  = $defaultData;
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
		$pageNum               = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		
		$filters               = array("page"=>$pageNum,"maxitems"=>$pageSize,"libelle"=>null,"numero"=>null,"localiteid"=>0,"searchQ"=>null,"demandeurid"=>0,"promoteurid"=>0,
								       "typeid"=>0,"statutid"=>0,"expired"=>4,"disponible"=>4,"date"=>null,"demandeurname"=>null,"promoteurname"=>null,"nomcommercial"=>null);
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
		$this->view->pageSize  = $pageSize;
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
		$demandeurRow                  = $demande->demandeur( $demandeid);
		$promoteurRow                  = $demande->promoteur( $demandeid);
		$entrepriseRow                 = $demande->entreprise($demandeid);
		
        $this->view->demande           = $demande;
        $this->view->demandeid         = $demandeid;
        $this->view->demandeurid       = $demandeurid;
		$this->view->entrepriseid      = $entrepriseid;
        $this->view->demandeurIdentite = ( $demandeurRow->identityid)?$modelDemandeur->identite($demandeurRow->identityid) : null;
        $this->view->promoteurIdentite = ( $promoteurRow->identityid)?$modelPromoteur->identite($promoteurRow->identityid) : null;		
        $this->view->demandeur         = $demandeurRow;	
        $this->view->promoteur         = $promoteurRow;	
        $this->view->entreprise        = $entrepriseRow;
		$this->view->domaineActivite   = ($entrepriseRow)?$modelDomaine->findRow($entrepriseRow->domaineid,"domaineid", null, false) : null;
		$this->view->formeJuridique    = ($entrepriseRow)?$modelEntrepriseForme->findRow($entrepriseRow->formid,"formid", null, false) : null;
		$this->view->statut            = $demande->findParentRow("Table_Demandestatuts");
		$this->view->localite          = $demande->findParentRow("Table_Localites");
		$this->view->documents         = $demande->documents($demandeid);
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
				$urlDone               = $this->_helper->HttpUri(array("controller" => "requetes", "action" => "create", "module" => "public"));
				$urlSecurityCheck      = $this->_helper->HttpUri(array("controller" => "securitycheck", "action" => "captcha", "module" => "public", "params" => array("done" => $urlDone , "token" => $defaultSession->token )));
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
			                                                       ->where("R.typedocument = ?", $typedocumentVal )->where("R.registreid = ?", $registreid);
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
							                      "replyToSiteWeb" =>  "http://www.siraah.net/about",
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
							                         "replyToSiteWeb" =>  "http://www.siraah.net/about",
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