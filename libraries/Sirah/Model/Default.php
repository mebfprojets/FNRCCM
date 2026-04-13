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
 * Cette classe représente le modele par défaut du Package de SIRAH
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

class Sirah_Model_Default extends Zend_Db_Table_Row_Abstract 
{
	
	/**
	 *
	 * @var string : le message d'erreur interne au model
	 */
	protected $_error = null;
	
	/**
	 *
	 * @var Zend_Cache_Core instance : le gestionnaire de stockage de cache
	 */
	protected $_cache = null;
	
	/**
	 * Activation/désactivation de l’autosauvegarde
	 * 
	 * à la destruction
	 * 
	 * @var boolean
	 */
	private static $_autoSave = true;
	
	
	protected $_cacheKey     = null;
	
	
	/**
	 * Le constructeur de la classe
	 * 
	 * @access public
	 * 
	 * @param  array $input les données des colonnes de la table du modele
	 *       	 
	 * @return void
	 */
	public function __construct( $input = array() ) 
	{
		$class             = explode ( '_', get_class ( $this ) );
		$this->_tableClass = ( null == $this->_tableClass || empty($this->_tableClass) ) ? 'Table_' . array_pop ( $class ) . 's' : $this->_tableClass;		
		parent::__construct ( $input );		
		if (! isset ( $input ['data'] )) {
			  $cols = $this->_table->info(Zend_Db_Table_Abstract::COLS );
			  $input ['data'] = array_combine( $cols , array_pad(array(), count($cols ), null ) );
		}
		$this->_data = $input ['data'];		
		$this->_initCache();
	}
	
	
	/**
	 * Permet d'initialiser le cache du model
	 *
	 * @access public
	 * 
	 */
	protected function _initCache()
	{
		if(null == $this->_cache) {
			$cacheManager   = Sirah_Fabric::getCachemanager();
			if(!$cacheManager->hasCache("Model" )) {
				$modelCache = Sirah_Cache::getInstance("Model", "Core", "File", array ("lifetime" => 1800, "automatic_serialization" => true ) );
			} else {
				$modelCache = $cacheManager->getCache("Model" );
			}
			$this->_cache   = $modelCache;
		}
	}
		
	/**
	 * méthode de gestion du paramètre d'autosauvegarde
	 * 
	 * @access public
	 * @param
	 *       	 void
	 * @return void
	 */
	public static function setAutoSave($save) 
	{
		self::$_autoSave = ( bool ) $save;
	}
	
	/**
	 * Sauvegarde les données dans le cache
	 * 
	 * @access public
	 * 
	 * @param  array    $data           le tableau des données à stocker dans le cache
	 * @param  string   $cacheId        la clé du cache correspondant aux données qu'on veut mémoriser dans le cache
	 * @param  string   $prefixCacheId  le préfixe de la clé de l'information que l'on souhaite cache
	 * @param  array    $cacheIdTags    les balises correspondantes à la clé des données cachées
	 * 
	 * @return void
	 */
	public function saveToMemory($data , $cacheId=null , $prefixCacheId="" , $cacheIdTags = array()) 
	{
		$this->_initCache();
		$cache     = $this->_cache;
		$data      = empty($data) ? $this->_data : $data;
		$tableName = $this->_table->info("name");		
		
		if(! $cache instanceof Zend_Cache_Core) {
			return;
		}
		if(null==$cacheId){
		    $cacheId = '';
		    foreach ( $this->_primary as $primary ) {
			    $cacheId .= '_' . $this->$primary;
		    }
		    $cacheKey     = Sirah_Functions_Generator::getAlpha(8);
		    while(false  != $cache->load($prefixCacheId . $cacheId . $cacheKey)){
		    	$cacheKey = Sirah_Functions_Generator::getAlpha(8);
		    }
		    $cacheId      = $cacheId.$cacheKey;
		}
		if(false !== $cache->load($prefixCacheId . $cacheId)) {
			$cache->remove($prefixCacheId . $cacheId);
			if(!empty($cacheIdTags)) {
				$cache->clean(Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG , $cacheIdTags);
			}
		}
		try{
		     $cache->save($data , $prefixCacheId . $cacheId , $cacheIdTags);
		} catch (Exception $e) {
			$this->setError("Le gestionnaire de cache du processus metier ne supporte pas les balises, il indique l'erreur suivante :".$e->getMessage());
			return false;
		}
		return true;
	}
	
