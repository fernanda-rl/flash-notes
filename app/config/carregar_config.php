<?php
/**
 * Carregador de Configuração - Flashnotes
 * =====================================================
 * Lê o arquivo `config.php` uma única vez e disponibiliza os
 * valores para o resto do sistema através da função config().
 *
 * Uso:
 *   require_once __DIR__ . '/../config/carregar_config.php';
 *   $senha = config('banco.senha');
 *   $porta = config('email.porta', 587);   // com valor padrão
 */

/**
 * Devolve todo o array de configuração, carregando do arquivo
 * na primeira chamada e reaproveitando depois.
 */
function configuracao_completa()
{
    // static: o arquivo é lido uma vez por requisição
    static $configuracao = null;

    if ($configuracao !== null) {
        return $configuracao;
    }

    $caminho = __DIR__ . '/config.php';

    if (!file_exists($caminho)) {
        // Mensagem de instalação: sem o config.php nada funciona
        die(
            'Arquivo de configuração não encontrado. ' .
            'Copie app/config/config.example.php para app/config/config.php ' .
            'e preencha os dados do banco e do e-mail.'
        );
    }

    $configuracao = require $caminho;

    return $configuracao;
}

/**
 * Lê um valor da configuração usando ponto como separador.
 *
 * @param string $caminho Ex.: 'banco.senha' ou 'email.host'
 * @param mixed  $padrao  Valor devolvido se a chave não existir
 * @return mixed
 */
function config($caminho, $padrao = null)
{
    $valor = configuracao_completa();

    foreach (explode('.', $caminho) as $parte) {

        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $padrao;
        }

        $valor = $valor[$parte];
    }

    return $valor;
}
?>
