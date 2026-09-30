/**
 * Caderno - JavaScript
 * Controla as abas (Anotações | Arquivos | Tarefas | Eventos)
 * e os modais de anotações e arquivos.
 */

// =====================================================
// ABAS
// =====================================================

function abrirAba(nomeAba) {

    // Marca o botão da aba
    document.querySelectorAll('.aba').forEach(botao => {
        botao.classList.toggle('ativa', botao.dataset.aba === nomeAba);
    });

    // Mostra o painel correspondente
    document.querySelectorAll('.painel-aba').forEach(painel => {
        painel.classList.toggle('ativo', painel.id === 'painel-' + nomeAba);
    });

    // Mantém a aba na URL, para que F5 e o retorno dos CRUDs
    // caiam na mesma aba em que o usuário estava.
    const url = new URL(window.location.href);
    url.searchParams.set('aba', nomeAba);
    history.replaceState(null, '', url);
}

document.querySelectorAll('.aba').forEach(botao => {
    botao.addEventListener('click', function () {
        abrirAba(this.dataset.aba);
    });
});

// Abre a aba indicada pelo PHP (padrão: anotações)
abrirAba(typeof abaInicial !== 'undefined' ? abaInicial : 'anotacoes');

// =====================================================
// MODAIS
// =====================================================

function abrirModal(modalId) {
    document.getElementById(modalId).classList.add('ativo');
    document.getElementById('overlay').classList.add('ativo');
}

function fecharModal(modalId) {
    document.getElementById(modalId).classList.remove('ativo');
    document.getElementById('overlay').classList.remove('ativo');
}

function fecharTodosModais() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('ativo');
    });
    document.getElementById('overlay').classList.remove('ativo');
}

// =====================================================
// ANOTAÇÕES
// =====================================================

document.getElementById('botao-adicionar-anotacao').addEventListener('click', function () {
    document.getElementById('modal-adicionar-anotacao')
        .querySelector('form').reset();

    abrirModal('modal-adicionar-anotacao');
});

function abrirModalEditarAnotacao(id, titulo, conteudo) {
    document.getElementById('id-anotacao-editar').value = id;
    document.getElementById('titulo-anotacao-editar').value = titulo;
    document.getElementById('conteudo-anotacao-editar').value = conteudo;

    abrirModal('modal-editar-anotacao');
}

function abrirModalExcluirAnotacao(id, titulo) {
    document.getElementById('id-anotacao-excluir').value = id;
    document.getElementById('titulo-anotacao-excluir').textContent = titulo;

    abrirModal('modal-excluir-anotacao');
}

// =====================================================
// ARQUIVOS
// =====================================================

document.getElementById('botao-adicionar-arquivo').addEventListener('click', function () {
    document.getElementById('modal-adicionar-arquivo')
        .querySelector('form').reset();

    abrirModal('modal-adicionar-arquivo');
});

function abrirModalExcluirArquivo(id, nome) {
    document.getElementById('id-arquivo-excluir').value = id;
    document.getElementById('titulo-arquivo-excluir').textContent = nome;

    abrirModal('modal-excluir-arquivo');
}

// =====================================================
// TAREFAS E EVENTOS
// =====================================================
// Criados aqui já vinculados à disciplina, sem sair do caderno.

document.getElementById('botao-adicionar-tarefa').addEventListener('click', function () {
    document.getElementById('modal-adicionar-tarefa')
        .querySelector('form').reset();

    abrirModal('modal-adicionar-tarefa');
});

document.getElementById('botao-adicionar-evento').addEventListener('click', function () {
    document.getElementById('modal-adicionar-evento')
        .querySelector('form').reset();

    abrirModal('modal-adicionar-evento');
});

// =====================================================
// ATALHOS
// =====================================================

// Fechar modais ao pressionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        fecharTodosModais();
    }
});
