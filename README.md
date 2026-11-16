# GDA – Gestion d'Arrivage

<p align="center">
  <img src="logo.png" alt="GDA Logo" width="180">
</p>

<h3 align="center">Gestion d'Arrivage</h3>

<p align="center">
  A powerful web application for managing, searching, filtering, and organizing incoming stock and Excel-based product data.
</p>

---

## About the Project

**GDA (Gestion d'Arrivage)** is a web application designed to simplify the management of incoming goods and product arrival records.

The application allows users to manage large amounts of Excel-based data containing important information such as:

- Product References
- Brands
- Suppliers
- Prices
- Arrival Dates

Instead of manually searching through multiple Excel files, GDA provides a centralized and easy-to-use interface where users can quickly search, filter, sort, organize, and export their data.

The goal is to make handling large datasets faster, easier, and more efficient.

## Features

- **Data Management:** Handle large datasets containing product and arrival information.
- **Advanced Search:** Quickly find products using their references and other available information.
- **Filtering:** Filter records by brand, supplier, price, arrival date, and other fields.
- **Sorting:** Easily organize and reorder table rows.
- **Data Export:** Export searched and filtered results for further use.
- **Easy Copying:** Quickly copy individual values or selected data.
- **Excel-Oriented Workflow:** Designed to simplify working with large amounts of Excel data.
- **User-Friendly Interface:** A clean and intuitive interface for efficient data handling.

## Technologies Used

| Technology | Purpose                          |
| ---------- | -------------------------------- |
| Laravel    | Backend API                      |
| PHP        | Backend development              |
| React      | Frontend interface               |
| JavaScript | Frontend functionality           |
| Vite       | Frontend development server      |
| Composer   | PHP dependency management        |
| npm        | JavaScript dependency management |

## Installation & Setup

Follow these steps to install and run the project locally.

### 1. Clone the Repository

```bash
git clone https://github.com/aminechaib/GDA-REACT-API-LARAVEL-PROD-v.git
```

Navigate to the project directory:

```bash
cd GDA-REACT-API-LARAVEL-PROD-v
```

### 2. Install Backend Dependencies

```bash
composer install
```

### 3. Configure the Laravel Environment

Create your environment file:

```bash
cp .env.example .env
```

For Windows PowerShell, you can use:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```
Database migration:

```bash
php artisan migrate
```
### 4. Start the Laravel Backend

```bash
php artisan serve
```

The Laravel backend will be available at:

```text
http://127.0.0.1:8000
```

Keep this terminal running.

### 5. Install Frontend Dependencies

Open a **new terminal** and navigate to the frontend directory:

```bash
cd frontend
```

Install the dependencies:

```bash
npm install
```

### 6. Start the React Frontend

```bash
npm run dev
```

Vite will provide a local development URL in your terminal.

Open that URL in your browser.

**Congratulations! Your GDA application is now running locally.**

Make sure both the Laravel backend and React frontend terminals remain running.

---

## Project Structure

```text
GDA-REACT-API-LARAVEL-PROD-v/
│
├── app/                  # Laravel application
├── bootstrap/
├── config/
├── database/
├── public/
├── routes/               # Backend API routes
├── storage/
├── frontend/             # React application
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── ...
│
├── resources/
├── .env.example
├── artisan
├── composer.json
├── logo.png
└── README.md
```

---
## Developer

<p align="center">
  Made with dedication by <strong>Amine Chaib</strong>
</p>

---

<p align="center">
  <strong>GDA – Gestion d'Arrivage</strong><br>
  Simplifying data management, one arrival at a time.
</p>
