1.1	 The Problem

Children of this digital generation are increasingly relying on internet-accessing devices to entertain themselves, acquire knowledge, and communicate with others. Along with an increase in home Wi-Fi networks, the affordability of which is getting easier day by day, children have been using the internet for longer times, often without much supervision from adults. While the internet does have useful educational resources, it also poses certain serious concerns due to detrimental social connections, excessive entertainment, and hazardous information.

Most parents think that regular router limits, or basic device-level safety settings, are enough for security. In reality, these steps often are not enough. When parents are not actively monitoring their children's online activities, children keep accessing inappropriate websites, spending too much time on entertainment platforms, or engaging in other unsafe online behaviors.

Various studies have always highlighted the adverse impact of unsupervised use of the internet. According to UNICEF, with poor monitoring of children's internet use, there are heightened risks of sexual exploitation, cyberbullying, and predation (UNICEF, 2023). In the absence of adequate monitoring, unsafe interactions might go unreported, whereby children are exposed to harmful content and online predators.
In every case, there are a number of obstacles that parents have to overcome in order to keep their kids safe online. Children nowadays typically use a range of gadgets, from computers and tablets to smartphones, and they constantly switch between different browsers, apps, and even secret browsing modes. Due to this factor, manual supervision seems virtually impossible for most parents. Another thing is that many parents lack technical skills to properly configure safe surfing settings or to identify dangerous online behavior patterns.

Although useful, the current solutions for parental control have serious disadvantages. Most of them offer very limited visibility into real-time behavior; however, they do allow website filtering at a rudimentary level. They rarely emphasize access to educational content, and their reporting is often inadequate. As a result of this deficiency, parents cannot utilize positive reinforcement to reinforce good digital behaviors or identify hazardous conduct as it occurs.

There is an evident need for an inclusive system, considering the increase in internet-related threats and failures of traditional monitoring technologies. Such a system should provide network-level monitoring of Wi-Fi usage, detect patterns of risky behavior, effectively block access to non-educational content, and notify parents immediately when infractions occur. Without such an integrated solution in place, parents are still incapable of regularly offering digital supervision, putting children at preventable online risks.


1.2	The Client

The primary clients of this project are parents and guardians responsible for supervising their children's use of the internet within the home environment. As children become increasingly dependent upon digital platforms for education, communication, and forms of entertainment, parents face a heightened sense of pressure to ensure that their online experiences remain appropriate and safe. The rapid evolution in internet technologies has made this increasingly complex, especially for clients who may already be balancing work and other household and personal obligations.

Many parents rely on the default configuration options available in routers or on the parental control capabilities preinstalled in most devices. However, while these tools may seem adequate at first glance, most of them lack depth, flexibility, and, frankly speaking, intelligence to cope with modern online threats. Many parents complain that, first of all, they can't even understand how to set these settings correctly; it is for this reason that the protection turns out to be weak and not consistent. Furthermore, parents can't depend exclusively on controls at the device level with regard to kids using smartphones, tablets, laptops, and even smart TVs to connect to the internet.

Another challenge the client group faces is the lack of real-time awareness. In most cases, the tools rarely offer detailed or timely reports that would allow the parents to understand what their children are doing at any moment on the internet. Without such real data, parents often find out about inappropriate behavior when it has already happened-a time when interventions may be late or after exposures that are harmful have already taken place. That lack of visibility further prevents parents from recognizing patterns, such as overuse of entertainment platforms or repeated attempts to access forbidden websites.

Furthermore, parents are concerned with finding the balance between supervising their children and at the same time supporting their educational growth. The internet is a proven source of learning materials, games, and videos; however, parents want to feel confident in knowing that their child is exposed to safe content, as well as spending enough time utilizing educational resources. Very few pieces of existing parental control software promote educational content or incentivize productive online behavior, thus leaving an enormous support gap for parents who wish to foster balanced digital use.

