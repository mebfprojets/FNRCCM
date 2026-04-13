<?php

class Admin_CategoriesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Gestion des catégories de projets"  ;
		
		$model              = $this->getModel("projectcategory");		
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
		
		$filters            = array("libelle" => null, "code"=>null,"parentid"=>0);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$categories        = $model->getList( $filters , $pageNum , $pageSize);
		$paginator          = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns       = array("left");
		$this->view->categories    = $categories;
		$this->view->filters       = $filters;
		$this->view->paginator     = $paginator;
		$this->view->pageNum       = $pageNum;
		$this->view->pageSize      = $pageSize;			
	}	
		
	public function createAction()
	{
		$this->view->title     = "Nouvelle categorie de projets";
		
		$me                    = Sirah_Fabric::getUser();                 
		
		$model                 = $this->getModel("projectcategory");			
		$defaultData           = $model->getEmptyData();
 
		$errorMessages         = array();
		$parents               = $model->getSelectListe("Sélectionnez une catégorie parente",array("catid","libelle"), array(),null,null,false );
 
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$formData          = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data       = array_merge( $model->getEmptyData(), $formData);
			
			$userTable         = $model->getTable();
			$dbAdapter         = $me->getTable()->getAdapter();
			$prefixName        = $me->getTable()->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$insert_data["libelle"]            = $libelle = $stringFilter->filter($insert_data["libelle"]);
			$insert_data["code"]               = $stringFilter->filter($insert_data["code"]);
			$insert_data["parentid"]           = intval($insert_data["parentid"]);
			$insert_data["description"]        = $stringFilter->filter($insert_data["description"]);
			$insert_data["description"]        = $stringFilter->filter($insert_data["description"]);

			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]              = " Veuillez entrer une désignation valide pour la catégorie";
			} elseif( $model->findRow($insert_data["libelle"], "libelle" , null , false )) {
				$errorMessages[]              = sprintf("Une catégorie existant porte la même désignation %s , veuillez entrer une désignation différente ", $insert_data["libelle"]);
		    }  
			if(!$strNotEmptyValidator->isValid( $insert_data["code"])) {
				$errorMessages[]              = " Veuillez entrer un code valide pour catégorie";
			} elseif( $model->findRow($insert_data["code"], "code" , null , false )) {
				$errorMessages[]              = sprintf("Une catégorie existante porte le code %s , veuillez entrer un code différent", $insert_data["code"]);
		    }  
 
			$insert_data["creatorid"]         = $me->userid;
			$insert_data["creationdate"]      = time();	
			$insert_data["updatedate"]        = $insert_data["updateduserid"] = 0;	
            				
			if( empty($errorMessages)) {
				$emptyData                    = $model->getEmptyData();
				$clean_insert_data            = array_intersect_key( $insert_data, $emptyData);
				if( $dbAdapter->insert($userTable->info("name"), $clean_insert_data) ) {
					$catid                    = $dbAdapter->lastInsertId();	
 				    //On vide le cache
                    $modelCache               = $model->getMetadataCache();
					if( $modelCache ) {
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}
					$successMessage           = sprintf("La catégorie %s:%s a été enregistrée avec succès",$insert_data["code"], $insert_data["libelle"]);
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => $successMessage));
						exit;
					}
					$this->setRedirect($successMessage, "success" );
					$this->redirect("admin/categories/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement de la catégorie a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la catégorie a echoué" , "error");
					$this->redirect("admin/categories/list")	;
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
		$this->view->data             = $defaultData;
	}
	
	
	public function editAction()
	{
		$this->view->title    = " Mettre à jour les informations d'une catégorie";
		
		$catid         = intval($this->_getParam("catid", $this->_getParam("id" , 0)));
		
		if(!$catid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/categories/list");
		}		
		$model                    = $this->getModel("projectcategory");
		$categorie                = $model->findRow( $catid , "catid" , null , false);		
		if(!$categorie) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/categories/list");
		}		
		$defaultData         = $categorie->toArray();
		$errorMessages       = array();  
		$parents             = $this->view->parents = $model->getSelectListe("Sélectionnez une catégorie parente",array("catid","libelle"), array(),null,null,false );
		
		if( $this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$userTable       = $model->getTable();
			$dbAdapter       = $userTable->getAdapter();
			$prefixName      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$update_data["libelle"]      = $libelle = $stringFilter->filter($update_data["libelle"]);
			$update_data["code"]         = $stringFilter->filter($update_data["code"]);
			$update_data["parentid"]     = intval($update_data["parentid"]);
			$update_data["description"]  = $stringFilter->filter($update_data["description"]);			
			if(!$strNotEmptyValidator->isValid( $update_data["libelle"])) {
				$errorMessages[]         = " Veuillez entrer une désignation valide pour catégorie";
			} elseif( $model->findRow($update_data["libelle"], "libelle" , null , false ) && ( $categorie->libelle!=$update_data["libelle"])) {
				$errorMessages[]         = sprintf(" Une catégorie existant porte la même désignation %s , veuillez entrer une désignation différente ", $update_data["libelle"]);
		    } else {
				$update_data["libelle"]  = $libelle;
			}
			if( $model->findRow($update_data["code"], "code" , null , false ) && ( $categorie->code!=$update_data["code"])) {
				$errorMessages[]         = sprintf("Une catégorie existante porte le code %s , veuillez entrer un code différent", $update_data["code"]);
		    }  
			$update_data["updateduserid"]= $me->userid;
			$update_data["updatedate"]   = time();	
			$categorie->setFromArray( $update_data );				
			if( empty($errorMessages)) {
				if( $categorie->save()) {	
                    //On vide le cache
                    $modelCache          = $model->getMetadataCache();
					if( $modelCache ) {
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}					
					$successMessage      = sprintf("Les informations de la catégorie %s:%s ont été mises à jour avec succès",$update_data["code"], $update_data["libelle"]);
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = $successMessage;
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect($successMessage, "success" );
					$this->redirect("admin/categories/infos/id/".$catid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la catégorie"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la catégorie" , "message");
					$this->redirect("admin/categories/infos/id/".$catid);
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
		$this->view->data       = $defaultData;
		$this->view->catid      = $this->view->categoryid = $catid;
	}	
 		
		
	public function infosAction()
	{
		$id              = intval($this->_getParam("id", $this->_getParam("catid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/categories/list");
		}		
		$model      = $this->getModel("projectcategory");
		$categorie  = $model->findRow( $id , "catid" , null , false);		
		if(!$categorie) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune catégorie n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune catégorie n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/categories/list");
		}
		$this->view->categorie       = $categorie;
		$this->view->parent          = null;

		$this->view->title           = sprintf(" Les informations de la catégorie %s", $categorie->libelle );
		$this->view->columns         = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$me            = Sirah_Fabric::getUser();
		$model         = $this->getModel("projectcategory");
		$dbAdapter     = $model->getTable()->getAdapter();
		$tablePrefix   = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("catids", $this->_getParam("ids",array()));
		$errorMessages = $filtersCategories = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}

		$ids           = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $id) {
				     if( $dbAdapter->delete($tablePrefix. "sdr_projects_categories", array("catid=?"=>$id)) ) {
						 $dbAdapter->delete($tablePrefix. "sdr_projects_fiches"    , array("catid=?"=>$id));
					 } else {
						 $errorMessages[] = sprintf("La catégorie id %d n'a pas pu être supprimée", $id);
					 }						 
			}  
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/categories/list");
		} else {
			$modelCache = $model->getMetadataCache();
            if( $modelCache ) {
				$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			}				
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les catégories selectionnées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les catégories selectionnées ont été supprimées avec succès", "success");
			$this->redirect("admin/categories/list");
		}
	}
}