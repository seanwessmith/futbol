<html>
<head>
  <title>Unknown Players</title>
</head>
<?php
ini_set( 'default_socket_timeout', 120 );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('simple_html_dom.php');

$startTime = time();
//Send updates while script is running
function send_message($startTime, $id, $message, $progress) {
    $d = array('Iteration: ' => $message , 'progress' => $progress);

    echo "<pre>Seconds: ";
    echo time() - $startTime. PHP_EOL;
    echo json_encode($d) . PHP_EOL;
    echo PHP_EOL;
    echo "</pre>";
    flush();
}

//CONNECT TO SQL        //
$mysqli = new mysqli("localhost", "root", "root", "dkings");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
//END SQL CONNECTION   //


//Grab record count
$sql0 = "SELECT count(*) AS rec_count FROM players WHERE espn_id = 0";
$res = $mysqli->query($sql0);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$rec_count = $row['rec_count'];
}

//Cycle through 1 player
$i = 0;
for ($y = 0; $y < $rec_count;) {
$sql1 = "SELECT * FROM players WHERE espn_id = 0";
$res = $mysqli->query($sql1);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
  $unmatchedPlayer = $row['player_name'];
}

//Get player name ready for URL
$encodedPlayer = urlencode($unmatchedPlayer);
$hrefPlayer = strtolower(str_replace(' ', '\-', $unmatchedPlayer));
//Get player name ready for SQL
$name = str_replace('\'', '\\\'', $unmatchedPlayer);
//Grab HTML page used to grep ESPN number
echo "Player: ".$encodedPlayer."<br>";
$dom = new DOMDocument;
$url = "https://www.google.com/search?safe=off&q=".$encodedPlayer."espn+fc&oq=".$encodedPlayer."+espn+fc";
$ch = curl_init();
$timeout = 5;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$html = curl_exec($ch);
curl_close($ch);

# Create a DOM parser object
$dom = new DOMDocument();

# Parse the HTML from Google.
# The @ before the method call suppresses any warnings that
# loadHTML might throw because of invalid HTML in the page.
@$dom->loadHTML($html);
foreach($dom->getElementsByTagName('a') as $link) {
  if(strpos($link->getAttribute('href'), "www.espnfc.us/player/")) {
    $href = $link->getAttribute('href');
    $href = substr($href, strpos($href, "player/") + 7);
    $espnID = explode("/", $href, 2);
    break;
  }
}

if ($espnID[0] !== NULL){
  //Insert Player Name and ESPN ID into players table
  $sql0 = "UPDATE `players` SET espn_id = '$espnID[0]', changed_on = curdate() WHERE player_name = '$name'";
  $mysqli->query($sql0);
}
$y++;

$i++;
if($i %20 == 0) {
send_message($startTime, $i, $y . ' of '.$rec_count, round(($y / $rec_count) * 100 ,2).'%');
}
}
$totalTime = time() - $startTime;
echo " Total Time Taken: ".$totalTime;
?>
