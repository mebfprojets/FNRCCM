<?php

class DomainesController extends Sirah_Controller_Default
{
  		
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$view->title        = "Les secteurs d'activités"  ;
		
		$model              = $this->getModel("domaine");
	
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                = $this->_request->getParams();
			
		$libelle               = strip_tags($this->_getParam("libelle", null ))	;	
		$filters               = array("libelle" => $libelle );
		if( empty( $libelle) ) {
			$filters["parentid"] = 0;
			$filters["libelle"]  = "";
		}
		$view->columns     = array("left");
		$view->domaines    = $model->getList( $filters );
		$view->model       = $model;
		$view->filters     = $filters;
		$view->paginator   = $paginator;
		$view->pageNum     = $pageNum;
		$view->pageSize    = $pageSize;			
	}
	
	 
 		
		
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base");
		$view            = &$this->view;
		$id              = intval($this->_getParam("id", $this->_getParam("domaineid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/domaines/list");
		}		
		$model           = $this->getModel("domaine");
		$domaine         = $model->findRow( $id , "domaineid" , null , false);		
		if(!$domaine) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=> " Aucune entrée de domaine  n'a été retrouvée avec cet identifiant "));
				exit;
			}
			$this->setRedirect(" Aucune entrée de secteur d'activités n'a été retrouvée avec cet identifiant " , "error");
			$this->redirect("public/domaines/list");
		}
		$view->domaine         = $domaine;
		$view->parent          = ( $domaine->parentid ) ? $model->findRow( $domaine->parentid , "domaineid", null , false ) : null;
		$view->formations      = array();
		$view->metiers         = array();
		$view->etablissements  = array();
		$view->title           = " Les informations d'un secteur d'activités";
		$view->columns         = $view->modules = array();
		$view->showLayoutTitle = true;	
	} 	
	
	 
}