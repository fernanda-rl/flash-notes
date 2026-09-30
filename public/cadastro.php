<?php
/**
 * Página de Cadastro - Flashnotes
 * HTML e PHP unificados em um único arquivo
 */

// Inicia a sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// CONEXÃO COM O BANCO (credenciais em app/config/config.php)
require_once __DIR__ . '/../app/config/conexao.php';

// Proteção contra CSRF
require_once __DIR__ . '/../app/helpers/csrf.php';

// Variáveis para armazenar mensagens de erro/sucesso
$mensagem_erro = '';
$mensagem_sucesso = '';

// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Confere o token de segurança antes de qualquer processamento
    if (!validar_csrf()) {

        $mensagem_erro = "Sua sessão expirou. Tente novamente.";

    } else {

    // Captura os dados do formulário
    $nome_novo = trim($_POST['nome_usuario'] ?? '');
    $email_novo = $_POST['email_usuario'] ?? '';
    $senha_nova = $_POST['senha_usuario'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha_usuario'] ?? '';

    // Limpeza básica de dados (Sanitização)
    $email_novo = filter_var($email_novo, FILTER_SANITIZE_EMAIL);

    // Limita ao tamanho da coluna `nome` (varchar 100)
    $nome_novo = mb_substr($nome_novo, 0, 100);

    // Validação básica
    if (empty($nome_novo) || empty($email_novo) || empty($senha_nova) || empty($confirmar_senha)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } elseif (mb_strlen($nome_novo) < 2) {
        $mensagem_erro = "O nome deve ter no mínimo 2 caracteres.";
    } elseif (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Por favor, insira um e-mail válido.";
    } elseif (strlen($senha_nova) < 6) {
        $mensagem_erro = "A senha deve ter no mínimo 6 caracteres.";
    } elseif ($senha_nova !== $confirmar_senha) {
        $mensagem_erro = "As senhas não coincidem. Tente novamente.";
    } else {
    // Verifica se o e-mail já existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email_novo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensagem_erro = "Este e-mail já está cadastrado.";
    } else {
        // Criptografa a senha
            $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);

            // Insere no banco
            $sql = "INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Nome informado pelo usuário no formulário
            $stmt->bind_param("sss", $nome_novo, $email_novo, $senha_hash);

            if ($stmt->execute()) {
                $mensagem_sucesso = "Cadastro realizado com sucesso! Agora faça login.";
            } else {
                // O erro do banco não é mostrado ao usuário: ele revelaria
                // nomes de tabelas e colunas.
                error_log('Erro ao cadastrar usuário: ' . $conn->error);
                $mensagem_erro = "Não foi possível concluir o cadastro. Tente novamente.";
            }
        }

        $stmt->close();
    }

    } // fecha o else da validação de CSRF
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Cadastro</title>
    <link rel="stylesheet" href="css/cadastro.css">
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
                    <li><a href="#" class="ativo">Login / Cadastre-se</a></li>
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

        <!-- Lado Direito: Formulário de Cadastro -->
        <div class="secao-direita">
            <div class="caixa-cadastro">
                <div class="logo">
                    <img src="img/logo_completa_azul.png" alt="Logo" class="imagem-ilustracao">
                </div>
                
                <h2 class="titulo-cadastro">Cadastro</h2>

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

                <form action="cadastro.php" method="POST" class="formulario-cadastro">
                    <?php echo campo_csrf(); ?>

                    <div class="campo-entrada">
                        <label for="nome">NOME</label>
                        <input
                            type="text"
                            id="nome"
                            name="nome_usuario"
                            placeholder="Como quer ser chamado?"
                            maxlength="100"
                            required
                            value="<?php echo isset($_POST['nome_usuario']) ? htmlspecialchars($_POST['nome_usuario']) : ''; ?>"
                        >
                    </div>

                    <div class="campo-entrada">
                        <label for="email">EMAIL</label>
                        <input
                            type="email"
                            id="email"
                            name="email_usuario"
                            required
                            value="<?php echo isset($_POST['email_usuario']) ? htmlspecialchars($_POST['email_usuario']) : ''; ?>"
                        >
                    </div>

                    <div class="campo-entrada">
                        <label for="senha">SENHA</label>
                        <input type="password" id="senha" name="senha_usuario" required>
                    </div>

                    <div class="campo-entrada">
                        <label for="confirmar-senha">CONFIRMAR SENHA</label>
                        <input type="password" id="confirmar-senha" name="confirmar_senha_usuario" required>
                    </div>

                    <button type="submit" class="botao-entrar">Cadastrar</button>
                </form>

                <div class="links-auxiliares">
                    <p>Você já possui uma conta? Entre <a href="login.php">aqui</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>