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
 * Cette classe correspond à la fabrique de l'application
 * dont le but est de créer toutes les ressources necessaires
 * au bon fonctionnnement de l' application
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

class Sirah_Fabric 
{
	
	
	static public function getPdf($options = array()) 
	{
		if( defined("DEFAULT_PDF_MANAGER") ) {
			$pdfManager = DEFAULT_PDF_MANAGER;
		} elseif( isset($options["manager"])) {
			$pdfManager = $options["manager"];
	    } else {
			$pdfManager = "Sirah_Pdf_Default";
		}
		$PDF            = null;
		$orientation    = isset($options["orientation"] )?$options["orientation"] :((defined("DEFAULT_PDF_ORIENTATION"))?DEFAULT_PDF_ORIENTATION:"P");
		$unit           = isset($options["unit"]        )?$options["unit"]        :((defined("DEFAULT_PDF_UNIT"       ))?DEFAULT_PDF_UNIT       :"mm");
		$format         = isset($options["format"]      )?$options["format"]      :((defined("DEFAULT_PDF_FORMAT"     ))?DEFAULT_PDF_FORMAT     :"A4");
		$encoding       = isset($options["encoding"]    )?$options["encoding"]    :((defined("DEFAULT_PDF_ENCODING"   ))?DEFAULT_PDF_ENCODING   :"UTF-8");
		$show_header    = isset($options["print_header"])?$options["print_header"]:((defined("SHOW_PDF_HEADER"        ))?SHOW_PDF_HEADER:true);
		$show_footer    = isset($options["print_footer"])?$options["print_footer"]:((defined("SHOW_PDF_FOOTER"        ))?SHOW_PDF_FOOTER:true);
		$margins        = isset($options["margins"]     )?$options["margins"]     :((defined("DEFAULT_PDF_MARGINS"    ))?DEFAULT_PDF_MARGINS:"10,50,10");
		$marginLeft     = $marginRight  = 10;
		$marginTop      = 50;
		if( !empty($margins) ) {
			if( is_string( $margins )) {
				$margins              = preg_split("/[\s,;]+/", $margins );
			}
			$margins                  = (array)$margins;
			if( isset( $margins[0] )) {
				$marginLeft           = $margins[0];
			}
			if( isset( $margins[1] )) {
				$marginTop            = $margins[1];
			}
			if( isset( $margins[2] )) {
				$marginRight          = $margins[2];
			}
		}
		if( class_exists( $pdfManager )) {
			$PDF                      = new $pdfManager($orientation, $unit, $format, true, $encoding);
		} else {
			switch(strtolower( $pdfManager )) {
				case "default":
				default:
				     $PDF             = new Sirah_Pdf_Default($orientation, $unit, $format, true, $encoding);
					 break;
				case "project":
				     $PDF             = new ProjectPdf_Default($orientation, $unit, $format, true, $encoding);
					 break;
			}
		}
		$PDF->SetPrintHeader($show_header);
        $PDF->SetPrintFooter($show_footer);
		$PDF->SetMargins($marginLeft, $marginTop, $marginRight);
		Zend_Registry::set("PDF", $PDF );
		return $PDF;
	}
	
	/**
	 * **** Des méthodes statiques correspondant aux fabriques de la classe de
	 * l'application ****
	 */
	
	static function getApplication($name = "") 
	{
	
	}
	
	/**
	 * Permet de récupérer l'objet de configuration de l'application
	 *
	 * @param boolean $toArray  indique si la configuration doit etre retournée sous forme de tableau
	 *
	 * @return mixed
	 *
	 */
	static function getConfig($toArray = true)
	{
		if(!Zend_Registry::isRegistered("config")){
			throw new Sirah_Exception("L'objet de configuration n'est pas enregistré dans le registre" );
		}
		$cfg  = Zend_Registry::get("config");
		if($toArray){
			return  $cfg->toArray();
		}
		return $cfg;
	}
	
	/**
	 * Permet de récupérer la ressource de gestion de base de données
	 *
	 *
	 * @return Zend_Db_Adapter_Abstract
	 *
	 */
	static function getDbo() 
	{
		if (! Zend_Registry::isRegistered("db")){
			throw new Sirah_Exception("La ressource de gestion de la base de donnees n'a pas ete correctement créée" );
		}
		return Zend_Registry::get ( "db" );
	}
	
