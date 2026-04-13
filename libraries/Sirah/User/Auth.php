<?php


/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
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
 * Cette classe permet de gérer l'authentification
 * 
 * des utilisateurs de la plateforme SIRAH
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
class Sirah_User_Auth extends Zend_Auth
{
	
	/**
	 * @var Zend_Auth_Adapter_Interface
	 */
	protected $_adapter       = null;
  
   /**
    * @var bool definit si le système d'authentification doit etre securisé
    * 
    */
    protected $_secured       = true;
  
    /**
     * @var bool definit si la session
     *      d'authentification est expirée
     *
     */
    protected $_expired       = false;
  
   /**
    * @var string contenant le mode de controle de l'authentification valide
    * 
    */
    protected $_securitycheck   = array("ipaddress","expiretime");
  
   /**
    * @var integer la valeur de la durée
    *      d'expiration de la session
    *
    */
    protected $_expiretime       = 0;
    
    /**
     * @var array la liste des paramètres
     *      de vérification de la validité 
     *      de la sécurité d'authentification
     */
    protected $_securityChecks   = array("ipaddress","browser","usertoken","expiretime","all");
     
     /**
      * Indique le statut actuel de l'authentification
      * de l'utilisateur.Sa valeur correspond 
      * au resultat de Zend_Auth_Result
      * 
      * @var integer
      */
    public $status               = 0;
     
     
     /**
      * @var integer indique si on doit se rappeler de l'utilisateur ou pas
      *
      */
    protected $_rememberMe       = 1;

 
     /**
      * Retourne une instance de Zend_Auth
      *
      * Implémentation du pattern Singleton
      *
      * @return Zend_Auth Provides a fluent interface
      */
    public static function getInstance($config=array())
    {
  	    if( $config instanceof Zend_Config){
  	   	    $config  = $config->toArray();
  	    }
        if( null === self::$_instance) { 
            self::$_instance = new self();            
            //On crée un storage
            $authStorageSession = new Zend_Session_Namespace("User_Auth");
            if(!$authStorageSession->initialised ) {
            	//On régénère l'ID de session pour se protéger des vols de session par fixation
            	Zend_Session::regenerateId();
            	$authStorageSession->initialised  = true;
            }
            if(!isset($options["storage"]) ) {
                $authStorage   = new Zend_Auth_Storage_Session("User_Auth", "row");
                self::$_instance->setStorage($authStorage);
            } elseif(isset($options["storage"]) && ($options["storage"] instanceof Zend_Auth_Storage_Interface)) {
            	self::$_instance->setStorage($options["storage"]);
            }            
            if( isset($config["rememberMe"] ) ) {
            	self::$_instance->setRememberMe(intval($config["rememberMe"]));
            }           
            if( isset($config["expiretime"])){
            	self::$_instance->setExpiretime(intval($config["expiretime"]));
            }
            if( isset($config["adapter"]) && ($config["adapter"] instanceof Zend_Auth_Adapter_Interface)){
            	self::$_instance->setAdapter($config["adapter"]);
            	$adapter       = $config["adapter"];
            } else {
            	$userTable     = Sirah_User_Table::getInstance();
            	$dbAdapter     = $userTable->getAdapter();
            	$userTableName = $userTable->info("name");
            	$adapter       = new Sirah_User_Auth_Adapter_Table( $dbAdapter , $userTableName , "username" , "password");
            	self::$_instance->setAdapter($adapter);
            }            
            $credentialColumn = (isset($config["authentication"]["credentialColumn"])) ? $config["authentication"]["credentialColumn"]: "password";
            $identityColumn   = (isset($config["authentication"]["identityColumn"]))   ? $config["authentication"]["identityColumn"]  : "username";
            $adapter->setIdentityColumn($identityColumn);
            $adapter->setCredentialColumn($credentialColumn);
            
            if(isset($config["secured"])){
            	self::$_instance->setSecured(intval($config["secured"]));
            }
            if( isset($config["securityChecks"])){
            	self::$_instance->setSecurityChecks($config["securityChecks"]);
            }
        }
        return self::$_instance;
    }

    /**
     * Permet d'authentifier un utilisateur
     * en utilisant son identifiant
     *
     * @return Zend_Auth_Result
     */
     public function authenticate( Zend_Auth_Adapter_Interface $adapter = null )
     {
     	$adapter  = ( null == $adapter ) ? $this->getAdapter() : $adapter;
     	if(null===$adapter || !($adapter instanceof Zend_Auth_Adapter_Interface)){
     		return false;
     	}
     	$result = $adapter->authenticate();     	    	
     	if ($this->hasIdentity()) {
     		$this->clearIdentity();
     	}     	
     	if( $result->isValid() ) {     		
     		//On stocke les données dans le storage
     		$storageData                          = $adapter->getResultRowObject( null , array("password"));
     		$authenticatedUser                    = Sirah_User::getInstance($storageData->userid);
     		$authenticatedUser->lastIpAddress     = Sirah_Functions::getIpAddress();
     		$authenticatedUser->lastConnectedDate = time();
     		$authenticatedUser->lastHttpClient    = Sirah_Functions::getBrowser();
     		$authenticatedUser->logintoken        = null;     		
     		$this->getStorage()->write( $authenticatedUser );
     	}   	
     	$this->statut               = $result->getCode();     	
     	return $result;
     }    
     
