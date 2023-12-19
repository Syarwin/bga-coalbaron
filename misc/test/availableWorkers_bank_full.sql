-- TEST : move all player2 meeples in bank spaces to test possible spaces

SELECT  @player1 :=player_id  FROM `player` where player_no = 1 ;
SELECT  @player2 :=player_id FROM `player` where player_no = 2 ;

-- PLACE ALL PLAYER 2 WORKERS in 'bank_6'
update meeples set meeple_location = 'bank_6' , player_id=@player2
where (meeple_location=CONCAT('reserve-',@player2) or player_id=@player2  ) and type ='worker';


SELECT * FROM `meeples` LIMIT 100;

SELECT COUNT(*),meeple_location FROM `meeples` group by meeple_location; 
