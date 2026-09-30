<?php
/**
 * Página de Login - Flashnotes
 *
 * Cuidados de segurança neste arquivo:
 * - A mensagem de erro é sempre a mesma ("E-mail ou senha incorretos"),
 *   para que a tela não sirva de consulta de quais e-mails têm conta.
 * - Há um limite de tentativas por sessão, com bloqueio temporário.
 * - Depois do login bem-sucedido o ID da sessão é trocado
 *   (session_regenerate_id), o que impede fixação de sessão.
 */

// Inicia a sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

require_once __DIR__ . '/../app/config/conexao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

$mensagem_erro = '';

// Regras de bloqueio vindas do config
$maximo_tentativas = config('login.maximo_tentativas', 5);
$bloqueio_minutos  = config('login.bloqueio_minutos', 15);

// ==========================
// BLOQUEIO POR TENTATIVAS
// ==========================
// O contador vive na sessão do visitante. Não é uma defesa contra um
// atacante determinado (basta limpar os cookies), mas já segura o
// chute repetido de senha no mesmo navegador. Um controle mais forte
// exigiria registrar as tentativas por IP no banco.

$bloqueado_ate = $_SESSION['login_bloqueado_ate'] ?? 0;

$esta_bloqueado = ($bloqueado_ate > time());

if ($esta_bloqueado) {

    $minutos_restantes = (int) ceil(($bloqueado_ate - time()) / 60);

    $mensagem_erro =
        "Muitas tentativas de login. Aguarde {$minutos_restantes} " .
        ($minutos_restantes === 1 ? "minuto" : "minutos") . " e tente novamente.";
}

if (!$esta_bloqueado && $_SERVER["REQUEST_METHOD"] == "POST" && !validar_csrf()) {

    $mensagem_erro = "Sua sessão expirou. Tente novamente.";

} elseif (!$esta_bloqueado && $_SERVER["REQUEST_METHOD"] == "POST") {

    $email_digitado = $_POST['email_usuario'] ?? '';
    $senha_digitada = $_POST['senha_usuario'] ?? '';

    $email_digitado = filter_var($email_digitado, FILTER_SANITIZE_EMAIL);

    if (empty($email_digitado) || empty($senha_digitada)) {

        $mensagem_erro = "Por favor, preencha todos os campos.";

    } else {

        // Busca usuário no banco
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email_digitado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuario = $resultado->fetch_assoc();

        $stmt->close();

        // Uma única condição para "e-mail não existe" e "senha errada":
        // as duas situações produzem exatamente a mesma resposta.
        if ($usuario && password_verify($senha_digitada, $usuario['senha_hash'])) {

            // Login correto: zera o contador de tentativas
            unset($_SESSION['login_tentativas']);
            unset($_SESSION['login_bloqueado_ate']);

            // Troca o ID da sessão antes de gravar os dados do usuário.
            // Sem isso, um ID conhecido de antemão continuaria valendo
            // depois do login (fixação de sessão).
            session_regenerate_id(true);

            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['email_usuario'] = $usuario['email'];

            // A chave é 'nome_usuario' (e não 'usuario_nome') para casar
            // com 'email_usuario' e com o que a sidebar lê. Antes os dois
            // lados usavam nomes trocados e a saudação nunca aparecia.
            $_SESSION['nome_usuario'] = $usuario['nome'];

            // Preferência de aparência: guardada na sessão para que as
            // páginas não precisem consultar o banco a cada carregamento.
            $_SESSION['tema'] = $usuario['tema'] ?? 'sistema';

            header("Location: dashboard.php");
            exit();

        } else {

            $_SESSION['login_tentativas'] =
                ($_SESSION['login_tentativas'] ?? 0) + 1;

            if ($_SESSION['login_tentativas'] >= $maximo_tentativas) {

                $_SESSION['login_bloqueado_ate'] =
                    time() + ($bloqueio_minutos * 60);

                unset($_SESSION['login_tentativas']);

                $mensagem_erro =
                    "Muitas tentativas de login. Aguarde {$bloqueio_minutos} minutos e tente novamente.";

            } else {

                // Mensagem genérica: não diz se o problema foi o e-mail
                // ou a senha.
                $mensagem_erro = "E-mail ou senha incorretos.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Login</title>
    <link rel="stylesheet" href="css/login.css">
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

        <!-- Lado Direito: Formulário de Login -->
        <div class="secao-direita">
            <div class="caixa-login">
                <div class="logo">
                    <img src="img/logo_completa_azul.png" alt="Logo" class="imagem-ilustracao">
                </div>
                
                <h2 class="titulo-login">Login</h2>

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

                <form action="login.php" method="POST" class="formulario-login">
                    <?php echo campo_csrf(); ?>

                    <div class="campo-entrada">
                        <label for="email">EMAIL</label>
                        <input type="email" id="email" name="email_usuario" required>
                    </div>

                    <div class="campo-entrada">
                        <label for="senha">SENHA</label>
                        <input type="password" id="senha" name="senha_usuario" required>
                    </div>

                    <button type="submit" class="botao-entrar">Entrar</button>
                </form>

                <div class="links-auxiliares">
                    <p>Você não possui uma conta? Cadastre-se <a href="cadastro.php">aqui</a></p>
                    <p>Você esqueceu sua senha? Clique <a href="esqueciasenha.php">aqui</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>