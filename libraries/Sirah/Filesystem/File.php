<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basee sur les composants des la
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
 * Cette classe fournit de nombreuses methodes 
 * pour traiter un fichier du systeme de l'application.
 * Il modifie quelques comportements par defaut du 
 * systeme de fichiers par defaut de PHP
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */



class Sirah_Filesystem_File
{	
	
	/**
	 * @var array la collection des pointeurs des fichiers
	 */
	
	protected static $_pointers  = array();
	
	/**
	 * @var array la liste des modes d'ouverture possibles
	 */
	public static $open_modes    = array("rb" , "wb" , "ab" , "rb+" , "wb+" , "ab+" , "a+b" , "r+b" , "w+b" ,
			                             "r"  , "w"  , "a"  , "r+"  , "w+"  , "a+");
	
	/**
	 * La fabrique du systeme de gestion des fichiers de la librairie
	 *
	 * @static
	 * @param   string  | Sirah_Filesystem_Adapter_Abstract $adapter le support de gestion des fichiers
	 * @param   string  | array    $params       Les param�tres de cr�ation du support de gestion des fichiers
	 * @param   string   $mode                   Le mode d'acces au fichier.
	 * @param   mixed    $lock                   Le mode de verrouillage du fichier
	 * 
	 * @return  mixed    Sirah_Filesystem_File_Adapter_Abstract instance ou Sirah_Filesystem_Exception
	 *
	 * @since
	 */
	
	static function fabric( $adapter , $params = array() , $mode = "rb" , $lock=false)
	{
		if(is_string($params) && !empty($params)){
			$filename  = $params;
			$params    = array("filename"  => $filename);
		}
		if( !isset( $params["filename"] ) ){
			throw new Sirah_Filesystem_Exception("Une erreur survient dans la création du gestionnaire de fichier : le chemin du fichier n'est pas indiqu�");			
		}
		if( !in_array( $mode , self::$open_modes ) ){
			throw new Sirah_Filesystem_Exception(sprintf("Le mode d'ouverture du fichier %s est invalide" , $params["filename"]));
		}
		$filebasename   = Sirah_Filesystem::basename($params["filename"]);
		$lock           = (bool)$lock;		
		//On crée l'adaptateur de gestion du fichier(création , lecture , modification)
		if(is_string($adapter)){
			$adapterClass  = "Sirah_Filesystem_Adapter_".ucfirst($adapter);
			if(!class_exists( $adapterClass ) ){
				throw new Sirah_Filesystem_Exception("Une erreur survient dans la création du gestionnaire de fichier : L'adaptateur fourni n'est pas valide");
			}
			$adapter  = new $adapterClass( $params["filename"] , $params , $mode, $lock);
		} elseif( !$adapter instanceof Sirah_Filesystem_Adapter_Abstract){
			throw new Sirah_Filesystem_Exception("Une erreur survient dans la création du gestionnaire de fichier : L'adaptateur fourni n'est pas valide");
		} else {
			$params["locked"]      = $lock;
			$params["opened_mode"] = $mode;
			$adapter->setOptions($params);
		}		
		$pointerName    = Sirah_Filesystem::stripExtension($filebasename);
		if(!isset($_pointers[$pointerName][$mode])){
			self::$_pointers[$pointerName][$mode] = $adapter;
		}
		return self::$_pointers[$pointerName][$mode];
	}

  }
