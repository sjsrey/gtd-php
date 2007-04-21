<?php
function safeIntoDB($value) {
	if (is_array($value)) {
		$value = array_map('safeIntoDB', $value);
	} else {
		if ( get_magic_quotes_gpc() && !empty($value) && is_string($value) ) $value = stripslashes($value);
		$value=mysql_real_escape_string($value);
		// can be a problem with escape strings in PHP below 5.1.1, so we may need to do something here:
		// if ( version_compare(PHP_VERSION,'5.1.1','<') ) ; 
	}
	return $value;
}
?>