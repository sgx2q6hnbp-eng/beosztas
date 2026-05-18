# Munkabeosztás Rendszer – Telepítési útmutató (Hostinger)

## Követelmények
- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.4+
- Composer
- Apache (mod_rewrite engedélyezve)

---

## 1. Fájlok feltöltése
1. Töltsd fel a projekt összes mappáját a Hostinger File Manager-rel vagy FTP-n.
2. A `public/` mappa tartalmát másold a **public_html/** mappába.
3. A többi mappát (app, config, migrations, vendor) töltsd fel **a public_html FÖLÉ**,
   tehát azonos szintre, ne bele!

```
/home/user/
├── public_html/          ← ide kerül a public/ mappa tartalma
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── app/
├── config/
├── migrations/
└── vendor/
```

---

## 2. Adatbázis létrehozása (hPanel)
1. Hostinger hPanel → Adatbázisok → MySQL adatbázisok
2. Hozz létre egy új adatbázist és felhasználót.
3. Másold az adatbázis nevét, felhasználóját és jelszavát.

---

## 3. .env fájl kitöltése
Szerkeszd a `config/.env` fájlt:
```
DB_HOST=localhost
DB_NAME=u123456789_beosztás
DB_USER=u123456789_admin
DB_PASS=erős_jelszó_ide
APP_URL=https://yourdomain.com
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=email_jelszó
```

---

## 4. Migrációk futtatása
A Hostinger hPanel-ben: Adatbázisok → phpMyAdmin → SQL fül
Futtasd sorban a migrations/ mappában lévő .sql fájlokat:
```
001_create_users.sql
002_create_fleets.sql
003_create_shifts.sql
004_create_leave_requests.sql
005_create_swap_requests.sql
006_create_shift_logs.sql
```

---

## 5. Composer telepítés (Hostinger SSH)
```bash
cd /home/user/
composer install --no-dev --optimize-autoloader
```

---

## 6. Admin jelszó beállítása
Az `001_create_users.sql` egy placeholder hash-t szúr be.
Az első belépés után az admin a profil oldalon tud jelszót változtatni,
vagy futtasd ezt a PHP scriptet egyszer:

```php
echo password_hash('UjJelszo123!', PASSWORD_BCRYPT, ['cost' => 12]);
// Az eredményt másold be a users táblában az admin sorának password mezőjébe
```

---

## 7. PHP verzió beállítása Hostingeren
hPanel → Weboldalak → PHP konfiguráció → PHP 8.2

---

## Biztonsági ellenőrzőlista
- [ ] .env fájl NEM érhető el webről (.htaccess védi)
- [ ] APP_DEBUG=false éles környezetben
- [ ] HTTPS beállítva (Hostinger ingyenes SSL)
- [ ] Admin jelszó megváltoztatva
- [ ] migrations/ mappa nem érhető el webről


---

## 2. Fázis – Excel Import (Telepítési jegyzetek)

### Composer csomagok telepítése (SSH)
```bash
composer require phpoffice/phpspreadsheet
```

### uploads/ mappa jogosultság (SSH)
```bash
chmod 755 uploads/
chmod 755 uploads/excel/
```

### Router kiegészítés (public/index.php $routes tömbhöz)
```php
'GET'  => [
    '/admin/import'          => fn() => (new ImportController())->showForm(),
    '/admin/import/template' => fn() => (new ImportController())->downloadTemplate(),
],
'POST' => [
    '/admin/import' => fn() => (new ImportController())->handle(),
],
```

### Import logika összefoglalója
- Csak .xlsx fájl fogadható el (max. 5 MB)
- Az 1–4. sor kihagyva (cím, info, üres, fejléc)
- Flotta: "I." → fleet_id=1, "II." → fleet_id=2
- Dátum: Excel számformátum ÉS ÉÉÉÉ.HH.NN szöveg is támogatott
- Névegyezés: pontos match, majd LIKE fallback
- Felülírás: csak 'active' státuszú sorokat ír felül
- Táppénz/szabadság/hiányzás státuszú sorok MEGMARADNAK
- Import után a fájl automatikusan törlődik a szerverről
