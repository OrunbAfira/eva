<?php
// Configuração de SMTP para envio de e-mails
// Ajuste os valores abaixo com as credenciais fornecidas.

// Definições explícitas do ambiente atual
define('SMTP_HOST', 'smtp-relay.brevo.com'); // Host SMTP Brevo
define('SMTP_PORT', 587); // Porta Brevo
define('SMTP_USERNAME', '9bb547001@smtp-brevo.com'); // Usuário Brevo
define('SMTP_PASSWORD', 'bOm5hATSY9Qr2gKF'); // Senha Brevo
define('SMTP_ENCRYPTION', 'tls'); // 'tls' (porta 587) ou 'ssl' (porta 465)
// Enviar apenas se o e-mail existir na base? (true/false)
if (!defined('SMTP_SEND_ONLY_IF_USER_EXISTS')) define('SMTP_SEND_ONLY_IF_USER_EXISTS', false);
define('SMTP_FROM', 'drailom256@gmail.com');
define('SMTP_FROM_NAME', 'EVA');
define('SMTP_AUTH', true);

