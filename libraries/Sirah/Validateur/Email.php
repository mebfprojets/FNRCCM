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

class Sirah_Validateur_Email extends Zend_Validate_EmailAddress
{
	
	
	/**
	 * @var array
	 */
	protected $_messageTemplates = array(
			self::INVALID            => "Le type fourni est invalide, sa valeur doit etre une chaine de caractères",
			self::INVALID_FORMAT     => "'%value%' n'est pas un email valide dans le format de base local-part@hostname",
			self::INVALID_HOSTNAME   => "'%hostname%' n'est pas un nom d'hote valide pour l'adresse email '%value%'",
			self::INVALID_MX_RECORD  => "'%hostname%' does not appear to have a valid MX record for the email address '%value%'",
			self::INVALID_SEGMENT    => "'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network.",
			self::DOT_ATOM           => "'%localPart%' can not be matched against dot-atom format",
			self::QUOTED_STRING      => "'%localPart%' can not be matched against quoted-string format",
			self::INVALID_LOCAL_PART => "'%localPart%' n'est pas un nom de domaine  valide pour l'adresse email '%value%'",
			self::LENGTH_EXCEEDED    => "'%value%' depasse la longueur autorisée ",
	);
	
	
	/**
	 * Cette méthode permet de vérifier la validité de l'email
	 *
	 * @access public
	 * @param   string | array  $value le mot de passe
	 * @return  boolean vrai ou faux
	 */
	public function isValid($value)
	{
		$email  = (is_array($value) && array_key_exists("email",$value)) ? $value["email"] : $value;
    	
    	if(!is_string($email)){
    		return false;
    	}
        $this->_setValue($email);
        
		return parent::isValid($email);		
	}


}
