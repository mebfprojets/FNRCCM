<?php

class Table_Activitephotos extends Sirah_Model_Table
{

     protected $_name         = "sdr_projects_gallery_photos";

     protected $_primary      = array("photoid");
     
     protected $_referenceMap = array( "Gallery"=> array(
     		                                              "columns"       => array("galleryid") ,
     		                                              "refTableClass" => "Table_Activitegalleries" ,
     		                                              "refColumns"    => array("galleryid")
                                                    )   	 	 
     		                    );
         
  }

