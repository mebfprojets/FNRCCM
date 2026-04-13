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
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
class Api_DocumentsController extends Sirah_Controller_Default
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
			if( isset($requestHeaders['Authorization'])) {
				$headers    = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	
	
	public function init()
	{
		header("Access-Control-Allow-Origin:*");
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		$response            = $this->getResponse();
		$authorizationHeader = $this->_authorizationHeader();
		if(!preg_match('/Bearer\s(\S+)/',$authorizationHeader, $matches)) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(400);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"HTTP/1.1 400 Bad Request ".$authorizationHeader,"status"=>"400"));
			exit;
		}
		$token  = isset($matches[1])?$matches[1] : null;
		if(!$token || empty($token)) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(400);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=> "HTTP/1.1 400 Bad Request","status"=>"400"));
			exit;
		}
		try {
			$expiration = 864000;
			$jwt        = new JWT(JWT_SECRETE, 'HS256',$expiration);
			$jwtPayload = $jwt->decode($token);
		} catch(Exception $e ) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			$response->sendHeaders();
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
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(401);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=> "HTTP/1.1 400 Bad Request : TOKEN INVALIDE : ".$jwtUID." / ".$userid,"status"=>"401"));
			exit;
		}
	}
	
	
	protected function _isJson($string){
		// Trim to remove extra spaces/newlines
		$string = trim($string);
		// Empty string is not valid JSON
		if ($string === '') {
			return false;
		}
		// PHP 8.3+ has json_validate() which is faster
		if (function_exists('json_validate')) {
			return json_validate($string);
		}
		// Fallback for older PHP versions
		json_decode($string);
		return (json_last_error() === JSON_ERROR_NONE);
    }
 
	
	public function listAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response            = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		$model                   = $this->getModel("apidocument");
        $modelType               = $this->getModel("documentcategorie");	
 
 	
		$documents               = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 1));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_documents" => array());
		}
		if(!isset( $stateStore->filters["_documents"]["maxitems"])) {
			$stateStore->filters["_documents"] = array("page"=>1,"maxitems"=>20,"numrccm"=>null,"num_rccm"=>null,"numero"=>null,"type"=>null,"filename"=>null,"registreid"=>0,"nomcommercial"=>null,"order"=>"DESC","documentids"=>array(),"documentid"=>0);			
		}
		

		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$rawData                  = file_get_contents("php://input");
		$jsonData                 = array("numrccm"=>"");
		if( $this->_isJson($rawData) ) {
			$jsonData             = json_decode($rawData, true);
		}			
		$params                   = array_merge($this->_request->getParams(), $this->_request->getPost(), $jsonData);
		$pageNum                  = (isset($params["page"]    ))? intval($params["page"])     : $stateStore->filters["_documents"]["page"];
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_documents"]["maxitems"];		
		$searchQ                  = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                  = $stateStore->filters["_documents"];
        //$params                   = $filters = $this->_request->getPost();	
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}	 			
 
		 
		$stateStore->filters["_documents"] = $filters;
		$orders                           = array("D.creationdate DESC","D.documentid DESC");
 
		try {
		   $documents                      = $model->getList($filters,$pageNum, $pageSize,$orders);
		   $paginator                      = $model->getListPaginator($filters);
		} catch(Exception $e) {
		   $errorMessages[]               = sprintf("Une erreur s'est produite : %s", $e->getMessage());
		}
		$total                            = count($documents);
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
			$total                        =  $paginator->getTotalItemCount();
		}
		if( count($documents) && empty($errorMessages) ) {
			$responseData                 = array("data"=>$documents,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData                 = array("data"=>$documents,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
		 		
	}
	
	
	public function infosAction(){
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response            = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		
		$model               = $modelDocument = $this->getModel("apidocument");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$prefixName          = $modelTable->info("namePrefix");
		$fileData            = $fileDataB64 = null;
		$errorMessages       = $document    = array();
		$localBasePath       =  APPLICATION_DATA_PATH;
		$rawData             = file_get_contents("php://input");
		$jsonData            = array("numrccm"=>"","documentid"=>0);
		if( $this->_isJson($rawData) ) {
			$jsonData        = json_decode($rawData, true);
		}
		$params              = array_merge($this->_request->getParams(), $this->_request->getPost(),$jsonData);
		$id                  = (isset($params["id"])         && intval($params["id"]        ))? intval($params["id"]) : 0;
		$documentid          = (isset($params["documentid"]) && intval($params["documentid"]))? intval($params["documentid"]) : $id;
		$registreid          = (isset($params["registreid"]) && intval($params["registreid"]))? intval($params["registreid"])  : 0;
		$numRccm             = (isset($params["numero"]) && !empty($params["numero"]))? strip_tags($params["numero"])  : (isset($params["numrccm"])?$params["numrccm"] : "");
		try {
		     $document                = $model->document($documentid);
		} catch(Exception $e ) {
			$errorMessages[]          = sprintf("Erreur d'extraction du document : %s", $e->getMessage());
		}
		if(!isset($document["filepath"])) {
			$errorMessages[]              = sprintf("Erreur d'extraction du document : Document non trouvé : ".$documentid);
		} elseif( isset($document["filepath"]) && empty($document["filepath"])) {
			$errorMessages[]              = sprintf("Erreur d'extraction du document : chemin non trouvé");
		} elseif( isset($document["filepath"]) && !empty($document["filepath"])) {
			$filePath                     = $document["filepath"];
			$documentid                   = $document["documentid"];
			$fileDocumentRoot             = substr($filePath, 0, stripos($filePath, "privatedata"));
		    $filename                     = str_replace($fileDocumentRoot,DOCUMENTS_PATH.DS,$filePath);
			$url                          = $document["download_url"] = str_replace(APPLICATION_PATH,VIEW_BASE_URI."/myV1",$filePath); 
			//On copie le fichier vers une source privée
			
			$maxSize                      = 2145728; 
			$linkFile                     = "";
			if( file_exists($filename) ) {
                clearstatcache(true, $filename);				
				$fileData                 = file_get_contents($filename);
                $fileDataB64              = base64_encode($fileData);
				$document["filepath"]     = $filename;
				$document["filesize"]     = $filesize = filesize($filename); 
				$document["filemime"]     = mime_content_type($filename);
				$document["filedata"]     = ($filesize<=$maxSize)?$fileDataB64 : "";
				$document["download_url"] = $document["downloadUrl"] = "http://102.207.225.155:3231/erccm/api/documents/download/documentid/".$documentid."?id=".$documentid;
				$document["filename"]     = basename($filename);
			}
		}
		if( isset($document["filedata"]) && (!empty($document["filedata"]) || !empty($document["download_url"])) && empty($errorMessages) ) {
			$responseData             = array("data"=>$document,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$response->clearAllHeaders();
			$response->setHttpResponseCode(500);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
	}
	
	public function downloadAction(){
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		 
		clearstatcache() ;
		$model               = $modelDocument = $this->getModel("apidocument");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$prefixName          = $modelTable->info("namePrefix");
		$fileData            = $fileDataB64 = null;
		$errorMessages       = $document    = array();
		
		$localBasePath       =  APPLICATION_DATA_PATH;
		$rawData             = file_get_contents("php://input");
		$jsonData            = array("numrccm"=>"","documentid"=>0);
		if( $this->_isJson($rawData) ) {
			$jsonData        = json_decode($rawData, true);
		}
		$params              = array_merge($this->_request->getParams(), $this->_request->getPost(),$jsonData);
		$id                  = (isset($params["id"])         && intval($params["id"]        ))? intval($params["id"]) : 0;
		$documentid          = (isset($params["documentid"]) && intval($params["documentid"]))? intval($params["documentid"]) : $id;
		$registreid          = (isset($params["registreid"]) && intval($params["registreid"]))? intval($params["registreid"])  : 0;
		$numRccm             = (isset($params["numero"]) && !empty($params["numero"]))? strip_tags($params["numero"])  : (isset($params["numrccm"])?$params["numrccm"] : "");
		$filename            = "";
		try {
		     $document       = $model->document($documentid);
		} catch(Exception $e ) {
			$errorMessages[] = sprintf("Erreur d'extraction du document : %s", $e->getMessage());
		}
		if(!isset($document["filepath"])) {
			$errorMessages[] = sprintf("Erreur d'extraction du document : Document non trouvé");
		} elseif( isset($document["filepath"]) && empty($document["filepath"])) {
			$errorMessages[] = sprintf("Erreur d'extraction du document : Document non trouvé");
		} elseif( isset($document["filepath"]) && !empty($document["filepath"])) {
			$filePath        = $document["filepath"];
			$documentid      = $document["documentid"];
			$fileDocumentRoot= substr($filePath, 0, stripos($filePath, "privatedata"));
		    $filename        = str_replace($fileDocumentRoot,DOCUMENTS_PATH.DS,$filePath);
 
		}
		if( empty($errorMessages) && file_exists($filename)) {
			$documentExtension        = strtolower( Sirah_Filesystem::getFilextension($filename));			
			$contentType              = "application/pdf";
			switch( $documentExtension ) {
				case "doc" :
				case "docx":
					$contentType      = "application/msword";
					break;
				case "pdf" :
					$contentType      = "application/pdf";
					break;
				case "xls":
				case "xlsx":
					$contentType      = "application/excel";
					break;
				case "png":
				case "gif":
				case "jpg":
				case "jpeg":
				case "bmp":
					$contentType      = "image/*";
					break;
				default:
					$contentType      = "application/octet-stream";		
			}		
			$contentType              = "application/pdf"; 
			header('Content-Description: File Transfer');
			header('Content-Type: '.$contentType );
			header('Content-Disposition: attachment; filename='.basename($filename) );
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename) );
			
			$content = ob_get_clean();		
			flush();
			readfile( $filename );
			exit;
		} else {
			$response->setHeader("Content-Type","application/json",true);
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>0,"numpage"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
		 
		
 
	}
	
  
	
	protected function _lastDocumentId() {
		$model               = $modelDocument = $this->getModel("document");
		$modelTable          = $model->getTable();
		$dbAdapter           = $modelTable->getAdapter();
		$prefixName          = $modelTable->info("namePrefix");
		$documentid          = 1;
		$selectLastDocument  = $dbAdapter->select()->from(array("R"=>$tablePrefix."system_users_documents"),array("R.documentid"))
												   ->order(array("R.documentid DESC"))
												   ->limitPage(1,1);
		$lastDbDocumentId    = $dbAdapter->fetchOne($selectLastDocument);
		if( $lastDbDocumentId ) {
			$documentid      = $lastDbDocumentId + 20;
		}
		while( $model->findRow(intval($documentid),"documentid",null,false) ) {
			   $documentid   = $documentid + 5;
		}
		return $documentid;
	}
	
	 
	
	
	 
	
	 
}