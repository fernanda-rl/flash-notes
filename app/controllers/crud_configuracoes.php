<?php
require __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/enviar_email.php';

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {

    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$mensagem = '';
$erro = '';


// ======================================
// BUSCA USUÁRIO
// ======================================

$sql_usuario = "SELECT * FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();
$usuario = $resultado_usuario->fetch_assoc();

// Define valores padrão caso venham nulos do banco
$usuario['notificacao_email'] = $usuario['notificacao_email'] ?? 0;
$usuario['notificacao_navegador'] = $usuario['notificacao_navegador'] ?? 0;
$usuario['resumo_semanal'] = $usuario['resumo_semanal'] ?? 0;
$usuario['tema'] = $usuario['tema'] ?? 'sistema';
$usuario['nome'] = $usuario['nome'] ?? 'Usuário';


// ======================================
// ALTERAR NOME
// ======================================

if (isset($_POST['alterar_nome'])) {

    $novo_nome = trim($_POST['novo_nome'] ?? '');

    // Limita ao tamanho da coluna `nome` (varchar 100)
    $novo_nome = mb_substr($novo_nome, 0, 100);

    if (empty($novo_nome)) {

        $erro = "Informe o seu nome.";

    } elseif (mb_strlen($novo_nome) < 2) {

        $erro = "O nome deve ter no mínimo 2 caracteres.";

    } else {

        $sql = "UPDATE usuarios SET nome = ? WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("si", $novo_nome, $usuario_id);

        if ($stmt->execute()) {

            $usuario['nome'] = $novo_nome;

            // A sidebar lê o nome da sessão, então ela precisa ser
            // atualizada para a saudação mudar já no próximo carregamento.
            $_SESSION['nome_usuario'] = $novo_nome;

            $mensagem = "Nome alterado com sucesso!";

        } else {

            $erro = "Erro ao alterar o nome.";
        }

        $stmt->close();
    }
}


// ======================================
// ALTERAR TEMA (APARÊNCIA)
// ======================================

if (isset($_POST['salvar_tema'])) {

    $tema_escolhido = $_POST['tema'] ?? '';

    // Só os três valores do enum são aceitos
    if (!in_array($tema_escolhido, ['sistema', 'claro', 'escuro'], true)) {

        $erro = "Opção de aparência inválida.";

    } else {

        $sql = "UPDATE usuarios SET tema = ? WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("si", $tema_escolhido, $usuario_id);

        if ($stmt->execute()) {

            $usuario['tema'] = $tema_escolhido;

            // A sessão guarda o tema para que as outras páginas não
            // precisem consultar o banco a cada carregamento.
            $_SESSION['tema'] = $tema_escolhido;

            $mensagem = "Aparência atualizada com sucesso!";

        } else {

            $erro = "Erro ao salvar a aparência.";
        }

        $stmt->close();
    }
}


// ======================================
// ALTERAR EMAIL
// ======================================

if (isset($_POST['alterar_email'])) {

    $novo_email = trim($_POST['novo_email']);
    $confirmar_email = trim($_POST['confirmar_novo_email']);

    if (empty($novo_email) || empty($confirmar_email)) {

        $erro = "Preencha todos os campos.";

    } elseif ($novo_email !== $confirmar_email) {

        $erro = "Os emails não coincidem.";

    } else {

        $sql = "UPDATE usuarios SET email = ? WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("si", $novo_email, $usuario_id);

        if ($stmt->execute()) {

            $mensagem = "Email alterado com sucesso!";

            $usuario['email'] = $novo_email;

        } else {

            $erro = "Erro ao alterar email.";

        }
    }
}


// ======================================
// ALTERAR SENHA
// ======================================

if (isset($_POST['alterar_senha'])) {

    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_nova_senha'];

    if (
        empty($senha_atual) ||
        empty($nova_senha) ||
        empty($confirmar_senha)
    ) {

        $erro = "Preencha todos os campos.";

    } elseif (!password_verify($senha_atual, $usuario['senha_hash'])) {

        $erro = "Senha atual incorreta.";

    } elseif ($nova_senha !== $confirmar_senha) {

        $erro = "As senhas não coincidem.";

    } else {

        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios SET senha_hash = ? WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("si", $nova_senha_hash, $usuario_id);

        if ($stmt->execute()) {

            $mensagem = "Senha alterada com sucesso!";

        } else {

            $erro = "Erro ao alterar senha.";

        }
    }
}


// ======================================
// DELETAR CONTA
// ======================================

if (isset($_POST['deletar_conta'])) {

    $email = trim($_POST['email_deletar']);
    $senha = $_POST['senha_deletar'];

    if (
        $email !== $usuario['email'] ||
        !password_verify($senha, $usuario['senha_hash'])
    ) {

        $erro = "Email ou senha incorretos.";

    } else {

        $sql = "DELETE FROM usuarios WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("i", $usuario_id);

        if ($stmt->execute()) {

            session_destroy();

            header("Location: login.php");
            exit();

        } else {

            $erro = "Erro ao deletar conta.";

        }
    }
}

// ======================================
// ALTERAR NOTIFICAÇÕES
// ======================================

if (isset($_POST['salvar_notificacoes'])) {

    $notificacao_email = isset($_POST['notificacao_email']) ? 1 : 0;

    $notificacao_navegador = isset($_POST['notificacao_navegador']) ? 1 : 0;

    $resumo_semanal = isset($_POST['resumo_semanal']) ? 1 : 0;

    $sql = "UPDATE usuarios 
            SET notificacao_email = ?,
                notificacao_navegador = ?,
                resumo_semanal = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiii",
        $notificacao_email,
        $notificacao_navegador,
        $resumo_semanal,
        $usuario_id
    );

if ($stmt->execute()) {

    $mensagem = "Preferências de notificação atualizadas com sucesso!";

    // Atualiza os dados do usuário
    $usuario['notificacao_email'] = $notificacao_email;

    $usuario['notificacao_navegador'] = $notificacao_navegador;

    $usuario['resumo_semanal'] = $resumo_semanal;

    // ======================================
    // ENVIA EMAIL DE CONFIRMAÇÃO
    // ======================================

    if ($notificacao_email == 1) {

        $assunto = "Notificações ativadas - Flashnotes";
    $lista_notificacoes = "";

    // ======================================
    // MONTA LISTA DINAMICAMENTE
    // ======================================
    if ($notificacao_email == 1) {
        $lista_notificacoes .= "<li>Notificações por email</li>";
    }

    // Mesmo texto usado na tela de Configurações: os avisos aparecem
    // no sino dentro do Flashnotes, não como notificação do sistema
    // operacional.
    if ($notificacao_navegador == 1) {
        $lista_notificacoes .= "<li>Notificações no sistema</li>";
    }

    if ($resumo_semanal == 1) {
        $lista_notificacoes .= "<li>Resumo semanal</li>";
    }

    // ======================================
    // MONTA EMAIL
    // ======================================

    $mensagemHTML = "
        <h2>Preferências atualizadas!</h2>
        <p>Olá {$usuario['nome']},</p>
        <p>Suas preferências de notificação foram atualizadas com sucesso.</p>
        <p>Você ativou:</p>
        <ul>
            {$lista_notificacoes}
        </ul>
    ";

        enviarEmail(
            $usuario['email'],
            $assunto,
            $mensagemHTML
        );
    }

    } else {
        $erro = "Erro ao salvar notificações.";
    }


    $stmt->close();
}