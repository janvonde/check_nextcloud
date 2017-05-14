#!/usr/bin/php
<?php

/***
 *
 * Monitoring plugin to check the status of nextcloud security scan for a given hostname + URI
 *
 * Copyright (c) 2017 Jan Vonde <mail@jan-von.de>
 *
 *
 * Usage: /usr/bin/php ./check_nextcloud.php -H cloud.example.com -u /nextcloud
 *
 *
 * Don't run this check too often. You could run into an API limit on the
 * nextcloud scan server. Once a day is good.
 *
 *
 * For more information visit https://github.com/janvonde/check_nextcloud
 *
 ***/





// get commands passed as arguments
$options = getopt("H:u:");
if (!is_array($options) ) {
  print "There was a problem reading the passed option.\n\n";
  exit(1);
}

if (count($options) != "2") {
  print "check_nextcloud.php - Monitoring plugin to check the status of nextcloud security scan for a given hostname + URI.\n
You need to specify the following parameters:
  -H:  hostname of the nextcloud instance, for example cloud.example.com
  -u:  uri of the nextcloud instance, for example / or /nextcloud  \n\n";
  exit(2);
}

$nchost = trim($options['H']);
$ncuri = trim($options['u']);
$ncurl = $nchost . $ncuri;

// get UUID from scan.nextcloud.com service
$url = 'https://scan.nextcloud.com/api/queue';
$data = array("url" => "$ncurl");
$options = array(
  'http' => array(
    'header'  => "Content-type: application/x-www-form-urlencoded\r\nX-CSRF: true\r\n",
    'method'  => 'POST',
    'content' => http_build_query($data),
  )
);
$postcontext  = stream_context_create($options);
$answer = @file_get_contents($url, false, $postcontext);
if ($answer === FALSE) {
  echo "WARNING: Could not get get UUID for given host $ncurl. Aborting. \n";
  exit (1);
}
$result = json_decode($answer, true);
$uuid = $result['uuid'];



// get information for the uuid
$getcontext = stream_context_create(array(
  'http' => array(
    'timeout' => 3
    )
  )
);
$uuidresult_fetch = @file_get_contents("https://scan.nextcloud.com/api/result/$uuid", false, $getcontext);
if ($uuidresult_fetch === FALSE) {
  echo "WARNING: Could not get information for given host $ncurl. Aborting. \n";
  exit (1);
}
$uuidresult = json_decode($uuidresult_fetch, true);



// if ithe result is older than 24h requeue the host for rescanning
if (strtotime($uuidresult['scannedAt']['date']) <= strtotime('-24 hours')) {
  // use the same parameters from queue call, just change url
  $url = 'https://scan.nextcloud.com/api/requeue';
  $result = json_decode(file_get_contents($url, false, $postcontext), true);
}



// print output for icinga
$rating = $uuidresult['rating'];
$vulns = count($uuidresult['vulnerabilities']);
$lastscan = date("d.m.Y - H:i:s\h", strtotime($uuidresult['scannedAt']['date']));

if ($rating == 5) { $tr = "A+"; }
if ($rating == 4) { $tr = "A"; }
if ($rating == 3) { $tr = "C"; }
if ($rating == 2) { $tr = "D"; }
if ($rating == 1) { $tr = "E"; }
if ($rating == 0) { $tr = "F"; }


if ($rating == 5 || $rating == 4) {
  echo "OK: $tr rating for $ncurl, $vulns vulnerabilities identified, last scan: $lastscan | badrating=0, vulnerabilities=$vulns\n";
  exit(0);
}

if ($rating == 3 || $rating == 2) {
  echo "WARNING: $tr rating for $ncurl, $vulns vulnerabilities identified, last scan: $lastscan. Please see https://scan.nextcloud.com/results/$uuid | badrating=1, vulnerabilities=$vulns\n";
  exit(1);
}


if ($rating == 1 || $rating == 0) {
  echo "CRITICAL: $tr rating for $ncurl, $vulns vulnerabilities identified, last scan: $lastscan. Immediate action required! See https://scan.nextcloud.com/results/$uuid | badrating=2, vulnerabilities=$vulns\n";
  exit(2);
}
?>
