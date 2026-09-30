<?php
/**
 * Conexão com o Banco de Dados - Flashnotes
 * =====================================================
 * Ponto ÚNICO de conexão do sistema. Nenhuma página deve
 * criar `new mysqli(...)` por conta própria: basta incluir
 * este arquivo e usar a variável $conn.
 *
 * As credenciais vêm de app/config/config.php, que não é
 * versionado.
 *
 * Uso:
 *   require_once __DIR__ . '/../app/config/conexao.php';
 */

require_once __DIR__ . '/carregar_config.php';

// Reaproveita a conexão se este arquivo já tiver sido incluído e ela
// continuar viva.
//
// Atenção ao detalhe: uma conexão JÁ FECHADA continua sendo
// `instanceof mysqli`, e só acusa erro no primeiro uso. Algumas páginas
// chamam $conn->close() antes de montar o HTML e depois incluem a
// sidebar, que precisa do banco de novo — por isso o teste é um ping
// dentro de try/catch, e não apenas uma checagem de tipo.

$conexao_viva = false;

if (isset($conn) && $conn instanceof mysqli) {
    try {
        $conexao_viva = @$conn->ping();
    } catch (\Throwable $e) {
        $conexao_viva = false;
    }
}

if (!$conexao_viva) {

    $conn = @new mysqli(
        config('banco.host'),
        config('banco.usuario'),
        config('banco.senha'),
        config('banco.nome')
    );

    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }

    // Garante acentuação correta em toda a aplicação
    $conn->set_charset(config('banco.charset', 'utf8mb4'));
}
?>
