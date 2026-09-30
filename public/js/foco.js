/**
 * Foco - JavaScript
 * Cronômetro das sessões de estudo (Pomodoro)
 *
 * A contagem NÃO é feita somando o intervalo do setInterval. O que
 * vale é o relógio do sistema: ao iniciar (ou retomar) guardamos o
 * instante em que a etapa deve acabar e, a cada tique, o tempo
 * restante é a diferença até Date.now(). O setInterval só serve para
 * redesenhar a tela. Assim, quando o navegador segura os temporizadores
 * da aba em segundo plano — o que ele faz o tempo todo —, o cronômetro
 * não atrasa: ao voltar, ele já mostra o valor certo.
 *
 * Depende de tokenCsrf, preferenciasFoco e limitesFoco, definidos em
 * foco.php.
 */

// =====================================================
// ESTADO
// =====================================================

// Configuração em vigor no cronômetro. Começa com o que está salvo
// no banco e só muda quando o usuário salva outra.
const config = {
    foco: preferenciasFoco.foco_min,
    pausa: preferenciasFoco.pausa_min,
    pausaLonga: preferenciasFoco.pausa_longa_min,
    ciclosAtePausaLonga: preferenciasFoco.ciclos_ate_pausa_longa
};

const estado = {
    fase: 'foco',        // 'foco', 'pausa' ou 'pausa_longa'
    rodando: false,
    iniciada: false,     // houve um Iniciar que ainda não foi parado
    duracaoFase: 0,      // segundos que a etapa atual tem no total
    restante: 0,         // segundos que faltam (valor de referência)
    fimPrevisto: 0,      // instante em que a etapa acaba, em ms
    ciclos: 0,           // blocos de foco completados desde o Iniciar
    inicioBloco: null    // quando o bloco de FOCO atual começou
};

let intervalo = null;
let somLigado = true;
let audioContexto = null;

// Verdadeiro quando o usuário escolheu "Personalizado": só nesse modo
// os campos de foco e pausa ficam abertos para digitação.
let modoPersonalizado = false;

const TITULO_PADRAO = 'Flashnotes - Foco';

const rotulos = {
    foco: 'Foco',
    pausa: 'Pausa',
    pausa_longa: 'Pausa longa'
};

// =====================================================
// ELEMENTOS
// =====================================================

const elMostrador = document.getElementById('mostrador');
const elEtapa = document.getElementById('etapa-atual');
const elCiclos = document.getElementById('contador-ciclos');
const elAviso = document.getElementById('aviso-etapa');

const botaoIniciar = document.getElementById('botao-iniciar');
const botaoPausar = document.getElementById('botao-pausar');
const botaoRetomar = document.getElementById('botao-retomar');
const botaoParar = document.getElementById('botao-parar');
const botaoSom = document.getElementById('botao-som');

const campoFoco = document.getElementById('campo-foco');
const campoPausa = document.getElementById('campo-pausa');
const campoPausaLonga = document.getElementById('campo-pausa-longa');
const campoCiclos = document.getElementById('campo-ciclos');

const botaoPersonalizado = document.getElementById('botao-personalizado');
const botaoSalvarConfig = document.getElementById('botao-salvar-config');
const elErroConfig = document.getElementById('erro-configuracao');

const campoDisciplina = document.getElementById('campo-disciplina');
const campoTarefa = document.getElementById('campo-tarefa');

const listaSessoes = document.getElementById('lista-sessoes');

const painelCronometro = document.querySelector('.painel-cronometro');

// =====================================================
// FORMATAÇÃO
// =====================================================

function formatarRelogio(segundos) {

    const totais = Math.max(0, Math.round(segundos));

    const minutos = Math.floor(totais / 60);
    const resto = totais % 60;

    return String(minutos).padStart(2, '0') + ':' + String(resto).padStart(2, '0');
}

