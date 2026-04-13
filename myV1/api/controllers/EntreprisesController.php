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
class Api_EntreprisesController extends Sirah_Controller_Default
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
	
	/*
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
	*/
	
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
	
	public static function fixEncoding($data) {
		if (is_array($data)) {
			return array_map(array(self::class,'fixEncoding'), $data);
		}
		$encoding = mb_detect_encoding($data, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

		if ($encoding !== 'UTF-8') {
			return mb_convert_encoding($data, 'UTF-8', $encoding);
		}
		return $data;
	}
	
	static function utf8Fix($data) {
		return array_map(function ($item) {
			if (is_array($item)) {
				return self::utf8Fix($item);
			}
			if (is_string($item)) {
				return mb_convert_encoding($item, 'UTF-8', 'Windows-1252');
			}
			return $item;
		}, $data);
	}
 
	
	public function listAction()
	{		
	    $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response            = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		$model                   = $this->getModel("registre");
 
 	     
		$entreprises             = $errorMessages = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 1));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_entreprises" => array());
		}
		if(!isset( $stateStore->filters["_entreprises"]["maxitems"])) {
			$stateStore->filters["_entreprises"] = array("page"=>1,"maxitems"=>20,"numrccm"=>null,"numcnss"=>null,"numero"=>null,"numifu"=>null,"libelle"=>null,"nomcommercial"=>null,"order"=>"DESC","entrepriseids"=>array(),"entrepriseid"=>0);			
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
		$pageNum                  = (isset($params["page"]    ))? intval($params["page"])     : $stateStore->filters["_entreprises"]["page"];
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_entreprises"]["maxitems"];		
		$searchQ                  = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                  = $stateStore->filters["_entreprises"];
        //$params                   = $filters = $this->_request->getPost();	
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}	 	
        $dbConnected        = false;
		try {
			$dbName         = "MEBF-NAV";
			$dbHost         = "10.60.16.59";
			$dbUsername     = "FNRCMM";
			$dbPwd          = "P@ssw0rd-FnRCCM3";
			$siguDbParams   = array("host"=>$dbHost,"username"=>$dbUsername,"password"=>$dbPwd,"dbname"=>$dbName,"isDefaultAdapter" =>0);
			$dbSource       = Zend_Db::factory("Sqlsrv"   , $siguDbParams);
		} catch(Exception $e) {
			$errorMessages[]= sprintf("Erreur de connexion à la base de données : %s", $e->getMessage());
		}
		 
		if( empty($errorMessages) && $dbSource) {
			try {

				$dbSelect       = $dbSource->select()->from(array("ENT"=>"MEBF\$Entreprise"),array("codeEntreprise"=>"ENT.No_","ENT.FormeJuridique",
																						 "IdActivite"=>"ENT.Primary Activity","IdActivitePrincipale"=>"ENT.Primary Activity","ActivitePrimaire"=>"ENT.Primary Activity","ActiviteSecondaire"=>"ENT.Activity Sector","codeActivite"=>"ENT.Primary Activity",
																						 "ENT.Pays","ENT.Adress","Address"=>"ENT.Adress","ENT.Quartier","ENT.Porte","Email"=>"ENT.E-Mail","ENT.E-Mail","TelMobile1"=>"ENT.Mobile 1","TelMobile2"=>"ENT.Mobile 2","TelBureau"=>"ENT.Tel Domicile","ENT.Avenue","ENT.Rue","BoitePostale"=>"ENT.Boite postale" ,"CodePostale"=>"ENT.Code Postale","Province"=>"ENT.Province Code","Arrondissement"=>"ENT.Arrondissement Code",
																						 "libelle"=>"ENT.Commercial Name","NomCommercial"=>"ENT.Commercial Name","DenominationSociale"=>"ENT.DonominationSocial","denomination"=>"ENT.DonominationSocial","sigle"=>"ENT.Sigle","ENT.Sigle",
																						 "ENT.MontantAction","Capital"=>"ENT.CapitalEnNumeraire","ENT.CapitalEnNature","ENT.capitalEnIndustrie","ENT.CapitalSocial","DontNature"=>"ENT.CapitalSocial",
																						 "Numero"=>"ENT.RCCM","NumeroRCCM"=>"ENT.RCCM","ENT.StatusRCCM",
																						 "NumeroIFU"=>"ENT.IFU","ENT.StatusIFU","Regime"=>"ENT.Taxation Regime","DivisionFiscaleCode"=>"ENT.Division Fiscal Code",
																						 "NumeroCNSS"=>"ENT.CNSS","ENT.StatusCNSS","StatutCNSS"=>"ENT.StatusCNSS","effectif_perm"=>"ENT.Employee Permanat","effectif_temp"=>"Employee Temporary"))								
										   ->join(    array("FRM"=>"MEBF\$Forme Juridique")  ,"FRM.Code=ENT.FormeJuridique",array("forme"=>"FRM.Libelle","codeforme"=>"FRM.Code","FRM.IdentifiantFormaliteCreate","FRM.IdentifiantFormaliteModif","FRM.IdentifiantFormaliteRadiation"))
										   ->joinLeft(array("ACT"=>"MEBF\$Company Activity") ,$dbSource->quoteIdentifier("ACT.Code")."=".$dbSource->quoteIdentifier("ENT.Primary Activity"),array("SecteurActivite"=>"ACT.Description","libActivite"=>"ACT.Description","descriptionActivite"=>"ACT.Description"))							 
										   ->joinLeft(array("TER"=>"MEBF\$Terrain")          ,"TER.IdTerrain=ENT.IdTerrain",array("TER.NumeroSection","TER.Superficie","TER.NumeroLot","TER.NumeroParcelle","TER.Perimetre","TER.IdTerrain"))
										   ->joinLeft(array("DIV"=>"MEBF\$Division Fiscale") ,$dbSource->quoteIdentifier("DIV.Code")."=".$dbSource->quoteIdentifier("ENT.Division Fiscal Code"),array("DivisionFiscale"=>"DIV.Name"))
										   ->joinLeft(array("REG"=>"MEBF\$Regime Imposition"),$dbSource->quoteIdentifier("REG.Code")."=".$dbSource->quoteIdentifier("ENT.Taxation Regime"),array("RegimeFiscale"=>"REG.Name"));
				if( isset($filters["numrccm"]) && !isset($filters["numero"]) && !empty($filters["numrccm"])) {
					$filters["numero"] = $filters["numrccm"];
				}
				if( isset($filters["numero"]) && !empty($filters["numero"])) {
					$dbSelect->where("ENT.RCCM LIKE '%".strip_tags($filters["numero"])."%'");
				}
                if( isset($filters["numifu"]) && !empty($filters["numifu"])) {
					$dbSelect->where("ENT.IFU LIKE '%".$filters["numifu"]."%'");
				}
                if( isset($filters["numcnss"]) && !empty($filters["numcnss"])) {
					$dbSelect->where("ENT.CNSS LIKE '%".strip_tags($filters["numcnss"])."%'");
				}
                if( isset($filters["libelle"]) && !empty($filters["libelle"])) {
					$dbSelect->where($dbSource->quoteIdentifier("ENT.Commercial Name")." LIKE '%".strip_tags($filters["libelle"])."%'");
				}
                $entreprises       = $dbSource->fetchAll($dbSelect, array(), Zend_Db::FETCH_ASSOC);	
				//var_dump($dbSelect->__toString()); 
                if( count($entreprises)>1) {
					$entreprises   = array();
				}					
			} catch(Exception $e) {
				$errorMessages[]   = sprintf("Erreur de sélection des entreprises : %s", $e->getMessage());
			}
		}
		 
		$total                            = 0;
		$response->clearAllHeaders();
		$response->setHttpResponseCode(200);	
		$response->sendHeaders();
 
		if( count($entreprises) && empty($errorMessages) ) {
			$total                        = count($entreprises);
			$cleanEntreprises             = self::utf8Fix($entreprises);
			$responseData                 = array("data"=>$cleanEntreprises,"total"=>$total,"pageSize"=>1,"numpage"=>1);
 
			echo json_encode(array("response"=>$responseData,"status"=>"200"),JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		} elseif(count($errorMessages)){
			$responseData                 = array("data"=>array(),"paginator"=>0,"total"=>$total,"pageSize"=>1,"numpage"=>1);
			 
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>1,"numpage"=>10);
			 
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
		 		
	}
	
	
	
	public function dirigeantsAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$response            = $this->getResponse();
		$response->setHeader("Content-Type","application/json",true);
		$model                   = $this->getModel("registre");
 
 	     
		$entreprises             = $errorMessages = array();
		$paginator               = null;
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 1));
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_usagers" => array());
		}
		if(!isset( $stateStore->filters["_usagers"]["maxitems"])) {
			$stateStore->filters["_usagers"] = array("page"=>1,"maxitems"=>20,"numrccm"=>null,"numcnss"=>null,"numero"=>null,"numifu"=>null,"libelle"=>null,"nomcommercial"=>null,"order"=>"DESC","usagerids"=>array(),"usagerid"=>0);			
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
		$pageNum                  = (isset($params["page"]    ))? intval($params["page"])     : $stateStore->filters["_usagers"]["page"];
		$pageSize                 = (isset($params["maxitems"]))? intval($params["maxitems"]) : $stateStore->filters["_usagers"]["maxitems"];		
		$searchQ                  = (isset($params["searchQ"] ))? $params["searchQ"]          : null;
		$filters                  = $stateStore->filters["_usagers"];
        //$params                   = $filters = $this->_request->getPost();	
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}	 	
        $dbConnected        = false;
		try {
			$dbName         = "MEBF-NAV";
			$dbHost         = "10.60.16.59";
			$dbUsername     = "FNRCMM";
			$dbPwd          = "P@ssw0rd-FnRCCM3";
			$siguDbParams   = array("host"=>$dbHost,"username"=>$dbUsername,"password"=>$dbPwd,"dbname"=>$dbName,"isDefaultAdapter" =>0);
			$dbSource       = Zend_Db::factory("Sqlsrv"   , $siguDbParams);
		} catch(Exception $e) {
			$errorMessages[]= sprintf("Erreur de connexion à la base de données : %s", $e->getMessage());
		}
		 
		if( empty($errorMessages) && $dbSource) {
			try {
				$dbSelectUsagers    = $dbSource->select()->from(    array("U"  =>"MEBF\$Usager"),array( "IdUsager"=>"U.No_","U.NomRaisonSociale","U.NomJeuneFille","firstname"=>"U.Prenom","lastname"=>"U.Surnom","nom"=>"U.Surnom","prenom"=>"U.Prenom","U.Surnom","U.SituationMatrimoniale","U.Gender","Sexe"=>"U.Gender","CNIB"=>"U.CIN","NumeroPiece"=>"U.CIN","U.CIN","datenaissance"=>new Zend_Db_Expr("FORMAT(U.DateNaissance,'yyyy-MM-dd')"),"U.LieuNaissance",
					                                                                                    "Country"=>"U.Country Code","U.Avenue","telephone"=>"U.Phone No_","Telephone"=>"U.Phone No_","Mobile"=>"U.Phone No_","Email"=>"U.E-Mail","email"=>"U.E-Mail","Tel1Domicile"=>"U.Tel Domicile","Mobile1"=>"U.Phone No_","Mobile2"=>"U.Mobile 2","Tel2Domicile"=>"U.Tel Bureau","U.Tel Bureau","U.Quartier",
																										"U.IdFonction","U.Rue","CodePostal"=>"U.Code Postale","BoitePostale"=>"U.Boite postale","U.Code Secteur_Village","U.Arrondissement Code"))
														 ->join(    array("ENT"=>"MEBF\$Entreprise"),$dbSource->quoteIdentifier("ENT.Legal Representative")."=".$dbSource->quoteIdentifier("U.No_"), array("codeEntreprise"=>"ENT.No_","IdEntreprise"=>"ENT.No_"))														 													    
														 ->joinLeft(array("DC" =>"MEBF\$Document Attachment"),$dbSource->quoteIdentifier("DC.No_")."=".$dbSource->quoteIdentifier("U.No_")." AND ".$dbSource->quoteIdentifier("DC.File Name")."='CNIB'",array("DatePiece" =>new Zend_Db_Expr("FORMAT(".$dbSource->quoteIdentifier("DC.Date Of Etablisement").",'yyyy-MM-dd')"),"LieuPiece"=>"DC.Place Of Etablisement"))
														 ->joinLeft(array("F"  =>"MEBF\$Usager Fonction"),$dbSource->quoteIdentifier("F.Code")."=".$dbSource->quoteIdentifier("U.IdFonction"),array("CodeFonction"=>"F.code","Profession"=>"F.Name","Fonction"=>"F.Name"));
				if( isset($filters["numrccm"]) && !isset($filters["numero"]) && !empty($filters["numrccm"])) {
					$filters["numero"] = $filters["numrccm"];
				}
				if( isset($filters["numero"]) && !empty($filters["numero"])) {
					$dbSelectUsagers->where("ENT.RCCM LIKE '%".strip_tags($filters["numero"])."%'");
				}
                if( isset($filters["numifu"]) && !empty($filters["numifu"])) {
					$dbSelectUsagers->where("ENT.IFU LIKE '%".$filters["numifu"]."%'");
				}
                if( isset($filters["numcnss"]) && !empty($filters["numcnss"])) {
					$dbSelectUsagers->where("ENT.CNSS LIKE '%".strip_tags($filters["numcnss"])."%'");
				}
                if( isset($filters["libelle"]) && !empty($filters["libelle"])) {
					$dbSelectUsagers->where($dbSource->quoteIdentifier("ENT.Commercial Name")." LIKE '%".strip_tags($filters["libelle"])."%'");
				}
                $usagers       = $dbSource->fetchAll($dbSelectUsagers, array(), Zend_Db::FETCH_ASSOC);	
									
			} catch(Exception $e) {
				$errorMessages[]   = sprintf("Erreur de sélection des usagers : %s", $e->getMessage());
			}
		}
		 
		$total                            = 0;
		$response->clearAllHeaders();
		$response->setHttpResponseCode(200);	
		$response->sendHeaders();
 
		if( count($usagers) && empty($errorMessages) ) {
			$total                   = count($usagers);
			$cleanUsagers            = self::utf8Fix($usagers);
			$responseData            = array("data"=>$cleanUsagers,"total"=>$total,"pageSize"=>1,"numpage"=>1);
 
			echo json_encode(array("response"=>$responseData,"status"=>"200"),JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		} elseif(count($errorMessages)){
			$responseData            = array("data"=>array(),"paginator"=>0,"total"=>$total,"pageSize"=>1,"numpage"=>1);
			 
			echo ZendX_JQuery::encodeJson(array("response"=>"Erreurs:".implode(" ; ",$errorMessages),"status"=>"500"));
			exit;
		} else {
			$responseData            = array("data"=>array(),"paginator"=>null,"total"=>0,"pageSize"=>1,"numpage"=>10);
			 
			echo ZendX_JQuery::encodeJson(array("response"=>$responseData,"status"=>"204"));
			exit;
		}
	}
	 
	
	 
	
	 
}