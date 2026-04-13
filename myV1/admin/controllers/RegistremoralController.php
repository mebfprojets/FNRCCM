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
class Admin_RegistremoralController extends Sirah_Controller_Default
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
			$selectRegistres          = $dbSourceAdapter->select()->from( array("R" => $tablePrefix."rccm_registre" ))
		                                                          ->join( array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid"         , array("RP.representantid"))
																  ->join( array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.cnib","RE.passport","RE.sexe","RE.telephone","RE.profession"))											  
																  ->join( array("E" => $tablePrefix."rccm_registre_entreprises")  ,"E.registreid=R.registreid"          , array("E.entrepriseid","E.responsable","E.responsableid","E.formid","E.responsable_email","E.num_securite_social","E.num_ifu","E.num_rc","E.reference","E.chiffre_affaire","E.groupid","E.address","E.email","E.phone1","E.phone2","E.siteweb","E.country","E.city","E.zip","E.nbemployes_min","E.nbemployes_max","E.datecreation","E.presentation","E.region"))
																  ->where("R.type=2")
																  ->order(array("R.annee DESC","R.registreid DESC"));				
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
			$emptyModificationData        = $modelModification->getEmptyData();
			
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
									if( isset($sourceRegistre["formid"]) && isset($sourceRegistre["entrepriseid"]) && intval($sourceRegistre["formid"])) {										
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
		$this->view->title  = "ERCCM : Historique des RCCM de type Personnes Morales"  ;
		
		$model              = $this->getModel("registremorale");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = $errorMessages   = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          =  new Zend_Filter();
		$stringFilter->addFilter( new Zend_Filter_StringTrim());
		$stringFilter->addFilter( new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]      ))? intval($params["page"])    : 1;
		$pageSize             = (isset($params["maxitems"]  ))? intval($params["maxitems"]): NB_ELEMENTS_PAGE;		
		
		$filters              = array("libelle"=> null,"numero"=>null,"numcnss"=>null,"numifu"=>null,"domaineid"=>0,"localiteid"=>0,"annee"=>null,"denomination"=>null,"nom"=>null,"prenom"=>null,"searchQ"=>null,
		                              "date_year"=>0,"date_month"=>0,"date_day"=>0,"periode_start_year"=>DEFAULT_START_YEAR,"country"=>null,"sexe"=> null,"name"=>null,
				                      "periode_end_year"=> DEFAULT_END_YEAR,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_day"=>DEFAULT_START_DAY,
				                      "periode_end_day" => DEFAULT_END_DAY ,"periode_end_month"  =>DEFAULT_END_MONTH  ,"passport"=>null,"telephone"=>null);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? urldecode($params["searchq"])  : "";
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
        if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval( $filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"]  ) && intval( $filters["periode_end_day"]  )) && (isset($filters["periode_start_day"])   && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year" => $filters["periode_start_year"] ,"month"=> $filters["periode_start_month"],"day"=> $filters["periode_start_day"]  ));
			$zendPeriodeEnd           = new Zend_Date(array("year" => $filters["periode_end_year"]   ,"month"=> $filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]    ));
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
		$this->view->columns       = array("left");
		$this->view->registres     = $registres;
		$this->view->domaines      = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites     = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users         = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->statuts       = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters       = $filters;
		$this->view->params        = $params;
		$this->view->paginator     = $paginator;
		$this->view->pageNum       = $pageNum;
		$this->view->pageSize      = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title                   = "Enregistrer/Archiver un RCCM de type `Personne Morale`";
		
		$model                               = $this->getModel("registre");
		$modelMoral                          = $this->getModel("registremorale");
		$modelRepresentant                   = $this->getModel("representant");
		$modelEntreprise                     = $this->getModel("entreprise");
		$modelDomaine                        = $this->getModel("domaine");
		$modelLocalite                       = $this->getModel("localite");
		$modelDocument                       = $this->getModel("document");
		$modelEntrepriseforme                = $this->getModel("entrepriseforme");
		$modelCity                           = $this->getModel("countrycity");
		$me                                  = Sirah_Fabric::getUser();
		
		$newCreation                         = intval($this->_getParam("new_creation" , 0   ));
		$registreDefaultData                 = $model->getEmptyData();
		$entrepriseDefaultData               = $modelEntreprise->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité" , array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);
		
		$defaultData                         = array_merge( $entrepriseDefaultData , $registreDefaultData );
		$defaultData["domaineid"]            = intval($this->_getParam("domaineid" , $me->getParam("default_domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID)));
		$defaultData["localiteid"]           = intval($this->_getParam("localiteid", $me->getParam("default_localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID)));		
		$defaultData["date_year"]            = intval($this->_getParam("annee"     , $me->getParam("default_year"      , DEFAULT_YEAR)));
		$defaultData["country"]              = "BF";
		$defaultData["date_month"]           = sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));
		$defaultData["date_day"]             = null;
		$defaultData["country"]              = "BF";
		$defaultData["formid"]               = intval($this->_getParam("formid", 5));
		$defaultData["check_documents"]      = 0;
		$defaultData["find_documents"]       = 0;
		$defaultData["find_documents_src"]   = $fileSource = ( is_dir(DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
		$errorMessages                       = array();
		$registreid                          = 0;
		$dirigeantid                         = 0;
		$representantid                      = 0;
		
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$registre_data                   = array_merge($registreDefaultData  , array_intersect_key($postData,  $registreDefaultData   ));
			$entreprise_data                 = array_merge($entrepriseDefaultData, array_intersect_key($postData,  $entrepriseDefaultData ));			
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
			$findDocuments                   = (isset($postData["find_documents"]  ))? intval($postData["find_documents"]) : 0;
			$checkDocuments                  = (isset($postData["check_documents"] ))? intval($postData["check_documents"]): 0;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
			$numeroParts                     = $model->getNumParts($numero);
			$registreYear                    = (isset($numeroParts["annee"]))?intval($numeroParts["annee"]) : substr( $numero, 5, 4);
			if(!is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}
			if(!$modelMoral->checkNum($numero) ) {
				$errorMessages[]             = sprintf("Le numéro RCCM n° %s saisi ne semble pas valide.", $numero);
			}
			if( $model->findRow( $numero , "numero" , null , false )) {
				$errorMessages[]             = sprintf("Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]             = " Veuillez entrer un nom commercial valide pour ce registre";
			} elseif( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]             = sprintf("Un registre existant porte le nom commercial %s , veuillez entrer un nom commercial différent ", $libelle);
			} else {
				$registre_data["libelle"]    = $libelle;
			}			   
			if( !intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]]) ) {
				$errorMessages[]             = "Veuillez sélectionner une localité valide";
			} else {
				$registre_data["localiteid"] = intval( $registre_data["localiteid"] ) ;
			}			
			$localiteCode                    = (isset($localitesCodes[$registre_data["localiteid"]])) ? $localitesCodes[$registre_data["localiteid"]] : "";	
			$dateYear                        = (isset($postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                       = (isset($postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                         = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			$zendDate                        = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			$numeroPrefixToCheck             = sprintf("BF%s", $localiteCode);
						
			/* if( substr( $numero, 0, 5) != $numeroPrefixToCheck ) {
				$errorMessages[]             = "Le numéro attribué à ce registre n'est pas valide.";
			}			
			$numeroPrefixToCheck             = sprintf("BF%s%dB", $localiteCode, $dateYear);
			
			if(stripos($registre_data["numero"], $numeroPrefixToCheck) === FALSE) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il devrait commencer par %s", $registre_data["numero"], $numeroPrefixToCheck);
			}
            if(strlen($registre_data["numero"]) != 14) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $registre_data["numero"] );
			}	*/					
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
            if( !intval($registre_data["date"])) {
				$errorMessages[]             = "Veuillez indiquer une date d'inscription valide";
			}			
			$registre_data["type"]              = 2;
			$registre_data["statut"]            = 1;
			$registre_data["checked"]           = intval($checkDocuments);
			$registre_data["category"]          = "M0";
			$registre_data["description"]       = $stringFilter->filter( $registre_data["description"]);
			$registre_data["adresse"]           = $stringFilter->filter( $registre_data["adresse"]);
			$registre_data["telephone"]         = $stringFilter->filter( $registre_data["telephone"]);
			$registre_data["addressid"]         = 0;
			$registre_data["communeid"]         = 0;
			$registre_data["ifuid"]             = 0;
			$registre_data["numifu"]            = (isset($postData["numifu"])) ? $stringFilter->filter( $postData["numifu"] ) : "";
			$registre_data["cnssid"]            = 0;
			$registre_data["numcnss"]           = (isset($postData["numcnss"]))? $stringFilter->filter( $postData["numcnss"]) : "";
			$registre_data["cpcid"]             = 0;
			$registre_data["statusid"]          = 0;
			$registre_data["annee"]             = $registreYear;
			$registre_data["capital"]           = (isset($postData["capital"]  ))? floatval( $postData["capital"])   : 0;
			$registre_data["capital_nature"]    = (isset($postData["capital"]  ))? floatval( $postData["capital"])   : 0;
			$registre_data["capital_numeraire"] = (isset($postData["capital"]  ))? floatval( $postData["capital"])   : 0;
			$registre_data["nbactions"]         = (isset($postData["nbactions"]))? floatval( $postData["nbactions"]) : 0;
			$registre_data["creatorid"]         = $me->userid;
			$registre_data["creationdate"]      = time();	
			$registre_data["updateduserid"]     = 0;
			$registre_data["updatedate"]        = 0;
			$registre_data["parentid"]          = 0;
			$registre_data["domaineid"]         = intval( $registre_data["domaineid"] ) ;
			$documentsUploadAdapter             = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			$documentsUploadAdapter->setOptions(array("ignoreNoFile" => true));
			
			if(!empty($registre_data["numifu"])) {
				if( $foundRegistre = $model->findRow( $registre_data["numifu"], "numifu" , null , false )) {
					$errorMessages[]            = sprintf("Un RCCM existant porte déjà le numéro IFU %s", $registre_data["numifu"]);
				}
			}
            if(!empty($registre_data["numcnss"])) {
				if( $foundRegistre = $model->findRow( $registre_data["numcnss"], "numcnss" , null , false )) {
					$errorMessages[]          = sprintf("Un RCCM existant porte déjà le numéro CNSS %s", $registre_data["numcnss"]);
				}
			}			
			if(!$findDocuments ) {
				$documentsUploadAdapter      = new Zend_File_Transfer();
			    $documentsUploadAdapter->addValidator("Count"    , false , 4 );
			    $documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			    //$documentsUploadAdapter->addValidator("Size"     , false , array("max" => 1000));
			    //$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => 1000));
			    if(!$documentsUploadAdapter->isUploaded("docmini") ) {
				    $errorMessages[]         = "Le document formulaire n'a pas été fourni";
			    }
			    if(!$documentsUploadAdapter->isUploaded("docoriginal")) {
				    $errorMessages[]         = "Le document  personnel n'a pas été fourni";
			    }
				if(!$documentsUploadAdapter->isUploaded("docstatut")) {
				    $errorMessages[]         = "Le document contenant le statut n'a pas été fourni";
			    }
				if( $checkDocuments && empty($errorMessages) ) {
					$formulaireFilename      = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename       = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;	
					$statutFilename          = (isset($_FILES["docstatut"]["tmp_name"]  )) ? $_FILES["docstatut"]["tmp_name"]   : null;	
					$checkRccmData           = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, 
					                                 "numero"     => $numero, "statut" => $statutFilename );
					if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
						$errorMessages[]     =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
					}
				}
			} else {				
				$rccmFormulaireFilepath      = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero. DS. $numero."-FR.pdf";
			    $rccmPersonnelFilepath       = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero. DS. $numero."-PS.pdf";
				$rccmStatutFilepath          = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero. DS. $numero."-ST.pdf";

				if(!file_exists( $rccmFormulaireFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				if(!file_exists( $rccmStatutFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le statut du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				$checkRccmData               = array("formulaire"=> $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,"numero"=> $numero, "statut"=> $rccmStatutFilepath);
				if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
					$errorMessages[]         =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents", $numero);
				}
			}			
			if(!count( $errorMessages  )) {
				$emptyData                   = $model->getEmptyData();
				$clean_registre_data         = array_intersect_key($registre_data,$emptyData);
				if($dbAdapter->insert( $tableName, $clean_registre_data) ) {
				   $registreid                            = $dbAdapter->lastInsertId();					  
					  //On enregistre les informations de l'entreprise
				   $entreprise_data["registreid"]         = $registreid;
                   $entreprise_data["num_securite_social"]= (isset($postData["num_securite_social"]))? $stringFilter->filter( $postData["num_securite_social"] ) : $registre_data["numcnss"];
			       $entreprise_data["num_ifu"]            = (isset($postData["num_ifu"]            ))? $stringFilter->filter( $postData["num_ifu"])              : $registre_data["numifu"];
				   $entreprise_data["num_rc"]             = (isset($postData["num_rc"]             ))? $stringFilter->filter( $postData["num_rc"] )              : $numero;					  
				   $entreprise_data["libelle"]            = $stringFilter->filter($registre_data["libelle"]);
				   $entreprise_data["address"]            = $stringFilter->filter($entreprise_data["address"]);
				   $entreprise_data["email"]              = $stringFilter->filter($entreprise_data["email"])	;
				   $entreprise_data["phone1"]             = $stringFilter->filter($entreprise_data["phone1"])	;
				   $entreprise_data["phone2"]             = (isset( $postData["phone2"]  )) ? $stringFilter->filter( $postData["phone2"])   : "";
				   $entreprise_data["siteweb"]            = (isset( $postData["siteweb"] )) ? $stringFilter->filter( $postData["siteweb"])  : "";
				   $entreprise_data["country"]            = "";
				   $entreprise_data["zip"]                = "";
				   $entreprise_data["city"]               = 0;
				   if(!$entreprise_data["city"] && isset( $postData["ville"] ) ) {
				        if($strNotEmptyValidator->isValid( $postData["ville"] ) ) {
					       $libelleVille  = $stringFilter->filter( $postData["ville"] );
					       $rowCity       = $modelCity->findRow( $libelleVille , "libelle" , null , false);
					       if( $rowCity ) {
						       $entreprise_data["city"]   = $rowCity->id;
					       } else {
						       $libelleVille = $stringFilter->filter( $postData["ville"] );
						       if( $dbAdapter->insert( $prefixName . "system_countries_cities", array("libelle" => $libelleVille, "creatorid" => $me->userid,"creationdate" => time() ) ) ) {
							       $insert_data["city"]   = $dbAdapter->lastInsertId();
						       }
					       }
				        }
			       }
				   if( !empty($registre_data["numero"] ) ) {
				   	   $pageKey                           = preg_replace('/\s+/', '-', strtolower( $registre_data["numero"] ) );
				   	   $entreprise_data["pagekey"]        = $entreprise_data["reference"] = $pageKey;
				   }
                   if( isset($postData["libelle2"]) && !empty($postData["libelle2"])) {
					   $entreprise_data["libelle"]        = $stringFilter->filter($postData["libelle2"]);
				   }   
				   $entreprise_data["responsable"]        = ( isset( $postData["administrateur"] ))? $stringFilter->filter( $postData["administrateur"]) : "";
				   $entreprise_data["capital"]            = ( isset( $postData["capital"]        ))? floatval( preg_replace('/[^0-9\.,]/','',$postData["capital"] ))        : 0;
				   $entreprise_data["chiffre_affaire"]    = ( isset( $postData["chiffre_affaire"]))? floatval( preg_replace('/[^0-9\.,]/','',$postData["chiffre_affaire"])) : 0;
				   $entreprise_data["nbemployes_min"]     = ( isset( $postData["nbemployes_min"] ))? intval(   preg_replace('/[^0-9\.,]/','',$postData["nbemployes_min"]))  : 0;
				   $entreprise_data["nbemployes_max"]     = ( isset( $postData["nbemployes_max"] ))? intval(   preg_replace('/[^0-9\.,]/','',$postData["nbemployes_max"]))  : 0;
				   $entreprise_data["datecreation"]       = $registre_data["date"];
				   $entreprise_data["presentation"]       = "";
				   $entreprise_data["region"]             = 0;
				   $entreprise_data["groupid"]            = 1;
				   $entreprise_data["responsableid"]      = 0;
				   $entreprise_data["responsable_email"]  = ( isset( $postData["responsable_email"] )) ? $stringFilter->filter( $postData["responsable_email"]) : "";
				   $entreprise_data["formid"]             = ( isset( $postData["formid"] ) )  ? intval( $postData["formid"] )   : 0;
			       $entreprise_data["domaineid"]          = ( isset( $postData["domaineid"])) ? intval( $postData["domaineid"]) : 0;
				   $entreprise_data["reference"]          = $registre_data["numero"];
				   $entreprise_data["creatorid"]          = $me->userid;
				   $entreprise_data["creationdate"]       = time();
				   $entreprise_data["updateduserid"]      = 0;
				   $entreprise_data["updatedate"]         = 0;				   
					  
				   if(    $dbAdapter->insert( $prefixName . "rccm_registre_entreprises", $entreprise_data ) ) {
					  	  $entrepriseid                   = $dbAdapter->lastInsertId();
						  for( $i=1; $i<=3; $i++ )  {
							   if(isset( $postData["dirigeants_nom".$i] ) && isset( $postData["dirigeants_prenom".$i] )) {
								  $dirigeantInfos         = array("registreid" => $registreid, "entrepriseid" => $entrepriseid, "fonction" => "GERANT", "representantid" => 0);
                                  $representant_data      =	$modelRepresentant->getEmptyData();
                                  //On enregistre les informations de l'representant					  
					              $dateNaissanceYear      = (isset($postData["dirigeants_date_naissance_year".$i] ))? $stringFilter->filter($postData["dirigeants_date_naissance_year".$i])  : "0000";
					              $dateNaissanceMonth     = (isset($postData["dirigeants_date_naissance_month".$i]))? $stringFilter->filter($postData["dirigeants_date_naissance_month".$i]) : "00";
					              $dateNaissanceDay       = (isset($postData["dirigeants_date_naissance_day".$i]) && ( $postData["dirigeants_date_naissance_day".$i] != "00" ))? $stringFilter->filter($postData["dirigeants_date_naissance_day".$i]) : "05";
					  
					              $representant_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					              $representant_data["lieunaissance"] = (isset($postData["dirigeants_lieunaissance".$i] ))? $stringFilter->filter($postData["dirigeants_lieunaissance".$i])  : "";
					              $representant_data["marital_status"]= (isset($postData["dirigeants_marital_status".$i]))? $stringFilter->filter($postData["dirigeants_marital_status".$i]) : "";
					              $representant_data["nom"]           = (isset($postData["dirigeants_nom".$i]           ))? $stringFilter->filter($postData["dirigeants_nom".$i])            : "";
					              $representant_data["prenom"]        = (isset($postData["dirigeants_prenom".$i]        ))? $stringFilter->filter($postData["dirigeants_prenom".$i])         : "";
					              $representant_data["adresse"]       = (isset($postData["dirigeants_adresse".$i]       ))? $stringFilter->filter($postData["dirigeants_adresse".$i])        : "";
								  $representant_data["cnib"]          = (isset($postData["dirigeants_cnib".$i]          ))? $stringFilter->filter($postData["dirigeants_cnib".$i])           : "";
					              $representant_data["city"]          = 0;
					              $representant_data["country"]       = "BF";
								  $representant_data["sexe"]          = (isset($postData["dirigeants_sexe".$i]       ))? $stringFilter->filter($postData["dirigeants_sexe".$i]) : "";
					              $representant_data["email"]         = (isset($postData["dirigeants_email".$i]      ))? $stringFilter->filter($postData["dirigeants_email".$i]) : "";
					              $representant_data["telephone"]     = (isset($postData["dirigeants_telephone".$i]  ))? $stringFilter->filter($postData["dirigeants_telephone".$i]) : "";
					              $representant_data["passport"]      = (isset($postData["dirigeants_passport".$i]   ))? $stringFilter->filter($postData["dirigeants_passport".$i]) : "";
					              $representant_data["profession"]    = (isset($postData["dirigeants_profession".$i] ))? $stringFilter->filter($postData["dirigeants_profession".$i]) : "";
								  $representant_data["structure"]     = "";
					              $representant_data["creatorid"]     = $me->userid;
					              $representant_data["creationdate"]  = time();
					              $representant_data["updateduserid"] = 0;
					              $representant_data["updatedate"]    = 0;
                                  if(!empty($representant_data["nom"]) && !empty($representant_data["prenom"])) {
									  if( $dbAdapter->insert( $prefixName . "rccm_registre_representants", $representant_data ) ) {
										  $representantid                    = $dbAdapter->lastInsertId();
										  $dirigeantInfos["representantid"]  = $representantid;
										  $dirigeantInfos["fonction"]        = (isset($postData["dirigeants_profession".$i] ))? $stringFilter->filter($postData["dirigeants_profession".$i]) : "";
										  if( $dbAdapter->insert( $prefixName . "rccm_registre_dirigeants", $dirigeantInfos )) {
											  $dirigeantid                   = true;
										  }
									  }
								  }									  
							   }
						  }
					  	  if( $registreid && $dirigeantid && $representantid ) {					  	  						  	  	   					  	  	  					  	  	   					  	  	  
                              if(!$findDocuments) {
								   //On essaie d'enregistrer les documents du registre				  	  	       
					  	  	       $rcPathroot                     = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode . DS . $registreYear. DS . $numero;					  	  	  
					  	  	       $documentData                   = array();
					  	  	       $documentData["userid"]         = $me->userid;
					  	  	       $documentData["category"]       = 1;
					  	  	       $documentData["resource"]       = "registremoral";
					  	  	       $documentData["resourceid"]     = 0;
					  	  	       $documentData["filedescription"]= $registre_data["description"];
					  	  	       $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	       $documentData["creationdate"]   = time();
					  	  	       $documentData["creatoruserid"]  = $me->userid;
								   
								   if( $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docstatut") && $documentsUploadAdapter->isUploaded("docoriginal")) {
									   $formulairePath             = $rcPathroot . DS . $numero."-FR.pdf";
					  	  	           $personnelPath              = $rcPathroot . DS . $numero."-PS.pdf";
								       $statutPath                 = $rcPathroot . DS . $numero."-ST.pdf";
					  	  	           $documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath, "overwrite" => true), "docmini");
								       if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode . DS .$registreYear) ) {
									       if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
										       @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										       @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
										       @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
									       }
									      if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
										     @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
										     @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
									      }
									         @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear);
									         @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear, 0777 );									   
								       }
								       if(!is_dir($rcPathroot)) {
									       @chmod($rcPathroot, 0777 );
									       @mkdir($rcPathroot);
								       }					  	  	  
					  	  	           $documentsUploadAdapter->receive("docmini");
					  	  	  	       if( $documentsUploadAdapter->isReceived("docmini") ) {
					  	  	  	   	       $miniFileSize                     = $documentsUploadAdapter->getFileSize("docmini");
					  	  	  	   	       $formulaireData                   = $documentData;
					  	  	  	   	       $formulaireData["filename"]       = $modelDocument->rename("FORMULAIRE", $numero);
					  	  	  	   	       $formulaireData["filepath"]       = $formulairePath;
					  	  	  	   	       $formulaireData["access"]         = 0 ;
					  	  	  	   	       $formulaireData["filextension"]   = "pdf";
					  	  	  	   	       $formulaireData["filesize"]       = floatval( $miniFileSize );
					  	  	  	   	       if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
					  	  	  	   	   	       $documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	   	       $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0 ));
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
					  	  	  		       $personnelDocData["filename"]         = $modelDocument->rename("PERSONNEL",$numero);
					  	  	  		       $personnelDocData["filepath"]         = $personnelPath;
					  	  	  		       $personnelDocData["access"]           = 6;
					  	  	  		       $personnelDocData["filextension"]     = "pdf";
					  	  	  		       $personnelDocData["filesize"]         = floatval($personnelDocFileSize);
					  	  	  		       if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  			       $documentid                       = $dbAdapter->lastInsertId();
					  	  	  			       $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 6));
					  	  	  		       } else {
					  	  	  			       $errorMessages[]                  = "Les informations du document personnel ont été partiellement enregistrées";
					  	  	  		       }					  	  	  	
					  	  	  	        } else {
					  	  	  		           $errorMessages[]                  = "Le document personnel n'a pas pu être copié sur le serveur";
					  	  	  	        }
							            $documentsUploadAdapter->addFilter("Rename", array("target" => $statutPath,"overwrite" => true), "docstatut");
					  	  	  	        $documentsUploadAdapter->receive("docstatut");
					  	  	  	        if( $documentsUploadAdapter->isReceived("docstatut") ) {
					  	  	  		        $statutDocFileSize                    = $documentsUploadAdapter->getFileSize("docstatut");
					  	  	  		        $statutDocData                        = $documentData;
					  	  	  		        $statutDocData["filename"]            = $modelDocument->rename("STATUT",$numero);
					  	  	  		        $statutDocData["filepath"]            = $statutPath;
					  	  	  		        $statutDocData["access"]              = 6;
					  	  	  		        $statutDocData["filextension"]        = "pdf";
					  	  	  		        $statutDocData["filesize"]            = floatval($statutDocFileSize);
					  	  	  		        if( $dbAdapter->insert( $prefixName ."system_users_documents", $statutDocData) ) {
					  	  	  			        $documentid                       = $dbAdapter->lastInsertId();
					  	  	  			        $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid"=> $documentid,"access"=> 6));
					  	  	  		        } else {
					  	  	  			        $errorMessages[]                   = "Les informations du document statut ont été partiellement enregistrées";
					  	  	  		        }					  	  	  	
					  	  	  	        } else {
					  	  	  		            $errorMessages[]                   = "Le document statut n'a pas été copié sur le serveur";
					  	  	  	        }
								    }					  	  	  					  	  	  					  	  	  					  	  	  					  	  	      							   
							  } else {
				                $rccmFormulaireFilepath                    = $filesSource. DS . $localiteCode.DS .$registreYear.DS. $numero. DS. $numero."-FR.pdf";
			                    $rccmPersonnelFilepath                     = $filesSource. DS . $localiteCode.DS .$registreYear.DS. $numero. DS. $numero."-PS.pdf";
								$rccmStatutFilepath                        = $filesSource. DS . $localiteCode.DS .$registreYear.DS. $numero. DS. $numero."-ST.pdf";
								$rcPathroot                                = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode . DS .$registreYear. DS . $numero;
								$newRccmFormulaireFilepath                 = $rcPathroot . DS . $numero."-FR.pdf";
								$newRccmPersonnelFilepath                  = $rcPathroot . DS . $numero."-PS.pdf";
                                $newRccmStatutFilepath                     = $rcPathroot . DS . $numero."-ST.pdf";		
								if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath ) && file_exists($rccmStatutFilepath)) {										   											   
					  	  	        $documentData                   = array();
					  	  	        $documentData["userid"]         = $me->userid;
					  	  	        $documentData["category"]       = 2;
					  	  	        $documentData["resource"]       = "registremoral";
					  	  	        $documentData["resourceid"]     = 0;
					  	  	        $documentData["filedescription"]= $registre_data["description"];
					  	  	        $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	        $documentData["creationdate"]   = time();
					  	  	        $documentData["creatoruserid"]  = $me->userid;
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode . DS .$registreYear)) {
									    if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
										    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
										    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
									    }
									    if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
										    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
										    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
									    }
									        @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear);
									        @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear, 0777 );									   
								    }
								    if(!is_dir($rcPathroot)) {
									    @chmod($rcPathroot, 0777 );
									    @mkdir($rcPathroot);
								    }
									if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
					  	  	  	   	    $formulaireFileData                 = $documentData;
					  	  	  	   	    $formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE", $numero);
					  	  	  	   	    $formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
					  	  	  	   	    $formulaireFileData["access"]       = 0 ;
					  	  	  	   	    $formulaireFileData["filextension"] = "pdf";
					  	  	  	   	    $formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
					  	  	  	   	    if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	   	   	    $documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	   	    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0 ));
					  	  	  	   	    } else {
					  	  	  	   	   	    $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	   	    }
									} else {
											$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
									}
									if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	   	    $personnelFileData                  = $documentData;
					  	  	  	   	    $personnelFileData["filename"]      = $modelDocument->rename("PERSONNEL", $numero);
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
									if( TRUE ==@copy($rccmStatutFilepath, $newRccmStatutFilepath)) {
					  	  	  	   	    $statutFileData                     = $documentData;
					  	  	  	   	    $statutFileData["filename"]         = $modelDocument->rename("STATUT", $numero);
					  	  	  	   	    $statutFileData["filepath"]         = $newRccmStatutFilepath;
					  	  	  	   	    $statutFileData["access"]           = 6;
					  	  	  	   	    $statutFileData["filextension"]     = "pdf";
					  	  	  	   	    $statutFileData["filesize"]         = floatval(filesize( $rccmstatutFilepath ));
					  	  	  	   	    if( $dbAdapter->insert( $prefixName ."system_users_documents", $statutFileData )) {
					  	  	  	   	   	    $documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	   	    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
					  	  	  	   	    } else {
					  	  	  	   	   	    $errorMessages[]                = "Les informations du statut ont été partiellement enregistrées";
					  	  	  	   	    }
									} else {
											$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du statut  n'a pas fonctionné", $numero);
									}
								}  
							  }							  
					  	  	  if( !count( $errorMessages ) ) {
					  	  	  	  if( $this->_request->isXmlHttpRequest() ) {
					  	  	  		  $this->_helper->viewRenderer->setNoRender(true);
					  	  	  		  $this->_helper->layout->disableLayout(true);
					  	  	  		  echo ZendX_JQuery::encodeJson(array("success" => "Les informations du registre de type moral ont été enregistrées avec succès"));
					  	  	  		  exit;
					  	  	  	   }
					  	  	  	      $this->setRedirect("Les informations du registre de type moral ont été enregistrées avec succès", "success" );
					  	  	  	      if( $newCreation ) {
										  $this->redirect("admin/registremoral/create");
									  } else {
										  $this->redirect("admin/registremoral/infos/id/" . $registreid );
									  }					  	  	  	
					  	  	  }					  	  	  					  	  	  					  	  	  					  	  	
					  	  } else {
					  	  	$errorMessages[]= " Les informations du registre ont été partiellement enregistrées, veuillez reprendre l'opération";
					  	  }
					  } else {
					  	$errorMessages[]    = " Les informations de l'entreprise n'ont pas été enregistrées, veuillez reprendre l'opération";
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
		$this->view->formes    = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid","libelle"), array("orders" => array("libelle ASC")), null , null , false );
	}
	
	
	public function editAction()
	{
		$this->view->title    = " Mettre à jour les informations d'un registre de commerce ";
		
		$registreid           = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));
			
		$model                = $this->getModel("registremorale");
		$modelRepresentant    = $this->getModel("representant");
		$modelEntreprise      = $this->getModel("entreprise");
		$modelDomaine         = $this->getModel("domaine");
		$modelLocalite        = $this->getModel("localite");
		$modelDocument        = $this->getModel("document");
		$modelEntrepriseforme = $this->getModel("entrepriseforme");
 	
		$registre             = ( $registreid ) ? $model->findRow( $registreid, "registreid" , null , false) : null;
		$entreprise           = ( $registreid ) ? $modelEntreprise->findRow(  $registreid, "registreid", null , false) : null;
		$dirigeants           = ( $registre   ) ? $registre->dirigeants() : array();
		if(!$registre || !$entreprise ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremoral/list");
		}
		$entrepriseid                      = $entreprise->entrepriseid;
		$domaines                          = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                         = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                    = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);		
		
		$registreData                      = $registre->toArray();
		$entrepriseData                    = $entreprise->toArray();
		$defaultData                       = array_merge( $entrepriseData, $registreData );
		$errorMessages                     = array();  
		
		$defaultData["date_year"]          = date("Y", $registre->date);
		$defaultData["date_month"]         = date("m", $registre->date);
		$defaultData["date_day"]           = date("d", $registre->date);
        $defaultData["check_documents"]    = $registre->checked;
		$defaultData["find_documents"]     = DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
		$defaultData["find_documents_src"] = $fileSource = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		if( $this->_request->isPost()) {
			$postData                      = $this->_request->getPost();
			$update_registre_data          = array_merge( $registreData  , array_intersect_key( $postData, $registreData   ));
			$update_entreprise_data        = array_merge( $entrepriseData, array_intersect_key( $postData, $entrepriseData ));
			$me                            = Sirah_Fabric::getUser();
			$modelTable                     = $me->getTable();
			$dbAdapter                     = $modelTable->getAdapter();
			$prefixName                    = $modelTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                  = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($update_registre_data["numero"])));
			$libelle                         = $stringFilter->filter( $update_registre_data["libelle"] );
			$findDocuments                   = (isset($postData["find_documents"])) ? intval($postData["find_documents"]) : DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
			$checkDocuments                  = (isset($postData["check_documents"]))? intval($postData["check_documents"]): DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
			$registreYear                    = substr($numero, 5, 4);
			if( !is_dir($defaultDocumentSrc) ) {
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
            $numeroPrefixToCheck                     = sprintf("BF%s", $localiteCode);
			/*if( substr( $numero, 0, 5) != $numeroPrefixToCheck ) {
				$errorMessages[]                     = "Le numéro attribué à ce registre n'est pas valide.";
			}*/
            if(strlen($update_registre_data["numero"]) != 14) {
				$errorMessages[]                     = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $update_registre_data["numero"] );
			}
			$update_registre_data["domaineid"]             = intval( $update_registre_data["domaineid"] ) ;	
			$update_registre_data["date"]                  = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["annee"]                 = $registreYear;
			$update_registre_data["type"]                  = 2;
			$update_registre_data["checked"]               = intval($checkDocuments);
			$update_registre_data["statut"]                = intval( $update_registre_data["statut"]  );
			$update_registre_data["numifu"]                = $stringFilter->filter( $update_registre_data["numifu"]);
			$update_registre_data["numcnss"]               = $stringFilter->filter( $update_registre_data["numcnss"]);
			$update_registre_data["description"]           = $stringFilter->filter( $update_registre_data["description"]  );
			$update_registre_data["adresse"]               = $stringFilter->filter( $update_registre_data["adresse"]    );
			$update_registre_data["telephone"]             = $stringFilter->filter( $update_registre_data["telephone"]  );
			$update_registre_data["updateduserid"]         = $me->userid;
			$update_registre_data["updatedate"]            = time();
			
            if( !intval($update_registre_data["date"])) {
				$errorMessages[]             = "Veuillez indiquer une date d'inscription valide";
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
			//On enregistre les informations de l'entreprise
			$update_entreprise_data["libelle"]             = $stringFilter->filter( $update_registre_data["libelle"]);
			$update_entreprise_data["num_securite_social"] = (isset($postData["num_securite_social"])) ? $stringFilter->filter( $postData["num_securite_social"] ) : "";
			$update_entreprise_data["num_rc"]              = (isset($postData["num_rc"]             )) ? $stringFilter->filter( $postData["num_rc"] ) : $numero;	
			$update_entreprise_data["siteweb"]             = $stringFilter->filter($update_entreprise_data["siteweb"]);
			$update_entreprise_data["adress"]              = $stringFilter->filter($update_entreprise_data["adress"]);
			$update_entreprise_data["phone1"]              = $stringFilter->filter($update_entreprise_data["phone2"]);	
            $update_entreprise_data["phone2"]              = $stringFilter->filter($update_entreprise_data["phone1"]);			
			$update_entreprise_data["responsable"]         = (isset($postData["administrateur"] ))? $stringFilter->filter( $postData["administrateur"]):$update_entreprise_data["responsable"];
			$update_entreprise_data["capital"]             = (isset($postData["capital"]        ))? floatval( preg_replace('/[^0-9\.,]/','', $postData["capital"])): floatval( preg_replace('/[^0-9\.,]/','', $update_entreprise_data["capital"]));
			$update_entreprise_data["nbemployes_min"]      = (isset($postData["nbemployes_min"] ))? intval(  $postData["nbemployes_min"]): $update_entreprise_data["nbemployes_min"];
			$update_entreprise_data["nbemployes_max"]      = (isset($postData["nbemployes_max"] ))? intval(  $postData["nbemployes_max"]): $update_entreprise_data["nbemployes_max"];
			$update_entreprise_data["datecreation"]        = $update_registre_data["date"];						
			$update_entreprise_data["updateduserid"]       = $me->userid;
			$update_entreprise_data["updatedate"]          = time();
			$update_entreprise_data["formid"]              = ( isset( $postData["formid"] ) )  ? intval( $postData["formid"] )   : 5;
			$update_entreprise_data["domaineid"]           = ( isset( $postData["domaineid"])) ? intval( $postData["domaineid"]) : 0;
			
			if( isset($postData["libelle2"]) && !empty($postData["libelle2"])) {
				$update_entreprise_data["libelle"]         = $stringFilter->filter($postData["libelle2"]);
			}
            $documentsUploadAdapter                        = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			$documentsUploadAdapter->setOptions(array("ignoreNoFile" => true));			
			if(!$findDocuments ) {
				if( $checkDocuments && empty($errorMessages) && $documentsUploadAdapter->isUploaded("docstatut") && $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
					$formulaireFilename      = (isset($_FILES["docmini"]["tmp_name"]    ))? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename       = (isset($_FILES["docoriginal"]["tmp_name"]))? $_FILES["docoriginal"]["tmp_name"] : null;	
					$statutFilename          = (isset($_FILES["docstatut"]["tmp_name"]  ))? $_FILES["docstatut"]["tmp_name"]   : null;	
					$checkRccmData           = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, 
					                                 "numero"     => $numero, "statut" => $statutFilename );
					if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
						$errorMessages[]     =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
					}
				}
			} else {				
				$rccmFormulaireFilepath      = $filesSource. DS . $localiteCode . DS .$registreYear. DS . $numero. DS. $numero."-FR.pdf";
			    $rccmPersonnelFilepath       = $filesSource. DS . $localiteCode . DS .$registreYear. DS . $numero. DS. $numero."-PS.pdf";
				$rccmStatutFilepath          = $filesSource. DS . $localiteCode . DS .$registreYear. DS . $numero. DS. $numero."-ST.pdf";
				/*if(!file_exists($rccmFormulaireFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				if(!file_exists($rccmStatutFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le statut du RC N° %s n'existe pas dans la source des documents", $numero);
				}
				$checkRccmData               = array("formulaire"=> $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,
				                                     "numero"    => $numero, "statut" => $rccmStatutFilepath);
				if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
					$errorMessages[]         =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents", $numero);
				}*/
			}			
			if(isset(  $update_registre_data["registreid"])) {
				unset( $update_registre_data["registreid"] );
			}
			if(isset(  $update_entreprise_data["entrepriseid"])) {
				unset( $update_entreprise_data["entrepriseid"] );
			}					 
			$registre->setFromArray(   $update_registre_data );
			$entreprise->setFromArray( $update_entreprise_data );
			if( empty($errorMessages)) {
				if( $registre->save() && $entreprise->save() ) {
					if( count($postData["dirigeants"]) && isset($postData["dirigeants_nom1"]) && isset($postData["dirigeants_prenom1"])) {
						$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				        $dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");
						$dirigeants  = array();
						foreach( $postData["dirigeants"] as $dirigeantKey ) {
							     $dirigeantInfos                     = array("registreid"=>$registreid,"entrepriseid"=>$entrepriseid,"fonction"=>"GERANT","representantid"=>0);
                                 $representant_data                  =	$modelRepresentant->getEmptyData();
                                  //On enregistre les informations du representant					  
					             $dateNaissanceYear                  = (isset($postData["dirigeants_date_naissance_year".$dirigeantKey] ))? $stringFilter->filter($postData["dirigeants_date_naissance_year".$dirigeantKey])  : "0000";
					             $dateNaissanceMonth                 = (isset($postData["dirigeants_date_naissance_month".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_date_naissance_month".$dirigeantKey]) : "00";
					             $dateNaissanceDay                   = (isset($postData["dirigeants_date_naissance_day".$dirigeantKey]) && ( $postData["dirigeants_date_naissance_day".$dirigeantKey] != "00" ))? $stringFilter->filter($postData["dirigeants_date_naissance_day".$dirigeantKey]) : "00";
					  
					             $representant_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					             $representant_data["lieunaissance"] = (isset($postData["dirigeants_lieunaissance".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_lieunaissance".$dirigeantKey]) : "";
					             $representant_data["marital_status"]= (isset($postData["dirigeants_marital_status".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_marital_status".$dirigeantKey]) : "";
					             $representant_data["nom"]           = (isset($postData["dirigeants_nom".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_nom".$dirigeantKey]) : "";
					             $representant_data["prenom"]        = (isset($postData["dirigeants_prenom".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_prenom".$dirigeantKey]) : "";
					             $representant_data["adresse"]       = (isset($postData["dirigeants_adresse".$dirigeantKey]))? $stringFilter->filter($postData["dirigeants_adresse".$dirigeantKey]) : "";
					             $representant_data["city"]          = 0;
					             $representant_data["country"]       = (isset($postData["dirigeants_country".$dirigeantKey]    ))? $stringFilter->filter($postData["dirigeants_country".$dirigeantKey]) : "BF";
								 $representant_data["sexe"]          = (isset($postData["dirigeants_sexe".$dirigeantKey]       ))? $stringFilter->filter($postData["dirigeants_sexe".$dirigeantKey]) : "";
					             $representant_data["email"]         = (isset($postData["dirigeants_email".$dirigeantKey]      ))? $stringFilter->filter($postData["dirigeants_email".$dirigeantKey]) : "";
					             $representant_data["telephone"]     = (isset($postData["dirigeants_telephone".$dirigeantKey]  ))? $stringFilter->filter($postData["dirigeants_telephone".$dirigeantKey]) : "";
					             $representant_data["passport"]      = (isset($postData["dirigeants_passport".$dirigeantKey]   ))? $stringFilter->filter($postData["dirigeants_passport".$dirigeantKey]) : "";
								 $representant_data["profession"]    = (isset($postData["dirigeants_profession".$dirigeantKey] ))? $stringFilter->filter($postData["dirigeants_profession".$dirigeantKey]) : "";
					             $representant_data["structure"]     = "";
					             $representant_data["creatorid"]     = $me->userid;
					             $representant_data["creationdate"]  = time();
					             $representant_data["updateduserid"] = 0;
					             $representant_data["updatedate"]    = 0;
								 $representant_data["cnib"]          = $representant_data["passport"];
								 
								 if( !empty($representant_data["nom"]) && !empty($representant_data["prenom"])) {
									    if( $dbAdapter->insert( $prefixName ."rccm_registre_representants", $representant_data )) {
										    $representantid                    = $dbAdapter->lastInsertId();
											$dirigeantInfos["representantid"]  = $representantid;
										    $dirigeantInfos["fonction"]        = (isset($postData["dirigeants_profession".$dirigeantKey] ))? $stringFilter->filter($postData["dirigeants_profession".$dirigeantKey]) : "";
										    if( $dbAdapter->insert( $prefixName ."rccm_registre_dirigeants", $dirigeantInfos ) ) {
												$dirigeantid                   = $dbAdapter->lastInsertId();
											} else {
												$errorMessages[]               = sprintf("Les informations du dirigeant n'ont pas pu être enregistrées");
											}
										} else {
											    $errorMessages[]               = sprintf("Les informations du promoteur n'ont pas pu être enregistrées");
										}
								 }								 
								 /*$dirigeants[]                       = array_merge($representant_data, $dirigeantInfos);*/                                  
						}
					}					
					if(!$findDocuments ) {
						$documentData                   = array();
					  	$documentData["userid"]         = $me->userid;
					  	$documentData["category"]       = 1;
					  	$documentData["resource"]       = "registremoral";
					  	$documentData["resourceid"]     = 0;
					  	$documentData["filedescription"]= $registre_data["description"];
					  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	$documentData["creationdate"]   = time();
					  	$documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	$formulairePath                 = $rcPathroot . DS . $numero."-FR.pdf";
					  	$personnelPath                  = $rcPathroot . DS . $numero."-PS.pdf";
						$statutPath                     = $rcPathroot . DS . $numero."-ST.pdf";
						if( $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal") && $documentsUploadAdapter->isUploaded("docstatut")) {
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode . DS .$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
								}
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear, 0777 );									   
							}
							if(!is_dir($rcPathroot)) {
								@chmod($rcPathroot, 0777 );
								@mkdir($rcPathroot);
							}
							$documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath, "overwrite" => true), "docmini");
							$documentsUploadAdapter->receive("docmini");
					  	  	if( $documentsUploadAdapter->isReceived( "docmini") ) {
					  	  	  	$miniFileSize                     = $documentsUploadAdapter->getFileSize("docmini");
					  	  	  	$formulaireData                   = $documentData;
					  	  	  	$formulaireData["filename"]       = $modelDocument->rename("FORMULAIRE", $numero);
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
					  	  	  	   	$errorMessages[]              = "Le formulaire n'a pas été reçu par le serveur";
					  	  	}
							$documentsUploadAdapter->addFilter("Rename", array("target" => $personnelPath, "overwrite" => true), "docoriginal");
					  	  	$documentsUploadAdapter->receive("docoriginal");
							if( $documentsUploadAdapter->isReceived("docoriginal") ) {
					  	  	    $personnelDocFileSize                 = $documentsUploadAdapter->getFileSize("docoriginal");
					  	  	  	$personnelDocData                     = $documentData;
					  	  	  	$personnelDocData["filename"]         = $modelDocument->rename("PERSONNEL",$numero);
					  	  	  	$personnelDocData["filepath"]         = $personnelPath;
					  	  	  	$personnelDocData["access"]           = 6;
					  	  	  	$personnelDocData["filextension"]     = "pdf";
					  	  	  	$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
								$dbAdapter->delete($prefixName."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							    $dbAdapter->delete($prefixName."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  		$documentid                       = $dbAdapter->lastInsertId();
					  	  	  		$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 6));
					  	  	  	} else {
					  	  	  		$errorMessages[]                  = "Les informations du document personnel ont été partiellement enregistrées";
					  	  	  	}					  	  	  	
					  	  	} else {
					  	  	  		$errorMessages[]                  = "Le document personnel n'a pas été copié sur le serveur";
					  	  	}
							$documentsUploadAdapter->addFilter("Rename", array("target" => $statutPath, "overwrite" => true), "docstatut");
					  	  	$documentsUploadAdapter->receive("docstatut");
							if( $documentsUploadAdapter->isReceived("docstatut") ) {
					  	  	    $statutDocFileSize                 = $documentsUploadAdapter->getFileSize("docstatut");
					  	  	  	$statutDocData                     = $documentData;
					  	  	  	$statutDocData["filename"]         = $modelDocument->rename("STATUT",$numero );
					  	  	  	$statutDocData["filepath"]         = $statutPath;
					  	  	  	$statutDocData["access"]           = 2;
					  	  	  	$statutDocData["filextension"]     = "pdf";
					  	  	  	$statutDocData["filesize"]         = floatval($statutDocFileSize);
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid,"access=2"));
							    $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=2 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $statutDocData) ) {
					  	  	  		$documentid                    = $dbAdapter->lastInsertId();
					  	  	  		$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 2));
					  	  	  	} else {
					  	  	  		$errorMessages[]               = "Les informations du document statut ont été partiellement enregistrées";
					  	  	  	}					  	  	  	
					  	  	} else {
					  	  	  		$errorMessages[]               = "Le document statut n'a pas été copié sur le serveur";
					  	  	}
						}					  	  	       					  	  	
					} else {
						$rccmFormulaireFilepath             = $filesSource. DS . $localiteCode. DS .$registreYear. DS . $numero. DS. $numero."-FR.pdf";
			            $rccmPersonnelFilepath              = $filesSource. DS . $localiteCode. DS .$registreYear. DS . $numero. DS. $numero."-PS.pdf";
						$rccmStatutFilepath                 = $filesSource. DS . $localiteCode. DS .$registreYear. DS . $numero. DS. $numero."-ST.pdf";
						if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath ) && file_exists($rccmStatutFilepath)) {
							$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
							$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
							$newRccmStatutFilepath          = $rcPathroot . DS . $numero."-ST.pdf";
										   					  	  	       					  	  	  
					  	  	$documentData                   = array();
					  	  	$documentData["userid"]         = $me->userid;
					  	  	$documentData["category"]       = 1;
					  	  	$documentData["resource"]       = "registremoral";
					  	  	$documentData["resourceid"]     = 0;
					  	  	$documentData["filedescription"]= $registre_data["description"];
					  	  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	$documentData["creationdate"]   = time();
					  	  	$documentData["creatoruserid"]  = $me->userid;
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode . DS .$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
								}
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS .$registreYear, 0777 );									   
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
					  	  	  	$formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE", $numero );
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
							if(file_exists( $newRccmPersonnelFilepath )) {
								@unlink( $newRccmPersonnelFilepath );
							}
							if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	$personnelFileData                  = $documentData;
					  	  	  	$personnelFileData["filename"]      = $modelDocument->rename("PERSONNEL", $numero);
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
							if( TRUE ==@copy($rccmStatutFilepath, $newRccmStatutFilepath)) {
					  	  	  	$statutFileData                     = $documentData;
					  	  	  	$statutFileData["filename"]         = $modelDocument->rename("STATUT", $numero);
					  	  	  	$statutFileData["filepath"]         = $newRccmPersonnelFilepath;
					  	  	  	$statutFileData["access"]           = 2;
					  	  	  	$statutFileData["filextension"]     = "pdf";
					  	  	  	$statutFileData["filesize"]         = floatval(filesize( $rccmStatutFilepath ));
								$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=2"));
							    $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=2 AND registreid='".$registreid."')");
					  	  	  	if( $dbAdapter->insert($prefixName ."system_users_documents", $statutFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert($prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 2));
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
							$jsonErrorArray            = $update_data;
							$jsonErrorArray["success"] = "Les informations du registre ont été mises à jour avec succès";
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect("Les informations du registre ont été mises à jour avec succès", "success" );
						$this->redirect("admin/registremoral/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("admin/registremoral/list" );
				}
			} else {
				    $defaultData   = array_merge($update_entreprise_data, $update_registre_data, $postData );				
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
		$this->view->dirigeants  = $dirigeants;
		$this->view->formes      = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid","libelle"), array("orders" => array("libelle ASC")), null , null , false );
	}	
 		
		
	public function infosAction()
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
			$this->redirect("registremoral/list");
		}		
		$model               = $this->getModel("registremorale");
		$modelDirigeant      = $this->getModel("registredirigeant");
		$modelRepresentant   = $this->getModel("representant");
		$modelEntreprise     = $this->getModel("entreprise");
		$modelDomaine        = $this->getModel("domaine");
		$modelLocalite       = $this->getModel("localite");
		$modelDocument       = $this->getModel("document");
		$modelEntrepriseforme= $this->getModel("entrepriseforme");
		$modelCity           = $this->getModel("countrycity");
 	
		$registre            = $model->findRow( $registreid, "registreid" , null , false);
		$entreprise          = $modelEntreprise->findRow(  $registreid, "registreid", null , false  );
		$dirigeants          = ( $registre ) ? $registre->dirigeants() : array();
		if(!$registre ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremoral/list");
		}
			
		$registreData              = $registre->toArray();
		$entrepriseData            = ($entreprise)?$entreprise->toArray() : array();
		$defaultData               = array_merge( $entrepriseData, $registreData );
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->registreid    = $registreid;
		$this->view->entreprise    = $entreprise;
		$this->view->domaine       = $registre->findParentRow("Table_Domaines");
		$this->view->forme         = ( $entreprise ) ? $entreprise->findParentRow("Table_Entrepriseformes") : null;
		$this->view->localite      = $registre->findParentRow("Table_Localites");
		$this->view->documents     = $registre->documents();
		$this->view->modifications = $registre->modifications($regitreid);
		$this->view->suretes       = $registre->suretes($registreid);
		$this->view->dirigeants    = $dirigeants;
		$this->view->title         = sprintf("Les informations du registre de commerce numero %s", $registre->numero);
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
			$this->redirect("registremoral/list");
		}		
		$model                 = $this->getModel("registre");
		$modelEntreprise       = $this->getModel("entreprise");
		$modelDocument         = $this->getModel("document");
		$dbAdapter             = $model->getTable()->getAdapter();
		$prefixName            = $model->getTable()->info("namePrefix");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);	
		$entreprise            = $modelEntreprise->findRow($registreid , "registreid", null , false );	
		if(!$registre ||  !$entreprise ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremoral/list");
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
			$this->redirect("admin/registremoral/list");
		}
		$rccmFormulaireFilepath            = $fileSource. DS . $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-FR.pdf";
		$rccmPersonnelFilepath             = $fileSource. DS . $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-PS.pdf";
		$rccmStatutFilepath                = $fileSource. DS . $localiteCode . DS . $dateYear. DS . $numero. DS. $numero."-ST.pdf";
		$rcPathroot                        = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode. DS . $dateYear. DS . $numero;
		
		if(!file_exists($rccmFormulaireFilepath)) {
			$errorMessages[]               = "Dans le dossier source, le formulaire du registre est manquant";
		}
		if(!file_exists( $rccmPersonnelFilepath )) {
			$errorMessages[]               = "Dans le dossier source, le fond de dossier du registre est manquant";
		}
		if(!file_exists($rccmStatutFilepath)) {
			$errorMessages[]               = "Dans le dossier source, le statut du registre est manquant";
		}
		if(!is_dir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode . DS . $dateYear) ) {
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
			}
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
			}
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS . $dateYear);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS . $dateYear, 0777 );									   
		}
		if(!is_dir($rcPathroot)) {
			@chmod($rcPathroot, 0777 );
			@mkdir($rcPathroot);
		}
		$documentData                   = array();
		$documentData["userid"]         = $me->userid;
		$documentData["category"]       = 2;
		$documentData["resource"]       = "registremorale";
		$documentData["resourceid"]     = 0;
		$documentData["filedescription"]= $registre->description;
		$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
		$documentData["creationdate"]   = time();
		$documentData["creatoruserid"]  = $me->userid;
		$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
		$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
		$newRccmStatutFilepath          = $rcPathroot . DS . $numero."-ST.pdf";
		if( TRUE ==@copy($rccmFormulaireFilepath, $newRccmFormulaireFilepath )) {
			$formulaireFileData                 = $documentData;
			$formulaireFileData["filename"]     = $modelDocument->rename("FORMULAIRE", $numero );
			$formulaireFileData["filepath"]     = $newRccmFormulaireFilepath;
			$formulaireFileData["access"]       = 0 ;
			$formulaireFileData["filextension"] = "pdf";
			$formulaireFileData["filesize"]     = floatval(filesize($rccmFormulaireFilepath));
			$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
			$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
			if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
				$documentid                         = $dbAdapter->lastInsertId();
				$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
			} else {
				$errorMessages[]                    = "Les informations du formulaire ont été partiellement enregistrées";
			}
			} else {
				$errorMessages[]                    = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
			}
			if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
				$personnelFileData                  = $documentData;
				$personnelFileData["filename"]      = $modelDocument->rename("FOND_DE_DOSSIER",$numero );
				$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
				$personnelFileData["access"]        = 6;
				$personnelFileData["filextension"]  = "pdf";
				$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
				$dbAdapter->delete($prefixName."rccm_registre_documents",array("registreid=".$registreid, "access=6"));
				$dbAdapter->delete($prefixName."system_users_documents" ,"documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
				if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					$documentid                     = $dbAdapter->lastInsertId();
					$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				} else {
					$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
				}
			} else {
					$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
			}
			if( TRUE ==@copy($rccmStatutFilepath, $newRccmStatutFilepath)) {
				$personnelFileData                  = $documentData;
				$personnelFileData["filename"]      = $modelDocument->rename("STATUT",$numero );
				$personnelFileData["filepath"]      = $newRccmStatutFilepath;
				$personnelFileData["access"]        = 2;
				$personnelFileData["filextension"]  = "pdf";
				$personnelFileData["filesize"]      = floatval(filesize($newRccmStatutFilepath));
				$dbAdapter->delete($prefixName ."rccm_registre_documents",array("registreid=".$registreid, "access=2"));
				$dbAdapter->delete($prefixName ."system_users_documents" ,"documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=2 AND registreid='".$registreid."')");
				if( $dbAdapter->insert($prefixName ."system_users_documents", $personnelFileData)) {
					$documentid                     = $dbAdapter->lastInsertId();
					$dbAdapter->insert($prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
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
			      $this->setRedirect("Les nouveaux documents ( à jour) de ce registre ont été indexés avec succès", "success");
			      $this->redirect("admin/registremoral/infos/registreid/".$registreid);
			} else {
				if( $this->_request->isXmlHttpRequest()) {
				    echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				    exit;
			    }
			    foreach( $errorMessages as $errorMessage) {
				         $this->_helper->Message->addMessage($errorMessage , "error");
			    }
			    $this->redirect("admin/registremoral/infos/registreid/".$registreid);
			}			
	}
	
	public function updatealldocsAction()
	{
		$model                     = $this->getModel("registre");
		$modelEntreprise           = $this->getModel("entreprise");
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
		             $entreprise   = $modelEntreprise->findRow($registreid , "registreid", null , false );
					 if( $registre && $entreprise ) {
						 $numero                 = $registre->numero;
		                 $dateYear               = substr( $numero, 5, 4);
		                 $localite               = $registre->findParentRow("Table_Localites");
		                 $localiteCode           = ($localite ) ? $localite->code : "";
						 $rccmFormulaireFilepath = $fileSource.DS. $localiteCode. DS . $dateYear. DS . $numero. DS. $numero."-FR.pdf";
		                 $rccmPersonnelFilepath  = $fileSource.DS. $localiteCode. DS . $dateYear. DS . $numero. DS. $numero."-PS.pdf";
		                 $rccmStatutFilepath     = $fileSource.DS. $localiteCode. DS . $dateYear. DS . $numero. DS. $numero."-ST.pdf";
						 $rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES"    . DS . $localiteCode. DS . $dateYear. DS . $numero;
					     if( empty($localiteCode) || (strlen($dateYear) != 4)) {
							 $errorMessages[]    = sprintf("Le RCCM n° %s n'est pas valide ", $numero );
							 continue;
						 }
						 if(!file_exists($rccmFormulaireFilepath)) {
			                 $errorMessages[]    = sprintf("Dans le dossier source, le formulaire du registre n° %s est manquant", $numero);
							 continue;
		                 }
		                 if(!file_exists( $rccmStatutFilepath )) {
			                $errorMessages[]     = sprintf("Dans le dossier source, le statut du registre n° %s est manquant", $numero);
							continue;
		                 }
						 if(!file_exists( $rccmPersonnelFilepath )) {
			                $errorMessages[]     = sprintf("Dans le dossier source, le fond de dossier du registre n° %s est manquant", $numero);
							continue;
		                 }
		                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode . DS . $dateYear) ) {
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES")) {
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES");
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES", 0777 );
			                 }
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode)) {
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode, 0777 );
			                 }
				            @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS . $dateYear);
				            @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MORALES". DS . $localiteCode. DS . $dateYear, 0777 );									   
		                 }
		                 if(!is_dir($rcPathroot)) {
			                @chmod($rcPathroot, 0777 );
			                @mkdir($rcPathroot);
		                 }
						 $documentData                           = array();
		                 $documentData["userid"]                 = $me->userid;
		                 $documentData["category"]               = 1;
		                 $documentData["resource"]               = "registremoral";
		                 $documentData["resourceid"]             = 0;
		                 $documentData["filedescription"]        = $registre->description;
		                 $documentData["filemetadata"]           = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
		                 $documentData["creationdate"]           = time();
		                 $documentData["creatoruserid"]          = $me->userid;
		                 $newRccmFormulaireFilepath              = $rcPathroot . DS . $numero."-FR.pdf";
						 $newRccmStatutFilepath                  = $rcPathroot . DS . $numero."-ST.pdf";
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
						 if( TRUE ==@copy($rccmStatutFilepath, $newRccmStatutFilepath)) {
			                 $statutFileData                  = $documentData;
			                 $statutFileData["filename"]      = $modelDocument->rename("STATUT", $numero);
				             $statutFileData["filepath"]      = $newRccmStatutFilepath;
				             $statutFileData["access"]        = 2;
				             $statutFileData["filextension"]  = "pdf";
				             $statutFileData["filesize"]      = floatval(filesize($newRccmStatutFilepath));
				             $dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=2"));
				             $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=2 AND registreid='".$registreid."')");
				             if( $dbAdapter->insert( $prefixName ."system_users_documents", $statutFileData)) {
					             $documentid                  = $dbAdapter->lastInsertId();
					             $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				             } else {
					             $errorMessages[]                = sprintf("Les informations du statut du RC n° %s ont été partiellement enregistrées", $numero);
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
				             $dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
				             $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
				             if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					             $documentid                     = $dbAdapter->lastInsertId();
					             $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				             } else {
					             $errorMessages[]                = sprintf("Les informations du fond de dossier du RC n° %s ont été partiellement enregistrées", $numero);
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
			$this->redirect("admin/registremoral/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été re-indexés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été re-indexés avec succès", "success");
			$this->redirect("admin/registremoral/list");
		}
	}
	
	public function csvimportAction()
	{		
		$errorMessages              = array();
		$jsonCsvRows                = array();
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender( true );
		    $this->_helper->layout->disableLayout( true );
			$me                     = Sirah_Fabric::getUser();
			$postData               = $this->_request->getPost();
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
				
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
	
	public function ajaximportAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		//$this->getResponse()->setHeader("Content-Type", "application/json");
		if(!$this->_request->isPost() ) {
			echo ZendX_JQuery::encodeJson(array("error" => "La requête que vous avez transmise n'est pas valide"));
			exit;
		}
		$csvRow                              = $postData = $this->_request->getPost();
		
		$model                               = $this->getModel("registre");
		$modelRepresentant                   = $this->getModel("representant");
		$modelEntreprise                     = $this->getModel("entreprise");
		$modelDomaine                        = $this->getModel("domaine");
		$modelLocalite                       = $this->getModel("localite");
		$modelDocument                       = $this->getModel("document");
		$modelEntrepriseforme                = $this->getModel("entrepriseforme");
		$modelDirigeant                      = $this->getModel("registredirigeant");
		$modelRepresentant                   = $this->getModel("representant");
		
		$modelTable                          = $model->getTable();
		$prefixName                          = $modelTable->info("namePrefix");
		$dbDestination                       = $dbAdapter = $modelTable->getAdapter();
		$me                                  = Sirah_Fabric::getUser();
		
		$registreDefaultData                 = $model->getEmptyData();
		$entrepriseDefaultData               = $modelEntreprise->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle")   , array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("code"      ,"localiteid"), array() , 0 , null , false);
		$countries                           = $this->view->countries();
		$localiteid                          = intval($this->_getParam("localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID));
		$domaineid                           = intval($this->_getParam("domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID ));
		$defaultData                         = array_merge( $entrepriseDefaultData , $registreDefaultData );
		$defaultData["domaineid"]            = intval($this->_getParam("domaineid" , $me->getParam("default_domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID)));
		$defaultData["localiteid"]           = intval($this->_getParam("localiteid", $me->getParam("default_localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID)));		
		$defaultData["date_year"]            = intval($this->_getParam("annee"     , $me->getParam("default_year"      , DEFAULT_YEAR)));
		$defaultData["country"]              = "BF";
		$defaultData["date_month"]           = sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));
		$defaultData["date_day"]             = null;
		$defaultData["country"]              = "BF";
		$defaultData["find_documents_src"] = $fileSource = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		$errorMessages                       = array();
		$registreid                          = 0;
		$entrepriseid                        = 0;
		$registre                            = null;
		$registre2                           = null;
		
		$strNotEmptyValidator                = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		$stringFilter                        = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$numeroRegistre            = $numero = (isset($csvRow["numero"]              ))? preg_replace("/\s/","", $stringFilter->filter(strtoupper($stringFilter->filter($csvRow["numero"])))): null;
		$localiteRegistre          = (isset($csvRow["localite"]                      ))? strtoupper($stringFilter->filter($csvRow["localite"]))                          : "";
		$libelleRegistre           = (isset($csvRow["nom_commercial"]                ))? trim($stringFilter->filter($csvRow["nom_commercial"]),"-")                      : "";
		$dateRegistre              = (isset($csvRow["date_enregistrement"]           ))? trim($stringFilter->filter($csvRow["date_enregistrement"]),"-")                 : date("d/m/Y");
		$descriptionRegistre       = (isset($csvRow["description"]                   ))? trim($csvRow["description"], "-")                                               : "";
		$exploitantLastname        = (isset($csvRow["nom"]                           ))? trim($stringFilter->filter($csvRow["nom"]), "-")                                : "";
		$exploitantFirstname       = (isset($csvRow["prenom"]                        ))? trim($stringFilter->filter($csvRow["prenom"]), "-")                             : "";
		$exploitantLieuNaissance   = (isset($csvRow["lieu_naissance"]                ))? trim($stringFilter->filter($csvRow["lieu_naissance"]),"-")                      : "";
		$exploitantDateNaissance   = (isset($csvRow["date_naissance"]                ))? trim($stringFilter->filter($csvRow["date_naissance"]),"-")                      : "";
        $exploitantSexe            = (isset($csvRow["sexe"]                          ))? trim(strtoupper($stringFilter->filter($csvRow["sexe"])),"-")                    : "";
		$exploitantAdresse         = (isset($csvRow["adresse"]                       ))? trim($stringFilter->filter($csvRow["adresse"]),"-")                             : "";
		$exploitantTelephone       = (isset($csvRow["telephone"]                     ))? trim($stringFilter->filter($csvRow["telephone"]),"-")                           : "";
		$exploitantPassport        = (isset($csvRow["passport"]                      ))? trim($stringFilter->filter($csvRow["passport"] ),"-")                           : "";
		$exploitantNationalite     = (isset($csvRow["nationalite"]                   ))? trim(strtoupper($stringFilter->filter($csvRow["nationalite"])),"-")             : "";
		$exploitantMaritalStatus   = (isset($csvRow["situation_matrimonial"]         ))? trim($stringFilter->filter($csvRow["situation_matrimonial"]),"-")               : "";
		$exploitantEmail           = (isset($csvRow["email"]                         ))? trim($stringFilter->filter($csvRow["email"]), "-")                              : " ";
		$exploitantFonction        = (isset($csvRow["fonction"]                      ))? trim($stringFilter->filter($csvRow["fonction"]), "-")                           : "GERANT";	
        $entrepriseAdresse         = (isset($csvRow["entreprise_adresse"]            ))? $stringFilter->filter($csvRow["entreprise_adresse"])                            : "";		
        $entrepriseTelephone       = (isset($csvRow["entreprise_telephone"]          ))? $stringFilter->filter($csvRow["entreprise_telephone"])                          : "";	
        $entrepriseCapital         = (isset($csvRow["entreprise_capital"]            ))? floatval( preg_replace('/[^0-9\.,]/','',$csvRow["entreprise_capital"]))         : 0;	
        $entrepriseChiffreAffaire  = (isset($csvRow["entreprise_chiffre_affaire"]    ))? floatval( preg_replace('/[^0-9\.,]/','',$csvRow["entreprise_chiffre_affaire"])) : 0;
        $entrepriseNumSecurite     = (isset($csvRow["entreprise_num_securite_social"]))? $stringFilter->filter($csvRow["entreprise_num_securite_social"])                : "";
        $entrepriseEmail           = (isset($csvRow["entreprise_email"]              ))? $stringFilter->filter($csvRow["entreprise_email"])                              : "";		
		
        $toUpdateRegistre          = null;		
		$localitesCodes2           = $localitesCodes;
        $inscriptionDate           = 0;
		$toRemove                  = false;
		$localiteCode              = (isset($postData["localite"]           )) ? $stringFilter->filter($postData["localite"]) : "";
        $dateYear                  =  $registreYear = (isset($postData["date_year"])) ? intval($postData["date_year"]): null;
		array_flip($localitesCodes2);
		
		//print_r($localitesCodes2); die();
								 
		if(!$strNotEmptyValidator->isValid($numero)) {
		    $errorMessages[]     = "Votre requête n'est pas valide, car le numéro RC est vide";
		} 
		if($toUpdateRegistre     = $model->findRow( $numero, "numero" , null , false )) {		   
		   $registre             = $toUpdateRegistre;
		   $registreid           = $toUpdateRegistre->registreid;
		   $registreDirigeant    = $modelDirigeant->findRow( $registreid, "registreid", null , false )	;
		   $representantid       = $registreDirigeant->representantid;
		   $registreRepresentant = ( $registreDirigeant ) ? $modelRepresentant->findRow( $registreDirigeant->representantid , "representantid", null , false ) : null;
		   if( $registre->creatorid != $me->userid) {
			   $errorMessages[]  = sprintf("Le registre de commerce numéro %s existe déjà", $numeroRegistre);
			   $toRemove         = false;
		   } 
		} 
        if(!$strNotEmptyValidator->isValid($libelleRegistre)) {
			$libelleRegistre    = sprintf("%s %s %s", $exploitantLastname, $exploitantFirstname, $numero);
		} 
		$libelleRegistre        = str_replace($numero, "", $libelleRegistre). " ".$numero;
		if( $registre2          = $model->findRow( $libelleRegistre , "libelle" , null , false )) {
			$errorMessages[]    = sprintf("Un registre existant porte le nom commercial %s, veuillez entrer un nom commercial différent du RC n° %s", $libelleRegistre, $numeroRegistre );
			$registre2Id        = $registre2->registreid;
			if( $registre2Id   != $registreid ) {
			    $errorMessages[]= sprintf("Un registre existant porte le nom commercial %s, veuillez entrer un nom commercial différent du RC n° %s", $libelleRegistre, $numeroRegistre );
		    }
		}    
		if(!$strNotEmptyValidator->isValid($exploitantLastname ) || !$strNotEmptyValidator->isValid($exploitantFirstname)) {
			$errorMessages[]    = sprintf("Veuillez entrer un nom de famille et/ou prénom valide du representant du RC numéro %d", $numero );
		} 	
		if( empty($localiteRegistre) ) {
			$localiteRegistre   = strtoupper(substr($numeroRegistre,2,3));
        } 
		if(!isset( $localitesCodes2[$localiteRegistre])) {
			$errorMessages[]    = sprintf("Veuillez sélectionner une localité valide du registre n° %s:%s", $numero, $localiteRegistre );
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
        if(!$inscriptionDate ) {
            $errorMessages[]    = sprintf("Veuillez indiquer une date valide pour le registre n° %s", $numero );
		}									 
        $numeroPrefixToCheck    = sprintf("BF%s", $localiteRegistre);
		$numTypeRegistre        = strtoupper(trim(substr($numero, 9, 1)));
                                 //$dateYear              = $registreYear = date("Y", $inscriptionDate);
        if( $numTypeRegistre !== "B") {
			$errorMessages[]    = sprintf("Le numéro de la Personne Morale `%s` ne semble pas valide", $numero );
		}			
        if( substr($numero,0,5)!= $numeroPrefixToCheck ) {
			$errorMessages[]    = sprintf("Le numéro attribué au registre numéro %s n'est pas valide", $numero);
		}								 
		if( strlen($numero ) != 14) {
			$errorMessages[]    = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $numero);
		}
		if(!$dateYear ) {
			$dateYear           = $registreYear = substr($numero,5, 4);
		}
		if((strtolower($exploitantSexe) == "femme") || (strtolower($exploitantSexe) == "femmes") || (strtolower($exploitantSexe) == "feminin")) {
		    $exploitantSexe     = "F";
		} elseif( (strtolower($exploitantSexe)=="homme") || (strtolower($exploitantSexe) == "hommes") || (strtolower($exploitantSexe) == "masculin") ) {
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
		if(!$strNotEmptyValidator->isValid($entrepriseAdresse)) {
			$entrepriseAdresse         = $exploitantAdresse;
		}
		if(!$strNotEmptyValidator->isValid($entrepriseTelephone)) {
			$entrepriseTelephone       = $exploitantTelephone;
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
		if(empty( $errorMessages) && !empty( $numero )) {
			if( $registre ) {
				$registreid       = $registre->registreid;
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			$registre_data                   = array();
		    $registre_data["numero"]         = $numero;
	        $registre_data["libelle"]        = $libelleRegistre;
	        $registre_data["localiteid"]     = $localiteid;
		    $registre_data["date"]           = $inscriptionDate;
		    $registre_data["type"]           = 2;
		    $registre_data["statut"]         = 1;
            $registre_data["category"]       = "M0";
		    $registre_data["checked"]        = 1;
		    $registre_data["description"]    = $descriptionRegistre;
			$registre_data["adresse"]        = $entrepriseAdresse;
			$registre_data["telephone"]      = $entrepriseTelephone;
		    $registre_data["creatorid"]      = $me->userid;
            $registre_data["creationdate"]   = time()+150;	
            $registre_data["updateduserid"]  = 0;
            $registre_data["updatedate"]     = 0;
            $registre_data["domaineid"]      = $domaineid;
			$registre_data["parentid"]       = 0;
			
			if( $dbAdapter->insert( $prefixName ."rccm_registre", $registre_data)) {
				$toRemove                                = true;
				$registreid                              = $dbAdapter->lastInsertId();
				$dbAdapter->delete( $prefixName ."rccm_registre_entreprises", "libelle=\"".$libelleRegistre."\"");								
				$entreprise_data["registreid"]           = $registreid;
                $entreprise_data["num_securite_social"]  = $entrepriseNumSecurite;
			    $entreprise_data["num_rc"]               = $registre_data["numero"];					  
				$entreprise_data["libelle"]              = $stringFilter->filter($registre_data["libelle"]);
				$entreprise_data["address"]              = $entrepriseAdresse;
				$entreprise_data["email"]                = $entrepriseEmail;
				$entreprise_data["phone1"]               = $entrepriseTelephone;
				$entreprise_data["phone2"]               = "";
				$entreprise_data["siteweb"]              = "";
				$entreprise_data["country"]              = "BF";
				$entreprise_data["zip"]                  = "";
				$entreprise_data["city"]                 = 0;
				$entreprise_data["responsable"]          = "";
				$entreprise_data["capital"]              = $entrepriseCapital;
				$entreprise_data["chiffre_affaire"]      = 0;
				$entreprise_data["nbemployes_min"]       = 0;
				$entreprise_data["nbemployes_max"]       = 0;
				$entreprise_data["datecreation"]         = $registre_data["date"];
				$entreprise_data["presentation"]         = "";
				$entreprise_data["region"]               = 0;
				$entreprise_data["groupid"]              = 1;
			    $entreprise_data["responsableid"]        = 0;
				$entreprise_data["responsable_email"]    = "";
				$entreprise_data["formid"]               = 5;
			    $entreprise_data["domaineid"]            = $registre_data["domaineid"];
				$entreprise_data["reference"]            = $registre_data["numero"];
				$entreprise_data["creatorid"]            = $me->userid;
				$entreprise_data["creationdate"]         = time()+160;
				$entreprise_data["updateduserid"]        = 0;
				$entreprise_data["updatedate"]           = 0;
                if( $dbAdapter->insert( $prefixName . "rccm_registre_entreprises", $entreprise_data ) ) {
					$entrepriseid                        = $dbAdapter->lastInsertId();	
					$representant_data                   = array();
			        $representant_data["datenaissance"]  = $dateNaissance;
			        $representant_data["lieunaissance"]  = $exploitantLieuNaissance;
			        $representant_data["nom"]            = $exploitantLastname;
			        $representant_data["prenom"]         = $exploitantFirstname;
			        $representant_data["adresse"]        = $exploitantAdresse;
			        $representant_data["city"]           = 0;
			        $representant_data["country"]        = $exploitantNationalite;
			        $representant_data["email"]          = $exploitantEmail;
			        $representant_data["marital_status"] = $exploitantMaritalStatus;
			        $representant_data["telephone"]      = $exploitantTelephone;
			        $representant_data["passport"]       = $exploitantPassport;
			        $representant_data["sexe"]           = $exploitantSexe;
			        $representant_data["structure"]      = "";
			        $representant_data["profession"]     = $exploitantFonction;
			        $representant_data["creatorid"]      = $me->userid;
			        $representant_data["creationdate"]   = time();
			        $representant_data["updateduserid"]  = 0;
			        $representant_data["updatedate"]     = 0;
				    if( $dbAdapter->insert( $prefixName ."rccm_registre_representants", $representant_data )) {
				        $representantid                  = $dbAdapter->lastInsertId();
				        if( $dbAdapter->insert( $prefixName ."rccm_registre_dirigeants", array("registreid"=> $registreid,"representantid"=> $representantid,"fonction"=> $exploitantFonction))) {											 
					        $registres[]                 = $registreid;
					        $rccmFormulaireFilepath      = $fileSource.DS.$localiteCode.DS. $registreYear.DS.$numero.DS.$numero."-FR.pdf";
			                $rccmPersonnelFilepath       = $fileSource.DS.$localiteCode.DS. $registreYear.DS.$numero.DS.$numero."-PS.pdf";
					        $rccmStatutFilepath          = $fileSource.DS.$localiteCode.DS. $registreYear.DS.$numero.DS.$numero."-ST.pdf";
							$rcPathroot                  = APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MORALES".DS.$localiteCode. DS .$registreYear. DS. $numero;
							if( file_exists($rccmFormulaireFilepath) && file_exists($rccmStatutFilepath) && file_exists($rccmPersonnelFilepath) && $registreid && $entrepriseid ) {
								$newRccmFormulaireFilepath       = $rcPathroot. DS . $numero."-FR.pdf";
						        $newRccmPersonnelFilepath        = $rcPathroot. DS . $numero."-PS.pdf";
								$newRccmStatutFilepath           = $rcPathroot. DS . $numero."-PS.pdf";
								$documentData                    = array();
					  	        $documentData["userid"]          = $me->userid;
					  	        $documentData["category"]        = 1;
					  	        $documentData["resource"]        = "registremoral";
					  	        $documentData["resourceid"]      = 0;
					  	        $documentData["filedescription"] = $registre_data["description"];
					  	        $documentData["filemetadata"]    = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	        $documentData["creationdate"]    = time();
					  	        $documentData["creatoruserid"]   = $me->userid;
								if(!is_dir(   APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode. DS .$registreYear) ) {
							       if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES")) {
								      @chmod( APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
								      @mkdir( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES");
								      @chmod( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES", 0777 );
							       }
							       if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode)) {
								      @mkdir( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode);
								      @chmod( APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode, 0777 );
							       }
							       @mkdir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode. DS . $registreYear);
							       @chmod(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MORALES". DS . $localiteCode. DS . $registreYear, 0777 );									   
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
							            $errorMessages[]                = sprintf("Le registre numéro %s n'a pas été enregistré , Le formulaire n'a pas pu être copié", $numero);
						        }
								if( TRUE ==@copy($rccmStatutFilepath, $newRccmStatutFilepath)) {
					  	  	        $statutFileData                     = $documentData;
					  	  	        $statutFileData["filename"]         = $modelDocument->rename("STATUT", $numero);
					  	  	        $statutFileData["filepath"]         = $newRccmStatutFilepath;
					  	  	        $statutFileData["access"]           = 2;
					  	  	        $statutFileData["filextension"]     = "pdf";
					  	  	        $statutFileData["filesize"]         = floatval(filesize( $rccmStatutFilepath ));
					  	  	        if( $dbAdapter->insert( $prefixName  ."system_users_documents", $statutFileData)) {
					  	  	  	        $documentid                     = $dbAdapter->lastInsertId();
					  	  	  	        $dbAdapter->insert( $prefixName  ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 2));
					  	  	        } 
						        } else {
							            $errorMessages[]                = sprintf("Le registre numéro %s n'a pas été enregistré, Le statut n'a pas pu etre copié", $numero );
						        }
						        if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	        $personnelFileData                  = $documentData;
					  	  	        $personnelFileData["filename"]      = $modelDocument->rename("PERSONNEL", $numero);
					  	  	        $personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	        $personnelFileData["access"]        = 6;
					  	  	        $personnelFileData["filextension"]  = "pdf";
					  	  	        $personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
					  	  	        if( $dbAdapter->insert( $prefixName  ."system_users_documents", $personnelFileData)) {
					  	  	  	        $documentid                     = $dbAdapter->lastInsertId();
					  	  	  	        $dbAdapter->insert( $prefixName  ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
					  	  	        } 
						        } else {
							            $errorMessages[]                = sprintf("Le registre numéro %s n'a pas été enregistré, Le fond de dossier n'a pas pu etre copié", $numero );
						        }
							} else {
								        $errorMessages[]                = sprintf("Le registre numéro %s n'a pas été enregistré, le fond de dossier, le statut ou le formulaire est manquant : %s - %d - %d", $numero, $rccmPersonnelFilepath, $entrepriseid, $registreid );
							}
					    } else {
							            $errorMessages[]                = sprintf("Au moins un dirigeant doit être enregistré pour le registre numéro %s", $numero );
						}
				    } else {
				                        $errorMessages[]                = sprintf("Au moins un dirigeant doit être enregistré pour le registre numéro %s", $numero );
			        }
				} else {
					                    $errorMessages[]                = sprintf("Les informations de l'entreprise associée au registre numéro %s n'ont pas été enregistrées", $numero );
				}					
			} else {
				                        $errorMessages[]                = sprintf("Le registre numéro %s n'a pas été enregistré, Aucune information n'a pu être enregistrée", $numero );
			}
		}		
		if( count( $errorMessages )) {
			if( intval($registreid) && ($toRemove==true)) {
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
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
	
		
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("registre");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$me            = Sirah_Fabric::getUser();
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
						$dbAdapter->delete($prefixName."rccm_registre_moral"        , array("registreid=".$id ));						
						$dbAdapter->delete($prefixName."rccm_registre_entreprises"  , "entrepriseid IN (SELECT entrepriseid FROM ".$prefixName."rccm_registre_moral     WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"     , "documentid   IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
						$dbAdapter->delete($prefixName."rccm_registre_documents"    , array("registreid=".$id ));
						$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$id);						
				        $dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$id."')");
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
			$this->redirect("admin/registremoral/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registremoral/list");
		}
	}
	
	public function addmanagerAction()
	{
		$model              = $this->getModel("registre");
		$modelRepresentant  = $this->getModel("representant");
		$modelEntreprise    = $this->getModel("entreprise");
		$dbAdapter          = $model->getTable()->getAdapter();
		$prefixName         = $model->getTable()->info("namePrefix");
		$me                 = Sirah_Fabric::getUser();
		$registreid         = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));
		
		$errorMessages      = array();
		$registre           = ( $registreid ) ? $model->findRow( $registreid, "registreid" , null , false) : null;
		$entreprise         = ( $registreid ) ? $modelEntreprise->findRow(  $registreid, "registreid", null , false  ) : null;
		if(!$registre || !$entreprise ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremoral/list");
		}
		$defaultData         = $modelRepresentant->getEmptyData();
		if( $this->_request->isPost() ) {
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator         = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$postData                     = $this->_request->getPost();
			$insert_data                  = array_merge($defaultData, array_intersect_key($postData, $defaultData));
			$dateNaissanceYear            = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
			$dateNaissanceMonth           = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay             = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
				
			$insert_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$insert_data["lieunaissance"] = $stringFilter->filter( $insert_data["lieunaissance"]  );
			$insert_data["marital_status"]= $stringFilter->filter( $insert_data["marital_status"] );
			$insert_data["nom"]           = $stringFilter->filter( $insert_data["nom"]      );
			$insert_data["prenom"]        = $stringFilter->filter( $insert_data["prenom"]   );
			$insert_data["adresse"]       = $stringFilter->filter( $insert_data["adresse"]  );
			$insert_data["email"]         = $stringFilter->filter( $insert_data["email"]    );
			$insert_data["passport"]      = $stringFilter->filter( $insert_data["passport"] );
			$insert_data["sexe"]          = $stringFilter->filter( $insert_data["sexe"]     );
			$insert_data["city"]          = 0;
			$insert_data["country"]       = $stringFilter->filter( $insert_data["country"]  );
			$insert_data["telephone"]     = $stringFilter->filter( $insert_data["telephone"]);
			$insert_data["profession"]    = $stringFilter->filter( $insert_data["profession"]);
			$insert_data["structure"]     = "";						
			$insert_data["creatorid"]     = $me->userid;
			$insert_data["creationdate"]  = time();
			$insert_data["updateduserid"] = 0;
			$insert_data["updatedate"]    = 0;
			if(!$strNotEmptyValidator->isValid( $insert_data["nom"] ) || !$strNotEmptyValidator->isValid( $insert_data["prenom"] )) {
				$errorMessages[]          = " Veuillez entrer un nom de famille et/ou prénom valide pour le representant";
			}
			if( empty( $errorMessages )) {
				if( $dbAdapter->insert( $prefixName . "rccm_registre_representants", $insert_data )) {
					$representantid       = $dbAdapter->lastInsertId();
					$dbAdapter->insert( $prefixName . "rccm_registre_dirigeants", array("registreid" => $registreid,"representantid" => $representantid, "fonction" => $insert_data["profession"]));
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
				        echo ZendX_JQuery::encodeJson(array("success" =>  "Les informations du gérant ont été enregistrés avec succès"));
				        exit;
					}
					$this->setRedirect("Les informations du gérant ont été enregistrés avec succès","success");
					$this->redirect("admin/registremoral/list");
				}
			}
			 $defaultData        = array_merge( $defaultData , $postData );
		}
		if( count( $errorMessages )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data    = $defaultData;
	}
	
	public function delmanagerAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("registre");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("representantids", $this->_getParam("ids",array()));
		$errorMessages = array();
		
		if( count(   $ids )) {
			foreach( $ids as $representantid ) {
				     if( $dbAdapter->delete( $prefixName."rccm_registre_representants", "representantid=".$representantid)) {
						 $dbAdapter->delete( $prefixName."rccm_registre_dirigeants", "representantid=".$representantid);
					 } else {
						 $errorMessages[]   = sprintf("Le représentant id#%s n'a pas été supprimé", $representantid );
					 }
				     
			}
		}
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/registremoral/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les dirigeants selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les dirigeants selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registremoral/list");
		}
	}
	
	
	
	public function importdocubasecsvAction()
	{
		@ini_set('memory_limit', '512M');
		
		$this->view->title          = "Importer les documents";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                    "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$defaultInitData            = array("localite"=> "OUA","annee"=>2000);
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$csvRows                    = array();
		$rccmSaved                  = array();
		$errorMessages              = array();
		$countries                  = $this->view->countries();
		
		if( $this->_request->isPost() ) {
			$postData               = $this->_request->getPost();
			$annee                  = (isset($postData["annee"]    ))? intval($postData["annee"]) : 2000;
			$localite               = (isset($postData["localite"] ))? $postData["localite"]      : "OUA";
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée un validateur de filtre
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
			
			if( !intval( $annee )) {
				$errorMessages[]    = "Veuillez renseigner une année valide";
			}
			if( !$strNotEmptyValidator->isValid( $localite )) {
				$errorMessages[]    = "Veuillez sélectionner une localité valide";
			}
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			
			$me                     = Sirah_Fabric::getUser();
			$modelTable              = $me->getTable();
			$dbAdapter              = $modelTable->getAdapter();
			$prefixName             = $tablePrefix = $modelTable->info("namePrefix");
			$imported               = array();
						 			
            $csvDestinationName     = APPLICATION_DATA_PATH . DS . "tmp" . DS . time()."rccmImport.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("Quartier","Section","Lot","Parcelle","NumeroRCCM","NomDemandeur","AdressePostale","DateDemande","DateNaissance","LieuNaissance","Pays","NomCommercial","Capital","ArticlePrincipale","Telephone","AdressePhysique");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvRows        = $csvAdapter->getLines();
					$csvItems       = 1;
					
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $registreData       = array();
							     $DateDemande        = (isset($csvRow["DateDemande"]        ))?$csvRow["DateDemande"]                         : "";
								 $DateNaissance      = (isset($csvRow["DateNaissance"]      ))?$csvRow["DateNaissance"]                       : "";
								 $LieuNaissance      = (isset($csvRow["LieuNaissance"]      ))?trim($stringFilter->filter($csvRow["LieuNaissance"]),"-"): "";
								 $NumeroRCCM         = (isset($csvRow["NumeroRCCM"]         ))?trim($stringFilter->filter($csvRow["NumeroRCCM"] ),"-")  : "";
								 $Quartier           = (isset($csvRow["Quartier"]           ))?trim($stringFilter->filter($csvRow["Quartier"]),"-")     : "";
								 $Section            = (isset($csvRow["Section"]            ))?trim($stringFilter->filter($csvRow["Section"]),"-")      : "";
								 $Parcelle           = (isset($csvRow["Parcelle"]           ))?trim($stringFilter->filter($csvRow["Parcelle"]),"-")     : "";
								 $Lot                = (isset($csvRow["Lot"]                ))?trim($stringFilter->filter($csvRow["Lot"]),"-")          : "";
								 $NomCommercial      = (isset($csvRow["NomCommercial"]      ))?trim($stringFilter->filter($csvRow["NomCommercial"]),"-"):"";
								 $NomDemandeur       = (isset($csvRow["NomDemandeur"]       ))?trim($stringFilter->filter($csvRow["NomDemandeur"]),"-") :"";
								 $ArticlePrincipale = (isset($csvRow["ArticlePrincipale"] ))?trim($stringFilter->filter($csvRow["ArticlePrincipale"]),"-") :"";
								 $Pays               = (isset($csvRow["Pays"]               ))?trim($stringFilter->filter($csvRow["Pays"]),"-")           :"";
								 $AdressePostale     = (isset($csvRow["AdressePostale"]     ))?trim($stringFilter->filter($csvRow["AdressePostale"]),"-") :"";
								 $AdressePhysique    = (isset($csvRow["AdressePhysique"]    ))?trim($stringFilter->filter($csvRow["AdressePhysique"]),"-"):"";
								 $Telephone          = (isset($csvRow["Telephone"]          ))?trim($stringFilter->filter($csvRow["Telephone"]),"-")      :"";
								 $Capital            = (isset($csvRow["Capital"]            ))?trim($stringFilter->filter($csvRow["Capital"]),"-")        :0;
								 
								 if(!$strNotEmptyValidator->isValid($NomDemandeur) || !$strNotEmptyValidator->isValid($DateDemande) || !$strNotEmptyValidator->isValid($NomCommercial) || !$strNotEmptyValidator->isValid($DateNaissance)) {
								     continue;
								 }
                                 if( strlen($NumeroRCCM) > 14 ) {
									 continue;
								 }
								 if( $NomCommercial=="NEANT" ) {
									 $NomCommercial = "";
								 }
								 if( $NomDemandeur=="NEANT" ) {
									 $NomDemandeur = "";
								 }
								 $NumeroRCCM                                = $model->normalizeNum($NumeroRCCM,$annee, $localite );
								 if( trim(substr($NumeroRCCM, 9, 1)) != "B") {
									 continue;
								 }
								 $registreData["numero"]                    = $NumeroRCCM;
								 $registreData["numparent"]                 = "";
								 $registreData["type_modification"]         = 0;
								 $registreData["capital"]                   = $Capital;
								 $Adresse                                   = $AdressePostale;
								 $dbAdapter->delete("rccm_registre_indexation", array("numero=\"".$NumeroRCCM."\""));
								 $searchInDbSql                             = "SELECT * FROM rccm_registre_indexation WHERE numero=\"".$NumeroRCCM."\"";
						         $contentRegistre                           = $dbAdapter->fetchRow( $searchInDbSql, array(), 5);
								 $namesToArray                              = preg_split("/[\s]+/", $NomDemandeur);
								 if( isset($namesToArray[0]) ) {
									 $registreData["nom"]                   = $namesToArray[0];
									 unset($namesToArray[0]);
								 }
								 if(!empty($namesToArray) ) {
									 $registreData["prenom"]                = implode(" ", $namesToArray);
								 }
								 $countryAssocArray                         = Sirah_Functions_ArrayHelper::search($countries, $Pays );
								 if( count( $countryAssocArray )) {
									 $registreData["nationalite"]           = key( $countryAssocArray );
								 } else {
									 $registreData["nationalite"]           = "";
								 }
								 if( $strNotEmptyValidator->isValid( $AdressePhysique ) ) {
									 $Adresse                               = $Adresse . $AdressePhysique; 
								 }
								 if( $strNotEmptyValidator->isValid( $Quartier ) ) {
									 $Adresse                               = $Adresse . " Quartier : " . $Quartier; 
								 }
								 if( $strNotEmptyValidator->isValid( $Parcelle ) ) {
									 $Adresse                               = $Adresse . " Parcelle : " . $Parcelle . " Section : ".$Section . " Lot : ". $Lot; 
								 }
								 if( $contentRegistre ) {									 
									 $registreData["nom_commercial"]        = ($strNotEmptyValidator->isValid($NomCommercial     ))? $NomCommercial      : $contentRegistre->nom_commercial;
									 $registreData["date_enregistrement"]   = ($strNotEmptyValidator->isValid($DateDemande       ))? $DateDemande        : $contentRegistre->date_enregistrement;
									 $registreData["date_naissance"]        = ($strNotEmptyValidator->isValid($DateNaissance     ))? $DateNaissance      : $contentRegistre->date_naissance;
									 $registreData["lieu_naissance"]        = ($strNotEmptyValidator->isValid($LieuNaissance     ))? $LieuNaissance      : $contentRegistre->lieu_naissance;
									 $registreData["description"]           = ($strNotEmptyValidator->isValid($ArticlePrincipale))? $ArticlePrincipale : $contentRegistre->description;
									 $registreData["telephone"]             = ($strNotEmptyValidator->isValid($Telephone         ))? $Telephone          : $contentRegistre->telephone;
									 $registreData["adresse"]               = ($strNotEmptyValidator->isValid($Adresse           ))? $Adresse            : $contentRegistre->adresse;
									 $registreData["sexe"]                  =  $contentRegistre->sexe;
									 $registreData["passport"]              =  $contentRegistre->passport;
									 $registreData["situation_matrimonial"] =  $contentRegistre->situation_matrimonial;
									 $registreData["capital"]               =  $contentRegistre->capital;
									 $registreData["nationalite"]           = ($strNotEmptyValidator->isValid($registreData["nationalite"]))? $registreData["nationalite"] : $contentRegistre->nationalite;
								 } else {
									 $registreData["nom_commercial"]        = $NomCommercial;
									 $registreData["date_enregistrement"]   = $DateDemande  ;
									 $registreData["date_naissance"]        = $DateNaissance;
									 $registreData["lieu_naissance"]        = $LieuNaissance;
									 $registreData["description"]           = $ArticlePrincipale;
									 $registreData["telephone"]             = $Telephone;
									 $registreData["adresse"]               = $Adresse;
									 $registreData["sexe"]                  = "";
									 $registreData["passport"]              = "";
									 $registreData["situation_matrimonial"] = "Célibataire";
									 $registreData["nationalite"]           = ($strNotEmptyValidator->isValid($registreData["nationalite"]))? $registreData["nationalite"] : "BF";
								 }
                                 if( $contentRegistre ) {
									 unset($registreData["numero"]);
									 if( $dbAdapter->update( $tablePrefix   ."rccm_registre_indexation", $registreData, "numero='".$NumeroRCCM."'")) {
										 $rccmSaved[]                       = $NumeroRCCM;
									 }
								 } else {
									     $dbAdapter->delete( $tablePrefix   ."rccm_registre_indexation", "numero='".$NumeroRCCM."'");
									 if( $dbAdapter->insert( $tablePrefix   ."rccm_registre_indexation", $registreData)) {
										 $rccmSaved[]                       = $NumeroRCCM;
									 }
								 }									 
						}
					}  else {
						             $errorMessages[]                       = "Ce fichier CSV ne contient aucune ligne valide";
					}
				} else {
					                 $errorMessages[]                       = "Le fichier que vous avez selectionné  ne semble pas valide";
				}
			} else {
				                     $errorMessages[]                       = "Veuillez renseigner un fichier CSV valide";
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
			} else {
				$nextYear         = $annee + 1;
				$this->setRedirect(sprintf("Votre opération a permis d'introduire %d RCCMs dans la base de données", count($rccmSaved) ), "success");
				$this->redirect("admin/registremoral/importdocubasecsv/annee/".$nextYear);
			}				
		}
		$this->view->data         = $defaultData;
		$this->view->annees       = $annees;
	    $this->view->localites    = $localites;
		
		$this->render("docubasecsv");	
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
			$srcPath              = (isset($postData["srcpath"]       ))? $postData["srcpath"]        : "F:\\FNRCCM2017-2018/DOCSCAN/COMBINE";
			$destPath             = (isset($postData["destpath"]      ))? $postData["destpath"]       : "F:\\FNRCCM2017-2018/DOCSCAN/DEST";
			$checkPath            = (isset($postData["checkpath"]     ))? $postData["checkpath"]      : "G:\\ERCCM";
			$annee                = (isset($postData["annee"]         ))? intval($postData["annee"])  : 2000;
			$localite             = (isset($postData["localite"]      ))? $postData["localite"]       : "OUA";
			$nbreDocuments        = (isset($postData["nbre_documents"]))? intval( $postData["nbre_documents"] ) : 1000;
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
			$rccmSearchKey        = sprintf("BF%s%dB", $localite, $annee);
			$rccmPSFiles          = glob( $documentSrcRootPath."/*/".$rccmSearchKey."*-PS.pdf");
			$i                    = 0;
			if( count(   $rccmPSFiles ) ) {
				foreach( $rccmPSFiles as $rccmPSFile) {
						 $csvRowData                             = array();
					     $numRccm                                = $numero = str_ireplace(array("-FR","-ST",".pdf","-PS"),"", basename($rccmPSFile));
						 $checkRccmRow                           = $model->findRow( $numRccm, "numero", null, false );
						 $checkIndexationFiles                   = (is_dir($checkPath))?glob($checkPath."/*/".$localite."/".$annee."/".$numRccm.".pdf" ) : array();
						 $checkinMissingPath                     = "G:\\MISSINGS". DS . $localite. DS. $annee . DS .  $numRccm . DS . $numRccm."-PS.pdf" ;
						 if( file_exists($checkinMissingPath) ) {
							 continue;
						 }
						 if($i===$nbreDocuments ) {
							 break;
						 }
						 if( count($checkIndexationFiles) ) {
							 //$errorMessages[]                    = sprintf("Le numéro RCCM %s existe déjà", $numRccm);
							 continue;
						 }
						 if( $checkRccmRow ) {
							 //$errorMessages[]                    = sprintf("Le numéro RCCM %s existe déjà", $numRccm);
							 continue;
						 }
                         if( file_exists($documentDestRootPath . DS . $numRccm.".pdf") ) {
							 //$errorMessages[]                    = sprintf("Le numéro RCCM %s existe déjà", $numRccm);
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
                            $csvRowData["entreprise_capital"]    = $contentRegistre->capital;							
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
										$csvRowData["entreprise_capital"]    = 0;
										$csvRowData["nom_commercial"]        = strtoupper($nomCommercial);
										$csvRowData["description"]           = strtoupper(trim(str_replace(array("TELEPHONE",":","NOM COMMERCIAL","","DEMINATION","RCCM","DIRIGEANT",$numero,$searchNum2,$searchNum3,
																						  $searchNum,$telToReplace,$lastname,$firstname,$nomCommercial,$telephone),"", $registreStr)));
								}
							}
						 }
                         $csvRowData["entreprise_adresse"]                   = $csvRowData["adresse"];
						 $csvRowData["entreprise_telephone"]                 = $csvRowData["telephone"];
                         if( empty($csvRowData["nationalite"] ) ) {
							 $csvRowData["nationalite"]              = "BF";
						 }                       				 
						 //On copie le fichier dans la destination
						 $documentFilename                                   = $documentDestRootPath . DS . $numRccm.".pdf"; 
						 if( true == copy( $rccmPSFile, $documentFilename ) ) {
							 $csvRows[$numRccm]                      = $csvRowData;
                             $i++;							 
						 }
				}
			}
			//print_r($csvRows);die();
			if( count( $csvRows )) {
				$csvHeader   = array("numero","nom_commercial","date_enregistrement","description","nom","prenom","lieu_naissance","date_naissance","sexe","passport","adresse","telephone","nationalite","situation_matrimonial","entreprise_adresse","entreprise_telephone","entreprise_capital");
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
	
	protected function __checkRccmFiles($rccmFilesInfos = array(), &$errorMessages)
	{
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		$result    = true;
		
		if(!isset($rccmFilesInfos["formulaire"]) || !isset($rccmFilesInfos["personnel"]) || !isset($rccmFilesInfos["statut"])) {
			return false;
		}
		if(!file_exists($rccmFilesInfos["formulaire"]) || !file_exists($rccmFilesInfos["personnel"]) || !file_exists($rccmFilesInfos["statut"])) {
			return false;
		}
		$formulaireFilePath        = $rccmFilesInfos["formulaire"];
		$completFilePath           = $rccmFilesInfos["personnel"];
		$statutFilePath            = $rccmFilesInfos["statut"];
		try{
			 $pdfRegistre          = new FPDI();
			 $pagesFormulaire      = (file_exists($formulaireFilePath)) ? $pdfRegistre->setSourceFile( $formulaireFilePath ) : 0;
			 $pagesComplet         = (file_exists($completFilePath   )) ? $pdfRegistre->setSourceFile( $completFilePath    ) : 0;
			 $pagesStatut          = (file_exists($statutFilePath    )) ? $pdfRegistre->setSourceFile( $statutFilePath     ) : 0;
		} catch(Exception $e ) {
			/*$errorMessages[]       = sprintf("Le fichier %s ou %s est invalide", $completFilePath, $formulaireFilePath);
			$result                = false;*/
			$pagesFormulaire       = $pagesComplet = $pagesStatut = 0;
			return true;
		}
		if( $pagesFormulaire && ( $pagesComplet < $pagesFormulaire )) {
			$errorMessages[]       = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages",$numRccm);
		    $result                = false;
		}
		if( $pagesStatut && ( $pagesComplet < $pagesStatut )) {
			$errorMessages[]       = sprintf("Le statut du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages",$numRccm);
		    $result                = false;
		}
		if( file_exists( $formulaireFilePath)) {
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
		}
		return $result;
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
}