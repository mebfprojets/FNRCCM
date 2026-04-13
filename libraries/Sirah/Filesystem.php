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
 * Cette classe permet de gerer le systeme de fichiers de l'application
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */



defined('OS_WINDOWS') 
  || define('OS_WINDOWS',!strncasecmp(PHP_OS,"win",3));

defined("DS")
  || define("DS",DIRECTORY_SEPARATOR);



class Sirah_Filesystem
{
	
	/**
	 *  Les constantes des options de liste
	 *
	 */
	const LIST_ALL                      = 15;
	const LIST_ONLY_FILES               = 16;
	const LIST_ONLY_DIRS                = 17;
	const LIST_DOT_DIRS                 = 18;

	/**
	 *  Les constantes des parametres de tris 
	 *  
	 */
    const SORT_BY_DATE                  =  20;
    const SORT_BY_SIZE                  =  21;
    const SORT_BY_NAME                  =  22;
    const SORT_BY_TYPE                  =  23;
    const SORT_BY_RANDOM                =  24;
    const SORT_BY_REVERSE               =  25;
    
    
    /**
     *  Les constantes des codes des erreurs produites
     *
     */
    const ERROR_COPY_FILENOTFOUND      = -20;
    const ERROR_COPY_FILEXIST          = -21;
    const ERROR_COPY_INVALIDPATH       = -22;
    const ERROR_COPY_SRCNOTREADABLE    = -23;
    const ERROR_COPY_DESTNOTWRITABLE   = -24;
    const ERROR_COPY_FAILED            = -25;
    const ERROR_LIST_DIRNOTREADABLE    = -26;
    
	
	/**
	 * Permet de copier un dossier ou un fichier dans un dossier destination
	 *
	 * @static
	 * @param   string   $src          Le chemin de la source du dossier
	 * @param   string   $dest         Le chemin de la destination du dossier.
	 * @param   string   $basepath     Le chemin de la racine des dossiers.
	 * @param   bool     $override     doit-il remplacer un fichier/dossier semblable existant.
	 * @param   Sirah_Filesystem_Adapter_Interface  .
	 *
	 * @return  mixed true ou un tableau contenant des erreurs et un rapport de la copie  .
	 *
	 * @since   
	 */
	public static function copy($src,$dest,$basepath='',$override=false,Sirah_Filesystem_Adapter_Interface $adapter=null)
	{		
		@ini_set('max_execution_time',100000);
		@ini_set('xdebug.max_nesting_level',10000);
		
		if( $basepath && $basepath!=$src && $basepath!=$dest){
			$src         = $basepath.DS.$src;
			$dest        = $basepath.DS.$dest;
		 }
		 $srcPath        = rtrim($src,DS);
		 $destPath       = rtrim($dest,DS);
		 $destDirPath    = self::basename($destPath);
		 
		 if(!self::exists($destDirPath)){
		 	self::mkDir($destDirPath, 0777);
		 }
		 		       
		 //On fait quelque verifications des dossiers sources et de destinations
		 if(!self::exists($srcPath)){
		 	$msg       = "Une erreur de copie a ete generee par le systeme : le fichier source $srcPath est introuvable";
		 	return Sirah_Error::raiseError(self::ERROR_COPY_FILENOTFOUND, $msg);
		 }		 
		 if(!self::isReadable($srcPath)){
		 	$msg       = "Une erreur de copie a ete generee par le systeme : le fichier source $srcPath est innacessible en lecture";
		 	return Sirah_Error::raiseError(self::ERROR_COPY_SRCNOTREADABLE, $msg);
		 }		 
		 if(self::isDir($destPath) && !self::isWritable($destPath)){
		 	$msg       = "Une erreur de copie a ete generee par le systeme : le fichier de destination $destPath est innacessible en ecriture";
		 	return Sirah_Error::raiseError(self::ERROR_COPY_DESTNOTWRITABLE, $msg);
		 }
		 
		 //Si l'objet source est un repertoire, on copie le contenu du repertoire dans le dossier de destination
		 if(self::isDir($srcPath)){
              $dirHandle   = @opendir($srcPath);	    
		 	  while(false !== ($fileEntry=@readdir($dirHandle))){
		 	  	if($fileEntry==="." || $fileEntry===".."){
		 	  		continue;
		 	  	}
		 		$entrySrcPath  = $srcPath.DS.$fileEntry;
		 		$entryDestPath = $destPath.DS.$fileEntry;
		 		if( true!=($copyResult = self::copy($entrySrcPath,$entryDestPath,'',$override,$adapter))){
		 			return $copyResult;
		 		 }		 				 		
		 	   }
		 	@closedir($dirHandle); 	 	
		 }
		 else {	 	
		    //Si un adaptateur de copie est fourni, on l'utilise pour effetuer la copie, sinon on utilise la methode de copie par defaut
		 	if(null!==$adapter && $adapter instanceof Sirah_Filesystem_Adapter_Interface){
		 		if(true!=($adapterResult = $adapter->copy($destPath,'',$override))){
		 			return $adapterResult;
		 		}
		 	}
		 	else{
		 		if(self::exists($destPath) && !$override && (@filemtime($srcPath) > @filemtime($destPath))){
		 			$msg       = "Une erreur de copie a ete generee par le systeme : le fichier $destPath existe dejà, impossible de l'ecraser";
		 			return Sirah_Error::raiseError(self::ERROR_COPY_FILEXIST, $msg);
		 		}
		 		if(empty($error)){
		 		  if(!copy($srcPath,$destPath)){
		 		  	  $msg       = "Une erreur de copie a ete generee par le systeme : la copie du fichier $srcPath dans $destPath **** $destDirPath a echoue";
		 		  	  return Sirah_Error::raiseError(self::ERROR_COPY_FAILED, $msg);
		 		   }
		 		}
		 	}
		 }		 
		 return true;
	 }	 
	    
