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
 * Cette classe correspond au controlleur
 * charg� d'afficher les erreurs g�n�r�es
 * par le syst�me au cours de l'ex�cution
 * d'une tache.
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

class Admin_ErrorController extends Sirah_Controller_Default
{
	
	/**
	 * Instance de Zend_Log
	 * @var Zend_Log
	 */
	private $_logHandle = null;
	
	/**
	 * L'instance de l'exception g�n�r�e comme erreur
	 *  @var Exception instance
	 */
	private $_errorHandler;
	
	
	/**
	 * Le message g�n�r�
	 *  @var string
	 */
	private $_errorMessage;
	
	/**
	 * Le message g�n�r�
	 *  @var string
	 */
	private static $errorMessage;
	
	/**
	 * Le titre du message
	 *  @var string
	 */
	private static $errorViewTitle;
	
	/**
	 * Le code HTTP correspondant au message
	 *  @var string
	 */
	private static $httpCode;
	
	
	/**
	 *  Permet d'initialiser le controlleur
	 *  
	 *  
	 */
	public function init() 
	{
		$user = Sirah_Fabric::getUser();		
		if(Zend_Registry::isRegistered("log")){
		   $this->_logHandle  = Zend_Registry::get("log");
		 } else {
			$this->_logHandle = new Sirah_Log_Register();
		}		
		$this->_logHandle->setEventItem("user", $user->username);
		$formater = new Zend_Log_Formatter_Simple ( "Une exception est generée le %timestamp% durant la session de %user% et avait pour cause %message% \n" );
		$writer   = & $this->_logHandle->getWriter("fichier" );		
		if( $writer ){
		    $writer->setFormatter( $formater );
		}
	}
	
	/**
	 *  Avant que la requete n'entre en boucle de distribution
	 *
	 *
	 */
	public function preDispatch() 
	{		
		$response            = $this->getResponse();
		$this->_errorHandler = $error_handler = $this->_getParam("error_handler");
		self::$errorMessage  = "";
		
		switch($error_handler->type)
		{
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER :
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION :
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE :
				self::$httpCode             = 404;
				self::$errorViewTitle       = " Page Introuvable ";
				self::$errorMessage         = " La page à laquelle vous souhaitez acceder, est malheureusement indisponible ou protégée en accès. <br/>
						                        Le lien que vous avez suivi peut etre incorrect ou la page peut avoir été supprimée ou renommée.";
				break;			
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER :
				switch (get_class($this->_errorHandler->exception)) {
					case 'Zend_View_Exception' :
						self::$httpCode       = 500;
						self::$errorViewTitle = " Page Introuvable ";
						self::$errorMessage   = "La page à laquelle vous souhaitez acceder, est malheureusement indisponible ou protégée en accès. <br/>
						                         Le lien que vous avez suivi peut etre incorrect ou la page peut avoir été supprimée ou renommée.";
						break;
					
					case 'Sirah_Exception_Acl' :
						self::$httpCode      = 502;
						self::$errorViewTitle= " Accès refusé ";
						self::$errorMessage  = " Vous semblez accéder à une page protegée necessitant des permissions. Veuillez prendre attache avec notre équipe.  ";
						break;
					
					case 'Zend_Mail_Transport_Exception' :
						self::$httpCode       = 508;
						self::$errorViewTitle = " Serveur de messagerie inaccessible ";
						self::$errorMessage   = " Le serveur de messagerie n'est pas accessible ou ne reussit pas à transmettre un message. ";
						break;
					
					case 'Zend_Db_Exception' :
						self::$httpCode      = 503;
						self::$errorViewTitle= " Erreurs Techniques du système ";
						self::$errorMessage  = " Des problèmes techniques ressortent dans le fontionnement du système. Nous nous excusons pour cette défaillance et vous
						                         promettons d'y apporter une solution le plus tot possible.";
						break;
					
					case 'Sirah_Exception' :
						self::$httpCode        = 507;
						self::$errorViewTitle  = " Erreurs Techniques du système ";
						self::$errorMessage    =  " Des problèmes techniques ressortent dans le fontionnement du système. Nous nous excusons pour cette défaillance et vous
						                            promettons d'y apporter une solution le plus tot possible.";
						break;
					
					case 'Sirah_Controller_Exception' :
						self::$httpCode       = 504;
						self::$errorViewTitle = " Erreurs Techniques du système ";
						self::$errorMessage   = " Une erreur technique s'est produite dans l'exécution de votre requete. 
						                          Nous nous excusons du désagrement et vous promettons d'y apporter une solution dans de brefs délai.";
						break;
						
					case 'Sirah_Filesystem_Exception' :
						 self::$httpCode       = 505;
						 self::$errorViewTitle = " Erreurs Techniques du système ";
						 self::$errorMessage   = "Une erreur technique s'est produite dans l'exécution de votre requete. 
						                          Nous nous excusons du désagrement et vous promettons d'y apporter une solution dans de brefs délai.";
						 break;
					
					default :
						self::$httpCode        = 506;
						self::$errorViewTitle  = " Erreurs Techniques du système ";
						self::$errorMessage    = " Une erreur technique de type ".$this->_errorHandler->exception." s'est produite dans l'exécution de votre requete. 
						                           Nous nous excusons du désagrement et vous promettons d'y apporter une solution dans de brefs délai. ";
						break;
				}
				break;
		}
	}
	
