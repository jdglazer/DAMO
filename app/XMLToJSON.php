<?php
	require_once( $_SERVER["DOCUMENT_ROOT"]."DM_initialize.php" );
	
/**
 * A class that is responsible for taking an XML model and valid instance of the Scope object and performing final task of converting 
 * to JSON model. This was originally supposed to be the final object the user interacts with to draw XML string out of framework; now,
 * however, it's the Damo object!
 * 
 * @author Glazer, Joshua D.
 */
	class XMLToJSON extends DM_initialize {
		
/**
 * Constructor initializes object by calling implementing parent constructor
 * 
 * @param $Scope An instance of the Scope object containing all data necessary for making and processing queries
 * @param $_xml_doc The XML DOM around which JSON end-product will be based
 */
		function __construct($scope, $_xml_or_json) {
			parent::__construct($scope, $_xml_or_json);
						
		}
		
/**
 * A function that checks to see if a given DOMElement/DOMNode has child elements, 'elements' being defined as tag type nodes
 * 
 * @param $element The parent element to check for children elements
 * @return bool True if the DOMElement passed to argument has child tag elements, false if not
 */
		protected function XMLhasChildElement($element) {
			
			if(get_class($element) != "DOMNode" && get_class($element) != "DOMElement" ) 
				die(_err_report($this, "Invalid argument"));
			
			
			$return = false;
			 
			if($element->hasChildNodes()) {
				
				$nodeList = $element->childNodes;
				
			//run through child node list looking for tag type nodes
				for($i = 0; $i < $nodeList->length; $i++) {
					
					$NodeNow = $nodeList->item($i);
					
					if($NodeNow->nodeType == 1) {
						
						$return = true;
						
						break;
					}
				}
			}
			
			return $return;
		}
/**
 * A function used to determine whether or not a node has a child with a specified name
 * 
 * @param $node A DOMNode whose child nodes will be searched
 * @param $name The name of a sought after child node
 * @return bool True if the specified node has a child with the specified name
 */
		private function hasChildWithName($node, $name) {
			
			$return = false;
			
			$nodeList = $node->childNodes;
			
			for($i = 0; $i < $nodeList->length; $i++) {
				
				$curNode = $nodeList->item($i);
				
				if($curNode->nodeType == 1) {
					
					if($curNode->nodeName == $name) {
						
						$return = true;
						
						break;
					}
				}
			}
			
			return $return;
		}
		
/**
 * A function to find the index of the last childNode that is an element (ie, a tag)
 * 
 * @param $nodelist A DOMNodeList object
 * @return int An integer index associated with the last node on the DOMNodeList passed to the function
 */
		private function lastChildElement($nodelist) {
			
			$lastChild = 0;
			
			for($i = 0; $i < $nodelist->length; $i++) {
				
				if($nodelist->item($i)->nodeType == 1)
					$lastChild = $i;
			}
			
			return $lastChild;
		}

/**
 * A function to turn a specicific node in xml into json data and call itself recursively if the node has child elements
 * 
 * @param $_JSON_ A JSON String to build on
 * @param $node An XML DOMNode upon which JSON model will be based
 * @param $listIdentifier A String indicating the list element identifier tag name in the XML model
 * @param $isLastChild true if the $node passed in is the last child of its parent, false otherwise
 * @return String A json string
 */
		private function produceJSON($_JSON_, $node, $listIdentifier, $isLastChild) {
			
			if($this->XMLhasChildElement($node)) {
				
				if($node->nodeName != $listIdentifier)
					$_JSON_ .= "\"".$node->nodeName."\": ";
					
				if($this->hasChildWithName($node, $listIdentifier)) {
					
					$NList = $node->childNodes;
					$_JSON_ .= "[";
					
					for($i = 0; $i < $NList->length; $i++) {
						
						$isLast = ($this->lastChildElement($NList) == $i ? true: false);
						
						if($NList->item($i)->nodeType == 1)
							$_JSON_ = $this->produceJSON($_JSON_, $NList->item($i), $listIdentifier, $isLast);
					}
					$_JSON_ .= "]";
				}
				else {
					
					$NList = $node->childNodes;
					
					$_JSON_ .= "{";
					
					for($i = 0; $i < $NList->length; $i++) {
						
						$isLast = ($this->lastChildElement($NList) == $i ? true: false);
						
						if($NList->item($i)->nodeType == 1)
						//recursive call :-) SO COOL!!
							$_JSON_ = $this->produceJSON($_JSON_, $NList->item($i), $listIdentifier, $isLast);
					}
					$_JSON_ .= "}";
				}
				
				if(!$isLastChild) {
					$_JSON_ .= ", ";
				}
			}
			else {
				
				if($node->nodeName != $listIdentifier) {
					$_JSON_ .= "\"".$node->nodeName."\": ";
				}
				
				$result = $this->getNodeQueryResult($node); 
				
				if(!is_numeric($result))
					$_JSON_ .= "\"".$result."\"";
				else 
					$_JSON_ .= $result;
					
				if(!$isLastChild)
					$_JSON_ .= ", ";
			}
			
			return $_JSON_;			
		}
/** 
 * A function that gets the value from the MySQL database that has been bound to the node, by the registered query, result handler, ect
 * 
 * @param The node for which value is drawn
 * @returns String The value associated with the specified node
 * 
 */	
		private function getNodeQueryResult($_node) {
			
			if(is_object($_node) && method_exists($_node, "hasChildNodes")) {
				
				$mysqli_result = $this->get_attr_val($_node, "dm-query-point");
				$FN_name = $this->get_attr_val($_node, "dm-result-handler");
				$iteration = $this->get_attr_val($_node, "iter");
				
				if($FN_name == "")
					return "";
				
				if($mysqli_result == "" && array_key_exists( $FN_name, $this->_scope->dm_result_handler))
					return $this->_scope->dm_result_handler[$FN_name](null, $iteration);
				
					
				$return ="";
				
				if($iteration != "") {
					
					$return = $this
									->_scope
									->dm_result_handler[$FN_name]($this->mysql_result_registry[$mysqli_result], intval($iteration));
				}
				else {
					
					$return = $this
									->_scope
									->dm_result_handler[$FN_name]($this->mysql_result_registry[$mysqli_result], 0);
				}
					
				return $return;
			}
			else return "";
		}
		
/**
 * converts xml to json taking the name of the tag which is used to denote a list element (eg. parsed as [el1, el2, el3])
 * 
 * @param $NametoParseasList A string indicating the name associated with list elements in the XML model
 * @return String A complete json string!
 */		
		public function _JSON($NametoParseasList="_li") {
			
			$_JSON_ = "{";
			
			$this->rebuildWithDMRepeat($NametoParseasList, "dm-repeat");
			
		//find first node, whether it's last or is a list node
			$_JSON_ = $this->produceJSON($_JSON_, $this->xml_doc->documentElement, $NametoParseasList, true);
				
			$_JSON_ .= "}";
			
			return $_JSON_;
		}
	}
	
?>
