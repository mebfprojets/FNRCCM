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

class Sirah_Model_Table_MenuItem extends Sirah_Db_Table
{
	
	protected $_name    = "system_layout_menu_element";
	
	protected $_primary = "elementid";
	
		
	public function getRows($menuid) 
	{		
		$select        = $this->select()->where("menuid=?" , intval($menuid));		
		$select->order(array("elementorder ASC"));
		$rowset        = $this->fetchAll($select);			
		if($rowset) {
			return  $rowset->toArray();
		}
		return array();
	}	
		
	
	public function findRow($id)
	{
		$select        = $this->select()->where("elementid=?" , intval($id));
		return $this->fetchRow($select);
	}
	
  }
