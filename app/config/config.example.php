<?php
/**
 * Configuração do Flashnotes - MODELO
 * =====================================================
 * Este arquivo é um MODELO e vai para o controle de versão.
 * Ele NÃO deve conter senhas reais.
 *
 * Para configurar o sistema:
 *   1. Copie este arquivo para o mesmo diretório com o nome `config.php`
 *   2. Preencha os valores reais em `config.php`
 *   3. Nunca versione o `config.php` (ele está no .gitignore)
 *
 * No Windows (dentro de app/config):
 *   copy config.example.php config.php
 *
 * No Linux/Mac:
 *   cp config.example.php config.php
 */

return [

    // =====================================================
    // BANCO DE DADOS
    // =====================================================
    'banco' => [
        'host'    => 'localhost',
        'usuario' => 'flashuser',
        'senha'   => 'SUA_SENHA_DO_BANCO',
        'nome'    => 'flashnotes',
        'charset' => 'utf8mb4',
    ],

    // =====================================================
    // ENVIO DE E-MAIL (SMTP)
    // =====================================================
    // No Gmail é preciso usar uma "senha de app" (16 caracteres),
    // gerada em: Conta Google > Segurança > Senhas de app.
    // A senha normal da conta não funciona.
    'email' => [
        'host'       => 'smtp.gmail.com',
        'porta'      => 587,
        'usuario'    => 'seu-email@gmail.com',
        'senha'      => 'SUA_SENHA_DE_APP',
        'remetente'  => 'seu-email@gmail.com',
        'nome_envio' => 'Flashnotes',

        // Caixa que recebe as mensagens do formulário "Fale conosco"
        'contato'    => 'seu-email@gmail.com',
    ],

    // =====================================================
    // RECUPERAÇÃO DE SENHA
    // =====================================================
    'recuperacao_senha' => [
        // Minutos de validade do código enviado por e-mail
        'validade_minutos'  => 15,

        // Tentativas erradas antes de o código ser invalidado
        'maximo_tentativas' => 5,
    ],

    // =====================================================
    // LOGIN
    // =====================================================
    'login' => [
        // Tentativas erradas antes do bloqueio temporário
        'maximo_tentativas' => 5,

        // Minutos de bloqueio após estourar o limite
        'bloqueio_minutos'  => 15,
    ],
];
