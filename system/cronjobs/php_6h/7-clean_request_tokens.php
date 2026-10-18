<?php

	$db2->query('DELETE FROM oauth_request_token WHERE (timestamp+1000)<'.time());
	
?>