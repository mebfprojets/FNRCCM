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
 * qui permet de créer une barre d'outils ou de tache
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

  class Sirah_View_Helper_Supina_SupinaToolsbar extends Sirah_View_Helper_Toolsbar
  {
  	

  	public function supinaToolsbar( $attributes = array() , $eventHandler = null )
  	{
  		if (null !== $attributes && !empty($attributes)) {
  	 		$this->_attributes = $attributes;
  	 	}
  	 	if(null !== $eventHandler){
  	 	   $this->setEventHandler($eventHandler);
  	 	}  	 	
  	 	return $this; 
  	}
  	
  	/**
  	 * Permet de créer un outil qui peut etre ajouté à la pile des outils
  	 * de la barre de tache
  	 *
  	 * @return Sirah_View_Helper_Toolbar
  	 */
  	public function tool($label, $icon = null, $eventHandler = null)
  	{
  		if(null == $this->view){
  			$this->view  = Zend_Layout::getMvcInstance()->getView();
  		}
  		if(null === $eventHandler && (null!== ($defaultEventHandler = $this->getEventHandler()))){
  			$eventHandler  =  $defaultEventHandler;
  		}
  		return $this->view->supinaTools( $label , $icon , $eventHandler);
  	}
  	
  	/**
  	 * Permet d'ajouter des outils à la barre des outils
  	 *
  	 * @param Sirah_View_Helper_Toolbar_Tool $tool
  	 * @param string $id
  	 */
  	public function add( Sirah_View_Helper_Supina_SupinaTools $tool , $id = null)
  	{
  		if( ( null !== $id ) && !empty($id)){
  			$tool->setId($id) ;
  			$this->_tools[$id] = $tool;
  			return $this;
  		}
  		$this->_tools[]  = $tool;
  		return $this;
  	}
   
  	 
  	 /**
  	  * Convertit la table en chaine de caractère
  	  *
  	  * @return string
  	  */
  	 public function __toString()
  	 {
  	 	$attributes      = $this->_attributes;
  	 	$output          = "";
  	 	$toolsListString = '';
  	 
  	 	if (!isset($attributes['class'])) {
  	 		$attributes['class'] = array("page-subnav","toolsbar-nav");
  	 	} else {
  	 		$attributes['class'] = (array)$attributes['class'];
  	 	} 	
  	 	if( !isset( $attributes['id'] )) {
  	 		$attributes['id']    = "page-subnav";
  	 	} 	  	 	  	  	 	
  	 	if (count(   $this->_tools)) { 
  	 		foreach( $this->_tools as $tool ){
				     if( $tool->isDropdown() ) {
						 $output  .= "<li class=\"dropdown\"> ".$tool." </li>";
					 } else {
						 $output  .= "<li> ".$tool." </li>";
					 } 	 			     
  	 		} 	 	 		
  	 	} 	 		
  	 	$outputStr = sprintf('<ul%s>%s</ul>', $this->_htmlAttribs($attributes) , $output);
  	 	
  	 	return $outputStr;
  	 }	 	
  }

