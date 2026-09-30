<?php
    /**
     * Sidebar Reutilizável - Flashnotes
     * Este arquivo contém a navegação lateral e o perfil do usuário
     * Deve ser incluído em todas as páginas do dashboard
     */

    // Inicia a sessão se não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Obtém informações do usuário da sessão
    $email_usuario = $_SESSION['email_usuario'] ?? 'usuario@exemplo.com';

    require_once __DIR__ . '/../app/config/conexao.php';

    $usuario_id = $_SESSION['usuario_id'] ?? 0;

    // Nome de exibição. Sessões abertas antes desta funcionalidade não
    // têm a chave, então buscamos no banco uma vez e guardamos.
    if (!isset($_SESSION['nome_usuario']) && $usuario_id > 0) {

        $stmt_nome = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");

        if ($stmt_nome) {
            $stmt_nome->bind_param("i", $usuario_id);
            $stmt_nome->execute();

            $linha_nome = $stmt_nome->get_result()->fetch_assoc();

            $stmt_nome->close();

            $_SESSION['nome_usuario'] = $linha_nome['nome'] ?? 'Usuário';
        }
    }

    $nome_usuario = $_SESSION['nome_usuario'] ?? 'Usuário';

    // Só o primeiro nome cabe bem na barra; o nome completo fica no
    // menu de perfil.
    $primeiro_nome = trim(explode(' ', trim($nome_usuario))[0]);

    if ($primeiro_nome === '') {
        $primeiro_nome = 'Usuário';
    }
    if (isset($_GET['ler_notificacao'])) {

        $id_notificacao =
            intval($_GET['ler_notificacao']);

        $sql_ler = "
            UPDATE notificacoes
            SET lida = 1
            WHERE id = ?
            AND usuario_id = ?
        ";

        $stmt_ler =
            $conn->prepare($sql_ler);

        $stmt_ler->bind_param(
            "ii",
            $id_notificacao,
            $usuario_id
        );

        $stmt_ler->execute();
    }

    // ======================================
    // CRIAR NOTIFICAÇÕES AUTOMÁTICAS
    // ======================================

    // ======================================
    // TAREFAS QUE VENCEM HOJE
    // ======================================

    $sql_tarefas_hoje = "
        SELECT id, titulo
        FROM tarefas
        WHERE usuario_id = ?
        AND vencimento = CURDATE()
        AND status != 'Concluído'
    ";

    $stmt_tarefas_hoje = $conn->prepare($sql_tarefas_hoje);

    $stmt_tarefas_hoje->bind_param("i", $usuario_id);

    $stmt_tarefas_hoje->execute();

    $resultado_tarefas_hoje =
        $stmt_tarefas_hoje->get_result();

    while ($tarefa = $resultado_tarefas_hoje->fetch_assoc()) {

        $titulo_notificacao =
            "Tarefa vence hoje";

        $mensagem_notificacao =
            "A tarefa '{$tarefa['titulo']}' vence hoje.";

        // EVITA DUPLICAR

        $sql_verifica = "
            SELECT id
            FROM notificacoes
            WHERE usuario_id = ?
            AND titulo = ?
            AND mensagem = ?
        ";

        $stmt_verifica = $conn->prepare($sql_verifica);

        $stmt_verifica->bind_param(
            "iss",
            $usuario_id,
            $titulo_notificacao,
            $mensagem_notificacao
        );

        $stmt_verifica->execute();

        $resultado_verifica =
            $stmt_verifica->get_result();

        if ($resultado_verifica->num_rows == 0) {

            $sql_insert = "
                INSERT INTO notificacoes
                (usuario_id, titulo, mensagem)
                VALUES (?, ?, ?)
            ";

            $stmt_insert = $conn->prepare($sql_insert);

            $stmt_insert->bind_param(
                "iss",
                $usuario_id,
                $titulo_notificacao,
                $mensagem_notificacao
            );

            $stmt_insert->execute();
        }
    }

    // ======================================
    // TAREFAS QUE VENCEM AMANHÃ
    // ======================================

    $sql_tarefas_amanha = "
        SELECT id, titulo
        FROM tarefas
        WHERE usuario_id = ?
        AND vencimento = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        AND status != 'Concluído'
    ";

    $stmt_tarefas_amanha =
        $conn->prepare($sql_tarefas_amanha);

    $stmt_tarefas_amanha->bind_param(
        "i",
        $usuario_id
    );

    $stmt_tarefas_amanha->execute();

    $resultado_tarefas_amanha =
        $stmt_tarefas_amanha->get_result();

    while (
        $tarefa =
        $resultado_tarefas_amanha->fetch_assoc()
    ) {

        $titulo_notificacao =
            "Tarefa vence amanhã";

        $mensagem_notificacao =
            "A tarefa '{$tarefa['titulo']}' vence amanhã.";

        $sql_verifica = "
            SELECT id
            FROM notificacoes
            WHERE usuario_id = ?
            AND titulo = ?
            AND mensagem = ?
            AND DATE(data_criacao) = CURDATE()
        ";

        $stmt_verifica =
            $conn->prepare($sql_verifica);

        $stmt_verifica->bind_param(
            "iss",
            $usuario_id,
            $titulo_notificacao,
            $mensagem_notificacao
        );

        $stmt_verifica->execute();

        $resultado_verifica =
            $stmt_verifica->get_result();

        if ($resultado_verifica->num_rows == 0) {

            $sql_insert = "
                INSERT INTO notificacoes
                (usuario_id, titulo, mensagem)
                VALUES (?, ?, ?)
            ";

            $stmt_insert =
                $conn->prepare($sql_insert);

            $stmt_insert->bind_param(
                "iss",
                $usuario_id,
                $titulo_notificacao,
                $mensagem_notificacao
            );

            $stmt_insert->execute();
        }
    }

    // ======================================
    // TAREFAS ATRASADAS
    // ======================================

    $sql_tarefas_atrasadas = "
        SELECT id, titulo
        FROM tarefas
        WHERE usuario_id = ?
        AND vencimento < CURDATE()
        AND status != 'Concluído'
    ";

    // ======================================
    // PROVAS AMANHÃ
    // ======================================

    $sql_provas = "
        SELECT id, titulo
        FROM eventos
        WHERE usuario_id = ?
        AND tipo = 'prova'
        AND data = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ";

    $stmt_provas = $conn->prepare($sql_provas);

    $stmt_provas->bind_param("i", $usuario_id);

    $stmt_provas->execute();

    $resultado_provas =
        $stmt_provas->get_result();

    while ($prova = $resultado_provas->fetch_assoc()) {

        $titulo_notificacao =
            "Prova amanhã";

        $mensagem_notificacao =
            "A prova '{$prova['titulo']}' acontece amanhã.";

        // EVITA DUPLICAR

        $sql_verifica = "
            SELECT id
            FROM notificacoes
            WHERE usuario_id = ?
            AND titulo = ?
            AND mensagem = ?
        ";

        $stmt_verifica = $conn->prepare($sql_verifica);

        $stmt_verifica->bind_param(
            "iss",
            $usuario_id,
            $titulo_notificacao,
            $mensagem_notificacao
        );

        $stmt_verifica->execute();

        $resultado_verifica =
            $stmt_verifica->get_result();

        if ($resultado_verifica->num_rows == 0) {

            $sql_insert = "
                INSERT INTO notificacoes
                (usuario_id, titulo, mensagem)
                VALUES (?, ?, ?)
            ";

            $stmt_insert = $conn->prepare($sql_insert);

            $stmt_insert->bind_param(
                "iss",
                $usuario_id,
                $titulo_notificacao,
                $mensagem_notificacao
            );

            $stmt_insert->execute();
        }
    }

    // BUSCA NOTIFICAÇÕES
    $sql_notificacoes = "
        SELECT *
        FROM notificacoes
        WHERE usuario_id = ?
        AND lida = 0
        ORDER BY data_criacao DESC
        LIMIT 5
    ";

    $stmt_notificacoes = $conn->prepare($sql_notificacoes);

    $stmt_notificacoes->bind_param("i", $usuario_id);

    $stmt_notificacoes->execute();

    $resultado_notificacoes = $stmt_notificacoes->get_result();

    // QUANTIDADE NÃO LIDAS
    $sql_nao_lidas = "
        SELECT COUNT(*) as total
        FROM notificacoes
        WHERE usuario_id = ?
        AND lida = 0
    ";

    $stmt_nao_lidas = $conn->prepare($sql_nao_lidas);
    $stmt_nao_lidas->bind_param("i", $usuario_id);
    $stmt_nao_lidas->execute();
    $resultado_nao_lidas = $stmt_nao_lidas->get_result();
    $total_nao_lidas = $resultado_nao_lidas->fetch_assoc()['total'];

