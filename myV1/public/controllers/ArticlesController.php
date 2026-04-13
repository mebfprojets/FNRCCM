<?php

class ArticlesController extends Sirah_Controller_Default
{
	
	public function init()
	{
		parent::init();
		//$this->view->headMeta()->
	}
	
	public function listAction()
	{
	    $this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$this->view->title             = "Historique des publications";
		$model                         = new Model_Article();
		$modelCategorie                = new Model_Articlecategorie();
		
		$items                         = array();
		$categorie                     = null;
		$paginator                     = null;
		$view                          = &$this->view;
		$me                            = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter                  =  new Zend_Filter();
		$stringFilter->addFilter(         new Zend_Filter_StringTrim());
		$stringFilter->addFilter(         new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params                        = $this->_request->getParams();
		$pageNum                       = (isset($params["page"]    ))? intval($params["page"])     : 1;
		$pageSize                      = (isset($params["maxitems"]))? intval($params["maxitems"]) : 10;	
 
		$searchQ                       = (isset($params["q"]       ))? $stringFilter->filter($params["q"]) : null;
		$filters                       = array("title"=>$searchQ,"code"=>null,"catid"=>0,"searchQ"=>$searchQ,"date_year"=>null,"date_month"=>null,"date_day"=>null,"keywords"=>null,"periode_start_day"=>0,"periode_start_month"=>0,"periode_start_year"=>0,"periode_end_day" =>0,"periode_end_month"=>0,"periode_end_year"=>0,"advanced"=>1);		
 
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     if( array_key_exists($filterKey, $filters)) {
						 $filters[$filterKey] =  $stringFilter->filter($filterValue);
					 }			     
			}
		}
		if( isset($filters["catid"]) && intval($filters["catid"])) {
			$categorie                 = $modelCategorie->findRow(intval($filters["catid"]),"catid",null,false);
			if( $categorie ) {
				if(!empty($categorie->keywords)) {
					$view->headMeta()->appendName("keywords",$categorie->keywords);
				} else {
					$view->headMeta()->appendName("keywords","fnrccm,erccm,fichier,national,burkina");
				}
				if(!empty($categorie->description)) {
					$view->headMeta()->appendName("description",$categorie->description);
				}  
				$this->view->title    = $categorie->title;
			}
		}
		
		$items                        = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                    = $model->getListPaginator($filters);	
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->items            = $items;
		$this->view->categorie        = $categorie;
 
