<?php


/**
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIEMBF BURKINA FASO
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

class Api_PromoteursController extends Sirah_Controller_Default
{
	
	protected $_me = null;
	
	private function _authorizationHeader(){
	    $headers            = null;	
	    if( isset($_SERVER['Authorization'])) {
			$headers        = trim($_SERVER["Authorization"]);
		} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers        = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} else if (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if( isset($requestHeaders['Authorization'])) {
				$headers    = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	
	/*
	public function init()
	{
		$response            = $this->getResponse();
		$authorizationHeader = $this->_authorizationHeader();
		if(!preg_match('/Bearer\s(\S+)/',$authorizationHeader, $matches)) {
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(400);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			echo ZendX_JQuery::encodeJson(array("response"=>"HTTP/1.1 400 Bad Request","status"=>"400"));
			exit;
		}
		$token  = isset($matches[1])?$matches[1] : null;
		if(!$token || empty($token)) {
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(400);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			echo ZendX_JQuery::encodeJson(array("response"=> "HTTP/1.1 400 Bad Request","status"=>"400"));
			exit;
		}
		try {
			$expiration = 864000;
			$jwt        = new JWT(JWT_SECRETE, 'HS256',$expiration);
			$jwtPayload = $jwt->decode($token);
		} catch(Exception $e ) {
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			echo ZendX_JQuery::encodeJson(array("response"=> "TOKEN INVALID","status"=>"401"));
			exit;
		}	
        $me           = Sirah_Fabric::getUser();	
		$username     = "BAD_USERNAME";
        $jwtUID       = (isset($jwtPayload["uid"]     ))? $jwtPayload["uid"]      : null;
        $jwtUsername  = (isset($jwtPayload["username"]))? $jwtPayload["username"] : null;
        $jwtExp       = (isset($jwtPayload["exp"]     ))? $jwtPayload["exp"]      : 0;		
        if( null !== $jwtUID ) {
			$me       = Sirah_Fabric::getUser($jwtUID);	
			$username = $me->username;
		}			
		$tpsRestant   = $jwtExp - time();
		if(($username!= $jwtUsername) || ($tpsRestant<=0)){
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			echo ZendX_JQuery::encodeJson(array("response"=> "HTTP/1.1 400 Bad Request : TOKEN INVALIDE : ".$jwtUID." / ".$userid,"status"=>"401"));
			exit;
		}
	}*/
	
	
	public function listAction()
	{		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		$promoteurs            = array();
		$paginator             = null;
		$me                    = Sirah_Fabric::getUser();
		$model                 = $this->getModel("apidemandepromoteur");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 100;	
		$filters               = array("searchQ"=>null,"lastname"=>null,"firstname"=>null,"code"=>null,"name"=>null,"groupid"=>0,"identifiant"=>null,"passport"=>null,"nationalite"=>null,"email"=>null,"telephone"=> null,"entrepriseid"=>0,"promoteurid"=>0,"promoteurid"=>0,		                              
									   "periode_start"=>null,"periode_end"=>null,"periode_start_day"=>null,"periode_start_month"=>null,"periode_start_year"=>null,"periode_end_day"=>null,"periode_end_month"=>null,"periode_end_year"=>null);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey=> $filterValue ) {
				     $filters[$filterKey] =  $stringFilter->filter($filterValue);
			}
		}
		if( empty($filters["name"]) && (!empty($filters["lastname"]) || !empty( $filters["firstname"] ))) {
			$filters["name"]  = trim(sprintf("%s %s", $filters["lastname"], $filters["firstname"]));
		}
        try {			
			$promoteurs       = $model->getList($filters,$pageNum, $pageSize );
			$paginator        = $model->getListPaginator($filters);
		} catch(Exception $e ) {
			$errorMessages[]  = sprintf("Erreur base de données : %s ", $e->getMessage());
		}
		$total                = 0;
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
			$total            =  $paginator->getTotalItemCount();
		}
		if( count($promoteurs) && empty($errorMessages) ) {
			$responseData     = array("data"=>$promoteurs,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData          = array("data"=>$promoteurs,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		} 
	}
	
	
	public function updateAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response               = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                     = Sirah_Fabric::getUser();
		$model                  = $this->getModel("promoteur");
		$postData               = $this->_request->getPost();	
		$postData["promoteurid"]= (isset($postData["promoteurid"]))?intval($postData["promoteurid"]) : 0;
		$promoteurid            = intval($this->_getParam("id", $postData["promoteurid"]));
		$successMessages        = $errorMessages = array();
		
		if(!intval($promoteurid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun promoteur n'a été trouvé avec l'ID %d",$promoteurid),"status"=>"204"));
			exit;
		}
		$promoteurRow             = $model->findRow(intval($promoteurid),"promoteurid",null,false);
		if(!$promoteurRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun promoteur n'a été trouvé avec l'ID %d",$promoteurid),"status"=>"204"));
			exit;
		}
		$modelTable                 = $model->getTable();
		$dbAdapter                  = $modelTable->getAdapter();
		$prefixName                 = $modelTable->info("namePrefix");
		$stringFilter               = new Zend_Filter();
		$stringFilter->addFilter(     new Zend_Filter_StringTrim());
		$stringFilter->addFilter(     new Zend_Filter_StripTags());
		
		$promoteurid                = $promoteurRow->promoteurid;
		$sync_promoteurid           = (isset($postData["sync_promoteurid"]))?intval($postData["sync_promoteurid"]) : 0;
		
		if( intval($sync_promoteurid) && ($sync_promoteurid!=$promoteurid)) {
			$promoteurSyncPromoteur = $model->findRow(intval($sync_promoteurid),"promoteurid",null,false);
			if(!$promoteurSyncPromoteur) {
				$defaultData        = $model->getEmptyData();
				$promoteurData      = array_intersect_key($postData, $defaultData);
				if( isset($promoteurData["promoteurid"])) {
					unset($promoteurData["promoteurid"]);
				}				 
				if( isset($promoteurData["creatorid"])) {
					unset($promoteurData["creatorid"]);
				}
				if( isset($promoteurData["creationdate"])) {
					unset($promoteurData["creationdate"]);
				}
				$promoteurData["promoteurid"]  = intval($sync_promoteurid);
				if( $dbAdapter->update($prefixName."reservation_promoteurs"          , array("promoteurid"=>intval($sync_promoteurid)), array("promoteurid=?"=>intval($promoteurid)))) {
				    $dbAdapter->update($prefixName."reservation_demandes"            , array("promoteurid"=>intval($sync_promoteurid)), array("promoteurid=?"=>intval($promoteurid)));
				    $dbAdapter->update($prefixName."reservation_demandes_entreprises", array("promoteurid"=>intval($sync_promoteurid)), array("promoteurid=?"=>intval($promoteurid)));
					//$dbAdapter->update($prefixName."reservation_demandes_documents"  , array("promoteurid"=>intval($sync_promoteurid)), array("promoteurid=?"=>intval($promoteurid)));
					 
				    $successMessages[] = sprintf("Le promoteur ID %d a été mis à jour avec succès", $promoteurid);
				} else {
					$errorMessages[]   = sprintf("Le promoteur ID %d n'a pas été mis à jour", $promoteurid);
				}
			} else {
				    $errorMessages[]   = sprintf("Un promoteur existant porte le ID : %d en liaison avec le membre ID :%d", $sync_promoteurid, $promoteurid);
			}
		} else {
			        $successMessages[] = sprintf("Le promoteur ID %d a été mis à jour avec succès", $promoteurid);
		}
		if( count($successMessages) ) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}
	}
	 		 
}