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



defined('OS_WINDOWS')
|| define('OS_WINDOWS' , !strncasecmp(PHP_OS , "win",3));

/**
 * La taille par defaut des donnees à lire
 */
if (!defined('DEFAULT_READSIZE')) {
	define('DEFAULT_READSIZE', 1024, true);
}

/**
 * La taille maximale des donnees à lire pour les lignes
 */
if (!defined('MAX_LINE_SIZE')) {
	define('MAX_LINE_SIZE', 4096, true);
}


/**
 * Verifie si le verrou des fichiers doit etre bloque ou pas en
 *  cas de verrou incompatible incompatible
 */
if (!defined('VERROU_BLOCK')) {
	 define('VERROU_BLOCK', true, true);
}

/**
 * Verrouillage partage.
 * Plusieurs processus peuvent disposer d’un   verrouillage
 * partage  simultanement  sur  un  même fichier
 */
define('VERROU_PARTAGE', LOCK_SH | (VERROU_BLOCK ? 0 : LOCK_NB), true);

/**
 * Verrouillage exclusif au cas ou c'est necessaire
 *  Un  seul  processus  dispose  d’un
 verrouillage exclusif sur un fichier, à un moment donne.
 */
define('VERROU_EXCLUSIVE', LOCK_EX | (VERROU_BLOCK ? 0 : LOCK_NB), true);


/**
 * L'option d'ouverture du fichier en mode lecture uniquement
 */
define('O_READ_ONLY', 'rb', true);

/**
 *  L'option d'ouverture du fichier en mode ecriture uniquement
 */
define('O_WRITE_ONLY', 'wb', true);

/**
 *  L'option d'ouverture du fichier en mode lecture et ecriture
 */
define('O_READ_WRITE', 'rb+', true);