	 /**
	  * Permet de deplacer un dossier ou un fichier dans un dossier de destination
	  *
	  * @static
	  * @param   string   $src          Le chemin de la source du dossier
	  * @param   string   $dest         Le chemin de la destination du dossier.
	  * @param   bool   $override       doit-il remplacer un fichier/dossier semblable existant.
	  * @param   bool  $recursive       doir-il deplacer le dossier de façon recursive ?
	  * @param   Sirah_Filesystem_Adapter_Interface  .
	  *
	  * @return  mixed true ou un tableau contenant des erreurs et un rapport de la copie  .
	  *
	  * @since
	  */
	 public static function move($src,$dest,$override=true,$recursive=true,Sirah_Filesystem_Adapter_Interface $adapter=null)
	 {
	 	if(true!=($copyRes   = self::copy($src,$dest,$override,$adapter))){
	 		return $copyRes;
	 	}
	    if(true!=($removeRes = self::remove($src,$recursive,$error,$error_code))){
	    	return $removeRes;
	    }
	 	return true;
	 }
	 
	 
	 /**
	  * Permet de supprimer une liste de fichiers ou de dossiers
	  *
	  * @static
	  * @param   string | array   $files  un tableau des fichiers à supprimer
	  * @param   bool                     la suppression doit-elle etre recursive ?
	  *
	  * @return  mixed true ou un tableau contenant des erreurs et un rapport de la copie  .
	  * @throws Sirah_Filesytem_Exception
	  * @since
	  */
	 public static function remove( $files , $recursive=true,Sirah_Filesystem_Adapter_Interface $adapter=null)
	 {
	 	@ini_set('max_execution_time',1000000);
	 	@ini_set('xdebug.max_nesting_level',100000);
	 	$files  = (array)$files;
	 	foreach($files as $file){
	 		if(!self::exists($file)){
	 			continue;
	 		}
	 		//on donne des permissions d'ecriture du fichier ou du dossier
	 		self::chmod($file, 0777);	 		
	 		//On effectue la suppresion selon si c'est un dossier ou un fichier
	 		if(self::isDir($file) && !self::isLink($file) && $recursive){
	 			$dirHandle  = @opendir($file);
	 			while(false!==($dirEntry=@readdir($dirHandle))){
	 				self::remove(array($file.DS.$dirEntry),true);
	 			}
	 		   @closedir($dirHandle);
	 	    if (true !== @rmdir($file)) {
	 	    	return Sirah_Error::raiseError(0, sprintf('La suppression du dossier %s a echoue', $file));
	 		 }
	 	   }  else {	 	   	    
	 	   	    if(null!==$adapter){
	 	   	    	$adapter->remove($file);
	 	   	    }
                // https://bugs.php.net/bug.php?id=52176
                if (defined('PHP_WINDOWS_VERSION_MAJOR') && is_dir($file)) {
                    if(true !== @rmdir($file)) {
                      return Sirah_Error::raiseError(0, sprintf('La suppression du fichier %s a echoue', $file));
                    }
                } else {
                    if (true !== @unlink($file)) {
                       return Sirah_Error::raiseError(0, sprintf('La suppression du fichier %s a echoue', $file));
                    }
                }
            }
	 	}
	 	return true;	 	 
	 }
	 
	 
	 /**
	  * Permet de renommer un fichier ou un dossier
	  *
	  * @static
	  * @param   string   $old         L'ancien nom
	  * @param   string   $new         Le nouveau nom.
	  * @param   string   $context     Le contexte (optionnel et supporte uniquement par le PHP5)
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function rename($old,$new,$context=null)
	 {
	 	if(!self::isReadable($old)){
	 		return Sirah_Error::raiseError(0,sprintf("Impossible de renommer le fichier/dossier %s, car il n'est pas accessible en lecture",$old));
	 	}
	    if(true!==@rename($old,$new,$context)){
	       return Sirah_Error::raiseError(0,sprintf("Impossible de renommer le fichier/dossier %s",$old));
	    }	    	
	     return true;
	  }
	 	 
	 /**
	  * Permet de lister tout le contenu d'un dossier
	  *
	  * @static
	  * @param   string   $pathname          Le chemin du dossier
	  * @param   int      $list_option       definit les options du listing(par defaut il liste tout sauf les points)
	  * @param   int      $sort              definit les modes de tri du contenu(par defaut il fait le tri par hasard)
	  * @param   mixed    $callback          une fonction appelee sur chaque entree du dossier
	  * 
	  * @return  array un tableau contenant la liste des fichiers du dossier.
	  *
	  * @since
	  */
	 public static function listDir($pathname,$list_option=15,$sort=24,$callback=null)
	 {
	 	@ini_set('max_execution_time',100000);
	 	@ini_set('xdebug.max_nesting_level',10000);
	 	 $dirEntries         = array();	 	 
	 	 if(self::isDir($pathname)){
	 	 	for ($dir = dir($pathname); false !== $dirEntry = $dir->read(); ) {
	 	 		if(($list_option & self::LIST_DOT_DIRS) || $dirEntry{0}!=="."){
	 	 			$isDir  = (self::isDir($dirEntry) || $dirEntry==="." || $dirEntry==="..");
	 	 			if(((  !$isDir && $list_option & self::LIST_ONLY_FILES )
	 	 				|| ($isDir && $list_option & self::LIST_ONLY_DIRS) 
	 	 			    || ($list_option & self::LIST_ALL)
	 	 					)
	 	 				&& (!is_callable($callback) || call_user_func_array($callback, array($dirEntry)))){
	 	 				$dirEntries[]  = array(  "name"       => $dirEntry,
	 	 						                 "extension"  => ($isDir)  ? null : self::getFilextension($pathname.DS.$dirEntry),
	 	 						                 "size"       => self::getSize($pathname.DS.$dirEntry),
	 	 						                 "date"       => @filemtime($pathname.DS.$dirEntry),
	 	 						                    );
	 	 			}
	 	 		}
	 	 	}
	 	 	$dir->close();	 	 	
	 	 	if($sort){
	 	 	  $dirEntries = self::sort($dirEntries,$sort);
	 	 	}
	 	 }
	 	 return $dirEntries;
	  }
	 
