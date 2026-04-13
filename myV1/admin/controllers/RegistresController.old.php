<?php
require_once("tcpdf/tcpdf.php");
require_once("Fpdi/fpdi.php");
require 'E:\webserver/www/Xpdf/vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use XPDF\PdfToText;

use \setasign\Fpdi;

require_once("FPDF/fpdf.php");
require_once("FPDI2/src/autoload.php");

function formatNumber(    $number  ){
	      if(!is_numeric( $number )){
			  return $number;
		  }
		  return preg_replace('/(\d{1,3})(?=(\d{3})+$)/', "$1.", floatval($number));
}
class Admin_RegistresController extends Sirah_Controller_Default
{	

    public function testsAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		 $uri = Zend_Uri::factory("http://localhost/erccm/admin/registres/tests");
		 print_r($_SERVER);
		 print_r($uri->getScheme());die();
		
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
		$this->view->title  = "Les registres de commerce"  ;
		
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
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters              = array("libelle"=> null,"numero"=> null,"domaineid"=>0,"creatorid"=> 0,"localiteid"=>0,"annee" => 0,"nom"=> null, "prenom" => null,"searchQ" => null,
		                              "date_year"   => null, "date_month" => null, "date_day" => null,"periode_start_year" => DEFAULT_START_YEAR,
				                      "periode_end_year"=> DEFAULT_END_YEAR, "periode_start_month"=> DEFAULT_START_MONTH,"periode_start_day"  => DEFAULT_START_DAY ,
				                      "periode_end_day" => DEFAULT_END_DAY , "periode_end_month"  => DEFAULT_END_MONTH,"passport"=>null,"telephone"=>null);		
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
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->domaines  = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users     = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types     = array(0 => "Selectionnez un type de registre", 1 => "Personnes Physiques", 2 => "Personnes morales", 3 => "Sûrétés", 4 => "Modifications");
		$this->view->statuts   = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $this->view->maxitems = $pageSize;			
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
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelrepresentant     = $this->getModel("representant");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$physique              = $modelPhysique->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$physique ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/registres/list");
		}
		if( $registre->type == 1) {
			$this->redirect("admin/registrephysique/infos/registreid/".$registreid);
		} else {
			$this->redirect("admin/registremoral/infos/registreid/".$registreid);
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
	
			$documentsUploadAdapter                = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator("Count"    , false , 1 );
			$documentsUploadAdapter->addValidator("Extension", false , array("pdf" , "png" , "gif" , "jpg" , "docx" , "doc" , "xml"));
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
		
	
	/*** METHODES NON FORMELLES ***/
	public function verifyAction()
	{
		$this->view->title  = "Vérifier les RCCM traités";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("start_annee" => 2000,"end_annee" => 2016, "localites" => array("OUA","BBD","BFR","ORD","ZNR","MNG","GAO","KYA","OHG","KDG"),
		                            "rootpath"    => "G:\\DATAS_RCCM\\GED\\SOURCE","category" =>"A" , "findInRoot" => false, "from" => 1, "to" => 200);
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
			$to               = (isset( $postData["to"]         )) ? intval( $postData["to"])          : $defaultData["to"];
			$from             = (isset( $postData["from"]       )) ? intval( $postData["from"])        : $defaultData["from"];
			$findInRoot       = (isset( $postData["findInRoot"] )) ? intval( $postData["findInRoot"])  : $defaultData["findInRoot"];
			
			if(!is_dir( $rootPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $rootPath);
			}				
			if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
			}
			if(!count($years)) {
				$errorMessages[]  = "Veuillez préciser une plage de périodes";
			}
			if(!count( $checkedLocalites )) {
				$errorMessages[]  = "Veuillez selectionner les localités concernées";
			}
			if(!intval( $from )) {
				$errorMessages[]  = "Veuillez indiquer un nombre initial valide";
			}
			if(!intval( $to )) {
				$errorMessages[]  = "Veuillez indiquer un nombre final valide";
			}
			if( empty( $errorMessages)) {
				for( $from; $from <= $to; $from++ ) {
					  foreach( $checkedLocalites as $checkedLocaliteCode ) {
							   $notFoundItems[$checkedLocaliteCode]["files"] = array();
						       foreach( $years as $annee ) {
										$notFoundItems[$annee]["files"]                       = array();
										$notFoundItems[$checkedLocaliteCode][$annee]["files"] = array();
								        $numRccm          = sprintf("BF%s%d%s",$localitecode, $annee, $categoryKey);
										if(!$findInRoot)  {
											$srcPath      = $srcPath . DS . $checkedLocaliteCode . DS . $annee;
										}
										$checkedFilePath  = $srcPath . $numRccm.".pdf";
										$rccmExists       = count(preg_grep('#(?:^|/)'.$numRccm.'0{1,3}'.$from.'\.pdf$#',glob($srcPath.DS.$numRccm.'*.pdf')));
										if(!$rccmExists) {
											$notFoundItems["files"][] = $notFoundItems[$checkedLocaliteCode]["files"][] = $notFoundItems[$annee]["files"][] = $notFoundItems[$checkedLocaliteCode][$annee]["files"][] = $numRccm . sprintf("%04d", $from).".pdf";
										}
							   }						  
					  }
				}				
			}  
			if(empty( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
			    $this->_helper->layout->disableLayout(true);
				$checkPointOutputHtml = $this->view->partial("registres/checkmissingpdf.phtml",array("rows"=> $notFoundItems,"annees"=> $years,"localites"=> $localites,"checkedLocalites"=>$checkedLocalites,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee));
				
				$me                   = Sirah_Fabric::getUser();
                $PDF                  = new Sirah_Pdf_Default("L", "mm", array(429,483) , true , "UTF-8");
                $PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
                $PDF->SetTitle("Etat des lieux de la base de données des RCCM");
		
		       $margins                 = $PDF->getMargins();
		       $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		       $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		       $PDF->AddPage();
		
		       $PDF->Ln(10);				     	 
		       $PDF->SetFont("helvetica" , "" , 11 );				     	 
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
	
		$defaultData        = array("start_annee" => 2000,"end_annee" => 2016, "localites" => array("OUA","BBD","BFR","ORD","ZNR","MNG","GAO","KYA","OHG","KDG"),
		                            "rootpath"    => "G:\DATAS_RCCM\GED\DESTINATION\PHYSIQUES","category" =>"A","findInRoot" => false, "from" => 1, "to" => 200);
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
			
			if(!is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $rootPath);
			}				
			if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
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
	
	public function validateAction()
	{
		$this->view->title  = "Vérifier les retraitements effectués";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("localites" => array("OUA","BBD"),"rootpath"=> "G:\FNRCCM\TRAITEMENTS","category" =>"A","ops_username" => null,
		                            "findInRoot"=> 0,"years" => array("2008","2009","2010","2011","2012","2013"),"start_annee" => 2000,"end_annee" => 2016);
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$foundItems         = array();
		$invalidItems       = array();
		$notFoundItems      = array("files" => array());
		$missingOutputHtml  = "";
		$invalidOutputHtml  = "";
		$opsUsername        = null;
		$missings           = $invalids = 0;
	
		$localites          = $modelLocalite->getSelectListe(null, array("code","libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
		if( $this->_request->isPost( )) {			
			$postData         = $this->_request->getPost();
			$srcPath          = (isset( $postData["rootpath"]   )) ? $postData["rootpath"]             : $defaultData["rootpath"];
			$checkedLocalites = (isset( $postData["localites"]  )) ? $postData["localites"]            : $defaultData["localites"];
			$startAnnee       = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : $defaultData["start_annee"];
			$endAnnee         = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
			$categoryKey      = (isset( $postData["category"]   )) ? trim(   $postData["category"])    : $defaultData["category"];
			$opsUsername      = (isset( $postData["ops_username"]))? trim(strip_tags($postData["ops_username"])) : "";
			$categoryLibelle  = ($categoryKey == "A"             ) ? "PHYSIQUES"                       : "MORALES";
			$years            = array();
						
			if(!empty($opsUsername) && is_dir($srcPath)) {
				$srcPath          = $srcPath . DS . preg_replace("/\s/","-", $opsUsername) . DS . "CHECKED";
			}						
			if(!is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath);
			}				
			if(!count( $checkedLocalites )) {
				$errorMessages[]  = "Veuillez selectionner les localités concernées";
			}
			if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
			}
			if(!count($years)) {
				$errorMessages[]  = "Veuillez préciser une plage de périodes";
			}
            $pdfToText            = XPDF\PdfToText::create(array('pdftotext.binaries'=> 'F:\webserver\www\binaries\Xpdf\pdftotext.exe','pdftotext.timeout'=> 30,),$logger);			
			if( empty( $errorMessages)) {				
				foreach($checkedLocalites as $checkedLocaliteCode ) {
					    $foundItems[$checkedLocaliteCode]["files"]                     = (isset($foundItems[$checkedLocaliteCode]["files"]   ))? $foundItems[$checkedLocaliteCode]["files"]   : array();
						$notFoundItems[$checkedLocaliteCode]["files"]                  = (isset($notFoundItems[$checkedLocaliteCode]["files"]))? $notFoundItems[$checkedLocaliteCode]["files"]: array();
						$invalidItems[$checkedLocaliteCode]["files"]                   = (isset($invalidItems[$checkedLocaliteCode]["files"] ))? $invalidItems[$checkedLocaliteCode]["files"] : array();
						foreach( $years as $annee ) {
								 $checkedFilePath                                      = null;
								 $foundItems[$annee]["files"]                          = (isset($foundItems[$annee]["files"]   ))? $foundItems[$annee]["files"]    :array();
								 $notFoundItems[$annee]["files"]                       = (isset($notFoundItems[$annee]["files"]))? $notFoundItems[$annee]["files"] :array();
								 $invalidItems[$annee]["files"]                        = (isset($invalidItems[$annee]["files"] ))? $invalidItems[$annee]["files"]  :array();
								 $foundItems[$checkedLocaliteCode][$annee]["files"]    = (isset($foundItems[$checkedLocaliteCode][$annee]["files"]   ))? $foundItems[$checkedLocaliteCode][$annee]["files"]    : array();
								 $notFoundItems[$checkedLocaliteCode][$annee]["files"] = (isset($notFoundItems[$checkedLocaliteCode][$annee]["files"]))? $notFoundItems[$checkedLocaliteCode][$annee]["files"] : array();
								 $invalidItems[$checkedLocaliteCode][$annee]["files"]  = (isset($invalidItems[$checkedLocaliteCode][$annee]["files"] ))? $invalidItems[$checkedLocaliteCode][$annee]["files"]  : array();
								 $numRccm                                              = sprintf("BF%s%d%s", $checkedLocaliteCode, $annee, $categoryKey);
								 if(!$findInRoot)  {
									$checkedFilePath = $srcPath . DS .$categoryLibelle . DS . $checkedLocaliteCode . DS . $annee ;
							     } else {
									$checkedFilePath = $srcPath . DS .$categoryLibelle ;
								 }
								 $totalFiles         = glob($checkedFilePath."/".$numRccm."*.pdf");
								 $directories        = preg_grep("#(?:^|/)".$numRccm."[0-9]{1,4}$#", glob($checkedFilePath."/*", GLOB_ONLYDIR));
                                 @usort($directories,  function($a, $b) {return strcmp($a, $b);});	
                                						 								 
								 if( count(    $directories)) {
									 $lastDirectory       = array_values(array_slice($directories, -1))[0];
									 $directoryEnd        = str_replace($checkedFilePath."/", "", $lastDirectory);
									 $lastDirKey          = ( count( $directories)) ? intval(str_replace($numRccm, "", $directoryEnd)) : 1;
									 
									//print_r(count(    $directories));	die();	
									 
									 for( $i=1;  $i <= $lastDirKey; $i++ ) {
										  $dirname           = sprintf("%s%04d", $numRccm, $i);
										  $filesDir          = $checkedFilePath.DS.$dirname;
										  $allDirFiles       = glob($checkedFilePath."/".$dirname."/*.pdf");
										  $allDirDirectories = glob($checkedFilePath."/".$dirname."/*", GLOB_ONLYDIR);
										  $rccmFormulaire    = count(preg_grep('#(?:^|/)'.$numRccm.'0{0,3}'.$i.'-FR\.pdf$#',glob($checkedFilePath."/".$dirname."/*-FR.pdf")));
										  $rccmComplet       = count(preg_grep('#(?:^|/)'.$numRccm.'0{0,3}'.$i.'-PS\.pdf$#',glob($checkedFilePath."/".$dirname."/*-PS.pdf")));
										  $rccmStatut        = count(preg_grep('#(?:^|/)'.$numRccm.'0{0,3}'.$i.'-ST\.pdf$#',glob($checkedFilePath."/".$dirname."/*-ST.pdf")));									  										  
										  $formulaireContent = "";
										  if(isset($directories[$i])) {
											  $dirnameToCheck  = str_replace($checkedFilePath."/", "", $directories[$i]);
											  if((strlen($dirnameToCheck) != 14) ) {
												  $invalids++;
												  $errorMessages[]         = sprintf("Le dossier du RCCM n° %s n'a pas été correctement nommé", $dirnameToCheck);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
												  //echo stripos($dirnameToCheck,$numRccm); die($dirnameToCheck);
												  continue;
											  }
										  }
										  if((count($allDirFiles)  > 3 ) || (count($allDirDirectories)  >= 1 ) ) {
											  $dirnameToCheck          = $checkedFilePath."/".$dirname;
											  $invalids++;
											  $errorMessages[]         = sprintf("Le dossier du RCCM n° %s contient plus de fichiers et dossiers que prévus", $dirnameToCheck);
											  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											  continue;
										  }
										  //On vérifie les fichiers invalides
										  $formulaireFilePath   = $checkedFilePath . DS . $dirname . DS . $dirname."-FR.pdf";
										  $statutFilePath       = $checkedFilePath . DS . $dirname . DS . $dirname."-ST.pdf";
										  $completFilePath      = $checkedFilePath . DS . $dirname . DS . $dirname."-PS.pdf";
										  if(file_exists($checkedFilePath . DS . $dirname . DS . $dirname." -FR.pdf")) {
											  @rename($checkedFilePath . DS . $dirname . DS . $dirname." -FR.pdf", $checkedFilePath . DS . $dirname . DS . $dirname."-FR.pdf");
										  }
										  if(file_exists($checkedFilePath . DS . $dirname . DS . $dirname." -PS.pdf")) {
											  @rename($checkedFilePath . DS . $dirname . DS . $dirname." -PS.pdf", $checkedFilePath . DS . $dirname . DS . $dirname."-PS.pdf");
										  }
										  if(file_exists($checkedFilePath . DS . $dirname . DS . $dirname." -ST.pdf")) {
											  @rename($checkedFilePath . DS . $dirname . DS . $dirname." -ST.pdf", $checkedFilePath . DS . $dirname . DS . $dirname."-ST.pdf");
										  }
										  if(file_exists($checkedFilePath . DS . $dirname . DS . $dirname."-SP.pdf")) {
											  @rename($checkedFilePath . DS . $dirname . DS . $dirname."-SP.pdf", $checkedFilePath . DS . $dirname . DS . $dirname."-PS.pdf");
										  }
										  if(file_exists( $formulaireFilePath )) {
											  $formulaireContent     = $pdfToText->getText($formulaireFilePath);
										  }										  
										  if( !empty( $formulaireContent )) {
				                              $findAnormalChar   = ((stripos($formulaireContent," casier")!==false)    || (stripos($formulaireContent," bail")!==false) || (stripos($formulaireContent," CARTE D'IDENTITE")!==false) ||
				                                                    (stripos($formulaireContent," judiciaire")!==false)|| (stripos($formulaireContent," CNIB")!==false) || (stripos($formulaireContent," RESIDENCE")!==false) || 
									                                (stripos($formulaireContent," mairie") !==false)|| (stripos($formulaireContent," contrat")!==false) || (stripos($formulaireContent," passport")!==false)  ||
																	(stripos($formulaireContent," procuration") !==false));
			                                   if($findAnormalChar ) {
					                              $errorMessages[] = sprintf("Le formulaire du RCCM n° %s n'a pas été bien traité", $dirname);
					                              continue;
				                                }
			                              }
										  if(!$rccmFormulaire && !$rccmComplet && !$rccmStatut && is_dir($filesDir)) {
											  $invalids++;
											  $errorMessages[]  = sprintf("Dossier %s vide ou est invalide ", $dirname);
										      $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											  continue;
										  }
										  if(!$rccmFormulaire && !$rccmComplet && !$rccmStatut) {
											  $missings++;
											  $notFoundItems["files"][] = $notFoundItems[$checkedLocaliteCode]["files"][] = $notFoundItems[$annee]["files"][] = $notFoundItems[$checkedLocaliteCode][$annee]["files"][] = $dirname.".pdf";
										      continue;
										  }
										  if(($categoryKey == "A") && ($rccmFormulaire || $rccmComplet)) {
											  if(!$rccmFormulaire) {
												  $invalids++;
												  $errorMessages[]  = sprintf("Formulaire du RCCM n° %s manquant ", $dirname);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
												  continue;
											  }
											  if(!$rccmComplet) {
												  $invalids++;
												  $errorMessages[]  = sprintf("Document complet du RCCM n° %s manquant", $dirname);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-PS.pdf";
												  continue;
											  }
										  }
                                          if(($categoryKey == "B") && ($rccmFormulaire || $rccmStatut)) {
											   if(!$rccmFormulaire) {
												  $invalids++;
												  $errorMessages[]  = sprintf("Formulaire du RCCM n° %s manquant ", $dirname);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
												  continue;
											  }
											  if(!$rccmComplet) {
												  $invalids++;
												  $errorMessages[]  = sprintf("Document complet du RCCM n° %s manquant", $dirname);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-PS.pdf";
												  continue;
											  }
											  if(!$rccmStatut) {
												  $invalids++;
												  $errorMessages[]         = sprintf("Statut du RCCM n° %s manquant ", $dirname);
												  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-ST.pdf";
												  continue;
											  }
                                          }										  
										  try{
										      $pdfRegistre          = new FPDI();
						                      $pagesFormulaire      = (file_exists($formulaireFilePath)) ? $pdfRegistre->setSourceFile( $formulaireFilePath ) : 0;
										      $pagesStatut          = (file_exists($statutFilePath    )) ? $pdfRegistre->setSourceFile( $statutFilePath     ) : 0;
										      $pagesComplet         = (file_exists($completFilePath   )) ? $pdfRegistre->setSourceFile( $completFilePath    ) : 0;
										  }catch(Exception $e ) {
											  $invalids++;
											  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											  $errorMessages[]         = sprintf("Le fichier %s ou %s est invalide", $completFilePath, $formulaireFilePath, $statutFilePath);
											  continue;
										  }
										  if( $pagesFormulaire && ( $pagesComplet <= $pagesFormulaire )) {
											  $invalids++;
											  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											  $errorMessages[]         = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages", $dirname);
											  continue;
										  }
										  if( $pagesFormulaire && ( $pagesFormulaire > 3 )) {
											  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											  $errorMessages[]         = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il contient plus de 4 pages", $dirname);
											  continue;
										  }
										  if( $pagesStatut && ( $pagesComplet <= $pagesStatut )) {
											  $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-ST.pdf";
											  $errorMessages[]         = sprintf("Le statut du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages", $dirname);
											  continue;
										  }
                                              $foundItems["files"][]   = $foundItems[$checkedLocaliteCode]["files"][] = $foundItems[$annee]["files"][] = $foundItems[$checkedLocaliteCode][$annee]["files"][] = $dirname.".pdf";										  
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
			}
			$missingOutputHtml   = $this->view->partial("registres/validation/missing.phtml" ,array("rows"=> $notFoundItems,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"opsUsername"=>$opsUsername,"type" => $categoryKey));
			$invalidOutputHtml   = $this->view->partial("registres/validation/invalids.phtml",array("rows"=> $invalidItems ,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"opsUsername"=>$opsUsername,"type" => $categoryKey));
			$validOutputHtml     = $this->view->partial("registres/validation/valids.phtml"  ,array("rows"=> $foundItems   ,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"opsUsername"=>$opsUsername,"type" => $categoryKey));
			$errorHtml           = "";
			
			if(count( $errorMessages )) {
				$errorHtml       =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
                      $errorHtml.="<tr><td width=\"100%\" style=\"font-size:13pt; text-align:center;background-color:#E5E5E5\" align=\"center\"><b> ".sprintf('HISTORIQUE DES ERREURS PRODUITES PAR %s', $opsUsername)." </b></td></tr>";
                $errorHtml      .=" </table>";
				$errorHtml      .=" <ul>";
				foreach( $errorMessages as $errorMessage ) {
					     $errorHtml      .=" <li> ".$errorMessage."</li>";
				}
				$errorHtml      .=" </ul>";
			}	
            echo $errorHtml.$invalidOutputHtml.$validOutputHtml;die();		
			$me                  = Sirah_Fabric::getUser();
            $PDF                 = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
            $PDF->SetCreator(sprintf("%s", $opsUsername));
            $PDF->SetTitle(  sprintf("Validation des retraitements effectués par %s", $opsUsername));
			$PDF->SetPrintHeader(false);
		    $PDF->SetPrintFooter(false);
		
		    $margins                 = $PDF->getMargins();
		    $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		    $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		    $PDF->AddPage();
		
		    $PDF->Ln(10);				     	 
		    $PDF->SetFont("helvetica" , "" , 12);	
            $PDF->writeHTML( $errorHtml        , true , false , true , false , '' );$PDF->AddPage();			
		    $PDF->writeHTML( $invalidOutputHtml, true , false , true , false , '' );$PDF->AddPage();
            $PDF->writeHTML( $validOutputHtml  , true , false , true , false , '' );$PDF->AddPage();
            //$PDF->writeHTML( $missingOutputHtml, true , false , true , false , '' );			
		 
		    echo $PDF->Output(sprintf("Validation%s.pdf", preg_replace("/\s/","-", $opsUsername)),"D");
		    exit;
		}
        $this->view->invalidHtml = ($invalids) ? $invalidOutputHtml : null;	
		$this->view->missingHtml = ($missings) ? $missingOutputHtml : null;	
		$this->view->validHtml   = $validOutputHtml;	
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
		
		$this->render("validation");
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
		$defaultData        = array("srcfolder" => "G:\DATAS_RCCM\GED\NUMERISATIONS", "destfolder" => "G:\DATAS_RCCM\GED\A_RETRAITER");
		
		if( $this->_request->isPost() ) {
			$postData         = $this->_request->getPost();
			
			$srcFolder        = $srcPath  = (isset($postData["srcfolder"] )) ? trim(strip_tags($postData["srcfolder"]))  : "G:\DATAS_RCCM\GED\NUMERISATIONS";
			$destFolder       = $destPath = (isset($postData["destfolder"])) ? trim(strip_tags($postData["destfolder"])) : "G:\DATAS_RCCM\GED\A_RETRAITER";
			$opsUsername      = (isset($postData["ops_username"])) ? trim(strip_tags($postData["ops_username"])) : "";
						
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
										 $foundFiles[]  = $rccmFilename;
									 }elseif((TRUE ==@copy($rccmFile, $newFileDirFr)) && (TRUE==@copy($rccmFile, $newFileDirPS)) && (TRUE==@copy($rccmFile, $newFileDirST))) {
										 $foundFiles[]  = $rccmFilename;
									 }
								 } else {
									 if(file_exists($fileDirname.DS.$rccmFilename."-FR.pdf")) {
										 @rename($fileDirname.DS.$rccmFilename."-FR.pdf", $newFileDirFr);
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
	
		
	public function progressAction()
	{
		$this->view->title  = "Vérifier les taux d'exécution par localité";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("localites" => array("OUA","BBD","DDG","KDG","MNG","GAO","DRI","DJB","FDG","OHG"),
		                            "rootpath"  => "G:\ERCCM","category" =>"A",
                                    "years"     => array("2000","2001","2002","2003","2004","2005","2006",
									                     "2007","2008","2009","2010","2011",
									                     "2012","2013","2014","2015","2016","2017"),"start_annee" => 2000,"end_annee" => 2017);
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
	    $sourceItems        = array("files" => array());
		$foundItems         = array("files" => array());
		$invalidItems       = array("files" => array());
		$registres          = array();
		$notFoundItems      = array("files" => array());
		$missingOutputHtml  = "";
		$invalidOutputHtml  = "";
		$missings           = $invalids = 0;
	
		$localites          = $modelLocalite->getSelectListe(null, array("code","libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016","2017" => "2017");
		if( $this->_request->isPost( )) {	           		
			$postData         = $this->_request->getPost();
			$srcPath          = (isset( $postData["rootpath"]   )) ? $postData["rootpath"]             : $defaultData["rootpath"];
			$checkedLocalites = (isset( $postData["localites"]  )) ? $postData["localites"]            : $defaultData["localites"];
			$startAnnee       = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : $defaultData["start_annee"];
			$endAnnee         = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
			$categoryKey      = (isset( $postData["category"]   )) ? trim(   $postData["category"])    : "A";
			$categoryLibelle  = "PHYSIQUES";
			$years            = array();
												
			if(!is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath);
			}				
			if(!count( $checkedLocalites )) {
				$errorMessages[]  = "Veuillez selectionner les localités concernées";
			}
			if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
			}
			if(!count($years)) {
				$errorMessages[]  = "Veuillez préciser une plage de périodes";
			}				
			if( empty( $errorMessages)) {
                $dbsourceParams = array("host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
				                        "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
				                        "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
				                        "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "sigar" ),
				                        "isDefaultAdapter" => 0);
		        try{
			         $dbSource        = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
			         $dbSource->getConnection();
		        } catch( Zend_Db_Adapter_Exception $e ) {
			         $errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		        } catch( Zend_Exception $e ) {
			         $errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		        }				
				foreach($checkedLocalites as $checkedLocaliteCode ) {
					    $sourceItems[$checkedLocaliteCode]["total"]                    = 0;
					    $foundItems[$checkedLocaliteCode]["total"]                     = 0;
						$notFoundItems[$checkedLocaliteCode]["total"]                  = 0;
						
					    $foundItems[$checkedLocaliteCode]["files"]                     = (isset($foundItems[$checkedLocaliteCode]["files"]   ))? $foundItems[$checkedLocaliteCode]["files"]   : array();
						$notFoundItems[$checkedLocaliteCode]["files"]                  = (isset($notFoundItems[$checkedLocaliteCode]["files"]))? $notFoundItems[$checkedLocaliteCode]["files"]: array();
						$invalidItems[$checkedLocaliteCode]["files"]                   = (isset($invalidItems[$checkedLocaliteCode]["files"] ))? $invalidItems[$checkedLocaliteCode]["files"] : array();
						foreach( $years as $annee ) {
								 $checkedFilePath                                      = null;
                                 $sourceItems[$annee]["total"]                         = (isset($sourceItems[$annee]["total"]  ))? $sourceItems[$annee]["total"]   :0;
					             $foundItems[$annee]["total"]                          = (isset($foundItems[$annee]["total"]   ))? $foundItems[$annee]["total"]    :0;
						         $notFoundItems[$annee]["total"]                       = (isset($notFoundItems[$annee]["total"]))? $notFoundItems[$annee]["total"] :0;							 

								 $foundItems[$annee]["files"]                          = (isset($foundItems[$annee]["files"]   ))? $foundItems[$annee]["files"]    :array();
								 $notFoundItems[$annee]["files"]                       = (isset($notFoundItems[$annee]["files"]))? $notFoundItems[$annee]["files"] :array();
								 $invalidItems[$annee]["files"]                        = (isset($invalidItems[$annee]["files"] ))? $invalidItems[$annee]["files"]  :array();
								 
								 $sourceItems[$checkedLocaliteCode][$annee]["total"]   = 0;
								 $foundItems[$checkedLocaliteCode][$annee]["total"]    = 0;
								 $notFoundItems[$checkedLocaliteCode][$annee]["total"] = 0;
								 
								 $foundItems[$checkedLocaliteCode][$annee]["files"]    = (isset($foundItems[$checkedLocaliteCode][$annee]["files"]   ))? $foundItems[$checkedLocaliteCode][$annee]["files"]    : array();
								 $notFoundItems[$checkedLocaliteCode][$annee]["files"] = (isset($notFoundItems[$checkedLocaliteCode][$annee]["files"]))? $notFoundItems[$checkedLocaliteCode][$annee]["files"] : array();
								 $invalidItems[$checkedLocaliteCode][$annee]["files"]  = (isset($invalidItems[$checkedLocaliteCode][$annee]["files"] ))? $invalidItems[$checkedLocaliteCode][$annee]["files"]  : array();
								 $numRccm                                              = $searchRccmKey = sprintf("BF%s%d", $checkedLocaliteCode, $annee);
								 
								 $dbSourceSelect       = $dbSource->select()->from(array("A" => "archive"), array("A.analyse","A.date_enregistrement","A.date_deb","A.id_archive"))
					                                              ->joinLeft(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive","F.nomged_fichier"))
					                                              ->where("F.nom_fichier LIKE ?","%".$searchRccmKey."%")->order(array("A.analyse ASC","F.nom_fichier ASC"));								 
								 $sourceFiles          = $dbSource->fetchAll( $dbSourceSelect );
								 
								 //$totalFiles         = glob($checkedFilePath."/".$numRccm."*.pdf");
								 $foundDirectories     = preg_grep("#(?:^|/)".$numRccm."A|B|M|S[0-9]{1,4}$#",glob( $srcPath."/".$checkedLocaliteCode."/".$annee."/*", GLOB_ONLYDIR));
								 @usort( $foundDirectories,  function($a, $b) {return strcmp($a, $b);});
								if((count($foundDirectories) >0) && (count($sourceFiles) <=  count($foundDirectories))) {
									$sourceItems[$checkedLocaliteCode]["total"]   = $sourceItems[$checkedLocaliteCode][$annee]["total"]  = $sourceItems[$annee]["total"] = count($foundDirectories);
									$sourceItems[$checkedLocaliteCode]["files"]   = $sourceItems[$annee]["files"] = $sourceItems[$checkedLocaliteCode][$annee]["files"] = $foundDirectories;
						            $foundItems[$checkedLocaliteCode]["total"]    = $foundItems[$checkedLocaliteCode][$annee]["total"] = $foundItems[$annee]["total"] = count($foundDirectories);
									$foundItems[$checkedLocaliteCode]["files"]    = $foundItems[$annee]["files"]= $foundItems[$checkedLocaliteCode][$annee]["files"] = $foundDirectories;
								    $notFoundItems[$checkedLocaliteCode]["total"] = $notFoundItems[$annee]["total"] = $notFoundItems[$checkedLocaliteCode][$annee]["total"]=0;
									$notFoundItems[$checkedLocaliteCode]["files"] = $notFoundItems[$annee]["files"] = $notFoundItems[$checkedLocaliteCode][$annee]["files"] = array();
								} elseif( count($sourceFiles) >  count($foundDirectories)) {
									 $sourceItems[$checkedLocaliteCode]["total"]  = $sourceItems[$checkedLocaliteCode][$annee]["total"] = $sourceItems[$annee]["total"]= count($sourceFiles);
									 foreach(  $sourceFiles as $sourceFile ) {
										       $srcNumRegistre             = strstr($sourceFile["nom_fichier"], ".", true );
                                               $sourceFileDirectory        = "G:\\DATAS_RCCM\\GED\\SOURCE". DS . intval($sourceFile["dossier"]). DS . $sourceFile["filename"];		                                              											   
                                               $categoryKey                = "A";
											   $categoryLibelle            = "PHYSIQUES";
											   $rccmNumPrefixPhysique      = sprintf("BF%s%dA", $checkedLocaliteCode, $annee);
											   $rccmNumPrefixMorales       = sprintf("BF%s%dB", $checkedLocaliteCode, $annee);
											   $rccmNumPrefixModifications = sprintf("BF%s%dM", $checkedLocaliteCode, $annee);
											   $rccmNumPrefixSuretes       = sprintf("BF%s%dS", $checkedLocaliteCode, $annee);
                                               if(stripos($srcNumRegistre, $rccmNumPrefixPhysique) !== false ) {
												   $categoryKey            = "A";
												   $categoryLibelle        = "PHYSIQUES";
											   } elseif(stripos($srcNumRegistre, $rccmNumPrefixMorales) !== false ) {
												   $categoryKey            = "B";
												   $categoryLibelle        = "MORALES";
											   } elseif(stripos($srcNumRegistre, $rccmNumPrefixModifications)!== false ) {
												   $categoryKey            = "M";
												   $categoryLibelle        = "MODIFICATIONS";
											   } elseif( stripos($srcNumRegistre, $rccmNumPrefixSuretes) !== false) {
												   $categoryKey            = "S";
												   $categoryLibelle        = "SURETES";
											   }												   
                                               $checkedFilePath            = $srcPath . DS . $checkedLocaliteCode . DS . $annee ;         							 
			    	     	                   $numIdPrefix                = substr( $srcNumRegistre, 0 , 10);
			    	     	                   $numIdKey                   = substr( $srcNumRegistre, 10, strlen( $srcNumRegistre ));
			    	     	                   $numRegistre                = $dirname = strtoupper( $numIdPrefix ) . sprintf("%04d", $numIdKey );
											   $numRccm                    = strtoupper(sprintf("BF%s%d%s", $checkedLocaliteCode, $annee, $categoryKey));
											   if( in_array($numRegistre, $sourceItems["files"])) {
												    $sourceItems[$checkedLocaliteCode]["total"]--;
											        $sourceItems[$annee]["total"]--;
											        $sourceItems[$checkedLocaliteCode][$annee]["total"]--;
													continue;
											   }
                                               if(!file_exists( $sourceFileDirectory ))	{
												   $sourceItems[$checkedLocaliteCode]["total"]--;
											       $sourceItems[$annee]["total"]--;
											       $sourceItems[$checkedLocaliteCode][$annee]["total"]--;
												   continue;
                                               } else {											   
												   $isIncoherence           = false;
												   try{
			                                            $pdfRegistre        = new FPDI();
			                                            $pagesRegistre      = (file_exists($sourceFileDirectory)) ? $pdfRegistre->setSourceFile($sourceFileDirectory) : 0;
														if( $pagesRegistre <= 2 ) {
															$isIncoherence  = true;
														}
		                                           } catch(Exception $e ) {
			                                             
		                                           }
												   if( $isIncoherence ) {
													   $sourceItems[$checkedLocaliteCode]["total"]--;
											           $sourceItems[$annee]["total"]--;
											           $sourceItems[$checkedLocaliteCode][$annee]["total"]--;
												       continue;
												   }
											   }												   
											   $sourceItems["files"][] = $sourceItems[$checkedLocaliteCode]["files"][] = $sourceItems[$annee]["files"][] = $sourceItems[$checkedLocaliteCode][$annee]["files"][] = $dirname;
											   
										  $analyse = $registreStr      = $cleanTxt = trim( $sourceFile["analyse"]) ;
										  preg_match("#(?P<numero>BF".strtoupper($checkedLocaliteCode)."(\d{4})(A|B|M|S)(\d{1,4}))#i", $analyse, $matches );
					                      $replaceNumRccm              = (isset( $matches["numero"])) ? $matches["numero"] : $numRccm;
					 
					                      if( $replaceNumRccm ) {
						                      while(stripos( $cleanTxt, $replaceNumRccm )!==false) {
							                        $cleanTxt = trim(str_replace($replaceNumRccm, "", $cleanTxt));
						                      }
					                      }
					                     if(( $x_pos    = stripos( $cleanTxt, ";"))!==false) {
						                      $cleanTxt = trim(substr(  $cleanTxt, 0, $x_pos + 1), " ;");
					                     }
					                     $cleanTxt                         = trim(str_replace(array(":",",",": ",":  "), "", $cleanTxt));
					                     $cleanTxtToArray                  = explode(",", $cleanTxt );
					                     $name                             = (isset($cleanTxtToArray[0])) ? $cleanTxtToArray[0] : "";
										 $registres[$numRegistre]["name"]  = utf8_encode($name);
										 $registres[$numRegistre]["numero"]= $numRegistre;
											   
										  //On vérifie les fichiers invalides
										  $formulaireFilePath              = $checkedFilePath . DS . $numRegistre. DS . $numRegistre."-FR.pdf";
										  $statutFilePath                  = $checkedFilePath . DS . $numRegistre. DS . $numRegistre."-ST.pdf";
										  $completFilePath                 = $checkedFilePath . DS . $numRegistre. DS . $numRegistre."-PS.pdf";										  
										  
										  if(!file_exists($formulaireFilePath)) {											  											     	
											 $notFoundItems[$checkedLocaliteCode]["total"]++;
											 $notFoundItems[$annee]["total"]++;
											 $notFoundItems[$checkedLocaliteCode][$annee]["total"]++;
											 $notFoundItems["files"][]= $notFoundItems[$checkedLocaliteCode]["files"][]= $notFoundItems[$annee]["files"][] = $notFoundItems[$checkedLocaliteCode][$annee]["files"][] = $dirname;											 
																	 
											 continue;
										  }	else {
											$isValid                = true;
											  try{
										           $pdfRegistre     = new FPDI();
						                           $pagesFormulaire = (file_exists($formulaireFilePath)) ? $pdfRegistre->setSourceFile( $formulaireFilePath ) : 0;
										           $pagesComplet    = (file_exists($completFilePath   )) ? $pdfRegistre->setSourceFile( $completFilePath    ) : 0;
												   $pagesStatut     = (file_exists($statutFilePath    )) ? $pdfRegistre->setSourceFile( $statutFilePath     ) : 0;
										           
												   if( $pagesComplet <= $pagesFormulaire ) {											          
										               $isValid                 = false;
												   }
												   if( intval($pagesStatut) && ($pagesComplet <= $pagesStatut ) ) {													  
										               $isValid     = false;
												   }
											  }catch( Exception $e ) {
											   
										      }
											  if( false == $isValid ) {
												  $invalids++;
											      $invalidItems["files"][] = $invalidItems[$checkedLocaliteCode]["files"][] = $invalidItems[$annee]["files"][] = $invalidItems[$checkedLocaliteCode][$annee]["files"][] = $dirname."-FR.pdf";
											      $errorMessages[]         = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages", $dirname);
												  continue;
											  }
										 }											  
										  $foundItems[$checkedLocaliteCode]["total"]++;
										  $foundItems[$annee]["total"]++;
										  $foundItems[$checkedLocaliteCode][$annee]["total"]++;
										  $foundItems["files"][] = $foundItems[$checkedLocaliteCode]["files"][] = $foundItems[$annee]["files"][] = $foundItems[$checkedLocaliteCode][$annee]["files"][] = $dirname;					  
									 }
									                                   							 
									 if((count($foundItems["files"]) < count($foundDirectories)) && count($foundItems["files"])) {
										 foreach( $foundDirectories as $directoryPath) {
											      $directoryName  = trim(basename($directoryPath));
												  if(!in_array($directoryName, $foundItems["files"])) {
													  $foundItems[$checkedLocaliteCode]["total"]++;
										              $foundItems[$annee]["total"]++;
										              $foundItems[$checkedLocaliteCode][$annee]["total"]++;
													  $foundItems["files"][] = $foundItems[$checkedLocaliteCode]["files"][] = $foundItems[$annee]["files"][] = $foundItems[$checkedLocaliteCode][$annee]["files"][] = $directoryName;
													  
													  $sourceItems[$checkedLocaliteCode]["total"]++;
										              $sourceItems[$annee]["total"]++;
										              $sourceItems[$checkedLocaliteCode][$annee]["total"]++;
										              $sourceItems["files"][] = $sourceItems[$checkedLocaliteCode]["files"][] = $sourceItems[$annee]["files"][] = $sourceItems[$checkedLocaliteCode][$annee]["files"][] = $directoryName;
													  
												   }
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
			}
			$missingOutputHtml   = $this->view->partial("registres/progress/missing.phtml",array("notFoundItems" => $notFoundItems,"rows"=> $notFoundItems,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"type" => $categoryKey, "registres" => $registres,"foundItems"=> $foundItems, "sourceItems" => $sourceItems ));
			$validOutputHtml     = $this->view->partial("registres/progress/valids.phtml" ,array("foundItems"=> $foundItems, "notFoundItems" => $notFoundItems, "sourceItems" => $sourceItems,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"type" => $categoryKey));
			$inValidOutputHtml   = $this->view->partial("registres/progress/invalids.phtml",array("rows"=> $invalidItems,"foundItems"=> $foundItems,"annees"=> $years ,"localites"=> $localites,"checkedLocalites" => $checkedLocalites ,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee,"type" => $categoryKey));
			$errorHtml           = "";
			
			if(count( $errorMessages )) {
				$errorHtml       =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
                      $errorHtml.="<tr><td width=\"100%\" style=\"font-size:13pt; text-align:center;background-color:#E5E5E5\" align=\"center\"><b> ".sprintf('HISTORIQUE DES ERREURS PRODUITES PAR %s', $opsUsername)." </b></td></tr>";
                $errorHtml      .=" </table>";
				$errorHtml      .=" <ul>";
				foreach( $errorMessages as $errorMessage ) {
					     $errorHtml      .=" <li> ".$errorMessage."</li>";
				}
				$errorHtml      .=" </ul>";
			}		
            echo $validOutputHtml.$inValidOutputHtml.$missingOutputHtml;die();		
			$me                  = Sirah_Fabric::getUser();
            $PDF                 = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
            $PDF->SetCreator(sprintf("%s", $me->lastname, $me->firstname ));
            $PDF->SetTitle(  "Bilan d'exécution des retraitements");
			$PDF->SetPrintHeader(false);
		    $PDF->SetPrintFooter(false);
		
		    $margins                 = $PDF->getMargins();
		    $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		    $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		    $PDF->AddPage();
		
		    $PDF->Ln(10);				     	 
		    $PDF->SetFont("helvetica" , "" , 12);	
            //$PDF->writeHTML( $errorHtml        , true , false , true , false , '' );$PDF->AddPage();			
		    //$PDF->writeHTML( $invalidOutputHtml, true , false , true , false , '' );$PDF->AddPage();
            $PDF->writeHTML( $validOutputHtml  , true , false , true , false , '' );$PDF->AddPage();
            //$PDF->writeHTML( $missingOutputHtml, true , false , true , false , '' );			
		 
		    echo $PDF->Output("Progression.pdf","D");
		    exit;
		}
		$this->view->missingHtml = ($missings) ? $missingOutputHtml : null;	
		$this->view->validHtml   = $validOutputHtml;	
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
		
		$this->render("progress");
	}
	
	public function stats1Action()
	{
		$this->view->title  = "STATISTIQUES DES RCCM PHYSIQUES TRAITES";
		
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("category" => "A","rootpath" => "G:\\BFRCCM","start_annee" => 2000,"end_annee" => 2016,"localites" => array("OUA","BBD","BFR","ORD","ZNR","MNG","GAO","KYA","OHG","KDG"));
		$errorMessages      = array();
		$successMessages    = array();
		$localites          = $modelLocalite->getSelectListe(null, array("code","libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");									
	    $foundItems         = array();
		if( $this->_request->isPost() ) {								
			$postData         = $this->_request->getPost();
			$rootPath         = (isset( $postData["rootpath"]   )) ? $postData["rootpath"]             : "G:\\BFRCCM";
			$checkedLocalites = (isset( $postData["localites"]  )) ? $postData["localites"]            : array();
			$startAnnee       = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : date("Y");
			$endAnnee         = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
			$categoryKey      = (isset( $postData["category"]   )) ? trim(   $postData["category"] )   : "A";
			$categoryLibelle  = ($categoryKey == "A"             ) ? "PHYSIQUES" : "MORALES";
			$registres        = array();
							 
			if(!is_dir( $rootPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $rootPath);
			}
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter         =  new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				$years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
			}
			if(!count($years)) {
				$errorMessages[]  = "Veuillez préciser une plage de périodes";
			}
			if(!count( $checkedLocalites )) {
				$errorMessages[]  = "Veuillez selectionner les localités concernées";
			}
			if( empty( $errorMessages ) ) {				
				foreach( $years as $annee ) {
					     $foundItems[$annee]["totalFormulaires"] = $foundItems[$annee]["totalStatuts"] =  $foundItems[$annee]["totalComplets"] = 0;
						 foreach( $checkedLocalites as $localitecode ) {
							      $localiteLibelle                       = (isset($localites[$localitecode])) ? $localites[$localitecode] : null;
							      if( null==$localiteLibelle) {
								      $errorMessages[]                   = "Localité Invalide";
								      continue;
							      }
								  $numeroInitial  = sprintf("BF%s%d%s",$localitecode, $annee, $categoryKey );
								  $foundItems[$annee][$localitecode]["totalFormulaires"]= 0;
								  $foundItems[$annee][$localitecode]["totalComplets"]   = 0;
								  $foundItems[$annee][$localitecode]["totalStatuts"]    = 0;
								  $srcPath        = $rootPath . DS . $localitecode . DS . $categoryLibelle . DS . $numeroInitial;							  
								  foreach(glob($srcPath."*",GLOB_ONLYDIR)  as $rccmFileDirectory ) {
				                          foreach(glob($rccmFileDirectory."/*.pdf") as $rccmFilePath ) {
											      $rccmFilename       = str_ireplace("/","",strrchr($rccmFilePath,"/")); 
											      if((FALSE != stripos($rccmFilename, "-FR"))) {
													 $foundItems[$annee]["totalFormulaires"]	            = $foundItems[$annee]["totalFormulaires"] +1;
                                                     $foundItems[$annee][$localitecode]["totalFormulaires"]	= $foundItems[$annee][$localitecode]["totalFormulaires"] +1;												  
						                             continue;
					                              }
					                              if((FALSE != stripos($rccmFilename, "-ST")) && !file_exists($statutDest)) {
													  $foundItems[$annee]["totalStatuts"]                   = $foundItems[$annee]["totalStatuts"] +1;
                                                      $foundItems[$annee][$localitecode]["totalStatuts"]    = $foundItems[$annee][$localitecode]["totalStatuts"] +1;													  
						                              continue;
					                              }
												  $foundItems[$annee]["totalComplets"]                      = $foundItems[$annee]["totalComplets"] +1;
					                              $foundItems[$annee][$localitecode]["totalComplets"]       = $foundItems[$annee][$localitecode]["totalComplets"] +1;
						                  }
								  }
						 }
				}					
			}				
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				
				$checkPointOutputHtml = $this->view->partial("registres/statistiques1.phtml",array("rows"=> $foundItems,"annees"=> $years,"localites"=> $localites,"checkedLocalites"=>$checkedLocalites,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee));
				
				$me                   = Sirah_Fabric::getUser();
                $PDF                  = new Sirah_Pdf_Default("L", "mm", array(429,483) , true , "UTF-8");
                $PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
                $PDF->SetTitle("Statistiques des RCCM");
		
		       $margins                 = $PDF->getMargins();
		       $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		       $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		       $PDF->AddPage();
		
		       $PDF->Ln(10);				     	 
		       $PDF->SetFont("helvetica" , "" , 11 );				     	 
		       $PDF->writeHTML($checkPointOutputHtml, true , false , true , false , '' );	
		 
		       echo $PDF->Output("statistiques1.pdf","D");
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
	}
	
	public function checkpointAction()
	{
		$this->view->title  = "Etat des lieux des RCCM";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("dbsource_host" => "localhost","dbsource_user" =>"root","dbsource_password" => "","start_annee" => 2000,"end_annee" => 2016, "localites" => array("OUA","BBD","BFR"),
				                    "dbsource_name" => "sigar","dbsource_tablename"=>"archive", "srcpath" => "G:\\DATAS_RCCM\\GED\\SOURCE", "category" => "A");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array("NUMERO_REGISTRE_TROP_LONG"=> array(), "DOCUMENT_PDF_MANQUANT"=> array(), "NOM_DIRIGEANT_INVALIDE" => array(),
				                    "NOM_COMMERCIAL_INVALIDE"  => array(), "REGISTRE_EXISTANT"    => array(), "NUMERO_REGISTRE_TROP_COURT"=> array());
	
		$foundItems         = array();
	
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
		if( $this->_request->isPost( )) {						
			$postData       = $this->_request->getPost();
			$dbsourceParams = array(
					                "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
					                "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
					                "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
					                "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "" ),
					                "isDefaultAdapter" => 0);
			try{
				$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
				$dbSource->getConnection();
			} catch( Zend_Db_Adapter_Exception $e ) {
				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
			} catch( Zend_Exception $e ) {
				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
			}				
			if( empty( $errorMessages )) {
				$checkedLocalites     = (isset( $postData["localites"]  )) ? $postData["localites"]            : array();
				$startAnnee           = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : date("Y");
				$endAnnee             = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
				$categoryKey          = (isset( $postData["category"]   )) ? trim(   $postData["category"] )   : "A";
				$srcPath              = (isset( $postData["srcpath"]    )) ? trim(   $postData["srcpath"] )    : $defaultData["srcpath"];
				$years                = array();
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter         =  new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				
				if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				   $years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
				}
				if(!count($years)) {
					$errorMessages[]  = "Veuillez préciser une plage de périodes";
				}
				if(!count( $checkedLocalites )) {
					$errorMessages[]  = "Veuillez selectionner les localités concernées";
				}
                if(empty( $errorMessages )) {
					foreach( $checkedLocalites as $localitecode ) {
						     $foundItems[$localitecode]["totalPDF"] = $foundItems[$localitecode]["totalRegistres"] = "NC";
							 $localiteLibelle                       = (isset($localites[$localitecode])) ? $localites[$localitecode] : null;
							 if( null==$localiteLibelle) {
								 $errorMessages[]                   = "Localité Invalide";
								 continue;
							 }
						     foreach( $years as $annee ) {
								      $numeroInitial  = sprintf("BF%s%d%s",$localitecode, $annee, $categoryKey );
									  $dbSourceSelect = $dbSource->select()->from(array("A" => "archive"), array("A.analyse","A.date_enregistrement","A.date_deb"))
					                                                       ->joinLeft(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nomged_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive"))
					                                                       ->where("F.nom_fichier LIKE ?", "%".strip_tags($numeroInitial)."%");
					                  $registres      = $dbSource->fetchAll( $dbSourceSelect );									  
									  
									  $foundItems[$localitecode][$annee]["registres"]               = array();
									  $foundItems[$localitecode][$annee]["totalRegistres"]          = "NC";
									  $foundItems[$localitecode][$annee]["totalPDF"]                = "NC";
									  
									  foreach( $registres as $registreRow ) {
										       $rccmFilename                                        = $registreRow["nomged_fichier"];
											   $foundItems[$localitecode][$annee]["registres"][]    = $rccmFilename;
											   $foundItems[$localitecode][$annee]["totalRegistres"] = $foundItems[$localitecode][$annee]["totalRegistres"] + 1;
											   $foundItems[$localitecode]["totalRegistres"]         = $foundItems[$localitecode]["totalRegistres"] + 1;
											   
											   $pathOfDocument  = $srcPath . DS . $registreRow["id_archive"] . DS . $rccmFilename;
											   if( file_exists( $pathOfDocument )) {
												   $foundItems[$localitecode][$annee]["totalPDF"]   = $foundItems[$localitecode][$annee]["totalPDF"] + 1;
												   $foundItems[$localitecode]["totalPDF"]           = $foundItems[$localitecode]["totalPDF"]+1;
											   }									       
									  }
							 }
					}
				}					
			}			
			if(empty( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
			    $this->_helper->layout->disableLayout(true);
				$checkPointOutputHtml = $this->view->partial("registres/checkpointpdf.phtml",array("rows"=> $foundItems,"annees"=> $years,"localites"=> $localites,"checkedLocalites"=>$checkedLocalites,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee, "category" => $categoryKey));
				
				$me                   = Sirah_Fabric::getUser();
                $PDF                  = new Sirah_Pdf_Default();
                $PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
                $PDF->SetTitle("Etat des lieux de la base de données des RCCM");
		
		       $margins                 = $PDF->getMargins();
		       $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		       $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		       $PDF->AddPage();
		
		       $PDF->Ln(10);				     	 
		       $PDF->SetFont("helvetica" , "" , 11 );				     	 
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
		
		$this->render("checkpoint");
	}
	
	
	public function checkpointallAction()
	{
		$this->view->title  = "Etat des lieux des RCCM";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("dbsource_host" => "localhost","dbsource_user" =>"root","dbsource_password" => "","start_annee" => 2000,"end_annee" => 2016, "localites" => array("OUA","BBD","BFR"),
				                    "dbsource_name" => "sigar","dbsource_tablename"=>"archive", "srcpath" => "G:\\DATAS_RCCM\\GED\\SOURCE", "category" => "A");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array("NUMERO_REGISTRE_TROP_LONG"=> array(), "DOCUMENT_PDF_MANQUANT"=> array(), "NOM_DIRIGEANT_INVALIDE" => array(),
				                    "NOM_COMMERCIAL_INVALIDE"  => array(), "REGISTRE_EXISTANT"    => array(), "NUMERO_REGISTRE_TROP_COURT"=> array());
	
		$foundItems         = array();
	
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
		if( $this->_request->isPost( )) {						
			$postData       = $this->_request->getPost();
			$dbsourceParams = array(
					                "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
					                "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
					                "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
					                "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "" ),
					                "isDefaultAdapter" => 0);
			try{
				$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
				$dbSource->getConnection();
			} catch( Zend_Db_Adapter_Exception $e ) {
				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
			} catch( Zend_Exception $e ) {
				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
			}				
			if( empty( $errorMessages )) {
				$checkedLocalites     = (isset( $postData["localites"]  )) ? $postData["localites"]            : array();
				$startAnnee           = (isset( $postData["start_annee"])) ? intval( $postData["start_annee"]) : date("Y");
				$endAnnee             = (isset( $postData["end_annee"]  )) ? intval( $postData["end_annee"])   : intval($startAnnee)+1;
				$srcPath              = (isset( $postData["srcpath"]    )) ? trim(   $postData["srcpath"] )    : $defaultData["srcpath"];
				$years                = array();
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter         =  new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				
				if(intval($startAnnee) && ($startAnnee < $endAnnee) ) {
				   $years = Sirah_Functions_ArrayHelper::rangeWithCombine( $startAnnee, $endAnnee );
				}
				if(!count($years)) {
					$errorMessages[]  = "Veuillez préciser une plage de périodes";
				}
				if(!count( $checkedLocalites )) {
					$errorMessages[]  = "Veuillez selectionner les localités concernées";
				}
                if(empty( $errorMessages )) {
					foreach( $checkedLocalites as $localitecode ) {
						     $foundItems[$localitecode]["totalPDF"] = $foundItems[$localitecode]["totalRegistres"] = "NC";
							 $localiteLibelle                       = (isset($localites[$localitecode])) ? $localites[$localitecode] : null;
							 if( null==$localiteLibelle) {
								 $errorMessages[]                   = "Localité Invalide";
								 continue;
							 }
						     foreach( $years as $annee ) {
								      $numeroInitial  = sprintf("BF%s%d",$localitecode, $annee);
									  $dbSourceSelect = $dbSource->select()->from(array("F" => "fichier"),  array("F.nom_fichier","F.nomged_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive"))
					                                                       ->where("F.nom_fichier LIKE ?", "%".strip_tags($numeroInitial)."%")
																		   ->order(array("F.nom_fichier ASC"));
					                  $registres      = $dbSource->fetchAll( $dbSourceSelect );									  
									  
									  $foundItems[$localitecode][$annee]["registres"]               = array();
									  $foundItems[$localitecode][$annee]["totalRegistres"]          = "NC";
									  $foundItems[$localitecode][$annee]["totalPDF"]                = "NC";
									  $foundItems[$localitecode][$annee]["files"]                   = array();
									  
									  foreach( $registres as $registreRow ) {
										       $rccmFilename                                        = $registreRow["nomged_fichier"];
											   $foundItems[$localitecode][$annee]["registres"][]    = $rccmFilename;
											   $foundItems[$localitecode][$annee]["totalRegistres"] = $foundItems[$localitecode][$annee]["totalRegistres"] + 1;
											   $foundItems[$localitecode]["totalRegistres"]         = $foundItems[$localitecode]["totalRegistres"]         + 1;
											   
											   $pathOfDocument                                      = $srcPath . DS . $registreRow["id_archive"] . DS . $rccmFilename;
											   if( file_exists( $pathOfDocument )) {
												   $foundItems[$localitecode][$annee]["totalPDF"]   = $foundItems[$localitecode][$annee]["totalPDF"] + 1;
												   $foundItems[$localitecode]["totalPDF"]           = $foundItems[$localitecode]["totalPDF"]+1;
												   $foundItems[$localitecode][$annee]["files"][]    = str_replace(array(".pdf",".PDF"), "", $registreRow["nom_fichier"]);
											   }									       
									  }
							 }
					}
				}					
			}	

            //print_r($foundItems);	die();		
			if(empty( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
			    $this->_helper->layout->disableLayout(true);
				$checkPointOutputHtml = $this->view->partial("registres/checkpointallpdf.phtml",array("rows"=>$foundItems,"annees"=> $years,"localites"=> $localites,"checkedLocalites"=>$checkedLocalites,"startAnnee"=>$startAnnee,"endAnnee"=>$endAnnee));
				
				$me                   = Sirah_Fabric::getUser();
                $PDF                  = new Sirah_Pdf_Default();
                $PDF->SetCreator(sprintf("%s %s", $me->lastname, $me->firstname ));
                $PDF->SetTitle("Etat des lieux de la base de données des RCCM");
		
		       $margins                 = $PDF->getMargins();
		       $contenuWidth            = $PDF->getPageWidth()-$margins["left"]-$margins["right"];
		       $PDF->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		       $PDF->AddPage();
		
		       $PDF->Ln(10);				     	 
		       $PDF->SetFont("helvetica" , "" , 11 );				     	 
		       $PDF->writeHTML($checkPointOutputHtml, true , false , true , false , '' );	
		 
		       echo $PDF->Output("checkpointall.pdf","D");
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
		
		$this->render("checkpointall");
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
	
	
	public function extractphysiquesAction()
	{
		$this->view->title  = "EXTRAIRE des RCCM  PHYSIQUES";
	
		$defaultData        = array("localitekey" => null, "extract_pages" => null , "srcfolder"=> "UNE_PAGE",
				                    "rootpath" => "G:\DOSSIER_A_RETRAITER", "destpath" => "G:\\BFRCCM");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$extractedItems     = array();
		$localites          = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS","BBD");
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");

	
		if( $this->_request->isPost() ) {								
			$postData       = $this->_request->getPost();
			$rootPath       = (isset( $postData["rootpath"]  )) ? $postData["rootpath"] : "G:\DOSSIER_A_RETRAITER";
			$srcFolder      = (isset( $postData["srcfolder"] )) ? $postData["srcfolder"]: "UNE_PAGE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "G:\\BFRCCM";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$extractPageStr = (isset( $postData["extract_pages"] )) ? $postData["extract_pages"] : "1";
			$srcPath        = "";
			$registres      = array();
				
			if( empty( $kl )) {
				$errorMessages[] = "Veuillez selectionner une localité valide";
			}
			if( empty( $ka )) {
				$errorMessages[] = "Veuillez selectionner une année valide";
			}
			if( !empty( $kl) && !empty( $ka )) {
				$srcPath         = $rootPath . DS . $srcFolder;
				//$srcPath       = $rootPath . DS . strtoupper( $kl ). DS . $ka . DS . $srcFolder;
			}
			if( !is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath );
			}
			if( !is_dir( $destPath  )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'a pas été trouvé. Veuillez vérifier", $destPath  );
			}
			if( empty( $errorMessages )) {
				$srcDir  = dir( $srcPath );
				while( false !== ( $rccmFilename = $srcDir->read()) ) {
					if( $rccmFilename == "." || $rccmFilename == ".." || empty( $rccmFilename ))
						continue;
					$searchNum      = strtoupper(sprintf("BF%s%dA", $kl, $ka ));
					$rccmCleanNum   = strtoupper(sprintf("%s%04d" , $searchNum, intval(str_replace( $searchNum , "" , strstr( $rccmFilename , ".", true )))));
					$rccmNum        = $registres[] = strstr( $rccmFilename , ".", true );
					$newFileDir     = $destPath . DS . strtoupper( $rccmCleanNum );
					//$newFileDir   = $destPath . DS . strtoupper( $kl ) . DS . "PHYSIQUES". DS . strtoupper( $rccmCleanNum );
					$rccmFilePath   = $srcPath. DS . $rccmFilename;
					$rccmFrFilename = $rccmCleanNum."-FR.pdf";
					$rccmPsFilename = $rccmCleanNum."-PS.pdf";
					$hasSaved       = false;											

					if( file_exists( $newFileDir. DS . $rccmFrFilename) || file_exists( $newFileDir. DS . $rccmPsFilename ) ) {
						continue;
					}					
					if(!is_dir( $newFileDir )) {
						@chmod( $newFileDir , 0777 );
						 mkdir( $newFileDir , 0777 , true );
					}	
					if(!empty( $extractPageStr ) && file_exists( $rccmFilePath )) {
					   try{
						$pdfDest      = new FPDI();
						$pdfDest->AddPage();
						$pdfDest->setSourceFile(  $rccmFilePath  );
						$extractPages = preg_split("/[\s,;]/", $extractPageStr );
						$canSave      = false;
						if( count(   $extractPages )) {
							foreach( $extractPages as $pageKey ) {
								if( $i = intval( $pageKey)) {
									$pdfDest->useTemplate($pdfDest->importPage($i));
									$canSave  = true;
								}
							}
							if( $canSave ) {
								$formulaire = $newFileDir. DS . $rccmFrFilename;
								$pdfDest->Output( $formulaire , "F");
								$hasSaved   = true;
							}
						}
					   } catch ( Exception $e ) {
					   	
					   }
					}
					$fileDest  = $newFileDir. DS . $rccmPsFilename;
					if( !file_exists( $fileDest ) && file_exists( $rccmFilePath  ) && $hasSaved ) {
						if( true == @copy( $rccmFilePath , $fileDest )) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
					}
				}
			}				
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT D'EXTRACTION DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count( $extractedItems )." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
					
				if( count( $renamedItems )) {
					echo "1-) ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				if( count( $renamedItems )) {
					echo "2-) ------------------------------------------------- LISTE DES REGISTRES NON COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $notSavedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
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
		$this->render("extractphysiques");
	}
	
	
	public function extractphysiques2Action()
	{
		$this->view->title  = "EXTRAIRE des RCCM  PHYSIQUES";
	
		$defaultData        = array("localitekey" => null,"rootpath" => "G:\\TRAITEMENT_SANGARE", "destpath" => "G:\\BFRCCM");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$extractedItems     = array();
		$localites          = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS","BBD");
		$annees             = array("2001"=> "2001", "2002"=> "2002", "2003"=> "2003", "2004"=> "2004", "2005"=> "2005", "2007"=> "2007",
		                            "2008"=> "2008", "2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                    "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
	
		if( $this->_request->isPost() ) {								
			$postData       = $this->_request->getPost();
			$rootPath       = (isset( $postData["rootpath"]  )) ? $postData["rootpath"] : "G:\\TRAITEMENT_SANGARE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "G:\\BFRCCM";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$srcPath        = "";
			$registres      = array();
				
			if( empty( $kl )) {
				$errorMessages[] = "Veuillez selectionner une localité valide";
			}
			if( empty( $ka )) {
				$errorMessages[] = "Veuillez selectionner une année valide";
			}
			if( !empty( $kl) && !empty( $ka )) {
				$srcPath         = $rootPath . DS . "PersonnesPhysiques" . DS . strtoupper( $kl ). DS . $ka ;
			}
			if( !is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath );
			}
			if( !is_dir( $destPath  )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'a pas été trouvé. Veuillez vérifier", $destPath  );
			}
			if( empty( $errorMessages ) ) {				
				foreach(glob($srcPath."/*",GLOB_ONLYDIR)  as $rccmFileDirectory ) {
				foreach(glob($rccmFileDirectory."/*.pdf") as $rccmFilePath ) {
					$rccmFilename       = str_ireplace("/","",strrchr($rccmFilePath,"/")); 
					$searchNum          = strtoupper(sprintf("BF%s%dA", $kl, $ka ));
					$rccmCleanNum       = strtoupper(sprintf("%s%04d" , $searchNum, intval(str_ireplace(array("-FR","-ST", $searchNum), "" , strstr( $rccmFilename , ".", true )))));
					$rccmNum            = $registres[] = strstr( $rccmFilename , ".", true );
					$newFileDir         = $destPath . DS . strtoupper( $kl ) . DS . "PHYSIQUES". DS . strtoupper( $rccmCleanNum );
					$rccmFrFilename     = $rccmCleanNum."-FR.pdf";
					$rccmPsFilename     = $rccmCleanNum."-PS.pdf";
					$rccmStatutFilename = $rccmCleanNum."-ST.pdf";	

					if( file_exists( $newFileDir. DS . $rccmFrFilename) && file_exists( $newFileDir. DS . $rccmPsFilename ) && file_exists( $newFileDir. DS . $rccmStatutFilename)) {
						continue;
					}
					if(!is_dir( $newFileDir )) {
						@chmod( $newFileDir, 0777 );
						mkdir(  $newFileDir, 0777 , true );
					}	
					//Si c'est un formulaire
					$formulaireDest       = $newFileDir. DS . $rccmFrFilename;
					if((FALSE != stripos($rccmFilename, "-FR")) && !file_exists($formulaireDest)) {						
						if( true == @copy( $rccmFilePath , $formulaireDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						continue;
					}
					//Si c'est un statut
					$statutDest       = $newFileDir. DS . $rccmStatutFilename;
					if((FALSE != stripos($rccmFilename, "-ST")) && !file_exists($statutDest)) {						
						if( true == @copy( $rccmFilePath , $statutDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						continue;
					}
					//Si c'est le fond documentaire
					$psDest       = $newFileDir. DS . $rccmPsFilename;
					if(!file_exists($psDest)) {						
						if( true == @copy( $rccmFilePath , $psDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
					}					 				
				}
			  }
			}				
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT D'EXTRACTION DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count( $extractedItems )." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
					
				if( count( $renamedItems )) {
					echo "1-) ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				if( count( $renamedItems )) {
					echo "2-) ------------------------------------------------- LISTE DES REGISTRES NON COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $notSavedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
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
		$this->render("extractphysiques2");
	}
	
	public function extractphysiques3Action()
	{
		$this->view->title  = "EXTRAIRE des RCCM  PHYSIQUES";
	
		$defaultData        = array("localitekey" => null,"rootpath" => "G:\\TRAITEMENT_SANGARE", "destpath" => "G:\\BFRCCM");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$extractedItems     = array();
		$localites          = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS","BBD");
		$annees             = array("2001"=> "2001", "2002"=> "2002", "2003"=> "2003", "2004"=> "2004", "2005"=> "2005", "2007"=> "2007",
		                            "2008"=> "2008", "2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                    "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
	
		if( $this->_request->isPost() ) {								
			$postData       = $this->_request->getPost();
			$rootPath       = (isset( $postData["rootpath"]  )) ? $postData["rootpath"] : "G:\\TRAITEMENT_SANGARE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "G:\\BFRCCM";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$srcPath        = "";
			$registres      = array();
				
			if( empty( $kl )) {
				$errorMessages[] = "Veuillez selectionner une localité valide";
			}
			if( empty( $ka )) {
				$errorMessages[] = "Veuillez selectionner une année valide";
			}
			if( !empty( $kl) && !empty( $ka )) {
				$srcPath         = $rootPath . DS . "PersonnesPhysiques" . DS . strtoupper( $kl ). DS . $ka ;
			}
			if( !is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath );
			}
			if( !is_dir( $destPath  )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'a pas été trouvé. Veuillez vérifier", $destPath  );
			}
			if( empty( $errorMessages ) ) {				
				foreach(glob($srcPath."/*.pdf") as $rccmFilePath ) {
					$rccmFilename       = str_ireplace("/","",strrchr($rccmFilePath,"/")); 
					$searchNum          = strtoupper(sprintf("BF%s%dA", $kl, $ka ));
					$rccmCleanNum       = strtoupper(sprintf("%s%04d" , $searchNum, intval(str_ireplace(array("-FR","-ST", $searchNum), "" , strstr( $rccmFilename , ".", true )))));
					$rccmNum            = $registres[] = strstr( $rccmFilename , ".", true );
					$newFileDir         = $destPath . DS . strtoupper( $kl ) . DS . "PHYSIQUES". DS . strtoupper( $rccmCleanNum );
					$rccmFrFilename     = $rccmCleanNum."-FR.pdf";
					$rccmPsFilename     = $rccmCleanNum."-PS.pdf";
					$rccmStatutFilename = $rccmCleanNum."-ST.pdf";	

					if( file_exists( $newFileDir. DS . $rccmFrFilename) && file_exists( $newFileDir. DS . $rccmPsFilename ) && file_exists( $newFileDir. DS . $rccmStatutFilename)) {
						continue;
					}
					if(!is_dir( $newFileDir )) {
						@chmod( $newFileDir, 0777 );
						mkdir(  $newFileDir, 0777 , true );
					}	
					//Si c'est un formulaire
					$formulaireDest       = $newFileDir. DS . $rccmFrFilename;
					if((FALSE != stripos($rccmFilename, "-FR")) && !file_exists($formulaireDest)) {						
						if( true == @copy( $rccmFilePath , $formulaireDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						if(!file_exists($srcPath. DS .$rccmCleanNum. DS .$rccmFrFilename)) {
							@rename( $rccmFilePath, $srcPath. DS .$rccmCleanNum. DS .$rccmFrFilename);
						} else {
							@unlink( $rccmFilePath );
						}
						continue;
					}
					//Si c'est un statut
					$statutDest       = $newFileDir. DS . $rccmStatutFilename;
					if((FALSE != stripos($rccmFilename, "-ST")) && !file_exists($statutDest)) {						
						if( true == @copy( $rccmFilePath , $statutDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
                        if(!file_exists($srcPath. DS .$rccmCleanNum. DS .$rccmStatutFilename)) {
							@rename( $rccmFilePath, $srcPath. DS .$rccmCleanNum. DS .$rccmStatutFilename);
						} else {
							@unlink( $rccmFilePath );
						}						
						continue;
					}
					//Si c'est le fond documentaire
					$psDest       = $newFileDir. DS . $rccmPsFilename;
					if(!file_exists($psDest)) {						
						if( true == @copy( $rccmFilePath , $psDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						if(!file_exists($srcPath."/".$rccmCleanNum."/".$rccmFilename.".pdf")) {
							@rename( $rccmFilePath, $srcPath."/".$rccmCleanNum."/".$rccmFilename.".pdf");
						} else {
							@unlink( $rccmFilePath );
						}
					}					 				
				}
			}				
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT D'EXTRACTION DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count( $extractedItems )." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
					
				if( count( $renamedItems )) {
					echo "1-) ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				if( count( $renamedItems )) {
					echo "2-) ------------------------------------------------- LISTE DES REGISTRES NON COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $notSavedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
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
		$this->render("extractphysiques3");
	}	
	
	
	
	public function extractmoralesAction()
	{
		$this->view->title  = "EXTRAIRE des RCCM  PHYSIQUES";
	
		$defaultData        = array("localitekey" => null, "extract_pages" => null , "srcfolder"=> "UNE_PAGE",
				                    "rootpath" => "G:\\DATAS_RCCM\\GED\\DESTINATION", "destpath" => "G:\\BFRCCM");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$extractedItems     = array();
		$localites          = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS","BBD");
		$annees             = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				"2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
	
		if( $this->_request->isPost() ) {					
			$postData       = $this->_request->getPost();
			$rootPath       = (isset( $postData["rootpath"]  )) ? $postData["rootpath"] : "G:\DOSSIER_A_RETRAITER";
			$srcFolder      = (isset( $postData["srcfolder"] )) ? $postData["srcfolder"]: "UNE_PAGE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "G:\\DATAS_RCCM\\GED\\BFRCCM";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$extractPageStr = (isset( $postData["extract_pages"] )) ? $postData["extract_pages"] : "1";
			$srcPath        = "";
			$registres      = array();
	
			if( empty( $kl )) {
				$errorMessages[] = "Veuillez selectionner une localité valide";
			}
			if( empty( $ka )) {
				$errorMessages[] = "Veuillez selectionner une année valide";
			}
			if( !empty( $kl) && !empty( $ka )) {
				$srcPath         = $rootPath . DS . strtoupper( $kl ). DS . $ka . DS . $srcFolder;
			}
			if( !is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath );
			}
			if( !is_dir( $destPath  )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'a pas été trouvé. Veuillez vérifier", $destPath  );
			}
			if( empty( $errorMessages )) {
				$srcDir  = dir( $srcPath );
				while( false !== ( $rccmFilename = $srcDir->read()) ) {
					if( $rccmFilename == "." || $rccmFilename == ".." || empty( $rccmFilename ))
						continue;
					$searchNum      = strtoupper(sprintf("BF%s%dB", $kl, $ka ));
					$rccmCleanNum   = strtoupper(sprintf("%s%04d" , $searchNum, intval(str_replace( $searchNum , "" , strstr( $rccmFilename , ".", true )))));
					$rccmNum        = $registres[] = strstr( $rccmFilename , ".", true );
					$newFileDir     = $destPath . DS . strtoupper( $kl ) . DS . "MORALES". DS . strtoupper( $rccmCleanNum );
					$rccmFilePath   = $srcPath. DS . $rccmFilename;
					$rccmFrFilename = $rccmCleanNum."-FR.pdf";
					$rccmPsFilename = $rccmCleanNum."-PS.pdf";
					$hasSaved       = false;
					if( file_exists( $newFileDir. DS . $rccmFrFilename) || file_exists( $newFileDir. DS . $rccmPsFilename ) ) {
						continue;
					}	                	
					if(!is_dir( $newFileDir )) {
						@chmod( $newFileDir, 0777 );
						mkdir( $newFileDir , 0777 , true );
					}	
					if( !empty( $extractPageStr ) && file_exists( $rccmFilePath )) {
						$pdfDest      = new FPDI();
						$pdfDest->AddPage();
						$pdfDest->setSourceFile(  $rccmFilePath  );
						$extractPages = preg_split("/[\s,;]/", $extractPageStr );
						$canSave      = false;
						if( count(   $extractPages )) {
							foreach( $extractPages as $pageKey ) {
								if( $i = intval( $pageKey)) {
									$pdfDest->useTemplate($pdfDest->importPage($i));
									$canSave  = true;
								}
							}
							if( $canSave ) {
								$formulaire = $newFileDir. DS . $rccmFrFilename;
								$pdfDest->Output( $formulaire , "F");
								$hasSaved   = true;
							}
						}
					}
					$fileDest  = $newFileDir. DS . $rccmPsFilename;
					if( !file_exists( $fileDest ) && file_exists( $rccmFilePath  ) && $hasSaved ) {
						if( true == @copy( $rccmFilePath , $fileDest )) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
					}
				}
			}	
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT D'EXTRACTION DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count( $extractedItems )." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
					
				if( count( $renamedItems )) {
					echo "1-) ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				if( count( $renamedItems )) {
					echo "2-) ------------------------------------------------- LISTE DES REGISTRES NON COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $notSavedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
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
		$this->render("extractmorales");
	}
	
	public function extractmorales2Action()
	{
		$this->view->title  = "EXTRAIRE des RCCM  MORALES";
	
		$defaultData        = array("localitekey" => null,"rootpath" => "G:\\TRAITEMENT_SANGARE", "destpath" => "G:\\BFRCCM");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$extractedItems     = array();
		$localites          = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS","BBD");
		$annees             = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                    "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
	
		if( $this->_request->isPost() ) {					
			$postData       = $this->_request->getPost();
			$rootPath       = (isset( $postData["rootpath"]  )) ? $postData["rootpath"] : "G:\\TRAITEMENT_SANGARE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "G:\\BFRCCM";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$srcPath        = "";
			$registres      = array();
	
			if( empty( $kl )) {
				$errorMessages[] = "Veuillez selectionner une localité valide";
			}
			if( empty( $ka )) {
				$errorMessages[] = "Veuillez selectionner une année valide";
			}
			if( !empty( $kl) && !empty( $ka )) {
				$srcPath         = $rootPath . DS . "PersonnesMorales" . DS . strtoupper( $kl ). DS . $ka ;
			}
			if( !is_dir( $srcPath )) {
				$errorMessages[] = sprintf("Le dossier source %s n'a pas été trouvé. Veuillez vérifier", $srcPath );
			}
			if( !is_dir( $destPath  )) {
				$errorMessages[] = sprintf("Le dossier de destination %s n'a pas été trouvé. Veuillez vérifier", $destPath  );
			}
			if( empty( $errorMessages ) ) {				
				foreach(glob($srcPath."/*",GLOB_ONLYDIR)  as $rccmFileDirectory ) {
				foreach(glob($rccmFileDirectory."/*.pdf") as $rccmFilePath ) {
					$rccmFilename       = str_ireplace("/","",strrchr($rccmFilePath,"/")); 
					$searchNum          = strtoupper(sprintf("BF%s%dB", $kl, $ka ));
					$rccmCleanNum       = strtoupper(sprintf("%s%04d" , $searchNum, intval(str_ireplace(array("-FR","-ST", $searchNum), "" , strstr( $rccmFilename , ".", true )))));
					$rccmNum            = $registres[] = strstr( $rccmFilename , ".", true );
					$newFileDir         = $destPath . DS . strtoupper($kl) . DS . "MORALES". DS . strtoupper($rccmCleanNum);
					$rccmFrFilename     = $rccmCleanNum."-FR.pdf";
					$rccmPsFilename     = $rccmCleanNum."-PS.pdf";
					$rccmStatutFilename = $rccmCleanNum."-ST.pdf";	

					if( file_exists( $newFileDir. DS . $rccmFrFilename) && file_exists( $newFileDir. DS . $rccmPsFilename ) && file_exists( $newFileDir. DS . $rccmStatutFilename)) {
						continue;
					}
					if(!is_dir($newFileDir )) {
					   @chmod( $newFileDir, 0777 );
					   mkdir(  $newFileDir, 0777 , true );
					}	
					//Si c'est un formulaire
					$formulaireDest       = $newFileDir. DS . $rccmFrFilename;
					if((FALSE != stripos($rccmFilename, "-FR")) && !file_exists($formulaireDest)) {						
						if( true == @copy( $rccmFilePath , $formulaireDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						continue;
					}
					//Si c'est un statut
					$statutDest       = $newFileDir. DS . $rccmStatutFilename;
					if((FALSE != stripos($rccmFilename, "-ST")) && !file_exists($statutDest)) {						
						if( true == @copy( $rccmFilePath , $statutDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
						continue;
					}
					//Si c'est le fond documentaire
					$psDest       = $newFileDir. DS . $rccmPsFilename;
					if(!file_exists($psDest)) {						
						if( true == @copy( $rccmFilePath , $psDest)) {
							$extractedItems[] = $rccmFilename;
						} else {
							$notSavedItems[]  = $rccmFilename;
						}
					}					 				
				}
			  }
			}	
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT D'EXTRACTION DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count( $extractedItems )." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
					
				if( count( $renamedItems )) {
					echo "1-) ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				if( count( $renamedItems )) {
					echo "2-) ------------------------------------------------- LISTE DES REGISTRES NON COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $notSavedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
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
		$this->render("extractmorales2");
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
		$statutFilePath            = ($isMorale && isset($rccmFilesInfos["statut"])) ? $rccmFilesInfos["statut"] : null;
		try{
			 $pdfRegistre          = new FPDI();
			 $pagesFormulaire      = (file_exists($formulaireFilePath)) ? $pdfRegistre->setSourceFile( $formulaireFilePath ) : 0;
		     $pagesStatut          = (file_exists($statutFilePath    )) ? $pdfRegistre->setSourceFile( $statutFilePath     ) : 0;
			 $pagesComplet         = (file_exists($completFilePath   )) ? $pdfRegistre->setSourceFile( $completFilePath    ) : 0;
		} catch(Exception $e ) {
			/*$errorMessages[]       = sprintf("Le fichier %s ou %s est invalide", $completFilePath, $formulaireFilePath);
			$result                = false;*/
			$pagesFormulaire       = 0;
			$pagesComplet          = 0;
			$pagesStatut           = 0;
		}
		if( $pagesFormulaire && ( $pagesComplet <= $pagesFormulaire )) {
			$errorMessages[]       = sprintf("Le formulaire du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages",$numRccm);
		    $result                = false;
		}
		if( $pagesStatut && ( $pagesComplet <= $pagesStatut )) {
			$errorMessages[]       = sprintf("Le statut du RCCM n° %s n'est pas valide parce qu'il dépasse le fichier complet en nombre de pages", $numRccm);
		    $result                = false;
		}
		if( $pagesComplet <= 1 ) {
			$rccmFilesInfos["incoherence"]  = true;
		}
		/*if( file_exists( $formulaireFilePath)) {
			$logger                = new Logger('MyLogger');
            $pdfToText             = XPDF\PdfToText::create(array('pdftotext.binaries'=> 'F:\webserver\www\binaries\Xpdf\pdftotext.exe','pdftotext.timeout'=> 30,),$logger);
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
		}*/
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
		                               "2009"=>"2009","2010"=>"2010","2011"=> "2011","2012"=>"2012","2013"=> "2013", "2014"=> "2014","2015"=> "2015", "2016"=> "2016", "2017" => "2017");
		/*$defaultMaxNumByYears  = array("2000"=>"2031","2001"=>"2714","2002"=>"3144","2003"=>"3423","2004"=>"3984","2005"=>"4049","2006"=>"3591","2007"=>"4005","2008"=>"3702",
		                               "2009"=>"4900","2010"=>"4394","2011"=> "4794","2012"=>"5776","2013"=> "6480","2014"=> "7115","2015"=>"6613","2016"=> "9230");
        */
		$defaultMaxNumByYears  = array("2000"=>"0","2001"=>"0","2002"=>"0","2003"=>"0","2004"=>"0","2005"=>"12","2006"=>"61","2007"=>"43","2008"=>"47",
		                               "2009"=>"36","2010"=>"58","2011"=> "23","2012"=>"59","2013"=>"74","2014"=> "241","2015"=>"92","2016"=> "193");									   
		$defaultData           = array("srcpath" => "F:\\ERCCM","localite" => "OUA","minNbPages" => 3,"maxNbPagesFr" => 4, "check_documents" => 0, "checked_annees" => $annees);
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
			$checkedAnnees     = (isset( $postData["checked_annees"] )) ? $postData["checked_annees"] : $annees;
			$localiteCode      = (isset( $postData["localite"]       )) ? $postData["localite"]             : $defaultData["localite"];
			$localiteid        = (isset( $localiteIDS[$localiteCode] )) ? $localiteIDS[$localiteCode]       : 0;
			$srcPath           = (isset( $postData["srcpath"]        )) ? $postData["srcpath"]              : $defaultData["srcpath"];
			$minNbPages        = (isset( $postData["minNbPages"]     )) ? intval($postData["minNbPages"])   : intval($defaultData["minNbPages"]);
			$maxNbPagesFr      = (isset( $postData["maxNbPagesFr"]   )) ? intval($postData["maxNbPagesFr"]) : intval($defaultData["maxNbPagesFr"]);
			$maxNumByYears     = $defaultMaxNumByYears = (isset( $postData["maxNumByYears"]  ) && count($postData["maxNumByYears"])) ? $postData["maxNumByYears"] : $defaultMaxNumByYears;
			$totalReste        = 0;
			$check_documents   = (isset( $postData["check_documents"])) ? intval($postData["check_documents"]) : intval($defaultData["check_documents"]);
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
						 $totalRegistres = count(glob($srcPath."/".$localiteCode."/".$checkedAnneeVal."/*", GLOB_ONLYDIR));
						 $lastNum        = (isset($postData["maxNumByYears_".$checkedAnneeVal]))? intval($postData["maxNumByYears_".$checkedAnneeVal]) : $totalRegistres ;
						 $reste          = $lastNum - $totalRegistres;
						 $totalReste    += $reste;
						 $output        .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d - Total des documents : %s retraités / %s, Reste à collecter : %s',$checkedAnneeVal,number_format($totalRegistres, 0, " "," "),number_format($lastNum, 0, " "," "), number_format($reste, 0, " ", " ") )."</strong></td> </tr>";
						 //print_r($postData);die();
						 $j              = 0;
						 if( $totalRegistres ) {
							 for( $i =1; $i<= $lastNum ; $i++ ) {
								  $numKey              = sprintf("%04d", $i);
								  $numRccmPhysique     = sprintf("BF%s%dA%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmMorale       = sprintf("BF%s%dB%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmModification = sprintf("BF%s%dM%04d", $localiteCode, $checkedAnneeVal, $i);
						          $numRccmSurete       = sprintf("BF%s%dS%04d", $localiteCode, $checkedAnneeVal, $i);
						          $bgColor             = "style=\"background-color:".$this->view->cycle(array("#FFFFFF","#F5F5F5"))->next()."\"";
								  $checkedDocuments    = array();
								  $invalidMsg          = "";
								  
						          if( file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-FR.pdf" ) ||
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique.".pdf" ) ||
                                      file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique.".pdf" ) ) {							  
									  $checkedDocuments["numero"]     = $numRccmPhysique;
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmPhysique."/".$numRccmPhysique."-PS.pdf";
								  } elseif(
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-FR.pdf" ) ||
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale.".pdf" ) ||
                                      file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmMorale;
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-PS.pdf";
									  $checkedDocuments["statut"]     = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmMorale."/".$numRccmMorale."-ST.pdf";
					              } elseif(
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-FR.pdf" ) ||
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification.".pdf" ) ||
                                      file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmModification;
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-PS.pdf";
									  if( file_exists($srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-ST.pdf")) {
										  $checkedDocuments["statut"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmModification."/".$numRccmModification."-ST.pdf";
									  }
					              } elseif(
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-FR.pdf" ) ||
						              file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete.".pdf" ) ||
                                      file_exists( $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete.".pdf" )) {
									  $checkedDocuments["numero"]     = $numRccmSurete;
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-PS.pdf";
									  if( file_exists($srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-ST.pdf")) {
										  $checkedDocuments["statut"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccmSurete."/".$numRccmSurete."-ST.pdf";
									  }
					              } else {
									  $checkedDocuments["numero"]     = $numRccm = sprintf("BF%s%dA|B|M|S%04d", $localiteCode, $checkedAnneeVal, $i);
									  $checkedDocuments["formulaire"] = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccm."/".$numRccm."-FR.pdf";
						              $checkedDocuments["personnel"]  = $srcPath."/".$localiteCode."/".$checkedAnneeVal."/".$numRccm."/".$numRccm."-PS.pdf";
							          $invalidMsg                    .= sprintf("Ce registre n'est pas disponible dans notre base de données, %s", $numRccm);
									  									  
									  $numKey                         = sprintf("%04d", $i);
									  //On enregistre dans la base de données que nous ne trouvons pas ce registre
									  $missingData                    = array("numero"=>$numRccm,"numkey"=>$numKey,"rheanum"=>"","annee"=>$checkedAnneeVal,"found"=>0,"observations"=>$invalidMsg,
									                                          "localite"=>$localiteCode,"localiteid"=>$localiteid,"creationdate"=>time(),"creatorid"=>$me->userid,"rhearegistreid"=>0);
									  
									      $dbAdapter->delete( $prefixName ."rheaweb_registres_missings","numero='".$numRccm."'");
										  $dbAdapter->delete( $prefixName ."rheaweb_registres_missings","numkey='".$numKey."'");
									  if( $dbAdapter->insert( $prefixName ."rheaweb_registres_missings", $missingData)) {
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
										  $output .=" <tr style=\"background-color:#FF0000;color:white;\"><td>".$j."</td> <td> ".$checkedDocuments["numero"]."</td><td> ".$invalidMsg." </td> </tr> ";
									  }										  									  
								  }
							 }
						 } else {
							  $output .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d', $checkedAnneeVal )."</strong></td> </tr>";
			                  $output .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;background-color:#FF0000;color:white;\"> NON COLLECTE </td> </tr>";
						 }
				}
				       $output        .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('TOTAL GENERAL DES DOCUMENTS A COLLECTER : %s', number_format($totalReste , 0,' ',' ') )."</strong></td> </tr>";
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
	
	
	public function missingsAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Liste des documents manquants non disponibles dans la base de données du FNRCCM"  ;
		
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters               = array("libelle"=> null,"numero" => null,"localiteid" =>0,"searchQ" => null,"annee"=>null,"found"=>0);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$registreids           = (isset($params["registreids"]))? $params["registreids"]: array();
        if(count( $registreids )) {
			$registres         = $model->getListMissings(array("registreids"=> $registreids ));
		} else {
			$registres         = $model->getListMissings( $filters , $pageNum , $pageSize);
		}
		$paginator             = $model->getListMissingsPaginator($filters);
		
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid","libelle") , array() , null , null , false );		 
		$this->view->filters   = $filters;
		$this->view->params    = $params;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $this->view->maxitems = $pageSize;	
	}
	
	public function missingspdfAction()
	{
		$this->_helper->layout->disableLayout(true);			
		$this->view->title  = "Liste des documents manquants non disponibles dans la base de données du FNRCCM"  ;
		
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		
		$filters               = array("libelle"=> null,"numero" => null,"localiteid" =>0,"searchQ" => null,"annee"=>null,"found"=>0);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$registreids           = (isset($params["registreids"]))? $params["registreids"]: array();
        if(count( $registreids )) {
			$registres         = $model->getListMissings(array("registreids"=> $registreids ));
		} else {
			$registres         = $model->getListMissings( $filters , $pageNum , $pageSize);
		}
		$paginator             = $model->getListMissingsPaginator($filters);
		
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid","libelle") , array() , null , null , false );		 
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $this->view->maxitems = $pageSize;	
	}
	
	public function missingsdeleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
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
				      if(!$dbAdapter->delete($prefixName."rheaweb_registres_missings", "registreid=".$id)) {
						  $errorMessages[]  = sprintf("Impossible de supprimer le registre id#%d", $id);
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
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres manquants selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres manquants selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/registres/list");
		}
	}
	
	public function findmissingsAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		$me                       = Sirah_Fabric::getUser();
		
		$localites                = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS              = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                   = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                  "2009"=>"2009","2010"=>"2010","2011"=> "2011","2012"=>"2012","2013"=> "2013", "2014"=> "2014","2015"=> "2015", "2016"=> "2016", "2017" => "2017");		 							   
		$defaultData              = array("annee" => 2000,"localite" => "OUA", "startword"=>null,"maxres"=>1000);
		$foundRegistres           = array();
		$errorMessages            = array();
		
		if( $this->_request->isPost() ) {
			$modelTable           = $model->getTable();
		    $prefixName           = $tablePrefix = $modelTable->info("namePrefix");
		    $dbAdapter            = $modelTable->getAdapter();
			$postData             = $this->_request->getPost();
			$startword            = (isset($postData["startword"]))? $postData["startword"]: "";
			$annee                = (isset($postData["annee"]    ))? $postData["annee"]    : 0;
			$localiteCode         = (isset($postData["localite"] ))? $postData["localite"] : $defaultData["localite"];
			$localiteid           = (isset($localiteIDS[$localiteCode]))?$localiteIDS[$localiteCode] : 0;
			$maxres               = (isset($postData["maxres"] )) ? intval($postData["maxres"]) : 1000;
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			if( !isset( $localiteIDS[$localiteCode] ) ) {
				$localiteCode     = null;
			}
			if( !isset($annees[$annee] )) {
				$annee            = null;
			}
			if(!$strNotEmptyValidator->isValid($startword) && (!$strNotEmptyValidator->isValid($localiteCode) || !$strNotEmptyValidator->isValid($annee)) ) {
			    $errorMessages[]  = "Veuillez renseigner des critères valides de recherche";
			}
			if(!$strNotEmptyValidator->isValid($startword) &&  $strNotEmptyValidator->isValid($localiteCode) && $strNotEmptyValidator->isValid($annee)) {
			   $startword         = sprintf("BF%s%d*", strtoupper($localiteCode), $annee);
			}
			if( empty( $errorMessages )) {
				$client = new Zend_Http_Client("http://10.1.25.13/rheaweb/dictionary", array("keepalive"=> true,"timeout"=> 1000));
				$client->setConfig(array("strictredirects"=> false));
				//$client->setCookieJar(true);			
				$client->setHeaders(array( "Host"              => "10.1.25.13",
										   "Accept"            => "*/*",
                                           "Accept-encoding"   => "gzip, deflate",
										   "Accept-Language"   => "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
										   "Connection"        => "keep-alive",
										   "Content-type"      => "application/x-www-form-urlencoded",
										   "Origin"            => "http://10.1.25.13",
										   "Referer"           => "http://10.1.25.13/rheaweb/",
										   "Cookie"            => "amfid=817381646; JSESSIONID=63B0182A190C17FB16006C5CAFD45199; docubase_password_cookie=11:hamed.banaoBHME@31",
										   "Connection-Length" => "81",
										   "User-Agent"        => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36",
                                           "X-Powered-By"      => "Banao Hamed"));
			
			    $client->setParameterGet("cmd"    , "wordlist");
                $client->setParameterGet("formId" , 14);
				$client->setParameterGet("fieldId", "f1");
				$client->setParameterGet("dictionaryType", "forForm");
				$client->setParameterGet("maxres" , $maxres);
				$client->setParameterGet("mask"   , $startword);
				//$client->setRawData($rawData);
				
				$rheawebResponse      = $client->request("GET");
				$rheawebRegistresList = $rheawebResponse->getBody();
				$regitresFromUL       = Sirah_Functions_String::ulToArray($rheawebRegistresList);
				
				if( count(   $regitresFromUL )) {
					foreach( $regitresFromUL as $rheawebNumero ) {
						     $rheawebNumRccm   = preg_replace("/\s+/","",$rheawebNumero);
						     $rheawebNumKey    = str_replace(sprintf("BF%s%d", strtoupper($localiteCode),$annee), "", $rheawebNumRccm);
							 $rheawebNumType   = strtoupper(substr($rheawebNumKey, 0, 1));
							 $rheawebNumValue  = intval(substr($rheawebNumKey,1));
							 $cleanNumRccm     = sprintf("BF%s%04d%s%04d", strtoupper($localiteCode),$annee, $rheawebNumType, $rheawebNumValue );
							 
							 $rheawebRegistres = $model->getRheawebList( array("numero"  =>$rheawebNumRccm));
							 $missingRegistres = $model->getListMissings(array("numero"  =>$cleanNumRccm));
							 $rheawebData      = array("numero"=>$rheawebNumRccm,"numkey"=>$rheawebNumKey,"cleanum"=>$cleanNumRccm,
							                           "searchkeys"=>$startword,"documentspath"=>"","annee"=>$annee,"valid"=>1,
													   "localite"  =>$localiteCode,"localiteid"=>$localiteid,"found"=>0,
													   "creationdate"=>time(),"creatorid"=>$me->userid,"updatedate"=>0,"updateduserid"=>0);
							 $missingData      = array("rheanum"=>$rheawebNumRccm,"numkey"=>$rheawebNumKey,"numero"=>$cleanNumRccm,"annee"=>$annee,
							                           "localite"=>$localiteCode,"localiteid"=>$localiteid,"found"=>0,"rhearegistreid"=>0);						   
							 if( count($missingRegistres)) {
								 $rheawebData["found"] = 1;
								 $missingData["found"] = 1;
							 }
							 if( count($rheawebRegistres)) {
								 $rheawebregistreid = $rheawebRegistres[0]["registreid"];
                                 unset($rheawebData["creationdate"]);
                                 unset($rheawebData["creatorid"]   );
                                 $rheawebData["updatedate"]      = time();	
                                 $rheawebData["updateduserid"]   = $me->userid;								 
								 if(!$dbAdapter->update( $tablePrefix. "rheaweb_registre", $rheawebData, array("registreid=".$rheawebregistreid))) {
									 $errorMessages[]            = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour pour RHEAWEB", $cleanNumRccm);
								 }
							 } else {
								 if( $dbAdapter->insert( $tablePrefix. "rheaweb_registre", $rheawebData ) ) {
									 $rheawebregistreid = $dbAdapter->lastInsertId();
								 } else {
									 $errorMessages[]            = sprintf("Les informations du registre numéro %s n'ont pas pu être enregistrées pour RHEAWEB", $cleanNumRccm);
								 }
							 }
							 if( count($missingRegistres)) {
								 $missingData["rhearegistreid"]  = $rheawebregistreid;
								 $missingregistreid              = $missingRegistres[0]["registreid"];
								 $dbAdapter->delete( $tablePrefix. "rheaweb_registres_missings",array("numkey='".$rheawebNumKey."'", "registreid NOT IN(".$missingregistreid.")"));	
								 if(!$dbAdapter->update( $tablePrefix. "rheaweb_registres_missings", $missingData, array("registreid=".$missingregistreid))) {
								    $errorMessages[]             = sprintf("Les informations du registre numéro %s n'ont pas pu être mises à jour", $cleanNumRccm);
								 }
							 } else {                                							 
								 if(!$dbAdapter->insert( $tablePrefix. "rheaweb_registres_missings", $missingData)) {
									 $errorMessages[]            = sprintf("Les informations du registre numéro %s n'ont pas pu être enregistrées", $cleanNumRccm);
								 }
							 }						    
					}
				} else {
					$errorMessages[]   = "Aucun registre de commerce n'a été trouvé dans RHEAWEB pour les paramètres fournis";
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
				    echo ZendX_JQuery::encodeJson(array("success" => "L'opération s'est effectuée avec succès"));
				    exit;
			    }
				$this->setRedirect("L'opération s'est effectuée avec succès", "success");
				$this->redirect("admin/registres/missings/found/1/annee/".$annee."/localiteid/".$localiteid); 
			}			
		}		
		$this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->localites  = $localites;				
	}
	
	public function copymissingsAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		$me                       = Sirah_Fabric::getUser();
		
		$localites                = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","libelle"), array() , 0 , null , false);
		$localiteIDS              = $modelLocalite->getSelectListe("Selectionnez une localité", array("code","localiteid"), array() , 0 , null , false);
		$annees                   = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                  "2009"=>"2009","2010"=>"2010","2011"=> "2011","2012"=>"2012","2013"=> "2013", "2014"=> "2014","2015"=> "2015", "2016"=> "2016", "2017" => "2017");		 							   
		$defaultData              = array("annee"=> 2000,"localite" =>"OUA","destpath"=>"F:\\FNRCCM2017-2018\\DOCUBASE","maxres"=>1000);
		$copied                   = array();
		$errorMessages            = array();
		
		if( $this->_request->isPost() ) {
			$modelTable           = $model->getTable();
		    $prefixName           = $tablePrefix = $modelTable->info("namePrefix");
		    $dbAdapter            = $modelTable->getAdapter();
			$postData             = $this->_request->getPost();
 
			$annee                = (isset($postData["annee"]    ))? $postData["annee"]    : 0;
			$localiteCode         = (isset($postData["localite"] ))? $postData["localite"] : $defaultData["localite"];
			$localiteid           = (isset($localiteIDS[$localiteCode]))?$localiteIDS[$localiteCode]  : 0;
			$maxres               = (isset($postData["maxres"]   ))? intval($postData["maxres"])      : 1000;
			$destPath             = (isset($postData["destpath"] ))? $postData["destpath"]            : "F:\\FNRCCM2017-2018\\DOCUBASE";
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			if(!isset( $localiteIDS[$localiteCode] ) ) {
				$localiteCode     = null;
			}
			if(!isset($annees[$annee] )) {
				$annee            = null;
			}
			if(!is_dir( $destPath ) ) {
				$errorMessages[]  = sprintf("Le dossier de destination %s n'a pas été trouvé", $destPath);
			}
			if( null == $localiteCode ) {
				$errorMessages[]  = "La localité indiquée est invalide";
			}
			if( empty( $errorMessages )) {
			    //On récupère les documents rheaweb qui semblent disponibles
				$rheawebFoundRc   = $model->getListMissings(array("found"=>1,"annee"=>$annee,"localiteid"=>$localiteid));
				//print_r($rheawebFoundRc);die();
				if( count(   $rheawebFoundRc )) {
					foreach( $rheawebFoundRc as $rheawebRc ) {
						     $rheawebNumero = $rheawebRc["rheanum"];
							 $numRccm       = $rheawebRc["numero"];
							 $newRccmDir    = $destPath . DS . $localiteCode . DS . $annee	. DS . 	$numRccm;
							 $numPrefix     = sprintf("BFOUA%sA",$annee);
							 if(is_dir( $newRccmDir) ) {
								 $errorMessages[]  = sprintf("Le dossier %s existe déjà", $newRccmDir);
								 continue;
							 }
							 if((false===stripos($rheawebNumero,sprintf("BFOUA%sA",$annee))) && (false===stripos($rheawebNumero,sprintf("BFOUA%sB",$annee))) 
							 && (false===stripos($rheawebNumero,sprintf("BFOUA%sM",$annee))) && (false===stripos($rheawebNumero,sprintf("BFOUA%sS",$annee))) 
								 ) {
									 $errorMessages[]  = $numPrefix .":".sprintf("Le numéro RCCM %s n'est pas valide", $rheawebNumero);
									 continue;
							 }
							 //print_r($rheawebNumero);die();
							 if( !empty( $rheawebNumero ) ) {
								 $client    = new Zend_Http_Client("http://10.1.25.13/rheaweb/controler", array("keepalive"=> true,"timeout"=> 1000));
								 $client->setConfig( array("strictredirects"   => false));			
								 $client->setHeaders(array("Host"              => "10.1.25.13",
														   "Accept"            => "*/*",
														   "Accept-encoding"   => "gzip, deflate",
														   "Accept-Language"   => "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
														   "Connection"        => "keep-alive",
														   "Content-type"      => "application/x-www-form-urlencoded",
														   "Content-Length"    => "1700",
														   "Origin"            => "http://10.1.25.13",
														   "Referer"           => "http://10.1.25.13/rheaweb/",
														   "Cookie"            => "amfid=592295683; JSESSIONID=9B24752E23B729601AA699942AE2FF65; docubase_password_cookie=11:hamed.banaoBHME@31",
														   "Connection-Length" => "81",
														   "User-Agent"        => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36",
														   "X-Powered-By"      => "Banao Hamed"));
							
								 $client->setParameterGet("Rhea_form_id", "14");
								 $client->setParameterGet("Rhea_14_b0_boolop" , "AND");
								 $client->setParameterGet("Rhea_14_b0_children", "l1;l2;l3;l4;l5;l6;l7;l8;l9;l10;l11;l12;l13;l14;l15;l16;l17;l18;l19;");
								 $client->setParameterGet("Rhea_14_l1_field", "f1");
								 $client->setParameterGet("Rhea_14_l1_op", "EQU");
								 $client->setParameterGet("Rhea_14_l1_value1" ,$rheawebNumero);
								 $client->setParameterGet("cmd"       ,"list");
								 $client->setParameterGet("action"    ,"newquery");
								 $client->setParameterGet("page"      ,"null");
								 $client->setParameterGet("idactivity","null");
								 $jsonData                 = array();
								 $fileLists                = array();
								 try {
									 $rheawebResponse      = $client->request("GET");
									 $rheawebResponseBody  = preg_replace('/\s+/', '',$rheawebResponse->getBody());
									 preg_match_all("/(?<=children:)\s*\[(.*)\]/ms", $rheawebResponseBody, $matches );									  
								 } catch( Exception $e ) {
									 $matches              = array();
									 $rheawebResponseBody  = "";
									 $errorMessages[]      = $e->getMessage();
									 continue;
								 }
                                 //print_r($matches);print_r($rheawebNumero);die();								 
								 if( isset($matches[1][0]) )  {
									$filesInfoString = explode("},{", preg_replace(array("/\[/","/\]/"),array("{","}"),$matches[1][0]));
 
									if( count(   $filesInfoString ) ) {
										foreach( $filesInfoString as $stringKey => $stringVal ) {
												 $stringVal     = ltrim($stringVal,"{");
												 $stringVal     = rtrim($stringVal,"}");
												 $jsonStr       = "{".$stringVal."}";
												 $fileLists[]   = array("jsonVal" => json_decode($jsonStr, true ), "jsonStr"=>$jsonStr);
										}
									}
								 }							 
								 if( count(   $fileLists ) ) {
									 foreach( $fileLists as $rcFileData ) {
										      if( isset($rcFileData["jsonVal"]["title"]) && isset($rcFileData["jsonVal"]["db_docData"]["numdoc"]) ) {
												  $rcFileTitle  = $rcFileData["jsonVal"]["title"];
												  $rcFileKey    = $rcFileData["jsonVal"]["key"];
												  $rcFileNumdoc = $rcFileData["jsonVal"]["db_docData"]["numdoc"];
												  $cookieString = "Cookie:amfid=592295683; JSESSIONID=9B24752E23B729601AA699942AE2FF65; tree-targetlist-expand=; tree-targetlist-select=;tree-targetlist-focus=".$rcFileKey.";tree-targetlist-active=".$rcFileKey."; docubase_password_cookie=11:hamed.banaoBHME@31";
											      
												  $clientBrowse = new Zend_Http_Client("http://10.1.25.13/rheaweb/do/targetlist/service/browsedoc", array("keepalive"=> true,"timeout"=> 1000));
												  $clientBrowse->setConfig(array("strictredirects"=> false));
												  $clientBrowse->setHeaders(array("Host"              => "10.1.25.13",
																				  "Accept"            => "application/json, text/javascript, */*; q=0.01",
																				  "Accept-encoding"   => "gzip, deflate",
																				  "Accept-Language"   => "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
																				  "Connection"        => "keep-alive",
																				  "Content-type"      => "application/x-www-form-urlencoded; charset=UTF-8",
																				  "Content-Length"    => "13",
																				  "Origin"            => "http://10.1.25.13",
																				  "Referer"           => "http://10.1.25.13/rheaweb/controler",
																				  "Cookie"            => $cookieString,
																				  "X-Requested-With"  => "XMLHttpRequest",
																				  "User-Agent"        => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
																				  "X-Powered-By"      => "Banao Hamed"));
												  $clientBrowse->setParameterPost("numdoc", $rcFileNumdoc);
												  try {
													  $browseResponse       = $clientBrowse->request("POST");
												  } catch (Exception $e ) {
													  $errorMessages[]      = $e->getMessage();
													  continue;
												  }	
                                                  //var_dump( $browseResponse->getBody());die();												  
												  if( $browseResponse->getStatus()==200 ) {
													  $browseJsonStr        = $browseResponse->getBody();
													  $browseJsonData       = json_decode($browseJsonStr, true);
													  //print_r($rcFileNumdoc); print_r($browseJsonData);die();
													  if( isset($browseJsonData["result"]["foundDoc"]) ) {
														  if(   $browseJsonData["result"]["foundDoc"] == true ) {
															    $clientView = new Zend_Http_Client("http://10.1.25.13/rheaweb/do/browsedoc/viewDocument", array("keepalive"=> true,"timeout"=> 1000));
																$clientView->setConfig(array("strictredirects"=> false));			
																$clientView->setHeaders(array("Host"              => "10.1.25.13",
																							  "Accept"            => "application/json, text/javascript, */*; q=0.01",
																							  "Accept-encoding"   => "gzip, deflate",
																							  "Accept-Language"   => "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7",
																							  "Connection"        => "keep-alive",
																							  "Content-type"      => "application/x-www-form-urlencoded; charset=UTF-8",
																							  "Content-Length"    => "40",
																							  "Origin"            => "http://10.1.25.13",
																							  "Referer"           => "http://10.1.25.13/rheaweb/viewer_only.jsp?container=browse&property=browse",
																							  "Cookie"            => $cookieString,
																							  "X-Requested-With"  => "XMLHttpRequest",
																							  "User-Agent"        => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
																							  "X-Powered-By"      => "Banao Hamed"));
															
																$clientView->setParameterPost("index", "1");
																$clientView->setParameterPost("container", "browse");
																$clientView->setParameterPost("property" , "browse");
																try {
																	$viewResponse           = $clientView->request("POST");
																    $viewReturnString       = $viewResponse->getBody();
																} catch( Exception $e ) {
																	$errorMessages[]        = $e->getMessage();
																}																
																if( !empty($viewReturnString) ) {
																	  $viewReturnJsonResult = json_decode($viewReturnString, true);
																	  $viewReturnJsonData   = (isset($viewReturnJsonResult["result"]))?$viewReturnJsonResult["result"]:array();
																	  //print_r($viewReturnJsonData);die();
																	  if( isset($viewReturnJsonData["uniqueIdentifier"]) && isset($viewReturnJsonData["filename"])) {
																		  $rcFilename       = str_replace("#","",substr($viewReturnJsonData["filename"], 0, stripos($viewReturnJsonData["filename"], "#")));
																		  $uniqueid         = $viewReturnJsonData["uniqueIdentifier"];
																		  $downloadUri      = "http://10.1.25.13".$rcFilename;
																		  
																		  //print_r($downloadUri);die();											  
																		  $clienDownload    = new Zend_Http_Client($downloadUri, array("keepalive"=> true,"timeout"=> 1000));
																		  $clienDownload->setConfig( array("strictredirects" => false));			
																		  $clienDownload->setHeaders(array("Host"            => "10.1.25.13",
																										   "Accept"          => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
																										   "Accept-encoding" => "gzip, deflate",
																										   "Origin"          => "http://10.1.25.13",
																										   "Referer"         => "http://10.1.25.13/rheaweb/viewer_only.jsp?container=browse&property=browse",
																										   "Cookie"          => $cookieString,
																										   "Upgrade-Insecure-Requests" => 1,
																										   "User-Agent"        => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36",
																										   "X-Powered-By"      => "Banao Hamed"));													
																		  $clienDownload->setStream(); 
																		  $browseResponse    = $clienDownload->request("GET");
																		  $documentType      = "Formulaire";
																		  $documentName      = $numRccm."-FR.pdf";
																		  if( false !==stripos($rcFileTitle, "|PIECES") ) {
																			  $documentName  = $numRccm."-PS.pdf";
																			  $documentType  = "PiecesJointes";
																		  }
																		  $newRccmFilename   = $destPath.DS.$localiteCode.DS. $annee.DS.$numRccm.DS.$documentName;
																		  if( !is_dir($destPath . DS . $localiteCode) ) {
																			  @chmod( $destPath, 0777);
																			  @mkdir( $destPath . DS . $localiteCode);
																		  }
																		  if( !is_dir($destPath . DS . $localiteCode . DS . $annee) ) {
																			  @chmod( $destPath . DS . $localiteCode, 0777);
																			  @mkdir( $destPath . DS . $localiteCode . DS . $annee);
																		  }
																		  if( !is_dir($destPath . DS . $localiteCode . DS . $annee . DS . $numRccm) ) {
																			  @chmod( $destPath . DS . $localiteCode . DS , 0777);
																			  @mkdir( $destPath . DS . $localiteCode . DS . $annee . DS . $numRccm);
																		  }	
																		  if( file_exists($newRccmFilename) && ($documentType=="Formulaire")) {
																			  @unlink($newRccmFilename);
																			  if( true==copy($browseResponse->getStreamName(), $newRccmFilename)) {
																			      $copied[]   = $newRccmFilename;
																		      }
																		  }	elseif( file_exists($newRccmFilename) && ($documentType=="PiecesJointes") ) {
																			  $newRccmTMPFilename    = $destPath.DS.$localiteCode.DS. $annee.DS.$numRccm.DS.$numRccm."TMP-PS.pdf";														  
																			  $filesizeBeforeCombined= filesize($newRccmFilename);
																			  if(true==copy($browseResponse->getStreamName(), $newRccmTMPFilename)) {
																				 try {
																					 $combinedFiles  = array($newRccmTMPFilename, $newRccmFilename );
																					 $combinedFilePDF= new Fpdi\Fpdi();
																					 foreach( $combinedFiles as $combinedFile ) {
																							  if(file_exists(   $combinedFile)) {
																								  $pageCount  = $combinedFilePDF->setSourceFile($combinedFile);
																								  for ( $j = 1;  $j <= $pageCount; $j++) {
																										$combinedTplIdx  = $combinedFilePDF->importPage($j);
																										
																										$combinedPDFSize = $combinedFilePDF->getTemplateSize($combinedTplIdx);
																										$combinedFilePDF->AddPage( $combinedPDFSize['orientation'], $combinedPDFSize);
																										$combinedFilePDF->useTemplate($combinedTplIdx);
																								  }
																							  }
																					 }
																					 $combinedFilePDF->Output("F", $newRccmFilename);
																					 $filesizeAfterCombined  = filesize($newRccmFilename);
																					 if( $filesizeAfterCombined >= $filesizeBeforeCombined ) {
																						 @unlink($newRccmTMPFilename);
																					 }
																				 } catch(Exception $e ) {
																					 $errorMessages[]  = sprintf("Erreur de copie du document %s : %s", $newRccmFilename, $e->getMessage());
																				 }
																			  }																			  									      
																		  } else {
																			  if( true==copy($browseResponse->getStreamName(), $newRccmFilename)) {
																			      $copied[]   = $newRccmFilename;
																		      }
																		  }																			  
																	  }
																  }
														  }
													  }
												  }												  												  
											  }
									 }
								 }
							 }
					}
				} else {
					$errorMessages[] = sprintf("Aucun document manquant pour l'année %d de la localité %s n'a été retrouvé sur RHEAWEB",$annee,$localiteCode);
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
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d documents ont été copiés avec succès", count($copied) )));
				    exit;
			    }
				$this->setRedirect(sprintf("%d documents ont été copiés avec succès", count($copied)), "success");
				$this->redirect("admin/registres/copymissings/annee/".$annee."/localiteid/".$localiteid);  
			}			
		}		
		$this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->localites  = $localites;
		
		$this->render("copymissings");
	}
	
	
	public function rheawebAction()
	{
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");
		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$defaultData           = array("srcpath"=>"F:\\RHEAWEB/SOURCE","check_documents"=>0,"checked_annees"=>array(),"destpath"=>"F:\\RHEAWEB/DEST",
		                               "localites"=> array("OUA","BBD","DDG","KDG"), "storepath"=>"G:\\ERCCM","extract_components"=>0);
		$copied                = array();
		$errorMessages         = array();
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();			
			$srcPath              = (isset($postData["srcpath"]       ))? $postData["srcpath"]        : "G:\\RHEAWEB/SOURCE";
			$destPath             = (isset($postData["destpath"]      ))? $postData["destpath"]       : "G:\\RHEAWEB/DEST";
			$storePath            = (isset($postData["storepath"]     ))? $postData["storepath"]      : "G:\\ERCCM";
			$checkedAnnees        = (isset($postData["checked_annees"]))? $postData["checked_annees"] : $annees;
			$checkedLocalites     = (isset($postData["localites"]     ))? $postData["localites"]      : $defaultData["localites"];
			$extractComponents    = (isset($postData["extract_components"]))?intval($postData["extract_components"]):intval($defaultData["extract_components"]);
			$fullDocumentPages    = array();
			$formDocumentPages    = array();
			$statuteDocumentPages = array();
			$documentTitles       = array();			
			
			if( !is_dir( $srcPath  )) {
				$errorMessages[]  = "La source des documents n'a pas été trouvée";
			}
            if( !is_dir( $destPath )) {
				$errorMessages[]  = "La destination des documents n'a pas été trouvée";
			}
            if( !is_dir( $storePath )) {
				$errorMessages[]  = "Le dossier du ERCCM n'a pas été trouvé";
			}			
			if( count(   $checkedLocalites ) && count($checkedAnnees) && empty($errorMessages)) {
				foreach( $checkedLocalites as $checkedLocalite ) {
					     $localiteSrcPath   = $srcPath  . DS. $checkedLocalite;
						 $localiteDestPath  = $destPath . DS. $checkedLocalite;
						 $localiteStorePath = $storePath. DS. $checkedLocalite;
						 if(!is_dir( $localiteSrcPath) || !is_dir( $localiteStorePath )) {
							 continue;
						 }
						 foreach( $checkedAnnees as $checkedAnnee ) {
							      $anneeSrcPath   = $localiteSrcPath  . DS . $checkedAnnee;
								  $anneeDestPath  = $localiteDestPath . DS . $checkedAnnee;
								  $anneeStorePath = $localiteStorePath. DS . $checkedAnnee;
								  $numRccmKey     = sprintf("BF%s%s", $checkedLocalite, $checkedAnnee);
								  
								  $csvFile        = $anneeSrcPath . DS . $numRccmKey.".csv";
								  $tifSrcPath     = $anneeSrcPath . DS . "DOCUMENTS";
								  $pagesDestPath  = $anneeDestPath;
								 
								  if( !file_exists( $csvFile )) {
									  $errorMessages[] = sprintf("Aucun fichier CSV n'a été trouvé pour l'élement %s", $numRccmKey );
									  continue;
								  }
                                  if( !is_dir($tifSrcPath)) {
									  $errorMessages[] = sprintf("Le dossier source des documents pour l'élement %s n'a pas été trouvé ", $numRccmKey );
									  continue;
								  }								  
								  $csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvFile,"has_header"=>1), "rb");
					              $csvRows        = $csvAdapter->getLines();
								  $archives       = array();
								  $statutArchives = array();
                                  $titlesToArray  = array();								  
								  
								  if( count(   $csvRows )) {
									  foreach( $csvRows as $csvRow ) {
										       $csvNumRccm       = (isset($csvRow["Numero_RCCM"]    ))? preg_replace("/\s+/","",$csvRow["Numero_RCCM"]): "";
											   $csvDocumentName  = (isset($csvRow["Nom_du_document"]))? $csvRow["Nom_du_document"]: "";
											   $csvDocumentTitle = (isset($csvRow["Titre"]          ))? trim($csvRow["Titre"]): "";
											   $titleToArray     = $titlesToArray[] = (!empty($csvDocumentTitle))? explode("|", $csvDocumentTitle):array();
											   if( empty($csvNumRccm) || empty($csvDocumentName)) {
												   continue;
											   }											   
                                               if( false !== ($numRccm = $model->normalizeNum($csvNumRccm, $checkedAnnee, $checkedLocalite ))) {
												   $storeFormLocation  = $anneeStorePath. DS . $numRccm. DS .sprintf("%s-FR.pdf", $numRccm);												    
												   if( file_exists( $storeFormLocation )) {
													   $errorMessages[]= sprintf("Le numéro RCCM %s a déjà été retraité", $csvNumRccm);
													   continue;
												   }
												   if(!isset($archives[$numRccm])) {
													   $isArchive      = (FALSE !== stripos($csvDocumentName, "ARCHIV"));
													   $tifImagePath   = $tifSrcPath  .DS.$csvDocumentName.".TIF";
													   $pagePath       = $anneeSrcPath.DS."PAGES".DS.$numRccm;                                                       												   
													   if( file_exists( $tifImagePath )) {
														   try{															   
													           $isFormFile  = false;
															   $isStatute   = false;
															   if( isset($titleToArray[1] )) {
																   $typeOfTitle     = trim($titleToArray[1]);
																   $documentTitle   = preg_replace("/\s+/", "_", $typeOfTitle);																   
																   if(in_array(preg_replace("/\s+/","",$typeOfTitle),array("M0","M1","M2","M3","M4","P0","P1","P2","P3","P4","P5","S1","S2","S3","S4"))) {
																        $isFormFile = true ;
															            $isStatute  = false;
																   } 
																   if((false!== stripos($documentTitle,"ACTES")) || (false!== stripos($documentTitle,"CONSTITUTIFS"))){
																	   $isFormFile  = false;
															           $isStatute   = true;                                                                       																	   
																   }																   
															   }
															   if(!empty(    $documentTitle )) {
																   if( isset($documentTitles[$documentTitle][$numRccm])) {
																	   continue;
																   } else {
																	         $documentTitles[$documentTitle][$numRccm] = $typeOfTitle;
																   }
															   }
                                                               if(!is_dir($pagePath))	{
																   @chmod($anneeSrcPath.DS."PAGES", 0777 );
																   @mkdir($pagePath);
															   }															   
															   $tiffImages = new Imagick( $tifImagePath );
															   $nbrePages  = $tiffImages->getNumberImages();
															   $pageNum    = (isset($fullDocumentPages[$numRccm]))?(count($fullDocumentPages[$numRccm])+1): 0;															    													   															    
															   foreach( $tiffImages as $i => $jpgImage ){
																        $docPagePath   = $pagePath."/PAGE_".$pageNum.".jpg";
																        if( !file_exists( $docPagePath ) ) {
																			$jpgImage->thumbnailImage(1024,0);
																			if( TRUE != $jpgImage->writeImage($docPagePath)){																			
																			    $docPagePath = null;
																		    }
																		}
                                                                        if( null!==$docPagePath ) {
																			$fullDocumentPages[$numRccm][$pageNum] = $docPagePath;
																			if(($isFormFile==true) || (($i==0) && ($isArchive==true ))) {
																				$formDocumentPages[$numRccm][$i]   = $docPagePath;
																			}
																			if(($isStatute) && !isset($statutArchives[$numRccm]) && (!$isFormFile) && (!isset($archives[$numRccm]))) {
																				$statuteDocumentPages[$numRccm][$i]= $docPagePath;
																			}
																			if( $isArchive == true ) {
																				$archives[$numRccm]                = $docPagePath;
																			}
																			$pageNum++;
																		}																																			                                                                                                                                                       																																					
															   }
															   if( $isStatute) {
																   $statutArchives[$numRccm] = $pagePath."/PAGE_0.jpg";
															   }
															   $tiffImages->destroy();
														   } catch( Exception $e ) {
															   print_r($e->getMessage());die();
															   $errorMessages[]  = sprintf("ERREUR DANS LE DOSSIER DU RCCM N° %s : %s", $numRccm, $e->getMessage());
														   }														  
													   }									    
												   } else {
													   $errorMessages[]  = sprintf("Le numéro RCCM %s est invalide car il existe dans les archives", $csvNumRccm);
												   }
											   } else {
												   $errorMessages[]  = sprintf("Le numéro RCCM %s est invalide", $csvNumRccm);
											   }												   
									  }
								  }
								  /*print_r($statuteDocumentPages); echo " <br/> \n";
								  echo "PS: ".count($fullDocumentPages)." / ST: ".count($statuteDocumentPages);
								  die();*/
								  $formulaires    = array();
								  //On crée les documents de type formulaire
								  if( count(   $formDocumentPages) && ($extractComponents)) {
									  foreach( $formDocumentPages as $numRccm => $formPages ) {
										       $formFilePath = $anneeDestPath. DS . $numRccm. DS .sprintf("%s-FR.pdf", $numRccm);
										       if( count( $formPages ) && !file_exists($formFilePath)) {
												   $formPDF  =  new Sirah_Pdf_Default();
											       $formPDF->SetCreator("ERCCM");
		                                           $formPDF->SetTitle(sprintf("FORMULAIRE DU RCCM N° %s", $numRccm ));
		                                           $formPDF->SetAutoPageBreak(TRUE, 10);
		                                           $formPDF->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		                                           $formPDF->SetMargins(  2 , 2 , 2);
		                                           $formPDF->SetHeaderMargin(5);
		                                           $formPDF->SetFooterMargin(30);
		                                           $formPDF->SetPrintHeader(true);
		                                           $formPDF->SetPrintFooter(false);
		
		                                           $margins     = $formPDF->getMargins();
												   $imageX      = $margins["left"];
												   $imageY      = $margins["top"];
		                                           $imageWidth  = $formPDF->getPageWidth() -$margins["left"]-$margins["right"];
											       $imageHeight = $formPDF->getPageHeight()-$margins["top"] -$margins["bottom"];
		
		                                           foreach( $formPages as $formImage ) {
													        $formPDF->AddPage();
															$formPDF->Image($formImage, $imageX, $imageY,$imageWidth, 0 , '' , '' , 'C' , false);
												   }												   												   
												   if(!is_dir( $anneeDestPath . DS . $numRccm )) {
													   @chmod( $anneeDestPath , 0777 );
													   @mkdir( $anneeDestPath . DS . $numRccm );
												   }
												   if( file_exists($formFilePath)) {
													   @unlink($formFilePath);
												   }
												   $formPDF->Output($formFilePath, "F");
												   if( file_exists( $formFilePath )) {
													   $formulaires[$numRccm]  = $formFilePath;
												   }
											   }										       
									  }
								  }
								  //On créee les statuts
								  $statutes = array();
								  if( count(   $statuteDocumentPages) && $extractComponents) {
									  foreach( $statuteDocumentPages as $numRccm=>$statutePages ) {
										       $statuteFilePath = $anneeDestPath. DS . $numRccm. DS .sprintf("%s-ST.pdf", $numRccm);
											   if( count($statutePages) && !file_exists($statuteFilePath)) {
												   $statutePDF  = new Sirah_Pdf_Default();
												   $statutePDF->SetCreator("ERCCM");
		                                           $statutePDF->SetTitle(sprintf("STATUT DU RCCM N° %s", $numRccm ));
		                                           $statutePDF->SetAutoPageBreak(TRUE, 10);
		                                           $statutePDF->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		                                           $statutePDF->SetMargins(  2 , 2 , 2);
		                                           $statutePDF->SetHeaderMargin(5);
		                                           $statutePDF->SetFooterMargin(30);
		                                           $statutePDF->SetPrintHeader(true);
		                                           $statutePDF->SetPrintFooter(false);
		
		                                           $margins     = $statutePDF->getMargins();
												   $imageX      = $margins["left"];
												   $imageY      = $margins["top"];
		                                           $imageWidth  = $statutePDF->getPageWidth() -$margins["left"]-$margins["right"];
											       $imageHeight = $statutePDF->getPageHeight()-$margins["top"] -$margins["bottom"];
		
		                                           foreach( $statutePages as $statuteImage ) {
													        $statutePDF->AddPage();
															$statutePDF->Image($statuteImage, $imageX, $imageY,$imageWidth, 0 , '' , '' , 'C' , false);
												   }												   												   
												   if(!is_dir( $anneeDestPath. DS. $numRccm )) {
													   @chmod( $anneeDestPath , 0777 );
													   @mkdir( $anneeDestPath. DS. $numRccm );
												   }
												   if( file_exists($statuteFilePath)) {
													   @unlink(    $statuteFilePath);
												   }
												   $statutePDF->Output( $statuteFilePath, "F");
												   if( file_exists(     $statuteFilePath )) {
													   $statutes[$numRccm] = $statuteFilePath;
												   }
											   }
									  }
								  }								  
								  //On créée et on enregistre les fonds de dossiers
								  if( count(   $fullDocumentPages )) {
									  foreach( $fullDocumentPages as $numRccm=>$fullPages ) {
										       $fullFilePath = $anneeDestPath. DS . $numRccm. DS .sprintf("%s-PS.pdf", $numRccm);
											   if( count($fullPages) && !file_exists($fullFilePath)) {
												   $fullPDF  = new Sirah_Pdf_Default();
												   $fullPDF->SetCreator("ERCCM");
		                                           $fullPDF->SetTitle(sprintf("STATUT DU RCCM N° %s", $numRccm ));
		                                           $fullPDF->SetAutoPageBreak(TRUE, 10);
		                                           $fullPDF->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		                                           $fullPDF->SetMargins(  2 , 2 , 2);
		                                           $fullPDF->SetHeaderMargin(5);
		                                           $fullPDF->SetFooterMargin(30);
		                                           $fullPDF->SetPrintHeader(true);
		                                           $fullPDF->SetPrintFooter(false);
												   
												   $isMorale    = (substr($numRccm,0,10)==sprintf("BF%s%sB",$checkedLocalite,$checkedAnnee)) ? 1 : 0;
		
		                                           $margins     = $fullPDF->getMargins();
												   $imageX      = $margins["left"];
												   $imageY      = $margins["top"];
		                                           $imageWidth  = $fullPDF->getPageWidth() -$margins["left"]-$margins["right"];
											       $imageHeight = $fullPDF->getPageHeight()-$margins["top"] -$margins["bottom"];
												   
												   foreach( $fullPages as $fullImage ) {
													        $fullPDF->AddPage();
															$fullPDF->Image($fullImage, $imageX, $imageY,$imageWidth, 0 , '' , '' , 'C' , false);
												   }												   												   
												   if(!is_dir( $anneeDestPath. DS. $numRccm )) {
													   @chmod( $anneeDestPath, 0777 );
													   @mkdir( $anneeDestPath. DS. $numRccm );
												   }
												   if( file_exists($fullFilePath)) {
													   @unlink(    $fullFilePath);
												   }
												   $fullPDF->Output( $fullFilePath, "F");
												   if(!isset($formulaires[$numRccm]) && file_exists($fullFilePath)) {
													   $formFilePath = $anneeDestPath. DS . $numRccm. DS .sprintf("%s-FR.pdf", $numRccm);
													   if( file_exists($formFilePath)) {
													       @unlink(    $formFilePath);
												       }
													   @copy( $fullFilePath, $formFilePath );
												   }
												   if(!isset($statutes[$numRccm]) && file_exists($fullFilePath) && $isMorale ) {
													   $statuteFilePath = $anneeDestPath. DS . $numRccm. DS .sprintf("%s-ST.pdf", $numRccm);
													   if( file_exists($statuteFilePath)) {
													       @unlink(    $statuteFilePath);
												       }
													   @copy( $fullFilePath, $statuteFilePath);
												   }
												   if( file_exists($fullFilePath)) {
													   $copied[$checkedLocalite][$checkedAnnee][$numRccm]  = $fullFilePath;
												   }
											   }
									  }
								  }
						 }
				}
			}
			if(!count( $errorMessages )) {				
				if( count($copied) && count($checkedAnnees) && count($checkedLocalites)) {
					$yearWidth = 90 / count($checkedAnnees);
					$totalCols = count($checkedAnnees) + 1;
					$output    = "<table border='1' width='100%' cellspacing='2' cellpadding='2'>";
					        $output.=" <thead>";
							        $output.="<tr>";
									$output.="   <th width=\"10%\"> LOCALITES </th>";
									foreach( $checkedAnnees as $checkedAnnee ) {
										     $output .= "<th width=\"".$yearWidth."%\"> ".sprintf("Année %d", $checkedAnnee)." </th> ";
									}
									$output.="</tr>";
							$output.=" </thead>";
							$output.=" <tbody>";
							$output.= "<tr><td align=\"center\" colspan=\"".$totalCols."\" style=\"text-align:center;\"><strong>TOTAL DES DOCUMENTS COPIES DEPUIS RHEAWEB </strong></td> </tr>";
							foreach( $checkedLocalites as $checkedLocalite )  {
								     if( isset( $localites[$checkedLocalite])){
										 $localiteLibelle =$localites[$checkedLocalite];
										 $bgColor = "style=\"background-color:".$this->view->cycle(array("#FFFFFF","#F5F5F5"))->next()."\"";
								         $output .= " <tr {$bgColor}>" ;
									          $output.= "<td {$bgColor}> ".$localiteLibelle." </td>";
											  foreach( $checkedAnnees as $checkedAnnee ) {
												       $nbreCopied = (isset($copied[$checkedLocalite][$checkedAnnee]))? count($copied[$checkedLocalite][$checkedAnnee]) : 0;
												       $output    .= "<td {$bgColor}> ".$nbreCopied."</td>";
											  }
									     $output .= " </tr>" ;
									 }								     
							}
							$output.=" </tbody>";
					$output.=" </table>";
					echo $output;
					exit;
				}
			} else {
				$defaultData        = array_merge( $defaultData , $postData );	
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
	    }
        $this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	     $this->view->localites = $localites;		
		$this->render("rheaweb");
	}
	
	public function rheawebcsvAction()
	{
		
		@ini_set('memory_limit', '512M');
		require_once("tcpdf/tcpdf.php");
		require_once("Fpdi/fpdi.php");
		$model                 = $this->getModel("registre");
		$modelLocalite         = $this->getModel("localite");
		$localites             = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité", array("code", "libelle"), array() , 0 , null , false);
		$annees                = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                               "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$defaultData           = array("srcpath"=>"F:\\RHEAWEB/SOURCE","check_documents"=>0,"checked_annees"=>array(),"destpath"=>"F:\\RHEAWEB/DEST",
		                               "localites"=> array("OUA","BBD","DDG","KDG"), "storepath"=>"G:\\ERCCM","extract_components"=>0);
		$copied                = array();
		$errorMessages         = array();
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();			
			$srcPath              = (isset($postData["srcpath"]       ))? $postData["srcpath"]        : "G:\\RHEAWEB/SOURCE";
			$destPath             = (isset($postData["destpath"]      ))? $postData["destpath"]       : "G:\\RHEAWEB/DEST";
			$storePath            = (isset($postData["storepath"]     ))? $postData["storepath"]      : "G:\\ERCCM";
			$checkedAnnees        = (isset($postData["checked_annees"]))? $postData["checked_annees"] : $annees;
			$checkedLocalites     = (isset($postData["localites"]     ))? $postData["localites"]      : $defaultData["localites"];
			$extractComponents    = (isset($postData["extract_components"]))?intval($postData["extract_components"]):intval($defaultData["extract_components"]);
			$fullDocumentPages    = array();
			$formDocumentPages    = array();
			$statuteDocumentPages = array();
			$documentTitles       = array();			
			
			if( !is_dir( $srcPath  )) {
				$errorMessages[]  = "La source des documents n'a pas été trouvée";
			}
            if( !is_dir( $destPath )) {
				$errorMessages[]  = "La destination des documents n'a pas été trouvée";
			}
            if( !is_dir( $storePath )) {
				$errorMessages[]  = "Le dossier du ERCCM n'a pas été trouvé";
			}			
			if( count(   $checkedLocalites ) && count($checkedAnnees) && empty($errorMessages)) {
				foreach( $checkedLocalites as $checkedLocalite ) {
					     $localiteSrcPath   = $srcPath  . DS. $checkedLocalite;
						 $localiteDestPath  = $destPath . DS. $checkedLocalite;
						 $localiteStorePath = $storePath. DS. $checkedLocalite;
						 if(!is_dir( $localiteSrcPath) || !is_dir( $localiteStorePath )) {
							 continue;
						 }
						 foreach( $checkedAnnees as $checkedAnnee ) {
							      $anneeSrcPath   = $localiteSrcPath  . DS . $checkedAnnee;
								  $anneeDestPath  = $localiteDestPath . DS . $checkedAnnee;
								  $anneeStorePath = $localiteStorePath. DS . $checkedAnnee;
								  $numRccmKey     = sprintf("BF%s%s", $checkedLocalite, $checkedAnnee);
								  
								  $csvFile        = $anneeSrcPath . DS . $numRccmKey.".csv";
								  $tifSrcPath     = $anneeSrcPath . DS . "DOCUMENTS";
								  $pagesDestPath  = $anneeDestPath;
								 
								  if( !file_exists( $csvFile )) {
									  $errorMessages[] = sprintf("Aucun fichier CSV n'a été trouvé pour l'élement %s", $numRccmKey );
									  continue;
								  }
                                  if( !is_dir($tifSrcPath)) {
									  $errorMessages[] = sprintf("Le dossier source des documents pour l'élement %s n'a pas été trouvé ", $numRccmKey );
									  continue;
								  }								  
								  $csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvFile,"has_header"=>1), "rb");
					              $csvRows        = $csvAdapter->getLines();
								  $archives       = array();
								  $statutArchives = array();
                                  $titlesToArray  = array();								  
								  
								  if( count(   $csvRows )) {
									  foreach( $csvRows as $csvRow ) {
										       $csvNumRccm       = (isset($csvRow["Numero_RCCM"]    ))? preg_replace("/\s+/","",$csvRow["Numero_RCCM"]): "";
											   $csvDocumentName  = (isset($csvRow["Nom_du_document"]))? $csvRow["Nom_du_document"]: "";
											   $csvDocumentTitle = (isset($csvRow["Titre"]          ))? trim($csvRow["Titre"]): "";
											   $titleToArray     = $titlesToArray[] = (!empty($csvDocumentTitle))? explode("|", $csvDocumentTitle):array();
											   if( empty($csvNumRccm) || empty($csvDocumentName)) {
												   continue;
											   }											   
                                               if( false !== ($numRccm = $model->normalizeNum($csvNumRccm, $checkedAnnee, $checkedLocalite ))) {
												   $storeFormLocation  = $anneeStorePath. DS . $numRccm. DS .sprintf("%s-FR.pdf", $numRccm);												    
												   if( file_exists( $storeFormLocation )) {
													   $errorMessages[]= sprintf("Le numéro RCCM %s a déjà été retraité", $csvNumRccm);
													   continue;
												   }
												   if(!isset($archives[$numRccm])) {
													   $isArchive      = (FALSE !== stripos($csvDocumentName, "ARCHIV"));
													   $tifImagePath   = $tifSrcPath  .DS.$csvDocumentName.".TIF";
													   $pagePath       = $anneeSrcPath.DS."PAGES".DS.$numRccm;                                                       												   
													   if( file_exists( $tifImagePath )) {
														   try{															   
													           $isFormFile  = false;
															   $isStatute   = false;
															   if( isset($titleToArray[1] )) {
																   $typeOfTitle     = trim($titleToArray[1]);
																   $documentTitle   = preg_replace("/\s+/", "_", $typeOfTitle);																   
																   if(in_array(preg_replace("/\s+/","",$typeOfTitle),array("M0","M1","M2","M3","M4","P0","P1","P2","P3","P4","P5","S1","S2","S3","S4"))) {
																        $isFormFile = true ;
															            $isStatute  = false;
																   } 
																   if((false!== stripos($documentTitle,"ACTES")) || (false!== stripos($documentTitle,"CONSTITUTIFS"))){
																	   $isFormFile  = false;
															           $isStatute   = true;                                                                       																	   
																   }																   
															   }
															   if(!empty(    $documentTitle )) {
																   if( isset($documentTitles[$documentTitle][$numRccm])) {
																	   continue;
																   } else {
																	         $documentTitles[$documentTitle][$numRccm] = $typeOfTitle;
																   }
															   }
                                                               if(!is_dir($pagePath))	{
																   @chmod($anneeSrcPath.DS."PAGES", 0777 );
																   @mkdir($pagePath);
															   }															   
															   $tiffImages = new Imagick( $tifImagePath );
															   $nbrePages  = $tiffImages->getNumberImages();
															   $pageNum    = (isset($fullDocumentPages[$numRccm]))?(count($fullDocumentPages[$numRccm])+1): 0;															    													   															    
															   foreach( $tiffImages as $i => $jpgImage ){
																        $docPagePath   = $pagePath."/PAGE_".$pageNum.".jpg";
																        if( !file_exists( $docPagePath ) ) {
																			$jpgImage->thumbnailImage(1024,0);
																			if( TRUE != $jpgImage->writeImage($docPagePath)){																			
																			    $docPagePath = null;
																		    }
																		}
                                                                        if( null!==$docPagePath ) {
																			$fullDocumentPages[$numRccm][$pageNum] = $docPagePath;
																			if(($isFormFile==true) || (($i==0) && ($isArchive==true ))) {
																				$formDocumentPages[$numRccm][$i]   = $docPagePath;
																			}
																			if(($isStatute) && !isset($statutArchives[$numRccm]) && (!$isFormFile) && (!isset($archives[$numRccm]))) {
																				$statuteDocumentPages[$numRccm][$i]= $docPagePath;
																			}
																			if( $isArchive == true ) {
																				$archives[$numRccm]                = $docPagePath;
																			}
																			$pageNum++;
																		}																																			                                                                                                                                                       																																					
															   }
															   if( $isStatute) {
																   $statutArchives[$numRccm] = $pagePath."/PAGE_0.jpg";
															   }
															   $tiffImages->destroy();
														   } catch( Exception $e ) {
															   print_r($e->getMessage());die();
															   $errorMessages[]  = sprintf("ERREUR DANS LE DOSSIER DU RCCM N° %s : %s", $numRccm, $e->getMessage());
														   }														  
													   }									    
												   } else {
													   $errorMessages[]  = sprintf("Le numéro RCCM %s est invalide car il existe dans les archives", $csvNumRccm);
												   }
											   } else {
												   $errorMessages[]  = sprintf("Le numéro RCCM %s est invalide", $csvNumRccm);
											   }												   
									  }
								  }
						 }
				}
			}
			if(!count( $errorMessages )) {				
				if( count($copied) && count($checkedAnnees) && count($checkedLocalites)) {
					$yearWidth = 90 / count($checkedAnnees);
					$totalCols = count($checkedAnnees) + 1;
					$output    = "<table border='1' width='100%' cellspacing='2' cellpadding='2'>";
					        $output.=" <thead>";
							        $output.="<tr>";
									$output.="   <th width=\"10%\"> LOCALITES </th>";
									foreach( $checkedAnnees as $checkedAnnee ) {
										     $output .= "<th width=\"".$yearWidth."%\"> ".sprintf("Année %d", $checkedAnnee)." </th> ";
									}
									$output.="</tr>";
							$output.=" </thead>";
							$output.=" <tbody>";
							$output.= "<tr><td align=\"center\" colspan=\"".$totalCols."\" style=\"text-align:center;\"><strong>TOTAL DES DOCUMENTS COPIES DEPUIS RHEAWEB </strong></td> </tr>";
							foreach( $checkedLocalites as $checkedLocalite )  {
								     if( isset( $localites[$checkedLocalite])){
										 $localiteLibelle =$localites[$checkedLocalite];
										 $bgColor = "style=\"background-color:".$this->view->cycle(array("#FFFFFF","#F5F5F5"))->next()."\"";
								         $output .= " <tr {$bgColor}>" ;
									          $output.= "<td {$bgColor}> ".$localiteLibelle." </td>";
											  foreach( $checkedAnnees as $checkedAnnee ) {
												       $nbreCopied = (isset($copied[$checkedLocalite][$checkedAnnee]))? count($copied[$checkedLocalite][$checkedAnnee]) : 0;
												       $output    .= "<td {$bgColor}> ".$nbreCopied."</td>";
											  }
									     $output .= " </tr>" ;
									 }								     
							}
							$output.=" </tbody>";
					$output.=" </table>";
					echo $output;
					exit;
				}
			} else {
				$defaultData        = array_merge( $defaultData , $postData );	
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
	    }
        $this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	     $this->view->localites = $localites;		
		$this->render("rheaweb");
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
	
		
	public function importsiguecsvAction()
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
		$months                     = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData            = array("srcpath"=>"F:\\FNRCCM2017-2018\\ORIGINAL","destpath"=>"F:\\FNRCCM2017-2018\\SIGUE",
		                                    "opsusername"=>"","opspath"=>"F:\\FNRCCM2017-2018\\OPS","annee"=>2016,"sigueuser"=>"compte.OPS",
											"sigueuri"=>"http://10.60.16.17:8014/Piece/ShowFile/","overwrite"=>"COMBINE","siguepwd"=>"P@ssw0rd",
											"sigueuauth_type"=>CURLAUTH_NTLM);
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$imported                   = array();
		$rccms                      = array();
		$rccmDocuments              = array();
		$rccmNumeros                = array();
		$rccmDates                  = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {			
			$postData               = $this->_request->getPost();
			$srcPath                = (isset($postData["srcpath"]    ))? $postData["srcpath"]               : "F:\\FNRCCM2017-2018\\ORIGINAL";
			$destPath               = (isset($postData["destpath"]   ))? $postData["destpath"]              : "F:\\FNRCCM2017-2018\\SIGUE";
			$opsPath                = (isset($postData["opspath"]    ))? $postData["opspath"]               : "F:\\FNRCCM2017-2018\\OPS";
			$opsUsername            = (isset($postData["opsusername"]))? $postData["opsusername"]           : "";
		    $checkedYear            = (isset($postData["annee"]      ))? intval($postData["annee"])         : 2017;
			$checkedMonth           = (isset($postData["mois"]       ))? sprintf("%02d",$postData["mois"])  : 1;
			$sigueUri               = (isset($postData["sigueuri"]   ))? $postData["sigueuri"]              : "http://10.60.16.17:8014/Piece/ShowFile/";
			$sigueAuthType          = (isset($postData["sigueuauth_type"] ))? $postData["sigueuauth_type"]  : CURLAUTH_NTLM;
			$sigueUsername          = (isset($postData["sigueuser"]  ))? $postData["sigueuser"]             : "compte.OPS";
			$siguePassword          = (isset($postData["siguepwd"]   ))? $postData["siguepwd"]              : "P@ssw0rd";
			$overwriteOption        = (isset($postData["overwrite"]  ))? strtoupper($postData["overwrite"]) : "COMBINE";
			$cleanOpsUsername       = preg_replace("/\s+/","_", $opsUsername);
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
			
			if(!is_dir( $srcPath  )) {
				$errorMessages[]    = "La source des documents n'a pas été trouvée";
			}
            if(!is_dir( $destPath )) {
				$errorMessages[]    = "La destination des documents n'a pas été trouvée";
			}
			if( empty( $siguePassword ) ) {
				$siguePassword      = "P@ssw0rd";
			}
			if(!empty($opsUsername) && is_dir( $opsPath ) && intval($checkedYear) && intval( $checkedMonth ) && isset($months[$checkedMonth])) {
                $monthname          = $months[$checkedMonth]; 				
				$opsWorkFolder      = $opsPath . DS . $cleanOpsUsername. DS . $checkedYear . DS . $monthname;
				if( is_dir( $opsWorkFolder ) ) {
					$opsDaysFolders = glob($opsWorkFolder."/*", GLOB_ONLYDIR) ;
					if( count(   $opsDaysFolders ) ) {
						foreach( $opsDaysFolders  as $opsDayFolder ) {
							     $opsRccmFolders   = glob($opsDayFolder."/*", GLOB_ONLYDIR) ;
								 if( count(   $opsRccmFolders ) ) {
									 foreach( $opsRccmFolders as $opsRccmFolder ) {
										      $opsRccmFolderName = Sirah_Filesystem::mb_basename($opsRccmFolder);
											  
											  if((strtoupper(substr($opsRccmFolderName,0, 9)) != strtoupper(sprintf("BFOUA%d", $checkedYear ))) || (strlen($opsRccmFolderName)<14) ) {
											      $searchNomCommercial = str_replace(' ', '', $opsRccmFolderName);
												  $foundRccm           = $model->findsiguercmms(array("nomcommercial"=>$searchNomCommercial,"annee"=>$checkedYear));
												  if( isset( $foundRccm[0]["cleanum"] )) {
													  $opsDayFolderNumRccm = $foundRccm[0]["cleanum"];
													  $newRccmFolderName   = $opsDayFolder . DS . $opsDayFolderNumRccm;
													  if(true===rename($opsRccmFolder, $newRccmFolderName )) {
														 $opsRccmFiles     = glob($newRccmFolderName."/*.pdf") ;
														 if( count(   $opsRccmFiles ) ) {
															 foreach( $opsRccmFiles as $opsRccmFile ) {
																	  $newFilename =  null;
																	  if( false!==stripos($opsRccmFile, "-FR.pdf") ) {
																		  $newFilename = $opsDayFolder . DS . $opsDayFolderNumRccm. DS . $opsDayFolderNumRccm."-FR.pdf";
																	  } elseif( false!==stripos($opsRccmFile, "-PS.pdf")) {
																		  $newFilename = $opsDayFolder . DS . $opsDayFolderNumRccm. DS . $opsDayFolderNumRccm."-PS.pdf";
																	  } elseif( false!==stripos($opsRccmFile, "-ST.pdf")) {
																		  $newFilename = $opsDayFolder . DS . $opsDayFolderNumRccm. DS . $opsDayFolderNumRccm."-ST.pdf";
																	  }
																	  if( null != $newFilename ) {
																		  rename( $opsRccmFile, $newFilename );
																	  }
															 }
														 }
													  }
												  }										
										 }
									 }
								 }							     
						}
					} else {
						$errorMessages[]   = sprintf("Le dossier de %s est vide", $opsUsername );
					}
				} else {
					    $errorMessages[]   = sprintf("Le dossier %s de %s n'est pas valide", $opsWorkFolder , $opsUsername);
				}
			}
			 
            $csvDestinationName     = $destPath. DS . time() . sprintf("sigueFile%02d%04d.csv",$checkedMonth,$checkedYear );			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive("registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvAdapter    = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName, "has_header" =>1), "rb");
					$csvLines      = $csvAdapter->getLines();
					$csvItems      = 1;
					
					$csvRows = array_filter($csvLines,function($csvRow) use ($checkedYear,$checkedMonth){
						if(!Zend_Date::isDate($csvRow["DateDemande"],"YYYY-MM-dd H:i") && 
						   !Zend_Date::isDate($csvRow["DateDemande"],"dd/mm/YYYY H:i") && 
						   !Zend_Date::isDate($dateDemande, Zend_Date::ISO_8601)) {
						   return false;
						}
						if((false===stripos($csvRow["DateDemande"],sprintf("%04d-%02d",$checkedYear,$checkedMonth ))) &&
						   (false===stripos($csvRow["DateDemande"],sprintf("%02d/%04d",$checkedYear,$checkedMonth )))
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
								 $nomCommercial = $stringFilter->filter($csvRow["NomCommercial"]);
								 
								 if((strlen($numeroRCCM) < 14) || (strlen($numeroRCCM) > 16)) {
									$errorMessages[] = sprintf("Le numéro RCCM %s de SIGUE est invalide", $numeroRCCM );
									continue;
								 }
								  if( Zend_Date::isDate($dateDemande, Zend_Date::ISO_8601) ) {
									 $zendDate  = new Zend_Date($dateDemande, Zend_Date::ISO_8601);
						         }elseif( Zend_Date::isDate($dateDemande,"YYYY-MM-dd H:i:s") ) {
									 $zendDate  = new Zend_Date($dateDemande,"YYYY-MM-dd H:i");
								 } else {
									 $zendDate  = new Zend_Date($dateDemande,"dd/mm/YYYY H:i");
								 }
								 $cleanNumRccm  = $model->normalizeNum($numeroRCCM,$checkedYear,"OUA");
								 $numKey        = trim(substr($cleanNumRccm, 10));
								 $numLocalite   = trim(substr($cleanNumRccm, 2, 3));
								 $localiteid    = (isset($localiteIDS[$numLocalite]))?$localiteIDS[$numLocalite]   : $localiteIDS["OUA"] ;
								 
								 $rccmYear      = $annee = ( $zendDate ) ? $zendDate->get(Zend_Date::YEAR)         : 0;
								 $rccmMois      = $month = ( $zendDate ) ? strtoupper($zendDate->toString("MMMM")) : "";
								 $rccmDay       = $jour  = ( $zendDate ) ? $zendDate->toString("ddMMYYYY")         : "";
								 $rccmDate      =          ( $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP)    : 0;
                                 $rccmMonthValue= ( $zendDate ) ? $zendDate->toString("MM") : "";
								 $rccmDayValue  = ( $zendDate ) ? $zendDate->toString("dd") : "";
                                 $documentName  = $cleanNumRccm."-PS.pdf";								 
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
                                 $documentsPath        = $destPath . DS . $rccmYear . DS . $rccmMois .DS . $jour . DS . $cleanNumRccm;
								 $documentFilename     = $documentsPath . DS . $documentName;
                                 if( file_exists( $documentFilename ) && ($overwriteOption=="SKIP")) {
									 continue;
								 }									 
								 $rccmDates[$rccmYear][$rccmMonthValue][$rccmDayValue][$cleanNumRccm] = $nomCommercial;
								 $sigueRccm     = $model->findsiguerc($numeroRCCM);
								 $sigueRccmData = array("numero"=>$numeroRCCM,"numkey"=>$numKey,"cleanum"=>$cleanNumRccm,"localiteid"=>$localiteid,"valid"=>1,"found"=>0,"date"=>$rccmDate,
                                                        "localite"=> $numLocalite,"nomcommercial"=>$nomCommercial,"annee"=>$rccmYear,"datedemande"=>$dateDemande);
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
								 $rccmNumeros[$cleanNumRccm]         = $nomCommercial;
								 $rccmDocuments                      = $model->findsiguercpairdocs($numeroRCCM, $idPIECE );
								 if(!count( $rccmDocuments ) ) {
                                     $rccmDocument                   = "F:\\FNRCCM2017-2018\\TMP\\".$cleanNumRccm.".pdf";
									 $sigueUri                       = preg_replace ("/^ /", "", $sigueUri);
                                     $sigueUri                       = preg_replace ("/ $/", "", $sigueUri);
                                     $url                            = trim($sigueUri,"/")."/".$idPIECE;									 
									 $ch                             = curl_init();
		                             curl_setopt($ch, CURLOPT_URL, $url);

									//Set the post parameters
									 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
									 curl_setopt($ch, CURLOPT_POST, FALSE);
									 curl_setopt($ch, CURLOPT_HTTPGET , TRUE);
									 curl_setopt($ch, CURLOPT_HEADER  , true);
									 curl_setopt($ch, CURLOPT_NOBODY  , false);
									 curl_setopt($ch, CURLOPT_TIMEOUT , 10);
									 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
									 curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
									 curl_setopt($ch, CURLOPT_USERPWD , $sigueUsername.':'.$siguePassword);

									 $sigueDocumentFile = curl_exec($ch);
									 
									 file_put_contents($rccmDocument, $sigueDocumentFile);
 
                                     if( file_exists( $rccmDocument ) )	 {										 
										 $documentFileSize               = filesize($rccmDocument);
										
										 if(!is_dir( $destPath . DS . $rccmYear ) ) {
											 @chmod( $destPath , 0777);
											 @mkdir( $destPath . DS . $rccmYear);
										 }
										 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois ) ) {
											 @chmod( $destPath . DS . $rccmYear, 0777);
											 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois);
										 }
										 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour ) ) {
											 @chmod( $destPath . DS . $rccmYear. DS . $rccmMois, 0777);
											 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour);
										 }
										 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour. DS . $cleanNumRccm ) ) {
											 @chmod( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour, 0777);
											 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour. DS . $cleanNumRccm);
										 }
										 
										 $doctype                        = "Formulaire";
										 if( false !== stripos($numeroPIECE, "P0/CEFORE")) {
											 $documentName               = $cleanNumRccm."-FR.pdf";
											 $doctype                    = "Formulaire";
										 } elseif( false !== stripos($numeroPIECE, "AEC/CEFORE")) {
											 $documentName               = $cleanNumRccm."-PS.pdf";
											 $doctype                    = "Accusé de réception";
										 }										 
										 $documentData                   = array();
										 $documentData["userid"]         = $me->userid;
										 $documentData["category"]       = 1;
										 $documentData["resource"]       = "registres";
										 $documentData["resourceid"]     = 0;
										 $documentData["filedescription"]= $cleanNumRccm;
										 $documentData["filemetadata"]   = sprintf("%s;%s;%s", $idPIECE, $numeroPIECE, $cleanNumRccm);
										 $documentData["creationdate"]   = time();
										 $documentData["creatoruserid"]  = $me->userid;									 
										 $documentData["filename"]       = $modelDocument->rename($numeroPIECE.$idPIECE, $me->userid );
										 $documentData["filepath"]       = $documentFilename ;
										 $documentData["access"]         = 0;
										 $documentData["filextension"]   = "pdf";
										 $documentData["filesize"]       = floatval( $documentFileSize );
										 $documentTransfered             = false;
										 
										 if( file_exists( $documentFilename) && ($overwriteOption=="ERASE")) {
											 @unlink($documentFilename);
											 $documentTransfered         = copy( $rccmDocument, $documentFilename );
										 } elseif( file_exists( $documentFilename) && ($overwriteOption=="COMBINE")) {
											 $combinedFiles              = array($rccmDocument,$documentFilename);
											 $combinedFilePDF            = new Fpdi\Fpdi();
											 foreach( $combinedFiles as $combinedFile ) {
													  if( file_exists($combinedFile)) {
														  $pageCount = $combinedFilePDF->setSourceFile($combinedFile);
														  for ( $j = 1;  $j <= $pageCount; $j++) {
																$combinedTplIdx  = $combinedFilePDF->importPage($j);
																
																$combinedPDFSize = $combinedFilePDF->getTemplateSize($combinedTplIdx);
																$combinedFilePDF->AddPage( $combinedPDFSize['orientation'], $combinedPDFSize);
																$combinedFilePDF->useTemplate($combinedTplIdx);
														  }
													  }
											 }
											 $combinedFilePDF->Output("F", $documentFilename );
											 $documentTransfered     = true;
										 } else {
											 $documentTransfered         = copy( $rccmDocument, $documentFilename );
										 }											 
										 if( true === $documentTransfered ) {
											 $dbAdapter->delete( $prefixName . "sigue_registre_documents"  , array("numrccm='".$cleanNumRccm."'"));
											 $dbAdapter->delete( $prefixName . "system_users_documents"    ,       "documentid IN (SELECT documentid FROM ".$prefixName."sigue_registre_documents WHERE numrccm='".$cleanNumRccm."')");
											
											 if($dbAdapter->insert( $prefixName . "system_users_documents" , $documentData) ) {
												$documentid                = $dbAdapter->lastInsertId();
												$sigueDocumentData         = array("documentid"=>$documentid ,"registreid"=>$registreid,"numrccm"=>$cleanNumRccm,"documentkey"=>$idPIECE,"numdoctype"=>$numeroPIECE,"doctype"=>$doctype,"documentspath"=>$documentFilename);
											    if( $dbAdapter->insert( $prefixName . "sigue_registre_documents", $sigueDocumentData) ) {							
													$imported[$documentid] = $idPIECE;
													if( is_dir( $opsPath ) && !empty( $opsUsername )) {														
														$opsRccmFolder     = $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour . DS . $cleanNumRccm;
													    $fullDocument      = $opsRccmFolder. DS . $cleanNumRccm."-PS.pdf"; 
														$statutDocument    = $opsRccmFolder. DS . $cleanNumRccm."-ST.pdf"; 
														$opsFilename       = $opsRccmFolder. DS . $documentName;
														$rccmAllFiles      = glob( $opsRccmFolder."/*.pdf");
														if( file_exists( $opsFilename ) && ($overwriteOption=="SKIP") ) {
															continue;
														}
														//On corrige les fichiers qui existent déjà dans le dossier du RCCM
														if( count(   $rccmAllFiles ) ) {
															foreach( $rccmAllFiles  as $thisRccmFilename ) {
																     $rccmFileNum         = str_ireplace(".pdf", "", trim(basename($thisRccmFilename)));
                                                                     																	 
																	 if( false!==stripos($rccmFileNum, "-FR") && ( $rccmFileNum != $cleanNumRccm."-FR")) {
																		 $filePathInfos   = pathinfo($thisRccmFilename);
																		 $fileDirname     = (isset($filePathInfos["dirname"] )) ? $filePathInfos["dirname"] : "";
																		 $renamedFile     = $fileDirname. DS .$cleanNumRccm."-FR.pdf";
																		 if( is_dir( $fileDirname ) ) {
																			 @rename($thisRccmFilename, $renamedFile );
																		 }															 
																	 }
																	 if(false!==stripos($rccmFileNum, "-PS") && ( $rccmFileNum != $cleanNumRccm."-PS")) {
																		 $filePathInfos   = pathinfo($thisRccmFilename);
																		 $fileDirname     = (isset($filePathInfos["dirname"] )) ? $filePathInfos["dirname"] : "";
																		 $renamedFile     = $fileDirname. DS .$cleanNumRccm."-PS.pdf";
																		 if( is_dir( $fileDirname ) ) {
																			 @rename($thisRccmFilename, $renamedFile );
																		 }															 
																	 }
																	 if(false!==stripos($rccmFileNum, "-ST") && ( $rccmFileNum != $cleanNumRccm."-ST")) {
																		 $filePathInfos   = pathinfo($thisRccmFilename);
																		 $fileDirname     = (isset($filePathInfos["dirname"] )) ? $filePathInfos["dirname"] : "";
																		 $renamedFile     = $fileDirname. DS .$cleanNumRccm."-ST.pdf";
																		 if( is_dir( $fileDirname ) ) {
																			 @rename($thisRccmFilename, $renamedFile );
																		 }															 
																	 }
															}
														}						
														if(!is_dir( $opsPath  . DS . $cleanOpsUsername ) ) {
															@chmod( $opsPath, 0777);
															@mkdir( $opsPath  . DS . $cleanOpsUsername );
														}
														if(!is_dir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear ) ) {
															@chmod( $opsPath  . DS . $cleanOpsUsername, 0777);
															@mkdir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear );
														}
														if(!is_dir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear. DS . $rccmMois ) ) {
															@chmod( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear, 0777);
															@mkdir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear. DS . $rccmMois );
														}
														if(!is_dir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour ) ) {
															@chmod( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois, 0777);
															@mkdir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour );
														}
														if(!is_dir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour. DS . $cleanNumRccm ) ) {
															@chmod( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour, 0777);
															@mkdir( $opsPath  . DS . $cleanOpsUsername . DS . $rccmYear . DS . $rccmMois . DS . $jour. DS . $cleanNumRccm );
														}
														if( is_dir($opsRccmFolder ) && !file_exists($opsFilename) ) {												
															if(  TRUE === copy( $rccmDocument , $opsFilename) ) {
																 $rccmDocuments          = (file_exists($fullDocument))?array($opsFilename, $fullDocument):array($opsFilename);																 
																 try {
																	 $fullPDF            = new Fpdi\Fpdi();
																	 foreach( $rccmDocuments as $rccmFilename ) {
																			  if( file_exists($rccmFilename)) {
																				  $pageCount = $fullPDF->setSourceFile($rccmFilename);
																				  for ( $j = 1;  $j <= $pageCount; $j++) {
																						$tplIdx      = $templateId = $fullPDF->importPage($j);
																						
																						$fullPDFSize = $fullPDF->getTemplateSize($templateId);
																						$fullPDF->AddPage( $fullPDFSize['orientation'], $fullPDFSize);
																						$fullPDF->useTemplate($templateId);
																				  }
																			  }
																	 }
																	 //On fouille dans le dossier du registre de commerce pour supprimer tous les autres PDF
																	 $rccmDocuments = (!in_array( $fullDocument, $rccmDocuments))?array($opsFilename, $fullDocument) :  $rccmDocuments;
																	 if( !in_array($statutDocument, $rccmDocuments)) {
																		 $rccmDocuments[] = $statutDocument;
																	 }
																	 if( count(   $rccmAllFiles ) ) {
																		 foreach( $rccmAllFiles  as $thisRccmFilename ) {
																				  if(!in_array( $thisRccmFilename, $rccmDocuments) ) {
																					  @unlink($thisRccmFilename);
																				  }
																		 }
																	 }
																	 @unlink($fullDocument);
																	 $fullPDF->Output("F",$fullDocument);
																	 curl_close( $ch );
																	 @unlink($rccmDocument);
																 } catch(Exception $e ) {
																	 $errorMessages[]= sprintf("Une erreur s'est produite dans le traitement du document %s RCCM N° %s : %s", $opsFilename, $cleanNumRccm, $e->getMessage());
																 }																 
															} else {
																$errorMessages[]   =  sprintf("Le document de l'OPS %s n'a pas pu être copié dans le dossier %s", $opsFilename, $fullDocument);
															}																
														}
													}
												}
											 }
										 }
									 }										 									 									 
								 }								 
						}
					}
					//$rccmDates[$rccmYear][$rccmMonthValue][$rccmDayValue][$cleanNumRccm] = $nomCommercial;															
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
				    echo ZendX_JQuery::encodeJson(array("success" => sprintf("%d dossiers ont été importés depuis SIGUE avec succès", count($imported) )));
				    exit;
			    }
				$this->setRedirect( sprintf("%d dossiers ont été importés depuis SIGUE avec succès", count($imported) ), "success");
				$this->redirect("admin/registres/importsiguefiles"); 
			}			
		}
        $this->view->data       = $defaultData;
		$this->view->annees     = $annees;
	    $this->view->months     = $months;
        $this->render("importsigue");		
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
		$annees                     = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                                    "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$months                     = array(1=>"Janvier",2=>"Fevrier",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Aout",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Decembre");
		$defaultInitData            = array("srcpath"  =>"F:\\FNRCCM2017-2018\\FINAL\\SIGUE\\ORIGINAL","destpath"=>"F:\\FNRCCM2017-2018\\FINAL\\SIGUE\\A_RETRAITER","annee"=>2016,
		                                    "checkpath"=>"F:\\FNRCCM2017-2018\\FINAL\\FNRCCM2017-2018","overwrite"=>"COMBINE","sigueuser"=>"compte.OPS",
									        "sigueuri"=>"http://10.60.16.17:8014/Piece/ShowFile/","siguepwd"=>"P@ssw0rd","sigueuauth_type"=>CURLAUTH_NTLM);
		
		$defaultData                = array_merge( $defaultInitData, $getParams);
		$combined                   = array();
		$errorMessages              = array();
		
		if( $this->_request->isPost() ) {
			set_time_limit(12000);
			$postData               = $this->_request->getPost();
			$srcPath                = (isset($postData["srcpath"]         ))? $postData["srcpath"]               : "C:\\SIGUE\\ORIGINAL";
			$destPath               = (isset($postData["destpath"]        ))? $postData["destpath"]              : "C:\\SIGUE\\A_RETRAITER";
		    $checkPath              = (isset($postData["checkpath"]       ))? $postData["checkpath"]             : "C:\\FNRCCM2017-2018";
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
			if(!empty( $localiteCode) && isset($localites[$localiteCode]) ) {
				$destPath           = $destPath . DS . $localiteCode ;
				$srcPath            = $srcPath  . DS . $localiteCode ;
				$checkPath          = $checkPath. DS . $localiteCode ;
			}
			if(!is_dir( $checkPath )) {
				$errorMessages[]    = "Le chemin de vérification n'est pas valide";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée un validateur de filtre
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			
			$documentsUploadAdapter = new Zend_File_Transfer();
			$documentsUploadAdapter->addValidator('Count'    , false , 1);
			$documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
			
			$basicFilename          = $documentsUploadAdapter->getFileName("registres", false );
			
			$me                     = Sirah_Fabric::getUser();
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $userTable->info("namePrefix");
			$copied                 = array();
						 			
            $csvDestinationName     = $destPath. DS . time() . "mySigueData.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("NumeroRCCM","DateDemande","IdPiece","IdTypePiece","NumeroPiece","NomCommercial","NomPromoteur");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvLines       = $csvAdapter->getLines();
					$csvItems       = 1;
					if( isset($csvLines[0]) ) {
					    unset($csvLines[0]);
					}
					$csvRows        = array_filter($csvLines,function($csvRow) use ($checkedYear,$checkedMonth){
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
					@uasort($csvRows,function($a, $b){if($a['DateDemande']==$b['DateDemande']){return 0;} return ($a['DateDemande'] < $b['DateDemande']) ? -1 : 1; });
				    if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $dateDemande          = (isset($csvRow["DateDemande"]  ))?$csvRow["DateDemande"]                        : "";
								 $numeroRCCM           = (isset($csvRow["NumeroRCCM"]   ))?$stringFilter->filter($csvRow["NumeroRCCM"] ) : "";
								 $numeroPIECE          = (isset($csvRow["NumeroPiece"]  ))?$stringFilter->filter($csvRow["NumeroPiece"]) : "";
								 $idTypePIECE          = (isset($csvRow["IdTypePiece"]  ))?$stringFilter->filter($csvRow["IdTypePiece"]) : "";
								 $idPIECE              = (isset($csvRow["IdPiece"]      ))?$stringFilter->filter($csvRow["IdPiece"])     : "";
								 $nomCommercial        = (isset($csvRow["NomCommercial"]))?$stringFilter->filter($csvRow["NomCommercial"]):"";
								 $rccmDocumentPathRoot = trim(trim($srcPath,"\\"),  DS  );
								 $zendDate             = null;
								 if( empty( $numeroPIECE ) ) {
									 $numeroPIECE      = $idTypePIECE;
								 }								 
								 if(strlen($numeroRCCM) < 10 ) {
									$errorMessages[]   = sprintf("Le numéro RCCM %s de SIGUE est invalide", $numeroRCCM );
									continue;
								 }
								 if(Zend_Date::isDate($dateDemande,"dd/mm/YYYY H:i")) {
									  $zendDate        = new Zend_Date($dateDemande, Zend_Date::DATES ,"fr_FR");								 
						         } elseif( Zend_Date::isDate($dateDemande,"YYYY-MM-dd H:i:s") ) {
									  $zendDate        = new Zend_Date($dateDemande,"YYYY-MM-dd H:i");
								 } elseif( Zend_Date::isDate($dateDemande, Zend_Date::ISO_8601) ) {
									  $zendDate        = new Zend_Date($dateDemande, Zend_Date::ISO_8601);
						         } else {
									  $zendDate        = null;
								 }
								 if( null== $zendDate ) {
									$errorMessages[]   = sprintf("La date du RCCM N° %s de SIGUE est invalide", $numeroRCCM,  $dateDemande);
									continue;
								 }
								 $numLocalite          = trim(substr($numeroRCCM, 2, 3));
								 $numYear              = trim(substr($numeroRCCM, 5, 4));
								 $cleanNumRccm         = $model->normalizeNum($numeroRCCM, $numYear, $numLocalite);
								 $numKey               = trim(substr($cleanNumRccm, 10));								 
								 $localiteid           = (isset($localiteIDS[$numLocalite]))?$localiteIDS[$numLocalite]   : $localiteIDS["OUA"] ;
								 
								 $rccmYear             = $annee = ($zendDate) ? $zendDate->get(Zend_Date::YEAR)         : 0;
								 $rccmMois             = $month = ($zendDate) ? strtoupper($zendDate->toString("MMMM")) : "";
								 $rccmDay              = $jour  = ($zendDate) ? $zendDate->toString("ddMMYYYY")         : "";
								 $rccmDate             =          ($zendDate) ? $zendDate->get(Zend_Date::TIMESTAMP)    : 0;
                                 $rccmMonthValue       =          ($zendDate) ? $zendDate->toString("MM")               : "";
								 $rccmDayValue         =          ($zendDate) ? $zendDate->toString("dd")               : "";
                                 $documentName         = $cleanNumRccm."-FR.pdf";
                                 $rccmTmpDocument      = $rccmDocumentPathRoot  . DS . $rccmYear. DS . $rccmMois. DS . $jour . DS . $numeroRCCM.".pdf";
                                 $psDocumentFilename   = $destPath . DS . $rccmYear. DS . $rccmMois .DS . $jour . DS . $cleanNumRccm. DS . $cleanNumRccm ."-PS.pdf";								 
								 
								 if(!intval($rccmYear) || !$zendDate || !intval($rccmMonthValue) || !intval($rccmDayValue)) {
									 $errorMessages[]  = sprintf("La date du numéro RCCM %s est invlide : %s", $numeroRCCM, $dateDemande );
									 continue;
								 }
                                 if( is_dir( $checkPath )) {
									 $checkFilePS      = $checkPath . DS . $rccmYear . DS . $cleanNumRccm . DS . $cleanNumRccm."-PS.pdf";
									 if( file_exists( $checkFilePS )) {
										 continue;
									 }
								 }									 
                                 $documentsPath        = $destPath . DS . DS . $rccmYear . DS . $rccmMois .DS . $jour . DS . $cleanNumRccm;
								 $documentFilename     = $documentsPath . DS . $documentName;
                                 if( file_exists($rccmTmpDocument) && ($overwriteOption=="SKIP")) {
									 continue;
								 }
                                 $doctype              = "Formulaire";
								 if( (false!== stripos($numeroPIECE, "P2")) || (false!== stripos($numeroPIECE, "P1")) || (false!== stripos($numeroPIECE, "Formulaire")) || (false!== stripos($numeroPIECE, "M1")) || (false!== stripos($numeroPIECE, "M0"))){
									 $documentName     = $cleanNumRccm."-FR.pdf";
									 $documentFilename = $documentsPath . DS . $documentName;
									 $doctype          = "Formulaire";
									 $overwriteOption  = "ERASE";
								 } elseif( false!== stripos($numeroPIECE, "STAT") ) {
									 $documentName     = $cleanNumRccm."-ST.pdf";
									 $documentFilename = $documentsPath . DS . $documentName;
									 $doctype          = "STATUT";
									 $overwriteOption  = "ERASE";
						         } else {
									 $documentName     = $cleanNumRccm."-PS.pdf";
									 $documentFilename = $documentsPath . DS . $documentName;
									 $doctype          = "Fond de dossier";
									 $overwriteOption  = "COMBINE";
								 }								 								 
                                 $rccmDocumentPath     = $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois. DS . $jour ;								 
								 $rccmDocument         = $rccmDocumentPath     . DS . $cleanNumRccm.".pdf";
								 
								 if(!is_dir( $rccmDocumentPath) ) {
									 if(!is_dir( $rccmDocumentPathRoot . DS . $rccmYear ) ) {
										 @chmod( $rccmDocumentPathRoot , 0777);
										 @mkdir( $rccmDocumentPathRoot . DS . $rccmYear);
									 }
									 if(!is_dir( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois ) ) {
										 @chmod( $rccmDocumentPathRoot . DS . $rccmYear, 0777);
										 @mkdir( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois);
									 }
									 if(!is_dir( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois. DS . $jour ) ) {
										 @chmod( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois, 0777);
										 @mkdir( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois. DS . $jour);
										 @chmod( $rccmDocumentPathRoot . DS . $rccmYear. DS . $rccmMois. DS . $jour, 0777);
									 }
								 }
								 $sigueUri             = preg_replace ("/^ /", "", $sigueUri);
								 $sigueUri             = preg_replace ("/ $/", "", $sigueUri);
								 $url                  = trim(trim($sigueUri,"\\"), DS)."/".$idPIECE;									 
								 $ch                   = curl_init();
								 curl_setopt($ch, CURLOPT_URL, $url);

								//Set the post parameters
								 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
								 curl_setopt($ch, CURLOPT_POST, FALSE);
								 curl_setopt($ch, CURLOPT_HTTPGET , TRUE);
								 curl_setopt($ch, CURLOPT_HEADER  , true);
								 curl_setopt($ch, CURLOPT_NOBODY  , false);
								 curl_setopt($ch, CURLOPT_TIMEOUT , 10);
								 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
								 curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
								 curl_setopt($ch, CURLOPT_USERPWD , $sigueUsername.':'.$siguePassword);

								 $sigueDocumentFile = curl_exec($ch);
								 
								 file_put_contents($rccmTmpDocument, $sigueDocumentFile);

								 if( file_exists($rccmTmpDocument ) && filesize($rccmTmpDocument)) {
									 if(!is_dir( $destPath . DS . $rccmYear ) ) {
										 @chmod( $destPath , 0777);
										 @mkdir( $destPath . DS . $rccmYear);
									 }
									 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois ) ) {
										 @chmod( $destPath . DS . $rccmYear, 0777);
										 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois);
									 }
									 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour ) ) {
										 @chmod( $destPath . DS . $rccmYear. DS . $rccmMois, 0777);
										 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour);
									 }
									 if(!is_dir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour. DS . $cleanNumRccm ) ) {
										 @chmod( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour, 0777);
										 @mkdir( $destPath . DS . $rccmYear. DS . $rccmMois. DS . $jour. DS . $cleanNumRccm);
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
										 $documentTransfered         = copy( $rccmTmpDocument, $psDocumentFilename);
									 }
									 if( true === $documentTransfered ) {
										 @unlink( $rccmTmpDocument);
										 $copied[]               = $psDocumentFilename;
									 }
								 }
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
			$userTable              = $me->getTable();
			$dbAdapter              = $userTable->getAdapter();
			$prefixName             = $tablePrefix = $userTable->info("namePrefix");
			$imported               = array();
						 			
            $csvDestinationName     = APPLICATION_DATA_PATH . DS .  "tmp" . DS . time()."rccmImport.csv";			
			$documentsUploadAdapter->addFilter("Rename", array("target"=> $csvDestinationName, "overwrite"=>true) , "registres");
			if( $documentsUploadAdapter->isUploaded("registres") && empty( $errorMessages )) {
				$documentsUploadAdapter->receive(   "registres");
				if( $documentsUploadAdapter->isReceived("registres") ) {
					$csvHeader      = array("Quartier","Section","Lot","Parcelle","NumeroRCCM","NomDemandeur","AdressePostale","DateDemande","DateNaissance","LieuNaissance","Pays","NomCommercial","ActivitePrincipale","Telephone","AdressePhysique");
					$csvAdapter     = Sirah_Filesystem_File::fabric("Csv",array("filename"=>$csvDestinationName,"has_header"=> true,"header"=>$csvHeader), "rb");
					$csvRows        = $csvAdapter->getLines();
					$csvItems       = 1;
					
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $registreData       = array();
							     $DateDemande        = (isset($csvRow["DateDemande"]        ))?$csvRow["DateDemande"]                         : "";
								 $DateNaissance      = (isset($csvRow["DateNaissance"]      ))?$csvRow["DateNaissance"]                       : "";
								 $LieuNaissance      = (isset($csvRow["LieuNaissance"]      ))?$stringFilter->filter($csvRow["LieuNaissance"]): "";
								 $NumeroRCCM         = (isset($csvRow["NumeroRCCM"]         ))?$stringFilter->filter($csvRow["NumeroRCCM"] )  : "";
								 $Quartier           = (isset($csvRow["Quartier"]           ))?$stringFilter->filter($csvRow["Quartier"])     : "";
								 $Section            = (isset($csvRow["Section"]            ))?$stringFilter->filter($csvRow["Section"])      : "";
								 $Parcelle           = (isset($csvRow["Parcelle"]           ))?$stringFilter->filter($csvRow["Parcelle"])     : "";
								 $Lot                = (isset($csvRow["Lot"]                ))?$stringFilter->filter($csvRow["Lot"])          : "";
								 $NomCommercial      = (isset($csvRow["NomCommercial"]      ))?$stringFilter->filter($csvRow["NomCommercial"]):"";
								 $NomDemandeur       = (isset($csvRow["NomDemandeur"]       ))?$stringFilter->filter($csvRow["NomDemandeur"]) :"";
								 $ActivitePrincipale = (isset($csvRow["ActivitePrincipale"] ))?$stringFilter->filter($csvRow["ActivitePrincipale"]) :"";
								 $Pays               = (isset($csvRow["Pays"]               ))?$stringFilter->filter($csvRow["Pays"])           :"";
								 $AdressePostale     = (isset($csvRow["AdressePostale"]     ))?$stringFilter->filter($csvRow["AdressePostale"]) :"";
								 $AdressePhysique    = (isset($csvRow["AdressePhysique"]    ))?$stringFilter->filter($csvRow["AdressePhysique"]):"";
								 $Telephone          = (isset($csvRow["Telephone"]          ))?$stringFilter->filter($csvRow["Telephone"])      :"";
								 
								 if(!$strNotEmptyValidator->isValid($NomDemandeur) || !$strNotEmptyValidator->isValid($DateDemande) || !$strNotEmptyValidator->isValid($NomCommercial) || !$strNotEmptyValidator->isValid($DateNaissance)) {
								     continue;
								 }
                                 if( strlen($NumeroRCCM) > 14 ) {
									 continue;
								 }
								 $NumeroRCCM                                = $model->normalizeNum($NumeroRCCM,$annee, $localite );
								 $registreData["numero"]                    = $NumeroRCCM;
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
									 $registreData["description"]           = ($strNotEmptyValidator->isValid($ActivitePrincipale))? $ActivitePrincipale : $contentRegistre->description;
									 $registreData["telephone"]             = ($strNotEmptyValidator->isValid($Telephone         ))? $Telephone          : $contentRegistre->telephone;
									 $registreData["adresse"]               = ($strNotEmptyValidator->isValid($Adresse           ))? $Adresse            : $contentRegistre->adresse;
									 $registreData["sexe"]                  =  $contentRegistre->sexe;
									 $registreData["passport"]              =  $contentRegistre->passport;
									 $registreData["situation_matrimonial"] =  $contentRegistre->situation_matrimonial;
									 $registreData["nationalite"]           = ($strNotEmptyValidator->isValid($registreData["nationalite"]))? $registreData["nationalite"] : $contentRegistre->nationalite;
								 } else {
									 $registreData["nom_commercial"]        = $NomCommercial;
									 $registreData["date_enregistrement"]   = $DateDemande  ;
									 $registreData["date_naissance"]        = $DateNaissance;
									 $registreData["lieu_naissance"]        = $LieuNaissance;
									 $registreData["description"]           = $ActivitePrincipale;
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
				$this->redirect("admin/registres/importdocubasecsv/annee/".$nextYear);
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
		                                  "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		$defaultInitData          = array("srcpath"=>"C:\\ERCCM\\DATA","destpath"=>"C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS","localite"=> "OUA","annee"=>2000,"nbre_documents"=>1000,
		                                  "sigar_dbhost" => "", "");
		
		
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
			$srcPath              = (isset($postData["srcpath"]  ))? $postData["srcpath"]      : "F:\\FNRCCM2017-2018/DOCSCAN/COMBINE";
			$destPath             = (isset($postData["destpath"] ))? $postData["destpath"]     : "F:\\FNRCCM2017-2018/DOCSCAN/DEST";
			$checkPath            = (isset($postData["checkpath"]))? $postData["checkpath"]    : "G:\\ERCCM";
			$annee                = (isset($postData["annee"]    ))? intval($postData["annee"]): 2000;
			$localite             = (isset($postData["localite"] ))? $postData["localite"] : "OUA";
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
						 $checkIndexationFiles                   = glob("C:\\Users\User\\Desktop\\FNRCCM-2017-2018\\INDEXATIONS/*/".$localite."/".$annee."/".$numRccm.".pdf" );
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

    public function checkusersdataAction()
    {
		@ini_set('memory_limit', '512M');
		$this->view->title  = "Vérifier les RCCM retraités des OPS";
		$modelLocalite      = $this->getModel("localite");
		$modelDocument      = $this->getModel("document");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelEntreprise    = $this->getModel("entreprise");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("annee"=>2016,"localite"=>"OUA","saveclean"=>0,"cleanpath"=>"G:\\ERCCM","srcpath"=> "F:\\FNRCCM2017-2018\\OPS","invalidpath"=>"INCOHERENCES","errorspath"=>"ERREURS","ops_username"=>"");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
		$opsName            = "";
	
		$validItems         = $invalidItems = $errorItems = $dataItems = array();
	
	    $opsUsernames       = array(0=> "Sélectionnez un utilisateur","ouedraogo_alida"=>"OUEDRAOGO ALIDA","sogbo_aurele"=>"SOGBO AURELE","traore_korotimi"=>"TRAORE KOROTIMI","dayamba_raissa"=>"DAYAMBA RAISSA","sangare_alimata"=>"SANGARE ALIMATA","traore_sandrine"=>"TRAORE SANDRINE");
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		if( $this->_request->isPost( )) {
			$postData             = $this->_request->getPost();
			$srcPath              = ( isset( $postData["srcpath"]        )) ? $postData["srcpath"]        : $defaultData["srcpath"];
			$invalidPath          = ( isset( $postData["invalidpath"]    )) ? $postData["invalidpath"]    : $defaultData["invalidpath"];
			$errorsPath           = ( isset( $postData["errorspath"]     )) ? $postData["errorspath"]     : $defaultData["errorspath"];
			$localite             = ( isset( $postData["localite"]       )) ? $postData["localite"]       : $defaultData["localite"];
			$annee                = ( isset( $postData["annee"]          )) ? intval($postData["annee"])  : $defaultData["annee"];
			$opsUsername          = ( isset( $postData["ops_username"]   )) ? $postData["ops_username"]   : $defaultData["ops_username"];
			$opsName              = ( isset( $opsUsernames[$opsUsername] )) ? $opsUsernames[$opsUsername] : "";
			$numRccmKey           = "BF";
           		
			
			if( empty( $opsUsername ) || !isset( $opsUsernames[$opsUsername] ) ) {
				$errorMessages[]  = "Veuillez sélectionner un utilisateur valide";
			} else {
				$opsUsername      = strtoupper($opsUsername);
				$errorsPath       = $srcPath . DS . $opsUsername . DS . "ERREURS";	
				$srcPath          = $srcPath . DS . $opsUsername . DS . "ERCCM"  ;				
			}
            if(!isset(  $localites[$localite] ) ) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			} else {
				$srcPath          = $srcPath    . DS . $localite;
				$errorsPath       = $errorsPath . DS . $localite;
				$numRccmKey       = $numRccmKey . strtoupper($localite);
			}	
            if(!isset( $annees[$annee] )) {
				$errorMessages[]  = "Veuillez sélectionner une année valide";
			} else {
				$srcPath          = $srcPath    . DS . $annee;
				$errorsPath       = $errorsPath . DS . $annee;
				$numRccmKey       = $numRccmKey . intval($annee);
			}				
			if(!is_dir( $srcPath ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  n'existe pas. Veuillez vérifier.",  $opsName );
			} else {
				$dataItems        = glob( $srcPath. DS ."*", GLOB_ONLYDIR);
			}
			if(!count( $dataItems ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  pour l'année %d ne contient aucun document. Veuillez vérifier.",  $opsName, $annee );
			}
			//print_r($errorsPath);die();
			if( empty(   $errorMessages ) ) {				
				foreach( $dataItems     as $rccmDirectory ) {
					     $dirRccmNum     = Sirah_Filesystem::mb_basename($rccmDirectory);
						 $formulaireFile = $rccmDirectory . DS . $dirRccmNum."-FR.pdf";
						 $statuteFile    = $rccmDirectory . DS . $dirRccmNum."-ST.pdf";
						 $completeFile   = $rccmDirectory . DS . $dirRccmNum."-PS.pdf";
						 $directoryFiles = glob( $rccmDirectory."/*.pdf");						
						 
						 if( strlen( $dirRccmNum ) != 14 ) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : il doit être renommé par un numéro RC portant 14 lettres", $dirRccmNum, $annee );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {	                                						 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }
						 if((false !== stripos(sprintf("BF%s%dA", $localite,$annee), $dirRccmNum ) ) && (count($directoryFiles) > 2)) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : il ne doit pas y avoir plus de 2 documents", $dirRccmNum, $annee  );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 } elseif( count($directoryFiles)> 3 ) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : il ne doit pas y avoir plus de 3 documents", $dirRccmNum, $annee  );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }						  						 						 
						 if(!file_exists($formulaireFile )) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : le formulaire du RCCM n'existe pas .....%s", $dirRccmNum, $annee, $formulaireFile );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }
						 if(!file_exists($completeFile )) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : le fond de dossier du RCCM n'existe pas", $dirRccmNum , $annee, $completeFile );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }
						 if(!file_exists($statuteFile) && (false!==stripos(sprintf("BF%s%dB", $localite,$annee), $dirRccmNum ))) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : le statut du RCCM n'existe pas", $dirRccmNum, $annee, $statuteFile );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }
						 try{
							 $pdfRegistre     = new Fpdi\Fpdi();
							 $pagesFormulaire = (file_exists($formulaireFile)) ? $pdfRegistre->setSourceFile($formulaireFile ) : 0;
							 $pagesStatut     = (file_exists($statuteFile   )) ? $pdfRegistre->setSourceFile($statuteFile    ) : 0;
							 $pagesComplet    = (file_exists($completeFile  )) ? $pdfRegistre->setSourceFile($completeFile   ) : 0;
						 }catch( Exception $e ) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : les documents du dossier ne sont pas valides", $dirRccmNum, $annee  );
						     $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						 }
						 if( $pagesFormulaire && ($pagesComplet <= $pagesFormulaire )) {
							 $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : le formulaire du dossier n'est pas valide", $dirRccmNum, $annee  );
							 $errorItems[$dirRccmNum]    = $message;
							 if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							 }							 
							 continue;
						  }
						  if( $pagesStatut && ( $pagesComplet <= $pagesStatut )) {
							  $errorMessages[] = $message = sprintf("Le dossier numéro %s de l'année %d est invalide : le statut du dossier n'est pas valide", $dirRccmNum, $annee  );
							  $errorItems[$dirRccmNum]    = $message;
							  if( is_dir( $errorsPath ) ) {								 
								 $this->__moveDir( $rccmDirectory, $dirRccmNum, $errorsPath );
							  }							 
							  continue;
						  }
						  
						  $validItems[] = $dirRccmNum.".pdf";
				}
			}
            
			$validHtml           =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
			          $validHtml.="    <tr><td width=\"100%\" style=\"font-size:13pt;text-align:center;\"  align=\"center\"><b> ". sprintf("L'utilisateur %s a retraité au total %d valides pour l'année %d", $opsName, count($validItems) , $annee) ." </b></td></tr>";
            $validHtml          .=" </table>";			
            if( count( $errorMessages )) {
				$errorHtml       =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
                      $errorHtml.="<tr><td width=\"100%\" style=\"font-size:13pt; text-align:center;background-color:#E5E5E5\" align=\"center\"><b> ".sprintf('HISTORIQUE DES ERREURS PRODUITES PAR %s : AU TOTAL %d ERREURS ', $opsName, count( $errorMessages ))." </b></td></tr>";
                $errorHtml      .=" </table>";
				$errorHtml      .=" <ul>";
				foreach( $errorMessages as $errorMessage ) {
					     $errorHtml      .=" <li> ".$errorMessage."</li>";
				}
				$errorHtml      .=" </ul>";
			}
            $me                  = Sirah_Fabric::getUser();
            $PDF                 = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
            $PDF->SetCreator(sprintf("%s", $opsUsername));
            $PDF->SetTitle(  sprintf("Validation des retraitements effectués par %s", $opsName));
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
		    echo $PDF->Output(sprintf("Validation%s.pdf", preg_replace("/\s/","-", $opsName)),"D");
		    exit;			
		}		
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
		$this->view->users       = $opsUsernames;
		
		$this->render("checkdata");
	}
		
	public function cleanusersdataAction()
    {
		@ini_set('memory_limit', '512M');
		$this->view->title  = "Vérifier les RCCM retraités des OPS";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("annee"=>2016,"localite"=>"OUA","srcpath"=> "F:\\FNRCCM2017-2018\\OPS", "destpath"=>"F:\\FNRCCM2017-2018\\OPS","ops_username"=>"");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
		$opsName            = "";
	
		$validItems         = $invalidItems = $errorItems = $dataItems = array();
	
	    $opsUsernames       = array(0=> "Sélectionnez un utilisateur","ouedraogo_alida"=>"OUEDRAOGO ALIDA","sogbo_aurele"=>"SOGBO AURELE","traore_korotimi"=>"TRAORE KOROTIMI","dayamba_raissa"=>"DAYAMBA RAISSA","sangare_alimata"=>"SANGARE ALIMATA","traore_sandrine"=>"TRAORE SANDRINE");
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		if( $this->_request->isPost( )) {
			$postData             = $this->_request->getPost();
			$srcPath              = ( isset( $postData["srcpath"]        )) ? $postData["srcpath"]        : $defaultData["srcpath"];
			$destPath             = ( isset( $postData["destpath"]       )) ? $postData["destpath"]       : $defaultData["destpath"];
			$localite             = ( isset( $postData["localite"]       )) ? $postData["localite"]       : $defaultData["localite"];
			$annee                = ( isset( $postData["annee"]          )) ? intval($postData["annee"])  : $defaultData["annee"];
			$opsUsername          = ( isset( $postData["ops_username"]   )) ? $postData["ops_username"]   : $defaultData["ops_username"];
			$opsName              = ( isset( $opsUsernames[$opsUsername] )) ? $opsUsernames[$opsUsername] : "";
			$numRccmKey           = "BF";
           		
			if( is_dir($destPath ) ){
				@chmod($destPath, 0777);
			}
			if( empty( $opsUsername ) || !isset( $opsUsernames[$opsUsername] ) ) {
				$errorMessages[]  = "Veuillez sélectionner un utilisateur valide";
			} else {
				$opsUsername      = strtoupper($opsUsername);	
				$srcPath          = $srcPath . DS . $opsUsername . DS . "ERREURS";
                $destPath         = $destPath. DS . $opsUsername . DS . "CORRECTIONS";
                if(!is_dir( $destPath ) ){
					@mkdir( $destPath );
					@chmod( $destPath, 0777);
				}					
			}
            if(!isset(  $localites[$localite] ) ) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			} else {
				$srcPath          = $srcPath    . DS . $localite;
				$destPath         = $destPath   . DS . $localite;
				$numRccmKey       = $numRccmKey . strtoupper($localite);
				if(!is_dir( $destPath ) ){
					@mkdir( $destPath );
					@chmod( $destPath, 0777);
				}
			}	
            if(!isset( $annees[$annee] )) {
				$errorMessages[]  = "Veuillez sélectionner une année valide";
			} else {
				$srcPath          = $srcPath    . DS . $annee;
				$destPath         = $destPath   . DS . $annee;
				$numRccmKey       = $numRccmKey . intval($annee);
				if(!is_dir( $destPath ) ){
					@mkdir( $destPath );
					@chmod( $destPath, 0777);
				}
			}				
			if(!is_dir( $srcPath ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  n'existe pas. Veuillez vérifier.",  $opsName );
			} else {
				$dataItems        = glob( $srcPath. DS ."*", GLOB_ONLYDIR);
			}
			if(!is_dir( $destPath ) ) {
				$errorMessages[]  = sprintf( "Le dossier de correction de l'utilisateur %s  n'existe pas. Veuillez vérifier.",  $opsName );
			}
			if(!count( $dataItems ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  pour l'année %d ne contient aucun document. Veuillez vérifier.",  $opsName, $annee );
			}
			//print_r($errorsPath);die();
			if( empty(   $errorMessages ) ) {				
				foreach( $dataItems     as $rccmDirectory ) {
					     $dirRccmNum     = Sirah_Filesystem::mb_basename($rccmDirectory);
						 $formulaireFile = $rccmDirectory . DS . $dirRccmNum."-FR.pdf";
						 $statuteFile    = $rccmDirectory . DS . $dirRccmNum."-ST.pdf";
						 $completeFile   = $rccmDirectory . DS . $dirRccmNum."-PS.pdf";
						 $directoryFiles = glob( $rccmDirectory."/*.pdf");
						 $newDirRccmNum  = null;
                         
                         if((false !== stripos($dirRccmNum, "BBF")) || (false !== stripos($dirRccmNum, "BFAOUA")) || (false !== stripos($dirRccmNum, "BAFOUA")) || (false !== stripos($dirRccmNum, "BFFOUA"))) {
						    $newDirRccmNum  = str_replace(array("BBF","BFAOUA","BAFOUA","BFFOUA","BOUA"), array("BF","BFOUA","BFOUA","BFOUA","BFOUA"), trim(preg_replace("/\s+/", "", $dirRccmNum)) );
						 }							 						 
						 if( strlen( $dirRccmNum ) != 14 ) {
							$newDirRccmNum = (!empty( $newDirRccmNum )) ? $newDirRccmNum  : trim(preg_replace("/\s+/", "", $dirRccmNum));							 							  
						 }
						 //On essaie de renommer
						 if(!empty( $newDirRccmNum ) ) {
							 $rccmLocalite      = trim(substr( $newDirRccmNum, 2, 3));
							 $rccmAnnee         = trim(substr( $newDirRccmNum, 5, 4));
							 $rccmTypeCode      = trim(substr( $newDirRccmNum, 9, 1));
							 $rccmId            = trim(substr( $newDirRccmNum, 10));							 
							 $newDirRccmNum     = vsprintf("BF%s%04d%s%04d",array($rccmLocalite, $rccmAnnee, $rccmTypeCode, $rccmId ));
							 
							 $newRccmDirectory  = $destPath        . DS . $newDirRccmNum;
							 $newFormulaireFile = $newRccmDirectory. DS . $newDirRccmNum. "-FR.pdf";
							 $newCompleteFile   = $newRccmDirectory. DS . $newDirRccmNum. "-PS.pdf";
							 $newStatuteFile    = $newRccmDirectory. DS . $newDirRccmNum. "-ST.pdf";
							 
							 if(!is_dir($newRccmDirectory) ) {
								 @chmod($destPath, 0777);
								 @mkdir($newRccmDirectory);
							 }
							 if( file_exists( $newFormulaireFile )) {
								 unlink($newFormulaireFile);
							 }
							 if( file_exists( $newCompleteFile )) {
								 unlink($newCompleteFile);
							 }
							 if( file_exists( $newStatuteFile ) ) {
								 unlink($newStatuteFile);
							 }							 							 
							 //On déplace les fichiers 
							 //On commence par le formulaire
							 $formulaireFiles = glob( $rccmDirectory."/*-FR.pdf");
							 if( isset( $formulaireFiles[0] ) ) {
								 if( true == copy( $formulaireFiles[0], $newFormulaireFile ) ) {
									 unlink($formulaireFiles[0]);
								 }
							 }
							 $completeFiles = glob( $rccmDirectory."/*-PS.pdf");
							 if( isset( $completeFiles[0] ) ) {
								 if( true == copy( $completeFiles[0], $newCompleteFile) ) {
									 unlink($completeFiles[0]);
								 }
							 }
							 $statuteFiles = glob( $rccmDirectory."/*-ST.pdf");
							 if( isset( $statuteFiles[0] ) ) {
								 if( true == copy( $statuteFiles[0], $newStatuteFile) ) {
									 unlink($statuteFiles[0]);
								 }
							 }
							 //S'il existe d'autres fichiers dans ce dossier on les supprime
							 $directoryFiles = glob( $rccmDirectory."/*.pdf");
							 if( count(   $directoryFiles ) ) {
								 foreach( $directoryFiles as $directoryFile ) {
									      unlink($directoryFile);
								 }
							 }
							 rmdir($rccmDirectory);
							 $validItems[] = $newDirRccmNum.".pdf";
						 }  					  						  						  
				}
			}
            
			$validHtml           =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
			          $validHtml.="    <tr><td width=\"100%\" style=\"font-size:13pt;text-align:center;\"  align=\"center\"><b> ". sprintf("%d erreurs de %s pour l'année %d ont été corrigés avec succès", count($validItems) , $opsName,  $annee ) ." </b></td></tr>";
            $validHtml          .=" </table>";			
            if( count( $errorMessages )) {
				$errorHtml       =" <table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" border=\"1\" align=\"center\" style=\"text-align:center;\" >";
                      $errorHtml.="<tr><td width=\"100%\" style=\"font-size:13pt; text-align:center;background-color:#E5E5E5\" align=\"center\"><b> ".sprintf('HISTORIQUE DES ERREURS PRODUITES PAR %s : AU TOTAL %d ERREURS ', $opsName, count( $errorMessages ))." </b></td></tr>";
                $errorHtml      .=" </table>";
				$errorHtml      .=" <ul>";
				foreach( $errorMessages as $errorMessage ) {
					     $errorHtml      .=" <li> ".$errorMessage."</li>";
				}
				$errorHtml      .=" </ul>";
			}
            $me                  = Sirah_Fabric::getUser();
            $PDF                 = new Sirah_Pdf_Default("L", "mm", "A3", true , "UTF-8");
            $PDF->SetCreator(sprintf("%s", $opsUsername));
            $PDF->SetTitle(  sprintf("Correction des retraitements effectués par %s", $opsName));
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
		    echo $PDF->Output(sprintf("Corrections%s.pdf", preg_replace("/\s/","-", $opsName)),"D");
		    exit;			
		}		
		$this->view->data        = $defaultData;
		$this->view->localites   = $localites;
		$this->view->annees      = $annees;
		$this->view->users       = $opsUsernames;
	}
	
	
	public function saveusersdataAction()
	{
		@ini_set('memory_limit', '512M');
		$this->view->title  = "Sauvegarder les RCCM retraités des OPS";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelRegistre      = $this->getModel("registre");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $dbAdapter = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("annee"=>2016,"localite"=>"OUA","srcpath"=> "F:\\FNRCCM2017-2018\\OPS", "destpath"=>"F:\\ERCCM","ops_username"=>"");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
		$opsName            = "";
	
		$validItems         = $invalidItems = $errorItems = $dataItems = array();
	
	    $opsUsernames       = array(0=> "Sélectionnez un utilisateur","ouedraogo_alida"=>"OUEDRAOGO ALIDA","sogbo_aurele"=>"SOGBO AURELE","traore_korotimi"=>"TRAORE KOROTIMI","dayamba_raissa"=>"DAYAMBA RAISSA","sangare_alimata"=>"SANGARE ALIMATA","traore_sandrine"=>"TRAORE SANDRINE");
		$localites          = $modelLocalite->getSelectListe(null, array("code", "libelle") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007","2008"=>"2008",
		                            "2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=>"2016","2017"=>"2017");
		if( $this->_request->isPost( )) {
			$postData             = $this->_request->getPost();
			$srcPath              = ( isset( $postData["srcpath"]        )) ? $postData["srcpath"]        : $defaultData["srcpath"];
			$destPath             = ( isset( $postData["destpath"]       )) ? $postData["destpath"]       : $defaultData["destpath"];
			$localite             = ( isset( $postData["localite"]       )) ? $postData["localite"]       : $defaultData["localite"];
			$annee                = ( isset( $postData["annee"]          )) ? intval($postData["annee"])  : $defaultData["annee"];
			$opsUsername          = ( isset( $postData["ops_username"]   )) ? $postData["ops_username"]   : $defaultData["ops_username"];
			$opsName              = ( isset( $opsUsernames[$opsUsername] )) ? $opsUsernames[$opsUsername] : "";
			$numRccmKey           = "BF";
           		
			if( is_dir($destPath ) ){
				@chmod($destPath, 0777);
			}
			if( empty( $opsUsername ) || !isset( $opsUsernames[$opsUsername] ) ) {
				$errorMessages[]  = "Veuillez sélectionner un utilisateur valide";
			} else {
				$opsUsername      = strtoupper($opsUsername);	
				$srcPath          = $srcPath . DS . $opsUsername . DS . "ERCCM";				
			}
            if(!isset(  $localites[$localite] ) ) {
				$errorMessages[]  = "Veuillez sélectionner une localité valide";
			} else {
				$srcPath          = $srcPath    . DS . $localite;
				$numRccmKey       = $numRccmKey . strtoupper($localite);
				if(!is_dir( $destPath ) ){
					@mkdir( $destPath );
					@chmod( $destPath, 0777);
				}
			}	
            if(!isset( $annees[$annee] )) {
				$errorMessages[]  = "Veuillez sélectionner une année valide";
			} else {
				$srcPath          = $srcPath    . DS . $annee;
				$numRccmKey       = $numRccmKey . intval($annee);
			}				
			if(!is_dir( $srcPath ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  n'existe pas. Veuillez vérifier.",  $opsName );
			} else {
				$dataItems        = glob( $srcPath. DS ."*", GLOB_ONLYDIR);
			}
			if(!count( $dataItems ) ) {
				$errorMessages[]  = sprintf( "Le dossier de l'utilisateur %s  pour l'année %d ne contient aucun document. Veuillez vérifier.",  $opsName, $annee );
			}
			//print_r($errorsPath);die();
			if( empty(   $errorMessages ) ) {				
				foreach( $dataItems     as $rccmDirectory ) {
				}
			}
		}
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