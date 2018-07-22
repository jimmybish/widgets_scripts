<?php
/**
 * Created by PhpStorm.
 * User: bishopj
 * Date: 26/02/15
 * Time: 10:16 AM
 */


// Load the url and creds from config.ini.php
$ini_array = parse_ini_file("config.ini.php");
$user = $ini_array['user'];
$pass = $ini_array['password'];
$url = $ini_array['url'];

// Connect and authenticate against the Jira API endpoint
$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $response = curl_exec($ch);
    curl_close($ch);

// Data is received from Jira in JSON. Convert this to PHP arrays.
$issue_list = json_decode($response);

// Grab the ticket number of the top (newest) ticket in the array.
$newestTicket = $issue_list -> issues[0] -> key;

// Get the 'created' time of the newest ticket. It's in UTC, so set that and convert to local time.
$newestTicketTime = new DateTime($issue_list -> issues[0] -> fields -> created, new DateTimeZone('UTC'));
$newestTicketTime -> setTimezone(new DateTimeZone('Australia/Hobart'));

// Filter out the ticket list by copying any tickets where the 'created' and 'updated' times match into a new array called $newtickets.
// Maybe I should have gone the other way and removed entries from $issues_list where times don't match, but meh. It is what it is.
$newTickets = array();
foreach ($issue_list -> issues as $issue) {
    $createdDate = new DateTime($issue -> fields -> created);
    $updatedDate = new DateTime($issue -> fields -> updated);

    if ($createdDate == $updatedDate)
        array_push($newTickets, $issue);
    }
$unhandledCount = count($newTickets);

// Cycle through the above-created $newtickets array and grab all their ticket numbers and titles. Save this in another array
// that will be passed through the JSON feed to ajax.js.
$ticketList = array();
foreach ($newTickets as $newTicket) {
    $key = $newTicket -> key;
    $summary = $newTicket -> fields -> summary;
    array_push($ticketList, "<li>" . $key . " - " . $summary . "</li>");
}

// Now format to JSON and return the results. Done!
$resultsArray = array (
    'Newest' => $newestTicket,
    'NewestTime' => $newestTicketTime -> format('H:i:s'),
    'Unhandled' => $unhandledCount,
    'List' => $ticketList);
echo json_encode($resultsArray);