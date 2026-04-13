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
 * @link       https://github.com/twitter/typeahead.js/blob/master/doc/bloodhound.md
 * @since
 */

class Sirah_View_Helper_TypeaHead_Engine_Bloodhound extends Zend_View_Helper_FormText
{
	
	/**
	 * @var string
	 */
	protected	$_promise = null;	
		
	/**
	 * @var string
	 */
	protected $_name     = null;
	
	
	/**
	 * @var array
	 */
	protected $_local    = array();
	
	/**
	 * @var mixed
	 */
	protected $_prefetch  = null;
	
	
	/**
	 * @var mixed
	 */
	protected $_remote   = null;
	
	/**
	 * Permet de créer le moteur de suggestions
	 *
	 * @param string  $name          l'option name de bloodhood, qui correspond en mme tps au id du champ
	 * @param string  $options       les paramètres par defaut de création de bloodhood
	 * @param array   $attributes    les attributs du champ d'autocomplétion
	 * @param boolean $reinitialize  permet de vérifier s'il faut re-créer ou pas
	 *
	 * @return Sirah_View_Helper_Buttonbar_Button
	 */
	public function bloodhound( $name, $options =array() , $attributes = array() , $reinitialize = false )
	{
		$button = new static();
		if( null==$name || empty( $name ) ) {
			throw new Sirah_View_Helper_Exception("Impossible de créer le moteur de suggestion car les paramètres fournis sont invalides");
		}
		$button->setView($this->view);
		$button->setName($labelValue);
		$button->setIcon($glyphicon);
		$button->setEventHandler($eventHandler);
		$button->setAttributes($attributes);
		$button->setAllowedEvents(array("click","submit","dblclick"));
		
		return $button;
	}	
	
		
	/**
	 * Permet de récupérer la priorité
	 *
	 */
	public function getPriority()
	{
		return $this->_priority;
	}	
	
	/**
	 * Permet de mettre à jour la priorité
	 *
	 */
	public function setPriority($priority)
	{
		if(in_array( $priority , Sirah_View_Helper_Buttonbar_Button::$priorities )) {
		   $this->_priority  = $priority;
		}
		return $this;
	}
	
	/**
	 * Permet de vérifier si le boutton est actif
	 *
	 */
	public function isPrimary()
	{
		return ( $this->_priority == "primary");
	}
	
