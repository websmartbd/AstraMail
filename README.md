# AstraMail 🚀 — Professional Email Marketing Dashboard

AstraMail is a high-performance, self-hosted email marketing platform designed for speed, reliability, and beautiful analytics. Built with PHP 7.4+ and a modern, high-density SaaS interface.

---

## ✨ Key Features

### 🖥️ Modern SaaS Interface
- **Responsive Dashboard**: Mobile-first design that feels like a native app on your phone.
- **High-Density Data**: Optimized layouts to show more information with less scrolling.
- **Glassmorphic Touches**: Subtle blurs, consistent iconography, and a premium aesthetic.

### 🧙 4-Step Campaign Wizard
- **Step 1: Campaign Identity**: Give your campaign a name.
- **Step 2: Smart Composer**: Visual WYSIWYG editor with HTML toggle and toolbar.
- **Step 3: Targeting**: Send to everyone or select specific contacts.
- **Step 4: Scheduling**: Launch instantly or schedule for a specific date/time.

### 🛰️ Real-Time Delivery Dashboard (`logs.php`)
- **Global Delivery Log**: Watch every single email go out across all campaigns in one master feed.
- **Active Monitor**: Live progress bars, sent/failed counters, and real-time status updates.
- **Upcoming Queue**: Visual list of campaigns waiting to be sent.
- **Engine Status**: Live view of the background delivery process (`cron.php`).

### 📊 Advanced Analytics & Tracking
- **Open Tracking**: Automatic injection of tracking pixels to record when emails are read.
- **Click Tracking**: Every link in your email is automatically converted into a tracking link.
- **Link Performance**: Detailed breakdown in reports showing exactly which links were most popular.
- **Engagement Rates**: Automated calculation of Open and Click percentages.

### ⚙️ Smart Delivery Engine
- **Hourly Limits**: Protect your SMTP reputation by setting safe hourly sending limits.
- **Persistence**: "Zero-Config" tracking—the system automatically senses your website URL.
- **Flat-File Storage**: Lightning-fast performance using JSON storage (no database required).

---

## 🛠️ Installation & Setup

1. **Upload**: Upload all files to your PHP 7.4+ server.
2. **Permissions**: Ensure the `/storage/` directory and its subfolders are writable (755 or 777).
3. **Configure**: Log in with default credentials and go to **Settings** to enter your SMTP details.
4. **Cron Job**: This is **REQUIRED** for sending and scheduling. Set up a cron job to run every 5 minutes:
   ```bash
   */5 * * * * php /path/to/your/folder/cron.php >/dev/null 2>&1
   ```

---

## 📁 Directory Structure
- `/assets/`: CSS, JS, and UI design tokens.
- `/core/`: Authentication, mailer core, and shared configuration.
- `/storage/`: All data files (Contacts, Settings, Campaigns).
- `/storage/archive/`: Completed campaign reports.
- `/storage/scheduled/`: Campaigns waiting for their time.

---

## 🔒 Security
- **Auth Protection**: Every page is protected by a session-based login.
- **Data Privacy**: Flat-file JSON storage keeps your data isolated and easy to back up.
- **Bot Filtering**: Tracking logic automatically ignores common web crawlers to keep your stats clean.

---

**Built with ❤️ for AstraMail Users.**
