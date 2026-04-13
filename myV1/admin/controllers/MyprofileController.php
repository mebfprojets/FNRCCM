<?php

defined("USER_AVATAR_PATH")
|| define("USER_AVATAR_PATH", APPLICATION_DATA_USER_PATH . "avatars");

class Admin_MyprofileController extends Sirah_Controller_Default
{
		
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base");		
		$model                       = $this->getModel("profile");
		$modelCoordonnees            = $this->getModel("profilecoordonnee");
		$modelAvatar                 = $this->getModel("profileavatar");	
		$profile                     = $model->getRow( 0 ,true , false );
		$coordonnees                 = $modelCoordonnees->findRow( $profile->profileid , "profileid" , null , false );
		$avatar                      = $modelAvatar->findRow($profile->profileid , "profileid" , null , false);
				
		$this->view->profile         = $profile;
		$this->view->coordonnees     = $coordonnees;
		$this->view->avatar          = $avatar;
		$this->view->documents       = $profile->documents();
		$this->view->title           = "Aperçu de mon profil";
		$this->view->showProfileMenu = true;
		$this->view->columns         = array("left");
	}
	
	public function settingsAction()
	{
		$this->view->title          = "Enregistrer mes tags";
		$errorMessages              = array();
		
		$csrfTokenId                = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue             = $this->_helper->csrf->getToken(300);
		$csrfFormNames              = $this->_helper->csrf->getFormNames(array("profileid", "params") , false );
		$params                     = $this->_request->getParams();
		$model                      = $this->getModel("profile");
		$user                       = Sirah_Fabric::getUser();
		$profile                    = $model->getRow( 0 , true , false );
		if( empty( $profile->params ) ) {
			$profile->setParams( $user->getParams() );
		}	
		$params                     = $profile->paramsToArray();
		if ( $this->_request->isPost() ) {
			 if( $this->_helper->csrf->isValid() ) {
			 	 $postData          = $this->_request->getPost();
			 	 $formData          = array_intersect_key( $postData , $profile->paramsToArray( $params  ));
			 	 $stringFilter      = new Zend_Filter();
			 	 $stringFilter->addFilter(new Zend_Filter_StringTrim());
			 	 $stringFilter->addFilter(new Zend_Filter_StripTags());
			 	
			 	 //On crée les validateurs nécessaires
			 	 $strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" , "zero" , "string" , "float" , "null"));
			 	 $updatedParams        = array_merge( $profile->paramsToArray( $params ) , $formData );
			 	 
			 	 $updatedParams["view_profile_infos"]       = intval( $updatedParams["view_profile_infos"] );
			 	 $updatedParams["view_profile_coordonnees"] = intval( $updatedParams["view_profile_coordonnees"] );
			 	 $updatedParams["view_profile_experiences"] = intval( $updatedParams["view_profile_experiences"] );
			 	 $updatedParams["view_profile_formations"]  = intval( $updatedParams["view_profile_formations"] );
			 	 $updatedParams["view_profile_tags"]        = intval( $updatedParams["view_profile_tags"] );
			 	 $updatedParams["view_profile_content"]     = intval( $updatedParams["view_profile_content"] );
			 	 $updatedParams["view_profile_contacts"]    = intval( $updatedParams["view_profile_contacts"] );
			 	 $updatedParams["view_profile_cv"]          = intval( $updatedParams["view_profile_cv"] );
			 	 $updatedParams["findme_from_email"]        = intval( $updatedParams["findme_from_email"]);
			 	 $updatedParams["findme_from_name"]         = intval( $updatedParams["findme_from_name"] );
			 	 $updatedParams["findme_from_tags"]         = intval( $updatedParams["findme_from_tags"] );
			 	 $updatedParams["findme_from_phone"]        = intval( $updatedParams["findme_from_phone"]);
			 	 $updatedParams["allow_robots_index"]       = intval( $updatedParams["allow_robots_index"]);			 	 
			 	 if( $profile->setParams( $updatedParams ) ) {
			 	 	$userUpdatedParams  = array_intersect_key( $updatedParams , $user->getParams() );
			 	 	$userParams         = array_merge( $updatedParams, $userUpdatedParams );
			 	 	$user->setParams( $userParams );
			 	 	if( $this->_request->isXmlHttpRequest() ) {
			 	 		$this->_helper->viewRenderer->setNoRender( true );
			 	 		$this->_helper->layout->disableLayout( true );
			 	 		echo ZendX_JQuery::encodeJson(array("success" => "Les paramètres de confidentialité de votre profil ont été mis à jour avec succès"));
			 	 		exit;
			 	 	}
			 	 	$this->setRedirect("Les paramètres de confidentialité de votre profil ont été mis à jour avec succès" , "success");
			 	 	$this->redirect("myprofile/infos");			 	 	
			 	 } else {
			 	 	if( $this->_request->isXmlHttpRequest() ) {
			 	 		$this->_helper->viewRenderer->setNoRender( true );
			 	 		$this->_helper->layout->disableLayout( true );
			 	 		echo ZendX_JQuery::encodeJson(array("error" => "Aucune mise à jour n'a été appliquée dans les paramètres de votre profil"));
			 	 		exit;
			 	 	}
			 	 	$this->setRedirect("Aucune mise à jour n'a été appliquée dans les paramètres de votre profil" , "error");
			 	 	$this->redirect("myprofile/infos");	
			 	 }			 	
			 } else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( array("profileid", "params") , true );			
		}
		$this->view->params         = $params;
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->profileid      = $profile->profileid;
	}
	
	public function tagsAction()
	{
		$this->view->title       = " Enregistrer mes tags";
		$errorMessages           = array();
		
		$csrfTokenId             = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue          = $this->_helper->csrf->getToken(300);
		$csrfFormNames           = $this->_helper->csrf->getFormNames( array("tags") , false );
		$params                  = $this->_request->getParams();
		$model                   = $this->getModel("keyword");
		$modelProfile            = $this->getModel("profile");
		$user                    = Sirah_Fabric::getUser();
		$profile                 = $modelProfile->getRow(0 ,true , false );
		
		if( $this->_request->isPost( ) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter    = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				$userTable       = $user->getTable();
				$prefixName      = $userTable->info("namePrefix");
				$dbAdapter       = Sirah_Fabric::getDbo();
								
				$postData        = $this->_request->getPost();
				$tags            = ( isset( $postData["tags"] ) ) ? $postData["tags"] : array();
				$newTags         = array();
				if( count(   $tags ) ) {					
					foreach( $tags as $keywordLibelle ) {
						if( is_numeric( $keywordLibelle ) ) {
							$keywordRow = $model->findRow( intval( $keywordLibelle ), "id" , null , false );
						} else {
							$keywordRow = $model->findRow( $stringFilter->filter( $keywordLibelle ), "libelle" , null , false );
						}						
						$keywordId  = ( $keywordRow ) ? $keywordRow->id : null;
						if( !$keywordRow && $strNotEmptyValidator->isValid( $keywordLibelle ) ) {
							$keywordLibelle = $stringFilter->filter( $keywordLibelle );
							if( $dbAdapter->insert( $prefixName . "system_general_keywords", array("libelle" => $keywordLibelle, "creationdate" => time(), "creatorid" => $user->userid ) ) ) {
								$keywordId  = $dbAdapter->lastInsertId();
								$keywordRow = $model->findRow( $keywordId, "id", null , false );
							}
						}
						//On vérifie pour voir s'il n'ya pas une entrée de ce domaine pour l'utilisateur
						$checkRow   = $dbAdapter->fetchCol( $dbAdapter->select()->from(array( "PD" => $prefixName . "system_users_profile_tags"), array("tagid"))
								                                                ->where("PD.tagid = ?", intval( $keywordId ) )->where("PD.profileid = ?", $profile->profileid ) );
						if( $keywordRow && !isset( $checkRow[0] ) ) {
							$insert_keyword  = array("tagid" => $keywordId, "profileid" => $profile->profileid, "creationdate" => time(), "creatorid" => $user->userid , "level" => 0 );
							if( $dbAdapter->insert( $prefixName . "system_users_profile_tags", $insert_keyword ) ) {
								$newTags[$keywordId]        = $insert_keyword;
								$newTags[$keywordId]["tag"] = $keywordRow->libelle;
							}
						}
					}
				}
				if( !count( $newTags ) ) {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						echo ZendX_JQuery::encodeJson( array("error" => "Aucun tag valide n'a été saisi"));
						exit;
					}
					$this->setRedirect( "Aucun tag valide n'a été saisi" , "error" );
					$this->redirect("myprofile/infos");
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						$jsonReturn             = array();
						$jsonReturn["tags"]     = $newTags;
						$jsonReturn["success"]  = "Les mots clés que vous avez saisis ont été rattachés à votre profil";
						echo ZendX_JQuery::encodeJson( $jsonReturn );
						exit;
					}
					$this->setRedirect("Les mots clés que vous avez saisis ont été rattachés à votre profil" , "success");
					$this->redirect("myprofile/infos");
				}
			}	else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( array("profileid") , true );
		}
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->profileid      = $profile->profileid;
		$this->view->tagList        = $model->getSelectListe("Saisissez des mots clés" , array("id", "libelle") , array() , null , null , false );
	}
	
	public function removetagAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$modelProfile  = $this->getModel("profile");
		$profile       = $modelProfile->getRow(0 ,true , false );
		$table         = $modelProfile->getTable();
		$dbAdapter     = $table->getAdapter();
		$prefixName    = $table->info("namePrefix");
		$ids           = $this->_getParam("tags", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string( $ids ) ) {
			$ids  = explode("," ,$ids);
		}
		$ids      = (array)$ids;
		if( count( $ids ) ) {
			foreach( $ids as $id ) {
				if(!$dbAdapter->delete( $prefixName ."system_users_profile_tags" ,"profileid =".$profile->profileid." AND tagid =".intval( $id ) )) {
					$errorMessages[]  = " Erreur de la base de donnée l'élement id#$id n'a pas été supprimé ";
				}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete sont invalides ";
		}
		if(count( $errorMessages )) {
			if(   $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("myprofile/infos");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les tags fournis ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les tags fournis ont été supprimés avec succès", "success");
			$this->redirect("myprofile/infos");
		}
	}
	
	public function changeavatarAction()
	{
		$this->view->title           = "Changer mon avatar";
		$errorMessages               = array();
		
		$csrfTokenId                 = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue              = $this->_helper->csrf->getToken(300);
		$csrfFormNames               = $this->_helper->csrf->getFormNames(array("profileid") , false );
		$params                      = $this->_request->getParams();
		$model                       = $this->getModel("profile");
		$user                        = Sirah_Fabric::getUser();
		$profile                     = $model->getRow( 0 , true, false );
				
		if( $this->_request->isPost() && isset( $params[$csrfFormNames["profileid"]] ) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				$modelAvatar         = $this->getModel("profileavatar");
				$avatar              = $modelAvatar->findRow($profile->profileid , "profileid" , null , false);
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter        =   new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());				
				
				$avatarUpload = new Zend_File_Transfer();
				//On inclut les différents validateurs de l'avatar
				$avatarUpload->addValidator('Count',false,1);
				$avatarUpload->addValidator("Extension",false,array("png","jpg","jpeg","gif","bmp"));
				$avatarUpload->addValidator("FilesSize",false,array("max"       => "3MB"));
				$avatarUpload->addValidator("ImageSize",false,array("minwidth"  => 10,
						                                            "maxwidth"  => 3200,
						                                            "minheight" => 10,
						                                            "maxheight" => 2800));				
				$avatarExtension   = Sirah_Filesystem::getFilextension($avatarUpload->getFileName('myavatar'));
				$currentAvatar     = ( $avatar ) ? $avatar->filename : null;
					
				//On inclut les différents filtres de l'avatar
				$avatarBaseName    = time() . $user->userid . "avatar." . $avatarExtension;
				$originalFilename  = USER_AVATAR_PATH .DS . 'original' . DS . $avatarBaseName ;
				$avatarUpload->addFilter("Rename" , array("target" => $originalFilename , "overwrite" => true) , "myavatar");
				//On upload l'avatar de l'utilisateur
				if( $avatarUpload->isUploaded("myavatar") ) {
					$avatarUpload->receive("myavatar");
				} else {
					$errorMessages[]  = "L'avatar fourni n'est pas valide";
				}
				if($avatarUpload->isReceived("myavatar")) {
					//on supprime l'avatar existant de l'utilisateur
					if((null != $currentAvatar) && !empty($currentAvatar) && Sirah_Filesystem::exists(USER_AVATAR_PATH .DS . 'original' . DS . $currentAvatar )){
						@unlink(USER_AVATAR_PATH . DS . 'thumb'    . DS . $currentAvatar );
						@unlink(USER_AVATAR_PATH . DS . 'mini'     . DS . $currentAvatar );
						@unlink(USER_AVATAR_PATH . DS . 'original' . DS . $currentAvatar );
					}
					//On fait une copie de l'avatar dans le dossier "THUMBNAILS" du dossier des avatars
					$avatarImage  = Sirah_Filesystem_File::fabric("Image" , $originalFilename , "rb+");
					$avatarImage->resize("180", null , true , USER_AVATAR_PATH . DS . "mini" );
					$avatarImage->resize("90" , null , true , USER_AVATAR_PATH . DS . "thumb" );
				
					//on enregistre l'avatar dans la base de données
					$avatarData   = array(
							               "profileid" => $profile->profileid,
							               "libelle"   => sprintf(" Avatar de %s %s " , $user->lastname , $user->firstname),
							               "filename"  => $avatarBaseName );
					if(!$avatar) {
						$avatar    = $this->getModel("profileavatar");
					}
					$userTable  = $user->getTable();
					$avatar->setFromArray($avatarData);
					$userTable->save(array( "avatar"  => $avatarBaseName));
					if(!$avatar->save()) {
						$errorMessages[]  = "Les informations de l'avatar n'ont pas été correctement enregistrées dans la base de données";
					}
				} else {
					$uploadMessages = $avatarUpload->getErrors();
					if(!empty($uploadMessages)) {
						foreach($uploadMessages as $key => $errorCode ) {
							$errorMessages[]  = Sirah_Controller_Default::getUploadMessage( $errorCode );
						}
					}
				}
				if(!empty($errorMessages)){
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
						exit;
					}
					foreach($errorMessages as $errorMessage){
						$this->getHelper("Message")->addMessage($errorMessage , "error");
					}
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {
						clearstatcache();
						$basePath    = str_replace(APPLICATION_PATH , ROOT_PATH . DS ."myV1"  , USER_AVATAR_PATH );
						$avatarPath  = str_replace( DS , "/" , $basePath . DS . "mini" .DS );
						$returnJson  = array("success" => sprintf("Votre avatar a été mis à jour avec succès" , $user->lastname , $user->firstname),
								"files"   => array(array("name" => $avatarBaseName , "extension"  => $avatarExtension , "path"  => $avatarPath )) );
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson($returnJson);
						exit;
					}
					$this->setRedirect(sprintf("L'avatar du profil de %s %s a été mis à jour avec succès" , $user->lastname,$user->firstname),"success");
					$this->redirect("myprofile/infos");
				}
			}	else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames      = $this->_helper->csrf->getFormNames( array("profileid") , true );
		}
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->profileid      = $profile->profileid;
		$this->render("avatarupload");
	}
			
	public function presentationAction()
	{
		$this->view->title   = "Mettre à jour ma  présentation";
		
		$model               = $this->getModel("profile");		
		$profile             = $model->getRow( 0 ,true , false );
		$defaultData         = $profile->toArray();
		$errorMessages       = array();
		$csrfFormNamesArray  = array("professionalstate","profileid","presentation");
		$csrfTokenId         = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue      = $this->_helper->csrf->getToken(500 );
		$csrfFormNames       = $this->_helper->csrf->getFormNames( $csrfFormNamesArray , false );		
		$params              = $this->_request->getParams();
		
		if( $this->_request->isPost() && isset( $params[$csrfFormNames["presentation"]] ) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				$postData                      = $this->_request->getPost();
				
				$postData["presentation"]      = ( isset( $postData[$csrfFormNames["presentation"]] ) )     ? $postData[$csrfFormNames["presentation"]]  : "";
				$postData["professionalstate"] = ( isset( $postData[$csrfFormNames["professionalstate"]] )) ? $postData[$csrfFormNames["professionalstate"]] : "";
				
				$update_data     = array_merge( $defaultData , $postData);
				$me              = Sirah_Fabric::getUser();
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter    = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				
				//On crée les validateurs nécessaires
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
					
				if(!$strNotEmptyValidator->isValid( $update_data["presentation"] ) ) {
					$errorMessages[]  = "Vous devrez entrer quelques mots sur votre présentation";
				} else {
					$update_data["presentation"] = substr($stringFilter->filter($update_data["presentation"]), 0 , 1000 );
				}
				if(!$strNotEmptyValidator->isValid( $update_data["professionalstate"] ) ) {
					$errorMessages[]  = "Vous devrez entrer quelques mots sur votre statut professionnel";
				} else {
					$update_data["professionalstate"] = $stringFilter->filter($update_data["professionalstate"]);
				}
				$update_data["profileid"]       = $profile->profileid;
				$defaultData                    = $update_data;
				//on sauvegarde la table
				$profile->setFromArray($update_data);
				if(empty( $errorMessages ) ) {
					if( $profile->save()) {
						$profileTable    = $profile->getTable();
						$tableName       = $profileTable->info("name");
						$prefixCacheId   = "row_".$tableName;
						$cacheId         = "pk_".$profile->userid;
						$profile->saveToMemory($profile , $cacheId , $prefixCacheId);
						if( $this->_request->isXmlHttpRequest() ) {
							$jsonArray            = $update_data;
							$jsonArray["success"] = "La présentation de votre profil a été mise à jour";
							$this->_helper->viewRenderer->setNoRender(true);
							echo ZendX_JQuery::encodeJson($jsonArray);
							exit;
						}
						$this->setRedirect("La présentation de votre profil a été mise à jour" , "success");
						$this->redirect("myprofile/infos")	;
					}  else {
						if( $this->_request->isXmlHttpRequest()) {
							$jsonArray            = $update_data;
							$jsonArray["error"]   = "Aucune modification n'a été faite dans les informations de la présentation ";
							$this->_helper->viewRenderer->setNoRender(true);
							echo ZendX_JQuery::encodeJson($jsonArray);
							exit;
						}
						$this->setRedirect("Aucune modification n'a été faite dans les informations de la présentation" , "message");
						$this->redirect("myprofile/infos")	;
					}
				} else {
					$defaultData   = $update_data;
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
						exit;
					}
					foreach($errorMessages as $message) {
						$this->_helper->Message->addMessage($message) ;
					}
				}
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( $csrfFormNamesArray , true );		
		}	
		$this->view->data           = $defaultData;
		$this->view->profileid      = $profile->profileid;
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->render("edit-presentation")	;
	}
		
	public function editAction()
	{		
		$this->view->title   = "Mettre à jour les informations de mon profil";
		
		$model               = $this->getModel("profile");
		
		$profile             = $model->getRow(0 ,true , false );
		$defaultData         = $profile->toArray();
		$errorMessages       = array();
		$csrfFormNamesArray  = array("firstname", "lastname", "socialstate", "professionalstate","profileid", "sexe", "birthday",
				                     "presentation", "birthaddress", "birthday_year", "birthday_day","birthday_month", "language");
		$csrfTokenId         = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue      = $this->_helper->csrf->getToken(500 );
		$csrfFormNames       = $this->_helper->csrf->getFormNames( $csrfFormNamesArray , false );
		
		if( $this->_request->isPost() && isset( $_POST[$csrfFormNames["firstname"]] ) )    {
			if( $this->_helper->csrf->isValid( ) ) {
				$postData                      = $this->_request->getPost();
				$formData                      = array();
				$formData["lastname"]          = ( isset( $postData[$csrfFormNames["lastname"]] ) )          ? $postData[$csrfFormNames["lastname"]]            : "";
				$formData["firstname"]         = ( isset( $postData[$csrfFormNames["firstname"]] ) )         ? $postData[$csrfFormNames["firstname"]]           : "";
				$formData["birthday_year"]     = ( isset( $postData[$csrfFormNames["birthday_year"]]) )      ? $postData[$csrfFormNames["birthday_year"]]       : "";
				$formData["birthday_month"]    = ( isset( $postData[$csrfFormNames["birthday_month"]] ))     ? $postData[$csrfFormNames["birthday_month"]]      : "";
				$formData["birthday_day"]      = ( isset( $postData[$csrfFormNames["birthday_day"]] ) )      ? $postData[$csrfFormNames["birthday_day"]]        : "";
				$formData["presentation"]      = ( isset( $postData[$csrfFormNames["presentation"]] ) )      ? $postData[$csrfFormNames["presentation"]]        : "";
				$formData["sexe"]              = ( isset( $postData[$csrfFormNames["sexe"]] ) )              ? $postData[$csrfFormNames["sexe"]]                : "";
				$formData["professionalstate"] = ( isset( $postData[$csrfFormNames["professionalstate"]] ) ) ? $postData[$csrfFormNames["professionalstate"]]   : "";
				$formData["socialstate"]       = ( isset( $postData[$csrfFormNames["socialstate"]] ) )       ? $postData[$csrfFormNames["socialstate"]]         : "";
				$formData["birthaddress"]      = ( isset( $postData[$csrfFormNames["birthaddress"]] ) )      ? $postData[$csrfFormNames["birthaddress"]]        : "";
				$formData["language"]          = ( isset( $postData[$csrfFormNames["language"]] ) )          ? $postData[$csrfFormNames["language"]]            : "";
					
				$update_data                   = array_merge( $defaultData , $formData );
				$me                            = Sirah_Fabric::getUser();
					
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter    = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
					
				//On crée les validateurs nécessaires
				$strNotEmptyValidator             = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				$nameValidator                    = new Validator_Name();
					
				if(!$strNotEmptyValidator->isValid( $update_data["firstname"] ) || ( !$nameValidator->isValid( $update_data["firstname"] ) ) ) {
					$errorMessages[]              = "Le prénom que vous avez saisi est invalide";
				} else {
					$update_data["firstname"]     = $stringFilter->filter($update_data["firstname"]);
				}
				if(!$strNotEmptyValidator->isValid( $update_data["lastname"] ) || ( !$nameValidator->isValid( $update_data["lastname"] ) ) ) {
					$errorMessages[]              = "Le prénom que vous avez saisi est invalide";
				} else {
					$update_data["lastname"]      = $stringFilter->filter($update_data["lastname"]);
				}
				$postData["birthday_year"]        = (isset($formData["birthday_year"]))  ? sprintf("%04d",$formData["birthday_year"])  : "0000";
				$postData["birthday_month"]       = (isset($formData["birthday_month"])) ? sprintf("%02d",$formData["birthday_month"]) : "00";
				$postData["birthday_day"]         = (isset($formData["birthday_day"]))   ? sprintf("%02d",$formData["birthday_day"])   : "00";
				$zendBirthDay                     = new Zend_Date(array( "year" => $postData["birthday_year"] ,
						                                                 "month"=> $postData["birthday_month"],
						                                                 "day"  => $postData["birthday_day"]  ));
				$update_data["birthaddress"]      = $stringFilter->filter( $update_data["birthaddress"]      );
				$update_data["presentation"]      = $stringFilter->filter( $update_data["presentation"]      );
				$update_data["socialstate"]       = $stringFilter->filter( $update_data["socialstate"]       );
				$update_data["professionalstate"] = $stringFilter->filter( $update_data["professionalstate"] );
				$update_data["language"]          = $stringFilter->filter( $update_data["language"]);
				$update_data["sexe"]              = $stringFilter->filter( $update_data["sexe"]);
				$update_data["birthday"]          = $zendBirthDay->getTimestamp();
				$update_data["updateduserid"]     = $me->userid;
				$update_data["updatedate"]        = time();
				
				$defaultData                      = $update_data;
				//on sauvegarde la table
				$profile->setFromArray( $update_data );
				if(empty( $errorMessages ) ) {
					if( $profile->save() ) {
						$me->save( $update_data );
						$profileTable     = $profile->getTable();
						$tableName        = $profileTable->info("name");
						$prefixCacheId    = "row_".$tableName;
						$cacheId          = "pk_".$profile->userid;
						$profile->saveToMemory($profile , $cacheId , $prefixCacheId);
						if( $this->_request->isXmlHttpRequest() ) {
							$jsonArray            = $update_data;
							$jsonArray["success"] = "La mise à jour des informations de votre profil a été effectuée avec succès";
							$this->_helper->viewRenderer->setNoRender(true);
							echo ZendX_JQuery::encodeJson($jsonArray);
							exit;
						}
						$this->setRedirect("La mise à jour des informations de votre profil a été effectuée avec succès" , "success");
						$this->redirect("myprofile/infos")	;
					}  else {
						if( $this->_request->isXmlHttpRequest()) {
							$jsonArray            = $update_data;
							$jsonArray["error"]   = "Aucune modification n'a été appliquée à vos informations";
							$this->_helper->viewRenderer->setNoRender(true);
							echo ZendX_JQuery::encodeJson($jsonArray);
							exit;
						}
						$this->setRedirect("Aucune modification n'a été appliquée à vos informations" , "message");
						$this->redirect("myprofile/infos")	;
					}
				} else {
					$defaultData   = $update_data;
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
						exit;
					}
					foreach($errorMessages as $message) {
						$this->_helper->Message->addMessage($message) ;
					}
				}
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( $csrfFormNamesArray , true );						
		}
		if(!isset($defaultData["birthday_year"]) && intval($defaultData["birthday"])) {
			$zendBirthDay                  = new Zend_Date($defaultData["birthday"] , Zend_Date::TIMESTAMP );
			$defaultData["birthday_year"]  = $zendBirthDay->get(Zend_Date::YEAR);
			$defaultData["birthday_month"] = $zendBirthDay->get(Zend_Date::MONTH);
			$defaultData["birthday_day"]   = $zendBirthDay->get(Zend_Date::DAY);
		} else {
			$defaultData["birthday_year"]  = "0000";
			$defaultData["birthday_month"] = "0";
			$defaultData["birthday_day"]   = "0";
		}
		$defaultData["sexe"]        = ( !empty( $defaultData["sexe"] ) ) ? $defaultData["sexe"] : "M";
		$this->view->data           = $defaultData;
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->render("edit-profile")	;	
	}
	
	public function addressAction()
	{
		$this->view->title   = "Mettre à jour mes contacts";
		
		$errorMessages       = array();
		$model               = $this->getModel("profile");
		$modelCoordonnees    = $this->getModel("profilecoordonnee");
		$modelCity           = $this->getModel("countrycity");
		$profile             = $model->getRow(0 ,true , false );

		$csrfTokenId         = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue      = $this->_helper->csrf->getToken(500 );
		$csrfFormNames       = $this->_helper->csrf->getFormNames(array("email", "tel_dom", "tel_bureau", "tel_mob", "profileid") , false );
		
		$coordonnees         = $modelCoordonnees->findRow($profile->profileid);
		$defaultData         = (isset( $coordonnees->profileid ) ) ? $coordonnees->toArray() : array();
		$params              = $this->_request->getParams();
		
		if( $this->_request->isPost() && isset( $params[$csrfFormNames["email"]] ) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				$postData               = $this->_request->getPost();					
				$postData["email"]      = ( isset( $postData[$csrfFormNames["email"]] ) )    ? $postData[$csrfFormNames["email"]]   : "";
				$postData["tel_mob"]    = ( isset( $postData[$csrfFormNames["tel_mob"]] ) )  ? $postData[$csrfFormNames["tel_mob"]] : "";
				$postData["tel_dom"]    = ( isset( $postData[$csrfFormNames["tel_dom"]] ) )  ? $postData[$csrfFormNames["tel_dom"]] : "";
				$postData["tel_bureau"] = ( isset( $postData[$csrfFormNames["tel_bureau"]])) ? $postData[$csrfFormNames["tel_bureau"]] : "";
										
				$update_data            = array_merge( $defaultData , $postData);
				$me                     = Sirah_Fabric::getUser();
				$userTable              = $me->getTable();
				$prefixName             = $userTable->info("namePrefix");
				$adapter                = $dbAdapter = Sirah_Fabric::getDbo();
					
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter    = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				
				$emailValidator      = new Sirah_Validateur_Email();
					
				//On crée les validateurs nécessaires
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
					
				$update_data["email"] = $stringFilter->filter($update_data["email"]);
				/*					
				$emailSelect          = $adapter->select()->from( $prefixName . "system_users_profile_coordonnees")->where("email = ?", $update_data["email"]);
				if(isset($coordonnees->email)) {
					$emailSelect->where("email != ?" , $coordonnees->email);
				}
				$rowEmail        = $adapter->fetchRow( $emailSelect );*/
				if( !$emailValidator->isValid( $update_data["email"] ) ) {
					$errorMessages[]  = sprintf("L'adresse email %s que vous avez saisie est invalide", $update_data["email"] );
				} elseif(!$userTable->checkEmail( $update_data["email"] ) )  {
					$errorMessages[]  = sprintf("L'adresse email %s est associée à un autre compte. Si vous en etes le propriétaire, vous pouvez nous contacter", $update_data["email"]);
				} 
				if( !$update_data["city"] && isset($update_data["ville"] ) ) {
					if( $strNotEmptyValidator->isValid( $update_data["ville"] ) ) {
						$libelleVille  = $stringFilter->filter( $update_data["ville"]);
						$rowCity       = $modelCity->findRow( $libelleVille , "libelle" , null , false);
						if( $rowCity ) {
							$update_data["city"]     = $rowCity->id;
							unset($update_data["ville"]);
						} else {
							$libelleVille = $stringFilter->filter( $update_data["ville"] );
							if( $dbAdapter->insert( $prefixName . "system_countries_cities", array("libelle" => $libelleVille , "creatorid" => $me->userid , "creationdate" => time() ) ) ) {
								$update_data["city"] = $dbAdapter->lastInsertId();
								unset($update_data["ville"]);
							}
						}
					}
				}
				$update_data["tel_bureau"]   = $stringFilter->filter($update_data["tel_bureau"]);
				$update_data["tel_dom"]      = $stringFilter->filter($update_data["tel_dom"]);
				$update_data["tel_mob"]      = $stringFilter->filter($update_data["tel_mob"]);
				$update_data["rue"]          = $stringFilter->filter($update_data["rue"]);
				$update_data["city"]         = intval($update_data["city"]);
				$update_data["address"]      = $stringFilter->filter($update_data["address"]);
				$update_data["department"]   = $stringFilter->filter($update_data["department"]);
				$update_data["country"]      = $stringFilter->filter($update_data["country"]);
				$update_data["code_postal"]  = $stringFilter->filter($update_data["code_postal"]);
				$update_data["updatedate"]   = time();
				$update_data["profileid"]    = $profile->profileid;
				$defaultData                 = $update_data;					
				if(empty($errorMessages))    {
					$coordonnees->setFromArray( $update_data );
					if( $coordonnees->save() ) {
						$userData            = $update_data;
						$userData["phone1"]  = $update_data["tel_mob"];
						$userData["phone2"]  = $update_data["tel_dom"];
						$me->save( $userData );
						$rowCacheKey  = $coordonnees->getRowCacheKey("profileid");
						$coordonnees->saveToMemory( $coordonnees , $rowCacheKey , "");
						if( $this->_request->isXmlHttpRequest() ) {
							$jsonArray  =  $update_data;
							$jsonArray["success"]  =  " Vos coordonnées ont été mises à jour avec succès ";
							echo ZendX_JQuery::encodeJson($jsonArray);
							exit;
						}
						$this->setRedirect("Vos coordonnées ont été mises à jour avec succès" , "success");
						$this->redirect("myprofile/infos");							
					} else {
						if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender(true);
							echo ZendX_JQuery::encodeJson(array("error"  => "Aucune modification n'a té faite sur vos coordonnées"));
							exit;
						}
						$this->setRedirect("Aucune modification n'a té faite sur vos coordonnées" , "message");
						$this->redirect("myprofile/infos");
					}
				} else {
					if( $this->_request->isXmlHttprequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => "  ".implode(" , " , $errorMessages)));
						exit;
					}
					foreach( $errorMessages as $message ) {
						     $this->_helper->Message->addMessage( $message ) ;
					}
				}
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( $csrfFormNamesArray , true );					
		}
		$cityRow                    = ( $coordonnees ) ? $coordonnees->findParentRow("Table_Countrycities")	: null;	
		$this->view->data           = $defaultData;
		$this->view->ville          = ( $cityRow ) ? $cityRow->libelle : "";
		$this->view->cities         = $modelCity->getTypeaheadList( 10 , null , array("id DESC", "libelle ASC") );
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->render("edit-contacts");
	} 
		
	public function insertdomaineAction()
	{		
		$this->view->title       = " Enregistrer mes secteurs d'activités";
		$errorMessages           = array();
		
		$csrfTokenId             = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue          = $this->_helper->csrf->getToken(200);
		$csrfFormNames           = $this->_helper->csrf->getFormNames( array("domaines") , false );
		$params                  = $this->_request->getParams();
		$model                   = $this->getModel("domaine");
		$modelProfile            = $this->getModel("profile");
		$user                    = Sirah_Fabric::getUser();
		$profile                 = $modelProfile->getRow( 0 ,true , false );
		
		if( $this->_request->isPost( ) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				//On crée les filtres qui seront utilisés sur les données du formulaire
				$stringFilter    = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				$userTable       = $user->getTable();
				$prefixName      = $userTable->info("namePrefix");
				$dbAdapter       = Sirah_Fabric::getDbo();
								
				$postData        = $this->_request->getPost();
				$domaines        = ( isset( $postData["domaines"] ) ) ? $postData["domaines"] : array();
				$newDomaines     = array();
				if( count(   $domaines ) ) {					
					foreach( $domaines as $domaineLibelle ) {
						if( is_numeric( $domaineLibelle ) ) {
							$domaineRow = $model->findRow( intval( $domaineLibelle ), "id" , null , false );
						} else {
							$domaineRow = $model->findRow( $stringFilter->filter( $domaineLibelle ), "libelle" , null , false );
						}						
						$domaineId  = ( $domaineRow ) ? $domaineRow->id : null;
						if( !$domaineRow && $strNotEmptyValidator->isValid( $domaineLibelle ) ) {
							$domaineLibelle = $stringFilter->filter( $domaineLibelle );
							if( $dbAdapter->insert( $prefixName . "system_offre_domaines", array("libelle" => $domaineLibelle, "creationdate" => time(), "creatorid" => $user->userid ) ) ) {
								$domaineId  = $dbAdapter->lastInsertId();
								$domaineRow = $model->findRow( $domaineId, "id", null , false );
							}
						}
						//On vérifie pour voir s'il n'ya pas une entrée de ce domaine pour l'utilisateur
						$checkRow   = $dbAdapter->fetchCol( $dbAdapter->select()->from(array("PD" => $prefixName . "system_users_profile_domaines"), array("domaineid"))
								                                                ->where("PD.domaineid = ?", intval( $domaineId ) )->where("PD.profileid = ?", $profile->profileid ) );
						if( $domaineRow && !isset( $checkRow[0] ) ) {
							$insert_domaine  = array("domaineid" => $domaineId, "profileid" => $profile->profileid, "creationdate" => time(), "creatorid" => $user->userid);
							if( $dbAdapter->insert( $prefixName . "system_users_profile_domaines", $insert_domaine ) ) {
								$newDomaines[$domaineId]            = $insert_domaine;
								$newDomaines[$domaineId]["domaine"] = $domaineRow->libelle;
							}
						}
					}
				}
				if( !count( $newDomaines ) ) {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						echo ZendX_JQuery::encodeJson( array("error" => "Aucun secteur d'activité valide n'a été saisi"));
						exit;
					}
					$this->setRedirect("Aucun secteur d'activité valide n'a été saisi" , "error" );
					$this->redirect("myprofile/infos");
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						$jsonReturn             = array();
						$jsonReturn["domaines"]     = $newDomaines;
						$jsonReturn["success"]  = "Les secteurs d'activités que vous avez saisis ont été rattachés à votre profil";
						echo ZendX_JQuery::encodeJson( $jsonReturn );
						exit;
					}
					$this->setRedirect("Les secteurs d'activités que vous avez saisis ont été rattachés à votre profil" , "success");
					$this->redirect("myprofile/infos");
				}
			}	else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( array("profileid") , true );
		}
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
		$this->view->profileid      = $profile->profileid;
		$this->view->domaines       = $model->getSelectListe("Saisissez des secteurs d'activité" , array("id", "libelle") , array() , null , null , false );
		
		$this->render("insertdomaine");
	}
	
	public function insertcompetenceAction()
	{
		$this->view->title       = "Enregistrer une compétence";
	
		$modelProfile            = $this->getModel("profile");
		$model                   = $this->getModel("profession");
		$profile                 = $modelProfile->getRow(0 ,true , false );
		$errorMessages           = array();
		$data                    = array();
		$levels                  = array( 0 => "Selectionnez le niveau",    1 => "Debutant", 2 => "Moyen",
				                          3 => "Intermédiaire", 4 => "Bon", 5 => "Excellent");
		if( $this->_request->isPost( ) ) {
			$postData            = $this->_request->getPost();
			$me                  = Sirah_Fabric::getUser();
			$userTable           = $me->getTable();
			$dbAdapter           = $userTable->getAdapter();
			$prefixName          = $userTable->info("namePrefix");
			$competences         = ( isset( $postData["competences"] ) ) ? $postData["competences"] : array();
			$savedCompetences    = array();
				
			//On crée les filtres qui seront utilisés sur les paramètres de recherche
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" ,"zero" ,"string" ,"float" ,"empty_array" ,"null"));
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			if( is_string( $competences ) ) {
				$competences    = array( $competences );
			}
			if( count(   $competences ) ) {
				foreach( $competences as $ligne ) {
					$profession    = ( isset( $postData["profession".$ligne] ) )      ? $stringFilter->filter( $postData["profession".$ligne] )   : "";
					$professionid  = ( isset( $postData["professionid".$ligne] ) )    ? intval( $postData["professionid".$ligne] ) : 0;
					$level         = ( isset( $postData["professionLevel".$ligne] ) ) ? intval( $postData["professionLevel".$ligne] ) : "";
					$appreciation  = ( isset( $postData["appreciation".$ligne] ) )    ? $stringFilter->filter( $postData["appreciation".$ligne] ) : "";	
					if( !$professionid && !$strNotEmptyValidator->isValid( $profession ) ) {
						continue;
					}	
					if(!$professionid && $strNotEmptyValidator->isValid( $profession ) ) {
						$rowCompetence  = $model->findRow( $profession , "libelle" , null ,false );
						if( $rowCompetence ) {
							$professionid = $rowCompetence->id;
						} else {
							if( !$dbAdapter->insert( $prefixName."system_offre_professions", array("libelle"=> $profession,"creationdate"=> time(),"creatorid" => $me->userid))) {
								$errorMessages[] = sprintf("La profession que vous avez saisie à la ligne %d n'a pas pu etre enregistrée", $ligne );
								continue;
							}
						}
					}
					if( !$professionid || !$strNotEmptyValidator->isValid( $profession ) ) {
						$errorMessages[]  = sprintf("La profession que vous avez saisie à la ligne %d n'est pas valide", $ligne );
						continue;
					}
					if( !isset( $levels[ $level ] ) ) {
						$errorMessages[]  = sprintf("Veuillez selectionner un niveau valide de la compétence à la ligne %d", $ligne);
						continue;
					}
					$insertRow  = array("creationdate" => time(),"creatorid" => $me->userid, "level" => $level,"professionid" => $professionid,
							            "appreciation" => $appreciation , "profileid" => $profile->profileid );
					if( !$dbAdapter->insert($prefixName . "system_users_profile_professions",  $insertRow) ) {
						$errorMessages[] = sprintf(" La compétence saisie à la ligne %d n'a pas pu etre enregistrée", $ligne );
						continue;
					}	else {
						$insertRow["competenceid"] = $dbAdapter->lastInsertId();
						$insertRow["profession"]   = $profession;
					}
					$savedCompetences[$insertRow["competenceid"]] = $insertRow;
				}
				if( empty( $errorMessages ) ) {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						$this->_helper->layout->disableLayout( true );
						$returnJson               = array();
						$returnJson["success"]    = "Les compétences que vous avez saisies ont été rattachées à votre profil avec succès";
						$returnJson["competences"]= $savedCompetences;
						echo ZendX_JQuery::encodeJson( $returnJson );
						exit;
					}
					$this->setRedirect("Les compétences que vous avez saisies ont été rattachées à votre profil avec succès", "success");
					$this->redirect("myprofile/infos");
				} else {
					if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender( true );
						$this->_helper->layout->disableLayout( true );
						echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
						exit;
					}
					foreach( $errorMessages as $errorMessage ) {
						$this->getHelper("Message")->addMessage( $errorMessage,"error");
					}
				}
			} else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender( true );
					echo ZendX_JQuery::encodeJson( array("error" => "Aucune compétence valide n'a été selectionnée, veuillez saisir des compétences valides et réessayer"));
					exit;
				}
				$this->setRedirect("Aucune compétence valide n'a été selectionnée, veuillez saisir des compétences valides et réessayer", "error" );
				$this->redirect("myprofile/infos");
			}
		}
		$this->view->data        = $data;
		$this->view->professions = $model->getTypeaheadList( 10 , null , array("libelle ASC") );
		$this->render("insertcompetence");
	}
	
	public function removecompetenceAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$modelProfile  = $this->getModel("profile");
		$profile       = $modelProfile->getRow(0 ,true , false );
		$table         = $modelProfile->getTable();
		$dbAdapter     = $table->getAdapter();
		$prefixName    = $table->info("namePrefix");
		$ids           = $this->_getParam("competences",$this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string( $ids ) ) {
			$ids  = explode("," ,$ids);
		}
		$ids      = (array)$ids;
		if( count( $ids ) ) {
			foreach( $ids as $id ) {
				if(!$dbAdapter->delete( $prefixName ."system_users_profile_professions" ,"profileid =".$profile->profileid." AND competenceid =".intval( $id ) )) {
					$errorMessages[]  = " Erreur de la base de donnée l'élement id#$id n'a pas été supprimé ";
				}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete sont invalides ";
		}
		if(count( $errorMessages )) {
			if(   $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error" => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("myprofile/infos");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les compétences fournies ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les compétences fournies ont été supprimées avec succès", "success");
			$this->redirect("myprofile/infos");
		}
	}
			
	public function removelanguageAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$modelProfile  = $this->getModel("profile");
		$profile       = $modelProfile->getRow( 0 , true, false );
		$table         = $modelProfile->getTable();
		$dbAdapter     = $table->getAdapter();
		$prefixName    = $table->info("namePrefix");
		$ids           = $this->_getParam("languages",$this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string( $ids ) ) {
			$ids  = explode("," ,$ids);
		}
		$ids      = (array)$ids;
		if( count( $ids ) ) {
			foreach( $ids as $id ) {
				if(!$dbAdapter->delete( $prefixName ."system_users_profile_languages" ,"profileid =".$profile->profileid." AND languageid =".intval( $id ) )) {
					$errorMessages[]  = " Erreur de la base de donnée l'élement id#$id n'a pas été supprimé ";
				}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete sont invalides ";
		}
		if(count( $errorMessages )) {
			if(   $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("myprofile/infos");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success"  => "Les langues fournies ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les langues fournies ont été supprimées avec succès", "success");
			$this->redirect("myprofile/infos");
		}		
	}
	
	public function removedomaineAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$modelProfile  = $this->getModel("profile");
		$profile       = $modelProfile->getRow( 0 , true , false );
		$table         = $modelProfile->getTable();
		$dbAdapter     = $table->getAdapter();
		$prefixName    = $table->info("namePrefix");
		$ids           = $this->_getParam("domaines",$this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string( $ids ) ) {
			$ids  = explode("," ,$ids);
		}
		$ids      = (array)$ids;
		if( count( $ids ) ) {
			foreach( $ids as $id ) {
				if(!$dbAdapter->delete( $prefixName ."system_users_profile_domaines" , "profileid =".$profile->profileid." AND domaineid =".intval( $id ) )) {
					$errorMessages[]  = " Erreur de la base de donnée l'élement id#$id n'a pas été supprimé ";
				}
			}
		} else {
			$errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete sont invalides ";
		}
		if(count( $errorMessages )) {
			if(   $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				exit;
			}
			foreach($errorMessages as $errorMessage) {
				$this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("myprofile/infos");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les domaines ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les domaines ont été supprimés avec succès", "success");
			$this->redirect("myprofile/infos");
		}
	}
	
	public function uploadAction()
	{
		$this->view->title         = "Enregistrer un nouveau document";
		$me                        = Sirah_Fabric::getUser();
		$modelDocument             = $this->getModel("document");
		$modelCategory             = $this->getModel("documentcategorie");
		$modelProfile              = $this->getModel("profile");
		$defaultData               = $modelDocument->getEmptyData();
		$userDataPath              = $me->getDatapath();
		$errorMessages             = array();
		$uploadedFiles             = array();
		$categories                = $modelCategory->getSelectListe("Selectionnez une catégorie" , array("id" , "libelle") );
		
		$csrfTokenId               = $this->_helper->csrf->getTokenId(15);
		$csrfTokenValue            = $this->_helper->csrf->getToken(500 );
		$csrfFormNames             = $this->_helper->csrf->getFormNames( array("filedescription","filemetadata","category", "filename") , false );
		$params                    = $this->_request->getParams();
		
		if( $this->_request->isPost() && isset( $params[$csrfFormNames["category"]]) ) {
			if( $this->_helper->csrf->isValid( ) ) {
				$postData                     = $this->_request->getPost();
					
				$postData["category"]         = isset( $postData[$csrfFormNames["category"]] )        ? intval( $postData[$csrfFormNames["category"]] ) : 0;
				$postData["filedescription"]  = isset( $postData[$csrfFormNames["filedescription"]] ) ?  $postData[$csrfFormNames["filedescription"]] :"";
				$postData["filemetadata"]     = isset( $postData[$csrfFormNames["filemetadata"]] )    ?  $postData[$csrfFormNames["filemetadata"]] :"";
				$postData["filename"]         = isset( $postData[$csrfFormNames["filename"]] )        ?  $postData[$csrfFormNames["filename"]] :"";
				
				$formData                     = array_intersect_key( $postData ,  $defaultData )	;
				$documentData                 = array_merge( $defaultData ,  $formData );
				$userTable                    = $me->getTable();
				$dbAdapter                    = $userTable->getAdapter();
				$prefixName                   = $userTable->info("namePrefix");
				if( !is_dir( $userDataPath ) ) {
					$errorMessages[]   = "Votre dossier de stockage des documents n'est pas créé, veuillez l'indiquer à l'administrateur ";
				}
				//On crée les filtres qui seront utilisés sur les paramètres de recherche
				$stringFilter          = new Zend_Filter();
				$stringFilter->addFilter(new Zend_Filter_StringTrim());
				$stringFilter->addFilter(new Zend_Filter_StripTags());
					
				//On crée un validateur de filtre
				$strNotEmptyValidator  = new Zend_Validate_NotEmpty(array("integer" , "zero","string","float","empty_array","null"));
				
				$documentData["userid"]         = $me->userid;
				$documentData["category"]       = intval( $documentData["category"] );
				$documentData["resource"]       = ( isset( $postData["resource"] ) )   ? $stringFilter->filter($postData["resource"]) : "" ;
				$documentData["resourceid"]     = ( isset( $postData["resourceid"] ) ) ? intval($postData["resourceid"]) : 0 ;
				$documentData["filedescription"]= $stringFilter->filter( $documentData["filedescription"] );
				$documentData["filemetadata"]   = $stringFilter->filter( $documentData["filemetadata"] );
				
				$userMaxFileSize                = 32;
				$userMaxUploadFileSize          = 25;
				$userSingleFileSize             = 2;
				$userTotalFiles                 = 10;
				
				$documentsUpload                = new Zend_File_Transfer("Http", false , array("useByteString" => false ));
				$documentsUpload->addValidator("Count"    , false , 1 );
				$documentsUpload->addValidator("Extension", false , array("csv", "xls", "xlxs", "pdf","png", "gif", "jpg", "docx" , "doc" , "xml"));
				$documentsUpload->addValidator("Size"     , false , array("max"  => $userSingleFileSize."MB"));
				$documentsUpload->addValidator("FilesSize", false , array("max"  => $userSingleFileSize."MB"));
					
				$basicFilename                  = $documentsUpload->getFileName('mydocument' , false );
				$documentExtension              = Sirah_Filesystem::getFilextension($basicFilename);
				$tmpFilename                    = Sirah_Filesystem::getName( $basicFilename );
				$fileSize                       = $documentsUpload->getFileSize('mydocument');
				$userFilePath                   = $userDataPath . $basicFilename;
					
				$documentsUpload->addFilter("Rename" , array("target" => $userFilePath , "overwrite" => true) , "mydocument");
				//On upload l'avatar de l'utilisateur
				if( $documentsUpload->isUploaded("mydocument")){
					$documentsUpload->receive("mydocument");
				} else {
					$errorMessages[]  = " Le document que vous avez chargé n'est pas valide";
				}
				if( $documentsUpload->isReceived("mydocument") ) {
					$myFilename                     = ( isset( $postData["filename"] ) && $strNotEmptyValidator->isValid( $postData["filename"] ) ) ? $stringFilter->filter( $postData["filename"] ) : $tmpFilename;
					$documentData["filename"]       = $modelDocument->rename( $myFilename , $me->userid );
					$documentData["filepath"]       = $userFilePath ;
					$documentData["filextension"]   = $documentExtension;
					$documentData["filesize"]       = floatval($fileSize);
					$documentData["creationdate"]   = time();
					$documentData["creatoruserid"]  = $me->userid;
					if( $dbAdapter->insert( $prefixName . "system_users_documents"  , $documentData ) ) {
						$documentid                 = $dbAdapter->lastInsertId();
						$profile                    = $modelProfile->getRow( $me->userid , true , false );
						if( $profile ) {
							$profileDocument        = array(
									                         "documentid"    => $documentid ,
									                         "profileid"     => $profile->profileid,
									                         "libelle"       => $documentData["filename"],
									                         "description"   => $documentData["filedescription"],
									                         "type"          => ( isset( $categories[$documentData["category"]] )) ? $categories[$documentData["category"]] : "",
									                         "keys"          => $documentData["filemetadata"] ,
									                         "creatorid"     => $me->userid ,
									                         "creationdate"  => time());
							$dbAdapter->insert( $prefixName . "system_users_profile_documents"  , $profileDocument );
						}
						$uploadedFiles[$documentid] = $documentData;
					} else {
						$errorMessages[]            = "Les informations du document n'ont pas été enregistrées dans la base de données";
					}
				} else {
					$errorMessages[]                = "Le document n'a pas été chargé correctement sur le serveur";
				}
				if( empty($errorMessages ) ) {
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						$jsonArray             = array();
						$jsonArray["success"]  = "Le document a été enregistré avec succès";
						$jsonArray["document"] = $documentData ;
						echo ZendX_JQuery::encodeJson( $jsonArray );
						exit;
					}
					$this->_helper->Message->addMessage("Le document a été enregistré avec succès" , "success");
				} else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->layout->disableLayout(true);
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages ) ));
						exit;
					}
					foreach($errorMessages as $errorMessage){
						$this->getHelper("Message")->addMessage($errorMessage , "error");
					}
				}
			}	else {
				if( $this->_request->isXmlHttpRequest( ) ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("message" => "La durée de validité du formulaire doit etre depassée. Veuillez reprendre l'opération"));
					exit;
				}
			}
			$csrfFormNames          = $this->_helper->csrf->getFormNames( array("filedescription","filemetadata","category", "filename") , true );
		}
		$this->view->categories     = $categories;
		$this->view->data           = $defaultData;
		$this->view->formNames      = $csrfFormNames;
		$this->view->csrfTokenId    = $csrfTokenId;
		$this->view->csrfTokenValue = $csrfTokenValue;
	}
}
