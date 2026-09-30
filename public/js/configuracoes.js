// ======================================
// TOGGLES DE NOTIFICAÇÃO
// ======================================

const toggles = document.querySelectorAll('.toggle-switch input');

toggles.forEach(toggle => {

    toggle.addEventListener('change', () => {

        const card = toggle.closest('.opcao-notificacao');

        card.classList.add('animando');

        setTimeout(() => {
            card.classList.remove('animando');
        }, 300);

    });

});


// ======================================
// PREVIEW DO TEMA
// ======================================

const temas = document.querySelectorAll('.opcao-tema');

temas.forEach(tema => {

    tema.addEventListener('click', () => {

        temas.forEach(t => {
            t.classList.remove('ativa');
        });

        tema.classList.add('ativa');

    });

});