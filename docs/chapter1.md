# CHAPTER 1: THE PROJECT AND ITS BACKGROUND

## 1.1 The Problem

In today's digital age, children increasingly rely on internet-enabled devices for communication, entertainment, and educational purposes. As home Wi-Fi networks become more accessible and affordable, children are spending longer hours online, often with minimal adult supervision. While the internet offers valuable learning resources, it also presents significant risks through unsafe content, excessive entertainment platforms, and potentially harmful social interactions.

Many parents assume that basic device-level safety settings or standard router restrictions provide adequate protection. However, these measures often fall short. Children continue to access inappropriate websites, spend excessive time on entertainment platforms, or engage in risky online behaviors when parents are not actively monitoring their activities.

Research consistently demonstrates the serious consequences of unmonitored internet use. UNICEF warns that children face increased exposure to sexual exploitation, cyberbullying, and predatory behavior when online activity lacks effective supervision (UNICEF, 2023). Without effective monitoring, risky interactions can occur unnoticed, leaving children vulnerable to online predators and harmful content.

Parents face significant challenges when attempting to protect their children online. Modern children typically use multiple devices—smartphones, tablets, laptops—and frequently switch between different apps, browsers, and even private browsing modes. This makes manual supervision extremely difficult, if not impossible, for most parents. Furthermore, many parents lack the technical knowledge required to properly configure safe browsing settings or identify risky online behavior patterns.

Existing parental control tools, while helpful, have notable limitations. Most offer basic website filtering but provide limited visibility into real-time activity. Their reporting features are often incomplete, and they rarely prioritize educational content access. This gap leaves parents without effective means to detect harmful behavior as it occurs or to reinforce responsible digital habits through positive reinforcement.

The combination of growing online risks and the inadequacy of traditional monitoring tools creates a clear need for a comprehensive system. Such a system must observe Wi-Fi usage at the network level, identify unsafe behavior patterns, restrict non-educational content effectively, and alert parents immediately when violations occur. Without this type of integrated solution, parents remain unable to maintain consistent digital supervision, leaving children exposed to avoidable online threats.

## 1.2 The Client

The primary clients for this project are parents and guardians of school-aged children who seek effective ways to monitor, manage, and regulate their children's internet usage within the home environment. These clients share common concerns about ensuring their children access safe, age-appropriate, and educational online content while maintaining a healthy balance between screen time and other important responsibilities such as studying, physical activities, and family time.

This system addresses the needs of parents who require a user-friendly interface to configure internet access across multiple devices, particularly their children's devices. Parents want a centralized solution that enables them to monitor and control their children's internet usage comprehensively. Key needs include ensuring children access appropriate content, balancing internet access with educational activities, managing time limits effectively, and tracking browsing history and access attempts.

Parents also benefit from real-time notifications and detailed reports on their children's usage patterns. These include active device overviews, recent browsing activity summaries, security alerts, and schedule status updates. The system supports both local network access and remote access capabilities, allowing parents to monitor and control their children's internet usage from anywhere through secure web-based access. By integrating a captive portal-based system with Raspberry Pi 4B hardware and a web-based dashboard, this project provides a cost-effective, manageable, and technically sound solution that aligns with parents' goals of safety, education, and controlled internet access for their children.

## 1.3 The Project/Solution

The proposed solution is the "Child-Centric WiFi Monitoring and Control System with Learning Access Management and Automated Reporting"—a locally hosted parental control platform designed to run on a Raspberry Pi that simultaneously functions as a WiFi access point. The system uses Laravel as the web-based dashboard and automation manager, which controls the Raspberry Pi through Linux shell scripts.

### System Capabilities

The system provides comprehensive monitoring and control features. It monitors visited websites and allows parents to manually flag and block selected websites for assigned child devices. When a child's allocated internet time expires, the system redirects the device to a captive portal where the child must either take a quiz or watch an educational video to earn additional internet time. Parents can define schedules and duration limits for internet use through their parent dashboard.

The system provides real-time notifications to parents when critical events occur, including when a child's usage time limit is reached, when a flagged website is visited, when attempts are made to access blocked websites, or when new devices connect to the network. The system continuously monitors the total time each child's device spends online and generates comprehensive daily, weekly, and monthly reports. These reports summarize internet usage, visited sites, access to flagged websites, attempts to access blocked websites, and bandwidth consumption.

