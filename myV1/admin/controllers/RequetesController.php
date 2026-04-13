<?php

class Admin_RequetesController extends Sirah_Controller_Default
{

	
	public function listAction()
	{
		$this->view->title  = "Historique des demandes d'accès";
		
		$model              = $this->getModel("requete");
		$me                 = Sirah_Fabric::getUser();
		
		$requetes           = array();
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
		
		$filters            = array("libelle"=> null,"numero" => null, "registreid" => 0, "userid" => 0, "validated" => -1, "typedocument" => 0 );
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$requetes           = $model->getList( $filters , $pageNum , $pageSize);
		$paginator          = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->requetes       = $requetes;
		$this->view->typesdocuments = $model->typedocuments();
		$this->view->filters        = $filters;
		$this->view->paginator      = $paginator;
		$this->view->pageNum        = $pageNum;
		$this->view->pageSize       = $pageSize;
	}
	
	public function processAction()
	{
		$requestid        = intval($this->_getParam("requestid", $this->_getParam("id" , 0)));
		if(!$requestid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}
		$model         = $this->getModel("requete");
		$modelRegistre = $this->getModel("registre");					
		
		$requete       = $model->findRow( $requestid, "requestid" , null , false);
		$registre      = ( $requete ) ? $modelRegistre->findRow( $requete->registreid, "registreid", null , false ) : null;
		$validated     = intval($this->_getParam("validated", $this->_getParam("id" , 1)));
		if(!$requete || !$registre ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}
		$modelDocument         = $this->getModel("document");
		$typesDocuments        = $requete->typedocuments();
		$documents             = $model->documents( null , $requete->typedocument );
		$typedocument          = (isset( $typesDocuments[$requete->typedocument] )) ? $typesDocuments[$requete->typedocument] : "Complement";
		$client                = Sirah_Fabric::getUser($requete->userid);
		$config                = Sirah_Fabric::getConfig();
		$mailer                = Sirah_Fabric::getMailer();
		$defaultToEmail        = $config["resources"]["mail"]["defaultFrom"]["email"];
		$defaultToName         = $config["resources"]["mail"]["defaultFrom"]["name"];
		if( !$client->userid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Le client relatif à cette demande d'accès n'existe plus dans la plateforme, veuillez informer l'administrateur"));
				exit;
			}
			$this->setRedirect("Le client relatif à cette demande d'accès n'existe plus dans la plateforme, veuillez informer l'administrateur" , "error");
			$this->redirect("admin/requetes/list");
		}
		if( $this->_request->isPost()) {
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
			$postData          = $this->_request->getPost();						
			
			$registreid        = $requete->registreid;
			
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			if( intval( $validated ) == 1 ) {
				$validationDocument  = array("userid" => $requete->userid, "requestid" => $requestid , "documentid" => 0);
				if( count( $documents )) {
					$validationDocument["documentid"]= $documents[0]["documentid"];
				} else {
					$docUpload                       = new Zend_File_Transfer();
					$docUpload->addValidator("Count"    , false , 1 );
					$docUpload->addValidator("Extension", false , array("pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
					$docUpload->addValidator("Size"     , false , array("max" => "10MB"));
					$docUpload->addValidator("FilesSize", false , array("max" => "10MB"));
					if( $registre->type==1) {
					    $docPathroot          = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
					} else {
						$docPathroot          = APPLICATION_DATA_PATH . DS . "registres" . DS . "morales"  . DS . "original";
					}
					$searchIvalidStr          = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace                  = array ('e','a','i','u','o','n','y','c','-','','-');
					$docPathFilename          = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("requestdoc", false ));
					$docPath                  = $docPathroot . DS . time() . "_" .$docPathFilename;
					$docUpload->addFilter("Rename", array("target" => $docPath, "overwrite" => true), "requestdoc");
					if( !$docUpload->isUploaded("requestdoc") ) {
						$errorMessages[]      = "Un document doit être rattaché à cette opération de traitement";
					} else {
						$docUpload->receive("requestdoc");
						if( $docUpload->isReceived("requestdoc") ) {
							$docExtension                     = Sirah_Filesystem::getFilextension( $docPathFilename );
							$tmpFilename                      = Sirah_Filesystem::getName( $docPathFilename);
							$fileSize                         = $docUpload->getFileSize("requestdoc");
							
							$documentData                     = array();
							$documentData["userid"]           = $me->userid;
							$documentData["category"]         = $requete->typedocument;
							$documentData["resource"]         = "requete";
							$documentData["resourceid"]       = 0;
							$documentData["filedescription"]  = $registre->numero;
							$documentData["filemetadata"]     = $requete->requestoken;
							$documentData["creationdate"]     = time();
							$documentData["creatoruserid"]    = $me->userid;
							$filename                         = $registre->numero."_".$typedocument;
							$documentData["filename"]         = $modelDocument->rename( $filename, $me->userid );
							$documentData["filepath"]         = $docPath ;
							$documentData["access"]           = 6;
							$documentData["filextension"]     = $docExtension;
							$documentData["filesize"]         = floatval( $fileSize );
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $documentData ) ) {
								$documentid                   = $dbAdapter->lastInsertId();
								if( $dbAdapter->insert($prefixName . "rccm_registre_documents",array("registreid"=> $registreid,"documentid" => $documentid,"access" => 6 ))){
								    $validationDocument["documentid"] = $documentid;
								} else {
									$errorMessages[]          = "Les informations du document ont été partiellement enregistrées";
								}							
							} else {
								$errorMessages[]              = "Les informations du document ont été partiellement enregistrées";
							}
						} else {
							$errorMessages[]                  = "Le document relatif à la requête n'a pas été reçu par le serveur";
						}
					}
				}												
				if( $client->userid && empty( $errorMessages) ) {
					$msgSubject       = (isset($postData["requestsubject"]))? $stringFilter->filter( $postData["requestsubject"]): sprintf("Votre demande d'accès aux informations du RCCM %s", $registre->numero );
					$clientMsg        = (isset($postData["requestmsg"]    ))? $stringFilter->filter( $postData["requestmsg"]    ): "<p> Bonjour Mr/Mrs ".$client->lastname." ".$client->firstname." <br/> </p>
					                       <p> Votre demande d'accès au type de document ".$typedocument." du RCCM N° ".$registre->numero." a été validée. </p>
					                       <p> Veuillez vous connecter à votre compte pour le téléchargement du document </p>
					                       <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
					$msgPartialData   = array("subject"        =>  $msgSubject,
							                  "message"        =>  $clientMsg,
							                  "logoMsg"        =>  APPLICATION_STRUCTURE_LOGO,
							                  "replyToEmail"   =>  $defaultToEmail,
							                  "replyToName"    =>  $defaultToName,
							                  "replyToTel"     =>  "",
							                  "replyToSiteWeb" =>  "http://www.siraah.net/about",
							                  "toName"         =>  sprintf("%s %s", $client->lastname, $client->firstname),
							                  "toEmail"        =>  $client->email );
					$msgBody        = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
					$mailer->setFrom( $defaultToEmail , "FNRCCM ");
					$mailer->setSubject( $msgSubject );
					$mailer->addTo( $client->email , stripslashes( $client->lastname ) );
					$mailer->setBodyHtml( $msgBody );
					try{
						$mailer->send();
					} catch(Exception $e) {
						$errorMessages[]     = "Nous avons tenté de transmettre un email au client,son compte de messagerie semble inaccessible,vérifiez votre connexion internet et reprenez l'opération.";
						if( APPLICATION_DEBUG ) {
							$errorMessages[] = " Informations de débogages : ".$e->getMessage();
						}
					}
					if( empty( $errorMessages )) {
						if( $dbAdapter->insert( $prefixName . "rccm_access_requests_documents", $validationDocument )) {
							$requete->validated     = 1;
							$requete->accepted      = 1;
							$requete->response      = $clientMsg;
							$requete->updatedate    = time();
							$requete->updateduserid = $me->userid;
							if( $requete->save() ) {
								$this->setRedirect(sprintf("La requete de demande d'accès numéro %s a été validée avec succès. Un email a été transmis au client", $requete->requestoken) , "success");
								$this->redirect("admin/requetes/list");
							} else {
								$errorMessages[]= "Le traitement de la requête a echoué pour des raisons inconnues";
							}
						}
					}
				} 																																
			} else {
				$msgSubject       = (isset($postData["requestsubject"]))? $stringFilter->filter($postData["requestsubject"]): sprintf("Votre demande d'accès aux informations du RCCM %s", $registre->numero );
				$clientMsg        = (isset($postData["requestmsg"]    ))? $stringFilter->filter( $postData["requestmsg"]  ) : "<p> Bonjour Mr/Mrs ".$client->lastname." ".$client->firstname." <br/> </p>
				                                                                                                               <p> Votre demande d'accès au type de document ".$typedocument." du RCCM N° ".$registre->numero." n'a pas été validée. <br/> </p>
				                                                                                                               <p> Nous vous prions de bien vouloir prendre contact avec l'administrateur du FNRCCM pour plus d'informations. </p>
				                                                                                                               <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
				$msgPartialData   = array("subject"        =>  $msgSubject,
						                  "message"        =>  $clientMsg,
						                  "logoMsg"        =>  APPLICATION_STRUCTURE_LOGO,
						                  "replyToEmail"   =>  $defaultToEmail,
						                  "replyToName"    =>  $defaultToName,
						                  "replyToTel"     =>  "",
						                  "replyToSiteWeb" =>  "http://www.siraah.net/about",
						                  "toName"         =>  sprintf("%s %s", $client->lastname, $client->firstname),
						                  "toEmail"        =>  $client->email );
				$msgBody        = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
				$mailer->setFrom( $defaultToEmail , "FNRCCM ");
				$mailer->setSubject( $msgSubject );
				$mailer->addTo( $client->email , stripslashes( $client->lastname ) );
				$mailer->setBodyHtml( $msgBody );
				try{
					$mailer->send();
				} catch(Exception $e) {
					$errorMessages[]     = "Nous avons tenté de transmettre un email au requérant,son compte de messagerie semble inaccessible,vérifiez votre connexion internet et reprenez l'opération.";
					if( APPLICATION_DEBUG ) {
						$errorMessages[] = " Informations de débogages : ".$e->getMessage();
					}
				}
				if( empty( $errorMessages )) {
					$requete->validated     = 2;
					$requete->accepted      = 1;
					$requete->updatedate    = time();
					$requete->updateduserid = $me->userid;
				    $requete->response      = (isset($postData["requestresponse"])) ? $stringFilter->filter( $postData["requestresponse"]): $clientMsg;
					if( $requete->save() ) {
						$this->setRedirect(sprintf("Le traitement de la demande d'accès numéro %s a été enregistré avec succès. Un email a été transmis au requérant", $requete->requestoken) , "success");
						$this->redirect("admin/requetes/list");
					} else {
						$errorMessages[]= "Le traitement de la requête a echoué pour des raisons inconnues";
					}
				}
			}
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}		
		$this->view->requete   = $requete;
		$this->view->registre  = $registre;
		$this->view->requestid = $requestid;
		$this->view->validated = $validated;
		$this->view->title     = sprintf("Opération de traitement de la requête numéro %s", $requete->requestoken)	;	
		if( intval( $validated ) == 1 ) {
			$this->view->requestSubject = sprintf("FNRCCM : Votre demande d'accès numéro %s a été validée", $requete->requestoken );
			$this->view->requestMessage = "<p> Bonjour Mr/Mrs ".$client->lastname." ".$client->firstname." <br/> </p>
					                       <p> Votre requête de demande d'accès au registre N° ".$registre->numero." a favorablement été traitée. <br/> </p>
					                       <p> Nous vous invitons à vous connecter à votre compte pour télécharger le document </p>
					                       <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
			$this->render("validation");
		} else {
			$this->view->requestSubject = sprintf("Votre demande d'accès aux informations du RCCM %s", $registre->numero );
			$this->view->requestMessage = "<p> Bonjour Mr/Mrs ".$client->lastname." ".$client->firstname." <br/> </p>
				                           <p> Votre demande d'accès au type de document ".$typedocument." du RCCM N° ".$registre->numero." n'a pas été validée. <br/> </p>
				                           <p> Nous vous prions de bien vouloir prendre contact avec l'administrateur du FNRCCM pour plus d'informations. </p>
				                           <p> <b><i> Cordialement, ".stripslashes($defaultToName)." </i></b> </p>";
			$this->render("reject");
		}
	}
	
	
	public function infosAction()
	{
		$requestid        = intval($this->_getParam("requestid", $this->_getParam("id" , 0)));
		if(!$requestid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}
		$model                 = $this->getModel("requete");
		$modelRegistre         = $this->getModel("registre");
		
		$requete               = $model->findRow( $requestid, "requestid" , null , false);	
		if(!$requete ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}	     
		$registre                   = $modelRegistre->findRow( $requete->registreid, "registreid", null , false );	 
        $this->view->requete        = $requete;
		$this->view->registre       = $registre;
		$this->view->typesdocuments = $requete->typedocuments();
		$this->view->client         = Sirah_Fabric::getUser( $requete->userid );
		$this->view->requestid      = $requestid;
	}
	
	
	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$requestid        = intval($this->_getParam("requestid", $this->_getParam("id" , 0)));
		if(!$requestid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}
		$model                 = $this->getModel("requete");
		$modelRegistre         = $this->getModel("registre");
	
		$requete               = $model->findRow( $requestid, "requestid" , null , false);
		$me                    = Sirah_Fabric::getUser();
		if(!$requete ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/requetes/list");
		}
		if( ( $requete->validated != 1 ) || ( $me->userid != $requete->userid ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Vous n'êtes pas autorisé à télécharger ce document"));
				exit;
			}
			$this->setRedirect("Vous n'êtes pas autorisé à télécharger ce document", "error");
			$this->redirect("admin/requetes/list");
		}
		$documents               = $requete->documents( $requestid );
		if( !count( $documents )) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Le document auquel vous souhaitez accéder est indisponible."));
				exit;
			}
			$this->setRedirect("Le document auquel vous souhaitez accéder est indisponible.", "error");
			$this->redirect("admin/requetes/list");
		}
		$filename                = $documents[0]["filepath"];
		if( !file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'existe plus sur le serveur" , "error");
			$this->redirect("admin/requetes/list");
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
	 
	 public function deleteAction()
	 {
	 	$this->_helper->viewRenderer->setNoRender(true);
	 	$model         = $this->getModel("requete");
	 	$dbAdapter     = $model->getTable()->getAdapter();
	 	$prefixName    = $model->getTable()->info("namePrefix");
	 	$ids           = $this->_getParam("requestids", $this->_getParam("ids",array()));
	 	$errorMessages = array();
	 	if( is_string($ids) ) {
	 		$ids  = explode("," , $ids );
	 	}
	 	$ids      = (array)$ids;
	 	if(count($ids)) {
	 		foreach(  $ids as $id) {
	 			$requete                  = $model->findRow( $id , "requestid" , null , false );
	 			if( $requete  ) {
	 				if(!$requete->delete()) {
	 					$errorMessages[]  = " Erreur de la base de donnée : La requête id#$id n'a pas été supprimée";
	 				} else {
	 					$dbAdapter->delete($prefixName."rccm_access_requests_documents"   , "requestid=".$id);
	 				}
	 			} else {
	 				$errorMessages[]      = "Aucune entrée valide n'a été trouvée pour le registre #$id ";
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
	 		$this->redirect("admin/requetes/list");
	 	} else {
	 		if( $this->_request->isXmlHttpRequest() ) {
	 			echo ZendX_JQuery::encodeJson(array("success" => "Les demandes d'accès selectionnées ont été supprimées avec succès"));
	 			exit;
	 		}
	 		$this->setRedirect("Les demandes d'accès selectionnées ont été supprimées avec succès", "success");
	 		$this->redirect("admin/requetes/list");
	 	}
	 }
	
}