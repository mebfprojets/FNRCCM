<?php
require 'E:\webserver/www/Xpdf/vendor/autoload.php';
require 'E:\webserver/www/erccm/libraries/Forceutf8/vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use XPDF\PdfToText;
use \ForceUTF8\Encoding;

class Admin_AjaxresController extends Sirah_Controller_Default
{
	public function test2Action()
	{
	    $this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
	    $API_END_POINT      = API_SIGUE_URI;	
		$API_AUTH_USER      = API_SIGUE_AUTH_USER;
		$API_AUTH_PWD       = API_SIGUE_AUTH_PWD;
		$API_AUTH_SID       = base64_encode(sprintf("%s:%s", $API_AUTH_USER, $API_AUTH_PWD));				
		try {
			$authClient     = new  Zend_Http_Client($API_END_POINT."/auth/token", array('keepalive' => true));
			$authClient->setMethod(Zend_Http_Client::GET);
			$authClient->setHeaders(array("Authorization"=>"Basic ".$API_AUTH_SID,"Accept" => "application/json"));
							
			$authResponse   = $authClient->request();
			$API_AUTH_TOKEN = $authResponse->getBody();
			$authCookies    = $authResponse->getHeader("Set-Cookie");
			if( isset($authCookies[0])) {
				$API_AUTH_COOKIE = $authCookies[0];
			}
			if(!empty( $API_AUTH_TOKEN) ) {
				$appConfigSession->resources["sigue.api.auth.token"] = $API_AUTH_TOKEN;
				$headers["Authorization"]                            = "Bearer ".$API_AUTH_TOKEN;
			}
		} catch ( Exception $e ) {
			$errorMessages[]= sprintf("Erreur d'authentification : %s", $e->getMessage());					
		}
		var_dump($errorMessages); die();
	}
	
