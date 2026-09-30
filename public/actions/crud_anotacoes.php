<?php
/**
 * CRUD Anotações - Flashnotes
 * Processa as operações de anotações do caderno de uma disciplina.
 *
 * Toda operação confere duas coisas antes de tocar no banco:
 * 1. a disciplina informada pertence ao usuário logado;
 * 2. a anotação alvo pertence ao usuário logado.
 */

// Inicia a sessão
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../../app/helpers/sessao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../login.php");
    exit();
}

// =====================================================
// CONEXÃO COM O BANCO DE DADOS
// =====================================================
require_once __DIR__ . '/../../app/config/conexao.php';
require_once __DIR__ . '/../../app/helpers/disciplinas.php';

// Proteção contra CSRF: rejeita POST sem token válido
require_once __DIR__ . '/../../app/helpers/csrf.php';

exigir_csrf('../disciplinas.php');

$usuario_id = $_SESSION['usuario_id'];

// =====================================================
// DISCIPLINA DE DESTINO
// =====================================================

$disciplina_id = validar_disciplina_id(
    $conn,
    $_POST['disciplina_id'] ?? 0,
    $usuario_id
);

if ($disciplina_id === null) {
    $conn->close();

    $_SESSION['mensagem'] = "Disciplina não encontrada.";
    $_SESSION['tipo_mensagem'] = "erro";

    header("Location: ../disciplinas.php");
    exit();
}

// =====================================================
// PROCESSAMENTO DE REQUISIÇÕES (CRUD)
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    $acao = $_POST['acao'];

    // =====================================================
    // ADICIONAR ANOTAÇÃO
    // =====================================================
    if ($acao === 'adicionar') {

        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');

        if (!empty($titulo)) {

            $sql = "INSERT INTO anotacoes
                    (usuario_id, disciplina_id, titulo, conteudo)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("iiss", $usuario_id, $disciplina_id, $titulo, $conteudo);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Anotação criada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao criar anotação: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['mensagem'] = "Informe um título para a anotação.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =====================================================
    // EDITAR ANOTAÇÃO
    // =====================================================
    elseif ($acao === 'editar') {

        $id = intval($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');

        if ($id > 0 && !empty($titulo)) {

            // O usuario_id no WHERE impede editar anotação de outro usuário.
            $sql = "UPDATE anotacoes
                    SET titulo = ?, conteudo = ?
                    WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ssii", $titulo, $conteudo, $id, $usuario_id);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Anotação atualizada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar anotação: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['mensagem'] = "Informe um título para a anotação.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =====================================================
    // EXCLUIR ANOTAÇÃO
    // =====================================================
    elseif ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {

            $sql = "DELETE FROM anotacoes WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ii", $id, $usuario_id);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Anotação excluída com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao excluir anotação: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        }
    }
}

// =====================================================
// FECHA CONEXÃO E REDIRECIONA
// =====================================================

$conn->close();

header("Location: ../caderno.php?id=" . $disciplina_id . "&aba=anotacoes");
exit();
?>
