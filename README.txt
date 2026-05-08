============================================================
  FuelWise v2.0 — AI-Powered Personalized Nutrition
  Installation & Setup Guide
============================================================

Author:   Sunil Thapa (P2837603)
Project:  Final Year Thesis — Niels Brock
Course:   BSc Computer Science

============================================================
  WHAT'S NEW IN v2.0
============================================================

- Supports 5 health conditions (was 2):
    * Type 2 Diabetes
    * Pre-Diabetes        (NEW)
    * Hypertension        (NEW)
    * Weight Management   (NEW)
    * IBS
- Multi-condition support — users can select multiple concerns
- 7-day rotating meal plans (was 1-day)
- Exercise recommendations with condition-aware safety filtering
- In-app notifications + positive streak tracking
- Ingredient-level allergy filtering
- 2,395-food nutrition database (Kaggle import)
- Quick-update modal for fast plan regeneration
- DEV_MODE on-screen OTP/link display for testing without SMTP


============================================================
  REQUIREMENTS
============================================================

- XAMPP (Apache + MySQL + PHP 8.0+)
- Python 3.8+  (one-time, for CSV import)
- pandas       (pip install pandas)
- PHPMailer    (for email)
- A Gmail account with 2FA + App Password (for SMTP)


============================================================
  INSTALLATION STEPS
============================================================

STEP 1 — Place the project folder
----------------------------------------
Copy the `fuelwise/` folder to:

    C:\xampp\htdocs\fuelwise\

Start XAMPP's Apache and MySQL from the control panel.


STEP 2 — Create the database
----------------------------------------
1. Open phpMyAdmin:  http://localhost/phpmyadmin
2. Import in this order:
       db/fuelwise.sql        (schema — all tables)
       db/fuelwise_seed.sql   (45 curated meals + 28 exercises)


STEP 3 — Import the 2,395-food nutrition database
----------------------------------------
The Kaggle nutrition CSVs live in `db/datasets/`. They need
to be processed once into an SQL file, then imported.

1. Open a terminal, cd into the db folder:

       cd C:\xampp\htdocs\fuelwise\db

2. Install pandas if you don't have it:

       pip install pandas

3. Run the importer:

       python import_food_csvs.py

   Expected output:
       Total foods after dedup: 2395
       SQL file generated: food_nutrition_import.sql

4. Import the generated file in phpMyAdmin:

       db/food_nutrition_import.sql

   This populates the `food_nutrition` table with 2,395 foods
   classified for diabetes/IBS/hypertension/prediabetes/
   weight-loss safety + FODMAP/GI categories.

   Classification sources (citable for thesis):
       - Atkinson et al. 2021 — Glycemic Index
       - Bertin et al. 2024    — FODMAP levels
       - AHA Guidelines        — sodium thresholds
       - WHO 2020              — calorie/fiber thresholds


STEP 4 — Configure email (Gmail SMTP)
----------------------------------------
1. Enable 2-Step Verification:
       https://myaccount.google.com/security

2. Generate an App Password:
       https://myaccount.google.com/apppasswords

3. Open `includes/db.php` and update:

       define('MAIL_USERNAME', 'your.email@gmail.com');
       define('MAIL_PASSWORD', 'your16charapppassword');
       define('MAIL_FROM',     'your.email@gmail.com');

   IMPORTANT: The App Password is 16 characters, no spaces.
   Do NOT use your normal Gmail password.

4. Install PHPMailer (choose ONE):

   Option A — Composer:
       cd C:\xampp\htdocs\fuelwise
       composer require phpmailer/phpmailer

   Option B — Manual:
       Download from github.com/PHPMailer/PHPMailer
       Extract to:  fuelwise/phpmailer/
       (src/PHPMailer.php must exist at that path.)

5. Enable OpenSSL in PHP:
       - Open C:\xampp\php\php.ini
       - Find `;extension=openssl`, remove the semicolon
       - Save and restart Apache


STEP 5 — DEV MODE (test without SMTP)
----------------------------------------
If you haven't set up email but want to test auth flows:

   In includes/db.php:
       define('DEV_MODE', true);   // already set by default

