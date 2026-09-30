<?php

require __DIR__ . '/../config/conexao.php';
require __DIR__ . '/../helpers/enviar_email.php';

date_default_timezone_set('America/Sao_Paulo');

$amanha = date('Y-m-d', strtotime('+1 day'));


// ======================================
// ARRAY DE NOTIFICAÇÕES POR USUÁRIO
// ======================================

$usuarios_notificacoes = [];


// ======================================
// BUSCA TAREFAS
// ======================================

$sql_tarefas = "
    SELECT 
        tarefas.*,
        usuarios.nome,
        usuarios.email,
        usuarios.notificacao_email,
        usuarios.notificacao_navegador
    FROM tarefas

    INNER JOIN usuarios
    ON tarefas.usuario_id = usuarios.id

    WHERE vencimento = ?
    AND status != 'Concluído'
";

$stmt_tarefas = $conn->prepare($sql_tarefas);

$stmt_tarefas->bind_param("s", $amanha);

$stmt_tarefas->execute();

$resultado_tarefas = $stmt_tarefas->get_result();

while ($tarefa = $resultado_tarefas->fetch_assoc()) {

    $usuario_id = $tarefa['usuario_id'];

    // ======================================
    // CRIA ARRAY DO USUÁRIO
    // ======================================

    if (!isset($usuarios_notificacoes[$usuario_id])) {

        $usuarios_notificacoes[$usuario_id] = [
            'nome' => $tarefa['nome'],
            'email' => $tarefa['email'],
            'notificacao_email' => $tarefa['notificacao_email'],
            'notificacao_navegador' => $tarefa['notificacao_navegador'],
            'tarefas' => [],
            'eventos' => []
        ];
    }

    // ======================================
    // ADICIONA TAREFA
    // ======================================

    $usuarios_notificacoes[$usuario_id]['tarefas'][] = [
        'titulo' => $tarefa['titulo'],
        'vencimento' => $tarefa['vencimento']
    ];
}


// ======================================
// BUSCA EVENTOS
// ======================================

$sql_eventos = "
    SELECT 
        eventos.*,
        usuarios.nome,
        usuarios.email,
        usuarios.notificacao_email,
        usuarios.notificacao_navegador
    FROM eventos

    INNER JOIN usuarios
    ON eventos.usuario_id = usuarios.id

    WHERE data = ?
";

$stmt_eventos = $conn->prepare($sql_eventos);

$stmt_eventos->bind_param("s", $amanha);

$stmt_eventos->execute();

$resultado_eventos = $stmt_eventos->get_result();

while ($evento = $resultado_eventos->fetch_assoc()) {

    $usuario_id = $evento['usuario_id'];

    // ======================================
    // CRIA ARRAY DO USUÁRIO
    // ======================================

    if (!isset($usuarios_notificacoes[$usuario_id])) {

        $usuarios_notificacoes[$usuario_id] = [
            'nome' => $evento['nome'],
            'email' => $evento['email'],
            'notificacao_email' => $evento['notificacao_email'],
            'notificacao_navegador' => $evento['notificacao_navegador'],
            'tarefas' => [],
            'eventos' => []
        ];
    }

    // ======================================
    // ADICIONA EVENTO
    // ======================================

    $usuarios_notificacoes[$usuario_id]['eventos'][] = [
        'titulo' => $evento['titulo'],
        'tipo' => $evento['tipo'],
        'data' => $evento['data']
    ];
}


// ======================================
// ENVIA NOTIFICAÇÕES
// ======================================

foreach ($usuarios_notificacoes as $usuario_id => $dados) {

    $mensagemHTML = "
        <h2>Você possui atividades próximas!</h2>

        <p>Olá {$dados['nome']},</p>
    ";

    // ======================================
    // TAREFAS
    // ======================================

    if (!empty($dados['tarefas'])) {

        $mensagemHTML .= "
            <h3>Tarefas próximas do vencimento</h3>
            <ul>
        ";

        foreach ($dados['tarefas'] as $tarefa) {

            $mensagemHTML .= "
                <li>
                    <strong>{$tarefa['titulo']}</strong>
                    vence em {$tarefa['vencimento']}
                </li>
            ";
        }

        $mensagemHTML .= "</ul>";
    }

    // ======================================
    // EVENTOS
    // ======================================

    if (!empty($dados['eventos'])) {

        $mensagemHTML .= "
            <h3>Eventos próximos</h3>
            <ul>
        ";

        foreach ($dados['eventos'] as $evento) {

            $mensagemHTML .= "
                <li>
                    <strong>{$evento['titulo']}</strong>
                    ({$evento['tipo']})
                    em {$evento['data']}
                </li>
            ";
        }

        $mensagemHTML .= "</ul>";
    }

    // ======================================
    // FINALIZA EMAIL
    // ======================================

    $mensagemHTML .= "
        <p>
            Acesse o Flashnotes para mais detalhes.
        </p>
    ";


    // ======================================
    // ENVIA EMAIL
    // ======================================

    if ($dados['notificacao_email'] == 1) {

        enviarEmail(
            $dados['email'],
            "Atividades próximas - Flashnotes",
            $mensagemHTML
        );
    }


    // ======================================
    // SALVA NOTIFICAÇÕES INTERNAS
    // ======================================

    if ($dados['notificacao_navegador'] == 1) {

        $titulo = "Atividades próximas";

        $mensagem = "Você possui tarefas ou eventos próximos.";

        $sql_notificacao = "
            INSERT INTO notificacoes
            (usuario_id, titulo, mensagem)
            VALUES (?, ?, ?)
        ";

        $stmt_notificacao = $conn->prepare($sql_notificacao);

        $stmt_notificacao->bind_param(
            "iss",
            $usuario_id,
            $titulo,
            $mensagem
        );

        $stmt_notificacao->execute();
    }
}

echo "Notificações verificadas com sucesso.";