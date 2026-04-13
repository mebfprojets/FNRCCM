<?php
ini_set('memory_limit', '1024M'); 
require_once("tcpdf/tcpdf.php");
require_once("Fpdi/fpdi.php");
require 'D:\webserver/www/Xpdf/vendor/autoload.php';
require 'D:\webserver/www/erccm/libraries/Forceutf8/vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use XPDF\PdfToText;
use \ForceUTF8\Encoding;

class Admin_RegistresuretesController extends Sirah_Controller_Default
{
		
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "ERCCM : Historique des RCCM de type Sûrétés"  ;
		
		$model              = $this->getModel("registresurete");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		$modelSureteType    = $this->getModel("suretetype");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = array();
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
		
		$filters              = array("libelle"  => null,"numero"=> null,"domaineid" => 0,"creatorid"=> 0,"localiteid" => 0, "annee" => 0,"nom" => null, "prenom" => null,"searchQ" => null,
		                              "date_year"=> null,"date_month" => null,"date_day" => null,"periode_start_year" => DEFAULT_START_YEAR,"country" => null,"sexe" => null,
				                      "periode_end_year"=> DEFAULT_END_YEAR, "periode_start_month"=> DEFAULT_START_MONTH,"periode_start_day"=> DEFAULT_START_DAY,"type"=> null,
				                      "periode_end_day" => DEFAULT_END_DAY , "periode_end_month"  => DEFAULT_END_MONTH,"passport"=>null,"telephone"=>null, "parentid" => 0);		
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
				$filters["prenom"]    = implode(" ", $nameToArray );
			} elseif( count($nameToArray) == 2)	 {
				$filters["nom"]       = (isset($nameToArray[0])) ? $nameToArray[0] : "" ;
				$filters["prenom"]    = (isset($nameToArray[1])) ? $nameToArray[1] : "" ;
			} elseif( count($nameToArray) == 1)	 {
				$filters["name"]      = (isset($nameToArray[0])) ? $nameToArray[0] : "" ;
			}				
		}
		/*if( !$me->isAdmin() ) {
			 $filters["localiteid"]= $me->city;
		}
		if( $me->isOPS() ) {
			$filters["creatorid"] = $me->userid;
		}*/
        if((isset( $filters["date_month"]) && intval( $filters["date_month"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate        = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"] = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset( $filters["periode_end_month"]) && intval( $filters["periode_end_month"])) && (isset( $filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"]  ) && intval( $filters["periode_end_day"]  )) && (isset( $filters["periode_start_day"])   && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=> $filters["periode_start_year"] ,"month"=> $filters["periode_start_month"],"day" => $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=> $filters["periode_end_year"]   ,"month"=> $filters["periode_end_month"]  ,"day" => $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart ) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd   ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$registres                    = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                    = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->domaines  = $modelDomaine->getSelectListe( "Selectionnez un secteur d'articles", array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users     = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->statuts   = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $this->view->maxitems = $pageSize;	
        $this->view->types     = $modelSureteType->getSelectListe("Selectionnez le type", array("type","libelle"), array() , 0 , null , false);		
	}
	
	public function createAction()
	{
		$this->view->title                   = "Enregistrer un registre de type `Sûrétés`";
		
		$model                               = $this->getModel("registre");
		$modelSurete                         = $this->getModel("registresurete");
		$modelRepresentant                   = $this->getModel("representant");
		$modelLocalite                       = $this->getModel("localite");
		$modelSureteType                     = $this->getModel("suretetype");
		$me                                  = Sirah_Fabric::getUser();
				
		$sureteTypes                         = $modelSureteType->getSelectListe("Selectionnez le type"   , array("type"      , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "code"), array() , 0 , null , false);
		
		$errorMessages                       = array();
		$registreid                          = 0;
		$parentid                            = intval($this->_getParam("parentid"     , 0   ));
		$parentNumRc                         = strip_tags($this->_getParam("parentnum", null));
		
		$parent                              = (!empty($parentNumRc))? $model->findRow($parentNumRc,"numero", null, false):(($parentid)? $model->findRow($parentid,"registreid",null,false) : null);
		$parentid                            = ( $parent ) ? $parent->registreid   : 0;
		$parentNumRc                         = ( $parent ) ? $parent->numero       : null;
		$representants                       =   $dirigeants =( $parent   ) ? $parent->dirigeants() : array();
		$suretes                             = ( $parent ) ? $parent->suretes()    : array();			
		$representantid                      = (isset($representants[0]["representantid"]))? intval($representants[0]["representantid"]) : 0;		
		$registreDefaultData                 = ( $parent ) ? $parent->toArray()    : $model->getEmptyData();
        $registreDefaultData["numero"]		 = $registreDefaultData["description"] = null;
		$registreDefaultData["registreid"]   = $registreDefaultData["type"]        = 0;
		$sureteDefaultData                   = $modelSurete->getEmptyData();
		$representantDefaultData             = $modelRepresentant->getEmptyData();
		$registreDefaultData["telephone"]    = (isset( $representantDefaultData["telephone"] )) ? $representantDefaultData["telephone"] : "";
		$registreDefaultData["adresse"]      = (isset( $representantDefaultData["adresse"]   )) ? $representantDefaultData["adresse"]   : "";
		
		$defaultData                         = array_merge( $representantDefaultData, $sureteDefaultData, $registreDefaultData);
		$defaultData["date_year"]            = (intval($defaultData["date"]                  )) ? date("Y", intval($defaultData["date"])) : intval($this->_getParam("annee", $me->getParam("default_year", DEFAULT_YEAR)));		
		$defaultData["date_month"]           = (intval($defaultData["date"]                  )) ? date("m", intval($defaultData["date"])) : sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));
		$defaultData["date_day"]             = (intval($defaultData["date"]                  )) ? date("d", intval($defaultData["date"])) : null;
		$defaultData["periodstart_year"]     = $defaultData["periodend_year"]  = intval($this->_getParam("annee", $me->getParam("default_year", DEFAULT_YEAR)));		
		$defaultData["periodstart_month"]    = $defaultData["periodend_month"] = sprintf("%02d",intval($this->_getParam("month", $me->getParam("default_start_month", DEFAULT_START_MONTH))));	
		$defaultData["periodstart_day"]      = $defaultData["periodend_day"]   = null;	
		$defaultData["date_naissance_year"]  = null;
		$defaultData["date_naissance_month"] = null;
		$defaultData["date_naissance_day"]   = null;
		$defaultData["parentid"]             = $parentid;
		$defaultData["check_documents"]      = 0;
		$defaultData["find_documents"]       = 0;
		$defaultData["find_documents_src"]   = $fileSource = (is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
						
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$registre_data                   = array_merge( $registreDefaultData    , array_intersect_key( $postData, $registreDefaultData     ));
			$surete_data                     = array_merge( $sureteDefaultData      , array_intersect_key( $postData, $sureteDefaultData       ));
			$representant_data               = array_merge( $representantDefaultData, array_intersect_key( $postData, $representantDefaultData ));	
            				           			
			$userTable                       = $me->getTable();
			$dbAdapter                       = $userTable->getAdapter();
			$prefixName                      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
			$numero                          = $cleanRegistreNumero = preg_replace("/\s/", "", $stringFilter->filter(strtoupper($registre_data["numero"])));
			$libelle                         = $stringFilter->filter( $registre_data["libelle"] );
			$findDocuments                   = (isset($postData["find_documents"] ))? intval($postData["find_documents"]) : 0;
			$checkDocuments                  = (isset($postData["check_documents"]))? intval($postData["check_documents"]): 0;
			$defaultDocumentSrc              = $filesSource = (isset($postData["find_documents_src"]))? $stringFilter->filter($postData["find_documents_src"]): DEFAULT_FIND_DOCUMENTS_SRC;								
			 
			if(!is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}
			if(!$parent )  {
				$errorMessages[]             = "Veuillez sélectionner le registre de commerce principal";
			} else {
				$registre_data["parentid"]   = $parent->registreid;
				$registre_data["libelle"]    = $parent->libelle;
			}
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]             = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false )) {
				$errorMessages[]             = sprintf("Un registre existant porte le numéro %s , veuillez entrer un numéro différent", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle ) && $parent ) {
				$libelle                     = sprintf("SURETE %s", $parent->libelle);
		    } elseif(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]             = "Veuillez renseigner le titre de la sûrété";
			} 
			if( $model->findRow( $libelle , "libelle" , null , false )) {
				$libelle                     = ($parent ) ? sprintf("RC n° %s : %s", $parent->numero, $libelle ) : sprintf("RC n° %s : %s", $numero, $libelle ) ;
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
				$registre_data["localiteid"] = intval($registre_data["localiteid"] ) ;
			}			
			$localiteCode                    = (isset($localitesCodes[$registre_data["localiteid"]])) ? $localitesCodes[$registre_data["localiteid"]]           : "";	
			$dateYear                        = (isset($postData["date_year"] ))? $stringFilter->filter($postData["date_year"])                                  : "0000";
			$dateMonth                       = (isset($postData["date_month"]))? $stringFilter->filter($postData["date_month"])                                 : "00";
			$dateDay                         = (isset($postData["date_day"]) && ($postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			
			$periodStartYear                 = (isset($postData["periodstart_year"] ))? $stringFilter->filter($postData["periodstart_year"])                    : "0000";
			$periodStartMonth                = (isset($postData["periodstart_month"]))? $stringFilter->filter($postData["periodstart_month"])                   : "00";
			$periodStartDay                  = (isset($postData["periodstart_day"]  ))? $stringFilter->filter($postData["periodstart_day"]  )                   : "00";
			
			$periodEndYear                   = (isset($postData["periodend_year"]   ))? $stringFilter->filter($postData["periodend_year"])                      : "0000";
			$periodEndMonth                  = (isset($postData["periodend_month"]  ))? $stringFilter->filter($postData["periodend_month"])                     : "00";
			$periodEndDay                    = (isset($postData["periodend_day"]    ))? $stringFilter->filter($postData["periodend_day"]  )                     : "00";
			
			$zendPeriodStart                 = new Zend_Date(array("year"=> $periodStartYear,"month" => $periodStartMonth,"day" => $periodStartDay));
			$zendPeriodEnd                   = new Zend_Date(array("year"=> $periodEndYear  ,"month" => $periodEndMonth  ,"day" => $periodEndDay));			
			$zendDate                        = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			
			$registreYear                    = substr( $numero, 5, 4);	
            $numeroPrefixToCheck             = sprintf("BF%s", $localiteCode);	
            /*	
			if( substr( $numero, 0, 5) != $numeroPrefixToCheck ) {
				$errorMessages[]             = "Le numéro attribué à ce registre n'est pas valide.";
			}							
					
			 if(stripos($numero, $numeroPrefixToCheck) === FALSE) {
				$errorMessages[]             = sprintf("Le numéro RC %s que vous avez indiqué ne semble pas correct. Il devrait commencer par %s", $numero, $numeroPrefixToCheck);
			}*/
            if( strlen($registre_data["numero"]) != 14) {
				$errorMessages[]             = sprintf("La sûrété numéro %s que vous avez indiqué ne semble pas correct. Il doit comporter 14 caractères", $registre_data["numero"] );
			}
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;

            if(!intval($registre_data["date"])) {
				$errorMessages[]             = "Veuillez indiquer une date d'inscription valide";
			}
			$registre_data["type"]           = 3;
			$registre_data["statut"]         = 1;
			$registre_data["category"]       = sprintf("S%d", count($suretes));
			$registre_data["checked"]        = intval($checkDocuments);
			$registre_data["description"]    = $stringFilter->filter( $registre_data["description"]);
			$registre_data["adresse"]        = $stringFilter->filter( $registre_data["adresse"]    );
			$registre_data["telephone"]      = $stringFilter->filter( $registre_data["telephone"]  );
			$registre_data["creatorid"]      = $me->userid;
			$registre_data["creationdate"]   = time();	
			$registre_data["updateduserid"]  = 0;
			$registre_data["updatedate"]     = 0;
			$registre_data["domaineid"]      = intval( $registre_data["domaineid"] ) ;
			
            $surete_data["periodstart"]		 = ( null!= $zendPeriodStart    ) ? $zendPeriodStart->get(Zend_Date::TIMESTAMP) : 0;
			$surete_data["periodend"]		 = ( null!= $zendPeriodEnd      ) ? $zendPeriodEnd->get(  Zend_Date::TIMESTAMP) : 0;
			$surete_data["valeur"]           = ( isset( $postData["valeur"])) ? floatval(preg_replace('/[^0-9]/','', $postData["valeur"])) : 0;
			$surete_data["estate"]           = ( isset( $postData["estate"])) ? $stringFilter->filter( $postData["estate"]) : $registre_data["description"];
			$surete_data["titre"]            = ( isset( $postData["titre"] )) ? $stringFilter->filter( $postData["titre"] ) : $libelle;
			$surete_data["type"]             = ( isset( $postData["type"]  )) ? intval( $postData["type"]  )                : 0;
			$surete_data["creationdate"]     = time();	
			$surete_data["updateduserid"]    = 0;
			$surete_data["updatedate"]       = 0;
			$surete_data["creatorid"]        = $me->userid;
			
            /*if( !intval($surete_data["periodstart"] )) {
				$errorMessages[]             = "Veuillez indiquer une période de validité valide: Date de début";
			}
            if( intval($surete_data["periodend"]) <=intval($surete_data["periodstart"] ) ) {
				$errorMessages[]             = "Veuillez indiquer une période de validité valide: Date de fin";
			}*/
            if( !$surete_data["valeur"] )	 {
				$errorMessages[]             = "Veuillez indiquer la valeur de la surété";
			}
			if(!intval( $surete_data["type"] ) || !isset( $sureteTypes[$surete_data["type"]])) {
				$errorMessages[]             = "Veuillez indiquer un type de surété valide";
			}
            if(!$strNotEmptyValidator->isValid($surete_data["titre"] )) {
				$errorMessages[]             = "Veuillez indiquer un titre valide";
			}							
			if(!$findDocuments ) {
				$documentsUploadAdapter      = new Zend_File_Transfer();
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
				if( $checkDocuments && empty($errorMessages) ) {
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
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le formulaire de la sûrété N° %s n'existe pas dans la source des documents %s", $numero, $rccmFormulaireFilepath);
				}
				if(!file_exists($rccmPersonnelFilepath)) {
					$errorMessages[]         = sprintf("L'indexation automatique a echoué car le fond de dossier de la sûrété N° %s n'existe pas dans la source des documents %s", $numero, $rccmPersonnelFilepath);
				}
				$checkRccmData               = array("formulaire" => $rccmFormulaireFilepath, "personnel" => $rccmPersonnelFilepath,"numero" => $numero);
				if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
					$errorMessages[]         =  sprintf("Les fichiers du RCCM %s ne sont pas valides depuis la source. Veuillez vérifier le contenu des documents %s", $numero, $rccmPersonnelFilepath);
				}
			}				
			if(!count($errorMessages  )) {
				if( $dbAdapter->insert( $prefixName . "rccm_registre", $registre_data) ) {
					$registreid    = $dbAdapter->lastInsertId();
                    $defaultParams = array("default_start_month"=> $dateMonth,"default_year"=> $dateYear,"default_localiteid"=> $registre_data["localiteid"],
					                       "default_domaineid"=> $registre_data["domaineid"],"default_find_documents_src"=> DEFAULT_FIND_DOCUMENTS_SRC, 
										   "default_check_documents"=> DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY, "default_find_documents" => DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY);
                    $myParams              = $me->getParams();
                    $myPreferedParams      = array_merge( $myParams, $defaultParams);				  
					$me->setParams( $myPreferedParams );  					  
					$surete_data["registreid"] = $registreid;					
					if( $dbAdapter->insert( $prefixName . "rccm_registre_suretes", $surete_data )) {
						if( !$representantid ) {
							//On enregistre les informations de l'representant					  
					        $dateNaissanceYear                    = (isset($postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
					        $dateNaissanceMonth                   = (isset($postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
					        $dateNaissanceDay                     = (isset($postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
					  
					        $representant_data["datenaissance"]   = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					        $representant_data["lieunaissance"]   = $stringFilter->filter( $representant_data["lieunaissance"]  );
					        $representant_data["marital_status"]  = $stringFilter->filter( $representant_data["marital_status"] );
					        $representant_data["nom"]             = $stringFilter->filter( $representant_data["nom"] );
					        $representant_data["prenom"]          = $stringFilter->filter( $representant_data["prenom"]   );
					        $representant_data["adresse"]         = $stringFilter->filter( $representant_data["adresse"]  );
					        $representant_data["city"]            = 0;
					        $representant_data["profession"]      = ( isset( $postData["fonction"])) ? $stringFilter->filter($postData["fonction"]) : "GERANT";
					        $representant_data["country"]         = $stringFilter->filter( $representant_data["country"]  );
					        $representant_data["email"]           = $stringFilter->filter( $representant_data["email"]    );
					        $representant_data["telephone"]       = $stringFilter->filter( $representant_data["telephone"]);
					        $representant_data["passport"]        = $stringFilter->filter( $representant_data["passport"] );
					        $representant_data["sexe"]            = $stringFilter->filter( $representant_data["sexe"] );
					        $representant_data["structure"]       = "";
					        $representant_data["creatorid"]       = $me->userid;
					        $representant_data["creationdate"]    = time();
					        $representant_data["updateduserid"]   = 0;
					        $representant_data["updatedate"]      = 0;
							if( $dbAdapter->insert( $prefixName . "rccm_registre_representants", $representant_data ) ) {
					  	        $representantid                   = $dbAdapter->lastInsertId();
								$dirigeant_data                   = array();
								$dirigeant_data["registreid"]     = $registreid;
								$dirigeant_data["representantid"] = $representantid;
								$dirigeant_data["fonction"]       = $representant_data["profession"];
								$dbAdapter->insert( $prefixName   . "rccm_registre_dirigeants", $dirigeant_data );
							}
						}  else {
								$dirigeant_data                   = array();
								$dirigeant_data["registreid"]     = $registreid;
								$dirigeant_data["representantid"] = $representantid;
								$dirigeant_data["fonction"]       = "GERANT";
								$dbAdapter->insert( $prefixName   . "rccm_registre_dirigeants", $dirigeant_data );
						}							
                        if(!$findDocuments ) {
								   //On essaie d'enregistrer les documents du registre
					  	  	$modelDocument                        = $this->getModel("document");					  	  	       
					  	  	$rcPathroot                           = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS."SURETES".DS.$localiteCode.DS.$registreYear. DS . $numero;
					  	  	  
					  	  	$documentData                         = array();
					  	  	$documentData["userid"]               = $me->userid;
					  	  	$documentData["category"]             = 1;
					  	  	$documentData["resource"]             = "registresuretes";
					  	  	$documentData["resourceid"]           = 0;
					  	  	$documentData["filedescription"]      = $registre_data["description"];
					  	  	$documentData["filemetadata"]         = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	  	$documentData["creationdate"]         = time();
					  	  	$documentData["creatoruserid"]        = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	  	$formulairePath                       = $rcPathroot . DS . $numero."-FR.pdf";
					  	  	$personnelPath                        = $rcPathroot . DS . $numero."-PS.pdf";
					  	  	$documentsUploadAdapter->addFilter("Rename", array("target" => $formulairePath, "overwrite" => true), "docmini");
							
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES". DS . $localiteCode . DS . $registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode, 0777 );
								}
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $registreYear);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $registreYear, 0777 );									   
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
					  	  	  	   	       $formulaireData["filename"]       = $modelDocument->rename("FORMULAIRE", $numero);
					  	  	  	   	       $formulaireData["filepath"]       = $formulairePath;
					  	  	  	   	       $formulaireData["access"]         = 0 ;
					  	  	  	   	       $formulaireData["filextension"]   = "pdf";
					  	  	  	   	       $formulaireData["filesize"]       = floatval( $miniFileSize );
					  	  	  	   	       if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
					  	  	  	   	   	       $documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	   	       $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
											   if( $parent ) {
												   $parentFormulaireData             = $formulaireData;
												   $parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE SURETE N° %s", $numero), $parent->numero);
												   if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
													   $parentDocumentid             = $dbAdapter->lastInsertId();
													   $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => -1 ));
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
					  	  	  		$personnelDocData["filename"]         = $modelDocument->rename("FOND DE DOSSIER", $numero);
					  	  	  		$personnelDocData["filepath"]         = $personnelPath;
					  	  	  		$personnelDocData["access"]           = 6;
					  	  	  		$personnelDocData["filextension"]     = "pdf";
					  	  	  		$personnelDocData["filesize"]         = floatval($personnelDocFileSize);
					  	  	  		if( $dbAdapter->insert($prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  			$documentid                       = $dbAdapter->lastInsertId();
					  	  	  			$dbAdapter->insert($prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid" => $documentid, "access" => 6));
										if( $parent ) {
											$parentPersonnelData              = $personnelDocData;
											$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
											if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentPersonnelData )) {
											    $parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5));
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
				            $rccmFormulaireFilepath = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS. $numero."-FR.pdf";
			                $rccmPersonnelFilepath  = $filesSource.DS.$localiteCode.DS.$registreYear.DS.$numero.DS. $numero."-PS.pdf";
							$rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode.DS.$registreYear.DS.$numero;
							if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
								$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
								$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
								$modelDocument                  = $this->getModel("document");					  	  	       					  	  	  
					  	  	    $documentData                   = array();
					  	  	    $documentData["userid"]         = $me->userid;
					  	  	    $documentData["category"]       = 1;
					  	  	    $documentData["resource"]       = "registresurete";
					  	  	    $documentData["resourceid"]     = 0;
					  	  	    $documentData["filedescription"]= $registre_data["description"];
					  	  	    $documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $registreYear);
					  	  	    $documentData["creationdate"]   = time();
					  	  	    $documentData["creatoruserid"]  = $me->userid;
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."SURETES". DS . $localiteCode . DS . $registreYear) ) {
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES")) {
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES");
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES", 0777 );
									}
									if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode)) {
										@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode);
										@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode, 0777 );
									}
									    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $registreYear);
									    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $registreYear, 0777 );									   
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
											$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE SURETE N° %s", $numero), $parent->numero);
										    if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
												$parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => -1 ));
											}
										}
									} else {
					  	  	  	   	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	   	}
								} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la sûrété numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
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
											$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
											if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
											    $parentDocumentid             = $dbAdapter->lastInsertId();
											    $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5 ));
										    }
										}
									} else {
					  	  	  	   	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	   	}
								} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la sûrété numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
								}
							} else {
										$errorMessages[]                = sprintf("L'indexation automatique de la sûrété numéro %s a echoué car les documents n'ont pas été trouvés", $numero);
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
				    echo ZendX_JQuery::encodeJson(array("success" => "La sûrété a été enregistrée avec succès"));
				    exit;
			    }
				$this->setRedirect("Les informations de la surété ont été enregistrées avec succès" , "success");
				$this->redirect("admin/registresuretes/infos/registreid/".$registreid);
			}
		}		
		if( count( $errorMessages ) ) {
			if( intval($registreid)) {
				$dbAdapter->delete($prefixName."rccm_registre"              , "registreid=".$registreid);
				$dbAdapter->delete($prefixName."rccm_registre_suretes"      , "registreid=".$registreid);
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
		$this->view->types          = $sureteTypes;
		$this->view->parentid       = $parentid;
		$this->view->parentNum      = $parentNumRc;
		$this->view->representantid = $representantid;
	}
	
	
	public function editAction()
	{
		$this->view->title = " Mettre à jour les informations d'une sûrété";
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id", 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect(   "admin/registresuretes/list");
		}		
		$model                 = $this->getModel("registre");
		$modelSurete           = $this->getModel("registresurete");
		$modelSureteType       = $this->getModel("suretetype");
		$modelRepresentant     = $this->getModel("representant");
		$modelLocalite         = $this->getModel("localite");
		$modelDocument         = $this->getModel("document");
 	
		$registre              = $model->findRow(      $registreid, "registreid", null , false);		
		$surete                = $modelSurete->findRow($registreid, "registreid", null , false);
        $representant          = ( $surete ) ? $surete->representant() : null;		
		if(!$registre || !$surete || !$representant ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registresuretes/list");
		}
		$defaultParent                       =($registre->parentid) ? $model->findRow($registre->parentid, "registreid", null , false) : null;
		$sureteTypes                         = $modelSureteType->getSelectListe("Selectionnez le type"     , array("type" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe(  "Selectionnez une localité", array("localiteid", "libelle"), array() , 0 , null , false);
		$localitesCodes                      = $modelLocalite->getSelectListe(  "Selectionnez une localité", array("localiteid", "code"), array() , 0 , null , false);	
		$registreData                        = $registre->toArray();
		$sureteData                          = $surete->toArray();
		$representantData                    = $representant->toArray();
		$representantid                      = intval($this->_getParam("representantid", $representant->representantid));
		$parentid                            = intval($this->_getParam("parentid"      , $registre->parentid));
		$parentNumRc                         = strip_tags($this->_getParam("parentnum" , (( $defaultParent )? $defaultParent->numero : null )));
		$parent                              = (!empty($parentNumRc))? $model->findRow($parentNumRc,"numero", null, false):(($parentid)? $model->findRow($parentid,"registreid",null,false) : null);
		$parentid                            = ( $parent) ? $parent->registreid   : 0;
		$parentNumRc                         = ( $parent) ? $parent->numero       : null;
		
		$registreData["telephone"]           = (isset( $representantData["telephone"])) ? $representantData["telephone"] : "";
		$registreData["adresse"]             = (isset( $representantData["adresse"]  )) ? $representantData["adresse"]   : "";
		$defaultData                         = array_merge( $registreData, $sureteData, $representantData);
		$errorMessages                       = array();  		
		
		$defaultData["date_year"]            = date("Y", $registre->date);
		$defaultData["date_month"]           = date("m", $registre->date);
		$defaultData["date_day"]             = date("d", $registre->date);
		$defaultData["periodstart_year"]     = date("Y", $surete->periodstart);		
		$defaultData["periodstart_month"]    = date("m", $surete->periodstart);		
		$defaultData["periodstart_day"]      = date("d", $surete->periodstart);
		$defaultData["periodend_year"]       = date("Y", $surete->periodend  );		
		$defaultData["periodend_month"]      = date("m", $surete->periodend  );		
		$defaultData["periodend_day"]        = date("d", $surete->periodend  );
		$defaultData["date_naissance_year"]  = (isset($defaultData["datenaissance"] ))? date("Y", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["date_naissance_month"] = (isset($defaultData["datenaissance"] ))? date("m", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["date_naissance_day"]   = (isset($defaultData["datenaissance"] ))? date("d", strtotime($defaultData["datenaissance"])) : null;
		$defaultData["check_documents"]      = DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY;
		$defaultData["find_documents"]       = DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY;
		$defaultData["find_documents_src"]   = $fileSource = (is_dir( DEFAULT_FIND_DOCUMENTS_SRC)) ? DEFAULT_FIND_DOCUMENTS_SRC : "C:\ERCCM/DATA";
		
		if( $this->_request->isPost()) {
			$postData                        = $this->_request->getPost();
			$update_registre_data            = $registre_data = array_merge($registreData, array_intersect_key( $postData,$registreData));
			$update_surete_data              = $surete_data   = array_merge($sureteData  , array_intersect_key( $postData,$sureteData  ));
			/*$update_physique_data            = array_merge($physiqueData  , array_intersect_key( $postData,  $physiqueData) );*/
			$update_representant_data        = array_merge($representantData, array_intersect_key( $postData,  $representantData) );
			$me                              = Sirah_Fabric::getUser();
			$userTable                       = $me->getTable();
			$dbAdapter                       = $userTable->getAdapter();
			$prefixName                      = $userTable->info("namePrefix");
			$parentid                        = $update_registre_data["parentid"] = $registre_data["parentid"] = (isset( $postData["parentid"] )) ? intval($postData["parentid"]) : $registre->parentid;
			$parent                          = ( $parentid ) ? $model->findRow( $parentid, "parentid", null , false ) : null;	
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
			if( !is_dir($defaultDocumentSrc) ) {
				$defaultDocumentSrc          = $filesSource = "C:\ERCCM/DATA";
			}
			if(!$parent )  {
				$errorMessages[]             = "Veuillez sélectionner le registre de commerce principal";
			} else {
				$update_registre_data["parentid"]    = $parent->registreid;
			}
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]                     = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false ) && ( $registre->numero != $numero ) ) {
				$errorMessages[]                     = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$update_registre_data["numero"]      = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle ) && $parent ) {
				$libelle                             = sprintf("SURETE DU RC N° %s", $parent->numero);
		    } elseif(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                     = "Veuillez renseigner le titre de la sûrété";
			} 
			if( $model->findRow( $libelle , "libelle" , null , false )) {
				$libelle                             = ( $parent ) ? sprintf("RC n° %s : %s", $parent->numero, $libelle ) : sprintf("RC n° %s : %s", $numero, $libelle ) ;
			} else {
				$update_registre_data["libelle"]     = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $update_representant_data["nom"] ) || !$strNotEmptyValidator->isValid( $update_representant_data["prenom"])) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour le promoteur";
			}				
			if((!intval( $update_registre_data["localiteid"] ) || !isset( $localites[$update_registre_data["localiteid"]])) && !$parent ) {
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
			
			$periodStartYear                         = (isset($postData["periodstart_year"] ))? $stringFilter->filter($postData["periodstart_year"]) : "0000";
			$periodStartMonth                        = (isset($postData["periodstart_month"]))? $stringFilter->filter($postData["periodstart_month"]): "00";
			$periodStartDay                          = (isset($postData["periodstart_day"]  ))? $stringFilter->filter($postData["periodstart_day"]  ): "00";
			
			$periodEndYear                           = (isset($postData["periodend_year"]   ))? $stringFilter->filter($postData["periodend_year"] )  : "0000";
			$periodEndMonth                          = (isset($postData["periodend_month"]  ))? $stringFilter->filter($postData["periodend_month"])  : "00";
			$periodEndDay                            = (isset($postData["periodend_day"]    ))? $stringFilter->filter($postData["periodend_day"]  )  : "00";
			
			$zendDate                                = new Zend_Date(array("year"=> $dateYear,"month" => $dateMonth, "day" => $dateDay ));
			$zendPeriodStart                         = new Zend_Date(array("year"=> $periodStartYear,"month" => $periodStartMonth,"day" => $periodStartDay));
			$zendPeriodEnd                           = new Zend_Date(array("year"=> $periodEndYear  ,"month" => $periodEndMonth  ,"day" => $periodEndDay));
			$rcPathroot                              = APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode.DS.$dateYear.DS.$numero;
            $numeroPrefixToCheck                     = sprintf("BF%s%dS", $localiteCode, $dateYear);
			
			$registreYear                            = substr($numero, 5, 4 );
			
			/*if(stripos($update_registre_data["numero"], $numeroPrefixToCheck) === FALSE ) {
				$errorMessages[]                     = sprintf("La sûrété numéro %s que vous avez indiquée ne semble pas correct. Il devrait commencer par %s", $update_registre_data["numero"], $numeroPrefixToCheck);
			}*/
            if(strlen($update_registre_data["numero"])!= 14) {
				$errorMessages[]                       = sprintf("La sûrété numéro %s que vous avez indiquée ne semble pas correct. Il doit comporter 14 caractères", $update_registre_data["numero"] );
			}			
			
			$update_registre_data["domaineid"]         = intval( $update_registre_data["domaineid"] ) ;
			$update_registre_data["date"]              = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["type"]              = 3;
			$update_registre_data["checked"]           = intval($checkDocuments);
			$update_registre_data["statut"]            = intval( $update_registre_data["statut"]  );
			$update_registre_data["description"]       = $stringFilter->filter( $update_registre_data["description"]);
			$update_registre_data["adresse"]           = $stringFilter->filter( $update_registre_data["adresse"]    );
			$update_registre_data["telephone"]         = $stringFilter->filter( $update_registre_data["telephone"]  );
			$update_registre_data["updateduserid"]     = $me->userid;
			$update_registre_data["updatedate"]        = time();
			
			$update_surete_data["periodstart"]		   = ( null != $zendPeriodStart  ) ? $zendPeriodStart->get(Zend_Date::TIMESTAMP) : $update_registre_data["date"];
			$update_surete_data["periodend"]		   = ( null != $zendPeriodEnd    ) ? $zendPeriodEnd->get(  Zend_Date::TIMESTAMP) : ($update_registre_data["date"]+(3600*72));
			$update_surete_data["valeur"]              = (isset( $postData["valeur"])) ? floatval(preg_replace('/[^0-9]/','', $postData["valeur"])) : 0;
			$update_surete_data["estate"]              = (isset( $postData["estate"])) ? $stringFilter->filter( $postData["estate"]) : $update_registre_data["description"];
			$update_surete_data["titre"]               = (isset( $postData["titre"] )) ? $stringFilter->filter( $postData["titre"] ) : $libelle;
			$update_surete_data["type"]                = (isset( $postData["type"]  )) ? intval( $postData["type"]  ) : 0;
			
			/*if( !intval($update_surete_data["periodstart"] )) {
				$errorMessages[]             = "Veuillez indiquer une période de validité valide: Date de début";
			}
            if( intval($update_surete_data["periodend"]) <=intval($update_surete_data["periodstart"] ) ) {
				$errorMessages[]             = "Veuillez indiquer une période de validité valide: Date de fin";
			}*/
            if( !$update_surete_data["valeur"] )	 {
				$errorMessages[]             = "Veuillez indiquer la valeur de la surété";
			}
			if(!intval($update_surete_data["type"] ) || !isset( $sureteTypes[$update_surete_data["type"]])) {
				$errorMessages[]             = "Veuillez indiquer un type de surété valide";
			}
            if(!$strNotEmptyValidator->isValid($update_surete_data["titre"] )) {
				$errorMessages[]             = "Veuillez indiquer un titre valide";
			}
			
			//On enregistre les informations du representant
			$dateNaissanceYear                         = (isset($postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"] ) : "0000";
			$dateNaissanceMonth                        = (isset($postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay                          = (isset($postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
				
			$update_representant_data["datenaissance"] = sprintf("%04d-%02d-%s 00:00:00", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$update_representant_data["lieunaissance"] = $stringFilter->filter( $update_representant_data["lieunaissance"]  );
			$update_representant_data["marital_status"]= $stringFilter->filter( $update_representant_data["marital_status"] );
			$update_representant_data["nom"]           = $stringFilter->filter( $update_representant_data["nom"]            );
			$update_representant_data["prenom"]        = $stringFilter->filter( $update_representant_data["prenom"]         );
			$update_representant_data["adresse"]       = $stringFilter->filter( $update_representant_data["adresse"]        );
			$update_representant_data["email"]         = $stringFilter->filter( $update_representant_data["email"]          );
			$update_representant_data["passport"]      = $stringFilter->filter( $update_representant_data["passport"]       );
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
					$formulaireFilename               = (isset($_FILES["docmini"]["tmp_name"]    )) ? $_FILES["docmini"]["tmp_name"]     : null;	
					$personnelFilename                = (isset($_FILES["docoriginal"]["tmp_name"])) ? $_FILES["docoriginal"]["tmp_name"] : null;	
					$checkRccmData                    = array("formulaire" => $formulaireFilename, "personnel" => $personnelFilename, "numero" => $numero);
					if( false == $this->__checkRccmFiles($checkRccmData, $errorMessages )) {
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
			if( isset( $update_representant_data["representantid"])) {
				$representantid  = $update_representant_data["representantid"];
				unset( $update_representant_data["representantid"] );
			}
			if( isset( $update_surete_data["registreid"] ))  {
				unset( $update_surete_data["registreid"]);
			}
			$registre->setFromArray(     $update_registre_data     );
			$representant->setFromArray( $update_representant_data );
			$surete->setFromArray(       $update_surete_data       );
			if(empty($errorMessages)) {
				if( $registre->save() && $surete->save()) {
					$cleanRepresentantData  = array_intersect_key($update_representant_data, $representant->getEmptyData());
					$dbAdapter->update( $prefixName ."rccm_registre_representants",$cleanRepresentantData, "representantid=".$representantid);
					if(!$findDocuments ) {
						$documentData                   = array();
					  	$documentData["userid"]         = $me->userid;
					  	$documentData["category"]       = 1;
					  	$documentData["resource"]       = "registresurete";
					  	$documentData["resourceid"]     = 0;
					  	$documentData["filedescription"]= $registre_data["description"];
					  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode, $dateYear);
					  	$documentData["creationdate"]   = time();
					  	$documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	$formulairePath                 = $rcPathroot . DS . $numero."-FR.pdf";
					  	$personnelPath                  = $rcPathroot . DS . $numero."-PS.pdf";
						if( $documentsUploadAdapter->isUploaded("docmini") && $documentsUploadAdapter->isUploaded("docoriginal")) {
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."SURETES". DS . $localiteCode . DS . $registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode, 0777 );
								}
								    @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode.DS.$registreYear);
								    @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode.DS.$registreYear, 0777 );									   
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
								$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=-1"));
							    $dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0  AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = -1 AND registreid='".$parentid."')");
								if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireData ) ) {
					  	  	  	   	$documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
									if( $parent ) {
										$parentFormulaireData             = $formulaireData;
										$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE SURETE N° %s", $numero), $parent->numero);
										if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
											$parentDocumentid             = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-1"));
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
							    $dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=5"));
								$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=5 AND registreid='".$parentid."')");
								if( $dbAdapter->insert( $prefixName ."system_users_documents", $personnelDocData) ) {
					  	  	  		$documentid                       = $dbAdapter->lastInsertId();
					  	  	  		$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid,"access" => 6));
									if( $parent ) {
										$parentPersonnelData              = $personnelDocData;
										$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
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
						$rccmFormulaireFilepath             = $filesSource. DS . "SURETES".DS. $localiteCode . DS .$registreYear. DS . $numero. DS. $numero."-FR.pdf";
			            $rccmPersonnelFilepath              = $filesSource. DS . "SURETES".DS. $localiteCode . DS .$registreYear. DS . $numero. DS. $numero."-PS.pdf";
						
						if( file_exists($rccmFormulaireFilepath) && file_exists( $rccmPersonnelFilepath )) {
							$newRccmFormulaireFilepath      = $rcPathroot . DS . $numero."-FR.pdf";
							$newRccmPersonnelFilepath       = $rcPathroot . DS . $numero."-PS.pdf";
										   					  	  	       					  	  	  
					  	  	$documentData                   = array();
					  	  	$documentData["userid"]         = $me->userid;
					  	  	$documentData["category"]       = 1;
					  	  	$documentData["resource"]       = "registresurete";
					  	  	$documentData["resourceid"]     = 0;
					  	  	$documentData["filedescription"]= $registre_data["description"];
					  	  	$documentData["filemetadata"]   = sprintf("%s;%s;%s", $numero, $localiteCode,$registreYear);
					  	  	$documentData["creationdate"]   = time();
					  	  	$documentData["creatoruserid"]  = $me->userid;
							if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS.$localiteCode.DS.$registreYear) ) {
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES")) {
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES");
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES", 0777 );
								}
								if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS . $localiteCode)) {
									@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS . $localiteCode);
									@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS . $localiteCode, 0777 );
								}
								@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS .$registreYear);
								@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS .$registreYear, 0777 );									   
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
					  	  	  	$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = -1 AND registreid='".$parentid."')");
					  	  	  	if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
					  	  	  	   	$documentid                     = $dbAdapter->lastInsertId();
					  	  	  	   	$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid,"access" => 0 ));
					  	  	  	} else {
					  	  	  	    $errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
								    $errorMessages[]                = sprintf("L'indexation automatique de la sûrété numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
							if( file_exists( $newRccmPersonnelFilepath )) {
								@unlink(     $newRccmPersonnelFilepath );
							}
							if( TRUE ==@copy($rccmPersonnelFilepath, $newRccmPersonnelFilepath)) {
					  	  	  	$personnelFileData                  = $documentData;
					  	  	  	$personnelFileData["filename"]      = $modelDocument->rename("PERSONNEL",$numero);
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
										$parentPersonnelData              = $personnelDocData;
										$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
										if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
											$parentDocumentid             = $dbAdapter->lastInsertId();
											$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5 ));
										}
									}
					  	  	  	} else {
					  	  	  	   	$errorMessages[]                = "Les informations du formulaire ont été partiellement enregistrées";
					  	  	  	}
							} else {
									$errorMessages[]                = sprintf("L'indexation automatique de la sûrété numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
							}
						}  
					}															
					if( !count( $errorMessages ) ) {
						if( $this->_request->isXmlHttpRequest()) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							$defaultData               = array_merge( $update_physique_data, $update_representant_data, $update_registre_data, $postData );
							$jsonErrorArray            = $defaultData;
							$jsonErrorArray["success"] = sprintf("Les informations de la sûrété numéro %s ont été mises à jour avec succès", $numero);
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect(sprintf("Les informations de la sûrété numéro %s ont été mises à jour avec succès", $numero), "success" );
						$this->redirect("admin/registresuretes/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été effectuée dans les informations de la surété"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été effectuée dans les informations du registre de commerce" , "message");
					$this->redirect("admin/registresuretes/infos/id/".$registreid);
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
		$this->view->parentid       = $parentid;
		$this->view->parentNum      = $parentNumRc;
		$this->view->representantid = $representantid;
		$this->view->localiteid     = $localiteid;
		$this->view->localites      = $localites;
		$this->view->types          = $sureteTypes ;
	}	
 		
		
	public function infosAction()
	{		
		$registreid            = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));			
		$model                 = $this->getModel("registre");
		$modelSurete           = $this->getModel("registresurete");
		$modelSureteType       = $this->getModel("suretetype");
		$modelRepresentant     = $this->getModel("representant");
		$modelLocalite         = $this->getModel("localite");
		$modelDocument         = $this->getModel("document");
 	
		$registre              = $model->findRow(      $registreid, "registreid", null , false);
		$surete                = $modelSurete->findRow($registreid, "registreid", null , false);
        $representant          = ( $surete ) ? $surete->representant($registreid) : null;		
		if(!$registre || !$surete || !$representant ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registresuretes/list");
		}
		$registreData              = $registre->toArray();
		$sureteData                = $surete->toArray();
		$dirigeants                = $registre->dirigeants();
		$representantData          = (isset( $dirigeants[0]["representantid"])) ? $dirigeants[0] : array();
		$defaultData               = array_merge( $registreData, $sureteData, $representantData);
		
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->surete        = $surete;
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
		$modelSurete               = $this->getModel("registresurete");
		$modelRepresentant         = $this->getModel("representant");
		$modelDocument             = $this->getModel("document");
		$dbAdapter                 = $model->getTable()->getAdapter();
		$prefixName                = $model->getTable()->info("namePrefix");
		$me                        = Sirah_Fabric::getUser();
		
		$ids                       = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages             = array();
		if( is_string($ids) ) {
			$ids                   = explode("," , $ids );
		}
		$ids                       = (array)$ids;
		if( count(   $ids )) {
			foreach( $ids as $registreid ) {
				     $registre     = $model->findRow( $registreid, "registreid" , null , false);
		             $surete       = $modelSurete->findRow( $registreid, "registreid", null , false )	;
					 if( $registre && $surete ) {
						 $numero                 = $registre->numero;
		                 $dateYear               = substr( $numero, 5, 4);
		                 $localite               = $registre->findParentRow("Table_Localites");
		                 $localiteCode           = ( $localite ) ? $localite->code : "";
						 $rccmFormulaireFilepath = DEFAULT_FIND_DOCUMENTS_SRC.DS."SURETES".DS.$localiteCode.DS.$dateYear.DS.$numero.DS.$numero."-FR.pdf";
		                 $rccmPersonnelFilepath  = DEFAULT_FIND_DOCUMENTS_SRC.DS."SURETES".DS.$localiteCode.DS.$dateYear.DS.$numero.DS.$numero."-PS.pdf";
		                 $rcPathroot             = APPLICATION_INDEXATION_STOCKAGE_FOLDER .DS."SURETES".DS.$localiteCode.DS.$dateYear.DS.$numero;
					     if( empty($localiteCode) || (strlen($dateYear) != 4)) {
							 $errorMessages[]    = sprintf("La surété n° %s n'est pas valide", $numero );
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
		                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES". DS . $localiteCode . DS . $dateYear) ) {
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS . "SURETES")) {
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS. "SURETES");
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS. "SURETES", 0777 );
			                 }
			                 if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS. $localiteCode)) {
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS. $localiteCode);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS. $localiteCode, 0777 );
			                 }
				                 @mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS. $localiteCode. DS . $dateYear);
				                 @chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER.DS."SURETES".DS. $localiteCode. DS . $dateYear, 0777 );									   
		                 }
		                 if(!is_dir($rcPathroot)) {
			                @chmod($rcPathroot, 0777 );
			                @mkdir($rcPathroot);
		                 }
						 $documentData                           = array();
		                 $documentData["userid"]                 = $me->userid;
		                 $documentData["category"]               = 1;
		                 $documentData["resource"]               = "registresurete";
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
							 $dbAdapter->delete($prefixName."rccm_registre_documents", array("registreid=".$parentid  , "access=5"));
							 $dbAdapter->delete($prefixName."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
					  	  	 $dbAdapter->delete($prefixName."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=5 AND registreid='".$parentid."')");
			                 
							 if( $dbAdapter->insert($prefixName."system_users_documents" , $formulaireFileData)) {
				                 $documentid                     = $dbAdapter->lastInsertId();
				                 $dbAdapter->insert($prefixName."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
								 if( $parent ) {
									 $parentFormulaireData             = $formulaireFileData;
									 $parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE SURETE N° %s", $numero), $parent->numero);
									 if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
										 $parentDocumentid             = $dbAdapter->lastInsertId();
										 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-1"));
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
								 if( $parent ) {
									 $parentPersonnelData              = $personnelFileData;
									 $parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
									 if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
										 $parentDocumentid             = $dbAdapter->lastInsertId();
										 $dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5 ));
									 }
								 }
				             } else {
					             $errorMessages[]                = sprintf("Les informations du formulaire de la surété n° %s ont été partiellement enregistrées", $numero);
								 continue;
				             }
		                 } else {
					             $errorMessages[]                = sprintf("L'indexation automatique de la surété numéro %s a echoué car la copie du fichier n'a pas fonctionné", $numero);
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
			$this->redirect("admin/registresuretes/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été re-indexés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été re-indexés avec succès", "success");
			$this->redirect("admin/registresuretes/list");
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
			$this->redirect("admin/registresuretes/list");
		}		
		$model                 = $this->getModel("registre");
		$modelSurete           = $this->getModel("registresurete");
		$modelRepresentant     = $this->getModel("representant");
		$modelDocument         = $this->getModel("document");
		$dbAdapter             = $model->getTable()->getAdapter();
		$prefixName            = $model->getTable()->info("namePrefix");
 	
		$registre              = $model->findRow(       $registreid, "registreid" , null , false);
		$surete                = $modelSurete->findRow( $registreid, "registreid", null , false );	
		if(!$registre || !$surete) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registresuretes/list");
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
		$rcPathroot                        = APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS."SURETES".DS.$localiteCode.DS . $dateYear. DS . $numero;
				
		if(!file_exists($rccmFormulaireFilepath)) {
			$errorMessages[]               = "Dans le dossier source, le formulaire du registre est manquant";
		}
		if(!file_exists( $rccmPersonnelFilepath )) {
			$errorMessages[]               = "Dans le dossier source, le fond de dossier du registre est manquant";
		}
		if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS ."SURETES". DS . $localiteCode . DS . $dateYear) ) {
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES")) {
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER, 0777 );
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES");
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES", 0777 );
			}
			if(!is_dir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode)) {
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode, 0777 );
			}
				@mkdir(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $dateYear);
				@chmod(APPLICATION_INDEXATION_STOCKAGE_FOLDER. DS . "SURETES". DS . $localiteCode. DS . $dateYear, 0777 );									   
		}
		if(!is_dir($rcPathroot)) {
			@chmod($rcPathroot, 0777 );
			@mkdir($rcPathroot);
		}
		$documentData                   = array();
		$documentData["userid"]         = $me->userid;
		$documentData["category"]       = 1;
		$documentData["resource"]       = "registresuretes";
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
			$dbAdapter->delete($prefixName ."rccm_registre_documents", array("registreid=".$parentid  , "access=-1"));
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0  AND registreid='".$registreid."')");
			$dbAdapter->delete($prefixName ."system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = -1 AND registreid='".$parentid."')");
			if( $dbAdapter->insert( $prefixName ."system_users_documents", $formulaireFileData)) {
				$documentid                     = $dbAdapter->lastInsertId();
				$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $registreid,"documentid"=> $documentid, "access" => 0 ));
				if( $parent ) {
					$parentFormulaireData             = $formulaireFileData;
					$parentFormulaireData["filename"] = $modelDocument->rename(sprintf("FORMULAIRE SURETE N° %s", $numero), $parent->numero);
					if( $dbAdapter->insert( $prefixName ."system_users_documents", $parentFormulaireData )) {
						$parentDocumentid             = $dbAdapter->lastInsertId();
						$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => "-1"));
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
					$parentPersonnelData              = $personnelFileData;
					$parentPersonnelData["filename"]  = $modelDocument->rename(sprintf("FOND DE DOSSIER SURETE N° %s", $numero), $parent->numero);
					if( $dbAdapter->insert( $prefixName . "system_users_documents", $parentPersonnelData )) {
						$parentDocumentid             = $dbAdapter->lastInsertId();
						$dbAdapter->insert( $prefixName ."rccm_registre_documents", array("registreid"=> $parentid,"documentid"=> $parentDocumentid,"access" => 5 ));
					}
				}
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
			      $this->redirect("admin/registresuretes/infos/registreid/".$registreid);
		} else {
				if( $this->_request->isXmlHttpRequest()) {
				    echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				    exit;
			    }
			    foreach( $errorMessages as $errorMessage) {
				         $this->_helper->Message->addMessage($errorMessage , "error");
			    }
			    $this->redirect("admin/registresuretes/infos/registreid/".$registreid);
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
						$dbAdapter->delete($prefixName."rccm_registre_suretes"      , "registreid=".$id);
						$dbAdapter->delete($prefixName."rccm_registre_dirigeants"   , "registreid=".$id);						
						$dbAdapter->delete($prefixName."rccm_registre_representants", "representantid IN (SELECT representantid FROM ".$prefixName."rccm_registre_physique  WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"     , "documentid     IN (SELECT documentid     FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
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
			$this->redirect("admin/registresuretes/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registresuretes/list");
		}
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