Through the web-based parental control dashboard, parents can configure access for connected devices, flag websites, block websites, add quizzes and educational videos for the captive portal, and review detailed reports. The dashboard is accessible both locally within the home network and remotely through secure remote access methods, enabling parents to monitor and manage their children's internet usage even when away from home. The system also manages connected devices for blocking and whitelisting purposes. Security measures include user authentication, firewall rules, MAC address whitelisting, session management, and regular log monitoring to prevent unauthorized access.

### System Architecture

The Raspberry Pi 4B connects to the existing home network through a LAN cable and acts as the access point for the child devices' WiFi network. Laravel runs directly on the Raspberry Pi itself using Nginx or Apache with PHP-FPM, meaning the entire web system operates on the same machine. This local deployment allows Laravel to directly execute Linux commands to control network operations. The parent dashboard is accessible through the local network by default, and remote access can be configured through various methods such as VPN connections, cloud tunneling services, or port forwarding with appropriate security measures, enabling parents to access the control interface from any location with internet connectivity.

The Raspberry Pi 4B serves multiple critical roles simultaneously. It functions as the access point that provides WiFi connectivity to child devices. It operates as a captive portal that intercepts and redirects children to authentication pages where they can choose between quiz and video options. It acts as a firewall and router that controls network traffic using iptables or nftables. It serves as a monitoring device that tracks and logs all network activity. Finally, it hosts the Laravel application as a web server, providing the dashboard and user interface.

Laravel acts as the central manager that sends instructions to the operating system. Rather than directly controlling hardware, Laravel triggers system-level operations through various mechanisms. These include shell commands for direct Linux command execution, Python helper scripts for complex operations, Bash scripts for network and system management, system service restarts for managing services like NoDogSplash and network services, and iptables/nftables rules for firewall and routing configuration.

This project provides parents with a complete, integrated system for monitoring, managing, and regulating internet usage of child devices within a home network. The system combines an interactive dashboard with a learning-based access mechanism that encourages educational engagement while maintaining appropriate internet boundaries.

## 1.4 The Project Objectives

The project aims to achieve the following specific objectives:

1. Develop a locally hosted parental control platform that provides comprehensive monitoring and control capabilities.

2. Design and implement a captive portal system that provides controlled access to the internet for child users, requiring educational engagement to earn internet time.

3. Develop a child portal interface that displays permitted browsing access, remaining time, notifications from parents, and options to select between quiz and video activities.

4. Develop a user-friendly registration and login module for parents and administrators, ensuring secure access to the parent dashboard, while children access the system through the captive portal using device-based identification (MAC address).

5. Create a parent control dashboard that enables parents to monitor browsing activity, set internet time limits, block or allow specific websites, view real-time connection status, and add, remove, or modify quizzes and educational videos for child users.

6. Integrate the system with compatible PLDT or Globe Wi-Fi modems to ensure proper local network routing and captive portal functionality.

7. Evaluate the system's usability, reliability, and effectiveness through testing with selected parent-child user pairs.

8. Ensure data security and privacy by implementing secure authentication, firewall rules, MAC address whitelisting, and session management.

## 1.5 Scope and Delimitation

The system primarily aids parents in monitoring and controlling their children's devices. Specifically, the system will be able to:

1. Monitor visited websites and allow parents to manually flag and block selected websites for assigned child devices.

2. Redirect assigned child devices to take a quiz or watch a selected educational video that must be passed or completed for continuation of internet connection.

3. Define schedules and duration limits for internet use in assigned child devices through the parent device.

4. Notify parents in real-time when the usage time limit of an assigned child device has been reached, when a flagged website was visited, when attempts are made to access blocked websites, or when new devices are connected to the system.

5. Monitor the total time a child's device spends online.

6. Generate daily, weekly, and monthly reports summarizing internet usage, visited sites, access to flagged websites, attempts to access blocked websites, and bandwidth used.

7. Allow parents to configure access for connected devices, flag websites, block websites, add quizzes and educational videos for the captive portal, and review reports through a web-based parental control dashboard that supports both local network and remote access.

8. Manage connected devices for blocking and whitelisting purposes.

9. Provide basic security measures to prevent unauthorized access to the system, including user authentication, firewall rules, MAC address whitelisting, session management, and regular log monitoring.

## 1.6 Design Constraints