/** Data no formato que o MySQL espera, no fuso do próprio usuário. */
function formatarDataMySQL(data) {

    const doisDigitos = (numero) => String(numero).padStart(2, '0');

    return data.getFullYear() + '-' +
        doisDigitos(data.getMonth() + 1) + '-' +
        doisDigitos(data.getDate()) + ' ' +
        doisDigitos(data.getHours()) + ':' +
        doisDigitos(data.getMinutes()) + ':' +
        doisDigitos(data.getSeconds());
}

function duracaoDaFase(fase) {

    if (fase === 'pausa') {
        return config.pausa * 60;
    }

    if (fase === 'pausa_longa') {
        return config.pausaLonga * 60;
    }

    return config.foco * 60;
}

// =====================================================
// DESENHO DA TELA
// =====================================================

function segundosRestantes() {

    if (!estado.rodando) {
        return estado.restante;
    }

    // Ceil para que o mostrador só troque de número quando o segundo
    // realmente virar: com floor, iniciar 25:00 mostraria 24:59 de cara.
    return Math.max(0, Math.ceil((estado.fimPrevisto - Date.now()) / 1000));
}

function atualizarTela() {

    const restante = segundosRestantes();

    elMostrador.textContent = formatarRelogio(restante);

    elCiclos.textContent = 'Ciclos concluídos: ' + estado.ciclos;

    if (!estado.iniciada) {
        elEtapa.textContent = 'Pronto para começar';
        document.title = TITULO_PADRAO;
        return;
    }

    elEtapa.textContent = estado.rodando
        ? rotulos[estado.fase]
        : rotulos[estado.fase] + ' (pausado)';

    // O tempo restante no título da aba, para acompanhar de longe ou
    // com o Flashnotes em segundo plano.
    document.title = formatarRelogio(restante) + ' · ' + rotulos[estado.fase] +
        ' - Flashnotes';
}

function atualizarBotoes() {

    botaoIniciar.hidden = estado.iniciada;
    botaoPausar.hidden = !(estado.iniciada && estado.rodando);
    botaoRetomar.hidden = !(estado.iniciada && !estado.rodando);

    botaoParar.disabled = !estado.iniciada;

    // Trocar os tempos no meio de uma sessão bagunçaria a contagem e o
    // registro; os campos voltam a abrir quando o cronômetro para.
    const travar = estado.iniciada;

    campoPausaLonga.disabled = travar;
    campoCiclos.disabled = travar;
    botaoSalvarConfig.disabled = travar;

    document.querySelectorAll('.botao-preset').forEach(botao => {
        botao.disabled = travar;
    });

    // Os campos de foco e pausa só ficam abertos no modo Personalizado
    campoFoco.disabled = travar || !modoPersonalizado;
    campoPausa.disabled = travar || !modoPersonalizado;
}

function mostrarAviso(texto) {

    elAviso.textContent = texto;

    painelCronometro.classList.add('destacado');

    setTimeout(() => {
        painelCronometro.classList.remove('destacado');
    }, 3000);
}

// =====================================================
// AVISO SONORO
// =====================================================

/**
 * Três bipes curtos gerados na hora pela Web Audio API — evita
 * depender de um arquivo de áudio.
 *
 * O contexto só pode ser criado depois de um clique do usuário, por
 * isso ele nasce no primeiro Iniciar e não no carregamento da página.
 */
function tocarAviso() {

    if (!somLigado) {
        return;
    }

    try {

        if (audioContexto === null) {
            audioContexto = new (window.AudioContext || window.webkitAudioContext)();
        }

        if (audioContexto.state === 'suspended') {
            audioContexto.resume();
        }

        for (let i = 0; i < 3; i++) {

            const oscilador = audioContexto.createOscillator();
            const volume = audioContexto.createGain();

            const inicio = audioContexto.currentTime + i * 0.28;

            oscilador.frequency.value = 880;
            oscilador.type = 'sine';

            // Sobe e desce o volume: um bipe que corta seco estala.
            volume.gain.setValueAtTime(0.0001, inicio);
            volume.gain.exponentialRampToValueAtTime(0.25, inicio + 0.02);
            volume.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.2);

            oscilador.connect(volume);
            volume.connect(audioContexto.destination);

            oscilador.start(inicio);
            oscilador.stop(inicio + 0.22);
        }

    } catch (erro) {
        // Navegador sem Web Audio ou com o áudio bloqueado: o aviso
        // visual continua valendo, então não há o que fazer aqui.
    }
}

