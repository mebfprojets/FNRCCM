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
 * Cette classe correspond à la passerelle table qui permet 
 * de gérer les ACL de la plateforme basée sur SIRAH
 * Elle est consituée de méthodes statiques utiles
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_User_Acl_Table 
{
	
		
  /**
	* Permet d'autoriser un/des utilisateur(s) à acceder à un ou des objets
	*
	* @static
	* @param   array  $userids      Les identifiants des utilisateurs
	* @param   array  $roleids      Les identifiants des roles
	* @param   array  $resourceids  Les ressources concernées par l'autorisation
	* @param   array  $objectids    Les objets concernés par l'autorisation
	*
	* @return  true ou false en fonction du resultat
	*
	*/
	public function allow($userids=array(),$roleids=array(),$resourceids=array(),$objectids=array())
	{
		$userids      = (array)$userids;
		$roleids      = (array)$roleids;
		$resourceids  = (array)$resourceids;
		$objectids    = (array)$objectids;
	
		if( empty($userids)){
			return Sirah_User_Acl_Table::addRightUsingRoles($roleids,$resourceids,$objectids);
		}
		return Sirah_User_Acl_Table::addRightUsingUserids($userids,$resourceids,$objectids);
	}
	
	/**
	 * Permet  de rétirer des permissions à des utilisateurs
	 * ou à des roles utilisateurs
	 *
	 * @static
	 * @param   array  $userids      Les identifiants des utilisateurs
	 * @param   array  $roleids      Les identifiants des roles
	 * @param   array  $resourceids  Les ressources concernées par l'autorisation
	 * @param   array  $objectids    Les objets concernés par l'autorisation
	 *
	 * @return  true ou false en fonction du resultat
	 *
	 */
	public function deny($userids=array(),$roleids=array(),$resourceids=array(),$objectids=array())
	{
		$userids      = (array)$userids;
		$roleids      = (array)$roleids;
		$resourceids  = (array)$resourceids;
		$objectids    = (array)$objectids;
	
		if(empty($userids)){
			return Sirah_User_Acl_Table::addRightUsingRoles($roleids,$resourceids,$objectids,0);
		}
		return Sirah_User_Acl_Table::addRightUsingUserids($userids,$resourceids,$objectids,0);
	}
	
	
	/**
	 * Permet d'ajouter une nouvelle permission à
	 * l'ACL à travers l'identifiant de l'utilisateur. Le type de
	 * permission peut etre allow ou deny (étant dans notre cas des booleens)
	 *
	 * @static
	 * @param   array     $userids      Les identifiants des utilisateurs
	 * @param   array     $resourceids  Les ressources concernées par l'autorisation
	 * @param   array     $objectids    Les objets concernés par l'autorisation
	 * @param   boolean   $allow        indique la permission d'autorisation
	 *
	 * @return  true ou false en fonction du resultat
	 *
	 */
	public function addRightUsingUserids($userids=array(),$resourceids=array(),$objectids=array(),$allow=1)
	{
		$userids           = (array)$userids;
		$resourceids       = (array)$resourceids;
		$objectids         = (array)$objectids;
	
		if(empty($userids) || (empty($resourceids) && empty($objectids))){
			Sirah_Error::raiseError(0,"Impossible d'enregistrer des permissions car les paramètres fournis sont invalides ");
			return false;
		}
		if(empty($resourceids)){
			$resourceids         = Sirah_User_Acl_Table::getResourcesHasObjects($objectids);
		}	
		foreach($resourceids as $resourceid){
			$rightResourceid     = 0;
			$rightObjectid       = 0;
			$rightUserid         = 0;
			 
			if(isset($resourceid["resourceid"])){
				$rightResourceid = intval($resourceid["resourceid"]);
			} elseif(!is_numeric($resourceid)) {
				$rightResourceid = intval(Sirah_User_Acl_Table::getAclResourceid($resourceid));
			} else {
				$rightResourceid = intval($resourceid);
			}
	
			if(!Sirah_User_Acl_Table::getAclResourcename($rightResourceid)){
				continue;
			}
			if(empty($objectids)){
				$resourceObjects      = Sirah_User_Acl_Table::getAclObjects($rightResourceid);
				if(!empty($resourceObjects)){
					foreach($reourceObjects as $resourceObject){
						$objectids[]  = $resourceObject["objectid"];
					}
				} else {
					continue;
				}
			}
			foreach($objectids as $objectid){
				if(isset($objectid["objectid"])){
					$rightObjectid = intval($objectid["objectid"]);
				} elseif(!is_numeric($objectid)) {
					$rightObjectid = intval(Sirah_User_Acl_Table::getAclObjectid($objectid , $rightResourceid ));
				} else {
					$rightObjectid = intval($objectid);
				}	
				if(!Sirah_User_Acl_Table::getAclObjectname($rightObjectid)){
					continue;
				}
				foreach($userids as $userid){
					$rightUserid  = (isset($userid["userid"]))?intval($userid["userid"]):intval($userid);	
					$aclRight     = array( "userid"    => intval($rightUserid),
							               "roleid"    => 0,
							               "objectid"  => intval($rightObjectid),
							               "allow"     => intval($allow));
					
					if(!Sirah_User_Acl_Table::existAclRight($aclRight)){
						if(!Sirah_User_Acl_Table::addRight($acl)){
							Sirah_Error::raiseError(0,"L'insertion de la permission #{".$rightUserid."}-{".$rightObjectid."}-{".intval($allow)."} a echoué ");
						}
					}
				}
			}
		}
	
	}
	
	/**
	 * Permet d'ajouter une nouvelle permission à
	 * l'ACL à travers l'identifiant de l'utilisateur. Le type de
	 * permission peut etre allow ou deny (étant dans notre cas des booleens)
	 *
	 * @static
	 * @param   array     $userids      Les identifiants des utilisateurs
	 * @param   array     $resourceids  Les ressources concernées par l'autorisation
	 * @param   array     $objectids    Les objets concernés par l'autorisation
	 * @param   boolean   $allow        indique la permission d'autorisation
	 *
	 * @return  true ou false en fonction du resultat
	 *
	 */
	public function addRightUsingRoles($roleids=array(),$resourceids=array(),$objectids=array(),$allow=1)
	{
		$roleids           = (array)$roleids;
		$resourceids       = (array)$resourceids;
		$objectids         = (array)$objectids;
		$rights            = array();
	
		if(empty($roleids) || (empty($resourceids) && empty($objectids))){
			throw new Sirah_User_Acl_Exception("Impossible d'enregistrer des permissions car les paramètres fournis sont invalides ");
			return false;
		}
		if(empty($resourceids)){
			$resourceids       = Sirah_User_Acl_Table::getResourcesHasObjects($objectids);
		}	
		foreach($resourceids as $resourceid){
			$rightResourceid     = 0;
			$rightObjectid       = 0;
			$rightUserid         = 0;
			 
			if(isset($resourceid["resourceid"])){
				$rightResourceid = intval($resourceid["resourceid"]);
			} elseif (!is_numeric($resourceid)){
				$rightResourceid = intval(Sirah_User_Acl_Table::getAclResourceid($resourceid));
			} else {
				$rightResourceid = intval($resourceid);
			}
				
			if(!Sirah_User_Acl_Table::getAclResourcename($rightResourceid)){
				continue;
			}
			if(empty($objectids)){
				$resourceObjects      = Sirah_User_Acl_Table::getAclObjects($rightResourceid);
				if(count($resourceObjects)){
					foreach($reourceObjects as $resourceObject){
						$objectids[]  = $resourceObject["objectid"];
					}
				}
				else{
					continue;
				}
			}
			foreach($objectids as $objectid){
				if(isset($objectid["objectid"])){
					$rightObjectid = intval($objectid["objectid"]);
				} elseif(!is_numeric($objectid)) {
					$rightObjectid = intval(Sirah_User_Acl_Table::getAclObjectid($objectid , $rightResourceid));
				} else{
					$rightObjectid = intval($objectid);
				}	
				if(!Sirah_User_Acl_Table::getAclObjectname($rightObjectid)){
					continue;
				}
				foreach($roleids as $roleid){
					$rightRoleid  = (isset($roleid["roleid"]))?intval($roleid["roleid"]):intval($roleid);
						
					$aclRight     = array( "userid"    => 0,
							               "roleid"    => intval($rightRoleid),
							               "objectid"  => intval($rightObjectid),
							               "allow"     => intval($allow));
						
					if(!Sirah_User_Acl_Table::rightExist($aclRight)){
						if(!Sirah_User_Acl_Table::addRight($aclRight)){
							Sirah_Error::raiseWarning(0,"L'insertion de la permission #{". $rightRoleid."}-{".$rightObjectid."}-{".intval($allow)."} a echoué ");
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Permet de vérifier si une ligne de permission existe déjà
	 * dans la table des permissions de l'ACL
	 *
	 * @static
	 *
	 * @return boolean true or false
	 * @since
	 */
	public function rightExist($right=array())
	{
		if(!isset($right["userid"]) || !isset($right["roleid"]) || !isset($right["objectid"])){
			Sirah_Error::raiseError(0,"Les paramètres de la permission que vous souhaitez enregistrer, sont invalides");
			return false;
		}
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
		
		$rightUser         = intval($right["userid"]);
		$rightRole         = (!is_numeric($right["roleid"]))  ? Sirah_User_Table::getRoleid($right["roleid"])    : intval($right["roleid"]);
		$rightObject       = (!is_numeric($right["objectid"]))? Sirah_User_Table::getObjectid($right["objectid"]): intval($right["objectid"]);
		$rightType         = isset($right["allow"])           ? intval($right["allow"])  : 1;
		
		$rightSelect       = $dbAdapter->select()->from( $tableNamePrefix."system_acl_rights")
		                                         ->where("objectid = ?",intval($right["objectid"]))
		                                         ->where("roleid   = ?",intval($right["roleid"]))
		                                         ->where("userid   = ?",intval($right["userid"]))
		                                         ->where("allow    = ?",$rightType);		
		return $dbAdapter->fetchOne($rightSelect);				
	}
	
	
	
	/**
	 * Permet d'enregistrer un role
	 * dans la table des permissions de l'ACL
	 *
	 * @static
	 * @param   string    $rolename      le nom du role
	 * @param   integer   $accesslevel   le niveau du role
	 * @param   array     $parents
	 *
	 * @return boolean true or false
	 * @since
	 */
	public function addRole($rolename , $parents=array() , $accesslevel=10)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
		$creator           = Sirah_Fabric::getUser();
		
		$insertData        = array(
				                   "rolename"      => $rolename,
				                   "accesslevel"   => $accesslevel,
				                   "creationdate"  => time(),
				                   "creatoruserid" => $creator->id);		
		if($dbAdapter->insert($tableNamePrefix."system_acl_roles",$insertData)){
			$roleId  = $dbAdapter->lastInsertId();
			if(!empty($parents)){
				foreach($parents as $parent){
					$parentId  = Sirah_User_Acl_Table::getRoleid($parent);
					if($parentId && !Sirah_User_Acl_Table::hasRoleParent($parentId,$roleId)){
						$dbAdapter->insert($tableNamePrefix."system_acl_parentroles",array("childroleid"   => $roleId,
								                                                           "parentroleid"  => $parentId,
								                                                           "creationdate"  => time(),
								                                                           "creatoruserid" => $creator->id
								                                                               ));
					}
				}
			}
			return $roleid;
		}
		return false;
	}
	
	
	
	/**
	 * Permet d'enregistrer une ressource
	 * dans la table des ressources
	 *
	 * @static
	 * @param   string    $resourcename      le nom de la ressource
	 * @param   string    $parent            le nom de la ressource parente
	 * @parent  string    $description       La description de la ressource
	 *
	 * @return boolean true or false
	 * @since
	 */
	public function addResource($resourcename,$parent=null,$description="")
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
		$creator           = Sirah_Fabric::getUser();
	
		$insertData        = array(
				                   "resourcename"  => $resourcename,
				                   "parentid"      => (!is_numeric($parent))?Sirah_User_Acl_Table::getResourceid($parent):$parent,
				                   "description"   => $description,
				                   "creationdate"  => time(),
				                   "creatoruserid" => $creator->id);	
		if($dbAdapter->insert($tableNamePrefix."system_acl_resources",$insertData)){
			return $dbAdapter->lastInsertId();			
		}
		return false;
	}
	
	/**
	 * Permet d'enregistrer une permission
	 * dans la table des permissions de l'ACL
	 *
	 * @static
	 *
	 * @return boolean true or false
	 * @since
	 */
	public function addRight($right=array())
	{
		if(!isset($right["userid"]) || !isset($right["roleid"]) || !isset($right["objectid"]) || !isset($right["allow"])){
			Sirah_Error::raiseError(0,"Les paramètres de la permission que vous souhaitez enregistrer, sont invalides");
			return false;
		}
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
		$creator           = Sirah_Fabric::getUser();
	
		$rightUser         = intval($right["userid"]);
		$rightRole         = intval($right["roleid"]);
		$rightObject       = intval($right["objectid"]);
		$rightAllow        = intval($right["allow"]);		
		$insertData        = array("userid"        => $rightUser,
				                   "roleid"        => $rightRole,
				                   "objectid"      => $rightObject,
				                   "allow"         => $rightAllow,
				                   "creationdate"  => time(),
				                   "creatoruserid" => $creator->id);
		return $dbAdapter->insert($tableNamePrefix."system_acl_rights",$insertData);			
	 }
	 
	 
	 /**
	  * Permet de supprimer une permission
	  * dans la table des permissions de l'ACL
	  *
	  * @static
	  *
	  * @return boolean true or false
	  * @since
	  */
	 public function removeRight($right=array())
	 {
	 	if(!isset($right["userid"]) || !isset($right["roleid"]) || !isset($right["objectid"])){
	 		Sirah_Error::raiseError(0,"Les paramètres de la permission que vous souhaitez enregistrer, sont invalides");
	 		return false;
	 	}
	 	$userTable         = Sirah_User_Table::getInstance();
	 	$dbAdapter         = $userTable->getAdapter();
	 	$tableNamePrefix   = $userTable->info("namePrefix");
	 
	 	$rightWhere        = array(
	 	                             "userid     =".intval($right['userid']),
	 	                             "roleid     =".intval($right['roleid']),
	 	                             "objectid   =".intval($right['objectid']));
	 
	 	$deleteQuery       = " DELETE FROM ".$tableNamePrefix."system_acl_rights ";	 	
	 	$deleteQuery      .= " WHERE ".implode( " AND ",$rightWhere);
	 	
	 	$stmt              = $dbAdapter->query($deleteQuery);
	 	$result            = $stmt->rowCount();
	 	return $result;
	 }
	 
	 
	 /**
	  * Permet de recupérer les roles associés à l'utilisateur
	  *
	  * @static
	  * 
	  * @param   string  $filter
	  * 
	  * @return  array   la liste de tous les roles de l'application
	  *
	  * @since
	  */
	 public function getAllRoles($filter = null)
	 {
	 	$userTable         = Sirah_User_Table::getInstance();
	 	$dbAdapter         = $userTable->getAdapter();
	 	$tableNamePrefix   = $userTable->info("namePrefix");
	 
	 	$selectRoles       = $dbAdapter->select()->from(array("R"  => $tableNamePrefix."system_acl_roles"),array("R.roleid" , "R.rolename")); 	

	 	if(null!==$filter && !empty($filter)) {
	 	   $selectRoles->where("R.rolename LIKE ?","%".$filter."%");
	 	}	 	
	 	$selectRoles->order(array("R.accesslevel ASC"));
	    
	 	$rolesRows         = $dbAdapter->fetchPairs($selectRoles);
	 	return $rolesRows;
	 }
	 
	 
	 /**
	  * Permet de recupérer le niveau d'accès du role
	  *
	  * @static
	  * @param   integer $roleid
	  * @return  integer 
	  *
	  */
	 public function getAccesslevel($roleid=0)
	 {
	 	$userTable         = Sirah_User_Table::getInstance();
	 	$dbAdapter         = $userTable->getAdapter();
	 	$tableNamePrefix   = $userTable->info("namePrefix");
	 
	 	$select            = $dbAdapter->select()->from(array("R"  => $tableNamePrefix."system_acl_roles"),array("R.accesslevel"))
	 	                                         ->where("R.roleid = ? ",intval($roleid));
	 	$accessLevel       = $dbAdapter->fetchCol($select);
	 	return $accessLevel;
	 }
	 
	 /**
	  * Permet de recupérer le role sous forme d'objet
	  *
	  * @static
	  * 
	  * @param   integer $roleid    L'identifiant du role.
	  *
	  * @since
	  */
	 public function getRole( $roleid )
	 {
	 	$userTable         = Sirah_User_Table::getInstance();
	 	$dbAdapter         = $userTable->getAdapter();
	 	$tableNamePrefix   = $userTable->info("namePrefix");
	 
	 	$selectRole        = $dbAdapter->select()->from(array("R" => $tableNamePrefix."system_acl_roles"))
	 	                                         ->where("R.roleid = ?",intval($roleid));
	 	
	 	return  $dbAdapter->fetchRow($selectRole);
	 }
	
	/**
	 * Permet de recupérer les roles associés à l'utilisateur
	 *
	 * @static
	 * @param   integer $userid    L'identifiant de l'utilisateur.
	 * @return  array   la liste des roles associés à l'utilisateur
	 *
	 * @since
	 */
	static public function getRoles($userid)
	{
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
	
		$selectRoles       = $dbAdapter->select()->from(array("UR" => $tableNamePrefix."system_acl_useroles"),array("UR.roleid"))
		                                         ->join(array("R"  => $tableNamePrefix."system_acl_roles"),"R.roleid=UR.roleid",array("R.rolename"))
		                                         ->where("UR.userid=?",intval($userid))
		                                         ->order(array("R.accesslevel ASC"));
		$rolesRows         = $dbAdapter->fetchPairs($selectRoles);
		return $rolesRows;
	}
	
	/**
	 * Permet de vérifier si l'utilisateur a un role
	 * correspondant au paramètre fourni
	 *
	 * @static
	 * @param   integer $userid    L'identifiant de l'utilisateur.
	 * @param   integer $roleid    L'identifiant du role
	 * @return  boolean true or false
	 *
	 * @since
	 */
	static public function hasRole( $userid , $roleid )
	{
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
		
		if(!is_numeric($roleid)){
			$roleid        = Sirah_User_Acl_Table::getRoleid( $roleid );
		}	
		$selectRole       = $dbAdapter->select()->from(array("UR" => $tableNamePrefix."system_acl_useroles"),array("UR.roleid"))
		                                        ->join(array("R"  => $tableNamePrefix."system_acl_roles"),"R.roleid=UR.roleid",array("R.rolename"))
		                                        ->where("UR.userid=?", intval($userid))
		                                        ->where("UR.roleid=?", intval($roleid));
		$roleRow          = $dbAdapter->fetchRow($selectRole);
		return (!empty($roleRow));
	}
	
	
	/**
	 * Permet de recupérer les roles parents d'un role bien donné
	 *
	 * @static
	 * @param   integer $roleid      L'identifiant du role.
	 * @return  array   la liste des roles parents
	 *
	 * @since
	 */
	static public function getRoleParents($roleid)
	{
		$userTable       = Sirah_User_Table::getInstance();
		$dbAdapter       = $userTable->getAdapter();
		$tableNamePrefix = $userTable->info("namePrefix");
				
		if(!is_numeric($roleid)){
			$roleid        = Sirah_User_Acl_Table::getRoleid($roleid);
		}	
		$selectParents   = $dbAdapter->select()
		                                     ->from(array("R" => $tableNamePrefix."system_acl_roles"),array("R.roleid","R.rolename"))
		                                     ->join(array("P" => $tableNamePrefix."system_acl_parentroles"),"P.parentroleid=R.roleid",null)
		                                     ->where("P.childroleid=?",intval($roleid))
		                                     ->order(array("R.accesslevel ASC")); 
		return $dbAdapter->fetchPairs($selectParents);
	}
	
	
	/**
	 * Permet de recupérer les ressources parentes d'une ressource bien donnée
	 *
	 * @static
	 * @param   integer $resourceid      L'identifiant de la ressource.
	 * @return  array   la liste des ressources parentes
	 *
	 * @since
	 */
	static public function getResourceParent($resourceid)
	{
		$userTable       = Sirah_User_Table::getInstance();
		$dbAdapter       = $userTable->getAdapter();
		$tableNamePrefix = $userTable->info("namePrefix");
		
		if(!is_numeric($resourceid)){
			$resourceid  = Sirah_User_Acl_Table::getResourceid($resourceid);
		}
	
		$selectParent    = $dbAdapter->select()
		                             ->from(array("P" => $tableNamePrefix."system_acl_resources"),array( "P.resourceid","P.resourcename"))
		                             ->where("P.resourceid IN ( SELECT R.parentid FROM `".$tableNamePrefix."system_acl_resources` R
		                                      		             WHERE R.resourceid=".intval($resourceid)." )");
		                                      
		$parentResource  = $dbAdapter->fetchRow($selectParent);
		return $parentResource;
	}
		
	/**
	 * Permet de vérifier si un role a un parent
	 *
	 * @static
	 * @param   integer  $roleid      L'identifiant du role.
	 * @param   integer  $parentid    L'identifiant du role parent.
	 * @return  bool true ou false
	 *
	 * @since
	 */
	static public function hasRoleParent($roleid,$parentid=0)
	{
		$userTable       = Sirah_User_Table::getInstance();
		$dbAdapter       = $userTable->getAdapter();
		$tableNamePrefix = $userTable->info("namePrefix");
	
		$selectParent    = $dbAdapter->select()->from(array("P" => $tableNamePrefix."system_acl_parentroles"),array("roleid"=>"P.parentroleid"))
		                                       ->join(array("R" => $tableNamePrefix."system_acl_roles"),"R.roleid=P.parentroleid",array("R.rolename"))
		                                       ->where("P.childroleid=?",intval($roleid));
		if(intval($parentid)){
			$selectParent->where("P.parentroleid=?",intval($parentid));
		}
		$selectParent->order(array("R.accesslevel ASC"));
		
		$parentRole     = $dbAdapter->fetchOne($selectParent);
		return $parentRole;
	}
	
	
	/**
	 * Permet de vérifier si une resource a un parent
	 *
	 * @static
	 * @param   integer  $resourceid      L'identifiant de la ressource.
	 * @param   integer  $parentid        L'identifiant de la resource parente(optionnel).
	 * @return  bool true ou false
	 *
	 * @since
	 */
	static public function hasResourceParent($resourceid,$parentid=0)
	{
		$userTable       = Sirah_User_Table::getInstance();
		$dbAdapter       = $userTable->getAdapter();
		$tableNamePrefix = $userTable->info("namePrefix");
	
		$selectParent    = $dbAdapter->select()->from(array("P" => $tableNamePrefix."system_acl_resources"),array("P.resourceid","P.resourcename"))		                             
		                                       ->where("P.resourceid IN ( SELECT R.parentid FROM `".$tableNamePrefix."system_acl_resources` R
		                                      		                                        WHERE R.resourceid=".$resourceid.") ");
		$parentResource  = $dbAdapter->fetchOne($selectParent);
		return $parentResource;
	}
	
	/**
	 * Permet de recupérer les roles parents d'un role bien donné
	 *
	 * @static
	 * @param   array   la liste des objets pour les ressources recherchées
	 * @return  array   la liste des ressources retrouvées
	 *
	 * @since
	 */
	public function getResourcesHasObjects($resourceobjects=array())
	{
		if(empty($resourceobjects)){
			return array();
		}
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tableNamePrefix   = $userTable->info("namePrefix");
	
		$selectResources   = $dbAdapter->select()->from(array("R" => $tablePrefix."system_acl_resources"),array("R.resourcename","R.resourceid"))
		                                         ->join(array("O" => $tablePrefix."system_acl_objects"),"O.resourceid=R.resourceid",null);
	
		$selectResources->where("O.objectid IN ( ".implode(" OR ",$resourceobjects)." ) ")
		                ->orWhere("O.objectname IN ( ".implode(" OR ",$resourceobjects)." ) ");
			
		return $dbAdapter->fetchPairs($selectResources);
	}
		
	/**
	 * Permet de recupérer toutes les ressources
	 * sur lesquelles sont autorisées l'utilisateur
	 *
	 * @static
	 * @param   integer $userid      L'identifiant de l'utilisateur.
	 * @return  array   la liste des  ressources autorisées à l'utilisateur
	 *
	 * @since
	 */
	static public function getAllowedResources($userid)
	{
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectResources   = $dbAdapter->select()->from(array("R" => $tablePrefix."system_acl_resources"),array("R.resourceid","R.resourcename"))
		                                         ->join(array("O" => $tablePrefix."system_acl_objects"),"O.resourceid=R.resourceid",null)
		                                         ->join(array("RG"=> $tablePrefix."system_acl_rights"),"RG.objectid=O.objectid",null)
		                                         ->where("  RG.userid = ".intval($userid)." OR
		                                         		
		                                         		   (RG.roleid IN (SELECT R2.roleid FROM `".$tablePrefix."system_acl_useroles` R2 
		                                         		                    WHERE  R2.userid=".intval($userid)." )) OR
		                                         		
		                                         		   (RG.roleid IN (SELECT R3.parentroleid FROM `".$tablePrefix."system_acl_parentroles` R3
		                                         		                    WHERE R3.childroleid IN (SELECT R4.roleid FROM `".$tablePrefix."system_acl_useroles` R4
		                                         		                                              WHERE R4.userid=".intval($userid).")))		                                         		
		                                         		     ");		                                         
		                                         		               
		$resources         = $dbAdapter->fetchPairs($selectResources);
		return $resources;
	}
	
	
	/**
	 * Permet d'attribuer des roles à un utilisateur 
	 *
	 * @static
	 * @param   integer $userid      L'identifiant de l'utilisateur .
	 * @param   array   $roleids     Les identifiants des roles à assigner
	 *                                
	 * @return  true ou false en fonction du résultat de l'assignation
	 *
	 * @since
	 */
	static public function assignRoleToUser($userid , $roles = array())
	{
		$userid            = intval($userid);
		$roles             = (array)$roles;
		$creator           = Sirah_Fabric::getUser();		
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
		$result            = true;
		$assignationData   = array( "userid"=> $userid,"roleid"  => 0,"creationdate"=> time(),"creatoruserid"  => $creator->userid);		
		if(!$userTable->userid){
			return false;
		}		
		if( count(  $roles)){
			foreach($roles as $roleid){
					if(!is_numeric($roleid)){
						$roleid    = Sirah_User_Acl_Table::getRoleid($roleid);
					}
					if(Sirah_User_Acl_Table::getRolename($roleid)){
						$assignationData["roleid"] = $roleid;
						$dbAdapter->delete(    $tablePrefix."system_acl_useroles", "userid=".$userid." AND roleid=".$roleid );
						if(!$dbAdapter->insert($tablePrefix."system_acl_useroles", $assignationData)){
							$result = false;
						}
					}			 	
			}
		}
		return $result;
	}
	
	
	/**
	 * Permet de rétirer des roles pour un utilisateur
	 *
	 * @static
	 * @param   integer $userid      L'identifiant de l'utilisateur .
	 * @param   array   $roleids     Les identifiants des roles à assigner
	 *
	 *
	 * @since
	 */
	static public function unAssignRoles($userid , $roles = array())
	{
		$userid            = intval($userid);
		$roles             = (array)$roles;
		$creator           = Sirah_Fabric::getUser();
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
		$result            = true;
		$assignationData   = array( "userid" => $userid,"roleid" => 0,"creationdate"   => time(),"creatoruserid"  => $creator->userid);
		if(!$userTable->userid){
			return false;
		}
		if(empty($roles)) {
			$roles        = Sirah_User_Acl_Table::getRoles($userid);
			$roles        = (!empty($roles)) ? array_keys($roles) : array();
		}
		if(count($roles)){
			foreach($roles as $roleid){
				if(!is_numeric($roleid)){
					$roleid    = Sirah_User_Acl_Table::getRoleid($roleid);
				}
				if(Sirah_User_Acl_Table::getRolename($roleid)){
					if(!$dbAdapter->delete($tablePrefix . "system_acl_useroles" , array( "roleid = " . intval($roleid) . " "  , " userid = " . intval($userid) . " " ) )){
						$result = false;
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * Permet de recupérer toutes les permissions
	 * d'un utilisateur
	 *
	 * @static
	 * @param   integer $userid      L'identifiant de l'utilisateur .
	 * @param   integer $resourceid  L'identifiant de la ressource sur laquelle 
	 *                                nous souhaitons vérifier la permission
	 * @param   integer $objectid    L'identifiant de l'objet sur lequel 
	 *                                nous souhaitons vérifier la permission
	 *  @param  integer $right       Le type de permission pour lequel
	 *                                nous souhaitons récupérer les droits
	 * @return  array   la liste des permissions de l'utilisateur
	 *
	 * @since
	 */
	static public function getUserRights($userid , $resourceid=0 , $objectid=0 , $right = null)
	{
		$userTable         = Sirah_User_Table::getInstance($userid);
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
		
		$resourceid        = (is_numeric($resourceid))? $resourceid : Sirah_User_Acl_Table::getResourceid($resourceid);
		$objectid          = (is_numeric($objectid))  ? $objectid   : Sirah_User_Acl_Table::getObjectid($objectid , $resourceid);	
		$selectRights      = $dbAdapter->select()->from(array("RG" => $tablePrefix."system_acl_rights"),array("RG.userid" , "RG.allow"))
		                                         ->join(array("O"  => $tablePrefix."system_acl_objects"),"O.objectid=RG.objectid",array("O.objectname" , "O.description"))		                  
		                                         ->join(array("RS" => $tablePrefix."system_acl_resources"),"RS.resourceid=O.resourceid",array("RS.resourcename"))
		                                         ->where("RG.userid = ? " , intval($userid));
		if(intval($resourceid)){
			$selectRights->where(" ( O.resourceid =".intval($resourceid)."  OR 
					                 O.resourceid IN ( SELECT R3.parentid FROM `".$tablePrefix."system_acl_resources` R3
					                                          WHERE R3.resourceid=".intval($resourceid).")
					                ) ");
		}		
	    if(($right==1 || $right==0) && $right !== null ){
			$selectRights->where("RG.allow=?" , intval($right));
		}	
		if(intval($objectid)){
			$selectRights->where("RG.objectid=?" , intval($objectid));
		}
		$selectRights->order(array("RG.allow DESC"));		
		return $dbAdapter->fetchAll($selectRights);
	}
		
	/**
	 * Permet de recupérer toutes les permissions
	 * d'un role utilisateur
	 *
	 * @static
	 * @param   integer $roleid      L'identifiant du role .
	 * @param   integer $resourceid  L'identifiant de la ressource sur laquelle
	 *                                nous souhaitons vérifier la permission
	 * @param   integer $objectid    L'identifiant de l'objet sur lequel
	 *                                nous souhaitons vérifier la permission
	 * @return  array   la liste des permissions du role
	 *
	 * @since
	 */
	static public function getRoleRights($roleid , $resourceid=0 , $objectid=0 , $right=null)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
				
		$roleid            = (is_numeric($roleid))      ? $roleid : Sirah_User_Acl_Table::getRoleid($roleid);
		$resourceid        = (is_numeric($resourceid))  ? $resourceid : Sirah_User_Acl_Table::getResourceid($resourceid);
		$objectid          = (is_numeric($objectid))    ? $objectid : Sirah_User_Acl_Table::getObjectid($objectid , $resourceid);
		
		$selectRights      = $dbAdapter->select()->from(array("RG" => $tablePrefix."system_acl_rights"),array("RG.roleid" , "RG.allow"))
		                                         ->join(array("O"  => $tablePrefix."system_acl_objects"),"O.objectid=RG.objectid",array("O.objectname" , "O.description"))
		                                         ->join(array("RL" => $tablePrefix."system_acl_resources"),"RL.resourceid=O.resourceid",array("RL.resourcename" , "resourcedescription" =>"RL.description"))
		                                         ->where("( RG.roleid =".$roleid." OR 
		                                         		    RG.roleid IN ( SELECT R1.parentroleid FROM `".$tablePrefix."system_acl_parentroles` R1
						                                                   WHERE  R1.childroleid=".$roleid." ))");	                                         
		if(intval($resourceid)){
			$selectRights->where("(O.resourceid = ".intval($resourceid)." OR 
					               O.resourceid IN ( SELECT R3.parentid FROM `".$tablePrefix."system_acl_resources` R3
					                                 WHERE R3.resourceid=".intval($resourceid).")) ");
		}
		if(($right==1 || $right==0) && $right !== null ){
			$selectRights->where("RG.allow=?",intval($right));
		}	
		if(intval($objectid)){
			$selectRights->where("RG.objectid=?" , intval($objectid));
		}		
		$selectRights->order(array("RL.resourceid ASC"));	
		return $dbAdapter->fetchAll($selectRights);		
	}
	
	/**
	 * Permet de recupérer les permissions d'un invité sur une ressource
	 *
	 * @static
	 * @param   integer $resourceid  L'identifiant de la ressource sur laquelle
	 *                                nous souhaitons vérifier la permission
	 * @param   integer $objectid    L'identifiant de l'objet sur lequel
	 *                                nous souhaitons vérifier la permission
	 * @return  array   la liste des permissions du role
	 *
	 * @since
	 */
	public function getGuestRights($resourceid=0,$objectid=0,$right=null)
	{
		return Sirah_User_Acl_Table::getRoleRights("Guest" , $resourceid , $objectid);		
	}
	
	
	/**
	 * Permet de vérifier si un utilisateur ou un role
	 * est autorisé à accéder à un objet
	 *
	 * @static
	 * @param   integer $userid      L'identifiant de l'utilisateur
	 * @param   integer $roleid      L'identifiant du role .
	 * @param   integer $resourceid  L'identifiant de la ressource sur laquelle
	 *                                nous souhaitons vérifier la permission
	 * @param   integer $objectid    L'identifiant de l'objet sur lequel
	 *                                nous souhaitons vérifier la permission
	 * @return  bool true or false en fonction du résultat trouvé
	 *
	 * @since
	 */
	static public function isAllowed($userid = 0 , $roleid = 0 , $resourceid = 0 , $objectid = 0)
	{
		if(intval($userid)){
			//On essaie d'abord de vérifier la permission pour les objets de type all
			if($allObjectid = Sirah_User_Acl_Table::getObjectid("all" , $resourceid)) {
				if( count(Sirah_User_Acl_Table::getUserRights($userid , $resourceid , $allObjectid , 1))) {
					return true;
				}
			}
		   return count(Sirah_User_Acl_Table::getUserRights($userid , $resourceid , $objectid , 1));
		}		
		if(!is_numeric($roleid)){
			$roleid  = Sirah_User_Acl_Table::getRoleid($roleid);
		}
		//On essaie d'abord de vérifier la permission pour les objets de type all
		if($allObjectid = Sirah_User_Acl_Table::getObjectid("all" , $resourceid)) {
			if(count(Sirah_User_Acl_Table::getRoleRights($roleid , $resourceid , $allObjectid , 1))) {
				return true;
			}
		}		
		return count(Sirah_User_Acl_Table::getRoleRights($roleid , $resourceid , $objectid , 1));				
	 }
	
	/**
	 * Permet de recupérer toutes les ressources
	 * de l'ACL de la plateforme
	 *
	 * @static
	 * @param   integer  l'identifiant de l'application (optionnel)
	 *                  pour laquelle on veut les ressources
	 * @return  array   la liste des ressources
	 *
	 * @since
	 */
	static public function getResources($moduleid=0)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectResources   = $dbAdapter->select()->from($tablePrefix."system_acl_resources",array("resourceid","resourcename"));
	
		if(intval($moduleid)){
			$selectResources->where("moduleidid=?",intval($moduleid));
		}
		return $dbAdapter->fetchPairs($selectResources);
	}
	
	
	/**
	 * Permet de recupérer tous les objets
	 * de l'ACL de la plateforme
	 *
	 * @static
	 * @param   integer l'identifiant de la ressource
	 * @return  array   la liste des objets d'une ressource ou
	 *                  tous les objets de l'application
	 *
	 * @since
	 */
	static public function getObjects($resourceid=0 , $includeAll = false)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectObjects     = $dbAdapter->select()->from($tablePrefix."system_acl_objects");
		
		if(!$includeAll){
			$selectObjects->where("objectname NOT LIKE 'all' ");
		}	
		if(intval($resourceid)){
			$selectObjects->where("resourceid=?" , intval($resourceid));
		}
		$selectObjects->order(array("resourceid DESC"));
		return $dbAdapter->fetchAll($selectObjects);
	}
	
	/**
	 * Permet de recupérer le nom de la ressource
	 * de l'ACL de la plateforme à partir de l'identifiant
	 *
	 * @static
	 * @param   integer l'identifiant de la ressource
	 * @return  string  le nom de la ressource
	 *
	 * @since
	 */
	static public function getResourcename($resourceid)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectResource    = $dbAdapter->select()->from($tablePrefix."system_acl_resources","resourcename")
		                                         ->where("resourceid=?",intval($resourceid));
		return $dbAdapter->fetchOne($selectResource);
	}
	
	/**
	 * Permet de recupérer la description de la ressource
	 * de l'ACL de la plateforme à partir de l'identifiant
	 *
	 * @static
	 * @param   integer l'identifiant de la ressource
	 * @return  string  le nom de la ressource
	 *
	 * @since
	 */
	public function getResourcedesc($resourceid)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectResource    = $dbAdapter->select()->from($tablePrefix."system_acl_resources","description")
		                                         ->where("resourceid=?",intval($resourceid));
		return $dbAdapter->fetchOne($selectResource);
	}
	
	
	/**
	 * Permet de recupérer l'identifiant d'une ressource
	 * de l'ACL de la plateforme à partir de son nom
	 *
	 * @static
	 * @param   string  le nom de la ressource
	 * @return  integer l'identifiant de la ressource
	 *
	 * @since
	 */
	static public function getResourceid($resourcename)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectResource    = $dbAdapter->select()->from($tablePrefix."system_acl_resources","resourceid")
		                                         ->where("resourcename=?",$resourcename);
		return $dbAdapter->fetchOne($selectResource);
	}
	
	
	/**
	 * Permet de recupérer le nom du role
	 * de l'ACL de la plateforme à partir de l'identifiant
	 *
	 * @static
	 * @param   integer l'identifiant du role
	 * @return  string  le nom du role
	 *
	 * @since
	 */
	static public function getRolename($roleid)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectRole        = $dbAdapter->select()->from($tablePrefix."system_acl_roles", "rolename")
		                                         ->where("roleid=?",intval($roleid));
		return $dbAdapter->fetchOne( $selectRole );
	}
	
	
	/**
	 * Permet de recupérer l'identifiant d'un role
	 * de l'ACL de la plateforme à partir de son role
	 *
	 * @static
	 * @param   string  le nom du role
	 * @return  integer l'identifiant du role
	 *
	 * @since
	 */
	static public function getRoleid( $rolename )
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectRole        = $dbAdapter->select()->from($tablePrefix."system_acl_roles","roleid")->where("rolename=?",$rolename);
		return $dbAdapter->fetchOne($selectRole);
	}	
	
	
	/**
	 * Permet de recupérer l'identifiant
	 * de l'ACL de la plateforme à partir de son nom
	 *
	 * @static
	 * @param   integer l'identifiant de l'objet
	 * @return  string  le nom de l'objet
	 *
	 * @since
	 */
	static public function getObjectname($objectid)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectObject      = $dbAdapter->select()->from($tablePrefix."system_acl_objects","objectname")
		                                         ->where("objectid=?",intval($objectid));
		return $dbAdapter->fetchOne($selectObject);
	}
	
	/**
	 * Permet de recupérer l'identifiant de la ressource
	 * de l'ACL de la plateforme à partir de l'id de l'objet
	 *
	 * @static
	 * @param   string  l'identifiant de l'objet
	 * @return  integer l'identifiant de la resource
	 *
	 * @since
	 */
	static public function getObjectResourceid($objectid)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectObject      = $dbAdapter->select()->from($tablePrefix."system_acl_objects","resourceid")
		                                         ->where("objectid=?",intval($objectid));
		return $dbAdapter->fetchOne($selectObject);
	}
	
	/**
	 * Permet de recupérer l'identifiant d'un objet
	 * de l'ACL de la plateforme à partir de son nom
	 *
	 * @static
	 * @param   string  le nom de l'objet
	 * @return  integer l'identifiant de l'objet
	 *
	 * @since
	 */
	static public function getObjectid($objectname , $resourceid = 0)
	{
		$userTable         = Sirah_User_Table::getInstance();
		$dbAdapter         = $userTable->getAdapter();
		$tablePrefix       = $userTable->info("namePrefix");
	
		$selectObject      = $dbAdapter->select()->from($tablePrefix."system_acl_objects","objectid")
		                                         ->where("objectname=?",$objectname);
		if(intval($resourceid)) {
			$selectObject->where("resourceid=?" , intval($resourceid));
		}
		return $dbAdapter->fetchOne($selectObject);
	 }

  }
