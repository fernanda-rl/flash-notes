<?php

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../../app/helpers/sessao.php';

include __DIR__ . '/../../app/config/conexao.php';
require_once __DIR__ . '/../../app/helpers/disciplinas.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';

/*
|--------------------------------------------------------------------------
| MODO DE RESPOSTA
|--------------------------------------------------------------------------
| A tela Agenda chama este arquivo por fetch() e espera JSON. O caderno
| envia um formulário comum e espera ser redirecionado de volta. O campo
| oculto "retorno" distingue os dois casos.
*/

$vindo_do_caderno = (($_POST['retorno'] ?? '') === 'caderno');

if (!$vindo_do_caderno) {
    header('Content-Type: application/json');
}

/** Encerra a requisição no formato esperado por quem chamou. */
function responder($sucesso, $disciplina_id, $extra = [])
{
    global $vindo_do_caderno;

    if ($vindo_do_caderno) {

        $_SESSION['mensagem'] = $sucesso
            ? "Evento salvo com sucesso!"
            : "Erro ao salvar o evento.";

        $_SESSION['tipo_mensagem'] = $sucesso ? "sucesso" : "erro";

        $destino = ($disciplina_id !== null)
            ? "../caderno.php?id=" . $disciplina_id . "&aba=eventos"
            : "../agenda.php";

        header("Location: " . $destino);
        exit;
    }

    echo json_encode(array_merge(['sucesso' => $sucesso], $extra));
    exit;
}

if (!isset($_SESSION['usuario_logado'])) {
    responder(false, null);
}

// Proteção contra CSRF. Como este arquivo também responde a chamadas
// AJAX, a falha é devolvida no mesmo formato de quem chamou (JSON ou
// redirecionamento), em vez de uma página de erro.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validar_csrf()) {
    responder(false, null, ['erro' => 'token_invalido']);
}

$usuario_id = $_SESSION['usuario_id'];

$acao = $_POST['acao'] ?? '';

// Vínculo opcional com disciplina: devolve null se vazio ou se a
// disciplina informada não pertencer ao usuário logado.
$disciplina_id = validar_disciplina_id(
    $conn,
    $_POST['disciplina_id'] ?? 0,
    $usuario_id
);

/*
|--------------------------------------------------------------------------
| ADICIONAR
|--------------------------------------------------------------------------
*/

if ($acao == 'adicionar') {

    $titulo = $_POST['titulo'];
    $data = $_POST['data'];
    $tipo = $_POST['tipo'];

    $sql = "INSERT INTO eventos
            (usuario_id, disciplina_id, titulo, data, tipo)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iisss",
        $usuario_id,
        $disciplina_id,
        $titulo,
        $data,
        $tipo
    );

    if ($stmt->execute()) {
        responder(true, $disciplina_id, ['id' => $conn->insert_id]);
    } else {
        responder(false, $disciplina_id);
    }
}

/*
|--------------------------------------------------------------------------
| EDITAR
|--------------------------------------------------------------------------
*/

if ($acao == 'editar') {

    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $data = $_POST['data'];
    $tipo = $_POST['tipo'];

    $sql = "UPDATE eventos
            SET disciplina_id = ?, titulo = ?, data = ?, tipo = ?
            WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "isssii",
        $disciplina_id,
        $titulo,
        $data,
        $tipo,
        $id,
        $usuario_id
    );

    responder($stmt->execute(), $disciplina_id);
}

/*
|--------------------------------------------------------------------------
| EXCLUIR
|--------------------------------------------------------------------------
*/

if ($acao == 'excluir') {

    $id = $_POST['id'];

    $sql = "DELETE FROM eventos
            WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $id,
        $usuario_id
    );

    responder($stmt->execute(), $disciplina_id);
}