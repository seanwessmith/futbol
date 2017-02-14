<html>
<head>
  <title>Results</title>
</head>
<?php
//Send updates while script is running
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

//Prevent timing out
ini_set( 'default_socket_timeout', 120 );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('simple_html_dom.php');

////CONNECT TO SQL        ////
$mysqli = new mysqli("localhost", "root", "root", "dkings");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
//// END SQL CONNECTION  ////
$username = "seanwessmith";
$password = "Cam3lback";
$URL     = "https://www.draftkings.com/mycontests";

$ch = curl_init();
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_URL,$URL);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
$result=curl_exec ($ch);
if ($result === FALSE) {
    printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
           htmlspecialchars(curl_error($ch)));
}

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
////Execute code once logged in to dk////
// $html = file_get_html('https://www.draftkings.com/mycontests');
// //Test to see if page has player name
// $link = array();
// $big_table = $html->find('div');
// foreach($big_table as $table) {
//     $link = $table->find('a');
//     if (isset($link[0])) {
//         $href = $link[0]->innertext;
//         echo $href;
//     }
//   }
////END execute code to log into dk////


curl_close ($ch);


?>
