# Taiwan Air Raid Shelter Data Project

This project serves as the data source for [https://kiang.github.io/air_raid_shelter/](https://kiang.github.io/air_raid_shelter/), providing comprehensive information about air raid shelters across Taiwan.

## Overview

This project extracts, processes, and geocodes air raid shelter data from the National Police Agency of Taiwan (NPA) to create accessible datasets in multiple formats (KML, CSV, JSON) for mapping and analysis purposes.

## Data Source

The original data is sourced from the National Police Agency's air raid shelter information portal:
- **URL**: https://adr.npa.gov.tw/
- **Content**: Official registry of air raid shelters across Taiwan, organized by city and district

## Project Structure

```
├── csv/                          # Processed CSV files
│   ├── pool/                     # Geocoded data by city (22 cities/counties)
│   └── [city]_[district]*.csv    # Raw CSV data by district
├── docs/
│   └── json/                     # GeoJSON files for web mapping
├── kml/                          # Original KML files from Google My Maps
├── raw/
│   ├── geocoding/                # Geocoding results cache
│   ├── list.csv                  # Processing log
│   └── page.html                 # Original webpage content
└── scripts/                      # Data processing scripts
    ├── 01_parse.php              # Extract Google My Maps links and download KML
    ├── 02_kml2csv.php            # Convert KML to CSV format
    ├── 03_geocoding.php          # Geocode addresses and consolidate data
    ├── 04_pool2json.php          # Convert to GeoJSON format
    └── _config.php               # Configuration file
```

## Data Processing Workflow

### 1. Data Extraction (`01_parse.php`)
- Scrapes the NPA website to extract Google My Maps links
- Downloads KML files for each city/district
- Creates processing log in `raw/list.csv`

### 2. KML to CSV Conversion (`02_kml2csv.php`)
- Parses KML files and extracts shelter information
- Converts to CSV format with proper headers
- Handles multiple folders within KML files

### 3. Geocoding and Consolidation (`03_geocoding.php`)
- Processes addresses for consistent formatting
- Performs geocoding using TGOS API (Taiwan Government Open Spatial Data)
- Consolidates data by city/county into `csv/pool/` directory
- Caches geocoding results to avoid duplicate API calls

### 4. GeoJSON Generation (`04_pool2json.php`)
- Converts consolidated CSV data to GeoJSON format
- Creates web-ready geographic data files in `docs/json/`

## Data Fields

Each shelter record contains the following information:

| Field | Description | Example |
|-------|-------------|---------|
| 類別 | Shelter type | 一般住宅 (Residential), 一般大樓 (Commercial Building) |
| 電腦編號 | Computer ID | WOA01073 |
| 村里別 | Village/Borough | 三愛里 |
| 地址 | Address | 臺北市中正區臨沂街75巷6號 |
| 緯經度 | Coordinates | 25.034645,121.528668 |
| 地下樓層數 | Underground floors | B01, B02 |
| 可容納人數 | Capacity | 33.0 |
| 轄管分局 | Police precinct | 中正第一分局 |
| 備註 | Remarks | Additional notes |

## Coverage

The dataset covers all 22 cities and counties in Taiwan:
- 臺北市, 新北市, 桃園市, 臺中市, 臺南市, 高雄市 (6 special municipalities)
- 基隆市, 新竹市, 嘉義市 (3 provincial cities)
- 宜蘭縣, 新竹縣, 苗栗縣, 彰化縣, 南投縣, 雲林縣, 嘉義縣, 屏東縣, 臺東縣, 花蓮縣, 澎湖縣, 金門縣, 連江縣 (13 counties)

## Usage

### For Web Development
Use the GeoJSON files in `docs/json/` for web mapping applications:
```javascript
// Example: Load Taipei City shelters
fetch('./docs/json/臺北市.json')
  .then(response => response.json())
  .then(data => {
    // Process GeoJSON data
    console.log(`Loaded ${data.features.length} shelters`);
  });
```

### For Data Analysis
Use the consolidated CSV files in `csv/pool/` for statistical analysis:
```bash
# Count shelters by city
wc -l csv/pool/*.csv

# Example output format for each city CSV:
# latitude,longitude,properties
# 25.034645,121.528668,"{""類別"":""一般住宅"", ...}"
```

### Running the Scripts

1. **Install Dependencies**:
   ```bash
   cd scripts
   composer install
   ```

2. **Configure API Access** (optional for geocoding):
   ```php
   // Edit scripts/_config.php
   return [
       'tgos' => [
           'APPID' => 'your_app_id',
           'APIKey' => 'your_api_key',
       ],
   ];
   ```

3. **Run Processing Pipeline**:
   ```bash
   php scripts/01_parse.php      # Extract and download KML files
   php scripts/02_kml2csv.php    # Convert to CSV
   php scripts/03_geocoding.php  # Geocode and consolidate
   php scripts/04_pool2json.php  # Generate GeoJSON
   ```

## Dependencies

- **PHP 7.4+** with curl extension
- **Composer** for dependency management
- **Symfony Components**:
  - symfony/browser-kit
  - symfony/http-client
  - symfony/dom-crawler

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source. The original data belongs to the National Police Agency of Taiwan.

## Related Projects

- **Web Interface**: [https://kiang.github.io/air_raid_shelter/](https://kiang.github.io/air_raid_shelter/)
- **Data Visualization**: Interactive map showing shelter locations and details

## Contact

For questions or issues, please open an issue on the GitHub repository.

---

**Note**: This data is for informational purposes. For official and up-to-date information, please refer to the original NPA source at https://adr.npa.gov.tw/