<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */
defined("PAYMENT_GATEWAY_APIKEY")
    || define("PAYMENT_GATEWAY_APIKEY","1726210322638da41d8edad2.11557689");	
defined("PAYMENT_GATEWAY_SITE_ID")
    || define("PAYMENT_GATEWAY_SITE_ID","448705");	 
defined("APPLICATION_ORDERCART_EXPIRATION")
    || define("APPLICATION_ORDERCART_EXPIRATION",10800);	

defined("JWT_SECRETE")
    || define("JWT_SECRETE","f1650d56-15a0-11ed-861d-0242ac120002");	
 
require 'vendor/autoload.php';
 

require_once("tcpdf/tcpdf.php");
class PaymentsController extends Sirah_Controller_Default
{
	
	protected $_member  = null;
	
	protected $_cart    = null;
	
	public function init()
	{
		$orderCart      = new Zend_Session_Namespace("ordercart");
	
		if(!isset($orderCart->initialised) || !$orderCart->initialised ) {
			$orderCart->articles    = array();
			$orderCart->registres   = array();
			$orderCart->documents   = array();
			$orderCart->requests    = array();
			$orderCart->counter     = 0;
			$orderCart->initialised = true;
			$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
		}
		 
		$this->_cart  = &$orderCart;
		parent::init();
	}
	
	
	public function createAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                                            = &$this->view;
		$orderCart                                       = new Zend_Session_Namespace("ordercart");
		$application                                     = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($orderCart->commande) || !isset($orderCart->commandeid)){
		    $orderCart->commande                         = array();
			$orderCart->commandeid                       = 0;
		}
		
		$commandeid                                      = intval($this->_getParam("commandeid", $this->_getParam("orderid", $orderCart->commandeid)));
		$model                                           = $modelCommande = $this->getModel("commande");
		$modelPaiement                                   = $this->getModel("commandepaiement");
		$modelInvoice                                    = $this->getModel("commandefacture");
		$modelMember                                     = $this->getModel("member");
		$modelCountry                                    = $this->getModel("country");
		
		$modelTable                                      = $model->getTable();
		$dbAdapter                                       = $modelTable->getAdapter();
		$tablePrefix        = $prefixName                = $modelTable->info("namePrefix");
		
		$me                                              = Sirah_Fabric::getUser();
		$accountid                                       = $userid    = $me->userid;
		$member                                          = $this->_member;
		if(!$member ) {
			$accountMember                               = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                                  = $modelMember->findRow($accountMember->memberid,"memberid",null,false);
			}
		}		
		$view->member                                    = $member;
		$commandeRow                                     = $invoiceRow = null;
		if(!$commandeRow                                 = $model->findRow($commandeid,"commandeid",null,false)) {
			$orderCart->commande                         = array();
			$orderCart->invoice                          = array();
			$orderCart->invoiceAddress                   = array();
			$orderCart->commandeItems                    = array();
			$orderCart->commandeid                       = 0;
			$orderCart->invoiceid                        = 0;
			$this->setRedirect("La commande concernée semble inexistante","error");
			$this->redirect("public/orders/create");
		}  
		if( $commandeRow->memberid != $member->memberid) {
			$this->setRedirect("Commande Invalide !","error");
			$this->redirect("public/members/dashboard");
		}
		$memberid                                        = $commandeRow->memberid;
		$commandePaiements                               = $commandeRow->paiements($commandeid);
		$invoiceRow                                      = $modelInvoice->findRow( $commandeid,"commandeid",null,false);
		if(!$invoiceRow ) {
			$this->setRedirect("La facture de la commande concernée semble inexistante","error");
			$this->redirect("public/orders/create");
		}
		$invoiceid                                       = $invoiceRow->invoiceid;
		$orderCart->commande                             = $commandeRow->toArray();
		$orderCart->commandeid                           = $commandeid;
		$orderCart->invoiceid                            = $invoiceid;
		$orderCart->invoice                              = ($invoiceRow)? $invoiceRow->toArray()    : array();
		$orderCart->invoiceAddress                       = ($invoiceRow)? $invoiceRow->billing_address($invoiceid,"array")  : array();
		$orderCart->commandeItems                        = $commandeRow->products($commandeid);
		
		$invoiceAddress                                  = ($invoiceRow)? $invoiceRow->billing_address($invoiceid,"object") : null;
		$modePaiement                                    = ($invoiceRow)? $invoiceRow->modepaiement : 1;
				
		//On génére le numéro de transaction  et on enregistre le paiement
		$transid                                         = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
		$paiementData                                    = array();
		//var_dump($transid); die();
		if(!empty($transid) && !count($commandePaiements)) {
			//on enregistre le mode de paiement
			$methodPaiement                              = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
			$modePaiementData                            = array("numero"=>$transid);
		    $modePaiementData["libelle"]                 = sprintf("TOUCHPAY %s n° %s du %s", $methodPaiement,$transid,date("d/m/Y"));
			$modePaiementData["bankid"]                  = 0;
			$modePaiementData["entrepriseid"]            = 0;
			$modePaiementData["banque"]                  = "TOUCHPAY";
			$modePaiementData["address"]                 = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]                 = $commandeRow->valeur_ttc;
			$modePaiementData["processed"]               = 0;
			$modePaiementData["date"]                    = date("Y-m-d");
			$modePaiementData["creationdate"]            = time();
			$modePaiementData["creatorid"]               = $me->userid;
			$modePaiementData["updatedate"]              = $modePaiementData["updateduserid"] = 0;
			if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements", $modePaiementData)) {
				$modePaiementId                          = $dbAdapter->lastInsertId();
				$webPaiementData                         = array("webpaiementid"=>$modePaiementId,"transactionid"=>$transid);
				$webPaiementData["date"]                 = date("Y-m-d");
				$webPaiementData["status"]               = "UNVALIDATED";
				$webPaiementData["phonenumber"]          = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
				$webPaiementData["countrycode"]          = ($invoiceAddress )? $invoiceAddress->country : $member->country;
				$webPaiementData["payeur"]               = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)  : $member->name;
				$webPaiementData["method"]               = ($modePaiement==1)? "MOBILE_MONEY"           : "CARTE_VISA";
			    $callingCode                             = (!empty($webPaiementData["countrycode"]))?$modelCountry->callingCode($webPaiementData["countrycode"])  : "+226";
				if(!empty($callingCode)) {
					$webPaiementData["phonenumber"]      = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
				}
				if( $dbAdapter->insert( $prefixName."erccm_vente_modepaiements_web",$webPaiementData)) {
					$paiementData                        = array("commandeid"=>$commandeid,"invoiceid"=>$invoiceid,"accountid"=>$accountid,"memberid"=>$memberid,"modepaiement"=>$modePaiement,"modepaiementid"=>$modePaiementId,"validated"=>0,"canceled"=>0,"date"=>time(),"creationdate"=>time());
					$paiementData["numero"]              = $modelPaiement->autoNum(date("Y"));
					$paiementData["num_transaction"]     = "";
					$paiementData["num_commande"]        = $transid;
					$paiementData["libelle"]             = sprintf("Paiement par TOUCHPAY n° %s du %s de la commande n° %s",$transid,date("d/m/Y"),$commandeRow->ref);
				    $paiementData["observation"]         = "";
					$paiementData["montant"]             = $commandeRow->valeur_ttc;
					$paiementData["totalAPayer"]         = $commandeRow->valeur_ttc;
					$paiementData["totalPaid"]           = $commandeRow->valeur_ttc;
					$paiementData["frais_transaction"]   = 0;
					$paiementData["reste"]               = 0;
					$paiementData["creatorid"]           = $me->userid;
					$paiementData["updatedate"]          = $paiementData["updateduserid"] = 0;
					if( $dbAdapter->insert( $prefixName."erccm_vente_commandes_paiements",$paiementData)) {
						$paiementId                      = $paiementData["paiementid"]    = $dbAdapter->lastInsertId();
						$paiementData["transid"]         = $transid;
						$paiementData["status"]          = "UNVALIDATED";
						$paiementData["phonenumber"]     = ($invoiceAddress)? $invoiceAddress->phone   : $member->tel1;
						$paiementData["countrycode"]     = ($invoiceAddress)? $invoiceAddress->country : $member->country;
						$paiementData["city"]            = ($invoiceAddress)? $invoiceAddress->city    : $member->city;
						$paiementData["email"]           = ($invoiceAddress)? $invoiceAddress->email   : $member->email;
						$paiementData["zipcode"]         = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
						$paiementData["address"]         = ($invoiceAddress)? $invoiceAddress->address : $member->address;
						$paiementData["payeur"]          = ($invoiceAddress)? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
						$paiementData["payeur_lastname"] = ($invoiceAddress)? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			            $paiementData["payeur_firstname"]= ($invoiceAddress)? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
						$paiementData["method"]          = ($modePaiement==1)? "MOBILE_MONEY" : "CREDIT_CARD";
					}
				}
			}
		} elseif(!empty($transid) && isset($commandePaiements[0]["transid"]) && count($commandePaiements)) {
			if(!empty($commandePaiements[0]["transid"])) {
				$transid                        = $commandePaiements[0]["transid"];
			} else {
				$transid                        = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			}
			$transid                            = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
			$modePaiementId                     = $commandePaiements[0]["modepaiementid"];
			$modePaiementData                   = array("numero"=>$transid);
		    $modePaiementData["libelle"]        = sprintf("Paiement par TOUCHPAY n° %s du %s",$transid,date("d/m/Y"));
			$modePaiementData["banque"]         = "TOUCHPAY";
			$modePaiementData["address"]        = ($invoiceAddress)? sprintf("%s,email:%s,tel:%s",$invoiceAddress->address,$invoiceAddress->email,$invoiceAddress->phone) : sprintf("%s,email:%s,tel:%s",$member->address,$member->email,$member->tel1);
			$modePaiementData["montant"]        = $commandeRow->valeur_ttc;
			$modePaiementData["processed"]      = 0;
			$modePaiementData["date"]           = date("Y-m-d");
			
			if( $dbAdapter->update( $prefixName."erccm_vente_modepaiements"    , $modePaiementData, array("modepaiementid=?"=>$modePaiementId))){
			    $dbAdapter->update( $prefixName."erccm_vente_modepaiements_web", array("transactionid"=>$transid), array("webpaiementid=?"=>$modePaiementId));
			}			
			$paiementData                       = $commandePaiements[0];
			$paiementId                         = $commandePaiements[0]["paiementid"];
			$paiementData["transid"]            = $transid;
			$paiementData["status"]             = "UNVALIDATED";
			$paiementData["phonenumber"]        = ($invoiceAddress )? $invoiceAddress->phone   : $member->tel1;
			$paiementData["countrycode"]        = ($invoiceAddress )? $invoiceAddress->country : $member->country;
			$paiementData["email"]              = ($invoiceAddress )? $invoiceAddress->email   : $member->email;
			$paiementData["city"]               = ($invoiceAddress )? $invoiceAddress->city    : $member->city;
			$paiementData["zipcode"]            = sprintf("%05d",$modelCountry->zipCode($paiementData["countrycode"]));
			$paiementData["address"]            = ($invoiceAddress )? $invoiceAddress->address : $member->address;
			$paiementData["payeur"]             = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerName)      : $member->name;
			$paiementData["payeur_lastname"]    = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerLastName)  : $member->lastname;
			$paiementData["payeur_firstname"]   = ($invoiceAddress )? sprintf("%s", $invoiceAddress->customerFirstName) : $member->firstname;
			$paiementData["method"]             = ($modePaiement==1)? "MOBILE_MONEY"           : "CREDIT_CARD";
			$callingCode                        = (!empty($paiementData["countrycode"]))?$modelCountry->callingCode($paiementData["countrycode"])  : "+226";
			if(!empty($callingCode)) {
				$webPaiementData["phonenumber"] = str_replace($callingCode,"",$webPaiementData["phonenumber"]);
			}
		}
		if(!isset($paiementData["paiementid"]) || !intval($paiementData["paiementid"])) {
			$this->setRedirect("Le paiement ne peut s'effectuer pour des raisons de sécurité ","error");
			$this->redirect("public/orders/create");
		}
		//On génère l'url de notification
		$notifyURL              = $this->_helper->HttpUri(array("scheme"=>"https","module"=>"public","controller"=>"payments","action"=>"validate","params"=> array("id"=>$paiementId,"orderid"=>$commandeid)));
 		
		$orderCart->paiement    = $paiementData;
		$view->data             = $paiementData;
		$view->commande         = $commandeRow;
		$view->invoice          = $invoiceRow;
		$view->commandeid       = $commandeid;
		$view->paiementid       = $paiementId;
		$view->customer         = $view->member = $member;
		$view->trans_id         = $transid;
		$view->notify_url       = $notifyURL;
		$view->apikey           = (defined("PAYMENT_GATEWAY_APIKEY" ))?PAYMENT_GATEWAY_APIKEY  : "1726210322638da41d8edad2.11557689";
		$view->api_site_id      = (defined("PAYMENT_GATEWAY_SITE_ID"))?PAYMENT_GATEWAY_SITE_ID : "448705";
		$view->title            = "Procéder au paiement de la commande";
	}
	
	
	public function processAction()
	{
		$me                     = Sirah_Fabric::getUser();
		$model                  = $modelCommande = $this->getModel("commande");
		$modelPaiement          = $this->getModel("commandepaiement");
		$modelInvoice           = $this->getModel("commandefacture");
		$modelMember            = $this->getModel("member");
		$modelCountry           = $this->getModel("country");
		
		
		
        $this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$view                          = &$this->view;
		$orderCart                     = new Zend_Session_Namespace("ordercart");
		$application                   = new Zend_Session_Namespace("erccmapp");
		$commandeid                    = intval($this->_getParam("commandeid", $this->_getParam("orderid",0)));
		if(!isset($orderCart->paiement)) {			
			$this->redirect("public/payments/create/commandeid/".$commandeid);
		} elseif(isset($orderCart->paiement["commandeid"]) && !intval($commandeid)) {
			$commandeid                = $orderCart->paiement["commandeid"];
		}
		if(!isset($orderCart->commande) || !isset($orderCart->commandeid)){
		    $orderCart->commande       = array();
			$orderCart->commandeid     = 0;
		}
		$commandeid                    = intval($this->_getParam("commandeid", $this->_getParam("orderid", $orderCart->commandeid)));
 
		$modelTable                    = $modelPaiement->getTable();
		$dbAdapter                     = $modelTable->getAdapter();
		$prefixName                    = $modelTable->info("namePrefix");	
		$tableName                     = $modelTable->info("name");
		
		$accountid                     = $me->userid;
		$member                        = $this->_member;
		if(!$member ) {
			$accountMember             = $modelMember->fromuser($accountid);
			if( $accountMember) {
				$member                = $modelMember->findRow( $accountMember->memberid,"memberid",null,false);
			}
		}	
		$memberid                      = $member->memberid;
        $view->member                  = $member;
		$view->memberid                = $memberid;
		
		$commandeRow                                     = $invoiceRow = null;
		if(!$commandeRow                                 = $model->findRow($commandeid,"commandeid",null,false)) {
			$orderCart->commande                         = array();
			$orderCart->invoice                          = array();
			$orderCart->invoiceAddress                   = array();
			$orderCart->commandeItems                    = array();
			$orderCart->commandeid                       = 0;
			$orderCart->invoiceid                        = 0;
			$this->setRedirect("La commande concernée semble inexistante","error");
			$this->redirect("public/orders/create");
		}
		if( $commandeRow->memberid != $member->memberid) {
			$this->setRedirect("Commande Invalide !","error");
			$this->redirect("public/members/dashboard");
		}
		$memberid                                        = $commandeRow->memberid;
		$commandePaiements                               = $commandeRow->paiements($commandeid);
		$invoiceRow                                      = $modelInvoice->findRow( $commandeid,"commandeid",null,false);
		if(!$invoiceRow ) {
			$this->setRedirect("La facture de la commande concernée semble inexistante","error");
			$this->redirect("public/orders/create");
		}
		$invoiceid                                       = $invoiceRow->invoiceid;
		$requestParams                                   = $this->_request->getParams();
				
		if(!isset($requestParams["num_command"]) || !isset($requestParams["num_transaction_from_gu"])) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Les références de cette transaction ne sont pas valides. Veuillez procéder au paiement."));
				exit;
			}
			$this->setRedirect("Les références de cette transaction ne sont pas valides. Veuillez procéder au paiement.","error");
			$this->redirect("public/payments/create");
		}
		$paiementTransId               = $trans_id = $orderCart->paiement["transid"];
		$touchpayNumOrder              = (isset($requestParams["num_command"]            ))?strip_tags(substr($requestParams["num_command"],0,25))             : 0;
		$touchpayNumTrans              = (isset($requestParams["num_transaction_from_gu"]))?strip_tags(substr($requestParams["num_transaction_from_gu"],0,25)) : 0;
		$touchPayAmount                = (isset($requestParams["amount"]                 ))?floatval(  substr($requestParams["amount"],0,30))                  : 0;
		$transactionPaiement           = (!empty($touchpayNumOrder))? $modelPaiement->transaction($touchpayNumOrder)  : false;
		$checkCommandeId               = ($transactionPaiement     )? ($commandeid==$transactionPaiement->commandeid) : false;
		if(($paiementTransId != $touchpayNumOrder) || !$transactionPaiement || ($checkCommandeId==false)) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Les références de cette transaction ne sont pas valides. Veuillez procéder au paiement."));
				exit;
			}
			$this->setRedirect("Les références de cette transaction ne sont pas valides. Veuillez procéder au paiement.","error");
			$this->redirect("public/payments/create");
		}
		if( $transactionPaiement->montant > $touchPayAmount ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Le montant que vous avez payé est insuffisant pour valider la commande"));
				exit;
			}
			$this->setRedirect("Le montant que vous avez payé est insuffisant pour valider la commande.","error");
			$this->redirect("public/payments/create");
		}		
		if(!count($commandePaiements) || !$commandeRow ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Commande Invalide !"));
				exit;
			}
			$this->setRedirect("Commande Invalide !","error");
			$this->redirect("public/payments/create");
		}
		
		$paiementid                    = $transactionPaiement->paiementid;
		$commandeid                    = $transactionPaiement->commandeid;
		$fraisTransaction              = floatval($touchPayAmount) - $transactionPaiement->montant;
		$totalPaid                     = $transactionPaiement->montant + $fraisTransaction;
		$totalAPayer                   = $totalPaid;
		$paiementDataUpdated           = array("totalPaid"=>$totalPaid,"totalAPayer"=>$totalAPayer,"frais_transaction"=>$fraisTransaction,
		                                       "statutid"=>2,"validated"=>1,"num_transaction"=>$touchpayNumTrans,"num_commande"=>$touchpayNumOrder,"updatedate"=>time(),"updateduserid"=>26);
		try {
			if( $dbAdapter->update( $prefixName."erccm_vente_commandes_paiements",$paiementDataUpdated , array("paiementid=?"=>$paiementid))) {
		        $dbAdapter->update( $prefixName."erccm_vente_modepaiements"      ,array("processed"=>1), array("numero=\"".$trans_id."\""));
				$dbAdapter->update( $prefixName."erccm_vente_commandes_invoices" ,array("statutid" =>3,"updatedate"=>time(),"updateduserid"=>26)              , array("commandeid=?"=>$commandeid));
				$dbAdapter->update( $prefixName."erccm_vente_commandes"          ,array("validated"=>1,"statutid"=>3,"updatedate"=>time(),"updateduserid"=>26), array("commandeid=?"=>$commandeid));
				$dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"  ,array("status"   =>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
			    
				$commandeProducts       = $modelCommande->products($commandeid);
				if( count(   $commandeProducts) ) {
					foreach( $commandeProducts as  $commandeProduct) {
						     if( isset($commandeProduct["demandeid"]) && intval($commandeProduct["demandeid"])) {
								 $demandeid = intval($commandeProduct["demandeid"]);
								 $dbAdapter->update( $prefixName."reservation_demandes",array("statutid"=>1,"updatedate"=>time(),"updateduserid"=>26), array("demandeid=?"=>$demandeid));
							 }
					}
				}
			    
			} else {
				$errorMessages[]       = sprintf("Le paiement n'a pas été enregistré pour des raisons inconnues. Veuillez signaler à l'administrateur");
			}
		} catch( Exception $e ) {
			    $errorMessages[]       = sprintf("Le paiement n'a pas été enregistré pour des raisons inconnues. Veuillez signaler à l'administrateur");
		}		
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> implode(",", $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $errorMessage){
					 $this->getHelper("Message")->addMessage($errorMessage , "error");
			}
			$this->redirect("public/payments/create");
		}		
		
		if(!count($commandePaiements)) {
			$this->redirect("public/payments/create/commandeid/".$commandeid);
		}
 
		$orderCart->articles           = array();
		$orderCart->registres          = array();
		$orderCart->documents          = array();
		$orderCart->requests           = array();
		$orderCart->commande           = array();
		$orderCart->invoice            = array();
		$orderCart->invoiceAddress     = array();
		$orderCart->commandeItems      = array();
		$orderCart->commandeid         = 0;
		$orderCart->invoiceid          = 0;
		$orderCart->counter            = 0;
		$orderCart->initialised        = true;
		$orderCart->setExpirationSeconds(APPLICATION_ORDERCART_EXPIRATION);
 
		
		$this->setRedirect("Vous avez finalisé avec succès la procédure de votre commande. Nous vous reviendrons dans un délai de 48H maximum après que votre paiement ait été validé.", "success");
		$this->redirect("public/members/dashboard");
	}
	
	public function howtoAction()
	{
 
		$this->view->title            = "Les moyens de paiements que vous pouvez utiliser sur cette plateforme";
		$this->render("fonctionnement");
	}
	
	public function refusedAction()
	{		
	    $modelPaiement                = $model = $this->getModel("commandepaiement");
		$modelTable                   = $model->getTable();
		$dbAdapter                    = $modelTable->getAdapter();
		$prefixName                   = $modelTable->info("namePrefix");	
		$tableName                    = $modelTable->info("name");
		//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter                 =  new Zend_Filter();
		$stringFilter->addFilter(        new Zend_Filter_StringTrim());
		$stringFilter->addFilter(        new Zend_Filter_StripTags());
		
		$trans_id                     = $id_transaction      = $stringFilter->filter($this->_getParam("trans_id", $this->_getParam("transactionid",0 )));
		$typeError                    = $stringFilter->filter($this->_getParam("type",1));
		$transactionPaiement          = $transactionCommande = null;
		$errorMessages                = array();
		if(!empty($trans_id)) {
			$transactionPaiement      = $model->transaction($trans_id,"array");
			$transactionCommande      = (isset($transactionPaiement["commandeid"]) && intval($transactionPaiement["commandeid"]))?$modelCommande->findRow(intval($transactionPaiement["commandeid"]),"commandeid",null,false) : null;
		}
		if( $trans_id && isset($transactionPaiement["commandeid"])) {
			$commandeid               = $transactionPaiement["commandeid"];
			$paiementid               = $transactionPaiement["paiementid"];
			$dbAdapter->update( $prefixName."erccm_vente_commandes_paiements",array("validated"=>0,"statutid"=>1),array("paiementid=?"=>$paiementid));
			$dbAdapter->update( $prefixName."erccm_vente_commandes"          ,array("validated"=>0,"statutid"=>1),array("commandeid=?"=>$commandeid));
			switch( $typeError ) {
				case "1":
				default:
				    $errorMessages[]  = "Votre paiement a été refusé par l'opérateur";
					break;
				case "2"  :
				    $errorMessages[]  = "Paiement Invalide";
					break;
			}
		} else {
			$errorMessages[]          = "Transaction Invalide";
		}
		
		$message                      = sprintf("<h3><u>Erreurs de paiements sur la commande n° %s:</u></h3>\n<br/> ",$transactionCommande->ref);
		if( count(   $errorMessages) ) {
			foreach( $errorMessages as $errorMessage ) {
				     $message        .= $errorMessage." \n<br/> ";
			}
		}
		$this->redirect("public/payments/create");
		$this->view->message         = $message;
		$this->view->data            = $transactionPaiement;
		$this->view->commande        = $transactionCommande;
		$this->view->trans_id        = $trans_id;
		$this->view->commandeid      = (isset($transactionPaiement["commandeid"]))?$transactionPaiement["commandeid"] : 0;
		$this->view->paiementid      = (isset($transactionPaiement["paiementid"]))?$transactionPaiement["paiementid"] : 0;
	}
	
	public function validateAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$view                      = &$this->view;
		$orderCart                 = new Zend_Session_Namespace("ordercart");
		$application               = new Zend_Session_Namespace("erccmapp");
		
		$model                     = $this->getModel("commandepaiement");
		$modelCommande             = $this->getModel("commande");
		$modelInvoice              = $this->getModel("commandefacture");
		$modelMember               = $this->getModel("member");
		$modelCountry              = $this->getModel("country");
		
		$me                        = Sirah_Fabric::getUser();
        $params                    = $this->_request->getParams();
		
		$postData                  = array_merge($params,$this->_request->getPost());
		$postData["cpm_trans_id"]  = (isset( $postData["cpm_trans_id"]))? $postData["cpm_trans_id"] : "";
		$postData["num_from_gu"]   = (!isset($postData["num_from_gu"] ))? $postData["cpm_trans_id"] : "";
		$responseFormat            = (isset( $postData["output"]) && !empty($postData["output"]))?$postData["output"] : "html";
		if(!isset($postData["num_from_gu"]) || empty($postData["num_from_gu"])) {
			if( $this->_request->isXmlHttpRequest()) {
				echo ZendX_JQuery::encodeJson(array("error"=> "Les références de cette transaction ne sont pas valides"));
				exit;
			}
			echo "Les références de cette transaction ne sont pas valides";
			die();
		}
		if(!isset($postData["cpm_trans_id"]) || empty($postData["cpm_trans_id"])){
			$postData["cpm_trans_id"]  = $postData["num_from_gu"];
		}
		$modelTable                = $model->getTable();
		$dbAdapter                 = $modelTable->getAdapter();
		$prefixName                = $modelTable->info("namePrefix");	
		$tableName                 = $modelTable->info("name");
		//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter              =  new Zend_Filter();
		$stringFilter->addFilter(     new Zend_Filter_StringTrim());
		$stringFilter->addFilter(     new Zend_Filter_StripTags());
		
		$trans_id                  = $id_transaction      = $stringFilter->filter($postData["cpm_trans_id"]);
		$transactionPaiement       = $transactionCommande = null;
		$errorMessages             = array();
		if(!empty($trans_id)) {
			$transactionPaiement   = $model->transaction($trans_id);
			$transactionCommande   = (isset($transactionPaiement->commandeid) && intval($transactionPaiement->commandeid))?$modelCommande->findRow(intval($transactionPaiement->commandeid),"commandeid",null,false) : null;
		}
		if( $transactionPaiement && $transactionCommande) {
			if( $transactionPaiement->validated== 1) {
				if( $this->_request->isXmlHttpRequest() || $responseFormat=="json") {
					echo ZendX_JQuery::encodeJson(array("success"=> "Cette transaction a déjà été validée"));
					exit;
				}
				echo "Cette transaction a déjà été validée";
				die();
			}
			$commandeid            = $transactionPaiement->commandeid;
			$paiementid            = $transactionPaiement->paiementid;
			$apiKey                = (defined("PAYMENT_GATEWAY_APIKEY" ))?PAYMENT_GATEWAY_APIKEY  : "1726210322638da41d8edad2.11557689";
		    $site_id               = (defined("PAYMENT_GATEWAY_SITE_ID"))?PAYMENT_GATEWAY_SITE_ID : "448705";
			/*
			try {
				$CinetPay          = new CinetPay($site_id, $apiKey,"PROD","V1");
				$CinetPay->setTransId($trans_id)->getPayStatus();
				$cpm_amount        = $paymentAmount = $CinetPay->_cpm_amount;
				$cpm_phone_prefixe = $CinetPay->_cpm_phone_prefixe;
                $cel_phone_num     = $CinetPay->_cel_phone_num;
				$created_at        = $CinetPay->_created_at;
				$updated_at        = $CinetPay->_updated_at;
				$cpm_result        = $CinetPay->_cpm_result;
				$cpm_trans_status  = $CinetPay->_cpm_trans_status;
				if( $paymentAmount== $transactionPaiement->montant) {
					//Quand le paiement est fait avec succès
					if( $cpm_result == '00' ) {
						//On met à jour le statut du paiement
						$paiementUpdated = array("statutid"=>2,"validated"=>1,"updatedate"=>time(),"updateduserid"=>26);
						if( $dbAdapter->update($tableName,$paiementUpdated,array("paiementid=?"=>$paiementid))) {
							$dbAdapter->update($prefixName."erccm_vente_modepaiements"     ,array("processed"=>1),array("numero=\"".$trans_id."\""));
						    $dbAdapter->update($prefixName."erccm_vente_commandes_invoices",array("statutid"=>3,"updatedate"=>time(),"updateduserid"=>26) ,array("commandeid=\"".$commandeid."\""));
							$dbAdapter->update($prefixName."erccm_vente_commandes"         ,array("validated"=>1,"statutid"=>3,"updatedate"=>time(),"updateduserid"=>26),array("commandeid=\"".$commandeid."\""));
						    $dbAdapter->update($prefixName."erccm_vente_modepaiements_web" ,array("status"=>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
						} else {
							throw new Exception("La transaction avait été certainement déjà validée");
						}
					} else {
						$dbAdapter->update($tableName,array("statutid"=>3,"validated"=>0),array("paiementid=?"=>$paiementid));
						throw new Exception("La transaction a été invalidée ");
					}
				} else {
					throw new Exception("Montant de la transaction invalide");
				}
			} catch(Exception $e ) {
				$errorMessages[]   = sprintf("Transaction n° %s invalide : %s", $trans_id, $e->getMessage());
			}	*/	
            $paiementUpdated = array("statutid"=>2,"validated"=>1,"updatedate"=>time(),"updateduserid"=>26);
			if( $dbAdapter->update($tableName,$paiementUpdated,array("paiementid=?"=>$paiementid))) {
				$dbAdapter->update($prefixName."erccm_vente_modepaiements"     ,array("processed"=>1),array("numero=\"".$trans_id."\""));
				$dbAdapter->update($prefixName."erccm_vente_commandes_invoices",array("statutid"=>3,"updatedate"=>time(),"updateduserid"=>26) ,array("commandeid=\"".$commandeid."\""));
				$dbAdapter->update($prefixName."erccm_vente_commandes"         ,array("validated"=>1,"statutid"=>3,"updatedate"=>time(),"updateduserid"=>26),array("commandeid=\"".$commandeid."\""));
				$dbAdapter->update($prefixName."erccm_vente_modepaiements_web" ,array("status"=>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
			} else {
				throw new Exception("La transaction avait été certainement déjà validée");
			}			
		} else {
			if( $this->_request->isXmlHttpRequest() || $responseFormat=="json") {
				echo ZendX_JQuery::encodeJson(array("error"=> "Les références de cette transaction ne sont pas valides"));
				exit;
			}
			echo "Les références de cette transaction ne sont pas valides";
			die();
		}
		if( count($errorMessages) ) {
			if( $this->_request->isXmlHttpRequest() || $responseFormat=="json") {
				echo ZendX_JQuery::encodeJson(array("error" => implode(" ; ",$errorMessages)));
				exit;
			}
			echo "<h3> Des erreurs ont été relevées: </h3>";
			foreach( $errorMessages as $errorMessage){
					 echo $errorMessage."\n<br/>";
			}
			 exit;
		} else {
			if( $this->_request->isXmlHttpRequest() || $responseFormat=="json") {
				echo ZendX_JQuery::encodeJson(array("success"=> "Opération effectuée avec succès"));
				exit;
			}
			echo "Opération effectuée avec succès";
			die();
		}
	}
	
	public function unvalidateAction()
	{
	}
	
	public function infosAction()
	{
	}
 
}

