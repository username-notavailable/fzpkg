<?php

// https://www.gardainformatica.it/database-comuni-italiani

$data = [];
$array = [];
$outputFile = __DIR__ . DIRECTORY_SEPARATOR . 'makeArray.output.php';
$gi_subdir = 'gi_db_comuni-2024-06-30' . DIRECTORY_SEPARATOR . 'json';

foreach (['gi_regioni', 'gi_province', 'gi_comuni'] as $fileSlug) {
    $data[$fileSlug] = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $gi_subdir . DIRECTORY_SEPARATOR . $fileSlug . '.json'), true);
}

file_put_contents($outputFile, '<?php

return [
');

foreach ($data as $fileSlug => $fileData) {
    foreach ($fileData as $itemInfo) {
        if ($fileSlug === 'gi_regioni') {
            $item = strtolower($itemInfo['denominazione_regione']);
        }
        else if ($fileSlug === 'gi_province') {
            $item = strtolower($itemInfo['denominazione_provincia']);
        }
        else if ($fileSlug === 'gi_comuni') {
            $item = strtolower($itemInfo['denominazione_ita']);
        }

        $item = preg_replace('@/.*@', '', $item);

        if (!in_array($item, $array)) {
            $array[] = $item;
        }
    }
}

foreach($array as $item) {
    file_put_contents($outputFile, "\t\"" . $item . "\"," . PHP_EOL, FILE_APPEND);
}

file_put_contents($outputFile, "];" . PHP_EOL, FILE_APPEND);