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
 * Cette classe est une interface pour la passerelle vers la table des utilisateurs
 * 
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

interface Sirah_User_Table_Interface
{

	 public function getData() ;
	 
	 public function getRoles();
	 
	 public function getUserIdBy($identifiant,$params);
	 
	 public function setFromArray($data);
	 
	 public function save($data);
	 
	 public function setLastVisiteDate($timestamp);
	 
	 public function setLastIpAddress($ipaddress);
	 
	 public function setLastHttpClient($client);
	 
	 public function setConnected($state);	
	 
	 public function setNbConnections($nb);
	 
	 public function setLocked($locked);
	 
	 public function setBlocked($blocked);
	 
	 public function setActivated($activated);
	 
	 public function setExpired($expired);

  }
