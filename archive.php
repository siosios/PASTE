<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */
session_start();

require_once('config.php');
require_once('includes/functions.php');

// Disable access to archives from non GET requests.
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    http_response_code(405);
    exit('405 Method Not Allowed.');
}

// UTF-8
header('Content-Type: text/html; charset=utf-8');

$date    = date('jS F Y');
$ip      = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');
$con     = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

// Set site originally to private.
$privatesite = 'on';

if (mysqli_connect_errno()) {
    die("Unable to connect to database");
}
$query  = "SELECT * FROM site_info";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $title				= Trim($row['title']);
    $des				= Trim($row['des']);
    $baseurl			= Trim($row['baseurl']);
    $keyword			= Trim($row['keyword']);
    $site_name			= Trim($row['site_name']);
    $email				= Trim($row['email']);
    $twit				= Trim($row['twit']);
    $face				= Trim($row['face']);
    $gplus				= Trim($row['gplus']);
    $ga					= Trim($row['ga']);
    $additional_scripts	= Trim($row['additional_scripts']);
}

// Set theme and language
$query  = "SELECT * FROM interface";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $default_lang  = Trim($row['lang']);
    $default_theme = Trim($row['theme']);
}

require_once("langs/$default_lang");

$p_title = $lang['archive']; // "Pastes Archive";

// Check if IP is banned
if ( is_banned($con, $ip) ) die($lang['banned']); // "You have been banned from ".$site_name;

// Site permissions
$query  = 'SELECT * FROM site_permissions where id = 1 LIMIT 1';
$result = mysqli_query($con, $query);

if ($row = mysqli_fetch_array($result))
	$siteprivate = Trim($row['siteprivate']);

if ($siteprivate == '')
    $privatesite = 'off';

// Logout
if (isset($_GET['logout'])) {
	header('Location: ' . $_SERVER['HTTP_REFERER']);
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    session_destroy();
}

// Page views 
$query = "SELECT @last_id := MAX(id) FROM page_view";

$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $last_id = $row['@last_id := MAX(id)'];
}

if ($last_id) {
    $query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
    $result = mysqli_query($con, $query);

    while ($row = mysqli_fetch_array($result)) {
        $last_date = $row['date'];
    }
}

if ($last_date == $date) {
    if (str_contains($data_ip, $ip)) {
        $query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
        $result = mysqli_query($con, $query);
        
        while ($row = mysqli_fetch_array($result)) {
            $last_tpage = Trim($row['tpage']);
        }
        $last_tpage = $last_tpage + 1;
        
        // IP already exists. Update view count.
        $query = "UPDATE page_view SET tpage=$last_tpage WHERE id=" . Trim($last_id);
        mysqli_query($con, $query);
    } else {
        $query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
        $result = mysqli_query($con, $query);
        
        while ($row = mysqli_fetch_array($result)) {
            $last_tpage  = Trim($row['tpage']);
            $last_tvisit = Trim($row['tvisit']);
        }
        $last_tpage  = $last_tpage + 1;
        $last_tvisit = $last_tvisit + 1;
        
        // Update both tpage and tvisit.
        $query = "UPDATE page_view SET tpage=$last_tpage,tvisit=$last_tvisit WHERE id=" . Trim($last_id);
        mysqli_query($con, $query);
        file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    }
} else {
    // Delete the file and clear data_ip
    unlink("tmp/temp.tdata");
    $data_ip = "";
    
    // New date is created
    $query = "INSERT INTO page_view (date,tpage,tvisit) VALUES ('$date','1','1')";
    mysqli_query($con, $query);
    
    // Update the IP
    file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    
}

$query  = "SELECT * FROM ads WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $text_ads = Trim($row['text_ads']);
    $ads_1    = Trim($row['ads_1']);
    $ads_2    = Trim($row['ads_2']);
    
}
// Theme
require_once('theme/' . $default_theme . '/header.php');
require_once('theme/' . $default_theme . '/archive.php');
require_once('theme/' . $default_theme . '/footer.php');
?>
