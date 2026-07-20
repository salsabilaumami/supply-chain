# 🌍 Global Supply Chain Risk Intelligence Platform

**Global Supply Chain Risk Intelligence Platform** adalah aplikasi web berbasis **Laravel** yang digunakan untuk memantau, menganalisis, dan memvisualisasikan risiko yang dapat memengaruhi aktivitas **rantai pasok global**.

Platform ini mengintegrasikan data negara, indikator ekonomi, cuaca, nilai tukar mata uang, berita global, lokasi pelabuhan, risk scoring, grafik analitik, perbandingan negara, daftar favorit, dashboard admin, serta fitur kreativitas berupa **Port Route Estimator**.

Sistem ini bukan aplikasi pelacakan paket berdasarkan nomor resi, tetapi merupakan platform **Business Intelligence** dan **Decision Support System** untuk membantu pengguna memahami potensi risiko dalam aktivitas impor, ekspor, distribusi, dan rantai pasok global.

---

## 📌 Project Identity

| Keterangan | Detail |
|---|---|
| Nama Project | Global Supply Chain Risk Intelligence Platform |
| Jenis Project | Web Application |
| Framework | Laravel 13 |
| Bahasa Pemrograman | PHP 8.4, JavaScript |
| Database | MySQL |
| Fokus Sistem | Supply Chain Risk Monitoring |
| Visualisasi | Chart.js, Leaflet.js, OpenStreetMap |
| Fitur Kreativitas | Port Route Estimator |
| Developer | Salsabila Umami |
| NIM | 240180125 |
| Kelas | A3 |

---

## 🎯 Project Objective

Tujuan utama project ini adalah membangun sistem monitoring risiko rantai pasok global yang mampu menampilkan data penting dalam satu platform, seperti:

| Indikator | Fungsi |
|---|---|
| GDP | Melihat kekuatan ekonomi negara |
| Inflasi | Mengukur tekanan ekonomi dan biaya distribusi |
| Populasi | Melihat skala pasar dan aktivitas negara |
| Mata Uang | Melihat dampak perubahan kurs |
| Cuaca | Menganalisis potensi gangguan logistik |
| Berita Global | Menganalisis sentimen dan risiko eksternal |
| Pelabuhan | Melihat lokasi dan risiko pelabuhan |
| Risk Score | Menghasilkan skor risiko rantai pasok |
| Route Estimator | Mengestimasi jarak dan risiko rute antar pelabuhan |

---

## ✨ Main Features

| No | Fitur | Status |
|---|---|---|
| 1 | 🌐 Global Overview | ✅ Selesai |
| 2 | 🏳️ Global Country Dashboard | ✅ Selesai |
| 3 | 🛡️ Risk Scoring Engine | ✅ Selesai |
| 4 | 🌦️ Global Weather Monitoring | ✅ Selesai |
| 5 | 💱 Currency Impact Dashboard | ✅ Selesai |
| 6 | 📰 News Intelligence | ✅ Selesai |
| 7 | 🧠 Lexicon-Based Sentiment Analysis | ✅ Selesai |
| 8 | 📊 Data Visualization Dashboard | ✅ Selesai |
| 9 | ⚖️ Country Comparison Engine | ✅ Selesai |
| 10 | ⚓ Global Ports | ✅ Selesai |
| 11 | 🧭 Port Route Estimator | ✅ Selesai |
| 12 | ⭐ Favorite Monitoring List | ✅ Selesai |
| 13 | 👨‍💼 Admin Dashboard | ✅ Selesai |
| 14 | 🔐 Authentication System | ✅ Selesai |
| 15 | 🔗 REST API | ✅ Selesai |

---

## 🛠️ Technology Stack

### Backend

| Teknologi | Keterangan |
|---|---|
| PHP 8.4 | Bahasa pemrograman backend |
| Laravel 13 | Framework utama aplikasi |
| MySQL | Database relasional |
| Eloquent ORM | Pengelolaan model dan database |
| Laravel Migration | Pengelolaan struktur tabel |
| Laravel Seeder | Pengisian data awal |

### Frontend

| Teknologi | Keterangan |
|---|---|
| Blade Template Engine | Template frontend Laravel |
| Bootstrap 5 | Styling dan komponen UI |
| JavaScript ES6 | Interaktivitas halaman |
| AJAX | Pengambilan data tanpa reload penuh |

