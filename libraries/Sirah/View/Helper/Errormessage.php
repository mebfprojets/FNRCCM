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
 * qui permet de gérerb les messages d'erreur de l'application
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

  class Sirah_View_Helper_Errormessage extends Zend_View_Helper_HtmlElement
  {
  	
  		
  	/**
  	 * Permet d'afficher la vue des messages d'erreurs
  	 *
  	 * @param   array    $messages  Le tableau contenant des messages
  	 * @param   string   $type      Le type de message d'erreur ( message | error | warning)
  	 * @param   array    les attributs de l'élement html
  	 *
  	 * @since
  	 */
     public function errormessage( $messages=array() , $type=null , $attribs=array() )
     {
      	$messageHandler  = Zend_Controller_Action_HelperBroker::getStaticHelper("Message");
      	$sessionMessages = $messageHandler->getMessages();
      	$front           = Zend_Controller_Front::getInstance();
      	$request         = $front->getRequest();
      	$current         = $request->isPost();
      	if( empty( $sessionMessages ) && $current ) {
      		$sessionMessages = $messageHandler->getCurrentMessages();
      	}
      	$messages        =  ( empty( $messages ) ) ? $sessionMessages : $messages;      	
      	$messageOutput   = "";
      	$messageBlock    = "";
      	$frMsgTypes      = array("message"       => "Informations !",
      			                 "error"         => "Echecs !",
      			                 "warning"       => "Alertes !",
      			                 "success"       => "Succes ",
      			                 "notice"        => "Notifications",
      			                 "notifications" => "Notifications");
      	if(!isset($attribs["class"])){
      		$attribs["class"]    = array("sirah-message");
      	} else {
      		$attribs["class"]    = (array)$attribs["class"];
      		$attribs["class"][]  = "sirah-message";
      	}
      	
      	$attribs["class"]        = implode(" " , $attribs["class"] );
      	$attribs["id"]           = "sirah-page-message";     	      	
      	if(!empty(   $messages ) ) {     		
      		foreach( $messages as $key => $message ) {
      			$type                   = (null===$type) ? $key : $type;
      			$msgType                = (null===$type || (!array_key_exists($type,$frMsgTypes))) ? strtolower("Message") : strtolower($frMsgTypes[$type]);
      			$classes                = array(" alert " , $this->_getErrorClass($type));
      			$messageOutput         .=" <div class=\"".implode(" ",$classes)."\" >";
      			if( is_array( $message ) ) {
      				$messageOutput     .= "<div class=\"alert-content\"><h4 class=\"alert-title\"> ".strtoupper($msgType)." :</h4> <p> ".implode(" , " , $message)." </p> </div>";
      			} elseif(!empty( $message ) ) {
      				$messageOutput     .= $message;
      			}
      			$messageOutput         .=" <a href=\"#\" class='glyph-icon alert-close-btn icon-remove close clearMessage'></a> </div> \n ";
      		}
      		$messageBlock              = "<div ".$this->_htmlAttribs($attribs)." > ".$messageOutput."  </div>";
      		$messageHandler->clearMessages( $type );
      	}        	
      	return $messageBlock;
      }
      
    

      /**
       * Permet de récupérer l'attribut html correspondant au type d'erreur
       *
       * @static
       * @param   string   $type      Le type de message d'erreur ( message | error | warning)
       *
       * @since
       */
      protected function _getErrorClass($type)
      {
      	$msgHtmlAttrib  = "error";
      	switch(strtolower($type)){
      		case "error":
      		case "erreur":
      		case "danger":
      			$msgHtmlAttrib  = " alert-danger ";
      			break;
      		case "message":
      		case "info":
      		case "information":
      			$msgHtmlAttrib  = " alert-info ";
      			break;
      		case "succes":
      		case "success":
      			$msgHtmlAttrib  = " alert-success ";
      			break;
      		case "warning":
      			$msgHtmlAttrib  = " alert-warning ";
      			break;
      	 }
      	 return $msgHtmlAttrib;
      }

   }

