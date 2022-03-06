<?php
$pairs = [
    '.' => '_'
];
foreach (glob(__DIR__ . '/kml/*.kml') as $kmlFile) {
    $p = pathinfo($kmlFile);
    $xml = simplexml_load_file($kmlFile);
    if (isset($xml->Document->Folder)) {
        $folderNameCount = [];
        foreach ($xml->Document->Folder as $folder) {
            $folderName = (string)$folder->name;
            $folderName = strtr($folderName, $pairs);
            if(!isset($folderNameCount[$folderName])) {
                $folderNameCount[$folderName] = 0;
                $csvFile = __DIR__ . '/csv/' . $p['filename'] . '_' . $folderName . '.csv';
            } else {
                ++$folderNameCount[$folderName];
                $csvFile = __DIR__ . '/csv/' . $p['filename'] . '_' . $folderName . $folderNameCount[$folderName] . '.csv';
            }
            $fh = fopen($csvFile, 'w');
            $headerDone = false;
            $header = [];
            foreach ($folder->Placemark as $placemark) {
                if (!isset($placemark->ExtendedData)) {
                    continue;
                }
                $line = [];
                foreach ($placemark->ExtendedData->Data as $col) {
                    if (false === $headerDone) {
                        $header[] = (string)$col->attributes()->name;
                    }
                    $line[] = (string)$col->value;
                }
                if (false === $headerDone) {
                    fputcsv($fh, $header);
                    $headerDone = true;
                }
                fputcsv($fh, $line);
            }
        }
    }
}
