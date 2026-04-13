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
 * 
 * 


<div class="btn-buttonbar btn-buttonbar2">
  <div class="btn-group">
    <button class="btn">Dashboard</button>
  </div>
  <div class="btn-group">
    <button class="btn">Button 1</button>
    <button class="btn dropdown-toggle" data-toggle="dropdown">
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
      <li><a href="#">Action</a></li>
      <li><a href="#">Another action</a></li>
      <li><a href="#">Something else here</a></li>
      <li class="divider"></li><li><a href="#">Separated link</a></li>
    </ul>
  </div>
  <div class="btn-group">
    <button class="btn">Item 3</button>
    <button class="btn dropdown-toggle" data-toggle="dropdown">
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
      <li><a href="#">Action</a></li>
      <li><a href="#">Another action</a></li>
      <li><a href="#">Something else here</a></li>
      <li class="divider"></li>
      <li><a href="#">Separated link</a></li>
    </ul>
  </div>
</div>

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

  class Sirah_View_Helper_Buttonsbar extends Sirah_View_Helper_HtmlElement implements Iterator, Countable
  {
    
    /**
     * liste des bouttons
     *
     * @var array
     */
    protected $_buttons = array();
  	

    /**
     * Arret direct à l'aide de vue
     *
     * @return Sirah_View_Helper_Buttonbar
     */
  	 public function buttonsbar( $attributes = array() , $eventHandler = null)
  	 {
  	 	$button = new static();
  	 	if (null !== $attributes && !empty($attributes)) {
  	 		$button->setAttributes($attributes);
  	 	}
  	 	if(null == $button->getView()){
  	 		$view  = Zend_Layout::getMvcInstance()->getView();
  	 		$button->setView($view);
  	 	}
  	 	if(null !== $eventHandler){
  	 	   $button->setEventHandler($eventHandler);
  	 	}  	 	
  	 	return $button; 	 	
  	 }
  	  	 
  	/**
  	 * Permet de créer un outil qui peut etre ajouté à la pile des outils
  	 * de la barre de tache
  	 *
  	 * @return Sirah_View_Helper_Buttonbar
  	 */
  	 public function button($label, $icon = null, $eventHandler = null)
  	 {
  	 	if(null == $this->view){
  	 	   $this->view  = Zend_Layout::getMvcInstance()->getView();
  	 	}
  	 	if(null === $eventHandler && (null!== ($defaultEventHandler = $this->getEventHandler()))){
  	 		$eventHandler  =  $defaultEventHandler;
  	 	}
  	 	return $this->view->buttons($label , $icon , $eventHandler);  	 	
  	 }
  	 
  	 
  	 /**
  	  * Permet d'ajouter des boutons
  	  *
  	  * @param Sirah_View_Helper_Toolbar_Tool $button
  	  * @param string $id
  	  */
  	 public function add(Sirah_View_Helper_Buttonsbar_Buttons $button , $id = null)
  	 {
  	 	if(( null !== $id ) && !empty($id)){
  	 		$button->setId($id) ;
  	 	    $this->_buttons[$id] = $button;
  	 	    return $this;
  	 	}
  	 	$this->_buttons[]  = $button;
  	 	return $this;
  	 }
  	 
  	 /**
  	  * Permet de supprimer un outil
  	  *
  	  * @param Sirah_View_Helper_Toolbar_Tool
  	  */
  	 public function remove($buttonId)
  	 {
  	 	if(array_key_exists($buttonId , $this->_buttons )){
  	 	   unset($this->_buttons[$buttonId]);
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
  	 	return current($this->_buttons);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Move forward to next element
  	  * @link http://php.net/manual/en/iterator.next.php
  	  * @return void Any returned value is ignored.
  	  */
  	 public function next()
  	 {
  	 	next($this->_buttons);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Return the key of the current element
  	  * @link http://php.net/manual/en/iterator.key.php
  	  * @return mixed scalar on success, or null on failure.
  	  */
  	 public function key()
  	 {
  	 	key($this->_buttons);
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
  	 	return isset($this->_buttons[$key]);
  	 }
  	 
  	 /**
  	  * (PHP 5 &gt;= 5.0.0)<br/>
  	  * Rewind the Iterator to the first element
  	  * @link http://php.net/manual/en/iterator.rewind.php
  	  * @return void Any returned value is ignored.
  	  */
  	 public function rewind()
  	 {
  	 	reset($this->_buttons);
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
  	 	return sizeof($this->_buttons);
  	 }
  	 
  	 /**
  	  * Convertit la classe en chaine de caractère
  	  *
  	  * @return string
  	  */
  	 public function __toString()
  	 {
  	 	$attributes      = $this->_attributes;
  	 	$output          = "";
  	 	$buttonsListString = '';
  	 	
  	 	if (!isset($attributes['class'])) {
  	 		$attributes['class'] = array("btn-buttonbar","sirah-ui-buttonbar");
  	 	} else {
  	 		$attributes['class'] = (array)$attributes['class'];
  	 	}   	  	 	
  	 	if (count($this->_buttons)) { 
  	 		foreach($this->_buttons as $button){
  	 			$output  .= $button;
  	 		} 	 	 		
  	 	} 	 		
  	 	return sprintf('<div%s>%s</div>', $this->_htmlAttribs($attributes) , $output);
  	 }
  	 	

  }