	/**
	 * Permet de récupérer lle gestionnaire de cache
	 *
	 *
	 * @return Zend_Cache_Manager
	 *
	 */
	static function getCachemanager() 
	{
		if (!Zend_Registry::isRegistered("cacheManager" )) {
			throw new Sirah_Exception ( "La ressource de gestion des caches n'a pas été correctement créee" );
		}
		return Zend_Registry::get ( "cacheManager" );
	}
	
	/**
	 * Permet de récupérer l'instance d'un utilisateur
	 * celui connecté par defaut
	 *
	 * @param string l'identifiant de l'utilisateur (son id ou son email ou
	 *        	son username)
	 *       	
	 * @return Sirah_User instance
	 *        
	 */
	static function getUser( $userid = null ) 
	{
       $userAuth     = Sirah_User_Auth::getInstance();
       $instance     = null;
       $log          = Sirah_Fabric::getLog();
            
       if( $userAuth->hasIdentity() ) {
           $userStorage = $userAuth->getStorage();
           $user        = &$userStorage->read();   	   
           if( ( $user->userid==$userid ) || ( null === $userid ) ) {
        	     $instance = $user;
           } 
        }
        if( null === $instance )  {
        	try{
        		$instance  = &Sirah_User::getInstance( $userid );
        	} catch( Sirah_User_Exception $e ) {
        		$log->err($e->getMessage());
        		$instance  = &Sirah_User::getInstance();
        	}
        }              
		return $instance;
	}
	
	
	/**
	 * Permet de récupérer l'instance de l'ACL
	 * d'un utilisateur ou d'un role spécifique
	 *
	 * @param $aclType string
	 *       	 le type de l'ACL
	 * @param $userid string
	 *       	 l'identifiant de l'utilisateur concerné par l'ACL
	 * @param $roleid string
	 *       	 l'identifiant du role concerné par l'ACL
	 *       	
	 * @return Zend_Acl_Core instance
	 *        
	 */
	static function getAcl($aclType = "userAcl", $userid = 0, $roleid = 0) 
	{
		$cacheManager = Sirah_Fabric::getCachemanager();
		$aclKey       = ($userid == 0 || null == $userid || empty($userid))?preg_replace("/[^a-zA-Z0-9]/","",$aclType.$roleid) : preg_replace("/[^a-zA-Z0-9]/","",$aclType . $userid);		
		if (! $cacheManager->hasCache("Acl")) {
			$aclCache = Sirah_Cache::getInstance("Acl", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
		} else {
			$aclCache = $cacheManager->getCache("Acl" );
		}
		if( false == $aclCache->load($aclKey) ) {
			$newAcl = self::_createAcl($aclType, $userid, $roleid );
			$newAcl->addResource(new Zend_Acl_Resource("index"));
			$newAcl->addResource(new Zend_Acl_Resource("error"));			
			$aclCache->save($newAcl, $aclKey );
		}
		$cacheManager->setCache("Acl", $aclCache );		
		return $aclCache->load($aclKey );
	}
	
	/**
	 * Permet de récupérer la ressource de journalisation des erreurs
	 *
	 * @return Sirah_Log instance
	 *        
	 */
	static function getLog() 
	{
		if (! Zend_Registry::isRegistered ( "log" )) {
			throw new Sirah_Exception ( "La ressource de création des données de journalisation n'a pas été correctement créee" );
		}
		return Zend_Registry::get ( "log" );
	}
	
	/**
	 * Permet de récupérer la ressource de journalisation des erreurs
	 *
	 * @return Sirah_Mail instance
	 *        
	 */
	static function getMailer() 
	{
		if (! Zend_Registry::isRegistered ( "mailer" )) {
			throw new Sirah_Exception ( "La ressource de gestion des emails n'a pas été correctement créee" );
		}
		return Zend_Registry::get("mailer" );
	}
	
	/**
	 * Permet de créer l'ACL
	 *
	 * @param $aclType string  le type d'ACL qu'on veut créer
	 * @param $userid string   l'identifiant de l'utilisateur concerné
	 * @param $roleid string   l'identifiant du role concerné
	 *       	
	 * @return Sirah_User_Acl instance
	 *        
	 */
	protected static function _createAcl($aclType = "userAcl", $userid = 0, $roleid = null) 
	{
		$acl = null;
		switch ($aclType) {
			default :
			case "userAcl" :
				if (0 !== $userid && null !== $userid && !empty($userid )) {
					$acl = Sirah_User_Acl::getInstance(intval($userid),array("loadDynamicData" => true ));
				} else {
					$acl = Sirah_User_Acl::getInstance(0);
				}
				break;
			
			case "roleAcl" :
				$acl      = Sirah_User_Acl::getInstance(0);
				$roleid   = (is_numeric($roleid)) ? $roleid : Sirah_User_Acl_Table::getRoleid($roleid );
				$rolename = Sirah_User_Acl_Table::getRolename($roleid );
				
				if(intval($roleid)){
					/**
					 *  On récupère les autorisations en fonction de leur type
					 */
					$roleAllowedRights = Sirah_User_Acl_Table::getRoleRights(intval($roleid), 0, 0, 1 );
					$roleDeniedRights  = Sirah_User_Acl_Table::getRoleRights(intval($roleid), 0, 0, 0 );					
					if(!$acl->hasRole($rolename)){
						$dynamicRoleParents = Sirah_User_Acl_Table::getRoleParents($roleid);						
						if(!empty($dynamicRoleParents)){
							$acl->addDynamicRoles($dynamicRoleParents);
						}						
						$acl->addRole( $rolename , $dynamicRoleParents);
					}	
					if( count($roleAllowedRights)){
						foreach($roleAllowedRights as $allowedRight){
						    if(!$acl->has($allowedRight["resourcename"])){
					            $acl->addResource($allowedRight["resourcename"]);
				             }
				             if($allowedRight["objectname"] == "all") {
				             	$acl->allow($rolename , $allowedRight["resourcename"] , array());
				             } else {
				             	$acl->allow($rolename , $allowedRight["resourcename"] , $allowedRight["objectname"]);
				             }                           
						}
					}					
					if( count($roleDeniedRights)){
						foreach($roleDeniedRights as $roleDeniedRight){
							if(!$acl->has($roleDeniedRight["resourcename"])){
								$acl->addResource($roleDeniedRight["resourcename"]);
							}
							if($roleDeniedRight["objectname"] == "all") {
							   $acl->deny($rolename , $roleDeniedRight["resourcename"] , array());
							} else {
							   $acl->deny($rolename , $roleDeniedRight["resourcename"] , $roleDeniedRight["objectname"]);
							}
						}
					}
					
				}
				break;
		}		
		//On ajoute les permissions du role invité
		$guestRoleId        = Sirah_User_Acl_Table::getRoleid("Guest");
		$guestAllowedRights = Sirah_User_Acl_Table::getRoleRights(intval($guestRoleId), 0, 0, 1 );
		$guestDeniedRights  = Sirah_User_Acl_Table::getRoleRights(intval($guestRoleId), 0, 0, 0);
			
		if(!$acl->hasRole("Guest")){
			$guestParents  = Sirah_User_Acl_Table::getRoleParents($guestRoleId);
			$acl->addRole("Guest",$guestParents);
		}
		if( count($guestAllowedRights)){
			foreach($guestAllowedRights as $guestAllowedRight){
				if(!$acl->has($guestAllowedRight["resourcename"])){
					$acl->addResource($guestAllowedRight["resourcename"]);
				}
				if($guestAllowedRight["objectname"] == "all") {
				   $acl->allow("Guest" , $guestAllowedRight["resourcename"] , array());
				} else {
				   $acl->allow("Guest" , $guestAllowedRight["resourcename"] , $guestAllowedRight["objectname"]);
				}				
			}
		}
		if( count($guestDeniedRights)){
			foreach($guestDeniedRights as $guestDeniedRight){
				if(!$acl->has($guestDeniedRight["resourcename"])){
					$acl->addResource($guestDeniedRight["resourcename"]);
				}
				if($guestDeniedRight["objectname"] == "all") {
					$acl->deny("Guest" , $guestDeniedRight["resourcename"] , array());
				} else {
					$acl->deny("Guest",$guestDeniedRight["resourcename"],$guestDeniedRight["objectname"]);
				}
			}
		}	
		//On ajoute des permissions statiques
		$acl->addResource(new Zend_Acl_Resource("index"));
		$acl->addResource(new Zend_Acl_Resource("error"));
		$acl->addResource(new Zend_Acl_Resource("recoverpwd"));
		$acl->addResource(new Zend_Acl_Resource("securitycheck"));
		$acl->addResource(new Zend_Acl_Resource("ajaxres"));
		$acl->allow(null,"index");
		$acl->allow(null,"error");
		$acl->allow(null,"ajaxres");
		$acl->allow(null,"recoverpwd");
		$acl->allow(null,"securitycheck");
		
		return $acl;
	}

}
