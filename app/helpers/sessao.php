<?php
/**
 * Sessão Segura - Flashnotes
 * =====================================================
 * Configura o cookie de sessão ANTES de a sessão ser iniciada e
 * então inicia a sessão.
 *
 * Precisa ser incluído no lugar de `session_start()`, e sempre
 * como a primeira coisa do arquivo — depois que a sessão começa
 * não dá mais para mudar as opções do cookie.
 *
 * Uso:
 *   require_once __DIR__ . '/../app/helpers/sessao.php';
 *
 * O que é configurado:
 * - HttpOnly: o JavaScript da página não consegue ler o cookie,
 *   o que limita o estrago de uma falha de XSS.
 * - SameSite=Lax: o navegador não envia o cookie em requisições
 *   vindas de outros sites, o que corta a maior parte dos ataques
 *   CSRF (o token do csrf.php cobre o resto).
 * - Secure: só quando o acesso é HTTPS, senão o cookie não
 *   funcionaria no XAMPP local, que roda em HTTP.
 */

if (session_status() === PHP_SESSION_NONE) {

    // Detecta HTTPS para ligar o Secure apenas quando faz sentido
    $usando_https =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => 0,          // expira ao fechar o navegador
        'path'     => '/',
        'domain'   => '',
        'secure'   => $usando_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Aceita apenas os IDs de sessão gerados pelo próprio PHP,
    // impedindo que alguém fixe um ID pela URL.
    ini_set('session.use_strict_mode', 1);

    session_start();
}
?>
