<?php

class Twitter_Bootstrap_Tables_Table_Cell extends Zend_View_Helper_HtmlElement
{
    /**
     * The cell value
     *
     * @var string
     */
    protected $_value;

    /**
     * The cell attributes map
     *
     * @var array
     */
    protected $_attributes;

    /**
     * @var boolean
     */
    protected $_isHeader;
   
    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->_attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @return boolean
     */
    public function isHeader()
    {
        $this->_isHeader = true;
    }

    /**
     * Generates a new table cell
     *
     * @param string $value
     * @param array $attributes
     *
     * @return Twitter_Bootstrap_Tables_Table_Cell
     */
    public function cell($value, array $attributes = null)
    {
        $cell = new static();
        $cell->setView($this->view);
        $cell->setValue($value);

        if (null !== $attributes) {
            $cell->setAttributes($attributes);
        }
        return $cell;
    }
    
    /**
     * @return mixed
     */
    protected function _getHtmlTag()
    {
    	$value          = $this->getValue();
    	$htmlCheckRegex = "/^(?:<\s*(.+)\s*\/>)?(?:<\s*(.+)\s*>\s*(.+)\s*<\s*\/(.+)\s*>)?/i";
    	if(preg_match($htmlCheckRegex,$value,$matches)){
    		return $matches;
    	}
    	return false;
    }
    
    
    /**
     * Recupère les attributs à partir d'un chaine de caractère
     *
     * @param  string $attributeString
     * @return string
     */
    public function getAttributesFromString($attributeString)
    {
    	$attributes        = array();
    	$attributesFromStr = explode(" ",$attributeString);
    	if(!empty($attributesFromStr)){
    		foreach($attributesFromStr as $attributeElement){
    			if(preg_match("/\s*([a-zA-Z0-9#]+)\s*=\s*(.+)\s*/i" , $attributeElement , $attributeElementMatches)){
    				$attributeValue  = (isset($attributeElementMatches[2]) && !empty($attributeElementMatches[2])) ? trim($attributeElementMatches[2]) : "";
    				if(isset($attributeElementMatches[1]) && !empty($attributeElementMatches[1])){
    					$attributes[$attributeElementMatches[1]] = preg_replace("/[^a-zA-Z0-9#'\/\[\]\s]/i","",$attributeValue);
    				} else {
    					$attributes[] = preg_replace("/[^a-zA-Z0-9#'\/\[\]\s]/i","",$attributeValue);
    				}
    			}
    		}
    	}
    	return $attributes;
    }

    /**
     * Renders the table cell
     *
     * @return string
     */
    public function __toString()
    {
    	if(false!==($htmlTagMatches = $this->_getHtmlTag())){
    		//input or image tag
    		if(isset($htmlTagMatches[1]) && !empty($htmlTagMatches[1])){
    			$TagNameRegex = "/\s?(.+)\s+(.+)/i";
    			if(preg_match($TagNameRegex , $htmlTagMatches[1],$matches)){
    				$tag                 = (isset($matches[1]) && !empty($matches[1])) ? $matches[1] : null;
    				$attributes          = array();
    				if(null!==$tag){
    					if(isset($matches[2]) && !empty($matches[2])){
    						$attributeString   = trim($matches[2]);
    						$attributes        = $this->getAttributesFromString($attributeString);
    					} 
    					$tableTag    = 't' . ($this->_isHeader ? 'h' : 'd');
    					$tableValue  = sprintf("<%s %s />",$tag,$this->_htmlAttribs($attributes));
    					return sprintf("<%s%s>%s</%s>", $tableTag, $this->_htmlAttribs($this->getAttributes()),$tableValue, $tableTag);
    				}
    			}
    		} elseif(isset($htmlTagMatches[2]) && !empty($htmlTagMatches[2])) {
    			$TagNameRegex = "/\s?(.+)\s+(.+)/i";
    			if(preg_match($TagNameRegex,$htmlTagMatches[2],$matches)){
    				$tag                 = (isset($matches[1]) && !empty($matches[1])) ? $matches[1] : null;
    				$tagValue            = (isset($htmlTagMatches[3]) && !empty($htmlTagMatches[3])) ? $htmlTagMatches[3] : null;
    				$attributes          = array();
    				if(null!==$tag){
    					if(isset($matches[2]) && !empty($matches[2])){
    						$attributeString   = trim($matches[2]); 
    						$attributes        = $this->getAttributesFromString($attributeString);
    					}
    					$tableTag    = 't' . ($this->_isHeader ? 'h' : 'd');
    					$tableValue  = sprintf("<%s %s>%s</%s>", $tag , $this->_htmlAttribs($attributes) , $this->view->escape($tagValue),$tag);
    					return sprintf("<%s%s>%s</%s>", $tableTag, $this->_htmlAttribs($this->getAttributes()),$tableValue, $tableTag);
    				}
    			}    			
    		}
    	}
        $tag = 't' . ($this->_isHeader ? 'h' : 'd');
        return sprintf('<%s%s>%s</%s>', $tag, $this->_htmlAttribs($this->getAttributes()), $this->view->escape($this->getValue()), $tag);
    }
}
