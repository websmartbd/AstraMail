# 🚀 AstraMail Premium

A robust, modern, and lightweight email marketing platform built for precision and reliability. Designed to handle automated campaigns with granular control over delivery speeds and scheduling.

![Mailer Dashboard](https://img.shields.io/badge/Status-Scale--Ready-success?style=for-the-badge)
![PHP Version](https://img.shields.io/badge/PHP-7.4+-777bb4?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---

## ✨ Key Features

- **🕒 Multi-Campaign Scheduling**: Plan and queue unlimited future campaigns. The smart background worker automatically promotes and launches them at the exact second they are due.
- **📊 Real-time Progress Tracking**: Watch your campaigns send live with a dynamic progress bar, delivery logs, and bounce tracking.
- **⚙️ Configurable Hourly Limits**: Stay safe with your hosting provider by setting custom hourly sending limits directly from the dashboard.
- **👥 Contact Management**: Full CRUD support for your audience list with easy search and filtering.
- **🌍 Global Timezone Sync**: Sync the entire platform (Dashboard, Scheduler, and Worker) to your local timezone for perfect timing.
- **🎨 Premium UI**: A mobile-first, dark-mode-ready interface designed for a sleek professional experience.

---

## 🛠️ Installation & Setup

1. **Upload**: Copy all files to your PHP 7.4+ web server.
2. **Permissions**: Ensure the `storage/` directory and its subfolders are writable (`755` or `777`).
3. **Configure**: 
   - Login to the dashboard (Default: `admin123`).
   - Go to **Settings** and enter your SMTP credentials.
   - Set your **Local Timezone** and **Hourly Send Limit**.
4. **Automation**: Set up a Cron Job to run every minute (or every hour) depending on your needs:
   ```bash
   * * * * * php /path/to/your/mailer/cron.php
   ```

---

## 📈 Scalability Roadmap (The Future)

This platform is architected to be "Plug-and-Play" for high scalability. While the current JSON-based storage is perfect for lists up to 10,000+ contacts, here is how we can scale it to millions:

### 1. Database Migration (MySQL/PostgreSQL)
- **Current**: Flat-file JSON storage.
- **Upgrade**: Move `contacts.json` and `campaignState.json` to a relational database. This allows for instant querying and filtering of millions of records without memory overhead.

### 2. Distributed Worker Queue (Redis)
- **Current**: Single sequential cron worker.
- **Upgrade**: Implement a Redis-backed queue. This allows multiple "workers" to send emails simultaneously from different servers, increasing throughput from thousands to millions per hour.

### 3. API Connectors (Amazon SES / SendGrid)
- **Current**: Direct SMTP connection.
- **Upgrade**: Add API drivers for high-reputation bulk mail providers. This bypasses local server limits and ensures maximum deliverability for massive audiences.

### 4. Advanced Analytics
- **Upgrade**: Implement tracking pixels for Open Rates and URL redirectors for Click-Through Rate (CTR) monitoring.

---

## 📝 License
This project is licensed under the MIT License - see the LICENSE file for details.

---

**Developed with ❤️ for B.M Shifat.**
