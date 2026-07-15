# Ubuntu VPS'ga Docker orqali deploy

Bu yo'riqnoma **fastappeal.uz** (GeomapGov) ishlab turgan VPS'ga xalaqit bermay,
ushbu loyihani alohida konteynerlarda ishga tushirish uchun.

## 0. Portlar rejasi

| Servis                        | Port                    | Izoh                          |
|-------------------------------|-------------------------|-------------------------------|
| GeomapGov (mavjud)            | 80, 443, 8000 (ichki)   | Tegilmaydi                    |
| **Bu loyiha (talimyo web)**   | **8081** (host)         | `.env` orqali o'zgartiriladi  |
| Bu loyiha MySQL               | *hostga chiqarilmagan*  | Faqat `talimyo_net` ichida    |

## 1. Kodni VPS'ga ko'chirish

Lokalda (Windows):
```bash
git add . && git commit -m "docker deploy" && git push
```

VPS'da:
```bash
cd /var/www
git clone <repo-url> talimyonalishai
# yoki mavjud bo'lsa: cd /var/www/talimyonalishai && git pull
cd talimyonalishai
```

Agar git yo'q bo'lsa, `scp -r` yoki `rsync` bilan yuborsangiz ham bo'ladi.

## 2. `.env` yaratish (parollarni MAJBURIY o'zgartiring)

```bash
cp .env.example .env
nano .env
```

Kamida `DB_PASS` va `DB_ROOT_PASS`ni kuchli parollarga o'zgartiring.
`WEB_PORT` — 8081 default; agar band bo'lsa (`ss -tlnp | grep 8081`) 8082, 8090 kabi qo'ying.

## 3. Docker o'rnatilganini tekshirish

GeomapGov Docker ishlatgani uchun ehtimol Docker mavjud:
```bash
docker --version && docker compose version
```

Yo'q bo'lsa:
```bash
curl -fsSL https://get.docker.com | sh
```

## 4. Ishga tushirish

```bash
docker compose up -d --build
docker compose logs -f web    # birinchi start'da migratsiya loglari
```

**Muhim:** birinchi safar MySQL init 20–40 soniya oladi (base SQL importlanadi).
Web konteyner MySQL tayyor bo'lgunicha kutadi (healthcheck).

## 5. Tekshirish

VPS ichida:
```bash
curl -I http://127.0.0.1:8081/index.php   # HTTP/1.1 200 yoki 302 login'ga
```

Brauzerda: `http://<VPS_IP>:8081/`

Login sahifasi: `http://<VPS_IP>:8081/login.php`
Default admin: `admin` / `admin123` (birinchi kirishdan keyin **darhol** almashtiring).

## 6. Nginx orqali domenlash (ixtiyoriy)

Agar `talim.example.uz` domenini shu loyihaga bog'lamoqchi bo'lsangiz,
GeomapGov'ning nginx'iga XALAQIT BERMAY yangi server-block qo'shing:

`/etc/nginx/sites-available/talimyo.conf`:
```nginx
server {
    listen 80;
    server_name talim.example.uz;

    client_max_body_size 200M;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/talimyo.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d talim.example.uz    # SSL
```

## 7. Foydali komandalar

```bash
docker compose ps                     # holat
docker compose logs -f web            # web loglar
docker compose logs -f db             # db loglar
docker compose exec web bash          # web ichiga kirish
docker compose exec db mysql -uroot -p$DB_ROOT_PASS edudirectionai_db
docker compose down                   # to'xtatish (ma'lumot qoladi)
docker compose down -v                # to'xtatish + DB volume o'chirish (XAVFLI)
docker compose restart web
```

## 8. Backup

```bash
# DB dump
docker compose exec db mysqldump -uroot -p$DB_ROOT_PASS edudirectionai_db \
    > backup-$(date +%F).sql

# outputs/data papkalari — host'da to'g'ridan-to'g'ri arxivlash:
tar czf talimyo-files-$(date +%F).tar.gz data outputs
```

## 9. Yangilash

```bash
git pull
docker compose up -d --build
```

## GeomapGov'ga xalaqit yo'qligi kafolati

- 80/443 — nginx (host)'da qoldi, tegilmadi
- 8000 — GeomapGov'ning `web:8000` (ichki, faqat GeomapGov docker network'ida)
- 8081 — bu loyiha (host'da yangi port)
- MySQL 3306 — GeomapGov'da SQLite, bu loyiha MySQL faqat o'z docker network'ida
- Docker network'lar alohida: `talimyo_net` va GeomapGov'ning o'z network'i
