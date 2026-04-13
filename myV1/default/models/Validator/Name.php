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

class Validator_Name extends Zend_Validate_Abstract
{

  const TROP_LONG              = 'long';

  const TROP_COURT             = 'court';

  const CARACTER_INVALID       = 'invalid';

  public $minimum              = 4;

  public $maximum              = 100;
  
  protected $_invalidWords     = array("login","logout","carreers","carreer","formations","formation","stage","stages","job","jobs","search","searchs","emplois","sante","langue",
  		                               "language","languages","mariage","pute","sexe","putain","batard","merde","fuck","imbecile","sal","sale","prostitue","voleur","impoli","",
  		                               "null","false","java","javascript","myletter","mycv","import","customisation","ajaxres","dashboard","accounts","account","useroles","userights",
  		                               "false","system","profile","myprofile","mycontacts","offres","offre","useraccount","useraccounts","userights","useroles","documents","document",
  		                               "usernotifications","page","pages","entreprise","entreprises","ligne","lignes","domaine","domaines","recruiters","recruiter","project","projects",
  		                               "professions","profession","certifications","certification","key","keyword","knowledge","keywords","knowledges","contrats","contrat","contratypes",
  		                               "categories","categorie","candidature","candidatures","cron","myprofiles","salaud","impoli","guest","guests","invites","clients","client","invite",
  		                               "server","science","mauvais","user","users","username","lastname","nom","prenom","noms","prenoms","link","liaison","drogue","anonyme","anonymes");
  protected $_messageVariables = array(
                                        'min'                  => 'minimum',
                                        'max'                  => 'maximum',
                                        'invalid'              => '_invalid_caracter');
  
  protected $_invalid_caracter = "";
  protected $_messageTemplates = array(      
                                       self::TROP_LONG        =>  "Votre nom '%value%' est trop long, il ne doit pas depasser '%max%'  caracteres ",
                                       self::TROP_COURT       =>  "Votre nom '%value%' est trop court, il doit contenir au minimum '%min%' caracteres ",
                                       self::CARACTER_INVALID =>  "Votre nom '%value%'  contient un caractere invalide : '%invalid%',les caracteres speciaux sont interdits ");


   /**
    * Cette méthode permet de vérifier la validité 
    * d'un nom
    * 
    * @access public
    * @param   string le nom de la personne
    * @return  boolean vrai ou faux
    */
    public function isValid( $value )
    {    	 
    	if( !is_string( $value ) ) {
    		 return false;
    	}
        $this->_setValue( $value );
        if( strlen( $value ) < $this->minimum ) {
            $this->_error(self::TROP_COURT);
            return false;
        }
       if ( strlen( $value ) > $this->maximum ) {
            $this->_error(self::TROP_LONG);
            return false;
       }
       if( in_array( strtolower( $value ) , $this->_invalidWords ) ) {
       	   $this->_error( self::CARACTER_INVALID );
       	   return false;
       }
      $pattern="/([\s!()^+=#\]\[*~|$`?<>}&%{\/])/";
      if( preg_match_all( $pattern , $value , $matches)){
          $this->_invalid_caracter  =  $matches[1];
          $this->_error(self::CARACTER_INVALID);
          return false; 
       }
      return true;
    }



}
