<?php
	require_once( $_SERVER["DOCUMENT_ROOT"]."app/XMLToJSON.php");
	
/**
 * A class encasing all the functionality of the DaMo framework
 * 
 * @author Glazer, Joshua D.
 */
	class Damo extends XMLToJSON {
		
/**
 * Takes an instance of the Scope object an XML model as a String or file resource to open up framework functionality
 * 
 * @param $Scope An instance of the Scope object containing all data necessary for making and processing queries
 * @param $_xml_doc The XML DOM around which JSON end-product will be based
 */
		function __construct($_scope_, $_xml_source) {
			
			parent::__construct($_scope_, $_xml_source);
		}
	}
?>
