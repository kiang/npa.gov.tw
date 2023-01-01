<?php
$url = 'https://adr.npa.gov.tw/';
$rawFile = __DIR__ . '/raw/page.html';
file_put_contents($rawFile, file_get_contents($url));
$raw = file_get_contents($rawFile);
$pos = strpos($raw, '<table class="ed_table">');
$posEnd = strpos($raw, '</table>', $pos);
$rows = explode('</tr>', substr($raw, $pos, $posEnd - $pos));
$city = '';
foreach ($rows as $row) {
    $cols = explode('</td>', $row);
    foreach ($cols as $k => $col) {
        $pos = strpos($col, '?mid=');
        if (false === $pos) {
            $cols[$k] = trim(strip_tags($col));
        } else {
            $parts1 = explode('edit?mid=', $col);
            if (count($parts1) === 2) {
                $parts2 = explode('&amp;', $parts1[1]);
                $cols[$k] = $parts2[0];
            } else {
                $parts1 = explode('viewer?mid=', $col);
                if (count($parts1) === 2) {
                    $parts2 = explode('&amp;', $parts1[1]);
                    $cols[$k] = $parts2[0];
                } else {
                    $cols[$k] = '';
                }
            }
        }
    }
    foreach ($cols as $k => $v) {
        $cols[$k] = str_replace(["\n", "\r"], '', $v);
    }
    $cnt = count($cols);
    if ($cnt === 5) {
        $city = $cols[1];
        $targetFile = __DIR__ . '/kml/' . $cols[1] . '_' . $cols[2] . '.kml';
        file_put_contents($targetFile, file_get_contents('https://www.google.com/maps/d/u/0/kml?mid=' . $cols[3] . '&forcekml=1'));
    } elseif ($cnt === 3) {
        $targetFile = __DIR__ . '/kml/' . $city . '_' . $cols[0] . '.kml';
        file_put_contents($targetFile, file_get_contents('https://www.google.com/maps/d/u/0/kml?mid=' . $cols[1] . '&forcekml=1'));
    }
}
