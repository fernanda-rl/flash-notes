<?php
/**
 * Proteção CSRF - Flashnotes
 * =====================================================
 * CSRF (Cross-Site Request Forgery) é quando outro site faz o
 * navegador da vítima enviar um formulário para o nosso sistema
 * aproveitando a sessão já aberta. Para impedir isso, todo
 * formulário POST carrega um token secreto que só existe na
 * sessão do usuário, e o servidor confere esse token antes de
 * executar a ação.
 *
 * Uso no formulário:
 *   <?php echo campo_csrf(); ?>
 *
 * Uso na action que recebe o POST:
 *   require_once __DIR__ . '/../../app/helpers/csrf.php';
 *   exigir_csrf();            // encerra a requisição se inválido
 *
 * Ou, quando quiser tratar o erro manualmente:
 *   if (!validar_csrf()) { ... }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devolve o token da sessão, criando-o na primeira chamada.
 */
function token_csrf()
{
    if (empty($_SESSION['token_csrf'])) {
        // 32 bytes aleatórios em hexadecimal = 64 caracteres
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

/**
 * Devolve o campo oculto pronto para colar dentro do <form>.
 */
function campo_csrf()
{
    return '<input type="hidden" name="token_csrf" value="'
        . htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Confere o token recebido no POST contra o da sessão.
 *
 * @return bool
 */
function validar_csrf()
{
    $recebido = $_POST['token_csrf'] ?? '';

    if (empty($_SESSION['token_csrf']) || empty($recebido)) {
        return false;
    }

    // hash_equals compara em tempo constante, evitando que o
    // tempo de resposta revele quantos caracteres bateram.
    return hash_equals($_SESSION['token_csrf'], $recebido);
}

/**
 * Valida o token e interrompe a requisição se ele não conferir.
 * Use nas actions, logo após incluir este arquivo.
 *
 * @param string|null $redirecionar_para Se informado, redireciona
 *                                       com mensagem em vez de
 *                                       mostrar a página de erro.
 */
function exigir_csrf($redirecionar_para = null)
{
    // Só requisições POST carregam token
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (validar_csrf()) {
        return;
    }

    if ($redirecionar_para !== null) {

        $_SESSION['mensagem'] = "Sua sessão expirou. Tente novamente.";
        $_SESSION['tipo_mensagem'] = "erro";

        header("Location: " . $redirecionar_para);
        exit();
    }

    http_response_code(403);
    die('Requisição inválida (token de segurança ausente ou expirado). Volte e tente novamente.');
}
?>
