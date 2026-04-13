<?php

class Admin_GalleryvideosController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{
		
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("base");
		}
		$id              = $galleryid = intval($this->_getParam("id", $this->_getParam("galleryid", 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/galleries/list");
		}
		$model         = $this->getModel("gallery");
		$modelVideo    = $this->getModel("articlevideo");
		$gallerie      = $model->findRow($id , "galleryid" , null , false);
		if(!$gallerie ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de gallerie videos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de gallerie videos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/galleries/list");
		}
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter             = new Zend_Filter();
		$stringFilter->addFilter(   new Zend_Filter_StringTrim());
		$stringFilter->addFilter(   new Zend_Filter_StripTags());
		$defaultFilter            = $stringFilter->filter($this->_getParam("generalfilter" , null));
		$filters                  = array("libelle"  => $defaultFilter, "galleryid"=> $id );
		$this->view->gallerie     = $gallerie;
		$this->view->filters      = $filters;
		$this->view->videos       = $gallerie->videos( $id, $filters );
		$this->view->title        = sprintf("Liste des vidéos de la gallerie %s", $gallerie->libelle );
		$this->view->columns      = array("left");
		$this->view->videoSources = array(0=> "Vidéo locale","youtube"=>"Vidéo Youtube","vimeo"=>"Vidéo Viméo","dailymotion"=> "Vidéo dailymotion");
		$requestUri               = $this->getRequest()->getRequestUri();
		$this->view->hostName     = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . substr_replace( $requestUri, "", strpos($requestUri, "videos/")) ;
	}
	
    public function createAction()
	{
		$this->view->title       = "Enregistrer une vidéo dans la gallerie";
		$errorMessages           = array();
		$galleryid               = intval( $this->_getParam("galleryid" , 0));
		$activityid              = intval( $this->_getParam("activityid", 0));
	
		$model                   = $this->getModel("gallery");
		$modelArticle           = $this->getModel("article");
		$modelVideo              = $this->getModel("articlevideo");
		$me                      = Sirah_Fabric::getUser();
		$modelTable              = $model->getTable();
		$dbAdapter               = $modelTable->getAdapter();
		$prefixName              = $modelTable->info("namePrefix");
		$gallery_filepath        = APPLICATION_DATA_PATH . DS . "articles" . DS . "galleries"  ;
	
		$gallerie                = $model->findRow( $galleryid , "galleryid" , null , false);
		$article                = $modelArticle->findRow( $activityid, "activityid", null , false );
		if(!$gallerie && !$article) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de gallerie vidéos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de gallerie vidéos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/galleries/list");
		}
		$galleryid               = ( $article ) ? $article->galleryid : $galleryid;
		if(!intval( $galleryid )) {
			$articleLibelle     = $article->libelle;
			$newGallery          = array("activityid"=>$activityid, "libelle"=>$articleLibelle,"description"=>$articleLibelle,"updatedate"=> 0,"filepath"=>$gallery_filepath,"creationdate"=>time(),"creatorid"=> $me->userid, "updateduserid"=> 0 );
			if( $dbAdapter->insert( $prefixName . "sdr_projects_gallery", $newGallery )) {
				$galleryid       = $dbAdapter->lastInsertId();
				$gallerie        = $model->findRow( $galleryid , "galleryid" , null , false);
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
				$this->redirect("admin/galleries/list");
			}
		}
		$defaultData               = $modelVideo->getEmptyData();
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
			$videoUpload->addValidator("FilesSize", false, array("max" => "50MB"));
	
			$postData             = $this->_request->getPost();
			$videoLibelle         = ( isset( $postData["libelle"]    ))? $stringFilter->filter($postData["libelle"] )     : "";
			$videoDescription     = ( isset( $postData["description"]))? $stringFilter->filter($postData["description"] ) : "";

			$basicFilename        = $videoUpload->getFileName("videosrcfile", false );
			$tmpFilename          = Sirah_Filesystem::getName(  $basicFilename);
			$videoExtension       = Sirah_Filesystem::getFilextension( $basicFilename );
			$videoFileName        = "videoLocal".time().".".$videoExtension;
			$videoFilepath        = $gallery_filepath . DS .$videoFileName;
			$videoUpload->addFilter("Rename", array("target" => $videoFilepath, "overwrite" => true) , "videosrcfile");
			
			//On upload la vidéo de l'utilisateur
			if( $videoUpload->isUploaded("videosrcfile")){
				$videoUpload->receive(   "videosrcfile");
			}  
			if( $videoUpload->isReceived("videosrcfile")) {				
				//on enregistre la vidéo dans la base de données
				$videoData   = array("galleryid"=> $galleryid,"filepath"=>$videoFilepath,"videosrc"=> "","libelle"=>$videoLibelle,"description"=>$videoDescription,"videourl"=>"","creationdate"=>time(), "creatorid"=>$me->userid );
				if( !$dbAdapter->insert( $prefixName ."sdr_projects_gallery_videos", $videoData  )) {
					 $errorMessages[]  = "La vidéo n'a pas été enregistrée dans la base de données";
				}
			} else {
				$videoSrc          = ( isset($postData["videosrc"])) ? $stringFilter->filter( strtolower( $postData["videosrc"])) : "";
				$videoUrl          = ( isset($postData["videourl"])) ? filter_var( $postData["videourl"], FILTER_SANITIZE_URL )   : "";
				Zend_Uri::setConfig(array('allow_unwise' => true));
				if( empty( $videoSrc ) || !in_array( $videoSrc, array("youtube", "vimeo","dailymotion") ) ) {
					$errorMessages[]   = "La source de la vidéo n'est pas valide, veuillez sélectionner entre youtube, dailymotion, vimeo";
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
					if( !$dbAdapter->insert( $prefixName . "sdr_projects_gallery_videos", $videoData  )) {
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
				$this->redirect("admin/galleries/infos/galleryid/".$galleryid);
			}
		}
		$this->view->galleryid    = $galleryid;
		$this->view->article     = $article;
		$this->view->data         = $defaultData;
		$this->view->title        = ( $article )? sprintf("Ajouter une vidéo dans la galerie vidéo de l'article %s", $article->libelle) : "Nouvelle vidéo dans la galerie vidéos d'article";
		$this->view->videoSources = array(0=> "Vdéo locale", "youtube"=>"Vidéo Youtube","vimeo"=>"Vidéo Viméo","dailymotion"=>"Vidéo dailymotion");
	}
	

	public function infosAction()
	{
		$this->_helper->layout->disableLayout(true);
		$this->view->title       = "Lecture de la vidéo";
		$errorMessages           = array();
		$videoid                 = intval( $this->_getParam("videoid", $this->_getParam("id", 0)));

		$modelVideo              = $this->getModel("articlevideo");
		
		$video                   = $modelVideo->findRow( $videoid , "videoid" , null , false);
		if(!$video ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entrée de videos n'a été retrouvée avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucune entrée de videos n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/galleries/list");
		}
		$request                 = $this->getRequest();
		$videoLibelle            = $video->libelle;
		$video                   = $video->filepath;
		$videoFile               = "";
		$requestUri              = $request->getRequestUri();
		$requestHost             = $request->getHttpHost();
		$requestScheme           = $request->getScheme();
		$hostName                = $requestScheme."://". $requestHost. substr_replace( $requestUri, "", strpos($requestUri, "videos/")) ;
		$this->view->video       = $video;
		$this->view->request     = $request;
		$this->view->requestHost = $requestHost;
		$this->view->requestUri  = $requestUri;
		     
	}

	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
	
		$model         = $this->getModel( "video");
		$ids           = $this->_getParam("videoids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if( count(    $ids )) {
			foreach( $ids as $id) {
					$videoid   = intval( $id );
					$video     = $model->findRow( $videoid, "videoid" , null , false );
					if( $video ) {
						if(!$video->delete()) {
							$errorMessages[]  = " Erreur de la base de donnée la vidéo id#$id n'a pas été supprimée ";
						} 
					} else {
						$errorMessages[]      = "Aucune entrée valide n'a été trouvée pour la gallerie videos id #$id ";
					}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}
		if( count($errorMessages)) {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/galleries/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les vidéos selectionnées ont  été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les vidéos selectionnées ont  été supprimées avec succès", "success");
			$this->redirect("admin/galleryvideos/list");
		}
	}
	
	public function _removeStyle( $src )
	{
		$headScriptContainer = Zend_View_Helper_Placeholder_Registry::getRegistry()->getContainer("Zend_View_Helper_HeadStyle");
		$iter = $headScriptContainer->getIterator();
		$success = FALSE;
		foreach ($iter as $k => $value) {
			if( strpos($value->attributes["src"], $src) !== FALSE) {
				$iter->offsetUnset($k);
				$success = TRUE;
			}
		}
		Zend_View_Helper_Placeholder_Registry::getRegistry()->setContainer("Zend_View_Helper_HeadStyle", $headScriptContainer);
		return $success;
	}

	public function _removeScript( $src ) 
	{
		$headScriptContainer = Zend_View_Helper_Placeholder_Registry::getRegistry()->getContainer("Zend_View_Helper_HeadScript");
		$iter = $headScriptContainer->getIterator();
		$success = FALSE;
		foreach ($iter as $k => $value) {
			if(strpos($value->attributes["src"], $src) !== FALSE) {
				$iter->offsetUnset($k);
				$success = TRUE;
			}
		}
		Zend_View_Helper_Placeholder_Registry::getRegistry()->setContainer("Zend_View_Helper_HeadScript", $headScriptContainer);
		return $success;
	}
}