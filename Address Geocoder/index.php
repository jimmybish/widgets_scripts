<?php

/*
PHP Script to poll the Google Maps API and populate it with coordinates to be used in 
an internal Oracle APEX Application.
This will use as many Google API keys as you can throw at it, to get around
the 2500 calls per day limitation Google imposes on free accounts.

It's a bit of a slap-together job, but it works!


Requirements:
- PHP 5.4+
- OCI8 for PHP
- Oracle Instant Client 12.1
- The config and queries files in the ./inc directory


Handy References:
http://antoine.hordez.fr/2012/09/30/howto-install-oracle-oci8-on-rhel-centos-fedora/
http://php.net/manual/en/oci8.examples.php

Instructions:
- Install the required packages
- Update Proxy and API details in config.ini.php
- Run in a web browser if you want to cycle until completion, or via command line
  to run once (or schedule via cron)
- If you need to double check OCI8 is installed correctly, create and open phpinfo.php and search for 'oci8'
  to see if the module is loaded


v1.0 - James Bishop - Initial run
*/


// Contains usernames, passwords, connection strings and API keys
$config = parse_ini_file("./inc/config.ini.php", true);

// Contains the APEX queries
include "./inc/queries.php";


$oraUser = $config['db']['User'];
$oraPass = $config['db']['Pass'];
$connectString = $config['db']['connectString'];

$apikeys = $config['api_keys']['keys'];
$rand = rand(0,count($apikeys));
$apikey = $apikeys[$rand];

// Edit the $rowNum value to change the number of results processed
$rowNum =  "10";


// Connect to the Database
$conn = oci_connect($oraUser,$oraPass,$connectString);

// If any DB connection errors, log them to /var/log/httpd/error_log
if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}



// Count how many addresses need updating
$stid = oci_parse($conn, $addrCount);
oci_define_by_name($stid, 'NUM', $addressesCount);
oci_execute($stid);

while (oci_fetch($stid)) {
    echo "There are $addressesCount addresses to be processed";
}

