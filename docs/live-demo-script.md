# UniBook Cloud — Short Taglish Live Demo Script

**Introduction:**  
Good day. Our project is UniBook Cloud, a campus facility and equipment reservation system. Staff can request rooms or equipment, while the Admin reviews and approves or rejects the requests.

**Architecture:**  
Our Windows computer is the host. VirtualBox runs Ubuntu Server. Inside Ubuntu, UFW provides firewall rules and Docker Compose runs our PHP/Apache web container and MySQL database container.

**Dashboard:**  
After logging in, the dashboard shows reservation activity. The Admin can see all requests, while Staff can see their own requests.

**Resources:**  
The Resources page lists shared rooms and equipment, including their location and availability. Admin can add a resource or mark it unavailable.

**Create a request:**  
As Staff, I select a resource, enter the purpose, date, start time, and end time, then submit the request. The system saves it with Pending status and checks for conflicts with approved reservations.

**Review:**  
As Admin, I open the request and approve or reject it. I can also add a note. Staff can then check the updated status.

**Database and persistence:**  
The application data is stored in MySQL inside the `unibook-db` container. The named Docker volume keeps database files when containers are restarted or recreated.

**Security and backup:**  
We use password hashing, sessions, CSRF tokens, prepared statements, server-side role checks, and UFW. A Bash script creates timestamped database backups, and Cron can run it automatically.

**Closing:**  
Overall, UniBook Cloud demonstrates a multi-tier application, Docker containerization, database persistence, firewall configuration, and automated backups. Thank you.
