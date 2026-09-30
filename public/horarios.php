<?php
/**
 * Horários - Flashnotes
 * Página para visualizar o horário semanal de aulas
 */

// Inicia a sessão
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// =====================================================
// CONEXÃO COM O BANCO
// =====================================================

require_once __DIR__ . '/../app/config/conexao.php';

// Obtém o ID do usuário logado
$usuario_id = $_SESSION['usuario_id'];

// =====================================================
// ARRAY DOS DIAS DA SEMANA
// =====================================================

$horarios = [
    'Segunda-feira' => [],
    'Terça-feira' => [],
    'Quarta-feira' => [],
    'Quinta-feira' => [],
    'Sexta-feira' => [],
    'Sábado' => [],
    'Domingo' => []
];

// =====================================================
// BUSCAR HORÁRIOS DO BANCO
// =====================================================

// O nome da disciplina agora vem da tabela `disciplinas`
$sql = "SELECT d.nome AS disciplina, h.horario_inicio, h.horario_fim, h.dia
        FROM horarios h
        INNER JOIN disciplinas d ON d.id = h.disciplina_id
        WHERE h.usuario_id = ?
        ORDER BY h.horario_inicio ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $usuario_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $dia = $row['dia'];

        // Evita erro caso o dia não exista
        if (!isset($horarios[$dia])) {
            $horarios[$dia] = [];
        }

        $horarios[$dia][] = [
            'disciplina' => $row['disciplina'],
            'horario' =>
                substr($row['horario_inicio'], 0, 5)
                . ' - ' .
                substr($row['horario_fim'], 0, 5)
        ];
    }

    $stmt->close();
}

// A conexão NÃO é fechada aqui: sidebar.php a reaproveita para
// montar as notificações. O PHP a encerra ao fim do script.

// =====================================================
// DIAS DA SEMANA
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

require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashnotes - Horários</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/horarios.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/tema.css">
</head>

<body>

<div class="container-dashboard">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="conteudo-principal">

        <div class="cabecalho-horarios">

            <div class="titulo-horarios">

                <img src="icons/relogio.svg" width="24" height="24" alt="Relógio">

                <h1>Horário</h1>

            </div>

        </div>

        <!-- Grade semanal -->
        <div class="grade-semanal">

            <?php foreach ($dias_semana as $dia): ?>

                <div class="coluna-dia">

                    <h2 class="titulo-dia">
                        <?php echo htmlspecialchars($dia); ?>
                    </h2>

                    <div class="lista-horarios">

                        <?php if (count($horarios[$dia]) > 0): ?>

                            <?php foreach ($horarios[$dia] as $aula): ?>

                                <div class="card-horario">

                                    <div class="nome-disciplina">
                                        <?php echo htmlspecialchars($aula['disciplina']); ?>
                                    </div>

                                    <div class="horario-aula">
                                        <?php echo htmlspecialchars($aula['horario']); ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="card-horario vazio">

                                <div class="nome-disciplina">
                                    Nenhuma aula
                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </main>

</div>
<script src="js/sidebar.js"></script>
</body>
</html>