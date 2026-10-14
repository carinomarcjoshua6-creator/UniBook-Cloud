# Final Reflection

UniBook Cloud helped me understand how an application and its infrastructure work together. The project combines a PHP/Apache web tier with a separate MySQL database tier using Docker Compose. It also demonstrates how application features such as resource booking and approval workflows depend on reliable database design.

A major lesson was the importance of persistent storage. A database should not depend on the lifetime of one container, so the project uses a named Docker volume. Security also needs multiple layers: password hashing and prepared statements in the application, role checks for administrative actions, and UFW rules at the server level.

The Bash backup script and Cron scheduling demonstrate how repetitive operational tasks can be automated. However, a backup is only useful when it can be restored, so backup validation and recovery testing should be part of ongoing maintenance.

Overall, UniBook Cloud is not only a booking website. It is a small multi-tier system that brings together application development, database management, containerization, network security, persistence, automation, and technical documentation.
