-- =====================================================
-- Flashnotes - Migração "Sessões de foco" (Pomodoro)
-- =====================================================
-- Cria as duas tabelas da tela Foco:
--
--   `preferencias_foco`  uma linha por usuário, com a última
--                        configuração de tempos que ele usou. Serve
--                        para a tela abrir já com os valores dele.
--
--   `sessoes_foco`       o histórico: uma linha por bloco de foco
--                        encerrado, completo ou interrompido.
--
-- Execute depois de migracao_cadernos.sql e migracao_tema.sql.
-- (phpMyAdmin > Importar, ou:
--  mysql -u flashuser -p flashnotes < migracao_foco.sql)
-- =====================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------
-- 1. preferencias_foco
-- -----------------------------------------------------
-- A chave primária é o próprio usuario_id: cada usuário tem no
-- máximo uma configuração, e o INSERT ... ON DUPLICATE KEY UPDATE
-- do crud_foco.php depende dessa unicidade.
--
-- Os padrões são o Pomodoro clássico (25/5, pausa longa de 15
-- minutos a cada 4 ciclos), os mesmos que a tela mostra para quem
-- nunca salvou uma configuração.

CREATE TABLE IF NOT EXISTS `preferencias_foco` (
  `usuario_id` int(11) NOT NULL,
  `foco_min` int(11) NOT NULL DEFAULT 25,
  `pausa_min` int(11) NOT NULL DEFAULT 5,
  `pausa_longa_min` int(11) NOT NULL DEFAULT 15,
  `ciclos_ate_pausa_longa` int(11) NOT NULL DEFAULT 4,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_preferencias_foco_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 2. sessoes_foco
-- -----------------------------------------------------
-- `duracao_minutos` guarda o tempo REALMENTE focado: quando o
-- usuário para no meio, vale o que já tinha corrido, não o tempo
-- configurado. O tempo em pausa nunca entra nessa conta.
--
-- `data_inicio` é o instante em que o bloco de foco começou, não o
-- instante em que a linha foi gravada — uma sessão de 50 minutos é
-- registrada só no fim, e a data precisa apontar para o começo.
--
-- `concluida` distingue o ciclo inteiro (1) do que foi interrompido
-- pelo botão Parar (0).
--
-- ON DELETE SET NULL nos dois vínculos: apagar a disciplina ou a
-- tarefa não apaga o histórico de estudo, apenas desfaz o vínculo.

CREATE TABLE IF NOT EXISTS `sessoes_foco` (
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
  CONSTRAINT `fk_sessoes_foco_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sessoes_foco_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessoes_foco_tarefa`
    FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
