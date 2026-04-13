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
 * Cette classe permet de gérer le cache de l'application
 * il va permettre de stocker certainses informations utiles
 * dans le cache
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Cache extends Zend_Cache
{
	
	/**
	 * Sirah_Cache instance
	 * 
	 *  Les instances des gestionnaires de cache
	 */
	protected static $_caches     = array();
		
	 
	 /**
	  * Permet de mettre à jour des données dans le cache
	  *
	  * @param  array   $data les données à enregistrer dans le cache
	  * @param  string  $key  la clé de la donnée à mettre à jour
	  *
	  */
	 public function set($data, $key)
	 {
	 	return $this->save($data, $key);
	 }
	 
	 /**
	  * Permet de recupérer une donnée stockée dans le cache 
	  * à partir de sa clé
	  *
	  * @param string $key  la clé de la donnée qu'on souhaite récupérer
	  * 
	  * @return mixed la donnée que nous voulons
	  *
	  */
	 public function get($key)
	 {
	 	return $this->load($key);
	 }	 	 
	 
	 /**
	  * Permet d'effectuer un nettoyage du cache à partir d'une clé
	  * 
	  * @param string $key   la clé de la donnée qu'on souhaite nettoyer
	  * @param array  $tags  les balises correspondants à ce que nous souhaitons supprimons
	  *
	  * @return boolean true or false
	  *
	  */
	 public function clean($key = null,$tags=array())
	 {
	 	if ($key === null) {
	 		return $this->clean("all",$tags);
	 	}
	 	return $this->remove($key);
	 }
	 
	 /**
	  * Permet de créer ou récupérer une instance du gestionnaire de cache
	  * 
	  * @param   string $instanceName     l'identifiant de l'instance du cache
	  * @param   string $frontend         le nom de l'instance du frontend à créer
	  * @param   string $backend          le nom de l'instance du backend à créer
	  * @param   array  $frontendOptions  les options utilisés dans la création du frontend
	  * @param   array  $backendOptions   les options utilisées dans la création du backend
	  * 
	  * @return Zend_Cache instance
	  *
	  */
	 public static function getInstance( $instanceName=null , $frontend="Core" , $backend="File" , $frontendOptions=array() , $backendOptions=array())
	 {
	 	//On initialise le nom du frontend
	 	$frontend      = ( null==$frontend || !in_array( $frontend,Zend_Cache::$standardFrontends ) ) ? "Core":$frontend;	 	
	 	if(!isset($frontendOptions["lifetime"])){
	 		$frontendOptions["lifetime"]   = 3600;
	 	}
	 	if($backend=="File" && !isset($backendOptions["cache_dir"])){
	 		if(is_dir(APPLICATION_DATA_CACHE)){
	 			$backendOptions["cache_dir"] = APPLICATION_DATA_CACHE;
	 		} elseif(is_dir('./tmp/')){
	 			$backendOptions["cache_dir"] = './tmp/';
	 		} else {
	 			throw new Sirah_Cache_Exception("Impossible de créer le gestionnaire de cache, le dossier de stockage est invalide");
	 		}
	 	}	 	
	 	$instanceName  = (null===$instanceName || empty($instanceName ) ) ? (strtolower($frontend).ucfirst($backend)):$instanceName;
	 	
	 	if (!isset(self::$_caches[$instanceName])) {
	 		self::$_caches[$instanceName]   = Sirah_Cache::factory( $frontend, $backend, $frontendOptions , $backendOptions );
	 	}
	 	Zend_Date::setOptions(array('cache' => self::$_caches[$instanceName] )); // Active aussi pour Zend_Locale
	 	Zend_Translate::setCache( self::$_caches[$instanceName] );
	 	return self::$_caches[$instanceName];
	 }
	 
  }
