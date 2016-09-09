<?php
	
	require_once($_SERVER["DOCUMENT_ROOT"]."dm_settings.php");
	
/**
 * A function that performs the general task of error reporting
 * 
 * @param $class The name of the parent class of the function in which the error occured
 * @param $error_msg A specific error message
 * @return String error messafe with class and function location
 */
 
 //DEVELOPER NOTE: move this to either the settings file or a global function library file
	function _err_report($class, $error_msg) {
		global $_SETTINGS;
		if($_SETTINGS["ERR_REPORT"] == "admin_debug")
			return "ERROR! ".get_class($class).'::'.debug_backtrace()[1]['function'].'() says: '.$error_msg; 
		else return $_SETTINGS["err_report"];
	}
/**
 * A class designed to store MySQL Database access information including hostname, username, and password.
 * The class also allows for a limit value on the number of connection made to be stored.
 * @author Glazer, Joshua D
 */ 
	class Permission_key {

		private $mysql_host;
		private $mysql_user;
		private $mysql_user_password;
		
		private $connection_limit;
		private $super_link;
		
		private $method_locations;
		
/**
 * Constructor which can take the host address for the mysql database and the username and password for the valid user
 * 
 * @param $MysqlHost The host address for the MySQL database
 * @param $MysqlUser The username for the MySQL database user
 * @param $MysqlUserPassword The password for the mysql database user
 */
		function __construct($MysqlHost = "", $MysqlUser = "", $MysqlUserPassword = "") {
			$this->mysql_host = $MysqlHost;
			$this->mysql_user = $MysqlUser;
			$this->mysql_user_password = $MysqlUserPassword;
		//keeps number of times connection is returned in order to limit it
			$this->connection_limit = 0;
		//super administrator link necessary to change and configure privileges      
			$this->super_link = NULL;             
		}
		
/**
 * (Soon to be deprecated )
 * Unlinks the super link
 */
		function __destructor() {
			if(is_object($this->super_link)) $this->super_unlinker();            
		}
/**
 * Makes a connection to the MySQL database and return the connection
 * 
 * @return mysqli a mysqli connection the database
 * @throws Exception This happens if the connection fails
 */
		public function get_conn() {
			global $_SETTINGS;
			if($this->connection_limit < $_SETTINGS["CONNECTION_LIMIT"]) {
				
				$conn_attempt = new mysqli($this->mysql_host, $this->mysql_user, $this->mysql_user_password);
				if(!$conn_attempt->connect_errno) {
					$this->connection_limit++;
					return $conn_attempt;
				}
			//if mysqli returns an error
				else {
					if( $_SETTINGS["ERR_REPORT"] == "admin_debug" )
						die(_err_report($this, "connection to database failed"));
					else
						throw new Exception("Failed to connect to ".$this->MysqlHost);
				}
			}
		//if the connection limit (as set in setting file) is exceeded
			else
				if( $_SETTINGS["ERR_REPORT"] == "admin_debug" )
					die(_err_report($this, "connection limit exceeded"));
				else
					throw new Exception( "Failed to connect to ".$this->MysqlHost);
		}
/**
 * Provides the username for the specific MySQL user registered to the object
 * 
 * @return the mysql user's username
 */
		public function get_user() {
			return $this->mysql_user;
		}
/*		
 * Provides the host address for the specific MySQL database
 * 
 * @return the mysql database host address
 */
		public function get_host() {
			return $this->mysql_host;
		}
		
/**
 * (Soon to be deprecated)
 * Designed to make a super user link
 * 
 * @param $super_user The username for the super user
 * @param $super_password The password for the super user
 * @return boolean true if the superuser connection was made, false otherwise
 */
		private function super_linker($super_user, $super_password) {
			$connect_success = false;
			$initial_link = new mysqli($this->mysql_host, $super_user, $super_password);
			if(!$initial_link->connect_errno) {
				$this->super_link = $initial_link;
				$connect_success = true;
			}
			return $connect_success;
		}  
/**
 * ( Soon to be deprecated )
 * 
 * closes and deletes super link
 */
		private function super_unlinker() {
			if(is_object($this->super_link)) {
				if(method_exists($this->super_link, 'select_db')) {
					$this->super_link->close();
				}
			}
			$this->super_link = NULL;
		} 
	   
	}
	
/*
 * TEST CODE:
 * ----------
	$perm = new Permission_key( "**host address**", "**username**", "**password**" );
	
	$conn = $perm->get_conn();
	
	if ( $conn ) 
	    echo "connection made";
	else
	    echo "connection not made";
*/
?>
