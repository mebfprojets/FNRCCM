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
 * Cette classe permet de gérer les roles des utilisateurs
 * de la plateforme basée sur SIRAH
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */

class Sirah_User_Acl extends Zend_Acl 
{
	
	/**
	 * L'identifiant de l'utilisateur concerné
	 *
	 * @var integer
	 */
	protected $_userid = 0;
	
	/**
	 * Un tableau des registres de roles
	 *
	 * @var array
	 */
	protected $_userRoleRegistries = array ();
	
	/**
	 * Un tableau des instances de l'ACL
	 *
	 * @var array Sira_Acl instances
	 */
	protected static $_instances = null;
	
	/**
	 * Le constructeur de la classe
	 *
	 * @param $userid integer
	 *       	 l'identifiant de l'utilisateur
	 *       	
	 * @param $options array
	 *       	 Les options de la classe
	 *       	
	 * @return void
	 */
	protected function __construct($userid = 0, $config = array()) 
	{
		$this->_userid = $userid;
		if(isset($config ["loadDynamicData"]) && $config["loadDynamicData"] == true) {
			$this->addDynamicRoles();
			$this->addDynamicResources();
			$this->_addDynamicRules();
			$this->_addUsernameRight();
		}
	}
	
	/**
	 * Permet de récupérer une instance de l'ACL
	 *
	 * @static
	 *
	 * @param $userid integer       	
	 * @return Sirah_User_Acl
	 */
	public function getInstance($userid, $params=array()) 
	{
		if(! isset (self::$_instances [$userid] )) {
			self::$_instances [$userid] = new self($userid,$params );
		}
		return self::$_instances [$userid];
	}
	
	/**
	 * Permet de mettre à jour l'identifiant de l'utilisateur
	 * concerné par l'ACL
	 * 
	 * @param $userid integer       	
	 * @return void
	 */
	public function setUserid($userid) 
	{
		$this->_userid = $userid;
	}
	
	/**
	 * Permet de récupérer l'identifiant de l'utilisateur
	 * concerné par l'ACL
	 *
	 * @return void
	 */
	public function getUserid() 
	{
		return $this->_userid;
	}
	
	/**
	 * Permet de récupérer le nom d'utilisateur
	 * sans certains caractères
	 *
	 * @return void
	 */
	public function getCleanUsername() 
	{
		$userTable = Sirah_User_Table::getInstance($this->_userid);
		if(! $userTable->userid) {
			return null;;
		}
		$cleanUsername = (!empty ($userTable->username ))?preg_replace ("/[\@\s+\.,]/", "", $userTable->username ) : preg_replace ("/[\@\s+\.,]/", "", $userTable->email );
		return $cleanUsername;
	}
	
	/**
	 * Permet de vérifier si un role est autorisé ou non à accéder à un objet
	 * du système.
	 * Les paramètres $role et $resource doivent etre des références ou des
	 * identifiants
	 * pour un role et une ressource existantes
	 *
	 * @param $ruleRegexSubject string       	
	 * @param $role Zend_Acl_Role_Interface|string       	
	 * @param $resource Zend_Acl_Resource_Interface|string       	
	 * @param $privilege string       	
	 * @param $useDbTable integer       	
	 * @uses  Zend_Acl::get()
	 * @uses  Zend_Acl_Role_Registry::get()
	 * @return boolean
	 */
	public function isAuthorized($resource = null, $privilege = null, $role = null, $ruleRegexSubject= null, $useDbTable = 0) 
	{	
		if( null!= $ruleRegexSubject && !empty($ruleRegexSubject )){
			$ruleRegex = "`([a-zA-Z\*]+):([a-zA-Z\*]+)\/([a-zA-Z\*]+)`";
			if(preg_match($ruleRegex,$ruleRegexSubject,$matches )) {
				if(isset($matches [1] ) && ! empty($matches[1] )) {
					$roleSyntax = $matches [1];
					switch ($roleSyntax) {
						case "all" :
						case "myroles":
						case "*" :
							$role = null;
							break;
						default :
							$role = $matches [1];
							break;
					}
				}
				if(isset ($matches [2] )) {
					$resourceSyntax = $matches [2];
					switch ($resourceSyntax) {
						case "all" :
						case "*" :
							$resource = null;
							break;
						default :
							$resource = $matches [2];
							break;
					}
				}
				if(isset ($matches [3] )) {
					$privilegeSyntax = $matches [3];
					switch ($privilegeSyntax) {
						case "all" :
						case "*" :
							$privilege = null;
							break;
						default :
							$privilege = $matches [3];
							break;
					}
				}
			}
		}		

		if(null!==$resource && !$this->has($resource)){
			return false;
		}
		if(null!==$role && !$this->hasRole($role) && !intval($useDbTable )) {
			return false;
		} elseif(null!==$role && !$this->hasRole($role) && intval($useDbTable )) {
			$resourceName = ($resource instanceof Zend_Acl_Resource_Interface) ? $resource->getResourceId() : $resource;
			$roleName     = ($role instanceof Zend_Acl_Role_Interface) ? $role->getRoleId() : $role;
			return Sirah_User_Acl_Table::isAllowed(0, $roleName, $resourceName, $privilege );
		}
		
		if(null===$role){
			$roles  = $this->getRoles();
			if(!empty($roles)){
			    foreach( $roles as $roleName ){
				     if( $this->isAllowed( $roleName , $resource , $privilege ) ){
					      return true;
				     }
			    }
			}
		}		
		return false;
	}
	
