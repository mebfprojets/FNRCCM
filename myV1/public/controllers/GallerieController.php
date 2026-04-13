<?php

class Public_GallerieController extends Sirah_Controller_Default
{
	
	public function indexAction()
	{
	   
		$this->view->title   = " Bienvenue sur l'interface d'administration";
		$this->_helper->viewRenderer->setNoRender( true );
		
		echo "Juste un tests";
	}
	
	public function listAction()
	{
	
		$this->view->title   = " Gallerie Photos";
		$this->_helper->viewRenderer->setNoRender( true );
		
		$type  = strip_tags( $this->_request->getParam("type", "photos"))	;

		if( $type == "photos" ) {
			$this->_forward("photos");
		} elseif( $type == "videos" ) {
			$this->_forward("videos");
		} else {
			echo "Impossible d'afficher la gallerie, des paramètres requis sont ommis";
		}	
	}
	
	public function photosAction()
	{
		$this->view->title  = "Gallerie photos "  ;
		
		$model              = $this->getModel("gallerie");
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset( $params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset( $params["maxitems"])) ? intval($params["maxitems"]) : 10;
		
		$filters            = array("libelle" => null, "activityid" => 0 );
		if(!empty($params)) {
			foreach( $params as $filterKey => $filterValue ){
				     $filters[$filterKey]  =  $stringFilter->filter( $filterValue );
			}
		}
		$galleries                         = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator                         = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->headMeta()->appendName("description", "Gallerie photos et vidéos du projet OSRO/BKF/203/SWI");
		$this->view->headMeta()->appendName("keywords", "pfnl,burkina,faso,ouagadougou,fao,Fao,FAO,projet,osro,bkf203,BKF,203,swiss,suisse,BF,SWI,swi,PFNL,agriculture,ongone,obame,food,organization,united,nation" );
		$this->view->columns     = array("left");
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $pageSize;
		$this->view->galleries   = $galleries;
	}
	
	public function videosAction()
	{
		$this->_helper->layout->setLayout("gallerie");
		$this->view->title  = "Gallerie Vidéos "  ;
	
		$model              = $this->getModel("video");
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
	
		$filters            = array("libelle" => null, "activityid" => 0 );
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter( $filterValue );
			}
		}
		$videos                  = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator               = $model->getListPaginator( $filters );
	
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum );
			$paginator->setItemCountPerPage(  $pageSize);
		}
		
		$this->view->headMeta()->appendName("description", "Gallerie vidéos du projet OSRO/BKF/203/SWI");
		$this->view->headMeta()->appendName("keywords"   , "pfnl,burkina,faso,ouagadougou,fao,Fao,FAO,projet,osro,bkf203,BKF,203,swiss,suisse,BF,SWI,swi,PFNL,agriculture,ongone,obame,food,organization,united,nation" );
		
		$this->view->columns     = array("left");
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $pageSize;
		$this->view->videos      = $videos;
		$requestUri              = $this->getRequest()->getRequestUri();
		$this->view->hostName    = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . substr_replace( $requestUri, "", strpos($requestUri, "videos/")) ;		
	}
	
	public function infosAction()
	{	
		$this->view->title       = " Gallerie photos et vidéos";
		
		$type                    = strip_tags( $this->_request->getParam("type", "photos"))	;
	    $id                      = intval($this->_getParam("id", $this->_getParam("galleryid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/gallerie/list/type/photos");
		}
		$modelGallerie           = $this->getModel("gallerie");		
		$gallerie                = $modelGallerie->findRow( $id , "galleryid" , null , false);
		$gallerieView            = "photos-list";
		
		if( !$gallerie ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de gallerie photos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de gallerie photos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("public/gallerie/list/type/photos");
		}
		switch( strtolower($type ) ) {
			    case "photos":
			    default:
			    	$model             = $this->getModel("photo");
			    	$gallerieView      = "photos-list";
			    	$this->view->title = ( $gallerie ) ? "Gallerie Photos intitulée <small> ".$gallerie->libelle."</small>" : "Gallerie Photos";
			    	break;
			    case "videos":
			    	$model             = $this->getModel("video");
			    	$gallerieView      = "videos-list";
			    	$this->view->title = ( $gallerie ) ? "Gallerie Photos intitulée <small> ".$gallerie->libelle."</small>" : "Gallerie Photos";
			    	break;
		}
		$this->view->headMeta()->appendName("description", $this->view->title);
		$this->view->headMeta()->appendName("keywords", "pfnl,burkina,faso,ouagadougou,fao,Fao,FAO,projet,osro,bkf203,BKF,203,swiss,suisse,BF,SWI,swi,PFNL,agriculture,ongone,obame,food,organization,united,nation,gallerie,photos,videos" );
		
		$this->_helper->layout->setLayout("gallerie");	
		$this->view->gallerie     = $gallerie;
		$this->view->columns      = array();
		$this->view->rows         = $this->view->photos = $model->getList(array("galleryid" => $id ));
		
		$this->render( $gallerieView );			
	}	

}