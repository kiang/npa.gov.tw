<?php
$url = 'https://cdo.npa.gov.tw/ch/app/artwebsite/view?module=artwebsite&id=7966&serno=0492c1b9-2591-4418-af9f-73de33f32430';
$rawFile = __DIR__ . '/raw/page.html';
if (!file_exists($rawFile)) {
    file_put_contents($rawFile, file_get_contents($url));
}
$raw = file_get_contents($rawFile);
$pos = strpos($raw, '<table class="ed_table">');
$posEnd = strpos($raw, '</table>', $pos);
$rows = explode('</tr>', substr($raw, $pos, $posEnd - $pos));
foreach ($rows as $row) {
    $cols = explode('</td>', $row);
    foreach ($cols as $k => $col) {
        if ($k !== 3) {
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
    if (count($cols) === 5) {
        $targetFile = __DIR__ . '/kml/' . $cols[1] . '_' . $cols[2] . '.kml';
        if(!file_exists($targetFile)) {
            file_put_contents($targetFile, file_get_contents('https://www.google.com/maps/d/u/0/kml?mid=' . $cols[3] . '&forcekml=1'));
        }
    }
}
