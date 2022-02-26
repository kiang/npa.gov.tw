<?php
foreach(glob(__DIR__ . '/kml/*.kml') AS $kmlFile) {
    $p = pathinfo($kmlFile);
    $xml = simplexml_load_file($kmlFile);
    $csvFile = __DIR__ . '/csv/' . $p['filename'] . '.csv';
    $fh = fopen($csvFile, 'w');
    $headerDone = false;
    $header = [];
    if(isset($xml->Document->Folder->Placemark)) {
        $placemarks = $xml->Document->Folder->Placemark;
    } else {
        $placemarks = $xml->Document->Placemark;
    }
    foreach($placemarks AS $placemark) {
        $line = [];
        foreach($placemark->ExtendedData->Data AS $col) {
            if(false === $headerDone) {
                $header[] = (string)$col->attributes()->name;
            }
            $line[] = (string)$col->value;
        }
        if(false === $headerDone) {
            fputcsv($fh, $header);
            $headerDone = true;
        }
        fputcsv($fh, $line);
    }
}