# UniBook Cloud

**UniBook Cloud: Campus Facility and Equipment Reservation System**

CCM101 Mission 10 — Enterprise Cloud Architect  
Alternative project idea built with the same deployment pattern as CampusFix: Windows host → VirtualBox → Ubuntu Server → UFW → Docker Compose → PHP/Apache + MySQL → persistent volume, with Bash/Cron backups.

## Purpose

UniBook Cloud helps a school manage shared facilities and equipment reservations. Staff can submit reservation requests for rooms or equipment, check request status, and view activity history. Admin can manage resources, approve or reject requests, and monitor reservations. The MySQL schema has four related tables: users, resources, reservations, and reservation_activity.

## Roles

- **Admin:** manages resources, reviews requests, approves/rejects reservations, and manages user accounts.
- **Staff:** submits reservation requests and checks their own requests.

## Database design

- `users`: login accounts and role assignments
- `resources`: bookable rooms and equipment
- `reservations`: date/time requests, approval state, and admin notes
- `reservation_activity`: audit trail of request creation and status changes

## Stack

- PHP 8.2 + Apache
- MySQL 8
- Docker + Docker Compose
- Ubuntu Server + UFW
- Bash + Cron

## Quick start (local Docker)

1. Copy `.env.example` to `.env`.
2. Set strong passwords in `.env`.
3. Run `docker compose up -d --build`.
4. Open `http://localhost:8080/setup_admin.php`.
5. Create the first Admin account.
6. Log in at `http://localhost:8080`.
7. Delete `website/setup_admin.php` after creating the admin account, or restrict access to it.

> The setup page only works when the `users` table is empty. Do not leave it available in a public production deployment.

## Common commands

```bash
docker compose up -d --build
docker compose ps
docker compose logs --tail=100
docker compose exec db mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"
docker volume ls
```

## Backup

The script at `automation/backup.sh` creates a timestamped SQL dump from the database container. Configure the production path and Cron entry as described in `operational-manual.md`.

## Security notes

- Keep `.env` private; it is ignored by Git.
- Do not publish MySQL port 3306 to the host.
- Use UFW with a default-deny incoming policy.
- Change all example credentials before deployment.
- Use HTTPS and a proper secret-management approach before exposing this system beyond a trusted lab environment.
