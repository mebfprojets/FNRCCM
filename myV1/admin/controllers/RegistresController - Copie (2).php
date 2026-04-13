<?php
ini_set('memory_limit', '1024M'); 

require 'E:\webserver/www/Xpdf/vendor/autoload.php';
require 'E:\webserver/www/erccm/libraries/Forceutf8/vendor/autoload.php';
/*require 'E:\webserver/www/erccm/libraries/FPDI2/vendor/autoload.php';*/
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use XPDF\PdfToText;
use \ForceUTF8\Encoding;
/*use setasign\Fpdi\Fpdi;
  use setasign\Fpdi\PdfReader;*/
require_once("tcpdf/tcpdf.php");
require_once("Fpdi/fpdi.php");
/*require_once("FPDF/fpdf.php");
require_once("FPDI2/src/autoload.php");*/


function formatNumber(    $number  ){
	      if(!is_numeric( $number )){
			  return $number;
		  }
		  return preg_replace('/(\d{1,3})(?=(\d{3})+$)/', "$1.", floatval($number));
}
class Admin_RegistresController extends Sirah_Controller_Default
{	

    public function siguedbAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$appConfigSession = new Zend_Session_Namespace("AppConfig");
		
		$model  = $this->getModel("registre");
		
		var_dump(API_SIGUE_AUTH_TOKEN); die();

	}
	
    public function importnereAction()
	{
 
		$model                   = $this->getModel("registre");
    	$modelLocalite           = $this->getModel("localite")	;
        $modelAdresse            = $this->getModel("registreadresse");
		
		$updatedRegistres        = $errorMesages = array();
		
		$me                      = Sirah_Fabric::getUser();
		$userTable               = $me->getTable();
		$dbAdapter               = $userTable->getAdapter();
		$prefixName              = $userTable->info("namePrefix");
		$defaultData             = array("dbparams_host"=>"10.60.16.165\CCI", "dbparams_username"=>"sa", "dbparams_password"=>"P@ssw0rd","dbparams_dbname"=>"dbNERE",
		                                 "annee"=>0, "localite"=>"OUA", "localiteid"=>0);
		$localites               = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS             = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                  = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                 "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017",
										 "2018"=>"2018","2019"=>"2019","2020"=>"2020");	
		if( $this->_request->isPost() )  {
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $tablePrefix = $userTable->info("namePrefix");
			$postData               = $this->_request->getPost();
			$dbParams               = array("isDefaultAdapter" =>0);
			$dbParams["host"]       = $host     = (isset($postData["dbparams_host"]    ))? $postData["dbparams_host"]     : $defaultData["dbparams_host"];
		    $dbParams["username"]   = $username = (isset($postData["dbparams_username"]))? $postData["dbparams_username"] : $defaultData["dbparams_username"];
		    $dbParams["password"]   = $pwd      = (isset($postData["dbparams_password"]))? $postData["dbparams_password"] : $defaultData["dbparams_password"];
		    $dbParams["dbname"]     = $dbName   = (isset($postData["dbparams_dbname"]  ))? $postData["dbparams_dbname"]   : $defaultData["dbparams_dbname"];
		    $annee                  = (isset($postData["annee"]         ))? intval($postData["annee"])     : 0;
			$localiteCode           = (isset($postData["localite"]      ))? $postData["localite"]          : $defaultData["localite"];
			$localiteid             = (isset($localiteIDS[$localiteCode]))? $localiteIDS[$localiteCode]    : 0;
			$importedRegistres      = array();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter             = new Zend_Filter();
			$stringFilter->addFilter(   new Zend_Filter_StringTrim());
			$stringFilter->addFilter(   new Zend_Filter_StripTags());
			$stringFilter->addFilter(   new Sirah_Filtre_Encode());
			$stringFilter->addFilter(   new Sirah_Filtre_FormatDate());
			$stringFilter->addFilter(   new Sirah_Filtre_StripNull());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
 
			try {
				//$dbSource           = Zend_Db::factory("Sqlsrv", $dbParams);
				$dbSource           = Zend_Db::factory("Sqlsrv", $dbParams);
			} catch(Exception $e ) {
				$errorMessages[]    = sprintf("Erreur de connexion à la base de données : ".$e->getMessage()); 
				printf("Erreur de connexion à la base de données : ".$e->getMessage()); die();
			}
 
		    if( empty($errorMessages) ) {
				try {
				} catch(Exception $e) {
				}
				$selectRegistres             = $dbSource->select()->from(array("ENT"=>"EEntreprise"), array("NumeroRCCM"=>"ENT.NRCCM_ent","RCCM"=>"ENT.NRCCM_ent","NomCommercial"=>"ENT.nomcom_ent","DateRCCM"=>"ENT.DateRCCM_ent","IFU"=>"ENT.NIMPOT_ent","DateIFU"=>"ENT.datenimpot_ent","NumeroCNSS"=>"ENT.NSECU_ent","DateCNSS"=>"ENT.datensecu_ent"))
				                                                  ->join(array("ADR"=>"EAdresse"),"ADR.code_ent=ENT.code_ent",  array("parcelle"=>"ADR.parcelle_enta", "porte"=>"ADR.porte_enta","section"=>"ADR.section_enta","lot"=>"ADR.lot_enta","rue"=>"ADR.rue_enta"))
																  ->joinLeft(array("PSE"=>"PSecteur"),"PSE.code_sec=ADR.code_sect", array("lib_secteur"=>"PSE.lib_sec"))
																  ->joinLeft(array("PAR"=>"PArrondissement"),"PAR.code_arr=ADR.code_arr", array("lib_arrondissement"=>"PAR.lib_arr"))
																  ->order(array("ENT.DateRCCM_ent ASC"));
				$queryRows                  = $dbSource->fetchAll($selectRegistres, array(), Zend_Db::FETCH_ASSOC);												            
				//print_r($queryRows); die();
				if( count(   $queryRows) ) {
					foreach( $queryRows as $registreDbRow) {
						     $queryRow       =  array_map(function($field) use ($stringFilter){ $cleanField =$field;  if(is_string($field)){$cleanField = $stringFilter->filter($field); } return $cleanField; }, $registreDbRow);
						     $numRCCM        = (isset($queryRow["RCCM"]              ))?$queryRow["RCCM"]               : "";
							 $secteur        = (isset($queryRow["lib_secteur"]       ))?$queryRow["lib_secteur"]        : "";
							 $parcelle       = (isset($queryRow["parcelle"]          ))?$queryRow["parcelle"]           : "";
							 $porte          = (isset($queryRow["porte"]             ))?$queryRow["porte"]              : "";
							 $section        = (isset($queryRow["section"]           ))?$queryRow["section"]            : "";
							 $lot            = (isset($queryRow["lot"]               ))?$queryRow["lot"]                : "";
							 $rue            = (isset($queryRow["rue"]               ))?$queryRow["rue"]                : "";
							 $arrondissement = (isset($queryRow["lib_arrondissement"]))?$queryRow["lib_arrondissement"] : "";
							 $NumeroIFU      = (isset($queryRow["IFU"]               ))?$queryRow["IFU"]                : "";
							 $NumeroCNSS     = (isset($queryRow["NumeroCNSS"]        ))?$queryRow["NumeroCNSS"]         : "";
							 $DateIFU        = (isset($queryRow["DateIFU"]           ))?$queryRow["DateIFU"]            : "";
							 $DateCNSS       = (isset($queryRow["DateCNSS"]          ))?$queryRow["DateCNSS"]           : "";
							 
							 $cleanNumRCCM   = $model->normalizeNum($numRCCM);
							 
	
							 if( $cleanNumRCCM ) {
								 $registre                     = $model->findRow($cleanNumRCCM,"numero", null, false);
								 $queryRow["NumeroRCCM"]       = $cleanNumRCCM;
								 if( $registre ) {
									 if(!empty($NumeroIFU)) {
										 $registre->numifu     = $NumeroIFU;
									 }
									 if(!empty($NumeroCNSS) ) {
										 $registre->numcnss    = $NumeroCNSS;
									 }
									 
									 $registre->updateduserid  = 26;
									 $registre->updatedate     = time();
									 $registre->save();

									 $registreid           = $registre->registreid;
									 $registreAddress      = sprintf("arrondissement %s, secteur %s, parcelle n° %s, porte n° %s, section %s, lot %s", $arrondissement,$secteur,$parcelle,$porte,$section,$lot);
								 
								     $addressData          = array("code"=>$cleanNumRCCM,"avenue"=>"","description"=>$registreAddress,"quartier"=>$secteur,"rue"=>$rue,"porte"=>$porte,"numerolot"=>$lot,"numerosection"=>$section,"numeroparcelle"=>$parcelle, "updatedate"=>0,"updateduserid"=>0,"creationdate"=>0,"creatorid"=>0);  
								     $registreAdresse      = $modelAdresse->findRow($cleanNumRCCM,"code", null, false);
									 if( $registreAdresse ) {
										 $registreAdressId             = $registreAdresse->addressid;
										 $addressData["updateduserid"] = $me->userid;
										 $addressData["updatedate"]    = time();
										 if( $dbAdapter->update($prefixName."rccm_registre_address", $addressData, array("addressid=?"=>$registreAdressId)) ) {
										     $dbAdapter->update($prefixName."rccm_registre"        , array("adresse"=>$registreAddress,"numifu"=>$NumeroIFU,"numcnss"=>$NumeroCNSS), array("addressid=?"=>$registreAdressId));
										     $updatedRegistres[]       = $registreid;
											 $importedRegistres[]      = $queryRow;
										 }
									 } else {
										 $addressData                  = array_merge(array( "siteweb"=>"","codepostal"=>"","email"=>"","country"=>"BF","tel_dom1"=>"","tel_dom2"=>"","tel_mob1"=>"", "tel_mob2"=>""), $addressData);
										 $addressData["updateduserid"] = 0;
										 $addressData["updatedate"]    = 0;
										 $addressData["creatorid"]     = $me->userid;
										 $addressData["creationdate"]  = time();
										 if( $dbAdapter->insert($prefixName."rccm_registre_address", $addressData) ) {
											 $registreAdressId         = $dbAdapter->lastInsertId();
											 $dbAdapter->update($prefixName."rccm_registre", array("adresse"=>$registreAddress,"addressid"=>$registreAdressId,"numifu"=>$NumeroIFU,"numcnss"=>$NumeroCNSS), array("numero=?"=>$cleanNumRCCM));
										     $updatedRegistres[]       = $registreid;
											 $importedRegistres[]      = $queryRow;
										 }
									 }
								 }
							 }
					}
				}
			}
			print_r($importedRegistres); die();
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				$successMessage     = sprintf("L'opération s'est effectuée avec succès : nous avons mis à jour %d registres de commerce manquants", count($updatedRegistres) );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success" => $successMessage ));
					exit;
				}
				$this->setRedirect($successMessage, "success");
				$this->redirect("admin/registres/list/".$annee."/localiteid/".$localiteid); 
			}			
		}
	    $this->view->title     = "Importer les données à partir de NERE";
		$this->view->annees    = $annees;
		$this->view->localites = $localites;
		$this->view->data      = $defaultData;
	
	}
	
	public function importcnssAction()
	{
		@ini_set('memory_limit', '512M');		
		$this->view->title          = "Importer des données de la CNSS";
		$model                      = $this->getModel("registre");
		$modelFinance               = $this->getModel("registrefinance");
		$modelCaisse                = $this->getModel("registrecaisse");
		$modelLocalite              = $this->getModel("localite");
 
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
		$defaultInitData            = array("annee"=>2015,"localite"=>"OUA","folderstocheck"=>"F:\\ERCCM");
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$imported                   = $totalRows  = 0;
        $importedRows               = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
            $me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $tablePrefix = $userTable->info("namePrefix");			
			$postData               = $this->_request->getPost();
		    $annee                  = (isset($postData["annee"]         ))? intval($postData["annee"])  : 2017;
			$localiteCode           = (isset($postData["localite"]      ))? $postData["localite"]       : $defaultData["localite"];
			$localiteid             = (isset($localiteIDS[$localiteCode]))? $localiteIDS[$localiteCode] : 0;
			$i                      = 0;
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
 			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Count"    ,false, 1);
			$documentsUploadAdapter->addValidator("Extension",false, array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize",false, array("max" => "100MB"));
			
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			$csvStoreFilePath       = $me->getDatapath() . DS . time() . "CNSSData.csv";
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvStoreFilePath, "overwrite"=>true), "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty($errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("DateImmCNSS","DateEffetCNSS","NumeroCNSS","NumeroIFU","NumeroRCCM");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvStoreFilePath,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines       = $csvAdapter->getLines();
					$csvItems       = 1;
					if(isset($csvLines[0])) {
					   unset($csvLines[0]);
					}
					$csvRows        = array_filter($csvLines,function($csvRow) use ($model){return true;});
					$totalRows      = count($csvRows);
					if( $totalRows ) {
						foreach( $csvRows as $csvRow ) {
							     $NumRCCM           = preg_replace("/[^a-z0-9]/i", "", $csvRow["NumeroRCCM"]);
								 $NumCNSS           = preg_replace("/[^a-z0-9]/i", "", $csvRow["NumeroCNSS"]);
								 $NumIFU            = preg_replace("/[^a-z0-9]/i", "", $csvRow["NumeroIFU"]);
								 $DateCNSS          = $csvRow["DateImmCNSS"];
								 $DateEffetCNSS     = $csvRow["DateEffetCNSS"];
								 if( empty($NumRCCM) ) {
									 continue;
								 }
							     if( substr($NumRCCM,0, 2) != "BF" || stripos($NumRCCM,"BF")=== false ) {
									 $NumRCCM       = sprintf("BF%s", $NumRCCM);
								 }
								 $localiteCode      =  trim(substr($NumRCCM, 2, 3));
								 if(!isset($localiteIDS[$localiteCode]) ) {
									 continue;
								 }
							     $CleanNumRCCM      = $model->normalizeNum(preg_replace("/[^a-z0-9]/i", "", $NumRCCM));
								 if( $registre      = $model->findRow($CleanNumRCCM, "numero", null, false) && !empty($CleanNumRCCM) && !empty($NumIFU) && !empty($NumCNSS)) {									 
									 if( Zend_Date::isDate(         $DateCNSS,"dd/mm/YYYY")) {
										 $zendDate  = new Zend_Date($DateCNSS, Zend_Date::DATES ,"fr_FR");								 
									 } elseif( Zend_Date::isDate(   $DateCNSS,"YYYY-MM-dd") ) {
										  $zendDate = new Zend_Date($DateCNSS,"YYYY-MM-dd");
									 } elseif( Zend_Date::isDate(   $DateCNSS, Zend_Date::ISO_8601) ) {
										  $zendDate = new Zend_Date($DateCNSS, Zend_Date::ISO_8601);
									 } else {
										  $zendDate = null;
									 }
									 if( Zend_Date::isDate(      $DateEffetCNSS,"dd/mm/YYYY")) {
										$zendDateEffetCNSS = new Zend_Date($DateEffetCNSS, Zend_Date::DATES ,"fr_FR");								 
									 } elseif( Zend_Date::isDate($DateEffetCNSS,"YYYY-MM-dd") ) {
										$zendDateEffetCNSS = new Zend_Date($DateEffetCNSS,"YYYY-MM-dd");
									 } elseif( Zend_Date::isDate($DateEffetCNSS, Zend_Date::ISO_8601) ) {
										$zendDateEffetCNSS = new Zend_Date($DateEffetCNSS, Zend_Date::ISO_8601);
									 } else {
										$zendDateEffetCNSS = null;
									 }
									 if( null !== $zendDate ) {
										 $caisseData                      = array("numero"=>$NumCNSS,"numifu"=>$NumIFU,"numrccm"=>$CleanNumRCCM,"effectif"=>0, "nb_employes"=>0);
										 $caisseData["date_imm"]          = $zendDate->toString("YYYY-MM-dd H:i:s");
										 $caisseData["date_effet"]        = ( $zendDateEffetCNSS )? $zendDateEffetCNSS->toString("YYYY-MM-dd H:i:s") : $caisseData["date_imm"];
										 $cnssRow                         = $modelCaisse->findRow($NumCNSS,"numero", null, false);
										 if( isset($cnssRow->cnssid) ) {											 
											 $caisseData["updateduserid"] = $me->userid;
											 $caisseData["updatedate"]    = time();
											 if( $dbAdapter->update($prefixName."rccm_registre_cnss", $caisseData, array("numero=?"=>$NumCNSS)) ) {
											     $cnssid                  = $cnssRow->cnssid;
											 } else {
												 $errorMessages[]         = sprintf("Le numéro CNSS %s n'a pas pu être importé ", $NumCNSS); 
											 }
										 } else {
											 $caisseData["creatorid"]     = $me->userid;
											 $caisseData["creationdate"]  = time();
											 $caisseData["updateduserid"] = 0;
											 $caisseData["updatedate"]    = 0;
											 
											 if( $dbAdapter->insert( $prefixName."rccm_registre_cnss", $caisseData)) {
												 $cnssid                  = $dbAdapter->lastInsertId();												 
											 } else {
												 $errorMessages[]         = sprintf("Le numéro CNSS %s n'a pas pu être importé ", $NumCNSS);
											 }
										 }
										 if( $cnssid ) {
											 $registreUpdateData                  = array("cnssid"=>$cnssid,"numifu"=>$NumIFU,"numcnss"=>$NumCNSS);
											 $registreUpdateData["updateduserid"] = $me->userid;
											 $registreUpdateData["updatedate"]    = time();
											 if( $dbAdapter->update($prefixName."rccm_registre",$registreUpdateData, array("numero=?"=>$CleanNumRCCM)) ) {
												 $importedRows[$cnssid]           = $caisseData;
												 $imported++;
											 }
										 }
									 }  else {
										    $errorMessages[]   = sprintf("Le numéro CNSS %s n'a pas pu être importé parce que la date CNSS est invalide"); 
									 }									 
								 }		
                                            $i++;								 
						}
					}
				} else {
					                        $errorMessages[]   = "Le fichier CSV n'a pas été reçu par le serveur";
				}
			} else {
				                            $errorMessages[]   = "Le fichier CSV n'a pas été transféré";
			}
			if( count( $errorMessages ) ) {
			    if($this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			    }
			    foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			    }
			}	else {
				$successMessage     = sprintf("L'opération s'est effectuée avec succès : nous avons pu importer %d lignes de la CNSS sur un total de %d", $imported, $totalRows );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success" => $successMessage ));
					exit;
				}
				$this->setRedirect($successMessage, "success");
				$this->redirect("admin/registres/list/"); 
			}
		}
		$this->view->title            = "Import des données de la CNSS";
		$this->view->data             = $defaultData;
		$this->view->annees           = $annees;
		$this->view->localites        = $localites;
	}
	
	public function sigusearchAction()
	{
		$model                = $modelRegistre = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$searchQ              = $stringFilter->filter($this->_getParam("searchq"   , $this->_getParam("q"         , null )));
		$repository           = $stringFilter->filter($this->_getParam("searchfrom", $this->_getParam("repository", "sigu")));
		$rows                 = array();
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 0;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>$searchQ,"passport"=>null,"telephone"=>null,"name"=>null,"type"=>0,"keywords"=>null,
		                              "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,"periode_end_day" =>DEFAULT_END_DAY,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
        $findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
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
		$sigueListFound            = $model->siguesearch($filters, 0, 0);
		$registreids               = array();
		if( count(   $sigueListFound )) {
			foreach( $sigueListFound as $sigueRow ) {
				     if( $registre         = $modelRegistre->findRow($sigueRow["NumeroRCCM"], "numero",null, false )) {
						 $registreids[]    = $registre->registreid;
					 } else {
						if( $registreid    = $modelRegistre->insertsigue($sigueRow) !== false ) {
							$registreids[] = $registreid;
						}
					 }
			}
		}	
		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ, 3, $pageNum , $pageSize );
		    $paginator                = $model->getSimilarListPaginator($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]  = $similarSearchQ;
		} else {
			$registres                = (count($registreids))? $model->getList(array("registreids"=>$registreids))          : $model->getList( $filters , $pageNum , $pageSize);
		    $paginator                = (count($registreids))? $model->getListPaginator(array("registreids"=>$registreids)) : $model->getListPaginator($filters);
		}				
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Secteur d'activité" , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites        = $modelLocalite->getSelectListe("Localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types            = array(0 => "Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;
		
		$this->render("list");
	}
	
	public function apiodsearchAction()
	{
		$model                = $modelRegistre = $this->getModel("registre");
		
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$resultats            = array();
		$errorMessages        = array();
		$searchQ              = $stringFilter->filter($this->_getParam("searchq"   , $this->_getParam("q"         , null )));
		$repository           = $stringFilter->filter($this->_getParam("searchfrom", $this->_getParam("repository", null )));
		$rows                 = array();
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 0;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>$searchQ,"passport"=>null,"telephone"=>null,"name"=>null,"type"=>0,"keywords"=>null,
		                              "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,"periode_end_day" =>DEFAULT_END_DAY,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
        $findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
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
		$sigueListFound            = $model->apiodsearch($filters, $pageNum, $pageSize );
		$registreids               = array();
		if( count(   $sigueListFound )) {
			foreach( $sigueListFound as $sigueRow ) {
				     if( $registre         = $modelRegistre->findRow($sigueRow["NumeroRCCM"], "numero",null, false )) {
						 $registreids[]    = $registre->registreid;
					 } else {
						if( $registreid    = $modelRegistre->insertsigue($sigueRow) !== false ) {
							$registreids[] = $registreid;
						}
					 }
			}
		}	
		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ, 3, $pageNum , $pageSize );
		    $paginator                = $model->getSimilarListPaginator($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]  = $similarSearchQ;
		} else {
			$registres                = (count($registreids))? $model->getList(array("registreids"=>$registreids)) :  $model->getList( $filters , $pageNum , $pageSize);
		    $paginator                = $model->getListPaginator($filters);
		}				
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Secteur d'activité" , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites        = $modelLocalite->getSelectListe("Localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types            = array(0 => "Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;
	}

    public function cleandbAction()
	{
		$model                = $modelRegistre = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		
		$modelTable           = $model->getTable();
        $dbAdapter            = $modelTable->getAdapter();
		
		$registres            = $model->getList();
		$deletedRepresentants = array();
		
		if( count(   $registres) ) {
			foreach( $registres as $registre ) {
				     $registreid              = $registre["registreid"];
					 $Nom                     = $registre["nom"];
					 $Prenom                  = $registre["prenom"];
					 $Passport                = $registre["passport"];
					 $cleanRepresentantId     = $registre["representantid"];
					 
				     $foundDoublonRow         = $modelRegistre->getList(array("nom"=>$Nom,"prenom"=>$Prenom,"registreid"=>$registreid));
					 $foundDoublonRowPASSPORT = $modelRegistre->getList(array("passport"=>$Passport ,"registreid"=>$registreid));
 
					 if( count(   $foundDoublonRow) ) {
						 foreach( $foundDoublonRow as $promoteurData ) {
							      $representantid  =  $promoteurData["representantid"];
								  
								  if( $representantid != $cleanRepresentantId ) {
									  if( $dbAdapter->delete($tablePrefix."rccm_registre_representants", array("representantid=?"=>$representantid)) &&
									      $dbAdapter->delete($tablePrefix."rccm_registre_dirigeants"   , array("representantid=?"=>$representantid))) {
										  $deletedRepresentants[$representantid] = $promoteurData;
									 }
								  }
						 }
					 }
			}
		}
		
		$successMessage = sprintf("Le processus a réussi à supprimer %d doublons des promoteurs des registres ", count($deletedRepresentants));
		
		$this->setRedirect($successMessage, "success");
		$this->redirect("admin/registres/list");
	}
	
	
	public function updateAction()
	{
		$model                = $modelRegistre = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		$modelEntreprise      = $this->getModel("entreprise");
		$modelRepresentant    = $this->getModel("representant");
		
		$modelTable           = $model->getTable();
        $dbAdapter            = $modelTable->getAdapter();
		$tablePrefix          = $prefixName = $modelTable->info("namePrefix");
		
		$registres            = $model->getList();
		$entreprises          = $errorMessages = array();
		$entreprisesUpdated   = 0;
		
		if( count(   $registres)) {
			foreach( $registres as $registre ) {
				     $registreid                                = $registre["registreid"];
				     $NumeroRCCM                                = $registre["numero"];
					 $entrepriseBYNum                           = $modelEntreprise->findRow( $NumeroRCCM, "num_rc"    , null, false);
					 $entrepriseById                            = $modelEntreprise->findRow( $registreid, "registreid", null, false);
				     if(!$entrepriseBYNum || !$entrepriseById ) {
						 $entrepriseData                        = array("code"=>"","registreid"=>$registreid,"num_rc"=>$NumeroRCCM,"domaineid"=>$registre["domaineid"],"formid"=>1,"city"=>"0","responsable_email"=>"","siteweb"=> "","fax"=>"","zip"=>"","email"=>"","logo"=>"");
						 $entrepriseData["libelle"]             = $registre["libelle"];
						 $entrepriseData["presentation"]        = $registre["description"];
						 $entrepriseData["num_securite_social"] = $registre["numcnss"];
						 $entrepriseData["num_ifu"]             = $registre["numifu"];
						 $entrepriseData["capital"]             = $registre["capital"];
						 $entrepriseData["chiffre_affaire"]     = 0;
						 $entrepriseData["nbemployes_min"]      = $entrepriseData["nbemployes_max"] = 0;
						 $entrepriseData["groupid"]             = 0;
						 $entrepriseData["pagekey"]             = $entrepriseData["reference"]      = $NumeroRCCM;
						 $entrepriseData["datecreation"]        = $registre["date"];
						 $entrepriseData["creationdate"]        = $registre["creationdate"];
						 $entrepriseData["creatorid"]           = $registre["creatorid"];
						 $entrepriseData["updateduserid"]       = $entrepriseData["updatedate"]     = 0;
						 $entrepriseData["responsable"]         = sprintf("%s %s", $registre["nom"], $registre["prenom"]);
						 $entrepriseData["responsableid"]       = $registre["representantid"];
						 $entrepriseData["address"]             = $registre["adresse"];
						 $entrepriseData["phone1"]              = $registre["telephone"];
						 $entrepriseData["country"]             = $registre["country"];
						 $entrepriseData["email"]               = $registre["email"];
						 try {
							if( $dbAdapter->insert( $tablePrefix."rccm_registre_entreprises", $entrepriseData) ) {
								$entreprises[]                  = $entrepriseData;
								$entreprisesUpdated++;
							}
						 } catch( Exception $e ) {
							$errorMessages[]                    = sprintf("Une erreur s'est produite pendant l'insertion des informations : %s", $e->getMessage());
						 }						 						 
					 }
				
			}			
		}
		if( empty( $errorMessages ) ) {
			$successMessage            = sprintf("Cette opération s'est effectuée avec succès. Au total, %d entreprises ont été mises à jour avec succès", $entreprisesUpdated);
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				$jsonArray             = array();
				$jsonArray["success"]  = $successMessage;
 
				echo ZendX_JQuery::encodeJson( $jsonArray );
				exit;
			}
			$this->setRedirect($successMessage , "success");
			
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
					 $this->getHelper("Message")->addMessage($errorMessage , "error");
			}
		}
		$this->redirect("admin/registres/list");
	}
	
	public function updateapiAction()
	{
		$model                = $modelRegistre = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		$modelEntreprise      = $this->getModel("entreprise");
		$modelRepresentant    = $this->getModel("representant");
		
		$modelTable           = $model->getTable();
        $dbAdapter            = $modelTable->getAdapter();
		
		$registres            = $model->getList(array("creatorid"=>26));
		$entreprises          = $errorMessages = array();
		$entreprisesUpdated   = 0;
		$api_rccm_data        = array("code" =>"","numero"=>"","entrepriseid"=>0,"country"=>"BF","localite"=>"","localiteid"=>0,"annee"=>0,"ifuid"=>0,"cnssid"=>0,"numifu"=>"","numcnss"=>"","libelle"=>"","description"=>"","statut"=>0,"domaineid"=>0,
		                              "catid"=>0 ,"nbactions"=>0,"capital"=>0,"valid"=>1,"transfered"=>0,"parentid"=>0,"numparent"=>"","valid"=>0,"transfered"=>0,"creationdate"=>0,"creatorid"=>1,"updatedate"=>0,"updateduserid"=>0);
		
		$api_entreprise_data  = array("code"=>"","registreid"=>"","domaineid"=>0,"formid"=>0,"num_rccm"=>"","num_ifu"=>"","num_cnss"=>"","libelle"=>"","denomination"=>"","sigle"=>"","address"=>"","country"=>"BF","city"=>"","datecreation"=>"","datefermeture"=>"","nbemployes"=>"","capital"=>0,"chiffre_affaire"=>0,"telephone"=>"","email"=>"","siteweb"=>"", "creationdate"=>0,"creatorid"=>1,"updatedate"=>0,"updateduserid"=>0);
		$api_representant_data= array("registreid"=>0,"entrepriseid"=>0,"num_identite"=>"","nomcomplet"=>"","nom"=>"","prenom"=>"","profession"=>"","address"=>"","nationalite"=>"","telephone"=>"","email"=>"","sexe"=>"","lieunaissance"=>"","datenaissance"=>"","creationdate"=>"","creatorid"=>0,"updatedate"=>0,"");
		$sigueApiDbParams     = array("host" =>"localhost","username"=>"useradmin","password"=>"mebfSir@h1217","dbname"=>"sigueapi","isDefaultAdapter"=>0);
		try {
			$dbApi            = Zend_Db::factory("Pdo_Mysql", $sigueApiDbParams);		 
		} catch(Exception $e ) {
			$errorMessages[]  = sprintf("Erreur de connexion à la base de données : ".$e->getMessage()); 
			printf("Erreur de connexion à la base de données : ".$e->getMessage()); die();
		}
		if( count(   $registres)) {
			foreach( $registres      as $registre ) {
				     $registreid      = $registre["registreid"];
				     $NumeroRCCM      = $registre["numero"];
					 $entrepriseRow   = $modelEntreprise->findRow();
				
			}			
		}
	}
	
    public function viewpdfAction()
	{
	  $this->_helper->layout->disableLayout(true);
	  $this->_helper->viewRenderer->setNoRender(true);
	  $logger    = new Logger('MyLogger');
      $pdfToText = XPDF\PdfToText::create(array("pdftotext.binaries" =>"F:\webserver\www\binaries\Xpdf\pdftotext.exe","pdftotext.timeout"=> 30,), $logger);
	  
	 /* $countries = $this->view->countries();
	  $needle    = "Mali";
	  $found     = Sirah_Functions_ArrayHelper::search($countries,$needle);
	  print_r(key($found));die();*/
	 
	  for( $i=1; $i<= 50; $i++ ) {
		   $pdfFile  = sprintf("G:\BFRCCM/PDFLOTS/OUA/2010/BFOUA2010A%04d-PS.pdf", $i );
		   if( file_exists( $pdfFile )) {
			   $text   = preg_replace("/(?<=:)(\s*)/","",$pdfToText->getText($pdfFile,1,2));
			   $regex  = "/(?<=Mlle)(.*)(?=Prénoms)(.*)/";//Nom et prénom
			   $regex2 = '/(?<=LIEU de NAISSANCE:)(.*)(?: A)(.*)(?:NATIONALITE:)(.*)/';$regex10= '/(?<=NAISSANCE:)(.*)(?:à)(.*)(?:NATIONALITE.*\s*n*\s*:)(.*)/';
			   $regex3 = "/(?<=NOM COMMERCIAL:)(.*)/";//Nom commercial
			   $regex4 = "/(?<= postale|postal|postat|poslal\):)(.*)(?=Tel|Tél|Té)/";//Adresse
			   $regex5 = "/(?<=\(préciser\)):*(.*)/";//Activité Principale 1
			   $regex6 = "/(?<=Le)\s*(.*)\s*(?=Signature)/";//Date d'inscription 1
			   $regex7 = "/(?<=de début:)(.+)(?=N\"RCCM:)/";//Date d'inscription 2
			   $regex8 = "/(?<=Principale:)(.*)/";//Activité Principale 2
			   $regex9 = "/(?<=MATRIMONIALE:)(.*)/";//Situation matrimoniale
			   $regex11= "/(?<=Tél:)\s*(.*)\/*\s*(?=Secteur)/";
			   preg_match($regex4, $text, $textData );
			   print_r($textData);
			   printf("<p> Document %d : %s </p> <br/>", $i, $text);
		   }
	  }
	}
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "ERCCM : Les registres de commerce"  ;
		
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		
		$registres          = array();
		$paginator          = null;
		$me                 = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"domaineid"=>0,"creatorid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,"date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"type"=>0,"keywords"=>null,
		                              "searchfrom"=>"erccm","periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,"periode_end_day" =>DEFAULT_END_DAY ,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if(isset($filters["name"] )) {
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
        if( $me->isOPS() ) {
			$filters["creatorid"] = $me->userid;
		}
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate               = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]        = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
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

		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ, 3, $pageNum , $pageSize );
		    $paginator                = $model->getSimilarListPaginator($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]  = $similarSearchQ;
		} else {
			$registres                = $model->getList( $filters , $pageNum , $pageSize);
		    $paginator                = $model->getListPaginator($filters);
		}				
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Secteur d'activité" , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites        = $modelLocalite->getSelectListe("Localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types            = array(0 => "Type",1 => "Physiques",2=> "Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;			
	}
	
	public function exportAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
				
		$model                = $this->getModel("registre");
		
		$registres            = $errorMessages = array();
		$me                   = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"domaineid"=>0,"creatorid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,
		                              "date_year"=>null,"date_month"=> null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"keywords"=>null,
				                      "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=> DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,
				                      "periode_end_day"=>DEFAULT_END_DAY ,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if(isset($filters["name"] )) {
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
        if( $me->isOPS() ) {
			$filters["localiteid"]    = $me->city;
		}
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate                  = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]           = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_month"]))
				&&
			(isset($filters["periode_end_day"])   && intval($filters["periode_end_day"] ))  && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$registres                    = $model->getList( $filters , $pageNum , $pageSize);
		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]   = $similarSearchQ;
		}  
		$registreids                  = (isset($params["registreids"]))? $params["registreids"]: array();
        if( count( $registreids ) ) {
			$registres                = $model->getList(array("registreids"=> $registreids ));
		}			
		if( count(   $registres ) ) {			
			$myStoreDataPath          = $me->getDatapath(); 
            if( !is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(   $myStoreDataPath , 0777);
				@mkdir(  $myStoreDataPath . DS . "ARCHIVES");
			}				
			$rccmDocumentDest         = $myStoreDataPath . DS . "ARCHIVES" .DS.  "ERCCM_ListDocuments.pdf" ;
			if( file_exists( $rccmDocumentDest )) {
				@unlink($rccmDocumentDest);
			}
			$combinedFilesPDF         = new Fpdi\Fpdi();
			foreach( $registres as $registre ) {
					 $registreid      = $registre["registreid"];
					 $NumeroRCCM      = $registre["numero"];
					 $NumeroLOCALITE  = trim(substr($NumeroRCCM, 2, 3));
					 $NumeroYEAR      = trim(substr($NumeroRCCM, 5, 4));
					 $NumeroTYPE      = strtoupper(trim(substr($NumeroRCCM, 9, 1)));
					 $typeRCCM        = "PHYSIQUES";
					 if( $NumeroTYPE=="B") {
						 $typeRCCM    = "MORALES";
					 } elseif($NumeroTYPE=="M") {
						 $typeRCCM    = "MODIFICATIONS";
					 } elseif($NumeroTYPE=="S")  {
						 $typeRCCM    = "SURETES";
					 } else {
						 $typeRCCM    = "PHYSIQUES";
					 }
					 $rccmFilesFolder = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS .$typeRCCM. DS . $NumeroLOCALITE . DS . $NumeroYEAR. DS . $NumeroRCCM;
					 $rccmFiles       = glob( $rccmFilesFolder."/*PS.pdf");
					 if( count(   $rccmFiles )) {
						 foreach( $rccmFiles as $rccmFile ) {
							     try {
									  $rccmFilePages  = $combinedFilesPDF->setSourceFile($rccmFile);
									  for ( $j =1;  $j <= $rccmFilePages; $j++) {
											$combinedTplIdx  = $combinedFilesPDF->importPage($j);										
											$combinedPDFSize = $combinedFilesPDF->getTemplateSize($combinedTplIdx);
											$combinedFilesPDF->AddPage( $combinedPDFSize['orientation'], $combinedPDFSize);
											$combinedFilesPDF->useTemplate($combinedTplIdx);
									  }
								 } catch( Exception $e ) {
									 $errorMessages[]        = sprintf("Une erreur s'est produite dans l'ouverture du RCCM N° %s : %s", $NumeroRCCM, $e->getMessage());
								 }
						 }
					 } else {
						 $errorMessages[] = sprintf("Le RCCM N° %s ne contient aucun dossier valide",$NumeroRCCM);
					 }
			}			
		} else {
			             $errorMessages[] =  "Aucun RCCM de ce type n'a été trouvé dans la base de données";			 
		}	
        if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}	else {
			$combinedFilesPDF->Output("F", $rccmDocumentDest);
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"=>"Le document des RCCM selectionnés a été produit avec succès","tmpDocument"=>$rccmDocumentDest));
				exit;
			}			
			exit;
		}					
	}
	
	public function exportzipAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
				
		$model                = $this->getModel("registre");
		
		$registres            = $errorMessages = array();
		$me                   = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"domaineid"=>0,"creatorid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,
		                              "date_year"=> null,"date_month"=> null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"keywords"=>null,
				                      "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=> DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,
				                      "periode_end_day"=>DEFAULT_END_DAY ,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if(isset($filters["name"] )) {
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
        if( $me->isOPS() ) {
			$filters["localiteid"]   = $me->city;
		}
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate                  = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]           = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_month"]))
				&&
			(isset($filters["periode_end_day"])   && intval($filters["periode_end_day"] ))  && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$registres                    = $model->getList( $filters , $pageNum , $pageSize);
		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]   = $similarSearchQ;
		}  
		$registreids                  = (isset($params["registreids"]))? $params["registreids"]: array();
        if( count( $registreids ) ) {
			$registres                = $model->getList(array("registreids"=> $registreids ));
		}			
		if( count(   $registres ) ) {			
			$myStoreDataPath          = $me->getDatapath(); 
            if( !is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(   $myStoreDataPath , 0777);
				@mkdir(  $myStoreDataPath . DS . "ARCHIVES");
			}				
			$rccmDocumentDest             = $zipRCCMFilename = $myStoreDataPath . DS . "ARCHIVES" .DS.  "ERCCM_ListDocuments.zip" ;
			if( file_exists($rccmDocumentDest )) {
				@unlink($rccmDocumentDest);
			}
			try {
				$zipRCCMs                 = new ZipArchive();
				if( $zipRCCMs->open($zipRCCMFilename ,ZipArchive::CREATE|ZipArchive::OVERWRITE) == TRUE) {
					foreach( $registres as $registre ) {
						 $registreid      = $registre["registreid"];
						 $NumeroRCCM      = $registre["numero"];
						 $NumeroLOCALITE  = trim(substr($NumeroRCCM, 2, 3));
						 $NumeroYEAR      = trim(substr($NumeroRCCM, 5, 4));
						 $NumeroTYPE      = strtoupper(trim(substr($NumeroRCCM, 9, 1)));
						 $typeRCCM        = "PHYSIQUES";
						 if( $NumeroTYPE=="B") {
							 $typeRCCM    = "MORALES";
						 } elseif($NumeroTYPE=="M") {
							 $typeRCCM    = "MODIFICATIONS";
						 } elseif($NumeroTYPE=="S")  {
							 $typeRCCM    = "SURETES";
						 } else {
							 $typeRCCM    = "PHYSIQUES";
						 }
						 $rccmFilesFolder = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS .$typeRCCM. DS . $NumeroLOCALITE . DS . $NumeroYEAR. DS . $NumeroRCCM;
						 $rccmFiles       = glob( $rccmFilesFolder."/*PS.pdf");
						 if( count(   $rccmFiles )) {
							 foreach( $rccmFiles as $rccmFile ) {
								      $rccmFileName   = str_replace("-PS","",basename($rccmFile));
									  $zipRCCMs->addFile( $rccmFile, $rccmFileName);
							 }
						 } else {
							 $errorMessages[]     = sprintf("Le fond de dossier du RCCM N° %s n'a pas été trouvé", $NumeroRCCM);
						 }
				    }
					$zipRCCMs->close();
				}	else {
					         $errorMessages[]     = "Impossible de créer l'archive des RCCM";
				}					
			} catch ( Exception $e ) {
			}		 
		} else {
			                  $errorMessages[]     = "Aucun RCCM valide n'a été sélectionné";
		}
        if( empty( $errorMessages ) ) {
			header("Content-type: application/zip"); 
			header("Content-Disposition: attachment; filename=".basename($zipRCCMFilename).""); 
			header("Pragma: no-cache"); 
			header("Expires: 0"); 
			readfile($zipRCCMFilename);
			exit;
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
			$this->redirect("admin/registres/list");
		}			
	}
	
	
	public function exportcsvAction()
	{
		@ini_set('memory_limit', '-1');
		$model                = $this->getModel("registre");
		$modelLocalite        = $this->getModel("localite");
		$modelDomaine         = $this->getModel("domaine");
		
		$registres            = array();
		$paginator            = null;
		$me                   = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
 
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10000;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"domaineid"=>0,"creatorid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,
		                              "date_year"=> null,"date_month"=> null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"keywords"=>null,
				                      "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=> DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,
				                      "periode_end_day" =>DEFAULT_END_DAY,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
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
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate                  = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]           = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_month"]))
				&&
			(isset($filters["periode_end_day"])   && intval($filters["periode_end_day"] ))  && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}	
 
		$registres                    = $model->getList( $filters , $pageNum , $pageSize);
		if( $findSimilar && !empty($similarSearchQ) ) {
			$registres                = $model->getSimilarList($similarSearchQ);
			$filters["searchQ"]       = $params["searchQ"]   = $similarSearchQ;
		}  
		$registreids                  = (isset($params["registreids"]))? $params["registreids"]: array();
        if( count( $registreids ) ) {
			$registres                = $model->getList(array("registreids"=> $registreids ));
		}			
		if( count(   $registres ) ) {			
		    $nationalites             = $this->view->nationalites();
		    $csvRows                  = array();
			$myStoreDataPath          = $me->getDatapath(); 
            if( !is_dir( $myStoreDataPath . DS . "ARCHIVES") ) {
				chmod(   $myStoreDataPath , 0777);
				@mkdir(  $myStoreDataPath . DS . "ARCHIVES");
			}				
			$rccmDocumentDest         = $myStoreDataPath . DS . "ARCHIVES" .DS.  "ERCCM_ListDocuments.csv" ;
			if( file_exists( $rccmDocumentDest )) {
				@unlink($rccmDocumentDest);
			}
			foreach( $registres as $registre ) {
				     $registreid                          = $registre["registreid"];
					 $NumeroRCCM                          = $registre["numero"];
				     $NumeroLOCALITE                      = trim(substr($NumeroRCCM, 2, 3));
				     $NumeroYEAR                          = trim(substr($NumeroRCCM, 5, 4));
				     $NumeroTYPE                          = strtoupper(trim(substr($NumeroRCCM, 9, 1)));
					 $registreCountry                     = strtolower($registre["country"]);
 
				     $csvRowData                          = array();
					 $csvRowData["NUMERO_RCCM"]           = $NumeroRCCM;
					 $csvRowData["NUMERO_IFU"]            = $registre["numifu"];
					 $csvRowData["NUMERO_CNSS"]           = $registre["numcnss"];
					 $csvRowData["NOM"]                   = $registre["nom"];
					 $csvRowData["PRENOM"]                = $registre["prenom"];
					 $csvRowData["ADRESSE"]               = $registre["adresse"];
					 $csvRowData["TELEPHONE"]             = $registre["telephone"];
					 $csvRowData["DATE_NAISSANCE"]        = $registre["date_naissance"];
					 $csvRowData["LIEU_NAISSANCE"]        = $registre["lieunaissance"];
					 $csvRowData["NOM_COMMERCIAL"]        = $registre["libelle"];
					 $csvRowData["ACTIVITE"]              = $registre["description"];
					 $csvRowData["SEXE"]                  = ((stripos("M", $registre["sexe"])!==false) || (stripos("H", $registre["sexe"])!==false))? "Homme" : "Femme";
					 $csvRowData["PASSPORT"]              = iconv("Windows-1252", "UTF-8",$registre["passport"]);
					 $csvRowData["NATIONALITE"]           = (isset($nationalites[$registreCountry]))?$nationalites[$registreCountry] : $registre["country"];
					 $csvRowData["DATE_ENREGISTREMENT"]   = $registre["date_registre"];
					 $csvRowData["SITUATION_MATRIMONIAL"] = "Celibataire";						
			         $csvRows[$registreid]                = $csvRowData;	     
			}
			if( count( $csvRows )) {
				$csvHeader   = array("NUMERO_RCCM","NUMERO_IFU","NUMERO_CNSS","DATE_ENREGISTREMENT","NOM_COMMERCIAL","ACTIVITE","NOM","PRENOM","LIEU_NAISSANCE","DATE_NAISSANCE","SEXE","ADRESSE","TELEPHONE","PASSPORT","NATIONALITE","SITUATION_MATRIMONIAL");
				$csvAdapter  = Sirah_Filesystem_File::fabric("Csv", array("filename"=>$rccmDocumentDest,"has_header" => true, "header" => $csvHeader ) , "wb+" );
				if( $csvAdapter->save( $csvRows ) ) {
					$this->_helper->Message->addMessage( sprintf("Votre opération de création du fichier CSV s'est produite avec succès"), "success");
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					//$this->getResponse()->setHeader("Content-Type" , "text/csv");					
					echo $csvAdapter->Output($rccmDocumentDest);
					@unlink($rccmDocumentDest);
					exit;
				} else {
				    $errorMessages[]  = " Aucun RCCM n'a pu être exporté ";
				}
			}
            if( !empty( $errorMessages ) ){
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->layout->disableLayout(true);
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $errorMessage){
						 $this->_helper->Message->addMessage($errorMessage , "error");
				}
			}
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun RCCM de ce type n'a été trouvé dans la base de données"));
				exit;
			}
			$this->setRedirect("Aucun RCCM de ce type n'a été trouvé dans la base de données", "error");
			$this->redirect("admin/registres/list");
		}		
	}
	
	
	public function editAction()
	{		
		$registreid            = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		if(!$registre) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registres/list");
		}
		$registreType        = $registre->type;
		if( $registreType== 1) {
			$this->redirect("admin/registrephysique/edit/registreid/".$registreid);
		} elseif($registreType== 2) {
			$this->redirect("admin/registremoral/edit/registreid/".$registreid);
		} elseif($registreType== 3) {
			$this->redirect("admin/registresuretes/edit/registreid/".$registreid);
		} elseif($registreType== 4) {
			$this->redirect("admin/registremodifications/edit/registreid/".$registreid);
		}
	}
		
			
	public function infosAction()
	{		
		$registreid            = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		if(!$registre) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registres/list");
		}
		$registreType        = $registre->type;
		if( $registreType== 1) {
			$this->redirect("admin/registrephysique/infos/registreid/".$registreid);
		} elseif($registreType== 2) {
			$this->redirect("admin/registremoral/infos/registreid/".$registreid);
		} elseif($registreType== 3) {
			$this->redirect("admin/registresuretes/infos/registreid/".$registreid);
		} elseif($registreType== 4) {
			$this->redirect("admin/registremodifications/infos/registreid/".$registreid);
		}
	} 	
	
	public function uploadAction()
	{
		$registreid           = intval($this->_getParam("registreid", $this->_getParam("id" , 0 )));
		$category             = intval($this->_getParam("category"  , $this->_getParam("categorie" , 0 )));
		$model                = $this->getModel("registre");
		if(!$registreid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("registres/list");
		}
		$registre            = $model->findRow( $registreid , "registreid" , null , false );
		if(!$registre) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("registres/list");
		}
		$me                     = $user = Sirah_Fabric::getUser();
		$modelDocument          = $this->getModel("document");
		$modelCategory          = $this->getModel("documentcategorie");
		$defaultData            = $modelDocument->getEmptyData();
		$userDataPath           = APPLICATION_DATA_PATH . DS . "registres". DS;
		$errorMessages          = array();
		$uploadedFiles          = array();
		$categories             = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
		$numero                 = $registre->numero;
		$dateYear               = date("Y", $registre->date);
		$localite               = $registre->findParentRow("Table_Localites");
		$localiteCode           = ( $localite ) ? $localite->code : "OUA";
		$type                   = ( $registre->type == 1 ) ? "PHYSIQUES" : "MORALES";
		$rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode . DS . $dateYear. DS . $numero;
	
		if( $this->_request->isPost() ) {
			$postData           = $this->_request->getPost();
			$formData           = array_intersect_key( $postData ,  $defaultData )	;
			$documentData       = array_merge( $defaultData ,  $formData );
			$userTable          = $me->getTable();
			$dbAdapter          = $userTable->getAdapter();
			$prefixName         = $userTable->info("namePrefix");
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS .$type. DS . $localiteCode . DS . $dateYear) ) {
				if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type)) {
					@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
					@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type);
					@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type, 0777 );
				}
				if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode)) {
					@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode);
					@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode, 0777 );
				}
					@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode. DS . $dateYear);
					@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . $type. DS . $localiteCode. DS . $dateYear, 0777 );									   
			}
			if(!is_dir($rcPathroot)) {
				@chmod($rcPathroot, 0777 );
				@mkdir($rcPathroot);
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			//On crée un validateur de filtre
			$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
			$documentData                   = array();
			$documentData["userid"]         = $me->userid;
			$documentData["category"]       = 1;
			$documentData["resource"]       = "registres";
			$documentData["resourceid"]     = 0;
			$documentData["filedescription"]= $registre->description;
			$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
			$documentData["creationdate"]   = time();
			$documentData["creatoruserid"]  = $me->userid;
	
			$documentsUploadAdapter         = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Count"    , false , 1 );
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf","png","gif","jpg","docx","doc","xml"));
			$documentsUploadAdapter->addValidator("Size"     , false , array("max"=> DEFAULT_UPLOAD_MAXSIZE));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max"=> DEFAULT_UPLOAD_MAXSIZE));
			$searchIvalidStr                = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
			$replace                        = array ('e','a','i','u','o','n','y','c','-','','-');					  	  	  
			$registreDocument               = preg_replace( $searchIvalidStr, $replace, $documentsUploadAdapter->getFileName("registredoc", false ));
			$registreDocPath                = $rcPathroot. DS .$registreDocument;
			$documentsUploadAdapter->addFilter("Rename", array("target" => $registreDocPath , "overwrite" => true), "registredoc");	
			
			if(!$documentsUploadAdapter->isUploaded("registredoc") ) {
				$errorMessages[]            = "Le document n'a pas été transféré";
			} else {
				$documentsUploadAdapter->receive("registredoc");
				if( $documentsUploadAdapter->isReceived("registredoc") ) {					
					$docExtension                     = Sirah_Filesystem::getFilextension( $registreDocument );
					$docFilename                      = Sirah_Filesystem::getName( $registreDocument );
					$myFilename                        = (isset( $postData["filename"]) && $strNotEmptyValidator->isValid($postData["filename"] ) ) ? $stringFilter->filter( $postData["filename"] ) :$docFilename;
					$docFileSize                      = $documentsUploadAdapter->getFileSize("registredoc");
					$registreDocData                  = $documentData;
					$registreDocData["filename"]      = $modelDocument->rename(strtoupper($myFilename),$numero );
					$registreDocData["filepath"]      = $registreDocPath;
					$registreDocData["filextension"]  = $docExtension;
					$registreDocData["filesize"]      = floatval( $docFileSize );
					$registreDocData["access"]        = 6;
					if( $dbAdapter->insert( $prefixName . "system_users_documents", $registreDocData) ) {
					  	$documentid                   = $dbAdapter->lastInsertId();
					  	$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid"=> $registreid,"documentid" => $documentid,"access" => 6));
					} else {
					  	$errorMessages[]              = "Les informations du document ont été partiellement enregistrées";
					}					  	  	  	   	
				} else {
					  	$errorMessages[]              = "Le document n'a pas été reçu par le serveur";
				}
			}			 
			if( empty( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonArray             = array();
					$jsonArray["success"]  = "Le document a été enregistré avec succès";
					$jsonArray["document"] = $documentData ;
					echo ZendX_JQuery::encodeJson( $jsonArray );
					exit;
				}
				$this->_helper->Message->addMessage("Le document a été enregistré avec succès" , "success");
			} else {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
					exit;
				}
				foreach( $errorMessages as $errorMessage){
					     $this->getHelper("Message")->addMessage($errorMessage , "error");
				}
			}
		}
		$this->view->registreid = $registreid;
		$this->view->registre   = $registre;
		$this->view->categories = $categories;
		$this->view->data       = $defaultData;
		$this->view->category   = $category;
	}
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("registre");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$registre                 = $model->findRow( $id , "registreid" , null , false );
				if( $registre  ) {
					if(!$registre->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée : Le registre id#$id n'a pas été supprimé";
					} else {
						$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$id);						
						$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
						$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$id);
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le registre #$id ";
				}
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/registres/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registres/list");
		}			
	}
	
	
	public function finderrorsAction()
	{
		@ini_set('memory_limit', '1024M');
		$this->view->title  = "Retrouver des erreurs dans les RCCM indexés";
		
		$me                 = Sirah_Fabric::getUser();
		$model              = $this->getModel("registre");
		$modelDocument      = $this->getModel("document");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelLocalite      = $this->getModel("localite");
		
		$opsUsers           = array("Sélectionnez un OPS");
		$users              = Sirah_User_Table::getUsers(array("rolename"=>"OPS"));
		$localites          = $modelLocalite->getSelectListe("Sélectionnez une localité", array("code", "libelle") , array() , null , null , false );
		$localiteIDS        = $modelLocalite->getSelectListe("Sélectionnez une localité", array("code", "localiteid") , array() , null , null , false );
		$annees             = array(0=> "Sélectionnez une année",
		                            "2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018","2019"=>"2019");
		$countries          = $this->view->countries();
		$defaultData        = array("ops_userid"=>0,"localite"=>0,"annee"=>0,"type"=>0,"check_documents"=>0,"srcpath"=>"G:\\ERCCM","errorspath"=>"G:\\RETRAITEMENTS_INVALIDES","maxitems"=>2000,
		                            "check_documents"=>0,"periode_start_year"=>DEFAULT_START_YEAR,"periode_start_month"=>DEFAULT_START_MONTH,
		                            "periode_start_day"=>DEFAULT_START_DAY,"periode_end_year"=>DEFAULT_END_YEAR,"periode_end_day"=>DEFAULT_END_DAY,"periode_end_month"=> DEFAULT_END_MONTH);
		$opsDataErrors      = $csvRows = $csvRowsPhysique = $csvRowsMoral = $csvRowsModifications = array();
		$totalValid         = $total = 0;
	
		if( count(   $users ) ) {
			foreach( $users as $user ) {
				     $opsUsers[$user["userid"]] = $user["username"];
			}
		}		
		if( $this->_request->isPost() ) {
			$userTable      = $me->getTable();
			$dbAdapter      = $userTable->getAdapter();
			$prefixName     = $userTable->info("namePrefix");
			$postData       = $this->_request->getPost();
		    $srcPath        = (isset($postData["srcpath"]        ))? $postData["srcpath"]          : $defaultData["srcpath"];
			$errorsPath     = (isset($postData["errorspath"]     ))? $postData["errorspath"]       : $defaultData["errorspath"];
			$localite       = (isset($postData["localite"]       ))? $postData["localite"]         : $defaultData["localite"];
			$annee          = (isset($postData["annee"]          ))? intval($postData["annee"])    : $defaultData["annee"];
			$opsUserid      = (isset($postData["ops_userid"]     ))? $postData["ops_userid"]       : $defaultData["ops_userid"];
			$checkDocuments = (isset($postData["check_documents"]))? $postData["check_documents"]  : intval($defaultData["check_documents"]);
			$maxItems       = (isset($postData["maxitems"]       ))? $postData["maxitems"]         : intval($defaultData["maxitems"]);
			$opsName        = $opsUsername = (isset($opsUsers[$opsUserid]))? $opsUsers[$opsUserid] : "";
			$documentsPath  = APPLICATION_INDEXATION_STOCKAGE_FOLDER;
			$fileSource     = (is_dir( DEFAULT_FIND_DOCUMENTS_SRC))? DEFAULT_FIND_DOCUMENTS_SRC    : "C:\ERCCM/DATA";
			$numRccmKey     = "BF";
			$localiteid     = 0;
			$localiteValue  = "";
			
			if( empty( $opsName ) ) {
				$errorMessages[] = "Veuillez sélectionner un OPS valide";
			} else {
				$opsUsername     = strtoupper(preg_replace("/[^A-Za-z0-9 ]/","-", $opsName));
				$errorsPath      = $errorsPath . DS . $opsUsername;	
				 if(!is_dir($errorsPath ) ){
					@mkdir( $errorsPath );
					@chmod( $errorsPath, 0777);
				}
				$errorsPath      = $errorsPath . DS . "ERREURS";
                if(!is_dir( $errorsPath ) ){
					@mkdir( $errorsPath );
					@chmod( $errorsPath, 0777);
				}				
			}
			if(!isset(  $localites[$localite] ) || !isset($localiteIDS[$localite])) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			} else if($localite!=0) {
				$localiteid       = $localiteIDS[$localite];
				$localiteValue    = $localites[$localite];
				$srcPath          = $srcPath    . DS . $localite;
				$errorsPath       = $errorsPath . DS . $localite;
				$numRccmKey       = $numRccmKey . strtoupper($localite);
				if(!is_dir( $errorsPath ) ){
					@mkdir( $errorsPath );
					@chmod( $errorsPath, 0777);
				}
			}	
            if(!isset( $annees[$annee] )) {
				$errorMessages[]  = "Veuillez sélectionner une année valide";
			} elseif( $annee!=0) {
				$srcPath          = $srcPath    . DS . $annee;
				$errorsPath       = $errorsPath . DS . $annee;
				$numRccmKey       = $numRccmKey . intval($annee);
				if(!is_dir( $errorsPath ) ){
					@mkdir( $errorsPath );
					@chmod( $errorsPath, 0777);
				}
			}
			/*if(!is_dir( $srcPath ) && $checkDocuments ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  n'existe pas dans %s. Veuillez vérifier.",  $opsName,  $srcPath);
			}  */
			if(!is_dir( $errorsPath ) && $checkDocuments ) {
				$errorMessages[]  = sprintf( "Le dossier de rangement des erreurs de %s  n'existe pas dans %s. Veuillez vérifier.",  $opsName,$errorsPath);
			} 
			if( (isset($postData["periode_end_month"]) && intval( $postData["periode_end_month"] )) && (isset( $postData["periode_start_month"]) && intval( $postData["periode_start_month"] ))
					&&
				(isset($postData["periode_end_day"]) && intval( $postData["periode_end_day"] ))  && (isset( $postData["periode_start_day"]) && intval( $postData["periode_start_day"] ))
			)	{
				$zendPeriodeStart          = new Zend_Date(array("year" => $postData["periode_start_year"],"month"=> $postData["periode_start_month"],"day"=> $postData["periode_start_day"]  ));
				$zendPeriodeEnd            = new Zend_Date(array("year" => $postData["periode_end_year"]  ,"month"=> $postData["periode_end_month"]  ,"day"=> $postData["periode_end_day"]    ,));
				$postData["periode_start"] = ($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
				$postData["periode_end"]   = ($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
				$reportPeriodStart         = ($zendPeriodeStart)? $zendPeriodeStart->toString("d MMM Y") : "";
				$reportPeriodEnd           = ($zendPeriodeEnd  )? $zendPeriodeEnd->toString("d MMM Y")   : "";
			} else {
				$postData["periode_start"] = $postData["periode_end"] =  0;
				$reportPeriodEnd           = $reportPeriodStart       = "Non Précisé";
			}				
			if( empty( $errorMessages) ) {
				/** On récupère d'abord toutes les données comportant des erreurs dans la base de données  **/
				$userSimpleRegistres               = $model->simpleList(array("creatorid"=>$opsUserid,"annee"=>$annee,"localiteid"=>$localiteid,"periode_start"=>$postData["periode_start"],"periode_end"=>$postData["periode_end"]), 1, $maxItems);
				//print_r(count($userSimpleRegistres)); die();
				if( count(   $userSimpleRegistres ) ) {
					foreach( $userSimpleRegistres as $simpleRegistre ) {
						     $isValid              = 1;
						     $registreid           = intval($simpleRegistre["registreid"]);
							 $numRccm              = $simpleRegistre["numero"];
							 $numRccmType          = $simpleRegistre["type"];
							 $nomCommercial        = $simpleRegistre["libelle"];
							 $date_registre        = $simpleRegistre["date_registre"];
							 $rccmDescription      = Encoding::fixUTF8(preg_replace("/Â/","",$simpleRegistre["description"]));
							 $rccmAdresse          = Encoding::fixUTF8(preg_replace("/Â/","",$simpleRegistre["adresse"]));
							 $numRccmLocalite      = trim(  substr($numRccm, 2, 3));
		                     $numRccmAnnee         = intval(substr($numRccm, 5, 4));
							 $numRccmLocaliteId    = (!empty($numRccmLocalite)) ? $localiteIDS[$numRccmLocalite] : 0;
						     $simpleRegistreNumKey = strtoupper(substr(preg_replace("/\s+/", "", $numRccm ),0, 9));
							 $csvRows[$registreid] = array("numero"=>$numRccm,"nom_commercial"=>Encoding::toUTF8($nomCommercial),"date_enregistrement"=>$date_registre,
							                               "description"=>$rccmDescription,"adresse"=>$rccmAdresse,"telephone"=>$simpleRegistre["telephone"]);
							 $total++;
							 $opsDataErrors[$registreid] = (isset($opsDataErrors[$registreid]["errors"]))?$opsDataErrors[$registreid] : array("numero"=>$numRccm,"nom_commercial"=>$simpleRegistre["libelle"],"documents"=>array(),"errors" => array());
							 $errorSolution        = "Allez-y dans le menu, vous cliquez sur Gestion des registres et ensuite sur ";
							 if( $numRccmType==1 ) {
								 $errorSolution   .= " Personnes physiques";
								 $csvRowsPhysique[$registreid] = $csvRows[$registreid];
							 } elseif($numRccmType==2) {
								 $errorSolution   .= " Personnes morales";
								 $csvRowsMoral[$registreid] = $csvRows[$registreid];
							 } elseif($numRccmType==3) {
								 $errorSolution   .= " Gestion des modifications";
								 $csvRowsModifications[$registreid] = $csvRows[$registreid];
							 } elseif($numRccmType==4) {
								 $errorSolution   .= " Gestion des sûrétés";
							 } else {
								 $errorSolution   .= " Registres de commerce";
							 }
							 $errorSolution       .= sprintf(". Dans la barre de recherche, vous saisissez le numéro RCCM `%s`, puis vous cliquez recherchez. Ensuite vous selectionnez le registre retrouvé et vous cliquez sur le bouton Modifier. ", $numRccm );
							 if( $simpleRegistreNumKey != $numRccmKey && intval($annee) && intval($localiteid)) {								 
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Vous mettez le bon numéro RC";
								 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Erreur de renommage du RCCM","description"=>sprintf("Le RCCM a un numéro commençant par <b> %s </b> alors qu'il devrait commencer par <b> %s </b>. ", $simpleRegistreNumKey, $numRccmKey),"solution"=>$thisSolution);
							 }
							 if(!intval($simpleRegistre["domaineid"]) && empty($simpleRegistre["description"]) ) {
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Vous sélectionnez un domaine d'activité, puis vous saisissez dans le champ `Décrire l'activité`";
								 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Domaine d'activité non renseigné","description"=>"Le domaine d'activité de ce RCCM est vide. Veuillez indiquer au moins un secteur d'activité ","solution"=>$thisSolution);
							 }
							 if((!intval($simpleRegistre["annee"]) || ($simpleRegistre["annee"]!=$numRccmAnnee)) && ($numRccmType<=2)) {
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Dans le champ `Date d'inscription`, renseignez la bonne année.";
								 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Année RCCM invalide","description"=>sprintf("L'année <b> %d </b> que vous avez indiquée pour ce RCCM est invalide",$simpleRegistre["annee"]),"solution"=>$thisSolution);
							 }
							 if(intval($simpleRegistre["annee"]) < 1998){
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Dans le champ `Date d'inscription`, renseignez la bonne année.";
								 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Année invalide","description"=>"L'année que vous avez indiquée pour ce RCCM ne doit pas être en dessous de l'année 1998","solution"=>$thisSolution);
							 }
							 if((!intval($simpleRegistre["localiteid"]) || ($simpleRegistre["localiteid"]!=$numRccmLocaliteId)) && $numRccmType<=2 ) {
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Dans le champ `Localité`, sélectionnez la bonne localité.";
								 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Localité invalide","description"=>"La localité que vous avez indiquée pour ce RCCM est invalide", "solution"=>$thisSolution);
							 }							   
							 $dirigeants           = $model->dirigeants($registreid);
							 if( $numRccmType==2 ) {
								 $rccmEntreprise   = $model->enterprise($registreid); 
                                 if(!$rccmEntreprise) {
									 $isValid      = false;
									 $thisSolution = "Demandez à l'administrateur de la plateforme de vous assister pour corriger ce problème.";
								     $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations manquantes de l'entreprise ","description"=>"Les informations de l'entreprise associée à ce registre sont manquantes", "solution"=>$thisSolution);
								 }									 
							 }
							 if(!count($dirigeants) ) {
								 $isValid          = false;
								 $thisSolution     = $errorSolution.". Dans le champ `Nom & Prénom`, saisissez le bon nom et prénom du promoteur .";
								 $opsDataErrors[$registreid]["errors"][]         = array("title"=>"Informations du promoteur Invalides","description"=>"Aucun dirigeant n'a été associé à ce registre de commerce. Veuillez renseigner dans votre fichier Excel, les informations du promoteur et re-importez.", "solution"=>$thisSolution);								 
							 } else {								
								foreach( $dirigeants as $dirigeant ) {
									     $dirigeantName                          = sprintf("%s %s", $dirigeant["nom"],$dirigeant["prenom"]);
										 $dirigeantPassport                      = Sirah_Functions_String::cleanUtf8($dirigeant["passport"]);
										 $dirigeantSexe                          = $dirigeant["sexe"];
										 $dirigeantCountry                       = Encoding::fixUTF8(preg_replace("/Â/","",$dirigeant["country"]));
										 $dirigeantBithdayDay                    = $dirigeant["date_naissance_day"];
										 $dirigeantBithdayMonth                  = $dirigeant["date_naissance_month"];
										 $dirigeantBithdayYear                   = $dirigeant["date_naissance_year"];
										 
										 $csvRows[$registreid]["nom"]            = (isset($dirigeant["nom"]   ))?Encoding::fixUTF8(preg_replace("/Â/","",$dirigeant["nom"])): "";
										 $csvRows[$registreid]["prenom"]         = (isset($dirigeant["prenom"]))?Encoding::fixUTF8(preg_replace("/Â/","",$dirigeant["prenom"])) : "";
										 $csvRows[$registreid]["lieu_naissance"] = (isset($dirigeant["lieunaissance"]))?$dirigeant["lieunaissance"] : "";
										 $csvRows[$registreid]["date_naissance"] = (isset($dirigeant["datenaissance"]))?sprintf("%s/%s/%s",$dirigeantBithdayDay,$dirigeantBithdayMonth,$dirigeantBithdayYear): "";
										 $csvRows[$registreid]["sexe"]           = $dirigeantSexe;		
                                         $csvRows[$registreid]["nationalite"]    = $dirigeantCountry;
                                         $csvRows[$registreid]["passport"]       = $dirigeantPassport;	
                                         $csvRows[$registreid]["telephone"]      = $dirigeant["telephone"];
                                         $csvRows[$registreid]["adresse"]        = Sirah_Functions_String::cleanUtf8($dirigeant["adresse"]);											 
                                         $csvRows[$registreid]["situation_matrimonial"] = Sirah_Functions_String::cleanUtf8($dirigeant["marital_status"]);											 
										 if( empty( $dirigeantName ) ) {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `Nom & Prénom`, saisissez le bon nom et prénom du promoteur .";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur Invalides","description"=>"Le promoteur correspondant au RCCM n'a pas un nom valide", "solution"=>$thisSolution);
										 }
										 /*if( empty( $dirigeantPassport ) ) {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `N° passport ou N°CNIB`, saisissez le bon numéro passport ou CNIB du promoteur .";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur Invalides","description"=>"Le promoteur correspondant au RCCM n'a pas un numéro PASSPORT/CNIB valide", "solution"=>$thisSolution);
										 }*/
										 if( $dirigeantSexe!= "F" && $dirigeantSexe!= "M" && $dirigeantSexe!= "H" && $dirigeantSexe!= "Hommes" && $dirigeantSexe!= "Femmes") {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `Sexe`, saisissez la bonne valeur.";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur Invalides","description"=>"L'information sur le sexe du promoteur n'est pas valide. Veuillez indiquer F ou M", "solution"=>$thisSolution);
										 }
										 if(!isset($countries[$dirigeantCountry] ) && $numRccmType==1) {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `Nationalité`, sélectionnez le bon pays.";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur Invalides","description"=>"La nationalité du promoteur n'est pas valide", "solution"=>$thisSolution);
										 }
										 $maximumBirthdayYear   = date("Y") - 5;
										 if( $dirigeantBithdayDay <1 || $dirigeantBithdayDay>31 || $dirigeantBithdayMonth<1 || $dirigeantBithdayMonth>12 || $dirigeantBithdayYear<1850 || $dirigeantBithdayYear>$maximumBirthdayYear) {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `Date & lieu de naissance`, saisissez la bonne date de naissance .";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur Invalides","description"=>"La date de naissance du promoteur n'est pas valide","solution"=>$thisSolution);
										 }
                                         if( empty($dirigeant["lieunaissance"])) {
											 $isValid           = false;
											 $thisSolution      = $errorSolution.". Dans le champ `Date & lieu de naissance`, saisissez le lieu de naissance .";
											 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Informations du promoteur invalides","description"=>"Le lieu de naissance du promoteur n'est pas valide","solution"=>$thisSolution);
										 }											 
								}
							 }
							 $rccmCheckingInfos = array("numero"=>$numRccm,"registreid"=>$registreid);
							 if($numRccmType==1) {
								$rccmStorePath = $documentsPath.DS."PHYSIQUES". DS .$numRccmLocalite.DS.$numRccmAnnee.DS.$numRccm;						
							 } elseif( $numRccmType==2) {
								$rccmStorePath = $documentsPath.DS."MORALES"  . DS .$numRccmLocalite.DS.$numRccmAnnee.DS.$numRccm;
							 } elseif( $numRccmType==3) {
								$rccmStorePath = $documentsPath.DS."MODIFICATIONS".DS.$numRccmLocalite.DS.$numRccmAnnee. DS . $numRccm;
							 } elseif( $numRccmType==4) {
								$rccmStorePath = $documentsPath.DS."SURETES".DS.$numRccmLocalite.DS.$numRccmAnnee.DS.$numRccm;
							 }
							 
							 $rccmCheckingInfos["formulaire"] = $opsDataErrors[$registreid]["documents"]["formulaire"] = $rccmStorePath.DS. sprintf("%s-FR.pdf", $numRccm);	 
							 $rccmCheckingInfos["personnel"]  = $opsDataErrors[$registreid]["documents"]["personnel"]  = $rccmStorePath.DS. sprintf("%s-PS.pdf", $numRccm);
							 $rccmCheckingInfos["statut"]     = $opsDataErrors[$registreid]["documents"]["statut"]     = $rccmStorePath.DS. sprintf("%s-ST.pdf", $numRccm);
							 if( $numRccmType==2 && !file_exists($rccmCheckingInfos["statut"]) ) {
								 $statuteFile                 = $fileSource. DS .$numRccmLocalite.DS.$numRccmAnnee.DS.$numRccm. DS. sprintf("%s-ST.pdf", $numRccm);
                                 if( file_exists( $statuteFile )) {
									 @copy($statuteFile, $rccmCheckingInfos["statut"]);
								 }									 
							 }
							 if( $checkDocuments ) {
								 /*$documents           = $model->documents($registreid);*/
								 $registreNbreDocuments = count(glob($rccmStorePath.DS."*.pdf"));
								 if( $numRccmType==2 && $registreNbreDocuments!=3) {
									 $isValid           = false;
									 $thisSolution      = $errorSolution.". Dans le champ `Indexation automatique`, indiquez Non et vous renseignez manuellement les bons nouveaux documents .";
									 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Documents Invalides","description"=>sprintf("Ce RCCM devrait contenir 3 types de documents (Formulaire+STATUT+FOND_DE_DOSSIERS) alors qu'il en contient %d", $registreNbreDocuments), "solution"=>$thisSolution);
								 } elseif($numRccmType==1 && $registreNbreDocuments!=2) {
									 $isValid           = false;
									 $thisSolution      = $errorSolution.". Dans le champ `Indexation automatique`, indiquez Non et vous renseignez manuellement les bons nouveaux documents .";
									 $opsDataErrors[$registreid]["errors"][] = array("title"=>"Documents Invalides","description"=>sprintf("Ce RCCM devrait contenir 2 types de documents (Formulaire+FOND_DE_DOSSIERS) alors qu'il en contient %d", $registreNbreDocuments), "solution"=>$thisSolution);
								 } else {									
									foreach( $documents as $document ) {
										     $documentId          = $document["documentid"];									     
										     $updateDocumentData  = array("updateduserid"=>$updatedUserid,"updatedate"=>time());
										     if( stripos($document["filename"],"FORMULAIRE")!== false && ($numRccmType==2 || $numRccmType==1)) {
												 $updateDocumentData["category"] = 1;
												 $updateDocumentData["access"]   = 0;
												 $updateDocumentData["filename"] = $modelDocument->rename("FORMULAIRE", $numRccm);
												 $updateDocumentData["filepath"] = (file_exists($rccmCheckingInfos["formulaire"]))?$rccmCheckingInfos["formulaire"] : $document["filepath"];
											 } elseif(stripos($document["filename"],"STATUT")!== false && ($numRccmType==2 || $numRccmType==1)) {
												 $updateDocumentData["category"] = 2;
												 $updateDocumentData["access"]   = 6;
												 $updateDocumentData["filename"] = $modelDocument->rename("ACTES ET STATUTS JURIDIQUES", $numRccm);
												 $updateDocumentData["filepath"] = (file_exists($rccmCheckingInfos["statut"]))?$rccmCheckingInfos["statut"]: $document["filepath"];
											 } elseif(stripos($document["filename"],"PERSONNEL")!== false && ($numRccmType==2 || $numRccmType==1)) {
												 $updateDocumentData["category"] = 4;
												 $updateDocumentData["access"]   = 6;
												 $updateDocumentData["filepath"] = (file_exists($rccmCheckingInfos["personnel"]))?$rccmCheckingInfos["personnel"] : $document["filepath"];
												 $updateDocumentData["filename"] = $modelDocument->rename("DOSSIER COMPLET", $numRccm);
											 }
											 $dbAdapter->update( $prefixName. "system_users_documents", $updateDocumentData, array("documentid=".$documentId));
											 /*
										     if( empty($document["filepath"]) ) {
												 $isValid      = false;
									             $thisSolution = $errorSolution.". Dans le champ `Indexation automatique`, indiquez Non et vous renseignez manuellement les bons nouveaux documents .";
									             $opsDataErrors[$registreid]["errors"][] = array("title"=>"Documents Invalides","description"=>sprintf("Ce RCCM devrait contenir 2 types de documents (Formulaire+FOND_DE_DOSSIERS) alors qu'il en contient %d", $registreNbreDocuments), "solution"=>$thisSolution);
											 }*/
									}							
                                    $checkDocumentsErrors            = array();
                                    if( false===$this->__checkRccmFiles($rccmCheckingInfos, $checkDocumentsErrors ) ) {
										$isValid  = false;
										$checkDocumentsErrorStr      = ( count($checkDocumentsErrors) ) ? implode(", ", $checkDocumentsErrors) : "";
										$thisSolution = $errorSolution.". Dans le champ `Indexation automatique`, indiquez Non et vous renseignez manuellement les bons nouveaux documents .";
									    $opsDataErrors[$registreid]["errors"][] = array("title"=>"Retraitements Invalides","description"=>sprintf("Erreurs de retraitements : %s", $checkDocumentsErrorStr), "solution"=>$thisSolution);
									}										
								 }
							 }							 
							 if( $isValid ) {
								 $totalValid++;
								 if( isset($csvRows[$registreid])) {
									 unset($csvRows[$registreid]);
								 }
                                 if( isset($csvRowsPhysique[$registreid])) {
									 unset($csvRowsPhysique[$registreid]);
								 }
                                 if( isset($csvRowsMoral[$registreid])) {
									 unset($csvRowsMoral[$registreid]);
								 }	
                                 if( isset($csvRowsModifications[$registreid])) {
									 unset($csvRowsModifications[$registreid]);
								 }								 
							 } else {
								 if( isset($csvRowsPhysique[$registreid])) {
									 $csvRowsPhysique[$registreid]      = $csvRows[$registreid];
								 }
                                 if( isset($csvRowsMoral[$registreid])) {
									 $csvRowsMoral[$registreid]         = $csvRows[$registreid];
								 }	
                                 if( isset($csvRowsModifications[$registreid])) {
									 $csvRowsModifications[$registreid] = $csvRows[$registreid];
								 }								 
							 }
					}
				}				
			}
			if( empty( $errorMessages ) ) {
				$this->_helper->layout->disableLayout(true);
				$this->_helper->viewRenderer->setNoRender(true);
				
				$opsFilenameStr   = preg_replace("/\s/","-", $opsUsername);
				$myStoreDataPath  = (is_dir($errorsPath))?$errorsPath.DS : $me->getDatapath(); 
				if(!is_dir( $myStoreDataPath . "TMP") ) {
					chmod(  $myStoreDataPath , 0777);
					@mkdir( $myStoreDataPath . "TMP");
				}
				
				$tmpPDFilepath     = $myStoreDataPath ."TMP". DS . sprintf("BilanIndexation_De_%s.pdf", $opsFilenameStr);
				$tmpCSVFilepath    = $myStoreDataPath ."TMP". DS . sprintf("BilanACorriger_De_%s.csv" , $opsFilenameStr);
				$tmpPQCSVFilepath  = $myStoreDataPath ."TMP". DS . sprintf("BilanACorrigerPhysique_De_%s.csv" , $opsFilenameStr);
				$tmpMRCSVFilepath  = $myStoreDataPath ."TMP". DS . sprintf("BilanACorrigerMoral_De_%s.csv" , $opsFilenameStr);
				$tmpMDCSVFilepath  = $myStoreDataPath ."TMP". DS . sprintf("BilanACorrigerModifications_De_%s.csv" , $opsFilenameStr);
				$zipERRORSFilename = $myStoreDataPath ."TMP". DS . sprintf("BilanERREURS_De_%s.zip" ,   $opsFilenameStr);
 
				if( file_exists($tmpPDFilepath)) {
					@unlink($tmpPDFilepath);
				}
				if( file_exists($tmpCSVFilepath)) {
					@unlink($tmpCSVFilepath);
				}
				if( file_exists($tmpPQCSVFilepath)) {
					@unlink($tmpPQCSVFilepath);
				}
				if( file_exists($tmpMRCSVFilepath)) {
					@unlink($tmpMRCSVFilepath);
				}
				if( file_exists($tmpMDCSVFilepath)) {
					@unlink($tmpMDCSVFilepath);
				}
				if( file_exists($zipERRORSFilename)) {
					@unlink($zipERRORSFilename);
				}
				$totalErrors  = $total - $totalValid;
				$reportDate   = date("d/m/Y");				
				$reportOutput = sprintf(" <p style=\"font-size:large\"> Bonjour Mr/Mle/Mme %s. Je suis le robot de la plateforme ERCCM. Le %s, j'ai parcouru vos travaux de retraitement et d'indexation effectués dans la période du %s au %s. J'ai détecté au total %d erreurs. Mais ne vous inquietez, je vous proposerai des solutions pour corriger. <p>", 
				                         $opsName, $reportDate, $reportPeriodStart, $reportPeriodEnd, $totalErrors);
				if(!empty( $localiteValue ) && intval($localiteid)) {
					$reportOutput.=sprintf("<p style=\"font-size:large\"><b> Localité Concernée : </b> %s </p>", $localiteValue);					
				}	
                if(intval( $annee )) {
					$reportOutput.=sprintf("<p style=\"font-size:large\"><b> Année oncernée : </b> %d </p>", $annee);					
				}
				$reportOutput    .= sprintf("<h1 bgcolor=\"#FAFAFA\" style=\"background:#FAFAFA;color:#000;font-size:22;text-align:center;\"> BILAN DES ERREURS A CORRIGER DE %s </h1>", strtoupper($opsName));
                $reportOutput    .= "<table cellspacing=\"1\" cellpadding=\"1\" width=\"100%\" border=\"0\" align=\"left\">
				                      <tr>";				
                $reportOutput    .= "<td width=\"35%\">".sprintf("<h3><b> TOTAL RETRAITES ET INDEXES </b> : %s </h3>", $total)."</td>";	
                $reportOutput    .= "<td width=\"35%\">".sprintf("<h3><b> TOTAL VALIDE </b> : %s </h3>", $totalValid)."</td>";
                $reportOutput    .= "<td width=\"30%\">".sprintf("<h3><b> TOTAL DES ERREURS </b> : %s </h3>", $totalErrors)."</td>";
                $reportOutput    .= " </tr></table>";			
				
				
				$reportOutput    .= "<table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"left\" style=\"text-align:left;font-size:medium\">
				                        <thead><tr> <th width=\"40%\"> RCCM </th> <th width=\"20%\"> TYPES D'ERREURS </th><th width=\"40%\"> DETAILS </th></tr></thead>
										<tbody>";
				if( count(  $opsDataErrors ) && $totalErrors>0) {
					uasort( $opsDataErrors, function($a,$b){if($a["numero"]==$b["numero"]){return 0;} return($a["numero"]<$b["numero"])?-1 : 1;});
					foreach($opsDataErrors as $registreid => $registreErrorsInfos ) {
						    $registreLibelle = sprintf("%s : %s", $registreErrorsInfos["numero"], $registreErrorsInfos["nom_commercial"]);
							$registreErrors  = $registreErrorsInfos["errors"];
							$rowSpan         = count($registreErrors);
							if( $rowSpan==0 || empty($registreErrors))
								continue;							
							$i               = 0;
							$rowBg           = $this->view->cycle(array("#F0F0F0","#FFFFFF"))->next();
							foreach( $registreErrors as $registreError ) {
								     $reportOutput.="<tr style=\"background-color:".$rowBg."\">";
									        if( $i== 0) {
											    $reportOutput.="<td width=\"40%\" style=\"text-align:left;\" rowspan=\"".$rowSpan."\"> ".$registreLibelle." </td>";
										    }
											$reportOutput    .="<td width=\"20%\" style=\"text-align:left;\">".$registreError['title']      ."</td>";
									        $reportOutput    .="<td width=\"40%\" style=\"text-align:left;\">".$registreError['description']."</td>";
									 $reportOutput.="</tr>";
									 $i++;
							}	
                          							
					}
				}															
				$reportOutput    .= "   </tbody></table>";
									 
				//print_r($reportOutput); die();					 
				$PDF              = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
				$PDF->SetCreator(sprintf("%s", $opsUsername));
				$PDF->SetTitle(  sprintf("Bilan des retraitements et des indexations effectués par %s", $opsName));
				$PDF->SetMargins(5,25,5);
				$PDF->SetPrintHeader(false);
				$PDF->SetPrintFooter(false);
			
				$margins          = $PDF->getMargins();
				$contenuWidth     = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
				$PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				$PDF->AddPage();
			
				$PDF->Ln(10);				     	 
				$PDF->SetFont("helvetica" , "" , 10);	
				$PDF->writeHTML($reportOutput, true , false , true , false , '' );				 
				$PDF->Output($tmpPDFilepath,"F");
                
				//On créé un fichier CSV contenant la liste des erreurs sur les RCCM
				$csvHeader  = array("numero","nom_commercial","date_enregistrement","description","nom","prenom","lieu_naissance","date_naissance","sexe","adresse","telephone","passport","nationalite","situation_matrimonial");
				if( count($csvRowsPhysique) ) {										
				    $csvAdapter = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$tmpPQCSVFilepath,"has_header"=>true,"header"=> $csvHeader ) , "wb+" );
					$csvAdapter->save($csvRowsPhysique);
				}
				if( count($csvRowsMoral) ) {										
				    $csvAdapter = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$tmpMRCSVFilepath,"has_header"=>true,"header"=> $csvHeader ) , "wb+" );
					$csvAdapter->save($csvRowsMoral);
				}
				if( count($csvRowsModifications) ) {										
				    $csvAdapter = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$tmpMDCSVFilepath,"has_header"=>true,"header"=> $csvHeader ) , "wb+" );
					$csvAdapter->save($csvRowsModifications);
				}
                //On crée une archive
                try{
					$zipERRORSFiles      = new ZipArchive();
					$zipLimitFiles       = 5;
					if( $zipERRORSFiles->open($zipERRORSFilename ,ZipArchive::CREATE|ZipArchive::OVERWRITE) == TRUE) {
						if( file_exists( $tmpPDFilepath ) ) {
							$zipERRORSFiles->addFile($tmpPDFilepath   , $opsFilenameStr.".pdf");
						}
						if( file_exists($tmpPQCSVFilepath) && count($csvRowsPhysique)) {
							$zipERRORSFiles->addFile($tmpPQCSVFilepath, $opsFilenameStr."_Physique".".csv");
						}
						if( file_exists($tmpMRCSVFilepath) && count($csvRowsMoral)) {
							$zipERRORSFiles->addFile($tmpMRCSVFilepath, $opsFilenameStr."_Moral".".csv");
						}
						if( file_exists($tmpMDCSVFilepath) && count($csvRowsModifications)) {
							$zipERRORSFiles->addFile($tmpMDCSVFilepath, $opsFilenameStr."_Modifications".".csv");
						}
						if( count(   $csvRows )) {
							foreach( $csvRows as $registreid => $csvRow) {
								     if ($zipERRORSFiles->numFiles>$zipLimitFiles) {
										  $zipERRORSFiles->close();
										 if( $zipERRORSFiles->open($zipERRORSFilename) !== TRUE) {
											 break;
										 }
									 }
								     if( isset($opsDataErrors[$registreid]["documents"]["personnel"]) && file_exists($opsDataErrors[$registreid]["documents"]["personnel"])) {
									     $zipERRORSFiles->addFile($opsDataErrors[$registreid]["documents"]["personnel"],$opsDataErrors[$registreid]["numero"].".pdf");
									 }
							}
						}
						//print_r($zipERRORSFiles->numFiles); die();
						$zipERRORSFiles->close();
					} else {
						$errorMessages[] = "Impossible de créer une archive des fichiers generés";
					}
				} catch(Exception $e ) {
					    $errorMessages[] = "Impossible de créer une archive des fichiers generés: ".$e->getMessage();
				}					
			}			
			if( count( $errorMessages) ){
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
						 $this->_helper->Message->addMessage($message) ;
				}
			} else {
				header("Content-type: application/zip"); 
				header("Content-Disposition: attachment; filename=".basename($zipERRORSFilename).""); 
				header("Pragma: no-cache"); 
				header("Expires: 0"); 
				readfile($zipERRORSFilename);
				exit;
			}
		}
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
		$this->view->users       = $opsUsers;
		
		$this->render("errors");
	}
		
	
	public function findmissingAction()
	{
		$this->view->title  = "Recherche des RCCM Manquants";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("start_annee"=>2000,"end_annee"=>2016,"localites"=>array("OUA","BBD","BFR","ORD","ZNR","MNG","GAO","KYA","OHG","KDG"),"rootpath"=>"G:\DATAS_RCCM\GED\DESTINATION\PHYSIQUES","category" =>"A","findInRoot" => false, "from" => 1, "to" => 200);
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$foundItems         = array();
		$notFoundItems      = array("files" => array());
	
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
		if( $this->_request->isPost( )) {						
			$postData         = $this->_request->getPost();
			$srcPath          = (isset( $postData["rootpath"]   )) ? $postData["rootpath"]             : $defaultData["rootpath"];
			$checkedLocalites = (isset( $postData["localites"]  )) ? $postData["localites"]            : $defaultData["localites"];
			$startAnnee       = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : $defaultData["start_annee"];
			$endAnnee         = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
			$categoryKey      = (isset( $postData["category"]   )) ? trim(   $postData["category"] )   : $defaultData["category"];
			$categoryLibelle  = ($categoryKey == "A"             ) ? "PHYSIQUES"                       : "MORALES";
			$findInRoot       = (isset( $postData["findInRoot"] )) ? intval( $postData["findInRoot"])  : $defaultData["findInRoot"];
			
			if(!is_dir($srcPath )) {
				$errorMessages[]  = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $rootPath);
			}				
			if( intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years            = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
			}
			if(!count($years)) {
				$errorMessages[]  = "Veuillez préciser une plage de périodes";
			}
			if(!count( $checkedLocalites )) {
				$errorMessages[]  = "Veuillez selectionner les localités concernées";
			}
			if( empty( $errorMessages)) {
				foreach($checkedLocalites as $checkedLocaliteCode ) {
						$notFoundItems[$checkedLocaliteCode]["files"]                  = (isset($notFoundItems[$checkedLocaliteCode]["files"]))? $notFoundItems[$checkedLocaliteCode]["files"] : array();
						foreach( $years as $annee ) {
								 $checkedFilePath                                      = null;
								 $notFoundItems[$annee]["files"]                       = (isset($notFoundItems[$annee]["files"]))? $notFoundItems[$annee]["files"] :array();
								 $notFoundItems[$checkedLocaliteCode][$annee]["files"] = (isset($notFoundItems[$checkedLocaliteCode][$annee]["files"]))? $notFoundItems[$checkedLocaliteCode][$annee]["files"] : array();
								 $numRccm                                              = sprintf("BF%s%d%s", $checkedLocaliteCode, $annee, $categoryKey);
								 if(!$findInRoot)  {
									$checkedFilePath = $srcPath . DS . $checkedLocaliteCode . DS . $annee ;
							     } else {
									$checkedFilePath = $srcPath;
								 }
								 $totalFiles         = glob($checkedFilePath."/".$numRccm."*.pdf");
								 $lastFile           = array_values(array_slice($totalFiles, -1))[0];
								 $fileBasename       = str_replace($checkedFilePath."/", "", $lastFile );
								 $lastFileKey        = (count($totalFiles) >= 1 ) ? intval(str_replace($numRccm, "", $fileBasename)) : 1;
								 if( count($totalFiles)) {
									 for( $i=1;  $i <=  $lastFileKey; $i++ ) {
										  $rccmExists      = count(preg_grep('#(?:^|/)'.$numRccm.'0{0,3}'.$i.'\.pdf$#',glob($checkedFilePath."/".$numRccm.'*.pdf')));
								          if(!$rccmExists) {
									          $notFoundItems["files"][] = $notFoundItems[$checkedLocaliteCode]["files"][] = $notFoundItems[$annee]["files"][] = $notFoundItems[$checkedLocaliteCode][$annee]["files"][] = $numRccm . sprintf("%04d", $i).".pdf";
											//echo "Fichier manquant : ".$checkedFilePath."/".$numRccm . sprintf("%04d", $from).".pdf \n<br/>";
								          }
									 }
								 } 								 
						}						  
				}			
			}  
			if(empty( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
			    $this->_helper->layout->disableLayout(true);
				$checkPointOutputHtml = $this->view->partial("registres/checkmissingpdf.phtml",array("rows"=> $notFoundItems,"annees"=> $years,"localites"=> $localites,"checkedLocalites"=>$checkedLocalites,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee, "type" => $categoryKey));
				
				print_r( $checkPointOutputHtml );die();
				
				$me                   = Sirah_Fabric::getUser();
                $PDF                  = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
                $PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
                $PDF->SetTitle("Etat des lieux de la base de données des RCCM");
		
		       $margins                 = $PDF->getMargins();
		       $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		       $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			   $PDF->SetPrintHeader(false);
		       $PDF->SetPrintFooter(false);
		       $PDF->AddPage();
		
		       $PDF->Ln(10);				     	 
		       $PDF->SetFont("helvetica" , "" , 13);				     	 
		       $PDF->writeHTML($checkPointOutputHtml, true , false , true , false , '' );	
		 
		       echo $PDF->Output("checkpoint.pdf","D");
		       exit;
			} else {
				$defaultData       = $postData;
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" " , $messages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage( $message , "error" ) ;
				}
			}
		}		
		$this->view->data      = $defaultData;
		$this->view->localites = $localites;
		$this->view->annees    = $annees;
		
		$this->render("checkmissing");
	}
	
		

	public function numerisationAction()
	{
		$this->view->title  = "Convertir les documents numérisés en dossiers à retraiter";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
		$foundFiles         = array();
		$defaultData        = array("srcfolder" => "C:\ERCCM\\NUMERISATIONS","destfolder" => "C:\ERCCM\\A_RETRAITER");
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();
			
			$srcFolder            = $srcPath  = (isset($postData["srcfolder"]   ))? trim(strip_tags($postData["srcfolder"]))   : "C:\ERCCM\\NUMERISATIONS";
			$destFolder           = $destPath = (isset($postData["destfolder"]  ))? trim(strip_tags($postData["destfolder"]))  : "C:\ERCCM\\A_RETRAITER";
			$opsUsername          =             (isset($postData["ops_username"]))? trim(strip_tags($postData["ops_username"])): "";
						
			if(!empty($opsUsername) && is_dir($srcPath)) {
				$srcPath          = $srcFolder. DS . preg_replace("/\s/","-", $opsUsername);
				if(is_dir( $destPath ) && !is_dir($destPath. DS . preg_replace("/\s/","-", $opsUsername))) {
					$newDestDir   = $destFolder. DS . preg_replace("/\s/","-", $opsUsername);
					@chmod( $destFolder   , 0777 );
					@mkdir( $newDestDir);
				}
				$destPath         = $destFolder. DS . preg_replace("/\s/","-", $opsUsername);
			}						
			if(!is_dir( $srcPath  )) {
				$errorMessages[]  = sprintf("Le dossier source %s n'est pas valide", $srcPath);
			}
            if(!is_dir( $destPath  )) {
				$errorMessages[]  = sprintf("Le dossier de destination %s n'est pas valide", $destPath);
			} 			
			if(empty($errorMessages)){
				$files            = glob($srcPath . DS ."*.pdf");
				if(count(    $files )) {
					foreach( $files as $rccmFile) {	
                             $rccmFilename    = str_ireplace(".pdf", "", trim(basename( $rccmFile))); 
							 $filePathInfos   = pathinfo($rccmFile);
							 $fileDirname     = (isset($filePathInfos["dirname"] )) ? $filePathInfos["dirname"] : "";
                             $annee           = substr( $rccmFilename, 5, 4);	
                             $localite        = strtoupper(substr( $rccmFilename,2,3));
                             $numRccm         = str_replace(substr($rccmFilename,0,10), "", $rccmFilename);
                             $numIdPrefix     = substr( $rccmFilename, 0 , 9);
							 $newRccmFilename = substr( $rccmFilename, 0 , 10) . sprintf("%04d", intval($numRccm));							 
                             if((stripos( $numRccm, "FR") !== false ) || (stripos( $numRccm, "Bis") !== false )) {
								 continue;
							 }								 
						     $fileRccmDir     = $destPath . DS . $localite .  DS . $annee . DS . $newRccmFilename;
							 if(!is_dir( $fileRccmDir )) {
								 @chmod( $destPath    , 0777 );
						         @mkdir( $fileRccmDir , 0777 , true );
								 @chmod( $fileRccmDir , 0777 );
								 
								 $newFileDirFr    = $fileRccmDir. DS . $newRccmFilename."-FR.pdf";
								 $newFileDirST    = $fileRccmDir. DS . $newRccmFilename."-ST.pdf";
								 $newFileDirPS    = $fileRccmDir. DS . $newRccmFilename."-PS.pdf";
								 if(stripos($rccmFilename, $numIdPrefix."A") !== false ) {
									if(file_exists($fileDirname.DS.$rccmFilename."-FR.pdf")) {
										 @rename($fileDirname.DS.$rccmFilename."-FR.pdf", $newFileDirFr);
										 @copy($rccmFile, $newFileDirPS);
										 $foundFiles[]  = $rccmFilename;
									}elseif((TRUE ==@copy($rccmFile, $newFileDirFr)) && (TRUE==@copy($rccmFile, $newFileDirPS))) {
										 $foundFiles[]  = $rccmFilename;
									}
								 } elseif((stripos($rccmFilename, $numIdPrefix."B") !== false) || (stripos($rccmFilename, $numIdPrefix."M") !== false)  ) {
									 if(file_exists($fileDirname.DS.$rccmFilename."-FR.pdf")) {
										 @rename(   $fileDirname.DS.$rccmFilename."-FR.pdf", $newFileDirFr);
										 @copy($rccmFile, $newFileDirPS);
										 @copy($rccmFile, $newFileDirST);
										 $foundFiles[]  = $rccmFilename;
									 }elseif((TRUE ==@copy($rccmFile, $newFileDirFr)) && (TRUE==@copy($rccmFile, $newFileDirPS)) && (TRUE==@copy($rccmFile, $newFileDirST))) {
										 $foundFiles[]  = $rccmFilename;
									 }
								 } else {
									 if(file_exists($fileDirname.DS.$rccmFilename."-FR.pdf")) {
										 @rename(   $fileDirname.DS.$rccmFilename."-FR.pdf", $newFileDirFr);
										 @copy($rccmFile, $newFileDirPS);
										 $foundFiles[]  = $rccmFilename;
									 }elseif((TRUE ==@copy($rccmFile, $newFileDirFr)) && (TRUE==@copy($rccmFile, $newFileDirPS))) {
										 $foundFiles[]  = $rccmFilename;
									 }
								 }
							 }
					}
				}
			}
			if(!empty( $errorMessages )) {
				$defaultData       = $postData;
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode("" , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage( $message , "error" ) ;
				}
			} else {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success" =>  sprintf("%d dossiers ont été créés avec succès", count($foundFiles)) ));
					exit;
				}
				$this->_helper->Message->addMessage( sprintf("%d dossiers ont été créés avec succès", count($foundFiles)), "success" ) ;
			}
		}
		$this->view->data        = $defaultData;
		$this->render("numerisation");
	}

	
	
	
	
	public function uploadocsAction()
	{
		$this->view->title   = "Rattacher des documents aux registres";
		
		$modelLocalite     = $this->getModel("localite");
		$modelDocument     = $this->getModel("document");
		$model             = $this->getModel("registrephysique");
		$modelRegistre     = $this->getModel("registre");		
		$modelEntreprise   = $this->getModel("entreprise");
		$modelTable        = $model->getTable();
		$prefixName        = $modelTable->info("namePrefix");
		$dbAdapter         = $modelTable->getAdapter();
		$me                = Sirah_Fabric::getUser();
		$registres         = array();
		
		$defaultData       = array("type"    => 1, "localiteid" => 0, "annee" => 0 , "srcfolder" => "G:\\DATAS_RCCM\\GED\\BFRCCM",
				                   "use_ftp" => 0, "ftp_server" => "ftp.siraah.net", "ftp_user" => "siraa482729", "ftp_pwd" => "GbXYBRpN" );
		$errorMessages     = array();
		$successMessages   = array();
		$notSavedItems     = array();
		
		$savedItems        = array();
		$localites         = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "code") , array() , null , null , false );
		$annees            = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                   "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
		
		
		if( $this->_request->isPost( ) ) {
			$postData      = $this->_request->getPost();
			$kl            = (isset( $postData["localiteid"]) && isset( $localites[$postData["localiteid"]] )) ? $localites[$postData["localiteid"]]: "";
			$ka            = (isset( $postData["annee"]     )) ? intval( $postData["annee"] ) : date("Y");
			$type          = (isset( $postData["type"]      )) ? intval( $postData["type"] )  : 1;
			$useFtp        = (isset( $postData["use_ftp"]   )) ? intval( $postData["use_ftp"]): 0;
			$ftp_server    = (isset( $postData["ftp_server"])) ? $postData["ftp_server"]: "";
			$ftp_user      = (isset( $postData["ftp_user"]  )) ? $postData["ftp_user"]: "";
			$ftp_pwd       = (isset( $postData["ftp_pwd"]   )) ? $postData["ftp_pwd"] : "";
			$registres     = array();		
			$srcFolder     = (isset( $postData["srcfolder"] )) ? $postData["srcfolder"] : "G:\\DATAS_RCCM\\GED\\BFRCCM";
			$miniPPDocPathroot    = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini";
			$orginalPPDocPathroot = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
			$miniPMDocPathroot    = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "mini";
			$orginalPMDocPathroot = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales". DS . "original";
						
			if( $type == 1 ) {
				$model       = $this->getModel("registrephysique");
				$registres   = $model->getList(array("localiteid" => intval( $postData["localiteid"]), "annee" => $ka, "type" => 1) );
				$srcFilePath = $srcFolder.DS. strtoupper($kl).DS. "PHYSIQUES";
			} elseif($type  == 2 ) {
				$model       = $this->getModel("registremorale");
				$registres   = $model->getList(array("localiteid" => intval( $postData["localiteid"]), "annee" => $ka, "type" => 2),0, 0);
				$srcFilePath = $srcFolder.DS. strtoupper($kl).DS. "MORALES";
			}		
			if( count(   $registres )) {
				foreach( $registres as $registre ) {	
					     $registreid            = intval( $registre["registreid"] );
					     $numRegistre           = $registre["numero"];	
					     $srcFilePathFormulaire = $srcFilePath .DS. strtoupper($registre["numero"]).DS. strtoupper($registre["numero"])."-FR.pdf";
					     $srcFilePathComplet    = $srcFilePath .DS. strtoupper($registre["numero"]).DS. strtoupper($registre["numero"])."-PS.pdf";

					     if( file_exists( $srcFilePathFormulaire ) && file_exists( $srcFilePathComplet )) {
					     	 if( $type == 1 ) {					     	 	
					     	 	 $miniDocPath                    = $miniPPDocPathroot    . DS . sprintf("%09d_Fr.pdf", $registreid );
					     	 	 $originalDocPath                = $orginalPPDocPathroot . DS . sprintf("%09d_Ps.pdf", $registreid );
					     	 	 $documentData                   = array();
					     	 	 $documentData["userid"]         = $me->userid;
					     	 	 $documentData["category"]       = 1;
					     	 	 $documentData["resource"]       = "registrephysique";
					     	 	 $documentData["resourceid"]     = 0;
					     	 	 $documentData["filedescription"]= $numRegistre;
					     	 	 $documentData["filemetadata"]   = "";
					     	 	 $documentData["creationdate"]   = time();
					     	 	 $documentData["creatoruserid"]  = $me->userid;					     	 	
					     	 } elseif( $type == 2 ) {
					     	 	 $miniDocPath                    = $miniPMDocPathroot    . DS . sprintf("%09d_Fr.pdf", $registreid );
					     	 	 $originalDocPath                = $orginalPMDocPathroot . DS . sprintf("%09d_Ps.pdf", $registreid );
					     	 	 $documentData                   = array();
					     	 	 $documentData["userid"]         = $me->userid;
					     	 	 $documentData["category"]       = 1;
					     	 	 $documentData["resource"]       = "registremorale";
					     	 	 $documentData["resourceid"]     = 0;
					     	 	 $documentData["filedescription"]= $numRegistre;
					     	 	 $documentData["filemetadata"]   = "";
					     	 	 $documentData["creationdate"]   = time();
					     	 	 $documentData["creatoruserid"]  = $me->userid;
					     	 }
					     	 if(file_exists( $miniDocPath) || file_exists( $originalDocPath )) {
					     	 	continue;
					     	 }					     	 
					     	 if($useFtp && !empty( $ftp_server ) && !empty( $ftp_user )) {
					     	 	$conn_id = ftp_connect($ftp_server) or die("Impossible de se connecter au serveur $ftp_server");
					     	 	if(@ftp_login( $conn_id, $ftp_user, $ftp_pwd  )) {
					     	 		if(ftp_put( $conn_id, $miniDocPath, $srcFilePathFormulaire , FTP_ASCII )) {
					     	 			$miniFilename                     = sprintf("%09d_Fr.pdf", $registreid );
					     	 			$miniDocumentData                 = $documentData;
					     	 			$miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename, $me->userid );
					     	 			$miniDocumentData["filepath"]     = $miniDocPath ;
					     	 			$miniDocumentData["access"]       = 0 ;
					     	 			$miniDocumentData["filextension"] = "pdf";
					     	 			$miniDocumentData["filesize"]     = floatval( filesize( $srcFilePathFormulaire ));
					     	 			if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
					     	 				$documentid                   = $dbAdapter->lastInsertId();
					     	 				if( $dbAdapter->insert( $prefixName. "rccm_registre_documents",array("registreid" => $registreid,"documentid"=> $documentid, "access" => 0 ))) {
					     	 					$savedItems[]             = $numRegistre;
					     	 				}
					     	 			} else {
					     	 				$notSavedItems[]              = $numRegistre;
					     	 			}
					     	 		} else {
					     	 			$notSavedItems[]                  = $numRegistre;
					     	 		}
					     	 		if(@ftp_put( $conn_id, $originalDocPath, $srcFilePathComplet, FTP_ASCII )) {
					     	 			$originalFilename                 = sprintf("%09d_Ps.pdf", $registreid );
					     	 			$originalDocumentData                 = $documentData;
					     	 			$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename, $me->userid );
					     	 			$originalDocumentData["filepath"]     = $originalDocPath ;
					     	 			$originalDocumentData["access"]       = 6 ;
					     	 			$originalDocumentData["filextension"] = "pdf";
					     	 			$originalDocumentData["filesize"]     = floatval( filesize( $srcFilePathComplet ));
					     	 			if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData ) ) {
					     	 				$documentid                       = $dbAdapter->lastInsertId();
					     	 				if( $dbAdapter->insert( $prefixName. "rccm_registre_documents",array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6))) {
					     	 					$savedItems[]                 = $numRegistre;
					     	 				}
					     	 			} else {
					     	 				$notSavedItems[]                  = $numRegistre;
					     	 			}
					     	 		}
					     	 	} else {
					     	 		die("Impossible de se connecter au serveur $ftp_server . Problème d'identification");
					     	 	}
					     	 } else {
					     	 	 if(@copy( $srcFilePathFormulaire, $miniDocPath )) {
					     	 	 	$miniFilename                     = sprintf("%09d_Fr.pdf", $registreid );
					     	 	 	$miniDocumentData                 = $documentData;
					     	 	 	$miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename, $me->userid );
					     	 	 	$miniDocumentData["filepath"]     = $miniDocPath ;
					     	 	 	$miniDocumentData["access"]       = 0 ;
					     	 	 	$miniDocumentData["filextension"] = "pdf";
					     	 	 	$miniDocumentData["filesize"]     = floatval( filesize( $srcFilePathFormulaire ));
					     	 	 	if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
					     	 	 		$documentid                   = $dbAdapter->lastInsertId();
					     	 	 		if( $dbAdapter->insert( $prefixName. "rccm_registre_documents",array("registreid" => $registreid,"documentid"=> $documentid, "access" => 0 ))) {
					     	 	 			$savedItems[]             = $numRegistre;
					     	 	 		}
					     	 	 	} else {
					     	 	 		$notSavedItems[]              = $numRegistre;
					     	 	 	}
					     	 	 }
					     	 	 if(@copy( $srcFilePathComplet, $originalDocPath )) {
					     	 	 	$originalFilename                     = sprintf("%09d_Ps.pdf", $registreid );
					     	 	 	$originalDocumentData                 = $documentData;
					     	 	 	$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename, $me->userid );
					     	 	 	$originalDocumentData["filepath"]     = $originalDocPath ;
					     	 	 	$originalDocumentData["access"]       = 6 ;
					     	 	 	$originalDocumentData["filextension"] = "pdf";
					     	 	 	$originalDocumentData["filesize"]     = floatval( filesize( $srcFilePathComplet ));
					     	 	 	if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData ) ) {
					     	 	 		$documentid                       = $dbAdapter->lastInsertId();
					     	 	 		if( $dbAdapter->insert( $prefixName. "rccm_registre_documents",array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6))) {
					     	 	 			$savedItems[]                 = $numRegistre;
					     	 	 		}
					     	 	 	} else {
					     	 	 		$notSavedItems[]                  = $numRegistre;
					     	 	 	}
					     	 	 }
					     	 }//					     	
					     } else {
					     	printf("Les chemins %s et %s n'ont pas été trouvés", $srcFilePathFormulaire, $srcFilePathComplet );
					     }
				}
				
				$totalNotSaved  = count($registres ) - count($savedItems) ;
				echo "-------------------------------------------------- RAPPORT D'IMPORT DES DONNEES A PARTIR D'UNE ANCIENNE BASE DE DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES IMPORTES       :".count($savedItems)." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
					
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
				exit;
			}						
		}

		$this->view->localites  = $localites;
		$this->view->data       = $defaultData;
		$this->render("uploadocs");
	}
	
	
	
	protected function __checkRccmFiles(&$rccmFilesInfos, &$errorMessages)
	{
		$result    = true;
		
		if(!isset($rccmFilesInfos["formulaire"]) || !isset($rccmFilesInfos["personnel"]) || !isset($rccmFilesInfos["numero"])) {
			return false;
		}
        $numRccm   = $rccmFilesInfos["numero"];
		$isMorale  = (substr( $numRccm,-4,1) == "B") ? 1 : 0;
		if(!file_exists($rccmFilesInfos["formulaire"]) || !file_exists($rccmFilesInfos["personnel"]) || ($isMorale && !isset($rccmFilesInfos["statut"]))) {
			return false;
		}
		$formulaireFilePath        = $rccmFilesInfos["formulaire"];
		$completFilePath           = $rccmFilesInfos["personnel"];
		$statutFilePath            = ($isMorale && isset($rccmFilesInfos["statut"])) ? $rccmFilesInfos["statut"] : "";
		try{
			 $pdfRegistre          = new FPDI();
			 $pagesFormulaire      = (file_exists($formulaireFilePath))? $pdfRegistre->setSourceFile($formulaireFilePath ) : 0;
		     $pagesStatut          = (file_exists($statutFilePath    ))? $pdfRegistre->setSourceFile($statutFilePath     ) : 0;
			 $pagesComplet         = (file_exists($completFilePath   ))? $pdfRegistre->setSourceFile($completFilePath    ) : 0;
		} catch(Exception $e ) {
			/*$errorMessages[]       = sprintf("Le fichier %s ou %s est invalide", $completFilePath, $formulaireFilePath);*/
			$result                = true;
			$pagesFormulaire       = 0;
			$pagesComplet          = 0;
			$pagesStatut           = 0;
		}
		if($isMorale && !$pagesStatut) {
			$errorMessages[]       = sprintf("Le statut du RCCM n° %s n'existe pas dans le dossier retraité",$numRccm);
		    $result                = false;
		}
		if( $pagesFormulaire && ( $pagesComplet < $pagesFormulaire )) {
			$errorMessages[]       = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages",$numRccm);
		    $result                = false;
		}
		if( $pagesStatut     && ( $pagesComplet < $pagesStatut )) {
			$errorMessages[]       = sprintf("Le statut du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages", $numRccm);
		    $result                = false;
		}
		if( $pagesComplet <= 1 ) {
			$rccmFilesInfos["incoherence"]  = true;
		}
		if( file_exists( $formulaireFilePath)) {
			try {
				$logger                = new Logger('MyLogger');
				$pdfToText             = XPDF\PdfToText::create(array('pdftotext.binaries'=> 'F:\webserver\www\binaries\Xpdf\pdftotext.exe','pdftotext.timeout'=> 30,),$logger);
				$formulaireContent     = $pdfToText->getText( $formulaireFilePath );
				if(!empty( $formulaireContent )) {
					$findAnormalChar   = ((stripos($formulaireContent," casier")!==false)    || (stripos($formulaireContent," CARTE D'IDENTITE")!==false) ||
										  (stripos($formulaireContent," judiciaire")!==false)|| (stripos($formulaireContent," CNIB")!==false) || 
										  (stripos($formulaireContent," passport")!==false)  || (stripos($formulaireContent," procuration") !==false) );
					if( $findAnormalChar ) {
						$errorMessages[] = sprintf("Le formulaire du RCCM n° %s n'a pas été bien traité", $numRccm);
						$result          = false;
					}
				}
			} catch(Exception $e) {
				$result                  = true;
			}			
		}
		return $result;
	}
	
	public function globalstatsAction()
	{
		$this->view->title     = "Statistiques Globales";
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");
		
		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016", "2017" => "2017");
	    $defaultData           = array("srcpath"=> "G:\\ERCCM","localites"=> array("OUA","BBD","DDG","KDG","MNG","GAO","DRI","DJB","FDG","OHG","BRM","KGS"), "check_documents" => 0, "checked_annees" => $annees);
		$registres             = array();
		$valids                = array();
		$invalids              = array();
		$i                     = 1;
		$errorMessages         = array();
		$renderView            = "globalstats";
		$checkedAnnees         = $defaultData["checked_annees"];
		$checkedLocalites      = $defaultData["localites"];
		$filesUsernames        = array();
		$filesPeriodes         = array();
		$invalidFiles          = array();
		$sourceStats           = array("OUA"=>array(2000=>2031,2001=>2714,2002=>3144,2003=>3421,2004=>3984,2005=>4049,2006=>3591,2007=>4005,2008=>3702,2009=>4900,
		                                            2010=>4394,2012=>5776,2013=>6480,2014=>7115,2015=>6613,2016=>9230,2017=>0),
									   "BBD"=>array(2000=>0,2001=>0,2002=>0,2003=>0,2004=>0,2005=>0,2006=>0,2007=>0,2008=>0,2009=>0,
		                                            2010=>0,2012=>0,2013=>0,2014=>0,2015=>0,2016=>0,2017=>0),				
									   "BFR"=>array(2000=>0,2001=>0,2002=>0,2003=>0,2004=>0,2005=>0,2006=>0,2007=>0,2008=>0,2009=>0,
		                                            2010=>0,2012=>0,2013=>0,2014=>0,2015=>0,2016=>0,2017=>0),
                                       "DDG"=>array(2000=>0,2001=>0,2002=>0,2003=>0,2004=>0,2005=>0,2006=>0,2007=>0,2008=>0,2009=>0,
		                                            2010=>0,2012=>0,2013=>0,2014=>0,2015=>0,2016=>0,2017=>0));
		$usernameMapping       = array("BBD"=>"Ouedraogo-Alida","GAO"=>"Sangare-Alima"  ,"OHG"=>"Dayamba-Raissa" ,"KDG"=>"Groupe-Traore"  ,"MNG"=>"Ouedraogo-Alida",
		                               "ORD"=>"Sangare-Alima"  ,"BFR"=>"Ouedraogo-Alida","DPG"=>"Ouedraogo-Alida","LEO"=>"Ouedraogo-Alida","NNA"=>"Ouedraogo-Alida",
									   "YKO"=>"Ouedraogo-Alida","TGN"=>"Ouedraogo-Alida","DDG"=>"Ouedraogo-Alida","BGD"=>"Sangare-Alima"  ,"DRI"=>"Sangare-Alima"  ,
									   "KGS"=>"Sangare-Alima"  ,"GAO"=>"Sangare-Alima"  ,"TNK"=>"Sangare-Alima"  ,"ZNR"=>"Sangare-Alima"  ,"KYA"=>"Sangare-Alima"  ,
									   "BRM"=>"Sangare-Alima"  ,"DJB"=>"Ouedraogo-Alida","TGN"=>"Ouedraogo-Alida","DBG"=>"Sangare-Alima"  ,"FDG"=>"Sangare-Alima"  ,									   
									   "DDG"=> array(
									                  "2002"=>"Ouedraogo-Alida","2003"=>"Ouedraogo-Alida","2007"=>"Ouedraogo-Alida","2008" =>"Ouedraogo-Alida",
													  "2004"=>"Ouedraogo-Alida","2005"=>"Ouedraogo-Alida","2006"=>"Ouedraogo-Alida","2014"=>"Ouedraogo-Alida",
													  "2015"=>"Sangare-Alima"  ,"2009"=>"Ouedraogo-Alida","2012"=>"Groupe-Traore","2013"=> "Groupe-Traore",
													  "2011"=>"Ouedraogo-Alida","2010"=>"Ouedraogo-Alida","2000"=>"Sangare-Alima","2001"=>"Ouedraogo-Alida",
													  "2016"=>"Dayamba-Raissa" ,"2017"=> "Ouedraogo-Alida"),
									   "OUA"=> array(
									                  "2002"=>"Sangare-Alima" ,"2003"=> "Sangare-Alima" ,"2007"=> "Sangare-Alima","2008" =>"Sangare-Alima",
													  "2004"=>"Dayamba-Raissa","2005"=> "Dayamba-Raissa","2006"=> "Dayamba-Raissa","2014"=>"Dayamba-Raissa",
													  "2015"=>"Dayamba-Raissa","2009"=> "Groupe-Traore" ,"2012"=> "Groupe-Traore","2013"=> "Groupe-Traore",
													  "2011"=>"Ouedraogo-Alida","2010"=>"Ouedraogo-Alida","2000"=>"Sangare-Alima","2001"=>"Sangare-Alima",
													  "2016"=>"Administrateur","2017"=> "Administrateur")
									  );									  
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$checkedAnnees     = (isset($postData["checked_annees"])) ? $postData["checked_annees"] : $annees;
			$checkedLocalites  = (isset($postData["localites"]     )) ? $postData["localites"]      : $defaultData["localites"];
			$srcPath           = (isset($postData["srcpath"]       )) ? $postData["srcpath"]        : $defaultData["srcpath"];
			$registres         = array();
			@uasort($checkedLocalites,function($a,$b){if($a==$b){return 0;} return ($a < $b) ? -1 : 1;});
			@uasort($checkedAnnees   ,function($a,$b){if($a==$b){return 0;} return ($a < $b) ? -1 : 1;});
			if( count(   $checkedLocalites ) && count( $checkedAnnees )) {
				foreach( $checkedLocalites   as $checkedLocalite  ) {
					     if(empty( $checkedLocalite ) || !is_string( $checkedLocalite )) {
									  continue;
						 }
						 if(!isset($sourceStats[$checkedLocalite])) {
							 $sourceStats[$checkedLocalite]              = array();
						 }
					     $registres[$checkedLocalite]["valids"]["files"] = $registres[$checkedLocalite]["invalids"]["files"] = $registres[$checkedLocalite]["annees"] = array();
						 $registres[$checkedLocalite]["valids"]["total"] = $registres[$checkedLocalite]["invalids"]["total"] = 0;
						 $registres[$checkedLocalite]["annees"]          = array();
					     foreach( $checkedAnnees as $checkedAnneeKey => $checkedAnneeVal ) {
							      if(!isset($sourceStats[$checkedLocalite][$checkedAnneeVal])) {
									  $sourceStats[$checkedLocalite][$checkedAnneeVal] = 0;
								  }
							      if( empty( $checkedAnneeVal ) || !intval( $checkedAnneeVal ) || !is_numeric($checkedAnneeVal)) {
									  continue;
								  }
							      $checkedFilesFR = glob($srcPath."/".$checkedLocalite."/".$checkedAnneeVal."/*/*-FR.pdf");
								  $checkedFilesPS = glob($srcPath."/".$checkedLocalite."/".$checkedAnneeVal."/*/*-PS.pdf");
								  $checkedFilesAll= glob($srcPath."/".$checkedLocalite."/".$checkedAnneeVal."/*/*.pdf");
								  $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["valids"]          = $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["invalids"]["files"] = array();
								  $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["valids"]["total"] = $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["invalids"]["total"] =0;								  
								  $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["proprietaires"]   = array();
								  $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["periodes"]        = array();
								  if( count(   $checkedFilesFR )) {
									  foreach( $checkedFilesFR as $checkedFileFR ) {
										       $numRccm                        = str_ireplace(array("-FR.pdf",".pdf"),"", basename($checkedFileFR));
											   $fileInfos                      = stat($checkedFileFR);
											   $proprietaireFichier            = (isset($usernameMapping[$checkedLocalite][$checkedAnneeVal])) ? $usernameMapping[$checkedLocalite][$checkedAnneeVal]: ((isset($usernameMapping[$checkedLocalite])) ? $usernameMapping[$checkedLocalite] : "Administrateur");
											   if( is_array( $proprietaireFichier )) {
												   $proprietaireFichier        = "Administrateur";
											   }
											   $periodeModification            = date("M-Y");
											   if( isset($fileInfos["uid"])) {								   
												   $periodeModification        = date("M-Y", $fileInfos["mtime"] );
											   }
											   if(!in_array($proprietaireFichier, $filesUsernames) )
											       $filesUsernames[]           = $proprietaireFichier;
											   if(!in_array($periodeModification, $filesPeriodes)) 
												   $filesPeriodes[]            = $periodeModification;
										       //On vérifie d'abord la qualité du fichier
											   $filesDir                       = $srcPath."/".$checkedLocalite."/".$checkedAnneeVal."/".$numRccm;
											   $checkedDocuments["numero"]     = $numRccm ;
									           $checkedDocuments["formulaire"] = $filesDir."/".$numRccm."-FR.pdf";
						                       $checkedDocuments["personnel"]  = $filesDir."/".$numRccm."-PS.pdf";
										       $checkedDocuments["statut"]     = (file_exists($filesDir."/".$numRccm."-ST.pdf")) ? $filesDir."/".$numRccm."-ST.pdf" : null;
											   $checkedDocuments["incoherence"]= false;
											   
											   if((false === $this->__checkRccmFiles( $checkedDocuments, $errorMessages)) ) {
										           if( true == $checkedDocuments["incoherence"] ) {
													   $incoherenceFileDir     = $srcPath."/INCOHERENCES/".$checkedLocalite."/".$checkedAnneeVal."/".$numRccm;
													   if(!is_dir( $incoherenceFileDir )) {
														   @chmod($srcPath."/INCOHERENCES", 0777 );
														   if(!is_dir($srcPath."/INCOHERENCES/".$checkedLocalite)){
															   @mkdir($srcPath."/INCOHERENCES/".$checkedLocalite);
															   @chmod($srcPath."/INCOHERENCES/".$checkedLocalite, 0777 );
														   }
														   if(!is_dir($srcPath."/INCOHERENCES/".$checkedLocalite."/".$checkedAnneeVal)) {
															   @mkdir($srcPath."/INCOHERENCES/".$checkedLocalite."/".$checkedAnneeVal);
															   @chmod($srcPath."/INCOHERENCES/".$checkedLocalite."/".$checkedAnneeVal, 0777 );
														   }
														   @mkdir($incoherenceFileDir);
														   @chmod($incoherenceFileDir     , 0777 );
													   }													   
													   @rename(  $checkedDocuments["formulaire"], $incoherenceFileDir."/".$numRccm."-FR.pdf");
													   @rename(  $checkedDocuments["personnel"] , $incoherenceFileDir."/".$numRccm."-PS.pdf");
													   if( $checkedDocuments["statut"] ) {
														   @rename(  $checkedDocuments["statut"], $incoherenceFileDir."/".$numRccm."-ST.pdf");
													   }
													   @rmdir($filesDir);
													   /*print_r(file_exists($incoherenceFileDir."/".$numRccm."-FR.pdf" ));die($incoherenceFileDir);*/
												   }
												   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["invalids"]["files"][] = $registres[$checkedLocalite]["invalids"]["files"][] = $numRccm;
												   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["invalids"]["total"]  += 1;
												   $registres[$checkedLocalite]["invalids"]["total"]                              += 1;
												   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["proprietaires"][$proprietaireFichier]["invalids"]["files"][] = $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["periodes"][$periodeModification]["invalids"]["files"][] = $numRccm;
												   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["proprietaires"][$proprietaireFichier]["invalids"]["total"]  += 1;
												   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["periodes"][$periodeModification]["invalids"]["total"]       += 1;
												   $invalidFiles[]             = $checkedFileFR;
												   
												   continue;
									           }
											   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["valids"]["files"][] = $registres[$checkedLocalite]["valids"]["files"][] = $numRccm;
											   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["valids"]["total"]  += 1;
											   $registres[$checkedLocalite]["valids"]["total"]                              += 1;
											   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["proprietaires"][$proprietaireFichier]["valids"]["files"][] = $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["periodes"][$periodeModification]["valids"]["files"][] = $numRccm;
											   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["proprietaires"][$proprietaireFichier]["valids"]["total"]  += 1;
											   $registres[$checkedLocalite]["annees"][$checkedAnneeVal]["periodes"][$periodeModification]["valids"]["total"]       += 1;										       											   										       
									  }
								  }
						 }
				}
			} else {
				$errorMessages[]  = "Vous devrez sélectionner au moins une année et au moins une localité";
			}				
			if( count(   $registres )) {
				$renderView       = "globalstats2";
				
			} elseif( count( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest()) {
			        $this->_helper->viewRenderer->setNoRender(true);
			        echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
			        exit;
		        }
		        foreach( $errorMessages as $message ) {
				         $this->_helper->Message->addMessage($message) ;
		        }
			}
		}
		@uasort($filesUsernames,function($a,$b){if($a==$b){return 0;} return ($a < $b) ? -1 : 1;});
		@uasort($filesPeriodes ,function($a,$b){
			$periodeExplode1  = explode("-",$a);
			$periodeExplode2  = explode("-",$b);
			if(isset($periodeExplode1[1]) && isset($periodeExplode2[1])) {
				$moisA        = $periodeExplode1[0];
				$moisB        = $periodeExplode2[0];
				$anneeA       = $periodeExplode1[1];
				$anneeB       = $periodeExplode2[1];
				if(($moisA == $moisB ) && ($anneeA == $anneeB )) {
					return 0;
				}
				if( ($moisA <= $moisB) && ($anneeA <= $anneeB )) {
					return - 1;
				}
				if( ($moisA > $moisB ) && ($anneeA > $anneeB )) {
					return 1;
				}
			} else {
				if($a==$b){return 0;} return ($a < $b) ? -1 : 1;
			}			
		});
		$this->view->invalids         = $invalidFiles;
        $this->view->registres        = $registres;
		$this->view->usernames        = $filesUsernames;
        $this->view->periodes         = $filesPeriodes;		
        $this->view->checkedAnnees    = $checkedAnnees;
        $this->view->checkedLocalites = $checkedLocalites;		
		$this->view->data             = $defaultData;
		$this->view->localites        = $localites;
		$this->view->annees           = $annees;
		$this->view->statistiques     = $sourceStats;
		
		$this->render($renderView);
	}
	
	public function localitestatsAction()
	{
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");

		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS           = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=> "2011","2012"=>"2012","2013"=> "2013", "2014"=> "2014","2015"=> "2015", "2016"=> "2016", "2017" => "2017","2018"=>"2018");
		$defaultMaxNumByYears  = array("2000"=>"526","2001"=>"755","2002"=>"926","2003"=>"792","2004"=>"930","2005"=>"868","2006"=>"737","2007"=>"648","2008"=>"569","2009"=>"508","2010"=>"594","2011"=> "668","2012"=>"878","2013"=> "939","2014"=>"1401","2015"=>"1339","2016"=> "2147","2017"=>"2112","2018"=>"3000");       
		/*$defaultMaxNumByYears  = array("2000"=>"0","2001"=>"0","2002"=>"0","2003"=>"0","2004"=>"0","2005"=>"12","2006"=>"61","2007"=>"43","2008"=>"47",
		                               "2009"=>"36","2010"=>"58","2011"=> "23","2012"=>"59","2013"=>"74","2014"=> "241","2015"=>"92","2016"=> "193");*/									   
		$defaultData           = array("srcpath" => APPLICATION_INDEXATION_STOCKAGE_FOLDER,"localite" => "BBD","minNbPages" => 3,"maxNbPagesFr" => 4, "check_documents" => 0, "checked_annees" => $annees);
		$rccms                 = array();
		$existants             = array();
		$i                     = 1;
		$errorMessages         = array();
		$invalidMsg            = "";		
		
		if( $this->_request->isPost() ) {
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
			
			$postData          = $this->_request->getPost();
			$checkedAnnees     = (isset( $postData["checked_annees"] ))? $postData["checked_annees"]       : $annees;
			$localiteCode      = (isset( $postData["localite"]       ))? $postData["localite"]             : $defaultData["localite"];
			$localiteid        = (isset( $localiteIDS[$localiteCode] ))? $localiteIDS[$localiteCode]       : 0;
			$srcPath           = (isset( $postData["srcpath"]        ))? $postData["srcpath"]              : $defaultData["srcpath"];
			$minNbPages        = (isset( $postData["minNbPages"]     ))? intval($postData["minNbPages"])   : intval($defaultData["minNbPages"]);
			$maxNbPagesFr      = (isset( $postData["maxNbPagesFr"]   ))? intval($postData["maxNbPagesFr"]) : intval($defaultData["maxNbPagesFr"]);
			$maxNumByYears     = $defaultMaxNumByYears = (isset( $postData["maxNumByYears"] ) && count($postData["maxNumByYears"])) ? $postData["maxNumByYears"] : $defaultMaxNumByYears;
			$totalReste        = $j = 0;
			$resteAcollecter   = array();
			$check_documents   = (isset( $postData["check_documents"]))? intval($postData["check_documents"]) : intval($defaultData["check_documents"]);
			$output            = "<table border='1' width='100%' cellspacing='2' cellpadding='2'> 
		                            <thead><tr><th width='10%'> N° d'ordre </th><th width='30%'> Numéros RCCM </th> <th width='60%'> Observations </th></tr> </thead>
							        <tbody>";
			if(!isset($localites[$localiteCode])) {
				$errorMessages[] = "Veuillez sélectionner une localité valide";
			}
			if( count(   $checkedAnnees ) && empty($errorMessages) ) {
				foreach( $checkedAnnees as $checkedAnneeKey => $checkedAnneeVal ) {
					     $checkedFiles   = glob($srcPath."/".$localiteCode."/".$checkedAnneeVal."/*/*-PS.pdf");
				         $unCheckedFiles = glob($srcPath."/".$localiteCode."/".$checkedAnneeVal."/*.pdf");
						 $totalRegistres = count(glob($srcPath."/*/".$localiteCode."/".$checkedAnneeVal."/*", GLOB_ONLYDIR));
						 $lastNum        = $totalRegistres ;
						 $reste          = $lastNum - $totalRegistres;
						 //$totalReste     = 0;
						 $resteAcollecter[$checkedAnneeVal] = array("total"=>0);
						 $output        .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d - Total des documents : %s retraités ',$checkedAnneeVal,number_format($totalRegistres, 0, " "," "))."</strong></td> </tr>";
						 //print_r($postData);die();
						 $j              = 0;						 
						 if( $totalRegistres ) {
							 for( $i =1; $i<= $lastNum; $i++ ) {
								  $numKey              = sprintf("%04d", $i);
								  $numRccmPhysique     = sprintf("BF%s%dA%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmMorale       = sprintf("BF%s%dB%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmModification = sprintf("BF%s%dM%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmSurete       = sprintf("BF%s%dS%04d", $localiteCode, $checkedAnneeVal, $i);
						          $bgColor             = "style=\"background-color:".$this->view->cycle(array("#FFFFFF","#F5F5F5"))->next()."\"";
								  $checkedDocuments    = array();
								  $invalidMsg          = "";
								  
						          if( file_exists( $srcPath."/PHYSIQUES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-FR.pdf" ) ||
						              file_exists( $srcPath."/PHYSIQUES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique.".pdf" ) ||
                                      file_exists( $srcPath."/PHYSIQUES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique.".pdf" ) ) {							  
									  $checkedDocuments["numero"]     = $numRccmPhysique;
									  $checkedDocuments["formulaire"] = $srcPath."/PHYSIQUES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/PHYSIQUES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-PS.pdf";
								  } elseif(
						              file_exists( $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-FR.pdf" ) ||
						              file_exists( $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale.".pdf" ) ||
                                      file_exists( $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmMorale;
									  $checkedDocuments["formulaire"] = $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-PS.pdf";
									  $checkedDocuments["statut"]     = $srcPath."/MORALES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-ST.pdf";
					              } elseif(
						              file_exists( $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-FR.pdf" ) ||
						              file_exists( $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification.".pdf" ) ||
                                      file_exists( $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmModification;
									  $checkedDocuments["formulaire"] = $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-PS.pdf";
									  if( file_exists($srcPath."/".$localiteCode."/MODIFICATIONS/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-ST.pdf")) {
										  $checkedDocuments["statut"] = $srcPath."/MODIFICATIONS/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-ST.pdf";
									  }
					              } elseif(
						              file_exists( $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-FR.pdf" ) ||
						              file_exists( $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete.".pdf" ) ||
                                      file_exists( $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmSurete;
									  $checkedDocuments["formulaire"] = $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-PS.pdf";
									  if( file_exists($srcPath."/".$localiteCode."/SURETES/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-ST.pdf")) {
										  $checkedDocuments["statut"] = $srcPath."/SURETES/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-ST.pdf";
									  }
					              } else {
									  $checkedDocuments["numero"]     = $numRccm = sprintf("BF%s%dA|B|M|S%04d", $localiteCode, $checkedAnneeVal, $i);
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccm."/".$numRccm."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccm."/".$numRccm."-PS.pdf";
							          $invalidMsg                    .= sprintf("Ce registre n'est pas disponible dans la base de données ERCCM, %s", $numRccm);
									  									  
									  $numKey                         = sprintf("%04d", $i);
									  $resteAcollecter[$checkedAnneeVal]["total"] = $resteAcollecter[$checkedAnneeVal]["total"]+1;
									  $totalReste                     = $totalReste + 1;
									  //On enregistre dans la base de données que nous ne trouvons pas ce registre
									  $missingData                    = array("numero"=>$numRccm,"numkey"=>$numKey,"rheanum"=>"","annee"=>$checkedAnneeVal,"found"=>0,"observations"=>$invalidMsg,"localite"=>$localiteCode,"localiteid"=>$localiteid,"creationdate"=>time(),"creatorid"=>$me->userid,"rhearegistreid"=>0);

									      $dbAdapter->delete( $prefixName."rheaweb_registres_missings","numero='".$numRccm."'");
										  $dbAdapter->delete( $prefixName."rheaweb_registres_missings","numkey='".$missingData['numkey']."'");
									  if( $dbAdapter->insert( $prefixName."rheaweb_registres_missings", $missingData)) {
										  //$resteAcollecter[$checkedAnneeVal]["total"] = $resteAcollecter[$checkedAnneeVal]["total"]+1;
										  $j++; 
									  }	                                     									  
						          }	
                                  /*if( $minNbPages && isset($checkedDocuments["personnel"]) && file_exists($checkedDocuments["personnel"]))	 {
									  try{
									       $logger          = new Logger('MyLogger');
                                           $pdfInfo         = XPDF\PdfInfo::create(array('pdfinfo.binaries'=> 'F:\webserver\www\binaries\Xpdf\pdfinfo.exe','pdfinfo.timeout' => 30,), $logger);

                                           $personnelInfos  = $pdfInfo->extractInfo($checkedDocuments["personnel"]);
									       if( isset( $personnelInfos["pages"] )) {
										       $invalidMsg .= ( $personnelInfos["pages"] < $minNbPages ) ? sprintf(" Le document numéro %s semble être un fichier  incohérent ", $checkedDocuments["numero"] ) : "";
									        }
									  } catch( Exception $e ) {
									  }
								  }		*/	                                 							  
								  if( $check_documents )    {
									  $checkedDocuments["maxNbPagesFr"] = $maxNbPagesFr;
									  $checkedDocuments["minNbPages"]   = $minNbPages;
									  if( false === $this->__checkRccmFiles( $checkedDocuments, $errorMessages )) {
										  $invalidMsg                  .= sprintf("Le registre numéro %s n'a pas été retraité correctement, pour les raisons suivantes : %s", $checkedDocuments["numero"], implode(", ", $errorMessages ));
									  }
								  }								  
								  if( empty( $invalidMsg ) ) {
									  /*$output .=" <tr ".$bgColor."> <td> ".$i." </td> <td> ".$checkedDocuments["numero"]." </td> <td> Fichier disponible et valide </td> </tr> ";*/
								  } else {
									  if( is_dir( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique) ||
									      is_dir( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale)      ||
                                          is_dir( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification) ||
										  is_dir( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete)) {
										  /*$output .=" <tr style=\"background-color:#FFFF00;color:#000;\"><td> ".$i."</td> <td> ".$checkedDocuments["numero"]."</td><td> ".$invalidMsg.": Le dossier est mal traité </td> </tr> ";*/
									  } else {
										  //$resteAcollecter[$checkedAnneeVal]["total"] = $resteAcollecter[$checkedAnneeVal]["total"]+1;								  
										  $output .=" <tr style=\"background-color:#FF0000;color:white;\"><td>".$j."</td> <td> ".$checkedDocuments["numero"]."</td><td> ".$invalidMsg." </td> </tr> ";
									  }										  									  
								  }
							 }
							  $totalACollecter  = (isset($resteAcollecter[$checkedAnneeVal]["total"]))?$resteAcollecter[$checkedAnneeVal]["total"] : 0; 
							 
							  if( $totalACollecter > 0) {
								  $output      .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d - Total des manquants : %s / %s retraités ',$checkedAnneeVal,number_format($totalACollecter, 0, " "," "),number_format($totalRegistres, 0, " "," "))."</strong></td> </tr>";
							  }	else {
								  $output      .= "<tr class=\"alert alert-success siccess\"><td align=\"center\" colspan=\"4\" style=\"text-align:center;\" class=\"alert alert-success siccess\"><strong>".sprintf('AUCUN DOCUMENT MANQUANT POUR L\'ANNEE %d : TOUT CE QUI A ETE COLLECTE, RETRAITE A ETE AUSSI INDEXE. ',$checkedAnneeVal)."</strong></td> </tr>";
							  }								  
						 } else {
							  $totalACollecter  = (isset($resteAcollecter[$checkedAnneeVal]["total"]))?$resteAcollecter[$checkedAnneeVal]["total"] : 0;
							  $output .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d', $checkedAnneeVal )."</strong></td> </tr>";
			                  $output .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;background-color:#FF0000;color:white;\"> AUCUN DOCUMENT INDEXE N'A ETE TROUVE POUR L'ANNEE ".$checkedAnneeVal." </td> </tr>";
						 }
						 //$totalReste   = $j;
				}
				       $output        .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('TOTAL GENERAL DES DOCUMENTS MANQUANTS : %s', number_format($totalReste, 0,' ',' ') )."</strong></td> </tr>";
			} else {
				$errorMessages[]       = "Veuillez sélectionner au moins une année";
			}
			if( empty( $errorMessages  ))  {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				$output      .= "</tbody></table> ";		
		        echo $output;
			    exit;
			}
            $defaultData        = array_merge( $defaultData , $postData );			
		} 
        if( count( $errorMessages ) ) {
		   if( $this->_request->isXmlHttpRequest()) {
			   $this->_helper->viewRenderer->setNoRender(true);
			   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
			   exit;
		   }
		   foreach( $errorMessages as $message ) {
				    $this->_helper->Message->addMessage($message) ;
		   }
	    }
         $this->view->data          = $defaultData;
	     $this->view->maxNumByYears = $defaultMaxNumByYears;
	     $this->view->annees        = $annees;
	     $this->view->localites     = $localites;		
	}

	public function combineprepareAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		
		$this->view->title        = "Préparation du Dossier Combine";
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		$getParams                = $this->_request->getParams();
		
		$annees                   = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                  "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$months                   = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData          = array("srcpath"=>"F:\\FNRCCM2017-2018/DOCUMENTS_SCANNES","destpath"=>"F:\\FNRCCM2017-2018/COMBINE","annee"=>2016);
		
		$defaultData              = array_merge( $defaultInitData, $getParams);
		$combined                 = array();
		$errorMessages            = array();
		$i                        = 0;
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();			
			$srcPath              = (isset($postData["srcpath"] ))? $postData["srcpath"]       : "F:\\FNRCCM2017-2018/DOCUMENTS_SCANNES";
			$destPath             = (isset($postData["destpath"]))? $postData["destpath"]      : "F:\\FNRCCM2017-2018/COMBINE";
			$annee                = (isset($postData["annee"]   ))? intval($postData["annee"]) : 2016;
			$foldersPath          = $srcPath . DS . $annee;
			if(!is_dir( $srcPath  )) {
				$errorMessages[]  = sprintf("La source des documents `%s` n'a pas été trouvée", $srcPath );
			}
			if(!is_dir($foldersPath )) {
				$errorMessages[]  = sprintf("La source des documents `%s` n'a pas été trouvée", $foldersPath);
			}
			 if(!is_dir( $destPath )) {
				$errorMessages[]  = sprintf("La destination des documents `%s` n'a pas été trouvée", $destPath);
			}
            if(!intval( $annee ) ) {
				$errorMessages[]  = "Veuillez renseigner une année valide";
			}
			@chmod( $destPath );
			if( empty( $errorMessages ) ) {				
				$yearFolders      = glob($foldersPath."/*", GLOB_ONLYDIR);
                if( !is_dir( $destPath . DS . $annee) ) {
					@mkdir(  $destPath . DS . $annee );
					@chmod(  $destPath . DS . $annee, 0777);
				}					
				if( count(   $yearFolders )) {
					@chmod(  $foldersPath, 0777 );
					foreach( $yearFolders as  $monthFolder ) {
						     @chmod( $monthFolder, 0777 );
						     $monthname     = Sirah_Filesystem::mb_basename($monthFolder);
						     $monthFolders  = glob($foldersPath. DS . $monthname. "/*", GLOB_ONLYDIR);
                             if( !is_dir( $destPath . DS . $annee . DS . $monthname ) ) {
                                 @mkdir(  $destPath . DS . $annee . DS . $monthname );
                                 @chmod(  $destPath . DS . $annee . DS . $monthname, 0777 );								 
							 }							 
							 if( count(   $monthFolders ) ) {
								 foreach( $monthFolders as $monthDayFolder ) {
									      @chmod( $monthDayFolder, 0777 );
										  $dayStr         = preg_replace("/\s+/","",Sirah_Filesystem::mb_basename($monthDayFolder));
										  $combineFolder  = $destPath . DS . $annee . DS . $monthname . DS . $dayStr;
										  if(!is_dir( $combineFolder ) ) {
											  @mkdir( $combineFolder );
											  @chmod( $combineFolder, 0777 );
										  }
										  $dayFolders     = glob( $monthDayFolder. "/*", GLOB_ONLYDIR);
										  if( count(   $dayFolders ) ) {
											  foreach( $dayFolders  as $clientFolder ) {
												       $clientFolderStr     = preg_replace("/\s+/","",Sirah_Filesystem::mb_basename($clientFolder));
													   $clientCombineFolder = $combineFolder . DS . $clientFolderStr;
												       $clientDocuments     = glob( $clientFolder . "/*.pdf");
													   if(!is_dir(  $clientCombineFolder ) ) {
														   @mkdir(  $clientCombineFolder );
														   @chmod(  $clientCombineFolder, 0777 );
													   }
													   if( count(   $clientDocuments)) {						  
														   foreach( $clientDocuments as $clientDocument ) {
															        $clientFilenameStr     = preg_replace("/\s+/","",Sirah_Filesystem::mb_basename($clientDocument));
																	$clientCombineFilename = $clientCombineFolder . DS . $clientFilenameStr;
																	if(!file_exists( $clientCombineFilename )) {
																		if( TRUE== copy($clientDocument, $clientCombineFilename) ) {
																			$i++;
																		} else {
																			$errorMessages[]  = sprintf("Le fichier source `%s` n'a pas été copié dans le dossier %s", $clientDocument, $clientCombineFolder);
																		}
																	}													        
														   }
													   }
											  }
										  }	 else {
											  $errorMessages[]   = sprintf("Aucun document n'a été trouvé dans le dossier `%s` ", $monthDayFolder);
										  }											  
								 }
							 } else {
									$errorMessages[]        = sprintf("Le dossier du mois de `%s` : `%s` ne contient aucun document", $monthname, $foldersPath. DS . $monthname);
							 }
					}
				} else {
					$errorMessages[]        = sprintf("Le dossier `%s/*` ne contient aucun document", $foldersPath);
				}
			}
			if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d dossiers ont été renomés avec succès", $i)));
				    exit;
			    }
				$this->setRedirect(sprintf("%d dossiers ont été renomés avec succès", $i), "success");
				$this->redirect("admin/registres/combine/annee/".$annee); 
			}
		}
		$this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;	
	}
	
	
	public function importsiguedataAction()
	{
		@ini_set('memory_limit', '512M');		
		$this->view->title          = "Importer des données depuis un fichier CSV SIGUE";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$modelDocument              = $this->getModel("document");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                    "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
		$defaultInitData            = array("annee"=>2015,"localite"=>"OUA","folderstocheck"=>"F:\\ERCCM");
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$imported                   = array();
		$rccms                      = array();
		$rccmDocuments              = array();
		$rccmNumeros                = array();
		$rccmDates                  = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
            $me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $tablePrefix = $userTable->info("namePrefix");			
			$postData               = $this->_request->getPost();
		    $annee                  = (isset($postData["annee"]         ))? intval($postData["annee"])  : 2017;
			$localiteCode           = (isset($postData["localite"]      ))? $postData["localite"]       : $defaultData["localite"];
			$localiteid             = (isset($localiteIDS[$localiteCode]))? $localiteIDS[$localiteCode] : 0;
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			if( !isset( $localiteIDS[$localiteCode] ) ) {
				$errorMessages[]    = "Veuillez sélectionner une localité ";
			}
			if(!isset($annees[$annee] )) {
				$errorMessages[]    = "Veuillez sélectionner une année ";
			}			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Count"    ,false, 1);
			$documentsUploadAdapter->addValidator("Extension",false, array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize",false, array("max" => "100MB"));
			
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			$csvStoreFilePath       = $me->getDatapath() . DS . time() . "mySigueData.csv";
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvStoreFilePath, "overwrite"=>true), "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty($errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("NumeroRCCM","DateRCCM","AncienRCCM","NomCommercial","SecteurActivite","Nom","Prenom","DateNaissance","LieuNaissance","Sexe","Telephone","Adresse","NumeroPiece","LibelleTypePiece");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvStoreFilePath,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines       = $csvAdapter->getLines();
					$csvItems       = 1;
					if(isset($csvLines[0])) {
					   unset($csvLines[0]);
					}
					$csvRows          = array_filter($csvLines,function($csvRow) use ($model){
						 						
						return true;
					});
					//print_r($csvRows);die();
					 if( count(  $csvRows ) ) {
						 foreach($csvRows as $csvKey => $csvRow ) {
							     $DateDemande        = (isset($csvRow["DateRCCM"]        ))?$csvRow["DateRCCM"]                                    : "";
								 $NumeroRCCM         = (isset($csvRow["NumeroRCCM"]      ))?$stringFilter->filter($csvRow["NumeroRCCM"] )          : "";
								 $AncienRCCM         = (isset($csvRow["AncienRCCM"]      ))?trim($stringFilter->filter($csvRow["AncienRCCM"]),"-") : "";
								 $Description        = (isset($csvRow["SecteurActivite"] ))?$stringFilter->filter($csvRow["SecteurActivite"] )     : "";
								 $NumeroPIECE        = (isset($csvRow["NumeroPiece"]     ))?$stringFilter->filter($csvRow["NumeroPiece"])          : "";								 
								 $NomCommercial      = (isset($csvRow["NomCommercial"]   ))?$stringFilter->filter($csvRow["NomCommercial"])        : "";
								 $Nom                = (isset($csvRow["Nom"]             ))?$stringFilter->filter($csvRow["Nom"])                  : "";
								 $Prenom             = (isset($csvRow["Prenom"]          ))?$stringFilter->filter($csvRow["Prenom"])               : "";
								 $Telephone          = (isset($csvRow["Telephone"]       ))?$stringFilter->filter($csvRow["Telephone"])            : "";
								 $DateNaissance      = (isset($csvRow["DateNaissance"]   ))?$stringFilter->filter($csvRow["DateNaissance"])        : "";
								 $LieuNaissance      = (isset($csvRow["LieuNaissance"]   ))?$stringFilter->filter($csvRow["LieuNaissance"])        : "";
								 $Sexe               = (isset($csvRow["Sexe"]            ))?$stringFilter->filter($csvRow["Sexe"])                 : "M";
								 $Adresse            = (isset($csvRow["Adresse"]         ))?$stringFilter->filter($csvRow["Adresse"])              : " ";
								 $NumParent          = "";
								 $zendDate           = null;

								 if(($NomCommercial=="NULL") || !$strNotEmptyValidator->isValid($NomCommercial) ) {
									 $errorMessages[]= sprintf("Le nom commercial du numéro RCCM N° %s de SIGUE est invalide",$NumeroRCCM, $DateDemande);
									 continue;
								 }				
                                								 
								 $numLocalite        = trim(substr($NumeroRCCM, 2, 3));
								 $numYear            = trim(substr($NumeroRCCM, 5, 4));
								 $numTypeRegistre    = trim(substr($NumeroRCCM, 9, 1));								 
								 $cleanNumRccm       = $model->normalizeNum($NumeroRCCM, $numYear, $numLocalite);								 
								 $numKey             = trim(substr($NumeroRCCM, 10));
								 $checkedRccmRow     = $model->findRow($cleanNumRccm, "numero", null, false );
								 if( $checkedRccmRow ) {
									 $dbAdapter->delete($prefixName."rccm_registre_indexation","numero=\"".$cleanNumRccm."\"");
								 }
								 if( $numTypeRegistre=="M" ) {
									 $NumParent      = $AncienRCCM;
								 }
								 if( empty($numLocalite) || empty($numYear) ) {
									 $errorMessages[]= sprintf("Le numéro RCCM %s de SIGUE est invalide", $NumeroRCCM);
									 continue;
								 }
								 if(!$NumeroPIECE || empty( $NumeroPIECE )) {
									 $NumeroPIECE    = "  ";
								 }
								 $sigueData                              = array("numero"=>$cleanNumRccm,"nom_commercial"=>$NomCommercial,"nom"=>$Nom,"prenom"=>$Prenom,"date_enregistrement"=>$DateDemande,"telephone"=>$Telephone,"numparent"=>$NumParent,"lieu_naissance"=>$LieuNaissance,"date_naissance"=>$DateNaissance,"sexe"=>$Sexe,"passport"=>$NumeroPIECE,"description"=>$Description,"adresse"=>$Adresse);								 								 
								 $searchInDbSql                          = "SELECT * FROM ".$prefixName."rccm_registre_indexation WHERE numero=\"".$cleanNumRccm."\"";
						         $contentRegistre                        = $dbAdapter->fetchRow( $searchInDbSql, array(), 5);
								 if( $contentRegistre ) {
									 if(!$dbAdapter->update( $prefixName . "rccm_registre_indexation",$sigueData, array("numero='".$cleanNumRccm."'"))) {
										 $errorMessages[]                = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour",$NumeroRCCM);
									 } else {
										 $imported[]                     = $NumeroRCCM;
									 }
								 } else {
									 $sigueData["numparent"]             = $NumParent;	
									 $sigueData["situation_matrimonial"] = "Celibataire";	
									 $sigueData["capital"]               = 1000000;
									 $sigueData["type_modification"]     = "";
									 
									 if(!$dbAdapter->insert($prefixName . "rccm_registre_indexation", $sigueData)) {
										 $errorMessages[]                = sprintf("Les informations du registre numéro %s n'ont pas été enregistrées",$cleanNumRccm);
									 } else {
										 $imported[]                     = $NumeroRCCM;
									 }
								 }									 								 								 
						 }
					 }
				}
			}
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				$successMessage     = sprintf("L'opération s'est effectuée avec succès : nous avons enregistrés %d registres de commerce manquants", count($imported));
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => $successMessage ));
				    exit;
			    }
				$this->setRedirect($successMessage, "success");
				$this->redirect("admin/registres/createcsv/annee/".$annee."/localiteid/".$localiteid); 
			}			
		}
		$this->view->data         = $defaultData;
		$this->view->localites    = $localites;
		$this->view->annees       = $annees;
		$this->render("importsiguedata");
	}
	
	public function importsiguerowsAction()
	{
		$this->view->title          = "Importer des données depuis un fichier CSV SIGUE";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$modelDocument              = $this->getModel("document");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                    "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$months                     = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData            = array("srcpath"=>"F:\\FNRCCM2017-2018\\ORIGINAL","destpath"=>"F:\\FNRCCM2017-2018\\SIGUE","annee"=>2016 );
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$imported                   = array();
		$rccms                      = array();
		$rccmDocuments              = array();
		$rccmNumeros                = array();
		$rccmDates                  = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {			
			$postData               = $this->_request->getPost();
			$srcPath                = (isset($postData["srcpath"]    ))? $postData["srcpath"]             : "F:\\FNRCCM2017-2018\\ORIGINAL";
			$destPath               = (isset($postData["destpath"]   ))? $postData["destpath"]            : "F:\\FNRCCM2017-2018\\SIGUE";
		    $checkedYear            = (isset($postData["annee"]      ))? intval($postData["annee"])       : 0;
			$checkedMonth           = (isset($postData["mois"]       ))? sprintf("%02d",$postData["mois"]): 0;
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
			
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $userTable->info("namePrefix");			
			 			
            $csvDestinationName     = $destPath. DS . time() . "mySigue.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive("registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					//$csvHeader     = array("DateDemande","NumeroRCCM","IdPiece","NumeroPiece","NomCommercial","NomPromoteur");
					$csvHeader     = array("NumeroRCCM","DateDemande","IdPiece","IdTypePiece","NumeroPiece","NomCommercial","NomPromoteur");
					$csvAdapter    = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines      = $csvAdapter->getLines();
					$csvItems      = 1;
					if( isset($csvLines[0]) ) {
					    unset($csvLines[0]);
					}
					$csvRows       = array_filter($csvLines,function($csvRow) use ($checkedYear,$checkedMonth){
						if(!Zend_Date::isDate($csvRow["DateDemande"],"YYYY-MM-dd H:i") && 
						   !Zend_Date::isDate($csvRow["DateDemande"],"dd/mm/YYYY H:i") && 
						   !Zend_Date::isDate($csvRow["DateDemande"], Zend_Date::ISO_8601)) {
						   return false;
						}
						if((false===stripos($csvRow["DateDemande"],sprintf("%04d-%02d",$checkedYear ,$checkedMonth))) &&
						   (false===stripos($csvRow["DateDemande"],sprintf("%02d/%04d",$checkedMonth,$checkedYear)))
						) {
							return false;
						}
						    return true;
					});
					//print_r($csvRows);die();
					@uasort($csvRows,function($a, $b){if($a['DateDemande'] == $b['DateDemande']){return 0;} return ($a['DateDemande'] < $b['DateDemande']) ? -1 : 1; });
					
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {							     
								 $dateDemande   = trim($csvRow["DateDemande"]);
								 $numeroRCCM    = $stringFilter->filter($csvRow["NumeroRCCM"] );
								 $numeroPIECE   = $stringFilter->filter($csvRow["NumeroPiece"]);
								 $idPIECE       = $stringFilter->filter($csvRow["IdPiece"]);
								 $nomCommercial = trim($csvRow["NomCommercial"]);
								 $nomPromoteur  = trim($csvRow["NomPromoteur"]);
								 
								 /*if((strlen($numeroRCCM) < 14) || (strlen($numeroRCCM) > 16)) {
									$errorMessages[] = sprintf("Le numéro RCCM %s de SIGUE est invalide", $numeroRCCM );
									continue;
								 }*/
								 if( empty( $dateDemande ) ) {
									 continue;
								 }
								 if( empty( $numeroRCCM ) ) {
									 continue;
								 }
								 if(!Zend_Date::isDate($dateDemande,"YYYY-MM-dd H:i") && !Zend_Date::isDate($dateDemande,"dd/mm/YYYY H:i") ) {
									 $errorMessages[] = sprintf("La date du numéro RCCM %s de SIGUE est invalide :%s", $numeroRCCM, $dateDemande );
									 continue;
								 }
								 $numYear       = intval(trim(substr($numeroRCCM, 5, 4)));
								 $numLocalite   = trim(substr($numeroRCCM, 2, 3));
								 if(!intval($numYear) || (strlen($numYear) != 4 )) {
									 $numYear   = $annee;
								 }
								 if( empty( $numLocalite ) ) {
									 $numLocalite = "OUA";
								 }
								 $cleanNumRccm  = $model->normalizeNum($numeroRCCM, $numYear,$numLocalite,"BF");
								 $numKey        = trim(substr($cleanNumRccm, 10));								 
								 $localiteid    = (isset($localiteIDS[$numLocalite]))?$localiteIDS[$numLocalite] : $localiteIDS["OUA"] ;
								 
								 if(Zend_Date::isDate($dateDemande,"dd/mm/YYYY H:i")) {
									  $zendDate  = new Zend_Date($dateDemande, Zend_Date::DATES ,"fr_FR");								 
						         } elseif( Zend_Date::isDate($dateDemande,"YYYY-MM-dd H:i:s") ) {
									  $zendDate  = new Zend_Date($dateDemande,"YYYY-MM-dd H:i");
								 } elseif( Zend_Date::isDate($dateDemande, Zend_Date::ISO_8601) ) {
									  $zendDate  = new Zend_Date($dateDemande, Zend_Date::ISO_8601);
						         } else {
									  $zendDate  = null;
								 }
								 
								 $rccmYear      = $annee = ( $zendDate ) ? $zendDate->get(Zend_Date::YEAR)         : 0;
								 $rccmMois      = $month = ( $zendDate ) ? strtoupper($zendDate->toString("MMMM")) : "";
								 $rccmDay       = $jour  = ( $zendDate ) ? $zendDate->toString("ddMMYYYY")         : "";
								 $rccmDate      =          ( $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP)    : 0;
                                 $rccmMonthValue= ( $zendDate ) ? $zendDate->toString("MM") : "";
								 $rccmDayValue  = ( $zendDate ) ? $zendDate->toString("dd") : "";								 
								 if( intval($checkedYear)  && (intval($checkedYear)!=$rccmYear)) {
									 continue;
								 }
								 if( intval($checkedMonth) && ($checkedMonth!=$rccmMonthValue)) {
									 continue;
								 }
								 if(!intval($rccmYear) || !$zendDate || !intval($rccmMonthValue) || !intval($rccmDayValue)) {
									 $errorMessages[]  = sprintf("La date du numéro RCCM %s est invlide : %s", $numeroRCCM, $dateDemande );
									 continue;
								 }								 
								 $rccmDates[$rccmYear][$rccmMonthValue][$rccmDayValue][$cleanNumRccm] = $nomCommercial;
								 $sigueRccm     = $model->findsiguerc($numeroRCCM);
								 $sigueRccmData = array("numero"=>$numeroRCCM,"numkey"=>$numKey,"cleanum"=>$cleanNumRccm,"localiteid"=>$localiteid,"valid"=>1,"found"=>0,"date"=>$rccmDate,
                                                        "localite"=> $numLocalite,"nomcommercial"=>$nomCommercial,"nompromoteur"=>$nomPromoteur,"annee"=>$rccmYear,"datedemande"=>$dateDemande);
								 if( $sigueRccm->registreid ) {
									 $registreid                     = $sigueRccm->registreid;
									 $sigueRccmData["updateduserid"] = $me->userid;
									 $sigueRccmData["updatedate"]    = time();
									 if(!$dbAdapter->update( $prefixName . "sigue_registre", $sigueRccmData, array("numero='".$numeroRCCM."'"))) {
										 $errorMessages[]            = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour", $numeroRCCM);
									 }
								 } else {
									 $sigueRccmData["creatorid"]     = $me->userid;
									 $sigueRccmData["creationdate"]  = time();
									 if(!$dbAdapter->insert( $prefixName . "sigue_registre", $sigueRccmData) ) {							 
										 $errorMessages[]            = sprintf("Les informations du registre numéro %s n'ont pas pu être enregistrées", $numeroRCCM);
									 } else {
										 $registreid                 = $dbAdapter->lastInsertId();
									 }
								 }
								 $rccms[$registreid]                 = $sigueRccmData;						 
						}
					} else {
						         $errorMessages[]                    = "Le fichier CSV ne comporte aucune ligne valide";
					}
					//$rccmDates[$rccmYear][$rccmMonthValue][$rccmDayValue][$cleanNumRccm] = $nomCommercial;															
				} else {
					$errorMessages[]                                 = "Le fichier CSV n'a pas pu être copié sur le serveur";
				}
			} else {
				    $errorMessages[]                                 = "Le fichier CSV n'a pas pu être reçu par le serveur";
			}
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d dossiers ont été importés depuis SIGUE avec succès", count($rccms) )));
				    exit;
			    }
				$this->setRedirect( sprintf("%d dossiers ont été importés depuis SIGUE avec succès", count($rccms) ), "success");
				$this->redirect("admin/registres/combine"); 
			}			
		}
        $this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;
        $this->render("importsiguerows");
	}
	
	public function importsiguefilesAction()
	{
		@ini_set('memory_limit', '512M');
		
		$this->view->title          = "Importer les documents";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
		$months                     = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData            = array("srcpath" =>"C:\\SIGUE\\ORIGINAL","destpath"=>"C:\\SIGUE\\A_RETRAITER","annee"=>2016,"checkpath"=>"G:\\ERCCM","incoherencespath"=>"G:\\ERCCM_INCOHERENCES","overwrite"=>"COMBINE","sigueuser"=>"compte.OPS","sigueuri"=>"http://10.60.16.17:8014/Piece/ShowFile/","siguepwd"=>"P@ssw0rd","sigueuauth_type"=>CURLAUTH_NTLM);
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$combined                   = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
			set_time_limit(0);
			$postData               = $this->_request->getPost();
			$srcPath                = (isset($postData["srcpath"]         ))? $postData["srcpath"]               : "C:\\SIGUE\\ORIGINAL";
			$destPath               = (isset($postData["destpath"]        ))? $postData["destpath"]              : "C:\\SIGUE\\A_RETRAITER";
		    $checkPath              = (isset($postData["checkpath"]       ))? $postData["checkpath"]             : "G:\\ERCCM";
			$incoherencesPath       = (isset($postData["incoherencespath"]))? $postData["incoherencespath"]      : "G:\\ERCCM";
			$localiteCode           = (isset($postData["localite"]        ))? $postData["localite"]              : "OUA";
			$checkedYear            = (isset($postData["annee"]           ))? sprintf("%04d",$postData["annee"]) : 0;
			$checkedMonth           = (isset($postData["mois"]            ))? sprintf("%02d",$postData["mois"])  : 0;
			$sigueUri               = (isset($postData["sigueuri"]        ))? $postData["sigueuri"]              : "http://10.60.16.17:8014/Piece/ShowFile/";
			$sigueAuthType          = (isset($postData["sigueuauth_type"] ))? $postData["sigueuauth_type"]       : CURLAUTH_NTLM;
			$sigueUsername          = (isset($postData["sigueuser"]       ))? $postData["sigueuser"]             : "compte.OPS";
			$siguePassword          = (isset($postData["siguepwd"]        ))? $postData["siguepwd"]              : "P@ssw0rd";
			$overwriteOption        = (isset($postData["overwrite"]       ))? strtoupper($postData["overwrite"]) : "COMBINE";
			
			if(!is_dir( $srcPath )) {
				$errorMessages[]    = sprintf("Le dossier source %s ne semble pas valide", $srcPath );
			}
			if(!is_dir( $destPath )) {
				$errorMessages[]    = sprintf("Le dossier de destination %s ne semble pas valide", $destPath);
			}
			if(!is_dir( $checkPath )) {
				$errorMessages[]    = "Le chemin de vérification n'est pas valide";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
			
			//On crée un validateur de filtre
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
			
			$basicFilename            = $documentsUploadAdapter->getFileName("registres", false );
			
			$me                       = Sirah_Fabric::getUser();
			$userTable                = $me->getTable();
			$dbAdapter                = $userTable->getAdapter();
			$prefixName               = $userTable->info("namePrefix");
			$copied                   = array();
						 			
            $csvDestinationName       = $destPath. DS . time() . "mySigueData.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName,"overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader        = array("NumeroRCCM","DateRCCM","IdPiece","NumeroPiece","NomCommercial");
					$csvAdapter       = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines         = $csvAdapter->getLines();
					$csvItems         = 1;
					if( isset($csvLines[0]) ) {
					    unset($csvLines[0]);
					}
					$csvRows          = array_filter($csvLines,function($csvRow) use ($checkedYear,$checkedMonth,$model){
						$NumeroRCCM   = (isset($csvRow["NumeroRCCM"] ))?$csvRow["NumeroRCCM"] : "";
						$numLocalite  = trim(substr($NumeroRCCM, 2, 3));
						$numYear      = trim(substr($NumeroRCCM, 5, 4));
						$cleanNumRccm = $model->normalizeNum($NumeroRCCM, $numYear, $numLocalite);
						$checkRccmRow = $model->findRow($cleanNumRccm , "numero", null, false );
						if( $checkRccmRow ) {
							return false;
						}
                        if( empty($csvRow["IdPiece"])) {
							return false;
						}							
						return true;
					});	
                    //print_r( $csvRows ); die();					
				    if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $DateDemande          = (isset($csvRow["DateRCCM"]        ))?$csvRow["DateRCCM"]                           : "";
								 $NumeroRCCM           = (isset($csvRow["NumeroRCCM"]      ))?$stringFilter->filter($csvRow["NumeroRCCM"] ) : "";
								 $NumeroPIECE          = (isset($csvRow["NumeroPiece"]     ))?$stringFilter->filter($csvRow["NumeroPiece"]) : "";
								 $idPIECE              = (isset($csvRow["IdPiece"]         ))?$csvRow["IdPiece"]                            : "";
								 $rccmDocumentPathRoot = trim(trim($srcPath,"\\"),  DS     );
								 $numLocalite          = trim(substr($NumeroRCCM, 2, 3));
								 $numYear              = trim(substr($NumeroRCCM, 5, 4));
								 $cleanNumRccm         = $model->normalizeNum($NumeroRCCM, $numYear, $numLocalite);
								 $numKey               = trim(substr($cleanNumRccm, 10));								 
								 $localiteid           = (isset($localiteIDS[$numLocalite]))?$localiteIDS[$numLocalite] : $localiteIDS["OUA"] ;
								 $checkRccmRow         = $model->findRow($cleanNumRccm , "numero", null, false );
								 if( $checkRccmRow ) {
									 continue;
								 }
								 if( empty( $idPIECE ) ) {
									 continue;
								 }
                                 $documentName         = $cleanNumRccm."-FR.pdf";
                                 $rccmTmpDocument      = $rccmDocumentPathRoot. DS . $numLocalite. DS . $numYear      . DS . $idPIECE.".pdf";
                                 $psDocumentFilename   = $destPath            . DS . $numLocalite. DS . $numYear . DS . $cleanNumRccm. DS . $cleanNumRccm ."-PS.pdf";								 
								 									 
                                 $documentsPath        = $destPath. DS . $numLocalite . DS . $numYear . DS . $cleanNumRccm;								 
                                 if( file_exists($rccmTmpDocument) && ($overwriteOption=="SKIP")) {
									 continue;
								 }
                                 $doctype              = "Formulaire";
								 $formulaireName       = strtolower(preg_replace("/\s/","-", $NumeroPIECE));
								 if( (false!==stripos($formulaireName,"P2")) || (false!==stripos($formulaireName,"RCCM")) || (false!==stripos($formulaireName,"P0")) || (false!==stripos($formulaireName,"P1")) || (false!==stripos($formulaireName,"formulaire-rccm")) || (false!==stripos($formulaireName,"M1")) || (false!== stripos($formulaireName,"M0"))){
									 $documentName     = $cleanNumRccm."-FR.pdf";
									 $doctype          = "Formulaire";
									 $overwriteOption  = "ERASE";
								 } elseif( false!== stripos($formulaireName, "statut") ) {
									 $documentName     = $cleanNumRccm."-ST.pdf";
									 $doctype          = "STATUT";
									 $overwriteOption  = "ERASE";
						         } else {
									 $documentName     = $cleanNumRccm."-PS.pdf";
									 $doctype          = "Fond de dossier";
									 $overwriteOption  = "COMBINE";
								 }	
								 $documentFilename     = $documentsPath. DS .$documentName;
								 $localDataFolder      = $checkPath    . DS .$numLocalite . DS . $numYear. DS . $cleanNumRccm;
                                 if(file_exists( $localDataFolder.DS.$documentName)) {
									 $errorMessages[]  = sprintf("Le fichier %s existe déjà ", $documentName );
								    continue;
								 }									 
                                 $rccmDocumentPath     = $rccmDocumentPathRoot. DS . $numLocalite . DS . $numYear . DS . $cleanNumRccm;							 
								 $rccmDocument         = $rccmDocumentPath    . DS . $cleanNumRccm.".pdf";
								 
								 if(!is_dir( $rccmDocumentPath) ) {
									 if(!is_dir( $rccmDocumentPathRoot. DS . $numLocalite)) {
										 @chmod( $rccmDocumentPathRoot , 0777);
										 @mkdir( $rccmDocumentPathRoot. DS . $numLocalite);
									 }
									 if(!is_dir( $rccmDocumentPathRoot. DS . $numLocalite . DS . $numYear)) {
										 @chmod( $rccmDocumentPathRoot. DS . $numLocalite , 0777);
										 @mkdir( $rccmDocumentPathRoot. DS . $numLocalite . DS . $numYear);
									 }									 
								 }
								 $sigueUri             = preg_replace ("/^ /", "", $sigueUri);
								 $sigueUri             = preg_replace ("/ $/", "", $sigueUri);
								 $url                  = trim(trim($sigueUri,"\\"), DS)."/".$idPIECE;									 
								 $ch                   = curl_init();
								 curl_setopt($ch, CURLOPT_URL, $url);

								//On prépare les données à transmettre à SIGUE
								 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
								 curl_setopt($ch, CURLOPT_POST, FALSE);
								 curl_setopt($ch, CURLOPT_HTTPGET , TRUE);
								 curl_setopt($ch, CURLOPT_HEADER  , true);
								 curl_setopt($ch, CURLOPT_NOBODY  , false);
								 curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
								 curl_setopt($ch, CURLOPT_USERPWD , $sigueUsername.':'.$siguePassword);

								 $sigueDocumentFile = curl_exec($ch);
								 
								 file_put_contents($rccmTmpDocument, $sigueDocumentFile);

								 if( file_exists($rccmTmpDocument ) && (filesize($rccmTmpDocument) > 1000)) {
									 if(!is_dir( $destPath . DS . $numLocalite ) ) {
										 @chmod( $destPath , 0777);
										 @mkdir( $destPath . DS . $numLocalite);
									 }
									 if(!is_dir( $destPath . DS . $numLocalite . DS . $numYear ) ) {
										 @chmod( $destPath . DS . $numLocalite , 0777);
										 @mkdir( $destPath . DS . $numLocalite . DS . $numYear);
									 }
									 if(!is_dir( $destPath . DS . $numLocalite . DS . $numYear. DS . $cleanNumRccm) ) {
										 @chmod( $destPath . DS . $numLocalite . DS . $numYear , 0777);
										 @mkdir( $destPath . DS . $numLocalite . DS . $numYear. DS . $cleanNumRccm);
									 }									 
									 if( file_exists($documentFilename) && ($overwriteOption=="ERASE") && ($doctype!= "Fond de dossier")) {
										 @unlink($documentFilename);						
                                         $documentTransfered = copy( $rccmTmpDocument, $documentFilename );										 
									 }elseif(!file_exists($documentFilename) && ($doctype!= "Fond de dossier")) {
										 $documentTransfered = copy( $rccmTmpDocument, $documentFilename );	
									 }										 
									 if( file_exists($psDocumentFilename)) {
										 $combinedFiles              = array($rccmTmpDocument,$psDocumentFilename) ;
										 try {
											 $combinedFilePDF        = new Fpdi\Fpdi();
											 foreach( $combinedFiles as $combinedFile ) {
													  if( file_exists(  $combinedFile)) {
														  $pageCount =  $combinedFilePDF->setSourceFile($combinedFile);
														  for ( $j = 1; $j <= $pageCount; $j++) {
																$combinedTplIdx  = $combinedFilePDF->importPage($j);
																
																$combinedPDFSize = $combinedFilePDF->getTemplateSize($combinedTplIdx);
																$combinedFilePDF->AddPage( $combinedPDFSize['orientation'], $combinedPDFSize);
																$combinedFilePDF->useTemplate($combinedTplIdx);
														  }
													  }
											 }
											 $combinedFilePDF->Output("F", $psDocumentFilename);
											 $documentTransfered     = true;
										 } catch( Exception $e ) {
											 $errorMessages[]        = $e->getMessage();
											 $documentTransfered     = false;
										 }
									 } else {
										     $documentTransfered     = copy( $rccmTmpDocument, $psDocumentFilename);
									 }
									 /*$incoherencesDir                = $incoherencesPath . DS . "OUA". DS  . $rccmYear. DS . $rccmMois. DS . $jour. DS . $cleanNumRccm;
									 if( is_dir( $incoherencesDir )) {
										 @rmdir( $incoherencesDir );
									 }
									 if( true === $documentTransfered ) {
										 @unlink( $rccmTmpDocument);
										 $copied[]               = $psDocumentFilename;
									 }*/
								 }
								 curl_close($ch);
						}
					}				
				} else {
					$errorMessages[]  = "Le fichier CSV n'a pas pu être transféré";
				}
			} else {
				    $errorMessages[]  = "Le fichier CSV n'a pas pu être transféré";
			}
			 //print_r(count($copied));print_r($copied);die();			
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d documents ont été copiés avec succès", count($copied) )));
				    exit;
			    }
				$this->setRedirect(sprintf("%d documents ont été copiés avec succès", count($copied)), "success");
				$this->redirect("admin/registres/importsiguefiles/annee/".$checkedYear."/mois/".$checkedMonth); 
			} 
		}
		$this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;
        $this->render("importsiguefiles");
	}
		
	
	
	public function downloadsiguefilesAction()
	{
		@ini_set('memory_limit', '512M');
		
		$this->view->title          = "Importer les documents";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
		$months                     = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData            = array("srcpath" =>"C:\\SIGUE\\ORIGINAL","destpath"=>"C:\\SIGUE\\A_RETRAITER","annee"=>2016,"checkpath"=>"G:\\ERCCM","incoherencespath"=>"G:\\ERCCM_INCOHERENCES","overwrite"=>"COMBINE","sigueuser"=>"compte.OPS","sigueuri"=>"http://10.60.16.17:8014/Piece/ShowFile/","siguepwd"=>"P@ssw0rd","sigueuauth_type"=>CURLAUTH_NTLM);
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$combined                   = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
			set_time_limit(0);
			$postData               = $this->_request->getPost();
			$srcPath                = (isset($postData["srcpath"]         ))? $postData["srcpath"]               : "C:\\SIGUE\\ORIGINAL";
			$destPath               = (isset($postData["destpath"]        ))? $postData["destpath"]              : "C:\\SIGUE\\A_RETRAITER";
		    $checkPath              = (isset($postData["checkpath"]       ))? $postData["checkpath"]             : "G:\\ERCCM";
			$sigueUri               = (isset($postData["sigueuri"]        ))? $postData["sigueuri"]              : "http://10.60.16.17:8014/Piece/ShowFile/";
			$sigueAuthType          = (isset($postData["sigueuauth_type"] ))? $postData["sigueuauth_type"]       : CURLAUTH_NTLM;
			$sigueUsername          = (isset($postData["sigueuser"]       ))? $postData["sigueuser"]             : "compte.OPS";
			$siguePassword          = (isset($postData["siguepwd"]        ))? $postData["siguepwd"]              : "P@ssw0rd";
			$overwriteOption        = (isset($postData["overwrite"]       ))? strtoupper($postData["overwrite"]) : "COMBINE";
			
			if(!is_dir( $srcPath )) {
				$errorMessages[]    = sprintf("Le dossier source %s ne semble pas valide", $srcPath );
			}
			if(!is_dir( $destPath )) {
				$errorMessages[]    = sprintf("Le dossier de destination %s ne semble pas valide", $destPath);
			}
			if(!is_dir( $checkPath )) {
				$errorMessages[]    = "Le chemin de vérification n'est pas valide";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
			
			//On crée un validateur de filtre
			$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			
			$documentsUploadAdapter   = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv","xls","xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max"=>"100MB"));
			
			$basicFilename            = $documentsUploadAdapter->getFileName("registres", false );
			
			$me                       = Sirah_Fabric::getUser();
			$userTable                = $me->getTable();
			$dbAdapter                = $userTable->getAdapter();
			$prefixName               = $userTable->info("namePrefix");
			$copied                   = array();
						 			
            $csvDestinationName       = $destPath. DS . time() . "mySigueData.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName,"overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader        = array("NomCommercial","Nom","Prenom","Telephone","NumeroPiece","IdPiece","LibelleTypePiece","IdTypePiece");
					$csvAdapter       = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines         = $csvAdapter->getLines();
					$csvItems         = 1;
					if( isset($csvLines[0]) ) {
					    unset($csvLines[0]);
					}					
				    if( count(   $csvLines ) ) {
						foreach( $csvLines as $csvKey => $csvRow ) {
							     $NomCommercial        = (isset($csvRow["NomCommercial"]   ))?$stringFilter->filter($csvRow["NomCommercial"])    : "";
								 $NumeroPIECE          = (isset($csvRow["NumeroPiece"]     ))?$stringFilter->filter($csvRow["NumeroPiece"])      : "";
								 $LibelleTypePiece     = (isset($csvRow["LibelleTypePiece"]))?$stringFilter->filter($csvRow["LibelleTypePiece"]) : "";
								 $Telephone            = (isset($csvRow["Telephone"]       ))?$stringFilter->filter($csvRow["Telephone"])        : "";
								 $idPIECE              = (isset($csvRow["IdPiece"]         ))?$csvRow["IdPiece"]                                 : "";
								 $rccmDocumentPathRoot = trim(trim($srcPath,"\\"),  DS     );
								 $folderName           = preg_replace("/\s/","_" , $NomCommercial."_".$Telephone );
								  
                                 $documentName         = $folderName."-FR.pdf";
                                 $rccmTmpDocument      = $rccmDocumentPathRoot   . DS.$idPIECE.".pdf";
                                 $psDocumentFilename   = $destPath.DS.$folderName. DS . $folderName."-PS.pdf";
                                  								 
								 if( file_exists( $rccmTmpDocument ) ) {
									 continue;
								 }									 
                                 $documentsPath        = $destPath.DS.$folderName. DS . $folderName;								 
                                 $doctype              = "Formulaire";
								 $formulaireName       = strtolower(preg_replace("/\s/","-", $LibelleTypePiece));
								 if( (false!==stripos($formulaireName,"P2")) || (false!==stripos($formulaireName,"RCCM")) || (false!==stripos($formulaireName,"P0")) || (false!==stripos($formulaireName,"P1")) || (false!==stripos($formulaireName,"formulaire-rccm")) || (false!==stripos($formulaireName,"M1")) || (false!== stripos($formulaireName,"M0"))){
									 $documentName     = $folderName."-FR.pdf";
									 $doctype          = "Formulaire";
									 $overwriteOption  = "ERASE";
								 } elseif( false!== stripos($formulaireName, "statut") ) {
									 $documentName     = $folderName."-ST.pdf";
									 $doctype          = "STATUT";
									 $overwriteOption  = "ERASE";
						         } else {
									 $documentName     = $folderName."-PS.pdf";
									 $doctype          = "Fond de dossier";
									 $overwriteOption  = "COMBINE";
								 }									 
                                 $rccmDocumentPath     = $rccmDocumentPathRoot. DS . $folderName . DS . $folderName;							 
								 $rccmDocument         = $rccmDocumentPath    . DS . $folderName.".pdf";
								 
								 if(!is_dir( $rccmDocumentPath) ) {
									 if(!is_dir( $rccmDocumentPathRoot. DS . $folderName)) {
										 @chmod( $rccmDocumentPathRoot , 0777);
										 @mkdir( $rccmDocumentPathRoot. DS . $folderName);
									 }								 
								 }
								 $documentFilename     = $documentsPath. DS .$documentName;
								 $sigueUri             = preg_replace ("/^ /", "", $sigueUri);
								 $sigueUri             = preg_replace ("/ $/", "", $sigueUri);
								 $url                  = trim(trim($sigueUri,"\\"), DS)."/".$idPIECE;									 
								 $ch                   = curl_init();
								 curl_setopt($ch, CURLOPT_URL, $url);

								//On prépare les données à transmettre à SIGUE
								 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
								 curl_setopt($ch, CURLOPT_POST, FALSE);
								 curl_setopt($ch, CURLOPT_HTTPGET , TRUE);
								 curl_setopt($ch, CURLOPT_HEADER  , true);
								 curl_setopt($ch, CURLOPT_NOBODY  , false);
								 curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
								 curl_setopt($ch, CURLOPT_USERPWD , $sigueUsername.':'.$siguePassword);

								 $sigueDocumentFile = curl_exec($ch);
								 
								 file_put_contents($rccmTmpDocument, $sigueDocumentFile);

								 if( file_exists($rccmTmpDocument) && (filesize($rccmTmpDocument) > 1000)) {
									 if(!is_dir( $destPath . DS . $folderName) ) {
										 @chmod( $destPath , 0777);
										 @mkdir( $destPath . DS . $folderName);
									 }								 
									 if( file_exists($documentFilename) && ($overwriteOption=="ERASE") && ($doctype!= "Fond de dossier")) {
										 @unlink($documentFilename);						
                                         $documentTransfered = copy( $rccmTmpDocument, $documentFilename );										 
									 }elseif(!file_exists($documentFilename) && ($doctype!= "Fond de dossier")) {
										 $documentTransfered = copy( $rccmTmpDocument, $documentFilename );	
									 }										 
									 if( file_exists($psDocumentFilename)) {
										 $combinedFiles              = array($rccmTmpDocument,$psDocumentFilename) ;
										 try {
											 $combinedFilePDF        = new Fpdi\Fpdi();
											 foreach( $combinedFiles as $combinedFile ) {
													  if( file_exists(  $combinedFile)) {
														  $pageCount =  $combinedFilePDF->setSourceFile($combinedFile);
														  for ( $j = 1; $j <= $pageCount; $j++) {
																$combinedTplIdx  = $combinedFilePDF->importPage($j);																
																$combinedPDFSize = $combinedFilePDF->getTemplateSize($combinedTplIdx);
																$combinedFilePDF->AddPage( $combinedPDFSize['orientation'], $combinedPDFSize);
																$combinedFilePDF->useTemplate($combinedTplIdx);
														  }
													  }
											 }
											 $combinedFilePDF->Output("F", $psDocumentFilename);
											 $documentTransfered     = true;
										 } catch( Exception $e ) {
											 $errorMessages[]        = $e->getMessage();
											 $documentTransfered     = false;
										 }
									 } else {
										     $documentTransfered     = copy( $rccmTmpDocument, $psDocumentFilename);
									 }
								 }
								 curl_close($ch);
						}
					}				
				} else {
					$errorMessages[]  = "Le fichier CSV n'a pas pu être transféré";
				}
			} else {
				    $errorMessages[]  = "Le fichier CSV n'a pas pu être transféré";
			}
			 //print_r(count($copied));print_r($copied);die();			
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d documents ont été copiés avec succès", count($copied) )));
				    exit;
			    }
				$this->setRedirect(sprintf("%d documents ont été copiés avec succès", count($copied)), "success");
				$this->redirect("admin/registres/downloadsiguefiles/annee/".$checkedYear."/mois/".$checkedMonth); 
			} 
		}
		$this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;
        $this->render("downloadsiguefiles");
	}
	
	public function combineAction()
	{		
		@ini_set('memory_limit', '512M');
		
		$this->view->title     = "Copier et Fusionner les documents";
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");
		$getParams             = $this->_request->getParams();
		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$months                = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData       = array("srcpath"=>"F:\\FNRCCM2017-2018/COMBINE","destpath"=>"F:\\FNRCCM2017-2018/DEST","checkpath"=>"G:\\ERCCM",
		                               "localites"=> array("OUA","BBD","DDG","KDG"),"annee"=>2016,"mois"=>"1","jour"=>null);
		
		$defaultData           = array_merge( $defaultInitData, $getParams);
		$combined              = array();
		$errorMessages         = array();
		
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();			
			$srcPath           = (isset($postData["srcpath"]  ))? $postData["srcpath"]  : "F:\\FNRCCM2017-2018/DOCSCAN/COMBINE";
			$destPath          = (isset($postData["destpath"] ))? $postData["destpath"] : "F:\\FNRCCM2017-2018/DOCSCAN/DEST";
			$checkPath         = (isset($postData["checkpath"]))? $postData["checkpath"]: "G:\\ERCCM";
			$annee             = (isset($postData["annee"]    ))? $postData["annee"]    : 2015;
			$mois              = (isset($postData["mois"]     ))? $postData["mois"]     : 1;
			$jour              = (isset($postData["jour"]     ))? $postData["jour"]     : "01082016";
			$month             = "JANVIER";
			if(!is_dir( $srcPath  )) {
				$errorMessages[]  = "La source des documents n'a pas été trouvée";
			}
            if(!is_dir( $destPath )) {
				$errorMessages[]  = "La destination des documents n'a pas été trouvée";
			}
			if(!isset($months[$mois])) {
				$errorMessages[]  = "Veuillez sélectionner un mois valide";
			} else {
				$month            = strtoupper($months[$mois]);
			}			
			$logger               = new Logger('MyLogger');
            //$pdfToText          = XPDF\PdfToText::create(array("pdftotext.binaries" => "F:\webserver\www\binaries\Xpdf\pdftotext.exe","pdftotext.timeout"=> 30,), $logger);
			for(   $dayValue=1;$dayValue<=31; $dayValue++) {	
                   $jour                  = sprintf("%02d%02d%04d", $dayValue, $mois, $annee);			
				   $documentsSrcPath      = $srcPath .DS . $annee . DS . $month . DS . $jour;
				   $documentsRootFolders  = (is_dir( $documentsSrcPath )) ? glob($documentsSrcPath."/*", GLOB_ONLYDIR) : array();					
                   $dateDemande           = sprintf("%04d-%02d-%02d 00:00:00", $annee, $mois, $dayValue );
					if(!is_dir( $documentsSrcPath )) {
						continue;
						$errorMessages[]  = sprintf("Le dossier %s n'a pas été trouvé", $documentsSrcPath);
					}
					$i                    = 0;
					$combined             = array();
					//print_r($documentsSrcPath);die();
					if( count(   $documentsRootFolders )) {
						foreach( $documentsRootFolders as $documentsRegistreFolder ) {				     
								 $i++;
								 $documentsRegistresFiles = glob( $documentsRegistreFolder."/*.pdf");
								 $nomCommercial           = Sirah_Filesystem::mb_basename($documentsRegistreFolder);
								 $rccmFoldername          = preg_replace("/[^a-z0-9]/i","_", $nomCommercial);						 
								 $combined[$jour][$i]     = array("files"=>array(),"folders"=>array(),"foldername1"=>$nomCommercial,"foldername"=>$rccmFoldername,"folderpath"=>$documentsRegistreFolder,"nomcommercial"=>$nomCommercial,"numrc"=>null);
								 if( count(   $documentsRegistresFiles ) ) {
									 foreach( $documentsRegistresFiles as $documentsRegistresFilename ) {
											  $registreFilename = str_ireplace(".pdf", "", trim(basename($documentsRegistresFilename)));
											  if((false===stripos($registreFilename,"-PS")) && (false===stripos($registreFilename,"-FR")) && (false===stripos($registreFilename,"-ST"))) {
												 $combined[$jour][$i]["files"][] = $documentsRegistresFilename;
												 $searchNomCommercial = str_replace(array(' ',"_"), array('',""), $nomCommercial);
												 $sigueRccmFound      = $model->findsiguercmms(array("nomcommercial"=>$searchNomCommercial,"annee"=>$annee,"mois"=>sprintf("%02d",$mois),"day"=>sprintf("%02d",$dayValue)));
												 if( isset( $sigueRccmFound[0]["cleanum"] ) ) {
													 //print_r($sigueRccmFound);die();
													 $cleanNumRccm    = $sigueRccmFound[0]["cleanum"];
													 if(!empty( $cleanNumRccm) && (false !==stripos($cleanNumRccm,"BFOUA20") )) {
														 $combined[$jour][$i]["numrc"] = $cleanNumRccm;														 														  
													 }	 								     
												 }
											  }									  
									 }
								 }
						}	
                        //print_r($combined[$jour]);die();						
						if( count(   $combined[$jour] )) {
							foreach( $combined[$jour] as $combinedFolders ) {
									 $rccmFiles        = $combinedFolders["files"];
									 $rccmFoldername   = $combinedFolders["foldername"];
									 $rccmFolderpath   = $combinedFolders["folderpath"];
									 $nomCommercial    = $combinedFolders["nomcommercial"];
									 $rccmDestPath     = $destPath. DS . $annee . DS . $month. DS . $jour . DS . preg_replace("/[^a-z0-9]/i","_",$nomCommercial);
									 $numRccm          = "NUMRCCM" ;
									 if(!empty($combinedFolders["numrc"]) && (false!==stripos($combinedFolders["numrc"],"BFOUA20"))) {
									     $numRccm      = $combinedFolders["numrc"];
										 $rccmDestPath = $destPath. DS . $annee . DS . $month. DS . $jour . DS . $numRccm;
									 }
                                     $copied[]         = $rccmDestPath;									 
                                       										 
									 if(!is_dir( $rccmDestPath ) && count( $rccmFiles )) {
										 if(!is_dir( $destPath . DS . $annee ) ) {
											 @chmod( $destPath, 0777);
											 @mkdir( $destPath . DS . $annee);
										 }
										 if( !is_dir( $destPath . DS . $annee . DS . $month ) ) {
											 @chmod(  $destPath . DS . $annee, 0777);
											 @mkdir(  $destPath . DS . $annee . DS . $month);
										 }
										 if( !is_dir( $destPath . DS . $annee . DS . $month . DS . $jour ) ) {
											 @chmod(  $destPath . DS . $annee . DS . $month);
											 @mkdir(  $destPath . DS . $annee . DS . $month . DS . $jour);
										 }
											 @chmod(  $destPath . DS . $annee . DS . $month . DS . $jour, 0777);
											 mkdir(   $rccmDestPath);
											 chmod(   $rccmDestPath, 0777);
										 
										try {
										 $rccmPDF          = new Fpdi\Fpdi();
										 foreach( $rccmFiles as    $rccmFilename ) {
												  $pageCount = $rccmPDF->setSourceFile($rccmFilename);
												  for ( $j = 1;  $j <= $pageCount; $j++) {
														$tplIdx      = $rccmPDF->importPage($j);
														$rccmPDFSize = $rccmPDF->getTemplateSize($tplIdx);
														$rccmPDF->AddPage($rccmPDFSize['orientation'], $rccmPDFSize);
														$rccmPDF->useTemplate($tplIdx);
												  }
												  $filename          = trim(basename($rccmFilename));
												  $fileFolderName    = str_ireplace(".pdf", "", $filename);
												  $rccmFilePath      = $rccmDestPath. DS .$filename;
												  
												  if((false!==stripos( $fileFolderName,"RCCM"))) {
													  $rccmFilePath  = $rccmDestPath. DS . $numRccm."-FR.pdf";
												  }
												  if((false!==stripos( $fileFolderName,"STATUT")) || ( $filename == "AC.pdf")) {
													  $rccmFilePath  = $rccmDestPath. DS . $numRccm."-ST.pdf";
												  }	                                                 												  
												  if((false===stripos($fileFolderName,"CNSS")) && (false===stripos($fileFolderName,"CPC")) && (false===stripos( $fileFolderName,"CNIB")) && (false===stripos($fileFolderName,"DH")) && (false===stripos( $fileFolderName,"PASS"))
													  && (false===stripos($fileFolderName,"PROCURATION")) && (false===stripos( $fileFolderName,"PUH")) && (false===stripos( $fileFolderName,"FCPC")) && (false===stripos($fileFolderName,"FV"))
													  && (false===stripos($fileFolderName,"DC")) && (false===stripos( $fileFolderName,"CR")) && (false===stripos( $fileFolderName,"AM")) && (false===stripos($fileFolderName,"CASIER"))
													  && (false===stripos($fileFolderName,"CB")) && (false===stripos( $fileFolderName,"FL")) && (false===stripos( $fileFolderName,"CJ")) && (false===stripos($fileFolderName,"PROC")) ) {														  
													  if(false == copy( $rccmFilename, $rccmFilePath)) {
														  $errorMessages[]  = sprintf("La copie du fichier %s dans le dossier %s a echoué", $rccmFilename, $rccmFilePath);
													  }
												  }	
												  if( (false!==stripos($fileFolderName,"IFU")) || (false!==stripos($fileFolderName,"RCCM")) || (false!==stripos($fileFolderName,"STATUT")) || ( $filename == "AC.pdf") ) {
													  if( false === copy( $rccmFilename, $rccmFilePath)) {
														  $errorMessages[]  = sprintf("La copie du fichier %s dans le dossier %s a echoué", $rccmFilename, $rccmFilePath);
													  }
												  }													  
										 }
										 $rccmDestPathFullDocument = $rccmDestPath. DS .$numRccm."-PS.pdf";
										 $rccmPDF->Output( "F", $rccmDestPathFullDocument);
									    } catch(Exception $e ) {
											$errorMessages[]       = $e->getMessage();
										}
									 }
							}
						}
					} else {
						$errorMessages[]  = sprintf("Aucun dossier valide n'a été retrouvé dans '%s'", $documentsSrcPath);
					}
			}
            //print_r(count($copied));print_r($copied);die();			
            if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d dossiers ont été créés avec succès", $i)));
				    exit;
			    }
				$this->setRedirect(sprintf("%d dossiers ont été créés avec succès", $i), "success");
				$this->redirect("admin/registres/combine/annee/".$annee."/mois/".$mois); 
			}				
		}				
        $this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;
	}


	
	
	public function createcsvAction()
	{		
		@ini_set('memory_limit', '512M');
		
		$this->view->title        = "Créer des fichiers CSV";
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		$modelTable               = $model->getTable();
		$prefixName               = $tablePrefix    = $modelTable->info("namePrefix");
		$dbAdapter                = $modelTable->getAdapter();
		$getParams                = $this->_request->getParams();
		$localites                = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                   = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                  "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
		$defaultInitData          = array("srcpath"=>"C:\\ERCCM\\DATA","checkpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS","destpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS","localite"=>"OUA","annee"=>2000,"nbre_documents"=>1000,"sigar_dbhost" => "");
				
		$sigarDbSourceParams      = array(
				                          "sigardb_host"     => (isset($getParams["dbsource_host"])    ? $getParams["dbsource_host"]     : "localhost" ),
				                          "sigardb_username" => (isset($getParams["dbsource_user"])    ? $getParams["dbsource_user"]     : "root"  ),
				                          "sigardb_password" => (isset($getParams["dbsource_password"])? $getParams["dbsource_password"] : "mebfSir@h1217" ),
				                          "sigardb_dbname"   => (isset($getParams["dbsource_name"])    ? $getParams["dbsource_name"]     : "sigar" ),
				                          "isDefaultAdapter" => 0);
		
		$defaultData              = array_merge( $defaultInitData, $sigarDbSourceParams, $getParams);
		$csvRows                  = array();
		$errorMessages            = array();
		
		if( $this->_request->isPost()) {        
			$postData             = $this->_request->getPost();			
			$srcPath              = (isset($postData["srcpath"]  ))? $postData["srcpath"]       : "F:\\FNRCCM2017-2018/DOCSCAN/COMBINE";
			$destPath             = (isset($postData["destpath"] ))? $postData["destpath"]      : "F:\\FNRCCM2017-2018/DOCSCAN/DEST";
			$checkPath            = (isset($postData["checkpath"]))? $postData["checkpath"]     : "G:\\ERCCM";
			$annee                = (isset($postData["annee"]    ))? intval($postData["annee"]) : 2000;
			$localite             = (isset($postData["localite"] ))? $postData["localite"]      : "OUA";
			$nbreDocuments        = (isset($postData["nbre_documents"] ))? intval( $postData["nbre_documents"] ) : 1000;
			$sigarDbSourceParams  = array(
				                          "host"     => (isset($postData["sigardb_host"])    ? $postData["sigardb_host"]     : "localhost" ),
				                          "username" => (isset($postData["sigardb_username"])? $postData["sigardb_username"] : "root"  ),
				                          "password" => (isset($postData["sigardb_password"])? $postData["sigardb_password"] : "mebfSir@h1217" ),
				                          "dbname"   => (isset($postData["sigardb_dbname"])  ? $postData["sigardb_dbname"]   : "sigar" ),
				                          "isDefaultAdapter" => 0);
			try{
				$dbSource         = Zend_Db::factory("Pdo_Mysql", $sigarDbSourceParams);
				$dbSource->getConnection();
			} catch( Zend_Db_Adapter_Exception $e ) {
				$errorMessages[]  = "Les paramètres de la base de donnée de SIGARD ne sont pas valides, debogage: ".$e->getMessage();
			} catch( Exception $e ) {
				$errorMessages[]  = "Les paramètres de la base de donnée de SIGARD ne sont pas valides, debogage: ".$e->getMessage();
			}							  
			if(!is_dir( $srcPath  )) {
				$errorMessages[]  = sprintf("La source des documents `%s` n'a pas été trouvée", $srcPath );
			}
            if(!is_dir( $destPath )) {
				$errorMessages[]  = sprintf("La destination des documents `%s` n'a pas été trouvée",  $destPath);
			}
			if(!isset($localites[$localite])) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			}  
			$documentSrcRootPath  = $srcPath  . DS . $localite . DS . $annee;
            $documentDestRootPath = $destPath . DS . $localite . DS . $annee;
			if(!is_dir($destPath . DS . $localite) && is_dir($destPath)) {
				@chmod($destPath, 0777);
				@mkdir($destPath . DS . $localite);
			}
            if(!is_dir($destPath . DS . $localite . DS . $annee) && is_dir($destPath . DS . $localite)) {
				@chmod($destPath . DS . $localite, 0777);
				@mkdir($destPath . DS . $localite . DS . $annee);
			}
            if( !is_dir( $documentSrcRootPath ) ) {
				$errorMessages[]  = sprintf(" Vous n'avez pas respecté la structure des dossiers source %s", $documentSrcRootPath );
			}
            if( !is_dir( $documentDestRootPath ) ) {
				$errorMessages[]  = sprintf(" Vous n'avez pas respecté la structure des dossiers de destination %s", $documentDestRootPath);
			}
			if( !intval( $nbreDocuments ) ) {
				$errorMessages[]  = "Veuillez indiquer le nombre total de documents à récupérer";
			}
			$rccmSearchKey        = sprintf("BF%s%dA", $localite, $annee);
			$rccmPSFiles          = glob( $documentSrcRootPath."/*/".$rccmSearchKey."*-PS.pdf");
			$i                    = 0;
			if( count(   $rccmPSFiles ) ) {
				foreach( $rccmPSFiles as $rccmPSFile) {
						 $csvRowData                             = array();
					     $numRccm                                = $numero = str_ireplace(array("-FR","-ST",".pdf","-PS"),"", basename($rccmPSFile));
						 $checkRccmRow                           = $model->findRow( $numRccm, "numero", null, false );
						 $checkIndexationFiles                   = (is_dir($checkPath))?glob($checkPath."/*/".$localite."/".$annee."/".$numRccm.".pdf" ) : array();
						 if($i===$nbreDocuments ) {
							 break;
						 }
						 if( count($checkIndexationFiles) ) {
							 $fileFolder                         = $checkPath."/*/".$localite."/".$annee."/".$numRccm.".pdf"; 
							 //$errorMessages[]                  = sprintf("Le numéro RCCM N° %s existe déjà : %s", $numero, $fileFolder );
							 continue;
						 }
						 if( $checkRccmRow ) {
							 continue;
						 }
                         if( file_exists($documentDestRootPath . DS . $numRccm.".pdf") ) {
							 continue;
						 }							 
						 $searchInDbSql                          = "SELECT * FROM ".$tablePrefix ."rccm_registre_indexation WHERE numero=\"".$numRccm."\"";
						 $contentRegistre                        = $dbAdapter->fetchRow( $searchInDbSql, array(), 5);
						 $csvRowData["numero"]                   = $numRccm;
						 if($contentRegistre ) {							
							$csvRowData["nom"]                   = strtoupper($contentRegistre->nom);
							$csvRowData["prenom"]                = strtoupper($contentRegistre->prenom);
							$csvRowData["adresse"]               = $contentRegistre->adresse;
							$csvRowData["telephone"]             = $contentRegistre->telephone;
							$csvRowData["date_naissance"]        = $contentRegistre->date_naissance;
							$csvRowData["lieu_naissance"]        = strtoupper($contentRegistre->lieu_naissance);
							$csvRowData["nom_commercial"]        = strtoupper($contentRegistre->nom_commercial);
							$csvRowData["description"]           = $contentRegistre->description;
							$csvRowData["sexe"]                  = $contentRegistre->sexe;
							$csvRowData["passport"]              = $contentRegistre->passport;
                            $csvRowData["nationalite"]           = $contentRegistre->nationalite;
                            $csvRowData["date_enregistrement"]   = $contentRegistre->date_enregistrement;
                            $csvRowData["situation_matrimonial"] = "Celibataire";								
						 } else {							
							if( empty( $errorMessages )) {
								$searchNum            = substr($numero,0,10).intval(substr($numero,10,14));
								$searchNum2           = substr($numero,0,10).sprintf("%02d",intval(substr($numero,10,14)));
								$searchNum3           = substr($numero,0,10).sprintf("%03d",intval(substr($numero,10,14)));
							
								$dbSourceSelect       = $dbSource->select()->from(array("A" => "archive"), array("A.analyse","A.date_deb","A.id_archive"))
																	       ->join(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive","F.nomged_fichier"))
																	       ->where("F.nom_fichier LIKE ?"  , "%".$searchNum."%")
																		   ->orWhere("F.nom_fichier LIKE ?", "%".$searchNum2."%")
																		   ->orWhere("F.nom_fichier LIKE ?", "%".$searchNum3."%")
																		   ->orWhere("F.nom_fichier LIKE ?", "%".$numero."%");
								$registres            = $dbSource->fetchAll( $dbSourceSelect );
								if( count( $registres )) {
									$registre         = $registres[0];
									$analyse          = $registreStr   = $cleanTxt  = trim( $registre["analyse"]) ;
									$numRegistre      = $nomCommercial = $telephone = $dirigeant = $lastname = $firstname = $exploitant = $telToReplace = "";
									preg_match('#NOM COMMERCIAL\s*:\s*(?P<nomcommercial>[^.;,]+)#', $registreStr, $nomCommercialMatches);
									preg_match('#NOM COM\s*:\s*(?P<nomcommercial>[^.;,]+)#', $registreStr, $nomComMatches);
									preg_match('#DEMINATION\s*:\s*(?P<dirigeant>[^.;,]+)#', $registreStr, $exploitantMatches);
									preg_match('#EXPLOITANT\s*:\s*(?P<dirigeant>[^.;,]+)#', $registreStr, $exploitantMatches2);
									preg_match('#RCCM\s*:\s*(?P<numero>[^.,;]+)#', $registreStr, $numeroMatches);
									preg_match('#DIRIGEANT\s*:\s*(?P<dirigeant>[^.,;]+)#', $registreStr, $dirigeantMatches);
									preg_match('#TELEPHONE\s*:\s*(?P<telephone>[^.,;]+)#', $registreStr, $telephoneMatches);
									if( isset( $dirigeantMatches["dirigeant"])) {
										$dirigeant = trim($dirigeantMatches["dirigeant"]);
									}
									if( isset( $telephoneMatches["telephone"])) {
										$telephone = $telToReplace = trim($telephoneMatches["telephone"]);
									}
									if( isset( $nomCommercialMatches["nomcommercial"])) {
										$nomCommercial = trim(str_replace(array("TELEPHONE",":", $telToReplace), "", $nomCommercialMatches["nomcommercial"])) ;
									} elseif( isset( $nomComMatches["nomcommercial"])) {
										$nomCommercial = trim(str_replace(array("TELEPHONE",":", $telToReplace), "", $nomComMatches["nomcommercial"])) ;
									} 
									if(!empty( $dirigeant )) {
										$dirigeantToArray    = explode(" ", $dirigeant );
										if( $dirigeantToArray[0] ) {
											$lastname        = $dirigeantToArray[0];
											unset($dirigeantToArray[0]);
										}
										$firstname           = implode(" ", $dirigeantToArray );
									} elseif(isset($exploitantMatches["dirigeant"])) {
										$dirigeantToArray    = explode(" ", $exploitantMatches["dirigeant"]);
										if( $dirigeantToArray[0] ) {
											$lastname        = $dirigeantToArray[0];
											unset($dirigeantToArray[0]);
										}
											 $firstname           = implode(" ", $dirigeantToArray );
									} elseif( $exploitantMatches2["dirigeant"] ) {
											  $dirigeantToArray    = explode(" ", $exploitantMatches2["dirigeant"]);
											  if( $dirigeantToArray[0] ) {
												  $lastname        = $dirigeantToArray[0];
												  unset($dirigeantToArray[0]);
											  }
										$firstname    = implode(" ", $dirigeantToArray );
									}                   				
										$csvRowData["nom"]                   = strtoupper($lastname);
										$csvRowData["prenom"]                = strtoupper($firstname);
										$csvRowData["telephone"]             = $telephone;
										$csvRowData["adresse"]               = "";
										$csvRowData["date_naissance"]        = "";
										$csvRowData["lieu_naissance"]        = "";
										$csvRowData["passport"]              = "";
										$csvRowData["sexe"]                  = "M";
										$csvRowData["date_naissance"]        = "";
										$csvRowData["date_enregistrement"]   = "";
										$csvRowData["nationalite"]           = "BF";
										$csvRowData["situation_matrimonial"] = "Célibataire";
										$csvRowData["nom_commercial"]        = strtoupper($nomCommercial);
										$csvRowData["description"]           = strtoupper(trim(str_replace(array("TELEPHONE",":","NOM COMMERCIAL","","DEMINATION","RCCM","DIRIGEANT",$numero,$searchNum2,$searchNum3,
																						  $searchNum,$telToReplace,$lastname,$firstname,$nomCommercial,$telephone),"", $registreStr)));
								}
							}
						 }		
                         if( empty($csvRowData["nationalite"] ) ) {
							 $csvRowData["nationalite"]              = "BF";
						 }
                        				 
						 //On copie le fichier dans la destination
						 $documentFilename                           = $documentDestRootPath . DS . $numRccm.".pdf"; 
						 if( true == copy( $rccmPSFile, $documentFilename ) ) {
							 $csvRows[$numRccm]                      = $csvRowData;
                             $i++;							 
						 }
				}
			}
			//print_r($csvRows);die();
			if( count( $csvRows )) {
				$csvHeader   = array("numero","nom_commercial","date_enregistrement","description","nom","prenom","lieu_naissance","date_naissance","sexe","adresse","telephone","passport","nationalite","situation_matrimonial");
				$csvFilename = time().sprintf("rccmExcelLot-BF%s%d_du%s.csv", $localite, $annee, date("dmY"));
				$csvTmpFile  = $documentDestRootPath . DS .  $csvFilename;	
				$csvAdapter  = Sirah_Filesystem_File::fabric("Csv", array("filename"=> $csvTmpFile,"has_header" => true, "header" => $csvHeader ) , "wb+" );
				if( $csvAdapter->save( $csvRows ) ) {
					$this->_helper->Message->addMessage( sprintf("Votre opération de création du fichier CSV s'est produite avec succès"), "success");
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					//$this->getResponse()->setHeader("Content-Type" , "text/csv");					
					echo $csvAdapter->Output( $csvFilename );
					@unlink( $csvTmpFile );
					exit;
				} else {
				    $errorMessages[]  = " Aucun RCCM n'a pu être exporté ";
				}
			}
            if( !empty( $errorMessages ) ){
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->layout->disableLayout(true);
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $errorMessage){
						 $this->_helper->Message->addMessage($errorMessage , "error");
				}
			}  				
		}				
		$this->view->data         = $defaultData;
		$this->view->annees       = $annees;
	    $this->view->localites    = $localites;
		
		$this->render("exportcsv");			
	} 
	
	
	
	
	public  function mergefoldersAction()
	{
		@ini_set('memory_limit', '512M');
		$this->view->title  = "FUSION DES DOSSIERS";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("annee"=>2016,"localite"=>"OUA","srcpath"=>"F:\\FNRCCM2017-2018\\OPS","destpath"=>"F:\\ERCCM2");
		$errorMessages      = array();
		$successMessages    = array();
	
		$mergedItems        = $dataItems = array();
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
	
		if( $this->_request->isPost( )) {
			$postData             = $this->_request->getPost();
			$srcPath              = ( isset($postData["srcpath"]  )) ? $postData["srcpath"]               : $defaultData["srcpath"];
			$destPath             = ( isset($postData["destpath"] )) ? $postData["destpath"]              : $defaultData["destpath"];
			$rccmLocalite         = ( isset($postData["localite"] )) ? $postData["localite"]              : $defaultData["localite"];
			$rccmAnnee            = ( isset($postData["annee"]    )) ? intval($postData["annee"])         : $defaultData["annee"];
			$overwriteOption      = ( isset($postData["overwrite"])) ? strtoupper($postData["overwrite"]) : "ERASE";
           					 
			if(!is_dir( $srcPath ) ) {
				$errorMessages[]  = sprintf( "Le dossier source %s  n'existe pas. Veuillez vérifier.",  $srcPath);
			} else {
				$dataItems        = glob( $srcPath. DS ."*", GLOB_ONLYDIR);
			}
			if(!is_dir($destPath ) ) {
				$errorMessages[]  = sprintf("Le dossier de destination %s n'a pas été trouvé", $destPath);
			}
			if(!isset(  $localites[$rccmLocalite] ) ) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			}
			if(!count( $dataItems ) ) {
				$errorMessages[]  = "Le dossier source ne contient aucun document";
			}
			if(!isset( $annees[$rccmAnnee] )) {
				$errorMessages[]  = "Veuillez sélectionner une année valide";
			}
			if( empty(   $errorMessages ) ) {				
				foreach( $dataItems    as $rccmDirectory ) {
					     $dirRccmNum    = Sirah_Filesystem::mb_basename($rccmDirectory);
						 $rccmTypeCode  = trim(substr( $dirRccmNum, 9, 1));
						 $rccmId        = trim(substr( $dirRccmNum, 10));
						 
						 if(($rccmTypeCode!=="A") && ($rccmTypeCode!=="B") && ($rccmTypeCode!=="M") && ($rccmTypeCode!=="S")) {
							 $errorMessages[]  = $message = sprintf("Le type du registre `%s` semble invalide dans le dossier %s", $rccmTypeCode, $dirRccmNum);
							 $errorItems[$dirRccmNum]     = $message;
							 continue;
						 }
						 if( intval( $rccmId ) > 15000 ) {
							 $errorMessages[]  = $message = sprintf("Le numéro RCMM du dossier %s  semble invalide",$dirRccmNum);
							 $errorItems[$dirRccmNum]     = $message;
							 continue;
						 }
						 $formulaireFile                  = $rccmDirectory . DS . $dirRccmNum."-FR.pdf";
						 $statuteFile                     = $rccmDirectory . DS . $dirRccmNum."-ST.pdf";
						 $completeFile                    = $rccmDirectory . DS . $dirRccmNum."-PS.pdf";
						 
						 $rccmSavingPath                  = $destPath . DS . $rccmLocalite;
						 if(!is_dir( $rccmSavingPath  ) ) {
							 @chmod( $destPath      , 0777);
							 @mkdir( $rccmSavingPath);
							 @chmod( $rccmSavingPath, 0777);
						 }
						 $rccmSavingPath                  = $destPath . DS . $rccmLocalite . DS . $rccmAnnee;
					     if(!is_dir( $rccmSavingPath )) {
							 @mkdir( $rccmSavingPath);
						 }
						 $rccmSavingPath                  = $destPath . DS . $rccmLocalite . DS . $rccmAnnee . DS . $dirRccmNum;
						 if(!is_dir( $rccmSavingPath )) {
							 @mkdir( $rccmSavingPath);
						 }
						 if(is_dir(  $rccmSavingPath) ) {
							 $savedFormulaireFile         = $destPath . DS . $rccmLocalite . DS . $rccmAnnee . DS . $dirRccmNum . DS . $dirRccmNum."-FR.pdf";
							 $savedStatuteFile            = $destPath . DS . $rccmLocalite . DS . $rccmAnnee . DS . $dirRccmNum . DS . $dirRccmNum."-ST.pdf";
							 $savedCompleteFile           = $destPath . DS . $rccmLocalite . DS . $rccmAnnee . DS . $dirRccmNum . DS . $dirRccmNum."-PS.pdf";
							 							 					 
							 if( $overwriteOption==="ERASE" ) {
								 $savedDirectoryFiles = glob( $rccmSavingPath."/*");
								 if( count(   $savedDirectoryFiles ) ) {
									 foreach( $savedDirectoryFiles as $savedDirectoryFile ) {
											  if( is_dir( $savedDirectoryFile ) ) {
												  @rmdir( $savedDirectoryFile );
											  } elseif( is_file( $savedDirectoryFile) ) {
												  @unlink($savedDirectoryFile);
											  }									      
									 }
								 }  
							 }	 						 
							 if( file_exists(    $formulaireFile)  && !file_exists($savedFormulaireFile)) {
								 if( false==copy($formulaireFile, $savedFormulaireFile )) {
									  $errorMessages[]  = $message = sprintf("La copie du formulaire du dossier numéro %s a echoué",$dirRccmNum);
									  $errorItems[$dirRccmNum]     = $message;
									  continue;
								 }
							 } else {
								      $errorMessages[]  = $message = sprintf("Le formulaire du dossier numéro %s n'existe pas",$dirRccmNum);
									  $errorItems[$dirRccmNum]     = $message;
									  continue;
							 }							 
							 if( file_exists(    $completeFile ) && !file_exists($savedCompleteFile)) {
								 if( false==copy($completeFile, $savedCompleteFile)) {
									  $errorMessages[]  = $message = sprintf("La copie du fond de dossier numéro %s a echoué",$dirRccmNum);
									  $errorItems[$dirRccmNum]     = $message;
									  continue;
								 }
							 } else {
								      $errorMessages[]  = $message = sprintf("Le fond de dossier numéro %s n'existe pas",$dirRccmNum);
									  $errorItems[$dirRccmNum]     = $message;
									  continue;
							 }
							 if( file_exists(    $statuteFile ) && !file_exists($savedStatuteFile)) {
								 if( false==copy($statuteFile, $savedStatuteFile)) {
									  $errorMessages[]  = $message = sprintf("La copie du statut du dossier numéro %s a echoué",$dirRccmNum);
									  $errorItems[$dirRccmNum]     = $message;
									  continue;
								 }
							 }
						 }						 
						 $mergedItems[]  =  $dirRccmNum;
				}
			}			
			$validHtml           =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
			          $validHtml.="    <tr><td width=\"100%\" style=\"font-size:13pt;text-align:center;\"  align=\"center\"><b> ". sprintf("%d dossiers valides ont été sauvegardés avec succès ", count($mergedItems)) ." </b></td></tr>";
            $validHtml          .=" </table>";			
            if( count( $errorMessages )) {
				$errorHtml       =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
                      $errorHtml.="<tr><td width=\"100%\" style=\"font-size:13pt; text-align:center;background-color:#E5E5E5\" align=\"center\"><b> HISTORIQUE DES ERREURS </b></td></tr>";
                $errorHtml      .=" </table>";
				$errorHtml      .=" <ul>";
				foreach( $errorMessages as $errorMessage ) {
					     $errorHtml      .=" <li> ".$errorMessage."</li>";
				}
				$errorHtml      .=" </ul>";
			}
            $me                  = Sirah_Fabric::getUser();
            $PDF                 = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
            $PDF->SetTitle(  "");
			$PDF->SetPrintHeader(false);
		    $PDF->SetPrintFooter(false);
		
		    $margins             = $PDF->getMargins();
		    $contenuWidth        = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		    $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		    $PDF->AddPage();
		
		    $PDF->Ln(10);				     	 
		    $PDF->SetFont("helvetica"   , "" , 12);	
			$PDF->writeHTML( $validHtml , true , false , true , false , '' );$PDF->AddPage();
            $PDF->writeHTML( $errorHtml , true , false , true , false , '' );$PDF->AddPage();			           				 
		    echo $PDF->Output("SauvegardesDonnees.pdf","D");
		    exit;
		}		
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
	}
	
	public function readdirAction()
	{
		@ini_set('memory_limit', '512M');
		$this->view->title  = "COPIE DES DOSSIERS";
		$modelLocalite      = $this->getModel("localite");
		$modelRegistre      = $model = $this->getModel("registre");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("annee"=>2016,"copyall"=>1,"localite"=>"BBD","srcpath"=>"G:\\ERCCM","indexingpath"=>"G:\\COMPLEMENTS\\A_INDEXER","processingpath"=>"G:\\COMPLEMENTS\\A_RETRAITER","destpath"=>"G:\\COMPLEMENTS\\DEST");
		$errorMessages      = array();
		$successMessages    = array();
	
		$dataItems          = array();
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017","2018"=>"2018");
									
		if( $this->_request->isPost()) {
			$postData       = $this->_request->getPost();
			$srcPath        = (isset($postData["srcpath"]       ))? $postData["srcpath"]        : "";
			$destPath       = (isset($postData["destpath"]      ))? $postData["destpath"]       : "";
			$processingPath = (isset($postData["processingpath"]))? $postData["processingpath"] : "";
			$indexingPath   = (isset($postData["indexingpath"]  ))? $postData["indexingpath"]   : "";
			$rccmLocalite   = (isset($postData["localite"]      ))? $postData["localite"]       : $defaultData["localite"];
			$copyAll        = (isset($postData["copyall"]       ))? $postData["copyall"]        : 0;
			$annee          = (isset($postData["annee"]         ))? intval($postData["annee"])  : 0;
			if(!is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'est pas valide", $srcPath );
			}  
			/*
			if(!is_dir( $processingPath )) {
				$errorMessages[] = sprintf("Le dossier 'à retraiter' %s n'est pas valide", $processingPath);
			} else {
				$processingPath  = $processingPath. DS . $rccmLocalite;
				if(!is_dir($processingPath)) {
					@mkdir($processingPath, 0777);
				}
			}
			if(!is_dir( $indexingPath )) {
				$errorMessages[] = sprintf("Le dossier 'à indexer' %s n'est pas valide", $indexingPath);
			} else {
				$indexingPath    = $indexingPath. DS . $rccmLocalite;
				if(!is_dir($indexingPath)) {
					@mkdir($indexingPath, 0777);
				}
			}*/
			if(!is_dir( $destPath )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'est pas valide", $destPath );
			} else {
				$destPath        = $destPath. DS . $rccmLocalite;
				$srcPath         = $srcPath . DS . $rccmLocalite;
				if(!is_dir($destPath)) {
					@mkdir($destPath, 0777);
				}
			}
			if( intval( $annee ) && empty( $errorMessages ) ) {
				$processingPath  = $processingPath. DS . $annee;
				$indexingPath    = $indexingPath  . DS . $annee;
				$destPath        = $destPath      . DS . $annee;
				$srcPath         = $srcPath       . DS . $annee;
				if(!is_dir($destPath)) {
					@mkdir($destPath, 0777);
				}
				/*if(!is_dir( $processingPath ) ) {
					@mkdir( $processingPath, 0777);
				}
				if(!is_dir( $indexingPath) ) {
					@mkdir( $indexingPath  , 0777);
				}*/
			}
			if(!isset(  $localites[$rccmLocalite] ) ) {
				$errorMessages[] = "Veuillez sélectionner une localité valide";
			}
			if( empty( $errorMessages ) ) {
				$files           = glob( $srcPath. DS . "*", GLOB_ONLYDIR);
				
				if( count(   $files )) {
					foreach( $files as $rccmFilename ) {
						     $psFilename             = $frFilename = $rccmFilename ;
							 $numeroRCCM             = Sirah_Filesystem::mb_basename($rccmFilename);
							 $isSolved               = $stFilename = false;
  
							 $checkPsFilename        = $srcPath. DS . $numeroRCCM. DS . $numeroRCCM."-PS.pdf";
                             $checkSTFilename        = $srcPath. DS . $numeroRCCM. DS . $numeroRCCM."-ST.pdf";							 
							 $checkFrFilename        = $srcPath. DS . $numeroRCCM. DS . $numeroRCCM."-FR.pdf";								 
							 if( file_exists( $checkPsFilename )) {
								 $psFilename         = $frFilename = $checkPsFilename;
							 }
							 if( file_exists( $checkFrFilename )) {
								 $frFilename         = $checkFrFilename;
							 }
							 if( file_exists( $checkSTFilename )) {
								 $stFilename         = $checkSTFilename;
							 }
							 $numeroRCCM             = str_ireplace(array("-FR","-ST",".pdf","-PS",".PDF"),"",$numeroRCCM);
							 $cleanNumeroRCCM        = $model->normalizeNum($numeroRCCM,$annee);							 
							 $foundRccm              = $model->findRow($cleanNumeroRCCM, "numero", null, false );
							 
							 if( $foundRccm ) {
								 continue;
							 }							 
							 if(!file_exists( $psFilename ) || !file_exists($frFilename) || is_dir($psFilename) || is_dir($frFilename)) {
								 continue;
							 }
							 $cleanDestPathname      = $destPath. DS . $numeroRCCM;
							 if(!is_dir( $cleanDestPathname )) {
								 @mkdir( $cleanDestPathname, 0777);
							 }
							 $newRccmFilenamePs      = strtoupper($numeroRCCM)."-PS.pdf";
							 $newRccmFilenameFR      = strtoupper($numeroRCCM)."-FR.pdf";
							 $newRccmFilenameST      = strtoupper($numeroRCCM)."-ST.pdf";
							 $destFilenamePs         = $cleanDestPathname. DS . $newRccmFilenamePs;
							 $destFilenameFR         = $cleanDestPathname. DS . $newRccmFilenameFR;
							 $destFilenameST         = $cleanDestPathname. DS . $newRccmFilenameST;
							 if( file_exists( $destFilenamePs ) || file_exists( $destFilenameFR ) ) {
								 continue;
							 }
							 if( $copyAll ) {						                                							 
								 if( copy($psFilename, $destFilenamePs) && copy($frFilename, $destFilenameFR)) {
									 if( $stFilename!==false && file_exists($stFilename)) {
										 copy($stFilename, $destFilenameST);
									 }
									 $dataItems[] = $newRccmFilename;
								 }							 
							 } else {
								 $dataItems[] = $newRccmFilename;
							 }
							 
							 //print_r($cleanNumeroRCCM); print_r($foundRccm); die();
							 /*														 
							 if(!$foundRccm) {	
                                 $indexingFilename   = $indexingPath. DS . $newRccmFilename;							 
                                 if(!file_exists($indexingFilename)) {
									 @copy( $psFilename, $indexingFilename);
								 }									 								 
							 }
							 if(!$isSolved && !$foundRccm) {
								 $processingFilename = $processingPath. DS . $newRccmFilename;
								 if(!file_exists( $processingFilename )) {
									 @copy($psFilename, $processingFilename);
								 }								 
							 }*/							 								 
					}
				}				
			} 
			$defaultData  = array_merge($defaultData, $postData);
			if( count($errorMessages) ) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage($message) ;
				}
			} else {
				$successMessage  = sprintf("Cette opération s'est effectuée avec succès. Au total %d RCCM ont été retrouvés avec succès", count($dataItems));
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("success"=> $successMessage));
					exit;
				}
				$this->_helper->Message->addMessage($successMessage, "sucess") ;
			}
		}
       
        $this->view->data        = $defaultData;
        $this->view->localites   = $localites;
		$this->view->annees      = $annees;		
	}

    protected function __copyDir( $dirname, $destname = null, $rootpath = null )
	{
		$destDirExists = true;
		$hasDone       = true;
		if(!is_dir( $dirname ) ) {
			return false;
		}		
		if( null=== $destname ) {
			$destname      = Sirah_Filesystem::mb_basename( $dirname );
		}
		if(!empty(  $rootpath) && is_dir( $rootpath )) {			    
		    $destname      = $rootpath . DS . $destname;
			chmod(  $rootpath , 0777 );
		}
		if(!is_dir( $destname ) ) {
			$destDirExists = mkdir(  $destname );
		}
		if( $destDirExists ) {						
			$dirFiles      = glob( $dirname . DS . "*");
			if( count(   $dirFiles ) ) {
				foreach( $dirFiles as  $dirFile ) {
					     if( !is_file( $dirFile ) ) continue;
					     $fileDestname = $destname . DS. basename($dirFile);
						 if( file_exists( $fileDestname ) ) {
							 unlink( $fileDestname );
						 }
					     if( FALSE   === copy( $dirFile, $fileDestname ) ) {
							 $hasDone  = false;
						 }
				}
			}
		} else {
			$hasDone  = false;
		}
		return $hasDone;
	}
	
	public function missingnereAction()
	{ 
		$model                   = $this->getModel("registre");
    	$modelLocalite           = $this->getModel("localite")	;
        $modelAdresse            = $this->getModel("registreadresse");
		
		$updatedRegistres        = $errorMesages = array();
		
		$me                      = Sirah_Fabric::getUser();
		$userTable               = $me->getTable();
		$dbAdapter               = $userTable->getAdapter();
		$prefixName              = $userTable->info("namePrefix");
		$missings                = array();
		$defaultData             = array("dbparams_host"=>"10.60.16.165\CCI", "dbparams_username"=>"sa", "dbparams_password"=>"P@ssw0rd","dbparams_dbname"=>"dbNERE",
		                                 "annee"=>0, "localite"=>"OUA", "localiteid"=>0);
		$localites               = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS             = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                  = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                 "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017",
										 "2018"=>"2018","2019"=>"2019","2020"=>"2020");	
		if( $this->_request->isPost() )  {
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $tablePrefix = $userTable->info("namePrefix");
			$postData               = $this->_request->getPost();
			$dbParams               = array("isDefaultAdapter" =>0);
			$dbParams["host"]       = $host     = (isset($postData["dbparams_host"]    ))? $postData["dbparams_host"]     : $defaultData["dbparams_host"];
		    $dbParams["username"]   = $username = (isset($postData["dbparams_username"]))? $postData["dbparams_username"] : $defaultData["dbparams_username"];
		    $dbParams["password"]   = $pwd      = (isset($postData["dbparams_password"]))? $postData["dbparams_password"] : $defaultData["dbparams_password"];
		    $dbParams["dbname"]     = $dbName   = (isset($postData["dbparams_dbname"]  ))? $postData["dbparams_dbname"]   : $defaultData["dbparams_dbname"];
		    $annee                  = (isset($postData["annee"]         ))? intval($postData["annee"])     : 0;
			$localiteCode           = (isset($postData["localite"]      ))? $postData["localite"]          : $defaultData["localite"];
			$localiteid             = (isset($localiteIDS[$localiteCode]))? $localiteIDS[$localiteCode]    : 0;
			$importedRegistres      = array();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$strNotEmptyValidator     = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter             = new Zend_Filter();
			$stringFilter->addFilter(   new Zend_Filter_StringTrim());
			$stringFilter->addFilter(   new Zend_Filter_StripTags());
			$stringFilter->addFilter(   new Sirah_Filtre_Encode());
			$stringFilter->addFilter(   new Sirah_Filtre_FormatDate());
			$stringFilter->addFilter(   new Sirah_Filtre_StripNull());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
 
			try {
				//$dbSource           = Zend_Db::factory("Sqlsrv", $dbParams);
				$dbSource            = Zend_Db::factory("Sqlsrv", $dbParams);
			} catch(Exception $e ) {
				$errorMessages[]     = sprintf("Erreur de connexion à la base de données : ".$e->getMessage()); 
				printf("Erreur de connexion à la base de données : ".$e->getMessage()); die();
			}
			
 
		    if( empty($errorMessages) ) {
				try {
					$erccmRegistres               = $model->getList(array(),1, 1000, array("R.localiteid DESC","FROM_UNIXTIME(R.date,'%Y') DESC", "R.numero DESC"));
					if( count(   $erccmRegistres )) {
						foreach( $erccmRegistres as $erccmRegistre ) {
								 $NumeroRCCM      = $erccmRegistre["numero"];
								 
								 $NumRcStart      = substr($NumeroRCCM , 0, 9);
								 $NumRcEnd        = intval(substr($NumeroRCCM, 10, strlen($NumeroRCCM)));
								 $likeNumeroRCCM  = sprintf("%s%%%s", $NumRcStart, $NumRcEnd);
								 
								 $selectRegistre  = $dbSource->select()->from(array("ENT"=>"EEntreprise"), array("NumeroRCCM"=>"ENT.NRCCM_ent","RCCM"=>"ENT.NRCCM_ent","NomCommercial"=>"ENT.nomcom_ent","DateRCCM"=>"ENT.DateRCCM_ent","IFU"=>"ENT.NIMPOT_ent","DateIFU"=>"ENT.datenimpot_ent","NumeroCNSS"=>"ENT.NSECU_ent","DateCNSS"=>"ENT.datensecu_ent"))
																	   ->where(     "ENT.NRCCM_ent LIKE ?", "%".$NumeroRCCM."%")
																	   ->orWhere(   "ENT.NRCCM_ent LIKE ?", "%".$likeNumeroRCCM."%")
																	   ->limitPage(1,1);
								 $nereRCCMS       = $dbSource->fetchRow($selectRegistre, array(), Zend_Db::FETCH_ASSOC);
								 if(!isset($nereRCCMS["NumeroRCCM"]) ) {
									 $missings[]  = $erccmRegistre;
								 }						 
						}
					}
				} catch(Exception $e) {
					$errorMessages[]              = sprintf("Une erreur d'extraction s'est produite : %s", $e->getMessage());
				}		 
			}

            if( count( $errorMessages ) ) {
			    if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				    exit;
			    }
			   foreach( $errorMessages as $message ) {
						echo $message." <br/>" ;
			   }
			}	else {
				$totalRccms     = count($missings);	
                $output         ="<table align=\"center\" width=\"100%\" border=\"0\" cellspacing=\"4\" cellpadding=\"4\" >";
                    $output    .=" <tr bgcolor=\"#CCCCCC\" style=\"background-color:#CCCCCC\"> 
										<td align=\"center\" style=\"border-bottom:1px solid black;\">                    
										   <span style=\"font-family:arial;font-size:11pt;font-weight:bold;height:25px;\"> ".sprintf('Historique des RCCMS MANQUANTS DANS NERE, TOTAL : %d registres',$totalRccms)." </span> <br/>
										</td> 
								  </tr> ";
                $output        .=" </table> "; 				
				
				$output        .= "<table border='1' width='100%' cellspacing='2' cellpadding='2'> 
										<thead><tr><th width='5%'> N° d'ordre </th><th width='10%'> Localités </th><th width='5%'> Années </th><th width='10%'> Numéros RCCM </th><th width='5%'> N° IFU </th><th width='5%'> N° CNSS </th><th width='25%'> Promoteurs</th> <th width='40%'> Secteurs d'activités </th></tr> </thead>
										<tbody>";
				$i              = 1;						
				if( count($missings) ) {
					foreach( $missings as $registre ) {
						     $promoteur   = sprintf("%s %s", $registre["nom"], $registre["prenom"]);
						     $output.=      " <tr>";
							 $output.=      "        <td> ".$i." </td>";
							 $output.=      "        <td> ".$registre['localite']    ." </td>";
							 $output.=      "        <td> ".$registre['annee']       ." </td>";
							 $output.=      "        <td> ".$registre['numero']      ." </td>";
							 $output.=      "        <td> ".$registre['numifu']      ." </td>";
							 $output.=      "        <td> ".$registre['numcnss']     ." </td>";
							 $output.=      "        <td> ".$promoteur               ." </td>";
							 $output.=      "        <td> ".$registre['description'] ." </td>";
							 $output.=      " </tr>";
							 $i++;
					}					
				} else {
					$output    .=      "<tr><td align=\"center\" colspan=\"5\"> AUCUN REGISTRE MANQUANT N'A ETE TROUVE </td> </tr>";
				}
                echo $output;
                $this->_helper->viewRenderer->setNoRender(true);
                exit;
			}			
		}
	    $this->view->title     = "Importer les données à partir de NERE";
		$this->view->annees    = $annees;
		$this->view->localites = $localites;
		$this->view->data      = $defaultData;
	
	    $this->render("missingnere");
	}
	
	public function historyAction()
	{
		$this->view->title     = "Explorer la base de données";
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");

		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS           = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=> "2011","2012"=>"2012","2013"=> "2013", "2014"=> "2014","2015"=> "2015", "2016"=> "2016", "2017" => "2017",
									   "2018"=>"2018","2019"=>"2019","2020"=>"2020");
		
		$defaultMaxNumByYears  = array("2000"=>"2031","2001"=>"2714","2002"=>"3144","2003"=>"3421","2004"=>"3984","2005"=>"4049","2006"=>"3591","2007"=>"4005","2008"=>"3702",
		                               "2009"=>"4900","2010"=>"4394","2011"=> "4794","2012"=>"5776","2013"=>"6480","2014"=>"7115","2015"=>"6613","2016"=> "9230","2017" => "11000");
        
		/*$defaultMaxNumByYears  = array("2000"=>"0","2001"=>"0","2002"=>"0","2003"=>"0","2004"=>"0","2005"=>"12","2006"=>"61","2007"=>"43","2008"=>"47",
		                               "2009"=>"36","2010"=>"58","2011"=> "23","2012"=>"59","2013"=>"74","2014"=> "241","2015"=>"92","2016"=> "193");*/									   
		$defaultData           = array("localite"=>"OUA","startnum" =>1,"annee" => date("Y"));
		$rccms                 = array();
		$existants             = array();
		$i                     = 1;
		$errorMessages         = array();
		$invalidMsg            = "";		
		
		if( $this->_request->isPost() ) {
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
			$postData          = $this->_request->getPost();
			
			
			$checkedAnnee      = (isset( $postData["annee"]         ))? $postData["annee"]          : date("Y");
			$localiteCode      = (isset( $postData["localite"]      ))? $postData["localite"]       : $defaultData["localite"];
			$localiteid        = (isset( $localiteIDS[$localiteCode]))? $localiteIDS[$localiteCode] : 0;
            $startNum          = (isset( $postData["startnum"]      ))? $postData["startnum"]       : 0; 
			$localiteLibelle   = (isset( $localites[$localiteCode]  ))? $localites[$localiteCode]   : "";
            $endNum            = (isset( $postData["limitnum"]      ))? $postData["limitnum"]       : 1000;			
						
			if( intval($checkedAnnee) < 2000 || intval($checkedAnnee) > 2018 ) {
				$errorMessages[]= "Veuillez sélectionner une année valide";
			}
			if(!intval($localiteid) ) {
				$errorMessages[]= "Veuillez sélectionner une localité valide";
			}
			if(!intval($startNum) ) {
				$errorMessages[]= "Veuillez renseigner le numéro de départ";
			}
			
			if( empty($errorMessages) ) {
				$numLike        = strtoupper(sprintf("BF%s%s", $localiteCode, $checkedAnnee));
				try {
				$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.localiteid","R.domaineid","R.numero","R.libelle","R.description","R.date","R.category","R.type","date_registre"=>"FROM_UNIXTIME(R.date,'%d/%m/%Y')","annee"=>"FROM_UNIXTIME(R.date,'%Y')",
				                                                   "numvalue"=>new Zend_Db_Expr("CAST(SUBSTRING(R.numero,-4,4) as UNSIGNED)")
																   ))
		                                    ->join(array("RP"   =>$tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid",array("RP.representantid"))
		                                    ->join(array("RE"   =>$tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.passport","RE.sexe","RE.country","RE.marital_status","RE.telephone","RE.email","RE.profession","date_naissance"=>$dateNaissance))
											->join(array("L"    =>$tablePrefix."rccm_localites"),"L.localiteid=R.localiteid",array("localite"=>"L.libelle"))
		                                    ->joinLeft(array("D"=>$tablePrefix."rccm_domaines"), "D.domaineid=R.domaineid"  ,array("domaine" =>"D.libelle"))
											->where("R.localiteid=?", intval($localiteid))
											->where("FROM_UNIXTIME(R.date,'%Y') >= ?", intval($checkedAnnee))
											->where(new Zend_Db_Expr("CAST(SUBSTRING(R.numero,-4,4) as UNSIGNED)")." >= ?", $startNum)
											->order(array("R.localiteid DESC","FROM_UNIXTIME(R.date,'%Y') ASC","R.numero ASC"));
				//print_r($selectRegistre->__toString()); die();
				$registres      = $dbAdapter->fetchAll($selectRegistre);
				} catch(Exception $e ) {
					print_r($e->getMessage());die();
				}
				$totalRccms     = count($registres);	
                $output         ="<table align=\"center\" width=\"100%\" border=\"0\" cellspacing=\"4\" cellpadding=\"4\" >";
                    $output    .=" <tr bgcolor=\"#CCCCCC\" style=\"background-color:#CCCCCC\"> 
										<td align=\"center\" style=\"border-bottom:1px solid black;\">                    
										   <span style=\"font-family:arial;font-size:11pt;font-weight:bold;height:25px;\"> ".sprintf('Historique des RCCMS DE LA LOCALITE DE %s A PARTIR DU NUMERO %d DE L\'ANNEE %d, TOTAL : %d registres', $localiteLibelle,$startNum, $checkedAnnee, $totalRccms)." </span> <br/>
										</td> 
								  </tr> ";
                $output        .=" </table> "; 				
				
				$output        .= "<table border='1' width='100%' cellspacing='2' cellpadding='2'> 
										<thead><tr><th width='5%'> N° d'ordre </th><th width='20%'> Localités </th><th width='15%'> Années </th><th width='15%'> Numéros </th><th width='30%'> Promoteurs</th> <th width='30%'> Secteurs d'activités </th></tr> </thead>
										<tbody>";
				$i              = 1;						
				if( count($registres) ) {
					foreach( $registres as $registre ) {
						     $promoteur   = sprintf("%s %s", $registre["nom"], $registre["prenom"]);
						     $output.=      " <tr>";
							 $output.=      "        <td> ".$i." </td>";
							 $output.=      "        <td> ".$registre['localite']      ." </td>";
							 $output.=      "        <td> ".$registre['annee']        ." </td>";
							 $output.=      "        <td> ".$registre['numero']       ." </td>";
							 $output.=      "        <td> ".$promoteur               ." </td>";
							 $output.=      "        <td> ".$registre['description'] ." </td>";
							 $output.=      " </tr>";
							 $i++;
					}					
				} else {
					$output    .=      "<tr><td align=\"center\" colspan=\"5\"> AUCUN REGISTRE MANQUANT N'A ETE TROUVE </td> </tr>";
				}
                echo $output;
                $this->_helper->viewRenderer->setNoRender(true);
                exit;				
			}
			
			if( count( $errorMessages ) ) {
			   if( $this->_request->isXmlHttpRequest()) {
				   $this->_helper->viewRenderer->setNoRender(true);
				   echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				   exit;
			   }
			   foreach( $errorMessages as $errorMessage ) {
						$this->getHelper("Message")->addMessage($errorMessage , "error");
			   }
			}	 
		}
		
		$this->view->localites = $localites;
		$this->view->annees    = $annees;
		$this->view->data      = $defaultData;
	}


    protected function __moveDir( $dirname, $destname = null, $rootpath = null  )
	{
		$destDirExists = true;
		$hasDone       = true;
		if(!is_dir( $dirname ) ) {
			return false;
		}		
		if( null=== $destname ) {
			$destname      = Sirah_Filesystem::mb_basename( $dirname );
		}
		if(!empty(  $rootpath) && is_dir( $rootpath )) {			    
		    $destname      = $rootpath . DS . $destname;
			chmod(  $rootpath , 0777 );
		}
		if(!is_dir( $destname ) ) {
			$destDirExists = mkdir(  $destname );
		}
		if( $destDirExists ) {
			$dirFiles      = glob( $dirname . DS . "*");
			if( count(   $dirFiles ) ) {
				foreach( $dirFiles as $dirFile ) {
					     if(!is_file( $dirFile ) ) continue;
					     $fileDestname = $destname . DS. basename($dirFile);
						 if( file_exists( $fileDestname ) ) {
							 unlink( $fileDestname );
						 }
					     if( FALSE === copy( $dirFile, $fileDestname ) ) {
							 $hasDone  = false;
						 } else {
							 @unlink( $dirFile );
						 }
				}
				@rmdir($dirname );
			}
		} else {
			$hasDone  = false;
		}
		return $hasDone;
	}	
}