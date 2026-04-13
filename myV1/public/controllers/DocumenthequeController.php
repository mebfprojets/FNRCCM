<?php

class DocumenthequeController extends Sirah_Controller_Default
{
	
	
	public function typesAction()
	{

		$this->view->title       = "Les documents officiels";
		$typeid                  = intval( $this->_getParam("typeid", $this->_getParam("id",0)));
		
		if(!intval($typeid)) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez consulter n'existe plus") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez consulter n'existe plus" , "error");
			$this->redirect("public/index/index");
		}
		
		$modelDocumentype        = new Model_Documentcategorie();
		$documentType            = $modelDocumentype->findRow($typeid,"id",null,false);
		if(!$documentType) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez consulter n'existe plus") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez consulter n'existe plus" , "error");
			$this->redirect("public/index/index");
		}
		$this->view->title       = sprintf("Documents officiels : %s", $documentType->libelle);
		$this->view->libelle     = $documentType->libelle;
		$this->view->description = $documentType->description;
	}
 
	
	public function infosAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout( true );
		
		$documentid             = intval( $this->_getParam("documentid", $this->_getParam("id" , 0 ) ) );
		$modelDocument          = $this->getModel("document");
		$document               = $modelDocument->findRow($documentid,"documentid",null,false);
		$me                     = Sirah_Fabric::getUser();
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger est invalide") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger est invalide" , "error");
			$this->redirect("public/documentheque/list");
		}
		if( $document->access > 0 && !$me->userid && $document->category != "7" &&  $document->category != "4" ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Vous ne pouvez pas télécharger ce document, vous devrez disposer d'un compte") );
				exit;
			}
			$this->setRedirect("Vous ne pouvez pas télécharger ce document, vous devrez disposer d'un compte" , "error");
			$this->redirect("public/index/index");
		}
		$filename                = $document->filepath;
		if( !file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document auquel vous souhaitez accéder, n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document auquel vous souhaitez accéder, n'existe plus sur le serveur" , "error");
			$this->redirect("public/index/index");
		}
		$documentExtension      = strtolower( Sirah_Filesystem::getFilextension( $filename ) );
		if( $documentExtension == "png" || $documentExtension == "jpeg" || $documentExtension == "jpg" ||
				$documentExtension == "gif" || $documentExtension == "bmp" ) {
			$imgFile  = str_replace( APPLICATION_PATH , ROOT_PATH . DS ."myV1"  ,  $filename );
			$imgFile  = str_replace( DS , "/" , $imgFile );
			echo  "<div class=\"row col-md-12\"> ";
			echo "<img src=\"".$imgFile."\" />";
			echo  "</div>";
			exit;
		}
		$this->_forward("download" , null , null , array("id" => $documentid ) );
	}
	
	public function formalitesAction()
	{
		$this->view->title   = "Historique des formalités  ";
	
		$model              = $this->getModel("document");
		$modelCategory      = $this->getModel("documentcategorie");
		$documents          = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		//On crée un validateur de filtre
		$strNotEmptyValidator    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params                  = $this->_request->getParams();
		$pageNum                 = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize                = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$filters                 = array("category" => 4, "access" => 0);
		$documents               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->documents   = $documents;
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		
		$this->render("formalites");
	}
	
	public function formulairesAction()
	{
		$this->view->title   = "Liste des formalités  ";
	
		$model              = $this->getModel("document");
		$modelCategory      = $this->getModel("documentcategorie");
		$documents          = array();
		$paginator          = null;
	
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		//On crée un validateur de filtre
		$strNotEmptyValidator    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params                  = $this->_request->getParams();
		$pageNum                 = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize                = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$filters                 = array("category" => 7, "access" => 0);
		$documents               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->documents   = $documents;
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
	
		$this->render("list");
	}
	

	public function listAction()
	{	
		$this->view->title   = "Liste des documents  ";
		
		$model              = $this->getModel("document");
		$modelCategory      = $this->getModel("documentcategorie");
		$documents          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultFilename      = $stringFilter->filter($this->_getParam("generalfilter", $this->_getParam("libelle", null )));
		$filters              = array("filename" => $defaultFilename, "filetype" => null, "category" => null, "access" => 0);
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$documents               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->documents   = $documents;
		$this->view->categories  = $modelCategory->getSelectListe("Selectionnez un dossier", array("id" , "libelle") );
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;			
	}
	
	
	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout( true );
	
		$documentid     = intval( $this->_getParam("documentid" , $this->_getParam("id" , 0 ) ) );
		$modelDocument  = $this->getModel("document");
		$document       = $modelDocument->findRow( $documentid , "documentid" , null , false );
	
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger est invalide") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger est invalide" , "error");
			$this->redirect("public/index/index");
		}
		$filename                = $document->filepath;
		if( !file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'existe plus sur le serveur" , "error");
			$this->redirect("public/index/index");
		}
		$documentExtension        = strtolower( Sirah_Filesystem::getFilextension( $filename ) );
		$contentType              = "application/octet-stream";
		switch( $documentExtension ) {
			case "doc" :
			case "docx":
				$contentType      = "application/msword";
				break;
			case "pdf" :
				$contentType      = "application/pdf";
				break;
			case "xls":
			case "xlsx":
				$contentType      = "application/excel";
				break;
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
			case "bmp":
				$contentType      = "image/*";
				break;
			default:
				$contentType      = "application/octet-stream";
		}
		header('Content-Description: File Transfer');
		header('Content-Type: '.$contentType );
		header('Content-Disposition: attachment; filename='.basename( $filename ) );
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize( $filename ) );
	
		if( $content = ob_get_clean() ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Des entetes HTTP ont déjà été transmises" ) );
				exit;
			}
			echo "Des entetes HTTP ont déjà été transmises";
			exit;
		}
		flush();
		@readfile( $filename );
		exit;
	}
	
}