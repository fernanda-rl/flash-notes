<?php
/**
 * Disciplinas - Flashnotes
 * Página para exibir e gerenciar disciplinas (matérias) e suas aulas
 * Verifica se o usuário está autenticado antes de exibir o conteúdo
 */

// Inicia a sessão
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit();
}

// =====================================================
// CONEXÃO COM O BANCO DE DADOS
// =====================================================
require_once __DIR__ . '/../app/config/conexao.php';

// Obtém o ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];

// =====================================================
// BUSCAR DISCIPLINAS DO BANCO DE DADOS
// =====================================================
// Cada disciplina é uma linha; as aulas vêm em seguida e são
// agrupadas por disciplina no PHP.

$disciplinas = array();

// Os contadores vêm de subconsultas próprias: um JOIN das quatro tabelas
// multiplicaria as linhas e inflaria os totais.
$sql = "SELECT
            d.id, d.nome, d.professor, d.cor,
            (SELECT COUNT(*) FROM anotacoes a
              WHERE a.disciplina_id = d.id AND a.usuario_id = d.usuario_id) AS total_anotacoes,
            (SELECT COUNT(*) FROM arquivos f
              WHERE f.disciplina_id = d.id AND f.usuario_id = d.usuario_id) AS total_arquivos,
            (SELECT COUNT(*) FROM tarefas t
              WHERE t.disciplina_id = d.id AND t.usuario_id = d.usuario_id) AS total_tarefas,
            (SELECT COUNT(*) FROM eventos e
              WHERE e.disciplina_id = d.id AND e.usuario_id = d.usuario_id) AS total_eventos
        FROM disciplinas d
        WHERE d.usuario_id = ?
        ORDER BY d.nome ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['aulas'] = array();
        $disciplinas[$row['id']] = $row;
    }

    $stmt->close();
}

// =====================================================
// BUSCAR AULAS E AGRUPAR POR DISCIPLINA
// =====================================================

$sql = "SELECT id, disciplina_id, dia, horario_inicio, horario_fim
        FROM horarios
        WHERE usuario_id = ?
        ORDER BY horario_inicio ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        // Ignora aula de disciplina que não está na lista acima
        if (isset($disciplinas[$row['disciplina_id']])) {
            $disciplinas[$row['disciplina_id']]['aulas'][] = $row;
        }
    }

    $stmt->close();
}

// A conexão NÃO é fechada aqui: sidebar.php a reaproveita para
// montar as notificações, e o require_once dela não reabriria.
// O PHP encerra a conexão sozinho ao fim do script.

// =====================================================
// DIAS DA SEMANA (usados nos formulários)
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

