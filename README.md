# Global Supply Chain Risk Intelligence Platform

Global Supply Chain Risk Intelligence Platform adalah aplikasi berbasis web untuk memantau dan menganalisis risiko yang dapat memengaruhi rantai pasok global.

Sistem ini mengintegrasikan data negara, kondisi ekonomi, cuaca, nilai tukar mata uang, berita global, lokasi pelabuhan, dan risk scoring ke dalam satu dashboard analitik untuk membantu proses monitoring serta pengambilan keputusan bisnis.

Project ini dikembangkan sebagai platform monitoring risiko rantai pasok global berbasis multi-API, dashboard analytics, geospatial visualization, business intelligence, dan simple scoring algorithm.

Platform ini dibangun untuk memantau seluruh indikator tersebut dalam satu sistem agar dapat membantu pengambilan keputusan bisnis.

## Main Features

- Global Country Dashboard
- Risk Scoring Engine
- Global Weather Monitoring
- Currency Impact Dashboard
- News Intelligence
- Lexicon-Based Sentiment Analysis
- Port Location Dashboard
- Data Visualization Dashboard
- Country Comparison Engine
- Favorite Monitoring List
- Admin Dashboard
- REST API

---

## Technology Stack

### Backend

- PHP 8.4
- Laravel 13
- MySQL

### Frontend

- Bootstrap 5
- AJAX
- JavaScript ES6
- Blade Template Engine

### Data Visualization

- Chart.js
- Leaflet.js
- OpenStreetMap

### Version Control

- Git
- GitHub

---

## External Data Sources

Project ini menggunakan beberapa API dan dataset eksternal:

- Open-Meteo API
- World Bank API
- REST Countries API
- ExchangeRate API
- GNews API
- World Port Index Dataset
- OpenStreetMap

---

## Feature Details

### 1. Global Country Dashboard

User dapat memilih negara seperti Germany, China, Indonesia, atau Australia.

Sistem menampilkan data utama negara:

- GDP
- Inflasi
- Populasi
- Mata uang
- Cuaca saat ini

Data tambahan yang juga dapat ditampilkan:

- Ekspor
- Impor
- Kurs
- Sentimen berita
- Risk Score

Route:

```text
/countries
/api/countries
```

---

### 2. Risk Scoring Engine

Sistem menghitung risiko rantai pasok menggunakan weighted risk model.

Formula:

```text
Risk Score =
Weather Risk +
Inflation Risk +
Exchange Rate Risk +
News Sentiment Risk
```

Bobot perhitungan:

```text
Weather Risk       = 30%
Inflation Risk     = 20%
Exchange Rate Risk = 10%
News Sentiment     = 40%
```

Output risk level:

```text
0 - 24    = Low Risk
25 - 49   = Medium Risk
50 - 74   = High Risk
75 - 100  = Critical Risk
```

Route:

```text
/risk
/api/risk
```

---

### 3. Global Weather Monitoring

Halaman ini digunakan untuk memantau kondisi cuaca global berdasarkan negara yang dipilih.

Data yang ditampilkan:

- Temperatur
- Curah hujan
- Kecepatan angin
- Kondisi cuaca
- Risiko cuaca
- Peta interaktif

Route:

```text
/weather
/api/weather
```

---

### 4. Currency Impact Dashboard

Halaman ini digunakan untuk memantau dampak perubahan nilai tukar mata uang terhadap risiko rantai pasok.

Data yang ditampilkan:

- Nilai tukar
- Perubahan kurs
- Risiko kurs
- Grafik perubahan kurs menggunakan Chart.js

Route:

```text
/currency
/api/currency
```

---

### 5. News Intelligence

Halaman ini digunakan untuk menampilkan berita terkait ekonomi, logistik, perdagangan, shipping, dan geopolitik.

Kategori berita yang digunakan:

- Logistics
- Trade
- Shipping
- Economy
- Geopolitical issue

Route:

```text
/news
/api/news
```

---

### 6. Lexicon-Based Sentiment Analysis

Sistem melakukan analisis sentimen berita menggunakan kamus kata positif dan negatif.

Output analisis:

- Positive
- Neutral
- Negative
- News risk score

Contoh proses:

```text
Inflation increases while exports decrease due to war.

Positive word:
- increases

Negative words:
- inflation
- decrease
- war

Result:
Negative sentiment
```

---

### 7. Port Location Dashboard

Halaman ini digunakan untuk menampilkan lokasi pelabuhan dunia menggunakan World Port Index Dataset.

Fitur:

- Cari pelabuhan
- Cari negara
- Marker pelabuhan interaktif
- Peta Leaflet.js
- Data koordinat pelabuhan

Route:

```text
/ports
/api/ports
```

---

### 8. Data Visualization Dashboard

Halaman ini digunakan untuk menampilkan grafik perkembangan data dari waktu ke waktu.

Grafik yang ditampilkan:

- GDP Trend
- Inflation Trend
- Currency Trend
- Risk Trend

Route:

```text
/visualization
/api/visualization
```

---

### 9. Country Comparison Engine

Halaman ini digunakan untuk membandingkan dua negara.

Data yang dibandingkan:

- GDP
- Inflation
- Risk
- Weather
- Currency

Contoh:

```text
Germany vs Australia
```

Route:

```text
/comparison
/api/comparison
```

---

### 10. Favorite Monitoring List

