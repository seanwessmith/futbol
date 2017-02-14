<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta content="width=device-width,initial-scale=1.0,user-scalable=no,minimum-scale=1.0,maximum-scale=1.0" id="viewport" name="viewport">
	<link rel="stylesheet" type="text/css" href="./css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="./css/wynd.css">
	<script src="./js/jquery-1.11.3.min.js"></script>
	<script src="./js/migrate.js"></script>
	<script src="./js/bootstrap.min.js"></script>
	<script src="./js/main.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<link rel="shortcut icon" href="img/favicon.ico">
	<!--[if lt IE 9]>
	        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	    <![endif]-->
	<title>Results Test</title>
	<style>
</style>
</head>
<body>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
////CONNECT TO SQL        ////
$mysqli = new mysqli("localhost", "root", "root", "dkings");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (".$mysqli->connect_errno .")".$mysqli->connect_error;
}
//// END SQL CONNECTION  ////

//Change this when using new draft kings link//
$csvLink = "/Users/seanwessmith/Downloads/draftkings-contest-entry-history.csv";
///////////////////////////////////////////////

$viewName   = NULL;
$csv_link   = NULL;
$oldCSVLink = NULL;
$new_csv    = 0;
$csvData    = NULL;
$lines      = NULL;
$csvArray   = array();

////Download new CSV and Parse into players////
$csvData = file_get_contents($csvLink);
$lines = explode(PHP_EOL, $csvData);
$array = array();
foreach ($lines as $line) {
    $csvArray[] = str_getcsv($line);
}
unset($csvArray[0]);
array_pop($csvArray);
/////////////////////////////////////////////////////////////

$sql2 = "INSERT INTO `results`(`entry_type`, `entry_date`, `placed`, `entries`, `points`, `places_paid`) VALUES ";

$i = 0;
foreach ($csvArray as $array){
  if ($array[0] == 'MLB') {
	$entry_type  = str_replace("'","''", $array[1]);
	$entry_date  = str_replace("'","''", $array[2]);
	$placed      = str_replace("'","''", $array[3]);
  $entries     = str_replace("'","''", $array[7]);
  $points      = str_replace("'","''", $array[4]);
  $places_paid = str_replace("'","''", $array[10]);
  $sql3 = "SELECT count(*) as rec_count FROM results WHERE entry_type = ".$entry_type." AND entry_date = ".$entry_date." AND placed = ";
	if ($i == 0) {
		$sql2 .= "('".$entry_type."','".$entry_date."','".$placed."', '".$entries."', '".$points."', '".$places_paid."')";

	} else {
    $sql2 .= ", ('".$entry_type."','".$entry_date."','".$placed."', '".$entries."', '".$points."', '".$places_paid."')";
	}
	$i++;
}
//PHP string can only handle < 100 records, this inserts into SQL in steps
if ($i == 80) {
  $sql2 .= " ON DUPLICATE KEY UPDATE `changed_on` = curdate();";
  $mysqli->query($sql2);
  $sql2 = "INSERT INTO `results`(`entry_type`, `entry_date`, `placed`, `entries`, `points`, `places_paid`) VALUES ";
  $i = 0;
}
}
$sql2 .= " ON DUPLICATE KEY UPDATE `changed_on` = curdate();";
$mysqli->query($sql2);
$sql2 = "INSERT INTO `results`(`entry_type`, `entry_date`, `placed`, `entries`, `points`, `places_paid`) VALUES ";
}
