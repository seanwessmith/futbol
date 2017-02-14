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
	<title>Fantasy Baseball Quant</title>
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
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
//// END SQL CONNECTION  ////

//Change this when using new draft kings link//
$csvLink = "https://www.draftkings.com/lineup/getavailableplayerscsv?contestTypeId=28&draftGroupId=9803";
///////////////////////////////////////////////

$viewName   = NULL;
$csv_link   = NULL;
$oldCSVLink = NULL;
$new_csv    = 0;
$sql0 = "SELECT url FROM dk_csv WHERE url = '$csvLink'";
$res = $mysqli->query($sql0);
$res->data_seek(0);
if ($res !== 0) {
  while ($row = $res->fetch_assoc()) {
	  $oldCSVLink = $row['url'];
  }
	$sql0 = "UPDATE dk_csv SET active = '0'";
	$res = $mysqli->query($sql0);
}

////Add csv url to the dk_csv table if not there already
if ($oldCSVLink != $csvLink) {
		$new_csv = 1;
  ////Insert csv url into the dk_csv table////
  $sql1 = "INSERT INTO dk_csv (url, added_on, active) VALUES ('$csvLink', curdate(), '1');";
  $res = $mysqli->query($sql1);
} else {
	////Set csv to active
	$sql1 = "UPDATE dk_csv SET active = '1' WHERE url = '".$csvLink."'";
	$res = $mysqli->query($sql1);
}
$sql5 = "SELECT csv_id FROM dk_csv WHERE url = '".$csvLink."'";
$res = $mysqli->query($sql5);
while ($row = $res->fetch_assoc()) {
	$csv_id = $row['csv_id'];
}
/////////////////////////////////////////////

////Download new CSV and Parse into players////
$csvData = file_get_contents($csvLink);
$lines = explode(PHP_EOL, $csvData);
$array = array();
foreach ($lines as $line) {
    $csvArray[] = str_getcsv($line);
}
unset($csvArray[0]);
array_pop($csvArray);
///////////////////////////////////////////////////////////////

////Part 1 of 3 for the SQL statement
$sql2 = "INSERT INTO players (`player_name`,  `dk_name`, `position`, `team_nickname`, `added_on`) VALUES ";
////Part 2 of 3 for the SQL statment
$i = 0;
foreach ($csvArray as $array){
	$name     = str_replace("'","''", $array[1]);
	$position = $array[0];
	$team     = str_replace("'","''", $array[5]);
	if ($i == 0) {
		$sql2 .= "('".$name."','".$name."','".$position."', '".$team."', curdate())";

	} else {
    $sql2 .= ", ('".$name."','".$name."','".$position."', '".$team."', curdate())";
	}
	$i = 1;
}
////Part 3 of 3 for the SQL statement
$sql2 .= " ON DUPLICATE KEY UPDATE `position` = VALUES(position), `team_nickname` = VALUES(team_nickname), `changed_on` = curdate();";

//Insert new csv
$mysqli->query($sql2);
////Grab newly inserted csv for use below

////Grab the espn_id from players and push onto the csvArray
foreach ($csvArray as $key => &$array){
	$name = str_replace("'","''", $array[1]);
	  $sql3 = "SELECT espn_id FROM players WHERE dk_name = '".$name."'";
		$res = $mysqli->query($sql3);
		$res->data_seek(0);
		if ($res !== 0) {
		  while ($row = $res->fetch_assoc()) {
		    $array[] = $row['espn_id'];
		  }
    } else {
			echo "<br>".$name;
		}
}

////Insert records into the dk_stats table if there is a new CSV
if ($new_csv == 1) {
$sql4 = "INSERT INTO dk_stats (`espn_id`, `salary`, `points`, `value`, `csv_id`, `added_on`) VALUES ";
$i = 0;

foreach ($csvArray as $array){
	if (isset($array[6]) == false) {
		echo "<br><pre>".$array[0]." ".$array[1]." ".$array[2]." ".$array[3]." ".$array[4]." ".$array[5]."</pre>";
	}
	$espn_id = str_replace("'","''", $array[6]);
	$salary    = $array[2];
	$points    = $array[4];
	if ($salary != 0 && $points != 0) {
	$value     = round(($points/$salary)*100000);
} else {
	$value = 0;
}
	if ($i == 0) {
		$sql4 .= "('".$espn_id."', '".$salary."', '".$points."', '".$value."','".$csv_id."', curdate())";
	} else {
    $sql4 .= ", ('".$espn_id."', '".$salary."', '".$points."','".$value."','".$csv_id."', curdate())";
	}
	$i = 1;
}
$mysqli->query($sql4);
}

