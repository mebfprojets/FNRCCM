<?php
if(!defined("SIRAH_TABLE_PREFIX"))
     define("SIRAH_TABLE_PREFIX","sirah");

/**
 * Ce fichier est un composant de la librairie SIRAH
 * Il permet de controller un utilisateur depuis son authentification à l'acces de son compte
 *
 * Banao Hamed <banaohamed@yahoo.fr>
 * @copyright   Copyright (C) 2012 - 2015 Open Source Matters, Inc. All rights reserved.
 */


/**
 * User Class. Classe de controle de l'objet utilisateur dans une application orientée web
 *
 * @category Sira_User_Abstract
 * @package  User
 */

abstract Sirah_User_Abstract
{
    /**
     * IS_ADMIN
     */
     
    const IS_ADMIN               = 2;
     
    /**
     * ETAT CONNECTE
     */
    const LOGGEDIN               = 1;
    
    /**
     * ETAT DECONNECTE
     */
    const LOGGEDOUT              = 0;
    
    /**
     * ETAT INACTIF
     */
    const INACTIVE               = -1;
    
    /**
     * ETAT BLOCKED
     */
    const BLOCKED                = -2;
    
    /**
     * ETAT ECHEC AUTHENTICATION
     */
    const AUTHENTICATION_FAILED  = -3;
   
   /**
     * ETAT UTILISATEUR INCONNU
     */
    const UNKNOWN_USER           = -4;    
    
   /**
     * ETAT COMPTE EXPIRE
     */
    const EXPIRED                = -5;  
    
   /**
     * ETAT INVITE
     */
    const IS_GUEST               = -6;      

  /**
   * Un tableau des instances de la classe Sirah_User
   * @var array  Sira_User instances container.
   */
  protected static $_instances     = null;
  
  /**
   * Les options de configuration de l'instance
   * @var array
   */
   
  protected $_options              = array("table"           =>  array("name" => "users",   "prefix"    => SIRAH_TABLE_PREFIX,
                                                                       "dsn"  => null   ,   "primary"   => array("userid")),
                                           "authentication"  =>  array("type" => "dbTable", "userTable" => null,
                                                                       "secured"          => true, "security_check"   => "browser",
                                                                       "passwordHashSalt" => null, "passwordHashAlgo" => "sha1",
                                           		                       "rememberMe"       => true,
                                                                       "storage"          => array("type"             => "session",
                                                                                                   "expirationTime"   => 3600,    
                                                                                                   "idleTime"         => 2700)),
                                           "acl"             => null,
                                           "helper"          => null,
                                           "log"             => null);
  
  /**
   * La Data Gateway pour acceder 
   * aux informations de l'utilisateur depuis la BDD
   *
   * @var Sira_User_Table_Interface
   */
  protected $_table                = null;
 
  /**
   * L'objet Acl pour l'autorisation d'acces
   *
   * @var Sira_User_Acl_Interface
   */
  protected $_acl                 = null;

  /**
   * L'objet d'authentification
   * 
   * @var Sira_User_Auth_Interface
   */
  protected $_auth                = null;

 /**
  * Aide Utilisateur
  *
  * @var Sira_User_Helper
  */
  protected $_helper              = null;

 /**
  * Objet journal
  *
  * @var Sira_User_Log
  */
  protected $_log                 = null;

  /**
   * Les messages d'erreurs
   *
   * @var array
   */
  protected $_messages            = array( "error"    =>  array(),
                                           "success"  =>  array(),
                                           "info"     =>  array(),
                                           "warning"  =>  array());

  /**
   * Conteneur des objets des groupes associés
   * 
   * @var array 
   */
  protected $_groups              = null;

  /**
   * Les attributs de l'utilisateur
   *
   * @var array
   */
  protected $_attributes          = array("userid"          => 0,
  		                                  "userkey"         => null,
                                          "name"            => "anonyme",
                                          "username"        => null,
                                          "password"        => null,
                                          "password_clair"  => null,
                                          "password_salt"   => null,
                                          "email"           => null,
                                          "sexe"            => null,
                                          "activated"       => null,
                                          "blocked"         => null,
                                          "registerDate"    => null,
                                          "lastVisiteDate"  => null,
                                          "lastUpdatedDate" => null,
                                          "lastIpAddress"   => null,
                                          "lastHttpBrowser" => null);
  /**
   * Conteneur des filtres de données
   *
   * @var array
   */
  protected $_filters             = array();

  /**
   * Conteneur des validateurs de données
   *
   * @var array
   */
  protected $_validators          = array();
  
 /**
  * Le constructeur de la classe
  * @param int User ID value
  * @param array $options Sirah_User options
  * @return void
  */
  public function __construct($userid=0,$config=array())
  {
    if($params instanceof Zend_Config){
       $params   =  $params->toArray();
          }
    $this->setOptions($config);
      if($userid){
            $this->_loadData($userid);
          }
       } 
 
   /**
    * Cette méthode permet de mettre à jour les paramètres
    * @access public
    * @param array
    * @return Sirah_User instance
    */ 
  function setOptions($options=array())
  {  
    if(isset($options["table"])){
       $this->setTable(new Sirah_User_Table($options["table"]));
              } 
    if(array_key_exists("authentication",$options)){
      $this->setAuth(new Sirah_User_Auth($options["authentication"]));
           }
    if(array_key_exists("acl",$options)){
      $this->setAcl($options["acl"]);
           }
     if(array_key_exists("helper",$options)){
       $this->setHelper($options["helper"]);
         }
     else{
       $this->_helper   = new Sirah_User_Helper();
          }
     array_merge_recursive($this->_options,$options);
     return $this;
       }
 
 
   /**
    * Cette méthode permet d'accéder aux options de configuration de l'instance
    * @access public
    * @return
    */
    
   function getOptions()
   { 
        return $this->_options;  
       }
 
   /**
    * Cette méthode permet de récupérer les informations de l'utilisateur depuis une base de données
    * @access protected
    * @param integer identifiant de l'utilisateur
    * @return bool true or false
    */
   protected function _loadData($userid)
   {
      if(!$userid){
                 return;
            }
      if(!$this->_table || !$this->_table instanceof Sirah_User_Table_Interface){
           throw new Sirah_User_Exception("Impossible de charger les données de l'utilisateur car vous n'avez pas fourni un objet de table valide");
           }
      if(!empty($data = $this->_table->getData($userid))){
             $this->_attributes  = $data;
        return true;
           }
         return false;
       }
  

 /**
  * Le singleton qui permet de créer une seule instance de la classe
  * @access static
  * @param integer ou string designant l'identifiant de l'utilisateur
  * @return Sirah_User Object
  */ 
 public static function getInstance($identifiant=0,$config=array())
 {
    $userid      = (int)$identifiant;
    $instances   = self::$_instances;

    if(!is_numeric($identifiant) && array_key_exists("table",$config)){
        $userTable  = $config["table"];
      if($userTable instanceof Sirah_User_Table_Interface) {
          if(!$userid = $userTable->getUserIdBy($identifiant,array("username","email"))) {
               throw new Sirah_User_Exception(sprintf("L'utilisateur #id: %s est introuvable dans le système",$identifiant));
               return;
             }
           }
      if(!isset($instances[$userid])){
          $instances[$userid]   = new self($userid,$config);
         }
       return $instances[$userid];
    }

  /**
   * Retourne la table de stockage des données
   * @access public
   * @param void
   * @return Sirah_User_Table_Interface
   */  
  public function getTable()
  {
    if(null==$this->_table){
          $this->_table    = new Sirah_User_Table();
      }
     return $this->_table;
     }


   /**
   * Modifie la table de stockage des données
   * @access public
   * @param Sirah_User_Table_Interface
   * @return Sirah_User_Abstract : L'instance actuelle
   */
  public function setTable(Sirah_User_Table_Interface $table)
  {
     $this->_table        = $table;
     return $this;
      }


   /**
   * Retourne l'objet d'authentification de User
   * @access public
   * @param void
   * @return Sirah_User_Auth_Interface
   */
  
  public function getAuth()
  {
    if(null==$this->_auth){
            $this->_auth    = Sirah_User_Auth::getInstance();
         }
     return $this->_auth;
      }


   /**
   * Modifie l'objet d'authentification de User
   * @access public
   * @param Sirah_User_Auth_Interface
   * @return Sirah_User_Abstract : L'instance actuelle
   */
  public function setAuth(Sirah_User_Auth_Interface $auth)
  {
     if($auth instanceof Sirah_User_Auth_Interface){     
            $this->_auth        = $auth;
        }
       return $this;
     }

   
  /**
   * Retourne l'objet d'acl de User
   * @access public
   * @param void
   * @return Sirah_User_Auth_Interface
   */  
  public function getAcl()
  {
    if(null!==$this->_acl){
            $this->_acl    = Sirah_User_Acl::getInstance();
         }
     return $this->_acl;
      }


   /**
   * Modifie l'objet d'acl de User
   * @access public
   * @param Sirah_User_Acl_Interface
   * @return Sirah_User_Abstract : L'instance actuelle
   */
  public function setAcl(Sirah_User_Acl_Interface $acl)
  {
     if($acl instanceof Sirah_User_Acl_Interface){
         $this->_acl        = $acl;
       }
     return $this;
      }


  /**
   * Retourne l'objet d'aide de User
   * @access public
   * @param void
   * @return Sirah_User_Helper Object
   */  
  public function getHelper()
  {
    if(null!==$this->_helper){
            $this->_helper    = new Sirah_User_Helper;
         }
     return $this->_helper;
      }


  /**
   * Modifie l'objet d'authentification de User
   * @access public
   * @param Object
   * @return Sirah_User_Abstract : L'instance actuelle
   */
  public function setHelper($helper)
  {
     $this->_helper        = $helper;
     return $this;
      }


  /**
   * Permet de recuperer le code du statut actuel de l'utilisateur
   * @access public
   * @return interger
   */
  public function getStatus()
  {
       return $this->_status;
    }


  /**
   * Permet de mettre à jour le code du statut actuel
   * @access public
   * @param interger 
   * @return Sirah_User_Abstract : L'instance actuelle
   */
  public function setStatus($code=0)
  {
      $this->_status  = $code;
      return $this;
    }

  
  /**
   * Vérifie s'il est connecté ou non à son compte
   * @access public
   * @return bool
   */
   public function isLoggedIn()
   {
     return ($this->getStatus()==self::LOGGEDIN); 
        }
        
  /**
   * Vérifie si l'utilisateur est inactif
   * @access public
   * @return bool
   */
   
   public function isInactive()
   {
     return ($this->getStatus()==self::INACTIVE); 
        }
        
   /**
    * Vérifie si l'utilisateur est bloqué
    * @access public
    * @return bool
    */
   
   public function isBlocked()
   {
     return ($this->getStatus()==self::BLOCKED); 
        }
        
   /**
    * Vérifie si l'utilisateur est un invité
    * @access public
    * @return bool
    */
   
   public function isGuest()
   {
     return ($this->getStatus()==self::GUEST || $this->id<=0); 
        }
        
  /**
    * Vérifie si l'utilisateur est un administrateur
    * @access public
    * @return bool
    */
   
   public function isAdmin()
   {
     return ($this->getStatus()==self::IS_ADMIN); 
        }
        
  /**
   * Permet de recuperer les données de l'utilisateur sous forme de tableau
   * @access public
   * @param void
   * @return array
   */
   
   function getData()
   {
     return $this->_attributes;
        }
        
   /**
    *
    *
    */
  function getMessages($type=null)
  {
     $type   = strtolower($type);
     if(isset($this->_messages[$type])){
         return $this->_messages[$type];
        }
      return array();
      }
      
      
  /**
   *
   *
   */
   
  function addMessage($message,$type="error")
  {   
     $type   = strtolower($type);
     if(array_key_exists($type,$this->_messages)) {
       $this->_messages[$type][] = $message;
         }
      return $this;
      }

  /**
   * Permet de verifier les données de l'utilisateur
   * @access public
   * @param array
   * @return bool vrai si la vérification est validée
   */
   public function checkData($data=array())
   {
       if(count($this->_validators) && !empty($data)){
          foreach($this->_validators as $validator){
            if(method_exists($validator,"isValid") && method_exists($validator,"getMessage")){
               if(!$validator->isValid($data)){
                   $this->addMessage($validator->getMessage(),"warning");
                   return false;
               }
             }
           }
         }         
         return true;
      }
      
  /**
   * Permet de filtrer les données de l'utilisateur
   * @access public
   * @param array
   * @return array les données après le filtrage
   */
   public function filterData($data=array())
   {
       $dataFiltered   = $data;
       if(count($this->_filters) && !empty($data)){
          foreach($this->_filters as $filter){
            if(method_exists($filter,"filter")){
               foreach($data as $key=>$val){
                 $dataFiltered[$key] =  $filter->filter($val);
               }
             }
           }
         }         
         return $dataFiltered;
      }


 /**
  * Permet d'ajouter des filtres de données à l'instance
  * @access public
  * @param filter instance
  * @return Sirah_User instance
  */
  public function addFilter($filter,$name=null)
  {
    if(!empty($name) && null!==$name){
     $this->_filters[$name]  = $filter;
        }
     else
        $this->_filters[]       = $filter;

     return $this;
      }
      
 /**
  * Permet d'ajouter des validateurs de données à l'instance
  * @access public
  * @param validator instance
  * @return Sirah_User instance
  */
  public function addValidator($validator,$name=null)
  {
    if(!empty($name) && null!==$name){
     $this->_validators[$name]  = $validator;
        }
     else
        $this->_validators[]  = $validator;
     return $this;
      }
      
   /**
  * Permet de supprimer un filtre de données
  * @access public
  * @param string key of filter
  * @return Sirah_User instance
  */
  public function removeFilter($name)
  {
   if(array_key_exists($name,$this->_filters)){
      unset($this->_filters[$name]);
     }
     return $this;
      }
      
 /**
  * Permet de supprimer un validateur de la liste
  * @access public
  * @param string key of validator
  * @return Sirah_User instance
  */
  public function removeValidator($name)
  {
    if(array_key_exists($name,$this->_validators)){
      unset($this->_validators[$name]);
        }
     return $this;
     }
  


  /**
   * Permet d'assigner des données à l'utilisateur actuel
   * @access public
   * @param array
   * @return instance actuelle
   */
   public function setFromArray(&$data,$ignore=array)
   {
        $userData = array_intersect_key($data, $this->_attributes);          
        foreach ($userData as $key => $val) {
         if(!in_array($key,$ignore)){
            $this->_attributes[$key]  = $val;
            }
         }
       return $this;      
      }


  /**
   * Permet de mettre à jour les données de l'utilisateur
   * @access public
   * @param array
   * @return instance actuelle
   */
   public function save($data=array())
   {       
       if(!$this->checkData($data)){
               return;
            }
       $filteredData = (!empty($data))?$this->filterData($data):$this->getData();
       $userTable    = $this->getTable();
       $this->setFromArray($filteredData);
       
       if(!$userTable->setFromArray($this->getData())){
       	  $this->addMessage($userTable->getError(),"error");
       	  return;
          }
       return $userTable->save();
       }
       
       
  abstract function login($username,$password)
  
  abstract function logout($username,$password)


  /**
   * Methode magique, l'accesseur
   * @access public
   * On recupere les membres de la class avec la propriété  attributes
   * @param mixed
   * @return mixed 
   */
   public function __get($attribute)
   {
     if(isset($this->_attributes[$attribute])){
             return $this->_attributes[$attribute];
          }
     throw new Sirah_User_Exception(sprintf("L'attribut %s est inexistant dans le composant des utilisateurs",$attribute));
       }
  /**
   * Methode magique, le modificateur
   * @access public
   * @param mixed
   * @param mixed 
   * @return mixed 
   */
   public function __set($attribute,$value)
   {
     if(isset($this->_attributes[$attribute])){
            $this->_attributes[$attribute]  = $value;
            $this->_table->{$attribute}     = $value;
          }
    throw new Sirah_User_Exception(sprintf("L'attribut %s est inexistant dans le composant des utilisateurs",$attribute));
       }

  

   }
