<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basÃ©e sur les composants des la
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
 * Cette classe permet de gere les cookies de l'application
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Cookie_Namespace
{
	/**
	 * Namespace - L'espace de nom dans lequel le cookie est enregistré
	 *
	 * @var string
	 */
	protected $_namespace = "Default";
	
	/**
	 * Domaine  - Le domaine auquel est attribué le cookie
	 *
	 * @var string
	 */
	protected $_domain = null;
	
	
	/**
	 * Path  - Le dossier de l'application concerné
	 *
	 * @var string
	 */
	protected $_path = null;
	
	
	/**
	 * L'attribut sécurisé du cookie
	 *
	 * @var boolean
	 */
	protected $_secured = 0;
	
	/**
	 * L'attribut sécurisé du cookie
	 *
	 * @var boolean
	 */
	protected $_httpOnly = 0;
	
	
	/**
	 * Expire - La durée de vie du cookie
	 *
	 * @var integer
	 */
	protected $_expire = 3600;
	
	
	
	
	public function setExpirationSeconds($seconds, $variables = null)
	{
		
	}
	
	
	
	

	

  }
