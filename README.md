# Brainware University - Employee Subject Selection System

A clean, production-ready **PHP 8.2+** web application designed for faculty subject preferences selection and Department Head (HOD) administrative oversight. 

Built specifically for deployment on **Vercel** serverless functions with external **MySQL-compatible** persistent databases (e.g. TiDB, PlanetScale, Aiven, AWS RDS) and zero-dependency local development via PHP's built-in server.

---

## Key Features & Business Rules

1. **One Submission Per Email (Enforced at Database Level)**:
   - Faculty members can submit their subject selection **exactly once**.
   - Database table `submissions` has a `UNIQUE` constraint on `faculty_id` and `faculty_email`.
   - Once submitted, the selection is **permanently locked**: no editing, no resubmission, no deletion.
   - Subsequent logins directly display the immutable **Submission Receipt** (`SUB-XXXXXX`).
   - Direct API attempts to resubmit return **HTTP 409 Conflict** (`Resubmission is not allowed`).

2. **Strict Exactly 5 Subjects**:
   - Faculty must select **exactly 5 subjects** in their preferred order.
   - The UI prevents selecting a 6th subject with instant feedback: *"You can select a maximum of 5 subjects."*
   - Review and submission buttons are disabled until exactly 5 subjects are chosen.
   - The backend strictly enforces: exactly 5 items, non-zero positive IDs, active catalog status, and zero duplicates.

3. **Selection Order Preservation**:
   - The exact 1-to-5 order in which the faculty clicked and reviewed the subjects is stored in `submission_subjects` (`selection_order` column).
   - The chosen order is maintained across the Faculty Receipt, HOD Dashboard, and exported Excel report.

4. **HOD Administrative Portal**:
   - Live KPI overview: **Total Faculty**, **Submitted**, and **Pending**.
   - Real-time search by faculty name, email, or employee code.
   - Server-side filter tabs: **All**, **Submitted**, **Pending**.
   - One-click native Microsoft Excel (`.xlsx`) export with bold headers, auto-filters, and frozen top row.

5. **Production Vercel Compatibility**:
   - Runs on Vercel using `vercel-php@0.6.2` (PHP 8.2 serverless runtime).
   - No SQLite on ephemeral filesystems; uses hosted MySQL with PDO prepared statements.
   - Generates Excel files using PHP native `ZipArchive` & OpenXML with zero external dependencies.

---

## Project Structure

```text
employee-subject-selection/
│
├── public/
│   ├── index.php             # Web server entry point & router invoker
│   ├── css/
│   │   └── style.css         # Modern, responsive design system
│   └── js/
│       ├── faculty.js        # Faculty selection tracking, order badges & modal
│       └── hod.js            # HOD dashboard search, filter & dynamic updates
│
├── api/
│   └── index.php             # Vercel serverless function entry point
│
├── app/
│   ├── config/
│   │   └── config.php        # Environment loader, security headers, sessions
│   ├── database/
│   │   └── Database.php      # PDO Connection Singleton with SSL support
│   ├── auth/
│   │   └── Auth.php          # Session, CSRF, rate limiting & role guards
│   ├── models/
│   │   ├── Faculty.php       # Master faculty queries & HOD aggregations
│   │   ├── Subject.php       # Academic curriculum catalog queries
│   │   └── Submission.php    # Submission transactions & unique constraint enforcement
│   ├── services/
│   │   └── ExcelExport.php   # Pure PHP native OpenXML (.xlsx) generator
│   ├── helpers/
│   │   └── Response.php      # JSON response, escaping & date helpers
│   ├── views/
│   │   ├── layout/
│   │   │   ├── header.php    # Brand header & navigation
│   │   │   └── footer.php    # Brand footer & toast messaging
│   │   ├── faculty/
│   │   │   ├── login.php     # Faculty login card
│   │   │   ├── dashboard.php # Live subject selector with order badges
│   │   │   └── receipt.php   # Locked submission receipt (no edit/resubmit)
│   │   └── hod/
│   │       ├── login.php     # Administrative HOD login
│   │       └── dashboard.php # KPI cards, faculty table & export
│   └── Router.php            # Unified application route controller
│
├── database/
│   ├── schema.sql            # Full MySQL database schema
│   ├── seed.sql              # 27 curriculum subjects & faculty accounts
│   └── init_db.php           # Database migration CLI script
│
├── tests/
│   └── system_test.php       # Automated end-to-end integration & security test suite
│
├── router.php                # Local dev server router for php -S localhost:8000
├── vercel.json               # Vercel deployment configuration
├── composer.json             # PHP dependencies and autoloading configuration
├── .env.example              # Environment variables template
└── README.md                 # Complete documentation
```