Other differences are in the level of technological knowledge of parents as clients. Some parents have no problem with settings and controls, but others feel overwhelmed even with the simplest configurations. This ascertains the fact that the system should be accessible, intuitive, and easy to manage irrespective of technical background on the part of clients. It has to cater to highly tech-savvy parents just as much as it does to those who need more lucid guidelines and streamlined processes. All things considered, the customer needs a system for parental monitoring and control that works at the level of the network, offers real-time reporting, encourages educational engagement, and allows rules and restrictions to be customized. They need something that would allow them to protect their kids from potential harm through online interactions but still keep the experience enriching and age appropriate.

1.3	The Project/Solution

The proposed solution is the "Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting", a locally hosted parental-control platform designed to run on a Raspberry Pi 4B acting simultaneously as a Wi-Fi access point. Laravel 12 controls the Raspberry Pi using Linux shell scripts, acting as the web-based dashboard and automation manager.

The system comes fully equipped with monitoring and control capabilities. It tracks websites visited and allows parents to flag and block selected websites for child devices. Upon the expiration of the Internet time allocated to a child, the system will then route the device to a captive portal where the child is obligated either to take a quiz or watch an educational video in lieu of more allowed time. Parents can define internet access schedules and duration limits for children through their parent dashboard.

For each critical event, such as a child's usage time limit is reached, a flagged website is visited, attempts are made to access blocked websites, or new devices connect to the network, the system sends notifications in real-time to the parents. The system continuously monitors the total time each child's device spends online and generates comprehensive daily, weekly, and monthly reports. These reports summarize internet usage, visited sites, access to flagged websites, attempts to access blocked websites, and bandwidth consumption.

Through the web-based parental control dashboard, parents can configure access for connected devices, flag websites, block websites, add quizzes and educational videos to the captive portal, and view detailed reports. The dashboard is locally available within a home network and remotely through secure remote access methods, which lets parents monitor and manage their children's internet usage even outside of the home. It also handles connected devices for blocking and whitelisting purposes. Security includes user authentication, firewall rules, MAC address whitelisting, session management, and regular log monitoring against unauthorized access.

The system architecture is built upon Laravel 12 as the backend framework, which provides the web-based dashboard and automation management capabilities. The frontend utilizes Blade Templates with Alpine.js for interactive user interfaces, while MariaDB serves as the database management system for data storage and retrieval. NoDogSplash is integrated as the captive portal solution, providing the mechanism for intercepting and redirecting devices to educational content. Network control is achieved through iptables or nftables firewall rules for device-level blocking, and DNS-based blocking via dnsmasq for domain and application-level control, enabling comprehensive network and application management. The NoDogSplash integration employs `ndsctl` commands (auth and deauth) to dynamically manage device authentication states, allowing the system to redirect devices to the portal interface when their allocated time expires and authenticate them after successful completion of quizzes or educational videos.

The Raspberry Pi 4B is connected to the existing home network with a LAN cable and serves as an access point of the child device's Wi-Fi network. Laravel 12 will run directly on the Raspberry Pi itself, using either Nginx or Apache with PHP-FPM; thus, the whole web system will be operating on the very same machine. Because of this local deployment, Laravel 12 can execute Linux commands directly to manage network operations. The default setup will make the parent dashboard accessible over the local network, while remote access can be enabled through various ways, including VPN connections, cloud tunneling services, or port forwarding with appropriate security measures to enable parents to reach the control interface from any place with internet connectivity.

Raspberry Pi 4B plays multiple critical roles all at once: it serves as an Access Point, providing Wi-Fi to child devices; it works like a captive portal-that would intercept and redirect children to authentication pages that allow them to choose either a quiz or video option; it acts like a firewall and router, which controls inbound and outbound network traffic using iptables or nftables; it acts as a monitoring device to track and log all network activities; and finally, it hosts the Laravel 12 application as a web server, providing a dashboard and user interface.

Laravel 12's role is that of a central manager, which gives orders to the operating system. Laravel 12 does not manage hardware directly; it does this via several mechanisms, such as shell commands, to execute Linux commands directly; Python helper scripts to perform complex network operations, though most system management operations utilize Bash scripts; Bash scripts for network and system management; system service restarts to manage services, such as NoDogSplash and NetworkServices; and iptables/nftables rules to configure firewalls and routing. This project presents a comprehensive, integrated system for parents to monitor, manage, and regulate internet usage by child devices in a home network. It integrates the elements of an interactive dashboard with a learning-based access mechanism to ensure educational engagement with appropriate boundaries on the internet.

