<?php

class Admin_UserightsController extends Sirah_Controller_Default

{
	
	
	public function listAction()
	{
		$roleid             = intval($this->_getParam("roleid"  , $this->_getParam("id" , 0)));
		$roleRow            = Sirah_User_Acl_Table::getRole($roleid);
			
		$this->view->rights       = Sirah_User_Acl_Table::getRoleRights($roleid);
		$this->view->title        = ( $roleRow ) ? "Les permissions du role `<u>".$roleRow['rolename']."</u>`" : "Gerer les permissions d'acces aux fonctionnalites";
		$this->view->roles        = Sirah_User_Acl_Table::getAllRoles();
		$this->view->roleid       = $roleid;
		$this->view->role         = $roleRow;
		$this->view->rightobjects = Sirah_User_Acl_Table::getObjects();

	}			

	public function editAction()
	{
		$roleid      = intval($this->_getParam("roleid",$this->_getParam("id" , 0)));
		$me          = Sirah_Fabric::getUser();
		$roleRow     = Sirah_User_Acl_Table::getRole($roleid);
		
		if(empty($roleRow)){
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete, sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour la mise à jour du role, sont invalides" , "error");
			$this->redirect("admin/useroles/list");
		}	
		$userTable     = $me->getTable();
		$dbAdapter     = Sirah_Fabric::getDbo();
		$tablePrefix   = $userTable->info("namePrefix");
		$errorMessages = array();
	
		if($this->_request->isPost()) {
			$postData         = $this->_request->getPost();
			$allPrivileges    = isset($postData["all"])     ? $postData["all"]     : array();
			$objectPrivileges = isset($postData["objects"]) ? $postData["objects"] : array();				
			//On supprime toutes les permissions existantes de l'utilisateur
			$dbAdapter->delete($tablePrefix ."system_acl_rights" ,"roleid=".intval($roleid));
			if(!is_array($allPrivileges)) {
				$allPrivileges    = (array) $allPrivileges;
			}
			if(!is_array($objectPrivileges)) {
				$objectPrivileges = (array) $objectPrivileges;
			}
			if(count($allPrivileges)) {
				foreach($allPrivileges as $allPrivilege) {
					$allPrivilegeObjects  = explode("-" , $allPrivilege);
					if(count($allPrivilegeObjects) >= 2) {
						$allResourceid   = array_shift($allPrivilegeObjects);
						foreach($allPrivilegeObjects as $allPrivilegeObjectId) {
							if($allObjectid = Sirah_User_Acl_Table::getObjectid("all" , $allResourceid)){
								if(isset($objectPrivileges[$allPrivilegeObjectId])) {
									unset($objectPrivileges[$allPrivilegeObjectId]);
								}
								$rightRow = array("objectid" => $allObjectid, "userid" => 0,"roleid" => $roleid,"allow" => 1,"creationdate" => time(),"creatoruserid"=> $me->userid);
								if(!Sirah_User_Acl_Table::rightExist($rightRow)){
									if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
										$errorMessages[]  = "La permission objectid#".$allObjectid." resourceid#".$allResourceid." n'a pas été enregistrée ";
									}
								}
							} else {
								$errorMessages[]  = "La permission objectid#".$allObjectid." resourceid#".$allResourceid." n'a pas été enregistrée ";
							}
						}
					}
				}
			}
			if(count($objectPrivileges)) {
				foreach($objectPrivileges as $objectPrivilegeId) {
					if( $objectPrivilegename = Sirah_User_Acl_Table::getObjectname($objectPrivilegeId)) {
						$rightRow = array("objectid" => $objectPrivilegeId, "userid" => 0, "roleid" => $roleid, "allow" => 1, "creationdate" => time(),"creatoruserid"=> $me->userid);
						if(!Sirah_User_Acl_Table::rightExist($rightRow)){
							if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
								$errorMessages[] = "La permission objectid#".$objectPrivilegeId." n'a pas été enregistrée ";
							}
						} else {
							    $errorMessages[] = "La permission objectid#".$objectPrivilegeId." userid#".$userid." existe déjà. ";
						}
					}
				}
			}
			if(!empty($errorMessages)) {
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
					exit;
				}
				foreach($errorMessages as $errorMessage) {
					$this->getHelper("Message")->addMessage($errorMessage);
				}
			} else {
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("success"  => sprintf("Les permissions du role %s ont été définies avec succès" , $roleRow["rolename"])));
					exit;
				}
				$this->setRedirect(sprintf("Les permissions du role %s ont été définies avec succès" , $roleRow["rolename"]), "success");
				$this->redirect("admin/useroles/list");
			}
		}
		$this->view->rightobjects = Sirah_User_Acl_Table::getObjects();
		$this->view->roleRow      = $roleRow;
		$this->view->roleid       = $roleid;
		$this->view->title        = sprintf("Mettre à jour les permissions du role `%s` " , $roleRow["rolename"]);
	}


}