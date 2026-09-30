<?php
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// Proteção de acesso
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit();
}

// CONEXÃO
require_once __DIR__ . '/../app/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];

// inicializar como array

$tarefas_pendentes = [];
$proximos_eventos = [];
$horarios = [];

// ======================
// TAREFAS
// ======================

// LEFT JOIN: a tarefa pode não estar vinculada a nenhuma disciplina
$sql = "SELECT t.*, d.nome AS disciplina
        FROM tarefas t
        LEFT JOIN disciplinas d ON d.id = t.disciplina_id
        WHERE t.usuario_id = ?
        AND t.status != 'Concluído'
        ORDER BY
            CASE
                WHEN t.status = 'Não iniciado' THEN 1
                WHEN t.status = 'Em progresso' THEN 2
                ELSE 3
            END,
            t.vencimento ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tarefas_pendentes[] = $row;
}

// ======================
// EVENTOS
// ======================

// LEFT JOIN: o evento pode não estar vinculado a nenhuma disciplina
$sql = "SELECT e.*, d.nome AS disciplina
        FROM eventos e
        LEFT JOIN disciplinas d ON d.id = e.disciplina_id
        WHERE e.usuario_id = ?
        ORDER BY e.data ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $proximos_eventos[] = $row;
}

// ======================
// HORÁRIOS
// ======================

$sql = "SELECT * FROM horarios WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $horarios[] = $row;
}

require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
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
            <div class="colunas-dashboard">
                
                <!-- Coluna 1: Tarefas Pendentes -->
                <section class="coluna">
                    <div class="cabecalho-coluna">
                        <img src="icons/checklist.svg" width="24" height="24" alt="Checklist">
                        <h2>Tarefas Pendentes</h2>
                    </div>
                    
            <div class="lista-itens">
                <?php foreach ($tarefas_pendentes as $tarefa): ?>
                    <?php
                    $corPrioridade = '#2e7d32';
                    if ($tarefa['prioridade'] == 'Alta') {
                        $corPrioridade = '#FF4444';
                    }
                    elseif ($tarefa['prioridade'] == 'Média') {
                        $corPrioridade = '#FFD700';
                    }
                    elseif ($tarefa['prioridade'] == 'Baixa') {
                        $corPrioridade = '#2e7d32';
                    }
                    ?>
                    <a class="item-tarefa" href="tarefas.php">
                        <div class="titulo-tarefa">
                            <strong>
                                <?php echo htmlspecialchars($tarefa['titulo']); ?>
                            </strong>
                        </div>

                        <div class="detalhes-tarefa">
                            <span class="vencimento">
                                Vence:
                                <?php echo date('d/m/Y', strtotime($tarefa['vencimento'])); ?>
                            </span>

                            <span class="prioridade"
                                style="background-color: <?php echo $corPrioridade; ?>;">
                                <?php echo htmlspecialchars($tarefa['prioridade']); ?>
                            </span>

                        </div>

                        <div class="status-tarefa
                            status-<?php echo strtolower(str_replace(' ', '-', $tarefa['status'])); ?>">
                            <?php echo htmlspecialchars($tarefa['status']); ?>
                        </div>

                        <?php if (!empty($tarefa['disciplina'])): ?>
                            <div class="disciplina-item">
                                <?php echo htmlspecialchars($tarefa['disciplina']); ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>

                <?php if (count($tarefas_pendentes) === 0): ?>
                    <p class="lista-vazia">Nenhuma tarefa pendente.</p>
                <?php endif; ?>
            </div>
                </section>
                
                <!-- Coluna 2: Próximos Eventos -->
                <section class="coluna">
                    <div class="cabecalho-coluna">
                        <img src="icons/calendario4dias.svg" width="24" height="24" alt="Calendario">
                        <h2>Próximos Eventos</h2>
                    </div>
                    
                    <div class="lista-itens">
                        <?php foreach ($proximos_eventos as $evento): ?>
                            <a class="item-evento" href="agenda.php">
                                <div class="marcador-evento"></div>
                                <div class="info-evento">
                                    <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                    <span class="data-evento">Data: <?php echo date('d/m/Y', strtotime($evento['data'])); ?></span>

                                    <?php if (!empty($evento['disciplina'])): ?>
                                        <span class="disciplina-item">
                                            <?php echo htmlspecialchars($evento['disciplina']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>

                        <?php if (count($proximos_eventos) === 0): ?>
                            <p class="lista-vazia">Nenhum evento agendado.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Coluna 3: Horário -->
                <?php
                date_default_timezone_set('America/Sao_Paulo');

                $diasSemana = [
                    'Sunday' => 'Domingo',
                    'Monday' => 'Segunda-feira',
                    'Tuesday' => 'Terça-feira',
                    'Wednesday' => 'Quarta-feira',
                    'Thursday' => 'Quinta-feira',
                    'Friday' => 'Sexta-feira',
                    'Saturday' => 'Sábado'
                ];

                $diaHoje = $diasSemana[date('l')];

                // O nome da disciplina agora vem da tabela `disciplinas`.
                // O id vem junto para que a aula leve ao caderno dela.
                $sql = "SELECT d.id AS disciplina_id, d.nome AS disciplina,
                               h.horario_inicio, h.horario_fim, h.dia
                        FROM horarios h
                        INNER JOIN disciplinas d ON d.id = h.disciplina_id
                        WHERE h.usuario_id = ?
                        AND h.dia = ?
                        ORDER BY h.horario_inicio ASC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $usuario_id, $diaHoje);
                $stmt->execute();

                $resultado = $stmt->get_result();

                $horarios = [];

                while ($row = $resultado->fetch_assoc()) {
                    $horarios[] = $row;
                }

                ?>
                <section class="coluna">
                    <div class="cabecalho-coluna">
                        <img src="icons/relogio.svg" width="24" height="24" alt="Relógio">
                        <h2>Horário</h2>
                    </div>
                    
                    <div class="lista-itens">
                        <?php if (count($horarios) > 0): ?>

                                <?php foreach ($horarios as $horario): ?>

                                    <!-- A aula leva direto ao caderno da disciplina -->
                                    <a class="item-horario"
                                       href="caderno.php?id=<?php echo (int) $horario['disciplina_id']; ?>">

                                        <div class="disciplina-horario">
                                            <strong>
                                                <?php echo htmlspecialchars($horario['disciplina']); ?>
                                            </strong>
                                        </div>

                                        <div class="tempo-horario">
                                            <?php echo date('H:i', strtotime($horario['horario_inicio'])); ?>
                                            -
                                            <?php echo date('H:i', strtotime($horario['horario_fim'])); ?>
                                        </div>

                                        <div class="dia-horario">
                                            <?php echo htmlspecialchars($horario['dia']); ?>
                                        </div>

                                    </a>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="item-horario">
                                    <div class="disciplina-horario">
                                        <strong>Nenhuma aula hoje</strong>
                                    </div>

                                    <div class="tempo-horario">
                                        Aproveite seu dia ✨
                                    </div>
                                </div>

                            <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script src="js/sidebar.js"></script>
</body>
</html>
