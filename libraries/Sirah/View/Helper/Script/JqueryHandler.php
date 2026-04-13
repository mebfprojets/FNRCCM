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

 class Sirah_View_Helper_Script_JqueryHandler extends Sirah_View_Helper_Script_EventHandler
 {
  	
  	
  	/**
  	 * 
  	 *
  	 * @param array
  	 */
  	public function jqueryHandler($events = array())
  	{
  		$eventHandler  = new static();
  		$events  = (array)$events;
  		if(!empty($events)){
  			$eventHandler->setEvents($events);
  		}
  		return $eventHandler;
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
  		   $jqScript = sprintf('%s("#%s").bind("%s",function(event){%s});',
  		   		               "jQuery",
  		   		               $selector,
  		   		               $event,
  		   		               $function);
  		   $this->view->jQuery()->addOnLoad($jqScript);	
  		} 
  		return $this; 		
  	}
  	
  	/**
  	 * Permet de retourner l'objet sous forme de chaine de caractère
  	 *
  	 *
  	 * @return string
  	 */
  	public function __toString()
  	{
  		$events  = $this->getEvents();  		
  		if(count($events)){
  			foreach(  $events as $elementNodeId => $eventData){
  				      list($event , $function) = each($eventData);
  				      $this->attachEvent( $elementNodeId , $event , $function);
  			}
  		}
  		return "";
  	}
  	
  		
  	

  }

