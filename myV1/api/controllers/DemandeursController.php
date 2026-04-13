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

class Api_DemandeursController extends Sirah_Controller_Default
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
	}
	
	
	public function listAction()
	{		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		$demandeurs               = array();
		$paginator             = null;
		$me                    = Sirah_Fabric::getUser();
		$model                 = $this->getModel("apidemandeur");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 100;	
		$filters               = array("searchQ"=>null,"lastname"=>null,"firstname"=>null,"code"=>null,"name"=>null,"groupid"=>0,"identifiant"=>null,"passport"=>null,"nationalite"=>null,"email"=>null,"telephone"=> null,"entrepriseid"=>0,"demandeurid"=>0,"demandeurid"=>0,		                              
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
			$demandeurs       = $model->getList($filters,$pageNum, $pageSize );
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
		if( count($demandeurs) && empty($errorMessages) ) {
			$responseData     = array("data"=>$demandeurs,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData          = array("data"=>$demandeurs,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
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
		$model                  = $this->getModel("demandeur");
		$postData               = $this->_request->getPost();	
		$postData["demandeurid"]= (isset($postData["demandeurid"]))?intval($postData["demandeurid"]) : 0;
		$demandeurid            = intval($this->_getParam("id", $postData["demandeurid"]));
		$successMessages        = $errorMessages = array();
		
		if(!intval($demandeurid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun demandeur n'a été trouvé avec l'ID %d",$demandeurid),"status"=>"204"));
			exit;
		}
		$demandeurRow             = $model->findRow(intval($demandeurid),"demandeurid",null,false);
		if(!$demandeurRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun demandeur n'a été trouvé avec l'ID %d",$demandeurid),"status"=>"204"));
			exit;
		}
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$prefixName            = $modelTable->info("namePrefix");
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$demandeurid           = $demandeurRow->demandeurid;
		$sync_demandeurid      = (isset($postData["sync_demandeurid"]))?intval($postData["sync_demandeurid"]) : 0;
		
		if( intval($sync_demandeurid) && ($sync_demandeurid!=$demandeurid)) {
			$demandeurSyncDemandeur = $model->findRow(intval($sync_demandeurid),"demandeurid",null,false);
			if(!$demandeurSyncDemandeur) {
				$defaultData        = $model->getEmptyData();
				$demandeurData      = array_intersect_key($postData, $defaultData);
				if( isset($demandeurData["demandeurid"])) {
					unset($demandeurData["demandeurid"]);
				}				 
				if( isset($demandeurData["creatorid"])) {
					unset($demandeurData["creatorid"]);
				}
				if( isset($demandeurData["creationdate"])) {
					unset($demandeurData["creationdate"]);
				}
				$demandeurData["demandeurid"]  = intval($sync_demandeurid);
				try {
					if( $dbAdapter->update($prefixName."reservation_demandeurs"          , array("demandeurid"=>intval($sync_demandeurid)), array("demandeurid=?"=>intval($demandeurid)))) {
						$dbAdapter->update($prefixName."reservation_demandes"            , array("demandeurid"=>intval($sync_demandeurid)), array("demandeurid=?"=>intval($demandeurid)));
						$dbAdapter->update($prefixName."reservation_demandes_entreprises", array("demandeurid"=>intval($sync_demandeurid)), array("demandeurid=?"=>intval($demandeurid)));
						$dbAdapter->update($prefixName."reservation_demandes_documents"  , array("demandeurid"=>intval($sync_demandeurid)), array("demandeurid=?"=>intval($demandeurid)));
						 
						$successMessages[] = sprintf("Le demandeur ID %d a été mis à jour avec succès", $demandeurid);
					} else {
						$errorMessages[]   = sprintf("Le demandeur ID %d n'a pas été mis à jour", $demandeurid);
					}
				} catch(Exception $e) {
					    $errorMessages[]   = sprintf("Le demandeur ID %d n'a pas été mis à jour : %s", $demandeurid, $e->getMessage());
				}
			} else {
				    $errorMessages[]   = sprintf("Un demandeur existant porte le ID : %d en liaison avec le membre ID :%d", $sync_demandeurid, $demandeurid);
			}
		} else {
			        $successMessages[] = sprintf("Le demandeur ID %d a été mis à jour avec succès", $demandeurid);
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