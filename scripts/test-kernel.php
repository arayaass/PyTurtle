<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// Test-only HTTP adapter: run the real Laravel kernel without a listening socket.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$input = json_decode(stream_get_contents(STDIN), true);
$cookies = [];
foreach (explode(';', $input['headers']['cookie'] ?? '') as $cookie) {
    $pair = explode('=', trim($cookie), 2);
    if (count($pair) === 2) {
        $cookies[$pair[0]] = urldecode($pair[1]);
    }
}
$server = ['SCRIPT_NAME' => '/index.php', 'SCRIPT_FILENAME' => realpath(__DIR__.'/../public/index.php'), 'SERVER_PORT' => 8011, 'SERVER_NAME' => '127.0.0.1'];
foreach ($input['headers'] as $key => $val) {
    $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $val;
}
if (isset($input['headers']['content-type'])) {
    $server['CONTENT_TYPE'] = $input['headers']['content-type'];
}
$request = Request::create($input['url'], $input['method'], [], $cookies, [], $server, $input['body'] ?? '');
if (str_contains($input['headers']['content-type'] ?? '', 'application/x-www-form-urlencoded')) {
    parse_str($input['body'], $form);
    $request->request->replace($form);
}
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$out = ['status' => $response->getStatusCode(), 'headers' => $response->headers->all(), 'body' => base64_encode($response->getContent())];
$kernel->terminate($request, $response);
echo json_encode($out, JSON_THROW_ON_ERROR);
