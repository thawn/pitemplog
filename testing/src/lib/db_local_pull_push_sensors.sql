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
INSERT INTO `temp1` VALUES (1558793641,0.21),(1558793701,0.21),(1558793761,0.21),(1558793821,0.21),(1558793852,0.21),(1558793881,0.21),(1558793886,0.21);
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
INSERT INTO `temp1_15min` VALUES (1558793641,0.21),(1558793886,0.21);
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
INSERT INTO `temp1_5min` VALUES (1558793641,0.21),(1558793886,0.21);
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
INSERT INTO `temp1_60min` VALUES (1558793886,0.21);
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
INSERT INTO `temp3` VALUES (1558793641,0.114),(1558793701,0.114),(1558793761,0.114),(1558793821,0.114),(1558793852,0.114),(1558793881,0.114),(1558793886,0.114);
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
INSERT INTO `temp3_15min` VALUES (1558793641,0.114),(1558793886,0.114);
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
INSERT INTO `temp3_5min` VALUES (1558793641,0.114),(1558793886,0.114);
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
INSERT INTO `temp3_60min` VALUES (1558793886,0.114);
/*!40000 ALTER TABLE `temp3_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp4`
--

DROP TABLE IF EXISTS `temp4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp4` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp4`
--

LOCK TABLES `temp4` WRITE;
/*!40000 ALTER TABLE `temp4` DISABLE KEYS */;
INSERT INTO `temp4` VALUES (1558793641,12.457),(1558793701,0.457),(1558793702,12.457),(1558793761,12.457),(1558793761,0.457),(1558793821,12.457),(1558793821,0.457),(1558793853,12.457),(1558793881,12.457),(1558793881,0.457),(1558793887,12.457);
/*!40000 ALTER TABLE `temp4` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp4_15min`
--

DROP TABLE IF EXISTS `temp4_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp4_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp4_15min`
--

LOCK TABLES `temp4_15min` WRITE;
/*!40000 ALTER TABLE `temp4_15min` DISABLE KEYS */;
INSERT INTO `temp4_15min` VALUES (1558793641,12.457),(1558793887,7.657);
/*!40000 ALTER TABLE `temp4_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp4_5min`
--

DROP TABLE IF EXISTS `temp4_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp4_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp4_5min`
--

LOCK TABLES `temp4_5min` WRITE;
/*!40000 ALTER TABLE `temp4_5min` DISABLE KEYS */;
INSERT INTO `temp4_5min` VALUES (1558793641,12.457),(1558793887,7.657);
/*!40000 ALTER TABLE `temp4_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp4_60min`
--

DROP TABLE IF EXISTS `temp4_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp4_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp4_60min`
--

LOCK TABLES `temp4_60min` WRITE;
/*!40000 ALTER TABLE `temp4_60min` DISABLE KEYS */;
INSERT INTO `temp4_60min` VALUES (1558793887,8.09336);
/*!40000 ALTER TABLE `temp4_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp5`
--

DROP TABLE IF EXISTS `temp5`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp5` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp5`
--

LOCK TABLES `temp5` WRITE;
/*!40000 ALTER TABLE `temp5` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp5` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp5_15min`
--

DROP TABLE IF EXISTS `temp5_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp5_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp5_15min`
--

LOCK TABLES `temp5_15min` WRITE;
/*!40000 ALTER TABLE `temp5_15min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp5_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp5_5min`
--

DROP TABLE IF EXISTS `temp5_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp5_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp5_5min`
--

LOCK TABLES `temp5_5min` WRITE;
/*!40000 ALTER TABLE `temp5_5min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp5_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp5_60min`
--

DROP TABLE IF EXISTS `temp5_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp5_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp5_60min`
--

LOCK TABLES `temp5_60min` WRITE;
/*!40000 ALTER TABLE `temp5_60min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp5_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp6`
--

DROP TABLE IF EXISTS `temp6`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp6` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp6`
--

LOCK TABLES `temp6` WRITE;
/*!40000 ALTER TABLE `temp6` DISABLE KEYS */;
INSERT INTO `temp6` VALUES (1558793641,24.074),(1558793701,0.074),(1558793701,24.074),(1558793761,24.074),(1558793761,0.074),(1558793821,24.074),(1558793821,0.074),(1558793853,24.074),(1558793881,24.074),(1558793881,0.074),(1558793887,24.074);
/*!40000 ALTER TABLE `temp6` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp6_15min`
--

DROP TABLE IF EXISTS `temp6_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp6_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp6_15min`
--

LOCK TABLES `temp6_15min` WRITE;
/*!40000 ALTER TABLE `temp6_15min` DISABLE KEYS */;
INSERT INTO `temp6_15min` VALUES (1558793641,24.074),(1558793887,14.474);
/*!40000 ALTER TABLE `temp6_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp6_5min`
--

DROP TABLE IF EXISTS `temp6_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp6_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp6_5min`
--

LOCK TABLES `temp6_5min` WRITE;
/*!40000 ALTER TABLE `temp6_5min` DISABLE KEYS */;
INSERT INTO `temp6_5min` VALUES (1558793641,24.074),(1558793887,14.474);
/*!40000 ALTER TABLE `temp6_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp6_60min`
--

DROP TABLE IF EXISTS `temp6_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp6_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp6_60min`
--

LOCK TABLES `temp6_60min` WRITE;
/*!40000 ALTER TABLE `temp6_60min` DISABLE KEYS */;
INSERT INTO `temp6_60min` VALUES (1558793887,15.3467);
/*!40000 ALTER TABLE `temp6_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp7`
--

DROP TABLE IF EXISTS `temp7`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp7` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp7`
--

LOCK TABLES `temp7` WRITE;
/*!40000 ALTER TABLE `temp7` DISABLE KEYS */;
INSERT INTO `temp7` VALUES (1558793701,0.169),(1558793761,0.169),(1558793761,0.169),(1558793821,0.169),(1558793821,0.169),(1558793881,0.169),(1558793881,0.169);
/*!40000 ALTER TABLE `temp7` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp7_15min`
--

DROP TABLE IF EXISTS `temp7_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp7_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp7_15min`
--

LOCK TABLES `temp7_15min` WRITE;
/*!40000 ALTER TABLE `temp7_15min` DISABLE KEYS */;
INSERT INTO `temp7_15min` VALUES (1558793881,0.169);
/*!40000 ALTER TABLE `temp7_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp7_5min`
--

DROP TABLE IF EXISTS `temp7_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp7_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp7_5min`
--

LOCK TABLES `temp7_5min` WRITE;
/*!40000 ALTER TABLE `temp7_5min` DISABLE KEYS */;
INSERT INTO `temp7_5min` VALUES (1558793881,0.169);
/*!40000 ALTER TABLE `temp7_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp7_60min`
--

DROP TABLE IF EXISTS `temp7_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp7_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp7_60min`
--

LOCK TABLES `temp7_60min` WRITE;
/*!40000 ALTER TABLE `temp7_60min` DISABLE KEYS */;
INSERT INTO `temp7_60min` VALUES (1558793881,0.169);
/*!40000 ALTER TABLE `temp7_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp8`
--

DROP TABLE IF EXISTS `temp8`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp8` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp8`
--

LOCK TABLES `temp8` WRITE;
/*!40000 ALTER TABLE `temp8` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp8` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp8_15min`
--

DROP TABLE IF EXISTS `temp8_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp8_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp8_15min`
--

LOCK TABLES `temp8_15min` WRITE;
/*!40000 ALTER TABLE `temp8_15min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp8_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp8_5min`
--

DROP TABLE IF EXISTS `temp8_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp8_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp8_5min`
--

LOCK TABLES `temp8_5min` WRITE;
/*!40000 ALTER TABLE `temp8_5min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp8_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp8_60min`
--

DROP TABLE IF EXISTS `temp8_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp8_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp8_60min`
--

LOCK TABLES `temp8_60min` WRITE;
/*!40000 ALTER TABLE `temp8_60min` DISABLE KEYS */;
/*!40000 ALTER TABLE `temp8_60min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp9`
--

DROP TABLE IF EXISTS `temp9`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp9` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp9`
--

LOCK TABLES `temp9` WRITE;
/*!40000 ALTER TABLE `temp9` DISABLE KEYS */;
INSERT INTO `temp9` VALUES (1558793701,0.801),(1558793761,0.801),(1558793761,0.801),(1558793821,0.801),(1558793821,0.801),(1558793881,0.801),(1558793881,0.801);
/*!40000 ALTER TABLE `temp9` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp9_15min`
--

DROP TABLE IF EXISTS `temp9_15min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp9_15min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp9_15min`
--

LOCK TABLES `temp9_15min` WRITE;
/*!40000 ALTER TABLE `temp9_15min` DISABLE KEYS */;
INSERT INTO `temp9_15min` VALUES (1558793881,0.801);
/*!40000 ALTER TABLE `temp9_15min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp9_5min`
--

DROP TABLE IF EXISTS `temp9_5min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp9_5min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp9_5min`
--

LOCK TABLES `temp9_5min` WRITE;
/*!40000 ALTER TABLE `temp9_5min` DISABLE KEYS */;
INSERT INTO `temp9_5min` VALUES (1558793881,0.801);
/*!40000 ALTER TABLE `temp9_5min` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temp9_60min`
--

DROP TABLE IF EXISTS `temp9_60min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp9_60min` (
  `time` int(11) DEFAULT NULL,
  `temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temp9_60min`
--

LOCK TABLES `temp9_60min` WRITE;
/*!40000 ALTER TABLE `temp9_60min` DISABLE KEYS */;
INSERT INTO `temp9_60min` VALUES (1558793881,0.801);
/*!40000 ALTER TABLE `temp9_60min` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-05-25 16:18:52