The development of this captive portal-based parental control system is influenced by several technical, operational, and resource-related constraints that affect how the system is designed, implemented, and deployed. These constraints shaped many design decisions and limited certain capabilities, but they also helped focus the solution on what's most important for parents.

### Technical Constraints

**Hardware Compatibility**: The system depends on PLDT or Globe modem models that support captive portal functionality or DNS redirection. Not all router models allow custom firmware installation or advanced network configurations, which may limit compatibility with certain home network setups.

**Network Infrastructure Limitations**: The performance of the captive portal relies heavily on the stability of the local Wi-Fi network. Internet speed and signal strength variations may affect the responsiveness of both the portal interface and the parent dashboard. Remote access to the parent dashboard depends on the home network's internet connection quality and may require additional configuration such as port forwarding, VPN setup, or cloud tunneling services, which may not be available in all network environments.

**Browser Dependency**: The system is designed primarily for web browsers, and compatibility may vary across older browsers or devices with outdated software. This limitation affects the user experience on some devices.

**HTTPS and Encryption Restrictions**: Because most modern websites use HTTPS encryption, the system cannot inspect the actual content of encrypted traffic. The system uses HTTP-only interception through the captive portal; HTTPS requests are not intercepted and devices can access HTTPS sites directly. This is an acceptable limitation for the use case, as most browsers attempt HTTP first for captive portal detection, and the system can still redirect devices when they attempt to access HTTP sites. The system can only control access at the domain level, which limits the granularity of content filtering.

### Software Constraints

**Web-Based Only**: The design is limited to web technologies and does not include native mobile app development. Some advanced parental control features available in native applications, such as application-level blocking, cannot be implemented in this web-based approach.

**Limited Backend Processing Power**: Since the system runs on a lightweight server (Raspberry Pi) or local machine, computationally intensive processes like deep packet inspection are not feasible. This limits the depth of traffic analysis possible.

**Open-Source and Free Tools Only**: The project must avoid paid tools or proprietary libraries due to resource limitations, which may restrict access to certain advanced features available in commercial solutions.

### Operational Constraints

**User Knowledge and Technical Skills**: Parents may have limited technical skills, requiring the system to be simple and intuitive. However, this simplicity requirement restricts the addition of overly complex features that might confuse non-technical users.

**Maintenance and Monitoring**: The system requires periodic upkeep, such as updating blocked site lists or managing user accounts. Parents must be willing to perform basic maintenance tasks or have access to technical support.

**Testing Environment**: Testing is limited to a home network setup with a small sample of parent-child users, which may not fully represent all possible usage scenarios or edge cases.

### Security and Privacy Constraints

**Data Privacy Regulations**: The system must follow data privacy practices, limiting the amount of user information stored. Sensitive data such as passwords and browsing logs must be protected, though full encryption may be challenging given limited hosting resources.

**No Deep Content Analysis**: For privacy and security reasons, the system cannot monitor actual messages, videos, or detailed content viewed by children. It can only track domain-level access through logs, which provides limited visibility into actual online activities.

### Resource Constraints

**Limited Budget**: Only low-cost or freely available tools can be used in system development, which restricts the selection of technologies and services.

**Time Constraints**: The project timeline restricts the development of additional features such as AI-based content filtering or advanced analytics capabilities.

**Small Development Team**: The system is developed by a small team, which limits the complexity and scale of features that can be implemented within the project timeline.

## 1.7 Engineering Standards

This project follows established engineering standards and industry best practices to ensure the system operates reliably, securely, and maintains compatibility with existing network infrastructure and web technologies. The standards applied here directly support the system's core functionality and security requirements, ensuring that the solution works with existing devices and networks while maintaining appropriate security measures.

### IEEE 802.11 Standards (Wi-Fi)

Since the Raspberry Pi functions as a WiFi access point for child devices, the system must comply with IEEE 802.11 standards that govern wireless local area networks. These standards define how devices communicate over Wi-Fi, including data transmission rates, frequency bands, and security protocols like WPA2/WPA3. The system leverages these standards to ensure that child devices—whether smartphones, tablets, or laptops—can reliably connect to the access point regardless of manufacturer. This compatibility is essential because the system needs to work with various devices that families already own (IEEE, 2020).

### IEEE 802.3 Standards (Ethernet)

