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

class Sirah_Db_Table extends Zend_Db_Table_Abstract
{
	
	/**
	 * Le nom du préfixe de la table
	 *
	 * @var string
	 */
	protected $_tableNamePrefix    = "";
	
	const NAME_PREFIX              = 'namePrefix';
	
	public function __construct(array $config = array())
    {
       parent::__construct($config);      
       if(isset($config["name_prefix"]) && !empty($config["name_prefix"])){
       	  $this->_tableNamePrefix    = $config["name_prefix"];
        } elseif (defined("APPLICATION_TABLE_NAME_PREFIX")){
        	$this->_tableNamePrefix  =  APPLICATION_TABLE_NAME_PREFIX;
        }        
        if(null!==$this->_tableNamePrefix && !empty($this->_tableNamePrefix)){
        	$newTableName            =  $this->_tableNamePrefix.$this->info(self::NAME);
        	$prefixName              = preg_quote($this->_tableNamePrefix);
        	if(!preg_match("/^$prefixName/i" , $this->info(self::NAME))) {
        		$this->setOptions(array(self::NAME => $newTableName));
        	}
        }
     }
     
     
     /**
      * Permet de récupérer la première clé primaire
      *
      * @param   string la position de la clé
      * @return  mixed la ou les clés primaires
      */
     function getPrimary($key = 1 )
     {
     	$primaryKey = (array) $this->_primary;
     	if(count($primaryKey)==1 && $key == 1){
     		return current($primaryKey);
     	}elseif( isset($primaryKey[$key]) ){
     		return  $primaryKey[$key];
     	}
     	return  $primaryKey;
     }
     
     
     /**
      * setOptions()
      *
      * @param array $options
      * @return Zend_Db_Table_Abstract
      */
     public function setOptions(Array $options)
     {
     	foreach ($options as $key => $value) {
     		switch ($key) {
     			case self::ADAPTER:
     				$this->_setAdapter($value);
     				break;
     			case self::DEFINITION:
     				$this->setDefinition($value);
     				break;
     			case self::DEFINITION_CONFIG_NAME:
     				$this->setDefinitionConfigName($value);
     				break;
     			case self::SCHEMA:
     				$this->_schema = (string) $value;
     				break;
     			case self::NAME:
     				$this->_name = (string) $value;
     				break;
     			case self::NAME_PREFIX:
     				$this->_tableNamePrefix = (string) $value;
     			    break;
     			case self::PRIMARY:
     				$this->_primary = (array) $value;
     				break;
     			case self::ROW_CLASS:
     				$this->setRowClass($value);
     				break;
     			case self::ROWSET_CLASS:
     				$this->setRowsetClass($value);
     				break;
     			case self::REFERENCE_MAP:
     				$this->setReferences($value);
     				break;
     			case self::DEPENDENT_TABLES:
     				$this->setDependentTables($value);
     				break;
     			case self::METADATA_CACHE:
     				$this->_setMetadataCache($value);
     				break;
     			case self::METADATA_CACHE_IN_CLASS:
     				$this->setMetadataCacheInClass($value);
     				break;
     			case self::SEQUENCE:
     				$this->_setSequence($value);
     				break;
     			default:
     				// ignore unrecognized configuration directive
     				break;
     		}
     	}
     
     	return $this;
     }
     
     /**
      * Retourne des informations sur la table
      *
      * You can elect to return only a part of this information by supplying its key name,
      * otherwise all information is returned as an array.
      *
      * @param  $key The specific info part to return OPTIONAL
      * @return mixed
      */
     public function info($key = null)
     {
     	$this->_setupPrimaryKey();
     
     	$info = array(
     			self::SCHEMA           => $this->_schema,
     			self::NAME             => $this->_name,
     			self::NAME_PREFIX      => $this->_tableNamePrefix,
     			self::COLS             => $this->_getCols(),
     			self::PRIMARY          => (array) $this->_primary,
     			self::METADATA         => $this->_metadata,
     			self::ROW_CLASS        => $this->getRowClass(),
     			self::ROWSET_CLASS     => $this->getRowsetClass(),
     			self::REFERENCE_MAP    => $this->_referenceMap,
     			self::DEPENDENT_TABLES => $this->_dependentTables,
     			self::SEQUENCE         => $this->_sequence);
     
     	if ($key === null) {
     		return $info;
     	}
     
     	if (!array_key_exists($key, $info)) {
     		require_once 'Zend/Db/Table/Exception.php';
     		throw new Zend_Db_Table_Exception('Aucune information de la table relative à "' . $key . '" n\'a été trouvé');
     	}
     
     	return $info[$key];
     }

  }
