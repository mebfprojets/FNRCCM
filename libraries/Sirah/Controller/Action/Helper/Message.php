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
 * qui permet d'enregistrer les messages d'erreurs
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

  class Sirah_Controller_Action_Helper_Message extends Zend_Controller_Action_Helper_FlashMessenger
  {
  	
  		
  	/**
  	 * Permet d'enregistrer un message d'erreur dans l'application
  	 *
  	 * @static
  	 * @param   string   $message   Le message d'erreur
  	 * @param   string   $type      Le type de message d'erreur ( message | error | warning)
  	 *
  	 * @since
  	 */
  	function addMessage($message , $type="error")
  	{	
  		if (self::$_messageAdded === false) {
  			self::$_session->setExpirationHops(1, null, true);
  		} 		
  		if (!is_array(self::$_session->{$this->_namespace})) {
  			self::$_session->{$this->_namespace} = array();
  		} 		
  		$messages          =  self::$_session->{$this->_namespace};
  		$messages[$type][] = $message;  		
  		self::$_session->{$this->_namespace}  = $messages;
  		
  		return $this;
  	}
  	
  	/**
  	 * hasMessages() - Indique s'il ya des messages stockés dans la session
  	 * 
  	 * @param string  $type le type de message concerné
  	 *
  	 * @return boolean
  	 */
  	public function hasMessages($type=null)
  	{
  		if(null!==$type) {
  			 return isset(self::$_messages[$this->_namespace][$type]);
  		}
  		return isset(self::$_messages[$this->_namespace]);
  	}
  	
  	/**
  	 * getMessages() - Permet de récupérer les messages d'erreurs
  	 *
  	 * @param string  $type le type de message concerné
  	 * 
  	 * @return array
  	 */
  	public function getMessages($type=null)
  	{
  		if ($this->hasMessages($type)) {
  			return (null===$type) ? self::$_messages[$this->_namespace] : self::$_messages[$this->_namespace][$type];
  		} 	
  		return array();
  	}
  	
  	
  	/**
  	 * Supprime tous les messages d'erreur de la requete précédente
  	 * 
  	 * @param  string  $type le type de message d'erreur concerné
  	 * @return boolean True if messages were cleared, false if none existed
  	 */
  	public function clearMessages($type=null)
  	{
  		if ($this->hasMessages($type)) {
  			if(null!==$type){
  				unset(self::$_messages[$this->_namespace][$type]);
  			} else {
  				unset(self::$_messages[$this->_namespace]);
  			}
  			return true;
  		}	
  		return false;
  	}
  	
  	/**
  	 * hasCurrentMessages() - check to see if messages have been added to current
  	 * namespace within this request
  	 *
  	 * @param  string  $type le type de message d'erreur concerné
  	 * @return boolean
  	 */
  	public function hasCurrentMessages($type=null)
  	{
  		$messages  = self::$_session->{$this->_namespace};
  		if(null!==$type){
  			return isset($messages[$type]);
  		}
  		return isset($messages);
  	}
  	
  	/**
  	 * getCurrentMessages() - get messages that have been added to the current
  	 * namespace within this request
  	 *
  	 * @param  string  $type le type de message d'erreur concerné
  	 * @return array
  	 */
  	public function getCurrentMessages($type=null)
  	{
  		$messages  = self::$_session->{$this->_namespace};
  		if ($this->hasCurrentMessages($type)){
  			if(null!==$type){
  				return $messages[$type];
  			}
  			return $messages;
  		} 	
  		return array();
  	}
  	
  	/**
  	 * clear messages from the current request & current namespace
  	 * 
  	 * @param  string  $type le type de message d'erreur concerné
  	 *
  	 * @return boolean
  	 */
  	public function clearCurrentMessages($type=null)
  	{
  		$messages  = self::$_session->{$this->_namespace};
  		if ($this->hasCurrentMessages($type)) {
  			if(null!==$type){
  				unset($messages[$type]);
  			} else {
  				unset($messages);
  			}
  			return true;
  		}  	
  		return false;
  	}
  	 

  	
  	

   }