The Raspberry Pi connects to the home router via Ethernet cable, which means the system relies on IEEE 802.3 standards for wired network communication. These standards ensure that the Pi can communicate properly with the router and access the internet, which is necessary for the captive portal to function and for parents to access the dashboard remotely. Without proper Ethernet compliance, the system would be unable to route traffic between the child devices and the internet (IEEE, 2018).

### DHCP and DNS Protocols

The system uses Dynamic Host Configuration Protocol (DHCP) to automatically assign IP addresses to child devices when they connect to the WiFi network. This eliminates the need for manual network configuration and makes the system user-friendly for families. Additionally, the system relies on Domain Name System (DNS) protocols to resolve website addresses, which is crucial for monitoring visited websites and implementing domain-level blocking. These protocols are defined in RFC 2131 for DHCP and RFC 1034/1035 for DNS, ensuring the system works with standard network infrastructure (IETF, 1997; IETF, 1987).

### HTTP and HTTPS Protocols

The parent dashboard and captive portal are web-based applications that communicate using HTTP and HTTPS protocols. While HTTP handles basic web requests, HTTPS provides encrypted communication through Transport Layer Security (TLS), which is essential for protecting sensitive data like login credentials and browsing logs. The system is designed to support HTTPS for secure remote access to the parent dashboard, ensuring that authentication and monitoring data remain protected even when accessed over public networks. These protocols are standardized through various IETF RFCs, with HTTPS security defined in RFC 8446 (IETF, 2018).

### W3C Web Standards

The frontend of the system uses HTML, CSS, and JavaScript—technologies standardized by the World Wide Web Consortium (W3C). HTML provides the structure for the dashboard and portal interfaces, CSS handles styling and responsive design, and JavaScript (standardized as ECMAScript by ECMA International) enables interactive features like real-time notifications and form validation. Following these standards ensures that the system works consistently across different browsers and devices, which is important since parents may access the dashboard from various devices and browsers (W3C, 2021).

### OWASP Security Guidelines

Security is a critical concern for a parental control system that handles sensitive data about children's online activities. The project follows OWASP (Open Web Application Security Project) security best practices to protect against common vulnerabilities. Specifically, the system implements protection against injection attacks through Laravel's built-in query parameterization, uses CSRF tokens to prevent cross-site request forgery, implements secure session management, and follows authentication best practices. The OWASP Top Ten list guides the security approach, helping identify and mitigate risks like broken authentication, security misconfigurations, and sensitive data exposure (OWASP, 2021).

### Data Privacy Principles

While this is a local system primarily for home use, the project incorporates data privacy principles inspired by regulations like the General Data Protection Regulation (GDPR). The system minimizes data collection to only what's necessary for functionality, implements secure storage of sensitive information like passwords through hashing, and provides mechanisms for parents to review and manage stored data. These practices ensure that children's privacy is protected and that the system handles personal information responsibly, even though full GDPR compliance may not be required for a local home network system (European Union, 2016).

### Software Quality Standards

The development process follows software quality principles outlined in ISO/IEC 25010, which provides a framework for evaluating software quality attributes. The system is designed with maintainability, reliability, usability, and security in mind. For instance, the use of the Laravel framework promotes maintainable code structure, comprehensive error handling ensures reliability, and the user-friendly dashboard design focuses on usability. While the project doesn't pursue formal ISO/IEC 25010 certification, these quality attributes guide the development approach (ISO/IEC, 2011).

## 1.8 Engineering Design Process

The development of this parental control system followed a structured engineering design process that helped break down the complex problem into manageable steps. This iterative approach—from identifying needs to building, testing, and improving the system—ensured that the final solution actually addresses what parents need while working within technical and resource constraints. The process guided decisions about everything from choosing the Raspberry Pi as the hardware platform to designing how children earn internet time through quizzes and educational videos.

### 1.8.1 Ask: Identify the Need and Constraints

The project started by clearly understanding what parents actually struggle with when trying to monitor their children's internet use. Parents need a way to see what websites their kids visit, block inappropriate content, limit how long children can be online, and get notified when something concerning happens. But they also want something that encourages learning, which is why the system requires children to complete quizzes or watch educational videos to earn more internet time.

However, building this system came with several constraints that shaped the design. The Raspberry Pi 4B was chosen because it's affordable and powerful enough to run both a WiFi access point and a web server, but it also means the system has limited processing power compared to dedicated servers. The system works on the local network by default, which keeps it simple and secure, though remote access can be added later if needed. Another important constraint is that most websites now use HTTPS encryption, which means the system can only see which domains children visit, not the specific pages or content. Finally, the educational quizzes and videos need to be created by parents, so the system had to be designed to make this process straightforward.

