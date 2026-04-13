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
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
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

class Admin_AccountController extends Sirah_Controller_Default
{
	
	const UNABLE_TO_SENDMAIL     =  0;
	const UNABLE_TO_ACTIVATE     = -1;
	const ACTIVATE_TOKEN_EXPIRED = -2;
	
	
	/**
	 * L'action qui permet d'afficher le tableau de bord
	 *
	 * de l'utilisateur
	 *
	 *
	 */
	public function indexAction()
	{
		$me     = Sirah_Fabric::getUser();
		print_r($me->isLoggedIn());
		$this->_helper->viewRenderer->setNoRender(true);	
	}
	
	/**
	 * L'action qui permet à l'utilisateur
	 * 
	 * de visualiser les informations de son compte
	 *
	 *
	 */
	public function settingsAction()
	{
		$me                = Sirah_Fabric::getUser();
		$modelLocalite     = $this->getModel("localite");
		$modelDomaine      = $this->getModel("domaine");
		$modelProject      = new Model_Project();
			
		$pwdSession        = new Zend_Session_Namespace("lostpassword");
		$userTable         = $me->getTable();			
		if(!$me->isLoggedIn()){
			$me->logout();
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération" , "error");
			$this->redirect("admin/account/login");
		}
		$projectInstance    = $modelProject->findRow(1, "current", null, false );
        if( $projectInstance ) {
			$me->setParams( $projectInstance->paramsToArray(), false); 
		}			
		$userData           = $userTable->getData();
		$userRoles          = Sirah_User_Acl_Table::getRoles($me->userid);	
        $userParams         = $me->getParams();	
		$projectParams      = $projectInstance->paramsToArray();
        			
		if( $this->_request->isPost() ) {			
			$postData       = $this->_request->getPost();
			$filteredParams = array();
			$stringFilter   = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());			
			if( count($postData ) ) {
				foreach( $postData as $paramKey => $paramValue ) {					     
					     $filteredParams[$paramKey]     =  $paramValue;
						 if(empty( $paramValue ) && isset($projectParams[$paramKey])) {
							 $filteredParams[$paramKey] = $projectParams[$paramKey];
						 }
				}
			}
			if( $me->setParams( $filteredParams ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("success" => "Les changements que vous avez faits ont été pris en compte par le système"));
					exit;
				}
				$this->_helper->Message->addMessage("Les changements que vous avez faits ont été pris en compte par le système" , "success");
			} else {
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("message" => "Aucun changement n'a été fait dans les paramètres du compte"));
					exit;
				}
				$this->_helper->Message->addMessage("Aucun changement n'a été fait dans les paramètres du compte" , "message");
			}			
		}
         
        if(count(    $projectParams ))	 {
			foreach( $projectParams as $paramKey => $paramValue ) {
				     if((!isset( $userParams[$paramKey])) || (isset($userParams[$paramKey]) && empty( $userParams[$paramKey]))) {
						 $userParams[$paramKey]  = $paramValue;
					 }  
			}
			$me->setParams($userParams);
        }			
		$this->view->title     = "Les paramètres de  mon compte";
		$this->view->user      = $me;
		$this->view->domaines  = $modelDomaine->getSelectListe( "Selectionnez un secteur d'article", array("domaineid" , "libelle"), array() , 0 , null , false);
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$this->view->roles     = $userRoles;
		$this->view->params    = $userParams;	
		$this->view->pwdtoken  = $pwdSession->lostpwdtoken = Sirah_User_Helper::getToken(15) . time();
	}
	
	public function changepwdAction()
	{
		$pwdSession               = new Zend_Session_Namespace("lostpassword");
		//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
		if(!isset( $pwdSession->lostpwdtoken ) || !$this->_hasParam( $pwdSession->lostpwdtoken ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Formulaire invalide"));
				exit;
			}
			die("Formulaire Invalide");
		}			
		$postData            = $this->_request->getParams();
		$me                  = Sirah_Fabric::getUser();
		$userTable           = $me->getTable();
		$dbAdapter           = $userTable->getAdapter();
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer", "zero", "string", "float", "empty_array","null"));
		$passwordSelect      = $dbAdapter->select()->from($userTable->info("name"), array("password"))->where("userid = ?", $me->userid );
		
		$passwordRow         = $dbAdapter->fetchRow( $passwordSelect );			
		$oldPwd              = ( isset( $postData["password"] ) )      ? trim( $postData["password"] ) : "";
		$newPwd              = ( isset( $postData["newpwd"] ) )        ? trim( $postData["newpwd"] )   : "";
		$confirmation        = ( isset( $postData["newpwdconfirm"] ) ) ? trim( $postData["newpwdconfirm"] ) : "";
		
		if( !Sirah_User_Helper::verifyPassword( $oldPwd , $passwordRow["password"] ) ) {
			$errorMessages[] = "Votre ancien mot de passe que vous avez saisi n'est pas valide";
		}			
		if( empty( $errorMessages ) && !$strNotEmptyValidator->isValid( $postData["newpwd"] ) ) {
			$errorMessages[]           = implode(" ; ",$passwordValidator->getMessages());
		} elseif( empty( $errorMessages ) ) {
			if( empty( $postData["newpwdconfirm"] ) ) {
				$errorMessages[]       = "Des données de création sont manquantes";
			} elseif( $confirmation   !== $newPwd ) {
				$errorMessages[]       = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
			}
		}
		if(count(  $errorMessages )  ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->getHelper("Message")->addMessage($errorMessage,"error");
			}
			$errorMsg    = implode(", ",$errorMessages );
			$this->setRedirect( $errorMsg."; veuillez réessayer " , "error");
			$this->redirect("admin/account/settings");
		}  else {
			$userid       = $pwdSession->userid;
			$user         = Sirah_Fabric::getUser($userid);		
			if( $user->setPassword( $newPwd ) ) {
				$this->setRedirect(" Votre mot de passe a été mis à jour avec succès ","success");
				$this->redirect("admin/account/settings");
			} else {
				$errorMsg     = " La mise à jour du mot de passe n'a pas fonctionné ";
				if( APPLICATION_DEBUG ) {
					$errorMsg .= " , pour les raisons suivantes : ". implode(" , ",$user->getMessage());
				}
				$this->setRedirect( $errorMsg."; veuillez réessayer ","error");
				$this->redirect("admin/account/settings");
			}
		}
	}
	
	/**
	 * L'action qui permet d'initialiser les données du compte
	 *
	 * de l'utilisateur
	 *
	 *
	 */
	public function initAction()
	{
		$cacheManager    = Sirah_Fabric::getCachemanager();
		if (!$cacheManager->hasCache("NavigationCache")) {
			$navigationCache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
			$cacheManager->setCache("NavigationCache", $navigationCache );
		} else {
			$navigationCache = $cacheManager->getCache("NavigationCache" );
		}
		$navigationCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->getHelper("Message")->clearMessages();
		$this->_forward("index");
	}
	
	
	/**
	 * L'action qui permet de vérifier l'identité
	 * 
	 * de l'utilisateur
	 *
	 *
	 */
	public function loginAction()
	{		
        $this->view->title   = " CONNECTEZ-VOUS A VOTRE COMPTE";
        $this->view->subtitle= " Bienvenue sur la page d'authentification";
		
		$guest               = Sirah_Fabric::getUser();		
		if( $guest->userid ) {
			$this->redirect("admin/dashboard/list");
		}
		//On met à jour le layout pour la connexion à un compte
		$this->_helper->layout->setLayout("login");
		
		$viewData           = array("username"=>null,"password"=>null,"rememberme"=>0);
		$errorMessages      = array();
		$captchaSession     = new Zend_Session_Namespace("captchas");		
		$csrfTokenId        = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue     = $this->_helper->csrf->getToken(300);
		$csrfFormNames      = $this->_helper->csrf->getFormNames(array("username", "password", "rememberme") , false );
		$captcha            = null;
		$authSuccess        = false;
		$nextUri            = $this->_getParam("next", $this->_getParam("continue", $this->_getParam("returnTo", "admin/dashboard/list" ) ) );
				
		//Si la vérification du captcha a echoué au moins dix fois, on bloque l'accès à la page pendant une heure
		if( isset( $captchaSession->checksAuth )  && ( $captchaSession->checksAuth >= NB_CHECK_AUTHS_CAPTCHA ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson(array( "error" => "Vous ne pourrez plus exécuter cette action pendant au moins une heure" ));
				exit;
			}		    
			$this->setRedirect("Vous ne pourrez plus exécuter cette action pendant au moins une heure", "error");
			$this->redirect("admin/index/index");
		}
		//On active le captcha si c'est nécessaire
		if( isset( $captchaSession->showcaptcha ) && ( true == $captchaSession->showcaptcha  )) {
			$imgDir               = realpath( DOCUMENTS_PATH  ."/../../myTpl/rccm/images/captchas/")	;
			$imgUrl               = ROOT_PATH ."/myTpl/rccm/images/captchas/";
			$captcha              = $this->_createCaptcha( 5 , "arial.ttf" , $imgDir , $imgUrl );
			$this->view->subtitle = "Nous voulons vérifier que vous n'etes pas un robot";
		}				
		//Quand les données sont postées
		if( $this->_request->isPost() ) {							
			//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
			if( $this->_helper->csrf->isValid() ) {				
				$stringFilter   = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
					
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" ,"zero" ,"string" ,"float" ,"empty_array" ,"null"));				
				if(!$captchaSession->initialised ) {
					$captchaSession->initialised = true;					
					$captchaSession->showcaptcha = false;
					$captchaSession->checksAuth  = 0;
					$captchaSession->setExpirationSeconds(7200);
				}				
				$postData             = $this->_request->getPost();
				$viewData             = array_merge( $viewData , $postData );
					
				$identity             = ( isset($postData["username"]   )) ? $stringFilter->filter( $postData["username"] ) : null;
				$credential           = ( isset($postData["password"]   )) ? $postData["password"] : null;
				$rememberMe           = ( isset($postData["rememberme"] )) ? intval($postData["rememberme"]) : 1;
				if(!$strNotEmptyValidator->isValid($identity) && ( !$captchaSession->showcaptcha ) ) {
					$errorMessages[]  = "Entrez un identifiant valide";
				}
				if(!$strNotEmptyValidator->isValid( $credential ) && ( !$captchaSession->showcaptcha )) {
					$errorMessages[]  = "Entrez un mot de passe valide";
				}								
				//On vérifie le captcha
				if((null!==$captcha) && ( false!==$captcha ) && ( true == $captchaSession->showcaptcha ) ) {
					$captchainput            = (isset($postData["captcha"]    )) ? $postData["captcha"] : "";
					$captchatime             = (isset($postData["captchatime"])) ? $postData["captchatime"]:0;
					if(!$captcha->isValid( $captchainput ) ) {
						$captchaMessages     = $captcha->getMessages();
						$errorMessages[]     = "La vérification du chiffre qui apparait dans l'image a echoué";
						if( APPLICATION_DEBUG ) {
							$errorMessages[] = (is_array($captchaMessages)) ? implode(" ", $captchaMessages) : $captchaMessages;
						}
						$captchaSession->showcaptcha = true;
						$authSuccess                 = false;
						$captchaSession->checksAuth++;
					} else {
						$captchaSession->showcaptcha = false;
						$captchaSession->checksAuth  = 0;
					}
				}
				//S'il n'ya pas d'erreur dans les processus précédents, on authentifie l'utilisateur
				if( empty( $errorMessages )  ) {
					$guest->clearMessages();
					$loggedInUser = $guest->login( $identity , $credential , $rememberMe);
					$captchaSession->checksAuth++;
					if((false !== $loggedInUser) && (null!==$loggedInUser)){
						$authSuccess                 = true;
					} else {
						$errorMessages               = array_shift($guest->getMessages());
						$authSuccess                 = false;						
					}
				}
				if( count( $errorMessages ) ) {									
					//Après trois tentatives d'authentification échouées, vérifier qu'on n'a pas affaire à un robot à travers un captcha.
					if(!isset( $captchaSession->checksAuth    ) ) {
						$captchaSession->checksAuth = 0;
					} elseif ( $captchaSession->checksAuth >= NB_CHECK_AUTHS ) {
						$captchaSession->showcaptcha= true;
						if( null==$captcha ) {
							$captcha = $this->_createCaptcha( 8, "trebucit.ttf", realpath(DOCUMENTS_PATH."/../../myTpl/rccm/images/captchas/"), (ROOT_PATH."/myTpl/rccm/images/captchas/"));
						}
					} 					
				}
				if( $authSuccess )  {
					$cacheManager   = Sirah_Fabric::getCachemanager();
					$successMsg     = sprintf("Bienvenue Mr/Mrs %s %s sur la plateforme du ERCCM.", $loggedInUser->lastname, $loggedInUser->firstname );
					if( $cacheManager->hasCache("Acl")){
						$aclCache   = $cacheManager->getCache("Acl");
						$aclCache->save("isAllowed","aclAllowed_".$loggedInUser->userid."_account_init");
						$aclCache->save("isAllowed","aclAllowed_".$loggedInUser->userid."_account_index");
					}
					if( !$cacheManager->hasCache("NavigationCache")) {
						$navigationCache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime"=> 1800, "automatic_serialization"=> true ) );
						$cacheManager->setCache("NavigationCache", $navigationCache );
					} else {
						$navigationCache = $cacheManager->getCache("NavigationCache" );
					}
					if( $cacheManager->hasCache("Model") ) {
						$modelCache   = $cacheManager->getCache("Model");
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}
					if( $cacheManager->hasCache("roleAcl") ) {
						$roleAclCache   = $cacheManager->getCache("roleAcl");
						$roleAclCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}
					$navigationCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					Zend_Uri::setConfig(array('allow_unwise' => true));
					if ( Zend_Uri::check( $nextUri ) ) {
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender( true );
							$this->_helper->layout->disableLayout( true );
							echo ZendX_JQuery::encodeJson(array("reload"=> true, "newurl" => $nextUri ));
							exit;
						}
						$this->redirect( $nextUri );
					} else {
						$front                 = Zend_Controller_Front::getInstance();
						$application           = new Zend_Session_Namespace("AppRequests");
			            $defaultModuleName     = (isset($application->last["module"])     && !empty($application->last["module"]    ))? $application->last["module"]     : "admin";
						$defaultControllerName = (isset($application->last["controller"]) && !empty($application->last["controller"]))? $application->last["controller"] : "dashboard";
						$defaultActionName     = (isset($application->last["action"])     && !empty($application->last["action"]    ))? $application->last["action"]     : "list";
						$requestParams         = (isset($application->last["params"])     && !empty($application->last["params"]    ))? $application->last["params"]     : array();
						$urlParams             = array("module"=>$defaultModuleName,"controller"=>$defaultControllerName,"action"=>$defaultActionName);
						$routeParams           = (count($requestParams))?array_merge($urlParams,$requestParams) : $urlParams;
						$nextURI               = $this->view->url($routeParams,"default",true);
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender( true );
							$this->_helper->layout->disableLayout(     true );
							$this->getHelper("Message")->addMessage( $successMsg ,"success");
							echo ZendX_JQuery::encodeJson(array("reload"=>true,"newurl"=>$nextURI));
							exit;
						}
						$this->setRedirect($successMsg , "success");
						$this->redirect($nextURI);
					}					
				} 
			} else {
			    if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true );
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres de securité rattachés au formulaire doivent etre expirés. Veuillez re-actuliser la page", "reload" => true ));
					exit;
			    }
			    $csrfFormNames = $this->_helper->csrf->getFormNames(array("username", "password", "rememberme") , true );
			}									
		}		
		if( ( null!=$captcha ) && isset( $captchaSession->showcaptcha ) && (true == $captchaSession->showcaptcha)) {
			$captcha->generate();
			if( $this->_request->isXmlHttpRequest() && ( $captchaSession->checksAuth == NB_CHECK_AUTHS ) ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("reload"=>true ) );
				exit;
			}
		}
		//print_r(  $captchaSession->checksAuth );
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages) ));
				exit;
			}
			foreach( $errorMessages as $key => $error ) {
				     $type = (is_numeric($key)) ? "error" : $key;
				     $this->getHelper("Message")->addMessage( $error ,"error");
			}
		}
		$this->view->messages       = $errorMessages;
		$this->view->data           = $viewData;
		$this->view->captcha        = $captcha;
		$this->view->showcaptcha    = ( isset( $captchaSession->showcaptcha )) ? $captchaSession->showcaptcha : false;
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->doneUri        = $nextUri;
		$this->view->register       = true;		
	}
	
	
	/**
	 * L'action qui permet à l'utilisateur de se deconnecter
	 *
	 *
	 */
	public function logoutAction()
	{
		$loggedInUser   = Sirah_Fabric::getUser();		
		$defaultSession = new Zend_Session_Namespace();
		$cacheManager   = Sirah_Fabric::getCachemanager();
		if( $cacheManager->hasCache("Acl") ) {
			$aclCache   = $cacheManager->getCache("Acl");
			$aclCache->save("isAllowed","aclAllowed_".$loggedInUser->userid."_account_activate");
		}
		if (!$cacheManager->hasCache("NavigationCache")) {
			$navigationCache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
			$cacheManager->setCache("NavigationCache", $navigationCache );
		} else {
			$navigationCache = $cacheManager->getCache("NavigationCache" );
		}
		if( $cacheManager->hasCache("Model")){
			$modelCache   = $cacheManager->getCache("Model");
			$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		if( $cacheManager->hasCache("userAcl")){
			$userAclCache   = $cacheManager->getCache("userAcl");
			$userAclCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		if( $cacheManager->hasCache("roleAcl")){
			$roleAclCache   = $cacheManager->getCache("roleAcl");
			$roleAclCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		$navigationCache->clean(Zend_Cache::CLEANING_MODE_ALL);
		if(!isset($defaultSession->logoutoken)){
			die("Impossible de déconnecter cet utilisateur de sa session");
		} 		
		if(!$this->_hasParam($defaultSession->logoutoken)){
			die("Impossible de déconnecter cet utilisateur de sa session");
		}	
		$loggedInUser->logout();
		$this->redirect("admin/account/login");	
	}
				
	/**
	 * L'action qui permet à un utilisateur de créer son compte.
	 *
	 *
	 */
	public function createAction()
	{		
		//On initialise les variables nécessaires
		$user            = Sirah_Fabric::getUser();
		$registerDoneUri = $this->_getParam("doneUri" , null );
		$errorMessages   = array();
		
		//S'il n'est pas invité, on lui refuse cette opération
		if(!$user->isGuest()){
			$this->setRedirect("Vous n'etes pas autorisé à effectuer cette opération","error");
			$this->redirect("admin/myprofile/infos");
		}		
		$defaultData           = array("firstname"         => ""  , "lastname"   => "","phone1" => "",   "phone2"      => "",
				                       "address"           => ""  , "zipaddress" => "","city"   => "",   "country"     => Sirah_User_Helper::getCountry(),
				                       "language"          => "FR", "facebookid" => "","skypeid"=> "",   "username"    => "",
				                       "password"          => ""  , "email"      => "","sexe"   => null, "expired"     => 0,
				                       "activated"         => 1   , "blocked"    => 0 ,"locked" => 0, "accesstoken" => "",
				                       "logintoken"        => ""  , "params"     => "",
				                       "admin"             => 0,
				                       "statut"            => 0,
				                       "creatoruserid"     => $user->userid,
				                       "updateduserid"     => 0,
				                       "registeredDate"    => time(),
				                       "lastConnectedDate" => 0,
				                       "lastUpdatedDate"   => 0,
				                       "lastIpAddress"     => "",
				                       "lastHttpClient"    => "",
				                       "lastSessionId"     => "" );		
		$defaultSession         = new Zend_Session_Namespace("inscription");
		if(!$defaultSession->initialised ) {
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}		
		if( $this->_request->isPost()){             			
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ) {
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$defaultSession        = new Zend_Session_Namespace("captchacheck");
				$defaultSession->checks= 0;
				$urlDone               = $this->_helper->HttpUri(array("controller" => "offres"  , "action" => "list"));
				$urlSecurityCheck      = $this->_helper->HttpUri(array("controller" => "securitycheck", "action" => "captcha", "params" => array("done" => $urlDone , "token" => $defaultSession->token )));
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide", "reload" => true, "newurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide","error");
				$this->redirect($urlSecurityCheck);
			}			
			$postData           = $viewData = $this->_request->getPost();
			$insert_data        = array_merge( $defaultData , $postData);
			$newuserid          = 0;
			$userTable          = $user->getTable();
			$dbAdapter          = $userTable->getAdapter();
			$prefixName         = $userTable->info("namePrefix");			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$nameValidator       = new Validator_Name();						
			$emailValidator      = new Sirah_Validateur_Email();
			$passwordValidator   = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(4);
				
			$insert_data["username"]     = $stringFilter->filter($insert_data["username"]);
			$insert_data["email"]        = $stringFilter->filter($insert_data["email"]);
			
			if(!$strNotEmptyValidator->isValid( $insert_data["firstname"] ) || ( !$nameValidator->isValid( $insert_data["firstname"] ) ) ) {
				$errorMessages[]         = " Le prénom que vous avez saisi, est invalide ";
		    } else {
				$insert_data["firstname"]= $stringFilter->filter($insert_data["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($insert_data["lastname"]) || ( !$nameValidator->isValid( $insert_data["lastname"] ) ) ) {
				$errorMessages[]         = " Le nom de famille que vous avez saisi, est invalide ";
			} else {
				$insert_data["lastname"] = $stringFilter->filter($insert_data["lastname"]);
			}
			if(!$strNotEmptyValidator->isValid( $insert_data["country"] ) ) {
				$insert_data["country"]  = " ";
			} else {
				$insert_data["country"]  = $stringFilter->filter($insert_data["country"]);
			}
			if(!$strNotEmptyValidator->isValid($insert_data["phone1"])){
				$insert_data["phone1"]   = " ";
			} else {
				$insert_data["phone1"]   = $stringFilter->filter( $insert_data["phone1"] );
			}			
			if(!$userTable->checkEmail( $insert_data["email"] ) || !$emailValidator->isValid( $insert_data["email"] ) ) {
				$errorMessages[]         = " L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}
			if(!$strNotEmptyValidator->isValid( $insert_data["username"] ) && empty( $errorMessages ) ) {
				$newUsername             = preg_replace('/\s+/', '.', strtolower( $insert_data["lastname"]." ".$insert_data["firstname"] ) );
				$countUsernameSelect     = $dbAdapter->select()->from( $prefixName . "system_users_account")->where("username = ?",$newUsername );
				$countUsername           = intval( count( $dbAdapter->fetchAll( $countUsernameSelect ) ) );
				$countVal                = $countUsername + 1;
				$insert_data["username"] = ( $countUsername  ) ? $newUsername.".".$countVal : $newUsername;				
			} else {
				$insert_data["username"] = $stringFilter->filter( $insert_data["username"] );
			}
			if(!$strNotEmptyValidator->isValid( $insert_data["password"] ) ) {
				$errorMessages[]         = "Entrez un mot de passe valide";
			} else {
				$insert_data["password"] = $postData["password"];			
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]    = "Des données de création sont manquantes";
				} elseif($postData["confirmedpassword"] !== $insert_data["password"]) {
					$errorMessages[]    = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			}
			$insert_data["address"]        = $stringFilter->filter($insert_data["address"]);
			$insert_data["city"]           = ( isset( $insert_data["city"] ) )       ? intval( $insert_data["city"] ) : 0;
			$insert_data["phone2"]         = $stringFilter->filter( $insert_data["phone2"]);
			$insert_data["zipaddress"]     = $stringFilter->filter( $insert_data["zipaddress"]);
			$insert_data["language"]       = ( isset( $insert_data["language"] ) )   ? $stringFilter->filter( $insert_data["language"]) : "";
			$insert_data["facebookid"]     = $stringFilter->filter( $insert_data["facebookid"] );
			$insert_data["skypeid"]        = $stringFilter->filter( $insert_data["skypeid"] );
			$insert_data["sexe"]           = $stringFilter->filter( $insert_data["sexe"]);
			$insert_data["activated"]      = 0;
			$insert_data["blocked"]        = 0;
			$insert_data["locked"]         = 0;
			$insert_data["lastIpAddress"]  = Sirah_Functions::getIpAddress();
			$insert_data["lastHttpClient"] = Sirah_Functions::getBrowser();
			$insert_data["lastSessionId"]  = Zend_Session::getId();
				
			$defaultData                   = $insert_data;			 
			if( empty( $errorMessages ) ) {
				$user->clearMessages();
				if(!$newuserid   = $user->save($insert_data)){
					$saveErrors  = $user->getMessages("error");
					foreach($saveErrors as $type => $msg){
						$msg     = (is_array($msg)) ? array_shift($msg)  : $msg;
						$errorMessages[]  = $msg;
					}
				}
			}	
			if(!empty($errorMessages)){
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
					exit;
				}
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}  else {				               				
				$config               = Sirah_Fabric::getConfig();		
				$mailer               = Sirah_Fabric::getMailer();
				$newUser              = Sirah_Fabric::getUser($newuserid);				
				$activateAccountToken = Sirah_User_Helper::getToken(10) . time() . $newuserid;	
				$modelStructure       = $this->getModel("structure");
				$structure            = $modelStructure->get();
				
				$defaultToEmail       = $config["resources"]["mail"]["defaultFrom"]["email"];
				$defaultToName        = $config["resources"]["mail"]["defaultFrom"]["name"];
				
				$linkActivateWithToken= Sirah_Functions::url($this->view->url(array("controller" => "account", "action" => "activate")),"token=".$activateAccountToken."&uid=".$newuserid, 81 , APPLICATION_HOST);
				$linkSimpleActivate   = Sirah_Functions::url($this->view->url(array("controller" => "account", "action" => "activate")),"uid=".$newuserid, 81 , APPLICATION_HOST);
				$linkContact          = Sirah_Functions::url($this->view->url(array("controller" => "help", "action" => "contactadmin")),"subject=activateAccount&uid=".$newuserid."&token=".$activateAccountToken, 81 , APPLICATION_HOST);				
				$activationMsg        = " La création de votre compte s'est produite avec succès. <br/>
				                          A présent, vous devrez l'activer pour y acceder. <br/>
				                          Pour l'activer, vous pouvez : <br/>
				                          <ul>
				                               <li>  Cliquer directement  sur le lien : <a href=\"".$linkActivateWithToken."\">Activer mon compte</a> </li>
				                               <li>  Ou, copier cette clé <b> ".$activateAccountToken." </b>: 
				                                     et  la coller dans le formulaire de l'adresse suivante :<a href=\"".$linkSimpleActivate."\"> Formulaire d'activation de mon compte</a> </li>	
				                               <li>  En cas d'incompréhensions ou de difficultés, <a href=\"".$linkContact."\"> Contactez-nous pour aide </a>			                          
				                          </ul>
				                          <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
				$msgSubject     = "Activation de votre compte d'accès sur la plateforme SIRAH";								
				$msgPartialData = array("subject"        =>  $msgSubject,
						                "message"        =>  $activationMsg,
						                "logoMsg"        =>  APPLICATION_STRUCTURE_LOGO,
						                "replyToEmail"   =>  $defaultToEmail,
						                "replyToName"    =>  $defaultToName,
						                "replyToTel"     =>  "",
						                "replyToSiteWeb" =>  "https://www.siraah.net/about",
						                "toName"         =>  sprintf("%s %s", $newUser->lastname, $newUser->firstname),
						                "toEmail"        =>  $newUser->email );
				$msgBody        = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
				$mailer->setFrom( $defaultToEmail , "SIRAH");
				$mailer->setSubject( $msgSubject );
				$mailer->addTo( $newUser->email , stripslashes( $newUser->lastname ) );
				$mailer->setBodyHtml( $msgBody );				
				try{
					$mailer->send();
				} catch(Exception $e) {
					//On supprime l'utilisateur car son email est invalide
					$userTable = $newUser->getTable();
					$userAuth  = $newUser->getAuth();					
					if( $userAuth->hasIdentity() ) {
						$userAuth->clearIdentity();
						Zend_Session::forgetMe();
					}
					$newUser->delete();
					$errorMessages[]     = " Nous avons tenté de vous transmettre un email d'activation de votre compte sans succès, 
					                         votre compte de messagerie semble inaccessible, vérifiez votre connexion internet et reprenez l'opération.";
					if( APPLICATION_DEBUG ) {
						$errorMessages[] = " Informations de débogages : ".$e->getMessage();
					}
					$guest               = Sirah_Fabric::getUser(0);
					$this->view->user    = $guest;
				}
				if(!empty(   $errorMessages )  ) {
					foreach( $errorMessages as $errorMessage ) {
						     $this->getHelper("Message")->addMessage( $errorMessage , "message" );
					}
				}  else {
					//On lui assigne le role par defaut des utilisateurs
					Sirah_User_Acl_Table::assignRoleToUser( $newUser->userid , APPLICATION_DEFAULT_USERS_ROLENAME );
					$lastSessionId    = Zend_Session::getId();
					$userTable        = $newUser->getTable();
					$userTable->setLastSessionId( $lastSessionId );
					
					//On stocke les données d'activation dans la session
					$activateSession          = new Zend_Session_Namespace("Activation");
					$activateSession->code    = $activateAccountToken;
					$activateSession->userid  = $newUser->userid;
					$activateSession->setExpirationSeconds(86400);
					
					$cacheManager   = Sirah_Fabric::getCachemanager();
					if( $cacheManager->hasCache("Acl")){
						$aclCache  = $cacheManager->getCache("Acl");
						$aclCache->save("isAllowed","aclAllowed_".$newUser->userid."_account_activate");
					}
					if (!$cacheManager->hasCache("NavigationCache")) {
						$navigationCache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
						$cacheManager->setCache("NavigationCache", $navigationCache );
					} else {
						$navigationCache = $cacheManager->getCache("NavigationCache" );
					}	
					$navigationCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					$successMsg = sprintf("Merci Mr/Mrs %s %s, votre compte a été créé avec succès, veuillez vérifier votre boite email et vos spams afin d'activer le compte (dans tout au plus 24H) ",$newUser->lastname,$newUser->firstname);
					
					if( null==$registerDoneUri || !Zend_Uri::check( $registerDoneUri ) ) {
						$registerDoneUri  = $linkSimpleActivate;
					}
					$this->setRedirect( $successMsg ,"success");
					$this->redirect(    $registerDoneUri);					
				}
			}
		}		
		//On crée un jeton de connexion
		if(!isset($defaultSession->token)){
			$defaultSession->token    = Sirah_User_Helper::getToken(15).time();
		}		
		$this->view->title            = "INSCRIPTION : Créer mon compte";
		$this->view->data             = $defaultData;
		$this->view->token            = $defaultSession->token;
		$this->view->registerDoneUri  = $registerDoneUri;
		
		$this->render("register");
	}
	
	
	/**
	 * L'action qui permet à l'utilisateur courant
	 * 
	 * de mettre � jour son profil
	 *
	 *
	 */
	public function editAction()
	{
		$userData              = array();
		$errorMessages         = array();
		$me                    = Sirah_Fabric::getUser();
		$doneUri               = $this->_getParam("doneUri", $this->_getParam("continue", null ) );
					
		if(!$me->isLoggedIn()){
			$me->logout();
			$this->redirect("admin/account/login");
		}		
		$userTable              = $me->getTable();
		$prefixName             = $userTable->info("namePrefix");
		$userData               = $viewData = $userTable->getData();
        $dbAdapter	            = $userTable->getAdapter();	
		$defaultSession         = new Zend_Session_Namespace("accountedit");
		if(!$defaultSession->initialised){
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}
		if( null== $doneUri ) {
			$doneUri = Sirah_Functions::url($this->view->url(array("controller" => "account", "action" => "settings")),"", 81 , APPLICATION_HOST);
		}		
		if( $this->_request->isPost() ) {			
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if(!isset($defaultSession->token) || ($this->_getParam("t","") != $defaultSession->token) ){
				$defaultSession->token = Sirah_User_Helper::getToken(15).time();
				$urlDone          = Sirah_Functions::url($this->view->url(array("controller" => "account", "action" => "edit")),"t=".$defaultSession->token, 81 , APPLICATION_HOST);
				$urlSecurityCheck = $this->view->url(array("controller" => "securitycheck", "action"  => "captcha", "done" => $urlDone));
				if( $this->_request->isXmlHttpRequest()){
					$this->_helper->viewRenderer->setNoRender( true );
					echo ZendX_JQuery::encodeJson(array("error" => "Formulaire Invalide", "reload" => true, "reloadurl" => $urlSecurityCheck));
					exit;
				}
				$this->setRedirect("Formulaire invalide","error");
				$this->redirect($urlSecurityCheck);
			}			
			$postData           = $this->_request->getPost();
			$postData           = $this->_request->getPost();
			$update_data        = array_merge( $viewData , $postData );			 
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
		
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$nameValidator               = new Validator_Name();
			$usernameValidator           = new Sirah_Validateur_Username();
			$emailValidator              = new Sirah_Validateur_Email();
			
			$update_data["email"]        = $stringFilter->filter( $update_data["email"]   );
			$update_data["username"]     = $stringFilter->filter( $update_data["username"]);
			 
			if(!$strNotEmptyValidator->isValid($update_data["firstname"])  ){
				$errorMessages[]         = " Votre prénom saisi est invalide ";
			} else {
				$update_data["firstname"]= $stringFilter->filter( $update_data["firstname"] );
			}			 
			if(!$strNotEmptyValidator->isValid($update_data["lastname"])  ) {
				$errorMessages[]         = " Votre nom saisi est invalide ";
			} else {
				$update_data["lastname"] = $stringFilter->filter( $update_data["lastname"] );
			}
			if( $update_data["username"] == $me->username ) {
				$newUsername             = preg_replace('/\s+/', '.', strtolower(  $update_data["lastname"]." ". $update_data["firstname"] ) );
				$countUsernameSelect     = $dbAdapter->select()->from( $prefixName . "system_users_account")->where("username = ?" , $newUsername );
				$countUsername           = intval( count( $dbAdapter->fetchAll( $countUsernameSelect ) ) );
				$countVal                = $countUsername + 1;
				$update_data["username"] = ( $countUsername  ) ? $newUsername.".".$countVal : $newUsername;
			} else {
				$update_data["username"] = $stringFilter->filter(  $update_data["username"] );
			}			 
		    if( ( !$userTable->checkUsername( $update_data["username"] ) && ( $me->username != $update_data["username"] ) ) ) {
				$errorMessages[]         = " Le nom d'utilisateur ".$update_data["username"]." n'est pas valide ou peut etre associé à un second compte ";
			}	 					
			if(!$userTable->checkEmail($update_data["email"])){
				$errorMessages[]         = " L'adresse email ".$update_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
			}							
			$update_data["lastUpdatedDate"] = time();
			$update_data["updateduserid"]   = $me->userid;
			$update_data["country"]         = $stringFilter->filter($update_data["country"]);			 
			$viewData                       = $update_data;
			
			if( isset( $update_data["password"] ) ) {
				unset( $update_data["password"] );
			}			 
			if(empty($errorMessages)){
				$me->clearMessages();
				if(!$me->save($update_data)){
				    $saveErrors  = $me->getMessages();
					foreach($saveErrors as $type => $msg){
						$msg              = (is_array($msg)) ? array_shift($msg)  : $msg;
						$errorMessages[]  = $msg;
					}
				}
			}
			if(!empty($errorMessages)){
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("error" => implode(" ; " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $errorMessage ) {
					$this->getHelper("Message")->addMessage( $errorMessage , "message");
				}
				$this->redirect("admin/account/settings");
			}  else {	
				if( $this->_request->isXmlHttpRequest() ) {
					echo ZendX_JQuery::encodeJson(array("success" => "La mise à jour de vos informations, s'est produite avec succès" ));
					exit;
				}	
				$this->setRedirect("La mise à jour de vos informations, s'est produite avec succès","success");
				$this->redirect( $doneUri );
			}
		}		
		//On crée un jeton de connexion
		if(!isset($defaultSession->token)){
			$defaultSession->token = Sirah_User_Helper::getToken(15).time();
		}		
		$this->view->data    = $viewData;
		$this->view->title   = "Mise à jour des informations de mon compte ";	
		$this->view->token   = $defaultSession->token;
		$this->view->doneUri = $doneUri;
		
		$this->render("modification");
	}
	
	
	/**
	 * L'action qui permet à l'utilisateur courant
	 *
	 * de mettre à jour son avatar
	 *
	 *
	 */
	public function changeavatarAction()
	{		
		$me                = Sirah_Fabric::getUser();
		$errorMessages     = array();
		
		if(!$me->isLoggedIn()){
			$me->logout();
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération, il faudra vous connecter","error");
			$this->redirect("admin/account/login");
		}		
		if( $this->_request->isPost()){				
			$avatarUpload = new Zend_File_Transfer();
				
			//On inclut les différents validateurs de l'avatar
			$avatarUpload->addValidator('Count',false,1);
			$avatarUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
			$avatarUpload->addValidator("FilesSize",false,array("max"       => "10MB"));
			$avatarUpload->addValidator("ImageSize",false,array("minwidth"  => 10,
					                                            "maxwidth"  => 800,
					                                            "minheight" => 10,
					                                            "maxheight" => 600));
				
			$avatarExtension = Sirah_Filesystem::getFilextension($avatarUpload->getFileName('avatar'));
			//On inclut les différents filtres de l'avatar
			$avatarUpload->addFilter("Rename",array("target" => USER_AVATAR_PATH . DS .$me->logintoken . "Avatar.".$avatarExtension,"overwrite" => true),"avatar");
		
			//On upload l'avatar de l'utilisateur
			if( $avatarUpload->isUploaded("avatar")){
				$avatarUpload->receive("avatar");
			} else {
				$errorMessages[]  = "L'avatar fourni n'est pas valide";
			}				
			if($avatarUpload->isReceived("avatar")){
				$newAvatarFilename  = USER_AVATAR_PATH.DS.$me->logintoken."Avatar.".$avatarExtension;
		
				//on supprime l'avatar existant de l'utilisateur
				if((null!==$me->avatar) && !empty($me->avatar) && Sirah_Filesystem::exists($me->avatar) && ($me->avatar != $newAvatarFilename)){
					Sirah_Filesystem::remove($me->avatar);
				}
				//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
				$avatarImage  = Sirah_Filesystem_File::fabric("Image",$newAvatarFilename,"ab");		
				$thumbAvatar  = $avatarImage->resize("180px");		
				$thumbAvatar->copy(USER_AVATAR_PATH . DS . "thumbnails" , true);
		
				if(!$me->save(array( "avatar"  => $newAvatarFilename))){
					$errorMessages[]   = " Votre avatar n'a pas été mis à jour " ;
				}		
			} else {
				$errorMessages   = $avatarUpload->getMessages();
				array_unshift($errorMessages,"L'avatar fourni n'est pas valide pour les raisons suivantes : ");
			}				
			if(!empty($errorMessages)){
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}  else {
				$this->setRedirect("Votre avatar a été mis à jour avec succès","success");
				$this->redirect("admin/dashboard/list");
			}
		}
		$this->view->title   = "Mettre à jour mon avatar";
		$this->view->userid  = $me->userid;
		$this->view->avatar  = $me->avatar;
	
	}
		
	/**
	 * L'action qui permet de réinitialiser le processus d'activation
	 * de mon compte
	 *
	 *
	 */
	public function reinitactivationAction()
	{
		$guest                  = Sirah_Fabric::getUser();
		$token                  = $this->_getParam("token",null);
		$userActivationSession  = new Zend_Session_Namespace("Activation");
		$email                  = null;
		
		$this->setLayout("login");
		
		if( $guest->isLoggedIn() && $guest->activated ) {
			$this->setRedirect("Votre compte est déjà activé","message");
			$this->redirect("admin/account/settings");
		}		
		if(!isset($userActivationSession->token) || ($userActivationSession->token != $token) ) {
			$this->setRedirect("Impossible d'effectuer cette action, des paramètres supplementaires sont nécessaires","error");
			$this->redirect("admin/account/login");			
		}		
		if( $this->_request->isPost() || $this->_hasParam("email")){			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$email      = $stringFilter->filter($this->_getParam("email",null));
			$userTable  = $guest->getTable();			
			
			if($row = $userTable->find(array("email" => $email))){				
				$userid                  = $row["userid"];
				$user                    = Sirah_Fabric::getUser($userid);
				$config                  = Sirah_Fabric::getConfig();
				$mailer                  = Sirah_Fabric::getMailer();
				
				$activateAccountToken    = Sirah_User_Helper::getToken(10) . time() . $userid;
				$activateSession->code   = $activateAccountToken;
				$activateSession->userid = $userid;		
				
				$linkActivateWithToken   = Sirah_Functions::url($this->view->url(array("controller" => "account", "action"  => "activate")),"token=".$activateAccountToken."&uid=".$userid, 81 , APPLICATION_HOST);
				$linkSimpleActivate      = Sirah_Functions::url($this->view->url(array("controller" => "account", "action"  => "activate")),"uid=".$userid, 81 , APPLICATION_HOST);
				$linkContact             = Sirah_Functions::url($this->view->url(array("controller" => "help", "action"  => "contactadmin")),"subject=activateAccount&uid=".$userid."&token=".$activateAccountToken, 81 , APPLICATION_HOST);
				
				$activationMsg           = " 
				                            Pour activer votre compte, vous pouvez : <br/>
				                               <ul>
				                                   <li> Cliquer directement  sur le lien : <a href=\"".$linkActivateWithToken."\">Activer mon compte</a> </li>
				                                   <li>  Ou copier cette clé <b> ".$activateAccountToken." </b>:
				                                             et copier-la dans le formulaire de l'adresse suivante :<a href=\"".$linkSimpleActivate."\">Formulaire d'activation de mon compte</a> </li>
				                                   <li>  En cas d'incompréhensions ou de difficultés, <a href=\"".$linkContact."\"> Contactez-nous pour aide </a>
				                               </ul>";
				
				$defaultToEmail = $config["resources"]["mail"]["defaultFrom"]["email"];
				$defaultToName  = $config["resources"]["mail"]["defaultFrom"]["name"];
				
				$mailer->setFrom($defaultToEmail,stripslashes($defaultToName));
				$mailer->setSubject("Activation de votre compte");
				$mailer->addTo( $user->email , stripslashes($user->lastname. " ".$user->firstname) );
				$mailer->setBodyHtml($activationMsg);
				
				try{
					$mailer->send();
				} catch(Exception $e) {
					$this->setRedirect(" Nous avons tenté de vous transmettre un email d'activation de votre compte sans succès, votre compte de messgarie semble inaccessible, vérifiez votre connexion internet et réessayer.","error");
					$this->redirect("admin/account/reinitactivation/token/".$token);
				}				
				$activateSession->setExpirationSeconds(86400);
				
				$userTable       = $user->getTable();
				$lastSessionId   = Zend_Session::getId();
				$userTable       = $user->getTable();
				$userTable->setLastSessionId($lastSessionId);
				
				$this->setRedirect("Nous avons transmis un message d'activation de votre compte dans l'adresse de messagerie $email, veuillez vérifier et suivre les instructions","success");
				$this->redirect("admin/account/login");
			}
			
			$this->getHelper("Message")->addMessage(" Impossible d'effectuer l'opération, vous avez entré un email invalide ");	
			$token  = Sirah_User_Helper::getToken(15).time();
			$userActivationSession->setExpirationSeconds(time()+(60*60*60));
		}		
		$this->view->token   = $token;
		$this->view->email   = $email;		
		$this->render("emailactivation");
	}
	
	/**
	 * L'action qui permet d'activer mon compte
	 *
	 *
	 */
	public function activateAction()
	{
		$uid    = $this->_getParam("uid",null);
		$token  = $this->_getParam("token",null);
		$user   = Sirah_Fabric::getUser($uid);
		
		$this->_helper->layout->setLayout("login");
		
		if( $user->isLoggedIn() && $user->activated ) {
			$this->setRedirect("Votre compte est déjà activé","message");
			$this->redirect("admin/account/settings");
		}						
		if( null!==$token  ) {
			$userActivationSession  = new Zend_Session_Namespace("Activation");	
			$newToken               = Sirah_User_Helper::getToken(15) . time();			
			if(!isset($userActivationSession->code) && !$user->isGuest()){
				$sessionData                   = $user->getSessionData();
				$userActivationSession->code   = isset($sessionData["Activation"]["code"]) ? $sessionData["Activation"]["code"] : null;
				$userActivationSession->userid = $uid;
			}			
			if(($userActivationSession->code != $token)){
				$userActivationSession->token  = $newToken;
				$userActivationSession->setExpirationSeconds(86400);
				$this->authorize("reinitactivation");
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"     => "Impossible d'effectuer cette action, des paramètres supplementaires sont nécessaires",
							                            "reload"    => true,
							                            "reloadUrl" => $this->view->url(array("controller" => "account", "action" => "reinitactivation", "token" => $newToken ))));
					exit;
				}
				$this->setRedirect("La clé d'activation de votre compte semble expirée, veuillez reprendre la procédure en indiquant votre email","message");
				$this->redirect("admin/account/reinitactivation/token/".$newToken);
			}
			$userid  = $userActivationSession->userid;
			$me      = Sirah_Fabric::getUser($userid);			
			if($me->enable()){
				$this->setRedirect("Votre compte est activé avec succès, vous pouvez vous authentifier pour y accéder","success");
				$this->redirect("admin/account/login");
			} else {
				$userActivationSession->setExpirationSeconds(86400);
				$userActivationSession->token  = $newToken;
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"     => "L'activation de votre compte a echoué, veuillez reprendre l'opération",
							                            "reload"    => true,
							                            "reloadUrl" => $this->view->url(array("controller" => "account", "action" => "reinitactivation", "token" => $newToken ))));
					exit;
				}
				$this->setRedirect("L'activation de votre compte a echoué, veuillez reprendre l'opération","error");
				$this->redirect("admin/account/reinitactivation/token/".$newToken);
			}			
			$userTable       = $me->getTable();
			$lastSessionId   = Zend_Session::getId();
			$userTable       = $me->getTable();
			$userTable->setLastSessionId($lastSessionId);
		}	
		$this->view->title   = "Activation de mon compte";
		$this->render("activation");	
	}	
}