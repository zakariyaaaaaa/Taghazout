<div align="center">

<img src="https://readme-typing-svg.demolab.com?font=Playfair+Display&size=42&duration=3000&pause=1000&color=1E6FD9&center=true&vCenter=true&width=600&lines=🌊+Taghazout+Platform;Morocco's+Surf+Capital;Book+%7C+Explore+%7C+Experience" alt="Taghazout" />

<br/>

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-008CDD?style=for-the-badge&logo=stripe&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

<br/>

> **A full-stack tourism platform for Taghazout Bay** — Morocco's world-famous surf village 🇲🇦  
> Book hotels, discover restaurants, join surf courses, and earn loyalty points.

<br/>

![Wave Divider](https://capsule-render.vercel.app/api?type=waving&color=1E6FD9&height=100&section=header&text=&fontSize=0)

</div>

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🏄 Surf Courses
Browse and book surf lessons with certified instructors directly from the platform.

### 🏨 Hotel Booking
Discover and reserve the best local accommodations in Taghazout Bay.

### 🍽️ Restaurants
Explore handpicked dining spots — from tagine spots to beach cafés.

### 🖼️ Gallery
A visual journey through Taghazout's landscapes, waves, and culture.

</td>
<td width="50%">

### 🎁 Loyalty Points System
Earn points with every booking and redeem them for exclusive rewards.

### 💳 Stripe Payments
100% secure online payments powered by Stripe.

### 🔔 Smart Notifications
Real-time alerts for bookings, rewards, and updates.

### ❤️ Favorites
Save your favorite hotels, restaurants, and activities.

</td>
</tr>
</table>

---

## 🛠️ Tech Stack

<div align="center">

| Layer | Technology | Version |
|:---:|:---:|:---:|
| 🖥️ Backend | PHP | 8.0+ |
| 🗄️ Database | MySQL | 8.0+ |
| 🎨 Frontend | HTML5 + CSS3 + JS | — |
| 💳 Payments | Stripe API | v20 |
| 📦 Dependencies | Composer | 2.x |
| 🌐 Server | XAMPP / Apache | — |

</div>

---

## 🚀 Getting Started

### Prerequisites

\`\`\`bash
PHP >= 8.0
MySQL >= 8.0
Composer
XAMPP (or any Apache server)
\`\`\`

### Installation

\`\`\`bash
# 1️⃣ Clone the repository
git clone https://github.com/zakariyaaaaaa/Taghazout.git
cd Taghazout

# 2️⃣ Install PHP dependencies
composer install

# 3️⃣ Set up configuration
cp includes/config.example.php includes/config.php
# ✏️ Edit config.php with your credentials

# 4️⃣ Import the database
# Import taghazout.sql via phpMyAdmin or CLI

# 5️⃣ Launch the app
# Start Apache & MySQL in XAMPP
# 🌐 Visit: http://localhost/Taghazout
\`\`\`

---

## ⚙️ Configuration

\`\`\`php
<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'taghazout');
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('STRIPE_PUBLIC_KEY', 'pk_test_...');
\`\`\`

> ⚠️ **Never commit \`config.php\`** — it's already in \`.gitignore\`

---

## 📁 Project Structure

\`\`\`
🌊 Taghazout/
├── 📁 assets/              # CSS, JS, images, icons
├── 📁 includes/            # Config, DB connection, helpers
├── 📁 profile/             # User profile & loyalty pages
│   ├── loyalty-points.php
│   ├── loyalty-store.php
│   └── redeem-points.php
├── 📁 payment/             # Stripe payment flow
│   ├── payment-success.php
│   └── payment-failed.php
├── 📁 uploads/             # User uploads (gitignored)
├── 📁 vendor/              # Composer deps (gitignored)
├── 📄 index.php            # Entry point
├── 📄 hotels.php           # Hotel listings
├── 📄 restaurants.php      # Restaurant listings
├── 📄 surf-courses.php     # Surf course listings
├── 📄 gallery.php          # Photo gallery
├── 📄 favorites.php        # Saved favorites
└── 📄 notifications.php    # User notifications
\`\`\`

---

## 🔐 Security

- 🔒 \`includes/config.php\` — excluded from Git (DB credentials & API keys)
- 📁 \`uploads/\` — excluded from Git (user files)
- 📦 \`vendor/\` — excluded, run \`composer install\` after cloning
- 💳 Stripe handles all payment data (PCI compliant)

---

## 📍 About Taghazout

<div align="center">

*Taghazout is a small fishing village on Morocco's Atlantic coast,*  
*world-renowned for its surf breaks, golden sunsets, and laid-back lifestyle.*  
*This platform connects visitors with the very best local experiences.*

🌊 🏄 🌅 🇲🇦

</div>

---

## 👨‍💻 Author

<div align="center">

**Zakariya**

[![GitHub](https://img.shields.io/badge/GitHub-@zakariyaaaaaa-181717?style=for-the-badge&logo=github)](https://github.com/zakariyaaaaaa)

<br/>

*Built with ❤️ from Morocco 🇲🇦*

![Footer](https://capsule-render.vercel.app/api?type=waving&color=1E6FD9&height=80&section=footer)

</div>
