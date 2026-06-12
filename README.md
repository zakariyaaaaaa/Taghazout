# 🚀 5 مشاريع PHP كاملة — مستوى متقدم

> كل مشروع فيه: الفكرة، الـ Features، قاعدة البيانات، الـ Tech Stack، وتقدير الوقت

---

---

# 🏥 1. ClinicFlow — نظام إدارة عيادة طبية

## 💡 الفكرة
منصة متكاملة لإدارة عيادة طبية — من حجز المواعيد وحتى الوصفات الطبية والدفع. ثلاثة أدوار مختلفة: طبيب، مريض، مسؤول.

## ✨ Features

### 👤 للمريض
- تسجيل حساب + تحميل وثائق طبية (PDF/صور)
- حجز موعد مع اختيار الطبيب، التاريخ، والوقت
- عرض سجل المواعيد والوصفات
- دفع الكشوفات عبر Stripe
- إشعارات (تأكيد موعد، تذكير قبل 24h)
- Chat مع الطبيب (messaging system)

### 🩺 للطبيب
- لوحة تحكم مع جدول المواعيد اليومي (Calendar view)
- قبول/رفض المواعيد مع سبب
- كتابة وصفة طبية رقمية قابلة للطباعة (PDF)
- عرض ملف المريض الكامل (تاريخ + وثائق)
- إدارة أوقات العمل (availability schedule)
- إحصائيات: عدد المرضى، أكثر الأمراض...

### 🔧 للمسؤول (Admin)
- إضافة/حذف أطباء ومرضى
- إدارة التخصصات والأقسام
- تقارير مالية (إيرادات يومية/شهرية)
- سجل كامل لكل العمليات (audit log)

## 🗄️ قاعدة البيانات

```sql
users (id, name, email, password, role, phone, avatar, created_at)
doctors (id, user_id, specialty, bio, price_per_consultation, rating)
patients (id, user_id, date_of_birth, blood_type, allergies)
appointments (id, doctor_id, patient_id, date, time, status, reason, notes)
prescriptions (id, appointment_id, doctor_id, patient_id, medications, instructions, pdf_path, created_at)
medical_documents (id, patient_id, title, file_path, type, uploaded_at)
payments (id, appointment_id, amount, stripe_payment_id, status, paid_at)
messages (id, sender_id, receiver_id, content, is_read, sent_at)
notifications (id, user_id, title, body, is_read, type, created_at)
doctor_availability (id, doctor_id, day_of_week, start_time, end_time, is_active)
reviews (id, doctor_id, patient_id, rating, comment, created_at)
```

## 🛠️ Tech Stack
- **Backend**: PHP 8+ (OOP + MVC pattern)
- **Database**: MySQL 8
- **Frontend**: HTML5 + CSS3 + JS + Chart.js (للإحصائيات)
- **Payments**: Stripe API
- **PDF**: TCPDF أو DomPDF (وصفات + فواتير)
- **Server**: XAMPP / Apache
- **Extra**: PHPMailer (إشعارات email)

## ⏱️ تقدير الوقت
| Phase | المدة |
|---|---|
| Setup + Auth system | 1 أسبوع |
| Patient module | 2 أسبوع |
| Doctor module | 2 أسبوع |
| Admin + Reports | 1 أسبوع |
| Payments + PDF | 1 أسبوع |
| Testing + Deploy | 1 أسبوع |
| **المجموع** | **~8 أسابيع** |

## 🔥 ليش هو impressive فالـ CV؟
HealthTech = domain صعب ومطلوب. Role-based system + PDF generation + real-time notifications = مهارات advanced بينة.

---

---

# 🏠 2. DarMa — منصة إيجار شقق مغربية

## 💡 الفكرة
Airbnb ولكن للسوق المغربي — إيجار يومي/شهري للشقق والرياضات والفيلات. بائع + مستأجر + admin.

## ✨ Features

### 👤 للمستأجر
- بحث متقدم (مدينة، تاريخ، سعر، نوع، عدد غرف)
- خريطة تفاعلية (Google Maps API)
- حجز مع تأكيد فوري أو طلب موافقة
- دفع عبر Stripe + إيصال PDF
- نظام تقييم ومراجعة بعد الإقامة
- Favorites + مقارنة بين عقارين
- Chat مع صاحب العقار

### 🏠 لصاحب العقار (Host)
- إضافة عقار مع صور متعددة (drag & drop upload)
- تقويم الإتاحة (availability calendar)
- قبول/رفض الحجوزات
- إحصائيات: أرباح، تقييمات، عدد الزوار
- سياسة الإلغاء (مرنة / صارمة)
- Payout نظام (سحب الأرباح)

