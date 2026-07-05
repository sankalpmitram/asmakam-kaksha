# अस्माकं कक्षा — कक्षा प्रबंधन प्रणाली

एक हल्का, मोबाइल-फर्स्ट **Progressive Web App (PWA)**, जो संस्कृत माध्यम विद्यालय के कक्षा शिक्षकों हेतु
कक्षा, छात्र एवं उपस्थिति प्रबंधन के लिए बनाया गया है। पूरा इंटरफ़ेस सरल संस्कृत शब्दावली सहित हिन्दी में है।

No database. No framework. Pure **PHP 8 + HTML5 + CSS3 + Vanilla JS**, with all data stored in JSON files.

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
- Backup (ZIP of all JSON + uploads) and Restore
- Installable PWA with offline app-shell caching (service worker)
- Mobile-first, saffron/white/green Material-style UI with bottom navigation

---

## 📁 Folder Structure

```
asmakam-kaksha/
├── index.php                 # Front controller / router
├── print_report.php          # Print-friendly report view (used for PDF export)
├── manifest.json             # PWA manifest
├── service-worker.js         # Offline app-shell caching
├── Dockerfile                # PHP 8 + Apache image (Render/Docker ready)
├── docker-entrypoint.sh       # Binds Apache to Render's runtime $PORT
├── render.yaml                # Render.com blueprint
├── .htaccess                  # Apache hardening (blocks direct .json access)
├── includes/
│   ├── functions.php          # JSON read/write with file locking, sanitizers, helpers
│   ├── auth.php                # Session auth (login/logout/guards)
│   ├── header.php / footer.php # Shared layout (app bar + bottom nav)
├── pages/                     # Page templates (server-rendered HTML shells)
│   ├── login.php, dashboard.php, classes.php, students.php,
│   │   attendance.php, attendance_history.php, reports.php, settings.php
├── api/                       # JSON API endpoints consumed by assets/js/*.js
│   ├── login.php, logout.php, classes.php, students.php, student_photo.php,
│   │   attendance.php, settings.php, dashboard.php, reports.php,
│   │   export_excel.php, backup.php, restore.php, reset.php
├── assets/
│   ├── css/style.css          # Full design system (light + dark theme)
│   ├── js/                    # One JS file per page + shared app.js
│   ├── images/                # Placeholder avatar / logo SVGs
│   └── icons/                 # PWA icons (192px, 512px)
├── data/                      # JSON "database" — auto-created on first request
│   ├── classes.json, students.json, attendance.json, settings.json, users.json
└── uploads/                   # Student photos & school logo uploads
```

---

## 🚀 Running Locally

**Requirements:** PHP 8+, with the `zip` and `fileinfo` extensions enabled (both are bundled by default in
most PHP installs; `fileinfo` ships enabled, `zip` may need `sudo apt install php-zip` on Linux).

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

The `data/*.json` files are created automatically on the very first request — you do not need to create
them by hand. Uploaded photos and the school logo are stored in `uploads/`.

---

## 🐳 Deploying to Render.com

This repo includes a `Dockerfile`, `docker-entrypoint.sh`, and `render.yaml`, so Render's **Docker**
environment can build and run it directly:

1. Push this repository to GitHub.
2. In Render, choose **New → Blueprint** and point it at the repo (it will read `render.yaml`), or choose
   **New → Web Service → Docker** manually.
3. Deploy. Render provides the `PORT` environment variable at runtime; `docker-entrypoint.sh` reconfigures
   Apache to listen on it automatically — no manual port setup needed.

**Persistence note:** Render's free web service filesystem is ephemeral (it resets on redeploy/restart).
For real classroom use on the free plan, use **Settings → डेटा बैकअप** regularly to download a ZIP backup,
and **Settings → पुनर्स्थापित करें** to restore it after a redeploy. For always-on persistence, attach a
paid Render Persistent Disk to the container's `/data` and `/uploads` paths.

---

## 🔐 Security Notes

- Session-based auth with `httponly`, `SameSite=Lax` cookies, and session ID regeneration on login.
- All JSON reads/writes use `flock()` (shared for reads, exclusive for writes) to avoid corruption from
  concurrent requests.
- All text input is sanitized (`trim` + `strip_tags`) before being stored.
- Phone/WhatsApp numbers are validated against a 10-digit Indian mobile pattern.
- Uploaded images are validated by real MIME type (via `finfo`), not just file extension, and capped at 2MB.
- `.htaccess` blocks direct browser access to any `.json` file and disables PHP execution inside
  `data/` and `uploads/`.
- Duplicate roll numbers within the same class, and duplicate attendance for the same class + date, are
  both rejected server-side.

---

## 📊 Export Formats

- **Excel** exports are UTF-8 CSV files (with BOM, so Devanagari text renders correctly) — they open
  directly in Excel, Google Sheets, or LibreOffice. No external library dependency.
- **PDF** export opens a dedicated print-friendly page (`print_report.php`); use the browser's
  "Print → Save as PDF" to generate a PDF. This avoids requiring a heavyweight PDF-generation library
  for a no-framework PHP app.

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