1.4	The Project Objectives

This project aims to achieve the following specific objectives:

1. To create a parental control system that is hosted locally, hence offering extra immense monitoring and control capabilities.

2. To design and implement a captive portal system that provides controlled access to the internet for child users, which involves an educational engagement to earn time on the internet.

3. To design a child portal interface that will show allowed browsing access, remaining time, and notifications coming from parents, with options to choose either a quiz or video activity.

4. To create a user-friendly registration and login module for parents and administrators. Secure access for parents to the parent dashboard, while children will access the system using a captive portal with identification through devices based on the MAC address.

5. To design a parent control dashboard that allows parents to view the browsing activity of their child, set time limits on internet access, block/allow websites, view current connection status in real time, and add new or modify/remove quizzes and educational videos for child users.

6. To integrate the system with compatible PLDT Wi-Fi modems for proper local network routing and captive portal functionality.

7. To evaluate the system’s usability, reliability, and effectiveness through testing with selected parent-child user pairs.

8. Data security and privacy should be ensured through the implementation of secure authentication, firewall rules, MAC whitelisting, and session management.

1.5	Scope and Delimitation

The system mainly aids parents in monitoring and controlling their child’s device. Specifically, the system will be able to: 

1. Monitor visited websites, manually flag, and block selected websites of assigned child devices. 

2. Redirect the assigned child device to take a quiz or watch a selected educational video that should be passed or completed for continuation of the internet connection. 

3. Define the schedules and duration for internet use in the assigned child devices through the parent device. 

4. Notify in real-time to the parent’s device if the usage time limit of the assigned child device has been reached, if the flag website was visited, an attempt is made to access blocked websites, or if new devices are connected to the system. 

5. Monitor the total time a child’s device spends online. 

6. Generate daily, weekly, and monthly reports for a summary of internet usage, visited sites, access to the flagged websites, attempts to access blocked websites, and bandwidth used. 

7. Allow the parent device to configure access to connected device’s, flag websites, block websites, add quiz and educational videos for the captive portal, and review reports through a web-based parental control dashboard. 

8. Manage connected devices for blocking and whitelisting. 

9. Provide basic security measures to prevent unauthorized access to the system, such as user authentication, firewall rules, MAC address whitelisting, session management, and regular log monitoring.


1.6	Design Constraints

Several technical, operational, and resource-related constraints underpin the development of this captive portal-based parental control system, factoring into various design, implementation, and deployment possibilities. It is these constraints that aided in a number of the design decisions and limited certain capabilities while also focusing the solution on what is most important to the parents.

Hardware Compatibility: This system relies on PLDT or Globe modem models that allow captive portal functionality or DNS redirection. Not every router model supports the installation of custom firmware or advanced configurations in the network, which may be incompatible with certain setups of home networks.

Network Infrastructure Limitations: The captive portal's performance is highly dependent on the stability of the local Wi-Fi network. Variability in internet speeds and signal strength may cause slow responsiveness of both the Portal Interface and the parent dashboard. Remote access to the parent dashboard relies on the quality of the internet connection from the home networks and might, under certain circumstances, need extra configuration like port forwarding, VPN setup, or cloud tunneling services, which not all network environments can support.

Browser Dependency: The system is basically designed for web browsers, and the compatibility of older browsers or devices that operate on very old software is limited. This limits the functionality of the system in certain devices.

HTTPS and Encryption Limitations: Since most modern websites employ active HTTPS encryption, the actual content of encrypted traffic cannot be inspected. The system performs HTTP-only interception via the Captive Portal; HTTPS requests are not intercepted, and devices are allowed to reach all HTTPS sites directly. This will be an acceptable limitation for the use case, since most browsers attempt HTTP first for captive portal detection, and the system can still perform redirects when devices attempt to reach HTTP sites. The system can only provide control at the domain level, which limits the granularity with which content filtering can be performed.