### 🔧 Admin
- موافقة على العقارات الجديدة (verification system)
- إدارة النزاعات بين المستأجر والمالك
- كومسيون على كل حجز (configurable %)
- تقارير مالية + خريطة حرارية للمناطق الأكثر طلبًا

## 🗄️ قاعدة البيانات

```sql
users (id, name, email, password, role, phone, avatar, verified, created_at)
properties (id, host_id, title, description, type, city, address, lat, lng, price_per_night, max_guests, status)
property_images (id, property_id, image_path, is_primary)
property_amenities (id, property_id, amenity) -- wifi, pool, parking...
bookings (id, property_id, guest_id, check_in, check_out, guests_count, total_price, status, created_at)
payments (id, booking_id, amount, commission, host_payout, stripe_id, status, paid_at)
reviews (id, booking_id, property_id, reviewer_id, rating, comment, host_reply, created_at)
availability (id, property_id, date, is_blocked)
messages (id, booking_id, sender_id, content, sent_at)
favorites (id, user_id, property_id, saved_at)
payouts (id, host_id, amount, status, processed_at)
```

## 🛠️ Tech Stack
- **Backend**: PHP 8+ MVC
- **Database**: MySQL
- **Frontend**: HTML + CSS + Vanilla JS + Leaflet.js (خرائط مجانية)
- **Payments**: Stripe (Connect للـ payouts)
- **Images**: Cloudinary API أو local storage
- **Extra**: Google Maps / OpenStreetMap

## ⏱️ تقدير الوقت
| Phase | المدة |
|---|---|
| Auth + Property CRUD | 1.5 أسبوع |
| Search + Map integration | 1.5 أسبوع |
| Booking system + Calendar | 2 أسبوع |
| Payments + Payouts | 1.5 أسبوع |
| Reviews + Chat | 1 أسبوع |
| Admin + Deploy | 1 أسبوع |
| **المجموع** | **~8.5 أسابيع** |

## 🔥 ليش هو impressive فالـ CV؟
Marketplace بنظام commission + multi-role + خرائط تفاعلية = من أصعب أنواع الـ web apps.

---

---

# 🎓 3. MadrasaTech — منصة تعليمية

## 💡 الفكرة
منصة كورسات أونلاين بالدارجة/العربية — أستاذ يرفع كورسات، طالب يشري ويتعلم، نظام تقدم وشهادات. بحال Udemy ولكن ديالك.

## ✨ Features

### 👤 للطالب
- تصفح كورسات بالفئة / البحث / التقييم
- شراء كورس عبر Stripe أو نقاط
- مشاهدة فيديوهات (YouTube embed أو upload)
- تقدم مرئي (progress bar بالـ %)
- اختبارات (quiz) بعد كل درس
- شهادة PDF عند الإكمال مع QR Code للتحقق
- Bookmarks + ملاحظات شخصية داخل الكورس
- Q&A section لكل درس

### 👨‍🏫 للأستاذ
- لوحة إنشاء كورس (sections + lessons drag & drop)
- رفع فيديوهات أو ربط YouTube
- إنشاء اختبارات (quiz builder)
- إحصائيات: عدد الطلاب، الدروس الأكثر مشاهدة، ratings
- أرباح + طلب سحب

### 🔧 Admin
- موافقة على الكورسات قبل النشر
- إدارة الفئات والعلامات
- نسبة الكومسيون
- تقارير + إحصائيات عامة

## 🗄️ قاعدة البيانات

```sql
users (id, name, email, password, role, bio, avatar, created_at)
courses (id, instructor_id, title, description, category_id, price, thumbnail, level, language, status)
sections (id, course_id, title, position)
lessons (id, section_id, title, video_url, duration, position, is_free_preview)
enrollments (id, user_id, course_id, enrolled_at, completed_at)
lesson_progress (id, enrollment_id, lesson_id, watched_at, is_completed)
quizzes (id, lesson_id, title)
quiz_questions (id, quiz_id, question, options JSON, correct_answer)
quiz_attempts (id, user_id, quiz_id, score, attempted_at)
certificates (id, user_id, course_id, certificate_code, issued_at, pdf_path)
reviews (id, user_id, course_id, rating, comment, created_at)
payments (id, user_id, course_id, amount, stripe_id, status, paid_at)
bookmarks (id, user_id, lesson_id, note, created_at)
```

## 🛠️ Tech Stack
- **Backend**: PHP 8+ OOP
- **Database**: MySQL
- **Frontend**: HTML + CSS + JS + Video.js
- **PDF**: TCPDF (شهادات مع QR Code)
- **Payments**: Stripe
- **Extra**: QR Code library للتحقق من الشهادات

