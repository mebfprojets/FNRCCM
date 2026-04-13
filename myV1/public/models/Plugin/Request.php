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
 * Cette classe correspond à un plugin qui stocke les différentes requetes
 *  en session
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Plugin_Request extends Zend_Controller_Plugin_Abstract
{
	private $_storage  = null;
	
	private $_current  = array();
		
	public function __construct()
	{
		if ( null == $this->_storage ) {
			 $this->_storage = new Zend_Session_Namespace("requests");
		}		
		$this->_current      = array("controller"=>"","action"=>"","module"=>"","headers"=>$_SERVER['HTTP_USER_AGENT'] );
		if( array_key_exists("HTTP_ACCEPT", $_SERVER ) ) {
			$this->_current["headers"].= $_SERVER['HTTP_USER_AGENT'];
		}		
	}
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$front          = Zend_Controller_Front::getInstance();
		$response       = $this->getResponse();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
 
		
	
	}
	     

  }
