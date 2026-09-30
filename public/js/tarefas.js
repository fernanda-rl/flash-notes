/**
 * Tarefas - JavaScript
 * Gerencia os modais e interações da página de tarefas
 */

let tarefaEmExclusao = null;

// Abrir modal de adicionar tarefa
document.getElementById('botao-adicionar-tarefa').addEventListener('click', function() {
    abrirModal('modal-adicionar');
    document.querySelector('.formulario-tarefa').reset();
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

// Função para abrir modal de editar
function abrirModalEditar(id, titulo, vencimento, prioridade, status, disciplinaId) {
    document.getElementById('id-tarefa-editar').value = id;
    document.getElementById('titulo-tarefa-editar').value = titulo;
    document.getElementById('vencimento-tarefa-editar').value = vencimento;
    document.getElementById('prioridade-tarefa-editar').value = prioridade;
    document.getElementById('status-tarefa-editar').value = status;

    // disciplinaId vem null quando a tarefa não está vinculada,
    // e '' é o valor da opção "Sem disciplina".
    document.getElementById('disciplina-tarefa-editar').value =
        disciplinaId === null ? '' : disciplinaId;

    abrirModal('modal-editar');
}

// Função para abrir modal de excluir
function abrirModalExcluir(id, titulo) {
    tarefaEmExclusao = id;
    document.getElementById('titulo-tarefa-excluir').textContent = titulo;
    document.getElementById('id-tarefa-excluir').value = id;
    abrirModal('modal-excluir');
}

// Função para confirmar exclusão
function confirmarExclusao() {
    if (tarefaEmExclusao !== null) {
        // Aqui você faria uma requisição AJAX para deletar a tarefa do banco de dados
        console.log('Deletando tarefa com ID:', tarefaEmExclusao);
        
        // Por enquanto, apenas removemos do DOM
        const card = document.querySelector(`[data-id="${tarefaEmExclusao}"]`);
        if (card) {
            card.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                card.remove();
                fecharModal('modal-excluir');
                tarefaEmExclusao = null;
            }, 300);
        }
    }
}

// Função para marcar como concluída
function marcarConcluida(id) {
    const card = document.querySelector(`[data-id="${id}"]`);
    if (card) {
        card.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            card.style.opacity = '0.5';
            card.style.textDecoration = 'line-through';
            // Aqui você faria uma requisição AJAX para marcar como concluída no banco de dados
            console.log('Tarefa marcada como concluída:', id);
        }, 300);
    }
}

// Função para salvar nova tarefa
function salvarTarefa(event) {
    event.preventDefault();
    
    const titulo = document.getElementById('titulo-tarefa').value;
    const vencimento = document.getElementById('vencimento-tarefa').value;
    const prioridade = document.getElementById('prioridade-tarefa').value;
    
    // Converter data para formato DD/MM/YY
    const data = new Date(vencimento);
    const dataFormatada = String(data.getDate()).padStart(2, '0') + '/' + 
                         String(data.getMonth() + 1).padStart(2, '0') + '/' + 
                         String(data.getFullYear()).slice(-2);
    
    // Mapa de cores por prioridade
    const coresPrioridade = {
        'alta': '#FF4444',
        'media': '#FFD700',
        'baixa': '#8B5CF6'
    };
    
    // Aqui você faria uma requisição AJAX para salvar a tarefa no banco de dados
    console.log('Salvando tarefa:', { titulo, vencimento, prioridade });
    
    // Por enquanto, apenas adicionamos ao DOM
    const novoCard = document.createElement('div');
    novoCard.className = 'card-tarefa';
    novoCard.setAttribute('data-id', '0');
    novoCard.setAttribute('data-prioridade', prioridade);
    novoCard.innerHTML = `
        <div class="indicador-prioridade" style="background-color: ${coresPrioridade[prioridade]};"></div>
        <div class="conteudo-tarefa">
            <h3>${titulo}</h3>
            <div class="info-tarefa">
                <p><strong>Vence:</strong> ${dataFormatada}</p>
                <p><strong>Prioridade:</strong> ${prioridade.charAt(0).toUpperCase() + prioridade.slice(1)}</p>
            </div>
        </div>
        <div class="acoes-tarefa">
            <button class="botao-concluir" onclick="marcarConcluida(0)" title="Marcar como concluído">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </button>
            <button class="botao-editar" onclick="abrirModalEditar(0, '${titulo}', '${vencimento}', '${prioridade}')">
                Editar
            </button>
            <button class="botao-deletar" onclick="abrirModalExcluir(0, '${titulo}')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </button>
        </div>
    `;
    
    document.getElementById('grade-tarefas').insertBefore(novoCard, document.getElementById('grade-tarefas').firstChild);
    fecharModal('modal-adicionar');
    document.querySelector('.formulario-tarefa').reset();
}

// Função para salvar edição
function salvarEdicao(event) {
    event.preventDefault();
    
    const id = document.getElementById('id-tarefa-editar').value;
    const titulo = document.getElementById('titulo-tarefa-editar').value;
    const vencimento = document.getElementById('vencimento-tarefa-editar').value;
    const prioridade = document.getElementById('prioridade-tarefa-editar').value;
    
    // Converter data para formato DD/MM/YY
    const data = new Date(vencimento);
    const dataFormatada = String(data.getDate()).padStart(2, '0') + '/' + 
                         String(data.getMonth() + 1).padStart(2, '0') + '/' + 
                         String(data.getFullYear()).slice(-2);
    
    // Mapa de cores por prioridade
    const coresPrioridade = {
        'alta': '#FF4444',
        'media': '#FFD700',
        'baixa': '#8B5CF6'
    };
    
    // Aqui você faria uma requisição AJAX para atualizar a tarefa no banco de dados
    console.log('Editando tarefa:', { id, titulo, vencimento, prioridade });
    
    // Por enquanto, apenas atualizamos o DOM
    const card = document.querySelector(`[data-id="${id}"]`);
    if (card) {
        card.setAttribute('data-prioridade', prioridade);
        card.innerHTML = `
            <div class="indicador-prioridade" style="background-color: ${coresPrioridade[prioridade]};"></div>
            <div class="conteudo-tarefa">
                <h3>${titulo}</h3>
                <div class="info-tarefa">
                    <p><strong>Vence:</strong> ${dataFormatada}</p>
                    <p><strong>Prioridade:</strong> ${prioridade.charAt(0).toUpperCase() + prioridade.slice(1)}</p>
                </div>
            </div>
            <div class="acoes-tarefa">
                <button class="botao-concluir" onclick="marcarConcluida(${id})" title="Marcar como concluído">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
                <button class="botao-editar" onclick="abrirModalEditar(${id}, '${titulo}', '${vencimento}', '${prioridade}')">
                    Editar
                </button>
                <button class="botao-deletar" onclick="abrirModalExcluir(${id}, '${titulo}')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            </div>
        `;
    }
    
    fecharModal('modal-editar');
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
