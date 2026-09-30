<?php
/**
 * Caderno da Disciplina - Flashnotes
 *
 * Reúne, em abas, todo o conteúdo de uma disciplina:
 * Anotações | Arquivos | Tarefas | Eventos
 *
 * Tarefas e eventos são apresentados em modo de leitura: continuam
 * sendo criados e editados nas telas Tarefas e Agenda, onde ganharam
 * o campo de vínculo com a disciplina.
 */

// Inicia a sessão
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// =====================================================
// CONEXÃO COM O BANCO DE DADOS
// =====================================================
require_once __DIR__ . '/../app/config/conexao.php';
require_once __DIR__ . '/../app/helpers/disciplinas.php';

$usuario_id = $_SESSION['usuario_id'];

// =====================================================
// DISCIPLINA DO CADERNO
// =====================================================
// A busca já filtra por usuario_id: um id de outro usuário
// simplesmente não é encontrado e devolve o usuário à lista.

$disciplina_id = intval($_GET['id'] ?? 0);

$disciplina = buscar_disciplina_do_usuario($conn, $disciplina_id, $usuario_id);

if ($disciplina === null) {
    $conn->close();

    $_SESSION['mensagem'] = "Disciplina não encontrada.";
    $_SESSION['tipo_mensagem'] = "erro";

    header("Location: disciplinas.php");
    exit();
}

// =====================================================
// AULAS DA DISCIPLINA
// =====================================================

$aulas = array();

$sql = "SELECT id, dia, horario_inicio, horario_fim
        FROM horarios
        WHERE disciplina_id = ? AND usuario_id = ?
        ORDER BY horario_inicio ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $disciplina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $aulas[] = $row;
}

$stmt->close();

// =====================================================
// ANOTAÇÕES
// =====================================================

$anotacoes = array();

$sql = "SELECT id, titulo, conteudo, data_criacao, data_atualizacao
        FROM anotacoes
        WHERE disciplina_id = ? AND usuario_id = ?
        ORDER BY data_atualizacao DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $disciplina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $anotacoes[] = $row;
}

$stmt->close();

// =====================================================
// ARQUIVOS
// =====================================================

$arquivos = array();

$sql = "SELECT id, nome_original, tipo_mime, tamanho, data_envio
        FROM arquivos
        WHERE disciplina_id = ? AND usuario_id = ?
        ORDER BY data_envio DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $disciplina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $arquivos[] = $row;
}

$stmt->close();

// =====================================================
// TAREFAS VINCULADAS
// =====================================================

$tarefas = array();

$sql = "SELECT id, titulo, vencimento, prioridade, status
        FROM tarefas
        WHERE disciplina_id = ? AND usuario_id = ?
        ORDER BY vencimento ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $disciplina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tarefas[] = $row;
}

$stmt->close();

// =====================================================
// EVENTOS VINCULADOS
// =====================================================

$eventos = array();

$sql = "SELECT id, titulo, data, tipo
        FROM eventos
        WHERE disciplina_id = ? AND usuario_id = ?
        ORDER BY data ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $disciplina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $eventos[] = $row;
}

$stmt->close();

// A conexão NÃO é fechada aqui: sidebar.php a reaproveita para
// montar as notificações, e o require_once dela não reabriria.
// O PHP encerra a conexão sozinho ao fim do script.

// =====================================================
// CORES (mesmas usadas em Tarefas e Agenda)
// =====================================================

$cores_prioridade = [
    'Alta'  => '#FF4444',
    'Média' => '#FFD700',
    'Baixa' => '#2e7d32'
];

$cores_tipo_evento = [
    'prova'        => '#FF4444',
    'apresentacao' => '#FF6B6B',
    'trabalho'     => '#FFD700',
    'reuniao'      => '#3B82F6',
    'outro'        => '#8B5CF6'
];

// =====================================================
// RECUPERAR MENSAGENS DE FEEDBACK
// =====================================================
$mensagem = '';
$tipo_mensagem = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];

    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

