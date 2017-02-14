## SELECT duplicate player_stats
SELECT espn_id, game_date, count(*) FROM `player_stats` GROUP BY game_date, espn_id ORDER BY count(*) DESC

                                    *********DELETION STATEMENTS*********

## Remove duplicate players
DELETE  t1.* FROM    players t1 LEFT JOIN (SELECT min(espn_id) as espn_id FROM players group BY player_name) t2 ON t1.espn_id IN (t2.espn_id) WHERE   t2.espn_id IS NULL

## Remove duplicate FROM pitcher_stats
DELETE player_stats.* FROM `player_stats` left join (SELECT max(game_id) AS game_id FROM player_stats GROUP BY game_date, player_id) a ON a.game_id = player_stats.game_id WHERE a.game_id IS NULL
