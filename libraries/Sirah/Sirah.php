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
 * Cette classe représente le composant d'initialisation des ressources de SIRAH
 * 
 * Elle réprésente la classe d'initialisation de l' application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
 
class Sirah_Application  extends Zend_Application
{
	
	/**
	 * Le nom de l'application
	 *
	 * @var string
	 */
	protected $_name     = null;
	
	/**
	 * L'identifiant de l'application
	 *
	 * @var integer
	 */	
	protected $_id       = null;
	
	
	/**
	 *  L'utilisateur actuel de l'application
	 *
	 * @var Sirah_User instance
	 */
	protected $_identity = null;
	
	
	/**
	 *  Le conteneur des messages d'erreurs
	 *
	 * @var array
	 */
	protected $_messageQueue = array();
	
	
	
	/**
	 * Permet de créer une instance de l'application.
	 *
	 * @param   array  $options les options de configuration de l'application.
	 *
	 * @return  void
	 *
	 */
	static function getInstance($name="")
	{
		
	}
		
	
	/**
	 * Permet de sortir de l'application
	 *
	 * @param   integer  $code  Le code de la sortie
	 *
	 * @return  void
	 *
	 */
	public function close($code = 0)
	{
		exit($code);
	}
		
	/**
	 * Permet de stocker des messages d'erreur dans le conteneur de messages de l'application
	 *
	 * @param   string le message d'erreur à stocker
	 * @param   string le type du message à stocker
	 *
	 * @return  void
	 *
	 */
	public function enqueueMessage($msg,$type="error")
	{
		// Si l'application ne contient aucune erreur dans son conteneur, on vérifie dans la session
		if (!count( $this->_messageQueue ) ) {
			$sessionNamespace  = new Zend_Session_Namespace();
			$sessionQueue      = $sessionNamespace->messagequeue;

			if (count($sessionQueue)) {
				$this->_messageQueue            = $sessionQueue;
				$sessionNamespace->messagequeue = array();
			}
		}
		// on stocke le message d'erreur dans l'application
		$this->_messageQueue[] = array('message' => $msg, 'type' => strtolower($type));	
	}
		
	
	/**
	 * Permet de recupérer les messages d'erreur de l'application
	 *
	 * @return array les messages d'erreurs
	 *
	 */
	public function getMessage()
	{
		// Si l'application ne contient aucune erreur dans son conteneur, on vérifie dans la session
		if (!count( $this->_messageQueue ) ) {
		    $sessionNamespace  = new Zend_Session_Namespace();
			$sessionQueue      = $sessionNamespace->messagequeue;

			if (count( $sessionQueue ) ) {
				$this->_messageQueue            = $sessionQueue;
				$sessionNamespace->messagequeue = array();
			}
		}		
		return $this->_messageQueue;
	}
		
	
	/**
	 * Permet de recupérer le nom de l'application
	 *
	 * @return string le nom de l'application
	 *
	 */
	public function getApplicationame()
	{
	   return $this->_name;
	}
	
	
	public function getApplicationid()
	{
	   return $this->_id;
	}
		
	/**
	 * La méthode permet de déterminer si l'hote est Windows ou pas
	 *
	 * @return  boolean  Vrai si l'hote est un windows OS
	 *
	 * @since   11.1
	 */
	public static function isWinOS()
	{
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}
	

}