?>

<?php
    // A folha de estilo da sidebar é carregada no <head> de cada página,
    // e não aqui. Este arquivo é incluído dentro do <body>, então um
    // <link> nesta posição venceria css/tema.css na cascata e a barra
    // ficaria sempre com as cores do modo claro.
?>

<!-- Barra Superior -->
<div class="barra-superior">
    <div class="barra-esquerda">
        <button class="botao-menu-mobile" id="botao-menu-mobile">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <img
            src="img/logo_completa_azul.png"
            alt="Ilustração 3D de Bloco de Notas"
            id="logo_c"
            style="width:184px;height:46px;"
        >
    </div>

    <div class="barra-direita">
        <span class="saudacao-usuario">
            Olá, <strong><?php echo htmlspecialchars($primeiro_nome); ?></strong>
        </span>

        <button class="botao-notificacao" id="botao-notificacao">

            <img
                src="icons/sino.svg"
                width="24"
                height="24"
                alt="Sino de notificação"
                style="filter: brightness(0) invert(1);"
            >

            <?php if ($total_nao_lidas > 0): ?>
                <span class="badge-notificacao">
                    <?php echo $total_nao_lidas; ?>
                </span>
            <?php endif; ?>

        </button>

        <button class="botao-perfil" id="botao-perfil">
            <img
                src="icons/perfil.svg"
                width="24"
                height="24"
                alt="Perfil"
                style="filter: brightness(0) invert(1);"
            >
        </button>
    </div>