function aplicarEstadoDoSom() {

    botaoSom.textContent = somLigado
        ? '🔔 Aviso sonoro ligado'
        : '🔕 Aviso sonoro silenciado';

    botaoSom.setAttribute('aria-pressed', somLigado ? 'false' : 'true');
    botaoSom.classList.toggle('silenciado', !somLigado);
}

// =====================================================
// CRONÔMETRO
// =====================================================

function prepararFase(fase) {

    estado.fase = fase;
    estado.duracaoFase = duracaoDaFase(fase);
    estado.restante = estado.duracaoFase;
    estado.rodando = false;
}

function iniciarContagem() {

    estado.fimPrevisto = Date.now() + estado.restante * 1000;
    estado.rodando = true;

    if (estado.fase === 'foco' && estado.inicioBloco === null) {
        estado.inicioBloco = new Date();
    }

    if (intervalo === null) {
        // 250 ms: o relógio é conferido contra Date.now() de qualquer
        // forma, e um intervalo curto deixa a virada do segundo suave.
        intervalo = setInterval(tique, 250);
    }

    atualizarBotoes();
    atualizarTela();
}

function pararContagem() {

    if (intervalo !== null) {
        clearInterval(intervalo);
        intervalo = null;
    }

    estado.rodando = false;
}

function tique() {

    const restante = segundosRestantes();

    estado.restante = restante;

    atualizarTela();

    if (restante <= 0) {
        concluirEtapa();
    }
}

/**
 * Fim natural de uma etapa: grava a sessão quando o que terminou foi
 * um bloco de foco e emenda na etapa seguinte.
 */
function concluirEtapa() {

    pararContagem();

    if (estado.fase === 'foco') {

        // Ciclo inteiro: o tempo focado é a duração cheia da etapa.
        // O aviso fica por conta da troca de etapa, logo abaixo.
        registrarSessao(estado.duracaoFase, true, false);

        estado.ciclos += 1;
        estado.inicioBloco = null;

        const ehPausaLonga =
            (estado.ciclos % config.ciclosAtePausaLonga) === 0;

        prepararFase(ehPausaLonga ? 'pausa_longa' : 'pausa');

        mostrarAviso(ehPausaLonga
            ? 'Ciclo concluído! Hora da pausa longa.'
            : 'Ciclo concluído! Hora da pausa.');

    } else {

        prepararFase('foco');

        mostrarAviso('Pausa encerrada. De volta ao foco.');
    }

    tocarAviso();

    // A etapa seguinte começa sozinha, como no Pomodoro clássico; o
    // botão Pausar continua ali para quem quiser respirar mais.
    iniciarContagem();
}

function aoIniciar() {

    if (estado.iniciada) {
        return;
    }

    estado.iniciada = true;
    estado.ciclos = 0;
    estado.inicioBloco = null;

    elAviso.textContent = '';

    prepararFase('foco');
    iniciarContagem();
}

function aoPausar() {

    if (!estado.iniciada || !estado.rodando) {
        return;
    }

    // Congela o que falta ANTES de derrubar o intervalo: daqui em
    // diante estado.restante é a referência, e o retomar parte dele.
    estado.restante = segundosRestantes();

    pararContagem();

    atualizarBotoes();
    atualizarTela();
}

function aoRetomar() {

    if (!estado.iniciada || estado.rodando) {
        return;
    }

    iniciarContagem();
}

