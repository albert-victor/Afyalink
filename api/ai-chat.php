<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$startedAt = microtime(true);
$body = getRequestBody();
$query = trim((string) ($body['message'] ?? ''));
$uiLang = in_array($body['lang'] ?? 'sw', ['sw', 'en'], true) ? $body['lang'] : 'sw';
$lang = detectQueryLanguage($query, $uiLang);
$context = $body['context'] ?? [];

if ($query === '') {
    jsonResponse(['success' => false, 'error' => 'Message is required'], 422);
}

$apiKey = $config['openrouter']['api_key'];
if ($apiKey === '') {
    jsonResponse([
        'success' => false,
        'error' => 'offline',
        'fallback' => getOfflineAiResponse($query, $lang, $context, 'no_api_key'),
    ], 503);
}

try {
    $dbStarted = microtime(true);
    $pdo = getDbConnection();
    $search = resolveAiSearchFilters($pdo, $query, $context);
    $hospitals = searchAiHospitals($pdo, $search, 8);
    $servicesByHospital = fetchHospitalServicesBatch($pdo, array_column($hospitals, 'id'));
    $dbMs = (int) round((microtime(true) - $dbStarted) * 1000);
    $hospitalList = formatHospitalListReply($hospitals, $servicesByHospital, $search, $lang);

    $aiStarted = microtime(true);
    $intro = callOpenRouterIntro($query, $lang, $search, count($hospitals), $config);
    $aiMs = (int) round((microtime(true) - $aiStarted) * 1000);

    $reply = trim($intro . "\n\n" . $hospitalList);

    jsonResponse([
        'success' => true,
        'data' => [
            'reply' => $reply,
            'source' => 'openrouter+db',
            'model' => $intro ? 'structured' : 'db-only',
            'lang' => $lang,
            'hospitals_found' => count($hospitals),
            'filters' => [
                'region' => $search['region_name'],
                'service' => $search['service_name'],
            ],
        ],
        'meta' => [
            'timestamp' => date('c'),
            'timings' => [
                'db_ms' => $dbMs,
                'ai_ms' => $aiMs,
                'total_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ],
        ],
    ]);
} catch (Throwable $e) {
    $reason = str_contains(strtolower($e->getMessage()), 'rate') ? 'rate_limited' : 'unavailable';

    jsonResponse([
        'success' => false,
        'error' => 'ai_unavailable',
        'fallback' => getOfflineAiResponse($query, $lang, $context, $reason),
        'message' => $config['debug'] ? $e->getMessage() : null,
        'meta' => [
            'timings' => [
                'total_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ],
        ],
    ], 503);
}

function detectQueryLanguage(string $query, string $fallback = 'sw'): string
{
    $lower = mb_strtolower($query, 'UTF-8');

    $swMarkers = [
        'hospitali', 'huduma', 'dharura', 'naomba', 'nina', 'ninaumwa', 'pole', 'asante',
        'habari', 'wapi', 'gani', 'ugonjwa', 'daktari', 'homa', 'maumivu', 'kichwa', 'tumbo',
        'mguu', 'mkono', 'jino', 'degedege', 'malaria', 'ukimwi', 'uzazi', 'mimba', 'mtoto',
        'samahani', 'tafadhali', 'ninahitaji', 'nime', 'ame', 'wame', 'kuna', 'hakuna',
        'ndio', 'hapana', 'sana', 'karibu', 'leo', 'sasa', 'naweza', 'msaada', 'afya',
    ];
    $enMarkers = [
        'hospital', 'service', 'emergency', 'doctor', 'please', 'where', 'which', 'pain',
        'headache', 'fever', 'need', 'find', 'available', 'help', 'sick', 'illness',
        'symptoms', 'near', 'nearby', 'clinic', 'ambulance', 'pregnant', 'child', 'baby',
        'located', 'have', 'am', 'i am', 'my',
    ];

    $swScore = 0;
    $enScore = 0;

    foreach ($swMarkers as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
            $swScore++;
        }
    }
    foreach ($enMarkers as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
            $enScore++;
        }
    }

    if (preg_match('/\b(ni|na|ya|wa|za|kwa|mimi|wewe|yangu|yako|nina|nime|tuna)\b/u', $lower)) {
        $swScore++;
    }
    if (preg_match('/\b(the|is|are|my|your|what|how|can|could|would|located)\b/u', $lower)) {
        $enScore++;
    }

    if ($swScore > $enScore) {
        return 'sw';
    }
    if ($enScore > $swScore) {
        return 'en';
    }

    return $fallback;
}

