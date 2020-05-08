CREATE SCHEMA IF NOT EXISTS toe;
USE toe;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

