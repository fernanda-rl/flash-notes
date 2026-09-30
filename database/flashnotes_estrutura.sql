-- =====================================================
-- Flashnotes - Criação do banco de dados
-- =====================================================
-- Cria o banco `flashnotes` do zero, já com toda a estrutura
-- atual: disciplinas, cadernos (anotações e arquivos), vínculo
-- de tarefas e eventos com disciplina, preferência de tema,
-- sessões de foco (Pomodoro) e recuperação de senha por código.
--
-- Este arquivo cria apenas a ESTRUTURA, sem dados.
-- Para dados de demonstração, use `seed_exemplo.sql` depois deste.
--
-- Como executar:
--   phpMyAdmin  : aba Importar > escolher este arquivo > Executar
--   linha de comando:
--     mysql -u root -p < flashnotes_estrutura.sql
--
-- ATENÇÃO: o DROP DATABASE da linha abaixo apaga um banco
-- `flashnotes` que já exista. Comente essa linha se quiser
-- preservar o banco atual.
--
-- Histórico: este arquivo já inclui tudo o que as migrações
-- aplicaram (migracao_cadernos.sql, migracao_tema.sql,
-- migracao_remover_tipo_perfil.sql e migracao_foco.sql).
-- Quem estiver criando o
-- banco do zero NÃO precisa executar as migrações — elas
-- servem apenas para atualizar um banco antigo.
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `flashnotes`;

CREATE DATABASE `flashnotes`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `flashnotes`;

-- -----------------------------------------------------
-- 1. usuarios
-- -----------------------------------------------------
-- Tabela raiz: todas as demais apontam para ela.
--
-- Sobre algumas colunas:
--
-- `senha_hash`             Hash bcrypt gerado por password_hash().
--                          A senha em si nunca é guardada.
--
-- `token_recuperacao`      Hash do código de 6 dígitos enviado por
--                          e-mail na recuperação de senha. Guardar o
--                          código em texto puro permitiria que quem
--                          lesse o banco trocasse a senha de qualquer
--                          usuário. Fica NULL quando não há processo
--                          de recuperação em andamento.
--
-- `expiracao_token`        Até quando o código acima vale (15 minutos
--                          por padrão, ver app/config/config.php).
--
-- `notificacao_navegador`  Corresponde à opção "Notificações no
--                          sistema" da tela de Configurações: os
--                          avisos aparecem no ícone de sino, dentro
--                          do próprio Flashnotes. O nome da coluna
--                          é anterior a esse ajuste de rótulo.
--
-- `tema`                   Preferência de aparência: 'sistema'
--                          acompanha o navegador, 'claro' e 'escuro'
--                          fixam a escolha.
--
-- Não existe coluna de tipo de perfil: ela chegou a ser criada com os
-- valores 'estudante' e 'professor', mas nenhuma funcionalidade a
-- utilizava e foi removida. O perfil de professor está registrado no
-- README como possibilidade de trabalho futuro.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 2. disciplinas
-- -----------------------------------------------------
-- Cada disciplina é também o caderno do estudante. O nome é
-- único por usuário, então a mesma matéria com várias aulas na
-- semana continua sendo um único caderno.

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `professor` varchar(100) DEFAULT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#0066da',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_disciplina_usuario` (`usuario_id`,`nome`),
  CONSTRAINT `fk_disciplinas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 3. horarios
-- -----------------------------------------------------
-- As aulas de cada disciplina na grade semanal.

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
  CONSTRAINT `fk_horarios_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_horarios_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 4. tarefas
-- -----------------------------------------------------
-- O vínculo com disciplina é opcional. ON DELETE SET NULL:
-- apagar a disciplina NÃO apaga a tarefa, ela apenas perde o
-- vínculo e continua aparecendo na tela Tarefas.

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
  CONSTRAINT `fk_tarefas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tarefas_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 5. eventos
-- -----------------------------------------------------
-- Mesma regra das tarefas: vínculo opcional e preservado na
-- exclusão da disciplina.

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
  CONSTRAINT `fk_eventos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_eventos_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 6. anotacoes
-- -----------------------------------------------------
-- Anotações do caderno. `data_atualizacao` se atualiza sozinha
-- a cada edição.

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
  CONSTRAINT `fk_anotacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anotacoes_disciplina`
    FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 7. arquivos
-- -----------------------------------------------------
-- Metadados dos arquivos do caderno. O arquivo em si fica em
-- public/uploads/disciplinas/ com o nome aleatório gravado em
-- `nome_armazenado`; `nome_original` é o nome que o usuário vê.

CREATE TABLE `arquivos` (
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
-- 8. notificacoes
-- -----------------------------------------------------

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- 9. preferencias_foco
-- -----------------------------------------------------
-- A última configuração de tempos da tela Foco. Uma linha por
-- usuário: o usuario_id é a própria chave primária, e o
-- INSERT ... ON DUPLICATE KEY UPDATE da action depende disso.
-- Os padrões são o Pomodoro clássico.

CREATE TABLE `preferencias_foco` (
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
-- 10. sessoes_foco
-- -----------------------------------------------------
-- Histórico de estudo: uma linha por bloco de foco encerrado.
--
-- `duracao_minutos` é o tempo REALMENTE focado (o que já tinha
-- corrido quando o usuário parou), `data_inicio` é o começo do
-- bloco — e não o instante da gravação, que acontece só no fim —
-- e `concluida` diz se o ciclo inteiro foi cumprido.
--
-- Os dois vínculos são opcionais e usam ON DELETE SET NULL:
-- apagar a disciplina ou a tarefa não apaga o histórico.

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

-- =====================================================
-- USUÁRIO DO MYSQL USADO PELA APLICAÇÃO
-- =====================================================
-- O sistema se conecta como `flashuser` (ver app/config/conexao.php).
-- Sem este usuário as páginas não conseguem acessar o banco.
-- Se ele já existir no seu MySQL, pode pular esta parte.

CREATE USER IF NOT EXISTS 'flashuser'@'localhost' IDENTIFIED BY '1234';

GRANT ALL PRIVILEGES ON `flashnotes`.* TO 'flashuser'@'localhost';

FLUSH PRIVILEGES;