	  /**
	   * Permet de recuperer l'extension d'un fichier
	   *
	   * @static
	   * @param   string   $filename         le nom du fichier
	   * @param   string   $filextension     l'extension du fichier ou false en cas d'echec
	   *
	   * @since
	   */
	  public static function getFilextension($filename)
	  {
        return preg_replace('/^.+\.([^.]+)$/D', '$1', $filename);        
	   }
	   
	   /**
	    * Permet de recuperer le nom du fichier sans l'extension
	    *
	    * @static
	    * @param   string   $filename     Le nom du fichier
	    * @return  string   retourne le nom du fichier sans l'extension .
	    *
	    * @since
	    */
	   public static function stripExtension($filename)
	   {
	   	 return preg_replace('#\.[^.]*$#','', $filename);
	   }
	  
	  
	  /**
	   * Permet de creer un dossier
	   *
	   * @static
	   * @param   array    $dirs              Un tableau contenant les dossier à creer
	   * @param   int      $mode              les permissions du dossier à creer
	   * @param   boolean  $recursive         definit si la creation du dossier est recursive ou pas
	   *
	   * @since
	   */
	 public static function mkDir( $dirs , $mode = 0777 , $recursive=true)
	 {
	 	$dirs         = self::toIterator($dirs);
	 	$createdDirs  = array();
	 		foreach($dirs as $dir){
	 			if( self::isDir($dir) ){
	 				$createdDirs[]  = $dir;
	 				continue;
	 			}	 			
	 		 $origmask = @umask(0);
	 		 if( true!= mkdir( $dir , $mode , $recursive)){
	 		 	 @umask($origmask);
	 		 	 $createdDirs[]  = $dir;
	 		 }
	 	  }
	 	  @umask($origmask);
	 	  return ( count($createdDirs) == count($dirs) );
	 }
	 
	 
	 /**
	  * Retourne le dossier temporaire relatif soit aux dossiers temporaires de la variable d'environnement du systeme
	  *
	  * @static
	  * @access  public
	  * @return  string  le chemin du dossier temporaire
	  */
	 public static function tmpDir()
	 {
	 	if (OS_WINDOWS) {
	 		if (isset($_ENV['TEMP'])) {
	 			return $_ENV['TEMP'];
	 		}
	 		if (isset($_ENV['TMP'])) {
	 			return $_ENV['TMP'];
	 		}
	 		if (isset($_ENV['windir'])) {
	 			return $_ENV['windir'] . '\\temp';
	 		}
	 		if (isset($_ENV['SystemRoot'])) {
	 			return $_ENV['SystemRoot'] . '\\temp';
	 		}
	 		if (isset($_SERVER['TEMP'])) {
	 			return $_SERVER['TEMP'];
	 		}
	 		if (isset($_SERVER['TMP'])) {
	 			return $_SERVER['TMP'];
	 		}
	 		if (isset($_SERVER['windir'])) {
	 			return $_SERVER['windir'] . '\\temp';
	 		}
	 		if (isset($_SERVER['SystemRoot'])) {
	 			return $_SERVER['SystemRoot'] . '\\temp';
	 		}
	 		return '\temp';
	 	}
	 	if (isset($_ENV['TMPDIR'])) {
	 		return $_ENV['TMPDIR'];
	 	}
	 	if (isset($_SERVER['TMPDIR'])) {
	 		return $_SERVER['TMPDIR'];
	 	}
	 	return '/tmp';
	 }
	 
	 
	 
