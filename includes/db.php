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
class PasteDB {

 function __construct( $dbhost, $dbname, $dbuser, $dbpassword ) {

  $this->dbuser = $dbuser;
	$this->dbpassword = $dbpassword;
	$this->dbname = $dbname;
	$this->dbhost = $dbhost;

  $this->db_connect();
 }

 function db_connect() {

   try {
     $this->db = new mysqli( $this->dbhost, $this->dbuser, $this->dbpassword, $this->dbname );
   } catch (Exception $e ) {
     echo "Service unavailable";
     echo "message: " . $e->message;   // not in live code obviously...
     exit;
  }
 }

 /**
  * Get a single row from the database and retuen as an object.
  *
  * @since 2.2
  */
 function get_row( $query ) {

   $output = false;

   $result = $this->query( $query );
   if( $result instanceof mysqli_result ) {
     $output = mysqli_fetch_object( $result );
   }
   return ( $output ) ? $output : new stdClass;
 }

 /**
  * Main query
  */
 function query( $query ) {

   try {
     $result = mysqli_query( $this->db, $query );
   }  catch (Exception $e ) {
     echo "Service unavailable";
     echo "message: " . $e->message;   // not in live code obviously...
     exit;
   }
   //mysqli_free_result( $result );
   return $result;
 }

 function escape( $string ) {
   return mysqli_real_escape_string( $this->db, $string );
 }


}

$GLOBALS['pastedb'] = new PasteDB( $dbhost, $dbname, $dbuser, $dbpassword );
