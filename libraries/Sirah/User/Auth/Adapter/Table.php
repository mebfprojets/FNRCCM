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
 * Cette classe correspond à un adaptateur
 * 
 * d'authentification des utilisateurs
 * 
 * qui hérite de Zend_Auth_Adapter_DbTable.
 * 
 * Elle correspond au  pattern Adaptateur
 * 
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
class Sirah_User_Auth_Adapter_Table extends Zend_Auth_Adapter_DbTable
{
	
	/**
	 * Permet d'authentifier un utilisateur
	 * en utilisant l'adaptateur de la table
	 * des utilisateurs.
	 *
	 * @param  string $identity          la valeur de l'identifiant d'authentification
	 * @param  string $password          la valeur du mot de passe non crypté
	 * @param  bool   $cryptedCredential indique si le mot de passe doit etre crypté ou pas
	 * @param  string $encryption        le type de cryptage utilisé
	 * @return Zend_Auth_Result
	 */
	public function authenticate( $identity=null , $password=null , $cryptedCredential=true , $encryption="crypt")
	{	
		if( null===$identity){
			$identity    = $this->_identity;
		}		 
		if(is_null($password)){
			$password    = $this->_credential;
		}	
		$identityColumn  =  $this->_identityColumn;
		$dbAdapter       =  $this->_zendDb;
		$tableName       =  $this->_tableName;
		$dbSelect        =  $this->getDbSelect();
		 
		if(null==$tableName || empty($tableName)){
			$resultInfos['code']           =  Sirah_User_Auth_Result::FAILURE_DISABLED;
			$resultInfos["identity"]       =  null;
			$resultInfos["messages"][]     = "Impossible d'effectuer l'authentification de l'utilisateur car des parmètres sont manquants";
			return new Sirah_User_Auth_Result($resultInfos['code'] , $resultInfos['identity'] , $resultInfos['messages']);
		}		
		$dbSelect       = $dbAdapter->select()->from(array("U"=>"system_users_account"),array("U.userid","U.password","U.activated","U.blocked","U.expired","U.locked"))
		                                      ->where(sprintf("U.%s=?",$identityColumn), stripslashes(strip_tags($identity)));
	    //echo $dbSelect->__toString(); die();
		$row                               = $dbAdapter->fetchRow($dbSelect);	
		$resultInfos                       = array();
	
		if(!isset($row["userid"])){
			$resultInfos['code']           =   Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
			$resultInfos['identity']       =   null;
			$resultInfos['messages'][]     =   "  Votre nom d'utilisateur ou votre mot de passe est invalide ";
		}	
		elseif(isset($row["activated"]) && !($row["activated"]) ){	
			$resultInfos['code']           =  Sirah_User_Auth_Result::FAILURE_DISABLED;
			$resultInfos["identity"]       =  null;
			$resultInfos["messages"][]     = "  Votre compte est désactivé. Veuillez contacter l'administrateur";
		}
		elseif(isset($row["blocked"]) && ($row["blocked"]) ){	
			$resultInfos['code']           = Sirah_User_Auth_Result::FAILURE_BLOCKED;
			$resultInfos["identity"]       = null;
			$resultInfos["messages"][]     = " Votre compte est bloqué. Veuillez contacter l'administrateur";
		}
		elseif(isset($row["expired"]) && ($row["expired"]) ){
			$resultInfos['code']           = Sirah_User_Auth_Result::FAILURE_ACCOUNT_EXPIRED;
			$resultInfos["identity"]       = null;
			$resultInfos["messages"][]     = " Votre compte est expiré. Veuillez contacter l'administrateur";
		}
		elseif(isset($row["locked"]) && ($row["locked"]) ){
			$resultInfos['code']           = Sirah_User_Auth_Result::FAILURE_LOCKED;
			$resultInfos["identity"]       = null;
			$resultInfos["messages"][]     = " Votre compte est verrouillé. Veuillez contacter l'administrateur";
		}else{		
			if(($cryptedCredential && !Sirah_User_Helper::verifyPassword( $password , $row["password"] ) ) || (!$cryptedCredential && $row["password"]!=$password) ) {
				$resultInfos['code']       = Sirah_User_Auth_Result::FAILURE_CREDENTIAL_INVALID;
				$resultInfos['identity']   = null;
				$resultInfos['messages'][] = " Votre nom d'utilisateur ou votre mot de passe est invalide ";
	
		    } else {	
				$resultInfos['code']       = Sirah_User_Auth_Result::SUCCESS;
				$resultInfos['identity']   = $row['userid'];
				$resultInfos['messages'][] = " Votre authentification s'est produite avec succès. Bienvenue à votre session  ";
				
				unset($row["password"]);				
				$this->_resultRow          = $row;	
			 }	
		 }
		 $this->_result                    =  new Sirah_User_Auth_Result($resultInfos['code'],$resultInfos['identity'],$resultInfos['messages']);
	
		return $this->_result;	
	}
 


}

