# UniBook Cloud — Operational Manual

## 1. Project Overview

UniBook Cloud is a multi-tier campus facility and equipment reservation system. Staff can request rooms or shared equipment for a date and time. Admin reviews requests and approves or rejects them. The system keeps reservation status, administrative notes, and an activity history in MySQL.

## 2. Architecture

Windows Host → VirtualBox → Ubuntu Server → UFW → Docker Compose → PHP/Apache web container + MySQL database container → persistent MySQL volume.

The browser reaches the application through host port `8080`, mapped to Apache port `80`. MySQL port `3306` remains internal to the Docker network.

## 3. Requirements

- Windows 11 host
- VirtualBox and Ubuntu Server VM
- Docker Engine and Docker Compose
- UFW firewall
- Git (optional, for version control)

## 4. Configure Environment

In the project directory:

```bash
cp .env.example .env
nano .env
```

Replace the example passwords with strong unique values. Do not commit `.env` to GitHub.

## 5. Start the Application

```bash
docker compose up -d --build
docker compose ps
docker compose logs --tail=100
```

Open `http://127.0.0.1:8080` from the host when port forwarding is configured, or `http://SERVER-IP:8080` when the server is reachable on that address.

## 6. First Admin Setup

Open `/setup_admin.php` and create the first administrator with a strong password. After confirming login works, delete `website/setup_admin.php` from the deployed web directory. The setup page refuses to create another account after the first user exists.

## 7. Application Workflow

1. Staff signs in.
2. Staff selects **New Reservation**.
3. Staff chooses an available resource, purpose, date, start time, and end time.
4. The system validates the date/time and checks for conflicts with already approved reservations.
5. The request is saved with `Pending` status.
6. Admin opens the request and selects `Approved` or `Rejected`, with an optional note.
7. Staff can check the final status. Staff may cancel their own pending request.

## 8. Database

Database name: `unibook`  
Tables: `users` (accounts and roles), `resources` (rooms/equipment), `reservations` (booking requests and decisions), and `reservation_activity` (creation, approval, rejection, and cancellation history).

The MySQL database runs inside `unibook-db`. Its data is stored in the named Docker volume `unibook_mysql_data`.

Inspect the database:

```bash
docker compose exec db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

Then run:

```sql
SHOW TABLES;
SELECT id, username, full_name, role FROM users;
SELECT reservation_code, purpose, reservation_date, status FROM reservations;
SELECT reservation_id, actor_user_id, action, details, created_at FROM reservation_activity ORDER BY created_at DESC;
```

## 9. Security Controls

- Passwords are stored using PHP `password_hash()` and verified using `password_verify()`.
- Sessions are regenerated after successful login.
- CSRF tokens protect POST forms.
- PDO prepared statements are used for database access.
- Admin-only pages enforce the Admin role server-side.
- MySQL is not published as a host port.
- UFW should default-deny incoming traffic and allow only required ports.

Example firewall configuration (confirm your SSH access path before enabling):

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 8080/tcp
sudo ufw enable
sudo ufw status verbose
```

## 10. Persistence and Recovery Test

1. Create a reservation and note its code.
2. Run `docker compose restart`.
3. Check the reservation in the web application or query MySQL.
4. Run `docker compose down` followed by `docker compose up -d`.
5. Confirm the reservation still exists.
6. Reboot the VM and confirm containers and data return.

**Never use `docker compose down -v` during persistence testing** because `-v` removes named volumes.

## 11. Database Backup

Make the script executable:

```bash
chmod +x automation/backup.sh
```

For the deployed VM, copy it to `/opt/unibook/backup.sh` and ensure the backup directory is writable by the account running it.

```bash
sudo mkdir -p /opt/unibook/backups
sudo cp automation/backup.sh /opt/unibook/backup.sh
sudo chmod 750 /opt/unibook/backup.sh
sudo /opt/unibook/backup.sh
ls -lh /opt/unibook/backups/
```

The script uses `mysqldump --no-tablespaces`, creates a timestamped `.sql` file, verifies it is non-empty, and removes backups older than seven days. Test backup generation before configuring Cron.

## 12. Cron Scheduling

Example daily schedule at 23:00 VM local time:

```cron
0 15 * * * /opt/unibook/backup.sh >> /opt/unibook/backups/backup.log 2>&1
```

Install it in the intended service account's crontab and confirm the timezone:

```bash
timedatectl
crontab -e
crontab -l
systemctl status cron
tail -n 30 /opt/unibook/backups/backup.log
```

## 13. Troubleshooting

- **Web page unavailable:** check `docker compose ps`, port forwarding, and UFW.
- **Database connection error:** check `.env`, container health, and `docker compose logs db web`.
- **Admin setup blocked:** this is expected once any user account exists.
- **Reservation rejected due to conflict:** review existing approved reservations for that resource/date/time.
- **Backup fails:** verify container name, `.env` values inside the DB container, Docker permissions, and backup directory permissions.

## 14. Live Demonstration Checklist

1. Show the architecture diagram and project folders.
2. Show Ubuntu Server and `docker compose ps`.
3. Open the UniBook Cloud login page.
4. Log in as Admin and show the dashboard.
5. Show resources.
6. Create a Staff account from User Management.
7. Log out and log in as Staff.
8. Submit a reservation request.
9. Log out and log in as Admin.
10. Open the request and approve or reject it with a note.
11. Log in as Staff and show the updated status.
12. Show MySQL tables and reservation records.
13. Show the Docker volume.
14. Show UFW status.
15. Run a database backup and show the generated `.sql` file.
16. Show the Cron entry and backup log.
17. Demonstrate persistence after a container restart.

## 15. Limitations

This is an educational deployment baseline. Before production use, add HTTPS, stronger audit logging, stricter reservation policies, rate limiting, automated restore tests, and formal secret management.
