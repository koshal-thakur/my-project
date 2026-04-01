<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';

function jarvis_response(string $reply, array $actions = [], array $suggestions = []): void
{
    echo json_encode([
        'ok' => true,
        'reply' => $reply,
        'actions' => $actions,
        'suggestions' => $suggestions,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jarvis_env_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function jarvis_external_ai_enabled(): bool
{
    $apiKey = trim((string)(getenv('JARVIS_AI_API_KEY') ?: ''));
    if ($apiKey === '') {
        return false;
    }
    return jarvis_env_bool('JARVIS_AI_ENABLED', true);
}

function jarvis_external_ai_answer(string $question, array $context = []): array
{
    if (!jarvis_external_ai_enabled()) {
        return ['ok' => false, 'reply' => ''];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'reply' => ''];
    }

    $apiKey = trim((string)(getenv('JARVIS_AI_API_KEY') ?: ''));
    $endpoint = trim((string)(getenv('JARVIS_AI_ENDPOINT') ?: 'https://openrouter.ai/api/v1/chat/completions'));
    $model = trim((string)(getenv('JARVIS_AI_MODEL') ?: 'openai/gpt-4o-mini'));
    $siteName = trim((string)(getenv('JARVIS_SITE_NAME') ?: 'Quiz Competitors'));
    $siteUrl = trim((string)(getenv('JARVIS_SITE_URL') ?: 'http://localhost/questionsforyou'));

    $systemPrompt = "You are Jarvis, an assistant for the {$siteName} website. "
        . "Give concise, helpful answers in plain text. "
        . "If a request is unsafe or impossible, suggest a safe practical alternative. "
        . "Keep answers short and actionable.";

    $contextText = '';
    if (!empty($context)) {
        $pairs = [];
        foreach ($context as $k => $v) {
            $pairs[] = $k . ': ' . $v;
        }
        $contextText = "\nWebsite context: " . implode(' | ', $pairs);
    }

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question . $contextText],
        ],
        'temperature' => 0.4,
        'max_tokens' => 280,
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: ' . $siteUrl,
        'X-Title: ' . $siteName,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 14);

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'reply' => ''];
    }

    $json = json_decode($raw, true);
    $reply = trim((string)($json['choices'][0]['message']['content'] ?? ''));
    if ($reply === '') {
        return ['ok' => false, 'reply' => ''];
    }

    return ['ok' => true, 'reply' => $reply];
}

function jarvis_scalar(mysqli $conn, string $sql, string $types = '', array $params = [], int $default = 0): int
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $default;
    }

    if ($types !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_row($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row || !isset($row[0])) {
        return $default;
    }

    return (int)$row[0];
}

function jarvis_top_leaderboard(mysqli $conn, int $limit = 5): array
{
    $rows = [];
    $sql = 'SELECT username, score, time FROM leaderboard ORDER BY score DESC, time ASC, username ASC LIMIT ?';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $rows;
    }

    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function jarvis_subject_count(mysqli $conn): int
{
    return jarvis_scalar(
        $conn,
        "SELECT COUNT(*) FROM (SELECT subject FROM quiz_question GROUP BY subject) t"
    );
}

$rawInput = file_get_contents('php://input');
$decoded = null;
if (is_string($rawInput) && $rawInput !== '') {
    $decoded = json_decode($rawInput, true);
}

$message = '';
if (is_array($decoded) && isset($decoded['message'])) {
    $message = trim((string)$decoded['message']);
} elseif (isset($_POST['message'])) {
    $message = trim((string)$_POST['message']);
}

if ($message === '') {
    jarvis_response(
        'Jarvis online. Tell me what you need.',
        [],
        ['Open quiz', 'Top 5 leaderboard', 'Play video about AI', 'What is DBMS normalization?']
    );
}

$lower = mb_strtolower($message, 'UTF-8');

