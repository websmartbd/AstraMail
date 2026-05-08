# 🚀 AstraMail: Advanced Email Marketing Engine

**AstraMail** is a professional-grade, high-performance email marketing platform built for precision delivery and real-time intelligence. Designed to run efficiently on shared hosting or dedicated servers, it combines a stunning modern UI with a powerful background engine.

---

## 🌟 Key Features

### 📡 Mission Control Dashboard (`logs.php`)
*   **Real-Time Monitoring**: Live progress tracking of active campaigns.
*   **Global Delivery Intelligence**: Consolidated stats (Pending, Sent, Failed) across all historical campaigns.
*   **System Engine Monitor**: A terminal-style feed of the `cron.log` for full backend transparency.
*   **Upcoming Queue**: Visibility into future scheduled campaigns.

### 🧼 Automated Bounce Handling (`core/bounceHandler.php`)
*   **IMAP Integration**: Connects to your inbox to detect undelivered messages.
*   **Automatic Pruning**: Automatically marks failed addresses as "Bounced" to protect your sender reputation.
*   **Rate-Limit Protection**: Smarter detection that ignores server-side "Max Defer" errors, protecting your valid contacts.
*   **Manual Override**: One-click reactivation of contacts from the management panel.

### 📊 Precision Analytics (`reports.php`)
*   **Open Tracking**: Invisible pixel technology to monitor when emails are read.
*   **Click Tracking**: Automatic link wrapping to measure engagement.
*   **High-Density Reporting**: SVG-powered analytics cards for Sent, Opens, Clicks, and Bounces.
*   **Historical Archive**: Permanent storage of campaign results for year-over-year comparison.

### ⚙️ Delivery Engine (`cron.php`)
*   **Status-Aware Sending**: Automatically skips "Bounced" or "Unsubscribed" contacts.
*   **Hourly Limits**: Configurable caps to stay within shared hosting SMTP restrictions.
*   **Smart Scheduling**: Plan your outreach in advance with time-accurate execution.
*   **System Diagnostics**: One-click environment check (`check.php`) for server compatibility.

---

## 🛠️ Technical Stack
*   **Backend**: PHP 7.4+ (Standard library)
*   **Frontend**: Vanilla HTML5 / JavaScript (ES6)
*   **Design**: Modern Dark/Light Design System with Custom SVG Iconography.
*   **Storage**: High-speed JSON flat-file storage (No SQL database required).
*   **Tracking**: PHP-based Pixel & Link Redirection Engine.

---

## 🚀 Quick Start

1.  **Upload**: Move all files to your server (e.g., `/public_html/mail/`).
2.  **Permissions**: Ensure the `/storage/` directory and its subfolders are writable (chmod 755 or 777).
3.  **Configure**: Visit the **Settings** tab to enter your SMTP (Sending) and IMAP (Bounce) credentials.
4.  **Automate**: Set up a Cron Job in your hosting panel (cPanel/DirectAdmin) to run every hour:
    ```bash
    php /home/your-user/public_html/mail/cron.php
    ```
5.  **Verify**: Click "Run System Diagnostics" in Settings to ensure all extensions are active.

---

## 🛡️ Security & Privacy
*   **Token Protection**: All sensitive API actions are protected by a unique system secret.
*   **Unsubscribe System**: Built-in compliant unsubscribe flow for audience management.
*   **Transport Security**: Full support for SSL/TLS SMTP encryption.

---
*Created with ❤️ for professional marketers. AstraMail — Reach Beyond.*
