<?php

function sanitize($string) {
	return preg_replace('/[^a-zA-Z0-9 ]/', '', $string);
}

$prompts = json_decode(file_get_contents('https://api.aiprm.com/api2/Prompts?Community=&Limit=10&Offset=0&OwnerExternalID=user-Sym2oNwW2gUBywbi1gqkKPyB&OwnerExternalSystemNo=1&SortModeNo=2'));

foreach ($prompts as $prompt) {
	@mkdir('prompts/' . $prompt->Category);
	echo $prompt->Title . " => '" . sanitize($prompt->Title) . "'\n";
	echo "TEASER => {$prompt->Teaser}\n\n";
}
