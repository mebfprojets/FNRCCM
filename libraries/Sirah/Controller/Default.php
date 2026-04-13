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
 * Cette classe représente le controlleur par défaut du Package de SIRAH
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */
 defined('APPLICATION_HOST_PORT')
    || define('APPLICATION_HOST_PORT', 80);

class Sirah_Controller_Default extends Zend_Controller_Action 
{
	
	/**
	 *
	 * @var array : un tableau des instances du modèle MVC correspondant au
	 *      controlleur
	 */
	protected $_models = array ();
	
	/**
	 *
	 * @var string : chaine de caractère indiquant le chemin du template
	 *      concerné par le controlleur
	 */
	protected $_layoutPath = "default";
	
	/**
	 *
	 * @var string : chaine de caractère spécifiant l'url de rédirection
	 */
	protected $_redirectUrl = "/";
	
	/**
	 *
	 * @var string : le message de redirection du controlleur
	 */
	public $_message     = null;
	
	/**
	 *
	 * @var string : le type de message de redirection du controlleur
	 */
	
	public $_messageType = null;
	
	/**
	 * Permet d'initialiser le controlleur
	 * 
	 * @access public
	 * @param
	 *       	 void
	 * @return void
	 */
	
	public function init() 
	{
		$this->view->isAjax = $this->_request->isXmlHttpRequest ();
		if (!Zend_Controller_Action_HelperBroker::hasHelper('Message' )) {
			 Zend_Controller_Action_HelperBroker::addPath ( "Sirah/Helpers/Actions", "Sirah_Helper_Action" );
		}
		if($this->_request->isXmlHttpRequest()) {
		   $this->_helper->layout->disableLayout(true);
		}
	}
	
	
	
	public function postDispatch( )
	{
		$messages  = $this->_helper->Message->getCurrentMessages();
		$message   = "";
		if( count(   $messages ) ) {
			foreach( $messages as $key => $msg ) {
				if( is_array($msg ) ) {
					$message   .= implode(" , " , $msg );
				} elseif(!empty( $msg ) ) {
					$message   .= $msg ;
				}
			}
		}
		$this->view->message   = $message ;
	}
	
	/**
	 * Permet d'initialiser le controlleur
	 * 
	 * @access public
	 * @param
	 *       	 void
	 * @return void
	 */
	protected function _createModel($class) 
	{
		if (array_key_exists ( $class, $this->_models )) {
			return $this->_models [$class];
		}		
		if (! class_exists ( $class )) {
			throw new Sirah_Controller_Exception ( "La classe " . $class . " n'est pas trouvée. Verifiez vos chemins d'inclusions " );
		}
		$this->_models [$class] = new $class();
		return $this->_models [$class];
	}
	
	/**
	 * Permet de récupérer un modele pour le controlleur
	 * 
	 * @access public
	 * @param   string le nom du modèle; l'identifiant de la ligne
	 * @return Zend_Db_Table_Row instance
	 */
	public function getModel($name = null, $rowid = null) 
	{
		$request  = $this->getRequest();
		if (null == $name || empty ( $name )) {
			$name = $request->getControllerName();
		}		
		// On crée le modèle
		$model_class = 'Model_' . ucfirst ( $name );
		$model = $this->_createModel ( $model_class );
		
		if (null !== $rowid && ! empty ( $rowid )) {
			$model = $model->findRow( $rowid );
   	        $this->_models [$model_class] = $model;
		}
		return $model;
	}
	
	/**
	 * Permet de définir les informations de la redirection de la requete
	 * 
	 * @access public
	 * @param
	 *       	 string le message de redirection
	 * @param
	 *       	 string le type de message de redirection
	 * @param
	 *       	 array en option les paramètres de redirection
	 * @return void
	 */
	public function setRedirect( $message = null, $type = 'error', $url = array()) 
	{		
		if (is_array ( $url ) && ! empty ( $url )) {
			if (array_key_exists ( 'controller', $url )) {
				if ($url ['controller'] === null) {
					$url ['controller'] = '/';
				}
			} else {
				$url ['controller'] = '/';
			}			
			if (! array_key_exists ( 'action', $url )) {
				$url ['action'] = '';
			}
			$this->_redirectUrl = $url ['controller'] . $url ['action'];
		}
		$this->_message     = $message;
		$this->_messageType = $type;
	}
	
