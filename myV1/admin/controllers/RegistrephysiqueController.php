<?php
require_once("tcpdf/tcpdf.php");
require_once("Fpdi/fpdi.php");
require 'vendor/autoload.php';
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

class Admin_RegistrephysiqueController extends Sirah_Controller_Default
{
	
	 public function getDirigeants($registreid,&$dbAdapter,$tablePrefix="") 
	{		
		if(!intval($registreid))  {
			return array();
		}		 		
		$selectDirigeants  = $dbAdapter->select()->from(array("RE"=>$tablePrefix."rccm_registre_representants"), array("RE.representantid","RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.marital_status","RE.email","RE.telephone","RE.cnib","RE.passport","RE.representantid","RE.sexe","RE.country","RE.city","date_naissance_year"=>"YEAR(RE.datenaissance)","date_naissance_month"=>"MONTH(RE.datenaissance)","date_naissance_day"=>"DAYOFMONTH(RE.datenaissance)","RE.creationdate","RE.creatorid"))
		                                         ->join(array("D" =>$tablePrefix."rccm_registre_dirigeants"),"D.representantid=RE.representantid",array("D.fonction","profession"=>"D.fonction"))
											     ->where("D.registreid=?", intval($registreid))
												 ->group(array("D.registreid","D.representantid","RE.representantid"))
												 ->order(array("RE.representantid ASC","RE.nom ASC", "RE.prenom ASC"));
		return $dbAdapter->fetchAll($selectDirigeants, array(), Zend_Db::FETCH_ASSOC);									  
	}

    public function getDocuments($registreid,&$dbAdapter,$tablePrefix="")
	{
		if(!intval($registreid))  {
			return array();
		}
		$selectDocuments = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.documentid","D.resource","D.resourceid","D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.resourceid","D.userid","D.creatoruserid","D.updateduserid","D.updatedate"))
				                               ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("categorie"=>"C.libelle","C.icon","catid"=>"C.id"))
				                               ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",array("RD.access"))
				                               ->where("RD.registreid=?",intval($registreid ));

