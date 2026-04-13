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
 * Cette classe correspond à un plugin de vérification
 * des droits d'accès à une ressource de l'application
 * En cas d'interdiction d'accès, il route la requete
 * sur la ressource de notification du refus d'accès
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Plugin_AppUsertasks extends Zend_Controller_Plugin_Abstract
{
	
	
	 
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$user              = $me = Sirah_Fabric::getUser();
		$response          = $this->getResponse();
		$controllerName    = $request->getControllerName();
		$moduleName        = $request->getModuleName();
		$actionName        = $request->getActionName();
		$apiDatabases      = array();
		$hasException      = false;	
		$errorMessages     = array();
		
		if(($moduleName=="admin") && $me->userid) {
			$appConfigSession  = new Zend_Session_Namespace("AppConfig");
			//unset($appConfigSession->usertasks);
			//var_dump($appConfigSession->resources); die();
			if(!isset( $appConfigSession->usertasks)) {
				$model                                             = new Model_Demanderequest();
				$appConfigSession->usertasks                       = new stdClass();
				$appConfigSession->usertasks->hasRead              = false;
				$appConfigSession->usertasks->firstPush            = true;
				$appConfigSession->usertasks->nonProcessedRequests = $model->nonProcessedRequests(0,$me->userid,0,500);
				$resourceExpirationTMS                             = 86400;
				$appConfigSession->setExpirationSeconds($resourceExpirationTMS, 'usertasks');
			}       
			if(!isset($appConfigSession->usertasks->firstPush) || (isset($appConfigSession->usertasks->firstPush) && ($appConfigSession->usertasks->firstPush==true) && count($appConfigSession->usertasks->nonProcessedRequests) )) {
			    $moduleName      = "admin";
				$controllerName  = "demandes";
				$actionName      = "tasks";
				$appConfigSession->usertasks->firstPush = false;
			}				
 
			// On met à jour les informations du controlleur				
			$request->setModuleName(      $moduleName);
			$request->setControllerName(  $controllerName);
			$request->setActionName(      $actionName);	
		}	
	}	     
}
