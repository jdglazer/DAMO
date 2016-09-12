<?php
	include($_SERVER['DOCUMENT_ROOT']."Permission_key.php");
	
	if(!class_exists("stdClass")) {
		
		class stdClass {}
	}
	
/**
 * A class with all the base capabilities necessary for parsing and expanding the XML DOM and performing the MySQL
 * queries based on XML DOM
 * @author Glazer, Joshua D.
 */
	class DM_format_parser_tools {
		
	//DEVELOPER NOTE: Need to redesign such that the public properties are private. Consider using getter functions
	
	//An array to store mysqli_result objects
		protected $mysql_result_registry;
	//A variable to store xml DOM Document object
		public $xml_doc;
	//A variable to store scope object 
		public $_scope;
	//A complete listing of nodes present in $xml_doc
		protected $nodeList;
	//A variable designed to temporarily aid recursive functions in persisting data between recursive calls
		protected $temp_node_listing;
/**
 * The constructor initialized the resigtry for MySQL results (mysql_result objects) and the DOM document around which
 * the front end JSON model is built. It performs the queries to the database based on presense of dm-query-point attribute
 * in XML model argument and created a list of DOM elements that is filtered of comments and textual elements
 * 
 * @param $_xml_model resource or string The xml model upon which query patter and JSON model will be based
 */
		function __construct($_xml_model) {
			
			$this->mysql_result_registry = [];
			$this->xml_doc = new DOMDocument(); 
			$this->xml_doc->validateOnParse = true;
			$this->temp_node_listing;
			
			if(is_file($_xml_model)) {
				$this->xml_doc->load($_xml_model);
			}
			else if(is_string($_xml_model)){
				$this->xml_doc->loadXML($_xml_model);
			}
		
		//perform queries and build non-text none list for further parsing
			$this->DMResultRegister("dm-query-point");
			$this->elementNodesAsList();
		}

/**
 * A function that returns the value of an attribute associated with a specific DOM element. If that element is not 
 * present in the element, it is searched for in parent elements until it is found. A null string is returned if it's
 * not found. 
 * 
 * @param $element The element for which to find the attribute value
 * @param $attr The name of the attribute whose value is being sought out
 * @return String The value of the attribute in question for the provided element or for the nearest parent element that has 
 * the specified element
 * @throws Exception For invalid argument types
 */
		protected function get_attr_val($element, $attr) {
			
			global $_SETTINGS;
		//verifying validity of argument types
			if(!is_object($element) || !is_string($attr)) {
				
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "XML parse failure" );
			}
			
			$return = "";
			$parent = $element;
			 			
			while($return == "" && $parent != NULL && $parent->nodeType != 9) {
				if($parent->hasAttribute($attr)) {
					$return = $parent->getAttribute($attr);
					$parent = $parent->parentNode;
				}
				else 
					$parent = $parent->parentNode;
			}
			return $return;
		}