DNS-Based Blocking Capabilities: The system implements DNS-based blocking via dnsmasq to block domains and applications at the network level. This approach effectively blocks mobile applications, such as Facebook, Instagram, and TikTok, by blocking all related API domains, working for both web browsers and mobile applications. When a domain is blocked, dnsmasq redirects DNS queries for that domain to 127.0.0.1 (localhost), preventing both web browsers and mobile applications from accessing the blocked service. However, this is limited to domain-level control and cannot inspect the actual content of encrypted HTTPS traffic.

Web-based only: The design is limited to web technologies and does not include native mobile app development. However, the system does support app-level blocking through DNS-based domain blocking, which effectively blocks mobile applications, such as Facebook, Instagram, and TikTok, by blocking all related API domains. This approach works for both web browsers and mobile applications, providing comprehensive blocking at the network level even though the system itself is web-based.

Limited backend processing power: Because the system is hosted on a lightweight server, Raspberry Pi 4B, computationally intensive tasks such as deep packet inspection cannot be performed. This limits the depth in traffic analysis that can be done.

Free and open-source tools only: The project should not depend on a paid tool or proprietary libraries, possibly restricting resource-constrained users from accessing certain advanced features provided by commercial solutions.

User Knowledge and Technical Skills: The technical skills of the parents are limited, so the system needs to be straightforward and intuitive. However, this simplicity requirement prohibits the embedding of features that are overly complex, which may overwhelm non-technical users.

Maintenance and Monitoring: This system needs periodic maintenance to update the blocked site lists or manage user accounts. Parents must be willing to carry out simple maintenance tasks or have access to technical support.

Testing Environment: Testing will be confined to a home network setup with a small sample of parent-child users. It cannot represent every possible usage scenario or edge case.

Data Privacy Regulations: The system shall adhere to data privacy best practices by not storing more information than necessary regarding users. Sensitive data, such as passwords and user logs, needs to be kept secure; however, full encryption might be difficult due to limited hosting resources.

No Deep Content Analysis: The system cannot monitor the real messages, videos, or detailed content viewed by children due to privacy and security reasons. It can track domain-level access through logs, which has limited capability in showing actual online activities.

Limited Budget: System development can only utilize low-cost or freely available tools, therefore limiting the range of choice within the selection of technologies and services.

Time Constraints: The project timeline restricts the development of additional features such as AI-based content filtering or advanced analytics capabilities. Small Development Team: The system is developed by a small team that limits the complexity and scale of features which can be implemented within the project timeline.

1.7	Engineering Standards

This project follows the established standards of engineering and industry best practices with a view to making the system work reliably, securely, and in harmony with the existing network infrastructure and web technologies. Standards applied here directly support core functionalities and the security requirements of the system, ensuring that the solution works with existing devices and networks while maintaining appropriate security measures.

IEEE 802.11 Standards (Wi-Fi)

The Raspberry Pi 4B works as a Wi-Fi access point for child devices; the system has to follow the IEEE 802.11 standards that control wireless local area networks. This means a variety of standards that outline the manner in which devices communicate via Wi-Fi, including the transmission rate of the data, frequency band, and security protocols such as WPA2/WPA3. The system uses this standard so as to ensure that no matter the child device used, whether smartphones, tablets, or laptops of any given manufacturer, they are always connected to the access point smoothly. This is very important because the system has to be compatible with a number of devices already owned by families. (IEEE, 2020).

IEEE 802.3 Standards (Ethernet)

The Raspberry Pi is connected to the home router through an Ethernet cable, making the system dependent on wired network communications standards of IEEE 802.3. These standards ensure proper communication of the Pi with the router for traffic routing between child devices and the internet, captive portal functionality, and remote dashboard access by parents. Without proper Ethernet compliance, the system cannot route traffic between the child devices and the internet.

DHCP and DNS Protocols

The system will use the Dynamic Host Configuration Protocol to automatically assign IP addresses to child devices that connect to the Wi-Fi network. This is convenient as it eliminates the need for configuring the network manually and thus makes the system friendly for a family. Further, this system relies on the DNS protocols in resolving website addresses, which is essential in monitoring visited websites and their domain name level blocking. The system uses dnsmasq as the DNS server, which enables DNS-based blocking by redirecting blocked domains to 127.0.0.1, effectively blocking both web browsers and mobile applications. The protocols for DHCP are defined by RFC 2131 and DNS by RFC 1034/1035 to ensure that the system works with standard network infrastructures (IETF, 1997; IETF, 1987).

