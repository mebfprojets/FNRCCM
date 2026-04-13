<?php

class Admin_ClientgroupsController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = " La liste des groupes de clients "  ;
		
		$model              = $this->getModel("clientgroup");
		
		$groupes            = array();
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
		
		$filters            = array("libelle" => null  );		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$groupes               = $model->getList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->groupes   = $groupes;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title                   = " Enregistrer un groupe de clients ";
		
		$model                               = $this->getModel("clientgroup");
		
		$defaultData                         = $model->getEmptyData();
		$errorMessages                       = array();
		
		if( $this->_request->isPost() ) {
			$postData        = $this->_request->getPost();
			$formData        = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data     = array_merge( $model->getEmptyData() , $formData);
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = $userTable->getAdapter();
			$prefixName      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$floatValidator       = new Zend_Validate_Float();	
			$floatFilter	      = new Zend_Filter_Digits();
			
			$libelle              = $stringFilter->filter($insert_data["libelle"]);
			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]  = " Veuillez entrer une désignation valide pour ce groupe de client";
			} elseif( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]                = sprintf("Un groupe existant porte la désignation %s , veuillez entrer une désignation différente " , $libelle );
		    } else {
				$insert_data["libelle"]         = $libelle;
			}
			$insert_data["code"]                = $stringFilter->filter( $insert_data["code"] );
			$insert_data["description"]         = $stringFilter->filter( $insert_data["description"] );
			$insert_data["creatorid"]           = $me->userid;
			$insert_data["creationdate"]        = time();											
			if(empty($errorMessages)) {
				if( $dbAdapter->insert( $prefixName . "envoitout_clients_groups", $insert_data) ) {
					$groupid       = $dbAdapter->lastInsertId();
					if(!$strNotEmptyValidator->isValid( $insert_data["code"] )) {
						$code                   = sprintf("G-%06d", $groupid );
						$groupe                 = $model->findRow(  $groupid , "groupid" , null , false );
						$groupe->code           = $code;
						$groupe->save();
					}				
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "Le groupe de clients a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Le groupe de clients a été enregistré avec succès", "success" );
					$this->redirect("admin/groupclients/list" );					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement du groupe de clients a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du groupe de clients a echoué" , "error");
					$this->redirect("admin/groupclients/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach($errorMessages as $message) {
					$this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data      = $defaultData;	
	}
	
	
	public function editAction()
	{
		$this->view->title  = " Mettre à jour les informations d'un groupe de clients  ";
		
		$groupid            = intval($this->_getParam("groupid", $this->_getParam("id" , 0)));
		
		if(!$groupid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/groupclients/list");
		}		
		$model              = $this->getModel("clientgroup");
 	
		$group              = $model->findRow($groupid , "groupid" , null , false);		
		if(!$group) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/groupclients/list");
		}		
		$defaultData         = $group->toArray();
		$errorMessages       = array();  
		
		if( $this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = $userTable->getAdapter();
			$prefixName      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$libelle              = $stringFilter->filter( $update_data["libelle"] );
			$code                 = $stringFilter->filter( $update_data["code"] );
			
		   if(!$strNotEmptyValidator->isValid($update_data["libelle"])) {
				$errorMessages[]           = " Veuillez entrer une désignation valide du groupe de clients";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ( $group->libelle != $libelle ) ) {
				$errorMessages[]           = sprintf(" Un groupe existant porte la désignation %s , veuillez entrer une désignation différente ", $libelle );
		    } else {
				$update_data["libelle"]    = $libelle;
			}
			if(!$strNotEmptyValidator->isValid($code )) {
				$errorMessages[]           = " Veuillez entrer un code famille valide";
			} elseif( $model->findRow( $code , "code" , null , false ) && ( $group->code != $code ) ) {
				$errorMessages[]           = sprintf(" Un groupe existant porte le code %s , veuillez entrer un code différent ", $code );
			} else {
				$update_data["code"]       = $code;
			}			 
			$update_data["description"]    = $stringFilter->filter( $update_data["description"] );
			$update_data["updateduserid"]  = $me->userid;
			$update_data["updatedate"]     = time();	
			$group->setFromArray($update_data);				
			if(empty($errorMessages)) {
				if( $group->save()) {					 					
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations du groupe de clients ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations du groupe de clients ont été mises à jour avec succès", "success" );
					$this->redirect("admin/groupclients/infos/id/".$groupid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => " Aucune modifiation n'a été apportée sur les informations du groupe de clients"));
						exit;
					}
					$this->setRedirect(" Aucune modifiation n'a été apportée sur les informations du groupe de clients" , "message");
					$this->redirect("admin/groupclients/infos/id/".$groupid);
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
		$this->view->data      = $defaultData;
		$this->view->groupid   = $groupid;
	}	
 		
		
	public function infosAction()
	{
		$id              = intval($this->_getParam("id" , $this->_getParam("groupid"  , 0)));		
		$model           = $this->getModel("clientgroup");
		$group           = $model->findRow($id , "groupid" , null , false);		
		if(!$group) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides", "error");
			$this->redirect("admin/groupclients/list");
		}
		$this->view->groupe     = $group;
		$this->view->title      = " Les informations d'un groupe de clients ";
		$this->view->columns    = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("clientgroup");
		$ids           = $this->_getParam("groupids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$group  = $model->findRow( $id , "groupid" , null , false );
				if($group) {
					if(!$group->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée Le groupe de client id#$id n'a pas été supprimée ";
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour la formation id #$id ";
				}
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/groupclients/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les groupes de clients indiqués ont  été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les groupes de clients indiqués ont  été supprimés avec succès", "success");
			$this->redirect("admin/groupclients/list");
		}
	}
}