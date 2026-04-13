<?php

class Admin_UserolesController extends Sirah_Controller_Default

{
	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}
		$this->view->title          = "Liste des roles de l'application";				
		
		$rolename                   = trim(strip_tags($this->_getParam("rolename", null)));
		
		$allRoles                   = Sirah_User_Acl_Table::getAllRoles( $rolename );
		
		$this->view->roles          = $allRoles;		
		$this->view->searchRolename = $rolename;
		$this->view->columns        = array("left");

	}
	
	
	public function createAction()
	{
		$this->view->title    = "Enregistrer un nouveau role";	
			
		$defaultData          = array( "roleid"      => null,
				                       "rolename"    => null,
				                       "description" => null,
				                       "accesslevel" => 0 );		
		$errorMessages        = array();
		
		if($this->_request->isPost()) {
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = Sirah_Fabric::getDbo();
			$tablePrefix     = $userTable->info("namePrefix");
			$data            = $this->_request->getPost();
			$postData        = array_intersect_key( $data ,$defaultData );
			$insert_data     = array_merge( $defaultData , $postData );
			$parents         = (isset($data["parents"])) ? $data["parents"] : array();
			if(!is_array($parents)) {
				$parents     = (array)$parents;
			}			
			 $stringFilter  = new Zend_Filter();
			 $stringFilter->addFilter(new Zend_Filter_StringTrim());
			 $stringFilter->addFilter(new Zend_Filter_StripTags());
			 
			 $strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			 
			 if(!$strNotEmptyValidator->isValid($insert_data["rolename"])) {
			 	$errorMessages[]           = "Vous devrez entrer un libellé valide pour le role";
			 }  else {
			 	$insert_data["rolename"]   = $stringFilter->filter($insert_data["rolename"]);
			 	if(Sirah_User_Acl_Table::getRoleid($insert_data["rolename"])) {
			 		$errorMessages[]       = "Un role existant porte le meme nom , entrez un nom ou libellé différent";
			 	}
			 }
			 if(!$strNotEmptyValidator->isValid($insert_data["description"])) {
			 	$errorMessages[]          = "Vous devrez indiquer une petite description du role";
			 }  else {
			 	$insert_data["description"] = $stringFilter->filter($insert_data["description"]);
			 }
			 $insert_data["accesslevel"]   = intval($insert_data["accesslevel"]);
			 $insert_data["creationdate"]  = time();
			 $insert_data["creatoruserid"] = $me->userid;
			 
			 $defaultData                  = $insert_data;			 
			 if(empty($errorMessages)) {
			  if($dbAdapter->insert($tablePrefix . "system_acl_roles" , $insert_data)) {
			 	$newRoleId  = $dbAdapter->lastInsertId();
			 	if(!empty($parents)) {
			 		foreach($parents as $roleParentId){
			 			if(Sirah_User_Acl_Table::getRolename($roleParentId)){
			 				$rowParent  = array(
			 						             "childroleid"   => $newRoleId,
			 						             "parentroleid"  => $roleParentId,
			 						             "creationdate"  => time(),
			 						             "creatoruserid" => $me->userid);
			 				$dbAdapter->insert($tablePrefix . "system_acl_parentroles" , $rowParent);
			 			}
			 		}
			 	}			 	
			 	if($this->_request->isXmlHttpRequest()) {
			 		$this->_helper->viewRenderer->setNoRender(true);
			 		echo ZendX_JQuery::encodeJson(array("success"  => "Le role a été enregistré avec succès" , "roleid" => $newRoleId));
			 		exit;
			 	}
			 	$this->setRedirect("Le role a été enregistré avec succès" , "success");
			 	$this->redirect("admin/useroles/list");
			 }	
		   } else {
		   	  if($this->_request->isXmlHttpRequest()) {
		   	  	echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
		   	  	exit;
		   	  }
		      foreach($errorMessages as $errorMessage) {
					$this->_helper->Message->addMessage( $errorMessage , "error");
			  }
		   }		
		}		
		$this->view->data      = $defaultData;
		$this->view->roles     = Sirah_User_Acl_Table::getAllRoles();		
	}
	
	public function infosAction()
	{
		$roleid              = intval($this->_getParam("roleid" , $this->_getParam("id" , 0)));
		$roleRow             = Sirah_User_Acl_Table::getRole($roleid);
		if( empty($roleRow) ) {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour cette requete, sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour cette requete, sont invalides" , "error");
			$this->redirect("admin/useroles/list");
		}
		$this->view->title   = "Les informations d'un role";		
		$this->view->role    = $roleRow;
		$this->view->parents = Sirah_User_Acl_Table::getRoleParents($roleid);
		$this->view->roles   = Sirah_User_Acl_Table::getAllRoles();
		$this->view->roleid  = $roleid;
	}
	
	
	public function editAction()
	{
		$this->view->title   = "Mettre à jour les informations d'un role";
		
		$roleid              = intval($this->_getParam("roleid" , $this->_getParam("id" , 0)));		
		$roleRow             = $defaultData = Sirah_User_Acl_Table::getRole($roleid);		
		if(empty($roleRow)){
			if($this->_request->isXmlHttpRequest()) {
			   echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour la mise à jour du role, sont invalides"));
			   exit;
			}
			$this->setRedirect("Les paramètres fournis pour la mise à jour du role, sont invalides" , "error");
			$this->redirect("admin/useroles/list");
		}
		$currentParents      = Sirah_User_Acl_Table::getRoleParents($roleid);
		if($this->_request->isPost()) {
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = Sirah_Fabric::getDbo();
			$tablePrefix     = $userTable->info("namePrefix");
			$data            = $this->_request->getPost();
			$postData        = array_intersect_key( $data ,$defaultData );
			$update_data     = array_merge( $defaultData , $postData );
			$newParents      = (isset($data["parents"])) ? $data["parents"] : array();
			if(!is_array($newParents)) {
				$newParents  = (array)$newParents;
			}
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));			
			if(!$strNotEmptyValidator->isValid($update_data["rolename"])) {
				$errorMessages[]          = "Vous devrez entrer un libellé valide pour le role";
			}  else {
				$update_data["rolename"]  = $stringFilter->filter($update_data["rolename"]);
				$existantId               = Sirah_User_Acl_Table::getRoleid($update_data["rolename"]);
				if(intval($existantId) && ($existantId != $roleid)) {
					$errorMessages[]      = "Un role existant porte le meme nom , entrez un nom ou un libellé différent";
				}
			}
			if(!$strNotEmptyValidator->isValid($update_data["description"])) {
				$errorMessages[]          = "Vous devrez indiquer une petite description du role";
			}  else {		
			  $update_data["description"] = $stringFilter->filter($update_data["description"]);
			}	
			$update_data["accesslevel"]   = intval($update_data["accesslevel"]);			
			$defaultData                  = $update_data;
			if(empty($errorMessages)) {
				if($dbAdapter->update($tablePrefix . "system_acl_roles" , $update_data , "roleid=".intval($roleid))) {
					$dbAdapter->delete($tablePrefix . "system_acl_parentroles" , "childroleid=".intval($roleid));
					$newParents     = array_flip($newParents);				
						foreach($newParents as $roleParentId => $value){
							if(Sirah_User_Acl_Table::getRolename($roleParentId)){
								$rowParent  = array(
										"childroleid"   => $roleid,
										"parentroleid"  => $roleParentId,
										"creationdate"  => time(),
										"creatoruserid" => $me->userid);
								$dbAdapter->insert($tablePrefix . "system_acl_parentroles" , $rowParent);
							}
						}
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("success"  => "Le role a été mis à jour avec succès" , "roleid" => $roleid));
						exit;
					}
					$this->setRedirect("Le role a été mis  à jour avec succès" , "success");
					$this->redirect("admin/useroles/list");
				} elseif(!empty($newParents)) {
					$dbAdapter->delete($tablePrefix . "system_acl_parentroles" , "childroleid=".intval($roleid));
					$newParents     = array_flip($newParents);
						foreach($newParents as $roleParentId => $value){
							if(Sirah_User_Acl_Table::getRolename($roleParentId)){
								$rowParent  = array(
										"childroleid"   => $roleid,
										"parentroleid"  => $roleParentId,
										"creationdate"  => time(),
										"creatoruserid" => $me->userid);
								$dbAdapter->insert($tablePrefix . "system_acl_parentroles" , $rowParent);
							}
						}
					if($this->_request->isXmlHttpRequest()) {
						echo ZendX_JQuery::encodeJson(array("success"  => sprintf("Les roles parents du role %s ont été mis à jour avec succès " , $update_data["rolename"] ) , "roleid" => $roleid));
						exit;
					}
					$this->setRedirect(sprintf("Les roles parents du role %s ont été mis à jour avec succès " , $update_data["rolename"] ), "success");
					$this->redirect("admin/useroles/list");					
				} else {
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson(array("error" => sprintf("Aucune modification réelle n'a été appliquée sur le role %s " , $update_data["rolename"] )));
						exit;
					}
					$this->setRedirect(sprintf("Aucune modification réelle n'a été appliquée sur le role %s " , $update_data["rolename"] ));
					$this->redirect("admin/useroles/list");
				}
			} else {
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" , " , $errorMessages)));
					exit;
				}
				foreach($errorMessages as $errorMessage) {
					$this->_helper->Message->addMessage( $errorMessage , "error");
				}
			}
		}
		$this->view->data      = $defaultData;
		$this->view->roles     = Sirah_User_Acl_Table::getAllRoles();
	    $this->view->parents   = $currentParents;
	    $this->view->roleid    = $roleid;
	}
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$me              = Sirah_Fabric::getUser();
		$userTable       = $me->getTable();
		$dbAdapter       = Sirah_Fabric::getDbo();
		$tablePrefix     = $userTable->info("namePrefix");
		$errorMessages   = array();
		
		$roleids         = $this->_getParam("roleids",$this->_getParam("ids",array()));
		if(is_string($roleids)) {
			$roleids     = explode("," , $roleids);
		}
		if(!is_array($roleids)){
			$roleids     = (array)$roleids;
		}
		if(!empty($roleids)) {
			foreach($roleids as $roleid) {
				if($dbAdapter->delete($tablePrefix . "system_acl_roles" , "roleid=".intval($roleid))) {
				   $dbAdapter->delete($tablePrefix . "system_acl_parentroles" , "( childroleid=".intval($roleid)." OR parentroleid=".intval($roleid)." )" );
				   $dbAdapter->delete($tablePrefix . "system_acl_useroles" , "roleid=".intval($roleid));
				   $dbAdapter->delete($tablePrefix . "system_acl_rights" , "roleid=".intval($roleid));
				   $dbAdapter->delete($tablePrefix . "system_acl_rights_params" , "roleid=".intval($roleid));
				} else {
					$errorMessages[] = "Le role id#$roleid n'a pas été correctement supprimé";
				}
			}			
		} else {
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun role valide n' até supprimé"));
				exit;
			}
			$this->setRedirect("Aucun role valide n'a été supprimé" , "message");
			$this->redirect("admin/useroles/list");
		}
		
	    if(!empty($errorMessages)) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages)));
				exit;
			}
			$this->setRedirect(implode(" , " , $errorMessages) , "error");
			$this->redirect("admin/useroles/list");
		} else {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("success" => "Le(s) roles selectionnés, ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Le(s) roles selectionnés, ont été supprimés avec succès", "success");
			$this->redirect("admin/useroles/list");
		}		
	}

    public function updaterightsAction()
	{
		$roleid      = intval($this->_getParam("roleid",$this->_getParam("id" , 0)));
		$me          = Sirah_Fabric::getUser();
		$roleRow     = Sirah_User_Acl_Table::getRole($roleid);
		
		if(empty($roleRow)){
			if($this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour la mise à jour du role, sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour la mise à jour du role, sont invalides" , "error");
			$this->redirect("useroles/list");
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
					$allPrivilegeObjects  = explode("-", $allPrivilege);
					if(count($allPrivilegeObjects) >= 2) {
						$allResourceid    = array_shift($allPrivilegeObjects);
						foreach($allPrivilegeObjects as $allPrivilegeObjectId) {
							if($allObjectid = Sirah_User_Acl_Table::getObjectid("all" , $allResourceid)){
								if(isset($objectPrivileges[$allPrivilegeObjectId])) {
									unset($objectPrivileges[$allPrivilegeObjectId]);
								}
								$rightRow = array("objectid"=>$allObjectid,"userid"=> 0,"roleid"=> $roleid,"allow"=> 1,"creationdate" => time(),"creatoruserid"=> $me->userid);
								$dbAdapter->delete($tablePrefix ."system_acl_rights" , array("roleid=".intval($roleid),"objectid=".$allObjectid));
								if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
									$errorMessages[]  = "La permission objectid#".$allObjectid." resourceid#".$allResourceid." n'a pas été enregistrée ";
								}								
							} 
						}
					} else {
						$removedAllResourceid   = array_shift($allPrivilegeObjects);
						if( intval( $removedAllResourceid )) {
							if( $removedAllObjectid = Sirah_User_Acl_Table::getObjectid("all", $removedAllResourceid )){
								$dbAdapter->delete($tablePrefix ."system_acl_rights", array("roleid=".intval($roleid),"objectid=".$removedAllObjectid ));
							}
						}
						
					}
				}
			}
			if(count($objectPrivileges)) {
				foreach($objectPrivileges as $objectPrivilegeId) {
					if($objectPrivilegename = Sirah_User_Acl_Table::getObjectname($objectPrivilegeId)) {
						$rightRow = array("objectid"=> $objectPrivilegeId,"userid"=> 0,"roleid"=> $roleid, "allow"=> 1,"creationdate" => time(),"creatoruserid"=> $me->userid);
						$dbAdapter->delete($tablePrefix ."system_acl_rights" , array("roleid=".intval($roleid),"objectid=".$objectPrivilegeId));
						if(!$dbAdapter->insert($tablePrefix ."system_acl_rights" , $rightRow)) {
							$errorMessages[]  = "La permission objectid#".$objectPrivilegeId." n'a pas été enregistrée ";
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
				$this->redirect("useroles/list");
			}
		}
		$this->view->rightobjects = Sirah_User_Acl_Table::getObjects();
		$this->view->roleRow      = $roleRow;
		$this->view->roleid       = $roleid;
		$this->view->title        = sprintf("Définir les permissions du role `%s` " , $roleRow["rolename"]);
		$this->render("definerights");
	}
	
	public function rightsAction()
	{
		$roleid      = intval($this->_getParam("roleid"  , $this->_getParam("id" , 0)));
		$roleRow     = $defaultData = Sirah_User_Acl_Table::getRole($roleid);
		
		if(!$roleRow) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "L'identifiant du role est incorrect"));
				exit;
			}
			$this->setRedirect("L'identifiant du role est invalide" , "error");
			$this->redirect("useroles/list");
		}
		
		$this->view->rights = Sirah_User_Acl_Table::getRoleRights($roleid);
		$this->view->title  = "Les permissions du role `".$roleRow['rolename']."`";		
	}
	



}