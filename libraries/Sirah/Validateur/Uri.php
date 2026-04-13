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
 * Cette classe permet de vérifier la validité des URLs
 * saisis par les utilisateurs de la plateforme
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Validateur_Url extends Zend_Validate_Abstract
{
	
	const INVALID_URL = 'invalidUrl';
	
	protected $_messageTemplates = array(
			self::INVALID_URL   => "'%value%' est une URL invalide .",
	);
	
	
	public function isValid($value)
	{
		$valueString = (string) $value;
		$this->_setValue($valueString);
	
		if (!Zend_Uri::check($value)) {
			$this->_error(self::INVALID_URL);
			return false;
		}
		return true;
	}

  

}
