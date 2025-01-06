<?php

declare(strict_types=1);

define('DATA_URL', 'https://api.aiprm.com/api9/Prompts?Topic=&Limit=10&Offset=0&OwnerOperatorERID=' . getenv('USER_ID') . '&OwnerSystemNo=1&SortModeNo=2&UserFootprint=&IncludeTeamPrompts=true');

function sanitize($string) {
	$string = str_replace([
			'&'
		], [
			'and'
		], $string);
	$string = preg_replace('/[^a-zA-Z0-9 \-,]/', '', $string);
	$string = trim($string);

	return $string;
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

if(!$data = @file_get_contents(DATA_URL)) {
	echo "[-] Could not get Data from API - ";
	die(1);
}

$prompts = json_decode($data);

foreach ($prompts as $prompt) {
	$chunks = explode('/', trim($prompt->CanonicalURL, '/'));
	$path = "prompts_v2/{$chunks[0]}/{$chunks[1]}";
	$name = strtolower(sanitize($prompt->Title));
	@mkdir($path, 0777, true);

	file_put_contents("$path/$name.txt", file_content_from_prompt($prompt));
}
