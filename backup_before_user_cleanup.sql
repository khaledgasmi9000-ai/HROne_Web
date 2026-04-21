-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: HR_ONE
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `profil`
--

DROP TABLE IF EXISTS `profil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profil` (
  `ID_Profil` int(11) NOT NULL AUTO_INCREMENT,
  `Nom_Profil` varchar(100) NOT NULL,
  PRIMARY KEY (`ID_Profil`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profil`
--

LOCK TABLES `profil` WRITE;
/*!40000 ALTER TABLE `profil` DISABLE KEYS */;
INSERT INTO `profil` VALUES (1,'Candidat'),(2,'RH'),(3,'Employee');
/*!40000 ALTER TABLE `profil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateur` (
  `ID_UTILISATEUR` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Entreprise` int(11) NOT NULL,
  `ID_Profil` int(11) NOT NULL,
  `Nom_Utilisateur` varchar(100) NOT NULL,
  `Mot_Passe` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Adresse` varchar(255) DEFAULT NULL,
  `Num_Tel` varchar(30) DEFAULT NULL,
  `CIN` varchar(20) DEFAULT NULL,
  `Num_Ordre_Sign_In` int(11) NOT NULL,
  `Date_Naissance` date DEFAULT NULL,
  `Gender` char(1) DEFAULT NULL,
  `firstLogin` int(11) DEFAULT 0,
  `first_login` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID_UTILISATEUR`),
  KEY `FK_UTILISATEUR_Entreprise` (`ID_Entreprise`),
  KEY `FK_UTILISATEUR_Profil` (`ID_Profil`),
  KEY `FK_UTILISATEUR_Ordre` (`Num_Ordre_Sign_In`),
  CONSTRAINT `FK_UTILISATEUR_Entreprise` FOREIGN KEY (`ID_Entreprise`) REFERENCES `entreprise` (`ID_Entreprise`),
  CONSTRAINT `FK_UTILISATEUR_Ordre` FOREIGN KEY (`Num_Ordre_Sign_In`) REFERENCES `ordre` (`Num_Ordre`),
  CONSTRAINT `FK_UTILISATEUR_Profil` FOREIGN KEY (`ID_Profil`) REFERENCES `profil` (`ID_Profil`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur`
--

LOCK TABLES `utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur` VALUES (4,1,1,'Khaled','$2a$12$1pKSrCIRA.7QWm5OPIucd.9H3PzJyzA.N/ylvHZOmsDmE1Q80m4Dy','Khaled.gasmi@gmail.com','Ariana','25526694','14534530',194386774,'2004-10-28','H',0,NULL,1),(5,1,1,'KhaledCondidat','$2a$12$KAAkHya/0wjBkcyB60QDveNl48guB8FhXOxITlvZgVi2fQQwabOJW','Khaled.gasmi@innova.tn','Ariana','25526694','14531531',194392933,'2005-06-28','H',0,NULL,1),(7,3,2,'KhaledRH','$2a$12$XXwYaTdKDNQnd6lPEFVZjuDzJEccm.5mbewd.IDjFlff9dKazuIfe','khaled.gasmi@innova.com','Ariana','25526694','14531532',194393458,'2005-02-24','H',0,NULL,1),(8,3,3,'Khaled Employe','$2a$12$XUTd8m.z0F7pKDQ9T4N/Mugb7yN4EoeeH5oBz.N8LtzvtVbsoZDWy','Khaled@gmail.com','Ariana','25526694','14531533',194397200,'2002-02-14','H',0,NULL,1),(10,3,3,'KhaledEMMppp','TEMP_PASSWORD','khaled@gaze.com',NULL,NULL,NULL,194402292,'2023-02-02','H',0,NULL,1),(11,3,3,'azeazeaze','TEMP_PASSWORD','khaled.gasa@gmail.com',NULL,NULL,NULL,194402451,'2022-02-17','H',0,NULL,1),(12,3,3,'azeazeaz','TEMP_PASSWORD','aze@mai.com',NULL,NULL,NULL,194402565,'2022-02-09','H',0,NULL,1),(13,3,3,'azaze','TEMP_PASSWORD','kh@az.ccc',NULL,NULL,NULL,194469285,'2019-02-07','F',0,NULL,1),(14,3,3,'azeaezdsq','TEMP_PASSWORD','dsq@QSD.q',NULL,NULL,NULL,194469517,'2026-02-03','F',0,NULL,1),(15,3,3,'eazeaz','TEMP_PASSWORD','dqsdqs@fm.c',NULL,NULL,NULL,194469600,'2026-02-12','H',0,NULL,1),(17,3,3,'Khaled Employe','TEMP_PASSWORD','Khaled@Employe.com',NULL,NULL,NULL,194474471,'2026-02-03','H',0,NULL,1),(18,3,3,'KhaledEmploye','$2a$12$HImbCvAOO7dB0XcJeZqMIupQakNhT4pkaFv6EcaNgqgxUS4PzRbMu','K@E.C',NULL,NULL,NULL,194474800,'2026-02-03','H',0,NULL,1),(19,1,1,'DisifyTest','$2a$12$8I244J1u8vcr91vQjyMDBuLzxEd7B62ZJ8.VTpKVmG4i2p0XPYHBW','Khaled.gasmi.Disify@gmail.com','Ariaana','25526694','14531534',194482808,'2026-03-03','H',0,NULL,1),(22,1,1,'Fares','$2a$12$0pHSKB8zbpw5.L4xuvBuIeA.CELyC04aVWm1MHPEYY7kWCaondio2','fares.gargouri@esprit.tn','Araiana','25526694','14531536',194486348,'2023-03-09','H',0,NULL,1),(23,1,1,'Fares2','$2a$12$Oh6QWybDHC0Jde3QXIufHOYyR6z1quNWowEdFS.Ku.f9is.5KbNiO','khaled.gai@innova.com','Ariana','25526694','14531546',194486770,'2022-03-10','H',0,NULL,1),(24,1,1,'Fares','$2a$12$desaDNVvHvx/ZV3Jq7jbqeB/SldKP0NEVUkJ8xQZc7IGY43YUFMuC','Khaled.gasmi.9000@gmail.com','Araiana','25526694','14531553',194487219,'2023-03-13','H',0,NULL,1),(25,7,2,'Fares','$2y$13$cl7RRVViY919lTLRmeqtteuHORC2MptMpip3XXMnQ2qd8OwXw1guW','fares@innova.com','tunis','26977888','12345678',1782385201,'2004-03-29','H',1,1,1),(32,1,3,'fares gargouri','$2y$13$4GcJiv/38Vt.Ln8.d5UG7uWtzDot0RxB8lLw7yZAU0edbodqI90iW','faresgargouri2@gmail.com','tunis','26977888','12345678',1782385203,'2004-03-29','H',1,1,1);
/*!40000 ALTER TABLE `utilisateur` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_User_bi_Num_Ordre_Sign_In` BEFORE INSERT ON `utilisateur` FOR EACH ROW BEGIN IF NEW.Num_Ordre_Sign_In IS NOT NULL THEN CALL Ensure_NumOrdre_Exists(NEW.Num_Ordre_Sign_In); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_User_bu_Num_Ordre_Sign_In` BEFORE UPDATE ON `utilisateur` FOR EACH ROW BEGIN IF NEW.Num_Ordre_Sign_In IS NOT NULL AND NEW.Num_Ordre_Sign_In <> OLD.Num_Ordre_Sign_In THEN CALL Ensure_NumOrdre_Exists(NEW.Num_Ordre_Sign_In); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee` (
  `ID_Employe` int(11) NOT NULL AUTO_INCREMENT,
  `ID_UTILISATEUR` int(11) NOT NULL,
  `Solde_Conge` int(11) NOT NULL,
  `Nbr_Heure_De_Travail` int(11) NOT NULL,
  `Mac_Machine` varchar(50) DEFAULT NULL,
  `SALAIRE` int(11) DEFAULT 0,
  PRIMARY KEY (`ID_Employe`),
  KEY `FK_Employee_UTILISATEUR` (`ID_UTILISATEUR`),
  CONSTRAINT `FK_Employee_UTILISATEUR` FOREIGN KEY (`ID_UTILISATEUR`) REFERENCES `utilisateur` (`ID_UTILISATEUR`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condidat`
--

DROP TABLE IF EXISTS `condidat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `condidat` (
  `ID_Condidat` int(11) NOT NULL AUTO_INCREMENT,
  `ID_UTILISATEUR` int(11) NOT NULL,
  `CV` text DEFAULT NULL,
  PRIMARY KEY (`ID_Condidat`),
  KEY `FK_Condidat_UTILISATEUR` (`ID_UTILISATEUR`),
  CONSTRAINT `FK_Condidat_UTILISATEUR` FOREIGN KEY (`ID_UTILISATEUR`) REFERENCES `utilisateur` (`ID_UTILISATEUR`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condidat`
--

LOCK TABLES `condidat` WRITE;
/*!40000 ALTER TABLE `condidat` DISABLE KEYS */;
/*!40000 ALTER TABLE `condidat` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-20 19:57:02
