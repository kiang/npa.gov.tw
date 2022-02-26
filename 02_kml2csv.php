<?php
foreach (glob(__DIR__ . '/kml/*.kml') as $kmlFile) {
    $p = pathinfo($kmlFile);
    $xml = simplexml_load_file($kmlFile);
    $csvFile = __DIR__ . '/csv/' . $p['filename'] . '.csv';
    $fh = fopen($csvFile, 'w');
    $headerDone = false;
    $header = [];
    if (isset($xml->Document->Folder)) {
        foreach ($xml->Document->Folder as $folder) {
            foreach ($folder->Placemark as $placemark) {
                if(!isset($placemark->ExtendedData)) {
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
