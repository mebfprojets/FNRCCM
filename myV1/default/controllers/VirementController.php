<?php


class VirementController extends Sira_Mvc_Controller
{


 function indexController()
  {

    $this->view->title           = "Gestion des paiements par virements";

    $model                       = $this->getModel("virement");

    $this->view->virements       = $model->getRowset();
    }

   
function detailAction()
{
    $this->view->title           = "Details d'un paiement par virement";

    $id                          = (int)$this->_getParam("id",$this->_getParam("virement",0));
    $model                       = $this->getModel();
    $virement                    = $model->findRow($id);

    if(!$virement)
    {
      $this->_helper->viewRenderer->setNoRender(true);
       if(!$this->_request->isXmlHttpRequest())
       {
         $this->setRedirect("Impossible d'afficher les details de ce chèque, il n'existe pas dans le système","error");
         $this->redirect("cheque/index");
              }
        echo ZendX_JQuery::encodeJson(array("error"=>"Impossible d'afficher les details de ce chèque, il n'existe pas dans le système"));
        exit;
           }

    $this->view->virement        = $virement;
    $this->view->reglement       = $virement->findParentRow("Table_Achatreglements");
    $this->view->paiementvente   = $virement->findParentRow("Table_Ventepaiements");
    }


function ajouterAction()
{

   $this->view->title                     = "Ajouter un paiement par virement";

   if($this->_request->isPost())
   {
     $this->_helper->viewRenderer->setNoRender(true);

     $data                                = $this->_request->getPost();
     $user                                = Sira_Factory::getUser();
     $adapter                             = Zend_Registry::get("db");

     $insert_data                         = array();

     $insert_data["numero"]               = strip_tags(addslashes($data["numero"])); 
     $insert_data["date"]                 = strip_tags(addslashes($data["date"])); 
     $insert_data["numcompte_debiteur"]   = strip_tags(addslashes($data["numcompte_debiteur"]));
     $insert_data["numcompte_reception"]  = strip_tags(addslashes($data["numcompte_reception"]));
     $insert_data["banque"]               = strip_tags(addslashes($data["cheque_banque"]));
     $insert_data["montant"]              = (float)(trim(str_replace(" ","",$data["cheque_montant"])));
     $insert_data["userid"]               = $user->id;

     $insert_data["date_enregistrement"]  = date("Y-m-d H:i:s");

     if($adapter->insert("sira_paiement_virement",$insert_data))
     {
        if(!$this->_request->isXmlHttpRequest())
        {
          $this->setRedirect("L'enregistrement du paiement par virement s'est effectué avec succès","succes");
          $this->redirect("cheque/index");
               }
         echo ZendX_JQuery::encodeJson(array("success"=>"L'enregistrement du paiement par virement s'est effectué avec succès"));
         exit();
           }
      else
      {
        if(!$this->_request->isXmlHttpRequest())
        {
          $this->setRedirect("L'opération d'enregistrement a echoué","erreur");
          $this->redirect("cheque/index");
               }
          echo ZendX_JQuery::encodeJson(array("error"=>"L'opération d'enregistrement a echoué"));
          exit;
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

    if(!$db->delete('sira_paiement_virement',$where))
    {
       if(!$this->_request->isXmlHttpRequest())
       {
          $this->setRedirect("La suppression ne s'est pas effectuée correctement...","erreur");
          $this->redirect("virement/index");
           }
       $return  = array("error"=>"La suppression ne s'est pas effectuée correctement...");
    }  
    else
    {
        if(!$this->_request->isXmlHttpRequest())
       {
          $this->setRedirect("La suppression a ete effectuée avec succes...","succes");
          $this->redirect("virement/index");
           }
        $return=array("success"=>" La suppression a ete effectuée avec succes... ");
     }
      echo ZendX_JQuery::encodeJson($return);
   }



  function modifierAction()
  {
     $this->view->title           =     "Mise à jour d'un paiement par virement";

     $model                       = $this->getModel();

     $id                          = (int)$this->_getParam("id",$this->_getParam("cheque",0));

     $cheque                      = $model->findRow($id);

     if(!$cheque)
     {
        if(!$this->_request->isXmlHttpRequest())
        {
          $this->setRedirect("Impossible de mettre à jour ce paiement par virement, car son identifiant est invalide","error");
          $this->redirect("virement/index");
            }
       echo ZendX_JQuery::encodeJson(array("error"=>"Impossible de mettre à jour ce paiement par virement, car son identifiant est invalide"));
       exit;
           }

      $this->view->cheque          = $cheque;

      if($this->_request->isPost())
      {
        $this->_helper->viewRenderer->setNoRender(true);
        $data                               = $this->_request->getPost();

        $update_data                        = array();
        $update_data["numero"]              = strip_tags(addslashes($data["numero"]));
        $update_data["banque"]              = strip_tags(addslashes($data["banque"]));
        $update_data["numcompte_debiteur"]  = strip_tags(addslashes($data["numcompte_debiteur"]));
        $update_data["numcompte_reception"] = strip_tags(addslashes($data["numcompte_reception"]));
        $update_data["date"]                = strip_tags(addslashes($data["date"]));

        $cheque->setFromArray($update_data);

        if($cheque->save())
        {
            if(!$this->_request->isXmlHttpRequest())
            {
                $this->setRedirect("L'opération s'est effectuée avec succes","succes");
                $this->redirect("virement/index");
                  }
             echo ZendX_JQuery::encodeJson(array("success"=>"L'opération s'est effectuée avec succes"));
             exit();

                }
         else
         {
            if(!$this->_request->isXmlHttpRequest())
            {
                $this->setRedirect("L'opération a echoué","erreur");
                $this->redirect("virement/index");
               }
            echo ZendX_JQuery::encodeJson(array("error"=>"L'opération a echoué"));
            exit();
               }
             }
           }

}