$pageMap = [
    'home' => 'index2.html',
    'index' => 'index2.html',
    'login' => 'LOGINpage.php',
    'sign up' => 'signup.php',
    'signup' => 'signup.php',
    'register' => 'signup.php',
    'about' => 'about1.php',
    'contact' => 'contact2.php',
    'support' => 'support_replies.php',
    'support replies' => 'support_replies.php',
    'quiz' => 'welcomequiz.php',
    'start quiz' => 'welcomequiz.php',
    'rankings' => 'Scoreboard.php',
    'leaderboard' => 'Scoreboard.php',
    'scoreboard' => 'Scoreboard.php',
    'account' => 'account.php',
    'admin' => 'adminlogin.php',
];

if (preg_match('/\b(help|commands|what can you do)\b/u', $lower)) {
    jarvis_response(
        'I can navigate pages, open YouTube videos, control media, search the web, answer live website stats, and use external AI (when configured) for broader knowledge.',
        [],
        ['Top 5 leaderboard', 'Site status', 'Play video on Python loops', 'What is OOP?', 'Open Start Quiz']
    );
}

if (preg_match('/\b(site status|system status|dashboard status|overall status)\b/u', $lower)) {
    $totalUsers = jarvis_scalar($conn, 'SELECT COUNT(*) FROM user');
    $activeAttempts = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_attempts WHERE status = 'active'");
    $pendingPayments = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_payments WHERE admin_approval_status = 'pending'");
    $supportThreads = jarvis_scalar($conn, 'SELECT COUNT(*) FROM (SELECT user_id, email FROM support_chat_messages GROUP BY user_id, email) t');
    $isLive = jarvis_scalar($conn, 'SELECT is_quiz_live FROM quiz_control WHERE id = 1 LIMIT 1', '', [], 0) === 1;

    $reply = 'Live status: Quiz is ' . ($isLive ? 'ON' : 'OFF')
        . '. Users: ' . $totalUsers
        . ', Active attempts: ' . $activeAttempts
        . ', Pending payments: ' . $pendingPayments
        . ', Support threads: ' . $supportThreads . '.';

    jarvis_response($reply, [], ['Open Admin', 'Open Rankings', 'Open Support Replies']);
}

if (preg_match('/\b(top|best)\s*(\d+)?\s*(leaderboard|rankings|players|users)\b/u', $lower) || preg_match('/\bleaderboard\b|\brankings\b|\btop players\b/u', $lower)) {
    $limit = 5;
    if (preg_match('/\btop\s*(\d{1,2})\b/u', $lower, $m)) {
        $limit = max(1, min(10, (int)$m[1]));
    }

    $topRows = jarvis_top_leaderboard($conn, $limit);
    if (empty($topRows)) {
        jarvis_response('No leaderboard records yet. Once users complete quizzes, rankings will appear.', [['type' => 'navigate', 'url' => 'welcomequiz.php']]);
    }

    $lines = [];
    foreach ($topRows as $idx => $row) {
        $lines[] = ($idx + 1) . ') ' . ($row['username'] ?? 'User') . ' — Score ' . (int)($row['score'] ?? 0) . ', Time ' . (int)($row['time'] ?? 0) . 's';
    }
    jarvis_response('Top leaderboard:\n' . implode('\n', $lines), [['type' => 'navigate', 'url' => 'Scoreboard.php']]);
}

if (preg_match('/\b(payment|payments|pending payments|approval)\b/u', $lower)) {
    $totalPayments = jarvis_scalar($conn, 'SELECT COUNT(*) FROM quiz_payments');
    $pendingPayments = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_payments WHERE admin_approval_status = 'pending'");
    $approvedPayments = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_payments WHERE admin_approval_status = 'approved'");
    $reply = 'Payments summary: Total ' . $totalPayments . ', Pending ' . $pendingPayments . ', Approved ' . $approvedPayments . '.';
    jarvis_response($reply, [['type' => 'navigate', 'url' => 'admin_payments.php']], ['Open Admin Payments', 'Open Account']);
}

