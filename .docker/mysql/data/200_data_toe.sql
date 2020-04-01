-- MySQL dump 10.13  Distrib 5.7.12, for Win64 (x86_64)
--
-- Host: localhost    Database: toe
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

use toe;


--
-- Dumping data for table `bus`
--

LOCK TABLES `bus` WRITE;
/*!40000 ALTER TABLE `bus` DISABLE KEYS */;
INSERT INTO `bus` VALUES (1,'F','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(2,'R','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(3,'Q','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(4,'K','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(5,'L','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(6,'I','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(7,'M','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(8,'AB','2016-10-31 16:31:27','2016-10-31 16:31:27',1),(9,'O','2016-10-31 16:31:27','2016-10-31 16:31:27',1);
/*!40000 ALTER TABLE `bus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `country`
--


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


--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES (1,9,'Trick-or-Eat-2016','2016-12-31'),(2,9,'test event','2016-12-12');
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `question`
--

LOCK TABLES `question` WRITE;
/*!40000 ALTER TABLE `question` DISABLE KEYS */;
INSERT INTO `question` VALUES (1,'Did you experience any difficulties while using this app?',280,'active', UTC_TIMESTAMP(), UTC_TIMESTAMP());
/*!40000 ALTER TABLE `question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member`
--


--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;
/*!40000 ALTER TABLE `member` DISABLE KEYS */;
INSERT INTO `member` VALUES (4,NULL,'2017-10-08 23:18:23','false',1,'true'),(5,NULL,'2017-10-08 23:18:23','false',1,'true'),(6,NULL,'2017-10-08 23:18:24','false',1,'true'),(7,1,'2017-10-09 18:57:51','false',1,'true'),(8,1,'2017-10-09 23:21:49','false',1,'true'),(9,1,'2017-10-09 18:57:51','false',1,'true'),(10,1,'2017-10-14 21:02:31','false',1,'false'),(11,2,'2017-10-09 01:13:16','false',1,'true'),(12,2,'2017-10-09 01:23:05','false',1,'true'),(13,2,'2017-10-09 01:20:15','false',1,'true'),(14,2,'2017-10-09 00:07:38','false',1,'false'),(15,3,'2017-10-09 02:03:35','false',2,'false'),(16,4,'2017-10-09 23:21:49','false',1,'false'),(17,5,'2017-10-09 22:48:06','false',1,'false'),(18,5,'2017-10-09 22:48:06','true',1,'false'),(19,NULL,'2017-10-15 20:36:38','false',2,'false'),(20,6,'2017-10-15 20:51:04','false',1,'true'),(21,6,'2017-10-15 20:51:04','true',1,'true'),(22,6,'2017-10-15 20:51:04','true',1,'true'),(23,6,'2017-10-15 20:51:04','true',1,'true'),(24,6,'2017-10-15 20:51:04','true',1,'true'),(25,6,'2017-10-15 20:51:04','true',1,'true');
/*!40000 ALTER TABLE `member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_request`
--

--
-- Dumping data for table `password_request`
--

LOCK TABLES `password_request` WRITE;
/*!40000 ALTER TABLE `password_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `region`
--
-- Dumping data for table `region`
--

LOCK TABLES `region` WRITE;
/*!40000 ALTER TABLE `region` DISABLE KEYS */;
INSERT INTO `region` VALUES (1,1,'Alberta',51.496845,-115.928055),(2,1,'British Columbia',53.726669,-127.647621),(3,1,'Manitoba',53.760860,-98.813873),(4,1,'New Brunswick',46.498390,-66.159668),(5,1,'Newfoundland and Labrador',53.135509,-57.660435),(6,1,'Northwest Territories',62.135189,-122.792473),(7,1,'Nova Scotia',44.651070,-63.582687),(8,1,'Nunavut',66.830925,-69.600800),(9,1,'Ontario',43.761539,-79.411079),(10,1,'Prince Edward Island',43.761539,-59.411079),(11,1,'Quebec',46.829853,-71.254028),(12,1,'Saskatchewan',49.663284,-103.853294),(13,1,'Yukon',60.721188,-135.056839),(14,2,'Alabama',32.806671,-86.791130),(15,2,'Alaska',61.370716,-152.404419),(16,2,'Arizona',33.729759,-111.431221),(17,2,'Arkansas',34.969704,-92.373123),(18,2,'California',36.116203,-119.681564),(19,2,'Colorado',39.059811,-105.311104),(20,2,'Connecticut',41.597782,-72.755371),(21,2,'Delaware',39.318523,-75.507141),(22,2,'Florida',27.766279,-81.686783),(23,2,'Georgia',33.040619,-83.643074),(24,2,'Hawaii',21.094318,-157.498337),(25,2,'Idaho',44.240459,-114.478828),(26,2,'Illinois',40.349457,-88.986137),(27,2,'Indiana',39.849426,-86.258278),(28,2,'Iowa',42.011539,-93.210526),(29,2,'Kansas',38.526600,-96.726486),(30,2,'Kentucky',37.668140,-84.670067),(31,2,'Louisiana',31.169546,-91.867805),(32,2,'Maine',44.693947,-69.381927),(33,2,'Maryland',39.063946,-76.802101),(34,2,'Massachusetts',42.230171,-71.530106),(35,2,'Michigan',43.326618,-84.536095),(36,2,'Minnesota',45.694454,-93.900192),(37,2,'Mississippi',32.741646,-89.678696),(38,2,'Missouri',38.456085,-92.288368),(39,2,'Montana',46.921925,-110.454353),(40,2,'Nebraska',41.125370,-98.268082),(41,2,'Nevada',38.313515,-117.055374),(42,2,'New Hampshire',43.452492,-71.563896),(43,2,'New Jersey',40.298904,-74.521011),(44,2,'New Mexico',34.840515,-106.248482),(45,2,'New York',42.165726,-74.948051),(46,2,'North Carolina',35.630066,-79.806419),(47,2,'North Dakota',47.528912,-99.784012),(48,2,'Ohio',40.388783,-82.764915),(49,2,'Oklahoma',35.565342,-96.928917),(50,2,'Oregon',44.572021,-122.070938),(51,2,'Pennsylvania',40.590752,-77.209755),(52,2,'Rhode Island',41.680893,-71.511780),(53,2,'South Carolina',33.856892,-80.945007),(54,2,'South Dakota',44.299782,-99.478000),(55,2,'Tennessee',35.747845,-86.692345),(56,2,'Texas',31.054487,-97.563461),(57,2,'Utah',40.150032,-111.862434),(58,2,'Vermont',44.045876,-72.710686),(59,2,'Virginia',37.769337,-78.169968),(60,2,'Washington',47.400902,-121.490494),(61,2,'West Virginia',38.491226,-80.954453),(62,2,'Wisconsin',44.268543,-89.616508),(63,2,'Wyoming',42.755966,-107.302490);
/*!40000 ALTER TABLE `region` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `route`
--

--
-- Dumping data for table `route`
--

LOCK TABLES `route` WRITE;
/*!40000 ALTER TABLE `route` DISABLE KEYS */;
INSERT INTO `route` VALUES (1,1,'2017-10-09 00:56:17',NULL);
/*!40000 ALTER TABLE `route` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `route_archive`
--

--
-- Dumping data for table `route_archive`
--

LOCK TABLES `route_archive` WRITE;
/*!40000 ALTER TABLE `route_archive` DISABLE KEYS */;
INSERT INTO `route_archive` VALUES (1,'/2-59db0030f23b7.kmz','2-AB-route-2-modded.kmz',5,'Bus','true','true','true',2,1);
/*!40000 ALTER TABLE `route_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team`
--

--
-- Dumping data for table `team`
--

LOCK TABLES `team` WRITE;
/*!40000 ALTER TABLE `team` DISABLE KEYS */;
INSERT INTO `team` VALUES (1,1,NULL,8,'routetestingteam-PERMANENT','123'),(2,1,1,14,'routetestingteam-withroute-PERMANENT','123'),(3,2,NULL,15,'otherroutetestingteam-PERMANENT','123'),(4,1,NULL,16,'Team Of One!','123'),(5,1,NULL,17,'Empty Team of 2','123'),(6,1,NULL,20,'fullteam-PERMANENT','123');
/*!40000 ALTER TABLE `team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--


--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin@toetests.com','$2y$10$OFwSR2WU8eZPSsLO87mIcOr2pa6jRyOnOh7fY0ttEzdUIU.pnXUly','admin','notreg','2017-10-08 23:02:17',9,'false','false','false'),(2,'normaluser@toetests.com','$2y$10$mV4xl6w0CICgDLrlZTKmYO4nXDzR/h2ubag.9MQEgZTr7PieVkzO.','user','notreg','2017-10-08 23:02:17',9,'true','true','true'),(3,'organizer@toetests.com','$2y$10$T.6DqjF5q4vvhx6bUXUu.evwBIe3165t2N19lQzMCdtgAjUs15wta','organizer','notreg','2017-10-08 23:02:17',9,'true','true','true'),(4,'admin_registered_for_event@toetests.com','$2y$10$Y.MQah2CqWEcpubw4CO9peVBRTn0HZCqMnHYOuH6vC.Pks6TQAbEm','admin','registered','2017-10-08 23:18:23',9,'true','true','true'),(5,'user_registered_for_event@toetests.com','$2y$10$Bbi2ZcK3pUg2HJ3UTfipxeZSv6a0eKQ.vIvdwqZwGwhUn9D0vm6Ly','user','registered','2017-10-08 23:18:23',9,'true','true','true'),(6,'organizer_registered_for_event@toetests.com','$2y$10$/5/jBbSbdi3xzS7wTzrxW.p5/bQ3BVdk9MRtsfyciBHbf8aCOPeGa','oranizer','registered','2017-10-08 23:18:24',9,'true','true','true'),(7,'admin_on_team@toetests.com','$2y$10$/5/jBbSbdi3xzS7wTzrxW.p5/bQ3BVdk9MRtsfyciBHbf8aCOPeGa','admin','onteam','2017-10-09 01:25:15',9,'true','false','true'),(8,'user_on_team_as_captain@toetests.com','$2y$10$MblYeusjyCJMCR.T9M3ae.gOTaxi1CLvag3.kOPg1blejHL.x74Bu','captainuser','onteam','2017-10-09 04:50:10',9,'true','false','true'),(9,'organizer_on_team@toetests.com','$2y$10$eId6qP7KhKlUAeYS6K75d.lbUUAZQxDtyyWJpGCkDNu1UTyWZn6hO','organizer','onteam','2017-10-09 01:25:15',9,'true','false','true'),(10,'user_on_team@toetests.com','$2y$10$/abSVtpFYdFr4ai.HQO8/eex3Q.6XNVPFjrY8vCXSHVbkOv1lRrW.','NOT captain user','onteam','2017-10-09 05:18:18',9,'false','false','false'),(11,'admin_on_team_with_route@toetests.com','$2y$10$s0fnB3eCztvji60dSdmxIOGkwR9RFLnO0SDFvuDx6B8Nc8UnuOnYG','admin','withroute','2017-10-09 00:16:06',9,'true','true','true'),(12,'organizer_on_team_with_route@toetests.com','$2y$10$tA1REgOdSRwyQXejSZaYWeEpnFTq1tj6O1Y2GWJseEf/nUluMP6ji','organizer','withroute','2017-10-09 00:20:58',9,'true','true','true'),(13,'user_on_team_with_route@toetests.com','$2y$10$EUP05zIA4NSOQ4SghiO30.FQk1g04b3DGKH0jsKoWbOEdMzb6F8d.','user','withroute','2017-10-09 00:20:58',9,'true','true','true'),(14,'user_on_team_as_captain_with_route@toetests.com','$2y$10$kudGTZlaiy3v14w1abWDruS.qSijqSMyb1TFNGzbvu/oO5TcmZCRi','captainuser','withroute','2017-10-09 00:20:53',9,'false','false','false'),(15,'user_on_team_as_captain_with_apos@toetests.com','$2y$10$WwIgc8uiCiVwmFSR211KEeDrnHQeyA38MGYjZoWGmNOz2No1tqj0i','APOS','apos','2017-10-09 01:51:32',9,'false','false','false'),(16,'user_on_team_of_one_as_captain@toetests.com','$2y$10$fof68WUAECXFimHvqT1C8ukbbeiaFXkApe51neOH3SgVskDpsEpqi','Leader','OfOne','2017-10-09 21:52:59',9,'false','false','false'),(17,'user_on_team_of_empty_as_captain@toetests.com','$2y$10$rWU6MxeIXONMvinU4d6OC.3DnSjbQlVz9oxtiXbhcDVlcvlGGjeTu','captainuser','ofemptyteam','2017-10-09 22:47:33',9,'false','false','false'),(18,'8_1@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-09 22:48:06',9,'false','false','false'),(19,'user_registered_for_other_event@toetests.com','$2y$10$JKLCEon6l6mn7DVzUnTGJu7yEeBG22of87IC0pFjXTCjm2h1yMXWS','normaluser','otherevent','2017-10-15 20:36:09',9,'false','false','false'),(20,'user_on_team_as_captain_full_team@toetests.com','$2y$10$WwGDsrJsdy8d1AFBYvImLefnzG7yy9mE48KTOaieC0LZ1nsJQkW.e','fullteam','captain','2017-10-15 20:50:28',9,'true','true','true'),(21,'6_1@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-15 20:51:04',9,'true','true','true'),(22,'6_2@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-15 20:51:04',9,'true','true','true'),(23,'6_3@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-15 20:51:04',9,'true','true','true'),(24,'6_4@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-15 20:51:04',9,'true','true','true'),(25,'6_5@toeholder.com','tobedeleted','TOEGeneratedFirstName','TOEGeneratedFirstName','2017-10-15 20:51:04',9,'true','true','true');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_role`
--


--
-- Dumping data for table `user_role`
--

LOCK TABLES `user_role` WRITE;
/*!40000 ALTER TABLE `user_role` DISABLE KEYS */;
INSERT INTO `user_role` VALUES (1,'admin'),(2,'participant'),(3,'organizer'),(3,'participant'),(4,'admin'),(4,'participant'),(5,'participant'),(6,'organizer'),(6,'participant'),(7,'admin'),(7,'participant'),(8,'participant'),(9,'organizer'),(9,'participant'),(10,'participant'),(11,'admin'),(11,'participant'),(12,'organizer'),(12,'participant'),(13,'participant'),(14,'participant'),(15,'participant'),(16,'participant'),(17,'participant'),(18,'participant'),(19,'participant'),(20,'participant'),(21,'participant'),(22,'participant'),(23,'participant'),(24,'participant');
/*!40000 ALTER TABLE `user_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zone`
--


--
-- Dumping data for table `zone`
--

LOCK TABLES `zone` WRITE;
/*!40000 ALTER TABLE `zone` DISABLE KEYS */;
INSERT INTO `zone` VALUES (1,'test-zone','active','2017-01-30 17:28:51','2017-10-09 02:48:53','42 Wallaby Grove, Sydney, New South Wales, Australia','Scary Dentist Office',-1,200,9,-33.66835800,150.62088600,16),(2,'created-zone','active','2017-10-08 01:10:21','2017-10-08 01:10:21','50 stone road east','University Center',-1,400,9,43.53219300,-80.22685900,12);
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

-- Dump completed on 2017-10-29  2:31:32