////Update players.value
$sql10 = "UPDATE players JOIN (SELECT espn_id, round(sum(total_score)/count(*)) AS points FROM player_stats GROUP BY espn_id) a ON players.espn_id = a.espn_id SET players.value = (a.points/players.salary*100000)";
$mysqli->query($sql10);
////Update players.salary
$sql8 = "UPDATE players JOIN (SELECT salary, dk_stats.espn_id FROM dk_stats,
				(SELECT max(added_on) AS added_on, espn_id FROM dk_stats GROUP BY espn_id) a
				 WHERE a.espn_id = dk_stats.espn_id AND a.added_on = dk_stats.added_on) a
				 ON a.espn_id = players.espn_id SET players.salary = a.salary";
$mysqli->query($sql8);
$sql11 = "UPDATE players JOIN (SELECT espn_id, round(sum(total_score)/count(*)) AS points FROM player_stats GROUP BY espn_id) a ON players.espn_id = a.espn_id SET players.value = (a.points/players.salary*100000)";
$mysqli->query($sql11);

$sql12 = "UPDATE players JOIN (SELECT espn_id, round(sum(total_score)/count(*)) AS points FROM player_stats GROUP BY espn_id) a ON players.espn_id = a.espn_id SET players.points = a.points";
$mysqli->query($sql12);
?>

<!-- Navigation Bar -->
<div class="full bg"></div>
<div class="full blurbg" id="cliptop"></div>
<div class="full blurbg" id="mainblur"></div>
<div id="nav" class="navbar navbar-default navbar-fixed-top">
  <div class="container constrained">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
      <a class="navbar-brand">Fantasy Baseball Quant</a> </div>
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li id="homebar" class="baritem active"><a href="#content">Build</a></li>
        <li id="projbar" class="baritem"><a href="#projects">Mission Control</a></li>
        <li id="connbar" class="baritem"><a href="#connect">History</a></li>
      </ul>
    </div>
  </div>
</div>
<!-- End of Navigation Bar -->

<!-- Header and Salary Cap input field -->
<div class="container-fluid" id="content">
<div class="row padme">
  <div id="box1" class="col-md-6 box center">
    <h1>Fantasy Baseball Quant</h1>
		<form class="form-inline" method="POST">
			<?php
	      if($_SERVER['REQUEST_METHOD'] == "POST"){
					echo 'yes';
		  //Set sal_cap
			//If the javascript varaible is posted then pass to PHP variable
			print "<pre>";
		  print_r($_POST);
		  print "</pre>";

			if(isset($_POST['sal_cap'])){
			    $sal_cap = $_POST['sal_cap'];
					echo 'true';
			}else{
				echo 'no';
				?>
			<script>
				//Post id=salary_cap value from textbox
				///////Grab Salary Cap///////
					function salCap(){
					//Grab values from quantity and price textboxes
					var salcap  = $('#sal_cap').val();
							//Send calculated subtotal to the subtotal textbox
					}
					$.ajax({
			        type: "POST",
			        url: "index.php",
			        data:{ sal_cap: salcap },
			        success: function(data){
			            console.log(data);
			        }
			    })
					alert($salcap)
					</script>
					<?
			echo $sal_cap;}
}
?>
    <div class="input-group">
      <input type="text" id="salary_cap" class="form-control" placeholder="Enter your Salary Cap...">
      <span class="input-group-btn">
        <button class="btn btn-default" type="button">Go!</button>
      </span>
    </div><!-- /input-group -->
	</form>
  </div><!-- /.row -->
</div>
</div>
<!-- End of header and salary cap input field -->