// =====================================================
// DIAS DA SEMANA (formulário de aulas)
// =====================================================

$dias_semana = [
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
    'Domingo'
];

// Aba aberta ao carregar (usada no retorno dos CRUDs)
$aba_inicial = $_GET['aba'] ?? 'anotacoes';

if (!in_array($aba_inicial, ['anotacoes', 'arquivos', 'tarefas', 'eventos', 'aulas'], true)) {
    $aba_inicial = 'anotacoes';
}

require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - <?php echo htmlspecialchars($disciplina['nome']); ?></title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/disciplinas.css">
    <link rel="stylesheet" href="css/cadernos.css">
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
            <!-- Mensagem de Feedback -->
            <?php if (!empty($mensagem)): ?>
                <div class="mensagem-feedback mensagem-<?php echo $tipo_mensagem; ?>">
                    <p><?php echo htmlspecialchars($mensagem); ?></p>
                    <button onclick="this.parentElement.style.display='none';" class="botao-fechar-mensagem">&times;</button>
                </div>
            <?php endif; ?>

            <a href="disciplinas.php" class="voltar-cadernos">&larr; Todas as disciplinas</a>

            <!-- Cabeçalho do caderno -->
            <div class="cabecalho-caderno"
                 style="--cor-disciplina: <?php echo htmlspecialchars($disciplina['cor']); ?>;">

                <div class="titulo-pagina">
                    <img src="icons/caderno.svg" width="32" height="32" alt="Caderno">
                    <div>
                        <h1><?php echo htmlspecialchars($disciplina['nome']); ?></h1>
                        <p>
                            <?php if (!empty($disciplina['professor'])): ?>
                                <?php echo htmlspecialchars($disciplina['professor']); ?>
                            <?php else: ?>
                                Sem professor informado
                            <?php endif; ?>

                            <?php if (count($aulas) > 0): ?>
                                &middot;
                                <?php
                                $resumo_aulas = [];
                                foreach ($aulas as $aula) {
                                    $resumo_aulas[] =
                                        $aula['dia'] . ' ' .
                                        substr($aula['horario_inicio'], 0, 5) . '-' .
                                        substr($aula['horario_fim'], 0, 5);
                                }
                                echo htmlspecialchars(implode(' | ', $resumo_aulas));
                                ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Abas -->
            <div class="abas-caderno" id="abas-caderno">
                <button class="aba" data-aba="anotacoes">
                    Anotações
                    <span class="contador-aba"><?php echo count($anotacoes); ?></span>
                </button>
                <button class="aba" data-aba="arquivos">
                    Arquivos
                    <span class="contador-aba"><?php echo count($arquivos); ?></span>
                </button>
                <button class="aba" data-aba="tarefas">
                    Tarefas
                    <span class="contador-aba"><?php echo count($tarefas); ?></span>
                </button>
                <button class="aba" data-aba="eventos">
                    Eventos
                    <span class="contador-aba"><?php echo count($eventos); ?></span>
                </button>
                <button class="aba" data-aba="aulas">
                    Aulas
                    <span class="contador-aba"><?php echo count($aulas); ?></span>
                </button>
            </div>

            <!-- ============================================= -->
            <!-- ABA: ANOTAÇÕES                                -->
            <!-- ============================================= -->
            <section class="painel-aba" id="painel-anotacoes">

                <div class="cabecalho-painel">
                    <h2>Anotações</h2>
                    <button class="botao-adicionar" id="botao-adicionar-anotacao">
                        Nova anotação +
                    </button>
                </div>

                <?php if (count($anotacoes) > 0): ?>
                    <div class="lista-anotacoes">
                        <?php foreach ($anotacoes as $anotacao): ?>
                            <article class="card-anotacao">
                                <h3><?php echo htmlspecialchars($anotacao['titulo']); ?></h3>

                                <!-- Conteúdo colado nas tags de propósito: a
                                     classe usa white-space: pre-wrap, que
                                     preserva espaços — a quebra de linha e a
                                     indentação do HTML virariam um recuo na
                                     primeira linha do texto. -->
                                <p class="conteudo-anotacao"><?php echo nl2br(htmlspecialchars($anotacao['conteudo'])); ?></p>

                                <div class="rodape-anotacao">
                                    <span class="data-anotacao">
                                        Atualizada em
                                        <?php echo date('d/m/Y H:i', strtotime($anotacao['data_atualizacao'])); ?>
                                    </span>

                                    <div class="acoes-disciplina">
                                        <button class="botao-editar"
                                            onclick='abrirModalEditarAnotacao(
                                                <?php echo (int) $anotacao["id"]; ?>,
                                                <?php echo json_encode($anotacao["titulo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                                                <?php echo json_encode($anotacao["conteudo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                            )'>
                                            Editar
                                        </button>

                                        <button class="botao-deletar"
                                            onclick='abrirModalExcluirAnotacao(
                                                <?php echo (int) $anotacao["id"]; ?>,
                                                <?php echo json_encode($anotacao["titulo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                            )'>
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>Nenhuma anotação nesta disciplina. Crie a primeira!</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============================================= -->
            <!-- ABA: ARQUIVOS                                 -->
            <!-- ============================================= -->
            <section class="painel-aba" id="painel-arquivos">

                <div class="cabecalho-painel">
                    <h2>Arquivos</h2>
                    <button class="botao-adicionar" id="botao-adicionar-arquivo">
                        Enviar arquivo +
                    </button>
                </div>

                <?php if (count($arquivos) > 0): ?>
                    <div class="lista-arquivos">
                        <?php foreach ($arquivos as $arquivo): ?>
                            <?php $eh_pdf = ($arquivo['tipo_mime'] === 'application/pdf'); ?>

                            <div class="card-arquivo">
                                <div class="icone-arquivo <?php echo $eh_pdf ? 'icone-pdf' : ''; ?>">
                                    <?php echo $eh_pdf ? 'PDF' : strtoupper(substr(pathinfo($arquivo['nome_original'], PATHINFO_EXTENSION), 0, 4)); ?>
                                </div>

                                <div class="info-arquivo">
                                    <h3><?php echo htmlspecialchars($arquivo['nome_original']); ?></h3>
                                    <p>
                                        <?php echo formatar_tamanho_arquivo($arquivo['tamanho']); ?>
                                        &middot;
                                        enviado em <?php echo date('d/m/Y', strtotime($arquivo['data_envio'])); ?>
                                    </p>
                                </div>

                                <div class="acoes-disciplina">
                                    <a class="botao-editar"
                                       href="actions/baixar_arquivo.php?id=<?php echo (int) $arquivo['id']; ?>"
                                       target="_blank" rel="noopener">
                                        Abrir
                                    </a>

                                    <a class="botao-editar"
                                       href="actions/baixar_arquivo.php?id=<?php echo (int) $arquivo['id']; ?>&amp;download=1">
                                        Baixar
                                    </a>

                                    <button class="botao-deletar"
                                        onclick='abrirModalExcluirArquivo(
                                            <?php echo (int) $arquivo["id"]; ?>,
                                            <?php echo json_encode($arquivo["nome_original"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                        )'>
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>Nenhum arquivo nesta disciplina. Envie seus PDFs e materiais de aula.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============================================= -->
            <!-- ABA: TAREFAS                                  -->
            <!-- ============================================= -->
            <section class="painel-aba" id="painel-tarefas">

                <div class="cabecalho-painel">
                    <h2>Tarefas desta disciplina</h2>
                    <div class="acoes-painel">
                        <a href="tarefas.php" class="botao-secundario">Ver todas</a>
                        <button class="botao-adicionar" id="botao-adicionar-tarefa">
                            Nova tarefa +
                        </button>
                    </div>
                </div>

                <?php if (count($tarefas) > 0): ?>
                    <div class="grade-vinculados">
                        <?php foreach ($tarefas as $tarefa): ?>
                            <?php $cor = $cores_prioridade[$tarefa['prioridade']] ?? '#2e7d32'; ?>

                            <div class="card-vinculado">
                                <div class="indicador-vinculado"
                                     style="background-color: <?php echo $cor; ?>;"></div>

                                <div class="conteudo-vinculado">
                                    <h3><?php echo htmlspecialchars($tarefa['titulo']); ?></h3>
                                    <div class="info-disciplina">
                                        <p><strong>Vence:</strong> <?php echo date('d/m/Y', strtotime($tarefa['vencimento'])); ?></p>
                                        <p><strong>Prioridade:</strong> <?php echo htmlspecialchars($tarefa['prioridade']); ?></p>
                                        <p><strong>Status:</strong> <?php echo htmlspecialchars($tarefa['status']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>
                            Nenhuma tarefa vinculada. Em
                            <a href="tarefas.php">Tarefas</a>,
                            escolha esta disciplina ao criar ou editar uma tarefa.
                        </p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============================================= -->
            <!-- ABA: EVENTOS                                  -->
            <!-- ============================================= -->
            <section class="painel-aba" id="painel-eventos">

                <div class="cabecalho-painel">
                    <h2>Eventos desta disciplina</h2>
                    <div class="acoes-painel">
                        <a href="agenda.php" class="botao-secundario">Ver todos</a>
                        <button class="botao-adicionar" id="botao-adicionar-evento">
                            Novo evento +
                        </button>
                    </div>
                </div>

                <?php if (count($eventos) > 0): ?>
                    <div class="grade-vinculados">
                        <?php foreach ($eventos as $evento): ?>
                            <?php $cor = $cores_tipo_evento[$evento['tipo']] ?? '#8B5CF6'; ?>

                            <div class="card-vinculado">
                                <div class="indicador-vinculado"
                                     style="background-color: <?php echo $cor; ?>;"></div>

                                <div class="conteudo-vinculado">
                                    <h3><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                    <div class="info-disciplina">
                                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data'])); ?></p>
                                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars(ucfirst($evento['tipo'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>Nenhum evento vinculado a esta disciplina ainda.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============================================= -->
            <!-- ABA: AULAS                                    -->
            <!-- ============================================= -->
            <section class="painel-aba" id="painel-aulas">

                <div class="cabecalho-painel">
                    <h2>Aulas na semana</h2>
                    <a href="horarios.php" class="botao-secundario">Ver grade semanal</a>
                </div>

                <?php if (count($aulas) > 0): ?>
                    <div class="lista-arquivos">
                        <?php foreach ($aulas as $aula): ?>
                            <div class="card-arquivo">
                                <div class="icone-arquivo">
                                    <?php echo htmlspecialchars(mb_substr($aula['dia'], 0, 3)); ?>
                                </div>

                                <div class="info-arquivo">
                                    <h3><?php echo htmlspecialchars($aula['dia']); ?></h3>
                                    <p>
                                        <?php
                                        echo htmlspecialchars(
                                            substr($aula['horario_inicio'], 0, 5) . ' - ' .
                                            substr($aula['horario_fim'], 0, 5)
                                        );
                                        ?>
                                    </p>
                                </div>

                                <div class="acoes-disciplina">
                                    <form method="POST" action="actions/crud_disciplinas.php">
                <?php echo campo_csrf(); ?>
                                        <input type="hidden" name="acao" value="excluir_aula">
                                        <input type="hidden" name="id" value="<?php echo (int) $aula['id']; ?>">
                                        <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                                        <button type="submit" class="botao-deletar">Remover</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>Nenhuma aula cadastrada para esta disciplina.</p>
                    </div>
                <?php endif; ?>

                <!-- Formulário de nova aula, direto no painel -->
                <form class="formulario-aula" method="POST" action="actions/crud_disciplinas.php">
                <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="adicionar_aula">
                    <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">

                    <h3>Adicionar aula</h3>

                    <div class="linha-formulario">
                        <div class="grupo-formulario-claro">
                            <label for="dia-semana-aula">Dia da semana</label>
                            <select id="dia-semana-aula" name="dia" required>
                                <option value="">Selecione um dia</option>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grupo-formulario-claro">
                            <label for="horario-inicio-aula">Início</label>
                            <input type="time" id="horario-inicio-aula" name="horario_inicio" required>
                        </div>

                        <div class="grupo-formulario-claro">
                            <label for="horario-termino-aula">Término</label>
                            <input type="time" id="horario-termino-aula" name="horario_fim" required>
                        </div>

                        <button type="submit" class="botao-adicionar">Adicionar</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL ADICIONAR ANOTAÇÃO                              -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-adicionar-anotacao">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Nova Anotação</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar-anotacao')">&times;</button>
            </div>

            <form class="formulario-disciplina" method="POST" action="actions/crud_anotacoes.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">

                <div class="grupo-formulario">
                    <label for="titulo-anotacao">Título</label>
                    <input type="text" id="titulo-anotacao" name="titulo"
                           placeholder="Ex: Resumo da aula 3" maxlength="255" required>
                </div>

                <div class="grupo-formulario">
                    <label for="conteudo-anotacao">Conteúdo</label>
                    <textarea id="conteudo-anotacao" name="conteudo" rows="8"
                              placeholder="Escreva sua anotação aqui..."></textarea>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL EDITAR ANOTAÇÃO                                 -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-editar-anotacao">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Editar Anotação</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-editar-anotacao')">&times;</button>
            </div>

            <form class="formulario-disciplina" method="POST" action="actions/crud_anotacoes.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                <input type="hidden" id="id-anotacao-editar" name="id">

                <div class="grupo-formulario">
                    <label for="titulo-anotacao-editar">Título</label>
                    <input type="text" id="titulo-anotacao-editar" name="titulo" maxlength="255" required>
                </div>

                <div class="grupo-formulario">
                    <label for="conteudo-anotacao-editar">Conteúdo</label>
                    <textarea id="conteudo-anotacao-editar" name="conteudo" rows="8"></textarea>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL EXCLUIR ANOTAÇÃO                                -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-excluir-anotacao">
        <div class="conteudo-modal conteudo-excluir">
            <div class="titulo-excluir">
                <h2 id="titulo-anotacao-excluir">Anotação</h2>
                <h3>Excluir</h3>
            </div>

            <p class="mensagem-excluir">Deseja realmente excluir essa anotação?</p>

            <div class="botoes-excluir">
                <button class="botao-nao" onclick="fecharModal('modal-excluir-anotacao')">Não</button>

                <form method="POST" action="actions/crud_anotacoes.php" style="display: inline;">
                <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                    <input type="hidden" id="id-anotacao-excluir" name="id">
                    <button type="submit" class="botao-sim">Sim</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL ENVIAR ARQUIVO                                  -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-adicionar-arquivo">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Enviar Arquivo</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar-arquivo')">&times;</button>
            </div>

            <form class="formulario-disciplina" method="POST"
                  action="actions/crud_arquivos.php" enctype="multipart/form-data">
                <?php echo campo_csrf(); ?>

                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">

                <div class="grupo-formulario">
                    <label for="arquivo">Arquivo</label>
                    <input type="file" id="arquivo" name="arquivo" required
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.png,.jpg,.jpeg,.gif,.zip">
                    <small class="ajuda-formulario">
                        PDF, Word, PowerPoint, Excel, texto, imagens ou ZIP. Até 10 MB.
                    </small>
                </div>

                <button type="submit" class="botao-salvar">Enviar</button>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL EXCLUIR ARQUIVO                                 -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-excluir-arquivo">
        <div class="conteudo-modal conteudo-excluir">
            <div class="titulo-excluir">
                <h2 id="titulo-arquivo-excluir">Arquivo</h2>
                <h3>Excluir</h3>
            </div>

            <p class="mensagem-excluir">Deseja realmente excluir esse arquivo?</p>

            <div class="botoes-excluir">
                <button class="botao-nao" onclick="fecharModal('modal-excluir-arquivo')">Não</button>

                <form method="POST" action="actions/crud_arquivos.php" style="display: inline;">
                <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                    <input type="hidden" id="id-arquivo-excluir" name="id">
                    <button type="submit" class="botao-sim">Sim</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL NOVA TAREFA (já vinculada a esta disciplina)     -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-adicionar-tarefa">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Nova Tarefa</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar-tarefa')">&times;</button>
            </div>

            <form class="formulario-disciplina" method="POST" action="actions/crud_tarefas.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                <!-- Faz o CRUD devolver o usuário para este caderno -->
                <input type="hidden" name="retorno" value="caderno">

                <p class="ajuda-formulario">
                    Será vinculada a <?php echo htmlspecialchars($disciplina['nome']); ?>
                    e também aparecerá na tela Tarefas.
                </p>

                <div class="grupo-formulario">
                    <label for="titulo-tarefa-caderno">Título da tarefa</label>
                    <input type="text" id="titulo-tarefa-caderno" name="titulo" maxlength="255" required>
                </div>

                <div class="grupo-formulario">
                    <label for="vencimento-tarefa-caderno">Data de vencimento</label>
                    <input type="date" id="vencimento-tarefa-caderno" name="vencimento" required>
                </div>

                <div class="grupo-formulario">
                    <label for="prioridade-tarefa-caderno">Prioridade</label>
                    <select id="prioridade-tarefa-caderno" name="prioridade" required>
                        <option value="Baixa">Baixa</option>
                        <option value="Média">Média</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="status-tarefa-caderno">Status</label>
                    <select id="status-tarefa-caderno" name="status" required>
                        <option value="Não iniciado">Não iniciado</option>
                        <option value="Em progresso">Em progresso</option>
                        <option value="Concluído">Concluído</option>
                    </select>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL NOVO EVENTO (já vinculado a esta disciplina)     -->
    <!-- ===================================================== -->
    <div class="modal" id="modal-adicionar-evento">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Novo Evento</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar-evento')">&times;</button>
            </div>

            <form class="formulario-disciplina" method="POST" action="actions/crud_agenda.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="disciplina_id" value="<?php echo (int) $disciplina_id; ?>">
                <!-- Faz o CRUD responder com redirect em vez de JSON -->
                <input type="hidden" name="retorno" value="caderno">

                <p class="ajuda-formulario">
                    Será vinculado a <?php echo htmlspecialchars($disciplina['nome']); ?>
                    e também aparecerá na Agenda.
                </p>

                <div class="grupo-formulario">
                    <label for="titulo-evento-caderno">Título do evento</label>
                    <input type="text" id="titulo-evento-caderno" name="titulo" maxlength="255" required>
                </div>

                <div class="grupo-formulario">
                    <label for="data-evento-caderno">Data do evento</label>
                    <input type="date" id="data-evento-caderno" name="data" required>
                </div>

                <div class="grupo-formulario">
                    <label for="tipo-evento-caderno">Tipo de evento</label>
                    <select id="tipo-evento-caderno" name="tipo" required>
                        <option value="prova">Prova</option>
                        <option value="apresentacao">Apresentação</option>
                        <option value="trabalho">Trabalho</option>
                        <option value="reuniao">Reunião</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Overlay para modais -->
    <div class="overlay" id="overlay" onclick="fecharTodosModais()"></div>

    <script>
        const abaInicial = <?php echo json_encode($aba_inicial); ?>;
    </script>

    <script src="js/caderno.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>
