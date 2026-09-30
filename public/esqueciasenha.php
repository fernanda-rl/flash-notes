<?php

/**
 * Página de Esqueci a Senha - Flashnotes
 * HTML e PHP unificados em um único arquivo
 * Fluxo com 3 etapas: Email -> Código -> Nova Senha
 *
 * Como a segurança deste fluxo funciona:
 *
 * 1. O código de 6 dígitos é gerado com random_int(), que é um
 *    gerador criptográfico (rand() é previsível e não serve).
 * 2. O código NÃO é guardado em texto puro: vai para o banco como
 *    hash (coluna token_recuperacao), junto com a data de expiração
 *    (coluna expiracao_token). Quem lê o banco não consegue usar o
 *    código para trocar a senha de ninguém.
 * 3. O código vale por poucos minutos e aceita um número limitado de
 *    tentativas erradas; passando disso, ele é invalidado.
 * 4. A etapa 3 não confia só numa flag de sessão: ela revalida o
 *    código contra o hash do banco antes de gravar a nova senha.
 * 5. A etapa 1 responde sempre a mesma coisa, exista ou não o e-mail,
 *    para não servir de lista de quem tem conta no sistema.
 * 6. Depois de usado, o código é apagado do banco.
 */

// Inicia a sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// Conexão, envio de e-mail e proteção CSRF
require_once __DIR__ . '/../app/config/conexao.php';
require_once __DIR__ . '/../app/helpers/enviar_email.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

// Regras vindas do config
$validade_minutos  = config('recuperacao_senha.validade_minutos', 15);
$maximo_tentativas = config('recuperacao_senha.maximo_tentativas', 5);

// Variáveis para armazenar mensagens e controlar o fluxo
$mensagem_erro = '';
$mensagem_sucesso = '';

$etapa_atual = $_SESSION['etapa_recuperacao'] ?? 1;
$email_recuperacao = $_SESSION['email_recuperacao'] ?? '';

/**
 * Limpa todo o estado do processo de recuperação da sessão.
 */
function limpar_recuperacao()
{
    unset($_SESSION['email_recuperacao']);
    unset($_SESSION['etapa_recuperacao']);
    unset($_SESSION['codigo_validado']);
    unset($_SESSION['tentativas_codigo']);
}

/**
 * Apaga o código de recuperação do banco, invalidando-o.
 */
function invalidar_codigo($conn, $email)
{
    $sql = "UPDATE usuarios
            SET token_recuperacao = NULL, expiracao_token = NULL
            WHERE email = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }
}

// ==========================
// RESETAR PROCESSO
// ==========================
if (isset($_GET['reset'])) {

    // Se havia um processo em andamento, o código também é anulado
    if (!empty($_SESSION['email_recuperacao'])) {
        invalidar_codigo($conn, $_SESSION['email_recuperacao']);
    }

    limpar_recuperacao();

    $_SESSION['etapa_recuperacao'] = 1;

    header("Location: esqueciasenha.php");
    exit();
}

