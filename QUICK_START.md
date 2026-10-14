# Quick Start Guide

Deploy the 3-tier HomeFind app on AWS in about 45 minutes. This is the condensed version. For explanations and troubleshooting, see `docs/SETUP.md`.

Tested on **Ubuntu 24.04 LTS**, **t3.micro**, AWS Academy Learner Lab (October 2026).

---

## Prerequisites

- [ ] AWS account or Learner Lab (region us-east-1)
- [ ] SSH key. In the Learner Lab, go to **AWS Details → Download PEM** (`labsuser.pem`, key pair **vockey**), then run `chmod 400 labsuser.pem`
- [ ] FRED API key: https://fred.stlouisfed.org/docs/api/api_key.html
- [ ] Your public IP (search "what is my IP")

---

## How it works

```
Browser → Server 1 (web, public) → Server 2 (app, private) → Server 3 (MySQL, private)
                                                            → FRED API (internet)
```

The browser only talks to **Server 1**. Server 1's Apache **reverse-proxies** `/api/*` to Server 2's private IP. Servers 2 and 3 have no public access.

---

## Step 1: Security groups (5 min)

**EC2 → Security Groups → Create**. Create them in this order. Add rules under **Inbound** only, and leave Outbound as *All traffic*.

| Group | Inbound rules |
|---|---|
| **web-tier** | SSH 22 ← My IP · HTTP 80 ← Anywhere-IPv4 |
| **app-tier** | SSH 22 ← My IP · HTTP 80 ← **web-tier** (security group) · HTTP 80 ← My IP *(temporary)* |
| **db-tier** | SSH 22 ← My IP · MySQL 3306 ← **app-tier** (security group) · MySQL 3306 ← My IP |

---

## Step 2: Launch 3 instances (5 min)

All three: **Ubuntu Server 24.04 LTS (x86)**, **t3.micro**, key pair **vockey**, auto-assign public IP **on**, **10 GiB gp3**. Under Network settings, choose **Select existing security group** with one group only.

| Name | Security group | Public IP | Private IP |
|---|---|---|---|
| HomeFind-Web-Server1 | web-tier | ________ | ________ |
| HomeFind-App-Server2 | app-tier | ________ | ________ |
| HomeFind-DB-Server3 | db-tier | ________ | ________ |

---

## Step 3: Server 3 — Database (10 min)

```bash
ssh -i labsuser.pem ubuntu@SERVER3_PUBLIC_IP

sudo apt update && sudo apt install mysql-server -y
sudo mysql_secure_installation      # N to VALIDATE PASSWORD, Y to everything else
                                    # "Skipping password set for root" is normal: root = sudo mysql

sudo sed -i 's/^bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql

git clone https://github.com/ank5688/aws-app.git ~/aws-app
sudo mysql < ~/aws-app/server3/schema.sql
sudo mysql < ~/aws-app/server3/sample_data.sql
sudo mysql -e "SELECT COUNT(*) FROM housing_db.listings;"    # 55
```

Create IP-restricted users (`sudo mysql`). Generate the password with `openssl rand -base64 18`:

```sql
CREATE USER 'appuser'@'SERVER2_PRIVATE_IP' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT SELECT ON housing_db.listings TO 'appuser'@'SERVER2_PRIVATE_IP';
CREATE USER 'appuser'@'YOUR_IP' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT SELECT ON housing_db.listings TO 'appuser'@'YOUR_IP';
DROP USER 'appuser'@'%';
SELECT user, host FROM mysql.user WHERE user = 'appuser';     -- 2 rows, no '%'
exit;
```

**✅ Done when:** the count is 55 and there are exactly 2 `appuser` rows.

---

## Step 4: Server 2 — Application (10 min)

```bash
ssh -i labsuser.pem ubuntu@SERVER2_PUBLIC_IP

sudo apt update && sudo apt install apache2 php libapache2-mod-php php-mysql php-curl -y
# (no php-json: it's built into PHP 8)

git clone https://github.com/ank5688/aws-app.git ~/aws-app
cd ~/aws-app/server2
bash setup.sh                       # creates includes/db.php and includes/fred.php from templates

nano includes/db.php                # DB_HOST = SERVER3_PRIVATE_IP, DB_PASS = YOUR_PASSWORD
nano includes/fred.php              # FRED_API_KEY = your key
grep DB_HOST includes/db.php        # double-check: no stray characters

php -r "require 'includes/db.php'; print_r(testDatabaseConnection());"   # [success] => 1
php -r "require 'includes/fred.php'; print_r(testFredAPI());"            # live rate (6.75 = fallback, means a bad key)

sudo cp -r api includes test.php /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod 640 /var/www/html/includes/db.php /var/www/html/includes/fred.php

curl -s "http://localhost/api/search-json.php?zip=90210&income=100000&beds=3&baths=2" \
  | grep -E '"success"|total_listings|affordable_count'
```