</div>

<?php
    // ======================================
    // ITEM ATIVO DO MENU
    // ======================================
    // caderno.php é a página de uma disciplina, então acende o mesmo
    // item que disciplinas.php — o usuário continua "dentro" de Disciplinas.

    $pagina_atual = basename($_SERVER['PHP_SELF']);

    if ($pagina_atual === 'caderno.php' || $pagina_atual === 'cadernos.php') {
        $pagina_atual = 'disciplinas.php';
    }

    /** Devolve a classe do item quando ele corresponde à página aberta. */
    function classe_item_menu($arquivo, $pagina_atual)
    {
        return ($arquivo === $pagina_atual) ? 'item-menu ativo' : 'item-menu';
    }
?>

<!-- Menu Lateral (Sidebar) -->
<aside class="sidebar" id="sidebar">
    <nav class="menu-navegacao">
        <a href="dashboard.php" class="<?php echo classe_item_menu('dashboard.php', $pagina_atual); ?>">
            <img src="icons/quadro.svg" width="24" height="24" alt="Quadro branco" style="filter: brightness(0) invert(1);">
            <span>Dashboard</span>
        </a>

        <a href="disciplinas.php" class="<?php echo classe_item_menu('disciplinas.php', $pagina_atual); ?>">
                <img src="icons/caderno.svg" width="24" height="24" alt="Caderno" style="filter: brightness(0) invert(1);">
            <span>Disciplinas</span>
        </a>

        <a href="horarios.php" class="<?php echo classe_item_menu('horarios.php', $pagina_atual); ?>">
            <img src="icons/relogio.svg" width="24" height="24" alt="Relógio" style="filter: brightness(0) invert(1);">
            <span>Horários</span>
        </a>

        <a href="tarefas.php" class="<?php echo classe_item_menu('tarefas.php', $pagina_atual); ?>">
            <img src="icons/checklist.svg" width="24" height="24" alt="Checklist" style="filter: brightness(0) invert(1);">
            <span>Tarefas</span>
        </a>

        <a href="agenda.php" class="<?php echo classe_item_menu('agenda.php', $pagina_atual); ?>">
            <img src="icons/calendario4dias.svg" width="24" height="24" alt="Calendario" style="filter: brightness(0) invert(1);">
            <span>Agenda</span>
        </a>

        <a href="foco.php" class="<?php echo classe_item_menu('foco.php', $pagina_atual); ?>">
            <img src="icons/cronometro.svg" width="24" height="24" alt="Cronômetro" style="filter: brightness(0) invert(1);">
            <span>Foco</span>
        </a>

        <a href="configuracoes.php" class="<?php echo classe_item_menu('configuracoes.php', $pagina_atual); ?>">
            <img src="icons/engrenagem.svg" width="24" height="24" alt="Engrenagem" style="filter: brightness(0) invert(1);">
            <span>Configurações</span>
        </a>
    </nav>
