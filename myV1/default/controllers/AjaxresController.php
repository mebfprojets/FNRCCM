<?php

class AjaxresController extends Sirah_Controller_Default
{
	
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
	
	public function soutenanceslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$model                   = $this->getModel("soutenance");
		$modelCohorte            = $this->getModel("cohorte");
		$modelSalle              = $this->getModel("salle");
		$me                      = Sirah_Fabric::getUser();
		$cacheManager            = Sirah_Fabric::getCachemanager();
		$instances               = array();
		$instancesListePaginator = null;
		$cohortes                = $modelCohorte->getSelectListe( "Selectionnez une formation" , array("id" , "libelle") , array() , null , null , false );
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
		$calendarYear       = intval(sprintf("%04d" , $this->_getParam("year" , null  )));
		$calendarMonth      = intval(sprintf("%02d" , $this->_getParam("month", null  )));
		$calendarDay        = intval(sprintf("%02d" , $this->_getParam("day"  , null  )));
		$calendarStartDate  = $stringFilter->filter($this->_getParam("startdate", null ));
		$calendarEndDate    = $stringFilter->filter($this->_getParam("endate"   , null ));
		$calendarDate       = $stringFilter->filter($this->_getParam("startdate", null ));
	
		$calendarStartime   = intval( $this->_getParam("startime", 0));
		$calendarEndtime    = intval( $this->_getParam("endtime", 0));
		$limitList          = intval( $this->_getParam("limit"  , 10));
		$pageNum            = intval( $this->_getParam("page"   , 1));
		$defaultCohortid    = intval( $this->_getParam("cohortid" , null ));
		$salleid            = intval( $this->_getParam("salleid" , 0 ));
		$lastname           = strip_tags( $this->_getParam("lastname" , null ) );
		$firstname          = strip_tags( $this->_getParam("firstname", null ) );
	
		if( $me->isEtudiant() || $me->isStudent() ) {
			$modelEtudiant  = $this->getModel("etudiant");
			$myInscription  = $modelEtudiant->getInscription( $me->userid );
			$defaultCohortid= $myInscription->cohortid;
			$cohortes       = array( $defaultCohortid => $cohortes[$myInscription->cohortid] );
		} elseif( $me->isTeacher() || $me->isEnseignant() ) {
			$modelTeacher   = $this->getModel("teacher");
			$myHonoraire    = $modelTeacher->getHonoraire( $me->userid );
			$cohortes       = ( $myHonoraire ) ? $myHonoraire->getCohortes() : array("Aucune unité formation assignée");
		}
		$filters        = array("cohortid" => $defaultCohortid, "salleid" => $salleid , "lastname"     => $lastname    ,
				"firstname"    => $firstname   ,
				"annee"        => $calendarYear,
				"month"        => $calendarMonth,
				"day"          => $calendarDay,
				"startime"     => $calendarStartime,
				"endtime"      => $calendarEndtime,
				"periode_start"=> $calendarStartDate,
				"periode_end"  => $calendarEndDate );
	
		$soutenances             = $model->getList( $filters , $pageNum , $limitList );
		$soutenanceListePaginator= $model->getListPaginator( $filters );
		$cohortes                = $modelCohorte->getSelectListe( "Selectionnez une cohorte" , array("id" , "libelle") , array() , null , null , false );
	
		if( null !== $instancesListePaginator ) {
			$soutenanceListePaginator->setCurrentPageNumber($pageNum );
			$soutenanceListePaginator->setItemCountPerPage( $limitList );
		}
		$this->view->soutenances = $soutenances;
		$this->view->cohortes    = $cohortes;
		$this->view->salles      = $modelSalle->getSelectListe( "Selectionnez une salle de cours" , array("id" , "libelle") , array() , null , null , false );
		$this->view->filters     = $filters;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $limitList;
		$this->view->paginator   = $soutenanceListePaginator;
		$this->view->maxitems    = $limitList;
		$this->view->parentform  = $this->_request->getParam("parentform" , "none");
	
