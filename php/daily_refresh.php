<html>
<head>
  <title>Player Update2</title>
</head>

<style>
body {
    background-color: #282C34;
    /******* RED ******/
    /*color: #E06C75;*/
    /******* BLUE ******/
    color: #61AFEF;
}
</style>

<?php
//Prevent timing out
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

////CONNECT TO SQL        ////
$mysqli = new mysqli("localhost", "root", "root", "dkings");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
//// END SQL CONNECTION  ////
/*
////Update the probable players for the day////
//Grab HTML page used to grab probable players from ESPN
$url = "http://www.rotowire.com/soccer/champions-lineups.htm";
////Reset all players probability to 0
$sql0 = "UPDATE players SET probable = 0";
$res = $mysqli->query($sql0);
$probable_count = 0;

$ch = curl_init();
$timeout = 5;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$html = curl_exec($ch);
curl_close($ch);

# Create a DOM parser object
$dom = new DOMDocument();

# Parse the HTML from Rotowire.
# The @ before the method call suppresses any warnings that
# loadHTML might throw because of invalid HTML in the page.
@$dom->loadHTML($html);
foreach($dom->getElementsByTagName('a') as $link) {
  $sql1 = "UPDATE players SET probable = 1 WHERE player_name = ";
  $sql2 = "INSERT INTO probable_player_history (player_name, game_date) VALUES (";
    $href = $link->getAttribute('title');
    if (isset($href) && $href !== '') {
        $sql1 .= "'".$href."'";
        $sql2 .= "'".$href."', curdate()) ON DUPLICATE KEY UPDATE player_name = '".$href."', game_date = curdate()";
        $probable_count++;
        $mysqli->query($sql1);
        $mysqli->query($sql2);
        $sql9 = "UPDATE `probable_player_history` JOIN players ON probable_player_history.player_name = players.player_name SET probable_player_history.espn_id= players.espn_id";
        $mysqli->query($sql9);
    }
  }

    ////END update the probable players////
		send_message($startTime, "DONE", "Updated ".$probable_count." probable players for the day. ", '100%');
    flush();
*/
    ////Update the team's opponents for the day////
    //Set all teams opponents to NULL
    $sql1  = "UPDATE team SET opponent = NULL";
    $res   = $mysqli->query($sql1);

    $url = "http://www.espnfc.us/scores?date=20170216";
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $html = curl_exec($ch);
    curl_close($ch);
    # Create a DOM parser object
    $dom = new DOMDocument();
    # Parse the HTML from ESPN.
    # The @ before the method call suppresses any warnings that
    # loadHTML might throw because of invalid HTML in the page.
    @$dom->loadHTML($html);

    foreach($dom->getElementsByTagName('div') as $link) {
      if ($link->getAttribute('class') == 'team-name')
      {
        $parent = $link->parentNode;
        $team1 = NULL;
        $team2 = NULL;
        $count = 0;
        foreach ( $parent->childNodes as $pp ) {
          if ($pp->nodeName == 'div') {
            if (!$team1)
              $team1 = rtrim(ltrim(preg_replace('/\'/', '', $pp->nodeValue)));
            else
              $team2 = rtrim(ltrim(preg_replace('/\'/', '', $pp->nodeValue)));
          }
        }
        $sql1 = "SELECT count(*) AS count FROM team WHERE team_name = '".$team1."'";
        $res = $mysqli->query($sql1);
        $res->data_seek(0);
        if ($res !== NULL) {
          $row = $res->fetch_assoc();
          $count = $row['count'];
        }
        if ($count == 0)
        {
          $sql1 = "INSERT INTO team (team_name) VALUES ('".$team1."')";
          echo $sql1;
          $res = $mysqli->query($sql1);
        }
        $sql1 = "UPDATE team SET opponent = '".$team2."' WHERE team.team_name = '".$team1."'";
        $res = $mysqli->query($sql1);
        $sql1 = "UPDATE team SET opponent = '".$team1."' WHERE team.team_name = '".$team2."'";
        $res = $mysqli->query($sql1);
        }
      }
    ////END the teams opponents////
		send_message($startTime, "DONE", "Updated all teams and opponents for the day. ", '100%');
    flush();
    /*
    ////Refresh the results table////
    //Grab latest draftkings results csv from the downloads folder//
    $csvLink = "/Users/sean/Downloads/draftkings-contest-entry-history.csv";
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

    send_message($startTime, "DONE", "Updated results table. ", '100%');
    flush();
    ///////////////////////////////////////////////////
*/

