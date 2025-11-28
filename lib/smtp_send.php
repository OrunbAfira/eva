<?php
// Envio SMTP mínimo com STARTTLS e AUTH LOGIN, com log opcional

function smtp_send($to, $subject, $html, $from, $fromName, $host, $port, $username, $password, $encryption = 'tls', $timeout = 30)
{
    $crlf = "\r\n";
    $debugDir = __DIR__ . '/../assets/debug';
    if (!is_dir($debugDir)) {@mkdir($debugDir, 0777, true);}    
    $logFile = $debugDir . '/smtp_log.txt';
    $log = function($msg) use ($logFile) {
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s P') . "] " . $msg . "\n", FILE_APPEND);
    };

    $log("--- Nova tentativa: host=$host port=$port enc=$encryption ---");
    $contextOptions = ['ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'crypto_method' => STREAM_CRYPTO_METHOD_ANY_CLIENT
    ]];

    $errno = 0; $errstr = '';
    $try = function($host, $port, $encryption) use ($contextOptions, $timeout, &$errno, &$errstr) {
        $transport = ($encryption === 'ssl') ? 'ssl://' : 'tcp://';
        return @stream_socket_client($transport . $host . ':' . (int)$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, stream_context_create($contextOptions));
    };

    $fp = $try($host, $port, $encryption);
    if (!$fp) {
        $log("Falha de conexão: $errstr ($errno)");
        // Fallback automático entre TLS 587 e SSL 465
        if ($encryption === 'ssl') {
            $log('Tentando fallback: TLS na porta 587');
            $encryption = 'tls';
            $port = 587;
            $fp = $try($host, $port, $encryption);
        } else {
            $log('Tentando fallback: SSL na porta 465');
            $encryption = 'ssl';
            $port = 465;
            $fp = $try($host, $port, $encryption);
        }
        if (!$fp) {
            $log("Fallback também falhou: $errstr ($errno)");
            return false;
        }
    }
    stream_set_timeout($fp, $timeout);

    $read = function() use ($fp, $log) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && ($line[3] === ' ')) break;
        }
        $log('S: ' . trim($data));
        return $data;
    };
    $expect = function($codes) use ($read) {
        $resp = $read();
        if (!$resp) return false;
        $code = (int)substr($resp, 0, 3);
        return in_array($code, (array)$codes, true);
    };
    $uEnc = base64_encode($username);
    $pEnc = base64_encode($password);
    $send = function($cmd) use ($fp, $crlf, $log, $uEnc, $pEnc) {
        $logCmd = $cmd;
        if ($cmd === $uEnc) { $logCmd = '[USERNAME ENCODED HIDDEN]'; }
        if ($cmd === $pEnc) { $logCmd = '[PASSWORD ENCODED HIDDEN]'; }
        $log('C: ' . $logCmd);
        return fwrite($fp, $cmd . $crlf) !== false;
    };

    if (!$expect([220])) { fclose($fp); $log('Erro: sem 220 na abertura'); return false; }
    if (!$send('EHLO localhost')) { fclose($fp); $log('Erro: EHLO envio'); return false; }
    if (!$expect([250])) { fclose($fp); $log('Erro: EHLO resposta'); return false; }

    if ($encryption === 'tls') {
        if (!$send('STARTTLS') || !$expect([220])) { fclose($fp); $log('Erro: STARTTLS'); return false; }
        $ok = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : 0) | (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT : 0));
        if (!$ok) { fclose($fp); $log('Erro: enable_crypto'); return false; }
        if (!$send('EHLO localhost') || !$expect([250])) { fclose($fp); $log('Erro: EHLO pós-TLS'); return false; }
    }

    // AUTH LOGIN
    if (!$send('AUTH LOGIN') || !$expect([334])) { fclose($fp); $log('Erro: AUTH LOGIN'); return false; }
    if (!$send($uEnc) || !$expect([334])) { fclose($fp); $log('Erro: USERNAME'); return false; }
    if (!$send($pEnc) || !$expect([235])) {
        $log('Falha primária de autenticação (LOGIN). Tentando fallback Brevo com username "apikey"...');
        if ($send('AUTH LOGIN') && $expect([334])) {
            $altUserEnc = base64_encode('apikey');
            if ($send($altUserEnc) && $expect([334]) && $send($pEnc) && $expect([235])) {
                $log('Fallback Brevo (apikey) bem-sucedido.');
            } else {
                fclose($fp);
                $log('Erro: PASSWORD (após fallback apikey)');
                return false;
            }
        } else {
            fclose($fp);
            $log('Erro: AUTH LOGIN (falha também no fallback apikey)');
            return false;
        }
    }

    // MAIL FROM / RCPT TO
    $fromAddr = $from ?: $username;
    if (!$send('MAIL FROM:<' . $fromAddr . '>') || !$expect([250])) { fclose($fp); $log('Erro: MAIL FROM'); return false; }
    if (!$send('RCPT TO:<' . $to . '>') || !$expect([250,251])) { fclose($fp); $log('Erro: RCPT TO'); return false; }

    // DATA
    if (!$send('DATA') || !$expect([354])) { fclose($fp); $log('Erro: DATA'); return false; }

    // Headers + body
    $headers = [];
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'From: ' . ($fromName ? ('=?UTF-8?B?' . base64_encode($fromName) . '?=') : '') . ' <' . $fromAddr . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $data = implode($crlf, $headers) . $crlf . $crlf . $html . $crlf . '.';
    $log('C: [MIME DATA + BODY]');
    if (fwrite($fp, $data . $crlf) === false || !$expect([250])) { fclose($fp); $log('Erro: envio BODY/terminador'); return false; }

    $send('QUIT');
    fclose($fp);
    $log('OK: enviado com sucesso');
    return true;
}

?>