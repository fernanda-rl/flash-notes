<?php
/**
 * Configuração do Flashnotes - VALORES REAIS
 * =====================================================
 * ESTE ARQUIVO NÃO VAI PARA O CONTROLE DE VERSÃO.
 * Ele está listado no .gitignore justamente por conter senhas.
 *
 * O modelo sem valores reais é o `config.example.php`.
 *
 * ATENÇÃO: a senha de app do Gmail que estava escrita direto no
 * código foi comprometida (ficou exposta em três arquivos) e deve
 * ser revogada no Google. Troque o valor de 'senha' abaixo pela
 * nova senha de app assim que gerá-la.
 */

return [

    // =====================================================
    // BANCO DE DADOS
    // =====================================================
    'banco' => [
        'host'    => 'localhost',
        'usuario' => 'flashuser',
        'senha'   => '1234',
        'nome'    => 'flashnotes',
        'charset' => 'utf8mb4',
    ],

    // =====================================================
    // ENVIO DE E-MAIL (SMTP)
    // =====================================================
    'email' => [
        'host'       => 'smtp.gmail.com',
        'porta'      => 587,
        'usuario'    => 'flashnotess@gmail.com',

        // TROQUE AQUI pela nova senha de app do Gmail
        'senha'      => 'COLOQUE_A_NOVA_SENHA_DE_APP',

        'remetente'  => 'flashnotess@gmail.com',
        'nome_envio' => 'Flashnotes',
        'contato'    => 'flashnotess@gmail.com',
    ],

    // =====================================================
    // RECUPERAÇÃO DE SENHA
    // =====================================================
    'recuperacao_senha' => [
        'validade_minutos'  => 15,
        'maximo_tentativas' => 5,
    ],

    // =====================================================
    // LOGIN
    // =====================================================
    'login' => [
        'maximo_tentativas' => 5,
        'bloqueio_minutos'  => 15,
    ],
];
