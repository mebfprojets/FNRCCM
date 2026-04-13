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

  class Sirah_View_Helper_Toolsbar extends Sirah_View_Helper_HtmlElement implements Iterator, Countable
  {
    
    /**
     * liste des outils
     *
     * @var array
     */
    protected $_tools = array();
  	

    /**
     * Arret direct à l'aide de vue
     *
     * @return Sirah_View_Helper_Toolbar
     */
  	 public function toolsbar($attributes = array() , $eventHandler = null)
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
  	 	return $this->view->tools( $label , $icon , $eventHandler);  	 	
  	 }
  	 
  	 
  	 /**
  	  * Permet d'ajouter des outils à la barre des outils
  	  *
  	  * @param Sirah_View_Helper_Toolbar_Tool $tool
  	  * @param string $id
  	  */
  	 public function add(Sirah_View_Helper_Toolsbar_Tools $tool , $id = null)
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
  	  * Permet de supprimer un outil
  	  *
  	  * @param Sirah_View_Helper_Toolbar_Tool
  	  */
  	 public function remove($toolKey)
  	 {
  	 	if(array_key_exists($toolKey , $this->_tools )){
  	 	   unset($this->_tools[$toolKey]);
  	 	   return true;
  	 	}
  	 	return false;
  	 }
  	 
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Return the current element
  	  * @link http://php.net/manual/en/iterator.current.php
  	  * @return mixed Can return any type.
  	  */
  	 public function current()
  	 {
  	 	return current($this->_tools);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Move forward to next element
  	  * @link http://php.net/manual/en/iterator.next.php
  	  * @return void Any returned value is ignored.
  	  */
  	 public function next()
  	 {
  	 	next($this->_tools);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Return the key of the current element
  	  * @link http://php.net/manual/en/iterator.key.php
  	  * @return mixed scalar on success, or null on failure.
  	  */
  	 public function key()
  	 {
  	 	key($this->_tools);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Checks if current position is valid
  	  * @link http://php.net/manual/en/iterator.valid.php
  	  * @return boolean The return value will be casted to boolean and then evaluated.
  	  * Returns true on success or false on failure.
  	  */
  	 public function valid()
  	 {
  	 	$key = $this->key();
  	 	return isset($this->_tools[$key]);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Rewind the Iterator to the first element
  	  * @link http://php.net/manual/en/iterator.rewind.php
  	  * @return void Any returned value is ignored.
  	  */
  	 public function rewind()
  	 {
  	 	reset($this->_tools);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.1.0)<br/>
  	  * Count elements of an object
  	  * @link http://php.net/manual/en/countable.count.php
  	  * @return int The custom count as an integer.
  	  * </p>
  	  * <p>
  	  * The return value is cast to an integer.
  	  */
  	 public function count()
  	 {
  	 	return sizeof($this->_tools);
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
  	 		$attributes['class'] = array("row","simplerowtoolbar");
  	 	} else {
  	 		$attributes['class'] = (array)$attributes['class'];
  	 	}   	  	 	
  	 	if (count($this->_tools)) { 
  	 		$output  .= "<ul class='nav nav-list inline toolbarlist' >";
  	 		foreach($this->_tools as $tool){
  	 			$output  .= "<li class='toolbarlistItem'>".$tool." </li>";
  	 		} 	 	 		
  	 		$output  .= "</ul>";
  	 	} 	 		
  	 	return sprintf('<div%s>%s</div>', $this->_htmlAttribs($attributes) , $output);
  	 }
  	 	

  }