	/**
	 * Permet de rediriger la requete vers un autre controlleur
	 * 
	 * @access public
	 * @param
	 *       	 string l'url de redirection
	 * @return void
	 */
	public function redirect($url = null, $options=[]) 
	{
		if( defined("APPLICATION_HOST_PORT")) {
			$_SERVER['SERVER_PORT'] = APPLICATION_HOST_PORT;
		}
		$errorMessages  = array();
		$url            = trim($url);
	    if( null !== $this->_message) {
		    $this->getHelper("Message")->addMessage($this->_message , $this->_messageType);
		}		
 
		//Si c'est une requete ajax, on recup�re les erreurs qu'on affiche en json
		if( $this->_request->isXmlHttpRequest() ) {
		    $errorMessages    = array_shift($this->getHelper("Message")->getCurrentMessages());
		    if(empty($errorMessages)) {
		       $errorMessages = array_shift($this->getHelper("Message")->getMessages());
		    }
		    $errorToString = (is_array($errorMessages)) ? implode(" , " , $errorMessages) : array();
		    
		    $this->_helper->viewRenderer->setNoRender(true);
		    $this->_helper->layout->disableLayout(true);		    
		    echo Zendx_JQuery::encodeJson(array("error" => $errorToString ));
		    exit;
		}		
		if( null == $url || empty( $url ) ) {
			$url = $this->_redirectUrl;
		}		
		$this->_helper->redirector->gotoUrl($url, $options);
	}
	
	
	/**
	 * Méthode statique permettant de créer des URLs
	 *
	 * @access public
	 * 
	 * @param  mixed les paramètres fournis pour la création de l'URL
	 *        	
	 * @return void
	 */
	static public function uri( $pageUriOptions = array( ) )
	{
		Zend_Uri::setConfig(array('allow_unwise' => true));
		if( is_string( $pageUriOptions ) && Zend_Uri::check( $pageUriOptions ) ) {
			$pageUriOptions = array("uri" => $pageUriOptions);
		} elseif( is_string( $pageUriOptions ) ) {
			$front          = Zend_Controller_Front::getInstance();
			$request        = $front->getRequest();
			$thisController = $request->getControllerName();
			$defaultModule  = $front->getDefaultModule();
			$pageUriOptions = array("action" => $pageUriOptions, "controller" => $thisController, "module" => $defaultModule );
		}
		if( empty( $pageUriOptions ) ||
		    ( !isset( $pageUriOptions["uri"] ) && !isset( $pageUriOptions["action"] ) && !isset( $pageUriOptions["controller"] ) ) ) {
			throw new Sirah_Controller_Exception ( "ERREUR:CREATION URL => Les paramètres fournis pour la création de l'URL sont invalides" );
		}
		if( !isset( $pageUriOptions["action"] ) && isset( $pageUriOptions["controller"] ) ) {
			$pageUriOptions["action"]  = "index";
		}
		try {
		    $page   = Zend_Navigation_Page::factory( $pageUriOptions );
		} catch( Exception $e ) {
			throw new Sirah_Controller_Exception ( "ERREUR:CREATION URL => ".$e->getMessage() );
		}
		return $page->getHref();
	}
	
	/**
	 * Méthode magique permettant de controller la modification d'une propriété
	 * du controlleur
	 * 
	 * @access public
	 * @param  string l'attribut auquel qu'on souhaite modifier; la valeur de
	 *        	l'attribut
	 * @return void
	 */
	public function __call($methodName, $args) 
	{
		require_once 'Sirah/Controller/Exception.php';
		if ('Action' == substr ( $methodName, - 6 )) {
			$action = substr ( $methodName, 0, strlen ( $methodName ) - 6 );
			throw new Sirah_Controller_Exception ( sprintf ( 'L\'action "%s" que vous souhaitez executer n\'existe pas', $action ), 404 );
		}
		
		throw new Sirah_Controller_Exception (sprintf ( 'La ressource "%s" a laquelle vous voulez accéddder n\'existe pas ', $methodName ), 500 );
	}
	