	/**
	 * Définir le gestionnaire de cache du model
	 * 
	 * @access public
	 * 
	 * @return void
	 */
	public function setMetadataCache($cache = null) 
	{
		if ($cache instanceof Zend_Cache_Core) {
			$this->_cache = $cache;
		}
	}
	
	/**
	 * Définir le gestionnaire de cache du model
	 * 
	 * @access public
	 * 
	 * @return Zend_Cache_Core instance
	 */
	public function getMetadataCache() 
	{
		return $this->_cache;
	}
	
	/**
	 * Permet de retrouver des données depuis le cache
	 *
	 * @access public
	 * 
	 * @param  string  $cacheId           la clé unique de la donnée qu'on veut recupérer dans le cache
	 * @param  string  $prefixCacheId     Le préfix de la clé de la donnée qu'on souhaite récupérer dans le cache
	 * @param  array   $IdsMatchingTags   les balises correspondant à la clé unique au cas ou on ne connait pas la clé
	 *
	 * @return mixed  les données que nous voulons false au cas ou il ne trouve rien
	 */
	public function fetchInCache($cacheId=null , $prefixCacheId="" , $IdsMatchingTags = array())
	{
		$cache  = $this->_cache;
		//Si on n'a pas un gestionnaire de cache valide pour ce model, on retourne false
		if(null===$cache || (!$cache instanceof Zend_Cache_Core)){
			return false;
		}
		//Si on ne connait pas la clé de l'information qu'on veut récupérer à partir du cache
		if((null===$cacheId || empty($cacheId)) && !empty($IdsMatchingTags)){
			try{
			     $foundIds   =  $cache->getIdsMatchingTags($IdsMatchingTags);
			} catch(Exception $e) {
				$this->setError("Le gestionnaire de cache du processus metier ne supporte pas les balises");
				return false;
			}
			$cacheId  = (count($foundIds))  ? $foundIds[0] : null;
		}		
		if(null==$cacheId){
			return false;
		}
		return $cache->load( $prefixCacheId . $cacheId);
	}
	
	/**
	 * Destructeur d’objet.
	 * Sauvegarde automatiquement l’objet dans le cache
	 * si la sauvegarde est activée et si l’objet a été modifié
	 * depuis sa création.
	 */
	public function __destruct() 
	{
		if (! self::$_autoSave || empty ( $this->_modifiedFields )){
			return;
		}
		$this->saveToMemory($this->_data , strtolower($this->_tableClass));
	}
	
	/**
	 * Appelé à la désérialisation de l’objet
	 * Reconnecte automatiquement l’objet à sa table
	 */
	public function __wakeup() 
	{
		$this->setTable( new $this->_tableClass());
	}
	
	/**
	 * Permet d'enregistrer une erreur dans le modele
	 * 
	 * @access   public
	 * @param    string : le message d'erreur
	 * @return   void
	 */
	public function setError($error) 
	{
		$this->_error = $error;
	}
	
	/**
	 * Permet d'enregistrer une erreur dans le modele
	 * 
	 * @access public
	 * @param
	 *       	 void
	 * @return string
	 */
	public function getError() 
	{
		return $this->_error;
	}
		
	/**
	 * Permet de faire un netoyage de caractères invalides
	 * dans l'id ou le tag de cache
	 *
	 * @access public
	 * @param  string $key  la variable à nettoyer
	 * 
	 * @return string  la variable propre
	 */
	protected function _sanitizeCacheTagOrId($key)
	{
		if(empty($key)){
			throw new Sirah_Model_Exception("Impossible de créer un tag ou un id de cache vide");
		}
		$sanitizedRegex  = "/[^a-zA-Z0-9_]/";
		return preg_replace($sanitizedRegex , "" , $key);
	}
	
