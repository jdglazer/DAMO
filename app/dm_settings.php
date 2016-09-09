<?php
/*DEVELOPER NOTES:
 *----------------
 * $_SETTINGS - An extendible array allowing for framework settings to be set. This should be made full visible to all other
 *		parts of the program
 *
 * 	ERR_REPORT - determines how the program should report errors. If it is set to "admin_debug", it will put out s
 * 	specific errors from the part of the program causing problems. Otherwise it will put out the value stored at this setting.
 *	
 *	CONNECTION_LIMIT - limits the number of connections a user can make using a single instance of the Permission key object
 */
	$_SETTINGS = array(
				"ERR_REPORT" => "admin_debug",
				"CONNECTION_LIMIT" => 100
				);
/*DEVELOPER NOTES:
 *----------------
 * This is an array of functions that tells the DMRepeat Directive how to copy specific attributes into the repeated 
 * DOM elements. The keys are the attribute names and the value is a function that returns a two part indexed array 
 * with the first index corresponding to an attribute name and the next an attribute value. If the function returns 
 * false (or an invalid value for the attribute) the attribute will not be copied to the repeated elements. NOTE: A 
 * change of this code requires the corresponding XML parser code to be changed in order for it to have an effect.
 *
 *Example 
 *   Repeated element in xml:
 *   	<parent dm-repeat='2'>
 *	     <age dm-name='identifier'> 
 *	...
 *   After going through repeat engine:
 *      <!-- Note how dm-name was changed to dm-class and maintained it's attribute value -->
 *      <parent>
 *	     <_li iter='0'>
 *	     	<age dm-class='identifier'>...</age>
 *	     </_li>
 *	     <_li>
 *           	<age dm-class='identifier'>...</age>
 *           </_li>
 *      </parent>
 *      
 */
	$_DmRepeatSpecialInstructions = [
				"dm-name" => function($attr_val, $i) {	return array("dm-class", $attr_val); },
				"dm-repeat" => function() { return -1; }
				];
?>
