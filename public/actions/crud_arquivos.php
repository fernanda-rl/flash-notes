<?php
/**
 * CRUD Arquivos - Flashnotes
 * Envio e exclusão dos arquivos de uma disciplina (principalmente PDFs).
 *
 * O binário vai para public/uploads/disciplinas/ com um nome aleatório;
 * o banco guarda o nome original para exibição. O nome enviado pelo
 * usuário nunca vira nome de arquivo no disco, e a extensão é conferida
 * contra uma lista fixa.
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
// REGRAS DE UPLOAD
// =====================================================

const TAMANHO_MAXIMO_ARQUIVO = 10485760; // 10 MB

/**
 * Confere se o conteúdo real do arquivo corresponde à extensão.
 *
 * A extensão é só o final do nome e qualquer um pode escrever o que
 * quiser nela. O finfo lê os bytes iniciais do arquivo e devolve o
 * tipo verdadeiro; se os dois não baterem, o envio é recusado.
 *
 * Alguns formatos aceitam mais de um MIME: .docx, .xlsx, .pptx e .zip
 * são todos arquivos ZIP por dentro, e arquivos .txt são detectados
 * ora como text/plain, ora como application/octet-stream.
 *
 * @param string $caminho_temporario Arquivo recém-enviado
 * @param string $extensao           Extensão já validada na lista
 * @return bool
 */
function tipo_real_confere($caminho_temporario, $extensao)
{
    // Se a extensão finfo não estiver ativa, não há como conferir:
    // a validação por lista de extensões continua valendo.
    if (!function_exists('finfo_open')) {
        return true;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if ($finfo === false) {
        return true;
    }

    $tipo_real = finfo_file($finfo, $caminho_temporario);

    finfo_close($finfo);

    if ($tipo_real === false) {
        return false;
    }

    // MIMEs aceitos para cada extensão permitida
    $aceitos = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office',
                   'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office',
                   'application/x-ole-storage', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation',
                   'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office',
                   'application/x-ole-storage', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'application/zip'],
        'txt'  => ['text/plain', 'application/octet-stream'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif'  => ['image/gif'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
    ];

    if (!isset($aceitos[$extensao])) {
        return false;
    }

    return in_array($tipo_real, $aceitos[$extensao], true);
}

$extensoes_permitidas = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'  => 'text/plain',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'zip'  => 'application/zip'
];

