<?php

declare(strict_types=1);

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

$data = file_get_contents(DATA_URL);
$prompts = json_decode($data);

$i = 0;
$max = count($prompts);
$batchSize = 10;

for ($j = 0; $j < $max; $j += $batchSize) {
    $batch = array_slice($prompts, $j, $batchSize);
    $urls = [];

    foreach ($batch as $prompt) {
        $urls[$prompt->ID] = "https://api.aiprm.com/api9/Prompts/{$prompt->ID}?OperatorERID=" . USER_ID . "&SystemNo=1&Basic=true";
    }

    $responses = fetch_prompts_in_parallel($urls);

    foreach ($batch as $prompt) {
        $chunks = explode('/', trim($prompt->CanonicalURL, '/'));
        $path = "prompts_v2/{$chunks[0]}/{$chunks[1]}";
        $name = strtolower(sanitize($prompt->Title));
        @mkdir($path, 0777, true);

        if (isset($responses[$prompt->ID])) {
            file_put_contents(".data/{$prompt->ID}.json", $responses[$prompt->ID]);
            $prompt->Prompt = json_decode($responses[$prompt->ID])->Prompt;
        }

        file_put_contents("$path/$name.txt", file_content_from_prompt($prompt));
        echo "[$j/" . $i++ . "/{$max}]\tWorking on $prompt->CanonicalURL\n";
    }
}