////INPUT: SELECT statement that selects players needing updating////
$sqlSelect = "SELECT * FROM players where refreshed_on = 0";
/////////////////////////////////////////////////////////////////////

//Grab record count
$sql0 = "SELECT count(*) AS rec_count FROM ($sqlSelect) a";
$res = $mysqli->query($sql0);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$rec_count = $row['rec_count'];
}
$step        = 0;
$updateCount = 0;
$newRecords  = 0;
$noTable     = 0;
$not_in_db   = 0;
$var         = 0;
$noTablePlayer = array();

for ($y = 0; $y < $rec_count;) {
//Reset PHP script processing time to prevent script ending after 30 seconds//
set_time_limit(0);
//////////////////////////////////////////////////////////////////////////////

//Select one player that needs updating
$sql0 = $sqlSelect." LIMIT 0,1";
echo $sql0;
$res = $mysqli->query($sql0);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$espnID     = $row['espn_id'];
$playerID = $row['espn_id'];
}

$url = "http://www.espnfc.com/player/".$espnID;
echo "<br>".$url."<br>";
$ch = curl_init();
$timeout = 5;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$html = curl_exec($ch);
curl_close($ch);
# Create a DOM parser object
$dom = new DOMDocument();
# Parse the HTML from ESPN.
# The @ before the method call suppresses any warnings that
# loadHTML might throw because of invalid HTML in the page.
@$dom->loadHTML($html);

//    THIS UPDATES PLAYERS STATIC INFO
//Test to see if page is the standard format needed to grab relevant info//
$i        = 0;
$position = NULL;
$height   = NULL;
$weight   = NULL;
$age      = NULL;
$dob      = NULL;
$pob      = NULL;
/*
foreach($dom->getElementsByTagName('dd') as $link) {
  if ($link->nodeValue == 'Defender' || $link->nodeValue == 'Midfielder' || $link->nodeValue == 'Forward' || $link->nodeValue == 'Goalkeeper')
    $position = $link->nodeValue;
}
*/
foreach($dom->getElementsByTagName('dl') as $col1) {
  $col1_p = $col1->parentNode;
  foreach ($col1_p->childNodes as $col1_c) {
    if ($col1_c->nodeName == 'dl') {
      $prev = NULL;
      foreach ($col1_c->childNodes as $row) {
      if ($prev == 'Position: ' && $i % 2 == 0) {
        $position = $row->nodeValue;
      } else if ($prev == 'Height: ' && $i % 2 == 0) {
        $height = $row->nodeValue;
      } else if ($prev == 'Weight: ' && $i % 2 == 0) {
        $weight = $row->nodeValue;
      } else if ($prev == 'Age: ' && $i % 2 == 0) {
        $age = $row->nodeValue;
      } else if ($prev == 'DOB: ' && $i % 2 == 0) {
        $dob = $row->nodeValue;
      } else if ($prev == 'POB: ' && $i % 2 == 0) {
        $pob = $row->nodeValue;
      }
      if ($i % 2 == 0) {
        $prev = $row->nodeValue;
      }
      $i++;
      }
    }
  }
}
  echo "position = ".$position."<br>";
  echo "height = ".$height."<br>";
  echo "weight = ".$weight."<br>";
  echo "age = ".$age."<br>";
  echo "dob = ".$dob."<br>";
  echo "pob = ".$pob."<br>";
  ////END OF GENERAL INFORMATION////

  ////STARTING GAME INFORMATION////
  //VARIABLES TO NAVIGATE HTML TABLE//
  $i = 0;
  $appear = 0;
  //VARIABLES TO SAVE SQL INFORMATION//
  $team = NULL;
  $game_date = NULL;
  $opponent = NULL;
  $win_result = 0;
  $competition = NULL;
  $score_result = 0;
  $appear = NULL;
  $goals = 0;
  $assists = 0;
  $shots = 0;
  $shots_on_goal = 0;
  $fouls_commited = 0;
  $fouls_suffered = 0;
  $yellow_cards = 0;
  $red_cards = 0;

  foreach($dom->getElementsByTagName('tbody') as $tbody) {
    foreach ($tbody->childNodes as $td) {
      foreach ($td->childNodes as $field) {
        if ($appear == 1) {
          if ($field->nodeValue != '' && $field->nodeValue != NULL && !strpos(json_encode($field->nodeValue), '\n')) {
            if ($team == NULL) {
              $team = $field->nodeValue;
            } elseif ($opponent == NULL) {
              $opponent = $field->nodeValue;
            } elseif ($game_date == NULL) {
              $game_date = $field->nodeValue;
            } elseif ($competition == 0) {
              $competition = $field->nodeValue;
            } elseif ($win_result == NULL) {
              $win_result = $field->nodeValue;
            } elseif ($appear == NULL) {
              $appear = $field->nodeValue;
            } elseif ($goals == NULL) {
              $goals = $field->nodeValue;
            } elseif ($assists == NULL) {
              $assists = $field->nodeValue;
            } elseif ($shots == NULL) {
              $shots = $field->nodeValue;
            } elseif ($shots_on_goal == NULL) {
              $shots_on_goal = $field->nodeValue;
            } elseif ($fouls_commited == NULL) {
              $fouls_commited = $field->nodeValue;
            } elseif ($fouls_suffered == NULL) {
              $fouls_suffered = $field->nodeValue;
            } elseif ($yellow_cards == NULL) {
              $yellow_cards = $field->nodeValue;
            } elseif ($red_cards == NULL) {
              $red_cards = $field->nodeValue;
            }
            $i++;
          }
        }
        if ($i % 14 == 0 && $appear == 1) {
          $sql3 = "INSERT INTO `player_stats`(`espn_id`,`game_date`, `opponent`, `win_result`, `competition`, `score_result`,
                  `appear`, `goals`, `assists`, `shots`, `shots_on_goal`, `fouls_commited`, `fouls_suffered`, `yellow_cards`, `red_cards`, `added_on`)
                  VALUES ('".$espnID."','".$team."','".$opponent."','".$game_date."','".$win_result."','".$competition."','".$score_result."',
                  '".$appear."','".$goals."','".$assists."','".$shots."','".$shots_on_goal."','".$fouls_commited."','".$fouls_suffered."',
                  '".$yellow_cards."','".$red_cards."')";
          $res = $mysqli->query($sql3);
          $team = NULL;
          $game_date = NULL;
          $opponent = NULL;
          $win_result = 0;
          $competition = NULL;
          $score_result = 0;
          $appear = NULL;
          $goals = 0;
          $assists = 0;
          $shots = 0;
          $shots_on_goal = 0;
          $fouls_commited = 0;
          $fouls_suffered = 0;
          $yellow_cards = 0;
          $red_cards = 0;
        }
        if ($field->nodeValue == "Appear") {
          $appear = 1;
          break ;
        }
      }
    }
  }