HTTP and HTTPS Protocols
Parent Dashboard and Captive Portal are web applications that use HTTP and HTTPS protocols to communicate. While HTTP can handle simple web requests, HTTPS uses TLS to provide an encrypted communication channel, which is necessary for sending sensitive data like login credentials or browsing logs. The system will support HTTPS to provide secure remote access to the parent dashboard, which will protect authentication and monitoring data even when the application is accessed over public networks. The captive portal functionality is implemented using NoDogSplash, which intercepts HTTP requests from unauthenticated devices and redirects them to the portal interface. These protocols are standardized through various IETF RFCs, with HTTPS security defined in RFC 8446. IETF. 2018.

W3C Web Standards

The frontend of the system utilizes HTML, CSS, and JavaScript technologies standardized by the W3C. Structure for the dashboard and portal interfaces is provided with HTML, while styles and responsive design elements are handled by CSS. JavaScript, standardized as ECMAScript by the international standards organization ECMA International, develops interactive features such as real-time notifications and form validation. Following these standards means the system will work consistently across different browsers and devices, something important since parents may access the dashboard from a variety of devices and browsers. W3C, 2021

OWASP Security Guidelines

Security becomes a crucial concern if a parental control system handles sensitive data about children's online activities. The project follows OWASP security best practices to avoid vulnerabilities of all kinds. The system defends against injection attacks, using query parameterization out of the box provided by Laravel. The system prevents cross-site request forgery by providing CSRF tokens. Authentication best practices are followed, while sessions are managed securely. The OWASP Top Ten list helps in guiding the security approach to mitigate risks like broken authentication, security misconfiguration, and exposure of sensitive data (OWASP, 2021).

Data Privacy Principles

While this is a local system and mainly used in the house, the project designs data privacy, inspired by the regulation such as General Data Protection Regulation (GDPR). It does not collect data on anything that is not required for its functionality. The password data is kept safely because it has hashing applied to sensitive information. It allows parents also to review the data that would be stored in the device and manages it. These practices ensure that the children's privacy is maintained and personal information should be responsibly handled even though full GDPR compliance is not considered applicable for a local home network system; European Union 2016 Software Quality Standards The software development process follows the principles of software quality in ISO/IEC 25010, which provides a systematic approach to software quality attributes. When designing the system, maintainability, reliability, usability, and security are considered. For example, the use of the Laravel framework encourages structure in coding for maintainability, and thorough error handling enables reliability, while the design of the user dashboard is done with usability in mind. Although the project does not seek official certification to ISO/IEC 25010, these quality attributes provide guidelines on developing the system.

1.8	Engineering Design Process

The development of the "Child-Centric Wi-Fi monitoring and control system with learning access management and automated reporting" followed a structured engineering design process, which helped break down the complex problem into manageable steps. This iterative approach-from identifying needs to building, testing, and improvement-ensures that the final solution addresses what parents need while keeping in consideration constraints of a technical and resource nature. This process has guided decisions on everything, from the choice of hardware platform Raspberry Pi 4B to how children gain internet time through quizzes and education videos.

1.8.1	Ask: Identify the Need and Constraints

The project started with an understanding of the clear pain points parents face in monitoring their children’s internet usage. What parents want is to know what websites their kids visit, block inappropriate content, set limits on how much time children can spend online, and receive notifications if something alarming happens. On the other hand, they seek something that will further encourage learning, which is why the system requires kids to complete quizzes or watch educational videos in order to earn more Internet time.

In building this system, however, came a number of constraints that shaped the design, the Raspberry Pi 4B was picked because it's affordable and powerful enough to run both a Wi-Fi access point and a web server, but this also means the system is rather limited in processing power compared to dedicated servers. By default, the system operates on the local network, which keeps things simple and secure, although it is possible to add remote access if needed later. Another crucial constraint is that most websites now use HTTPS encryption, which means the system will only be able to see what domains children visit, but not the pages or content. Finally, parents will need to create the educational quizzes and videos. Thus, the system needs to be designed in such a way as to make this process as smooth as possible.

