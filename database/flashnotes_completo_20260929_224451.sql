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
-- Current Database: `flashnotes`
--

/*!40000 DROP DATABASE IF EXISTS `flashnotes`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `flashnotes` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `flashnotes`;

--
-- Table structure for table `anotacoes`
--

DROP TABLE IF EXISTS `anotacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anotacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_anotacoes_usuario` (`usuario_id`),
  KEY `fk_anotacoes_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_anotacoes_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anotacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anotacoes`
--

LOCK TABLES `anotacoes` WRITE;
/*!40000 ALTER TABLE `anotacoes` DISABLE KEYS */;
INSERT INTO `anotacoes` VALUES (2,12,5,'Exercicios de Calculo','Será necessário realizar exercicios de calculo para a aula do dia 20/09/2026.','2026-09-18 16:53:22','2026-09-18 16:53:22');
/*!40000 ALTER TABLE `anotacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `arquivos`
--

DROP TABLE IF EXISTS `arquivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `arquivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `nome_armazenado` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `tamanho` int(10) unsigned NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_arquivos_nome_armazenado` (`nome_armazenado`),
  KEY `fk_arquivos_usuario` (`usuario_id`),
  KEY `fk_arquivos_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_arquivos_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_arquivos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arquivos`
--

LOCK TABLES `arquivos` WRITE;
/*!40000 ALTER TABLE `arquivos` DISABLE KEYS */;
INSERT INTO `arquivos` VALUES (2,12,5,'22TC48NKWIT54NKG3YKS2BKCZUYIQX.PDF','4c9b1d85ca53bc62edd28b7ac9ae20ad.pdf','application/pdf',1065424,'2026-09-18 16:53:37');
/*!40000 ALTER TABLE `arquivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disciplinas`
--

DROP TABLE IF EXISTS `disciplinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `professor` varchar(100) DEFAULT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#0066da',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_disciplina_usuario` (`usuario_id`,`nome`),
  CONSTRAINT `fk_disciplinas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disciplinas`
--

LOCK TABLES `disciplinas` WRITE;
/*!40000 ALTER TABLE `disciplinas` DISABLE KEYS */;
INSERT INTO `disciplinas` VALUES (1,2,'Matemática','João da Silva','#0066da','2026-09-18 16:40:45'),(2,2,'Português','Kim Seungmin','#0066da','2026-09-18 16:40:45'),(3,11,'Portugues','Carlos','#0066da','2026-09-18 16:40:45'),(5,12,'Matemática','Reinaldo','#db0042','2026-09-18 16:52:33');
/*!40000 ALTER TABLE `disciplinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'outro',
  PRIMARY KEY (`id`),
  KEY `fk_eventos_usuario` (`usuario_id`),
  KEY `fk_eventos_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_eventos_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_eventos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
INSERT INTO `eventos` VALUES (1,2,NULL,'Prova de Física','2026-04-10','prova'),(2,2,NULL,'Apresentação de Trabalho','2026-04-11','outro'),(3,2,NULL,'Prova de Matemática','2026-04-08','prova'),(4,2,NULL,'Entrega de Projeto','2026-04-15','trabalho'),(5,2,NULL,'Prova de Coreano','2026-07-20','prova'),(20,11,NULL,'Resumo Desire','2026-08-28','prova'),(22,12,5,'Entrega Matemática','2026-09-20','prova');
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
  `disciplina_id` int(11) NOT NULL,
  `horario_inicio` time NOT NULL,
  `horario_fim` time NOT NULL,
  `dia` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_horarios_usuario` (`usuario_id`),
  KEY `fk_horarios_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_horarios_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_horarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios`
--

LOCK TABLES `horarios` WRITE;
/*!40000 ALTER TABLE `horarios` DISABLE KEYS */;
INSERT INTO `horarios` VALUES (1,2,1,'08:00:00','08:33:00','Segunda-feira'),(2,2,2,'09:50:00','11:30:00','Sexta-feira'),(3,2,2,'20:00:00','21:00:00','Terça-feira'),(14,11,3,'05:00:00','06:00:00','Segunda-feira'),(16,12,5,'07:00:00','09:00:00','Segunda-feira'),(18,12,5,'08:00:00','09:00:00','Terça-feira');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes`
--

LOCK TABLES `notificacoes` WRITE;
/*!40000 ALTER TABLE `notificacoes` DISABLE KEYS */;
INSERT INTO `notificacoes` VALUES (0,6,'Teste','Notificação funcionando',1,'2026-05-29 01:27:02'),(2,6,'Prova amanhã','A prova \'Matematica\' acontece amanhã.',1,'2026-05-29 02:07:42'),(3,6,'Tarefa vence hoje','A tarefa \'Lista de Exercício de Matemática\' vence hoje.',1,'2026-05-29 02:23:07'),(4,6,'Tarefa vence amanhã','A tarefa \'Licao\' vence amanhã.',1,'2026-05-29 02:27:17'),(5,6,'Tarefa vence hoje','A tarefa \'licao\' vence hoje.',1,'2026-05-29 02:32:11'),(6,6,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-05-29 02:43:35'),(7,6,'Prova amanhã','A prova \'Prova de Calculo\' acontece amanhã.',1,'2026-05-29 02:44:02'),(8,6,'Prova amanhã','A prova \'Lista de Matemática\' acontece amanhã.',1,'2026-05-29 02:44:17'),(9,6,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-05-29 16:16:31'),(10,6,'Tarefa vence amanhã','A tarefa \'licao\' vence amanhã.',1,'2026-05-29 16:16:36'),(11,6,'Tarefa vence amanhã','A tarefa \'Lista de Matemática\' vence amanhã.',1,'2026-06-01 18:29:27'),(12,6,'Tarefa vence amanhã','A tarefa \'Trabalho de Pt\' vence amanhã.',0,'2026-06-01 18:35:55'),(13,6,'Tarefa vence amanhã','A tarefa \'Prova de Calculo\' vence amanhã.',0,'2026-06-01 18:36:02'),(14,6,'Tarefa vence amanhã','A tarefa \'Exercicios de Calculo\' vence amanhã.',0,'2026-06-01 18:36:18'),(15,8,'Tarefa vence amanhã','A tarefa \'Trabalho De SegInfo\' vence amanhã.',1,'2026-06-04 15:07:53'),(16,8,'Tarefa vence amanhã','A tarefa \'Exercício de ProgLin\' vence amanhã.',0,'2026-06-04 15:08:19'),(17,8,'Prova amanhã','A prova \'Prova Programação Linear\' acontece amanhã.',0,'2026-06-04 15:09:26'),(18,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 15:24:13'),(19,8,'Tarefa vence amanhã','A tarefa \'Resumo Desire\' vence amanhã.',0,'2026-06-04 15:49:17'),(20,8,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-06-04 15:55:44'),(21,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:13:45'),(22,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:15:09'),(23,8,'Atividades próximas','Você possui tarefas ou eventos próximos.',0,'2026-06-04 16:17:15'),(24,11,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',0,'2026-08-29 00:48:32'),(25,12,'Tarefa vence amanhã','A tarefa \'Lista de Exercício de Matemática\' vence amanhã.',1,'2026-09-18 16:56:53');
/*!40000 ALTER TABLE `notificacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preferencias_foco`
--

DROP TABLE IF EXISTS `preferencias_foco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preferencias_foco` (
  `usuario_id` int(11) NOT NULL,
  `foco_min` int(11) NOT NULL DEFAULT 25,
  `pausa_min` int(11) NOT NULL DEFAULT 5,
  `pausa_longa_min` int(11) NOT NULL DEFAULT 15,
  `ciclos_ate_pausa_longa` int(11) NOT NULL DEFAULT 4,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_preferencias_foco_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preferencias_foco`