### Data Visualization

| Library | Fungsi |
|---|---|
| Chart.js | Grafik tren dan perbandingan data |
| Leaflet.js | Peta interaktif |
| OpenStreetMap | Tile map untuk peta global |

### Version Control

| Teknologi | Keterangan |
|---|---|
| Git | Version control |
| GitHub | Repository online |

---

## 🌐 External Data Sources

| Sumber Data | Digunakan Untuk |
|---|---|
| Open-Meteo API | Data cuaca global |
| World Bank API | GDP, inflasi, populasi, ekspor, impor |
| REST Countries API | Data negara, bendera, wilayah, mata uang |
| ExchangeRate API | Nilai tukar mata uang |
| GNews API | Berita global |
| World Port Index Dataset | Data pelabuhan dunia |
| OpenStreetMap | Visualisasi peta |

---

## 🔄 System Workflow

Alur kerja sistem:

    API / Dataset Eksternal
            ↓
    Laravel Service Layer
            ↓
    Database / Cache
            ↓
    Controller
            ↓
    Blade Dashboard
            ↓
    Chart / Map / Table
            ↓
    Business Insight & Risk Decision

Sistem mengambil data dari API dan dataset eksternal, menyimpannya ke database atau cache, kemudian mengolahnya menjadi dashboard, grafik, peta, tabel, dan risk score.

---

## 📂 Application Menu Structure

| Group Menu | Menu |
|---|---|
| Intelligence | Global Overview |
| Intelligence | Risk Scoring Engine |
| Intelligence | Country Monitoring |
| Intelligence | Weather Monitoring |
| Intelligence | Currency Impact |
| Intelligence | News Intelligence |
| Analytics | Data Visualization |
| Logistics | Global Ports |
| Logistics | Country Comparison |
| Logistics | Favorite Monitoring List |
| System | Admin Dashboard |

---

## 🧩 Feature Details

## 1. 🌐 Global Overview

Global Overview adalah halaman utama setelah user login.

Halaman ini dibuat ringkas dalam bentuk card agar user langsung dapat melihat indikator penting negara yang dipilih.

### Data yang Ditampilkan

| Data | Keterangan |
|---|---|
| GDP | Produk Domestik Bruto negara |
| Inflasi | Tingkat inflasi terbaru |
| Populasi | Jumlah penduduk |
| Mata Uang | Kode dan nama mata uang |
| Cuaca Saat Ini | Temperatur, hujan, dan angin |
| Total Risk Score | Skor risiko total negara |
| Weather Risk | Risiko dari kondisi cuaca |
| Inflation Risk | Risiko dari inflasi |
| Exchange Rate Risk | Risiko dari nilai tukar |
| News Sentiment Risk | Risiko dari sentimen berita |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | / | Halaman Global Overview |
| GET | /api/dashboard | Data dashboard dalam format JSON |

---

## 2. 🏳️ Global Country Dashboard

Global Country Dashboard digunakan untuk memantau detail negara berdasarkan pilihan user.

User dapat memilih negara seperti:

| Contoh Negara |
|---|
| Indonesia |
| Germany |
| China |
| Australia |

### Data Utama

| Data | Keterangan |
|---|---|
| GDP | Nilai ekonomi negara |
| Inflasi | Tingkat inflasi negara |
| Populasi | Jumlah penduduk |
| Mata Uang | Kode dan nama mata uang |
| Cuaca Saat Ini | Temperatur, curah hujan, dan angin |

### Data Tambahan

| Data | Keterangan |
|---|---|
| Ekspor | Nilai ekspor barang dan jasa |
| Impor | Nilai impor barang dan jasa |
| Kurs | Nilai tukar terhadap USD |
| Berita | Sentimen berita terkait negara |
| Risk Score | Skor risiko negara |
| Grafik | Visualisasi pendukung |
| Sinkronisasi | Update data ekonomi, cuaca, kurs, dan berita |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /countries | Halaman Country Monitoring |
| GET | /api/countries | Data country dalam format JSON |

---

## 3. 🛡️ Risk Scoring Engine

Risk Scoring Engine digunakan untuk menghitung risiko rantai pasok berdasarkan beberapa indikator utama.

### Komponen Risk Score

