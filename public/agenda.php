<?php
/**
 * Agenda - Flashnotes
 */

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Conexão com banco
include __DIR__ . '/../app/config/conexao.php';
require_once __DIR__ . '/../app/helpers/disciplinas.php';

// ID do usuário logado
$usuario_id = $_SESSION['usuario_id'];

// Disciplinas disponíveis para vincular
$disciplinas = buscar_disciplinas_usuario($conn, $usuario_id);

// Buscar eventos do usuário
// LEFT JOIN: o vínculo com disciplina é opcional, então eventos sem
// disciplina continuam aparecendo normalmente nesta tela.
$sql = "SELECT e.*, d.nome AS disciplina
        FROM eventos e
        LEFT JOIN disciplinas d ON d.id = e.disciplina_id
        WHERE e.usuario_id = ?
        ORDER BY e.data ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Array de eventos
$eventos = [];

while($row = $resultado->fetch_assoc()) {
    $eventos[] = $row;
}

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

// Data atual
// O (int) é necessário: date('m') devolve "09", e desde o PHP 8 uma
// string com zero à esquerda não é aceita como índice numérico —
// $mes_nome["09"] emitia "Undefined array key" de janeiro a setembro.
$mes_atual = (int) date('m');
$ano_atual = date('Y');
$mes_nome = array(
    '',
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro'
);