/**
 * Parar encerra a sessão inteira. Se o cronômetro estava num bloco de
 * foco, o que já tinha corrido é gravado como sessão interrompida —
 * o tempo passado em pausa não entra nessa conta, porque o cronômetro
 * fica congelado enquanto ela dura.
 */
function aoParar() {

    if (!estado.iniciada) {
        return;
    }

    const restante = segundosRestantes();

    pararContagem();

    // Limpa antes de registrar: a resposta do fetch chega depois e é
    // ela que deve ficar escrita aqui.
    elAviso.textContent = '';

    if (estado.fase === 'foco') {

        const focados = estado.duracaoFase - restante;

        if (focados > 0) {
            registrarSessao(focados, false, true);
        } else {
            // Parou no mesmo segundo em que iniciou.
            mostrarAviso('Sessão de menos de meio minuto: nada foi registrado.');
        }

    } else {
        // Parar durante uma pausa não gera sessão: não houve foco.
        mostrarAviso('Pausa encerrada. Nada a registrar.');
    }

    estado.iniciada = false;
    estado.ciclos = 0;
    estado.inicioBloco = null;

    prepararFase('foco');

    atualizarBotoes();
    atualizarTela();
}

// =====================================================
// REGISTRO DA SESSÃO
// =====================================================

/**
 * Manda a sessão para o banco e conta na tela o que aconteceu.
 *
 * `avisarNaTela` fica falso quando o bloco terminou sozinho: nesse
 * caso concluirEtapa() já escreveu o aviso da troca de etapa, e a
 * resposta do fetch chegaria depois, apagando aquela mensagem. Erro e
 * recusa são avisados de qualquer jeito — é justamente o silêncio
 * nesses dois casos que faz a lista parecer quebrada.
 */
function registrarSessao(segundosFocados, concluida, avisarNaTela) {

    const inicio = estado.inicioBloco || new Date();

    fetch('actions/crud_foco.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            token_csrf: tokenCsrf,
            acao: 'registrar_sessao',
            segundos_focados: Math.round(segundosFocados),
            data_inicio: formatarDataMySQL(inicio),
            concluida: concluida ? '1' : '0',
            disciplina_id: campoDisciplina.value,
            tarefa_id: campoTarefa.value
        })
    })
    .then(res => res.json())
    .then(resposta => {

        if (!resposta.sucesso) {
            mostrarAviso('Não foi possível registrar esta sessão.');
            return;
        }

        if (!resposta.registrada) {
            mostrarAviso('Sessão de menos de meio minuto: nada foi registrado.');
            return;
        }

        inserirSessaoNaLista(resposta.sessao);

        if (avisarNaTela) {
            mostrarAviso('Sessão de ' + resposta.sessao.duracao +
                ' registrada em Últimas sessões.');
        }
    })
    .catch(() => {
        mostrarAviso('Não foi possível registrar esta sessão.');
    });
}

/**
 * Coloca a sessão recém-gravada no topo da lista.
 *
 * A página não é recarregada de propósito: nesse momento o cronômetro
 * da etapa seguinte já está correndo, e um reload o perderia. Os
 * textos vêm do banco (nome de disciplina, título de tarefa), então
 * entram por textContent e nunca por innerHTML.
 */
