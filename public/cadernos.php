<?php
/**
 * Cadernos - Flashnotes
 *
 * A lista de cadernos foi unificada com a tela de Disciplinas: cada
 * disciplina é o seu próprio caderno, e o card abre caderno.php.
 *
 * Este arquivo permanece apenas para que links e favoritos antigos
 * continuem funcionando, encaminhando para a tela atual.
 */

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

header("Location: disciplinas.php");
exit();
?>
