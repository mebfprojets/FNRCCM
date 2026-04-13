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
 * qui correspond au gestionnaire des évenements javascripts
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

 class Sirah_View_Helper_Script_EventHandler extends Sirah_View_Helper_HtmlElement
 {
  	
 	/**
 	 * @var array
 	 */
  	protected $_events        = array();
  	
  	
  	/**
  	 * @var array
  	 */
  	protected $_allowedEvents = array("click", "change","blur","focus","focusin", "focusout","load","hover",
  			                          "resize","scroll" ,"unload", "dblclick","mouseup ","mousemove","mouseove",
  			                          "mouseenter","mouseout","keypress","keydown","keyup","submit");
  	
  	
  	protected $_defaultFunctionLoaded = false;
  	
  	
  	protected $_scripts   = array();
  	 	  	
  	
  	/**
  	 * 
  	 *
  	 * @param array
  	 */
  	public function eventHandler($events = array())
  	{
  		$eventHandler  = new static();
  		$events  = (array)$events;
  		if(!empty($events)){
  			$eventHandler->setEvents($events);
  		}
  		if(null==$eventHandler->view){
  			$eventHandler->view  = Zend_Layout::getMvcInstance()->getView();
  		}
  		return $eventHandler;
  	}
  	
  	/**
  	 * Permet de charger les fonctions javascript 
  	 * par defaut.
  	 *
  	 * @return 
  	 */
  	public function loadDefaultFunctions()
  	{
  		if($this->_defaultFunctionLoaded){
  			return;
  		}
  		$eventsFn  = "
  		               function onLoad(fn) 
  		               {
                           if (document.addEventListener){
                               document.addEventListener('DOMContentLoaded' , fn , false );
                               window.addEventListener('load', fn, false );
                           }  else if (document.attachEvent) {
	                           document.attachEvent( 'onreadystatechange', fn );			
			                   window.attachEvent('onload', fn );
                           }
                       } \n 
  	                  function addEvent(selector, event, fn) 
  	                  {
                         if (selector.addEventListener){
                             selector.addEventListener(event, fn, false);
                          } else if (selector.attachEvent) {
                             selector.attachEvent('on' + event, fn);
                          }
                       } \n ";
  	   
  	   $this->view->headScript()->prependScript($eventsFn   , 'text/javascript');  	   
  	   $this->_defaultFunctionLoaded  = true;
  	}
  	
  	
  	/**
  	 * Permet d'ajouter du code au chargement de la page
  	 *
  	 * @param string $function
  	 */
  	public function addOnLoad($function)
  	{  		 
  		$this->_scripts[]  = $function;
  	    return $this;
  	}
  	
  	/**
  	 * Permet d'ajouter un évenement à la page
  	 *
  	 * @param string $selector
  	 * @param string $event
  	 * @param string $function
  	 */
  	public function attachEvent($selector , $event , $function)
  	{
  		if(!in_array($event , $this->_allowedEvents )){
  			return $this;
  		}
  		$selector  = $selector;
  		$event     = $event;
  		$function  = $function;
  		if(!empty($selector) && !empty($event) && !empty($function)){
  			$eventFunction ="
  			                  var element  = document.getElementById('".$selector."'); \n
  			                  if(element){
  			                     ".sprintf('addEvent(element,\'%s\',function(element){%s})' , $event , $function ).";
  			                  } ";
  		   $this->addOnLoad( $eventFunction );	
  		} 
  		return $this; 		
  	}
  	
  	
  	/**
  	 * Permet d'assigner un évenement à un selecteur javascript
  	 *
  	 * @param string $nodeSelectorId l'element html
  	 * @param string $event          le nom de l'événement
  	 * @param string $function       la fonction javascript
  	 */
  	public function bind($nodeSelectorId , $eventName , $eventFunction)
  	{
  		if(!in_array($eventName , $this->_allowedEvents )){
  			return $this;
  		}
  		if(null==$this->_events || empty($this->_events)){
  			$this->_events    = array();
  		}
  		if(null !== $nodeSelectorId && !empty($nodeSelectorId) && !empty($eventName) && !empty($eventFunction)){
  			$nodeSelectorId  = $this->view->escape($nodeSelectorId);
  			$eventName       = $this->view->escape($eventName);
  			$eventFunction   = $eventFunction;
  			if(!array_key_exists($nodeSelectorId, $this->_events )){
  				$this->_events[$nodeSelectorId]         = array();
  			}
  			$this->_events[$nodeSelectorId][$eventName] = $eventFunction ;
  		}
  		return $this;
  	}
  	
  	
  	/**
  	 * Permet de désassigner un évenement
  	 *
  	 * @param string $nodeSelectorId l'element html
  	 * @param string $event          le nom de l'événement
  	 */
  	public function unbind($nodeSelectorId , $eventName)
  	{
  		if(isset($this->_events[$nodeSelectorId][$eventName])){
  			unset($this->_events[$nodeSelectorId][$eventName]);
  		}
  		return $this;
  	}
  	
  	/**
  	 * @param array $events
  	 */
  	public function setAllowedEvents($events = array())
  	{
  		if(!empty($events)){
  			$this->_allowedEvents  = $events;
  		}
  		return  $this;
  	}
  	
  	/**
  	 * @param array $events
  	 */
  	public function setEvents($events = array())
  	{
  		if(!empty($events)){
  			$this->_events  = $events;
  		}
  		return  $this; 		
  	}
  	
  	
  	/**
  	 * @return array $attributes
  	 */
  	public function getEvents()
  	{
  		return  $this->_events;
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
  			$selectorid    = (isset($argv[1])) ? $argv[1] : null;
  			if(!empty($eventFunction)){
  				return $this->bind( $selectorid , $eventName , $eventFunction);
  			}
  		}
  	}
  	
  	
  	/**
  	 * Permet de retourner l'objet sous forme de chaine de caractère
  	 *
  	 *
  	 * @retur string
  	 */
  	public function __toString()
  	{
  		$events  = $this->_events ;  		
  		if(count($events)){
  			foreach( $events as $elementNodeId => $eventData){
  				     list($event , $function) = each($eventData);
  				     $this->attachEvent( $elementNodeId , $event , $function);
  			}
  		}
  		$jsAddOnloadString = "";
  		if(!empty($this->_scripts)){
  			if(!$this->_defaultFunctionLoaded){
  				$this->loadDefaultFunctions();
  			}
  			foreach( $this->_scripts as $script){
  				     $jsAddOnloadString .= $script." \n ";
  			}
  			$jsOutput = sprintf("  %s \n" , $jsAddOnloadString);
  			$this->view->headScript()->appendScript( $jsOutput );
  		}
  		return "";
  	}
  }

