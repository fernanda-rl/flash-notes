<?php

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../../app/helpers/sessao.php';

require_once __DIR__ . '/../../app/config/conexao.php';
require_once __DIR__ . '/../../app/helpers/disciplinas.php';
require_once __DIR__ . '/../../app/helpers/foco.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';

/*
|--------------------------------------------------------------------------
| MODO DE RESPOSTA
|--------------------------------------------------------------------------
| A tela Foco chama este arquivo por fetch() e espera JSON, sempre: um
| redirecionamento no meio de uma sessão perderia o cronômetro que está
| correndo. Por isso até a falha de login e a de token saem em JSON, como
| já acontece em crud_agenda.php.
*/

header('Content-Type: application/json');

/** Encerra a requisição devolvendo JSON. */
function responder($sucesso, $extra = [])
{
    echo json_encode(array_merge(['sucesso' => $sucesso], $extra));
    exit;
}

if (
    !isset($_SESSION['usuario_logado']) ||
    $_SESSION['usuario_logado'] !== true
) {
    responder(false, ['erro' => 'nao_logado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, ['erro' => 'metodo_invalido']);
}

if (!validar_csrf()) {
    responder(false, ['erro' => 'token_invalido']);
}

$usuario_id = $_SESSION['usuario_id'];

$acao = $_POST['acao'] ?? '';

/*
|--------------------------------------------------------------------------
| SALVAR PREFERÊNCIAS
|--------------------------------------------------------------------------
| Os quatro tempos passam por validar_valor_foco(), que prende cada um
| na faixa do helper. A validação do navegador (min/max nos campos) é
| conveniência; esta aqui é a que vale.
*/

if ($acao === 'salvar_preferencias') {

    $preferencias = [
        'foco_min' =>
            validar_valor_foco('foco_min', $_POST['foco_min'] ?? ''),

        'pausa_min' =>
            validar_valor_foco('pausa_min', $_POST['pausa_min'] ?? ''),

        'pausa_longa_min' =>
            validar_valor_foco('pausa_longa_min', $_POST['pausa_longa_min'] ?? ''),

        'ciclos_ate_pausa_longa' =>
            validar_valor_foco('ciclos_ate_pausa_longa', $_POST['ciclos_ate_pausa_longa'] ?? ''),
    ];

    $sucesso = salvar_preferencias_foco($conn, $usuario_id, $preferencias);

    // Os valores já validados voltam para a tela: se o POST trouxe algo
    // fora da faixa, o formulário se corrige com o que foi gravado.
    responder($sucesso, ['preferencias' => $preferencias]);
}

/*
|--------------------------------------------------------------------------
| REGISTRAR SESSÃO
|--------------------------------------------------------------------------
| Chamado quando um bloco de foco termina (ciclo inteiro) ou quando o
| usuário aperta Parar no meio dele. O tempo em pausa não chega aqui:
| o JavaScript envia apenas os segundos que o cronômetro de foco de fato
| consumiu.
*/

if ($acao === 'registrar_sessao') {

    $segundos = intval($_POST['segundos_focados'] ?? 0);

    // Teto de 24 horas: o valor vem do navegador e nada impede um POST
    // adulterado de pedir uma sessão absurda.
    if ($segundos > 86400) {
        $segundos = 86400;
    }

    // Menos de meio minuto não vira linha no histórico. Quem inicia e
    // para em seguida — por engano, ou só para testar o botão — não
    // enche a lista de sessões de zero minuto.
    if ($segundos < 30) {
        responder(true, ['registrada' => false]);
    }

    $duracao_minutos = (int) round($segundos / 60);

    // Vínculos opcionais: cada um vira NULL se vier vazio ou se apontar
    // para algo de outro usuário.
    $disciplina_id = validar_disciplina_id(
        $conn,
        $_POST['disciplina_id'] ?? 0,
        $usuario_id
    );

    $tarefa_id = validar_tarefa_id(
        $conn,
        $_POST['tarefa_id'] ?? 0,
        $usuario_id
    );

    $concluida = (($_POST['concluida'] ?? '0') === '1') ? 1 : 0;

    /*
    | A data de início vem do navegador porque é lá que o cronômetro
    | corre: uma sessão de 50 minutos só é gravada no fim, e usar NOW()
    | marcaria o término como se fosse o começo.
    |
    | Como é um valor do cliente, ele é conferido: precisa estar no
    | formato esperado e cair numa janela plausível. Fora disso, o
    | relógio do servidor assume — é melhor um horário aproximado do
    | que uma data inventada no histórico. A folga de uma hora para o
    | futuro cobre pequenas diferenças de relógio entre as duas pontas.
    */

    $data_inicio = date('Y-m-d H:i:s');

    $enviada = trim($_POST['data_inicio'] ?? '');

    if ($enviada !== '') {

        $data = DateTime::createFromFormat('Y-m-d H:i:s', $enviada);

        if ($data && $data->format('Y-m-d H:i:s') === $enviada) {

            $momento = $data->getTimestamp();
            $agora = time();

            if ($momento > $agora - 86400 && $momento < $agora + 3600) {
                $data_inicio = $enviada;
            }
        }
    }

    $sql = "INSERT INTO sessoes_foco
            (usuario_id, disciplina_id, tarefa_id, duracao_minutos, data_inicio, concluida)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        responder(false, ['erro' => 'falha_preparo']);
    }

    $stmt->bind_param(
        "iiiisi",
        $usuario_id,
        $disciplina_id,
        $tarefa_id,
        $duracao_minutos,
        $data_inicio,
        $concluida
    );

    if (!$stmt->execute()) {

        $stmt->close();

        responder(false, ['erro' => 'falha_gravacao']);
    }

    $id = $conn->insert_id;

    $stmt->close();

    // Os nomes voltam prontos para a tela inserir a sessão no topo da
    // lista sem precisar recarregar a página — o que interromperia o
    // cronômetro da pausa que acabou de começar.
    $nome_disciplina = null;
    $titulo_tarefa = null;

    if ($disciplina_id !== null) {
        $disciplina = buscar_disciplina_do_usuario($conn, $disciplina_id, $usuario_id);
        $nome_disciplina = $disciplina['nome'] ?? null;
    }

    if ($tarefa_id !== null) {

        $stmt_tarefa = $conn->prepare(
            "SELECT titulo FROM tarefas WHERE id = ? AND usuario_id = ?"
        );

        if ($stmt_tarefa) {

            $stmt_tarefa->bind_param("ii", $tarefa_id, $usuario_id);
            $stmt_tarefa->execute();

            $linha_tarefa = $stmt_tarefa->get_result()->fetch_assoc();

            $stmt_tarefa->close();

            $titulo_tarefa = $linha_tarefa['titulo'] ?? null;
        }
    }

    responder(true, [
        'registrada' => true,
        'sessao' => [
            'id'              => $id,
            'duracao'         => formatar_duracao_foco($duracao_minutos),
            'data_formatada'  => date('d/m/Y H:i', strtotime($data_inicio)),
            'concluida'       => $concluida,
            'disciplina'      => $nome_disciplina,
            'tarefa'          => $titulo_tarefa,
        ],
    ]);
}

responder(false, ['erro' => 'acao_invalida']);
