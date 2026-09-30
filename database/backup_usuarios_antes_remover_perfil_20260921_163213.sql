-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: flashnotes
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
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo_perfil` enum('estudante','professor') NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `expiracao_token` datetime DEFAULT NULL,
  `notificacao_email` tinyint(1) DEFAULT 1,
  `notificacao_navegador` tinyint(1) DEFAULT 1,
  `resumo_semanal` tinyint(1) DEFAULT 0,
  `tema` enum('sistema','claro','escuro') NOT NULL DEFAULT 'sistema',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Usuário','adrielly@gmail.com','$2y$10$lY2aBzE2cIOFNdvCjjvGHOIUn1WTc.x4fI0gqcMrRu95tsoH0Fifq','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(2,'Usuário','leticia@gmail.com','$2y$10$MQxjsnE2oXerfvrHTax1te6jJaZmfkxMPUALMWGeaizf7te7iBN.q','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(3,'Usuário','felipe@gmail.com','$2y$10$npPUklkOoJkeTs71lcxtb.TkyYaupHwTjugmlPMrKacLaL4F6w76m','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(5,'Usuário','felipeakira59@gmail.com','$2y$10$OYb7KioTtT6l4ohHM/kjWeIQ3NWPfxbDTjAVMeTM5r.R7bLUaJCDG','estudante','2026-05-26 01:29:58',NULL,NULL,1,1,0,'sistema'),(7,'Usuário','gustavocavalcantedias0@gmail.com','$2y$10$FmUT53YCwtoipYSet5pzzuMZyvR.OTEzwawV5E4jrHNsNfpZVqGT2','estudante','2026-05-29 02:19:06',NULL,NULL,1,1,0,'sistema'),(9,'Usuário','adriellymartinelli73@gmail.com','$2y$10$wiore7In7NCcL1lqJ/mrOO.LPeY5vw/2w45Ljae/XrVBFpC6Ubjei','estudante','2026-06-04 15:33:02',NULL,NULL,0,0,0,'sistema'),(10,'Usuário','adrielmartinelli@gmail.com','$2y$10$Hywdm0iZjvcwcrsYleVNzOzMoE5AgUg9B13Im91LzGYPsbEEo9EwO','estudante','2026-07-26 01:16:42',NULL,NULL,1,1,0,'sistema'),(11,'Usuário','ellymarrineli@gmail.com','$2y$10$mz.CgAsXunx1aTDgXaYOpOcFw7XJHrFtvUGZuizMwQ7FKyYuElpba','estudante','2026-08-29 00:47:39',NULL,NULL,1,1,0,'sistema'),(12,'Adrielly','adriellymartinelli89@gmail.com','$2y$10$3VINZO438pSektCEneAj3Ozf1MvUgpe36Yc34NJC/tt1NcX7wXi3q','estudante','2026-09-18 16:39:15',NULL,NULL,1,1,0,'claro');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-21 16:32:13