	/**
	 * Permet d'insérer une règle d'autorisation dans la base de données
	 *
	 * @param $allowPregSubject string       	
	 * @param $roles Zend_Acl_Role_Interface|string|array       	
	 * @param $resources Zend_Acl_Resource_Interface|string|array       	
	 * @param $privileges string|array       	
	 * @param $assert Zend_Acl_Assert_Interface       	
	 * @param $updateTable bool       	
	 * @uses Zend_Acl::setRule()
	 * @return Zend_Acl Provides a fluent interface
	 */
	public function allow($roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null,$allowPregSubject = null, $updateTable = 0) 
	{
		if(null !== $allowPregSubject && ! empty ($allowPregSubject )) {
			$allowRegex = "`([a-zA-Z\*]+):([a-zA-Z\*]+)\/([a-zA-Z\*]+)`";
			if(preg_match ($allowRegex, $allowPregSubject, $matches )) {
				if(isset ($matches [1] ) && ! empty ($matches [1] )) {
					$roleSyntax = $matches [1];
					switch ($roleSyntax) {
						case "all" :
						case "*" :
							$roles = null;
							break;
						default :
							$roles = explode (',', $matches [1] );
							break;
					}
				}
				if(isset ($matches [2] )) {
					$resourceSyntax = $matches [2];
					switch ($resourceSyntax) {
						case "all" :
						case "*" :
							$resources = null;
							break;
						default :
							$resources = explode (',', $matches [2] );
							break;
					}
				}
				if(isset ($matches [3] )) {
					$privilegeSyntax = $matches [3];
					switch ($privilegeSyntax) {
						case "all" :
						case "*" :
							$privileges = null;
							break;
						default :
							$privileges = explode (',', $matches [3] );
							break;
					}
				}
			}
		}
		parent::allow($roles , $resources , $privileges, $assert );		
		if(intval($updateTable)) {
			$tableRoles     = (null === $roles) ? array () : $roles;
			$tableResources = (null === $resources) ? array () : $resources;
			$tableObjects   = (null === $privileges) ? array () : $privileges;
			
			if(! is_array ($tableRoles )) {
				if($roles instanceof Zend_Acl_Role_Interface) {
					$roles      = $roles->getRoleId ();
				}
				$tableRoles     = array ($roles );
			}
			if(! is_array ($tableResources )) {
				if($resources instanceof Zend_Acl_Resource_Interface) {
					$resources  = $resources->getResourceId ();
				}
				$tableResources = array ($resources );
			}
			Sirah_User_Acl_Table::allow(array(), $tableRoles, $tableResources, $tableObjects );
		}
		return $this;
	}
	
