<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Le controlleur d'actions sur les comptes des utilisateurs
 * 
 * de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */

defined("USER_AVATAR_PATH")
   || define("USER_AVATAR_PATH" , APPLICATION_DATA_USER_PATH . DS . "avatar/");


class Admin_AccountsController extends Sirah_Controller_Default
{
    		
	/**
	 * L'action qui permet d'avoir la liste des comptes
	 * 
	 * des utilisateurs de l'application
	 *
	 *
	 */
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
	   $pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
	   $pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
	   
	   $defaultName        = (isset($params["searchQ"]) && !empty($params["searchQ"])) ? $stringFilter->filter($params["searchQ"]) : null;
	   
	   $filters            = array("lastname"=>null,"firstname"=>null,"username"=>null,"email"=> null,"name"=>$defaultName,
	   		                       "phone"=>null,"activated"=>1,"locked"=>0,"blocked"=>0,"expired"=> 0,"roleid"=>0,"rolename"=>null,"sexe"=>null,"admin"=>null,"country"=>null,"language"=>null);
	   
	   if(!empty(  $params)) {
	   	  foreach( $params as $filterKey => $filterValue){
	   	  	       $filters[$filterKey]  =  $stringFilter->filter($filterValue);
	   	  }
	   }
	   if( isset($filters["name"] )) {
			$nameToArray               = preg_split("/[\s]+/", $filters["name"]);
			if(count($nameToArray) > 2) {
				$filters["lastname"]   = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["firstname"]  = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray) == 2)	 {
				$filters["lastname"]   = (isset($nameToArray[0])) ? $nameToArray[0] : "" ;
				$filters["firstname"]  = (isset($nameToArray[1])) ? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( (count($nameToArray) == 1) && (false!==stripos($nameToArray[0], "Ml-") || is_numeric($nameToArray[0]))) {
				$filters["username"]   =  $filters["matricule"] = $nameToArray[0];
				unset($filters["name"]);
			}  				
	   }
	   $users                 = Sirah_User_Table::getUsers($filters , $pageNum , $pageSize);
       $usersListePaginator   = Sirah_User_Table::getUsersPaginator($filters);      	      
       if(null !== $usersListePaginator) {
       	  $usersListePaginator->setCurrentPageNumber($pageNum);
       	  $usersListePaginator->setItemCountPerPage($pageSize);
       }
	   $this->view->columns   = array("left");
	   $this->view->title     = "Liste des comptes des utilisateurs de la plateforme";
	   $this->view->searchQ   = $defaultName;
	   $this->view->roles     = Sirah_User_Acl_Table::getAllRoles();
	   $this->view->users     = $users;
	   $this->view->filters   = $filters;
	   $this->view->params    = $params;
	   $this->view->pageNum   = $pageNum;
	   $this->view->pageSize  = $this->view->maxitems = $pageSize;
	   $this->view->paginator = $usersListePaginator;
	}
	
	/**
	 * L'action qui permet de créer un nouveau compte
	 * utilisateur.
	 *
	 *
	 */
	public function createAction()
	{
		//On initialise les variables nécessaires
		$guestUser             = Sirah_Fabric::getUser(0);
		$me                    = Sirah_Fabric::getUser();
        $errorMessages         = array();
        $viewData              = array();
        $urlDone               = $this->_getParam("done","accounts/list");
		
		$modelLocalite         = $this->getModel("localite");
        $civilites             = array("Mr"=>"Monsieur","Mme"=>"Madame","Mle"=> "Mademoiselle", "Excellence"=>"Son excellence","Maître"=>"Maître","Dr" => "Docteur","Pr" => "Professeur","Conseiller"=>"Le Conseiller");
        //S'il n'est pas connecté, on ne lui autorise pas l'accès à cette opération
        if(!$me->isLoggedIn()){
        	$this->setRedirect("Vous n'etes pas autorisé à effectuer cette opération","error");
        	$this->redirect("admin/account/login");
        }
        $defaultData           = array("firstname"          => ""  , "lastname"   => "","phone1" => "", "phone2"    => "",
				                       "address"            => ""  , "zipaddress" => "","city"   => "", "country"   => "",
				                       "language"           => "FR", "facebookid" => "","skypeid"=> "", "username"  => "",
				                       "password"           => ""  , "email"      => "","sexe"   => null, "expired" => 0,
				                       "activated"          => 1   , "blocked"    => 0 ,"locked" => 0, "accesstoken"=> "",
				                       "logintoken"         => ""  , "params"     => "",
									   "localiteid"         => 0   , "civilite"   => "Mr",   
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
		if($this->_request->isPost()){
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ){
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$urlDone          = Sirah_Functions::url($this->view->url(array("controller" => "accounts", "action"  => "create")),"t=".$defaultSession->token,81 , APPLICATION_HOST);
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
		   if( empty( $urlDone ) ) {
		   	   $urlDone         = "accounts/list";
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
		   $passwordValidator->setMinlength(5);
		   
		   if(!$strNotEmptyValidator->isValid($insert_data["firstname"]) || $insert_data["firstname"]=="anonyme"){
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
				$errorMessages[]         = " L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}			 
		   if(!$passwordValidator->isValid($insert_data["password"])){
				$errorMessages[]         = implode(" et  " , $passwordValidator->getMessages())   ;
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
		   $insert_data["localiteid"]     = (isset($postData["localiteid"]))?intval($postData["localiteid"]) : 0;
		   $insert_data["civilite"]       = (isset($postData["civilite"]) && isset($civilites[$postData["civilite"]]))?$stringFilter->filter($postData["civilite"]) : "Monsieur";
		   $insert_data["sexe"]           = $stringFilter->filter($insert_data["sexe"]);
		   $insert_data["lastIpAddress"]  = Sirah_Functions::getIpAddress();
		   $insert_data["lastHttpClient"] = Sirah_Functions::getBrowser();
		   $insert_data["lastSessionId"]  = "";
		   $insert_data["activated"]      = (isset($insert_data["activated"]))   ? intval($insert_data["activated"]) : 0;
		   $insert_data["blocked"]        = (isset($insert_data["blocked"]))     ? intval($insert_data["blocked"]) : 0;
		   $insert_data["locked"]         = (isset($insert_data["locked"]))      ? intval($insert_data["locked"]) : 0;
		   $sendmail                      = (isset($postData["sendmail"]))       ? intval($postData["sendmail"]) : 1;
		   $sendparams                    = (isset($postData["sendparams"]))     ? intval($postData["sendparams"]) : 1;		   
		   $defaultData                   = $insert_data;		   
		   if( empty($errorMessages)){
				$guestUser->clearMessages();
				if(!$newuserid   = $guestUser->save($insert_data)){
					$saveErrors  = $guestUser->getMessages("error");
					foreach($saveErrors as $type => $msg){
						$msg     = (is_array($msg)) ? array_shift($msg)  : $msg;
						$errorMessages[]  = $msg;
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
		   	  //On lui assigne le role par defaut des utilisateurs
		   	  Sirah_User_Acl_Table::assignRoleToUser( $newuserid , APPLICATION_DEFAULT_USERS_ROLENAME);
		   	  $roleid                  = ( isset( $postData["roleid"] )) ? intval( $postData["roleid"] ) : null;
		   	  if( ( null != $roleid ) && ( $rolename = Sirah_User_Acl_Table::getRolename($roleid) ) ) {
		   	  	    Sirah_User_Acl_Table::assignRoleToUser( $newuserid , $rolename );
		   	  }
		   	  //On tente de lui envoyer un email si c'est nécessaire
		   	  if($sendmail){
		   	  	 $config               = Sirah_Fabric::getConfig();
		   	  	 $mailer               = Sirah_Fabric::getMailer();
		   	  	 $newUser              = Sirah_Fabric::getUser($newuserid);
		   	  	 $activateAccountToken = Sirah_User_Helper::getToken(10) . time() . $newuserid;
		   	  	 $defaultToEmail       = $config["resources"]["mail"]["defaultFrom"]["email"];
		   	  	 $defaultToName        = $config["resources"]["mail"]["defaultFrom"]["name"];
		   	  	 $enterpriseName       = $config["system"]["application"]["enterprise"]["name"];
		   	  	 $applicationName      = $config["system"]["application"]["name"];
		   	  	 $currenDate           = Zend_Date::now();		   	  	
		   	  		   	  	
		   	  	 $emailMsg      = sprintf(" Bonjour Mr/Mrs %s %s \n <br/>
		   	  	 		                    Un compte utilisateur a été créé à votre nom, sur la plateforme %s de votre entreprise  %s
		   	  	 		                    par %s %s le %s. <br/> ",$newUser->lastname , $newUser->firstname , $applicationName ,$enterpriseName,
		   	  	 		                    $me->lastname , $me->firstname , $currenDate->toString("d/M/Y à H:m:s"));		   	  	 		                   
		   	  	 if($sendparams){
		   	  	 	$linkResetPwd = Sirah_Functions::url($this->view->url(array("controller" => "recoverpwd", "action"  => "init")),"uid=".$newuserid , 81 , APPLICATION_HOST);
		   	  	 		
		   	  	 	$emailMsg  .=  sprintf(" Les paramètres d'accès à votre compte sont les suivants : <br/>
		   	  	 			                   Nom d'utilisateur : %s <br/>
		   	  	 			                   Mot de passe      : <a href='".$linkResetPwd."'> Veuillez cliquer sur ce lien pour réinitialiser </a>." , $newUser->username);		   	  	 	 
		   	  	 } else {
		   	  	 	$emailMsg  .= " Veuillez prendre attache avec l'administrateur afin d'obtenir les paramètres d'accès à votre compte. <br/> " ;
		   	  	 }		   	  	 
		   	  	 if($newUser->locked){
		   	  	 	$emailMsg  .= " Le compte semble verrouillé, veuillez vérifier cela avec l'administrateur ou le responsable de la création  <br/> ";
		   	  	 }	   	  	 
		   	  	 if($newUser->blocked){
		   	  	 	$emailMsg  .= " Le compte semble bloqué, veuillez vérifier cela avec l'administrateur ou le responsable de la création  <br/> ";
		   	  	 }		   	  	 
		   	  	if (!$newUser->activated){
		   	  	 	 $emailMsg .= " Le compte semble desactivé, veuillez vérifier cela avec l'administrateur ou le responsable de la création <br/> ";
		   	  	 }		   	  	 		   	  			   	  	
		   	  	$mailer->setFrom($defaultToEmail,stripslashes($defaultToName));
		   	  	$mailer->setSubject("Votre compte d'accès à la plateforme ".$applicationName);
		   	  	$mailer->addTo($newUser->email,stripslashes($newUser->lastname. " ".$newUser->firstname));
		   	  	$mailer->setBodyHtml($emailMsg);		   	  	
		   	  	try {
		   	  		$mailer->send();
		   	  	} catch(Exception $e) {
		   	  		$errorMessages[]  = " Le compte est créé mais nous avons tenté de transmettre un email au nouvel utilisateur sans succès, le compte de messagerie semble inaccessible, vous pouvez l'informer de la création de son compte.";
		   	  	}		   	  	
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
		   	  	 $this->setRedirect(sprintf("La création du compte de l'utilisateur %s, s'est effectué avec succès",$newUser->lastname." ".$newUser->firstname),"success");
		   	  	 $this->redirect("admin/accounts/list");		   	  	
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
		$this->view->title         = " Creation du compte d'un utilisateur sur la plateforme ";
		$this->view->data          = $defaultData;
		$this->view->token         = $defaultSession->token;
		$this->view->roles         = Sirah_User_Acl_Table::getAllRoles();
		$this->view->localites     = $modelLocalite->getSelectListe("Sélectionnez une juridiction", array("localiteid", "libelle") , array() , null , null , false );
	}	
	
	/**
	 * L'action qui permet de faire ressortir les informations du compte
	 * 
	 * d'un utilisateur.
	 *
	 *
	 */
	public function infosAction()
	{
		$userid            = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$user              = Sirah_Fabric::getUser($userid);
		$userTable         = $user->getTable();		
		if(!$user->userid){
			$this->setRedirect("Impossible d'avoir les informations de l'utilisateur, les paramétres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}		
		$userData           = $userTable->getData();
		$userRoles          = Sirah_User_Acl_Table::getRoles($userid);
		$userRights         = Sirah_User_Acl_Table::getUserRights($userid);
		
		$this->view->title  = sprintf("Les informations du compte de %s %s",$user->lastname,$user->firstname);
		$this->view->user   = $user;
		$this->view->roles  = $userRoles;
		$this->view->rights = $userRights;
		$this->view->params = $user->getParams();		
	}	
	
	/**
	 * L'action qui permet de changer l'avatar du compte d'un utilisateur.
	 *
	 *
	 */
	public function changeavatarAction()
	{
		$userid            = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$user              = Sirah_Fabric::getUser($userid);
		$errorMessages     = array();
		
		if(!$user->userid){
			$this->setRedirect("Impossible d'effectuer des changements sur l'avatar de l'utilisateur, les paramètres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}		
		if($this->_request->isPost()){			
			$avatarUpload = new Zend_File_Transfer();			
			//On inclut les différents validateurs de l'avatar
			$avatarUpload->addValidator('Count',false,1);
			$avatarUpload->addValidator("Extension",false,array("png" , "jpg" , "jpeg" , "gif" , "bmp"));
			$avatarUpload->addValidator("FilesSize",false,array("max"       => "3MB"));
			$avatarUpload->addValidator("ImageSize",false,array("minwidth"  => 10,
					                                            "maxwidth"  => 800,
					                                            "minheight" => 10,
					                                            "maxheight" => 600));			
			$avatarExtension = Sirah_Filesystem::getFilextension($avatarUpload->getFileName('avatar'));
			//On inclut les différents filtres de l'avatar
			$avatarUpload->addFilter("Rename",array("target" => USER_AVATAR_PATH.DS.$user->logintoken."Avatar.".$avatarExtension,"overwrite" => true),"avatar");
						
			//On upload l'avatar de l'utilisateur
			if($avatarUpload->isUploaded("avatar")){
			   $avatarUpload->receive("avatar");
			} else {
				$errorMessages[]  = "L'avatar fourni n'est pas valide";
			}
			
			if( $avatarUpload->isReceived("avatar")){				
				$newAvatarFilename  = USER_AVATAR_PATH . DS . $user->logintoken ."Avatar.".$avatarExtension;

				//on supprime l'avatar existant de l'utilisateur
				if((null!==$user->avatar) && !empty($user->avatar) && Sirah_Filesystem::exists($user->avatar) && ($user->avatar != $newAvatarFilename)){
					Sirah_Filesystem::remove($user->avatar);
				}				
				//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
				$avatarImage  = Sirah_Filesystem_File::fabric("Image",$newAvatarFilename,"ab");								
				$thumbAvatar  = $avatarImage->resize("180px");				
				$thumbAvatar->copy(USER_AVATAR_PATH . DS . "thumbnails",true);
				
				$user->save(array( "avatar"  => $newAvatarFilename));				
			} else {
				$errorMessages   = $avatarUpload->getMessages();
				array_unshift($errorMessages,"L'avatar fourni n'est pas valide pour les raisons suivantes : ");
			}			
			if(!empty($errorMessages)){
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}  else {
				$this->setRedirect(sprintf("L'avatar du profil de %s %s a été mis à jour avec succès",$user->lastname,$user->firstname),"success");
				$this->redirect("admin/accounts/list");
			}
		}		
		$this->view->title   = sprintf("Mise à jour de l'avatar de %s %s",$user->lastname,$user->firstname);
		$this->view->userid  = $user->userid;
		$this->view->avatar  = $user->avatar;		
	}
	
	/**
	 * L'action qui permet de mettre à jour
	 * 
	 * les données du compte de l'utilisateur
	 *
	 *
	 */
	public function editAction()
	{
		$edituserid            = $userid = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$userData              = array();
		$errorMessages         = array();
		$me                    = Sirah_Fabric::getUser();
		$editedUser            = Sirah_Fabric::getUser($edituserid);
		$modelLocalite         = $this->getModel("localite");
		
		$userTable             = $editedUser->getTable();
		$dbAdapter             = $userTable->getAdapter();
		$prefixName            = $userTable->info("namePrefix");	
		$userData              = $viewData = $userTable->getData();
		$currentPhone          = (isset($viewData["phone1"])   && !empty($viewData["phone1"]  ))?$viewData["phone1"]   : $editedUser->phone1;
		$currentEmail          = (isset($viewData["email"])    && !empty($viewData["email"]   ))?$viewData["email"]    : $editedUser->email;
		$currentUsername       = (isset($viewData["username"]) && !empty($viewData["username"]))?$viewData["username"] : $editedUser->username;
		$civilites             = array("Mr"=>"Monsieur","Mme"=>"Madame","Mle"=> "Mademoiselle", "Excellence"=>"Son excellence","Maître"=>"Maître","Dr" => "Docteur","Pr" => "Professeur","Conseiller"=>"Le Conseiller");
		
		if(!$edituserid || !$userTable->userid){
			$this->setRedirect("Impossible d'effectuer cette opération, car vous avez fourni des paramétres invalides","error");
			$this->redirect("admin/accounts/list");
		}		
        //print_r($userData); die();		
		$defaultSession         = new Zend_Session_Namespace("accountsedit");
		if(!$defaultSession->initialised){
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}		
		if( $this->_request->isPost()){			
		//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ){
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$urlDone          = Sirah_Functions::url($this->view->url(array("module"=>"admin","controller"=>"accounts","action"=>"edit")),"t=".$defaultSession->token,81 , APPLICATION_HOST);
				$urlSecurityCheck = $this->view->url(array("module"=>"admin","controller"=>"securitycheck","action"=>"captcha","done"=> $urlDone));
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide","reload"=> true, "reloadurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide","error");
				$this->redirect($urlSecurityCheck);
			}
			$postData           = $this->_request->getPost();
			$update_data        = array_merge( $userData , $postData );
			 
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       =    new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator           = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$usernameValidator              = new Sirah_Validateur_Username();
			$emailValidator                 = new Sirah_Validateur_Email();
			$passwordValidator              = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(5);
			
			$update_data["username"]        = $stringFilter->filter($update_data["username"]);
			$update_data["email"]           = $stringFilter->filter($update_data["email"]);
			$postData["password"]           = $update_data["password"]  = (isset($postData["password"]))? $postData["password"] : "";
			
			$update_data["phone1"]          = intval( $update_data["phone1"] );
			$update_data["phone2"]          = intval( $update_data["phone2"] );
			if(!$update_data["phone1"]){
				$errorMessages[]            = " Veuillez indiquer un numéro de téléphone valide ";
			} elseif( $userTable->find(array("phone1" => $update_data["phone1"])) && ($userTable->phone1 != $update_data["phone1"])) {
				$errorMessages[]            = " Le numéro de téléphone n'est pas valide, car il est associé à un compte existant ";
			}
			 
			if(!$strNotEmptyValidator->isValid($update_data["firstname"]) || $update_data["firstname"]=="anonyme"){
				$errorMessages[]            = " Le prénom que vous avez saisi est invalide ";
			} else {
				$update_data["firstname"]   = $stringFilter->filter($update_data["firstname"]);
			}
			 
			if(!$strNotEmptyValidator->isValid($update_data["lastname"]) || $update_data["lastname"]=="anonyme"){
				$errorMessages[]            = " Le nom que vous avez saisi est invalide ";
			} else {
				$update_data["lastname"]    = $stringFilter->filter($update_data["lastname"]);
			}
			 
		    if(!$userTable->checkUsername($update_data["username"]) && ($currentUsername != $update_data["username"])){
				$errorMessages[]            = " Le nom d'utilisateur ".$update_data["username"]." n'est pas valide ou peut etre associé à un autre compte ";
		    }			
		    if(!$userTable->checkEmail($update_data["email"])       && ($currentEmail    != $update_data["email"])){
				$errorMessages[]            = " L'adresse email ".$update_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}			
			if( $strNotEmptyValidator->isValid( $update_data["password"])){
				if(!$passwordValidator->isValid($update_data["password"])){
					$errorMessages[]        = implode(" ,  " , $passwordValidator->getMessages())   ;
				} else {
					$update_data["password"]= $postData["password"];			
					if(!isset($postData["confirmedpassword"])){
						$errorMessages[]    = "Des données de création de compte sont manquantes";
					} elseif($postData["confirmedpassword"] !== $update_data["password"]) {
						$errorMessages[]    = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
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
			$update_data["civilite"]        = (isset($postData["civilite"]) && isset($civilites[$postData["civilite"]]))?$stringFilter->filter($postData["civilite"]) : "Monsieur";
			$update_data["localiteid"]      = (isset($update_data["localiteid"]))  ? intval($update_data["localiteid"]) : $userData["localiteid"];
		    $update_data["activated"]       = (isset($update_data["activated"]))   ? intval($update_data["activated"])  : $userData["activated"];
		    $update_data["blocked"]         = (isset($update_data["blocked"]))     ? intval($update_data["blocked"])    : $userData["blocked"];
		    $update_data["locked"]          = (isset($update_data["locked"]))      ? intval($update_data["locked"])     : $userData["locked"];
		    $sendmail                       = (isset($postData["sendmail"]))       ? intval($postData["sendmail"])      : 1;
		    $sendparams                     = (isset($postData["sendparams"]))     ? intval($postData["sendparams"])    : 1;		   
		    $viewData                       = $update_data;	    		  
		   
		    if( empty($errorMessages)){
		   	    $editedUser->clearMessages();
		   	    if(!$editedUser->save($update_data)){
		   	        $saveErrors             = $editedUser->getMessages();
					foreach($saveErrors as $type => $msg){
						$msg                = (is_array($msg)) ? array_shift($msg)  : $msg;
						$errorMessages[]    = $msg;
					}
				}
		    }		   
		    if(!empty($errorMessages)){
		   	    foreach( $errorMessages as $errorMessage){
		   	  	         $this->getHelper("Message")->addMessage($errorMessage,"error");
		   	    }
		   	    $this->_helper->viewRenderer->setNoRender(true);
		   	    $this->_helper->layout->disableLayout(true);
		   	    echo ZendX_JQuery::encodeJson(array("error"  => implode(" ",$errorMessages)));
		   	    exit;
		    }  else {
				if(($currentEmail != $update_data["email"]) || ($currentUsername != $update_data["username"]) || ($currentPhone!=$update_data["phone1"])) {
					$dbAdapter->update($prefixName."rccm_members"                    , array("code" =>$update_data["email"],"email"=>$update_data["email"],"username"=>$update_data["username"],"tel1"=>$update_data["phone1"],"tel2"=>$update_data["phone2"]), array("accountid=?"=>$userid));
				    $dbAdapter->update($prefixName."system_users_profile_coordonnees", array("email"=>$update_data["email"],"tel_mob"=>$update_data["phone1"]), array("profileid IN (SELECT P2.profileid FROM system_users_profile P2 WHERE P2.userid=?)"=>$userid));
				}
				//On stocke la signature
				$signatureImgPath         = USER_AVATAR_PATH . DS . $user->username ."Avatar.".$avatarExtension;
				$originalFilename         = "";
				$signatureUpload          = new Zend_File_Transfer();
				$signatureUpload->addValidator('Count'    , false, 2 );
				$signatureUpload->addValidator("Extension", false, array("png","jpg","jpeg","gif","bmp","PNG","JPEG","JPG"));
				$signatureUpload->addValidator("FilesSize", false, array("max"=>"15MB"));
				if( $signatureUpload->isUploaded("signature") ) {
					$signatureExtension   = Sirah_Filesystem::getFilextension( $signatureUpload->getFileName('signature') );
					$originalFilename     = $signatureImgPath. DS ."original". DS . $project->numproject ."Img.".$signatureExtension;
					$signatureUpload->addFilter("Rename", array("target"=> $originalFilename,"overwrite"=> true) , "signature");
					$signatureUpload->receive("signature") ;
				}
				if( $signatureUpload->isReceived("signature") ) {
					//On redimensionne  la signature en faisant des copies dans le  dossier thumb
					$signatureImage            = Sirah_Filesystem_File::fabric("Image" , $originalFilename , "rb+");
					$signatureImage->resize("90"  , null , true , $signatureImgPath . DS . "thumbs" );
					$dbAdapter->update($prefixName."system_users_profile",array("signature"=>$originalFilename),array("userid=?"=>$userid)); 
				}
				if( $sendmail){
					$config               = Sirah_Fabric::getConfig();
					$mailer               = Sirah_Fabric::getMailer();
					$activateAccountToken = Sirah_User_Helper::getToken(10) . time() . $editedUser->userid;
					$defaultToEmail       = $config["resources"]["mail"]["defaultFrom"]["email"];
					$defaultToName        = $config["resources"]["mail"]["defaultFrom"]["name"];
					$enterpriseName       = $config["system"]["application"]["enterprise"]["name"];
					$applicationName      = $config["system"]["application"]["name"];
					$updatedDate          = new Zend_Date(time(),Zend_Date::TIMESTAMP);
						
					$emailMsg             = sprintf(" Bonjour Mr/Mrs %s %s \n <br/>
													  Votre compte utilisateur sur la plateforme %s de votre entreprise  %s a été mis à jour le %s
													  par %s %s. Veuillez prendre contact avec lui, ou lui envoyer un email à l'adresse %s, pour en savoir plus. <br/> ",
													  $editedUser->lastname , $editedUser->firstname , $applicationName , $enterpriseName, 
													  $updatedDate->toString("d/M/Y à H:m:s"), $me->lastname , $me->firstname, $me->email);
					if( $editedUser->locked){
						$emailMsg        .= " Le compte semble verrouillé, veuillez vérifier cela avec l'auteur des modifications.  <br/> ";
					}
					if( $editedUser->blocked){
						$emailMsg        .= " Le compte semble bloqué, veuillez vérifier cela avec l'auteur des modifications. <br/> ";
					}
					if (!$editedUser->activated){
						$emailMsg        .= " Le compte semble desactivé, veuillez vérifier cela avec l'auteur des modifications. <br/> ";
					}
					
					$emailMsg            .= sprintf(" <i> <b> Cordialement <br/> %s </b> </i>" , stripslashes($defaultToName));
					 
					$mailer->setFrom($defaultToEmail , stripslashes($defaultToName));
					$mailer->setSubject("Votre compte d'accès à la plateforme ".$applicationName);
					$mailer->addTo($editedUser->email,stripslashes($editedUser->lastname. " ".$editedUser->firstname));
					$mailer->setBodyHtml($emailMsg);		   		 
					try {
						$mailer->send();
					} catch(Exception $e) {
						$errorMessages[]  = " L'opération s'est effectuée mais nous n'avons pas réussi à transmettre un email à l'utilisateur concerné, le compte de messagerie semble inaccessible, vous pouvez lui informer manuellement de la modfication de son compte.";
					}
				}
				if( empty($errorMessages)){
					if( $this->_request->isXmlHttpRequest()){
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"  => sprintf("La mise à jour des informations du compte de  %s, s'est effectuée avec succès",$editedUser->lastname." ".$editedUser->firstname)));
						exit;
					}
					$this->setRedirect(sprintf("La mise à jour des informations du compte de  %s, s'est effectuée avec succès",$editedUser->lastname." ".$editedUser->firstname),"success");
					$this->redirect("admin/accounts/list");
				} else {
					$viewData  = $postData;
					if($this->_request->isXmlHttpRequest()){
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => implode(" ", $errorMessages)));
						exit;
					}
					$this->setRedirect(implode(" ",$errorMessages),"message");
					$this->redirect("admin/accounts/list");
				}		   	   
		    }					   
		}
		//On crée un jeton de connexion
	   if(!isset($defaultSession->token)){
			$defaultSession->token = Sirah_User_Helper::getToken(15).time();
			$defaultSession->setExpirationSeconds(86400);
		}	
			
		$this->view->data              = $viewData;
		$this->view->title             = sprintf("Mise à jour du compte de %s %s " , $editedUser->lastname ,$editedUser->firstname);
		$this->view->token             = $defaultSession->token;	
		$this->view->localites         = $modelLocalite->getSelectListe("Sélectionnez une juridiction", array("localiteid", "libelle") , array() , null , null , false );
	}
	
	/**
	 * L'action qui permet de supprimer un compte
	 * utilisateur.
	 *
	 *
	 */
	public function deleteAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		$me            = Sirah_Fabric::getUser();
		$modelTable    = $me->getTable();
		$dbAdapter     = $modelTable->getAdapter();
		$prefixName    = $modelTable->info("namePrefix");
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}		
		if( empty($userids)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/accounts/list");
		} 		
		foreach($userids as $userid){
				$user      = Sirah_Fabric::getUser(intval($userid));
				$userTable = $user->getTable();
                $userEmail = $user->email;				
				if(!$user->userid){
					continue;
				}
				if( $userTable->connected){
					$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if($me->userid == intval($userid)){
					$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if(!$userTable->delete()){
					$errorMessages[] = sprintf("La suppression du compte de %s %s a echoué",$user->lastname,$user->firstname);
				} else {
					$dbAdapter->delete($prefixName."rccm_members",array("accountid=?"=>$userid));
					$dbAdapter->delete($prefixName."system_users_profile",array("userid=?"=>$userid));
					$dbAdapter->delete($prefixName."system_users_profile_coordonnees",array("profileid IN (SELECT P2.profileid FROM system_users_profile P2 WHERE P2.userid=?)"=>$userid));
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
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les comptes ont été supprimés avec succès" ));
				exit;
			}
			$this->setRedirect("Les comptes ont été supprimés avec succès","success");
		}
		$this->redirect("admin/accounts/list");	
	}
	
	
	/**
	 * L'action qui permet de déconnecter un/des utilisateurs
	 *
	 *
	 */
	public function disconnectAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
	
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}	
		if( empty($userids)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}
	
		foreach( $userids as $userid ) {
			     $user      = Sirah_Fabric::getUser(intval($userid));
			     if(!$user->userid){
				     continue;
			     }			
			     if(!$user->disconnect()){
				     $errorMessages[] = sprintf("La déconnexion de %s %s a echoué",$user->lastname,$user->firstname);
			     }			
		}
		if(!empty($errorMessages)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
				     $this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" =>  "Les utilisateurs ont été déconnectés de leur session avec succès" ));
				exit;
			}
			$this->_helper->Message->addMessage("Les utilisateurs ont été déconnectés de leur session avec succès","success");
		}
		$this->redirect("admin/accounts/list");
	}
	
	
	/**
	 * L'action qui permet de bloquer un compte.
	 *
	 *
	 */
	public function blockAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		$me            = Sirah_Fabric::getUser();
	
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}	
		if( empty($userids)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}	
		foreach( $userids as $userid ){
				$user      = Sirah_Fabric::getUser(intval($userid));
				$userTable = $user->getTable();
				if(!$user->userid){
					continue;
				}
				if($userTable->connected){
					$errorMessages[] = sprintf("Le blocage du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if($me->userid == intval($userid)){
					$errorMessages[] = sprintf("Le blocage du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if(!$userTable->delete()){
					$errorMessages[] = sprintf("Le blocage du compte de %s %s a echoué",$user->lastname,$user->firstname);
				}
				if(!$user->block()){
					$errorMessages[] = sprintf("Le blocage du compte de %s %s a echoue",$user->lastname,$user->firstname);
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
				$this->getHelper("Message")->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" =>"Les comptes ont été bloqués avec succès"));
				exit;
			}
			$this->getHelper("Message")->addMessage("Les comptes ont été bloqués avec succès","success");
		}
		$this->redirect("admin/accounts/list");
	}
	
	/**
	 * L'action qui permet de débloquer un compte.
	 *
	 *
	 */
	public function disblockAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
	
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}	
		if(empty($userids)){
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}
		foreach($userids as $userid){
			$user  = Sirah_Fabric::getUser(intval($userid));
			if(!$user->userid){
				continue;
			}
			if(!$user->disBlock()){
				$errorMessages[] = sprintf("Le déblocage du compte de %s %s a echoue",$user->lastname,$user->firstname);
			}
		}
		if(!empty($errorMessages)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			$this->_helper->Message->addMessage("Les comptes ont été débloqués avec succès","success");
		}
		$this->redirect("admin/accounts/list");
	}
	
	
	/**
	 * L'action qui permet de verrouiller un compte
	 * utilisateur.
	 *
	 *
	 */
	public function lockAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		$me            = Sirah_Fabric::getUser();
		
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}		
		if( empty($userids)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/accounts/list");
		} 		
		foreach($userids as $userid){
				$user      = Sirah_Fabric::getUser(intval($userid));			
				$userTable = $user->getTable();
				if(!$user->userid){
					continue;
				}
				if($userTable->connected){
					$errorMessages[] = sprintf("Le verrouillage du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if($me->userid == intval($userid)){
					$errorMessages[] = sprintf("Le verrouillage du compte de %s %s a echoué, l'utilisateur est présentement connecté",$user->lastname,$user->firstname);
					continue;
				}
				if(!$user->lock()){
					$errorMessages[] = sprintf("Le verouillage du compte de %s %s a echoué",$user->lastname,$user->firstname);
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
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "Les comptes ont été verrouillés avec succès"));
				exit;
			}
			    $this->_helper->Message->addMessage("Les comptes ont été verouillés avec succès","success");
		}
		$this->redirect("admin/accounts/list");	
	}
	
	
	/**
	 * L'action qui permet de deverrouiller un compte
	 * utilisateur.
	 *
	 *
	 */
	public function unlockAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}		
		if( empty($userids)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/accounts/list");
		} 		
		foreach( $userids as $userid ){
				$user  = Sirah_Fabric::getUser(intval($userid));			
				if(!$user->userid){
					continue;
				}
				if(!$user->unLock()){
					$errorMessages[] = sprintf("Le dévérouillage du compte de %s %s a echoué",$user->lastname,$user->firstname);
				}			
		}
		if(!empty($errorMessages)){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->_helper->Message->addMessage($errorMessage,"error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "Les comptes ont été déverrouillés avec succès"));
				exit;
			}
			    $this->_helper->Message->addMessage("Les comptes ont été déverouillés avec succès","success");
		}
		$this->redirect("admin/accounts/list");
	}
	
	public function exportAction()
	{
		$this->_helper->layout->disableLayout( true );
		$this->_helper->viewRenderer->setNoRender( true );
		$userids       = $this->_getParam("userids" , $this->_getParam("ids" , array()));
		$errorMessages = array();
		$rows          = array();
		$me            = Sirah_Fabric::getUser();
		
		if(is_string( $userids ) && !empty( $userids ) ){
			$userids   = (array) explode( "-" , $userids );
			if( empty($userids) ) {
				$userids  = (array) $userids;
			}
		}
		if( empty( $userids ) ){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
			$this->redirect("admin/accounts/list");
		}
		foreach( $userids as $userid ) {
			$user      = Sirah_Fabric::getUser( $userid  );
			if ( !$user->userid ) {
				  $errorMessages[]  = " L'utilisateur ayant l'id $userid  est invalide ";
				  continue;
			}
			$role                          = $user->getRole();
			$rows[$userid]["userid"]       = $user->userid;
			$rows[$userid]["nom_famille"]  = utf8_decode( $user->lastname  );
			$rows[$userid]["prenom"]       = utf8_decode( $user->firstname );
			$rows[$userid]["phone1"]       = $user->phone1;
			$rows[$userid]["phone2"]       = $user->phone2;
			$rows[$userid]["email"]        = $user->email;
			$rows[$userid]["username"]     = $user->username;
			$rows[$userid]["pays"]         = utf8_decode( $user->country );
			$rows[$userid]["ville"]        = utf8_decode( $user->city );
			$rows[$userid]["langue"]       = utf8_decode( $user->language );
			$rows[$userid]["role"]         = ( $role ) ? utf8_decode( $role ) : APPLICATION_DEFAULT_USERS_ROLENAME ;
			$rows[$userid]["activated"]    = $user->activated;
			$rows[$userid]["enabled"]      = $user->enabled;
			$rows[$userid]["blocked"]      = $user->blocked;
			$rows[$userid]["locked"]       = $user->locked;
			$rows[$userid]["expired"]      = $user->expired;
		}
		$headerRows  = $rows;
		$csvTmpFile  = APPLICATION_DATA_PATH . DS .  "tmp" . DS . "tmpimport" . ".csv";
		$csvHeader   = ( count( $headerRows ) ) ? array_keys( array_shift( $headerRows ) ) : array( "userid"    , "nom_famille" , "prenom" , "phone1" , "phone2"   , "email" ,
				                                                                                    "username"  , "password" , "pays"   , "ville"   , "langue" , "role" ,
				                                                                                    "activated" , "enabled"  , "blocked" , "locked" , "expired" );
		$csvAdapter  = Sirah_Filesystem_File::fabric( "Csv" , array( "filename" => $csvTmpFile , "has_header" => true , "header" => $csvHeader ) , "wb+" );	
		if( $csvAdapter->save( $rows ) ) {
			$this->getResponse()->setHeader("Content-Type" , "text/csv");
			echo $csvAdapter->Output("listecomptes.csv");
			@unlink( $csvTmpFile );
		} else {
			$errorMessages[]  = " Aucun compte utilisateur n'a été exporté ";
		} 
		if( !empty( $errorMessages ) ){
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
		} 
	}
	
	public function importAction()
	{
		$this->view->title  =  " Importer une liste de comptes d'utilisateurs ";
		$errorMessages      = array();
		$importedUsers      = array();
		
		if( $this->_request->isPost() ) {
			$postData    = $this->_request->getPost();
			$has_header  = ( isset( $postData["has_header"] ) ) ? intval( $postData["has_header"] ) : true ;
			$me          = Sirah_Fabric::getUser();
			$usersUpload = new Zend_File_Transfer();
			$usersUpload->addValidator('Count'      , false , 1 );
			$usersUpload->addValidator("Extension"  , false , array("csv" , "xls" , "xlxs"));
			$usersUpload->addValidator("FilesSize"  , false , array("max"  => "25MB"));

			$usersUpload->addFilter("Rename" , array("target" => APPLICATION_DATA_USER_PATH .  "import.csv"  , "overwrite" => true) , "accounts");
			if( $usersUpload->isUploaded("accounts") ){
				$usersUpload->receive("accounts");
			} else {
				$errorMessages[] = "Le fichier que vous importez n'est pas valide , certainement parce qu'elle a une extension invalide,";
			}				
			if( $usersUpload->isReceived("accounts")){
				$csvFilename = APPLICATION_DATA_USER_PATH .  "import.csv";
				$csvAdapter  = Sirah_Filesystem_File::fabric( "Csv" , array("filename"  => $csvFilename , "has_header" => $has_header ) , "rb"  );				
				$rows        = $csvAdapter->getLines();
				$defaultData = array(          "firstname"          => ""  , "lastname"   => "","phone1" => "",  "phone2"      => "",
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
				$i                     = 0;
				$nbre_total_lignes     = count($rows);
				$nbre_validated_lignes = 0;

				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter          = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_Callback('utf8_encode'));
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				//On crée les validateurs nécessaires
				$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer" , "zero" , "string" , "float" , "null"));
				$usernameValidator     = new Sirah_Validateur_Username();
				$emailValidator        = new Sirah_Validateur_Email();
				$passwordValidator     = new Sirah_Validateur_Password();
				$passwordValidator->setMinlength(5);
				if( count( $nbre_total_lignes ) ) {
					foreach( $rows   as  $key => $row ) {						
						$csvData              = array();
						$csvData["lastname"]  = ( !isset( $row["lastname"] ) )  ? ( isset( $row["nom_famille"] ) ? $row["nom_famille"] : ( isset( $row[0] ) ? $row[0] : "" )  ) : $row["lastname"];
						$csvData["firstname"] = ( !isset( $row["firstname"] ) ) ? ( isset( $row["prenom"] )  ? $row["prenom"]   : ( isset( $row[1] ) ? $row[1] : "" )  )   : $row["firstname"];
						$csvData["phone1"]    = ( !isset( $row["phone1"] ) )    ? ( isset( $row[2] ) ? $row[2] : "" )   : $row["phone1"];
						$csvData["phone2"]    = ( !isset( $row["phone2"] ) )    ? ( isset( $row[3] ) ? $row[3] : "" )   : $row["phone2"];
						$csvData["email"]     = ( !isset( $row["email"] ) )     ? ( isset( $row[4] ) ? $row[4] : "" )   : $row["email"];
						$csvData["username"]  = ( !isset( $row["username"] ) )  ? ( isset( $row["identifiant"] )  ? $row["identifiant"]   : ( isset( $row[5] ) ? $row[5] : "" )  )   : $row["username"];
						$csvData["password"]  = ( !isset( $row["password"] ) )  ? ( isset( $row["motpasse"] )  ? $row["motpasse"] : ( isset( $row[6] ) ? $row[6] : "Default30@bf" )  )   : $row["password"];
						$csvData["country"]   = ( !isset( $row["country"] ) )   ? ( isset( $row["pays"] )      ? $row["pays"]     : ( isset( $row[7] ) ? $row[7] : "" )  )   : $row["country"];
						$csvData["city"]      = ( !isset( $row["city"] ) )      ? ( isset( $row["ville"] )     ? $row["ville"]    : ( isset( $row[8] ) ? $row[8] : "" )  )   : $row["city"];
						$csvData["language"]  = ( !isset( $row["language"] ) )  ? ( isset( $row["langue"] )    ? $row["langue"]   : ( isset( $row[9] ) ? $row[9] : "" )  )   : $row["language"];
						$csvData["role"]      = ( !isset( $row["role"] ) )      ? ( isset( $row[10] ) ? $row[10] : APPLICATION_DEFAULT_USERS_ROLENAME ): $row["role"];
						$csvData["activated"] = ( !isset( $row["activated"] ) ) ? ( isset( $row[11] ) ? $row[11] : 1 )   : $row["activated"];
						$csvData["enabled"]   = ( !isset( $row["enabled"] ) )   ? ( isset( $row[12] ) ? $row[12] : 1 )   : $row["enabled"];
						$csvData["blocked"]   = ( !isset( $row["blocked"] ) )   ? ( isset( $row[13] ) ? $row[13] : 0 )   : $row["blocked"];
						$csvData["locked"]    = ( !isset( $row["locked"] ) )    ? ( isset( $row[14] ) ? $row[14] : 0 )   : $row["locked"];
						$csvData["expired"]   = ( !isset( $row["expired"] ) )   ? ( isset( $row[15] ) ? $row[15] : 0 )   : $row["expired"];
						$csvData["params"]    = ( !isset( $row["params"] ) )    ? ( isset( $row[16] ) ? $row[16] : "{}" ): $row["params"];
						
						$cleanData            = array_intersect_key( $csvData , $defaultData );
						$insert_data          = array_merge( $defaultData , $cleanData );
						$ligne                = ( is_numeric( $key ) ) ? $key : $i;
						$ligneErrorMessages   = array();
						$user                 = Sirah_User::getInstance();
						$userTable            = $user->getTable();						 
						if(!$strNotEmptyValidator->isValid($insert_data["firstname"]) || $insert_data["firstname"]=="anonyme"){
							$errorMessages[]           = $ligneErrorMessages[] = " Ligne $ligne : Le prénom de l'utilisateur est une chaine invalide ";
						} else {
							$insert_data["firstname"]  = $stringFilter->filter($insert_data["firstname"]);
						}						 
						if(!$strNotEmptyValidator->isValid($insert_data["lastname"]) || $insert_data["lastname"]=="anonyme"){
							$errorMessages[]           = $ligneErrorMessages[] = " Ligne $ligne : Le nom de l'utilisateur est une chaine invalide ";
						} else {
							$insert_data["lastname"]   = $stringFilter->filter($insert_data["lastname"]);
						}							
						if(!$strNotEmptyValidator->isValid($insert_data["country"])){
							$errorMessages[]          = $ligneErrorMessages[] = " Ligne $ligne : Veuillez indiquer un pays valide ";
						} else {
							$insert_data["country"]   = $stringFilter->filter($insert_data["country"]);
						}						 
						if(!$userTable->checkUsername($insert_data["username"])){
							$errorMessages[]          = $ligneErrorMessages[] = " Ligne $ligne : Le nom d'utilisateur ".$insert_data["username"]." n'est pas valide ou peut etre associé à un second compte ";
						}
						if(!$userTable->checkEmail($insert_data["email"])){
							$errorMessages[]          = " Ligne $ligne : L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
						}
						if( !$passwordValidator->isValid($insert_data["password"])){
							$errorMessages[]          = $ligneErrorMessages[] = " Ligne $ligne :" . implode(" et  " , $passwordValidator->getMessages())   ;
						} else {
							$salt                     = Sirah_Functions_Generator::getAlpha(12);
							$insert_data["password"]  = Sirah_User_Helper::cryptPassword( $insert_data["password"] , $salt);						
						} 
						$insert_data["address"]       = $stringFilter->filter($insert_data["address"]) ;
						$insert_data["zipaddress"]    = $stringFilter->filter($insert_data["zipaddress"]);
						$insert_data["language"]      = $stringFilter->filter($insert_data["language"] );
						$insert_data["facebookid"]    = $stringFilter->filter($insert_data["facebookid"]);
						$insert_data["skypeid"]       = $stringFilter->filter($insert_data["skypeid"]);
						$insert_data["sexe"]          = $stringFilter->filter($insert_data["sexe"]);
						$insert_data["lastIpAddress"] = "";
						$insert_data["lastHttpClient"]= "";
						$insert_data["lastSessionId"] = "";
						$insert_data["activated"]     = (isset($insert_data["activated"])) ? intval($insert_data["activated"]) : 1;
						$insert_data["blocked"]       = (isset($insert_data["blocked"]))   ? intval($insert_data["blocked"])   : 0;
						$insert_data["locked"]        = (isset($insert_data["locked"]))    ? intval($insert_data["locked"])    : 0;						
						if( empty( $ligneErrorMessages ) ) {
							$dbAdapter  = $userTable->getAdapter();
							$dbAdapter->insert( $userTable->info("name") , $insert_data );
							$newuserid  = $dbAdapter->lastInsertId();
							if( !$newuserid ) {
								$savedErrors  = $user->getMessages("error");
								foreach( $savedErrors as $type => $msg){
									$msg              = (is_array($msg)) ? array_shift( $msg )  : $msg;
									$errorMessages[]  = $msg;
								}
							} else {
								if( isset( $csvData["role"]) && !empty( $csvData["role"] ) && 
							        ( $roleid = Sirah_User_Acl_Table::getRoleid( $csvData["role"] ) ) && ( !Sirah_User_Acl_Table::hasRole( $newuserid , $csvData["role"] ) ) ) {
									  Sirah_User_Acl_Table::assignRoleToUser( $newuserid , $roleid );
								}
								$nbre_validated_lignes++;
								$userData                   = $insert_data;
								$userData["userid"]         = $newuserid;
								$userData["registeredDate"] = date("d-m-Y H:i:s");
								$importedUsers[]            = $userData;
							}
						} 
						$i++;
					}
				if( empty( $errorMessages ) ) {
					if($this->_request->isXmlHttpRequest()) {
					   $this->_helper->viewRenderer->setNoRender(true);
					   $this->_helper->layout->disableLayout( true );
					   $jsonResponse             = array();
					   $jsonResponse["users"]    = $importedUsers;
					   $jsonResponse["success"]  = sprintf(" %d comptes utilisateurs ont été importés sur un total de %d comptes " , $nbre_validated_lignes , $nbre_total_lignes );
					   echo ZendX_JQuery::encodeJson( $jsonResponse );
					   exit;
					}
					$this->_helper->Message->addMessage(sprintf(" %d comptes utilisateurs ont été importés sur un total de %d comptes " , $nbre_validated_lignes , $nbre_total_lignes ) , "success" );
				}
				} else {
					$errorMessages[]  = "Aucune ligne valide n'a été retrouvée dans le fichier uploadé";
				}				
			} else {
				$errorMessages[]      = "Le fichier que vous importez n'a pas été correctement chargé sur le serveur";
			}
		}	
		if( !empty( $errorMessages )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$jsonResponse          = array();
				$jsonResponse["users"] = $importedUsers;
				$jsonResponse["error"] = " Certaines lignes de la fiche n'ont pas été importées pour les raisons suivantes : " . implode(" , " , $errorMessages );
				echo ZendX_JQuery::encodeJson( $jsonResponse );
				exit;
			}
			foreach($errorMessages as $message) {
				$this->_helper->Message->addMessage( " Certaines lignes de la fiche n'ont pas été importées pour les raisons suivantes : " . $message ) ;
			}
		}
		$this->view->users            = $importedUsers;		
	}
	
	
	/**
	 * L'action qui permet d'activer un compte
	 * utilisateur.
	 *
	 *
	 */
	public function enableAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();		
		if(!is_array($userids)){
			$userids   = explode(",",$userids);
		}		
		if(empty($userids)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/accounts/list");
		} 
		
		foreach($userids as $userid){
			$user  = Sirah_Fabric::getUser(intval($userid));			
			if(!$user->userid){
				continue;
			}
			if(!$user->enable()){
				$errorMessages[] = sprintf("L'activation du compte de %s %s a echoué",$user->lastname,$user->firstname);
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
				echo ZendX_JQuery::encodeJson(array("success" => "L'activation des comptes s'est produite avec succès"));
				exit;
			}
			    $this->_helper->Message->addMessage("L'activation des comptes s'est produite avec succès","success");
		}
		$this->redirect("admin/accounts/list");
	}
	
	
	/**
	 * L'action qui permet de desactiver un compte
	 * utilisateur.
	 *
	 *
	 */
	public function disableAction()
	{
		$userids       = $this->_getParam("userids",$this->_getParam("ids",array()));
		$errorMessages = array();
		
		if( !is_array($userids) ){
			$userids   = explode("," , $userids);
		}		
		if(empty($userids)){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
		   $this->setRedirect("Impossible d'effectuer cette opération, les paramètres fournis sont invalides","error");
		   $this->redirect("admin/accounts/list");
		} 		
		foreach($userids as $userid){
			$user  = Sirah_Fabric::getUser(intval($userid));			
			if(!$user->userid){
				continue;
			}
			if(!$user->disable()){
				$errorMessages[] = sprintf("La désactivation du compte de %s %s a echoué",$user->lastname,$user->firstname);
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
		$this->redirect("admin/accounts/list");
	}
	
	
	public function rightsAction()
	{
		$userid      = intval($this->_getParam("userid"  , $this->_getParam("id" , 0)));
		$user        = Sirah_Fabric::getUser($userid);
	
		if(!$user->userid) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "L'identifiant de l'utilisateur concerné est incorrect"));
				exit;
			}
			$this->setRedirect("L'identifiant de l'utilisateur est incorrect" , "error");
			$this->redirect("admin/useroles/list");
		}	
		$this->view->rights = Sirah_User_Acl_Table::getUserRights($userid);
		$this->view->title  = sprintf("Les permissions de `%s %s` " , $user->lastname , $user->firstname);
	}
	
	public function assignrightsAction()
	{
		$userid      = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$user        = Sirah_Fabric::getUser($userid);
		$me          = Sirah_Fabric::getUser();
		
		if(!$user->userid){
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'assigner des roles à cet utilisateur, les paramètres fournis ne sont pas valides","error");
			$this->redirect("admin/accounts/list");
		}

		$userTable     = $user->getTable();
		$dbAdapter     = Sirah_Fabric::getDbo();
		$tablePrefix   = $userTable->info("namePrefix");
		$errorMessages = array();
		
		if($this->_request->isPost()) {			
			$postData         = $this->_request->getPost();			
			$allPrivileges    = isset($postData["all"])     ? $postData["all"]     : array();
			$objectPrivileges = isset($postData["objects"]) ? $postData["objects"] : array();
			
			//On supprime toutes les permissions existantes de l'utilisateur
			//$dbAdapter->delete($tablePrefix ."system_acl_rights" ,"userid=".intval($userid));			
			if(!is_array($allPrivileges)) {
				$allPrivileges    = (array) $allPrivileges;
			}
			if(!is_array($objectPrivileges)) {
				$objectPrivileges = (array) $objectPrivileges;
			}			
			if(count($allPrivileges)) {
				foreach($allPrivileges as $allPrivilege) {
					$allPrivilegeObjects  = explode("-" , $allPrivilege);
					if(count($allPrivilegeObjects) >= 2) {
						$allResourceid   = array_shift($allPrivilegeObjects);
						foreach($allPrivilegeObjects as $allPrivilegeObjectId) {
							if($allObjectid = Sirah_User_Acl_Table::getObjectid("all" , $allResourceid)){
								if(isset($objectPrivileges[$allPrivilegeObjectId])) {
									unset($objectPrivileges[$allPrivilegeObjectId]);
								}
								$rightRow = array("objectid" => $allObjectid, "userid" => $userid,"roleid" => 0,"allow" => 1,"creationdate" => time(),"creatoruserid"=> $me->userid);
								if(!Sirah_User_Acl_Table::rightExist($rightRow)){
								    $dbAdapter->delete($tablePrefix ."system_acl_rights", array("userid=".intval($userid), "objectid=".$allObjectid ));	
									if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
										$errorMessages[]  = "La permission objectid#".$allObjectid." resourceid#".$allResourceid." n'a pas été enregistrée ";
									} 
								} 								
							} 
						}
					}
				}
			}			
		    if(count($objectPrivileges)) {
				foreach($objectPrivileges as $objectPrivilegeId) {
					if($objectPrivilegename = Sirah_User_Acl_Table::getObjectname($objectPrivilegeId)) {
						$rightRow = array( "objectid" => $objectPrivilegeId, "userid" => $userid, "roleid" => 0, "allow" => 1, "creationdate" => time(),"creatoruserid"=> $me->userid);
						if(!Sirah_User_Acl_Table::rightExist($rightRow)){
						        $dbAdapter->delete($tablePrefix ."system_acl_rights", array("userid=".intval($userid), "objectid=".$objectPrivilegeId));
							if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
								$errorMessages[]  = "La permission objectid#".$objectPrivilegeId." n'a pas été enregistrée ";
							}
						} 
					}
				}
			}			
			if(!empty($errorMessages)) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
					exit;
				}
				foreach($errorMessages as $errorMessage) {
					$this->getHelper("Message")->addMessage($errorMessage);
				}
			} else {
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success"  => sprintf("Les permissions de %s %s ont été définies avec succès" , $user->lastname , $user->firstname )));
					exit;
				}
				$this->setRedirect(sprintf("Les permissions de %s %s ont été définies avec succès" , $user->lastname , $user->firstname ) , "success");
				$this->redirect("admin/accounts/list");
			}					
		}		
		$this->view->rightobjects = Sirah_User_Acl_Table::getObjects();
		$this->view->user         = $user;
		$this->view->userid       = $userid;   
		$this->view->title        = sprintf("Assigner des permissions à `%s %s` " , $user->lastname , $user->firstname);
		
		$this->render("definerights");
	}
	
	
	/**
	 * L'action qui permet d'assigner des roles à un
	 * utilisateur.
	 *
	 *
	 */
	public function assignrolesAction()
	{
		$userid      = intval($this->_getParam("userid",$this->_getParam("id",0)));
		$user        = Sirah_Fabric::getUser( $userid );
				
		if(!$user->userid){
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Impossible d'effectuer cette opération, les paramètres fournis sont invalides"));
				exit;
			}
			$this->setRedirect("Impossible d'assigner des roles à cet utilisateur, les paramètres fournis ne sont pas valides","error");
			$this->redirect("admin/accounts/list");
		}
		
		$userTable   = $user->getTable();
		$userRoles   = $userTable->getRoles();
		$allRoles    = Sirah_User_Acl_Table::getAllRoles();
		$dbAdapter   = Sirah_Fabric::getDbo();
		$tablePrefix = $userTable->info("namePrefix");
		
		if($this->_request->isPost()){			
		   $postData = $this->_request->getPost();		   
		   $newRoles = isset($postData["roles"]) ? $postData["roles"] : array();
		   
		   if(is_string($newRoles)) {
		   	  $newRoles  = explode("," , $newRoles);
		   }
		   if(!empty($newRoles) && is_array($newRoles)){
		   	  	if($dbAdapter->delete($tablePrefix."system_acl_useroles","userid=".$userid)){
		   	  	 if(true==Sirah_User_Acl_Table::assignRoleToUser($userid,$newRoles)){
		   	  	 	if( $this->_request->isXmlHttpRequest()) {
		   	  	 		$this->_helper->layout->disableLayout(true);
		   	  	 		$this->_helper->viewRenderer->setNoRender(true);
		   	  	 		echo ZendX_JQuery::encodeJson(array("success" => sprintf("Les roles ont été assignés avec succès à l'utilisateur %s %s",$user->lastname,$user->firstname)));
		   	  	 		exit;
		   	  	 	}
		   	  		$this->setRedirect(sprintf("Les roles ont été assignés avec succès à l'utilisateur %s %s",$user->lastname,$user->firstname),"success");
		   	  		$this->redirect("admin/accounts/list");
		   	  	}		   	  	
		   	  }		   	  
		   }
		   $this->setRedirect(sprintf("Aucun role n'a été assigné à %s %s",$user->lastname,$user->firstname),"message");
		   $this->redirect("admin/accounts/list");
		}		
		$this->view->title     = sprintf("Assigner des roles à %s %s",$user->lastname,$user->firstname);
		$this->view->allRoles  = $allRoles;
		$this->view->userRoles = $userRoles;
		$this->view->userid	   = $userid;
		
		$this->render("roles");
	}		
}