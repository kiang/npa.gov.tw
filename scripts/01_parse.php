<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

// Initialize browser
$browser = new HttpBrowser(HttpClient::create([
    'timeout' => 60,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]
]));

// Create output directories
$kmlPath = $basePath . '/kml';
$rawPath = $basePath . '/raw';
if (!file_exists($kmlPath)) {
    mkdir($kmlPath, 0777, true);
}
if (!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}

// Fetch the main page
$url = 'https://adr.npa.gov.tw/';
echo "Fetching main page: $url\n";

try {
    $crawler = $browser->request('GET', $url);
    $content = $browser->getResponse()->getContent();
    
    // Save raw HTML
    $rawFile = $rawPath . '/page.html';
    file_put_contents($rawFile, $content);
    echo "Saved raw HTML to: $rawFile\n";
    
    // Parse the table to extract Google My Maps links
    $listFh = fopen($rawPath . '/list.csv', 'w');
    fputcsv($listFh, ['序號', '縣市', '分區', 'Google Maps URL', 'KML URL', 'KML 檔案', '狀態']);
    
    // Find the table with Google My Maps links using XPath
    $table = $crawler->filterXPath('//table[@class="ed_table"]');
    if ($table->count() === 0) {
        throw new Exception('找不到包含 Google My Maps 連結的表格');
    }
    
    $rows = $table->filterXPath('.//tr');
    $currentCity = '';
    $rowNumber = 0;
    
    foreach ($rows as $i => $row) {
        // Skip header row
        if ($i === 0) continue;
        
        $rowCrawler = new Crawler($row);
        $cells = $rowCrawler->filterXPath('.//td');
        
        if ($cells->count() === 0) continue;
        
        $rowData = [];
        foreach ($cells as $cell) {
            $cellCrawler = new Crawler($cell);
            $text = trim($cellCrawler->text());
            $rowData[] = $text;
        }
        
        // Extract Google My Maps link using XPath
        $linkCell = $rowCrawler->filterXPath('.//td//a[contains(@href, "google.com/maps/d")]');
        if ($linkCell->count() === 0) continue;
        
        $googleMapsUrl = $linkCell->attr('href');
        
        // Parse the data based on number of columns
        if (count($rowData) === 4) {
            // Row with sequence number (new city)
            $rowNumber = $rowData[0];
            $currentCity = strip_tags($rowData[1]);
            $district = strip_tags($rowData[2]);
        } elseif (count($rowData) === 2) {
            // Row without sequence number (same city, different district)
            $district = strip_tags($rowData[0]);
        } else {
            continue;
        }
        
        // Extract map ID from Google My Maps URL
        $mapId = '';
        if (preg_match('/[?&]mid=([^&]+)/', $googleMapsUrl, $matches)) {
            $mapId = $matches[1];
        } elseif (preg_match('/\/d\/([^\/\?]+)/', $googleMapsUrl, $matches)) {
            $mapId = $matches[1];
        }
        
        if (empty($mapId)) {
            echo "警告：無法從以下網址提取地圖 ID：$googleMapsUrl\n";
            continue;
        }
        
        // Clean city and district names for filename
        $cleanCity = preg_replace('/[^\p{L}\p{N}\s]/u', '', $currentCity);
        $cleanDistrict = preg_replace('/[^\p{L}\p{N}\s]/u', '', $district);
        
        // Generate KML URL and filename
        $kmlUrl = "https://www.google.com/maps/d/u/0/kml?mid={$mapId}&forcekml=1";
        $filename = "{$cleanCity}_{$cleanDistrict}.kml";
        $targetFile = $kmlPath . '/' . $filename;
        
        echo "處理：$currentCity - $district\n";
        echo "地圖 ID：$mapId\n";
        echo "KML URL：$kmlUrl\n";
        
        // Download KML file
        $status = 'error';
        try {
            $kmlResponse = $browser->request('GET', $kmlUrl);
            $kmlContent = $browser->getResponse()->getContent();
            
            // Check if we got a valid KML file
            if (strpos($kmlContent, '<?xml') !== false && 
                strpos($kmlContent, '<kml') !== false) {
                file_put_contents($targetFile, $kmlContent);
                $status = 'success';
                echo "成功下載 KML：$targetFile\n";
            } elseif (strpos($kmlContent, '你沒有存取這個文件的權限') !== false ||
                     strpos($kmlContent, 'You do not have permission') !== false) {
                $status = 'permission_denied';
                echo "錯誤：沒有權限存取地圖：$mapId\n";
            } else {
                $status = 'invalid_response';
                echo "錯誤：收到無效的 KML 回應\n";
            }
        } catch (Exception $e) {
            echo "錯誤：下載 KML 時發生例外：" . $e->getMessage() . "\n";
        }
        
        // Write to CSV log
        fputcsv($listFh, [
            $rowNumber,
            $currentCity,
            $district,
            $googleMapsUrl,
            $kmlUrl,
            $filename,
            $status
        ]);
        
        // Add a small delay to avoid overwhelming the server
        usleep(500000); // 0.5 second delay
    }
    
    fclose($listFh);
    echo "處理完成。日誌檔案：{$rawPath}/list.csv\n";
    
} catch (Exception $e) {
    echo "錯誤：" . $e->getMessage() . "\n";
    exit(1);
}