<?php
/**
 * CRUD Disciplinas - Flashnotes
 *
 * Processa as operações da tela Disciplinas. Desde a criação dos
 * Cadernos, a disciplina vive na tabela `disciplinas` (nome, professor
 * e cor) e as aulas ficam em `horarios`, apontando para ela.
 *
 * Ações: adicionar | editar | excluir | adicionar_aula | excluir_aula
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

// Obtém o ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];

// Dias aceitos no campo de aula
$dias_validos = [
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
    'Domingo'
];

/**
 * Grava uma aula da disciplina, validando dia e horários.
 * Devolve true se a aula foi criada.
 */
function inserir_aula($conn, $usuario_id, $disciplina_id, $dia, $inicio, $fim, $dias_validos)
{
    if (!in_array($dia, $dias_validos, true)) {
        return false;
    }

    if (empty($inicio) || empty($fim)) {
        return false;
    }

    $sql = "INSERT INTO horarios
            (usuario_id, disciplina_id, horario_inicio, horario_fim, dia)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("iisss", $usuario_id, $disciplina_id, $inicio, $fim, $dia);

    $sucesso = $stmt->execute();

    $stmt->close();

    return $sucesso;
}

// =====================================================
// PROCESSAMENTO DE REQUISIÇÕES (CRUD)
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    $acao = $_POST['acao'];

    // =====================================================
    // ADICIONAR DISCIPLINA
    // =====================================================
    // A primeira aula é opcional: a disciplina pode existir só para
    // servir de caderno, sem horário fixo na semana.
    if ($acao === 'adicionar') {

        $nome = trim($_POST['nome'] ?? '');
        $professor = trim($_POST['professor'] ?? '');
        $cor = trim($_POST['cor'] ?? '#0066da');

        $dia = trim($_POST['dia'] ?? '');
        $horario_inicio = trim($_POST['horario_inicio'] ?? '');
        $horario_fim = trim($_POST['horario_fim'] ?? '');

        if (!empty($nome)) {

            $sql = "INSERT INTO disciplinas (usuario_id, nome, professor, cor)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("isss", $usuario_id, $nome, $professor, $cor);

                if ($stmt->execute()) {

                    $disciplina_id = $conn->insert_id;

                    $stmt->close();

                    // Aula inicial, se informada
                    if (!empty($dia) && !empty($horario_inicio) && !empty($horario_fim)) {
                        inserir_aula(
                            $conn, $usuario_id, $disciplina_id,
                            $dia, $horario_inicio, $horario_fim, $dias_validos
                        );
                    }

                    $_SESSION['mensagem'] = "Disciplina adicionada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                }
                // 1062 = violação da chave única (usuario_id, nome)
                elseif ($conn->errno === 1062) {
                    $stmt->close();

                    $_SESSION['mensagem'] = "Você já tem uma disciplina com esse nome.";
                    $_SESSION['tipo_mensagem'] = "erro";
                }
                else {
                    $erro = $stmt->error;
                    $stmt->close();

                    $_SESSION['mensagem'] = "Erro ao adicionar disciplina: " . $erro;
                    $_SESSION['tipo_mensagem'] = "erro";
                }
            }
        } else {
            $_SESSION['mensagem'] = "Informe o nome da disciplina.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =====================================================
    // EDITAR DISCIPLINA
    // =====================================================
    elseif ($acao === 'editar') {

        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $professor = trim($_POST['professor'] ?? '');
        $cor = trim($_POST['cor'] ?? '#0066da');

        if ($id > 0 && !empty($nome)) {

            // O usuario_id no WHERE impede editar disciplina de outro usuário.
            $sql = "UPDATE disciplinas
                    SET nome = ?, professor = ?, cor = ?
                    WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sssii", $nome, $professor, $cor, $id, $usuario_id);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Disciplina atualizada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                }
                elseif ($conn->errno === 1062) {
                    $_SESSION['mensagem'] = "Você já tem uma disciplina com esse nome.";
                    $_SESSION['tipo_mensagem'] = "erro";
                }
                else {
                    $_SESSION['mensagem'] = "Erro ao atualizar disciplina: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['mensagem'] = "Informe o nome da disciplina.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =====================================================
    // EXCLUIR DISCIPLINA
    // =====================================================
    // O banco cuida em cascata das aulas, anotações e registros de
    // arquivos. As tarefas e os eventos são preservados: o vínculo
    // vira NULL e eles continuam nas telas Tarefas e Agenda.
    elseif ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id > 0 && buscar_disciplina_do_usuario($conn, $id, $usuario_id) !== null) {

            // Os binários no disco não são alcançados pelo CASCADE:
            // precisam ser apagados manualmente antes do DELETE.
            $sql = "SELECT nome_armazenado
                    FROM arquivos
                    WHERE disciplina_id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $usuario_id);
            $stmt->execute();

            $resultado = $stmt->get_result();

            $arquivos_no_disco = [];

            while ($linha = $resultado->fetch_assoc()) {
                $arquivos_no_disco[] = $linha['nome_armazenado'];
            }

            $stmt->close();

            $sql = "DELETE FROM disciplinas WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ii", $id, $usuario_id);

                if ($stmt->execute()) {

                    foreach ($arquivos_no_disco as $nome_armazenado) {

                        $caminho = diretorio_uploads_disciplinas() . '/' . basename($nome_armazenado);

                        if (is_file($caminho)) {
                            unlink($caminho);
                        }
                    }

                    $_SESSION['mensagem'] = "Disciplina excluída com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao excluir disciplina: " . $stmt->error;
                    $_SESSION['tipo_mensagem'] = "erro";
                }

                $stmt->close();
            }
        }
    }

    // =====================================================
    // ADICIONAR AULA À DISCIPLINA
    // =====================================================
    elseif ($acao === 'adicionar_aula') {

        $disciplina_id = validar_disciplina_id(
            $conn,
            $_POST['disciplina_id'] ?? 0,
            $usuario_id
        );

        $dia = trim($_POST['dia'] ?? '');
        $horario_inicio = trim($_POST['horario_inicio'] ?? '');
        $horario_fim = trim($_POST['horario_fim'] ?? '');

        if ($disciplina_id === null) {
            $_SESSION['mensagem'] = "Disciplina não encontrada.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
        elseif (inserir_aula(
            $conn, $usuario_id, $disciplina_id,
            $dia, $horario_inicio, $horario_fim, $dias_validos
        )) {
            $_SESSION['mensagem'] = "Aula adicionada com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
        }
        else {
            $_SESSION['mensagem'] = "Preencha o dia e os horários da aula.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }

    // =====================================================
    // EXCLUIR AULA
    // =====================================================
    elseif ($acao === 'excluir_aula') {

        $id = intval($_POST['id'] ?? 0);

        // Guarda a disciplina para poder voltar ao caderno certo
        $disciplina_id = validar_disciplina_id(
            $conn,
            $_POST['disciplina_id'] ?? 0,
            $usuario_id
        );

        if ($id > 0) {

            $sql = "DELETE FROM horarios WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ii", $id, $usuario_id);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Aula removida com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao remover aula: " . $stmt->error;
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

// As ações de aula nascem dentro do caderno, então devolvem o usuário
// para lá. As demais voltam para a grade de disciplinas.

$acao_de_aula = in_array($_POST['acao'] ?? '', ['adicionar_aula', 'excluir_aula'], true);

if ($acao_de_aula && isset($disciplina_id) && $disciplina_id !== null) {
    header("Location: ../caderno.php?id=" . $disciplina_id . "&aba=aulas");
    exit();
}

header("Location: ../disciplinas.php");
exit();
?>
