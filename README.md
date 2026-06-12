<div align="center">

<img src="https://readme-typing-svg.demolab.com?font=Playfair+Display&size=40&duration=3000&pause=1000&color=1E6FD9&center=true&vCenter=true&width=700&lines=🌊+Taghazout+Platform;Morocco's+Surf+Capital+%F0%9F%87%B2%F0%9F%87%A6;Book+%7C+Explore+%7C+Experience" alt="Taghazout Platform" />

<br/>

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-008CDD?style=for-the-badge&logo=stripe&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)

<br/>

> 🏄 **A full-stack tourism & lifestyle platform for Taghazout Bay**  
> Morocco's world-famous surf village — Book hotels, discover restaurants, join surf courses & earn loyalty points.

<br/>

![visitors](https://visitor-badge.laobi.icu/badge?page_id=zakariyaaaaaa.Taghazout)
[![GitHub stars](https://img.shields.io/github/stars/zakariyaaaaaa/Taghazout?style=social)](https://github.com/zakariyaaaaaa/Taghazout/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/zakariyaaaaaa/Taghazout?style=social)](https://github.com/zakariyaaaaaa/Taghazout/network)

</div>

<img src="https://capsule-render.vercel.app/api?type=waving&color=1E6FD9&height=120&section=header" width="100%"/>

---

## 📋 Table of Contents

- [✨ Features](#-features)
- [🛠️ Tech Stack](#️-tech-stack)
- [🚀 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [📁 Project Structure](#-project-structure)
- [🗄️ Database](#️-database)
- [💳 Stripe Payments](#-stripe-payments)
- [🔐 Security](#-security)
- [📍 About Taghazout](#-about-taghazout)

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🏄 Surf Courses
Browse and book surf lessons with certified local instructors. View schedules, difficulty levels, and instructor profiles.

### 🏨 Hotel Booking
Discover and reserve the best accommodations in Taghazout Bay — from surf camps to boutique riads.

### 🍽️ Restaurants
Explore handpicked dining spots — from fresh seafood to traditional tagine and beachside cafés.

### 🖼️ Gallery
A stunning visual journey through Taghazout's waves, landscapes, and vibrant culture.

</td>
<td width="50%">

### 🎁 Loyalty Points System
Earn points with every booking and activity. Redeem them for discounts, free nights, and exclusive rewards.

### 💳 Secure Payments
100% secure online payments powered by **Stripe** — supports cards and major payment methods.

### 🔔 Smart Notifications
Real-time alerts for booking confirmations, loyalty rewards, and platform updates.

### ❤️ Favorites
Save and revisit your favorite hotels, restaurants, surf spots, and activities.

</td>
</tr>
</table>

---

## 🛠️ Tech Stack

<div align="center">

| Layer | Technology | Details |
|:---:|:---:|:---:|
| 🖥️ **Backend** | PHP 8.0+ | Vanilla PHP, no framework |
| 🗄️ **Database** | MySQL 8.0+ | Relational DB via phpMyAdmin |
| 🎨 **Frontend** | HTML5 + CSS3 + JS | Custom design system |
| 💳 **Payments** | Stripe API | stripe-php v20 via Composer |
| 📦 **Dependencies** | Composer | Autoload + Stripe SDK |
| 🌐 **Server** | Apache / XAMPP | Local & production ready |
| 🔤 **Fonts** | Google Fonts | Playfair Display + Outfit |

</div>

---

## 🚀 Installation

### ✅ Prerequisites

Make sure you have the following installed:

- **PHP** >= 8.0
- **MySQL** >= 8.0
- **Composer** — [getcomposer.org](https://getcomposer.org)
- **XAMPP** — [apachefriends.org](https://www.apachefriends.org)
- **Git** — [git-scm.com](https://git-scm.com)

---

### 📦 Step-by-Step Setup

#### 1️⃣ Clone the repository

\`\`\`bash
git clone https://github.com/zakariyaaaaaa/Taghazout.git
cd Taghazout
\`\`\`

#### 2️⃣ Install PHP dependencies (Stripe + Composer autoload)

\`\`\`bash
composer install
\`\`\`

> This will install **stripe/stripe-php** and generate the `vendor/autoload.php` file.

#### 3️⃣ Configure the project

\`\`\`bash
cp includes/config.example.php includes/config.php
\`\`\`

Then open `includes/config.php` and fill in your credentials (see [Configuration](#️-configuration)).

#### 4️⃣ Import the database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Create a new database named `taghazout`
3. Click **Import** and select the `.sql` file from the project

#### 5️⃣ Start the server

1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL**
3. Visit: [http://localhost/Taghazout](http://localhost/Taghazout) 🎉

---

## ⚙️ Configuration

Create your config file from the example:

\`\`\`bash
cp includes/config.example.php includes/config.php
\`\`\`

Edit `includes/config.php`:

\`\`\`php
<?php
// ─── Database ───────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // your MySQL username
define('DB_PASS', '');             // your MySQL password
define('DB_NAME', 'taghazout');    // your database name

// ─── Stripe Payments ────────────────────────
define('STRIPE_SECRET_KEY', 'sk_test_...');   // from stripe.com dashboard
define('STRIPE_PUBLIC_KEY', 'pk_test_...');   // from stripe.com dashboard

// ─── App ────────────────────────────────────
define('BASE_URL', 'http://localhost/Taghazout');
\`\`\`

> ⚠️ **Never push `config.php` to GitHub** — it contains sensitive credentials.  
> It is already listed in `.gitignore` ✅

---

## 📁 Project Structure

\`\`\`
🌊 Taghazout/
│
├── 📁 assets/
│   ├── 📁 css/                 # Stylesheets & design system
│   ├── 📁 js/                  # JavaScript files
│   ├── 📁 images/              # Icons, photos, UI assets
│   └── 📁 videos/              # Background videos (gitignored if large)
│
├── 📁 includes/
│   ├── config.php              # ⚠️ DB & API keys (gitignored)
│   ├── config.example.php      # Template for config
│   ├── db.php                  # Database connection
│   └── helpers.php             # Utility functions
│
├── 📁 profile/
│   ├── profile.php             # User profile page
│   ├── loyalty-points.php      # Points balance & history
│   ├── loyalty-store.php       # Rewards store
│   └── redeem-points.php       # Redeem rewards
│
├── 📁 payment/
│   ├── payment-success.php     # Stripe success handler
│   └── payment-failed.php      # Stripe failure handler
│
├── 📁 uploads/                 # User-uploaded files (gitignored)
├── 📁 vendor/                  # Composer packages (gitignored)
│
├── 📄 index.php                # 🏠 Homepage
├── 📄 hotels.php               # 🏨 Hotel listings
├── 📄 hotel-details.php        # Hotel detail page
├── 📄 restaurants.php          # 🍽️ Restaurant listings
├── 📄 restaurant-details.php   # Restaurant detail page
├── 📄 surf-courses.php         # 🏄 Surf course listings
├── 📄 Surf-course-details.php  # Surf course detail page
├── 📄 activities.php           # 🎯 Activities page
├── 📄 activity-details.php     # Activity detail page
├── 📄 gallery.php              # 🖼️ Photo gallery
├── 📄 favorites.php            # ❤️ Saved favorites
├── 📄 notifications.php        # 🔔 User notifications
├── 📄 contact.php              # 📬 Contact page
├── 📄 toggle_favorite.php      # AJAX favorite toggle
│
├── 📄 .gitignore               # Git exclusions
├── 📄 composer.json            # Composer dependencies
├── 📄 composer.lock            # Locked dependency versions
└── 📄 README.md                # This file
\`\`\`

---

## 🗄️ Database

The project uses **MySQL**. The schema includes tables for:

| Table | Description |
|:---:|:---|
| `users` | Registered users & authentication |
| `hotels` | Hotel listings & details |
| `restaurants` | Restaurant listings |
| `surf_courses` | Surf course catalog |
| `activities` | Local activities |
| `bookings` | All user bookings |
| `favorites` | User saved favorites |
| `loyalty_points` | Points earned per user |
| `rewards` | Redeemable rewards catalog |
| `notifications` | User notification log |
| `payments` | Stripe payment records |

To import:
\`\`\`bash
# Via CLI
mysql -u root -p taghazout < database/taghazout.sql

# Or via phpMyAdmin (recommended for beginners)
\`\`\`

---

## 💳 Stripe Payments

This project uses [Stripe](https://stripe.com) for secure payment processing.

1. Create a free account at [stripe.com](https://stripe.com)
2. Go to **Developers → API Keys**
3. Copy your **Publishable key** and **Secret key**
4. Paste them in `includes/config.php`

\`\`\`php
define('STRIPE_SECRET_KEY', 'sk_test_xxxxxxxxxxxx');
define('STRIPE_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxx');
\`\`\`

> For testing, use Stripe's test card: `4242 4242 4242 4242` — any future date — any CVC

---

## 🔐 Security

| File / Folder | Status | Reason |
|:---:|:---:|:---|
| `includes/config.php` | 🚫 Gitignored | Contains DB credentials & API keys |
| `uploads/` | 🚫 Gitignored | User-uploaded files (can be large/sensitive) |
| `vendor/` | 🚫 Gitignored | Auto-generated by Composer |
| `*.log` | 🚫 Gitignored | Server error logs |
| `composer.json` | ✅ Tracked | Defines dependencies |
| `composer.lock` | ✅ Tracked | Locks exact versions for reproducibility |

---

## 📍 About Taghazout

<div align="center">

*Taghazout (تغازوت) is a small Berber fishing village on Morocco's Atlantic coast,*  
*located 15km north of Agadir. World-renowned for its surf breaks — Hash Point,*  
*Anchor Point, Killer Point — it attracts surfers and travelers from across the globe.*

*This platform was built to connect visitors with the authentic local experience.*

<br/>

🌊 **Waves** &nbsp;|&nbsp; 🏄 **Surf** &nbsp;|&nbsp; 🌅 **Sunsets** &nbsp;|&nbsp; 🫖 **Mint Tea** &nbsp;|&nbsp; 🇲🇦 **Morocco**

</div>

---

## 👨‍💻 Author

<div align="center">

**Zakariya**

[![GitHub](https://img.shields.io/badge/GitHub-zakariyaaaaaa-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/zakariyaaaaaa)

<br/>

*Built with ❤️ from Morocco 🇲🇦*

</div>

<img src="https://capsule-render.vercel.app/api?type=waving&color=1E6FD9&height=120&section=footer" width="100%"/>
