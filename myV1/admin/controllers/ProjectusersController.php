<?php

class ProjectusersController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{
	   if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
	   $cacheManager       = Sirah_Fabric::getCachemanager();
	   $users              = array();
	   $usersListePaginator= null;
	   
	   //On crée les filtres qui seront utilisés sur les paramètres de recherche
	   $stringFilter       = new Zend_Filter();
	   $stringFilter->addFilter(new Zend_Filter_StringTrim());
	   $stringFilter->addFilter(new Zend_Filter_StripTags());
	   
	   //On crée un validateur de filtre
	   $strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	   
	   $params             = $this->_request->getParams();
	   $userListCacheKey   = "usersListe";
	   $pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
	   $pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 20;
	   
	   $defaultName        = (isset($params["global-filter"]) && !empty($params["global-filter"])) ? $stringFilter->filter($params["global-filter"]) : null;
	   
	   $filters            = array("lastname"=> null, "firstname" => null, "username" => null, "language"=> null, "email"   => null,
	   		                       "phone"   => null, "activated" => null, "locked"=> null, "blocked"  => null  , "expired" => null,
	   		                       "roleid"  => 0   , "sexe"      => null, "admin"  => null, "country" => null  , "name"    => $defaultName);
	   
	   if(!empty(  $params)) {
	   	  foreach( $params as $filterKey => $filterValue){
	   	  	       $filters[$filterKey]  =  $stringFilter->filter( $filterValue );
	   	  }
	   }
	   if( empty( $filters["name"] ) && (!empty($filters["lastname"]) || !empty($filters["firstname"]))) {
		   $filters["name"] = sprintf("%s %s", $filters["lastname"], $filters["firstname"] );
	   }	   
	   $filters["rolename"] = "commerciaux";
	   $users               = Sirah_User_Table::getUsers($filters , $pageNum , $pageSize);
       $usersListePaginator = Sirah_User_Table::getUsersPaginator( $filters );      	      
       if(null !== $usersListePaginator) {
       	  $usersListePaginator->setCurrentPageNumber($pageNum);
       	  $usersListePaginator->setItemCountPerPage($pageSize);
       }
	   $this->view->columns   = array("left");
	   $this->view->title     = "Vos collaborateurs";
	   $this->view->users     = $users;
	   $this->view->filters   = $filters;
	   $this->view->pageNum   = $pageNum;
	   $this->view->pageSize  = $pageSize;
	   $this->view->paginator = $usersListePaginator;
	}
	
	public function createAction()
	{
		//On initialise les variables nécessaires
		$guestUser             = Sirah_Fabric::getUser(0);
		$me                    = Sirah_Fabric::getUser();
		$modelAgence           = $this->getModel("agence");
        $errorMessages         = array();
        $viewData              = array();
        $urlDone               = $this->_getParam("done", "projectusers/list");
        
        //S'il n'est pas connecté, on ne lui autorise pas l'accès à cette opération
        if(!$me->isLoggedIn()){
        	$this->setRedirect("Vous n'etes pas autorisé à effectuer cette opération","error");
        	$this->redirect("myaccount/login");
        }
        $defaultData           = array("firstname"          => ""  , "lastname"   => "","phone1" => "",  "phone2"      => "",
				                       "address"            => ""  , "zipaddress" => "","city"   => "",  "country"     => "",
				                       "language"           => "FR", "facebookid" => "","skypeid"=> "",  "username"    => "",
				                       "password"           => ""  , "email"      => "","sexe"   => null, "expired"     => 0,
				                       "activated"          => 1   , "blocked"    => 0 ,"locked" => 0, "accesstoken" => "",
				                       "logintoken"         => ""  , "params"     => "",
				                       "admin"              => 0,
				                       "statut"             => 0,
				                       "creatoruserid"      => $me->userid,
				                       "updateduserid"      => 0,
				                       "registeredDate"     => time(),
				                       "lastConnectedDate"  => 0,
				                       "lastUpdatedDate"    => 0,
				                       "lastIpAddress"      => "",
				                       "lastHttpClient"     => "",
				                       "lastSessionId"      => "" );
        
        $defaultSession         = new Zend_Session_Namespace("accounts-creation");
        if(!$defaultSession->initialised){
        	$defaultSession->initialised = true;
        	$defaultSession->setExpirationSeconds(86400);
        }		
		if( $this->_request->isPost()){
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ){
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$urlDone          = Sirah_Functions::url($this->view->url(array("controller" => "projectusers", "action"  => "create")),"t=".$defaultSession->token,81 , APPLICATION_HOST);
				$urlSecurityCheck = $this->view->url(array("controller"  => "securitycheck", "action"  => "captcha", "done" => $urlDone));
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide ou expiré", "reload" => true, "newurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide ou expiré","error");
				$this->redirect($urlSecurityCheck);
			}			
		   $postData           = $viewData = $this->_request->getPost();		   
		   $insert_data        = array_merge($defaultData,$postData);
		   $newuserid          = 0;
		   $userTable          = $guestUser->getTable();
		   $dbAdapter          = $userTable->getAdapter();
		   $prefixName         = $userTable->info("namePrefix");
		   if( empty( $urlDone ) ) {
		   	   $urlDone        = "projectusers/list";
		   }		   
		   //On crée les filtres qui seront utilisés sur les données du formulaire
		   $stringFilter       = new Zend_Filter();
		   $stringFilter->addFilter(new Zend_Filter_StringTrim());
		   $stringFilter->addFilter(new Zend_Filter_StripTags());
		   
		   //On crée les validateurs nécessaires
		   $strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		   $usernameValidator   = new Sirah_Validateur_Username();
		   $emailValidator      = new Sirah_Validateur_Email();
		   $passwordValidator   = new Sirah_Validateur_Password();
		   $passwordValidator->setMinlength(5)->strict(false);
		   
		   if(!$strNotEmptyValidator->isValid($insert_data["firstname"]) || ($insert_data["firstname"]=="anonyme")){
		   	   $errorMessages[]          = " Le prénom de l'utilisateur est une chaine invalide ";
		   } else {
		   	   $insert_data["firstname"] = $stringFilter->filter($insert_data["firstname"]);
		   }
		   
		   if(!$strNotEmptyValidator->isValid($insert_data["lastname"]) || $insert_data["lastname"]=="anonyme"){
		   	  $errorMessages[]           = " Le nom de l'utilisateur est une chaine invalide ";
		   } else {
		   	  $insert_data["lastname"]   = $stringFilter->filter($insert_data["lastname"]);
		   }		   			   
		   if(!$strNotEmptyValidator->isValid($insert_data["country"])){
		   	   $errorMessages[]          = " Veuillez indiquer un pays valide ";
		   } else {
		   	   $insert_data["country"]   = $stringFilter->filter($insert_data["country"]);
		   }
		   $insert_data["phone1"]        = intval( $insert_data["phone1"] );
		   $insert_data["phone2"]        = intval( $insert_data["phone2"] );
		   if(!$insert_data["phone1"]){
		   	   $errorMessages[]          = " Veuillez indiquer un numéro de téléphone valide ";
		   } elseif( $userTable->find(array("phone1" => $insert_data["phone1"] ) ) ) {
		   	   $errorMessages[]          = " Le numéro de téléphone n'est pas valide, car il est associé à un compte existant ";
		   }		   
		   if(!$userTable->checkUsername($insert_data["username"])){
				$errorMessages[]         = " Le nom d'utilisateur ".$insert_data["username"]." n'est pas valide ou peut etre associé à un second compte ";
			}			
	       if(!$userTable->checkEmail($insert_data["email"])){
				$errorMessages[]          = " L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}			 
		   if(!$passwordValidator->isValid($insert_data["password"])){
				$errorMessages[]          = implode(" et  " , $passwordValidator->getMessages())   ;
			} else {
				$insert_data["password"]  = $postData["password"];				
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]      = "Des données de création de compte sont manquantes";
				} elseif($postData["confirmedpassword"] !== $insert_data["password"]) {
					$errorMessages[]      = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			}									
		   $insert_data["address"]        = $stringFilter->filter($insert_data["address"]);
		   $insert_data["zipaddress"]     = $stringFilter->filter($insert_data["zipaddress"]);
		   $insert_data["language"]       = $stringFilter->filter($insert_data["language"]);
		   $insert_data["facebookid"]     = $stringFilter->filter($insert_data["facebookid"]);
		   $insert_data["skypeid"]        = $stringFilter->filter($insert_data["skypeid"]);
		   $insert_data["sexe"]           = $stringFilter->filter($insert_data["sexe"]);
		   $insert_data["lastIpAddress"]  = 0;
		   $insert_data["lastHttpClient"] = 0;
		   $insert_data["lastSessionId"]  = "";
		   $insert_data["activated"]      = (isset($insert_data["activated"]))   ? intval($insert_data["activated"]) : 1;
		   $insert_data["blocked"]        = (isset($insert_data["blocked"]))     ? intval($insert_data["blocked"])   : 0;
		   $insert_data["locked"]         = (isset($insert_data["locked"]))      ? intval($insert_data["locked"])    : 0;		   
		   $defaultData                   = $insert_data;		   
		   if(empty($errorMessages)){
				$guestUser->clearMessages();
				if(!$newuserid   = $guestUser->save($insert_data)){
					$saveErrors  = $guestUser->getMessages("error");
					foreach( $saveErrors as $type => $msg){
						     $msg             = (is_array($msg)) ? array_shift($msg)  : $msg;
						     $errorMessages[] = $msg;
					}
				}
			}	
		   if(!empty($errorMessages)){
		       if(  $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
					exit;
				}
				foreach( $errorMessages as $errorMessage ) {
					     $this->getHelper("Message")->addMessage($errorMessage,"error");
				}
		   }  else {
		   	  //On lui assigne le role par defaut des utilisateurs et le role commerciaux
		   	  Sirah_User_Acl_Table::assignRoleToUser( $newuserid, APPLICATION_DEFAULT_USERS_ROLENAME);
		   	  Sirah_User_Acl_Table::assignRoleToUser( $newuserid, "Commerciaux");  			  
			  $agenceid   = (isset($postData["agenceid"] )) ? intval($postData["agenceid"]) : 0;
			  if( $agenceid ) {
				  $agenceUser = array("userid"   => $newuserid, "periodstart"=> 0, "periodend"    =>0, "creationdate"=> time(),
							     	  "creatorid"=> $me->userid,"updatedate" => 0, "updateduserid"=>0, "agenceid"    => $agenceid);
				  $dbAdapter->insert( $prefixName . "gestoptic_projet_agences_users", $agenceUser );
			  }		   	   
		   	  if(empty($errorMessages)) {
		   	  	$cacheManager        = Sirah_Fabric::getCachemanager();
		   	  	 if($cacheManager->hasCache("userListe")){
		   	  		$userListeCache  = $cacheManager->getCache("userListe");
		   	  	 } else {
		   	  		$userListeCache  = Sirah_Cache::getInstance("userListe","Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
		   	  	 }
		   	  	 if(false !== ( $users = $userListeCache->load("usersListe"))){
		   	  		$userListeCache->remove("usersListe");
		   	  	 }
		   	  	 $this->setRedirect(sprintf("La création du compte du collaborateur %s, s'est effectuée avec succès",$newUser->lastname." ".$newUser->firstname),"success");
		   	  	 $this->redirect("projectusers/list");		   	  	
		   	  } else {
		   	  	 $this->setRedirect(implode(" ", $errorMessages),"message");
		   	  	 $this->redirect( $urlDone);
		   	  }   	  		   	  		   	  		   	  		   	  		   	  
		   }					   
		}        
		//On crée un jeton de connexion
		if(!isset($defaultSession->token)){
			$defaultSession->token = Sirah_User_Helper::getToken(15).time();
		}		
		$this->view->title    = "Creation du compte d'un collaborateur sur la plateforme ";
		$this->view->data     = $defaultData;
		$this->view->token    = $defaultSession->token;
		$this->view->agences  = $modelAgence->getSelectListe("Selectionenz une boutique", array("agenceid","libelle"), array() , null , null , false );
		$this->view->agenceid = intval($this->_getParam("agenceid", 0));
	}	
	
	
	public function editAction()
	{
		$edituserid            = intval($this->_getParam("userid", $this->_getParam("id",0)));
		$userData              = array();
		$errorMessages         = array();
		$me                    = Sirah_Fabric::getUser();
		$editedUser            = Sirah_Fabric::getUser($edituserid);
		$userTable             = $editedUser->getTable();
		$userData              = $viewData = $userTable->getData();
		$modelAgence           = $this->getModel("agence");
		
		if(!$edituserid || !$userTable->userid){
			$this->setRedirect("Impossible d'effectuer cette opération, car vous avez fourni des paramétres invalides","error");
			$this->redirect("projectusers/list");
		}				
		$defaultSession         = new Zend_Session_Namespace("accountsedit");
		if(!$defaultSession->initialised){
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}		
		if($this->_request->isPost()){			
		//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ){
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$urlDone          = Sirah_Functions::url($this->view->url(array("controller" => "projectusers", "action"=> "edit")),"t=".$defaultSession->token,81 , APPLICATION_HOST);
				$urlSecurityCheck = $this->view->url(array("controller"=> "securitycheck", "action"=> "captcha", "done" => $urlDone));
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide", "reload" => true, "reloadurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide","error");
				$this->redirect($urlSecurityCheck);
			}
			$postData           = $this->_request->getPost();
			$dbAdapter          = $userTable->getAdapter();
			$prefixName         = $userTable->info("namePrefix");
			$update_data        = array_merge( $userData , $postData );
			 
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$usernameValidator   = new Sirah_Validateur_Username();
			$emailValidator      = new Sirah_Validateur_Email();
			$passwordValidator   = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(5)->strict(false);
			
			$update_data["username"]     = $stringFilter->filter($update_data["username"]);
			$update_data["email"]        = $stringFilter->filter($update_data["email"]);
			$postData["password"]        = $update_data["password"]  = (isset($postData["password"])) ? $postData["password"] : "";
			
			$update_data["phone1"]       = intval($update_data["phone1"] );
			$update_data["phone2"]       = intval($update_data["phone2"] );
			if(!$update_data["phone1"]){
				$errorMessages[]         = " Veuillez indiquer un numéro de téléphone valide ";
			} elseif( $userTable->find(array("phone1" => $update_data["phone1"] ) ) && $userTable->phone1 != $update_data["phone1"]  ) {
				$errorMessages[]         = " Le numéro de téléphone n'est pas valide, car il est associé à un compte existant ";
			}			 
			if(!$strNotEmptyValidator->isValid($update_data["firstname"]) || ($update_data["firstname"]=="anonyme")){
				$errorMessages[]         = " Le prénom que vous avez saisi est invalide ";
			} else {
				$update_data["firstname"]= $stringFilter->filter($update_data["firstname"]);
			}			 
			if(!$strNotEmptyValidator->isValid($update_data["lastname"]) || ($update_data["lastname"]=="anonyme")){
				$errorMessages[]         = " Le nom que vous avez saisi est invalide ";
			} else {
				$update_data["lastname"] = $stringFilter->filter($update_data["lastname"]);
			}			 
		    if(!$userTable->checkUsername($update_data["username"])){
				$errorMessages[]         = " Le nom d'utilisateur ".$update_data["username"]." n'est pas valide ou peut etre associé à un autre compte ";
		    }			
		    if(!$userTable->checkEmail($update_data["email"])){
				$errorMessages[]         = " L'adresse email ".$update_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}			
			if($strNotEmptyValidator->isValid($update_data["password"])){
			  if(!$passwordValidator->isValid($update_data["password"])){
				  $errorMessages[]       = implode(" ,  " , $passwordValidator->getMessages())   ;
			  } else {
				$update_data["password"] = $postData["password"];			
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]     = "Des données de création de compte sont manquantes";
				} elseif($postData["confirmedpassword"] !== $update_data["password"]) {
					$errorMessages[]     = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			  }
			} else {
				unset($update_data["password"]);
			}   
		    $update_data["lastUpdatedDate"] = time();
		    $update_data["updateduserid"]   = $me->userid;
		    $update_data["country"]         = $stringFilter->filter($update_data["country"]);
		    $update_data["city"]            = $stringFilter->filter($update_data["city"]);
		    $update_data["sexe"]            = $stringFilter->filter($update_data["sexe"]);
		    $update_data["activated"]       = (isset($update_data["activated"]))   ? intval($update_data["activated"]) : 0;
		    $update_data["blocked"]         = (isset($update_data["blocked"]))     ? intval($update_data["blocked"])   : 0;
		    $update_data["locked"]          = (isset($update_data["locked"]))      ? intval($update_data["locked"])    : 0;	   
		    $viewData                       = $update_data;	    		  
		   
		    if(empty($errorMessages)){
		   	  $editedUser->clearMessages();
		   	  if(!$editedUser->save($update_data)){
		   	      $saveErrors  = $editedUser->getMessages();
					foreach( $saveErrors as $type => $msg){
						     $msg             = (is_array($msg)) ? array_shift($msg)  : $msg;
						     $errorMessages[] = $msg;
					}
				}
		    }		   
		    if(count($errorMessages)){
		   	   foreach( $errorMessages as $errorMessage){
		   	  	        $this->getHelper("Message")->addMessage($errorMessage,"error");
		   	  }
		   	  $this->_helper->viewRenderer->setNoRender(true);
		   	  $this->_helper->layout->disableLayout(true);
		   	  echo ZendX_JQuery::encodeJson(array("error"  => implode(" ",$errorMessages)));
		   	  exit;
		    }  else {
		   	
		   	if(empty($errorMessages)){
				$agenceid   = (isset($postData["agenceid"] )) ? intval($postData["agenceid"]) : 0;
			    if( $agenceid ) {
					$dbAdapter->delete( $prefixName . "gestoptic_projet_agences_users", array("userid=".$edituserid));
				    $agenceUser = array("userid"   => $edituserid, "periodstart"=> 0, "periodend"    =>0, "creationdate"=> time(),
							     	    "creatorid"=> $me->userid,"updatedate"  => 0, "updateduserid"=>0, "agenceid"    => $agenceid);
				    $dbAdapter->insert( $prefixName . "gestoptic_projet_agences_users", $agenceUser );
			    }	
		   		if($this->_request->isXmlHttpRequest()){
		   			$this->_helper->viewRenderer->setNoRender(true);
		   			$this->_helper->layout->disableLayout(true);
		   			echo ZendX_JQuery::encodeJson(array("success"  => sprintf("La mise à jour des informations du compte de  %s, s'est effectuée avec succès",$editedUser->lastname." ".$editedUser->firstname)));
		   			exit;
		   		}
		   		$this->setRedirect(sprintf("La mise à jour des informations du compte de  %s, s'est effectuée avec succès",$editedUser->lastname." ".$editedUser->firstname),"success");
		   		$this->redirect("projectusers/list");
		    } else {
		    	$viewData  = $postData;
		    	if($this->_request->isXmlHttpRequest()){
		    		$this->_helper->viewRenderer->setNoRender(true);
		    		$this->_helper->layout->disableLayout(true);
		    		echo ZendX_JQuery::encodeJson(array("error"  => implode(" ", $errorMessages)));
		    		exit;
		    	}
		   		$this->setRedirect(implode(" ",$errorMessages),"message");
		   		$this->redirect("projectusers/list");
		   	}		   	   
		  }					   
		}
		//On crée un jeton de connexion
	   if(!isset($defaultSession->token)){
			$defaultSession->token = Sirah_User_Helper::getToken(15).time();
			$defaultSession->setExpirationSeconds(86400);
		}	
		$this->view->agences  = $modelAgence->getSelectListe("Selectionenz une boutique", array("agenceid","libelle"), array() , null , null , false );	
		$this->view->data     = $viewData;
		$this->view->title    = sprintf("Mise à jour du compte de %s %s " , $editedUser->lastname ,$editedUser->firstname);
		$this->view->token    = $defaultSession->token;	
	}	
 		
		
	public function infosAction()
	{
		$userid            = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$user              = Sirah_Fabric::getUser($userid);
		$userTable         = $user->getTable();		
		if(!$user->userid){
			$this->setRedirect("Impossible d'avoir les informations de l'utilisateur, les paramétres fournis sont invalides","error");
			$this->redirect("projectusers/list");
		}		
		$userData           = $userTable->getData();
		$userRoles          = Sirah_User_Acl_Table::getRoles($userid);
		$userRights         = Sirah_User_Acl_Table::getUserRights($userid);
		
		$this->view->title  = sprintf("Les informations du compte de %s %s", $user->lastname,$user->firstname);
		$this->view->user   = $user;
		$this->view->roles  = $userRoles;
		$this->view->rights = $userRights;
		$this->view->params = $user->getParams();		
	}
	
	public function deleteAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		$me            = Sirah_Fabric::getUser();
		
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}		
		if(empty($userids)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("projectusers/list");
		} 		
		foreach($userids as $userid){
			$user      = Sirah_Fabric::getUser(intval($userid));
			$userTable = $user->getTable();			
			if(!$user->userid){
				continue;
			}
			if(!$user->isCommerciaux()) {
				$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué, ce dernier n'est pas un commercial.",$user->lastname,$user->firstname);
				continue;
			}
			if($userTable->connected){
				$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
				continue;
			}
			if($me->userid == intval($userid)){
				$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
				continue;
			}
			if(!$userTable->delete()){
				$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué",$user->lastname,$user->firstname);
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
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les comptes selectionnés ont été supprimés avec succès" ));
				exit;
			}
			$this->setRedirect("Les comptes selectionnés ont été supprimés avec succès","success");
		}
		$this->redirect("projectusers/list");	
	}
	
	
	 
}