/**
 * Une classe abstraite constituant l'adaptateur de base
 *
 * des gestionnaires de fichiers de la librairie
 *
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

abstract class Sirah_Filesystem_Adapter_Abstract implements Sirah_Filesystem_Adapter_Interface
{
	
	/**
	 * @var string le nom du chemin d'acces au fichier
	 */
	protected $_pathname  = null;
	
	/**
	 * @var string le nom de base du fichier sans son extension
	 */
	protected $_name      = null;
	
	/**
	 * @var string l'extension du fichier
	 */
	protected $_extension = null;
	
	/**
	 * @var bool l'etat de verouillage du fichier
	 */
	protected $_locked    = false;
	
	
	/**
	 * @var string le mode d'ouverture du fichier
	 */
	protected $_opened_mode= "rb";
	
	
	/**
	 * @var resource le pointeur du fichier
	 */
	protected $_handle    = null;	
	
	/**
	 * @var la position du curseur dans le fichier
	 */
	protected $_cursorpos = null;
	
	/**
	 * @var string le contenu du fichier
	 */
	protected $_content      = null;
	
		 
	/**
	 * Permet de creer l'objet
	 *
	 * @public
	 * @param   string   $filename     Le nom du fichier
	 * @param   string   $mode         Le mode d'acces au fichier.
	 * @param   mixed    $lock         Le mode de verrouillage du fichier
	 * @param   array    $params       Des parametres optionnels
	 *
	 * @since
	 */
	public function __construct( $filename , $options=array() , $mode = O_READ_ONLY , $lock = false)
	{
		//On initialise les varibales utilisées par le constructeur
		$filename           = $filename;
		$fileExists         = Sirah_Filesystem::exists($filename);
		$isDir              = is_dir( $filename );
		$isReadable         = is_readable( $filename );
		$isWritable         = is_writable( $filename );
		
		//On initialise les attributs de la classe
		$this->_pathname    = $filename;
		$this->_name        = Sirah_Filesystem::getName($filename);
		$this->_extension   = Sirah_Filesystem::getFilextension($filename);
		$this->_locked      = $lock;
		$this->_cursorpos   = $cursorpos  = isset($params["cursorpos"]) ? $params["cursorpos"] : 0;
		$this->_opened_mode = $mode;
		
		if(isset($options["filename"])){
			unset($options["filename"]);
		}
		if(isset($options["opened_mode"])){
			unset($options["opened_mode"]);
		}
		if(isset($options["locked"])){
			unset($options["locked"]);
		}
		if($isDir){
			throw new Sirah_Filesystem_Exception("Le fichier $filename utilise un mauvais adaptateur");
		}
		$this->setOptions($options);
							
	  if( !is_resource( $this->_handle ) ) {
	    switch($mode){
			case O_READ_ONLY:
			case "r":
				if( !$isReadable || !$fileExists ){
					throw new Sirah_Filesystem_Exception("L'acces en lecture au fichier $filename n'est pas possible");
				}
				break;
			case O_WRITE_ONLY:
			case "w":
				if( !$fileExists || !$isWritable){
					throw new Sirah_Filesystem_Exception("L'accès en ecriture au fichier $filename n'est pas possible");
				}
				break;
			case O_READ_WRITE:
			case "r+b":
				if( !$isWritable || !$isReadable ){
					throw new Sirah_Filesystem_Exception("L'accès en lecture/ecriture au fichier $filename n'est pas possible");
				}
				break;
			case "w+b" :
			case "wb+" :
			case "ab"  :
			case "a+b" :
			case "ab+" :
				$dirname    = dirname( $filename );
				if( !Sirah_Filesystem::isDir( $dirname ) || !Sirah_Filesystem::isWritable( $dirname ) ) {
					throw new Sirah_Filesystem_Exception("L'accès en lecture/ecriture au dossier $dirname n'est pas possible");
				}
				break;
		}
		$this->_handle  =  @fopen( $filename , $mode);
		if(!is_resource( $this->_handle )){
			$error   = error_get_last();
			$message = ( isset( $error["message"] ) ) ? $error["message"] : " ";
			throw new Sirah_Filesystem_Exception("Une erreur s'est produite dans l'ouverture du fichier $filename " . $message );
		}
		if($lock){
		   $this->lock();
		}	
	  }		
	}
	
	
	/**
	 * Permet de mettre à jour les paramètres de la classe
	 *
	 * @param   string   $options  les options 
	 *
	 * @return  Sirah_Filesystem_Adapter_Abstract instance
	 *
	 * @since
	 */
	public function setOptions($options)
	{
		if(isset($options["locked"])){
			$this->_locked = intval($options["locked"]);
		}
		if(isset($options["filename"])){
			$this->_pathname = Sirah_Filesystem::safeFilename($options["filename"]);
			$this->reset();
		}
		if(isset($options["opened_mode"])){
			if(!in_array( $mode , Sirah_Filesystem_File::$open_modes )){
				$filename = $this->_pathname;
				throw new Sirah_Filesystem_Exception(sprintf("L'option du mode d'ouverture du fichier %s est invalide" , $filename));
			}
			$this->_opened_mode  = $options["opened_mode"];
			$this->reset();
		}		
	}
		 
	/**
	 * Permet d'ecrire dans le fichier
	 *
	 * @param   string   $data         Les donnees a ecrire dans le fichier
	 * @param   integer  $offset       La position à partir de laquelle il faut commencer
	 *
	 * @return int    le nombre de bytes ecrits dans le fichier .
	 *
	 * @since
	 */
	public function write( $data , $offset = 0 )
	{
		$bytes        = 0;	
		if( $offset ) {
			$this->seek( $offset );
		}
		$fileHandle   = $this->getHandle();
		$filename     = $this->getPathname();
		if( false === ( $bytes = @fwrite( $fileHandle , $data , strlen( $data ) ) ) ){
			throw new Sirah_Filesystem_Exception("L'ecriture des donnees dans le fichier $filename a echoue");
		}
		$this->_cursorpos  = @ftell($fileHandle);
		return $bytes;
	}	
	
	/**
	 * Permet d'ajouter une ligne de caractère dans le fichier
	 * 
	 * @param  string la chaine de carctères à écrire dans le fichier
	 * @param  string le séparateur de lignes CRLF du système (UNIX = \n Windows = \r\n Mac = \r)
	 * @param  int    la position à partir de laquelle l'écriture doit commencer
	 *
	 * @return int    le nombre de bytes ecrits dans le fichier .
	 *
	 * @since
	 */
	public function writeLine( $linecontent , $crlf = "\n" , $offset = 0 )
	{
		if( $offset ) {
			$this->seek( $offset );
		}
		$bytes        = 0;
		$fileHandle   = $this->getHandle();
		$filename     = $this->getPathname();
		if( false === ( $bytes = @fwrite( $fileHandle , $linecontent . $crlf ) ) ) {
			throw new Sirah_Filesystem_Exception( " L'écriture de la ligne dans le fichier $filename a echoué " );
		}
		return $bytes;
	}
	
	/**
	 * Permet d'ajouter un caractère dans le fichier
	 *
	 * @param  int    la position de la ligne
	 * @param  string le séparateur de lignes CRLF du système (UNIX = \n Windows = \r\n Mac = \r)
	 * @param  int    la position à partir de laquelle l'écriture doit commencer
	 *
	 * @return int    le nombre de bytes ecrits dans le fichier .
	 *
	 * @since
	 */
	public function writeChar( $char , $crlf = "\n" , $offset = 0 )
	{
		if( $offset ) {
			$this->seek( $offset );
		}
		$bytes        = 0;
		$fileHandle   = $this->getHandle();
		$filename     = $this->getPathname();
		if( false === ( $bytes = @fwrite( $fileHandle , $char , 1 ) ) ) {
			throw new Sirah_Filesystem_Exception( " L'écriture du caractère $char dans le fichier $filename a echoué " );
			return false;
		}
		return $bytes;
	}
	 
	/**
	 * Permet de lire le contenu entier du fichier
	 *
	 * @access  public
	 * @return  mixed   Exception au cas ou une erreur se produit ou string si le contenu est correctement recupéré
	 */
	function readAll( )
	{
		$filename    = $this->getPathname();
		$filecontent = "";
		if (false === ( $filecontent = @file_get_contents( $filename ) ) ) {
			throw new Sirah_Filesystem_Exception(" Impossible de lire le contenu du fichier $filename ");
		}
		return $filecontent;
	}
	
	/**
	 * Permet de lire les donnees contenues dans un fichier
	 * 
	 * @param   int $size   la taille des données à lire
	 * @param   int $offset la position à partir de laquelle il faut commencer la lecture
	 *
	 * @return  mixed soit le contenu du fichier soit un booleen false.
	 *
	 * @since
	 */
	public function read( $size = DEFAULT_READSIZE , $offset = 0 )
	{
		$fileHandle  = $this->getHandle();		
		if($offset){
			$this->seek( $offset );
		}
		return !feof($fileHandle) ? fread( $fileHandle , $size ) : false;
	}
		
	/**
	 * Permet de modifier la position du curseur dans le fichier
	 *
	 * @param   integer  $offset.
	 *
	 * @return bool true ou false en fonction de la réussite
	 *
	 * @since
	 */
	public function seek($offset)
	{
		clearstatcache();
		$fileHandle        = $this->getHandle();
		$this->_cursorpos  = $offset;
		return @fseek( $fileHandle , $offset );
	}
		
	/**
	 * Permet de réinitialiser la position du curseur
	 *
	 *
	 *  @return bool true ou false en fonction de la r�ussite
	 *
	 * @since
	 */
	public function rewind()
	{
		$fileHandle        = $this->getHandle();
		$this->_cursorpos  = 0;
		return @rewind( $fileHandle );
	}	
	
	/**
	 * Permet de vérifier si le fichier est verouill� ou pas
	 *
	 * @return  bool true ou false
	 *
	 * @since
	 */
	public function isLocked()
	{
      return $this->_locked;
	}
		
	/**
	 * Permet de verouiller le fichier
	 *
	 *
	 * @return  bool vrai ou faux
	 *
	 * @since
	 */
	public function lock()
	{ 
		$lockType    = ( $this->_opened_mode == O_READ_ONLY ) ? VERROU_PARTAGE : VERROU_EXCLUSIVE;
		$handle      = $this->getHandle();
		$filename    = $this->getPathname();		
		if( !@flock( $handle , $lockType ) && VERROU_BLOCK ) {
			throw new Sirah_Filesystem_Exception(" Le fichier $filename est déjà verouillé ");
			return false;
		} 
		return true;
	}	
	
	/**
	 * Permet de deverouiller le fichier
	 *
	 *
	 * @return  bool vrai ou faux
	 *
	 * @since
	 */
	public function unlock()
	{
		$handle        = $this->getHandle();
		if(!@flock( $handle , LOCK_UN )) {
			throw new Sirah_Filesystem_Exception(" Le deverouillage du fichier $filename a echoue ");
			return false;
		}
		return true;
	}	
	 
	/**
	 * Permet de recuperer le nom du fichier sans son extension
	 *
	 * @return
	 *
	 * @since
	 */
	public function getName()
	{
		return $this->_name;
	}
	 
	/**
	 * Permet de recuperer le pointeur du fichier
	 *
	 * @return
	 *
	 * @since
	 */
	public function getHandle()
	{
		return $this->_handle;
	}
	 
	/**
	 * Permet de recuperer le nom du fichier sans son extension
	 *
	 * @return
	 *
	 * @since
	 */
	public function getPathname()
	{
		return $this->_pathname;
	}
	 	 
	/**
	 * Permet de recuperer l'extension du fichier
	 *
	 * @return
	 *
	 * @since
	 */
	public function getFilextension()
	{
		return $this->_extension;
	}
		
	/**
	 * Permet de récupérer le contenu
	 *
	 * @return
	 *
	 * @since
	 */
	public function getContent()
	{
	   return $this->readAll();
	}
	
	/**
	 * Permet de récupérer une ligne du contenu
	 *
	 * @param   $size    la taille correspondant à une ligne
	 * @param   $offset  la position à partir de laquelle il faut commencer
	 * 
	 * @return  string   le contenu de la ligne
	 *
	 * @since
	 */
	public function getLine( $size = MAX_LINE_SIZE , $offset = 0 )
	{
		if( $offset ) {
			$this->seek( $offset );
		}
		$filename   = $this->getPathname();
		$filehandle = $this->getHandle();		
		if( feof( $filehandle ) ) {
			return false;
		}
		return rtrim(fgets( $filehandle , $size ) , "\r\n");
	}
	
	/**
	 * Permet de récupérer un caractère
	 *
	 * @return
	 *
	 * @since
	 */
	public function getChar( $offset = 0 )
	{
		if( $offset ) {
			$this->seek( $offset );
		}
		$filename   = $this->getPathname();
		$filehandle = $this->getHandle();
		return !feof( $filehandle ) ? fread( $filehandle , 1 ) : false;
	}
	
	/**
	 * Permet de rechercher une chaine dans le contenu du fichier
	 *
	 * @return
	 *
	 * @since
	 */
	public function find( $needle , $start , $length  )
	{
	
	}
	
	/**
	 * Permet d'afficher le contenu du fichier
	 *
	 * @return
	 *
	 * @since
	 */
	public function toString()
	{
		return $this->readAll();	
	}
	
	
	/**
	 * Permet d'envoyer le fichier au navigateur
	 *
	 * @param string  $name le nom du fichier
	 *
	 * @return
	 */
	public function Output( $name = null )
	{
		$filename  = $this->getPathname();
		$name      = ( null === $name ) ? basename($filename) : $name ;
		if ( $data = ob_get_contents()) {
			 throw new Sirah_Filesystem_Exception(" Impossible d'envoyer ce fichier au navigateur , des données ont déjà été envoyées : " . $data );
		}
		header('Content-Description: File Transfer');
		if (headers_sent()) {
			throw new Sirah_Filesystem_Exception(" Des données ont été déjà envoyées au navigateur , impossible d'envoyer le contenu du fichier");
		}
		header('Cache-Control: public, must-revalidate, max-age=0');
		header('Pragma: public');
		header('Expires: 0');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream', false);
		header('Content-Type: application/download', false);
		header('Content-Type: application/excel', false);
		header('Content-Type: application/csv', false);
		header('Content-Disposition: attachment; filename="'.basename($name).'";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($filename));
		echo $this->readAll();		
	}
		
	/**
	 * Permet de recuperer les informations descriptives du fichier
	 *
	 * @return
	 *
	 * @since
	 */
	public function infos()
	{		
		$infos = array( "basename"  => $this->getName(),
				        "pathname"  => $this->getPathname(),
				        "extension" => $this->getFilextension() );
		
		return $infos;	
	}
	
	
	/**
	 * Permet de faire une copie du fichier dans un autre dossier
	 * 
	 * @param string $destination  le nouveau dossier destination du fichier
	 * @param bool   $overwrite    indique si un fichier existant dans le dossier de destination doit etre �cras�
	 * @param string $newname      le nouveau nom du fichier � sa destination
	 *
	 * @return bool true ou false en fonction du r�sultat de la copie
	 *
	 * @since
	 */
	public function copy( $destination , $overwrite = false , $newname = null)
	{
		$filesource      = $this->getPathname();
		$defaultFilename = $this->getName() . "." . $this->getFilextension();
		$filename        = (null!==$newname && !empty($newname)) ? $newname : $defaultFilename;
		$filedestination = $destination . DS . $filename;
				
		if(!Sirah_Filesystem::isDir($destination) || !Sirah_Filesystem::isWritable($destination)){
			throw new Sirah_Filesystem_Exception( sprintf("La copie du fichier %s ne peut etre effectu�e car la destination fournie est inaccessible",$filesource));
			return false;
		}		
		if(Sirah_Filesystem::exists($filedestination) && !$overwrite){
			throw new Sirah_Filesystem_Exception("Impossible d'effectuer la copie du fichier $filedestination, car la destination contient déjà un fichier du meme nom");
			return false;
		}
		if(!copy( $filesource , $filedestination ) ){
			throw new Sirah_Filesystem_Exception( sprintf("La copie du fichier %s a echoué" , $filesource));
			return false;
		}
		return $filedestination;	
	}	
	
	/**
	 * Permet de déplacer le fichier dans un autre dossier
	 *
	 * @param string $destination  le nouveau dossier destination du fichier
	 * @param bool   $overwrite    indique si un fichier existant dans le dossier de destination doit etre �cras�
	 * @param string $newname      le nouveau nom du fichier � sa destination
	 *
	 * @return bool true ou false en fonction du r�sultat de la copie
	 *
	 * @since
	 */
	public function move( $destination , $overwrite = false , $newname = null)
	{
		$filesource      = $this->getPathname();
		$defaultFilename = $this->getName() . "." . $this->getFilextension();
		$filename        = (null!==$newname && !empty($newname)) ? $newname : $defaultFilename;
		$filedestination = $destination . DS . $filename;
		
		if($this->isLocked()){
			throw new Sirah_Filesystem_Exception( sprintf("Le deplacement du fichier %s ne peut etre effectué car le fichier est verouillé" , $filesource));
			return false;
		}	
		if(!Sirah_Filesystem::isDir($destination) || !Sirah_Filesystem::isWritable($destination)){
			throw new Sirah_Filesystem_Exception(sprintf("La copie du fichier %s ne peut etre effectu�e car la destination fournie est inaccessible" , $filesource));
			return false;
		}
		if(Sirah_Filesystem::exists( $filedestination ) && !$overwrite){
			throw new Sirah_Filesystem_Exception("Impossible d'effectuer la copie du fichier $filedestination , car la destination contien");
			return false;
		}
		if(!rename( $filesource , $filedestination )){
			throw new Sirah_Filesystem_Exception(sprintf("La copie du fichier %s a echoué" , $filesource));
			return false;
		}
		$this->_pathname   = $filedestination;
		$this->reset();
		return $this;
	}
	
	
	/**
	 * Permet de supprimer le fichier
	 *
	 * @param  bool     $forced indique si la suppression du fichier doit etre forc�e ou pas
	 * @return boolean true
	 *
	 * @since
	 */
	public function remove( $force = false )
	{
		$filename  = $this->getPathname();
		if($this->isLocked() && !$force ){
			Sirah_Error::raiseWarning( 0 ,sprintf("Impossible d'effacer le fichier %s car celui ci est verouille" , $filename ));
			return false;
		} elseif ($this->isLocked() && $force){
			$this->unlock();
		}
		if(!Sirah_Filesystem::isWritable( $filename )){
			Sirah_Error::raiseWarning( 0 , sprintf("Impossible d'effacer le fichier %s car celui-ci est inaccessible en écriture") , $filename );
			return false;
		}	
		if(!@unlink( $filename )){
			Sirah_Error::raiseWarning( 0 , sprintf("La suppression du fichier %s a echoue" , $filename));
			return false;
		}
		$this->close();
		$this->_handle  = null;		
		return true;
	}
	
	/**
	 * Permet d'afficher le contenu du fichier
	 *
	 *
	 * @since
	 */
	public function reset()
	{
		$filename           = $this->getPathname();		
		if(null == $filename || empty( $filename ) || !Sirah_Filesystem::exists($filename)){
			throw new Sirah_Filesystem_Exception("Impossible d'initialiser ce gestionnaire de fichier car certains param�tres sont invalides");
			return false;
		}		
		//On initialise les attributs de la classe
		$this->_name        = Sirah_Filesystem::stripExtension( $filename );
		$this->_extension   = Sirah_Filesystem::getFilextension( $filename );		
		//On recrée le pointeur du fichier
		$this->_handle      =  @fopen( $filename , $this->_opened_mode);		
		if(!is_resource($this->_handle)){
			throw new Sirah_Filesystem_Exception("Une erreur s'est produite dans l'ouverture du fichier $filename");
		}
		$this->rewind();
		if($this->_locked){
			$this->lock();
		}
		return $this->_handle;	
	}
	
	/**
	 * Permet de sauvegarder le fichier avec les données mises à jour
	 *
	 *
	 * @since
	 */
	public function save( $data = null , $filename = null , $copy = true )
	{
		if( $this->isLocked() ) {
			throw new Sirah_Filesystem_Exception( "Impossible d'enregistrer le fichier ".$this->getPathname().", car celui-ci est verrouillé ");
		}
		$savedFile  = $this;
		if( null!== $filename && ( $filename != $this->getPathname() )) {
			$filename_destination = dirname( $filename );
			$copyNewname          = $this->getName() . "." . $this->getFilextension();
			if( $copy ) {
				$this->copy( $filename_destination , true , $copyNewname );
			} else {
				$this->move( $filename_destination , true , $copyNewname );
			}
		}
		if( null != $data && !empty( $data ) ) {
			$savedFile->write( $data , 0 );
			return true;
		}
		return false;
	}
	
	
	/**
	 * Permet d'afficher le contenu du fichier
	 *
	 * @static
	 * @return boolean true
	 *
	 * @since
	 */
	public function close()
	{	
		if( $this->isLocked() ) {
			$this->unlock();
		}
		$handle  = $this->getHandle();	
		return ( @fclose( $handle ) );	
	}
	
	
	/**
	 * Le destructeur du fichier
	 *
	 * @static
	 * @return boolean true
	 *
	 * @since
	 */
	public function __destruct()
	{
	  return $this->close();
	}
	
	
	
	
	
}