--

LOCK TABLES `preferencias_foco` WRITE;
/*!40000 ALTER TABLE `preferencias_foco` DISABLE KEYS */;
INSERT INTO `preferencias_foco` VALUES (12,25,5,1,1);
/*!40000 ALTER TABLE `preferencias_foco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessoes_foco`
--

DROP TABLE IF EXISTS `sessoes_foco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessoes_foco` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) DEFAULT NULL,
  `tarefa_id` int(11) DEFAULT NULL,
  `duracao_minutos` int(11) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `concluida` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_sessoes_foco_usuario` (`usuario_id`),
  KEY `fk_sessoes_foco_disciplina` (`disciplina_id`),
  KEY `fk_sessoes_foco_tarefa` (`tarefa_id`),
  CONSTRAINT `fk_sessoes_foco_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessoes_foco_tarefa` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessoes_foco_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessoes_foco`
--

LOCK TABLES `sessoes_foco` WRITE;
/*!40000 ALTER TABLE `sessoes_foco` DISABLE KEYS */;
INSERT INTO `sessoes_foco` VALUES (1,12,NULL,NULL,1,'2026-09-29 15:05:52',1),(2,12,NULL,NULL,1,'2026-09-29 15:07:52',1),(3,12,NULL,NULL,1,'2026-09-29 15:09:52',1),(4,12,NULL,NULL,1,'2026-09-29 15:10:57',1),(5,12,NULL,NULL,1,'2026-09-29 15:12:57',1);
/*!40000 ALTER TABLE `sessoes_foco` ENABLE KEYS */;
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
  `disciplina_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `vencimento` date NOT NULL,
  `prioridade` enum('Alta','Média','Baixa') NOT NULL,
  `status` enum('Não iniciado','Em progresso','Concluído') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tarefas_usuario` (`usuario_id`),
  KEY `fk_tarefas_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_tarefas_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tarefas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarefas`
