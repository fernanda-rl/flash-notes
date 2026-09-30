<?php
/**
 * Helper de Sessões de Foco - Flashnotes
 *
 * Funções compartilhadas pela tela Foco e pela action que grava as
 * preferências e as sessões. Centraliza duas regras:
 *
 * 1. Os LIMITES dos tempos. As mesmas faixas são usadas nos campos
 *    do formulário (min/max do HTML) e na validação do servidor, de
 *    modo que a checagem do navegador e a do PHP nunca divergem.
 *    A do navegador é conveniência; a que vale é a daqui.
 *
 * 2. O ISOLAMENTO por usuário: toda consulta filtra por usuario_id,
 *    então ninguém alcança a sessão ou a preferência de outro.
 */

// Faixas aceitas para cada tempo, em minutos, e para o número de
// ciclos até a pausa longa. O 'padrao' é o Pomodoro clássico, usado
// por quem ainda não salvou nenhuma configuração.
function limites_foco()
{
    return [
        'foco_min'               => ['min' => 1, 'max' => 180, 'padrao' => 25],
        'pausa_min'              => ['min' => 1, 'max' => 60,  'padrao' => 5],
        'pausa_longa_min'        => ['min' => 1, 'max' => 60,  'padrao' => 15],
        'ciclos_ate_pausa_longa' => ['min' => 1, 'max' => 12,  'padrao' => 4],
    ];
}

/**
 * Preferências padrão, na forma em que a tela as consome.
 */
function preferencias_foco_padrao()
{
    $padroes = [];

    foreach (limites_foco() as $campo => $limite) {
        $padroes[$campo] = $limite['padrao'];
    }

    return $padroes;
}

/**
 * Prende um valor recebido do formulário dentro da faixa do campo.
 *
 * Valor vazio, não numérico ou fora da faixa vira o padrão — em vez
 * de ser recusado — porque um número inválido aqui não é um erro que
 * o usuário precise corrigir: o formulário já limita os campos, e um
 * POST adulterado só consegue gravar um tempo razoável.
 *
 * @return int
 */
function validar_valor_foco($campo, $valor)
{
    $limites = limites_foco();

    if (!isset($limites[$campo])) {
        return 0;
    }

    $limite = $limites[$campo];

    if (!is_numeric($valor)) {
        return $limite['padrao'];
    }

    $numero = intval($valor);

    if ($numero < $limite['min'] || $numero > $limite['max']) {
        return $limite['padrao'];
    }

    return $numero;
}

/**
 * Busca a configuração salva do usuário.
 *
 * Quem nunca salvou nada não tem linha na tabela: nesse caso devolve
 * os padrões, sem gravar nada.
 *
 * @return array Com as quatro chaves de limites_foco().
 */
function buscar_preferencias_foco($conn, $usuario_id)
{
    $sql = "SELECT foco_min, pausa_min, pausa_longa_min, ciclos_ate_pausa_longa
            FROM preferencias_foco
            WHERE usuario_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return preferencias_foco_padrao();
    }

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $linha = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$linha) {
        return preferencias_foco_padrao();
    }

    // Vêm do banco como string; a tela e o JavaScript esperam número.
    return [
        'foco_min'               => (int) $linha['foco_min'],
        'pausa_min'              => (int) $linha['pausa_min'],
        'pausa_longa_min'        => (int) $linha['pausa_longa_min'],
        'ciclos_ate_pausa_longa' => (int) $linha['ciclos_ate_pausa_longa'],
    ];
}

/**
 * Grava a configuração do usuário, criando a linha na primeira vez.
 *
 * O ON DUPLICATE KEY UPDATE se apoia na chave primária usuario_id,
 * que garante uma única linha por usuário.
 *
 * @return bool
 */
function salvar_preferencias_foco($conn, $usuario_id, $preferencias)
{
    $sql = "INSERT INTO preferencias_foco
            (usuario_id, foco_min, pausa_min, pausa_longa_min, ciclos_ate_pausa_longa)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                foco_min = VALUES(foco_min),
                pausa_min = VALUES(pausa_min),
                pausa_longa_min = VALUES(pausa_longa_min),
                ciclos_ate_pausa_longa = VALUES(ciclos_ate_pausa_longa)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "iiiii",
        $usuario_id,
        $preferencias['foco_min'],
        $preferencias['pausa_min'],
        $preferencias['pausa_longa_min'],
        $preferencias['ciclos_ate_pausa_longa']
    );

    $sucesso = $stmt->execute();

    $stmt->close();

    return $sucesso;
}

/**
 * Valida o tarefa_id recebido de um formulário.
 *
 * Mesma ideia de validar_disciplina_id(): devolve o id apenas quando
 * a tarefa existe E pertence ao usuário; caso contrário devolve null,
 * que grava NULL na coluna.
 *
 * @return int|null
 */
function validar_tarefa_id($conn, $valor, $usuario_id)
{
    $tarefa_id = intval($valor);

    if ($tarefa_id <= 0) {
        return null;
    }

    $sql = "SELECT id FROM tarefas WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $tarefa_id, $usuario_id);
    $stmt->execute();

    $existe = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    return $existe ? $tarefa_id : null;
}

/**
 * Tarefas que podem ser vinculadas a uma sessão.
 *
 * As concluídas ficam de fora: focar numa tarefa já terminada não faz
 * sentido, e a lista curta é mais fácil de usar.
 *
 * @return array Lista com id, titulo e disciplina_id.
 */
function buscar_tarefas_para_foco($conn, $usuario_id)
{
    $tarefas = [];

    $sql = "SELECT id, titulo, disciplina_id
            FROM tarefas
            WHERE usuario_id = ?
            AND status != 'Concluído'
            ORDER BY vencimento ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        while ($linha = $resultado->fetch_assoc()) {
            $tarefas[] = $linha;
        }

        $stmt->close();
    }

    return $tarefas;
}

/**
 * Últimas sessões registradas pelo usuário, da mais recente para a
 * mais antiga.
 *
 * LEFT JOIN nos dois vínculos: a sessão pode não ter disciplina nem
 * tarefa, e continua aparecendo na lista de qualquer forma.
 *
 * @return array
 */
function buscar_sessoes_foco($conn, $usuario_id, $limite = 10)
{
    $sessoes = [];

    $sql = "SELECT s.id,
                   s.duracao_minutos,
                   s.data_inicio,
                   s.concluida,
                   d.nome AS disciplina,
                   t.titulo AS tarefa
            FROM sessoes_foco s
            LEFT JOIN disciplinas d ON d.id = s.disciplina_id
            LEFT JOIN tarefas t ON t.id = s.tarefa_id
            WHERE s.usuario_id = ?
            ORDER BY s.data_inicio DESC, s.id DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("ii", $usuario_id, $limite);
        $stmt->execute();

        $resultado = $stmt->get_result();

        while ($linha = $resultado->fetch_assoc()) {
            $sessoes[] = $linha;
        }

        $stmt->close();
    }

    return $sessoes;
}

/**
 * Formata a duração para exibição ("45 min", "1 h 15 min").
 */
function formatar_duracao_foco($minutos)
{
    $minutos = (int) $minutos;

    if ($minutos < 60) {
        return $minutos . ' min';
    }

    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;

    return $resto === 0
        ? $horas . ' h'
        : $horas . ' h ' . $resto . ' min';
}
?>