When DEV_MODE is true AND email sending fails:
   - Password reset links appear on the forgot-password page
   - Admin OTPs appear on the verify-otp page
   - Both shown in a yellow dev-mode box

>>> SET `DEV_MODE` TO `false` BEFORE DEPLOYMENT. <<<


STEP 6 — Access the app
----------------------------------------
User site:   http://localhost/fuelwise/
Admin panel: http://localhost/fuelwise/admin/login.php

Default admin credentials:
       Email:    admin@fuelwise.com
       Password: Admin@123
       (Change this after first login.)


============================================================
  PROJECT STRUCTURE
============================================================

fuelwise/
├── admin/                 Admin dashboard
│   ├── index.php          Stats (6-condition breakdown)
│   ├── users.php          User management
│   ├── foods.php          Curated meals CRUD
│   ├── exercises.php      NEW — Exercises CRUD
│   ├── recommendations.php Plan history
│   ├── login.php          OTP-gated login
│   └── verify-otp.php
├── api/
│   └── recommend.php      7-day plan engine + exercises
├── css/
│   ├── style.css          Core design
│   ├── dashboard.css
│   ├── auth.css
│   ├── admin.css
│   └── fuelwise-v2.css    NEW — modal, notifications,
│                          day tabs, exercise cards
├── db/
│   ├── fuelwise.sql                Schema (all tables)
│   ├── fuelwise_seed.sql           Meals + exercises
│   ├── import_food_csvs.py         Python → SQL generator
│   ├── food_nutrition_import.sql   Generated (2,395 foods)
│   └── datasets/                   Kaggle CSVs (5 files)
├── includes/
│   ├── db.php             Config + helpers + streaks +
│                          notifications + allergy matcher
│   ├── mailer.php         PHPMailer + dev-mode helpers
│   ├── header.php
│   └── footer.php
├── js/main.js
├── logs/                  mail.log (auto-created)
├── index.php
├── about.php
├── register.php
├── login.php              Tracks streaks on login
├── logout.php
├── forgot-password.php    Dev-mode reset link display
├── reset-password.php
├── assessment.php         5 conditions, split BP inputs
├── recommendation.php     7-day tabs, exercises, modal
├── dashboard.php          Notifications, streak, today
├── history.php
└── README.txt


============================================================
  DATABASE SCHEMA (v2.0)
============================================================

From v1:
   users, health_profiles, foods, recommendations,
   password_resets, admins, admin_logs

New in v2:
   food_nutrition     → 2,395 foods with nutrients + flags
   weekly_meals       → 7-day meal plan storage
   exercises          → Exercise library with safety flags
   notifications      → In-app notification feed
   user_streaks       → Login streak tracking
   profile_updates    → Analytics log for thesis

Expanded condition_type enum:
   diabetes | ibs | hypertension | prediabetes |
   weight_management | both | multiple

New health_profiles columns:
   conditions_list         (comma-separated multi-select)
   blood_pressure_systolic, blood_pressure_diastolic
   insulin_level, cholesterol_total
   weight_goal, target_weight


============================================================
  FEATURE CHECKLIST
============================================================

[✓] 5 health conditions with safety-aware filtering
[✓] Multi-condition combinations (intersection of rules)
[✓] 7-day rotating meal plan with day tabs
[✓] Shuffle / regenerate button
[✓] Quick-update modal with pre-filled saved profile
[✓] "Skip — Use Saved Profile" 1-click option
[✓] Ingredient-level allergy filtering
[✓] Exercise recommendations:
      - Light cardio/yoga safe for ALL
      - Gym ONLY for weight-management + moderate+ activity
      - Hypertension blocks heavy lifting (AHA)
      - IBS blocks abdominal-pressure exercises
[✓] In-app notification system
[✓] Positive streak tracking (supportive, no shaming)
[✓] Welcome / inactivity / streak milestone triggers
[✓] Clinical thresholds with graceful degradation
      (all medical fields optional)