/*
$generalStats = $html->find('ul.general-info li');
if ($generalStats != NULL) {
  $pos_num = $generalStats[0];
  preg_match('~>(.*?)<~', $pos_num, $output);
  $position = substr($output[1], -2);

  if ($position !== NULL) {
  //Grab General Stats of Player

  $generalStats2 = $html->find('h1');
  $name = $generalStats2[0];
  preg_match('~>(.*?)<~', $name, $output);
  $name = str_replace('\'', '\\\'', $output[1]);

  $generalStats2 = $html->find('ul.general-info li');
  $pos_num = $generalStats2[0];
  preg_match('~>(.*?)<~', $pos_num, $output);
  $position = substr($output[1], -2);
  if (strpos($position, 'F')) {
    $position = "OF";
  }
  $number   = substr($output[1], 1, 2);

  $throw_bat = preg_replace('~[,:]~', '', $generalStats2[1]->innertext);
  $arr = explode(' ', $throw_bat);
  $how_many = count($arr);
  for($i = 0; $i < $how_many; $i = $i + 2){
    if ($arr[$i] == "Bats") {
      $bat = $arr[$i+1];
    } elseif ($arr[$i] == "Throws") {
      $throw = $arr[$i+1];
    }
  }

  $teamArray = $generalStats2[2];
  preg_match('~<a(.*?)/a>~', $teamArray, $output4);
  $input = $output4[1];
  preg_match('~>(.*?)<~', $input, $output5);
  $team = $output5[1];

  $generalStats2 = $html->find('ul.player-metadata li');
  $birthDate = $generalStats2[0];
  preg_match('~<span>Birth Date</span>(.*?) \(Age~', $birthDate, $output6);
  $date = $output6[1];
  $date = str_replace(',', '', $date);
  //$date = str_replace(' ', '-', $date);
  $date =  date('Y/m/d', strtotime($date));

  if (preg_match('~Ht/Wt~', $generalStats2[3])) {
    $ht_wt = $generalStats2[3];
    preg_match('~</span>(.*?),~', $ht_wt, $output7);
    $height = $output7[1];
    preg_match('~,(.*?)lbs.~', $ht_wt, $output8);
    $weight = trim($output8[1]);
  } else {
    $ht_wt = $generalStats2[4];
    preg_match('~</span>(.*?),~', $ht_wt, $output7);
    $height = $output7[1];
    preg_match('~,(.*?)lbs.~', $ht_wt, $output8);
    $weight = trim($output8[1]);
  }
}
} else {
  $generalStats = $html->find('ul.player-metadata li');
  if ($generalStats != NULL) {
  foreach ($generalStats as $key => $value) {
    if (strpos($value->innertext, 'Date')) {

      //Remove the span element from $value
      foreach($value->find('span') as $e) {
        $e->outertext = '';
    }

    $date = $value->innertext;
    $date = str_replace('Birth Date', '', $date);
    $date =  date('Y/m/d', strtotime($date));
  } elseif (strpos($value->innertext, 'Position')) {

      //Remove the span element from $value
      foreach($value->find('span') as $e) {
        $e->outertext = '';
      }

      $position = str_replace('Position', '', $value->innertext) ;
      if (strpos($position, 'Field')) {
        $position = "OF";
      } elseif (strpos($position, 'Catcher')) {
        $position = "C";
      } elseif (strpos($position, 'Second')) {
        $position = "2B";
      } elseif (strpos($position, 'First')) {
        $position = "1B";
      } elseif (strpos($position, 'Third')) {
        $position = "3B";
      } elseif ($position == "Short Stop") {
        $position = "SS";
      } elseif ($position == "Shortstop") {
        $position = "SS";
      } elseif ($position == "Starting Pitcher") {
        $position = "SP";
      } elseif ($position == "Relief Pitcher") {
        $position = "RP";
      } elseif ($position == "Pitcher") {
        $position = "RP";
      }
    }
}
  $generalStats = $html->find('h1');
  $name = $generalStats[0];
  preg_match('~>(.*?)<~', $name, $output);
  $name = str_replace('\'', '\\\'', $output[1]);

  //Set defaults for incomplete data
  $number = 0;
  $team   = NULL;
  $throw  = NULL;
  $bat    = NULL;
  $height = NULL;
  $weight = NULL;
}
}

if ($name != NULL && $date !== NULL) {
$sql1 = "UPDATE players SET `espn_id` = '$espnID',`player_name` = '$name', `position` = '$position', `number` = '$number', `team` = '$team', `throw` = '$throw', `bat` = '$bat', `height` = '$height',
                              `weight` = '$weight',`birth_date` = '$date',`changed_on` = curdate() WHERE espn_id = $playerID";
$res = $mysqli->query($sql1);
}

//Grab Field Stats of Player
$table = array();
$table = $html->find('table',1);

if (strpos($table, 'No statistics available') == false && $table != NULL) {
  if ($position == 'SP' || $position == 'RP') {

//Build table from from table
$headData     = array();
$mainTable    = 0;
$skipNextRow  = 0;
$cellCounter  = 0;
$rowCounter   = 0;
$date         = 0;
$did_not_play = 0;
$has_records  = 0;
$cellSQL      = NULL;
foreach(($table->find('tr')) as $row) {
  $sql3 = "INSERT INTO `player_stats`(`espn_id`,`game_date`, `opponent`, `win_result`, `score_result`, `innings_pitched`,
          `hits`, `runs`, `earned_runs`, `home_runs`, `walks`, `strikeouts`, `ground_balls`, `fly_balls`, `pitches`,
          `batters_faced`, `game_score`, `added_on`) VALUES ";
  $rowCounter++;
    foreach($row->find('td') as $cell) {
        $cellData = $cell->innertext;
        //End the table loop once end is reached, determined by the cell "Totals"
        if ($cellData == "Totals" || $cellData == "&nbsp;") {
          break 2;
        }
        //Skip the two header columns following the Regular header and Monthly header
        if (strpos($cellData, 'Regular') == TRUE || $cellData == "Monthly Totals") {
          $skipNextRow = 1;
        }
        if ($cellData == "DATE") {
          $skipNextRow = 2;
        }
        if ($skipNextRow == 0) {
          if ($cellCounter == 0) {
            $cellData .= " 2016";
            $date =  date('Y/m/d', strtotime($cellData));
            $cellSQL .= "('".$playerID."','".$date."'";
          } elseif ($cellCounter == 1) {
            $cellSQL .= ",'".trim(substr($cellData, -3))."'";
          } elseif ($cellCounter == 2) {
            preg_match('~>(.*?)<~', $cellData, $output);
            if ($output[1] == "W") {
              $cellSQL .= ",'1',";
            } else {
              $cellSQL .= ",'0',";
            }
            preg_match('~<a(.*?)/a~', $cellData, $output);
            $input2 = $output[1];
            preg_match('~>(.*?)<~', $input2, $output2);
            $cellSQL .= "'".$output2[1]."'";
          } elseif ($cellCounter == 15 || $cellCounter == 16 || $cellCounter == 17) {
          } elseif ($cellCounter == 14) {
            $cellSQL .= ",'".$cellData."', curdate())";
          } elseif($cellData == "Did not play") {
            $did_not_play = 1;
          } else {
            $has_records = 1;
            $cellSQL .= ",'".$cellData."'";
          }
        }
        $cellCounter++;
      }
    $cellCounter = 0;
    //See if this players game has already been recorded in player_stats
    $sql4 = "SELECT count(*) as p_count FROM player_stats WHERE espn_id = '$playerID' AND game_date = '$date'";
    if ($date !== 0 && $skipNextRow == 0) {
      $res = $mysqli->query($sql4);
      $res->data_seek(0);
      while ($row = $res->fetch_assoc()) {
        $pCount = $row['p_count'];
      }
      if ($pCount == 0) {
        $sql3 .= $cellSQL;
        $not_in_db = 1;
        $cellSQL = NULL;
      }
    }
    //Used to skip the two header rows
    if ($skipNextRow == 1) {
      $skipNextRow = 2;
    } else {
      $skipNextRow = 0;
    }
    if ($not_in_db == 1 && $did_not_play == 0 && $has_records == 1) {
      $res = $mysqli->query($sql3);
      $newRecords++;
    }
    $did_not_play = 0;
    $has_records  = 0;
    $not_in_db    = 0;
    $cellSQL      = NULL;
}
} else {
  $table = $html->find('table',1);
    //Build table from from table
    $headData     = array();
    $mainTable    = 0;
    $skipNextRow  = 0;
    $cellCounter  = 0;
    $rowCounter   = 0;
    $date         = 0;
    $did_not_play = 0;
    $has_records  = 0;
    $cellSQL      = NULL;
    foreach(($table->find('tr')) as $row) {
      $cellSQL = NULL;
      $sql3 = "INSERT INTO `player_stats` (`espn_id`,`game_date`, `opponent`, `win_result`, `score_result`, `at_bat`,
        `runs`, `hits`, `double_hit`, `triple_hit`, `home_runs`, `rbi`, `walks`, `strikeouts`, `stolen_bases`,
        `caught_stealing`, `base_percent`, `slug_percent`, `added_on`) VALUES ";
      $rowCounter++;
        $rowData = array();
        foreach($row->find('td') as $cell) {
          ////Non Pitcher loop
            $cellData = $cell->innertext;
            //End the table loop once end is reached, determined by the cell "Totals"
            if ($cellData == "Totals" || $cellData == "&nbsp;") {
              break 2;
            }
            //Skip the two header columns following the Regular header and Monthly header
            if (strpos($cellData, 'Regular') == TRUE || $cellData == "Monthly Totals") {
              $skipNextRow = 1;
            }
            if ($cellData == "DATE") {
              $skipNextRow = 2;
            }
            if ($skipNextRow == 0) {
              if ($cellCounter == 0) {
                $cellData .= " 2016";
                $date =  date('Y/m/d', strtotime($cellData));
                  $cellSQL .= "('".$playerID."','".$date."'";
              } elseif ($cellCounter == 1) {
                $cellSQL .= ",'".trim(substr($cellData, -3))."'";
              } elseif ($cellCounter == 2) {
                preg_match('~>(.*?)<~', $cellData, $output);
                if ($output[1] == "W") {
                  $cellSQL .= ",'1',";
                } else {
                  $cellSQL .= ",'0',";
                }
                preg_match('~<a(.*?)/a~', $cellData, $output);
                $input2 = $output[1];
                preg_match('~>(.*?)<~', $input2, $output2);
                $cellSQL .= "'".$output2[1]."'";
              } elseif ($cellCounter == 16 || $cellCounter == 17) {
              } elseif ($cellCounter == 15) {
                $cellSQL .= ",'".$cellData."',curdate())";
              } elseif($cellData == "Did not play") {
                $did_not_play = 1;
              } else {
                $has_records = 1;
                $cellSQL .= ",'".$cellData."'";
              }
            }
            $cellCounter++;
          }
        $cellCounter = 0;
        //See if this players game has already been recorded in player_stats
        $sql4 = "SELECT count(*) as p_count FROM player_stats WHERE espn_id = '$playerID' AND game_date = '$date'";
        if ($date !== 0 && $skipNextRow == 0) {
          $res = $mysqli->query($sql4);
          $res->data_seek(0);
          while ($row = $res->fetch_assoc()) {
            $pCount = $row['p_count'];
          }
          if ($pCount == 0) {
            $sql3 .= $cellSQL;
          }
        }
        //Used to skip the two header rows
        if ($skipNextRow == 1) {
          $skipNextRow = 2;
        } else {
          $skipNextRow = 0;
        }
        if ($did_not_play == 0 && $has_records == 1) {
          $sql3 .= "ON DUPLICATE KEY UPDATE espn_id = '".$playerID."', changed_on = curdate()";
          $res = $mysqli->query($sql3);
          $newRecords++;
        }
        $did_not_play = 0;
        $has_records  = 0;
        $not_in_db    = 0;
    }
}
}
//Input new game stats into player_stats table
$updateCount++;
//Send updates while script is running
$var++;
if($var %20 == 0) {
send_message($startTime, $i, $updateCount . ' of '.$rec_count, round(($updateCount / $rec_count) * 100 ,2).'%');
}
$y++;
$step++;
//Set refreshed_on date for the newlyupdated player
$sql5 = "UPDATE players SET `refreshed_on` = curdate() WHERE `espn_id` = $playerID";
$res = $mysqli->query($sql5);
}
////Update the pitchers real total_score
$sql6 = "UPDATE `player_stats` JOIN players on players.espn_id = player_stats.espn_id
         SET `total_score`= ((`innings_pitched`*2.25)+(`strikeouts`*2)+(`win_result`*4)+
                             (`earned_runs`*-2)+(`hits`*-.6)+(`walks`*-.6))
         WHERE players.position like '%P%'";
$res = $mysqli->query($sql6);
////Update the pitchers real total_score
$sql7 = "UPDATE `player_stats` JOIN players on players.espn_id = player_stats.espn_id
         SET `total_score`= (((`hits`-`double_hit`-`triple_hit`)*3)+(`double_hit`*5)+
         (`triple_hit`*8)+(`home_runs`*10)+(`rbi`*2.25)+(`runs`*2.25)+(`walks`*2)+
         (`rbi`*2.25)+(`stolen_bases`*5)) WHERE players.position NOT LIKE '%P%'";
$res = $mysqli->query($sql7);

////Update hitting_strength against a pitcher
$sql = "UPDATE players JOIN (SELECT a.espn_id, a.player_name, a.average - b.tot_average AS hitting_strength FROM

(SELECT a.espn_id, a.player_name, round(SUM(b.hitting_strength)/count(*),2) AS average FROM
(SELECT players.espn_id, players.player_name, game_date, team_nickname FROM player_stats, players WHERE players.espn_id = player_stats.espn_id AND players.position LIKE '%P%' AND player_stats.innings_pitched > 0) a,
(SELECT round(SUM(total_score)/count(*),2) AS hitting_strength, game_date, opponent FROM player_stats, players WHERE player_stats.espn_id = players.espn_id AND players.position NOT LIKE '%P%' GROUP BY opponent, game_date) b WHERE a.team_nickname = b.opponent AND a.game_date = b.game_date GROUP BY a.espn_id) a,

(SELECT round(SUM(b.hitting_strength)/count(*),2) AS tot_average FROM
(SELECT players.espn_id, players.player_name, game_date, team_nickname FROM player_stats, players WHERE players.espn_id = player_stats.espn_id AND players.position LIKE '%P%' AND player_stats.innings_pitched > 0) a,
(SELECT round(SUM(total_score)/count(*),2) AS hitting_strength, game_date, opponent FROM player_stats, players WHERE player_stats.espn_id = players.espn_id AND players.position NOT LIKE '%P%' GROUP BY opponent, game_date) b WHERE a.team_nickname = b.opponent AND a.game_date = b.game_date) b) a ON players.espn_id = a.espn_id SET players.points_against = a.hitting_strength";
$mysqli->query($sql);

////Update pitching_strength against a hitter
$sql = "UPDATE players JOIN (SELECT a.espn_id, a.player_name, a.average - b.tot_average AS pitching_strength FROM
(SELECT a.espn_id, a.player_name, round(SUM(b.pitching_strength)/count(*),2) AS average FROM
(SELECT players.espn_id, players.player_name, game_date, team_nickname FROM player_stats, players WHERE players.espn_id = player_stats.espn_id AND players.position NOT LIKE '%P%' AND player_stats.at_bat > 0) a,
(SELECT round(SUM(total_score)/count(*),2) AS pitching_strength, game_date, opponent FROM player_stats, players WHERE player_stats.espn_id = players.espn_id AND players.position LIKE '%P%' GROUP BY opponent, game_date) b WHERE a.team_nickname = b.opponent AND a.game_date = b.game_date GROUP BY a.espn_id) a,
(SELECT round(SUM(b.pitching_strength)/count(*),2) AS tot_average FROM
(SELECT players.espn_id, players.player_name, game_date, team_nickname FROM player_stats, players WHERE players.espn_id = player_stats.espn_id AND players.position NOT LIKE '%P%' AND player_stats.at_bat > 0) a,
(SELECT round(SUM(total_score)/count(*),2) AS pitching_strength, game_date, opponent FROM player_stats, players WHERE player_stats.espn_id = players.espn_id AND players.position LIKE '%P%' GROUP BY opponent, game_date) b WHERE a.team_nickname = b.opponent AND a.game_date = b.game_date) b) a ON players.espn_id = a.espn_id SET players.points_against = a.pitching_strength";
$mysqli->query($sql);

//Higher points equates to an easy team to score hitting points against
$sql8 = "UPDATE team JOIN (SELECT round((sum(total_score))/count(*) - a.average, 2) as hitting_strength_against_pitchers, opponent FROM players, player_stats, (SELECT SUM(total_score)/count(*) AS average FROM player_stats) a WHERE players.espn_id = player_stats.espn_id AND position LIKE '%P%' GROUP BY player_stats.opponent) a
ON team.nickname = a.opponent SET team.hitting_strength = a.hitting_strength_against_pitchers";
$mysqli->query($sql8);
////Update hitting points accumulated against a certain team
//Higher points equates to an easy team to score pitchting points against
$sql9 = "UPDATE team JOIN (SELECT round((sum(total_score))/count(*) - a.average, 2) as pitching_strength_against_hitters, opponent FROM players, player_stats, (SELECT SUM(total_score)/count(*) AS average FROM player_stats) a WHERE players.espn_id = player_stats.espn_id AND position NOT LIKE '%P%' GROUP BY player_stats.opponent) a
ON team.nickname = a.opponent
SET team.pitching_strength = a.pitching_strength_against_hitters";
$mysqli->query($sql9);

$sql10 = "UPDATE players JOIN (SELECT espn_id, round(sum(total_score)/count(*)) AS points FROM player_stats GROUP BY espn_id) a ON players.espn_id = a.espn_id SET players.value = (a.points/players.salary*100000)";
$mysqli->query($sql10);

$sql11 = "UPDATE players JOIN (SELECT espn_id, round(sum(total_score)/count(*)) AS points FROM player_stats GROUP BY espn_id) a ON players.espn_id = a.espn_id SET players.points = a.points";
$mysqli->query($sql11);
*/
$totalTime = time() - $startTime;
echo " Total Time Taken: ".$totalTime;
//echo " Total Players updated: ".$updateCount;
//echo " Total Players with new records: ".$newRecords;
send_message($startTime,'CLOSE', 'Process complete', '100%');
?>
