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


// Définition d'erreur: Paramètres invalides
define( 'SIRAH_ERROR_ILLEGAL_OPTIONS', 1 );
// Définition d'erreur:  la fonction appellée n'existe pas
define( 'SIRAH_ERROR_CALLBACK_NOT_CALLABLE', 2 );
// Définition d'erreur: Gestionnaire d'erreur invalide
define( 'SIRAH_ERROR_ILLEGAL_MODE', 3 );

/*
 * Le conteneur des données du gestionnaire d'erreurs
*/
$GLOBALS['_SIRAH_ERROR_STACK'] = array();

/*
 * Les niveaux d'erreurs par défaut disponibles
*/
$GLOBALS['_SIRAH_ERROR_LEVELS'] = array(
		E_NOTICE 	=> 'Notice',
		E_WARNING	=> 'Warning',
		E_ERROR 	=> 'Error'
);

/*
 * Default error handlers
*/
$GLOBALS['_SIRAH_ERROR_HANDLERS'] = array(
		E_NOTICE 	=> array( 'mode' => 'message' ),
		E_WARNING 	=> array( 'mode' => 'message' ),
		E_ERROR 	=> array( 'mode' => null)
);

/**
 * Cette classe permet de gérer les erreurs générées dans l'application
 * 
 * Cette classe est beaucoup inspirée en architecture et concept des classes  patErrorManager et JError du package de Joomla 
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Error 
{
	
	/**
	 * Cette méthode permet de déterminer si la valeur est un objet exception ou pas
	 *
	 * @static
	 * @access	public
	 * @param	mixed	&$object	L'objet à vérifier
	 * @return	boolean	True si c'est une exception, false sinon
	 * @since	
	 */
	function isError(& $object)
	{
		if (!is_object($object)) {
			return false;
		}
		return is_a($object, 'Sirah_Exception') || is_a($object, 'Sirah_Error') || is_a($object, 'Exception');
	 }	
	 
	 /**
	  * Permet de récuperer la dernière erreur dans le conteneur des erreurs
	  *
	  * @static
	  * @access	public
	  * @return	mixed	Le dernier objet exception stocké dans le conteneur ou false si le conteneur ne contient rien
	  * @since	
	  */
	 function & getError($unset = false)
	 {
	 	if (!isset($GLOBALS['_SIRAH_ERROR_STACK'][0])) {
	 		$false = false;
	 		return $false;
	 	}
	 	if ($unset) {
	 		$error = array_shift($GLOBALS['_SIRAH_ERROR_STACK']);
	 	} else {
	 		$error = &$GLOBALS['_SIRAH_ERROR_STACK'][0];
	 	}
	 	return $error;
	 }
	 
	 
	 /**
	  * La méthode permet de récuperer tout le contenu du conteneur des erreurs
	  *
	  * @static
	  * @access	public
	  * @return	array 	
	  * @since	
	  */
	 function & getErrors()
	 {
	 	return $GLOBALS['_SIRAH_ERROR_STACK'];
	 }
	 
	 
	 /**
	  * Permet de créer un objet exception en lui fournissant les arguments
	  *
	  * @static
	  * @param	int		$level	Le niveau d'erreur, vous pouvez utiliser: E_ERROR, E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE.
	  * @param	string	$code	Le code d'erreur interne de l'application
	  * @param	string	$msg	Le message d'erreur
	  * @param	mixed	$info	Optional: Des informations d'erreurs complementaires
	  * @return	mixed	L'objet Sirah_Error
	  * @since	
	  */
	 function & raise($level, $code, $msg, $info = null)
	 {	 
	 	// On crée l'exception de l'erreur
	 	$exception = new Sirah_Error_Exception($msg, $code, $level, $info);
	 
	 	$handler = Sirah_Error::getErrorHandling($level);
	 
	 	$function = 'handle'.ucfirst($handler['mode']);
	 	if (is_callable(array('Sirah_Error', $function))) {
	 		$reference = & Sirah_Error::$function($exception,(isset($handler['options'])) ? $handler['options'] : array());
	 	} else {
	 		exit(
	 				'Sirah_Error::raise -> La méthode statique Sirah_Error::' . $function . ' n\'existe pas.' .
	 				' Contacter un administrateur pour le débogage' .
	 				'<br /><strong>L\'erreur générée est : </strong> ' .
	 				'<br />' . $exception->getMessage()
	 		);
	 	}	 
	 	//on stocke l'erreur dans le conteneur et on retourne l'erreur
	 	$GLOBALS['_SIRAH_ERROR_STACK'][] =& $reference;
	 	return $reference;
	 }
	 
	 
	 /**
	  * Génère une erreur de niveau E_ERROR
	  *
	  * @static
	  * @param	string	$code	Le code interne de l'erreur
	  * @param	string	$msg	Le message d'erreur.
	  * @param	mixed	$info	Optional: Des informations d'erreurs complementaires
	  * @return	object	$error	
	  * @since	
	  */
	 function & raiseError($code, $msg, $info = null)
	 {
	 	$reference = & Sirah_Error::raise(E_ERROR, $code, $msg, $info, true);
	 	return $reference;
	 }
	 	 
	 
	 /**
	  * Génère une erreur de niveau E_WARNING
	  *
	  * @static
	  * @param	string	$code	Le code interne de l'erreur
	  * @param	string	$msg	Le message d'erreur.
	  * @param	mixed	$info	Optional: Des informations d'erreurs complementaires
	  * @return	object	$error	
	  * @since	
	  */
	 function & raiseWarning($code, $msg, $info = null)
	 {
	 	$reference = & Sirah_Error::raise(E_WARNING, $code, $msg, $info);
	 	return $reference;
	 }
	 
	 /**
	  * Génère une erreur de niveau E_NOTICE
	  *
	  * @static
	  * @param	string	$code	Le code interne de l'erreur
	  * @param	string	$msg	Le message d'erreur.
	  * @param	mixed	$info	Optional: Des informations d'erreurs complementaires
	  * @return	object	$error	
	  * @since	
	  */
	 function & raiseNotice($code, $msg, $info = null)
	 {
	 	$reference = & Sirah_Error::raise(E_NOTICE, $code, $msg, $info);
	 	return $reference;
	 }
	 
	 /**
	  * Permet de récupérer le gestionnaire d'erreur actuel pour un niveau d'erreur spécifique
	  *
	  * @static
	  * @param	int		$level	Le niveau d'erreur : E_ALL, E_WARNING, E_NOTICE...
	  * @return	array	un tableau des informations du gestionnaire d'erreurs
	  * @since	
	  */
	 function getErrorHandling( $level )
	 {
	 	return $GLOBALS['_SIRAH_ERROR_HANDLERS'][$level];
	 }
	 	 
	 
	 /**
	  * Permet de définir la facon dont cette classe va gérer les différents niveaux d'erreurs. Utilisez cette méthode pour écraser les gestionnaires par défaut
	  *
	  * Les différents modes de gestion d'erreurs:
	  * - ignore
	  * - echo
	  * - verbose
	  * - die
	  * - message
	  * - log
	  * - callback
	  *
	  * C'est aussi possible de définir plusieurs gestionnaires à la fois avec les bits d'opérations de PHP
	  * Exemples:
	  * - E_ALL = Set the handling for all levels
	  * - E_ERROR | E_WARNING = Permet de définir le gestionnaire pour les erreurs et les avertissements
	  * - E_ALL ^ E_ERROR = Permet de définir le gestionnaire pour les differents niveaux d'erreurs sauf les erreurs
	  *
	  * @static
	  * @param	int		$level		Le niveau d'erreur pour lequel nous voulons définir un gestionnaire
	  * @param	string	$mode		Le mode de gestion des erreurs.
	  * @param	mixed	$options	Optionel:
	  * @return	mixed	True en cas de succès, ou un objet Sirah_Exception en cas d'echec.
	  * @since
	  */
	 function setErrorHandling($level, $mode, $options = null)
	 {
	 	$levels = $GLOBALS['_SIRAH_ERROR_LEVELS'];
	 
	 	$function = 'handle'.ucfirst($mode);
	 	if (!is_callable(array ('Sirah_Error',$function))) {
	 		return Sirah_Error::raiseError(E_ERROR, 'Sirah_Error:'.SIRAH_ERROR_ILLEGAL_MODE, 'Error Handling mode is not known', 'Mode: '.$mode.' is not implemented.');
	 	}
	 
	 	foreach ($levels as $eLevel => $eTitle) {
	 		if (($level & $eLevel) != $eLevel) {
	 			continue;
	 		}
	 
	 		// set callback options
	 		if ($mode == 'callback') {
	 			if (!is_array($options)) {
	 				return Sirah_Error::raiseError(E_ERROR, 'Sirah_Error:'.SIRAH_ERROR_ILLEGAL_OPTIONS, 'Les options d\'appel de la fonction invalide');
	 			}
	 
	 			if (!is_callable($options)) {
	 				$tmp = array ('GLOBAL');
	 				if (is_array($options)) {
	 					$tmp[0] = $options[0];
	 					$tmp[1] = $options[1];
	 				} else {
	 					$tmp[1] = $options;
	 				}
	 
	 			return Sirah_Error::raiseError(E_ERROR, 'Sirah_Error:'.SIRAH_ERROR_CALLBACK_NOT_CALLABLE, 'La fonction appelée n\'est pas valide', 'Function:'.$tmp[1].' scope '.$tmp[0].'.');
	 			}
	 		}
	 
	 		// save settings
	 		$GLOBALS['_SIRAH_ERROR_HANDLERS'][$eLevel] = array ('mode' => $mode);
	 		if ($options != null) {
	 			$GLOBALS['_SIRAH_ERROR_HANDLERS'][$eLevel]['options'] = $options;
	 		}
	 	}
	 
	 	return true;
	 }
	  
	 
	 /**
	  * Permet d'attacher un gestionnaire d'erreur à la classe Sirah_Error
	  *
	  * @access public
	  */
	 function attachHandler()
	 {
	 	set_error_handler(array('Sirah_Error', 'customErrorHandler'));
	 }
	 
	 /**
	  * Permet de détacher le gestionnaire d'erreur de la classe Sirah_Error
	  *
	  * @access public
	  */
	 function detachHandler()
	 {
	 	restore_error_handler();
	 }
	 
	 /**
	  * Permet d'enregistrer un nouveau niveau d'erreur aux gestionnaires d'erreurs
	  *
	  * Il permet d'ajouter des niveaux d'erreur 
	  * - E_NOTICE
	  * - E_WARNING
	  * - E_NOTICE
	  *
	  * @static
	  * @param	int		$level		Le niveau d'erreur à enregistrer
	  * @param	string	$name		Le nom du niveau d'erreur compréhensible des humains
	  * @param	string	$handler	Le gestionnaire d'erreur défini pour le nouveau niveau d'erreur [optionnel]
	  * @return	boolean	True on success; falsesi le niveau d'erreur a déjà été enregistré
	  * @since	
	  */
	 function registerErrorLevel( $level, $name, $handler = 'ignore' )
	 {
	 	if( isset($GLOBALS['_SIRAH_ERROR_LEVELS'][$level]) ) {
	 		return false;
	 	}
	 	$GLOBALS['_SIRAH_ERROR_LEVELS'][$level] = $name;
	 	SIRAH_Error::setErrorHandling($level, $handler);
	 	return true;
	 }
	 
	 /**
	  * Permet de convertir un niveau d'erreur à une chaine de caractère compréhensible par un etre humain
	  * 
	  * ex:. E_ERROR sera traduit en 'Error'
	  *
	  * @static
	  * @param	int		$level	le niveau d'erreur à traduire
	  * @return	mixed	la chaine de carctère traduisant le niveau
	  * @since	1.5
	  */
	 function translateErrorLevel( $level )
	 {
	 	if( isset($GLOBALS['_SIRAH_ERROR_LEVELS'][$level]) ) {
	 		return $GLOBALS['_SIRAH_ERROR_LEVELS'][$level];
	 	}
	 	return false;
	 }
	 
	 /**
	  * Gestionnaire d'erreur permettant d'ignorer une erreur
	  *
	  * @static
	  * @param	object	$error		Exception object to handle
	  * @param	array	$options	Handler options
	  * @return	object	The exception object
	  * @since
	  *
	  * @see	raise()
	  */
	 function & handleIgnore(&$error, $options)
	 {
	 	return $error;
	 }
	 
	 /**
	  * Gestionnaire d'erreur permettant de générer une exception
	  *
	  * @static
	  * @param	object	$error		Exception object to handle
	  * @param	array	$options	Handler options
	  * @return	object	The exception object
	  * @since
	  *
	  * @see	raise()
	  */
	 function & handlePluginerror(&$error, $options)
	 {
	 	$front    = Zend_Controller_Front::getInstance();
	 	$response = $front->getResponse();
	 	
	 	if($front->throwExceptions()){
	 		throw $error;
	 	}
	 	$response->setException($error);
	 	return $error;
	 }
	 
	 /**
	  * Gestionnaire d'erreur pour afficher une erreur avec echo
	  *
	  * @static
	  * @param	object	$error		Exception object to handle
	  * @param	array	$options	Handler options
	  * @return	object	The exception object
	  * @since	1.5
	  *
	  * @see	raise()
	  */
	 function & handleEcho(&$error, $options)
	 {
	 	$level_human = Sirah_Error::translateErrorLevel($error->get('level'));
	 
	 	if (isset ($_SERVER['HTTP_HOST'])) {
	 		// output as html
	 		echo "<br /><b>jos-$level_human</b>: ".$error->get('message')."<br />\n";
	 	} else {
	 	// output as simple text
	 	if (defined('STDERR')) {
	 	fwrite(STDERR, "Sirah_$level_human: ".$error->get('message')."\n");
	 	} else {
	 	echo "Sirah_$level_human: ".$error->get('message')."\n";
	 	}
	 	}
	 	return $error;
	 }
	 
	 
	   /**
	 	 * Gestionnaire d'erreur qui permet d'arreter le script en cours
	 	 *
	 	 * @static
	 	 * @param	object	$error		Exception object to handle
	 	 * @param	array	$options	Handler options
	 	 * @return	object	The exception object
	 	 * @since	1.5
	 	 *
	 	 * @see	raise()
	 	 */
	 	function & handleDie(& $error, $options)
	 	{
	 		$level_human = Sirah_Error::translateErrorLevel($error->get('level'));
	 
	 		if (isset ($_SERVER['HTTP_HOST'])) {
	 		// output as html
	 		exit("<br /><b>Sirah_$level_human</b> ".$error->get('message')."<br />\n");
	 		} else {
	 	 // output as simple text
	 	 if (defined('STDERR')) {
	 	 fwrite(STDERR, "Sirah_$level_human ".$error->get('message')."\n");
	 	 } else {
	 	 exit("Sirah_$level_human ".$error->get('message')."\n");
	 	 }
	 		}
	 		return $error;
	 	}
	 
	 	 /**
	 	 * Gestionnaire d'erreur des messages
	 	 * 	- Enqueues the error message into the system queue
	 	 *
	 	 * @static
	 	 * @param	object	$error		Exception object to handle
	 	 * @param	array	$options	Handler options
	 	 * @return	object	The exception object
	 	 * @since	1.5
	 	 *
	 	 * @see	raise()
	 	 */
	 	 function & handleMessage(& $error, $options)
	 	 {
	 		$type = ($error->get('level') == E_NOTICE) ? 'notice' : 'error';
	 		
	 		//Permet de stocker le message dans une session
	 		$messageHandler   =   Zend_Controller_Action_HelperBroker::getStaticHelper("Message");
	 		
	 		$messageHandler->addMessage($error->get('message'), $type);
	 		
	 		return $error;
	 	 }
	 
	 	/**
	 	 * Gestionnaire d'erreur par journalisation
	 	 *
	 	 * @static
	 	 * @param	object	$error		Exception object to handle
	 	 * @param	array	$options	Handler options
	 	 * @return	object	The exception object
	 	 * @since	1.5
	 	 *
	 	 * @see	raise()
	 	 */
	 	 function & handleLog(& $error, $options)
	 	 {
	 		$logHandle  = (isset(Zend_Registry::isRegistered("log")))?Zend_Registry::get("log"):new Sirah_Log_Register();
	 		$logHandle->err($error->get('message'));
	 
	 		return $error;
	 	 }
	 
	 		/**
	 	 * Gestionnaire d'erreur à travers une fonction externe
	 	 *
	 	 * @static
	 	 * @param	object	$error		Exception object to handle
	 	 * @param	array	$options	Handler options
	 	 * @return	object	The exception object
	 	 * @since	1.5
	 	 *
	 	 * @see	raise()
	 	 */
	 	 function &handleCallback( &$error, $options )
	 	 {
	 		$result = call_user_func( $options, $error );
	 		return $result;
	 		}
	 
	 		
	 	/**
	 	 * Permet d'afficher une page d'erreur et bloque le script
	 	 *
	 	 * @static
	 	 * @param	object	$error Exception object
	 	 * @return	void
	 	 * @since	
	 	 */
	    function customErrorPage(&$error)
	    {
	    	
	    }
	    
	    
	 	function customErrorHandler($level, $msg)
	    {
	 	   Sirah_Error::raise($level, '', $msg);
	 	 }

  }
