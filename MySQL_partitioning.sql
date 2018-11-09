DELIMITER $$

DROP PROCEDURE IF EXISTS UpdatePartitions $$

-- Procedure to delete old partitions and create new ones based on a given date.
-- partitions older than (today_date - days_past) will be dropped
-- enough new partitions will be made to cover until (today_date + days_future)
CREATE PROCEDURE UpdatePartitions (dbname TEXT, tblname TEXT, today_date DATE, days_past INT, days_future INT)
BEGIN

DECLARE maxpart_date date;
DECLARE partition_count int;
DECLARE minpart date;
DECLARE droppart_sql date;
DECLARE newpart_date date;
DECLARE newpart_sql varchar(500); 

SELECT COUNT(*)
INTO partition_count
FROM INFORMATION_SCHEMA.PARTITIONS
WHERE TABLE_NAME=tblname
AND TABLE_SCHEMA=dbname;

-- SELECT partition_count;

-- first, deal with pruning old partitions
WHILE (partition_count > days_past + days_future)
DO
-- optionally, do something here to deal with the parition you're dropping, e.g.
-- copy the data into an archive table

 SELECT STR_TO_DATE(MIN(PARTITION_DESCRIPTION), '''%Y-%m-%d''')
   INTO minpart
   FROM INFORMATION_SCHEMA.PARTITIONS
   WHERE TABLE_NAME=tblname
   AND TABLE_SCHEMA=dbname;

-- SELECT minpart;

 SET @sql := CONCAT('ALTER TABLE '
                    , tblname
                    , ' DROP PARTITION p'
                    , CAST(((minpart - INTERVAL 1 DAY)+0) as char(8))
                    , ';');

 -- SELECT @sql;
 PREPARE stmt FROM @sql;
 EXECUTE stmt;
 DEALLOCATE PREPARE stmt;

SELECT COUNT(*)
  INTO partition_count
  FROM INFORMATION_SCHEMA.PARTITIONS
  WHERE TABLE_NAME=tblname
  AND TABLE_SCHEMA=dbname;

-- SELECT partition_count;

END WHILE;

SELECT STR_TO_DATE(MAX(PARTITION_DESCRIPTION), '''%Y-%m-%d''')
INTO maxpart_date
FROM INFORMATION_SCHEMA.PARTITIONS
WHERE TABLE_NAME=tblname
AND TABLE_SCHEMA=dbname;

-- select maxpart_date;
-- create enough partitions for at least the next days_future days
WHILE (maxpart_date < today_date + INTERVAL days_future DAY)
DO

-- select 'here1';
SET newpart_date := maxpart_date + INTERVAL 1 DAY;
SET @sql := CONCAT('ALTER TABLE '
                    , tblname
                    , ' ADD PARTITION (PARTITION p'
                    , CAST(((newpart_date - INTERVAL 1 DAY)+0) as char(8))
                    , ' VALUES LESS THAN ('''
                    , newpart_date
                    , '''));');

-- SELECT @sql;
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT STR_TO_DATE(MAX(PARTITION_DESCRIPTION), '''%Y-%m-%d''')
  INTO maxpart_date
  FROM INFORMATION_SCHEMA.PARTITIONS
  WHERE TABLE_NAME=tblname
  AND TABLE_SCHEMA=dbname;

SET maxpart_date := newpart_date;

END WHILE;

END $$

DELIMITER ;

DROP PROCEDURE IF EXISTS partition_logs;
DELIMITER $$
CREATE PROCEDURE partition_logs(in tblname varchar(100),in partsize int,in lwater int,in hwater int)
BEGIN
DECLARE p_start_time bigint;
DECLARE p_end_time bigint;
DECLARE p_start_name bigint;
DECLARE p_end_name bigint;
DECLARE p_low_water bigint;
DECLARE p_high_water bigint;
DECLARE p_new_name bigint;
DECLARE p_new_time bigint;
DECLARE cnt int;
DECLARE deld int;
DECLARE partition_column varchar(100);
set partsize = partsize * 3600; Ñ turn the passed partition size in hours, into the unixtimestamp.
SELECT cast((substr(partition_name from 2)) as SIGNED ) into p_start_name FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position limit 1;
SELECT cast((substr(partition_name from 2)) as SIGNED ) into p_end_name FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position desc limit 1;
SELECT partition_description into p_start_time FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position limit 1;
SELECT partition_description into p_end_time FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position desc limit 1;
SELECT partition_expression into partition_column from information_schema.PARTITIONS where table_name = tblname limit 1;
select unix_timestamp(date_sub(date(now()), interval lwater day)) into p_low_water;
select unix_timestamp(date_add(date(now()), interval hwater day)) into p_high_water;
set @plw := p_low_water;
set @tbl := tblname;
set @col := partition_column;
set @ins := concat(Ôreplace into Ô,@tbl,Õ_archive select * from Ô,@tbl,Õ where Ô,@col,Õ < Ô,@plw);
prepare instmnt from @ins;
execute instmnt;
select p_start_time,p_low_water;
set deld = 0;
while (p_start_time < p_low_water)
do
select p_start_time,p_low_water;
set @pstart := p_start_name;
set @droppart := concat(ÔALTER TABLE Ô,@tbl,Õ drop partition pÕ,@pstart);
prepare dropstate from @droppart;
execute dropstate;
SELECT partition_description into p_start_time FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position limit 1;
SELECT cast((substr(partition_name from 2)) as SIGNED ) into p_start_name FROM information_schema.PARTITIONS where table_name = tblname order by partition_ordinal_position limit 1;
set deld = deld+1;
END WHILE;
set cnt=0;
while (p_end_time < p_high_water)
do
set p_end_name = p_end_name + 1;
IF (p_end_time < p_low_water)
THEN set p_end_time = p_low_water;
ELSE set p_end_time = p_end_time + partsize;
END IF;
set @pendname := p_end_name;
set @phighwater := p_end_time;
set @alter_log := concat(ÔALTER TABLE Ô,@tbl,Õ ADD PARTITION (PARTITION pÕ,@pendname,Õ VALUES LESS THAN(Ô,@phighwater,Õ))Õ);
prepare stmnt from @alter_log;
execute stmnt;
set cnt = cnt+1;
END WHILE;
select partsize as ÕsecondsÕ,p_low_water as Õstart atÕ,p_high_water as Õstop atÕ,cnt as Ôpartitions createdÕ,deld as Ôpartitions droppedÕ;
END$$