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
 * Cette classe correspond à l'interface utilisée par les classes utilisées pour la copie des fichiers
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Filesystem_Adapter_Image extends Sirah_Filesystem_Adapter_Abstract
{

	/**
	 *  L'identifiant de la ressource image
	 *  @var resource
	 *
	 */
	protected $_imgHandle            = null;
	
	/**
	 *  Le type de l'image
	 *  @var string
	 *
	 */
	protected $_imgType               = null;
	
	
	/**
	 *  Les types d'images support�s
	 *  @var array
	 *
	 */
	protected static $_supportedTypes = array();
	
	
	
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
	public function __construct($filename , $options=array() , $mode=O_READ_DONLY , $lock=false)
	{
		// Nous v�rifions que l'extension GD de PHP est disponible
		if(!extension_loaded('gd')){
			throw new Sirah_Filesystem_Exception("L'extension GD de PHP n'est pas disponible dans votre syst�me");
		}		
		//Nous d�terminons les types d'images support�s par notre GD
		if (!isset(self::$_supportedTypes[IMAGETYPE_JPEG])){
			$info = gd_info();
			self::$_supportedTypes[IMAGETYPE_JPEG] = (isset($info['JPEG Support']) && $info['JPEG Support'] || isset($info['JPG Support']) && $info['JPG Support']) ? true : false;
			self::$_supportedTypes[IMAGETYPE_PNG]  = ($info['PNG Support']) ? true : false;
			self::$_supportedTypes[IMAGETYPE_GIF]  = ($info['GIF Read Support']) ? true : false;
		}
		parent::__construct($filename , $options , $mode , $lock);		
		if(isset($options["handle"]) && is_resource($options["handle"]) && (get_resource_type($options["handle"]) == 'gd')){
			$this->_imgHandle  = $options["handle"];
		} else {
			$this->create();
		}
	}
	
	/**
	 * Permet de creer l'image
	 *
	 * @public
	 *
	 *
	 * @since
	 */
	public function create( $pathname = null)
	{
		$filepathname    = ( (null!= $pathname) && Sirah_Filesystem::exists($pathname)) ? $pathname : $this->getPathname();
		$this->_pathname = $filepathname ;
		$extension       = strtolower($this->getFilextension());
		
		if(!in_array($extension , array("gif","jpeg","jpg","png"))){
			throw new Sirah_Filesystem_Exception("L'image que vous avez indiquée n'est pas prise en compte par votre système");
		}
		$handle  = null;		
		switch($extension){
			case 'gif':
				// On s'assure que le type d'image GIF est support�
				if (false==self::$_supportedTypes[IMAGETYPE_GIF]){
					throw new Sirah_Filesystem_Exception("Les images GIF ne sont pas supportées par votre système");
				}
				// On tente de créer la ressource de l'image
				$handle = imagecreatefromgif($filepathname);
				if (!is_resource($handle)){
					throw new Sirah_Filesystem_Exception("La création de la ressource de l'image GIF a echoué") ;
				}
				break;
			case 'jpeg':
			case 'jpg':
				// On s'assure que le type d'image JPEG est supporté
				 if(false==self::$_supportedTypes[IMAGETYPE_JPEG]){
                     throw new Sirah_Filesystem_Exception("Les images JPEG ne sont pas supportées par votre système");
				 }				
				// On tente de cr�er la ressource de l'image JPEG
				$handle = imagecreatefromjpeg($filepathname);
				if(!is_resource($handle)){
					throw new Sirah_Filesystem_Exception("La création de la ressource de l'image JPEG a echoué") ;
				}
				break;				
			case 'png':
				// Make sure the image type is supported.
				if(false==self::$_supportedTypes[IMAGETYPE_PNG]){
				   throw new Sirah_Filesystem_Exception("Les images PNG ne sont pas supportées par votre système");
				}				
				// On tente de créer la ressource de l'image PNG
				$handle = imagecreatefrompng($filepathname);
				if(!is_resource($handle)){
				    throw new Sirah_Filesystem_Exception("La création de la ressource de l'image JPEG a echoué") ;
				}
				break;				
		}		
		if(null===$handle || !is_resource($handle)){
			throw new Sirah_Filesystem_Exception(sprintf("La création du flux d'image %s a echoué",$filepathname));
		}
		$this->_imgHandle = $handle;	
	}
		
	/**
	 * Permet de redimensionner l'image
	 * 
	 * @param string $width la nouvelle largeur de l'image
	 * @param string $height la nouvelle longueur de l'image
	 * @param bool   $proportionel indique si le rédimensionnement doit etre proportionnel ou pas
	 *
	 * @return Sirah_Filesystem_Adapter_Abstract instance
	 *
	 * @since
	 */
	public function resize($width = null , $height = null , $proportionel = true , $destination = null)
	{	
		if( $this->lock()){
			$this->unlock();
		}			
		if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas chargée");
		}
		//Si la largeur est nulle et que la longueur non nulle, on suppose que c'est une image carrée
		if( null==$width && $height!=null && !empty($height)){
			$width  = $height;
		}
		//Si la longueur est nulle et que la largeur non nulle, on suppose que c'est une image carrée
		if(null == $height && $width!=null && !empty($width)){
			$height  = $width;
		}
		// On récupère la nouvelle largeur formatée
		$width = $this->getWidthFromString($width);		
		// On récupère la nouvelle hauteur formatée
		$height = $this->getHeightFromString($height);
			
		if( ( $width == $this->width() ) && ( $height==$this->height() ) ){
			return $this;
		}		
		//On récupère la nouvelle taille
		$scaledDimensions  = $this->scale( $width , $height , $proportionel );		
		// On crée une nouvelle image
		$newHandle         = imagecreatetruecolor( $scaledDimensions["width"] , $scaledDimensions["height"] );		
		imagealphablending( $newHandle , false );
		imagesavealpha($newHandle , true);		
		if ($this->isTransparent()){			
			$rgba  = imageColorsForIndex( $this->_imgHandle , imagecolortransparent($this->_imgHandle));
			$color = imageColorAllocate(  $this->_imgHandle , $rgba['red'] , $rgba['green'] , $rgba['blue']);
			imagecolortransparent($handle, $color);
			imagefill($handle, 0, 0, $color);		
			imagecopyresized( $newHandle , $this->_imgHandle, 0, 0, 0, 0 , $scaledDimensions["width"],$scaledDimensions["height"], $this->width(), $this->height());
		} else {
			imagecopyresampled( $newHandle , $this->_imgHandle, 0, 0, 0, 0,$scaledDimensions["width"],$scaledDimensions["height"], $this->width(), $this->height());
		}
		if( null !== $destination && Sirah_Filesystem::isDir($destination)) {
			$imageInfos     = $this->infos();
			$newDestination = realpath($destination) . DS . $imageInfos["basename"];			
			$imgType        = strtolower($this->getFilextension());			
			$this->write( $newDestination , $newHandle , $imgType );
			$resizedImage   = Sirah_Filesystem_File::fabric("Image" , $newDestination , "rb+");	
		} else {
			$this->_imgHandle = $newHandle;
			return $this;
		}	 				
		return $resizedImage;		
	}
	
	
	/**
	 * Permet de rogner et redimensionner l'image
	 * 
	 * @param string $width la nouvelle largeur de l'image
	 * @param string $height la nouvelle longueur de l'image
	 * @param array  $cropPosition la position du rognage
	 *
	 * @return Sirah_Filesystem_Adapter_Abstract instance
	 *
	 * @since
	 */
	public function resizecrop($width = null , $height = null , $cropPosition="auto", $destination = null)
	{
		require_once("Imagemagician/php_image_magician.php");
		if( $this->lock()){
			$this->unlock();
		}			
		if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas chargée");
		}
		//Si la largeur est nulle et que la longueur non nulle, on suppose que c'est une image carrée
		if( null==$width && $height!=null && !empty($height)){
			$width  = $height;
		}
		//Si la longueur est nulle et que la largeur non nulle, on suppose que c'est une image carrée
		if(null == $height && $width!=null && !empty($width)){
			$height  = $width;
		}
		// On récupère la nouvelle largeur formatée
		$width  = $this->getWidthFromString($width);		
		// On récupère la nouvelle hauteur formatée
		$height = $this->getHeightFromString($height);
			
		if(($width == $this->width()) && ( $height==$this->height() ) ){
			return $this;
		}		
		//On récupère la nouvelle taille
		$scaledDimensions = $this->scale( $width , $height , true);       
		$imageInfos       = $this->infos();
		$filePath         =	(isset($imageInfos["pathname"]))? $imageInfos["pathname"] : $this->getPathname();
		$fileName         = (isset($imageInfos["basename"]))? $imageInfos["basename"] : $this->getName();
        
		$magicianObj      = new imageLib($filePath);	
       	$magicianObj->resizeImage($width,$height,array("crop", $cropPosition));
		$newDestination   = realpath($destination) . DS . $fileName;
        /*if( file_exists($newDestination)) {
			@unlink($newDestination);
		}	*/
		$magicianObj->saveImage($newDestination);   
		$errorMessages    = $magicianObj->getErrors();
		if(!count($errorMessages) ) {
			return Sirah_Filesystem_File::fabric("Image" ,$newDestination, "rb+");	
		} else {
			$errorMessage  = implode(",", $errorMessages);
			throw new Sirah_Filesystem_Exception(sprintf("Le rognage de la photo %s a echoué : %s" , $fileName, $errorMessage));
		}
        return false; 		
	}
	
	
	/**
	 * Méthode permettant de faire une rotation de l'image
	 *
	 * @param   mixed    $angle       L'angle de rotation de l'image
	 * @param   integer  $background  La couleur d'arrière plan à utiliser si de nouvelles sufaces ont été crées
	 *
	 * @return  Sirah_Filesystem_Adapter_Abstract instance
	 *
	 */
	public function rotate($angle, $background = -1)
	{
		if($this->isLocked()){
			$this->unlock();
		}
	    if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas chargée");
		}	
		$angle  = floatval($angle);	
		$handle = imagecreatetruecolor($this->width(),$this->height());	
		imagealphablending($handle, false);
		imagesavealpha($handle, true);	
		imagecopy($handle, $this->_imgHandle, 0, 0, 0, 0, $this->width(), $this->height());	
		// On réalise la rotation de l'image
		$handle = imagerotate($handle, $angle, $background);	
	    $this->_imgHandle  = $handle;	   
	    return $this;
	}
	
	
	/**
	 * Permet de vérifier la transparence de l'image.
	 *
	 * @return  bool
	 *
	 */
	public function isTransparent()
	{
	    if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas chargée");
		}	
		return (imagecolortransparent($this->_imgHandle) >= 0);
	}	
	
	/**
	 * Permet de recuperer la hauteur de l'image.
	 *
	 * @return
	 *
	 * @since
	 */
	public function height()
	{
		if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas chargée");
		}
		return imagesy($this->_imgHandle);	
	}
	
	
	/**
	 * Permet de recuperer la largeur de l'image.
	 *
	 * @return
	 *
	 * @since
	 */
	public function width()
	{
		if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas charg�e");
		}
		return imagesx($this->_imgHandle);	
	}
	
	
	/**
	 * Permet de verifier si l'image est cree
	 *
	 * @public
	 *
	 *
	 * @since
	 */
	public function isCreated()
	{
		// On vérifie que l'identifiant de la ressource image est valide
		if (!is_resource($this->_imgHandle) || (get_resource_type($this->_imgHandle) != 'gd')){
			return false;
		}		
		return true;	
	}
	
	/**
	 * Permet de recuperer la hauteur de l'image 
	 * à partir d'une chaine de caractere formatee.
	 * 
	 * @param string $height  la hauteur formatée
	 *
	 * @return string la hauteur au format pixels
	 *
	 * @since
	 */
	public function getHeightFromString($height=null)
	{
		if(null == $height ){
			return;
		}	
		//Si la taille est indiquée en pourcentage
		if (preg_match('/^[0-9]+(\.[0-9]+)?\%$/', $height)){
			$height = intval(round($currentHeight * floatval(str_replace('%', '', $height)) / 100));
		} elseif (preg_match('/^[0-9]+(\.[0-9]+)?px$|^[0-9]+(\.[0-9]+)?pixels$/', $height)){			
			$height = intval(round(floatval(str_replace('px','', $height))));			
		} elseif (preg_match('/^[0-9]+(\.[0-9]+)?cm$|^[0-9]+(\.[0-9]+)?centimetres$/', $height)){
			$height  = intval(round( 72 / 2.54*floatval(str_replace('cm','', $height))));
		} else {
			$height = intval(round(floatval($height)));
		}		
		return $height;	
	}
	
	
	/**
	 * Permet de recuperer la largeur de l'image
	 * à partir d'une chaine de caractère formatée.
	 *
	 * @param  string $width  la largeur formatée
	 * @return string la largeur au format pixels
	 *
	 * @since
	 */
	public function getWidthFromString($width=null)
	{
		if(null == $width) {
			return;
		}
		//Si la taille est indiquée en pourcentage
		if (preg_match('/^[0-9]+(\.[0-9]+)?\%$/', $width)){
			$width = intval(round($currentWidth * floatval(str_replace('%', '', $width)) / 100));
		} elseif (preg_match('/^[0-9]+(\.[0-9]+)?px$|^[0-9]+(\.[0-9]+)?pixels$/', $width)){
			$width = intval(round(floatval(str_replace('px','', $width))));
		} elseif (preg_match('/^[0-9]+(\.[0-9]+)?cm$|^[0-9]+(\.[0-9]+)?centimetres$/', $width)){
			$width  = intval(round( 72 / 2.54*floatval(str_replace('cm','', $width))));
		} else {
			$width = intval(round(floatval($width)));
		}
		return $width;	
	}
	
	/**
	 * Permet de redimensionner la taille de l'image
	 *
	 * @param integer $scaledx        la nouvelle largeur de l'image
	 * @param integer $scaledy        la nouvelle longeur de l'image
	 * @param bool    $proportionnel  indique si le rédimensionnement doit etre proportionnel ou pas
	 * @param bool    $scale_inside   indique si le redimendionnement utilise le plus petit ou grand ratio
	 * 
	 * @return array les nouvelles dimensions
	 * 
	 */
	public function scale($scaledx , $scaledy , $proportionnel = true , $scale_inside = true) 
	{
		$cx         = $this->width();
		$cy         = $this->height();		
		$dimensions = array("width"  => intval(round($cx)),
				            "height" => intval(round($cy)) );
		//On calcule les ratios
		$xratio    = 1;
		$yratio    = 1;
		$ratio     = 1;					
		if($scaledx > 0){
			$xratio  = $cx / $scaledx ;
		}
		if($scaledy > 0){
			$yratio  = $cy / $scaledy;
		}			
		if($scale_inside){
		    $ratio   = ($xratio > $yratio) ? $xratio : $yratio;
		} else {
			$ratio   = ($xratio < $yratio) ? $xratio : $yratio;				
		}			
		if( $proportionnel){
			$dimensions["width"]  = intval(round($cx / $ratio));
			$dimensions["height"] = intval(round($cy / $ratio));
		}
		return $dimensions;
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
		$infos = array( "basename"  => $this->getName() . "." . $this->getFilextension(),
				        "pathname"  => $this->getPathname(),
				        "extension" => $this->getFilextension() );
		// On récupère les informations necessaires sur l'image
		$info = getimagesize($infos["pathname"]);
		if (!$info){
			throw new Sirah_Filesystem_Exception("Impossible de r�cup�re les informations de l'image");
		}	
		$infos["width"]       =  $info[0];
		$infos["height"]      =  $info[1];
		$infos["type"]        =  $info[2];
		$infos["attributes"]  =  $info[3];
		$infos["mime"]        =  $info['mime'];
		$infos["bits"]        =  isset($info['bits']) ? $info['bits'] : null;
		$infos["channel"]     =  isset($info['channels']) ? $info['channels'] : null;			
		return $infos;
	}
	
	
	/**
	 * Permet d'envoyer l'image au navigateur
	 *
	 */
	public function Output()
	{
		if($stringcontent=@ob_get_contents()) {
			throw new Sirah_Filesystem_Exception("Impossible d'afficher l'image sur le navigateur, car des donn�es ont d�j� �t� envoy�es");
		}
		if(!$this->isCreated()){
			throw new Sirah_Filesystem_Exception("L'image n'est pas charg�e");
		}
		
		$extension  = strtolower($this->getFilextension());	
		switch($extension){
			case 'gif':
				header("Content-Type:image/gif");
				imagegif($this->_imgHandle);
				break;
			case 'jpeg':
			case 'jpg':
				 header("Content-Type:image/jpeg");
				 imagejpeg($this->_imgHandle);
				break;
				
			case 'png':
				 header("Content-Type:image/png");
				 imagepng($this->_imgHandle);
				break;				
		}
	}
	
	
	/**
	 * Permet d'écrire l'image actuelle dans un fichier
	 *
	 */
	public function write( $filepath , $resourceHandle = null , $imgType = IMAGETYPE_JPEG  , $quality = null )
	{
		if( ( null == $resourceHandle ) || !is_resource( $resourceHandle ) ) {
			  $resourceHandle = $this->_imgHandle;
			  $imgType        = strtolower($this->getFilextension());
		}
		$result = false;
	    switch ( $imgType )
		{
			case IMAGETYPE_GIF:
			case "gif" :
				$result = imagegif( $resourceHandle , $filepath);
				break;

			case IMAGETYPE_PNG:
			case "png" :
				$quality = ( null !== $quality ) ? intval($quality) : 0 ;
				$result  = imagepng( $resourceHandle  , $filepath , $quality );
				break;
			case IMAGETYPE_JPEG:
			case "jpg" :
			case "jpeg" :
				$quality = ( null !== $quality ) ? intval($quality) : 100 ;
				$result  = imagejpeg( $resourceHandle , $filepath , $quality );
				break;
		}
		return $result;
	}
	
	
	public function read($size , $offset)
	{
		return;
	}

	/**
	 * Permet de convertir la taille de l'image
	 * en centimètres ou millimètres
	 *
	 * @return array un tableau des nouvelles tailles
	 *
	 * @since
	 */
	function convertsize($to="cm",$dpi=72) 
	{
		$imgHandle  = $this->_imgHandle;		
		$x          = $this->width();
		$y          = $this->height();
		
		$newsize    = array( "width"  => $x,
				             "height" => $y );		
		switch($to){
			case "cm" :
			case "centimetres":
				$w = $x * 2.54 / $dpi;
				$h = $y * 2.54 / $dpi;
				
				$newsize["width"]  = $w;
				$newsize["height"] = $h;			
				break;
			case "mm" :
			case "millimetres":
				 $w = $x * 2.54 / ($dpi*10);
				 $h = $y * 2.54 / ($dpi*10);
				
				 $newsize["width"]  = $w;
				 $newsize["height"] = $h;
				 break;
		}		
		return $newsize;
	}	
	

 }
