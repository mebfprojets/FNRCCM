<?php

require 'vendor/autoload.php';

use \ReCaptcha\ReCaptcha as gReCaptcha;
use \ReCaptcha\RequestMethod\CurlPost as gReCaptchaMethodPost;

class RecoverpwdController extends Sirah_Controller_Default
{
		
	
	/**
	 * L'action qui permet de lancer la procedure
	 *
	 * de recuperation du mot de passe de l'utilisateur.
	 *
	 */
	public function initAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title    = "Réinitialisation du mot de passe";
	
		//On met à jour le template 
		//$this->_helper->layout->setLayout("login")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                            = &$this->view;
		$pwdSession                      = new Zend_Session_Namespace("lostpassword");
		$identity                        = null;
		$errorMessages                   = array();
				
		if(!$pwdSession->initialised){
			$pwdSession->setExpirationSeconds(24*3600);
			$pwdSession->initialised     = true;
			$pwdSession->lostpwdtoken    = null;
			$pwdSession->myPwdResetToken = null;
			$pwdSession->recovercode     = null;
		}	
		//Après avoir posté les données du formulaire
		if( $this->_request->isPost() ) {			
			//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
			if(!isset($pwdSession->lostpwdtoken) || !$this->_hasParam($pwdSession->lostpwdtoken)){
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "Formulaire invalide"));
					exit;
				}
				die("Formulaire Invalide");
			}		
            //On crée les filtres qui seront utilisés sur les données du formulaire
			$strNotEmptyValidator    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$stringFilter            = new Zend_Filter();
			$stringFilter->addFilter(  new Zend_Filter_StringTrim());
			$stringFilter->addFilter(  new Zend_Filter_StripTags());

			
			$postData                = $this->_request->getPost();
			$gReCaptchaResponse	     = (isset($postData["g-recaptcha-response"]))? $postData["g-recaptcha-response"]            : null;	
            $identity                = (isset($postData["identity"]            ))? $stringFilter->filter($postData["identity"]) : null;	
			if(!$strNotEmptyValidator->isValid($gReCaptchaResponse)) {
				$errorMessages[]     =  "Veuillez valider le captcha de sécurité";
			} elseif( $strNotEmptyValidator->isValid($gCaptchaResponse)) {
				$recaptcha           = new gReCaptcha(API_GOOGLE_RECAPTCHA_SECRETE_KEY, new gReCaptchaMethodPost());
				$recaptchaResponse   = $recaptcha->verify($gReCaptchaResponse, $_SERVER['REMOTE_ADDR']);
				if(!$recaptchaResponse->isSuccess()) {
					$errorMessages[] = "Captcha Invalide !";
				}
			}	
			if( count($errorMessages) ) {
				$errorMessage        = implode(",", $errorMessages);
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"=>$errorMessage));
					exit;
				}
				$this->setRedirect($errorMessage,"error");
				$this->redirect("public/recoverpwd/init");
			}
			$user                    = $newUser = Sirah_Fabric::getUser($identity);			
			if( $user->userid ) {
				$mailer              = Sirah_Fabric::getMailer();
				$config              = Sirah_Fabric::getConfig();
				$password            = $user->getPassword();
				$userid              = $user->userid;
				$defaultFromEmail    = $config["resources"]["mail"]["defaultFrom"]["email"];
                $defaultFromName     = $config["resources"]["mail"]["defaultFrom"]["name"];
				$recoverToken        = Sirah_Functions_Generator::getAlpha(8);
  	            $pwdResetToken       = Sirah_User_Helper::cryptPassword($password,$recoverToken).Sirah_User_Helper::cryptPassword($user->username,$recoverToken);
                $recoverCode         = intval((intval(Sirah_Functions_Generator::getInteger(20))*time())/intval(Sirah_Functions_Generator::getInteger(10)));
                
                //$linkRecover         = Sirah_Functions::url($this->view->url(array("controller"=>"recoverpwd","action"=>"checkcode"),"default",true),"myPwdResetToken=".$pwdResetToken."&uid=".$userid);
                $linkRecover         = VIEW_BASE_URI."/public/recoverpwd/checkcode/myPwdResetToken/".$pwdResetToken."/uid/".$userid; 
				$linkContact         = VIEW_BASE_URI."/public/project/contacts/uid/".$userid; 	
				
                $recoverMsg          = " Bonjour Mr/Mrs ".$user->lastname." ".$user->firstname."  <br/>
										Pour renouveller votre mot de passe, veuillez suivre les instructions ci-dessous  : <br/>
										A partir du navigateur sur lequel vous avez initié la requête de réinitialisation :
									    <ul>
											<li> 1. Cliquer sur le lien suivant : <a title=\"Cliquez sur ce lien pour changer votre mot de passe\" href=\"".$linkRecover."\"> Renouveller mon mot de passe; </a></li>
											<li> 2. Ensuite, Copier le code suivant : <b> ".$recoverCode." </b> et collez dans le champ du formulaire de la page ouverte via le lien ci-dessus;</li>
											<li> Si vous avez des difficultés ,   <a title=\"Veuillez nous contacter\" href=\"".$linkContact."\"> Contactez-nous pour aide </a></li>
										</ul>";
				$msgSubject         = "Vous souhaitez changer votre mot de passe sur la plateforme";						
                $msgPartialData     = array("subject"=>$msgSubject,"message"=>$recoverMsg,"logoMsg"=> APPLICATION_STRUCTURE_LOGO,"replyToEmail"=>$defaultFromEmail,"replyToName"=>$defaultFromName,"replyToTel"=>"","replyToSiteWeb"=>"https://www.fichiernationalrccm.com/project/infos","toName" => sprintf("%s %s", $newUser->lastname, $newUser->firstname),"toEmail"=>  $newUser->email );
			    $recoverMsgBody     = $view->partial("mailtpl/default.phtml" , $msgPartialData );
                
                
                $mailer->setFrom($defaultFromEmail,stripslashes($defaultFromName));
                $mailer->setSubject($msgSubject);
                $mailer->addTo($user->email,stripslashes($user->lastname." ".$user->firstname));
                $mailer->setBodyHtml($recoverMsgBody);
                
                //On tente d'envoyer un email à l'utilisateur
                try{
                	$mailer->send();
                } catch(Exception $e) {
                	$errorMsg       = " Nous avons tenté de vous transmettre un email de renouvellement de votre mot de passe sans succès, veuillez réessayer l'opération";
                	if(APPLICATION_DEBUG){
                	   $errorMsg   .=" \n <br/> , l'erreur suivante a été retournée par le serveur de messagerie : ".$e->getMessage();
                	}
                	$this->setRedirect($errorMsg,"error");
                	$this->redirect("public/recoverpwd/init");
                }               
                $pwdSession->recovercode       = $recoverCode;
                $pwdSession->myPwdResetToken   = $pwdResetToken;
                $pwdSession->userid            = $user->userid;
                $lastSessionId                 = Zend_Session::getId();
                $userTable                     = $user->getTable();
                $userTable->setLastSessionId( $lastSessionId );
                
                $this->getHelper("Message")->addMessage(" Un mail vous a été transmis dans votre compte de messagerie $user->email. Vérifiez dans vos messages reçus et dans les spams pour suivre des instructions.Dans 24H, cette opération ne sera plus autorisée ","success");	
                $this->redirect("public/recoverpwd/checkcode/uid/".$userid."/myPwdResetToken/".$pwdResetToken)	;		
			} else {
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "L'identifiant que vous avez indiqué n'est pas reconnu par notre système"));
					exit;
				}
				$this->getHelper("Message")->addMessage(" L'identifiant ou l'adresse email que vous avez indiqué n'est pas reconnu par notre système ","error");				
			}													
		}		
		$pwdSession->lostpwdtoken = Sirah_User_Helper::getToken(15).time();		
		$this->view->formtoken    = $pwdSession->lostpwdtoken;
		$this->view->identity     = $identity;

		$this->render("default");	
	}
	
	
	public function checkcodeAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title = "Vérification du code de renouvellement";
		$pwdRememberToken  = trim($this->_getParam("myPwdResetToken",null));		
		$pwdSession        = new Zend_Session_Namespace("lostpassword");
		$uid               = intval($this->_getParam("uid"));
		$errorMessages     = array();
		$code              = null;
		$user              = Sirah_Fabric::getUser($uid);
		
		if(!$user->userid){
			$this->setRedirect("Le lien que vous avez suivi est incorrect, vous pouvez reprendre l'opération","error");
			$this->redirect("public/recoverpwd/init");
		}
		
		//On met à jour le template pour la connexion à un compte
		$this->_helper->layout->setLayout("login");		
		if(!isset($pwdSession->myPwdResetToken)){
			$sessionData                 = $user->getSessionData();
			$pwdSession->myPwdResetToken = isset($sessionData["lostpassword"]["myPwdResetToken"]) ? $sessionData["lostpassword"]["myPwdResetToken"] : null;
			$pwdSession->checkcodetoken  = isset($sessionData["lostpassword"]["checkcodetoken"])  ? $sessionData["lostpassword"]["checkcodetoken"]  : null;
			$pwdSession->recovercode     = isset($sessionData["lostpassword"]["recovercode"])     ? $sessionData["lostpassword"]["recovercode"]     : null;
			$pwdSession->allowchange     = isset($sessionData["lostpassword"]["allowchange "])    ? $sessionData["lostpassword"]["allowchange"]     : false;
			$pwdSession->userid          = $uid;
		}		
		if( $pwdSession->myPwdResetToken != $pwdRememberToken){
		    $this->setRedirect("Le lien que vous avez suivi est incorrect ou expiré, vous pouvez reprendre l'opération","error");
		    $this->redirect("public/recoverpwd/init");
		}		
		if( $this->_request->isPost()){			
			//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
			if(!isset($pwdSession->checkcodetoken) || !$this->_hasParam($pwdSession->checkcodetoken)){
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "Formulaire invalide"));
					exit;
				}
				die("Formulaire Invalide");
			}
			$postData               = $this->_request->getPost();
			$code                   = trim($postData["code"]);
			
			if( $code != $pwdSession->recovercode){
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"=> "Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire"));
					exit;
				}
			    $this->getHelper("Message")->addMessage(" Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire ","error");
			}	else {
				$pwdSession->allowchange  = true;
				$this->setRedirect("La vérification du code s'est produite avec succès, veuillez indiquer le nouveau mot de passe","success");
				$this->redirect("public/recoverpwd/reset");
			}		
		}		
		$pwdSession->checkcodetoken    = Sirah_User_Helper::getToken(15).time();
		$this->view->formtoken         = $pwdSession->checkcodetoken;
		$this->view->code              = $code;	
		$this->view->myPwdResetToken   = $pwdSession->myPwdResetToken;
		$this->view->userid            = $uid;
	}
	
	
	public function resetAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title         = " Renouvellement du mot de passe de votre compte ";
		$pwdSession                = new Zend_Session_Namespace("lostpassword");
		$errorMessages             = array();
		
		//On met à jour le template pour la connexion à un compte
		$this->_helper->layout->setLayout("login");
		
		if(!isset($pwdSession->allowchange)){
			$pwdSession->allowchange= false;
		}		
		if( false===$pwdSession->allowchange){
			if( $this->_request->isXmlHttpRequest()){
				echo ZendX_JQuery::encodeJson(array("error"  => "Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire"));
				exit;
			}
			$this->setRedirect(" Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire ","error");
			$this->redirect("public/recoverpwd/init");
		}
		
		if( $this->_request->isPost() ) {			
			//Une astuce pour contourner une attaque par force brute, en utilisant le jeton du formulaire
			if(!isset($pwdSession->resetoken) || !$this->_hasParam($pwdSession->resetoken)){
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "Formulaire invalide"));
					exit;
				}
				die("Formulaire Invalide");
			}
			
			$postData            = $this->_request->getPost();
			
			$passwordValidator   = new Sirah_Validateur_Password();
			$passwordValidator->setMinlength(4);
			
			$postData["password"]          = trim($postData["password"]);
			$postData["confirmedpassword"] = trim($postData["confirmedpassword"]);
					
			if(!$passwordValidator->isValid($postData["password"])){
				$errorMessages[]           = implode(" ; ", $passwordValidator->getMessages());
			} else {			
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]       = "Des données de création sont manquantes";
				} elseif($postData["confirmedpassword"] !== $postData["password"]) {
					$errorMessages[]       = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			}		
			if( count($errorMessages)){
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => implode(" ; ",$errorMessages)));
					exit;
				}
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}  else {
				$userid       = $pwdSession->userid;
				$user         = Sirah_Fabric::getUser($userid);
				
				if( $user->setPassword($postData["password"])){
					$this->setRedirect(" Votre mot de passe a été mis à jour avec succès ","success");
					$this->redirect("public/account/login");
				} else {
					$errorMsg     = " La mise à jour du mot de passe n'a pas fonctionné ";
					if(APPLICATION_DEBUG){
					   $errorMsg .= " , pour les raisons suivantes : ". implode(" , ",$user->getMessage());
					}
					$this->setRedirect($errorMsg."; veuillez réessayer ","error");
					$this->redirect("public/account/reset");
				}				
			}			
		}				
		$pwdSession->resetoken  = Sirah_User_Helper::getToken(15).time();
		$this->view->formtoken  = $pwdSession->resetoken;	
	}
}