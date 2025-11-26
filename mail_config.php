<?php
// SMTP config (env vars override defaults)

// Host/port
if (!defined('SMTP_HOST'))        define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com');
if (!defined('SMTP_PORT'))        define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));

// Credentials
if (!defined('SMTP_USERNAME'))    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
if (!defined('SMTP_PASSWORD'))    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');

// Encryption: 'tls' (587) or 'ssl' (465)
if (!defined('SMTP_ENCRYPTION'))  define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

// Policy
if (!defined('SMTP_SEND_ONLY_IF_USER_EXISTS')) define('SMTP_SEND_ONLY_IF_USER_EXISTS', false);

// Sender
if (!defined('SMTP_FROM'))        define('SMTP_FROM', getenv('SMTP_FROM') ?: '');
if (!defined('SMTP_FROM_NAME'))   define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'EVA');

// Auth
if (!defined('SMTP_AUTH'))        define('SMTP_AUTH', filter_var(getenv('SMTP_AUTH') ?: true, FILTER_VALIDATE_BOOLEAN));
