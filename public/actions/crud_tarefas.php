<?php

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../../app/helpers/sessao.php';

// =====================================================
// VERIFICA LOGIN
// =====================================================

if (
    !isset($_SESSION['usuario_logado']) ||
    $_SESSION['usuario_logado'] !== true
) {
    header("Location: ../login.php");
    exit();
}

// =====================================================
// CONEXÃO COM BANCO
// =====================================================

require_once __DIR__ . '/../../app/config/conexao.php';

require_once __DIR__ . '/../../app/helpers/disciplinas.php';

// Proteção contra CSRF: rejeita POST sem token válido
require_once __DIR__ . '/../../app/helpers/csrf.php';

exigir_csrf('../tarefas.php');

// =====================================================
// USUÁRIO LOGADO
// =====================================================

$usuario_id = $_SESSION['usuario_id'];

// =====================================================
// DISCIPLINA VINCULADA (opcional)
// =====================================================
// Resolvida uma única vez, porque além de ir para o banco ela também
// decide o redirecionamento do fim do arquivo. Devolve null quando o
// campo vem vazio ou quando a disciplina é de outro usuário.

$disciplina_id = validar_disciplina_id(
    $conn,
    $_POST['disciplina_id'] ?? 0,
    $usuario_id
);

// =====================================================
// PROCESSAR AÇÕES
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['acao'])
) {

    $acao = $_POST['acao'];

    // =================================================
    // ADICIONAR TAREFA
    // =================================================

    if ($acao === 'adicionar') {

        $titulo = trim($_POST['titulo'] ?? '');

        $vencimento = trim($_POST['vencimento'] ?? '');

        $prioridade = trim($_POST['prioridade'] ?? '');

        $status = trim($_POST['status'] ?? 'Não iniciado');

        if (
            !empty($titulo) &&
            !empty($vencimento) &&
            !empty($prioridade) &&
            !empty($status)
        ) {

            $sql = "
                INSERT INTO tarefas
                (
                    usuario_id,
                    disciplina_id,
                    titulo,
                    vencimento,
                    prioridade,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "iissss",
                    $usuario_id,
                    $disciplina_id,
                    $titulo,
                    $vencimento,
                    $prioridade,
                    $status
                );

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Tarefa adicionada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao adicionar tarefa: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['mensagem'] = "Por favor, preencha todos os campos.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =================================================
    // EDITAR TAREFA
    // =================================================

    elseif ($acao === 'editar') {

        $id = intval($_POST['id'] ?? 0);

        $titulo = trim($_POST['titulo'] ?? '');

        $vencimento = trim($_POST['vencimento'] ?? '');

        $prioridade = trim($_POST['prioridade'] ?? '');

        $status = trim($_POST['status'] ?? '');

        if (
            $id > 0 &&
            !empty($titulo) &&
            !empty($vencimento) &&
            !empty($prioridade) &&
            !empty($status)
        ) {

            $sql = "
                UPDATE tarefas
                SET
                    disciplina_id = ?,
                    titulo = ?,
                    vencimento = ?,
                    prioridade = ?,
                    status = ?
                WHERE id = ?
                AND usuario_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "issssii",
                    $disciplina_id,
                    $titulo,
                    $vencimento,
                    $prioridade,
                    $status,
                    $id,
                    $usuario_id
                );

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Tarefa atualizada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar tarefa: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['mensagem'] = "Por favor, preencha todos os campos.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =================================================
    // EXCLUIR TAREFA
    // =================================================

    elseif ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {

            $sql = "
                DELETE FROM tarefas
                WHERE id = ?
                AND usuario_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $id,
                    $usuario_id
                );

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Tarefa excluída com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao excluir tarefa: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        }
    }
}

// =====================================================
// FECHA CONEXÃO
// =====================================================

$conn->close();

// =====================================================
// REDIRECIONA
// =====================================================
// Quando a tarefa foi criada de dentro de um caderno, o usuário volta
// para lá; caso contrário, segue o fluxo normal da tela de Tarefas.

if (($_POST['retorno'] ?? '') === 'caderno' && $disciplina_id !== null) {
    header("Location: ../caderno.php?id=" . $disciplina_id . "&aba=tarefas");
    exit();
}

header("Location: ../tarefas.php");
exit();

?>