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
 * Cette classe correspond à un plugin de vérification
 * des droits d'accès à une ressource de l'application
 * En cas d'interdiction d'accès, il route la requete
 * sur la ressource de notification du refus d'accès
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Plugin_Apisigue extends Zend_Controller_Plugin_Abstract
{
	
	
	 
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$user              = Sirah_Fabric::getUser();
		$response          = $this->getResponse();
		$controllerName    = $request->getControllerName();
		$moduleName        = $request->getModuleName();
		$actionName        = $request->getActionName();
		$apiDatabases      = array();
		$hasException      = false;	
		$errorMessages     = array();
		
		if( $moduleName=="admin" && $controllerName=="ajaxres" && $actionName=="apisearch") {
			$appConfigSession  = new Zend_Session_Namespace("AppConfig");
			//unset($appConfigSession->resources);
			if(!isset( $appConfigSession->resources )) {
				$appConfigSession->resources = array();
				$resourceExpirationTMS       = 3600*24*5;
				$appConfigSession->setExpirationSeconds($resourceExpirationTMS, 'resources');
			}       
			if(!isset( $appConfigSession->resources["sigue.databases"]) || empty($appConfigSession->resources["sigue.databases"])) {
				if( defined("API_SIGUE_CONFIG_FILE") && file_exists( API_SIGUE_CONFIG_FILE)) {
					$sigueApiConfig  = json_decode(file_get_contents(API_SIGUE_CONFIG_FILE), true);
					if( isset(   $sigueApiConfig["databases"] ) && is_array($sigueApiConfig["databases"]) && count($sigueApiConfig["databases"])) {
						foreach( $sigueApiConfig["databases"] as $sigueApiDbParams) {
								 if( isset( $sigueApiDbParams["connection"]) ) {
									 $connectionParams             = array();
									 $connectionParams["host"]     = (isset($sigueApiDbParams["connection"]["host"]  ))?$sigueApiDbParams["connection"]["host"]   : "localhost";
									 $connectionParams["port"]     = (isset($sigueApiDbParams["connection"]["port"]  ))?$sigueApiDbParams["connection"]["port"]   : "3306";
									 $connectionParams["username"] = (isset($sigueApiDbParams["connection"]["user"]  ))?$sigueApiDbParams["connection"]["user"]   : (isset($sigueApiDbParams["connection"]["username"])?$sigueApiDbParams["connection"]["username"] : "localhost");
									 $connectionParams["password"] = (isset($sigueApiDbParams["connection"]["pwd"]   ))?$sigueApiDbParams["connection"]["pwd"]    : (isset($sigueApiDbParams["connection"]["password"])?$sigueApiDbParams["connection"]["password"] : "");
									 $connectionParams["dbname"]   = (isset($sigueApiDbParams["connection"]["dbname"]))?$sigueApiDbParams["connection"]["dbname"] : (isset($sigueApiDbParams["connection"]["database"])?$sigueApiDbParams["connection"]["database"] : "");
									 
									 if( isset($sigueApiDbParams["client"])) {
										 if( $sigueApiDbParams["client"] == "mssql") {
											 $dbAdapterClient = "Sqlsrv";
										 } elseif( $sigueApiDbParams["client"] == "mysql") {
											 $dbAdapterClient = "Pdo_Mysql";
										 } else {
											 $dbAdapterClient = ucfirst($sigueApiDbParams["client"] );										 
										 }
									 } else {
										$dbAdapterClient = "Pdo_Mysql";
									 }
									 try {
										$apiDatabase     = Zend_Db::factory($dbAdapterClient, $sigueApiDbParams["connection"]);	
									 } catch( Exception $e ) {
										$errorMessages[] = sprintf("La base de données de SIGU #%s n'est pas accessible, ce qui indisponibilise le système d'interfaçage. Voir votre Administrateur. Message débogage : %s ", $connectionParams["host"], $e->getMessage());
										continue;
									 }
									 $apiDatabases[]     = $apiDatabase;	
								 }							 	                            					 
						}
					}
				}
				if( empty($apiDatabases) ) {
					$sigueApiDbParams    = array("host"=>SIGUEDB_HOST,"username"=>SIGUEDB_USERNAME,"password"=>SIGUEDB_PWD,"dbname"=>SIGUEDB_NAME,"isDefaultAdapter" =>0);
					$dbAdapterClient     = "Sqlsrv";
					try {
						$apiDatabases[]  = Zend_Db::factory($dbAdapterClient, $sigueApiDbParams);
					} catch( Exception $e ) {
						$errorMessages[] = sprintf("La base de données de SIGU #%s n'est pas accessible, ce qui indisponibilise le système d'interfaçage. Voir votre Administrateur. Message débogage : %s ",SIGUEDB_HOST, $e->getMessage());
					}			
				}
				$appConfigSession->resources["sigue.databases"] = $apiDatabases;
			}
			if(!isset($appConfigSession->resources["sigue.api.auth.token"]) && defined("API_SIGUE_HOST") && defined("API_SIGUE_URI_ROOT") && defined("API_SIGUE_PORT") && defined("API_SIGUE_AUTH_USER") && defined("API_SIGUE_AUTH_PWD")) {
				$API_URI            = sprintf("%s:%s%s", API_SIGUE_HOST, API_SIGUE_PORT, API_SIGUE_URI_ROOT );	
				$API_AUTH_USER      = API_SIGUE_AUTH_USER;
				$API_AUTH_PWD       = API_SIGUE_AUTH_PWD;
				$API_AUTH_SID       = base64_encode(sprintf("%s:%s", $API_AUTH_USER, $API_AUTH_PWD));	
				$API_AUTH_COOKIE    = "";			
	 
				try {
					$appConfigSession->resources["sigue.api.auth.sid"]  = $API_AUTH_SID;
					$appConfigSession->resources["sigue.api.auth.user"] = $API_AUTH_USER;
					$appConfigSession->resources["sigue.api.auth.pwd"]  = $API_AUTH_PWD;
					$appConfigSession->resources["sigue.api.uri"]       = $API_URI;
					$authClient     = new  Zend_Http_Client($API_URI."/auth/token", array('keepalive' => true));
					$authClient->setHeaders(array("Authorization"=>"Basic ".$API_AUTH_SID));
									
					$authResponse   = $authClient->request();
					$API_AUTH_TOKEN = $authResponse->getBody();
					$authCookies    = $authResponse->getHeader("Set-Cookie");
					if( isset($authCookies[0])) {
						$API_AUTH_COOKIE = $authCookies[0];
						$appConfigSession->resources["sigue.api.auth.cookie"] = $API_AUTH_COOKIE;
						if(!defined("API_SIGUE_AUTH_COOKIE")) {
							define( "API_SIGUE_AUTH_COOKIE", $API_AUTH_COOKIE); 
						}
					}
					if(!empty( $API_AUTH_TOKEN) ) {
						$appConfigSession->resources["sigue.api.auth.token"]  = $API_AUTH_TOKEN;
						if(!defined("API_SIGUE_AUTH_TOKEN")) {
							define( "API_SIGUE_AUTH_TOKEN", $API_AUTH_TOKEN); 
						}
					}
					if(!defined("API_SIGUE_URI")) {
						define( "API_SIGUE_URI", $API_URI);
					}
				} catch ( Exception $e ) {
					$errorMessages[]    =	sprintf("L'API SIGUEAPI est indisponible : %s ", $e->getMessage());		
				}
			} 
			if(!defined("API_SIGUE_AUTH_TOKEN") && isset($appConfigSession->resources["sigue.api.auth.token"])) {
				define( "API_SIGUE_AUTH_TOKEN"  , $appConfigSession->resources["sigue.api.auth.token"]); 
			}
			if(!defined("API_SIGUE_AUTH_COOKIE") && isset($appConfigSession->resources["sigue.api.auth.cookie"])) {
				define( "API_SIGUE_AUTH_COOKIE", $appConfigSession->resources["sigue.api.auth.cookie"]); 
			}	
			if(!defined("API_SIGUE_AUTH_SID") && isset($appConfigSession->resources["sigue.api.auth.sid"])) {
				define( "API_SIGUE_AUTH_SID"   , $appConfigSession->resources["sigue.api.auth.sid"]); 
			}
			if(!defined("API_SIGUE_URI") && isset($appConfigSession->resources["sigue.api.uri"])) {
				define( "API_SIGUE_URI"   , $appConfigSession->resources["sigue.api.uri"]); 
			}		
			
			if( count( $errorMessages)) {
				$errorMessage           = implode(", ", $errorMessages );
				$apiUnvailableException = new Zend_Controller_Router_Exception(sprintf("Le système d'interfaçage avec SIGU ne fonctionne pas. Des erreurs ont été détectées : \n %s", $errorMessage));			
				$hasException           = true;
				$controllerName         = "error";
				$actionName             = "index";
				$error                  = new Zend_Controller_Plugin_ErrorHandler();
				$error->type            = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
				$error->request         = clone($request);
				$error->exception       = $apiUnvailableException;
				$request->setParam('error_handler', $error);
			}
			// On met à jour les informations du controlleur				
			$request->setModuleName(      $moduleName);
			$request->setControllerName(  $controllerName);
			$request->setActionName(      $actionName);	
		}	
	}	     
}
