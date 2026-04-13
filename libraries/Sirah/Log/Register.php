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
 * Cette classe correspond au gestionnaire de journalisation
 * des messages d'erreurs de l'application
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 *
 * @since
 *
 *
 */
class Sirah_Log_Register extends Zend_Log 
{
	
	/*
	 * var Zend_Log_Writer_Abstract instance
	 *
	 */
	protected $_defaultWriter = null;
	
	
	
	/*
	 * le constructeur de la classe
	 * 
	 * @param Zend_Log_Writer_Abstract $writer
	*
	*/
	function __construct(Zend_Log_Writer_Abstract $writer = null) 
	{
		parent::__construct ( $writer );
		$this->setDefaultWriter ();	
	}
	
	
	/**
	 * Définit le gestionnaire par défaut d'écriture des erreurs dans le journal
	 *
	 * @param Zend_Log_Writer_Abstract $writer
	 *
	 */
	public function setDefaultWriter(Zend_Log_Writer_Abstract $writer = null) 
	{
		
		// Le writer par defaut est la base de donnees
		if (null === $writer) {
			$db = null;
			// On recupere la base de donnees
			if (Zend_Registry::isRegistered("db"))
				$db = Zend_Registry::get( "db" );
			else
				$db = Zend_Db_Table::getDefaultAdapter();
			
			$writer = new Sirah_Log_Writer_Db( $db );
			$this->addWriter ( $writer, "default" );
		}
		
		$this->_defaultWriter = $writer;
	}
	
   /**
	* Enregistrer un gestionnaire d'écriture des erreurs dans le journal
	*
	* @param Zend_Log_Writer_Abstract $writer
	* @param string $writerName le nom du redacteur
	*
	*/
	public function addWriter(Zend_Log_Writer_Abstract $writer, $writerName = null) 
	{
		parent::addWriter($writer);
		if (null !== $writerName)
			$this->_writers [$writerName] = $writer;
	}
	
	
	/**
	 * Supprime un gestionnaire d'écriture des erreurs dans le journal
	 *
	 * @param string $writerName le nom du redacteur
	 *
	 */
	public function removeWriter($name) 
	{		
		$writer = &$this->getWriter ( $name );
		$writer->shutdown ();
		unset ($this->_writers[$name] );	
	}
	
	
	/**
	 * On veut recupérer un gestionnaire d'écriture des erreurs dans le journal
	 *
	 * @param string $writerName le nom du redacteur
	 *
	 */
	public function &getWriter($name ="default") 
	{
		$writer = null;		
		if (isset($this->_writers [$name] ))
			$writer = & $this->_writers [$name];		
		return $writer;	
	}
	
	
	/**
	 * On veut recupérer le gestionnaire d'écriture par defaut
	 *
	 */
	public function getDefaultWriter() 
	{
		
		return $this->_defaultWriter;
	}

}
