document.addEventListener('DOMContentLoaded', function () {

    // ======================================
    // MENU MOBILE
    // ======================================

    const botaoMenuMobile =
        document.getElementById('botao-menu-mobile');

    const sidebar =
        document.getElementById('sidebar');

    if (botaoMenuMobile && sidebar) {

        botaoMenuMobile.addEventListener('click', function () {

            sidebar.classList.toggle('ativo');

        });

    }

    // ======================================
    // MENU PERFIL
    // ======================================

    const botaoPerfil =
        document.getElementById('botao-perfil');

    const menuPerfil =
        document.getElementById('menu-perfil');

    // ======================================
    // MENU NOTIFICAÇÕES
    // ======================================

    const botaoNotificacao =
        document.getElementById('botao-notificacao');

    const menuNotificacoes =
        document.getElementById('menu-notificacoes');

    // ======================================
    // BOTÃO PERFIL
    // ======================================

    if (botaoPerfil && menuPerfil) {

        botaoPerfil.addEventListener('click', function (event) {

            event.stopPropagation();

            // FECHA NOTIFICAÇÕES
            if (menuNotificacoes) {
                menuNotificacoes.classList.remove('ativo');
            }

            menuPerfil.classList.toggle('ativo');

        });

        menuPerfil.addEventListener('click', function (event) {

            event.stopPropagation();

        });

    }

    // ======================================
    // BOTÃO NOTIFICAÇÕES
    // ======================================

    if (botaoNotificacao && menuNotificacoes) {

        botaoNotificacao.addEventListener('click', function (event) {

            event.stopPropagation();

            // FECHA PERFIL
            if (menuPerfil) {
                menuPerfil.classList.remove('ativo');
            }

            menuNotificacoes.classList.toggle('ativo');

        });

        menuNotificacoes.addEventListener('click', function (event) {

            event.stopPropagation();

        });

    }

    // ======================================
    // FECHAR AO CLICAR FORA
    // ======================================

    document.addEventListener('click', function () {

        if (menuPerfil) {
            menuPerfil.classList.remove('ativo');
        }

        if (menuNotificacoes) {
            menuNotificacoes.classList.remove('ativo');
        }

    });

});