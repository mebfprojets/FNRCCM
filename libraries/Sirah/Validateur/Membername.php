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
 * Cette classe permet de vérifier la validité du nom d'utilisateur
 * saisi par un utilisateur de la plateforme
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Validateur_Membername extends Zend_Validate_Abstract
{

  const TROP_LONG              = 'long';

  const TROP_COURT             = 'court';

  const CARACTER_INVALID       = 'invalid';

  public $minimum              = 2;

  public $maximum              = 150;


  protected $invalid_caracter  = null;

  protected $_messageVariables = array(
                                        'min'                  => 'minimum',
                                        'max'                  => 'maximum',
                                        'invalid'              => 'invalid_caracter'
    );

  protected $_messageTemplates = array(      
                                       self::TROP_LONG        =>  "Votre nom '%value%' est trop long, il ne doit pas depasser '%max%'  caracteres ",
                                       self::TROP_COURT       =>  "Votre nom '%value%' est trop court, il doit contenir au minimum '%min%' caracteres ",
                                       self::CARACTER_INVALID =>  "Votre nom '%value%' contient un caractere invalide : '%invalid%',les caracteres speciaux sont interdits ");


   /**
    * Cette méthode permet de vérifier la validité 
    * du nom d'utilisateur
    * 
    * @access public
    * @param   string | array  $value le mot de passe
    * @return  boolean vrai ou faux
    */
    public function isValid($value)
    {
    	$username  = (is_array($value) && array_key_exists("username",$value)) ? $value["username"] : $value;    	 
    	if(!is_string($username)){
    		return false;
    	}
        $this->_setValue($username);

        if(strlen($username) < $this->minimum){
           $this->_error(self::TROP_COURT);
           return false;
        }

       if(strlen($username) > $this->maximum){
          $this->_error(self::TROP_LONG);
          return false;
       }
      $pattern="/([\s!()^+=#\]\[*~|$`?<>}&%{\/])/";

      if( preg_match_all( $pattern , $username , $matches)){
          $this->invalid_caracter  =  $matches[1];
          $this->_error(self::CARACTER_INVALID);
          return false; 
       }
      return true;
    }


    public function getInvalidCaracters()
    {
      return $this->invalid_caracters;
    }

}
