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
OwnPrompt: {$prompt->OwnPrompt}
PromptHint: {$prompt->PromptHint}
PromptPackageID: {$prompt->PromptPackageID}
PromptTypeNo": {$prompt->PromptTypeNo}
RevisionTime: {$prompt->RevisionTime}
Usages: {$prompt->Usages}
Views: {$prompt->Views}
Votes: {$prompt->Votes}

Prompt:
{$prompt->Prompt}

EOF;
}

$prompts = json_decode(file_get_contents('https://api.aiprm.com/api2/Prompts?Community=&Limit=10&Offset=0&OwnerExternalID=user-Sym2oNwW2gUBywbi1gqkKPyB&OwnerExternalSystemNo=1&SortModeNo=2'));

foreach ($prompts as $prompt) {
	$path = 'prompts/' . $prompt->Category;
	$name = strtolower(sanitize($prompt->Title));
	@mkdir($path);

	file_put_contents("$path/$name.txt", file_content_from_prompt($prompt));
}
