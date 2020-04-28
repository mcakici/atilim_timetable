<?php
$dbhost = "localhost";
$dbname = "timetable";
$dbuser = "root";
$dbpass = "";
$cookie_name = 'atilimtimetable_hash';

$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
//$db = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass);
$db->query("SET NAMES 'utf8'");
$db->query("SET CHARACTER SET utf8");
$db->query("SET COLLATION_CONNECTION = 'utf8_general_ci'");


function set_new_default_timezone($timezone_val = "Europe/Istanbul"){
  if(empty($timezone_val))
  date_default_timezone_set("Europe/Istanbul");
  else
  date_default_timezone_set($timezone_val);
}

set_new_default_timezone();

?>