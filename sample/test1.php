<?php

	include("../Damo.php");

	$myscope = new Scope();

	$myscope->perm_key(["key1"], [new Permission_key("**host address**", "**username**", "**password**")]);

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

	$myscope->query(["query1"], ["SELECT * FROM LetsTestDaMo.UserInformation WHERE max_score > ? ORDER BY max_score DESC"])
					 ->bind_types(["score_type"], ["i"])
					 ->bind_values(["score_limit"], [[5000]])
					 ->result_handler(["rh1", "rh2"], [$ref_to_function1, $ref_to_function2]);

	$damo_instance = new Damo($myscope, "test1.xml");

	echo $damo_instance->_JSON();

?>

