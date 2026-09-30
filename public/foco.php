<?php
/**
 * Foco - Flashnotes
 * Sessões de estudo com cronômetro (Pomodoro)
 *
 * A tela é servida pelo PHP, mas o cronômetro vive no JavaScript
 * (public/js/foco.js). O servidor entra em dois momentos: ao abrir,
 * entregando a última configuração salva, e depois pelo fetch() da
 * action actions/crud_foco.php, que grava as preferências e as
 * sessões encerradas.
 */

// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// =====================================================
// CONEXÃO COM BANCO
// =====================================================

require_once __DIR__ . '/../app/config/conexao.php';

require_once __DIR__ . '/../app/helpers/disciplinas.php';
require_once __DIR__ . '/../app/helpers/foco.php';

// Usuário logado
$usuario_id = $_SESSION['usuario_id'];

// Disciplinas e tarefas disponíveis para vincular à sessão
$disciplinas = buscar_disciplinas_usuario($conn, $usuario_id);

$tarefas = buscar_tarefas_para_foco($conn, $usuario_id);

// Última configuração salva (ou o Pomodoro clássico, para quem
// nunca salvou nenhuma)
$preferencias = buscar_preferencias_foco($conn, $usuario_id);

// Histórico recente
$sessoes = buscar_sessoes_foco($conn, $usuario_id, 10);

// Faixas aceitas nos campos: as mesmas do servidor, para que o
// navegador recuse antes de enviar aquilo que o PHP recusaria depois.
$limites = limites_foco();

// A conexão NÃO é fechada aqui: sidebar.php a reaproveita para
// montar as notificações. O PHP a encerra ao fim do script.

