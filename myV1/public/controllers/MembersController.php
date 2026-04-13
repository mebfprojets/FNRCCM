<?php


/**
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIEMBF BURKINA FASO
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

class MembersController extends Sirah_Controller_Default
{
	protected $_member  = null;
	
	public function init()
	{
		$actionName     = $this->getRequest()->getActionName();
		if(($actionName!=="login") && ($actionName!=="create") && ($actionName!=="register") && ($actionName!=="exitandlogin")) {
			$me         = $loggedInUser = Sirah_Fabric::getUser();  
			$model                      = $this->getModel("member");
			$accountMember              = $model->fromuser($me->userid);
			if(!$accountMember ) {
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
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=> "Cette page n'est accessible qu'aux membres "));
					exit;
				}	
				$this->redirect("public/members/login");	
			}
			$this->_member            = $member;
		}		
		parent::init();
	}
	
	public function exitandloginAction()
	{
		$view              = &$this->view;
		
		$me                = Sirah_Fabric::getUser();
		$defaultSession    = new Zend_Session_Namespace("inscription");
		
		$inscriptionToken  = strip_tags($this->_getParam("inscriptiontoken", null ));
		$tokenFromSession  = "badToken";
		$memberid          = intval($this->_getParam("memberid" , 0));
		$accountid         = intval($this->_getParam("accountid", $me->userid));
		if( isset($defaultSession->token) ) {
			$tokenFromSession = $defaultSession->token;
		}		
		if(!$me->userid && intval($accountid) && ($tokenFromSession==$inscriptionToken)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success"=>"Votre compte a été créé avec succès. Un message vous a été transmis par email pour l'activer(Vérifiez vos spams si vous ne trouvez pas)." ));
				exit;
			}
			$this->setRedirect("Votre compte a été créé avec succès. Un message vous a été transmis par email pour l'activer(Vérifiez vos spams si vous ne trouvez pas).","success");
			$this->redirect("public/members/login");
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success"=>"Votre compte a été créé avec succès. Un message vous a été transmis par email pour l'activer (Vérifiez vos spams si vous ne trouvez pas)." ));
				exit;
			}
			$this->setRedirect("Votre compte a été créé avec succès. Un message vous a été transmis par email pour l'activer (Vérifiez vos spams si vous ne trouvez pas).","success");
			$this->redirect("public/members/login");
		}				 
	}
	
	 
	
	public function dashboardAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$me                   = Sirah_Fabric::getUser();
		$accountid            = $me->userid;
		$view                 = &$this->view;		
		$model                = $this->getModel("member");
        $modelDemande         = $this->getModel("demande");
		$accountMember        = $model->fromuser($accountid);
		$debug                = intval($this->_getParam("debug", 0));
        //var_dump($accountMember); die();
		if(!$accountMember ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Cette page n'est accessible qu'aux abonnés "));
				exit;
			}		
            $this->redirect("public/members/login");
		}
		$memberid           = $accountMember->memberid;
		$member             = $model->findRow($memberid,"memberid",null,false);
		if(!$member ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Cette page n'est accessible qu'aux abonnés "));
				exit;
			}		
            $this->redirect("public/members/login");	
		}
		$orderCart             = new Zend_Session_Namespace("ordercart");
		 
		$view->user            = $me;
        $view->member          = $this->view->client   = $member;
		$view->memberid        = $this->view->clientid = $memberid;
		$this->view->paiements = $member->paiements($memberid,15);		
		$this->view->commandes = $member->commandes($memberid,15);
		$this->view->invoices  = $this->view->factures = $invoices = $member->invoices($memberid,10);
		$this->view->demandes  = $modelDemande->getList(array("accountid"=>$accountid,"debug"=>$debug),1,15);
		$this->view->demandeur = $member->demandeur($memberid);
		$this->view->documents = $member->documents($memberid);	
        $this->view->entreprise= $member->findParentRow("Table_Entreprises");
		$this->view->pays      = $member->findParentRow("Table_Countries");		
		$this->view->ordercart = $orderCart;
        $view->title           = "Tableau de Bord";		
		 
	}
	
	public function loginAction()
	{
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
        $this->view->title     = "ESPACE PRIVE : CONNEXION";
		
		$guest                 = Sirah_Fabric::getUser();		
		if( $guest->isLoggedIn() ) {
			$this->redirect("public/members/dashboard");
		}
		$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');	
		$viewData             = array("username" => null,"password" => null,"rememberme" => 0);
		$errorMessages        = array();
		$captchaSession       = new Zend_Session_Namespace("captchas");		
		$csrfTokenId          = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue       = $this->_helper->csrf->getToken(300);
		$csrfFormNames        = $this->_helper->csrf->getFormNames(array("username","password") , false );
		$captcha              = null;
		$authSuccess          = false;
		$nextUri              = $this->_getParam("next", $this->_getParam("continue", $this->_getParam("returnTo", "" ) ) );

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
					
				$identity             = (isset($postData["username"]  ))? $stringFilter->filter( $postData["username"] ) : null;
				$credential           = (isset($postData["password"]  ))? $postData["password"]           : null;
				$rememberMe           = (isset($postData["rememberme"]))? intval($postData["rememberme"]) : 1;
				/*
				$gReCaptchaResponse	           = (isset($_POST['g-recaptcha-response']))? $_POST['g-recaptcha-response']        : null;
				if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
					$errorMessages[]           =  "Veuillez valider le captcha de sécurité";
				} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse) ) {
					$recaptcha                 = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
					$recaptchaResponse         = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
					if(!$recaptchaResponse->isSuccess()) {
						$errorMessages[]       = "Captcha Invalide !";
					}
				}*/
				if(!$strNotEmptyValidator->isValid($identity) && ( !$captchaSession->showcaptcha ) ) {
					$errorMessages[]  = "Entrez un identifiant valide";
				}
				if(!$strNotEmptyValidator->isValid( $credential ) && ( !$captchaSession->showcaptcha )) {
					$errorMessages[]  = "Entrez un mot de passe valide";
				}								
				//On vérifie le captcha
				if((null!==$captcha) && (false!==$captcha) && ( true == $captchaSession->showcaptcha ) ) {
					$captchainput                    = (isset($postData["captcha"]    )) ? $postData["captcha"] : "";
					$captchatime                     = (isset($postData["captchatime"])) ? $postData["captchatime"]:0;
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
				if( count( $errorMessages ) ) {									
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
					if( Zend_Uri::check( $nextUri ) ) {
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
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender( true );
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres de securité rattachés au formulaire doivent etre expirés. Veuillez re-actuliser la page", "reload" => true ));
					exit;
				}
				$csrfFormNames = $this->_helper->csrf->getFormNames(array("username","password","rememberme") , true );
			}									
		}		
		if((null!=$captcha ) && isset( $captchaSession->showcaptcha ) && (true == $captchaSession->showcaptcha)) {
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
	
	public function createAction()
	{
		$this->redirect('public/members/register');
	}
	
	public function registerAction()
	{		
	    $this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		//On initialise les variables nécessaires
		$user              = $me = Sirah_Fabric::getUser();
		$registerDoneUri   = $this->_getParam("doneUri","");
		$errorMessages     = array();
		
		$model             = $modelMember = $this->getModel("member");
		$modelEntreprise   = $this->getModel("entreprise");
		$modelCity         = $this->getModel("countrycity");
		$modelCountry      = $this->getModel("country");
		$modelProfile      = $this->getModel("profile");
		$modelLocalite     = $this->getModel("localite");
		$modelCoordonnees  = $this->getModel("profilecoordonnee");
		$modelDemandeur    = $this->getModel("demandeur");
		$modelIdentite     = $this->getModel("usageridentite");
		$modelIdentiteType = $this->getModel("usageridentitetype");
		
		$view              = &$this->view;
		
		//S'il n'est pas invité, on lui refuse cette opération
		if(!$user->isGuest() && intval($user->userid)){
			$this->setRedirect("Vous avez déjà un compte et vous êtes déjà authentifié(e). Si vous souhaitez créer un nouveau compte, veuillez vous déconnecter.","success");
			$this->redirect("public/members/dashboard");
		}
		$this->view->identiteTypes  = $identiteTypes = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
        $this->view->localites      = $localites     = $modelLocalite->getSelectListe(    "Sélectionnez une juridiction"            , array("localiteid", "libelle") , array() , null , null , false );
				
		$defaultData                = array("civilite"=>"","firstname"=>"","lastname"=>"","phone1"=>"","phone2"=>"","address"=>"","zipaddress"=>"","city"=>"","localiteid"=>0,"country"=>"BF","nationalite"=>"BF",
									        "birthday"=>"","birthaddress"=>"","username"=>"","password"=>"","email"=>"","sexe"=>"","passport"=>"",
									        "activated"=>1,"blocked"=>0,"locked"=>0,"accesstoken"=>"","logintoken"=>"","params"=>"","admin"=>0,
									        "creatoruserid"=>$me->userid,"updateduserid"=>0,"registeredDate"=> time(),"lastConnectedDate" => 0,
									        "lastUpdatedDate"=>0,"lastIpAddress"=>"","lastHttpMember"=> "","lastSessionId"=> "",
									        "numidentite"=>"","organisme_etablissement"=>"","lieu_etablissement"=>"","identitetype"=>"",
									        "date_etablissement"=>"","date_etablissement_day"=>0,"date_etablissement_month"=>0,"date_etablissement_year"=>0
                                     );		
		$csrfFormNamesArray         = array_merge(array_keys($defaultData), array("confirmedpassword","token","birthday","birthaddress","agree_tos"));
		$csrfTokenId                = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue             = $this->_helper->csrf->getToken(500);
		$csrfFormNames              = $fn   = $this->_helper->csrf->getFormNames($csrfFormNamesArray, false);
		$defaultSession             = new Zend_Session_Namespace("inscription");
		if(!$defaultSession->initialised ) {
			$defaultSession->initialised    = true;
			$defaultSession->returnToUrl    = "";
			$defaultSession->setExpirationSeconds(86400);
		}		
		if( $this->_request->isPost() && isset($_POST[$csrfFormNames["firstname"]]) && isset($_POST[$csrfFormNames["agree_tos"]])){   	
            if( $this->_helper->csrf->isValid() ) {
				//Une astuce pour contourner une attaque par force brute, en utilisaant le jeton du formulaire
				if(!isset($defaultSession->token) || ($this->_getParam($csrfTokenId,"") != $defaultSession->token) ) {
					$defaultSession->token = $csrfTokenValue;
					$defaultSession        = new Zend_Session_Namespace("captchacheck");
					$defaultSession->checks= 0;
					$urlDone               = $this->_helper->HttpUri(array("module"=>"public","controller"=>"account","action"=> "create"));
					$urlSecurityCheck      = $this->_helper->HttpUri(array("module"=>"public","controller"=>"securitycheck","action"=>"captcha","params"=> array("done"=> $urlDone, "token" => $defaultSession->token )));				
					if( $this->_request->isXmlHttpRequest()) {
						echo ZendX_JQuery::encodeJson(array("error"=>"Formulaire Invalide","reload"=>true, "newurl"=>$urlSecurityCheck));
						exit;
					}
					$this->setRedirect("Formulaire invalide","error");
					$this->redirect($urlSecurityCheck);
				}			
				$postData           = $viewData = $this->_request->getPost();
				$insert_data        = $defaultData ;
				if( count($postData) ) {
					$formInputKeys  = array_flip($csrfFormNames);
					foreach( $postData as $inputName=>$inputValue) {
						     if( array_key_exists($inputName,$formInputKeys)) {
								 $decodedInputName               = $formInputKeys[$inputName];
								 $insert_data[$decodedInputName] = $viewData[$decodedInputName] = $postData[$decodedInputName] = $inputValue;
							 }
					}
				}
				$emptyMemberData                = $modelMember->getEmptyData();
				$emptyDemandeurData             = $modelDemandeur->getEmptyData();
				$defaultIdentityData            = $modelIdentite->getEmptyData();
			    $memberPostData                 = array_intersect_key($postData, $emptyMemberData);
			    $memberData                     = array_merge($emptyMemberData , $memberPostData);
				$pieceIdentityData              = array_merge($defaultIdentityData, array_intersect_key($postData,$defaultIdentityData));
				
				//print_r($insert_data); print_r($csrfFormNames);die();
				$newuserid                      = $accountid = 0;
				$userTable                      = $user->getTable();
				$dbAdapter                      = $userTable->getAdapter();
				$prefixName                     = $userTable->info("namePrefix");			
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter                   = new Zend_Filter();
				$stringFilter->addFilter(         new Zend_Filter_StringTrim());
				$stringFilter->addFilter(         new Zend_Filter_StripTags());
				
				//On crée les validateurs nécessaires
				$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
				$nameValidator                 = new Validator_Name();						
				$emailValidator                = new Sirah_Validateur_Email();
				$membernameValidator           = new Sirah_Validateur_Membername();
				
				$gReCaptchaResponse	           = (isset($postData["g-recaptcha-response"]))? $postData["g-recaptcha-response"]  : null;
				if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
					$errorMessages[]           =  "Veuillez valider le captcha de sécurité";
				} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse)) {
					$recaptcha                 = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
					$recaptchaResponse         = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
					if(!$recaptchaResponse->isSuccess()) {
						$errorMessages[]       = "Captcha Invalide !";
					}
				}			
				$emailIsValid                  = false;
				$agreeTos                      = (isset($postData["agree_tos"] ))? intval($postData["agree_tos"])                : 0;	
                $insert_data["lastname"]       = (isset($postData["lastname"]  ))? $stringFilter->filter($postData["lastname"])  : "";	
                $insert_data["firstname"]      = (isset($postData["firstname"] ))? $stringFilter->filter($postData["firstname"]) : "";				
				$insert_data["username"]       = (isset($postData["username"]  ))? $stringFilter->filter($postData["username"])  : "";
				$insert_data["email"]          = (isset($postData["email"]     ))? $stringFilter->filter($postData["email"])     : "";
				$insert_data["sexe"]           = (isset($postData["sexe"]      ))? $stringFilter->filter($postData["sexe"])      : "";
				$insert_data["country"]        = (isset($postData["country"]   ))? $stringFilter->filter($postData["country"])   : "";
				$insert_data["localiteid"]     = (isset($postData["localiteid"]))? intval($postData["localiteid"])               : 0;
				$insert_data["city"]           = (isset($postData["city"]      ))? intval($postData["city"])                     : 0;
				$insert_data["phone1"]         = (isset($postData["phone1"]    ))? $stringFilter->filter($postData["phone1"])    : "";
				$insert_data["phone2"]         = (isset($postData["phone2"]    ))? $stringFilter->filter($postData["phone2"])    : "";
				$memberData["tel1"]		       =  $stringFilter->filter($insert_data["phone1"]);
                $memberData["tel2"]		       =  $stringFilter->filter($insert_data["phone2"]);
                $birthdayDate                  = (isset($postData["birthday"]  ))? $postData["birthday"]                         : "" ;
				$accountEmail                  = $insert_data["email"];
				if(!intval($agreeTos)) {
					$errorMessages[]           = "Vous devrez d'abord accepter nos termes de services";
				}					
				//On vérifie les informations de la référence de la pièce d'identité
				$zendIdentityDate              = new Zend_Date(array("year" => (isset($postData["date_etablissement_year"] ))? intval($postData["date_etablissement_year"]) : 0,
																	 "month"=> (isset($postData["date_etablissement_month"]))? intval($postData["date_etablissement_month"]): 0,
																	 "day"  => (isset($postData["date_etablissement_day"]  ))? intval($postData["date_etablissement_day"])  : 0 ) );						
				$postData["date_etablissement"]  = ($zendIdentityDate)?$zendIdentityDate->toString("YYYY-MM-dd") : "";
				if( intval($postData["identitetype"]) && isset($identiteTypes[$postData["identitetype"]])) {
					$pieceIdentityData["typeid"] = intval($postData["identitetype"]);
				}
				if( $strNotEmptyValidator->isValid($postData["numidentite"]) ) {
					$pieceIdentityData["numero"]                  = $stringFilter->filter( $postData["numidentite"] );
				}
				if( $strNotEmptyValidator->isValid($postData["organisme_etablissement"]) ) {
					$pieceIdentityData["organisme_etablissement"] = $stringFilter->filter($postData["organisme_etablissement"] );
				}
				if( $strNotEmptyValidator->isValid($postData["lieu_etablissement"]) ) {
					$pieceIdentityData["lieu_etablissement"]      = $stringFilter->filter($postData["lieu_etablissement"] );
				}
				if( $strNotEmptyValidator->isValid($postData["date_etablissement"]) && Zend_Date::isDate($postData["date_etablissement"], "YYYY-MM-dd")) {
					$pieceIdentityData["date_etablissement"]      = $stringFilter->filter($postData["date_etablissement"] );
				}
				//On vérifie qu'il n'ya pas une personne qui a la même identité
				if( empty($errorMessages) && !empty($pieceIdentityData["numero"]) && !empty($pieceIdentityData["date_etablissement"])
					&& !empty($pieceIdentityData["organisme_etablissement"]) && !empty($pieceIdentityData["lieu_etablissement"])) {
					$numidentite          = $pieceIdentityData["numero"];
					$postData["passport"] = $numPassport = ($zendIdentityDate)?sprintf("%s du %s", $numidentite,$zendIdentityDate->toString("dd/MM/YYYY")) : sprintf("%s du %s", $numidentite,$postData["date_etablissement"]);
					$selectIdentity       = $dbAdapter->select()->from( $prefixName."reservation_demandeurs_identite")
					                                            ->where("numero=?",$numidentite)
														        ->where("date_etablissement=?",$pieceIdentityData["date_etablissement"]);
					$foundIdentity        = $dbAdapter->fetchRow($selectIdentity, array(), Zend_DB::FETCH_ASSOC);
                    if( count($foundIdentity) && isset($foundIdentity["numero"])) {
						$foundMember      = $modelMember->findRow($numPassport,"passport",null,false);
						if( $foundMember && isset($foundMember->memberid)) {
							if( intval($foundMember->accountid)) {
								$errorMessages[] = sprintf("Une autre personne existe déjà dans le système avec les mêmes références de la pièce d'identité");
							}
						}
					}						
				}
				//On enregistre les références de la pièce d'identité
				if( empty($errorMessages) && !empty($pieceIdentityData["numero"]) && !empty($pieceIdentityData["date_etablissement"])
					&& !empty($pieceIdentityData["organisme_etablissement"]) && !empty($pieceIdentityData["lieu_etablissement"])) {
					$pieceIdentityData["creationdate"]  = time();
					$pieceIdentityData["creatorid"]     = 1;
					$pieceIdentityData["updatedate"]    = 0;
					$pieceIdentityData["updateduserid"] = 0;
					$dbAdapter->delete(     $prefixName."reservation_demandeurs_identite", array("numero=?"=>$pieceIdentityData["numero"],"typeid=?"=>$pieceIdentityData["typeid"]));
					if( $dbAdapter->insert( $prefixName."reservation_demandeurs_identite", $pieceIdentityData)  ) {
						$identityid                     = $dbAdapter->lastInsertId();
						$numidentite                    = sprintf("%s n° %s du %s par %s", $identiteTypes[$postData["identitetype"]], $pieceIdentityData["numero"], $pieceIdentityData["date_etablissement"], $pieceIdentityData["organisme_etablissement"], $pieceIdentityData["lieu_etablissement"]);
						$postData["passport"]           = $postData["numidentite"]    = $demandeurData["numidentite"] = $numidentite;
						$postData["identityid"]         = $demandeurData["identityid"]= $identityid;
				    }
				}				
				if(!$strNotEmptyValidator->isValid( $insert_data["firstname"] ) || !$membernameValidator->isValid($insert_data["firstname"])) {
					$errorMessages[]                    = "Veuillez saisir un nom valide ";
				} else {
					$insert_data["firstname"]           = $stringFilter->filter($insert_data["firstname"]);
				}
				if(!$strNotEmptyValidator->isValid($insert_data["lastname"])  || !$membernameValidator->isValid($insert_data["lastname"])) {
					$errorMessages[]                    = " Le nom de famille que vous avez saisi, est invalide ";
				} else {
					$insert_data["lastname"]            = $stringFilter->filter($insert_data["lastname"]);
				}
				if(!$strNotEmptyValidator->isValid($insert_data["phone1"])){
					$insert_data["phone1"]              = " ";
				} else {
					$insert_data["phone1"]              = $memberData["tel1"] = $stringFilter->filter( $insert_data["phone1"] );
				}
				if( $strNotEmptyValidator->isValid($insert_data["phone1"])) {
					if( $modelMember->findRow($insert_data["phone1"],"tel1",null,false) ) {
						$errorMessages[]                = sprintf("Un compte existe déjà avec le numéro de téléphone %s", $insert_data["phone1"]);
					}
				}
				$countryCallingCode                     = (!empty($insert_data["country"])) ?$modelCountry->callingCode($insert_data["country"]) : "";
				if( empty( $countryCallingCode ) ) {
					$errorMessages[]           = "Veuillez sélectionner votre pays de résidence";
				} else {
					$formatPhoneNumber         = sprintf("+%s%s", $countryCallingCode , preg_replace("/[^0-9]|(".$countryCallingCode.")/s", '', $memberData["tel1"] ));
					$validPhoneNumberPattern   ="/\+[0-9]{2,3}+[0-9]{8,10}/s";
					if(!preg_match( $validPhoneNumberPattern, $formatPhoneNumber ) ) {
						$errorMessages[]       = "Veuillez saisir un numéro de téléphone respectant le format de numéro de téléphone de votre pays de résidence";
					} else {
						$memberData["tel1"]	   = $insert_data["phone1"] = $formatPhoneNumber;
					}
				}
                if(!intval($insert_data["city"]) && isset($postData["ville"]) && !empty($insert_data["country"])) {
					if( $strNotEmptyValidator->isValid(   $postData["ville"])) {
						$libelleVille          = $stringFilter->filter(strip_tags($postData["ville"]));
						$rowCity               = $modelCity->findRow( $libelleVille, "libelle" , null , false);
						if( $rowCity ) {
							$memberData["city"]= $rowCity->localiteid;
						} else {
							$libelleVille      = $stringFilter->filter( $postData["ville"] );
							$codeVille         = strtoupper(substr($libelleVille,0, 3));
							$i = 0;
							while( $modelCity->findRow( $codeVille, "code", null , false )) {
								   $i++;
								   $codeVille  = $codeVille."(".$i.")";
							}
							if( $dbAdapter->insert( $prefixName."system_countries_cities",array("libelle"=>$libelleVille,"country"=>$insert_data["country"],"code"=>$codeVille,"creatorid"=>$me->userid,"creationdate"=>time()))) {
								$memberData["city"] = $insert_data["city"] = $dbAdapter->lastInsertId();
							}
						}
					}
				}				
				if( Zend_Date::isDate($birthdayDate, "dd/MM/YYYY")) {				
					$zendBirthday              = new Zend_Date($birthdayDate, "dd/MM/YYYY");
					$birthdayTms               = ($zendBirthday)? $zendBirthday->get(Zend_Date::TIMESTAMP)       : 0;
                    $memberData["birthday"]    = ($zendBirthday)? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "";					
				} else {
					$zendBirthday              =  new Zend_Date(array("year" =>(isset($postData["birthday_year"] ))? intval($postData["birthday_year"]) : 0,
																	  "month"=>(isset($postData["birthday_month"]))? intval($postData["birthday_month"]): 0,
																	  "day"  =>(isset($postData["birthday_day"]  ))? intval($postData["birthday_day"])  : 0 ) );
					$birthdayTms               = ($zendBirthday)? $zendBirthday->get(Zend_Date::TIMESTAMP)       : 0;
                    $memberData["birthday"]    = ($zendBirthday)? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "";							
				}	
				if( intval($birthdayTms) ) {
					$memberData["birthday"]    = ($zendBirthday) ? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "";
					$insert_data["birthday"]   = $birthdayTms;
				}				
				if(!$userTable->checkEmail( $insert_data["email"] ) || !$emailValidator->isValid( $insert_data["email"] ) ) {
					$errorMessages[]           = " L'adresse email ".$insert_data["email"]." n'est pas valide ou peut etre associée à un autre compte ";
				} else {
					$emailIsValid              = true;
				}
				if(!$strNotEmptyValidator->isValid( $insert_data["username"]) && $emailIsValid && empty( $errorMessages ) ) {					 
					$insert_data["username"]   = $insert_data["email"];				
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
				$insert_data["address"]        = (isset($postData["address"]   ))? $stringFilter->filter($postData["address"])    : "";
				$insert_data["city"]           = (isset($insert_data["city"]   ))? intval( $insert_data["city"] )                 : 0;
				$insert_data["zipaddress"]     = (isset($postData["zipaddress"]))? $stringFilter->filter($postData["zipaddress"]) : "";
				$insert_data["language"]       = (isset($postData["language"]  ))? $stringFilter->filter($postData["language"])   : "";
				$insert_data["facebookid"]     = "";
				$insert_data["skypeid"]        = "";				
				$insert_data["activated"]      = 0;
				$insert_data["expired"]        = 0;
				$insert_data["blocked"]        = 0;
				$insert_data["locked"]         = 0;
				$insert_data["nb_connections"] = 0;
				$insert_data["lastIpAddress"]  = Sirah_Functions::getIpAddress();
				$insert_data["lastHttpMember"] = Sirah_Functions::getBrowser();
				$insert_data["lastSessionId"]  = Zend_Session::getId();
				$insert_data["creatoruserid"]  = 1;
				$insert_data["registeredDate"] = $insert_data["creationdate"] = $insert_data["lastConnectedDate"] = time();
				$insert_data["params"]         = "{}";
				$insert_data["presentation"]   = "";
				$insert_data["avatar"]         = "";
						 
				if( empty( $errorMessages ) ) {
					try {
						$user->clearMessages();
						if(!$newuserid         = $user->save($insert_data)){
							$saveErrors        = $user->getMessages("error");
							foreach( $saveErrors as $type => $msg){
									 $msg                 = (is_array($msg)) ? array_shift($msg)  : $msg;
									 $errorMessages[]     = $msg;
							}
						}
					} catch(Exception $e) {
						$newuserid             = 0;
						$errorMessages[]       = sprintf("Erreur de création de compte peut être pour un doublon de téléphone ou d'email");
					}					
				}	
				if( empty($errorMessages) && $newuserid){	
                    $accountid        = $newuserid;				
					$newUser          = $me                 = Sirah_Fabric::getUser($newuserid);
					
					$dbAdapter->delete( $prefixName."system_users_profile","profileid IN (SELECT C.profileid FROM system_users_profile_coordonnees C WHERE C.email=\"".$accountEmail."\")");
					$dbAdapter->delete( $prefixName."system_users_profile_coordonnees",array("email=\"".$accountEmail."\""));
					$dbAdapter->delete( $prefixName."rccm_members",array("email=\"".$accountEmail."\""));
					$config                                 = Sirah_Fabric::getConfig();		
					$mailer                                 = Sirah_Fabric::getMailer();
					$newUser                                = Sirah_Fabric::getUser($accountid);				
					$activateAccountToken                   = Sirah_User_Helper::getToken(10). time() .$accountid;	
					$modelProfile                           = $this->getModel("profile");
					
					$profile                                = $modelProfile->getRow($accountid,true , false );
                    $profileid	                            = ($profile  )? $profile->profileid : 0;				
					$userCoordonnees                        = ($profileid)? $modelCoordonnees->findRow($profileid, "profileid" , null , false ) : null ;
					if( $profileid )  {
						$profileDefaultData                 = $profile->toArray();
						$cleanProfileData                   = array_intersect_key($insert_data,$profileDefaultData );
						$profileData                        = array_merge( $profileDefaultData,$cleanProfileData );
						$profileData["userid"]              = $accountid;
						$profile->setFromArray( $profileData );
						$profile->save();
						if( $userCoordonnees )  {
							$coordonneesData                = $insert_data;
						    $coordonneesData["tel_mob"]     = $insert_data["phone1"];
							$coordonneesData["tel_bureau"]  = $insert_data["phone2"];
							$userCoordonnees->setFromArray($coordonneesData );
							$userCoordonnees->save();
						}
						$memberData["name"]                 = $memberName = sprintf("%s %s", $insert_data["lastname"], $insert_data["firstname"]);
						$memberData["avatar"]               = "";
						$memberData["accountid"]            = intval($accountid );
						$memberData["entrepriseid"]         = (isset($postData["entrepriseid"]     ))?intval($postData["entrepriseid"]) : 0;
						$memberData["code"]                 = (isset($postData["code"]             ))?$stringFilter->filter($postData["code"] )       : $insert_data["username"];
						$memberData["civilite"]             = (isset($postData["civilite"]         ))?$stringFilter->filter($postData["civilite"] )   : "Mr/Mme";
						$memberData["passport"]             = (isset($postData["passport"]         ))?$stringFilter->filter($postData["passport"] )   : "";
						$memberData["nationalite"]          = (isset($postData["nationalite"]      ))?$stringFilter->filter($postData["nationalite"]) : "BF";
						$memberData["country"]              = (isset($postData["country"]          ))?$stringFilter->filter($postData["country"] )    : $memberData["nationalite"];
						$memberData["address"]              = $stringFilter->filter($insert_data["address"] );
						$memberData["matrimonial"]          = (isset($postData["socialstate"]      ))?$stringFilter->filter($postData["socialstate"])       : $stringFilter->filter($memberData["matrimonial"]);
						$memberData["fonction"]             = (isset($postData["professionalstate"]))?$stringFilter->filter($postData["professionalstate"]) : $stringFilter->filter($memberData["fonction"] );
						$memberData["observations"]         = "";
						$memberData["params"]               = "";
						$memberData["activated"]            = 0;
						$memberData["groupid"]              = 1;
						$memberData["creationdate"]         = time();
						$memberData["creatorid"]            = $accountid;
						$memberData["updatedate"]           = 0;
						$memberData["updateduserid"]        = 0;
						$memberEmptyData                    = $modelMember->getEmptyData();
						$memberTable                        = $modelMember->getTable();
						$memberTableName                    = $memberTable->info("name");
						$member_insert_data                 = array_intersect_key( $memberData  , $memberEmptyData );
						if( $dbAdapter->insert($memberTableName, $member_insert_data ) )	{
							$memberid                       = $dbAdapter->lastInsertId();
							if(!$strNotEmptyValidator->isValid( $memberData["code"] )) {
								$code                       = "Ml-".sprintf("%06d", $memberid);
								$member                     = $modelMember->findRow($memberid,"memberid",null , false );
								$member->code               = $code;
								$member->save();
							}							
							//On enregistre la personne comme étant un demandeur
							$demandeurData                  = array_merge($emptyDemandeurData,array_intersect_key($memberData,$emptyDemandeurData));
							$demandeurData["name"]          = $memberName;
							$demandeurData["datenaissance"] = ($zendBirthday)? $zendBirthday->toString("YYYY-MM-dd") : "";
							$demandeurData["lieunaissance"] = $memberData["birthaddress"];
							$demandeurData["telephone"]     = (!empty($memberData["tel2"]))?sprintf("%s/%s",$memberData["tel1"],$memberData["tel2"]) : sprintf("%s",$memberData["tel1"]);
							$demandeurData["adresse"]       = $memberData["address"];
							$demandeurData["profession"]    = $memberData["fonction"];
							$demandeurData["numidentite"]   = (isset($postData["numidentite"]))?$postData["numidentite"] : "";
							$demandeurData["identityid"]    = (isset($postData["identityid"] ))?$postData["identityid"]  : 0;
							$demandeurData["accountid"]     = intval($accountid);
							$demandeurData["creationdate"]  = time();
							$demandeurData["creatorid"]     = 1;
							$demandeurData["updatedate"]    = 0;
							$demandeurData["updateduserid"] = 0;
							if(!empty($demandeurData["numidentite"]) && intval($demandeurData["identityid"])) {
								if(!$dbAdapter->insert($prefixName."reservation_demandeurs",$demandeurData) )	{
									$errorMessages[]        = "Les informations en tant que potentiel demandeur de vérification n'ont pas été enregistrées.";
								}
							}		
							$defaultFromEmail               = $config["resources"]["mail"]["defaultFrom"]["email"];
							$defaultFromName                = $config["resources"]["mail"]["defaultFrom"]["name"];
							$civilite                       = (!empty($memberData["civilite"]))?$memberData["civilite"] : "Mr/Mme";
														 
							$linkActivateWithToken          = VIEW_BASE_URI."/public/account/activate/token/".$activateAccountToken."/uid/".$accountid; 
							$linkSimpleActivate             = VIEW_BASE_URI."/public/account/activate/uid/".$accountid;				
							$activationMsg                  = " Bonjour ".sprintf('%s %s',$civilite,$memberName).". La création de votre compte s'est produite avec succès. <br/>
																A présent, vous devrez l'activer en suivant les instructions ci-dessous: <br/>
																<ul>
																	<li>  Cliquez directement  sur le lien : <a title=\"Cliquer pour activer votrecompte\" href=\"".$linkActivateWithToken."\">Activer mon compte</a> </li>
																	<li>  Ou, copier cette clé <b> ".$activateAccountToken." </b>: et la coller dans le formulaire de l'adresse suivante :<a href=\"".$linkSimpleActivate."\"> Formulaire d'activation de mon compte</a> </li>	
																	<li>  En cas d'incompréhensions ou de difficultés, <a href=\"#\"> Contactez-nous pour aide </a>			                          
																</ul>
																<p> <b><u> NB: </u> La démarche d'activation du compte s'expire dans 24H. Veuillez prendre des dispositions pour le faire avant.</b> </p> 
																<p> <b><i> Cordialement, ".stripslashes($defaultFromName)." </i></b> </p>";
							$msgSubject                     = "Activation de votre compte d'accès sur la plateforme ".$defaultFromName;								
							$msgPartialData                 = array("subject"=>$msgSubject,"message"=>$activationMsg,"logoMsg"=> APPLICATION_STRUCTURE_LOGO,"replyToEmail"=>$defaultFromEmail,"replyToName"=>$defaultFromName,"replyToTel"=>"","replyToSiteWeb"=>"https://www.fichiernationalrccm.com/project/infos","toName" => sprintf("%s %s", $newUser->lastname, $newUser->firstname),"toEmail"=>  $newUser->email );
							$msgBody                        = $view->partial("mailtpl/default.phtml" , $msgPartialData );
							$mailer->setFrom($defaultFromEmail,$defaultFromName);
							$mailer->setSubject( $msgSubject );
							$mailer->addTo($accountEmail,$memberData["lastname"]);
							$mailer->setBodyHtml( $msgBody );		
                            $sendingMail                     = false;							
							try{
								$sendingMail = $mailer->send();
							} catch(Exception $e) {
								//On supprime l'utilisateur car son email est invalide
								$userTable   = $newUser->getTable();
								$userAuth    = $newUser->getAuth();					
								if( $userAuth->hasIdentity() ) {
									$userAuth->clearIdentity();
									Zend_Session::forgetMe();
								}
								$newUser->delete();
								$dbAdapter->delete( $prefixName."rccm_members","memberid='".$memberid."'") ;
								$errorMessages[]     = " Nous avons tenté de vous transmettre un email d'activation de votre compte sans succès, votre compte de messagerie semble inaccessible, vérifiez votre connexion internet ou contactez les administrateurs de cette platefforme et reprenez l'opération.";
								if( APPLICATION_DEBUG ) {
									$errorMessages[] = " Informations de débogages : ".$e->getMessage();
								}
								$guest               = Sirah_Fabric::getUser(0);
								$view->user          = $guest;
							}							
						} else {
							    $errorMessages[]     = "L'opération de création de votre a echoué";
						}						
					}
				}
				if( count($errorMessages) ) {
					$defaultData  = $postData;
					if( $this->_request->isXmlHttpRequest()){
						echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
						exit;
					}
					foreach( $errorMessages as $errorMessage){
							 $this->getHelper("Message")->addMessage($errorMessage,"error");
					}
				} else {
					//On lui assigne le role par defaut des utilisateurs
					Sirah_User_Acl_Table::assignRoleToUser($accountid, APPLICATION_DEFAULT_USERS_ROLENAME );
					Sirah_User_Acl_Table::assignRoleToUser($accountid, "OPERATEURS");
					$lastSessionId            = Zend_Session::getId();
					$userTable                = $me->getTable();
					$userTable->setLastSessionId( $lastSessionId );	
					
					$defaultSession->token    = $inscriptionToken = $this->_helper->csrf->getToken(500);
					$defaultSession->setExpirationSeconds(600);

					//On stocke les données d'activation dans la session
					$activateSession          = new Zend_Session_Namespace("Activation");
					$activateSession->code    = $activateAccountToken;
					$activateSession->userid  = $newUser->userid;
					$activateSession->setExpirationSeconds(86400);
					 
					$successMsg               = sprintf("Merci %s %s, votre compte a été créé avec succès. Un mail vous a été transmis pour son activation(Vérifiez vos spams si vous ne trouvez pas). ", $insert_data["civilite"],$memberName);
                    $registerDoneUri          = (isset($postData["registerDoneUri"]))? $postData["registerDoneUri"] : "";
					if(!empty($registerDoneUri) ) {
						if( stripos($registerDoneUri,"http")==false ) {
							$registerDoneUri  = $_SERVER["HTTP_HOST"]."://".$_SERVER["HTTP_SCHEME"]."/".trim($registerDoneUri,"/");
						}
						$doneURI              = (Zend_Uri::check($registerDoneUri)==true)?Zend_Uri::factory($registerDoneUri) : null;	
						if( null==$doneURI ) {
							$this->setRedirect("Impossible de rediriger la requete sur l'adresse URL fournie","error");
							$this->redirect("public/members/login");
						} elseif(($_SERVER["HTTP_HOST"]!=$doneURI->getHost()) || ($_SERVER["HTTP_SCHEME"]!=$doneURI->getScheme())) {
							$this->setRedirect("Impossible de rediriger la requete sur un autre domaine","error");
							$this->redirect("public/members/login");
						}
						$this->setRedirect( $successMsg ,"success");
						$this->_helper->redirector->gotoUrl($registerDoneUri); 
					} elseif(isset($defaultSession->returnToUrl) && !empty($defaultSession->returnToUrl)) {
						$registerDoneUri      = $defaultSession->returnToUrl;
						if( stripos($registerDoneUri,"http")!==false ) {
							$this->_helper->redirector->gotoUrl($registerDoneUri); 
						} else {
							$this->redirect($registerDoneUri);
						}
				    } else {
					    //$this->setRedirect( $successMsg ,"success");
					    $this->redirect("public/members/exitandlogin/inscriptiontoken/".$inscriptionToken."/accountid/".$accountid."/memberid/".$memberid);
					}
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
		if( count(   $defaultData)) {
			foreach( $defaultData as $dKey=>$dValue) {
				     if( isset($csrfFormNames[$dKey])) {
						 $defaultData[$csrfFormNames[$dKey]] = $dValue;
					 }
			}
		}
 
		//On crée un jeton de connexion
		$defaultSession->token        = $csrfTokenValue;		
		$this->view->title            = "INSCRIPTION : Créer un compte";
		$this->view->data             = $defaultData;
		$this->view->token            = $csrfTokenValue;	
        $this->view->formNames        = $csrfFormNames;
		$this->view->csrfTokenId      = $csrfTokenId;
		$this->view->csrfTokenValue   = $csrfTokenValue;		
		$this->render("register");
	}
	
	
	public function editAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$me                 = Sirah_Fabric::getUser();
		$userTable          = $me->getTable();
		$dbAdapter          = $userTable->getAdapter();
		$prefixName         = $userTable->info("namePrefix");	
		$model              = $this->getModel("member");
		$modelCity          = $this->getModel("countrycity");
		$modelProfile       = $this->getModel("profile");
		$modelCoordonnees   = $this->getModel("profilecoordonnee");
		
		if( $me->isGuest() ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Veuillez vous authentifier "));
				exit;
			}	
            $this->redirect("public/members/login");			
		} 
		$accountMember           = $model->fromuser($me->userid);
		if(!$accountMember ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
            $this->redirect("public/members/login");	
		}
		$memberid             = $accountMember->memberid;
		$member               = $model->findRow($memberid , "memberid", null , false );
		if(!$member ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> sprintf("Aucun membre valide n'a été retrouvé avec l'identifiant %s " , $memberid )));
				exit;
			}
			$this->setRedirect(sprintf("Aucun membre valide n'a été retrouvé avec l'identifiant %s " , $memberid ) , "error");
			$this->redirect("public/members/dashboard");
		}
		$defaultData         = $member->toArray();
		$errorMessages       = array();
		$entreprise          = ( $entrepriseid ) ? $modelEntreprise->findRow( $entrepriseid,"entrepriseid",null, false ) : null;
	
		if( $this->_request->isPost()  )    {
			$postData                    = $this->_request->getPost();
			$memberData                  = array_merge( $defaultData , $postData );	
            //On met à jour les informations du compte de l'utilisateur
			$myDefaultData               = $userTable->getData();
										
			//On crée les validateurs nécessaires
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$usernameValidator           = new Sirah_Validateur_Username();
			$emailValidator              = new Sirah_Validateur_Email();
			$passwordValidator           = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(4);
	        $memberData["code"]          = (isset( $postData["code"] ))?$stringFilter->filter($postData["code"]) : $member->code;
	       	
            $birthdayDate                = (isset($postData["birthday"] ))?$postData["birthday"]                 : "" ;
			if( Zend_Date::isDate($birthdayDate, "dd/MM/YYYY")) {				
				$zendBirthday            = new Zend_Date($birthdayDate, "dd/MM/YYYY");
                $birthdayTms             = ($zendBirthday)? $zendBirthday->get(Zend_Date::TIMESTAMP)       : 0;				
			    $memberData["birthday"]  = ($zendBirthday)? $zendBirthday->toString("YYYY-MM-dd HH:mm:ss") : "";
			} else {
				$zendBirthday            = new Zend_Date($member->birthday,"YYYY-MM-dd HH:mm:ss");
			    $birthdayTms             = ($zendBirthday)? $zendBirthday->get(Zend_Date::TIMESTAMP) : 0;	
                $memberData["birthday"]  = $member->birthday; 				
			}	
 			
			if(!$emailValidator->isValid($memberData["email"] ) ) {
				$errorMessages[]         = "Veuillez fournir une adresse email valide";
			} elseif(($existantMember    = $model->findRow($memberData["email"],"email",null,false)) && (strtolower($memberData["email"])!=strtolower($member->email))) {
				$errorMessages[]         = sprintf(" Un member du nom de %s %s existe déjà avec cette adresse email %s", $existantMember->lastname, $existantMember->firstname, $memberData["email"] );
		    }  if(!$userTable->checkEmail($memberData["email"]) && ($memberData["email"] != $member->email) ){
				$errorMessages[]         = $errorMessages[] = " L'adresse email ".$memberData["email"]." n'est pas valide ou peut etre associée à un autre compte ";
		    } else {
				$memberData["email"]     = $stringFilter->filter($memberData["email"]);
			}
			if(!$userTable->checkUsername($memberData["code"])  && (strtolower($memberData["code"])!=strtolower($member->code))){
				$errorMessages[]         = " Le nom d'utilisateur ".$memberData["code"]." n'est pas valide ou peut etre associé à un autre compte ";
		    }
			if(!$strNotEmptyValidator->isValid($memberData["tel1"])) {
				$errorMessages[]         = " Veuillez entrer un numéro de téléphone mobile valide";
			} elseif(($existantMember    = $model->findRow($memberData["tel1"],"tel1", null,false)) && ($memberData["tel1"]!= $member->tel1 ) ) {
				$errorMessages[]         = sprintf(" Un member du nom de %s %s existe déjà avec ce numéro %s", $existantMember->lastname, $existantMember->firstname, $memberData["tel1"] );
		    } else {
				$memberData["tel1"]      = $stringFilter->filter($memberData["tel1"]);
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
			if(!$memberData["city"] && isset($postData["ville"] ) ) {
				if( $strNotEmptyValidator->isValid( $postData["ville"] ) ) {
					$libelleVille  = $stringFilter->filter( $postData["ville"]);
					$rowCity       = $modelCity->findRow( $libelleVille , "libelle" , null , false);
					if( $rowCity ) {
						$memberData["city"]     = $rowCity->localiteid;
					} else {
						$libelleVille = $stringFilter->filter( $postData["ville"] );
						$codeVille    = strtoupper(substr( $libelleVille, 0, 3 ));
						$i = 0;
						while( $modelCity->findRow( $codeVille, "code", null , false )) {
							   $i++;
							   $codeVille = $codeVille."(".$i.")";
						}
						if( $dbAdapter->insert( $prefixName."system_countries_cities",array("libelle"=>$libelleVille,"code"=>$codeVille,"creatorid"=>$me->userid,"creationdate"=>time()))) {
							$memberData["city"] = $dbAdapter->lastInsertId();
						}
					}
				}
			}
			$memberData["groupid"]           = 1;
			$memberData["code"]              = $stringFilter->filter( $memberData["code"] );
			$memberData["passport"]          = $stringFilter->filter( $memberData["passport"] );
			$memberData["nationalite"]       = $stringFilter->filter( $memberData["nationalite"] );
			$memberData["country"]           = $stringFilter->filter( $memberData["country"] );
			$memberData["address"]           = $stringFilter->filter( $memberData["address"] );
			$memberData["birthaddress"]      = $stringFilter->filter( $memberData["birthaddress"] );
			$memberData["tel2"]              = $stringFilter->filter( $memberData["tel2"] );
			$memberData["matrimonial"]       = $stringFilter->filter( $memberData["matrimonial"] );
			$memberData["fonction"]          = $stringFilter->filter( $memberData["fonction"] );
			$memberData["observations"]      = $memberData["observations"];
			$memberData["updateduserid"]     = $me->userid;
			$memberData["updatedate"]        = time();
			
			$formAccountData                 = array_intersect_key($postData, $myDefaultData);
			$accountData                     = array_merge($myDefaultData, $formAccountData );
			$accountData["username"]         = $stringFilter->filter($memberData["code"]);
			$accountData["email"]            = $stringFilter->filter($memberData["email"]);
			$accountData["phone1"]           = $stringFilter->filter($memberData["tel1"]);
			$accountData["phone2"]           = $stringFilter->filter($memberData["tel2"]);
			$postData["password"]            = $accountData["password"]  = (isset($postData["password"])) ? $postData["password"] : "";
			if( $strNotEmptyValidator->isValid( $accountData["password"])){
				if(!$passwordValidator->isValid($accountData["password"])){
					$errorMessages[]         = implode(" ,  " , $passwordValidator->getMessages())   ;
				} else {
					$accountData["password"] = $postData["password"];			
					if(!isset($postData["confirmedpassword"])){
						$errorMessages[]     = "Des données de création de compte sont manquantes";
					} elseif($postData["confirmedpassword"] !== $accountData["password"]) {
						$errorMessages[]     = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
					}
				}
			} else {
				unset($accountData["password"]);
			} 
	
			$defaultData                     = $memberData;
			//on sauvegarde la table
			$member->setFromArray( $memberData );
			if( empty( $errorMessages ) ) {
				if( $member->save() ) {
					$accountData["lastUpdatedDate"]   = time();
		            $accountData["updateduserid"]     = $me->userid;
					$me->clearMessages();
				    if(!$me->save( $accountData )){
					    $saveErrors                   = $me->getMessages();
						foreach( $saveErrors as $type => $msg){
							     $msg                 = (is_array($msg)) ? array_shift($msg)  : $msg;
							     $errorMessages[]     = $msg;
						}
					} else {
						$memberProfile                = $modelProfile->getRow($me->userid  , true , false );
						$profileid                    = ($memberProfile )? $memberProfile->profileid : 0;
						$memberProfileCoordonnees     = ($memberProfile )? $modelCoordonnees->findRow($profileid,"profileid" , null , false ) : null ;
						if( $memberProfile ) {
							$profileData              = $accountData;
							if( isset( $profileData["profileid"] ) ) {
								unset( $profileData["profileid"] );
							}
							$profileData["userid"]    = $me->userid;
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
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("success"=>"Vos informations personnelles ont été mises à jour avec succès"));
						exit;
					}
					$this->setRedirect("Vos informations personnelles ont été mises à jour avec succès", "success");
					$this->redirect("public/members/dashboard");
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("error"=>"Aucune modification n'a été faite dans vos informations"));
						exit;
					}
					$this->setRedirect("Aucune modification n'a été faite dans vos informations","error");
					$this->redirect("public/members/dashboard");
				}
			} 
			if( count( $errorMessages ) ) {
				$defaultData   = array_merge( $postData, $accountData, $memberData);
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
		$defaultData["sexe"]               = ( !empty( $defaultData["sexe"] ) ) ? $defaultData["sexe"] : "M";
		$defaultData["birthday_day"]       = date("d", $member->birthday);
		$defaultData["birthday_month"]     = date("m", $member->birthday);
		$defaultData["birthday_year"]      = date("Y", $member->birthday);
 
		$this->view->data                  = $defaultData;
		$this->view->memberid              = $memberid;
		$this->view->title                 = sprintf("Mise à jour des informations personnelles");
		$this->view->cities                = array();
		$this->view->ville                 = ( $ville ) ? $ville->libelle : "";
		$this->render("edit")	;
	}
	
	
	public function editpwdAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$me                 = Sirah_Fabric::getUser();
		$userTable          = $me->getTable();
		$dbAdapter          = $userTable->getAdapter();
		$prefixName         = $userTable->info("namePrefix");	
		$model              = $this->getModel("member");
		$modelCity          = $this->getModel("countrycity");
		$modelProfile       = $this->getModel("profile");
		$modelCoordonnees   = $this->getModel("profilecoordonnee");
		
		if( $me->isGuest() ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Veuillez vous authentifier "));
				exit;
			}	
            $this->redirect("public/members/login");			
		} 
		$accountMember           = $model->fromuser($me->userid);
		if(!$accountMember ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Cette page n'est accessible qu'aux membres "));
				exit;
			}	
            $this->redirect("public/members/login");	
		}
		$memberid             = $accountMember->memberid;
		$member               = $model->findRow($memberid , "memberid", null , false );
		if(!$member ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> sprintf("Aucun membre valide n'a été retrouvé avec l'identifiant %s " , $memberid )));
				exit;
			}
			$this->setRedirect(sprintf("Aucun membre valide n'a été retrouvé avec l'identifiant %s " , $memberid ) , "error");
			$this->redirect("public/members/dashboard");
		}
		$defaultData         = array_merge($userTable->getData(),$member->toArray());
		$errorMessages       = array();
 
	
		if( $this->_request->isPost()  )    {
			$postData                    = $this->_request->getPost();
			$memberData                  = array_merge( $defaultData , $postData );	
            //On met à jour les informations du compte de l'utilisateur
			$myDefaultData               = $userTable->getData();
										
			//On crée les validateurs nécessaires
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                = new Zend_Filter();
			$stringFilter->addFilter(      new Zend_Filter_StringTrim());
			$stringFilter->addFilter(      new Zend_Filter_StripTags());
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$usernameValidator           = new Sirah_Validateur_Username();
			$emailValidator              = new Sirah_Validateur_Email();
			$passwordValidator           = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(4);
			
			$passwordSelect              = $dbAdapter->select()->from($userTable->info("name"), array("password"))->where("userid = ?", $me->userid );
		
		    $passwordRow                 = $dbAdapter->fetchRow( $passwordSelect);
			$oldPwd                      = ( isset($postData["password"]     ))? trim( $postData["password"] )     : "";
		    $newPwd                      = ( isset($postData["newpwd"]       ))? trim( $postData["newpwd"] )       : "";
		    $confirmation                = ( isset($postData["newpwdconfirm"]))? trim( $postData["newpwdconfirm"]) : "";
	       	if(!Sirah_User_Helper::verifyPassword( $oldPwd , $passwordRow["password"] ) ) {
				$errorMessages[]         = "Votre ancien mot de passe que vous avez saisi n'est pas valide";
			}			
			if( empty( $errorMessages ) && !$strNotEmptyValidator->isValid( $postData["newpwd"] ) ) {
				$errorMessages[]         = implode(" ; ",$passwordValidator->getMessages());
			} elseif( empty( $errorMessages ) ) {
				if( empty( $postData["newpwdconfirm"] ) ) {
					$errorMessages[]     = "Des données de création sont manquantes";
				} elseif( $confirmation!== $newPwd ) {
					$errorMessages[]     = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			}
             
			if( empty( $errorMessages ) ) {
				$userAccount             = Sirah_Fabric::getUser($me->userid);			
				if( $userAccount->setPassword( $newPwd ) ) {
					$this->setRedirect(" Votre mot de passe a été mis à jour avec succès ","success");
					$this->redirect("public/members/dashboard");
				} else {
					$errorMessages[]     = sprintf("Le changement du mot de passe a echoué");
				}				 
			} 
			if( count( $errorMessages ) ) {
				$defaultData   = array_merge( $postData, $accountData, $memberData);
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
		$this->view->data                  = $defaultData;
		$this->view->memberid              = $memberid;
		$this->view->title                 = sprintf("Changement du mot de passe");
 
		$this->render("editpwd")	;
	}
	 		 
}