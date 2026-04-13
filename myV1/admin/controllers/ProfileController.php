<?php

defined("USER_AVATAR_PATH")
|| define("USER_AVATAR_PATH" , APPLICATION_DATA_USER_PATH . "avatars");

class Admin_ProfileController extends Sirah_Controller_Default
{
	public function init()
	{
		$userid  = intval( $this->_getParam("userid", 0 ) );
		$user    = Sirah_Fabric::getUser( $userid );
		if( !$userid || $user->isGuest( ) )  {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis à cette page sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis à cette page sont invalides", "error");
			$this->redirect("admin/dashboard/list");
		}		
	}
	
	public function infosAction()
	{
		$userid                      = intval( $this->_getParam("userid", 0 ) );
		$me                          = Sirah_Fabric::getUser();
		$model                       = $this->getModel("profile");
		$modelCoordonnees            = $this->getModel("profilecoordonnee");
		$modelAvatar                 = $this->getModel("profileavatar");	
		$profile                     = $model->getRow( $userid ,true , false );
		$coordonnees                 = $modelCoordonnees->findRow( $profile->profileid , "profileid" , null , false );
		$avatar                      = $modelAvatar->findRow($profile->profileid , "profileid" , null , false);
		$carreers                    = $profile->carreers(   null , 5);
		$formations                  = $profile->formations( null , 5);

		$this->view->myuserid        = $me->userid;
		$this->view->profile         = $profile;
		$this->view->coordonnees     = $coordonnees;
		$this->view->avatar          = $avatar;
		$this->view->documents       = $profile->documents();
		$this->view->formations      = $formations;
		$this->view->certifications  = $profile->certifications();
		$this->view->carreers        = $carreers;
		$this->view->languages       = $profile->languages();
		$this->view->domaines        = $profile->domaines();
		$this->view->projects        = $profile->projects();
		$this->view->competences     = $profile->competences();
		$this->view->cvdocs          = $profile->cvdocs();
		$this->view->tags            = $profile->tags();
		$this->view->letterdocs      = $profile->letterdocs();
		$this->view->title           = "Les informations de mon profil";
		$this->view->showProfileMenu = true;
		$this->view->columns         = array("left");
	}

}