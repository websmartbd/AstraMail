# 🚀 AstraMail: High-Precision Email Marketing Engine
> **Status:** v1.0.0 | **Engine:** PHP 7.4+ | **Architecture:** Zero-DB (JSON)

**AstraMail** is a professional, self-contained email marketing platform engineered for high-deliverability and real-time observability. Built with a refined PHP/JSON ecosystem, it offers extreme portability and speed, managing complex audience intelligence and background delivery without the overhead of a SQL database.

---

## 📡 Mission Control & Observability
*High-fidelity monitoring for total campaign transparency.*

*   **Live Telemetry**: Real-time progress monitoring for active background campaigns.
*   **Global Delivery Dashboard**: A unified view of account health, aggregating "Sent" and "Failed" events into a single intelligence feed.
*   **Engine Live-Feed**: Integrated terminal monitor streaming `cron` logs directly to your dashboard.
*   **Queue Management**: View and audit upcoming scheduled tasks before they execute.

## 🧼 Self-Cleaning Audience Logic
*Sophisticated feedback loops to protect your sender reputation.*

*   **IMAP Intelligent Scanning**: Automated inbox monitoring for DSN and hard host failures (550 errors).
*   **Self-Healing Lists**: Automatic pruning of "Bounced" addresses to maintain high domain authority.
*   **SMTP Rate-Limit Awareness**: Intelligent protection that distinguishes between "Bad Emails" and "Server Deferrals," protecting valid contacts during server outages.
*   **Manual Reactivation**: One-click restoration of contacts from the audience management panel.

## 📊 Precision Analytics
*Lightweight, high-impact reporting powered by SVG technology.*

*   **Engagement Tracking**: Real-time monitoring of "Opens" (pixel tracking) and "Clicks" (link wrapping).
*   **Bot Filtering**: Advanced tracking logic that ignores non-human scanners to ensure data accuracy.
*   **High-Density Reporting**: Clean analytics cards for Sent, Delivered, Opened, and Clicked metrics.
*   **Historical Archive**: Permanent record-keeping in a lean, portable JSON format.

## ⚙️ The Delivery Core
*Optimized for stability on shared, VPS, or dedicated environments.*

*   **Batch Delivery Control**: Built-in throttles to stay within provider-specific hourly SMTP limits.
*   **Status-Aware Logic**: Multi-layer filtering to ensure blocked or unsubscribed contacts are never messaged.
*   **Environment Diagnostics**: One-click system check to verify server compatibility and permissions.
*   **Smart Scheduling**: Precision timing for automated campaign launches.

---

## 🛠️ Technical Stack
| Component | Technology |
| :--- | :--- |
| **Backend** | PHP 7.4+ |
| **Frontend** | Vanilla HTML5 / JavaScript (ES6) |
| **Architecture** | Flat-File JSON (Portable) |
| **Tracking** | 1x1 Transparent Pixel & Link Redirection |
| **UI Design** | Premium Glassmorphism Design System |

---

## 🚀 Professional Deployment

### 1. Preparation
Upload the project directory to your web server (e.g., `/public_html/mail/`).

### 2. Permissions
Verify that the `/storage/` directory and all its sub-folders have write permissions (`0755` or `0777`).

### 3. Verification
Navigate to **Settings** and run the **System Diagnostics** to confirm all PHP extensions (IMAP, OpenSSL, JSON) are active.

### 4. Configuration
Enter your SMTP credentials for sending and IMAP credentials for automated bounce monitoring.

### 5. Automation
Add the following Cron Job to your server to enable the background engine:
```bash
* * * * * php /path/to/your/folder/cron.php > /dev/null 2>&1
```

---
*Created with ❤️ for professional marketers. AstraMail — Reach Beyond.*