	 /**
	  * Permet de verifier si des fichiers existent dans le systeme de fichiers
	  *
	  * @static
	  * @param   string | array  $files    un tableau des fichiers à verifier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function exists( $files )
	 {
	 	$files  = self::toIterator($files);
	 		foreach($files as $filename){
	 			if(!@file_exists($filename)){
	 				return false;
	 			}
	 		}
	 	return true;	 	 
	 }
	 
	 
	 
	 /**
	  * Permet de faire une recherche de fichiers dans un ou plusieurs dossiers
	  *
	  * @static
	  * @param   string | array  $filesDirs    un tableau des dossiers de recherche
	  * @param   string          $name         Le nom du fichier/dossier recherche ou une syntaxe interne
	  * @param   string          $type         L'extension du fichier recherche
	  * @param   int             $size         La taille du fichier/dossier recherche
	  * @param   int             $date         Le timestamp correspondant à la date de modification du fichier ou du dossier
	  * @param   string |array   $regexes      une ou plusieurs expressions regulieres pour la recherche
	  * @param   bool            $recursive    la recherche doit-elle etre recursive ?
	  *
	  * @return   array la liste des fichiers retrouves .
	  *
	  * @since
	  */
	 public static function find($filesDirs,$name,$type=null,$size=0,$date=null,$regexes=array(),$recursive=true)
	 {
	 	$name            = trim($name);
	 	$type            = trim(strtolower($type));
	 	$size            = intval($size);
	 	$date            = intval($date);
	 	$filesDirs       = (array)$filesDirs;
	 	$found           = array();
	 	$searchMatches   = array();
	 	
	 	//Par defaut, on cree des masques internes pour verifier la syntaxe du nom du fichier/dossier recherche
	 	$internalRegex   = array("(?:\s*--name\s'([a-zA-Z]*)')",
	 			                 "(?:\s*--type\s'([a-zA-Z]*)')",
	 			                 "(?:\s*--date\s'?([0-9]*)'?)",
	 			                 "(?:\s*--size\s'?([0-9]*)'?)",
	 			                 "(?:\s*\*\.([a-zA-Z]*))");
	 	
	 	preg_match("/".implode("?",$internalRegex)."?/i",$name,$searchMatches);
	 	
	 	//Si le nom du fichier ou dossier est fourni
	 	//On verifie que le nom ne correspond pas à la recherche de tous les fichiers
	 	if((null==$name || !empty($name)) && $name=="*.*"){
	 		$name  = "all";	
	 	 }	 	 
	 	//On verifie que la recherche correspond à la syntaxe " find --name 'nomfichier' "
	 	if((null==$name || !empty($name)) && isset($searchMatches[1])){
	 		$name  = $searchMatches[1];
	 	}
	 	//On verifie que la recherche correspond à la syntaxe " find --type 'extension' "
	 	if((null==$name || !empty($name)) && isset($searchMatches[2])){
	 	   $type   = $searchMatches[2];
	 	}
	 	if((null==$name || !empty($name)) && isset($searchMatches[3])){
	 		$date   = $searchMatches[3];
	 	}
	 	if((null==$name || !empty($name)) && isset($searchMatches[4])){
	 		$size   = intval($searchMatches[4]);
	 	}
	 	if((null==$name || !empty($name)) && isset($searchMatches[5])){
	 		$type  = $searchMatches[5];
	 	}

	 	$searchMatches = array();
	 	$customRegex   = (count($regexes))?"/".implode("?",$regexes)."?/i":null;
	 		 	
	 	if(count($filesDirs)){
	 		foreach($filesDirs as $fileDir){
	 			
	 		    if(!self::exists($fileDir)){
	 				continue;
	 			}
	 			$nameOk     = false;
	 			$typeOk     = false;
	 			$sizeOk     = false;
	 			$dateOk     = false;
	 			$isDir      = (self::isDir($fileDir))?true:false;
	 			
	 			$filename   = self::basename($fileDir);
	 			$filetype   = ($isDir)  ? null  :  self::getFilextension($fileDir);
	 			$filesize   = self::getSize($fileDir);
	 			$filedate   = @filemtime($fileDir);
	 			$fileFound  = array();
	 				 			
	 			//On verifie le nom
	 			if(null===$name || empty($name) || ($filename==$name) || ($name=="all")){
	 				$nameOk  = true;
	 			}
	 			if(null!==$customRegex && !empty($customRegex)){
                   if(preg_match($customRegex,$filename,$searchMatches)){
                   	$nameOk = true;
                   }
	 			}	 			
	 			if(null==$type || empty($type) || ($filetype==$type)){
	 				$typeOk  = true;
	 			}
	 			if(0==$size  || 0==$filesize || ($filesize>=$size)){
	 				$sizeOk  = true;
	 			}	 			
	 			if(0==$date  || 0==$filedate || ($filedate<=$date)){
	 				$dateOk  = true;
	 			}	 			
	 			
	 			if($isDir && $recursive){
	 				$dirHandle  = @opendir($fileDir);
	 				while(false!==($dirEntry=@readdir($dirHandle))){
	 					if($dirEntry=="." || $dirEntry==".."){
	 						continue;
	 					}
	 			      $filesFound  = self::find(array($fileDir.DS.$dirEntry),$name,$type,$size,$date,$regexes,true);
	 			      if(!empty($filesFound)){
	 			      	foreach($filesFound as $fileFound){
	 			      		$found[] = $fileFound;
	 			      	  }
	 			      	  $found[]   = $fileDir.DS.$dirEntry;
	 			        }
	 				  }
	 				@closedir($dirHandle);	 				
	 			 }
	 			 
	 			 if($nameOk && $typeOk && $sizeOk && $dateOk && !$isDir){
	 			 	$found[]  = $fileDir;
	 			 }
	 		  }
	 	    }

	 	    return $found;
	     }
	     
      
      /**
       * Permet de faire le nettoyage de certains caracteres dans le nom du fichier
       *
       * @static
       * @param   string   $filename
       *
       * @return  mixte true ou un boolean false en cas d'echec.
       *
       * @since
       */
      public static function safeFilename($filename)
      {
      	if( OS_WINDOWS ){
      		$filename   = strtolower($filename);
      	}
      	$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\-\\ ]#', '#^\.#');
      	return preg_replace($regex, '', $filename);
      }
	 
	 
    /**
	  * Permet de recuperer la taille d'un fichier ou d'un dossier
	  *
	  * @static
	  * @param   string  $file     le chemin du fichier ou du dossier
	  * @param   int     $fileSize la taille de depart du fichier ou du dossier
	  * @param   string  $format   le format de taile à retourner
	  *
	  * @return int      $filesize la taille calculee
	  * 
	  * @throws  Sirah_Filesystem_Exception genere une exception au cas ou le fichier/dossier n'existe pas
	  * 
	  * @since
	  */
	 public static function getSize($file,$fileSize=0)
	 {
	 	@ini_set('max_execution_time',100000);
	 	@ini_set('xdebug.max_nesting_level',10000);
	     if(!self::exists($file)){
	     	return $fileSize;
	     }
	     if(self::isDir($file)){
	     	$dirHandle = @opendir($file);
	     	while(false!==($dirEntry=@readdir($dirHandle))){
	     		if($dirEntry=="." || $dirEntry==".."){
	     			continue;
	     		}
	     		if(self::isDir($file.DS.$dirEntry)){
	     			$fileSize+=self::getSize($file.DS.$dirEntry);
	     		}
	     		else{
	     			$fileSize+=@filesize($file.DS.$dirEntry);
	     		}   		
	     	 }
	     	 @closedir($dirHandle);
	     }
	     else{
	     	$fileSize+=@filesize($file);
	     }	    
	     return $fileSize;
	 }
	 
	 
	 /**
	  *  Permet de trier des fichiers
	  *
	  * @static
	  * @access  public
	  * @return  array
	  * @param   array   $files
	  * @param   int     $sort
	  */
	 function sort($files, $sort=22)
	 {	 	
	 	if (!$files) {
	 		return array();
	 	}
	 
	 	if (!$sort) {
	 		return $files;
	 	}
	 
	 	if ($sort === 25) {
	 		return array_reverse($files);
	 	}
	 
	 	if ($sort & self::SORT_BY_RANDOM) {
	 		shuffle($files);
	 		return $files;
	 	}
	 
	 	$names = array();
	 	$sizes = array();
	 	$dates = array();
	 	$types = array();
	 
	 	if ($sort & self::SORT_BY_NAME) {
	 		$r = &$names;
	 	} elseif ($sort & self::SORT_BY_DATE) {
	 		$r = &$dates;
	 	} elseif ($sort & self::SORT_BY_SIZE) {
	 		$r = &$sizes;
	 	} 
	    elseif ($sort & self::SORT_BY_TYPE) {
	 		$r = &$types;
	 	} else {
	 		asort($files, SORT_REGULAR);
	 		return $files;
	 	}
	 
	 	$sortFlags = array(
	 			self::SORT_BY_NAME => SORT_STRING,
	 			self::SORT_BY_DATE => SORT_NUMERIC,
	 			self::SORT_BY_SIZE => SORT_NUMERIC,
	 	);
	 
	 	foreach ($files as $file) {
	 		$names[] = (isset($file["name"]))?$file["name"]:null;
	 		$sizes[] = (isset($file["size"]))?$file["size"]:0;
	 		$dates[] = (isset($file["date"]))?$file["date"]:null;
	 		$types[] = (isset($file["type"]))?$file["type"]:null;
	 	}
	 
	 	if ($sort & self::SORT_BY_REVERSE) {
	 		arsort($r, $sortFlags[$sort & ~1]);
	 	} else {
	 		asort($r, $sortFlags[$sort]);
	 	}
	 
	 	$result = array();
	 	foreach ($r as $i => $f) {
	 		$result[] = $files[$i];
	 	}
	 
	 	return $result;
	 }
	 
    /**
     * Permet de changer la permission d'un fichier ou d'un dossier de facon recursive
     *
     * @param string |array             $files      Les noms de fichiers ou de dossiers
     * @param integer                   $mode       Le nouveau mode (octal)
     * @param integer                   $umask      Le mode umask (octal)
     * @param Boolean                   $recursive  Si le changement du mode doit etre fait de facon recursive
     *
     * @throws Sirah_Error_Exception
     */
    public static function chmod($files, $mode, $umask = 0000, $recursive = false)
    {
    	if(is_string($files)){
    	    $files  = (array)$files;
    	 }  	 
    	 if(is_array($files) && count($files)){
    	 	foreach($files as $file){
    	 		if(self::isDir($file) && $recursive){
    	 			$dirHandle  = @opendir($file);
    	 			while(false!=($dirEntry=@readdir($dirHandle))){
    	 				$dirFile = $file.DS.$dirEntry;
    	 				if(self::isDir($dirFile) && $recursive){
    	 					self::chmod(array($dirFile),$mode,$umask,$recursive);
    	 				}
    	 				else{
    	 					if (true !== @chmod($dirFile, $mode & ~$umask)) {
    	 						throw new Sirah_Error_Exception(sprintf('La fonction chmod du systeme de fichiers a echoue sur le fichier %s', $dirFile));
    	 					}
    	 				}
    	 			}
    	 		}   	 		
    	 		if (true !== @chmod($file, $mode & ~$umask)) {
    	 			throw new Sirah_Error_Exception(sprintf('La fonction chmod du systeme de fichiers a echoue sur le fichier %s', $file));
    	 		}
    	 	}
    	 }    	
      }
	 
      /**
       * Permet de changer le proprietaire des fichiers ou dossiers fournis 
       *
       * @param string|array              $files     Les noms de fichiers ou de dossiers dont on veut changer le proprietaire
       * @param string                    $user      Le nom d'utilisateur du nouveau proprietaire
       * @param Boolean                   $recursive  Si le changement du proprietaire doit etre fait de facon recursive
       *
       * @throws Sirah_Error_Exception
       */
      public static function chown($files, $user, $recursive = false)
      {
	 	if(is_string($files)){
	 		$files  = (array)$files;
	 	}
	 	if(is_array($files) && count($files)){
	 		foreach($files as $file){
	 			if(self::isDir($file) && !self::isLink($dirFile) && $recursive){
	 				$dirHandle  = @opendir($file);
	 				while(false!=($dirEntry=@readdir($dirHandle))){
	 					$dirFile = $file.DS.$dirEntry;
	 					if(self::isDir($dirFile) && !self::isLink($dirFile) && $recursive){
	 						self::chown(array($dirFile),$user,$recursive);
	 					} elseif(self::isLink($dirFile) && function_exists("lchown")){
	 						if (true !== @lchown($dirFile, $user)) {
	 							throw new Sirah_Error_Exception(sprintf('La fonction chown du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 						}
	 					} else {
	 						if (true !== @chown($dirFile,$user)) {
	 							throw new Sirah_Error_Exception(sprintf('La fonction chown du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 						}
	 					}
	 				}
	 			}
	 			elseif(self::isLink($file) && function_exists("lchown")){
	 				if (true !== @lchown($file, $user)) {
	 					throw new Sirah_Error_Exception(sprintf('La fonction chown du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 				}
	 			}
	 	        else{
	 			      if (true !== @chown($file,$user)) {
	 				      throw new Sirah_Error_Exception(sprintf('La fonction chown du systeme de fichiers a echoue sur le fichier %s', $file));
	 			       }
	 	          }
	 		   }
	 	   }	 
	 }
	 
     /**
       * Permet de changer lr proprietaire des fichiers ou dossiers fournis 
       *
       * @param string|array              $files     Les noms de fichiers ou de dossiers dont on veut changer le proprietaire
       * @param string                    $group      Le nom du groupe
       * @param Boolean                   $recursive  Si le changement du mode doit etre fait de facon recursive
       *
       * @throws Sirah_Error_Exception
       */
      public static function chgrp($files, $group, $recursive = false)
      {
	 	if(is_string($files)){
	 		$files  = (array)$files;
	 	}
	 	if(is_array($files) && count($files)){
	 		foreach($files as $file){
	 			if(self::isDir($file) && !self::isLink($dirFile) && $recursive){
	 				$dirHandle  = @opendir($file);
	 				while(false!=($dirEntry=@readdir($dirHandle))){
	 					$dirFile = $file.DS.$dirEntry;
	 					if(self::isDir($dirFile) && !self::isLink($dirFile) && $recursive){
	 						self::chown(array($dirFile),$group,$recursive);
	 					}
	 					elseif(self::isLink($dirFile) && function_exists("lchgrp")){
	 						if (true !== @lchgrp($dirFile, $group)) {
	 							throw new Sirah_Error_Exception(sprintf('La fonction chgrp du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 						}
	 					}
	 					else{
	 						if (true !== @chgrp($dirFile,$group)) {
	 							throw new Sirah_Error_Exception(sprintf('La fonction chgrp du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 						}
	 					}
	 				}
	 			}
	 			elseif(self::isLink($file) && function_exists("lchgrp")){
	 				if (true !== @lchgrp($file, $group)) {
	 					throw new Sirah_Error_Exception(sprintf('La fonction chgrp du systeme de fichiers a echoue sur le fichier %s', $dirFile));
	 				}
	 			}
	 	        else{
	 			      if (true !== @chgrp($file,$group)) {
	 				    throw new Sirah_Error_Exception(sprintf('La fonction chgrp du systeme de fichiers a echoue sur le fichier %s', $file));
	 			       }
	 	          }
	 		   }
	 	   }	 
	 }
	 
	
	 /**
	  * Permet de recuperer le nom de base d'un fichier ou un dossier
	  *
	  * @static
	  * @param string $filepath le chemin du fichier ou du dossier
	  *
	  * @return le nom du fichier ou du dossier
	  */
	 public static function basename( $filepath )
	 {
	 	$fileInfos     = pathinfo($filepath);
	 	$dirname       = $fileInfos["dirname"];
	 	$basename      = $fileInfos["basename"];
	 	$fileExtension = self::getFilextension($filepath);
	 	$filename      = (version_compare(PHP_VERSION,"5.2.0",">=")) ? $fileInfos["filename"] : (substr($basename , 0 , strrpos($basename , ".")));
	 	$filename     .= ".".$fileExtension;
	 	return (self::isDir($filename)) ? $dirname : $filename;
	 }
	 
	 /**
	  * Permet de recuperer le nom de base d'un dossier
	  *
	  * @static
	  * @param string $filepath le chemin du fichier ou du dossier
	  *
	  * @return le nom du fichier ou du dossier
	  */
	 public static function mb_basename($filename, $separator = "/") 
     { 
	       $filename  = str_ireplace(array("\\","/","//","\\\\"),$separator, $filename );
           return end(explode($separator ,$filename)); 
     } 
	 
	 /**
	  * Permet de recuperer le nom de base d'un fichier (sans l'extension)
	  *
	  * @static
	  * @param string $filepath le chemin du fichier ou du dossier
	  *
	  * @return le nom du fichier ou du dossier
	  */
	public function getName( $filepath )
	{
		$fileInfos     = pathinfo( $filepath );
	 	$dirname       = $fileInfos["dirname"];
	 	$basename      = $fileInfos["basename"];	 	
	 	return substr($basename , 0 , strrpos( $basename , "."));
	}
	 
	 
    /**
     * Permet de convertir un chemin bien donne en chemin relatif à un chemin parent
     * Copie de la methode de Symphony
     *
     * @static
     * @param string $endPath   Absolute path of target
     * @param string $startPath Absolute path where traversal begins
     *
     * @return string Path of target relative to starting path
     */
    public static function makePathRelative($endPath, $startPath)
    {
        // Normalize separators on windows
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $endPath   = strtr($endPath, '\\', '/');
            $startPath = strtr($startPath, '\\', '/');
        }

        // Split the paths into arrays
        $startPathArr = explode('/', trim($startPath, '/'));
        $endPathArr = explode('/', trim($endPath, '/'));

        // Find for which directory the common path stops
        $index = 0;
        while (isset($startPathArr[$index]) && isset($endPathArr[$index]) && $startPathArr[$index] === $endPathArr[$index]) {
            $index++;
        }

        // Determine how deep the start path is relative to the common path (ie, "web/bundles" = 2 levels)
        $depth = count($startPathArr) - $index;

        // Repeated "../" for each level need to reach the common path
        $traverser = str_repeat('../', $depth);

        $endPathRemainder = implode('/', array_slice($endPathArr, $index));

        // Construct $endPath from traversing to the common path, then to the remaining $endPath
        $relativePath = $traverser . (strlen($endPathRemainder) > 0 ? $endPathRemainder . '/' : '');

        return (strlen($relativePath) === 0) ? './' : $relativePath;
    }  	 
	 
	
   /**
	 * Permet de retirer les slash inutiles
	 *
	 * @static
	 * @param	string	$path	Le chemin à nettoyer
	 * @param	string	$ds		Directory separator (optional)
	 * @return	string	Le chmin nettoye
	 * @since	
	 */
	function cleanPath($path, $ds=DS)
	{
		$path = trim($path);
		if (!empty($path)) {
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		} 
		return $path;
	}
	 
	 
	 /**
	  * Retourne une chaine de caractere
	  *
	  * @static
	  * @access  public
	  * @param   array   $parts Array containing the parts to be joined
	  * @param   string  $separator The directory seperator
	  */
	 function buildPath($parts, $separator = DIRECTORY_SEPARATOR)
	 {
	 	$qs = '/^'. preg_quote($separator, '/') .'+$/';
	 	for ($i = 0, $c = count($parts); $i < $c; $i++) {
	 		if (!strlen($parts[$i]) || preg_match($qs, $parts[$i])) {
	 			unset($parts[$i]);
	 		} elseif (0 == $i) {
	 			$parts[$i] = rtrim($parts[$i], $separator);
	 		} elseif ($c - 1 == $i) {
	 			$parts[$i] = ltrim($parts[$i], $separator);
	 		} else {
	 			$parts[$i] = trim($parts[$i], $separator);
	 		}
	 	}
	 	return implode($separator, $parts);
	 }
	 
	 /**
	  * Permet de verifier si un chemin est absolu ou pas
	  *
	  * @static
	  * @param   string   $file          Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isAbsolutePath($file)
	 {
	 	if (strspn($file, '/\\', 0, 1)
	 			|| (strlen($file) > 3 && ctype_alpha($file[0])
	 					&& substr($file, 1, 1) === ':'
	 					&& (strspn($file, '/\\', 2, 1))
	 			)
	 			|| null !== parse_url($file, PHP_URL_SCHEME)
	 	) {
	 		return true;
	 	}
	 	 
	 	return false;
	 }
	 
	 /**
	  * Permet de verifier que le fichier indique est un dossier
	  *
	  * @static
	  * @param   string   $filename          Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isDir($filename)
	 {
	 	clearstatcache();
	 	$filetype   = @filetype($filename);
	 	return ($filetype=="dir");
	 }
	 
	 
	 /**
	  * Permet de verifier que le chemin indique est celui d'un fichier
	  *
	  * @static
	  * @param   string   $filename      Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isFile($filename)
	 {
	 	clearstatcache();
	 	$filetype   = @filetype($filename);
	 	return ($filetype=="file");
	 }
	 
	 /**
	  * Permet de verifier que le chemin indique est celui d'un fichier
	  *
	  * @static
	  * @param   string   $filename     Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isLink($filename)
	 {
	 	clearstatcache();
	 	$filetype   = @filetype($filename);
	 	return ($filetype=="link");
	 }
	 
	 
	 /**
	  * Permet de verifier que le fichier/dossier est accessible en lecture
	  *
	  * @static
	  * @param   string   $filename  Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isReadable($filename)
	 {
	 	clearstatcache();
	 	if(!self::exists($filename)){
	 		return false;
	 	}
	 	return is_readable($filename);
	 }
	 
	 
	 /**
	  * Permet de verifier que le fichier/dossier est accessible en lecture
	  *
	  * @static
	  * @param   string   $filename  Le chemin du fichier
	  *
	  * @return  bool true or false .
	  *
	  * @since
	  */
	 public static function isWritable($filename)
	 {
	 	clearstatcache();
	 	if(!self::exists($filename)){
	 		return false;
	 	}
	 	return is_writable($filename);
	 }

	 
	 /**
	  * Permet de rendre iteratif une variable contenant les informations d'u ou des fichiers
	  * 
	  * C'est une copie de la m�thode toIterator de Symphony
	  * 
	  * @static
	  * 
	  * @param mixed $files
	  *
	  * @return Traversable 
	  */
	 public static function toIterator($files)
	 {
	 	if (!$files instanceof Traversable) {
	 		 $files = new ArrayObject(is_array($files) ? $files : array($files));
	 	}	 
	 	return $files;
	 }


  }
