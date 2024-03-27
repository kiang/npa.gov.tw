<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$browser = new HttpBrowser(HttpClient::create());

$url = 'https://adr.npa.gov.tw/';
$rawFile = $basePath . '/raw/page.html';
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
        $cols[$k] = str_replace(["\n", "\r", "\t"], '', $v);
    }
    $cnt = count($cols);
    if ($cnt === 5) {
        $city = $cols[1];
        $targetFile = $basePath . '/kml/' . $cols[1] . '_' . $cols[2] . '.kml';
        $browser->request('GET', 'https://www.google.com/maps/d/u/0/kml?mid=' . $cols[3] . '&forcekml=1');
        file_put_contents($targetFile, $browser->getResponse()->getContent());
    } elseif ($cnt === 3) {
        $targetFile = $basePath . '/kml/' . $city . '_' . $cols[0] . '.kml';
        $browser->request('GET', 'https://www.google.com/maps/d/u/0/kml?mid=' . $cols[1] . '&forcekml=1');
        file_put_contents($targetFile, $browser->getResponse()->getContent());
    }
}
