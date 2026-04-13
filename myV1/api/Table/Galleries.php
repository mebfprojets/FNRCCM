<?php

class Table_Galleries extends Sirah_Model_Table
{
    protected $_name            = "erccm_crm_content_gallery";

    protected $_primary         = array("galleryid");
     
    protected $_dependentTables = array("Table_Galleryphotos", "Table_Galleryvideos","Table_Articles");
	
	protected $_rowClass        = "Model_Gallery";
}

