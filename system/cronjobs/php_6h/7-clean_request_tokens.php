<?php

	$db2->query('DELETE FROM oauth_request_token WHERE (time_stamp+1000)<'.time());
	
?>