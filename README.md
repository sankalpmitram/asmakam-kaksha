# अस्माकं कक्षा — कक्षा प्रबंधन प्रणाली

एक हल्का, मोबाइल-फर्स्ट **Progressive Web App (PWA)**, जो संस्कृत माध्यम विद्यालय के कक्षा शिक्षकों हेतु
कक्षा, छात्र एवं उपस्थिति प्रबंधन के लिए बनाया गया है। पूरा इंटरफ़ेस सरल संस्कृत शब्दावली सहित हिन्दी में है।

No framework. **PHP 8 + HTML5 + CSS3 + Vanilla JS** on the server/frontend. App data lives in
**Google Firestore**, and student photos / the school logo live in **Firebase Storage** — both are real
persistent cloud services, so nothing is lost on host restarts, redeploys, or free-tier sleep/wake cycles.

---

## ✨ Features

- Single-teacher session login (username/password, hashed with `password_hash`)
- Dashboard with live stats: total classes, total students, today's present/absent, attendance %
- Class management: add / edit / delete / search, unlimited classes
- Student management: add / edit / delete / search / move between classes, optional photo
- Attendance: present / absent / late / half-day, "mark all present", duplicate-date protection
- Attendance history: monthly calendar view, edit or delete any past day's record
- Reports: today's / monthly / class / student, with **PDF** (print-friendly view) and **Excel** (CSV) export
- WhatsApp: auto-generated, editable message template; opens `wa.me` links per absent student — no SMS/3rd-party API
- Settings: school info & logo, WhatsApp template, light/dark theme, password change
- Backup (ZIP of all data + uploads) and Restore
- Installable PWA with offline app-shell caching (service worker)
- Mobile-first, saffron/white/green Material-style UI with bottom navigation

---

## 📁 Folder Structure

```
asmakam-kaksha/
├── index.php                    # Front controller / router
├── print_report.php             # Print-friendly report view (used for PDF export)
├── manifest.json                # PWA manifest
├── service-worker.js            # Offline app-shell caching
├── Dockerfile                   # PHP 8 + Apache image (Render/Docker ready)
├── docker-entrypoint.sh          # Binds Apache to Render's runtime $PORT
├── render.yaml                   # Render.com blueprint
├── .htaccess                     # Apache hardening (blocks direct .json access)
├── firebase-service-account.json # (you create this locally — gitignored, see setup below)
├── includes/
│   ├── firestore.php              # Firestore REST API client (auth + CRUD for app data)
│   ├── storage.php                 # Firebase Storage REST client (photo/logo uploads)
│   ├── functions.php               # read_json()/write_json()/next_id() — Firestore-backed
│   ├── auth.php                     # Session auth (login/logout/guards)
│   ├── header.php / footer.php      # Shared layout (app bar + bottom nav)
├── pages/                        # Page templates (server-rendered HTML shells)
│   ├── login.php, dashboard.php, classes.php, students.php,
│   │   attendance.php, attendance_history.php, reports.php, settings.php
├── api/                          # JSON API endpoints consumed by assets/js/*.js
│   ├── login.php, logout.php, classes.php, students.php, student_photo.php,
│   │   attendance.php, settings.php, dashboard.php, reports.php,
│   │   export_excel.php, backup.php, restore.php, reset.php
├── assets/
│   ├── css/style.css             # Full design system (light + dark theme)
│   ├── js/                       # One JS file per page + shared app.js
│   ├── images/                   # Placeholder avatar / logo SVGs (shown when no photo set)
│   └── icons/                    # PWA icons (192px, 512px)
```

---

## 🔥 Firebase / Firestore Setup (do this first)

The app needs a Firebase project with **Firestore** enabled, and a **service account key** so the
PHP backend can talk to it.

1. Go to **https://console.firebase.google.com** → **Add project** (free "Spark" plan is enough).
2. In your new project, open **Build → Firestore Database** → **Create database** → choose
   **Native mode** → pick any region → **Enable**.
3. Open **Build → Storage** → **Get started** → accept the default rules → **Done**. This creates your
   default storage bucket (used for student photos and the school logo).
4. Go to **Project settings (⚙️) → Service accounts** → **Generate new private key**. This downloads
   a JSON file (e.g. `yourproject-firebase-adminsdk-xxxxx.json`).
5. Rename that downloaded file to **`firebase-service-account.json`** and place it in the **project
   root** (same folder as `index.php`). It's already in `.gitignore` — never commit it to GitHub.

That's it for local development — `includes/firestore.php` and `includes/storage.php` read this file
automatically.

### If photo/logo uploads fail with a permissions error