// If there are more than 0, refresh the page in 2 seconds
// Be careful with balancing this with the rows returned. If the api calls take too long, it'll trip over itself.
if ($addressesCount > 0) {
    $url1 = $_SERVER['REQUEST_URI'];
    header("Refresh: 2; URL=$url1");

    $addrQuery = $addrQuery . $rowNum;

    // Execute the address query
    oci_free_statement($stid);
    $stid = oci_parse($conn, $addrQuery);
    oci_execute($stid);

    echo "<p>";
    echo "Using API Key: $apikey";
    echo "<p>";
    echo "Listing the first $rowNum:";
    echo "<p>";
    echo "<table border='1'>\n";
    echo "<th>Address<th>Coordinates<th>Status<tr>";

    // Loop through each returned row and perform actions
    // This doesn't run in an async thread, so will appear to freeze while doing everything.
    // Wait for a result before killing the script. I might make it better, but probably not.

    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {

        $premID = $row['CHAR_PREM_ID'];
        $address = $row['GEO_CODE'] . $row['ADDRESS2'] . " " . $row['TYPE'] . " " . $row['CITY'];
        $postcode = $row['POSTAL'];

        $latlong = geoQuery($apikey, $address, $postcode);

        unset($insertError);
        if ($latlong['lat']) {
            $insert = insertRow($conn, $premID, $latlong['lat'], $latlong['lng']);
        }
        else {
            $insert = "Google Maps Call Failed :( ";
            $insertError = 'true';
        }

        echo "    <td>". $address . "<td>" . $latlong['lat'] . ", " . $latlong['lng'];
        if ($insertError) {
            echo "<td><strong>" . $insert . "</strong><td>" . $latlong['url'];
        }
        else {
            echo "<td>" . $insert;
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}


// Count how many suburbs need updating
oci_free_statement($stid);
$stid = oci_parse($conn, $subCount);
oci_define_by_name($stid, 'NUM', $suburbsCount);
oci_execute($stid);

echo "<p>";

while (oci_fetch($stid)) {
    echo "There are $suburbsCount suburbs to be processed";
}

if ($suburbsCount > 0) {
    // Prepare for the suburbs query
    unset($latlong);
    oci_free_statement($stid);

    $subQuery = $subQuery . $rowNum;
    $stid = oci_parse($conn, $subQuery);
    oci_execute($stid);


    echo "<p>";
    echo "<table border='1'>\n";
    echo "<th>Suburb<th>Postcode<th>Coordinates<th>Status<th>URL<tr>";


    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {

        $suburb = $row['SUBURB'];
        $postcode = $row['POSTCODE'];

        $latlong = geoQuery($apikey, $suburb, $postcode);

	unset($insertError);
        if ($latlong['lat']) {
            $insert = updateRow($conn, $suburb, $latlong['lat'], $latlong['lng']);
        }
        else {
            $insert = "Google Maps Call Failed :( ";
	    $insertError = 'true';
        }


        echo "    <td>". $suburb . "<td>" . $postcode . "<td>" . $latlong['lat'] . ", " . $latlong['lng'];

	if ($insertError) {
	    echo "<td><strong>" . $insert . "</strong><td>" . $latlong['url'];
	}
	else {
	    echo "<td>" . $insert;
	}
        echo "</tr>\n";
    }
    echo "</table>\n";
}

oci_free_statement($stid);
oci_close($conn);


// The end



function insertRow($conn, $premID, $lat, $lng) {

    $stid = oci_parse($conn, 'INSERT INTO APEX."106_PREM_LATLONG" (PREM_ID, LATITUDE, LONGITUDE) VALUES (:premID, :lat, :lng)');
    // http://php.net/manual/en/oci8.examples.php
   
    oci_bind_by_name($stid, ':premID', $premID);
    oci_bind_by_name($stid, ':lat', $lat);
    oci_bind_by_name($stid, ':lng', $lng);

    $ins = oci_execute($stid);

    if ($ins) {
	$result = "Row Inserted";
    }
    else {
	$error = oci_error($stid);
	$result = $error['message'];
	$result .= "\n<pre>\n";
	$result .= $error['sqltext'];
    }
    oci_free_statement($stid);
    return $result;
}


function updateRow($conn, $suburb, $lat, $lng) {

   $stid = oci_parse($conn, 'UPDATE APEX."106_SUBURBS" SET LATITUDE=:lat, LONGITUDE=:lng WHERE SUBURB=:suburb');
    // http://php.net/manual/en/oci8.examples.php

    oci_bind_by_name($stid, ':suburb', $suburb);
    oci_bind_by_name($stid, ':lat', $lat);
    oci_bind_by_name($stid, ':lng', $lng);

    $ins = oci_execute($stid);

    if ($ins) {
        $result = "Row Updated";
    }
    else {
        $error = oci_error($stid);
        $result = $error['message'];
        $result .= "\n<pre>\n";
        $result .= $error['sqltext'];
    }
    oci_free_statement($stid);
    return $result;

}



function geoQuery($apikey, $address, $postcode) {

    // Create the url to pass to the api

    $postcode = explode(" ", $postcode);
    $postcode = $postcode[0];
    $address = $address . " " . $postcode . ", TAS, AUSTRALIA";
    $escapedAddress = preg_replace("/,[^ ]/",", ",$address);
    $escapedAddress = urlencode($escapedAddress);

    $url = "https://maps.googleapis.com/maps/api/geocode/json?key=$apikey&address=$escapedAddress";

    $response = json_decode(doCurl($url), true);
    $geometry = $response['results'][0]['geometry'];

    $latlong = array(
	'url' => $url,
	'lat' => $geometry['location']['lat'],
	'lng' => $geometry['location']['lng'],
    	);
    return $latlong;
    
}



function doCurl($url) {

    $config = parse_ini_file("./inc/config.ini.php", true);
    $proxy = $config['proxy']['Url'];
    $proxyCreds = $config['proxy']['User'] . ":" . $config['proxy']['Pass'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array( "Accept-Encoding: gzip, deflate", "Accept-Language: en-US"));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // connect timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout in seconds
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyCreds);

    $exec = curl_exec($ch);

    if (curl_errno($ch)) {
	return curl_error($ch);
    }
    curl_close($ch);

    return $exec;
}

?>