	/**
	 * Permet de récuperer un tableau de données
	 * 
	 * @access public
	 * 
	 * @param  string  $defaultText
	 * @param  array   $columns      Les colonnes que l'on souhaite
	 * @param  array   $search       Les critères de limitation de la liste
	 * @param  integer $limit        La taille maximale des lignes qu'on souhaite récupérer
	 * @param  boolean $cached       
	 * 
	 * @return array les données du selectListe que l'on veut récupérer
	 */
	public function getSelectListe( $defaultText = null , $columns = array() , $search=array() , $limit=0 , $callback = null , $cached = true) 
	{
		$cache                     = $this->_cache;
		$adapter                   = $this->_table->getAdapter();
		$tableName                 = $this->_table->info("name");
		$columns                   = (empty($columns)) ? array("id", "libelle") : $columns;
		$orders                    = array();
		$cached                    = intval( $cached );
		
		if( isset( $search["orders"] ) ) {
			$orders                = $search["orders"];
			unset( $search["orders"] );
		}		
		$cacheTags                 = (!empty($search)) ? Sirah_Functions_ArrayHelper::getKeys( $search , "string") : array("allTag");
		$cacheTags[]               = "limitTag".$limit;
		$cacheTags[]               = "selectlistTag";
		$prefixCacheId             = "selectListe".$this->_sanitizeCacheTagOrId($tableName);
		$cacheId                   = "modelRowsListe";
		
		if( ( false  != ($cachedSelectListe = $this->fetchInCache( $cacheId , $prefixCacheId , array()))) && $cached ) {
			return $cachedSelectListe;
		}		
		$select    =  $adapter->select()->from($tableName , $columns);
	    if(!empty(   $search ) ) {
	   	    foreach( $search as $searchKey => $searchVal ) {
	   		         $select->where(  $searchKey." LIKE ?", "%".strip_tags($searchVal)."%" );
	   	    }
	    }
		if( intval( $limit ) ) {
			$select->limit($limit);
		}
		if( !empty( $orders ) ) {
			$select->order( $orders );
		}
		$rows      =  $adapter->fetchPairs($select);	
	
		if( null !== $defaultText ){
			$rows[0] = $defaultText;
		}
		if(is_callable($callback) && !empty($rows)){
			array_walk_recursive($rows , $callback);
		}		
		if(null!==$cache){
			$this->saveToMemory( $rows , $cacheId , $prefixCacheId , array());
		}
       return $rows;
	}
	
	public function getTypeaheadList( $limit = 10 , $query = null , $orders = array(), $queryKey = "libelle" , $cached = false )
	{
		$search      = array("orders"  => $orders );
		$queryKey    = ( !empty( $queryKey ) ) ? $queryKey : "libelle";
		if ( null   != $query ) {
			 $search = array( $queryKey => $query );
		}
		$rows = $this->getAutocompleteListe( null , $search , $limit, null , $cached  );
		return $rows;		
	}
	
