## Select percentage placed in 50 player contests
SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 50
## Select percentage placed in 100 player contests
SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100
## Select percentage placed in  > 100 player contests
SELECT count(*) as times_entered, round(sum(entries)/count(*)) as contestants, round( ((sum(placed)/count(*)) / (sum(entries)/count(*)) ) * 100) AS percentage_placed FROM results WHERE entries = 100
