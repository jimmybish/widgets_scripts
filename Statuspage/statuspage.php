<?php

//
// Grabs the latest status from StatusPage and saves it to a local json file
// to be read by statuspage.js (ie; the internal end users).
// Windows Scheduled Task runs this every 1 minute.
//
// v0.1 / 02/11/17 - James Bishop - Initial Build
// v0.2 / 06/11/17 - James Bishop - Changed URL to summary.json for more data




// StatusPage Endpoint
// You can get the API Key and Page ID from the Manage Account page in StatusPage
$apikey = '';
$pageid = '';
$summaryUrl = 'https://' . $pageid . '.statuspage.io/api/v2/summary.json?api_key=' . $apikey;


function Connect($url) {
	$ch = curl_init();
		if (FALSE === $ch) {
			echo 'Failed to initialise';
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Behind a proxy? Enter details here
		curl_setopt($ch, CURLOPT_PROXY, ''); // http://proxyURL:port
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, ''); // user:password
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		$response = curl_exec($ch);
		if (FALSE === $response) {
			var_dump(curl_error($ch), curl_errno($ch));
		}
		curl_close($ch);	
	return $response;
}
$output = Connect($summaryUrl);
echo $output;
file_put_contents( dirname(__FILE__) . '/statuspage.json', $output);
	
?>