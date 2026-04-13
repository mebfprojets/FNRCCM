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
class Api_DemandesController extends Sirah_Controller_Default
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
 
	
	public function listAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		
		$model                   = $this->getModel("apidemande");
        $modelType               = $this->getModel("demandetype");	
        $modelStatut             = $this->getModel("demandestatut");
        $modelLocalite           = $this->getModel("localite");		
 	
		$demandes                = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 1));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_demandes" => array());
		}
		if(!isset( $stateStore->filters["_demandes"]["maxitems"])) {
			$stateStore->filters["_demandes"] = array("page"=>1,"maxitems"=>20,"libelle"=>null,"numero"=>null,"nomcommercial"=>null,"localiteid"=>0,"sync_demandeid"=>"-1","demandeid"=>0,"synchronized"=>"-1","searchQ"=>null,"lastname"=>null,"firstname"=>null,"name"=>null,"order"=>"DESC","demandeids"=>array(),
													  "typeid"=>0,"statutid"=>0,"expired"=>0,"disponible"=>4,"paid"=>1,"retried"=>0,"date"=>null,"promoteurid"=>0,"demandeurname"=>null,"promoteurname"=>null,"demandeurid"=>0,"creatorid"=>0,
													  "periode_start"=>null,"periode_end"=>null,"periode_start_day"=>null,"periode_start_month"=>null,"periode_start_year"=>null,"periode_end_day"=>0,"periode_end_month"=>null,"periode_end_year"=>0
                                                );			
		}
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]    ))? intval($params["page"])     : $stateStore->filters["_demandes"]["page"];
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_demandes"]["maxitems"];		
		$searchQ                  = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                  = $stateStore->filters["_demandes"];
        $params                   = array_merge($stateStore->filters["_demandes"], $params);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}	 			
 
		if( isset($filters["name"] )) {
			$nameToArray                  = preg_split("/[\s]+/", $filters["name"]);
			if( count($nameToArray) > 2) {
				$filters["lastname"]      = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["firstname"]     = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["lastname"]      = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["firstname"]     = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]          = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}
		$stateStore->filters["_demandes"] = $filters;
		$orders                           = array("R.date DESC","R.creationdate DESC","R.demandeid DESC");
		if( isset($filters["order"]) && (strtoupper($filters["order"])=="DESC")) {
			$orders                       = array("R.date DESC","R.creationdate DESC","R.demandeid DESC");
		} elseif(isset($filters["order"]) && (strtoupper($filters["order"])=="ASC")) {
			$orders                       = array("R.date ASC","R.creationdate ASC","R.demandeid ASC");
		} else {
			$orders                       = array("R.date DESC","R.creationdate DESC","R.demandeid DESC");
		}
		$filters["demandeids"]            = trim($filters["demandeids"]);
		if( isset($filters["demandeids"]) && !empty($filters["demandeids"]) && is_string($filters["demandeids"]) && !count($filters["demandeids"]) && !is_array( $filters["demandeids"])) {
			$filters["demandeids"]        = implode(",",$filters["demandeids"]);
		}
		try {
		   $demandes                      = $model->getList($filters,$pageNum, $pageSize,$orders);
		   //$paginator                     = $model->getListPaginator($filters);
		} catch(Exception $e) {
		   $errorMessages[]               = sprintf("Une erreur s'est produite : %s", $e->getMessage());
		}
		$total                            = count($demandes);
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
			$total                        =  $paginator->getTotalItemCount();
		}
		if( count($demandes) && empty($errorMessages) ) {
			$responseData                 = array("data"=>$demandes,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData                 = array("data"=>$demandes,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
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
	
 
	public function verifyncAction()
	{
		
		$model               = $this->getModel("demande");
		$modelRegistre       = $this->getModel("demande");
		$modelEntreprise     = $this->getModel("demandentreprise");
		$modelBlacklist      = $this->getModel("demandeblacklist");
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter        =   new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$postData            = $this->_request->getPost();
		$requestData         = $this->_request->getParams();
		
		$query               = (isset($requestData["search"]) && !empty($requestData["search"]))?preg_replace("/[^a-z0-9_\-\s]/i","",strip_tags(base64_decode($requestData["search"]))) : "";
		$available           = 1;
		$errorMessages       = array();
		$keywords            = (isset($postData["keywords"])  && !empty($postData["keywords"] ))?preg_replace("/[^a-z0-9_\-\s]/i","",strip_tags($postData["keywords"])) : $query;
		$query               = $keywords = substr($keywords,0,200);
		if( empty($keywords) ) {
			$errorMessages[] = "Veuillez saisir des mots clés du nom commercial";
		}
		if( empty($errorMessages) ) {
			$demandes       = $modelRegistre->basicList(array("searchQ"=>$keywords,"types" => array(1,2,3,4)), 1,5);
			if( count($demandes) ) {
				$i           = 0;
				foreach( $demandes as $demande ) {
						 $similarites[$i]          = sprintf("%s  : %s", $demande["numero"], $demande["libelle"]);
						 $similariteActivities[$i] = $demande["description"];
						 $demandes[$i]             = $demande;
						 if( strtolower($demande["libelle"]) == strtolower($query)) {
							 $available            = 0;
							 $errorMessages[]      = sprintf("Le nom commercial %s ne semble pas disponible. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.", $query);
							 break;
						 }							
						 $informationMessages[0]   = "Quelques noms commerciaux similaires ont été trouvés. Nous vous recommandons de vous rendre dans une juridiction proche pour approfondir la vérification.";
						 
						 $i++;
				}
			} else {
				$reservedEntreprises  = $modelEntreprise->getList(array("libelle"=>$keywords,"reserved"=>1), 1,5);
				if( count(   $reservedEntreprises) ) {
					foreach( $reservedEntreprises as $reservedEntreprise ) {
							 if( strtolower($reservedEntreprise["nomcommercial"]) ==strtolower($query) || strtolower($reservedEntreprise["sigle"]) == strtolower($query)) {
								 $available        = 0;
								 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.",$query);
								 break;
							 }
					}
				} else {
					$blacklisted = $modelBlacklist->getList(array("searchQ"=>$query),1,5);
					if( count(   $blacklisted)) {
						$i       = 0;
						foreach( $blacklisted as $item ) {
								 if( strtolower($item["libelle"])== strtolower($query)) {
									 $available       = 0;
									 $errorMessages[] = sprintf("Le nom commercial %s n'est pas autorisé. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.",$query);
									 break;
								 }
						}
					}
				}
			}
		}			 
		$response  = $this->getResponse();
		if( count($errorMessages) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>" Nom Commercial Indisponible. Raisons : ".implode("; ",$errorMessages),"status"=>"204"));
			exit;
		} elseif(empty($errorMessages) && $available) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>"Nom commercial disponible","status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>" Nom Commercial Indisponible.","status"=>"204"));
			exit;
		}
	}
	
	
	protected function _lastDemandeId() {
		$model              = $modelDemande = $this->getModel("demande");
		$modelTable         = $model->getTable();
		$dbAdapter          = $modelTable->getAdapter();
		$prefixName         = $modelTable->info("namePrefix");
		$demandeid          = 1;
		$selectLastDemande  = $dbAdapter->select()->from(array("R"=>$tablePrefix."reservation_demandes"),array("R.demandeid"))
												   ->order(array("R.demandeid DESC"))
												   ->limitPage(1,1);
		$lastDbDemandeId    = $dbAdapter->fetchOne($selectLastDemande);
		if( $lastDbDemandeId ) {
			$demandeid      = $lastDbDemandeId + 20;
		}
		while( $model->findRow(intval($demandeid),"demandeid",null,false) ) {
			   $demandeid   = $demandeid + 5;
		}
		return $demandeid;
	}
	
	public function resetidAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                    = Sirah_Fabric::getUser();
		$model                 = $modelDemande = $this->getModel("demande");

		$postData              = $this->_request->getPost();	
		$postData["demandeid"] = (isset($postData["demandeid"]))?intval($postData["demandeid"]) : 0;
		$demandeid             = intval($this->_getParam("id"   , $this->_getParam("demandeid", $postData["demandeid"])));
		$new_demandeid         = intval($this->_getParam("new_demandeid", $postData["new_demandeid"])); 
		$successMessages       = $errorMessages = array();
		$errorCode             = "0";
		if(!intval($demandeid) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec l'ID %d",$demandeid),"errorCode"=>"-2","status"=>"204"));
			exit;
		}
		$demandeRow               = $modelDemande->findRow(intval($demandeid),"demandeid",null,false);
		if( $demandeRow && intval($new_demandeid) ) {
			if( $model->findRow(intval($new_demandeid),"demandeid",null,false)  ) {
				$new_demandeid    = $this->_lastDemandeId();
			}
			while( $modelDemande->findRow(intval($new_demandeid),"demandeid",null,false) ) {
				   $new_demandeid  = $new_demandeid+1;
			}
			$new_demandeid         = $new_demandeid +100;
			$modelTable            = $model->getTable();
			$dbAdapter             = $modelTable->getAdapter();
			$prefixName            = $modelTable->info("namePrefix");
			
			$changeQuery           = "
			    UPDATE `reservation_demandes`             SET demandeid='".$new_demandeid."' WHERE demandeid='".$demandeid."';
				UPDATE `reservation_demandes_entreprises` SET demandeid='".$new_demandeid."' WHERE demandeid='".$demandeid."';
				UPDATE `erccm_vente_commandes_ligne`      SET demandeid='".$new_demandeid."' WHERE demandeid='".$demandeid."'";
			try {
				$queries    = str_getcsv($changeQuery,";");
				if( count(   $queries) ) {
					foreach( $queries as $queryStr ) {
							 if( empty($queryStr) ) {
								 continue;
							 }
							 try {
								 $stmt      = $dbAdapter->query($queryStr); 
								 $rawResult = $stmt->execute();
							 } catch(Exception $e ) {
								 $errorMessages[]  = sprintf("Une erreur s'est produite dans la réinitialisation de l'identifiant de la demande %d => %d : %s ON %s", $demandeid, $new_demandeid, $e->getMessage(), $queryStr);
							 } 
					}
				}
			} catch( Exception $e ) {
				$errorMessages[]  = sprintf("Une erreur s'est produite dans la réinitialisation de l'identifiant de la demande %d => %d : %s", $demandeid, $new_demandeid, $e->getMessage());
			}
		} else {
			    $errorMessages[]  = sprintf("Erreur de réinitialisation de l'identifiant de la demande %d => %d : %s (demande introuvable)", $demandeid, $new_demandeid, $e->getMessage());
		}
		if( empty($errorMessages) ) {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès, le changement s'est produit avec succès, le nouvel identifiant c'est : %d ", $new_demandeid),"demandeid"=>$demandeid,"new_demandeid"=>$new_demandeid,"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("errorCode"=>$errorCode,"response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}
	}
	
	
	public function updatestatutAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                    = Sirah_Fabric::getUser();
		$model                 = $modelDemande = $this->getModel("demande");
		$modelReservation      = $this->getModel("demandereservation");
		$modelEntreprise       = $this->getModel("demandentreprise");
		$modelDocument         = $this->getModel("document");
		$modelMember           = $this->getModel("member");
		$modelCommandeLigne    = $this->getModel("commandeligne");
		$postData              = $this->_request->getPost();	
		$postData["demandeid"] = (isset($postData["demandeid"]))?intval($postData["demandeid"]) : 0;
		$demandeid             = intval($this->_getParam("id", $postData["demandeid"]));
		$entrepriseName        = (isset($postData["entrepriseName"]))? strip_tags($postData["entrepriseName"]) : "";
		$entrepriseKeywords    = (isset($postData["keywords"]      ))? strip_tags($postData["keywords"])       : "";
		$demandeNumero         = $numero = (isset($postData["numero"]))? strip_tags($postData["numero"])       : "";
		$successMessages       = $errorMessages = array();
		$errorCode             = "0";
		if( empty($demandeNumero) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec le numéro %s",$numero),"errorCode"=>"-2","status"=>"204"));
			exit;
		}
		$demandeRow               = $model->findRow(($demandeNumero),"numero",null,false);
		 
		if(!$demandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec le numéro %s",$numero),"errorCode"=>"-2","status"=>"204"));
			exit;
		}
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$prefixName            = $modelTable->info("namePrefix");
		
		$defaultData           = $demandeRow->toArray();
		$updatedData           = array_merge($defaultData, array_intersect_key( $postData, $defaultData));
		if( isset($updatedData["demandeid"]) ) {
			unset($updatedData["demandeid"]);
		}
		if( isset($updatedData["entrepriseid"])) {
			unset($updatedData["entrepriseid"]);
		}
		if( isset($updatedData["demandeurid"])) {
			unset($updatedData["demandeurid"]);
		}
		if( isset($updatedData["promoteurid"])) {
			unset($updatedData["promoteurid"]);
		}
		if( isset($updatedData["creatorid"])) {
			unset($updatedData["creatorid"]);
		}
		if( isset($updatedData["creationdate"])) {
			unset($updatedData["creationdate"]);
		}
		if( isset($updatedData["nomcommercial"])) {
			unset($updatedData["nomcommercial"]);
		}
		if( isset($updatedData["denomination"])) {
			unset($updatedData["denomination"]);
		}
		if( isset($updatedData["keywords"])) {
			unset($updatedData["keywords"]);
		}
		if( isset($updatedData["libelle"])) {
			unset($updatedData["libelle"]);
		}
		if( isset($updatedData["statutid"]) && ($updatedData["statutid"]==7)) {
			$updatedData["statutid"] = 1;
		}
		try {
			$demandeRow->setFromArray($updatedData);
			if( $demandeRow->save() ) {
				$successMessages[]  = sprintf("La demande n° %s a été mise à jour avec succès", $numero);
			} else {
				$errorMessages[]    = sprintf("La demande n° %s n'a pas été mise à jour", $numero);
			}
		} catch(Exception $e ) {
			$errorMessages[]    = sprintf("La demande n° %s n'a pas été mise à jour : %s", $numero, $e->getMessage());
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
			echo ZendX_JQuery::encodeJson(array("errorCode"=>$errorCode,"response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
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
		$modelReservation      = $this->getModel("demandereservation");
		$modelEntreprise       = $this->getModel("demandentreprise");
		$modelDocument         = $this->getModel("document");
		$modelMember           = $this->getModel("member");
		$modelCommandeLigne    = $this->getModel("commandeligne");
		$modelDemandeur        = $this->getModel("demandeur");
		
		$modelTable                 = $model->getTable();
		$dbAdapter                  = $modelTable->getAdapter();
		$prefixName                 = $modelTable->info("namePrefix");
		
		$postData                   = array_merge($this->_request->getParams(),$this->_request->getPost());	
		$postData["demandeid"]      = (isset($postData["demandeid"]     ))? intval($postData["demandeid"])      : 0;
		$postData["sync_demandeid"] = (isset($postData["sync_demandeid"]))? intval($postData["sync_demandeid"]) : 0;
		$postData["synchronized"]   = (isset($postData["synchronized"]  ))? intval($postData["synchronized"])   : 0;
		$demandeid                  = intval($this->_getParam("id"            , $postData["demandeid"]));
		$sync_demandeid             = intval($this->_getParam("sync_demandeid", $postData["sync_demandeid"]));
		$synchronized               = intval($this->_getParam("synchronized"  , $postData["synchronized"]  ));
		$entrepriseName             = (isset($postData["entrepriseName"]))? strip_tags($postData["entrepriseName"]) : "";
		$entrepriseKeywords         = $keywords         = (isset($postData["keywords"]    ))? strip_tags($postData["keywords"])     : "";
		$denomination               = $nomCommercial    = (isset($postData["denomination"]))? strip_tags($postData["denomination"]) : "";
		$objet                      = $demandeObjet     = (isset($postData["objet"]       ))? strip_tags($postData["objet"])        : "";
		$numero                     = $demandeNumero    = (isset($postData["numero"]      ))? strip_tags($postData["numero"])       : "";
		$reference                  = $demandeReference = (isset($postData["reference"]   ))? strip_tags($postData["reference"])    : "";
		$successMessages            = $errorMessages    = array();
		$errorCode                  = "0";
		if(!intval($demandeid) && !intval($sync_demandeid)) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec l'ID %d",$demandeid),"errorCode"=>"-2","status"=>"204"));
			exit;
		}
		$demandeRow               = $model->findRow(intval($demandeid),"demandeid",null,false);
		if(!$demandeRow && intval($sync_demandeid)) {
			$demandeRow           = $model->findRow(intval($sync_demandeid),"sync_demandeid",null,false);
		}
		if(!$demandeRow && !empty($denomination) && !empty($demandeNumero)) {
			$foundDemandeByNumero = $model->getList(array("numero"=>$demandeNumero,"denomination"=>$denomination));
			if( isset($foundDemandeByNumero[0]["demandeid"]) ) {
				$demandeid        = $foundDemandeByNumero[0]["demandeid"];
				$syncDemande      = $demandeRow = $model->findRow($demandeid,"demandeid",null,false);
			}
			if( $demandeRow ) {
				$demandeid        = $demandeRow->demandeid;
				$demandeNumero    = $demandeRow->numero;
				$denomination     = $demandeRow->denomination;
				$libelle          = $demandeRow->libelle;
				$objet            = $demandeRow->objet;
				$dbAdapter->update( $prefixName."reservation_demandes" ,array("reference"=>$reference,"sync_demandeid"=>$sync_demandeid),array("demandeid=?"=>$demandeid));
						
				$successData      = array("demandeid"=>$demandeid,"objet"=>$objet,"numero"=>$demandeNumero,"denomination"=>$denomination,"libelle"=>$libelle,"reference"=>$reference,"response"=>sprintf("Succès : %s", implode(",",$successMessages)),"remote_demandeid"=>$demandeid,"status"=>"200");
				$response->clearAllHeaders();
			    $response->setHeader("Content-type","application/json",true);
				$response->setHttpResponseCode(200);	
				$response->sendHeaders();
				echo ZendX_JQuery::encodeJson($successData);
				exit;
			}
		}
		if(!$demandeRow) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Aucune demande n'a été trouvée avec l'ID %d",$demandeid),"errorCode"=>"-3","status"=>"204"));
			exit;
		}
		$demandeid             = $demandeRow->demandeid;    
		$demandeNumero         = $demandeRow->numero;
		$entrepriseName        = $demandeRow->denomination;
		$demandeInfos          = $modelDemande->getList(array("demandeid"=>$demandeid));
		
		$demandeurIdentite     = (isset($postData["demandeurIdentite"]))? strip_tags($postData["demandeurIdentite"]) : "";
		if( isset($demandeInfos[0]["demandeurIdentite"]) && !empty($demandeurIdentite)) {
			if( $demandeurIdentite!= $demandeInfos[0]["demandeurIdentite"]) {
				$response->clearAllHeaders();
				$response->setHttpResponseCode(200);
				$response->setHeader("Content-type","application/json",true);			
				$response->sendHeaders();
				echo ZendX_JQuery::encodeJson(array("remote_demandeuridentite"=>$demandeInfos[0]["demandeurIdentite"],"remote_demandeurid"=>$demandeInfos[0]["demandeurid"],"response"=>sprintf("La demande Id : %d, ref : %s avec le nom : %s semble avoir une reférence mandataire invalide. Sur le serveur distant, il s'appelle : %s",$demandeid, $demandeNumero, $entrepriseName, $demandeInfos[0]["demandeurName"]),"errorCode"=>"-6","status"=>"204"));
				exit;
			}
		}
		$promoteurIdentite     = (isset($postData["promoteurIdentite"]))? strip_tags($postData["promoteurIdentite"]) : "";
		if( isset($demandeInfos[0]["promoteurIdentite"]) && !empty($promoteurIdentite)) {
			if( $promoteurIdentite!= $demandeInfos[0]["promoteurIdentite"]) {
				$response->clearAllHeaders();
				$response->setHttpResponseCode(200);
				$response->setHeader("Content-type","application/json",true);			
				$response->sendHeaders();
				echo ZendX_JQuery::encodeJson(array("remote_promoteuridentite"=>$demandeInfos[0]["promoteurIdentite"],"remote_promoteurid"=>$demandeInfos[0]["promoteurid"],"response"=>sprintf("La demande Id : %d, ref : %s avec le nom : %s semble avoir une reférence mandataire invalide. Sur le serveur distant, il s'appelle : %s",$demandeid, $demandeNumero, $entrepriseName, $demandeInfos[0]["promoteurName"]),"errorCode"=>"-7","status"=>"204"));
				exit;
			}
		}
		
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		
		$nomCommercial         = $demandeDenomination = (isset($postData["denomination"]  ))? strip_tags($postData["denomination"]) : "";
		$sync_demandeid        = (isset($postData["sync_demandeid"]))? intval($postData["sync_demandeid"])   : 0;
		$reservationid         = (isset($postData["reservationid"] ))? intval($postData["reservationid"])    : 0;
		$entrepriseid          = (isset($postData["entrepriseid"]  ))? intval($postData["entrepriseid"])     : $demandeRow->entrepriseid;
		$updatedate            = (isset($postData["updatedate"]    ))? intval($postData["updatedate"])       : 0;
		$demandeNumero         = (isset($postData["numero"]        ))? strip_tags($postData["numero"])       : "";
		$demandeReference      = $reference = (isset($postData["reference"]     ))? strip_tags($postData["reference"])    : "";
		$demandeObjet          = (isset($postData["objet"]         ))? strip_tags($postData["objet"])        : "";
		$syncDemande           = $demandeRow;
		try {
			if( isset($postData["statutid"]) && (intval($postData["statutid"])==7)) {
				$postData["statutid"] = 1;
			}
			if( intval($demandeid)) {
				$syncDemande          = $model->findRow( intval($demandeid),"demandeid",null,false);
			}
			if((!$syncDemande || ($syncDemande->denomination!=$demandeDenomination)) && intval($sync_demandeid)) {
				 $syncDemande         = $model->findRow( intval($sync_demandeid),"sync_demandeid",null,false);
			}
			 
			if(!$syncDemande && !empty($demandeReference)) {
				$syncDemande          = $model->findRow($demandeReference,"reference",null,false);
			}
			if(!$syncDemande && !empty($demandeNumero)) {
				$foundDemandeByNumero = $model->getList(array("numero"=>$demandeNumero,"denomination"=>$nomCommercial));
				if( isset($foundDemandeByNumero[0]["demandeid"]) ) {
					$syncDemande      = $model->findRow($foundDemandeByNumero[0]["demandeid"],"demandeid",null,false);
				}
			}
		} catch(Exception $e ) {
			$errorMessages[]          = sprintf("Erreur Technique : %s", $e->getMessage());
		}
		if( $syncDemande && (strcasecmp(trim($syncDemande->denomination),trim($demandeDenomination))!=0) && (trim($syncDemande->numero)!=trim($demandeNumero))) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);
			$response->setHeader("Content-type","application/json",true);			
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array("denomination_reelle"=>$syncDemande->denomination,"denomination_transmise"=>$demandeDenomination,"response"=>sprintf("La demande Id : %d, ref : %s avec le nom : %s semble avoir un nom commercial différent en ligne. Sur le serveur distant, il s'appelle : %s",$demandeid, $demandeNumero, $entrepriseName,$syncDemande->denomination),"errorCode"=>"-8","status"=>"204"));
			exit;
		}
		$updatedData       = $updatedDemandeData = $postData;
		if( $syncDemande && !$synchronized) {
			$demandeid     = $syncDemande->demandeid;
			$sync_demandeid= $syncDemande->sync_demandeid;
			$defaultData   = $model->getEmptyData();
			$demandeData   = array_intersect_key($postData, $defaultData);
			if( isset($demandeData["demandeid"])) {
				unset($demandeData["demandeid"]);
			}
			if( isset($demandeData["nomcommercial"])) {
				unset($demandeData["nomcommercial"]);
			}
			if( isset($demandeData["denomination"])) {
				unset($demandeData["denomination"]);
			}
			if( isset($demandeData["keywords"])) {
				unset($demandeData["keywords"]);
			}
			if( isset($demandeData["libelle"])) {
				unset($demandeData["libelle"]);
			}
			if( isset($demandeData["accountid"])) {
				unset($demandeData["accountid"]);
			}
			if( isset($demandeData["memberid"])) {
				unset($demandeData["memberid"]);
			}
			if( isset($demandeData["entrepriseid"])) {
				unset($demandeData["entrepriseid"]);
			}
			if( isset($demandeData["demandeurid"])) {
				unset($demandeData["demandeurid"]);
			}
			if( isset($demandeData["promoteurid"])) {
				unset($demandeData["promoteurid"]);
			}
			if( isset($demandeData["creatorid"])) {
				unset($demandeData["creatorid"]);
			}
			if( isset($demandeData["creationdate"])) {
				unset($demandeData["creationdate"]);
			}
			$updatedDemandeData                    = array();
			$updatedDemandeData["sync_demandeid"]  = intval($sync_demandeid);
			$updatedDemandeData["reference"]       = $reference;
			$updatedDemandeData["updatedate"]      = time();
			$updatedDemandeData["updateduserid"]   = 26;
			if( isset($postData["statutid"]) && intval($postData["statutid"])) {
				if( intval($postData["statutid"])==7) {
					$postData["statutid"]          = 1;
				}
				$updatedDemandeData["statutid"]    = intval($postData["statutid"]);
			}
			try {
				if( $dbAdapter->update( $prefixName."reservation_demandes",$updatedDemandeData,array("demandeid=?"=>intval($demandeid)))) {
					$updatedDemandeData   = $updatedData  = $demandeData;
					$successMessages[]    = sprintf("La demande ID %d a été synchronisée avec succès", $demandeid);
				} else {
					$errorMessages[]      = sprintf("La demande ID %d concernant %s n'a pas été mise à jour. ", $demandeid,$demandeObjet);
				}
			} catch(Exception $e) {
				$errorMessages[]          = sprintf("La demande ID %d n'a pas été mise à jour. Motif : %s", $demandeid,$demandeObjet,$e->getMessage());
			}
		} 
		if( $syncDemande && $synchronized  ) {
			$demandeRow                   = $syncDemande;
			$sync_demandeid               = $demandeRow->sync_demandeid;
			if( $demandeRow) {
				$defaultData              = $demandeRow->toArray();
				$updatedData              = $updatedDemandeData = array_merge($defaultData, array_intersect_key( $postData, $defaultData));
				if( isset($updatedData["demandeid"]) ) {
					unset($updatedData["demandeid"]);
				}
				if( isset($updatedData["sync_demandeid"]) ) {
					unset($updatedData["sync_demandeid"]);
				}
				if( isset($updatedData["entrepriseid"])) {
					unset($updatedData["entrepriseid"]);
				}
				if( isset($updatedData["demandeurid"])) {
					unset($updatedData["demandeurid"]);
				}
				if( isset($updatedData["promoteurid"])) {
					unset($updatedData["promoteurid"]);
				}
				if( isset($updatedData["creatorid"])) {
					unset($updatedData["creatorid"]);
				}
				if( isset($updatedData["memberid"])) {
					unset($updatedData["memberid"]);
				}
				if( isset($updatedData["creationdate"])) {
					unset($updatedData["creationdate"]);
				}
				if( isset($updatedData["nomcommercial"])) {
					unset($updatedData["nomcommercial"]);
				}
				if( isset($updatedData["denomination"])) {
					unset($updatedData["denomination"]);
				}
				if( isset($updatedData["keywords"])) {
					unset($updatedData["keywords"]);
				}
				if( isset($updatedData["libelle"])) {
					unset($updatedData["libelle"]);
				}
				if( isset($updatedData["numero"])) {
					unset($updatedData["numero"]);
				}
				if( isset($postData["statutid"]) && intval($postData["statutid"])) {
					if( intval($postData["statutid"])==7) {
						 $postData["statutid"]         = 1;
					}
					$updatedData["statutid"]           = intval($postData["statutid"]);
				}
				$updatedData["updatedate"]             = time();				
				try {
					$demandeRow->setFromArray($updatedData);
					if( $demandeRow->save() ) {
						$demandeid                               = $reservationid = $demandeRow->demandeid;
						$entrepriseid                            = $demandeRow->entrepriseid;
						$denomination                            = $demandeRow->denomination;
						$demandeEntreprise                       = $modelEntreprise->findRow( intval($demandeid)   ,"demandeid"    ,null,false);
						if(!$demandeEntreprise) {
							$demandeEntreprise                   = $modelEntreprise->findRow( intval($entrepriseid),"entrepriseid",null,false);
						}
						if(!$demandeEntreprise) {
							$demandeEntreprise                   = $modelEntreprise->findRow( $denomination,"denomination",null,false);
						}
						if( intval($reservationid) && $demandeEntreprise) {
							$reservationRow                      = $modelReservation->findRow(intval($reservationid),"reservationid",null,false);
							if(!$reservationRow ) {
								$entrepriseid                    = $demandeRow->entrepriseid;
								$demandeurid                     = $demandeRow->demandeurid;
								$demandeurRow                    = null;
								if( $demandeurid ) {
									$demandeurRow                = $modelDemandeur->findRow($demandeurid,"demandeurid", null, false);
								}
								$NomCommercial                   = ($demandeEntreprise)?$demandeEntreprise->nomcommercial : $demandeRow->objet;
								$Denomination                    = ($demandeEntreprise)?$demandeEntreprise->denomination  : $demandeRow->objet;
								$Sigle                           = ($demandeEntreprise)?$demandeEntreprise->sigle         : "";
								$reservation_code                = (isset($postData["reservation_code"]          ))? $postData["reservation_code"]           : $demandeRow->numero;
								$reservation_expired             = (isset($postData["reservation_expired"]       ))? $postData["reservation_expired"]        : $demandeRow->expired;
								$reservation_expirationdate      = (isset($postData["reservation_expirationdate"]))? $postData["reservation_expirationdate"] : $demandeRow->periodend;
								$reservation_creationdate        = (isset($postData["reservation_creationdate"]  ))? $postData["reservation_creationdate"]   : $demandeRow->creationdate;
								$reservation_creatorid           = (isset($postData["reservation_creatorid"]     ))? $postData["reservation_creatorid"]      : $demandeRow->creatorid;
								$reservation_updatedate          = (isset($postData["reservation_updatedate"]    ))? $postData["reservation_updatedate"]     : $demandeRow->updatedate;
								$reservation_updateduserid       = (isset($postData["reservation_updateduserid"] ))? $postData["reservation_updateduserid"]  : $demandeRow->updateduserid;
								$reservationData                 =  array("reservationid"=>$demandeid,"entrepriseid"=>$entrepriseid,"code"=>$reservation_code,"denomination"=>$Denomination,"sigle"=>$Sigle,
																	      "expired"=>$reservation_expired ,"expirationdate"=>$reservation_expirationdate,"creationdate"=>$reservation_creationdate,"creatorid"=>$reservation_creatorid,"updatedate"=>$reservation_updatedate,"updateduserid"=>$reservation_updateduserid);
								$reservationData["demandeurid"]  = $demandeurid;
								$reservationData["entrepriseid"] = $entrepriseid;
								$reservationData["denomination"] = $demandeRow->denomination;
								$reservationData["nomcommercial"]= $demandeRow->denomination;
								try {
									$dbAdapter->delete(     $prefixName."reservation_demandes_reservations", array("reservationid=?"=>$demandeid));
									$dbAdapter->delete(     $prefixName."reservation_demandes_reservations", array("reservationid=?"=>$demandeid));
									if( $dbAdapter->insert( $prefixName."reservation_demandes_reservations", $reservationData) ){
										$dbAdapter->update( $prefixName."reservation_demandes_entreprises" , array("reserved"=>1) ,array("entrepriseid=?"=>$entrepriseid));
									}
								} catch(Exception $e) {
									$errorMessages[]       = sprintf("Erreur Technique : %s", $e->getMessage());
								}
							}
						}		
						$documentData                      = array();
						$successMessages[]                 = sprintf("La demande ID %d concernant le nom commercial %s a été mise à jour avec succès", $demandeid, $demandeObjet);
						if( isset($postData["documentid"]) && intval($postData["documentid"])  ) {
							$foundDocument                 = (intval($postData["documentid"]))?$modelDocument->findRow(intval($postData["documentid"]),"documentid",null,false) : null;
							$documentid                    = $insertedDocumentId = (!$foundDocument)? intval($postData["documentid"]) : 0;
							$documentStatutId              = (isset($postData["statutid"]                ))? intval($postData["statutid"])         : $defaultData["statutid"];
							$documentCreatorId             = (isset($postData["document_userid"]         ))? intval($postData["document_userid"])  : 0;						
							$filename                      = (isset($postData["document_filename"]       ))? $postData["document_filename"]        : md5(sprintf("Doc%08d_%08d_%d",$demandeid,$documentid,$documentStatutId));
							$filextension                  = (isset($postData["document_filextension"]   ))? $postData["document_filextension"]    : "pdf";
							$filesize                      = (isset($postData["document_filesize"]       ))? $postData["document_filesize"]        : 0;
							$filedescription               = (isset($postData["document_filedescription"]))? $postData["document_filedescription"] : $demande->libelle;
							$filepath                      = sprintf("%s/demandes/%s.%s", APPLICATION_DATA_PATH,md5(sprintf("Doc%08d_%08d_%d",$demandeid,$documentid,$documentStatutId)), $filextension);
							if( $demandeurRow ) {
								$documentCreatorId         = $demandeurRow->accountid;
							} else {
								$documentCreatorId         = 1;
							}
							if(!file_exists($filepath) && isset($_FILES['uploaded_doc'])) {
								$demandeFileInfos          = $_FILES["uploaded_doc"];
								$fileName                  = $demandeFileInfos['name'];
								$fileTmpName               = $demandeFileInfos['tmp_name'];
								$fileSize                  = $demandeFileInfos['size'];
								$fileError                 = $demandeFileInfos['error'];
								$fileType                  = $demandeFileInfos['type'];
								if( $fileError === 0 ) {
									if (!move_uploaded_file($fileTmpName, $filepath)) {
										$filepath          = "";
									}
								}
							}
							if( file_exists($filepath) ) {
								$documentData                  = $emptyDocumentData = $modelDocument->getEmptyData();
								$documentData                  = array_merge($documentData,array("userid"=>$documentCreatorId,"category"=>15,"filename"=>$filename,"filepath"=>$filepath,"filextension"=>$filextension,"filesize"=>$filesize,"resourceid"=>50,"resource"=>"demandes","filedescription"=>$filedescription,"filemetadata"=>sprintf("%s,%s,%d,demande",$demande->numero,$demande->objet,$demandeid)));
								$documentData["creatoruserid"] = $documentCreatorId;
								$documentData["creationdate"]  = time();
								/*if(!$foundDocument             = $modelDocument->findRow($documentid,"documentid",null,false)) {
									$documentData["documentid"]= $documentid;							
								} else {
									unset($documentData["documentid"]);
								}*/
								unset($documentData["documentid"]);
								$documentData["access"]        = (isset($postData["document_access"]    ))? intval($postData["document_access"])     : 0;
								$documentData["category"]      = (isset($postData["document_category"]  ))? intval($postData["document_category"])   : 15;
								$documentData["resource"]      = (isset($postData["document_resource"]  ))? $postData["document_resource"]           : "demandes";
								$documentData["resourceid"]    = (isset($postData["document_resourceid"]))? intval($postData["document_resourceid"]) : 0;
								$clean_document_data           = array_intersect_key($documentData, $emptyDocumentData);
		 
								$dbAdapter->delete(     $prefixName."system_users_documents"         ,array("filename LIKE ?"=>"%".$filename."%"));
								$dbAdapter->delete(     $prefixName."erccm_vente_commandes_documents",array("keywords LIKE ?"=>"%".$filename."%"));
								$dbAdapter->delete(     $prefixName."reservation_demandes_documents" ,array("contenu  LIKE ?"=>"%".$filename."%"));
								if( $dbAdapter->insert( $prefixName."system_users_documents", $clean_document_data)) {
									$documentid                = ($insertedDocumentId)?$insertedDocumentId : $dbAdapter->lastInsertId();
									$documentData["documentid"]= $documentid;
									$demandeurid               = $demandeRow->demandeurid;
									$documentTitle             = (isset($postData["document_title"]))? $postData["document_title"] : $modelDocument->filterIndex($filename  , $documentCreatorId);
									$demandeDocumentRow        = array("date"=>$documentData["creationdate"],"documentid"=>$documentid,"demandeid"=>$demandeid,"demandeurid"=>$demandeurid,"contenu"=>$filename,"libelle"=>$documentTitle,"document_type"=>"pdf","creationdate"=>time(),"creatorid"=>$documentCreatorId,"updatedate"=>0,"updateduserid"=>0);
									$dbAdapter->delete(     $prefixName."reservation_demandes_documents", array("demandeid=?"=>$demandeid,"documentid=?"=>$documentid));
									if( $dbAdapter->insert( $prefixName."reservation_demandes_documents", $demandeDocumentRow)) {
										/*if( $commandeProduct   = $modelCommandeLigne->findRow(intval($demandeid),"demandeid",null,false)) {
											$articleid         = $commandeProduct->productid;
											$commandeid        = $commandeProduct->commandeid;
											$commandeDocument  = array("documentid"=>$documentid,"articleid"=>$articleid,"keywords"=>sprintf("%s;%s;%s",$documentid,$filename,$demande->objet),"libelle"=>$documentTitle,"description"=>$filename,"creationdate"=>time(),"creatorid"=>$documentCreatorId,"updatedate"=>0,"updateduserid"=>0);
											$dbAdapter->delete( $prefixName."erccm_vente_commandes_documents",array("articleid=?"=>$articleid,"commandeid=?"=>$commandeid,"documentid=?"=>$documentid));
											$dbAdapter->insert( $prefixName."erccm_vente_commandes_documents",$commandeDocument);
										}*/
									} else {
										$dbAdapter->delete(     $prefixName."system_users_documents", array("documentid=?"=>$documentid));
										$errorMessages[]       = sprintf("Le document %s de la demande %d n'a pas pu être enregistré",$filename,$demandeid);
									}
								} else {
										$dbAdapter->delete(   $prefixName."system_users_documents"        , array("documentid=?"=>$documentid));
										$dbAdapter->delete(   $prefixName."reservation_demandes_documents", array("documentid=?"=>$documentid));
										$errorMessages[]      = sprintf("Le document %s n'a pas été enregistré ", $filename);
								}
							}
						}
						if( isset($postData["email_message"]) && !empty($postData["email_message"]) && isset($postData["email_subject"]) && !empty($postData["email_message"])) {
							$demandeurName     = $postData["email_addto_name"];
							$demandeurEmail    = $postData["email_addto_email"];
							$mailer            = new Zend_Mail("UTF-8");
							$mailer->clearFrom();
							$mailer->setFrom(   $postData["email_fromto_email"], $postData["email_fromto_name"]);
							$mailer->setSubject($postData["email_subject"]);
							//Envoi de mails
							$mailer->addTo($demandeurEmail,$demandeurName);
							$mailer->addTo("banaohakeem@siraah.net","FNRCCM");
							$mailer->addTo("banaohamed@gmail.com"  ,"MEBF");
							if( file_exists($filepath) ) {
								$documentContent         = file_get_contents($filepath);
								$attachment              = new Zend_Mime_Part($documentContent);
								$attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
								$attachment->type        = mime_content_type($filepath);
								$attachment->encoding    = Zend_Mime::ENCODING_BASE64;
								$attachment->filename    = $cleanFilename = (isset($filename))?$filename : ((isset($postData["document_filename"]))? $postData["document_filename"] : "Attestation de réservation");
								$mailer->addAttachment($attachment);
							}
							try{
								$mailer->send();
								echo "Envoi email...<br/>";
							} catch(Exception $e) {
								$mailSent          = false;
								$errorMessages[]   = sprintf("Une erreur s'est produite dans l'envoi d'un email à %s à l'adresse %s : %s",$demandeurName, $demandeurEmail ,$e->getMessage() );
							}
						}
					} else {
							$errorCode       = "-5";
							$errorMessages[] = sprintf("La demande ID %d n'a pas été mise à jour.", $demandeid);
					}
				} catch( Exception $e ) {
					        $errorMessages[] = sprintf("Erreur Technique : %s", $e->getMessage());
				}
			}
	    } else {
			        $successMessages[]  = sprintf("La demande ID %d a été mise à jour avec succès sur le serveur distant", $demandeid);
		}
		if( $syncDemande) {
			$demandeurRow               = $modelDemandeur->findRow($syncDemande->demandeurid,"demandeurid",null,false);
			if( $demandeurRow ) {
				$postData               = array_merge($demandeurRow->toArray(),$postData);
			}
		}
		if( count($successMessages) && empty($errorMessages) ) {
			$updatedData                   = array_merge($postData,$updatedData);
			$updatedData["demandeid"]      = $updatedData["remote_demandeid"] = $demandeid;
			$updatedData["sync_demandeid"] = $sync_demandeid;
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array_merge(array("data"=>$updatedData),array("response"=>sprintf("Succès : %s", implode(",",$successMessages)),"status"=>"200")));
			exit;
		} else {
			$updatedData                   = array_merge($postData,$updatedData);
			$updatedData["demandeid"]      = $updatedData["remote_demandeid"] = $demandeid;
			$updatedData["sync_demandeid"] = $sync_demandeid;
			$response->clearAllHeaders();
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
			echo ZendX_JQuery::encodeJson(array_merge(array("data"=>$updatedData),array("errorCode"=>$errorCode,"response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204")));
			exit;
		}
	}
	
	 
}