	/**
	 * Permet d'insérer une règle d'interdiction d'accès
	 * à une ou des ressources dans la base de données
	 *
	 * @param $allowPregSubject string       	
	 * @param $roles Zend_Acl_Role_Interface|string|array       	
	 * @param $resources Zend_Acl_Resource_Interface|string|array       	
	 * @param $privileges string|array       	
	 * @param $assert Zend_Acl_Assert_Interface       	
	 * @param $updateTable bool       	
	 * @uses Zend_Acl::setRule()
	 * @return Zend_Acl Provides a fluent interface
	 */
	public function deny($roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null, $allowPregSubject = null, $updateTable = 0) 
	{
		if(null !== $allowPregSubject && ! empty ($allowPregSubject )) {
			$allowRegex = "`([a-zA-Z\*]+):([a-zA-Z\*]+)\/([a-zA-Z\*]+)`";
			if(preg_match ($allowRegex, $allowPregSubject, $matches )) {
				if(isset ($matches [1] ) && ! empty ($matches [1] )) {
					$roleSyntax = $matches [1];
					switch ($roleSyntax) {
						case "all" :
						case "*" :
							$roles = null;
							break;
						default :
							$roles = explode (',', $matches [1] );
							break;
					}
				}
				if(isset ($matches [2] )) {
					$resourceSyntax = $matches [2];
					switch ($resourceSyntax) {
						case "all" :
						case "*" :
							$resources = null;
							break;
						default :
							$resources = explode (',', $matches [2] );
							break;
					}
				}
				if(isset ($matches [3] )) {
					$privilegeSyntax = $matches [3];
					switch ($privilegeSyntax) {
						case "all" :
						case "*" :
							$privileges = null;
							break;
						default :
							$privileges = explode (',', $matches [3] );
							break;
					}
				}
			}
		}
		parent::deny($roles, $resources, $privileges, $assert );
		
		if(intval ($updateTable )) {
			$tableRoles = (null === $roles) ? array () : $roles;
			$tableResources = (null === $resources) ? array () : $resources;
			$tableObjects = (null === $privileges) ? array () : $privileges;
			
			if(! is_array ($tableRoles )) {
				if($roles instanceof Zend_Acl_Role_Interface) {
					$roles = $roles->getRoleId ();
				}
				$tableRoles = array ($roles );
			}
			
			if(! is_array ($tableResources )) {
				if($resources instanceof Zend_Acl_Resource_Interface) {
					$resources = $resources->getResourceId ();
				}
				$tableResources = array ($resources );
			}
			Sirah_User_Acl_Table::deny (array (), $tableRoles, $tableResources, $tableObjects );
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter dans le registre la liste des roles enregistrés
	 * dans la base de données concernant l'utilisateur.
	 * 
	 * @param
	 *       	 la liste des roles de l'utilisateur
	 *       	
	 * @param $role Zend_Acl_Role_Interface       	
	 * @param $parents Zend_Acl_Role_Interface|string|array       	
	 * @param $updateTable bool       	
	 * @param $accesslevel integer       	
	 * @uses Zend_Acl_Role_Registry::add()
	 * @return Zend_Acl Provides a fluent interface
	 */
	public function addRole($role, $parents = null, $updateTable = 0, $accesslevel = 10) 
	{
		if($this->hasRole($role)){
			return;
		}
		parent::addRole($role,$parents);
		
		// Au cas ou doit mettre à jour la table des roles, on le réalise
		if(intval ($updateTable ) && ! Sirah_User_Acl_Table::getRoleid ($role )) {
			$rolename = ($role instanceof Zend_Acl_Role_Interface) ? $role->getRoleId () : $role;
			$roleParents = array ();
			if(is_string ($parents )) {
				$roleParents [] = $parents;
			} elseif($parents instanceof Zend_Acl_Role_Interface) {
				$roleParents [] = $parents->getRoleId ();
			} else {
				$roleParents    = $parents;
			}
			
			if(!Sirah_User_Acl_Table::addRole ($rolename, $roleParents, $accesslevel )) {
				throw new Sirah_User_Acl_Exception("Impossible d'enregistrer le role $rolename dans la table des ACLS");
			}
		}
		return $this;
	}
	
	/**
	 * Adds a Resource having an identifier unique to the ACL
	 *
	 * The $parent parameter may be a reference to, or the string identifier
	 * for,
	 * the existing Resource from which the newly added Resource will inherit.
	 *
	 * @param $resource Zend_Acl_Resource_Interface|string       	
	 * @param $parent Zend_Acl_Resource_Interface|string       	
	 * @param
	 *       	 s bool $updateTable
	 * @param $description string       	
	 * @throws Zend_Acl_Exception
	 * @return Zend_Acl Provides a fluent interface
	 */
	public function addResource($resource, $parent = null, $updateTable = 0, $description = "") 
	{
		if($this->has($resource)){
			return;
		}
		parent::addResource($resource,$parent );
				
		if(intval ($updateTable ) && ! Sirah_User_Acl_Table::getResourceid ($resource)) {
			$resourcename = ($resource instanceof Zend_Acl_Resource_Interface) ? $resource->getResourceId ():$resource;
			$parentname   = ($parent instanceof Zend_Acl_Resource_Interface) ? $parent->getResourceId():$parent;
			if(! Sirah_User_Acl_Table::addResource($resourcename, $parentname, $description )) {
				 Sirah_Error::raiseError(0, "Impossible d'enregistrer la ressource $resourcename dans la table des ACLS" );
			}
		}
		return $this;
	}
	
	/**
	 * Permet d'enregistrer le nom d'utilisateur
	 * comme un role de l'utilisateur
	 */
	protected function _addUsernameAsRole() 
	{
		$userTable = Sirah_User_Table::getInstance($this->_userid);
		if(!$userTable->userid) {
			return $this;
		}
		$cleanUsername = (!empty($userTable->username )) ? preg_replace ("/[\@\s+\.,]/", "", $userTable->username ) : preg_replace ("/[\@\s+\.,]/", "", $userTable->email );
		if(!$this->hasRole($cleanUsername )){
			$this->addRole($cleanUsername );
		}
		return $this;
	}
	/**
	 * Permet d'ajouter dans le registre la liste des roles enregistrés
	 * dans la base de données concernant l'utilisateur.
	 * 
	 * @param
	 *       	 la liste des roles de l'utilisateur
	 *       	
	 */
	public function addDynamicRoles($userRoles = array()) 
	{
		$userRoles    = (array) $userRoles;
		$dynamicRoles = (empty($userRoles )) ? Sirah_User_Acl_Table::getRoles($this->_userid) : $userRoles;
		
		if( count($dynamicRoles )) {
			foreach ($dynamicRoles as $roleId => $roleName ) {
				if(!$this->hasRole($roleName )) {
					$roleParents = Sirah_User_Acl_Table::getRoleParents($roleId);
					$parents     = array ();
					if(!empty($roleParents)) {
						foreach($roleParents as $roleParentId => $roleParentName ) {
							//On essaie d'éviter des boucles par rapport à l'héritage
							if(Sirah_User_Acl_Table::hasRoleParent($roleParentId , $roleId)) {
								continue;
							}
							if(!$this->hasRole($roleParentName)) {
								$this->addDynamicRoles(array($roleParentId => $roleParentName));
							}
							$parents [] = $roleParentName;
						}
					}
					$roleInstance = new Zend_Acl_Role($roleName );
					$this->addRole($roleInstance,$parents);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter dans le registre la liste des ressources enregistrées
	 * dans la base de données concernant l'utilisateur.
	 *
	 * @param  la liste des ressources à enregistrer dans le registre de
	 *       	 l'ACL
	 */
	public function addDynamicResources($resources = array()) 
	{
		$resources        = (array)$resources;
		$dynamicResources = (empty($resources)) ? Sirah_User_Acl_Table::getResources():$resources;
		
		if(count ($dynamicResources )) {
			foreach ($dynamicResources as $dynamicResourceid => $dynamicResourcename) {
				$resourceParent     = Sirah_User_Acl_Table::getResourceParent($dynamicResourceid);
				$resourceParentName = isset ($resourceParent ["resourcename"] ) ? $resourceParent ["resourcename"] : null;
				if( null !== $resourceParentName && !$this->has($resourceParentName )) {
					$parentRow      = array( "resourceid"   => $resourceParent["resourceid"],
							                 "resourcename" => $resourceParentName );
					$this->addDynamicResources($parentRow);
				}
				$this->addResource( $dynamicResourcename , $resourceParentName );
			}
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter dans le registre la liste des règles d'autorisation
	 * dans la base de données concernant l'utilisateur.
	 * 
	 */
	protected function _addDynamicRules($roles=array()) 
	{
		$userid       = $this->_userid;
		$dynamicRoles = (empty($roles)) ? Sirah_User_Acl_Table::getRoles($userid) : $roles;
		
		if(count($dynamicRoles)){
			foreach ($dynamicRoles as $roleId => $roleName ){	

				//On enregistre les droits des roles parents d'abord
				$roleParents = Sirah_User_Acl_Table::getRoleParents($roleId);
				if(!empty($roleParents)){
					$this->_addDynamicRules($roleParents);
				}
				/**
				 *  On récupère les autorisations en fonction de leur type 
				 */
				$roleAllowedRights = Sirah_User_Acl_Table::getRoleRights(intval($roleId), 0, 0, 1);
				$roleDeniedRights  = Sirah_User_Acl_Table::getRoleRights(intval($roleId), 0, 0, 0);
				
				if(count($roleAllowedRights )){
					foreach($roleAllowedRights as $allowedRight ) {						
						if($this->isAllowed($roleName,$allowedRight["resourcename"],$allowedRight["objectname"])){
						   continue;	
						}
						if(!$this->has($allowedRight["resourcename"])){
							$this->addResource($allowedRight["resourcename"]);
						}
						if($allowedRight["objectname"] == "all") {
						   $this->allow($roleName,$allowedRight["resourcename"] , array());
						} else {
						   $this->allow($roleName,$allowedRight["resourcename"],$allowedRight["objectname"]);
						}						
					}
				}
				if(count($roleDeniedRights )){
					foreach($roleDeniedRights as $deniedRight) {
						if(!$this->isAllowed($roleName,$allowedRight["resourcename"],$allowedRight["objectname"])){
							continue;
						}
						if(!$this->has($deniedRight["resourcename"])){
							$this->addResource($deniedRight["resourcename"]);
						}
						if($deniedRight["objectname"] == "all") {
						   $this->deny($roleName , $deniedRight["resourcename"] , array());
						} else {
						   $this->deny($roleName , $deniedRight["resourcename"] , $deniedRight["objectname"]);
						}
					}
				}
			}
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter dans le registre la liste des règles d'autorisation
	 * en prenant le nom d'utilisateur comme le role correspondant
	 * 
	 * 
	 */
	protected function _addUsernameRight() 
	{
		$userTable = Sirah_User_Table::getInstance($this->_userid);
		if(!$userTable->userid) {
			return;
		}
		$usernameRole = $this->getCleanUsername();
		if(!$this->hasRole($usernameRole)){
			$this->_addUsernameAsRole();
		}
		/**
		 * * On récupère les autorisations en fonction de leur type **
		 * 
		 */
		$userAllowedRights = Sirah_User_Acl_Table::getUserRights (intval($this->_userid), 0, 0, 1 );
		$userDeniedRights  = Sirah_User_Acl_Table::getUserRights (intval($this->_userid), 0, 0, 0 );
		
		if(count($userAllowedRights)) {
			foreach ($userAllowedRights as $allowedRight ) {
				if(!$this->has($allowedRight["resourcename"])){					
					$this->addResource($allowedRight["resourcename"]);
				}
				if($allowedRight["objectname"] == "all") {
					$this->allow($usernameRole , $allowedRight["resourcename"] , array());
				} else {
					$this->allow($usernameRole , $allowedRight["resourcename"] , $allowedRight["objectname"]);
				}				
			}
		}
		if(count($userDeniedRights)) {
			foreach($userDeniedRights as $deniedRight ){
				if(!$this->has($deniedRight["resourcename"])){
					$this->addResource($deniedRight["resourcename"]);
				}
			    if($deniedRight["objectname"] == "all") {
					$this->allow($usernameRole , $deniedRight["resourcename"] , array());
				} else {
					$this->allow($usernameRole , $deniedRight["resourcename"] , $deniedRight["objectname"]);
				}
				
			}
		}
		return $this;
	}
	
	/**
	 * Permet de récupérer le registre des roles
	 * de l'ACL
	 *
	 *
	 * @return Zend_Acl_Role_Registry
	 */
	protected function _getRoleRegistry() 
	{
		if(!isset($this->_userRoleRegistries [$this->_userid])) {
			$this->_userRoleRegistries [$this->_userid] = new Zend_Acl_Role_Registry ();
		}
		return $this->_userRoleRegistries[$this->_userid];
	}
	
	public function getRules()
	{
		return $this->_rules;
	}

}
