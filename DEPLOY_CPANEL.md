# دليل رفع المشروع على cPanel
# Aura Derma Clinic - cPanel Deployment Guide

---

## المتطلبات | Requirements
- PHP >= 8.2 (يفضل 8.3 أو 8.4)
- MySQL 5.7+ أو MariaDB 10.3+
- Composer 2.x
- Node.js 18+ (للبناء المحلي فقط - تم بالفعل)
- PHP Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, PDO_MySQL, Tokenizer, XML, GD/Imagick

---

## الخطوات | Steps

### الخطوة 1: رفع الملفات | Upload Files

1. ارفع ملف `aura-derma-website.zip` إلى مجلد `public_html` في cPanel عبر File Manager
2. فك الضغط عن الملف في `public_html`
3. يجب أن يكون هيكل الملفات كالتالي:

```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── build/
│   ├── storage/ (symlink)
│   ├── .htaccess
│   └── index.php
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── .htaccess  ← (انسخ من .htaccess.cpanel-root)
├── artisan
└── composer.json
```

### الخطوة 2: إعداد .htaccess الجذر | Root .htaccess

**مهم جداً!** انسخ محتوى `.htaccess.cpanel-root` إلى `.htaccess` في مجلد `public_html` الرئيسي:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,QSA]
</IfModule>
```

### الخطوة 3: إعداد قاعدة البيانات | Database Setup

1. اذهب إلى **MySQL Databases** في cPanel
2. أنشئ قاعدة بيانات جديدة (مثل: `auraderma_db`)
3. أنشئ مستخدم جديد (مثل: `auraderma_user`)
4. اربط المستخدم بقاعدة البيانات مع **ALL PRIVILEGES**
5. اذهب إلى **phpMyAdmin** واستورد ملف `database/aura_database_backup.sql`

### الخطوة 4: إعداد ملف .env | Environment Setup

1. انسخ `.env.production` إلى `.env`
2. عدّل القيم التالية:

```env
APP_URL=https://yourdomain.com        ← ضع الدومين الخاص بك

DB_DATABASE=auraderma_db               ← اسم قاعدة البيانات
DB_USERNAME=auraderma_user             ← اسم المستخدم
DB_PASSWORD=your_secure_password       ← كلمة المرور

MAIL_HOST=mail.yourdomain.com          ← سيرفر البريد
MAIL_USERNAME=info@yourdomain.com      ← البريد
MAIL_PASSWORD=email_password           ← كلمة مرور البريد
```

### الخطوة 5: أوامر الإعداد | Setup Commands

اذهب إلى **Terminal** في cPanel أو اتصل عبر SSH ونفذ:

```bash
cd ~/public_html

# تثبيت المكتبات (إذا لم يتم رفع vendor)
composer install --optimize-autoloader --no-dev

# إنشاء رابط التخزين
php artisan storage:link

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache

# صلاحيات المجلدات
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### الخطوة 6: ضبط الصلاحيات | Permissions

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod -R 775 public/storage/
```

### الخطوة 7: Cron Job (اختياري) | Cron Job (Optional)

في cPanel → Cron Jobs، أضف:

```
* * * * * cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## طريقة بديلة (Document Root) | Alternative Method

إذا كان لديك وصول لتغيير Document Root:

1. في cPanel → Domains → اضغط على الدومين
2. غيّر Document Root إلى: `public_html/public`
3. في هذه الحالة **لا تحتاج** ملف `.htaccess` في الجذر

---

## بيانات الدخول | Login Credentials

### الأدمن | Admin
- URL: `https://yourdomain.com/admin/login`
- Email: تحقق من جدول users

### الأطباء | Doctors
- URL: `https://yourdomain.com/doctor/login`

| Doctor | Email | Password |
|--------|-------|----------|
| د. آلاء العوضي | dr.alaa@auraderma.com | xK#9mVp2!dLq |
| د. إيمان مجدي | dr.eman@auraderma.com | Rw7$nBz4@hYs |
| د. أميرة أحمد | dr.amira@auraderma.com | Jf3!tGc8#mXe |
| د. أسماء حمدي | dr.asmaa@auraderma.com | Qv5@kHn1!wPr |

### السكرتارية | Secretary
- URL: `https://yourdomain.com/secretary/login`

---

## حل المشاكل | Troubleshooting

### صفحة بيضاء أو خطأ 500
```bash
php artisan config:clear
php artisan cache:clear
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### الصور لا تظهر
```bash
php artisan storage:link
# أو يدوياً
ln -s ~/public_html/storage/app/public ~/public_html/public/storage
```

### خطأ في قاعدة البيانات
- تأكد من صحة بيانات `.env`
- تأكد من استيراد `aura_database_backup.sql`