## ⏱️ تقدير الوقت
| Phase | المدة |
|---|---|
| Auth + Course CRUD | 1.5 أسبوع |
| Video player + Progress | 1.5 أسبوع |
| Quiz system | 1 أسبوع |
| Payments + Certificates PDF | 1.5 أسبوع |
| Instructor dashboard | 1 أسبوع |
| Admin + Deploy | 1 أسبوع |
| **المجموع** | **~7.5 أسابيع** |

## 🔥 ليش هو impressive فالـ CV؟
EdTech + PDF certificates مع QR verification + quiz engine = مشروع يبان فيه depth حقيقي.

---

---

# 🛒 4. SouqPro — متجر إلكتروني Multi-Vendor

## 💡 الفكرة
منصة تجارة إلكترونية فيها عدة بائعين — كل بائع عنده متجره الخاص، والزبون يقدر يشري من متاجر مختلفة في نفس الوقت.

## ✨ Features

### 👤 للزبون
- تصفح منتجات + فلترة (فئة، سعر، تقييم، بائع)
- سلة تسوق من عدة متاجر
- دفع موحد عبر Stripe (تقسيم تلقائي للبائعين)
- تتبع الطلب (pending → processing → shipped → delivered)
- مراجعات + تقييمات المنتجات
- كود خصم / كوبون
- Wishlist + مقارنة منتجات
- سجل الطلبات + إعادة الطلب

### 🏪 للبائع
- لوحة تحكم: إدارة المنتجات، المخزون، الطلبات
- رفع صور متعددة للمنتج
- إدارة المخزون مع تنبيه عند النفاد
- إحصائيات مبيعات مع Chart.js
- إدارة الشحن (تحديد مناطق التوصيل والأسعار)
- سحب الأرباح (بعد خصم الكومسيون)

### 🔧 Admin
- موافقة على البائعين الجدد
- إدارة الفئات والعلامات التجارية
- ضبط الكومسيون لكل بائع
- إدارة الكوبونات
- تقارير مالية شاملة

## 🗄️ قاعدة البيانات

```sql
users (id, name, email, password, role, phone, address, created_at)
shops (id, vendor_id, name, description, logo, banner, status, commission_rate)
products (id, shop_id, category_id, name, description, price, sale_price, stock, sku)
product_images (id, product_id, image_path, is_primary)
product_variants (id, product_id, size, color, price, stock)
orders (id, customer_id, total_amount, discount, shipping_cost, status, address, created_at)
order_items (id, order_id, product_id, shop_id, quantity, price, status)
payments (id, order_id, amount, stripe_id, status, paid_at)
vendor_payouts (id, shop_id, amount, commission_deducted, status, processed_at)
reviews (id, user_id, product_id, rating, comment, images JSON, created_at)
coupons (id, code, type, value, min_order, uses_limit, uses_count, expires_at)
wishlist (id, user_id, product_id, added_at)
shipping_zones (id, shop_id, zone_name, price, estimated_days)
categories (id, parent_id, name, slug, icon)
```

## 🛠️ Tech Stack
- **Backend**: PHP 8+ MVC
- **Database**: MySQL
- **Frontend**: HTML + CSS + JS + Chart.js
- **Payments**: Stripe Connect (split payments)
- **Images**: Local storage مع resize تلقائي (GD Library)
- **Extra**: Composer packages

## ⏱️ تقدير الوقت
| Phase | المدة |
|---|---|
| Auth + Shop setup | 1 أسبوع |
| Product + Category CRUD | 1.5 أسبوع |
| Cart + Order system | 2 أسبوع |
| Payments + Payouts | 1.5 أسبوع |
| Vendor dashboard + Stats | 1 أسبوع |
| Admin + Coupons + Deploy | 1.5 أسبوع |
| **المجموع** | **~8.5 أسابيع** |

## 🔥 ليش هو impressive فالـ CV؟
Multi-vendor + split payments + inventory management = من أعقد أنواع الـ e-commerce. نادر من يبنيه من scratch.

---

---

# 🍕 5. Tabel — منصة طلب أكل محلية

## 💡 الفكرة
منصة طلب أكل من مطاعم محلية — زبون يطلب، مطعم يستقبل ويجهز، ساعي يوصل. ثلاثة أدوار + real-time tracking.

## ✨ Features

