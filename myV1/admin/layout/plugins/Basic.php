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

class LayoutPlugin_Basic extends Zend_Controller_Plugin_Abstract
{
	
	public function routeShutdown( Zend_Controller_Request_Abstract $request )
	{
		$front          = Zend_Controller_Front::getInstance();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
		$me             = Sirah_Fabric::getUser();
		$errorPlugin    = $front->getPlugin("Zend_Controller_Plugin_ErrorHandler");
		if( FALSE != $errorPlugin )
			$errorPlugin->setErrorHandlerModule('default');
		
	}
	
	
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
		
	}	     
  }