/**
 * A function that performs a mysql query.  
 * 
 * @param $perm_key A Permission_key object representing a link to a database
 * @param $query MySQL query as a String
 * @param $bind_str A String formatted to represent the types of the values to beind to the query. These should be structured as the $types argument for the bind_param function of php's mysqli_stmt object. Default argument is null string.
 * @param $bind_array An array of values to bind to the MySQL query specified in $query argument (protection against SQL injection )
 * @return array|bool False if an error occurs or a 2-dimensional array containing a representation of the results
 */
 //DEVELOPER NOTE: function relies on mysqlnd being enabled. Uses mysqli, mysql_stmt objects and deprecated get_result
 //function. Need to change the structure to be forward compatible with new versions of php
		protected function mysql_query( $perm_key, $query, $bind_str = '', $bind_array = []) {
		//DEVELOPER NOTE: set this to throw exception if ERR_REPORT setting not admin_debug
			if(get_class($perm_key) != 'Permission_key') die(_err_report($this, "invalid argument(s)"));
			
			$link = $perm_key->get_conn();
			
			if(!$stmt = $link->prepare($query)) {
				if(is_object($link)) 
					$link->close();
				die(_err_report($this, "invalid mysql query: ".$query));
			}
			
			$param_array[] = $bind_str;
		//collecting bind values and storing in a set of dynamically created variables
			foreach($bind_array as $key => $bind_val) {
				
				$bind_name = 'bind'.$key;
				$$bind_name = addslashes($bind_val);
				$param_array[] = &$$bind_name; 
			}
			
		//allows for a dynamic number of arguments to be added to the bind_param function of the mysqli_stmt object
			call_user_func_array(array($stmt, 'bind_param'), $param_array);
			
			if(!$stmt->execute()){
				$stmt->close();	
				$link->close();
		//DEVELOPER NOTE: set this to throw exception if ERR_REPORT setting not admin_debug
				die(_err_report($this, "query could not be performed: ".$query));	
			}
			else {
			//builds an array of results to return
				$row; 
				$return_array = [];
				$result = $stmt->get_result();
				
				while($row = $result->fetch_assoc()) {
					$return_array[] = $row;
				};
				
				$return_array[0]["_point"] = 0;
				
				$stmt->close();
				$link->close();
				
				return $return_array;
			}
		}
/**
 * A function designed to parse the document and fill a registry with names of points in the document at which queries are 
 * assessed
 * 
 * @param $attr_register_marker A string argument sepcifying the name of the attribute which specifies the nodes at 
 * which queries are assessed
*/
		protected function DMResultRegister($attr_register_marker) {
			
			$this->attr_search($this->xml_doc->documentElement, $attr_register_marker, "query_point_");
		}
/**
 * This function does most of the work for the DM_result_register function by parsing through the document to find and set 
 * all occurances of the attribute who's presence determines query locations and then register these set names in the object's 
 * registry for mysqli_results
 * 
 * @param $currentNode The starting point in the XML DOM Document for the search for the specifice attribute name ($attr)
 * @param $attr The attribute that represents a query point in the DOM
 * @param $attr_value The default attribute base value to give the attribute if it has a null value. This is the same base 
 * name by which the query point is registered to the mysql_result_registry array (integer numbers are added to the end to
 * make the name unique)
 * @
 */
		private function attr_search($currentNode, $attr, $attr_val) {
			static $var_iterator = 0;
			
			if($currentNode->hasAttribute($attr)) {
				 
				if($currentNode->getAttribute($attr) == '' || 
					array_key_exists($currentNode->getAttribute($attr), $this->mysql_result_registry)) {
					
					$currentNode->setAttribute($attr, $attr_val.$var_iterator);
					$this->mysql_result_registry[$attr_val.$var_iterator] = new stdClass();
				}
				else {
					$this->mysql_result_registry[$currentNode->getAttribute($attr)] = new stdClass();
				}
				
				$var_iterator++;
			}; 
			
			if($currentNode->hasChildNodes()) {
				
				$childs = $currentNode->childNodes;
				
				for($i = 0; $i < $childs->length; $i++) {
					
					if($childs->item($i)->nodeType == 1)
						$this->attr_search($childs->item($i), $attr, $attr_val);
				}
			}
		}
		
/**
 * A helper function to initiate the construction of a nodeList from the starting point of the head element of the
 * xml model stored by this object
 */
		protected function elementNodesAsList() {
			$this->element_nodes_as_list_helper($this->xml_doc->documentElement);
		}
//DEVELOPER NOTE: This function does not verify that argument is of type DOMNode. This must be done at higher level in code
/**
 * Creates a node list filtered of text nodes and stores it in nodeList property
 * 
 * @param $element A DOMNode representing the highest parent level of node list to be stored in nodeList property
 */
		private function element_nodes_as_list_helper($element) {
		
		//nodeType of 1 indicates DOMElement
			if($element->nodeType == 1) {
				
				$this->nodeList[] = &$element;
			}
			
			if($element->hasChildNodes()) {
				
				$childList = $element->childNodes;
				
				for($i = 0 ; $i < $childList->length; $i++) {
				//recursive function call for DOMElements children
					$this->element_nodes_as_list_helper($childList->item($i));
				}				
			}
		}
		
