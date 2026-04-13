<?php
/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basee sur les composants de la
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
 * Cette classe permet de gerer les comptes des utilisateurs
 * d'appliquer des traitements sur les informations
 * caracteristiques des comptes des utilisateurs
 * de la plateforme basée sur SIRAH
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_User
{
	/**
	 * IS_ADMIN
	 * @var integer
	 */	 
	const IS_ADMIN               = 2;
	 
	/**
	 * ETAT CONNECTE
	 * @var integer
	 */
	const LOGGEDIN               = 1;
	
	/**
	 * ETAT DECONNECTE
	 * @var integer
	 */
	const LOGGEDOUT              = 0;
	
	/**
	 * ETAT INACTIF
	 * @var integer
	 */
	const DISABLED               = -1;
	
	/**
	 * ETAT VERROUILLE
	 * @var integer
	 */
	const LOCKED                 = -2;	
	 
	/**
	 * ETAT UTILISATEUR INCONNU
	 * @var integer
	 */
	const UNKNOWN_USER           = -3;
	
	/**
	 * ETAT COMPTE EXPIRE
	 * @var integer
	 */
	const EXPIRED                = -4;
	
	/**
	 * ETAT INVITE
	 * @var integer
	 */
	const IS_GUEST               = -5;
	
	/**
	 * LOG ACTIVe
	 */
	public $logEnabled           = true;
	
	/**
	 * Un tableau des instances de la classe Sirah_User
	 * @var array  Sira_User instances container.
	 */
	protected static $_instances     = array();
	
	/**
	 * Les options de configuration de l'instance
	 * @var array
	 */
	 
	protected static $_options       = array("table"          =>  array("name"            => "system_users_account",         
			                                                            "dbAdapter"       => null,   
			                                                            "primary"         => array("userid"), 
			                                                            "userid"          => 0),
			                                 "authentication" => array("identityColumn"   => "username",
			                                 		                   "credentialColumn" => "password",
			                                 		                   "expireTime"       => 864000,
			                                 		                   "rememberMe"       => 864000,
			                                 		                   "secured"          => true,
			                                 		                   "securityChecks"   => array("ipaddress", "expiretime", "usertoken")));
	
	/**
	 * La Data Gateway pour acceder
	 * aux informations de l'utilisateur depuis la BDD
	 *
	 * @var Sirah_User_Table_Interface
	 */
	protected $_table                = null;
	
	
	/**
	 * Aide Utilisateur
	 *
	 * @var Sirah_User_Helper
	 */
	protected $_helper              = null;
	
	
	/**
	 * L'instance de l'objet d'authentification
	 *
	 * @var Sirah_User_Auth
	 */
	protected $_auth              = null;
	
	
	/**
	 * Le statut
	 *
	 * @var integer
	 */
	protected $_status             = -5;
	
	
	/**
	 * Les messages d'erreurs
	 *
	 * @var array
	 */
	protected $_messages            = array( "error"   =>  array(),
			                                 "success" =>  array(),
			                                 "info"    =>  array(),
			                                 "warning" =>  array());
	
	
	/**
	 * Les attributs de l'utilisateur
	 *
	 * @var array
	 */
	protected $_attributes    = array(
			                           "userid"             => 0,
                                       "localiteid"         => 0,
			                           "firstname"          => "anonyme",
			                           "lastname"           => "anonyme",
			                           "phone1"             => "",
			                           "phone2"             => "",
			                           "address"            => "",
			                           "zipaddress"         => "",
			                           "city"               => "",
			                           "country"            => "",
			                           "language"           => "FR",
			                           "facebookid"         => "",
			                           "skypeid"            => "",
			                           "username"           => "guest",
			                           "password"           => null,
			                           "password_clair"     => null,
			                           "password_salt"      => null,
			                           "email"              => null,
			                           "sexe"               => null,
			                           "avatar"             => null,
			                           "expired"            => 0,
			                           "activated"          => 1,
			                           "blocked"            => 0,
			                           "locked"             => 0,
			                           "connected"          => 0,
			                           "admin"              => 0,
			                           "statut"             => 0,
			                           "nb_connections"     => 0,
			                           "accesstoken"        => null,
			                           "logintoken"         => null,
			                           "params"             => "{sendmail:0;changepassword:0;receivemail:0;updatenotification:0;remember_me:0;creationotification:0;systemnotification:0;connexionotification:0;newsletter:1;contentvisibility:0;contactvisibility:0;profilevisibility:0,
                                                                  security_client_check : 0,security_ipaddress_check:0}",
			                           "creatoruserid"      => 0,
			                           "updateduserid"      => 0,
			                           "registeredDate"     => 0,
			                           "lastConnectedDate"  => 0,
			                           "lastUpdatedDate"    => 0,
			                           "lastIpAddress"      => null,
			                           "lastHttpClient"     => null,
	                                   "lastSessionId"      => null);
	/**
	 * Conteneur des filtres de donnees
	 *
	 * @var array
	 */
	protected $_filters             = array();
	
	/**
	 * Conteneur des validateurs de donnees
	 *
	 * @var array
	 */
	protected $_validators          = array();
	
	/**
	 * Instance of Sirah_User_Plugin_Broker
	 * @var Sirah_User_Plugin_Broker
	 */
	
	protected $_plugins             = null;
	
	/**
	 * Le role de l'utilisateur
	 * @var string
	 */
	protected $_role                = null;
	
	
	/**
	 * Le chemin du dossier des documents de l'utilisateur
	 * @var string
	 */
	protected $_dataPath           = null;

	
	/**
	 * L'instance de l'objet conteneur Cookie de stockage des données de l'utilisateur
	 * @var Zend_Http_CookieJar
	 */
	protected $_cookie             = null;
	
	
	protected $_request            = null;
	
	
	protected $_response           = null;
	    
    /**
     * Le constructeur de la classe
     * @param integer $userid L'identifiant de l'utilisateur
     * @param array   $options Les paramàtres de creation de l'instance
     * @return void
     */
    public function __construct($userid=0,$params=array())
    {
    	if( $params instanceof Zend_Config){
    		$params   =  $params->toArray();
    	}
    	//On initialise les paramètres de création 
    	$params["table"]["userid"]   = $userid;
    	$this->setOptions($params);
    	//On charge les données si ce n'est pas un invité
    	if( $userid ){
    		$this->_attributes["userid"] = $userid;
    		$this->_loadData($userid);
    		$this->setStatus();
    	}
    	$this->_plugins = new Sirah_User_Plugin_Broker();
    	$this->_plugins->setUser($this);
     }
    
    /**
     * Interdit de faire un clone de l'objet
     * parceque c'est un singleton
     * 
     */
    private function __clone(){}
    
    /**
     * Cette methode permet de mettre à jour les options de création de
     * l'instance de la classe
     * @access public
     * @param   array  $options  
     * @return  Sirah_User instance
     */
    function setOptions($options=array())
    {
    	if(isset($options["table"])){
    		if(!class_exists("Sirah_User_Table")){
    		   require_once 'Sirah/User/Table.php';
    		}
    	  $this->setTable(Sirah_User_Table::getInstance($options["table"]["userid"],$options["table"]));
    	} 
    	if( isset($options["authentication"] ) ) {
    		if(!class_exists("Sirah_User_Auth")){
    			require_once 'Sirah/User/Auth.php';
    		}
    	  $this->setAuth(Sirah_User_Auth::getInstance($options["authentication"]));
    	}   	
    	array_merge_recursive(self::$_options,$options);
    	return $this;
    }
    
    /**
     * Cette methode permet de recuperer le chemin du dossier des documents
     * de l'utilisateur
     * @access public
     * @return string or null
     */
    
    public function getDatapath()
    {
    	$dataPathname     = $this->_dataPath;
    	if( empty( $this->_dataPath ) || ( null == $this->_dataPath ) ) {
    		$dataPathname = APPLICATION_DATA_USER_PATH . Sirah_Filesystem::safeFilename( Sirah_Filesystem::cleanPath ( $this->username ) ) . DS;
    		if(!is_dir($dataPathname) && is_dir(APPLICATION_DATA_USER_PATH)) {
    			if(!Sirah_Filesystem::mkdir( $dataPathname ) ) {
    				$dataPathname  = APPLICATION_DATA_USER_PATH;
    			}
    		}
    	}    	
    	return $dataPathname;    	
    }
    
    /**
     * Cette methode permet de mettre à jour le chemin du dossier des documents
     *
     * @access public
     * @param string le chemin du dossier de l'utilisateur
     * @return string or null
     */
    public function setDatapath( $userDataPathname )
    {
    	if( is_dir( $userDataPathname ) ) {
    		$this->_dataPath  = $userDataPathname;
    	}
    	return $this; 
    }       
    
    /**
     * Cette methode permet de recuperer les informations de l'utilisateur depuis une base de donnees
     * 
     * @access protected
     * @return bool true or false
     * @throws Sirah_User_Exception
     */
    protected function _loadData($userid)
    {
    	$userid = intval($userid);
    	if(!$userid){
    		return false;
    	}
    	$table                  = $this->getTable($userid);    	
    	if( null===$table ){
    		throw new Sirah_User_Exception("Le chargement des données de l'utilisateur a echoué, l'accès aux données est impossible");
    	}
    	$data = $table->getData(); 
    	if(!empty($data)){
    		$this->_attributes  = array_intersect_key( $data , $this->_attributes );
    	} else {
    		throw new Sirah_User_Exception("Le chargement des données de l'utilisateur id#$userid a echoué, paramètres invalides");
    	}
    }
    
    
    /**
     * Le singleton qui permet de creer une seule instance de la classe
     * @access static
     * @param  integer ou string designant l'identifiant de l'utilisateur (son nom d'utilisateur ou son email)
     * @return Sirah_User Object
     */
    public static function getInstance( $identifiant = 0 , $config = array() )
    {
    	$config      = array_merge_recursive( self::$_options , $config );    
    	if(null != $identifiant && !is_numeric($identifiant) && array_key_exists("table" , $config)){
    		$userTable = (isset($config["table"]))  ? Sirah_User_Table::getInstance(0 , $config["table"]) : null;    		
    		if( $userTable instanceof Sirah_User_Table_Interface ) {
    			if(!$userid = $userTable->getUserIdBy( $identifiant , array("username" , "email"))) {
    				throw new Sirah_User_Exception(sprintf("L'utilisateur #id: %s est introuvable dans le systeme" , $identifiant));
    				return;
    			} 
    		} 
        }  else {
        	$userid   = intval($identifiant);
        } 
    	if(!isset(self::$_instances[$userid])){
    	    self::$_instances[$userid]   = new Sirah_User( $userid , $config );
    	}
    	return self::$_instances[$userid];
    }
    
    /**
     * Retourne la table de stockage des donnees
     * @access public
     * @param  integer $userid l'identifiant numérique de l'utilisateur
     * @return Sirah_User_Table_Interface
     */
    public function getTable($userid=0)
    {
        if(null===$this->_table){
      	    $userid          = (intval($userid)) ? intval($userid) : $this->userid;
    		$this->_table    = Sirah_User_Table::getInstance($userid , array("name"=> "system_users_account", "primary" => "userid"));
    	}
      return $this->_table;
    }
    
    /**
     * Retourne l'instance de l'objet d'authentification de l'utilisateur
     * @access public
     * 
     * @return  Sirah_User_Auth
     */
    public function getAuth()
    {
    	if( null === $this->_auth ){
    		$this->_auth   = Sirah_User_Auth::getInstance(array("identityColumn" => "username","credentialColumn" => "password",
			                                 		            "secured"        => true,
			                                 		            "securityChecks" => array("ipaddress" , "expiretime" , "token")));
    	}
    	return $this->_auth;
    }
    
    /**
     * Met à jour l'objet d'authentification de l'utilisateur
     * 
     * @access public
     * 
     * @param  Sirah_User_Auth
     * 
     * @return Sirah_User_Abstract : L'instance actuelle
     */
     public function setAuth(Sirah_User_Auth $auth)
     {
    	$this->_auth        = $auth;
    	return $this;
     }  

     
     /**
      * Retourne l'instance de l'objet de traitement du cookie de stockage des infos de l'identité
      * @access public
      *
      * @return  Zend_Http_Cookie
      */
     public function getCookie()
     {
     	
     }
    
    /**
     * Permet de recuperer toutes les preferences de l'utilisateur
     * @access public
     * @param void
     * @return  array  un tableau contenant les differents paramàtres associes à l'utilisateur
     */
    public function getParams()
    {
    	$stringParams  = preg_replace( "/[\s]*/", "" , trim( $this->_attributes["params"] ) );
    	$stringParams  = trim(preg_replace("/^\{(.*)\}$/" , "$1" , $stringParams),"~");
    	$arrayParams   = explode("~", $stringParams);
    	$defaultParams = array("sendmail"                          => 1,
    			               "change_password_email"             => $this->get("email"),
    			               "change_password_security"          => 0,
    			               "receiveparams"                     => 0,
    			               "receivemail"                       => 0,
    			               "updatenotification"                => 0,
    			               "remember_me"                       => 0,
    			               "creationotification"               => 0,
    			               "systemnotification"                => 0,
    			               "connexionotification"              => 0,
    			               "receive_newsletter"                => 1,
    			               "security_client"                   => 0,
    			               "security_client_check"             => 0,
    			               "security_ipaddress"                => "",
    			               "security_ipaddress_check"          => 0,
    			               "receive_publication_notifications" => 1,
    			               "receive_comments_notifications"    => 0,
    			               "receive_alerts"                    => 1,
    			               "findme_from_email"                 => 0,
    			               "findme_from_phone"                 => 0,
    			               "findme_from_name"                  => 1,
    			               "allow_robots_index"                => 0,
    			               "profileaccesslevel"                => 0,
    			               "contentvisibility"                 => 0,
    			               "contactvisibility"                 => 0,
    			               "profilevisibility"                 => 0);
    	$params        = array();
    	if(!empty($arrayParams)){
    		foreach($arrayParams as $param){
    			$paramData  = explode("|" , $param );
    			if(isset($paramData[1])){
    				$params[$paramData[0]]  = $paramData[1];
    			} else {
    				$params[]               = $paramData[0];
    			}
    		}
    	}
    	$myParams  = array_merge( $defaultParams , $params )   	;
    	return $myParams ;
    }
    
    /**
     * Permet de recuperer un paramàtre à travers sa cle
     * @access  public
     * @param   void
     * @return  string la valeur du paramètre ou false si la cle du paramàtre n'existe pas
     */
    public function getParam( $paramKey , $defaultValue = false)
    {
       $params  = $this->getParams();
       if(isset( $params[$paramKey])){
       	  return $params[$paramKey];
       }
       return $defaultValue;
    }
	
	/**
     * Permet de savoir si un paramètre fait partie des options de préférences
     * @access  public
     * @param   void
     * @return  bool vrai si le paramètre existe et faux si le paramètre n'existe pas
     */
    public function hasParam( $paramKey )
    {
		$params = $this->getParams();
		return  isset($params[$paramKey]);              
    }
    
    /**
     * Permet de mettre à jour un paramàtre de l'utilisateur
     * @access  public
     * @param   string   $paramKey  le paramàtre à mettre à jour
     * @param   string   $paramVal  la valeur du paramàtre à jour
     * 
     */
    public function setParam( $paramKey , $paramVal )
    {
    	$params              = $this->getParams();
    	$params[$paramKey]   = $paramVal;
    	$this->setParams($params);
    }
    
    /**
     * Permet de mettre à jour toutes les preferences de l'utilisateur
     * @access public
     * @param   string | array  $params
     */
    public function setParams( $params , $save = true)
    {
    	 if(is_string($params) && !empty($params ) ) {
    	 	$this->_table->save(array("params" => $params));
    	 	$this->_attributes["params"] = $params;
    	 	return true;
    	 }   	 
    	if( is_array($params) && !empty($params)){
    		$paramString = "";
    		foreach($params as $paramEntryKey => $paramEntry){
    			if(is_int($paramEntryKey)){
    				$paramString   .=  $paramEntry."~";
    			} elseif (is_string($paramEntryKey)) {
    				$paramString   .=  $paramEntryKey."|".$paramEntry."~";
    			}
    		}
			if( false === $save ) {
				return true;
			}
    		$newParams  = $this->_attributes["params"] =  "{".$paramString."}";
    	    return $this->_table->save(array("params"  => $newParams));
    	}
    	return false;
    }
         
   /**
    * Modifie la table de stockage des donnees
    * @access public
    * @param Sirah_User_Table_Interface
    * @return Sirah_User_Abstract : L'instance actuelle
    */
    public function setTable($table)
    {
    	$this->_table        = $table;
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
     * Permet de mettre e jour le code du statut actuel
     * @access public
     * @param interger
     * @return Sirah_User : L'instance actuelle
     */
    public function setStatus($code=null)
    {
    	if(null===$code){
    		$table     = $this->getTable();
    		if( $table->locked ) {
    			$code  = self::LOCKED;
    		} elseif( $table->blocked ) {
    			$code  = self::BLOCKED;
    		} elseif( $table->expired ) {
    			$code  = self::EXPIRED;
    		} elseif(!$table->activated){
    			$code  = self::DISABLED;
    		} elseif($table->userid && $table->connected) {
    			$code  = self::LOGGEDIN;
    		} else {
    			$code  = self::IS_GUEST;
    		}    		
    		if($this->isAdmin()){
    		   $code   = self::IS_ADMIN;
    		}
    	}
    	$this->_status = $code;
    	return $this;
    }
    
    
    /**
     * Verifie s'il est logué à son compte.
     * 
     * @access public
     * @return bool
     */
    public function isLoggedIn()
    {
    	$userAuth = $this->getAuth();   	
    	if ( $userAuth->hasIdentity() ) {
    		 $this->setStatus( self::LOGGEDIN );
    		 return true;
    	}
    	return false;
    }
    
    /**
     * Verifie si le compte de l'utilisateur est inactif
     * @access public
     * @return bool
     */
    	 
    public function isDisabled()
    {
      return ($this->getStatus()==self::DISABLED || !intval($this->_attributes["activated"]));
    }
    
    /**
     * Verifie si le compte de l'utilisateur est verrouille
     * @access public
     * @return bool
     */
    	 
     public function isLocked()
     {
    	return ($this->getStatus()==self::LOCKED || intval($this->_attributes["locked"]));
     }
    	
    
    /**
     * Verifie si l'utilisateur est un invite
     * @access public
     * @return bool
     */    	 
    public function isGuest()
    {
    	return ( ( $this->getStatus()==self::IS_GUEST ) && ( $this->_attributes["userid"] <= 0 ) );
    }
    
    /**
     * Verifie si l'utilisateur est un administrateur
     * @access public
     * @return bool
     */
    	 
     public function isAdmin()
     {
     	if ( !$this->isLoggedIn() ) {
     		return false;
     	}
    	return ($this->getStatus()==self::IS_ADMIN || intval($this->_attributes["admin"]));
     }
     
     /**
      * Permet de recuperer un attribut de l'instance
      * 
      * @access public
      * @param  string     $key
      * @param  string     $default la valeur par défaut au cas ou l'attribut n'existe pas
      * @param  callable   $filter_func une fonction qui va agir sur la valeur avant de la retourner
      * @return array
      */
     
     function get($key , $default=null , $filter_func=null)
     {
     	if( isset( $this->_attributes[$key] ) ) {
     		$attributeVal  = $this->_attributes[$key];
     	} else {
     		$attributeVal  = $this->_attributes[$key] = $default;
     	}
     	if( is_callable( $filter_func ) ) {
     		return call_user_func( $filter_func , $attributeVal );
     	}
     	return $attributeVal;
     }
    
    /**
     * Permet de recuperer les donnees de l'utilisateur sous forme de tableau
     * @access public
     * @param void
     * @return array
     */
    	 
     function getData()
     {
    	return $this->_attributes;
     }
     
     /**
      * Permet de supprimer les messages d'ererur
      *
      */
     function clearMessages($type=null)
     {
     	$type   = strtolower($type);
     	if(null !== $type && isset($this->_messages[$type])){
     	   $this->_messages[$type] = array();
     	}
     	$this->_messages  = array( "error"    =>  array(),
			                       "success"  =>  array(),
			                       "info"     =>  array(),
			                       "warning"  =>  array());
     }
    
    /**
     * Permet de recuperer les messages d'ererur
     *
     */
    function getMessages($type=null)
    {
        $type   = strtolower($type);
        if(null !== $type && isset($this->_messages[$type])){
    	   return $this->_messages[$type];
        }
    	return $this->_messages;
    }
    
    /**
     * Permet d'inserer plusieurs messages d'erreurs
     * @access public
     * @param  array  $messages les messages d'erreurs
     * @param  string le type des messages
     */
    function addMessages($messages=array(),$type="error")
    {
    	if(!empty($messages)){
    		foreach($messages as $message){
    			$this->addMessage($message,$type);
    		}
    	}
    	return $this;
    }        
    
    
   /**
    * Permet d'inserer un message d'erreur
    * @access public
    * @param  string
    */    	 
    function addMessage($message,$type="error")
    {
        $type   = strtolower($type);
        if(array_key_exists( $type, $this->_messages)) { 
           $message  = (is_array($message)) ? array_shift($message)  : $message;
    	   $this->_messages[$type][] = $message;
    	}
    	return $this;
     }
     
     /**
      * Permet de recuperer un message d'ererur
      *
      */
     function getMessage($type="error")
     {
     	$type   = strtolower($type);
     	if(null !== $type && isset($this->_messages[$type])){
     		return array_shift($this->_messages[$type]);
     	}    	
     	return false;
     }
    
    /**
     * Permet de verifier les donnees de l'utilisateur
     * @access public
     * @param  array
     * @return bool vrai si la verification est validee
     */
    public function validateData($data=array())
    {
      if( count( $this->_validators ) && !empty( $data ) ) {
    	foreach( $this->_validators as $validator ) {
    		if( method_exists( $validator , "isValid" ) && method_exists( $validator , "getMessages" ) ){
    			if(!$validator->isValid($data)){
    				$error = implode(" ; ",$validator->getMessages());
    			    $this->addMessage($error,"error");
    			    return false;
    			}
    		}
    	}
      }
    		return true;
    }
    
    	/**
    	 * Permet de filtrer les donnees de l'utilisateur
    	 * @access public
    	 * @param array
    	 * @return array le tableau des donnees apres le filtrage
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
    	 * Permet d'ajouter des filtres de donnees e l'instance
    	 * @access public
    	 * @param filter instance
    	 * @return Sirah_User instance
    	 */
    	public function registerFilter($filter,$name=null)
    	{
    		if(!empty($name) && null!==$name){
    			$this->_filters[$name]  = $filter;
    		} else {
    			$this->_filters[]       = $filter;
    		}
    		return $this;
    	}
    
    	/**
    	 * Permet d'ajouter des validateurs de donnees e l'instance
    	 * @access public
    	 * @param validator instance
    	 * @return Sirah_User instance
    	 */
    	public function registerValidator($validator,$name=null)
    	{
    		if(!empty($name) && null!==$name){
    			$this->_validators[$name]  = $validator;
    		} else {
    			$this->_validators[]  = $validator;
    		}
    		return $this;
    	}
    
    	/**
    	 * Permet de supprimer un filtre de donnees
    	 * @access public
    	 * @param string key of filter
    	 * @return Sirah_User instance
    	 */
    	public function unregisterFilter($name)
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
    	public function unregisterValidator($name)
    	{
    		if(array_key_exists($name,$this->_validators)){
    			unset($this->_validators[$name]);
    		}
    		return $this;
    	}
       
    	/**
    	 * Permet d'assigner des donnees à l'utilisateur actuel
    	 * @access public
    	 * @param array
    	 * @return instance actuelle
    	 */
    	public function setFromArray($data , $ignore = array("password") )
    	{
	       $userData = array_intersect_key ($data, $this->_attributes );
	       foreach ( $userData as $key => $val ) {
		        if (!in_array( $key, $ignore)) {
			        $this->_attributes[$key] = $val;
		       }
	       }
	      return $this;
       }   	
    	
    	/**
    	 * Permet d'enregistrer un plugin dans le conteneur de plugins
    	 * @access public
    	 * @param string
    	 * @param  Sirah_User_Plugin_Abstract $plugin
    	 * @param  int plugin priority
    	 * @return instance actuelle
    	 */
    	public function registerPlugin(Sirah_User_Plugin_Abstract $plugin,$priority=null)
    	{
    		$this->_plugins->register($plugin,$priority);
    		return $this;
    	}
    	
    	/**
    	 * Permet de depiler un plugin du conteneur
    	 * @access public
    	 * @
    	 * @param mixed string|object
    	 * @return instance actuelle
    	 */
    	public function unregisterPlugin($plugin,$priority=null)
    	{
    		$this->_plugins->unregister($plugin,$priority);
    		return $this;
    	}
    
    
    	/**
    	 * Permet de mettre à jour les donnees de l'utilisateur
    	 * @access public
    	 * @param  array $data les           donnees à sauvegarder
    	 * @param  bool  $generate_password  determine si le mot de passe doit etre genere ou pas
    	 * @return bool true ou false en fonction du resultat
    	 *         de l'operation
    	 */
    	public function save( $data = array() , $generate_password = false )
    	{
    		$data  = array_intersect_key( $data , $this->_attributes );
    		if(!$this->validateData($data)){
    			$this->addMessage( " Les informations fournies pour l'enregistrement du compte sont invalides","error");
    			return false;
    		}
    		$filteredData = ( !empty( $data ) ) ? $this->filterData($data) : $this->getData();
    		if(!isset($filteredData["userid"])){
    			$filteredData["userid"] = $this->userid;
    		}
    		if(isset($filteredData["password"]) && !empty($filteredData["password"]) && (null!==$filteredData["password"])){
    			$salt                      = Sirah_Functions_Generator::getAlpha(12);
    			$filteredData["password"]  = Sirah_User_Helper::cryptPassword( $filteredData["password"] , $salt );
    		} elseif ( empty( $filteredData["password"] ) && $generate_password){
    			$salt                      = Sirah_Functions_Generator::getAlpha(12);
    			$filteredData["password"]  = Sirah_User_Helper::cryptPassword($this->_generatePassword() , $salt);
    		}  		
    		$userTable       = $this->getTable();    		 
    		if(!$userTable->setFromArray( $filteredData , "password")){
    			$tableError  = $userTable->getError();
    			$this->addMessage( $tableError , "error");
    			return false;
    		}
    		if(!$userTable->save( $filteredData , "password")){
    			$tableError  = $userTable->getError();
    			$this->addMessage( $tableError ,"error");
    			return false;
    		}
    		$this->_attributes  =  $userTable->getData();
    		return $userTable->getData("userid");
    	}    	 
      
       /**
    	* Methode magique, l'accesseur
    	* @access public
    	* On recupere les membres de la classe avec la propriete  attributes
    	* @param string attribute name
    	* @return mixed attribute value
    	*/
    	public function __get( $attribute )
    	{
    		if( array_key_exists($attribute , $this->_attributes ) ){
    			return $this->_attributes[$attribute];
    		} 		
    	}
    	
    	
    	/**
    	 * Methode magique, le modificateur
    	 * @access public
    	 * @param string attribute name
    	 * @param mixed  attribute value
    	 * @return void
    	 */
    	public function __set($attribute,$value)
    	{
    	   if(!array_key_exists( $attribute , $this->_attributes )){
    		   throw new Sirah_User_Exception(sprintf("L'attribut %s est inexistant dans les proprietes de l'utilisateur" , $attribute ) );
    		}
    	   $this->_attributes[$attribute]  = $value;
    	}
        	
    /**
     * Methode magique, l'accesseur aux methodes de la classe.
     * Elle ecrase la methode magique __call par defaut
     * @access public
     * @param string attribute name
     * @param mixed  attribute value
     * @return void
     */
    public function __call( $method , $args )
    {
      $method   = trim($method);
       if ( preg_match('#^on|^before|^after#i', $method, $matches ) ) {
    	  return $this->_plugins->$method($args);
       } 
       if( preg_match("/^is([a-zA-Z]+)$/i" , $method , $rolesFound ) ) {
       	   return ( $this->getRole() == $rolesFound[1] );
       }
       throw new Sirah_User_Exception(sprintf("Méthode Sirah_User::%s inconnue " , $method));
    }
  
  /**
   * La methode permettant de se connecter à son compte
   * 
   * @access public
   * @param  string le nom d'utilisateur du compte ou l'adresse email
   * @param  string le mot de passe du compte
   * @param  bool  permet de se rappeler du client
   * @return mixed false | Sirah_User instance
   */
   public function login( $username, $password, $rememberMe=true , $token = null , $params = array())
   {
   	 /*** On execute les evenements avant le login ***/
   	 $this->_plugins->beforeLogin($this, array("identity"=>$username, "credential"=> $password,"params"=>$params ));

   	 //On récupère l'objet d'authentification
   	 $userAuth         = $this->getAuth();
   	 $userAuthAdapter  = $userAuth->getAdapter();
   	 $loggedInUser     = null;
   	 $token            = (null===$token || empty($token))? Sirah_User_Helper::getToken(10) : $token;
   	 $emailValidator   = new Sirah_Validateur_Email();
   	 if( $emailValidator->isValid($username) ) {
   	 	 $userAuthAdapter->setIdentityColumn("email" );
   	 }   	   	 
   	 //On initialise les paramètres de l'instance d'authentification
   	 $userAuthAdapter->setIdentity(   $username );
   	 $userAuthAdapter->setCredential( $password );  	 
   	 $userAuth->setAdapter($userAuthAdapter);
   	 
   	 //Executer les plugins avant l'authentification
   	 $this->_plugins->beforeAuth($this, array("authInstance"=> $userAuth, "identity"=> $username,"credential" => $password ,  "params" => $params ));
   	 
   	 //On récupère le resultat de l'authentification
   	 $resultat  =   $userAuth->authenticate();
   	 
   	 //Executer les plugins après l'authentification
   	 $this->_plugins->afterAuth( $this , array("authResult" => $resultat,  "params" => $params ));
   	 
   	  //Si le resultat est valide
   	 if( $resultat->isValid() ) {    	 	
   	 	//Est ce que l'utilisateur veut rester connecté, si oui, on met à jour la session 	
   	 	$rememberMeSeconds = 86400 * 3;
   	 	if( $rememberMe ) {
   	 		$rememberMeSeconds = (isset(self::$_options["authentication"]["expireTime"])) ? intval(self::$_options["authentication"]["expireTime"]) : 604800;     	 		
   	 	}
   	 	$authSession       = new Zend_Session_Namespace("User_Auth");
   	 	$authSession->setExpirationSeconds( $rememberMeSeconds );
   	 	if( null != ($sessionSaveHandler = Zend_Session::getSaveHandler()) ) {
   	 		$sessionSaveHandler->setLifetime( $rememberMeSeconds )->setOverrideLifetime(true);
   	 	}
   	 	Zend_Session::rememberMe( $rememberMeSeconds );
   	 	Zend_Session::setOptions(array("gc_maxlifetime" => $rememberMeSeconds ));
   	 	//On recupère l'instance de l'utilisateur qui vient de se connecter
   	 	$storage             = $userAuth->getStorage();
   	 	$loggedInUser        = &$storage->read();
   	 	   	 	
   	    if( null!==$loggedInUser && $loggedInUser->userid){   	    	
   	 	    $loggedInUserTable= $loggedInUser->getTable();
   	 	    $dbAdapter        = $loggedInUserTable->getAdapter();
   	 	    $prefixName       = $loggedInUserTable->info("namePrefix");
   	 	
   	 	    //On initialise le jeton et récupère l'identifiant de session
   	 	    $token             = $token . $loggedInUser->userid;
   	 	    $lastSessionId     = Zend_Session::getId();
   	 	    $lastClientIp      = Sirah_Functions::getIpAddress();
   	 	    $lastClientBrowser = Sirah_Functions::getBrowser();
   	 	    $nbConnections     = $loggedInUser->nb_connections+1;
   	 	
   	 	    //On fait la mise à jour des variables de connexion.
   	 	    $loggedInUpdateData= array( "connected"         => 1,
   	 			                       "lastSessionId"     => $lastSessionId,
   	 			                       "logintoken"        => $token,
   	 	   		                       "lastConnectedDate" => time(),
   	 			                       "nb_connections"    => $nbConnections,
   	 			                       "statut"            => self::LOGGEDIN);
   	 	
   	 	    $loggedInUser->setFromArray( $loggedInUpdateData);
   	 	    $loggedInUserTable->save(    $loggedInUpdateData);  	 	   	 	
   	 	
   	 	    //On met à jour le nombre de connections de l'utilisateur   	 	
   	 	    $loggedInUser->nb_connections  = $nbConnections;
   	 	    $loggedInUserTable->setNbConnections($nbConnections);
   	 	  
   	 	    //On enregistre la connexion du client
   	 	    $dbAdapter->insert( $prefixName . "system_users_account_connexion", array("userid"     => $loggedInUser->userid, "ipaddress" => $lastClientIp , "sessionid" => $lastSessionId,
   	 	  		                                                                    "httpclient" => $lastClientBrowser, "token" => $token, "date" => time() ));
   	 	
   	 	    $loggedInUser->lastSessionId = $lastSessionId;
   	 	    $loggedInUser->statut        = self::LOGGEDIN;
   	 	   $storage->write($loggedInUser);
   	    }   	 		 
   	  } else {
   	  	$this->addMessages($resultat->getMessages() , "error");
   	  	return false;
   	  }   	 
   	  /*** On execute les evenements après login ***/
   	 $this->_plugins->afterLogin($loggedInUser , array("auth" => $userAuth,  "params" => $params ));
   	 
   	 return $loggedInUser;
   }


  /**
   * La methode permettant de deconnecter l'utilisateur de son compte
   * 
   * @access public
   * 
   * @return bool true | false
   */
   public function logout()
   {
   	 /*** On execute les evenements avant la deconnexion ***/
   	   $this->_plugins->beforeLogout( $this );
   	   $this->clearMessages();
   	 
   	   $userTable = $this->getTable();
   	   $userAuth  = $this->getAuth();
   	  
   	   if( $userAuth->hasIdentity() ) {
   		   $userAuth->clearIdentity();
   		  if( $userTable ) {
   		  	  $userTable->save( array( "connected" => 0 , "statut"  => self::LOGGEDOUT ));
   		  }
   	   }	   
   	   $loggedOutUser  = Sirah_User::getInstance(0);   	   
   	   /*** On execute les evenements après la deconnexion ***/
   	   $this->_plugins->afterLogout( $loggedOutUser );   	   
   	   Zend_Session::forgetMe();
   	   Zend_Session::regenerateId();
   	   
   	   return $loggedOutUser;
    }
    
    /**
     * La methode permettant d'attribuer un role à l'utilisateur
     * @access public
     * 
     * @param  string $rolename    le nom du role
     * 
     * @return bool true | false
     */
    public function assign( $rolename )
    {
    	return Sirah_User_Acl_Table::assignRoleToUser( $this->userid , $rolename);
    }

  /**
   * La methode permettant de controller l'acces à une ressource de l'utilisateur
   * @access public
   * @param  string $resource         le nom de la ressource
   * @param  string $object           le nom de l'objet
   * 
   * @return bool true | false
   */
  public function isAllowed( $resource , $object )
  {
  	$resourceName    = (is_numeric($resource)) ? Sirah_User_Acl_Table::getResourcename($resource) : $resource  ;
  	$objectName      = (is_numeric($object))   ? Sirah_User_Acl_Table::getObjectname($object , $resourceid) : $object ; 	
  	$acl             = Sirah_Fabric::getAcl("userAcl" , $this->userid , 0);  	
  	return $acl->isAuthorized( $resourceName , $objectName );
  }
          
   /**
    * La methode permettant de verifier si l'utilisateur a un role specifique
    * 
    * @access public
    * @param  string      $rolename le nom du role
    * @return bool true | false
    */
   public function hasRole( $rolename )
   {
   	 $applicationAcl  = Sirah_Fabric::getAcl("userAcl" , $this->userid);   	
   	 return $applicationAcl->hasRole($rolename);    
   }
   
   /**
    * La methode permettant de récupérer le role principal de l'utilisateur
    *
    * @access public
    * 
    * @return string la désignation du role
    */
   public function getRole()
   {
   	  $mainRole     = $this->_role;   	   
   	  if( null === $mainRole ) {
          $table        = $this->getTable();
          $dbAdapter    = $table->getAdapter();
          $tablePrefix  = $table->info("namePrefix");     
          $select       = $dbAdapter->select()->from(array("R"  => $tablePrefix . "system_acl_roles") , array("R.rolename"))
                                              ->join(array("UR" => $tablePrefix . "system_acl_useroles") , "UR.roleid = R.roleid", null)
                                              ->where("UR.userid = ? " , intval($this->userid))
                                              ->order(array("R.accesslevel ASC"));
          $mainRole     = $dbAdapter->fetchOne($select);       
          if(!$mainRole) {
       	      $mainRole = $this->username;
          } 
   	  }
       return $mainRole;
   }

  /**
   *  Cette operation permet d'autoriser l'utilisateur à acceder à des ressources d'une application bien donnee
   *  @access public
   *  @param  array   $resources       un tableau des ressources concernes
   *  @param  array   $objects         un tableau des objets concernes
   *  
   */
  public function allow( $resources = array() , $objects=array())
  {
     return Sirah_User_Acl_Table::allow( $this->userid , array() , $resources , $objects);
  }   
   
/**
   *  Cette operation permet d'interdire à l'utilisateur d'accder à une ou des ressources
   *  @access public
   *  @param  array   $resources       un tableau des ressources concernes
   *  @param  array   $objects         un tableau des objets concernes
   *  
   */
  public function deny($resources=array(),$objects=array())
  {
     return Sirah_User_Acl_Table::deny( $this->userid , array() , $resources , $objects );
  }
     
     
  /**
   *  Cette operation permet de supprimer le compte d'un utilisateur
   *  @access public
   *
   */
  public function delete()
  {
    return $this->_table->delete();
  }
  
  
  /**
   *  Cette operation permet de verouiller le compte d'un utilisateur
   *  @access public
   *
   */
  public function lock()
  {
    return $this->_table->setLocked(1);
  }
  
  /**
   *  Cette operation permet de deverouiller le compte d'un utilisateur
   *  @access public
   *
   */
  public function unLock()
  {
  	return $this->_table->setLocked(0);
  }
  
 
  /**
   *  Cette operation permet d'activer le compte d'un utilisateur
   *  @access public
   *
   */
  public function enable()
  {
  	return $this->_table->setActivated(1);
  }
  
  /**
   *  Cette operation permet de desactiver le compte d'un utilisateur
   *  @access public
   *
   */
  public function disable()
  {
  	return $this->_table->setActivated(0);
  }
  
  
  /**
   *  Cette operation permet de déconnecter un utilisateur de sa session.
   *  @access public
   *
   */
  public function disconnect()
  {
  	if(!$this->isLoggedIn() ) {
  	   $userTable      = $this->_table;
  	   $dbAdapter      = $userTable->getAdapter();
  	   $prefixName     = $userTable->infos("namePrefix");
  	   $lastSessionId  = $userTable->lastSessionId;
  	   
  	   if( $dbAdapter->delete($prefixName."system_users_session","session_id=".$lastSessionId)){
  	   	   $userTable->setConnected(0);
  	   	   return true;
  	   }  	  
  	}
  	return false;
  }
  
  /**
   *  Cette operation permet de bloquer un compte.
   *  @access public
   *
   */
  public function block()
  {
  	return $this->_table->setBlocked(1);
  }
   
  /**
   *  Cette operation permet de débloquer un compte.
   *  @access public
   *
   */
  public function disBlock()
  {
  	return $this->_table->setBlocked(0);
  }
  
  /**
   *  Cette operation permet de récupérer les données présentement stockées dans la session de l'utilisateur
   *  @access public
   *
   */
    public function getSessionData()
    {
      $table          = $this->getTable();
  	  $dbAdapter      = $table->getAdapter();
  	  $prefix         = $table->info("namePrefix");	
	  $rowSession     = $dbAdapter->fetchCol("select donnees FROM ".$prefix."system_users_session WHERE session_id = ".$dbAdapter->quote($this->lastSessionId));
	  $serializedData = (!empty($rowSession)) ? $rowSession[0] : "";
	  $sessionData    = Sirah_Functions::unserialize_session($serializedData);
	  return $sessionData;
    }
  
  /**
   *  Cette operation permet de récupérer le mot de passe de l'utilisateur
   *  @access public
   *
   */
  public function getPassword()
  {
  	$table     = $this->getTable();
  	return $table->getPassword();  	
  }

  /**
   *  Cette operation permet de mettre à jour le mot de passe de l'utilisateur
   *  @access public
   *  @param   string $newpassword le nouveau mot de passe
   *
   */
  public function setPassword($newpassword)
  {
  	if(empty($newpassword)){
  	   throw new Sirah_User_Exception("Impossible d'enregistrer un mot de passe vide");
  	}  	
  	$salt         = Sirah_Functions_Generator::getAlpha(12);
  	$newCryptPwd  = Sirah_User_Helper::cryptPassword($newpassword , $salt);
  	if(!$this->_table->save(array("password" => $newCryptPwd))){
  		$this->addMessage($this->_table->getError());
  		return false;
  	}
  	return true;
  }
  
  /**
   *  Cette operation permet de mettre à jour le nbre de connexions de l'utilisateur
   *  @access  public
   *  @param   integer $nbconnections
   *
   */
  public function setNbConnections($nbconnections)
  {
  	$this->_table->save(array("nb_connections" => intval($nbconnections)));
  	return $this;
  }
  
  
  /**
   *  Cette operation permet de generer un mot de passe aleatoire
   *  @access  public
   *  @param   string $newpassword le nouveau mot de passe
   *
   */
  protected function _generatePassword()
  {
     $randomPwd  = Sirah_Functions_Generator::getAlpha(12);
     return $randomPwd;
  }    

 }
