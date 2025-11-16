<?php
// Configuração de SMTP para envio de e-mails
// Ajuste os valores abaixo com as credenciais fornecidas.

// Definições explícitas do ambiente atual
define('SMTP_HOST', ''); // Host SMTP Brevo
define('SMTP_PORT', ); // Porta Brevo
define('SMTP_USERNAME', ''); // Usuário Brevo
define('SMTP_PASSWORD', ''); // Senha Brevo
define('SMTP_ENCRYPTION', ''); // 'tls' (porta 587) ou 'ssl' (porta 465)
// Enviar apenas se o e-mail existir na base? (true/false)
if (!defined('SMTP_SEND_ONLY_IF_USER_EXISTS')) define('SMTP_SEND_ONLY_IF_USER_EXISTS', false);
define('SMTP_FROM', '');
define('SMTP_FROM_NAME', '');
define('SMTP_AUTH', );

