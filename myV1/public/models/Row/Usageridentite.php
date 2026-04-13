<?php

class Model_Usageridentite extends Sirah_Model_Default
{

    protected $_error       = null;
	
	
	public function setError($error)
	{
		$this->_error       = $error;
		return $this;
	}
	
	public function getError()
	{
		return $this->_error;
	}
	
	
	public function getRow($data = array())
	{
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectIdentite   = $dbAdapter->select()->from(array("I"=> $tablePrefix."reservation_demandeurs_identite"))
												->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid", array("typePiece"  =>"T.libelle"));
	    if( isset($data["numidentite"]) && !empty($data["numidentite"])) {
			$selectIdentite->where("I.numero=?",strip_tags($data["numidentite"]));
		} elseif( isset($data["numero"]) && !empty($data["numero"])) {
			$selectIdentite->where("I.numero=?",strip_tags($data["numero"]));
		} else {
			return false;
		}
		if( isset($data["date_etablissement"]) && !empty($data["date_etablissement"])) {
			$selectIdentite->where("I.date_etablissement=?",strip_tags($data["date_etablissement"]));
		} else {
			return false;
		}
		if( isset($data["lieu_etablissement"]) && !empty($data["lieu_etablissement"])) {
			$selectIdentite->where("I.lieu_etablissement=?",strip_tags($data["lieu_etablissement"]));
		}else {
			return false;
		}
		if( isset($data["organisme_etablissement"]) && !empty($data["organisme_etablissement"])) {
			$selectIdentite->where("I.organisme_etablissement=?",$data["organisme_etablissement"]);
		}
		return $dbAdapter->fetchRow($selectIdentite, array(), Zend_Db::FETCH_OBJ);
	} 
 
}