require_once __DIR__ . '/tema.php';
?>
<!DOCTYPE html>
<html lang="pt-br"<?php echo $atributo_tema; ?>>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Flashnotes - Foco</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/foco.css">

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

        <!-- Cabeçalho -->
        <div class="cabecalho-foco">

            <div class="titulo-foco">

                <img src="icons/cronometro.svg"
                     width="24"
                     height="24"
                     alt="Cronômetro">

                <h1>Foco</h1>

            </div>

        </div>

        <div class="grade-foco">

                <!-- ===================================================== -->
                <!-- CRONÔMETRO -->
                <!-- ===================================================== -->

                <section class="painel-foco painel-cronometro">

                    <p class="etapa-atual" id="etapa-atual">
                        Pronto para começar
                    </p>

                    <p class="mostrador" id="mostrador">
                        25:00
                    </p>

                    <p class="contador-ciclos" id="contador-ciclos">
                        Ciclos concluídos: 0
                    </p>

                    <!-- Notificação visual do fim de cada etapa. Fica em
                         role="status" para que o leitor de tela também
                         anuncie a troca. -->
                    <p class="aviso-etapa" id="aviso-etapa" role="status"></p>

                    <div class="botoes-cronometro">

                        <button type="button"
                                class="botao-foco botao-iniciar"
                                id="botao-iniciar">

                            Iniciar

                        </button>

                        <button type="button"
                                class="botao-foco botao-pausar"
                                id="botao-pausar"
                                hidden>

                            Pausar

                        </button>

                        <button type="button"
                                class="botao-foco botao-retomar"
                                id="botao-retomar"
                                hidden>

                            Retomar

                        </button>

                        <button type="button"
                                class="botao-foco botao-parar"
                                id="botao-parar"
                                disabled>

                            Parar

                        </button>

                    </div>

                    <button type="button"
                            class="botao-som"
                            id="botao-som"
                            aria-pressed="false">

                        🔔 Aviso sonoro ligado

                    </button>

                </section>

                <!-- ===================================================== -->
                <!-- CONFIGURAÇÃO DOS TEMPOS -->
                <!-- ===================================================== -->

                <section class="painel-foco">

                    <h2>Tempos</h2>

                    <div class="presets" id="presets">

                        <button type="button"
                                class="botao-preset"
                                data-foco="25"
                                data-pausa="5">

                            25/5

                        </button>

                        <button type="button"
                                class="botao-preset"
                                data-foco="50"
                                data-pausa="10">

                            50/10

                        </button>

                        <button type="button"
                                class="botao-preset"
                                data-foco="15"
                                data-pausa="3">

                            15/3

                        </button>

                        <button type="button"
                                class="botao-preset"
                                id="botao-personalizado"
                                data-personalizado="1">

                            Personalizado

                        </button>

                    </div>

                    <div class="campos-tempo">

                        <div class="grupo-campo">

                            <label for="campo-foco">
                                Foco (min)
                            </label>

                            <input type="number"
                                   id="campo-foco"
                                   min="<?php echo (int) $limites['foco_min']['min']; ?>"
                                   max="<?php echo (int) $limites['foco_min']['max']; ?>"
                                   step="1"
                                   value="<?php echo (int) $preferencias['foco_min']; ?>"
                                   disabled>

                        </div>

                        <div class="grupo-campo">

                            <label for="campo-pausa">
                                Pausa (min)
                            </label>

                            <input type="number"
                                   id="campo-pausa"
                                   min="<?php echo (int) $limites['pausa_min']['min']; ?>"
                                   max="<?php echo (int) $limites['pausa_min']['max']; ?>"
                                   step="1"
                                   value="<?php echo (int) $preferencias['pausa_min']; ?>"
                                   disabled>

                        </div>

                        <div class="grupo-campo">

                            <label for="campo-pausa-longa">
                                Pausa longa
                            </label>

                            <input type="number"
                                   id="campo-pausa-longa"
                                   min="<?php echo (int) $limites['pausa_longa_min']['min']; ?>"
                                   max="<?php echo (int) $limites['pausa_longa_min']['max']; ?>"
                                   step="1"
                                   value="<?php echo (int) $preferencias['pausa_longa_min']; ?>">

                        </div>

                        <div class="grupo-campo">

                            <label for="campo-ciclos">
                                Ciclos
                            </label>

                            <input type="number"
                                   id="campo-ciclos"
                                   min="<?php echo (int) $limites['ciclos_ate_pausa_longa']['min']; ?>"
                                   max="<?php echo (int) $limites['ciclos_ate_pausa_longa']['max']; ?>"
                                   step="1"
                                   value="<?php echo (int) $preferencias['ciclos_ate_pausa_longa']; ?>">

                        </div>

                    </div>

                    <p class="aviso-configuracao" id="erro-configuracao" role="alert"></p>

                    <button type="button"
                            class="botao-salvar-foco"
                            id="botao-salvar-config">

                        Salvar configuração

                    </button>

                    <p class="ajuda-foco" id="ajuda-configuracao">
                        Tempos em minutos; pausa longa a cada N ciclos.
                        A configuração fica guardada.
                    </p>

                </section>

                <!-- ===================================================== -->
                <!-- VÍNCULO COM DISCIPLINA E TAREFA -->
                <!-- ===================================================== -->

                <section class="painel-foco">

                    <h2>Estudando</h2>

                    <div class="grupo-campo">

                        <label for="campo-disciplina">
                            Disciplina (opcional)
                        </label>

                        <select id="campo-disciplina">

                            <option value="">Sem disciplina</option>

                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?php echo (int) $disciplina['id']; ?>">
                                    <?php echo htmlspecialchars($disciplina['nome']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="grupo-campo">

                        <label for="campo-tarefa">
                            Tarefa (opcional)
                        </label>

                        <!-- data-disciplina permite ao foco.js esconder as
                             tarefas de outras disciplinas quando o usuário
                             escolhe uma. -->
                        <select id="campo-tarefa">

                            <option value="" data-disciplina="">Sem tarefa</option>

                            <?php foreach ($tarefas as $tarefa): ?>
                                <option value="<?php echo (int) $tarefa['id']; ?>"
                                        data-disciplina="<?php echo $tarefa['disciplina_id'] !== null ? (int) $tarefa['disciplina_id'] : ''; ?>">
                                    <?php echo htmlspecialchars($tarefa['titulo']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </div>

                </section>

                <!-- ===================================================== -->
                <!-- ÚLTIMAS SESSÕES -->
                <!-- ===================================================== -->

                <section class="painel-foco painel-historico">

                    <h2>Últimas sessões</h2>

                    <p class="ajuda-foco ajuda-historico">
                        Blocos de foco concluídos ou parados no meio.
                    </p>

                    <div class="lista-sessoes" id="lista-sessoes">

                        <?php foreach ($sessoes as $sessao): ?>

                            <div class="item-sessao">

                                <div class="duracao-sessao">
                                    <?php echo htmlspecialchars(
                                        formatar_duracao_foco($sessao['duracao_minutos'])
                                    ); ?>
                                </div>

                                <div class="info-sessao">

                                    <p class="data-sessao">
                                        <?php echo date('d/m/Y H:i', strtotime($sessao['data_inicio'])); ?>
                                    </p>

                                    <?php
                                        // A linha do vínculo é cortada com "..."
                                        // quando não cabe, então o texto inteiro
                                        // vai também no title.
                                        $partes = [];

                                        if (!empty($sessao['disciplina'])) {
                                            $partes[] = $sessao['disciplina'];
                                        }

                                        if (!empty($sessao['tarefa'])) {
                                            $partes[] = $sessao['tarefa'];
                                        }

                                        $vinculo = $partes
                                            ? implode(' · ', $partes)
                                            : 'Sem vínculo';
                                    ?>

                                    <p class="vinculo-sessao"
                                       title="<?php echo htmlspecialchars($vinculo); ?>">
                                        <?php echo htmlspecialchars($vinculo); ?>
                                    </p>

                                </div>

                                <span class="selo-sessao <?php echo $sessao['concluida'] ? 'selo-concluida' : 'selo-interrompida'; ?>">
                                    <?php echo $sessao['concluida'] ? 'Ciclo completo' : 'Interrompida'; ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                        <?php if (count($sessoes) === 0): ?>
                            <p class="lista-vazia" id="sessoes-vazio">
                                Nenhuma sessão registrada ainda.
                            </p>
                        <?php endif; ?>

                    </div>

                </section>

        </div>

    </main>

</div>

<script>
// Token CSRF enviado junto de cada requisição do foco.js
const tokenCsrf = <?php echo json_encode(token_csrf()); ?>;

// Última configuração salva e as faixas aceitas em cada campo
const preferenciasFoco = <?php echo json_encode($preferencias); ?>;

const limitesFoco = <?php echo json_encode($limites); ?>;
</script>

<script src="js/foco.js"></script>
<script src="js/sidebar.js"></script>
</body>
</html>
