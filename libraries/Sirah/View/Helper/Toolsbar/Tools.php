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

class Sirah_View_Helper_Toolsbar_Tools extends Sirah_View_Helper_HtmlElement
{
	
	/**
	 * @var array
	 */
	public static  $icons  =  array(			
        'adjust', 'align-center', 'align-justify', 'align-left',
        'align-right', 'arrow-down', 'arrow-left', 'arrow-right',
        'arrow-up', 'asterisk', 'backward', 'ban-circle',
        'barcode', 'bell', 'bold', 'book',
        'bookmark', 'briefcase', 'bullhorn', 'calendar',
        'camera', 'certificate', 'check', 'chevron-down',
        'chevron-left', 'chevron-right', 'chevron-up', 'circle-arrow-down',
        'circle-arrow-left', 'circle-arrow-right', 'circle-arrow-up', 'cog',
        'comment', 'download', 'download-alt', 'edit',
        'eject', 'envelope', 'exclamation-sign', 'eye-close',
        'eye-open', 'facetime-video', 'fast-backward', 'fast-forward',
        'file', 'film', 'filter', 'fire',
        'flag','flash','floppy-disk','floppy-open','floppy-remove','floppy-save','floppy-saved','folder-close', 'folder-open', 'font',
        'forward', 'fullscreen', 'gift', 'glass',
        'globe', 'hand-down', 'hand-left', 'hand-right',
        'hand-up', 'hdd', 'headphones', 'heart',
        'home', 'inbox', 'indent-left', 'indent-right',
        'info-sign', 'italic', 'leaf', 'list',
        'list-alt', 'lock', 'magnet', 'map-marker',
        'minus', 'minus-sign', 'move', 'music',
        'off', 'ok', 'ok-circle', 'ok-sign',
        'pause', 'pencil', 'picture', 'plane',
        'play', 'play-circle', 'plus', 'plus-sign',
        'print', 'qrcode', 'question-sign', 'random',
        'refresh', 'remove', 'remove-circle', 'remove-sign',
        'repeat', 'resize-full', 'resize-horizontal', 'resize-small',
        'resize-vertical', 'retweet', 'road', 'screenshot',
        'search', 'share', 'share-alt', 'shopping-cart','send',
        'signal', 'star', 'star-empty', 'step-backward',
        'step-forward', 'stop', 'tag', 'tags',
        'tasks', 'text-height', 'text-width', 'th',
        'th-large', 'th-list', 'thumbs-down', 'thumbs-up',
        'time', 'tint', 'transfer' , 'trash', 'upload',
        'user', 'volume-down', 'volume-off', 'volume-up',
        'warning-sign', 'wrench', 'zoom-in', 'zoom-out');
	
	
	/**
	 * @var string
	 */
	protected $_icon          = null;
	
	/**
	 * @var string
	 */
	protected $_iconPosition  = "left";
	
	/**
	 * @var string
	 */
	protected $_label         = "";
	
	/**
	 * @var string
	 */	
	protected $_color         = null;
			
	
	/**
	 * Permet de créer un outil de la barre des taches
	 *
	 * @param string $labelValue              le libellé de l'outil
	 * @param string $icon                    la désignation de l'icone à utiliser
	 * @param Sirah_View_Helper_EventHandler le gestionnaire des évenements javascript
	 *
	 * @return Sirah_View_Helper_Toolbar_Tool
	 */
	public function tools($labelValue, $icon , $eventHandler = null , $attributes = array())
	{
		$tool = new static();
		if(null==$labelValue || empty($labelValue)){
			throw new Sirah_View_Helper_Exception("Impossible de créer l'outil car les paramètres fournis sont invalide");
		}
		$tool->setView(  $this->view);
		$tool->setLabel( $labelValue);
		$tool->setIcon(  $icon);
		$tool->setEventHandler( $eventHandler);
		$tool->setAttributes(   $attributes  );
		$tool->setAllowedEvents(array("click","submit","dblclick"));		
		return $tool;
	}
	
			
	
	/**
	 * Permet de récupérer le gestionnaire des événements javascript
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return $this->_icon;
	}
		
	/**
	 * Permet de mettre à jour le type d'icone de l'outil
	 *
	 * @param string $icon le type d'icone de l'outil
	 * 
	 */
	public function setIcon($icon)
	{
		if(in_array($icon , self::$icons)){
		   $this->_icon = $icon;
		}
		return $this;
	}
	
	
	/**
	 * Permet de mettre à jour la position de l'icone
	 *
	 * @param string $position la position de l'icone
	 *
	 */
	public function setIconPosition($position)
	{
		$this->_iconPosition = $position;
		return $this;
	}
	
	/**
	 * Permet de recupérer la position de l'icone
	 *
	 * @return string
	 *
	 */
	public function getIconPosition()
	{
		return $this->_iconPosition;
	}
	
	/**
	 * Permet de récupérer la couleur de l'icone
	 *
	 * @return string
	 */
	public function getColor()
	{
		return $this->_color;
	}
	
	
	/**
	 * Permet de mettre à jour la couleur de l'icone de l'outil
	 *
	 * @param string $color
	 */
	public function setColor($color)
	{
		$this->_color = $color;
		return $this;
	}
	
	
	/**
	 * Permet de recupérer le label de l'outil
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return $this->_label;
	}
	
	/**
	 * Permet de mettre à jour le libellé de l'outil
	 *
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->_label = $label;
		return $this;
	}				
	
	
	/**
	 * Permet de retourner l'objet sous forme de chaine de caractère
	 *
	 */
	public function __toString()
	{		
		$outputIcon   = "";
		$label        = $this->getLabel();
		$icon         = "glyphicon glyphicon-".$this->getIcon();
		$color        = $this->getColor();
		$attributes   = $this->getAttributes();
		$iconPosition = $this->getIconPosition();
		$isDisabled   = $this->isDisabled();
		
		if(null !== $icon){
			$iconClass = array($icon) ;
			if($color == "white"){
			   $iconClass[]  = " glyphicon glyphicon-white ";
			}
			$outputIcon  = sprintf("<i %s></i>" , $this->_htmlAttribs(array("class" => $iconClass)));
		}
		if(!empty($color)) {
			$attributes["style"] = " color: ".$color;
		}
		if(!isset($attributes["href"])){
			$attributes["href"]  = "#";
		}
		if( $isDisabled){
			$attributes["class"] = "disabled tool-disabled";
		}
		if(null!=($eventHandler = $this->getEventHandler())){
			echo $eventHandler;
		}		
		$toolOutput    = sprintf("<a%s>%s%s</a>" ,  $this->_htmlAttribs($attributes) , $outputIcon , $label);
		switch($iconPosition) {
			case "left"  :
			case "top"   :
			case "bottom":
			default      :
				$toolOutput    = sprintf("<a%s>%s%s</a>" ,  $this->_htmlAttribs($attributes) , $outputIcon , $label);
				break;
			case "right" :
				$toolOutput    = sprintf("<a%s>%s%s</a>" ,  $this->_htmlAttribs($attributes) , $label , $outputIcon);			
		}		
		return  $toolOutput;		
	}
	
	
}