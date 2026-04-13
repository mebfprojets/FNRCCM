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

class Sirah_Model_Table_Menu extends Sirah_Db_Table
{
	
	protected $_name    = "system_layout_menu";
	
	protected $_primary = "menuid";
	
	
	
	public function getRows($position = null) 
	{		
		$select        = $this->select();	
		if( null !== $position && !empty($position) ) {
			$select->where("position=?" , $position);
		}
		$rowset        = $this->fetchAll($select);		
		if($rowset) {
			return  $rowset->toArray();
		}
		return array();
	}

	public function findRow($id)
	{
		$select        = $this->select()->where("menuid=?" , intval($id));
		return $this->fetchRow($select);
	}
	
  }
