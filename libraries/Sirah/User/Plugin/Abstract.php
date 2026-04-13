<?php
/**
 * SIRAH
 * Ce fichier est un composant de la librairie SIRAH
 *
 * Banao Hamed <banaohamed@yahoo.fr>
 * @copyright   Copyright (C) 2012 - 2015 Open Source Matters, Inc. All rights reserved.
 */


abstract class Sirah_User_Plugin_Abstract
{		
	
	/**
	 * @var Sirah_User Object
	 */
	protected $_user    = null;
		
	
	/**
	 * Mettre � jour l'objet Sirah_User
	 * @param Sirah_User
	 * @return Sirah_User_Plugin
	 * 
	 */
	public function setUser(Sirah_User $user)
	{
		$this->_user  = $user;
		return $this;
	}	
	
	
	/**
	 * R�cuperer l'objet Sirah_User
	 * 
	 * @return Sirah_User
	 *
	 */
	public function getUser()
	{
		return $this->_user;
	}
	
	
	/**
	 * Cette m�thode est ex�cut�e avant d'entammer le processus complet d'identification de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function beforeLogin($user,$args=array())
	{}
	
	/**
	 * Cette m�thode est ex�cut� avant d'entammer le processus complet d�connexion de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function beforeLogout($user,$args=array())
	{}
	
	/**
	 * Cette m�thode est ex�cut�e avant d'entammer le processus d'authentification de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function beforeAuth($user,$args=array())
	{}
	
	/**
	 * Cette m�thode est ex�cut�e avant d'entammer le processus d'autorisation d'acc�s � une ressource
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function beforeAuthorize($user,$args=array())
	{}
	
	/**
	 * Cette m�thode est ex�cut�e � la fin du processus d'authentification de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function afterAuth($user,$args=array())
	{}
	
	
	/**
	 * Cette m�thode est ex�cut�e � la fin du processus d'autorisation d'acc�s � une ressource
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function afterAuthorize($user,$args=array())
	{}
	
	/**
	 * Cette m�thode est ex�cut�e � la fin du processus complet d'identification de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function afterLogin($user,$args=array())
	{}
	
	
	/**
	 * Cette m�thode est ex�cut�e � la fin du processus complet d�connexion de l'utilisateur
	 * @param Sirah_User
	 * @param array des param�tres utilis�s par la m�thode
	 * @return void
	 */
	public function afterLogout($user,$args=array())
	{}
	
}