1.8.2	Research the Problem

The actual problem that parents face in their effort to monitor and control their children's use of the internet was considered, and research began. According to UNICEF, a lack of proper parental supervision can make a child more vulnerable to all sorts of dangerous situations online, including exploitation and cyberbullying. Research also showed that existing parental control tools fall short by offering limited real-time visibility, incomplete reporting, and rarely integrate educational content to encourage productive internet use.

The existing parental controls available in the market were studied to identify what kind of functionalities they offer and where the gap lies. Many commercial solutions tackle only the basic website filtering without providing the network-level control necessary to manage multiple devices. Several others come with subscription fees or complicated setup procedures that discourage non-technical parents. The research indicated that parents wanted a network-level approach, real-time monitoring, and educational engagement instead of mere blocking.

The research also explored how captive portal systems work, since this technology would be essential in redirecting children to educational content after their time expires. Research on time-based access control methods provided insight into the technical challenges of tracking Internet usage with accuracy in a multi-device, multi-session environment. Research on integrating educational content suggested that making the children take quizzes or watch educational videos could serve as a good balancing mechanism between the use of the Internet and learning.

This stage in the research of the problem revealed a number of technical challenges that would have to be addressed during the design. Monitoring active sessions, not just the time of connection, is a prerequisite for accurately tracking how much time each device has spent online. Controlling network access securely via firewall rules has to be reliable enough to allow parents to trust the system. The dashboard needs to be intuitively designed so it can be used by parents who have limited technical knowledge. Technology choices, based on the problem research, included Laravel 12 as the web framework because of the security and ease of use it offers, MariaDB because of its database reliability on Raspberry Pi 4B, Nginx and PHP-FPM because they provide efficient web serving, and NoDogSplash for captive portal implementation. NoDogSplash was chosen for its ability to intercept HTTP requests and redirect devices to the portal interface using `ndsctl` commands for state management, allowing the system to control device authentication states (Preauthenticated and Authenticated) dynamically, which is essential for the time-based access control mechanism.

1.8.3	Image: Develop Possible Solution

Various approaches were explored in good understanding of the problem and available technologies.

System architecture diagrams were made to visualize how data would flow from child devices through the Raspberry Pi to the Laravel application and back. Workflow charts mapped out what happens when a child's time expires, how quiz completion grants additional time, and how video watching with dictionary word validation works. Interface mockups helped plan what the parent dashboard should look like and how children would interact with the captive portal.

These visualizations helped in comparing different approaches. For instance, the use of iptables firewall rules for blocking was more reliable as compared to DNS filtering alone. The dictionary word system for videos was chosen over just simple completion tracking since it ensures that the children pay attention. These design decisions balanced what would work technically with what would be effective for the parents and usable for the children.

1.8.4	Plan: Select a Promising Solution

Following a review of the alternative approaches from the previous phase, a detailed plan was created for the chosen architecture. The selected solution combines a Raspberry Pi 4B configured to work simultaneously as a Wi-Fi access point and web server, and a Laravel-based application acting as a central system controller. With this architecture, child devices will be connected to the Pi's dedicated Wi-Fi network while the system controls internet access through Linux firewall commands executed by secure shell scripts.

The planning process defined the core system workflow through a series of interconnected processes. First, each child device is assigned a certain amount of time for internet access. The system tracks all active internet sessions and subtracts time from the total amount of time awarded based on actual use. Once that time has been used up, the device automatically becomes blocked from using the internet and is presented with a captive portal interface. At this stage, children will have to take an educational quiz or view an educational video to progress. Once done, the system will add more time to use the internet, so the child may resume browsing. Meanwhile, parents would access a web-based dashboard that provided them the ability to configure device settings, set up time limits, manage educational content, block websites, or review usage reports.

The detailed design phase included a number of key components: a database schema to fully capture all system elements: devices, time allocations, attempted quizzes, completed videos, browsing history, and the many relationships among these; the time-tracking logic will be implemented using background job processing with one job periodically checking for active sessions and subtracting time, while another continually checks for expired time allocations, triggering the correct captive portal redirect. Other design considerations included security for running shell commands from the Laravel application, implementation of video playback controls that restrict fast-forwarding or seeking, and how dictionary words shown during video playback sessions are validated.

