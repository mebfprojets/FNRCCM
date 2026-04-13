<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */


/**
 * Cette classe correspond à un plugin de vérification
 * des droits d'accès à une ressource de l'application
 * En cas d'interdiction d'accès, il route la requete
 * sur la ressource de notification du refus d'accès
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Plugin_Adminlayout extends Zend_Controller_Plugin_Abstract
{
	
    public function routeShutdown( Zend_Controller_Request_Abstract $request )
	{
		$front           = Zend_Controller_Front::getInstance();
		$controllerName  = $request->getControllerName();
		$moduleName      = $request->getModuleName();
		$actionName      = $request->getActionName();
		$me              = Sirah_Fabric::getUser();	
 		
		if( $moduleName!="api" && ($me->isGestionnaires() || $me->isSuperviseur() || $me->isManager() || $me->isAdministrateur() || $me->isAdmin()|| $me->isOPS())) {
			$errorPlugin = $front->getPlugin("Zend_Controller_Plugin_ErrorHandler");
		    $errorPlugin->setErrorHandlerModule('admin');
			$request->setModuleName("admin");
		}
	}
	
	/**
	 * Permet de vérifier la permission d'accès à la ressource
	 * 
	 * juste avant que la requete entre en boucle de distribution.
	 * 
	 * En cas d'echec, la requete sera re-routée sur d'autres controlleurs
	 * 
	 * @param Zend_Controller_Request_Abstract $request l'instance de la requete
	 *
	 *
     */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$front          = Zend_Controller_Front::getInstance();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		
		if( $moduleName == "public" ) {
			$layout     = Zend_Layout::getMvcInstance();
			$view       = $layout->getView();
			$headLink   = $view->headLink();
		    $headScript = $view->headScript();
			$layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
			
			$headLink->setContainer(new Zend_View_Helper_Placeholder_Container());
			$headScript->exchangeArray(array());
			
			$jquery  =  $view->jQuery();
		    $jquery->enable();
		    $rmode   = ZendX_JQuery::RENDER_JQUERY_ON_LOAD
							| ZendX_JQuery::RENDER_SOURCES
							| ZendX_JQuery::RENDER_STYLESHEETS;
		
		    $jquery->setRenderMode($rmode);

			$view->headTitle( $view->escape(HEAD_TITLE));
			$view->title    = $view->escape(HEAD_TITLE);
			$view->columns  = $view->modules = $view->bodyClasses = array();
						
			$front->setBaseUrl( BASE_PATH );
			$view->rootPath = $rootPath =  ROOT_PATH;
			$view->jsPath   = $jsPath   =  ROOT_PATH.'/myTpl/public/scripts/';
			$view->cssPath  = $cssPath  =  ROOT_PATH.'/myTpl/public/assets/';
			define("VIEW_BASE_PATH" ,      ROOT_PATH."/myTpl/public" );
			
			$headLink->appendStylesheet( $cssPath ."bootstrap/css/bootstrap.css");
			$headLink->appendStylesheet( $cssPath ."helpers/helpers-all.css");			
			$headLink->appendStylesheet( $cssPath ."elements/elements-all.css");
			$headLink->appendStylesheet( $cssPath ."icons/fontawesome/fontawesome.css");
			$headLink->appendStylesheet( $cssPath ."icons/linecons/linecons.css");
			$headLink->appendStylesheet( $cssPath ."snippets/snippets-all.css");
            $headLink->appendStylesheet( $cssPath ."helpers/border-radius.css");			
			$headLink->appendStylesheet( $jsPath  ."widgets/chosen/chosen.css");
			$headLink->appendStylesheet( $cssPath ."app/color.css");
			$headLink->appendStylesheet( $cssPath ."helpers/colors.css");
			$headLink->appendStylesheet( $cssPath ."app/main.css");
			$headLink->appendStylesheet( $cssPath ."app/responsive.css");
			$headLink->appendStylesheet( $cssPath ."app/custom.css");
			
            //$headScript->appendFile(     $jsPath  ."jquery-1.10.2.min.js");			
			$headScript->appendFile($jsPath."app.min.js");
			$headScript->appendFile($jsPath."init.js");		
			$headScript->appendFile($jsPath."jquery.mobile.js");
			$headScript->appendScript(" jQuery(document).ready(function(){
				                            var isMobile = jQuery.browser.mobile;
				                            jQuery('.clearMessage').click(function(event){event.preventDefault();jQuery(this).closest('#currentMsg').fadeOut('slow');jQuery(this).closest('div#sirah-page-message').fadeOut('slow');});				                                   
	                                    });");			
		}
	}
	
	
		     
  }