	/**
	 *  L'action par defaut qui doit etre exécutée
	 *
	 *
	 */
	public function indexAction() 
	{
		$this->view->title          = self::$httpCode." : ".self::$errorViewTitle;
		$this->getResponse()->setHttpResponseCode(self::$httpCode);
		
		$errorHandler               = (null!== $this->_errorHandler) ? $this->_errorHandler : $this->_getParam("error_handler");
		$errorMessage               = self::$errorMessage;
		
		$cacheManager               = Sirah_Fabric::getCachemanager();
		if (!$cacheManager->hasCache("NavigationCache")) {
			$cache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
			$cacheManager->setCache("NavigationCache", $cache );
		} else {
			$cache = $cacheManager->getCache("NavigationCache" );
		}
		
		if(false !== ($navigationPages = $cache->load("navigationpages"))) {
			$cache->remove("navigationpages");
		}
		
		if(APPLICATION_DEBUG){
			if(!$this->_request->isXmlHttpRequest()){
			      $errorMessage    .= " <br/><br/><br/> \n \n \n "
			                       .  " <p style='text-align:justify'> <b><span style='color:red;font-size:14px;'> INFORMATIONS DE DEBOGUAGE : </b> </span> "
			                       .  " <span style='color:red;font-size:18px;'> ".$errorHandler->exception->getMessage()." </span> "
			                       .  " <b> </p> ";
			} else {
				$errorMessage      .= " ".$errorHandler->exception->getMessage();
			}
		}		
		if($this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout(true);
			$this->_helper->viewRenderer->setNoRender(true);
			$error = array("error" =>  $errorMessage);
			echo ZendX_JQuery::encodeJson($error);
			exit;
		}		
		$this->view->error_title    =  self::$errorMessage;
		$this->view->error_content  =  $errorMessage;
		$this->_logHandle->err(self::$httpCode." : ".self::$errorMessage." ".$errorHandler->exception->getMessage());
	}
	
	/**
	 *  L'action par defaut qui doit etre exécutée
	 *
	 *
	 */
	public function listAction() 
	{
		$this->view->title          = self::$httpCode." : ".self::$errorViewTitle;
		$this->getResponse()->setHttpResponseCode(self::$httpCode);
		
		$errorHandler               = (null!== $this->_errorHandler) ? $this->_errorHandler : $this->_getParam("error_handler");
		$errorMessage               = self::$errorMessage;
		
		$cacheManager               = Sirah_Fabric::getCachemanager();
		if (!$cacheManager->hasCache("NavigationCache")) {
			$cache = Sirah_Cache::getInstance("Navigation", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
			$cacheManager->setCache("NavigationCache", $cache );
		} else {
			$cache = $cacheManager->getCache("NavigationCache" );
		}
		
		if(false !== ($navigationPages = $cache->load("navigationpages"))) {
			$cache->remove("navigationpages");
		}
		
		if(APPLICATION_DEBUG){
			if(!$this->_request->isXmlHttpRequest()){
			      $errorMessage    .= " <br/><br/><br/> \n \n \n "
			                       .  " <p style='text-align:justify'> <b><span style='color:red;font-size:14px;'> INFORMATIONS DE DEBOGUAGE : </b> </span> "
			                       .  " <span style='color:red;font-size:18px;'> ".$errorHandler->exception->getMessage()." </span> "
			                       .  " <b> </p> ";
			} else {
				$errorMessage      .= " ".$errorHandler->exception->getMessage();
			}
		}		
		if($this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout(true);
			$this->_helper->viewRenderer->setNoRender(true);
			$error = array("error" =>  $errorMessage);
			echo ZendX_JQuery::encodeJson($error);
			exit;
		}		
		$this->view->error_title    =  self::$errorMessage;
		$this->view->error_content  =  $errorMessage;
		$this->_logHandle->err(self::$httpCode." : ".self::$errorMessage." ".$errorHandler->exception->getMessage());
		$this->render("index");
	}
		
	/**
	 *  Similaire à indexAction
	 *
	 *
	 */
	public function errorAction()
	{
		$this->view->title    = self::$httpCode." : ".self::$errorViewTitle;
		$this->getResponse()->setHttpResponseCode(self::$httpCode);
		
		$errorHandler         = (null!== $this->_errorHandler) ? $this->_errorHandler : $this->_getParam("error_handler");
		$errorMessage         = self::$errorMessage;
		
		if(APPLICATION_DEBUG){
			if(!$this->_request->isXmlHttpRequest()){
			      $errorMessage    .= " <br/> \n "
			                       .  " <p style='text-align:justify'> <b><font color='red' size='13px'> INFORMATIONS DE DEBOGUAGE : </b> </font> "
			                       .  " <font color='red' size='11px'> ".$errorHandler->exception->getMessage()." </font> "
			                       .  " <b> </p> ";
			} else {
				$errorMessage      .= " ".$errorHandler->exception->getMessage();
			}
		}
		
	  if( $this->_request->isXmlHttpRequest()){
		  $this->_helper->layout->disableLayout(true);
		  $this->_helper->viewRenderer->setNoRender(true);
		  $error = array("error" =>  $errorMessage);
		  echo ZendX_JQuery::encodeJson($error);
		  exit;
		}			
		$this->view->error_title    =  self::$errorMessage;
		$this->view->error_content  =  $errorMessage;
		$this->_logHandle->err( self::$httpCode." : ".self::$errorMessage." ".$errorHandler->exception->getMessage());
		$this->render("index");
	}
			
}
