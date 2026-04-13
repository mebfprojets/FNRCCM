<?php

class AjaxresController extends Sirah_Controller_Default
{
	
	public function entrepriseformesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		
		$model                 = $this->getModel("entrepriseforme");
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter          = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]     ))? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"] ))? intval($params["maxitems"]) : 100;		
		
		$filters              = array("libelle"=>null,"type"=>null,"typeid"=>1);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}	
        $formes                    = $model->getList($filters,$pageNum,$pageSize);	
        $selectJson                = array();
		if( count($formes) ) {
			$selectJson[]          = array("label"=>"Sélectionnez une forme juridique","value"=>0,"text"=>"Sélectionnez une forme juridique");
			foreach( $formes as $forme) {
				     $selectJson[] = array("label"=>$forme["libelle"],"value"=>$forme["formid"],"text"=>$forme["libelle"] );
			}
		} else {
			         $selectJson   = array("error"=>"Aucune forme juridique n'a été trouvée ");
		}
		echo ZendX_JQuery::encodeJson( $selectJson );
		exit;			
	}
	
	public function registresAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		$this->getResponse()->setHeader("Content-Type", "application/json");

		$model        = $this->getModel("registre");
		$stringFilter = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		$query        = $stringFilter->filter( $this->_getParam("q", null ) );
		$typeid       = intval($this->_getParam("type",  0));
		$totalResults = intval($this->_getParam("limit", NB_ELEMENTS_PAGE ));
		$rows         = $model->getList(array("searchQ"=>$query, "type"=> $typeid, "types" => array(1, 2)), 1, $totalResults);
		$jsonRows     = array( 0 => array("label"=>0, "value"=>"Aucun résultat n'a été trouvé avec ces mots clés..."));
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
	
	public function countrycitiesAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		
		$model            = $this->getModel("countrycity");			
		$country          = strip_tags( $this->_getParam("country", null ));
		 		
		$cities           = $model->getList(array("country"=>$country), 0, 0);
		$selectJson       = array();
		if( count($cities)) {
			$selectJson[] = array("label"=>"Sélectionnez une ville","value"=>0,"text"=>"Sélectionnez une ville");
			foreach( $cities as $city ) {
				     $selectJson[] = array("label"=> $city["city_name"],"value"=>$city["localiteid"],"text"=>$city["libelle"] );
			}
		}  else {
				     $selectJson   = array("error"=> "Aucune ville n'a été trouvée en rapport avec ce pays");
		}
		echo ZendX_JQuery::encodeJson( $selectJson );
		exit;
	}
	
	public function checkusernameAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		 
		$me                   = Sirah_Fabric::getUser();
		$userTable            = $me->getTable();
				 
		$resultats            = array();
		$errorMessages        = array();
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
		$username             = $stringFilter->filter($this->_getParam("username", $this->_getParam("code", null )));
		if(!$userTable->checkUsername( $username ) ) {
			echo ZendX_JQuery::encodeJson(array("error"   => "Ce nom d'utilisateur semble indisponible. Veuillez essayer un autre."));
			exit;
		} else {
			echo ZendX_JQuery::encodeJson(array("success" => "Ce nom d'utilisateur est disponible..."));
			exit;
		}
	}
	
	public function checkemailAction()
	{
		$this->_helper->viewRenderer->setNoRender( true );
		$this->_helper->layout->disableLayout( true );
		 
		$me                   = Sirah_Fabric::getUser();
		$userTable            = $me->getTable();
		$model                = $this->getModel("member");
				 
		$resultats            = array();
		$errorMessages        = array();
		$emailValidator       =  new Sirah_Validateur_Email();
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer", "zero", "string","float","empty_array","null"));
		$email                = $stringFilter->filter($this->_getParam("email", $this->_getParam("code", null )));
		if(!$emailValidator->isValid($email ) ) {
			echo ZendX_JQuery::encodeJson(array("error" => "Cette adresse email n'est pas valide. Veuillez saisir une adresse valide."));
			exit;
		}
		if(!$userTable->checkEmail($email) ||  ($foundMember = $model->findRow($email,"email",null,false))) {
			echo ZendX_JQuery::encodeJson(array("error" => "Cette adresse email semble indisponible. Veuillez essayer une autre."));
			exit;
		}
		 
		    echo ZendX_JQuery::encodeJson(array("success" => "Cette adresse email est disponible..."));
		    exit;
	}
	
	public function productsAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title  = " Gestion des articles "  ;
	
		$model              = $this->getModel("produit");
		$modelCategory      = $this->getModel("produitcategorie");
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
	
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
	
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]      ))? intval($params["page"])            : 1;
		$pageSize           = (isset($params["maxitems"]  ))? intval($params["maxitems"])        : NB_ELEMENTS_PAGE;
		$dataType           = (isset($params["typeOfData"]))? strtolower( $params["typeOfData"]) : "html";
	
		$filters            = array("libelle" => null, "catid" => null);
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		if( $dataType=="json" ) {
			$this->_helper->viewRenderer->setNoRender( true );
			$produits      = $model->getSelectListe("Selectionnez un produit", array("productid","libelle"), $filters, 0 , null , false );
			$selectJson    = array();
			if( count(   $produits  )) {
				foreach( $produits as $productid  => $produitlib ) {
					     $selectJson[] = array("label" => $produitlib, "value" => $productid, "text" => $produitlib );
				}
			}
			echo ZendX_JQuery::encodeJson( $selectJson );
			exit;
		}
		
		$produits              = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator             = $model->getListPaginator($filters);
	
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns        = array("left");
		$this->view->filters        = $filters;
		$this->view->paginator      = $paginator;
		$this->view->pageNum        = $pageNum;
		$this->view->pageSize       = $pageSize;
		$this->view->produits       = $produits;
		$this->view->categories     = $modelCategory->getSelectListe("Selectionnez un type", array("catid"   , "libelle") , array() , null , null , false );
		$this->view->parentform     = $this->_request->getParam("parentform" , "none");
	    $this->view->selectedKey    = $this->_request->getParam("selectedKey", "productid");
		$this->view->selectedCmdKey = $this->_request->getParam("selectedCmdKey", "selectProduit");
		$this->view->selectedVal    = $this->_request->getParam("selectedVal", "produit");
		$this->render("produits");
	}
	 
	
 

}