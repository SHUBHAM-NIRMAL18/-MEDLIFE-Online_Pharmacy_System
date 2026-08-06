# MEDLIFE - Multi-Tenant SaaS Online Pharmacy Platform

**MEDLIFE** is an enterprise-grade Multi-Tenant **SaaS (Software as a Service) Online Pharmacy Platform** built in PHP & MySQL. It enables independent pharmacies to register their store, manage catalog & inventory, handle orders, and customize store delivery pricing—all while providing a Super Admin platform control center and a multi-pharmacy storefront for customers.

---

## 🌟 SaaS Platform Architecture

- **Multi-Tenant Data Isolation**: Database auto-migrator (`config/saas_setup.php`) enforces `pharmacy_id` tenant isolation across products, categories, orders, customers, and admin accounts.
- **Zero-Downtime Migration**: Existing single-store database schema automatically upgrades seamlessly without data loss under Pharmacy #1 ("MedLife Central Pharmacy").
- **Super Admin Platform Portal**: Central management system for platform revenue, pharmacy onboarding, and store suspension/activation.
- **Self-Service Pharmacy Registration**: External pharmacy owners can launch their store in minutes via `saas_register.php`.
- **Multi-Pharmacy Storefront**: Customers can select their preferred pharmacy store via the store dropdown widget.

---

## 🚀 Key Modules & Access Points

| Module | URL | Credentials / Details |
| :--- | :--- | :--- |
| **Storefront & Pharmacy Selector** | `/index.php` | Browse & switch active pharmacy stores |
| **Pharmacy Self-Registration** | `/saas_register.php` | Register new pharmacy & store owner account |
| **Super Admin Platform Portal** | `/saas_admin.php` | Email: `admin@medlifesaas.com`<br>Password: `admin123` |
| **Pharmacy Admin Login** | `/admin_login.php` | Log in to individual pharmacy admin portal |
| **Pharmacy Store Settings** | `/pharmacy_settings.php` | Update store details, address, & delivery fee |

---

## 🛒 Features

### For Pharmacy Tenants (Pharmacy Admins)
- 🏢 Custom Pharmacy profile, logo, address, and delivery fee settings.
- 📦 Manage inventory, stock alerts, and catalog products scoped per pharmacy.
- 📂 Sub-category hierarchy support scoped to the tenant.
- 📑 Handle incoming customer orders & prescription verification.
- 📊 Real-time pharmacy sales analytics and revenue overview.

### For Platform Managers (Super Admin)
- 🛡️ Monitor total registered pharmacies, active vs. suspended counts.
- 📈 Track total platform gross volume (revenue) & total orders across all tenants.
- ⚡ Activate, suspend, or manually onboard pharmacy tenants.

### For Customers / Patients
- 🏬 **Pharmacy Store Switcher**: Browse products and categories for chosen local pharmacy.
- 🔍 Instant search & dynamic filtering across medicines, supplements, and devices.
- 🛒 Shopping cart with automatic pharmacy-specific delivery charge calculation.
- 📄 Upload prescription images during checkout.
- 📍 Order tracking with unique tracking code.

---

## 🛠️ Tech Stack
- **Frontend:** HTML5, Vanilla CSS3 (Custom Design System, Glassmorphism, CSS Variables), JavaScript, Boxicons, FontAwesome
- **Backend:** Core PHP (Modular Session & Database Context Architecture)
- **Database:** MySQL / MariaDB (Prepared Statements, Shared Database Shared Schema Multi-Tenancy)
- **Environment:** XAMPP / Apache / Nginx

---

## 💻 Getting Started

### Prerequisites
- Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB).
- Ensure Apache and MySQL services are started.

### Installation & Setup
1. Clone the repository into your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/SHUBHAM-NIRMAL18/-MEDLIFE-Online_Pharmacy_System.git medlife
   ```
2. Create a MySQL database named `medlife`.
3. Configure database credentials in `.env` or `config.php` if different from default (`root` / no password).
4. Open your browser and navigate to:
   ```text
   http://localhost/medlife/index.php
   ```
5. Database migrations will run automatically on first connection!
