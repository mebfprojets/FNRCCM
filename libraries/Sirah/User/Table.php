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
 * Cette classe représente la table qui sert de passerelle avec la table des utilisateurs
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_User_Table extends Sirah_Db_Table implements Sirah_User_Table_Interface
{
	
	/**
	 *
	 * @var string  le nom de la table
	 */
	protected $_name   = "system_users_account";
	
	
	/**
	 *
	 * @var string  la clé primaire
	 */
	protected $_primary = array("userid");
	
	/**
	 *
	 * @var string le contenu des  erreurs générées 
	 */
	protected $_error  = "";
	
	/**
	 *
	 * @var array  les données de la table
	 */
	
	protected  $_data         = array();
	 
	 
	 /**
	  *
	  * @var array  les données par defaut
	  */
	protected  $_defaultData  = array(
			                           "userid"             => 0,
			                           "firstname"          => "anonyme",
			                           "lastname"           => "anonyme",
			                           "phone1"             => null,
			                           "phone2"             => null,
			                           "address"            => null,
			                           "zipaddress"         => null,
			                           "city"               => null,
									   "localiteid"         => null,
			                           "country"            => null,
	 		                           "language"           => "FR",
			                           "facebookid"         => null,
			                           "skypeid"            => null,
			                           "username"           => "guest",
			                           "password"           => null,
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
			                           "accesstoken"        => null,
			                           "logintoken"         => null,
			                           "params"             => null,
			                           "updateduserid"      => 0,
			                           "nb_connections"     => 0,
			                           "creatoruserid"      => 0,
			                           "registeredDate"     => null,
			                           "lastConnectedDate"  => null,
			                           "lastUpdatedDate"    => null,
			                           "lastIpAddress"      => null,
			                           "lastHttpClient"     => null,
	 			                       "lastSessionId"      => null );	 	 
	 /**
	  *
	  * @var array  
	  * un tableau des colonnes de la table
	  * qui ont été mises à jour
	  */
	 protected $_modifiedFields = array();
	 
	 
	/**
     * C'est une copie du tableau $_data quand les données
     * proviennent de la base de données
     *
     * @var array
     */
    protected $_cleanData = array();
    
    /**
     * Liste des instances de la table des utilisateurs
     *
     * @var array static
     */
    static $instances = array();
	 
	 
	 /**
	  * Permet de construire la classe
	  *
	  * @param   integer  $userid    L'identifiant de l'utilisateur.
	  * @param   array    $params   Un tableau des paramètres
	  *
	  * @since
	  */
	 public function __construct( $userid , $params=array())
	 {
	 	parent::__construct($params);
	 	if ( isset( $params['stored'] ) && $params['stored'] === true) {
	 		 $this->_cleanData = $this->_data;
	 	}
	 	$this->_loadData($userid);
	 }
	  
	  
	  
	  /**
	   * Un singleton permettant de recupérer une instance de la classe
	   *
	   * @static
	   * @param   integer  $userid    L'identifiant de l'utilisateur.
	   * @param   array    $params   Un tableau des paramètres
	   *
	   * @since
	   */
	  static public function getInstance( $userid=0 , $params=array())
	  {
	  	if(!$userid ) {
	  		$instance = new Sirah_User_Table( $userid, $params);
	  		return $instance;
	  	}
	  	if(!isset(self::$instances[$userid])){	  		
	  		self::$instances[$userid]  = new Sirah_User_Table( $userid, $params);
	  	}
	  	return self::$instances[$userid];
	  }
	 
	 /**
	  * Permet de charger les données de la table
	  *
	  * @param   integer  $userid    L'identifiant de l'utilisateur.
	  * @param   string   $username  Le nom d'utilisateur
	  * @param   string   $email     L'email de l'utilisateur
	  *
	  * @since
	  */
	 protected function _loadData( $userid=0,$username=null,$email=null)
	 {
	   if( $userid ) {
	      $rowsArray = $this->find(array("userid"=>$userid,"username" => $username,"email" => $email));	
	      if( isset ( $rowsArray["userid"] ) ) {
	          $this->_data = $this->_cleanData = array_intersect_key( $rowsArray , $this->_defaultData );
	          return true;
	       }
	 	 }
	 	 $this->_data = $this->_defaultData;
	 	 return false;
	  }

	/**
	 * Permet de sauvegarder les données de l'utilisateur
	 *
	 * @param   array $data les données à sauvegarder.
	 * @return  bool retourne vrai si la sauvegarde s'est bien passée sinon retourne faux
	 *
	 * @since
	 */
	public function save( $data = array() )
	{
		  $primaryKeyValue  = (isset($data["userid"]   ))? intval($data["userid"]) : $this->getData("userid");
		  $userFirstName    = (isset($data["firstname"]))? $data["firstname"]      : $this->getData("firstname");
		  $userLastName     = (isset($data["lastname"] ))? $data["lastname"]       : $this->getData("lastname");
		  
		if( empty( $this->_cleanData) && !intval($primaryKeyValue) && $userFirstName!=="anonyme" && $userLastName!=="anonyme"){
			return $this->_runInsert($data);
		} elseif( intval($primaryKeyValue) ){
			if(isset($data["userid"])){
				 unset($data["userid"]);
			}
			return $this->_runUpdate( $data );
	    }	  
	  return false;
	}
	
	
	/**
	 * Permet de supprimer le compte de l'utilisateur
	 *
	 * @return  bool retourne vrai si la suppression reussit
	 *
	 * @since
	 */
	public function delete($where  = "")
	{
		if(!intval($this->userid)){
			return false;
		}
		$userid      = $this->userid;
		$this->_data = $this->_defaultData;
		$where       = "userid=".intval($userid);
		$tableSpec   = ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
		return $this->_db->delete($tableSpec, $where);
	}
		
	/**
	 * Permet d'insérer une nouvelle ligne dans la table des utilisateurs
	 *
	 * @param   array $data les données à sauvegarder.
	 * @return  bool retourne vrai si la sauvegarde s'est bien passée sinon retourne faux
	 *
	 * @since
	 */
	protected function _runInsert( $data = array())
	{
		$insertData        = (empty($data)) ? $this->_data : $data;
		
		if(isset($insertData["userid"])){
		   unset($insertData["userid"]);
		}		
		if( $this->check($insertData)){
			$data["registeredDate"]  = time();
			if( $pkData = $this->insert($insertData)){
				$this->_loadData($pkData);
				return $pkData;
			}
			$this->setError("L'insertion des données de l'utilisateur dans le système a echoué");
			return false;
		}
		return false;
	 }
	 
	 /**
	  * Permet d'effectuer la mise à jour des données de l'utilisateur
	  *
	  * @param   array $updateData les données à mettre à jour.
	  * @return  bool  retourne vrai si la sauvegarde s'est bien passée sinon retourne faux
	  *
	  * @since
	  */
	 protected function _runUpdate( $updateData = array())
	 {
	 	$dbAdapter         = $this->getAdapter();
	 	$updateData        = (empty( $updateData )) ? $this->_data : $updateData;
	 	$userid            = (isset($updateData["userid"])) ? intval($updateData["userid"]) : $this->_data["userid"];
	 	
	 	if( isset($updateData["userid"] ) ){
	 		unset($updateData["userid"] );
	 	}
	 	if( isset($updateData["username"]) && (null==$updateData["username"] || empty($updateData["username"]))){
	 		unset($updateData["username"]);
	 	}	 	
	 	if(isset($updateData["email"]) && (null==$updateData["email"] || empty($updateData["email"]))){
	 		unset($updateData["email"]);
	 	}
	 	if(isset($updateData["password"]) && (null==$updateData["password"] || empty($updateData["password"]))){
	 		unset($updateData["password"]);
	 	}	 		
		if(isset($updateData["email"]) && !$this->checkEmail($updateData["email"])){
	           return false;	
		}	
		if(isset($updateData["username"]) && !$this->checkUsername($updateData["username"])){
			   return false;	
		}
		$where           = $dbAdapter->quoteInto("userid=?", intval($userid));
		
	 	if( $updatedRows = $this->update( $updateData , $where) ){
	 		$this->setFromArray($updateData,"password");
	 		return $updatedRows;
	 	}
	 	$this->setError("Aucune modification n'a été appliquée sur vos données. ");
	 	return false;
	 }
	 	
	/**
	 * Permet de vérifier les données de l'utilisateur
	 *
	 * @param   array    $data       Les données à vérifier
	 * @param   array    $ignoreKeys Les clés à ignorer dans la vérification
	 * @return  boolean  vrai si la vérification est valide
	 * @since
	 */
	public function check( $data = array() , $ignoreKeys = array() )
	{
	  if( empty( $data ) ) {
	  	  $data         = $this->_data;
	   }
	   $ignoreKeys      = (is_string($ignoreKeys))  ? explode(",",$ignoreKeys)  :  $ignoreKeys;
	   
	   $checkEmail      = (isset($data["email"]))     ?  $data["email"]    : null;
	   $checkUsername   = (isset($data["username"]))  ?  $data["username"] : null;
	   $checkFirstName  = (isset($data["firstname"])) ?  $data["firstname"]: null;
	   $checkLastName   = (isset($data["lastname"]))  ?  $data["lastname"] : null;
	   
	   if(null!==$checkEmail && !$this->checkEmail($checkEmail) && !in_array($checkEmail,$ignoreKeys)) {
	   	  return false;
	   } elseif (null !== $checkUsername && !$this->checkUsername($checkUsername) && !in_array($checkUsername,$ignoreKeys)){
	   	  return false;
	   } elseif($checkFirstName==="anonyme" || empty($checkFirstName)){
	   	  $this->setError(" Le prénom que vous avez saisi, est invalide ");
	   	  return false;
	   } elseif($checkLastName==="anonyme" || empty($checkLastName)){
	   	  $this->setError(" Le nom de famille que vous avez saisi, est invalide ");
	   	  return false;
	   }	  
	   return true;
	 }
	 
	 /**
	  * Permet de vérifier la validdité d'un email
	  *
	  * @param   string   $value  la valeur de l'email
	  * @return  boolean  vrai si la vérification est valide
	  * @since
	  */
	 public function checkEmail($value)
	 {
	 	$emailValidator = new Sirah_Validateur_Email();
	 	$adapter        = $this->getAdapter();
	 	$tableName      = $this->info("name");
		$prefixName     = $this->info("namePrefix");	
	 	$select         = $adapter->select()->from(array("U"=>$tableName))
		                                    ->join(array("P"=>$prefixName."system_users_profile"),"P.userid=U.userid",null)
											->join(array("C"=>$prefixName."system_users_profile_coordonnees"),"C.profileid=P.profileid",null);
	 	
	 	$select->where("U.email=\"".$value."\" OR C.email=\"".$value."\"")
		       ->where("U.userid != ?" , $this->userid);	
	 	$emailFound = $adapter->fetchRow($select , array(), Zend_DB::FETCH_ASSOC);	 	
	 	if((null !== $value) && !$emailValidator->isValid($value)){
	 		$this->setError(" L'email fourni n'est pas valide ");
	 		return false;
	 	} elseif ( ( null !== $value ) && !empty($emailFound)) {
	 		$this->setError(" L'email fourni semble etre associé à un autre compte");
	 		return false;
	 	}
	 	return true;
	 }
	 
	 /**
	  * Permet de vérifier la validdité d'un email
	  *
	  * @param   string   $value  la valeur de l'email
	  * @return  boolean  vrai si la vérification est valide
	  * @since
	  */
	 public function checkUsername($value)
	 {
	 	$usernameValidator = new Sirah_Validateur_Username();
	 	$adapter           = $this->getAdapter();
	 	$tableName         = $this->info("name");
	 	$select            = $adapter->select()->from($tableName);	 	
	 	$select->where("username= ?" , $value)->where("userid    != ?" , $this->userid);	
	 	$usernameFound = $adapter->fetchRow($select , array(), Zend_DB::FETCH_ASSOC);
	 	 
	 	if( ( null !== $value ) && !$usernameValidator->isValid($value)){
	 		$this->setError(" Le nom d'utilisateur fourni n'est pas valide ");
	 		return false;
	 	} elseif( (null !== $value ) && !empty($usernameFound)) {
	 		$this->setError(" Le nom d'utilisateur fourni semble etre associé à un autre compte");
	 		return false;
	 	}
	 	return true;
	 }
	 	 
	 /**
	  * Permet d'enregistrer une erreur dans le composant
	  *
	  * @param   string $error  Enregistrer une erreur dans le composant
	  *
	  * @since
	  */
	 public function setError($error)
	 {
	    $this->_error  = $error;
	 }
	 	 
	 /**
	  * Permet de vérifier les données de l'utilisateur
	  *
	  * @param   string $error  Enregistrer une erreur dans le composant
	  *
	  * @since
	  */
	 public function getError()
	 {
	 	return $this->_error;
	 }
	   	 
	/**
	 * Permet de recupérer une ligne dans la table des utilisateurs
	 *
	 * @param   array   Les données servant de conditions de recherches
	 * @param   string  
	 * @return  mixed   Retourne un tableau contenant les données d'une ligne correspondante
	 *
	 * @since
	 */
	public function find( $whereData=array() , $clauseKey=" OR ")
	{
		if( empty($whereData) ) {
			$primariesKey  = (array)$this->_primary;
			foreach( $primariesKey as $primaryKey){
				     $whereData[$primaryKey] = isset($this->_data[$primaryKey]) ? $this->_data[$primaryKey] : null;
			}
		}
		$dbAdapter         = $this->getAdapter();
		$tableName         = $this->info("name");		
		$queryString       = " SELECT * FROM ".$dbAdapter->quoteTableAs($tableName , null , true)." \n  ";
		$whereClause       = array();		
		foreach( $whereData as $whereKey => $whereVal){
				 if( null!==$whereVal && !empty($whereVal)){
				     $whereClause[] =  $dbAdapter->quoteIdentifier($whereKey , true)." = ".$dbAdapter->quote($whereVal);
				 }
		}
		if(!empty($whereClause)){
			$queryString .= " WHERE  ( ".implode($clauseKey,$whereClause)." ) ";
		}  else {
			return array();
		}
		$findRow     = $dbAdapter->fetchRow( $queryString , array() , Zend_DB::FETCH_ASSOC);
		
		return $findRow;
	 }
	
	/**
	 * Permet de recupérer les données de l'utilisateur
	 *
	 * @param   string  $key   La colone de la ligne 
	 * @return  mixed   une chaine au cas ou on aura spécifié la clé ou un tableau   
	 *
	 * @since
	 */
	public function getData($key=null)
	{
		if( null===$key || !isset($this->_data[$key])){
			return $this->_data;
		}
		return $this->_data[$key];
	}
	
	
	/**
	 * Permet de recuperer les roles de l'utilisateur

	 * @return  array la liste des roles de l'utilisateur
	 *
	 * @since
	 */
	public function getRoles()
	{
		$userid  = $this->userid;
		$roles   = Sirah_User_Acl_Table::getRoles($userid);		
		return $roles;
	}
	
	/**
	 * Permet d'assigner des données à la table
	 *
	 * @param   array    $bindArray    Le tableau des données
	 * @param   array    $ignoreKeys   Les clés du tableau à ignorer
	 *
	 * @since
	 */
	public function setFromArray($bindArray=array() , $ignoreKeys = array())
	{
	    if( empty($bindArray)){
	   	    return false;
	    }
	    $ignoreKeys = (is_string($ignoreKeys)) ? explode(",",$ignoreKeys) : $ignoreKeys;
	    $userData   =  array_intersect_key($bindArray , $this->_data);	   
	    foreach( $userData as $bindKey => $bindValue){
				 if(!in_array( $bindKey, $ignoreKeys)){
					  $this->__set($bindKey , $bindValue);
				 }
	    }
	   return true;
	}		
	
	/**
	 * Permet de recupérer l'identifiant de l'utilisateur 
	 * à partir de son nom d'utilisateur ou de son email
	 * 
	 * @param   string   $searchval La valeur correspondant aux paramètres recherchés
	 * @param   array    $attributes    Les paramètres de récupération. 
	 *                                  On peut récupérer son identifiant à partir
	 *                                   de son email ou de son nom d'utilisateur
	 *                              
	 * @return  integer  l'identifiant de l'utilisateur
	 * 
	 */
	public function getUserIdBy( $searchval="" , $attributes=array())
	{
		$findWhereData  = array_fill_keys($attributes , $searchval);
		if( $findRows    = $this->find($findWhereData)){
			return $findRows["userid"];
		}
		return false;
	 }	

	/** Permet de recupérer le mot de passe de l'utilisateur
	 *
	 *
	 * @return  string le mot de passe crypté
	 *
	 */
	 public function getPassword()
	 {
	 	$dbAdapter         = $this->getAdapter();
	 	$tableName         = $this->info("name");
	 	$userid            = intval($this->userid);
	 	
	 	$queryString       = " SELECT password FROM ".$dbAdapter->quoteTableAs( $tableName , null , true )." \n WHERE userid = ? ";
	 	
	 	return $dbAdapter->fetchCol($queryString,$userid);
	 }
	 
	/**
	 * Permet de mettre à jour la dernière date de connexion de l'utilisateur
	 *
	 * @param   integer  $timestamp    La valeur du timestamp de la dernière connexion(facultatif)
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLastVisiteDate($timestamp=0)
	{
	   $userid      = $this->userid;
	   $updateData  = array( "userid"            => $userid,
	   		                 "lastConnectedDate" => (intval($timestamp))?intval($timestamp):time());
	   return $this->_runUpdate($updateData);
	}	
	
	
	/**
	 * Permet de mettre à jour l'addresse IP de connexion de l'utilisateur
	 *
	 * @param   string   $ipaddress       L'addresse IP de l'utilisateur
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLastIpAddress($ipaddress="")
	{
		$userid      = $this->userid;
		if(empty($ipaddress) || null===$ipaddress){
			$ipaddress = Sirah_Functions::getIpAddress();
		}
		$updateData  = array( "userid"         => $userid,
				              "lastIpAddress"  => $ipaddress);
		return $this->_runUpdate($updateData);	
	}
	
	/**
	 * Permet de mettre à jour le client HTTP utilisé par le navigateur
	 *
	 * @param   string   $browser         Le navigateur utilisé par l'utilisateur
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLastHttpClient($client="")
	{
		$userid      = $this->userid;
		if(empty($client) || null===$client){
			$client = Sirah_Functions::getBrowser();
		}
		$updateData  = array( "userid"         => $userid,
				              "lastHttpClient" => $client);
		return $this->_runUpdate($updateData);
	}
	
	/**
	 * Permet de mettre à jour le dernier identifiant de session attribué à l'utilisateur.
	 *
	 * @param   string   $sessionId      Le dernier identifiant de session
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLastSessionId($sessionId)
	{
		$userid      = $this->userid;		
		$updateData  = array( "userid"        => $userid,
				              "lastSessionId" => $sessionId);		
		return $this->_runUpdate($updateData);
	}
	
	/**
	 * Permet de mettre à jour le jeton d'accès de l'utilisateur
	 *
	 * @param   string   $token           Le jeton de l'utilisateur
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setAccessToken($token)
	{
		$userid      = $this->userid;
		$updateData  = array( "userid"       => $userid,
				              "accesstoken"  => $token);
		return $this->_runUpdate($updateData);
	}
	
	/**
	 * Permet de mettre à jour le jeton de connexion de l'utilisateur
	 *
	 * @param   string   $token           Le jeton de l'utilisateur
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLoginToken($token)
	{
		$userid      = $this->userid;
		$updateData  = array( "userid"      => $userid,
				              "logintoken"  => $token);
		return $this->_runUpdate($updateData);
	}

	
	/**
	 * Permet de mettre à jour le statut connecté de l'utilisateur
	 *
	 * @param   integer  $statut       Le statut de connexion
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setConnected($statut=0)
	{
		$userid      = $this->userid;
		$updateData  = array( "userid"     => $userid,
				              "connected"  => $statut);
		return $this->_runUpdate($updateData);
	}
	
	/**
	 * Permet de mettre à jour le nombre de fois que l'utilisateur s'est connecté
	 * 
	 * @param   integer  $nb   le nombre de connexions de l'utilisateur
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setNbConnections($nb=0)
	{
		$userid      = $this->userid;
		$updateData  = array( "userid"          => $userid,
				              "nb_connections"  => $nb);
		return $this->_runUpdate($updateData);
	}
	
	/**
	 * Permet de mettre à jour le statut verrouillé de l'utilisateur
	 *
	 * @param   integer  $locked       Le statut de connexion
	 *
	 * @return  true ou false en fonction des resultats de la mise à jour
	 *
	 */
	public function setLocked($locked=0)
	{
		$userid      = $this->userid;
		$updateData  = array( "userid"  => $userid,
				              "locked"  => $locked);
		return $this->_runUpdate($updateData);
	 }	 
	 
	 /**
	  * Permet de mettre à jour le statut bloqué de l'utilisateur
	  *
	  * @param   integer  $locked       Le statut de connexion
	  *
	  * @return  true ou false en fonction des resultats de la mise à jour
	  *
	  */
	 public function setBlocked($blocked=0)
	 {
	 	$userid      = $this->userid;
	 	$updateData  = array( "userid"   => $userid,
	 			              "blocked"  => $blocked);
	 	return $this->_runUpdate($updateData);
	 }
	 
	 
	 /**
	  * Permet de mettre à jour le statut activé de l'utilisateur
	  *
	  * @param   integer  $activated    Le statut activé
	  *
	  * @return  true ou false en fonction des resultats de la mise à jour
	  *
	  */
	 public function setActivated($activated=0)
	 {
	 	$userid      = $this->userid;
	 	$updateData  = array( "userid"     => $userid,
	 			              "activated"  => $activated);
	 	return $this->_runUpdate($updateData);
	 }
	 
	 /**
	  * Permet d'expirer le compte d'un utilisateur
	  *
	  * @param   integer  $expired  la valeur du statut expiré
	  *
	  * @return  true ou false en fonction des resultats de la mise à jour
	  *
	  */
	 public function setExpired($expired=0)
	 {
	 	$userid      = $this->userid;
	 	$updateData  = array( "userid"   => $userid,
	 			              "expired"  => $expired);
	 	return $this->_runUpdate($updateData);
	 }
	 
	 
	 /**
	  * Permet de récupérer un champ de la table des utilisateurs
	  *
	  * @param  string $columnName Le nom de la colonne de la table
	  * @return string             Le nom de la colonne correspondante
	  *
	  */
	 public function __get($columnName)
	 {
	 	return $this->_data[$columnName];
	 }
	  
	 /**
	  * Permet de mettre à jour les colonnes
	  * à travers la méthode magique __set
	  *
	  * @param  string $columnName Le nom de la colonne.
	  * @param  mixed  $value      La valeur de la colonne
	  * @return void
	  */
	 public function __set($columnName, $value)
	 {
	 	$this->_data[$columnName]           = $value;
	 	$this->_modifiedFields[$columnName] = true;
	 }
	  
	 /**
	  * Permet de supprimer un champ de la table
	  *
	  * @param  string $columnName The column key.
	  * @return Zend_Db_Table_Row_Abstract
	  * @throws Zend_Db_Table_Row_Exception
	  */
	 public function __unset($columnName)
	 {
	 	if (!array_key_exists($columnName, $this->_data)) {
	 		require_once 'Sirah/User/Table/Exception.php';
	 		throw new Sirah_User_Table_Exception("La colonne spécifiée \"$columnName\" n'est pas un champ valide");
	 	}
	 	unset( $this->_data[$columnName]);
	 	return $this;
	 }
	  
	 /**
	  * Permet de vérifier si la table des utilisateurs
	  * possede une colonne bien précise
	  *
	  * @param  string  $columnName   La clé de la colonne.
	  * @return boolean
	  */
	 public function __isset($columnName)
	 {
	 	return array_key_exists($columnName, $this->_data);
	 }
	 
	 
	 /**
	  * Permet de récupérer l'instance de pagination
	  * de la liste des utilisateurs
	  *
	  * @static
	  * @param  array $filters
	  * @return Zend_Paginator
	  */
	 public function getUsersPaginator($filters = array() )
	 {
	 	$userTable    = Sirah_User_Table::getInstance();
	 	$dbAdapter    = $userTable->getAdapter();
	 	$tablePrefix  = $userTable->info("namePrefix");	 	
	 	$selectUsers  = $dbAdapter->select()->from(array("U" => $userTable->info("name")), array("U.userid"))
	 	                                    ->join(array("UR"=> $tablePrefix."system_acl_useroles"),"UR.userid=U.userid",null)
	 	                                    ->join(array("R" => $tablePrefix."system_acl_roles"),"R.roleid=UR.roleid",null);
	    if( isset($filters["lastname"])   && !empty($filters["lastname"])){
	 		$selectUsers->where("U.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
	 	}	 	
	 	if( isset($filters["firstname"])  && !empty($filters["firstname"])){
	 		$selectUsers->where("U.firstname LIKE ?","%".$filters["firstname"]."%");
	 	}
	 	if( isset($filters["username"])   && !empty($filters["username"])){
	 		$selectUsers->where("U.username LIKE ?","%".$filters["username"]."%");
	 	}	 	
	 	if( isset($filters["email"])      && !empty($filters["email"])){
	 		$selectUsers->where("U.email=?",$filters["email"]);
	 	}
	 	if( isset($filters["country"])    && !empty($filters["country"])){
	 		$selectUsers->where("U.country=?",$filters["country"]);
	 	}
	 	if( isset($filters["userid"])     && intval($filters["userid"])){
	 		$selectUsers->where("U.userid=?",intval($filters["userid"]));
	 	}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])){
	 		$selectUsers->where("U.localiteid=?",intval($filters["localiteid"]));
	 	}
	 	if( isset($filters["phone"])      && !empty($filters["phone"])){
	 		$selectUsers->where($dbAdapter->quote("U.phone1=".$filters["phone"]." OR U.phone2 =".$filters["phone"]));
	 	}
	 	if( isset($filters["expired"])    && ($filters["expired"]==1 || $filters["expired"]==0)){
	 		$selectUsers->where("U.expired=?",intval($filters["expired"]));
	 	}
	 	if( isset($filters["activated"])  && ($filters["activated"]==1 || $filters["activated"]==0)){
	 		$selectUsers->where("U.activated=?",intval($filters["activated"]));
	 	}
	 	if( isset($filters["blocked"])    && ($filters["blocked"]==1 || $filters["blocked"]==0)){
	 		$selectUsers->where("U.blocked=?",intval($filters["blocked"]));
	 	}
	 	if( isset($filters["locked"])     && ($filters["locked"]==1 || $filters["locked"]==0)){
	 		$selectUsers->where("U.locked=?",intval($filters["locked"]));
	 	}
	 	if( isset($filters["admin"])      && ( $filters["admin"]>=1 )  ) {
	 		$selectUsers->where("U.admin=?",intval($filters["admin"]));
	 	}
	 	if( isset($filters["rolename"])   && !empty($filters["rolename"])){
	 		$selectUsers->where("R.rolename=?" , $filters["rolename"] );
	 	}
	 	if( isset($filters["roleid"])     && intval($filters["roleid"]) ){
	 		$selectUsers->where("R.roleid=?" , intval( $filters["roleid"] ) );
	 	}
	 	$selectUsers->group(array("UR.userid"));
	 	$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectUsers);
	 	$rowCount          = intval(count($dbAdapter->fetchAll($selectUsers)));
	 	$paginationAdapter->setRowCount($rowCount);	 	
	 	$paginator = new Zend_Paginator($paginationAdapter);
	 	
	 	return $paginator;
	}
	
	 
	 /**
	  * Permet de recupérer la localité de utilisateur
	  *
	  * @static
	  * @param  integer la clé primaire du compte utilisateur
	  * @int    l'identifiant de la localité
	  *
	  * @since
	  */
	public function getUserLocaliteId($userid=0)
	{
	 	$userTable        = Sirah_User_Table::getInstance();
	 	$dbAdapter        = $userTable->getAdapter();
	 	$tablePrefix      = $userTable->info("namePrefix");
	 
	 	$selectLocaliteId = $dbAdapter->select()->from(    array("U" => $userTable->info("name")), array("U.localiteid"))
	 	                                        ->join(    array("UR"=> $tablePrefix."system_acl_useroles"),"UR.userid=U.userid",null)
	 	                                        ->join(    array("R" => $tablePrefix."system_acl_roles"),"R.roleid=UR.roleid",null)
												->joinLeft(array("L" => $tablePrefix."rccm_localites"),"L.localiteid=U.localiteid",null)
												->where("U.userid=?", intval($userid));	 	
	    
	 	return $dbAdapter->fetchOne($selectLocaliteId);
	}	
		 
	 
	 /**
	  * Permet de recupérer tous les utilisateurs
	  * de l'ACL de la plateforme
	  *
	  * @static
	  * @param   array   les filtres de recherche des utilisateurs
	  * @return  array   la liste des utilisateurs
	  *
	  * @since
	  */
	public function getUsers($filters = array() , $pageNum = 0 , $pageSize = 0)
	{
	 	$userTable         = Sirah_User_Table::getInstance();
	 	$dbAdapter         = $userTable->getAdapter();
	 	$tablePrefix       = $userTable->info("namePrefix");
	 
	 	$selectUsers       = $dbAdapter->select()->from(    array("U" => $userTable->info("name")))
	 	                                         ->join(    array("UR"=> $tablePrefix."system_acl_useroles"),"UR.userid=U.userid",null)
	 	                                         ->join(    array("R" => $tablePrefix."system_acl_roles")   ,"R.roleid=UR.roleid",array("R.rolename"))
												 ->joinLeft(array("L" => $tablePrefix."rccm_localites")     ,"L.localiteid=U.localiteid", array("localite"=>"L.libelle"));	 	
	    if( isset($filters["name"]) && !empty($filters["name"])){
			$likeLastname  = new Zend_Db_Expr("U.lastname  LIKE '%".$filters["name"]."%'");
			$likeFirstname = new Zend_Db_Expr("U.firstname LIKE '%".$filters["name"]."%'");
			$likeMatricule = new Zend_Db_Expr("U.username  LIKE '%".$filters["name"]."%'");
			$selectUsers->where("{$likeLastname} OR {$likeFirstname} OR {$likeMatricule}");
		}
		if( isset($filters["lastname"])  && !empty($filters["lastname"])){
	 		$selectUsers->where("U.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
	 	}	 	
	 	if( isset($filters["firstname"]) && !empty($filters["firstname"])){
	 		$selectUsers->where("U.firstname LIKE ?","%".$filters["firstname"]."%");
	 	}
	 	if( isset($filters["username"])  && !empty($filters["username"])){
	 		$selectUsers->where("U.username LIKE ?","%".$filters["username"]."%");
	 	}	 	
	 	if( isset($filters["email"])     && !empty($filters["email"])){
	 		$selectUsers->where("U.email=?",$filters["email"]);
	 	}
		if( isset($filters["userid"]) && intval($filters["userid"])){
	 		$selectUsers->where("U.userid=?",intval($filters["userid"]));
	 	}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])){
	 		$selectUsers->where("U.localiteid=?",intval($filters["localiteid"]));
	 	}
	 	if( isset($filters["country"]) && !empty($filters["country"]) ){
	 		$selectUsers->where("U.country=?",$filters["country"]);
	 	}
	 	if( isset($filters["language"]) && !empty($filters["language"])){
	 		$selectUsers->where("U.language=?",$filters["language"]);
	 	}
	 	if( isset($filters["phone"]) && !empty($filters["phone"])){
	 		$selectUsers->where($dbAdapter->quote("U.phone1=".$filters["phone"]." OR U.phone2 =".$filters["phone"]));
	 	}
	 	if( isset($filters["expired"]) && ($filters["expired"]==1 || $filters["expired"]==0)){
	 		$selectUsers->where("U.expired=?",intval($filters["expired"]));
	 	}
	 	if( isset($filters["activated"]) && ($filters["activated"]==1 || $filters["activated"]==0)){
	 		$selectUsers->where("U.activated=?",intval($filters["activated"]));
	 	}
	 	if( isset($filters["blocked"]) && ($filters["blocked"]==1 || $filters["blocked"]==0)){
	 		$selectUsers->where("U.blocked=?",intval($filters["blocked"]));
	 	}
	 	if( isset($filters["locked"]) && ($filters["locked"]==1 || $filters["locked"]==0)){
	 		$selectUsers->where("U.locked=?",intval($filters["locked"]));
	 	}
	 	if( isset($filters["admin"]) && ( $filters["admin"]>=1 ) ){
	 		$selectUsers->where("U.admin=?",intval($filters["admin"]));
	 	}
	 	if( isset($filters["rolename"]) && !empty($filters["rolename"])){
	 		$selectUsers->where("R.rolename=?" ,  $filters["rolename"] );
	 	}
		if( isset($filters["roles"]) && is_array($filters["roles"]) && count($filters["roles"])) {
			$selectUsers->where("R.rolename IN(?)",array_map("strip_tags",$filters["roles"]))
			            ->orWhere("R.roleid IN(?)",array_map("intval"    ,$filters["roles"]));
		}
	 	if( isset($filters["roleid"]) && intval($filters["roleid"]) ){
	 		$selectUsers->where("R.roleid=?" , intval( $filters["roleid"] ) );
	 	}
	 	$selectUsers->group(array("U.userid"))->order(array("U.lastConnectedDate DESC","R.accesslevel ASC"));
	 	if( intval($pageNum) && intval($pageSize)) {
	 		$selectUsers->limitPage($pageNum , $pageSize);
	 	}	 
	 	//print_r( $selectUsers->__toString()); die();	
	 	return $dbAdapter->fetchAll($selectUsers , array() , Zend_Db::FETCH_ASSOC);
	 }	 
  }