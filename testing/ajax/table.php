<html>
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
</head>
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
  <td><strong>Position</strong</td> <td><strong>Name</strong</td> <td><strong>Salary</strong></td><td><strong>Points</strong></td>
  </tr>
	<?php

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
	foreach($best_team as $key=>$value)
	{
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
		echo "<tr><td>";
		echo $position;
		echo "</td>";
		echo "<td>";
		echo $name;
		echo "</td>";
		echo "<td>";
		echo $salary;
		echo "</td>";
		echo "<td>";
		echo $points;
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
  <tr><td><td><td><strong>Total Salary <?php echo $tot_sal; ?></strong></td><td><strong>Total Points: <?php echo $totPoints;?></strong></td><tr>
	<td></td><td></td><td></td><td></td>
	</tr>
	<tr></tr>
	</tbody>
</table>
</div>
</div>
</form>
