<?php

class Sirah_Log_Writer_Db extends Zend_Log_Writer_Abstract
{

private $_db           = null;

private $_table        = "system_journal";

private $_tableColumns = array("msg"           => "message",
                               "niveau"        => "priority",
                               "priorityName"  => "priorityName",
                               "timestamp"     => "timestamp",
                               "pid"           => "pid",
                               "user"          => "user");


function __construct($db=null,$table="system_journal",$columns=null)
{
          if($db===null || !($db instanceof Zend_Db_Adapter_Abstract))   
                $db=Zend_Db_Table::getDefaultAdapter();
                
          $this->_db     = $db;
          $this->_table  = $table;
          if(null!==$columns)
              $this->_tableColumns=$columns;

}

static public function factory($config)
{
    return null;
 }


protected function _write($event)
{

    if ($this->_db === null)             
                 throw  new Sira_Exception(" Votre adaptateur de traitement de la base de données est invalide ");

        $store=array();
        if ($this->_tableColumns === null  || empty($this->_tableColumns))
            $store = $event;
        else 
        {
          foreach ($this->_tableColumns as $columnNom => $eventKey) 
          {
              if(!isset($event[$eventKey]))
              {                  
                    $store[$columnNom]="";
                    continue;
                }
                $store[$columnNom] = $event[$eventKey];
            }
        }

        $this->_db->insert($this->_table, $store);

  }

function setFormatter(Zend_Log_Formatter_Interface $formatter)
{
   throw new Sirah_Exception(" Ce redacteur n'inclut pas la prise en charge de formatters ");
}

public function shutdown()
{
     $this->_db=null;
  }







}
