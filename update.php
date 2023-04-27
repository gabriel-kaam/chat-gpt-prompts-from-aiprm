<?php

define('DATA_URL', 'https://api.aiprm.com/api3/Prompts?Community=&Limit=10&Offset=0&OwnerExternalID=user-uGYvFVhqDuXSFLXmGL33vjmL&OwnerExternalSystemNo=1&SortModeNo=2&UserFootprint=');

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
Category: {$prompt->Category}
Teaser: {$prompt->Teaser}

Community: {$prompt->Community}
CreationTime: {$prompt->CreationTime}
Help: {$prompt->Help}
ID: {$prompt->ID}
PromptHint: {$prompt->PromptHint}
PromptPackageID: {$prompt->PromptPackageID}

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
	list($topic, ) = explode('-', $prompt->Community, 2);
	$path = "prompts/$topic/{$prompt->Category}";
	$name = strtolower(sanitize($prompt->Title));
	@mkdir($path, 0777, true);

	file_put_contents("$path/$name.txt", file_content_from_prompt($prompt));
}