| Komponen | Bobot |
|---|---:|
| Weather Risk | 30% |
| Inflation Risk | 20% |
| Exchange Rate Risk | 10% |
| News Sentiment Risk | 40% |

### Formula

    Risk Score =
    (Weather Risk × 30%)
    + (Inflation Risk × 20%)
    + (Exchange Rate Risk × 10%)
    + (News Sentiment Risk × 40%)

### Risk Level

| Score | Level |
|---:|---|
| 0 - 24 | Low Risk |
| 25 - 49 | Medium Risk |
| 50 - 74 | High Risk |
| 75 - 100 | Critical Risk |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /risk | Halaman Risk Scoring Engine |
| GET | /api/risk | Data risk dalam format JSON |

---

## 4. 🌦️ Global Weather Monitoring

Global Weather Monitoring digunakan untuk memantau kondisi cuaca negara yang dipilih.

### Data Cuaca

| Data | Keterangan |
|---|---|
| Temperatur | Suhu saat ini |
| Curah Hujan | Presipitasi |
| Kecepatan Angin | Kecepatan angin dalam km/jam |
| Kondisi Cuaca | Deskripsi kondisi cuaca |
| Weather Risk | Skor risiko cuaca |
| Peta Interaktif | Visualisasi lokasi negara |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /weather | Halaman Weather Monitoring |
| GET | /api/weather | Data weather dalam format JSON |

---

## 5. 💱 Currency Impact Dashboard

Currency Impact Dashboard digunakan untuk memantau dampak perubahan nilai tukar mata uang terhadap risiko rantai pasok.

### Data Currency

| Data | Keterangan |
|---|---|
| Base Currency | Mata uang dasar, misalnya USD |
| Target Currency | Mata uang negara tujuan |
| Exchange Rate | Nilai tukar terbaru |
| Change Percentage | Persentase perubahan kurs |
| Currency Risk | Skor risiko kurs |
| Currency Converter | Konversi nilai mata uang |
| Chart.js | Grafik perubahan kurs |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /currency | Halaman Currency Impact |
| GET | /api/currency | Data currency dalam format JSON |

---

## 6. 📰 News Intelligence

News Intelligence digunakan untuk menampilkan berita yang berhubungan dengan ekonomi, logistik, perdagangan, shipping, dan geopolitikal.

### Kategori Berita

| Kategori | Keterangan |
|---|---|
| Logistics | Berita logistik |
| Trade | Berita perdagangan |
| Shipping | Berita pengiriman dan pelabuhan |
| Economy | Berita ekonomi |
| Geopolitical Issue | Berita geopolitik |

### Data yang Ditampilkan

| Data | Keterangan |
|---|---|
| Judul Berita | Headline berita |
| Sumber | Nama media |
| Tanggal Publikasi | Waktu berita diterbitkan |
| Kategori | Jenis berita |
| Sentiment | Positive, Neutral, Negative |
| News Risk Score | Skor risiko berita |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /news | Halaman News Intelligence |
| GET | /api/news | Data news dalam format JSON |

---

## 7. 🧠 Lexicon-Based Sentiment Analysis

Sistem menggunakan pendekatan sederhana berbasis kamus kata positif dan negatif untuk menganalisis sentimen berita.

### Output Sentiment

| Output | Keterangan |
|---|---|
| Positive | Berita cenderung positif |
| Neutral | Berita netral |
| Negative | Berita cenderung berisiko |
| News Risk Score | Skor risiko berdasarkan sentimen |

### Contoh Analisis

    Kalimat:
    Inflation increases while exports decrease due to war.

    Positive word:
    - increases

    Negative words:
    - inflation
    - decrease
    - war

    Result:
    Negative sentiment

Sentimen berita digunakan sebagai salah satu komponen dalam perhitungan Risk Scoring Engine.

---

## 8. 📊 Data Visualization Dashboard

Data Visualization Dashboard digunakan untuk menampilkan grafik perkembangan data dari waktu ke waktu.

### Grafik Utama

| Grafik | Keterangan |
|---|---|
| GDP Trend | Perkembangan GDP |
| Inflation Trend | Perkembangan inflasi |
| Currency Trend | Perkembangan nilai tukar |
| Risk Trend | Perkembangan risk score |