	/**
	 * Permet de récupérer la liste d'autocomplétion
	 *
	 * @access public
	 *
	 * @param   array     les colonnes que l'on souhaite pour la liste d'autocomplétition
	 * @param   array     les données de recherche
	 * @param   integer   la taille maximale des données à récupérer
	 * @return  array
	 */
	public function getAutocompleteListe($columns = array() , $search = array() , $limit = 0 , $callback = null , $cached = true)
	{
		$cache             = $this->_cache;
		$adapter           = $this->_table->getAdapter();
		$tableName         = $this->_table->info("name");
		$columns           = (empty($columns)) ? array("id" , "libelle")  :  $columns;
		$orders            = array();
		$cached            = intval( $cached );
		
		if( isset( $search["orders"] ) ) {
			$orders        = $search["orders"];
			unset( $search["orders"] );
		}
		
		$cacheTags         = (!empty($search)) ? Sirah_Functions_ArrayHelper::getKeys($search , "string") : array("allTag");
		$cacheTags[]       = "limitTag".$limit;
		$cacheTags[]       = "autocompleteTag";
		$prefixCacheId     = "autocompleteListe".$this->_sanitizeCacheTagOrId($tableName);
		$cacheId           = "modelRowsListe";
		
		if( (false  != ($cachedAutocompleteListe = $this->fetchInCache( $cacheId , $prefixCacheId , array()))) && $cached){
			return $cachedAutocompleteListe;
		}		
		$select    =  $adapter->select()->from($tableName , $columns);
	    if(!empty(   $search ) ) {
	   	    foreach( $search as $searchKey => $searchVal ) {
	   		   $select->where(  $adapter->quoteIdentifier ( $searchKey ) ." LIKE ?", "%".strip_tags( $searchVal )."%" );
	   	    }
	    }
		if(intval($limit)){
			$select->limit($limit);
		}
		if( !empty( $orders ) ) {
			 $select->order( $orders );
		}
		$rows              =  $adapter->fetchAll($select);
		$autocompleteList  =  array();
		if( count($rows) ) {
			$rowid         = 0;
			foreach( $rows as $k => $val ) {
				if(is_callable($callback)) {
					array_walk_recursive($val , $callback);
				}
				$rowValues                          = Sirah_Functions_ArrayHelper::getValues ( $val , "string" , null , false , null);
				if( count( $columns ) ) {
					$autocompleteList[$rowid]       = array_intersect_key( $val , $columns );
				}
				$autocompleteList[$rowid]["label"]  = isset($rowValues[0]) ? $rowValues[0] : "";
				$autocompleteList[$rowid]["value"]  = isset($rowValues[1]) ? $rowValues[1] : "";
				$rowid++;
			}
		}	
		if($cache instanceof Zend_Cache_Core){
			$this->saveToMemory( $autocompleteList , $cacheId , $prefixCacheId , array());
		}
       return $autocompleteList;
	}
		
