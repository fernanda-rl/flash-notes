/**
 * Cadernos - JavaScript
 * Filtra a grade de cadernos pelo nome da disciplina.
 */

document.getElementById('campo-pesquisa').addEventListener('input', function (e) {

    const termo = e.target.value.toLowerCase();

    const cards = document.querySelectorAll('.card-caderno');

    cards.forEach(card => {

        const nome = card.querySelector('h3').textContent.toLowerCase();

        if (nome.includes(termo)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
});