1.8.5	Create: Build a Prototype

The implementation of the prototype started by setting up the hardware and software infrastructure. The Raspberry Pi 4B was installed with the Raspberry Pi OS Lite and was configured as a Wi-Fi access point. The web server environment was set up by installing Nginx, PHP-FPM, and MariaDB to host the Laravel application. The Laravel framework was chosen for developing the core of the application, which was built out to include full core functionality, such as device management, where parents can enroll children's devices by their MAC addresses; website blocking and flagging mechanisms; quiz management and creation interfaces; the ability to upload educational videos and embedded dictionary words within them; monitoring of active sessions for time tracking; and captive portal integration, which handles the process of redirecting devices when time allocations have expired.

The prototype implementation includes several specialized services and components: a time tracking service that calculates remaining time allocations based on active internet sessions, the time granting service which is responsible for adding time rewards when kids successfully complete a quiz or view videos. The captive portal interface provides children with choices to either take a quiz or watch a video once their time runs out. The quiz system consists of question validation algorithms and automatic scoring, while the video system introduces playback controls that limit fast-forwarding and seeking, along with dictionary word validation whenever the video is finished. The parent dashboard is also built with views for comprehensive device management, report generation on usage, scheduling, and real-time monitoring of activities.

Security implementation involved multiple layers: protection against user authentication with role-based access control to differentiate parent/administrator privileges, CSRF protection in all form submissions, secure session management to prevent unauthorized access, and firewall rule configuration to allow device blocking at the network level. The system also included background job processing that runs periodically, which checks for the expiration of time, monitors active sessions, and generates automated reports.

1.8.6	Test and Evaluate Prototype

Comprehensive testing of the prototype covers system functionality, reliability, and usability. Time tracking mechanisms were subject to rigorous testing in order to ensure that time gets deducted accurately as kids browse on the internet, including edge cases where a device may disconnect during active sessions. The captive portal redirect functionality was also tested for proper blocking of devices with expired time allocations from accessing the internet and their correct redirects to the quiz and video selection interface.

The quiz system functionality was tested to verify that questions are correctly displayed, answers are accurately validated, and time granting mechanisms are activated only when the predefined passing score is reached. The video system was assessed to confirm that video fast-forward and seeking controls are indeed disabled; dictionary words appear at random times within playback; and upon video completion, word validation functions accurately. Parent dashboard usability testing covered adding devices, creating quizzes, uploading educational videos, and reading reports generated without requiring technical knowledge.

Testing highlighted the areas that needed refinement, including session tracking mechanisms for better handling when devices get disconnected and then reconnect to the network. The timing of notifications needs adjustment to ensure parents are alerted in a timely manner regarding any crucial events. The intuitive dashboard interface was redesigned based on user testing and feedback received for easy navigation. These tests hence gave insight into how well the system performs under real conditions and served as a guideline for further improvements.

1.8.7	Improve: Redesign as Needed

Systematic improvements to system reliability, ease of use, and effectiveness were made based on comprehensive test and evaluation results. The time tracking algorithm was optimized to better handle edge cases; it handles situations when a device is disconnected and then reconnected without compromising the precision of time calculations. The notification system is refined to notify parents at the right time and with timely relevance whenever there are critical events like time expiration, an attempt to visit a blocked website, or website visits marked as Flagged.

The dashboard interface was redesigned to be more intuitive, with enhanced navigation structures and a better logical flow of features. Generating reports was enhanced in terms of the range of information that is provided and its formatting, making it easier for parents to interpret. Security measures were enhanced by improving input validation mechanisms and utilizing stronger authentication procedures to help block unauthorized access and protect sensitive data.

This iterative improvement process represents an ongoing commitment to the refinement of the system, ensuring the solution evolves through feedback from parents and children in the real world. The ultimate objective is the development of a system that will not only function well from a technical perspective but also actually help families manage their internet usage in a manner that encourages both online safety and educational engagement.





