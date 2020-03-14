-- MySQL dump 10.13  Distrib 5.7.12, for Win64 (x86_64)
--
-- Host: localhost    Database: toe
-- ------------------------------------------------------
-- Server version	5.6.35

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `route`
--

DROP TABLE IF EXISTS `route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route` (
  `route_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL COMMENT '	',
  `start_time` datetime NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  UNIQUE KEY `route_id_UNIQUE` (`route_id`),
  KEY `fk_ROUTE_EVENT1_idx` (`event_id`),
  KEY `fk_I_BUS1_idx` (`bus_id`),
  CONSTRAINT `fk_I_BUS1` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`bus_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_ROUTE_EVENT1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_ROUTE_ID` FOREIGN KEY (`route_id`) REFERENCES `route_archive` (`route_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route`
--

LOCK TABLES `route` WRITE;
/*!40000 ALTER TABLE `route` DISABLE KEYS */;
INSERT INTO `route` VALUES (6,1,'2017-10-29 14:23:08',NULL),(7,1,'2017-10-29 14:23:09',NULL),(8,1,'2017-10-29 14:23:10',NULL),(9,1,'2017-10-29 14:23:12',NULL),(10,1,'2017-10-29 14:23:13',NULL),(11,1,'2017-10-29 14:23:14',NULL),(12,1,'2017-10-29 14:23:15',NULL),(13,1,'2017-10-29 14:23:16',NULL),(14,1,'2017-10-29 14:23:17',NULL),(15,1,'2017-10-29 14:23:18',NULL),(16,1,'2017-10-29 14:23:19',NULL),(17,1,'2017-10-29 14:23:20',NULL),(18,1,'2017-10-29 14:23:21',NULL),(19,1,'2017-10-29 14:23:22',NULL),(20,1,'2017-10-29 14:23:23',NULL),(21,1,'2017-10-29 14:23:24',NULL),(22,1,'2017-10-29 14:23:25',NULL),(23,1,'2017-10-29 14:23:26',NULL),(24,1,'2017-10-29 14:23:27',NULL),(25,1,'2017-10-29 14:23:28',NULL),(26,1,'2017-10-29 14:23:29',NULL),(27,1,'2017-10-29 14:23:30',NULL),(28,1,'2017-10-29 14:23:31',NULL),(29,1,'2017-10-29 14:23:32',NULL),(30,1,'2017-10-29 14:23:33',NULL),(31,1,'2017-10-29 14:23:34',NULL),(32,1,'2017-10-29 14:23:35',NULL),(33,1,'2017-10-29 14:23:36',NULL),(34,1,'2017-10-29 14:23:37',NULL),(35,1,'2017-10-29 14:23:38',NULL),(36,1,'2017-10-29 14:23:39',NULL),(37,1,'2017-10-29 14:23:40',NULL),(38,1,'2017-10-29 14:23:41',NULL),(39,1,'2017-10-29 14:23:42',NULL),(40,1,'2017-10-29 14:23:43',NULL),(41,1,'2017-10-29 14:23:44',NULL),(42,1,'2017-10-29 14:23:44',NULL),(43,1,'2017-10-29 14:23:45',NULL),(44,1,'2017-10-29 14:25:22',NULL),(45,1,'2017-10-29 14:25:24',NULL),(46,1,'2017-10-29 14:25:25',NULL),(47,1,'2017-10-29 14:25:26',NULL),(48,1,'2017-10-29 14:25:27',NULL),(49,1,'2017-10-29 14:25:28',NULL),(50,1,'2017-10-29 14:25:29',NULL),(51,1,'2017-10-29 14:25:30',NULL),(52,1,'2017-10-29 14:25:31',NULL),(53,1,'2017-10-29 14:25:31',NULL),(54,1,'2017-10-29 14:25:32',NULL),(55,1,'2017-10-29 14:25:33',NULL),(56,1,'2017-10-29 14:25:34',NULL),(57,1,'2017-10-29 14:25:35',NULL),(58,1,'2017-10-29 14:25:36',NULL),(59,1,'2017-10-29 14:25:37',NULL),(60,1,'2017-10-29 14:25:38',NULL),(61,1,'2017-10-29 14:25:39',NULL),(62,1,'2017-10-29 14:25:40',NULL),(63,1,'2017-10-29 14:25:41',NULL),(64,1,'2017-10-29 14:25:42',NULL),(65,1,'2017-10-29 14:25:43',NULL),(66,1,'2017-10-29 14:25:44',NULL),(67,1,'2017-10-29 14:25:45',NULL),(68,1,'2017-10-29 14:25:46',NULL),(69,1,'2017-10-29 14:25:47',NULL),(70,1,'2017-10-29 14:25:48',NULL),(71,1,'2017-10-29 14:25:49',NULL),(72,1,'2017-10-29 14:25:50',NULL),(73,1,'2017-10-29 14:25:51',NULL);
/*!40000 ALTER TABLE `route` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `route_archive`
--

DROP TABLE IF EXISTS `route_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_archive` (
  `route_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_file_url` varchar(1000) CHARACTER SET utf8 NOT NULL,
  `route_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `Required_people` int(11) NOT NULL,
  `type` enum('Bus','Walk','Drive') CHARACTER SET utf8 NOT NULL,
  `wheelchair_accessible` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `blind_accessible` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `hearing_accessible` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `zone_id` int(11) NOT NULL,
  `owner_user_id` int(11) NOT NULL,
  PRIMARY KEY (`route_id`),
  UNIQUE KEY `route_id_UNIQUE` (`route_id`),
  KEY `IX_route_archive_type` (`type`),
  KEY `IX_route_archive_wheelchair_accessible` (`wheelchair_accessible`),
  KEY `IX_route_archive_blind_accessible` (`blind_accessible`),
  KEY `IX_route_archive_hearing_accessible` (`hearing_accessible`),
  KEY `FK_zone_route_archive_zone_id` (`zone_id`),
  KEY `FK_user_route_archive_owner_user_id` (`owner_user_id`),
  CONSTRAINT `FK_user_route_archive_owner_user_id` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_zone_route_archive_zone_id` FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route_archive`
--

LOCK TABLES `route_archive` WRITE;
/*!40000 ALTER TABLE `route_archive` DISABLE KEYS */;
INSERT INTO `route_archive` VALUES (1,'/2-59db0030f23b7.kmz','2-AB-route-2-modded.kmz',6,'Bus','true','true','true',2,1),(2,'/1-59f4f448972a0.kmz','1-AB-route-1-modded.kmz',6,'Bus','false','false','false',1,1),(3,'/1-59f4f8bb7eed0.kmz','1-AB-route-6-modded.kmz',6,'Bus','false','false','false',1,1),(5,'/1-59f4fbad03f27.kml','1-Trick-Or-Eat_Zone_2.kml',6,'Bus','false','false','false',1,1),(6,'/4-59f5497b8987a.kmz','1-Route_1',6,'Bus','false','false','false',4,1),(7,'/4-59f5497ba6819.kmz','1-Route_2',6,'Bus','false','false','false',4,1),(8,'/4-59f5497bab083.kmz','1-Route_6',6,'Bus','false','false','false',4,1),(9,'/4-59f5497bab9d0.kmz','1-Route_9',6,'Bus','false','false','false',4,1),(10,'/4-59f5497bb67b6.kmz','1-Route_8',6,'Bus','false','false','false',4,1),(11,'/4-59f5497be094b.kmz','1-Route_5',6,'Bus','false','false','false',4,1),(12,'/4-59f5497be0e1d.kmz','1-Route_4',6,'Bus','false','false','false',4,1),(13,'/4-59f5497bea216.kmz','1-Route_7',6,'Bus','false','false','false',4,1),(14,'/4-59f5497c041f6.kmz','1-Route_3',6,'Bus','false','false','false',4,1),(15,'/5-59f54a3be9102.kmz','2-Route_1',6,'Bus','false','false','false',5,1),(16,'/5-59f54a3c16857.kmz','2-Route_2',6,'Bus','false','false','false',5,1),(17,'/5-59f54a3c16a28.kmz','2-Route_6',6,'Bus','false','false','false',5,1),(18,'/5-59f54a3c1bc66.kmz','2-Route_3',6,'Bus','false','false','false',5,1),(19,'/5-59f54a3c2f088.kmz','2-Route_5',6,'Bus','false','false','false',5,1),(20,'/5-59f54a3c3fbdf.kmz','2-Route_4',6,'Bus','false','false','false',5,1),(21,'/6-59f54a6964f8f.kmz','3-Route_1',6,'Bus','false','false','false',6,1),(22,'/6-59f54a69812d0.kmz','3-Route_2',6,'Bus','false','false','false',6,1),(23,'/6-59f54a6981701.kmz','3-Route_6',6,'Bus','false','false','false',6,1),(24,'/6-59f54a69820cb.kmz','3-Route_5',6,'Bus','false','false','false',6,1),(25,'/6-59f54a6994a8b.kmz','3-Route_3',6,'Bus','false','false','false',6,1),(26,'/6-59f54a69a5f2b.kmz','3-Route_4',6,'Bus','false','false','false',6,1),(27,'/6-59f54a69a5fd4.kmz','3-Route_7',6,'Bus','false','false','false',6,1),(28,'/7-59f54aa1010e2.kmz','4-Route_1',6,'Bus','false','false','false',7,1),(29,'/7-59f54aa106648.kmz','4-Route_2',6,'Bus','false','false','false',7,1),(30,'/7-59f54aa107d9b.kmz','4-Route_4',6,'Bus','false','false','false',7,1),(31,'/7-59f54aa11ec7d.kmz','4-Route_3',6,'Bus','false','false','false',7,1),(32,'/7-59f54aa129ac0.kmz','4-Route_6',6,'Bus','false','false','false',7,1),(33,'/7-59f54aa139bce.kmz','4-Route_9',6,'Bus','false','false','false',7,1),(34,'/7-59f54aa139fa7.kmz','4-Route_8',6,'Bus','false','false','false',7,1),(35,'/7-59f54aa13a02d.kmz','4-Route_5',6,'Bus','false','false','false',7,1),(36,'/7-59f54aa14573b.kmz','4-Route_7',6,'Bus','false','false','false',7,1),(37,'/8-59f54ac2d25a7.kmz','5-Route_1',6,'Bus','false','false','false',8,1),(38,'/8-59f54ac2d6550.kmz','5-Route_6',6,'Bus','false','false','false',8,1),(39,'/8-59f54ac2d6b41.kmz','5-Route_4',6,'Bus','false','false','false',8,1),(40,'/8-59f54ac305e35.kmz','5-Route_3',6,'Bus','false','false','false',8,1),(41,'/8-59f54ac32920f.kmz','5-Route_5',6,'Bus','false','false','false',8,1),(42,'/8-59f54ac329395.kmz','5-Route_2',6,'Bus','false','false','false',8,1),(43,'/8-59f54ac32ecb2.kmz','5-Route_8',6,'Bus','false','false','false',8,1),(44,'/8-59f54ac338a88.kmz','5-Route_7',6,'Bus','false','false','false',8,1),(45,'/9-59f54adad4d8b.kmz','6-Route_1',6,'Bus','false','false','false',9,1),(46,'/9-59f54adad826f.kmz','6-Route_2',6,'Bus','false','false','false',9,1),(47,'/9-59f54adadbc89.kmz','6-Route_3',6,'Bus','false','false','false',9,1),(48,'/9-59f54adb0414f.kmz','6-Route_4',6,'Bus','false','false','false',9,1),(49,'/9-59f54adb156ba.kmz','6-Route_6',6,'Bus','false','false','false',9,1),(50,'/9-59f54adb1555f.kmz','6-Route_5',6,'Bus','false','false','false',9,1),(51,'/10-59f54aef3e2b1.kmz','7-Route_1',6,'Bus','false','false','false',10,1),(52,'/10-59f54aef49f90.kmz','7-Route_3',6,'Bus','false','false','false',10,1),(53,'/10-59f54aef4a0fb.kmz','7-Route_4',6,'Bus','false','false','false',10,1),(54,'/10-59f54aef59abd.kmz','7-Route_2',6,'Bus','false','false','false',10,1),(55,'/10-59f54aef6cf93.kmz','7-Route_9',6,'Bus','false','false','false',10,1),(56,'/10-59f54aef6d0ed.kmz','7-Route_8',6,'Bus','false','false','false',10,1),(57,'/10-59f54aef7af1a.kmz','7-Route_7',6,'Bus','false','false','false',10,1),(58,'/10-59f54aef8b97b.kmz','7-Route_5',6,'Bus','false','false','false',10,1),(59,'/10-59f54aef8bbea.kmz','7-Route_6',6,'Bus','false','false','false',10,1),(60,'/11-59f54b0ca848c.kmz','8-Route_1',6,'Bus','false','false','false',11,1),(61,'/11-59f54b0ca9f45.kmz','8-Route_2',6,'Bus','false','false','false',11,1),(62,'/11-59f54b0cadc88.kmz','8-Route_3',6,'Bus','false','false','false',11,1),(63,'/11-59f54b0cc2046.kmz','8-Route_6',6,'Bus','false','false','false',11,1),(64,'/11-59f54b0cd18fd.kmz','8-Route_5',6,'Bus','false','false','false',11,1),(65,'/11-59f54b0cd1a53.kmz','8-Route_7',6,'Bus','false','false','false',11,1),(66,'/11-59f54b0ce15b7.kmz','8-Route_4',6,'Bus','false','false','false',11,1),(67,'/11-59f54b0cefc86.kmz','8-Route_9',6,'Bus','false','false','false',11,1),(68,'/11-59f54b0cefb36.kmz','8-Route_8',6,'Bus','false','false','false',11,1),(69,'/12-59f54b202cb2f.kmz','9-Route_5',6,'Bus','false','false','false',12,1),(70,'/12-59f54b202cdec.kmz','9-Route_2',6,'Bus','false','false','false',12,1),(71,'/12-59f54b2031708.kmz','9-Route_1',6,'Bus','false','false','false',12,1),(72,'/12-59f54b2036a73.kmz','9-Route_4',6,'Bus','false','false','false',12,1),(73,'/12-59f54b204d7ae.kmz','9-Route_3',6,'Bus','false','false','false',12,1);
/*!40000 ALTER TABLE `route_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zone`
--

DROP TABLE IF EXISTS `zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zone` (
  `zone_id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('active','inactive','retired') COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `central_parking_address` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `central_building_name` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `zone_radius_meter` int(11) NOT NULL DEFAULT '0',
  `houses_covered` int(11) NOT NULL DEFAULT '0',
  `region_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL DEFAULT '0.00000000',
  `longitude` decimal(11,8) NOT NULL DEFAULT '0.00000000',
  `zoom` int(11) NOT NULL DEFAULT '5',
  PRIMARY KEY (`zone_id`),
  UNIQUE KEY `zone_id_UNIQUE` (`zone_id`),
  UNIQUE KEY `zone_name_UNIQUE` (`zone_name`),
  KEY `zone_status_IDX` (`status`),
  KEY `FK_region_zone_region_id_idx` (`region_id`),
  CONSTRAINT `FK_region_zone_region_id` FOREIGN KEY (`region_id`) REFERENCES `region` (`region_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zone`
--

LOCK TABLES `zone` WRITE;
/*!40000 ALTER TABLE `zone` DISABLE KEYS */;
INSERT INTO `zone` VALUES (1,'test-zone','active','2017-01-30 17:28:51','2017-10-09 02:48:53','42 Wallaby Grove, Sydney, New South Wales, Australia','Scary Dentist Office',-1,200,9,-33.66835800,150.62088600,16),(2,'created-zone','active','2017-10-08 01:10:21','2017-10-08 01:10:21','50 stone road east','University Center',-1,400,9,43.53219300,-80.22685900,12),(3,'Peterzone','active','2017-10-28 22:05:20','2017-10-28 22:05:20','Mark S Burnham Trail, Douro, ON K0L 1S0, Canada','peter\'s place',50,50,9,44.30026441,-78.26660156,7),(4,'1','active','2017-10-28 23:03:42','2017-10-29 03:08:43','177 Rickson Ave, Guelph, ON N1G 4Y6, Canada','Rickson Ridge Public School',0,0,9,43.51329600,-80.21289500,7),(5,'2','active','2017-10-28 23:08:13','2017-10-29 03:08:34','140 Goodwin Dr, Guelph, ON N1L 0G7, Canada','Westminster Woods Public School',0,0,9,43.50985900,-80.18015940,7),(6,'3','active','2017-10-28 23:09:33','2017-10-28 23:09:33','151 Waterloo Ave, Guelph, ON N1H 3J1, Canada','Guelph Montessori School',0,0,9,43.53743910,-80.25465380,7),(7,'4','active','2017-10-28 23:10:26','2017-10-28 23:10:26','87 Dean Ave, Guelph, ON N1G, Canada','Harcourt Memorial United Church',0,0,9,43.76153900,-79.41107900,7),(8,'5','active','2017-10-28 23:11:30','2017-10-28 23:11:30','54 Westmount Rd, Guelph, ON N1H 5H7, Canada','Our Lady of Lourdes Catholic School',0,0,9,43.54762880,-80.26822790,7),(9,'6','active','2017-10-28 23:12:16','2017-10-28 23:12:16','10 Guelph St, Guelph, ON N1H 5Y8, Canada','St. Joseph Catholic School',0,0,9,43.76153900,-79.41107900,7),(10,'7','active','2017-10-28 23:13:32','2017-10-28 23:13:32','397 Stevenson St N, Guelph, ON N1E 5C1, Canada','Edward Johnson Public School',0,0,9,43.76153900,-79.41107900,7),(11,'8','active','2017-10-28 23:15:13','2017-10-28 23:15:13','75 Ottawa Crescent, Guelph, ON N1E 2A8, Canada','Ottawa Crescent Public School',0,0,9,43.76153900,-79.41107900,7),(12,'9','active','2017-10-28 23:17:48','2017-10-28 23:17:48','57 Victoria Rd N, Guelph, ON N1E 5G9, Canada','Saint James Catholic School',0,0,9,43.76153900,-79.41107900,7);
/*!40000 ALTER TABLE `zone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'toe'
--

--
-- Dumping routines for database 'toe'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-10-29 18:54:51
