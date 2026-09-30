-- =====================================================
-- Flashnotes - Migração: remover `usuarios.tipo_perfil`
-- =====================================================
-- A coluna aceitava os valores 'estudante' e 'professor', mas
-- nenhuma funcionalidade do sistema chegou a usá-la: todas as
-- contas eram criadas como 'estudante' e nenhuma tela lia o campo.
--
-- Em vez de manter no banco uma promessa que o código não cumpre,
-- a coluna é removida. O perfil de professor (com compartilhamento
-- de material entre turmas) fica registrado no README como
-- possibilidade de trabalho futuro.
--
-- Execute depois de migracao_cadernos.sql e migracao_tema.sql.
--
-- ATENÇÃO: faça backup antes. A remoção apaga o dado de forma
-- definitiva, mas como todas as contas são 'estudante', não há
-- informação real sendo perdida.
-- =====================================================

SET NAMES utf8mb4;

-- Conferência: mostra quantas contas existem de cada tipo.
-- Se aparecer alguma linha com 'professor', pare e avalie antes
-- de seguir, porque esse dado seria perdido.
SELECT `tipo_perfil`, COUNT(*) AS total
FROM `usuarios`
GROUP BY `tipo_perfil`;

ALTER TABLE `usuarios` DROP COLUMN `tipo_perfil`;