function resolveAiSearchFilters(PDO $pdo, string $query, array $context): array
{
    $lower = mb_strtolower($query, 'UTF-8');
    $regionId = !empty($context['region_id']) ? (int) $context['region_id'] : null;
    $regionName = null;
    $serviceCode = !empty($context['service']) ? (string) $context['service'] : null;
    $serviceName = null;

    $regions = $pdo->query('SELECT id, name, name_sw FROM regions ORDER BY name')->fetchAll();
    $aliases = [
        'dar es salaam' => ['dar', 'dsm', 'dar es salaam', 'dar-es-salaam'],
        'morogoro'      => ['morogoro', 'moro'],
        'pwani'         => ['pwani', 'coast'],
        'iringa'        => ['iringa'],
        'mwanza'        => ['mwanza'],
        'mbeya'         => ['mbeya'],
    ];

    foreach ($regions as $region) {
        $names = array_filter([
            mb_strtolower($region['name']),
            mb_strtolower((string) ($region['name_sw'] ?? '')),
        ]);
        $names = array_merge($names, $aliases[mb_strtolower($region['name'])] ?? []);
        foreach ($names as $name) {
            if ($name !== '' && str_contains($lower, $name)) {
                $regionId = (int) $region['id'];
                $regionName = $region['name'];
                break 2;
            }
        }
    }

    if ($regionId && !$regionName) {
        foreach ($regions as $region) {
            if ((int) $region['id'] === $regionId) {
                $regionName = $region['name'];
                break;
            }
        }
    }

    $serviceKeywords = [
        'malaria'      => 'malaria',
        'mosquito'     => 'malaria',
        'emergency'    => 'emergency',
        'dharura'      => 'emergency',
        'a&e'          => 'emergency',
        'maternity'    => 'maternity',
        'uzazi'        => 'maternity',
        'mimba'        => 'maternity',
        'pregnant'     => 'maternity',
        'pregnancy'    => 'maternity',
        'delivery'     => 'maternity',
        'laboratory'   => 'laboratory',
        'maabara'      => 'laboratory',
        'lab '         => 'laboratory',
        'x-ray'        => 'xray',
        'xray'         => 'xray',
        'pharmacy'     => 'pharmacy',
        'duka la dawa' => 'pharmacy',
        'surgery'      => 'surgery',
        'upasuaji'     => 'surgery',
        'pediatric'    => 'pediatrics',
        'pediatrics'   => 'pediatrics',
        'watoto'       => 'pediatrics',
        'child'        => 'pediatrics',
        'baby'         => 'pediatrics',
        'dental'       => 'dental',
        'menyu'        => 'dental',
        'hiv'          => 'hiv',
        'ukimwi'       => 'hiv',
        'aids'         => 'hiv',
        'tb'           => 'tb',
        'kifua kikuu'  => 'tb',
        'dialysis'     => 'dialysis',
        'immunization' => 'immunization',
        'chanjo'       => 'immunization',
        'opd'          => 'opd',
        'outpatient'   => 'opd',
        'fever'        => 'opd',
        'sick'         => 'opd',
        'ill'          => 'opd',
        'unwell'       => 'opd',
        'headache'     => 'opd',
        'homa'         => 'opd',
        'maumivu'      => 'opd',
        'pain'         => 'opd',
    ];

    uksort($serviceKeywords, fn($a, $b) => strlen($b) <=> strlen($a));

    foreach ($serviceKeywords as $keyword => $code) {
        if (str_contains($lower, $keyword)) {
            $serviceCode = $code;
            break;
        }
    }

    if ($serviceCode) {
        $stmt = $pdo->prepare('SELECT name FROM service_types WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $serviceCode]);
        $serviceName = $stmt->fetchColumn() ?: $serviceCode;
    }

    return [
        'region_id'    => $regionId,
        'region_name'  => $regionName,
        'service_code' => $serviceCode,
        'service_name' => $serviceName,
    ];
}

function searchAiHospitals(PDO $pdo, array $search, int $limit = 8): array
{
    $regionId = $search['region_id'] ?? null;
    $serviceCode = $search['service_code'] ?? null;
    $serviceAttempts = [];

    if ($serviceCode) {
        $serviceAttempts[] = $serviceCode;
        if ($serviceCode === 'malaria') {
            $serviceAttempts[] = 'opd';
            $serviceAttempts[] = 'laboratory';
        } elseif ($serviceCode === 'opd') {
            $serviceAttempts[] = 'emergency';
        }
    }
    $serviceAttempts[] = null;

    $seen = [];
    $results = [];

    foreach ($serviceAttempts as $code) {
        [$sql, $params] = buildHospitalQuery($regionId, null, null, $code);
        $stmt = $pdo->prepare($sql . ' LIMIT ' . ($limit * 2));
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $hospital) {
            $id = (int) $hospital['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $results[] = $hospital;
            if (count($results) >= $limit) {
                break 2;
            }
        }

        if ($regionId && count($results) >= 3) {
            break;
        }
    }

    return $results;
}

function formatHospitalListReply(array $hospitals, array $servicesByHospital, array $search, string $lang): string
{
    if (empty($hospitals)) {
        if ($lang === 'sw') {
            return 'Hakuna hospitali iliyopatikana kwa vigezo hivi kwenye database yetu. '
                . 'Jaribu mkoa jirani au tumia kitufe cha Tafuta Hospitali. Kwa dharura, piga 114.';
        }

        return 'No hospitals matched this search in our database. '
            . 'Try a nearby region or use the hospital search filters. For emergencies, call 114.';
    }

    $region = $search['region_name'] ?? ($lang === 'sw' ? 'mkoa uliochagua' : 'your region');
    $service = $search['service_name'] ?? ($lang === 'sw' ? 'huduma husika' : 'relevant services');

    if ($lang === 'sw') {
        $header = "**Hospitali zinazopatikana – {$region}** ({$service}):\n";
    } else {
        $header = "**Hospitals available – {$region}** ({$service}):\n";
    }

    $lines = [];
    $n = 1;
    foreach ($hospitals as $hospital) {
        $services = $servicesByHospital[(int) $hospital['id']] ?? [];
        $openServices = array_filter($services, fn($s) => $s['availability'] === 'open');
        $openLabel = $lang === 'sw' ? 'Wazi sasa' : 'Open now';
        $closedLabel = $lang === 'sw' ? 'Imefungwa sasa' : 'Closed now';

        if ($openServices) {
            $openNames = array_map(
                fn($s) => $lang === 'sw' && !empty($s['name_sw']) ? $s['name_sw'] : $s['name'],
                $openServices
            );
            $status = "{$openLabel}: " . implode(', ', $openNames);
        } else {
            $status = $closedLabel;
        }

        $phone = $hospital['phone'] ?: ($lang === 'sw' ? 'Hakuna simu' : 'No phone');
        $lines[] = "{$n}. **{$hospital['name']}** – {$hospital['district']}, {$hospital['region']}. {$status}. "
            . ($lang === 'sw' ? 'Simu' : 'Phone') . ": {$phone}";
        $n++;
    }

    $footer = $lang === 'sw'
        ? "\nTaarifa hizi ni kwa muda huu tu – piga simu kabla ya kwenda. Kwa dharura, piga 114."
        : "\nAvailability is for right now – call before you go. For emergencies, call 114.";

    return $header . implode("\n", $lines) . $footer;
}

function callOpenRouterIntro(
    string $query,
    string $lang,
    array $search,
    int $hospitalCount,
    array $config
): string {
    $fallback = buildEmpathyPrefix($query, $lang);
    if ($fallback === '') {
        $fallback = $lang === 'sw'
            ? 'Nimekuelewa. Nimechunguza hospitali zilizopo kwenye mfumo wetu.'
            : 'I understand. I searched our hospital database for you.';
    }

    try {
        $result = callOpenRouterShortIntro($query, $lang, $search, $hospitalCount, $config);
        return trim($result['content']);
    } catch (Throwable) {
        return rtrim($fallback);
    }
}

function callOpenRouterShortIntro(
    string $query,
    string $lang,
    array $search,
    int $hospitalCount,
    array $config
): array {
    $models = getOpenRouterModels($config);
    $errors = [];

    foreach ($models as $model) {
        try {
            return attemptOpenRouterIntroCall($query, $lang, $search, $hospitalCount, $config, $model);
        } catch (OpenRouterRateLimitException $e) {
            $errors[] = "{$model}: rate limited";
            if ($e->retryAfter > 0 && $e->retryAfter <= 3) {
                sleep((int) ceil($e->retryAfter));
                try {
                    return attemptOpenRouterIntroCall($query, $lang, $search, $hospitalCount, $config, $model);
                } catch (Throwable $retryError) {
                    $errors[] = "{$model} retry: " . $retryError->getMessage();
                }
            }
        } catch (Throwable $e) {
            $errors[] = "{$model}: " . $e->getMessage();
        }
    }

    throw new RuntimeException('Intro AI unavailable. ' . implode('; ', $errors));
}

function attemptOpenRouterIntroCall(
    string $query,
    string $lang,
    array $search,
    int $hospitalCount,
    array $config,
    string $model
): array {
    $langName = $lang === 'sw' ? 'Kiswahili' : 'English';
    $region = $search['region_name'] ?? 'unknown';
    $service = $search['service_name'] ?? 'general care';

    $systemPrompt = <<<PROMPT
You are AfyaLink AI. Write ONLY 1-2 short empathetic sentences in {$langName}.
The user needs hospital service info (not medical diagnosis).
Region detected: {$region}. Service: {$service}. Found {$hospitalCount} hospitals in our database.
Show sympathy for their illness/situation. Do NOT list hospitals (listed separately).
Do NOT mention being an AI or repeat disclaimers. Do NOT say data is unavailable if hospitals were found.
PROMPT;

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query],
        ],
        'max_tokens' => 90,
        'temperature' => 0.3,
    ];

    $ch = curl_init($config['openrouter']['base_url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['openrouter']['api_key'],
            'HTTP-Referer: ' . $config['openrouter']['site_url'],
            'X-Title: ' . $config['openrouter']['app_name'],
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Network error: ' . $curlError);
    }

    $data = json_decode($response, true);

    if ($httpCode === 429) {
        throw new OpenRouterRateLimitException(
            $data['error']['message'] ?? 'Rate limited',
            (int) ($data['error']['metadata']['retry_after_seconds'] ?? 0)
        );
    }

    if ($httpCode >= 400) {
        throw new RuntimeException($data['error']['message'] ?? "HTTP {$httpCode}");
    }

    $content = trim($data['choices'][0]['message']['content'] ?? '');
    if ($content === '') {
        throw new RuntimeException('Empty intro');
    }

    return ['content' => $content, 'model' => $data['model'] ?? $model];
}

