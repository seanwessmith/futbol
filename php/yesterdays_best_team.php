<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta content="width=device-width,initial-scale=1.0,user-scalable=no,minimum-scale=1.0,maximum-scale=1.0" id="viewport" name="viewport">
	<link rel="stylesheet" type="text/css" href="../css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="../css/wynd.css">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<script src="../js/migrate.js"></script>
	<script src="../js/bootstrap.min.js"></script>
	<script src="../js/main.js"></script>
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
$sqlSelect = "SELECT players.player_id, players.player_name, players.position, players.salary, round((player_stats.total_score/players.salary)*100000) AS value, player_stats.total_score AS points
FROM players, player_stats WHERE players.points > 0 AND player_stats.player_id = players.player_id AND player_stats.game_date = curdate() - 1";

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
  $best_team_id = "AND players.player_id NOT IN (".$best_team_id.")";
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
	$t_salkey     = floatval($row['player_id']);
	$t_salary     = floatval($row['salary']);
	$t_value      = $row['value'];
}

////Account for opponents difficulty rating////
$t_name     = str_replace("'","''", $t_name);
if (strpos($t_position, 'P') == true) {
$sql6 = "SELECT pitching_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team = team.team_name AND players.player_id = player_stats.player_id AND players.player_name = '".$t_name."'";
} else {
$sql6 = "SELECT hitting_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team = team.team_name AND players.player_id = player_stats.player_id AND players.player_name = '".$t_name."'";
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
		$sql6 = "SELECT nickname, pitching_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team = team.team_name AND players.player_id = player_stats.player_id AND players.player_name = '".$name."'";
	  } else {
		$sql6 = "SELECT nickname, hitting_strength AS relative_diff, player_stats.opponent FROM team, players, player_stats WHERE players.team = team.team_name AND players.player_id = player_stats.player_id AND players.player_name = '".$name."'";
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
$times_entered     = $row['times_entered'];
$contestants       = $row['contestants'];
$percentage_placed = $row['percentage_placed'];
}
 ?>
 <form action=''>
 <div class="row padme" id="team">
   <div class="col-md-6 box center">
		 <table class="table table-hover">
		   <tbody>
				 <tr>
					 <th></th>
		      <th>Historical Results</th>
					<th></th>
				 </tr>
			 </tr>
			 <th>Entries</th><th>Avg. Contestants</th><th>Avg. Placed</th>
		 </tr>
				 <tr>
					 <?php
					 $sql13 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50";
					 $res = $mysqli->query($sql13);
					 $res->data_seek(0);
					 while ($row = $res->fetch_assoc()) {
					 $times_entered     = $row['times_entered'];
					 $contestants       = $row['contestants'];
					 $percentage_placed = $row['percentage_placed'];
					 }
					  ?>
				 <td><?php echo $times_entered; ?></td><td><?php echo $contestants; ?></td><td><?php echo $percentage_placed; ?></td>
			   </tr>
				 <tr>
					 <?php
					 $sql13 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100";
					 $res = $mysqli->query($sql13);
					 $res->data_seek(0);
					 while ($row = $res->fetch_assoc()) {
					 $times_entered     = $row['times_entered'];
					 $contestants       = $row['contestants'];
					 $percentage_placed = $row['percentage_placed'];
					 }
					  ?>
				 <td><?php echo $times_entered; ?></td><td><?php echo $contestants; ?></td><td><?php echo $percentage_placed; ?></td>
			   </tr>
				 <tr>
					 <?php
					 $sql13 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries > 100";
					 $res = $mysqli->query($sql13);
					 $res->data_seek(0);
					 while ($row = $res->fetch_assoc()) {
					 $times_entered     = $row['times_entered'];
					 $contestants       = $row['contestants'];
					 $percentage_placed = $row['percentage_placed'];
					 }
					  ?>
				 <td><?php echo $times_entered; ?></td><td><?php echo $contestants; ?></td><td><?php echo $percentage_placed; ?></td>
			   </tr>
		</tbody>
		</table>
	</div>
</div>
<!-- <input class="btn btn-transparent" target="_blank" name="opponent_team" value="Opponent team" onclick="window.open('./php/test2.php')"/> -->

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