	/**
	 * Permet de récupérer des lignes provenant de la table de ce modele
	 *
	 * @access public
	 *
	 * @param   array     $columns les colonnes que l'on souhaite pour la liste d'autocomplétition
	 * @param   array     $joins la liste des tables qu'il faut joindre
	 * @param   array     $search les filtres de recherche de lignes
	 * @param   array     $order les paramètres de l'ordre de tri
	 * @param   array     $group les options de groupement des lignes trouvées
	 * @param   integer   $limitStart la position initiale du curseur
	 * @param   integer   $limitEnd   la position finale du curseur
	 * @param   bool      $cached     Indique s'il faut mettre en cache ou pas
	 * @param   callable  $callback   Une fonction à appliquer sur la liste des données récupérées
	 * @return  array
	 */
	public function getList($columns = array() , $joins = array() , $search = array() ,$order = array() , $group = array(), $pageNum = 0 , $pageMaxItems = 0 , $cached = true , $callback=null)
	{
		$cache              = $this->_cache;
		$adapter            = $this->_table->getAdapter();
		$tableName          = $this->_table->info("name");
		$prefixCacheId      = "liste".$this->_sanitizeCacheTagOrId($tableName);
		$columns            = (array) $columns;
		$joins              = (array) $joins;
		$search             = (array) $search;
		$order              = (array) $order;
		$group              = (array) $group;
		$pageNum            = intval($pageNum) ;
		$pageMaxItems       = intval($pageMaxItems);
		$cached             = (bool) $cached;
		
		if(($cache instanceof Zend_Cache_Core) && $cached){
			
		  //Créons les tags de reconnaissance de la clé de cette liste qu'on souhaite stocker dans le cache
		  $columnKeys       = Sirah_Functions_ArrayHelper::getValues($columns,"string" , null,false,"columnsTags");
		  $joinKeys         = Sirah_Functions_ArrayHelper::getKeys($joins,"string",null,false,"joinsTags");
		  $searchKeys       = Sirah_Functions_ArrayHelper::getKeys($search,"string",null,false,"searchTags");
		  $orderKeys        = Sirah_Functions_ArrayHelper::getKeys($order,"string",null,false,"orderTags");
		  $groupKeys        = Sirah_Functions_ArrayHelper::getKeys($group,"string",null,false,"groupTags");
		
		  if(count($columnKeys)){
			foreach($columnKeys as $columnKey){
				$cacheTags[]  = $columnKey;
			}
		  }
		  if(count($joinKeys)){
			foreach($joinKeys as $joinKey){
				$cacheTags[]  = $joinKey;
			}
		  }		  
		  if(count($searchKeys)){
		  	foreach($searchKeys as $searchKey){
		  		$cacheTags[]  = $searchKey;
		  	}
		  }
		  if(count($orderKeys)){
		  	foreach($orderKeys as $orderKey){
		  		$cacheTags[]  = $orderKey;
		  	}
		  }
		  if(count($groupKeys)){
		  	foreach($groupKeys as $groupKey){
		  		$cacheTags[]  = $groupKey;
		  	}
		  }		  
		  $cacheTags[]     = "limitStartTag".$limitStart;
		  $cacheTags[]     = "limitEndTag".$limitEnd;
		  if(false  != ($cachedListe = $this->fetchInCache(null , $prefixCacheId , $cacheTags))){		  
		  	 return $cachedListe;
		  }
	   }
	   $columns = (empty($columns)) ? "*" : $columns;
	   $select  = $adapter->select()->from(array("T" => $tableName),$columns);
	   
	  if(!empty($joins)){
	   	$i  = 0;
	   	foreach($joins as $joinvalue){
	   		$joinedTable    = isset($joinvalue["table"])?array("J".$i => $joinvalue["table"]):null;
	   		$joinedRelation = null;
	   		$joinedColumns  = (isset($joinvalue["columns"]) && !empty($joinvalue["columns"]) )?$joinvalue["columns"]:null;
	   		if(null!==$joinedTable && isset($joinvalue["relation"])){
	   			if(list($joinRelationLeft,$joinRelationRight) = each($joinvalue["relation"])){
	   			   $joinedRelation = "J".$i.".".$joinRelationLeft." = T.".$joinRelationRight;
	   			}
	   		}	   		
	   		if(null!==$joinedRelation){
	   			$select->join($joinedTable,$joinedRelation,$joinedColumns);
	   		}
	   		$i++;
	   	 }
	   }	   
	  if(!empty($search)){
	   	  foreach($search as $searchKey => $searchVal){
	   		$select->where($searchKey." = ?",$searchVal);
	   	  }
	  }
	  if(!empty($order)){
	  	$select->order($order);
	  }
	  if(!empty($group)){
	  	$select->group($group);
	  }		  
	  if($pageNum && $pageMaxItems && ($pageMaxItems > $pageNum) ) {
	  	 $select->limitPage( $pageNum , $pageMaxItems );
	  }	 
	  $listeRows   = $adapter->fetchAll($select);	  
	  //On applique unn traitement particulier aux elements de la liste
	  if(is_callable($callback)){
	  	 array_walk_recursive($listeRows , $callback);
	  }	  
	  if(($cache instanceof Zend_Cache_Core) && $useCache){
	  	  $this->saveToMemory($listeRows,null,$prefixCacheId,$cacheTags);
	  }
	  return  $listeRows;
	}
	
	/**
	 * Permet de vérifier la présence de doublons dans un tableau
	 * 
	 * @access public
	 * @param    array les données à vérifier
	 * @return array
	 */
	public function fetchDoublons($inputdata = array()) 
	{
		$doublons = array ();
		$cols     = $this->_table->info ( Zend_Db_Table_Abstract::COLS );
		$select   = $this->_table->select ();
		$adapter  = $this->_table->getAdapter ();
		
		if (count( $cols )) {
			foreach ( $cols as $field ) {
				if (isset ( $inputdata [$field] )) {
					$valeur = $inputdata [$field];
					$doublonRows = $table->fetchAll($adapter->quoteInto ( $select->where ( $adapter->quoteIdentifier ( $field ) . "=?", $valeur ) ) );
					if (! empty ( $doublonRows )){
						$doublons [$field] = $doublonRows;
					}
				}
			}
		}
		return $doublons;
	}
	
	/**
	 * Permet d'insérer des lignes dans la table
	 * 
	 * @access public
	 * @param  array les données à enregistrer dans la table
	 * @return le resultat de l'enregistrement
	 * 
	 */
	public function insert($inputData = array()) 
	{
		$this->setFromArray($inputData);
		return $this->save();
	}
	
