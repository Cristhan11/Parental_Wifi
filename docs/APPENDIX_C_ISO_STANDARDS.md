APPENDIX C: ISO STANDARDS

This appendix documents ISO standards and industry standards that the Child-Centric WiFi Monitoring and Control System follows. Following these standards helps the system achieve reliability, security, usability, and maintainability.

C.1	ISO/IEC 25010: Systems and Software Quality Requirements and Evaluation

The system applies ISO/IEC 25010 principles, which define quality models for systems and software. These quality characteristics are covered:

C.1.1	Functional Suitability

Functional Completeness: All required functionality is included for parental control, time management, content filtering, and educational access control as specified in the project scope.

Functional Correctness: All functions work correctly and produce accurate results. Time tracking calculations are precise. Device blocking works reliably. Quiz and video validation is accurate.

Functional Appropriateness: Functions match the intended use case. Parents get intuitive interfaces. The captive portal offers child-friendly interfaces.

C.1.2	Performance Efficiency

Time Behavior: The system responds to user actions within acceptable timeframes. Background jobs run efficiently. Database queries are optimized for the Raspberry Pi's processing capabilities.

Resource Utilization: The system uses available resources efficiently. Memory usage is optimized. Background jobs are lightweight to prevent system overload.

Capacity: The system handles the expected number of devices and concurrent operations within Raspberry Pi 4B hardware constraints.

C.1.3	Compatibility

Coexistence: The system coexists with other network services and does not interfere with normal network operations.

Interoperability: The system uses standard network protocols IEEE 802.11, IEEE 802.3, DHCP, and DNS. This ensures compatibility with consumer devices and home routers.

C.1.4	Usability

Appropriateness Recognizability: The user interface is intuitive for non-technical parents. Technical terms are avoided. Clear labels guide users through operations.

Learnability: The system is easy to learn. The dashboard has clear navigation. Help text is available for complex operations.

Operability: The system is easy to operate. Common tasks take minimal steps. Error messages give clear guidance.

User Error Protection: The system prevents user errors through input validation. Confirmation dialogs appear for critical actions. Error messages are clear.

User Interface Aesthetics: The interface uses modern design principles with Tailwind CSS. This creates a clean and professional appearance.

Accessibility: The system follows WCAG 2.1 AA considerations. It includes keyboard navigation support, focus indicators, and accessible color choices.

C.1.5	Reliability

Maturity: The system operates reliably under normal conditions. Background jobs run consistently. Error handling ensures graceful degradation.

Availability: The system is designed for 24/7 operation. Critical services are configured as systemd services with auto-restart enabled.

Fault Tolerance: The system handles errors gracefully. Failed operations are logged. The system continues to function even when individual operations fail.

Recoverability: The system recovers from failures. Services restart automatically. Database transactions ensure data integrity.

C.1.6	Security

Confidentiality: User data and browsing history are stored securely. Passwords are hashed using bcrypt. Database access is restricted.

Integrity: Data integrity is maintained through database constraints, foreign key relationships, and transaction management.

Authenticity: User authentication ensures only authorized parents can access the system. Session management blocks unauthorized access.

Accountability: All system operations are logged for audit trails. Script executions, time grants, and blocking actions get recorded.

C.1.7	Maintainability

Modularity: The system follows Laravel's MVC architecture with clear separation of concerns. Service classes encapsulate business logic. Controllers handle HTTP requests.

Reusability: Code is organized into reusable service classes. Common functionality is centralized, reducing duplication.

Analyzability: Code follows PSR-12 coding standards, making it easy to understand and analyze. Comprehensive logging helps with debugging.

Modifiability: The system is designed for easy modification. Service classes can be extended. New features can be added without affecting existing functionality.

Testability: The system has unit tests and feature tests. PHPUnit is used for automated testing, ensuring code quality.

C.1.8	Portability

Adaptability: The system can be adapted to different network configurations. Settings can be adjusted through configuration files.

Installability: The system can be installed on Raspberry Pi OS Lite (64-bit) following documented installation procedures.

Replaceability: Components can be replaced with alternatives. Nginx can be replaced with Apache. MariaDB can be replaced with MySQL.

C.2	PSR-12: Extended Coding Style Guide

The system applies PSR-12 coding standards for PHP code:

C.2.1	Code Structure

Files: All PHP files use the <?php opening tag. They follow the namespace declaration structure.

Classes: Class declarations follow PSR-12 naming conventions. Class names use PascalCase. Methods use camelCase.

Properties and Methods: Visibility is declared for all properties and methods. Properties are typed where possible.

C.2.2	Code Formatting

Indentation: Code uses 4 spaces for indentation, not tabs.

Line Length: Lines do not exceed 120 characters where possible.

Keywords: PHP keywords are lowercase (true, false, null, etc.).

C.2.3	Namespaces and Use Declarations

Namespaces: All code is organized under the App namespace.

Use Declarations: Use statements are organized alphabetically and grouped by type (framework, application, third-party).

C.3	W3C Standards

C.3.1	HTML5

The system uses HTML5 semantic elements for proper document structure. Forms use appropriate input types. Accessibility attributes are included where necessary.

C.3.2	CSS3

Styling follows CSS3 standards. The system uses Tailwind CSS, which generates standards-compliant CSS. Custom styles apply CSS3 best practices.

C.3.3	ECMAScript (JavaScript)

JavaScript code follows ECMAScript standards. The system uses Alpine.js, which follows modern JavaScript standards. Code is written for compatibility with modern browsers.

