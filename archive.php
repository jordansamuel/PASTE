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
include_once( 'includes/db.php' );
require_once('includes/functions.php');

// UTF-8
header('Content-Type: text/html; charset=utf-8');

global $pastedb;

$date    = date('jS F Y');
$ip      = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');


$query  = "SELECT * FROM site_info";
$result = $pastedb->get_row( $query );

$title       = $result->title;
$des         = $result->des;
$baseurl     = $result->baseurl;
$keyword     = $result->keyword;
$site_name   = $result->site_name;
$email       = $result->email;
$twit        = $result->twit;
$face        = $result->face;
$gplus       = $result->gplus;
$ga          = $result->ga;
$additional_scripts  = $result->additional_scripts;

// Set theme and language
$query  = "SELECT * FROM interface";
$result = $pastedb->get_row( $query );

$default_lang  = $result->lang;
$default_theme = $result->theme;

require_once("langs/$default_lang");

$p_title = $lang['archive']; // "Pastes Archive";

// Check if IP is banned
$query  = "SELECT * FROM ban_user WHERE ip='$ip' LIMIT 1";
$result = $pastedb->get_row($query);

if ( isset( $result->ip ) ) {
  die( $lang['banned'] ); // "You have been banned from ".$site_name;
}

// Site permissions
$query  = "SELECT * FROM site_permissions where id='1'";
$result = $pastedb->get_row( $query );

$siteprivate  = $result->siteprivate;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
} else {
	if ($siteprivate == "on" ) {
		$privatesite = "on";
  } else {
    $privatesite = 'off';
  }
}

// Logout
if (isset($_GET['logout'])) {
	header('Location: ' . $_SERVER['HTTP_REFERER']);
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    session_destroy();
}

// Page views
$query = "SELECT @last_id := MAX(id) as last_id FROM page_view";

$result = $pastedb->get_row($query);

$last_id = $result->last_id;


$query  = sprintf( "SELECT * FROM page_view WHERE id=%s", $last_id );
$result = $pastedb->get_row( $query );

$last_date = $result->date;

if ($last_date == $date) {
    if (str_contains($data_ip, $ip)) {

      $last_tpage = $result->tpage;
      $last_tpage++;

      // IP already exists, Update view count
      $query = sprintf( "UPDATE page_view SET tpage=%s WHERE id=%s", $last_tpage, $last_id );
      $pastedb->query( $query );

    } else {

          $last_tpage  = $result->tpage;
          $last_tvisit = $result->tvisit;
          $last_tpage++;
          $last_tvisit++;

          // Update both tpage and tvisit.
          $query = sprintf( "UPDATE page_view SET tpage=%a,tvisit=%s WHERE id=%s",
                    $last_tpage,
                    $last_tvisit,
                    $last_id
                  );
          $pastedb->query($query);
          file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
        }
} else {
    // Delete the file and clear data_ip
    unlink("tmp/temp.tdata");
    $data_ip = "";

    // New date is created
    $query = "INSERT INTO page_view (date,tpage,tvisit) VALUES ('$date','1','1')";
    $pastedb->query($query);

    // Update the IP
    file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);

}

$query  = "SELECT * FROM ads WHERE id='1'";
$result = $pastedb->get_row( $query );

$text_ads = $result->text_ads;
$ads_1    = $result->ads_1;
$ads_2    = $result->ads_2;

// Theme
require_once('theme/' . $default_theme . '/header.php');
require_once('theme/' . $default_theme . '/archive.php');
require_once('theme/' . $default_theme . '/footer.php');