/**
 * Provides a list of integer offsets in nodeList array associated with DOMNodes that have a given attribute present. Useful in determining query points in xml model by
 * finding elements with dm-query-point attribute present, for instance;
 * 
 * @param $attr A string value name for a specific attribute
 * @return array Offsets in the objects nodeList array
 * @throws Exception For invalid arguments
 */
		protected function getNodeByAttr($attr) {
			
			global $_SETTINGS;
		//checking validity of the argument	
			if( !is_string($attr) ) {
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "XML parse failure" );
			}
			
			$return = [];
			
			for($i = 0; $i < sizeof($this->nodeList); $i++) {
				if($this->nodeList[$i]->hasAttribute($attr))
					$return[] = $i;
			}
			return $return;
		}			
		
/**
 * A function designed to produce a list of offsets in nodeList associated with DOMNodes for which a specified attribute 
 * is present and has a specific value.
 * 
 * @param $attr A String with the attribute name
 * @param $val A String with the attribute value to look for
 * @return array An indexed array of offsets in array stored in nodeList propert
 * @throws Exception For invalid arguments
 */
		protected function getNodeByAttrValue($attr, $val) {
			
			$return = [];
			
			for($i = 0; $i < sizeof($this->nodeList); $i++) {
				if($this->nodeList[$i]->hasAttribute($attr) && $this->nodeList[$i]->getAttribute($attr) == $val)
					$return[] = $i;
			}
			return $return;
		}
/**
 * A function that parses down from a parent node and finds all descenedants with a certain attribute present.Uses the 
 * temp_node_listing helper property of this object to keep track of nodes to return. This function is then nested inside 
 * find_node_by_attribute_presence function
 * 
 * @param $parent_node_ A DOMNode for which the children elements will be searched through and stored in the object's temp_node_listing array if they contain the valid attributes
 * @param $attribute_to_search_by A String representing the name of the attribute by which to search for nodes
 * 
*/

		private function find_child_nodes_by_attribute_presence($parent_node_, $attribute_to_search_by) {
			
			if($parent_node_->hasChildNodes()) {
				
				$child_list = $parent_node_->childNodes;
				
				for($i = 0; $i < $child_list->length; $i++) {
					
					if($child_list->item($i)->nodeType == 1) {
						
						$attrs = $child_list->item($i)->attributes;
						
						for($j = 0; $j < $attrs->length; $j++) {
							
							if($attrs->item($j)->nodeName == $attribute_to_search_by) {
								
								$this->temp_node_listing[] = $child_list->item($i);								
								break;
							}
						}
						
						if($child_list->item($i)->hasChildNodes()) {
							
							$this->find_child_nodes_by_attribute_presence($child_list->item($i), $attribute_to_search_by);
						}
					}
				}
			}
		}
		
/**
 * Gets all nodes under and including the parent node specified for which the specified attribute is present
 * 
 * @param $parent_node_ The parent node where the search starts
 * @param $search_attr A String representing the attribute name by which to search
 * @return array A list of DOMNodes with the specified attributes
 */
		protected function find_nodes_by_attribute_presence($parent_node_, $search_attr) {
			
			if(get_class($parent_node_) != "DOMNode" || !is_string($search_attr)) 
				_err_report($this, "invalid arguments");
			
			$this->temp_node_listing = [];
				
			$this->find_child_nodes_by_attribute_presence($parent_node_, $search_attr);
			
			return $this->temp_node_listing;
			
		}
