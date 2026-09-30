<?php

require __DIR__ . '/../config/conexao.php';
require __DIR__ . '/../helpers/enviar_email.php';

date_default_timezone_set('America/Sao_Paulo');


// ======================================
// DEFINE PERÍODO DA SEMANA
// ======================================

$inicio_semana = date('Y-m-d');

$fim_semana = date(
    'Y-m-d',
    strtotime('+7 days')
);


// ======================================
// BUSCA USUÁRIOS
// ======================================

$sql_usuarios = "
    SELECT *
    FROM usuarios
    WHERE resumo_semanal = 1
";

$resultado_usuarios = $conn->query($sql_usuarios);


// ======================================
// LOOP USUÁRIOS
// ======================================

while ($usuario = $resultado_usuarios->fetch_assoc()) {

    $usuario_id = $usuario['id'];

    // ======================================
    // BUSCA TAREFAS DA SEMANA
    // ======================================

    $sql_tarefas = "
        SELECT *
        FROM tarefas
        WHERE usuario_id = ?
        AND vencimento BETWEEN ? AND ?
        AND status != 'Concluído'
        ORDER BY vencimento ASC
    ";

    $stmt_tarefas = $conn->prepare($sql_tarefas);

    $stmt_tarefas->bind_param(
        "iss",
        $usuario_id,
        $inicio_semana,
        $fim_semana
    );

    $stmt_tarefas->execute();

    $resultado_tarefas = $stmt_tarefas->get_result();


    // ======================================
    // BUSCA EVENTOS DA SEMANA
    // ======================================

    $sql_eventos = "
        SELECT *
        FROM eventos
        WHERE usuario_id = ?
        AND data BETWEEN ? AND ?
        ORDER BY data ASC
    ";

    $stmt_eventos = $conn->prepare($sql_eventos);

    $stmt_eventos->bind_param(
        "iss",
        $usuario_id,
        $inicio_semana,
        $fim_semana
    );

    $stmt_eventos->execute();

    $resultado_eventos = $stmt_eventos->get_result();


    // ======================================
    // MONTA EMAIL
    // ======================================

    $mensagemHTML = "
        <h2>Resumo semanal - Flashnotes</h2>

        <p>Olá {$usuario['nome']},</p>

        <p>
            Aqui está um resumo das suas próximas atividades da semana.
        </p>
    ";


    // ======================================
    // TAREFAS
    // ======================================

    if ($resultado_tarefas->num_rows > 0) {

        $mensagemHTML .= "
            <h3>Tarefas da semana</h3>
            <ul>
        ";

        while ($tarefa = $resultado_tarefas->fetch_assoc()) {

            $mensagemHTML .= "
                <li>
                    <strong>{$tarefa['titulo']}</strong>
                    — vence em {$tarefa['vencimento']}
                </li>
            ";
        }

        $mensagemHTML .= "</ul>";
    }


    // ======================================
    // EVENTOS
    // ======================================

    if ($resultado_eventos->num_rows > 0) {

        $mensagemHTML .= "
            <h3>Eventos da semana</h3>
            <ul>
        ";

        while ($evento = $resultado_eventos->fetch_assoc()) {

            $mensagemHTML .= "
                <li>
                    <strong>{$evento['titulo']}</strong>
                    ({$evento['tipo']})
                    — {$evento['data']}
                </li>
            ";
        }

        $mensagemHTML .= "</ul>";
    }


    // ======================================
    // RODAPÉ
    // ======================================

    $mensagemHTML .= "
        <p>
            Organize sua semana acessando o Flashnotes.
        </p>
    ";


    // ======================================
    // ENVIA EMAIL
    // ======================================

    enviarEmail(
        $usuario['email'],
        "Resumo semanal - Flashnotes",
        $mensagemHTML
    );
}

echo "Resumo semanal enviado com sucesso.";