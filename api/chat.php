<?php
// api/chat.php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido ou message ausente']);
    exit;
}

$message = trim($data['message']);
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem vazia']);
    exit;
}

$remote = 'https://newtonhack.serv00.net/api/gpt.php';
$url = $remote . '?' . http_build_query(['text' => $message]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_FAILONERROR, false);
$resp = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'cURL error: ' . $curl_err, 'http_code' => $http_code]);
    exit;
}

// tenta decodificar JSON; se não for, devolve como texto em 'reply'
$decoded = @json_decode($resp, true);
if (is_array($decoded)) {
    $reply = $decoded['answer'] ?? $decoded['response'] ?? $decoded['text'] ?? json_encode($decoded, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} else {
    $reply = trim($resp);
}

// resposta final para o frontend
echo json_encode(['ok' => true, 'reply' => $reply, 'http_code' => $http_code], JSON_UNESCAPED_UNICODE);