<?php

class RegistresController extends Sirah_Controller_Default
{	

    public function ajaxsearchAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("registre");
		$ipaddress    = $guestIp = Sirah_Functions::getIpAddress();
		if( $model->hasBlasklisted($ipaddress)) {
			$errorMessage = sprintf("Votre adresse IP : '%s' semble blacklistée. Vous n'êtes pas autorisé à accéder à cette page", $ipaddress);
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> $errorMessage));
				exit;
			}
			$this->setRedirect($errorMessage, "error");
			$this->redirect("public/index/index");
		}
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$stateStore   = new Zend_Session_Namespace("Statestore");
		$resetSearch  = intval($this->_getParam("reset", 0));
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters      = array("_registres" => array());
			$stateStore->foundResults = 0;
			$stateStore->searchCounter= 0;
		}
	    if(!isset( $stateStore->searchCounter)){
			$stateStore->searchCounter= 0;
		}
		if(!$this->_helper->csrf->isValid() ) {	
		    echo ZendX_JQuery::encodeJson(array("error"=>"Veuillez actualiser la page. Le jeton transmis semble expiré"));
		    exit;
		}
		$stateStore->searchCounter++;
		$stateStore->setExpirationSeconds(7200);
		$stateStore->setExpirationSeconds(1800,"searchCounter");
		
		if( isset($stateStore->searchCounter) && ($stateStore->searchCounter>125)) {
			echo ZendX_JQuery::encodeJson(array("error"=>"Vous ne pouvez pas effectuer cette opération plus de 25 fois"));
		    exit;
		}
 
		$query               = $stringFilter->filter( $this->_getParam("q", null ) );
		$typeid              = intval($this->_getParam("type",  0));
		$totalResults        = intval($this->_getParam("limit", 10));
		$keywords            = substr(preg_replace("/[_,;]/"," ",$query),0,300);
		$searchQ             = Sirah_Functions_String::cleanUtf8($keywords);
		$searchQ             = preg_replace("/[#|\^@\*\"]/","",$searchQ);
		$rows                = $errorMessages = array();
		try {
			$rows            = $model->basicList(array("searchQ"=>$searchQ,"type"=>$typeid,"types"=>array(1,2,3,4)), 1, $totalResults);
		} catch(Exception $e) {
			$rows            = array();
			$errorMessages[] = sprintf("Une ereur technique s'est produite : %s", $e->getMessage());
		}
		
		$jsonRows            = array( 0 => array("label"=>0, "value"=>"Aucun résultat n'a été trouvé avec ces mots clés..."));
		
		
		$searchKey                      = "AJAX:".$searchQ;
		$hasSearchedValue               = 1;
		try {
			$hasSearchedValue           = $model->hasSearched($ipaddress, $searchKey);
		} catch(Exception $e ) {
			$errorMessages[]            = sprintf("Une ereur technique s'est produite : %s", $e->getMessage());
		}
		if(!$hasSearchedValue) {
			$modelTable                 = $model->getTable();
			$dbAdapter                  = $modelTable->getAdapter();
			$tablePrefix                = $modelTable->info("namePrefix");
			$searchLog                  = array();
			$searchLog["ipaddress"]     = $ipaddress;
			$searchLog["searchkey"]     = $searchKey;
			$searchLog["searchresults"] = 0;
			$searchLog["creationdate"]  = time();
			$searchLog["creatorid"]     = 1;
            $searchLog["searchresults"] = count($rows);
			try {
				$dbAdapter->delete( $tablePrefix."rccm_registre_search", array("ipaddress=?"=>$ipaddress,"searchkey=?"=>$searchLog["searchkey"]));
			    $dbAdapter->insert( $tablePrefix."rccm_registre_search", $searchLog);
			} catch(Exception $e ) {
				$errorMessages[]        = sprintf("Une ereur technique s'est produite : %s", $e->getMessage());
			}			
		}
		if( count(   $rows ) ) {
			$rowid    = 0;
			$jsonRows = array();
			foreach( $rows as $row ) {
					 $jsonRows[$rowid]["label"] = $row["numero"];
					 $jsonRows[$rowid]["value"] = $row["libelle"];				
				     $rowid++;
			}
		}
		echo ZendX_JQuery::encodeJson($jsonRows);
		exit;
	}

    public function listAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  		
		$this->view->title  = "FNRCCM : Recherche de personnes physiques ou de personnes morales"  ;
		
		$view               = $this->view;
		$model              = $this->getModel("registre");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		
		$registres          = $errorMessages = array();
		$paginator          = null;
		$me                 = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10;	
		$adavancedSearch      = (isset($params["advanced"]))? intval($params["advanced"]) : 0;	
		$query                = (isset($params["q"]       ))? $stringFilter->filter($params["q"]) : null;
		$keywords             = substr(preg_replace("/[\-_,;]/"," ",$query),0,200);
		$searchQ              = preg_replace("/ SARL| SA/i","",Sirah_Functions_String::cleanUtf8($keywords));
		$searchQ              = preg_replace("/[#|\^@\*\"]/","",$searchQ);
		
		$filters              = array("libelle"=>null,"numero"=>null,"domaineid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"searchQ"=>$searchQ,"date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"type"=>0,"keywords"=>null,
		                              "periode_start_day"=>0,"periode_start_month"=>0,"periode_start_year"=>0,"periode_end_day" =>0,"periode_end_month"=>0,"periode_end_year"=>0,"advanced"=>1);		
		$findSimilar          = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    )) ? Sirah_Functions_String::cleanUtf8(substr(urldecode($params["searchq"]),0,200)) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		 
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		   $zendDate               = new Zend_Date(array("year" => $filters["date_year"] ,"month" => $filters["date_month"],"day" => $filters["date_day"]  ));
		   $filters["date"]        = ($zendDate ) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if((isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_day"]))
				&&
		   (isset($filters["periode_end_day"])   && intval($filters["periode_end_day"] ))  && (isset($filters["periode_start_day"])   && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart         = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd           = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"] = ($zendPeriodeStart) ? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periode_end"]   = ($zendPeriodeEnd  ) ? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}		
		$params["searchQ"]            = (isset( $filters["searchQ"] ))? $filters["searchQ"] : "";
		try {
			$registres                = $model->basicList( $filters , $pageNum , $pageSize);
		    $paginator                = $model->getListPaginator($filters);
		} catch(Exception $e) {
			$errorMessages[]          = sprintf("Erreur technique : %s ", $e->getMessage());
		}				
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns          = array("left");
		$this->view->registres        = $registres;
		$this->view->domaines         = $modelDomaine->getSelectListe( "Sélectionnez un secteur d'activité", array("domaineid" , "libelle"));
		$this->view->localites        = $modelLocalite->getSelectListe("Sélectionnez une localité"         , array("localiteid", "libelle"));
		$this->view->users            = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types            = array(0 => "Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts          = array(0 => "Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		$this->view->pageNum          = $pageNum;
		$this->view->advanced         = $adavancedSearch;
		$this->view->pageSize         = $this->view->maxitems = $pageSize;	
        $this->view->title            = ( !empty($params["searchQ"]) ) ? sprintf("ERCCM : les résultats de la recherche '%s' ", $params["searchQ"]) : "ERCCM : Faites vos recherches";
		
		$view->headMeta()->appendName("keywords",sprintf("%s,RCCM,entreprise,société,SARL,registre,commerce",$searchQ));
		if( count($registres) && isset( $registres[0])) {
			$registre                 = $registres[0];
			$view->headMeta()->appendName("description", htmlentities(strip_tags(sprintf("Fichier National RCCM du Burkina Faso %s %s : %s", $registre["numero"], $registre["libelle"], $registre["description"]))) );
		}
	}

     
	public function searchAction()
	{		
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} 		
		$this->view->title       = "ERCCM : Recherche de personnes physiques ou de personnes morales"  ;
		
		$model                   = $modelRegistre = $this->getModel("registre");
		$modelLocalite           = $this->getModel("localite");
		$modelDomaine            = $this->getModel("domaine");
		
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$tablePrefix             = $modelTable->info("namePrefix");
		
	    $csrfTokenId             = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue          = $this->_helper->csrf->getToken(300);
		$csrfFormNames           = $this->_helper->csrf->getFormNames(array("searchQ","query") , false );
		$guestIp                 = $ipaddress = Sirah_Functions::getIpAddress(); 
		if( $model->hasBlasklisted($ipaddress)) {
			$errorMessage = sprintf("Votre adresse IP : '%s' semble blacklistée. Vous n'êtes pas autorisé à accéder à cette page", $ipaddress);
			
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> $errorMessage));
				exit;
			}
			$this->setRedirect($errorMessage, "error");
			$this->redirect("public/index/index");
		}
		$registres               = $errorMessages = array();
		$paginator               = null;
		$me                      = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter            = new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
		$stateStore              = new Zend_Session_Namespace("Statestore");
		$resetSearch             = intval($this->_getParam("reset", 0));
		
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters      = array("_registres" => array());
			$stateStore->foundResults = 0;
			$stateStore->searchCounter= 0;

		}
		if(!isset($stateStore->filters["_registres"]["searchQ"]) && !isset($stateStore->filters["_registres"]["libelle"]) && !isset( $stateStore->filters["_registres"]["keywords"]) && !isset( $stateStore->filters["_registres"]["numero"])
		&& !isset($stateStore->filters["_registres"]["numifu"])  && !isset($stateStore->filters["_registres"]["numcnss"])) {
			$stateStore->filters["_registres"] = array("searchQ"=>null,"q"=>null,"name"=>null,"libelle"=>null,"numifu"=>null,"numcnss"=>null,"numero"=>null,"domaineid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>"","prenom"=>"","searchQ"=>"","date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"name"=>null,"typeid"=>0,"type"=>0,"keywords"=>null,"maxitems"=>0);
		}
		$stateStore->searchCounter++;
		
		$stateStore->setExpirationSeconds(3600);
		$stateStore->setExpirationSeconds(1800,"searchCounter");
		
		$postData                     = $this->_request->getPost();
		 
		if( isset($stateStore->searchCounter) && ($stateStore->searchCounter>125)) {
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération plus de 125 fois","error");
			$this->redirect("public/index/index");
		}
		if( $this->_request->isPost() && !$this->_helper->csrf->isValid()) {
			$this->setRedirect("Vous ne pouvez pas effectuer cette opération pour des raisons de sécurité","error");
			$this->redirect("public/index/index");
		}
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]               ))? intval($params["page"])                        : 1;
		$pageSize             = (isset($params["maxitems"]           ))? intval($params["maxitems"])                    : 10;	
		$advancedSearch       = (isset($params["advanced"]           ))? intval($params["advanced"])                    : 0;	
		$advancedSearchForm   = (isset($params["advancedSearchForm"] ))? intval($params["advancedSearchForm"])          : 0;	
		$query                = (isset($params["q"]                  ))? $stringFilter->filter(urldecode($params["q"])) : ((isset($params["searchQ"]))?$stringFilter->filter($params["searchQ"]): "");
		
		$keywords             = substr(preg_replace("/[_,;]/"," ",$query),0,200);
		$searchQ              = Sirah_Functions_String::cleanUtf8($keywords);
		$searchQ              = preg_replace("/[#|\^@\*\"]/","",$searchQ);
		
		$searchLog            = array();
		$filters              = array_merge(array("searchQ"=>$searchQ,"q"=>"","name"=>"","libelle"=>"","keywords"=>"","numifu"=>null,"numcnss"=>null,"numero"=>"","domaineids"=>"","localiteids"=>""),$stateStore->filters["_registres"]);
        $params               = array_merge($filters, $params);
		$findSimilar          = (isset($params["findsimilar"]))? intval($params["findsimilar"]) : 0;
		$similarSearchQ       = (isset($params["searchq"]    ))? urldecode($params["searchq"])  : "";
		$makeSearch           = false;
		$domaineids           = $localiteids           = $errorMessages = array();
		$stateStore->filters["_registres"]["maxitems"] = $pageSize;
		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     if( array_key_exists($filterKey,$filters) && !empty($params[$filterKey])) {
						 $filters[$filterKey] = substr(preg_replace("/[#|\^@\*\"]/","", Sirah_Functions_String::cleanUtf8($stringFilter->filter($filterValue))),0,250);
						 $makeSearch          = true;
					 }				     
			}
			if( empty($filters["searchQ"]) && empty($filters["libelle"]) && empty($filters["keywords"]) && empty($filters["name"]) && empty($filters["numero"]) && empty($filters["numifu"]) && empty($filters["numcnss"]) && empty($filters["domaineids"]) && empty($filters["localiteids"])) {
			    $makeSearch = false;
			}   
		}
		if(!empty($searchQ )) {
			$advancedSearch                     = 0;
			$makeSearch                         = true;
			$filters["searchQ"]                 = $params["searchQ"] = $searchQ;
			
			if(!empty($guestIp)) {
				$searchKey                      = $searchQ;
				if(!$model->hasSearched($ipaddress, $searchKey)) {
					$searchLog["ipaddress"]     = $ipaddress;
					$searchLog["searchkey"]     = $searchKey;
					$searchLog["searchresults"] = 0;
					$searchLog["creationdate"]  = time();
					$searchLog["creatorid"]     = 1;					
				}
			}
		}
        if((isset($filters["date_month"]) && intval( $filters["date_month"])) && (isset($filters["date_year"]) && intval( $filters["date_year"] )) && (isset( $filters["date_year"]) && intval( $filters["date_year"] ))) {
		    $zendDate                     = new Zend_Date(array("year"=>$filters["date_year"],"month"=>$filters["date_month"],"day" => $filters["date_day"]  ));
		    $filters["date"]              = ($zendDate) ? $zendDate->get("Y-MM-d") : "";			   
		}
		if( (isset($filters["periode_end_month"]) && intval($filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval($filters["periode_start_day"]))
				&&
			(isset($filters["periode_end_day"])   && intval($filters["periode_end_day"]  )) && (isset($filters["periode_start_day"])  && intval($filters["periode_start_day"]  ))
		)	{
			$zendPeriodeStart             = new Zend_Date(array("year"=>$filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]));
			$zendPeriodeEnd               = new Zend_Date(array("year"=>$filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]  ));
			$filters["periode_start"]     = ($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP) : 0;
			$filters["periode_end"]       = ($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP) : 0;
		}	
        if( isset($params["domaineids"]) && !empty($params["domaineids"] )) {
			$makeSearch                   = true;
			$domaineids                   = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids               = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey]   = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]        = $domaineids;
		}		
		if( isset($params["localiteids"]) && !empty($params["localiteids"])) {
			$makeSearch                   = true;
			$localiteids                  = (array) $params["localiteids"];
			if( is_string( $localiteids)) {
				$localiteids              = array(  $params["localiteids"] );
			}
			foreach( $localiteids as $dKey=> $dVal ) {
				     $localiteids[$dKey]  = $stringFilter->filter($dVal );
			}
			$filters["localiteids"]       = $localiteids;
		}	
        if(!intval($pageNum)) {
			$pageNum                      = 1;
		}		
        if(!intval($pageSize) || $pageSize> 100) {
			$pageSize                     = 10;
		}		
		$stateStore->filters["_registres"]= array_merge($stateStore->filters["_registres"], $filters);	
		$params["searchQ"]                = (isset( $filters["searchQ"] ))? $filters["searchQ"] : "";
		//var_dump($filters); die();	
		try {
			$registres                    = ($makeSearch)? $model->getList($filters,$pageNum,$pageSize) : array();
		    $paginator                    = ($makeSearch)? $model->getListPaginator($filters) : null;
		} catch(Exception $e) {
			$errorMessages[]              = sprintf("Erreur technique : %s", $e->getMessage());
		}
		if( count($registres) && isset($searchLog["ipaddress"])){
			$searchLog["searchresults"]   = (null!==$paginator)? $paginator->getTotalItemCount() : count($registres);
			$dbAdapter->delete( $tablePrefix."rccm_registre_search", array("ipaddress=?"=>$ipaddress,"searchkey=?"=>$searchLog["searchkey"]));
			$dbAdapter->insert( $tablePrefix."rccm_registre_search", $searchLog);
		}
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		if(!count($registres) && !$makeSearch ) {
			$advancedSearch               = 1;
		} elseif(count($registres)) {
			$advancedSearch               = 0;
		}
		$advancedSearch                   = intval($this->_getParam("advanced", $advancedSearch));
		$view                             = $this->view;
		$view->headMeta()->appendName("keywords"   , sprintf("%s,RCCM,entreprise,société,SARL,registre,commerce",$searchQ));
		$this->view->columns              = array("left");

		if( count($registres) && isset( $registres[0])) {
			$registre                 = $registres[0];
			$view->headMeta()->appendName("description", htmlentities(strip_tags(sprintf("Fichier National RCCM du Burkina Faso %s %s : %s", $registre["numero"], $registre["libelle"], $registre["description"]))) );
		} else {
			$view->headMeta()->appendName("description", "Le service en ligne de « Recherche d’entreprises » met à la disposition du public une base de données nationale comportant des informations sur des entreprises immatriculées au Burkina Faso de l’année 2000 à nos jours. ");        
		}
		$this->view->registres            = $registres;
		$this->view->checkedLocalites     = $localiteids;
		$this->view->checkedDomaines      = $domaineids;
		$this->view->csrfTokenId          = $csrfTokenId;
		$this->view->csrfTokenValue       = $csrfTokenValue;
		$this->view->formNames            = $csrfFormNames;
		$this->view->domaines             = $modelDomaine->getSelectListe( "Secteur d'activité", array("domaineid","libelle"));
		$this->view->localites            = $modelLocalite->getSelectListe("Localité"          , array("localiteid","libelle"),array(),null, null , false );
		$this->view->users                = array(0=>"Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types                = array(0=>"Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts              = array(0=>"Selectionnez un statut", 1 => "Créé", 2 => "Modifié", 3 => "Radié");
		$this->view->filters              = $filters;
		$this->view->params               = $params;
		$this->view->paginator            = $paginator;
		$this->view->pageNum              = $pageNum;
		$this->view->advanced             = $advancedSearch;
		$this->view->advancedSearchForm   = $advancedSearchForm;
		$this->view->pageSize             = $this->view->maxitems = $pageSize;	
        $this->view->title                = (!empty($params["searchQ"]) ) ? sprintf("ERCCM : les résultats de la recherche '%s' ", $params["searchQ"]) : "Recherchez une entreprise";		
	}
	
	public function advancedsearchAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  		
		$this->view->title                = "FNRCCM : Recherche Avancée d'entreprises"  ;
		
		$model                            = $this->getModel("registre");
		$modelLocalite                    = $this->getModel("localite");
		$modelDomaine                     = $this->getModel("domaine");
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		$csrfTokenId           = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue        = $this->_helper->csrf->getToken(300);
		$csrfFormNames         = $this->_helper->csrf->getFormNames(array("searchQ","query") , false );
		
		$params                = $this->_request->getParams();
		$pageNum               = (isset($params["page"]       ))? intval($params["page"])     : 1;
		$pageSize              = (isset($params["maxitems"]   ))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;	
		$adavancedSearch       = (isset($params["advanced"]   ))? intval($params["advanced"]) : 0;	
		$searchQ               = (isset($params["q"]          ))? $stringFilter->filter(urldecode($params["q"])) : null;
		$filters               = array("searchQ"=>$searchQ,"libelle"=>null,"numero"=>null,"domaineid"=>0,"localiteid"=>0,"annee"=>0,"nom"=>null,"prenom"=>null,"date_year"=>null,"date_month"=>null,"date_day"=>null,"passport"=>null,"telephone"=>null,"country"=>0,"name"=>null,"type"=>0,"keywords"=>null,
		                               "periode_start_day"=>DEFAULT_START_DAY,"periode_start_month"=>DEFAULT_START_MONTH,"periode_start_year"=>DEFAULT_START_YEAR,"periode_end_day" =>DEFAULT_END_DAY ,"periode_end_month"=>DEFAULT_END_MONTH,"periode_end_year"=>DEFAULT_END_YEAR,"advanced"=>1);		
		$findSimilar           = (isset($params["findsimilar"])) ? intval($params["findsimilar"]) : 0;
		$similarSearchQ        = (isset($params["searchq"]    )) ? urldecode($params["searchq"]) : "";
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$view                             = $this->view;
		$view->headMeta()->appendName("description", "Le service en ligne de « Recherche d’entreprises » met à la disposition du public une base de données nationale comportant des informations sur des entreprises immatriculées au Burkina Faso de l’année 2000 à nos jours. ");
        $view->headMeta()->appendName("keywords", "recherche,entreprise,burkina,ouagadougou,faso,afrique,ohada,rccm,entreprise,entreprises,ohada,base,de,donnees,MEBF,CCIBF,fichier-national,maison,de,l-entreprise,mebf,registre,erccm,fnrccm,documents,fn-rccm,e-rccm,FN-RCCM,RCCM,commerce,fichier,national,credit,mobilier,reservation,disponibilité,nom,commercial,denomination" );
		 
		$this->view->domaines       = $modelDomaine->getSelectListe( "Secteur d'activité", array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites      = $modelLocalite->getSelectListe("Localité"          , array("localiteid", "libelle") , array() , null , null , false );
		$this->view->users          = array(0 => "Selectionnez le créateur")+$modelLocalite->getUsersList($filters["localiteid"]);
		$this->view->types          = array(0 => "Type de registre",1 => "Personnes Physiques",2=> "Personnes Morales",3=>"Sûrétés",4=>"Modifications");
		$this->view->statuts        = array(0 => "Selectionnez un statut",1=>"Créé",2=>"Modifié",3=> "Radié");
		$this->view->filters        = $filters;
		$this->view->params         = $params;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->formNames      = $csrfFormNames;
		$this->view->advanced       = 1;
	}
	
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  
		
		$registreid             = $id = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/registres/list");
		}
		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelMorale           = $this->getModel("registremorale");
		$modelDirigeant        = $this->getModel("registredirigeant");
		$modelRepresentant     = $this->getModel("representant");
		$modelEntreprise       = $this->getModel("entreprise");	
 
        $modelTable            = $model->getTable();
		$dbAdapter             = $modelTable->getAdapter();
		$tablePrefix           = $modelTable->info("namePrefix");
		
		$guestIp               = $ipaddress = Sirah_Functions::getIpAddress();
		
		if( $model->hasBlasklisted($ipaddress)) {
			$errorMessage = sprintf("Votre adresse IP : '%s' semble blacklistée. Vous n'êtes pas autorisé à accéder à cette page", $ipaddress);
			
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> $errorMessage));
				exit;
			}
			$this->setRedirect($errorMessage, "error");
			$this->redirect("public/index/index");
		}
		$view                  = $this->view;
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$renderView            = "infos";		
		if(!$registre ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/registres/list");
		}
		
		if(!empty($guestIp)) {
			$stateStore                   = new Zend_Session_Namespace("Statestore");
			$ipaddress                    = $guestIp;
			$searchKey                    = (isset($stateStore->filters["_registres"]["searchQ"]) && !empty($stateStore->filters["_registres"]["searchQ"]))? $stateStore->filters["_registres"]["searchQ"] : $registre->libelle;
			$stateStore->filters["_registres"]["searchQ"] = $searchKey;
			if(!$model->hasViewed($ipaddress, $registreid)) {
				$viewLog                  = array();
				$viewLog["ipaddress"]     = $ipaddress;
				$viewLog["searchkey"]     = $searchKey;
				$viewLog["numrccm"]       = $registre->numero;
				$viewLog["libelle"]       = $registre->libelle;
				$viewLog["registreid"]    = $registreid;
				$viewLog["creationdate"]  = time();
				$viewLog["creatorid"]     = 1;
				$dbAdapter->delete( $tablePrefix."rccm_registre_consultations", array("ipaddress=?"=>$ipaddress,"registreid=?"=>$registreid));
				$dbAdapter->insert( $tablePrefix."rccm_registre_consultations", $viewLog);
			}
		}
		$entreprise                = $modelEntreprise->findRow( $registreid, "registreid", null , false  );
        $dirigeants                = array()/*$registre->dirigeants($registreid)*/;
		$dirigeant                 = $modelDirigeant->findRow( $registreid,"registreid",null,false )	;
		$representant              = ($dirigeant)?$modelRepresentant->findRow($dirigeant->representantid,"representantid", null , false ) : null;
		$registreData              = $registre->toArray();
		$dirigeantData             = ( $dirigeant   )? $dirigeant->toArray()    : array();
		$representantData          = ( $representant)? $representant->toArray() : array();
		$entrepriseData            = ( $entreprise  )? $entreprise->toArray()   : array();
		$defaultData               = array_merge( $representantData, $dirigeantData, $registreData,$entrepriseData );
		$this->view->representant  = ($representant )? $representant : $registre->representant( $registreid);
		$this->view->dirigeants    = $dirigeants;
		$this->view->entreprise    = $entreprise;
		$this->view->forme         = ( $entreprise)? $entreprise->findParentRow("Table_Entrepriseformes") : null;
		if( $registre->type == 1 ) {			   
			$renderView            = "physique";
		} elseif( $registre->type == 2 ) {
			$renderView            = "morale";
		}								
		$this->view->data          = $defaultData;
		$this->view->registre      = $registre;
		$this->view->registreid    = $registreid;
		$this->view->numero        = $numero = $registre->numero;		
		$this->view->domaine       = $registre->findParentRow("Table_Domaines");
		$this->view->localite      = $registre->findParentRow("Table_Localites");
		$this->view->documents     = $registre->documents($registreid);
		$this->view->modifications = $registre->modifications($registreid);
		$this->view->suretes       = $registre->suretes($registreid);
		$this->view->address       = null;
		$this->view->title         = sprintf("Les informations du registre numero %s", $numero);
		
		$view->headMeta()->appendName("keywords", sprintf("%s,%s,%s,%s", $numero, $registre->libelle, $registre->numifu, $registre->numcnss));
		
		$view->headMeta()->appendName("description", htmlentities(strip_tags(sprintf("Fichier National RCCM du Burkina Faso %s %s : %s",$numero,$registre->libelle,$registre->description))) );
 
		$this->render( $renderView );		 
	}
	
}