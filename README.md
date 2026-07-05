# Global Supply Chain Risk Intelligence Platform

Global Supply Chain Risk Intelligence Platform adalah aplikasi berbasis web untuk memantau dan menganalisis risiko yang dapat memengaruhi rantai pasok global.

Sistem mengintegrasikan data negara, kondisi ekonomi, cuaca, nilai tukar mata uang, berita global, dan lokasi pelabuhan ke dalam satu dashboard analitik untuk membantu proses monitoring dan pengambilan keputusan.

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

## Technology Stack

### Backend
- PHP 8.4
- Laravel 13
- MySQL

### Frontend
- Bootstrap 5
- AJAX
- JavaScript ES6

### Data Visualization
- Chart.js
- Leaflet.js

## External Data Sources

- Open-Meteo API
- World Bank API
- REST Countries API
- Exchange Rate API
- GNews API
- World Port Index Dataset
- OpenStreetMap

## Project Status

Development started on July 5, 2026.

### Completed

- [x] Laravel 13 project initialization
- [x] MySQL database configuration
- [x] Initial database migration
- [x] Git repository initialization
- [x] Initial project commit

### In Progress

- [ ] Database ERD design
- [ ] Core database migrations
- [ ] Eloquent models and relationships
- [ ] Authentication and authorization
- [ ] External API integration
- [ ] Risk scoring engine
- [ ] Analytics dashboard

## Database

Database name:

` supply_chain_risk `

Database management is handled through Laravel migrations.

## Development

```bash
composer install
php artisan migrate
php artisan serve