# 🚀 AstraMail: Advanced Email Marketing Engine

**AstraMail** is a professional-grade, high-performance email marketing platform built for precision delivery and real-time intelligence. Designed to run efficiently on shared hosting or dedicated servers, it combines a stunning modern UI with a powerful background engine.

---

## 🌟 Key Features

### 📡 Mission Control Dashboard
*   **Real-Time Monitoring**: Live progress tracking of active campaigns.
*   **Global Delivery Intelligence**: Consolidated stats (Pending, Sent, Failed) across all historical campaigns.
*   **System Engine Monitor**: A terminal-style feed of the engine logs for full backend transparency.
*   **Upcoming Queue**: Visibility into future scheduled campaigns.

### 🧼 Automated Bounce Handling
*   **IMAP Integration**: Connects to your inbox to detect undelivered messages.
*   **Automatic Pruning**: Automatically marks failed addresses as "Bounced" to protect your sender reputation.
*   **Rate-Limit Protection**: Smarter detection that ignores server-side "Max Defer" errors, protecting your valid contacts.
*   **Manual Override**: One-click reactivation of contacts from the management panel.

### 📊 Precision Analytics
*   **Open Tracking**: Invisible pixel technology to monitor when emails are read.
*   **Click Tracking**: Automatic link wrapping to measure engagement.
*   **High-Density Reporting**: SVG-powered analytics cards for Sent, Opens, Clicks, and Bounces.
*   **Historical Archive**: Permanent storage of campaign results for year-over-year comparison.

### ⚙️ Delivery Engine
*   **Status-Aware Sending**: Automatically skips "Bounced" or "Unsubscribed" contacts.
*   **Hourly Limits**: Configurable caps to stay within shared hosting SMTP restrictions.
*   **Smart Scheduling**: Plan your outreach in advance with time-accurate execution.
*   **System Diagnostics**: One-click environment check for server compatibility.

---

## 🛠️ Technical Stack
*   **Backend**: PHP 7.4+
*   **Frontend**: Vanilla HTML5 / JavaScript (ES6)
*   **Design**: Modern Dark/Light Design System.
*   **Storage**: High-speed JSON flat-file storage.
*   **Tracking**: PHP-based Pixel & Link Redirection Engine.

---

## 🚀 Quick Start

1.  **Upload**: Move all files to your server.
2.  **Permissions**: Ensure the `/storage/` directory is writable.
3.  **Configure**: Visit the **Settings** tab to enter your SMTP and IMAP credentials.
4.  **Automate**: Set up a Cron Job to run `cron.php` regularly.
5.  **Verify**: Run System Diagnostics in Settings to ensure all extensions are active.

---
*Created with ❤️ for professional marketers. AstraMail — Reach Beyond.*
