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

class Sirah_Controller_Plugin_Rbac extends Zend_Controller_Plugin_Abstract
{
	
	
	/**
	 * Permet de vérifier la permission d'accès à la ressource
	 * 
	 * juste avant que la requete entre en boucle de distribution.
	 * 
	 * En cas d'echec, la requete sera re-routée sur d'autres controlleurs
	 * 
	 * @param Zend_Controller_Request_Abstract $request l'instance de la requete
	 *
	 *
     */ 
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$user           = Sirah_Fabric::getUser();
		$front          = Zend_Controller_Front::getInstance();
		$dispatcher     = $front->getDispatcher();
		$response       = $this->getResponse();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
		$cacheManager   = Sirah_Fabric::getCachemanager();
		$isAllowed      = false;
		$hasException   = false;		
		$acl            = Sirah_Fabric::getAcl("userAcl", $user->userid , 0);

		print_r( $acl->getResources()); die();
		//Si l'instance de l'ACL n'est pas créée, on génère une erreur
		if(null==$acl || !($acl instanceof Zend_Acl)){
			$exception  = new Zend_Controller_Router_Exception("L'instance de la ressource de controle d'accès est invalide" , Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER);
			$hasException     = true;
			$controllerName   = "error";
			$actionName       = "index";
			$error            = new Zend_Controller_Plugin_ErrorHandler();
		    $error->type      = "";
			$error->request   = clone($request);
			$error->exception = $exception;
			$request->setParam('error_handler', $error);				
		}
		
		//On vérifie que la ressource à laquelle on veut accéder, existe dans l'ACL
		if(!$acl->has($controllerName) && $controllerName!="error" && $controllerName!="index" && $user->isLoggedIn()){
			$notFoundException= new Zend_Controller_Router_Exception(" Vous tentez d'accéder à une page qui n'existe plus ");			
			$hasException     = true;
			$controllerName   = "error";
			$actionName       = "index";
			$error            = new Zend_Controller_Plugin_ErrorHandler();
			$error->type      = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
			$error->request   = clone($request);
			$error->exception = $notFoundException;
			$request->setParam('error_handler', $error);				
		} 	
			
		if($cacheManager->hasCache("Acl")){
			$aclCache  = $cacheManager->getCache("Acl");
			if( false !== $aclCache->load("aclAllowed_".$user->userid."_".$controllerName."_".$actionName)){
				$isAllowed = true;
			}
		}		
	   if(!$isAllowed && !$hasException){
			$isAllowed = $acl->isAuthorized( $controllerName , $actionName );			
			if( $isAllowed ){
				if( $cacheManager->hasCache("Acl") ){
					$aclCache  = $cacheManager->getCache("Acl");
					$aclCache->save("isAllowed" , "aclAllowed_".$user->userid."_".$controllerName."_".$actionName);
				}
			} else {				    				    
			   if( !$user->isLoggedIn() ) {
				    $controllerName  = "account";
				    $actionName      = "login";
				} else {
				  $notFoundException = new Zend_Controller_Router_Exception("La page est certainement protégée en accès ou est indisponible");			
			      $hasException      = true;
			      $controllerName    = "error";
			      $actionName        = "index";
			      $error             = new Zend_Controller_Plugin_ErrorHandler();
			      $error->type       = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
			      $error->request    = clone($request);
			      $error->exception  = $notFoundException;
			      $request->setParam('error_handler', $error);				
			   }
			}
		} 
		
		/*else {
		    $notFoundException = new Zend_Controller_Router_Exception("La page est certainement protégée en accès ou est indisponible");			
			      $hasException      = true;
			      $controllerName    = "error";
			      $actionName        = "index";
			      $error             = new Zend_Controller_Plugin_ErrorHandler();
			      $error->type       = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
			      $error->request    = clone($request);
			      $error->exception  = $notFoundException;
			      $request->setParam('error_handler', $error);			
		} */
		// On met à jour les informations du controlleur
		$request->setModuleName($moduleName);
		$request->setControllerName($controllerName);
		$request->setActionName($actionName);
	}	    
  }
