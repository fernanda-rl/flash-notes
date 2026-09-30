<?php
/**
 * Helper de Disciplinas - Flashnotes
 *
 * Funções compartilhadas pelas telas que precisam ler ou validar
 * disciplinas. Centraliza a regra de isolamento: toda consulta
 * filtra por usuario_id, de modo que um usuário nunca alcança a
 * disciplina de outro.
 */

/**
 * Retorna todas as disciplinas do usuário, em ordem alfabética.
 *
 * @return array Lista de arrays com id, nome, professor e cor.
 */
function buscar_disciplinas_usuario($conn, $usuario_id)
{
    $disciplinas = [];

    $sql = "SELECT id, nome, professor, cor
            FROM disciplinas
            WHERE usuario_id = ?
            ORDER BY nome ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        while ($linha = $resultado->fetch_assoc()) {
            $disciplinas[] = $linha;
        }

        $stmt->close();
    }

    return $disciplinas;
}

/**
 * Busca uma disciplina específica garantindo que ela pertence ao usuário.
 *
 * @return array|null A disciplina, ou null se não existir ou for de outro usuário.
 */
function buscar_disciplina_do_usuario($conn, $disciplina_id, $usuario_id)
{
    $sql = "SELECT id, nome, professor, cor, data_criacao
            FROM disciplinas
            WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $disciplina_id, $usuario_id);
    $stmt->execute();

    $disciplina = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $disciplina ?: null;
}

/**
 * Valida o disciplina_id recebido de um formulário.
 *
 * Usada por tarefas e eventos, onde o vínculo é opcional. Devolve o id
 * apenas quando a disciplina existe E pertence ao usuário; em qualquer
 * outro caso devolve null, que grava NULL na coluna.
 *
 * @return int|null
 */
function validar_disciplina_id($conn, $valor, $usuario_id)
{
    $disciplina_id = intval($valor);

    if ($disciplina_id <= 0) {
        return null;
    }

    $sql = "SELECT id FROM disciplinas WHERE id = ? AND usuario_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $disciplina_id, $usuario_id);
    $stmt->execute();

    $existe = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    return $existe ? $disciplina_id : null;
}

/**
 * Diretório onde os arquivos das disciplinas são gravados.
 * Criado sob demanda no primeiro upload.
 */
function diretorio_uploads_disciplinas()
{
    return __DIR__ . '/../../public/uploads/disciplinas';
}

/**
 * Formata um tamanho em bytes para exibição (ex.: "1,4 MB").
 */
function formatar_tamanho_arquivo($bytes)
{
    $bytes = (float) $bytes;

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }

    return $bytes . ' B';
}
?>