C.4	Network Standards

C.4.1	IEEE 802.11 (Wi-Fi)

The system uses IEEE 802.11 standards for Wi-Fi connectivity. The Raspberry Pi's onboard 802.11ac Wi-Fi interface creates the access point. This ensures compatibility with consumer devices.

C.4.2	IEEE 802.3 (Ethernet)

The system uses IEEE 802.3 standards for Ethernet connectivity. The Raspberry Pi connects to the home router via Ethernet cable for internet access.

C.4.3	RFC 2131 (DHCP)

The system uses DHCP (Dynamic Host Configuration Protocol) as defined in RFC 2131 for automatic IP address assignment. Child devices receive IP addresses automatically when they connect to the WiFi network.

C.4.4	RFC 1034/1035 (DNS)

The system uses DNS (Domain Name System) as defined in RFC 1034 and RFC 1035 for domain name resolution. DNS-based blocking through dnsmasq redirects blocked domains to localhost.

C.5	Security Standards

C.5.1	OWASP Top Ten

The system applies security measures based on the OWASP Top Ten guidelines:

Injection Protection: All user inputs are validated and sanitized. Database queries use parameterized statements through Laravel's Eloquent ORM. This prevents SQL injection.

Broken Authentication: User authentication uses Laravel's built-in authentication system with bcrypt password hashing. Session management applies Laravel's secure session handling.

Sensitive Data Exposure: Passwords are hashed. Sensitive data is stored securely. Database connections use secure credentials.

XML External Entities (XXE): Not applicable as the system does not process XML from untrusted sources.

Broken Access Control: Authorization is enforced through Laravel policies. DevicePolicy ensures users can only manage their own devices.

Security Misconfiguration: System configuration follows security best practices. Script execution is restricted through whitelisting. File permissions are properly set.

Cross-Site Scripting (XSS): Blade templates automatically escape output. This prevents XSS attacks. User inputs are validated and sanitized.

Insecure Deserialization: The system uses Laravel's serialization mechanisms, which are secure. Untrusted data is not deserialized.

Using Components with Known Vulnerabilities: Dependencies are regularly updated. Composer is used for dependency management. Security advisories are monitored.

Insufficient Logging and Monitoring: Comprehensive logging is implemented. All critical operations are logged. This includes script executions, time grants, and blocking actions.

C.5.2	CSRF Protection

The system applies CSRF (Cross-Site Request Forgery) protection through Laravel's built-in CSRF token mechanism. All forms include CSRF tokens. POST requests are validated.

C.5.3	Password Security

Passwords are hashed using bcrypt, which Laravel provides by default. Bcrypt is a strong, adaptive hashing algorithm. It resists brute-force attacks.

C.6	Database Standards

C.6.1	Laravel Migration Conventions

The system applies Laravel's migration conventions:

Foreign Key Relationships: Foreign keys are defined to maintain referential integrity. Cascade deletes ensure related records are automatically removed when parent records are deleted.

Timestamps: All tables include created_at and updated_at timestamps for audit trails.

Indexes: Indexes are added for frequently queried columns MAC addresses and timestamps to improve query performance.

C.6.2	Data Integrity

Database constraints ensure data integrity:
- Foreign key constraints prevent orphaned records
- Unique constraints prevent duplicate entries
- Not null constraints ensure required fields are populated
- Check constraints validate data ranges where applicable

C.7	Accessibility Standards

C.7.1	WCAG 2.1 AA Considerations

The system applies WCAG 2.1 AA considerations:

Keyboard Navigation: All interactive elements support keyboard navigation. Users can navigate the interface with only the keyboard.

Focus Indicators: Focus indicators are visible for keyboard navigation. Interactive elements show clear focus states.

Color Contrast: Color choices consider contrast ratios for readability. Text is readable against background colors.

ARIA Labels: ARIA labels are used where appropriate to improve screen reader support. Form inputs have proper labels.

C.8	Testing Standards

C.8.1	PHPUnit Testing

The system uses PHPUnit for automated testing:

Unit Tests: Service classes and models are tested in isolation. Unit tests verify individual methods and functions.

Feature Tests: HTTP endpoints and user workflows are tested through feature tests. Feature tests simulate user interactions and verify system behavior.

Test Coverage: Critical functionality is covered by tests. Time tracking, time granting, and portal flows are thoroughly tested.

C.9	Documentation Standards

C.9.1	Code Documentation

Code is documented following PHP DocBlock standards:
- Classes have class-level documentation
- Methods have parameter and return type documentation
- Complex logic has inline comments where necessary

C.9.2	User Documentation

User documentation follows technical writing best practices:
- Clear, concise instructions
- Step-by-step procedures
- Troubleshooting guides
- Glossary of terms

C.10	Compliance Summary

The Child-Centric WiFi Monitoring and Control System complies with the following standards:

- ISO/IEC 25010: Systems and Software Quality Requirements and Evaluation
- PSR-12: Extended Coding Style Guide
- W3C HTML5, CSS3, and ECMAScript standards
- IEEE 802.11 (Wi-Fi) and IEEE 802.3 (Ethernet) standards
- RFC 2131 (DHCP) and RFC 1034/1035 (DNS) standards
- OWASP Top Ten security guidelines
- CSRF protection standards
- WCAG 2.1 AA accessibility considerations
- PHPUnit testing standards

This compliance helps the system meet industry standards for quality, security, usability, and maintainability. It provides a reliable and secure solution for parental control and child internet management.