	public function testAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$totalResults  = 10;
		$query         = "BURKINDI";
		$errorMessages = $similarites = array();
		try {
			$siguSearchClient         = new  Zend_Http_Client(VIEW_BASE_URI."/admin/ajaxres/apisearch", array('keepalive' => true));
			$siguSearchClient->setMethod(  Zend_Http_Client::GET);
			$siguSearchClient->setParameterGet( "repository", "sigu");
			$siguSearchClient->setParameterGet( "searchq"   , $query);
			$siguSearchClient->setParameterGet( "limit"     , $totalResults);
			$siguSearchClient->setCookieJar();
			$siguSearchClient->setHeaders(array("Cookie"=>sprintf("%s=%s",session_name(),session_id()),"Accept" => "application/json","Content-type"=>"application/json"));
			
			$siguSearchResponse     = $siguSearchClient->request();
			if( $siguSearchResponse ) {
				$registres          = json_decode($siguSearchResponse->getBody(), true);
				if( count(   $registres )) {
					foreach( $registres as $NumeroRCCM => $registre ) {
							 $similarites[] = sprintf("%s : %s", $registre["label"], $registre["value"]);
							 if( $registre["value"] == $query ) {
								 $rejected  = 1;
							 }									 
					}
				}
			}
		} catch( Exception $e ) {
			$errorMessages[]        = sprintf("Une erreur s'est produite dans la communication avec l'API SIGU : %s", $e->getMessage());
		}
		ob_end_clean();
		print_r($similarites); die();
	}
	
	public function apisearchAction()
	{
 
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		
		$model                = $modelRegistre = $this->getModel("registre");
		$appConfigSession     = new Zend_Session_Namespace("AppConfig");
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$searchQ              = $stringFilter->filter($this->_getParam("searchq"   , $this->_getParam("q"         , null )));
		$repository           = $stringFilter->filter($this->_getParam("searchfrom", $this->_getParam("repository", "sigu")));
		$rows                 = array();
		$headers              = array("Accept"=>'application/json');
		
		$errorMessages        = array();
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"searchQ"=>$searchQ,"keywords"=>$searchQ, "name"=>null,"nom_raison_sociale"=>null,"searchq"=>null);		
        $searchParams         = array_intersect_key($params, $filters);
		if(!empty( $searchParams)) {			
			foreach( $searchParams as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( isset($filters["name"] )) {
			$nameToArray           = preg_split("/[\s]+/", $filters["name"]);
			if(count($nameToArray) > 2) {
				$filters["nom"]    = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["prenom"] = implode(" ", $nameToArray );
				unset($filters["name"]);
			} elseif( count($nameToArray)==2)	 {
				$filters["nom"]    = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["prenom"] = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
				unset($filters["name"]);
			} elseif( count($nameToArray)==1)	 {
				$filters["name"]   = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}     
        if( count(   $filters ))	{
			foreach( $filters as $filterKey=> $filterValue ) {
				if( empty($filters[$filterKey]) || null== $filters[$filterKey]) {
					unset($filters[$filterKey]);
				}
			}
		}	
 
		$API_END_POINT         = API_SIGUE_URI;	
		$API_AUTH_USER         = API_SIGUE_AUTH_USER;
		$API_AUTH_PWD          = API_SIGUE_AUTH_PWD;
		$API_AUTH_TOKEN        = "";
		$API_AUTH_COOKIE       = "";
		$API_URI               = $API_END_POINT."/";
		$API_SEND_METHOD       = "GET";
		$API_RAISON_SOCIALE    = "raison_sociale";
		$API_LIBELLE_KEY       = "nom_commercial";
		$API_ACTIVITE_KEY      = "lib_activite";
		$API_ADDRESS_KEY       = "adresse_rccm";
		$API_NUMERO_KEY        = "num_rccm";
		$API_REGISTREID_KEY    = "registreid";
		$SEARCHQ_KEY           = "keywords";
		$apiResponse           = null;
		$allowedFilters        = array();
			 
		switch( strtolower($repository) ) {
			case "sigue":
			case "sigu":
			default:	
			    $SEARCHQ_KEY            = "keywords";
                $allowedFilters         = array("num_rccm"=>"","num_ifu"=>"","num_cnss"=>"","nom_commercial"=>"","raison_sociale"=>"","tel_rccm"=>"","nom_prenom_prom"=>"","code_activite"=>"","lib_activite"=>"","keywords"=>"","sigle"=>"");			
				if( defined("API_SIGUE_AUTH_TOKEN") && !empty(API_SIGUE_AUTH_TOKEN)) {					
					$API_AUTH_TOKEN     = $appConfigSession->resources["sigue.api.auth.token"] = API_SIGUE_AUTH_TOKEN;
					$API_AUTH_COOKIE    = ( defined("API_SIGUE_AUTH_COOKIE"))? API_SIGUE_AUTH_COOKIE : "";
				} elseif( isset( $appConfigSession->resources["sigue.api.auth.token"])) { 
				    $API_AUTH_TOKEN     = $appConfigSession->resources["sigue.api.auth.token"];
					$API_AUTH_COOKIE    = ( isset( $appConfigSession->resources["sigue.api.auth.cookie"] ))?$appConfigSession->resources["sigue.api.auth.cookie"] : "";
		        } else {
					$API_END_POINT      = API_SIGUE_URI;	
					$API_AUTH_USER      = API_SIGUE_AUTH_USER;
					$API_AUTH_PWD       = API_SIGUE_AUTH_PWD;
					$API_AUTH_SID       = base64_encode(sprintf("%s:%s", $API_AUTH_USER, $API_AUTH_PWD));				
					try {
						$authClient     = new  Zend_Http_Client($API_END_POINT."/auth/token", array('keepalive' => true));
						$authClient->setMethod(Zend_Http_Client::GET);
						$authClient->setHeaders(array("Authorization"=>"Basic ".$API_AUTH_SID,"Accept" => "application/json"));
										
						$authResponse   = $authClient->request();
						$API_AUTH_TOKEN = $authResponse->getBody();
						$authCookies    = $authResponse->getHeader("Set-Cookie");
						if( isset($authCookies[0])) {
							$API_AUTH_COOKIE = $authCookies[0];
						}
						if(!empty( $API_AUTH_TOKEN) ) {
							$appConfigSession->resources["sigue.api.auth.token"] = $API_AUTH_TOKEN;
							$headers["Authorization"]                            = "Bearer ".$API_AUTH_TOKEN;
						}
					} catch ( Exception $e ) {
						$errorMessages[]= sprintf("Erreur d'authentification : %s", $e->getMessage());					
					}
				}	
				$headers["Cookie"]        = $API_AUTH_COOKIE;
				$headers["Authorization"] = "Bearer ".$API_AUTH_TOKEN;
                $API_URI                  = $API_END_POINT."/registres/list";
                $API_SEND_METHOD          = Zend_Http_Client::GET;
                $API_REGISTREID_KEY       = "registreid";	
                $API_RAISON_SOCIALE       = "raison_sociale";
				$API_LIBELLE_KEY          = "nom_commercial";
				$API_ACTIVITE_KEY         = "lib_activite";
				$API_ADDRESS_KEY          = "adresse_rccm";
				$API_NUMERO_KEY           = "num_rccm";				
				break;
		    case "apiods":
			case "dgi":
			case "cnss":
			case "impots":
			    $SEARCHQ_KEY              = "nom_raison_sociale";
			    $allowedFilters           = array("cnss_dgi"=>"","num_ifu"=>"","statut_ifu"=>"","regime"=>"","nom_raison_sociale"=>"","forme_juridique"=>"","personnalite"=>"","email"=>"");
			    $API_LIBELLE_KEY          = "NOM_RAISON_SOCIALE";
				$API_NUMERO_KEY           = "NUM_IFU";
				$API_REGISTREID_KEY       = "NUM_IFU";	
                $API_RAISON_SOCIALE       = "NOM_RAISON_SOCIALE";
				$API_LIBELLE_KEY          = "NOM_RAISON_SOCIALE";
				$API_ACTIVITE_KEY         = "ACTIVITE";
				$API_ADDRESS_KEY          = "ADRESSE_GEOGRAPHIQUE";
				$API_END_POINT            = API_ODS_URI;	
				$API_AUTH_USER            = API_ODS_AUTH_USER;
				$API_AUTH_PWD             = API_ODS_AUTH_PWD;

			    if( defined("API_ODS_AUTH_TOKEN") && !empty(API_ODS_AUTH_TOKEN)) {					
					$API_AUTH_TOKEN       = $appConfigSession->resources["ods.api.auth.token"] = API_ODS_AUTH_TOKEN;
					$API_AUTH_COOKIE      = ( defined( API_ODS_AUTH_COOKIE ))? API_ODS_AUTH_COOKIE : "";
				} elseif( isset( $appConfigSession->resources["ods.api.auth.token"])) { 
				    $API_AUTH_TOKEN       = $appConfigSession->resources["ods.api.auth.token"];
					$API_AUTH_COOKIE      = ( isset( $appConfigSession->resources["ods.api.auth.cookie"] ))?$appConfigSession->resources["ods.api.auth.cookie"] : "";
		        } else {
					$API_END_POINT        = API_ODS_URI;	
					$API_AUTH_USER        = API_ODS_AUTH_USER;
					$API_AUTH_PWD         = API_ODS_AUTH_PWD;
					$API_AUTH_SID         = base64_encode(sprintf("%s:%s", $API_AUTH_USER, $API_AUTH_PWD));				
					try {
						$authClient       = new Zend_Http_Client($API_END_POINT."/loginm", array('keepalive' => true));
						$authClient->setMethod( Zend_Http_Client::POST);
						$authClient->setParameterPost(array("login"=>$API_AUTH_USER,"password"=>$API_AUTH_PWD));
						$authClient->setHeaders(array("Authorization"=>"Basic ".$API_AUTH_SID,"Accept" => "application/json"));
										
						$authResponse        = $authClient->request();
						$authResponseArray   = json_decode($authResponse->getBody(), true);
						$authCookies         = $authResponse->getHeader("Set-Cookie");
                        //print_r($authCookies); die();
						if(!empty($authCookies)) {
							$API_AUTH_COOKIE = $authCookies;							
						}
						if( isset($authResponseArray["reponse"]["token"])) {
							$API_AUTH_TOKEN  = $authResponseArray["reponse"]["token"];
						} else {
							throw new Exception("Jeton Introuvable...");
						}
						if(!empty( $API_AUTH_TOKEN) ) {
							$appConfigSession->resources["ods.api.auth.token"] = $API_AUTH_TOKEN;
						}
					} catch ( Exception $e ) {
						$errorMessages[]  = sprintf("Erreur d'authentification : %s", $e->getMessage());					
					}
				}
				$headers["Cookie"]        = $API_AUTH_COOKIE;
				$headers["Authorization"] = "Bearer ".$API_AUTH_TOKEN;
				$API_URI                  = $API_END_POINT."/dgi/ifu/ifuall";
				$API_SEND_METHOD          = Zend_Http_Client::POST;
				$searchQ                  = strtoupper($searchQ);
				break;			
		}
		//print_r($errorMessages); die();
		//print_r($API_AUTH_COOKIE); die();
		if(!count( $filters)) {
			$errorMessages[]       = "Veuillez indiquer des filtres de recherche !";
		}
		if( empty($API_AUTH_TOKEN) ) {
			$errorMessages[]       = "Les paramètres d'authentification associés à cette requête sont invalides. Veuillez consulter le fournisseur du service ";
		} else {
			try {				
				$rawData           = '';
				$parameterData     = array($SEARCHQ_KEY=>$searchQ);
				$requestClient     = new  Zend_Http_Client( $API_URI, array('keepalive' => true));
				$requestClient->setMethod($API_SEND_METHOD);
				foreach( $filters as $filterKey => $filterValue ) {
					     if(!isset($allowedFilters[$filterKey])) {
							 continue;
						 }
						 if($rawData !== '') {
							$rawData .= '&';
						 }
						$rawData     .= $filterKey. '='. $filterValue;
						$parameterData[$filterKey]   =   $filterValue;				
				}
				switch( $API_SEND_METHOD ) {
					case Zend_Http_Client::GET:
					default:
					   $requestClient->setParameterGet($parameterData);
					   break;
					case Zend_Http_Client::POST:
					   $requestClient->setParameterPost($parameterData);
					   break;
				}

				//$requestClient->setParameterPost(array("nom_raison_sociale"=> "DAFANI"));
                //$requestClient->setParameterGet( array("nom_raison_sociale"=>$searchQ,$API_LIBELLE_KEY=>$searchQ));
				//$requestClient->setRawData($rawData);
				$requestClient->setHeaders($headers);							
				$apiResponse       = $requestClient->request();
				
			} catch ( Exception $e ) {
				$errorMessages[]   = sprintf("Erreur d'extraction:  %s [URI : %s, %s] ", $e->getMessage(), $API_URI, $searchQ);				
			}
		}       
		if( count($errorMessages) ) {
			ob_end_clean();
			echo ZendX_JQuery::encodeJson(array(0 => array("label"=>0,"value"=> "Erreur produite : ".implode(", ", $errorMessages))));
			exit;
		} else {
			$jsonRows              = array(0 => array("label"=>0, "value"=>"Aucune entreprise n'a été trouvée avec ces mots clés..."));
			$apiResponseData       = ( $apiResponse )? json_decode($apiResponse->getBody(), true) : array();
			//var_dump($apiResponseData); die();
			$rows                  = array();
			if( isset( $apiResponseData["etat"]["code"]) && isset( $apiResponseData["reponse"]) && $apiResponseData["etat"]["code"]== "200") {
				$rows              = $apiResponseData["reponse"];
			}
			if( count(   $rows ) ) {
				$rowid             = 0;
				$jsonRows          = array();
				foreach( $rows as $row ) {
					     $RESULTKEY                             = (isset($row[$API_REGISTREID_KEY]))? $row[$API_REGISTREID_KEY] : $rowid;
						 $jsonRows[$RESULTKEY ]                 = $row;
						 $jsonRows[$RESULTKEY ]["label"]        = (isset($row[$API_NUMERO_KEY]    ))? $row[$API_NUMERO_KEY]     : $row["numero"] ;
						 $jsonRows[$RESULTKEY ]["value"]        = (isset($row[$API_LIBELLE_KEY]   ))? $row[$API_LIBELLE_KEY]    : $row["nom_commercial"];		
						 $jsonRows[$RESULTKEY ]["denomination"] = (isset($row[$API_RAISON_SOCIALE]))? $row[$API_RAISON_SOCIALE] : "";	
						 $jsonRows[$RESULTKEY ]["activite"]     = (isset($row[$API_ACTIVITE_KEY]  ))? $row[$API_ACTIVITE_KEY]   : "";	
						 $jsonRows[$RESULTKEY ]["promoteur"]    = (isset($row["nom_prenom_prom"]  ))? $row["nom_prenom_prom"]   : "";
                         $jsonRows[$RESULTKEY ]["adresse"]      = (isset($row[$API_ADDRESS_KEY]   ))? $row[$API_ADDRESS_KEY]    : "";
						 if( isset( $row["sigle"]) && !empty($row["sigle"])) {
							 $jsonRows[$RESULTKEY ]["value"]    = sprintf("%s (%s) ", $jsonRows[$RESULTKEY]["value"],$row["sigle"] );
						 }
                         if( isset($jsonRows[$RESULTKEY ]["denomination"]) && !empty($jsonRows[$RESULTKEY ]["denomination"])) {
							 $jsonRows[$RESULTKEY ]["value"]    = sprintf("%s / Dénomination sociale: %s ", $jsonRows[$RESULTKEY]["value"], $jsonRows[$RESULTKEY ]["denomination"]);
						 }						 
						 $rowid++;
				}
			}
			if(isset( $apiResponseData["etat"]["code"]) && isset( $apiResponseData["reponse"]) && $apiResponseData["etat"]["code"]== "401") {
				$jsonRows    = array();
				$jsonRows[0] =array("label"=>"ERREUR","activite"=>"Non disponible! Erreur de connexion.", "value"=>"API ODS INACCESSIBLE ! Veuillez informer l'administrateur!");
			}
			ob_end_clean();
			echo ZendX_JQuery::encodeJson($jsonRows);
		    exit;
		}	
	}
	
	public function promoteurAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("promoteur");
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = intval ($this->_getParam("id", $this->_getParam("promoteurid", 0)));
 
		$rows         = $model->getList(array("promoteurid"=>$query), 1,1);
		$jsonRows     = array("error"=>"Aucun mandataire n'a été trouvé via cet identifiant","data"=>array());
		if( count(   $rows ) ) {
			$jsonRows = array("success"=>"Un mandataire a été trouvé","data"=>array());
			foreach( $rows as $row ) {
					 $jsonRows["data"][$row["promoteurid"]] = $row ;
			}
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}

	public function promoteursAction()
	{
		$this->_helper->viewRenderer->setNoRender( true);
		$this->_helper->layout->disableLayout(     true);
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("promoteur");
		$stringFilter =          new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", $this->_getParam("name", null)));
		$rows         = $model->getList(array("searchQ"=>$query,"lastname"=>"","firstname"=>"","numero"=>"", "identityid"=>0), 1, $totalResults);
		$jsonRows     = array( 0 => array("label"=>0, "value"=>"Aucun mandataire n'a été trouvé sous ce nom..."));
		if( count(   $rows ) ) {
			$rowid    = 0;
			$jsonRows = array();
			foreach( $rows as $row ) {
					 $jsonRows[$rowid]["label"]                   = sprintf("%s %s : %s",$row["lastname"],$row["firstname"],$row["numidentite"]);	
					 $jsonRows[$rowid]["value"]                   = sprintf("%s %s : %s",$row["lastname"],$row["firstname"],$row["numidentite"]);
                     $jsonRows[$rowid]["lastname"]                = $row["lastname"];	
                     $jsonRows[$rowid]["firstname"]               = $row["firstname"];	
                     $jsonRows[$rowid]["numidentite"]             = $row["numidentite"];
					 $jsonRows[$rowid]["identite_numero"]         = $row["numeroPiece"];
					 $jsonRows[$rowid]["identitetypeid"]          = $row["typePieceId"];
					 $jsonRows[$rowid]["lieu_etablissement"]      = $row["lieu_etablissement"];
					 $jsonRows[$rowid]["date_etablissement"]      = $row["date_etablissement"];
					 $jsonRows[$rowid]["organisme_etablissement"] = $row["organisme_etablissement"];
                     $jsonRows[$rowid]["telephone"]               = $row["telephone"];
                     $jsonRows[$rowid]["adresse"]                 = $row["adresse"];	
                     $jsonRows[$rowid]["profession"]              = $row["profession"];					 
                     $jsonRows[$rowid]["email"]                   = $row["email"];					 
                     $jsonRows[$rowid]["promoteurid"]             = $row["promoteurid"];	
                     $jsonRows[$rowid]["name"]                    = sprintf("%s %s", $row["lastname"], $row["firstname"]);
                     $jsonRows[$rowid]["sexe"]                    = $row["sexe"];					 
				     $rowid++;
			}
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}

	public function promoteurslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$promoteurs               = array();
		$promoteursListePaginator = null;
		$model                    = $this->getModel("promoteur");
		$modelIdentiteType        = $this->getModel("usageridentitetype");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]         ))? intval($params["page"])          : 1;
		$pageSize                 = (isset($params["maxitems"]     ))? intval($params["maxitems"])      : NB_ELEMENTS_PAGE;	
		$generalFilter            = (isset($params["generalfilter"]))? intval($params["generalfilter"]) : (isset($params["searchq"])?$params["searchq"] : "");
		$filters                  = array("searchQ"=>$generalFilter,"lastname"=>null,"firstname"=>null,"name"=>null,"email"=>null,"telephone"=>null,"numidentite"=>null,"identitetypeid"=>0);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue ) {
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( empty( $filters["name"]) && (!empty($filters["lastname"] ) || !empty( $filters["firstname"] ))) {
			$filters["name"]        = trim(sprintf("%s %s", $filters["lastname"],$filters["firstname"]));
		}		
		$promoteurs                 = $model->getList($filters , $pageNum , $pageSize );
		$promoteursListePaginator   = $model->getListPaginator($filters);
	
		if( null !== $promoteursListePaginator ) {
			$promoteursListePaginator->setCurrentPageNumber($pageNum);
			$promoteursListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->promoteurs     = $promoteurs;
		$this->view->pageNum        = $pageNum;
		$this->view->pageSize       = $pageSize;
		$this->view->paginator      = $promoteursListePaginator;
		$this->view->maxitems       = $pageSize;
		$this->view->columns        = array("left");
		 
		$this->view->identiteTypes  = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		$this->view->parentForm     = $this->_request->getParam("parentform"  , "none");
		$this->view->selectedKey    = $this->_request->getParam("selectedKey" , "promoteurid");
		$this->view->selectCmdKey   = $this->_request->getParam("selectCmdKey", "selectPromoteur");
		$this->view->selectedVal    = $this->_request->getParam("selectedVal" , "promoteurname");
		$this->render("promoteurs");
	}
	
	public function demandeurAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("demandeur");
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = intval ($this->_getParam("id", $this->_getParam("demandeurid", 0)));
 
		$rows         = $model->getList(array("demandeurid"=>$query), 1,1);
		$jsonRows     = array("error"=>"Aucun mandataire n'a été trouvé via cet identifiant","data"=>array());
		if( count(   $rows ) ) {
			$jsonRows = array("success"=>"Un mandataire a été trouvé","data"=>array());
			foreach( $rows as $row ) {
					 $jsonRows["data"][$row["demandeurid"]] = $row ;
			}
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}

	public function demandeursAction()
	{
		$this->_helper->viewRenderer->setNoRender( true);
		$this->_helper->layout->disableLayout(     true);
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("demandeur");
		$stringFilter =          new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", $this->_getParam("name", null)));
		$rows         = $model->getList(array("searchQ"=>$query,"lastname"=>"","firstname"=>"","numero"=>"", "identityid"=>0), 1, $totalResults);
		$jsonRows     = array( 0 => array("label"=>0, "value"=>"Aucun mandataire n'a été trouvé sous ce nom..."));
		if( count(   $rows ) ) {
			$rowid    = 0;
			$jsonRows = array();
			foreach( $rows as $row ) {
					 $jsonRows[$rowid]["label"]                   = sprintf("%s %s : %s",$row["lastname"],$row["firstname"],$row["numidentite"]);	
					 $jsonRows[$rowid]["value"]                   = sprintf("%s %s : %s",$row["lastname"],$row["firstname"],$row["numidentite"]);
                     $jsonRows[$rowid]["lastname"]                = $row["lastname"];	
                     $jsonRows[$rowid]["firstname"]               = $row["firstname"];	
                     $jsonRows[$rowid]["numidentite"]             = $jsonRows[$rowid]["identitenumero"]     = $row["numidentite"];
					 $jsonRows[$rowid]["identite_numero"]         = $row["numeroPiece"];
					 $jsonRows[$rowid]["identitetypeid"]          = $row["typePieceId"];
					 $jsonRows[$rowid]["lieu_etablissement"]      = $jsonRows[$rowid]["identite_lieu"]      = $row["lieu_etablissement"];
					 $jsonRows[$rowid]["date_etablissement"]      = $jsonRows[$rowid]["identite_date"]      = $row["date_etablissement"];
					 $jsonRows[$rowid]["organisme_etablissement"] = $jsonRows[$rowid]["identite_organisme"] =  $row["organisme_etablissement"];
                     $jsonRows[$rowid]["telephone"]               = $row["telephone"];
                     $jsonRows[$rowid]["adresse"]                 = $row["adresse"];	
                     $jsonRows[$rowid]["profession"]              = $row["profession"];					 
                     $jsonRows[$rowid]["email"]                   = $row["email"];					 
                     $jsonRows[$rowid]["demandeurid"]             = $row["demandeurid"];	
                     $jsonRows[$rowid]["name"]                    = sprintf("%s %s", $row["lastname"], $row["firstname"]);
                     $jsonRows[$rowid]["sexe"]                    = $row["sexe"];					 
				     $rowid++;
			}
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}

	public function demandeurslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$demandeurs               = array();
		$demandeursListePaginator = null;
		$model                    = $this->getModel("demandeur");
		$modelIdentiteType        = $this->getModel("usageridentitetype");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
	
		$params                   = $this->_request->getParams();
		$pageNum                  = (isset($params["page"]         ))? intval($params["page"])          : 1;
		$pageSize                 = (isset($params["maxitems"]     ))? intval($params["maxitems"])      : NB_ELEMENTS_PAGE;	
		$generalFilter            = (isset($params["generalfilter"]))? $params["generalfilter"] : (isset($params["searchq"])?$params["searchq"] : "");
		$filters                  = array("searchQ"=>$generalFilter,"lastname"=>null,"firstname"=>null,"name"=>null,"email"=>null,"telephone"=>null,"numidentite"=>null,"identitetypeid"=>0);	
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue ) {
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( empty( $filters["name"]) && (!empty($filters["lastname"] ) || !empty( $filters["firstname"] ))) {
			$filters["name"]        = trim(sprintf("%s %s", $filters["lastname"],$filters["firstname"]));
		}		
		//print_r($filters);die();
		$demandeurs                 = $model->getList($filters , $pageNum , $pageSize );
		$demandeursListePaginator   = $model->getListPaginator($filters);
	
		if( null !== $demandeursListePaginator ) {
			$demandeursListePaginator->setCurrentPageNumber($pageNum);
			$demandeursListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->demandeurs     = $demandeurs;
		$this->view->pageNum        = $pageNum;
		$this->view->pageSize       = $pageSize;
		$this->view->paginator      = $demandeursListePaginator;
		$this->view->maxitems       = $pageSize;
		$this->view->columns        = array("left");
		 
		$this->view->identiteTypes  = $modelIdentiteType->getSelectListe("Selectionnez un type de pièce d'identité", array("typeid", "libelle") , array() , null , null , false );
		$this->view->parentForm     = $this->_request->getParam("parentform"  , "none");
		$this->view->selectedKey    = $this->_request->getParam("selectedKey" , "demandeurid");
		$this->view->selectCmdKey   = $this->_request->getParam("selectCmdKey", "selectDemandeur");
		$this->view->selectedVal    = $this->_request->getParam("selectedVal" , "demandeurname");
		$this->render("demandeurs");
	}
	
	public function registresAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("registre");
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", $this->_getParam("searchq", null)));
		$typeid       = intval($this->_getParam("type",  0));
		$totalResults = intval($this->_getParam("limit", NB_ELEMENTS_PAGE ));
		$rows         = $errorMessages = array();
		try {
			$rows     = $model->getList(array("searchQ"=>$query,"type"=> $typeid,"types"=> array(1,2,3,4)), 1, $totalResults);
		} catch(Exception $e) {
		}		
		$jsonRows     = array( 0 => array("label"=>0, "value"=>"Aucune engtreprise n'a été trouvée avec ces mots clés..."));
		if( count(   $rows ) ) {
			$rowid    = 0;
			$jsonRows = array();
			foreach( $rows as $row ) {
					 $jsonRows[$rowid]["label"] = $row["numero"];
					 $jsonRows[$rowid]["value"] = $row["libelle"];				
				     $rowid++;
			}
		}
		if( count($errorMessages)) {
			echo ZendX_JQuery::encodeJson(array("label"=>"erreur","value"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
			exit;
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}
	
	
	public function csvrowsAction()
	{		
		$errorMessages              = array();
		$jsonCsvRows                = array();
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender( true );
		    $this->_helper->layout->disableLayout( true );
			$me                     = Sirah_Fabric::getUser();
			$postData               = $this->_request->getPost();
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$documentsUploadAdapter = new Zend_File_Transfer();
		    $documentsUploadAdapter->addValidator('Count'    , false , 1);
		    $documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
	        $documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
		
		    $destinationName        = $me->getDatapath(). "csvFile.csv";						
		    $documentsUploadAdapter->addFilter("Rename", array("target" => $destinationName, "overwrite"=> true) , "csvfile");
			if( $documentsUploadAdapter->isUploaded("csvfile") ) {
				$documentsUploadAdapter->receive(   "csvfile");
				if( $documentsUploadAdapter->isReceived("csvfile") ) {
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename" => $destinationName,"has_header" =>1), "rb");
					$csvRows        = $csvAdapter->getLines();
                    $i              = 0;					
					if( count(   $csvRows )) {
						$jsonCsvRows["rows"] = array();	
						foreach( $csvRows as $csvRow ) {
							     foreach( $csvRow as $csvColKey => $csvColValue ) {
									      $csvRow[$csvColKey]         = Encoding::toUTF8($csvColValue);
								 }
                                 $jsonCsvRows["rows"][$i]             = $csvRow;
                                 $jsonCsvRows["rows"][$i]["localite"] = (isset($csvRow["localite"] ))? strtoupper($stringFilter->filter($csvRow["localite"])): ((isset($postData["localite"])) ? $stringFilter->filter($postData["localite"]) : "");								 
						         $i++;
						}
					}								
				} else {
					$errorMessages[]     = "Le fichier CSV n'a pas pu être copié sur le serveur";
				}
			} else {
				$errorMessages[]         = "Veuillez selectionner un fichier CSV valide";
			}
			if( count( $jsonCsvRows["rows"] )) {
				$this->_helper->viewRenderer->setNoRender( true );
			    $jsonCsvRows["success"]  = "Les données du fichier CSV ont été récupérées avec success";				
				echo ZendX_JQuery::encodeJson($jsonCsvRows, true);
				exit;
		    }
		    if( count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender( true );
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites :". implode(", ", $errorMessages )));
			    exit;
		    }
		} else {	
          $this->render("csvupload");	
		}		  
	}
	
	public function checkrclibAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$model                = $this->getModel("registre");
        $modelTable           = $model->getTable();
		$prefixName           = $modelTable->info("namePrefix");
		$dbAdapter            = $modelTable->getAdapter();		
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$libelle              = $stringFilter->filter($this->_getParam("libelle", $this->_getParam("librccm", null )));
		if( $registre = $model->findRow( $libelle, "libelle", null, false ) ) {
			echo ZendX_JQuery::encodeJson(array("error"   => "Ce nom commercial n'est pas disponible. Rajoutez peut-être le numéro RC devant.."));
			exit;
		} else {
			echo ZendX_JQuery::encodeJson(array("success" => "Ce nom commercial est disponible, vous pouvez l'utiliser..."));
			exit;
		}
	}
	
	public function checkrcnumAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$model                = $this->getModel("registre");
     
        $modelTable           = $model->getTable();
		$prefixName           = $modelTable->info("namePrefix");
		$dbAdapter            = $modelTable->getAdapter();		
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$numero               = $stringFilter->filter($this->_getParam("numero"    , $this->_getParam("numrccm"       , null )));
		$registreid           = $stringFilter->filter($this->_getParam("registreid", $this->_getParam("id"            , 0    )));
		$typeRCCM             = $stringFilter->filter($this->_getParam("type"      , $this->_getParam("identification", "general")));
		$allowedNumeroSizes   = array(12,13,14,19);
		$numeroSize           = (!empty($numero))?intval(strlen($numero)) : 0;
		$isValid              = false;
		/*if(!in_array($numeroSize,$allowedNumeroSizes)) {
			echo ZendX_JQuery::encodeJson(array("error"=> "Le numéro RCCM saisi n'a pas une taille valide"));
			exit;
		}*/
		if( empty($typeRCCM)) {
			$typeRCCM         = "general";
		}
		if( strtolower($typeRCCM)=="physique" || strtoupper($typeRCCM)=="A") {
			$modelPhysique    = $this->getModel("registrephysique");
			$isValid          = $modelPhysique->checkNum($numero);
		} elseif(strtolower($typeRCCM)=="moral"        || strtoupper($typeRCCM)=="B") {
			$modelMoral       = $this->getModel("registremorale");
			$isValid          = $modelMoral->checkNum($numero);
		} elseif(strtolower($typeRCCM)=="modification" || strtoupper($typeRCCM)=="M") {
			$modelModification= $this->getModel("registremodification");
			$isValid          = $modelModification->checkNum($numero);
		} elseif(strtolower($typeRCCM)=="surete"       || strtoupper($typeRCCM)=="S") {
			$modelSurete      = $this->getModel("registresurete");
			$isValid          = $modelSurete->checkNum($numero);
		} else {
			$isValid          = $model->checkNum($numero);
		}
		if(!$isValid){
			echo ZendX_JQuery::encodeJson(array("error"=> "Le format du numéro RCCM saisi n'est pas valide"));
			exit;
		}
		$existantRegistre     = $model->findRow($numero,"numero",null,false);
		if(!$existantRegistre ) {
			$existantRegistre = $model->findRow(preg_replace("/[-\s:]+/","",$numero),"numero",null,false);
		}
        $foundRegistre        = (intval($registreid))?(($existantRegistre)?($existantRegistre->registreid!=$registreid):false) : ($existantRegistre!=null);		
		if( $foundRegistre ) {
			echo ZendX_JQuery::encodeJson(array("error"=> "Ce numéro RCCM existe déjà dans la base de données","numero"=>$numero));
			exit;
		} else {
			echo ZendX_JQuery::encodeJson(array("success"=> "Ce numéro RCCM est bon et disponible...","numero"=>$numero));
			exit;
		}
	}
	
	public function searchrcAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		//$this->getResponse()->setHeader("Content-Type", "application/json");
		
		$model                = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
        $modelTable           = $model->getTable();
		$prefixName           = $modelTable->info("namePrefix");
		$dbAdapter            = $tablePrefix = $modelTable->getAdapter();		
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$numero               = $stringFilter->filter($this->_getParam("numero", $this->_getParam("numrccm", null )));		
		
		if(!$model->checkNum($numero)) {
			echo ZendX_JQuery::encodeJson(array("error" => sprintf("Le numéro RCCM `%s` ne semble pas valide...", $numero)));
			exit;
		}
		if( $registre = $model->findRow( $numero, "numero", null, false ) ) {
			echo ZendX_JQuery::encodeJson(array("error" => "Ce numéro RCCM existe déjà dans la base de données..."));
			exit;
		}
		$localitesCodes                   = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "localiteid"), array() , 0 , null , false);
		$numeroParts                      = $model->getNumParts($numero);
		//var_dump($numeroParts); die();
		$localiteCode                     = (isset($numeroParts["localite"]))?$numeroParts["localite"] : strtoupper(substr($numero,2,3));
		$annee                            = (isset($numeroParts["annee"]   ))?$numeroParts["annee"]    : intval(substr($numero, 5, 4));
		$data                             = array("numero"=>$numero,"localite"=>$localiteCode,"annee"=>$annee,"lastname"=>"","firstname"=> "","description"=>"","libelle"=>"","description" => "", "telephone" => "");
		$data["success"]                  = "Proposition d'informations";
		$data["localiteid"]               = (isset( $localitesCodes[$localiteCode])) ? intval($localitesCodes[$localiteCode]) : 0;
		$data["date_enregistrement_year"] = $annee;
		$data["date_naissance_year"]      = "";
		$data["date_naissance_month"]     = $data["date_naissance_day"] = $data["date_naissance_month"] = $data["date_naissance_day"] = "00";
		if( $strNotEmptyValidator->isValid( $numero ) ) {
			$searchNum                    = substr($numero,0,10).intval(substr($numero,10,14));
			$searchNum2                   = substr($numero,0,10).sprintf("%02d",intval(substr($numero,10,14)));
			$searchNum3                   = substr($numero,0,10).sprintf("%03d",intval(substr($numero,10,14)));
			$data["searchnum"]            = $searchNum;	
			$searchSql                    = "SELECT * FROM rccm_registre_indexation WHERE numero=\"".$numero."\"";
			$contentRegistre              = $dbAdapter->fetchRow($searchSql, array(), 5);
			if( $contentRegistre ) {
				$data["lastname"]         = strtoupper($contentRegistre->nom);
				$data["firstname"]        = strtoupper($contentRegistre->prenom);
				$data["adresse"]          = $contentRegistre->adresse;
				$data["telephone"]        = $contentRegistre->telephone;
				$data["date_naissance"]   = $contentRegistre->date_naissance;
				$data["date_naissance"]   = strtoupper($contentRegistre->date_naissance);
				$data["lieu_naissance"]   = strtoupper($contentRegistre->lieu_naissance);
				$data["libelle"]          = strtoupper($contentRegistre->nom_commercial);
				$data["description"]      = $contentRegistre->description;
				$data["sexe"]             = $contentRegistre->sexe;
				$data["passport"]         = $contentRegistre->passport;				
			}  									
			//$dbsourceParams   = array("host" => "localhost","username"=> "root","password" => "@dMinRoot.MEBF","dbname" =>"sigard_base","isDefaultAdapter" => 0);		   			 
		}
		if(!empty( $data["date_naissance"] )) {
            preg_match("#([0-9]{2})\/([0-9]{2})\/([0-9]{4})#", $data["date_naissance"], $dateNaissancetParts );	
            if( isset( $dateNaissancetParts[1] ))	 {
				$data["date_naissance_day"]   = $dateNaissancetParts[1];
			}
            if( isset( $dateNaissancetParts[2] ))	 {
				$data["date_naissance_month"] = $dateNaissancetParts[2];
			}
            if( isset( $dateNaissancetParts[3] ))	 {
				$data["date_naissance_year"]  = $dateNaissancetParts[3];
			}           		
		}
		if(!empty( $data["date_naissance"] )) {
            preg_match("#([0-9]{2})\/([0-9]{2})\/([0-9]{4})#", $data["date_naissance"], $dateNaissancetParts );	
            if(isset( $dateNaissancetParts[1] ))	 {
				$data["date_naissance_day"]   = $dateNaissancetParts[1];
			}
            if(isset( $dateNaissancetParts[2] ))	 {
				$data["date_naissance_month"] = $dateNaissancetParts[2];
			}
            if(isset( $dateNaissancetParts[3] ))	 {
				$data["date_naissance_year"]  = $dateNaissancetParts[3];
			}           		
		}
		echo ZendX_JQuery::encodeJson($data);
		exit;
	}
	
	public function fileprogressAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		
		$session = $_SESSION['upload_progress_'.intval($this->_getParam('PHP_SESSION_UPLOAD_PROGRESS'))];
		$progress = array(
				           'lengthComputable' => true,
				           'loaded' => $session['bytes_processed'],
				           'total'  => $session['content_length']);
		echo ZendX_JQuery::encodeJson($progress);		
	}		
	
    public function countriesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("country");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}
	
	
	public function keywordsAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("keyword");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}
	
	
	public function citiesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("countrycity");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}
	
	
	public function projectypesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("projectype");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
		exit;
	}
	
	
	
	public function languagesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("language");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}
	
	
	public function domainesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("domaine");
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );		
	}
	
	public function professionsAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("profession");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}
	
	public function entreprisesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");
		$model        = $this->getModel("entreprise");
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$list         = $model->getTypeaheadList(  10 , $query );
		echo ZendX_JQuery::encodeJson( $list );
	}	
	
	
	public function domaineslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		
		$model              = $this->getModel("domaine");		
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$libelle              = $stringFilter->filter($this->_getParam("libelle", null));
		$domaines             = $model->getList(          array("libelle" => $stringFilter->filter($libelle) ) , $pageNum , $pageSize);
		$paginator            = $model->getListPaginator( array("libelle" => $stringFilter->filter($libelle) ));
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns      = array("left");
		$this->view->domaines     = $domaines;
		$this->view->libelle      = $libelle;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->parentform   = $this->_request->getParam("parentform" , "none");
		$this->view->maxitems     = $pageSize;
		$this->render("domaines");
	}	 
	
	public function registreslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->view->title  = "Liste des registres"  ;
	
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
	
		$registres          = array();
		$paginator          = null;
		$me                 = Sirah_Fabric::getUser();
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
	
		$filters              = array("libelle"=> null,"numero"=> null,"domaineid"=>0,"creatorid"=> 0,"localiteid"=>0,"annee" => 0,"nom"=> null, "prenom" => null,"searchQ" => null,
		                              "date_year"=> null, "date_month" => null, "date_day" => null,"periode_start_year" => DEFAULT_START_YEAR,"periode_end_year"=> DEFAULT_END_YEAR, "periode_start_month"=> DEFAULT_START_MONTH,"periode_start_day"=> DEFAULT_START_DAY ,"periode_end_day" => DEFAULT_END_DAY , "periode_end_month"  => DEFAULT_END_MONTH,"passport"=>null,"telephone"=>null);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if(isset($filters["name"] )) {
			$nameToArray          = preg_split("/[\s]+/", $filters["name"]);
			if(count($nameToArray) > 2) {
				$filters["nom"]   = $nameToArray[0] ;
				unset($nameToArray[0]);
				$filters["prenom"] = implode(" ", $nameToArray );
			} elseif( count($nameToArray) == 2)	 {
				$filters["nom"]    = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
				$filters["prenom"] = (isset($nameToArray[1]))? $nameToArray[1] : "" ;
			} elseif( count($nameToArray) == 1)	 {
				$filters["name"]   = (isset($nameToArray[0]))? $nameToArray[0] : "" ;
			}				
		}
        if( !$me->isAdmin() ) {
			 $filters["localiteid"] = $me->city;
		}
        if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval( $filters["periode_end_month"] ))  && (isset( $filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
				(isset( $filters["periode_end_day"]) && intval( $filters["periode_end_day"] ))  && (isset( $filters["periode_start_day"]) && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart    = new Zend_Date(array("year" => $filters["periode_start_year"],"month"=> $filters["periode_start_month"],"day"=> $filters["periode_start_day"]  ));
			$zendPeriodeEnd      = new Zend_Date(array("year" => $filters["periode_end_year"]  ,"month"=> $filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]    ,));
			$filters["periode_start"] = ($zendPeriodeStart ) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd   ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$registres                  = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                  = $model->getListPaginator($filters);
		
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns        = array("left");
		$this->view->registres      = $registres;
		$this->view->domaines       = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites      = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users          = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types          = array(0 => "Selectionnez un type de registre", 1 => "Personnes Physiques", 2 => "Personnes morales", 3 => "Sûrétés", 4 => "Modifications");
		$this->view->filters        = $filters;
		$this->view->params         = $params;
		$this->view->paginator      = $paginator;
		$this->view->pageNum        = $pageNum;
		$this->view->pageSize       = $pageSize;
		$this->view->parentform     = $this->_request->getParam("parentform" , "none");
		$this->view->selectedKey    = $this->_request->getParam("selectedKey", "none");
		$this->view->selectedVal    = $this->_request->getParam("selectedVal", "none");
		$this->view->selectedCmdKey = $this->_request->getParam("selectedCmdKey", "none");
		$this->view->maxitems       = $pageSize;
		$this->view->showSearch     = intval($this->_getParam("showsearch", true ));
		$this->render("registres");
	}	 
}