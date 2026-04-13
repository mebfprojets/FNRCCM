<?php

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
		$this->view->title    = "VOTRE COMPTE";
		$this->view->subtitle = "Réinitialisation de votre mot de passe";
	
		//On met à jour le template 
		$this->_helper->layout->setLayout("login");
		$pwdSession        = new Zend_Session_Namespace("lostpassword");
		$identity          = null;
				
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
			$postData         = $this->_request->getPost();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());

			$identity         = (isset($postData["identity"])) ? $stringFilter->filter($postData["identity"]) : null;			
			$user             = Sirah_Fabric::getUser($identity);			
			if( $user->userid ) {
				$mailer       = Sirah_Fabric::getMailer();
				$config       = Sirah_Fabric::getConfig();
				$password     = $user->getPassword();
				$userid       = $user->userid;
				$recoverToken = Sirah_Functions_Generator::getAlpha(12);
  	            $pwdResetToken= Sirah_User_Helper::cryptPassword($password,$recoverToken) . time() . Sirah_User_Helper::cryptPassword($user->username,$recoverToken);
                $recoverCode  = intval((intval(Sirah_Functions_Generator::getInteger(20))*time())/intval(Sirah_Functions_Generator::getInteger(10)));
                
                $linkRecover  = Sirah_Functions::url($this->view->url(array("controller"=>"recoverpwd","action"=>"checkcode")),"myPwdResetToken=".$pwdResetToken."&uid=".$userid);
                $linkContact  = Sirah_Functions::url($this->view->url(array("controller"=>"help","action"=>"contactadmin")),"subject=resetpwd&myPwdResetToken=".$pwdResetToken."&uid=".$userid);
                
                $recoverMsg   = " Bonjour Mr/Mrs ".$user->lastname." ".$user->firstname."  <br/>
                                  Pour renouveller votre mot de passe, veuillez suivre les instructions ci-dessous. <br/>
                                  A partir du navigateur sur lequel vous avez initié cette requete :
                                       <ul>
                                           <li> Cliquer  sur ce lien : <a href=\"".$linkRecover."\"> Renouveller mon mot de passe </a> ou copiez-collez cette adresse
                                                dans la barre d'adresse du meme navigateur <b> $linkRecover </b>  </li>
                                           <li> Copier - Coller le code suivant dans le champ du formulaire de la page <b> ".$recoverCode." </b>:
                                           <li> Si le problème persiste ou que vous avez des incompréhensions, <a href=\"".$linkContact."\"> Contactez-nous pour aide </a>
                                       </ul>";
                
                $defaultToEmail = $config["resources"]["mail"]["defaultFrom"]["email"];
                $defaultToName  = $config["resources"]["mail"]["defaultFrom"]["name"];
                
                $mailer->setFrom($defaultToEmail,stripslashes($defaultToName));
                $mailer->setSubject("Vous souhaitez changer votre mot de passe");
                $mailer->addTo($user->email,stripslashes($user->lastname." ".$user->firstname));
                $mailer->setBodyHtml($recoverMsg);
                
                //On tente d'envoyer un email à l'utilisateur
                try{
                	$mailer->send();
                } catch(Exception $e) {
                	$errorMsg  = " Nous avons tenté de vous transmettre un email de renouvellement de votre mot de passe sans succès, veuillez réessayer l'opération";
                	if(APPLICATION_DEBUG){
                	   $errorMsg.=" \n <br/> , l'erreur suivante a été retournée par le serveur de messagerie : ".$e->getMessage();
                	}
                	$this->setRedirect($errorMsg,"error");
                	$this->redirect("recoverpwd/init");
                }               
                $pwdSession->recovercode       = $recoverCode;
                $pwdSession->myPwdResetToken   = $pwdResetToken;
                $pwdSession->userid            = $user->userid;
                $lastSessionId                 = Zend_Session::getId();
                $userTable                     = $user->getTable();
                $userTable->setLastSessionId( $lastSessionId );
                
                $this->getHelper("Message")->addMessage(" Un email vous a été envoyé dans votre compte de messagerie $user->email. Vérifiez dans vos messages reçus et dans les spams pour suivre des instructions.
                		                                  Dans 24H, cette opération ne sera plus autorisée ","success");				
			} else {
				if( $this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "L'identifiant que vous avez indiqué n'est pas reconnu par notre système"));
					exit;
				}
				$this->getHelper("Message")->addMessage(" L'identifiant que vous avez indiqué n'est pas reconnu par notre système ","error");				
			}													
		}		
		$pwdSession->lostpwdtoken = Sirah_User_Helper::getToken(15).time();		
		$this->view->formtoken    = $pwdSession->lostpwdtoken;
		$this->view->identity     = $identity;

		$this->render("default");	
	}
	
	
	public function checkcodeAction()
	{
		$this->view->title = "Vérification du code de renouvellement";
		$pwdRememberToken  = trim($this->_getParam("myPwdResetToken",null));		
		$pwdSession        = new Zend_Session_Namespace("lostpassword");
		$uid               = intval($this->_getParam("uid"));
		$errorMessages     = array();
		$code              = null;
		$user              = Sirah_Fabric::getUser($uid);
		
		if(!$user->userid){
			$this->setRedirect("Le lien que vous avez suivi est incorrect, vous pouvez reprendre l'opération","error");
			$this->redirect("recoverpwd/init");
		}
		
		//On met à jour le template pour la connexion à un compte
		$this->_helper->layout->setLayout("login");		
		if(!isset($pwdSession->myPwdResetToken)){
			$sessionData                 = $user->getSessionData();
			$pwdSession->myPwdResetToken = isset($sessionData["lostpassword"]["myPwdResetToken"]) ? $sessionData["lostpassword"]["myPwdResetToken"] :null;
			$pwdSession->checkcodetoken  = isset($sessionData["lostpassword"]["checkcodetoken"])  ? $sessionData["lostpassword"]["checkcodetoken"] :null;
			$pwdSession->recovercode     = isset($sessionData["lostpassword"]["recovercode"])     ? $sessionData["lostpassword"]["recovercode"] :null;
			$pwdSession->allowchange     = isset($sessionData["lostpassword"]["allowchange "])    ? $sessionData["lostpassword"]["allowchange"] :false;
			$pwdSession->userid          = $uid;
		}		
		if($pwdSession->myPwdResetToken != $pwdRememberToken){
		   $this->setRedirect("Le lien que vous avez suivi est incorrect ou expiré, vous pouvez reprendre l'opération","error");
		   $this->redirect("recoverpwd/init");
		}		
		if($this->_request->isPost()){			
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
			
			if($code != $pwdSession->recovercode){
				if($this->_request->isXmlHttpRequest()){
					echo ZendX_JQuery::encodeJson(array("error"  => "Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire"));
					exit;
				}
			   $this->getHelper("Message")->addMessage(" Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire ","error");
			}	else {
				$pwdSession->allowchange  = true;
				$this->setRedirect("La vérification du code s'est produite avec succès, veuillez indiquer le nouveau mot de passe","success");
				$this->redirect("recoverpwd/reset");
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
		$this->view->title         = " Renouvellement du mot de passe de votre compte ";
		$pwdSession                = new Zend_Session_Namespace("lostpassword");
		$errorMessages             = array();
		
		//On met à jour le template pour la connexion à un compte
		$this->_helper->layout->setLayout("login");
		
		if(!isset($pwdSession->allowchange)){
			$pwdSession->allowchange= false;
		}		
		if(false===$pwdSession->allowchange){
			if( $this->_request->isXmlHttpRequest()){
				echo ZendX_JQuery::encodeJson(array("error"  => "Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire"));
				exit;
			}
			$this->setRedirect(" Le code de renouvellement n'est pas correct, vérifiez votre email et copiez-collez le code dans le champ du formulaire ","error");
			$this->redirect("recoverpwd/init");
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
				$errorMessages[]           = implode(" ; ",$passwordValidator->getMessages());
			} else {			
				if(!isset($postData["confirmedpassword"])){
					$errorMessages[]       = "Des données de création sont manquantes";
				} elseif($postData["confirmedpassword"] !== $postData["password"]) {
					$errorMessages[]       = "Vos mots de passe ne correspondent pas, veuillez re-saisir ";
				}
			}		
			if(count($errorMessages)){
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
					$this->redirect("account/login");
				} else {
					$errorMsg     = " La mise à jour du mot de passe n'a pas fonctionné ";
					if(APPLICATION_DEBUG){
					   $errorMsg .= " , pour les raisons suivantes : ". implode(" , ",$user->getMessage());
					}
					$this->setRedirect($errorMsg."; veuillez réessayer ","error");
					$this->redirect("account/reset");
				}				
			}			
		}				
		$pwdSession->resetoken  = Sirah_User_Helper::getToken(15).time();
		$this->view->formtoken  = $pwdSession->resetoken;	
	}
}