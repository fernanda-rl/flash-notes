-- =====================================================
-- Flashnotes - Migração "Cadernos"
-- =====================================================
-- Cria a tabela `disciplinas` e liga a ela os horários,
-- as anotações, os arquivos, as tarefas e os eventos.
--
-- PRESERVA TODOS OS DADOS EXISTENTES:
-- as disciplinas são extraídas da tabela `horarios` antes
-- de as colunas de texto serem removidas.
--
-- Execute o arquivo inteiro de uma vez (phpMyAdmin > Importar,
-- ou: mysql -u flashuser -p flashnotes < migracao_cadernos.sql)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

START TRANSACTION;

-- -----------------------------------------------------
-- 1. Tabela `disciplinas`
-- -----------------------------------------------------
-- Uma linha por disciplina do usuário. O nome é único por
-- usuário, de modo que a mesma matéria com várias aulas na
-- semana continua sendo UM único caderno.

CREATE TABLE IF NOT EXISTS `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `professor` varchar(100) DEFAULT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#0066da',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_disciplina_usuario` (`usuario_id`, `nome`),
  CONSTRAINT `fk_disciplinas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 2. Migração dos dados: horarios -> disciplinas
-- -----------------------------------------------------
-- Cada par (usuario_id, disciplina) distinto vira uma
-- disciplina. O professor é herdado da primeira aula.

INSERT IGNORE INTO `disciplinas` (`usuario_id`, `nome`, `professor`)
SELECT `usuario_id`, `disciplina`, MIN(`professor`)
FROM `horarios`
GROUP BY `usuario_id`, `disciplina`;

-- -----------------------------------------------------
-- 3. `horarios` passa a apontar para `disciplinas`
-- -----------------------------------------------------

ALTER TABLE `horarios`
  ADD COLUMN `disciplina_id` int(11) DEFAULT NULL AFTER `usuario_id`;

UPDATE `horarios` h
INNER JOIN `disciplinas` d
  ON d.`usuario_id` = h.`usuario_id`
 AND d.`nome` = h.`disciplina`
SET h.`disciplina_id` = d.`id`;

-- Conferência: a consulta abaixo DEVE retornar 0.
-- Se retornar mais que 0, interrompa e investigue antes de seguir.
SELECT COUNT(*) AS horarios_sem_disciplina
FROM `horarios`
WHERE `disciplina_id` IS NULL;

ALTER TABLE `horarios`
  MODIFY `disciplina_id` int(11) NOT NULL,
  ADD KEY `fk_horarios_disciplina` (`disciplina_id`),
  ADD CONSTRAINT `fk_horarios_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Nome e professor agora vivem em `disciplinas`; remover evita
-- dado duplicado e divergente entre as aulas da mesma matéria.
ALTER TABLE `horarios`
  DROP COLUMN `disciplina`,
  DROP COLUMN `professor`;

-- -----------------------------------------------------
-- 4. Tabela `anotacoes`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `anotacoes` (
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
  CONSTRAINT `fk_anotacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anotacoes_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 5. Tabela `arquivos`
-- -----------------------------------------------------
-- O binário fica em public/uploads/disciplinas/ ; aqui guardamos
-- apenas os metadados. `nome_armazenado` é o nome aleatório no
-- disco, `nome_original` é o que o usuário vê.

CREATE TABLE IF NOT EXISTS `arquivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `nome_armazenado` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `tamanho` int(10) UNSIGNED NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_arquivos_nome_armazenado` (`nome_armazenado`),
  KEY `fk_arquivos_usuario` (`usuario_id`),
  KEY `fk_arquivos_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_arquivos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_arquivos_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 6. `tarefas` vinculada à disciplina (opcional)
-- -----------------------------------------------------
-- ON DELETE SET NULL: apagar a disciplina NÃO apaga a tarefa,
-- ela apenas deixa de estar vinculada e continua na tela Tarefas.

ALTER TABLE `tarefas`
  ADD COLUMN `disciplina_id` int(11) DEFAULT NULL AFTER `usuario_id`,
  ADD KEY `fk_tarefas_disciplina` (`disciplina_id`),
  ADD CONSTRAINT `fk_tarefas_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------
-- 7. `eventos` vinculado à disciplina (opcional)
-- -----------------------------------------------------

ALTER TABLE `eventos`
  ADD COLUMN `disciplina_id` int(11) DEFAULT NULL AFTER `usuario_id`,
  ADD KEY `fk_eventos_disciplina` (`disciplina_id`),
  ADD CONSTRAINT `fk_eventos_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;
