<?php

class EntrepriseController extends Sirah_Controller_Default
{
	

	public function listAction()
	{
		$this->_helperViewRenderer->setNoRender(true);
		$this->view->title      = "Liste des entreprises";
	}
	
	public function infosAction()
	{		
		$id                      = intval($this->_getParam("id" , $this->_getParam("entreprise" , 1 )));
		
		$model                   = $this->getModel("structure");		
		$entreprise              = $model->findRow($id);		
		if( !$entreprise ) {
			$this->setRedirect("Aucune information n'a été retrouvée pour l'entreprise don vous souhaitez visualiser les informations");
			$this->redirect("index/index");
		}
		
		$this->view->title       = "Les informations de l'entreprise";
		$this->view->entreprise  = $entreprise;	
		$this->render("infosentreprise");
	}
	
	
	public function editAction()
	{
		$this->view->title       = "Mettre à jour les informations de l'entreprise";
		
		$id                      = intval($this->_getParam("id" , $this->_getParam("entreprise" , 1 )));		
		$model                   = $this->getModel("structure");
		$entreprise              = $model->findRow($id , "id" , null , false );
		
		if( !$entreprise ) {
			$this->setRedirect("Aucune information n'a été retrouvée pour l'entreprise don vous souhaitez visualiser les informations");
			$this->redirect("index/index");
		}
		
		if($this->_request->isPost()) {
			$postData            = $this->_request->getPost();	
            $entrepriseData      = $entreprise->toArray();
			$updateData          = array_merge( $entrepriseData , $postData );
			$errorMessages       = array();
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			 
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$emailValidator              = new Sirah_Validateur_Email();
			
			if(!$strNotEmptyValidator->isValid($updateData["libelle"])) {
				$errorMessages[]         = " Veuillez fournir une désignation valide pour l'entreprise ";
			} else {
				$updateData["libelle"]   = $stringFilter->filter($updateData["libelle"]);
			}
			if(!$strNotEmptyValidator->isValid($updateData["telephone"])) {
				$errorMessages[]         = " Veuillez fournir un numéro de téléphone valide pour l'entreprise ";
			} else {
				$updateData["telephone"] = $stringFilter->filter($updateData["telephone"]);
			}
			if(!$strNotEmptyValidator->isValid($updateData["codepostal"])) {
				$errorMessages[]         = " Veuillez fournir un code postal valide ";
			} else {
				$updateData["codepostal"]= $stringFilter->filter($updateData["codepostal"]);
			}
			if(!$emailValidator->isValid($updateData["email"])) {
				$errorMessages[]         = " Veuillez fournir une adresse email valide pour l'entreprise ";
			} else {
				$updateData["email"]     = $stringFilter->filter($updateData["email"]);
			}		
			    $updateData["slogan"]      = $stringFilter->filter($updateData["slogan"]);
			    $updateData["country"]     = $stringFilter->filter($updateData["country"]);
			    $updateData["siteweb"]     = $stringFilter->filter($updateData["siteweb"]);
			    $updateData["ville"]       = $stringFilter->filter($updateData["ville"]);
			    $updateData["fax"]         = $stringFilter->filter($updateData["fax"]);
			    $updateData["adresse"]     = $stringFilter->filter($updateData["adresse"]);
			    $updateData["responsable"] = $stringFilter->filter($updateData["responsable"]);
			    $updateData["ifu"]         = $stringFilter->filter($updateData["ifu"]);
			    
			    $entreprise->setFromArray($updateData);
			    
			    if(empty($errorMessages)) {
			    	if(!$entreprise->save()) {
			    		if($this->_request->isXmlHttpRequest()) {
			    			$this->_helper->viewRenderer->setNoRender(true);
			    		    $this->_helper->layout->disableLayout(true);
			    		    echo ZendX_JQuery::encodeJson(array("error"  => "Aucune mise à jour réelle n'a été appliquée sur les informations de l'entreprise "));
			    		    exit;
			    		}
			    		$this->setRedirect("Aucune mise à jour réelle n'a été appliquée sur les informations de l'entreprise" , "message");
			    		$this->redirect("entreprise/infos/id/".$entreprise->id);
			    	} else {
			    		$entreprise = $model->findRow($id , "id" , null , false );
			    		if($this->_request->isXmlHttpRequest()) {
			    			$this->_helper->viewRenderer->setNoRender(true);
			    			$this->_helper->layout->disableLayout(true);
			    			echo ZendX_JQuery::encodeJson(array("success"  => "Les informations de l'entreprise on été mises à jour avec succès"));
			    			exit;
			    		}
			    		$this->setRedirect("Les informations de l'entreprise on été mises à jour avec succès" , "success");
			    		$this->redirect("entreprise/infos/id/".$entreprise->id);
			    	}			    	
			    } else {
			    	if($this->_request->isXmlHttpRequest()) {
			    		$this->_helper->viewRenderer->setNoRender(true);
			    		$this->_helper->layout->disableLayout(true);
			    		echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages)));
			    		exit;
			    	}
			    	foreach($errorMessages as $errorMessage) {
			    		$this->_helper->Message->addMessage($errorMessage , "error");
			    	}
			    }
		}		
		$this->view->entreprise  = $entreprise;
		$this->render("editentreprise");
	}
	
	


}






