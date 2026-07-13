# EduDirectionAI Professional v5.0 — Authentication Module

## Joylashtirish
1. Papkani `C:\xampp\htdocs\EduDirectionAI_Professional_v4` ichiga oching yoki mavjud fayllar ustiga nusxa qiling.
2. phpMyAdmin’da `edudirectionai_db` bazasi import qilingan bo‘lishi kerak.
3. Brauzerda oching:
   `http://localhost/EduDirectionAI_Professional_v4/web/login.php`

## Demo loginlar
- Super Admin: `admin` / `admin123`
- Direktor: `director_demo` / `director123`
- Zavuch: `vice_demo` / `vice123`
- O‘qituvchi: `teacher_demo` / `teacher123`

Birinchi kirishda demo parollar avtomatik `password_hash()` bilan yangilanadi.

## Yangi fayllar
- `config/database.php`
- `config/session.php`
- `config/auth.php`
- `web/login.php`
- `web/logout.php`
- `web/dashboard.php`
- `web/admin/index.php`
- `web/director/index.php`
- `web/vice/index.php`
- `web/teacher/index.php`
- `web/student/index.php`
- `web/admin/schools.php`
