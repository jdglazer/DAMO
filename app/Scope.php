<?php
//include "Permission_key.php";
/**
 * A class that packages and delivers to the xml parsing and MySQL querying engine all of the following:
 * 	MySQL queries, database permission keys, values to bind to query, bind value types, and result handler functions
 * 
 * @author Glazer, Joshua D.
 */
	class Scope {
	//arrays for php-side storage of all query and result handling information
		public $dm_queries = [];
		public $dm_perm_keys = [];
		public $dm_bind_types = [];
		public $dm_bind_values = [];
		public $dm_result_handler = [];
		
/**
 * A function to verify that two inputs are arrays and are the same length as eachother
 * 
 * @return boolean true if two inputs are arrays of the same length, false otherwise
 */
		private function check_input($one, $two) {
			if(!is_array($one) || !is_array($two)) 
				return false; 
			if(sizeof($one) != sizeof($two)) 
				return false; 
		//Not reached if above checks fail
			return true;
		}
		
/**
 * Registers to scope a set of queries and assosciated names/ids
 * 
 * @param $names An indexed array of names to correspond, in order, to queries passed to $queries argument
 * @param $queries An indexed array of valid MySQL queries to be referenced in xml layout by associated names in $names argument
 * @return Scope The parent instance of the Scope object (allows for function chaining )
 */
		public function query($names, $queries) {
			
			global $_SETTINGS;
		//basic input validity testing
			if(!$this->check_input($names, $queries)) {
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "scope failure" );
			}
			
			foreach($names as $key => $name) {
			//makes sure values to be registered are strings
				if(!is_string($name) || !is_string($queries[$key])) 
					continue;
					
				$this->dm_queries[$name] = $queries[$key];
			}
			return $this;
		}
		
/**
 * Registers to scope a set of permission key objects and assosciated names/ids
 * 
 * @param $names An indexed array of names to correspond, in order, to permission keys passed to $keys argument
 * @param $keys An indexed array of permission key objects to be referenced in xml layout by associated names in $names argument
 * @return Scope The parent instance of the Scope object (allows for function chaining)
 */
		public function perm_key($names, $keys) {
			
			global $_SETTINGS;
		//basic input validity testing
			if(!$this->check_input($names, $keys)) { 
				
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "scope failure" );
			}
			
			foreach($names as $key => $name) {
			//checks to make sure values from array are valid
				if(!is_string($name) || !is_object($keys[$key])) 
					continue;
				if(get_class($keys[$key]) != "Permission_key") 
					continue;
					
				$this->dm_perm_keys[$name] = $keys[$key];
			}
			return $this;
		}
		
/**
 * (Soon to be deprecated )
 * Registers to scope a set of bind value type strings (eg. 'ssi' or 'issi') and assosciated names/ids
 * 
 * @param $names An indexed array of names to correspond, in order, to bind types strings passed to $bind_strs argument
 * @param $bind_strs An indexed array of strings defining bind types referenced in xml layout by associated names in $names argument. 
 * 			Eg. 'ssi' represents String, String, integer
 * @return Scope The parent instance of the Scope object (allows for function chaining)
 */
 //DEVELOPER NOTE: Deprecate this function and replace with auto-detection functionality for bind values
		public function  bind_types($names, $bind_strs) {
			
			global $_SETTINGS;
		//basic input validity testing
			if(!$this->check_input($names, $bind_strs)) {
								
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "scope failure" );
			}
			foreach($names as $key => $name) {
			//Advanced testing for type validity of registration values
				if(!is_string($name) || !is_string($bind_strs[$key])) continue;
				$this->dm_bind_types[$name] = $bind_strs[$key];
			}
			return $this;
		}
/**
 * Registers to scope a set of values to bind to queries (safe guard against sql injection) and assosciated names/ids
 * 
 * @param $names An indexed array of names to correspond, in order, to values to be bound to queries passed to $bind_values argument
 * @param $keys An indexed array of values to bind to queries committed to MySQL database (referenced in xml )
 * @return Scope The parent instance of the Scope object (allows for function chaining)
 */
		public function bind_values($names, $bind_values) {
		
			global $_SETTINGS;
			
		//initial argument validity testing
			if(!$this->check_input($names, $bind_values)) {
				
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "scope failure" );
			}
			
			foreach($names as $key => $name) {
			//advanced testing of type validity of values from function arguments
				if(!is_string($name) || (!is_string($bind_values[$key]) && !is_array($bind_values[$key])))
					continue;
				$this->dm_bind_values[$name] = $bind_values[$key];
			}
			return $this;
		}
/**
  * Registers to scope a function or set of function and associated name(s)/id(s)
  * 
  * @param $name A string name or indexed array of names to correspond, in order, to function(s) from the $function argument
  * @param $function A function reference or an indexed array of function referenced that take(s) an two arguments, an array of results and an iteration number
  * @return Scope The parent instance of the Scope object (allows for function chaining)
  */
		public function result_handler($name, $function) {
			
			global $_SETTINGS;
			
		//checks to make sure argument one and two are a string and function, respectively, or an array and array,
		//respectively
			if(!(is_string($name) || is_array($name)) || !(is_callable($function) || is_array($function))) {
				
				if($_SETTINGS["ERR_REPORT"] == "admin_debug")
					die(_err_report($this, "invalid argument(s)"));
				else
					throw new Exception( "scope failure" );
			}
			
		//if arguments types are String and function
			if(is_callable($function))
				$this->dm_result_handler[$name] = $function;
				
		//if argument types are arrays
			else {
				foreach($function as $key=>$func) {
					$this->dm_result_handler[$name[$key]] = $func;
				}
			}
			
			return $this;
		}
		
/**
 * A function that clears all registered permission keys, queries, bind valued, bind type string, and result handling functions
 * 
 * @return Scope The parent instance of the Scope object (allows for function chaining)
 */
		public function clr() {
			$this->dm_queries = [];
			$this->dm_perm_keys = [];
			$this->dm_bind_types = [];
			$this->dm_bind_values = [];
			$this->dm_result_handler = [];
			
			return $this;
		}
	}
	
//Creates an instance of the Scope object names $scope for framework user. Saves the user a line of code!
	$scope = new Scope();
	
/** TEST CODE (Relies on specific database structure):
 *----------------------------------------------------
 * NOTE: un-comment include statement at the top of the document to run test code.
 * 
  $scope->perm_key(["key1"], [new Permission_key("**host address**", "**password**", "**password**")]);

    $ref_to_function1 = function($result, $i) {
         if(isset($result[$i]["user_name"]) )
              return $result[$i]["user_name"];
         else
              return "";
    };

    $ref_to_function2 = function($result, $i) {
           if(isset($result[$i]["max_score"]) )
              return $result[$i]["max_score"];
         else
              return "";
    };

    $scope->query(["query1"], ["SELECT * FROM LetsTestDamo.UserInformation WHERE max_score > ? ORDER BY max_score DESC"])
                     ->bind_types(["score_type"], ["i"])
                     ->bind_values(["score_limit"], [[5000]])
                     ->result_handler(["rh1", "rh2"], [$ref_to_function1, $ref_to_function2]);
  */

?>
