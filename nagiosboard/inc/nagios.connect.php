<?php
/**
 * Created by PhpStorm.
 * User: bishopj
 * Date: 6/03/15
 * Time: 10:02 AM
 */

// Get the creds from the ini file
$ini_array = parse_ini_file("config.ini.php");
$user = $ini_array['user'];
$pass = $ini_array['pass'];

// Get if values have been passed to determine the desired endpoint
if(isset($_GET["env"])) {

    if ($_GET["env"] === "office") {
        $env = $ini_array['office'];
    } else {
        $env = $ini_array['prod'];
    }
    // Room for additional endpoints
} else {
    // Default to Prod
    $env = $ini_array['prod'];
}

$path = '/cgi-bin/status.cgi?host=all&type=detail&hoststatustypes=3&serviceprops=8234&servicestatustypes=28';
$url = $env . $path;

$curl = curl_init();
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_USERPWD, $user . ":" . $pass);
curl_setopt($curl, CURLOPT_REFERER, $base);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($curl);
curl_close($curl);



$headingRegex = "/<TD align=left valign=center CLASS=\'(.*?)\'><A HREF='(.*?)' title='(.*?)'>(.*?)<\/A><\/TD>/";
$durationRegex = "/<TD CLASS=\'(status.*?)\' (?:nowrap)>(.*?)<\/TD>/";
$contentRegex = "/<TD CLASS=\'(status.*?)\'>(.*?)<\/TD>/";

preg_match_all($headingRegex, $response, $headingMatches);
preg_match_all($contentRegex, $response, $contentMatches);
preg_match_all($durationRegex, $response, $durationMatches);


$hostinfo = array();

$hostContent = array_chunk($contentMatches[2], 3);
$hostDuration = array_chunk($durationMatches[2], 3);

$json = array();

foreach ($headingMatches[4] as $i => $host) {
    $hostentry = array();
    $hostentry['Hostname'] = $host;
    $hostentry['Level'] = $hostContent[$i][0];
    $hostentry['Info'] = $hostContent[$i][2];
    // $hostentry['Duration'] = $hostDuration[$i][2];


    $jsonentry = json_encode($hostentry);
    array_push($json, $hostentry);

}

$encoded = json_encode($json);
echo $encoded;