<?php


class Admin_UserdocumentsController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{
		$this->_helper->layout->setLayout("base");
		$this->view->title  = "Liste des documents des utilisateurs"  ;
		
		$model              = $this->getModel("document");
		$modelCategory      = $this->getModel("documentcategorie");
		$documents          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));		
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;
		$defaultFilename      = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters              = array("filename"        => $defaultFilename ,
				                      "userid"          => null,
				                      "filemetadata"    => null,
				                      "filedescription" => null,
				                      "filextension"    => null,
				                      "category"        => null,
				                      "username"        => null,
				                      "lastname"        => null,
				                      "firstname"       => null,
				                      "email"           => null );		
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$documents          = $model->getList($filters , $pageNum , $pageSize);
		$paginator          = $model->getListPaginator($filters);
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns     = array("left");
		$this->view->documents   = $documents;
		$this->view->categories  = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;				
	}
	
	public function createAction()
	{
		$this->view->title         = "Enregistrer un nouveau document";		
		$me                        = Sirah_Fabric::getUser();		
		$userid                    = intval($this->_getParam("userid" , $me->userid ));
		$done                      = $this->_getParam("done" , null );
		$model                     = $this->getModel("document");
		$modelCategory             = $this->getModel("documentcategorie");
		$defaultData               = $model->getEmptyData();
		$user                      = Sirah_Fabric::getUser( $userid );
		$userDataPath              = $user->getDatapath();
		$errorMessages             = array();
		$uploadedFiles             = array();
		
		if( $this->_request->isPost() ) {
			$postData              = $this->_request->getPost();
			$formData              = array_intersect_key( $postData ,  $defaultData )	;
			$documentData          = array_merge( $defaultData ,  $formData );
			$userTable             = $user->getTable();
			$dbAdapter             = $userTable->getAdapter();
			$prefixName            = $userTable->info("namePrefix");
			if( !is_dir( $userDataPath ) ) {
				$errorMessages[]   = "Le dossier de stockage des documents de l'utilisateur n'est pas valide ";			
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			//On crée un validateur de filtre
			$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			
			$documentData["userid"]         = $userid;
			$documentData["category"]       = intval( $documentData["category"] );
			$documentData["resource"]       = ( isset( $postData["resource"] ) )   ? $stringFilter->filter($postData["resource"]) : "" ;
			$documentData["resourceid"]     = ( isset( $postData["resourceid"] ) ) ? intval($postData["resourceid"]) : 0 ;
			$documentData["filedescription"]= $stringFilter->filter( $documentData["filedescription"] );
			$documentData["filemetadata"]   = $stringFilter->filter( $documentData["filemetadata"] );
			
			$userMaxFileSize                = 32;
			$userMaxUploadFileSize          = 25;
			$userSingleFileSize             = 5;
			$userTotalFiles                 = 10;
			
			$documentsUpload                = new Zend_File_Transfer();
			$documentsUpload->addValidator("Count"      , false , $userTotalFiles );
			$documentsUpload->addValidator("Extension"  , false , array("csv" , "xls" , "xlxs" , "pdf" , "png" , "gif" , "jpg" , "docx" , "doc" , "xml"));
			$documentsUpload->addValidator("Size"       , false , array("max"  => $userSingleFileSize."MB"));
			$documentsUpload->addValidator("FilesSize"  , false , array("max"  => $userMaxUploadFileSize."MB"));			
			$files                 = $documentsUpload->getFileInfo();		
			if( count( $files ) && empty( $errorMessages ) ) {
				foreach( $files as $file  => $info ) {
					if( !$documentsUpload->isValid( $file) ) {
						 $errorMessages[] = sprintf("Le fichier %s n'est pas valide, il n'a pas été chargé " , $info["name"] );	
						 continue;					
					}
					$userDocumentPath     = $userDataPath . $info["name"];
					$tmpFilename          = Sirah_Filesystem::getName( $info["tmp_name"] );
					$documentsUpload->addFilter("Rename" , array("target" => $userDocumentPath , "overwrite" => true) , $info["name"]);
					$documentsUpload->receive( $file );					
					if( $documentsUpload->isReceived( $file ) ) {
						$documentData["filename"]     = $model->rename( $tmpFilename , $userid );
						$documentData["filepath"]     = $userDocumentPath ;
						$documentData["filextension"] = Sirah_Filesystem::getFilextension( $userDocumentPath );
						$documentData["creationdate"] = time();
						$documentData["creatoruserid"]= $me->userid;
						if( $dbAdapter->insert( $prefixName . "system_users_documents"  , $documentData ) ) {
							$documentid                 = $dbAdapter->lastInsertId();
							$uploadedFiles[$documentid] = $documentData;
						}
					} else {
						$errorMessages[]                = sprintf(" Le fichier %s n'a pas été chargé sur le serveur " , $info["name"] );
					}
				}
			} else {
				$errorMessages[] = " Aucun fichier valide n'a été chargé sur le serveur ";
			}	
			$uploadMessages      = $documentsUpload->getMessages();
			if( count( $uploadMessages ) ) {
				foreach( $uploadMessages as $key => $errorCode ) {
						 $errorMessages[]  = Sirah_Controller_Default::getUploadMessage( $errorCode );
				}
			}			
			if( empty($errorMessages ) ) {
				if( ( false == $done ) || ( null == $done ) || !Zend_Uri::check( $done ) ) {
					if( $this->_request->isXmlHttpRequest() ) {
						$jsonArray           = array();
						$jsonArray["files"]  = $uploadedFiles;
						$jsonArray["success"]= "Les documents de l'utilisateur ont été chargés avec succès";
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout( true );
						echo ZendX_JQuery::encodeJson($jsonArray);
						exit;
					}
					$this->_helper->Message->addMessage( "Les documents de l'utilisateur ont été chargés avec succès" , "success");
				} else {
					$documentIds    = array_keys( $uploadedFiles );
					$documentIdsStr = "ids-" . implode("-" , $documentIds );
					$donePage       = Zend_Navigation_Page::factory(array("type" => "uri" , "label" => $done , "uri" => $done , "params" => array( "docs" => $documentIdsStr ) ));
					$forrwardLink   = $donePage->getHref();
					$this->_redirect( $forrwardLink );
				}
			} else {
				$defaultData        = $postData;
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout( true );
					echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages )));
					exit;
				}
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage,"error");
				}
			}
		}		
		$this->view->userid      = $userid;
		$this->view->done        = $done;
		$this->view->categories  = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
		$this->view->data        = $defaultData;		
	}
	
	public function editAction()
	{
		
	
	}
	
		
	public function infosAction()
	{			
		$documentid             = intval( $this->_getParam("documentid", $this->_getParam("id" , 0 )));
		$decodedFile            = $file = utf8_decode(rawurldecode($this->_getParam("file" , $this->_getParam("doc", ""))));
		$dialogTitle            = utf8_decode(rawurldecode($this->_getParam("title" , $this->_getParam("dialogTitle", ""))));
		$modelDocument          = $this->getModel("document");
		$document               = $modelDocument->findRow( $documentid , "documentid" , null , false );
 	
		if( !$document  && !file_exists($file) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger est invalide") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger est invalide" , "error");
			$this->redirect("userdocuments/list");
		}		
		$filename                    = ( $document ) ? $document->filepath : $file;
		if(!$document && $file) {
			$document                = new stdClass();
			$document->filepath      = $file;
			$document->creatoruserid = $me->userid;
			$document->creationdate  = time();
			$document->filename      = Sirah_Filesystem::getName($file);
			$document->libelle       = Sirah_Filesystem::getName($file);
		}
		$fileDocumentRoot            = substr($filename, 0, stripos($filename , "privatedata"));
		$filename                    = str_replace($fileDocumentRoot,DOCUMENTS_PATH.DS,$filename );
        //print_r($fileDocumentRoot);die();		
		if(!file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document auquel vous souhaitez accéder, n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document auquel vous souhaitez accéder, n'existe plus sur le serveur" , "error");
			$this->redirect("userdocuments/list");
		}
		$documentExtension      = strtolower( Sirah_Filesystem::getFilextension( $filename ) );		
		if( $documentExtension == "png" || $documentExtension == "jpeg" || $documentExtension == "jpg" || $documentExtension == "gif" || $documentExtension == "bmp" ) {
			$imgFile  = str_replace( APPLICATION_PATH , ROOT_PATH . DS ."myV1"  ,  $filename );			
			$imgFile  = str_replace( DS , "/" , $imgFile );
			echo  "<div class=\"row col-md-12\"> ";
			echo  "    <img src=\"".$imgFile."\" />";
			echo  "</div>";
			exit;
	    } else if( $documentExtension == "pdf" ) {
			require_once("tcpdf/tcpdf.php");
		    require_once("Fpdi/fpdi.php");
			$pdfData                 = "";
			try{
			    $pdfDocument         = new FPDI();
			    $pdfFileNbPages      = $pageCount = $pdfDocument->setSourceFile($filename);
				for($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
					// import a page
					$templateId      = $pdfDocument->importPage($pageNo,"MediaBox");
					$size            = $pdfDocument->getTemplateSize($templateId);
					$pdfDocument->SetMargins(0,0,0);
					$pdfDocument->SetPrintHeader(false);
		            $pdfDocument->SetPrintFooter(false);
					$pdfDocument->AddPage($size['orientation'], $size);
					// use the imported page and adjust the page size
					$pdfDocument->useTemplate($templateId, null, null, 0, 0, true);
				}
				
				$pdfData             = base64_encode($pdfDocument->Output("", "S"));
			    $libelle             = $document->filename;
			} catch(Exception $e) {
				$pdfFileNbPages      = 0;  
                $libelle             = $document->filename;		
              	$pdfData	         = base64_encode(file_get_contents($filename));
			}
			$this->view->dialogTitle = (!empty($dialogTitle )) ? $dialogTitle : $libelle;
			$this->view->document    = $document;
			$this->view->filepath    = $filename;
			$this->view->filedata    = $pdfData;
			$this->view->nbPages     = $pdfFileNbPages;
			$this->render("pdfiframe");
		} else {
			$this->render("docinfos");	
		}			     	
	}

	public function downloadAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout( true );
		
		$documentid             = intval( $this->_getParam("documentid" , $this->_getParam("id" , 0 ) ) );
		$modelDocument          = $this->getModel("document");		
		$document               = $modelDocument->findRow( $documentid , "documentid" , null , false );
		
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger est invalide") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger est invalide" , "error");
			$this->redirect("admin/userdocuments/list");
		}		
		$filename                = $document->filepath;
		if( !file_exists( $filename ) ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson( array("error" => "Le document que vous souhaitez télécharger n'existe plus sur le serveur") );
				exit;
			}
			$this->setRedirect("Le document que vous souhaitez télécharger n'existe plus sur le serveur" , "error");
			$this->redirect("admin/userdocuments/list");
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
	    $this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout(true);
		$documentid     = intval( $this->_getParam("documentid" , $this->_getParam("id" , 0 )));
		$me             = Sirah_Fabric::getUser( );
		if( !$documentid ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("index/index");
		}
		$model        = $this->getModel("document");
		$document     = $model->findRow( $documentid , "documentid" , null , false );
		if( !$document ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("index/index");
		} 
		$modelTable = $model->getTable();
		$dbAdapter  = $modelTable->getAdapter();
		$tableName  = $modelTable->info("name");	
		$filepath   = $document->filepath;		
		if( $dbAdapter->delete($tableName,array("documentid=?"=>$documentid)) ) {
			@unlink( $filepath );
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "La suppression du document s'est effectuée avec succès "));
				exit;
			}
			$this->setRedirect("La suppression du document s'est effectuée avec succès " , "success");
			$this->redirect("index/index");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "La suppressionn du document a echoué , veuillez réessayer"));
				exit;
			}
			$this->setRedirect( "La suppression du document a echoué , veuillez réessayer" , "error");
			$this->redirect("index/index");
		}
		
	}



}