-- =====================================================
-- Flashnotes - Migração "Tema" (aparência)
-- =====================================================
-- Guarda a preferência de aparência de cada usuário.
--
-- 'sistema' (padrão) acompanha o modo claro/escuro do navegador
-- ou do sistema operacional; 'claro' e 'escuro' fixam a escolha.
--
-- Execute depois de migracao_cadernos.sql.
-- =====================================================

SET NAMES utf8mb4;

ALTER TABLE `usuarios`
  ADD COLUMN `tema` enum('sistema','claro','escuro') NOT NULL DEFAULT 'sistema'
  AFTER `resumo_semanal`;