	public function getEmptyData()
	{
		$table  = $this->_table;
		$cols   = $table->info(Zend_Db_Table_Abstract::COLS );
		$data   = array_combine($cols, array_pad(array(), count( $cols ), null ) );
		return $data;
	}
	
	
	/**
	 * Méthode magique permettant de récupérer les informations des données
	 * parentes ou des données dépendantes
	 * 
	 * @access public
	 * @param  string       le nom de la colonne
	 * @return array        la valeur de la colonne
	 */
	public function __get($columnName) 
	{
		$columnName = $this->_transformColumn ( $columnName );		
		if (array_key_exists($columnName, $this->_data )) {
			return $this->_data [$columnName];
		}		
		// Au cas ou c'est une colonne parente
		if (isset ( $this->_data ["fk_id_".$columnName] )) {
			$parentClassName = "Table_" . ucfirst ( $columnName ) . "s";
			if (class_exists ( $parentClassName )) {
				$parentRow = @$this->findParentRow($parentClassName);
				if ($parentRow && isset ( $parentRow->libelle )) {
					 return $parentRow->libelle;
				}
			}
		}
		throw new Sirah_Model_Exception ( "La colonne `" . $columnName . "` que vous souhaitez recuperer dans la ligne, est introuvable" );
	}
	
	
	/**
	 * Méthode permettant de récupérer la clé de l'instance de la ligne
	 *
	 * @access  public
	 * 
	 * @param   string   $primaryKey  le nom de la clé primaire
	 *
	 * @return string
	 */
	public function getRowCacheKey($primaryKey = null)
	{
		if( null != $this->_cacheKey ) {
		   return $this->_cacheKey;
		}
		$table              = $this->_table;
		$adapter            = $table->getAdapter();
		$tableName          = $table->info("name");		
		if (null === $primaryKey) {
			$primaryKey     = $table->getPrimary();
		}
		$prefixCacheId      = "row_".$this->_sanitizeCacheTagOrId($tableName);
		$cacheId            = "pk_".$this->_sanitizeCacheTagOrId($primaryKey);		
		$cacheKey           = $this->_cacheKey = $prefixCacheId . $cacheId;		
		return $cacheKey;	
	}
	
	
	/**
	 * Méthode permettant de supprimer la ligne dans le cache
	 *
	 * @access  public
	 * 
	 */
	public function cleanRowCache()
	{
		$cacheKey = $this->getRowCacheKey();
		$cache    = $this->getCache();		
		if( null != $cache ) {
			$cache->remove($cacheKey);
		}
	}
	
	
	/**
	 * Méthode permettant de récupérer une ligne à partir de la clé primaire
	 * 
	 * @access  public
	 * 
	 * @param   string   $primaryVal  la valeur de la clé primaire
	 * @param   string   $primaryKey  le nom de la clé primaire
	 * @param   callable $callback    un traitement à appliquer sur les données
	 * @param   boolean  $cached
	 * 
	 * @return Zend_Db_Table_Row instance
	 */
	public function findRow( $primaryVal , $primaryKey = null , $callback = null , $cached = true ) 
	{		
		if( empty($primaryVal) && ( $primaryVal == 0 ) && ( null == $primaryVal ) ) {
			 return false;
		}
		$table              = $this->_table;
		$adapter            = $table->getAdapter();
		$tableName          = $table->info("name");				
		if ( null == $primaryKey ) {
			 $primaryKey    = $table->getPrimary();
		}
		$prefixCacheId      = "row_".$this->_sanitizeCacheTagOrId($tableName);
		$cacheId            = "pk_".$this->_sanitizeCacheTagOrId($primaryKey);
		$this->_cacheKey    = $prefixCacheId . $cacheId;
		if( ( false !== ( $cachedRow  = $this->fetchInCache( $cacheId , $prefixCacheId ))) && $cached){
			return $cachedRow;
		}
		$row  = $this->load( $primaryVal , $primaryKey , $callback );
		if( $row && $cached ) {
		    $this->saveToMemory($row , $cacheId , $prefixCacheId);
		}
		return $row;
	}
	
