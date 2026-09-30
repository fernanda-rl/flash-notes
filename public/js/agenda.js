/**
 * Agenda - JavaScript
 * Gerencia o calendário, modais e interações da página de agenda
 */

let eventoEmExclusao = null;
let mesAtual = new Date().getMonth();
let anoAtual = new Date().getFullYear();

const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

// Inicializar calendário ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    renderizarCalendario();
    
    // Botões de navegação do calendário
    document.getElementById('mes-anterior').addEventListener('click', function() {
        mesAtual--;
        if (mesAtual < 0) {
            mesAtual = 11;
            anoAtual--;
        }
        renderizarCalendario();
    });
    
    document.getElementById('mes-proximo').addEventListener('click', function() {
        mesAtual++;
        if (mesAtual > 11) {
            mesAtual = 0;
            anoAtual++;
        }
        renderizarCalendario();
    });
    
    // Botão de adicionar evento
    document.getElementById('botao-adicionar-evento').addEventListener('click', function() {
        abrirModal('modal-adicionar');
        document.querySelector('.formulario-evento').reset();
    });
});

// Função para renderizar o calendário
function renderizarCalendario() {

    const diasCalendario =
        document.getElementById('dias-calendario');

    const mesAnoElement =
        document.getElementById('mes-ano');

    mesAnoElement.textContent =
        meses[mesAtual] + ' ' + anoAtual;

    diasCalendario.innerHTML = '';

    const primeiroDia =
        new Date(anoAtual, mesAtual, 1).getDay();

    const diasNoMes =
        new Date(anoAtual, mesAtual + 1, 0).getDate();

    const diasMesAnterior =
        new Date(anoAtual, mesAtual, 0).getDate();

    // =========================
    // MÊS ANTERIOR
    // =========================

    for (let i = primeiroDia - 1; i >= 0; i--) {

        const dia = document.createElement('div');

        dia.className =
            'dia-calendario outro-mes';

        dia.textContent =
            diasMesAnterior - i;

        diasCalendario.appendChild(dia);
    }

    // =========================
    // MÊS ATUAL
    // =========================

    const hoje = new Date();

    for (let i = 1; i <= diasNoMes; i++) {

        const dia =
            document.createElement('div');

        dia.className =
            'dia-calendario';

        const dataAtual =
            `${anoAtual}-${String(mesAtual + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

        // NUMERO
        const numero =
            document.createElement('span');

        numero.textContent = i;

        dia.appendChild(numero);

        // HOJE
        if (
            i === hoje.getDate() &&
            mesAtual === hoje.getMonth() &&
            anoAtual === hoje.getFullYear()
        ) {
            dia.classList.add('hoje');
        }

        // =========================
        // FILTRAR EVENTOS
        // =========================

        const eventosDoDia =
            eventosPHP.filter(evento => {

                return evento.data === dataAtual;

            });

        // =========================
        // BOLINHAS
        // =========================

        if (eventosDoDia.length > 0) {

            const container =
                document.createElement('div');

            container.className =
                'container-bolinhas';

            eventosDoDia.forEach(evento => {

                const bolinha =
                    document.createElement('div');

                bolinha.className =
                    'bolinha-evento';

                let cor = '#8B5CF6';

                switch(evento.tipo) {

                    case 'prova':
                        cor = '#FF4444';
                        break;

                    case 'apresentacao':
                        cor = '#FF6B6B';
                        break;

                    case 'trabalho':
                        cor = '#FFD700';
                        break;

                    case 'reuniao':
                        cor = '#3B82F6';
                        break;
                }

                bolinha.style.backgroundColor = cor;

                container.appendChild(bolinha);

            });

            dia.appendChild(container);

            // CLICK
            dia.addEventListener('click', () => {

                abrirEventosDoDia(
                    dataAtual,
                    eventosDoDia
                );

            });

        }

        diasCalendario.appendChild(dia);
    }

    // =========================
    // PROXIMO MES
    // =========================

    const totalCelulas =
        diasCalendario.children.length;

    const restantes =
        42 - totalCelulas;

    for (let i = 1; i <= restantes; i++) {

        const dia =
            document.createElement('div');

        dia.className =
            'dia-calendario outro-mes';

        dia.textContent = i;

        diasCalendario.appendChild(dia);
    }
}

function abrirEventosDoDia(data, eventos) {

    const titulo =
        document.getElementById('titulo-dia-eventos');

    const lista =
        document.getElementById('lista-eventos-dia');

    titulo.textContent =
        `Eventos de ${data.split('-').reverse().join('/')}`;

    lista.innerHTML = '';

    eventos.forEach(evento => {

        let cor = '#8B5CF6';

        if (evento.tipo === 'prova') {
            cor = '#FF4444';
        }

        if (evento.tipo === 'apresentacao') {
            cor = '#FF6B6B';
        }

        if (evento.tipo === 'trabalho') {
            cor = '#FFD700';
        }

        if (evento.tipo === 'reuniao') {
            cor = '#3B82F6';
        }

        // O título e o tipo são texto digitado pelo usuário, então vão
        // por textContent e nunca por innerHTML.
        const item = document.createElement('div');
        item.className = 'evento-dia-popup';

        const marcador = document.createElement('div');
        marcador.className = 'cor-evento-popup';
        marcador.style.background = cor;

        const texto = document.createElement('div');

        const titulo = document.createElement('h3');
        titulo.textContent = evento.titulo;

        const tipo = document.createElement('p');
        tipo.textContent = evento.tipo;

        texto.appendChild(titulo);
        texto.appendChild(tipo);

        item.appendChild(marcador);
        item.appendChild(texto);

        lista.appendChild(item);
    });

    abrirModal('modal-eventos-dia');
}

// Função para selecionar um dia
function selecionarDia(elemento) {
    // Remover seleção anterior
    document.querySelectorAll('.dia-calendario.selecionado').forEach(el => {
        el.classList.remove('selecionado');
    });
    
    // Adicionar seleção ao dia clicado
    if (!elemento.classList.contains('outro-mes')) {
        elemento.classList.add('selecionado');
    }
}

// Função para abrir modal
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('overlay');
    
    modal.classList.add('ativo');
    overlay.classList.add('ativo');
}

// Função para fechar modal
function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('overlay');
    
    modal.classList.remove('ativo');
    overlay.classList.remove('ativo');
}

// Função para fechar todos os modais
function fecharTodosModais() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('ativo');
    });
    document.getElementById('overlay').classList.remove('ativo');
}

// Função para abrir modal de editar
function abrirModalEditar(id, titulo, data, tipo, disciplinaId) {
    document.getElementById('id-evento-editar').value = id;
    document.getElementById('titulo-evento-editar').value = titulo;
    document.getElementById('data-evento-editar').value = data;
    document.getElementById('tipo-evento-editar').value = tipo;

    // disciplinaId vem null quando o evento não está vinculado,
    // e '' é o valor da opção "Sem disciplina".
    document.getElementById('disciplina-evento-editar').value =
        (disciplinaId === null || disciplinaId === undefined) ? '' : disciplinaId;

    abrirModal('modal-editar');

}

// Função para abrir modal de excluir
function abrirModalExcluir(id, titulo) {
    eventoEmExclusao = id;
    document.getElementById('titulo-evento-excluir').textContent = titulo;
    
    abrirModal('modal-excluir');
}

// Função para confirmar exclusão
function confirmarExclusao() {
    fetch('actions/crud_agenda.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            token_csrf: tokenCsrf,
            acao: 'excluir',
            id: eventoEmExclusao
        })
    })
    .then(res => res.json())
    .then(resposta => {
        if (resposta.sucesso) {
            location.reload();
        } else {
            alert('Erro ao excluir.');
        }
    });
}

// Função para salvar novo evento
function salvarEvento(event) {
    event.preventDefault();
    const titulo = document.getElementById('titulo-evento').value;
    const data = document.getElementById('data-evento').value;
    const tipo = document.getElementById('tipo-evento').value;
    const disciplinaId = document.getElementById('disciplina-evento').value;
    fetch('actions/crud_agenda.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            token_csrf: tokenCsrf,
            acao: 'adicionar',
            titulo: titulo,
            data: data,
            tipo: tipo,
            disciplina_id: disciplinaId
        })
    })
    .then(res => res.json())
    .then(resposta => {
        if (resposta.sucesso) {
            location.reload();
        } else {
            alert('Erro ao salvar evento.');
        }
    });
}

// Função para salvar edição
function salvarEdicao(event) {
    event.preventDefault();
    const id = document.getElementById('id-evento-editar').value;
    const titulo =
        document.getElementById('titulo-evento-editar').value;
    const data =
        document.getElementById('data-evento-editar').value;
    const tipo =
        document.getElementById('tipo-evento-editar').value;
    const disciplinaId =
        document.getElementById('disciplina-evento-editar').value;
    fetch('actions/crud_agenda.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            token_csrf: tokenCsrf,
            acao: 'editar',
            id: id,
            titulo: titulo,
            data: data,
            tipo: tipo,
            disciplina_id: disciplinaId
        })
    })
    .then(res => res.json())
    .then(resposta => {
        if (resposta.sucesso) {

            location.reload();
        } else {
            alert('Erro ao editar.');

        }
    });
}

// Fechar modais ao pressionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharTodosModais();
    }
});

// Animação de fade out
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);