/** 
 * This is a function designed to rebuild xml document based on dm-repeat directive. In this version only the first dm-repeat directive 
 * found is used in reconstruction - all following dm-repeats are ignored.
 * 
 * @param $listElementName This is the specific String tag name  associated with a list element. List elements get placed into indexed arrays in the frameworks final json output
 * @param $DMRepeatDirective A String representing the name of the attribute associated with the repeat functionality 
 * @return bool true if the rebuild was a successful, false otherwise
 */
		protected function rebuildWithDMRepeat($listElementName, $DMRepeatDirective) {
			
			global $_DmRepeatSpecialInstructions;
			
			if(!empty($this->getNodeByAttr($DMRepeatDirective))) {
			
			/*DEVELOPER NOTE:
			 *---------------
			 *MAY WANT TO ENCASE THIS ENTIRE THING BELOW HERE IN A FOREACH LOOP WITH:
				$this->getNodeByAttr($DMRepeatDirective) 
			 *AS ARRAY TO ITERATE OVER 
			*/
			//gets first dm-repeat directive containing node
				$node = $this->nodeList[$this->getNodeByAttr($DMRepeatDirective)[0]];
			//gets dm-repeat attribute from node
				$node_attr = $node->attributes->getNamedItem($DMRepeatDirective);
			//gets value of attribute node
				$value = $node_attr->nodeValue;
			//create a variable to store replacement node
				$new_node = null;

				unset($node_attr);
				
				$name = $node->nodeName;
				$attributes = $node->attributes;
				$childList = $node->childNodes;
			
				$new_node = $this->xml_doc->createElement($name);
				
				for($j = 0; $j < $attributes->length; $j++) {
					$attr = $attributes->item($j);
					
				/*DEVELOPER NOTE:
				 *---------------
				 * PROCEDURE TO ADD FUNCTIONALITY OF SPECIAL ATTRIBUTE ACTIONS ARRAY FROM SETTINGS FILE:
				 * In order to do this we will add a function to parse through repeated child node before it is repeated and search
				 * for attributes in attribute actions array. If found, attribute will be removed and new one will be re-added in its 
				 * place
				 */					
					$new_node->setAttribute($attr->nodeName, $attr->nodeValue);
				}
				
				for($i = 0; $i < $value; $i++) {
					
					$new_list_el = $this->xml_doc->createElement($listElementName);	
					$new_list_el->setAttribute("iter", strval($i));
					
					for($k = 0; $k < $childList->length; $k++) {
						if($childList->item($k)->nodeType == 1) {
							$child = $childList->item($k)->cloneNode(true);
							$new_list_el->appendChild($child);
						}
					}
					
					foreach($_DmRepeatSpecialInstructions as $key=>$function) {
						
						$nodes_found = $this->find_nodes_by_attribute_presence($new_list_el, $key);
						
						foreach($nodes_found as $_node_) {
							
							$replacement_node = $_node_->cloneNode(true);
							$replacement_node->removeAttribute($key);
							
							$a = $function($_node_->attributes->getNamedItem($key)->nodeValue, $i);
							
							if(is_array($a) && sizeof($a) > 1) { 								
								$replacement_node->setAttribute($a[0], $a[1]);
							}
											
							$new_list_el->replaceChild($replacement_node, $_node_);
						}
						
					} 
					$new_node->appendChild($new_list_el);
				}
			/* DEVELOPER NOTE:
			 * ---------------
			 * There is an error in this function that seems to not allow multiple dm-repeats to be handled.
			 * This is something that should be fixed in future versions of this program
			 */
				$node->parentNode->replaceChild($new_node, $node); 
			//nodeList is reconstructed based on adjustment made to DOMDocument
				$this->nodeList = [];
				$this->elementNodesAsList();
				
				return true;
	
			}
			else return false; 			
		}
/*TEST CODE:
 *----------
		public function tester($n, $l, $m) {
			return $this->xml_doc->documentElement->childNodes->item($n)->childNodes->item($l)->attributes->item($m)->nodeName;
		}
		
		public function tester1 () {
			$tmp_array = $this->find_nodes_by_attribute_presence($this->xml_doc->documentElement, "dm-class");
			echo $tmp_array[1]->attributes->item(1)->nodeValue;;
		} 
*/
 	}	

?>