		$this->render("soutenances");
	
	}
	
	public function commandeslistAction()
	{
		$this->_helper->layout->disableLayout(true);
	
		$model              = $this->getModel("commande");
		$modelProduit       = $this->getModel("commandeproduit");
		$modelPrestataire   = $this->getModel("prestataire");
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
		$params              = $this->_request->getParams();
		$pageNum             = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize            = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$filters             = array("prestataireid" => 0 ,
				"produitid"     => 0 ,
				"reference"     => null ,
				"date"          => null ,
				"datelivraison" => null ,
				"statut"        => null );
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
			if( empty( $filters[$filterKey] ) ) {
				$filters[$filterKey]  = null;
			}
		}
		$commandes                = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                = $model->getListPaginator($filters);
	
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->model        = $model;
		$this->view->columns      = array("left");
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->commandes    = $commandes;
		$this->view->prestataires = $modelPrestataire->getSelectListe( "Selectionnez un prestataire" , array("id" , "libelle") , array() , null , null , false );
		$this->view->produits     = $modelProduit->getSelectListe("Selectionnez un produit" , array("id" , "libelle") , array() , null , null , false );
		$this->view->parentform   = $this->_request->getParam("parentform" , "none");
	
		$this->render("commandes");
	}
	
	public function devoirslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$model              = $this->getModel("devoir") ;
		$modelCohorte       = $this->getModel("cohorte");
		$modelCourse        = $this->getModel("course")	;
		$devoirs            = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$filters            = array("libelle"   => null , "cohortid" => 0 , "courseid" => 0 , "startime" => 0 , "endtime"  => 0 );
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$devoirs               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
	
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns   = array("left");
		$this->view->devoirs   = $devoirs;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->parentform= $this->_request->getParam("parentform" , "none");
		$this->view->cohortes  = $modelCohorte->getSelectListe( "Selectionnez une formation" , array("id" , "libelle") , array() , null , null , false );
		$this->view->courses   = $modelCourse->getSelectListe(  "Selectionnez un module de cours" , array("courseid" , "libelle") , array() , null , null , false );
	
		$this->render("devoirs");
	}
	
	public function examslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$model              = $this->getModel("exam");
		$modelCohorte       = $this->getModel("cohorte");
	
		$exams              = array();
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
		$filters              = array("libelle"   => null , "cohortid" => 0 );
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$exams                 = $model->getList($filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns   = array("left");
		$this->view->exams     = $exams;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->parentform= $this->_request->getParam("parentform" , "none");
		$this->view->cohortes  = $modelCohorte->getSelectListe( "Selectionnez une formation" , array("id" , "libelle") , array() , null , null , false );
		$this->render("exams");
	}
	
	public function honoraireslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$model              = $this->getModel("coursehonoraire");
		$modelCohorte       = $this->getModel("cohorte");
		$modelCourse        = $this->getModel("course");
	
		$honoraires         = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
	
		$defaultLibelle     = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters            = array("libelle"=> $defaultLibelle , "teachername"=> null , "courseid" => 0, "cohortid"=> 0,
				                    "statut" => 0, "periode_start_year" => 0, "periode_end_year"   => 0);
		if(!empty($params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$honoraires            = $model->getList($filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
	
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->honoraires = $honoraires;
		$this->view->filters    = $filters;
		$this->view->paginator  = $paginator;
		$this->view->pageNum    = $pageNum;
		$this->view->pageSize   = $pageSize;
		$this->view->cohortes   = $modelCohorte->getSelectListe( "Selectionnez une formation" , array("id" , "libelle") , array() , null , null , false );
		$this->view->courses    = $modelCourse->getSelectListe(  "Selectionnez un module de cours" , array("courseid" , "libelle") , array() , null , null , false );
		$this->view->parentform = $this->_request->getParam("parentform" , "none");
		$this->render("honoraires");
	}
	
	
	public function candidatslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$cacheManager            = Sirah_Fabric::getCachemanager();
		$candidats               = array();
		$candidatsListePaginator = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer" , "zero" , "string" , "float" , "empty_array" , "null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
	
		$defaultName        = (isset($params["global-filter"]) && !empty($params["global-filter"])) ? $stringFilter->filter($params["global-filter"]) : null;
		$filters            = array(
				"lastname"           => null,
				"firstname"          => $defaultName,
				"username"           => null,
				"email"              => null,
				"phone"              => null,
				"cohortid"           => 0,
				"sexe"               => null,
				"periode_start_year" => 0,
				"periode_end_year"   => 0);
		if(!empty($params)) {
			foreach( $params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$model                   = $this->getModel("candidat");
		$modelCohorte            = $this->getModel("cohorte");
		$candidats               = $model->getListe($filters , $pageNum , $pageSize );
		$candidatsListePaginator = $model->getListePaginator($filters);
		if( null !== $candidatsListePaginator ) {
			$candidatsListePaginator->setCurrentPageNumber($pageNum);
			$candidatsListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->users     = $candidats;
		$this->view->cohortes  = $modelCohorte->getSelectListe("Selectionnez une cohorte" , array("id" , "libelle") );
		$this->view->filters   = $filters;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;
		$this->view->paginator = $candidatsListePaginator;
		$this->view->parentform= $this->_request->getParam("parentform" , "none");
		$this->view->maxitems  = $pageSize;
		$this->render("candidats");
	}
	
	public function etudiantslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$cacheManager            = Sirah_Fabric::getCachemanager();
		$etudiants               = array();
		$etudiantsListePaginator = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultName        = (isset($params["global-filter"]) && !empty($params["global-filter"])) ? $stringFilter->filter($params["global-filter"]) : null;
		$filters            = array(
				"lastname"           => null,
				"firstname"          => null,
				"username"           => $defaultName,
				"email"              => null,
				"phone"              => null,
				"activated"          => 1,
				"locked"             => 0,
				"blocked"            => 0,
				"expired"            => 0,
				"cohortid"           => 0,
				"socialstate"        => null,
				"sexe"               => null,
				"admin"              => null,
				"country"            => null,
				"language"           => null,
				"periode_start_year" => 0,
				"periode_end_year"   => 0);
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$model                   = $this->getModel("etudiant");
		$modelCohorte            = $this->getModel("cohorte");
		$etudiants               = $model->getListe($filters , $pageNum , $pageSize);
		$etudiantsListePaginator = $model->getListePaginator($filters);
	
		if(null !== $etudiantsListePaginator ) {
			$etudiantsListePaginator->setCurrentPageNumber($pageNum);
			$etudiantsListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->users     = $etudiants;
		$this->view->cohortes  = $modelCohorte->getSelectListe("Selectionnez une formation" , array("id" , "libelle") );
		$this->view->filters   = $filters;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;
		$this->view->paginator = $etudiantsListePaginator;
		$this->view->parentform= $this->_request->getParam("parentform" , "none");
		$this->view->maxitems  = $pageSize;
		$this->render("etudiants");
	}
	
	public function teacherslistAction()
	{
		$this->_helper->layout->disableLayout(true);
		$cacheManager            = Sirah_Fabric::getCachemanager();
		$teachers                = array();
		$teachersListePaginator  = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultName        = (isset($params["global-filter"]) && !empty($params["global-filter"])) ? $stringFilter->filter($params["global-filter"]) : null;
		$filters            = array(
				                    "lastname"           => null,
				                    "firstname"          => $defaultName,
				                    "username"           => null,
				                    "email"              => null,
				                    "phone"              => null,
				                    "socialstate"        => null,
				                    "cohortid"           => 0,
				                    "periode_start_year" => 0,
				                    "periode_end_year"   => 0,
				                    "sexe"               => null);
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$model                   = $this->getModel("teacher");
		$modelCohorte            = $this->getModel("cohorte");
		$teachers                = $model->getListe($filters , $pageNum , $pageSize);
		$teachersListePaginator  = $model->getListePaginator($filters);
	
		if( null !== $teachersListePaginator ) {
			$teachersListePaginator->setCurrentPageNumber($pageNum);
			$teachersListePaginator->setItemCountPerPage($pageSize);
		}
		$this->view->users     = $teachers;
		$this->view->cohortes  = $modelCohorte->getSelectListe("Selectionnez une formation" , array("id" , "libelle") );
		$this->view->filters   = $filters;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;
		$this->view->paginator = $teachersListePaginator;
		$this->view->parentform= $this->_request->getParam("parentform" , "none");
		$this->view->maxitems  = $pageSize;
		$this->render("teachers");
	}
	
	public function inscriptionslistAction()
	{
		$this->_helper->layout->disableLayout(true);
	
		$model              = $this->getModel("inscription");
		$modelCohorte       = $this->getModel("cohorte")	;
		$inscriptions       = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultName        = $stringFilter->filter($this->_getParam("globalfilter", null));
		$filters            = array("numdossier" => null, "name" => $defaultName, "lastname"    => null, "firstname" => null, "username" => null ,
				                    "email"      => null, "cohortid" => 0, "periode_start_year" => START_YEAR, "periode_end_year" => END_YEAR);
		if(!empty($params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$inscriptions          = ( BASIC_MODE ) ? $model->basicList( $filters , $pageNum , $pageSize) : $model->getList($filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns      = array("left");
		$this->view->inscriptions = $inscriptions;
		$this->view->cohortes     = $modelCohorte->getSelectListe("Selectionnez une formation" , array("id" , "libelle") );
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->parentform   = $this->_request->getParam("parentform" , "none");
		$this->view->maxitems     = $pageSize;
		$this->view->showSearch   = intval($this->_getParam("showsearch", true ));
		if( BASIC_MODE ) {
			$this->render("basicinscriptions");
		} else {
			$this->render("inscriptions");
		}		
	}
	
	
	public function basicinscriptionsAction()
	{
		$this->_helper->layout->disableLayout(true);
	
		$model              = $this->getModel("inscription");
		$modelCohorte       = $this->getModel("cohorte")	;
		$inscriptions       = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultName        = $stringFilter->filter($this->_getParam("globalfilter", null));
		$filters            = array("numdossier" => null, "name" => $defaultName, "lastname" => null, "firstname" => null, "username" => null ,
				                    "email"      => null, "cohortid" => 0, "periode_start_year" => START_YEAR, "periode_end_year"   => END_YEAR);
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$inscriptions          = $model->basicList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns      = array("left");
		$this->view->inscriptions = $inscriptions;
		$this->view->cohortes     = $modelCohorte->getSelectListe("Selectionnez une formation" , array("id" , "libelle") );
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->parentform   = $this->_request->getParam("parentform" , "none");
		$this->view->maxitems     = $pageSize;
		$this->view->showSearch   = intval($this->_getParam("showsearch", true ));
		$this->render("basicinscriptions");
	}
	
	
	public function candidatureslistAction()
	{
		$this->_helper->layout->disableLayout(true);
	
		$model              = $this->getModel("candidature");
		$modelCohorte       = $this->getModel("cohorte")	;
		$inscriptions       = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultNumDossier  = $stringFilter->filter($this->_getParam("global-filter" , null));
		$filters            = array("numdossier"         => $defaultNumDossier ,
				                    "username"           => null ,
				                    "lastname"           => null ,
				                    "firstname"          => null ,
				                    "email"              => null ,
				                    "processed"          => null,
				                    "validated"          => null,
				                    "cohortid"           => 0,
				                    "cohortetype"        => 0,
				                    "userid"             => 0 ,
				                    "periode_start_year" => 0,
				                    "periode_end_year"   => 0,
				                    "datecandidature"    => null ,
				                    "annee"              => null);			
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$candidatures            = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator($filters);
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns      = array("left");
		$this->view->candidatures = $candidatures;
		$this->view->cohortes     = $modelCohorte->getSelectListe("Selectionnez une formation" , array("id" , "libelle") );
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;
		$this->view->maxitems     = $pageSize;
		$this->view->parentform   = $this->_request->getParam("parentform" , "none");
		$this->render("candidatures");
	}
}