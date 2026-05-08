============================================================
  FuelWise v2.0 — AI-Powered Personalized Nutrition
  Installation & Setup Guide
============================================================

Author:   Sunil Thapa (P2837603)
Project:  Final Year Thesis — Niels Brock
Course:   BSc Computer Science

============================================================
 # FuelWise — AI-Based Personalized Nutrition System

A web-based dietary recommendation system for adults with type 2 diabetes, prediabetes, irritable bowel syndrome (IBS), hypertension and weight-management goals. FuelWise generates personalized seven-day meal plans through a **hybrid recommendation architecture**: a Groq-hosted Llama-3.3-70B-Versatile large language model serves as the primary plan generator, with a deterministic rule-based engine acting as the safety-net fallback when the LLM is unavailable, rate-limited or returns a plan that fails validation.

The system explicitly supports cultural cuisine preferences (Indian, Asian, Mediterranean, Western, African, Latin American), religious dietary law (halal, kosher), dietary preferences (vegetarian, vegan, gluten-free) and ingredient-level allergen filtering with curated keyword expansion.

---

## Table of Contents

1. [System Requirements](#1-system-requirements)
2. [Quick Install (XAMPP)](#2-quick-install-xampp)
3. [Database Setup](#3-database-setup)
4. [Configuration](#4-configuration)
5. [First Run](#5-first-run)
6. [Project Structure](#6-project-structure)
7. [How the Recommendation Engine Works](#7-how-the-recommendation-engine-works)
8. [Admin Panel](#8-admin-panel)
9. [Troubleshooting](#9-troubleshooting)
10. [Acknowledgements](#10-acknowledgements)

---

## 1. System Requirements

| Component | Version |
|---|---|
| PHP | 8.0 or higher |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Apache | 2.4+ (with `mod_rewrite` enabled) |
| Python (one-time, for food import only) | 3.8+ with `pandas` |
| Recommended environment | XAMPP 8.0+ on Windows / macOS / Linux |
| Internet access | Required for the Groq LLM primary path and SMTP password recovery |

**Required PHP extensions:** `mysqli`, `curl`, `openssl`, `mbstring`, `json` (all enabled by default in XAMPP).

---

## 2. Quick Install (XAMPP)

The fastest way to get FuelWise running on a fresh machine.

### Step 1 — Install XAMPP

Download XAMPP from <https://www.apachefriends.org> and install with default options. Start **Apache** and **MySQL** from the XAMPP control panel.

### Step 2 — Place the project files

Extract the contents of `fuelwise-v2.zip` so that the `fuelwise/` folder sits inside XAMPP's `htdocs/` directory:

```
C:\xampp\htdocs\fuelwise\         (Windows)
/Applications/XAMPP/htdocs/fuelwise/   (macOS)
/opt/lampp/htdocs/fuelwise/       (Linux)
```

You should now have a folder structure like:

```
htdocs/
└── fuelwise/
    ├── api/
    ├── admin/
    ├── includes/
    ├── db/
    ├── css/
    ├── js/
    ├── index.php
    ├── README.md
    └── ...
```

### Step 3 — Continue to database setup (next section)

---

## 3. Database Setup

FuelWise uses a single MySQL database called `fuelwise`. Set-up takes two SQL imports plus one optional Python script.

### Step 1 — Create the database and schema

1. Open phpMyAdmin: <http://localhost/phpmyadmin>
2. Click **New** in the left sidebar and create a database called `fuelwise` (collation: `utf8mb4_general_ci`).
3. With `fuelwise` selected, click the **Import** tab.
4. Choose `db/fuelwise.sql` from this project and click **Go**.

This creates all 15 tables (`users`, `admins`, `health_profiles`, `recommendations`, `weekly_meals`, `food_nutrition`, `foods`, `exercises`, `notifications`, `user_streaks`, `profile_updates`, `password_resets`, `admin_logs`, `chat_rooms`, `chat_messages`, `chatbot_messages`) with correct foreign keys.

### Step 2 — Load the food knowledge base

The 2,395-row `food_nutrition` table is built offline by classifying five Kaggle nutrition CSVs against published clinical thresholds (Atkinson et al. 2021 for glycaemic index, Bertin et al. 2024 for FODMAP, the AHA DASH thresholds for sodium, and WHO dietary guidelines for weight management).

A pre-built SQL dump is provided so you do **not** need to run Python. In phpMyAdmin, with `fuelwise` selected, click **Import** again and load:

```
db/food_nutrition_import.sql
```

(This may take 10–30 seconds — it inserts 2,395 rows.)

> **Optional — rebuild the food classification yourself:**
> If you want to regenerate the classification (for example, after adjusting thresholds), run the Python importer instead:
> ```bash
> cd db/
> pip install pandas
> python import_food_csvs.py
> ```
> This reads the five CSVs in `db/datasets/`, applies the rule-based classifier, and writes a fresh `food_nutrition_import.sql`. Then re-import that file in phpMyAdmin.

### Step 3 — (Optional) Seed the curated meals and exercise library

If they are not already populated by `fuelwise.sql`, also import:

```
db/foods_seed.sql       (45 culturally-tagged meal templates)
db/exercises_seed.sql   (exercise library with safety filters)
```

---

## 4. Configuration

All deployment-specific settings live in a single file: `includes/config.php`.

You will need to provide your own credentials for two external services:

### 4.1 Database credentials

Open `includes/config.php` and check the database block:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');         // empty by default in XAMPP
define('DB_NAME', 'fuelwise');
```

The XAMPP defaults work out of the box. If you set a MySQL password, update `DB_PASS`.

### 4.2 Groq API key (required for the LLM primary path)

1. Sign up for a free Groq account at <https://console.groq.com>.
2. Generate an API key from the Groq console.
3. Paste it into `includes/config.php`:

```php
define('GROQ_API_KEY', 'gsk_your_key_here');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
```

> **Without a valid Groq key**, plan generation will silently fall back to the deterministic rule-based pipeline. The system remains fully functional but loses the LLM-generated meal variety described in §4.4.2 of the thesis.

### 4.3 SMTP credentials (required for password reset and admin OTP)

FuelWise sends two kinds of email: user password resets and admin OTP codes. Configure your SMTP block in `includes/config.php`:

```php
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'your.email@gmail.com');
define('SMTP_PASS',     'your-16-char-gmail-app-password');
define('SMTP_FROM',     'your.email@gmail.com');
define('SMTP_FROMNAME', 'FuelWise');
```

> **Gmail users:** you must use an **App Password** (not your normal Gmail password). Create one at <https://myaccount.google.com/apppasswords> after enabling 2-Step Verification.
>
> **Skipping email entirely:** if you only need to test the recommendation engine, leave the SMTP block at its placeholder values. Registration will still work — only password reset and admin OTP delivery require functional email.

---

## 5. First Run

With Apache + MySQL running and `fuelwise.sql` + `food_nutrition_import.sql` loaded:

1. Open <http://localhost/fuelwise/> in your browser.
2. Click **Register** and create a user account.
3. Click **Complete Health Assessment** on the dashboard. Fill in:
   - Age, gender, weight, height, activity level
   - Target health condition (one or more of: type 2 diabetes, prediabetes, IBS, hypertension, weight management)
   - Dietary preference (vegetarian, vegan, halal, kosher, gluten-free, none)
   - Cultural cuisine (Indian, Asian, Mediterranean, Western, African, Latin, universal)
   - Allergens (comma-separated, e.g. `peanut, shellfish`)
   - Optional: blood pressure, glucose, cholesterol, insulin
4. Click **Generate Plan**. The system will:
   - Try the Groq LLM path first (≈4 seconds).
   - If validation fails or the API is unavailable, fall back to the rule-based pipeline (under 1 second).
   - Render a seven-day plan with day tabs, breakfast/lunch/dinner/snacks, and macronutrient breakdowns.
5. Each generated plan is recorded in the `recommendations` table. The `source` column shows which path produced the plan: `'llm'` or `'dataset'`.

### Reproducing the thesis test cases

The eight walkthrough test cases described in §4.5.2 of the thesis can be reproduced by registering test users with the parameters from Table 4.1 — for example, **T4** is a multi-condition user with diabetes + hypertension, vegetarian, Indian cuisine. Plan generation under that profile should produce a low-GI, low-sodium, vegetarian, Indian-leaning seven-day plan in either path.

---

## 6. Project Structure

```
fuelwise/
├── index.php                    # Public landing page
├── about.php                    # About / disclaimer page
├── register.php / login.php     # User authentication
├── forgot-password.php          # Password reset flow
├── assessment.php               # Health-assessment form
├── dashboard.php                # User dashboard
├── recommendation.php           # 7-day plan viewer (day tabs)
├── history.php                  # Past plan history
├── chatbot.php                  # Groq-powered nutrition chatbot
├── chat.php                     # Live chat with admin
│
├── api/
│   └── recommend.php            # Plan-generation endpoint (orchestrates LLM + fallback)
│
├── admin/                       # OTP-gated admin panel
│   ├── login.php / verify-otp.php
│   ├── index.php                # Admin dashboard
│   ├── users.php                # User management
│   ├── foods.php                # Food database management
│   ├── exercises.php            # Exercise library
│   ├── recommendations.php      # View all generated plans
│   └── chat.php                 # Live-chat admin interface
│
├── includes/
│   ├── config.php               # *** CONFIGURE THIS *** (DB, Groq, SMTP)
│   ├── db.php                   # getDB(), foodMatchesAllergies()
│   ├── gemini.php               # Groq client + ALLERGEN_MAP + validators
│   ├── mailer.php               # PHPMailer wrapper
│   ├── header.php / footer.php
│
├── css/                         # 5 stylesheets
├── js/main.js                   # Day-tab switcher, conditional fields
│
└── db/
    ├── fuelwise.sql                  # Schema (15 tables)
    ├── food_nutrition_import.sql     # Pre-classified 2,395 foods
    ├── foods_seed.sql                # 45 curated meal templates
    ├── exercises_seed.sql            # Exercise library
    ├── import_food_csvs.py           # Optional: re-classify the CSVs
    └── datasets/
        └── FOOD-DATA-GROUP1..5.csv   # Source Kaggle datasets
```

---

## 7. How the Recommendation Engine Works

A complete description is given in **§4.4.2 of the thesis**. In brief:

1. **Profile loading** — `api/recommend.php` reads the user's `health_profiles` row.
2. **Target computation** — TDEE, per-meal calorie targets, and condition-specific macro/fiber/sodium targets are computed deterministically.
3. **LLM primary path** — `geminiRecommend()` in `includes/gemini.php` builds an anonymized prompt (no user name or email is ever sent to Groq), calls the Groq endpoint, parses the JSON response, and runs three validators:
   - **Allergen-leakage check** against the 24-entry `ALLERGEN_MAP` (catches derivatives like `marzipan` from `nuts`, `satay` from `peanuts`).
   - **Structural-completeness check** (7 days × 4 meals).
   - **Macronutrient-sanity check** against the user's targets.
4. **Rule-based fallback** — If any validator rejects the plan, or the API call fails or times out, control falls through to `fetchMealsWithFallback()` in `api/recommend.php`. This:
   - Constructs a SQL query against the curated `foods` table conjoining all applicable safety, dietary and cultural filters.
   - Implements a four-tier graceful-degradation fallback for empty candidate sets: drop cultural filter → drop dietary filter → category-only.
   - Applies application-layer allergen substring filtering through `foodMatchesAllergies()` in `includes/db.php`.
5. **Persistence** — Plan is written as one INSERT into `recommendations` (with `source = 'llm'` or `source = 'dataset'`) plus seven INSERTs into `weekly_meals`, plus an UPDATE of `user_streaks`.
6. **Render** — User is redirected to `recommendation.php?id=...` which displays the plan as day tabs.

---

## 8. Admin Panel

Access the admin panel at <http://localhost/fuelwise/admin/>.

1. Enter email + password.
2. Receive a six-digit OTP by email (ten-minute expiry).
3. Enter OTP to access the dashboard.

The admin panel allows: managing users, food database, exercise library, viewing all generated plans, viewing audit logs, and handling live-chat messages from users.

---

## 9. Troubleshooting

### "Plan generation takes a long time then returns an empty plan"

Most likely the Groq API is unreachable AND the rule-based fallback's SQL filters returned no candidates (e.g. an unusual combination of condition + diet + culture). Check:

- `includes/config.php` — is `GROQ_API_KEY` valid?
- The browser's network tab — is `api/recommend.php` returning a 200?
- The MySQL `recommendations` table — was a row inserted? If yes with `source = 'dataset'`, fallback is working; if no row at all, the fallback's four-tier degradation also returned empty (rare).

### "Email isn't being sent"

- Gmail users: verify you are using a **16-character App Password**, not your account password.
- Check `includes/config.php` SMTP block.
- Check `php.ini` — `extension=openssl` must be enabled.
- Look at PHP's error log (`xampp/apache/logs/error.log`).

### "Cannot find /admin/ — 404"

Apache `mod_rewrite` is not enabled. In `xampp/apache/conf/httpd.conf` un-comment `LoadModule rewrite_module modules/mod_rewrite.so` and restart Apache.

### "MySQL: Access denied for user 'root'@'localhost'"

You set a MySQL root password but didn't update `DB_PASS` in `includes/config.php`. Update it.

### "ImportError: pandas not found" (when running the Python importer)

The Python importer is **optional** — you only need it if you want to regenerate `food_nutrition_import.sql`. For normal use, just import the pre-built SQL file.

If you do want to run it: `pip install pandas` (or `pip3 install pandas` on macOS / Linux).

### "Got a 'rate limit exceeded' error from Groq"

Groq's free tier has request-rate limits. Wait one minute, or upgrade your Groq plan. The system will automatically use the rule-based fallback in the meantime, so the user-facing flow is unaffected.

---

## 10. Acknowledgements

- Food nutrition data from five publicly available Kaggle datasets (FOOD-DATA-GROUP1 through GROUP5).
- Glycaemic-index thresholds: Atkinson et al. (2021), *International tables of glycemic index and glycemic load values 2021*.
- FODMAP food classifications: Bertin et al. (2024), *The Role of the FODMAP Diet in IBS*; Monash University FODMAP database.
- Sodium thresholds: American Heart Association DASH guidelines.
- Weight-management thresholds: World Health Organization dietary guidelines.
- LLM inference: Groq's hosted Llama-3.3-70B-Versatile.
- Email transport: PHPMailer.

For the full architectural rationale, design decisions, evaluation walkthrough and limitations, see the accompanying thesis document `FINAL PROJECT.docx`.

---

## License & Disclaimer

This project is an academic prototype submitted for the CTEC3451 module at De Montfort University. It is **not a medical device** and has not undergone clinical validation. Plans generated by FuelWise should not be used as a substitute for advice from a registered dietitian, physician or other qualified healthcare professional. Users with severe allergies should manually verify each generated meal before consuming it.


============================================================