Halaman ini menjadi pusat visualisasi grafik agar halaman Global Overview tetap ringkas.

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /visualization | Halaman Data Visualization |
| GET | /api/visualization | Data visualization dalam format JSON |

---

## 9. ⚖️ Country Comparison Engine

Country Comparison Engine digunakan untuk membandingkan indikator antarnegara.

### Data Perbandingan

| Data | Keterangan |
|---|---|
| GDP | Perbandingan ekonomi |
| Inflation | Perbandingan inflasi |
| Risk Score | Perbandingan risiko |
| Weather Risk | Perbandingan risiko cuaca |
| Currency Risk | Perbandingan risiko kurs |

### Contoh

    Germany vs Australia
    China vs Indonesia

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /comparison | Halaman Country Comparison |
| GET | /api/comparison | Data comparison dalam format JSON |

---

## 10. ⚓ Global Ports

Global Ports digunakan untuk menampilkan data lokasi pelabuhan dunia berdasarkan World Port Index Dataset.

### Fitur Utama

| Fitur | Keterangan |
|---|---|
| Search Country | Memilih negara |
| Search Port | Mencari pelabuhan |
| Port List | Daftar pelabuhan utama |
| Leaflet Map | Peta interaktif lokasi pelabuhan |
| Port Marker | Marker pelabuhan |
| Port Risk Score | Skor risiko pelabuhan |
| Risk Chart | Grafik risiko pelabuhan |

### Data Pelabuhan

| Data | Keterangan |
|---|---|
| Port Name | Nama pelabuhan |
| Port Code | Kode pelabuhan |
| City | Kota pelabuhan |
| Latitude | Koordinat latitude |
| Longitude | Koordinat longitude |
| Capacity Score | Skor kapasitas |
| Congestion Score | Skor kepadatan |
| Weather Exposure Score | Skor paparan cuaca |
| Risk Score | Skor risiko pelabuhan |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /ports | Halaman Global Ports |
| GET | /api/ports | Data ports dalam format JSON |

---

## 11. 🧭 Port Route Estimator

Port Route Estimator adalah fitur kreativitas tambahan pada halaman Global Ports.

Fitur ini memungkinkan user memilih port asal dan port tujuan untuk mendapatkan estimasi rute awal.

### Input

| Input | Keterangan |
|---|---|
| Port Asal | Pelabuhan keberangkatan |
| Port Tujuan | Pelabuhan tujuan |

### Output

| Output | Keterangan |
|---|---|
| Nama Port Asal | Pelabuhan awal |
| Nama Port Tujuan | Pelabuhan akhir |
| Negara Asal | Negara port asal |
| Negara Tujuan | Negara port tujuan |
| Estimasi Jarak Laut | Jarak estimasi dalam kilometer |
| Nautical Miles | Jarak estimasi dalam mil laut |
| Estimasi Waktu | Perkiraan waktu perjalanan |
| Route Risk Score | Risiko rute |
| Rekomendasi | Saran penggunaan rute |

### Metode Perhitungan

    Koordinat port asal
            +
    Koordinat port tujuan
            ↓
    Haversine Distance
            ↓
    Estimasi jarak laut
            ↓
    Estimasi nautical miles
            ↓
    Estimasi durasi berdasarkan kecepatan kapal
            ↓
    Route Risk Recommendation

### Catatan

Port Route Estimator bukan sistem pelacakan kapal real-time. Fitur ini digunakan sebagai estimasi awal untuk membantu analisis rute logistik.

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /ports | Halaman Global Ports dan Route Estimator |
| GET | /api/ports | Data ports dan route estimator dalam format JSON |

---

## 12. ⭐ Favorite Monitoring List

Favorite Monitoring List digunakan agar user dapat menyimpan negara yang ingin dipantau secara khusus.

### Fitur

| Fitur | Keterangan |
|---|---|
| Add Favorite | Menambahkan negara ke daftar favorit |
| Remove Favorite | Menghapus negara dari daftar favorit |
| Watchlist Summary | Melihat ringkasan negara favorit |
| Risk Monitoring | Memantau risk score negara favorit |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /watchlist | Halaman Favorite Monitoring List |
| GET | /api/watchlist | Data watchlist dalam format JSON |

---

## 13. 👨‍💼 Admin Dashboard

Admin Dashboard digunakan untuk mengelola dan memantau data sistem.

