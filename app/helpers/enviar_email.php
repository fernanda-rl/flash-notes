<?php
/**
 * Envio de E-mail - Flashnotes
 * =====================================================
 * Ponto ÚNICO de envio de e-mail do sistema. Nenhuma página
 * deve montar o PHPMailer por conta própria.
 *
 * As credenciais de SMTP vêm de app/config/config.php, que não
 * é versionado.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/carregar_config.php';

/**
 * Envia um e-mail em HTML.
 *
 * @param string      $destino        Para quem enviar
 * @param string      $assunto        Assunto da mensagem
 * @param string      $mensagemHTML   Corpo em HTML
 * @param array       $opcoes         Chaves aceitas:
 *                                    - 'texto_alternativo': versão em texto puro
 *                                    - 'responder_para':    e-mail do Reply-To
 *                                    - 'responder_nome':    nome do Reply-To
 * @return bool true se o envio foi aceito pelo servidor SMTP
 */
function enviarEmail($destino, $assunto, $mensagemHTML, $opcoes = [])
{
    $mail = new PHPMailer(true);

    try {

        // =====================================================
        // CONFIGURAÇÃO SMTP (vinda do config.php)
        // =====================================================
        $mail->isSMTP();

        $mail->Host = config('email.host');

        $mail->SMTPAuth = true;

        $mail->Username = config('email.usuario');

        $mail->Password = config('email.senha');

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = config('email.porta', 587);

        $mail->CharSet = 'UTF-8';

        // =====================================================
        // REMETENTE E DESTINATÁRIO
        // =====================================================
        $mail->setFrom(
            config('email.remetente'),
            config('email.nome_envio', 'Flashnotes')
        );

        $mail->addAddress($destino);

        // Responder-para, usado pelo formulário "Fale conosco" para
        // que a resposta vá direto a quem escreveu.
        if (!empty($opcoes['responder_para'])) {
            $mail->addReplyTo(
                $opcoes['responder_para'],
                $opcoes['responder_nome'] ?? ''
            );
        }

        // =====================================================
        // CONTEÚDO
        // =====================================================
        $mail->isHTML(true);

        $mail->Subject = $assunto;

        $mail->Body = $mensagemHTML;

        if (!empty($opcoes['texto_alternativo'])) {
            $mail->AltBody = $opcoes['texto_alternativo'];
        }

        $mail->send();

        return true;

    } catch (Exception $e) {

        // O erro detalhado não é devolvido para a tela: expor o
        // ErrorInfo revelaria host, usuário e detalhes do SMTP.
        error_log('Falha ao enviar e-mail: ' . $mail->ErrorInfo);

        return false;
    }
}
?>
