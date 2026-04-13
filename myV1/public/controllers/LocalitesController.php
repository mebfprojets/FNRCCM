<?php

class LocalitesController extends Sirah_Controller_Default
{
		
	public function listAction()
	{
		$this->view->title  = " Liste des localités ciblées"  ;
		
		$model              = $this->getModel("localite");
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags() );
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 100;
		
		$filters            = array("libelle" => null);
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter( $filterValue );
			}
		}
		$localites              = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator            = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $pageSize;
		$this->view->localites   = $localites;
	}
	
	
	
	public function infosAction()
	{		
		$model          = $this->getModel("localite" );	
		$modelPhysique  = $this->getModel("registrephysique");
		$modelMoral     = $this->getModel("registremorale");
		$id             = intval($this->_getParam("id", $this->_getParam("localiteid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/localites/list");
		}
		$localite          = $model->findRow( $id , "localiteid" , null , false);
		$registrePhysiques = $modelPhysique->getList(array("localiteid" => $id ) );
		$registreMorales   = $modelMoral->getList(array("localiteid" => $id ) );
		if(!$localite) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune localité n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée localité n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("public/localites/list");
		}								
		 
		$this->view->localite   = $localite;
		$this->view->physiques  = $registrePhysiques;
		$this->view->morales    = $registreMorales;
		$this->view->title      = sprintf("Les informations de la localité %s", $registre->libelle );
	}
	
}