# Panel Adjustment — Engr. Zarate's Suggestions

Based on the minutes of the meeting from the project proposal defense.

---

## Adjustments

### Documentation & Roles
- **Differentiate roles clearly in the documentation** — Clearly define and distinguish the roles of Admin, Parent, Child Device, and Guest.
- **Add label for roles** in different lists throughout the system.
- **Add instructions for naming conventions** for devices.

### Admin Features
- **Include activity logs** — The system should record all activities done by the admin through logs.

### Child Device Features
- **Add age specification for children** — Child devices should have an age filter so that website content, quizzes, and educational videos are assigned age-appropriately.
- **Make the child landing page more interactive** — Design it to be appropriate and engaging for children.
- **Change quiz passing score to passing percentage** — Use a percentage-based passing threshold instead of a fixed score.

### Blocklist & Whitelist
- **Add a search function** that can filter by categories in the blocklist and whitelist of websites.
- **Add a "delete all" function** to remove all devices from the blocklist and whitelist at once.

### Account Management
- **Add data download/backup before account deletion** — Allow users to download their current data for backup purposes before deleting their account.
- **Add email verification** for account creation.

### Scheduling & Time
- **Add date selection for schedule settings** — Allow selecting specific dates in addition to time-based scheduling.

### UI/UX Improvements
- **Improve UI/UX for role selection of devices** — When Parent or Guest roles are selected, they should have preset configurations applied automatically.

### Analytics
- **Add time-range selections for analytics graphs** — Provide options for Daily, Weekly, Monthly, and Yearly views.

---

## Additional Q&A Clarifications (For Documentation Reference)
- **Admin intervention for account creation** — Justified for security purposes and to prevent children from creating their own parent accounts.
- **Authentication method** — Based on MAC address of the device, approved by the admin.
- **Auto-detection limitation** — The system can detect a device's MAC address on first connection but cannot determine who the user is; admin must manually assign roles.
- **Time reduction mechanism** — Countdown starts when the child device connects; pauses when disconnected if remaining time exists.
- **Target beneficiaries** — Both parents and children.
- **Target age group** — Children below 18 years old.
- **Main problem addressed** — Unsupervised children browsing the internet.
- **Technical challenge identified** — HTTPS encryption as a development obstacle.

---

## Category Breakdown

### Documentation Adjustments
These items involve changes to written documentation, descriptions, and reference materials:

1. Differentiate roles clearly in the documentation (Admin, Parent, Child Device, Guest)
2. Add label for roles in different lists in the system
3. Add instructions for naming conventions for devices
4. Include activity logs documentation
5. Change quiz passing score to passing percentage (update documentation to reflect new metric)
6. Document the admin intervention justification for account creation
7. Document the authentication method (MAC address-based)
8. Document the auto-detection limitation
9. Document the time reduction mechanism
10. Document the target beneficiaries and target age group
11. Document the main problem addressed (unsupervised children browsing)

### Prototype Adjustments
These items involve actual changes to the system's functionality, UI, or code:

1. Add age specification/filter for child devices (content, quizzes, videos assigned by age)
2. Make the child landing page more interactive and engaging for children
3. Change quiz passing score to passing percentage (system logic update)
4. Add a search function with category filter in blocklist and whitelist
5. Add a "delete all" function for devices in blocklist and whitelist
6. Add data download/backup function before account deletion
7. Add email verification for account creation
8. Add date selection for schedule settings
9. Improve UI/UX for role selection with preset configurations for Parent and Guest
10. Add time-range selections (Daily, Weekly, Monthly, Yearly) for analytics graphs
11. Include activity logs feature (admin activity recording)
12. Add label for roles displayed in system lists
