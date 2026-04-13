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


class Api_RegistresController extends Sirah_Controller_Default
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
	
	public function testAction()
	{
		
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		echo ZendX_JQuery::encodeJson(array("response"=> "L'accès est bon","status"=>"200"));
		exit;
		
	}
	
	
	public function listAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response             = $this->getResponse();
		$model                = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		
		$registres            = $errorMessages = array();
		$paginator            = null;
		$me                   = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"domaineids"=>null,"domaineid"=>0,"creatorid"=>0,"localiteids"=>null,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,"date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"type"=>0,"keywords"=>null,
		                              "searchfrom"=>"erccm","periode_start_day"=>null,"periode_start_month"=>null,"periode_start_year"=>null,"periode_end_day"=>null,"periode_end_month"=>null,"periode_end_year"=>null);		
 
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		$domaineids           = $localiteids = array();
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray              = preg_split("/[\s]+/", $filters["name"]);
			if(count($nameToArray) > 2) {
				$filters["nom"]       = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["prenom"]    = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["nom"]       = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["prenom"]    = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]      = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}        
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate                  = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]           = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_day"]))
				&&
			(isset($filters["periode_end_day"])  && intval($filters["periode_end_day"] ))   && (isset($filters["periode_start_day"])  && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}	
        if( isset($params["domaineids"]) && !empty( $params["domaineids"] ) ) {
			$domaineids               = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids           = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]      = $domaineids;
		}		
		if( isset( $params["localiteids"]) && !empty( $params["localiteids"] ) ) {
			$localiteids                = (array)$params["localiteids"];
			if( is_string( $localiteids) ) {
				$localiteids            = array( $params["localiteids"]);
			}
			foreach( $localiteids as $dKey => $dVal ) {
				     $localiteids[$dKey]= $stringFilter->filter($dVal );
			}
			$filters["localiteids"]     = $localiteids;
		}
		$registres                      = $model->getList($filters,$pageNum,$pageSize);
		$paginator                      = $model->getListPaginator($filters);		
        $total                          = 0;		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
			$total                      = $paginator->getTotalItemCount();
		}
		if( count($registres) && empty($errorMessages) ) {
			$responseData               = array("data"=>$registres,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData              = array("data"=>$registres,"paginator"=>$paginator,"total"=>$total,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData              = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>$pageSize,"numpage"=>$pageNum);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}		 		
	}
	
	
	public function lastAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                    = Sirah_Fabric::getUser();
		$model                 = $modelRegistre = $this->getModel("registre");
 
		$successMessages       = $errorMessages = array();
		
		$params                = $this->_request->getParams();
		$annee                 = (isset($params["annee"]        ))? intval($params["annee"])         : 0;
		$beforeRegistreId      = (isset($params["maxregistreid"]))? intval($params["maxregistreid"]) : 0;
		$registre              = $model->last($annee,$beforeRegistreId);
		 
		if( count($registre) && empty($errorMessages) ) {
			$responseData      = array("data"=>$registre,"total"=>1);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"200"));
			exit;
		} elseif(count($errorMessages)){
			$responseData            = array("data"=>array(),"total"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0);
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}		
	}
	
	 
	
	public function verifyncAction()
	{		
		$model               = $this->getModel("demande");
		$modelRegistre       = $this->getModel("registre");
		$modelEntreprise     = $this->getModel("demandentreprise");
		$modelBlacklist      = $this->getModel("demandeblacklist");
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter        =   new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$postData            = $this->_request->getPost();
		$requestData         = $this->_request->getParams();
		
		$query               = (isset($requestData["search"]) && !empty($requestData["search"]))?$modelRegistre->cleanName(strip_tags(base64_decode($requestData["search"]))) : "";
		$available           = 1;
		$errorMessages       = array();
		$keywords            = (isset($postData["keywords"])  && !empty($postData["keywords"] ))?$modelRegistre->cleanName(strip_tags($postData["keywords"])) : $query;
		$query               = $keywords = substr($keywords,0,350);
		if( empty($keywords) ) {
			$errorMessages[] = "Veuillez saisir des mots clés du nom commercial";
		}
		if( empty($errorMessages) ) {
			$registres       = $modelRegistre->getList(array("searchQ"=>$keywords,"types"=>array(1,2,3,4)), 1,5);
			if( count($registres) ) {
				$i           = 0;
				foreach( $registres as $registre ) {
					     $foundRegistreLib         = $modelRegistre->cleanName($registre["libelle"],$registre["numero"]);
						 $similarites[$i]          = sprintf("%s : %s",$registre["numero"],$registre["libelle"] );
						 $similariteActivities[$i] = $registre["description"];
						 $registres[$i]            = $registre;
						 if((false!==stripos($keywords,$foundRegistreLib)) || ($foundRegistreLib==$keywords) || ($registre["libelle"]==$keywords)) {
							 $available            = 0;
							 $errorMessages[]      = sprintf("Le nom commercial %s ne semble pas disponible. Une entreprise %s a ete trouve avec le numero RCCM: %s", $query, $foundRegistreLib,$registre["numero"]);
							 break;
						 }							
						 $informationMessages[0]   = "Quelques noms commerciaux similaires ont été trouvés. Nous vous recommandons de vous rendre dans une juridiction proche pour approfondir la vérification.";
						 
						 $i++;
				}
			} else {
				$reservedEntreprises  = $modelEntreprise->getList(array("libelle"=>$keywords,"reserved"=>1), 1,25);
				if( count(   $reservedEntreprises) ) {
					foreach( $reservedEntreprises as $reservedEntreprise ) {
						     $foundRegistreLib     = $modelRegistre->cleanName($reservedEntreprise["nomcommercial"]);
							 $foundRegistreSigle   = (!empty($reservedEntreprise["sigle"]))?$modelRegistre->cleanName($reservedEntreprise["sigle"]) : "";
							 if( $foundRegistreLib== $query ) {
								 $available        = 0;
								 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Un nom similaire reservé a été trouvé : %s.",$query, $foundRegistreLib);
								 break;
							 } elseif(!empty($foundRegistreSigle) && (false!==stripos($foundRegistreSigle,$keywords))) {
								 $available        = 0;
								 $errorMessages[]  = sprintf("Le nom commercial %s semble déjà reservé. Le sigle %s apparait dans le nom commercial à réserver.",$query,$foundRegistreSigle);
								 break;
							 }
					}
				} else {
					$blacklisted = $modelBlacklist->getList(array("searchQ"=>$query),1,5);
					if( count(   $blacklisted)) {
						$i       = 0;
						foreach( $blacklisted as $item ) {
							     $foundBlackListLibelle  = $modelRegistre->cleanName($item["libelle"]);
								 if( false!==stripos($keywords,$foundBlackListLibelle)) {
									 $available          = 0;
									 $errorMessages[]    = sprintf("Le nom commercial %s n'est pas autorisé. Vous pouvez approfondir la vérification en vous rendant dans la juridiction la plus proche.",$query);
									 break;
								 }
						}
					}
				}
			}
		}			 
		$response  = $this->getResponse();
		if( count($errorMessages) ) {
			$response->setHttpResponseCode(204);
			echo ZendX_JQuery::encodeJson(array("response"=>"Nom Commercial Indisponible. Raisons : ".implode("; ", $errorMessages),"status"=>"204"));
			exit;
		} elseif(empty($errorMessages) && $available) {
            $response->setHttpResponseCode(200);
			echo ZendX_JQuery::encodeJson(array("response"=>"Nom commercial disponible","status"=>"200"));
			exit;
		} else {
			$response->setHttpResponseCode(204);	
			echo ZendX_JQuery::encodeJson(array("response"=>" Nom Commercial Indisponible.","status"=>"204"));
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
		$model                 = $modelRegistre = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelSurete           = $this->getModel("registresurete");
		$modelMoral            = $this->getModel("registremorale");
		$modelModification     = $this->getModel("registremodification");
		$modelEntreprise       = $this->getModel("entreprise");
		$modelRepresentant     = $this->getModel("representant");
		$modelDomaine          = $this->getModel("domaine");
		$modelLocalite         = $this->getModel("localite");
 
		$successMessages       = $errorMessages = array();
		$localites             = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle"), array(),0, null , false);
		$domaines              = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" ,"libelle"), array() , 0 , null , false); 
		 
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$prefixName            = $modelTable->info("namePrefix");
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$postData              = $this->_request->getPost();
        $registreDefaultData   = $model->getEmptyData();	
		$insertData            = array_merge($registreDefaultData, array_intersect_key($postData,$registreDefaultData));
		
		if(!isset($insertData["registreid"]) || !intval($insertData["registreid"])) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du RCCM : %d",$insertData["registreid"]),"status"=>"204"));
			exit;
		}
		$registreid                    = intval($insertData["registreid"]);
		$registreRow                   = $model->findRow(intval($registreid),"registreid",null,false);
		$dirigeants                    = $model->dirigeants(intval($registreid));
		if( $registreRow && count($dirigeants)) {
			$errorMessages[]           = sprintf("Duplicata : Un RCCM existe déjà avec l'ID %s" , $registreid);
		} elseif( $registreRow && ($registreRow->numero==$insertData["numero"]) && !count($dirigeants)) {
			$registreRow->delete();
		}			
		if(!isset($insertData["numero"]) || empty($insertData["numero"])) {
			$errorMessages[]           = sprintf("Duplicata : Numéro RCCM invalide pour l'ID %s", $registreid);
		} else {
			$registreRow               = $model->findRow(strip_tags($insertData["numero"]),"numero",null,false); 
			if( $registreRow) {
				$errorMessages[]       = sprintf("Duplicata : Un RCCM existe déjà avec le numéro %s" , strip_tags($insertData["numero"]));
			}
		}
		
		if(!isset($insertData["libelle"]) || empty($insertData["libelle"])) {
			$errorMessages[]           = sprintf("Nom commercial invalide pour l'ID %s", $registreid);
		} else {
			$registreRow               = $model->findRow(strip_tags($insertData["libelle"]),"libelle",null,false); 
			if( $registreRow) {
				$errorMessages[]       = sprintf("Duplicata : Un RCCM existe déjà avec le nom commercial %s" , strip_tags($insertData["libelle"]));
			}
		}
		if(!isset($insertData["localiteid"]) || !intval($insertData["localiteid"]) || !isset($localites[$insertData["localiteid"]])) {
			$errorMessages[]           = "Veuillez sélectionner une localité valide";
		}
		if(!isset($insertData["domaineid"])  || !intval($insertData["domaineid"])  || !isset($domaines[$insertData["domaineid"]])) {
			$errorMessages[]           = "Veuillez sélectionner un secteur d'activités valide";
		}
		if( empty($errorMessages) ) {
			try {
				$dbAdapter->delete(     $prefixName."rccm_registre", array("registreid=?"=>$registreid));
				if( $dbAdapter->insert( $prefixName."rccm_registre", $insertData)) {
					if( isset($postData["formid"]) && intval($postData["formid"])) {
						$entrepriseDefaultData                  = $modelEntreprise->getEmptyData();
						$entreprise_data                        = array_merge($entrepriseDefaultData, array_intersect_key($postData,$entrepriseDefaultData ));				
						$entreprise_data["registreid"]          = $registreid;
						$entreprise_data["formid"]              = $insertData["formid"];
						$entreprise_data["num_securite_social"] = $insertData["numcnss"];
						$entreprise_data["num_ifu"]             = $insertData["numifu"];
						$entreprise_data["num_rc"]              = $insertData["numero"];					  
						$entreprise_data["libelle"]             = $stringFilter->filter($insertData["libelle"]);
						$entreprise_data["address"]             = (isset($postData["address"] ))? $stringFilter->filter($postData["address"])  : "";
						$entreprise_data["email"]               = (isset($postData["email"]   ))? $stringFilter->filter($postData["email"])	  : "";
						$entreprise_data["phone1"]              = (isset($postData["phone1"]  ))? $stringFilter->filter($postData["phone1"])	  : "";
						$entreprise_data["phone2"]              = (isset($postData["phone2"]  ))? $stringFilter->filter($postData["phone2"])   : "";
						$entreprise_data["siteweb"]             = (isset($postData["siteweb"] ))? $stringFilter->filter($postData["siteweb"])  : "";
						$entreprise_data["country"]             = "";
						$entreprise_data["zip"]                 = "";
						$entreprise_data["city"]                = 0;
						$entreprise_data["responsable"]         = (isset($postData["responsable"]      ))? $stringFilter->filter($postData["responsable"])                       : "";
						$entreprise_data["responsableid"]       = (isset($postData["responsableid"]    ))? $stringFilter->filter($postData["responsableid"])                     : 0;
						$entreprise_data["responsable_email"]   = (isset($postData["responsable_email"]))? $stringFilter->filter($postData["responsable_email"])                 : "";
						$entreprise_data["capital"]             = (isset($postData["capital"]          ))? floatval(preg_replace('/[^0-9\.,]/','',$postData["capital"] ))        : 0;
						$entreprise_data["chiffre_affaire"]     = (isset($postData["chiffre_affaire"]  ))? floatval(preg_replace('/[^0-9\.,]/','',$postData["chiffre_affaire"])) : 0;
						$entreprise_data["nbemployes_min"]      = (isset($postData["nbemployes_min"]   ))? intval(  preg_replace('/[^0-9\.,]/','',$postData["nbemployes_min"]))  : 0;
						$entreprise_data["nbemployes_max"]      = (isset($postData["nbemployes_max"]   ))? intval(  preg_replace('/[^0-9\.,]/','',$postData["nbemployes_max"]))  : 0;
						$entreprise_data["datecreation"]        = $insertData["date"];
						$entreprise_data["presentation"]        = (isset($postData["presentation"]     ))? $stringFilter->filter( $postData["presentation"])                     : "";
						$entreprise_data["fax"]                 = (isset($postData["fax"]              ))? $stringFilter->filter( $postData["fax"])                              : "";
						$entreprise_data["region"]              = 0;
						$entreprise_data["groupid"]             = 1;
						$entreprise_data["formid"]              = (isset($postData["formid"]           ))? intval( $postData["formid"] )   : $insertData["formid"];
						$entreprise_data["domaineid"]           = (isset($postData["domaineid"]        ))? intval( $postData["domaineid"]) : 0;
						$entreprise_data["reference"]           = $insertData["numero"];
						$entreprise_data["pagekey"]             = (isset( $postData["pagekey"]         ))? $stringFilter->filter($postData["pagekey"]) : $insertData["numero"];
						$entreprise_data["creatorid"]           = $insertData["creatorid"];
						$entreprise_data["creationdate"]        = $insertData["creationdate"];
						$entreprise_data["updateduserid"]       = 0;
						$entreprise_data["updatedate"]          = 0;					
						$dbAdapter->insert( $prefixName."rccm_registre_entreprises", $entreprise_data );
					}
					if( isset($postData["estate"]) || isset($postData["titre"])) {
						$surete_data                            = array();
						$surete_data["registreid"]              = $registreid;	
						$surete_data["periodstart"]		        = (isset($postData["periodstart"]        ))? intval($postData["periodstart"])                           : 0;
						$surete_data["periodend"]		        = (isset($postData["periodend"]          ))? intval($postData["periodend"])                             : 0;
						$surete_data["valeur"]                  = (isset($postData["valeur"]             ))? floatval(preg_replace('/[^0-9]/','', $postData["valeur"])) : 0;
						$surete_data["estate"]                  = (isset($postData["estate"]             ))? $stringFilter->filter($postData["estate"])                 : $registre_data["description"];
						$surete_data["titre"]                   = (isset($postData["titre"]              ))? $stringFilter->filter($postData["titre"] )                 : $libelle;
						$surete_data["nom_constituant"]         = (isset($postData["nom_constituant"]    ))? $stringFilter->filter($postData["nom_constituant"] )       : "";
						$surete_data["numrccm_constituant"]     = (isset($postData["numrccm_constituant"]))? $stringFilter->filter($postData["numrccm_constituant"])    : "";
						$surete_data["type"]                    = (isset($postData["typeSurete"]         ))? intval( $postData["typeSurete"]  )                         : 0;
						$surete_data["creationdate"]            = $insertData["creationdate"];	
						$surete_data["updateduserid"]           = 0;
						$surete_data["updatedate"]              = 0;
						$surete_data["creatorid"]               = $insertData["creatorid"];
						$dbAdapter->insert( $prefixName."rccm_registre_suretes", $surete_data);
					}
					if( isset($postData["article_actuel"])) {
						$modification_data                      = array();
						$modification_data["registreid"]        = $registreid;
						$modification_data["article_actuel"]    = (isset($postData["article_actuel"]) && !empty($postData["article_actuel"])) ? $stringFilter->filter( $postData["article_actuel"]) : $registre_data["description"];
						$modification_data["article_suppr"]     = (isset($postData["article_suppr"] ) && !empty($postData["article_suppr"] )) ? $stringFilter->filter( $postData["article_suppr"])  : "";
						$modification_data["article_ajout"]     = (isset($postData["article_ajout"] ) && !empty($postData["article_ajout"] )) ? $stringFilter->filter( $postData["article_ajout"])  : "";
						$modification_data["type"]              = (isset($postData["typeModification"])) ? intval( $postData["typeModification"]  ) : 7;
						$modification_data["creationdate"]      = $insertData["creationdate"];		
						$modification_data["creatorid"]         = $insertData["creatorid"];
						$modification_data["updateduserid"]     = 0;
						$modification_data["updatedate"]        = 0;
						$emptyModificationData                  = $modelModification->getEmptyData();
						$insert_modification                    = array_intersect_key( $modification_data, $emptyModificationData );
						$dbAdapter->insert( $prefixName."rccm_registre_modifications", $insert_modification );
					}
					
					
										
					$successMessages[]                      = sprintf("Les données du RCCM n° %s ont été enregistrées avec succès : %d", $insertData["numero"], $registreid);
				} else {
					$errorMessages[]                        = sprintf("Les données du RCCM n° %s n'ont pas pu être enregistrées   : %d", $insertData["numero"], $registreid);
				}
			} catch(Exception $e) {
				$errorMessages[]       = sprintf("Une erreur technique s'est produite : %s ", $e->getMessage());
			}			    
		}
		if( count($successMessages) && empty($errorMessages) ) {
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}		
	}
	
	public function addirigeantAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
 		
		$response                  = $this->getResponse();
		$me                        = Sirah_Fabric::getUser();
		$model                     = $modelRegistre = $this->getModel("registre");
		$modelPhysique             = $this->getModel("registrephysique");
		$modelRepresentant         = $this->getModel("representant");
		$modelDomaine              = $this->getModel("domaine");
		$modelLocalite             = $this->getModel("localite");
 
		$successMessages           = $errorMessages = array();
		$localites                 = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle"), array(),0, null , false);
		$domaines                  = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" ,"libelle"), array() , 0 , null , false); 
		 
		$modelTable                = $model->getTable();
		$dbAdapter                 = $modelTable->getAdapter();
		$prefixName                = $modelTable->info("namePrefix");
		$strNotEmptyValidator      = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		$stringFilter              = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());
		
		$postData                           = $this->_request->getPost();
        $registreDefaultData                = $model->getEmptyData();	
		$representantDefaultData            = $modelRepresentant->getEmptyData();
		$registreData                       = array_merge($registreDefaultData    , array_intersect_key($postData,$registreDefaultData));
		$representant_data                  = array_merge($representantDefaultData, array_intersect_key($postData,$representantDefaultData));
		$representant_data["cnib"]          = (isset($postData["cnib"]         ))?$postData["cnib"]          : (isset($postData["passport"])?$postData["passport"] : "");
		$representant_data["city"]          = (isset($postData["city"]         ))?$postData["city"]          : "";
		$representant_data["creationdate"]  = (isset($postData["creationdate"] ))?$postData["creationdate"]  : time();
		$representant_data["creatorid"]     = (isset($postData["creatorid"]    ))?$postData["creatorid"]     : 26;
		$representant_data["updateduserid"] = (isset($postData["updateduserid"]))?$postData["updateduserid"] : 0;
		$representant_data["updatedate"]    = (isset($postData["updatedate"]   ))?$postData["updatedate"]    : 0;
		if(!isset($registreData["registreid"]) || !intval($registreData["registreid"])) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du RCCM : %d",$registreData["registreid"]),"status"=>"204"));
			exit;
		}
		if(!isset($representant_data["representantid"]) || !intval($representant_data["representantid"])) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du représentant : %d",$representant_data["representantid"]),"status"=>"204"));
			exit;
		}
		$registreid                       = intval($registreData["registreid"]);
		$representantid                   = intval($representant_data["representantid"]);
		$registreRow                      = $model->findRow(intval($registreid),"registreid",null,false);
		if(!$registreRow) {
			$errorMessages[]              = sprintf("Le RCCM ID %s n'existe pas" , $registreid);
		} 
		if(!$strNotEmptyValidator->isValid($representant_data["nom"]) || !$strNotEmptyValidator->isValid($representant_data["prenom"])) {
			$errorMessages[]              = sprintf(" Veuillez entrer un nom de famille et/ou prénom valide pour le representant du RCCM ID : %s", $registreid);
		}
		if( empty($errorMessages)) {
			$dbAdapter->delete(         $prefixName."rccm_registre_representants", array("representantid=?"=>$representantid));
			$dbAdapter->delete(         $prefixName."rccm_registre_dirigeants"   , array("representantid=?"=>$representantid));
			try {
				$entrepriseid             = (isset($postData["entrepriseid"]))?intval($postData["entrepriseid"]) : 0;
				$representantRegistre     = array("registreid"=>$registreid,"representantid"=>$representantid,"fonction"=>"GERANT","entrepriseid"=>$entrepriseid);
					
				if( $dbAdapter->insert( $prefixName."rccm_registre_representants", $representant_data) &&
				    $dbAdapter->insert( $prefixName."rccm_registre_dirigeants"   , $representantRegistre)) {
					
					$successMessages[]    = sprintf("Les données du représentant du RCCM n° %s ont été enregistrées avec succès: %d", $insertData["numero"], $registreid);
				} else {
					$errorMessages[]      = sprintf("Les données du représentant du RCCM n° %s n'ont pas pu être enregistrées  : %d", $insertData["numero"], $registreid);
				}
			}catch(Exception $e ) {
				$errorMessages[]          = sprintf("Une erreur technique s'est produite : %s ", $e->getMessage());
			}			
		}
		if( count($successMessages) && empty($errorMessages) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}
	}
	
	public function addocumentAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response                = $this->getResponse();
		
		$me                      = Sirah_Fabric::getUser();
		$model                   = $modelRegistre = $this->getModel("registre");
		$modelPhysique           = $this->getModel("registrephysique");
		$modelDocument           = $this->getModel("document");
		
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$strNotEmptyValidator    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		$stringFilter            = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());
		
		$postData                = $this->_request->getPost();
		$registreDefaultData     = $model->getEmptyData();	
		$documentDefaultData     = $modelDocument->getEmptyData();
		$registreData            = array_merge($registreDefaultData, array_intersect_key($postData,$registreDefaultData));
		$documentData            = array_merge($documentDefaultData, array_intersect_key($postData,$documentDefaultData));
		if(!isset($registreData["registreid"]) || !intval($registreData["registreid"])) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du RCCM : %d",$registreData["registreid"]),"status"=>"204"));
			exit;
		}
		if(!isset($postData["documentid"]) || !intval($postData["documentid"])) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du document du RCCM %d",$registreData["registreid"]),"status"=>"204"));
			exit;
		}
		$registreid                    = intval($registreData["registreid"]);
		$documentid                    = intval($documentData["documentid"]);
		$access                        = (isset($postData["access"]))?intval($postData["access"]) : 0;
		$registreRow                   = $model->findRow(intval($registreid),"registreid",null,false);
		if(!$registreRow) {
			$errorMessages[]           = sprintf("Le RCCM ID %s n'existe pas" , $registreid);
		}
		$NumeroRCCM                    = ($registreRow)?$registreRow->numero : "";
		if(!$strNotEmptyValidator->isValid($documentData["filename"]) || !$strNotEmptyValidator->isValid($documentData["filepath"] )) {
			$errorMessages[]           = sprintf(" Veuillez entrer un nom valide pour le document du N° RCCM %s  : #ID%s", $NumeroRCCM, $registreid);
		}
		if( empty($errorMessages)) {
			$documentFilename          = $documentData["filename"];
			try {
				$dbAdapter->delete(    $prefixName."system_users_documents" , array("filename LIKE ?"=>"%".$documentFilename."%"));
				$dbAdapter->delete(    $prefixName."system_users_documents" , array("documentid=?"=>$documentid));
				$dbAdapter->delete(    $prefixName."rccm_registre_documents", array("documentid=?"=>$documentid));
				if( $dbAdapter->insert($prefixName."system_users_documents" , $documentData) ) {
					$dbAdapter->insert($prefixName."rccm_registre_documents", array("registreid"=>$registreid,"documentid"=>$documentid,"access"=>$access)); 				
					$successMessages[] = sprintf("Les données du document #ID%s du RCCM n° %s ont été enregistrées avec succès : %d",$documentid,$NumeroRCCM,$registreid);
				} else {
					$errorMessages[]   = sprintf("Les données du document #ID%s du RCCM n° %s n'ont pas pu être enregistrées   : %d",$documentid,$NumeroRCCM,$registreid);
				}
			} catch(Exception $e ) {
				$errorMessages[]       = sprintf("Une erreur technique s'est produite : %s ", $e->getMessage());
			}
		}
		if( count($successMessages) && empty($errorMessages) ) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}
	}	 
	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response              = $this->getResponse();
		$response->setHeader("Content-type","application/json",true);
		
		$me                    = Sirah_Fabric::getUser();
		$model                 = $modelRegistre = $this->getModel("registre");
 
		$successMessages       = $errorMessages = array();
		 
		$modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$prefixName            = $modelTable->info("namePrefix");
		
		$postData              = $this->_request->getPost();
		$registreid            = (isset($postData["registreid"]))?intval($postData["registreid"]) : 0;
		
		if(!intval($registreid)) {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);			
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Veuillez renseigner l'ID du RCCM : %d",$registreid),"status"=>"204"));
			exit;
		}
		try {
			$registreRow               = $model->findRow(intval($registreid),"registreid",null,false);
			if( $registreRow) {			
				if(!$registreRow->delete() && $dbAdapter->delete($prefixName."rccm_registre_dirigeants",array("registreid=?"=>$registreid))) {
					$errorMessages[]   = " Erreur de la base de donnée : Le registre id#$registreid n'a pas été supprimé";
				} else {
					$successMessages[] = sprintf("Le registre ID %s a été supprimé", $registreid);
					$dbAdapter->delete($prefixName."rccm_registre_dirigeants"      , array("registreid=?"=>$registreid));						
					$dbAdapter->delete($prefixName."rccm_registre_representants"   , "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique  WHERE registreid='".$registreid."')");						
					$dbAdapter->delete($prefixName."system_users_documents"        , "documentid     IN (SELECT documentid     FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
					$dbAdapter->delete($prefixName."rccm_registre_documents"       , array("registreid=?"=>$registreid));
				}
			}
		} catch(Exception $e ) {
			$errorMessages[]           = sprintf("Erreur Technique signalée pour le registreid %s", $registreid);
		}
		if( count($successMessages) && empty($errorMessages) ) {
			$response->setHeader("Content-type","application/json",true);
			$response->setHttpResponseCode(200);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Succès : %s", implode(",",$successMessages) ),"status"=>"200"));
			exit;
		} else {
			$response->clearAllHeaders();
			$response->setHttpResponseCode(200);	
			$response->sendHeaders();
            $response->setHeader("Content-type","application/json",true);	
			echo ZendX_JQuery::encodeJson(array("response"=>sprintf("Erreur : %s", implode(",",$errorMessages) ),"status"=>"204"));
			exit;
		}		
	}
}