function getOpenRouterModels(array $config): array
{
    $models = array_merge(
        [$config['openrouter']['model']],
        $config['openrouter']['fallback_models'] ?? []
    );

    return array_values(array_unique(array_filter(array_map('trim', $models))));
}

class OpenRouterRateLimitException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 0
    ) {
        parent::__construct($message);
    }
}

function getOfflineAiResponse(
    string $query,
    string $lang,
    array $context,
    string $reason = 'unavailable'
): string {
    $empathy = buildEmpathyPrefix($query, $lang);

    if ($reason === 'no_api_key') {
        if ($lang === 'sw') {
            return $empathy
                . "Samahani, msaidizi wa AI haujasanidi bado. "
                . "Tumia 'Tafuta Hospitali' kuona hospitali na huduma zinazopatikana sasa hivi. "
                . 'Kwa dharura, piga 114.';
        }

        return $empathy
            . 'Sorry, the AI assistant is not configured yet. '
            . 'Use hospital search to see available services right now. '
            . 'For emergencies, call 114.';
    }

    if ($reason === 'rate_limited') {
        if ($lang === 'sw') {
            return $empathy
                . 'Samahani, msaidizi una msongamano wa maombi. Jaribu tena baada ya dakika moja, '
                . "au tumia 'Tafuta Hospitali'. Kwa dharura, piga 114.";
        }

        return $empathy
            . 'Sorry, the AI assistant is busy right now. Try again in a minute, '
            . 'or use hospital search. For emergencies, call 114.';
    }

    if ($lang === 'sw') {
        return $empathy
            . 'Samahani, msaidizi wa AI haupatikani kwa sasa. '
            . "Tumia 'Tafuta Hospitali' kuona hospitali na huduma zinazopatikana. "
            . 'Kwa dharura, piga 114.';
    }

    return $empathy
        . 'Sorry, the AI assistant is temporarily unavailable. '
        . 'Use hospital search to see available services. '
        . 'For emergencies, call 114.';
}