	/**
	 * Permet de créer une liste d'outils
	 * dans le dropdown du bouton
	 *  
	 * @param array $items
	 * @param Sirah_View_Helper_Script_EventHandler $eventHandler
	 * @param array $defaultAttributes
	 *
	 */
	public function dropdown($items = array() , $eventHandler = null , $defaultAttributes = array())
	{
		if(!empty($items)) {
			foreach($items as $itemId => $itemLabel){
				if(is_numeric($itemId)){
					$itemId  = "btnDropdonwItemId-".intval($itemId);
				}
				$this->insertDropdownItem($itemLabel , $itemId, null, $eventHandler, $defaultAttributes);
			}	
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter un element à la liste des
	 * outils dropdown
	 * 
	 * @param mixed  $item
	 * @param string $itemId
	 * @param string $glyphicon
	 * @param Sirah_View_Helper_Script_EventHandler
	 * @param array $attributes
	 *
	 */
	public function insertDropdownItem($item , $itemId = null, $glyphicon = null, $eventHandler = null, $attributes = array())
	{
		if(is_string($item)) {
			$item  = $this->view->tool($item , $glyphicon , $eventHandler , $attributes);
		} elseif(!$item instanceof Sirah_View_Helper_Toolbar_Tool) {
			return $this;
		}
		if( null !== $itemId ){
			$item->setId($itemId);
		    $this->_dropdownitems[$itemId]  = $item;
		    return $this;
		}
		$this->_dropdownitems[]            = $item;		
		return $this;
	}
	
   /**
	* Permet de rétirer un element dropdown
	*
	* @param string $itemId
	*/
	public function removeDropdownItem($itemId)
	{
		if(isset($this->_dropdownitems[$itemId])){
		   unset($this->_dropdownitems[$itemId]);
		}
		return $this;
	}
	
	/**
	 * Permet de recupérer un element du dropdown
	 *
	 * @param string $itemId
	 */
	public function getDropdownItem($itemId)
	{
		if(isset($this->_dropdownitems[$itemId])){
			return $this->_dropdownitems[$itemId];
		}
		return false;
	}
	
	
	/**
	 * Permet de générer le html de la liste
	 * des élements du dropdwn
	 *
	 * @param array $items (Optionnal)
	 */
	public function dropdownList($items = array())
	{
		$items   = (empty($items)) ?  $this->_dropdownitems : $items;
		$dropdownListOutput = "";		
		if(count($items)) {
			$listOutput     = "";
			foreach($items as $itemId => $itemToolHelper){
				$listOutput    .= " <li class='dropdowListItem list".$itemId."'> ".$itemToolHelper." </li> ";
			}
			$dropdownListOutput = sprintf("<ul %s> %s </ul>" , $this->_htmlAttribs(array("class" => array("dropdown-menu"))) , $listOutput) ;
		}
       return $dropdownListOutput;
	}
	
	
	/**
	 * Permet de retourner l'objet sous forme de chaine de caractère
	 *
	 */
	public function __toString()
	{	
		$outputIcon = "";
		$priority   = $this->getPriority();
		$isDisabled = $this->isDisabled();				
		$label      = $this->getLabel();
		$glyphicon  = $this->getIcon();
		$color      = $this->getColor();
		$attributes = $this->getAttributes();
		$btnClasses = array("btn" , "btn-default", "sirah-ui-btn");
		
		$glyphiconPosition    = $this->getIconPosition();		
		$dropdownItems   = $this->_dropdownitems;
		$dropdownOutput  = "";
		$id              = $this->getId();
		$dropdownId      = $id."Dropdown";
		
		if(!isset($attributes["id"]) && !empty($id)) {
			$attributes["id"]  = $id;
		}
		
		if(isset($attributes["class"])){
		   $btnClasses  = (array) $attributes["class"];
		   unset($attributes["class"]);
		} 		
		if( ($priority == "primary") && !in_array("btn-primary" , $btnClasses ) ){
			$btnClasses[]  = "btn-primary";
			$color         = "white";
		} elseif($priority == "secondary") {
			if( $primaryKey = array_search("btn-primary", $btnClasses )) {
				unset($btnClasses[$primaryKey]);
			}
			$btnClasses[]  = "btn-secondary";
		} 				
		if($isDisabled){
		   $btnClasses[]   = "disabled";
		   $btnClasses[]   = "btn-disabled";
		}		
		if(null !== $glyphicon){
			$glyphiconClass = array( "glyphicon glyphicon-".$glyphicon ) ;
			if($color == "white"){
			   $glyphiconClass[]     = " glyphicon glyphicon-white ";
			}
			$outputIcon         = sprintf("<i %s></i>" , $this->_htmlAttribs(array("class" => $glyphiconClass)));
		}
		if(!empty($dropdownItems)){
			$dropdownList       = $this->dropdownList();
			$dropdownIcon       = sprintf("<span %s></span>" , $this->_htmlAttribs(array("class" => array("caret"))));
			
			$dropdownBtnClass   = $btnClasses;
			$dropdownBtnClass[] = "dropdown-toggle";
			$dropdownOutput  = sprintf("<button %s > %s &nbsp; </button> %s " ,
					                   $this->_htmlAttribs(array("class" => $dropdownBtnClass , "data-toggle" => "dropdown" , "id" => $dropdownId)) ,
					                   $dropdownIcon ,
					                   $dropdownList);			
		}
		$attributes["class"]  = $btnClasses  ;

		if($glyphiconPosition == "right"){
		   $buttonHtmlString  = sprintf(" <div class='btn-group'> \n
					<button %s> %s %s </button> \n
					%s
					</div> ",
					$this->_htmlAttribs($attributes),
					$label,
		   		    $outputIcon,
					$dropdownOutput );
			
		 } else {
		   $buttonHtmlString  = sprintf(" <div class='btn-group'> \n 
				                            <button %s> %s %s </button> \n
				                            %s
				                          </div> ",
				                          $this->_htmlAttribs($attributes),
				                          $outputIcon,
				                          $label,
				                          $dropdownOutput );
		}		
		if(null!=($eventHandler = $this->getEventHandler())){
			echo $eventHandler;
		}		
		return $buttonHtmlString;		
	}
	
	
}