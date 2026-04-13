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
 * Cette classe permet de gérer le système de fichiers de l'application
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Filesystem_Folder
{

	/**
	 * @var string le chemin du dossier
	 */
    protected $_path  = "/";
    
    
    /**
     * Le constructeur de la classe
     *
     * @param   string   $src          Le chemin d'accès au dossier
     *
     * @return  void
     *
     * @since
     */
    public function __construct($pathname,$options=array())
    {
    	$pathname = Sirah_Filesystem::cleanPath($pathname);
    	if(!is_dir($pathname)){
    		throw new Sirah_Filesystem_Exception("Le chemin que vous avez indiqué pour la création de l'objet dossier est invalide");
    	}
    	$this->_path  = $pathname;
    }
	
	/**
	 * Permet de faire une copie du dossier
	 *
	 * @param   string   $src          Le chemin de la source du dossier
	 * @param   string   $dest         Le chemin de la destination du dossier.
	 * @param   string   $root         Le chemin de la racine des dossiers.
	 * @param   string   $override     remplace un fichier/dossier semblable ou pas.
	 * @param   Sirah_Filesystem_Transport_Interface  .
	 *
	 * @return  mixte true ou un tableau contenant des erreurs et un rapport de la copie  .
	 *
	 * @since   
	 */
	public function copy($dest,$override = false,Sirah_Filesystem_Transport_Interface $transport= null,&$error='',&$error_code=0)
	{
		
	 }
	 
	 
   
	 /**
	  * Permet de convertir l'objet en un tableau de fichiers et sous-dossiers
	  *
	  * @return  un tableau de tous les sous-dossiers et fichiers du dossier  .
	  *
	  * @since
	  */	
     public function toIterator($pathname="",$options=array())
     {
     	$folderArray  = array();
     	$pathname     = (empty($pathname))?$this->_path:$pathname;
     	$path         = array();

      	$pathIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathname,FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
      	foreach ($pathIterator as $splFileInfo) {
      		$path = $splFileInfo->isDir()
      		? array($splFileInfo->getFilename() => array())
      		: array($splFileInfo->getFilename());
      	
      		for ($depth = $pathIterator->getDepth() - 1; $depth >= 0; $depth--) {
      			$path = array($pathIterator->getSubIterator($depth)->current()->getFilename() => $path);
      		}
      		$folderArray = array_merge_recursive($folderArray, $path);
      	 }
      	 return $folderArray;
      }


  }
