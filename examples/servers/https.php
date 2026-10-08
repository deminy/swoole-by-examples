#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to serve HTTPS (HTTP over SSL/TLS), and how a client verifies the server's certificate.
 *
 * To keep the example self-contained, it creates a self-signed certificate for host name "localhost" at runtime,
 * using PHP's OpenSSL extension, and stores it in a temporary folder. In production, use a certificate issued by a
 * certificate authority (e.g., Let's Encrypt) instead.
 *
 * Enabling SSL/TLS on a server takes two steps:
 *   1. Turn on SSL/TLS for the port. For the coroutine-style server used here, pass true as the third argument of
 *      the constructor. For \Swoole\Http\Server and other servers, add flag SWOOLE_SSL to the socket type, e.g.,
 *      `new Swoole\Http\Server('0.0.0.0', 443, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL)`.
 *   2. Set options "ssl_cert_file" and "ssl_key_file" to the certificate file and its private key file.
 *
 * The script then makes two HTTPS requests to the server, with peer verification turned on:
 *   1. The first client doesn't trust the self-signed certificate, so the TLS handshake fails and no request is sent.
 *   2. The second client trusts the certificate (option "ssl_cafile"), so the request succeeds.
 * For the first request, Swoole also prints a NOTICE explaining why the certificate was rejected.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/https.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// Create a private key and a self-signed certificate for host name "localhost", valid for one day.
$dir = sys_get_temp_dir() . '/swoole-https-example-' . getmypid();
if (!is_dir($dir)) {
    mkdir($dir, 0700, true);
}
$keyFile  = "{$dir}/server.key";
$certFile = "{$dir}/server.crt";
$key      = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
$csr      = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
$cert     = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']); // @phpstan-ignore argument.type
openssl_pkey_export_to_file($key, $keyFile);
openssl_x509_export_to_file($cert, $certFile); // @phpstan-ignore argument.type

run(function () use ($certFile, $keyFile): void {
    $server = new Server('127.0.0.1', 0, true); // Port 0 lets the operating system pick an unused port.
    $server->set(
        [
            Constant::OPTION_SSL_CERT_FILE => $certFile,
            Constant::OPTION_SSL_KEY_FILE  => $keyFile,
        ]
    );
    $server->handle('/', function (Request $request, Response $response): void {
        $response->end('Hello over HTTPS!');
    });
    go(function () use ($server): void {
        $server->start();
    });

    // Client #1: verifies the server's certificate against the system's trusted certificate authorities only. The
    // self-signed certificate isn't signed by any of them, so the TLS handshake fails.
    $client = new Client('127.0.0.1', $server->port, true);
    $client->set(
        [
            Constant::OPTION_SSL_VERIFY_PEER => true,
            // The host name the certificate must be issued for. The client connects by IP address, so without this
            // option, the host name in the certificate would not be checked at all.
            Constant::OPTION_SSL_HOST_NAME   => 'localhost',
        ]
    );
    $client->get('/');
    echo 'Client #1 (does not trust the certificate): ', $client->statusCode > 0 ? "HTTP {$client->statusCode}" : 'request failed - the TLS handshake was rejected', PHP_EOL;
    $client->close();

    // Client #2: also trusts the self-signed certificate, so the TLS handshake succeeds and the request is sent.
    $client = new Client('127.0.0.1', $server->port, true);
    $client->set(
        [
            Constant::OPTION_SSL_VERIFY_PEER => true,
            Constant::OPTION_SSL_HOST_NAME   => 'localhost',
            Constant::OPTION_SSL_CAFILE      => $certFile,
        ]
    );
    $client->get('/');
    echo 'Client #2 (trusts the certificate): ', "HTTP {$client->statusCode}, body: {$client->body}", PHP_EOL;
    $client->close();

    $server->shutdown();
});

unlink($certFile);
unlink($keyFile);
rmdir($dir);
