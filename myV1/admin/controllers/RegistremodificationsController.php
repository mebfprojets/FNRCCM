<?php
require 'vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use XPDF\PdfToText;
use \ForceUTF8\Encoding;
/*use setasign\Fpdi\Fpdi;
  use setasign\Fpdi\PdfReader;*/
require_once("tcpdf/tcpdf.php");
require_once("Fpdi/fpdi.php");
 

class Admin_RegistremodificationsController extends Sirah_Controller_Default
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
		$annee                        = intval($this->_getParam("annee", 0));
 
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
																  ->join( array("M" => $tablePrefix."rccm_registre_modifications"),"M.registreid=R.registreid"          , array("typeModification"=>"M.type","M.activite_actuel","M.activite_suppr","M.activite_ajout"))
																  ->order(array("R.annee ASC","R.registreid ASC"));				
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
									if( isset($postData["article_actuel"])) {
										$modification_data                           = array();
										$modification_data["registreid"]             = $registreid;
										$modification_data["article_actuel"]         = (isset($postData["article_actuel"]) && !empty($postData["article_actuel"])) ? $stringFilter->filter( $postData["article_actuel"]) : $registre_data["description"];
										$modification_data["article_suppr"]          = (isset($postData["article_suppr"] ) && !empty($postData["article_suppr"] )) ? $stringFilter->filter( $postData["article_suppr"])  : "";
										$modification_data["article_ajout"]          = (isset($postData["article_ajout"] ) && !empty($postData["article_ajout"] )) ? $stringFilter->filter( $postData["article_ajout"])  : "";
										$modification_data["type"]                   = (isset($postData["typeModification"]))? intval( $postData["typeModification"]  ) : 7;
										$modification_data["creationdate"]           = $insertData["creationdate"];		
										$modification_data["creatorid"]              = $insertData["creatorid"];
										$modification_data["updateduserid"]          = (isset($postData["updateduserid"]   ))? intval($postData["updateduserid"]) : 0;
										$modification_data["updatedate"]             = (isset($postData["updatedate"]         ))? intval($postData["updatedate"])    : 0;	
										
										$insert_modification                         = array_intersect_key( $modification_data, $emptyModificationData );
										$dbAdapter->delete(     $prefixName."rccm_registre_modifications", array("registreid=?"=>$registreid));
										if(!$dbAdapter->insert( $prefixName."rccm_registre_modifications", $insert_modification)) {
											$errorMessages[$registreid]              = sprintf("La modification du RCCM N° %s ID %d n'a pas pu être enregistrée", $insertData["numero"],$registreid);
										    continue;
										}
									}
									/*$registreDirigeants                              = $this->getDirigeants($registreid,$dbSourceAdapter,$prefixName);
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
		$this->view->title     = "ERCCM:Historique des RCCM de type Modifications"  ;
		
		$model                 = $this->getModel("registremodification");
		$modelLocalite         = $this->getModel("localite");
		$modelDomaine          = $this->getModel("domaine");
		$modelModificationType = $this->getModel("modificationtype");
		$me                    = Sirah_Fabric::getUser();
		
		$registres             = $errorMessages = array();
		$paginator             = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags() );
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"  => null,"numero"=> null,"domaineid" => 0,"creatorid"=> 0,"localiteid"=> 0,"annee" => 0,"nom" => null, "prenom" => null,"searchQ" => null,
		                              "date_year"=> null,"date_month" => null,"date_day" => null,"periode_start_year" => DEFAULT_START_YEAR,"country" => null,"sexe" => null,
				                      "periode_end_year"=> DEFAULT_END_YEAR,"periode_start_month"=> DEFAULT_START_MONTH,"periode_start_day"=> DEFAULT_START_DAY,"type"=> null,
				                      "periode_end_day" => DEFAULT_END_DAY ,"periode_end_month"  => DEFAULT_END_MONTH,"passport"=>null,"telephone"=>null, "parentid" => 0);		
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
				$filters["nom"]       = (isset($nameToArray[0])) ? $nameToArray[0] : "" ;
				$filters["prenom"]    = (isset($nameToArray[1])) ? $nameToArray[1] : "" ;
			} elseif( count($nameToArray) == 1)	 {
				$filters["name"]      = (isset($nameToArray[0])) ? $nameToArray[0] : "" ;
			}				
		}
		if( !$me->isAdmin() && !$me->isOPS()) {
			 $filters["localiteid"]= $me->city;
		}
		if( $me->isOPS() ) {
			$filters["creatorid"] = $me->userid;
		}
        if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"] ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_month"])) &&
			(isset( $filters["periode_end_day"]  ) && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart                = new Zend_Date(array("year"=> $filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]  ));
			$zendPeriodeEnd                  = new Zend_Date(array("year"=> $filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"] ));
			$filters["periode_start"]        = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP)  : 0;
			$filters["periode_end"]          = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP)  : 0;
		}	
        try {
			$registres                       = $model->getList( $filters , $pageNum , $pageSize);
		    $paginator                       = $model->getListPaginator($filters);
		} catch(Exception $e ) {
			$errorMessages[]                 = sprintf("Une erreur technique s'est produite : %s", $e->getMessage());
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
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns                 = array("left");
		$this->view->registres               = $registres;
		$this->view->domaines                = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activités", array("domaineid", "libelle") , array() , null , null , false );
		$this->view->localites               = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users                   = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->filters                 = $filters;
		$this->view->paginator               = $paginator;
		$this->view->pageNum                 = $pageNum;
		$this->view->pageSize                = $this->view->maxitems = $pageSize;	
        $this->view->types                   = $modelModificationType->getSelectListe("Selectionnez le type", array("type","libelle"), array() , 0 , null , false);		
	}
	
	public function createAction()
	{
		$this->view->title                                = "Enregistrer un registre de type `Modification`";
		
		$model                                            = $this->getModel("registre");
		$modelDomaine                                     = $this->getModel("domaine");
		$modelModification                                = $this->getModel("registremodification");
		$modelRepresentant                                = $this->getModel("representant");
		$modelLocalite                                    = $this->getModel("localite");
		$modelModificationType                            = $this->getModel("modificationtype");
		$me                                               = Sirah_Fabric::getUser();
		
        $domaines                                         = $modelDomaine->getSelectListe(  "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);		
		$modificationTypes                                = $modelModificationType->getSelectListe("Selectionnez le type"       , array("type"      , "libelle"), array() , 0 , null , false);
		$localites                                        = $modelLocalite->getSelectListe( "Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                                   = $modelLocalite->getSelectListe( "Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);
		
		$errorMessages                                    = array();
		$registreid                                       = 0;
		$parentid                                         = intval(    $this->_getParam("parentid"    , 0   ));
		$parentNumRc                                      = strip_tags($this->_getParam("parentnum"   , null));
		$newCreation                                      = intval(    $this->_getParam("new_creation", 0   ));
		
		$parent                                           = (!empty($parentNumRc))? $model->findRow($parentNumRc,"numero", null, false):(($parentid)? $model->findRow($parentid,"registreid",null,false) : null);
		$parentid                                         = ( $parent ) ? $parent->registreid      : 0;
		$parentNumRc                                      = ( $parent ) ? $parent->numero          : null;
		$representants                                    = $dirigeants = ( $parent ) ? $parent->dirigeants()    : array();
		$modifications                                    = ( $parent ) ? $parent->modifications() : array();			
		$representantid                                   = (isset($representants[0]["representantid"]))? intval($representants[0]["representantid"]) : 0;		
		$registreDefaultData                              = ( $parent ) ? $parent->toArray()       : $model->getEmptyData();
        $registreDefaultData["numero"]		              = null;
		$registreDefaultData["registreid"]                = 0;
		$modificationDefaultData                          = (isset( $modifications[0]["registreid"]      )) ? $modifications[0] : $modelModification->getEmptyData();
		$representantDefaultData                          = (isset( $representants[0]["representantid"]  )) ? $representants[0] : $modelRepresentant->getEmptyData();		
				
		$registreDefaultData["telephone"]                 = (isset( $representantDefaultData["telephone"])) ? $representantDefaultData["telephone"]            : "";
		$registreDefaultData["adresse"]                   = (isset( $representantDefaultData["adresse"]  )) ? $representantDefaultData["adresse"]              : "";
		$defaultData                                      =  array_merge( $representantDefaultData , $modificationDefaultData , $registreDefaultData);
		$defaultData["date_year"]                         = (intval($defaultData["date"]                 )) ? date("Y", intval($defaultData["date"]))          : intval($this->_getParam("annee", $me->getParam("default_year", DEFAULT_YEAR)));		
		$defaultData["date_month"]                        = (intval($defaultData["date"]                 )) ? date("m", intval($defaultData["date"]))          : sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));
		$defaultData["date_day"]                          = (intval($defaultData["date"]                 )) ? date("d", intval($defaultData["date"]))          : null;
		
		if( Zend_Date::isDate( $defaultData["datenaissance"], "YYYY-MM-DD H:i:s" ) ) {
			$zendDateNaissance                            = new Zend_Date( $defaultData["datenaissance"] , "YYYY-MM-DD H:i:s");
			$defaultData["date_naissance_year"]           = ( $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::YEAR)  : 0;
            $defaultData["date_naissance_month"]          = ( $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::MONTH) : 0;
            $defaultData["date_naissance_day"]            = ( $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::DAY)   : 0;				
		} else {
			$defaultData["date_naissance_year"]           = 0;
		    $defaultData["date_naissance_month"]          = "01";
		    $defaultData["date_naissance_day"]            = "01";
		}	
         			
		$defaultData["parentid"]             = $parentid;
		$defaultData["type"]                 = 7;
		$defaultData["country"]              = (!empty( $defaultData["country"]           )) ? $defaultData["country"] : "BF";
		$defaultData["check_documents"]      = $checkDocuments = intval($this->_getParam("check_documents", 0));
		$defaultData["find_documents"]       = DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
		$defaultData["find_documents_src"]   = $fileSource     = (is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
        if( empty( $defaultData["article_actuel"] ) && !empty($defaultData["description"])) {
			$defaultData["article_actuel"]   = $defaultData["description"];
		}
        if( empty( $defaultData["article_ajout"] ) && !empty($defaultData["description"] )) {
			$defaultData["article_ajout"]    = $defaultData["description"];
		}		
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$registre_data                   = array_merge( $registreDefaultData    , array_intersect_key($postData, $registreDefaultData     ));
			$modification_data               = array_merge( $modificationDefaultData, array_intersect_key($postData, $modificationDefaultData ));
			$representant_data               = array_merge( $representantDefaultData, array_intersect_key($postData, $representantDefaultData ));			
			$modelTable                      = $model->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
			$tableName                       = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(          new Zend_Filter_StringTrim());
			$stringFilter->addFilter(          new Zend_Filter_StripTags() );
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($registre_data["numero"])));
			$registre_data["libelle"]        = $libelle     = (isset($postData["libelle"]    ))? $stringFilter->filter($postData["libelle"])     : "";
			$registre_data["description"]    = $description = (isset($postData["description"]))? $stringFilter->filter($postData["description"]) : "";
			$registre_data["domaineid"]      = (isset($postData["domaineid"]       ))? intval($postData["domaineid"])  : (($parent)?$parent->domaineid  : 0);
			$registre_data["localiteid"]     = (isset($postData["localiteid"]      ))? intval($postData["localiteid"]) : (($parent)?$parent->localiteid : 0);
			$findDocuments                   = (isset($postData["find_documents"]  ))? intval($postData["find_documents"])  : 0;
			$checkDocuments                  = (isset($postData["check_documents"] ))? intval($postData["check_documents"]) : 0;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;								
			$checkRCLibRow                   = $model->findRow( $libelle , "libelle" , null , false );
			$checkRCType                     = ( $checkRCLibRow ) ? $checkRCLibRow->type  : 0;
			if(!is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}
			/*
			if(!$parent )  {
				$errorMessages[]             = "Veuillez sélectionner le registre de commerce principal";
			} */
			if(!$modelModification->checkNum($numero )) {
				$errorMessages[]             = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false )) {
				$errorMessages[]             = sprintf("Un registre existant porte le numéro %s , veuillez entrer un numéro différent", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle ) && $parent ) {
				$libelle                     = sprintf("MODIFICATION DU RC N° %s", $parent->numero);
		    } elseif(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]             = "Veuillez renseigner le titre de la modification";
			} 
			if( $checkRCType === 4 ) {
				$libelle                     = ( $parent ) ? sprintf("RC n° %s : %s", $parent->numero, $libelle ) : sprintf("RC n° %s : %s", $numero, $libelle ) ;
			} else {
				$registre_data["libelle"]    = $libelle;
			}
			if((!$strNotEmptyValidator->isValid( $representant_data["nom"] ) || !$strNotEmptyValidator->isValid( $representant_data["prenom"])) && !count($dirigeants)) {
				$errorMessages[]             = " Veuillez entrer un nom de famille et/ou prénom valide pour le promoteur";
			} elseif((!$strNotEmptyValidator->isValid( $representant_data["nom"] ) || !$strNotEmptyValidator->isValid( $representant_data["prenom"])) && count($dirigeants)) {
			    $representantid              = $dirigeants[0]["representantid"];
			}				
			if((!intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]])) && !$parent ) {
				$errorMessages[]             = "Veuillez sélectionner une localité valide";
			} elseif((!intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]])) && $parent ) {
				$registre_data["localiteid"] = $parent->localiteid;
		    } else {
				$registre_data["localiteid"] = intval( $registre_data["localiteid"] ) ;
			}
			$localiteCode                    = (isset($localitesCodes[$registre_data["localiteid"]])) ? $localitesCodes[$registre_data["localiteid"]] : "";	
			$dateYear                        = (isset($postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                       = (isset($postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                         = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
						 			
			$zendPeriodStart                 = new Zend_Date(array("year"=> $periodStartYear,"month" => $periodStartMonth,"day" => $periodStartDay));
			$zendPeriodEnd                   = new Zend_Date(array("year"=> $periodEndYear  ,"month" => $periodEndMonth  ,"day" => $periodEndDay));			
			$zendDate                        = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			
			$numeroParts                     = $model->getNumParts($numero);
			$registreYear                    = (isset($numeroParts["annee"]))?intval($numeroParts["annee"]) : substr( $numero, 5, 4);
            $numeroPrefixToCheck             = sprintf("BF%s", $localiteCode);	
            /*
			if( substr( $numero, 0, 5) != $numeroPrefixToCheck ) {
				$errorMessages[]             = "Le numéro attribué à ce registre n'est pas valide.";
			}							
						
			 if(stripos($numero, $numeroPrefixToCheck) === FALSE) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il devrait commencer par %s", $numero, $numeroPrefixToCheck);
			}
            if(strlen($registre_data["numero"]) != 14) {
				$errorMessages[]             = sprintf("La modification numéro %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $registre_data["numero"] );
			}*/
			$registre_data["date"]                = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
            $registre_data["annee"]               = (isset($postData["annee"]))? intval($postData["annee"])  : (($zendDate)?$zendDate->get(Zend_Date::YEAR) : 0);
            if(!intval($registre_data["date"])) {
				$errorMessages[]                  = "Veuillez indiquer une date d'inscription valide";
			}
			$registre_data["type"]                = 4;
			$registre_data["statut"]              = 2;
			$registre_data["category"]            = sprintf("M%d", count($modifications));
			$registre_data["checked"]             = intval($checkDocuments);
			$registre_data["description"]         = (isset($postData["article_actuel"]) && !empty($postData["article_actuel"])) ? $stringFilter->filter( $postData["article_actuel"]) : $registre_data["description"];
			$registre_data["adresse"]             = (isset($postData["adresse"]           ))? $stringFilter->filter($postData["adresse"])     : (($parent)?$parent->adresse     : "");
			$registre_data["telephone"]           = (isset($postData["telephone"]         ))? $stringFilter->filter($postData["telephone"])   : (($parent)?$parent->telephone   : "");
			$registre_data["capital"]             = (isset($postData["capital"]           ))? floatval($postData["capital"])   : (($parent)?$parent->capital           : 0);
			$registre_data["capital_nature"]      = (isset($postData["capital_nature"]    ))? floatval($postData["capital_nature"])   : (($parent)?$parent->capital_nature    : 0);
			$registre_data["capital_numeraire"]   = (isset($postData["capital_numeraire"] ))? floatval($postData["capital"])   : (($parent)?$parent->capital_numeraire : 0);
			$registre_data["nbactions"]           = (isset($postData["nbactions"]         ))? floatval($postData["nbactions"]) : (($parent)?$parent->nbactions         : 0);
			$registre_data["addressid"]           = ($parent)?$parent->addressid   : 0;
			$registre_data["communeid"]           = ($parent)?$parent->communeid   : 0;
			$registre_data["ifuid"]               = ($parent)?$parent->ifuid       : 0;
			$registre_data["numifu"]              = (isset($postData["numifu"]   ))? $stringFilter->filter($postData["numifu"] ) : (($parent)?$parent->numifu   : 0);
			$registre_data["cnssid"]              = ($parent)?$parent->cnssid      : 0;
			$registre_data["numcnss"]             = (isset($postData["numcnss"]  ))? $stringFilter->filter($postData["numcnss"]) : (($parent)?$parent->numcnss  : 0);
			$registre_data["cpcid"]               = 0;
			$registre_data["statusid"]            = 0;
			$registre_data["creatorid"]           = $me->userid;
			$registre_data["creationdate"]        = time();	
			$registre_data["updateduserid"]       = 0;
			$registre_data["updatedate"]          = 0;
			
			
			$modification_data["article_actuel"]  = (isset($postData["article_actuel"]) && !empty($postData["article_actuel"])) ? $stringFilter->filter( $postData["article_actuel"]) : $registre_data["description"];
			$modification_data["article_suppr"]   = (isset($postData["article_suppr"] ) && !empty($postData["article_suppr"] )) ? $stringFilter->filter( $postData["article_suppr"])  : "";
			$modification_data["article_ajout"]   = (isset($postData["article_ajout"] ) && !empty($postData["article_ajout"] )) ? $stringFilter->filter( $postData["article_ajout"])  : "";
			$modification_data["type"]            = (isset($postData["type"]  )) ? intval( $postData["type"]  ) : 7;
			$modification_data["creationdate"]    = time();	
			$modification_data["creatorid"]       = $me->userid;
			$modification_data["updateduserid"]   = 0;
			$modification_data["updatedate"]      = 0;
			
			if(!intval( $modification_data["type"] ) || !isset( $modificationTypes[$modification_data["type"]])) {
				$errorMessages[]                  = "Veuillez indiquer un type de modification valide";
			}							
			if(!$findDocuments ) {
				$documentsUploadAdapter           = new Zend_File_Transfer();
			    $documentsUploadAdapter->addValidator("Count"    , false , 3);
			    $documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			    $documentsUploadAdapter->addValidator("Size"     , false , array("max" => DEFAULT_UPLOAD_MAXSIZE));
			    $documentsUploadAdapter->addValidator("FilesSize", false , array("max" => DEFAULT_UPLOAD_MAXSIZE));
			    if(!$documentsUploadAdapter->isUploaded("docmini") ) {
				    $errorMessages[]         = "Le document formulaire n'a pas été fourni";
			    }
			    if(!$documentsUploadAdapter->isUploaded("docoriginal")) {
				    $errorMessages[]         = "Le document  personnel n'a pas été fourni";
			    }
				if( $checkDocuments ) {
					$formulaireFilename      = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename       = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;	
					$checkRccmData           = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, "numero" => $numero);
					if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
						$errorMessages[]     =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
					}
				}
			} else {				
				$rccmFormulaireFilepath      = $filesSource.DS. $localiteCode.DS.$registreYear. DS . $numero. DS. $numero."-FR.pdf";
			    $rccmPersonnelFilepath       = $filesSource.DS. $localiteCode.DS.$registreYear. DS . $numero. DS. $numero."-PS.pdf";
				
				if(!file_exists( $rccmFormulaireFilepath )) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire de la modification N° %s n'existe pas dans la source des documents %s", $numero, $rccmFormulaireFilepath);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier de la modification N° %s n'existe pas dans la source des documents %s", $numero, $rccmPersonnelFilepath);
				}
				if( $checkDocuments ) {
					$checkRccmData           = array("formulaire" => $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,"numero" => $numero);
					if((false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) && $checkDocuments) {
						$errorMessages[]     =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents %s", $numero, $rccmPersonnelFilepath);
					}
				}				
			}				
			if(!count($errorMessages  )) {
				$emptyData                   = $model->getEmptyData();
				$clean_registre_data         = array_intersect_key($registre_data,$emptyData);
				if( $dbAdapter->insert( $tableName, $clean_registre_data) ) {
					$registreid              = $dbAdapter->lastInsertId();
                    $defaultParams           = array("default_start_month"=> $dateMonth,"default_year" => $dateYear,"default_localiteid" =>  $registre_data["localiteid"],
					                                 "default_domaineid"  => $registre_data["domaineid"],"default_find_documents_src" => DEFAULT_FIND_DOCUMENTS_SRC, 
										             "default_check_documents"=> DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY, "default_find_documents" => DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY);
                    $myParams                = $me->getParams();
                    $myPreferedParams        = array_merge( $myParams, $defaultParams);				  
					$me->setParams( $myPreferedParams );  					  
					$modification_data["registreid"]              = $registreid;
					$emptyModificationData                        = $modelModification->getEmptyData();
					$insert_modification                          = array_intersect_key( $modification_data, $emptyModificationData );
					if( $dbAdapter->insert( $prefixName."rccm_registre_modifications", $insert_modification )) {
						if( $parent ) {
							$parent->statut                       = 2;
							$parent->updatedate                   = time();
				            $parent->updateduserid                = $me->userid;
				            $parent->save();
						}
                        if( isset($representant_data["representantid"])) {
							unset($representant_data["representantid"]);
						}						
					        $dateNaissanceYear                    = (isset($postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
					        $dateNaissanceMonth                   = (isset($postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
					        $dateNaissanceDay                     = (isset($postData["date_naissance_day"]) && ($postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
					  
					        $representant_data["datenaissance"]   = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					        $representant_data["lieunaissance"]   = $stringFilter->filter( $representant_data["lieunaissance"]  );
					        $representant_data["marital_status"]  = $stringFilter->filter( $representant_data["marital_status"] );
					        $representant_data["nom"]             = $stringFilter->filter( $representant_data["nom"]            );
					        $representant_data["prenom"]          = $stringFilter->filter( $representant_data["prenom"]         );
					        $representant_data["adresse"]         = $stringFilter->filter( $representant_data["adresse"]        );
					        $representant_data["city"]            = 0;
					        $representant_data["profession"]      = ( isset( $postData["fonction"])) ? $stringFilter->filter($postData["fonction"]): "GERANT";
					        $representant_data["country"]         = $stringFilter->filter( $representant_data["country"]        );
					        $representant_data["email"]           = $stringFilter->filter( $representant_data["email"]          );
					        $representant_data["telephone"]       = $stringFilter->filter( $representant_data["telephone"]);
					        $representant_data["passport"]        = $stringFilter->filter( $representant_data["passport"] );
							$representant_data["cnib"]            = (isset($postData["cnib"]))? $stringFilter->filter($postData["cnib"]) : $stringFilter->filter( $representant_data["passport"] );
					        $representant_data["sexe"]            = $stringFilter->filter( $representant_data["sexe"]     );
					        $representant_data["structure"]       = "";
					        $representant_data["creatorid"]       = $me->userid;
					        $representant_data["creationdate"]    = time();
					        $representant_data["updateduserid"]   = 0;
					        $representant_data["updatedate"]      = 0;
						if((!$representantid &&  $strNotEmptyValidator->isValid( $representant_data["nom"]) && $strNotEmptyValidator->isValid( $representant_data["prenom"])) ||
						   ( $representantid && ($representant_data["nom"] != $representantDefaultData["nom"]) && ($representant_data["prenom"] != $representantDefaultData["prenom"]))
						  ) {			
                            $cleanRepresentantData                = array_intersect_key($representant_data, $modelRepresentant->getEmptyData());						  
							if( $dbAdapter->insert( $prefixName . "rccm_registre_representants", $cleanRepresentantData) ) {
					  	        $representantid                   = $dbAdapter->lastInsertId();
								$dirigeant_data                   = array();
								$dirigeant_data["registreid"]     = $registreid;
								$dirigeant_data["representantid"] = $representantid;
								$dirigeant_data["fonction"]       = $representant_data["profession"];
								$dbAdapter->insert( $prefixName   . "rccm_registre_dirigeants", $dirigeant_data );
							}
						} elseif( $representantid && ($representant_data["nom"] == $representantDefaultData["nom"]) && ($representant_data["prenom"] == $representantDefaultData["prenom"]) ) {						          
								  $cleanRepresentantData  = array_intersect_key($representant_data, $modelRepresentant->getEmptyData());
								  $dbAdapter->update( $prefixName . "rccm_registre_representants" , $cleanRepresentantData, "representantid=".$representantid) ;
								  $dbAdapter->delete( $prefixName . "rccm_registre_dirigeants"    , "registreid=".$registreid);
								  $dirigeant_data                   = array();
								  $dirigeant_data["registreid"]     = $registreid;
								  $dirigeant_data["representantid"] = $representantid;
								  $dirigeant_data["fonction"]       = $representant_data["profession"];
								  $dbAdapter->insert( $prefixName."rccm_registre_dirigeants", $dirigeant_data );
						}							
                        if(!$findDocuments ) {
					  	  	$modelDocument                        = $this->getModel("document");					  	  	       
					  	  	$rcPathroot                           = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear. DS . $numero;
					  	  	  
					  	  	$documentData                         = array();
					  	  	$documentData["userid"]               = $me->userid;
					  	  	$documentData["category"]             = 1;
					  	  	$documentData["resource"]             = "registremodifications";
					  	  	$documentData["resourceid"]           = 0;
					  	  	$documentData["filedescription"]      = $registre_data["description"];
					  	  	$documentData["filemetadata"]         = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	  	$documentData["creationdate"]         = time();
					  	  	$documentData["creatoruserid"]        = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	  	$formulairePath                       = $rcPathroot . DS . $numero."-FR.pdf";
					  	  	$personnelPath                        = $rcPathroot . DS . $numero."-PS.pdf";
					  	  	$documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath, "overwrite" => true), "docmini");
							
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS". DS . $localiteCode . DS . $registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode, 0777 );
								}
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $registreYear);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $registreYear, 0777 );									   
						    }
							if(!is_dir($rcPathroot)) {
								@chmod($rcPathroot, 0777 );
								@mkdir($rcPathroot);
							}					  	  	  
					  	  	if(!$documentsUploadAdapter->isUploaded("docmini") ) {
					  	  	  	$errorMessages[]            = "Le formulaire n'a pas été fourni ";
					  	  	} else {
					  	  	  	$documentsUploadAdapter->receive("docmini");
					  	  	  	if( $documentsUploadAdapter->isReceived("docmini") ) {
								    $miniFileSize                     = $documentsUploadAdapter->getFileSize("docmini");
								    $formulaireData                   = $documentData;
								    $formulaireData["filename"]       = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $numero);
								    $formulaireData["filepath"]       = $formulairePath;
								    $formulaireData["access"]         = 0 ;
								    $formulaireData["filextension"]   = "pdf";
								    $formulaireData["filesize"]       = floatval( $miniFileSize );
								    if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
									    $documentid                   = $dbAdapter->lastInsertId();
									    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
									    if( $parent ) {
										    $parentFormulaireData             = $formulaireData;
										    $parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $parent->numero);
										    if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
											   $parentDocumentid              = $dbAdapter->lastInsertId();
											   $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => -2 ));
										    }
									    }
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
					  	  	  		$personnelDocData["filename"]         = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $numero);
					  	  	  		$personnelDocData["filepath"]         = $personnelPath;
					  	  	  		$personnelDocData["access"]           = 6;
					  	  	  		$personnelDocData["filextension"]     = "pdf";
					  	  	  		$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
					  	  	  		if( $dbAdapter->insert($prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  			$documentid                       = $dbAdapter->lastInsertId();
					  	  	  			$dbAdapter->insert($prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid" => $documentid, "access" => 6));
										if( $parent ) {
											$parentPersonnelData              = $personnelDocData;
											$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
											if( $dbAdapter->insert($prefixName."system_users_documents", $parentPersonnelData )) {
											    $parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert($prefixName."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 4));
										    }
										}
					  	  	  		} else {
					  	  	  			$errorMessages[]                   = "Les informations du document personnel ont été partiellement enregistrées";
					  	  	  		}					  	  	  	
					  	  	  	} else {
					  	  	  		    $errorMessages[]                   = "Le document personnel n'a pas été copié sur le serveur pour les raisons suivantes: ".implode(", ", $documentsUploadAdapter->getMessages());
					  	  	  	}
					  	  	}
						} else {
				            $rccmFormulaireFilepath             = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS. $numero."-FR.pdf";
			                $rccmPersonnelFilepath              = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS. $numero."-PS.pdf";
							$rcPathroot                         = APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear.DS.$numero;
							if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
								$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
								$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
								$modelDocument                  = $this->getModel("document");					  	  	       					  	  	  
					  	  	    $documentData                   = array();
					  	  	    $documentData["userid"]         = $me->userid;
					  	  	    $documentData["category"]       = 1;
					  	  	    $documentData["resource"]       = "registremodification";
					  	  	    $documentData["resourceid"]     = 0;
					  	  	    $documentData["filedescription"]= $registre_data["description"];
					  	  	    $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	  	    $documentData["creationdate"]   = time();
					  	  	    $documentData["creatoruserid"]  = $me->userid;
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode . DS . $registreYear) ) {
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS");
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS", 0777 );
									}
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode)) {
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode);
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode, 0777 );
									}
									    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $registreYear);
									    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $registreYear, 0777 );									   
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
					  	  	  	   	    if( $parent ) {
											$parentFormulaireData             = $formulaireData;
											$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $parent->numero);
										    if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
												$parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => -2 ));
											}
										}
									} else {
					  	  	  	   	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	   	}
								} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la modification numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
								}
								if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	   	$personnelFileData                  = $documentData;
					  	  	  	   	$personnelFileData["filename"]      = $modelDocument->rename("FOND DE DOSSIER", $numero);
					  	  	  	   	$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	  	   	$personnelFileData["access"]        = 6;
					  	  	  	   	$personnelFileData["filextension"]  = "pdf";
					  	  	  	   	$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
					  	  	  	   	if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					  	  	  	   	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
					  	  	  	   	    if( $parent ) {
											$parentPersonnelData        = $personnelDocData;
											$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
											if( $dbAdapter->insert( $prefixName."system_users_documents", $parentPersonnelData )) {
											    $parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert( $prefixName."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 4));
										    }
										}
									} else {
					  	  	  	   	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	   	}
								} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la modification numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
								}
							} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la modification numéro %s a echoué car les documents n'ont pas été trouvés", $numero);
						    }
						}							
					}					  					  					  				  					  					 					  					  					 					
				}  else {
					    $errorMessages[]    = " Les informations du registre n'ont pas été enregistrées, veuillez reprendre l'opération";
				}
			} 
			$defaultData        = array_merge( $defaultData , $postData );
			if( empty( $errorMessages )) {
				if( $this->_request->isXmlHttpRequest()) {
				    $this->_helper->viewRenderer->setNoRender(true);
				    echo ZendX_JQuery::encodeJson(array("success" => "Les informations de la modifications ont été enregistrées avec succès"));
				    exit;
			    }
				$this->setRedirect("Les informations de la modifications ont été enregistrées avec succès" , "success");
				if( $newCreation ) {
					$this->redirect("admin/registremodifications/create");
				} else {
					$this->redirect("admin/registremodifications/infos/registreid/".$registreid);
				}					
			}
		}		
		if( count( $errorMessages ) ) {
			if( intval($registreid)) {
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_modifications", "registreid=".$registreid);
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
		$this->view->data           = $defaultData;
		$this->view->localites      = $localites;
		$this->view->domaines       = $domaines;
		$this->view->types          = $modificationTypes;
		$this->view->parentid       = $parentid;
		$this->view->parentNum      = $parentNumRc;
		$this->view->representantid = $representantid;
	}
	
	
	public function editAction()
	{
		$this->view->title = " Mettre à jour les informations d'une modification";
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id", 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect(   "admin/registremodifications/list");
		}		
		$model                 = $this->getModel("registre");
		$modelModification     = $this->getModel("registremodification");
		$modelModificationType = $this->getModel("modificationtype");
		$modelRepresentant     = $this->getModel("representant");
		$modelLocalite         = $this->getModel("localite");
		$modelDomaine          = $this->getModel("domaine");
		$modelDocument         = $this->getModel("document");
 	
		$registre              = $model->findRow( $registreid, "registreid", null , false);		
		$modification          = $modelModification->findRow($registreid, "registreid", null , false);
        $representant          = ( $modification) ? $modification->representant() : null;		
		if(!$registre || !$modification) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremodifications/list");
		}
		$defaultParent                       =($registre->parentid) ? $model->findRow($registre->parentid, "registreid", null , false) : null;
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$modificationTypes                   = $modelModificationType->getSelectListe("Selectionnez le type", array("type" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid","libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid","code"), array() , 0 , null , false);	
		$registreData                        = $registre->toArray();
		$modificationData                    = $modification->toArray();
		$representantData                    = $representant->toArray();
		$representantid                      = intval($this->_getParam("representantid", $representant->representantid));
		$parentid                            = intval($this->_getParam("parentid"      , $registre->parentid));
		$parentNumRc                         = strip_tags($this->_getParam("parentnum" , (( $defaultParent )? $defaultParent->numero : null )));
		$parent                              = (!empty($parentNumRc))? $model->findRow($parentNumRc,"numero", null, false):(($parentid)? $model->findRow($parentid,"registreid",null,false) : null);
		$parentid                            = ( $parent) ? $parent->registreid   : 0;
		$parentNumRc                         = ( $parent) ? $parent->numero       : null;
		
		$defaultData                         = array_merge( $registreData, $modificationData, $representantData);
		$emptyRepresentantData               = array("passport"=>null,"nom"=>null,"prenom"=>null,"adresse"=>null,"country"=>null,"city"=>null,"telephone"=>null,"email"=>null,"structure"=>null,"marital_status"=>null,"sexe"=>null,"lieunaissance"=>null,"datenaissance"=>null,"creationdate"=>null,"creatorid"=>null,"updatedate"=>null,"updateduserid"=>null);
		$errorMessages                       = array();  		
		
		$defaultData["date_year"]            = date("Y", $registre->date);
		$defaultData["date_month"]           = date("m", $registre->date);
		$defaultData["date_day"]             = date("d", $registre->date);
		$defaultData["date_naissance_year"]  = (isset($defaultData["datenaissance"]))? date("Y", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["date_naissance_month"] = (isset($defaultData["datenaissance"]))? date("m", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["date_naissance_day"]   = (isset($defaultData["datenaissance"]))? date("d", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["check_documents"]      = $registre->checked;
		$defaultData["find_documents"]       = DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
		$defaultData["check_documents"]      = $checkDocuments = 0;
		$defaultData["find_documents_src"]   = $fileSource     = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
 
		if( $this->_request->isPost()) {
			$postData                        = $this->_request->getPost();
			$update_registre_data            = $registre_data     = array_merge($registreData    , array_intersect_key($postData,$registreData));
			$update_modification_data        = $modification_data = array_merge($modificationData, array_intersect_key($postData,$modificationData  ));
			$update_representant_data        = array_merge($representantData, array_intersect_key( $postData,$emptyRepresentantData) );
			
			$me                              = Sirah_Fabric::getUser();
			$modelTable                       = $me->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
			$parentid                        = $update_registre_data["parentid"] = $registre_data["parentid"] = (isset( $postData["parentid"] )) ? intval($postData["parentid"]) : $registre->parentid;
			$parent                          = ( $parentid ) ? $model->findRow( $parentid, "parentid", null , false ) : null;	
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(          new Zend_Filter_StringTrim());
			$stringFilter->addFilter(          new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($update_registre_data["numero"])));
			$libelle                         = $stringFilter->filter( $update_registre_data["libelle"] );
			$findDocuments                   = (isset($postData["find_documents"])) ? intval($postData["find_documents"]) : DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
			$checkDocuments                  = (isset($postData["check_documents"]))? intval($postData["check_documents"]): DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
			
			if(!is_dir( $defaultDocumentSrc ) ) {
				$defaultDocumentSrc          = $fileSource = "C:\ERCCM/DATA";	
			}
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]             = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false ) && ( $registre->numero != $numero ) ) {
				$errorMessages[]             = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$update_registre_data["numero"]      = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle ) && $parent ) {
				$libelle                             = sprintf("MODIFICATION DU RC N° %s", $parent->numero);
		    } elseif(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                     = "Veuillez renseigner le titre de la modification";
			} 
			if( $model->findRow( $libelle , "libelle" , null , false )) {
				$libelle                             = ( $parent ) ? sprintf("RC n° %s : %s", $parent->numero, $libelle ) : sprintf("RC n° %s : %s", $numero, $libelle ) ;
			} else {
				$update_registre_data["libelle"]     = $libelle;
			}
			if(!$strNotEmptyValidator->isValid($update_representant_data["nom"]) || !$strNotEmptyValidator->isValid($update_representant_data["prenom"])) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour le promoteur";
			}				
			if((!intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]])) && !$parent ) {
				$errorMessages[]                     = "Veuillez sélectionner une localité valide";
			} elseif((!intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]])) && $parent ) {
				$update_registre_data["localiteid"]  = $parent->localiteid;
		    } else {
				$update_registre_data["localiteid"]  = intval( $update_registre_data["localiteid"] ) ;
			}
			$localiteCode                            = (isset($localitesCodes[$update_registre_data["localiteid"]])) ? $localitesCodes[$update_registre_data["localiteid"]] : "OUA";	 
			$dateYear                                = (isset($postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                               = (isset($postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                                 = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";						 
			
			$zendDate                                = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			$rcPathroot                              = APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$dateYear.DS.$numero;
            $numeroPrefixToCheck                     = sprintf("BF%s%dM", $localiteCode, $dateYear);
			
			$registreYear                            = substr($numero, 5, 4 );
			
			/*if( stripos($update_registre_data["numero"], $numeroPrefixToCheck) === FALSE ) {
				$errorMessages[]                     = sprintf("La modification numéro %s que vous avez indiquée ne semble pas correct. Il devrait commencer par %s", $update_registre_data["numero"], $numeroPrefixToCheck);
			}*/
            if( strlen($update_registre_data["numero"])!= 14) {
				$errorMessages[]                         = sprintf("La modification numéro %s que vous avez indiquée ne semble pas correct. Il doit comporter 14 caractères", $update_registre_data["numero"] );
			}						
			$update_registre_data["domaineid"]           = intval( $update_registre_data["domaineid"] ) ;
			$update_registre_data["date"]                = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["checked"]             = intval($checkDocuments);
			$update_registre_data["statut"]              = intval( $update_registre_data["statut"]  );
			$update_registre_data["description"]         = $stringFilter->filter( $update_registre_data["description"]);
			$update_registre_data["adresse"]             = $stringFilter->filter( $update_registre_data["adresse"]    );
			$update_registre_data["telephone"]           = $stringFilter->filter( $update_registre_data["telephone"]  );
			$update_registre_data["updateduserid"]       = $me->userid;
			$update_registre_data["updatedate"]          = time();
			
			$update_modification_data["article_actuel"] = ( isset($postData["article_actuel"]) && !empty($postData["article_actuel"])) ? $stringFilter->filter( $postData["article_actuel"]) : $registre_data["description"];
			$update_modification_data["article_suppr"]  = ( isset($postData["article_suppr"] ) && !empty($postData["article_suppr"] )) ? $stringFilter->filter( $postData["article_suppr"])  : "";
			$update_modification_data["article_ajout"]  = ( isset($postData["article_ajout"] ) && !empty($postData["article_ajout"] )) ? $stringFilter->filter( $postData["article_ajout"])  : "";
			$update_modification_data["type"]            = ( isset($postData["type"]  )) ? intval( $postData["type"]  ) : 0;
			
			if(!intval($update_modification_data["type"] ) || !isset( $modificationTypes[$update_modification_data["type"]])) {
				$errorMessages[]                         = "Veuillez indiquer un type de modification valide";
			}			
			//On enregistre les informations du representant
			$dateNaissanceYear                           = (isset($postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"] ) : "0000";
			$dateNaissanceMonth                          = (isset($postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay                            = (isset($postData["date_naissance_day"]) && ($postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
				
			$update_representant_data["datenaissance"]   = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$update_representant_data["lieunaissance"]   = $stringFilter->filter($update_representant_data["lieunaissance"]  );
			$update_representant_data["marital_status"]  = $stringFilter->filter($update_representant_data["marital_status"] );
			$update_representant_data["nom"]             = $stringFilter->filter($update_representant_data["nom"]            );
			$update_representant_data["prenom"]          = $stringFilter->filter($update_representant_data["prenom"]         );
			$update_representant_data["adresse"]         = $stringFilter->filter($update_representant_data["adresse"]        );
			$update_representant_data["email"]           = $stringFilter->filter($update_representant_data["email"]          );
			$update_representant_data["passport"]        = $stringFilter->filter($update_representant_data["passport"]       );
			$update_representant_data["profession"]      = (isset( $postData["fonction"])) ? $stringFilter->filter($postData["fonction"]) : $stringFilter->filter( $update_representant_data["profession"]);
			$update_representant_data["sexe"]            = $stringFilter->filter($update_representant_data["sexe"] );
			$update_representant_data["city"]            = 0;
			$update_representant_data["country"]         = $stringFilter->filter($update_representant_data["country"]   );
			$update_representant_data["telephone"]       = $stringFilter->filter($update_representant_data["telephone"] );
			$update_representant_data["structure"]       = "";						
			$update_representant_data["updateduserid"]   = $me->userid;
			$update_representant_data["updatedate"]      = time();
			
			$documentsUploadAdapter                      = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf"));
			$documentsUploadAdapter->setOptions(array("ignoreNoFile" => true));
			
			if(!$findDocuments ) {
				if( $checkDocuments && empty($errorMessages) && $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
					$formulaireFilename               = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename                = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;	
					$checkRccmData                    = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, "numero" => $numero);
					if( (false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) && $checkDocuments ) {
						$errorMessages[]              =  sprintf("Les fichiers du RCCM %s ne sont pas valides. Veuillez vérifier le contenu des documents",$numero);
					}
				}
			} else {				
				$rccmFormulaireFilepath               = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS.$numero."-FR.pdf";
			    $rccmPersonnelFilepath                = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS.$numero."-PS.pdf";
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
			if( isset( $update_registre_data["registreid"])) {
				unset( $update_registre_data["registreid"] );
			}
			if( isset( $update_modification_data["registreid"] ))  {
				unset( $update_modification_data["registreid"]);
			}
			$registre->setFromArray(     $update_registre_data     );
			$modification->setFromArray( $update_modification_data );
			if(empty($errorMessages)) {
				$representantData                       = array_intersect_key($update_representant_data, $emptyRepresentantData);
				$representantid                         = $representant->representantid;
				if( $registre->save() && $modification->save()) {
					$dbAdapter->update(  $prefixName."rccm_registre_representants",$representantData, array("representantid=?"=>$representantid));
					if(!$findDocuments ) {
						$documentData                   = array();
					  	$documentData["userid"]         = $me->userid;
					  	$documentData["category"]       = 1;
					  	$documentData["resource"]       = "registremodification";
					  	$documentData["resourceid"]     = 0;
					  	$documentData["filedescription"]= $registre_data["description"];
					  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
					  	$documentData["creationdate"]   = time();
					  	$documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	$formulairePath                 = $rcPathroot . DS . $numero."-FR.pdf";
					  	$personnelPath                  = $rcPathroot . DS . $numero."-PS.pdf";
						if( $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode, 0777 );
								}
								    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear);
								    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear, 0777 );									   
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
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=0"));
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=-2"));
							    $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0  AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = -2 AND registreid='".$parentid."')");
								if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
					  	  	  	   	$documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
									if( $parent ) {
										$parentFormulaireData             = $formulaireData;
										$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $parent->numero);
										if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
											$parentDocumentid             = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-2"));
										}
									}
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
					  	  	  	$personnelDocData["filename"]         = $modelDocument->rename("FOND DE DOSSIER", $numero);
					  	  	  	$personnelDocData["filepath"]         = $personnelPath;
					  	  	  	$personnelDocData["access"]           = 6;
					  	  	  	$personnelDocData["filextension"]     = "pdf";
					  	  	  	$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							    $dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=4"));
								$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=4 AND registreid='".$parentid."')");
								if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  		$documentid                       = $dbAdapter->lastInsertId();
					  	  	  		$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid,"access" => 6));
									if( $parent ) {
										$parentPersonnelData              = $personnelDocData;
										$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
										if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
											$parentDocumentid             = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5 ));
										}
									}
					  	  	  	} else {
					  	  	  		$errorMessages[]                  = "Les informations du document personnel ont été partiellement enregistrées";
					  	  	  	}					  	  	  	
					  	  	} else {
					  	  	  		$errorMessages[]                  = "Le document personnel n'a pas été copié sur le serveur";
					  	  	}
						}					  	  	       					  	  	
					} else {
						$rccmFormulaireFilepath             = $filesSource. DS . $localiteCode. DS .$registreYear. DS . $numero. DS. $numero."-FR.pdf";
			            $rccmPersonnelFilepath              = $filesSource. DS . $localiteCode. DS .$registreYear. DS . $numero. DS. $numero."-PS.pdf";
						
						if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
							$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
							$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
										   					  	  	       					  	  	  
					  	  	$documentData                   = array();
					  	  	$documentData["userid"]         = $me->userid;
					  	  	$documentData["category"]       = 1;
					  	  	$documentData["resource"]       = "registremodification";
					  	  	$documentData["resourceid"]     = 0;
					  	  	$documentData["filedescription"]= $registre_data["description"];
					  	  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	$documentData["creationdate"]   = time();
					  	  	$documentData["creatoruserid"]  = $me->userid;
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS.$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode, 0777 );
								}
								    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode. DS .$registreYear);
								    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode. DS .$registreYear, 0777 );									   
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
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=0"));
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=-1"));
							    $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0  AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = -2 AND registreid='".$parentid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0 ));
					  	  	  	} else {
					  	  	  	    $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
								    $errorMessages[]                = sprintf("L'indexation automatique de la modification numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
							if( file_exists( $newRccmPersonnelFilepath )) {
								@unlink(     $newRccmPersonnelFilepath );
							}
							if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	$personnelFileData                  = $documentData;
					  	  	  	$personnelFileData["filename"]      = $modelDocument->rename("FOND DE DOSSIER",$numero);
					  	  	  	$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
					  	  	  	$personnelFileData["access"]        = 6;
					  	  	  	$personnelFileData["filextension"]  = "pdf";
					  	  	  	$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid,"access=6"));
							    $dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  ,"access=5"));
								$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=5 AND registreid='".$parentid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
									if( $parent ) {
										$parentPersonnelData              = $personnelDocData;
										$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
										if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentPersonnelData )) {
											$parentDocumentid             = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 4 ));
										}
									}
					  	  	  	} else {
					  	  	  	   	    $errorMessages[]   = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
									    $errorMessages[]   = sprintf("L'indexation automatique de la modification numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
						}  
					}															
					if( !count( $errorMessages ) ) {
						if( $this->_request->isXmlHttpRequest()) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							$defaultData               = array_merge( $update_physique_data, $update_representant_data, $update_registre_data, $postData );
							$jsonErrorArray            = $defaultData;
							$jsonErrorArray["success"] = sprintf("Les informations de la modification numéro %s ont été mises à jour avec succès", $numero);
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect(sprintf("Les informations de la modification numéro %s ont été mises à jour avec succès", $numero), "success" );
						$this->redirect("admin/registremodifications/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été effectuée dans les informations de la modification"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été effectuée dans les informations du registre de commerce" , "message");
					$this->redirect("admin/registremodifications/infos/id/".$registreid);
				}
			} else {
				    $defaultData   = array_merge($update_representant_data, $update_registre_data, $postData );				
			}					
		}
		if( count( $errorMessages )) {
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
		$this->view->data           = $defaultData;
		$this->view->parentid       = $parentid;
		$this->view->registreid     = $registreid;
		$this->view->parentNum      = $parentNumRc;
		$this->view->representantid = $representantid;
		$this->view->localiteid     = $localiteid;
		$this->view->localites      = $localites;
		$this->view->domaines       = $domaines;
		$this->view->types          = $modificationTypes;
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
			$this->redirect("admin/registremodifications/list");
		}		
		$model                 = $this->getModel("registre");
		$modelModification     = $this->getModel("registremodification");
		$modelModificationType = $this->getModel("modificationtype");
		$modelRepresentant     = $this->getModel("representant");
		$modelLocalite         = $this->getModel("localite");
		$modelDocument         = $this->getModel("document");
 	
		$registre              = $model->findRow( $registreid, "registreid", null , false);
		$modification          = $modelModification->findRow( $registreid, "registreid", null , false);
        $representant          = ( $modification ) ? $modification->representant() : null;		
		if(!$registre || !$modification || !$representant ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremodifications/list");
		}
		$registreData              = $registre->toArray();
		$modificationData          = $modification->toArray();
		$dirigeants                = $registre->dirigeants();
		$representantData          = (isset( $dirigeants[0]["representantid"])) ? $dirigeants[0] : array();
		$defaultData               = array_merge( $registreData, $modificationData, $representantData);
		
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->modification  = $modification;
		$this->view->parent        = ( $registre->parentid ) ? $model->findRow( $registre->parentid,"registreid", null , false): null ;
		$this->view->registreid    = $registreid;
		$this->view->representant  = $representant;
		$this->view->domaine       = $registre->findParentRow("Table_Domaines");
		$this->view->localite      = $registre->findParentRow("Table_Localites");
		$this->view->documents     = $registre->documents();
		$this->view->title         = sprintf("Les informations du registre de commerce numero %s", $registre->numero);
		$this->view->columns       = array("left");	
	} 
	
	public function updatealldocsAction()
	{
		$model                     = $this->getModel("registre");
		$modelModification         = $this->getModel("registremodification");
		$modelRepresentant         = $this->getModel("representant");
		$modelDocument             = $this->getModel("document");
		$dbAdapter                 = $model->getTable()->getAdapter();
		$prefixName                = $model->getTable()->info("namePrefix");
		$me                        = Sirah_Fabric::getUser();
		$fileSource                = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
		
		$ids                       = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages             = array();
		if( is_string($ids) ) {
			$ids                   = explode("," , $ids );
		}
		$ids                       = (array)$ids;
		if( count(   $ids )) {
			foreach( $ids as $registreid ) {
				     $registre     = $model->findRow( $registreid, "registreid" , null , false);
		             $modification = $modelModification->findRow( $registreid, "registreid", null , false )	;
					 if( $registre && $modification ) {
						 $numero                 = $registre->numero;
		                 $dateYear               = substr( $numero, 5, 4);
		                 $localite               = $registre->findParentRow("Table_Localites");
		                 $localiteCode           = ( $localite ) ? $localite->code : "";
						 $rccmFormulaireFilepath = $fileSource.DS.$localiteCode.DS.$dateYear.DS.$numero.DS.$numero."-FR.pdf";
		                 $rccmPersonnelFilepath  = $fileSource.DS.$localiteCode.DS.$dateYear.DS.$numero.DS.$numero."-PS.pdf";
		                 $rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER .DS."MODIFICATIONS".DS.$localiteCode.DS.$dateYear.DS.$numero;
					     if( empty($localiteCode) || (strlen($dateYear) != 4)) {
							 $errorMessages[]    = sprintf("La modification n° %s n'est pas valide", $numero );
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
		                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS". DS . $localiteCode . DS . $dateYear) ) {
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS . "MODIFICATIONS")) {
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS. "MODIFICATIONS");
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS. "MODIFICATIONS", 0777 );
			                 }
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS. $localiteCode)) {
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS. $localiteCode);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS. $localiteCode, 0777 );
			                 }
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS. $localiteCode. DS . $dateYear);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS. $localiteCode. DS . $dateYear, 0777 );									   
		                 }
		                 if(!is_dir($rcPathroot)) {
			                @chmod($rcPathroot, 0777 );
			                @mkdir($rcPathroot);
		                 }
						 $documentData                           = array();
		                 $documentData["userid"]                 = $me->userid;
		                 $documentData["category"]               = 1;
		                 $documentData["resource"]               = "registremodification";
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
							 
			                 $dbAdapter->delete($prefixName."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							 $dbAdapter->delete($prefixName."rccm_registre_documents", array("registreid=".$parentid  , "access=4"));
							 $dbAdapter->delete($prefixName."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	 $dbAdapter->delete($prefixName."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=4 AND registreid='".$parentid."')");
			                 
							 if( $dbAdapter->insert($prefixName."system_users_documents" , $formulaireFileData)) {
				                 $documentid                     = $dbAdapter->lastInsertId();
				                 $dbAdapter->insert($prefixName."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
								 if( $parent ) {
									 $parentFormulaireData             = $formulaireFileData;
									 $parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $parent->numero);
									 if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
										 $parentDocumentid             = $dbAdapter->lastInsertId();
										 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-2"));
									 }
								 }
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
			                 $personnelFileData["filename"]      = $modelDocument->rename("FOND DE DOSSIER", $numero);
				             $personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
				             $personnelFileData["access"]        = 6;
				             $personnelFileData["filextension"]  = "pdf";
				             $personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
				             $dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
				             $dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
				             if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
					             $documentid                     = $dbAdapter->lastInsertId();
					             $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
								 if( $parent ) {
									 $parentPersonnelData              = $personnelFileData;
									 $parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
									 if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
										 $parentDocumentid             = $dbAdapter->lastInsertId();
										 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 4));
									 }
								 }
				             } else {
					             $errorMessages[]                = sprintf("Les informations du formulaire de la modification n° %s ont été partiellement enregistrées", $numero);
								 continue;
				             }
		                 } else {
					             $errorMessages[]                = sprintf("L'indexation automatique de la modification numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
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
			$this->redirect("admin/registremodifications/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été re-indexés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été re-indexés avec succès", "success");
			$this->redirect("admin/registremodifications/list");
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
			$this->redirect("admin/registremodifications/list");
		}		
		$model                 = $this->getModel("registre");
		$modelModification     = $this->getModel("registremodification");
		$modelRepresentant     = $this->getModel("representant");
		$modelDocument         = $this->getModel("document");
		$dbAdapter             = $model->getTable()->getAdapter();
		$prefixName            = $model->getTable()->info("namePrefix");
 	
		$registre              = $model->findRow(       $registreid, "registreid" , null , false);
		$modification          = $modelModification->findRow( $registreid, "registreid", null , false );	
		if(!$registre || !$modification) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremodifications/list");
		}
		$parentid              = $registre->parentid;
		$parent                = ( $parentid ) ? $model->findRow( $parentid, "parentid", null, false ) : null;
		$numero                = $registre->numero;
		$dateYear              = substr( $numero, 5, 4);
		$localite              = $registre->findParentRow("Table_Localites");
		$localiteCode          = ($localite ) ? $localite->code : "";
		$me                    = Sirah_Fabric::getUser();
		if( empty( $localiteCode ) || (strlen( $dateYear ) != 4 )) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registremodifications/list");
		}
		$fileSource                        = ( is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";	
		$rccmFormulaireFilepath            = $fileSource.DS.$localiteCode.DS.$dateYear.DS .$numero.DS. $numero."-FR.pdf";
		$rccmPersonnelFilepath             = $fileSource.DS.$localiteCode.DS.$dateYear.DS .$numero.DS. $numero."-PS.pdf";
		$rcPathroot                        = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS."MODIFICATIONS"    .DS.$localiteCode.DS . $dateYear. DS . $numero;
		
		if(!file_exists($rccmFormulaireFilepath)) {
			$errorMessages[]               = "Dans le dossier source, le formulaire du registre est manquant";
		}
		if(!file_exists( $rccmPersonnelFilepath )) {
			$errorMessages[]               = "Dans le dossier source, le fond de dossier du registre est manquant";
		}
		if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."MODIFICATIONS".DS.$localiteCode.DS. $dateYear) ) {
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS");
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS", 0777 );
			}
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode)) {
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode, 0777 );
			}
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $dateYear);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS . $dateYear, 0777 );									   
		}
		if(!is_dir($rcPathroot)) {
			@chmod($rcPathroot, 0777 );
			@mkdir($rcPathroot);
		}
		$documentData                   = array();
		$documentData["userid"]         = $me->userid;
		$documentData["category"]       = 1;
		$documentData["resource"]       = "registremodifications";
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
			$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=0"));
			$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=-2"));
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access= 0  AND registreid='".$registreid."')");
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access= -2 AND registreid='".$parentid."')");
			if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
				$documentid                     = $dbAdapter->lastInsertId();
				$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
				if( $parent ) {
					$parentFormulaireData             = $formulaireFileData;
					$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE MODIFICATION N° %s", $numero), $parent->numero);
					if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
						$parentDocumentid             = $dbAdapter->lastInsertId();
						$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-2"));
					}
				}
			} else {
				$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
			}
		} else {
				$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
		}
		if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
			$personnelFileData                  = $documentData;
			$personnelFileData["filename"]      = $modelDocument->rename("FOND DE DOSSIER", $numero);
			$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
			$personnelFileData["access"]        = 6;
			$personnelFileData["filextension"]  = "pdf";
			$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
			$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$registreid, "access=6"));
			$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=5"));
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=5 AND registreid='".$parentid."')");
			if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelFileData)) {
				$documentid                     = $dbAdapter->lastInsertId();
				$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
				if( $parent ) {
					$parentPersonnelData        = $personnelFileData;
					$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $parent->numero);
					if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
						$parentDocumentid             = $dbAdapter->lastInsertId();
						$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" =>4));
					}
				}
			} else {
				$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
			}
		} else {
				$errorMessages[]                = sprintf("L'indexation automatique du RCCM numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
		}			
		if(empty( $errorMessages )) {
				$registre->updatedate           = time();
				$registre->updateduserid        = $me->userid;
				$registre->save();
				if( $this->_request->isXmlHttpRequest() ) {
				    echo ZendX_JQuery::encodeJson(array("success" => "Les nouveaux documents (à jour) de ce registre ont été indexés avec succès"));
				    exit;
			    }
			      $this->setRedirect("Les nouveaux documents (à jour) de ce registre ont été indexés avec succès", "success");
			      $this->redirect("admin/registremodifications/infos/registreid/".$registreid);
		} else {
				if( $this->_request->isXmlHttpRequest()) {
				    echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				    exit;
			    }
			    foreach( $errorMessages as $errorMessage) {
				         $this->_helper->Message->addMessage($errorMessage , "error");
			    }
			    $this->redirect("admin/registremodifications/infos/registreid/".$registreid);
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
						$dbAdapter->delete($prefixName."rccm_registre_modifications", "registreid=".$id);
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
			$this->redirect("admin/registremodifications/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registremodifications/list");
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
				    $errorMessages[]     = "Veuillez selectionner un fichier CSV valide";
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
		$csvRow                            = $postData = $this->_request->getPost();
		/*echo ZendX_JQuery::encodeJson($csvRow );exit;*/
		$model                             = $this->getModel("registre");
		$modelDomaine                      = $this->getModel("domaine");
		$modelModification                 = $this->getModel("registremodification");
		$modelRepresentant                 = $this->getModel("representant");
		$modelLocalite                     = $this->getModel("localite");
		$modelModificationType             = $this->getModel("modificationtype");
		$modelDocument                     = $this->getModel("document");
		$me                                = Sirah_Fabric::getUser();		
		
		$modelTable                        = $model->getTable();
		$prefixName                        = $modelTable->info("namePrefix");
		$dbDestination                     = $dbAdapter = $modelTable->getAdapter();
		
		$registreDefaultData               = $model->getEmptyData();
		$representantDefaultData           = $modelRepresentant->getEmptyData();
		$domaines                          = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" ,"libelle")   , array() , 0 , null , false);
		$localites                         = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid","libelle")   , array() , 0 , null , false);
		$localitesCodes                    = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("code"      ,"localiteid"), array() , 0 , null , false);
		$typeModifications                 = $modelModificationType->getSelectListe("Selectionnez le type"      , array("type"      ,"libelle")   , array() , 0 , null , false);
		$countries                         = $this->view->countries();
		$localiteid                        = intval($this->_getParam("localiteid", APPLICATION_REGISTRE_DEFAULT_LOCALITEID));
		$domaineid                         = intval($this->_getParam("domaineid" , APPLICATION_REGISTRE_DEFAULT_DOMAINEID ));
		$defaultData                       = array_merge($representantDefaultData, $registreDefaultData);
		$modifications                     = array();
		$errorMessages                     = array();
		
		$defaultData["localiteid"]         = $localiteid;
		$defaultData["domaineid"]          = $domaineid ;
		$defaultData["find_documents_src"] = $filesSource = "C:\\ERCCM/DATA";
		$registreid                        = 0;
		$toRemove                          = false;
		$registre                          = null;
		$registre2                         = null;
					//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter                      = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
		$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		
		if( isset( $csvRow["numero_parent"] ) ) {
			$csvRow["parent"]            = $csvRow["numero_parent"];
		} elseif( isset( $csvRow["numparent"] )) {
			$csvRow["parent"]            = $csvRow["numparent"];
		}			
        $parentid                        = (isset($csvRow["parentid"]             ))? $csvRow["parentid"]   : 0  ;		
        $numeroParent                    = (isset($csvRow["parent"]               ))? preg_replace("/\s/","", $stringFilter->filter(strtoupper($stringFilter->filter($csvRow["parent"])))): null;       		
		$numeroRegistre                  = $numero = (isset($csvRow["numero"]     ))? preg_replace("/\s/","", $stringFilter->filter(strtoupper($stringFilter->filter($csvRow["numero"])))): null;
		$localiteRegistre                = (isset($csvRow["localite"]             ))? strtoupper($stringFilter->filter($csvRow["localite"]))          : "";
		$libelleRegistre                 = (isset($csvRow["nom_commercial"]       ))? trim($stringFilter->filter($csvRow["nom_commercial"]),"-")      : "";
		$dateRegistre                    = (isset($csvRow["date_enregistrement"]  ))? trim($stringFilter->filter($csvRow["date_enregistrement"]),"-") : date("d/m/Y");
		$descriptionRegistre             = (isset($csvRow["description"]          ))? trim($csvRow["description"], "-")                               : "";
		$typeModification                = (isset($csvRow["type_modification"]    ))? strtoupper($stringFilter->filter($csvRow["type_modification"])) : "";
		$exploitantLastname              = (isset($csvRow["nom"]                  ))? trim($stringFilter->filter($csvRow["nom"]), "-")                : "";
		$exploitantFirstname             = (isset($csvRow["prenom"]               ))? trim($stringFilter->filter($csvRow["prenom"]), "-")             : "";
		$exploitantLieuNaissance         = (isset($csvRow["lieu_naissance"]       ))? trim($stringFilter->filter($csvRow["lieu_naissance"]),"-")      : "";
		$exploitantDateNaissance         = (isset($csvRow["date_naissance"]       ))? trim($stringFilter->filter($csvRow["date_naissance"]),"-")      : "";
        $exploitantSexe                  = (isset($csvRow["sexe"]                 ))? trim(strtoupper($stringFilter->filter($csvRow["sexe"])),"-")    : "";
		$exploitantAdresse               = (isset($csvRow["adresse"]              ))? trim($stringFilter->filter($csvRow["adresse"]),"-")                : "";
		$exploitantTelephone             = (isset($csvRow["telephone"]            ))? trim($stringFilter->filter($csvRow["telephone"]),"-")              : "";
		$exploitantPassport              = (isset($csvRow["passport"]             ))? trim($stringFilter->filter($csvRow["passport"] ),"-")              : "";
		$exploitantNationalite           = (isset($csvRow["nationalite"]          ))? trim(strtoupper($stringFilter->filter($csvRow["nationalite"])),"-"): "";
		$exploitantMaritalStatus         = (isset($csvRow["situation_matrimonial"]))? trim($stringFilter->filter($csvRow["situation_matrimonial"]),"-")  : "";
		$exploitantEmail                 = (isset($csvRow["email"]                ))? trim($stringFilter->filter($csvRow["email"]), "-")                 : " ";
		$exploitantFonction              = (isset($csvRow["fonction"]             ))? trim($stringFilter->filter($csvRow["fonction"]), "-")              : "GERANT";

        $parent                          = (!empty($numeroParent))? $model->findRow($numeroParent ,"numero", null, false):(($parentid)? $model->findRow($parentid,"registreid",null,false) : null);
		
		if(!isset($typeModifications[$typeModification]) ) {
			$typeModification            = 7;
		}  		
		if( $parent ) {
			$parentid                    = $parent->registreid     ;
			$parentNumRc                 = $parent->numero         ;
			$representants               = $parent->dirigeants()   ;
			$modifications               = $parent->modifications();			
					
			$registreDefaultData         = $parent->toArray();
			$representantid              = (isset($representants[0]["representantid"]))? intval($representants[0]["representantid"]) : 0;
            $representantDefaultData     = (isset($representants[0]["representantid"]))? $representants[0]                           : array();
            $modificationDefaultData     = (isset($modifications[0]["registreid"]    ))? $modifications[0]                           : array();
            $defaultData                 = array_merge( $registreDefaultData, $representantDefaultData, $modificationDefaultData);
            if( empty( $libelleRegistre ) ) {
				$libelleRegistre         = $defaultData["libelle"];
			}
            if( empty( $dateRegistre ) ||  !Zend_Date::isDate($dateRegistre, "dd/MM/YYYY" ) ) {
				$dateRegistre            = date("d/m/Y", $defaultData["date"]);
			}
            if( empty( $descriptionRegistre )  ) {
				$descriptionRegistre     =  $defaultData["description"];
			}
            if( empty( $exploitantLastname )  && isset($representants[0]["lastname"])) {
				$exploitantLastname      = $representants[0]["lastname"];
			}
            if( empty( $exploitantFirstname )  && isset($representants[0]["firstname"])) {
				$exploitantFirstname     = $representants[0]["firstname"];
			}
            if( empty( $exploitantLieuNaissance )  && isset($representants[0]["lieunaissance"])) {
				$exploitantLieuNaissance = $representants[0]["lieunaissance"];
			}
            if( (empty( $exploitantDateNaissance) ||  !Zend_Date::isDate($exploitantDateNaissance, "dd/MM/YYYY" )) && isset($representants[0]["datenaissance"]) ) {
				$zendDateNaissance       = new Zend_Date($representants[0]["datenaissance"], "YYYY-MM-dd HH:mm:ss");
				if( $zendDateNaissance ) {
					$exploitantDateNaissance = $zendDateNaissance->toString("dd/MM/YYYY");
				}
			}
            if( empty( $exploitantSexe ) && isset($representants[0]["sexe"])) {
				$exploitantSexe          = $representants[0]["sexe"];
			}
            if( empty( $exploitantAdresse )  && isset($representants[0]["adresse"])) {
				$exploitantAdresse       = $representants[0]["adresse"];
			}
            if( empty( $exploitantTelephone ) &&  isset($representants[0]["telephone"])) {
				$exploitantTelephone     = $representants[0]["telephone"];
			}
            if( empty( $exploitantPassport ) &&  isset($representants[0]["passport"]) ) {
				$exploitantPassport      = $representants[0]["passport"];
			}
            if( empty( $exploitantNationalite ) && isset($representants[0]["country"]) ) {
				$exploitantNationalite   = $representants[0]["country"];
			}
            if( empty( $exploitantMaritalStatus ) && isset($representants[0]["marital_status"]) ) {
				$exploitantMaritalStatus = $representants[0]["marital_status"];
			}
            if( empty( $exploitantFonction )  && isset($representants[0]["profession"])) {
				$exploitantFonction      = $representants[0]["profession"];
			}
            if( empty( $exploitantEmail ) ) {
				$exploitantEmail         = $defaultData["email"];
			}			
		} else {
			$errorMessages[]             = sprintf("Le numéro Parent %s que vous avez indiqué ne semble pas valide", $numeroParent );
		}											 
		$localitesCodes2                 = $localitesCodes;
        $inscriptionDate                 = 0;
		$localiteCode                    = (isset($postData["localite"] )) ? $stringFilter->filter($postData["localite"]) : "";
        $dateYear                        =  $registreYear = (isset($postData["date_year"])) ? intval($postData["date_year"]): null;
		array_flip($localitesCodes2);
								 
		if(!$strNotEmptyValidator->isValid($numero)) {
		    $errorMessages[]             = "Votre requête n'est pas valide, car le numéro RC est vide";
		} 
		if($toUpdateRegistre             = $model->findRow( $numero, "numero" , null , false )) {		   
		   $registre                     = $toUpdateRegistre;
		   $registreid                   = $toUpdateRegistre->registreid;
		   if( $registre->creatorid != $me->userid) {
			   $errorMessages[]          = sprintf("Le registre de commerce numéro %s existe déjà", $numeroRegistre);
		   } 
		} 
        if(!$strNotEmptyValidator->isValid($libelleRegistre)) {
			$libelleRegistre             = sprintf("%s %s %s", $exploitantLastname, $exploitantFirstname, $numero);
		} 
		if( $registre2                   = $model->findRow( $libelleRegistre , "libelle" , null , false )) {
			$registre2Id                 = $registre2->registreid;
			$libelleRegistre             = "Modification ". $libelleRegistre;
			/*if( $registre2Id    != $registreid ) {
			    $errorMessages[] = sprintf("Un registre existant porte le nom commercial %s , veuillez entrer un nom commercial différent du RC n° %s", $libelleRegistre, $numeroRegistre );
		    }*/
		}  
		if(!$strNotEmptyValidator->isValid($exploitantLastname ) || !$strNotEmptyValidator->isValid($exploitantFirstname)) {
			$errorMessages[]     = sprintf("Veuillez entrer un nom de famille et/ou prénom valide du representant du RC numéro %d", $numero );
		} 	
		if( empty($localiteRegistre) ) {
			$localiteRegistre    = strtoupper(substr($numero,2,3));
        } 
		if(!isset( $localitesCodes2[$localiteRegistre])) {
			$errorMessages[]     = sprintf("Veuillez sélectionner une localité valide du registre n° %s", $numero );
		} else {
			$localiteid          = intval($localitesCodes2[$localiteRegistre]);
			$localiteCode        = $localiteRegistre;
		}
		if( $strNotEmptyValidator->isValid( $dateRegistre ) && Zend_Date::isDate($dateRegistre, "dd/MM/YYYY" ) ) {
			$zendDate            = new Zend_Date($dateRegistre, "dd/MM/YYYY");
            $inscriptionDate     = $zendDate->get(Zend_Date::TIMESTAMP);
        } elseif( $strNotEmptyValidator->isValid($dateRegistre) && Zend_Date::isDate($dateRegistre,"YYYY-MM-dd")) {
			$zendDate            = new Zend_Date( $dateRegistre,"YYYY-MM-dd");
			$inscriptionDate     = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
	    } elseif( $strNotEmptyValidator->isValid($dateRegistre) && Zend_Date::isDate($dateRegistre,"YYYY-MM-dd HH:mm:ss")) {
			$zendDate            = new Zend_Date( $dateRegistre,"YYYY-MM-dd HH:mm:ss");
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
                                 //$dateYear              = $registreYear = date("Y", $inscriptionDate);                                									 
        $numTypeRegistre         = strtoupper(trim(substr($numero, 9, 1)));
                                 //$dateYear              = $registreYear = date("Y", $inscriptionDate);
        if( $numTypeRegistre  !== "M") {
			$errorMessages[]     = sprintf("Le numéro de la modification `%s` ne semble pas valide ", $numero );
		}
		if( substr($numero,0,5) != $numeroPrefixToCheck ) {
			$errorMessages[]     = sprintf("Le numéro attribué au registre numéro %s n'est pas valide", $numero);
		}								 
		if( strlen($numero ) != 14) { 
			$errorMessages[]     = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $numero);
		}
		if(!$dateYear ) {
			$dateYear            = $registreYear = substr($numero,5, 4);
		}
		if((strtolower($exploitantSexe)=="femme") || (strtolower($exploitantSexe)=="femmes") || (strtolower($exploitantSexe) == "feminin")) {
		    $exploitantSexe      = "F";
		} elseif((strtolower($exploitantSexe) == "homme") || (strtolower($exploitantSexe) == "hommes") || (strtolower($exploitantSexe) == "masculin") ) {
		    $exploitantSexe      = "H";
		}
		if(($exploitantSexe != "F") && ($exploitantSexe != "M") && ( $exploitantSexe != "H")) {
            $errorMessages[]     = sprintf("Veuillez un sexe valide (entre F, M, et H) pour le registre n° %s : ", $numero, $exploitantSexe);
		}
        if( $strNotEmptyValidator->isValid($exploitantDateNaissance) && Zend_Date::isDate($exploitantDateNaissance, "dd/MM/YYYY" ) ) {
            $zendDate            = new Zend_Date( $exploitantDateNaissance,Zend_Date::DATES , "fr_FR" );
            $dateNaissance       = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
		} elseif( $strNotEmptyValidator->isValid($exploitantDateNaissance) && Zend_Date::isDate($exploitantDateNaissance,"YYYY-MM-dd")) {
			$zendDate            = new Zend_Date( $exploitantDateNaissance,"YYYY-MM-dd");
			$dateNaissance       = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
	    } elseif( $strNotEmptyValidator->isValid($exploitantDateNaissance) && Zend_Date::isDate($exploitantDateNaissance,Zend_Date::ISO_8601)) {
			$zendDate            = new Zend_Date($exploitantDateNaissance,Zend_Date::ISO_8601);
			$dateNaissance       = $zendDate->toString('YYYY-MM-dd HH:mm:ss');
	    } else {
            $errorMessages[]     = sprintf("Veuillez indiquer une date de naissance valide du registre numéro %s", $numero );
        }
        if(!$strNotEmptyValidator->isValid( $exploitantLieuNaissance )) {
            $errorMessages[]     = sprintf("Veuillez indiquer un lieu de naissance valide du registre numéro %s", $numero);								 
		}
		if(!isset($countries[$exploitantNationalite] )) {
			if( $countryFound               = Sirah_Functions_ArrayHelper::search($countries, $exploitantNationalite )) {
				$exploitantNationalite      = key($countryFound); 
			}
		}
		if( empty( $errorMessages ) && !empty( $numero )) {
			if( $registre ) {
				$registreid                  = $registre->registreid;
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_modifications", "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique  WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid     IN (SELECT documentid     FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
				$dbAdapter->delete($prefixName."rccm_registre_documents"    , "registreid=".$registreid);
			}
			$registre_data                   = $modification_data = array();
		    $registre_data["numero"]         = $numero;
	        $registre_data["libelle"]        = $libelleRegistre;
	        $registre_data["localiteid"]     = $localiteid;
		    $registre_data["date"]           = $inscriptionDate;
		    $registre_data["type"]           = 4;
		    $registre_data["statut"]         = 2;
            $registre_data["category"]       = sprintf("M%d", count($modifications));
		    $registre_data["checked"]        = 1;
		    $registre_data["description"]    = $descriptionRegistre;
			$registre_data["adresse"]        = $exploitantAdresse;
			$registre_data["telephone"]      = $exploitantTelephone;
		    $registre_data["creatorid"]      = $me->userid;
            $registre_data["creationdate"]   = time()+100;	
            $registre_data["updateduserid"]  = 0;
            $registre_data["updatedate"]     = 0;
            $registre_data["domaineid"]      = $domaineid;
			$registre_data["parentid"]       = $parentid;
						
			   /*$domaines                        = $modelDomaine->getList(array("libelle"=> $descriptionRegistre ));
			
			if( count( $domaines )) {
				$registre_data["domaineid"]  = (isset($domaines[0]["domaineid"] )) ? intval($domaines[0]["domaineid"]) : $domaineid;
			}
			print_r($registre_data);die();*/
			if( $dbAdapter->insert( $prefixName ."rccm_registre", $registre_data)) {
				$registreid                               = $dbAdapter->lastInsertId();
				$toRemove                                 = true;
				$modification_data["registreid"]          = $registreid;
				$modification_data["article_actuel"]     = ( isset($csvRow["article_actuel"]) && !empty($csvRow["article_actuel"]))? $stringFilter->filter($csvRow["article_actuel"]) : $registre_data["description"];
				$modification_data["article_suppr"]      = ( isset($csvRow["article_suppr"] ) && !empty($csvRow["article_suppr"] ))? $stringFilter->filter($csvRow["article_suppr"])  : "";
				$modification_data["article_ajout"]      = ( isset($csvRow["article_ajout"] ) && !empty($csvRow["article_ajout"] ))? $stringFilter->filter($csvRow["article_ajout"])  : "";
				$modification_data["type"]                = (!empty($typeModification )       ) ? $typeModification : 7;
				$modification_data["creationdate"]        = time();	
				$modification_data["creatorid"]           = $me->userid;
				$modification_data["updateduserid"]       = 0;
				$modification_data["updatedate"]          = 0;
				if( $dbAdapter->insert( $prefixName . "rccm_registre_modifications", $modification_data )) {
					$representant_data                    = array();
					$representant_data["datenaissance"]   = $dateNaissance;
					$representant_data["lieunaissance"]   = $exploitantLieuNaissance;
					$representant_data["nom"]             = $exploitantLastname;
					$representant_data["prenom"]          = $exploitantFirstname;
					$representant_data["adresse"]         = $exploitantAdresse;
					$representant_data["city"]            = 0;
					$representant_data["country"]         = $exploitantNationalite;
					$representant_data["email"]           = $exploitantEmail;
					$representant_data["marital_status"]  = $exploitantMaritalStatus;
					$representant_data["telephone"]       = $exploitantTelephone;
					$representant_data["passport"]        = $exploitantPassport;
					$representant_data["sexe"]            = $exploitantSexe;
					$representant_data["structure"]       = "";
					$representant_data["profession"]      = $exploitantFonction;
					$representant_data["creatorid"]       = $me->userid;
					$representant_data["creationdate"]    = time();
					$representant_data["updateduserid"]   = 0;
					$representant_data["updatedate"]      = 0;
					if( $dbAdapter->insert(     $prefixName ."rccm_registre_representants", $representant_data ) ) {
						$representantid                   = $dbAdapter->lastInsertId();
						if( $dbAdapter->insert( $prefixName ."rccm_registre_dirigeants", array("registreid"=> $registreid,"representantid"=> $representantid,"fonction"=> "GERANT"))) {											 
							$registres[]                  = $registreid;
							$defaultDocumentSrc           = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;
							$rccmFormulaireFilepath       = $filesSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-FR.pdf";
							$rccmPersonnelFilepath        = $filesSource.DS.$localiteCode.DS. $registreYear. DS .$numero.DS.$numero."-PS.pdf";
							$rcPathroot                   = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS .$registreYear. DS. $numero;
													 //print_r($rccmFormulaireFilepath);print_r($rcPathroot);die();
							if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath ) && $registreid) {
								$newRccmFormulaireFilepath       = $rcPathroot. DS . $numero."-FR.pdf";
								$newRccmPersonnelFilepath        = $rcPathroot. DS . $numero."-PS.pdf";
								$modelDocument                   = $this->getModel("document");					  	  	       					  	  	  
								$documentData                    = array();
								$documentData["userid"]          = $me->userid;
								$documentData["category"]        = 1;
								$documentData["resource"]        = "registremodifications";
								$documentData["resourceid"]      = 0;
								$documentData["filedescription"] = $registre_data["description"];
								$documentData["filemetadata"]    = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
								$documentData["creationdate"]    = time();
								$documentData["creatoruserid"]   = $me->userid;
								
								if(!is_dir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS". DS . $localiteCode. DS .$registreYear) ) {
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS")) {
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS");
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "MODIFICATIONS", 0777 );
									}
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode)) {
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode);
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode, 0777 );
									}
									@mkdir(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode. DS . $registreYear);
									@chmod(    APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."MODIFICATIONS". DS . $localiteCode. DS . $registreYear, 0777 );									   
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
								if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
									$personnelFileData                  = $documentData;
									$personnelFileData["filename"]      = $modelDocument->rename(sprintf("FOND DE DOSSIER MODIFICATION N° %s", $numero), $numero);
									$personnelFileData["filepath"]      = $newRccmPersonnelFilepath;
									$personnelFileData["access"]        = 6;
									$personnelFileData["filextension"]  = "pdf";
									$personnelFileData["filesize"]      = floatval(filesize( $rccmPersonnelFilepath ));
									if( $dbAdapter->insert( $prefixName  ."system_users_documents", $personnelFileData)) {
										$documentid                     = $dbAdapter->lastInsertId();
										$dbAdapter->insert( $prefixName  ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 6));
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
				                    $errorMessages[]                    = sprintf("Les informations propres à la modification n'ont pas pu être enregistrées dans la base de données");
				}				
			} else {
					                $errorMessages[]                    = sprintf("Le registre numéro %s n'a pas été enregistré, Aucune information n'a pu être enregistrée", $numero );
			}
		}		
		if( count( $errorMessages )) {
			if( intval($registreid) && ($toRemove==true)) {
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_modifications", "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$registreid);						
				$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique  WHERE registreid='".$registreid."')");						
				$dbAdapter->delete($prefixName."system_users_documents"     , "documentid     IN (SELECT documentid     FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$registreid."')");
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
						 			
            $csvDestinationName     = APPLICATION_DATA_PATH . DS .  "tmp" . DS . time()."rccmImport.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("Quartier","Section","Lot","Parcelle","NumeroPARENT","NomDemandeur","AdressePostale","DateDemande","DateNaissance","LieuNaissance","Pays","NumeroRCCM","NomCommercial","ArticlePrincipale","Telephone","AdressePhysique");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvRows        = $csvAdapter->getLines();
					$csvItems       = 1;
					//print_r($csvRows); die();
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $registreData       = array();
							     $DateDemande        = (isset($csvRow["DateDemande"]        ))?$csvRow["DateDemande"]                               : "";
								 $DateNaissance      = (isset($csvRow["DateNaissance"]      ))?$csvRow["DateNaissance"]                             : "";
								 $LieuNaissance      = (isset($csvRow["LieuNaissance"]      ))?$stringFilter->filter($csvRow["LieuNaissance"])      : "";
								 $NumeroRCCM         = (isset($csvRow["NumeroRCCM"]         ))?preg_replace("/\s/", "",$stringFilter->filter($csvRow["NumeroRCCM"]  )) : "";
								 $NumeroRCCMParent   = (isset($csvRow["NumeroPARENT"]       ))?preg_replace("/\s/", "",$stringFilter->filter($csvRow["NumeroPARENT"])) : "";
								 $Quartier           = (isset($csvRow["Quartier"]           ))?$stringFilter->filter($csvRow["Quartier"])           : "";
								 $Section            = (isset($csvRow["Section"]            ))?$stringFilter->filter($csvRow["Section"])            : "";
								 $Parcelle           = (isset($csvRow["Parcelle"]           ))?$stringFilter->filter($csvRow["Parcelle"])           : "";
								 $Lot                = (isset($csvRow["Lot"]                ))?$stringFilter->filter($csvRow["Lot"])                : "";
								 $NomCommercial      = (isset($csvRow["NomCommercial"]      ))?$stringFilter->filter($csvRow["NomCommercial"])      : "";
								 $NomDemandeur       = (isset($csvRow["NomDemandeur"]       ))?$stringFilter->filter($csvRow["NomDemandeur"])       : "";
								 $ArticlePrincipale = (isset($csvRow["ArticlePrincipale"] ))?$stringFilter->filter($csvRow["ArticlePrincipale"]) : "";
								 $Pays               = (isset($csvRow["Pays"]               ))?$stringFilter->filter($csvRow["Pays"])               : "";
								 $AdressePostale     = (isset($csvRow["AdressePostale"]     ))?$stringFilter->filter($csvRow["AdressePostale"])     : "";
								 $AdressePhysique    = (isset($csvRow["AdressePhysique"]    ))?$stringFilter->filter($csvRow["AdressePhysique"])    : "";
								 $Telephone          = (isset($csvRow["Telephone"]          ))?$stringFilter->filter($csvRow["Telephone"])          : "";
								 $TypeModification   = 7;
								 
								 if((FALSE===$model->isValidNum($NumeroRCCMParent)) || (FALSE===$model->isValidNum($NumeroRCCM)) ) {
									 //$errorMessages[]= sprintf("Le numéro %s n'est pas valide", $NumeroRCCM);
								     continue;
								 }
								 if((strlen($NomCommercial)<=2) || (strtolower($NomCommercial) == "neant")) {
									 $NomCommercial                         = "";
								 }
								 $numeroRcYear       = $annee               = substr($NumeroRCCM, 5, 4 );
								 $NumeroRCCM                                = $model->normalizeNum($NumeroRCCM);
								 $NumeroRCCMParent                          = $model->normalizeNum($NumeroRCCMParent);							
								 
						         $registreParent                            = $model->findRow( $NumeroRCCMParent, "numero", null, false );
								 $dirigeants                                = ( $registreParent ) ? $registreParent->dirigeants() : array();
								 if(!$registreParent || !isset( $dirigeants[0] ) ) {
									 //$errorMessages[]                       = sprintf("Le numéro parent de %s n'a pas été trouvé", $NumeroRCCM);
									 continue;
								 } else {
									 $dirigeant                             = $dirigeants[0];
									 if(!$strNotEmptyValidator->isValid($NomDemandeur) ) {
										 $NomDemandeur                      = sprintf("%s %s", $dirigeant["lastname"], $dirigeant["firstname"]);
									 }
									 if(!$strNotEmptyValidator->isValid($NomCommercial) ) {
										 $NomCommercial                     = $registreParent->libelle;
									 } else {
										 $TypeModification                  = 2;
									 }
									 if(!$strNotEmptyValidator->isValid($LieuNaissance) ) {
										 $LieuNaissance                     = $dirigeant["lieunaissance"];
									 }
									 if(!$strNotEmptyValidator->isValid($Pays) ) {
										 $Pays                              = $dirigeant["country"];
									 }
									 if(!$strNotEmptyValidator->isValid($Telephone) ) {
										 $Telephone                         = $registreParent->telephone;
									 }
									 if(!$strNotEmptyValidator->isValid($ArticlePrincipale) ) {
										 $ArticlePrincipale                = $registreParent->description;
									 } else {
										 $TypeModification                  = 4;
									 }
									 if(!$strNotEmptyValidator->isValid($AdressePhysique) ) {
										 $AdressePhysique                   = $registreParent->adresse;
									 }
								 }
								 if(!$strNotEmptyValidator->isValid($NomDemandeur) || !$strNotEmptyValidator->isValid($DateDemande) || !$strNotEmptyValidator->isValid($NomCommercial) || !$strNotEmptyValidator->isValid($DateNaissance)) {
								     continue;
								 }
								 $registreData["numero"]                    = $NumeroRCCM;
								 $registreData["numparent"]                 = $NumeroRCCMParent;
								 $registreData["type_modification"]         = $TypeModification;
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
									 $registreData["sexe"]                  = $contentRegistre->sexe;
									 $registreData["passport"]              = $contentRegistre->passport;
									 $registreData["situation_matrimonial"] = $contentRegistre->situation_matrimonial;
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
            if(!empty( $errorMessages ) ){
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
				$this->redirect("admin/registremodifications/importdocubasecsv/annee/".$nextYear);
			}				
		}
		$this->view->data         = $defaultData;
		$this->view->annees       = $annees;
	    $this->view->localites    = $localites;
		
		$this->render("docubasecsv");	
	}
	
	public function importsiguedataAction()
	{
		@ini_set('memory_limit', '512M');		
		$this->view->title          = "Importer des données depuis un fichier CSV SIGUE";
		$model                      = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$modelDocument              = $this->getModel("document");
		$getParams                  = $this->_request->getParams();
		$localites                  = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$localiteIDS                = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                    "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$defaultInitData            = array("annee" =>2016,"localite"=>"OUA","folderstocheck"=>"F:\\ERCCM");
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$imported                   = array();
		$rccms                      = array();
		$rccmDocuments              = array();
		$rccmNumeros                = array();
		$rccmDates                  = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
            $me                     = Sirah_Fabric::getUser();
			$modelTable              = $me->getTable();
			$dbAdapter              = $modelTable->getAdapter();
			$prefixName             = $tablePrefix = $modelTable->info("namePrefix");			
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
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
			
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			$csvStoreFilePath       = $me->getDatapath() . DS . time() . "mySigueData.csv";
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvStoreFilePath, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("NumeroRCCM","DateRCCM","AncienRCCM","NomCommercial","Denomination","Description","Nom","Prenom","Telephone","Adresse","DateNaissance","LieuNaissance","Sexe","NumeroPiece");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvStoreFilePath,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines       = $csvRows = $csvAdapter->getLines();
					$csvItems       = 1;
					if( isset($csvRows[0]) ) {
					    unset($csvRows[0]);
					}
					 if( count(  $csvRows ) ) {
						 foreach($csvRows as $csvKey => $csvRow ) {
							     $DateDemande          = (isset($csvRow["DateRCCM"]     ))?$csvRow["DateRCCM"]                                       : "";
								 $AncienRCCM           = (isset($csvRow["AncienRCCM"]   ))?trim($stringFilter->filter($csvRow["AncienRCCM"]),"-")    : "";
								 $NumeroRCCM           = (isset($csvRow["NumeroRCCM"]   ))?trim($stringFilter->filter($csvRow["NumeroRCCM"]),"-")    : "";
								 $NomCommercial        = (isset($csvRow["NomCommercial"]))?trim($stringFilter->filter($csvRow["NomCommercial"]),"-") : "";
								 $Denomination         = (isset($csvRow["Denomination"] ))?trim($stringFilter->filter($csvRow["Denomination"]) ,"-") : "";
								 $Nom                  = (isset($csvRow["Nom"]          ))?trim($stringFilter->filter($csvRow["Nom"]),"")           : "";
								 $Prenom               = (isset($csvRow["Prenom"]       ))?$stringFilter->filter($csvRow["Prenom"])        : "";
								 $Telephone            = (isset($csvRow["Telephone"]    ))?$stringFilter->filter($csvRow["Telephone"])     : "";
								 $Adresse              = (isset($csvRow["Adresse"]      ))?$stringFilter->filter($csvRow["Adresse"])       : "";
								 $Description          = (isset($csvRow["Description"]  ))?$stringFilter->filter($csvRow["Description"])   : "";
								 $DateNaissance        = (isset($csvRow["DateNaissance"]))?$stringFilter->filter($csvRow["DateNaissance"]) : "";
								 $LieuNaissance        = (isset($csvRow["LieuNaissance"]))?$stringFilter->filter($csvRow["LieuNaissance"]) : "";
								 $NumeroPiece          = (isset($csvRow["NumeroPiece"]  ))?$stringFilter->filter($csvRow["NumeroPiece"])   : "";
								 $Sexe                 = (isset($csvRow["Sexe"]         ))?$stringFilter->filter($csvRow["Sexe"])          : "M";
								 $NumParent            = "";
								 $zendDate             = null;
								 
								 if(strlen($NumeroRCCM) < 10 ) {
									$errorMessages[]   = sprintf("Le numéro RCCM %s de SIGUE est invalide", $NumeroRCCM);
									continue;
								 }
								 if(Zend_Date::isDate($DateDemande,"dd/mm/YYYY")) {
									  $zendDate          = new Zend_Date($DateDemande,"dd/mm/YYYY");								 
						         } elseif( Zend_Date::isDate($DateDemande,"YYYY-MM-dd") ) {
									  $zendDate          = new Zend_Date($DateDemande,"YYYY-MM-dd");
								 } elseif( Zend_Date::isDate($DateDemande, Zend_Date::ISO_8601) ) {
									  $zendDate          = new Zend_Date($DateDemande, Zend_Date::ISO_8601);
						         } else {
									  $zendDate          = null;
								 }
								 if(null== $zendDate ) {
									$errorMessages[]     = sprintf("La date du RCCM N° %s de SIGUE est invalide",$NumeroRCCM, $DateDemande);
									continue;
								 }
								 if(($NomCommercial=="NULL") || !$strNotEmptyValidator->isValid($NomCommercial) ) {
									 $errorMessages[]    = sprintf("Le nom commercial du numéro RCCM N° %s de SIGUE est invalide",$NumeroRCCM, $DateDemande);
									 continue;
								 }								 
								 $numLocalite            = trim(substr($NumeroRCCM, 2, 3));
								 $numYear                = trim(substr($NumeroRCCM, 5, 4));
								 $numTypeRegistre        = trim(substr($NumeroRCCM, 9, 1));
								 if( $numYear>2015 ) {
									 $NumeroRCCM         = $AncienRCCM;
									 $numLocalite        = trim(substr($NumeroRCCM, 2, 3));
								     $numYear            = trim(substr($NumeroRCCM, 5, 4));
								     $numTypeRegistre    = trim(substr($NumeroRCCM, 9, 1));
								 }	
                                 if( $numTypeRegistre!="M" ) {
									 continue;
								 } else {
									 $AncienNumeroRCCM   = $AncienRCCM;
									 $AncienNumLocalite  = trim(substr($AncienNumeroRCCM, 2, 3));
								     $AncienNumYear      = trim(substr($AncienNumeroRCCM, 5, 4));
								     $AncienNumType      = trim(substr($AncienNumeroRCCM, 9, 1));									 
									 if(!isset($annees[$AncienNumYear]) || !isset($localites[$AncienNumLocalite]) || ($AncienNumType=="M") ) {
										 continue;
									 }
									 $NumParent          = $model->normalizeNum($AncienNumeroRCCM,$AncienNumYear,$AncienNumLocalite);	
								 }	                                								 
								 $cleanNumRccm           = $model->normalizeNum($NumeroRCCM, $numYear, $numLocalite);								 
								 $numKey                 = trim(substr($NumeroRCCM, 10));
								 $checkedRccmRow         = $model->findRow($cleanNumRccm, "numero", null, false );
								 if( $checkedRccmRow ) {
									 continue;
								 }
								 if( empty($numLocalite) || empty($numYear) ) {
									 $errorMessages[]    = sprintf("Le numéro RCCM %s de SIGUE est invalide", $NumeroRCCM);
									 continue;
								 }
								 $sigueData                              = array("numero"=>$cleanNumRccm,"nom_commercial"=>$NomCommercial,"nom"=>$Nom,"prenom"=>$Prenom,"date_enregistrement"=>$DateDemande,"telephone"=>$Telephone,"numparent"=>$NumParent,
								                                                 "lieu_naissance"=>$LieuNaissance,"date_naissance"=>$DateNaissance,"sexe"=>$Sexe,"passport"=>$NumeroPiece,"description"=>$Description,"adresse"=>$Adresse);								 								 
								 $searchInDbSql                          = "SELECT * FROM ".$prefixName."rccm_registre_indexation WHERE numero=\"".$cleanNumRccm."\"";
						         $contentRegistre                        = $dbAdapter->fetchRow( $searchInDbSql, array(), 5);
								 if( $contentRegistre ) {
									 if(!$dbAdapter->update( $prefixName . "rccm_registre_indexation",$sigueData, array("numero='".$cleanNumRccm."'"))) {
										 $errorMessages[]                = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour",$NumeroRCCM);
									 } else {
										 $imported[]                     = $NumeroRCCM;
									 }
								 } else {
									 $sigueData["situation_matrimonial"] = "Celibataire";	
									 $sigueData["capital"]               = 0;
									 $sigueData["type_modification"]     = 7;
									 
									 if(!$dbAdapter->insert($prefixName  . "rccm_registre_indexation", $sigueData)) {
										 $errorMessages[]                = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour",$cleanNumRccm);
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
		$defaultInitData          = array("srcpath"=>"C:\\ERCCM\\DATA","checkpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS","destpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS","localite"=> "OUA","annee"=>2000,"nbre_documents"=>1000,"sigar_dbhost" => "", "");
				
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
			$rccmSearchKey        = sprintf("BF%s%dM", $localite, $annee);
			$rccmPSFiles          = glob( $documentSrcRootPath."/*/".$rccmSearchKey."*-PS.pdf");
			$i                    = 0;
			if( count(   $rccmPSFiles ) ) {
				foreach( $rccmPSFiles as $rccmPSFile) {
						 $csvRowData           = array();
					     $numRccm              = $numero = str_ireplace(array("-FR","-ST",".pdf","-PS"),"", basename($rccmPSFile));
						 $checkRccmRow         = $model->findRow( $numRccm, "numero", null, false );
						 $checkIndexationFiles = (is_dir($checkPath))?glob($checkPath."/*/".$localite."/".$annee."/".$numRccm.".pdf" ) : array();
						 $checkinMissingPath   = "G:\\MISSINGS". DS . $localite. DS . $annee . DS .  $numRccm . DS . $numRccm."-PS.pdf" ;
						 if( file_exists($checkinMissingPath) ) {
							 continue;
						 }
						 if($i===$nbreDocuments ) {
							 break;
						 }
						 if( count($checkIndexationFiles) ) {
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
						 $csvRowData["type_modification"]        = "";
						 if($contentRegistre ) {							
						    $csvRowData["parent"]                = $contentRegistre->numparent;
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
                            $csvRowData["type_modification"]     = $contentRegistre->type_modification;							
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
										$csvRowData["parent"]                = 0;
										$csvRowData["nationalite"]           = "BF";
										$csvRowData["situation_matrimonial"] = "Célibataire";
										$csvRowData["nom_commercial"]        = strtoupper($nomCommercial);
										$csvRowData["description"]           = strtoupper(trim(str_replace(array("TELEPHONE",":","NOM COMMERCIAL","","DEMINATION","RCCM","DIRIGEANT",$numero,$searchNum2,$searchNum3,
																						  $searchNum,$telToReplace,$lastname,$firstname,$nomCommercial,$telephone),"", $registreStr)));
										$csvRowData["parent"]                = "";												  
								}
							}
						 }
						  
                         /*if( empty( $csvRowData["parent"] ) ) {
							 continue;
						 }
                         if(!$registreParent = $model->findRow( $csvRowData["parent"], "numero", null, false ) ) {
							 continue;
						 }		*/					 
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
				$csvHeader   = array("numero","parent","nom_commercial","date_enregistrement","description","nom","prenom","lieu_naissance","date_naissance","sexe","adresse","telephone","passport","type_modification");
				$csvFilename = time().sprintf("rccmExcel-BF%s%dM_du%s.csv", $localite, $annee, date("dmY"));
				$csvTmpFile  = $documentDestRootPath . DS .  $csvFilename;	
				$csvAdapter  = Sirah_Filesystem_File::fabric("Csv", array("filename"=>$csvTmpFile,"has_header"=>true,"header"=>$csvHeader), "wb+" );
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
		return $result;
	}
	
	 
}