<!-- Grab information from database to build team-->
	<?php
  //Set hard values, sal_cap and set tot_sal to 0
	$sal_cap = 50000;
	$tot_sal = 0;
	//Create a best team array
	$best_team = array("p00_n"=>"0","p00_s"=>"0","p00_v"=>"0","p00_k"=>"0","p00_p"=>"0","p00_t"=>"0",
										 "p01_n"=>"0","p01_s"=>"0","p01_v"=>"0","p01_k"=>"0","p01_p"=>"0","p01_t"=>"0",
										 "c00_n"=>"0","c00_s"=>"0","c00_v"=>"0","c00_k"=>"0","c00_p"=>"0","c00_t"=>"0",
										 "f00_n"=>"0","f00_s"=>"0","f00_v"=>"0","f00_k"=>"0","f00_p"=>"0","f00_t"=>"0",
										 "s00_n"=>"0","s00_s"=>"0","s00_v"=>"0","s00_k"=>"0","s00_p"=>"0","s00_t"=>"0",
										 "t00_n"=>"0","t00_s"=>"0","t00_v"=>"0","t00_k"=>"0","t00_p"=>"0","t00_t"=>"0",
										 "ss0_n"=>"0","ss0_s"=>"0","ss0_v"=>"0","ss0_k"=>"0","ss0_p"=>"0","ss0_t"=>"0",
										 "o00_n"=>"0","o00_s"=>"0","o00_v"=>"0","o00_k"=>"0","o00_p"=>"0","o00_t"=>"0",
										 "o01_n"=>"0","o01_s"=>"0","o01_v"=>"0","o01_k"=>"0","o01_p"=>"0","o01_t"=>"0",
										 "o02_n"=>"0","o02_s"=>"0","o02_v"=>"0","o02_k"=>"0","o02_p"=>"0","o02_t"=>"0");

/////////////////////////////////////////////////////////////////////////////////////////////////
////BEGIN: Build Team Loop - adds players to the team starting with the highest value players////

////Base SQL statement
$sqlSelect = "SELECT players.espn_id, players.player_name, players.position, dk_stats.salary, round((players.points/dk_stats.salary)*100000) AS value, players.points
							FROM players , dk_stats, dk_csv WHERE dk_csv.csv_id = dk_stats.csv_id AND dk_stats.espn_id = players.espn_id
							AND dk_csv.active = 1 AND players.points > 0 AND players.probable = '1'";

//Grab record count from base SQL statement
$sql = "SELECT count(*) AS rec_count FROM ($sqlSelect) a";
$res = $mysqli->query($sql);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$rec_count = $row['rec_count'];
}
////Initiate variables for use in the loop statment
$y                 = 0;
$var               = 0;
$new_player        = 0;
$no_new_players    = 0;
$step              = 0;
$loop              = 0;
$SP_position       = 0;
$RP_position       = 0;
$C_position        = 0;
$fi_position       = 0;
$se_position       = 0;
$th_position       = 0;
$SS_position       = 0;
$OF_position       = 0;
$unfilled_position = NULL;

