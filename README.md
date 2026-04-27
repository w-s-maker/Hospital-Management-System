# 🏥 Afya Hospital Management System

> **A comprehensive, full-stack Hospital Management System built to revolutionize healthcare delivery — powered by an integrated AI Assistant that puts patients first.**

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://mariadb.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Dialogflow](https://img.shields.io/badge/Google-Dialogflow-FF9800?logo=dialogflow&logoColor=white)](https://dialogflow.cloud.google.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🌟 Overview

**Afya Hospital Management System** is a feature-rich, web-based platform designed to digitize and streamline every aspect of hospital operations — from patient registration and appointment scheduling to billing, medical records management, and real-time staff coordination.

What truly sets Afya apart is its **AI-powered Hospital Assistant**, built on Google Dialogflow, that is available 24/7 to help patients check doctor availability, book appointments, retrieve medical records, and get instant answers to their healthcare queries — all through a natural, conversational interface. No more long phone queues. No more confusion. Just seamless, intelligent healthcare at your fingertips.

Whether you're a **patient** looking to book an appointment from your couch, a **doctor** managing a packed schedule and accessing encrypted patient records, or a **hospital administrator** overseeing the entire operation with rich dashboards and audit trails — Afya has you covered.

---

## ✨ Key Features

### 🤖 AI-Powered Patient Assistant (Dialogflow)
- **24/7 Conversational AI** embedded directly into the patient portal
- Ask about doctor availability, specializations, and schedules
- Book, reschedule, or inquire about appointments via natural language
- Retrieve medical records and billing information conversationally
- All chatbot interactions are **logged and auditable** by hospital admins

### 👨‍⚕️ Multi-Role Portal System
The system supports **five distinct user roles**, each with a tailored dashboard and experience:

| Role | Capabilities |
|------|-------------|
| **Patient** | Book appointments, view medical history, pay bills (M-Pesa & Card), submit feedback, interact with AI assistant |
| **Doctor** | Manage appointments, view/create medical records, manage schedules, generate invoices, record patient visits with PDF generation |
| **Nurse** | Access relevant patient information and assist in care coordination |
| **Hospital Staff** | Administrative support functions |
| **Admin** | Full system control — CRUD operations on all entities, analytics dashboards, audit/chatbot/data-access logs, user management, notification management |

### 📅 Smart Appointment Management
- Patients can book appointments online with their preferred doctor
- Doctors can create, reschedule, and manage appointments
- Admins have full oversight with bulk management capabilities
- **Real-time notifications** sent to both patients and doctors on every update
- Appointment statuses: Scheduled, Completed, Cancelled

### 🔐 Security & Data Protection
- **AES-256-CBC Encryption** for sensitive patient medical records
- Secure password hashing with `password_hash()` and `password_verify()`
- Role-based access control (RBAC) across all portals
- Session-based authentication with role validation on every page
- Comprehensive **Audit Logs** tracking every CRUD operation
- **Data Access Logs** recording who accessed which patient data and when
- Input sanitization and parameterized queries (PDO) to prevent SQL injection