// =====================================================
// RECUPERAR MENSAGENS DE FEEDBACK
// =====================================================
$mensagem = '';
$tipo_mensagem = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];

    // Limpa as mensagens da sessão após exibir
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Disciplinas</title>
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

            <div class="cabecalho-pagina">
                <div class="titulo-pagina">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <div>
                        <h1>Disciplinas</h1>
                        <p>Gerencie suas disciplinas e horários</p>
                    </div>
                </div>
                <button class="botao-adicionar" id="botao-adicionar-disciplina">
                    Adicionar disciplina +
                </button>
            </div>

            <!-- Barra de Pesquisa -->
            <div class="barra-pesquisa">
                <input type="text" id="campo-pesquisa" placeholder="PESQUISE AQUI A DISCIPLINA" class="campo-pesquisa">
            </div>

            <!-- Grade de Disciplinas -->
            <div class="grade-disciplinas" id="grade-disciplinas">
                <?php if (count($disciplinas) > 0): ?>
                    <?php foreach ($disciplinas as $disciplina): ?>
                        <div class="card-disciplina"
                             data-id="<?php echo (int) $disciplina['id']; ?>"
                             style="--cor-disciplina: <?php echo htmlspecialchars($disciplina['cor']); ?>;">

                            <!-- O corpo do card inteiro abre o caderno da disciplina.
                                 Os botões de ação ficam fora do link, logo abaixo. -->
                            <a class="corpo-disciplina"
                               href="caderno.php?id=<?php echo (int) $disciplina['id']; ?>">

                                <h3><?php echo htmlspecialchars($disciplina['nome']); ?></h3>

                                <p class="professor-caderno">
                                    <?php
                                    echo !empty($disciplina['professor'])
                                        ? htmlspecialchars($disciplina['professor'])
                                        : 'Sem professor informado';
                                    ?>
                                </p>

                                <div class="info-disciplina">
                                    <?php if (count($disciplina['aulas']) > 0): ?>
                                        <?php foreach ($disciplina['aulas'] as $aula): ?>
                                            <p>
                                                <strong>Aula:</strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $aula['dia'] . ', ' .
                                                    substr($aula['horario_inicio'], 0, 5) . ' - ' .
                                                    substr($aula['horario_fim'], 0, 5)
                                                );
                                                ?>
                                            </p>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p><strong>Aulas:</strong> nenhuma cadastrada</p>
                                    <?php endif; ?>
                                </div>

                                <div class="contadores-caderno">
                                    <span class="contador-caderno">
                                        <strong><?php echo (int) $disciplina['total_anotacoes']; ?></strong> anotações
                                    </span>
                                    <span class="contador-caderno">
                                        <strong><?php echo (int) $disciplina['total_arquivos']; ?></strong> arquivos
                                    </span>
                                    <span class="contador-caderno">
                                        <strong><?php echo (int) $disciplina['total_tarefas']; ?></strong> tarefas
                                    </span>
                                    <span class="contador-caderno">
                                        <strong><?php echo (int) $disciplina['total_eventos']; ?></strong> eventos
                                    </span>
                                </div>

                                <span class="abrir-caderno">Abrir caderno &rarr;</span>
                            </a>

                            <div class="acoes-disciplina">
                                <button class="botao-editar" onclick="abrirModalEditar(<?php echo (int) $disciplina['id']; ?>)">
                                    Editar
                                </button>

                                <button class="botao-deletar" onclick="abrirModalExcluir(<?php echo (int) $disciplina['id']; ?>)">
                                    Deletar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mensagem-vazia">
                        <p>Nenhuma disciplina cadastrada. Comece adicionando uma!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Adicionar Disciplina -->
    <div class="modal" id="modal-adicionar">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Adicionar Disciplina</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar')">&times;</button>
            </div>
            <form class="formulario-disciplina" method="POST" action="actions/crud_disciplinas.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="adicionar">

                <div class="grupo-formulario">
                    <label for="nome-disciplina">Nome da Disciplina</label>
                    <input type="text" id="nome-disciplina" name="nome" placeholder="Ex: Matemática" maxlength="100" required>
                </div>

                <div class="grupo-formulario">
                    <label for="professor">Nome do Professor</label>
                    <input type="text" id="professor" name="professor" placeholder="Ex: Prof. João Silva" maxlength="100">
                </div>

                <div class="grupo-formulario">
                    <label for="cor-disciplina">Cor do caderno</label>
                    <input type="color" id="cor-disciplina" name="cor" value="#0066da">
                </div>

                <hr class="divisor-formulario">

                <p class="ajuda-formulario">
                    Primeira aula (opcional) — você pode adicionar mais aulas depois.
                </p>

                <div class="grupo-formulario">
                    <label for="dia-semana">Dia da Semana</label>
                    <select id="dia-semana" name="dia">
                        <option value="">Selecione um dia</option>
                        <?php foreach ($dias_semana as $dia): ?>
                            <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="horario-inicio">Horário de Início</label>
                    <input type="time" id="horario-inicio" name="horario_inicio">
                </div>

                <div class="grupo-formulario">
                    <label for="horario-termino">Horário de Término</label>
                    <input type="time" id="horario-termino" name="horario_fim">
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar Disciplina -->
    <div class="modal" id="modal-editar">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2 id="titulo-editar">Editar Disciplina</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-editar')">&times;</button>
            </div>
            <form class="formulario-disciplina" method="POST" action="actions/crud_disciplinas.php">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" id="id-disciplina-editar" name="id">

                <div class="grupo-formulario">
                    <label for="nome-disciplina-editar">Nome da Disciplina</label>
                    <input type="text" id="nome-disciplina-editar" name="nome" placeholder="Ex: Matemática" maxlength="100" required>
                </div>

                <div class="grupo-formulario">
                    <label for="professor-editar">Nome do Professor</label>
                    <input type="text" id="professor-editar" name="professor" placeholder="Ex: Prof. João Silva" maxlength="100">
                </div>

                <div class="grupo-formulario">
                    <label for="cor-disciplina-editar">Cor do caderno</label>
                    <input type="color" id="cor-disciplina-editar" name="cor" value="#0066da">
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>

    <!-- As aulas da disciplina sao gerenciadas na aba "Aulas" do caderno -->
    <!-- Modal Excluir Disciplina -->
    <div class="modal" id="modal-excluir">
        <div class="conteudo-modal conteudo-excluir">
            <div class="titulo-excluir">
                <h2 id="nome-disciplina-excluir">Disciplina</h2>
                <h3>Excluir</h3>
            </div>
            <p class="mensagem-excluir">
                Deseja realmente excluir essa matéria?<br>
                As aulas, anotações e arquivos dela serão apagados.
                As tarefas e os eventos serão mantidos, apenas sem o vínculo.
            </p>
            <div class="botoes-excluir">
                <button class="botao-nao" onclick="fecharModal('modal-excluir')">Não</button>
                <form id="form-excluir" method="POST" action="actions/crud_disciplinas.php" style="display: inline;">
                <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" id="id-disciplina-excluir" name="id">
                    <button type="submit" class="botao-sim">
                        Sim
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Overlay para modais -->
    <div class="overlay" id="overlay" onclick="fecharTodosModais()"></div>

    <script>
        // Dados das disciplinas para preencher os modais sem nova consulta.
        // As flags JSON_HEX_* escapam < > ' " &, para que um nome de
        // disciplina não consiga fechar esta tag <script>.
        const disciplinasPHP = <?php
            echo json_encode(
                array_values($disciplinas),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
        ?>;
    </script>

    <script src="js/disciplinas.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>
