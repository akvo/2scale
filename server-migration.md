# 🛠️ Server Migration Steps

## 1. 📦 Dump Database from Previous Hosting

SSH into your old server and export the database:

```bash
mysqldump -u [DB_USER] -p [DB_NAME] > [DUMP_FILENAME].sql
```

You’ll be prompted for the database password.

## 2. 📥 Download the Dump File to Local Machine

Use scp to securely copy the SQL dump file:

```bash
scp [SSH_USERNAME]@[SERVER_IP_OR_DOMAIN]:/path/to/[DUMP_FILENAME].sql /local/path/to/save/
```

Replace the placeholders with your actual SSH username, server path, and destination.


## 3. ⚙️ Prepare Laravel Environment

Before importing the SQL file, ensure Laravel migrations are already applied:

```bash
php artisan migrate
```

This ensures the schema is properly initialized before restoring data.


## 4. 🧼 Modify SQL Dump (Important!)

If your SQL file contains lines like:

```bash
CREATE DEFINER=`old_user`@`host` ...
```

You may encounter permission errors during import.

✅ To fix:

Replace all:

```bash
DEFINER=\some_user`@`some_host`withDEFINER=CURRENT_USER`.
```
You can use a text editor or run:

```bash
sed -i '' 's/DEFINER=`[^`]*`@`[^`]*`/DEFINER=CURRENT_USER/g' [DUMP_FILENAME].sql
```

(The above sed command is for macOS. For Linux, remove '' after -i.)


## 5. 🗄️ Import SQL into New Server

Once the SQL file is cleaned up, import it using:

```bash
mysql -u [DB_USER] -p [DB_NAME] < [DUMP_FILENAME].sql
```


## 6. ❌ Disable current cron jobs

Disable 2 types of cron jobs from previous server:

- RSR sync

```bash
cd /home/customer/www/tc.akvo.org/public_html/{PROJECT_DIR} && /usr/local/bin/php73 /home/customer/www/tc.akvo.org/public_html/PROJECT_DIR/artisan rsr:sync
```

- Flow sync

```bash
cd /home/customer/www/tc.akvo.org/public_html/{PROJECT_DIR} && /usr/local/bin/php73 /home/customer/www/tc.akvo.org/public_html/PROJECT_DIR/artisan flow:sync
```

## 7. ⏱️ Optional: Increase PHP Max Execution Time

If needed, increase the max_execution_time to 300 seconds (5 minutes):

In `.htaccess` (if supported):

```bash
php_value max_execution_time 300
```

Or in your `php.ini`:

```bash
max_execution_time = 300
```

Or at runtime (for specific scripts):

```php
ini_set('max_execution_time', 300);
```

*⚠️ On shared hosting (e.g., SiteGround), changes may need to be made via the hosting control panel (PHP Manager).*
