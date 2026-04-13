<?php

class Admin_ArticlecategoriesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "GESTION DES CATEGORIES D'ARTICLES"  ;
		
		$model              = $this->getModel("articlecategorie");
		
		$unites             = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       =    new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;		
		
		$filters            = array("title"=>null);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$categories             = $model->getList( $filters , $pageNum , $pageSize);
		$paginator              = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns    = array("left");
		$this->view->categories = $categories;
		$this->view->filters    = $filters;
		$this->view->paginator  = $paginator;
		$this->view->pageNum    = $pageNum;
		$this->view->pageSize   = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title     = "NOUVELLE CATEGORIE";
		
		$model                 = $this->getModel("articlecategorie");
		
		$defaultData           = $model->getEmptyData();
		$errorMessages         = array();
		
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$emptyData         = $model->getEmptyData();
			$formData          = array_intersect_key($postData ,$emptyData);
			$insert_data       = array_merge($emptyData, $formData);
			$me                = Sirah_Fabric::getUser();
			$modelTable        = $model->getTable();
			$dbAdapter         = $modelTable->getAdapter();
			$prefixName        = $modelTable->info("namePrefix");
			$tableName         = $modelTable->info("name");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      =     new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		
			$title                  = $insert_data["title"] = (isset($postData["title"]))? $stringFilter->filter($postData["title"]) : "";
			if(!$strNotEmptyValidator->isValid($title)) {
				$errorMessages[]    = " Veuillez entrer une désignation valide pour cette catégorie";
			} elseif( $model->findRow( $title , "title" , null , false )) {
				$errorMessages[]    = sprintf("Une catégorie existante porte la désignation %s, veuillez entrer une désignation différente " , $title );
		    }  
			if(!$strNotEmptyValidator->isValid( $insert_data["code"] ) && (strlen( $insert_data["title"]) >= 2 ) ) {
				$titleStrToArray    = str_split($insert_data["title"]);
				shuffle( $titleStrToArray );
				$categorieCode= strtoupper( $titleStrToArray[0]."".$titleStrToArray[1]);
				$i                  = 1;
				while( $model->findRow( $categorieCode, "code", null , false )) {
					$i++;
					if( isset( $titleStrToArray[$i] ) ) {
						$categorieCode = strtoupper( $titleStrToArray[0]."".$titleStrToArray[$i]);
					} else {
						$categorieCode = strtoupper( $titleStrToArray[0]."".$titleStrToArray[1]).sprintf("%02d",$i);
					}
				}
				$insert_data["code"]     = $categorieCode;
			}
			if(!$strNotEmptyValidator->isValid( $insert_data["code"]) ) {
				$errorMessages[]         = "Veuillez saisir un code de catégorie valide";
			} elseif( $model->findRow( $insert_data["code"], "code", null , false ) )	 {
				$errorMessages[]         = "Le code de la catégorie que vous avez saisi, existe déjà";
			}
 			$insert_data["keywords"]     = (isset($postData["keywords"]))?$stringFilter->filter($postData["keywords"])  : ""; 
			$insert_data["description"]  = (isset($postData["description"]))?$stringFilter->filter($postData["description"])  : "";
			$insert_data["creatorid"]    = $me->userid;
			$insert_data["creationdate"] = time();
            $insert_data["updatedate"]   = $insert_data["updateduserid"] = 0;			
			if( empty($errorMessages)) {
				if( $dbAdapter->insert( $tableName, $insert_data) ) {
					$catid               = $dbAdapter->lastInsertId();				
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"=>"La catégorie a été enregistrée avec succès"));
						exit;
					}
					$this->setRedirect("La catégorie a été enregistrée avec succès", "success" );
					$this->redirect("admin/articlecategories/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement de la catégorie a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la catégorie a echoué" , "error");
					$this->redirect("admin/articlecategories/list")	;
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
		$this->view->title     = " Mettre à jour les informations d'une catégorie";
		
		$catid                 = intval($this->_getParam("catid", $this->_getParam("id" , 0)));
		
		if(!$catid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/articlecategories/list");
		}		
		$model                 = $this->getModel("articlecategorie");
		$modelUnite            = $this->getModel("unitemesure");
 	
		$categorie          = $model->findRow( $catid , "catid" , null , false);		
		if(!$categorie) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/articlecategories/list");
		}		
 
		$defaultData              = $categorie->toArray();
		$errorMessages            = array();  
		
		if( $this->_request->isPost()) {
			$postData             = $this->_request->getPost();
			$update_data          = array_merge( $defaultData , $postData);
			$me                   = Sirah_Fabric::getUser();
			$modelTable           = $model->getTable();
			$dbAdapter            = $modelTable->getAdapter();
			$prefixName           = $modelTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter         = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$title                = $stringFilter->filter( $update_data["title"] );
			
		   //On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		
			$title                = $update_data["title"] = (isset($postData["title"]))? $stringFilter->filter($postData["title"]) : $categorie->title;
			$update_data["code"]  = (isset($postData["code"]))? $stringFilter->filter($postData["code"]) : $categorie->code;
 
			if(!$strNotEmptyValidator->isValid($title)) {
				$errorMessages[]  = " Veuillez entrer une désignation valide pour cette catégorie";
			} elseif( $model->findRow( $title , "title" , null , false ) && ($title!=$categorie->title) ) {
				$errorMessages[]  = sprintf("Une catégorie existante porte la désignation %s , veuillez entrer une désignation différente " , $title );
		    }  
			if(!$strNotEmptyValidator->isValid( $update_data["code"] ) && (strlen( $update_data["title"]) >= 2 ) ) {
				$titleStrToArray      = str_split($update_data["title"]);
				shuffle( $titleStrToArray );
				$categorieCode = strtoupper( $titleStrToArray[0]."".$titleStrToArray[1]);
				$i                    = 1;
				while( $model->findRow( $categorieCode, "code", null , false )) {
					$i++;
					if( isset( $titleStrToArray[$i] ) ) {
						$categorieCode = strtoupper( $titleStrToArray[0]."".$titleStrToArray[$i]);
					} else {
						$categorieCode = strtoupper( $titleStrToArray[0]."".$titleStrToArray[1]).sprintf("%02d",$i);
					}
				}
				$update_data["code"]      = $categorieCode;
			}
			if(!$strNotEmptyValidator->isValid( $update_data["code"]) ) {
				$errorMessages[]          = "Veuillez entrer un code valide";
			} elseif( $model->findRow($update_data["code"],"code",null,false) && (strtolower($update_data["code"])!=strtolower($categorie->code)))	 {
				$errorMessages[]          = "Le code de la catégorie que vous avez saisi, existe déjà";
			}
			$update_data["description"]   = (isset($postData["description"]))?$stringFilter->filter($postData["description"]) : $categorie->description;
			$update_data["updateduserid"] = $me->userid;
			$update_data["updatedate"]    = time();	
			$categorie->setFromArray( $update_data);				
			if( empty($errorMessages)) {
				if( $categorie->save()) {					 					
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations de la catégorie ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations de la catégorie ont été mises à jour avec succès", "success" );
					$this->redirect("admin/articlecategories/infos/id/".$catid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"=>"Aucune modifiation n'a été apportée sur les informations de la catégorie"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la catégorie" , "message");
					$this->redirect("admin/articlecategories/infos/id/".$catid);
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
		$this->view->data     = $defaultData;
		$this->view->catid    = $catid;
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
			$this->redirect("admin/articlecategories/list");
		}		
		$model           = $this->getModel("articlecategorie");
		$categorie    = $model->findRow( $id , "catid" , null , false);		
		if( !$categorie ){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"  => " Aucune entrée de catégorie n'a été retrouvée avec cet identifiant "));
				exit;
			}
			$this->setRedirect("Aucune entrée de catégorie n'a été retrouvée avec cet identifiant", "error");
			$this->redirect("admin/articlecategories/list");
		}
		$this->view->articlecategorie = $this->view->categorie = $this->view->category = $categorie;
		$this->view->title            = "LES INFORMATIONS D'UNE CATEGORIE";
		$this->view->columns          =  array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model                     = $this->getModel("articlecategorie");
		$ids                       = $this->_getParam("catids", $this->_getParam("ids",array()));
		$errorMessages             = array();
		if( is_string($ids) ) {
			$ids                   = explode("," , $ids );
		}
		$ids                       = (array)$ids;
		if( count($ids)) {
			foreach(  $ids as $id) {
					$categorie             = $model->findRow( $id , "catid" , null , false );
					if($categorie) {
						if(!$categorie->delete()) {
							$errorMessages[] = " Erreur de la base de donnée : La catégorie id#$id n'a pas été supprimée ";
						}
					} else {
							$errorMessages[] = "Aucune entrée valide n'a été trouvée pour la localité id #$id ";
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
			$this->redirect("admin/articlecategories/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"=>"Les catégories sélectionnées ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les catégories sélectionnées ont été supprimés avec succès", "success");
			$this->redirect("admin/articlecategories/list");
		}
	}
}