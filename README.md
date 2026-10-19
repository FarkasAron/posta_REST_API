# Megyék és városok REST API

Laravel 13 REST API a korábbi irányítószámos (megyék / városok) feladathoz. Ugyanazt az adatmodellt és funkciókat tudja, mint a `megyek_laravel` webes alkalmazás, de Blade nézetek helyett JSON-t ad vissza. Az adatmódosításhoz **Laravel Sanctum tokenes hitelesítés** kell.

## Adatmodell

- **County** (`counties`): `id`, `name`, `badge` (a megye címerének útvonala vagy URL-je, opcionális)
- **City** (`cities`): `id`, `id_county` (foreign key), `name`, `zip_code`, `population`

`County` 1:N `City`. A megye JSON-jában a `badge` mellett megjelenik a `badge_url` (teljes kép-URL), a megyelistában pedig a `population` (a megye városainak összlakossága).

## Végpontok

Az alap URL: `http://localhost:8000/api`. A 🔒 jelű végpontokhoz kell a fejléc: `Authorization: Bearer {token}`.

| Metódus | Végpont | Leírás | Auth |
|---|---|---|---|
| POST | `/login` | Bejelentkezés, token kérése (`email`, `password`) | – |
| POST | `/logout` | Az aktuális token törlése | 🔒 |
| GET | `/cities` | Városok lapozva. Query: `search` (névrészlet), `county` (megye id), `page`, `per_page` (1–100, alap: 25) | – |
| GET | `/cities/{id}` | Egy város a megyéjével | – |
| POST | `/cities` | Új város (`name`, `id_county`, `zip_code`, `population`) | 🔒 |
| PUT / PATCH | `/cities/{id}` | Város módosítása (PUT: minden mező kötelező, PATCH: csak a változók) | 🔒 |
| DELETE | `/cities/{id}` | Város törlése | 🔒 |
| GET | `/counties` | Összes megye összlakossággal. Query: `search` (névrészlet) | – |
| GET | `/counties/{id}` | Egy megye | – |
| POST | `/counties` | Új megye (`name`, opcionális `badge`) | 🔒 |
| PUT / PATCH | `/counties/{id}` | Megye módosítása | 🔒 |
| DELETE | `/counties/{id}` | Megye törlése (**409**, ha tartoznak hozzá városok) | 🔒 |

A keresés és a megyeszűrés együtt is használható, pl. `GET /api/cities?search=Aba&county=7`.

Hibakódok: `401` hiányzó/érvénytelen token vagy rossz belépési adat, `404` ismeretlen erőforrás, `409` megye törlése városokkal, `422` validációs hiba (mezőnkénti magyar üzenetekkel).

## Telepítés

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env: DB_DATABASE=megyek_rest_api (előbb hozd létre az üres MySQL adatbázist)
php artisan migrate --seed
php artisan serve
```

A seeder a valódi magyar megye- és településlistát tölti be (`database/seeders/data/*.json`), a `population` viszont **demo adat** (véletlenszerű, nem KSH statisztika). Létrehoz egy teszt felhasználót is:

- E-mail: `test@example.com`
- Jelszó: `password`

## Tesztelés

**Bruno:** a Brunóban `Open Collection`, majd a `Bruno/megyek-rest-api` mappa kiválasztása (a szervernek és az adatbázisnak futnia kell, `php artisan serve`). A kérések számozottak és sorrendben egymásra épülnek: a `01-login` automatikusan eltárolja a tokent, a `12-city-create` és a `16-county-create` az új azonosítót, ezért semmit nem kell kézzel bemásolni. Minden kérés ellenőrzi a várt státuszkódot (köztük a 401, 409 és 422 hibaeseteket is), így a kollekció a **Run** gombbal egyben lefuttatható. Parancssorból: `cd Bruno/megyek-rest-api && npx @usebruno/cli run`.

**Automata tesztek** (PHPUnit, 20 teszt): `php artisan test`

## APIDOC dokumentáció

A dokumentáció az [apiDoc](https://apidocjs.com) segítségével készül, a controllerek `@api` kommentjeiből. A kész kimenet a `public/docs` mappában van (benne van a repóban), így a szerver indítása után itt érhető el:

**http://localhost:8000/docs/index.html**

Újragenerálás kommentmódosítás után:

```bash
npm install
npm run docs
```

A dokumentáció elkészítéséhez MI (Claude) segítséget használtam.