---

## Database Schema (`database/schema.sql`)

### 1. `faculty`
```sql
CREATE TABLE `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_faculty_emp_code` (`employee_code`),
  INDEX `idx_faculty_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. `subjects`
```sql
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(50) NOT NULL,
  `subject_name` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_subject_code` (`subject_code`),
  INDEX `idx_subject_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. `submissions` (Enforces ONE EMAIL = ONE FINAL SUBMISSION)
```sql
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL UNIQUE,
  `faculty_email` VARCHAR(191) NOT NULL UNIQUE,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(20) NOT NULL DEFAULT 'SUBMITTED',
  CONSTRAINT `fk_submissions_faculty_id` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_faculty_email` FOREIGN KEY (`faculty_email`) REFERENCES `faculty` (`email`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. `submission_subjects` (Preserves exact 1-to-5 order)
```sql
CREATE TABLE `submission_subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `submission_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `selection_order` INT NOT NULL,
  UNIQUE KEY `uniq_sub_order` (`submission_id`, `selection_order`),
  UNIQUE KEY `uniq_sub_subject` (`submission_id`, `subject_id`),
  CONSTRAINT `fk_sub_subjects_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. `admins` & `login_attempts`
```sql
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Default Login Credentials

### HOD Administration
- **URL**: `http://localhost:8000/hod/login`
- **Email**: `hod.css@brainwareuniversity.ac.in`
- **Password**: `gurudev` (or `Admin@123`)

### Faculty Accounts (Pre-Seeded)
Default password for all sample faculty: `Faculty@123`

| Faculty Name | Email | Employee Code | Initial Status |
| :--- | :--- | :--- | :--- |
| **Dr. Arindam Ghosh** | `arindam.cs@brainwareuniversity.ac.in` | `BWU-FAC-001` | *Pending / Testable* |
| **Dr. Priya Sharma** | `priya.cs@brainwareuniversity.ac.in` | `BWU-FAC-002` | *Pending / Testable* |
| **Prof. Sourav Mukherjee** | `sourav.cs@brainwareuniversity.ac.in` | `BWU-FAC-003` | *Pending* |
| **Prof. Ananya Sen** | `ananya.cs@brainwareuniversity.ac.in` | `BWU-FAC-004` | *Pending* |
| **Dr. Rahul Banerjee** | `rahul.cs@brainwareuniversity.ac.in` | `BWU-FAC-005` | *Pending* |
| **Dr. Subhashis Roy** | `subhashis.cs@brainwareuniversity.ac.in` | `BWU-FAC-006` | *Pending* |
| **Prof. Debolina Chatterjee** | `debolina.cs@brainwareuniversity.ac.in` | `BWU-FAC-007` | *Pending* |
| **Prof. Amitabha Das** | `amitabha.cs@brainwareuniversity.ac.in` | `BWU-FAC-008` | *Pending* |
| **Dr. Sneha Bhattacharya** | `sneha.cs@brainwareuniversity.ac.in` | `BWU-FAC-009` | **Submitted** |
| **Dr. Kuntal Ghosh** | `kuntal.cs@brainwareuniversity.ac.in` | `BWU-FAC-010` | **Submitted** |

---

## Local Installation & Setup

### Prerequisites
- PHP 8.0+ (PHP 8.2+ recommended) with `pdo`, `pdo_mysql`, `zip` extensions enabled.
- MySQL or MariaDB running locally (e.g. via XAMPP) or a remote MySQL URL.

### 1. Clone & Configure Environment
Create `.env` from template:
```bash
cp .env.example .env
```
Edit `.env` with your database credentials:
```env
APP_ENV=development
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bwu_subject_selection
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECRET=bwu_secret_session_key_2026
HOD_EMAIL=hod.css@brainwareuniversity.ac.in
```

### 2. Initialize Database & Seed Curriculum
Run the initialization script:
```bash
php database/init_db.php
```
This creates the database `bwu_subject_selection`, applies `database/schema.sql`, and seeds all 27 curriculum subjects and sample faculty.

