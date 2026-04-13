<?php

class EntreprisesController extends Sirah_Controller_Default
{
			
	public function listAction()
	{
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title    = "ANNUAIRE DES ENTREPRISES"  ;
		
		$model                = $this->getModel("entreprise");
		$modelDomaine         = $this->getModel("domaine");
		$modelFormation       = $this->getModel("formation");
		$modelCity            = $this->getModel("countrycity");
		$modelEntreprisegroup = $this->getModel("entreprisegroup");
		$modelEntrepriseforme = $this->getModel("entrepriseforme");
		$entreprises          = array();
		$paginator            = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter        =   new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$default              = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters              = array("libelle"=>$default,"reference"=> null,"email" => null,"phone"=> null,"siteweb"=> null,"groupid"=> 0,
		                              "domaineid"=>0,"formid"=>0,"formationid"=>0,"domaine"=> null,"localiteid"=> null,"secteur" => null);
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( isset( $params["domaineids"] ) && !empty( $params["domaineids"] ) ) {
			$domaineids          = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids      = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey]   = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]= $domaineids;
		}
        if( isset( $params["metierids"] ) && !empty( $params["metierids"] ) ) {
			$metierids          = (array)$params["metierids"];
			if( is_string( $metierids) ) {
				$metierids      = array( $params["metierids"] );
			}
			foreach( $metierids as $metierKey => $metierVal ) {
				     $metierids[$metierKey]   = $stringFilter->filter($metierVal);
			}
			$filters["metierids"]  = $metierids;
		}
		if( isset( $params["formationids"] ) && !empty( $params["formationids"] ) ) {
			$formationids          = (array)$params["formationids"];
			if( is_string( $formationids) ) {
				$formationids      = array( $params["formationids"] );
			}
			foreach( $formationids as $formationKey => $formationVal ) {
				     $formationids[$formationKey]   = $stringFilter->filter($formationVal );
			}
			$filters["formationids"]   = $formationids;
		}
		$entreprises                   = $model->getList($filters , $pageNum , $pageSize);
		$paginator                     = $model->getListPaginator( $filters );
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->entreprises       = $entreprises;
		$this->view->checkedDomaines   = $domaineids;
		$this->view->checkedMetiers    = $metierids;
		$this->view->checkedFormations = $formationids;
		$this->view->groupes           = $modelEntreprisegroup->getSelectListe("Sélectionnez un type d'entreprise", array("groupid"    , "libelle"),array("parentid"=>1,"orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines          = $modelDomaine->getSelectListe("Sélectionnez des secteurs d'activités"    , array("domaineid"  , "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->formes            = $modelEntrepriseforme->getSelectListe("Sélectionnez une forme juridique" , array("formid"     , "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->localites         = $modelCity->getSelectListe("Sélectionnez une ville"                   , array("localiteid" , "city_name"), array("orders" => array("city_name ASC")), null , null , false );
		$this->view->formations        = $modelFormation->getSelectListe("Sélectionnez des formations"            , array("formationid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->filters           = $filters;
		$this->view->paginator         = $paginator;
		$this->view->maxitems          = $pageSize;
		$this->view->pageNum           = $pageNum;
		$this->view->pageSize          = $pageSize;
	}

			
	public function infosAction()
	{		
	    $this->_helper->layout->setLayout("base");
		$view                         = &$this->view;
		$id                           = $entrepriseid = intval($this->_getParam("id" , $this->_getParam("entrepriseid", $this->_getParam("etablissementid", 0))));		
		$model                        = $this->getModel("entreprise");	
		$entreprise                   = $model->findRow( $entrepriseid, "entrepriseid", null, false);				
		 		
		if(!$entreprise ) {
			$this->setRedirect("Aucune information n'a été retrouvée pour l'entreprise dont vous souhaitez visualiser les informations");
			$this->redirect("public/index/index");
		}		
		$view->title           = "Les informations de l'entreprise";
		$view->entreprise      = $entreprise;
		$view->entrepriseid    = $entrepriseid;
		$view->offreEmplois    = array();
		$view->offreFormations = array();
		$view->documents       = $entreprise->documents();
        $view->localites       = $entreprise->localites();		
		$view->domaines        = $entreprise->domaines();
		$view->metiers         = $entreprise->metiers();
		$view->formations      = $entreprise->formations();
		$view->forme           = $entreprise->findParentRow("Table_Entrepriseformes");
		$view->groupe          = $entreprise->findParentRow("Table_Entreprisegroups");
		$view->columns         = $view->modules = array();
		$view->showLayoutTitle = true;
		$this->render("infos");
	}
	
	 
	 


	
}