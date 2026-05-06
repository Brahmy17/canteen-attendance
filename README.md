# 🍽 Canteen Meal Attendance System

A QR-code-based meal attendance tracker for company canteens. Logs employee meals in real-time to Google Sheets, with a secured auditor dashboard, CSV export, and duplicate scan prevention.

---

## ✨ Features

| Feature | Details |
|---|---|
| 🔳 QR Scanner | Scans employee QR codes via device camera |
| 🚫 Duplicate Prevention | Blocks the same employee logging twice per meal per day |
| 📊 Auditor Dashboard | View live stats + full log history (PIN-protected) |
| 📥 CSV Export | Export filtered logs to CSV in one click |
| 📝 Employee Registration | Register employees and generate/download their QR code |
| 🗂 Google Sheets Backend | Logs sheet + Employees sheet in one spreadsheet |
| 🔐 Token Auth | Shared secret token protects the Apps Script endpoint |
| ✅ Input Validation | All data sanitized in both PHP and Apps Script |

---

## 📁 File Structure

```
canteen/
├── index.html          # Frontend (scanner, dashboard, registration)
├── api.php             # PHP middleware — validates & proxies to GAS
├── config.php          # 🔒 SECRET config — DO NOT COMMIT (gitignored)
├── appscript.js        # Paste this into Google Apps Script editor
├── .gitignore          # Ignores config.php
└── README.md
```

---

## 🚀 Setup Instructions

### Step 1 — Google Sheets

1. Create a new Google Spreadsheet.
2. Rename the first tab to **`Logs`**.
3. The Apps Script will auto-create a second tab called **`Employees`**.

---

### Step 2 — Google Apps Script

1. In your spreadsheet, go to **Extensions → Apps Script**.
2. Delete the default `Code.gs` content.
3. Paste the entire contents of **`appscript.js`** into the editor.
4. Set your **secret token** on line 7:
   ```js
   var TOKEN = "CHANGE_THIS_TO_A_LONG_RANDOM_STRING";
   ```
   Use a strong random string (e.g. 32+ characters).
5. Click **Deploy → New Deployment**:
   - Type: **Web App**
   - Execute as: **Me**
   - Who has access: **Anyone**
6. Copy the **Web App URL** shown after deploying.

---

### Step 3 — PHP Configuration

1. Copy `config.php.example` → `config.php` (or edit `config.php` directly).
2. Fill in your values:
   ```php
   define('GAS_URL',    'https://script.google.com/macros/s/YOUR_ID/exec');
   define('API_TOKEN',  'SAME_SECRET_AS_IN_APPS_SCRIPT');
   define('ADMIN_PIN',  '1234'); // Change this!
   ```
3. **Never commit `config.php`** — it's already in `.gitignore`.

---

### Step 4 — Deploy to Web Host / GitHub Pages

> **Note:** `api.php` requires a PHP web server (e.g. cPanel hosting, Laravel Valet, XAMPP). GitHub Pages serves static files only — upload these files to a PHP-capable host.

Upload all files **except `config.php`** to your web server:
```
index.html
api.php
.gitignore
README.md
```

For GitHub, push normally — `config.php` is gitignored and stays local.

---

## 🗂 Google Sheet Structure

### Sheet: `Logs`
| A: EmpID | B: Meal | C: Date | D: Time | E: Name | F: Dept |
|---|---|---|---|---|---|
| EMP-101 | Lunch | 2025-01-15 | 12:01:00 | Juan Dela Cruz | IT |

### Sheet: `Employees`
| A: EmpID | B: Name | C: Dept | D: Registered At |
|---|---|---|---|
| EMP-101 | Juan Dela Cruz | IT | 2025-01-10 09:00:00 |

---

## 🔐 Security Notes

- `config.php` is gitignored — never commit it.
- All requests from PHP to GAS include a secret `token` field.
- The GAS script rejects any request without the correct token.
- All inputs are sanitized in both PHP (`htmlspecialchars`) and Apps Script.
- The Auditor Dashboard requires a PIN to access.
- `Access-Control-Allow-Origin: *` — restrict this to your domain in production.

---

## 🛠 Customization

- **Add departments**: Edit the `<select id="reg-dept">` options in `index.html`.
- **Change meal hours**: Edit the `selectMeal()` call labels in `index.html`.
- **Change Admin PIN**: Update `ADMIN_PIN` in `config.php` and `ADMIN_PIN` in `appscript.js`.
- **Change token**: Must match in both `config.php` and `appscript.js`.

---

## 📋 Troubleshooting

| Problem | Fix |
|---|---|
| "Upstream request failed" | Check GAS_URL in config.php; redeploy Apps Script |
| "Unauthorized" from GAS | Token mismatch between config.php and appscript.js |
| Duplicate scan not blocked | Ensure Apps Script was redeployed after changes |
| Name/Dept blank in dashboard | Register the employee first via the Registration screen |
| Camera not working | Use HTTPS; grant browser camera permission |
