<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Cette classe représente une aide de vue
 *
 * qui permet de créer une barre d'outils ou de tache
 *
 * générés par l'application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 * 
 * $pattern = '/\.(fa-(?:\w+(?:-)?)+):before\s+{\s*content:\s*"(.+)";\s+}/';
 * $subject =  file_get_contents('css/font-awesome.min.css');
 * preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER);
 */

class Sirah_View_Helper_Supina_SupinaTools extends Sirah_View_Helper_Toolsbar_Tools
{
	protected $_dropdownitems = array();
	
	/**
	 * @var array
	 */
	public static $icons = array("glass","music","search","envelope-o","heart","star","star-o","user","film","th-large","th","th-list","check",
			                     "remove","close","times","search-plus","search-minus","power-off","signal","gear","cog","trash-o","home","file-o",
			                     "clock-o","road","download","arrow-circle-o-down","arrow-circle-o-up","inbox","play-circle-o","rotate-right","repeat",
			                     "refresh","list-alt","lock","flag","headphones","volume-off","volume-down","volume-up","qrcode","barcode","tag","tags","book",
			                     "bookmark","print","camera","font","bold","italic","text-height","text-width","align-left","align-center","align-right",
			                     "align-justify","list","dedent","outdent","indent","video-camera","photo","image","picture-o","pencil","map-marker",
			                     "adjust","tint","edit","pencil-square-o","share-square-o","check-square-o","arrows","step-backward","fast-backward",
			                     "backward","play","pause","stop","forward","fast-forward","step-forward","eject","chevron-left","chevron-right",
			                     "plus-circle","minus-circle","times-circle","check-circle","question-circle","info-circle","crosshairs",
			                     "times-circle-o","check-circle-o","ban","arrow-left","arrow-right","arrow-up","arrow-down","mail-forward","share",
			                     "expand","compress","plus","minus","asterisk","exclamation-circle","gift","leaf","fire","eye","eye-slash","warning",
			                     "exclamation-triangle","plane","calendar","random","comment","magnet","chevron-up","chevron-down","retweet",
			                     "shopping-cart","folder","folder-open","arrows-v","arrows-h","bar-chart-o","bar-chart","twitter-square","facebook-square",
			                     "camera-retro","key","gears","cogs","comments","thumbs-o-up","thumbs-o-down","star-half","heart-o","sign-out","linkedin-square",
			                     "thumb-tack","external-link","sign-in","trophy","github-square","upload","lemon-o","phone","square-o","bookmark-o","phone-square",
			                     "twitter","facebook-f","facebook","github","unlock","credit-card","rss","hdd-o","bullhorn","bell","certificate","hand-o-right",
			                     "hand-o-left","hand-o-up","hand-o-down","arrow-circle-left","arrow-circle-right","arrow-circle-up","arrow-circle-down","globe",
			                     "wrench","tasks","filter","briefcase","arrows-alt","group","users","chain","link","cloud","flask","cut","scissors","copy","files-o",
			                     "paperclip","save","floppy-o","square","navicon","reorder","bars","list-ul","list-ol","strikethrough","underline","table","magic",
			                     "truck","pinterest","pinterest-square","google-plus-square","google-plus","money","caret-down","caret-up","caret-left","caret-right",
			                     "columns","unsorted","sort","sort-down","sort-desc","sort-up","sort-asc","envelope","linkedin","rotate-left","undo","legal","gavel",
			                     "dashboard","tachometer","comment-o","comments-o","flash","bolt","sitemap","umbrella","paste","clipboard","lightbulb-o","exchange",
			                     "cloud-download","cloud-upload","user-md","stethoscope","suitcase","bell-o","coffee","cutlery","file-text-o","building-o","hospital-o",
			                     "ambulance","medkit","fighter-jet","beer","h-square","plus-square","angle-double-left","angle-double-right","angle-double-up",
			                     "angle-double-down","angle-left","angle-right","angle-up","angle-down","desktop","laptop","tablet","mobile-phone","mobile","circle-o",
			                     "quote-left","quote-right","spinner","circle","mail-reply","reply","github-alt","folder-o","folder-open-o","smile-o","frown-o","meh-o",
			                     "gamepad","keyboard-o","flag-o","flag-checkered","terminal","code","mail-reply-all","reply-all","star-half-empty","star-half-full",
			                     "star-half-o","location-arrow","crop","code-fork","unlink","chain-broken","question","info","exclamation","superscript","subscript",
			                     "eraser","puzzle-piece","microphone","microphone-slash","shield","calendar-o","fire-extinguisher","rocket","maxcdn","chevron-circle-left",
			                     "chevron-circle-right","chevron-circle-up","chevron-circle-down","html5","css3","anchor","unlock-alt","bullseye","ellipsis-h","ellipsis-v",
			                     "rss-square","play-circle","ticket","minus-square","minus-square-o","level-up","level-down","check-square","pencil-square","external-link-square",
			                     "share-square","compass","toggle-down","caret-square-o-down","toggle-up","caret-square-o-up","toggle-right","caret-square-o-right","euro","eur",
			                     "gbp","dollar","usd","rupee","inr","cny","rmb","yen","jpy","ruble","rouble","rub","won","krw","bitcoin","btc","file","file-text","sort-alpha-asc",
			                     "sort-alpha-desc","sort-amount-asc","sort-amount-desc","sort-numeric-asc","sort-numeric-desc","thumbs-up","thumbs-down","youtube-square",
			                     "youtube","xing","xing-square","youtube-play","dropbox","stack-overflow","instagram","flickr","adn","bitbucket","bitbucket-square","tumblr",
			                     "tumblr-square","long-arrow-down","long-arrow-up","long-arrow-left","long-arrow-right","apple","windows","android","linux","dribbble","skype",
			                     "foursquare","trello","female","male","gittip","gratipay","sun-o","moon-o","archive","bug","vk","weibo","renren","pagelines","stack-exchange",
			                     "arrow-circle-o-right","arrow-circle-o-left","toggle-left","caret-square-o-left","dot-circle-o","wheelchair","vimeo-square","turkish-lira","try",
			                     "plus-square-o","space-shuttle","slack","envelope-square","wordpress","openid","institution","bank","university","mortar-board","graduation-cap",
			                     "yahoo","google","reddit","reddit-square","stumbleupon-circle","stumbleupon","delicious","digg","pied-piper","pied-piper-alt","drupal","joomla",
			                     "language","fax","building","child","paw","spoon","cube","cubes","behance","behance-square","steam","steam-square","recycle","automobile","car",
			                     "cab","taxi","tree","spotify","deviantart","soundcloud","database","file-pdf-o","file-word-o","file-excel-o","file-powerpoint-o","file-photo-o",
			                     "file-picture-o","file-image-o","file-zip-o","file-archive-o","file-sound-o","file-audio-o","file-movie-o","file-video-o","file-code-o","vine",
			                     "codepen","jsfiddle","life-bouy","life-buoy","life-saver","support","life-ring","circle-o-notch","ra","rebel","ge","empire","git-square","git",
			                     "hacker-news","tencent-weibo","qq","wechat","weixin","send","paper-plane","send-o","paper-plane-o","history","genderless","circle-thin","header",
			                     "paragraph","sliders","share-alt","share-alt-square","bomb","soccer-ball-o","futbol-o","tty","binoculars","plug","slideshare","twitch","yelp",
			                     "newspaper-o","wifi","calculator","paypal","google-wallet","cc-visa","cc-mastercard","cc-discover","cc-amex","cc-paypal","cc-stripe","bell-slash",
			                     "bell-slash-o","trash","copyright","at","eyedropper","paint-brush","birthday-cake","area-chart","pie-chart","line-chart","lastfm","lastfm-square"
			                     ,"toggle-off","toggle-on","bicycle","bus","ioxhost","angellist","cc","shekel","sheqel","ils","meanpath","buysellads","connectdevelop","dashcube",
			                     "forumbee","leanpub","sellsy","shirtsinbulk","simplybuilt","skyatlas","cart-plus","cart-arrow-down","diamond","ship","user-secret","motorcycle",
			                     "street-view","heartbeat","venus","mars","mercury","transgender","transgender-alt","venus-double","mars-double","venus-mars","mars-stroke","mars-stroke-v",
			                     "mars-stroke-h","neuter","facebook-official","pinterest-p","whatsapp","server","user-plus","user-times","hotel","bed","viacoin","train","subway",
			                     "medium");
	
		
	/**
	 * Permet de retourner l'objet sous forme de chaine de caractère
	 *
	 */
	
