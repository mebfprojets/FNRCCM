<?php
/** 
 * This class is free for use but must remain open source
 * 
 * This class can read UL html code in to PHP array
 * @author 	 Ovidiu Mihalas - Lesei contact@miledino.ro
 */

Class Ul
{
    private $level = -1;
    private $ul_array = array();
    private $last_value;
    private $parent;
    private $parents = array();
    private $li_array = array();
    private $counter = 0;
    private $tree = array();
    private $id = 1;

    function __construct($list)
    {
        /**
        * Initialization of XML Parser
        *
        * @var Resource $xml_parser XML Parser
        */
        $xml_parser = xml_parser_create();
        /**
        * The method that will take care of the XML data
        */
        xml_set_character_data_handler($xml_parser, "character_data");
        /**
        * The method that will take care of the XML data elements
        */
        xml_set_element_handler($xml_parser, "start_element", "end_element");
        /**
        * We set the parse to be used inside an object
        */
        xml_set_object ( $xml_parser, $this );

        xml_parse($xml_parser, $list);

        xml_parser_free($xml_parser);
    }
    private function start_element($parser, $name, $attrs)
    {
        if($name=="UL")
        {
            $this->level++;
            if($this->level > 0)
            {
                $this->parent = $this->last_value;
                if(strlen($this->parent))
                {
                    $this->ul_array[$this->level][]=$this->parent;
                }
            }
            $this->counter++;
        }
    }
    private function end_element($parser, $name)
    {
        if($name=="UL")
        {
            if(strlen($this->parent))
            {
                foreach($this->li_array[$this->level] as $key=>$li)
                {
                    if(is_array($li))
                    {
                        foreach($li as $key1=>$kid)
                        {
                            $this->tree[$this->counter][$this->level][] = $kid;
                        }
                        unset($this->li_array[$this->level][$key]);
                    }
                }
            }
            else
            {
                foreach($this->li_array[$this->level] as $key=>$li)
                {
                    if(is_array($li))
                    {
                        foreach($li as $key1=>$kid)
                        {
                            $this->tree[$this->counter][$this->level][] = $kid;
                        }
                        unset($this->li_array[$this->level][$key]);
                    }
                }                
            }

            $this->level--;
            $this->counter++;
            $this->parent="";
        }
    }
    private function character_data($parser = NULL, $data = NULL)
    {
        if(strlen(trim($data)))
        {
            $data = trim($data);
            $data = $data."-|".$this->id++."|";
            $this->li_array[$this->level][$this->counter][]=$data;
            $this->last_value = $data;
        }
    }
    public function levels()
    {
        $parents = array();

        foreach($this->tree as $key=>&$lis)
        {
            if(is_array($lis))
            {
                foreach($lis as $key_1=>$lis_item)
                {
                    $parent="";
                    if(isset($this->ul_array[$key_1]))
                    {
                        $parent = array_shift($this->ul_array[$key_1]);
                    }
                    if(strlen($parent))
                    {
                        $lis[$key_1]['parent'] = $parent;
                        $parents[] = $parent;
                    }
                }
            }
        }

        //print_r($this->ul_array);
        //print_r($this->li_array);
        //print_r($this->counter);        
        //print_r($this->tree);
		return $this->tree;
    }
}
?>