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
 * Cette classe permet de gérer les 
 * résultats d'authentification
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
class Sirah_User_Auth_Result extends Zend_Auth_Result
{

	/**
	 * Echec d'authentification car la session est expirée
	 */
	const FAILURE_AUTH_EXPIRED      = -5;
	
	
	/**
	 * Echec d'authentification 
	 * car le compte de l'utilisateur
	 * est désactivé
	 *
	 */
	const FAILURE_DISABLED         = -6;

	/**
	 * Echec d'authentification car 
	 * le compte de l'utilisateur
	 * est bloqué
	 *
	 */
    const FAILURE_BLOCKED          = -7;
    
    
    /**
     * Echec d'authentification car 
     * le compte de l'utilisateur
     * est verrouillé
     *
     */
    const FAILURE_LOCKED           = -8;
    
    
    /**
     * Echec d'authentification car
     * le compte de l'utilisateur
     * est peut etre volé
     *
     */
    const FAILURE_SECURITY_BREACH  = -9;
    
    
    /**
     * Echec d'authentification compte expiré
     */
    const FAILURE_ACCOUNT_EXPIRED  = -10;
	
 


}

