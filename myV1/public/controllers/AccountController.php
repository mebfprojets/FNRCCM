<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Frameworkf
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
require 'vendor/autoload.php';

use \ReCaptcha\ReCaptcha as gReCaptcha;
use \ReCaptcha\RequestMethod\CurlPost as gReCaptchaMethodPost;

class AccountController extends Sirah_Controller_Default
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
		$pwdSession        = new Zend_Session_Namespace("lostpassword");
		$userTable         = $me->getTable();			
		if(!$me->isLoggedIn()){
			$me->logout();
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération" , "error");
			$this->redirect("public/account/login");
		}		
		$userData           = $userTable->getData();
		$userRoles          = Sirah_User_Acl_Table::getRoles($me->userid);		
		if( $this->_request->isPost() ) {			
			$postData       = $this->_request->getPost();
			$filteredParams = array();
			$stringFilter   = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());			
			if( count($postData ) ) {
				foreach( $postData as $paramKey => $paramValue ) {
					     $filteredParams[$paramKey]   = $stringFilter->filter($paramValue);
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
		$this->view->title    = "Les paramètres de  mon compte";
		$this->view->user     = $me;
		$this->view->roles    = $userRoles;
		$this->view->params   = $me->getParams();	
		$this->view->pwdtoken = $pwdSession->lostpwdtoken = Sirah_User_Helper::getToken(15) . time();
	}
	
	public function changepwdAction()
	{
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$pwdSession               = new Zend_Session_Namespace("lostpassword");
		//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
		if(!isset( $pwdSession->lostpwdtoken ) || !$this->_hasParam( $pwdSession->lostpwdtoken ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Formulaire invalide"));
				exit;
			}
			die("Formulaire Invalide");
		}			
		$postData              = $this->_request->getParams();
		$me                    = Sirah_Fabric::getUser();
		$userTable             = $me->getTable();
		$dbAdapter             = $userTable->getAdapter();
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$passwordSelect        = $dbAdapter->select()->from($userTable->info("name"), array("password"))->where("userid = ?", $me->userid );
		
		$passwordRow           = $dbAdapter->fetchRow( $passwordSelect);			
		$oldPwd                = ( isset($postData["password"]))     ? trim( $postData["password"] )     : "";
		$newPwd                = ( isset($postData["newpwd"]  ))     ? trim( $postData["newpwd"] )       : "";
		$confirmation          = ( isset($postData["newpwdconfirm"]))? trim( $postData["newpwdconfirm"]) : "";
		
		if(!Sirah_User_Helper::verifyPassword( $oldPwd , $passwordRow["password"] ) ) {
			$errorMessages[]   = "Votre ancien mot de passe que vous avez saisi n'est pas valide";
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
		if( count(  $errorMessages )  ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage){
				$this->getHelper("Message")->addMessage($errorMessage,"error");
			}
			$errorMsg    = implode(", ",$errorMessages );
			$this->setRedirect( $errorMsg."; veuillez réessayer " , "error");
			$this->redirect("public/account/settings");
		}  else {
			$userid       = $pwdSession->userid;
			$user         = Sirah_Fabric::getUser($userid);		
			if( $user->setPassword( $newPwd ) ) {
				$this->setRedirect(" Votre mot de passe a été mis à jour avec succès ","success");
				$this->redirect("public/account/settings");
			} else {
				$errorMsg     = " La mise à jour du mot de passe n'a pas fonctionné ";
				if( APPLICATION_DEBUG ) {
					$errorMsg .= " , pour les raisons suivantes : ". implode(" , ",$user->getMessage());
				}
				$this->setRedirect( $errorMsg."; veuillez réessayer ","error");
				$this->redirect("public/account/settings");
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

	
	public function dashboardAction()
	{
		$this->redirect("members/dashboard");
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
	    $this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
        $this->view->title  = "AUTHENTIFICATION : OUVRIR UNE SESSION";
		
		$guest              = Sirah_Fabric::getUser();		
		if( $guest->isLoggedIn() ) {
			$this->redirect("public/members/dashboard");
		}	
		$viewData           = array("username"=>null,"password"=>null,"rememberme" => 0);
		$errorMessages      = array();
		$captchaSession     = new Zend_Session_Namespace("captchas");		
		$csrfTokenId        = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue     = $this->_helper->csrf->getToken(300);
		$csrfFormNames      = $this->_helper->csrf->getFormNames(array("username","password") , false );
		$captcha            = null;
		$authSuccess        = false;
		$nextUri            = $this->_getParam("next", $this->_getParam("continue", $this->_getParam("returnTo", "" ) ) );

		//Si la vérification du captcha a echoué au moins dix fois, on bloque l'accès à la page pendant une heure
		if( isset( $captchaSession->checksAuth )  && ( $captchaSession->checksAuth >= NB_CHECK_AUTHS_CAPTCHA ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson(array( "error" => "Vous ne pourrez plus exécuter cette action pendant au moins une heure" ));
				exit;
			}
			$this->setRedirect("Vous ne pourrez plus exécuter cette action pendant au moins une heure", "error");
			$this->redirect("public/index/index");
		}
		//On active le captcha si c'est nécessaire
		if( isset( $captchaSession->showcaptcha ) && ( true == $captchaSession->showcaptcha  )) {
			$imgDir               = realpath( DOCUMENTS_PATH  ."/../../myTpl/public/images/captchas/")	;
			$imgUrl               = ROOT_PATH ."/myTpl/public/images/captchas/";
			$captcha              = $this->_createCaptcha( 8 , "trebucit.ttf" , $imgDir , $imgUrl );
			$this->view->title    = "VERIFICATION SECURITE : <div style=\"font-size:80%;position:relative;\">Nous voulons vérifier que vous n'etes pas un robot </div>";
		}		 
	    //Quand les données sont postées
		if( $this->_request->isPost() ) {							
			//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
			if( $this->_helper->csrf->isValid() ) {				
				$stringFilter     =      new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
					
				$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer" ,"zero" ,"string" ,"float" ,"empty_array" ,"null"));				
				if(!$captchaSession->initialised ) {
					$captchaSession->initialised = true;					
					$captchaSession->showcaptcha = false;
					$captchaSession->checksAuth  = 0;
					$captchaSession->setExpirationSeconds(7200);
				}				
				$postData             = $this->_request->getPost();
				$viewData             = array_merge( $viewData, $postData );
				
                $gReCaptchaResponse	  = (isset($postData["g-recaptcha-response"]))? $postData["g-recaptcha-response"]            : null;	
				$identity             = (isset($postData["username"]            ))? $stringFilter->filter($postData["username"]) : null;
				$credential           = (isset($postData["password"]            ))? $postData["password"]                        : null;
				$rememberMe           = (isset($postData["rememberme"]          ))? intval($postData["rememberme"])              : 1;
				/*if(!$strNotEmptyValidator->isValid($gReCaptchaResponse) && !$captchaSession->showcaptcha) {
					$errorMessages[]  =  "Veuillez valider le captcha de sécurité";
				} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse) && !$captchaSession->showcaptcha ) {
					$recaptcha        = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
					$recaptchaResponse= $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
					if(!$recaptchaResponse->isSuccess()) {
						$errorMessages[] = "Captcha Invalide !";
					}
				}*/
				if(!$strNotEmptyValidator->isValid($identity) && ( !$captchaSession->showcaptcha ) ) {
					$errorMessages[]  = "Entrez un identifiant valide";
				}
				if(!$strNotEmptyValidator->isValid( $credential ) && ( !$captchaSession->showcaptcha )) {
					$errorMessages[]  = "Entrez un mot de passe valide";
				}								
				//On vérifie le captcha
				if(( null!==$captcha ) && ( false!==$captcha ) && ( true == $captchaSession->showcaptcha ) ) {
					$captchainput            = (isset($postData["captcha"]    )) ? $postData["captcha"] : "";
					$captchatime             = (isset($postData["captchatime"])) ? $postData["captchatime"]:0;
					if(!$captcha->isValid( $captchainput ) ) {
						$captchaMessages             = $captcha->getMessages();
						$errorMessages[]             = "La vérification du chiffre qui apparait dans l'image a echoué";
						if( APPLICATION_DEBUG ) {
							$errorMessages[]         = (is_array($captchaMessages)) ? implode(" ", $captchaMessages) : $captchaMessages;
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
				if( empty( $errorMessages ) && $strNotEmptyValidator->isValid($identity) && $strNotEmptyValidator->isValid($credential)  ) {
					$guest->clearMessages();
					$loggedInUser = $guest->login( $identity , $credential , $rememberMe);
					$captchaSession->checksAuth++;
					if((false !== $loggedInUser) && (null!==$loggedInUser)){
						$authSuccess           = true;
					} else {
						$errorMessages         = array_shift($guest->getMessages());
						$authSuccess           = false;						
					}
				}
				if(count(    $errorMessages ) ) {									
					//Après trois tentatives d'authentification échouées, vérifier qu'on n'a pas affaire à un robot à travers un captcha.
					if(!isset( $captchaSession->checksAuth    ) ) {
						$captchaSession->checksAuth = 0;
					} elseif ( $captchaSession->checksAuth >= NB_CHECK_AUTHS ) {
						$captchaSession->showcaptcha= true;
						if( null==$captcha ) {
							$captcha = $this->_createCaptcha( 8, "trebucit.ttf", realpath(DOCUMENTS_PATH."/../../myTpl/public/images/captchas/"), (ROOT_PATH."/myTpl/public/images/captchas/"));
						}
					} 					
				}
				if( $authSuccess )  {
					$front                 = Zend_Controller_Front::getInstance();
					$defaultControllerName = "members";
					$defaultActionName     = "dashboard";
					$cacheManager          = Sirah_Fabric::getCachemanager();
					$successMsg            = sprintf("Bienvenue Mr/Mrs %s %s dans votre compte", $loggedInUser->lastname, $loggedInUser->firstname );
					 			
					if( $cacheManager->hasCache("Acl")){
						$aclCache   = $cacheManager->getCache("Acl");
						$aclCache->save("isAllowed", "aclAllowed_".$loggedInUser->userid."_account_init");
						$aclCache->save("isAllowed", "aclAllowed_".$loggedInUser->userid."_account_index");
					}
					if(!$cacheManager->hasCache("NavigationCache")) {
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
						$application    = new Zend_Session_Namespace("erccmapp");
                        $returnToUrl    = $this->view->url(array("module"=>"public","controller"=>$defaultControllerName,"action"=>$defaultActionName,"module"=>"public"));						
                        if( isset($application->returnToUrl) && !empty($application->returnToUrl)) {
							$returnToUrl= $application->returnToUrl;
						}							
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender( true );
							$this->_helper->layout->disableLayout(     true );
							$this->getHelper("Message")->addMessage( $successMsg ,"success");
							echo ZendX_JQuery::encodeJson(array("reload"=>true,"newurl"=> $returnToUrl));
							exit;
						}
						$this->setRedirect( $successMsg , "success");
						$this->redirect($returnToUrl);
					}					
				} 
			} else {
			   if(  $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender( true );
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres de securité rattachés au formulaire doivent etre expirés. Veuillez re-actuliser la page", "reload" => true ));
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
			die("Impossible de déconnecter cette personne de sa session");
		}	
		$loggedInUser->logout();
		$this->redirect("public/account/login");	
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
	    $this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$me                = Sirah_Fabric::getUser();
		$errorMessages     = array();
		
		if(!$me->isLoggedIn()){
			$me->logout();
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération, il faudra vous connecter","error");
			$this->redirect("public/account/login");
		}		
		if( $this->_request->isPost()){				
			$avatarUpload = new Zend_File_Transfer();
				
			//On inclut les différents validateurs de l'avatar
			$avatarUpload->addValidator('Count',false,1);
			$avatarUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
			$avatarUpload->addValidator("FilesSize",false,array("max"=> "3MB"));
			$avatarUpload->addValidator("ImageSize",false,array("minwidth"=>10,"maxwidth"=>800,"minheight"=>10,"maxheight"=>600));
				
			$avatarExtension = Sirah_Filesystem::getFilextension($avatarUpload->getFileName('avatar'));
			//On inclut les différents filtres de l'avatar
			$avatarUpload->addFilter("Rename",array("target"=>USER_AVATAR_PATH. DS .$me->logintoken . "Avatar.".$avatarExtension,"overwrite" => true),"avatar");
		
			//On upload l'avatar de l'utilisateur
			if( $avatarUpload->isUploaded("avatar")){
				$avatarUpload->receive("avatar");
			} else {
				$errorMessages[]  = "L'avatar fourni n'est pas valide";
			}				
			if( $avatarUpload->isReceived("avatar")){
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
			if(!empty(   $errorMessages)){
				foreach( $errorMessages as $errorMessage){
					     $this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}  else {
				$this->setRedirect("Votre avatar a été mis à jour avec succès","success");
				$this->redirect("public/members/dashboard");
			}
		}
		$this->view->title   = "Mettre à jour mon avatar";
		$this->view->userid  = $me->userid;
		$this->view->avatar  = $me->avatar;	
	}

	/**
	 * L'action qui permet à un utilisateur de créer son compte.
	 *
	 *
	 */
	public function createAction()
	{		
	    $this->redirect("public/members/register");
	    $this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		//On initialise les variables nécessaires
		$user            = $me = Sirah_Fabric::getUser();
		$registerDoneUri = $this->_getParam("doneUri", null );
		$errorMessages   = array();
		
		//S'il n'est pas invité, on lui refuse cette opération
		if(!$user->isGuest()){
			$this->setRedirect("Vous n'etes pas autorisé à effectuer cette opération","error");
			$this->redirect("index/index");
		}		
		$defaultData        = array("firstname"=>null,"lastname"=>null,"phone1"=>null,"phone2"=>null,"address"=>null,"zipaddress"=>"","city"=>"","country"=>Sirah_User_Helper::getCountry(),
				                    "birthday"=>"","birthaddress"=>null,"username"=>null,"password"=>null,"email"=>null,"sexe"=>null,"expired"=>0,"socialstate"=>null,
				                    "activated"=> 1,"blocked"=>0,"locked"=>0,"accesstoken"=>"","logintoken"=>"","params"=>"","admin"=>0,
				                    "creatoruserid"=>$me->userid,"updateduserid"=>0,"registeredDate"=> time(),"lastConnectedDate" => 0,
				                    "lastUpdatedDate"=>0,"lastIpAddress"=>"","lastHttpClient"=> "","lastSessionId"=> "" );		
		$csrfFormNamesArray = array_merge(array_keys($defaultData), array("confirmedpassword","token","birthday","birthaddress"));
		$csrfTokenId        = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue     = $this->_helper->csrf->getToken(500);
		$csrfFormNames      = $this->_helper->csrf->getFormNames($csrfFormNamesArray, false);
		$defaultSession     = new Zend_Session_Namespace("inscription");
		if(!$defaultSession->initialised ) {
			$defaultSession->initialised = true;
			$defaultSession->setExpirationSeconds(86400);
		}		
		if( $this->_request->isPost() && isset( $_POST[$csrfFormNames["firstname"]])){   	
            if( $this->_helper->csrf->isValid() ) {
				//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
				if(!isset($defaultSession->token) || ($this->_getParam($csrfTokenId,"") != $defaultSession->token) ) {
					$defaultSession->token = $csrfTokenValue;
					$defaultSession        = new Zend_Session_Namespace("captchacheck");
					$defaultSession->checks= 0;
					$urlDone               = $this->_helper->HttpUri(array("module"=>"public","controller"=>"account","action"=> "create"));
					$urlSecurityCheck      = $this->_helper->HttpUri(array("module"=>"public","controller"=>"securitycheck","action"=>"captcha","params"=> array("done"=>$urlDone,"token"=>$defaultSession->token )));				
					if( $this->_request->isXmlHttpRequest() ) {
						echo ZendX_JQuery::encodeJson(array("error"=>"Formulaire Invalide", "reload" => true, "newurl" => $urlSecurityCheck));
						exit;
					}
					$this->setRedirect("Formulaire invalide","error");
					$this->redirect($urlSecurityCheck);
				}			
				$postData           = $viewData = $this->_request->getPost();
				$insert_data        = $defaultData ;
				if( count(   $postData) ) {
					$formInputKeys  = array_flip($csrfFormNames);
					foreach( $postData as $inputName=>$inputValue) {
						     if( array_key_exists($inputName,$formInputKeys)) {
								 $decodedInputName               = $formInputKeys[$inputName];
								 $insert_data[$decodedInputName] = $viewData[$decodedInputName] = $postData[$decodedInputName] = $inputValue;
							 }
					}
				}
				//print_r($insert_data); print_r($csrfFormNames);die();
				$newuserid          = 0;
				$userTable          = $user->getTable();
				$dbAdapter          = $userTable->getAdapter();
				$prefixName         = $userTable->info("namePrefix");			
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter       =    new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				
				//On crée les validateurs nécessaires
				$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
				$nameValidator                 = new Validator_Name();						
				$emailValidator                = new Sirah_Validateur_Email();
				$membernameValidator           = new Sirah_Validateur_Membername();
				/*
				$gReCaptchaResponse	           = (isset($postData["g-recaptcha-response"]))? $postData["g-recaptcha-response"] : null;	
				if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
					$errorMessages[]           =  "Veuillez valider le captcha de sécurité";
				} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse)) {
					$recaptcha                 = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
					$recaptchaResponse         = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
					if(!$recaptchaResponse->isSuccess()) {
						$errorMessages[]       = "Captcha Invalide !";
					}
				}*/
				 
				$gReCaptchaResponse	           = (isset($_POST['g-recaptcha-response']))? $_POST['g-recaptcha-response']        : null;
				if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
					$errorMessages[]           =  "Veuillez valider le captcha de sécurité";
				} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse) ) {
					$recaptcha                 = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
					$recaptchaResponse         = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
					if(!$recaptchaResponse->isSuccess()) {
						$errorMessages[]       = "Captcha Invalide !";
					}
				}
				$insert_data["username"]       = $stringFilter->filter($insert_data["username"]);
				$insert_data["email"]          = $stringFilter->filter($insert_data["email"]);
				
				if(!$strNotEmptyValidator->isValid( $insert_data["firstname"] ) || !$membernameValidator->isValid($insert_data["firstname"])) {
					$errorMessages[]           = " Le prénom que vous avez saisi, est invalide ";
				} else {
					$insert_data["firstname"]  = $stringFilter->filter($insert_data["firstname"]);
				}
				if(!$strNotEmptyValidator->isValid($insert_data["lastname"])  || !$membernameValidator->isValid($insert_data["lastname"])) {
					$errorMessages[]           = " Le nom de famille que vous avez saisi, est invalide ";
				} else {
					$insert_data["lastname"]   = $stringFilter->filter($insert_data["lastname"]);
				}
				if(!$strNotEmptyValidator->isValid( $insert_data["country"] ) ) {
					$insert_data["country"]    = " ";
				} else {
					$insert_data["country"]    = $stringFilter->filter($insert_data["country"]);
				}
				if(!$strNotEmptyValidator->isValid($insert_data["phone1"])){
					$insert_data["phone1"]     = " ";
				} else {
					$insert_data["phone1"]     = $stringFilter->filter( $insert_data["phone1"] );
				}
                $birthdayDate                  = (isset($postData["birthday"] ))?$postData["birthday"]                         : "" ;
				if( Zend_Date::isDate($birthdayDate, "dd/MM/YYYY")) {				
					$zendBirthday              = new Zend_Date($birthdayDate, "dd/MM/YYYY");
					$birthdayTms               = ($zendBirthday) ? $zendBirthday->get(Zend_Date::TIMESTAMP)  : 0;				
				} else {
					$zendBirthday              =  new Zend_Date(array("year" =>(isset($postData["birthday_year"] ))? intval($postData["birthday_year"]) : 0,
																	  "month"=>(isset($postData["birthday_month"]))? intval($postData["birthday_month"]): 0,
																	  "day"  =>(isset($postData["birthday_day"]  ))? intval($postData["birthday_day"])  : 0 ) );
					$birthdayTms               = ($zendBirthday) ? $zendBirthday->get(Zend_Date::TIMESTAMP) : 0;							
				}	
				if(!intval($birthdayTms) ) {
					$errorMessages[]           = "Veuillez renseigner une date de naissance valide";
				} else {
					$insert_data["birthday"]   = date("Y-m-d",$birthdayTms);
				}				
				if(!$userTable->checkEmail( $insert_data["email"] ) || !$emailValidator->isValid( $insert_data["email"] ) ) {
					$errorMessages[]           = " L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
				}
				if(!$strNotEmptyValidator->isValid( $insert_data["username"] ) && empty( $errorMessages ) ) {
					$newUsername               = preg_replace('/\s+/', '.', strtolower( $insert_data["lastname"]." ".$insert_data["firstname"] ) );
					$countUsernameSelect       = $dbAdapter->select()->from( $prefixName."system_users_account")->where("username=?",$newUsername );
					$countUsername             = intval( count( $dbAdapter->fetchAll( $countUsernameSelect ) ) );
					$countVal                  = $countUsername + 1;
					$insert_data["username"]   = ( $countUsername  ) ? $newUsername.".".$countVal : $newUsername;				
				} else {
					$insert_data["username"]   = $stringFilter->filter( $insert_data["username"] );
				}
				if(!$strNotEmptyValidator->isValid( $insert_data["password"] ) ) {
					$errorMessages[]           = "Entrez un mot de passe valide";
				} else {
					$insert_data["password"]   = $postData["password"];			
					if(!isset($postData["confirmedpassword"])){
						$errorMessages[]       = "Des données de création sont manquantes";
					} elseif($postData["confirmedpassword"] !== $insert_data["password"]) {
						$errorMessages[]       = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
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
				$insert_data["activated"]      = 1;
				$insert_data["blocked"]        = 0;
				$insert_data["locked"]         = 0;
				$insert_data["lastIpAddress"]  = Sirah_Functions::getIpAddress();
				$insert_data["lastHttpClient"] = Sirah_Functions::getBrowser();
				$insert_data["lastSessionId"]  = Zend_Session::getId();
					
				$defaultData                   = $insert_data;			 
				if( empty( $errorMessages ) ) {
					$user->clearMessages();
					if(!$newuserid             = $user->save($insert_data)){
						$saveErrors            = $user->getMessages("error");
						foreach( $saveErrors as $type => $msg){
								 $msg          = (is_array($msg)) ? array_shift($msg)  : $msg;
								 $errorMessages[]  = $msg;
						}
					}
				}	
				if(!empty($errorMessages)){
					$defaultData  = $postData;
					if( $this->_request->isXmlHttpRequest()){
						echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
						exit;
					}
					foreach( $errorMessages as $errorMessage){
							 $this->getHelper("Message")->addMessage($errorMessage,"error");
					}
				}  else {	
                    $accountid        = $newuserid;				
					$newUser          = $me = Sirah_Fabric::getUser($newuserid);
					//On lui assigne le role par defaut des utilisateurs
					Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_DEFAULT_USERS_ROLENAME );
					Sirah_User_Acl_Table::assignRoleToUser($accountid, "Operateur");
					$lastSessionId    = Zend_Session::getId();
					$userTable        = $me->getTable();
					$userTable->setLastSessionId( $lastSessionId );					
					 
					$successMsg       = sprintf("Merci Mr/Mrs %s %s, votre compte a été créé avec succès ",$newUser->lastname,$newUser->firstname);

					$this->setRedirect( $successMsg ,"success");
					$this->redirect("public/account/login");
				}
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La validité du formulaire semble expirée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames            = $this->_helper->csrf->getFormNames($csrfFormNamesArray, true );					
		}		
		//On crée un jeton de connexion
		$defaultSession->token        = $csrfTokenValue;		
		$this->view->title            = "INSCRIPTION : Créer mon compte";
		$this->view->data             = $defaultData;
		$this->view->token            = $csrfTokenValue;	
        $this->view->formNames        = $csrfFormNames;
		$this->view->csrfTokenId      = $csrfTokenId;
		$this->view->csrfTokenValue   = $csrfTokenValue;		
		$this->render("register");
	}
	
	
	/**
	 * L'action qui permet de réinitialiser le processus d'activation
	 * de mon compte
	 *
	 *
	 */
	public function reinitactivationAction()
	{
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$guest                  = Sirah_Fabric::getUser();
		$token                  = $this->_getParam("token",null);
		$userActivationSession  = new Zend_Session_Namespace("Activation");
		$email                  = null;
		 
		if( $guest->isLoggedIn() && $guest->activated ) {
			$this->setRedirect("Votre compte est déjà activé","message");
			$this->redirect("public/index/index");
		}		
		if(!isset($userActivationSession->token) || ($userActivationSession->token != $token) ) {
			$this->setRedirect("Impossible d'effectuer cette action, des paramètres supplementaires sont nécessaires","error");
			$this->redirect("public/account/login");			
		}	        		
		if( $this->_request->isPost() || $this->_hasParam("email")){			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
			
			$email                       = $stringFilter->filter($this->_getParam("email",null));
			$userTable                   = $guest->getTable();			
			
			if( $row = $userTable->find(array("email" => $email))){				
				$userid                  = $row["userid"];
				$user                    = $newUser = Sirah_Fabric::getUser($userid);
				$config                  = Sirah_Fabric::getConfig();
				$mailer                  = Sirah_Fabric::getMailer();
				$defaultFromEmail        = $config["resources"]["mail"]["defaultFrom"]["email"];
				$defaultFromName         = $config["resources"]["mail"]["defaultFrom"]["name"];
				
				$activateAccountToken    = hash('ripemd160',sprintf("%s:%s",Sirah_User_Helper::getToken(5),$userid));
				$activateSession->code   = $activateAccountToken;
				$activateSession->userid = $userid;		
				
				$linkActivateWithToken   = VIEW_BASE_URI."/public/account/activate/token/".$activateAccountToken."/uid/".$userid; 
				$linkSimpleActivate      = VIEW_BASE_URI."/public/account/activate/uid/".$userid; 
				$linkContact             = VIEW_BASE_URI."/public/project/contacts/uid/".$userid; 
                $msgSubject              = "Vous souhaitez activer votre compte sur la plateforme";				
				$activationMsg           = " 
				                            Pour activer votre compte, vous pouvez : <br/>
				                            <ul>
												<li> Cliquez directement sur le lien suivant : <a title=\"Ce lien va automatiquement activer votre compte sur la plateforme\" href=\"".$linkActivateWithToken."\">Activer mon compte</a> </li>
												<li> Ou copier cette clé <b> ".$activateAccountToken." </b> : et copier-la dans le formulaire accessible à l'adresse suivante : <a href=\"".$linkSimpleActivate."\">Formulaire d'activation de mon compte</a> </li>
												<li> En cas d'incompréhensions ou de difficultés, <a title=\"Contacter notre équipe pour résoudre votre soucis.\" href=\"".$linkContact."\"> Contactez-nous pour aide </a>
											</ul>";
				$msgPartialData           = array("subject"=>$msgSubject,"message"=>$activationMsg,"logoMsg"=> APPLICATION_STRUCTURE_LOGO,"replyToEmail"=>$defaultFromEmail,"replyToName"=>$defaultFromName,"replyToTel"=>"","replyToSiteWeb"=>"https://www.fichiernationalrccm.com/project/infos","toName" => sprintf("%s %s", $newUser->lastname, $newUser->firstname),"toEmail"=>  $newUser->email );
			    $activationMsgBody        = $view->partial("mailtpl/default.phtml" , $msgPartialData );							
				$mailer->setFrom($defaultFromEmail,stripslashes($defaultFromName));
				$mailer->setSubject("Activation de votre compte");
				$mailer->addTo( $user->email , stripslashes($user->lastname. " ".$user->firstname) );
				$mailer->setBodyHtml($activationMsgBody);
				
				try{
					$mailer->send();
				} catch(Exception $e) {
					$this->setRedirect(" Nous avons tenté de vous transmettre un email d'activation de votre compte sans succès, votre compte de messgarie semble inaccessible, vérifiez votre connexion internet et réessayer.","error");
					$this->redirect("public/account/reinitactivation/token/".$token);
				}				
				$activateSession->setExpirationSeconds(86400);
				
				$userTable       = $user->getTable();
				$lastSessionId   = Zend_Session::getId();
				$userTable       = $user->getTable();
				$userTable->setLastSessionId($lastSessionId);
				
				$this->setRedirect("Nous avons transmis un message d'activation de votre compte dans l'adresse de messagerie $email, veuillez vérifier et suivre les instructions","success");
				$this->redirect("public/account/login");
			}
			
			$this->getHelper("Message")->addMessage(" Impossible d'effectuer l'opération, vous avez entré un email invalide ");	
			$token  = Sirah_User_Helper::getToken(15).time();
			$userActivationSession->setExpirationSeconds(time()+(60*60*60));
		}		
		$this->view->token   = $token;
		$this->view->email   = $email;		
		$this->render("emailactivation");
	}
 
  
  
    public function sendactivationtokenAction()
	{
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$guest                  = Sirah_Fabric::getUser();
		$token                  = $this->_getParam("token",null);
		$userActivationSession  = $activateSession = new Zend_Session_Namespace("Activation");
		$email                  = null;
		 
		if( $guest->isLoggedIn() && $guest->activated ) {
			$this->setRedirect("Votre compte est déjà activé","message");
			$this->redirect("public/index/index");
		}
        $csrfFormNamesArray     = array("email","username");		
 	    $csrfTokenId            = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue         = $this->_helper->csrf->getToken(500);
		$csrfFormNames          = $fn = $this->_helper->csrf->getFormNames($csrfFormNamesArray, false); 
        if(!isset($userActivationSession->initialised) || !$userActivationSession->initialised) { 
            $userActivationSession->initialised = true;  
            $userActivationSession->token       = $csrfTokenValue;			
		}
		if( $this->_request->isPost() && isset($_POST[$csrfFormNames["email"]])){	
            if( $this->_helper->csrf->isValid() ) {
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter                    = new Zend_Filter();
				$stringFilter->addFilter(          new Zend_Filter_StringTrim());
				$stringFilter->addFilter(          new Zend_Filter_StripTags());
				
				$email                           = (isset($_POST[$csrfFormNames["email"]]))? $_POST[$csrfFormNames["email"]] : "";						
				
				$view                            = &$this->view;
				if(!empty($email )) {
					$userTable                   = $guest->getTable();	
					if( $row = $userTable->find(array("email" => $email))){				
						$userid                  = $row["userid"];
						$user                    = $newUser   = Sirah_Fabric::getUser($userid);
						$config                  = Sirah_Fabric::getConfig();
						$mailer                  = Sirah_Fabric::getMailer();
						
						$defaultFromEmail        = $config["resources"]["mail"]["defaultFrom"]["email"];
				        $defaultFromName         = $config["resources"]["mail"]["defaultFrom"]["name"];
						
						$activateAccountToken    = hash('ripemd160',sprintf("%s:%s",Sirah_User_Helper::getToken(5),$userid));
						$activateSession->code   = $activateAccountToken;
						$activateSession->userid = $userid;		
						
						$msgSubject              = "Vous souhaitez activer votre compte sur la plateforme";
						$linkActivateWithToken   = VIEW_BASE_URI."/public/account/activate/token/".$activateAccountToken."/uid/".$userid; 
						$linkSimpleActivate      = VIEW_BASE_URI."/public/account/activate/uid/".$userid; 
						$linkContact             = VIEW_BASE_URI."/public/project/contacts/uid/".$userid; 	
						
						$activationMsg           = " 
														Pour activer votre compte, vous pouvez : <br/>
														<ul>
															<li> Cliquez directement sur le lien suivant : <a title=\"Ce lien va automatiquement activer votre compte sur la plateforme\" href=\"".$linkActivateWithToken."\">Activer mon compte</a> </li>
															<li> Ou copier cette clé <b> ".$activateAccountToken." </b> : et copier-la dans le formulaire accessible à l'adresse suivante :<a href=\"".$linkSimpleActivate."\">Formulaire d'activation de mon compte</a> </li>
															<li> En cas d'incompréhensions ou de difficultés, <a title=\"Contacter notre équipe pour résoudre votre soucis.\" href=\"".$linkContact."\"> Contactez-nous pour aide </a>
														</ul>";
						
						$msgPartialData          = array("subject"=>$msgSubject,"message"=>$activationMsg,"logoMsg"=> APPLICATION_STRUCTURE_LOGO,"replyToEmail"=>$defaultFromEmail,"replyToName"=>$defaultFromName,"replyToTel"=>"","replyToSiteWeb"=>"https://www.fichiernationalrccm.com/project/infos","toName" => sprintf("%s %s", $newUser->lastname, $newUser->firstname),"toEmail"=>  $newUser->email );
			            $activationMsgBody       = $view->partial("mailtpl/default.phtml" , $msgPartialData );
						
						$mailer->setFrom($defaultFromEmail,stripslashes($defaultFromName));
						$mailer->setSubject("Activation de votre compte");
						$mailer->addTo( $user->email , stripslashes($user->lastname. " ".$user->firstname) );
						$mailer->setBodyHtml($activationMsgBody);
						
						try{
							$mailer->send();
						} catch(Exception $e) {
							$this->setRedirect(" Nous avons tenté de vous transmettre un email d'activation de votre compte sans succès, votre compte de messgarie semble inaccessible, vérifiez votre connexion internet et réessayer.","error");
							$this->redirect("public/account/reinitactivation/token/".$token);
						}				
						$activateSession->setExpirationSeconds(86400);
						
						$userTable       = $user->getTable();
						$lastSessionId   = Zend_Session::getId();
						$userTable       = $user->getTable();
						$userTable->setLastSessionId($lastSessionId);
						
						$this->setRedirect("Nous avons transmis un message d'activation de votre compte dans l'adresse de messagerie $email (vérifiez vos spams), veuillez vérifier et suivre les instructions","success");
						$this->redirect("public/account/login");
					} else {
						$this->getHelper("Message")->addMessage(" Impossible d'effectuer l'opération, vous avez entré un email invalide");
					}
				} else {
					    $this->getHelper("Message")->addMessage(" Impossible d'effectuer l'opération,veuillez saisir une adresse email");
				}				
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La validité du formulaire semble expirée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames            = $this->_helper->csrf->getFormNames($csrfFormNamesArray, true );				
		}		
 	
		$this->view->title            = "Renvoyez un code d'activation";
		$this->view->token            = $csrfTokenValue;	
        $this->view->formNames        = $csrfFormNames;
		$this->view->csrfTokenId      = $csrfTokenId;
		$this->view->csrfTokenValue   = $csrfTokenValue;
		$this->view->userid           = $guest->userid;
		//$this->render("sendtoken");
	}
	
	 
	
	/**
	 * L'action qui permet d'activer mon compte
	 *
	 *
	 */
	public function activateAction()
	{
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$uid    = $this->_getParam("uid"  ,$this->_getParam("userid", null));
		$token  = $this->_getParam("token",null);
		$user   = Sirah_Fabric::getUser($uid);
		
		if( $user->activated ) {
			$this->setRedirect("Votre compte semble déjà activé","message");
			$this->redirect("public/account/login");
		}						
		if( null!==$token  ) {
			$userActivationSession             = new Zend_Session_Namespace("Activation");	
			$newToken                          = hash('ripemd160',sprintf("%s:%s",Sirah_User_Helper::getToken(15),$uid));			
			if(!isset($userActivationSession->code) && !$user->isGuest()){
				$sessionData                   = $user->getSessionData();
				$userActivationSession->code   = isset($sessionData["Activation"]["code"]) ? $sessionData["Activation"]["code"] : null;
				$userActivationSession->userid = $uid;
			}			
			if(($userActivationSession->code  != $token)){
				$userActivationSession->token  = $newToken;
				$userActivationSession->setExpirationSeconds(86400);
				$this->authorize("reinitactivation");
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"=>"Impossible d'effectuer cette action,des paramètres supplementaires sont nécessaires","reload"=>true,"reloadUrl"=>$this->view->url(array("controller"=>"account", "action"=>"reinitactivation","token"=> $newToken ))));
					exit;
				}
				$this->setRedirect("La clé d'activation de votre compte semble expirée, veuillez reprendre la procédure en indiquant votre email","message");
				$this->redirect("public/account/reinitactivation/token/".$newToken);
			}
			$userid             = $userActivationSession->userid;
			$me                 = Sirah_Fabric::getUser($userid);			
			if( $me->enable()){
				$modelTable     = $me->getTable();
				$dbAdapter      = $modelTable->getAdapter();
				$prefixName     = $modelTable->info("namePrefix");		
				$dbAdapter->update($prefixName."rccm_members", array("activated"=>1), array("accountid=?"=>$userid));
				$this->setRedirect("Votre compte est activé avec succès, vous pouvez vous authentifier pour y accéder","success");
				$this->redirect("public/account/login");
			} else {
				$userActivationSession->setExpirationSeconds(86400);
				$userActivationSession->token  = $newToken;
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"=> "L'activation de votre compte a echoué, veuillez reprendre l'opération","reload"=> true,"reloadUrl"=> $this->view->url(array("controller" => "account", "action" => "reinitactivation", "token" => $newToken ))));
					exit;
				}
				$this->setRedirect("L'activation de votre compte a echoué, veuillez reprendre l'opération","error");
				$this->redirect("public/account/reinitactivation/token/".$newToken);
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