require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Agenda</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/agenda.css">
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

            <div class="cabecalho-agenda">
                <div class="titulo-agenda">
                    <img src="icons/calendario4dias.svg" width="24" height="24" alt="Calendario">
                    <h1>Agenda</h1>
                </div>
            </div>
            
            <!-- Container Principal -->
            <div class="container-agenda">
                <!-- Calendário -->
                <div class="secao-calendario">
                    <div class="calendario">
                        <div class="cabecalho-calendario">
                            <button class="botao-mes" id="mes-anterior"><img src="icons/seta_esquerda.svg" width="24" height="24" alt="Calendario" style="filter: brightness(0) invert(1);"></button>
                            <h2 id="mes-ano"><?php echo $mes_nome[$mes_atual] . ' ' . $ano_atual; ?></h2>
                            <button class="botao-mes" id="mes-proximo"><img src="icons/seta_direita.svg" width="24" height="24" alt="Calendario" style="filter: brightness(0) invert(1);"></button>
                        </div>
                        
                        <div class="dias-semana">
                            <div class="dia-semana">Dom</div>
                            <div class="dia-semana">Seg</div>
                            <div class="dia-semana">Ter</div>
                            <div class="dia-semana">Qua</div>
                            <div class="dia-semana">Qui</div>
                            <div class="dia-semana">Sex</div>
                            <div class="dia-semana">Sab</div>
                        </div>
                        
                        <div class="dias-calendario" id="dias-calendario">
                            <!-- Preenchido por JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Próximos Eventos -->
                <div class="secao-eventos">
                    <div class="cabecalho-eventos">
                        <div class="titulo-eventos">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <h2>Eventos</h2>
                        </div>
                        <button class="botao-adicionar" id="botao-adicionar-evento">
                            Adicionar evento +
                        </button>
                    </div>
                    
                    <div class="lista-eventos" id="lista-eventos">
                        <?php foreach ($eventos as $evento): ?>
                            <?php
                            $coresTipo = [
                                'prova' => '#FF4444',
                                'apresentacao' => '#FF6B6B',
                                'trabalho' => '#FFD700',
                                'reuniao' => '#3B82F6',
                                'outro' => '#8B5CF6'
                            ];
                            $cor = $coresTipo[$evento['tipo']] ?? '#8B5CF6';
                            ?>

                            <div class="card-evento" data-id="<?= $evento['id']; ?>">

                                <div class="indicador-evento"
                                    style="background-color: <?= $cor; ?>;">
                                </div>

                                <div class="conteudo-evento">
                                    <h3><?= htmlspecialchars($evento['titulo']); ?></h3>
                                    <p class="data-evento">
                                        Data:
                                        <?= date('d/m/Y', strtotime($evento['data'])); ?>
                                    </p>

                                    <?php if (!empty($evento['disciplina'])): ?>
                                        <p class="disciplina-evento">
                                            <?= htmlspecialchars($evento['disciplina']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="acoes-evento">
                                    <button class="botao-editar-evento"
                                        onclick='abrirModalEditar(
                                            <?= (int) $evento["id"]; ?>,
                                            <?= json_encode($evento["titulo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                                            <?= json_encode($evento["data"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                                            <?= json_encode($evento["tipo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                                            <?= json_encode($evento["disciplina_id"]); ?>
                                        )'>
                                        Editar
                                    </button>

                                    <button class="botao-deletar-evento"
                                        onclick='abrirModalExcluir(
                                            <?= (int) $evento["id"]; ?>,
                                            <?= json_encode($evento["titulo"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                        )'>
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Adicionar Evento -->
    <div class="modal" id="modal-adicionar">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Adicionar Evento</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-adicionar')">&times;</button>
            </div>
            <form class="formulario-evento" onsubmit="salvarEvento(event)">
                <div class="grupo-formulario">
                    <label for="titulo-evento">Título do Evento</label>
                    <input type="text" id="titulo-evento" name="titulo" placeholder="Ex: Prova de Matemática" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="data-evento">Data do Evento</label>
                    <input type="date" id="data-evento" name="data" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="tipo-evento">Tipo de Evento</label>
                    <select id="tipo-evento" name="tipo" required>
                        <option value="prova">Prova</option>
                        <option value="apresentacao">Apresentação</option>
                        <option value="trabalho">Trabalho</option>
                        <option value="reuniao">Reunião</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="disciplina-evento">Disciplina (opcional)</label>
                    <select id="disciplina-evento" name="disciplina_id">
                        <option value="">Sem disciplina</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo (int) $disciplina['id']; ?>">
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Evento -->
    <div class="modal" id="modal-editar">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2>Editar Evento</h2>
                <button class="botao-fechar" onclick="fecharModal('modal-editar')">&times;</button>
            </div>
            <form class="formulario-evento" onsubmit="salvarEdicao(event)">
                <input type="hidden" id="id-evento-editar" name="id">
                
                <div class="grupo-formulario">
                    <label for="titulo-evento-editar">Título do Evento</label>
                    <input type="text" id="titulo-evento-editar" name="titulo" placeholder="Ex: Prova de Matemática" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="data-evento-editar">Data do Evento</label>
                    <input type="date" id="data-evento-editar" name="data" required>
                </div>
                
                <div class="grupo-formulario">
                    <label for="tipo-evento-editar">Tipo de Evento</label>
                    <select id="tipo-evento-editar" name="tipo" required>
                        <option value="prova">Prova</option>
                        <option value="apresentacao">Apresentação</option>
                        <option value="trabalho">Trabalho</option>
                        <option value="reuniao">Reunião</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="disciplina-evento-editar">Disciplina (opcional)</label>
                    <select id="disciplina-evento-editar" name="disciplina_id">
                        <option value="">Sem disciplina</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo (int) $disciplina['id']; ?>">
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="botao-salvar">Salvar</button>
            </form>
        </div>
    </div>
    
    <!-- Modal Excluir Evento -->
    <div class="modal" id="modal-excluir">
        <div class="conteudo-modal conteudo-excluir">
            <div class="titulo-excluir">
                <h2 id="titulo-evento-excluir">Evento</h2>
                <h3>Excluir</h3>
            </div>
            <p class="mensagem-excluir">Deseja realmente excluir esse evento?</p>
            <div class="botoes-excluir">
                <button class="botao-nao" onclick="fecharModal('modal-excluir')">Não</button>
                <button class="botao-sim" onclick="confirmarExclusao()">Sim</button>
            </div>
        </div>
    </div>

    <!-- Modal Eventos do Dia -->
    <div class="modal" id="modal-eventos-dia">
        <div class="conteudo-modal">
            <div class="cabecalho-modal">
                <h2 id="titulo-dia-eventos">Eventos do Dia</h2>

                <button class="botao-fechar"
                    onclick="fecharModal('modal-eventos-dia')">
                    &times;
                </button>
            </div>

            <div id="lista-eventos-dia"></div>
        </div>
    </div>
    
    <!-- Overlay para modais -->
    <div class="overlay" id="overlay"
        onclick="fecharTodosModais()">
    </div>

    <script>
    // Token CSRF enviado junto de cada requisição do agenda.js
    const tokenCsrf = <?php echo json_encode(token_csrf()); ?>;

    const eventosPHP = <?php
    $eventosFormatados = [];
    foreach ($eventos as $evento) {

        $eventosFormatados[] = [
            'id' => $evento['id'],
            'titulo' => $evento['titulo'],
            'tipo' => $evento['tipo'],
            'data' => date('Y-m-d', strtotime($evento['data']))
        ];
    }
    // As flags JSON_HEX_* escapam < > ' " &, para que o título de um
    // evento não consiga fechar esta tag <script>.
    echo json_encode(
        $eventosFormatados,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );
    ?>;
    </script>

    <script src="js/agenda.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>