User dapat menyimpan negara yang ingin dipantau secara khusus.

Fitur:

- Simpan negara ke daftar favorit
- Hapus negara dari daftar favorit
- Melihat daftar negara favorit
- Melihat ringkasan risiko negara favorit

Route:

```text
/watchlist
/api/watchlist
```

---

### 11. Admin Dashboard

Admin dapat mengelola dan memantau data sistem.

Fitur:

- Kelola user
- Kelola dataset pelabuhan
- Kelola artikel berita / analisis
- Monitoring data sistem

Route:

```text
/admin
```

---

## REST API Endpoints

Endpoint utama yang tersedia:

```text
GET /api/countries
GET /api/risk
GET /api/ports
GET /api/news
GET /api/currency
```

Endpoint tambahan:

```text
GET /api/dashboard
GET /api/weather
GET /api/comparison
GET /api/visualization
GET /api/watchlist
```

---

## Database

Database name:

```text
supply_chain_risk
```

Database dikelola menggunakan Laravel migrations.

Tabel utama yang digunakan dalam sistem:

```text
users
countries
economic_indicators
weather_data
exchange_rates
risk_scores
risk_components
news_caches
news_sentiments
positive_words
negative_words
global_ports
watchlists
articles
```

Catatan:

- `countries` menyimpan data negara dari REST Countries API.
- `economic_indicators` menyimpan data GDP, inflasi, populasi, ekspor, dan impor dari World Bank API.
- `weather_data` menyimpan data cuaca dari Open-Meteo API.
- `exchange_rates` menyimpan data kurs dari ExchangeRate API.
- `risk_scores` menyimpan hasil akhir risk scoring.
- `risk_components` menyimpan detail komponen perhitungan risk score.
- `news_caches` menyimpan cache berita dari GNews API.
- `news_sentiments` menyimpan hasil analisis sentimen berita.
- `positive_words` dan `negative_words` digunakan untuk lexicon-based sentiment analysis.
- `global_ports` menyimpan data pelabuhan dari World Port Index Dataset.
- `watchlists` menyimpan daftar negara favorit user.
- `articles` digunakan untuk data artikel atau analisis pada admin dashboard.

---

## Application Menu Structure

```text
INTELLIGENCE
- Global Overview
- Risk Scoring Engine
- Country Monitoring
- Weather Monitoring
- Currency Impact
- News Intelligence

ANALYTICS
- Data Visualization

LOGISTICS
- Global Ports
- Country Comparison
- Favorite Monitoring List

SYSTEM
- Admin Dashboard
```

---

## Installation

Clone repository:

```bash
git clone <repository-url>
cd supply-chain
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=supply_chain_risk
DB_USERNAME=root
DB_PASSWORD=
```

Configure API keys in `.env`:

```env
EXCHANGE_RATE_API_KEY=your_exchange_rate_api_key
GNEWS_API_KEY=your_gnews_api_key
REST_COUNTRIES_API_KEY=
WORLD_PORT_INDEX_DATASET_PATH=storage/app/datasets/world_port_index.csv
```

Run migration and seeder:

```bash
php artisan migrate --seed
```

Import World Port Index dataset:

```bash
php artisan ports:import-world-port-index
```

Run Laravel server:

```bash
php artisan serve
```

Run Vite:

```bash
npm run dev
```

Access application:

```text
http://127.0.0.1:8000
```

---

## Development Commands

Clear cache:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

Check route list:

```bash
php artisan route:list
```

Check required REST API routes:

```bash
php artisan route:list | findstr /I "api/countries api/risk api/ports api/news api/currency"
```

Check visualization routes:

```bash
php artisan route:list | findstr /I "visualization"
```

---

## Project Status

### Completed

- [x] Laravel 13 project initialization
- [x] MySQL database configuration
- [x] Authentication and authorization
- [x] Country data integration
- [x] World Bank API integration
- [x] Open-Meteo API integration
- [x] ExchangeRate API integration
- [x] GNews API integration
- [x] World Port Index dataset integration
- [x] Global Country Dashboard
- [x] Risk Scoring Engine
- [x] Global Weather Monitoring
- [x] Currency Impact Dashboard
- [x] News Intelligence
- [x] Lexicon-Based Sentiment Analysis
- [x] Port Location Dashboard
- [x] Data Visualization Dashboard
- [x] Country Comparison Engine
- [x] Favorite Monitoring List
- [x] Admin Dashboard
- [x] REST API endpoints
- [x] Chart.js visualization
- [x] Leaflet.js geospatial visualization
- [x] Git repository setup

---

## Final Project Summary

Global Supply Chain Risk Intelligence Platform adalah platform monitoring risiko rantai pasok global yang menggabungkan data ekonomi, cuaca, kurs mata uang, berita global, dan data pelabuhan untuk menghasilkan analisis risiko berbasis dashboard.

Sistem ini bukan aplikasi tracking paket berdasarkan nomor resi, tetapi platform business intelligence dan decision support system untuk membantu pengguna memahami risiko impor, ekspor, dan distribusi global.

---

## Develope
Nama  : Salsabila Umami
NIM   : 240180125
Kelas : A3
Project ini dikembangkan sebagai project final berbasis Full Stack Development, API Integration, Data Engineering, Dashboard Analytics, Geospatial Visualization, Business Intelligence, dan Decision Support System.
