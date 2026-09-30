-- =====================================================
-- Flashnotes - Dados de exemplo (demonstração)
-- =====================================================
-- Popula o banco com dois usuários FICTÍCIOS e conteúdo de
-- demonstração, para apresentar o sistema sem expor dados reais.
--
-- Execute DEPOIS de flashnotes_estrutura.sql:
--   mysql -u root -p flashnotes < seed_exemplo.sql
--
-- Contas criadas (a senha das duas é: senha123)
--   ana.souza@exemplo.com
--   bruno.lima@exemplo.com
--
-- ATENÇÃO: estas são contas de demonstração com senha conhecida.
-- Não use este arquivo em um ambiente de produção.
-- =====================================================

SET NAMES utf8mb4;

USE `flashnotes`;

-- -----------------------------------------------------
-- Usuários
-- -----------------------------------------------------
-- As senhas estão como hash bcrypt de "senha123", geradas com
-- password_hash(), do mesmo jeito que o cadastro do sistema faz.

INSERT INTO `usuarios`
    (`id`, `nome`, `email`, `senha_hash`,
     `notificacao_email`, `notificacao_navegador`, `resumo_semanal`, `tema`)
VALUES
    (1, 'Ana Souza', 'ana.souza@exemplo.com',
     '$2y$10$bYILWSyavc663ODmuW0ES.zuPnc4XTKhxChkN5LO0qlFnl5u4yytS',
     1, 1, 1, 'sistema'),

    (2, 'Bruno Lima', 'bruno.lima@exemplo.com',
     '$2y$10$mcBG5nfjrn93nNJIpa199eFUlzmiwmRiAM7MtAuMuwWB/qFDnKbRa',
     1, 0, 0, 'escuro');

-- -----------------------------------------------------
-- Disciplinas (os cadernos)
-- -----------------------------------------------------

INSERT INTO `disciplinas` (`id`, `usuario_id`, `nome`, `professor`, `cor`) VALUES
    (1, 1, 'Cálculo I',              'Prof. Reinaldo Dias',   '#db0042'),
    (2, 1, 'Algoritmos',             'Profa. Carla Menezes',  '#0066da'),
    (3, 1, 'Inglês Técnico',         'Prof. Daniel Rocha',    '#2e7d32'),
    (4, 2, 'Banco de Dados',         'Profa. Helena Prado',   '#8B5CF6');

-- -----------------------------------------------------
-- Aulas na semana
-- -----------------------------------------------------

INSERT INTO `horarios` (`usuario_id`, `disciplina_id`, `horario_inicio`, `horario_fim`, `dia`) VALUES
    (1, 1, '07:30:00', '09:10:00', 'Segunda-feira'),
    (1, 1, '07:30:00', '09:10:00', 'Quarta-feira'),
    (1, 2, '09:20:00', '11:00:00', 'Terça-feira'),
    (1, 2, '09:20:00', '11:00:00', 'Quinta-feira'),
    (1, 3, '19:00:00', '20:40:00', 'Sexta-feira'),
    (2, 4, '14:00:00', '15:40:00', 'Segunda-feira');

-- -----------------------------------------------------
-- Anotações
-- -----------------------------------------------------

INSERT INTO `anotacoes` (`usuario_id`, `disciplina_id`, `titulo`, `conteudo`) VALUES
    (1, 1, 'Regra da cadeia',
     'A derivada de uma função composta f(g(x)) é f''(g(x)) · g''(x).\n\nExemplo: para sen(3x), a derivada é 3·cos(3x).'),

    (1, 1, 'Limites notáveis',
     'lim (sen x)/x = 1 quando x tende a 0.\nEsse limite aparece bastante nas provas.'),

    (1, 2, 'Complexidade de algoritmos',
     'Busca linear: O(n)\nBusca binária: O(log n) — exige lista ordenada\nOrdenação por bolha: O(n²)'),

    (1, 3, 'Vocabulário da unidade 4',
     'deadline = prazo final\nassignment = trabalho, tarefa\nreport = relatório'),

    (2, 4, 'Formas normais',
     '1FN: sem grupos repetidos\n2FN: sem dependência parcial da chave\n3FN: sem dependência transitiva');

-- -----------------------------------------------------
-- Tarefas
-- -----------------------------------------------------
-- Algumas vinculadas a disciplina, outras não, para mostrar que
-- o vínculo é opcional.

INSERT INTO `tarefas` (`usuario_id`, `disciplina_id`, `titulo`, `vencimento`, `prioridade`, `status`) VALUES
    (1, 1,    'Lista de exercícios 3',        DATE_ADD(CURDATE(), INTERVAL 2 DAY),  'Alta',  'Não iniciado'),
    (1, 1,    'Revisar limites para a prova', DATE_ADD(CURDATE(), INTERVAL 5 DAY),  'Média', 'Em progresso'),
    (1, 2,    'Implementar busca binária',    DATE_ADD(CURDATE(), INTERVAL 7 DAY),  'Alta',  'Em progresso'),
    (1, 3,    'Redação sobre tecnologia',     DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Baixa', 'Não iniciado'),
    (1, NULL, 'Renovar carteirinha',          DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'Baixa', 'Não iniciado'),
    (2, 4,    'Modelar o banco do trabalho',  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  'Alta',  'Não iniciado');

-- -----------------------------------------------------
-- Eventos
-- -----------------------------------------------------

INSERT INTO `eventos` (`usuario_id`, `disciplina_id`, `titulo`, `data`, `tipo`) VALUES
    (1, 1,    'Prova de Cálculo I',         DATE_ADD(CURDATE(), INTERVAL 6 DAY),  'prova'),
    (1, 2,    'Entrega do projeto final',   DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'trabalho'),
    (1, 3,    'Apresentação oral',          DATE_ADD(CURDATE(), INTERVAL 9 DAY),  'apresentacao'),
    (1, NULL, 'Reunião do grupo de estudos', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'reuniao'),
    (2, 4,    'Prova de Banco de Dados',    DATE_ADD(CURDATE(), INTERVAL 8 DAY),  'prova');

-- -----------------------------------------------------
-- Observação sobre arquivos
-- -----------------------------------------------------
-- A tabela `arquivos` fica vazia de propósito: cada registro dela
-- depende de um arquivo real em public/uploads/disciplinas/. Inserir
-- linhas aqui criaria registros apontando para arquivos inexistentes.
-- Para demonstrar essa parte, envie um PDF pela própria tela do caderno.
