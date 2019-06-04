-- MySQL dump 10.16  Distrib 10.1.38-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mariadb    Database: temperatures
-- ------------------------------------------------------
-- Server version	10.3.14-MariaDB-1:10.3.14+maria~bionic

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `error`
--

DROP TABLE IF EXISTS `error`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error`
--

LOCK TABLES `error` WRITE;
/*!40000 ALTER TABLE `error` DISABLE KEYS */;
/*!40000 ALTER TABLE `error` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_15min`
--

DROP TABLE IF EXISTS `error_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_15min`
--

LOCK TABLES `error_15min` WRITE;
/*!40000 ALTER TABLE `error_15min` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_5min`
--

DROP TABLE IF EXISTS `error_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_5min`
--

LOCK TABLES `error_5min` WRITE;
/*!40000 ALTER TABLE `error_5min` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_60min`
--

DROP TABLE IF EXISTS `error_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_60min`
--

LOCK TABLES `error_60min` WRITE;
/*!40000 ALTER TABLE `error_60min` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newtable`
--

DROP TABLE IF EXISTS `newtable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newtable` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newtable`
--

LOCK TABLES `newtable` WRITE;
/*!40000 ALTER TABLE `newtable` DISABLE KEYS */;
/*!40000 ALTER TABLE `newtable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newtable_15min`
--

DROP TABLE IF EXISTS `newtable_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newtable_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newtable_15min`
--

LOCK TABLES `newtable_15min` WRITE;
/*!40000 ALTER TABLE `newtable_15min` DISABLE KEYS */;
/*!40000 ALTER TABLE `newtable_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newtable_5min`
--

DROP TABLE IF EXISTS `newtable_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newtable_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newtable_5min`
--

LOCK TABLES `newtable_5min` WRITE;
/*!40000 ALTER TABLE `newtable_5min` DISABLE KEYS */;
/*!40000 ALTER TABLE `newtable_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newtable_60min`
--

DROP TABLE IF EXISTS `newtable_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newtable_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newtable_60min`
--

LOCK TABLES `newtable_60min` WRITE;
/*!40000 ALTER TABLE `newtable_60min` DISABLE KEYS */;
/*!40000 ALTER TABLE `newtable_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp1`
--

DROP TABLE IF EXISTS `temp1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp1` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp1`
--

LOCK TABLES `temp1` WRITE;
/*!40000 ALTER TABLE `temp1` DISABLE KEYS */;
INSERT INTO `temp1` VALUES (1558791624,0.21),(1558791774,0.21),(1559318880,14.21),(1559318940,14.21),(1559319002,14.21),(1559319061,14.21),(1559319121,14.21),(1559319181,14.21),(1559319241,14.21),(1559319301,14.21),(1559319361,14.21),(1559319421,14.21),(1559319481,14.21),(1559319541,14.21),(1559319601,14.21),(1559319661,14.21),(1559319721,14.21);
/*!40000 ALTER TABLE `temp1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp1_15min`
--

DROP TABLE IF EXISTS `temp1_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp1_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp1_15min`
--

LOCK TABLES `temp1_15min` WRITE;
/*!40000 ALTER TABLE `temp1_15min` DISABLE KEYS */;
INSERT INTO `temp1_15min` VALUES (1558791774,0.21),(1559319241,14.21);
/*!40000 ALTER TABLE `temp1_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp1_5min`
--

DROP TABLE IF EXISTS `temp1_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp1_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp1_5min`
--

LOCK TABLES `temp1_5min` WRITE;
/*!40000 ALTER TABLE `temp1_5min` DISABLE KEYS */;
INSERT INTO `temp1_5min` VALUES (1558791774,0.21),(1559318940,14.21),(1559319241,14.21),(1559319541,14.21),(1559319601,14.21);
/*!40000 ALTER TABLE `temp1_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp1_60min`
--

DROP TABLE IF EXISTS `temp1_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp1_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp1_60min`
--

LOCK TABLES `temp1_60min` WRITE;
/*!40000 ALTER TABLE `temp1_60min` DISABLE KEYS */;
INSERT INTO `temp1_60min` VALUES (1558791774,0.21);
/*!40000 ALTER TABLE `temp1_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp3`
--

DROP TABLE IF EXISTS `temp3`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp3` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp3`
--

LOCK TABLES `temp3` WRITE;
/*!40000 ALTER TABLE `temp3` DISABLE KEYS */;
INSERT INTO `temp3` VALUES (1558791624,0.114),(1558791774,0.114),(1559318881,3.114),(1559318940,3.114),(1559319002,3.114),(1559319061,3.114),(1559319121,3.114),(1559319181,3.114),(1559319241,3.114),(1559319301,3.114),(1559319361,3.114),(1559319421,3.114),(1559319481,3.114),(1559319541,3.114),(1559319601,3.114),(1559319661,3.114),(1559319721,3.114);
/*!40000 ALTER TABLE `temp3` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp3_15min`
--

DROP TABLE IF EXISTS `temp3_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp3_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp3_15min`
--

LOCK TABLES `temp3_15min` WRITE;
/*!40000 ALTER TABLE `temp3_15min` DISABLE KEYS */;
INSERT INTO `temp3_15min` VALUES (1558791774,0.114),(1559319241,3.114),(1559319301,3.114);
/*!40000 ALTER TABLE `temp3_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp3_5min`
--

DROP TABLE IF EXISTS `temp3_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp3_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp3_5min`
--

LOCK TABLES `temp3_5min` WRITE;
/*!40000 ALTER TABLE `temp3_5min` DISABLE KEYS */;
INSERT INTO `temp3_5min` VALUES (1558791774,0.114),(1559318940,3.114),(1559319241,3.114),(1559319541,3.114),(1559319601,3.114);
/*!40000 ALTER TABLE `temp3_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp3_60min`
--

DROP TABLE IF EXISTS `temp3_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp3_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp3_60min`
--

LOCK TABLES `temp3_60min` WRITE;
/*!40000 ALTER TABLE `temp3_60min` DISABLE KEYS */;
INSERT INTO `temp3_60min` VALUES (1558791774,0.114);
/*!40000 ALTER TABLE `temp3_60min` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-05-31 18:22:54
