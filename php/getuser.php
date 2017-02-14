<!DOCTYPE html>
<html>
<head>
<style>
table {
    width: 100%;
    border-collapse: collapse;
}

table, td, th {
    border: 1px solid black;
    padding: 5px;
}

th {text-align: left;}
</style>
</head>
<body>

<?php
$q = intval($_GET['q']);

$mysqli = new mysqli("localhost", "root", "root", "dkings");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
if ($q == "1") {
  $sql2   = "SELECT total as times_entered, 2 as contestants, round(count_w/total*100) as percentage_placed FROM (SELECT count(*) as count_w FROM results WHERE entries = '2' AND placed = 1 AND entry_date > ADDDATE(NOW(), INTERVAL -1 DAY)) a, (SELECT count(*) as total FROM results WHERE entries = 2 AND entry_date > ADDDATE(NOW(), INTERVAL -1 DAY)) b";
  $sql50  = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50 AND entry_date > ADDDATE(NOW(), INTERVAL -1 DAY)";
  $sql100 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100 AND entry_date > ADDDATE(NOW(), INTERVAL -1 DAY)";
} elseif ($q == "7") {
  $sql2   = "SELECT total as times_entered, 2 as contestants, round(count_w/total*100) as percentage_placed FROM (SELECT count(*) as count_w FROM results WHERE entries = '2' AND placed = 1 AND entry_date > ADDDATE(NOW(), INTERVAL -1 WEEK)) a, (SELECT count(*) as total FROM results WHERE entries = 2 AND entry_date > ADDDATE(NOW(), INTERVAL -1 WEEK)) b";
  $sql50  = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50 AND entry_date > ADDDATE(NOW(), INTERVAL -1 WEEK)";
  $sql100 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100 AND entry_date > ADDDATE(NOW(), INTERVAL -1 WEEK)";
} else {
  $sql2   = "SELECT total as times_entered, 2 as contestants, round(count_w/total*100) as percentage_placed FROM (SELECT count(*) as count_w FROM results WHERE entries = '2' AND placed = 1 AND entry_date > ADDDATE(NOW(), INTERVAL -1 MONTH)) a, (SELECT count(*) as total FROM results WHERE entries = 2 AND entry_date > ADDDATE(NOW(), INTERVAL -1 MONTH)) b";
  $sql50  = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50 AND entry_date > ADDDATE(NOW(), INTERVAL -1 MONTH)";
  $sql100 = "SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100 AND entry_date > ADDDATE(NOW(), INTERVAL -1 MONTH)";
}
$res2   = $mysqli->query($sql2);
$res50  = $mysqli->query($sql50);
$res100 = $mysqli->query($sql100);
echo "<table>
<tr>
<th>Contestants</th>
<th>Entries</th>
<th>Placed</th>
</tr>";
while($row = mysqli_fetch_array($res2)) {
    echo "<tr>";
    echo "<td>" . $row['contestants'] . "</td>";
    echo "<td>" . $row['times_entered'] . "</td>";
    echo "<td>" . $row['percentage_placed'] . "</td>";
    echo "</tr>";
}
while($row = mysqli_fetch_array($res50)) {
    echo "<tr>";
    echo "<td>" . $row['contestants'] . "</td>";
    echo "<td>" . $row['times_entered'] . "</td>";
    echo "<td>" . $row['percentage_placed'] . "</td>";
    echo "</tr>";
}
while($row = mysqli_fetch_array($res100)) {
    echo "<tr>";
    echo "<td>" . $row['contestants'] . "</td>";
    echo "<td>" . $row['times_entered'] . "</td>";
    echo "<td>" . $row['percentage_placed'] . "</td>";
    echo "</tr>";
}
echo "</table>";
mysqli_close($mysqli);
?>
</body>
</html>
