<?php
// SMTP via ambiente; sem valores default reais
if (!defined('SMTP_HOST'))        define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
if (!defined('SMTP_PORT'))        define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 0));
if (!defined('SMTP_USERNAME'))    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
if (!defined('SMTP_PASSWORD'))    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
if (!defined('SMTP_ENCRYPTION'))  define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: '');
if (!defined('SMTP_SEND_ONLY_IF_USER_EXISTS')) define('SMTP_SEND_ONLY_IF_USER_EXISTS', false);
if (!defined('SMTP_FROM'))        define('SMTP_FROM', getenv('SMTP_FROM') ?: '');
if (!defined('SMTP_FROM_NAME'))   define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: '');//
if (!defined('SMTP_AUTH'))        define('SMTP_AUTH', filter_var(getenv('SMTP_AUTH') ?: false, FILTER_VALIDATE_BOOLEAN));