**✅ Done when:** curl shows `true`, `5`, `0`.

> `test.php` shows a JSON.parse error in the browser. That's a known bug in the test page only, and the page is deleted in Step 6.

---

## Step 5: Server 1 — Web + reverse proxy (5 min)

```bash
ssh -i labsuser.pem ubuntu@SERVER1_PUBLIC_IP

sudo apt update && sudo apt install apache2 -y
git clone https://github.com/ank5688/aws-app.git ~/aws-app
sudo cp ~/aws-app/server1/index.html ~/aws-app/server1/styles.css ~/aws-app/server1/script.js /var/www/html/

# Proxy config: make sure the IP matches SERVER2_PRIVATE_IP
grep ProxyPass ~/aws-app/server1/homefind-proxy.conf
sudo a2enmod proxy proxy_http
sudo cp ~/aws-app/server1/homefind-proxy.conf /etc/apache2/conf-available/
sudo a2enconf homefind-proxy
sudo apachectl configtest           # Syntax OK
sudo systemctl restart apache2      # sudo required

curl -s "http://localhost/api/search-json.php?zip=90210&income=100000&beds=3&baths=2" \
  | grep -E '"success"|total_listings|affordable_count'
```

Don't change `SERVER2_URL` in `script.js`. It's intentionally `''`, so the browser calls Server 1.

**✅ Done when:** curl shows `true`, `5`, `0` and `http://SERVER1_PUBLIC_IP` loads the search form.

---

## Step 6: Lock down (2 min)

```bash
# Server 2
sudo rm /var/www/html/test.php
```

In **EC2 → Security Groups → app-tier**, delete the **HTTP ← My IP** rule.

---

## Step 7: Test (5 min)

| # | Test | Expected |
|---|---|---|
| 1 | Browser: `http://SERVER1_PUBLIC_IP`, search **90210 / 100000 / 3 / 2** | 5 homes, all ✗ Not Affordable |
| 2 | Click **View as JSON** | JSON opens on Server 1's address |
| 3 | Search **85001 / 75000 / 1 / 1** | 5 homes, **1 ✓ Affordable** ($245k)* |
| 4 | From your laptop: `curl -m 5 "http://SERVER2_PUBLIC_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2"` | **Times out** (Server 2 is private) |
| 5 | On Server 1: `timeout 5 bash -c '</dev/tcp/SERVER3_PRIVATE_IP/3306' && echo OPEN \|\| echo BLOCKED` | **BLOCKED** (web tier can't reach the DB) |

\* Depends on the live FRED rate. It's affordable while the 30-year rate is below about 7.75%.

**✅ Deployment complete when all 5 pass.** Your app is live at `http://SERVER1_PUBLIC_IP`.

---

## Quick troubleshooting

| Symptom | Likely cause | Check |
|---|---|---|
| "Unable to connect to the server" on search | Proxy not enabled (404) or Server 1 can't reach Server 2 | On Server 1: `curl -sI http://localhost/api/search.php \| head -1` |
| DB test: `getaddrinfo ... Name or service not known` | Typo in `DB_HOST` | `grep DB_HOST includes/db.php` |
| DB test: `Access denied` | Wrong password, or no user for Server 2's IP | On Server 3: `sudo mysql -e "SELECT user,host FROM mysql.user WHERE user='appuser';"` |
| DB test hangs or times out | db-tier doesn't allow app-tier on 3306 | Security group rules |
| Rate is exactly 6.75% | FRED key invalid, so the fallback is used | Re-check `FRED_API_KEY` |
| SSH times out | Your IP changed, or the lab stopped | Update the **My IP** rules; start the lab |
| `systemctl` asks for a password | Missing `sudo` | Ctrl+C, rerun with `sudo` |

---

## Learner Lab notes

- Instances stop when the lab session ends. On restart, **public IPs change** (new app URL) but **private IPs don't**, so no config changes are needed.
- Stay within the free tier: t3.micro, 30 GiB total storage, and stop the lab when you're done.

## More documentation

- Full guide: `docs/SETUP.md`
- API reference: `docs/API_DOCUMENTATION.md`
- Architecture: `docs/NETWORK_DIAGRAM.md`

**GitHub Repository:** https://github.com/ank5688/aws-app