function inserirSessaoNaLista(sessao) {

    const vazio = document.getElementById('sessoes-vazio');

    if (vazio) {
        vazio.remove();
    }

    const item = document.createElement('div');
    item.className = 'item-sessao';

    const duracao = document.createElement('div');
    duracao.className = 'duracao-sessao';
    duracao.textContent = sessao.duracao;

    const info = document.createElement('div');
    info.className = 'info-sessao';

    const data = document.createElement('p');
    data.className = 'data-sessao';
    data.textContent = sessao.data_formatada;

    const vinculo = document.createElement('p');
    vinculo.className = 'vinculo-sessao';

    const partes = [];

    if (sessao.disciplina) {
        partes.push(sessao.disciplina);
    }

    if (sessao.tarefa) {
        partes.push(sessao.tarefa);
    }

    vinculo.textContent = partes.length > 0 ? partes.join(' · ') : 'Sem vínculo';

    // A linha é cortada com "..." quando não cabe; o texto inteiro fica
    // na dica do mouse.
    vinculo.title = vinculo.textContent;

    info.appendChild(data);
    info.appendChild(vinculo);

    const selo = document.createElement('span');

    selo.className = 'selo-sessao ' +
        (sessao.concluida ? 'selo-concluida' : 'selo-interrompida');

    selo.textContent = sessao.concluida ? 'Ciclo completo' : 'Interrompida';

    item.appendChild(duracao);
    item.appendChild(info);
    item.appendChild(selo);

    listaSessoes.insertBefore(item, listaSessoes.firstChild);

    // A tela mostra as 10 mais recentes, igual à consulta do PHP.
    const itens = listaSessoes.querySelectorAll('.item-sessao');

    if (itens.length > 10) {
        itens[itens.length - 1].remove();
    }
}

// =====================================================
// CONFIGURAÇÃO DOS TEMPOS
// =====================================================

/** Lê um campo e devolve null quando o valor está fora da faixa. */
function lerCampo(campo, limite) {

    const valor = Number(campo.value);

    if (!Number.isInteger(valor) || valor < limite.min || valor > limite.max) {
        return null;
    }

    return valor;
}

function marcarPresetAtivo(botao) {

    document.querySelectorAll('.botao-preset').forEach(item => {
        item.classList.toggle('ativo', item === botao);
    });
}

/**
 * Descobre, ao abrir a tela, se a configuração salva é um dos presets.
 * Se não for, a tela já abre em Personalizado com os campos liberados.
 */
function marcarPresetInicial() {

    const presets = document.querySelectorAll('.botao-preset[data-foco]');

    for (const botao of presets) {

        if (Number(botao.dataset.foco) === config.foco &&
            Number(botao.dataset.pausa) === config.pausa) {

            modoPersonalizado = false;
            marcarPresetAtivo(botao);
            return;
        }
    }

    modoPersonalizado = true;
    marcarPresetAtivo(botaoPersonalizado);
}

function salvarConfiguracao() {

    const foco = lerCampo(campoFoco, limitesFoco.foco_min);
    const pausa = lerCampo(campoPausa, limitesFoco.pausa_min);
    const pausaLonga = lerCampo(campoPausaLonga, limitesFoco.pausa_longa_min);
    const ciclos = lerCampo(campoCiclos, limitesFoco.ciclos_ate_pausa_longa);

    if (foco === null || pausa === null || pausaLonga === null || ciclos === null) {

        elErroConfig.textContent =
            'Use números inteiros: foco de ' + limitesFoco.foco_min.min + ' a ' +
            limitesFoco.foco_min.max + ' min, pausa de ' + limitesFoco.pausa_min.min +
            ' a ' + limitesFoco.pausa_min.max + ' min, pausa longa de ' +
            limitesFoco.pausa_longa_min.min + ' a ' + limitesFoco.pausa_longa_min.max +
            ' min e de ' + limitesFoco.ciclos_ate_pausa_longa.min + ' a ' +
            limitesFoco.ciclos_ate_pausa_longa.max + ' ciclos.';

        elErroConfig.classList.remove('aviso-ok');
        return;
    }

    fetch('actions/crud_foco.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            token_csrf: tokenCsrf,
            acao: 'salvar_preferencias',
            foco_min: foco,
            pausa_min: pausa,
            pausa_longa_min: pausaLonga,
            ciclos_ate_pausa_longa: ciclos
        })
    })
    .then(res => res.json())
    .then(resposta => {

        if (!resposta.sucesso) {
            elErroConfig.textContent = 'Não foi possível salvar a configuração.';
            elErroConfig.classList.remove('aviso-ok');
            return;
        }

        // O servidor devolve os valores como ficaram gravados.
        config.foco = resposta.preferencias.foco_min;
        config.pausa = resposta.preferencias.pausa_min;
        config.pausaLonga = resposta.preferencias.pausa_longa_min;
        config.ciclosAtePausaLonga = resposta.preferencias.ciclos_ate_pausa_longa;

        campoFoco.value = config.foco;
        campoPausa.value = config.pausa;
        campoPausaLonga.value = config.pausaLonga;
        campoCiclos.value = config.ciclosAtePausaLonga;

        elErroConfig.textContent = 'Configuração salva.';
        elErroConfig.classList.add('aviso-ok');

        // Com o cronômetro parado, o mostrador passa a refletir o novo
        // tempo de foco na hora.
        if (!estado.iniciada) {
            prepararFase('foco');
            atualizarTela();
        }
    })
    .catch(() => {
        elErroConfig.textContent = 'Não foi possível salvar a configuração.';
        elErroConfig.classList.remove('aviso-ok');
    });
}

