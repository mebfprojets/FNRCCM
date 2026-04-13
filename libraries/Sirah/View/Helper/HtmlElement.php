<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Cette classe représente une aide de vue
 * 
 * qui permet de créer l'instance de jquery
 * 
 * générés par l'application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

 class Sirah_View_Helper_HtmlElement extends Zend_View_Helper_HtmlElement
 {
  	
  	protected  $_idAttribute  = null;
  	
  	/**
  	 * @var Sirah_View_Helper_EventHandler
  	 *      correspond au gestionnaire d'événement de l'element HTML
  	 */
  	protected $_eventHandler = null;
  	
  	
  	/**
  	 * @var array $attributes
  	 */
  	protected $_attributes    = array();
  	
  	/**
  	 * @var boolean
  	 */
  	protected $_isDisabled    = false;
  	
  	
  	/**
  	 * @param array $attributes
  	 */
  	public function setAttributes(array $attributes)
  	{
  		$this->_attributes = $attributes;
  		return $this;
  	}
  	
  	
  	/**
  	 * @return array
  	 */
  	public function getAttributes()
  	{
  		return $this->_attributes;
  	}
  	
  	/**
  	 * @return string
  	 */
  	public function getId()
  	{
  		if(isset($this->_attributes["id"])){
  			return $this->_attributes["id"];
  		}
  		return $this->_idAttribute ;
  	}
  	
  	/**
  	 * @param string $selectorid
  	 */
  	public function setId($selectorid)
  	{
  		if(!empty($selectorid)){
  			$this->_idAttribute       = $this->_normalizeId($selectorid);
  			$this->_attributes["id"]  = $this->_idAttribute; 
  		}
  		return $this ;
  	}
  	  	
  	
  	/**
  	 * Permet de mettre à jour le gestionnaire des événement javascript
  	 *
  	 * @param Sirah_View_Helper_EventHandler le gestionnaire des évenements javascript
  	 *
  	 * @return Sirah_View_Helper_Toolbar_Tool
  	 */
  	public function setEventHandler($eventHandler)
  	{
  		if(null === $eventHandler){
  			return $this;
  		}
  		$eventHandler->setView($this->view);
  		
  		if($eventHandler instanceof Sirah_View_Helper_Script_EventHandler){
  		   $this->_eventHandler  = $eventHandler;
  		}
  		return $this;
  	}
  	
  	/**
  	 * Permet de vérifier si le bouton est désactivé
  	 * @return bool
  	 */
  	public function isDisabled()
  	{
  		return $this->_isDisabled;
  	}
  	
  	/**
  	 * Permet de desactiver le boutton
  	 */
  	public function disable()
  	{
  		$this->_isDisabled  = true;
  		return $this;
  	}
  	
  	/**
  	 * Permet de d'activer le boutton
  	 *
  	 *
  	 */
  	public function enable()
  	{
  		$this->_isDisabled  = false;
  		return $this;
  	}
  	
  	
  	/**
  	 * Permet de récupérer le gestionnaire des événement javascript
  	 *
  	 * @return Sirah_View_Helper_EventHandler
  	 */
  	public function getEventHandler()
  	{
  		return $this->_eventHandler;
  	}
  	
  	
  	/**
  	 * Permet d'associer l'exécution d'un évenement javascript sur l'objet
  	 *
  	 * @param string $eventName     le type d'évenement
  	 * @param string $eventFunction La fonction javascript associée à l'événement
  	 * @param string 
  	 *
  	 * @return bool
  	 */
  	public function bindEvent($eventName , $eventFunction , $selectorid = null)
  	{
  		if(null === $selectorid){
  			$selectorid  = $this->getId();
  		}
  		if(null=== $this->_eventHandler && !empty($eventName) && !empty($eventFunction)){
  		   $this->_eventHandler  = $this->view->eventHandler();
  		}
  		if(null !== $this->_eventHandler){
  			return $this->_eventHandler->bind($selectorid , $eventName, $eventFunction);
  		}
  		return false;
  	}
  	
  	
  	/**
  	 * Permet de retirer l'exécution d'un évenement javascript sur l'objet
  	 *
  	 * @param string $eventName  le type d'évenement
  	 *
  	 * @return bool
  	 */
  	public function unbindEvent($eventName , $selectorid = null)
  	{
  		if(null === $selectorid){
  			$selectorid  = $this->getId();
  		}
  		if(null !== $this->_eventHandler){
  			return $this->_eventHandler->unbind($selectorid , $eventName);
  		}
  		return false;
  	}
  	
  	
  	/**
  	 * Méthode magique pour directement assigner des évenements
  	 *
  	 * @param string $method
  	 * @param array  $argv
  	 *
  	 * @retur mixed
  	 */
  	public function __call($method, $argv)
  	{
  	  if(preg_match("/^on([a-zA-Z]+)/i" , $method , $matches)){
  		   $eventName     = strtolower($matches[1]);
  		   $eventFunction = (isset($argv[0])) ? $argv[0] : "";
  		   $selectorid    = (isset($argv[1])) ? $argv[1] : $this->getId();
  		   if(!empty($eventFunction)){
  		   	   return $this->bindEvent($eventName , $eventFunction, $selectorid);
  		   }
  		}  		
  	}		
 }