### Fitur Admin

| Fitur | Keterangan |
|---|---|
| Manage Users | Mengelola user |
| User Status | Mengaktifkan atau menonaktifkan user |
| Port Dataset | Memantau data pelabuhan |
| News / Analysis | Memantau berita dan artikel analisis |
| System Summary | Melihat ringkasan data sistem |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /admin | Halaman Admin Dashboard |

---

## 14. 🔐 Authentication System

Sistem menggunakan fitur authentication untuk login, register, dan logout.

### Alur Authentication

| Proses | Output |
|---|---|
| Register | Kembali ke halaman login |
| Login | Masuk ke halaman utama |
| Logout | Kembali ke halaman login |

### Route

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /login | Form login |
| POST | /login | Proses login |
| GET | /register | Form register |
| POST | /register | Proses register |
| POST | /logout | Proses logout |

---

## 🔗 REST API Endpoints

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | /api/dashboard | Data Global Overview |
| GET | /api/countries | Data Country Monitoring |
| GET | /api/risk | Data Risk Scoring |
| GET | /api/weather | Data Weather Monitoring |
| GET | /api/currency | Data Currency Impact |
| GET | /api/news | Data News Intelligence |
| GET | /api/visualization | Data Visualization |
| GET | /api/comparison | Data Country Comparison |
| GET | /api/ports | Data Global Ports |
| GET | /api/watchlist | Data Favorite Monitoring List |

REST API digunakan untuk menyediakan data dalam format JSON bagi kebutuhan integrasi atau pengembangan lanjutan.

---

## 🗄️ Database

Database name:

    supply_chain_risk

Database dikelola menggunakan Laravel migrations.

### Main Tables

| Tabel | Fungsi |
|---|---|
| users | Menyimpan data user, role, status, dan last login |
| countries | Menyimpan data negara dari REST Countries API |
| economic_indicators | Menyimpan data GDP, inflasi, populasi, ekspor, dan impor |
| weather_data | Menyimpan data cuaca dari Open-Meteo API |
| exchange_rates | Menyimpan data kurs dari ExchangeRate API |
| risk_scores | Menyimpan hasil akhir risk scoring |
| risk_components | Menyimpan detail komponen perhitungan risk score |
| news_caches | Menyimpan cache berita dari GNews API |
| news_sentiments | Menyimpan hasil analisis sentimen berita |
| positive_words | Kamus kata positif untuk sentiment analysis |
| negative_words | Kamus kata negatif untuk sentiment analysis |
| global_ports | Menyimpan data pelabuhan dari World Port Index Dataset |
| watchlists | Menyimpan daftar negara favorit user |
| articles | Data artikel atau analisis jika modul artikel digunakan |

---

## 📁 Project Structure

| Folder / File | Keterangan |
|---|---|
| app/Http/Controllers | Controller aplikasi |
| app/Models | Model database |
| app/Services | Service untuk API dan business logic |
| database/migrations | Struktur tabel database |
| database/seeders | Seeder data awal |
| resources/views | Blade template |
| routes/web.php | Route halaman web |
| routes/api.php | Route REST API |
| public | Asset publik |
| storage/app/datasets | Dataset seperti World Port Index |

---

## ⚙️ Installation

Clone repository:

    git clone <repository-url>
    cd supply-chain

Install PHP dependencies:

    composer install

Install frontend dependencies:

    npm install

Copy environment file:

    cp .env.example .env

Generate application key:

    php artisan key:generate

Configure database in `.env`:

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=supply_chain_risk
    DB_USERNAME=root
    DB_PASSWORD=

Configure API keys in `.env`:

    EXCHANGE_RATE_API_KEY=your_exchange_rate_api_key
    GNEWS_API_KEY=your_gnews_api_key
    REST_COUNTRIES_API_KEY=
    WORLD_PORT_INDEX_DATASET_PATH=storage/app/datasets/world_port_index.csv

Run migration and seeder:

    php artisan migrate --seed

Import World Port Index dataset:

    php artisan ports:import-world-port-index

Run Laravel server:

    php artisan serve

Run Vite:

    npm run dev

Access application:

    http://127.0.0.1:8000

---

## 🧪 Testing Flow

### Authentication

| URL | Keterangan |
|---|---|
| /register | Membuat akun baru |
| /login | Login manual |
| /logout | Logout dari sistem |