if (preg_match('/\b(active attempt|attempts|quiz attempts)\b/u', $lower)) {
    $activeAttempts = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_attempts WHERE status = 'active'");
    $completedAttempts = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_attempts WHERE status = 'completed'");
    $cancelledAttempts = jarvis_scalar($conn, "SELECT COUNT(*) FROM quiz_attempts WHERE status = 'cancelled'");
    $reply = 'Attempt summary: Active ' . $activeAttempts . ', Completed ' . $completedAttempts . ', Cancelled ' . $cancelledAttempts . '.';
    jarvis_response($reply, [['type' => 'navigate', 'url' => 'admin_active_attempts.php']], ['Open Active Attempts', 'Open Start Quiz']);
}

if (preg_match('/\b(support|chat|support replies|tickets)\b/u', $lower)) {
    $totalMessages = jarvis_scalar($conn, 'SELECT COUNT(*) FROM support_chat_messages');
    $threadCount = jarvis_scalar($conn, 'SELECT COUNT(*) FROM (SELECT user_id, email FROM support_chat_messages GROUP BY user_id, email) t');
    $reply = 'Support summary: ' . $threadCount . ' conversation threads and ' . $totalMessages . ' total messages.';
    jarvis_response($reply, [['type' => 'navigate', 'url' => 'support_replies.php']], ['Open Support Replies', 'Open Admin Support']);
}

if (preg_match('/\b(question|questions|subjects|topics)\b/u', $lower)) {
    $questionCount = jarvis_scalar($conn, 'SELECT COUNT(*) FROM quiz_question');
    $subjectCount = jarvis_subject_count($conn);
    $reply = 'Question bank status: ' . $questionCount . ' questions across ' . $subjectCount . ' subjects.';
    jarvis_response($reply, [['type' => 'navigate', 'url' => 'welcomequiz.php']], ['Open Start Quiz', 'Open Admin Questions']);
}

if (preg_match('/\b(time|date|india time|ist)\b/u', $lower)) {
    $tz = new DateTimeZone('Asia/Kolkata');
    $now = new DateTime('now', $tz);
    $text = $now->format('d M Y, h:i:s A') . ' IST';
    jarvis_response('Current India time: ' . $text);
}

if (preg_match('/\b(open|go to|visit)\s+(?:youtube|you\s*tube)\b/iu', $message)) {
    $query = '';
    if (preg_match('/\b(?:youtube|you\s*tube)\b\s*(?:for|about|on|search(?:\s+for)?|videos?\s+(?:on|about))?\s*(.*)$/iu', $message, $m)) {
        $query = trim((string)($m[1] ?? ''));
    }

    if (preg_match('/^(?:any|some|a|an)?\s*(?:video|videos|music|audio|song|songs|short|shorts)(?:\s+on\s+youtube)?$/iu', $query)) {
        $query = '';
    }

    jarvis_response(
        $query !== ''
            ? 'Opening YouTube search for: ' . $query
            : 'Opening YouTube Shorts now.',
        [[
            'type' => 'openYouTube',
            'query' => $query,
            'url' => ($query !== '' ? 'https://www.youtube.com/' : 'https://www.youtube.com/shorts'),
            'autoplay' => false,
        ]]
    );
}

if (preg_match('/\b(play|start)\b.*\b(video|videos|music|audio)\b/u', $lower) || preg_match('/\b(video|videos)\b.*\b(play|start)\b/u', $lower)) {
    $query = '';
    $defaultYouTubeUrl = 'https://www.youtube.com/shorts';
    if (preg_match('/(?:play|start)\s+(?:a\s+)?(?:video|videos|music|audio)\s*(?:about|on)?\s*(.*)$/iu', $message, $m)) {
        $query = trim((string)($m[1] ?? ''));
    }

    if (preg_match('/^(?:any|some|a|an)?\s*(?:video|videos|music|audio|song|songs|short|shorts)(?:\s+on\s+youtube)?$/iu', $query)) {
        $query = '';
    }

    $youtubeUrl = '';
    if (preg_match('/(https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/[\S]+)/iu', $message, $urlMatch)) {
        $youtubeUrl = trim((string)$urlMatch[1]);
    }

    jarvis_response(
        $youtubeUrl !== ''
            ? 'Opening your YouTube link and starting playback.'
            : ($query !== ''
                ? 'Opening YouTube for: ' . $query
                : 'Opening YouTube now.'),
        [[
            'type' => 'openYouTube',
            'query' => $query,
            'url' => ($youtubeUrl !== '' ? $youtubeUrl : $defaultYouTubeUrl),
            'autoplay' => ($youtubeUrl !== '' || $query !== ''),
        ]]
    );
}

