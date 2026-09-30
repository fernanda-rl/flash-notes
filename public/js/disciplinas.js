/**
 * Disciplinas - JavaScript
 * Gerencia os modais e interações da página de disciplinas.
 *
 * Os dados vêm do array disciplinasPHP, montado em disciplinas.php,
 * então os modais são preenchidos sem nova consulta ao servidor.
 */

// Abrir modal de adicionar disciplina
document.getElementById('botao-adicionar-disciplina').addEventListener('click', function() {
    document.getElementById('modal-adicionar').querySelector('form').reset();
    abrirModal('modal-adicionar');
});

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

// Localiza uma disciplina pelo id
function buscarDisciplina(id) {
    return disciplinasPHP.find(disciplina => Number(disciplina.id) === Number(id));
}

// Função para abrir modal de editar
function abrirModalEditar(id) {

    const disciplina = buscarDisciplina(id);

    if (!disciplina) {
        return;
    }

    document.getElementById('id-disciplina-editar').value = disciplina.id;
    document.getElementById('nome-disciplina-editar').value = disciplina.nome;
    document.getElementById('professor-editar').value = disciplina.professor || '';
    document.getElementById('cor-disciplina-editar').value = disciplina.cor;

    document.getElementById('titulo-editar').textContent = disciplina.nome + ' - Editar';

    abrirModal('modal-editar');
}

// As aulas de cada disciplina são gerenciadas na aba "Aulas" do caderno
// (caderno.php), e não mais por um modal nesta tela.

// Função para abrir modal de excluir
function abrirModalExcluir(id) {

    const disciplina = buscarDisciplina(id);

    if (!disciplina) {
        return;
    }

    // Nome da disciplina
    document.getElementById('nome-disciplina-excluir').textContent = disciplina.nome;

    // Define o ID no input hidden
    document.getElementById('id-disciplina-excluir').value = disciplina.id;

    // Abre o modal
    abrirModal('modal-excluir');
}

// Pesquisa de disciplinas
document.getElementById('campo-pesquisa').addEventListener('input', function(e) {
    const termo = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.card-disciplina');

    cards.forEach(card => {
        const nome = card.querySelector('h3').textContent.toLowerCase();
        if (nome.includes(termo)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Fechar modais ao pressionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharTodosModais();
    }
});
