# Setup and Deployment Guide

This guide deploys HomeFind on three EC2 instances. It has been tested end to end on **Ubuntu 24.04 LTS** in an AWS Academy Learner Lab (October 2026).

For a condensed version, see `QUICK_START.md` in the repo root.

## Prerequisites

- An AWS account or AWS Academy Learner Lab (region: us-east-1)
- An SSH key pair. In the Learner Lab, use **AWS Details → Download PEM** to get `labsuser.pem`, which matches the key pair **vockey**.
- A FRED API key (free at https://fred.stlouisfed.org/docs/api/api_key.html)
- A terminal with `ssh`

Before your first SSH login, protect the key file. SSH refuses keys that other users can read:

```bash
chmod 400 /path/to/labsuser.pem
```

## Architecture Overview

```
[Visitor's browser]
        │  HTTP :80 (public)
        ▼
[Server 1 - Web tier]   Apache: serves index.html/styles.css/script.js
        │                and reverse-proxies /api/* to Server 2
        │  HTTP :80 (private network only)
        ▼
[Server 2 - App tier]   Apache + PHP: business logic, mortgage math
        │  MySQL :3306 (private)        │  HTTPS :443 (outbound)
        ▼                               ▼
[Server 3 - DB tier]    MySQL         [FRED API]
```

**The browser only ever talks to Server 1.** `script.js` uses relative URLs (`/api/search.php`). Server 1's Apache reverse proxy forwards those requests to Server 2 over the private network. Server 2 has no public access, so the browser can't reach it directly, and it doesn't need to.

## Placeholders used in this guide

| Placeholder | Meaning |
|---|---|
| `YOUR_KEY.pem` | Your SSH private key (Learner Lab: `labsuser.pem`) |
| `SERVERn_PUBLIC_IP` | Public IPv4 of server n. **It changes when the Learner Lab restarts.** |
| `SERVERn_PRIVATE_IP` | Private IPv4 of server n (172.31.x.x in the default VPC). **It stays the same.** |
| `YOUR_IP` | Your workstation's public IP (search "what is my IP") |

All server-to-server configuration uses **private** IPs, so a lab restart doesn't break the app. Only the public URL changes.

---

## Step 1: Create the security groups (before launching instances)

Security groups can name **another security group** as a source, instead of an IP address. Create them in this order so each one exists before another group refers to it.

**EC2 → Security Groups → Create security group** (VPC: default). Add these rules under **Inbound**. Leave **Outbound** at the default: All traffic → 0.0.0.0/0.

| Group | Type | Port | Source | Purpose |
|---|---|---|---|---|
| **web-tier** | SSH | 22 | My IP | Admin access |
| | HTTP | 80 | Anywhere-IPv4 (0.0.0.0/0) | Public website |
| **app-tier** | SSH | 22 | My IP | Admin access |
| | HTTP | 80 | Custom → **web-tier** (security group) | Server 1 forwards API calls |
| | HTTP | 80 | My IP | **Temporary**, for setup testing. Delete it in Step 6. |
| **db-tier** | SSH | 22 | My IP | Admin access |
| | MySQL/Aurora | 3306 | Custom → **app-tier** (security group) | Server 2 queries listings |
| | MySQL/Aurora | 3306 | My IP (plus each teammate's IP/32) | Team workstation access |

> **Common mistake:** adding these rules under *Outbound*. Inbound means who may connect *to* the server. Restricting outbound to only 22/80 would block `apt` (443), MySQL (3306), and the FRED API (443).

---

## Step 2: Launch the instances

Launch all three with these settings (**EC2 → Instances → Launch instances**):

| Setting | Value |
|---|---|
| AMI | **Ubuntu Server 24.04 LTS**, 64-bit (x86) |
| Instance type | **t3.micro** (free tier eligible) |
| Key pair | vockey (or your own) |
| Network settings | **Edit → Select existing security group** → the tier's group **only**. Remove any `launch-wizard-#` group. |
| Auto-assign public IP | Enable |
| Storage | **10 GiB gp3** (3 × 10 = 30 GiB, the free-tier limit) |

| Name | Security group |
|---|---|
| HomeFind-Web-Server1 | web-tier |
| HomeFind-App-Server2 | app-tier |
| HomeFind-DB-Server3 | db-tier |

Record each server's **public** and **private** IP.

---

## Step 3: Server 3 (Database tier)

```bash
ssh -i YOUR_KEY.pem ubuntu@SERVER3_PUBLIC_IP
```

### 3.1 Install MySQL

```bash
sudo apt update && sudo apt install mysql-server -y
sudo systemctl status mysql        # expect: active (running) / "Server is operational"  (press q)
```

### 3.2 Secure the installation

```bash
sudo mysql_secure_installation
```

| Prompt | Answer |
|---|---|
| VALIDATE PASSWORD component? | N |
| Remove anonymous users? | Y |
| Disallow root login remotely? | Y |
| Remove test database? | Y |
| Reload privilege tables? | Y |

> On Ubuntu 24.04 you will see *"Skipping password set for root as authentication with auth_socket is used."* This is expected. Root logs in with **`sudo mysql`**, with no password and no `-p`.

### 3.3 Listen on the network

By default MySQL only accepts local connections. Change it to listen on all interfaces. The **security group** still decides who can actually connect.

```bash
sudo sed -i 's/^bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql
sudo ss -tlnp | grep 3306          # expect: 0.0.0.0:3306
```

(A second line showing `127.0.0.1:33060` is MySQL's X Protocol. It's normal and can be ignored.)

### 3.4 Load the schema and sample data

```bash
git clone https://github.com/ank5688/aws-app.git ~/aws-app
sudo mysql < ~/aws-app/server3/schema.sql
sudo mysql < ~/aws-app/server3/sample_data.sql
sudo mysql -e "SELECT COUNT(*) FROM housing_db.listings;"    # expect: 55
```

The sample data has **55 listings**: 11 zip codes × 5 homes each.

### 3.5 Create IP-restricted application users

`schema.sql` creates a placeholder account, `'appuser'@'%'` ("from any host"). Replace it with accounts tied to specific IPs.

Generate a strong password on your workstation (`openssl rand -base64 18`) and keep it private. Then:

```bash
sudo mysql
```

```sql
CREATE USER 'appuser'@'SERVER2_PRIVATE_IP' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT SELECT ON housing_db.listings TO 'appuser'@'SERVER2_PRIVATE_IP';

CREATE USER 'appuser'@'YOUR_IP' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT SELECT ON housing_db.listings TO 'appuser'@'YOUR_IP';

DROP USER 'appuser'@'%';

SELECT user, host FROM mysql.user WHERE user = 'appuser';
exit;
```

The final query should return exactly **two rows**, one per IP, with no `%` row.

> **MySQL 8 note:** the older one-line syntax `GRANT ... IDENTIFIED BY ...` was removed in MySQL 8.0. Create the user first, then grant.
>
> **Least privilege:** the app only reads listings, so it only gets `SELECT`.
>
> **Adding a teammate:** repeat the `CREATE USER` + `GRANT` pair with their IP, and add a matching MySQL rule to **db-tier**.

---

## Step 4: Server 2 (Application tier)

```bash
ssh -i YOUR_KEY.pem ubuntu@SERVER2_PUBLIC_IP
```

### 4.1 Install Apache and PHP

```bash
sudo apt update && sudo apt install apache2 php libapache2-mod-php php-mysql php-curl -y
php -v                              # expect: PHP 8.3.x
php -m | grep -E 'mysqli|curl'      # expect: both listed
```

> Do **not** add `php-json`. JSON is built into PHP 8, and on Ubuntu 24.04 that package doesn't exist, which makes the install fail.

### 4.2 Get the code and create the config files

```bash
git clone https://github.com/ank5688/aws-app.git ~/aws-app
cd ~/aws-app/server2
bash setup.sh                       # copies db.php.template → db.php, fred.php.template → fred.php
```

`db.php` and `fred.php` hold secrets and are listed in `.gitignore`. **Never commit them.**

Edit `includes/db.php`:

```php
define('DB_HOST', 'SERVER3_PRIVATE_IP');
define('DB_PASS', 'YOUR_PASSWORD');
```

Edit `includes/fred.php`:

```php
define('FRED_API_KEY', 'YOUR_FRED_API_KEY');
```

Check that the host line has no stray characters:

```bash
grep DB_HOST includes/db.php
```

### 4.3 Test each connection from the command line

```bash
php -r "require 'includes/db.php'; print_r(testDatabaseConnection());"   # expect: [success] => 1
php -r "require 'includes/fred.php'; print_r(testFredAPI());"            # expect: a live rate
```

> If FRED reports exactly **6.75%**, that's the hard-coded fallback, which means the API call failed. Check the key.

### 4.4 Deploy to Apache

```bash
sudo cp -r api includes test.php /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod 640 /var/www/html/includes/db.php /var/www/html/includes/fred.php
```

`640` keeps the password and API key readable only by Apache (`www-data`), not by every account on the server.

### 4.5 Test through Apache

```bash
curl -s "http://localhost/api/search-json.php?zip=90210&income=100000&beds=3&baths=2" \
  | grep -E '"success"|total_listings|affordable_count|mortgage_rate'
```

Expected: `"success": true`, `"total_listings": 5`, `"affordable_count": 0`.

> **Known issue:** opening `test.php` in a browser shows *"JSON.parse: unexpected character at line 1 column 1."* `test.php` includes `search-json.php`, which changes the whole page's Content-Type to `application/json`. You can confirm with `curl -sI http://localhost/test.php | grep -i content-type`. The app itself is not affected, and `test.php` is deleted in Step 6.

---

## Step 5: Server 1 (Web tier)

```bash
ssh -i YOUR_KEY.pem ubuntu@SERVER1_PUBLIC_IP
```

### 5.1 Install Apache and deploy the front end

```bash
sudo apt update && sudo apt install apache2 -y
git clone https://github.com/ank5688/aws-app.git ~/aws-app
sudo cp ~/aws-app/server1/index.html ~/aws-app/server1/styles.css ~/aws-app/server1/script.js /var/www/html/
```

`script.js` already uses `const SERVER2_URL = '';`, so it makes relative requests to `/api/...` on Server 1. **Do not** set it to Server 2's IP. Code in the visitor's browser cannot reach a private IP.

### 5.2 Configure the reverse proxy

`server1/homefind-proxy.conf` forwards `/api/` to Server 2. If your Server 2 private IP differs from the one in the file, update it first:

```bash
cd ~/aws-app/server1
grep ProxyPass homefind-proxy.conf                      # check the IP
sed -i 's/172\.31\.22\.214/SERVER2_PRIVATE_IP/g' homefind-proxy.conf   # only if it differs
```

Install and enable it:

```bash
sudo a2enmod proxy proxy_http
sudo cp homefind-proxy.conf /etc/apache2/conf-available/
sudo a2enconf homefind-proxy
sudo apachectl configtest          # expect: Syntax OK
sudo systemctl restart apache2
```

> Always run `configtest` before restarting. A config error would stop Apache from starting.
>
> Use `sudo` for `systemctl`. Without it, Ubuntu asks for a password the `ubuntu` user doesn't have (press Ctrl+C to cancel).

### 5.3 Test the full chain

```bash
curl -s "http://localhost/api/search-json.php?zip=90210&income=100000&beds=3&baths=2" \
  | grep -E '"success"|total_listings|affordable_count'
```

Expected: the same result as on Server 2 (`true`, `5`, `0`).

Then, in a browser, open `http://SERVER1_PUBLIC_IP`:

- Search **90210 / 100000 / 3 beds / 2 baths**. Expect 5 listings, all **✗ Not Affordable**.
- Click **View as JSON**. The JSON opens on **Server 1's** address.

---

## Step 6: Lock down

1. **Delete the setup test page** (Server 2):
   ```bash
   sudo rm /var/www/html/test.php
   ```
2. **Remove the temporary rule:** EC2 → Security Groups → **app-tier** → Edit inbound rules → delete **HTTP from My IP** → Save.

Final inbound rules:

- **web-tier:** SSH from My IP · HTTP from anywhere
- **app-tier:** SSH from My IP · HTTP from web-tier
- **db-tier:** SSH from My IP · MySQL from app-tier · MySQL from team IPs

### Optional: host firewall (defense in depth)

Security groups are enough for this project. If you also enable `ufw`, **allow SSH before enabling it**, or you will lock yourself out:

```bash
sudo ufw allow 22
sudo ufw allow from SERVER2_PRIVATE_IP to any port 3306    # Server 3 example
sudo ufw enable
```

---

## Step 7: Verify the deployment

### Positive tests (should work)

| Test | Where | Expected |
|---|---|---|
| Row count: `sudo mysql -e "SELECT COUNT(*) FROM housing_db.listings;"` | Server 3 | 55 |
| Browser search 90210 / 100000 / 3 / 2 | Browser → Server 1 | 5 homes, 0 affordable |
| Browser search 85001 / 75000 / 1 / 1 | Browser → Server 1 | 5 homes, **1 affordable** ($245,000 at ≈ $1,700/mo vs. a $1,750 limit)* |
| View as JSON | Browser → Server 1 | JSON on Server 1's address |

\* This result depends on the live FRED rate. The $245,000 home stays affordable while the 30-year rate is below about 7.75%.

### Negative tests (should fail, which proves the lockdown)

| Test | Where | Expected |
|---|---|---|
| `curl -m 5 "http://SERVER2_PUBLIC_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2"` | Your workstation | **Times out** |
| `timeout 5 bash -c '</dev/tcp/SERVER3_PRIVATE_IP/3306' && echo "OPEN - problem" \|\| echo "BLOCKED - good"` | Server 1 | **BLOCKED - good** |

A timeout (not an error message) is expected. Security groups silently drop blocked traffic.

---

## Troubleshooting

### "Unable to connect to the server" in the browser

`script.js` shows this message for **any** failed request, including a 404. Find the real status code:

```bash
# On Server 1
curl -sI http://localhost/api/search.php | head -1
```

- `404 Not Found`: the reverse proxy isn't enabled (Step 5.2).
- `502`/`503`: Server 1 can't reach Server 2. Check **app-tier** allows HTTP from **web-tier**, check the IP in `homefind-proxy.conf`, and make sure Apache is running on Server 2.

### Can't connect to the database from Server 2

1. MySQL is listening on the network (Server 3): `sudo ss -tlnp | grep 3306` should show `0.0.0.0:3306`.
2. Network path (Server 2): `timeout 5 bash -c '</dev/tcp/SERVER3_PRIVATE_IP/3306' && echo OPEN || echo BLOCKED`. If BLOCKED, check that **db-tier** allows 3306 from **app-tier**.
3. User exists for Server 2's IP (Server 3): `sudo mysql -e "SELECT user, host FROM mysql.user WHERE user='appuser';"`
4. Read the error carefully. `getaddrinfo ... Name or service not known` means `DB_HOST` isn't a valid IP (for example, a stray character). `Access denied` means the password or user/host doesn't match.

### FRED API not working

```bash
curl "https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US&api_key=YOUR_KEY&file_type=json&limit=1&sort_order=desc"
php -m | grep curl
```

A rate of exactly 6.75% in the app means the fallback is being used.

### SSH times out

Your IP probably changed (for example, you switched networks). Update the **My IP** rules in the security groups. Also check the instance is running. Learner Lab instances stop when the session ends, and their **public IPs change** on restart.

### Apache logs

```bash
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log
```

---

## Maintenance

### Back up the database (Server 3)
```bash
sudo mysqldump housing_db > housing_db_backup_$(date +%Y%m%d).sql
```

### Force a fresh mortgage rate (Server 2)

Apache uses a private `/tmp`, so the simplest way to clear the cache is to restart Apache:
```bash
sudo systemctl restart apache2
```

### After a Learner Lab restart

- Public IPs change: share Server 1's new URL.
- Private IPs don't change: no config edits are needed.
- If you're on a different network, update the **My IP** rules.

---

## References

- MySQL: https://dev.mysql.com/doc/
- PHP: https://www.php.net/docs.php
- Apache mod_proxy: https://httpd.apache.org/docs/2.4/mod/mod_proxy.html
- FRED API: https://fred.stlouisfed.org/docs/api/
