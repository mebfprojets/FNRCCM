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

  class Sirah_View_Helper_TreeFormSelect extends Sirah_View_Helper_HtmlElement 
  {
    
    /**
     * liste des outils
     *
     * @var array
     */
    protected $_items         = array();
    
    
    protected $_selectedValue = null;
    
    
    protected $_rootParent    = 0;
  	

  	 public function treeFormSelect( $id, $value = null, $attributes = array(), $items = array() , $parent = 0 )
  	 {
  	 	if (null !== $attributes && !empty($attributes)) {
  	 		$this->_attributes = $attributes;
  	 	}  	 	
  	 	if(null !== $eventHandler){
  	 	   $this->setEventHandler($eventHandler);
  	 	} 
  	 	$this->_selectedValue     = $value;
  	 	$this->_items             = $items; 
  	 	$this->_rootParent        = $parent;	 
  	 	$this->_attributes["id"]  = $id;
  	 	return $this; 	 	
  	 }
  	 
  	 
  	 public function setRoot( $parent )
  	 {
  	 	$this->_rootParent     = intval( $parent ); 
  	 }
  	 
  	 public function getParent()
  	 {
  	 	return $this->_rootParent ;
  	 }
  	 
  	 
  	 public function setItems( $items )
  	 {
  	 	$this->_items  = $items;
  	 	return $this;
  	 }
  	 
  	 
  	 public function getItems()
  	 {
  	 	return $this->_items;
  	 }
  	 
  	 
  	 public function setSelected( $value )
  	 {
  	 	$this->_selectedValue  = $value;  	 	
  	 	return $this;
  	 }
  	 
  	 
  	 public function getSelected()
  	 {
  	 	return  $this->_selectedValue;
  	 }
  	 
  	 
  	 public function addItem( $id, $item, $parentid = 0 )
  	 {
  	 	if( $parentid == 0 )   {
  	 		$this->_items[$id] = $item;
  	 	} elseif( isset( $this->_items[$parentid] )) {
  	 		$this->_items[$parentid]["children"][$id] = $item;
  	 	}
  	 	return $this;
  	 }
  	 
  	 
  	 public function removeItem( $id, $parentid = 0 )
  	 {
  	 	if( ($parentid == 0 ) && isset( $this->_items[$id] ) )   {
  	 		unset( $this->_items[$id] );
  	 	} elseif( isset( $this->_items[$parentid] )  && isset( $this->_items[$parentid]["children"][$id] )) {
  	 		unset( $this->_items[$parentid]["children"][$id] );
  	 	}
  	 	return $this;
  	 }
  	 
  	 
  	 public static function build( $options, $selectedValue , $level = 0 , $parent = 0, $depth = 0 )
  	 {
  	 	$htmlOutput  = "";
  	 	if( count(   $options )) {
  	 		foreach( $options as $itemId => $optionItem ) {
  	 			     $value      = (isset($optionItem["libelle"] )) ? $optionItem["libelle"] : null;
  	 			     $children   = (isset($optionItem["children"])) ? $optionItem["children"] : array();
  	 			     $selected   = ( $itemId == $selectedValue ) ? "selected='selected'" : "";
  	 			     $dash       = (($parent != 0) && ($optionItem["parentid"] != 0)) ? str_repeat("-", $level ) : "";
  	 			     if( empty( $value )) continue;
					 if(($parent == 0) || ($optionItem["parentid"] == 0)) {
						 $level  = 0;
					 } 
  	 			     $htmlOutput.= sprintf("<option value='%d' %s > ", $itemId, $selected );
  	 			               $htmlOutput .= $dash.$value;
  	 			     $htmlOutput.= "</option>";
  	 			     if(!empty($children)) {
  	 			     	 $level++;
  	 			     	 $htmlOutput .=Sirah_View_Helper_TreeFormSelect::build( $children, $selectedValue, $level, $itemId );
  	 			     }  					 
  	 		}
  	 	}
  	 	return $htmlOutput;
  	 }

  	 
  	 /**
  	  * Convertit la table en chaine de caractère
  	  *
  	  * @return string
  	  */
  	 public function __toString()
  	 {
  	 	$attributes  = $this->_attributes;  	 	
  	 	$id          = $attributes["id"];
  	 	$items       = $this->_items;
  	 	
  	 	$xhtml = '<select'
                . ' name="' . $id. '"'
                . ' id="' . $id . '"'
                . $this->_htmlAttribs($attributes)
                . ">\n    ";
  	 	$xhtml .= Sirah_View_Helper_TreeFormSelect::build( $items, $this->getSelected(), 0, $this->_rootParent );
  	 	$xhtml .= "</select>";
  	 	
  	 	return $xhtml;  	 	 
  	 }
  	 	
  }

