<?php
require __DIR__ . '/../config/env.php';

$payload = json_encode([
    'message' => 'am sick of malaria and am located at morogoro',
    'lang' => 'en',
    'context' => [],
]);

$started = microtime(true);
$ch = curl_init('http://localhost:8080/api/ai-chat.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 60,
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$total = round((microtime(true) - $started) * 1000);

echo "HTTP: {$code}\n";
echo "Total wall: {$total}ms\n";
echo "Meta: " . json_encode($data['meta'] ?? null, JSON_PRETTY_PRINT) . "\n";
echo "Filters: " . json_encode($data['data']['filters'] ?? null) . "\n";
echo "Hospitals found: " . ($data['data']['hospitals_found'] ?? 0) . "\n";
echo "Model: " . ($data['data']['model'] ?? '?') . "\n\n";
echo "Reply:\n" . ($data['data']['reply'] ?? $data['fallback'] ?? $data['message'] ?? '?') . "\n";
