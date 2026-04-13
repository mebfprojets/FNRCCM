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
 * de la validité de l'utilisateur.
 * On s'assure que ce n'est pas un vol de session
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Controller_Plugin_Authcheck extends Zend_Controller_Plugin_Abstract
{
	
	/**
	 * Permet de vérifier que l'objet d'autnetification de l'utilisateur est toujours valide
	 *
	 *
	 * @param Zend_Controller_Request_Abstract $request l'instance de la requete
	 *
	 *
	 */	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$user           = Sirah_Fabric::getUser();
		$userAuth       = $user->getAuth();

		$controllerName = $resource = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
				
		if($user->isLoggedIn() && !$userAuth->checkAuth($request->getParams())){
			$statut         = $userAuth->statut;
			$messageHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper("Message");
			
			switch($statut){
				case Sirah_User_Auth_Result::FAILURE_SECURITY_BREACH:
					$message = "Une erreur d'identité invalide ressort dans votre compte, veuillez vous reconnecter";
					break;
				 case Sirah_User_Auth_Result::FAILURE_AUTH_EXPIRED:
				 default:
				 	$message = "La durée de connexion à votre compte est dépassée, veuillez vous reconnecter";
				 	break;
			}
			$user->logout();
			$messageHelper->addMessage($message,"error");
			 
			$controllerName  = "myaccount";
			$actionName      = "login";
		}		
		// On met à jour les informations du controlleur
		$request->setModuleName(     $moduleName );
		$request->setControllerName( $controllerName );
		$request->setActionName(     $actionName );		
	}

	

  }
