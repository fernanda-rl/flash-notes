-- Backup da tabela `avaliacoes` antes de remover a funcionalidade de Notas
-- Gerado em 29/09/2026 20:33:43
-- Para restaurar: reaplique a estrutura da tabela e execute os INSERTs abaixo.

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nota` decimal(4,2) NOT NULL,
  `peso` decimal(5,2) NOT NULL DEFAULT 1.00,
  `data` date NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_avaliacoes_usuario` (`usuario_id`),
  KEY `fk_avaliacoes_disciplina` (`disciplina_id`),
  CONSTRAINT `fk_avaliacoes_disciplina` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_avaliacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `avaliacoes` (`id`, `usuario_id`, `disciplina_id`, `nome`, `nota`, `peso`, `data`, `data_criacao`) VALUES (4, 12, 5, 'Matemática', 5.00, 1.00, '2026-09-29', '2026-09-29 15:24:10');
INSERT INTO `avaliacoes` (`id`, `usuario_id`, `disciplina_id`, `nome`, `nota`, `peso`, `data`, `data_criacao`) VALUES (5, 12, 5, 'Adrielly Martineli', 9.00, 1.00, '2026-09-29', '2026-09-29 15:26:48');
