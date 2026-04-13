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

  class Sirah_View_Helper_SupinaPagination extends Sirah_View_Helper_HtmlElement
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
     public function supinaPagination( $rows = array() , $scrollingStyle = "Elastic" , $linkUrl = "" , $params = array() , $attributes = array())
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
     	$paginator       = $this->getPaginator();
     	$scrollingStyle  = $this->getScrollingStyle();
     	$attributes      = $this->getAttributes();
     	$isAjax          = $this->_isAjax ;
     	$linkUrl         = $this->getLinkUrl();
     	$eventHandler    = $this->getEventHandler();  
     	$nbrePerPage     = $paginator->getItemCountPerPage();   	
        if(null == $paginator) {
      		throw new Sirah_View_Helper_Exception(" Aucune instance valide de l'objet de pagination n'a été fournie  ");
      	}
     	$pages = $paginator->getPages($scrollingStyle);
     	$paginationOutputString  = "";
     	
     	if(!isset($attributes["class"])){
     		$attributes["class"] = array("btn-toolbar","sirah-ui-buttonbar", "paginationControl");
     	}
     	if(null == $eventHandler ) {
     	   $eventHandler  = $this->view->jqueryHandler();
     	}    	
     	if( $pages->pageCount ) { 
     		$paginationOutputString   .= sprintf("<div %s>" , $this->_htmlAttribs($attributes));     		
     		$paginationOutputString   .= sprintf("<div class='btn-group'><div class='size-md mrg10R'> De %d à %d sur %d élements </div> </div>", $pages->firstItemNumber, $pages->currentItemCount, $pages->totalItemCount );
     		$paginationOutputString   .= "<div class='btn-group'>";
     		//Précedent
     		if(isset($pages->previous)) {
     		   $previousButton         = $this->view->btn("&nbsp;" ,"chevron-left", $eventHandler , array("class" => array("btn", "btn-default", "sirah-ui-btn" , "sirahPaginationBtn", "prevPage")));
     		   $previousButton->setId("paginationBtnPrev");
     		   if(!empty($linkUrl) && $linkUrl!= "#" ) {
     		   	  $previousButton->onClick("document.location.href='".$linkUrl."/page/".$pages->previous."'; ");
     		   }
     		   $paginationOutputString .= $previousButton;
     		} else {
     		   $previousButton          = $this->view->btn("&nbsp;", "chevron-left" , $eventHandler , array("class" => array("btn", "btn-default", "sirah-ui-btn", "sirahPaginationBtn", "prevPage")));
     		   $previousButton->setId("paginationBtnPrev");
     		   $previousButton->disable();
     		   $paginationOutputString .= $previousButton;
     		} 
     		   		
     		//Suivant
     		if(isset($pages->next)) {
     		   $nextButton         = $this->view->btn("&nbsp;", "chevron-right", $eventHandler, array("class" => array("btn", "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" , "nextPage")));
     		   $nextButton->setId("paginationBtnNext");
     		   if(!empty($linkUrl) && $linkUrl!= "#" ) {
     		   	   $nextButton->onClick(" document.location.href='".$linkUrl."/page/".$pages->next."'; ");
     		   }
     		   $nextButton->setIconPosition("right");
     		   $paginationOutputString .= $nextButton;
     		} else {
     		   $nextButton         = $this->view->btn("&nbsp;", "chevron-right" , $eventHandler , array("class" => array("btn", "btn-default", "sirah-ui-btn" , "sirahPaginationBtn" , "nextPage")));
     		   $nextButton->setId("paginationBtnNext");
     		   $nextButton->disable();
     		   $nextButton->setIconPosition("right");
     		   $paginationOutputString .= $nextButton;
     		}     		 
     		$paginationOutputString    .= "</div>";
     		
     		$paginationOutputString    .=" <div class=\"dropdown mrg15L\"> 
     		                                   <a href=\"#\" class=\"btn btn-primary\" data-toggle=\"dropdown\">
     		                                       <i class=\"glyph-icon icon-list opacity-80\"></i>
     		                                       <i class=\"glyph-icon icon-chevron-down\">    </i>
     		                                   </a>
     		                                   <ul  class=\"dropdown-menu pad0B float-right\">
     		                                        <li class=\"header\"> Nbre d'élements par page </li>
     		                                        <li ".(($nbrePerPage == 10)       ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-10\"  class=\"paginationCountItem page-10\"> 10 éléments </a>  </li>
     		                                        <li ".(($nbrePerPage == 20)       ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-20\"  class=\"paginationCountItem page-20\"> 20 éléments </a>  </li>
     		                                        <li ".(($nbrePerPage == 30)       ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-30\"  class=\"paginationCountItem page-30\"> 30 éléments </a>  </li>
     		                                        <li ".(($nbrePerPage == 50)       ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-50\"  class=\"paginationCountItem page-50\"> 50 éléments </a>  </li>
     		                                        <li ".(($nbrePerPage == 100)      ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-100\" class=\"paginationCountItem page-100\">    100 éléments </a>  </li>
     		                                        <li ".(($nbrePerPage == 10000000) ? ' class=\'active\' ': '')."> <a id=\"paginationMaxitem-1000000\" class=\"paginationCountItem page-1000000\"> Toute la liste </a>  </li>
     		                                   </ul>
     		                               </div>";
     		
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

