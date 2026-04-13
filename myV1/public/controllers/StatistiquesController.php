<?php

class  StatistiquesController extends Sirah_Controller_Default
{
	public function listAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title        = "Données statistiques des RCCM produits par juridiction du pays";
		$view                     = &$this->view;
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		
		$params                   = $this->_request->getParams();
		$filters                  = array("localiteid" => 0, "annee" => 0 );
		$rows                     = array();
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$view->headMeta()->appendName("keywords","fnrccm,erccm,fichier,national,burkina,faso,FNRCCM,données,statistique,statistiques,observatoire");		
		$view->headMeta()->appendName("description","Données statistiques des RCCM produits par juridiction du pays");
 
		$view->isController = "stat-localites";
		$view->filters      = $filters;
		$view->model        = $model;
		$view->localites    = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$view->annees       = $annees = $model->getStatYears( );
		
		if(!intval( $filters["localiteid"] )) {
			$this->render("list");
		} else {
			$this->render("localite");
		}
	}
	
	public function localitesAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title        = "Données statistiques des RCCM produits par localité";
		$view                     = &$this->view;
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		
		$params                   = $this->_request->getParams();
		$filters                  = array("localiteid" => 0, "annee" => 0 );
		$rows                     = array();
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
						
		$this->view->isController = "stat-localites";
		$this->view->filters      = $filters;
		$this->view->model        = $model;
		$this->view->localites    = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->annees       = $annees = $model->getStatYears();
		$view->headMeta()->appendName("keywords","fnrccm,erccm,fichier,national,burkina,faso,FNRCCM,données,statistique,statistiques,observatoire");		
		$view->headMeta()->appendName("description","Données statistiques des RCCM produits par localité");
		if(!intval( $filters["localiteid"] )) {
			$this->render("localites");
		} else {
			$this->render("localite");
		}
	}
	
	public function domainesAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title        = "Statistiques des registres par secteur d'activités";
	    $model                    = $this->getModel("registre");
		$modelDomaine             = $this->getModel("domaine");
		
		$params                   = $this->_request->getParams();
		$filters                  = array("domaineid" => 0, "annee" => 0 );
		$rows                     = array();
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
						
		$this->view->isController = "stat-domaines";
		$this->view->filters      = $filters;
		$this->view->model        = $model;
		$this->view->domaines     = $modelDomaine->getSelectListe( "Selectionnez un domaine"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->annees       = $annees = $model->getStatYears( );
		
		if(!intval( $filters["domaineid"] )) {
			$this->render("domaines");
		} else {
			$this->render("domaine");
		}
	}
	
	public function sexeAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title        = "Statistiques des registres par sexe";
	
		$model                    = $this->getModel("registre");
		$modelLocalite            = $this->getModel("localite");
		
		$this->view->isController = "stat-sexes";
		$this->view->model        = $model;
		$this->view->localites    = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
	
		$this->render("sexes");
	}
			
	
}