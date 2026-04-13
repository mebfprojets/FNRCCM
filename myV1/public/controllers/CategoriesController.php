<?php

class CategoriesController extends Sirah_Controller_Default
{
		
	public function listAction()
	{
		$this->view->title  = " Liste des types d'activités "  ;
		
		$model              = $this->getModel("categorie");
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 50;
		
		$filters            = array("libelle" => null);
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter( $filterValue );
			}
		}
		$categories              = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator               = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns     = array("left");
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $pageSize;
		$this->view->categories  = $categories;
	}
	
	
	
	public function infosAction()
	{
		$this->view->title = " Informations du type d'activités et les activités relatives";
		
		$model             = $this->getModel("categorie" );
		$modelActivite     = $this->getModel("activite");
		
		$id                = intval($this->_getParam("id", $this->_getParam("catid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/categories/list");
		}
		$categorie      = $model->findRow( $id , "catid" , null , false);
		$activites      = $modelActivite->getList(          array("category" => $id ), 1 , 20);
		$paginator      = $modelActivite->getListPaginator( array("category" => $id ) );
		if(!$categorie) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de type d'activité n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de type d'activité n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("public/categories/list");
		}								
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber( 1 );
			$paginator->setItemCountPerPage(  20 );
		}
		$this->view->categorie  = $categorie;
		$this->view->activites  = $activites;
		$this->view->paginator  = $paginator;
	}
	
}