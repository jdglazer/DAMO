<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'DM_format_parser_tools.php');
	require_once($_SERVER['DOCUMENT_ROOT'].'Scope.php');
	
/**
 * A class to leverage its parent class' functionality to perform all necessary initialization tasks for framework
 * @author Glazer, Joshua D.
 * 
 */
	class DM_initialize extends DM_format_parser_tools {
		
/**
 * Constructor that performs all initialization including all queries and expansion of xml model at nodes containing dm-repeat directive
 * 
 * @param $Scope An instance of the Scope object containing all data necessary for making and processing queries
 * @param $_xml_doc The XML DOM around which JSON end-product will be based
 */		
		function __construct($Scope, $_xml_doc) {
			
			$this->_scope = $Scope;
			
			parent::__construct($_xml_doc);
			
			$this->execute_queries();
		}
		
//DEVELOPER NOTE: This function should be re-written to leverage the functionality execute_query function
/** 
 * A function that performs MySQL queries at each tag containing a dm-query-point property present and set.
 * It then precedes to register all query results as arrays in a registry property (array) called 
 * mysql_result_registry; the tag under which the result is registered value of the dm-query-point property as the key
*/
		protected function execute_queries() {
			
			foreach($this->mysql_result_registry as $key=>$result) {
				
			//gets all query points
				$nodes = $this->getNodeByAttrValue("dm-query-point", $key);
				
				foreach($nodes as $node) {
					
					$Node = $this->nodeList[$node];
				//DEVELOPER NOTE: add code to change error handling as per the ERR_REPORT setting in global $_SETTINGS array
					if(!is_object($Node)) 
						die(_err_report($this, "error parsing node"));
					
				//gets all relevant query information from xml model for specific query point
					$PK_name = $this->get_attr_val($Node, "dm-perm-key");
					$Q_name =  $this->get_attr_val($Node, "dm-query");
					$BS_name = $this->get_attr_val($Node, "dm-bind-str");
					$BA_name = $this->get_attr_val($Node, "dm-bind-array");
					$bind_vals;
					
				//aliasing _scope member under shorter name
					$s = $this->_scope;
					
				//DEVELOPER NOTE: add code to change error handling as per the ERR_REPORT setting in global $_SETTINGS array
					if(!array_key_exists($PK_name, $s->dm_perm_keys))
						die(_err_report($this, "perm key indicated in xml not registered in scope"));
						
					if(!array_key_exists($Q_name, $s->dm_queries))
						die(_err_report($this, "query indicated in xml not registered in scope"));
						
					if(!array_key_exists($BS_name, $s->dm_bind_types))
						die(_err_report($this, "bind type string indicated in xml not registered in scope"));
						
					if(!array_key_exists($BA_name, $s->dm_bind_values))
						die(_err_report($this, "bind array indicdated in xml not registered in scope"));
					
					if(	!is_array($s->dm_bind_values[$BA_name]))
						$bind_vals = array($s->dm_bind_values[$BA_name]);
					else
						$bind_vals = $s->dm_bind_values[$BA_name];
						
				//performs MySQL query
					$this->mysql_result_registry[$key] = $this->mysql_query(
																			$s->dm_perm_keys[$PK_name], 
																			$s->dm_queries[$Q_name], 
																			$s->dm_bind_types[$BS_name], 
																			$bind_vals
																			);
				}
			}
		}
/**
 * Executes a single query at dm-query-point specified by the function's one argument
 * 
 * @param The value to the dm-query-point attribute of the point of interest
 */	
		protected function execute_query($dm_query_point) {
			
			$nodes = $this->getNodeByAttrValue("dm-query-point", $dm_query_point);
			$Node = $this->nodeList[$nodes[0]];
			
			if(!is_object($Node)) die(_err_report($this, "error parsing node"));
			
			$PK_name = $this->get_attr_val($Node, "dm-perm-key");
			$Q_name =  $this->get_attr_val($Node, "dm-query");
			$BS_name = $this->get_attr_val($Node, "dm-bind-str");
			$BA_name = $this->get_attr_val($Node, "dm-bind-array");
			
		//aliasing scope member under shorter name
			$s = $this->_scope;
			
		//DEVELOPER NOTE: add code to change error handling as per the ERR_REPORT setting in global $_SETTINGS array
			if(!array_key_exists($PK_name, $s->dm_perm_keys))
				die(_err_report($this, "perm key indicated in xml not registered in scope"));
				
			if(!array_key_exists($Q_name, $s->dm_queries))
				die(_err_report($this, "query indicated in xml not registered in scope"));
				
			if(!array_key_exists($BS_name, $s->dm_bind_types))
				die(_err_report($this, "bind type string indicated in xml not registered in scope"));
				
			if(!array_key_exists($BA_name, $s->dm_bind_values))
				die(_err_report($this, "bind array indicdated in xml not registered in scope"));
			

			$this->mysql_result_registry[$dm_query_point] = $this->mysql_query(
																				$s->dm_perm_keys[$PK_name], 
																				$s->dm_queries[$Q_name], 
																				$s->dm_bind_types[$BS_name], 
																				array($s->dm_bind_values[$BA_name])
																			);
		}
/**
 * A function designed to pull out the data returned from the background database based on the query, permisssion key, bind types, 
 * bind values, and result handlers valid for the node containing the dm-name value passed in as an argument.
 * 
 * @param $dm_name The dm-name attribute value of the node from which the extracted MySQL data is taken
 * @param $iter If the dm-name attribute was turned to a dm-class by the expansion of the model under a dm-repeat this variable specifies the index of the instance of the class of interest
 * @return The value from the database associated with the node containing the dm-name value specified in $dm_name argument
 */
		public function extract_result($dm_name, $iter="default") {
			
			$node = null; 
			$Node = null; 
			$num_node = 0;
			
			if(!is_string($iter)) {
				$node = $this->getNodeByAttrValue("dm-class", $dm_name);
				$num_node = floor(abs($iter));
			}
			else {
				$node = $this->getNodeByAttrValue("dm-name", $dm_name);
			}
		
		//DEVELOPER NOTE: add code to change error handling as per the ERR_REPORT setting in global $_SETTINGS array
			if($num_node >= sizeof($node) || !is_array($node)) 
				die(_err_report($this, "referencing an XML node which does not exist. Check arguments"));
		
		//DEVELOPER NOTE: could $num_node be out of bounds for $node array? could $node[$num_node] be out of bounds for nodeList
			$Node = $this->nodeList[$node[$num_node]];
			
			$QP_name = $this->get_attr_val($Node, "dm-query-point");
			$FN_name = $this->get_attr_val($Node, "dm-result-handler");
			
		//DEVELOPER NOTE: add code to change error handling as per the ERR_REPORT setting in global $_SETTINGS array
			if(!array_key_exists($FN_name, $this->_scope->dm_result_handler))
				die(_err_report($this, "result handler from xml markup not registered in the scope"));
				
			if(!array_key_exists($QP_name, $this->mysql_result_registry))
				die(_err_report($this, "no result registered for xml query point used "));
				
			return $this->_scope->dm_result_handler[$FN_name]( 
																$this->mysql_result_registry[$QP_name], 
																$num_node 
															);
		}
	}
//STEPS TO MOVE DATA FROM MYSQL DATABASE

//1. register all necessary information to $scope with easy-to-work-with names							
//2. initialize DM_initialize object by injecting into it the $scope object and the file address to or string form of an xml file.
//	 The file should appropriately use names registered in the scope
//3. extract the result of the query with the extract_result method and the dm-name of the node for which you would like to extract the 
//   result

?>
