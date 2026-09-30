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
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'outro',
  PRIMARY KEY (`id`),
  KEY `fk_eventos_usuario` (`usuario_id`),
  CONSTRAINT `fk_eventos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
INSERT INTO `eventos` VALUES (1,2,'Prova de Física','2026-04-10','prova'),(2,2,'Apresentação de Trabalho','2026-04-11','outro'),(3,2,'Prova de Matemática','2026-04-08','prova'),(4,2,'Entrega de Projeto','2026-04-15','trabalho'),(5,2,'Prova de Coreano','2026-07-20','prova'),(20,11,'Resumo Desire','2026-08-28','prova');
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horarios`
--

DROP TABLE IF EXISTS `horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina` varchar(100) NOT NULL,
  `horario_inicio` time NOT NULL,
  `horario_fim` time NOT NULL,
  `dia` varchar(50) NOT NULL,
  `professor` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_horarios_usuario` (`usuario_id`),
  CONSTRAINT `fk_horarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios`
--

LOCK TABLES `horarios` WRITE;
/*!40000 ALTER TABLE `horarios` DISABLE KEYS */;
INSERT INTO `horarios` VALUES (1,2,'Matemática','08:00:00','08:33:00','Segunda-feira','João da Silva'),(2,2,'Português','09:50:00','11:30:00','Sexta-feira','Kim Seungmin'),(3,2,'Portugues','20:00:00','21:00:00','Terça-feira','Kim Seungmin'),(14,11,'Portugues','05:00:00','06:00:00','Segunda-feira','Carlos');
/*!40000 ALTER TABLE `horarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes`
--

LOCK TABLES `notificacoes` WRITE;
/*!40000 ALTER TABLE `notificacoes` DISABLE KEYS */;
INSERT INTO `notificacoes` VALUES (0,6,'Teste','Notificação funcionando',1,'2026-05-29 01:27:02'),(2,6,'Prova amanhã','A prova \'Matematica\' acontece amanhã.',1,'2026-05-29 02:07:42'),(3,6,'Tarefa vence hoje','A tarefa \'Lista de Exercício de Matemática\' vence hoje.',1,'2026-05-29 02:23:07'),(4,6,'Tarefa vence amanhã','A tarefa \'Licao\' vence amanhã.',1,'2026-05-29 02:27:17'),(5,6,'Tarefa vence hoje','A tarefa \'licao\' vence hoje.',1,'2026-05-29 02:32:11'),(6,6,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-05-29 02:43:35'),(7,6,'Prova amanhã','A prova \'Prova de Calculo\' acontece amanhã.',1,'2026-05-29 02:44:02'),(8,6,'Prova amanhã','A prova \'Lista de Matemática\' acontece amanhã.',1,'2026-05-29 02:44:17'),(9,6,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-05-29 16:16:31'),(10,6,'Tarefa vence amanhã','A tarefa \'licao\' vence amanhã.',1,'2026-05-29 16:16:36'),(11,6,'Tarefa vence amanhã','A tarefa \'Lista de Matemática\' vence amanhã.',1,'2026-06-01 18:29:27'),(12,6,'Tarefa vence amanhã','A tarefa \'Trabalho de Pt\' vence amanhã.',0,'2026-06-01 18:35:55'),(13,6,'Tarefa vence amanhã','A tarefa \'Prova de Calculo\' vence amanhã.',0,'2026-06-01 18:36:02'),(14,6,'Tarefa vence amanhã','A tarefa \'Exercicios de Calculo\' vence amanhã.',0,'2026-06-01 18:36:18'),(15,8,'Tarefa vence amanhã','A tarefa \'Trabalho De SegInfo\' vence amanhã.',1,'2026-06-04 15:07:53'),(16,8,'Tarefa vence amanhã','A tarefa \'Exercício de ProgLin\' vence amanhã.',0,'2026-06-04 15:08:19'),(17,8,'Prova amanhã','A prova \'Prova Programação Linear\' acontece amanhã.',0,'2026-06-04 15:09:26'),(18,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 15:24:13'),(19,8,'Tarefa vence amanhã','A tarefa \'Resumo Desire\' vence amanhã.',0,'2026-06-04 15:49:17'),(20,8,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-06-04 15:55:44'),(21,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:13:45'),(22,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:15:09'),(23,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:17:15'),(24,11,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',0,'2026-08-29 00:48:32');
/*!40000 ALTER TABLE `notificacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarefas`
--

DROP TABLE IF EXISTS `tarefas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `vencimento` date NOT NULL,
  `prioridade` enum('Alta','Média','Baixa') NOT NULL,
  `status` enum('Não iniciado','Em progresso','Concluído') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tarefas_usuario` (`usuario_id`),
  CONSTRAINT `fk_tarefas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarefas`
--

LOCK TABLES `tarefas` WRITE;
/*!40000 ALTER TABLE `tarefas` DISABLE KEYS */;
INSERT INTO `tarefas` VALUES (1,2,'Trabalho de Física','2026-04-10','Alta','Não iniciado'),(2,2,'Lista de Matemática','2026-04-08','Média','Em progresso'),(3,2,'Resumo de História','2026-04-12','Baixa','Concluído'),(4,2,'Projeto de Programação','2026-04-15','Alta','Não iniciado'),(5,2,'Lista de Matemática','2026-06-21','Baixa','Não iniciado'),(19,11,'Lista de Exercício de Matemática','2026-08-29','Baixa','Em progresso');
/*!40000 ALTER TABLE `tarefas` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Usuário','adrielly@gmail.com','$2y$10$lY2aBzE2cIOFNdvCjjvGHOIUn1WTc.x4fI0gqcMrRu95tsoH0Fifq','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0),(2,'Usuário','leticia@gmail.com','$2y$10$MQxjsnE2oXerfvrHTax1te6jJaZmfkxMPUALMWGeaizf7te7iBN.q','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0),(3,'Usuário','felipe@gmail.com','$2y$10$npPUklkOoJkeTs71lcxtb.TkyYaupHwTjugmlPMrKacLaL4F6w76m','estudante','2026-05-25 23:52:52',NULL,NULL,1,1,0),(5,'Usuário','felipeakira59@gmail.com','$2y$10$OYb7KioTtT6l4ohHM/kjWeIQ3NWPfxbDTjAVMeTM5r.R7bLUaJCDG','estudante','2026-05-26 01:29:58',NULL,NULL,1,1,0),(7,'Usuário','gustavocavalcantedias0@gmail.com','$2y$10$FmUT53YCwtoipYSet5pzzuMZyvR.OTEzwawV5E4jrHNsNfpZVqGT2','estudante','2026-05-29 02:19:06',NULL,NULL,1,1,0),(9,'Usuário','adriellymartinelli73@gmail.com','$2y$10$wiore7In7NCcL1lqJ/mrOO.LPeY5vw/2w45Ljae/XrVBFpC6Ubjei','estudante','2026-06-04 15:33:02',NULL,NULL,0,0,0),(10,'Usuário','adrielmartinelli@gmail.com','$2y$10$Hywdm0iZjvcwcrsYleVNzOzMoE5AgUg9B13Im91LzGYPsbEEo9EwO','estudante','2026-07-26 01:16:42',NULL,NULL,1,1,0),(11,'Usuário','ellymarrineli@gmail.com','$2y$10$mz.CgAsXunx1aTDgXaYOpOcFw7XJHrFtvUGZuizMwQ7FKyYuElpba','estudante','2026-08-29 00:47:39',NULL,NULL,1,1,0),(12,'Usuário','adriellymartinelli89@gmail.com','$2y$10$3VINZO438pSektCEneAj3Ozf1MvUgpe36Yc34NJC/tt1NcX7wXi3q','estudante','2026-09-18 16:39:15',NULL,NULL,1,1,0);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'flashnotes'
--

--
-- Dumping routines for database 'flashnotes'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-18 13:40:30
