<?php

class Admin_ArticlesController extends Sirah_Controller_Default
{
	
	 
		
	public function listAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}		
		$this->view->title          = "Gestion des articles ";
		
		$model                      = $this->getModel("article");
		$modelCategory              = $this->getModel("articlecategorie");
		$modelGallery               = $this->getModel("gallery");
		$modelDocument              = $this->getModel("document");
		$articles                   = array();
		$paginator                  = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter               = new Zend_Filter();
		$stringFilter->addFilter(     new Zend_Filter_StringTrim());
		$stringFilter->addFilter(     new Zend_Filter_StripTags());		
		//On crée un validateur de filtre
		$strNotEmptyValidator       = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));		
		$params                     = $this->_request->getParams();
		$pageNum                    = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize                   = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$defaultFilter              = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters                    = array("libelle"=>$defaultFilter,"catid"=>0,"published"=> false,"periodstart_day"=>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"  =>0);		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_month"]))
				&&
			(isset($filters["periodend_day"]  ) && intval($filters["periodend_day"]))   && (isset($filters["periodstart_day"])   && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=> $filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=> $filters["periodend_year"],"month"=> $filters["periodend_month"]  ,"day"=> $filters["periodend_day"]   ));
			$filters["periodstart"] = ($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periodend"]   = ($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
		}
		$articles                  = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                  = $model->getListPaginator( $filters );
		if( null !== $paginator)  {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage( $pageSize);
		}
		$this->view->columns        = array("left");
		$this->view->articles       = $articles;
		$this->view->categories     = $modelCategory->getSelectListe("Selectionnez une catégorie",array("catid","title"),array(),0,null,false);
		$this->view->filters        = $filters;
		$this->view->paginator      = $paginator;						
	}
	
	public function createAction()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		$this->view->title          = " Enregistrer un article";
		
		$model                      = $this->getModel("article");
		$modelCategory              = $this->getModel("articlecategorie");
		 
		$modelGallery               = $this->getModel("gallery");
		$modelDocument              = $this->getModel("document");
		 
		
		$articleCatId              = intval($this->_getParam("catid"    , $this->_getParam("category", 0)));
	 
		
		$defaultData                = array("catid"=>$articleCatId,"code"=>"","title"=>"","introtext"=>null,"keywords"=>null,"content"=>null,				                            
				                            "galleryid"=>0,"image"=>"","gallery_libelle"=>null,"gallery_description"=> null);
		$this->view->categories     = $categories = $modelCategory->getSelectListe("Selectionnez une catégorie", array("catid","title"), array(), 0 , null , false);
		$category                   = ( $articleCatId )?$modelCategory->findRow($articleCatId, "catid", null, false ) : null;
		$errorMessages              = array();
		$article                    = $articleid = null;

		if( $this->_request->isPost() ) {
			$postData               = $this->_request->getPost();
			$cleanArticleData       = $model->getEmptyData();
			$cleanPostData          = array_intersect_key( $postData, $cleanArticleData);
			$article_data           = array_merge($cleanArticleData, $cleanPostData    );
			
			$me                     = Sirah_Fabric::getUser();
			$modelTable             = $model->getTable();
			$dbAdapter              = $modelTable->getAdapter();
			$prefixName             = $modelTable->info("namePrefix");
			$tableName              = $modelTable->info("name");
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter           = new Zend_Filter();
			$stringFilter->addFilter( new Zend_Filter_StringTrim());
			$stringFilter->addFilter( new Zend_Filter_StripTags() );
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator        = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		
		    $zendDateDebut               = $zendDateFin = null;
			$article_data["catid"]       = (isset($postData["catid"]) && isset($categories[$postData["catid"]]))?intval($postData["catid"])     : 0;
			$article_data["code"]        = (isset($postData["code"]       ))? $stringFilter->filter($postData["code"])        : $model->createCode(); 
			$article_data["title"]       = (isset($postData["title"]    ))? $stringFilter->filter($postData["title"])         : ""; 
			$article_data["content"]     = (isset($postData["content"]  ))? $postData["content"]                              : "";
			$article_data["introtext"]   = (isset($postData["introtext"]))? $stringFilter->filter($postData["introtext"])     : "";			
			
			//On vérifie la validité des données de l'article
			if(!$strNotEmptyValidator->isValid( $article_data["title"] ) ) {
				$errorMessages[]           = "Veuillez renseigner le titre de l'article";
			} else {
				$article_data["title"]     = $stringFilter->filter( $article_data["title"] );
			}
			if(!$strNotEmptyValidator->isValid( $article_data["introtext"] ) && $strNotEmptyValidator->isValid( $article_data["content"] )) {
				$article_data["introtext"] = substr(strip_tags($article_data["content"]),0,200);
			}
            if(!intval($article_data["catid"]) || !isset($categories[$postData["catid"]])) {
				$errorMessages[]           = "Veuillez sélectionner une catégorie valide";
			}  			
			if(!$strNotEmptyValidator->isValid($article_data["code"]) ) {
				$errorMessages[]           = "Veuillez saisir un code valide";
			} elseif($codeExist = $model->findRow($article_data["code"], "code", null, false) ) {
				$errorMessages[]           = sprintf("Un article existant porte le code %s", $article_data["code"]);
			}
			$gallery_libelle               = (isset($postData["gallery_libelle"])) ? $stringFilter->filter( $postData["gallery_libelle"] ) : sprintf("Galerie photos de l'article %s", $article_data["title"]);
			if( !$strNotEmptyValidator->isValid( $gallery_libelle ) || ( $modelGallery->findRow( $gallery_libelle, "libelle", null, false )) ) {
				 $gallery_libelle          = $stringFilter->filter( $article_data["title"] ); 
			}
            $article_data["published"]     = (isset($postData["published"]  ))? intval($postData["published"]) : 1;
			$article_data["creationdate"]  = time();
			$article_data["publishdate"]   = (isset($postData["publishdate"]))? $postData["publishdate"]       : time();
			$article_data["date"]          = (isset($postData["date"]       ))? $postData["date"]              : time();
			$article_data["creatorid"]     = $me->userid;
			$article_data["alias"]         = (isset($postData["alias"]      ))? $stringFilter->filter($postData["alias"])      : "";
			$article_data["keywords"]      = (isset($postData["keywords"]   ))? $stringFilter->filter($postData["keywords"])   : "";
			$article_data["photogarde"]    = (isset($postData["photogarde"] ))? $stringFilter->filter($postData["photogarde"]) : "";
			$article_data["galleryid"]     = 0;
			$article_data["updatedate"]    = 0;	
			$article_data["updateduserid"] = 0;
									
			if( empty( $errorMessages ) )   {
				$articleData               = array_intersect_key($article_data, $cleanArticleData);
				$insertArticleData         = array_merge($cleanArticleData, $articleData);
								
				//On enregistre l'article
				if( $dbAdapter->insert($tableName, $insertArticleData ) ) {
					require_once("Upload/upload.php");
					$articleid             = $dbAdapter->lastInsertId();
					$article               = $model->findRow( $articleid, "articleid", null , false );
					
					//On enregistrer la photo de garde
					$imagesUpload          = new Zend_File_Transfer();
					$imagesUpload->addValidator("Count"    , false , 1 );
					$imagesUpload->addValidator("Extension", false , array("bmp","png","gif","jpg","jpeg","PNG"));
					$imagesUpload->addValidator("Size"     , false , array("max"=>"15MB"));
					$imagesUpload->addValidator("FilesSize", false , array("max"=>"15MB"));
					
					$gallery_filepath      = APPLICATION_DATA_PATH. DS ."articles". DS . "galleries" . DS . "original" ;
					$imgFilepathroot       = APPLICATION_DATA_PATH. DS ."articles". DS . "photogarde". DS . "original";
					
					$photoGardeFilename    = preg_replace("/[^a-z0-9\.]/i","_",$imagesUpload->getFileName("photogarde", false ));
					$tmpFilename           = Sirah_Filesystem::getName($photoGardeFilename);
					$tmpFileExtension      = Sirah_Filesystem::getFilextension($imagesUpload->getFileName("photogarde", false ));
					$imgFilePath           = $imgFilepathroot . DS .$photoGardeFilename;
					$photoGardePath        = $gallery_filepath. DS .$photoGardeFilename;
					$imagesUpload->addFilter("Rename", array("target" => $imgFilePath, "overwrite" => true), $imgFilePath );
					if( $imagesUpload->isUploaded("photogarde")) {
						$imagesUpload->receive(   "photogarde");
					}
					if( $imagesUpload->isReceived("photogarde") ) {
						//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
						
						$miniImagesPaths  = array(APPLICATION_DATA_PATH.DS."articles".DS."galleries" .DS."mini",
												  APPLICATION_DATA_PATH.DS."articles".DS."photogarde".DS."mini");
						$thumbImagesPaths = array(APPLICATION_DATA_PATH.DS."articles".DS."galleries" .DS."thumbs",
												  APPLICATION_DATA_PATH.DS."articles".DS."photogarde".DS."thumbs");	
                        foreach( $thumbImagesPaths as $thumbImagesPath ) {
							     $imageHandle                     = new Upload($imgFilePath);
								 $imageHandle->jpeg_quality       = 100;
								 $imageHandle->file_auto_rename   = false;
								 $imageHandle->file_overwrite     = true;
								 $imageHandle->image_y            = 200;
								 $imageHandle->image_x            = 300;
								 $imageHandle->file_new_name_body = $tmpFilename;
								 $imageHandle->Process($thumbImagesPath);	
						}	
                        foreach( $thumbImagesPaths as $miniImagesPath ) {
							     $imageHandle                     = new Upload($imgFilePath);
								 $imageHandle->jpeg_quality       = 100;
								 $imageHandle->file_auto_rename   = false;
								 $imageHandle->file_overwrite     = true;
								 $imageHandle->image_y            = 250;
								 $imageHandle->image_x            = 350;
								 $imageHandle->file_new_name_body = $tmpFilename;
								 $imageHandle->Process($miniImagesPath);	
						}						
						$article->photogarde    = $photoGardeFilename;
						$article->save();
					}						
					//On enregistre la gallerie photos
					$gallery_description       = $article->content;					  
					$gallery_row               = array("articleid"=>$articleid,"libelle"=>$gallery_libelle,"description"=>$gallery_description,"updatedate"=>0,"filepath"=>$gallery_filepath, "creationdate" => time(),"creatorid"=> $me->userid, "updateduserid" => 0 );
					if( $dbAdapter->insert( $prefixName ."erccm_crm_content_gallery", $gallery_row ) ) {
					  	$galleryid             = $dbAdapter->lastInsertId();
					  	$article->galleryid    = $galleryid;					  	  
					  	$article->save();
					    if( $imagesUpload->isReceived("photogarde") ) {
					  	  	$photoGardeRow     = array("articleid"=>$articleid,"galleryid"=>$galleryid,"libelle"=>$article->title.":Photo de garde","description"=>$article->introtext,"filepath"=>$photoGardePath,"creationdate"=>time(),"creatorid"=> $me->userid );
					  	  	$dbAdapter->insert($prefixName."erccm_crm_content_gallery_photos", $photoGardeRow );
					  	}					  	  					  	  					  	 
					  	for( $p = 1; $p <= 5; $p++ ) {
					  	  	   if(!isset( $postData["gallery_photos_title".$p] ) ) {
					  	  	   	   continue;
					  	  	   }
					  	  	   $fileInput       = "articlePhotos".$p ;
					  	  	   $fileTitle       = (!empty($postData["gallery_photos_title".$p]))? $postData["gallery_photos_title".$p] : "Photo".$p;
					  	  	   $fileDescription = (!empty($postData["gallery_photos_desc".$p] ))? $postData["gallery_photos_desc".$p] : "Photo".$p;
							   
					  	  	   $imagesUpload    = new Zend_File_Transfer();
					  	  	   $imagesUpload->addValidator("Count"    , false , 1 );
					  	  	   $imagesUpload->addValidator("Extension", false , array("bmp", "png", "gif", "jpg", "jpeg", "PNG"));
					  	  	   $imagesUpload->addValidator("Size"     , false , array("max" => "25MB"));
					  	  	   $imagesUpload->addValidator("FilesSize", false , array("max" => "25MB"));
					  	  	   $basicFilename   = preg_replace("/[^a-z0-9\.]/i","_",$imagesUpload->getFileName($fileInput,false ));
					  	  	   $imgFilePath     = $gallery_filepath .  DS .$basicFilename;
					  	  	   $imagesUpload->addFilter("Rename", array("target" => $imgFilePath, "overwrite" => true), $fileInput);
					  	  	   if(!$imagesUpload->isUploaded( $fileInput )) {
					  	  	   	   continue;
					  	  	   }
					  	  	   $imagesUpload->receive(       $fileInput );
					  	  	   if( $imagesUpload->isReceived( $fileInput ) ) {
								   $miniImagesPaths  = array(APPLICATION_DATA_PATH.DS."articles".DS."galleries" .DS."mini");
								   $thumbImagesPaths = array(APPLICATION_DATA_PATH.DS."articles".DS."galleries" .DS."thumbs");	
								   foreach( $thumbImagesPaths as $thumbImagesPath ) {
											$imageHandle                     = new Upload($imgFilePath);
										    $imageHandle->jpeg_quality       = 100;
										    $imageHandle->file_auto_rename   = false;
										    $imageHandle->file_overwrite     = true;
										    $imageHandle->image_y            = 200;
										    $imageHandle->image_x            = 300;
										    $imageHandle->file_new_name_body = $basicFilename;
										    $imageHandle->Process($thumbImagesPath);	
								  }	
								  foreach(  $miniImagesPaths as $miniImagesPath ) {
										    $imageHandle                     = new Upload($imgFilePath);
										    $imageHandle->jpeg_quality       = 100;
										    $imageHandle->file_auto_rename   = false;
										    $imageHandle->file_overwrite     = true;
										    $imageHandle->image_y            = 250;
										    $imageHandle->image_x            = 350;
										    $imageHandle->file_new_name_body = $basicFilename;
										    $imageHandle->Process($miniImagesPath);	
								  }
									  
								  $photoRow   = array("articleid"=>$articleid,"galleryid"=>$galleryid,"libelle"=>$fileTitle,"description"=>$fileDescription,"filepath"=> $imgFilePath,"creationdate"=>time(),"creatorid" => $me->userid );
					  	  	   	  $dbAdapter->insert( $prefixName . "erccm_crm_content_gallery_photos", $photoRow );
					  	  	    }
					  	}					  	  
					  	  //On enregistre la vidéo s'il y'en a
					  	$videoUpload                = new Zend_File_Transfer();
					  	$videoUpload->addValidator("Count"    , false , 1 );
					  	$videoUpload->addValidator("Extension", false , array("avi", "flv", "wma", "mpeg", "mp4", "mp3","mpg","mov"));
					  	$videoUpload->addValidator("Size"     , false , array("max" => "20MB"));
					  	$videoUpload->addValidator("FilesSize", false , array("max" => "20MB"));
					  	$basicFilename = preg_replace("/[^a-z0-9\.]/i","_",$videoUpload->getFileName("gallery_video", false ));
					  	$videoFilePath = $gallery_filepath .  DS .$basicFilename;
					  	$videoUpload->addFilter("Rename", array("target" => $videoFilePath, "overwrite" => true), "gallery_video");
					  	if( $videoUpload->isUploaded("gallery_video") ) {
					  	  	$videoUpload->receive(   "gallery_video");
					  	  	if( $videoUpload->isReceived("gallery_video") ) {
					  	  	  	$videoTitle  = ( isset( $postData["gallery_video_titre"] ) && !empty( $postData["gallery_video_titre"])) ?$postData["gallery_video_titre"] : $article->title;
					  	  	  	$videoDbRow  = array("articleid"=>$articleid,"galleryid"=>$galleryid,"libelle"=> $videoTitle,"description"=>$videoTitle, "filepath"=>$videoFilePath,"creationdate"=>time(),"creatorid"=> $me->userid );
					  	  	  	$dbAdapter->insert( $prefixName."erccm_crm_content_gallery_videos", $videoDbRow );
					  	  	}
					  	}					  	  
					  }	//On finit d'enregistrer la gallerie photos		
	                  
                      $documentsPath         = APPLICATION_DATA_PATH . DS . "articles" . DS ."documents";
					  $documentUpload        = new Zend_File_Transfer();
					  $documentUpload->addValidator('Count' , false , 2 );
					  $documentUpload->addValidator("Extension", false, array("png","jpg","jpeg","gif","bmp","PNG","JPEG","JPG","pdf","PDF"));
					  $documentUpload->addValidator("FilesSize", false, array("max" => "20MB"));
					  $documentExtension     = Sirah_Filesystem::getFilextension($documentUpload->getFileName('articledoc'));
					  $basicFilename         = Sirah_Filesystem::getName($documentUpload->getFileName('articledoc'));
					  $documentFilepath      = $documentsPath. DS . strtoupper($article->code) ."Doc_".$basicFilename.".".$documentExtension;
					  if( $documentUpload->isUploaded("articledoc") ) {						  
						  $documentUpload->addFilter("Rename", array("target" =>$documentFilepath,"overwrite"=> true) , "articledoc");
						  $documentUpload->receive("articledoc") ;
					  }
					  if( $documentUpload->isReceived("articledoc") ) {
						  $myFilename                        = "ArticleDoc_".$basicFilename;
						  $fileSize                          = $documentUpload->getFileSize("articledoc");
						  $documentData                      = $modelDocument->getEmptyData();
						  $articleDocData                    = array("articleid"=>$articleid);
						  $documentData["userid"]            = $me->userid;
						  $documentData["category"]          = 15;
						  $documentData["resource"]          = "articles" ;
						  $documentData["resourceid"]        = 0;
						  $documentData["filedescription"]   = $articleDocData["description"] = $stringFilter->filter( $article->introtext );
						  $documentData["filemetadata"]      = $articleDocData["keywords"]    = $article->keywords;						  				          
						  $documentData["filename"]          = $articleDocData["title"]       = $modelDocument->rename($myFilename, $me->userid );
						  $documentData["filepath"]          = $documentFilepath;
						  $documentData["filextension"]      = $documentExtension;
						  $documentData["filesize"]          = floatval($fileSize);
						  $documentData["creationdate"]      = $articleDocData["creationdate"]= time();
						  $documentData["creatoruserid"]     = $articleDocData["creatorid"]   =  $me->userid;
						  if( $dbAdapter->insert( $prefixName."system_users_documents", $documentData ) ) {
					          $documentid                    = $dbAdapter->lastInsertId();
							  $articleDocData["documentid"]  = $documentid;
							  $articleDocData["updatedate"]  = $articleDocData["updateduserid"] = 0;
                              $dbAdapter->insert($prefixName."erccm_crm_content_articles_documents",$articleDocData);
						  }
					  }					  
			    }				
			}  			
			if( count( $errorMessages ) ) {
				$defaultData                                          = array_merge($defaultData, $postData);
				if( $article && $articleid ) {
					$galleryid                                        = $article->galleryid;
					if( $article->delete() ) {
						if( $dbAdapter->delete($prefixName."erccm_crm_content_gallery"       , array("articleid=?"=>intval($articleid))) &&  $galleryid) {
							$dbAdapter->delete($prefixName."erccm_crm_content_gallery_photos", array("galleryid=?"=>intval($galleryid)));
						    $dbAdapter->delete($prefixName."erccm_crm_content_gallery_videos", array("galleryid=?"=>intval($galleryid)));
						}						
						$dbAdapter->delete($prefixName."erccm_crm_content_articles_documents", array("articleid=?"=>intval($articleid)));
					}
				}
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
				if(!$article ) {
					$errorMessage       = "L'article n'a pas pu être enregistré pour des raisons inconnues";
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => $errorMessage ));
						exit;
					}
					$this->setRedirect( $errorMessage, "error");
					$this->redirect("admin/articles/list");
				} else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonSuccessMsg = array("article"=> $article->toArray(),"success"=> "Les informations de l'article ont été enregistrées avec succès");
						echo ZendX_JQuery::encodeJson( $jsonSuccessMsg );
						exit;
					}
					$this->setRedirect("Les informations de l'article ont été enregistrées avec succès" , "success");
					$this->redirect("admin/articles/infos/articleid/".$articleid);
				}				
			}			
		}						

		$this->view->data            = $defaultData;
		$this->view->catid           = $articleCatId;
		$this->view->category        = $this->view->categorie = $category;
		$this->view->title           = "Enregistrer un article";
	}
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("default");
		$articleid      = intval($this->_getParam("id" , $this->_getParam("articleid" , 0 )));
		if(!$articleid) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}		
		$model                      = $this->getModel("article");
		$modelGallery               = $this->getModel("gallery");
		$article                   = $model->findRow( $articleid, "articleid", null , false );
		if(!$article) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"   => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}
		$this->view->article      = $article;		
		$this->view->articleid    = $articleid;
		$this->view->galleryid    = $article->galleryid;	
		$this->view->categorie    = $category  = $article->findParentRow("Table_Articlecategories");
		$this->view->gallery      = $article->gallery();
		$this->view->photos       = $article->photos();
		$this->view->videos       = $article->videos();
		$this->view->documents    = $article->documents();
		$this->view->videoSources = $videoSources = array(0=>"Vidéo locale","youtube"=>"Vidéo Youtube","vimeo"=>"Vidéo Viméo","dailymotion"=>"Vidéo dailymotion");
		
		$this->view->title        = sprintf("Résumé des informations de l'article N°: %s", $article->code); 
	}
	
	
	public function editAction()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		$articleid                = $id = intval($this->_getParam("id" , $this->_getParam("articleid", 0 )));
		if(!$articleid) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}
		$model                      = $this->getModel("article");
		$modelCategory              = $this->getModel("articlecategorie");		
		$modelGallery               = $this->getModel("gallery");
		$modelDocument              = $this->getModel("document");
		$article                    = $model->findRow( $articleid, "articleid", null , false );
		if(!$article) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}	      			
		$errorMessages               = array();
		$defaultData                 = $articleData = $article->toArray();
		
		$articleCatId                = intval($this->_getParam("catid"    , $this->_getParam("category", $article->catid)));
		$emptyDefaultData            = array("catid"=>$articleCatId,"code"=>"","title"=>"","introtext"=>null,"alias"=>null,"keywords"=>null,"content"=>null,"galleryid"=>0,"image"=>"","gallery_libelle"=>null,"gallery_description"=> null);
		
		$defaultData                 = array_merge($emptyDefaultData, $articleData);
		$this->view->categories      = $categories = $modelCategory->getSelectListe("Selectionnez une catégorie",array("catid","title"), array(), 0 , null , false);
		$category                    = ( $articleCatId )?$modelCategory->findRow($articleCatId, "catid"    , null, false ) : null;
		$errorMessages               = array();
		
		if( $this->_request->isPost() ) {			
			$postData                = $this->_request->getPost();
			//On récupère les informations de l'article
			$cleanPostData           = array_intersect_key($postData, $defaultData);
			$formData                = array_merge( $defaultData, $cleanPostData );
			$updated_data            = $article_data = $formData;
				
			$me                      = Sirah_Fabric::getUser();
			$modelTable              = $model->getTable();
			$dbAdapter               = $modelTable->getAdapter();
			$prefixName              = $modelTable->info("namePrefix");
            $tableName               = $modelTable->info("name"); 			
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter            = new Zend_Filter();
			$stringFilter->addFilter(  new Zend_Filter_StringTrim());
			$stringFilter->addFilter(  new Zend_Filter_StripTags());
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator      = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$article_data["catid"]     = (isset($postData["catid"]) && isset($categories[$postData["catid"]] ))?intval($postData["catid"])   : $article->catid;
			$article_data["code"]      = (isset($postData["code"]     ))? $stringFilter->filter($postData["code"])    : $article->code; 
			$article_data["title"]     = (isset($postData["title"]    ))? $stringFilter->filter($postData["title"])   : $article->title; 
			$article_data["content"]   = (isset($postData["content"]  ))? $stringFilter->filter($postData["content"]) : $article->content;
			$article_data["published"] = (isset($postData["published"]))? intval($postData["published"])              : $article->published;

			//On vérifie la validité des données de l'article
			if(!$strNotEmptyValidator->isValid( $article_data["title"] ) ) {
				$errorMessages[]       = "Veuillez renseigner le titre de l'article";
			} else {
				$article_data["title"] = $stringFilter->filter( $article_data["title"] );
			}
            if(!intval($article_data["catid"]) || !$category) {
				$errorMessages[]       = "Veuillez sélectionner une catégorie valide";
			}           
			if(!$strNotEmptyValidator->isValid($article_data["code"]) ) {
				$errorMessages[]                        = "Veuillez saisir un code article valide";
			} elseif(($codeExist = $model->findRow($article_data["code"], "code", null, false)) && ($article_data["code"]!=$article->code)) {
				$errorMessages[]        = sprintf("Un article existante porte le code %s", $article_data["code"]);
			}
			
			//On récupère les informations de la gallerie photo pour enregistrer
			$gallery_libelle            = (isset($postData["gallery_libelle"] )) ? $stringFilter->filter( $postData["gallery_libelle"] ) : "";
			if( !$strNotEmptyValidator->isValid( $gallery_libelle) || ( $modelGallery->findRow( $gallery_libelle, "libelle", null, false )) ) {
				 $gallery_libelle          = $stringFilter->filter( $article_data["title"] ); 
			}
			$article_data["published"]     = (isset($postData["published"]))? intval($postData["published"]) : $article->published;
			$article_data["updatedate"]    = time();
			$article_data["updateduserid"] = $me->userid;
 		
			if( empty( $errorMessages ) ) {
				if( isset($article_data["articleid"])) {
					unset($article_data["articleid"]);
				}
				$article->setFromArray($article_data);				
				if( $article->save() ) {                  
                    			
					//On enregistrer la photo de garde
					$imagesUpload               = new Zend_File_Transfer();
					$imagesUpload->addValidator("Count"    , false , 1 );
					$imagesUpload->addValidator("Extension", false , array("bmp", "png","gif","jpg","jpeg","PNG"));
					$imagesUpload->addValidator("Size"     , false , array("max" =>"25MB"));
					$imagesUpload->addValidator("FilesSize", false , array("max" =>"25MB"));
						
					$imgFilepathroot            = APPLICATION_DATA_PATH. DS ."articles".DS."photogarde".DS. "original";
					$gallery_filepath           = APPLICATION_DATA_PATH. DS ."articles".DS."galleries" .DS. "original";
				    $photoGardeFilename         = preg_replace("/[^a-z0-9\.]/i","_",$imagesUpload->getFileName("photogarde", false ));
					$tmpFilename                = Sirah_Filesystem::getName( $photoGardeFilename );
 
					$imgFilePath                = $imgFilepathroot . DS . $photoGardeFilename;
					$photoGardePath             = $gallery_filepath. DS . $photoGardeFilename;
					
					$imagesUpload->addFilter("Rename", array("target" => $imgFilePath, "overwrite" => true), $imgFilePath );
					if( $imagesUpload->isUploaded("photogarde")) {
						$imagesUpload->receive(   "photogarde");
					} else {
						$errorMessages[] = $imagesUpload->getErrors();
					}
						
					if( $imagesUpload->isReceived("photogarde") ) {
						//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
						try {
 
							$photoImage  = new Sirah_Filesystem_Adapter_Image($imgFilePath , "rb+");
							//$photoImage->resize("800", null , true , $photoGardePath );
							//$photoImage->resizecrop(800,null,"auto",$photoGardePath);
							//$photoImage->resizecrop(350,250,"auto",APPLICATION_DATA_PATH. DS . "articles" . DS . "galleries" . DS . "mini");
							//$photoImage->resizecrop(350,250,"auto",APPLICATION_DATA_PATH. DS . "articles" . DS . "galleries" . DS . "thumbs");
							
							//$photoImage->resizecrop(350,250,"auto",APPLICATION_DATA_PATH. DS . "articles" . DS . "photogarde" . DS . "mini");
							//$photoImage->resizecrop(350,250,"auto",APPLICATION_DATA_PATH. DS . "articles" . DS . "photogarde" . DS . "thumbs");

							$article->photogarde   = $photoGardeFilename;
							$article->save();
 
						} catch(Exception $e ) {
							$errorMessages[] = $e->getMessage();
						}						
					}                     					                   				
				} else {
					$errorMessages[]           = "Les informations de l'article n'ont pas été mises à jour";
				}				
			}			
		    if( count( $errorMessages ) ) {
				$defaultData                   = array_merge($defaultData, $postData);
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message , "error") ;
				}
			} else {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonSuccessMsg = array( "article"=> $article->toArray(),"success"=>"Les informations de l'article ont été mises à jour avec succès");
					echo ZendX_JQuery::encodeJson( $jsonSuccessMsg );
					exit;
				}
				$this->setRedirect("Les informations de l'article ont été mises à jour avec succès" , "success");
				$this->redirect("admin/articles/infos/articleid/".$articleid);
		    }			
		}					

		$this->view->data            = $defaultData;
		$this->view->catid           = $articleCatId;
		$this->view->category        = $category;
		$this->view->title           = sprintf("Mettre à jour les informations de l'article %s", $defaultData["code"]);
		$this->view->article         = $article;
		$this->view->articleid       = $articleid;
	}
	
	
	public function copyAction()
	{		
		$me                          = Sirah_Fabric::getUser();
		$model                       = $this->getModel("article");	
        $modelTable                  = $model->getTable();
		$dbAdapter                   = $modelTable->getAdapter();
		$prefixName                  = $modelTable->info("namePrefix");		
		$ids                         = $this->_getParam("articleids", $this->_getParam("ids", array()));
		$errorMessages               = array();
		if( is_string($ids)) {
			$ids                     = explode("," ,$ids);
		}
		$ids                         = (array)$ids;
		$createdIds                  =  array();
		
		if( count(   $ids) ) {
			foreach( $ids as $id) {
				     $articleSrcRow                       = $model->findRow($id,"articleid", null, false );
					 if( $articleSrcRow ) {
						 $newArticleData                  = $articleSrcRow->toArray();
						 $newArticleData["creationdate"]  = time();
						 $newArticleData["creatorid"]     = $me->userid;
						 $newArticleData["updatedate"]    = $newArticleData["updateduserid"] = 0;
						 $newArticleData["title"]       = sprintf("%s(Copie)", $articleSrcRow->libelle);
						 $newArticleData["code"]          = $model->createCode();
						 if( isset($newArticleData["articleid "])) {
							 unset($newArticleData["articleid "]);
						 }
						 if( $dbAdapter->insert($prefixName."erccm_crm_content_articles", $newArticleData)) {
							 $createdIds[]                 = $dbAdapter->lastInsertId();
						 }						 
					 }
			}
		}
		$this->setRedirect(sprintf("%d articles ont été dupliqués avec succès", count($createdIds)), "success");
		$this->redirect("admin/articles/list");
	}
		 		
	
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->_helper->viewRenderer->setNoRender(true);
		$model             = $this->getModel("article");		
		$ids               = $this->_getParam("articleids", $this->_getParam("ids", array()));
		$errorMessages     = array();
		if( is_string($ids)) {
			$ids           = explode("," ,$ids);
		}
		$ids               = (array)$ids;
		if( count($ids))  {
			$me            = Sirah_Fabric::getUser();
			$modelTable    = $me->getTable();
			$dbAdapter     = $modelTable->getAdapter();
			$prefixName    = $modelTable->info("namePrefix");
			foreach( $ids as $id) {
					if(!$dbAdapter->delete($prefixName."erccm_crm_content_articles","articleid=".$id)) {
						$errorMessages[]  = " Erreur de la base de donnée l'article id#$id n'a pas été supprimée ";
					} else {
						$dbAdapter->delete($prefixName."erccm_crm_content_gallery",            "articleid=".$id);
						$dbAdapter->delete($prefixName."erccm_crm_content_gallery_photos",     "articleid=".$id);
						$dbAdapter->delete($prefixName."erccm_crm_content_gallery_videos",     "articleid=".$id);
						$dbAdapter->delete($prefixName."erccm_crm_content_articles_documents","articleid=".$id);
					}					
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}
		if( count($errorMessages)) {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/articles/list");
		} else {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("success"=> "Les articles selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les articles selectionnés ont été supprimés avec succès" , "success");
			$this->redirect("admin/articles/list");
		}
	}
		
	public function addphotosAction()
	{
		$this->view->title       = "Enregistrer des photos dans la gallerie photos de cet article";
		$errorMessages           = array();
		$galleryid               = intval($this->_getParam("galleryid" , $this->_getParam("id", 0)));
		$articleid               = intval($this->_getParam("articleid", 0));
			
		$modelArticle            = $this->getModel("article");
		$modelGallery            = $model = $this->getModel("gallery");
		$modelPhoto              = $this->getModel("galleryphoto");
		$me                      = Sirah_Fabric::getUser();
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$gallery_filepath        = APPLICATION_DATA_PATH. DS. "articles". DS. "galleries". DS . "original" ;
			
		$gallery                 = $modelGallery->findRow(intval($galleryid), "galleryid", null , false );
		if(!$gallery) {
			$gallery             = $modelGallery->findRow(intval($articleid), "articleid", null , false );
		}
		if(!$gallery) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune galerie photos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune galerie photos n'a été retrouvée avec cet identifiant", "error");
			$this->redirect("galleries/list");
		} else {
			if(!intval($articleid) ) {
				$articleid          = $gallery->articleid;
			}
		}
		$article                    = (intval($articleid))?$modelArticle->findRow(intval($articleid),"articleid", null , false) : null;
		if(!$article) {
			$articleid              = $article->articleid;
		}
		 
		$photosToken                = $this->_getParam("photostoken", $this->_getParam("token", null));
		$defaultData                = $modelPhoto->getEmptyData();
		$defaultData["articleid"]   = $articleid;
		$defaultData["galleryid"]   = $galleryid;
		$errorMessages              = $uploadedPhotos = array();
		if( empty($photosToken) ) {
			$photosToken            = Sirah_User_Helper::getToken(4);			
		}
		if( $this->_request->isPost() ) {
	        $postData               = $this->_request->getPost();
			$capturedPhotos         = (isset($_FILES[$photosToken]))?$_FILES[$photosToken]  : array();
			$galleryPhotos          = $gallery->photos();
			$galleryCode            = $article->code;
			
			if( empty($capturedPhotos)  || !count($capturedPhotos)) {
				$errorMessages[]    = "Aucune photo n'a été selectionnée pour cette galerie";
			} else {
				$photoKey           = 0;
				$photoId            = count($galleryPhotos) + 1;
                //print_r($capturedPhotos); die();
				foreach( $capturedPhotos["tmp_name"] as  $i => $capturedPhotoFile ) {
					     $capturedPhotoFilePath = $capturedPhotoFile;
						 $capturedPhotoName     = $capturedPhotos["name"][$i];
						 $capturedPhotoFileName = (isset($postData["photolibelle_".$i]))? $postData["photolibelle_".$i] : $capturedPhotoName;
						 $capturedPhotoFileDesc = (isset($postData["photodesc_".$i]   ))? $postData["photodesc_".$i]    : $capturedPhotoName;
						 $capturedPhotoFileExt  = (file_exists($capturedPhotoFile))?Sirah_Filesystem::getFilextension($capturedPhotoName) : "";
						 $capturedPhotoNewName  =  sprintf("Articl%s_Photo%03d_%s", $galleryCode, $photoId, $capturedPhotoName) ;
						 $photoFilePath         = $gallery_filepath. DS . $capturedPhotoNewName;
 
						 if( file_exists($capturedPhotoFilePath) ) {
							 if( copy( $capturedPhotoFilePath, $photoFilePath)) {
								 $photoImage    = Sirah_Filesystem_File::fabric("Image" ,$photoFilePath, "rb+");
					  	  	   	 $photoImage->resize("180", null , true , APPLICATION_DATA_PATH. DS ."articles". DS. "galleries". DS . "mini"  );
					  	  	   	 $photoImage->resize("90" , null , true , APPLICATION_DATA_PATH. DS ."articles". DS. "galleries". DS . "thumbs" );
								 $galleryPhotoData                 = array("articleid"=>$articleid,"galleryid"=>$galleryid,"libelle"=>$capturedPhotoFileName,"description"=>$capturedPhotoFileDesc);
								 $galleryPhotoData["creationdate"] = time();
								 $galleryPhotoData["creatorid"]    = $me->userid;
								 $galleryPhotoData["filepath"]     = $photoFilePath;
								 if( $dbAdapter->insert( $prefixName."erccm_crm_content_gallery_photos", $galleryPhotoData)) {
									 $photoId                      = $dbAdapter->lastInsertId();
									 $uploadedPhotos[$photoId]     = $galleryPhotoData;
								 } else {
									 $errorMessages[]              = sprintf("Les informations de la photo n'ont pas pu être stockées", $capturedPhotoName);
								 }
							 } else {
								     $errorMessages[]              = sprintf("La photo %s n'a pas pu être transférée sur le serveur", $capturedPhotoName);
							 }								 
						 } else {
							         $errorMessages[]              = sprintf("La photo %s n'a pas pu être transférée : Chemin inconnu", $capturedPhotoName);
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
				$this->redirect("admin/articles/infos/id/".$articleid);
			}				
		}
		$this->view->token     = $this->view->photosToken = $photosToken;
		$this->view->galleryid = $galleryid;
		$this->view->articleid = $articleid;
		$this->view->article   = $article;
		$this->view->data      = $defaultData;
		$this->view->title     = ( $article ) ? sprintf("Enregistrer des photos dans la gallerie photos de l'article référencée %s", $article->code) : "Enregistrer des photos dans la gallerie photos de cet article";
		$this->render("photoupload");
	}
	
	
	 public function addvideoAction()
	{
		$this->view->title    = "Enregistrer une vidéo dans la gallerie";
		$errorMessages        = array();
		$galleryid            = intval($this->_getParam("galleryid" , 0));
		$articleid            = intval($this->_getParam("articleid", $this->_getParam("id", 0)));
	
		$modelGallery         = $this->getModel("gallery");
		$modelArticle         = $model = $this->getModel("article");
		$modelVideo           = $this->getModel("galleryvideo");
		$me                   = Sirah_Fabric::getUser();
		$modelTable           = $model->getTable();
		$dbAdapter            = $modelTable->getAdapter();
		$prefixName           = $modelTable->info("namePrefix");
		$gallery_filepath     = APPLICATION_DATA_PATH . DS . "articles" . DS . "galleries"  ;
	
		$gallerie             = $modelGallery->findRow($galleryid,"galleryid",null, false);
		$article              = $modelArticle->findRow($articleid,"articleid",null, false );
		if(!$article) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de gallerie vidéos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de gallerie vidéos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/articles/list");
		}
		 
		$galleryid               = ( $article ) ? $article->galleryid : $galleryid;
		if(!intval( $galleryid )) {
			$articleLibelle     = $article->title;
			$newGallery          = array("articleid"=>$articleid, "libelle"=>$articleLibelle,"description"=>$articleLibelle,"updatedate"=> 0,"filepath"=>$gallery_filepath,"creationdate"=>time(),"creatorid"=> $me->userid, "updateduserid"=> 0 );
			if( $dbAdapter->insert($prefixName."erccm_crm_content_gallery", $newGallery )) {
				$galleryid       = $dbAdapter->lastInsertId();
				$gallerie        = $model->findRow($galleryid, "galleryid" , null , false);
				$article->galleryid = $galleryid;
				$article->save();
			} else {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Cette vidéo n'est pas ajoutée dans le contexte d'une gallerie vidéos, veuillez informer l'administrateur"));
					exit;
				}
				$this->setRedirect("Cette vidéo n'est pas ajoutée dans le contexte d'une gallerie videos, veuillez informer l'administrateur" , "error");
				$this->redirect("admin/articles/list");
			}
		}
		$defaultData               = $modelVideo->getEmptyData();
		$this->view->videoSources  = $videoSources = array(0=> "Vidéo locale", "youtube"=>"Vidéo Youtube","vimeo"=>"Vidéo Viméo","dailymotion"=>"Vidéo dailymotion");
		$defaultData["galleryid"]  = $galleryid;
		if( $this->_request->isPost() ) {				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
	
			$videoUpload           = new Zend_File_Transfer();
			//On inclut les différents validateurs de la vidéo
			$videoUpload->addValidator('Count',false,1);
			$videoUpload->addValidator("Extension", false, array("avi","flv","wma","mpeg","mp4","mp3","mpg","mov"));
			$videoUpload->addValidator("FilesSize", false, array("max" => "150MB"));
	
			$postData             = $this->_request->getPost();
			$videoLibelle         = (isset($postData["libelle"]    ))? $stringFilter->filter($postData["libelle"] )     : "";
			$videoDescription     = (isset($postData["description"]))? $stringFilter->filter($postData["description"] ) : "";

			$basicFilename        = $videoUpload->getFileName("videosrcfile", false );
			$tmpFilename          = Sirah_Filesystem::getName($basicFilename);
			$videoExtension       = Sirah_Filesystem::getFilextension( $basicFilename );
			$videoFileName        = "videoLocal".time().".".$videoExtension;
			$videoFilepath        = $gallery_filepath . DS . $videoFileName;
			$videoUpload->addFilter("Rename", array("target" => $videoFilepath, "overwrite" => true) , "videosrcfile");
			
			//On upload la vidéo de l'utilisateur
			if( $videoUpload->isUploaded("videosrcfile")){
				$videoUpload->receive(   "videosrcfile");
			}  
			if( $videoUpload->isReceived("videosrcfile")) {				
				//on enregistre la vidéo dans la base de données
				$videoData             = array("galleryid"=> $galleryid,"filepath"=>$videoFilepath,"videosrc"=>0,"videourl"=>"","libelle"=>$videoLibelle,"description"=>$videoDescription,"creationdate"=>time(), "creatorid"=>$me->userid );
				if( !$dbAdapter->insert( $prefixName."erccm_crm_content_gallery_videos", $videoData  )) {
					 $errorMessages[]  = "La vidéo n'a pas été enregistrée dans la base de données";
				}
			} else {
				$videoSrc              = ( isset($postData["videosrc"]) && isset($videoSources[$postData["videosrc"]])) ? $stringFilter->filter( strtolower( $postData["videosrc"])) : "";
				$videoUrl              = ( isset($postData["videourl"])) ? filter_var( $postData["videourl"], FILTER_SANITIZE_URL )   : "";
				Zend_Uri::setConfig(array('allow_unwise' => true));
				if( empty( $videoSrc ) || !in_array( $videoSrc, array("youtube","vimeo","dailymotion") ) ) {
					$errorMessages[]   = "La source de la vidéo n'est pas valide, veuillez sélectionner soit youtube, soit dailymotion ou vimeo";
					$videoSrc          = "";
				}
				if( ( Zend_Uri::check($videoUrl) == FALSE ) && !empty( $videoSrc )) {
					switch( $videoSrc ) {
						case "dailymotion":
							$videoUrl     = sprintf("//www.dailymotion.com/embed/video/\%s", $videoUrl );
							break;
						case "youtube":
							$videoUrl     = sprintf("//www.youtube.com/embed/\%s", $videoUrl );
							break;
						case "vimeo":
							$videoUrl     = sprintf("//player.vimeo.com/video/\%s", $videoUrl );
							break;
						default:
							$videoUrl     = sprintf("//player.vimeo.com/video/\%s", $videoUrl );
					}
				}
				if( empty( $videoUrl )  ) {
					$errorMessages[]      = "Vous devrez saisir une URL valide";
				}
				if( empty( $errorMessages) ) {
					$videoData            = array("galleryid"=>$galleryid,"filepath"=>"","videosrc"=>$videoSrc,"libelle"=>$videoLibelle,"description"=> $videoDescription,"videourl"=> $videoUrl,"creationdate"=>time(),"creatorid"=> $me->userid );
					if( !$dbAdapter->insert( $prefixName . "erccm_crm_content_gallery_videos", $videoData  )) {
						$errorMessages[]  = "La vidéo n'a pas été enregistrée dans la base de données";
					}
				}				
			} 			 
			if(!empty( $errorMessages)){
				$defaultData   = $videoData;
				if(    $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
					exit;
				}
				foreach( $errorMessages as $errorMessage){
					     $this->getHelper("Message")->addMessage($errorMessage , "error");
				}
			}  else {
				if( $this->_request->isXmlHttpRequest()) {
					clearstatcache();
					$basePath    = str_replace( APPLICATION_PATH, ROOT_PATH . DS ."application", APPLICATION_DATA_PATH . DS . "articles" . DS . "galleries") ;
					$videoPath   = str_replace( DS , "/" , $basePath . DS . "mini" .DS );
					$returnJson  = array("success"=>"La vidéo a été enregistrée avec succès","files"=> array(array("name"=> $basicFilename, "extension"=> $videoExtension,"path"=> $videoPath )) );
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson($returnJson);
					exit;
				}
				$this->setRedirect("La vidéo a été enregistrée avec succès","success");
				$this->redirect("admin/articles/infos/articleid/".$galleryid);
			}
		}
		$this->view->galleryid    = $galleryid;
		$this->view->articleid    = $articleid;
		$this->view->article      = $article;
		$this->view->data         = $defaultData;
		$this->view->title        = ( $article )? sprintf("Ajouter une vidéo dans la galerie vidéo de l'article %s", $article->title) : "Nouvelle vidéo dans la galerie vidéos d'article";
		
		$this->render("videoupload");
	}
	
	public function uploadAction()
	{
		$articleid           = intval($this->_getParam("articleid" , $this->_getParam("id" , 0 )));
		$model                = $this->getModel("article");
		if(!$articleid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}
		$article            = $model->findRow( $articleid , "articleid" , null , false );
		if(!$article) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" ));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ,  sont invalides" , "error");
			$this->redirect("admin/articles/list");
		}
		$me                                  = Sirah_Fabric::getUser();
		$modelDocument                       = $this->getModel("document");
		$modelCategory                       = $this->getModel("documentcategorie");

		$defaultData                         = $modelDocument->getEmptyData();
		$fileDataPath                        = APPLICATION_DATA_PATH . DS . "articles" . DS . "documents"   ;
		$errorMessages                       = array();
		$uploadedFiles                       = array();
		$categories                          = $modelCategory->getSelectListe("Selectionnez une catégorie", array("id", "libelle") );
	
		if( $this->_request->isPost() ) {
			$postData                        = $this->_request->getPost();
			$formData                        = array_intersect_key( $postData ,  $defaultData )	;
			$documentData                    = array_merge( $defaultData ,  $formData );
			$articleDocument                 = array("articleid"=>$articleid,"updatedate"=>0,"updateduserid"=>0);
 
			$modelTable                      = $model->getTable();
			$dbAdapter                       = $modelTable->getAdapter();
			$prefixName                      = $modelTable->info("namePrefix");
			if(!is_dir( $fileDataPath ) ) {
				$errorMessages[]             = "Le dossier de stockage des documents de l'article n'est pas créé, veuillez informer l'administrateur ";
			}
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$stringFilter                    = new Zend_Filter();
			$stringFilter->addFilter(          new Zend_Filter_StringTrim());
			$stringFilter->addFilter(          new Zend_Filter_StripTags());
			//On crée un validateur de filtre
			$strNotEmptyValidator            = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
	
			$documentData["userid"]          = $me->userid;
			$documentData["category"]        = intval( $documentData["catid"] );
			$documentData["resource"]        = "articles" ;
			$documentData["resourceid"]      = $documentData["access"]          = 0;
			$documentData["filedescription"] = $articleDocument["description"] = $projectDocument["description"]  = $stringFilter->filter($documentData["filedescription"] );
			$documentData["filemetadata"]    = $articleDocument["keywords"]    = (isset($postData["filemetadata"]))?$stringFilter->filter($documentData["filemetadata"]) : "";
	
			$userMaxFileSize                 = 32;
			$userMaxUploadFileSize           = 100;
			$userSingleFileSize              = 100;
			$userTotalFiles                  = 10;
	
			$documentsUpload                 = new Zend_File_Transfer();
			$documentsUpload->addValidator("Count"    , false , 1 );
			$documentsUpload->addValidator("Extension", false , array("csv","xls","xlxs", "pdf","png","gif","jpg","docx", "doc" , "xml"));
			$documentsUpload->addValidator("Size"     , false , array("max"  => "20MB"));
			$documentsUpload->addValidator("FilesSize", false , array("max"  => "20MB"));
	
			$basicFilename                  = $documentsUpload->getFileName('articlefiles' , false );
			$documentExtension              = Sirah_Filesystem::getFilextension( $basicFilename );
			$tmpFilename                    = Sirah_Filesystem::getName( $basicFilename);
			$filePath                       = $fileDataPath . DS . time(). "_".sprintf("docarticle%04d", $articleid ) . "." . $documentExtension;
				
			$documentsUpload->addFilter("Rename", array("target" => $filePath, "overwrite" => true), "articlefiles");
			//On upload les fichiers du dossier d'article
			if( $documentsUpload->isUploaded("articlefiles")){
				$documentsUpload->receive(   "articlefiles");
			} else {
				$errorMessages[]            = " Le document que vous avez chargé n'est pas valide";
			}
			if( $documentsUpload->isReceived("articlefiles") ) {
				$fileSize                           = $documentsUpload->getFileSize("articlefiles");
				$myFilename                         = (isset($postData["filename"]) && $strNotEmptyValidator->isValid($postData["filename"])) ? $stringFilter->filter( $postData["filename"] ) : $tmpFilename;
				$documentData["filename"]           = $articleDocument["title"]      = $projectDocument["title"]      = $modelDocument->rename( $myFilename , $me->userid );
				$documentData["filepath"]           = $filePath;
				$documentData["filextension"]       = $documentExtension;
				$documentData["filesize"]           = floatval($fileSize);
				$documentData["creationdate"]       = $articleDocument["creationdate"] = $projectDocument["creationdate"] = time();
				$documentData["creatoruserid"]      = $articleDocument["creatorid"]    = $projectDocument["creatorid"]    = $me->userid;
				if( $dbAdapter->insert( $prefixName ."system_users_documents", $documentData ) ) {
					$documentid                     = $dbAdapter->lastInsertId();
					$articleDocument["documentid"]  = $documentData["documentid"]      =  $documentid;
					if( $dbAdapter->insert( $prefixName."erccm_crm_content_articles_documents", $articleDocument)) {
					}
					$uploadedFiles[$documentid]     = $documentData;
				} else {
					$errorMessages[]                = "Les informations du document n'ont pas été enregistrées dans la base de données";
				}
			} else {
				$errorMessages[]                    = "Le document n'a pas été chargé correctement sur le serveur";
			}
			if( empty( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					$jsonArray                      = array();
					$jsonArray["success"]           = "Le document a été enregistré avec succès";
					$jsonArray["document"]          = $documentData ;
					echo ZendX_JQuery::encodeJson( $jsonArray );
					exit;
				}
				$this->_helper->Message->addMessage("Le document a été enregistré avec succès" , "success");
				$this->redirect("admin/articles/infos/articleid/".$articleid);
			} else {
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
					exit;
				}
				foreach($errorMessages as $errorMessage){
					$this->getHelper("Message")->addMessage($errorMessage , "error");
				}
			}
		}
		$this->view->articleid    = $articleid;
		$this->view->categories    = $categories;
		$this->view->data          = $defaultData;
	}
	
	 
}