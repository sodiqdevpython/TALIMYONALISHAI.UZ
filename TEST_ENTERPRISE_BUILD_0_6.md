# EduDirectionAI Enterprise Build 0.6 Test Plan

## Test 1 — Student accounts generation
Direktor -> Student loginlar -> Student loginlarni yaratish.
Kutiladi: student rolidagi users yozuvlari yaratiladi.

## Test 2 — Student login
Jadvaldagi student login/parol bilan kiring.
Kutiladi: student/index.php ochiladi.

## Test 3 — Access isolation
Student boshqa student profilinga kira olmasligi kerak.
Student faqat o‘z username bilan bog‘langan `students` yozuvini ko‘radi.

## Test 4 — Student Passport
Student portalda PDF/Print ochilsin.
Kutiladi: browser print orqali PDF saqlash mumkin.

## Test 5 — Backward compatibility
Direktor, Teacher, Prediction va Student Intelligence Center avvalgidek ishlasin.
