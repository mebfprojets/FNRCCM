<?php


class Admin_MydocumentsController extends Sirah_Controller_Default
{
	
		
	public function infosAction()
	{
		$csrfFormNames = $this->_helper->csrf->getFormNames(array("documentid", "id"), false );
		$params        = $this->_request->getParams();
		if( ( !isset( $params[$csrfFormNames["documentid"]] ) && !isset( $params[$csrfFormNames["id"]] ) ) || ( !$this->_helper->csrf->isValid() ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "La page que vous recherchez est introuvable"));
				exit;
			}
			$this->redirect("admin/mydocuments/list");
		}
		$documentid     = intval($this->_getParam($csrfFormNames["id"], $this->_getParam($csrfFormNames["documentid"], 0 ) ) );
		if( !$documentid ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		}
		$model        = $this->getModel("document");
		$document     = $model->findRow( $documentid , "documentid", null ,false );
		$me           = Sirah_Fabric::getUser();	
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		}  elseif( $document && ( $document->userid != $me->userid ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Vous n'etes pas propriétaire du document, seul le proprietaire est autorisé à supprimer" ));
				exit;
			}
			$this->setRedirect("Vous n'etes pas propriétaire du document, seul le proprietaire est autorisé à supprimer" , "error");
			$this->redirect("admin/mydocuments/list");
			
		}
		$render                 = "";
		switch( strtolower( $document->filextension ) ) {
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
			case "bmp":
				$render         = "images";
				break;						
		}
	    if( empty( $render ) ) {
			$this->_helper->viewRenderer->setNoRender(true);
			$this->_helper->layout->disableLayout(true );
			$this->download( $fichier , $document->filextension );
		} else {
			$this->view->document = $document;
			$this->view->category = $document->findParentRow("Table_Documentcategories");
			$this->render( $render );
		}		
	}

	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout(true);		
		$csrfFormNames = $this->_helper->csrf->getFormNames(array("documentid", "id"), false );
		$params        = $this->_request->getParams();
		if( ( !isset( $params[$csrfFormNames["documentid"]] ) && !isset( $params[$csrfFormNames["id"]] ) ) || ( !$this->_helper->csrf->isValid() ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "La page que vous recherchez est introuvable"));
				exit;
			}
			$this->redirect("admin/mydocuments/list");
		}
		$documentid     = intval($this->_getParam($csrfFormNames["id"], $this->_getParam($csrfFormNames["documentid"], 0 ) ) );
		if( !$documentid ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		}
		$me           = Sirah_Fabric::getUser();
		$model        = $this->getModel("document");
		$document     = $model->findRow( $documentid , "documentid", null ,false );
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		} elseif( $document && ( $document->userid != $me->userid ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Vous n'etes pas propriétaire du document" ));
				exit;
			}
			$this->setRedirect("Vous n'etes pas propriétaire du document" , "error");
			$this->redirect("admin/mydocuments/list");			
		}							
		$fichier                  = $document->filepath;
		$extension                = strtolower($document->filextension);		
		$content_type             = "image/*";
		switch( $extension ) {
			case "doc" :
			case "docx":
				$content_type     = "application/msword";
				break;
			case "pdf" :
				$content_type     = "application/pdf";
				break;
			case "xls":
			case "csv":
			case "xlsx":
				$content_type     = "application/excel";
				break;
			default:
				$content_type      = "image/*";		
		}		
		if( file_exists( $fichier ) ) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($fichier));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($fichier));
			if( $content = ob_get_clean() ) {
				if(!$this->_request->isXmlHttpRequest()) {
					echo "Des entetes HTTP ont déjà été transmises";
					exit();
				}
				echo ZendX_JQuery::encodeJson(array("error" => "Des entetes HTTP ont déjà transmises"));
				exit;
			}
			flush();
			@readfile($fichier);
			exit;
		} else {
			if(!$this->_request->isXmlHttpRequest()) {
				$this->setRedirect("Impossible de télécharger le fichier, car il n'existe pas dans le dossier indiqué" , "error");
				$this->redirect("document/index");
			}
			echo ZendX_JQuery::encodeJson(array("error"=>"Impossible de télécharger le fichier, car il n'existe pas dans le dossier indiqué"));
			exit;
		}	
	}
	
	
	public function deleteAction()
	{		
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout(true);
		$csrfFormNames = $this->_helper->csrf->getFormNames(array("documentid", "id"), false );
		$params        = $this->_request->getParams();
		if( ( !isset( $params[$csrfFormNames["documentid"]] ) && !isset( $params[$csrfFormNames["id"]] ) ) || ( !$this->_helper->csrf->isValid() ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "La page que vous recherchez est introuvable"));
				exit;
			}
			$this->redirect("admin/mydocuments/list");
		}
		$documentid     = intval($this->_getParam($csrfFormNames["id"], $this->_getParam($csrfFormNames["documentid"], 0 ) ) );
		$me             = Sirah_Fabric::getUser( );
		if( !$documentid ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		}
		$model        = $this->getModel("document");
		$document     = $model->findRow( $documentid , "documentid" , null , false );
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/mydocuments/list");
		} elseif( $document && ( $document->userid != $me->userid ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => "Vous n'etes pas propriétaire du document, seul le proprietaire est autorisé à supprimer" ));
				exit;
			}
			$this->setRedirect("Vous n'etes pas propriétaire du document, seul le proprietaire est autorisé à supprimer" , "error");
			$this->redirect("admin/mydocuments/list");
			
		}		
		$filepath   = $document->filepath;		
		if( $document->delete( ) ) {
			@unlink( $filepath );
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "La suppression du document s'est effectuée avec succès "));
				exit;
			}
			$this->setRedirect("La suppression du document s'est effectuée avec succès " , "success");
			$this->redirect("admin/mydocuments/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => "La suppressionn du document a echoué , veuillez réessayer"));
				exit;
			}
			$this->setRedirect( "La suppressionn du document a echoué , veuillez réessayer" , "error");
			$this->redirect("admin/mydocuments/list");
		}		
	}
}