Uploaded files are made public via a per-object ACL (`predefinedAcl=publicRead`) so the app can display
them with a plain `<img src="...">` — no signed URLs needed. Some newer Firebase Storage buckets have
**"Uniform bucket-level access"** enabled, which disables per-object ACLs and causes uploads to fail.
To fix it, in the **Google Cloud Console** → **Cloud Storage → Buckets** → your bucket → **Permissions**:
- Either turn **Uniform bucket-level access → Off** (re-enables per-object ACLs, simplest fix), **or**
- Keep it on, and instead grant the **`allUsers`** principal the **Storage Object Viewer** role at the
  bucket level (makes all objects in the bucket publicly readable, works with uniform access).

### For Render (or any host where you can't upload a file securely)

Instead of the file, set these as **environment variables** in the Render dashboard
(Settings → Environment), using values from the same downloaded JSON key:

| Env var                    | Value comes from the JSON key's...                          |
|-----------------------------|--------------------------------------------------------------|
| `FIREBASE_PROJECT_ID`       | `project_id`                                                  |
| `FIREBASE_CLIENT_EMAIL`     | `client_email`                                                |
| `FIREBASE_PRIVATE_KEY`      | `private_key` (paste it as-is, including `-----BEGIN...` lines) |
| `FIREBASE_STORAGE_BUCKET`   | *(optional)* only set this if your bucket name isn't `{project_id}.appspot.com` — check under Storage → Files in the Firebase console for the exact bucket name shown at the top |

`render.yaml` already declares these three env vars (as `sync: false`, meaning you fill in the real
values in the Render dashboard, not in the file). If both the env vars **and** a local
`firebase-service-account.json` are present, the env vars win.

---

## 🚀 Running Locally

**Requirements:** PHP 8+ with the `curl`, `openssl`, `zip`, and `fileinfo` extensions enabled.
`openssl` and `fileinfo` are enabled by default in almost every PHP install; `curl` and `zip` may need:
```bash
sudo apt install php-curl php-zip   # Debian/Ubuntu, if not already enabled
```

```bash
cd asmakam-kaksha
php -S localhost:8000
```

Open **http://localhost:8000** in your browser.

### Default login
```
Username: teacher
Password: teacher@123
```
⚠️ Change this immediately from **Settings → कूटशब्द बदलें** after first login.

The first request automatically seeds Firestore with a default teacher user and default settings — you
don't need to create anything by hand in the Firebase console.

---

## 🐳 Deploying to Render.com

This repo includes a `Dockerfile`, `docker-entrypoint.sh`, and `render.yaml`, so Render's **Docker**
environment can build and run it directly:

1. Complete the **Firebase setup** above and note your three credential values.
2. Push this repository to GitHub (the real `firebase-service-account.json` should NOT be in the repo).
3. In Render, choose **New → Blueprint** and point it at the repo (it will read `render.yaml`), or choose
   **New → Web Service → Docker** manually.
4. In the Render dashboard → your service → **Environment**, add `FIREBASE_PROJECT_ID`,
   `FIREBASE_CLIENT_EMAIL`, and `FIREBASE_PRIVATE_KEY` with your real values.
5. Deploy. Render provides the `PORT` environment variable at runtime; `docker-entrypoint.sh`
   reconfigures Apache to listen on it automatically — no manual port setup needed.

**Persistence:** All app data (classes, students, attendance, settings, users) lives in Firestore, and
student photos / school logo live in Firebase Storage — both survive Render restarts, redeploys, and
free-tier sleep/wake cycles automatically. No paid disk needed anywhere in this app anymore.

---

## 🔐 Security Notes

- Session-based auth with `httponly`, `SameSite=Lax` cookies, and session ID regeneration on login.
- Firestore and Firebase Storage are accessed via a service-account OAuth2 token (server-side only) —
  the app never exposes Firebase credentials to the browser.
- All text input is sanitized (`trim` + `strip_tags`) before being stored.
- Phone/WhatsApp numbers are validated against a 10-digit Indian mobile pattern.
- Uploaded images are validated by real MIME type (via `finfo`), not just file extension, and capped at 2MB.
- `.htaccess` blocks direct browser access to any `.json` file, including `firebase-service-account.json`
  if it's ever placed at the project root.
- Duplicate roll numbers within the same class, and duplicate attendance for the same class + date, are
  both rejected server-side.

---

## 📊 Export Formats

- **Excel** exports are UTF-8 CSV files (with BOM, so Devanagari text renders correctly) — they open
  directly in Excel, Google Sheets, or LibreOffice. No external library dependency.
- **PDF** export opens a dedicated print-friendly page (`print_report.php`); use the browser's
  "Print → Save as PDF" to generate a PDF. This avoids requiring a heavyweight PDF-generation library.

---

## 🖌️ Theme

| Role       | Color                |
|------------|-----------------------|
| Primary    | Saffron `#FF9933`     |
| Secondary  | White `#ffffff`       |
| Accent     | Green `#138808`       |

Light and dark modes are both supported and toggle from the app bar or Settings page.

---

## 📄 License

Built for internal school use. Adapt freely for your own institution.