if (preg_match('/\b(pause|stop)\b.*\b(video|videos|music|audio|media)\b/u', $lower)) {
    jarvis_response('Pausing available media now.', [['type' => 'pauseMedia']]);
}

if (preg_match('/\b(mute)\b/u', $lower)) {
    jarvis_response('Muting available media.', [['type' => 'muteMedia']]);
}

if (preg_match('/\b(unmute)\b/u', $lower)) {
    jarvis_response('Unmuting available media.', [['type' => 'unmuteMedia']]);
}

if (preg_match('/\b(scroll to top|back to top|go top)\b/u', $lower)) {
    jarvis_response('Moving to top.', [['type' => 'scrollTop']]);
}

if (preg_match('/\b(search|find|look up)\b\s+(.+)/iu', $message, $m)) {
    $query = trim((string)($m[2] ?? ''));
    if ($query !== '') {
        jarvis_response('Searching the internet for: ' . $query, [['type' => 'openSearch', 'query' => $query]]);
    }
}

if (preg_match('/^(what\s+is|who\s+is|how\s+to|why\s+|when\s+|where\s+|explain\s+)/iu', $message)) {
    $ai = jarvis_external_ai_answer($message, [
        'page' => (is_array($decoded) ? (string)($decoded['page'] ?? '') : ''),
        'mode' => 'general-knowledge',
    ]);

    if (($ai['ok'] ?? false) === true && trim((string)($ai['reply'] ?? '')) !== '') {
        jarvis_response(
            (string)$ai['reply'],
            [],
            ['Play video about ' . $message, 'Search ' . $message]
        );
    }

    jarvis_response(
        'Opening web answer search for: ' . $message,
        [['type' => 'openSearch', 'query' => $message]],
        ['Search ' . $message, 'Play video about ' . $message]
    );
}

if (preg_match('/\b(open|go to|visit|take me to)\b/u', $lower)) {
    foreach ($pageMap as $keyword => $url) {
        if (mb_strpos($lower, $keyword) !== false) {
            jarvis_response('Opening ' . ucfirst($keyword) . ' page.', [['type' => 'navigate', 'url' => $url]]);
        }
    }
}

foreach ($pageMap as $keyword => $url) {
    if (mb_strpos($lower, $keyword) !== false) {
        jarvis_response('I can take you there now.', [['type' => 'navigate', 'url' => $url]]);
    }
}

$aiFallback = jarvis_external_ai_answer($message, [
    'page' => (is_array($decoded) ? (string)($decoded['page'] ?? '') : ''),
    'mode' => 'fallback',
]);

if (($aiFallback['ok'] ?? false) === true && trim((string)($aiFallback['reply'] ?? '')) !== '') {
    jarvis_response(
        (string)$aiFallback['reply'],
        [],
        ['Search ' . $message, 'Play video about ' . $message]
    );
}

jarvis_response(
    ((function_exists('jarvis_external_ai_enabled') && jarvis_external_ai_enabled())
        ? 'I can help with commands, live website data, and external AI answers. Try: "Top 5 leaderboard", "Site status", "Play video on Java", or "What is recursion?".'
        : 'I can help with commands, live website data, and internet lookup. Try: "Top 5 leaderboard", "Site status", "Play video on Java", or "What is recursion?".'),
    [],
    ['Top 5 leaderboard', 'Site status', 'Play video about AI', 'What is recursion?']
);
