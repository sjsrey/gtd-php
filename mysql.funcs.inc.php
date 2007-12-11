<?php
function safeIntoDB(&$value,$key=NULL) {
	// don't clean arrays - clean individual strings/values
	if (is_array($value)) {
		foreach ($value as $key=>$string) $value[$key] = safeIntoDB($string,$key);
		return $value;
	} else {
		// don't clean filters - we've cleaned those separately in the sqlparts function
		if (strpos($key,'filterquery')===false 
			&& !preg_match("/^'\d\d\d\d-\d\d-\d\d'$/",$value) ) // and don't clean dates
			{
			if ( get_magic_quotes_gpc() && !empty($value) && is_string($value) )
				$value = stripslashes($value);
			if(version_compare(phpversion(),"4.3.0",'<'))
				$value = mysql_escape_string($value);
			else  
				$value = mysql_real_escape_string($value);
		} else { return $value;}
		return $value;
	}
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