// =====================================================
// EVENTOS
// =====================================================

botaoIniciar.addEventListener('click', aoIniciar);
botaoPausar.addEventListener('click', aoPausar);
botaoRetomar.addEventListener('click', aoRetomar);
botaoParar.addEventListener('click', aoParar);

botaoSom.addEventListener('click', function () {

    somLigado = !somLigado;

    // Preferência de conveniência do aparelho, não do usuário no banco:
    // fica no localStorage deste navegador.
    try {
        localStorage.setItem('flashnotes_foco_som', somLigado ? '1' : '0');
    } catch (erro) {
        // Armazenamento bloqueado: a escolha vale só nesta visita.
    }

    aplicarEstadoDoSom();
});

document.querySelectorAll('.botao-preset[data-foco]').forEach(botao => {

    botao.addEventListener('click', function () {

        campoFoco.value = botao.dataset.foco;
        campoPausa.value = botao.dataset.pausa;

        modoPersonalizado = false;

        marcarPresetAtivo(botao);
        atualizarBotoes();

        salvarConfiguracao();
    });
});

botaoPersonalizado.addEventListener('click', function () {

    modoPersonalizado = true;

    marcarPresetAtivo(botaoPersonalizado);
    atualizarBotoes();

    campoFoco.focus();
});

botaoSalvarConfig.addEventListener('click', salvarConfiguracao);

// Escolher a disciplina encurta a lista de tarefas, deixando só as
// dela (e as que não têm disciplina nenhuma).
campoDisciplina.addEventListener('change', function () {

    const disciplina = campoDisciplina.value;

    Array.from(campoTarefa.options).forEach(opcao => {

        if (opcao.value === '') {
            return;
        }

        const combina = disciplina === '' ||
            opcao.dataset.disciplina === disciplina;

        // hidden esconde a opção nos navegadores que o respeitam;
        // disabled garante que ela não seja escolhida nos demais.
        opcao.hidden = !combina;
        opcao.disabled = !combina;

        if (!combina && campoTarefa.value === opcao.value) {
            campoTarefa.value = '';
        }
    });
});

// Ao voltar para a aba, redesenha na hora em vez de esperar o próximo
// tique — o navegador segura os temporizadores da aba escondida.
document.addEventListener('visibilitychange', function () {

    if (!document.hidden && estado.rodando) {
        tique();
    }
});

// Avisa que uma sessão está correndo antes de a aba ser fechada.
window.addEventListener('beforeunload', function (evento) {

    if (estado.iniciada) {
        evento.preventDefault();
        evento.returnValue = '';
    }
});

// =====================================================
// INÍCIO
// =====================================================

try {
    somLigado = localStorage.getItem('flashnotes_foco_som') !== '0';
} catch (erro) {
    somLigado = true;
}

aplicarEstadoDoSom();

marcarPresetInicial();

prepararFase('foco');

atualizarBotoes();
atualizarTela();
