<?php
/**
 * Download de Arquivo - Flashnotes
 *
 * Único caminho de acesso aos arquivos das disciplinas: a pasta de
 * uploads é bloqueada por .htaccess. Antes de entregar o binário,
 * confirma no banco que ele pertence ao usuário logado.
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

$usuario_id = $_SESSION['usuario_id'];

$id = intval($_GET['id'] ?? 0);

// =====================================================
// BUSCA O ARQUIVO DO USUÁRIO
// =====================================================

$sql = "SELECT nome_original, nome_armazenado, tipo_mime
        FROM arquivos
        WHERE id = ? AND usuario_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $usuario_id);
$stmt->execute();

$arquivo = $stmt->get_result()->fetch_assoc();

$stmt->close();
$conn->close();

// Arquivo inexistente ou de outro usuário: mesma resposta, sem pistas.
if (!$arquivo) {
    http_response_code(404);
    echo "Arquivo não encontrado.";
    exit();
}

// =====================================================
// ENTREGA O ARQUIVO
// =====================================================

// basename descarta qualquer tentativa de travessia de diretório que
// tenha chegado à coluna, mesmo que o nome seja sempre gerado por nós.
$caminho = diretorio_uploads_disciplinas() . '/' . basename($arquivo['nome_armazenado']);

if (!is_file($caminho)) {
    http_response_code(404);
    echo "Arquivo não encontrado.";
    exit();
}

// Com ?download=1 o arquivo é sempre salvo. Sem o parâmetro, PDFs e
// imagens abrem no navegador e o resto vai como download.
$forcar_download = isset($_GET['download']) && $_GET['download'] === '1';

$abrir_no_navegador = !$forcar_download && in_array(
    $arquivo['tipo_mime'],
    ['application/pdf', 'image/png', 'image/jpeg', 'image/gif'],
    true
);

$disposicao = $abrir_no_navegador ? 'inline' : 'attachment';

// Limpa qualquer buffer para não corromper o binário.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $arquivo['tipo_mime']);
header('Content-Length: ' . filesize($caminho));
// Dois nomes no cabeçalho, como manda a RFC 6266:
// - filename= : versão ASCII simples, para navegadores antigos. Não
//   pode ir percent-encoded, senão o arquivo é salvo com "%20" no nome.
// - filename*= : versão UTF-8, usada pelos navegadores atuais e que
//   preserva acentos.
// Troca os acentos pelas letras sem acento antes de limpar o resto, para
// que o fallback fique legível ("Revisao de Fisica.pdf"). Não usamos o
// iconv com //TRANSLIT porque no Windows ele devolve "Revis~ao de F'isica".
$acentos = [
    'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
    'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
    'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
    'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
    'ç'=>'c','ñ'=>'n',
    'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','Ä'=>'A',
    'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
    'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
    'Ó'=>'O','Ò'=>'O','Õ'=>'O','Ô'=>'O','Ö'=>'O',
    'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
    'Ç'=>'C','Ñ'=>'N'
];

$nome_ascii = strtr($arquivo['nome_original'], $acentos);

$nome_ascii = preg_replace('/[^\x20-\x7E]/', '_', $nome_ascii);
$nome_ascii = str_replace(['"', '\\'], '_', $nome_ascii);

header(
    'Content-Disposition: ' . $disposicao .
    '; filename="' . $nome_ascii . '"' .
    "; filename*=UTF-8''" . rawurlencode($arquivo['nome_original'])
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($caminho);
exit();
?>
