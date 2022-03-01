<?php
$config = require __DIR__ . '/config.php';
$geocodingPath = __DIR__ . '/raw/geocoding';
if (!file_exists($geocodingPath)) {
    mkdir($geocodingPath, 0777, true);
}
$addressCount = 0;

foreach (glob(__DIR__ . '/csv/*.csv') as $csvFile) {
    $p = pathinfo($csvFile);
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
        } elseif (isset($data['備註'])) {
            $point = explode(',', $data['備註']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
            } else {
                $point = [];
            }
        } elseif (isset($data['Location'])) {
            $point = explode(',', $data['Location']);
            if (count($point) === 2) {
                foreach ($point as $k => $v) {
                    $point[$k] = floatval($v);
                }
            }
        }

        if (empty($point)) {
            $lineParts = explode(',', $address);
            if (count($lineParts) === 2) {
                $point = [
                    floatval($lineParts[0]),
                    floatval($lineParts[1]),
                ];
                if (isset($data['備註']) && isset($data['地址'])) {
                    $data['地址'] = $data['備註'];
                    $data['備註'] = '';
                }
                if (empty($point[0])) {
                    $point = [];
                }
            }
        }
        if (empty($point)) {
            if (false !== strpos($p['filename'], '桃園市政府警察局') && !empty($address)) {
                if (false === strpos($address, '桃園市')) {
                    $pos = strpos($address, '區');
                    $address = '桃園市' . substr($address, 0, $pos) . '區' . $data['村里別'] . substr($address, $pos + 3);
                }
            }
            if (!empty($address)) {
                ++$addressCount;
                $pos = strpos($address, '號');
                $clearAddress = substr($address, 0, $pos) . '號';
                $geocodingFile = $geocodingPath . '/' . $clearAddress . '.json';
                if (!file_exists($geocodingFile)) {
                    $apiUrl = $config['tgos']['url'] . '?' . http_build_query([
                        'oAPPId' => $config['tgos']['APPID'], //應用程式識別碼(APPId)
                        'oAPIKey' => $config['tgos']['APIKey'], // 應用程式介接驗證碼(APIKey)
                        'oAddress' => $clearAddress, //所要查詢的門牌位置
                        'oSRS' => 'EPSG:4326', //坐標系統(SRS)EPSG:4326(WGS84)國際通用, EPSG:3825 (TWD97TM119) 澎湖及金馬適用,EPSG:3826 (TWD97TM121) 台灣地區適用,EPSG:3827 (TWD67TM119) 澎湖及金馬適用,EPSG:3828 (TWD67TM121) 台灣地區適用
                        'oFuzzyType' => '2', //0:最近門牌號機制,1:單雙號機制,2:[最近門牌號機制]+[單雙號機制]
                        'oResultDataType' => 'JSON', //回傳的資料格式，允許傳入的代碼為：JSON、XML
                        'oFuzzyBuffer' => '0', //模糊比對回傳門牌號的許可誤差範圍，輸入格式為正整數，如輸入 0 則代表不限制誤差範圍
                        'oIsOnlyFullMatch' => 'false', //是否只進行完全比對，允許傳入的值為：true、false，如輸入 true ，模糊比對機制將不被使用
                        'oIsSupportPast' => 'true', //是否支援舊門牌的查詢，允許傳入的值為：true、false，如輸入 true ，查詢時範圍包含舊門牌
                        'oIsShowCodeBase' => 'true', //是否顯示地址的統計區相關資訊，允許傳入的值為：true、false
                        'oIsLockCounty' => 'true', //是否鎖定縣市，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [縣市] 要與所輸入的門牌地址中的 [縣市] 完全相同
                        'oIsLockTown' => 'false', //是否鎖定鄉鎮市區，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [鄉鎮市區] 要與所輸入的門牌地址中的 [鄉鎮市區] 完全相同
                        'oIsLockVillage' => 'false', //是否鎖定村里，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [村里] 要與所輸入的門牌地址中的 [村里] 完全相同
                        'oIsLockRoadSection' => 'false', //是否鎖定路段，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [路段] 要與所輸入的門牌地址中的 [路段] 完全相同
                        'oIsLockLane' => 'false', //是否鎖定巷，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [巷] 要與所輸入的門牌地址中的 [巷] 完全相同
                        'oIsLockAlley' => 'false', //是否鎖定弄，允許傳入的值為：true、false，如輸入 true ，則代表查詢結果中的 [弄] 要與所輸入的門牌地址中的 [弄] 完全相同
                        'oIsLockArea' => 'false', //是否鎖定地區，允許傳入的值為：true、fals，如輸入 true ，則代表查詢結果中的 [地區] 要與所輸入的門牌地址中的 [地區] 完全相同
                        'oIsSameNumber_SubNumber' => 'true', //號之、之號是否視為相同，允許傳入的值為：true、false
                        'oCanIgnoreVillage' => 'true', //找不時是否可忽略村里，允許傳入的值為：true、false
                        'oCanIgnoreNeighborhood' => 'true', //找不時是否可忽略鄰，允許傳入的值為：true、false
                        'oReturnMaxCount' => '0', //如為多筆時，限制回傳最大筆數，輸入格式為正整數，如輸入 0 則代表不限制回傳筆數
                    ]);
                    $content = file_get_contents($apiUrl);
                    $pos = strpos($content, '{');
                    $posEnd = strrpos($content, '}') + 1;
                    $resultline = substr($content, $pos, $posEnd - $pos);
                    if (strlen($resultline) > 10) {
                        file_put_contents($geocodingFile, substr($content, $pos, $posEnd - $pos));
                        echo "[{$addressCount}]{$clearAddress}\n";
                    } elseif(false !== strpos($content, '資料行溢位')) {
                        continue;
                    } elseif(false !== strpos($content, '語法不正確')) {
                        continue;
                    } else {
                        echo $content . "\n";
                        exit();
                    }
                }
            }
        }
    }
}
