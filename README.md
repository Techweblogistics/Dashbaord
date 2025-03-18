# TechWeb Logistics - AI-Powered No-Code Website Builder

## Overview
TechWeb Logistics is an AI-powered No-Code Website Builder designed to simplify website creation using automation and AI-driven enhancements. This platform enables users to build, customize, and optimize websites effortlessly without coding expertise.

## Features
- AI-powered website generation
- Drag-and-drop customization
- Automated SEO and performance optimization
- AI-assisted content generation
- Real-time AI-driven enhancements

## Installation
1. Clone the repository:
   ```sh
   git clone https://github.com/Techweblogistics/Dashbaord/techweblogistic.git
   ```
2. Navigate to the project directory:
   ```sh
   cd techweblogistics
   ```
3. Install dependencies:
   ```sh
   composer install
   ```
4. Copy the environment file:
   ```sh
   cp .env.example .env
   ```
5. Generate application key:
   ```sh
   php artisan key:generate
   ```
6. Set up the database in the `.env` file and run migrations:
   ```sh
   php artisan migrate
   ```
7. Start the local development server:
   ```sh
   php artisan serve
   ```

## Deployment
To deploy the dashboard, follow these steps:
1. Configure the `.env` file for production.
2. Run database migrations:
   ```sh
   php artisan migrate --force
   ```
3. Serve the application using Apache/Nginx with PHP.

## Contact
For more information, visit [TechWeb Logistics](http://techweblogistics.com/) or access the dashboard at [TechWeb Logistics Dashboard](https://app.techweblogistics.com/login).