// ==========================
// PROCESSAMENTO
// ==========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && !validar_csrf()) {

    $mensagem_erro = "Sua sessão expirou. Recomece o processo.";

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==========================
    // ETAPA 1 - EMAIL
    // ==========================
    if (isset($_POST['etapa1_email'])) {

        $email = filter_var($_POST['email_usuario'] ?? '', FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem_erro = "E-mail inválido.";

        } else {

            // Procura o usuário, mas o resultado NÃO muda a resposta
            // mostrada na tela: a mensagem final é sempre a mesma.
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $existe = $stmt->get_result()->num_rows > 0;

            $stmt->close();

            if ($existe) {

                // random_int é criptograficamente seguro
                $codigo = random_int(100000, 999999);

                // No banco vai apenas o hash do código
                $codigo_hash = password_hash($codigo, PASSWORD_DEFAULT);

                $expira_em = date(
                    'Y-m-d H:i:s',
                    time() + ($validade_minutos * 60)
                );

                $sql = "UPDATE usuarios
                        SET token_recuperacao = ?, expiracao_token = ?
                        WHERE email = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $codigo_hash, $expira_em, $email);
                $stmt->execute();
                $stmt->close();

                $corpo = "
                    <h2>Recuperação de senha</h2>
                    <p>Seu código de recuperação é:</p>
                    <h1>{$codigo}</h1>
                    <p>O código vale por {$validade_minutos} minutos.</p>
                    <p>Se você não solicitou isso, ignore este e-mail.</p>
                ";

                enviarEmail(
                    $email,
                    'Recuperação de senha - Flashnotes',
                    $corpo,
                    ['texto_alternativo' =>
                        "Seu código de recuperação é: {$codigo}\n" .
                        "Ele vale por {$validade_minutos} minutos."]
                );
            }

            // Mesma resposta nos dois casos: não revela se o e-mail
            // está cadastrado.
            $_SESSION['email_recuperacao'] = $email;
            $_SESSION['etapa_recuperacao'] = 2;
            $_SESSION['tentativas_codigo'] = 0;

            $etapa_atual = 2;
            $email_recuperacao = $email;

            $mensagem_sucesso = "Se o e-mail estiver cadastrado, enviaremos um código.";
        }
    }

    // ==========================
    // ETAPA 2 - CÓDIGO
    // ==========================
    elseif (isset($_POST['etapa2_codigo'])) {

        $codigo_digitado = trim($_POST['codigo_recuperacao'] ?? '');
        $email = $_SESSION['email_recuperacao'] ?? '';

        if (empty($email)) {

            $mensagem_erro = "Processo expirado. Comece novamente.";
            $etapa_atual = 1;
            limpar_recuperacao();

        } else {

            // Busca o hash e a validade gravados na etapa 1
            $sql = "SELECT token_recuperacao, expiracao_token
                    FROM usuarios WHERE email = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $dados = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            $tem_codigo = !empty($dados['token_recuperacao']);

            $expirou = $tem_codigo &&
                (strtotime($dados['expiracao_token']) < time());

            if (!$tem_codigo || $expirou) {

                // Mesma mensagem para código inexistente e expirado
                $mensagem_erro = "Código inválido ou expirado. Solicite um novo.";
                $etapa_atual = 2;

                if ($expirou) {
                    invalidar_codigo($conn, $email);
                }

            } elseif (password_verify($codigo_digitado, $dados['token_recuperacao'])) {

                // Código correto: libera a etapa 3
                $_SESSION['etapa_recuperacao'] = 3;
                $_SESSION['codigo_validado'] = true;
                $_SESSION['tentativas_codigo'] = 0;

                $etapa_atual = 3;
                $mensagem_sucesso = "Código validado!";

            } else {

                // Código errado: conta a tentativa
                $_SESSION['tentativas_codigo'] =
                    ($_SESSION['tentativas_codigo'] ?? 0) + 1;

                $restantes = $maximo_tentativas - $_SESSION['tentativas_codigo'];

                if ($restantes <= 0) {

                    // Estourou o limite: o código morre aqui
                    invalidar_codigo($conn, $email);
                    limpar_recuperacao();

                    $_SESSION['etapa_recuperacao'] = 1;
                    $etapa_atual = 1;

                    $mensagem_erro = "Muitas tentativas incorretas. Solicite um novo código.";

                } else {

                    $etapa_atual = 2;
                    $mensagem_erro = "Código inválido. Tentativas restantes: {$restantes}.";
                }
            }
        }
    }

    // ==========================
    // ETAPA 3 - NOVA SENHA
    // ==========================
    elseif (isset($_POST['etapa3_senha'])) {

        $nova = $_POST['nova_senha_usuario'] ?? '';
        $confirmar = $_POST['confirmar_nova_senha_usuario'] ?? '';
        $email = $_SESSION['email_recuperacao'] ?? '';

        // A flag de sessão sozinha não basta: o código precisa
        // continuar válido no banco neste exato momento.
        $liberado = false;

        if (!empty($email) && !empty($_SESSION['codigo_validado'])) {

            $sql = "SELECT token_recuperacao, expiracao_token
                    FROM usuarios WHERE email = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $dados = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            $liberado = !empty($dados['token_recuperacao']) &&
                        strtotime($dados['expiracao_token']) >= time();
        }

        if (!$liberado) {

            $mensagem_erro = "Sessão de recuperação expirada. Comece novamente.";

            limpar_recuperacao();
            $_SESSION['etapa_recuperacao'] = 1;
            $etapa_atual = 1;

        } elseif ($nova !== $confirmar) {

            $mensagem_erro = "As senhas não coincidem.";
            $etapa_atual = 3;

        } elseif (strlen($nova) < 6) {

            $mensagem_erro = "A senha deve ter no mínimo 6 caracteres.";
            $etapa_atual = 3;

        } else {

            $hash = password_hash($nova, PASSWORD_DEFAULT);

            // Grava a nova senha E invalida o código na mesma query
            $sql = "UPDATE usuarios
                    SET senha_hash = ?,
                        token_recuperacao = NULL,
                        expiracao_token = NULL
                    WHERE email = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $hash, $email);

            if ($stmt->execute()) {

                $mensagem_sucesso = "Senha redefinida com sucesso! Redirecionando...";

                limpar_recuperacao();

                // Troca o ID da sessão para não reaproveitar a antiga
                session_regenerate_id(true);

                $etapa_atual = 4;

            } else {

                error_log('Erro ao redefinir senha: ' . $conn->error);
                $mensagem_erro = "Não foi possível redefinir a senha. Tente novamente.";
                $etapa_atual = 3;
            }

            $stmt->close();
        }
    }
}