### 👤 للزبون
- تصفح مطاعم قريبة (GPS أو اختيار المدينة)
- قائمة الطعام مع صور وتفاصيل
- سلة + تخصيص الطلب (بدون بصل، إضافة جبن...)
- دفع أونلاين (Stripe) أو عند الاستلام
- تتبع الطلب real-time (بدون WebSocket — polling كل 10 ثواني)
- تقييم المطعم والساعي بعد التوصيل
- طلبات سابقة + إعادة الطلب بنقرة

### 🍽️ للمطعم
- Dashboard يستقبل الطلبات الجديدة (auto-refresh)
- تغيير حالة الطلب: مقبول ← يُحضَّر ← جاهز ← مع الساعي
- إدارة القائمة (منتجات، أسعار، إتاحة)
- إدارة ساعات العمل وحالة المطعم (مفتوح/مغلق)
- إحصائيات: أكثر الأطباق طلبًا، أوقات الذروة

### 🛵 للساعي
- لوحة بسيطة: الطلبات الجاهزة للتوصيل في منطقته
- قبول طلب + تحديث الحالة (غادي ← وصلت ← تم التوصيل)
- سجل التوصيلات + الأرباح اليومية

### 🔧 Admin
- موافقة على المطاعم والسعاة
- إدارة المناطق والمدن
- تقارير + كومسيون

## 🗄️ قاعدة البيانات

```sql
users (id, name, email, password, role, phone, avatar, created_at)
restaurants (id, owner_id, name, description, logo, address, city, lat, lng, phone, category, status, is_open)
menu_categories (id, restaurant_id, name, position)
menu_items (id, restaurant_id, category_id, name, description, price, image, is_available)
item_options (id, item_id, name, choices JSON, is_required) -- مثل: الحجم، الإضافات
orders (id, customer_id, restaurant_id, driver_id, total, delivery_fee, status, address, notes, created_at)
order_items (id, order_id, item_id, quantity, price, customizations JSON)
payments (id, order_id, amount, method, stripe_id, status, paid_at)
drivers (id, user_id, vehicle_type, zone, is_available, total_earnings)
reviews (id, order_id, customer_id, restaurant_rating, driver_rating, comment, created_at)
zones (id, name, city, delivery_fee, estimated_minutes)
```

## 🛠️ Tech Stack
- **Backend**: PHP 8+ MVC
- **Database**: MySQL
- **Frontend**: HTML + CSS + JS (AJAX polling للـ real-time)
- **Maps**: Leaflet.js أو Google Maps
- **Payments**: Stripe
- **Extra**: PHPMailer + SMS API (optional)

## ⏱️ تقدير الوقت
| Phase | المدة |
|---|---|
| Auth (3 roles) + Restaurant setup | 1.5 أسبوع |
| Menu + Cart system | 1.5 أسبوع |
| Order flow + Status tracking | 2 أسبوع |
| Driver module | 1 أسبوع |
| Payments + Reviews | 1 أسبوع |
| Admin + Deploy | 1 أسبوع |
| **المجموع** | **~8 أسابيع** |

## 🔥 ليش هو impressive فالـ CV؟
Food delivery = معقدة logistically (3 roles + real-time + geo). شركات بحال Glovo بنات هادشي بالملايين.

---

---

# 🏆 مقارنة سريعة

| المشروع | الصعوبة | الـ CV Impact | الوقت |
|---|---|---|---|
| 🏥 ClinicFlow | ⭐⭐⭐⭐ | 🔥🔥🔥🔥🔥 | ~8 أسابيع |
| 🏠 DarMa | ⭐⭐⭐⭐⭐ | 🔥🔥🔥🔥🔥 | ~8.5 أسابيع |
| 🎓 MadrasaTech | ⭐⭐⭐ | 🔥🔥🔥🔥 | ~7.5 أسابيع |
| 🛒 SouqPro | ⭐⭐⭐⭐⭐ | 🔥🔥🔥🔥🔥 | ~8.5 أسابيع |
| 🍕 Tabel | ⭐⭐⭐⭐ | 🔥🔥🔥🔥🔥 | ~8 أسابيع |

---

## 💡 توصيتي الشخصية

اختار واحد من هادوك الثلاثة:

- **DarMa** إذا بغيتي شي بيبان فيه design جميل + complexity عالية
- **ClinicFlow** إذا بغيتي تدخل مجال الـ HealthTech (مطلوب بزاف)
- **Tabel** إذا بغيتي multi-role + real-time — الأكثر exciting للبناء

---

*كل مشروع من هاد 5 يقدر يكون portfolio piece قوي. الفرق بين مشروع عادي ومشروع impressive هو التفاصيل: error handling، security (CSRF, SQL injection protection)، responsive design، وREADME مزيانة كديك لي خدمنا ليك فـ Taghazout 😄*
