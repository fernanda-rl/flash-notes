<?php

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {

    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../app/controllers/crud_configuracoes.php';

$secao_ativa = $_GET['secao'] ?? 'email';


require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Configurações</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/configuracoes.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/tema.css">
</head>
<body>
    <div class="container-dashboard">
        <!-- Sidebar e Barra Superior -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <main class="conteudo-principal"> 

            <div class="cabecalho-configuracoes">
                <div class="titulo-configuracoes">
                    <img src="icons/engrenagem.svg" width="24" height="24" alt="Engrenagem">
                    <h1>Configurações</h1>
                </div>
            </div>
            
            <!-- Container Principal -->
            <div class="container-configuracoes">
                <!-- Menu Lateral -->
                <aside class="menu-configuracoes">
                    
                    <a href="?secao=nome" class="opcao-menu <?php echo ($secao_ativa === 'nome') ? 'ativa' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Alterar Nome</span>
                    </a>

                    <a href="?secao=email" class="opcao-menu <?php echo ($secao_ativa === 'email') ? 'ativa' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                        </svg>
                        <span>Alterar Email</span>
                    </a>
                    
                    <a href="?secao=senha" class="opcao-menu <?php echo ($secao_ativa === 'senha') ? 'ativa' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Alterar Senha</span>
                    </a>
                    
                    <a href="?secao=notificacoes" class="opcao-menu <?php echo ($secao_ativa === 'notificacoes') ? 'ativa' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span>Notificações</span>
                    </a>

                    <a href="?secao=aparencia" class="opcao-menu <?php echo ($secao_ativa === 'aparencia') ? 'ativa' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <span>Aparência</span>
                    </a>
                    
                    <a href="?secao=deletar" class="opcao-menu <?php echo ($secao_ativa === 'deletar') ? 'ativa' : ''; ?> opcao-deletar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>Deletar Conta</span>
                    </a>
                </aside>
                
                <!-- Conteudo das Seções -->
                <section class="conteudo-configuracoes">
                    <!-- SEÇÃO: ALTERAR NOME -->
                    <?php if ($secao_ativa === 'nome'): ?>
                        <div class="secao-ativa">
                            <h2>Alterar Nome</h2>
                            <form class="formulario-secao" method="POST">
                <?php echo campo_csrf(); ?>
                                <div class="grupo-input">
                                    <label for="novo-nome">Seu nome</label>
                                    <input
                                        type="text"
                                        id="novo-nome"
                                        name="novo_nome"
                                        placeholder="Como quer ser chamado?"
                                        maxlength="100"
                                        value="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                        required
                                    >
                                    <small class="ajuda-input">
                                        É esse nome que aparece na saudação do topo da página.
                                    </small>
                                </div>

                                <button
                                    type="submit"
                                    name="alterar_nome"
                                    class="botao-salvar"
                                >
                                    Salvar
                                </button>
                            </form>

                            <?php if (!empty($mensagem)): ?>
                                <div class="mensagem-sucesso">
                                    <?php echo $mensagem; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($erro)): ?>
                                <div class="mensagem-erro">
                                    <?php echo $erro; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- SEÇÃO: ALTERAR EMAIL -->
                    <?php if ($secao_ativa === 'email'): ?>
                        <div class="secao-ativa">
                            <h2>Alterar Email</h2>
                                <form class="formulario-secao" method="POST">
                <?php echo campo_csrf(); ?>
                                    <div class="grupo-input">
                                        <label>Email Atual</label>
                                        <input
                                            type="email"
                                            value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                            disabled
                                        >
                                    </div>

                                    <div class="grupo-input">
                                        <label for="novo-email">Novo Email</label>
                                        <input
                                            type="email"
                                            id="novo-email"
                                            name="novo_email"
                                            placeholder="novo.email@exemplo.com"
                                            required
                                        >
                                    </div>

                                    <div class="grupo-input">
                                        <label for="confirmar-novo-email">Confirmar Novo Email</label>
                                        <input
                                            type="email"
                                            id="confirmar-novo-email"
                                            name="confirmar_novo_email"
                                            placeholder="novo.email@exemplo.com"
                                            required
                                        >
                                    </div>

                                    <button
                                        type="submit"
                                        name="alterar_email"
                                        class="botao-salvar"
                                    >
                                        Salvar
                                    </button>
                                </form>
                                <?php if (!empty($mensagem)): ?>
                                    <div class="mensagem-sucesso">
                                        <?php echo $mensagem; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($erro)): ?>
                                    <div class="mensagem-erro">
                                        <?php echo $erro; ?>
                                    </div>
                                <?php endif; ?>  
                        </div>
                    <?php endif; ?>
                    
                    <!-- SEÇÃO: ALTERAR SENHA -->
                    <?php if ($secao_ativa === 'senha'): ?>
                        <div class="secao-ativa">
                            <h2>Alterar Senha</h2>
                                <form class="formulario-secao" method="POST">
                <?php echo campo_csrf(); ?>
                                    <div class="grupo-input">
                                        <label for="senha-atual">Senha Atual</label>
                                        <input
                                            type="password"
                                            id="senha-atual"
                                            name="senha_atual"
                                            placeholder="••••••••"
                                            required
                                        >
                                    </div>

                                    <div class="grupo-input">
                                        <label for="nova-senha">Nova Senha</label>
                                        <input
                                            type="password"
                                            id="nova-senha"
                                            name="nova_senha"
                                            placeholder="••••••••"
                                            required
                                        >
                                    </div>

                                    <div class="grupo-input">
                                        <label for="confirmar-nova-senha">Confirmar Nova Senha</label>
                                        <input
                                            type="password"
                                            id="confirmar-nova-senha"
                                            name="confirmar_nova_senha"
                                            placeholder="••••••••"
                                            required
                                        >
                                    </div>

                                    <button
                                        type="submit"
                                        name="alterar_senha"
                                        class="botao-salvar"
                                    >
                                        Salvar
                                    </button>
                                </form>
                                <?php if (!empty($mensagem)): ?>
                                    <div class="mensagem-sucesso">
                                        <?php echo $mensagem; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($erro)): ?>
                                    <div class="mensagem-erro">
                                        <?php echo $erro; ?>
                                    </div>
                                <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- SEÇÃO: NOTIFICAÇÕES -->
                    <?php if ($secao_ativa === 'notificacoes'): ?>
                    <div class="secao-ativa">
                        <h2>Preferências de Notificação</h2>
                        <form class="formulario-secao" method="POST">
                <?php echo campo_csrf(); ?>
                            <div class="opcao-notificacao">
                                <div class="info-notificacao">
                                    <h3>Notificações de Email</h3>
                                    <p> Receba alertas sobre tarefas e eventos importantes por email.</p>
                                </div>

                                <label class="toggle-switch">
                                    <input
                                        type="checkbox"
                                        name="notificacao_email"
                                        <?= $usuario['notificacao_email'] ? 'checked' : '' ?>
                                    >
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- O nome anterior era "Notificações de Navegador", o que
                                 prometia um aviso do sistema operacional. O que o
                                 Flashnotes faz é mostrar os avisos no próprio site,
                                 pelo ícone de sino, então o rótulo foi ajustado para
                                 descrever o comportamento real. -->
                            <div class="opcao-notificacao">
                                <div class="info-notificacao">
                                    <h3>Notificações no sistema</h3>
                                    <p>Veja os avisos de tarefas e provas no ícone de sino, aqui dentro do Flashnotes.</p>
                                </div>

                                <label class="toggle-switch">
                                    <input
                                        type="checkbox"
                                        name="notificacao_navegador"
                                        <?= $usuario['notificacao_navegador'] ? 'checked' : '' ?>
                                    >
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="opcao-notificacao">
                                <div class="info-notificacao">
                                    <h3>Resumo Semanal</h3>
                                    <p>Receba um resumo semanal toda segunda-feira das suas tarefas.</p>
                                </div>

                                <label class="toggle-switch">
                                        <input
                                            type="checkbox"
                                            name="resumo_semanal"
                                            <?= $usuario['resumo_semanal'] ? 'checked' : '' ?>
                                        >
                                    <span class="slider"></span>

                                </label>
                            </div>

                            <button
                                type="submit"
                                name="salvar_notificacoes"
                                class="botao-salvar"
                            >
                                Salvar Preferências
                            </button>

                        </form>

                        <?php if (!empty($mensagem)): ?>
                            <div class="mensagem-sucesso">
                                <?php echo $mensagem; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($erro)): ?>
                            <div class="mensagem-erro">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- SEÇÃO: APARÊNCIA -->
                    <?php if ($secao_ativa === 'aparencia'): ?>
                    <div class="secao-ativa">
                        <h2>Aparência</h2>

                        <form class="formulario-secao" method="POST" id="formulario-tema">
                <?php echo campo_csrf(); ?>

                            <div class="opcoes-tema">

                                <label class="opcao-tema <?php echo ($usuario['tema'] === 'claro') ? 'ativa' : ''; ?>">
                                    <input type="radio" name="tema" value="claro"
                                           <?php echo ($usuario['tema'] === 'claro') ? 'checked' : ''; ?>>
                                    <div class="preview-tema claro"></div>
                                    <h3>Claro</h3>
                                    <p>Fundo claro o tempo todo.</p>
                                </label>

                                <label class="opcao-tema <?php echo ($usuario['tema'] === 'escuro') ? 'ativa' : ''; ?>">
                                    <input type="radio" name="tema" value="escuro"
                                           <?php echo ($usuario['tema'] === 'escuro') ? 'checked' : ''; ?>>
                                    <div class="preview-tema escuro"></div>
                                    <h3>Escuro</h3>
                                    <p>Fundo escuro o tempo todo.</p>
                                </label>

                                <label class="opcao-tema <?php echo ($usuario['tema'] === 'sistema') ? 'ativa' : ''; ?>">
                                    <input type="radio" name="tema" value="sistema"
                                           <?php echo ($usuario['tema'] === 'sistema') ? 'checked' : ''; ?>>
                                    <div class="preview-tema automatico"></div>
                                    <h3>Automático</h3>
                                    <p>Acompanha o navegador ou o sistema.</p>
                                </label>

                            </div>

                            <button type="submit" name="salvar_tema" class="botao-salvar">
                                Salvar Aparência
                            </button>
                        </form>

                        <?php if (!empty($mensagem)): ?>
                            <div class="mensagem-sucesso">
                                <?php echo $mensagem; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($erro)): ?>
                            <div class="mensagem-erro">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- SEÇÃO: DELETAR CONTA -->
                    <?php if ($secao_ativa === 'deletar'): ?>
                        <div class="secao-ativa">
                            <h2>Deletar Conta</h2>
                            <div class="aviso-deletar">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <div>
                                    <h3>Atenção!</h3>
                                    <p>Deletar sua conta é uma ação permanente e irreversível. Todos os seus dados, tarefas, disciplinas e eventos serão removidos permanentemente.</p>
                                </div>
                            </div>
                                <form class="formulario-secao formulario-deletar" method="POST">
                <?php echo campo_csrf(); ?>
                                    <div class="grupo-input">
                                        <label for="email-deletar">Confirme seu Email</label>
                                        <input
                                            type="email"
                                            id="email-deletar"
                                            name="email_deletar"
                                            placeholder="seu.email@exemplo.com"
                                            required
                                        >
                                    </div>

                                    <div class="grupo-input">
                                        <label for="senha-deletar">Confirme sua Senha</label>
                                        <input
                                            type="password"
                                            id="senha-deletar"
                                            name="senha_deletar"
                                            placeholder="••••••••"
                                            required
                                        >
                                    </div>

                                    <div class="confirmacao-deletar">
                                        <label class="checkbox-confirmacao">
                                            <input
                                                type="checkbox"
                                                id="confirmar-deletar"
                                                required
                                            >
                                            <span>
                                                Entendo que esta ação é irreversível e desejo deletar minha conta
                                            </span>
                                        </label>
                                    </div>

                                    <button
                                        type="submit"
                                        name="deletar_conta"
                                        class="botao-deletar-conta"
                                    >
                                        Deletar Conta Permanentemente
                                    </button>
                                </form>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    
    <script src="js/configuracoes.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>