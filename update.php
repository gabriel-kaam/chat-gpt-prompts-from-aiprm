<?php

declare(strict_types=1);

define('BATCH_SIZE', 10);
define('USER_ID', getenv('USER_ID'));
define('DATA_URL', 'https://api.aiprm.com/api9/Prompts?Topic=&Limit=10&Offset=0&OwnerOperatorERID=' . USER_ID . '&OwnerSystemNo=1&SortModeNo=2&UserFootprint=&IncludeTeamPrompts=true');

function sanitize($string) {
    $string = str_replace(['&'], ['and'], $string);
    $string = preg_replace('/[^a-zA-Z0-9 \-,]/', '', $string);
    return trim($string);
}

function file_content_from_prompt($prompt) {
    $prompt->Teaser = trim($prompt->Teaser);
    return <<<EOF
AuthorName: {$prompt->AuthorName}
AuthorURL: {$prompt->AuthorURL}

Title: {$prompt->Title}
Activity: {$prompt->Activity}
Topic: {$prompt->Topic}
Teaser: {$prompt->Teaser}

RevisionTime: {$prompt->RevisionTime}
ID: {$prompt->ID}
PromptHint: {$prompt->PromptHint}

Prompt:
{$prompt->Prompt}

EOF;
}

function fetch_prompts_in_parallel($urls) {
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    $responses = [];

    foreach ($urls as $id => $url) {
        $curlHandles[$id] = curl_init($url);
        curl_setopt($curlHandles[$id], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandles[$id], CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($multiHandle, $curlHandles[$id]);
    }

    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle);
    } while ($running > 0);

    foreach ($curlHandles as $id => $ch) {
        $responses[$id] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);

    return $responses;
}

function get_paths($prompt) {
    $promptID = $prompt->ID;
    $chunks = explode('/', trim($prompt->CanonicalURL, '/'));
    $path = "prompts_v2/{$chunks[0]}/{$chunks[1]}";
    $name = strtolower(sanitize($prompt->Title));
    return [
        'json' => ".data/{$promptID}.json",
        'txt' => "$path/{$name}_{$promptID}.txt",
        'dir' => $path
    ];
}

function should_fetch_prompt($paths, $promptRevisionTime) {
    return !file_exists($paths['json']) || !file_exists($paths['txt']) || strtotime($promptRevisionTime) > filemtime($paths['json']);
}

function save_prompt($prompt, $response, $paths) {
    @mkdir($paths['dir'], 0777, true);
    file_put_contents($paths['json'], $response);
    file_put_contents($paths['txt'], file_content_from_prompt($prompt));
}

function handle_batch($batch, $current, $total) {
    $urls = [];
    $responses = [];

    foreach ($batch as $prompt) {
        $paths = get_paths($prompt);
        if (should_fetch_prompt($paths, $prompt->RevisionTime)) {
            $urls[$prompt->ID] = "https://api.aiprm.com/api9/Prompts/{$prompt->ID}?OperatorERID=" . USER_ID . "&SystemNo=1&Basic=true";
        }
    }

    if (!empty($urls)) {
        $responses = fetch_prompts_in_parallel($urls);
    }

    foreach ($batch as $prompt) {
        $paths = get_paths($prompt);
        $promptID = $prompt->ID;
        if (isset($responses[$promptID])) {
            $prompt->Prompt = json_decode($responses[$promptID])->Prompt;
            save_prompt($prompt, $responses[$promptID], $paths);
            echo "\n[+] Processing: {$prompt->CanonicalURL}\n";
        }
    }

    display_progress($current, $total);
}

function display_progress($current, $total) {
    $progress = ($current / $total) * 100;
    $barLength = 50;
    $filledLength = (int)round($barLength * ($current / $total));
    $bar = str_repeat('=', $filledLength) . str_repeat(' ', $barLength - $filledLength);
    printf("\rProgress: [%s] %d%% (%d/%d)", $bar, $progress, $current, $total);
}

$data = file_get_contents(DATA_URL);
$prompts = json_decode($data);

$max = count($prompts);
$totalBatches = ceil($max / BATCH_SIZE);

for ($i = 0; $i < $max; $i += BATCH_SIZE) {
    handle_batch(array_slice($prompts, $i, BATCH_SIZE), $i + BATCH_SIZE, $max);
}

echo "\nAll batches processed.\n";
