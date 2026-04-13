<?php

class Admin_ProductcategoriesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Gestion des types de produits"  ;
		
		$model              = $this->getModel("productcategorie");
		$modelDocumentype   = $this->getModel("documentcategorie");
		$categories         = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;		
		
		$filters            = array("libelle"=>null,"documentcatid"=>null);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$categories            = $model->getList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns      = array("left");
		$this->view->categories   = $categories;
		$this->view->documentypes = $this->view->documentcategories = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"   , "libelle"), array(), null , null , false );
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $this->view->maxitems = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title        = "Nouvelle catégorie de produits ou services";
		
		$model                    = $this->getModel("productcategorie");
		$modelDocumentype         = $this->getModel("documentcategorie");
		$defaultData              = $model->getEmptyData();
		
		$this->view->documentypes = $documentypes = $this->view->documentcategories = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"     , "libelle") , array() , null , null , false );
		$errorMessages            = array();
		
		if( $this->_request->isPost() ) {
			$postData             = $this->_request->getPost();
			$emptyData            = $model->getEmptyData();
			$formData             = array_intersect_key($postData ,$emptyData);
			$insert_data          = array_merge($emptyData, $formData);
			$me                   = Sirah_Fabric::getUser();
			$modelTable           = $model->getTable();
			$dbAdapter            = $modelTable->getAdapter();
			$prefixName           = $modelTable->info("namePrefix");
			$tableName            = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter         =  new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator         = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$insert_data["libelle"]       = $libelle       = (isset($postData["libelle"]      ))?$stringFilter->filter($postData["libelle"])    : "";
			$insert_data["code"]          = $code          = (isset($postData["code"]         ))?$stringFilter->filter($postData["code"])    : "";
			$insert_data["documentcatid"] = $documentcatid = (isset($postData["documentcatid"]))?intval($postData["documentcatid"]) : 0;
			$insert_data["cout_ht"]       = (isset($postData["cout_ht"]    ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ht"] ))) : 0;			
		    $insert_data["cout_ttc"]      = (isset($postData["cout_ttc"]   ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ttc"]))) : 0;
			$insert_data["description"]   = (isset($postData["description"]))? $stringFilter->filter($postData["description"]) : "";
			$insert_data["image"]         = (isset($postData["image"]      ))? $stringFilter->filter($postData["image"])       : "";
 
			if(!$strNotEmptyValidator->isValid($insert_data["libelle"])) {
				$errorMessages[]          = " Veuillez entrer une désignation valide pour la catégorie";
			} elseif($model->findRow($libelle,"libelle", null , false )) {
				$errorMessages[]          = sprintf(" Une catégorie existant porte la même désignation %s " , $libelle );
		    } else {
				$insert_data["libelle"]   = $libelle;
			}						
			if(!$strNotEmptyValidator->isValid($insert_data["cout_ttc"]) ) {
				$insert_data["cout_ttc"]  = ( $insert_data["cout_ht"] + (( $insert_data["cout_ht"]*18) / 100) );
			} else {
				$insert_data["cout_ttc"]  = floatval($insert_data["cout_ttc"]);
			}			 
			$insert_data["creatorid"]     = $me->userid;
			$insert_data["creationdate"]  = time();		
            if(!$strNotEmptyValidator->isValid( $insert_data["code"] ) ) {
				$insert_data["code"]      = $model->createCode();
			}			
			if( empty($errorMessages)) {
				if( $dbAdapter->insert( $tableName, $insert_data) ) {
					$catid                = $dbAdapter->lastInsertId();			
                    
					$imageUpload          = new Zend_File_Transfer();
					$imageUpload->addValidator('Count',false,1);
					$imageUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
					$imageUpload->addValidator("FilesSize",false,array("max" => "10MB"));
					//On enregistre ensuite la photo si elle existe
					$searchIvalidStr      = array('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace              = array('e','a','i','u','o','n','y','c','-','','-');
					$basicFilename        = preg_replace( $searchIvalidStr, $replace, $imageUpload->getFileName("image",false));
					$tmpFilename          = Sirah_Filesystem::getName(         $basicFilename );
					$photoExtension       = Sirah_Filesystem::getFilextension( $basicFilename );
					$imgFileName          = time() . "_" . preg_replace("/\s/","" ,$basicFilename );
					$imageFilePath        = APPLICATION_DATA_PATH.DS."products". DS."images".DS."original" ;
					$photoFilepath        = $imageFilePath       .DS. $imgFileName;
					$imageUpload->addFilter("Rename", array("target" => $photoFilepath, "overwrite" => true), "image");
					//On upload l'photo de l'utilisateur
					if( $imageUpload->isUploaded("image")){
						$imageUpload->receive(   "image");
					}
					if( $imageUpload->isReceived("image")) {
						$photoImage       = Sirah_Filesystem_File::fabric("Image", $photoFilepath , "rb+");
						$photoImage->resize("350", null, true, APPLICATION_DATA_PATH. DS . "products". DS . "images". DS . "mini" );
						$photoImage->resize("150", null, true, APPLICATION_DATA_PATH. DS . "products". DS . "images". DS . "thumbs");
						$dbAdapter->update( $tableName, array("image" => $imgFileName ), "catid=".$catid)	;
					}
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "La catégorie de produits a été enregistrée avec succès"));
						exit;
					}
					$this->setRedirect("La catégorie de produits a été enregistrée avec succès", "success" );
					$this->redirect("admin/productcategories/infos/id/" . $catid );					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement de la catégorie de produits a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la catégorie de produits a echoué", "error");
					$this->redirect("admin/productcategories/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data      = $defaultData;	
	}
	
	
	public function editAction()
	{
		$this->view->title     = "Mettre à jour les informations d'une catégorie";
		
		$catid                 = intval($this->_getParam("catid", $this->_getParam("id" , 0)));
		
		if(!$catid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/productcategories/list");
		}		
		$model                    = $this->getModel("productcategorie");
 	    $modelDocumentype         = $this->getModel("documentcategorie");
		$categorie                = $model->findRow($catid , "catid" , null , false);		
		if(!$categorie) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/productcategories/list");
		}		
		$this->view->documentypes = $documentypes  = $modelDocumentype->getSelectListe("Selectionnez un type de document", array("id"   ,"libelle"), array(),null,null,false);
		$defaultData              = $categorie->toArray();
		$errorMessages            = array();  
		
		if( $this->_request->isPost()) {
			$postData             = $this->_request->getPost();
			$update_data          = array_merge( $defaultData , $postData);
			$me                   = Sirah_Fabric::getUser();
			$modelTable           = $model->getTable();
			$dbAdapter            = $modelTable->getAdapter();
			$prefixName           = $modelTable->info("namePrefix");
			$tableName            = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                 = new Zend_Filter();
			$stringFilter->addFilter(       new Zend_Filter_StringTrim());
			$stringFilter->addFilter(       new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator         = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$update_data["libelle"]       = $libelle       = (isset($postData["libelle"]      ))?$stringFilter->filter($postData["libelle"]) : $defaultData["libelle"];
			$update_data["code"]          = $code          = (isset($postData["code"]         ))?$stringFilter->filter($postData["code"])    : $defaultData["code"];
			$update_data["documentcatid"] = $documentcatid = (isset($postData["documentcatid"]))?intval($postData["documentcatid"]) : $defaultData["documentcatid"];
			$update_data["cout_ht"]       = (isset($postData["cout_ht"]    ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ht"] ))) : $defaultData["cout_ht"];			
		    $update_data["cout_ttc"]      = (isset($postData["cout_ttc"]   ))? floatval(preg_replace("/[^0-9\.,]/","", str_replace(",",".",$postData["cout_ttc"]))) : $defaultData["cout_ttc"];	
			$update_data["description"]   = (isset($postData["description"]))? $stringFilter->filter($postData["description"]) : $defaultData["description"];
 
			if(!$strNotEmptyValidator->isValid($update_data["libelle"])) {
				$errorMessages[]          = " Veuillez entrer une désignation valide pour le produit";
			} elseif($model->findRow($libelle,"libelle",null, false ) && strtolower($update_data["libelle"])!= strtolower($categorie->libelle)) {
				$errorMessages[]          = sprintf(" Un produit existant porte la même désignation %s " , $libelle );
		    } else {
				$update_data["libelle"]   = $libelle;
			}
            if(!$strNotEmptyValidator->isValid($update_data["code"])) {
				$errorMessages[]          = " Veuillez entrer un code valide pour la catégorie";
			} elseif($model->findRow($code,"code", null , false ) && strtolower($update_data["code"])!= strtolower($categorie->code)) {
				$errorMessages[]          = sprintf(" Une catégorie existante porte le code %s " , $code);
		    } else {
				$update_data["code"]      = $code;
			}			
			if(!floatval($update_data["cout_ttc"]) ) {
				$update_data["cout_ttc"]  = ( $update_data["cout_ht"] + (( $update_data["cout_ht"]*18) / 100) );
			} else {
				$update_data["cout_ttc"]  = floatval($update_data["cout_ttc"]);
			}
			$update_data["updateduserid"] = $me->userid;
			$update_data["updatedate"]    = time();	
			
			$categorie->setFromArray($update_data);				
			if( empty($errorMessages)) {
				if( $categorie->save()) {		
                    
					$imageUpload          = new Zend_File_Transfer();
					$imageUpload->addValidator('Count',false,1);
					$imageUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
					$imageUpload->addValidator("FilesSize",false,array("max" => "10MB"));
					//On enregistre ensuite la photo si elle existe
					$searchIvalidStr      = array('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace              = array('e','a','i','u','o','n','y','c','-','','-');
					$basicFilename        = preg_replace( $searchIvalidStr, $replace, $imageUpload->getFileName("image",false));
					$tmpFilename          = Sirah_Filesystem::getName(         $basicFilename );
					$photoExtension       = Sirah_Filesystem::getFilextension( $basicFilename );
					$imgFileName          = time() . "_" . preg_replace("/\s/","" ,$basicFilename );
					$imageFilePath        = APPLICATION_DATA_PATH . DS . "products" . DS . "images". DS . "original" ;
					$photoFilepath        = $imageFilePath . DS . $imgFileName;
					$imageUpload->addFilter("Rename", array("target" => $photoFilepath, "overwrite" => true), "image");
					//On upload l'photo de l'utilisateur
					if( $imageUpload->isUploaded("image")){
						$imageUpload->receive(   "image");
					}
					if( $imageUpload->isReceived("image")) {
						$photoImage       = Sirah_Filesystem_File::fabric("Image", $photoFilepath , "rb+");
						$photoImage->resize("350", null , true , APPLICATION_DATA_PATH. DS . "products". DS . "images". DS . "mini" );
						$photoImage->resize("150", null , true , APPLICATION_DATA_PATH. DS . "products". DS . "images". DS . "thumbs");
						$dbAdapter->update( $tableName, array("image" => $imgFileName ), "catid=".$catid)	;
					}
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations de la catégorie de produits ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations de la catégorie de produits ont été mises à jour avec succès", "success" );
					$this->redirect("admin/productcategories/infos/id/".$catid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => " Aucune modifiation n'a été apportée sur les informations de la catégorie de produits"));
						exit;
					}
					$this->setRedirect(" Aucune modifiation n'a été apportée sur les informations de la catégorie de produits" , "message");
					$this->redirect("admin/productcategories/infos/id/".$catid);
				}
			} else {
				$defaultData   = $update_data;
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}					
		}	
		$this->view->data    = $defaultData;
		$this->view->catid   = $catid;
	}	
 		
		
	public function infosAction()
	{
		$id                = $catid   = intval($this->_getParam("id", $this->_getParam("catid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/productcategories/list");
		}		
		$model          = $this->getModel("productcategorie");
		$categorie      = $model->findRow( $id , "catid" , null , false);		
		if(!$categorie) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de catégorie de produits n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de catégorie de produits n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/productcategories/list");
		}
		$this->view->categorie   = $categorie;
		$this->view->catid       = $catid;
		$this->view->documentype = $this->view->documentcategorie = $categorie->findParentRow("Table_Documentcategories");
		$this->view->title       = " Les informations d'une catégorie de produits ";
		$this->view->columns     = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$model                  = $this->getModel("productcategorie");
		$modelTable             = $model->getTable();
		$dbAdapter              = $modelTable->getAdapter();
		$prefixName             = $tablePrefix = $modelTable->info("namePrefix");
		$tableName              = $modelTable->info("name");
		$ids                    = $this->_getParam("catids", $this->_getParam("ids",array()));
		$errorMessages          = array();
		if( is_string($ids) ) {
			$ids                = explode("," , $ids );
		}
		$ids                    = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $id) {
				     if(!$dbAdapter->delete($tablePrefix."erccm_vente_products_categories",array("catid=?"=>$id))) {
						 $errorMessages[] = sprintf("La catégorie ref#%d n'a pas pu être supprimé",$id);
					 }	else {
						 $dbAdapter->update($tablePrefix."erccm_vente_products",array("catid"=>0),array("catid=?"=>$id));
					 }	
			}
		} else {
			             $errorMessages[] = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/productcategories/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les catégories de produits indiquées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les catégories de produits indiquées ont été supprimées avec succès", "success");
			$this->redirect("admin/productcategories/list");
		}
	}
}