[✓] Admin panel with exercise CRUD
[✓] DEV_MODE for testing without email


============================================================
  THESIS CITATIONS USED IN CODE
============================================================

Dietary rules:
   Atkinson et al. (2021)  — Glycemic Index Tables
   Bertin et al. (2024)    — FODMAP classifications
   AHA DASH Guidelines      — Sodium/potassium thresholds
   WHO 2020                 — Calorie/fiber benchmarks
   ADA Position Statement   — Diabetes macro ratios

Exercise safety:
   WHO Physical Activity Guidelines 2020
   CDC Physical Activity Basics
   ADA Exercise Position Statement (2023)
   AHA Exercise Guidelines for Hypertension (2021)
   Ainsworth et al. (2011) — Compendium of Physical
     Activities (MET values)

Data sources:
   Kaggle Food Nutrition Dataset (5 CSVs, 2,395 foods)
   Kaggle Workout Dataset (gym exercises)


============================================================
  TROUBLESHOOTING
============================================================

Emails don't send
-----------------
1. Check logs/mail.log for error messages
2. Verify MAIL_PASSWORD is a 16-char App Password (no spaces)
3. Ensure OpenSSL is enabled in php.ini
4. Use DEV_MODE=true as workaround during testing

CSV import fails
----------------
1. Verify pandas: pip install pandas
2. Ensure CSVs are in db/datasets/ folder
3. Python output should show "Total foods after dedup: 2395"

"No suitable meals found"
-------------------------
1. User preferences may be too restrictive
2. Check fuelwise_seed.sql imported (should be ~45 meals)
3. Engine has 4 fallback tiers before giving up

7-day tabs not switching
------------------------
1. Check browser console for JavaScript errors
2. Clear cache (Ctrl+Shift+Del)

Gym exercises not showing for weight-management user
----------------------------------------------------
Make sure:
- User has weight_management OR weight_goal='lose'
- activity_level is moderately_active, very_active, or extra_active
(This restriction is by design — AHA/ADA guidelines.)


============================================================
  NOTES FOR THESIS DEFENSE
============================================================

Likely examiner questions:

1. "Why rule-based classification, not ML?"
   → Transparent, citable, reproducible. Published FODMAP
     and GI food lists (Atkinson 2021, Bertin 2024) are the
     clinical standard. ML on 2,395 foods with binary
     safety labels would just learn the same keyword rules
     with added opacity.

2. "Why block gym exercises for hypertension?"
   → AHA 2021 recommends avoiding heavy resistance training
     with uncontrolled BP — isometric effort can spike
     systolic pressure 10-20 mmHg during the lift.

3. "Why 7-day plans, not daily?"
   → Reduces decision fatigue. Allows weekly grocery
     shopping. Matches standard behavioural-adherence
     research for chronic condition management.

4. "Why positive streaks, no shaming?"
   → Published evidence (Michie et al. 2013, Behaviour
     Change Technique Taxonomy) shows positive reinforcement
     outperforms guilt-based interventions, especially for
     chronic-condition adherence.

5. "How do you handle missing clinical values?"
   → All optional. Engine uses TDEE + condition defaults.
     Warnings only appear when values ARE provided AND
     exceed clinical thresholds. No inference of missing
     values — respects that users may not know these.

6. "Why intersect safety filters for multi-condition users?"
   → Conservative by design. If a food must be both
     diabetes-safe AND hypertension-safe, we keep only the
     intersection. Better to show fewer safe foods than
     recommend something contraindicated.


============================================================
  CHANGELOG
============================================================

v2.0  (current)
  - 3 new conditions (hypertension, prediabetes, weight mgmt)
  - 7-day meal planning with day tabs
  - Exercise recommendations with condition safety
  - In-app notifications + streak tracking
  - 2,395-food nutrition DB (Kaggle import)
  - Quick-update modal
  - Ingredient-level allergy filtering
  - Dev-mode OTP display
  - Admin exercise CRUD

v1.0  (original)
  - Diabetes + IBS support
  - Single-day meal plan
  - OTP admin auth
  - PHPMailer integration

============================================================
