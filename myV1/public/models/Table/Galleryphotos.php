<?php

class Table_Galleryphotos extends Sirah_Model_Table
{

     protected $_name         = "erccm_crm_content_gallery_photos";

     protected $_primary      = array("photoid");
     
     protected $_referenceMap = array( "Gallery"=> array(
     		                                              "columns"       => array("galleryid") ,
     		                                              "refTableClass" => "Table_Galleries" ,
     		                                              "refColumns"    => array("galleryid")
                                                    )   	 	 
     		                    );
         
  }

