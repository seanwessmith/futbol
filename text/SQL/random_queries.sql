

## SELECT points, salary, total_points FROM all tables
SELECT sum(total_score), player_name, dk_detail.salary, dk_detail.points FROM players, pitcher_stats, dk_detail, dk_main WHERE players.player_id = pitcher_stats.player_id AND dk_detail.player_id = dk_main.player_id AND dk_main.name = players.player_name GROUP BY player_name ORDER BY `dk_detail`.`salary`  DESC

## SELECT average points FROM dk_csv
SELECT name, points, dk_main.player_id,  (sum(points) / count(*)) AS average_points, (MAX(points) - MIN(points)) AS points_differance FROM dk_detail, dk_main WHERE dk_main.player_id = dk_detail.player_id GROUP BY dk_detail.player_id ORDER BY `points_differance`  DESC

## SELECT total points per team FROM dk_detail
SELECT (a.points/count(*)) AS avg_sum ,count(*) AS player_count, sum(a.points) AS point_sum, a.team FROM (SELECT sum(points) AS points, team FROM dk_detail, dk_main WHERE dk_detail.player_id = dk_main.player_id AND position NOT LIKE '%P%'  GROUP BY dk_main.player_id) a GROUP BY team ORDER BY `avg_sum`  DESC

## Select all players from certain date, and how many points they received
SELECT pitcher_stats.total_score, players.player_name FROM players, pitcher_stats, probable_player_history WHERE players.player_id = pitcher_stats.player_id AND pitcher_stats.player_id = probable_player_history.player_id AND pitcher_stats.game_date = probable_player_history.game_date AND probable_player_history.game_date = '2016-05-11'
