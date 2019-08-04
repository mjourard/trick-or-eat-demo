-- MySQL dump 10.13  Distrib 5.7.12, for Win64 (x86_64)
--
-- Host: localhost    Database: scotchbox
-- ------------------------------------------------------
-- Server version	5.7.14

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
-- Table structure for table `bus`
--

DROP TABLE IF EXISTS `bus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bus` (
  `bus_id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_name` varchar(45) CHARACTER SET utf8 NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `zone_id` int(11) NOT NULL,
  PRIMARY KEY (`bus_id`),
  UNIQUE KEY `bus_id_UNIQUE` (`bus_id`),
  KEY `fk_BUS_ZONE_ID` (`zone_id`),
  CONSTRAINT `fk_BUS_ZONE_ID` FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus`
--

LOCK TABLES `bus` WRITE;
/*!40000 ALTER TABLE `bus` DISABLE KEYS */;
/*!40000 ALTER TABLE `bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `country` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(45) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`country_id`),
  UNIQUE KEY `country_id_UNIQUE` (`country_id`),
  UNIQUE KEY `country_name_UNIQUE` (`country_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `country`
--

LOCK TABLES `country` WRITE;
/*!40000 ALTER TABLE `country` DISABLE KEYS */;
INSERT INTO `country` VALUES (1,'Canada'),(2,'U.S.A.');
/*!40000 ALTER TABLE `country` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) NOT NULL,
  `event_name` varchar(45) CHARACTER SET utf8 NOT NULL,
  `year` date NOT NULL,
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `event_id_UNIQUE` (`event_id`),
  KEY `fk_EVENT_REGION1_idx` (`region_id`),
  CONSTRAINT `fk_EVENT_REGION1` FOREIGN KEY (`region_id`) REFERENCES `region` (`region_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES (1,9,'Guelph Trick or Eat','2016-12-31'),(2,9,'Waterloo Trick or Eat','2016-12-31'),(3,9,'Trick or Eat @ Laurier','2016-12-31'),(4,10,'Western TOE','2016-12-31');
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member` (
  `user_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `date_joined_team` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `checked_in` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `event_id` int(11) DEFAULT NULL,
  `can_drive` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id_UNIQUE` (`user_id`),
  KEY `fk_MEMBER_TEAM1_idx` (`team_id`),
  KEY `fk_MEMBER_EVENT1_idx` (`event_id`),
  CONSTRAINT `fk_MEMBER_EVENT1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_MEMBER_TEAM1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_MEMBER_USER1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;
/*!40000 ALTER TABLE `member` DISABLE KEYS */;
/*!40000 ALTER TABLE `member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_request`
--

DROP TABLE IF EXISTS `password_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_request` (
  `user_id` int(11) NOT NULL,
  `issued_at` varchar(255) NOT NULL,
  `expired_at` varchar(255) NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`unique_id`),
  UNIQUE KEY `unique_id_UNIQUE` (`unique_id`),
  KEY `user_id_issued_at_idx` (`user_id`,`issued_at`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_request`
--

LOCK TABLES `password_request` WRITE;
/*!40000 ALTER TABLE `password_request` DISABLE KEYS */;
INSERT INTO `password_request` VALUES (7,'1500949631','1500967631','5976ac7f80ae9',NULL);
/*!40000 ALTER TABLE `password_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `region`
--

DROP TABLE IF EXISTS `region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `region` (
  `region_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `region_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `latitude` decimal(9,6) NOT NULL DEFAULT '0.000000',
  `longitude` decimal(9,6) NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`region_id`),
  UNIQUE KEY `region_id_UNIQUE` (`region_id`),
  KEY `fk_REGION_COUNTRY1_idx` (`country_id`),
  CONSTRAINT `fk_REGION_COUNTRY1` FOREIGN KEY (`country_id`) REFERENCES `country` (`country_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `region`
--

LOCK TABLES `region` WRITE;
/*!40000 ALTER TABLE `region` DISABLE KEYS */;
INSERT INTO `region` VALUES (1,1,'Alberta',51.496845,-115.928055),(2,1,'British Columbia',53.726669,-127.647621),(3,1,'Manitoba',53.760860,-98.813873),(4,1,'New Brunswick',46.498390,-66.159668),(5,1,'Newfoundland and Labrador',53.135509,-57.660435),(6,1,'Northwest Territories',62.135189,-122.792473),(7,1,'Nova Scotia',44.651070,-63.582687),(8,1,'Nunavut',66.830925,-69.600800),(9,1,'Ontario',43.761539,-79.411079),(10,1,'Prince Edward Island',43.761539,-59.411079),(11,1,'Quebec',46.829853,-71.254028),(12,1,'Saskatchewan',49.663284,-103.853294),(13,1,'Yukon',60.721188,-135.056839),(14,2,'Alabama',32.806671,-86.791130),(15,2,'Alaska',61.370716,-152.404419),(16,2,'Arizona',33.729759,-111.431221),(17,2,'Arkansas',34.969704,-92.373123),(18,2,'California',36.116203,-119.681564),(19,2,'Colorado',39.059811,-105.311104),(20,2,'Connecticut',41.597782,-72.755371),(21,2,'Delaware',39.318523,-75.507141),(22,2,'Florida',27.766279,-81.686783),(23,2,'Georgia',33.040619,-83.643074),(24,2,'Hawaii',21.094318,-157.498337),(25,2,'Idaho',44.240459,-114.478828),(26,2,'Illinois',40.349457,-88.986137),(27,2,'Indiana',39.849426,-86.258278),(28,2,'Iowa',42.011539,-93.210526),(29,2,'Kansas',38.526600,-96.726486),(30,2,'Kentucky',37.668140,-84.670067),(31,2,'Louisiana',31.169546,-91.867805),(32,2,'Maine',44.693947,-69.381927),(33,2,'Maryland',39.063946,-76.802101),(34,2,'Massachusetts',42.230171,-71.530106),(35,2,'Michigan',43.326618,-84.536095),(36,2,'Minnesota',45.694454,-93.900192),(37,2,'Mississippi',32.741646,-89.678696),(38,2,'Missouri',38.456085,-92.288368),(39,2,'Montana',46.921925,-110.454353),(40,2,'Nebraska',41.125370,-98.268082),(41,2,'Nevada',38.313515,-117.055374),(42,2,'New Hampshire',43.452492,-71.563896),(43,2,'New Jersey',40.298904,-74.521011),(44,2,'New Mexico',34.840515,-106.248482),(45,2,'New York',42.165726,-74.948051),(46,2,'North Carolina',35.630066,-79.806419),(47,2,'North Dakota',47.528912,-99.784012),(48,2,'Ohio',40.388783,-82.764915),(49,2,'Oklahoma',35.565342,-96.928917),(50,2,'Oregon',44.572021,-122.070938),(51,2,'Pennsylvania',40.590752,-77.209755),(52,2,'Rhode Island',41.680893,-71.511780),(53,2,'South Carolina',33.856892,-80.945007),(54,2,'South Dakota',44.299782,-99.438828),(55,2,'Tennessee',35.747845,-86.692345),(56,2,'Texas',31.054487,-97.563461),(57,2,'Utah',40.150032,-111.862434),(58,2,'Vermont',44.045876,-72.710686),(59,2,'Virginia',37.769337,-78.169968),(60,2,'Washington',47.400902,-121.490494),(61,2,'West Virginia',38.491226,-80.954453),(62,2,'Wisconsin',44.268543,-89.616508),(63,2,'Wyoming',42.755966,-107.302490);
/*!40000 ALTER TABLE `region` ENABLE KEYS */;
UNLOCK TABLES;

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
  CONSTRAINT `FK_zone_route_archive_zone_id` FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_user_route_archive_owner_user_id` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route_archive`
--

LOCK TABLES `route_archive` WRITE;
/*!40000 ALTER TABLE `route_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `route_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `captain_user_id` int(11) NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE KEY `team_id_UNIQUE` (`team_id`),
  UNIQUE KEY `event_id_name_UNIQUE` (`event_id`,`name`),
  KEY `fk_TEAM_ROUTE1_idx` (`route_id`),
  KEY `fk_TEAM_CAPTAIN_USER_ID_idx` (`captain_user_id`),
  CONSTRAINT `fk_TEAM_CAPTAIN_USER_ID` FOREIGN KEY (`captain_user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_TEAM_ROUTE1` FOREIGN KEY (`route_id`) REFERENCES `route` (`route_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team`
--

LOCK TABLES `team` WRITE;
/*!40000 ALTER TABLE `team` DISABLE KEYS */;
/*!40000 ALTER TABLE `team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
  `password` varchar(100) CHARACTER SET utf8 NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `date_joined` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `region_id` int(11) NOT NULL,
  `hearing` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `visual` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `mobility` enum('true','false') CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `user_id_UNIQUE` (`user_id`),
  KEY `fk_USER_REGION1_idx` (`region_id`),
  KEY `IX_user_hearing` (`hearing`),
  KEY `IX_user_visual` (`visual`),
  KEY `IX_user_mobility` (`mobility`),
  CONSTRAINT `fk_USER_REGION1` FOREIGN KEY (`region_id`) REFERENCES `region` (`region_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin@TOEadmin.com','$2y$10$SZ7H6yhS4JGTWWY6SskuxO4dyG6R3c5is2GVDJWvIIQEGaKPM4/X.','admin','super','2016-08-19 14:54:35',9,'false','false','false'),(2,'normaluser@gmail.com','$2y$10$SZ7H6yhS4JGTWWY6SskuxO4dyG6R3c5is2GVDJWvIIQEGaKPM4/X.','normal','user','2016-11-13 13:47:45',9,'false','false','false'),(3, 'organizer@toeorganizers.com', '$2y$10$SZ7H6yhS4JGTWWY6SskuxO4dyG6R3c5is2GVDJWvIIQEGaKPM4/X.', 'organizer', 'of the event', '2017-02-10 13:47:45', 9, 'false', 'false', 'false'), (4, 'activedriver@gmail.com', 'password1', 'driver', 'dude', NOW(), 9, 'false', 'false', 'false'), (5, 'editor@gmail.com', 'password1', 'editor', 'bro', NOW(), 9, 'false', 'false', 'false'), (6, 'standardmod@toemods.com', 'password1', 'Mody', 'McModerator', NOW(), 9, 'false', 'false', 'false'), (7, 'otherorganizer@toeorganizers.com', 'password1', 'I also organizer things', 'derp', NOW(), 9, 'false', 'false', 'false');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role` enum('*','admin','organizer','moderator','editor','participant','driver') CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `user_role_id_role` (`user_id`,`role`),
  CONSTRAINT `user_role_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Holds the user''s roles for user accounts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_role`
--

LOCK TABLES `user_role` WRITE;
/*!40000 ALTER TABLE `user_role` DISABLE KEYS */;
INSERT INTO `user_role` VALUES (1,'*'),(2,'participant'),(2,'driver'),(3,'organizer'),(4,'participant'),(4,'driver'),(5,'editor'),(6,'moderator'),(7,'organizer');
/*!40000 ALTER TABLE `user_role` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zone`
--

LOCK TABLES `zone` WRITE;
/*!40000 ALTER TABLE `zone` DISABLE KEYS */;
INSERT INTO `zone` VALUES (1,'test-zone','active','2017-01-30 17:28:51','2017-02-09 01:38:21','391 Victoria Rd N, Guelph, ON, Canada','St. Patrick Catholic School',250,200,9,43.57691500,-80.25670600,16);
/*!40000 ALTER TABLE `zone` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `scotchbox`.`zone_BEFORE_INSERT` BEFORE INSERT ON `zone` FOR EACH ROW
BEGIN
	IF NEW.latitude < -90 OR NEW.latitude > 90 THEN
		CALL `bad latitude used. Must be between -90 and 90.`;
	END IF;
    IF NEW.longitude < -180 OR NEW.longitude > 180 THEN
		CALL `bad longitude used. Must be between -180 and 180`;
	END IF;
    IF NEW.zoom < 1 OR NEW.zoom > 20 THEN
		CALL `bad zoom used. Must be between 1 and 20`;
	END IF;
    IF NEW.zone_radius_meter < 0 THEN
		CALL `bad zone_radius_meter used. Must be greater than 0`;
	END IF;
    IF NEW.houses_covered < 0 THEN
		CALL `bad houses_covered used. Must be greater than 0`;
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `scotchbox`.`zone_BEFORE_UPDATE` BEFORE UPDATE ON `zone` FOR EACH ROW
BEGIN
	IF NEW.latitude < -90 OR NEW.latitude > 90 THEN
		CALL `bad latitude used. Must be between -90 and 90.`;
	END IF;
    IF NEW.longitude < -180 OR NEW.longitude > 180 THEN
		CALL `bad longitude used. Must be between -180 and 180`;
	END IF;
    IF NEW.zoom < 1 OR NEW.zoom > 20 THEN
		CALL `bad zoom used. Must be between 1 and 20`;
	END IF;
    IF NEW.zone_radius_meter < 0 THEN
		CALL `bad zone_radius_meter used. Must be greater than 0`;
	END IF;
    IF NEW.houses_covered < 0 THEN
		CALL `bad houses_covered used. Must be greater than 0`;
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-01-26 15:35:26