	/**
	 * Permet de créer un outil de la barre des taches
	 *
	 * @param string $labelValue              le libellé de l'outil
	 * @param string $icon                    la désignation de l'icone à utiliser
	 * @param Sirah_View_Helper_EventHandler le gestionnaire des évenements javascript
	 *
	 * @return Sirah_View_Helper_Toolbar_Tool
	 */
	public function supinaTools($labelValue, $icon , $eventHandler = null , $attributes = array())
	{
		$tool = new static();
		if(null==$labelValue || empty($labelValue)){
			throw new Sirah_View_Helper_Exception("Impossible de créer l'outil car les paramètres fournis sont invalide");
		}
		$tool->setView(  $this->view);
		$tool->setLabel( $labelValue);
		$tool->setIcon(  $icon);
		$tool->setEventHandler( $eventHandler);
		$tool->setAttributes(   $attributes  );
		$tool->setAllowedEvents(array("click","submit","dblclick"));		
		return $tool;
	}
	
	/**
	 * Permet de créer une liste d'outils
	 * dans le dropdown du bouton
	 *
	 * @param array $items
	 * @param Sirah_View_Helper_Script_EventHandler $eventHandler
	 * @param array $defaultAttributes
	 *
	 */
	public function dropdown($items = array() , $eventHandler = null , $defaultAttributes = array())
	{
		if(!empty($items)) {
			foreach($items as $itemId => $itemLabel){
				if(is_numeric($itemId)){
					$itemId  = "btnDropdonwItemId-".intval($itemId);
				}
				$this->insertDropdownItem($itemLabel, $itemId, null, $eventHandler, $defaultAttributes);
			}
		}
		return $this;
	}
	