	/**
	 * Méthode magique permettant de creer un modele, une table ou un formulaire
	 * 
	 * @access public
	 * @param
	 *       	 string l'attribut auquel on souhaite accéder
	 * @return mixte l'attribut
	 */
	public function __get($attr) 
	{
		$model = null;
		
		// On determine si c'est un modele ou pas
		if (strtolower ( substr ( $attr, 0, 5 ) ) == 'model') {
			$class = 'Model_' . substr ( $attr, 5 );
			$model = $this->_createModel ( $class );
		}		
		if (null === $model) {
			if(isset($this->$attr)){
				return $this->$attr;
			}
			throw new Sirah_Controller_Exception ( "Acces à la propriété $attr inconnue du controlleur" );
		}
		return $model;
	}
	
	
	/**
	 * Une méthode  qui permet d'autoriser l'accès à une fonctionnalité
	 * 
	 * @param  string $action l'identifiant de l'action
	 * @param  string $resource l'identifiant de la ressource sur laquelle il faut exécuter l'action
	 * @param  string l'identifiant de l'utilisateur concerné
	 *
	 *
	 *
	 */
	public function authorize( $action , $resource ="" , $userid="")
	{
		if ( empty( $resource ) ) {
			$request    = $this->getRequest();
			$resource   = $request->getControllerName();
		}
		$cacheManager   = Sirah_Fabric::getCachemanager();
		$user           = Sirah_Fabric::getUser();
		$userid         = ( !empty( $userid) )  ? $userid : $user->userid;
		$action         = ( !empty($action)   ) ? $action : "index";
		if( $cacheManager->hasCache("Acl") ){
			$aclCache   = $cacheManager->getCache("Acl");
			$cacheValue = implode("_" , array("aclAllowed" , $userid , $resource , $action));
			$aclCache->save("isAllowed" , $cacheValue);
		}
		return true;
	}
	
	
	static public function getUploadMessage($errorCode)
	{
		$errorMessage  = "";
		switch( $errorCode ) {	
			case Zend_Validate_File_ImageSize::WIDTH_TOO_SMALL:
			case Zend_Validate_File_ImageSize::HEIGHT_TOO_SMALL:
			case Zend_Validate_File_IsImage::FALSE_TYPE:
			case Zend_Validate_File_IsImage::FALSE_TYPE:NOT_DETECTED :
				$errorMessage = "Le fichier que vous avez selectionné n'est pas une image valide" ;
				break;
			case Zend_Validate_File_Size::TOO_BIG:			
				$errorMessage = "La taille du fichier est trop grande" ;
				break;
		    case Zend_Validate_File_ImageSize::HEIGHT_TOO_BIG:
			case "fileImageSizeHeightTooBig":
				$errorMessage = "Le fichier que vous avez selectionné n'est pas une image valide car la longeur en pixels de l'image est trop grande";
				break;
			case Zend_Validate_File_ImageSize::WIDTH_TOO_BIG:
			case "fileImageSizeWidthTooBig":
				$errorMessage = "Le fichier que vous avez selectionné n'est pas une image valide car la largeur en pixels de l'image est trop grande";
				break;
			case Zend_Validate_File_Size::TOO_BIG:TOO_SMALL :
				$errorMessage = "La taille du fichier est trop petite" ;
				break;
			case "fileUploadErrorIniSize":
				 $errorMessage = "La taille du fichier  dépasse la taille maximale autorisée dans la configuration du système" ;
				 break;
			case "fileUploadErrorFormSize":
				$errorMessage  = "La taille du fichier dépasse la taille maximale autorisée par le formulaire" ;
				break;
			case "fileUploadErrorNoFile":
				$errorMessage  = " Le fichier n'a pas été chargé ";
				break;
			case 'fileUploadErrorNoTmpDir':
				$errorMessage  = " Aucun dossier temporaire valide n'a été retrouvé pour ce fichier ";
				break;
			case  'fileUploadErrorCantWrite':
				$errorMessage  = " L'extension du fichier n'est pas valide ";
				break;
			case Zend_Validate_File_Upload::CANT_WRITE:
				$errorMessage  = " Le fichier ne peut etre copié dans son dossier de destination ";
				break;
		    case Zend_Validate_File_Upload::FILE_NOT_FOUND:
		    case Zend_Validate_File_Extension::FALSE_EXTENSION:
				 $errorMessage  = " Le fichier n'a pas été retrouvé ";
				break;
		}
		return $errorMessage;
	}
	
	/**
	 * Une méthode protegeée qui permet de créer un captcha
	 *
	 *
	 *
	 */
	protected function _createCaptcha( $length = 8 , $font = "trebucit.ttf" , $imgDir="" , $imgUrl="")
	{
		clearstatcache();
		if( FALSE == is_dir( $imgDir ) ) {
			$imgDir = realpath(DOCUMENTS_PATH."/../../myTpl/public/images/captchas/");
		}
		if( FALSE == is_dir( $imgDir ) ) {
			$imgUrl = ROOT_PATH."/myTpl/public/images/captchas/";
		}	
		//On créee et initialise le captcha
		$captcha              = new Zend_Captcha_Image();
		$captcha->setExpiration(120);
		$captcha->setTimeout(500);
		$captcha->setWordLen($length);
		$captcha->setHeight(100);
		$captcha->setWidth(350);
		$captcha->setFont(TTF_DIR.DS.$font)->setFontSize(50);
		$captcha->setSuffix(".png");
		$captcha->setImgUrl($imgUrl);
		$captcha->setImgDir($imgDir);
		return $captcha;
	}
	
	
	public function download( $fichier , $extension )
	{
		$type          = "application/octet-stream";
		switch( strtolower( $extension ) ) {
			case "doc" :
			case "docx":
				$type = "application/msword";
				break;
			case "pdf" :
				$type = "application/pdf";
				break;
			case "xls":
			case "csv":
			case "xlsx":
				$type = "application/excel";
				break;
			default:
				$type = "application/octet-stream";
		}
		header('Content-Description: File Transfer');
		header('Content-Type:'.$type);
		header('Content-Disposition: attachment; filename='.basename($fichier));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($fichier));
		if( $content = ob_get_clean() ) {
			if(!$this->_request->isXmlHttpRequest()) {
				echo "Des entetes HTTP ont déjà été transmises";
				exit();
			}
			echo ZendX_JQuery::encodeJson(array("error" => "Des entetes HTTP ont déjà transmises"));
			exit;
		}
		flush();
		@readfile($fichier);
	}

}