function buildEmpathyPrefix(string $query, string $lang): string
{
    $lower = mb_strtolower($query, 'UTF-8');

    $conditions = [
        'sw' => [
            'maumivu ya kichwa' => 'Pole sana kwa maumivu ya kichwa unayopitia.',
            'kichwa' => 'Pole sana kwa maumivu ya kichwa unayopitia.',
            'homa' => 'Pole sana, natumai utapata nafuu haraka.',
            'degedege' => 'Pole sana – hii ni dharura, nenda hospitali au piga 114 mara moja.',
            'malaria' => 'Pole sana, malaria inahitaji matibabu ya haraka.',
            'mimba' => 'Pole sana, natumai utapata huduma bora ya uzazi.',
            'uzazi' => 'Natumai utapata huduma bora ya uzazi.',
            'tumbo' => 'Pole sana kwa maumivu ya tumbo unayopitia.',
            'maumivu' => 'Pole sana kwa maumivu unayopitia.',
            'ninaumwa' => 'Pole sana kusikia kwamba hujisikii vizuri.',
            'ugonjwa' => 'Pole sana kwa hali yako ya afya.',
        ],
        'en' => [
            'headache' => "I'm sorry to hear you're experiencing a headache.",
            'fever' => "I'm sorry you're dealing with a fever – I hope you feel better soon.",
            'seizure' => 'This sounds urgent – please go to a hospital or call 114 immediately.',
            'malaria' => "I'm sorry to hear you're dealing with malaria – please seek care promptly.",
            'pregnant' => 'I hope you receive excellent maternity care.',
            'pregnancy' => 'I hope you receive excellent maternity care.',
            'stomach' => "I'm sorry you're experiencing stomach pain.",
            'pain' => "I'm sorry to hear you're in pain.",
            'sick' => "I'm sorry to hear you're not feeling well.",
            'ill' => "I'm sorry to hear about your health concern.",
            'unwell' => "I'm sorry to hear you're feeling unwell.",
        ],
    ];

    foreach ($conditions[$lang] ?? [] as $keyword => $phrase) {
        if (str_contains($lower, $keyword)) {
            return $phrase . ' ';
        }
    }

    return '';
}
