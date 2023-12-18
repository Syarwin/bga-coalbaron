-- TEST : insert fake meeples in bank spaces to test possible spaces
-- 23 meeples in bank_4, so no player can overbid !
-- 21 meeples in bank_1, but whatever the number, any player can come

-- fake player for test 
-- SELECT  @playerID :=player_id  FROM `player` where player_no = 2 ;
SELECT  @playerID :=99999  FROM `player` where player_no = 2 ;
-- delete only for fake player !
delete from meeples where player_id =  @playerID;

INSERT INTO `meeples` ( `meeple_state`, `meeple_location`, `player_id`, `type`) VALUES 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'), 
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'),
( '0', 'bank_1', @playerID, 'worker'), 

( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'),
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 
( '0', 'bank_4', @playerID, 'worker'), 

( '0', 'bank_6', @playerID, 'worker'), 
( '0', 'bank_6', @playerID, 'worker') ;

SELECT * FROM `meeples` LIMIT 100;

SELECT COUNT(*),meeple_location FROM `meeples` group by meeple_location; 
