<?php

class Admin_MembersController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{		
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}					
		$this->view->title     = "Gestion des comptes clients";
		$members               = array();
		$membersListePaginator = null;
		$me                    = Sirah_Fabric::getUser();
		$model                 = $this->getModel("member");
		$modelGroupe           = $this->getModel("membergroup");
		$modelEntreprise       = $this->getModel("entreprise");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		$filters              = array("searchQ"=>null,"lastname"=>null,"firstname"=>null,"code"=>null,"name"=>null,"groupid"=>0,"identifiant"=>null,"passport"=>null,"nationalite"=>null,"email"=>null,"telephone"=> null,"entrepriseid"=>0,"accountid"=>0,"demandeurid"=>0);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue ) {
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( empty( $filters["name"] ) && ( !empty( $filters["lastname"] ) || !empty( $filters["firstname"] ))) {
			$filters["name"]       = trim(sprintf("%s %s", $filters["lastname"], $filters["firstname"]));
		}
        if( intval( $filters["entrepriseid"] )) {
			$entreprise            = $modelEntreprise->findRow(intval( $filters["entrepriseid"]), "entrepriseid", null, false );
			$filters["entreprise"] = $this->view->entreprise = ( $entreprise ) ? $entreprise->libelle : "";
		} else {
			$filters["entreprise"] = $this->view->entreprise = null;
		}			
		if(!$me->isAdmin() ) {
			$filters["creatorid"]  = $me->userid;
		}
		$members                   = $model->getList($filters , $pageNum , $pageSize );
		$membersListePaginator     = $model->getListPaginator($filters);
	
		if(null !== $membersListePaginator ) {
			$membersListePaginator->setCurrentPageNumber($pageNum);
			$membersListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->members       = $members;
		$this->view->filters       = $filters;
		$this->view->pageNum       = $pageNum;
		$this->view->pageSize      = $pageSize;
		$this->view->paginator     = $membersListePaginator;
		$this->view->maxitems      = $pageSize;
		$this->view->columns       = array("left");
		$this->view->groupes       = $modelGroupe->getSelectListe("Selectionnez un groupe", array("groupid","libelle"), array() , null , null , false );
	}
	
	public function exportAction( )
	{
		$this->_helper->layout->disableLayout( true );
		$members       = array(); 
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter  = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;	
		$filters              = array("lastname"=>null,"firstname"=>null,"code"=>null,"name"=>null,"groupid"=>0,"identifiant"=>null,"passport"=>null,"nationalite"=>null,"email"=>null,"telephone"=> null,"entrepriseid" => 0);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue ) {
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( empty( $filters["name"] ) && ( !empty( $filters["lastname"] ) || !empty( $filters["firstname"] ))) {
			$filters["name"]   = trim(sprintf("%s %s", $filters["lastname"], $filters["firstname"]));
		}
        if( intval( $filters["entrepriseid"] )) {
			$entreprise        = $modelEntreprise->findRow(intval( $filters["entrepriseid"]), "entrepriseid", null, false );
			$filters["entreprise"] = $this->view->entreprise = ( $entreprise ) ? $entreprise->libelle : "";
		} else {
			$filters["entreprise"] = $this->view->entreprise = null;
		}
		$model                 = $this->getModel("member");
		$modelGroupe           = $this->getModel("membergroup");
		$members               = $model->getList($filters , $pageNum , $pageSize );
		$membersListePaginator = $model->getListPaginator($filters);
	
		if(null !== $membersListePaginator ) {
			$membersListePaginator->setCurrentPageNumber($pageNum);
			$membersListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->members   = $members;
		
		$this->view->users     = $members;
		$this->view->members   = $members;
		$this->view->filters   = $filters;
		
		$this->render("listepdf");		
	}	
	
	
	public function createAction()
	{
		$this->view->title                   = "Enregistrer un nouveau compte client";
		$model                               = $modelMember = $this->getModel("member");
		$modelGroupe                         = $this->getModel("membergroup");
		$modelEntreprise                     = $this->getModel("entreprise");
		$modelCity                           = $this->getModel("countrycity");
		$modelCountry                        = new Model_Country();
		$modelProfile                        = $this->getModel("profile");
		
		$memberDefaultData                   = $model->getEmptyData();
		
		$memberDefaultData["country"]        = "BF";
		$memberDefaultData["city"]           = "Ouagadougou";
		$memberDefaultData["groupid"]        = 0;
		$memberDefaultData["entrepriseid"]   = $entrepriseid = intval($this->_getParam("entrepriseid",$this->_getParam("entreprise", 0)));
		
		$memberDefaultData["birthday_year"]  = null;
		$memberDefaultData["birthday_month"] = null;
		$memberDefaultData["birthday_day"]   = null;
		$groupes                             = $modelGroupe->getSelectListe("Selectionnez un groupe", array("groupid", "libelle") , array() , null , null , false );		
		$entreprise                          = ( $entrepriseid ) ? $modelEntreprise->findRow( $entrepriseid,"entrepriseid",null, false ) : null;
		$createAccount                       = intval($this->_getParam("createaccount", $this->_getParam("create_account" ,0)));
		
		$country                             = $this->_getParam("country", "BF");
		$countries                           = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders"=> array("libelle ASC")), null , null , false );
        $cities                              = ((null!==$country)) ? $modelCity->getSelectListe("Selectionnez une ville",array("localiteid","city_name"), array("country_iso_code"=>$country,"orders" => array("city_name ASC")), null , null , false ) : array(0=>"Sélectionnez d'abord un pays");
		
		if( $this->_request->isPost() ) {			
			$postData                        = $this->_request->getPost();
			$defaultData                     = $model->getEmptyData();
			$memberFormData                  = array_intersect_key( $postData  , $defaultData);
			$memberData                      = array_merge( $defaultData, $memberFormData    );			
			
			$me                              = $user = Sirah_Fabric::getUser();
			$userTable                       = $me->getTable();
			$dbAdapter                       = $userTable->getAdapter();
			$prefixName                      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator           = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator                 = new Sirah_Validateur_Email();
			
			$memberData["code"]             = (isset( $postData["code"]))? $stringFilter->filter($postData["code"]) : (isset($postData["username"])?$stringFilter->filter($postData["username"]):"");
			$zendBirthday                   = new Zend_Date(array("year" => (isset($postData["birthday_year"] )) ? intval($postData["birthday_year"]) : 0,
					                                              "month"=> (isset($postData["birthday_month"])) ? intval($postData["birthday_month"]): 0,
					                                              "day"  => (isset($postData["birthday_day"]  )) ? intval($postData["birthday_day"])  : 0 ) );						
			$memberData["birthday"]         = $zendBirthday->get(Zend_Date::DATETIME);
			$memberData["birthaddress"]     = ( isset( $postData["birthaddress"] )) ? $stringFilter->filter($postData["birthaddress"]) : "";
			$memberData["tel2"]             = $stringFilter->filter( $memberData["tel2"] );
            $memberData["accountid"]        = $accountid = 0;	
			$memberData["country"]          = (isset( $postData["country"]  ))? $stringFilter->filter($postData["country"]) : "BF";
            $countryCallingCode             = (!empty($memberData["country"]))? $modelCountry->callingCode($memberData["country"]) : '';			
            
			if( empty( $countryCallingCode ) ) {
				$errorMessages[]            = "Veuillez sélectionner un pays d'origine valide";
			} else {
				$formatPhoneNumber          = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $memberData["tel1"] ));
				$validPhoneNumberPattern    ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
				if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
					$errorMessages[]        = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone de votre pays actuel";
				} else {
					$memberData["tel1"]	    = $formatPhoneNumber;
				}
			}			
		    if(!$emailValidator->isValid($memberData["email"] ) ) {
			    $errorMessages[]            = "Veuillez fournir une adresse email valide";
		    } elseif( $existantMember       =  $model->findRow(trim($memberData["email"]), "email", null , false )) {
		    	$errorMessages[]            = sprintf("Un member du nom de %s %s utilise cette adresse email %s", $existantMember->lastname, $existantMember->firstname, $memberData["email"] );
		    } else {
				$memberData["email"]        = $stringFilter->filter($memberData["email"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["tel1"])) {
				$errorMessages[]            = " Veuillez entrer un numéro de téléphone mobile valide";
			} elseif($existantMember        = $model->findRow(trim($memberData["tel1"]), "tel1", null , false ) ) {
				$errorMessages[]            = sprintf(" Un member du nom de %s %s existe déjà avec ce numéro %s", $existantMember->lastname, $existantMember->firstname, $memberData["tel1"] );
		    } else {
				$memberData["tel1"]         = $stringFilter->filter($memberData["tel1"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["firstname"])) {
				$errorMessages[]            = "Le prénom que vous avez saisi, est invalide";
			} else {
				$memberData["firstname"]    = $stringFilter->filter($memberData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["lastname"])) {
				$errorMessages[]            = "Le nom de famille que vous avez saisi est invalide";
			} else {
				$memberData["lastname"]     = $stringFilter->filter($memberData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["sexe"]) || (( $memberData["sexe"] != "M")  && ($memberData["sexe"] != "F" ) ) ) {
					$errorMessages[]        = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
					$memberData["sexe"]     = $stringFilter->filter( $memberData["sexe"] );
			}
            if( intval( $createAccount) ) {
				$memberAccountData          = array();
				$memberEmail                = $memberAccountData["email"]    = $memberData["email"];
				$memberUsername             = $memberAccountData["username"] = $memberData["code"];
				$memberPassword             = $memberAccountData["password"] = (isset($postData["password"])) ? $postData["password"] : "";
				if(!$strNotEmptyValidator->isValid($memberPassword) ) {
					$errorMessages[]        = "Entrez un mot de passe valide pour ce compte";
				} else {			
					if(!isset($postData["confirmedpassword"])){
						$errorMessages[]    = "Des informations sont manquantes";
					} elseif($postData["confirmedpassword"] !== $memberPassword) {
						$errorMessages[]    = "Vos mots de passe ne correspondent pas, veuillez re-saisir un mot de passe valide ";
					}
				}
				if(!$strNotEmptyValidator->isValid($memberUsername )) {
					$errorMessages[]        = "Veuillez saisir un identifiant valide pour ce compte member";
				}
				if(!$userTable->checkUsername( $memberUsername ) ) {
					$errorMessages[]        = sprintf("Un identifiant similaire à %s existe déjà",$memberUsername); 				
				}
				if(!$userTable->checkEmail($memberEmail ) ) {
					$errorMessages[]        = sprintf(" L'adresse email %s semble associée à un autre compte recruteur", $memberEmail);
				}
				if(!$emailValidator->isValid($memberEmail )) {
					$errorMessages[]        = sprintf(" L'adresse email %s ne semble pas valide", $memberEmail );
				}
				$memberAccountData["firstname"]     = $stringFilter->filter($memberData["firstname"]);
				$memberAccountData["lastname"]      = $stringFilter->filter($memberData["lastname"]);
				$memberAccountData["country"]       = $memberAccountData["nationalite"] = $memberData["country"] = (isset($postData["country"]))? $stringFilter->filter($postData["country"]) : "BF";
				$memberAccountData["birthday"]      = ($zendBirthday)?$zendBirthday->toString("YYYY-MM-dd") : date("Y-m-d", $birthdayTms);
				$memberAccountData["birthaddress"]  = $stringFilter->filter($memberData["birthaddress"]);
				$memberAccountData["address"]       = $stringFilter->filter($memberData["address"]);
				$memberAccountData["socialstate"]   = (isset($memberData["matrimonial"]))?  $memberData["matrimonial"]         : "";
				$memberAccountData["city"]          = $memberData["city"] = (isset($postData["city"]))? $stringFilter->filter($postData["city"]) : 0;
				$memberAccountData["phone1"]        = (isset($memberData["tel1"]))? $stringFilter->filter($memberData["tel1"]) : "";
				$memberAccountData["phone2"]        = (isset($memberData["tel2"]))? $stringFilter->filter($memberData["tel2"]) : "";
				$memberAccountData["zipaddress"]    = $stringFilter->filter($memberData["zipaddress"]);
				$memberAccountData["language"]      = (isset($postData["language"]))? $stringFilter->filter($postData["language"]) : "";
				$memberAccountData["sexe"]          = $stringFilter->filter($memberData["sexe"]);
				$memberAccountData["activated"]     = 1;				
				$memberAccountData["accountlife"]   = $memberAccountData["nb_connections"]= 0;
				$memberAccountData["blocked"]       = $memberAccountData["locked"]        = 0;
				$memberAccountData["expired"]       = $memberAccountData["connected"]     = 0;
				$memberAccountData["admin"]         = $memberAccountData["statut"]        = 0;
				$memberAccountData["accesstoken"]   = $memberAccountData["logintoken"]    = $memberAccountData["params"]  = "";
				$memberAccountData["lastIpAddress"] = $memberAccountData["facebookid"]    = $memberAccountData["skypeid"] = "";
				$memberAccountData["lastHttpMember"]= $memberAccountData["lastSessionId"] = "";
				$memberAccountData["registeredDate"]= $memberAccountData["creationdate"]  = time();
				$memberAccountData["creatoruserid"] = $me->userid;
				$memberAccountData["updateduserid"] = $memberAccountData["lastUpdatedDate"]= 0;
				
				if( empty( $errorMessages )) {
					$memberAccount                  = Sirah_User::getInstance();
					$memberAccount->clearMessages();
					if(!$accountid                  = $memberAccount->save($memberAccountData)){
						$saveErrors                 = $memberAccount->getMessages("error");
						$errorMessages[]            = "Erreur dans la création du compte";
						foreach( $saveErrors as $type => $msg){
								 $msg               = (is_array($msg)) ? array_shift($msg)  : $msg;
								 $errorMessages[]   = $msg;
						}
					} else {
						$memberData["accountid"]    = $accountid;
						$profile                    = $modelProfile->getRow($accountid,true , false );
						Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_DEFAULT_USERS_ROLENAME);
						switch( intval( $memberData["groupid"] ) ) {
							   case 1:
							   default:
							        Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_CLIENTS_ROLENAME);
							   break;
							   case 2:
							        Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_LEADERS_ROLENAME);
							   break;
							   case 3:
							        Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_RECIPIENTS_ROLENAME);
							   break;
							   case 4:
							        Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_PARTNERS_ROLENAME);
							   break;
						}
					}
				}
			}
            if( $strNotEmptyValidator->isValid($memberData["code"]) ) {
				if( $existantMember          = $model->findRow($memberData["code"], "code", null , false ) ) {
					$errorMessages[]         = sprintf("Un member existant porte déjà le nom d'utilisateur %s", $memberData["code"]);
				}
			}							
			$memberData["avatar"]            = "";			
			$memberData["entrepriseid"]      = intval( $memberData["entrepriseid"] );			
			$memberData["civilite"]          = $stringFilter->filter( $memberData["civilite"] );
			$memberData["passport"]          = $stringFilter->filter( $memberData["passport"] );
			$memberData["nationalite"]       = $stringFilter->filter( $memberData["nationalite"] );
			$memberData["address"]           = $stringFilter->filter( $memberData["address"] );			
			$memberData["matrimonial"]       = $stringFilter->filter( $memberData["matrimonial"] );
			$memberData["fonction"]          = $stringFilter->filter( $memberData["fonction"] );
			$memberData["observations"]      = $memberData["observations"];
			$memberData["params"]            = "";
			$memberData["activated"]         = 1;
			$memberData["groupid"]           = 0;
			$memberData["creationdate"]      = time();
			$memberData["creatorid"]         = $me->userid;
			$memberData["updatedate"]        = 0;
			$memberData["updateduserid"]     = 0;
			
			if( empty( $errorMessages ) ){
				$memberEmptyData             = $model->getEmptyData();
				$insert_data                 = array_intersect_key( $memberData , $memberEmptyData );
				if( $dbAdapter->insert( $prefixName ."rccm_members" , $insert_data ) )	{
					$memberid                = $dbAdapter->lastInsertId();
					if(!$strNotEmptyValidator->isValid( $memberData["code"] )) {
						$code                = "CL-".sprintf("%06d", $memberid );
						$member              = $model->findRow( $memberid , "memberid" , null , false );
						$member->code        = $code;
						$member->save();
					}				
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"=>"Les informations du member ont été enregistrées avec succès "));
						exit;
					}
					$this->setRedirect("Les informations du member ont été enregistrées avec succès", "success");
					$this->redirect("admin/members/infos/id/". $memberid );
				}	else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Les informations du member n'ont pas été enregistrées pour des raisons inconnues"));
						exit;
					}
					$this->setRedirect("Les informations du member n'ont pas été enregistrées pour des raisons inconnues", "error");
					$this->redirect("admin/members/list/");
				}		
			} else {
				$memberDefaultData   = $memberData;
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $message) {
						 $this->_helper->Message->addMessage($message , "error") ;
				}
			}
		}		
		$this->view->data          = $memberDefaultData;
		$this->view->groupes       = $groupes;
		$this->view->entreprise    = $this->view->data["entreprise"] = ( $entreprise ) ? $entreprise->libelle : null;
		$this->view->countries     = $countries;
		$this->view->cities        = $cities;
		$this->view->createAccount = $createAccount;
	}
	
	 public function editAction()
	{
		$model                     = $this->getModel("member");
		$modelCountry              = $this->getModel("country");
		$modelCity                 = $this->getModel("countrycity");
		$modelEntreprise           = $this->getModel("entreprise");
		$modelProfile              = $this->getModel("profile");
		$modelCoordonnees          = $this->getModel("profilecoordonnee");
		$memberid                  = intval($this->_getParam("id", $this->_getParam("memberid", 0 )));
		$member                    = $model->findRow( $memberid , "memberid", null , false );
		if(!$member ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid ) , "error");
			$this->redirect("admin/members/list");
		}
		$defaultData                = $member->toArray();
		$defaultData["entrepriseid"]= $entrepriseid = intval($this->_getParam("entrepriseid",$this->_getParam("entreprise", $member->entrepriseid )));
		$errorMessages              = array();
		$entreprise                 = ( $entrepriseid ) ? $modelEntreprise->findRow( $entrepriseid,"entrepriseid",null, false ) : null;
	    $country                    = $this->_getParam("country", null);
		$countries                  = $modelCountry->getSelectListe("Selectionnez un pays",array("code","libelle"), array("orders" => array("libelle ASC")), null , null , false );
        $cities                     = ((null!==$country)) ? $modelCity->getSelectListe("Selectionnez une ville",array("localiteid","city_name"), array("country_iso_code"=>$country,"orders" => array("libelle ASC")), null , null , false ) : array(0=>"Sélectionnez d'abord un pays");
		if( $this->_request->isPost()  )    {
			$postData               = $this->_request->getPost();
			$memberData             = array_merge( $defaultData , $postData );
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $userTable->info("namePrefix");
			
			$memberAccount          = ($member->accountid)? Sirah_Fabric::getUser($member->accountid) : null;
			$myDefaultData          = ($member->accountid && $memberAccount )? $memberAccount->getTable()->getData() : array("userid"=>0,"username"=>null,"email"=>null);
            $formAccountData        = array_intersect_key($postData, $myDefaultData);
			$accountData            = array_merge($myDefaultData, $formAccountData );			
				
			//On crée les validateurs nécessaires
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$usernameValidator           = new Sirah_Validateur_Username();
			$emailValidator              = new Sirah_Validateur_Email();
			$passwordValidator           = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(4);
				
			$birthdayDate                = (isset($postData["birthday"] ))?$postData["birthday"]  : "" ;
			$birthdayTms                 = 0;
			if( Zend_Date::isDate($birthdayDate, "dd/MM/YYYY")) {				
				$zendBirthday            = new Zend_Date($birthdayDate, "dd/MM/YYYY");
                $birthdayTms             = ($zendBirthday) ? $zendBirthday->get(Zend_Date::TIMESTAMP)  : 0;				
			    $memberData["birthday"]  = ($zendBirthday) ? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "";
			} else {
				$zendBirthday            =  new Zend_Date(array("year" =>(isset($postData["birthday_year"] ))? intval($postData["birthday_year"]) : 0,
					                                            "month"=>(isset($postData["birthday_month"]))? intval($postData["birthday_month"]): 0,
					                                            "day"  =>(isset($postData["birthday_day"]  ))? intval($postData["birthday_day"])  : 0 ) );
			    $birthdayTms             = $zendBirthday->get(Zend_Date::TIMESTAMP);							
			}	
            $memberData["birthday"]      = ($zendBirthday) ? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "0000-00-00 00:00:00";
	        if(!$emailValidator->isValid($memberData["email"] ) ) {
				$errorMessages[]         = "Veuillez fournir une adresse email valide";
			} elseif(($existantMember    = $model->findRow( $memberData["email"],"email",null,false)) && ($memberData["email"]!= $member->email)) {
				$errorMessages[]         = sprintf(" Un member du nom de %s %s existe déjà avec cette adresse email %s", $existantMember->lastname, $existantMember->firstname, $memberData["email"] );
		    } elseif(!$userTable->checkEmail($memberData["email"]) && ($memberData["email"] != $member->email) ){
				$errorMessages[]         = $errorMessages[]         = " L'adresse email ".$memberData["email"]." n'est pas valide ou peut etre associée à un autre compte ";
		    } else {
				$memberData["email"]     = $accountData["email"]    = $stringFilter->filter($memberData["email"]);
			}
			if((!$userTable->checkUsername($memberData["code"]))  && ($memberData["code"]!= $member->code)){
				$errorMessages[]         = " Le nom d'utilisateur ".$memberData["code"]." n'est pas valide ou peut etre associé à un autre compte ";
		    } else {
				$memberData["code"]      = $accountData["username"] = $memberData["code"];
			}
			if(!$strNotEmptyValidator->isValid($memberData["tel1"])) {
				$errorMessages[]         = " Veuillez entrer un numéro de téléphone mobile valide";
			} elseif(($existantMember    = $model->findRow( $memberData["tel1"], "tel1", null, false )) && ( $memberData["tel1"] != $member->tel1 ) ) {
				$errorMessages[]         = sprintf(" Un member du nom de %s %s existe déjà avec ce numéro %s", $existantMember->lastname, $existantMember->firstname, $memberData["tel1"] );
		    } else {
				$memberData["tel1"]      = $accountData["phone1"] = $stringFilter->filter($memberData["tel1"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["firstname"])) {
				$errorMessages[]         = "Le prénom que vous avez saisi, est invalide";
			} else {
				$memberData["firstname"] = $stringFilter->filter($memberData["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["lastname"])) {
				$errorMessages[]         = "Le nom de famille que vous avez saisi est invalide";
			} else {
				$memberData["lastname"]  = $stringFilter->filter($memberData["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid($memberData["sexe"]) || ( ( $memberData["sexe"] != "M" )  && ( $memberData["sexe"] != "F" ) ) ) {
				$errorMessages[]         = "Veuillez entrer un sexe valide , doit etre égal à M ou F";
			} else {
				$memberData["sexe"]      = $stringFilter->filter( $memberData["sexe"] );
			}
			if( !intval( $memberData["groupid"]) || !isset( $groupes[$memberData["groupid"]] )) {
				$errorMessages[]         = "Veuillez selectionner un groupe valide pour le member";
			} else {
				$memberData["groupid"]   = intval( $memberData["groupid"] );
			}
			$memberData["code"]          = $stringFilter->filter($memberData["code"] );
			$memberData["passport"]      = $stringFilter->filter($memberData["passport"] );
			$memberData["nationalite"]   = $stringFilter->filter($memberData["nationalite"] );
			$memberData["country"]       = $stringFilter->filter($memberData["country"] );
			$memberData["city"]          = intval( $memberData["city"] );
			$memberData["address"]       = $stringFilter->filter($memberData["address"] );
			$memberData["birthaddress"]  = $stringFilter->filter($memberData["birthaddress"] );
			$memberData["tel2"]          = $accountData["phone2"] = $stringFilter->filter($memberData["tel2"] );
			$memberData["matrimonial"]   = $stringFilter->filter($memberData["matrimonial"] );
			$memberData["fonction"]      = $stringFilter->filter($memberData["fonction"] );
			$memberData["observations"]  = $memberData["observations"];
			$memberData["updateduserid"] = $me->userid;
			$memberData["updatedate"]    = time();
	
			$defaultData                 = $memberData;	
            $accountData["password"]     = (isset($postData["password"])) ? $postData["password"] : "";
			if( $strNotEmptyValidator->isValid($accountData["password"])){			
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]     = "Des données de création de compte sont manquantes";
				} elseif($postData["confirmedpassword"] !== $accountData["password"]) {
					$errorMessages[]     = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			} else {
				unset($accountData["password"]);
			}			
			//on sauvegarde la table
			$member->setFromArray( $memberData );
			if( empty($errorMessages ) ) {
				if( $member->save() ) {
					if( $memberAccount && $member->accountid ) {						
						$accountData["lastUpdatedDate"]   = time();
						$accountData["updateduserid"]     = $me->userid;
						$memberAccount->clearMessages();
						if(!$memberAccount->save( $accountData )){
							$saveErrors                   = $memberAccount->getMessages();
							foreach( $saveErrors as $type => $msg){
									 $msg                 = (is_array($msg)) ? array_shift($msg)  : $msg;
									 $errorMessages[]     = $msg;
							}
						} else {
							$memberProfile                = $modelProfile->getRow($member->accountid  , true , false );
							$profileid                    = ($memberProfile)? $memberProfile->profileid : 0;
							$memberProfileCoordonnees     = ($memberProfile)? $modelCoordonnees->findRow($profileid,"profileid" , null , false ) : null ;
							if( $memberProfile ) {
								$profileData              = $accountData;
								if( isset($profileData["profileid"])) {
									unset($profileData["profileid"]);
								}
								$profileData["userid"]    = $member->accountid;
								$profileData["matricule"] = $accountData["username"];
								$profileData["birthday"]  = $birthdayTms;
								$memberProfile->setFromArray( $profileData );
								$memberProfile->save();
							}
							if( $memberProfileCoordonnees) {
								$coordonneesData               = $memberData;
								$coordonneesData["tel_mob"]    = $memberData["tel1"];
								$coordonneesData["tel_bureau"] = $memberData["tel2"];
								$memberProfileCoordonnees->setFromArray( $coordonneesData );
								$memberProfileCoordonnees->save();
							}						
						}
					}					
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("success"=>"Les informations du member ont été mises à jour avec succès"));
						exit;
					}
					$this->setRedirect("Les informations du member ont été mises à jour avec succès", "success");
					$this->redirect("admin/members/infos/memberid/".$memberid );
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modification n'a été faite dans les informations du member"));
						exit;
					}
					$this->setRedirect("Aucune modification n'a été faite dans les informations du member", "error");
					$this->redirect("admin/members/infos/memberid/".$memberid );
				}
			} else {
				$defaultData   = $update_data;
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
		if(!isset($defaultData["birthday_year"]) && intval($defaultData["birthday"])) {
			$zendBirthDay                  = new Zend_Date($defaultData["birthday"] , Zend_Date::DATETIME );
			$defaultData["birthday_year"]  = $zendBirthDay->get(Zend_Date::YEAR);
			$defaultData["birthday_month"] = $zendBirthDay->get(Zend_Date::MONTH);
			$defaultData["birthday_day"]   = $zendBirthDay->get(Zend_Date::DAY);
		} else {
			$defaultData["birthday_year"]  = "0000";
			$defaultData["birthday_month"] = "00";
			$defaultData["birthday_day"]   = "00";
		}
		$defaultData["sexe"]               = (!empty( $defaultData["sexe"] )) ? $defaultData["sexe"] : "M";
		$defaultData["birthday_day"]       = date("d", $member->birthday);
		$defaultData["birthday_month"]     = date("m", $member->birthday);
 
		$this->view->data                  = $defaultData;
		$this->view->memberid              = $memberid;
		$this->view->countries             = $countries;
		$this->view->cities                = $cities;
		$this->view->title                 = sprintf("Mettre à jour les informations du member %s %s", $member->lastname, $member->firstname );
	    $this->view->entreprise            =  $this->view->data["entreprise"] = ( $entreprise ) ? $entreprise->libelle : null;
		$this->render("edit")	;
	}
	
	
	public function deleteAction()
	{
		$memberids     = $this->_getParam("memberids", $this->_getParam("ids", array()));
		$errorMessages = array();
		$model         = $this->getModel("member");
		$me            = Sirah_Fabric::getUser();
		$userTable     = $me->getTable();
		$dbAdapter     = $userTable->getAdapter();
		$prefixName    = $userTable->info("namePrefix");
		$isAdmin       = $me->isAdmin();
		$userid        = $me->userid;
		
		if(!is_array( $memberids ) ) {
			$memberids = explode(",", $memberids );
		}
		if( empty( $memberids ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/members/list");
		}
	    if( count(  $memberids ) ) {
			foreach($memberids as $memberid ) {				    
					$memberRow             = $model->findRow( $memberid, "memberid", null, false);
					if( $memberRow ) { 
					    $deleteWhere       = array("memberid='".$memberid."'");
					    if(!$isAdmin ) {
						    $deleteWhere[] = "creatorid='".$userid."'";
					    }
						$accountid         = $memberRow->accountid;
						if(!$dbAdapter->delete( $prefixName."rccm_members", $deleteWhere)) {
							$errorMessages[]  = "Erreur de la base de donnée : le member id#$memberid n'a pas été supprimé ";
						} else {
							
							$dbAdapter->delete($prefixName."erccm_vente_commandes"          , $deleteWhere);
							$dbAdapter->delete($prefixName."erccm_vente_commandes_ligne"    , $deleteWhere);
							$dbAdapter->delete($prefixName."erccm_vente_commandes_invoices" , $deleteWhere);
							$dbAdapter->delete($prefixName."erccm_vente_commandes_paiements", $deleteWhere);
							$dbAdapter->delete($prefixName."reservation_demandeurs"         , array("accountid='".$accountid."'"));
							$dbAdapter->delete($prefixName."system_users_account"           , array("userid='".$accountid."'"));
							$dbAdapter->delete($prefixName."system_users_profile"           , array("userid='".$accountid."'"));
						}
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
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les comptes clients selectionnés ont été supprimés avec succès" ));
				exit;
			}
			$this->setRedirect("Les comptes clients selectionnés ont été supprimés avec succès" , "success");
		}
		$this->redirect("admin/members/list");		
	}
	
	
    public function infosAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		
		$model          = $this->getModel("member");
		$modelDemande   = $this->getModel("demande");
	    $errorMessages  = array();
		$me             = Sirah_Fabric::getUser();
		$memberid       = intval($this->_getParam("id", $this->_getParam("memberid" , 0 )));
		$member         = $model->findRow( $memberid , "memberid", null , false );
		if(!$member )  {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid ) , "error");
			$this->redirect("admin/members/list");
		}
        $groupid                      = intval( $member->groupid );	
		$accountid                    = $member->accountid;
		$demandeur                    = $member->demandeur($memberid);
        $this->view->member           = $this->view->client = $member;  	
		$this->view->paiements        = $member->paiements($memberid);		
		$this->view->commandes        = $member->commandes($memberid);
		$this->view->demandes         = $modelDemande->getList(array("accountid"=>$accountid));
		$this->view->demandeur        = $demandeur;
		$this->view->memberid         = $this->view->clientid = $memberid; 
		$this->view->accountid        = $accountid;
		$this->view->title            = "Les informations d'un compte client";
	}
				
	
	public function avatarAction()
	{
		$this->view->title           = "Remplacer la photo d'identité du member";
		$errorMessages               = array();
		
	    $model                       = $this->getModel("member");
		$memberid                    = intval( $this->_getParam("memberid", $this->_getParam("id", 0 ) )  );
		$member                      = $model->findRow( $memberid , "memberid", null , false );
	
		if( !$member->memberid  ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" =>  "Les paramètres fournis pour l'exécution de cette requete ne sont pas valides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ne sont pas valides" , "error");
			$this->redirect("admin/members/list");
		}		
		if(    $this->_request->isPost() ) {			
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter        =   new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
	
				$avatarUpload = new Zend_File_Transfer();
				//On inclut les différents validateurs de l'avatar
				$avatarUpload->addValidator('Count',false,1);
				$avatarUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
				$avatarUpload->addValidator("FilesSize",false,array("max"      => "4MB"));
				$avatarUpload->addValidator("ImageSize",false,array("minwidth" => 10,"maxwidth" => 3200,"minheight"=> 10,"maxheight" => 2800));
				$avatarExtension   = Sirah_Filesystem::getFilextension($avatarUpload->getFileName('memberavatar'));
				$currentAvatar     = $member->avatar;
					
				//On inclut les différents filtres de l'avatar
				$avatarBaseName    = time() . $memberid . "avatar." . $avatarExtension;
				$originalFilename  = APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . 'original' . DS . $avatarBaseName ;
				$avatarUpload->addFilter("Rename" , array("target" => $originalFilename , "overwrite" => true) , "memberavatar");
				//On upload l'avatar de l'utilisateur
				if( $avatarUpload->isUploaded("memberavatar") ) {
					$avatarUpload->receive("memberavatar");
				} else {
					$errorMessages[]  = "L'avatar fourni n'est pas valide";
				}
				if($avatarUpload->isReceived("memberavatar")) {
					//on supprime l'avatar existant de l'utilisateur
					if((null != $currentAvatar) && !empty($currentAvatar) && Sirah_Filesystem::exists(USER_AVATAR_PATH .DS . 'original' . DS . $currentAvatar )){
						@unlink( APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . 'thumb'    . DS . $currentAvatar );
						@unlink( APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . 'mini'     . DS . $currentAvatar );
						@unlink( APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . 'original' . DS . $currentAvatar );
					}
					//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
					$avatarImage  = Sirah_Filesystem_File::fabric("Image" , $originalFilename , "rb+");
					$avatarImage->resize("180", null , true , APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . "mini" );
					$avatarImage->resize("90" , null , true , APPLICATION_DATA_PATH . DS . "members" . DS . "avatars" . DS . "thumb" );
	
					$member->avatar     =  $avatarBaseName;
					if(!$member->save()) {
						$errorMessages[] = "Les informations de la photo n'ont pas été correctement enregistrées dans la base de données";
					}
				} else {
					$uploadMessages = $avatarUpload->getErrors();
					if(!empty($uploadMessages)) {
						foreach( $uploadMessages as $key => $errorCode ) {
							     $errorMessages[]  = Sirah_Controller_Default::getUploadMessage( $errorCode );
						}
					}
				}
				if(!empty($errorMessages)){
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages ) ));
						exit;
					}
					foreach( $errorMessages as $errorMessage ) {
						     $this->getHelper("Message")->addMessage($errorMessage , "error");
					}
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {
						clearstatcache();
						$basePath    = str_replace(APPLICATION_PATH , ROOT_PATH . DS ."myV1"  ,  DOCUMENTS_PATH . "members" . DS . "avatars" );
						$avatarPath  = str_replace( DS , "/" , $basePath . DS . "mini" .DS );
						$returnJson  = array("success" => sprintf("La photo de %s %s a été mise à jour avec succès", $member->lastname , $member->firstname),
								             "files"   => array(array("name" => $avatarBaseName, "extension" => $avatarExtension, "path" => $avatarPath )) );
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson($returnJson);
						exit;
					}
					$this->setRedirect( sprintf("La photo de %s %s a été mise à jour avec succès", $member->lastname , $member->firstname) ,"success");
					$this->redirect("admin/members/infos/id/".$memberid );
				}
		}
		$this->view->memberid  = $memberid;
		$this->view->member   = $member;
		$this->render("avatarupload");
	}
		
	
	public function uploadAction()
	{
		$this->view->title = "Enregistrer un nouveau document";
	
		$model             = $this->getModel("member");
		$memberid          = intval($this->_getParam("id" , $this->_getParam("memberid" , 0 )));
		$creator           = $me = Sirah_Fabric::getUser( );
		$member            = $model->findRow( $memberid , "memberid" , null , false );
		
		if( !$member->memberid ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid )));
				exit;
			}
			$this->setRedirect( sprintf("Aucun compte client valide n'a été retrouvé avec l'identifiant %s " , $memberid ) , "error");
			$this->redirect("admin/members/list");
		}	
		$modelDocument             = $this->getModel("document");
		$modelCategory             = $this->getModel("documentcategorie");

		$defaultData               = $modelDocument->getEmptyData();

		$errorMessages             = array();
		$uploadedFiles             = array();
		$categories                = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
	
		if( $this->_request->isPost()  ) {
			$postData                     = $this->_request->getPost();
	
			$formData                     = array_intersect_key( $postData ,  $defaultData )	;
			$documentData                 = array_merge( $defaultData ,  $formData );
			$userTable                    = $me->getTable();
			$dbAdapter                    = $userTable->getAdapter();
			$prefixName                   = $userTable->info("namePrefix");
			$memberDataPath               = APPLICATION_DATA_PATH . DS . "members" . DS . "scans";
			if( !is_dir( $memberDataPath )) {
				 $errorMessages[]         = "Le dossier de stockage des documents de l'utilisateur concerné n'est pas créé, veuillez l'indiquer à l'administrateur ";
			}
				//On crée les filtres qui seront utilisés sur les paramètres de recherche
				$stringFilter          = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
					
				//On crée un validateur de filtre
				$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
				$documentData["category"]       = intval( $documentData["category"] );
				$documentData["resource"]       = ( isset( $postData["resource"] ) )   ? $stringFilter->filter($postData["resource"]) : "members" ;
				$documentData["resourceid"]     = ( isset( $postData["resourceid"] ) ) ? intval($postData["resourceid"]) : 0 ;
				$documentData["filedescription"]= $stringFilter->filter( $documentData["filedescription"] );
				$documentData["filemetadata"]   = $stringFilter->filter( $documentData["filemetadata"]    );
	
				$userMaxFileSize                = 32;
				$userMaxUploadFileSize          = 25;
				$userSingleFileSize             = 5;
				$userTotalFiles                 = 10;
	
				$documentsUpload                = new Zend_File_Transfer("Http", false , array("useByteString" => false ));
				$documentsUpload->addValidator("Count"    , false , 1 );
				$documentsUpload->addValidator("Extension", false , array("csv", "xls", "xlxs", "pdf","png", "gif", "jpg", "docx" , "doc" , "xml"));
				$documentsUpload->addValidator("Size"     , false , array("max"  => $userSingleFileSize."MB"));
				$documentsUpload->addValidator("FilesSize", false , array("max"  => $userSingleFileSize."MB"));
					
				$basicFilename                  = $documentsUpload->getFileName('memberdocument', false );
				$documentExtension              = Sirah_Filesystem::getFilextension($basicFilename);
				$tmpFilename                    = Sirah_Filesystem::getName( $basicFilename );
				$fileSize                       = $documentsUpload->getFileSize('memberdocument');
				$userFilePath                   = $memberDataPath . DS . time() . "_" .  $basicFilename;
					
				$documentsUpload->addFilter("Rename" , array("target" => $userFilePath , "overwrite" => true) , "memberdocument");
				//On upload l'avatar de l'utilisateur
				if( $documentsUpload->isUploaded("memberdocument")){
					$documentsUpload->receive("memberdocument");
				} else {
					$errorMessages[]  = " Le document que vous avez chargé n'est pas valide";
				}
				if( $documentsUpload->isReceived("memberdocument") ) {
					$myFilename                     = ( isset( $postData["filename"] ) && $strNotEmptyValidator->isValid( $postData["filename"] ) ) ? $stringFilter->filter( $postData["filename"] ) : $tmpFilename;
					$documentData["filename"]       = "Fo-".sprintf("%04d", $memberid)."-".$myFilename;
					$documentData["filepath"]       = $userFilePath ;
					$documentData["filextension"]   = $documentExtension;
					$documentData["filesize"]       = floatval($fileSize);
					$documentData["creationdate"]   = time();
					$documentData["creatoruserid"]  = $documentData["userid"]  = $creator->userid;
					if( $dbAdapter->insert( $prefixName . "system_users_documents"  , $documentData ) ) {
						$documentid                 = $dbAdapter->lastInsertId();	
						if( $dbAdapter->insert( $prefixName . "rccm_members_documents"  , array("memberid" => $memberid, "documentid" => $documentid ) ))	{				
						    $uploadedFiles[$documentid] = $documentData;
						}    
					} else {
						$errorMessages[]            = "Les informations du document n'ont pas été enregistrées dans la base de données";
					}
				} else {
					$errorMessages[]                = "Le document n'a pas été chargé correctement sur le serveur";
				}
				if( empty($errorMessages ) ) {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						$jsonArray             = array();
						$jsonArray["success"]  = "Le document a été enregistré avec succès";
						$jsonArray["document"] = $documentData ;
						echo ZendX_JQuery::encodeJson( $jsonArray );
						exit;
					}
					$this->_helper->Message->addMessage("Le document du member a été enregistré avec succès" , "success");
				} else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
						exit;
					}
					foreach( $errorMessages as $errorMessage){
						     $this->getHelper("Message")->addMessage($errorMessage , "error");
					}
				}
		}
		$this->view->categories = $categories;
		$this->view->data       = $defaultData;
		$this->view->memberid   = $memberid;
		$this->view->member     = $member;
	}

    public function disableAction()
	{
		$memberids       = $this->_getParam("memberids",$this->_getParam("ids",array()));
		$errorMessages   = array();
		
		if(!is_array($memberids) ){
			$memberids   = explode("," , $memberids);
		}		
		if( empty($memberids)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/members/list");
		} 	
     	$me                  = Sirah_Fabric::getUser();
		$model               = $this->getModel("member");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$tablePrefix         = $modelTable->info("namePrefix");	
		$tableName           = $modelTable->info("name");
		foreach( $memberids as $memberid ){
			     if( intval($memberid) ) {
					 if( $dbAdapter->update($tableName,array("activated"=>0)   , array("memberid=?"=>$memberid))) {
						 if( $accountid   = $model->accountid($memberid)) {
							 $dbAdapter->update($tablePrefix."system_users_account", array("activated"=>0), array("accountid=?"=>$accountid));
						 }						 
					 } else {
						 $memberRow           = $model->findRow($memberid,"memberid",null,false);
						 if( $memberRow ) {
							 $errorMessages[] = sprintf("Le compte de %s n'a pas pu être désactivé", $memberRow->name);
						 }						 
					 }
				 }					 
		}
		if(!empty($errorMessages)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "La désactivation des comptes s'est produite avec succès"));
				exit;
			}
			    $this->_helper->Message->addMessage("La désactivation des comptes s'est produite avec succès","success");
		}
		$this->redirect("admin/members/list");
	}


    public function enableAction()
	{
		$memberids       = $this->_getParam("memberids",$this->_getParam("ids",array()));
		$errorMessages   = array();
		
		if(!is_array($memberids) ){
			$memberids   = explode("," , $memberids);
		}		
		if( empty($memberids)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/members/list");
		} 	
     	$me                  = Sirah_Fabric::getUser();
		$model               = $this->getModel("member");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$tablePrefix         = $modelTable->info("namePrefix");	
		$tableName           = $modelTable->info("name");
		foreach( $memberids as $memberid ){
			     if( intval($memberid) ) {
					 if( $dbAdapter->update($tableName,array("activated"=>1), array("memberid=?"=>$memberid))) {
						 if( $accountid       = $model->accountid($memberid)) {
							 $dbAdapter->update($tablePrefix."system_users_account", array("activated"=>1), array("accountid=?"=>$accountid));
						 }						 
					 } else {
						 $memberRow           = $model->findRow($memberid,"memberid",null,false);
						 if( $memberRow ) {
							 $errorMessages[] = sprintf("Le compte de %s n'a pas pu être activé", $memberRow->name);
						 }						 
					 }
				 }					 
		}
		if(!empty($errorMessages)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
				     $this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "L'activation des comptes s'est produite avec succès"));
				exit;
			}
			    $this->_helper->Message->addMessage("L'activation des comptes s'est produite avec succès","success");
		}
		$this->redirect("admin/members/list");
	}	
}