### 3. Start Local Server
Run with the built-in development router:
```bash
php -S localhost:8000 router.php
```
Open your browser at:
- Faculty Portal: [http://localhost:8000/login](http://localhost:8000/login)
- HOD Portal: [http://localhost:8000/hod/login](http://localhost:8000/hod/login)

---

## Vercel Deployment Instructions

The application is structured to deploy smoothly to Vercel.

### 1. Database Requirement for Vercel
Vercel serverless functions are stateless and read-only. You must connect to a persistent external MySQL database:
- **TiDB Cloud Serverless** (Free tier available, 100% MySQL compatible)
- **PlanetScale**
- **Aiven for MySQL**
- **AWS RDS / DigitalOcean Managed MySQL**

Import `database/schema.sql` and `database/seed.sql` into your hosted database.

### 2. Configure Vercel Project
In your Vercel Dashboard, add the following **Environment Variables**:
- `APP_ENV`: `production`
- `APP_URL`: `https://your-project.vercel.app`
- `DB_HOST`: Your remote MySQL host
- `DB_PORT`: `3306` (or remote port)
- `DB_DATABASE`: Your database name
- `DB_USERNAME`: Your database user
- `DB_PASSWORD`: Your database password
- `SESSION_SECRET`: Random 32+ character secret string
- `HOD_EMAIL`: `hod.css@brainwareuniversity.ac.in`

### 3. Deploy
Deploy using the Vercel CLI:
```bash
vercel --prod
```
Or connect your GitHub repository to Vercel. The included `vercel.json` automatically configures the `vercel-php@0.6.2` runtime and routes requests to `/api/index.php` and static assets to `/public/`.

---

## Security Implementation

- **Database-Level Unique Constraints**: `submissions.faculty_id UNIQUE` and `submissions.faculty_email UNIQUE`. Transactions guarantee atomic persistence.
- **CSRF Protection**: Synchronizer token pattern generated on every session. Form and AJAX submissions validated using `hash_equals()`.
- **Password Hashing**: `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt) and constant-time `password_verify()`.
- **SQL Injection Prevention**: 100% of database interactions use PDO prepared statements with strict parameter binding. Emulated prepares disabled.
- **Session Hardening**: `HttpOnly`, `SameSite=Lax`, `Secure` in production, session ID regeneration upon authentication, and 3-hour inactivity timeout.
- **Rate Limiting**: Failed logins tracked in `login_attempts`. 5 consecutive failed attempts trigger a 5-minute lockout.
- **XSS Prevention**: All dynamic template outputs sanitized with `Response::escape()` (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`).
- **Security Headers**: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Cache-Control: no-store`.

---

## Application Route & API Reference

| Method | Route | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | Root router redirect | Public |
| `GET` | `/login` | Faculty login page | Public |
| `POST` | `/login` | Faculty authentication endpoint | Public |
| `GET` | `/logout` | Terminate session & redirect to login | Authenticated |
| `GET` | `/dashboard` | Faculty subject selector OR locked receipt | Faculty Only |
| `POST` | `/api/faculty/submit` | Finalize 5 subject selection (Atomic transaction) | Faculty Only |
| `GET` | `/hod/login` | HOD administrator login page | Public |
| `POST` | `/hod/login` | HOD authentication endpoint | Public |
| `GET` | `/hod/dashboard` | Administrative overview & faculty table | HOD Only |
| `GET` | `/api/hod/faculty` | Dynamic search/filter faculty API | HOD Only |
| `GET` | `/api/hod/export` | Download genuine Microsoft Excel `.xlsx` | HOD Only |
| `GET` | `/hod/logout` | Terminate HOD session | HOD Only |

---

## Verification & Test Results

The system was verified via both an automated integration test suite and a full browser subagent session.

### Automated Test Suite (`tests/system_test.php`)
Command: `php tests/system_test.php`
- **Total Tests Run**: 28
- **Passed**: 28 (100%)
- **Failed**: 0

Test coverage included:
- Unauthenticated access protection and redirection across all routes.
- Faculty authentication with invalid format, incorrect password, and valid credentials.
- Faculty role isolation (faculty session denied HOD routes and APIs).
- Strict backend rejection of 0, 4, 6 subjects, duplicate subject IDs, non-existent subject IDs, and invalid CSRF tokens.
- Atomic insertion of 5 subjects preserving exact selection order.
- Immediate permanent locking upon submission (receipt view rendered, review button removed).
- Direct second API submission rejection with HTTP 409 Conflict.
- Preservation of lock state across logout and re-login.
- HOD login, KPI metrics calculations, filtering (`all`, `submitted`, `pending`), and search.
- Excel file generation verification (valid OpenXML ZIP structure, bold headers, autoFilter, frozen panes, and exact subject data).

---

## Known Limitations & Production Notes

1. **Email Domain Format**:
   - The system validates that emails follow standard university email formats.
2. **Database Engine**:
   - Requires MySQL 5.7+, MySQL 8.0+, or MariaDB 10.3+ with InnoDB storage engine for foreign keys and transactions.
3. **Resetting Submissions**:
   - Following business rules, faculty cannot reset or edit submissions. If an administrative reset is ever needed, the database admin can remove the submission row from `submissions`, which cascades to `submission_subjects`.