//                   BEGIN loop                 //
for ($no_new_players = 0; $no_new_players < 1;) {
	flush();

	//Get list of keys from all players on $best_team//
	$keys = null;
	foreach ($best_team as $key2 => $value2) {
			if ((substr($key2, -2)) == '_k') {
				if ($keys != null) {
				$keys = $keys.",'".$value2."'";
			} else {
				$keys = "'".$value2."'";
			}
			}
	}
	//////////////////////////////////////////////

	//Get total salary of $best_team//////////
	$allSalary = array();
	foreach ($best_team as $key3 => $value3) {
			if ((substr($key3, -2)) == '_s') {
				$allSalary[$key3]=$value3;
			}
	}
	$tot_sal = array_sum($allSalary);
/////////////////////////////////////////

//Check to see if best team has any players//
$best_team_id = NULL;
foreach ($best_team as $key4 => $value4) {
	if (substr($key4, -1) == 'k') {
		if ($value4 === "0") {
	} elseif ($best_team_id === NULL) {
		$best_team_id = "'".$value4."'";
	} else {
		$best_team_id .= " ,'".$value4."'";
	}
	}
}
////If best_team has players, set SQL to select different players////
if ($best_team_id !== NULL) {
  $best_team_id = "AND players.espn_id NOT IN (".$best_team_id.")";
}

$unfilled_position = NULL;
$unfilled = 0;
foreach($best_team as $key => $value){
  if (substr($key, -1) == "n" && $value == "0") {
    $unfilled = 1;
  }
}
  if ($unfilled == 1) {
		if ($best_team['p00_n'] != "0" && $best_team['p01_n'] != "0") {
			$unfilled_position = "'SP','RP'";
		}
	if ($best_team['c00_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'C'";
		} else {
			$unfilled_position .= " ,'C'";
		}
	}
	if ($best_team['f00_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'1B'";
		} else {
			$unfilled_position .= " ,'1B'";
		}
	}
	if ($best_team['s00_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'2B'";
		} else {
			$unfilled_position .= " ,'2B'";
		}
	}
	if ($best_team['t00_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'3B'";
		} else {
			$unfilled_position .= " ,'3B'";
		}
	}
	if ($best_team['ss0_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'SS'";
		} else {
			$unfilled_position .= " ,'SS'";
		}
	}
	if ($best_team['o00_n'] != "0" && $best_team['o01_n'] != "0" && $best_team['o02_n'] != "0") {
		if ($unfilled_position === NULL) {
			$unfilled_position = "'OF'";
		} else {
			$unfilled_position .= " ,'OF'";
		}
	}
}
if ($unfilled_position != NULL) {
$unfilled_position = "AND players.position NOT IN (".$unfilled_position.")";
}

////Check to see if any array values are 0
if ($best_team['p00_n'] == "0" || $best_team['p01_n'] == "0" || $best_team['c00_n'] == "0" || $best_team['f00_n'] == "0" || $best_team['s00_n'] == "0" || $best_team['t00_n'] == "0" ||
    $best_team['ss0_n'] == "0" || $best_team['o00_n'] == "0" || $best_team['o01_n'] == "0" || $best_team['o02_n'] == "0") {
	$orderby = "salary ASC";
} else {
	$orderby = "value DESC";
	$unfilled_position = NULL;
}

////Build final SQL statement////
$sql0 = "$sqlSelect $best_team_id $unfilled_position ORDER BY $orderby LIMIT $var,1";
$res = $mysqli->query($sql0);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
	$t_salary     = '';
	$t_name       = NULL;
	$t_value      = NULL;
	$t_salkey     = NULL;
	$t_position   = NULL;
	$t_points     = NULL;
	$t_points     = $row['points'];
	$t_name       = $row['player_name'];
	$t_position   = trim($row['position']);
	$t_salkey     = floatval($row['espn_id']);
	$t_salary     = floatval($row['salary']);
	$t_value      = $row['value'];
}

////Account for opponents difficulty rating////
if (strpos($t_position, 'P') == true) {
$sql6 = "SELECT pitching_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team_nickname = team.nickname AND players.espn_id = player_stats.espn_id AND players.player_name = '".$t_name."'";
} else {
$sql6 = "SELECT hitting_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team_nickname = team.nickname AND players.espn_id = player_stats.espn_id AND players.player_name = '".$t_name."'";
}
$res = $mysqli->query($sql6);
$res->data_seek(0);
while ($row    = $res->fetch_assoc()) {
$difficulty    = $row['relative_diff'];
}
$t_points   = $t_points + $difficulty;
$t_value    = $t_points/$t_salary*100000;
/////////////////////////////////////////////

	////Replace MYSQL position with new Position////
	$positionArray = array("SP"=>"p0", "RP"=>"p0", "C"=>"c0", "1B"=>"f0", "2B"=>"s0", "3B"=>"t0", "SS"=>"ss", "OF"=>"o0");

	$new_position = NULL;
	foreach ($positionArray as $key => $value) {
		if (strpos($t_position, $key) !== false) {
			if ($new_position !== NULL) {
				$new_position .= " ".$value;
			} else {
				$new_position = $value;
			}
			}
		}
	////////////////////////////////////////////////

	//// Grabs the position and value type off the $new_position variable ////
		$newPosition = array();
		if (strlen($new_position) > 2) {
			$newPosition[] = substr($new_position, 0, 2);
			$newPosition[] = substr($new_position, -1);
		} else {
			$newPosition[] = $new_position;
		}

		////Grabs all player points and salary for the new position////
		foreach($newPosition as $newValue) {
			$p_points    = array();
			$p_salary    = array();
			foreach($best_team as $key => $value){
				//Grab Points from all best_team Pitchers
    		if (substr($key, 0, 2) == $newValue && substr($key, -1) == "p"){
         	$p_points[$key] = $value;
    		}
				//Grab Salary from all best_team pitchers
				if (substr($key, 0, 2) == $newValue && substr($key, -1) == "s"){
         	$p_salary[$key] = $value;
    		}
			}
		//// Loops through however many players there are per the new position////
		$step2 = 0;
		foreach($p_points as $key => $value){
			if ($tot_sal + $t_salary - array_values($p_salary)[$step2] < $sal_cap && isset($p_points) && $t_points > array_values($p_points)[$step2]) {
				$p_key = substr($key, 0, 3);
				if (isset(${$t_position."_position"})) {
				${$t_position."_position"}++;
			} elseif ($t_position == "1B") {
				$fi_position++;
			} elseif ($t_position == "2B") {
				$se_position++;
			} elseif ($t_position == "3B") {
				$th_position++;
			} else {
			}
				$new_player = 1;
				$var        = -1;
				if ($best_team[$p_key."_n"] == 0) {
				$best_team[$p_key."_n"] = $t_name;
				$best_team[$p_key."_s"] = $t_salary;
				$best_team[$p_key."_v"] = $t_value;
				$best_team[$p_key."_k"] = $t_salkey;
				$best_team[$p_key."_p"] = $t_points;
				$best_team[$p_key."_t"] = $t_position;
			  }
				break 2;
    		}
				$step2++;
			}
  	}

