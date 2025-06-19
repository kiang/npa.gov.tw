<?php
$basePath = dirname(__DIR__);
//$config = require __DIR__ . '/config.php';
$geocodingPath = $basePath . '/raw/geocoding';
if (!file_exists($geocodingPath)) {
    mkdir($geocodingPath, 0777, true);
}
$poolPath = $basePath . '/csv/pool';
if (!file_exists($poolPath)) {
    mkdir($poolPath, 0777, true);
}
$addressCount = 0;
$geocodingEnabled = true;

$pool = [];
foreach (glob($basePath . '/csv/*.csv') as $csvFile) {
    $p = pathinfo($csvFile);
    $city = mb_substr($p['filename'], 0, 3, 'utf-8');
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 4096);
    if (empty($head)) {
        continue;
    }
    $hCount = count($head);
    while ($line = fgetcsv($fh, 4096)) {
        $lCount = count($line);
        if ($hCount > $lCount) {
            $missCount = $hCount - $lCount;
            for ($i = 0; $i < $missCount; $i++) {
                $line[] = '';
            }
            $data = array_combine($head, $line);
        } elseif ($hCount < $lCount) {
            $missCount = $lCount - $hCount;
            for ($i = 0; $i < $missCount; $i++) {
                array_pop($line);
            }
            $data = array_combine($head, $line);
        } else {
            $data = array_combine($head, $line);
        }

        $point = [];
        $address = '';
        if (isset($data['村里別']) && isset($data['地址']) && strlen($data['地址']) < strlen($data['村里別'])) {
            $address = $data['村里別'];
            $data['村里別'] = $data['地址'];
            $data['地址'] = $address;
        }
        if (isset($data['地址'])) {
            $address = $data['地址'];
        } elseif (isset($data['防空疏散避難設施地址'])) {
            $address = $data['防空疏散避難設施地址'];
        } elseif (isset($data['位置'])) {
            $address = $data['位置'];
        } elseif (isset($data['座落'])) {
            $address = $data['座落'];
        } elseif (isset($data['placemark_address'])) {
            $address = $data['placemark_address'];
        }
        if (isset($data['定位點'])) {
            $point = explode(',', $data['定位點']);
            foreach ($point as $k => $v) {
                $point[$k] = floatval($v);
            }
            if (count($point) !== 2) {
                $point = [];
            }
        } elseif (isset($data['緯度'])) {
            $point = [
                floatval($data['緯度']),
                floatval($data['經度']),
            ];
        } elseif (isset($data['Location'])) {
            $point = explode(',', $data['Location']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
            }
        } elseif (isset($data['經緯度'])) {
            $point = explode(',', $data['經緯度']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
            }
        } elseif (isset($data['緯經度'])) {
            $point = explode(',', $data['緯經度']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
            }
        } elseif (isset($data['備註'])) {
            $point = explode(',', $data['備註']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
                $data['備註'] = '';
            } else {
                $point = [];
            }
        }
        if (!isset($point[1])) {
            $point = [];
        }

        if (empty($point)) {
            $lineParts = explode(',', $address);
            if (count($lineParts) === 2) {
                $point = [
                    floatval($lineParts[0]),
                    floatval($lineParts[1]),
                ];
                if (!empty($data['備註']) && !empty($data['地址'])) {
                    $data['地址'] = $data['備註'];
                    $data['備註'] = '';
                    $address = $data['地址'];
                }
                if (empty($point[0])) {
                    $point = [];
                }
            }
        }
        $address = trim($address);
        if (false !== strpos($p['filename'], '桃園市政府警察局') && !empty($address) && isset($data['村里別'])) {
            if (false === strpos($address, '桃園市')) {
                $pos = strpos($address, '區');
                $address = '桃園市' . substr($address, 0, $pos) . '區' . $data['村里別'] . substr($address, $pos + 3);
            }
        }
        if (empty($point)) {
            if (!empty($address)) {
                ++$addressCount;
                $pos = strpos($address, '號');
                $clearAddress = substr($address, 0, $pos) . '號';
                $geocodingFile = $geocodingPath . '/' . $clearAddress . '.json';
                if (!file_exists($geocodingFile) && $geocodingEnabled) {
                    $command = <<<EOD
curl 'https://api.nlsc.gov.tw/MapSearch/ContentSearch?word=___KEYWORD___&mode=AutoComplete&count=1&feedback=XML' \
   -H 'Accept: application/xml, text/xml, */*; q=0.01' \
   -H 'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7' \
   -H 'Connection: keep-alive' \
   -H 'Origin: https://maps.nlsc.gov.tw' \
   -H 'Referer: https://maps.nlsc.gov.tw/' \
   -H 'Sec-Fetch-Dest: empty' \
   -H 'Sec-Fetch-Mode: cors' \
   -H 'Sec-Fetch-Site: same-site' \
   -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36' \
   -H 'sec-ch-ua: "Google Chrome";v="123", "Not:A-Brand";v="8", "Chromium";v="123"' \
   -H 'sec-ch-ua-mobile: ?0' \
   -H 'sec-ch-ua-platform: "Linux"'
EOD;
                    $result = shell_exec(strtr($command, [
                        '___KEYWORD___' => urlencode($clearAddress),
                    ]));
                    $cleanKeyword = trim(strip_tags($result));
                    if (!empty($cleanKeyword)) {
                        $command = <<<EOD
                        curl 'https://api.nlsc.gov.tw/MapSearch/QuerySearch' \
                          -H 'Accept: application/xml, text/xml, */*; q=0.01' \
                          -H 'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7' \
                          -H 'Connection: keep-alive' \
                          -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' \
                          -H 'Origin: https://maps.nlsc.gov.tw' \
                          -H 'Referer: https://maps.nlsc.gov.tw/' \
                          -H 'Sec-Fetch-Dest: empty' \
                          -H 'Sec-Fetch-Mode: cors' \
                          -H 'Sec-Fetch-Site: same-site' \
                          -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36' \
                          -H 'sec-ch-ua: "Google Chrome";v="123", "Not:A-Brand";v="8", "Chromium";v="123"' \
                          -H 'sec-ch-ua-mobile: ?0' \
                          -H 'sec-ch-ua-platform: "Linux"' \
                          --data-raw 'word=___KEYWORD___&feedback=XML&center=120.218280%2C23.007292'
                        EOD;
                        $result = shell_exec(strtr($command, [
                            '___KEYWORD___' => urlencode(urlencode($cleanKeyword)),
                        ]));
                        $json = json_decode(json_encode(simplexml_load_string($result)), true);
                        if (!empty($json['ITEM']['LOCATION'])) {
                            $parts = explode(',', $json['ITEM']['LOCATION']);
                            if (count($parts) === 2) {
                                file_put_contents($geocodingFile, json_encode([
                                    'AddressList' => [
                                        [
                                            'X' => $parts[0],
                                            'Y' => $parts[1],
                                        ],
                                    ],
                                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                echo "[{$addressCount}]{$clearAddress}\n";
                            }
                        }
                    }
                }
                if (file_exists($geocodingFile)) {
                    $geo = json_decode(file_get_contents($geocodingFile), true);
                    if (!empty($geo['AddressList'][0]['X'])) {
                        $point = [
                            floatval($geo['AddressList'][0]['Y']),
                            floatval($geo['AddressList'][0]['X']),
                        ];
                    }
                }
            }
        }
        if (!empty($point)) {
            if (!isset($pool[$city])) {
                $pool[$city] = fopen($poolPath . '/' . $city . '.csv', 'w');
                fputcsv($pool[$city], ['latitude', 'longitude', 'properties']);
            }
            if ($point[0] > $point[1]) {
                $tmp = $point[0];
                $point[0] = $point[1];
                $point[1] = $tmp;
            }
            fputcsv($pool[$city], [$point[0], $point[1], json_encode($data, JSON_UNESCAPED_UNICODE)]);
        }
    }
}