	/**
	 * Permet d'ajouter un element à la liste des
	 * outils dropdown
	 *
	 * @param mixed  $item
	 * @param string $itemId
	 * @param string $glyphicon
	 * @param Sirah_View_Helper_Script_EventHandler
	 * @param array $attributes
	 *
	 */
	public function insertDropdownItem($item , $itemId = null, $glyphicon = null, $eventHandler = null, $attributes = array())
	{
		if(is_string($item)) {
			$item  = $this->view->supinaTools($item , $glyphicon , $eventHandler , $attributes);
		} elseif(!$item instanceof Sirah_View_Helper_Supina_SupinaTools) {
			return $this;
		}
		if( null !== $itemId ){
			$item->setId($itemId);
			$this->_dropdownitems[$itemId]  = $item;
			return $this;
		}
		$this->_dropdownitems[]            = $item;
		return $this;
	}
	
	/**
	 * Permet de rétirer un element dropdown
	 *
	 * @param string $itemId
	 */
	public function removeDropdownItem($itemId)
	{
		if(isset($this->_dropdownitems[$itemId])){
			unset($this->_dropdownitems[$itemId]);
		}
		return $this;
	}
	
	/**
	 * Permet de recupérer un element du dropdown
	 *
	 * @param string $itemId
	 */
	public function getDropdownItem($itemId)
	{
		if( isset( $this->_dropdownitems[$itemId])){
			return $this->_dropdownitems[$itemId];
		}
		return false;
	}
	
	
	/**
	 * Permet de générer le html de la liste
	 * des élements du dropdwn
	 *
	 * @param array $items (Optionnal)
	 */
	public function dropdownList($items = array())
	{
		$items   = (empty($items)) ?  $this->_dropdownitems : $items;
		$dropdownListOutput = "";
		if( count($items)) {
			$listOutput     = "";
			foreach( $items as $itemId => $itemToolHelper){
				     $listOutput.= " <li class='dropdowListItem list".$itemId."'> ".$itemToolHelper." </li> ";
			}
			$dropdownListOutput = sprintf("<ul %s> %s </ul>" , $this->_htmlAttribs(array("class" => array("dropdown-menu"))) , $listOutput) ;
		}
		return $dropdownListOutput;
	}
	
	public function isDropdown()
	{
		return count($this->_dropdownitems);
	}
	
	/**
	 * Permet de mettre à jour le type d'icone de l'outil
	 *
	 * @param string $icon le type d'icone de l'outil
	 *
	 */
	public function setIcon($icon)
	{
		if(in_array($icon , self::$icons)){
			$this->_icon = $icon;
		}
		return $this;
	}
	
	public function __toString()
	{		
		$outputIcon      = "";
		$label           = $this->getLabel();
		$icon            = "glyph-icon icon-".$this->getIcon();
		$color           = $this->getColor();
		$attributes      = $this->getAttributes();
		$dropdownItems   = $this->_dropdownitems;
		$iconPosition    = $this->getIconPosition();
		$isDisabled      = $this->isDisabled();
		$dropdownOutput  = $dropdownIcon = "";
		
		if(null !== $icon){
			$iconClass = array($icon) ;
			if(!empty($color)) {
				$iconClass[]         = "font-".$color;
				$attributes["style"] = "color: ".$color;
			}
			$outputIcon              = sprintf("<i %s></i>", $this->_htmlAttribs(array("class" => $iconClass)));
		}
		if(!empty($dropdownItems)){
			$dropdownList       = $this->dropdownList();
			$dropdownIcon       = sprintf("<span %s></span>" , $this->_htmlAttribs(array("class" => array("glyph-icon","icon-caret-down"))));
			
			$dropdownBtnClass   = array();
			$dropdownBtnClass[] = "dropdown-menu";
			$dropdownOutput     = $dropdownList;
            $attributes["data-toggle"] 	= "dropdown";		
		}
		if(!isset($attributes["href"])){
			$attributes["href"]  = "#";
		}
		if( $isDisabled){
			$attributes["class"] = "disabled tool-disabled";
		}
		if(null!=($eventHandler  = $this->getEventHandler())){
			echo $eventHandler;
		}		
		$toolOutput              = sprintf("<a%s> %s <span> %s </span> %s</a> %s" ,  $this->_htmlAttribs($attributes) , $outputIcon , $label, $dropdownIcon, $dropdownOutput);
		switch($iconPosition) {
			case "left"  :
			case "top"   :
			case "bottom":
			default      :
				$toolOutput      = sprintf("<a%s>%s <span> %s </span> %s </a> %s",$this->_htmlAttribs($attributes) , $outputIcon , $label,$dropdownIcon, $dropdownOutput);
				break;
			case "right" :
				$toolOutput      = sprintf("%s<a%s> %s<span> %s </span> %s</a>",$dropdownIcon,$dropdownOutput ,$this->_htmlAttribs($attributes), $label , $outputIcon);			
		}		
		return  $toolOutput;		
	}
	
	
}