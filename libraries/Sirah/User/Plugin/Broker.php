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
 * Cette classe permet de stocker les différents plugins de controle 
 * des utilisateurs de la plateforme basée sur SIRAH
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_User_Plugin_Broker extends Sirah_User_Plugin_Abstract
{		
	
	/**
	 * un tableau de plugins utilisés
	 * array
	 */
	protected $_plugins   = array();
		
	
	/**
	 * Enregistrer un plugin dans le conteneur
	 *
	 * @param  string|Sirah_User_Plugin_Abstract
	 * @param  integer
	 * @return Sirah_User_Plugin_Broker
	 */
	public function register(Sirah_User_Plugin_Abstract $plugin,$priority=null)
	{
		if (false !== array_search($plugin, $this->_plugins, true)) {
			require_once 'Sirah/User/Exception.php';
			throw new Sirah_User_Exception(sprintf("Le plugin %s est déjà enregistré dans le conteneur",get_class($plugin)));
		}		
		$priority = (int) $priority;
		
		if( $priority ) {
			if(isset($this->_plugins[$priority])){
			 require_once 'Sirah/User/Exception.php';
			 throw new Sirah_User_Exception(sprintf("Un plugin est déjà enregistré avec la priorité %s",$priority));
			}			
			$this->_plugins[$priority]  = $plugin;
		} else {
			$priority       = count($this->_plugins);
		    while (isset($this->_plugins[$priority])) {
                ++$priority;
             }
            $this->_plugins[$priority] = $plugin;
		}	
		$user         = $this->getUser();
		
		if( $user instanceof Sirah_User ) {
			$this->_plugins[$priority]->setUser($user);
		}
		ksort($this->_plugins);		
		return $this;
	}
	
	
	/**
	 * Dépiler un plugin du conteneur
	 * 
	 * @param string|Sirah_User_Plugin_Abstract
	 * @return Sirah_User_Plugin_Broker
	 */
	public function unregister($plugin)
	{
		if ($plugin instanceof Sirah_User_Plugin_Abstract) {

			$priority = array_search($plugin, $this->_plugins, true);
			if (false === $priority) {
				require_once 'Sirah/User/Exception.php';
				throw new Sirah_User_Exception('Le plugin n\'est pas enregistré dans le conteneur.');
			}
			unset($this->_plugins[$priority]);
		} elseif (is_string($plugin)) {

			foreach ($this->_plugins as $priority => $_plugin) {
				$type = get_class($_plugin);
				if ($plugin == $type) {
					unset($this->_plugins[$priority]);
				}
			}
		}
		return $this;
		
	}
	
	
	/**
	 * Vérifier si le conteneur contient un plugin
	 *
	 * @param string
	 * @return bool
	 */
	public function hasPlugin($plugin)
	{
		foreach ($this->_plugins as $plugin) {
			$type = get_class($plugin);
			if ($class == $type) {
				return true;
			}
		}		
		return false;
	}
	
	
	/**
	 * Recupérer la liste des plugins du conteneur
	 *
	 * @return array
	 */
	public function getPlugins()
	{
		return $this->_plugins;
	}
	
	/**
	 * Retrouver un plugin se trouvant dans le conteneur
	 *
	 * @param  string $class Class name of plugin(s) desired
	 * @return false|Sirah_User_Plugin_Abstract
	 */
	public function getPlugin($plugin)
	{
		$found = array();
		foreach ($this->_plugins as $plugin) {
			$type = get_class($plugin);
			if ($class == $type) {
				$found[] = $plugin;
			}
		}		
		switch (count($found)) {
			case 0:
				return false;
			case 1:
				return $found[0];
			default:
				return $found;
		}
	}	
	
	/**
	 * Cette méthode est exécutée avant d'entammer le processus complet d'identification de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function beforeLogin($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->beforeLogin( $user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	}
	
	/**
	 * Cette méthode est exécuté avant d'entammer le processus complet déconnexion de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function beforeLogout($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->beforeLogout($user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	}
	
	/**
	 * Cette méthode est exécutée avant d'entammer le processus d'authentification de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function beforeAuth($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->beforeAuth($user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	}
	
	/**
	 * Cette méthode est exécutée avant d'entammer le processus d'autorisation d'accés à une ressource
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function beforeAuthorize($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->beforeAuthorize($user,$args);
			} catch (Exception $e) {
				return $e;
		  }
		}
		return true;
	 }
	 
	/**
	 * Cette méthode est exécutée é la fin du processus d'authentification de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function afterAuth($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->afterAuth($user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	 }	
	
	/**
	 * Cette méthode est exécutée é la fin du processus d'autorisation d'accés é une ressource
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function afterAuthorize($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->afterAuthorize($user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	}
	
	/**
	 * Cette méthode est exécutée é la fin du processus complet d'identification de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function afterLogin($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->afterLogin($user,$args);
			} catch (Exception $e) {
				return $e;
			}
		}
		return true;
	}
	
	
	/**
	 * Cette méthode est exécutée é la fin du processus complet déconnexion de l'utilisateur
	 * @param Sirah_User
	 * @param array des paramétres utilisés par la méthode
	 * @return void
	 */
	public function afterLogout($user,$args=array())
	{
		foreach ($this->_plugins as $plugin) {
			try {
				$plugin->afterLogout($user,$args);
			 } catch (Exception $e) {
				return $e;
			}
		}
		return true;
	 }
	
	
}