// if variable is larger or equal to record count, reset variable and set loop counter//
if ($var >= $rec_count) {
	$loop++;
	if ($new_player == 0) {
		$no_new_players = 1;
	}
///////////////////////////////////////////////////////////////////////////////////////
	$var = 0;
}
$new_player = 0;
$var++;
$y++;
}
//							END loop 						//

//// END second pass of best_team loop ////
//////////////////////////////////////////
?>

<!-- Build the form that displays active players to the user-->
<form action=''>
<div class="row padme" id="team">
  <div class="col-md-8 box center" id="projects">
			<div class="input-group">
			<input type="text" id="tags" class="form-control" placeholder="Enter the player or team name...">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button">Add</button>
			</span>
			</div>
<table class="table table-hover">
  <tbody>
  <tr>
  <th>Team</strong></th><th>Position</th><th>Name</th><th>Salary</th><th>Points</th><th>Opponent</th><th>Difficulty</th>
  </tr>
	<?php

	$sql7 = "SELECT (SUM(pitching_strength)/count(*)) AS avg_pitch_strength, (SUM(hitting_strength)/count(*)) AS avg_hit_strength FROM team";
	$res = $mysqli->query($sql7);
	$res->data_seek(0);
	while ($row    = $res->fetch_assoc()) {
	$avg_hit_strength    = $row['avg_hit_strength'];
	$avg_pitch_strength  = ($row['avg_pitch_strength']);
	}

	$allSalary = array();
	foreach ($best_team as $key3 => $value3) {
			if ((substr($key3, -2)) == '_s') {
				$allSalary[$key3]=$value3;
			}
	}
	$tot_sal = array_sum($allSalary);

	$totPoints = array();
	foreach ($best_team as $key => $value) {
			if ((substr($key, -2)) == '_p') {
				$totPoints[$key]=$key;
				$totPoints[$value]=$value;
			}
	}
	$totPoints = array_sum($totPoints);

	$position = null;
	$name     = null;
	$salary   = null;
	$points   = null;

	$y = 0;
	foreach ($best_team as $key=>$value) {
		//set the key to a value if matched
		if ((substr($key, -2)) == '_t') {
			$position = $value;
    }
		if ((substr($key, -2)) == '_n') {
			$name = $value;
		}
		if ((substr($key, -2)) == '_s') {
			$salary = $value;
    }
		if ((substr($key, -2)) == '_p') {
			$points = $value;
    }

	//each player has 6 attributes, $y waits until all 6 attributes are grabbed to print table
	if ($position != null && $name != null && $salary != null && $points != null) {

		////Grab the opposing teams difficulty for that position
		//Example: Grab how many points a pitcher averages against the opposing team
		if (strpos($position, 'P') == true) {
		$sql6 = "SELECT nickname, pitching_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team_nickname = team.nickname AND players.espn_id = player_stats.espn_id AND players.player_name = '".$name."'";
	  } else {
		$sql6 = "SELECT nickname, hitting_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team_nickname = team.nickname AND players.espn_id = player_stats.espn_id AND players.player_name = '".$name."'";
	  }
		$res = $mysqli->query($sql6);
		$res->data_seek(0);
		while ($row    = $res->fetch_assoc()) {
		$difficulty    = $row['relative_diff'];
		$team_nickname = strtoupper($row['nickname']);
		$opponent = strtoupper($row['opponent']);
		}

		echo "<tr><td style='width: 5%;'>";
		?><img class="two" src="http://a.espncdn.com/combiner/i?img=/i/teamlogos/mlb/500/<?php echo $team_nickname;?>.png&amp;h=150&amp;w=150"><?php
		echo "</td>";
		echo "<td>";
		echo $position;
		echo "</td>";
		echo "<td>";
		echo $name;
		echo "</td>";
		echo "<td>";
		echo number_format($salary);
		echo "</td>";
		echo "<td>";
		echo $points;
		echo "</td>";
		echo "<td>";
		echo $opponent;
		echo "</td>";
		echo "<td>";
		echo $difficulty;
		echo "</td></tr>";

		//reset values for next loop
		$position = null;
		$name     = null;
		$salary   = null;
		$points   = null;
		$y = 0;
	}
		//loop through first player set
		$y++;
	}
	 ?>
  <tr><td><strong>Totals: <strong><td><td><td><strong>$<?php echo number_format($tot_sal); ?></strong></td><td><strong><?php echo $totPoints;?> Points</strong></td><td><td><tr>
	<td></td><td></td><td></td><td></td><td></td><td></td><td></td>
	</tr>
	<tr></tr>
	</tbody>
