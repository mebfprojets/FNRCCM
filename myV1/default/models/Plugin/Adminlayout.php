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
		if(($moduleName != "api") && ($me->isGestionnaires() || $me->isSuperviseur() || $me->isManager() || $me->isAdministrateur() || $me->isAdmin()|| $me->isOPS())) {
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
			$layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
			$view       = $layout->getView();
			$view->headLink()->setContainer(new Zend_View_Helper_Placeholder_Container());
			$view->headScript()->exchangeArray(array());

			$view->headTitle( $view->escape(HEAD_TITLE));
			$view->title    = $view->escape(HEAD_TITLE);
			$view->columns  = $view->modules = $view->bodyClasses = array();
						
			$front->setBaseUrl( BASE_PATH );
			$view->rootPath = $rootPath =  ROOT_PATH;
			$view->jsPath   = $jsPath   =  ROOT_PATH.'/myTpl/public/scripts/';
			$view->cssPath  = $cssPath  =  ROOT_PATH.'/myTpl/public/assets/';
			define("VIEW_BASE_PATH" ,      ROOT_PATH."/myTpl/public" );
			
			$view->headLink()->appendStylesheet( $cssPath ."bootstrap/css/bootstrap.css");
			//$view->headLink()->appendStylesheet( $cssPath ."helpers/helpers-all.css");			
			//$view->headLink()->appendStylesheet( $cssPath ."elements/elements-all.css");
			$view->headLink()->appendStylesheet( $cssPath ."icons/fontawesome/fontawesome.css");
			//$view->headLink()->appendStylesheet( $cssPath ."icons/linecons/linecons.css");
			//$view->headLink()->appendStylesheet( $cssPath ."snippets/snippets-all.css");
            //$view->headLink()->appendStylesheet( $cssPath ."helpers/border-radius.css");			
			$view->headLink()->appendStylesheet( $jsPath  ."widgets/chosen/chosen.css");
			//$view->headLink()->appendStylesheet( $cssPath ."app/color.css");
			//$view->headLink()->appendStylesheet( $cssPath ."helpers/colors.css");
			$view->headLink()->appendStylesheet( $cssPath ."app/main.css");
			$view->headLink()->appendStylesheet( $cssPath ."app/responsive.css");
			$view->headLink()->appendStylesheet( $cssPath ."app/custom.css");
			
			$view->headScript()->appendFile(     $jsPath  ."scripts.min.js");
			$view->headScript()->appendFile(     $jsPath  ."demo.js");
			//$view->headScript()->appendFile(     $jsPath  ."widgets/skrollr/skrollr.js");
			$view->headScript()->appendFile(     $jsPath  ."widgets/bootstrap/bootstrap.min.js");
			$view->headScript()->appendFile(     $jsPath  ."widgets/bootstrap/jquery.dialog2.js");
		    $view->headScript()->appendFile(     $jsPath  ."widgets/bootstrap/jquery.dialog2.helpers.js");			
		    $view->headScript()->appendFile(     $jsPath  ."widgets/chosen/chosen.jquery.min.js");
		    $view->headScript()->appendFile(     $jsPath  ."widgets/chosen/ajaxchosen.min.js");
			//$view->headScript()->appendFile(     $jsPath  ."widgets/sticky/sticky.js");
			$view->headScript()->appendFile(     $jsPath  ."widgets/wow/wow.js");
			$view->headScript()->appendFile(     $jsPath  ."init.js");
			$view->headScript()->appendFile(     $jsPath  ."jquery.dynamic-select.min.js");
			$view->headScript()->appendFile(     $jsPath  ."sirah-1.4.js");		
			
			$view->headScript()->appendScript("
				                            window.SIRAH ||(window.SIRAH={}); 
                                            SIRAH.basePath = \"".VIEW_BASE_PATH."/\";
                                            jQuery(document).ready(function(){
				                                jQuery('.clearMessage').click(function(event){event.preventDefault();jQuery(this).closest('#currentMsg').fadeOut('slow');jQuery(this).closest('div#sirah-page-message').fadeOut('slow');});				                                   
	                                        });
											if(!(/Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i).test(navigator.userAgent || navigator.vendor || window.opera)){
											    skrollr.init({forceHeight: false,smoothScrolling:true});
										    }");
		}
	}
	
	
		     
  }