     /**
      * Permet de vérifier  si la session
      * d'authentification est valide
      *
      * @return true or false
      */
     public function checkAuth($postdata=array(),$serverdata=array(),$cookie=array())
     {
     	$storage       = $this->getStorage();
     	$storageData   = $storage->read();
     	$securityCheck = $this->getSecurityCheck();
     	
     	if(!$this->isSecured() && $this->hasIdentity()){
     		return true;
     	}   
     	if(in_array("all",$securityCheck) || in_array("expiretime",$securityCheck)) {
     		if(isset($storageData->lastConnectedDate) && $this->_expiretime > 0 && (($storageData->lastConnectedDate+$this->_expiretime) < time())){
     			$this->clearIdentity();
     			$this->statut  = Sirah_User_Auth_Result::FAILURE_AUTH_EXPIRED;
     			$this->setExpired(true);
     			return false;
     		}
     	} 	
     	if(in_array("all",$securityCheck) || in_array("ipaddress",$securityCheck)) {
     		if(isset($storageData->lastIpAddress) && ($storageData->lastIpAddress!=Sirah_Functions::getIpAddress())){
     			$this->clearIdentity();
     			$this->statut  = Sirah_User_Auth_Result::FAILURE_SECURITY_BREACH;
     			$this->setExpired(true);
     			return false;
     		}
     	}     	
       if(in_array("all",$securityCheck) || in_array("browser",$securityCheck)){
     	  if(isset($storageData->lastHttpClient) && ($storageData->lastHttpClient!=Sirah_Functions::getBrowser())){
     			$this->clearIdentity();
     			$this->statut  = Sirah_User_Auth_Result::FAILURE_SECURITY_BREACH;
     			$this->setExpired(true);
     			return false;
     		}
     	}     	
     	if(in_array("all",$securityCheck) || in_array("usertoken",$securityCheck)){
     		if(isset($postdata["token"]) && ($storageData->logintoken!=$postdata["token"])){
     			$this->clearIdentity();
     			$this->statut  = Sirah_User_Auth_Result::FAILURE_SECURITY_BREACH;
     			$this->setExpired(true);
     			return false;
     		}
     	}    	 
     	return true;
     }
     
     
     /**
      * Permet de vérifier si la session
      * 
      * d'authentification est expirée ou pas
      *
      *
      * @return bool vrai ou faux
      */
     public function isExpired()
     {
     	 return $this->_expired;
     }
     
     
     /**
      * Permet de vérifier si l'authentification
      * 
      * doit etre sécurisée ou pas
      *
      * 
      * @return bool vrai ou faux
      */
     public function isSecured()
     {
     	 return $this->_secured;
     }
           
     /**
      * Permet de mettre à jour la l'option
      * 
      * d'authentification sécurisée
      * 
      * @param integer  $securedVal la valeur de l'option sécurisée
      * 
      * @return Sirah_User_Auth
      *
      */
     public function setSecured($securedVal)
     {
        $this->_secured  = intval($securedVal);
        return $this;
     }
     
     
     /**
      * Permet de mettre à jour l'état de la session expirée
      *
      * @param integer $expired la valeur du paramètre expiré
      * 
      * @return Sirah_User_Auth
      *
      */
     public function setExpired($expired)
     {
     	 $this->_expired  = $expired;
     	 return $this;
     }         
     
     /**
      * Permet de mettre à jour les paramètres 
      * de vérification de l'authnetification de l'utilisateur
      *
      * @param  array $security
      * 
      * @return Sirah_User_Auth
      *
      */
     public function setSecurityChecks($security=array())
     {
     	$this->_securityCheck = array();
     	$security             = (array)$security;
     	
        foreach($security as $securityKey){
         	if(in_array($securityKey,$this->_securityChecks)){
     	 	   $this->_securityCheck[]  = $securityKey;
         	}
     	 }
     	 return $this;
     }    
     
     /**
      * Permet de mettre à jour la durée d'expiration
      * 
      * de la session d'authentification
      *
      * @param  integer $newtime la durée de vie
      * 
      * @param  bool    $add indique si sa doit etre ajouté
      * 
      *                      à la durée existante ou complètement remplacée
      * @return Sirah_User_Auth
      *
      */
     public function setExpiretime($newtime,$add=false)
     {
     	 $this->_expiretime = ($add)?$this->_expiretime+intval($newtime):intval($newtime);
     	 return $this;
     }
     
     /**
      * Permet de mettre à jour l'adaptateur utilisé pour l'authentification
      *
      *
      * @param  Zend_Auth_Adapter_Interface $adapter l'instance de l'adaptateur
      *
      * @return Sirah_User_Auth
      *
      */
     public function setAdapter(Zend_Auth_Adapter_Interface $adapter)
     {
     	$this->_adapter  = $adapter;
     	return $this;
     }
     
     /**
      * Permet de récupérer l'adaptateur d'authentification
      *
      *
      * @return Zend_Auth_Adapter_Interface $adapter l'instance de l'adaptateur
      *
      *
      */
     public function getAdapter()
     {
     	return $this->_adapter;
     }
     
     /**
      * Permet de récupérer le niveau de sécurité
      * de l'authentification de l'utilisateur
      *
      * @return string $securityCheck
      *
      */
     public function getSecurityCheck()
     {
     		return $this->_securitycheck;
     }
}

