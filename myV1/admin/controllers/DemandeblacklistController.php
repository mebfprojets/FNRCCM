<?php

class Admin_DemandeblacklistController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax = true;
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title      = "Gestion des noms commerciaux ou mots clés réservés"  ;
		
		$model                  = $this->getModel("demandeblacklist");		
		$items             = array();
		$paginator              = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter           =  new Zend_Filter();
		$stringFilter->addFilter(  new Zend_Filter_StringTrim());
		$stringFilter->addFilter(  new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator   =  new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params                 = $this->_request->getParams();
		$pageNum                = (isset($params["page"]))    ? intval($params["page"])        : 1;
		$pageSize               = (isset($params["maxitems"]))? intval($params["maxitems"])    : NB_ELEMENTS_PAGE;		
		$searchQ                = (isset($params["searchq"] ))? strip_tags($params["searchq"]) : null;
		$filters                = array("libelle" => $searchQ);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$items                  = $model->getList( $filters , $pageNum , $pageSize);
		$paginator              = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns    = array("left");
		$this->view->items      = $this->view->keywords = $items;
		$this->view->filters    = $filters;
		$this->view->paginator  = $paginator;
		$this->view->pageNum    = $pageNum;
		$this->view->pageSize   = $pageSize;			
	}	
		
	public function createAction()
	{
		$this->view->title      = "Ajouter des mots clés ou des noms commerciaux réservés";
		
		$me                     = Sirah_Fabric::getUser();                 
		
		$model                  = $this->getModel("demandeblacklist");			
		$defaultData            = $model->getEmptyData();
 
		$errorMessages          = $items  = array();
		$itemSaved              = 0;
 
		if( $this->_request->isPost() ) {
			$postData           = $this->_request->getPost();
			
			$modelTable         = $model->getTable();
			$dbAdapter          = $modelTable->getAdapter();
			$prefixName         = $modelTable->info("namePrefix");
			
			$defaultData        = $model->getEmptyData();
			$formData           = array_intersect_key($postData, $defaultData);
			$insert_data        = array_merge($defaultData, $formData);
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       =    new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$keywords           = array();
			if( isset($postData["keywords"]) && is_string($postData["keywords"])) {
				$keywords       = preg_split("/[;,]+/", $postData["keywords"]);
			} elseif( isset($postData["keywords"]) && is_array($postData["keywords"])) {
				$keywords       = $postData["keywords"];
			}			
			if( is_array($keywords) && count( $keywords )) {
				foreach( $keywords as $keyword ) {
					     $cleanKeyword                    = $stringFilter->filter($keyword);
					     $foundKeyWordRow                 = $model->findRow($cleanKeyword, "libelle", null, false);
						 if(!isset($foundKeyWordRow->itemid)) {
							 $insert_data["entrepriseid"] = 0;
							 $insert_data["libelle"]      = $cleanKeyword;
							 $insert_data["description"]  = (isset($postData["description"]))?$stringFilter->filter($postData["description"]) : $cleanKeyword;
							 $insert_data["creationdate"] = time();
							 $insert_data["creatorid"]    = $me->userid;
							 $insert_data["updateduserid"]= $insert_data["updatedate"] = 0;
							 
							 if( $dbAdapter->insert($prefixName."reservation_demandes_blacklist", $insert_data )) {
								 $itemid                  = $dbAdapter->lastInsertId();
								 $items[$itemid]          = $insert_data;
								 $itemSaved++;
							 }
						 }
				}
				if( count($items) >= count( $keywords )) {
				    if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("success" =>  sprintf("%d mots clés ont été ajoutés dans la liste des expressions non autorisées ", count($items))));
						exit;
					}
                    $this->setRedirect(sprintf("%d mots clés ont été ajoutés dans la liste des expressions non autorisées ", count($items)), "success");
                    $this->redirect("demandeblacklist/list");					
				} else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("success" =>  sprintf("%d mots clés ont été ajoutés dans la liste des expressions non autorisées ", count($items))));
						exit;
					}
                    $this->setRedirect(sprintf("%d mots clés ont été ajoutés dans la liste des expressions non autorisées ", count($items)), "infos");
                    $this->redirect("demandeblacklist/list");
				}
			} else {
				$errorMessages[]  = "Veuillez saisir au moins un mot clé";
			}
			if( count( $errorMessages )) {
				$defaultData      = $postData;
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
		$this->view->data       = $defaultData;
	}
	
	
	public function editAction()
	{
		$this->view->title    = " Mettre à jour les informations d'un mot clé";
		
		$itemid         = intval($this->_getParam("itemid", $this->_getParam("id" , 0)));
		
		if(!$itemid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandeblacklist/list");
		}		
		$model                    = $this->getModel("demandeblacklist");
		$item                = $model->findRow( $itemid , "itemid" , null , false);		
		if(!$item) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandeblacklist/list");
		}		
		$defaultData               = $item->toArray();
		$errorMessages             = array();  
		
		if( $this->_request->isPost()) {
			$postData              = $this->_request->getPost();
			$update_data           = array_merge( $defaultData , $postData);
			$me                    = Sirah_Fabric::getUser();
			$userTable             = $model->getTable();
			$dbAdapter             = $userTable->getAdapter();
			$prefixName            = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$update_data["libelle"]      = $libelle = $stringFilter->filter($update_data["libelle"]);
			$update_data["description"]  = $stringFilter->filter($update_data["description"]);			
			if(!$strNotEmptyValidator->isValid( $update_data["libelle"])) {
				$errorMessages[]         = " Veuillez entrer une désignation valide pour ce mot clé";
			} elseif( $model->findRow($update_data["libelle"], "libelle" , null , false ) && ( $item->libelle!=$update_data["libelle"])) {
				$errorMessages[]         = sprintf(" Une  mot clé existant porte la même désignation %s , veuillez entrer une désignation différente ", $update_data["libelle"]);
		    } else {
				$update_data["libelle"]  = $libelle;
			} 
			$update_data["updateduserid"]= $me->userid;
			$update_data["updatedate"]   = time();	
			$item->setFromArray( $update_data );				
			if( empty($errorMessages)) {
				if( $item->save()) {	
                    //On vidu cache
                    $modelCache          = $model->getMetadataCache();
					if( $modelCache ) {
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}					
					$successMessage      = sprintf("Les informations du mot clé %s ont été mises à jour avec succès",$update_data["libelle"]);
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = $successMessage;
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect($successMessage, "success" );
					$this->redirect("admin/demandeblacklist/infos/id/".$itemid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations du mot clé"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations du mot clé" , "message");
					$this->redirect("admin/demandeblacklist/infos/id/".$itemid);
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
		$this->view->item       = $this->view->keyword   = $item;
		$this->view->itemid     = $this->view->keywordid = $itemid;
	}	
 		
		
	public function infosAction()
	{
		$id              = intval($this->_getParam("id", $this->_getParam("itemid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/demandeblacklist/list");
		}		
		$model      = $this->getModel("demandeblacklist");
		$item       = $model->findRow( $id , "itemid" , null , false);		
		if(!$item) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun mot clé n'a été retrouvé avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucun  mot clé n'a été retrouvé avec cet identifiant" , "error");
			$this->redirect("admin/demandeblacklist/list");
		}
		$this->view->item    = $this->view->keyword = $item;
		$this->view->title   = sprintf(" Les informations du mot clé %s", $item->libelle );
		$this->view->columns = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$me            = Sirah_Fabric::getUser();
		$model         = $this->getModel("demandeblacklist");
		$dbAdapter     = $model->getTable()->getAdapter();
		$tablePrefix   = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("itemids", $this->_getParam("ids",array()));
		$errorMessages = $filtersCategories = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}

		$ids           = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $id) {
				     if(!$dbAdapter->delete($tablePrefix. "reservation_demandes_blacklist", array("itemid=?"=>$id)) ) {
						 $errorMessages[] = sprintf("Le mot clé id %d n'a pas pu être supprimé", $id);
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
			$this->redirect("admin/demandeblacklist/list");
		} else {
			$modelCache = $model->getMetadataCache();
            if( $modelCache ) {
				$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			}				
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les mots clés selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les mots clés selectionnés ont été supprimés avec succès", "success");
			$this->redirect("admin/demandeblacklist/list");
		}
	}
}