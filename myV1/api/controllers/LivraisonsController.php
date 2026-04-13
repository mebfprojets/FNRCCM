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


class Api_LivraisonsController extends Sirah_Controller_Default
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
		$response            = $this->getResponse();
		$authorizationHeader = $this->_authorizationHeader();
		if(!preg_match('/Bearer\s(\S+)/',$authorizationHeader, $matches)) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(400);	
			$response->setRawHeader("HTTP/1.1 400 Bad Request");
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"HTTP/1.1 400 Bad Request","status"=>"400"));
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
	
	 
	
	public function listAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                 = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		
		$model                    = $this->getModel("commandelivraison");
		$modelCommande            = $this->getModel("commande");
		$modelProduit             = $this->getModel("product");
		$modelCategory            = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$modelStatut              = $this->getModel("commandestatut");
		
		$paginator                = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize                 = (isset($params["maxitems"] ))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$searchQ                  = (isset($params["searchq"]  ))? $stringFilter->filter($params["searchq"]) : (isset($params["globalfilter"])?$params["globalfilter"] : "");
		
		$filters                  = array("searchQ"=>$searchQ,"numero"=>null,"name"=>null,"lastname"=>null,"firstname"=>null,"memberid"=>null,"productid"=> null,"registreid"=> null,"catid"=>null,"documentcatid"=> null,"statutid"=>null,
		                                  "delivered"=>4,"date_day"=>null,"date_month"=>null,"date_year"=>null,"date"=>null,
										  "periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);
		if(!empty(   $params  )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year"  => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"],"month" => $filters["periode_start_month"],"day" => $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month" => $filters["periode_end_month"]  ,"day"   => $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ( $zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]   = ( $zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}
		$livraisons                   = $model->getList($filters,$pageNum, $pageSize);
		$paginator                    = $model->getListPaginator($filters );
		$total                        = 0;
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
			$total                    =  $paginator->getTotalItemCount();
		}
		if( count($livraisons) && empty($errorMessages) ) {
			$responseData             = array("data"=>$livraisons,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData             = array("data"=>$livraisons,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(500);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(204);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
		 		
	}
	
	
	public function productsAction(){
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$model                   = $this->getModel("commandelivraison");
		$modelLigne              = $this->getModel("commandelivraisonligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $this->getModel("member");
 			
		$livraisonid             = intval( $this->_getParam("livraisonid", $this->_getParam("id", 0 )));
		$livraison               = $model->findRow($livraisonid,"livraisonid", null, false );
		if(!$livraison ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(500);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune livraison valide n'a été trouvée avec l'identifiant : %d", $livraisonid),"status"=>"500"));
			exit;
		}
		$livraisonProducts       = $livraison->products($livraisonid);
		$total                   = count($livraisonProducts);
		if( $total ) {
			$responseData        = array("data"=>$livraisonProducts,"paginator"=>null,"total"=>$total,"pageSize"=>0,"numpage"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} else {
			$responseData        = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>0,"numpage"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}	
	}
	
	public function undeliveredproductsAction(){
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$model                   = $this->getModel("commandelivraison");
		$modelLigne              = $this->getModel("commandelivraisonligne");
 			
		$livraisonid             = intval( $this->_getParam("livraisonid", $this->_getParam("id", 0 )));
		 
		$livraisonProducts       = $model->undeliveredproducts($livraisonid);
		$total                   = count($livraisonProducts);
		if( $total ) {
			$responseData        = array("data"=>$livraisonProducts,"paginator"=>null,"total"=>$total,"pageSize"=>0,"numpage"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} else {
			$responseData        = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>0,"numpage"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}	
	}
	
	
	public function updateproductsAction()
	{
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                      = Sirah_Fabric::getUser();
		
		$model                   = $this->getModel("commandelivraison");
		$modelLigne              = $this->getModel("commandelivraisonligne");
		$modelCommande           = $this->getModel("commande");
		$modelCommandeLigne      = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $this->getModel("member");
		$modelRegistre           = $this->getModel("registre");
		
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$stringFilter            = new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
		
		$postData                = $this->_request->getPost();	
		$postData["livraisonid"] = (isset($postData["livraisonid"]))?intval($postData["livraisonid"]) : 0;
		$livraisonid             = intval($this->_getParam("id", $postData["livraisonid"]));
		$successMessages         = $errorMessages = array();
		
		
		if(!intval($livraisonid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(204);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune instance de livraison n'a été trouvée avec l'ID %d",$livraisonid),"status"=>"204"));
			exit;
		}
		$livraisonRow             = $model->findRow(intval($livraisonid),"livraisonid",null,false);
		$commandeRow              = ($livraisonRow)?$modelCommande->findRow($livraisonRow->commandeid,"commandeid",null,false) : null;
		if(!$livraisonRow || !$commandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune instance de livraison n'a été trouvée avec l'ID %d",$livraisonid),"status"=>"204"));
			exit;
		}
		$livraison                = $livraisonRow;
		$commande                 = $commandeRow;
		$commandeid               = $commandeRow->commandeid;
		$productid                = (isset( $postData["productid"] ))? intval($postData["productid"])         : 0;
		$productRow               = (intval($productid))?$modelProduct->findRow(intval($productid),"productid",null,false) : null;
		
		$commandeLigne            = (intval($productid))?$modelCommandeLigne->getRow($commandeid,$productid)  : null;
		if(!$productRow || !$commandeLigne) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun produit n'a été trouvé avec l'ID %d",$productid),"status"=>"204"));
			exit;
		}
		$livraisonLigne                  = (intval($productid))?$modelLigne->getRow($livraisonid,$productid) : null;
		if( $livraisonLigne ) {
			$livraisonLigneData          = $livraisonLigne->toArray();
			$updateLivraisonLigne        = array_merge($livraisonLigneData, array_intersect_key($postData, $livraisonLigneData));
			if( isset($updateLivraisonLigne["livraisonid"]) ) {
				unset($updateLivraisonLigne["livraisonid"]);
			}
			if( isset($updateLivraisonLigne["productid"]) ) {
				unset($updateLivraisonLigne["productid"]);
			}
			try {
				if(!$dbAdapter->update( $prefixName."erccm_vente_commandes_livraisons_ligne",$updateLivraisonLigne,array("livraisonid=?"=>$livraisonid,"productid=?"=>$productid))) {
					$errorMessages[]     = sprintf("La livraison du produit %s n'a pas été enregistrée pour la comande ID %s", $productRow->libelle,$commandeid);
				} else {
					$successMessages[]   = sprintf("La livraison du produit %s a été enregistrée pour la comande ID %s", $productRow->libelle,$commandeid);
				}
			}catch(Exception $e ) {
				$errorMessages[]         = sprintf("La livraison du produit %s n'a pas été enregistrée pour la comande ID %s : ", $productRow->libelle,$commandeid, $e->getMessage());
			}			
		} else {
			try {
				$emptyLivraisonLigneData = $modelLigne->getEmptyData();
				$insertLivraisonLigne    = array_merge($emptyLivraisonLigneData, array_intersect_key($postData, $emptyLivraisonLigneData));
				if(!$dbAdapter->insert( $prefixName."erccm_vente_commandes_livraisons_ligne", $insertLivraisonLigne)) {
					$errorMessages[]     = sprintf("La livraison du produit %s n'a pas été enregistrée pour la comande ID %s", $productRow->libelle,$commandeid);
				} else {
					$successMessages[]   = sprintf("La livraison du produit %s a été enregistrée pour la comande ID %s", $productRow->libelle,$commandeid);
				}
			}catch( Exception $e ) {
				$errorMessages[]         = sprintf("La livraison du produit %s n'a pas été enregistrée pour la comande ID %s : ", $productRow->libelle,$commandeid, $e->getMessage());
			}
			
		}
		if( count($successMessages) ) {			
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->setHeader("Content-type","application/json",true);
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->setHeader("Content-type","application/json",true);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		} 
	}
	
	
	
	public function deliverproductAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                      = Sirah_Fabric::getUser();
		
		$model                   = $this->getModel("commandelivraison");
		$modelLigne              = $this->getModel("commandelivraisonligne");
		$modelCommande           = $this->getModel("commande");
		$modelCommandeLigne      = $this->getModel("commandeligne");
		$modelProduit            = $modelProduct = $this->getModel("product");
		$modelCategory           = $this->getModel("productcategorie");
        $modelClient	         = $this->getModel("member");
		$modelRegistre           = $this->getModel("registre");
		
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$stringFilter            = new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
		
		$postData                = $this->_request->getPost();	
		$postData["livraisonid"] = (isset($postData["livraisonid"]))?intval($postData["livraisonid"]) : 0;
		$livraisonid             = intval($this->_getParam("id", $postData["livraisonid"]));
		$successMessages         = $errorMessages = array();
		
		
		if(!intval($livraisonid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(204);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune instance de livraison n'a été trouvée avec l'ID %d",$livraisonid),"status"=>"204"));
			exit;
		}
		$livraisonRow             = $model->findRow(intval($livraisonid),"livraisonid",null,false);
		$commandeRow              = ($livraisonRow)?$modelCommande->findRow($livraisonRow->commandeid,"commandeid",null,false) : null;
		if(!$livraisonRow || !$commandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(204);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune instance de livraison n'a été trouvée avec l'ID %d",$livraisonid),"status"=>"204"));
			exit;
		}
		$livraison                = $livraisonRow;
		$commande                 = $commandeRow;
		$commandeid               = $commandeRow->commandeid;
		$registreid               = (isset( $postData["registreid"]))? intval($postData["registreid"]) : 0;
		$documentid               = (isset( $postData["documentid"]))? intval($postData["documentid"]) : 0;
		$productid                = (isset( $postData["productid"] ))? intval($postData["productid"])  : 0;
		$productRow               = (intval($productid))?$modelProduct->findRow(intval($productid),"productid",null,false) : null;
		$livraisonLigne           = (intval($productid))?$modelLigne->getRow($livraisonid,$productid)  : null;
		$commandeLigne            = (intval($productid))?$modelCommandeLigne->getRow($commandeid,$productid)  : null;
		if(!$productRow || !$livraisonLigne || !$commandeLigne) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(204);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucun produit n'a été trouvé avec l'ID %d",$productid),"status"=>"204"));
			exit;
		}
		$documentStored                    = false;
		
		$documentCreatorId                 = (isset( $postData["document_userid"]         ))? intval($postData["document_userid"])  : 0;						
		$filename                          = (isset( $postData["document_filename"]       ))? $postData["document_filename"]        : md5(sprintf("Doc%08d_%08d",$commandeid,$documentid));
		$filextension                      = (isset( $postData["document_filextension"]   ))? $postData["document_filextension"]    : "pdf";
		$filesize                          = (isset( $postData["document_filesize"]       ))? $postData["document_filesize"]        : 0;
		$filedescription                   = (isset( $postData["document_filedescription"]))? $postData["document_filedescription"] : $commande->libelle;
		$filepath                          = sprintf("%s/commandes/%s.%s", APPLICATION_DATA_PATH,md5(sprintf("Doc%08d_%08d",$commandeid,$documentid)), $filextension);
		$gedFilePath                       = (isset( $postData["document_ged_filepath"]   ))? $postData["document_ged_filepath"]    : "";
	    $document                          = (intval($documentid))?$modelDocument->findRow(intval($documentid),"documentid",null,false) : null;
		$registre                          = (intval($registreid))?$modelRegistre->findRow(intval($registreid),"registreid",null,false) : null;
		if(!empty($gedFilePath)) {
			$filepath                      = sprintf("%s/%s", APPLICATION_DATA_PATH, stristr($gedFilePath,"GED",false));
		}
		if( $document && $registre ) {	
            $NumeroRCCM                    = $registre->numero;		
            if(!file_exists($filepath)) {
				$response->clearAllHeaders();
				$response->setHttpResponseCode(200);
				$response->setHeader("Content-type","application/json",true);			
				$response->sendHeaders();
				echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Le document %d du RCCM n° %s n'a pas été trouvé: productid#%d,livraisonid#%d,commande#%d %d",$documentid,$NumeroRCCM,$productid,$livraisonid,$commandeid),"status"=>"204"));
				exit;
			}	
            $documentData                        = $document->toArray();
            if( isset($documentData["documentid"])) {
				unset($documentData["documentid"]);
			}				
			$documentData["updateduserid"]       = $documentCreatorId;
			$documentData["updatedate"]          = time();
			$documentData["filextension"]        = $filextension;
			$documentData["filesize"]            = $filesize;
			$documentData["filepath"]            = $filepath;
			if( $document->save()) {
				$documentStored                  = true;
			} else {
				$errorMessages[]                 = sprintf("Le document %s du RCCM % n'a pas mis à jour", $filename, $NumeroRCCM);
			}
		} else {
			$documentData                        = $modelDocument->getEmptyData();
			$documentData                        = array_merge($documentData,array("userid"=>$documentCreatorId,"category"=>15,"filename"=>$filename,"filepath"=>$filepath,"filextension"=>$filextension,"filesize"=>$filesize,"resourceid"=>50,"resource"=>"commandes","filedescription"=>$filedescription,"filemetadata"=>sprintf("%s,%s,%d,commande",$commande->ref,$commande->libelle,$commandeid)));
			if(!$document) {
				$documentData["documentid"]      = $documentid;				
				$documentData["creatoruserid"]   = $documentCreatorId;
				$documentData["creationdate"]    = time();
				$documentData["updateduserid"]   = $documentData["updatedate"] = 0;
				if( $dbAdapter->insert( $prefixName."system_users_documents", $documentData)) {
					$documentStored              = true;
				}				
			} else {
				$documentData                    = $document->toArray();
				if( isset($documentData["documentid"])) {
					unset($documentData["documentid"]);
				}
				$documentData["updateduserid"]   = $documentCreatorId;
				$documentData["updatedate"]      = time();
				$documentData["filename"]        = $filename;
				$documentData["filedescription"] = $filedescription;
				$documentData["filextension"]    = $filextension;
				$documentData["filesize"]        = $filesize;
				$documentData["filepath"]        = $filepath;
				if( $dbAdapter->update($prefixName."system_users_documents", $documentData, array("documentid=?"=>$documentid))) {
					$documentStored              = true;
				}
			}			
			if( $documentStored==true ) {
				$dbAdapter->update($prefixName."erccm_vente_commandes_ligne"           , array("documentid"=>$documentid), array("commandeid=?"=>$commandeid,"productid=?"=>$productid));
				$dbAdapter->update($prefixName."erccm_vente_commandes_livraisons_ligne", array("documentid"=>$documentid,"delivered"=>1), array("commandeid=?"=>$commandeid,"productid=?"=>$productid));
			    
				$DOCUMENT_EXPIRATION                 = 7776000;
				$registreDoc                         = ($registreid && $registre)?$registre->document($documentid) : null;
				$memberid                            = $commande->memberid;
				$catid                               = $productRow->catid;
				$documentcatid                       = $productRow->documentcatid;
				$documentTitle                       = ( $registreDoc )?$registreDoc->categorie : $productRow->libelle;
				$documentCode                        = sprintf("Doc_%d/%d/%d", $documentid,$productid,$commandeid);
				$memberDocumentData                  = array("memberid"=>$memberid,"productid"=>$productid,"catid"=>$catid,"documentid"=>$documentid,"registreid"=>$registreid,"code"=>$documentCode,"libelle"=>$documentTitle,"description"=>$filedescription);
			    $memberDocumentData["datexpiration"] = (isset($postData["document_expiration"]))?$postData["document_expiration"] : (time()+$DOCUMENT_EXPIRATION);
			    $memberDocumentData["creationdate"]  = time();
				$memberDocumentData["creatorid"]     = $commande->accountid;
				$memberDocumentData["updateduserid"] = $memberDocumentData["updatedate"]  = 0;
				$dbAdapter->delete(     $prefixName."erccm_vente_members_products", array("commandeid=?"=>$commandeid,"productid=?"=>$productid,"memberid=?"=>$memberid));
				if(!$dbAdapter->insert( $prefixName."erccm_vente_members_products", $memberDocumentData)) {
					$errorMessages[]                 = sprintf("Le document %s du RCCM % n'a été enregistré correctement", $filename, $NumeroRCCM);
				} else {
					$succesMessages[]                = sprintf("Les informations du document %s ont été enregistrées avec succès", $filename);
				}
			}
		}
	}
	 
	
	
    public function insertAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                      = Sirah_Fabric::getUser();
		
		$model                   = $this->getModel("commandelivraison");
		$modelLigne              = $this->getModel("commandelivraisonligne");
		$modelCommande           = $this->getModel("commande");
		
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$stringFilter            = new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
		
		$successMessages         = $errorMessages = array();
		
		$postData                = $this->_request->getPost();	
		$postData["livraisonid"] = (isset($postData["livraisonid"]))?intval($postData["livraisonid"]) : 0;
		$postData["commandeid"]  = $commandeid    = (isset($postData["commandeid"]))?intval($postData["commandeid"]) : 0;
		$livraisonid             = intval($this->_getParam("id", $postData["livraisonid"]));
		$successMessages         = $errorMessages = array();
		
		
		if(!intval($commandeid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune commande n'a été trouvée avec l'ID %d",$commandeid),"status"=>"204"));
			exit;
		}
 
		$commandeRow               =  $commande = $modelCommande->findRow($commandeid,"commandeid",null,false);
		if(!$commandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune commande n'a été trouvée avec l'ID %d",$commandeid),"status"=>"204"));
			exit;
		}
		$livraisonRow             = (intval($livraisonid))?$model->findRow($livraisonid,"livraisonid",null,false) : null;
		if( $livraisonRow ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("La livraison %d existe déjà",$livraisonid),"status"=>"200"));
			exit;
		}
		$emptyData                = $model->getEmptyData();
		$cleanInsertData          = array_merge($emptyData, array_intersect_key($postData,$emptyData));
		
		try {
			if( isset($cleanInsertData["livraisonid"]) && !intval($cleanInsertData["livraisonid"])) {
				unset($cleanInsertData["livraisonid"]);
			}
			if(!isset($cleanInsertData["creationdate"])) {
				$cleanInsertData["creationdate"]  = time();
				$cleanInsertData["creatorid"]     = 1;
			}
			if(!isset($cleanInsertData["updatedate"])) {
				$cleanInsertData["updatedate"]    = $cleanInsertData["updateduserid"] = 0;
			}
			if(!$dbAdapter->insert($prefixName."erccm_vente_commandes_livraisons", $cleanInsertData)) {
				$errorMessages[]   = sprintf("La livraison %s n'a pas été enregistrée.", $cleanInsertData["libelle"]);
			} else {
				$successMessages[] = sprintf("Livraison %s enregistrée avec succès.", $cleanInsertData["libelle"]);
			}
		} catch(Exception $e ) {
			    $errorMessages[]   = sprintf("Une erreur technique s'est produite dans la livraison de %s : %s ",$cleanInsertData["libelle"] , $e->getMessage());
		}
		if( count($successMessages) ) {			
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->setHeader("Content-type","application/json",true);
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->setHeader("Content-type","application/json",true);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		} 

	}	
	
	
	public function updateAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                    = Sirah_Fabric::getUser();
		$model                 = $modelDemande = $this->getModel("demande");
		$modelDocument         = $this->getModel("document");
		$modelMember           = $this->getModel("member");
		$modelCommandeLigne    = $this->getModel("commandeligne");
		$postData              = $this->_request->getPost();	
		$postData["demandeid"] = (isset($postData["demandeid"]))?intval($postData["demandeid"]) : 0;
		$demandeid             = intval($this->_getParam("id", $postData["demandeid"]));
		$successMessages       = $errorMessages = array();
		
		if(!intval($demandeid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec l'ID %d",$demandeid),"status"=>"204"));
			exit;
		}
		$demandeRow             = $model->findRow(intval($demandeid),"demandeid",null,false);
		if(!$demandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec l'ID %d",$demandeid),"status"=>"204"));
			exit;
		}
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$prefixName            = $modelTable->info("namePrefix");
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$demandeid             = $demandeRow->demandeid;
		$sync_demandeid        = (isset($postData["sync_demandeid"]))? intval($postData["sync_demandeid"]) : 0;
		$updatedate            = (isset($postData["updatedate"]    ))? intval($postData["updatedate"])     : 0;
		
		if( intval($sync_demandeid) && ($sync_demandeid!=$demandeid)) {
			$demandeSyncDemande= $model->findRow(intval($sync_demandeid),"demandeid",null,false);
			if(!$demandeSyncDemande) {
				$defaultData   = $model->getEmptyData();
				$demandeData   = array_intersect_key($postData, $defaultData);
				if( isset($demandeData["demandeid"])) {
					unset($demandeData["demandeid"]);
				}
				if( isset($demandeData["creatorid"])) {
					unset($demandeData["creatorid"]);
				}
				if( isset($demandeData["creationdate"])) {
					unset($demandeData["creationdate"]);
				}
				$demandeData["demandeid"]  = intval($sync_demandeid);
				if( $dbAdapter->update($prefixName."reservation_demandes"            , $demandeData,array("demandeid=?"=>intval($demandeid)))) {
					$dbAdapter->update($prefixName."reservation_demandes_entreprises", array("demandeid"=>intval($sync_demandeid)),array("demandeid=?"=>intval($demandeid)));
				    $dbAdapter->update($prefixName."reservation_demandes_documents"  , array("demandeid"=>intval($sync_demandeid)),array("demandeid=?"=>intval($demandeid)));
				    $dbAdapter->update($prefixName."erccm_vente_commandes_ligne"     , array("demandeid"=>intval($sync_demandeid)),array("demandeid=?"=>intval($demandeid)));
					$successMessages[] = sprintf("La demande ID %d a été mis à jour avec succès", $demandeid);
				} else {
					$errorMessages[]   = sprintf("La demande ID %d n'a pas été mise à jour", $demandeid);
				}
			} else {
				    $errorMessages[]   = sprintf("Une demande existante porte le ID : %d en liaison avec le membre ID :%d", $sync_demandeid, $demandeid);
			}
		} elseif(intval($sync_demandeid) && ($sync_demandeid==$demandeid) && intval($updatedate)) {
			$demandeRow                = $model->findRow(intval($sync_demandeid),"demandeid",null,false);
			if( $demandeRow) {
				$defaultData           = $demandeRow->toArray();
				$updatedData           = array_merge($defaultData, array_intersect_key( $postData, $defaultData));
				if( isset($updatedData["demandeid"]) ) {
					unset($updatedData["demandeid"]);
				}
				if( isset($updatedData["creatorid"])) {
					unset($updatedData["creatorid"]);
				}
				if( isset($updatedData["creationdate"])) {
					unset($updatedData["creationdate"]);
				}
				$demandeRow->setFromArray($updatedData);
				if( $demandeRow->save() ) {
					$successMessages[]                 = sprintf("La demande ID %d a été mise à jour avec succès", $demandeid);
					if( isset($postData["documentid"]) && intval($postData["documentid"]     )) {
						$documentid                    =  intval($postData["documentid"]);
						$documentCreatorId             = (isset( $postData["document_userid"]         ))? intval($postData["document_userid"])  : 0;
						
						$filename                      = (isset( $postData["document_filename"]       ))? $postData["document_filename"]        : md5(sprintf("Doc%05d_%05d",$demandeid,$documentid));
						$filextension                  = (isset( $postData["document_filextension"]   ))? $postData["document_filextension"]    : "pdf";
						$filesize                      = (isset( $postData["document_filesize"]       ))? $postData["document_filesize"]        : 0;
						$filedescription               = (isset( $postData["document_filedescription"]))? $postData["document_filedescription"] : $demande->libelle;
						$filepath                      = sprintf("%s/demandes/%s.%s", APPLICATION_DATA_PATH,md5(sprintf("Doc%07d_%07d",$demandeid,$documentid)), $filextension);
						
						$documentData                  = $modelDocument->getEmptyData();
						$documentData                  = array_merge($documentData,array("userid"=>$documentCreatorId,"category"=>15,"filename"=>$filename,"filepath"=>$filepath,"filextension"=>$filextension,"filesize"=>$filesize,"resourceid"=>50,"resource"=>"demandes","filedescription"=>$filedescription,"filemetadata"=>sprintf("%s,%s,%d,demande", $demande->numero,$demande->objet,$demandeid)));
						$documentData["creatoruserid"] = $documentCreatorId;
						$documentData["creationdate"]  = time();
						if(!$foundDocument             = $modelDocument->findRow($documentid,"documentid",null,false)) {
							$documentData["documentid"]= $documentid;							
						}
						if( $dbAdapter->insert( $prefixName."system_users_documents", $documentData)) {
							$documentid                = (isset($documentData["documentid"]) && intval($documentData["documentid"]))? $documentData["documentid"] : $dbAdapter->lastInsertId();
						    $documentData["documentid"]= $documentid;
							$demandeurid               = $demandeRow->demandeurid;
							$documentTitle             = $modelDocument->filterIndex($filename ,$documentCreatorId);
							$demandeDocumentRow        = array("documentid"=>$documentid,"demandeid"=>$demandeid,"demandeurid"=>$demandeurid,"contenu"=>"","libelle"=>$documentTitle,"document_type"=>"pdf","creationdate"=>time(),"creatorid"=>$documentCreatorId,"updatedate"=>0,"updateduserid"=>0);
						    $dbAdapter->delete( $prefixName."reservation_demandes_documents",array("demandeid=?"=>$demandeid,"documentid=?"=>$documentid));
							if( $dbAdapter->insert( $prefixName."reservation_demandes_documents", $demandeDocumentRow)) {
								if( $commandeProduct  = $modelCommandeLigne->findRow(intval($demandeid),"demandeid",null,false)) {
								    $articleid        = $commandeProduct->productid;
									$commandeDocument = array("documentid"=>$documentid,"articleid"=>$articleid,"keywords"=>$demande->objet,"libelle"=>$documentTitle,"description"=>$demande->libelle,
									                          "creationdate"=>time(),"creatorid"=>$documentCreatorId,"updatedate"=>0,"updateduserid"=>0);
								    $dbAdapter->delete( $prefixName."reservation_demandes_documents",array("articleid=?"=>$articleid,"documentid=?"=>$documentid));
								    $dbAdapter->insert( $prefixName."reservation_demandes_documents",$commandeDocument);
								}
							}
						} else {
							    $errorMessages[]      = sprintf("Le document %s n'a pas été enregistré ", $filename);
						}
					}
				}
			}
	    } else {
			        $successMessages[] = sprintf("La demande ID %d a été mise à jour avec succès", $demandeid);
		}
		if( count($successMessages) && empty($errorMessages) ) {
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