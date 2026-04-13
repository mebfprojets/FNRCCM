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
 * Cette classe permet de vérifier la validité des mots de passe
 * saisis par les utilisateurs de la plateforme
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Validateur_Password extends Zend_Validate_Abstract
{

    const MAX      = 'longeur';
    const MAJ      = 'majuscule';
    const MIN      = 'miniscule';
    const NUM      = 'numerique';
    const SPECIAL  = 'special';
    
    /**
     * @var int
     * la longueur minimale du mot de passe
     */
    protected $_minLength  = 7;

    protected $_messageTemplates = array(
        self::MAX       => "Le mot de passe doit avoir au moins 7caractères ",
        self::MAJ       => "Le mot de passe doit contenir au moins une lettre majuscule",
        self::MIN       => "Le mot de passe doit contenir au moins une lettre minuscule",
        self::SPECIAL   => "Le mot de passe doit contenir au moins un  caractere special(*,#,@,$,etc...)",
        self::NUM       => "Le mot de passe doit contenir au moins un chiffre");

    
    /**
     * Cette méthode permet de vérifier la validité du mot de passe
     * 
     * @access public
     * @param   string | array  $value le mot de passe
     * @return  boolean vrai ou faux
     */
    public function isValid($value,$minLength=0)
    {
    	$password  = (is_array($value) && array_key_exists("password",$value)) ? $value["password"] : $value;   	
    	if(!is_string($password)){
    		return false;
    	}
        $this->_setValue($password);

        $isValid = true;
        
        if($minLength <= 0) {
        	$minLength  = $this->_minLength;
        }

        // Si la longueur du mot de passe est inférieure à 7, le mot de passe est invalide
        if (strlen($password) < intval($minLength)) {
            $this->_error(self::MAX);
            $isValid = false;
        }

        // Si le mot de passe ne contient pas au minimum une majuscule, il est invalide
        if (!preg_match('/[A-Z]/',$password)) {
            $this->_error(self::MAJ);
            $isValid = false;
        }
 
        // Si le mot de passe ne contient pas au minimum une minuscule, il est invalide
        if (!preg_match('/[a-z]/',$password)) {
            $this->_error(self::MIN);
            $isValid = false;
        }
        // Si le mot de passe ne contient pas au minimum  un chiffre, il est invalide
        if (!preg_match('/[0-9]/',$password)) {
            $this->_error(self::NUM);
            $isValid = false;
        }

        // Si le mot de passe ne contient pas au minimum  un caractère spécial, il est invalide
        $pattern="/[\s!()^+=#\]\[*~|$`?<>}&%{@\/]/";
        if(!preg_match($pattern,$password,$matches)){
            $this->_error(self::SPECIAL);
            $isValid = false;
        }
        return $isValid;
     }
     
     /**
      * Cette méthode permet de changer la longueur minimale du mot de passe
      *
      * @access  public
      * @param   integer  $length
      * @return  Sirah_Validateur_Password instance
      */
     public function setMinlength($length)
     {
     	$this->_minLength  = $length;
        return $this;
     }
     
     
     /**
      * Cette méthode permet de récupérer la longueur minimale du mot de passe
      *
      * @access  public
      * 
      * @return  integer
      */
     public function getMinlength()
     {
     	return $this->_minLength;
     }

}
