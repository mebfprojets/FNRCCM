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
defined("PAYMENT_GATEWAY_API_SHIPPING_COST")
    || define("PAYMENT_GATEWAY_API_SHIPPING_COST",2);	
defined("PAYMENT_GATEWAY_API_ENDPOINT")
    || define("PAYMENT_GATEWAY_API_ENDPOINT","https://app.ligdicash.com");	
defined("PAYMENT_GATEWAY_API_TOKEN")
    || define("PAYMENT_GATEWAY_API_TOKEN","eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF9hcHAiOiIyNDgyNyIsImlkX2Fib25uZSI6NzU5MzkwLCJkYXRlY3JlYXRpb25fYXBwIjoiMjAyNS0wNi0wMyAxMjowMDoxMiJ9.fGWi-mhBNNCgPpkrd8XZhaU1YIbxXMj9XVR1lMZto5o");
 
defined("APPLICATION_ORDERCART_EXPIRATION")
    || define("APPLICATION_ORDERCART_EXPIRATION",86400);		

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
				$dbAdapter->update( $prefixName."erccm_vente_commandes_ligne"    ,array("validated"=>1,"updatedate"=>time(),"updateduserid"=>26)              , array("commandeid=?"=>$commandeid));
				$dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"  ,array("status"   =>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
			    
				$commandeProducts       = $modelCommande->products($commandeid);
				if( count(   $commandeProducts) ) {
					foreach( $commandeProducts as  $commandeProduct) {
						     if( isset($commandeProduct["demandeid"]) && intval($commandeProduct["demandeid"])) {
								 $demandeid = intval($commandeProduct["demandeid"]);
								 $dbAdapter->update( $prefixName."reservation_demandes"       ,array("statutid" =>1,"updatedate"=>time(),"updateduserid"=>26), array("demandeid=?"=>$demandeid));
								 $dbAdapter->update( $prefixName."erccm_vente_commandes_ligne",array("validated"=>1,"updatedate"=>time(),"updateduserid"=>26), array("demandeid=?"=>$demandeid));
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
		$this->view->commandeid      = (isset($transactionPaiement["commandeid"]))? $transactionPaiement["commandeid"] : 0;
		$this->view->paiementid      = (isset($transactionPaiement["paiementid"]))? $transactionPaiement["paiementid"] : 0;
	}
	
	protected function __getUserIpAddr() {
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){
			// IP from shared internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			// IP passed from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else{
			// IP from remote address
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
	
	public function validateAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);	 
		$this->_helper->layout->disableLayout(true);
		
		$alowed_ips       = array("15.235.84.74","54.36.123.199");
		$guestIp          = "";
		$orderCart        = new Zend_Session_Namespace("ordercart");
		$application      = new Zend_Session_Namespace("erccmapp");
		
		$model            = $modelCommande = $this->getModel("commande");
		$modelTable       = $model->getTable();
		$modelPaiement    = $this->getModel("commandepaiement");
		$modelInvoice     = $this->getModel("commandefacture");
 
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $prefixName = $modelTable->info("namePrefix");
		
		$commande         = $invoice    = $commandeRow  = null;
		$commandeid       = $invoiceid  = $transid      = $mOrder_id = $transaction_id = $transactionid = 0;
		 
		$me               = Sirah_Fabric::getUser();
        $requestParams    = $this->_request->getPost();
		$payload          = file_get_contents('php://input');
		$event            = json_decode($payload);
		
		$mToken           = (isset($requestParams["token"]) && !empty($requestParams["token"]      ))? $requestParams["token"]       : "";
		 
		if( empty($mToken) && $event) {
			$mToken       = $event->token;
			$date         = $event->date;
			$description  = $event->description;
			$requestParams= json_decode($payload, true);
		}
		$description      = (isset($requestParams["description"]) && !empty($requestParams["description"]))? $requestParams["description"] : "";
		$customerInfos    = (isset($requestParams["customer_details"]))?$requestParams["customer_details"] : array("lastname"=>"inconnu","firstname"=>"inconnu","email"=>"inconnu");
		$lastname         = (isset($customerInfos["lastname"])    && !empty($customerInfos["lastname"]   ))? $customerInfos["lastname"]    : "-";
		$firstname        = (isset($customerInfos["firstname"])   && !empty($customerInfos["firstname"]  ))? $customerInfos["firstname"]   : "-";
		$email            = (isset($customerInfos["email"])       && !empty($customerInfos["email"]      ))? $customerInfos["email"]       : "-";
		$date             = (isset($requestParams["date"])        && !empty($requestParams["date"]       ))? $requestParams["date"]        : date("d/m/Y h:i:s");
		
		//Si aucun Token valide n'a été transmis, on bloque
		if( empty($mToken)) {
			$errorMessage = "Requête Invalide : Paramètres manquants!";
			 
			$mToken           = time();
			$date             = date("d m Y");
			echo $errorMessage;
			exit; 
		}
		$isReservationRequest = $isValidPaiement = false;
		$checkPaymentData     = $apiResponseData = $requestParams;
		$description          = (isset($checkPaymentData["description"]))? strip_tags($checkPaymentData["description"]) : "";
		if(!isset($checkPaymentData["custom_data"]) || empty($checkPaymentData["custom_data"])) {
			$errorMessage     = "Votre paiement semble invalide et n'est pas associé à la commande de votre panier"; 
			$date             = date("d m Y");
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"   =>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>"INVALID_CUSTOMDATA","date"=>$date,"message"=>$errorMessage." ".$description));
			echo $errorMessage;
			exit;
		}
		$order_data           = (isset($checkPaymentData["custom_data"][0]))?$checkPaymentData["custom_data"][0] : array();		
		if( isset($order_data["valueof_customdata"]) && !empty($order_data["valueof_customdata"]) && !intval($commandeid)) {			
			$commandeid       = $order_id = $mOrder_id = intval($order_data["valueof_customdata"]);
			$commandeRow      = $commande = ($commandeid)?$modelCommande->findRow($commandeid,"commandeid",null,false) : null;
		}
		$transaction_data     = (isset($checkPaymentData["custom_data"][1]))?$checkPaymentData["transaction_data"][1] : array();		
		if( isset($transaction_data["valueof_customdata"]) && !empty($transaction_data["valueof_customdata"]) ) {			
			$transaction_id   = $transactionid = $checkPaymentData["transaction_id"] = intval($transaction_data["valueof_customdata"]);
		}
		if(!intval($commandeid) || !$commandeRow){
			$errorMessage     = "Votre paiement semble invalide et n'est pas associé à la commande de votre panier"; 
			$date             = date("d m Y");
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"  =>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>"INVALID_CUSTOMDATA","date"=>$date,"message"=>$errorMessage." ".$description));
			echo $errorMessage;
			exit;
		}
		$operatorName         = (isset($checkPaymentData["operator_name"]) && !empty($checkPaymentData["operator_name"]))?$checkPaymentData["operator_name"] : "LIGDICASH";
		$errorMessage         = "ERREUR : Votre paiement a été refusé par {$operatorName}. Assurez-vous de disposer des ressources financières dans votre compte de paiement. Veuillez contacter l'opérateur ou l'administrateur.";
		
		if(!isset($checkPaymentData["response_code"]) || empty($checkPaymentData["response_code"]) || ($checkPaymentData["response_code"]=="01")) {		    
			$date             = $checkPaymentData["date"];
			$description      = $checkPaymentData["response_text"];
			$errorMessage    .= " - Motif : ".$description;
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"=>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>$checkPaymentData["status"],"date"=>$date,"message"=>$errorMessage));
			echo $errorMessage;
			exit;			
		}
		if( isset($checkPaymentData["status"]) && ($checkPaymentData["status"]!="completed")) {			 
			$errorMessage = "ERREUR : Votre paiement est en attente de validation par {$operatorName}. Votre commande sera validée après un traitement favorable de l'opérateur";
			 
			$date         = $checkPaymentData["date"];
			$description  = $checkPaymentData["response_text"];
			$errorMessage.= " - Motif : ".$description;
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"=>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>$checkPaymentData["status"],"date"=>$date,"message"=>$errorMessage));
			echo $errorMessage;
			exit;
		} 
		
		if( $commandeRow->validated ) {
			echo "Cette facture semble avoir été déjà validée";
			exit;
		}
		$commandeRow->validated  = 1;
		$commandeRow->updatedate = time();
		$invoiceRow              = ($comandeid )? $modelInvoice->findRow($commandeid,"commandeid",null,false) : null;
		$commandePaiements       = ($commandeid)? $modelCommande->paiements($commandeid) : array();
		
		if(!count($commandePaiements) || !$commandeRow ) {
			$date                = $checkPaymentData["date"];
			$description         = $checkPaymentData["response_text"];
			$errorMessage       .= " - Motif : ".$description;
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"=>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>"INVALID_COMMAND","date"=>$date,"message"=>$errorMessage));
			echo $errorMessage;
			exit;
		}
		$commandeRow->save();
		if(!empty($commandePaiements[0]["transid"])) {
			$transid          = $paiementTransId = $trans_id = $commandePaiements[0]["transid"];
		} else {
			$transid          = $paiementTransId = $trans_id = $modelPaiement->transid(5,sprintf("%05d",$commandeid));
		}
		$paiementid           = $commandePaiements[0]["paiementid"];
		$modePaiementId       = $commandePaiements[0]["modepaiementid"];
		$transaction_id       = (isset($checkPaymentData["transaction_id"]))? $checkPaymentData["transaction_id"] : $transid;
		$totalPaid            = $commandeRow->valeur_ht;
		$fraisTransaction     = ceil(($totalPaid*2)/100);
		
		$totalAPayer          = $totalPaid+floatval($fraisTransaction);
		
		$paiementDataUpdated  = array( "totalPaid"=>$totalPaid,"totalAPayer"=>$totalAPayer,"frais_transaction"=>$fraisTransaction,
		                               "statutid" =>2,"validated"=>1,"num_transaction"=>$transaction_id,"num_commande"=>$transaction_id,"updatedate"=>time(),"updateduserid"=>26);
		try {
			if( $dbAdapter->update( $prefixName."erccm_vente_commandes_paiements" ,$paiementDataUpdated , array("paiementid=?"=>$paiementid))) {
		        $dbAdapter->update( $prefixName."erccm_vente_modepaiements"       ,array("processed"=>1), array("numero=\"".$trans_id."\""));
				$dbAdapter->update( $prefixName."erccm_vente_commandes_invoices"  ,array("statutid" =>3,"updatedate"=>time(),"updateduserid"=>26)              , array("commandeid=?"=>$commandeid));
				$dbAdapter->update( $prefixName."erccm_vente_commandes"           ,array("validated"=>1,"statutid"=>3,"updatedate"=>time(),"updateduserid"=>26), array("commandeid=?"=>$commandeid));
				$dbAdapter->update( $prefixName."erccm_vente_modepaiements_web"   ,array("status"   =>"VALIDATED"),array("transactionid=\"".$trans_id."\""));
			    $commandeProducts       = $modelCommande->products($commandeid);
				if( count(   $commandeProducts) ) {
					foreach( $commandeProducts as  $commandeProduct) {
						     if( isset($commandeProduct["demandeid"]) && intval($commandeProduct["demandeid"])) {
								 $isReservationRequest = true;
								 $demandeid            = intval($commandeProduct["demandeid"]);
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
			$errorMessage              = "";
			 
			foreach( $errorMessages as $message) {
					 $errorMessage.="ERREUR : ".$message ;
			}
			$date         = $checkPaymentData["date"];
			$description  = $checkPaymentData["response_text"];
			$errorMessage.= " - Motif : ".$description;
			$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"=>$mToken));
			$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>0,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>0,"token"=>$mToken,"status"=>"INVALID_COMMAND","date"=>$date,"message"=>$errorMessage));
			echo $errorMessage;
			exit;
		}		
 	
        $successMessage                = "Vous avez finalisé avec succès la commande. Nous vous reviendrons dans un délai de 48H maximum après que votre paiement ait été validé.";
        if( $isReservationRequest ) {
			$successMessage            = "Votre paiement a été validé avec succès. Votre nom commercial ou dénomination sociale est reservé sous réserve de confirmation par l'acteur compétent dans un délai de 48H maximum";
		}
		 	
        $this->_helper->viewRenderer->setNoRender(true);		
		echo $successMessage;
 
		$date           = $checkPaymentData["date"];
		$description    = $checkPaymentData["response_text"];
		$successMessage.= " - Message Reçu : ".$description;
		$dbAdapter->delete( $tablePrefix."reservation_payments_logs", array("token=?"=>$mToken));
		$dbAdapter->insert( $tablePrefix."reservation_payments_logs", array("firstname"=>$firstname,"lastname"=>$lastname,"email"=>$email,"finalized"=>1,"finalize_date"=>"-","ipaddress"=>$guestIp,"commandeid"=>$commandeid,"validated"=>1,"token"=>$mToken,"status"=>"COMPLETED","date"=>$date,"message"=>$successMessage));
		echo $errorMessage;
		exit;
		
		  
	}
	
	public function unvalidateAction()
	{
	}
	
	public function infosAction()
	{
	}
 
}

