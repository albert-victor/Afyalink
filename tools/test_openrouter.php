<?php
require __DIR__ . '/../config/env.php';
$config = require __DIR__ . '/../config/config.php';
$key = $config['openrouter']['api_key'];

// List free models
$ch = curl_init('https://openrouter.ai/api/v1/models');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
    CURLOPT_TIMEOUT        => 30,
]);
$r = curl_exec($ch);
curl_close($ch);
$data = json_decode($r, true);

echo "=== Free models with :free suffix ===\n";
$free = [];
foreach ($data['data'] ?? [] as $m) {
    if (str_ends_with($m['id'], ':free')) {
        $free[] = $m['id'];
    }
}
sort($free);
foreach ($free as $id) {
    echo "  {$id}\n";
}

echo "\n=== Testing openrouter/free router ===\n";
$modelsToTest = array_merge(['openrouter/free'], array_slice($free, 0, 5));

foreach ($modelsToTest as $model) {
    $payload = json_encode([
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => 'Reply with one word: OK']],
        'max_tokens' => 10,
    ]);

    $ch = curl_init($config['openrouter']['base_url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
            'HTTP-Referer: http://localhost:8080',
            'X-Title: AfyaLink Tanzania',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT    => 60,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = $code === 200 ? 'OK' : 'FAIL';
    echo "{$status} HTTP {$code} – {$model}\n";
    if ($code === 200) {
        $d = json_decode($response, true);
        echo '  Reply: ' . ($d['choices'][0]['message']['content'] ?? '?') . "\n";
        echo '  Used: ' . ($d['model'] ?? '?') . "\n";
    } else {
        $d = json_decode($response, true);
        echo '  ' . ($d['error']['message'] ?? substr($response, 0, 150)) . "\n";
    }
    sleep(2);
}
