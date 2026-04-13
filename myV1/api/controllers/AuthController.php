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
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
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
require 'vendor/autoload.php';
defined("JWT_SECRETE")
    || define("JWT_SECRETE","f1650d56-15a0-11ed-861d-0242ac120002");
	
use Ahc\Jwt\JWT;


class Api_AuthController extends Sirah_Controller_Default
{
	
	private function _authorizationHeader(){
	    $headers            = null;	
	    if( isset($_SERVER['Authorization'])) {
			$headers        = trim($_SERVER["Authorization"]);
		} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers        = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} else if (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) {
				$headers    = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	
	public function tokenAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		$credential  = $identity  = "";
		
		$authorizationHeader      = $this->_authorizationHeader();
		$postData                 = $this->_request->getPost();
		$identity                 = (isset($postData["username"]))?substr($stringFilter->filter($postData["username"]),0,35) : "";
		$credential               = (isset($postData["password"]))?substr($stringFilter->filter($postData["password"]),0,35) : "";		
		
		if((!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PWD']) || empty($_SERVER['PHP_AUTH_USER'])) && !empty($authorizationHeader)) {
			preg_match('/Basic\s(\S+)/',$authorizationHeader, $matches);
			
			if( isset($matches[1])) {
				$AUTH_CREDENTIALS = explode(':',base64_decode($matches[1]));
				$identity         = (isset($AUTH_CREDENTIALS[0]))?substr($stringFilter->filter($AUTH_CREDENTIALS[0]),0,35) : "";
				$credential       = (isset($AUTH_CREDENTIALS[1]))?$AUTH_CREDENTIALS[1] : "";
			}
		} elseif(!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PWD']) && !empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PWD'])) {
		    $identity             = substr($stringFilter->filter($_SERVER['PHP_AUTH_USER']),0,35);
			$credential           = $_SERVER['PHP_AUTH_PWD'];
		}
 
		$response                 = $this->getResponse();
		if( empty($identity) || empty($credential)) {			 
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 401 Unauthorized");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"HTTP/1.1 401 Unauthorized","status"=>"401"));
			exit;
		}
		$identity                 = filter_var($identity, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		$guest                    = Sirah_Fabric::getUser();
		try {
			$loggedInUser         = $guest->login($this->view->escape(stripslashes($identity)), $credential , $rememberMe);
		} catch(Exception $e ) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(500);	
			$response->setRawHeader("HTTP/1.1 401 Unauthorized");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreur d'authentification ","status"=>"500"));
			exit;
		}
		
		if((false==$loggedInUser) || (null==$loggedInUser)) {			
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 401 Unauthorized");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Identifiants Invalides","status"=>"401"));
			exit;
		}	
 
		$expiration  = 864000;
		$jwt         = new JWT(JWT_SECRETE,'HS256',$expiration);
		$clientIP    = "";
		$payload     = array("ip"=>$clientIP,"uid"=>$loggedInUser->userid,"username"=>$loggedInUser->username,"email"=>$loggedInUser->email,'exp'=>(time()+$expiration));;
		$token       = $jwt->encode($payload);
		echo ZendX_JQuery::encodeJson(array("response"=>$token,"status"=>"200"));
		exit;	
	}
	
	
	
	
	
	 
}