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
 * Cette classe permet de gérer les exceptions du package de SIRAH
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Model_Table extends Sirah_Model_Default
{
	
	protected $_schema = null;
	
	protected function _setupDatabaseAdapter()
	{
		$front         = Zend_Controller_Front::getInstance();
		$bootstrap     = $front->getParam('bootstrap');
		$multidb       = $bootstrap->getPluginResource('multidb');
		$this->_db     = $multidb->getDefaultDb();
		if( null != $this->_schema ) {
			$this->_db = $multidb->getDb( $this->_schema );
		}
	}
	
	public function __construct($config = array())
	{
		$class    = explode('_', get_class($this));
		$rowClass = ( ( null !== $this->_rowClass ) && !empty( $this->_rowClass ) && ( $this->_rowClass != "Zend_Db_Model_Row" ) ) ? $this->_rowClass : 'Model_' . substr(array_pop($class), 0, -1);
		if (!class_exists($rowClass)){
			throw new Sirah_Model_Exception("La classe $rowClass n'est pas trouvée");
		}
		$config  = array_merge( $config , array(self::ROW_CLASS => $rowClass));
		parent::__construct( $config );
	}
	
	
  }
