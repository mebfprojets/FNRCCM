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

class Plugin_Rbac extends Zend_Controller_Plugin_Abstract
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
		$user           = $me = Sirah_Fabric::getUser();
		$response       = $this->getResponse();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
	    $params         = $request->getParams();
		
		$isAllowed      = false;
		$hasException   = false;	
        $userid         = $user->userid;		
		$userRole       = $user->getRole();
		$acl            = Sirah_Fabric::getAcl("userAcl", $userid, 0);
		$roleAcl        = Sirah_Fabric::getAcl("roleAcl", 0      , $userRole);	
		
		if( $moduleName == "public" || $moduleName=="api") {
			$isAllowed  = true;			
		}
		
		//Si l'instance de l'ACL n'est pas créée, on génère une erreur
		if( null==$acl || !($acl instanceof Zend_Acl ) ) {
			$exception        = new Zend_Controller_Router_Exception("L'instance de la ressource de controle d'accès est invalide", Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER);
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
		if(($moduleName=="admin") && ( $roleAcl && !$roleAcl->has( $controllerName ) ) && !$acl->has( $controllerName ) 
	        && $controllerName!="error" && $controllerName!="index" && $user->isLoggedIn() ) {
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
	   if( !intval( $isAllowed ) && !$hasException ) {
			$isAllowed = ($userid)?$acl->isAuthorized( $controllerName , $actionName ) : false;					
			if( !intval( $isAllowed ) ){				 		    				    
			    if(!$user->isLoggedIn() ) {			   	
				    $controllerName     = "account";
				    $actionName         = "login";
				    $currentUri         = $request->getScheme(). "://" .$request->getHttpHost(). $request->getRequestUri();
				    $request->setParam("continue", $currentUri );
				} else {
				    $notFoundException  = new Zend_Controller_Router_Exception("La page que vous recherchez est indisponible ou est protégée en accès");			
			        $hasException       = true;
			        $controllerName     = "error";
			        $actionName         = "index";
			        $error              = new Zend_Controller_Plugin_ErrorHandler();
			        $error->type        = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
			        $error->request     = clone($request);
			        $error->exception   = $notFoundException;
			        $request->setParam('error_handler', $error);				
			    }
			}
		} 
        if( $moduleName=="admin" && !$me->isAdministrateur() && !$me->isAdmin() && !isset($params["admin_token"])) {
			$exception        = new Zend_Controller_Router_Exception("Page Introuvable", Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER);
			$hasException     = true;
			$controllerName   = "error";
			$actionName       = "index";
			$moduleName       = "public";
			$error            = new Zend_Controller_Plugin_ErrorHandler();
			$error->setErrorHandlerModule('public');
		    $error->type      = "";
			$error->request   = clone($request);
			$error->exception = $exception;
			$request->setModuleName("public");
			$request->setParam('error_handler', $error);
			$isAllowed      = false;
		}		
		// On met à jour les informations du controlleur				
		$request->setModuleName(      $moduleName);
		$request->setControllerName(  $controllerName);
		$request->setActionName(      $actionName);
	}	     
  }