		$selectDocuments->order(array("RD.registreid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function backupdbAction()
	{		
	    @ini_set('memory_limit', '-1');
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		
		$me                           = Sirah_Fabric::getUser();
		$userTable                    = $me->getTable();
		$model                        = $modelRegistre = $this->getModel("registre");
		$modelLocalite                = $this->getModel("localite");
		$modelDomaine                 = $this->getModel("domaine");
		$modelEntreprise              = $this->getModel("entreprise");
		$modelRepresentant            = $this->getModel("representant");
		$modelPhysique                = $this->getModel("registrephysique");
		$modelMoral                   = $this->getModel("registremorale");
		$modelModification            = $this->getModel("registremodification");
		$modelDocument                = $this->getModel("document");
		$modelTable                   = $model->getTable();
		$dbAdapter                    = $modelTable->getAdapter();
		$prefixName                   = $tablePrefix = $modelTable->info("namePrefix");
		$annee                        = intval($this->_getParam("annee",date("Y")));
 
        //On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$dbSourceParams               = array("host"=>"localhost","username"=>"useradmin","password"=>"mebfSir@h1217","dbname"=>"erccmbdd","isDefaultAdapter"=>0);
	    //$dbSourceParams               = array("host"=>"localhost","username"=>"root","password"=>"","dbname"=>"cerpamad_sauvegarde","isDefaultAdapter" => 0);
		$errorMessages                = $successMessages = $savedRegistres = array();
		$createdRegistres             = 0;
		try{
			$dbSourceAdapter          = Zend_Db::factory("Pdo_Mysql", $dbSourceParams);
			var_dump($dbSourceAdapter->getConnection());
		} catch( Zend_Db_Adapter_Exception $e ) {
			$errorMessages[]          = "Les paramètres de la base de données source ne sont pas valides, debogage: ".$e->getMessage();
		} catch( Exception $e ) {
			$errorMessages[]          = "Les paramètres de la base de données source ne sont pas valides, debogage: ".$e->getMessage();
		}
		if( empty($errorMessages) && $dbSourceAdapter) {
			$filters                  = array("periode_start_day"=>1,"periode_start_month"=>01,"periode_start_year"=>1999,"periode_end_day"=>5,"periode_end_month"=>11,"periode_end_year"=>2023);
			$periodStart              = $periodEnd = 0;
			if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_day"]))
					&&
				(isset($filters["periode_end_day"])  && intval($filters["periode_end_day"] ))   && (isset($filters["periode_start_day"])  && intval($filters["periode_start_day"]  ))
			)	{
				$zendPeriodeStart     = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
				$zendPeriodeEnd       = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
				$periodStart          = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
				$periodEnd            = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
			}
			$selectRegistres          = $dbSourceAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ))
		                                                          ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid"         , array("RP.representantid"))
																  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.cnib","RE.passport","RE.sexe","RE.telephone","RE.profession"))											  
                                                                  ->where("R.type=1")
																  ->order(array("R.annee ASC","R.registreid DESC"));				
		    /*if( intval($periodEnd) ){
				$selectRegistres->where("R.creationdate<=?", intval($periodEnd));
			}
			if( intval($periodStart) ){
				$selectRegistres->where("R.creationdate>=?", intval($periodStart));
			}*/	
            if( $annee ) {
				$selectRegistres->where("R.annee=?", intval($annee));
			}				
             			
			$sourceRegistres              = $dbSourceAdapter->fetchAll( $selectRegistres, array() , Zend_Db::FETCH_ASSOC);
			
			//var_dump(count($sourceRegistres)); die();
			
			$registreDefaultData          = $defaultData     = $modelRegistre->getEmptyData();
			$entrepriseDefaultData        = $modelEntreprise->getEmptyData();
			$documentDefaultData          = $modelDocument->getEmptyData();
			$representantDefaultData      = $modelRepresentant->getEmptyData();
			
			if( count(   $sourceRegistres) ) {
				foreach( $sourceRegistres as $sourceRegistre) {
					     $postData        =  $sourceRegistre;
					     $registreid      =  $sourceRegistre["registreid"];
						 $entrepriseid    =  (isset($sourceRegistre["entrepriseid"]))? $sourceRegistre["entrepriseid"] : 0;
						 if(!$registreRow =  $modelRegistre->findRow($registreid,"registreid",null,false)) {
							 $registreData=  $insertData = array_merge($defaultData, array_intersect_key($sourceRegistre,$defaultData));
							 try {
								$dbAdapter->delete(     $prefixName."rccm_registre", array("registreid=?"=>$registreid));
								if( $dbAdapter->insert( $prefixName."rccm_registre", $insertData)) {
									if( isset($sourceRegistre["formid"]) && intval($sourceRegistre["formid"])) {										
										$entreprise_data                        = array_merge($entrepriseDefaultData, array_intersect_key($sourceRegistre,$entrepriseDefaultData ));				
										$entreprise_data["registreid"]          = $registreid;
										$entreprise_data["formid"]              = $insertData["formid"];
										$entreprise_data["num_securite_social"] = $insertData["numcnss"];
										$entreprise_data["num_ifu"]             = $insertData["numifu"];
										$entreprise_data["num_rc"]              = $insertData["numero"];					  
										$entreprise_data["libelle"]             = $stringFilter->filter($insertData["libelle"]);
										$entreprise_data["address"]             = (isset($postData["address"] ))? $stringFilter->filter($postData["address"])  : "";
										$entreprise_data["email"]               = (isset($postData["email"]   ))? $stringFilter->filter($postData["email"])	   : "";
										$entreprise_data["phone1"]              = (isset($postData["phone1"]  ))? $stringFilter->filter($postData["phone1"])   : "";
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
										$entreprise_data["pagekey"]             = (isset($postData["pagekey"]          ))? $stringFilter->filter($postData["pagekey"]) : $insertData["numero"];
										$entreprise_data["creatorid"]           = $insertData["creatorid"];
										$entreprise_data["creationdate"]        = $insertData["creationdate"];
										$entreprise_data["updateduserid"]       = (isset($postData["updateduserid"]    ))? intval($postData["updateduserid"]) : 0;
										$entreprise_data["updatedate"]          = (isset($postData["updatedate"]       ))? intval($postData["updatedate"])    : 0;			
                                        $dbAdapter->delete(     $prefixName."rccm_registre_entreprises", array("registreid=?"=>$registreid));										
										if( $dbAdapter->insert( $prefixName."rccm_registre_entreprises", $entreprise_data )) {
											$entrepriseid                       = (intval($entrepriseid))?$entrepriseid : $dbAdapter->lastInsertId();
										} else {
											$errorMessages[$registreid]         = sprintf("L'entreprise du RCCM N° %s ID %d n'a pas pu être enregistrée", $insertData["numero"],$registreid);
										    continue;
										}
									}
									/* 
									$registreDirigeants                              = $this->getDirigeants($registreid,$dbSourceAdapter,$prefixName);
									if( count(   $registreDirigeants) ) {
										$dbAdapter->delete( $prefixName."rccm_registre_dirigeants", array("registreid=?"=>$registreid));
										foreach( $registreDirigeants as $registreDirigeant ) {
											     $representant_data                  = array_merge( $representantDefaultData, array_intersect_key($registreDirigeant, $representantDefaultData));
										         $representant_data["cnib"]          = (isset($registreDirigeant["cnib"]         ))? $registreDirigeant["cnib"]          : (isset($registreDirigeant["passport"])?$registreDirigeant["passport"] : "");
												 $representant_data["city"]          = (isset($registreDirigeant["city"]         ))? $registreDirigeant["city"]          : "";
												 $representant_data["creationdate"]  = (isset($registreDirigeant["creationdate"] ))? $registreDirigeant["creationdate"]  : time();
												 $representant_data["creatorid"]     = (isset($registreDirigeant["creatorid"]    ))? $registreDirigeant["creatorid"]     : 26;
												 $representant_data["updateduserid"] = (isset($registreDirigeant["updateduserid"]))? $registreDirigeant["updateduserid"] : 0;
												 $representant_data["updatedate"]    = (isset($registreDirigeant["updatedate"]   ))? $registreDirigeant["updatedate"]    : 0;
												 
												 if(!isset($representant_data["representantid"]) || !intval($representant_data["representantid"])) {
													 $errorMessages[$registreid]     = sprintf(" L'identifiant du representant du RCCM n° %s ID : %s est invalide", $insertData["numero"], $registreid);
												     continue;
												 } else {
													 $representantid                 = intval($representant_data["representantid"]);
												 }
												 if( empty($representant_data["nom"]) || empty($representant_data["prenom"])) {
													 $errorMessages[$registreid]     = sprintf(" Veuillez entrer un nom de famille et/ou prénom valide pour le representant du RCCM n° %s ID : %s", $insertData["numero"], $registreid);
												     continue;
												 }
												 $dbAdapter->delete( $prefixName."rccm_registre_representants" , array("representantid=?"=>$representantid));
			                                     $dbAdapter->delete( $prefixName."rccm_registre_dirigeants"    , array("representantid=?"=>$representantid));
												 $representantRegistre               = array("registreid"=>$registreid,"representantid"  =>$representantid,"fonction"=>"GERANT","entrepriseid"=>$entrepriseid);
					
												 if(!$dbAdapter->insert( $prefixName."rccm_registre_representants", $representant_data) ||
													!$dbAdapter->insert( $prefixName."rccm_registre_dirigeants"   , $representantRegistre)) {
													 $errorMessages[$registreid]     = sprintf("Les données du représentant du RCCM n° %s n'ont pas pu être enregistrées  : %d", $insertData["numero"], $registreid);
												 }
										}
									}
									$registreDocuments                               = $this->getDocuments( $registreid,$dbSourceAdapter,$prefixName);
									if( count(   $registreDocuments) ) {
										$dbAdapter->delete( $prefixName."rccm_registre_documents", array("registreid=?"=>$registreid));
										foreach( $registreDocuments as $registreDocument ) {
											     $documentData                       = array_merge($documentDefaultData, array_intersect_key($registreDocument,$documentDefaultData));
										         $documentid                         = intval($documentData["documentid"]);
												 $access                             = (isset($registreDocument["access"]))?intval($registreDocument["access"]) : 0;
												 $NumeroRCCM                         = $registreData["numero"];
												 if( isset($documentData["filename"]) && !empty($documentData["filename"]) && !empty($documentData["filepath"])){
													 $documentFilename               = $documentData["filename"];
													 $dbAdapter->delete(     $prefixName."system_users_documents" , array("filename LIKE ?"=>"%".$documentFilename."%"));
													 $dbAdapter->delete(     $prefixName."system_users_documents" , array("documentid=?"=>$documentid));
													 $dbAdapter->delete(     $prefixName."rccm_registre_documents", array("documentid=?"=>$documentid));
													 if( $dbAdapter->insert( $prefixName."system_users_documents" , $documentData) ) {
														 $dbAdapter->insert( $prefixName."rccm_registre_documents", array("registreid"=>$registreid,"documentid"=>$documentid,"access"=>$access)); 				
													 } else {
														 $errorMessages[$registreid] = sprintf("Les données du document #ID%s du RCCM n° %s n'ont pas pu être enregistrées   : %d",$documentid,$NumeroRCCM,$registreid);
													 }
												 }
										}
									}*/
									if(!isset($errorMessages[$registreid]) ) {
										$createdRegistres++;
										$savedRegistres[$registreid]  = $insertData;
										$successMessages[]            = sprintf("Les données du RCCM n° %s ont été enregistrées avec succès : %d", $insertData["numero"], $registreid);
									}									
								}
							 } catch(Exception $e) {
								$errorMessages[]  = sprintf("Une erreur technique s'est produite sur le RCCM N° %s : %s ", $insertData["numero"], $e->getMessage());
							 }
						 }
				}
			}
		}
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$jsonResponse                 = array();
				$jsonResponse["error"]        = " Des erreurs se sont produites : " . implode(" , " , $errorMessages );
				echo ZendX_JQuery::encodeJson( $jsonResponse );
				exit;
			}
			foreach( $errorMessages as $message) {
					 $this->_helper->Message->addMessage( " Des erreurs ont été produites : " . $message ) ;
			}
			
		} else {
			$successMessage  = sprintf("%d de RCCM ont été insérés",$createdRegistres, $updatedPaiements);
		    if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$jsonResponse                 = array();
				$jsonResponse["success"]      = $successMessage;
				echo ZendX_JQuery::encodeJson( $jsonResponse );
				exit;
			}
			$this->setRedirect($successMessage, "success");
		}
		
		$this->redirect("admin/registres/list");
	}	
		
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "ERCCM : Historique des RCCM de type Personnes Physiques"  ;
		
		$model              = $this->getModel("registrephysique");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = $errorMessages   = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=>null,"numero"=>null,"numcnss"=>null,"numpassport"=>null,"domaineid"=>0,"creatorid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>null,
		                              "date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"name"=>null,
				                      "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,
				                      "periode_end_day" =>DEFAULT_END_DAY ,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR);		
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
		if( $me->isOPS() ) {
			$filters["creatorid"] = $me->userid;
		}
        if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"])) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_month"]))
				&&
			(isset($filters["periode_end_day"]  ) && intval($filters["periode_end_day"]))   && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=> $filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=> $filters["periode_end_year"],"month"=> $filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]   ));
			$filters["periode_start"] = ($zendPeriodeStart ) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd   ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		if( $findSimilar && !empty($similarSearchQ) ) {
			try {
				$registres            = $model->getSimilarList($similarSearchQ);
				$paginator            = $model->getSimilarListPaginator($similarSearchQ);
				$filters["searchQ"]   = $params["searchQ"]  = $similarSearchQ;
			} catch(Exception $e ) {
				$errorMessages[]      = sprintf("Une erreur technique s'est produite : %s", $e->getMessage());
			}			
		} else {
			try {
				$registres            = $model->getList( $filters , $pageNum , $pageSize);
				$paginator            = $model->getListPaginator($filters);
			} catch(Exception $e ) {
				$errorMessages[]      = sprintf("Une erreur technique s'est produite : %s", $e->getMessage());
			}
		}		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		if(!empty($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message) {
					 $this->_helper->Message->addMessage($message , "error") ;
			}
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activités"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites        = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->params           = $params;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title                   = "Enregistrer un registre de commerce de type `Personnes Physiques`";
		
		$model                               = $this->getModel("registre");
		$modelPhysique                       = $this->getModel("registrephysique");
		$modelRepresentant                   = $this->getModel("representant");
		$modelDomaine                        = $this->getModel("domaine");
		$modelLocalite                       = $this->getModel("localite");
		$me                                  = Sirah_Fabric::getUser();
		
		$newCreation                         = intval($this->_getParam("new_creation" , 0   ));
		$registreDefaultData                 = $model->getEmptyData();
		$representantDefaultData             = $modelRepresentant->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);
		$defaultData                         = array_merge( $representantDefaultData, $registreDefaultData );
		$defaultData["localiteid"]           = intval($this->_getParam("localiteid" , $me->getParam("default_localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID)));		
		$defaultData["country"]              = "BF";
		$defaultData["date_year"]            = intval($this->_getParam("annee"      , $me->getParam("default_year"      , DEFAULT_YEAR)));		
		$defaultData["date_month"]           = sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));
		$defaultData["date_day"]             = null;
		$defaultData["date_naissance_year"]  = null;
		$defaultData["date_naissance_month"] = null;
		$defaultData["date_naissance_day"]   = null;
		$defaultData["domaineid"]            = intval($this->_getParam("domaineid", $me->getParam("default_domaineid", APPLICATION_REGISTRE_DEFAULT_DOMAINEID)));
		$defaultData["check_documents"]      = $checkDocuments = intval($this->_getParam("check_documents", 0));
		$defaultData["find_documents"]       = 0;
		$defaultData["find_documents_src"]   = $fileSource = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
		$errorMessages                       = array();
		$registreid                          = 0;
				
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$registre_data                   = array_merge( $registreDefaultData    , array_intersect_key( $postData, $registreDefaultData   ));
			$representant_data               = array_merge( $representantDefaultData, array_intersect_key( $postData, $representantDefaultData ));			
			$modelTable                      = $model->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
			$tableName                       = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(          new Zend_Filter_StringTrim());
			$stringFilter->addFilter(          new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($registre_data["numero"])));
			$libelle                         = $stringFilter->filter( $registre_data["libelle"] );
			$findDocuments                   = (isset($postData["find_documents"] ))? intval($postData["find_documents"])  : 0;
			$checkDocuments                  = (isset($postData["check_documents"]))? intval($postData["check_documents"]) : 0;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
			
			if(!is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}
			if(!$modelPhysique->checkNum($numero)) {
				$errorMessages[]             = sprintf("Le numéro RCCM %s saisi ne semble pas valide.", $numero);
			}
			if( $existantRegistre = $model->findRow($numero,"numero",null,false)) {
				$errorMessages[]             = sprintf("Un registre existant porte le numéro RCCM %s, veuillez entrer un numéro RCCM différent", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$libelle                     = sprintf("%s %s %s", $representant_data["nom"], $representant_data["prenom"], $numero);
			}
            if(($representant_data["sexe"]!= "F") && ($representant_data["sexe"] != "M") && ($representant_data["sexe"] != "H")) {
                $errorMessages[]             = "Veuillez indiquer un sexe valide (entre F, M, et H)";
		    }			
			if( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]             = sprintf("Un registre existant porte le nom commercial %s , veuillez entrer un nom commercial différent ", $libelle);
			} else {
				$registre_data["libelle"]    = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $representant_data["nom"] ) || !$strNotEmptyValidator->isValid( $representant_data["prenom"] )) {
				$errorMessages[]             = " Veuillez entrer un nom de famille et/ou prénom valide pour le representant";
			}  
			if(!intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]]) ) {
				$errorMessages[]             = "Veuillez sélectionner une localité valide";
			} else {
				$registre_data["localiteid"] = intval( $registre_data["localiteid"] ) ;
			}
			$localiteCode                    = (isset($localitesCodes[$registre_data["localiteid"]])) ? $localitesCodes[$registre_data["localiteid"]] : "OUA";	
			$dateYear                        = (isset($postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                       = (isset($postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                         = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			$zendDate                        = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			$numeroParts                     = $model->getNumParts($numero);
			$registreYear                    = (isset($numeroParts["annee"]))?intval($numeroParts["annee"]) : substr( $numero, 5, 4);	
            $numeroPrefixToCheck             = sprintf("BF%s", $localiteCode);	
            if( intval($registreYear) < 2000) {
				$registreYear                = $dateYear;
			}				
			/*			
			 if(stripos($numero, $numeroPrefixToCheck) === FALSE) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il devrait commencer par %s", $numero, $numeroPrefixToCheck);
			}
            if( strlen($registre_data["numero"]) < 14) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter au moins 14 caractères", $registre_data["numero"] );
			}*/				
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;

            if(!intval($registre_data["date"]) || (intval($registre_data["date"]) >= time()) ) {
				$errorMessages[]             = "Veuillez indiquer une date d'inscription valide";
			}			
			$registre_data["type"]              = 1;
			$registre_data["statut"]            = 1;
			$registre_data["category"]          = "P0";
			$registre_data["checked"]           = intval($checkDocuments);
			$registre_data["description"]       = $stringFilter->filter( $registre_data["description"]);
			$registre_data["adresse"]           = (isset( $postData["adresse2"]  ))? $stringFilter->filter($postData["adresse2"]  ) : "";
			$registre_data["telephone"]         = (isset( $postData["telephone2"]))? $stringFilter->filter($postData["telephone2"]) : "";
			$registre_data["creatorid"]         = $me->userid;
			$registre_data["date"]              = ( intval($registre_data["date"]))? $registre_data["date"] : 0;
			$registre_data["addressid"]         = 0;
			$registre_data["communeid"]         = 0;
			$registre_data["ifuid"]             = 0;
			$registre_data["numifu"]            = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"] ) : "";
			$registre_data["cnssid"]            = 0;
			$registre_data["numcnss"]           = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"]) : "";
			$registre_data["cpcid"]             = 0;
			$registre_data["statusid"]          = 0;
			
			$registre_data["annee"]             = $registreYear;
			$registre_data["capital"]           = (isset($postData["capital"]  ))? floatval($postData["capital"])   : 0;
			$registre_data["capital_nature"]    = (isset($postData["capital"]  ))? floatval($postData["capital"])   : 0;
			$registre_data["capital_numeraire"] = (isset($postData["capital"]  ))? floatval($postData["capital"])   : 0;
			$registre_data["nbactions"]         = (isset($postData["nbactions"]))? floatval($postData["nbactions"]) : 0;
			$registre_data["creationdate"]      = time();	
			$registre_data["updateduserid"]     = 0;
			$registre_data["updatedate"]        = 0;
			$registre_data["domaineid"]         = intval( $registre_data["domaineid"] ) ;
			$registre_data["parentid"]          = 0;
			
			if(!empty($registre_data["numifu"])) {
				if( $foundRegistre = $model->findRow( $registre_data["numifu"], "numifu" , null , false )) {
					$errorMessages[]            = sprintf("Un RCCM existant porte déjà le numéro IFU %s", $registre_data["numifu"]);
				}
			}
            if(!empty($registre_data["numcnss"])) {
				if( $foundRegistre = $model->findRow( $registre_data["numcnss"], "numcnss" , null , false )) {
					$errorMessages[]            = sprintf("Un RCCM existant porte déjà le numéro CNSS %s", $registre_data["numcnss"]);
				}
			}			
			if(!$findDocuments ) {
				$documentsUploadAdapter         = new Zend_File_Transfer();
			    $documentsUploadAdapter->addValidator("Count"    , false , 3 );
			    $documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			    /*$documentsUploadAdapter->addValidator("Size"     , false , array("max" => 50000));
			    $documentsUploadAdapter->addValidator("FilesSize", false , array("max" => 50000));*/
			    if(!$documentsUploadAdapter->isUploaded("docmini") ) {
				    $errorMessages[]         = "Le document formulaire n'a pas été fourni";
			    }
			    if(!$documentsUploadAdapter->isUploaded("docoriginal")) {
				    $errorMessages[]         = "Le document  personnel n'a pas été fourni";
			    }
				if( $checkDocuments && empty($errorMessages) ) {
					$formulaireFilename      = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename       = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;
                    if( $checkDocuments ) {
						$checkRccmData       = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, "numero" => $numero);
						if((false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) && $checkDocuments ) {
							$errorMessages[] =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
						}
					}															
				}
			} else {				
				$rccmFormulaireFilepath      = $fileSource.DS.$localiteCode.DS. $registreYear. DS . $numero. DS. $numero."-FR.pdf";
			    $rccmPersonnelFilepath       = $fileSource.DS.$localiteCode.DS. $registreYear. DS . $numero. DS. $numero."-PS.pdf";
				
				if(!file_exists( $rccmFormulaireFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire du RC N° %s n'existe pas dans la source des documents %s", $numero, $rccmFormulaireFilepath);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier du RC N° %s n'existe pas dans la source des documents %s", $numero, $rccmPersonnelFilepath);
				}
				if( $checkDocuments) {
					$checkRccmData           = array("formulaire" => $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,"numero" => $numero);
					if((false == $this->__checkRccmFiles($checkRccmData, $errorMessages ))) {
						$errorMessages[]     =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents %s", $numero, $rccmPersonnelFilepath);
					}
				}				
			}				
			if( !count( $errorMessages  )) {
				$emptyData                   = $model->getEmptyData();
				$clean_registre_data         = array_intersect_key($registre_data,$emptyData);
				if(   $dbAdapter->insert( $tableName, $clean_registre_data) ) {
					  $registreid            = $dbAdapter->lastInsertId();	
					  $defaultParams         = array("default_start_month"=>$dateMonth,"default_year"=>$dateYear,"default_localiteid"=>$registre_data["localiteid"],
					                                 "default_domaineid"=>$registre_data["domaineid"],"default_find_documents_src"=>$fileSource, 
													 "default_check_documents" => DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY, "default_find_documents"=>DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY);
                      $myParams              = $me->getParams();
                      $myPreferedParams      =	array_merge( $myParams, $defaultParams);				  
					  $me->setParams( $myPreferedParams );				  
					  //On enregistre les informations du représentant					  
					  $dateNaissanceYear                  = (isset($postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
					  $dateNaissanceMonth                 = (isset($postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
					  $dateNaissanceDay                   = (isset($postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
					  
					  $representant_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					  $representant_data["lieunaissance"] = $stringFilter->filter( $representant_data["lieunaissance"]  );
					  $representant_data["marital_status"]= $stringFilter->filter( $representant_data["marital_status"] );
					  $representant_data["nom"]           = $stringFilter->filter( $representant_data["nom"] );
					  $representant_data["prenom"]        = $stringFilter->filter( $representant_data["prenom"]   );
					  $representant_data["adresse"]       = $stringFilter->filter( $representant_data["adresse"]  );
					  $representant_data["city"]          = 0;
					  $representant_data["profession"]    = ( isset( $postData["fonction"])) ? $stringFilter->filter($postData["fonction"]) : "GERANT";
					  $representant_data["country"]       = $stringFilter->filter( $representant_data["country"]  );
					  $representant_data["email"]         = $stringFilter->filter( $representant_data["email"]    );
					  $representant_data["telephone"]     = $stringFilter->filter( $representant_data["telephone"]);
					  $representant_data["passport"]      = $stringFilter->filter( $representant_data["passport"] );
					  $representant_data["cnib"]          = (isset( $postData["cnib"] ))? $stringFilter->filter( $postData["cnib"] ) : $representant_data["passport"];
					  $representant_data["sexe"]          = $stringFilter->filter( $representant_data["sexe"] );
					  $representant_data["structure"]     = "";
					  $representant_data["creatorid"]     = $me->userid;
					  $representant_data["creationdate"]  = time();
					  $representant_data["updateduserid"] = 0;
					  $representant_data["updatedate"]    = 0;
					  
					  if( $dbAdapter->insert( $prefixName."rccm_registre_representants", $representant_data ) ) {
					  	  $representantid                 = $dbAdapter->lastInsertId();
					  	  if( $dbAdapter->insert( $prefixName."rccm_registre_dirigeants", array("registreid"=>$registreid,"representantid"=>$representantid,"entrepriseid"=>0,"fonction"=>$representant_data["profession"]))) {					  	  	
							  $rcPathroot                 = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode . DS . $registreYear. DS . $numero;
							  $formulairePath             = $rcPathroot . DS . $numero."-FR.pdf";
					  	  	  $personnelPath              = $rcPathroot . DS . $numero."-PS.pdf";
							  
							  if(!file_exists($formulairePath)) {
								  if(!$findDocuments ) {
									   //On essaie d'enregistrer les documents du registre
									   $modelDocument                  = $this->getModel("document");					  	  	       
									   $documentData                   = array();
									   $documentData["userid"]         = $me->userid;
									   $documentData["category"]       = 1;
									   $documentData["resource"]       = "registrephysique";
									   $documentData["resourceid"]     = 0;
									   $documentData["filedescription"]= $registre_data["description"];
									   $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
									   $documentData["creationdate"]   = time();
									   $documentData["creatoruserid"]  = $me->userid;
																													  
									   
									   $documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath, "overwrite" => true), "docmini");
									   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS . $registreYear) ) {
										   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
											   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
											   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
											   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
										   }
										   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
											   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
											   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
										   }
										   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear);
										   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear, 0777 );									   
									   }
									   if(!is_dir($rcPathroot)) {
										   @chmod($rcPathroot, 0777 );
										   @mkdir($rcPathroot);
									   }					  	  	  
									   if(!$documentsUploadAdapter->isUploaded("docmini") ) {
										   $errorMessages[]                      = "Le formulaire n'a pas été fourni ";
									   } else {
										   $documentsUploadAdapter->receive("docmini");
										   if( $documentsUploadAdapter->isReceived("docmini") ) {
											   $miniFileSize                     = $documentsUploadAdapter->getFileSize("docmini");
											   $formulaireData                   = $documentData;
											   $formulaireData["filename"]       = $modelDocument->rename("FORMULAIRE", $numero);
											   $formulaireData["filepath"]       = $formulairePath;
											   $formulaireData["access"]         = 0 ;
											   $formulaireData["filextension"]   = "pdf";
											   $formulaireData["filesize"]       = floatval( $miniFileSize );
											   if( $dbAdapter->insert( $prefixName."system_users_documents", $formulaireData ) ) {
												   $documentid                   = $dbAdapter->lastInsertId();
												   $dbAdapter->insert( $prefixName."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
											   } else {
												   $errorMessages[]              = "Les informations du formulaire ont été partiellement enregistrées";
											   }					  	  	  	   	
									   } else {
												   $errorMessages[]              = "Le formulaire n'a pas été copié pour des raisons suivantes: ".implode(", ", $documentsUploadAdapter->getMessages());
									   }
								  }	
								  if(!$documentsUploadAdapter->isUploaded("docoriginal") ) {
									  $errorMessages[]      = "Le  document personnel n'a pas été transféré";
								  } else {
									$documentsUploadAdapter->addFilter("Rename", array("target" => $personnelPath, "overwrite" => true), "docoriginal");
									$documentsUploadAdapter->receive("docoriginal");
									if( $documentsUploadAdapter->isReceived("docoriginal") ) {
										$personnelDocFileSize                 = $documentsUploadAdapter->getFileSize("docoriginal");
										$personnelDocData                     = $documentData;
										$personnelDocData["filename"]         = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
										$personnelDocData["filepath"]         = $personnelPath;
										$personnelDocData["access"]           = 6;
										$personnelDocData["filextension"]     = "pdf";
										$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
										if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
											$documentid                       = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 6));
										} else {
											$errorMessages[]                   = "Les informations du document personnel ont été partiellement enregistrées";
										}					  	  	  	
									} else {
											$errorMessages[]                   = "Le document personnel n'a pas été copié sur le serveur pour les raisons suivantes: ".implode(", ", $documentsUploadAdapter->getMessages());
									}
								   }
								  } else {
										$rccmFormulaireFilepath            = $fileSource.DS. $localiteCode . DS . $registreYear. DS . $numero. DS. $numero."-FR.pdf";
										$rccmPersonnelFilepath             = $fileSource.DS. $localiteCode . DS . $registreYear. DS . $numero. DS. $numero."-PS.pdf";
										$rcPathroot                        = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode . DS . $registreYear. DS . $numero;
										if(file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
										   $newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
										   $newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
										   $modelDocument                  = $this->getModel("document");					  	  	       					  	  	  
										   $documentData                   = array();
										   $documentData["userid"]         = $me->userid;
										   $documentData["category"]       = 1;
										   $documentData["resource"]       = "registrephysique";
										   $documentData["resourceid"]     = 0;
										   $documentData["filedescription"]= $registre_data["description"];
										   $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
										   $documentData["creationdate"]   = time();
										   $documentData["creatoruserid"]  = $me->userid;
										   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS . $registreYear) ) {
											   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
												   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
												   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
												   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
											   }
											   if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
												   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
												   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
											  }
												   @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear);
												   @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear, 0777 );									   
										   }
										   if(!is_dir($rcPathroot)) {
											  @chmod($rcPathroot, 0777 );
											  @mkdir($rcPathroot);
										   }
										   if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
											   $formulaireFileData                 = $documentData;
											   $formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE",$numero);
											   $formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
											   $formulaireFileData["access"]       = 0 ;
											   $formulaireFileData["filextension"] = "pdf";
											   $formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
											   if( $dbAdapter->insert( $prefixName."system_users_documents", $formulaireFileData)) {
												   $documentid                     = $dbAdapter->lastInsertId();
												   $dbAdapter->insert( $prefixName."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0));
											   } else {
												   $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
											   }
										   } else {
												   $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
										   }
										   if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
											   $personnelFileData                  = $documentData;
											   $personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
											   $personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
											   $personnelFileData["access"]        = 6;
											   $personnelFileData["filextension"]  = "pdf";
											   $personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
											   if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
												   $documentid                     = $dbAdapter->lastInsertId();
												   $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
											   } else {
												   $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
											   }
										   } else {
												   $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
										   }
										} else {
												   $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car les documents n'ont pas été trouvés", $numero);
										}
								  }
							  }							  
					  	  	  if( !count( $errorMessages ) ) {
					  	  	  	  if( $this->_request->isXmlHttpRequest() ) {
					  	  	  		  $this->_helper->viewRenderer->setNoRender(true);
					  	  	  		  $this->_helper->layout->disableLayout(true);
					  	  	  		  echo ZendX_JQuery::encodeJson(array("success" => "Les informations du registre de type physique ont été enregistrées avec succès"));
					  	  	  		  exit;
					  	  	  	   }
					  	  	  	      $this->setRedirect("Les informations du registre de type physique ont été enregistrées avec succès", "success" );
					  	  	  	      if( $newCreation ) {
										  $this->redirect("admin/registrephysique/create");
									  } else {
										  $this->redirect("admin/registrephysique/infos/id/" . $registreid );
									  }									  					  	  	  	
					  	  	  }					  	  	  					  	  	  					  	  	  					  	  	
					  	  } else {
					  	  	$errorMessages[]= " Les informations du registre ont été partiellement enregistrées, veuillez reprendre l'opération";
					  	  }
					  } else {
					  	$errorMessages[]    = " Les informations du représentant n'ont pas été enregistrées, veuillez reprendre l'opération";
					  }					  					  					 					
				}  else {
					    $errorMessages[]    = " Les informations du registre n'ont pas été enregistrées, veuillez reprendre l'opération";
				}
			} 
			            $defaultData        = array_merge( $defaultData , $postData );
		}		
		if( count( $errorMessages ) ) {
			if( intval($registreid)) {
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data      = $defaultData;
		$this->view->domaines  = $domaines;
		$this->view->localites = $localites;
	}
	
	
	public function editAction()
	{
		$this->view->title = " Mettre à jour les informations d'un registre de commerce ";
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id", 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registrephysique/list");
		}		
		$model                 = $this->getModel("registre");
		$modelDirigeant        = $this->getModel("registredirigeant");
		$modelRepresentant     = $this->getModel("representant");
		$modelDomaine          = $this->getModel("domaine");
		$modelLocalite         = $this->getModel("localite");
		$modelDocument         = $this->getModel("document");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$dirigeant             = $modelDirigeant->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$dirigeant) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registrephysique/list");
		}
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);
		$representant                        = $modelRepresentant->findRow( $dirigeant->representantid , "representantid", null , false );		
		$registreData                        = $registre->toArray();
		$dirigeantData                       = ( $dirigeant    ) ? $dirigeant->toArray()    : array();
		$representantData                    = ( $representant ) ? $representant->toArray() : array();
		$defaultData                         = array_merge( $representantData, $dirigeantData, $registreData );
		$errorMessages                       = array();  		
		
		$defaultData["date_year"]            = date("Y", $registre->date);
		$defaultData["date_month"]           = date("m", $registre->date);
		$defaultData["date_day"]             = date("d", $registre->date);
		$defaultData["date_naissance_year"]  = date("Y", strtotime($representant->datenaissance));
		$defaultData["date_naissance_month"] = date("m", strtotime($representant->datenaissance));
		$defaultData["date_naissance_day"]   = date("d", strtotime($representant->datenaissance));
		$defaultData["check_documents"]      = $checkDocuments = $registre->checked;
		$defaultData["find_documents"]       = DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
		$defaultData["find_documents_src"]   = $fileSource     = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
		
		if( $this->_request->isPost()) {
			$postData                        = $this->_request->getPost();
			$update_registre_data            = $registre_data = array_merge($registreData  , array_intersect_key( $postData,  $registreData) );
			/*$update_physique_data            = array_merge($physiqueData  , array_intersect_key( $postData,  $physiqueData) );*/
			$update_representant_data          = array_merge($representantData, array_intersect_key( $postData,  $representantData) );
			$me                              = Sirah_Fabric::getUser();
			$modelTable                       = $me->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($update_registre_data["numero"])));
			$libelle                         = $stringFilter->filter( $update_registre_data["libelle"] );
			$findDocuments                   = (isset($postData["find_documents"])) ? intval($postData["find_documents"]) : DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
			$checkDocuments                  = (isset($postData["check_documents"]))? intval($postData["check_documents"]): DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
			
			if(!is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}			
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]                     = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false ) && ( $registre->numero != $numero ) ) {
				$errorMessages[]                     = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$update_registre_data["numero"]      = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                     = " Veuillez entrer un nom commercial valide pour ce registre";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ( $registre->libelle != $libelle) ) {
		    } else {
				$update_registre_data["libelle"]     = $libelle;
			}
			if(($update_representant_data["sexe"] != "F") && ($update_representant_data["sexe"] != "M") && ($update_representant_data["sexe"] != "H")) {
                $errorMessages[]                     = "Veuillez indiquer un sexe valide (entre F, M, et H)";
		    }
			if(!$strNotEmptyValidator->isValid( $update_representant_data["nom"] ) || !$strNotEmptyValidator->isValid( $update_representant_data["prenom"] )  ) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour l'representant";
			}
			if( !intval( $update_registre_data["localiteid"] ) || !isset( $localites[$update_registre_data["localiteid"]]) ) {
				$errorMessages[]                     = "Veuillez sélectionner une localité valide";
			} else {
				$update_registre_data["localiteid"]  = intval( $update_registre_data["localiteid"] ) ;
			}
			$localiteCode                            = (isset( $localitesCodes[$update_registre_data["localiteid"]])) ? $localitesCodes[$update_registre_data["localiteid"]] : "OUA";	 
			$dateYear                                = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                               = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                                 = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";
			$zendDate                                = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			$rcPathroot                              = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode . DS . $dateYear. DS . $numero;
            $numeroPrefixToCheck                     = sprintf("BF%s%dA", $localiteCode, $dateYear);
			
			$registreYear                            = substr($numero, 5, 4 );
			
			if(stripos($update_registre_data["numero"], $numeroPrefixToCheck) === FALSE ) {
				$errorMessages[]                     = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il devrait commencer par %s", $update_registre_data["numero"], $numeroPrefixToCheck);
			}
            if( strlen($update_registre_data["numero"])< 14) {
				$errorMessages[]                     = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $update_registre_data["numero"] );
			}			
			
			$update_registre_data["domaineid"]       = intval( $update_registre_data["domaineid"] ) ;
			$update_registre_data["date"]            = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["type"]            = 1;
			$update_registre_data["checked"]         = intval($checkDocuments);
			$update_registre_data["statut"]          = intval( $update_registre_data["statut"]  );
			$update_registre_data["numifu"]          = $stringFilter->filter( $update_registre_data["numifu"]);
			$update_registre_data["numcnss"]         = $stringFilter->filter( $update_registre_data["numcnss"]);
			$update_registre_data["description"]     = $stringFilter->filter( $update_registre_data["description"]);
			$update_registre_data["adresse"]         = $stringFilter->filter( $update_registre_data["adresse"]    );
			$update_registre_data["telephone"]       = $stringFilter->filter( $update_registre_data["telephone"]  );
			$update_registre_data["annee"]           = $registreYear;
			$update_registre_data["updateduserid"]   = $me->userid;
			$update_registre_data["updatedate"]      = time();
			
			if(!intval($update_registre_data["date"]) || ( $update_registre_data["date"] >= time())) {
				$errorMessages[]                     = "Veuillez renseigner une date d'inscription valide";
			}	
            if(!empty($registre_data["numifu"])) {
				$foundRegistre                       = $model->findRow( $registre_data["numifu"], "numifu" , null , false );
				if( $foundRegistre  && $foundRegistre->registreid != $registreid ) {
					$errorMessages[]                 = sprintf("Un RCCM existant porte déjà le numéro IFU %s", $registre_data["numifu"]);
				}
			}
            if(!empty($registre_data["numcnss"])) {
				$foundRegistre                       = $model->findRow( $registre_data["numcnss"], "numcnss" , null , false );
				if( $foundRegistre  && $foundRegistre->registreid != $registreid )  {
					$errorMessages[]                 = sprintf("Un RCCM existant porte déjà le numéro CNSS %s", $registre_data["numcnss"]);
				}
			}			
			//On enregistre les informations du représentant
			$dateNaissanceYear                         = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
			$dateNaissanceMonth                        = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay                          = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
				
			$update_representant_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$update_representant_data["lieunaissance"] = $stringFilter->filter( $update_representant_data["lieunaissance"]  );
			$update_representant_data["marital_status"]= $stringFilter->filter( $update_representant_data["marital_status"] );
			$update_representant_data["nom"]           = $stringFilter->filter( $update_representant_data["nom"] );
			$update_representant_data["prenom"]        = $stringFilter->filter( $update_representant_data["prenom"]  );
			$update_representant_data["adresse"]       = $stringFilter->filter( $update_representant_data["adresse"] );
			$update_representant_data["email"]         = $stringFilter->filter( $update_representant_data["email"]   );
			$update_representant_data["passport"]      = $stringFilter->filter( $update_representant_data["passport"]);
			$update_representant_data["profession"]    = (isset( $postData["fonction"])) ? $stringFilter->filter($postData["fonction"]) : $stringFilter->filter( $update_representant_data["profession"]);
			$update_representant_data["sexe"]          = $stringFilter->filter( $update_representant_data["sexe"] );
			$update_representant_data["city"]          = 0;
			$update_representant_data["country"]       = $stringFilter->filter( $update_representant_data["country"]   );
			$update_representant_data["telephone"]     = $stringFilter->filter( $update_representant_data["telephone"] );
			$update_representant_data["structure"]     = "";						
			$update_representant_data["updateduserid"] = $me->userid;
			$update_representant_data["updatedate"]    = time();
			
			$documentsUploadAdapter                    = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			$documentsUploadAdapter->setOptions(array("ignoreNoFile" => true));
			
			if(!$findDocuments ) {
				if( $checkDocuments && empty($errorMessages) && $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
					$formulaireFilename      = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename       = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;	
					if( $checkDocuments ) {
						$checkRccmData       = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, "numero" => $numero);
						if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
							$errorMessages[] =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
						}
					}					
				}
			} else {				
				$rccmFormulaireFilepath      = $fileSource.DS. $localiteCode. DS . $registreYear. DS . $numero. DS. $numero."-FR.pdf";
			    $rccmPersonnelFilepath       = $fileSource.DS. $localiteCode. DS . $registreYear. DS . $numero. DS. $numero."-PS.pdf";
				/*if(!file_exists( $rccmFormulaireFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				$checkRccmData               = array("formulaire" => $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,"numero" => $numero);
				if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
					$errorMessages[]         =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents", $numero);
				}*/
			}			
			if(isset(  $update_registre_data["registreid"])) {
				unset( $update_registre_data["registreid"] );
			}
			if(isset(  $update_representant_data["representantid"])) {
				unset( $update_representant_data["representantid"] );
			}			 
			$registre->setFromArray(     $update_registre_data );
			$representant->setFromArray( $update_representant_data );
			if(empty($errorMessages)) {
				if( $registre->save() && $representant->save() ) {
					if(!$findDocuments ) {
						$documentData                   = array();
					  	$documentData["userid"]         = $me->userid;
					  	$documentData["category"]       = 1;
					  	$documentData["resource"]       = "registrephysique";
					  	$documentData["resourceid"]     = 0;
					  	$documentData["filedescription"]= $registre_data["description"];
					  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
					  	$documentData["creationdate"]   = time();
					  	$documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	$formulairePath                 = $rcPathroot . DS . $numero."-FR.pdf";
					  	$personnelPath                  = $rcPathroot . DS . $numero."-PS.pdf";
						if( $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS . $registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
								}
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $registreYear, 0777 );									   
							}
							if(!is_dir($rcPathroot)) {
								@chmod($rcPathroot, 0777 );
								@mkdir($rcPathroot);
							}
							$documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath,"overwrite" => true), "docmini");
							$documentsUploadAdapter->receive("docmini");
					  	  	if( $documentsUploadAdapter->isReceived( "docmini") ) {
					  	  	  	$miniFileSize                     = $documentsUploadAdapter->getFileSize("docmini");
					  	  	  	$formulaireData                   = $documentData;
					  	  	  	$formulaireData["filename"]       = $modelDocument->rename("FORMULAIRE",$numero);
					  	  	  	$formulaireData["filepath"]       = $formulairePath;
					  	  	  	$formulaireData["access"]         = 0 ;
					  	  	  	$formulaireData["filextension"]   = "pdf";
					  	  	  	$formulaireData["filesize"]       = floatval( $miniFileSize );
								$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
							    $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
					  	  	  	   	$documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
					  	  	  	} else {
					  	  	  	   	$errorMessages[]              = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}					  	  	  	   	
					  	  	} else {
					  	  	  	   	$errorMessages[]              = "Le formulaire n'a pas été copié sur le serveur";
					  	  	}
							$documentsUploadAdapter->addFilter("Rename", array("target" => $personnelPath, "overwrite" => true), "docoriginal");
					  	  	$documentsUploadAdapter->receive("docoriginal");
							if( $documentsUploadAdapter->isReceived("docoriginal") ) {
					  	  	    $personnelDocFileSize                 = $documentsUploadAdapter->getFileSize("docoriginal");
					  	  	  	$personnelDocData                     = $documentData;
					  	  	  	$personnelDocData["filename"]         = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
					  	  	  	$personnelDocData["filepath"]         = $personnelPath;
					  	  	  	$personnelDocData["access"]           = 6;
					  	  	  	$personnelDocData["filextension"]     = "pdf";
					  	  	  	$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							    $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  		$documentid                       = $dbAdapter->lastInsertId();
					  	  	  		$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 6));
					  	  	  	} else {
					  	  	  		$errorMessages[]                  = "Les informations du document personnel ont été partiellement enregistrées";
					  	  	  	}					  	  	  	
					  	  	} else {
					  	  	  		$errorMessages[]                  = "Le document personnel n'a pas été copié sur le serveur";
					  	  	}
						}					  	  	       					  	  	
					} else {
						$rccmFormulaireFilepath             = $fileSource.DS. $localiteCode . DS . $registreYear. DS . $numero. DS. $numero."-FR.pdf";
			            $rccmPersonnelFilepath              = $fileSource.DS. $localiteCode . DS . $registreYear. DS . $numero. DS. $numero."-PS.pdf";
						
						if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
							$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
							$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
										   					  	  	       					  	  	  
					  	  	$documentData                   = array();
					  	  	$documentData["userid"]         = $me->userid;
					  	  	$documentData["category"]       = 1;
					  	  	$documentData["resource"]       = "registrephysique";
					  	  	$documentData["resourceid"]     = 0;
					  	  	$documentData["filedescription"]= $registre_data["description"];
					  	  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	$documentData["creationdate"]   = time();
					  	  	$documentData["creatoruserid"]  = $me->userid;
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS .$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
								}
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS .$registreYear);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS .$registreYear, 0777 );									   
							}
							if(!is_dir($rcPathroot)) {
								@chmod($rcPathroot, 0777 );
								@mkdir($rcPathroot);
							}
							if( file_exists($newRccmFormulaireFilepath)) {
								@unlink($newRccmFormulaireFilepath);
							}
							if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
					  	  	  	$formulaireFileData                 = $documentData;
					  	  	  	$formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE",$numero);
					  	  	  	$formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
					  	  	  	$formulaireFileData["access"]       = 0 ;
					  	  	  	$formulaireFileData["filextension"] = "pdf";
					  	  	  	$formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
								$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
							    $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0 ));
					  	  	  	} else {
					  	  	  	    $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
								    $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
							if( file_exists( $newRccmPersonnelFilepath )) {
								@unlink( $newRccmPersonnelFilepath );
							}
							if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	$personnelFileData                  = $documentData;
					  	  	  	$personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER",$numero);
					  	  	  	$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	  	$personnelFileData["access"]        = 6;
					  	  	  	$personnelFileData["filextension"]  = "pdf";
					  	  	  	$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
								$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							    $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
					  	  	  	} else {
					  	  	  	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
									$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
						}  
					}															
					if( !count( $errorMessages ) ) {
						if( $this->_request->isXmlHttpRequest()) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							$defaultData               = array_merge( $update_physique_data, $update_representant_data, $update_registre_data, $postData );
							$jsonErrorArray            = $defaultData;
							$jsonErrorArray["success"] = sprintf("Les informations du registre de commerce numéro %s ont été mises à jour avec succès", $numero);
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect(sprintf("Les informations du registre de commerce numéro %s ont été mises à jour avec succès", $numero), "success" );
						$this->redirect("admin/registrephysique/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été effectuée dans les informations du registre de commerce"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été effectuée dans les informations du registre de commerce" , "message");
					$this->redirect("admin/registrephysique/infos/id/".$registreid);
				}
			} else {
				    $defaultData   = array_merge($update_representant_data, $update_registre_data, $postData );				
			}					
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data        = $defaultData;
		$this->view->localiteid  = $localiteid;
		$this->view->registreid  = $registreid;
		$this->view->domaines    = $domaines;
		$this->view->localites   = $localites;
	}	
 		
		
	public function infosAction()
	{		
		$registreid              = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registrephysique/list");
		}		
		$model                 = $this->getModel("registre");
		$modelDirigeant        = $this->getModel("registredirigeant");
		$modelRepresentant     = $this->getModel("representant");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$dirigeant             = $modelDirigeant->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$dirigeant) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registrephysique/list");
		}
		$representant              = $modelRepresentant->findRow( $dirigeant->representantid , "representantid", null , false );		
		$registreData              = $registre->toArray();
		$dirigeantData             = ( $dirigeant    ) ? $dirigeant->toArray() : array();
		$representantData          = ( $representant ) ? $representant->toArray() : array();
		$defaultData               = array_merge( $representantData, $dirigeantData, $registreData );
		
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->registreid    = $registreid;
		$this->view->representant  = $representant;
		$this->view->domaine       = $registre->findParentRow("Table_Domaines");
		$this->view->localite      = $registre->findParentRow("Table_Localites");
		$this->view->documents     = $documents = $registre->documents();
		$this->view->modifications = $registre->modifications();
		$this->view->suretes       = $registre->suretes();
		$this->view->title         = sprintf("Les informations du registre de commerce numero %s", $registre->numero);
		$this->view->columns       = array("left");	
	} 
	
	public function updatealldocsAction()
	{
		$model                     = $this->getModel("registre");
		$modelPhysique             = $this->getModel("registrephysique");
		$modelRepresentant         = $this->getModel("representant");
		$modelDocument             = $this->getModel("document");
		$dbAdapter                 = $model->getTable()->getAdapter();
		$prefixName                = $model->getTable()->info("namePrefix");
		$me                        = Sirah_Fabric::getUser();
		$fileSource                = (is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		
		$ids                       = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages             = array();
		if( is_string($ids) ) {
			$ids                   = explode("," , $ids );
		}
		$ids                       = (array)$ids;
		if( count(   $ids )) {
			foreach( $ids as $registreid ) {
				     $registre     = $model->findRow( $registreid, "registreid" , null , false);
					 if( $registre) {
						 $numero                 = $registre->numero;
		                 $dateYear               = substr( $numero, 5, 4);
		                 $localite               = $registre->findParentRow("Table_Localites");
		                 $localiteCode           = ($localite ) ? $localite->code : "";
						 $rccmFormulaireFilepath = $fileSource. DS.  $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-FR.pdf";
		                 $rccmPersonnelFilepath  = $fileSource. DS.  $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-PS.pdf";
		                 $rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES"    . DS . $localiteCode. DS . $dateYear. DS . $numero;
					     if( empty($localiteCode) || (strlen($dateYear) != 4)) {
							 $errorMessages[]    = sprintf("Le RCCM n° %s n'est pas valide ", $numero );
							 continue;
						 }
						 if(!file_exists($rccmFormulaireFilepath)) {
			                 $errorMessages[]    = sprintf("Dans le dossier source, le formulaire du registre n° %s est manquant", $numero);
							 continue;
		                 }
		                 if(!file_exists( $rccmPersonnelFilepath )) {
			                $errorMessages[]     = sprintf("Dans le dossier source, le fond de dossier du registre n° %s est manquant", $numero);
							continue;
		                 }
		                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS . $dateYear) ) {
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
			                 }
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
			                 }
				            @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $dateYear);
				            @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $dateYear, 0777 );									   
		                 }
		                 if(!is_dir($rcPathroot)) {
			                @chmod($rcPathroot, 0777 );
			                @mkdir($rcPathroot);
		                 }
						 $documentData                           = array();
		                 $documentData["userid"]                 = $me->userid;
		                 $documentData["category"]               = 1;
		                 $documentData["resource"]               = "registrephysique";
		                 $documentData["resourceid"]             = 0;
		                 $documentData["filedescription"]        = $registre->description;
		                 $documentData["filemetadata"]           = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
		                 $documentData["creationdate"]           = time();
		                 $documentData["creatoruserid"]          = $me->userid;
		                 $newRccmFormulaireFilepath              = $rcPathroot . DS . $numero."-FR.pdf";
		                 $newRccmPersonnelFilepath               = $rcPathroot . DS . $numero."-PS.pdf";
						 if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
			                 $formulaireFileData                 = $documentData;
			                 $formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE", $numero);
			                 $formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
			                 $formulaireFileData["access"]       = 0 ;
			                 $formulaireFileData["filextension"] = "pdf";
			                 $formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
			                 $dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
			                 $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
			                 if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
				                 $documentid                     = $dbAdapter->lastInsertId();
				                 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
			                 } else {
				                 $errorMessages[]                = sprintf("Les informations du formulaire du RC n° %s ont été partiellement enregistrées", $numero);
								 continue;
			                 }
		                 } else {
				                 $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
								 continue;
		                 }
						 if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
			                 $personnelFileData                  = $documentData;
			                 $personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
				             $personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
				             $personnelFileData["access"]        = 6;
				             $personnelFileData["filextension"]  = "pdf";
				             $personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
				             $dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
				             $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
				             if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					             $documentid                     = $dbAdapter->lastInsertId();
					             $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				             } else {
					             $errorMessages[]                = sprintf("Les informations du formulaire du RC n° %s ont été partiellement enregistrées", $numero);
								 continue;
				             }
		                 } else {
					             $errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
								 continue;
		                 }
						$registre->updatedate                    = time();
				        $registre->updateduserid                 = $me->userid;
				        $registre->save(); 
					 } else {
						 $errorMessages[]                        = sprintf("Le registre ayant l'identifiant #id%d n'existe pas dans votre base de données", $registreid );
						 continue;
					 }
			}
		} else {
			$errorMessages[]                                     = "Aucun registre de commerce n'a été selectionné";
		}
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/registrephysique/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été re-indexés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été re-indexés avec succès", "success");
			$this->redirect("admin/registrephysique/list");
		}
	}

    public function updatedocsAction()
	{
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registrephysique/list");
		}		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelRepresentant     = $this->getModel("representant");
		$modelDocument         = $this->getModel("document");
		$dbAdapter             = $model->getTable()->getAdapter();
		$prefixName            = $model->getTable()->info("namePrefix");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		if(!$registre  ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registrephysique/list");
		}
		$numero                            = $registre->numero;
		$dateYear                          = substr( $numero, 5, 4);
		$localite                          = $registre->findParentRow("Table_Localites");
		$localiteCode                      = ($localite ) ? $localite->code : "";
		$me                                = Sirah_Fabric::getUser();
		$fileSource                        = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		if( empty( $localiteCode ) || (strlen( $dateYear ) != 4 )) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registrephysique/list");
		}
		$rccmFormulaireFilepath            = $fileSource.DS. $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-FR.pdf";
		$rccmPersonnelFilepath             = $fileSource.DS. $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-PS.pdf";
		$rcPathroot                        = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES"    . DS . $localiteCode. DS . $dateYear. DS . $numero;
		
		if(!file_exists($rccmFormulaireFilepath)) {
			$errorMessages[]               = "Dans le dossier source, le formulaire du registre est manquant ".$rccmFormulaireFilepath ;
		}
		if(!file_exists( $rccmPersonnelFilepath )) {
			$errorMessages[]               = "Dans le dossier source, le fond de dossier du registre est manquant";
		}
		if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode . DS . $dateYear) ) {
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
			}
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode)) {
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode, 0777 );
			}
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $dateYear);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS . $dateYear, 0777 );									   
		}
		if(!is_dir($rcPathroot)) {
			@chmod($rcPathroot, 0777 );
			@mkdir($rcPathroot);
		}
		$documentData                   = array();
		$documentData["userid"]         = $me->userid;
		$documentData["category"]       = 1;
		$documentData["resource"]       = "registrephysique";
		$documentData["resourceid"]     = 0;
		$documentData["filedescription"]= $registre->description;
		$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
		$documentData["creationdate"]   = time();
		$documentData["creatoruserid"]  = $me->userid;
		$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
		$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
		if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
			$formulaireFileData                 = $documentData;
			$formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE", $numero);
			$formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
			$formulaireFileData["access"]       = 0 ;
			$formulaireFileData["filextension"] = "pdf";
			$formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
			$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
			$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
			if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
				$documentid                     = $dbAdapter->lastInsertId();
				$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
			} else {
				$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
			}
		} else {
				$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
		}
		if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
			$personnelFileData                  = $documentData;
			$personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
			$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
			$personnelFileData["access"]        = 6;
			$personnelFileData["filextension"]  = "pdf";
			$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
			$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
			$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
				if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					$documentid                     = $dbAdapter->lastInsertId();
					$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				} else {
					$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
				}
		} else {
					$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
		}			
		if(empty( $errorMessages )) {
				$registre->updatedate               = time();
				$registre->updateduserid            = $me->userid;
				$registre->save();
				if( $this->_request->isXmlHttpRequest() ) {
				    echo ZendX_JQuery::encodeJson(array("success" => "Les nouveaux documents (à jour) de ce registre ont été indexés avec succès"));
				    exit;
			    }
			      $this->setRedirect("Les nouveaux documents (à jour) de ce registre ont été indexés avec succès", "success");
			      $this->redirect("admin/registrephysique/infos/registreid/".$registreid);
		} else {
				if( $this->_request->isXmlHttpRequest()) {
				    echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				    exit;
			    }
			    foreach( $errorMessages as $errorMessage) {
				         $this->_helper->Message->addMessage($errorMessage , "error");
			    }
			    $this->redirect("admin/registrephysique/infos/registreid/".$registreid);
		}				
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
			$this->redirect("admin/registrephysique/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registrephysique/list");
		}
	}
	
	public function ajaximportAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		//$this->getResponse()->setHeader("Content-Type", "application/json");
		if(!$this->_request->isPost() ) {
			echo ZendX_JQuery::encodeJson(array("error" => "La requête que vous avez transmise n'est pas valide"));
			exit;
		}
		$csvRow                            = $postData = $this->_request->getPost();
		/*echo ZendX_JQuery::encodeJson($csvRow );exit;*/
		$model                             = $this->getModel("registrephysique");
		$modelRegistre                     = $this->getModel("registre");
		$modelRepresentant                 = $this->getModel("representant");
		$modelDomaine                      = $this->getModel("domaine");
        $modelLocalite                     = $this->getModel("localite");
		$modelDocument                     = $this->getModel("document");
		$modelDirigeant                    = $this->getModel("registredirigeant");
		$modelRepresentant                 = $this->getModel("representant");
		
		$modelTable                        = $model->getTable();
		$prefixName                        = $modelTable->info("namePrefix");
		$dbDestination                     = $dbAdapter = $modelTable->getAdapter();
		$me                                = Sirah_Fabric::getUser();
		
		$registreDefaultData               = $model->getEmptyData();
		$representantDefaultData           = $modelRepresentant->getEmptyData();
		$domaines                          = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" ,"libelle")   , array() , 0 , null , false);
		$localites                         = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle")   , array() , 0 , null , false);
		$localitesCodes                    = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("code"      ,"localiteid"), array() , 0 , null , false);
		$countries                         = $this->view->countries();
		$localiteid                        = intval($this->_getParam("localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID));
		$domaineid                         = intval($this->_getParam("domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID ));
		$defaultData                       = array_merge($representantDefaultData, $registreDefaultData);
		$errorMessages                     = array();
		
		$defaultData["localiteid"]         = $localiteid;
		$defaultData["domaineid"]          = $domaineid ;
		$defaultData["find_documents_src"] = $fileSource = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		$registreid                        = 0;
		$registre                          = null;
		$registre2                         = null;
		$toRemove                          = false;
					//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter                      = new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
		$strNotEmptyValidator    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
		$numeroRegistre          = $numero  = (isset($csvRow["numero"]    ))? preg_replace("/\s/","", $stringFilter->filter(strtoupper($stringFilter->filter($csvRow["numero"])))): null;
		$localiteRegistre        = (isset($csvRow["localite"]             ))? strtoupper($stringFilter->filter($csvRow["localite"])) : "";
		$libelleRegistre         = (isset($csvRow["nom_commercial"]       ))? $stringFilter->filter($csvRow["nom_commercial"]) : "";
		$dateRegistre            = (isset($csvRow["date_enregistrement"]  ))? $stringFilter->filter($csvRow["date_enregistrement"]) : date("d/m/Y");
		$descriptionRegistre     = (isset($csvRow["description"]          ))? $csvRow["description"]: "";
		$exploitantLastname      = (isset($csvRow["nom"]                  ))? $stringFilter->filter($csvRow["nom"]) : "";
		$exploitantFirstname     = (isset($csvRow["prenom"]               ))? $stringFilter->filter($csvRow["prenom"]) : "";
		$exploitantLieuNaissance = (isset($csvRow["lieu_naissance"]       ))? $stringFilter->filter($csvRow["lieu_naissance"])  : "";
		$exploitantDateNaissance = (isset($csvRow["date_naissance"]       ))? $stringFilter->filter($csvRow["date_naissance"])  : "";
        $exploitantSexe          = (isset($csvRow["sexe"]                 ))? strtoupper($stringFilter->filter($csvRow["sexe"])): "";
		$exploitantAdresse       = (isset($csvRow["adresse"]              ))? $csvRow["adresse"]  : "";
		$exploitantTelephone     = (isset($csvRow["telephone"]            ))? $stringFilter->filter($csvRow["telephone"]): "";
		$exploitantPassport      = (isset($csvRow["passport"]             ))? $csvRow["passport"]: "";
		$exploitantNationalite   = (isset($csvRow["nationalite"]          ))? strtoupper($stringFilter->filter($csvRow["nationalite"])): "";
		$exploitantMaritalStatus = (isset($csvRow["situation_matrimonial"]))? $stringFilter->filter($csvRow["situation_matrimonial"]): "";
		$exploitantEmail         = (isset($csvRow["email"]                ))? $stringFilter->filter($csvRow["email"]): " ";
		$exploitantFonction      = (isset($csvRow["fonction"]             ))? $stringFilter->filter($csvRow["fonction"]): "GERANT";	
        $toUpdateRegistre        = $representantid = $registreRepresentant = $registreid = null;		
								 
		$localitesCodes2         = $localitesCodes;
        $inscriptionDate         = 0;
		$localiteCode            = (isset($postData["localite"]           )) ? $stringFilter->filter($postData["localite"]) : "";
        $dateYear                =  $registreYear = (isset($postData["date_year"])) ? intval($postData["date_year"]): null;
		array_flip($localitesCodes2);
								 
		if(!$strNotEmptyValidator->isValid($numero)) {
		    $errorMessages[]    = "Votre requête n'est pas valide, car le numéro RC est vide";
		} 
		if($toUpdateRegistre    = $model->findRow( $numero, "numero" , null , false )) {		   
		   $registre            = $toUpdateRegistre;
		   $registreid          = $toUpdateRegistre->registreid;
		   $registreDirigeant   = $modelDirigeant->findRow( $registreid, "registreid", null , false )	;
		   $representantid      = $registreDirigeant->representantid;
		   $registreRepresentant= ( $registreDirigeant ) ? $modelRepresentant->findRow( $registreDirigeant->representantid , "representantid", null , false ) : null;
		   if( $registre->creatorid != $me->userid) {
			   $errorMessages[] = sprintf("Le registre de commerce numéro %s existe déjà", $numeroRegistre);
		   }
		} 
        if(!$strNotEmptyValidator->isValid($libelleRegistre)) {
			$libelleRegistre    = sprintf("%s %s %s", $exploitantLastname, $exploitantFirstname, $numero);
		} 
		if( $registre2          = $model->findRow( $libelleRegistre , "libelle" , null , false )) {
			$registre2Id        = $registre2->registreid;
			if( $registre2Id   != $registreid ) {
			    $errorMessages[]= sprintf("Un registre existant porte le nom commercial %s , veuillez entrer un nom commercial différent du RC n° %s", $libelleRegistre, $numeroRegistre );
		    }
		}  
		if(!$strNotEmptyValidator->isValid($exploitantLastname ) || !$strNotEmptyValidator->isValid($exploitantFirstname)) {
			$errorMessages[]    = sprintf("Veuillez entrer un nom de famille et/ou prénom valide du representant du RC numéro %d", $numero );
		} 	
		if( empty($localiteRegistre) ) {
			$localiteRegistre   = strtoupper(substr($numero,2,3));
        } 
		if(!isset( $localitesCodes2[$localiteRegistre])) {
			$errorMessages[]    = sprintf("Veuillez sélectionner une localité valide du registre n° %s", $numero );
		} else {
			$localiteid         = intval($localitesCodes2[$localiteRegistre]);
			$localiteCode       = $localiteRegistre;
		}
		if( $strNotEmptyValidator->isValid( $dateRegistre ) && Zend_Date::isDate($dateRegistre, "dd/MM/YYYY" ) ) {
			$zendDate            = new Zend_Date($dateRegistre, "dd/MM/YYYY");
            $inscriptionDate     = $zendDate->get(Zend_Date::TIMESTAMP);
        } elseif( $strNotEmptyValidator->isValid($dateRegistre) && Zend_Date::isDate($dateRegistre,"YYYY-MM-dd")) {
			$zendDate            = new Zend_Date( $dateRegistre,"YYYY-MM-dd");
			$inscriptionDate     = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
	    } elseif( $strNotEmptyValidator->isValid($dateRegistre) && Zend_Date::isDate($dateRegistre,Zend_Date::ISO_8601)) {
			$zendDate            = new Zend_Date($dateRegistre,Zend_Date::ISO_8601);
			$inscriptionDate     = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
	    } else {
            $errorMessages[]     = sprintf("Veuillez indiquer une date d'enregistrement valide du registre numéro %s", $numero );
        }
        if(!$inscriptionDate || ( $inscriptionDate >= time())) {
            $errorMessages[]     = sprintf("Veuillez indiquer une date valide pour le registre n° %s", $numero );
		}									 
        $numeroPrefixToCheck     = sprintf("BF%s", $localiteRegistre);
        $numTypeRegistre         = strtoupper(trim(substr($numero, 9, 1)));
        if( $numTypeRegistre !== "A") {
			$errorMessages[]    = sprintf("Le numéro de la Personne Physique `%s` ne semble pas valide", $numero );
		}                           									 
        if( substr($numero,0,5) != $numeroPrefixToCheck ) {
			$errorMessages[]     = sprintf("Le numéro attribué au registre numéro %s n'est pas valide", $numero);
		}								 
		if( strlen($numero )   != 14) {
			$errorMessages[]    = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $numero);
		}
		if(!$dateYear ) {
			$dateYear           = $registreYear = substr($numero,5, 4);
		}
		if((strtolower($exploitantSexe)=="femme") || (strtolower($exploitantSexe) == "femmes") || (strtolower($exploitantSexe) == "feminin")) {
		    $exploitantSexe     = "F";
		} elseif( (strtolower($exploitantSexe) == "homme") || (strtolower($exploitantSexe) == "hommes") || (strtolower($exploitantSexe) == "masculin") ) {
		    $exploitantSexe     = "H";
		}
		if(($exploitantSexe != "F") && ($exploitantSexe != "M") && ( $exploitantSexe != "H")) {
            $errorMessages[]    = sprintf("Veuillez un sexe valide (entre F, M, et H) pour le registre n° %s", $numero);
		}
        if( $strNotEmptyValidator->isValid($exploitantDateNaissance) && Zend_Date::isDate($exploitantDateNaissance, "dd/MM/YYYY" ) ) {
            $zendDate           = new Zend_Date( $exploitantDateNaissance,Zend_Date::DATES , "fr_FR" );
            $dateNaissance      = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
		} else {
            $errorMessages[]    = sprintf("Veuillez indiquer une date de naissance valide du registre numéro %s", $numero );
        }
        if(!$strNotEmptyValidator->isValid( $exploitantLieuNaissance )) {
            $errorMessages[]    = sprintf("Veuillez indiquer un lieu de naissance valide du registre numéro %s", $numero);								 
		}
		if(!isset($countries[$exploitantNationalite] )) {
			if( $countryFound          = Sirah_Functions_ArrayHelper::search($countries, $exploitantNationalite )) {
				$exploitantNationalite = key($countryFound); 
			}
		}
		if( $toUpdateRegistre && empty($errorMessages) ) {
			$registreid                      = $toUpdateRegistre->registreid;
			$registre_data                   = array();
			$registre_data["numero"]         = $numero;
			$registre_data["libelle"]        = $libelleRegistre;
			$registre_data["localiteid"]     = $localiteid;
			$registre_data["date"]           = $inscriptionDate;
			$registre_data["type"]           = 1;
			$registre_data["description"]    = $descriptionRegistre;
			$registre_data["adresse"]        = $exploitantAdresse;
			$registre_data["telephone"]      = $exploitantTelephone;
			$registre_data["updateduserid"]  = $me->userid;
			$registre_data["updatedate"]     = time()+100;	
			$registre_data["domaineid"]      = $domaineid;
			
			if( $dbAdapter->update($prefixName."rccm_registre", $registre_data, "registreid=".$registreid) && $registreRepresentant && $representantid) {
				$representant_data                   = ($registreRepresentant ) ? $registreRepresentant->toArray() : array();
				$representant_data["datenaissance"]  = $dateNaissance;
				$representant_data["lieunaissance"]  = $exploitantLieuNaissance;
				$representant_data["nom"]            = $exploitantLastname;
				$representant_data["prenom"]         = $exploitantFirstname;
				$representant_data["adresse"]        = $exploitantAdresse;
				$representant_data["country"]        = $exploitantNationalite;
				$representant_data["email"]          = $exploitantEmail;
				$representant_data["marital_status"] = $exploitantMaritalStatus;
				$representant_data["telephone"]      = $exploitantTelephone;
				$representant_data["passport"]       = $exploitantPassport;
				$representant_data["sexe"]           = $exploitantSexe;
				$representant_data["profession"]     = $exploitantFonction;
				$representant_data["updateduserid"]  = $me->userid;
				$representant_data["updatedate"]     = time();
				
				if( $dbAdapter->update( $prefixName ."rccm_registre_representants", $representant_data, "representantid=".$representantid )) {
					$jsonData                        = $registre_data;
					$jsonData["success"]             = sprintf("Le registre numéro %s a été mis à jour avec succès", $numero );
					echo ZendX_JQuery::encodeJson($jsonData);
					exit;
				} else {
					$errorMessages[]                 = sprintf("Le registre numéro %s n'a pas été mis à jour avec succès", $numero );
				}
			} else {
					$errorMessages[]                 = sprintf("Le registre numéro %s n'a pas été mis à jour avec succès", $numero );
			}
		}
		if( empty($errorMessages ) && !empty( $numero )) {						
			if( $registre ) {
				$registreid            = $registre->registreid;
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique  WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid     IN (SELECT documentid     FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			$registre_data                   = array();
		    $registre_data["numero"]         = $numero;
	        $registre_data["libelle"]        = $libelleRegistre;
	        $registre_data["localiteid"]     = $localiteid;
		    $registre_data["date"]           = $inscriptionDate;
		    $registre_data["type"]           = 1;
		    $registre_data["statut"]         = 1;
            $registre_data["category"]       = "P0";
		    $registre_data["checked"]        = 1;
		    $registre_data["description"]    = $descriptionRegistre;
			$registre_data["adresse"]        = $exploitantAdresse;
			$registre_data["telephone"]      = $exploitantTelephone;
		    $registre_data["creatorid"]      = $me->userid;
            $registre_data["creationdate"]   = time()+100;	
            $registre_data["updateduserid"]  = 0;
            $registre_data["updatedate"]     = 0;
            $registre_data["domaineid"]      = $domaineid;
			$registre_data["parentid"]       = 0;
		   /*$domaines                        = $modelDomaine->getList(array("libelle"=> $descriptionRegistre ));
        
        if( count( $domaines )) {
			$registre_data["domaineid"]  = (isset($domaines[0]["domaineid"] )) ? intval($domaines[0]["domaineid"]) : $domaineid;
        }
		print_r($registre_data);die();*/
        if( $dbAdapter->insert( $prefixName ."rccm_registre", $registre_data)) {
			$toRemove                           = true;
			$registreid                         = $dbAdapter->lastInsertId();
			$representant_data                  = array();
			$representant_data["datenaissance"] = $dateNaissance;
			$representant_data["lieunaissance"] = $exploitantLieuNaissance;
			$representant_data["nom"]           = $exploitantLastname;
			$representant_data["prenom"]        = $exploitantFirstname;
			$representant_data["adresse"]       = $exploitantAdresse;
			$representant_data["city"]          = 0;
			$representant_data["country"]       = $exploitantNationalite;
			$representant_data["email"]         = $exploitantEmail;
			$representant_data["marital_status"]= $exploitantMaritalStatus;
			$representant_data["telephone"]     = $exploitantTelephone;
			$representant_data["passport"]      = $exploitantPassport;
			$representant_data["sexe"]          = $exploitantSexe;
			$representant_data["structure"]     = "";
			$representant_data["profession"]    = $exploitantFonction;
			$representant_data["creatorid"]     = $me->userid;
			$representant_data["creationdate"]  = time();
			$representant_data["updateduserid"] = 0;
			$representant_data["updatedate"]    = 0;
			if( $dbAdapter->insert( $prefixName ."rccm_registre_representants", $representant_data )) {
				$representantid                 = $dbAdapter->lastInsertId();
				if( $dbAdapter->insert( $prefixName ."rccm_registre_dirigeants", array("registreid"=> $registreid,"representantid"=> $representantid,"fonction"=> "GERANT"))) {											 
					$registres[]                = $registreid;
					$rccmFormulaireFilepath     = $fileSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-FR.pdf";
			        $rccmPersonnelFilepath      = $fileSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-PS.pdf";
					$rcPathroot                 = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS .$registreYear. DS. $numero;
					if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath ) && $registreid) {
						$newRccmFormulaireFilepath       = $rcPathroot. DS . $numero."-FR.pdf";
						$newRccmPersonnelFilepath        = $rcPathroot. DS . $numero."-PS.pdf";
						$modelDocument                   = $this->getModel("document");					  	  	       					  	  	  
					  	$documentData                    = array();
					  	$documentData["userid"]          = $me->userid;
					  	$documentData["category"]        = 1;
					  	$documentData["resource"]        = "registrephysique";
					  	$documentData["resourceid"]      = 0;
					  	$documentData["filedescription"] = $registre_data["description"];
					  	$documentData["filemetadata"]    = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	$documentData["creationdate"]    = time();
					  	$documentData["creatoruserid"]   = $me->userid;
						
						if(!is_dir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS .$registreYear) ) {
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
							}
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode)) {
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode, 0777 );
							}
							@mkdir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode. DS . $registreYear);
							@chmod(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode. DS . $registreYear, 0777 );									   
						}
						if(!is_dir($rcPathroot)) {
							@chmod($rcPathroot, 0777 );
							@mkdir($rcPathroot);
						}
						if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {													 
					  	  	$formulaireFileData                 = $documentData;
					  	  	$formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE",$numero);
					  	  	$formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
					  	  	$formulaireFileData["access"]       = 0 ;
					  	  	$formulaireFileData["filextension"] = "pdf";
					  	  	$formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
					  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0));
					  	  	} 
						} else {
							$errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré , Le formulaire n'a pas pu être copié", $numero);
						}
						if( TRUE==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	$personnelFileData                  = $documentData;
					  	  	$personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
					  	  	$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	$personnelFileData["access"]        = 6;
					  	  	$personnelFileData["filextension"]  = "pdf";
					  	  	$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
					  	  	if( $dbAdapter->insert( $prefixName  ."system_users_documents", $personnelFileData)) {
					  	  	  	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	$dbAdapter->insert( $prefixName  ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access"=>6));
					  	  	} 
						} else {
							$errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Le fond de dossier n'a pas pu etre copié", $numero );
						}
					}  else {
						    $errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Le fond de dossier et le formulaire sont manquants", $numero );
					}																		
				} else {
					        $errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Les informations du promoteur n'ont pas été enregistrées", $numero );
				}
			} else {
				            $errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Les informations du promoteur n'ont pas été enregistrées", $numero );
			}
		  } else {
			                $errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Aucune information n'a pu être enregistrée", $numero );
          }
		}	
		if( count( $errorMessages ) ) {
			if( intval($registreid) && ($toRemove==true)) {
				$dbAdapter->delete($prefixName."rccm_registre", "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			echo ZendX_JQuery::encodeJson(array("error" => implode(", ", $errorMessages )));
			exit;
		}
        if( intval( $registreid )) {
			$jsonData             = $registre_data;
			$jsonData["success"]  = sprintf("Le registre numéro %s a été enregistré avec succès", $numero );
			echo ZendX_JQuery::encodeJson($jsonData);
			exit;
		}
        echo ZendX_JQuery::encodeJson(array("error" => "Aucun résultat valide n'a été retourné"));
        exit;		
		
	}
	
	public function importAction()
	{
		$this->view->title          = "Importer des données à partir d'un fichier Excel";
		$modelLocalite              = $this->getModel("localite");
		$modelDocument              = $this->getModel("document");
		$model                      = $this->getModel("registrephysique");
		$modelRegistre              = $this->getModel("registre");
		$modelEntreprise            = $this->getModel("entreprise");
		$modelRepresentant          = $this->getModel("representant");
		$modelDomaine               = $this->getModel("domaine");
		$modelTable                 = $model->getTable();
		$prefixName                 = $modelTable->info("namePrefix");
		$dbDestination              = $dbAdapter = $modelTable->getAdapter();
		$me                         = Sirah_Fabric::getUser();
		
		$registreDefaultData        = $model->getEmptyData();
		$representantDefaultData    = $modelRepresentant->getEmptyData();
		$domaines                   = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" ,"libelle"), array() , 0 , null , false);
		$localites                  = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle"), array() , 0 , null , false);
		$localitesCodes             = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("code"      ,"localiteid"), array() , 0 , null , false);
		$countries                  = $this->view->countries();
		$localiteid                 = intval($this->_getParam("localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID));
		$domaineid                  = intval($this->_getParam("domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID ));
		$defaultData                = array_merge($representantDefaultData,$registreDefaultData);
		$errorMessages              = array();
		$defaultData["localiteid"]  = $localiteid;
		$defaultData["domaineid"]   = $domaineid ;
		$defaultData["find_documents_src"] = $fileSource = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		$registreid                 = 0;
		
		if( $this->_request->isPost() ) {
			$postData               = $this->_request->getPost();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
				
			$basicFilename         = $documentsUploadAdapter->getFileName("registres", false );
			$destinationName       = $me->getDatapath(). $basicFilename ;
			
			$modelTable             = $me->getTable();
			$dbAdapter             = $modelTable->getAdapter();
			$prefixName            = $modelTable->info("namePrefix");
			
			$documentsUploadAdapter->addFilter("Rename", array("target" => $destinationName, "overwrite"=> true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") ) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvAdapter    = Sirah_Filesystem_File::fabric("Csv",array("filename" => $destinationName, "has_header" =>1), "rb");
					$csvRows       = $csvAdapter->getLines();
					$i             = 1;
					//print_r($csvRows);die();
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $numeroRegistre          = $numero = (isset($csvRow["numero"]     ))? preg_replace("/\s/","", $stringFilter->filter(strtoupper($stringFilter->filter($csvRow["numero"])))): null;
								 $localiteRegistre        = (isset($csvRow["localite"]             ))? strtoupper($stringFilter->filter($csvRow["localite"])) : ((isset($postData["localite"])) ? $stringFilter->filter($postData["localite"]) : "");
								 $libelleRegistre         = (isset($csvRow["nom_commercial"]       ))? $stringFilter->filter($csvRow["nom_commercial"]) : "";
								 $dateRegistre            = (isset($csvRow["date_enregistrement"]  ))? $stringFilter->filter($csvRow["date_enregistrement"]) : date("d/m/Y");
								 $descriptionRegistre     = (isset($csvRow["description"]          ))? $csvRow["description"]: "";
								 $exploitantLastname      = (isset($csvRow["nom"]                  ))? $stringFilter->filter($csvRow["nom"]) : "";
								 $exploitantFirstname     = (isset($csvRow["prenom"]               ))? $stringFilter->filter($csvRow["prenom"]) : "";
								 $exploitantLieuNaissance = (isset($csvRow["lieu_naissance"]       ))? $stringFilter->filter($csvRow["lieu_naissance"])  : "";
								 $exploitantDateNaissance = (isset($csvRow["date_naissance"]       ))? $stringFilter->filter($csvRow["date_naissance"])  : "";
								 $exploitantSexe          = (isset($csvRow["sexe"]                 ))? strtoupper($stringFilter->filter($csvRow["sexe"])): "";
								 $exploitantAdresse       = (isset($csvRow["adresse"]              ))? $csvRow["adresse"]  : "";
								 $exploitantTelephone     = (isset($csvRow["telephone"]            ))? $stringFilter->filter($csvRow["telephone"]): "";
								 $exploitantPassport      = (isset($csvRow["passport"]             ))? $csvRow["passport"]: "";
								 $exploitantNationalite   = (isset($csvRow["nationalite"]          ))? strtoupper($stringFilter->filter($csvRow["nationalite"])): "";
								 $exploitantMaritalStatus = (isset($csvRow["situation_matrimonial"]))? $stringFilter->filter($csvRow["situation_matrimonial"]): "";
								 $exploitantEmail         = (isset($csvRow["email"]                ))? $stringFilter->filter($csvRow["email"]): " ";
								 $exploitantFonction      = (isset($csvRow["fonction"]             ))? $stringFilter->filter($csvRow["fonction"]): "GERANT";
								 $registre                = null;
								 
								 $localitesCodes2         = $localitesCodes;
								 $inscriptionDate         = 0;
								 $localiteCode            = (isset($postData["localite"] )) ? $stringFilter->filter($postData["localite"]) : "";
								 $dateYear                =  $registreYear = (isset($postData["date_year"])) ? intval($postData["date_year"]): null;
								 array_flip($localitesCodes2);
								 
								 if(!$strNotEmptyValidator->isValid($numero)) {
									 continue;
			                     } 
								 if( $toUpdateRegistre    = $model->findRow( $numero, "numero" , null , false )) {
				                     $registre            = $toUpdateRegistre;
								 } 
			                     if(!$strNotEmptyValidator->isValid($libelleRegistre)) {
				                    //$errorMessages[]    = sprintf(" Veuillez entrer un nom commercial valide pour ce registre de la ligne %d", $i);
									continue;
			                     } elseif( $model->findRow( $libelleRegistre , "libelle" , null , false )) {
				                    $errorMessages[]      = sprintf("Un registre existant porte le nom commercial %s , veuillez entrer un nom commercial différent à la ligne %d", $libelleRegistre, $i );
									continue;
			                     }  
			                     if(!$strNotEmptyValidator->isValid($exploitantLastname ) || !$strNotEmptyValidator->isValid($exploitantFirstname)) {
				                    $errorMessages[]      = sprintf(" Veuillez entrer un nom de famille et/ou prénom valide du representant à la ligne %d", $i);
			                        continue;
								 }  
			                     if( empty($localiteRegistre) ) {
				                     $localiteRegistre    = strtoupper(substr($numero,2,3));
			                     } 
								 if(!isset( $localitesCodes2[$localiteRegistre])) {
									 $errorMessages[]     = sprintf("Veuillez sélectionner une localité valide à la ligne %d", $i);
									 continue;
						         } else {
									 $localiteid          = intval($localitesCodes2[$localiteRegistre]);
									 $localiteCode        = $localiteRegistre;
								 }
						         if( $strNotEmptyValidator->isValid( $dateRegistre ) && Zend_Date::isDate($dateRegistre, "dd/MM/YYYY" ) ) {
							         $zendDate            = new Zend_Date($dateRegistre, "dd/MM/YYYY");
							         $inscriptionDate     = $zendDate->get(Zend_Date::TIMESTAMP);
						         } else {
							         $errorMessages[]     = sprintf("Veuillez indiquer une date d'inscription valide à la ligne %d", $i);
									 continue;
						         }
                                 if(!$inscriptionDate ) {
									 $errorMessages[]     = sprintf("Veuillez indiquer une date valide à la ligne %d", $i);
									 continue;
								 }									 
                                 $numeroPrefixToCheck     = sprintf("BF%s", $localiteRegistre);
                                 //$dateYear              = $registreYear = date("Y", $inscriptionDate);                                									 
                                 if(substr($numero,0,5)  != $numeroPrefixToCheck ) {
				                     $errorMessages[]     = sprintf("Le numéro attribué au registre à la ligne %d n'est pas valide.", $i);
									 continue;
			                     }								 
			                     if( strlen($numero ) != 14) {
				                     $errorMessages[]     = sprintf("Le numéro RC %s que vous avez indiqué à la ligne %d ne semble pas correct. Il doit comporter 14 caractères", $numeroRegistre, $i );
			                         continue;
								 }
								 if( !$dateYear ) {
								      $dateYear           = $registreYear = substr($numero,5, 4);
								 }
								 if(($exploitantSexe != "F") && ($exploitantSexe != "M") && ( $exploitantSexe != "H")) {
									 $errorMessages[]     = sprintf("Veuillez un sexe valide (entre F, M, et H) à la ligne %d", $i);
									 continue;
								 }
								 if( $strNotEmptyValidator->isValid($exploitantDateNaissance) && Zend_Date::isDate($exploitantDateNaissance, "dd/MM/YYYY" ) ) {
							         $zendDate            = new Zend_Date( $exploitantDateNaissance,Zend_Date::DATES , "fr_FR" );
							         $dateNaissance       = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
						         } else {
							         $errorMessages[]     = sprintf("Veuillez indiquer une date de naissance valide à la ligne %d", $i);
									 continue;
						         }
								 if(!$strNotEmptyValidator->isValid( $exploitantLieuNaissance )) {
									 $errorMessages[]     = sprintf("Veuillez indiquer un lieu de naissance valide à la ligne %d", $i);
									 continue;
								 }
								 if(!isset($countries[$exploitantNationalite] )) {
									 if( $countryFound            = Sirah_Functions_ArrayHelper::search($countries, $exploitantNationalite )) {
										 $exploitantNationalite   = key($countryFound); 
									 }
								 }
								 $registre_data                   = array();
								 $registre_data["numero"]         = $numero;
								 $registre_data["libelle"]        = $libelleRegistre;
								 $registre_data["localiteid"]     = $localiteid;
								 $registre_data["date"]           = $inscriptionDate;
								 $registre_data["type"]           = 1;
			                     $registre_data["statut"]         = 1;
			                     $registre_data["category"]       = "P0";
			                     $registre_data["checked"]        = 0;
			                     $registre_data["description"]    = $descriptionRegistre;
			                     $registre_data["creatorid"]      = $me->userid;
			                     $registre_data["creationdate"]   = time()+100;	
			                     $registre_data["updateduserid"]  = 0;
			                     $registre_data["updatedate"]     = 0;
								 $registre_data["domaineid"]      = $domaineid;
			                     if( $domaines = $modelDomaine->getList(array("libelle"=> $descriptionRegistre )) ) {
									 $registre_data["domaineid"]  = (isset($domaines[0]["domaineid"] )) ? intval($domaines[0]["domaineid"]) : $domaineid;
								 }
                                 if( $registre) {
									 $dbAdapter->delete($prefixName."rccm_registre"   , "registreid=".$registreid);
									 $dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				                     $dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");						
				                     $dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				                     $dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
								 }									 
                                 if( $dbAdapter->insert( $prefixName ."rccm_registre", $registre_data)) {
									 $registreid                         = $dbAdapter->lastInsertId();
									 $toRemove                           = true;
									 $representant_data                  = array();
									 $representant_data["datenaissance"] = $dateNaissance;
									 $representant_data["lieunaissance"] = $exploitantLieuNaissance;
									 $representant_data["nom"]           = $exploitantLastname;
					                 $representant_data["prenom"]        = $exploitantFirstname;
					                 $representant_data["adresse"]       = $exploitantAdresse;
					                 $representant_data["city"]          = 0;
					                 $representant_data["country"]       = $exploitantNationalite;
					                 $representant_data["email"]         = $exploitantEmail;
									 $representant_data["marital_status"]= $exploitantMaritalStatus;
					                 $representant_data["telephone"]     = $exploitantTelephone;
					                 $representant_data["passport"]      = $exploitantPassport;
					                 $representant_data["sexe"]          = $exploitantSexe;
					                 $representant_data["structure"]     = "";
									 $representant_data["profession"]    = $exploitantFonction;
					                 $representant_data["creatorid"]     = $me->userid;
					                 $representant_data["creationdate"]  = time();
					                 $representant_data["updateduserid"] = 0;
					                 $representant_data["updatedate"]    = 0;
									 if( $dbAdapter->insert( $prefixName ."rccm_registre_representants", $representant_data )) {
										 $representantid                 = $dbAdapter->lastInsertId();
										 if( $dbAdapter->insert( $prefixName ."rccm_registre_dirigeants", array("registreid"=> $registreid,"representantid"=> $representantid,"fonction"=> "GERANT"))) {											 
											 $registres[]                = $registreid;
											 $rccmFormulaireFilepath     = $fileSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-FR.pdf";
			                                 $rccmPersonnelFilepath      = $fileSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-PS.pdf";
										     $rcPathroot                 = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES". DS . $localiteCode. DS .$registreYear. DS. $numero;
											 //print_r($rccmFormulaireFilepath);print_r($rcPathroot);die();
											 if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath ) && $registreid) {
												 $newRccmFormulaireFilepath       = $rcPathroot. DS . $numero."-FR.pdf";
										         $newRccmPersonnelFilepath        = $rcPathroot. DS . $numero."-PS.pdf";
										         $modelDocument                   = $this->getModel("document");					  	  	       					  	  	  
					  	  	                     $documentData                    = array();
					  	  	                     $documentData["userid"]          = $me->userid;
					  	  	                     $documentData["category"]        = 1;
					  	  	                     $documentData["resource"]        = "registrephysique";
					  	  	                     $documentData["resourceid"]      = 0;
					  	  	                     $documentData["filedescription"] = $registre_data["description"];
					  	  	                     $documentData["filemetadata"]    = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	  	                     $documentData["creationdate"]    = time();
					  	  	                     $documentData["creatoruserid"]   = $me->userid;
												 												 												 
												 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode. DS .$registreYear) ) {
									                if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES")) {
										               @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										               @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES");
										               @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "PHYSIQUES", 0777 );
									                }
									                if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode)) {
										               @mkdir( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode);
										               @chmod( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode, 0777 );
									                }
									                   @mkdir( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode. DS . $registreYear);
									                   @chmod( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."PHYSIQUES". DS . $localiteCode. DS . $registreYear, 0777 );									   
								                 }
								                 if(!is_dir($rcPathroot)) {
									                @chmod($rcPathroot, 0777 );
									                @mkdir($rcPathroot);
								                 }
												 if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
													 
					  	  	  	   	                 $formulaireFileData                 = $documentData;
					  	  	  	   	                 $formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE",$numero);
					  	  	  	   	                 $formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
					  	  	  	   	                 $formulaireFileData["access"]       = 0 ;
					  	  	  	   	                 $formulaireFileData["filextension"] = "pdf";
					  	  	  	   	                 $formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
					  	  	  	   	                 if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	   	   	                 $documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	   	                 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0));
					  	  	  	   	                 } 
										         } else {
													 $errorMessages[]                    = sprintf("Le registre de la ligne %d n'a pas été enregistré , Le formulaire n'a pas pu être copié", $i);
												 }
												 if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	   	                 $personnelFileData                  = $documentData;
					  	  	  	   	                 $personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER", $numero);
					  	  	  	   	                 $personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	  	   	                 $personnelFileData["access"]        = 6;
					  	  	  	   	                 $personnelFileData["filextension"]  = "pdf";
					  	  	  	   	                 $personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
					  	  	  	   	                if( $dbAdapter->insert( $prefixName  ."system_users_documents", $personnelFileData)) {
					  	  	  	   	   	                $documentid                      = $dbAdapter->lastInsertId();
					  	  	  	   	   	                $dbAdapter->insert( $prefixName  ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
					  	  	  	   	                } 
										        } else {
													    $errorMessages[]                 = sprintf("Le registre de la ligne %d n'a pas été enregistré , Le fond de dossier n'a pas pu etre copié", $i);
												}
											 }
										 }
									 } else {
										                $errorMessages[]                 = sprintf("Le registre de la ligne %d n'a pas été enregistré , Les informations du dirigeant n'ont pas été enregistrées", $i);
									 }
								 } else {
									                    $errorMessages[]                 = sprintf("Le registre de la ligne %d n'a pas été enregistré , Les informations de base n'ont pas été enregistrées", $i);
								 }									 
								 $i++;
						}
					}
				}
			}
			if(!count( $registres) && empty( $errorMessages )) {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("error" => "Aucun dossier n'a pu être importé" ));
				    exit;
			    }
				$this->setRedirect("Aucun dossier n'a pu être importé","error");
				$this->redirect("admin/registrephysique/list");
			}
			if( count($registres ) && empty( $errorMessages )) {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" =>  sprintf("Vous avez importé avec succès %d RC dans la base données.", count($registres) )));
				    exit;
			    }
				$this->setRedirect(sprintf("Vous avez importé avec succès %d RC dans la base données.", count($registres)),"success");
				$this->redirect("admin/registrephysique/list");
			}
		}
		if( count( $errorMessages ) ) {
			if( intval($registreid) && ($toRemove==true)) {
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}		
		$this->view->data      = $defaultData;
		$this->view->domaines  = $domaines;
		$this->view->localites = $localites;
	}
	
	
	public function exportfromdbAction()
	{
		$this->view->title     = "Produire un fichier CSV SIGARD";
		$modelLocalite         = $this->getModel("localite");
		$model                 = $this->getModel("registrephysique");
		$modelRegistre         = $this->getModel("registre");
		$modelTable            = $model->getTable();
		$prefixName            = $tablePrefix = $modelTable->info("namePrefix");
		$dbAdapter             = $dbAdapter   = $modelTable->getAdapter();
		$me                    = Sirah_Fabric::getUser();
		$registres             = array();
		
		$dbsourceParams        = array(
				                       "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
				                       "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
				                       "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "mebfSir@h1217" ),
				                       "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "sigar" ),
				                       "isDefaultAdapter" => 0);
		try{
			$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
			$dbSource->getConnection();
		} catch( Zend_Db_Adapter_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		} catch( Zend_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		}
		$localites           = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$annees              = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=> "2003", "2004"=>"2004","2005"=>"2005",
		                             "2006"=>"2006","2007"=>"2007","2008"=> "2008", "2009"=> "2009","2010"=>"2010","2011"=>"2011",
				                     "2012"=>"2012","2013"=> "2013", "2014"=> "2014", "2015"=>"2015","2016"=> "2016","2017"=> "2017");
		$defaultData         = array("srcpath" =>"C:\\ERCCM/DATA","destpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS", "localite"=> "OUA", "annee"=> DEFAULT_YEAR,"nbre_start" => 1, "nbre_maximal" => "200");							 
		if( $this->_request->isPost() ) {
			(PHP_VERSION_ID < 50600) ? iconv_set_encoding('internal_encoding', 'UTF-8') : ini_set('default_charset', 'UTF-8');
			$postData        = $this->_request->getPost();
			$stringFilter    = new Zend_Filter();
			//$stringFilter->addFilter(new Zend_Filter_Callback("utf8_decode"));
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$annee                = (isset( $postData["annee"]         )) ? intval( $postData["annee"] )                         : date("Y");
			$localite             = (isset( $postData["localite"]      )) ? $stringFilter->filter( $postData["localite"])        : "OUA";
			$srcPath              = (isset( $postData["srcpath"]       )) ? $stringFilter->filter( $postData["srcpath"] )        : "C:\\ERCCM/DATA";
			$destPath             = (isset( $postData["destpath"]      )) ? $stringFilter->filter( $postData["destpath"] )       : "C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS";
			$nbreStart            = (isset( $postData["nbre_start"]    )) ? intval( $postData["nbre_start"]  )                   : 1;
			$nbreMaximal          = (isset( $postData["nbre_maximal"]  )) ? intval( $postData["nbre_maximal"])                   : 100;
			$searchRccmKey        = (intval($annee) && !empty($localite)) ? strtoupper( sprintf("BF%s%dA", $localite , $annee )) : "";
			$countries            = array("BF"=>"Burkina Faso");
			$months               = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
			
			if(!empty( $searchRccmKey )) {
				$rccmFilesDirectory   = sprintf($srcPath."/%s/%d", $localite,$annee );
				$rccmKey              = sprintf("BF%s%dA", $localite,$annee );
				$searchFiles          = $rccmFilesDirectory."/*/*-PS.pdf";
				$files                = glob($searchFiles);
				$i                    = 0;
				/*$files              = array_combine(array_map("filemtime", $files), $files);
                ksort($files);
				$files                = array_filter($files,function($value) use (&$searchRccmKey,&$nbreStart,&$nbreMaximal){$numRccm=str_ireplace(array(".pdf","-PS","-FR"),"",trim(basename($value)));$integerVal=intval(str_ireplace($searchRccmKey,"",$numRccm));return (($integerVal >= $nbreStart) && ($integerVal <= $nbreMaximal));});*/
				//usort($myarray, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));
				//print_r();
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
				if( count(   $files )) {
					$logger       = new Logger('MyLogger');
                    $pdfToText    = XPDF\PdfToText::create(array('pdftotext.binaries'=> 'E:\webserver\www\erccm\binaries\Xpdf\pdftotext.exe','pdftotext.timeout'=> 30,),$logger);
					
					foreach( $files as $rccmFile ) {
						     if( $i > $nbreMaximal ) {
								 break;
							 }
						     $numRccm      = $numero = strtoupper(str_ireplace(array(".pdf","-PS","-FR"),"",trim(basename( $rccmFile))));
                             /*$selectNumero = $dbAdapter->select()->from($tablePrefix."rccm_registre_indexation")->where("numero=?", $numero );
                             $foundRccm    = $dbAdapter->fetchAll($selectNumero, array(),Zend_Db::FETCH_ASSOC ); 
							 if( isset( $foundRccm[0]["numero"] ) ) {
								 continue;
							 }*/
							 if( stripos($numero, $rccmKey)===false) {
								 continue;
							 }
							 $checkRccmRow = $modelRegistre->findRow($numero, "numero", null, false );
							 if( $checkRccmRow ) {
							     continue;
						     }
							 $checkIndexationFiles         = glob("C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS/*/".$localite."/".$annee."/".$numero.".pdf" );
							 if( count($checkIndexationFiles) ) {
								 continue;
							 }
							 if( file_exists($documentDestRootPath . DS . $numero.".pdf") ) {
								 continue;
							 }
							 if( file_exists( $rccmFile )) {
								 try{
							          $rccmContent  = preg_replace(array("/[«»]/"),array(""),$pdfToText->getText($rccmFile,1,14));
									  $rccmContent  = preg_replace(array("/(?<=:)(\s*)/","/(\s*)(?=:)/"),"", $rccmContent);
                                      								  
							     } catch( Exception $e) {
								      $rccmContent  = "";
							     }
							 }								 
							 $numRegistre=$nomCommercial=$telephone= $dirigeant=$lastname=$firstname=$birthday=$birthadress=$nationalite=$telToReplace=$exploitant=$adresse=$passport ="" ;
							 $sexe       = "M";
							 if(!empty( $rccmContent )) {                                						 
								  preg_match('/(?<=COMMERCIAL:|COMMERCIAL)(.*)\s*(?=ACTIVITE PRINCIPALE)(.*)/i', $rccmContent, $nomCommercialMatches1);
								  preg_match('/(?<=COMMERCIAL|COMMCRCIAL)(?:.*?):(.*)\s*(?=SIGLE)(.*)/i', $rccmContent, $nomCommercialMatches2);
					              preg_match('/(?<=NOM:)(.*)(?:PRENOM\(S\):)(.*)/i', $rccmContent, $exploitantMatches1);
								  preg_match('/(?<=Nom:)(.*)(?:Prénoms:)([^Adresse|\s]*)/i', $rccmContent, $exploitantMatches2);
								  preg_match('/(?<=NAISSANCE:)(?:\s*)(.*)(?:,)(.*)(?:NATIONALITE.*:)(.*)/i', $rccmContent, $birthInfosMatches1);
								  preg_match('/(?<=NAISSANCE:)(?:\s*)(.*)(?: à | a )(.*)(?:NATIONALITE.*\s*n*\s*:)(.*)/i' , $rccmContent, $birthInfosMatches2);
								  //preg_match('/(?<= postale|postal|postat|poslal\):)(.*)(?:Tel|Tél|Té)/i', $rccmContent , $adressMatches1);
								  preg_match('/(?<=POSTALE:)(.*)(?=Tel.:)/i', $rccmContent , $adressMatches1);
								  preg_match('/(?<=DOMICILE \(réel|reel|rcel et postal|réel et postal|postat\))(.*)/i', $rccmContent , $adressMatches2);
								  preg_match('/(?<=ACTIVITE PRINCIPALE)(?:.*):(.*)/i', $rccmContent, $descriptionMatches1);								  
								  preg_match('/(?<=EXERCEE)(?:.*?):(.*)/i' , $rccmContent, $descriptionMatches2);
								  preg_match('/(?<=Principale:)(.*)/i' , $rccmContent, $descriptionMatches3);
								  //preg_match('/(?<=Le)(?:\s*)(.*)(?:\s*)(?=Signature)/i'  , $rccmContent, $dateInscriptionMatches1);
								  preg_match('/(?<=Le)(?:\s*)(.*)(?:\s*)(?=\(JJ\/MM\/AAAA\))/i'  , $rccmContent, $dateInscriptionMatches1);
								  preg_match('/(?<=inscription le)\s*(.+)(?:sous le:)/i', $rccmContent, $dateInscriptionMatches2);
								  preg_match('/(?<=Date de début:)(.+)/i', $rccmContent, $dateInscriptionMatches3);
								  preg_match('/(?<=MATRIMONIALE:)(.*)/i'  , $rccmContent, $situationMatrimonialeMatches);
								  preg_match('/(?<=Tél:|Tel:|Té:|Te:|Tel.:)(.*)\s*(?:Origine)/i', $rccmContent, $phoneMatches1);
								  preg_match('/(?<=BURKINABE No)(.*)\s*(?:NOM)/i', $rccmContent, $passportMatches);
								  preg_match('/(?<=NATIONALITE:)(.*)/i'  , $rccmContent, $nationaliteMatches);
								  
								  //print_r($descriptionMatches2);print_r($rccmContent);die();
								  /* if($numRccm == "BFKDG2013A0002")	{							 
                                       print_r($exploitantMatches2);print_r($rccmContent);die();}	*/
								   
								  if( isset($nationaliteMatches[1]) ) {
									  $nationalite = substr(trim(preg_replace("/[^ \w]+/", "", strtoupper($nationaliteMatches[1]))), 0, 6 );
								  }
								  //On récupère le nom de famille
								  if( isset($exploitantMatches1[1]) ) {
									  $lastname  = trim(preg_replace("/[^ \w]+/", "", strtoupper($exploitantMatches1[1])));
								  } elseif( isset($exploitantMatches2[1])) {
									  $lastname  = trim(preg_replace("/[^ \w]+/", "", strtoupper($exploitantMatches2[1])));
							      } else {
									  $lastname  = "";
								  }
								  if(!empty($lastname) && ((stripos($lastname,"Mme") !== false) || (stripos($lastname,"Mlle") !== false ) || (stripos($lastname,"Mle") !== false ))) {
									  $sexe      = "F";
								  }
								  //On récupère le prénom
								  if( isset($exploitantMatches1[2]) ) {
									  $cleanTxt  = str_replace(array("Prénoms","Prenoms"), "", $exploitantMatches1[2]);
									  $firstname = trim(preg_replace("/[^ \w]+/", "", strtoupper( $cleanTxt )));
								  }  elseif( isset($exploitantMatches2[2])) {
									  $cleanTxt  = str_replace(array("Prénoms","Prenoms"), "", $exploitantMatches2[2]);
									  $firstname = trim(preg_replace("/[^ \w]+/", "", strtoupper( $cleanTxt )));
							      } else {
									  $firstname = "";
								  }
								  //On récupère le nom commercial
								  if( isset($nomCommercialMatches1[1]) ) {
									  $nomCommercial = trim(preg_replace("/[^ \w]+/", "", strtoupper($nomCommercialMatches1[1])));
								  } elseif( isset($nomCommercialMatches2[1]) ) {
									  $nomCommercial = trim(preg_replace("/[^ \w]+/", "", strtoupper($nomCommercialMatches2[1])));
							      } else {
									  $nomCommercial = "";
								  }
								  //On récupère l'adresse du dirigeant
								  if( isset($adressMatches1[1]) ) {
									  $adresse = trim(preg_replace("/[^ \w]+/", "", strtoupper($adressMatches1[1])));
								  }elseif( isset($adressMatches2[1])) {
									  $adresse = trim(preg_replace("/[^ \w]+/", "", strtoupper($adressMatches2[1])));
								  } else {
									  $adresse = "";
								  }
								   //On récupère la date de naissance
								  if( isset($birthInfosMatches2[1]) ) {
									  //$birthdayTxt = preg_replace("/[^0-9]/", "", $birthInfosMatches1[1]);
									  //$birthday    = substr(0,2,$birthdayTxt)."/".substr(3,2,$birthdayTxt)."/".substr(-1,4,$birthdayTxt);
									  $birthday      = $birthInfosMatches2[1];
								  } elseif( isset($birthInfosMatches1[1])) {
									  //$birthdayTxt = preg_replace("/[^0-9]/", "", $birthInfosMatches2[1]);
									  //$birthday    = substr(0,2,$birthdayTxt)."/".substr(3,2,$birthdayTxt)."/".substr(-1,4,$birthdayTxt);
									  $birthday      = $birthInfosMatches1[1];
								  }
								  //On récupère le lieu de naissance
								  if( isset($birthInfosMatches1[2])) {
									  $birthadress = preg_replace("/[^ \w]+/", "", strtoupper($birthInfosMatches1[2]));
								  } elseif( isset($birthInfosMatches2[2]) ) {
									  $birthadress = preg_replace("/[^ \w]+/", "", strtoupper($birthInfosMatches2[2]));
								  }
								  //On récupère la nationalité
								  if( !empty( $nationalite) ) {
									  if( $found         = Sirah_Functions_ArrayHelper::search($countries, $nationalite)) {
										  $nationalite   = key($found);
									  } else {
										  $nationalite   = "BF";
									  }
								  } elseif(isset($birthInfosMatches1[3]) && !empty($birthInfosMatches1[3])) {
									  $nationaliteSearch = substr($birthInfosMatches1[3],0,4);
									  if( $found         = Sirah_Functions_ArrayHelper::search($countries, $nationaliteSearch)) {
										  $nationalite   = key($found);
									  } else {
										  $nationalite   = "BF";
									  }
								  } elseif(isset($birthInfosMatches2[3]) && !empty($birthInfosMatches2[3])) {
									  $nationaliteSearch = substr($birthInfosMatches2[3],0, 4);
									  if( $found         = Sirah_Functions_ArrayHelper::search($countries, $nationaliteSearch)) {
										  $nationalite   = key($found);
									  }	else {
										  $nationalite   = "BF";
									  }										  
								  } else {
									  $nationalite       = "BF";
								  }
								  if( isset( $passportMatches[1])) {
									  $passport          = $passportMatches[1];
								  }
								  //On récupère la date d'inscription
								  if(isset($descriptionMatches1[1])) {
									  $description = preg_replace("/[^ \w]+/", "", strtoupper($descriptionMatches1[1]));
								  } elseif(isset($descriptionMatches2[1])) {
									  $description = preg_replace("/[^ \w]+/", "", strtoupper($descriptionMatches2[1]));
								  } elseif(isset($descriptionMatches3[1])) {
									  $description = preg_replace("/[^ \w]+/", "", strtoupper($descriptionMatches3[1]));
								  }
								  //On récupère la date d'inscription
								  if(isset( $dateInscriptionMatches1[1] ) ) {
									 //$dateInscriptionTxt = preg_replace("/[^0-9]/", "", $dateInscriptionMatches1[1]);
									 //$dateInscription    = substr(0,2,$dateInscriptionTxt)."/".substr(3,2,$dateInscriptionTxt)."/".substr(-1,4,$dateInscriptionTxt);
								       $dateInscription    = preg_replace("/[^0-9a-zA-Z\s\/]+/","",$dateInscriptionMatches1[1]);									   
								  } elseif( isset( $dateInscriptionMatches2[1] ) ) {
									 //$dateInscriptionTxt = preg_replace("/[^0-9]/", "", $dateInscriptionMatches2[1]);
									 //$dateInscription    = substr(0,2,$dateInscriptionTxt)."/".substr(3,2,$dateInscriptionTxt)."/".substr(-1,4,$dateInscriptionTxt);
								       $dateInscription    = preg_replace("/[^0-9a-zA-Z\s\/]+/","",$dateInscriptionMatches2[1]);
								  }	elseif( isset( $dateInscriptionMatches3[1])	) {
									   $dateInscription    = preg_replace("/[^0-9a-zA-Z\s\/]+/","",$dateInscriptionMatches3[1]);
								  }									  
								  //On récupère le numéro de téléphone
								  if( isset($phoneMatches1[1] )) {
									  $telephone   = preg_replace("/[^ \w]+/", "", strtoupper($phoneMatches1[1]));
								  }
								  if(!empty($birthday) && (stripos($birthday,"en") !== false )) {
									  $birthDayYear= sprintf("%04d", preg_replace("/[^0-9]/","", $birthday));
									  $birthday    = "01/01/".$birthDayYear;
								  }
                                  preg_match("/([a-zA-Z]+)/",$dateInscription, $dateInscriptionMonthArray);	
								 /* if( $numRccm =="BFOUA2010A0343") {
                                  print_r($dateInscription);die();	}	*/						  
								  if(!empty($dateInscription) && isset($dateInscriptionMonthArray[1])) {
									  
									  $dateDay     = substr(preg_replace("/[\s,_]+/","",$dateInscription), 0, 2);
									  $dateMonth   = "01";
									  $dateYear    = $annee;
									  $monthStr    = preg_replace("/[^a-zA-Z]+/","", $dateInscription);
									  $yearStr     = preg_replace("/[^0-9]+/",""   , $dateInscription);
									  if(!empty( $monthStr )) {
										  if($found     = Sirah_Functions_ArrayHelper::search($months, $monthStr)) {
										     $dateMonth = sprintf("%02d",key($found));
									      }
										  if(stripos($monthStr,"vrier")) {
											  $dateMonth= "02";
										  } elseif(stripos($monthStr,"out")) {
											  $dateMonth= "08";
										  } elseif(stripos($monthStr,"cembre")) {
											  $dateMonth= "12";
										  }
									  }
									  if(!empty( $yearStr )) {
										  $dateYear     = intval(substr($yearStr,2,4));
									  }
									  if(strlen($dateYear) < 4 ) {
										  $dateYear     = $annee;
									  }
									  $dateInscription  = sprintf("%02d/%02d/%04d", $dateDay, $dateMonth, $dateYear);
								  }
							 } 
							 /*print_r(array("lastname" => $lastname,"firstname" => $firstname,"nom_commercial" => $nomCommercial,"birthadress" => $birthadress,"numero"=>$numero,
							                 "adresse"  => $adresse, "dateInscription" => $dateInscription,"birthday" =>  $birthday, "description" => $description));die();*/
								 $searchNum      = $searchRccmKey.intval(substr($numero,10,14));
				                 $searchNum2     = $searchRccmKey.sprintf("%02d",intval(substr($numero,10,14)));
				                 $searchNum3     = $searchRccmKey.sprintf("%03d",intval(substr($numero,10,14)));
								 $dbSourceSelect = $dbSource->select()->from(array("A" => "archive"), array("A.analyse","A.date_enregistrement","A.date_deb","A.id_archive"))
					                                                  ->join(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive","F.nomged_fichier"))
		                                                              ->where("F.nom_fichier LIKE ?", "%".$searchNum.".pdf")->orWhere("F.nom_fichier LIKE ?", "%".$searchNum2.".pdf")
																	  ->orWhere("F.nom_fichier LIKE ?", "%".$searchNum3.".pdf")->orWhere("F.nom_fichier LIKE ?", "%".$numero.".pdf");
				                 $registres      = $dbSource->fetchAll( $dbSourceSelect );
								 if( count( $registres )) {
									 $registre   = $registres[0];
					                 $analyse    = $registreStr   = $cleanTxt  = trim( $registre["analyse"]) ;
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
					                 if( isset( $telephoneMatches["telephone"]) && empty( $telephone )) {
						                 $telephone = $telToReplace = trim($telephoneMatches["telephone"]);
					                 }
					                 if( isset( $nomCommercialMatches["nomcommercial"]) && empty($nomCommercial)) {
						                 $nomCommercial = trim(str_replace(array("TELEPHONE",":", $telToReplace), "", $nomCommercialMatches["nomcommercial"])) ;
					                 } elseif( isset( $nomComMatches["nomcommercial"]) && empty($nomCommercial)) {
						                 $nomCommercial = trim(str_replace(array("TELEPHONE",":", $telToReplace), "", $nomComMatches["nomcommercial"])) ;
					                 } 
					                 if(!empty( $dirigeant )) {
						                $dirigeantToArray    = preg_split("/[\s]+/", $dirigeant );
						                if( $dirigeantToArray[0] ) {
							                $lastname        = (empty( $lastname )) ? $dirigeantToArray[0] : $lastname;
							                unset($dirigeantToArray[0]);
						                }
						                    $firstname       = (empty( $firstname)) ?  implode(" ", $dirigeantToArray ) : $firstname;
					                 } elseif(isset($exploitantMatches["dirigeant"])) {
						               $dirigeantToArray     = preg_split("/[\s]+/", $exploitantMatches["dirigeant"]);
						               if( $dirigeantToArray[0] ) {
							               $lastname         = (empty( $lastname )) ? $dirigeantToArray[0] : $lastname;
							               unset($dirigeantToArray[0]);
						               }
								           $firstname        = (empty( $firstname)) ? implode(" ", $dirigeantToArray ) : $firstname;
					                 } elseif( $exploitantMatches2["dirigeant"] && empty($lastname) && empty($firstname )) {
							               $dirigeantToArray = preg_split("/[\s]+/", $exploitantMatches2["dirigeant"]);
						                if( $dirigeantToArray[0] ) {
							                $lastname        = (empty( $lastname )) ? $dirigeantToArray[0] : $lastname;
							                unset($dirigeantToArray[0]);
						                }
							                $firstname       = (empty( $firstname)) ? implode(" ", $dirigeantToArray ) : $firstname;
					                 }									  						 							 							 							 							          								 
								 }								 

							 $csvRows[$i]                            = $indexation_data                          = array();
							 $csvRows[$i]["numparent"]               = $indexation_data["numparent"]             = "";
							 $csvRows[$i]["numero"]                  = $indexation_data["numero"]                = $registres[] = strtoupper($numero);
							 $csvRows[$i]["nom_commercial"]          = $indexation_data["nom_commercial"]        = Encoding::toUTF8($nomCommercial);
							 $csvRows[$i]["date_enregistrement"]     = $indexation_data["date_enregistrement"]   = (null !== $dateInscription ) ? $dateInscription : " ";//(count($dateToArray)) ? implode("/", $dateToArray): $registre["date_enregistrement"];
							 $csvRows[$i]["description"]             = $indexation_data["description"]           = Encoding::fixUTF8(preg_replace("/Â/","",$description));
							 $csvRows[$i]["nom"]                     = $indexation_data["nom"]                   = Encoding::fixUTF8(preg_replace("/Â/","",$lastname));
							 $csvRows[$i]["prenom"]                  = $indexation_data["prenom"]                = Encoding::fixUTF8(preg_replace("/Â/","",$firstname));
							 $csvRows[$i]["date_naissance"]          = $indexation_data["date_naissance"]        = $birthday;
							 $csvRows[$i]["lieu_naissance"]          = $indexation_data["lieu_naissance"]        = Encoding::fixUTF8(preg_replace("/Â/","",$birthadress));							
							 $csvRows[$i]["sexe"]                    = $indexation_data["sexe"]                  = $sexe;
							 $csvRows[$i]["adresse"]                 = $indexation_data["adresse"]               = Encoding::fixUTF8(preg_replace("/code|postal|postat/i","",$adresse));
							 $csvRows[$i]["telephone"]               = $indexation_data["telephone"]             = Encoding::fixUTF8(preg_replace("/Â/","",$telephone));
							 $csvRows[$i]["passport"]                = $indexation_data["passport"]              = $passport;
							 $csvRows[$i]["nationalite"]             = $indexation_data["nationalite"]           = $nationalite;
							 $csvRows[$i]["situation_matrimonial"]   = $indexation_data["situation_matrimonial"] = "Celibataire";	
							 $i++;	
							    $dbAdapter->delete($prefixName ."rccm_registre_indexation", "numero='".$indexation_data["numero"]."'");
							 if( $dbAdapter->insert($prefixName ."rccm_registre_indexation", $indexation_data )) {
								 $documentFilename                   = $documentDestRootPath . DS . $numRccm.".pdf"; 
								 if( true == copy( $rccmFile, $documentFilename ) ) {
									 $csvRows[$numRccm]              = $csvRowData;
									 $i++;							 
								 }
							 }
					}
                } else {
					$errorMessages[]  = sprintf("Aucun dossier valide n'a été retrouvé dans %s", $rccmFilesDirectory );
				}					
				//print_r($csvRows);die();
				if( count( $csvRows )) {
					$csvHeader = array("numero","nom_commercial","date_enregistrement","description","nom","prenom","lieu_naissance","date_naissance","sexe","adresse","telephone","passport","nationalite","situation_matrimonial");
					$csvTmpFile = APPLICATION_DATA_PATH . DS .  "tmp" . DS . "rccmImport.csv";	
                    $csvAdapter = Sirah_Filesystem_File::fabric("Csv", array("filename" => $csvTmpFile,"has_header" => true, "header" => $csvHeader ) , "wb+" );
		            if( $csvAdapter->save( $csvRows ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
		                $this->_helper->layout->disableLayout(true);
			            //$this->getResponse()->setHeader("Content-Type" , "text/csv");
			            echo $csvAdapter->Output("rccmExcel.csv");
			            @unlink( $csvTmpFile );
						exit;
		            } else {
			          $errorMessages[]  = " Aucun RCCM n'a pu être exporté ";
		            }
				}
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
        $this->view->annees    = $annees;
		$this->view->data      = $defaultData;
		$this->view->localites = $localites;
        $this->render("export");		
	}
				 
	
	protected function __checkRccmFiles($rccmFilesInfos = array(), &$errorMessages)
	{
		$result    = true;
		
		if(!isset($rccmFilesInfos["formulaire"]) || !isset($rccmFilesInfos["personnel"])) {
			return false;
		}
		if(!file_exists($rccmFilesInfos["formulaire"]) || !file_exists($rccmFilesInfos["personnel"])) {
			return false;
		}
		$formulaireFilePath        = $rccmFilesInfos["formulaire"];
		$completFilePath           = $rccmFilesInfos["personnel"];
		try{
			 $pdfRegistre          = new FPDI();
			 $pagesFormulaire      = (file_exists($formulaireFilePath)) ? $pdfRegistre->setSourceFile( $formulaireFilePath ) : 0;
			 $pagesComplet         = (file_exists($completFilePath   )) ? $pdfRegistre->setSourceFile( $completFilePath    ) : 0;
		} catch(Exception $e ) {
			$errorMessages[]       = sprintf("Le fichier %s ou %s est invalide", $completFilePath, $formulaireFilePath);
			$result                = false;
		}
		if( $pagesFormulaire && ( $pagesComplet <= $pagesFormulaire )) {
			$errorMessages[]       = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages",$numRccm);
		    $result                = false;
		}
		if( file_exists( $formulaireFilePath)) {
			try{
				$logger                = new Logger('MyLogger');
				$pdfToText             = XPDF\PdfToText::create(array('pdftotext.binaries'=> 'E:\webserver\www\erccm\binaries\Xpdf\pdftotext.exe','pdftotext.timeout'=> 30,),$logger);
				$formulaireContent     = $pdfToText->getText( $formulaireFilePath );
				if( !empty( $formulaireContent )) {
					$findAnormalChar   = ((stripos($formulaireContent," casier")!==false)    || (stripos($formulaireContent," bail")!==false) || (stripos($formulaireContent," CARTE D'IDENTITE")!==false) ||
										  (stripos($formulaireContent," judiciaire")!==false)|| (stripos($formulaireContent," CNIB")!==false) || (stripos($formulaireContent," RESIDENCE")!==false) || 
										  (stripos($formulaireContent," mairie") !==false)|| (stripos($formulaireContent," contrat")!==false) || (stripos($formulaireContent," passport")!==false)  ||
										  (stripos($formulaireContent," procuration") !==false) );
					if( $findAnormalChar ) {
						$errorMessages[] = sprintf("Le formulaire du RCCM n° %s n'a pas été bien traité", $numRccm);
						$result          = false;
					}
				}
			} catch(Exception $e) {
				$result          = true;
			}			
		}
		
		return $result;
	}
	
	 
}