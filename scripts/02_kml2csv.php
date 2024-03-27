<?php
$basePath = dirname(__DIR__);
$pairs = [
    '.' => '_'
];
foreach (glob($basePath . '/kml/*.kml') as $kmlFile) {
    $p = pathinfo($kmlFile);
    $xml = simplexml_load_file($kmlFile);
    if (isset($xml->Document->Folder)) {
        $folderNameCount = [];
        foreach ($xml->Document->Folder as $folder) {
            $folderName = (string)$folder->name;
            $folderName = strtr($folderName, $pairs);
            if (!isset($folderNameCount[$folderName])) {
                $folderNameCount[$folderName] = 0;
                $csvFile = $basePath . '/csv/' . $p['filename'] . '_' . $folderName . '.csv';
            } else {
                ++$folderNameCount[$folderName];
                $csvFile = $basePath . '/csv/' . $p['filename'] . '_' . $folderName . $folderNameCount[$folderName] . '.csv';
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
                    if (!empty($placemark->address)) {
                        $header[] = 'placemark_address';
                    }
                    fputcsv($fh, $header);
                    $headerDone = true;
                }
                if (!empty($placemark->address)) {
                    $line[] = (string)$placemark->address;
                }
                fputcsv($fh, $line);
            }
        }
    }
}
