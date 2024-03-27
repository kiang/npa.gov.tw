<?php
$basePath = dirname(__DIR__);
$jsonPath = $basePath . '/docs/json';
if (!file_exists($jsonPath)) {
    mkdir($jsonPath, 0777, true);
}
foreach (glob($basePath . '/csv/pool/*.csv') as $csvFile) {
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    $fc = [
        'type' => 'FeatureCollection',
        'features' => [],
    ];
    while ($line = fgetcsv($fh, 2048)) {
        $f = [
            'type' => 'Feature',
            'properties' => json_decode($line[2], true),
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    floatval($line[1]), floatval($line[0])
                ],
            ],
        ];
        $fc['features'][] = $f;
    }
    $p = pathinfo($csvFile);
    $jsonFile = $jsonPath . '/' . $p['filename'] . '.json';
    file_put_contents($jsonFile, json_encode($fc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
