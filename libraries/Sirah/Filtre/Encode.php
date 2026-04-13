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
 * Cette classe permet de vérifier la validité des emails
 * saisis par les utilisateurs de la plateforme
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Filtre_Encode extends Zend_Filter_HtmlEntities
{
	

	public function filter($value)
	{
		//$floatVal     = preg_replace("/[^a-z0-9]/i", "",utf8_encode( $value));
		$encodedValue = utf8_encode( $value);
		return $encodedValue;
	}
	
	

}