### 1.8.2 Research the Problem

Research began by investigating the actual problem parents face when trying to monitor and control their children's internet use. UNICEF highlights that insufficient supervision increases the likelihood of harmful online interactions, including exploitation and cyberbullying (UNICEF, 2023). Research also showed that existing parental control tools often fall short because they provide limited real-time visibility, incomplete reporting, and rarely integrate educational content to encourage productive internet use.

Existing parental control solutions in the market were examined to understand what features they offer and where gaps exist. Most commercial solutions focus on basic website filtering but lack the network-level control needed to effectively manage multiple devices. Many require subscription fees or complex setup procedures that deter non-technical parents. The research revealed that parents need a solution that works at the network level, provides real-time monitoring, and encourages educational engagement rather than simply blocking access.

The research also explored how captive portal systems work, since this technology would be essential for redirecting children to educational content when their time expires. Studies on time-based access control methods helped understand the technical challenges of accurately tracking internet usage across multiple devices and sessions. Research into educational content integration showed that requiring children to complete quizzes or watch educational videos could be an effective way to balance internet access with learning.

This research phase identified several technical challenges that would need careful consideration during design. Accurately tracking how much time each device spends online requires monitoring active sessions rather than just connection time. Securely controlling network access through firewall rules needed to be reliable enough for parents to trust the system. The dashboard design would need to be intuitive enough for parents with limited technical knowledge to use effectively. Based on this problem research, technology choices were made: Laravel for the web framework due to its security features and ease of use, MariaDB for database reliability on Raspberry Pi, Nginx and PHP-FPM for efficient web serving, and NoDogSplash for captive portal implementation.

### 1.8.3 Imagine: Develop Possible Solutions

With a good understanding of the problem and available technologies, different approaches were explored. Several key design decisions needed to be made: Should the system block devices at the network level using firewall rules, or use DNS filtering? How should the captive portal redirect children when their time expires? Should quizzes be multiple-choice, short answer, or a mix? How can the system verify that children actually watch educational videos instead of just leaving them playing in the background?

System architecture diagrams were created to visualize how data would flow from child devices through the Raspberry Pi to the Laravel application and back. Workflow charts mapped out what happens when a child's time expires, how quiz completion grants additional time, and how video watching with dictionary word validation works. Interface mockups helped plan what the parent dashboard should look like and how children would interact with the captive portal.

These visualizations helped compare different approaches. For example, using iptables firewall rules for blocking proved more reliable than DNS filtering alone. The dictionary word system for videos was chosen over simple completion tracking because it ensures children actually pay attention. These design decisions balanced what would work technically with what would be effective for parents and usable for children.

### 1.8.4 Plan: Select a Promising Solution

After evaluating the alternative approaches identified in the previous phase, a comprehensive plan was developed for the selected solution architecture. The chosen approach integrates a Raspberry Pi 4B configured to function simultaneously as a WiFi access point and web server, with a Laravel-based application serving as the central management system. This architecture enables child devices to connect to the Pi's dedicated WiFi network, while the system controls internet access through Linux firewall commands executed via secure shell scripts.

The planning process defined the core system workflow through a series of interconnected processes. Initially, each child device receives a predetermined time allocation for internet access. The system continuously monitors active internet sessions and deducts time from the allocated amount based on actual usage. When the allocated time expires, the device is automatically blocked from internet access and redirected to a captive portal interface. At this point, children must choose between completing an educational quiz or watching an educational video. Upon successful completion of either activity, the system grants additional internet time, allowing the child to resume browsing. Concurrently, parents access a web-based dashboard to configure device settings, establish time limits, manage educational content, implement website blocking, and review usage reports.

The detailed planning phase encompassed several critical design components. The database schema was designed to comprehensively track all system entities, including devices, time allocations, quiz attempts, video completions, browsing logs, and the various relationships between these components. The time tracking mechanism was architected to utilize background job processing, with one job periodically monitoring active sessions and deducting time, while another job continuously checks for expired time allocations and triggers the appropriate captive portal redirect. Additional planning addressed security considerations for executing shell commands from the Laravel application, implementation of video playback controls that prevent fast-forwarding and seeking, and the validation mechanism for dictionary words displayed during video viewing sessions.

