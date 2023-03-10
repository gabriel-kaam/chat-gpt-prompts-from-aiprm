<?php

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

$prompts = json_decode(file_get_contents('https://api.aiprm.com/api2/Prompts?Community=&Limit=10&Offset=0&OwnerExternalID=user-Sym2oNwW2gUBywbi1gqkKPyB&OwnerExternalSystemNo=1&SortModeNo=2'));

foreach ($prompts as $prompt) {
	list($topic, ) = explode('-', $prompt->Community, 2);
	$path = "prompts/$topic/{$prompt->Category}";
	$name = strtolower(sanitize($prompt->Title));
	@mkdir($path, 0777, true);

	file_put_contents("$path/$name.txt", file_content_from_prompt($prompt));
}
