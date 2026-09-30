<?php

/**
 * Página Fale Conosco - Flashnotes
 * HTML e PHP unificados em um único arquivo
 * Formulário para envio de mensagens usando PHPMailer
 */

// Inicia a sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// Envio de e-mail centralizado (credenciais em app/config/config.php)
require_once __DIR__ . '/../app/helpers/enviar_email.php';

// Proteção contra CSRF
require_once __DIR__ . '/../app/helpers/csrf.php';

// Variáveis para mensagens
$mensagem_erro = '';
$mensagem_sucesso = '';

// ==========================
// PROCESSAMENTO DO FORMULÁRIO
// ==========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && !validar_csrf()) {

    $mensagem_erro = "Sua sessão expirou. Envie a mensagem novamente.";

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Captura os dados
    $nome_usuario = $_POST['nome_usuario'] ?? '';
    $email_usuario = $_POST['email_usuario'] ?? '';
    $assunto_mensagem = $_POST['assunto_mensagem'] ?? '';
    $mensagem_texto = $_POST['mensagem_texto'] ?? '';

    // Sanitização
    $nome_usuario = htmlspecialchars(trim($nome_usuario));
    $email_usuario = filter_var($email_usuario, FILTER_SANITIZE_EMAIL);
    $assunto_mensagem = htmlspecialchars(trim($assunto_mensagem));
    $mensagem_texto = htmlspecialchars(trim($mensagem_texto));

    // ==========================
    // VALIDAÇÕES
    // ==========================
    if (
        empty($nome_usuario) ||
        empty($email_usuario) ||
        empty($assunto_mensagem) ||
        empty($mensagem_texto)
    ) {

        $mensagem_erro = "Por favor, preencha todos os campos.";

    } elseif (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {

        $mensagem_erro = "Por favor, insira um e-mail válido.";

    } elseif (strlen($mensagem_texto) < 10) {

        $mensagem_erro = "A mensagem deve ter no mínimo 10 caracteres.";

    } else {

        // ==========================
        // ENVIO DO E-MAIL
        // ==========================
        // Usa o helper app/helpers/enviar_email.php, que concentra a
        // configuração de SMTP. O destinatário é a caixa de contato
        // definida no config, e o Reply-To é quem escreveu, para que
        // a resposta vá direto para a pessoa.

        $corpo_html = "
            <h2>Nova mensagem recebida</h2>

            <p><strong>Nome:</strong> {$nome_usuario}</p>

            <p><strong>E-mail:</strong> {$email_usuario}</p>

            <p><strong>Assunto:</strong> {$assunto_mensagem}</p>

            <hr>

            <p><strong>Mensagem:</strong></p>

            <p>" . nl2br($mensagem_texto) . "</p>

            <hr>

            <p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>
        ";

        $corpo_texto =
            "Nova mensagem recebida\n\n" .
            "Nome: {$nome_usuario}\n" .
            "E-mail: {$email_usuario}\n" .
            "Assunto: {$assunto_mensagem}\n\n" .
            "Mensagem:\n{$mensagem_texto}\n\n" .
            "Data: " . date('d/m/Y H:i:s');

        $enviado = enviarEmail(
            config('email.contato'),
            'Fale Conosco - ' . $assunto_mensagem,
            $corpo_html,
            [
                'texto_alternativo' => $corpo_texto,
                'responder_para'    => $email_usuario,
                'responder_nome'    => $nome_usuario,
            ]
        );

        if ($enviado) {

            $mensagem_sucesso = "Sua mensagem foi enviada com sucesso!";

            // Limpa formulário
            $_POST = array();

        } else {

            // O detalhe do erro fica no log do servidor, não na tela
            $mensagem_erro = "Não foi possível enviar sua mensagem agora. Tente novamente mais tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Fale Conosco</title>

    <link rel="stylesheet" href="css/faleconosco.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Pacifico&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-principal">

    <!-- LADO ESQUERDO -->
    <div class="secao-esquerda">

        <nav class="menu-superior">
            <ul>
                <li><a href="index.html">Início</a></li>
                <li><a href="login.php">Login / Cadastre-se</a></li>
                <li><a href="faleconosco.php" class="ativo">Fale conosco</a></li>
            </ul>
        </nav>

        <div class="conteudo-ilustracao">
            <img src="img/moca_pc.png"
                 alt="Pessoa usando computador"
                 class="imagem-ilustracao">
        </div>

        <footer class="rodape-esquerdo">
            <span>Siga-nos!</span>

            <a href="#">@flashnotes</a>

            <a href="mailto:flashnotess@gmail.com">
                flashnotess@gmail.com
            </a>
        </footer>

    </div>

    <!-- LADO DIREITO -->
    <div class="secao-direita">

        <div class="caixa-contato">

            <div class="logo">
                <img src="img/logo_completa_azul.png"
                     alt="Logo Flashnotes"
                     class="imagem-ilustracao">
            </div>

            <h2 class="titulo-contato">Fale Conosco</h2>

            <p class="subtitulo-contato">
                Tem alguma dúvida ou problema?
                Nos envie uma mensagem!
            </p>

            <!-- MENSAGEM DE ERRO -->
            <?php if (!empty($mensagem_erro)): ?>

                <div class="mensagem mensagem-erro">
                    <?php echo htmlspecialchars($mensagem_erro); ?>
                </div>

            <?php endif; ?>

            <!-- MENSAGEM DE SUCESSO -->
            <?php if (!empty($mensagem_sucesso)): ?>

                <div class="mensagem mensagem-sucesso">
                    <?php echo htmlspecialchars($mensagem_sucesso); ?>
                </div>

            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <form action="faleconosco.php"
                  method="POST"
                  class="formulario-contato">

                <?php echo campo_csrf(); ?>

                <div class="campo-entrada">

                    <label for="nome">NOME</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome_usuario"
                        required

                        value="<?php
                            echo isset($_POST['nome_usuario'])
                            ? htmlspecialchars($_POST['nome_usuario'])
                            : '';
                        ?>"
                    >
                </div>

                <div class="campo-entrada">

                    <label for="email">E-MAIL</label>

                    <input
                        type="email"
                        id="email"
                        name="email_usuario"
                        required

                        value="<?php
                            echo isset($_POST['email_usuario'])
                            ? htmlspecialchars($_POST['email_usuario'])
                            : '';
                        ?>"
                    >
                </div>

                <div class="campo-entrada">

                    <label for="assunto">ASSUNTO</label>

                    <input
                        type="text"
                        id="assunto"
                        name="assunto_mensagem"
                        required

                        value="<?php
                            echo isset($_POST['assunto_mensagem'])
                            ? htmlspecialchars($_POST['assunto_mensagem'])
                            : '';
                        ?>"
                    >
                </div>

                <div class="campo-entrada campo-textarea">

                    <label for="mensagem">MENSAGEM</label>

                    <textarea
                        id="mensagem"
                        name="mensagem_texto"
                        rows="6"
                        required><?php
                            echo isset($_POST['mensagem_texto'])
                            ? htmlspecialchars($_POST['mensagem_texto'])
                            : '';
                        ?></textarea>

                </div>

                <button type="submit" class="botao-enviar">
                    Enviar Mensagem
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>