</table>
</div>
</div>
</form>
<?php
$sql13 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50";
$res = $mysqli->query($sql13);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$all_fifty_entries   = $row['times_entered'];
$all_fifty_opponents = $row['contestants'];
$all_fifty_placed    = $row['percentage_placed'];
}
$sql14 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 2";
$res = $mysqli->query($sql13);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$all_two_entries   = $row['times_entered'];
$all_two_opponents = $row['contestants'];
$all_two_placed    = $row['percentage_placed'];
}
$sql13 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100";
$res = $mysqli->query($sql13);
$res->data_seek(0);
while ($row = $res->fetch_assoc()) {
$all_hundred_entries   = $row['times_entered'];
$all_hundred_opponents = $row['contestants'];
$all_hundred_placed    = $row['percentage_placed'];
}
 ?>


<script>
$( document ).ready(function() {
	$('#entries').text('<?php echo $all_two_entries; ?>');
 $('#opponents').text('<?php echo $all_two_opponents; ?>');
 $('#placed').text('<?php echo $all_two_placed; ?>');
});
$('.head').click(function(){
 var $this = $(this);
 $this.toggleClass('head');
 if($this.hasClass('head')){
	 $this.text('Head to Head');
	 $('#entries').text('<?php echo $all_two_entries; ?>');
	 $('#opponents').text('<?php echo $all_two_opponents; ?>');
	 $('#placed').text('<?php echo $all_two_placed; ?>');
 }
});
</script>

 <form action=''>
 <div class="row padme" id="team">
   <div class="col-md-6 box center">
		 <table class="table table-hover">
		   <tbody>
				 <tr>
					 <th></th>
		      <th>Historical Results<button class="head">Head to Head</button></th>
					<th></th>
				 </tr>
			 </tr>
			 <th>Entries</th><th>Avg. Contestants</th><th>Avg. Placed</th>
		 </tr>
				 <tr>
				 <td id="entries"></td><td id="opponents"></td><td id="placed"></td>
			   </tr>
		</tbody>
		</table>
	</div>
</div>
<input class="btn btn-transparent" target="_blank" name="opponent_team" value="Opponent team" onclick=""/>

<?php
////Functions/////
//Minimum point player from $best_team//
function minPoint($best_team) {
 //Minimum point player from $best_team//
 $allPoints = array();
 foreach ($best_team as $key => $value) {
		 if ((substr($key, -2)) == '_p') {
			 $allPoints[$key]=$key;
			 $allPoints[$value]=$value;
		 }
 }
 $minPoints = min($allPoints);
 return $minPoints;
}
 ?>
<script>
//autocomplete function
$(function() {
	$(".form-control").autocomplete({
		source: "search.php",
		minLength: 1
	});
});

//Dropdown autofills with the selected value
$( "#dd_qb" ).click(function() {
$("#btnAddProfile").html('G <span class="caret"></span></button>');
});
</script>
</body>
</html>
