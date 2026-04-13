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
 * qui permet de générer la pagination
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

  class Sirah_View_Helper_Pagination extends Sirah_View_Helper_HtmlElement
  {
  	
  	/**
  	 * @var Zend_Paginator
  	 */  	
  	 protected $_paginator  = null;
  	   	 
  	 /**
  	  * @var string
  	  */
  	 protected $_scrollingStyle = "Sliding";
  	 
  	 
  	 /**
  	  * @var bool
  	  */
  	protected $_isAjax          = false;
  	
  	/**
  	 * @var string
  	 */
  	protected $_linkUrl         = null;
  
  	
  	 /**
  	  * Permet de fournir l'instance de l'aide de vue
  	  *
  	  * @param  mixed   $rows les données de pagination
  	  * @param  string  $scrollingStyle le style de défilement
  	  * @param  array   $attributes les attributs
  	  * 
  	  * @throws Sirah_View_Helper_Exception
  	  */
     public function pagination( $rows = array() , $scrollingStyle = "Elastic" , $linkUrl = "" , $params = array() , $attributes = array())
     {
     	if(null === $this->view ){
     		$this->view  = Zend_Layout::getMvcInstance()->getView();
     	}
      	if(is_array($rows) && !empty($rows)) {
      		if(null == $this->_paginator){
      		   $this->_paginator  = new Zend_Paginator(new Zend_Paginator_Adapter_Array($rows));
      		}
      	} elseif( $rows instanceof  Zend_Paginator_Adapter_Interface) {
      		$this->_paginator     = new Zend_Paginator($rows);
      	}      	
      	if(null == $this->_paginator && isset($this->view->paginator)){
      		$this->_paginator     = $this->view->paginator;
      	}      	
      	$this->_scrollingStyle  = $scrollingStyle;
      	
      	$this->setLinkUrl($linkUrl);
      	$this->setParams($params);
      	$this->setAttributes($attributes);
      	$this->setEventHandler($this->view->jqueryHandler());
      	return $this;         
     } 
      
     /**
      * Permet de mettre à jour l'objet de pagination
      * 
      * @param   Zend_Paginator  $paginator
      * @return  Sirah_View_Helper_Pagination
      *
      */
     public function setPaginator($paginator)
     {
      	if( $paginator instanceof Zend_Paginator){
      	    $this->_paginator  = $paginator;
      	} 
      	return $this;     	
     }
     
     /**
      * Permet de recupérer l'objet de pagination
      *
      * @return   Zend_Paginator  $paginator
      *
      */
     public function getPaginator()
     {
      	 return $this->_paginator;
     }
     
     /**
      * Permet de mettre à jour le style de défilement
      *
      * @param string  $style
      *
      */
     public function setScrollingStyle($style)
     {
     	$this->_scrollingStyle = $style;
     	return $this;
     }
          
     /**
      * Permet de récupérer le style de défilement
      *
      * @param string 
      *
      */
     public function getScrollingStyle()
     {
     	return $this->_scrollingStyle ;
     }
     
     /**
      * Permet d'indiquer si nous sommes
      * dans une pagination de type ajax
      *
      * @return   Sirah_View_Helper_Pagination
      *
      */
     public function isAjax()
     {
     	$this->_isAjax  = true;    	
     	return $this;
     }
          
     /**
      * Permet de mettre à jour l'url de pagination
      *
      * @param string  $url
      *
      */
     public function setLinkUrl($url)
     {
     	$this->_linkUrl = $url;
     	return $this;
     }  

     /**
      * Permet de récupérer l'url de pagination
      *
      * @return string  $url
      *
      */
     public function getLinkUrl()
     {
     	if ( ( null == $this->_linkUrl ) && isset($this->view->paginationUrl) ) {
     		return $this->view->paginationUrl;
     	}
     	return 	$this->_linkUrl;
     }
     
     /**
      * Permet de rendre la vue
      *
      */
     public function render()
     {
     	$paginator       =  $this->getPaginator();
     	$scrollingStyle  =  $this->getScrollingStyle();
     	$attributes      =  $this->getAttributes();
     	$isAjax          =  $this->_isAjax ;
     	$linkUrl         =  $this->getLinkUrl();
     	$eventHandler    =  $this->getEventHandler();     	
        if(null == $paginator) {
      		throw new Sirah_View_Helper_Exception(" Aucune instance valide de l'objet de pagination n'a été fournie  ");
      	}
     	$pages = $paginator->getPages($scrollingStyle);
     	$paginationOutputString  = "";
     	
     	if(!isset($attributes["class"])){
     		$attributes["class"] = array("btn-buttonbar","sirah-ui-buttonbar", "paginationControl");
     	}
     	if(null == $eventHandler ) {
     	   $eventHandler  = $this->view->jqueryHandler();
     	}    	
     	if( $pages->pageCount ) { 
     		$paginationOutputString    .= sprintf("<div %s>" , $this->_htmlAttribs($attributes));     		
     		//Précedent
     		if(isset($pages->previous)) {
     		   $previousButton         = $this->view->buttons("Précédent" ,"chevron-left", $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn", "prevPage")));
     		   $previousButton->setId("paginationBtnPrev");
     		   if(!empty($linkUrl) && $linkUrl!= "#" ) {
     		   	  $previousButton->onClick("document.location.href='".$linkUrl."/page/".$pages->previous."'; ");
     		   }
     		   $paginationOutputString .= $previousButton;
     		} else {
     		   $previousButton          = $this->view->buttons("Précédent" , "chevron-left" , $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn", "prevPage")));
     		   $previousButton->setId("paginationBtnPrev");
     		   $previousButton->disable();
     		   $paginationOutputString .= $previousButton;
     		} 
     		// Affichage des différentes pages
     		foreach ($pages->pagesInRange as $page) {
     			if($page != $pages->current) {
     			   $pageButton        = $this->view->buttons($page , null, $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" )));
     			   $pageButton->setId("paginationBtnPage-".$page);
     			   if(!empty($linkUrl) && $linkUrl != "#" ) {
     			   	  $pageButton->onClick("document.location.href='".$linkUrl."/page/".$page."'; ");
     			   }
     			   $paginationOutputString .= $pageButton;
     			} else {
     				$pageButton        = $this->view->buttons($page , null , $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" , "currentBtn")));
     				$pageButton->setId("paginationBtnPage-".$page);
     				$pageButton->setPriority("primary");
     				$paginationOutputString .= $pageButton;
     			}
     		}    		     		
     		//Suivant
     		if(isset($pages->next)) {
     		   $nextButton         = $this->view->buttons("Suivant" ,"chevron-right", $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" , "nextPage")));
     		   $nextButton->setId("paginationBtnNext");
     		   if(!empty($linkUrl) && $linkUrl!= "#" ) {
     		   	   $nextButton->onClick(" document.location.href='".$linkUrl."/page/".$pages->next."'; ");
     		   }
     		   $nextButton->setIconPosition("right");
     		   $paginationOutputString .= $nextButton;
     		} else {
     		   $nextButton         = $this->view->buttons("Suivant" , "chevron-right" , $eventHandler , array("class" => array("btn" , "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" , "nextPage")));
     		   $nextButton->setId("paginationBtnNext");
     		   $nextButton->disable();
     		   $nextButton->setIconPosition("right");
     		   $paginationOutputString .= $nextButton;
     		}
     		$listInfosBtn              = $this->view->buttons("<b> Total : ".$pages->currentItemCount."/". $pages->totalItemCount .", Page ".$pages->current." sur ".$pages->pageCount."</b>", "" , $eventHandler , array( "class" => array("btn", "btn-default", "sirah-ui-btn" , "btn-primary-violet")));
     		$listInfosBtn->disable();
     		$paginationOutputString    .=  $listInfosBtn;
     		$paginationOutputString    .= "</div>";
     	} 
     	return $paginationOutputString;
     }
     
    /** Permet de rendre la vue
     *
     */
     public function __toString()
     {
     	try {
     		$this->render();
     	} catch(Exception $e) {
     		
     	}
     	
     	return "";
     }
      

   }