</aside>

<!-- Menu Perfil (Dropdown) -->
<div class="menu-perfil" id="menu-perfil">
    <div class="perfil-info">
        <div class="avatar-perfil">
            <img src="icons/perfil.svg" width="24" height="24" alt="Sino de notificação">
        </div>
        <div class="info-usuario">
            <p class="nome-usuario"><?php echo htmlspecialchars($nome_usuario); ?></p>
            <p class="email-usuario"><?php echo htmlspecialchars($email_usuario); ?></p>
        </div>
    </div>
    
    <hr class="divisor-menu">
    
    
    <a href="configuracoes.php" class="opcao-menu">
        <img src="icons/engrenagem.svg" width="24" height="24" alt="Engrenagem">
        Configurações
    </a>
    
    <hr class="divisor-menu">
    
    <a href="logout.php" class="opcao-menu opcao-sair">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        Sair
    </a>
</div>

<!-- MENU NOTIFICAÇÕES -->

<div class="menu-notificacoes" id="menu-notificacoes">

    <div class="topo-notificacoes">
        <h3>Notificações</h3>
    </div>

    <?php if ($resultado_notificacoes->num_rows > 0): ?>

        <?php while ($notificacao = $resultado_notificacoes->fetch_assoc()): ?>
            <div class="item-notificacao <?php echo ($notificacao['lida'] == 0) ? 'nao-lida' : ''; ?>">

                <h4>
                    <?php echo htmlspecialchars($notificacao['titulo']); ?>
                </h4>

                <p>
                    <?php echo htmlspecialchars($notificacao['mensagem']); ?>
                </p>

                <div class="rodape-notificacao">

                    <span>
                        <?php echo date(
                            'd/m H:i',
                            strtotime($notificacao['data_criacao'])
                        ); ?>
                    </span>

                    <?php if ($notificacao['lida'] == 0): ?>

                        <a
                            href="?ler_notificacao=<?php echo $notificacao['id']; ?>&abrir_notificacoes=1"
                            class="botao-marcar-lida"
                        >
                            Marcar como lida
                        </a>

                    <?php endif; ?>

                </div>

            </div>
        <?php endwhile; ?>

    <?php else: ?>
        <div class="sem-notificacoes">
            Nenhuma notificação.
        </div>
    <?php endif; ?>

</div>