		$this->view->pageSize         = $this->view->maxitems = $pageSize;	
		$this->view->filters          = $filters;
		$this->view->params           = $params;
		$this->view->paginator        = $paginator;
		 
	}
	
	
	public function imageAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  
		$view          = &$this->view;
		$imageid       = $id = intval($this->_getParam("imageid", $this->_getParam("photoid", $this->_getParam("id", 0))));
		if(!intval($imageid) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/articles/list");
		}
		$model        = new Model_Galleryphoto();
		$imageRow     = $model->findRow($imageid,"photoid",null,false);
		
		$filename     = $imageRow->filepath;
 
		if(!file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'existe plus sur le serveur" , "error");
			$this->redirect("public/dashboard/list");
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
	
	public function infosAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		}  
		$view          = &$this->view;
		$itemid        = $id = intval($this->_getParam("itemid", $this->_getParam("articleid", $this->_getParam("id", 0))));
		if(!$itemid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/articles/list");
		}
		$model                         = new Model_Article();
		$modelCategorie                = new Model_Articlecategorie();
		
		$itemRow        = $article     = $model->findRow($itemid,"articleid",null,false);
		if(!$itemRow ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("public/articles/list");
		}
		$this->view->article          = $this->view->item     = $itemRow;
		$this->view->articleid        = $this->view->itemid   = $itemid;
		$this->view->categorie        = $this->view->category = $categorie = $itemRow->findParentRow("Table_Articlecategories");
		$this->view->galleryid        = $galleryid            = $article->galleryid;
		$this->view->gallery          = $article->gallery();		
		$this->view->photos           = $article->photos();
		$this->view->videos           = $article->videos();
		$this->view->documents        = $article->documents();
		$this->view->videoSources     = $videoSources = array(0=>"Vidéo locale","youtube"=>"Vidéo Youtube","vimeo"=>"Vidéo Viméo","dailymotion"=>"Vidéo dailymotion");
		
		$this->view->title            = ($categorie)?sprintf("%s : %s",$categorie->title,$itemRow->title)  : sprintf("%s",$itemRow->title);
		if(!empty($itemRow->keywords)) {
			$view->headMeta()->appendName("keywords",$itemRow->keywords);
		} else {
			$view->headMeta()->appendName("keywords","fnrccm,erccm,fichier,national,burkina");
		}
		$metaDescription              = (!empty($itemRow->introtext))?substr(strip_tags($itemRow->introtext),0,100) : "";
		if(!empty($itemRow->content)) {
			$metaDescription          = $metaDescription." </br> ".substr(strip_tags($itemRow->content), 0,200);			
		} 
		$view->headMeta()->appendName("description",$metaDescription);
	}
	
	
	
	public function displayAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		}
		
		$this->view->title             = "Historique des publications";
		$model                         = new Model_Article();
		$modelCategorie                = new Model_Articlecategorie();
		
		$items                         = array();
		$categorie                     = null;
		$paginator                     = null;
		$view                          = &$this->view;
		$me                            = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter                  =  new Zend_Filter();
		$stringFilter->addFilter(         new Zend_Filter_StringTrim());
		$stringFilter->addFilter(         new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$view                          = &$this->view;
		$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params                        = $this->_request->getParams();
        $filters                       = array();
		$categorie                     = null;
		$viewLayout                    = "list";
		$categoryCode                  = "";
		if( isset($params["catid"]) && intval($params["catid"])) {
			$filters                   = array("catid"=>intval($params["catid"]));
			$articles                  = $model->getList($filters);
			$categorie                 = $modelCategorie->findRow(intval($params["catid"]),"catid",null,false);
			$categoryCode              = ($categorie)?$categorie->code : "";
		} elseif( isset($params["code"]) && !empty($params["code"]) ) {
			$categoryCode              = $stringFilter->filter($params["code"]);
			$filters                   = array("category_code"=>$categoryCode);
			$articles                  = $model->getList($filters);
			$categorie                 = $modelCategorie->findRow($categoryCode,"code",null,false);
		} else {
			$filters                   = array("category_code"=>"faq");
			$articles                  = $model->getList(array("category_code"=>"faq"));
			$categorie                 = $modelCategorie->findRow("faq","code",null,false);
		}
		if(!empty($categoryCode) && ($categoryCode=="faq")) {
			$viewLayout                = "faqlist";
		}
		if( count($articles)== 1) {
			$itemid                    = $articles[0]["articleid"];
			$itemRow        = $article = $model->findRow($itemid,"articleid",null,false);
			if(!$itemRow ) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
					exit;
				}
				$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
				$this->redirect("public/articles/list");
			}
			if(!$categorie) {
				$categorie                = $itemRow->findParentRow("Table_Articlecategories");
			}
			$this->view->article          = $this->view->item     = $itemRow;
			$this->view->articleid        = $this->view->itemid   = $itemid;
			$this->view->categorie        = $this->view->category = $categorie;
			$this->view->gallery          = $article->gallery();
			$this->view->galleryid        = $galleryid            = $article->galleryid;
			$this->view->photos           = $article->photos($itemid);
			$this->view->videos           = $article->videos($itemid);
		    $this->view->documents        = $article->documents($itemid);
			$this->view->title            = sprintf("%s",$itemRow->title);
			if(!empty($itemRow->keywords)) {
				$view->headMeta()->appendName("keywords",$itemRow->keywords);
			} else {
				$view->headMeta()->appendName("keywords","fnrccm,erccm,fichier,national,burkina");
			}
			$metaDescription              = (!empty($itemRow->introtext))?substr(strip_tags($itemRow->introtext),0,100) : "";
			if(!empty($itemRow->content)) {
				$metaDescription          = $metaDescription." </br> ".substr(strip_tags($itemRow->content), 0,200);			
			} 
			$view->headMeta()->appendName("description",$metaDescription);
			$this->render("article");
		} else {
			$paginator                    = $model->getListPaginator($filters);	
			if( null !== $paginator) {
				$paginator->setCurrentPageNumber(1);
				$paginator->setItemCountPerPage( 100);
			}
			$this->view->items            = $articles;
			$this->view->categorie        = "FAQ";
	 
			$this->view->pageSize         = $this->view->maxitems = 100;	
			$this->view->filters          = $filters;
			$this->view->params           = $params;
			$this->view->paginator        = $paginator;
			$this->view->title            = ($categorie)?sprintf("%s",$categorie->title)  : "Articles";
			$this->render($viewLayout);
		}
		 
	}

}