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
 * Cette classe représente une aide d'action
 * 
 * qui permetde génerer des URLs HTTP, elle est beaucoup semblable à
 * 
 * Zend_Controller_Action_Helper_Redirector
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

  class Sirah_Controller_Action_Helper_HttpUri extends Zend_Controller_Action_Helper_Abstract
  {
  	
  	/**
  	 * Url vers laquelle il faut se rediriger
  	 * @var string
  	 */
  	 protected $_redirectUri = null;
  	 
  	 
  	 
  	 /**
  	  * Cette méthode permet de rediriger une requete vers une URL
  	  * @var string le code de redirection
  	  */
  	 public function redirect( $codeRedirect = "302" )
  	 {
  	 	$uri  = $this->get();
  	 	$this->getResponse()->setRedirect( $uri , $codeRedirect );
  	 }
  	 
  	 /**
  	  * @access public
  	  *
  	  * @param  mixed les paramètres fournis pour la création de l'URL
  	  *
  	  * @return void
  	  */
  	 protected function _create( $uriOptions = null )
  	 {
  	 	$queryParams    = array();
  	 	$host           = ( isset( $uriOptions["host"] ) )   ? $uriOptions["host"]   : ( (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1') );
  	 	$scheme         = ( isset( $uriOptions["scheme"] ) ) ? $uriOptions["scheme"] : ( (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=="off") ? 'https' : 'http' );
  	 	$port           = ( isset( $uriOptions["scheme"] ) ) ? $uriOptions["port"]   : ( isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80);
  	 	$uri            = null;
  	 	$httpUri        = null;
  	 	Zend_Uri::setConfig(array('allow_unwise' => true));
  	 	if( is_string( $uriOptions ) && Zend_Uri::check( $uriOptions ) ) {
  	 		$uriOptions     = array("uri" => $uriOptions);
  	 		$host           = "";
  	 		$scheme         = "";
  	 		$port           = "";
  	 	} elseif( is_string( $uriOptions ) ) {
  	 		$request        = $this->getRequest();
  	 		$thisController = $request->getControllerName();
  	 		$defaultModule  = $this->getFrontController()->getDispatcher()->getDefaultModule();
  	 		$uriOptions     = array("action" => $uriOptions, "controller" => $thisController, "module" => $defaultModule );
  	 	}
  	 	if( empty( $uriOptions ) ||
  	 			( !isset( $uriOptions["uri"] ) && !isset( $uriOptions["action"] ) && !isset( $uriOptions["controller"] ) ) ) {
  	 		throw new Sirah_Controller_Action_Helper_Exception ( "ERREUR:CREATION URL => Les paramètres fournis pour la création de l'URL sont invalides" );
  	 	}
  	 	if( !isset( $uriOptions["action"] ) && isset( $uriOptions["controller"] ) ) {
  	 		$uriOptions["action"]  = "index";
  	 	}
  	 	if( isset( $uriOptions["params"] ) ) {
  	 		$queryParams  = $uriOptions["params"];
  	 		unset( $uriOptions["params"] );
  	 	}
  	 	try {
  	 		$uriPage   = Zend_Navigation_Page::factory( $uriOptions );
  	 		$uri       = trim( $uriPage->getHref() , "/" );
  	 	} catch( Exception $e ) {
  	 		throw new Sirah_Controller_Action_Helper_Exception ( "ERREUR:CREATION URL => ".$e->getMessage() );
  	 	}
  	 	$continueKeys           = array("continue", "uriDone", "done");
  	 	$continueParam          = "";
  	 	foreach( $continueKeys as $continueParamKey ) {
  	 		if( isset( $queryParams[$continueParamKey] ) ) {
  	 			 $continueParam = $continueParamKey ."=" . $queryParams[$continueParamKey] ;
  	 			 unset( $queryParams[$continueParamKey] );
  	 			 break;
  	 		}
  	 	}
  	 	$queryParamsToString    = ( !empty( $queryParams ) ) ? http_build_query( $queryParams ) . ( !empty( $continueParam ) ? "&" . $continueParam  : "" ) : "" . $continueParam;  	 	
  	 	if( Zend_Uri::check( $uri ) ) {
  	 		$this->_redirectUri = trim( $uri );
  	 	} else {
  	 		if( $port != "80" ) {
  	 			$this->_redirectUri = rtrim( $scheme . "://" . $host . ":" . "/"  . $uri . "/" . ( !empty( $queryParamsToString ) ? "?" . $queryParamsToString : "" ) , "//");
  	 		} else {
  	 			$this->_redirectUri = rtrim( $scheme . "://" . $host . "/" . $uri . "/" . ( !empty( $queryParamsToString ) ? "?" . $queryParamsToString : "" ) ,"//" );
  	 		}  	 		
  	 	}  	 	
  	 }
  	 
  	 
  	 public function get( $uriOptions = null )
  	 {
  	 	if( !empty( $uriOptions ) ) {
  	 		$this->_create( $uriOptions );
  	 	}
  	 	return $this->_redirectUri;
  	 }
  	
  	
  	/**
  	 * @access public
	 * 
	 * @param  mixed les paramètres fournis pour la création de l'URL
	 *        	
	 * @return string la valeur de l'URL HTTP
	 */
  	 public function direct( $uriOptions = null )
  	 {
  	 	$uri = $this->get( $uriOptions );
  	 	return $uri ; 	 	
  	 }

  	
  	

   }