### 💰 Billing & Payment Gateway
- Doctors can generate and manage invoices tied to appointments
- Patients can view billing history and download PDF invoices
- **M-Pesa integration** for mobile payments (Kenya's leading mobile money platform)
- **Card payment** support
- Transaction tokenization for payment security
- Payment statuses: Pending, Paid, Failed, Refunded

### 📋 Medical Records Management
- Patients can submit and upload medical history documents
- Doctors can view encrypted medical records for their assigned patients
- Visit records with detailed clinical notes
- **PDF generation** (via TCPDF) for visit summaries and medical records
- File upload and secure download functionality

### 📊 Admin Analytics Dashboard
- Real-time counts of Doctors, Patients, Nurses, and Hospital Staff
- **Interactive Charts** (Chart.js) — patient trends, ICU vs OPD distribution
- Upcoming appointments overview
- New patient registrations feed
- Hospital management metrics visualization

### 🔔 Real-Time Notification System
- In-app notifications for patients and doctors
- Appointment creation, update, and cancellation alerts
- Invoice and billing notifications
- Schedule change alerts
- Mark-as-read functionality

### 📝 Feedback System
- Patients can submit feedback with star ratings (1–5)
- Admin can view all feedback in a dedicated panel
- Helps drive continuous improvement in care quality

### 👤 Comprehensive Profile Management
- Education and experience records for doctors and staff
- Profile picture uploads
- Editable personal and professional information

---

## 🏗️ Project Architecture

```
FINALPROJECT2025/
│
├── Backend/                    # Admin portal & shared backend logic
│   ├── admindashboard.html     # Admin analytics dashboard
│   ├── loginpage.php           # Unified login for all roles
│   ├── signuppage.php          # User registration with role selection
│   ├── assets/                 # CSS, JS (Bootstrap, Chart.js, jQuery)
│   ├── tcpdf/                  # PDF generation library
│   ├── *_fetch_*.php           # Data fetching APIs
│   ├── *_add_*.php             # CRUD - Create operations
│   ├── *_update_*.php          # CRUD - Update operations
│   ├── *_delete_*.php          # CRUD - Delete operations
│   ├── get_audit_logs.php      # Audit trail retrieval
│   ├── get_chatbot_logs.php    # AI assistant log retrieval
│   └── get_data_access_logs.php# Data access audit trail
│
├── Doctor/                     # Doctor portal
│   ├── doctordashboard.php     # Doctor dashboard with stats
│   ├── appointments.php        # Appointment management
│   ├── medicalrecords.php      # Patient medical records viewer
│   ├── patient_details.php     # Detailed patient information
│   ├── billing.php             # Invoice management
│   ├── schedule.php            # Schedule management
│   ├── notifications.php       # Doctor notification center
│   └── profile.php             # Doctor profile management
│
├── Patient/                    # Patient portal
│   ├── index.html              # Patient-facing homepage
│   ├── appointment.php         # Appointment booking
│   ├── medical-history.php     # Medical records with encryption
│   ├── billing.php             # Billing overview & invoice download
│   ├── billinggateway.php      # Payment processing (M-Pesa & Card)
│   ├── feedback.php            # Feedback submission
│   ├── chatbot-process.php     # AI chatbot backend processing
│   └── style.css               # Modern, responsive styling
│
├── Project Screenshots/        # Application screenshots
├── db_connect.php              # Database connection configuration
└── hospital_management.sql     # Complete database schema & seed data
```

---

## 🗄️ Database Schema

The system uses a **MySQL/MariaDB** database with **17+ interconnected tables**:

| Table | Purpose |
|-------|---------|
| `users` | Unified authentication for all roles |
| `patients` | Patient demographic and medical info |
| `doctors` | Doctor profiles and specializations |
| `nurses` | Nursing staff records |
| `hospital_staffs` | General staff records |
| `admins` | Administrator profiles |
| `employees` | Unified staff registry with role mapping |
| `appointments` | Appointment scheduling and tracking |
| `doctor_schedule` | Doctor availability management |
| `billing` | Invoice and payment records |
| `patient_records` | Encrypted medical history and uploads |
| `visit_records` | Doctor visit notes and clinical data |
| `feedback` | Patient feedback and ratings |
| `notifications` | Multi-role notification system |
| `admin_notifications` | Admin-specific alerts (signups, CRUD, etc.) |
| `audit_logs` | Complete audit trail of system actions |
| `chatbot_logs` | AI assistant conversation logs |
| `data_access_logs` | Patient data access tracking |
| `education_informations` | Staff educational background |
| `experience_informations` | Staff professional experience |

---

## 🚀 Getting Started

### Prerequisites
- **XAMPP** (or any Apache + PHP + MySQL stack)
- PHP 8.0+
- MySQL / MariaDB
- A modern web browser

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/w-s-maker/Hospital-Management-System.git
   ```

2. **Move to your web server directory**
   ```bash
   # For XAMPP
   cp -r Hospital-Management-System/ /path/to/xampp/htdocs/FINALPROJECT2025
   ```

3. **Import the database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a new database named `hospital_management`
   - Import the `hospital_management (7).sql` file

4. **Configure database connection**
   - Update `db_connect.php` and `Backend/db_connect.php` with your local database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'hospital_management';
     $username = 'root';
     $password = '';
     ```

5. **Launch the application**
   - Patient Portal: `http://localhost/FINALPROJECT2025/Patient/index.html`
   - Admin Login: `http://localhost/FINALPROJECT2025/Backend/loginpage.php`

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 4, Font Awesome, Google Fonts |
| **Backend** | PHP 8.2 (PDO) |
| **Database** | MySQL / MariaDB |
| **AI Assistant** | Google Dialogflow (Messenger Integration) |
| **Charts** | Chart.js |
| **PDF Generation** | TCPDF |
| **Authentication** | PHP Sessions, bcrypt hashing |
| **Encryption** | AES-256-CBC (OpenSSL) |
| **Payments** | M-Pesa, Card (tokenized) |

---

## 📸 Screenshots

Screenshots of the application are available in the `Project Screenshots/` directory, showcasing the admin dashboard, patient portal, doctor interface, and more.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 👥 Authors

- **w-s-maker** — [GitHub Profile](https://github.com/w-s-maker)

---

<p align="center">
  <b>Afya Hospital</b> — Expert Healthcare For Your Family 💙
</p>