// Garante etapa inicial
if (!isset($_SESSION['etapa_recuperacao'])) {
    $_SESSION['etapa_recuperacao'] = 1;
    $etapa_atual = 1;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Esqueci a Senha</title>
    <link rel="stylesheet" href="css/esqueciasenha.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Pacifico&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-principal">
        <!-- Lado Esquerdo: Ilustração e Menu -->
        <div class="secao-esquerda">
            <nav class="menu-superior">
                <ul>
                    <li><a href="index.html">Início</a></li>
                    <li><a href="login.php" class="ativo">Login / Cadastre-se</a></li>
                    <li><a href="faleconosco.php">Fale conosco</a></li>
                </ul>
            </nav>

            <div class="conteudo-ilustracao">
                <img src="img/moca_pc.png" alt="Ilustração de uma pessoa trabalhando no computador" class="imagem-ilustracao">
            </div>

            <footer class="rodape-esquerdo">
                <span>Siga-nos!</span>
                <span>@flashnotes</span>
                <a href="mailto:flashnotess@gmail.com">flashnotess@gmail.com</a>
            </footer>
        </div>

        <!-- Lado Direito: Formulário de Recuperação -->
        <div class="secao-direita">
            <div class="caixa-recuperacao">
                <div class="logo">
                    <img src="img/logo_completa_azul.png" alt="Logo" class="imagem-ilustracao">
                </div>

                <h2 class="titulo-recuperacao">Esqueci a senha</h2>

                <!-- Indicador de Etapas -->
                <div class="indicador-etapas">
                    <div class="etapa <?php echo ($etapa_atual >= 1) ? 'ativa' : ''; ?>">1</div>
                    <div class="linha-etapas <?php echo ($etapa_atual >= 2) ? 'ativa' : ''; ?>"></div>
                    <div class="etapa <?php echo ($etapa_atual >= 2) ? 'ativa' : ''; ?>">2</div>
                    <div class="linha-etapas <?php echo ($etapa_atual >= 3) ? 'ativa' : ''; ?>"></div>
                    <div class="etapa <?php echo ($etapa_atual >= 3) ? 'ativa' : ''; ?>">3</div>
                </div>

                <!-- Exibe mensagens de erro ou sucesso -->
                <?php if (!empty($mensagem_erro)): ?>
                    <div class="mensagem mensagem-erro">
                        <?php echo htmlspecialchars($mensagem_erro); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensagem_sucesso)): ?>
                    <div class="mensagem mensagem-sucesso">
                        <?php echo htmlspecialchars($mensagem_sucesso); ?>
                    </div>
                <?php endif; ?>

                <!-- ETAPA 1: Email -->
                <?php if ($etapa_atual == 1): ?>
                    <form action="esqueciasenha.php" method="POST" class="formulario-recuperacao">
                        <?php echo campo_csrf(); ?>

                        <div class="campo-entrada">
                            <label for="email">EMAIL</label>
                            <input type="email" id="email" name="email_usuario" required>
                        </div>

                        <button type="submit" name="etapa1_email" class="botao-acao">Enviar Código</button>
                    </form>
                <?php endif; ?>

                <!-- ETAPA 2: Código de Verificação -->
                <?php if ($etapa_atual == 2): ?>
                    <form action="esqueciasenha.php" method="POST" class="formulario-recuperacao">
                        <?php echo campo_csrf(); ?>

                        <p class="texto-etapa">Insira o código de verificação enviado para:</p>
                        <p class="email-confirmacao"><?php echo htmlspecialchars($email_recuperacao); ?></p>

                        <div class="campo-entrada">
                            <label for="codigo">CÓDIGO</label>
                            <input type="text" id="codigo" name="codigo_recuperacao" placeholder="Insira o código de 6 dígitos" inputmode="numeric" maxlength="6" required>
                        </div>

                        <button type="submit" name="etapa2_codigo" class="botao-acao">Validar</button>
                    </form>

                    <div class="links-auxiliares">
                        <p><a href="esqueciasenha.php?reset=1" class="link-voltar">Voltar</a></p>
                    </div>
                <?php endif; ?>

                <!-- ETAPA 3: Nova Senha -->
                <?php if ($etapa_atual == 3): ?>
                    <form action="esqueciasenha.php" method="POST" class="formulario-recuperacao">
                        <?php echo campo_csrf(); ?>

                        <div class="campo-entrada">
                            <label for="nova-senha">NOVA SENHA</label>
                            <input type="password" id="nova-senha" name="nova_senha_usuario" minlength="6" required>
                        </div>

                        <div class="campo-entrada">
                            <label for="confirmar-nova-senha">CONFIRMAR SENHA</label>
                            <input type="password" id="confirmar-nova-senha" name="confirmar_nova_senha_usuario" minlength="6" required>
                        </div>

                        <button type="submit" name="etapa3_senha" class="botao-acao">Salvar</button>
                    </form>
                <?php endif; ?>

                <!-- ETAPA 4: Conclusão -->
                <?php if ($etapa_atual == 4): ?>
                    <div class="conclusao-recuperacao">
                        <p class="texto-conclusao">✓ Sua senha foi redefinida com sucesso!</p>
                        <p class="texto-secundario">Você será redirecionado para o login em alguns segundos...</p>
                        <a href="login.php" class="botao-acao">Ir para Login</a>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000);
                    </script>
                <?php endif; ?>

                <?php if ($etapa_atual < 4): ?>
                    <div class="links-auxiliares">
                        <p>Lembrou sua senha? <a href="login.php">Faça login aqui</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