### 1.8.5 Create: Build a Prototype

The prototype implementation commenced with the hardware and software infrastructure setup. A Raspberry Pi 4B was configured with Raspberry Pi OS Lite and established as a WiFi access point. The web server environment was established through the installation of Nginx, PHP-FPM, and MariaDB to host the Laravel application. The Laravel framework was selected as the development platform, and the application was constructed with comprehensive core functionality including device management capabilities that enable parents to register children's devices using MAC addresses, website blocking and flagging mechanisms, quiz creation and management interfaces, educational video upload functionality with integrated dictionary word support, active session monitoring for time tracking, and captive portal integration for redirecting devices when time allocations expire.

The prototype implementation incorporated several specialized services and components. The time tracking service was developed to calculate remaining time allocations based on active internet sessions, while the time granting service manages the addition of time rewards following successful quiz completion or video viewing. The captive portal interface provides children with options to select between quiz and video activities when their time expires. The quiz system includes question validation algorithms and automated scoring mechanisms, while the video system implements playback controls that restrict fast-forwarding and seeking capabilities, along with dictionary word validation that occurs upon video completion. The parent dashboard was constructed with comprehensive views for device management, usage report generation, schedule configuration, and real-time activity monitoring.

Security implementation encompassed multiple layers of protection. User authentication was implemented with role-based access control distinguishing between parent and administrator privileges. Cross-site request forgery (CSRF) protection was integrated into all form submissions, secure session management was established to prevent unauthorized access, and firewall rules were configured to enable device blocking at the network level. Additionally, the system incorporated background job processing that executes periodically to monitor time expiration, track active sessions, and generate automated reports.

### 1.8.6 Test and Evaluate Prototype

Comprehensive testing of the prototype was conducted to evaluate system functionality, reliability, and usability. The time tracking mechanism underwent rigorous testing to verify accurate time deduction as children browse the internet, with particular attention to edge cases such as devices disconnecting during active sessions. The captive portal redirect functionality was tested to ensure that devices with expired time allocations are properly blocked from internet access and correctly redirected to the quiz and video selection interface.

The quiz system functionality was evaluated to confirm proper question display, accurate answer validation, and correct time granting mechanisms that only activate when the predetermined passing score is achieved. The video system was tested to validate that fast-forward and seeking controls are effectively disabled, dictionary words appear at randomized intervals during playback, and word validation operates correctly upon video completion. The parent dashboard underwent usability testing to ensure that parents can efficiently add devices, create quizzes, upload educational videos, and comprehend the generated reports without requiring technical expertise.

The testing phase identified several areas requiring refinement. Session tracking mechanisms needed enhancement to properly handle scenarios where devices disconnect and subsequently reconnect to the network. Notification timing required adjustment to ensure parents receive alerts promptly when critical events occur. The dashboard interface was redesigned based on user feedback to improve intuitiveness and navigation. These evaluations provided valuable insights into system performance under actual usage conditions and informed subsequent improvement efforts.

### 1.8.7 Improve: Redesign as Needed

Based on the comprehensive testing and evaluation results, systematic improvements were implemented to enhance system reliability, usability, and overall effectiveness. The time tracking algorithm underwent optimization to more effectively handle edge cases, particularly scenarios involving device disconnection and reconnection, ensuring that time calculations maintain accuracy regardless of connection interruptions. The notification system was refined to provide more timely and relevant alerts to parents when critical events occur, such as time expiration, blocked website access attempts, or flagged website visits.

The dashboard interface underwent redesign to improve intuitiveness, with enhanced navigation structures and more logical organization of features. Report generation capabilities were enhanced to provide more comprehensive information and improved formatting that facilitates easier interpretation by parents. Security measures were strengthened through improved input validation mechanisms and more robust authentication procedures to prevent unauthorized access and protect sensitive data.

This iterative improvement process represents an ongoing commitment to system refinement, allowing the solution to evolve based on real-world usage feedback from parents and children. The ultimate objective is to develop a system that not only functions effectively from a technical perspective but also genuinely assists families in managing internet usage in a manner that promotes both online safety and educational engagement.

## References

- UNICEF (2023). *Keeping children safe online*. United Nations International Children's Emergency Fund. https://www.unicef.org/protection/keeping-children-safe-online