// =====================================================
// ENVIO MAIOR QUE post_max_size
// =====================================================
// Quando o corpo da requisição estoura post_max_size, o PHP descarta
// $_POST e $_FILES inteiros. Sem este tratamento, a ausência de
// disciplina_id viraria um enganoso "Caderno não encontrado".

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($_POST) &&
    empty($_FILES) &&
    (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
) {
    $conn->close();

    $_SESSION['mensagem'] = "O arquivo é grande demais para ser enviado. O limite é de 10 MB.";
    $_SESSION['tipo_mensagem'] = "erro";

    header("Location: ../disciplinas.php");
    exit();
}

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
// PROCESSAMENTO DE REQUISIÇÕES
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    $acao = $_POST['acao'];

    // =====================================================
    // ENVIAR ARQUIVO
    // =====================================================
    if ($acao === 'adicionar') {

        $envio = $_FILES['arquivo'] ?? null;

        if ($envio === null || $envio['error'] === UPLOAD_ERR_NO_FILE) {

            $_SESSION['mensagem'] = "Selecione um arquivo para enviar.";
            $_SESSION['tipo_mensagem'] = "erro";
        }

        // Erros de upload do próprio PHP (inclui arquivo maior que
        // upload_max_filesize, que estoura antes da nossa checagem).
        elseif ($envio['error'] !== UPLOAD_ERR_OK) {

            if ($envio['error'] === UPLOAD_ERR_INI_SIZE || $envio['error'] === UPLOAD_ERR_FORM_SIZE) {
                $_SESSION['mensagem'] = "O arquivo é grande demais. O limite é de 10 MB.";
            } else {
                $_SESSION['mensagem'] = "Não foi possível enviar o arquivo. Tente novamente.";
            }

            $_SESSION['tipo_mensagem'] = "erro";
        }

        elseif ($envio['size'] > TAMANHO_MAXIMO_ARQUIVO) {

            $_SESSION['mensagem'] = "O arquivo é grande demais. O limite é de 10 MB.";
            $_SESSION['tipo_mensagem'] = "erro";
        }

        elseif ($envio['size'] <= 0) {

            $_SESSION['mensagem'] = "O arquivo está vazio.";
            $_SESSION['tipo_mensagem'] = "erro";
        }

        else {

            $nome_original = $envio['name'];

            $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

            if (!isset($extensoes_permitidas[$extensao])) {

                $_SESSION['mensagem'] = "Tipo de arquivo não permitido.";
                $_SESSION['tipo_mensagem'] = "erro";
            }

            // is_uploaded_file garante que o caminho veio mesmo de um upload.
            elseif (!is_uploaded_file($envio['tmp_name'])) {

                $_SESSION['mensagem'] = "Envio inválido.";
                $_SESSION['tipo_mensagem'] = "erro";
            }

            // Confere o tipo REAL do arquivo, lendo os primeiros bytes
            // com finfo. Sem isto, bastaria renomear um .php para .pdf
            // para que a extensão passasse na checagem acima.
            elseif (!tipo_real_confere($envio['tmp_name'], $extensao)) {

                $_SESSION['mensagem'] =
                    "O conteúdo do arquivo não corresponde à extensão informada.";
                $_SESSION['tipo_mensagem'] = "erro";
            }

            else {

                $diretorio = diretorio_uploads_disciplinas();

                if (!is_dir($diretorio)) {
                    mkdir($diretorio, 0755, true);
                }

                // Nome no disco: aleatório, sem relação com o nome enviado.
                $nome_armazenado = bin2hex(random_bytes(16)) . '.' . $extensao;

                $destino = $diretorio . '/' . $nome_armazenado;

                if (move_uploaded_file($envio['tmp_name'], $destino)) {

                    $tipo_mime = $extensoes_permitidas[$extensao];
                    $tamanho = (int) $envio['size'];

                    // Limita o nome exibido ao tamanho da coluna.
                    $nome_original = mb_substr($nome_original, 0, 255);

                    $sql = "INSERT INTO arquivos
                            (usuario_id, disciplina_id, nome_original, nome_armazenado, tipo_mime, tamanho)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param(
                            "iisssi",
                            $usuario_id,
                            $disciplina_id,
                            $nome_original,
                            $nome_armazenado,
                            $tipo_mime,
                            $tamanho
                        );

                        if ($stmt->execute()) {
                            $_SESSION['mensagem'] = "Arquivo enviado com sucesso!";
                            $_SESSION['tipo_mensagem'] = "sucesso";
                        } else {
                            // O registro falhou: o arquivo órfão no disco é removido.
                            unlink($destino);

                            $_SESSION['mensagem'] = "Erro ao registrar o arquivo: " . $stmt->error;
                            $_SESSION['tipo_mensagem'] = "erro";
                        }

                        $stmt->close();
                    } else {
                        unlink($destino);

                        $_SESSION['mensagem'] = "Erro ao registrar o arquivo.";
                        $_SESSION['tipo_mensagem'] = "erro";
                    }
                } else {
                    $_SESSION['mensagem'] = "Não foi possível gravar o arquivo no servidor.";
                    $_SESSION['tipo_mensagem'] = "erro";
                }
            }
        }
    }

    // =====================================================
    // EXCLUIR ARQUIVO
    // =====================================================
    elseif ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {

            // Busca o nome no disco conferindo o dono, para poder apagar
            // o binário junto com o registro.
            $sql = "SELECT nome_armazenado
                    FROM arquivos
                    WHERE id = ? AND usuario_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $usuario_id);
            $stmt->execute();

            $arquivo = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if ($arquivo) {

                $sql = "DELETE FROM arquivos WHERE id = ? AND usuario_id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $id, $usuario_id);

                if ($stmt->execute()) {

                    $caminho = diretorio_uploads_disciplinas() . '/' . $arquivo['nome_armazenado'];

                    if (is_file($caminho)) {
                        unlink($caminho);
                    }

                    $_SESSION['mensagem'] = "Arquivo excluído com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                } else {
                    $_SESSION['mensagem'] = "Erro ao excluir arquivo: " . $stmt->error;
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

header("Location: ../caderno.php?id=" . $disciplina_id . "&aba=arquivos");
exit();
?>
