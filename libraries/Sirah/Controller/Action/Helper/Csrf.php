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
 * qui permet de créer des clés de protection des formulaires
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

  class Sirah_Controller_Action_Helper_Csrf  extends Zend_Controller_Action_Helper_Abstract
  {
  	
  	  public function getTokenId( $size = 10 )
  	  {
  	  	$csrfTokenSession  = new Zend_Session_Namespace("csrfTokens");
  	  	if( !isset($csrfTokenSession->tokenId ) ) {
  	  		$csrfTokenSession->tokenId = $this->rand( $size );
  	  		$csrfTokenSession->setExpirationSeconds( 180000 , 'tokenId');
  	  	}
  	  	return $csrfTokenSession->tokenId;
  	  }
  	  
  	  
  	  public function getToken( $size = 300 )
  	  {
  	  	$csrfTokenSession  = new Zend_Session_Namespace("csrfTokens");
  	  	if( !isset( $csrfTokenSession->tokenValue ) ) {
  	  		$csrfTokenSession->tokenValue = hash("sha256", $this->rand( $size ) );
  	  		$csrfTokenSession->setExpirationSeconds( 180000 , 'tokenValue');
  	  	}
  	  	return $csrfTokenSession->tokenValue;
  	  }
  	  
  	  public function isValid( )
  	  {
  	  	$request  = $this->getRequest();
  	  	if (  $request && ( $request->isPost( ) || $request->isGet( ) ) ) {
  	  		  $params = $request->getParams();
  	  		  if( isset( $params[$this->getTokenId()] ) && ( $params[$this->getTokenId()] == $this->getToken() ) ) {
  	  		  	  return true;
  	  		  } 
  	  	} 	  	
  	  	return false;
  	  }
  	  
  	  public function rand( $length = 10 )
  	  {
  	  	if ( @is_readable('/dev/urandom') ) {
  	  		  $f       = @fopen('/dev/urandom', 'r');
  	  		  $urandom = fread($f, $length );
  	  		  fclose($f);
  	  	} 	  	
  	  	$return='';
  	  	for ( $i=0; $i<$length; ++$i ) {
  	  		if (!isset($urandom)) {
  	  			if ($i%2==0) mt_srand(time()%2147 * 1000000 + (double)microtime() * 1000000);
  	  			$rand=48+mt_rand()%64;
  	  		} else {
  	  			$rand=48+ord($urandom[$i])%64;
  	  		} 	  	
  	  		if ($rand>57)   $rand+=7;
  	  		if ($rand>90)   $rand+=6;  	  	
  	  		if ($rand==123) $rand=52;
  	  		if ($rand==124) $rand=53;
  	  		$return.=chr($rand);
  	  	}
  	  	return $return;
  	  }
  	    	  
  	  public function getFormNames( $names , $regenerate )
  	  {
  	  	$values            = array();
  	  	$csrfTokenSession  = new Zend_Session_Namespace("csrfTokens");
  	  	if( !isset( $csrfTokenSession->initialized ) ) {
  	  		$csrfTokenSession->initialized = true;
  	  		$csrfTokenSession->formNames   = array("default");
  	  		$csrfTokenSession->setExpirationSeconds( 180000, 'formNames');
  	  	}
  	  	if( count(  $names ) )    {
  	  	   foreach( $names as $n) {
  	  			if( $regenerate == true ) {
  	  				 unset( $csrfTokenSession->formNames[$n] );
  	  			}
  	  			$s  = isset( $csrfTokenSession->formNames[$n] ) ? $csrfTokenSession->formNames[$n] : $this->rand(10); 
  	  			$csrfTokenSession->formNames[$n] = $s;
  	  			$values[$n] = $s;
  	  		}
  	  	} 	  	
  	  	return $values;
  	  }
  	
   }