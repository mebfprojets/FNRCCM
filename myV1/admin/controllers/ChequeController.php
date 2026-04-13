<?php

class ChequeController extends Sirah_Controller_Default
{


  function indexController()
  {
     $this->view->title           = " Gestion des paiements par cheques";

     $model                       = $this->getModel("cheque");

     $this->view->cheques         = $model->getListe();

    }

   
  function detailAction()
  {
    $this->view->title           = "Details d'un paiement par cheque";

    $id                          = (int)$this->_getParam("id",$this->_getParam("cheque",0));
    $model                       = $this->getModel("paiementcheque");
    $cheque                      = $model->findRow($id);

    if(!$cheque){
        $this->_helper->viewRenderer->setNoRender(true);
        if(!$this->_request->isXmlHttpRequest()){
            $this->setRedirect("Impossible d'afficher les details de ce chèque, il n'existe pas dans le système","error");
            $this->redirect("cheque/index");
        }
            echo ZendX_JQuery::encodeJson(array("error"=>"Impossible d'afficher les details de ce chèque, il n'existe pas dans le système"));
            exit;
   }
    $this->view->cheque          = $cheque;
    $this->view->reglement       = $cheque->findParentRow("Table_Achatreglements");
    $this->view->paiementvente   = $cheque->findParentRow("Table_Paiementventes");

  }


  function ajouterAction()
  {

   $this->view->title           = "Enregistrer un paiement par cheque";

   if($this->_request->isPost())
   {
     $this->_helper->viewRenderer->setNoRender(true);

     $data                                = $this->_request->getPost();
     $user                                = Sira_Fabric::getUser();
     $adapter                             = Sira_Fabric::getDbo();
     $insert_data                         = array();

     $insert_data["numero"]               = strip_tags(addslashes($data["cheque_numero"])); 
     $insert_data["date"]                 = strip_tags(addslashes($data["cheque_date"])); 
     $insert_data["lieu"]                 = strip_tags(addslashes($data["cheque_lieu"])); 
     $insert_data["banque"]               = strip_tags(addslashes($data["cheque_banque"])); 
     $insert_data["montant"]              = (float)(trim(str_replace(" ","",$data["cheque_montant"])));
     $insert_data["numcompte"]            = strip_tags(addslashes($data["numcompte"]));
     $insert_data["userid"]               = $user->id;
     $insert_data["date_enregistrement"]  = date("Y-m-d H:i:s");

     if($adapter->insert("sira_paiement_cheque",$insert_data)){
        $id  = $adapter->lastInsertId();
        if(!$this->_request->isXmlHttpRequest()){
            $this->setRedirect("L'enregistrement du cheque s'est effectué avec succès","succes");
            $this->redirect("cheque/detail/id/".$id);
         }
        echo ZendX_JQuery::encodeJson(array("success"   => "L'enregistrement des informations du cheque s'est effectué avec succès"));
        exit();
      } else {
        if(!$this->_request->isXmlHttpRequest()){
            $this->setRedirect("L'opération d'enregistrement a echoué","erreur");
            $this->redirect("cheque/index");
        }
        echo ZendX_JQuery::encodeJson(array("error"=>"L'opération d'enregistrement a echoué"));
        exit();
            }
        }
     }

function supprimerAction()
{
     $db        =  Zend_Registry::get("db");

     $this->_helper->layout->disableLayout();
     $this->_helper->viewRenderer->setNoRender(true);

     $return     = array();
     $where      = array();
     $ids        = $this->view->escape($this->_getParam('id'));    
  
     $where[]    = " id IN ({$ids}) ";

    if(!$db->delete('sira_paiement_cheque',$where))
    {
       if(!$this->_request->isXmlHttpRequest())
       {
          $this->setRedirect("La suppression ne s'est pas effectuée correctement...","erreur");
          $this->redirect("cheque/index");
           }
       $return=array("error"=>"La suppression ne s'est pas effectuée correctement...");
    }  
    else
    {
       if(!$this->_request->isXmlHttpRequest())
       {
          $this->setRedirect("La suppression a ete effectuée avec succes...","succes");
          $this->redirect("cheque/index");
           }
        $return=array("success"=>" La suppression a ete effectuée avec succes... ");
     }
      echo ZendX_JQuery::encodeJson($return);
   }



  function modifierAction()
  {
     $this->view->title           = "Mise à jour d'un paiement par chèque";

     $model                       = $this->getModel();

     $id                          = (int)$this->_getParam("id",$this->_getParam("cheque",0));
     $cheque                      = $model->findRow($id);

     if(!$cheque)
     {
            $this->triggerError(" Impossible de mettre à jour ce paiement par cheque, car son identifiant est invalide ");
           }

      $this->view->cheque          = $cheque;

      if($this->_request->isPost())
      {
        $this->_helper->viewRenderer->setNoRender(true);

        $data                      = $this->_request->getPost();

        $update_data               = array();

        $update_data["numero"]     = strip_tags(addslashes($data["numero"]));        
        $update_data["banque"]     = strip_tags(addslashes($data["banque"]));
        $update_data["date"]       = strip_tags(addslashes($data["date"]));
        $update_data["lieu"]       = strip_tags(addslashes($data["lieu"]));
        $update_data["numcompte"]  = strip_tags(addslashes($data["numcompte"]));

        $cheque->setFromArray($update_data);

        if($cheque->save())
        {
            if(!$this->_request->isXmlHttpRequest())
            {
                $this->setRedirect("L'opération s'est effectuée avec succes","succes");
                $this->redirect("cheque/index");
                  }
             echo ZendX_JQuery::encodeJson(array("success"=>"L'opération s'est effectuée avec succes"));
             exit;
              }
         else
         {
            if(!$this->_request->isXmlHttpRequest())
            {
                $this->setRedirect("L'opération a echoué","erreur");
                $this->redirect("cheque/index");

                 }
            echo ZendX_JQuery::encodeJson(array("error"=>"L'opération a echoué"));
            exit();
               }
             }
           }

}
