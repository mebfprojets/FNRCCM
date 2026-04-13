<?php

class Admin_GalleriesController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{				
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title  = "Galeries Photos"  ;
		
		
		$model              = $this->getModel("gallery");
		$modelArticle       = $this->getModel("article");
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		$params                            = $this->_request->getParams();
		$pageNum                           = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize                          = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		
		$defaultFilter                     = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters                           = array("libelle" => $defaultFilter, "articleid"=> 0 );
		if(!empty($params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter( $filterValue );
			}
		}
		$galleries                         = $model->getList( $filters, $pageNum , $pageSize  );
		$paginator                         = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum );
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns               = array("left");
		$this->view->filters               = $filters;
		$this->view->paginator             = $paginator;
		$this->view->pageNum               = $pageNum;
		$this->view->pageSize              = $pageSize;
		$this->view->galleries             = $galleries;
        $this->view->articles              = $modelArticle->getSelectListe("Selectionnez un article", array("articleid","libelle"), array(), 0 , null , false);		
	}
	
	public function createAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title                = " Nouvelle galerie photos";
		
		$model                            = $this->getModel("gallery");
		$modelArticle                     = $this->getModel("article");
		$defaultData                      = $model->getEmptyData();
		$errorMessages                    = array();
		
		$this->view->articles             = $articles = $modelArticle->getSelectListe("Selectionnez un article", array("articleid","libelle"), array(), 0 , null , false);
		
		if( $this->_request->isPost() ) {
			$postData                     = $this->_request->getPost();
			$emptyData                    = $model->getEmptyData();
			$formData                     = array_intersect_key($postData ,$emptyData);
			$insert_data                  = array_merge($emptyData, $formData);
			$me                           = Sirah_Fabric::getUser();
			$modelTable                   = $model->getTable();
			$dbAdapter                    = $modelTable->getAdapter();
			$prefixName                   = $modelTable->info("namePrefix");
		
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                 = new Zend_Filter();
			$stringFilter->addFilter(       new Zend_Filter_StringTrim());
			$stringFilter->addFilter(       new Zend_Filter_StripTags());
		
			//On crée les validateurs nécessaires
			$strNotEmptyValidator         = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		
			$insert_data["libelle"]       = $libelle   = (isset( $postData["libelle"]))?$stringFilter->filter($postData["libelle"]) : "";
			$insert_data["articleid"]     = $articleid = (intval($postData["articleid"]) && isset($articles[$postData["articleid"]]))? intval($postData["articleid"]) : 0;
			if(!$strNotEmptyValidator->isValid($insert_data["libelle"]) ) {
				$insert_data["libelle"]   = sprintf("Galerie photos de l'article %s",$articles[$postData["articleid"]]);
			}
			if( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]          = sprintf("Une gallerie photos existante porte le même nom %s " , $libelle );
			} else {
				$insert_data["libelle"]   = $libelle;
			}
			if(!intval($insert_data["articleid"])) {
				$errorMessages[]          = "Veuillez sélectionner l'article qui est associée cette galerie photos";
			}
			$insert_data["filepath"]      = APPLICATION_DATA_PATH. DS ."articles".DS. "galleries".DS."original" ;
			$insert_data["description"]   = $stringFilter->filter($insert_data["description"]);
			$insert_data["creatorid"]     = $me->userid;
			$insert_data["creationdate"]  = time();
			$insert_data["updatedate"]    = 0;
			$insert_data["updateduserid"] = 0;
			if( empty($errorMessages)) {
				    $dbAdapter->delete($prefixName."erccm_crm_content_gallery",array("articleid=?"=>intval($articleid) ));
				if( $dbAdapter->insert($prefixName."erccm_crm_content_gallery", $insert_data) ) {
					$galleryid            = $dbAdapter->lastInsertId();
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"=>"La gallerie photos a été enregistrée avec succès"));
						exit;
					}
					$this->setRedirect("La gallerie photos a été enregistrée avec succès", "success" );
					$this->redirect("admin/galleries/infos/galleryid/".$galleryid );
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement de la gallerie photos a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la gallerie photos a echoué", "error");
					$this->redirect("admin/galleries/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data       = $defaultData;
	}
	
	
	public function editAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title      = " Mettre à jour les informations d'une gallerie photos";
	
		$galleryid              = intval($this->_getParam("galleryid", $this->_getParam("id" , 0)));
	
		if(!$galleryid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/galleries/list");
		}
		$model          = $this->getModel("gallery");
	    $modelArticle   = $this->getModel("article");
		$gallerie       = $model->findRow( $galleryid , "galleryid" , null , false);
			
		if(!$gallerie) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/galleries/list");
		}	
		$defaultData         = $gallerie->toArray();
		$errorMessages       = array();
	
		if( $this->_request->isPost() ) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$modelTable      = $model->getTable();
			$dbAdapter       = $modelTable->getAdapter();
			$prefixName      = $modelTable->info("namePrefix");
	
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    =        new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags());
	
			//On crée les validateurs nécessaires
			$strNotEmptyValidator       = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
	
			$update_data["libelle"]     = $libelle   = (isset( $postData["libelle"] ))?$stringFilter->filter($postData["libelle"]) : $gallerie->libelle;
	        $update_data["articleid"]   = $articleid = (intval($postData["articleid"]) && isset($articles[$postData["articleid"]]))? intval($postData["articleid"]) : $gallerie->articleid;
			if(!$strNotEmptyValidator->isValid($update_data["libelle"])) {
				$errorMessages[]        = " Veuillez entrer une désignation de la gallerie photos";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ( $gallerie->libelle != $libelle ) ) {
				$errorMessages[]        = sprintf("Une gallerie existant porte la désignation %s , veuillez entrer une désignation différente " , $libelle );
			} else {
				$update_data["libelle"] = $libelle;
			}		
            if(!intval( $articleid )) {
				$errorMessages[]        = "Veuillez sélectionner l'article associée à cette galerie photos";
			}				
			$update_data["description"] = $stringFilter->filter($update_data["description"]);
			$update_data["updateduserid"]          = $me->userid;
			$update_data["updatedate"]             = time();
			$gallerie->setFromArray($update_data);
			if(empty($errorMessages)) {
				if( $gallerie->save() )       {
					$gallerie           = $model->findRow($galleryid , "galleryid" , null , false);
	
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations de la galerie ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations de la galerie ont été mises à jour avec succès", "success" );
					$this->redirect("admin/galleries/infos/id/".$galleryid);	
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => " Aucune modifiation n'a été apportée sur les informations de la gallerie photos"));
						exit;
					}
					$this->setRedirect(" Aucune modifiation n'a été apportée sur les informations de la gallerie photos" , "message");
					$this->redirect("admin/galleries/infos/id/".$galleryid );
				}
			} else {
				$defaultData   = $update_data;
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data       = $defaultData;
	}		
		

	public function infosAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$id              = $galleryid = intval($this->_getParam("id", $this->_getParam("galleryid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/galleries/list");
		}
		$model         = $this->getModel("gallery");
		$modelPhoto    = $this->getModel("galleryphoto");
		$gallerie      = $model->findRow($galleryid, "galleryid" , null , false);
		if( !$gallerie ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de gallerie photos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de gallerie photos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/galleries/list");
		}
		$this->view->gallerie   = $gallerie;
		$this->view->photos     = $gallerie->photos();
		$this->view->videos     = $gallerie->videos();
		$this->view->title      = "Les informations d'une gallerie photos ";
		$this->view->columns    = array("left");
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$me            = Sirah_Fabric::getUser();
		$model         = $this->getModel( "gallerie");		
		$modelTable    = $model->getTable();
		$dbAdapter     = $modelTable->getAdapter();
		$prefixName    = $modelTable->info("namePrefix");
		$ids           = $this->_getParam("galleryids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}
		$ids           = (array)$ids;
		if( count(    $ids )) {
			foreach($ids as $id) {
					$galleryid = intval( $id );
					$gallerie  = $model->findRow( $galleryid, "galleryid" , null , false );
					if( $gallerie ) {
						if(!$gallerie->delete()) {
							$errorMessages[]  = " Erreur de la base de donnée La gallerie photos id#$id n'a pas été supprimée ";
						} else {
							$dbAdapter->delete( $prefixName."erccm_crm_content_gallery_photos", "galleryid=".$galleryid);
							$dbAdapter->delete( $prefixName."erccm_crm_content_gallery_videos", "galleryid=".$galleryid);
						}
					} else {
						$errorMessages[]      = "Aucune entrée valide n'a été trouvée pour la gallerie photos id #$id ";
					}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}
		if(count( $errorMessages)) {
			if(   $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			         $this->redirect("admin/galleries/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" =>"Les galleries photos selectionnés ont été supprimées avec succès"));
				exit;
			}
			    $this->setRedirect("Les galleries photos selectionnés ont été supprimées avec succès", "success");
			    $this->redirect("admin/galleries/list");
		}
	}
	
	
	public function addphotosAction()
	{
		$this->view->title       = "Enregistrer une photo dans la gallerie";
		$errorMessages           = array();
		$galleryid               = intval($this->_getParam("galleryid" , $this->_getParam("id", 0)));
		$articleid               = intval($this->_getParam("articleid", 0));
			
		$modelArticle           = $this->getModel("article");
		$modelGallery            = $model = $this->getModel("gallery");
		$modelPhoto              = $this->getModel("galleryphoto");
		$me                      = Sirah_Fabric::getUser();
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$gallery_filepath        = APPLICATION_DATA_PATH. DS. "articles". DS. "galleries". DS . "original" ;
			
		$gallery                 = $model->findRow(intval($galleryid), "galleryid", null , false );
		if(!$gallery) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune galerie photos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune galerie photos n'a été retrouvée avec cet identifiant", "error");
			$this->redirect("admin/galleries/list");
		} else {
			if(!intval($articleid) ) {
				$articleid          = $gallery->articleid;
			}
		}
		$article                     = (intval($articleid))?$modelArticle->findRow(intval($articleid),"articleid", null , false) : null;
		if(!$article) {
			$articleid               = $article->articleid;
		}
		$photosToken                 = $this->_getParam("photostoken", $this->_getParam("token", null));
		$defaultData                 = $modelPhoto->getEmptyData();
		$defaultData["articleid"]    = $articleid;
		$defaultData["galleryid"]    = $galleryid;
		$errorMessages               = $uploadedPhotos = array();
		if( empty($photosToken) ) {
			$photosToken             = Sirah_User_Helper::getToken(4);			
		}
		if( $this->_request->isPost() ) {
	        $postData                = $this->_request->getPost();
			$capturedPhotos          = (isset($_FILES[$photosToken]))?$_FILES[$photosToken]  : array();
			$galleryPhotos           = $gallery->photos();
			$galleryCode             = $article->code;
			
			if( empty($capturedPhotos)  || !count($capturedPhotos)) {
				$errorMessages[]     = "Aucune photo n'a été selectionnée pour cette galerie";
			} else {
				$photoKey            = 0;
				$photoId             = count($galleryPhotos) + 1;
				foreach( $capturedPhotos as  $capturedPhoto ) {
					     $capturedPhotoFilePath = $capturedPhoto["tmp_name"];
						 $capturedPhotoFileName = (isset($postData["photolibelle_".$photoKey]))? $postData["photolibelle_".$photoKey] : $capturedPhoto["name"];
						 $capturedPhotoFileDesc = (isset($postData["photodesc_".$photoKey]   ))? $postData["photodesc_".$photoKey]    : $capturedPhotoFileName;
						 $capturedPhotoFileExt  = (file_exists($capturedPhotoFilePath))?Sirah_Filesystem::getFilextension($capturedPhotoFilePath) : "";
						 $capturedPhotoNewName  = (!empty($capturedPhotoFileExt))?sprintf("Activ%s_Photo%03d.%s", $galleryCode, $photoId, $capturedPhotoFileExt) :  preg_replace("/[^a-zA-Z0-9]/", "", $capturedPhotoFileName);
						 
						 if( file_exists( $capturedPhotoFilePath ) ) {
							 $galleryPhotoData                 = array("articleid"=>$articleid,"galleryid"=>$galleryid,"libelle"=>$capturedPhotoFileName,"description"=>$capturedPhotoFileDesc);
						     $galleryPhotoData["creationdate"] = time();
							 $galleryPhotoData["creatorid"]    = $me->userid;
							 $galleryPhotoData["filepath"]     = $gallery_filepath. DS . $capturedPhotoNewName;
							 if( $dbAdapter->insert( $prefixName."`erccm_crm_content_gallery_photos", $galleryPhotoData)) {
								 $photoId                      = $dbAdapter->lastInsertId();
								 $uploadedPhotos[$photoId]     = $galleryPhotoData;
							 } else {
								 $errorMessages[]              = sprintf("La photo %s n'a pas pu être transférée", $capturedPhotoNewName);
							 }
						 }
						 $photoKey++;
						 $photoId++;
				}
			}
            if( count( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message , "error") ;
				}
			} else {
				$successMessage      = sprintf("Votre opération de transfert de photos s'est effectuée avec succès. Au total %d photos ont pu être copiées", count($uploadedPhotos));
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonData        = array("photos"=>$uploadedPhotos,"success"=>$successMessage);
					echo ZendX_JQuery::encodeJson($jsonData);
					exit;
				}
				$this->setRedirect($successMessage,"success");
				$this->redirect("admin/galleries/infos/id/".$galleryid);
			}				
		}
		$this->view->token           = $this->view->photosToken = $photosToken;
		$this->view->galleryid       = $galleryid;
		$this->view->articleid      = $articleid;
		$this->view->article        = $article;
		$this->view->data            = $defaultData;
		$this->render("photoupload");
	}
	
	public function delphotosAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$galleryid                   = intval($this->_getParam("galleryid", $this->_getParam("id", 0)));
		$photoids                    = $this->_getParam("photoids"  , $this->_getParam("ids", array()));
		$errorMessages               = array();
		if( is_string( $photoids )) {
			$photoids                = explode("," , $photoids);
		}
		$photoids                    = (array)$photoids;
		$model                       = $this->getModel("gallery");
		$modelTable                  = $model->getTable();
		$dbAdapter                   = $modelTable->getAdapter();
		$prefixName                  = $modelTable->info("namePrefix");
		if( count(   $photoids) ) {
			foreach( $photoids as $photoid ) {
				     if(!$dbAdapter->delete( $prefixName."erccm_crm_content_gallery_photos", array("galleryid=?"=>$galleryid,"photoid=?"=>intval($photoid)))) {
						 $errorMessages[] = "L'opération de suppression de la photo que vous avez selectionnée a echoué";
					 }
			}
		}
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
		} else {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("success"=> "Les photos selectionnées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les photos selectionnées ont été supprimées avec succès", "success");
			$this->redirect("admin/galleries/infos/galleryid/".$galleryid);
		}
	}
}