### Main Pages

| URL | Keterangan |
|---|---|
| / | Global Overview |
| /countries | Country Monitoring |
| /risk | Risk Scoring Engine |
| /weather | Weather Monitoring |
| /currency | Currency Impact |
| /news | News Intelligence |
| /visualization | Data Visualization |
| /comparison | Country Comparison |
| /ports | Global Ports dan Port Route Estimator |
| /watchlist | Favorite Monitoring List |
| /admin | Admin Dashboard |

### Sample Testing URLs

| URL | Keterangan |
|---|---|
| /?country=IDN | Global Overview Indonesia |
| /countries?country=DEU | Country Monitoring Germany |
| /risk?country=CHN | Risk Scoring China |
| /weather?country=AUS | Weather Australia |
| /currency?country=IDN | Currency Indonesia |
| /news?country=DEU | News Germany |
| /visualization?country=CHN | Visualization China |
| /comparison | Country Comparison |
| /ports?country=IDN | Ports Indonesia |

---

## 🧹 Development Commands

Clear cache:

    php artisan optimize:clear
    php artisan view:clear
    php artisan route:clear
    php artisan config:clear

Check route list:

    php artisan route:list

Check required REST API routes:

    php artisan route:list | findstr /I "api/dashboard api/countries api/risk api/weather api/currency api/news api/visualization api/comparison api/ports api/watchlist"

Check web routes:

    php artisan route:list | findstr /I "dashboard countries risk weather currency news visualization comparison ports watchlist admin"

Check git status:

    git status

Commit changes:

    git add .
    git commit -m "Finalize supply chain risk intelligence platform"
    git push origin main

---

## ✅ Project Status

| Module | Status |
|---|---|
| Laravel 13 project initialization | ✅ Completed |
| MySQL database configuration | ✅ Completed |
| Authentication and authorization | ✅ Completed |
| Login, register, and logout flow | ✅ Completed |
| Country data integration | ✅ Completed |
| World Bank API integration | ✅ Completed |
| Open-Meteo API integration | ✅ Completed |
| ExchangeRate API integration | ✅ Completed |
| GNews API integration | ✅ Completed |
| World Port Index dataset integration | ✅ Completed |
| Global Overview | ✅ Completed |
| Global Country Dashboard | ✅ Completed |
| Risk Scoring Engine | ✅ Completed |
| Global Weather Monitoring | ✅ Completed |
| Currency Impact Dashboard | ✅ Completed |
| News Intelligence | ✅ Completed |
| Lexicon-Based Sentiment Analysis | ✅ Completed |
| Data Visualization Dashboard | ✅ Completed |
| Country Comparison Engine | ✅ Completed |
| Global Ports | ✅ Completed |
| Port Route Estimator | ✅ Completed |
| Favorite Monitoring List | ✅ Completed |
| Admin Dashboard | ✅ Completed |
| REST API endpoints | ✅ Completed |
| Chart.js visualization | ✅ Completed |
| Leaflet.js geospatial visualization | ✅ Completed |
| Git repository setup | ✅ Completed |
| GitHub repository update | ✅ Completed |

---

## 🧾 Final Project Summary

Global Supply Chain Risk Intelligence Platform adalah platform monitoring risiko rantai pasok global yang menggabungkan data ekonomi, cuaca, kurs mata uang, berita global, data negara, dan data pelabuhan untuk menghasilkan analisis risiko berbasis dashboard.

Sistem ini membantu user memantau indikator penting seperti GDP, inflasi, populasi, mata uang, cuaca, kurs, sentimen berita, risk score, pelabuhan global, dan estimasi rute antar pelabuhan.

Platform ini bukan aplikasi tracking paket, tetapi sistem Business Intelligence dan Decision Support System untuk membantu pengguna memahami risiko impor, ekspor, distribusi, dan rantai pasok global.

---

## 👩‍💻 Developer

| Data | Keterangan |
|---|---|
| Nama | Salsabila Umami |
| NIM | 240180125 |
| Project | Global Supply Chain Risk Intelligence Platform |

Project ini dikembangkan sebagai project final berbasis Full Stack Development, API Integration, Data Engineering, Dashboard Analytics, Geospatial Visualization, Business Intelligence, dan Decision Support System.
