-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 29/05/2026 às 19:09
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `flashnotes`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'outro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `eventos`
--

INSERT INTO `eventos` (`id`, `usuario_id`, `titulo`, `data`, `tipo`) VALUES
(1, 2, 'Prova de Física', '2026-04-10', 'prova'),
(2, 2, 'Apresentação de Trabalho', '2026-04-11', 'outro'),
(3, 2, 'Prova de Matemática', '2026-04-08', 'prova'),
(4, 2, 'Entrega de Projeto', '2026-04-15', 'trabalho'),
(5, 2, 'Prova de Coreano', '2026-07-20', 'prova'),
(8, 6, 'Prova de Calculo', '2026-05-29', 'prova'),
(9, 6, 'Prova de Calculo', '2026-05-29', 'prova'),
(10, 6, 'Lista de Matemática', '2026-05-29', 'outro');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `disciplina` varchar(100) NOT NULL,
  `horario_inicio` time NOT NULL,
  `horario_fim` time NOT NULL,
  `dia` varchar(50) NOT NULL,
  `professor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `horarios`
--

INSERT INTO `horarios` (`id`, `usuario_id`, `disciplina`, `horario_inicio`, `horario_fim`, `dia`, `professor`) VALUES
(1, 2, 'Matemática', '08:00:00', '08:33:00', 'Segunda-feira', 'João da Silva'),
(2, 2, 'Português', '09:50:00', '11:30:00', 'Sexta-feira', 'Kim Seungmin'),
(3, 2, 'Portugues', '20:00:00', '21:00:00', 'Terça-feira', 'Kim Seungmin');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `titulo`, `mensagem`, `lida`, `data_criacao`) VALUES
(0, 6, 'Teste', 'Notificação funcionando', 1, '2026-05-29 01:27:02'),
(2, 6, 'Prova amanhã', 'A prova \'Matematica\' acontece amanhã.', 1, '2026-05-29 02:07:42'),
(3, 6, 'Tarefa vence hoje', 'A tarefa \'Lista de Exercício de Matemática\' vence hoje.', 1, '2026-05-29 02:23:07'),
(4, 6, 'Tarefa vence amanhã', 'A tarefa \'Licao\' vence amanhã.', 1, '2026-05-29 02:27:17'),
(5, 6, 'Tarefa vence hoje', 'A tarefa \'licao\' vence hoje.', 1, '2026-05-29 02:32:11'),
(6, 6, 'Tarefa vence amanhã', 'A tarefa \'Lista de Exercício de Matemática\' vence amanhã.', 1, '2026-05-29 02:43:35'),
(7, 6, 'Prova amanhã', 'A prova \'Prova de Calculo\' acontece amanhã.', 1, '2026-05-29 02:44:02'),
(8, 6, 'Prova amanhã', 'A prova \'Lista de Matemática\' acontece amanhã.', 1, '2026-05-29 02:44:17'),
(9, 6, 'Tarefa vence amanhã', 'A tarefa \'Lista de Exercício de Matemática\' vence amanhã.', 1, '2026-05-29 16:16:31'),
(10, 6, 'Tarefa vence amanhã', 'A tarefa \'licao\' vence amanhã.', 1, '2026-05-29 16:16:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `vencimento` date NOT NULL,
  `prioridade` enum('Alta','Média','Baixa') NOT NULL,
  `status` enum('Não iniciado','Em progresso','Concluído') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tarefas`
--

INSERT INTO `tarefas` (`id`, `usuario_id`, `titulo`, `vencimento`, `prioridade`, `status`) VALUES
(1, 2, 'Trabalho de Física', '2026-04-10', 'Alta', 'Não iniciado'),
(2, 2, 'Lista de Matemática', '2026-04-08', 'Média', 'Em progresso'),
(3, 2, 'Resumo de História', '2026-04-12', 'Baixa', 'Concluído'),
(4, 2, 'Projeto de Programação', '2026-04-15', 'Alta', 'Não iniciado'),
(5, 2, 'Lista de Matemática', '2026-06-21', 'Baixa', 'Não iniciado'),
(7, 6, 'Lista de Exercício de Matemática', '2026-05-30', 'Baixa', 'Não iniciado'),
(8, 6, 'Exercicios de Calculo', '2027-03-30', 'Baixa', 'Em progresso'),
(9, 6, 'Licao', '2026-05-29', 'Baixa', 'Não iniciado'),
(10, 6, 'licao', '2026-05-30', 'Baixa', 'Não iniciado');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo_perfil` enum('estudante','professor') NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `expiracao_token` datetime DEFAULT NULL,
  `notificacao_email` tinyint(1) DEFAULT 1,
  `notificacao_navegador` tinyint(1) DEFAULT 1,
  `resumo_semanal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `tipo_perfil`, `data_criacao`, `token_recuperacao`, `expiracao_token`, `notificacao_email`, `notificacao_navegador`, `resumo_semanal`) VALUES
(1, 'Usuário', 'adrielly@gmail.com', '$2y$10$lY2aBzE2cIOFNdvCjjvGHOIUn1WTc.x4fI0gqcMrRu95tsoH0Fifq', 'estudante', '2026-05-25 23:52:52', NULL, NULL, 1, 1, 0),
(2, 'Usuário', 'leticia@gmail.com', '$2y$10$MQxjsnE2oXerfvrHTax1te6jJaZmfkxMPUALMWGeaizf7te7iBN.q', 'estudante', '2026-05-25 23:52:52', NULL, NULL, 1, 1, 0),
(3, 'Usuário', 'felipe@gmail.com', '$2y$10$npPUklkOoJkeTs71lcxtb.TkyYaupHwTjugmlPMrKacLaL4F6w76m', 'estudante', '2026-05-25 23:52:52', NULL, NULL, 1, 1, 0),
(5, 'Usuário', 'felipeakira59@gmail.com', '$2y$10$OYb7KioTtT6l4ohHM/kjWeIQ3NWPfxbDTjAVMeTM5r.R7bLUaJCDG', 'estudante', '2026-05-26 01:29:58', NULL, NULL, 1, 1, 0),
(6, 'Usuário', 'adriellymartinelli89@gmail.com', '$2y$10$O/bpGKcWMhQ4lCiMXOMfmeYPGzf4K7zfoX1TgAdsfvrwCBli2RBiy', 'estudante', '2026-05-28 03:00:58', NULL, NULL, 1, 0, 1),
(7, 'Usuário', 'gustavocavalcantedias0@gmail.com', '$2y$10$FmUT53YCwtoipYSet5pzzuMZyvR.OTEzwawV5E4jrHNsNfpZVqGT2', 'estudante', '2026-05-29 02:19:06', NULL, NULL, 1, 1, 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eventos_usuario` (`usuario_id`);

--
-- Índices de tabela `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_horarios_usuario` (`usuario_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tarefas_usuario` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `fk_eventos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `fk_horarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tarefas`
--
ALTER TABLE `tarefas`
  ADD CONSTRAINT `fk_tarefas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
