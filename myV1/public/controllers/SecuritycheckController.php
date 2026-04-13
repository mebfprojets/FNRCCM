<?php

class SecuritycheckController extends Sirah_Controller_Default
{
	
	
	public function reauthenticateAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title  =  " Cette requete demande un controle d'identité ";
		$errorMessages      = array();
		$user               = Sirah_Fabric::getUser();
		$doneUri            = $this->_getParam("done");
		$defaultSession     = new Zend_Session_Namespace("authentication");
		
		$this->_helper->layout->setLayout("base");	
		
		if(!$defaultSession->initialised){
			$defaultSession->initialised = true;
			$defaultSession->checks      = 0;
			$defaultSession->setExpirationSeconds(864000);
		}		
		if(!Zend_Uri::check($doneUri)){
			$errorMessages[] = " L'url de rédirection après le controle de sécurité, est invalide";
		}		
		if(!$user->isLoggedIn()){
			$this->redirect("public/account/login");
		}
		
		if($this->_request->isPost() && empty($errorMessages)){			
		   $postData    = $this->_request->getPost();
		   
		   $checkingPwd = (isset($postData["credential"]))  ? $postData["credential"] : null;
		   $userPwd     = $user->getPassword();
		   
		   if(Sirah_User_Helper::verifyPassword($checkingPwd,$userPwd)){
		   	  $defaultSession->isValid = true;
		   	  $this->redirect($doneUri);
		   }
		   $errorMessages[] = " Le controle de votre identité a echoué " ;
		}
		if($defaultSession->checks >= 5){
		   $user->logout();
		   $this->redirect("public/account/login");
		}	
		if($defaultSession->checks == 4){
		   $errorMessages[]                 = "  Vous etes à votre dernière vérification ";
		} else {
			if(!isset($errorMessages["message"])){
				$authorizedChecks           = intval(5 - intval($defaultSession->checks)); 
				$errorMessages["message"]   = array();
			}
			$errorMessages["message"][]     = " Vous avez droit à ".$authorizedChecks." vérifications ";
		}
		$defaultSession->checks++;
		$this->view->errorMessages  = $errorMessages;
		$this->view->user           = $user;
		$this->view->checks         = $defaultSession->checks;		
		$this->render("checkauth");
	}
	
	
	public function captchaAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title  =  " Controle de sécurité : Saisissez le code qui apparait dans l'image ";
		
		$imgDir             = realpath( DOCUMENTS_PATH  ."/../../myTpl/public/images/captchas/")	;
		//print_r($imgDir); die();
		$imgUrl             = ROOT_PATH ."/myTpl/public/images/captchas/";
		$captcha            = $this->_createCaptcha( 8 , "trebucit.ttf" , $imgDir , $imgUrl );
		$errorMessages      = array();
		$doneUri            = $this->_getParam("done");
		
		if(!Zend_Uri::check($doneUri)){
		    $errorMessages[]= " L'url de rédirection après le controle de sécurité, est invalide";
		}
		
		$defaultSession     = new Zend_Session_Namespace("captchacheck");
		
		if(!$defaultSession->initialised){
			$defaultSession->initialised = true;
			$defaultSession->checks      = 0;
			$defaultSession->setExpirationSeconds(864000);
			$defaultSession->done        = $doneUri ;
		}		
		if( $this->_request->isPost() && empty($errorMessages)){
			$postData                    = $this->_request->getPost();
			$captchainput                = $postData["captcha"];
			$captchatime                 = (isset($postData["captchatime"]))  ? $postData["captchatime"]:0;
				
			if(!$captcha->isValid($captchainput)){
				$captchaMessages         = $captcha->getMessages();
				$errorMessages[]         = "La vérification du code qui apparait dans l'image a echoué";
				if(APPLICATION_DEBUG){
					$errorMessages[]     = (is_array($captchaMessages)) ? implode(" ",$captchaMessages) : $captchaMessages;
				}
			}
			if(empty($errorMessages)){
				$defaultSession->isValid = true;
				$this->redirect($doneUri);
			}
		}		
		if( $defaultSession->checks >= 5){
			$user  = Sirah_Fabric::getUser();
			if($user->isLoggedIn()){
			   $defaultSession->checks = 0;
			   $afterReauthDone        = Sirah_Functions::url($this->view->url(array("controller" => "securitycheck", "action"  => "captcha")));
			   $this->setRedirect("Le controle de sécurité a echoué, veuillez vous authentifier","error");
			   $this->redirect("public/securitycheck/reauthenticate/?done=".$afterReauthDone);
			}
			throw new Sirah_Controller_Exception(" Le controle de sécurité a echoué, veuillez patienter quelques minutes ");
		}		
		$defaultSession->checks++;
		$this->view->captcha        = $captcha;
		$this->view->errorMessages  = $errorMessages;		
	}
	 
}
