<?php
/**
 * Tema (aparência) - Flashnotes
 *
 * Incluído no topo de cada página, ANTES do <!DOCTYPE>, para que o
 * atributo do tema já saia na tag <html>. Isso evita o "flash" de
 * tela branca que apareceria se o tema fosse aplicado por JavaScript
 * depois que a página já começou a ser pintada.
 *
 * Uso na página:
 *   require_once __DIR__ . '/tema.php';
 *   ...
 *   <html lang="pt-br"<?php echo $atributo_tema; ?>>
 *
 * Valores possíveis:
 *   'sistema' - não escreve atributo nenhum, então o CSS decide pelo
 *               prefers-color-scheme do navegador/sistema.
 *   'claro'   - data-tema="claro"
 *   'escuro'  - data-tema="escuro"
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// O tema fica na sessão para não custar uma consulta por página.
// Sessões abertas antes desta funcionalidade não têm a chave, então
// a primeira página carregada busca o valor no banco e guarda.
if (!isset($_SESSION['tema']) && isset($_SESSION['usuario_id'])) {

    // Reaproveita a conexão da página quando ela ainda está viva.
    // Atenção: uma conexão já fechada continua sendo instanceof mysqli,
    // e só dá erro no primeiro uso — por isso o try/catch em vez de um
    // teste de tipo. Páginas como tarefas.php e horarios.php fecham a
    // conexão antes de incluir este arquivo.
    $conexao_viva = false;

    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conexao_viva = @$conn->ping();
        } catch (\Throwable $e) {
            $conexao_viva = false;
        }
    }

    if (!$conexao_viva) {
        // Abre uma conexão nova a partir do config. Como $conn passa a
        // apontar para ela, a sidebar incluída em seguida também volta
        // a funcionar.
        require_once __DIR__ . '/../app/config/carregar_config.php';

        $conn = @new mysqli(
            config('banco.host'),
            config('banco.usuario'),
            config('banco.senha'),
            config('banco.nome')
        );

        if ($conn->connect_errno) {
            $conn = null;
        } else {
            $conn->set_charset(config('banco.charset', 'utf8mb4'));
        }
    }

    $_SESSION['tema'] = 'sistema';

    if ($conn !== null) {
        try {
            $stmt_tema = $conn->prepare("SELECT tema FROM usuarios WHERE id = ?");

            if ($stmt_tema) {
                $stmt_tema->bind_param("i", $_SESSION['usuario_id']);
                $stmt_tema->execute();

                $linha_tema = $stmt_tema->get_result()->fetch_assoc();

                $stmt_tema->close();

                $_SESSION['tema'] = $linha_tema['tema'] ?? 'sistema';
            }
        } catch (\Throwable $e) {
            // Sem preferência acessível, o padrão 'sistema' já está posto.
        }
    }
}

$tema = $_SESSION['tema'] ?? 'sistema';

// Só temas explícitos viram atributo; 'sistema' deixa o CSS decidir.
$atributo_tema = in_array($tema, ['claro', 'escuro'], true)
    ? ' data-tema="' . $tema . '"'
    : '';
?>
