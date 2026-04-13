<?php

class ProjectController extends Sirah_Controller_Default
{
	public function listAction()
	{
		$this->_helper->layout->setLayout("default");
		$this->view->title      = " Les informations générales du projet OSRO/BKF/203/SWI";
		$model                  = $this->getModel("project");
	
		$projectid              = "1";
		$project                = $model->findRow( $projectid , "projectid", null, false );
		$errorMessages          = array();
		$projectData            = $model->getEmptyData();
	
		if( !$project ) {
			$dbAdapter         = $model->getTable()->getAdapter();
			$prefixName        = $model->getTable()->info("namePrefix");
			$newRow            = array("projectid"             => 1,
					                   "code"                  => "FNRCCM",
					                   "libelle"               => "FICHIER NATIONAL DES REGISTRES DE COMMERCE ET DE CREDITS MOBILIERS",
					                   "objectif_global"       => "Cette information n'est pas encore disponible",
					                   "context"               => "Cette information n'est pas encore disponible",
					                   "presentation"          => "Cette information n'est pas encore disponible",
					                   "presentation_structure"=> "Cette information n'est pas encore disponible",
					                   "presentation_equipe"   => "Cette information n'est pas encore disponible",
					                   "creatorid"             => 1,
					                   "creationdate"          => time(),
					                   "updatedate"            => 0,
					                   "updateduserid"         => 0);
			if( $dbAdapter->insert( $prefixName . "rccm_projet_application", $newRow ) ) {
				$project         = $model->findRow( $projectid , "projectid", null, false );
				$projectData     = $project->toArray();
			} else {
				$errorMessages[] = "Les informations du projet sont indisponibles";
			}
		} else {
			$projectData          = $model->findRow( $projectid , "projectid", null, false );
		}
		if( count( $errorMessages ) ) {
			$defaultData        = $viewData;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message , "error") ;
			}
		}
	
		$this->view->data         = $projectData;
		$this->view->projectid    = $projectid;
		$this->view->columns      = array("left");
	
	}
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("default");
		$this->view->title      = " Les informations générales du projet OSRO/BKF/203/SWI";
		$model                  = $this->getModel("project");
		
		$projectid              = "1";
		$project                = $model->findRow( $projectid , "projectid", null, false );
		$errorMessages          = array();
		$projectData            = $model->getEmptyData();
		
		if( !$project ) {
             $dbAdapter         = $model->getTable()->getAdapter();
             $prefixName        = $model->getTable()->info("namePrefix");
             $newRow            = array( "projectid"             => 1,
             		                     "code"                  => "FNRCCM", 
             		                     "libelle"               => "FICHIER NATIONAL DES REGISTRES DE COMMERCE ET DE CREDITS MOBILIERS",
             		                     "objectif_global"       => "Cette information n'est pas encore disponible",
             		                     "context"               => "Cette information n'est pas encore disponible",
             		                     "presentation"          => "Cette information n'est pas encore disponible",
             		                     "presentation_structure"=> "Cette information n'est pas encore disponible",
             		                     "presentation_equipe"   => "Cette information n'est pas encore disponible",
             		                     "creatorid"             => 1,
             		                     "creationdate"          => time(),
             		                     "updatedate"            => 0,
             		                     "updateduserid"         => 0);
             if( $dbAdapter->insert( $prefixName . "rccm_projet_application", $newRow ) ) {
             	 $project         = $model->findRow( $projectid , "projectid", null, false );
             	 $projectData     = $project->toArray();
             } else {
             	 $errorMessages[] = "Les informations du projet sont indisponibles";
             }
		} else {
			$projectData          = $model->findRow( $projectid , "projectid", null, false );
		}		
		if( count( $errorMessages ) ) {
			$defaultData        = $viewData;
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message , "error") ;
			}
		}
		
		$this->view->data         = $projectData;
		$this->view->projectid    = $projectid;
		$this->view->columns      = array("left");
		
	}
	
	
	public function editAction()
	{
		$projectid      = intval($this->_getParam("id" , $this->_getParam("projectid" , 0 )));
		if(!$projectid) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("project/infos");
		}
		$model                      = $this->getModel("project");
		$project                   = $model->findRow( $projectid, "projectid", null , false );
		if(!$project) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("project/infos");
		}
		$defaultData               = $project->toArray();
		$errorMessages             = array();
		if( $this->_request->isPost() )  {			
			$postData              = $this->_request->getPost();
			$cleanPostData         = array_intersect_key( $postData, $defaultData );
			$updateData            = array_merge( $defaultData, $cleanPostData );
				
			$me                    = Sirah_Fabric::getUser();
			$userTable             = $me->getTable();
			$dbAdapter             = $userTable->getAdapter();
			$prefixName            = $userTable->info("namePrefix");
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$stringFilter          = new Zend_Filter();
			$htmlFilter            = new Zend_Filter_StripTags( array("allowTags"    => array("a", "b" ,"i", "p" , "i"  , "table" , "div", "tr" , "h3" , "h4", "br", "ul",
					                                                                          "td", "th", "thead", "tbody", "img", "u", "em", "h1", "h2", "strong", "li" ) ,
					                                                  "allowAttribs" => array("style", "width", "align", "height", "class", "href") ) );
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter( $htmlFilter);
			
			if(!$strNotEmptyValidator->isValid( $updateData["code"] ) ) {
				$errorMessages[]                      = "Veuillez entrer le code du projet";
			} else {
				$updateData["code"]                   = $stringFilter->filter( $updateData["code"] );
			}
			if(!$strNotEmptyValidator->isValid( $updateData["libelle"] ) ) {
				$errorMessages[]                      = "Veuillez entrer l'intitulé du projet";
			} else {
				$updateData["libelle"]                = $stringFilter->filter( $updateData["libelle"] );
			}
			    $updateData["objectif_global"]        = $stringFilter->filter( $updateData["objectif_global"] );
			    $updateData["objectif_strategique"]   = $stringFilter->filter( $updateData["objectif_strategique"] );
			    $updateData["objectifs"]              = $stringFilter->filter( $updateData["objectifs"] );
			    $updateData["introduction"]           = $stringFilter->filter( $updateData["introduction"] );
			    $updateData["context"]                = $stringFilter->filter( $updateData["context"] );
			    $updateData["presentation"]           = $stringFilter->filter( $updateData["presentation"] );
			    $updateData["presentation_structure"] = $stringFilter->filter( $updateData["presentation_structure"] );
			    $updateData["responsable"]            = $stringFilter->filter( $updateData["responsable"] );
			    $updateData["statut"]                 = 0;
			    $updateData["datedebut"]              = 0;
			    $updateData["datefin"]                = 0;
			    $updateData["updatedate"]             = 0;
			    $updateData["updateduserid"]          = 0;
			    
			    if( empty( $errorMessages ) ) {
			    	$project->setFromArray( $updateData );
			    	if( !$project->save( ) ) {
			    		$errorMessages[]              = "Aucune information du projet n'a été changée";
			    	} else {
			    		if( $this->_request->isXmlHttpRequest()) {
			    			$this->_helper->viewRenderer->setNoRender(true);
			    			$this->_helper->layout->disableLayout(true);
			    			$jsonSuccessMsg = array( "project" => $project->toArray() , "success" => "Les informations du projet ont été mises à jour avec succès");
			    			echo ZendX_JQuery::encodeJson( $jsonSuccessMsg );
			    			exit;
			    		}
			    		$this->setRedirect("Les informations du projet ont été mises à jour avec succès" , "success");
			    		$this->redirect("project/infos");
			    	}
			   }
			  if( count( $errorMessages ) ) {
			   	    $defaultData = $updateData;
			   	if( $this->_request->isXmlHttpRequest()) {
			   		$this->_helper->viewRenderer->setNoRender(true);
			   		$this->_helper->layout->disableLayout(true);
			   		echo ZendX_JQuery::encodeJson(array("error" =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
			   		exit;
			   	}
			   	foreach( $errorMessages as $message ) {
			   		     $this->_helper->Message->addMessage($message , "error") ;
			   	}
			 } 			    
		}
		$this->view->title   = "Mettre à jour les informations d'un projet";
		$this->view->data    = $defaultData;
		$this->view->columns = array("left");
	}	
}