--

LOCK TABLES `tarefas` WRITE;
/*!40000 ALTER TABLE `tarefas` DISABLE KEYS */;
INSERT INTO `tarefas` VALUES (1,2,NULL,'Trabalho de Física','2026-04-10','Alta','Não iniciado'),(2,2,NULL,'Lista de Matemática','2026-04-08','Média','Em progresso'),(3,2,NULL,'Resumo de História','2026-04-12','Baixa','Concluído'),(4,2,NULL,'Projeto de Programação','2026-04-15','Alta','Não iniciado'),(5,2,NULL,'Lista de Matemática','2026-06-21','Baixa','Não iniciado'),(19,11,NULL,'Lista de Exercício de Matemática','2026-08-29','Baixa','Em progresso'),(21,12,5,'Exercicios de Calculo','2026-09-20','Alta','Não iniciado'),(22,12,5,'Lista de Exercício de Matemática','2026-09-19','Baixa','Não iniciado');
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
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `expiracao_token` datetime DEFAULT NULL,
  `notificacao_email` tinyint(1) DEFAULT 1,
  `notificacao_navegador` tinyint(1) DEFAULT 1,
  `resumo_semanal` tinyint(1) DEFAULT 0,
  `tema` enum('sistema','claro','escuro') NOT NULL DEFAULT 'sistema',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Usuário','adrielly@gmail.com','$2y$10$lY2aBzE2cIOFNdvCjjvGHOIUn1WTc.x4fI0gqcMrRu95tsoH0Fifq','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(2,'Usuário','leticia@gmail.com','$2y$10$MQxjsnE2oXerfvrHTax1te6jJaZmfkxMPUALMWGeaizf7te7iBN.q','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(3,'Usuário','felipe@gmail.com','$2y$10$npPUklkOoJkeTs71lcxtb.TkyYaupHwTjugmlPMrKacLaL4F6w76m','2026-05-25 23:52:52',NULL,NULL,1,1,0,'sistema'),(5,'Usuário','felipeakira59@gmail.com','$2y$10$OYb7KioTtT6l4ohHM/kjWeIQ3NWPfxbDTjAVMeTM5r.R7bLUaJCDG','2026-05-26 01:29:58',NULL,NULL,1,1,0,'sistema'),(7,'Usuário','gustavocavalcantedias0@gmail.com','$2y$10$FmUT53YCwtoipYSet5pzzuMZyvR.OTEzwawV5E4jrHNsNfpZVqGT2','2026-05-29 02:19:06',NULL,NULL,1,1,0,'sistema'),(9,'Usuário','adriellymartinelli73@gmail.com','$2y$10$wiore7In7NCcL1lqJ/mrOO.LPeY5vw/2w45Ljae/XrVBFpC6Ubjei','2026-06-04 15:33:02',NULL,NULL,0,0,0,'sistema'),(10,'Usuário','adrielmartinelli@gmail.com','$2y$10$Hywdm0iZjvcwcrsYleVNzOzMoE5AgUg9B13Im91LzGYPsbEEo9EwO','2026-07-26 01:16:42',NULL,NULL,1,1,0,'sistema'),(11,'Usuário','ellymarrineli@gmail.com','$2y$10$mz.CgAsXunx1aTDgXaYOpOcFw7XJHrFtvUGZuizMwQ7FKyYuElpba','2026-08-29 00:47:39',NULL,NULL,1,1,0,'sistema'),(12,'Adrielly','adriellymartinelli89@gmail.com','$2y$10$3VINZO438pSektCEneAj3Ozf1MvUgpe36Yc34NJC/tt1NcX7wXi3q','2026-09-18 16:39:15',NULL,NULL,1,1,0,'claro');
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

-- Dump completed on 2026-09-29 22:44:51