	/**
	 * Méthode permettant de récupérer une ligne à partir de la clé primaire
	 * 
	 * @access public
	 * @param  string    $primaryVal  la valeur de la clé primaire
	 * @param  string    $primaryKey  le nom de la clé
	 * @param  callable  $callback    un traitement à appliquer sur les données
	 * 
	 * @return Zend_Db_Table_Row instance
	 * 
	 */
	public function load( $primaryVal , $primaryKey = null , $callback = null ) 
	{
		$table        = $this->_getTable();
		$adapter      = $table->getAdapter();
		if(null === $primaryVal || empty($primaryVal)) {
			throw new Sirah_Model_Exception(" Impossible de récupérer la ligne à partir d'une valeur vide ");
		}	
		if ( null == $primaryKey ) {
			 $primaryKey = $table->getPrimary();
		}		
		$select        = $table->select ()->where($adapter->quoteIdentifier($primaryKey )."=?" , $primaryVal);
		$row           = $table->fetchRow($select );
		
		if( is_callable( $callback ) && $row ){
		    $rowToArray =  $row->toArray();
		    array_walk_recursive( $rowToArray , $callback);
		    $row->setFromArray($rowToArray);
		}		
		return $row;
	}
	
	function getParams()
	{
		if(empty($this->params)){
			$this->setParams();
		}
		$parametres         = $this->params;
		$paramsExplode      = explode(";" , $parametres);
		$params             = new stdClass();
		if(count($paramsExplode)) {
			foreach($paramsExplode as $param) {
				$element                = explode("=" , $param);
				$params->{$element[0]}  = ( isset( $element[1] )) ? $element[1] : null;
			}
		}
		return $params;
	}
	
	/**
	 * Méthode permettant de récupérer un paramètre de la ligne
	 *
	 * @access public
	 * @param  string  $name       la clé du paramètre
	 * @param  string  $defaultVal la valeur par defaut
	 *
	 */
	function getParam( $name , $defaultVal="")
	{
		$params   = $this->getParams();	
		if( empty($name) || (null===$name) || !isset($params->{$name}) ) {
			return;
		}
		if(!isset($params->{$name})) {
			$params->{$name}  = $defaultVal;
		}
		return $params->{$name};
	}
	
	/**
	 * Méthode permettant de récupérer sous forme de tableau les paramètres de la ligne
	 *
	 * @access public
	 * @param  array   $params
	 *
	 */
	function paramsToArray( $params = array() )
	{
		if( empty( $params ) ) {
			$params       = $this->getParams();
		}
		if(is_object($params)) {
			$array        =  get_object_vars($params);
			$params       = array();	
			if(count($array)) {
				foreach($array as $k=>$val) {
					$params[$k]  = $val;
				}
			}
		} elseif(is_string($params)) {
			$paramsExplode                    = explode(";", $params);
			if(count($paramsExplode)) {
				foreach( $paramsExplode   as $param) {
					     $element             = explode("=", $param);
					     $elementKey          = (isset($element[0] )) ? $element[0] : 0;
					     $elementVal          = (isset($element[1] )) ? $element[1]  : 0; 
					     $params[$elementKey] = $elementVal;
				}
			}
		}
		return $params;
	}
	
	/**
	 * Méthode permettant de mettre à jour les paramètres associées à une ligne de la table
	 *
	 * @access public
	 * @param  array   $parametres
	 *
	 */
	function setParams( $parametres = array() )
	{		
		$formatParams    = "";
		if(count( $parametres ) ) {
			foreach( $parametres as $key  =>  $val ) {
				$formatParams  .= $key . "=" . $val .";" ;
			}
		}
		$this->params = substr( $formatParams , 0 , -1 );
		return $this->save();
	}
	
	
	/**
	 * Méthode permettant de récupérer la valeur de la colonne de la ligne
	 *
	 * @access public
	 * @param  string   $var       l'identifiant de la colonne
	 * @param  callable $callback  un traitement à effectuer sur la valeur avant de retourner
	 *
	 * @return string la valeur de la colonne
	 */
	public function get($var,$callback=null) 
	{
	   if(!isset($this->_data[$var])){
		   throw new Sirah_Model_Exception(sprintf("La colonne %s n'existe pas dans la ligne",$var));
	   }
	   if(is_callable($callback)){
	   	  return call_user_func($callback,$this->_data [$var]);
	   }		
		  return $this->_data [$var];
	}

}
