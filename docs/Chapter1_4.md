**CHILD-CENTRIC WIFI MONITORING AND CONTROL SYSTEM WITH**
**LEARNING ACCESS MANAGEMENT AND AUTOMATED REPORTING**


*____________MAJOR DESIGN EXPERIENCE INFORMATION____________________*

This capstone, entitled "Child-Centric Wi-Fi Monitoring and Control 
System with Learning Access Management and Automated Reporting," runs 
entirely on a Raspberry Pi 4B access point. It couples network-level 
control such as firewall, DNS blocking, captive portal with 
learning-based access extension through quiz or educational video that
encourages balanced internet use. It provides parents with a local, 
privacy-preserving dashboard to establish time limits, block or flag 
sites, manage devices, and review usage.  


The Engineering Design Process constraints consist of economic, 
technical/resource, security/privacy, operational, and 
environmental/sustainability. The Economic is low-cost, one-time 
hardware using Raspberry Pi 4B + SSD and open-source stack to avoid 
recurring fees. Technical/Resource has the requirement for low CPU/RAM
lightweight services, scheduled jobs, and pre-encoded media. The 
Security/Privacy has data stored locally, strict controls on execution
within shell, and OWASP-aligned web practices. The Operational is Easy
parental user experience, and offline-first behavior. Lastly, The 
Environmental/Sustainability has 3–7W power draw, small e-waste 
footprint, long-term operational reliability with replaceable 
individual components like power supply and SSD may need replacement 
over time. 


The System Architecture highlights the Raspberry Pi acting as Access 
Point, Captive Portal (NoDogSplash), Laravel 12 web application served
by Nginx, firewall/NAT, and MariaDB. It has three-tier control planes 
that has Laravel services to secure ScriptExecutor to approved bash 
scripts (iptables/ndsctl/dnsmasq). This separation enforces
validation,logging, and least privilege. The time tracking and 
enforcement has background jobs that track active sessions, deduct
time, detect expiration, and redirect to the portal. Successful 
quiz/video completion grants time and unblocks the device. 
The Educational engagement will have Quiz validation with passing 
thresholds like video flow with disabled seeking and randomized 
dictionary-word promptsthat must be recalled at completion. 
And Per-device policy has MAC-based device records, schedules, 
block/flag lists, timeallocations,and grants. 


Safety, Security, and Ethics will consider data minimization for 
Domain-level logging only and no packet payload inspection. Defense in 
depth for CSRF protection, input validation, bcrypt-hashed credentials 
sudoers whitelist for scripts, path and argument sanitization, and 
audit logging of executions. Child-friendly experience for Portal 
language is supportive rather than restrictive, and rewards are based 
on learning tasks rather than mere waiting. Lastly, Privacy 
andconsentfor all processing stays on the local appliance by default 
and remote access is opt-in and secured via VPN/tunnel guidance. 


The Standards and Compliance Considerations will follow Networking for
IEEE 802.11/802.3, RFC 2131 (DHCP), RFC 1034/1035 (DNS), RFC 8446 (TLS
1.3 for secure remote dashboard access). Web and code quality for W3C 
HTML/CSS/JS standards, PSR 12 PHP style, OWASP Top Ten controls, 
ISO/IEC 25010-inspired quality attributes. And the accessibility for 
WCAG-informed contrast and labeling in Blade templates. 


The Testing and Validation has Incremental test phases on Raspberry Pi
for Laravel setup, migrations/models, filesystem/video storage, shell 
execution, background jobs, and full portal flow. Functional coverage 
for Time expiration to portal redirect to quiz/video to time grant to 
unblock, DNS/app/domain blocking, device crud, and schedule 
enforcement.And Performance for background jobs is optimized for 
sub-second execution and low memory consumption on the Pi, and dnsmasq 
reloads are optimized to prevent service instability. 


The Risk and Mitigation consider hardware failure risk that is Medium 
but acceptable for home use, mitigated through documented recovery 
procedures, database backups, automatic service restarts, and 
cost-effective hardware replacement. Security risk that is Medium 
reduced through local data storage, strict script whitelisting via 
ScriptExecutor, and hardened web stack with OWASP-aligned practices. 
Lastly, Operational risk that is Mitigated through simple user 
experience, VPN/tunnel guidance for remote access, and offline-capable
operation.

---
*_______________________ABSTRATCK____________________________*

This capstone project is called "Child-Centric Wi-Fi Monitoring and 
Control System with Learning Access Management and Automated 
Reporting." Parents want better management and control over their 
children's internet use. Basic router settings provide limited 
options. This system addresses that need. 


The system serves as the central hub for the household's child Wi-Fi 
network. The system connects to the existing home network via a LAN 
cable. It acts as an access point for child devices. The systemcreates
an independent Wi-Fi network with SSID Parental_WiFi. Child devices 
connect to this network. The system runs a web-based parental control 
system and a captive portal to redirect the child devices. Network 
control enables comprehensive blocking of websites and mobile 
applications. When a child's assigned internet time runs out, the
system blocks the device from accessing the internet. The system 
redirects the device to a captive portal interface. At this portal, 
the child can take a quiz or watch an educational video that the 
parent has set up. After the child finishes the activity successfully, 
the system awards additional internet time and restores access to the 
child's device. 


The system have web-based dashboard that lets parents manage 
devices. Parents can manage time allocations, schedules, blocklists, a
whitelist, and assign roles. The dashboard is available over the local  
network by default. Remote access is enabled for parents to monitor 
and manage their children's internet use even when they are away from 
home. The system handles multiple roles. It acts as an access point, 
firewall, captive portal, and web app. The system also has background 
jobs that track sessions. They detect when time expires. All data 
stays local by default. The system uses only open-source software 
tools. 


Time management, network control, and learning incentives all
integrate in the system. Parents gain an efficient system to manage 
and control their children's internet usage. Children move toward 
educational engagement. Cost, security, reliability, and environmental 
impact are balanced. This keeps the system practical for home 
deployment. The system operates on a device with 4GB RAM running a 
64-bit operating system. Storage comes from a 480GB SSD. Power 
consumption stays between 3 and 7W during operation. 

Browsing control, time management, network control, and learning 
incentives all combine in the system. Parents gain an efficient system
to manage and control their children's internet usage, while children 
benefit from educational engagement. Cost, security, reliability, and 
environmental impact are balanced to keep the system practical for
home deployment.




**============================================================**
 *_______________________CHAPTER 01____________________________*
**============================================================**



*1.1 The Problem ____________________________*
Children of this digital generation rely more on internet-accessing
devices. They use these devices to entertain themselves. They use them 
to acquire knowledge. They use them to communicate with others. Home 
Wi-Fi networks are increasing. These networks are becoming more 
affordable every day. Children have been using the internet for longer
times. Often they use it without much supervision from adults. The 
internet has useful educational resources. It also poses serious 
concerns. These concerns come from detrimental social connections. 
They come from excessive entertainment. They come from hazardous 
information.


Most parents think regular router limits are enough for security. Some
parents think basic device-level safety settings are enough. In 
reality, these steps often are not enough.When parents are not 
actively monitoring their children's online activities, children keep 
accessing inappropriate websites. Children spend too much time on 
entertainment platforms. They engage in other unsafe online behaviors. 


Various studies highlight the adverse impact of unsupervised internet
use. Accordingto UNICEF, poor monitoring of children's internet use 
createsheightened risks. These risks include sexual exploitation. They 
include cyberbullying. They includepredation UNICEF,2023. Without 
adequate monitoring, unsafe interactions might go unreported. Children 
are exposed to harmful content. Children are exposed to online 
predators.


Parents face several obstacles to keep their kids safe online.
Children nowadays typically use a range of gadgets. These 
include computers. They include tablets. They include smartphones. 
Children constantly switch between different browsers. They switch
between apps. They switch between secret browsing modes. Due to this
factor, manual supervision seems virtually impossible for most 
parents. Another thing is that many parents lack technical skills. 
They cannot properly configure safe surfing settings. They cannot 
identify dangerous online behavior patterns. 


Current solutions for parental control have serious disadvantages.
Most of them offervery limited visibility into real-time behavior. 
They do allow website filtering at a rudimentary level. They rarely 
emphasize access to educational content. Their reporting is often 
inadequate. Parents cannot use positive reinforcement to reinforce 
good digital behaviors. Parents also cannot identify hazardous conduct
as itoccurs.


Internet-related threats are increasing. Traditional monitoring 
technologies are failing. There is an evident need for an inclusive 
system. This system must provide network-level monitoring of Wi-Fi 
usage. It must detect patterns of risky behavior. It must effectively 
block access to non-educational content. It must notify parents 
immediately when infractions occur. Without an integrated solution, 
parents cannot regularly offer digital supervision. This puts children 
at preventable online risks.


*1.2 The Client ____________________________*
The primary clients of this project are parents and guardians 
responsible for supervising their children's use of the internet 
within the home environment. As children become increasingly dependent
upon digital platforms for education, communication, and forms of 
entertainment, parents face a heightened sense of pressure to ensure 
that their online experiences remain appropriate and safe. The rapid 
evolution in internet technologies has made this increasingly complex,
especially for clients who may already be balancing work and other 
household and personal obligations. 


Many parents rely on the default configuration Alternatives available 
in routers or onthe parental control capabilities preinstalled in most 
devices.However, while these tools may seem adequate at first glance, 
most of them lack depth, flexibility, and, frankly speaking, 
intelligence to cope with modern online threats. Many parents complain 
that, first of all, they can't even understand how to set these 
settings correctly, it is for this reason that the protection turns 
out to be weak and not consistent. Furthermore, parents can't depend 
exclusively on controls at the device level with regard to kids using 
smartphones, tablets, laptops, and even smart TVs to connect to the 
internet. 


Another challenge the client group faces is the lack of real-time 
awareness. In most cases, the tools rarely offer detailed or timely 
reports that would allow the parents to understand what their children 
are doing at any moment on the internet. Without such real data, 
parents often find out about inappropriate behavior when it has 
already happened-a time when interventions may be late or after 
exposures that are harmful have already taken place. That lack of 
visibility further prevents parents from recognizing patterns, such as
overuse of entertainment platforms or repeated attempts to access
forbidden websites.


Furthermore, parents are concerned with finding the balance between
supervising their children and at the same time supporting their 
educational growth. The internet is a proven source of learning 
materials, games, and videos, however, parents want to feel confident 
in knowing that their child is exposed to safe content, as well as 
spending enough time utilizing educational resources. Very few pieces 
of existing parental control software promote educational content or 
incentivize productive online behavior, thus leaving an enormous 
support gap for parents who wish to foster balanced digital use.


Other differences are in the level of technological knowledge of 
parents as clients. Some parents have no problem with settings and 
controls, but others feel overwhelmed even with the simplest 
configurations. This ascertains the fact that the system should be
accessible, intuitive, and easy to manage irrespective of technical
background on the part of clients. It has to cater to highly 
tech-savvy parents just as much as it does to those who need more 
lucid guidelines and streamlined processes. All things considered, the 
customer needs a system for parental monitoring and control that works 
at the level of the network, offers real-time reporting, encourages 
educational engagement, and allows rules and restrictions to be 
customized. They need something that would allow them to protect their
kids from potential harm through online interactions but still keep
the experience enriching and age appropriate. 


*1.3 The Project/Solution ____________________________*
The proposed solution is the "Child-Centric Wi-Fi Monitoring and
Control System with Learning Access Management and Automated 
Reporting". This is a locally hosted parental control platform. The 
system operates as an integrated network gateway. The system operates 
as an access point. The system uses a web-based framework to manage
network operations. It manages operations through system level
commands. The framework acts as the dashboard interface. The framework 
acts as the automation manager.


The system comes fully equipped with monitoring capabilities. It comes
fully equipped with control capabilities. It tracks websites visited. 
It allows parents to flag selected websites for child devices. It 
allows parents to block selected websites for child devices. When the 
internet time allocated to a child expires, the system routes the 
device to a captive portal. At this portal, the child must take a 
quiz. The child can watch an educational video instead of getting more 
allowed time. Parents can define internet access schedules for 
children through their parent dashboard. Parents can define duration 
limits for children through their parent dashboard. 


The system sends notifications in real-time to parents for each 
critical event. These events include when a child's usage time limit 
is reached. They include when a flagged website is visited. They 
include when attempts are made to access blocked websites. They
include when new devices connect to the network. The system
continuously monitors the total time each child's device spends 
online. The system generates daily reports. The system generates 
weekly reports. The system generates monthly reports. These reports 
summarize internet usage. They summarize visited sites. They summarize 
access to flagged websites. They summarize attempts to access blocked 
websites. They summarize bandwidth consumption. 


Through the web-based parental control dashboard, parents can 
configure access for connected devices. Parents can flag websites. 
Parents can block websites. Parents can add quizzes to the captive 
portal. Parents can add educational videos to the captive portal.
Parents can view detailed reports. The dashboard is locally available
within a home network. The dashboard is available remotely through 
secure remote access methods. Parents can monitor their children's 
internet usage even outside of the home. Parents can manage their 
children's internet usage even outside of the home. The system handles
connected devices for blocking purposes. The system handles connected
devices for whitelisting purposes. Security includes user 
authentication. Security includes firewall rules. Security includes 
MAC address whitelisting. Security includes session management.
Security includes regular log monitoring against unauthorized access. 


The system operates as a dedicated network device. The system connects
to the existing home network. The system serves as an access point for 
the child device's Wi-Fi network. The web application runs directly on 
this local device. This allows the entire system to operate on a 
single computing platform. This local deployment allows the system
to execute system commands directly. The system uses these commands to
manage network operations. The default setup makes the parent 
dashboard accessible over the local network. Remote access can be 
enabled through VPN connections. Remote access can be enabled through 
cloud tunneling services. Remote access can be enabled through port
forwarding with appropriate security measures. This enables parents to
reach the control interface from any place with internet connectivity. 


The system plays multiple critical roles all at once. It serves as an
Access Point. It provides Wi-Fi to child devices. It works like a 
captive portal. It intercepts children. It redirects children to 
authentication pages. These pages allow them to choose either a quiz
or video option. It acts like a firewall. It acts like a router. It
controls inbound network traffic. It controls outbound network 
traffic. It acts as a monitoring device. It tracks all network 
activities. It logs all network activities. It hosts the web
application as a web server. It provides a dashboard. It provides a 
user interface. 


The web framework serves as the central manager. It coordinates system
operations.The framework does not directly control hardware. Instead, 
it manages the network through a secure, layered architecture. This 
architecture uses multiple mechanisms to send commands to the 
hardware. The system executes shell commands through helper scripts
for complex operations. The system uses system management scripts for
network control. The system manages system services for network 
services. The system manages system services for captive portal 
functionality. The system configures firewall rules to control
network traffic routing. This integrated system enables parents to
monitor internet usage by child devices in a home network. It enables 
parents to manage internet usage by child devices in a home network. 
It enables parents to regulate internet usage by child devices in a 
home network. The system combines an interactive dashboard with a 
learning-based access mechanism. The system ensures educational 
engagement. The system maintains appropriate boundaries on internet 
access. This creates a balanced approach. This approach combines 
supervision with positive reinforcement. This reinforcement comes 
through educational activities. 


*1.4 The Project Objectives ____________________________*
This project aims to achieve the following specific objectives: 

1. The project must deliver a locally hosted parental control system.
The system must provide network-level monitoring and control
capabilities. 

2. The project must design and implement a captive portal system. This
system provides controlled and monitored browsing experience for child 
users.Educational engagement is integrated into the system. Children
complete educational activities to earn additional internet time. This 
earned time allows them to continue their internet connection. 

3. The project must provide a captive portal. This portal displays the
remaining internet time. This portal offers quiz or video activity 
options. The project must provide a parent dashboard. Parents can 
control devices through this dashboard. Parents can schedule internet 
access, block websites, flag websites, and monitor visited websites.

4. The project must implement secure access for parents and
administrators. The system uses MAC-based device identification. The 
system uses secure command execution for network operations. 

5. The project must design a parent control dashboard. Parents can
view browsing history of child devices, set time limits, block 
websites, managequizzes for child users, and parents can manage
educational videos for child users. 

6. To integrate the system with compatible PLDT Wi-Fi modems while the
local device handles access point, captive portal, and routing
functions. 

7. Data security and privacy should be ensured through authentication,
firewall rules, MAC whitelisting, CSRF protection, session management, 
and log monitoring.

*1.5 Scope and Delimitation ____________________________*
The system mainly aids parents in monitoring and controlling their
child’s device. Specifically, the system will be able to:  

1. Monitor visited websites, manually flag, and block selected
websites of assigned child devices.  

2. Redirect the assigned child device to take a quiz or watch a 
selected educational video that should be passed or completed for 
continuation of the internet connection.  

3. Define the schedules and duration for internet use in the assigned
child devices through the parent device.

4. Notify in real-time to the parent’s device if the usage time limit
of the assigned child device has been reached, if the flag website was 
visited, an attempt is made to access blocked websites, or if new 
devices are connected to the system.

5. Monitor the total time a child’s device spends online.  

6. Generate daily, weekly, and monthly reports for a summary of
internet usage, visited sites, access to the flagged websites, 
attempts to access blocked websites, and bandwidth used.  

7. Allow the parent device to configure access to connected device’s,
flag websites, block websites, add quiz and educational videos for the 
captive portal, and review reports through a web-based parental 
control dashboard.  

8. Manage connected devices for blocking and whitelisting. 

9. Provide basic security measures to prevent unauthorized access to
the system, such as user authentication, firewall rules, MAC address 
whitelisting, session management, and regular log monitoring.

*1.6 Design constraints ____________________________*
Hardware Compatibility, the system depends on a local computing device
that serves as the access point, captive portal host, and web server. 
It assumes PLDT home routers that allow captive portal/DNS 
redirection, because not every router or firmware supports the 
required configurations. 


Network Infrastructure limitations, performance depends on local Wi-Fi
stability and upstream bandwidth. Remote dashboard access may require 
VPN, secure tunneling, or port forwarding, which not all home setups 
will support. 


Browser Dependency because the system is basically designed for web
browsers, and the compatibility of older browsers or devices that 
operate on very old software is limited. This limits the functionality 
of the system in certain devices.


HTTPS and Encryption of the system cannot inspect encrypted traffic.
Control is enforced at the DNS/domain level, content-level filtering 
or application-layer deep inspection is out of scope. Captive portal 
redirects rely on HTTP interception behavior common to captive portal 
detection.


Limited processing and resource capacity, because the system runs on a
lightweight computing platform, computationally intensive tasks such 
as deep packet inspection and heavyweight analytics cannot be 
performed. This limits the depth of traffic analysis to domain-level 
blocking rather than packet content inspection. Background jobs are
optimized for low overhead to work within these constraints. 


Free and open-source tools only because the solution relies solely on
free and open source tools, acknowledging that some advanced 
capabilities available in commercial offerings may not be included. 


User Knowledge and Technical Skills of Interfaces must remain simple
and intuitive for non-technical parents, so overly complex workflows 
are intentionally avoided to prevent overwhelming them. 


Maintenance and Monitoring for parents may need to update blocklists,
quizzes, and videos periodically, lightweight maintenance is expected. 


Testing Environment for validation is conducted within a home-network
setup using a small parent–child sample, and it cannot encompass every 
usage scenario or edge case.


And Data Privacy for the system collects only necessary data like
domains, timestamps, and MAC addresses. Full content capture is 
intentionally excluded to respect privacy and resource limits.


*1.7 Engineering Standards ____________________________*
IEEE 802.11 Standards of Wi-Fi, the system works as a Wi-Fi access
point for child devices and must follow the IEEE 802.11 standards that 
control wireless local area networks. This includes standards that 
outline the manner in which devices communicate via Wi-Fi, including 
the transmission rate of the data, frequency band, and security
protocols such as WPA2/WPA3. The system uses these standards to ensure
that no matter the child device used, whether smartphones, tablets, or 
laptops of any given manufacturer,they are always connected to the 
access point smoothly. This is very important because the system has 
to be compatible with a number of devices already owned by families. 
IEEE, 2020. 


IEEE 802.3 Standards of Ethernet, the system connects to the home
router through an Ethernet cable, making it dependent on wired network 
communications standards of IEEE 802.3. These standards ensure proper 
communication with the router for traffic routing between child 
devices and the internet, captive portal functionality, and remote
dashboard access by parents. Without proper Ethernet compliance, the
system cannot route traffic between the child devices and the 
internet. 


DHCP and DNS Protocols, the system will use the Dynamic Host
Configuration Protocol to automatically assign IP addresses to child 
devices that connect to the Wi-Fi network. This is convenient as it 
eliminates the need to configure the network manually and thus makes 
the system friendly for a family. Further, this system relies on the 
DNS protocols in resolving website addresses, which is essential in 
monitoring visited websites and their domain name level blocking. The 
protocols for DHCP are defined by RFC 2131 and DNS by RFC 1034/1035 to 
ensure that the system works with standard network infrastructures
IETF, 1997, IETF, 1987. 


HTTP and HTTPS Protocols, the Parent Dashboard and Captive Portal are
web applications that use HTTP and HTTPS protocols to communicate. 
While HTTP can handle simple web requests, HTTPS uses TLS to provide 
an encrypted communication channel, which is necessary for sending 
sensitive data like login credentials or browsing logs. The system 
will support HTTPS to provide secure remote access to the parent 
dashboard, which will protect authentication and monitoring data even 
when the application is accessed over public networks. These protocols 
are standardized through various IETF RFCs, with HTTPS security 
defined in RFC 8446. IETF. 2018. 


W3C Web Standards, the frontend of the system utilizes HTML, CSS, and 
JavaScript technologies standardized by the W3C. Structure for the
dashboard and portal interfaces is provided with HTML, while styles 
and responsive design elements are handled by CSS. JavaScript, 
standardized as ECMAScript by the international standards organization 
ECMA International, develops interactive features such as real-time
notifications and form validation. Following these standards means the
system will work consistently across different browsers and devices, 
something important since parents may access the dashboard from a 
variety of devices and browsers. W3C, 2021


OWASP Security Guidelines, the security becomes a crucial concern if a
parental control system handles sensitive data about children's online
activities. The project follows OWASP security best practices to avoid
vulnerabilities of all kinds. The system defends against injection 
attacks, using query parameterization and prepared statements. The
system prevents cross-site request forgery by providing CSRF tokens.
Authentication best practices are followed, while sessions are managed 
securely. The OWASP Top Ten list helps in guiding the security 
approach to mitigate risks like broken authentication, security
misconfiguration, and exposure of sensitive data OWASP, 2021. 


Data Privacy Principles, because this is a local system and mainly
used in the house, the project designs data privacy, inspired by the 
regulation such as General Data Protection Regulation GDPR. It does 
not collect data on anything that is not required for its 
functionality. The password data is kept safely because it has hashing
applied to sensitive information. It allows parents also to review the 
data that would be stored in the device and manages it. 
These practices ensure that the children's privacy is maintained and 
personal information should be responsibly handled even though full 
GDPR compliance is not considered applicable for a local home network 
system.


*1.8 Engineering Design Process ____________________________*
The development of the "Child-Centric Wi-Fi monitoring and control
system with learning access management and automated reporting" 
followed a structured engineering design process, which helped break 
down the complex problem into manageable steps.

This iterative approach-from identifying needs to building, testing,
and improvement ensures that the final solution addresses what parents 
need while keeping in consideration constraints of a technical and 
resource nature. This process has guided decisions on everything, from 
the choice of computing platform to how children gain internet time
through quizzes and educational videos.


*1.8.1 Ask: Identify the Need and Constraints  ______________________*
The project started with an understanding of the clear pain points
parents face in monitoring their children’s internet usage. What 
parents want is to know what websites their kids visit, block 
inappropriate content, set limits on how much time children can spend
online, and receive notifications if something alarming happens. On
the other hand, they seek something that will further encourage 
learning, which is why the system requires kids to complete quizzes or
watch educational videos in order to earn more Internet time.


In building this system, however, came a few constraints that shaped
the design. The computing platform was selected for affordability. The 
platform was selected for sufficient processing power. This processing 
power can run a Wi-Fi access point. This processing power can run a 
web server. The system is limited in processing power compared to 
dedicated servers. The system operates on the local network by 
default. This keeps things simple and secure. Remote access can be 
added if needed later. Most websites now use HTTPS encryption. The 
system can only see what domains children visit. The system cannot see 
the pages children visit. The system cannot see the content children 
view.

Parents must create educational quizzes. Parents must upload 
educational quizzes. Parents must create educational videos. Parents 
must upload educational videos. Thus, the system needs to be designed 
in such a way as to make this process as smooth as possible.


*1.8.2 Research the Problem ____________________________*
The actual problem that parents face in their effort to monitor and
control their children's use of the internet was considered, and 
research began. According to UNICEF, a lack of proper parental 
supervision can make a child more vulnerable to all sorts of
dangerous situations online, including exploitation and cyberbullying.
Research also showed that existing parental control tools fall short 
by offering limited real-time visibility, incomplete reporting, and 
rarely integrate educational content to encourage productive
internet use. 


The existing parental controls available in the market were studied to
identify what kind of functionalities they offer and where the gap 
lies. Manycommercial solutions tackle only the basic website filtering 
without providing the network-level control necessary to manage 
multiple devices. Several others come with subscription fees or
complicated setup procedures that discourage non-technical parents. 
The research indicated that parents wanted a network-level approach, 
real-time monitoring, and educational engagement instead of mere 
blocking. 


This stage in the research of the problem revealed several technical
challenges that would have to be addressed during the design.
Monitoring active sessions, not just the timeof connection, is a 
prerequisite for accurately tracking how much time each device has 
spent online. Controlling network access securely via firewall rules
must be reliable enough to allow parents to trust the system. The 
dashboard needs to be intuitively designed. Parents with limited 
technical knowledge must be able to use it.Technology choices were 
based on problem research. These choices included a modern web 
framework. This framework provides security. This framework provides 
ease of use. These choices included a reliable database system. This 
database system is suitable for local deployment. These choices
included efficient web serving components. These choices included a
captive portal solution. This solution redirects devices when time 
expires.


*1.8.3 Image: Develop Possible Solution  ____________________________*
System architecture diagrams visualize data flow. Data flows from
child devices through the local computing device. Data flows to the 
web application. Data flows back from the web application. Workflow 
charts map out processes. When a child's time expires, the system 
redirects them to the portal. Quiz completion grants additional time. 
Video watching includes dictionary word validation. The charts show 
these processes. Interface mockups helped plan what the parent 
dashboard should look like and how children would interact with the 
captive portal. 


These visualizations helped in comparing different approaches. For
instance, the use of iptables firewall rules for blocking was more 
reliable as compared to DNS filtering alone. The dictionary word 
system for videos was chosen over just simple completion tracking 
since it ensures that the children pay attention. These design 
decisions balanced what would work technically with what would be 
effective for the parents and usable for the children.


*1.8.4 Plan: Select a Promising Solution   __________________________*
Following a review of the alternative approaches from the previous
phase, an abstract plan was created for an architecture framework. The 
selected solution combines a local computing device configured to work 
simultaneously as a Wi-Fi access point and web server, and a web-based 
application acting as a central system controller. With this 
architecture, child devices will be connected to a dedicated Wi-Fi
network while the system controls internet access through firewall 
commands executed by secure system scripts.


The planning process defined the core system workflow through a series
of interconnected processes. First, each child device is assigned a
certain amount of time for internet access. The system tracks 
connection time and once that time has been used up, the device 
automatically becomes blocked from using the internet and is presented 
with a captive portal interface. At this stage, children will have to 
take an educational quiz or view an educational video to progress. 
Once done, the system will add more time to use the internet, so the 
child may resume browsing. Meanwhile, parents would access a web-based
dashboard that provided them the ability to configure device settings,
set up time limits, manage educational content, block websites, or 
review usage reports. 


The detailed design phase included a number of key components a 
database schema to fully capture all system elements like the devices, 
time allocations, attempted quizzes, completed videos, browsing 
history, and the many relationships among these. The time tracking 
logic uses background job processing. One job periodically checks for 
active sessions. This job subtracts time from active sessions. Another 
job continually checks for expired time allocations. This job triggers 
the captive portal redirect. Other design considerations were 
included. Security for running system commands from the web
application was considered. Video playback controls were implemented.
These controls restrict fast-forwarding. These controls restrict 
seeking. Dictionary words appear during video playback sessions. The 
system validates these words.


*1.8.5 Create: Build a Prototype   ____________________________*
The prototype starts with setting up the abstract architecture for
hardware and software. The system will be configured with a 
lightweight operating system and set up as a Wi-Fi access point. A web 
server was set up to host the web application. The web framework was 
chosen for developing the core of the application, which was built out 
to include full core functionality, such as device management, where
parents can enroll children's devices by their MAC addresses, the 
website blocking and flagging mechanisms, quiz management and creation 
interfaces, the ability to upload educational videos and embedded 
dictionary words within them, monitoring of active sessions for time 
tracking, and captive portal integration, which handles the process of
redirecting devices when time allocations have expired. 


The prototype implementation includes several specialized services and
components like a time tracking service that calculates remaining time
allocations based on active internet sessions, the time granting 
service which is responsible for adding time rewards when kids 
successfully complete a quiz or view videos. The captive portal
interface provides children with choices to either take a quiz or
watch a video once their time runs out. The quiz system consists of 
question validation algorithms and automatic scoring, while the video 
system introduces playback controls that limit fast-forwarding and
seeking, along with dictionary word validation whenever the video is
finished. The parent dashboard has views for device management. The 
parent dashboard has views for report generation on usage. The parent 
dashboard has views for scheduling. The parent dashboard has views for 
website blocking. The parent dashboard has views for website flagging. 
The parent dashboard has views for real-time monitoring of activities.
Security implementation uses multiple layers. The system provides 
protection against user authentication. The system uses role-based 
access control. This control differentiates parent privileges. This
control differentiates administrator privileges. The system provides
CSRF protection in all form submissions. The system provides secure 
session management. This management prevents unauthorized access. 
The system provides firewall rule configuration. This configuration 
allows device blocking at the network level.The system includes 
background job processing. This processing runs periodically. 
It checks for time expiration. It monitors active sessions. It 
generates automated reports. 


*1.8.6 Test and Evaluate Prototype   ____________________________*
Comprehensive testing of the prototype covers system functionality.
Testing covers system reliability. Testing covers system usability. 
Time tracking mechanisms were tested rigorously. Testing ensures time 
is deducted accurately as kids browse on the internet. Edge cases were 
tested. These cases occur when a device may disconnect during active
sessions.The captive portal redirect functionality was also tested for
proper blocking of devices with expired time allocations from 
accessing the internet and their correct redirects to the quiz and 
video selection interface. 


The quiz system functionality was tested. Testing verifies creation is
working. Testing verifies answers are accurately validated. Testing 
verifies time granting mechanisms are activated only when the 
predefined passing score is reached. The video system was assessed. 
Assessment confirms video fast-forward controls are disabled.
Assessment confirms seeking controls are disabled. Assessment confirms
dictionary words appear at random times during playback. Assessment 
confirms word validation functions accurately after video completion. 
Parent dashboard usability testing covered adding devices. Testing 
covered creating quizzes. Testing covered uploading educational 
videos. Testing covered reading reports. These reports are generated
without requiring technical knowledge. 


Testing highlighted the areas that needed refinement, including
session tracking mechanisms for better handling when devices get 
disconnected and then reconnect to the network. The timing of 
notifications needs adjustment to ensure parents are alerted in a
timely manner regarding any crucial events. The intuitive dashboard
interface was redesigned based on user testing and feedback received
for easy navigation. These tests gave insight into how well the system 
performs under real conditions and provided guidelines for further 
improvements. 


*1.8.7 Improve: Redesign as Needed   ____________________________*
Systematic improvements to system reliability, ease of use, and
effectiveness were made based on comprehensive test and evaluation 
results. The time tracking algorithm was optimized to better handle 
edge cases, it handles situations when a device is disconnected and 
then reconnected without compromising the precision of time
calculations. The notification system is refined to notify parents at 
the right time and with timely relevance whenever there are critical 
events like time expiration, an attempt to visit a blocked website,
or website visits marked as Flagged. 


The dashboard interface was redesigned to be more intuitive, with
enhanced navigation structures and a better logical flow of features.
Generating reports was enhanced,  range of information provided was 
expanded report formatting was improved. These changes make it easier 
for parents to interpret the reports. The security measures were 
enhanced, input validation mechanisms were improved, and stronger
authentication procedures were implemented. These measures help block 
unauthorized access. These measures protect sensitive data. 


This iterative improvement process shows an ongoing commitment to
system refinement. The solution evolves through feedback from parents.
This iterative improvement process shows an ongoing commitment to 
system refinement. The solution evolves through feedback from parents 
and children in real world use. The ultimate objective is developing a 
system. This system must function well from a technical perspective. 
This system must also help families manage their internet usage. This
management encourages online safety and educational engagement. 




**============================================================**
 *_______________________CHAPTER 02____________________________*
**============================================================**




*2.1 Discussion of Alternative Designs    ___________________________*
Router Firmware Customization, flashing open-source firmware 
OpenWRT-on supported commercial routers, followed by installing 
parental control packages. Minimum  additional hardware is required 
native performance of the routing device is maintained and wide
community support. Very high risk of bricking routers, rather limited 
compatibility with ISP-issued PLDT unit's steep learning curve for 
parents and limited UI customization. 


Cloud Managed Parental Control, 
utilizes third-party SaaS, which tunnels the traffic to the cloud for 
filtering and reporting. Automatically receives updates 
enterprise-grade analytics, with no maintenance required on-premise.
Although this is a very appealing solution, it includes drawbacks like 
it needs monthly fees, constant internet backhaul is needed, sends 
children's browsing data to external servers, hence raising major 
privacy concerns. In addition, it has very limited functionality 
offline and is more difficult to integrate quizzes and videos chosen 
by parents. 

Integrated Raspberry Pi 4B Access Point, uses a raspberry 
Pi 4B with SSD storage as an integrated access point, web server 
firewall, captive portal, and monitoring device. Low initial 
investment, energy efficient, open-source software tools with no
licensing fees, consolidate all functions into a single device, and 
have sufficient processing power for the requirements of 
offline-capable operation where the system continues to function and 
local 


*2.2 Design 1: Commercial Router with Custom Firmware _______________*
**2.2.1 Design Description               ___________________________**
The system is a commercial router flashed with open-source firmware, 
such as OpenWRT, as the central hub for the household "Child Wi-Fi" 
network. In this approach, the router connects to the existing home 
network via its WAN port and acts as both the primary router and 
access point for the child device's Wi-Fi network. It creates an 
independent Wi-Fi network with a different SSID which the child 
devices connect to and runs parental control packages directly on the 
router's firmware. The system utilizes the router's native routing 
capabilities while installing custom parental control packages that 
provide web-based management interfaces, DNS-based filtering via 
dnsmasq, and firewall rules for device-level blocking. 


When a child's assigned internet time is depleted, the system 
automatically blocks his or her device from accessing the internet 
using firewall rules and redirects them to a captive portal interface 
managed by packages like nodogsplash. At this portal, the child can 
either take a quiz or watch an educational video that the parent has 
set up. It is only after they have finished either activity 
successfully that the system will give more internet time and unblock their device. 


Lastly, a web dashboard where parents will be able to manage devices, 
flag/block websites, create quizzes and videos, set up schedules, view 
logs, and see reports. The parents can access the dashboard since it 
is available over the local network by default and remote access can 
be configured via VPN connections or cloud tunneling services if
desired, or port forwarding if appropriate security measures are 
taken, thus enabling parents to monitor and manage their children's 
internet use even when they are away from home. Since everything runs 
on the router itself, this leverages the router's native performance 
while maintaining local control. 



**2.2.2 Hardware Design                  ___________________________**
Core Router, a compatible commercial router that supports OpenWRT or 
similar open-source firmware, typically requiring ₱8,700-₱17,400 for a 
suitable model. The router will run OpenWRT firmware because of its 
wide community support, extensive package ecosystem, and ability to 
customize routing and parental control features. The router's 
native routing performance is maintained, providing excellent 
throughput and low latency for network operations. 


Networking, the commercial router will use its built-in dual-band Wi-Fi 
capabilities typically 802.11ac or 802.11ax to create the child 
device's network while maintaining its primary routing functions. The 
router connects to the ISP modem via its WAN port using a LAN cable 
for internet access, and its onboard Wi-Fi radios are configured to 
broadcast separate SSIDs for the child network and parent network, 
ensuring complete network segmentation. 


Storage, we rely on the router's internal flash storage, which 
typically ranges from 16MB to 128MB depending on the router model. 
This storage accommodates the OpenWRT firmware, installed packages, 
configuration files, and basic logging. For video storage and extended 
logging, we would need to attach a USB storage device or configure 
network-attached storage, which adds complexity and cost. The limited 
internal storage constrains the amount of educational videos and 
detailed logs that can be stored locally. 


Power and Cooling, the router will be powered via its standard AC 
adapter typically 12V/2A or similar, which is included with the router 
purchase. Commercial routers are designed for continuous operation and 
include built-in heat dissipation, so no additional cooling is 
required. The router's power consumption is typically 5-15W, making it 
energy efficient for 24/7 operation. 


Peripheral Support, USB ports on compatible routers remain available 
for attaching storage devices or additional peripherals. However, the 
router's limited processing power and memory compared to dedicated 
computing platforms restrict the complexity of applications that can 
run directly on the router.

```
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router (Network)                     │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (LAN Cable - Ethernet)
                            │ (Router Connection)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Commercial Router with OpenWRT Firmware                    │
│              (Primary Router + AP)                          │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi - 802.11ac/ax)
                            │ (SSID: Parental_WiFi)
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Child Devices Only (WiFi Only)                │
│         Smartphones, Tablets, Laptops                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ Power: 12V/2A AC Adapter
                            ▼
                    ┌───────────────┐
                    │   ╔═══════╗   │
                    │   ║       ║   │
                    │   ║       ║   │
                    │   ╚═══════╝   │
                    └───────────────┘
              Power Supply 12V/2A AC

              
┌─────────────────────────────────────────────────────────────┐
│              USB Storage (Optional)                          │
└─────────────────────────────────────────────────────────────┘
        Figure 2.2-1 Hardware Components Flowchart

```




**2.2.3 Schematic Design                 ___________________________**
The network flow proceeds from the ISP Modem/Router through a LAN 
cable to the Commercial Router with Custom Firmware, which
simultaneously functions as a Primary Router, WiFi Access Point, 
Firewall/Router iptables/nftables, and Captive Portal nodogsplash. 
Child devices connect via Wi-Fi, ensuring all traffic is routed 
through the router first, enabling comprehensive monitoring, 
filtering, and time-based access control using the router's native 
routing performance. 

```

                    ┌─────────────┐
                    │   🌐 🚀    │
                    │  Internet   │
                    └─────────────┘
                            │
                            │ (Internet Connection)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router                               │
│                  (Home Network)                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (LAN Cable - Ethernet)
                            │ (Router's Internet Access)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│     Commercial Router with OpenWRT Firmware                 │
│                                                             │
│  - Primary Router                                           │
│  - WiFi AP (SSID: Parental_WiFi)                            │
│  - Firewall (iptables/nftables)                             │
│  - Captive Portal (nodogsplash)                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi - 802.11ac/ax)
                            │ (Child Device Network)
                            │
                            ▼
                    ┌───────────────┐
                    │  📱 Child     |
                    │    Device     │
                    │ (Smartphone/  │
                    │   Tablet)     │
                    └───────────────┘
                Figure 2.2-2 Network Topology Diagram
```

The router manages multiple network zones which include the WAN the 
uplink to the internet, LAN the parent's network, and Child Network 
the child device's isolated network. We use NAT Network Address 
Translation plus firewall rules to isolate child traffic while still 
allowing the router itself to access the internet for updates and the 
parent dashboard. The router's built-in switching and routing 
capabilities provide efficient packet 
forwarding. 

```


                    ┌───────────┐
                    │ 🌐 Internet│
                    └───────────┘
                          ▲
                          │ (Internet Connection)
                          |
                    ┌───────────┐
                    │ SP Modem/  │
                    │   Router   │
                    │(Home Network)│
                    └───────────┘
                          ▲
                          │ (Ethernet - LAN Cable)
                          │ (Router's Internet Access)
                          |
┌─────────────────────────────────────────────────────────────┐
│ Commercial Router with OpenWRT Firmware                     │
├─────────────────────────────────────────────────────────────┤
│ WiFi Access Point (hostapd)                                 │
│  - Receives WiFi connections                                │
│  - SSID: Parental_WiFi                                      │
├─────────────────────────────────────────────────────────────┤
│ Captive Portal (nodogsplash/coova-chilli)                   │
│  - Intercepts HTTP requests                                 │
│  - Redirects expired devices                                │
├─────────────────────────────────────────────────────────────┤
│ System Services                                             │
│  - iptables/nftables (Firewall)                             │
│  - dnsmasq (DHCP/DNS)                                       │
├─────────────────────────────────────────────────────────────┤
│ Open WRT Packages                                           │
│  - Web Dashboard (LuCI)                                     │
│  - Parental Control Packages                                │
│  - Time Tracking Service                                    │
│  - Device Management                                        │
├─────────────────────────────────────────────────────────────┤
│ Storage                                                     │
│  - Internal Flash (16-128MB)                                │
│  - USB Storage (Optional)                                   │
│  - Configuration Files                                      │
│  - Logs & Database                                          │
└─────────────────────────────────────────────────────────────┘
        ▲
        │ (WiFi Connection)
        │ (SSID: Parental_WiFi)
        │
┌───────────────────────────────┐
│ 📱 Child Devices              │
│    (WiFi Clients)             │
└───────────────────────────────┘
            Figure 2.2-3 System Architecture Diagram
```

When a child device connects, it gets a DHCP lease from the router's 
dnsmasq service and is automatically registered in a device database 
stored on the router's flash storage or attached USB device. A time 
tracking service continuously monitors active internet sessions and 
updates device session records. When a device's remaining time 
minutes reach zero, firewall rules automatically place that device's 
MAC address in a blocked chain and redirect all their traffic to the 
captive portal. After the child completes a quiz or video, background 
processes call the time granting service to update the time allocation 
and remove the block. 

```
┌───────────────────┐
│ Device Connects   │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│       DHCP        │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│   Auto Register   │
└───────────────────┘
         │           ┌───────────────────┐
         ├──────────>│  Devices Table    │
         │           └───────────────────┘
         ▼
┌───────────────────┐
│ Block + Redirect  │
│     to Portal     │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Portal: Quiz or Vid│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Validate & Grant Tm│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Internet Access Res│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Time Tracking Svc  │
│(Monitor & Deduct) │
└───────────────────┘
         │
         └───────────────────────────────────────────┐
                                                     │
                                                     ▼
                                             ┌───────────┐
                                             │ Time = 0? │
                                             └─────┬─────┘
                                                   │
                                           ┌───────┴───────┐
                                           │  NO      YES  │
                                           ▼              ▼
                                   ┌───────────┐  ┌───────────┐
                                   │ Continue  │  │  Block +  │
                                   │ Browsing  │  │  Redirect │
                                   └───────────┘  └───────────┘
                                         │         │
                                         └────┬────┘
                                              ▼
                                           ┌───────┐
                                           │ Loop  │
                                           └───────┘
            Figure 2.2-4 Operational Data Flow Diagram
```


**2.2.4 Illustrative Design              ___________________________**

The hardware design uses a commercial router compatible with OpenWRT 
firmware, typically featuring dual-band Wi-Fi 802.11ac or 802.11ax, 
multiple Ethernet ports, and sufficient flash storage for firmware and 
packages. The system uses the router's built-in network interfaces 
where the WAN port connects to the ISP router via a LAN cable 
for internet access, while the router's Wi-Fi radios create the 
dedicated child device network. For extended storage needs, a USB 
storage device can be attached to accommodate video files and detailed 
logs, though this adds complexity and may impact router performance. 

The system is powered by the router's standard AC adapter, and the 
router's builtin thermal management ensures stable operation during 
24/7 use. Child devices like smartphones, tablets, and laptops connect 
only through Wi-Fi to the router's access point, and all their 
internet traffic goes through the router so we can monitor and control 
it using the router's native routing and firewall capabilities. 

```

                    ┌───────────┐
                    │ 🌐 Internet│
                    │    (WAN)   │
                    └───────────┘
                          │
                          │ (Internet Connection)
                          ▼
                    ┌───────────┐
                    │ ISP Router│
                    │(Home Network)│
                    │  (Provides │
                    │ Internet  │
                    │  Access)  │
                    └───────────┘
                          │
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│ Commercial Router with OpenWRT                              │
│ (CPU: MIPS/ARM, RAM: 128-512MB, WiFi: 802.11ac/ax,          │
| USB: OPTIONAL DATA STORAGE, ETHERNET: MULTIPLE PORTS,       | 
| Flash: 16MB-128MB)                                          |
└─────────────────────────────────────────────────────────────┘
    ┌───────────────────┴───────────────────┐
    │                                       │
    │ (WiFi AP: SSID: Parental_Wifi)        │ (USB 3.0)
    ▼                                       ▼
┌───────────┐                             ┌───────────┐
│Child Device│                            │USB Storage│
│(Smartphone/│                            │  Device   │
│  Tablet)  │                             │ (Optional)│
└───────────┘                             ├───────────┤
    |                                     │ - Videos  │
    │                                     │ - Extended│
    │ (Power: 12V/2A AC Adapter)          │   Logs    │
    ▼                                     └───────────┘
┌───────────┐
│Power Supply│
│ (Included) │
└───────────┘
            Figure 2.2-5 System Architecture Diagram - Hardware




┌──────────────────┬──────────────────────────┬──────────────────────┐
│ Component        │ Specification            │ Purpose              │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ Commercial       │ OpenWRT-compatible,      │ Serves as the core   │
│ Router           │ 802.11ac/ax, Multiple    │ routing platform,    │
│                  │ Ethernet ports           │ running the operating│
│                  │                          │ system and all       │ 
│                  │                          │ associated services. │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ Ethernet Port    │ Gigabit Ethernet         │ Provides the WAN     │
│ (WAN)            │                          │ connection to the ISP│
│                  │                          │ router for internet  │
│                  │                          │ access.              │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ WiFi Interface   │ 802.11ac/802.11ax        │ Operates in Access   │
│                  │ (Dualband)               │ Point mode, creating │
│                  │                          │ the Parental WiFi    │
│                  │                          │ network.             │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ Internal Storage │ 16MB-128MB Flash         │ Used to store the    │
│                  │                          │ OpenWRT firmware,    │
│                  │                          │ various packages, and│
│                  │                          │ configuration files. │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ USB Storage      │ USB 3.0 interface        │ Offers extended      │
│                  │ (Optional)               │ storage capacity for │
│                  │                          │ videos and detailed  │
│                  │                          │ logs.                │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ Power Supply     │ 12V/2A AC Adapter        │ Ensures stable power │
│                  │                          │ delivery for 24/7    │
│                  │                          │ operation of the     │
│                  │                          │ system.              │
└──────────────────┴──────────────────────────┴──────────────────────┘
                Table 2.2-1 Component Specification Table
```
The network setup uses the router's multiple interfaces to create 
isolated network zones. The WAN interface gets its IP address 
automatically from the ISP router through DHCP, and this serves as the 
internet connection. We use Network Address Translation NAT with 
MASQUERADE rules to translate the child devices' private IP addresses 
to the router's public IP address, which lets them access the internet 
while keeping them on a separate network. The Wi-Fi interface wlan0 is 
configured as an access point with a static IP address of 192.168.1.1, 
and it broadcasts the network. When child devices connect to this WiFi 
network, they automatically get IP addresses in the 192.168.1.x range 
through DHCP via dnsmasq, and all their internet traffic goes through 
the router so we can monitor and control it. 

```


┌─────────────────────────────────────────────────────────────┐
│             Commercial Router with OpenWRT                  │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ eth0 (WAN)                                               │ │
│ │ IP: DHCP (from ISP Router)                               │ │
│ │ Purpose: Internet Access                                │ │
│ └─────────────────────────────────────────────────────────┘ │
│                             │                               │
│                             ▼                               │
│                     NAT (MASQUERADE)                        │
│                             │                               │
│                             ▼                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ wlan0 (LAN/AP)                                           │ │
│ │ IP: 192.168.1.1 (Static)                                │ │
│ │ SSID: Parental_WiFi                                       │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                             │
                             │ (WiFi Connection)
                             │ (DHCP via dnsmasq)
                             ▼
                     ┌───────────┐
                     │ 📱        │
                     │ Child     │
                     │ Devices   │
                     │ (WiFi     │
                     │ Clients)  │
                     └───────────┘
                     IP Range: 192.168.1.x (DHCP via dnsmasq)

            Figure 2.2-6 Network Interfaces Diagram
```


**2.2.5 Design Standards                 ___________________________**
IEEE 802.11 Wi-Fi and IEEE 802.3 Ethernet, we use the IEEE 802.11 
standards for Wi-Fi and IEEE 802.3 standards for Ethernet to ensure 
compatibility with consumer devices and home routers. The router's 
native support for these standards ensures optimal performance and 
compatibility. 


DHCP standard RFC 2131, DNS RFC 1034/1035, our system uses standard 
network protocols, including DHCP for automatic IP address assignment, 
and DNS for domain resolution. OpenWRT's dnsmasq package provides 
robust implementation of these protocols. 


W3C HTML5, CSS3, ECMAScript, and OpenWRT Package Standards, for the 
frontend code, we use W3C HTML5, CSS3, and ECMAScript standards to 
ensure cross browser compatibility. The router's web interface and 
custom packages follow OpenWRT's development standards and package 
management conventions. 


OWASP and CSRF, we implement security measures based on the OWASP Top 
Ten guidelines, including injection protection, CSRF protection, secure 
session management, and proper input validation. However, the router's 
limited processing power may constrain the complexity of security 
implementations compared to dedicated server platforms. 


ISO/IEC 25010, our system follows the principles of ISO/IEC 25010, 
which include usability easy to use by parents, reliability operates 
predictably, and maintainability organization and documentation of 
code. However, the technical complexity of firmware flashing and 
package management may limit usability for non-technical parents. 

**2.2.6 Design Constraints               ___________________________**
The router's limited CPU and memory resources constrain the complexity 
of applications and services that can run directly on the router. For 
this reason, we focus on lightweight packages and avoid 
resource-intensive operations like video transcoding or deep packet 
inspection. Video storage and processing may require external USB 
storage or network-attached storage, adding complexity. 


The firmware flashing process presents significant risks, including 
the possibility of permanently bricking the router if the installation 
fails or is interrupted. This risk is particularly high for ISP-issued 
PLDT routers, which often have locked bootloaders or limited 
compatibility with open-source firmware. Some households may need to 
purchase a compatible router specifically for this purpose, increasing 
the initial cost. 


The system requires technical expertise to flash the firmware, install 
packages, and configure the router properly. Non-technical parents may 
struggle with the initial setup and ongoing maintenance, requiring 
technical support or professional assistance. The learning curve for 
managing OpenWRT and its packages is steeper than user-friendly 
commercial solutions. 

Compatibility, not all router models support OpenWRT or similar 
firmware, and ISP-issued routers are often incompatible. This limits 
deployment options and may require purchasing a new router, increasing 
costs. Router manufacturers may void warranties when custom firmware is 
installed, leaving families without support if hardware issues arise. 


Budget, while the router itself costs ₱8,700-₱17,400, the risk of 
bricking during firmware installation may require purchasing 
replacement routers, potentially doubling or tripling the total cost. 
Additionally, if external storage is needed for videos and logs, USB 
storage devices add to the expense.




*2.3 Design 2: Cloud-Managed Parental Control Service   _____________*
**2.3.1 Design Description               ___________________________**
Our designed system uses a third-party Software-as-a-Service SaaS 
platform as the central hub for managing the household "Child Wi-Fi" 
network. In this approach, a local gateway device or software agent 
connects to the existing home network and tunnels all child device 
traffic to a cloud-based service for filtering, monitoring, and 
reporting. The cloud service creates virtual network policies that 
apply to child devices, which connect to the home's existing Wi-Fi 
network. The system utilizes the cloud provider's web-based dashboard 
for device management, content filtering, time-based access control, 
and comprehensive analytics. 


If a child's assigned internet time is depleted, the cloud service 
automatically blocks his or her device from accessing the internet 
through policy enforcement and redirects them to a captive portal 
interface hosted in the cloud. At this portal, the child can either 
take a quiz or watch an educational video that the parent has set up 
through the cloud dashboard. It is only after they have finished either 
activity successfully that the system will give more internet time and 
unblock their device through policy updates. 


Lastly, a web dashboard hosted in the cloud where parents will be able 
to manage devices, flag/block websites, create quizzes and videos, set 
up schedules, view logs, and see reports. The parents can access the 
dashboard from anywhere with internet connectivity, as it is available 
through the cloud provider's web interface. This enables parents to 
monitor and manage their children's internet use even when they are 
away from home without requiring VPN setup or port forwarding. Since 
everything runs in the cloud, this provides enterprise-grade features 
and automatic updates but requires constant internet connectivity and 
sends children's browsing data to external servers. 


**2.3.2 Hardware Design                  ___________________________**
Local Gateway Device, we require a minimal local gateway device that 
runs as software or an add-on within the existing ISP router. The 
gateway software or router add on is installed on the router itself, 
eliminating the need for separate hardware. The gateway device tunnels 
traffic to the cloud service and enforces policies received from the 
cloud. This software-based approach requires no additional hardware 
investment, as it utilizes the existing router's capabilities. 


Networking, the local gateway device runs within the router itself, 
utilizing the router's existing network interfaces. Child devices 
connect to the home's existing Wi-Fi network the same network used by 
parents, and the gateway software intercepts their traffic directly at 
the router level to route it through the cloud service for filtering 
and monitoring. The gateway maintains a constant connection to the 
cloud service to receive policy updates and send usage data. 


Storage, all data storage occurs in the cloud provider's 
infrastructure. Browsing logs, device information, quiz results, video 
completion records, and usage analytics are stored on remote servers. 
This eliminates the need for local storage but means all data is 
transmitted to and stored on external servers. Video files uploaded by 
parents are stored in cloud storage, requiring internet bandwidth for 
both upload and streaming to children's devices. 


Power and Cooling, since the local gateway device runs as software or 
an add-on within the existing router, it requires no additional power 
or cooling. The gateway software utilizes the router's existing power 
supply and thermal management, adding minimal processing overhead to 
the router's normal operation. 


Peripheral Support, the gateway device typically has minimal 
peripheral support, focusing solely on network connectivity and cloud 
communication. Advanced features like local status indicators or GPIO 
access are generally not available, as the system is designed 
to be managed entirely through the cloud dashboard. 

```
┌─────────────────────────────────────────────────────────────┐
│   Cloud-Based Parental Control Service (SaaS)                │
│                                                              │
│  - Virtual Firewall                                         │
│  - Content Filter                                           │
│  - Time Management                                          │
│  - Captive Portal                                           │
│  - Data Storage                                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (Internet Connection)
                            │ (Traffic tunnels from gateway)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router (Home Network)                │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ Local Gateway Device                                   │ │
│  │ (Software/Add-on in Router)                           │ │
│  │                                                        │ │
│  │ - Tunnels traffic to cloud                            │ │
│  │ - Enforces cloud policies                             │ │
│  └──────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi - Existing Home Network)
                            │ (Same network as parents)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Child Devices Only (WiFi Only)                 │
│         (Smartphones, Tablets, Laptops)                     │
└─────────────────────────────────────────────────────────────┘

                Figure 2.3-1 Hardware Components Flowchart
```

**2.3.3 Schematic Design                 ___________________________**
The network flow proceeds from the ISP Modem/Router through the home 
network to a Local Gateway Device, which tunnels all child device 
traffic to a Cloud-Based Parental Control Service. The cloud service 
functions as a Virtual Firewall, Content Filter, Time Management 
System, and Captive Portal Host. Child devices connect to the home's 
existing Wi-Fi network, and their traffic is intercepted by the 
gateway and routed through the cloud service before reaching the 
internet, enabling comprehensive monitoring, filtering, and time-based 
access control using cloud-based policy enforcement. 

```
                            ┌───────────────────────────┐
                            │       Cloud Service       │
                            │ - Virtual Firewall        │
                            │ - Content Filter          │
                            │ - Captive Portal          │
                            │ - Analytics & Reporting   │
                            └───────────────────────────┘
                                      |
                                      │ (Internet Connection)
                                      │ (Traffic tunnels from gateway)
                                      │
┌───────────────────┐                 │
│   🌐 Internet     │<───────────────┘
│      (WAN)        │
└───────────────────┘
          │
          │ (Internet Connection)
          ▼
┌─────────────────────────────────────────────────────────────┐
│                 ISP Modem/Router                            │
│                     (Home Network)                          │
│ - Local Gateway Device (Software/Add-on in Router)          │
│ - Intercepts child traffic                                  │
│ - Tunnels to cloud service                                  │
└─────────────────────────────────────────────────────────────┘
          │
          │ (WiFi - Existing Home Network)
          │ (Child Device Network)
          ▼
      ┌───────────┐
      │   📱      │
      │Child Device│
      │(Smartphone/│
      │  Tablet)  │
      └───────────┘
                Figure 2.3-2 Network Topology Diagram
```
The system manages network traffic through cloud-based policies that 
are pushed to the local gateway device. The gateway maintains a 
persistent connection to the cloud service, receiving policy updates 
and sending usage data in real-time. We use cloud-based NAT and 
firewall rules to isolate child traffic and apply filtering policies, 
while the cloud service maintains centralized control over all network 
policies and device management. 

```


┌─────────────────────────────────────────────────────────────┐
│      Cloud-Based Parental Control Service                   │
│                                                             │
│  - Virtual Firewall: Content Filter                         │
│  - Time Management, Captive Portal                          │
│  - Cloud Storage, Web Dashboard                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (Internet - Traffic tunnels)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router (Home Network)                │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Local Gateway Device                                  │  │
│  │ (Software/Add-on in Router)                           │  │
│  │                                                       │  │
│  │ - Traffic interception                                │  │
│  │ - Policy Reinforcement                                │  │
│  │ - Cloud Tunnel (Encrypted)                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi - Home Network)
                            │ (Same network as parents)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Child Devices (WiFi Clients)                   │
│              (Existing Home Networks)                       │
└─────────────────────────────────────────────────────────────┘
                Figure 2.3-3 System Architecture Diagram

When a child device connects to the home network, the gateway device 
identifies it and registers it with the cloud service. The cloud 
service's time tracking system continuously monitors active internet 
sessions and updates device session records stored in cloud databases. 
When a device's remaining time minutes reach zero, the cloud service 
pushes blocking policies to the gateway, which enforces the block and 
redirects all traffic to the cloud-hosted captive portal. After the 
child completes a quiz or video hosted in the cloud, the service 
updates the time allocation and pushes policy updates to remove the 
block. 
```
```
┌───────────────────┐
│ Device Connects   │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Gateway Identifiers│
└───────────────────┘
         │
         ├───────────────────┐
         │                   ▼
         │           ┌───────────────────┐
         │           │Register with Cloud│
         │           └───────────────────┘
         │
         ▼
┌───────────────────┐
│Cloud Policy Applied│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│ Block + Redirect  │
│     to Portal     │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Portal: Quiz or Vid│
│ (Cloud Hosted)    │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Validate & Grant Tm│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Policy Update      │
│Pushed to Gateway  │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Internet Access Res│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Cloud Time Tracking│
│Svc (Monitor &     │
│Deduct Time)       │
└───────────────────┘
         │
         └───────────────────────────────────────────┐
                                                     │
                                                     ▼
                                             ┌───────────┐
                                             │ Time = 0? │
                                             └─────┬─────┘
                                                   │
                                           ┌───────┴───────┐
                                           │  NO      YES  │
                                           ▼              ▼
                                   ┌───────────┐  ┌───────────┐
                                   │ Continue  │  │Push Block │
                                   │ Browsing  │  │  Policy   │
                                   └───────────┘  └───────────┘
                                         │         │
                                         └────┬────┘
                                              ▼
                                           ┌───────┐
                                           │ Loop  │
                                           └───────┘
            Figure 2.3-4 Operational Data Flow Diagram               
```

**2.3.4 Illustrative Design              ___________________________**
The hardware design uses a minimal local gateway device that connects 
to the home network, typically a small appliance provided by the cloud 
service or software running on an existing router or computer. The 
system uses the home's existing network infrastructure where child 
devices connect to the same Wi-Fi network as parents, and the gateway 
device intercepts and routes their traffic through the cloud service. 
For data storage, all information is stored in the cloud provider's 
infrastructure, including browsing logs, device profiles, quiz 
content, educational videos, and usage analytics. 

The system is powered by the gateway device's standard power supply if 
hardwarebased or runs on existing hardware if software-based, 
requiring minimal additional power consumption. Child devices like 
smartphones, tablets, and laptops connect to the home's existing WiFi 
network, and all their internet traffic is intercepted by the gateway 
and routed through the cloud service so we can monitor and control it 
using cloud-based policies and filtering. 

```
┌─────────────────────────────────────────────────────────┐
│                 Cloud Infrastructure                    │
│  - Virtual Servers                                      │
│  - Cloud Storage                                        │
│  - Content Delivery Network                             │
│  - Database Servers                                     │
└─────────────────────────────────────────────────────────┘
         |
         │ (Internet Connection)
         │ (Traffic tunnels from gateway)
         ▼
┌────────┴───────────┐
│   🌐 Internet(WAN) │
└────────────────────┘
         │
         │ (Internet Connection)
         ▼
┌─────────────────────────────────────────────────────────┐
│              ISP Router (Home Network)                  │
│              - Provides Internet Access                 │
│  ┌─────────────────────────────────────────────────────┐│
│  │           Local Gateway Device                      ││
│  │           (Software/Add-on in Router)               ││
│  │           - Minimal Processing Power                ││
│  │           - Network Connectivity                    ││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
         │
         │ (WiFi - Home Network)
         │ (Same network as parents)
         ▼
┌─────────────────────────────────────────────────────────┐
│ 📱 Child Devices (WiFi Only)                            │
│  - Smartphones                                          │
│  - Tablets                                              │
│  - Laptops                                              │
└─────────────────────────────────────────────────────────┘
        Figure 2.3-5 System Architecture Diagram - Hardware


┌───────────────┬──────────────────────────┬─────────────────────────┐
│ Component     │ Specification            │ Purpose                 │
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Local Gateway │ Software/add-on running  │ Intercepts child        │
│ Device        │ within the router, with  │ traffic and tunnels it  │
│               │ minimal processing      │ to the cloud service.    │
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Network       │ Ethernet or WiFi         │ Connects to the home    │
│ Interface     │                          │ network and intercepts  │
│               │                          │ child device traffic.   │
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Cloud Service │ SaaS platform, virtual   │ Provides firewall,      │
│               │ infrastructure           │ filtering, time         │
│               │                          │ management, and storage.│
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Cloud Storage │ Remote database and      │ Stores all browsing     │
│               │ file storage             │ logs, device data,      │
│               │                          │ quizzes, videos, and    │
│               │                          │ analytics.              │
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Web Dashboard │ Cloud-hosted web         │ Serves as the parent    │
│               │ application              │ management interface,   │
│               │                          │ accessible from         │
│               │                          │ anywhere.               │
├───────────────┼──────────────────────────┼─────────────────────────┤
│ Router Power  │ Standard router adapter  │ Powers the router       │
│ Supply        │                          │ containing the gateway  │
│               │                          │ software.               │
└───────────────┴──────────────────────────┴─────────────────────────┘
        Table 2.3-1 Component Specification Table

```
The network setup uses the home's existing network infrastructure with 
the gateway device acting as an intermediary. Child devices connect to 
the home Wi-Fi network and receive IP addresses through the existing 
router's DHCP service. The gateway device intercepts traffic from 
child devices and tunnels it to the cloud service, which applies 
filtering policies, time restrictions, and content blocking before 
allowing traffic to proceed to the internet. The cloud service 
maintains centralized control, pushing policy updates to the gateway 
in real-time. When child devices connect, their traffic is
automatically routed through the cloud service, and all monitoring and 
control happens remotely through the cloud provider's infrastructure. 


```
Figure 2.3-6 Network Interfaces Diagram

┌─────────────────────────────────────────────────────────────┐
│              Cloud Service Network                          │
│                                                             │
│  - Virtual Firewall Rules                                   │
│  - Content Filtering Policies                               │
│  - Time Management Policies                                 │
│  - All traffic processed in cloud                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (Internet - Traffic Tunnels)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router (Home Network)                │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Router Network Interface                              │  │
│  │  - WAN: Internet Connection                           │  │
│  │  - LAN: Home Network (DHCP Server)                    │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Local Gateway Device                                  │  │
│  │ (Software/Add-on in Router)                           │  │
│  │                                                       │  │
│  │  ┌───────────────────────────────────────────────┐    │  │
│  │  │ Traffic Interception                          │    │  │
│  │  │  - Identifies child devices                   │    │  │
│  │  └───────────────────────────────────────────────┘    │  │
│  │                                                       │  │
│  │  ┌───────────────────────────────────────────────┐    │  │
│  │  │ Cloud Tunnel Interface                        │    │  │
│  │  │  - Encrypted tunnel to cloud                  │    │  │
│  │  │  - Sends child device traffic                 │    │  │
│  │  │  - Receives Policy updates                    │    │  │
│  │  └───────────────────────────────────────────────┘    │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi - Home Network)
                            │ (DHCP from router)
                            ▼
                    ┌───────────┐
                    │ 📱 Child  │
                    │  Devices  │
                    │(WiFi      │
                    │ Clients)  │
                    └───────────┘
                    IP Range: Router's DHCP range
                    (Same network as parent devices)
```

**2.3.5 Design Standards                 ___________________________**
IEEE 802.11 Wi-Fi and IEEE 802.3 Ethernet, we use the IEEE 802.11 
standards for Wi-Fi and IEEE 802.3 standards for Ethernet to ensure 
compatibility with consumer devices and home routers. The gateway 
device must support these standards to integrate with existing home 
networks. 


DHCP standard RFC 2131, DNS RFC 1034/1035, our system uses standard 
network protocols, including DHCP for automatic IP address assignment, 
and DNS for domain resolution. The cloud service may implement custom 
DNS filtering by intercepting and redirecting DNS queries. 


W3C HTML5, CSS3, ECMAScript, and Cloud API Standards, for the frontend 
code, we use W3C HTML5, CSS3, and ECMAScript standards to ensure 
cross-browser compatibility. The cloud dashboard follows RESTful API 
standards and modern web application practices for cloud-based 
services.


OWASP and CSRF, we rely on the cloud provider to implement security 
measures based on the OWASP Top Ten guidelines, including injection 
protection, CSRF protection, secure session management, and proper 
input validation. However, parents have limited control over the 
security implementation, as it is managed entirely by the cloud 
provider. 



ISO/IEC 25010, our system follows the principles of ISO/IEC 25010, 
which include usability easy to use by parents, reliability operates 
predictably, and maintainability organization and documentation of 
code. The cloud provider handles system maintenance and updates, 
reducing the burden on parents but also reducing their control over 
the system. 


Data Privacy Regulations, the system must comply with data protection 
regulations such as GDPR, as children's browsing data is stored on 
external servers. Parents must trust the cloud provider to handle 
sensitive data appropriately and comply with privacy laws. 


**2.3.6 Design Constraints               ___________________________**
filtering, policy enforcement, and device management occurs in the 
cloud. If the internet connection is lost, the gateway device may 
block all traffic or allow unfiltered access, depending on the 
service's fail-safe configuration. This creates a critical dependency 
on internet availability for the parental control system to operate.


The cloud service sends all children's browsing data, device 
information, and usage logs to external servers, raising significant 
privacy concerns. Parents must trust the cloud provider to protect 
sensitive data and comply with privacy regulations. Data breaches at 
the cloud provider could expose children's browsing habits and 
personal information, creating a privacy risk that local solutions 
avoid. 


Subscription Costs, the system requires ongoing monthly subscription 
fees, typically ₱580-₱1,740 per month, which accumulate to substantial 
costs over time. Over a 5-year period, subscription costs could total 
₱34,800-₱104,400, significantly exceeding the one-time cost of 
hardware-based alternatives. This recurring expense makes the system 
less accessible to families with limited budgets. 


Limited Offline Functionality, the system has very limited 
functionality when offline, as quizzes, videos, and the captive portal 
are hosted in the cloud. If internet connectivity is lost, children 
cannot access educational content or earn additional time, and 
parents cannot manage the system. This contrasts with local solutions 
that continue functioning during internet outages. 


Vendor Lock-in and Service Discontinuation, parents are dependent on 
the cloud provider's continued operation and service availability. If 
the provider increases prices, changes service terms, or discontinues 
the service, families may lose access to their configuration, 
historical data, and the system itself. Migrating to an alternative 
solution would require reconfiguring the entire system and potentially 
losing historical data. 


Limited Customization, while cloud services offer enterprise-grade 
features, they may not support highly customized requirements such as 
specific quiz formats, custom video validation methods, or unique 
parental control workflows. The system's capabilities are limited to 
what the cloud provider offers, with less flexibility than 
locally-hosted solutions. 


Integration Challenges, integrating custom features like 
parent-selected quizzes and videos may be more difficult with cloud 
services, as parents must work within the provider's predefined 
interfaces and APIs. Advanced customization may require additional 
development or may not be supported at all, limiting the system's 
ability to meet specific family needs.


*2.4 Design 3: Integrated Raspberry Pi Access Point Design  _________*
**2.4.1 Design Description               ___________________________**
Our designed system uses the Raspberry Pi 4B as the central hub for 
the household "Child Wi-Fi" network. In this regard, the Pi connects 
to the existing home network via a LAN cable and acts as an access 
point for the child device's Wi-Fi network. It creates its independent 
Wi-Fi network-with a different SSID-which the child devices connect to 
and runs our Laravel 12-based parental control system directly on the 
Pi itself. The system utilizes Blade Templates with Alpine.js for the 
frontend interface, MariaDB for data storage, and NoDogSplash as the 
captive portal solution. Network control is achieved through iptables 
or nftables firewall rules for device-level blocking and DNS-based 
blocking via dnsmasq for domain and application-level control, 
enabling comprehensive blocking of websites and mobile applications. 


If a child's assigned internet time is depleted, the system 
automatically blocks his or her device from accessing the internet and 
redirects them to a captive portal interface managed by NoDogSplash. 
At this portal, the child can either take a quiz or watch an 
educational video that the parent has set up. It is only after they 
have finished either activity  successfully that the system will give 
more internet time and unblock their device. 


Lastly, a web dashboard where parents will be able to manage devices, 
flag/block websites, create quizzes and videos, set up schedules, view 
logs, and see reports. The parents can access the dashboard since it is 
available over the local network by default and remote access can be 
configured via VPN connections or cloud tunneling services if you 
want, or port forwarding if appropriate security measures are taken, 
thus enabling parents to monitor and manage their children's internet 
use even when they are away from home. Since everything runs locally on 
the Pi, this gives parents full control and privacy. 

**2.4.2 Hardware Design                  ___________________________**
Core Compute, we choose the Raspberry Pi 4B with 4 GB RAM will run 
Raspberry Pi OS Lite 64-bit because of its affordability, good-enough 
processing power to run both a web server and network operations, and 
GPIOs for possible future enhancement of status LEDs. 


Networking, the Raspberry Pi 4B will use the dual-mode configuration 
which connects to the existing home network using a LAN cable for 
internet access, and its onboard 802.11ac Wi-Fi is configured as an 
access point to create the child device's network.  


Storage, we are using a Kingston A400 2.5" SATA Internal SSD with a 
capacity of 480GB. This storage will be partitioned to accommodate the 
operating system, Laravel application, MariaDB database, video files, 
and log files. We have chosen the SSD over using a microSD card since 
it is highly reliable for continuous write operations for example, 
logging and database transactions, and better performance can be 
obtained for video streaming.


Power and Cooling, the Raspberry Pi 4B will be powered via a 5V/3A 
USB-C power supply. We choose fitted low-profile heat sinks to ensure 
temperatures remain stable in continual operation, as the Pi will be 
running firewall operations and video streaming 24/7.  


Peripheral Support, HDMI and USB ports remain available for local 
debugging and maintenance. The GPIO header is reserved for potential 
future features, such as status LEDs to show which devices are active 
or when alerts occur. 

```
┌─────────────────────────────────────────────────────────────┐
│                 ISP Modem/Router                            │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (LAN Cable - Ethernet)
                            │ (Router Connection)
                            ▼
                ┌──────────────────┐
          ┌────>│ Raspberry Pi 4B  │<──────────┐
          |     └──────────────────┘           |
          |              |                     |
          │              │                     │
          |              ▼                     |
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│   SSD (Storage) │ │  WiFi AP (WiFi) │ │ Power (5V/3A    │
└─────────────────┘ └─────────────────┘ │    Supply)      │
                            │           └─────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│      Child Devices (WiFi Only)                              │
│      (Smartphones, Tablets, Laptops)                        │
└─────────────────────────────────────────────────────────────┘

            Figure 2.4-1 Hardware Components Flowchart
```

**2.4.3 Schematic Design                 ___________________________**
The network flow proceeds from the ISP Modem/Router through a LAN 
cable to the Raspberry Pi 4B, which simultaneously functions as a WiFi 
Access Point, Web Server Laravel 12 + Nginx + PHP-FPM, Firewall/Router 
iptables/nftables, and Captive Portal NoDogSplash. Child devices 
connect via Wi-Fi, ensuring all traffic is routed through the Pi 
first, enabling comprehensive monitoring, filtering, and time-based 
access control. 

```

┌───────────┐
│ 🌐 Internet│
└───────────┘
      │
      │ (Internet Connection)
      |
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router                               │
│                  (Home Network)                             │
└─────────────────────────────────────────────────────────────┘
      │
      │ (LAN Cable - Ethernet)
      │ (Pi's Internet Access)
      |
┌─────────────────────────────────────────────────────────────┐
│                    Raspberry Pi 4B                          │
│                                                             │
│  - Access Point (SSID: Parental_WiFi)                       │
│  - Firewall (iptables/nftables)                             │
│  - Captive Portal (NoDogSplash)                             │
│  - Web Application (Laravel)                                │
└─────────────────────────────────────────────────────────────┘
      │
      │ (WiFi - 802.11ac/ax)
      │ (Child Device Network)
      |
┌─────────────────────────────────────────────────────────────┐
│                    Child Device                             │
│              (Smartphone/Tablet/Laptop)                     │
└─────────────────────────────────────────────────────────────┘

            Figure 2.4-2 Network Topology Diagram
```

The Pi manages two network zones which are the WAN the uplink to the 
internet and LAN the child device’s. We use NAT Network Address 
Translation plus firewall rules to isolate child traffic while still 
allowing the Pi itself to access the internet for updates and 
the parent dashboard. 

```

┌───────────────────┐
│   Child Devices   │
└───────────────────┘
         │
         ▼
┌───────────────────┐   
│ Wi-Fi AP (hostapd)│
│ - Receives WiFi   │
│   connections     │
│ - SSID: Parental_ │
│   WiFi            │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│ NoDogSplash       │
│ (Captive Portal)  │
│ - Intercepts HTTP │
│   requests        │
│ - Redirects       │
│   expired devices │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│ System Services   │
│ - iptables        │
│   (Firewall)      │
│ - hostapd (WiFi AP)│
│ - dnsmasq         │
│   (DHCP/DNS)      │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│ Raspberry Pi 4B   │
│      System       │
└───────────────────┘
         │           ┌───────────────────┐
         ├──────────>│ Laravel           │
         │           │ Application       │
         │           └───────────────────┘
         │                     │
         │                     ▼
         │           ┌───────────────────┐
         │           │ MariaDB           │
         │           │ Database          │
         │           └───────────────────┘
         │
         ▼
┌───────────────────┐
│  Ethernet Port    │
│ - Connected to    │
│   ISP Router      │
│ - Pi's internet   │
│   access (WAN)    │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│ ISP Modem/Router  │
│  (Home Network)   │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│     Internet      │
└───────────────────┘

            Figure 2.4-3 System Data Flow Diagram       
```

When a child device connects, it gets a DHCP lease and is 
automatically registered in our device’s database table. The Time 
Tracking Service continuously monitors active internet sessions and 
updates device sessions records. When a device’s remaining time 
minutes reach zero, firewall rules automatically place that device’s 
MAC address in a blocked chain and redirect all their traffic to the 
captive portal. After the child completes a quiz or video, background 
jobs call the Time Granting Service to update the time allocation 
and remove the block. 

```

┌───────────────────┐
│ Device Connects   │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│       DHCP        │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│   Auto-Register   │
└───────────────────┘
         │           ┌───────────────────┐
         ├──────────>│  Devices Table    │
         │           └───────────────────┘
         ▼
┌──────────────────────────┐
│Time Tracking Svc         │
│(Background:              │
│TrackActiveSessions)      │
|- Monitor sessions        |
|- Update device_sessions  |
|- Deduct time             |
└──────────────────────────┘
     │
     |
     ▼
┌───────────┐
│ Time = 0? │
└─────┬─────┘
      │
  ┌───┴───────────────────┐
  │                       │
 NO                     YES
  │                       │
  ▼                       ▼
┌───────────────┐ ┌───────────────────┐
│ Continue      │ │ Block (iptables)  │
│ browsing      │ │ + Redirect        │
└───────────────┘ │ (NoDogSplash)     │
  │               └───────────────────┘
  |                        |
  ▼                        |
 Loop                      |
                           |
                           |
         ┌─────────────────┘
         │
         ▼
┌───────────────────┐
│Portal: Quiz or Vid│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Validate & Pass    │
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Time Granting Svc  │
│(Grant/Remove Block)│
└───────────────────┘
         │
         ▼
┌───────────────────┐
│Internet Access Res│
└───────────────────┘
         │
         ▼
┌────────────────────────┐
│Return to Time Tracking │
└────────────────────────┘
   
        Figure 2.4-4 Operational Data Flow Diagram
```

**2.4.4 Illustrative Design              ___________________________**
The hardware design uses a Raspberry Pi 4B with 4GB RAM running 
Raspberry Pi OS Lite 64-bit as the core computing platform. The system 
uses a dual-mode network setup where the Ethernet interface connects 
to the ISP router via a LAN cable for internet access, while the 
onboard 802.11ac WiFi interface creates the dedicated child device 
network. For storage, we use a Kingston A400 480GB SATA SSD connected 
through a USB-to-SATA adapter, which provides better reliability and 
performance for continuous database and video operations compared to 
microSD cards. 

The system is powered by a 5V/3A USB-C power supply, and we add fan 
and low profile heat sinks to keep temperatures stable during 24/7 
operation. Child devices like smartphones, tablets, and laptops 
connect only through WiFi to the access point, and all their internet 
traffic goes through the Raspberry Pi so we can monitor and control 
it. 

```


┌───────────────────────────────┐
│   Child Devices (WiFi Only)    │
│  - Smartphones                 │
│  - Tablets                     │
│  - Laptops                     │
└───────────────────────────────┘
         ▲
         │ (WiFi AP - 802.11ac)
         │ (SSID: Parental_WiFi)
         │
         |
         |          ┌───────────────┐
         |           │ 🌐 Internet  │
         |           │    (WAN)      │
         |           └───────────────┘
         |                   │
         |                   │ (Internet Connection)
         |                   ▼
         |           ┌──────────────┐
         |           │ ISP Router   │
         |           │(Home Network)│
         |           └──────────────┘
         |                   │
         |                   │ (Ethernet Cable - WAN)
         |                   ▼
┌─────────────────────────────────────────────────────────────┐
│              Raspberry Pi 4B (4GB RAM)                      │
│                                                             │
│  - Raspberry Pi OS Lite (64-bit)                            │
│  - CPU: Broadcom BCM2711                                    │
│  - RAM: 4GB LPDDR4                                           │
│  - Network: Dual-mode                                       │
│    * Ethernet (eth0): WAN                                   │
│    * WiFi (wlan0): AP (802.11ac)                            │
│  - Power: USB-C (5V/3A)                                     │
│  - Heat Sinks with Fan                                      │
└─────────────────────────────────────────────────────────────┘
    │                                   │
    │ (USB-C Power)                     │ (USB 3.0 Port)
    │                                   │
    ▼                                   ▼
┌────────────┐                   ┌───────────────────┐
│   Power    │                   │ USB-to-SATA       │
│  Supply    │                   │    Adapter        │
│(5V/3A USB-C│                   └───────────────────┘
│Surge Prot) │                             │
└────────────┘                             │
                                           ▼
                                  ┌───────────────────┐
                                  │ Kingston A400 SSD │
                                  │     (480GB)       │
                                  │                   │
                                  │ Storage for:      │
                                  │ - OS              │
                                  │ - Application     │
                                  │ - Database        │
                                  │ - Videos          │
                                  │ - Logs            │
                                  └───────────────────┘

            Figure 2.4-5 System Architecture Diagram




┌──────────────────┬──────────────────────────┬──────────────────────┐
│ Component        │ Specification            │ Purpose              │
├──────────────────┼──────────────────────────┼──────────────────────┤
│ Raspberry Pi 4B │ 4GB RAM, BCM2711 CPU     │ Core computing        │
│                 │                          │ platform, runs OS and │
│                 │                          │ all services          │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ Ethernet Port   │ Gigabit Ethernet eth0    │ WAN connection to ISP │
│                 │                          │ router for internet   │
│                 │                          │ access                │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ WiFi Interface  │ 802.11ac wlan0           │ Access Point mode,    │
│                 │                          │ creates Parental WiFi │
│                 │                          │ network               │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ SSD             │ Kingston A400 480GB SATA │ External storage via  │
│                 │                          │ USB-to-SATA adapter   │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ Power Supply    │ 5V/3A USB-C             │ Provides stable power  │
│                 │                          │ for 24/7 operation    │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ Heat Sinks      │ Aluminum Heatsink Set    │ Thermal management for│
│                 │ with Adhesive            │ continuous operation  │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ DC Fan          │ 5V DC fan                │ Active cooling to     │
│                 │                          │ maintain stable       │
│                 │                          │ temperatures during   │
│                 │                          │ continuous operation  │
├─────────────────┼──────────────────────────┼───────────────────────┤
│ USB-to-SATA     │ USB 3.0 interface        │ Connects SSD to       │
│ Adapter         │                          │ Raspberry Pi          │
└─────────────────┴──────────────────────────┴───────────────────────┘

                Table 2.4-1 Component Specification Table
```

The network setup uses two separate interfaces on the Raspberry Pi to
create isolated network zones. The Ethernet interface eth0 gets its IP 
address automatically from the ISP router through DHCP, and this 
serves as the WAN connection for internet access. We use Network 
Address Translation NAT with MASQUERADE rules to translate the 
child devices' private IP addresses to the Pi's public IP address,
which lets them access the internet while keeping them on a separate 
network. The WiFi interface wlan0 is set up as an access point with a 
static IP address of 192.168.4.1, and it broadcasts the network. When 
child devices connect to this WiFi network, they automatically get IP 
addresses in the 192.168.4.x range through DHCP, and all their 
internet traffic goes through the Raspberry Pi so we can monitor and 
control it. 

```


┌─────────────────────────────────────────────────────────────┐
│                    Raspberry Pi 4B                          │
│                                                             │
│  ┌──────────────────┐         ┌─────────────────────────┐   │
│  │ eth0             │         │ wlan0 (LAN/AP)          │   │
│  │ (WAN)            │         │                         │   │
│  │ IP: DHCP         │         │ IP: 192.168.4.1         │   │
│  │                  │         │ SSID: Parental_WiFi     │   │
│  └──────────────────┘         └─────────────────────────┘   │
│         │                              │                    │
│         │  NAT (MASQ)                  │                    │
│         └──────────────────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (WiFi Connection)
                            │ (DHCP via dnsmasq)
                            ▼
                    ┌───────────┐
                    │ 📱 Child  │
                    │  Devices  │
                    │(WiFi      │
                    │ Clients)  │
                    └───────────┘
                    IP Range: 192.168.4.x
                    (DHCP via dnsmasq)

            Figure 2.4-6 Network Interfaces Diagram
```
internet while keeping them on a separate network. The WiFi interface 
wlan0 is set up as an access point with a static IP address of 
192.168.4.1, and it broadcasts the network. When child devices connect 
to this WiFi network, they automatically get IP addresses in the 192.
168.4.x range through DHCP, and all their internet traffic goes 
through the Raspberry Pi so we can monitor and control it.

**2.4.5 Design Standards                 ___________________________**
IEEE 802.11 Wi-Fi and IEEE 802.3 Ethernet, we use the IEEE 802.11 
standards for Wi-Fi and IEEE 802.3 standards for Ethernet to ensure 
compatibility with consumer devices and home routers. 


DHCP standard RFC 2131, DNS RFC 1034/1035, our system uses standard 
network protocols, including DHCP for automatic IP address assignment, 
and DNS for domain resolution. 


W3C HTML5, CSS3, ECMAScript, and PSR-12 PHP, for the frontend code, we 
use W3C HTML5, CSS3, and ECMAScript standards to ensure cross-browser 
compatibility. The backend code follows PSR-12 PHP coding standards, 
and Laravel framework also adheres to PSR-12 for code style 
consistency.


OWASP and CSRF, we implement security measures based on the OWASP Top 
Ten guidelines, including injection protection, CSRF protection, 
secure session management, and proper input validation. Passwords are 
hashed using bcrypt, which Laravel provides by default. 


ISO/IEC 25010, our system follows the principles of ISO/IEC 25010, 
which include usability easy to use by parents, reliability operates 
predictably, and maintainability organization and documentation of 
code.


**2.4.6 Design Constraints               ___________________________**
The CPU and memory limitations on the Raspberry Pi limit how much we 
can do with packet inspection and video processing. For this reason, 
we focus on domain-level website blocking where we block entire 
domains rather than inspecting packet contents and pre-encoding videos 
rather than on-the-fly transcoding.


The captive portal system relies on the PLDT modems to allow Ethernet 
connections and proper routing. Some households with locked-down 
modems may require assistance from their ISP to configure this 
correctly.


The system requires stable power and adequate ventilation to run 
continuously. If the power goes out, devices will disconnect and need 
to reconnect when power returns. Critical services like Nginx, 
PHP-FPM, MariaDB, and network services are configured as systemd 
services with auto-restart enabled, so they automatically recover after 
power restoration.


The dashboard was designed to be simple enough for the average, 
non-techie parent. Technical terms are avoided in configuration 
Alternatives, advanced Alternatives remain only on admin-level areas of 
the site.


Budget, because many families cannot afford to pay high costs and 
because our research budget was limited, we only used open-source tools 
and commonly available hardware. This means we cannot use expensive 
commercial solutions but also make this system more approachable.



*2.5 Software Design     ____________________________________________*
**2.5.1 Design Description               ___________________________**
The software side of the system is built on Laravel 12, which provides 
a ModelView-Controller MVC structure along with queued jobs and custom 
services. The complete Laravel application runs directly on the 
Raspberry Pi, using Nginx as the web server, with PHP-FPM handling PHP 
processing


The controllers handle various aspects of the system including device 
management, quiz and video creation, reporting, and real-time 
notifications. The models represent the main data entities, including 
Device, Device Session, Quiz, Video, Browsing Log, Video Word Display, 
Video Completion, and Dictionary Word. These models, therefore, make 
it easy to work with database records and maintain many different 
pieces of data related to one another.


For the user interface, we use blade templates, a templating engine in 
Laravel that allows us to quickly create responsive pages. We added 
Alpine.js for lightweight interactivity and Tailwind CSS for styling. 
It keeps the frontend fast and doesn't require the use of heavy 
JavaScript frameworks.


Background workers, such as Laravel jobs, perform resource-intensive 
background tasks like tracking active sessions, sending alerts, report 
creation, and parsing network logs. These run in the background, so 
they don't slow down the web interface.


Laravel serves as the central manager that sends instructions to the 
operating system to manage the network through a secure, layered 
architecture. The system uses a three-tier approach, the NetworkService 
provides high-level network operations blocking, unblocking, 
whitelisting devices, querying connected devices, and monitoring 
traffic, the NoDogSplashService provides high-level captive portal 
operations redirecting devices to portal, allowing devices through, and 
checking redirect status, and the ScriptExecutor service acts as a 
security wrapper that validates, sanitizes, and executes shell scripts 
with proper security checks.


Finally, Bash scripts in the scripts/ directory execute iptables 
commands and NoDogSplash control commands ndsctl to modify firewall 
rules and manage device authentication states. Rather than directly 
controlling hardware, Laravel triggers system level operations through 
the mechanisms shell scripts for network control block_device.sh, 
unblock_device.sh, whitelist_device.sh, get_connected_devices.sh, 
monitor_traffic.sh, shell scripts for captive portal control 
redirect_device_portal.sh, allow_device_through.sh, 
check_device_redirected.sh, Python helper scripts for complex 
operations, system service restarts for managing services like 
NoDogSplash and network services, and iptables/nftables rules for 
firewall and routing configuration.


All script executions are carefully sanitized, whitelisted, and logged 
for security auditing. The ScriptExecutor service ensures that only 
approved scripts can be executed, validates script paths to prevent 
directory traversal attacks, escapes all arguments to prevent 
command injections, and executes scripts with sudo privileges 
configured through the sudoers file. The system is designed to use 
Laravel Broadcasting with WebSockets to provide real-time updates. This 
allows the parent dashboard to receive instant notifications 
on the occurrence of certain events, like when a child's time expires, 
when they try to access a blocked website, or when a flagged website is 
visited.



**2.5.2 Hardware Design                  ___________________________**
```


                    ┌───────────────────────────┐
                    │  Raspberry Pi 4B System   │
                    └───────────────────────────┘
                              │
                              ▼
          ┌───────────────────────────────────────────────┐
          │                 Web Server Layer              │
          │                 - Nginx                       │
          │                 - PHP-FPM (PHP 8.x)           │
          ├───────────────────────────────────────────────┤
          │             Laravel 12 Application            │
          │             - Controllers, Models, Views      │
          │               (Blade + Alpine.js)             │
          │             - Services, Background,           │
          │               Job, Queues                     │
          ├───────────────────────────────────────────────┤
          │                 Database Layer                │
          │                 - MariaDB (Database)          │
          ├───────────────────────────────────────────────┤
          │             System Integration Layer          │
          │             - Shell Scripts (iptables,        │
          │               hostapd, dnsmasq)               │
          │             - Python, Helper, Scripts         │
          ├───────────────────────────────────────────────┤
          │             Real-time Communication           │
          │             - Laravel Broadcasting            │
          │               + WebSockets                    │
          └───────────────────────────────────────────────┘

          Figure 2.5-1 Software Component Overview
```
Network Control Architecture, the network control system uses a 
three-tier architecture for secure and reliable device management. The 
NetworkService class provides high-level methods such as blockDevice, 
unblockDevice, whitelistDevice, getConnectedDevices, getTrafficStats, 
and isDeviceBlocked. These methods validate devices, update the 
database when necessary, and log errors.  


The ScriptExecutor service acts as a secure intermediary that validates 
script names against a whitelist, checks script paths to prevent 
directory traversal attacks, sanitizes all arguments using 
escapeshellarg, and executes scripts with sudo privileges. Actual 
network control is performed by Bash scripts block_device.sh, 
unblock_device.sh, whitelist_device.sh, get_connected_devices.sh, and 
monitor_traffic.sh that execute iptables commands to modify firewall 
rules on the INPUT and FORWARD chains based on MAC addresses. This 
layered approach ensures security, reliability, and maintainability. 

Command Execution Layer, we created service classes that safely execute 
shell commands and Python helper scripts to manage WiFi services, 
configure firewall rules, and control the captive portal. These 
services act as a secure layer between web controllers and system 
command execution, preventing unauthorized access.  


The ScriptExecutor service implements multiple security measures such 
as whitelist validation only approved scripts can be executed, path 
validation prevents directory traversal attacks, argument sanitization 
prevents command injection, and comprehensive logging all executions 
are logged for audit trails. Python scripts handle complex operations 
that are easier to implement in Python than in shell scripts. 


Process Monitoring, Laravel background jobs monitor device connections 
and track active sessions to determine which devices are connected and 
using the internet. The MonitorDeviceConnections job queries the ARP 
table to get currently connected devices, while the TrackActiveSessions 
job reads active session records from the database to calculate time 
usage. By correlating MAC addresses with active sessions, we can 
accurately track how much time each device has spent online and deduct 
it from their allocation.  


Media Handling, parents can upload educational videos. The system 
validates the uploaded files MP4, WebM, OGG formats, up to 512MB, 
stores them in storage/app/public/videos, and generates 
streaming-ready links. This is effectively carried out by Laravel's 
filesystem features, optimized for the Pi's storage capabilities. 

```

┌─────────────────────────────────────────────────────────┐
│                 Laravel Application                     │
│           (Controllers, Background Jobs, Services)      │
└─────────────────────────────────────────────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────────┐   ┌───────────────────┐
│   NetworkService    │   │ NoDogSplashService│
│ - blockDevice()     │   │ - redirectDevice()│
│ - unblockDevice()   │   │ - allowDevice()   │
│ - whitelistDevice() │   │ - checkRedirect() │
│ - getConnected()    │   └───────────────────┘
│ - getTrafficStats() │         │
└─────────────────────┘         │
         │                      │
         ▼                      ▼
┌─────────────────────────────────────────────────────────┐
│                   ScriptExecutor                        │
│  Security: Whitelist, Path Validation, Arg              │
│            Sanitization, Sudo Execution, Audit Logging  │
└─────────────────────────────────────────────────────────┘
         │                       │
         ▼                       ▼
┌───────────────────────┐   ┌──────────────────────┐
│  Network Scripts      │   │ NoDogSplash          │
│ - block_device.sh     │   │ Service (Scripts)    │
│ - unblock_device.sh   │   │ - redirect_portal.sh │
│ - whitelist_device.sh │   │ - allow_through.s    │
│ - get_connected_dev.sh│   │ - check_redirected.sh│
│ - monitor_traffic.sh  │   │                      │
└───────────────────────┘   │                      │
         │                  └──────────────────────┘
         │                          │
         ▼                          ▼
┌─────────────────────────────────────────────────────────┐
│                   System Services                       │
│  - iptables (Firewall)                                  │
│  - NoDogSplash (Captive Portal)                         │
│  - hostapd (WIFI AP)                                    │
│  - dnsmasq (DHCP/DNS)                                   │
│  - System Logs, Python Scripts                          │
└─────────────────────────────────────────────────────────┘

        Figure 2.5-2 Hardware Interaction Architecture
```

**2.5.3 Schematic Design                 ___________________________**
Presentation Layer, this layer is what users see and interact with. 
The Blade views and UI components are in resources/views, including 
the parent dashboard, captive portal pages, and report visualizations. 


Application Layer, this contains business logic. Controllers under 
app/Http/Controllers handle requests, including DeviceController, 
QuizController, VideoController, PortalController, 
BlockedWebsiteController, and others. Middleware enforces role-based 
access parents versus admins. Service classes, including 
TimeTrackingService, TimeGrantingService, VideoWordService, 
NetworkService, NoDogSplashService, and ScriptExecutor, contain 
reusable logic. 


Data Layer, eloquent models Laravel's ORM interact with MariaDB 
database tables defined in database/migrations. Relationships are 
configured so that, for example, a User can have many Device records, a 
Device can have many DeviceSession records, a Video can have many 
VideoCompletion records, and each VideoCompletion can have many 
VideoWordDisplay records. These relationships maintain data integrity 
automatically. 


Automation Layer, laravel Queues using the database driver, which 
works well on the Pi schedule recurring background jobs. These include 
TrackActiveSessions which monitors and deducts time, 
CheckTimeExpiration which detects when time runs out and triggers the 
portal redirect, MonitorDeviceConnections which tracks connected 
devices, EnforceSchedules which enforces time-based access rules, 
ParseNetworkLogs which parses network traffic logs, notification 
dispatch, report generation daily, weekly, and monthly, and log cleanup 


Network Control Layer, this layer implements the network control 
system architecture, which consists of various components working 
together. The NetworkService provides high-level network operations 
such as blocking, unblocking, and whitelisting devices, 
querying connected devices, monitoring traffic, and handling database 
updates and error logging. The NoDogSplashService provides high-level 
captive portal operations that redirect devices to portal using ndsctl 
deauth, allow devices through using ndsctl auth, and check redirect 
status. It also integrates with the time tracking system.  


The ScriptExecutor service acts as a secure wrapper that validates 
scripts against a whitelist, checks paths to prevent directory 
traversal, sanitizes arguments to prevent command injection, and 
executes scripts with sudo privileges configured through 
/etc/sudoers.d/parental-wifi-scripts.  


Shell scripts located in the scripts/ directory include network 
control scripts block_device.sh, unblock_device.sh, whitelist_device.
sh, get_connected_devices.sh, monitor_traffic.sh that execute iptables 
commands to modify firewall rules on the INPUT and FORWARD chains 
based on MAC addresses, and captive portal scripts 
redirect_device_portal.sh, allow_device_through.sh
check_device_redirected.sh that execute ndsctl commands to manage 
device authentication states.  


The scripts normalize MAC addresses, validate input, find tokens for 
devices from the NoDogSplash client list, and are idempotent safe to 
run multiple times. This multi-tier architecture ensures security, 
reliability, and proper separation of concerns. 


Captive Portal Layer, the NoDogSplash integration handles the automatic 
interception of HTTP requests when a device's time expires. The 
NoDogSplashService manages the authentication state of devices using 
ndsctl commands. When a device's time runs out, 
NoDogSplashService::redirectDeviceToPortal deauthenticates the device 
via ndsctl deauth and puts it in the Preauthenticated state.  


NoDogSplash then intercepts all HTTP requests from that device, 
redirecting them to the splash page splash.html?tok=TOKEN, which 
automatically redirects to the portal with the token parameter. The 
portal looks up the MAC address of the device based on the token. 
Once the quiz or video is completed, 
NoDogSplashService::allowDeviceThrough authenticates the device via 
ndsctl auth, restoring normal internet access. The system intercepts 
only HTTP requests, HTTPS requests are allowed to pass through, which 
is an acceptable limitation for this use case 


Real-time Communication Layer, the system is designed to support 
Laravel Broadcasting with WebSockets using Laravel Echo Server or 
Pusher for instant event broadcasting. When implemented, events such as 
device connections, blocked website attempts, flagged website visits, 
time limit reached, or time granted will be instantly updated in the 
parent dashboard without requiring a page refresh. 

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 7: Real-time Communication                            │
│ Laravel Broadcasting + WebSockets                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 6: Captive Portal                                     │
│ NoDogSplash Integration                                     │
│ - HTTP Request Interception                                 │
│ - Portal Redirect (ndsct1 deauth/auth)                      │
│ - Token-based MAC Address Lookup                            │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 5: Network Control                                    │
│ NetworkService + NoDogSplashService + ScriptExecutor        │
│ - Device Blocking/Unblocking (iptables)                     │
│ - Portal Redirect Management (NoDogSplash)                  │
│ - Domain/App-level Blocking (dnsmasq)                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: Automation                                         │
│ Laravel Queues (Database Driver)                            │
│ - TrackActiveSessions (every 5 min)                         │
│ - CheckTimeExpiration (every 2 min)                         │
│ - MonitorDeviceConnections (every 2 min)                    │
│ - EnforceSchedules (every 1 min)                            │
│ - ParseNetworkLogs (every 10 min)                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Data                                               │
│ Eloquent Models + MariaDB                                   │
│ - Device, User, Quiz, Video, DictionaryWord                 │
│ - DeviceSession, DeviceTimeGrant, VideoCompletion           │
│ - BlockedWebsite, FlaggedWebsite, AccessAttempt             │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Application                                        │
│ Controllers + Services + Middleware                         │
│ - DeviceController, QuizController, VideoController         │
│ - PortalController, BlockedWebsiteController                │
│ - TimeTrackingService, TimeGrantingService,                 │
│   VideoWordService                                          │
│ - Role-based Access Control (Parent/Admin)                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Presentation                                       │
│ Blade Views + Alpine.js + Tailwind CSS                      │
│ - Parent Dashboard (Device, Quiz, Video Management)         │
│ - Captive Portal Pages (Quiz/Video Selection, Completion)   │
│ - Report Visualizations                                     │
└─────────────────────────────────────────────────────────────┘
                Figure 2.5-3 Software Architecture Layers




┌─────────────────────────────────────────────────────────────┐
│              LARAVEL APPLICATION                           │
│  - CheckTimeExpiration Job                                  │
│  - TimeGrantingService                                      │
│  - DeviceController, BlockedWebsiteController              │
└─────────────────────────────────────────────────────────────┘
         │              │                    │
         ▼              ▼                    ▼
┌──────────────────┐ ┌───────────────────┐ ┌──────────────────┐
│ NetworkService   │ │ NoDogSplashService│ │ DomainBlocking   │
│ (PHP)            │ │                   │ │ Service          │
│ - block/unblock/ │ │ - redirectDevice  │ │ - block/unblock  │
│   whitelist      │ │   ToPortal        │ │   DomainForDevice│
│ - getConnected   │ │ - allowDevice     │ │ - updateDnsmasq  │
│ - getTrafficStats│ │   Through         │ │ - Blocklist      │
│ - isBlocked      │ │ - isDevice        │ │                  │
│                  │ │   Redirected      │ │                  │
└──────────────────┘ └───────────────────┘ └──────────────────┘
         │              │                    │
         └──────────────┴────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│         ScriptExecutor Service (Secure Wrapper)             │
│                                                             │
│  Security:                                                  │
│  - Whitelist Validation                                     │
│  - Path Validation                                          │
│  - Argument Sanitization                                    │
│  - Sudo Execution                                           │
│  - Audit Logging                                            │
└─────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Shell Scripts (Bash)                           │
│                                                             │
│  Network Control:                                           │
│  - MAC address validation                                   │
│  - iptables commands                                        │
│  - INPUT/FORWARD chains                                     │
│  - Idempotent operations                                    │
│                                                             │
│  Portal Control:                                            │
│  - Token lookup (ndsctl clients)                            │
│  - ndsctl deauth/auth                                       │
│  - Auth state management                                    │
│                                                             │
│  Domain Control:                                            │
│  - DNS blocking                                             │
│  - dnsmasq config update                                    │
│  - Wildcard domain support                                  │
└─────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              System Services                                │
│                                                             │
│  iptables (Firewall):                                       │
│  - INPUT Chain (DROP/ACCEPT rules)                          │
│  - FORWARD Chain (MAC-based blocking)                       │
│                                                             │
│  NoDogSplash:                                               │
│  - ndsctl auth/deauth                                       │
│  - HTTP interception                                        │
│  - Token management                                         │
│                                                             │
│  dnsmasq (DNS/DHCP):                                        │
│  - Domain blocking                                          │
│  - DHCP service                                             │
│                                                             │
│  hostapd (WIFI AP):                                         │
│  - SSID: Parental_WiFi                                      │
└─────────────────────────────────────────────────────────────┘
        Figure 2.5-4 Network Control System Architecture





┌──────────────────────────┐  ┌──────────────────────────┐  ┌──────────────────────────┐
│ CORE (Users, Devices,    │  │ QUIZ FLOW (Assignments   │  │ VIDEO FLOW (Assignments, │
│ Policies, Logs)          │  │ & Attempts)              │  │ Completion, Word         │
│                          │  │                          │  │ Validation)              │
├──────────────────────────┤  ├──────────────────────────┤  ├──────────────────────────┤
│ User (Parent)            │  │ User                     │  │ User (Parent)            │
│                          │  │                          │  │                          │
│     │                    │  │     │                    │  │     │                    │
│     │ 1:N                │  │     │ 1:N                │  │     │ 1:N                │
│     ▼                    │  │     ▼                    │  │     ▼                    │
│ Device                   │  │ Quiz                     │  │ Video                    │
│                          │  │                          │  │                          │
│     │                    │  │     │                    │  │     │                    │
│     │ 1:N                │  │     │ N:M                │  │     │ N:M                │
│     ▼                    │  │     ▼                    │  │     ▼                    │
│ DeviceSession            │  │ Device                   │  │ Device                   │
│ DeviceTimeGrant          │  │                          │  │                          │
│ BlockedWebsite           │  │     │                    │  │     │                    │
│ FlaggedWebsite           │  │     │ 1:N                │  │     │ 1:N                │
│ DeviceSchedule           │  │     ▼                    │  │     ▼                    │
│ BrowsingLog              │  │ QuizAttempt              │  │ VideoCompletion          │
│ AccessAttempt            │  │                          │  │                          │
│                          │  │                          │  │     │                    │
│                          │  │                          │  │     │ 1:N                │
│                          │  │                          │  │     ▼                    │
│                          │  │                          │  │ VideoWordDisplay         │
│                          │  │                          │  │                          │
│                          │  │                          │  │ User (Parent)            │
│                          │  │                          │  │                          │
│                          │  │                          │  │     │                    │
│                          │  │                          │  │     │ 1:N                │
│                          │  │                          │  │     ▼                    │
│                          │  │                          │  │ DictionaryWord           │
└──────────────────────────┘  └──────────────────────────┘  └──────────────────────────┘
            Figure 2.5-5 Entity Relationship Diagram
```

**2.5.4 Illustrative Design              ___________________________**
The parent dashboard consolidates all essential monitoring information 
in a single view using responsive blade templates with a card-based 
layout. The dashboard displays multiple panels showing different 
aspects of system monitoring and child activity. The time 
usage panel shows each child device with remaining time, allowing 
parents to monitor time limits across multiple devices at a glance. 
The quiz results panel displays all quiz attempts with child names and 
scores, helping parents track learning progress over time. The 
graphical representation panel displays internet usage trends with 
charts showing usage patterns, enabling parents to visualize 
consumption trends and make decisions about time allocation. Additional 
panels include indicators showing remaining quizzes and videos 
available for each device, helping parents track what educational 
activities children can complete to earn additional internet time. 


The portal landing page appears when a child's allocated internet time 
expires and the captive portal activates through NoDogSplash 
integration. The page displays the child's name and remaining internet 
time, along with two Alternatives to earn additional internet time 
like completing a quiz or watching an educational video. The interface 
presents these Alternatives as clear buttons, helping children 
understand their choices quickly. The interface uses a clean design 
that encourages participation and makes the learning process 
engaging. This landing page demonstrates the core captive portal 
functionality where HTTP requests are intercepted and redirected, 
serving as the entry point for the learning based access extension 
system. 


The quiz interface displays questions one at a time with a timer, 
ensuring children actively engage with the learning material within a 
set timeframe. The interface includes question navigation controls, 
answer validation, and progress tracking that shows which question the 
child is currently answering and how many remain. The design focuses 
attention on the current question, preventing distractions and helping 
children concentrate on providing accurate answers. After the child 
completes all questions, the system validates the answers and 
calculates the score based on the passing criteria set by the parent. 
If the quiz is passed, the system grants the configured time reward 
and redirects to the success page, if failed, the child can retry the 
quiz or choose to watch an educational video instead. 


The video player interface displays educational videos with disabled 
fast-forward and seeking controls, ensuring children watch videos in 
chronological order without skipping content. Dictionary words appear 
at random intervals during video playback, each displayed for a few 
seconds before disappearing, requiring children to pay attention 
throughout the entire video. The system tracks which words were shown 
and at their exact timestamps, storing this information for validation 
after the video completes. When the video reaches the end, the player 
shows a form asking the child to input all dictionary words that were 
displayed during playback, and the system validates the input against 
the words that were actually shown. If all words are entered 
correctly, the system grants the configured time reward and redirects 
to the success page, if incorrect, the child must watch the entire 
video again from the beginning with new random words displayed at 
different intervals. 


The blocked websites page displays all blocked sites per device in a 
list format so parents can manage content filtering for their 
children. The system supports two blocking types, the domain-level 
blocking prevents access to entire domains including all subdomains, 
and blocking websites like facebook.com and all its subdomains such as 
m.facebook.com and api.facebook.com. App-level blocking automatically 
blocks mobile applications along with all their related domains, 
providing comprehensive content filtering for apps that use multiple 
domains for functionality. The system uses DNS-based blocking via 
dnsmasq, where blocked domains are redirected to localhost to prevent 
network requests, but cached content may still display on devices. 


**2.5.5 Design Standards                 ___________________________**
PSR-12 Style Guidelines, the code follows PSR-12 style guidelines and 
uses Laravel service container patterns for dependency injection. The 
architecture applies SOLID principles to keep controllers focused and 
logic reusable across different parts of the system 


Laravel Migration Conventions, enforces foreign key relationships to 
maintain data integrity, utilizes timestamps for audit trails, and 
uses cascade deletes to automatically remove related records when 
parent records are deleted


ISO/IEC 25010, PHPUnit feature and unit tests are used to simulate 
user login, device management actions, and portal flows. These tests 
help ensure the system works correctly and align with ISO/IEC 25010 
reliability targets.


WCAG 2.1 AA considerations, the front-end design follows WCAG-informed 
principles with keyboard navigation support, focus indicators, and 
accessible color choices. Interactive elements support keyboard 
shortcuts and focus management. The system is designed to be 
accessible for both parents and children, with consideration for 
usability. Full WCAG 2.1 AA compliance would require verification of 
color contrast ratios and additional ARIA labeling for screen reader 
support.



**2.5.6 Design Constraints               ___________________________**
Processing Headroom, the Raspberry Pi has limited CPU and memory, so 
queue workers and background jobs are lightweight to prevent
overloading the system. Background jobs run at optimized intervals, 
with heavier tasks scheduled during off-peak hours. The database queue 
driver is used instead of Redis to reduce memory overhead. This 
ensures system responsiveness is not affected by resource consumption. 


Storage Footprint, the 480GB SSD has ample space, but video uploads 
are managed carefully to prevent storage overflow. Retention policies 
can be established for old videos and logs. The SSD offers superior 
wear-leveling and durability compared to microSD cards, making it 
suitable for continuous write operations from logs, video uploads, and 
database transactions.

Offline Operation, the system functions even when the internet is 
down. All dependencies like fonts and JavaScript libraries are bundled 
locally. Only Alternativeal remote notifications require outbound 
connectivity. The captive portal, quiz system, and video playback all 
function entirely offline once content is uploaded to the local 
storage. 


Security Surface, running shell commands from PHP is a serious 
security risk, hence, we developed several countermeasures using the 
ScriptExecutor service. We maintain a tight whitelist of scripts that 
are allowed to run. We set up sudoers entries that grant permissions 
to the www-data user to run these and only these scripts as NOPASSWD 
while providing full absolute paths. ScriptExecutor performs path 
validation, checks for script existence and executability, resolves 
symlinks and verifies the final path, and sanitizes all arguments to 
avoid command injections. All executions of scripts also log audit 
trails. This multi-layered approach to security means that, even if 
the web application is compromised, The attacker cannot run commands 
that are not in the whitelist.


Real-time Communication, the system is designed to support WebSocket 
connections for real-time updates. Laravel Broadcasting with 
WebSockets will provide instant notifications to the parent dashboard 
for events such as device connections, blocked website attempts, 
flagged website visits, time limit reached, or time granted


Integration with NoDogSplash, the captive portal system depends on 
NoDogSplash being properly configured and running. Portal redirects 
require proper firewall rules and splash page configuration to 
function correctly. The system intercepts only HTTP requests, HTTPS 
requests pass through, which is an acceptable limitation. The system 
relies on NoDogSplash for device authentication state management and 
token-based MAC address identification. If NoDogSplash is not running 
or misconfigured, the captive portal functionality fails and devices 
will not be redirected to the portal when time expires. 


**============================================================**
 *_______________________CHAPTER 03____________________________*
**============================================================**


*3.1 Summary of Constraints _________________________________________*

Technical Constraints, the system operates within several boundaries 
that shape its capabilities. Hardware compatibility limits deployment 
to PLDT modems that support captive portal behavior or DNS 
redirection, so households with incompatible routers may need 
adjustments. Wi‑Fi stability and upstream bandwidth affect portal 
responsiveness and dashboard access. Browser dependency can surface 
issues on older devices. HTTPS encryption prevents payload inspection, 
so filtering is enforced at the domain level rather than page-level 
analysis. 


Software Constraints, come from our chosen stack and resources. We 
stay web-based instead of building native mobile apps, and app-level 
blocking is done with DNS-based domain control. The Raspberry Pi 4B 
cannot handle deep packet inspection, so traffic analysis stays 
shallow. Sticking to open-source and free tools means some commercial 
features are out of reach.


Operational Constraints, has Operational considerations drive the 
system design and user experience requirements. Limited user knowledge 
and technical skills require a simple, intuitive interface that can 
limit adding complex features that might confuse nontechnical parents. 
The need for maintenance and monitoring requires that parents be 
willing and able to perform basic upkeep tasks themselves or have 
technical support available. Limitations in the testing environment 
restrict evaluations to home network setups with small user samples, 
which may not fully represent all possible usage scenarios. 


Security and privacy considerations, limit what the system can do. We 
keep stored user data to a minimum and protect sensitive information 
with HTTPS for the dashboard, but we do not inspect encrypted payloads. 
The system monitors domain-level access through logs, not the actual 
messages, videos, or detailed content children view.


Resource Constraints, relate directly to feature scope and technology 
selection. A constrained budget can only afford low-cost or freely 
available tools, thus affecting technology and service selections. 
Limited time will restrict the potential development of additional 
features, such as AI-based content filtering or advanced analytics. 
The small development team restricts the complexity and scale of 
features that can be implemented within the project timeline.


*3.2 Trade-Offs _____________________________________________________*
Design trade-offs are conscious decisions that involve a trade between 
alternatives, each with different pros and cons. The following section 
discusses five critical trade-off categories, each weighing 
significantly in the evaluation of design alternatives economic 
considerations, safety and cybersecurity, risk management, 
environmental impact, and sustainability.

**3.2.1 Tradeoff 1: Economic Material/Equipment Cost _______________**
Economic trade-off analysis considers the cost implications of 
hardware and software choices and balances initial investment against 
long-term operational expenses and system capabilities. 


Alternative A, a router firmware customization that uses a commercial 
router flashed with OpenWRT or similar firmware. It requires ₱8,700–₱17,
400 for a compatible router and risks warranty voiding and possible 
replacement if the flashing process fails. The advantage is that it 
reuses the router’s native performance and needs little extra 
hardware, but the high bricking risk, limited PLDT compatibility, and 
technical flashing process make it risky and harder for non-technical 
parents to adopt. 


Alternative B, a cloud-managed parental control that relies on a 
subscription service that tunnels traffic to the cloud for filtering 
and reporting. It avoids large upfront hardware costs but adds 
₱580–₱1,740 per month in fees plus any cloud hosting charges. 
Automatic updates and enterprise-grade analytics are positive, but 
long-term expenses, constant internet dependency, and outsourcing 
children’s browsing data to external servers make it less attractive 
for budget-sensitive and privacy-conscious families. 


Alternative C, a integrated Raspberry Pi 4B access point with SSD that  
costs about ₱5,800–₱8,840 Pi ₱3,200, SSD ₱2,350, power and accessories 
₱885–₱3,200. It keeps initial cost low, is energy efficient, and 
consolidates the access point, web server, firewall, captive portal,
and monitoring into a single device while using an open-source 
software tools with no license fees. Its processing power is lower than 
a full server, so resources must be managed carefully, but it provides 
the needed features at a price point suitable for 
home use.  


The chosen solution is a Raspberry Pi 4B with a 480GB SSD, balancing 
functionality against the cost of the hardware. The total investment 
in hardware at only around ₱5,800-₱8,840 produces a complete system 
that can serve as an access point, web server, firewall, and 
monitoring device simultaneously. 


Cost breakdown of Pi 4B 4GB is ₱3,244, Kingston A400 480GB SSD is ₱2,
350, 5V/3A USB-C supply is ₱580, heat sinks/cooling is ₱300, Ethernet 
and accessories is ₱300–₱580, and the total hardware is ₱6,780–₱7,075. 


Software costs of Raspberry Pi OS Lite, Laravel 12, MariaDB, Nginx, 
and NoDogSplash are all free and open source, making total software 
cost ₱0 and total system cost ₱6,780–₱7,075 one-time. This lower
upfront spend and zero license fees fit the project budget and keep the 
system affordable for families. 


The economic trade-off uses lower initial investment and zero 
recurring software license costs over increasing processing power and 
commercial support. This approach aligns with the project's budget 
limits and makes the system budget-friendly to families with minimal 
financial means. 



**3.2.2 Tradeoff 2: Safety Cybersecurity Risk Score  _______________**

The safety trade-off balances security with functionality and 
usability, comparing architectures by cybersecurity risk. Based on 
cybersecurity risk assessment, a number of security architectures were 
assessed. 


Alternative A, a cloud-based processing and storage scores high at 
7/10. It offers professional monitoring, automatic updates, and 
enterprise-grade security, but sends data to external servers, depends 
on internet connectivity, risks cloud breaches, and keeps children’s 
browsing data on external servers. 


Alternative B, a local storage with port forwarding scores medium-high 
at 6/10. It keeps data local and gives immediate dashboard access, but 
exposes the system to the public internet, needs firewall tuning, is 
subject to port scans, and can allow unauthorized access if 
misconfigured. 


The selected solution is a multi-layer security approach that keeps 
data local and provides secure remote access, bringing the risk score 
down to about 4/10. 


Local data storage keeps surfing logs, device info, and user data on 
the Raspberry Pi, avoids sending children’s data to external servers, 
and protects privacy within the home network, removing cloud breach 
exposure. 


Network-level security uses MAC-based firewall rules 
iptables/nftables, ScriptExecutor with whitelist and path validation 
plus argument sanitization, and sudoers that allow only authorized 
scripts, preventing unauthorized commands and access. 


Application-level security follows OWASP top ten controls such as CSRF 
protection, secure session management, bcrypt password hashing, and 
input validation/sanitization to guard against common web 
vulnerabilities. 


Secure remote access can use VPN or cloud tunneling services like 
ngrok or Cloudflare Tunnel to avoid port forwarding. If port forwarding 
is used, HTTPS with proper SSL setup is required. These Alternatives 
let users choose a secure access method that fits their needs. 

+------------------+------------+--------+--------------------------+
| Risk Category    | Risk Level | Score  | Mitigation               |
+------------------+------------+--------+--------------------------+
| Data Privacy     | Low        | 1/10   | Local Storage, No Cloud  |
|                  |            |        | Transmission             |
+------------------+------------+--------+--------------------------+
| Security Network | Medium     | 4/10   | Firewall Rules, MAC      |
|                  |            |        | Address Filtering,       |
|                  |            |        | ScriptExecutor           |
+------------------+------------+--------+--------------------------+
| Application      | Medium     | 4/10   | OWASP Guidelines, CSRF   |
| Security         |            |        | Protection, Input        |
|                  |            |        | Validation and           |
|                  |            |        | Sanitization             |
+------------------+------------+--------+--------------------------+
| Remote Access    | Medium-    | 5/10   | Optional secure access   |
| Security         | High       |        | VPN/Tunneling, with port |
|                  |            |        | forwarding only under    |
|                  |            |        | proper HTTPS             |
+------------------+------------+--------+--------------------------+
| Physical Security| Low        | 2/10   | Local Device, Physical   |
|                  |            |        | Access Control by Parents|
+------------------+------------+--------+--------------------------+
| OVERALL RISK     | MEDIUM     | 4/10   | Multi-Layered Security   |
| SCORE            |            |        | Architecture             |
+------------------+------------+--------+--------------------------+
           Table 3.2-1 Cybersecurity Risk Score Breakdown

The safety trade-off promotes local data storage and privacy over cloud-based convenience, producing a moderate cybersecurity risk score while keeping performance acceptable.  


The Advantages are data privacy for Children's browsing data stays on 
the home network, avoiding cloud data breach exposure. Local control 
for Parents manage data storage and security settings. Minimal 
external dependency for Core functions work locally without relying on 
third-party cloud services. And Compliance for Local storage aligns 
with GDPR-inspired data minimization without external processors.  


The Disadvantages and Mitigations are local maintenance responsibility 
for parents handle basic upkeep updates, monitoring, and 
troubleshooting. The mitigation is systemd auto-restart for services, 
clear documentation, and default secure configurations. No automatic 
cloud backup or updates. Local-only storage means no cloud backup, 
updates require manual installation. The mitigation is systemd 
services auto-restart on failure, documentation for backup procedures, 
an open-source stack offers community updates, but installation is 
manual. And Resource constraints limit advanced features for Raspberry 
Pi hardware limits deep packet inspection and enterprise-grade 
analytics. The mitigation is domain-level blocking, log monitoring, 
and scheduled background jobs optimized for Pi performance. 


Compared with cloud Alternative, the chosen design lowers data-privacy 
risk about approximately 1/10 vs. 7/10, while keeping overall risk 
moderate approximately 4/10 vs. 7/10, trading some setup complexity 
for local control and privacy.


The system employs many security layers like the injection prevention 
via Laravel sanitization/parameterized queries, authentication with 
secure password hashing and session management, CSRF tokens on all 
forms, secure script execution with argument sanitization, path 
validation, and whitelisting, network controls using MAC-based firewall 
rules with iptables.


With an overall cybersecurity risk score of 4/10, this multi-layered 
strategy offers sufficient protection for home network use while 
preserving local control and data privacy.


**3.2.3 Tradeoff 3: Risk Failure Rate  _____________________________**
By weighing reliability against cost and complexity, the risk 
trade-off analysis assesses system reliability and failure rates 
related to various hardware and software Alternatives. Several 
reliability and failure risk scenarios were evaluated.


Alternative A, a router firmware customization scores high at 8/10 if 
flashing succeeds, the commercial router can operate reliably for 
years, but there is a significant risk of incompatibility, failed 
firmware updates, and permanent router bricking during installation. 
These failure modes can force families to replace their ISP router out 
of pocket, which is unacceptable for many home users.


Alternative B, a cloud-managed parental control scores medium at 5/10. 
The cloud side usually runs on redundant infrastructure with automatic 
failovers, but the entire system depends on a stable internet
connection and long-term service availability. Connectivity loss, 
provider outages, or a discontinued service can all cause the parental 
controls to fail or become unavailable, even when the home network 
itself is working.


Alternative C, a integrated Raspberry Pi 4B access point with SSD 
scores medium at 5/10. It centralizes routing, captive portal, and 
monitoring on one device, which creates a single point of failure, but 
hardware is inexpensive to replace and the design includes several 
mitigation strategies.


Hardware failure risks, Pi 4B failure is mitigated with proper cooling 
heat sinks and a stable 5V/3A USB‑C power supply. The Raspberry Pi 4b 
can operate for many years with basic maintenance, though the power 
supply and SSD may need replacement over time. SSD failure risk is 
reduced by selecting an SSD instead of a microSD card, which offers 
better wear‑leveling and durability for continuous writes. The power 
supply is easily replaced if it fails. Overall hardware failure risk 
is medium 4/10. 


Software failure risks, OS crashes are low risk with Raspberry Pi OS 
Lite and are mitigated through systemd service management and automatic 
restarts. Application crashes in Laravel or its background jobs are 
handled by error logging, automatic job retries, and queue worker 
monitoring. Database corruption risk in MariaDB on SSD is low to 
medium and is mitigated by transaction logging and recommended backup 
procedures. Network service failures in hostapd, dnsmasq, and 
NoDogSplash are mitigated by service monitoring and auto‑restart 
scripts, giving an overall software failure risk of medium 5/10. 


Network failure risks, internet connectivity loss is likely and 
affects only remotedashboard access, while local controls continue to 
function due to the offline-capable design. WiFi access point failures 
are mitigated by using standard IEEE 802.11 protocols and hostapd’s 
reconnection behavior. Overall network failure risk remains medium 
5/10.



+------------------+------------+--------+--------------------------+
| Failure Category | Failure    | Score  | Mitigation              |
|                  | Risk Level |        | Effectiveness            |
+------------------+------------+--------+--------------------------+
| Hardware Failure | Medium     | 4/10   | Proper cooling, quality  |
|                  |            |        | components long-term     |
|                  |            |        | operational reliability  |
|                  |            |        | with replaceable         |
|                  |            |        | components               |
+------------------+------------+--------+--------------------------+
| Software Crashes | Medium     | 5/10   | Laravel error handling,  |
|                  |            |        | background job           |
|                  |            |        | monitoring, system,      |
|                  |            |        | service auto-restart     |
+------------------+------------+--------+--------------------------+
| Database         | Low-Medium | 4/10   | Transaction logging, SSD |
| Corruption       |            |        | reliability              |
+------------------+------------+--------+--------------------------+
| Network Service  | Medium     | 5/10   | Service monitoring,      |
| Failures         |            |        | systemd service         |
|                  |            |        | auto-restart             |
+------------------+------------+--------+--------------------------+
| Power Supply     | Medium     | 5/10   | Standard USB-C power     |
| Failure          |            |        | supply with rated cable, |
|                  |            |        | easily replaceable       |
+------------------+------------+--------+--------------------------+
| OVERALL FAILURE  | MEDIUM     | 5/10   | Multiple mitigation      |
| RISK SCORE       |            |        | strategies: systemd      |
|                  |            |        | auto-restart, error      |
|                  |            |        | handling, quality        |
|                  |            |        | components, replaceable  |
|                  |            |        | components               |
+------------------+------------+--------+--------------------------+
           Table 3.2-2 Failure Rate Score Breakdown

The risk trade-off uses several mitigation techniques to reduce the 
consequences of failure while accepting a medium failure risk 5/10 in 
return for simplicity and cost effectiveness. 


The advantages are cost-effective reliability for household use, 
replaceable components Pi, SSD, power supply, offline operation when 
internet is unavailable , service monitoring with automatic resumption 
via systemd auto-restart, and data preservation via MariaDB 
transaction logging .


The disadvantages and mitigations are single point of failure, the 
mitigation is 5 long-term operational reliability with easily 
replaceable components, and affordable replacement. Manual recovery 
may be required, the mitigation is documentation and automatic service 
restarts via systemd. No automatic failover, the mitigation is 
economical hardware replacement, detailed recovery procedures, and 
suitability for home use.


Compared with Alternatives, cloud-managed services have similar medium 
risk but are dominated by internet and provider availability, while 
router firmware customization has higher risk due to the possibility 
of permanent router failure.


Hardware reliability via quality components Raspberry Pi 4B, Kingston 
SSD, proper cooling heat sinks, stable power supply 5V/3A USB-C with 
standard USB-C cable, Raspberry Pi 4B hardware was designed for 
long-term operation with replaceable components power supply, SSD. 
Software reliability via Laravel error handling, background job 
monitoring and automated retry, systemd service auto-restart, and 
MariaDB transaction logging. Recovery procedures via system 
documentation and service restart via systemd.


Overall, Alternative C maintains simplicity and cost-effectiveness 
while achieving a balanced failure risk score of 5/10, providing 
sufficient reliability for home parental supervision. Mitigation 
strategies reduce failure impact and provide recovery paths for 
common failure scenarios. 


**3.2.4 Tradeoff 4: Environmental Cost-Benefit Analysis to the Environment**
The environmental trade-off evaluates energy usage, electronic waste, 
and carbon footprint across hardware and operating strategies. 


Alternative A, a router firmware customization reuses an existing or 
upgraded commercial router and typically has low power consumption 
similar to other home routers. Its environmental impact is driven more 
by the risk of premature router replacement if the firmware 
installation fails than by day‑to‑day energy use. A bricked router 
becomes electronic waste sooner than planned.


Alternative B, a cloud-managed parental control relies on always-on 
cloud infrastructure plus a local gateway, which spreads energy use 
across data centers, backbone networks, and the home device. While 
large cloud providers can be efficient at scale, this Alternative 
effectively adds the energy footprint of remote servers and 
long-distance data transmission on top of the household’s own 
networking equipment.


Alternative C, an integrated Raspberry Pi 4B access point with SSD 
replaces the need for a separate gateway device by combining access 
point, firewall, captive portal, and monitoring into one low-power 
unit.


Energy consumption, power usage is about 3–7 W in continuous operation, 
which translates to roughly 32 kWh per year for a 24/7 deployment. 
Compared with more power‑hungry gateway or server solutions, this 
represents a substantial reduction in household energy use for parental 
control.


Electronic waste, the hardware consists of a small Raspberry Pi 4B 
board, a 2.5″ SSD, and minimal accessories. The physical footprint and 
material usage are much smaller than full-size PCs or servers, and 
components can be reused or recycled at end of life.   


Manufacturing impact, the Raspberry Pi is manufactured efficiently at 
scale, and the SSD is a standard mass-produced component. Lightweight 
devices and minimal packaging reduce shipping and material overhead 
compared with larger systems.  


+--------------------+-------+-------+-----------------------------+
| Environmental      | Impact| Score | Details                     |
| Factor             | Level |       |                             |
+--------------------+-------+-------+-----------------------------+
| Energy Consumption | Low   | 2/10  | 32 kWh/year, 87-93% less    |
|                    |       |       | than alternatives           |
+--------------------+-------+-------+-----------------------------+
| Electronic Waste   | Low   | 2/10  | Small form factor, long-term|
|                    |       |       | operational reliability with|
|                    |       |       | replaceable components,     |
|                    |       |       | recyclable materials        |
+--------------------+-------+-------+-----------------------------+
| Manufacturing      | Low   | 2/10  | Efficient manufacturing at  |
| Impact             |       |       | scale, lightweight          |
|                    |       |       | components, minimal         |
|                    |       |       | packaging                   |
+--------------------+-------+-------+-----------------------------+
| Carbon Footprint   | Low   | 2/10  | Minimal CO2 emissions       |
|                    |       |       | compared to alternatives    |
+--------------------+-------+-------+-----------------------------+
| OVERALL            | LOW   | 2/10  | Lower than cloud or         |
| ENVIRONMENTAL      |       |       | dedicated server            |
| IMPACT             |       |       | alternatives                |
+--------------------+-------+-------+-----------------------------+
        Table 3.2-3 Environmental Impact Score Breakdown

Operational environmental benefits, local processing removes the need 
to send traffic to cloud data centers for filtering and reporting, 
reducing network transmission energy for this use case. 
Offline-capable operation avoids constant back-and-forth with remote 
services, and efficient lightweight software components keep CPU and 
memory usage modest.  

The advantages are low annual energy consumption, a compact hardware 
footprint, long-term operational reliability with replaceable 
components, and the ability to process traffic locally without 
additional cloud infrastructure.

The disadvantages and context are the system still consumes power 24/7 
and will eventually become electronic waste, but its low wattage, small 
size, and recyclability minimize this impact compared with bulkier 
alternatives.   

Overall, the Raspberry Pi 4B solution achieves a low environmental 
impact score of 2/10 while retaining full system capability, making it 
a practical and eco‑conscious Alternative for home parental control. 

+----------------------+------------+------------+-----------------+
| Alternative          | Annual     | Annual CO2 | Environmental   |
|                      | Energy     |            | Score           |
+----------------------+------------+------------+-----------------+
| Raspberry Pi 4B      | ~32 kWh    | ~16 kg     | 2/10 Low        |
+----------------------+------------+------------+-----------------+
| Cloud-Based Service  | ~100-200   | ~50-100 kg | 7/10 High       |
| with Local Gateway   | kWh        |            |                 |
+----------------------+------------+------------+-----------------+
| Commercial Router    | ~20-30 kWh | ~10-15 kg  | 3/10 Low-Medium |
| with Custom Firmware |            |            |                 |
+----------------------+------------+------------+-----------------+
             Table 3.2-4 Comparison to Alternatives

Therefore, the net environmental impact is about 93% less energy than 
mini PC alternative. 68-84% less energy than cloud service alternative. 
Minimal carbon footprint 16 kg CO2/year. And small electronic waste 
footprint due to it being compact.

The Raspberry Pi 4B solution is an eco-friendly Alternative for home 
parental control systems as it achieves minimal environmental impact 
2/10 while retaining full system capability, according to the 
environmental trade-off.


**3.2.5 Tradeoff 5: Sustainability Power Consumption/Life Span _____**
The sustainability trade-off analysis evaluates long-term viability by 
taking into account maintenance needs, hardware longevity, power 
consumption efficiency, and upgradeability. This assessment evaluates 
how each alternative will remain practical and affordable over time 
while minimizing unnecessary replacements and waste. Several 
sustainability scenarios were evaluated to determine the most 
appropriate long-term solution.


Alternative A, a router firmware customization reuses existing router 
hardware and keeps power consumption low, but sustainability is 
limited by the high risk of failed firmware installation and router 
bricking. A failed flash often forces early router replacement, which 
is costly for families and generates avoidable electronic waste. 


Alternative B, a cloud-managed parental control offers low local 
maintenance and automatic updates, but ties the system’s long-term 
viability to external subscription fees and the continued operation of 
a third-party service. If the provider increases prices, changes 
plans, or discontinues the product, families may be forced to switch 
solutions, making this path less sustainable from economic and 
operational perspectives.


Alternative C, a integrated Raspberry Pi 4B with SSD achieves a high 
sustainability score of 8 out of 10. It delivers very low power 
consumption around 3–7 W, long-term operational reliability with 
replaceable components, and a feature set that is likely to remain 
sufficient for home use for many years, reducing the need for frequent 
redesigns.


Alternative C balances low power consumption, sufficient longevity, 
and cost effective operation across the system's lifetime. The system 
demonstrates excellent power efficiency, operating at about 3–5 W when 
idle and 5–7 W under typical load when running the web server, Wi-Fi 
access point, and database, which keeps electricity costs low for 
continuous use. This power consumption efficiency earns an excellent 
rating of 9 out of 10, reflecting the system's ability to deliver full 
functionality while consuming minimal energy.


The hardware longevity of Alternative C is supported by proper cooling 
and a stable power supply. The Raspberry Pi 4B can operate for many 
years with basic maintenance, and components such as the SSD and USB‑C 
power supply are standard parts that can be replaced independently if 
they fail. The SSD's wear‑leveling design is suitable for logging 
and database workloads, and the overall system provides long-term 
operational reliability with replaceable components. The hardware 
lifespan receives a good rating of 7 out of 10, indicating that the 
system can serve families reliably over extended periods with proper 
care and occasional component replacement. 


Regarding upgradeability and maintenance, the Pi board itself is not 
upgradable in place, but the entire unit can be replaced at relatively 
low cost around ₱6,780–₱7,075. Software upgrades are straightforward 
because the solution uses open-source software with regular updates 
and strong community support. Maintenance requirements remain low, 
focusing on periodic software updates, log checks, and occasional 
hardware replacement when components reach end of life. The 
upgradeability and maintenance factor receives a medium rating of 6 
out of 10, reflecting the trade-off between limited in-place hardware 
upgrades and the cost-effectiveness of full system replacement when 
needed.


Long-term cost sustainability is strong because hardware costs are 
limited to the initial Pi-based system plus possible replacement after 
several years, power consumption remains modest thanks to the 3–7 W 
draw, and software costs stay at ₱0 because all major components are 
open source. This combination keeps total ownership costs manageable 
over a 5–10 year period. The cost sustainability factor achieves an 
excellent rating of 9 out of 10, demonstrating that the system remains 
affordable for families throughout its operational lifetime. 

Environmental sustainability is supported by the compact form factor, 
low material usage, and the ability to reuse or recycle components. 
Using a single low-power device instead of multiple boxes or cloud 
gateways also reduces the system's cumulative footprint. The 
environmental sustainability factor receives an excellent rating of 9 
out of 10, reflecting the minimal resource consumption and waste 
generation compared to alternative approaches.  

+----------------------------+--------+--------+------------------+
| Sustainability Factor      | Score  | Weight | Weighted Score   |
+----------------------------+--------+--------+------------------+
| Power Consumption          | 9/10   | 25%    | 2.25             |
| Efficiency                 |        |        |                  |
+----------------------------+--------+--------+------------------+
| Hardware Lifespan          | 7/10   | 25%    | 1.75             |
+----------------------------+--------+--------+------------------+
| Upgradeability/            | 6/10   | 15%    | 0.90             |
| Maintenance                |        |        |                  |
+----------------------------+--------+--------+------------------+
| Cost Sustainability        | 9/10   | 20%    | 1.80             |
+----------------------------+--------+--------+------------------+
| Environmental              | 9/10   | 15%    | 1.35             |
| Sustainability             |        |        |                  |
+----------------------------+--------+--------+------------------+
| Overall Sustainability     | 8/10   | 100%   | 8.1/10           |
+----------------------------+--------+--------+------------------+
           Table 3.2-5 Sustainability Score Breakdown

The advantages of the Alternative C include exceptional power 
efficiency, longterm operational reliability with replaceable parts, 
predictable and low operating costs, and minimal environmental impact 
given the capabilities provided. 


The main disadvantages are limited hardware upgradeability, replacing 
the Pi is easier than upgrading it, and the possibility that future 
requirements could eventually exceed the device’s capacity. These are 
mitigated by designing the system to meet current home use cases and 
by keeping replacement hardware affordable so that families can 
upgrade in the future without a major financial burden.  


+------------------+----------+-------------+------------+-----------+
| Alternative      | Power W  | Lifespan    | 5-Year     | Sustainab.|
|                  |          |             | Cost       | Score     |
+------------------+----------+-------------+------------+-----------+
| Raspberry Pi 4B  | ~3-7     | Long-term   | ~P14,975- | 8/10 High  |
|                  |          | operational | P15,565    |           |
|                  |          | reliability |            |           |
+------------------+----------+-------------+------------+-----------+
| Cloud-Managed    | 10-20 +  | Service-    | ~P34,800-  | 4/10      |
| Parental Control | cloud    | Dependent   | P104,400   | Medium-Low|
|                  | infra-   |             |            |           |
|                  | structure|             |            |           |
+------------------+----------+-------------+------------+-----------+
| Router Firmware  | ~5-15    | 3-5 years   | ~P8,700-   | 5/10      |
| Customization    |          |             | P17,400    | Medium    |
+------------------+----------+-------------+------------+-----------+
                Table 3.2-6 Sustainability Comparison

A long-term sustainability analysis for a 10-year projection includes 
one to two hardware replacements at years 5 to 7 and potentially 10 to 
14, resulting in estimated total hardware costs ranging from ₱13,560 
to ₱21,225 depending on the number of replacements. With one 
replacement, hardware costs total ₱13,560 to ₱14,150 for the initial 
system plus one replacement. With two replacements, the estimated 
hardware costs total ₱20,340 to ₱21,225. Total power costs amount 
estimated at ₱2,830 over 10 years, calculated at 32 kilowatt-hours per 
year multiplied by 10 years at ₱8.84 per kilowatt-hour. Total software 
costs remain zero since the system uses open-source software 
exclusively. The 10-year total cost is estimated in ranges from 
₱16,390 to ₱24,055, with an annual operating cost of ₱1,639 to ₱2,406 
per year.


Sustainability metrics demonstrate that the system achieves 87 to 93 
percent better power efficiency than mini PC alternatives. Cost 
efficiency is 50 to 70 percent lower than cloud subscription 
alternatives over 10 years. Environmental impact is minimal with a 
small electronic waste footprint and low resource usage. Maintenance 
efficiency is low with minimal maintenance requirements and easy 
troubleshooting procedures. 


The Raspberry Pi 4B solution achieves high sustainability with a score 
of 8 out of 10 through remarkable power efficiency, sufficient 
longevity, economical operation, and minimal environmental impact. 
Over 5 to 10 years, the system offers long-term sustainability while 
retaining full functionality, making it a viable Alternative for home 
parental control systems.


**Summary of the Normalized Values of the Design ___________________**
The design ratings have been standardized to a universal 0–10 scale to 
enable comparison and analysis across the five trade-off areas. This 
normalization provides insight into the overall design balance and 
enables direct comparison of performance across several assessment 
criteria. The normalized scores for each trade-off category are shown 
in Table 3.2-7, where higher values denote better performance, meaning 
lower cost, lower risk, lower environmental impact, and higher 
sustainability represent superior outcomes.


+-------------------+-----+--------+--------------------------+
| Trade-Off         | Raw | Normal | Interpretation           |
| Category          |Score| Score  |                          |
+-------------------+-----+--------+--------------------------+
| Economic Cost     | 9/10| 9.0/10 | Excellent cost-effective |
| Effectiveness     |     |        | (50-70% savings vs cloud)|
|                   |     |        | Low initial investment   |
|                   |     |        | (P6,780-P7,075), zero    |
|                   |     |        | recurring software costs.|
+-------------------+-----+--------+--------------------------+
| Safety            | 4/10| 6.0/10 | Moderate security, strong|
| Cybersecurity Risk|     |        | privacy protection. Raw  |
|                   |     |        | score (4/10) is risk     |
|                   |     |        | assessment (lower=lower  |
|                   |     |        | risk). Normalized to 6/10|
|                   |     |        | balancing privacy/security|
|                   |     |        | via local storage, multi-|
|                   |     |        | layered arch., optional  |
|                   |     |        | secure remote access.    |
+-------------------+-----+--------+--------------------------+
| Risk Failure Rate | 5/10| 5.0/10 | Acceptable reliability   |
|                   |     |        | for home use. Raw score  |
|                   |     |        | (5/10) is failure risk   |
|                   |     |        | (lower=lower prob.).     |
|                   |     |        | Medium risk accepted for |
|                   |     |        | cost-effectiveness, with |
|                   |     |        | mitigation (cooling, auto|
|                   |     |        | restart, error handling).|
+-------------------+-----+--------+--------------------------+
| Environmental     | 2/10| 8.0/10 | Excellent environmental  |
| Impact            |     |        | performance, minimal     |
|                   |     |        | impact. Raw score (2/10) |
|                   |     |        | is impact assessment     |
|                   |     |        | (lower=less negative     |
|                   |     |        | consequences). Normalized|
|                   |     |        | to 8/10. Achieved via    |
|                   |     |        | compact design, low      |
|                   |     |        | energy (32 kWh/year),    |
|                   |     |        | 87-93% energy savings.   |
+-------------------+-----+--------+--------------------------+
| Sustainability    | 8/10| 8.0/10 | Excellent long-term      |
|                   |     |        | viability. Superior power|
|                   |     |        | efficiency (3-7W), long- |
|                   |     |        | term operational         |
|                   |     |        | reliability (replaceable |
|                   |     |        | components), economical  |
|                   |     |        | operation (P14,975-      |
|                   |     |        | P15,565 over 5 years),   |
|                   |     |        | 50-70% lower costs than  |
|                   |     |        | cloud alternatives.      |
+-------------------+-----+--------+--------------------------+
                Table 3.2-7 Trade-Off Analysis


The normalized scores for each trade-off category are shown in 
Table 3.2-7, where higher values denote better performance, meaning 
lower cost, lower risk, lower environmental impact, and higher 
sustainability represent superior outcomes. The normalization 
methodology requires careful interpretation of different score types 
to ensure accurate comparison across the five trade-off areas.  


For risk-based criteria such as safety and cybersecurity, lower raw 
risk scores correspond to better adjusted performance scores, as lower 
risk inherently indicates better safety performance.  


Similarly, for impact-based criteria such as environmental 
considerations, lower raw impact scores correspond to better adjusted 
performance scores, since minimal environmental impact represents 
superior environmental performance. 


In contrast, scores for sustainability and economic performance are 
directly dependent on performance, where greater values are preferable, 
as higher sustainability and better cost-effectiveness directly 
indicate superior outcomes in these areas.


The normalized scores reveal the design's strengths and areas of 
reasonable compromise. The system demonstrates exceptional performance 
in three key areas, each scoring 8.0 or higher. 


Economic performance achieves 9.0 out of 10 through exceptional cost
effectiveness, accomplished through low initial investment of ₱6,780 
to ₱7,075, zero recurring software license costs, and 50 to 70 percent 
cost reductions over five to ten years compared to cloud subscription 
alternatives. 


Environmental performance achieves 8.0 out of 10 through minimal 
environmental impact, accomplished via compact design, low energy 
usage of 32 kilowatt-hours per year, and 87 to 93 percent energy 
savings compared to alternatives. 


Sustainability achieves 8.0 out of 10 through high long-term 
viability, accomplished via excellent power efficiency of 3 to 7 
watts, long-term operational reliability with replaceable components, 
and economical operation of ₱14,975 to ₱15,565 over 5 years.


The system maintains balanced performance in two areas, scoring 
between 5.0 and 7.9. Safety and cybersecurity achieves 6.0 out of 10, 
representing moderate security with strong privacy protection through 
local data storage, multi-layered security architecture, and 
Alternativeal secure remote access. Risk and failure rate achieves 5.0 
out of 10, representing acceptable reliability for home use, with the 
system accepting a medium failure risk in return for 
cost-effectiveness while implementing mitigation techniques 
including appropriate cooling, service monitoring, and thorough error 
handling.  


The weighted average performance across all five trade-off areas is 
7.2 out of 10. The normalized numbers demonstrate that the design 
maintains acceptable levels of security and dependability while 
performing very well in terms of economic, environmental, and 
sustainability considerations. 


This balance prioritizes cost-effectiveness and environmental 
responsibility without sacrificing crucial security and dependability 
aspects, aligning with the project's constraints and target user needs. 
The Raspberry Pi 4B-based solution provides the best overall balance 
across all trade-off parameters, making it an ideal choice for home 
parental control systems.


**Designers' Raw Ranking for the Design    _________________________**
Based on design limitations, user needs, and technical goals, the 
development team assessed and prioritized each trade-off category 
throughout the design process. The designers' raw rankings and their 
justifications demonstrate how different priorities influenced key 
design decisions, revealing the underlying rationale for evaluating 
alternatives.


The design team assigned importance rankings to each trade-off 
category on a scale of 1 to 10, where a score of 10 represents 
critical importance that must be optimized and cannot be compromised, 
scores of 8 to 9 represent very important priorities with high 
influence on design decisions, scores of 6 to 7 represent important 
considerations with moderate priority that are considered in design 
decisions, scores of 4 to 5 represent moderate priorities where 
acceptable trade-offs can be made, and scores of 1 to 3 represent 
low priorities with minimal influence on design decisions.

+--------------------+------------+------------------------------+
| Trade-Off Category | Importance | Rationale                    |
|                    | Ranking    |                              |
+--------------------+------------+------------------------------+
| Economic Cost      | 10/10      | Cost is the main factor      |
|                    |            | influencing our design. By   |
|                    |            | using a Raspberry Pi together|
|                    |            | with a suite of open-source  |
|                    |            | tools, we keep the initial   |
|                    |            | investment low and remove    |
|                    |            | recurring license fees,      |
|                    |            | making the system affordable |
|                    |            | for families to deploy.      |
+--------------------+------------+------------------------------+
| Safety             | 8/10       | Kids' browsing domains stay  |
| Cybersecurity      |            | local to protect privacy. We |
|                    |            | block unsafe/illegal domains.|
|                    |            | However, as compared to      |
|                    |            | cloud alternatives, local    |
|                    |            | storage lowers some security |
|                    |            | concerns.                    |
+--------------------+------------+------------------------------+
| Risk Failure Rate  | 7/10       | A single Pi + SSD is a single|
|                    |            | point of failure, but we     |
|                    |            | accept medium risk with      |
|                    |            | mitigations: proper cooling, |
|                    |            | stable power, auto-restart   |
|                    |            | services, and replaceable    |
|                    |            | parts.                       |
+--------------------+------------+------------------------------+
| Environmental      | 6/10       | Important but secondary. The |
| Impact             |            | Pi draws about 3-7W ~32      |
|                    |            | kWh/year and avoids cloud    |
|                    |            | transport energy. Small      |
|                    |            | footprint, but it still uses |
|                    |            | power and ends as e-waste.   |
+--------------------+------------+------------------------------+
| Sustainability     | 7/10       | Long-term viability comes    |
|                    |            | from very low power use and  |
|                    |            | cheap component swaps. It can|
|                    |            | run for years with basic     |
|                    |            | maintenance, upgrade paths   |
|                    |            | are limited, but replacement |
|                    |            | is affordable.               |
+--------------------+------------+------------------------------+
                Table 3.2-8 Designers' Raw Rankings


Economic considerations dominate with a ranking of 10 out of 10, as 
budget constraints and accessibility requirements make cost the 
primary design driver. This priority ensures that the system remains 
affordable for families with minimal financial means while maintaining 
full functionality. 


Security receives a ranking of 8 out of 10, reflecting that data 
privacy and protection are critical considerations, especially when 
dealing with children's browsing data. This high priority ensures that 
the system implements proper security measures while balancing 
functionality and usability. 


Reliability and sustainability both receive rankings of 7 out of 10, 
indicating that these factors are important but secondary to cost and 
security considerations. This balanced approach allows the system to 
achieve acceptable reliability and long-term viability without 
compromising the primary objectives of affordability and security. 


Environmental impact receives a ranking of 6 out of 10, showing that 
environmental considerations are valued and incorporated into design 
decisions, but they do not serve as primary constraints that would 
override cost or security priorities.  


These rankings would influence key design choices if applied. A 
Raspberry Pi 4B would align with the economic priority of 10 out of 10 
due to its affordability, without sacrificing the performance 
necessary to meet system requirements. 


Local data storage would be prioritized over cloud-based solutions to 
address both security, with its ranking of 8 out of 10, and cost, with 
its ranking of 10 out of 10, ensuring that children's browsing data 
remains within the home network while avoiding recurring subscription 
fees. 


A single hardware setup would be preferred over redundant systems due 
to the cost priority of 10 out of 10 taking precedence over maximum 
reliability, which has a ranking of 7 out of 10, demonstrating that 
acceptable reliability with affordable replacement costs would be 
preferable to expensive redundancy.  


Open-source software would address both the cost priority of 10 out of 
10 and the sustainability priority of 7 out of 10, providing zero 
recurring license costs while ensuring long-term software availability 
and community support. 


Energy efficiency would be optimized to address both the cost priority 
of 10 out of 10, by reducing electricity expenses, and the 
environmental priority of 6 out of 10, by minimizing carbon footprint 
and resource consumption. 


The raw rankings demonstrate that while security, dependability, and 
sustainability are significant secondary factors in the trade-off 
analysis, budgetary limitations serve as the primary motivator. 


This prioritization suggests that a system should remain accessible to 
families with limited financial resources while maintaining essential 
security protections and acceptable levels of reliability and 
environmental responsibility. Alternative C, aRaspberry Pi 4B
based solution demonstrates a balance of these competing priorities, 
showing how a system could be affordable, secure, reliable, and 
environmentally conscious within the constraints of the project's 
budget and target user needs.



*3.3 Influence of the Design Trade-Off in the Final Design __________*
The design choices made throughout the development of the system were 
shaped directly by the trade-off analysis. Instead of treating cost, 
security, reliability, environmental impact, and sustainability as 
isolated issues, the team used the trade-off results as a guide when 
deciding on hardware, software, and architectural patterns. This 
section explains how each trade-off category influenced concrete parts 
of the final design and how these decisions work together to shape the 
framework for the final design  


The economic trade-off, which received the highest importance ranking 
10/10, would have the strongest influence on design decisions. 
Alternative C centers on a Raspberry Pi 4B paired with a 480GB SSD, 
with a total hardware cost of roughly ₱6,780–₱7,075. 


This approach would rule out router firmware customization ₱8,700–₱17,
400 with high bricking risk and cloud-based parental control 
subscriptions ₱580–₱1,740 per month that would accumulate substantial 
recurring expenses over five years.


By letting a single Raspberry Pi act as the Wi-Fi access point, web 
server,firewall/router, captive portal, and monitoring device, this 
alternative avoids extra boxes, cabling, and power supplies that would 
increase both cost and complexity. 


The economic priority would also favor an entirely open-source software 
tools that runs Laravel 12, MariaDB, Nginx, and NoDogSplash which 
removes licensing fees and keeps software costs at ₱0. The 480GB 
Kingston A400 SSD offers enough capacity for the operating system, 
Laravel application, MariaDB database, logs, and educational videos at 
a low one-time price, avoiding ongoing cloud storage charges while 
remaining sufficient for typical home usage.


The safety and cybersecurity trade-off, ranked 8/10 in importance, 
guided how the system handles data and security at both the network and 
application levels. To keep children’s browsing data within the home, 
all logs, device information, and usage records are stored locally on 
the Raspberry Pi rather than sent to external servers. 


This local-based data storage design follows the preference for a low 
privacy risk profile while still giving parents the information they 
need. At the network layer, the three tier control architecture using 
NetworkService, ScriptExecutor, and whitelisted shell scripts 
formalizes how the web application interacts with firewall rules and 
NoDogSplash, so that only approved operations can run with elevated 
privileges. 


Whitelist validation, path validation, and argument sanitization, 
together with restricted sudoers entries, help prevent arbitrary 
command execution even if the web application is compromised. 


Remote access is treated as Alternativeal rather than mandatory, when 
remote access is needed, it is configured using secure methods such as 
VPN or trusted tunneling according to the project guidelines, while 
parents simply use the provided dashboard address, and all core 
functions continue to operate inside the local network even if remote 
access is never set up. 


On the application side, Laravel’s built-in protections and 
OWASP-aligned practices such as CSRF tokens on forms, secure session 
management, password hashing, and input validation, provide a 
reasonable level of protection that fits a home environment while 
keeping the dashboard usable for non‑technical parents. 


The risk and failure-rate trade-off, ranked 7/10, would influence how 
a design balances reliability with cost. Alternative C would accept a 
single Raspberry Pi 4B with SSD as a managed single point of failure 
and focus on mitigation strategies to keep the failure risk at an 
acceptable medium level, rather than deploying redundant hardware. 


Proper cooling using heat sinks, a stable 5V/3A USB‑C power supply, 
and careful configuration would aim to support long-term operation, 
with the understanding that individual components such as the power 
supply or SSD may need replacement over time. 

Background jobs and services would be managed through systemd with 
auto‑restart, and Laravel queue workers would be configured to retry 
failed jobs and log errors, so the system could recover from common 
faults such as process crashes or transient network issues without 
manual intervention. 


MariaDB's transaction logging and recommended backup procedures would 
support data preservation, and an offline‑based design would mean that 
ISP outages affect only remote access while the local dashboard, 
captive portal, and time tracking continue to function.  


These measures would collectively accept a medium failure risk in 
return for much lower hardware and operating costs, which matches the 
priorities of home users.


The environmental trade-off, which scored 6/10 in design importance but 
2/10 in impact for the selected solution, guided energy and 
resource-related choices. Selecting the Raspberry Pi 4B, which 
typically draws only 3–7 watts in continuous operation, instead of 
higher-power alternatives, reduces annual energy use to about 32 kWh 
per year. 


This low power demand not only lowers electricity bills but also avoids 
the larger environmental footprint associated with running a 
higher‑power device 24/7. Using an SSD instead of a microSD card 
improves reliability for continuous write workloads such as 
logging and database operations, and reduces the chance of early 
storage failures that would generate additional electronic waste. 


The software tools is kept lightweight, Raspberry Pi OS Lite, optimized 
Laravel configuration, and tuned background jobs to make efficient use 
of CPU and memory and further minimize resource usage. In addition, 
keeping all processing local on the Pi eliminates the ongoing energy 
costs associated with cloud data centers and long-distance data 
transfers for this use case.


The sustainability trade-off, also ranked 7/10 in importance but 
scoring high 8/10 in performance for Alternative C, ties together 
power efficiency, long-term viability, and maintainability. 


Very low continuous power consumption, combined with modest hardware 
cost, would allow the system to remain affordable to operate over many 
years. The use of commonly available, replaceable components Raspberry 
Pi 4B, a standard 2.5″ SSD, and an off‑the‑shelf USB‑C power supply 
means that parents or technicians could swap out failed parts without 
discarding the entire system. 


Open‑source software supports sustainability by providing regular 
updates and community support without vendor lock‑in or forced 
upgrades. Maintenance tasks, such as updating blocklists, quizzes, and 
videos or applying system updates, would be deliberately 
designed to be light so that the solution remains practical for 
families over time. In this way, sustainability would be achieved not 
by over‑provisioning hardware, but by pairing low‑power components with 
a design that can be maintained and, when necessary, replaced at a 
manageable cost.


Taken together, the trade-offs influenced the final design in several 
integrated ways rather than through isolated decisions. Local storage 
and an open‑source tools satisfy both cost 10/10 and security 8/10 
priorities by avoiding subscription fees and keeping sensitive data 
inside the home. 


Accepting a single-device architecture keeps hardware expenses within 
the economic constraints while relying on monitoring, auto‑restart, 
and careful configuration to maintain an acceptable reliability level. 
Low‑power hardware and a lean software stack support both 
environmental and financial goals by cutting energy use and operating 
expenses at the same time. 


Finally, the combination of replaceable components, open‑source 
software, and offline-capable operation underpins the sustainability 
objectives without exceeding the project’s budget. 


Overall, the design trade-off analysis provides a framework for 
evaluating alternatives, showing how a Raspberry Pi 4B-based approach 
could offer a practical balance between affordability, safety, 
reliability, environmental responsibility, and longterm viability for 
home parental control.


*3.4 Sensitivity Analysis     _______________________________________*
The sensitivity analysis checks how changing the importance of the 
five trade-off categories affects the overall design score. We start 
from the normalized scores from the previous section, the economic 9.0, 
safety 6.0, risk 5.0, environmental 8.0, and sustainability 8.0, and 
then assign a weight to each category based on how important it is 
in a given scenario. We use a simple weighted scoring method, where 
the total score is computed as  


Overall Score = Σ Normalized Score × Weight / Σ Weights 


The normalized scores show how well the design performs in each 
category, and the weights show how much priority each category gets in 
that iteration. To keep the scenarios comparable, we first scale the 
raw significance ratings so that their total is 10 in every iteration. 
This way, each scenario only changes the priority mix, while the 
scoring method stays the same and produces one overall score for each 
case. 


**3.4.1 Iteration 1: Economic Priority 10-9-8-7-6  _________________**
This iteration represents the designers' original ranking where 
economic cost is treated as the most important factor. The raw weights 
are Economic 10, Safety 8, Risk 7, Environmental 6, and Sustainability 
7, totaling 38. 


These are normalized to sum to 10, giving normalized weights of 
Economic 2.63, Safety 2.11, Risk 1.84, Environmental 1.58, and 
Sustainability 1.84.

The weighted score calculation multiplies each normalized score by its 
normalized weight: Economic 9.0 × 2.63 = 23.67, Safety 6.0 × 2.11 = 12.
66, Risk 5.0 × 1.84 = 9.20, Environmental 8.0 × 1.58 = 12.64, and 
Sustainability 8.0 × 1.84 = 14.72, for a total of 72.89. Dividing by 10 
gives an overall score of 7.29 out of 10. 


This case matches the real design priorities in the project: the system 
performs very well in terms of cost while still keeping the other areas 
at acceptable levels.


**3.4.2 Iteration 2: Balanced Priorities 6-7-8-9-10  _______________**
This iteration tests a scenario where priorities are reversed, with 
sustainability and environmental concerns most important. The raw 
weights are Economic 6, Safety 7, Risk 8, Environmental 9, and 
Sustainability 10, for a total of 40. 


Normalizing these to sum to 10 gives weights of Economic 1.50, Safety 1.
75, Risk 2.00, Environmental 2.25, and Sustainability 2.50.  


The weighted score calculation is Economic 9.0 × 1.50 = 13.50, Safety 6.
0 × 1.75 = 10.50, Risk 5.0 × 2.00 = 10.00, Environmental 8.0 × 2.25 = 
18.00, and Sustainability 8.0 × 2.50 = 20.00, for a total of 72.00, 
which gives an overall score of 7.20 out of 10 when divided by 10.


The design handles this case well because it already does strongly in 
environmental and sustainability terms, mainly due to low power use, 
compact hardware, and an opensource tools that can run for years, so 
the system stays competitive even when the focus moves away from cost 
and toward environmental and sustainability goals. 


**3.4.3 Iteration 3: Security Priority 9-10-8-7-6  _________________**
This iteration tests a scenario where safety and cybersecurity become 
the top priority. The raw weights are Economic 9, Safety 10, Risk 8, 
Environmental 7, and Sustainability 6, again totaling 40. 


These normalize to Economic 2.25, Safety 2.50, Risk 2.00, Environmental 
1.75, and Sustainability 1.50. The weighted scores are Economic 9.0 × 2.
25 = 20.25, Safety 6.0 × 2.50 = 15.00, Risk 5.0 × 2.00 = 10.00, 
Environmental 8.0 × 1.75 = 14.00, and Sustainability 8.0 × 1.50 = 12.
00, for a total of 71.25 and an overall score of about 7.13 out 
of 10. 


The design stays competitive because strong scores in cost, 
environmental impact, and sustainability help balance the more moderate 
safety score, showing that the layered security setup and local-based 
data storage still give reasonable protection when security is treated 
as the main concern.

**3.4.4 Iteration 4: Reliability Priority 8-7-10-6-9 _______________**
This iteration tests a scenario where reliability and failure risk at 
the top. Here, the raw weights are Economic 8, Safety 7, Risk 10, 
Environmental 6, and Sustainability 9, with a total of 40.  


Normalized, these become Economic 2.00, Safety 1.75, Risk 2.50, 
Environmental 1.50, and Sustainability 2.25. The weighted scores are 
Economic 9.0 × 2.00 = 18.00, Safety 6.0 × 1.75 = 10.50, Risk 5.0 × 2.50 
= 12.50, Environmental 8.0 × 1.50 = 12.00, and Sustainability 8.0 × 2.
25 = 18.00, giving a total of 71.00 and an overall score of about 7.10 
out of 10. 


Even with this heavier focus on reliability, the design is still 
workable because it continues to score well in cost, environmental 
impact, and sustainability, which supports the choice to use a single 
Raspberry Pi with mitigation steps for a home setup while noting 
that a fully redundant design would be needed if extremely low failure 
risk were required.

**3.4.5 Iteration 5: Equal Priorities 2-2-2-2-2  ___________________**
This iteration tests a scenario where it uses equal weights for all 
five trade-off categories, treating them as equally important. In this 
case, each category has a weight of 2, for a total of 10, so the 
normalized weight for every category is 2.00. 

The weighted scores are Economic 9.0 × 2.00 = 18.00, Safety 6.0 × 2.00 
= 12.00, Risk 5.0 × 2.00 = 10.00, Environmental 8.0 × 2.00 = 16.00, 
and Sustainability 8.0 × 2.00 = 16.00, for a total of 72.00 and an 
overall score of 7.20 out of 10. 

This tells us that the design does not depend on a single strong 
category to look good, instead, it stays consistent across cost, 
safety, risk, environmental impact, and 
sustainability.


**3.4.6 Conclusion of Sensitivity Analysis   _______________________**
The sensitivity analysis shows that the Raspberry Pi 4B-based design 
remains stable even when the relative importance of the five trade-off 
categories is changed. Across all five weighting scenarios, the 
overall score stays within a narrow range of about 7.10 to 7.29 out of 
10. This small variation, which represents roughly a 2–3 percent 
difference, indicates that the design is not overly dependent on a 
single priority ordering and that it performs consistently under 
different sets of assumptions.  


The highest overall score of approximately 7.29 out of 10 occurs in 
the economic‑priority case, which matches the project’s real 
constraints and intended use. This confirms that choosing a Raspberry 
Pi 4B with an open-source software tools is well‑suited to the goal of 
keeping costs low for families while still delivering the required 
functionality. 

At the same time, when the analysis shifts emphasis toward 
environmental impact, sustainability, security, or reliability, the 
overall score remains above 7.0 out of 10. In these cases, strong 
performance in economic, environmental, and sustainability dimensions 
helps offset the more modest scores in safety and risk, so that no 
single weakness causes the design to fail under a different priority 
mix.  

Taken together, these results support the view that Alternative C is 
genuinely balanced rather than narrowly optimized. Alternative C 
Raspberry Pi 4B solution remains competitive when priorities are 
cost‑focused, environment‑focused, security‑focused
reliability‑focused, or evenly weighted, which suggests that this 
alternative would likely continue to be appropriate even if stakeholder 
priorities shift slightly over time. For a home parental control 
application, where budgets, security expectations, and environmental 
awareness can differ from one household to another, this robustness 
suggests that Alternative  C could adapt to different emphasis without 
requiring a complete redesign.  


The sensitivity analysis therefore reinforces the earlier conclusion 
that Alternative C demonstrates a successful balance of conflicting 
trade-offs while remaining aligned with 
the project's objectives and constraints.


+------+-----------------+--------+--------+--------+--------+--------+---------+
| Iter | Priority Focus  | Eco Wt | Safe Wt| Risk Wt| Env Wt | Sust Wt| Score   |
+------+-----------------+--------+--------+--------+--------+--------+---------+
| 1    | Economic        | 2.63   | 2.11   | 1.84   | 1.58   | 1.84   | 7.29/10 |
|      | Original        |        |        |        |        |        |         |
+------+-----------------+--------+--------+--------+--------+--------+---------+
| 2    | Sustainability/ | 1.50   | 1.75   | 2.00   | 2.25   | 2.50   | 7.20/10 |
|      | Environmental   |        |        |        |        |        |         |
+------+-----------------+--------+--------+--------+--------+--------+---------+
| 3    | Security        | 2.25   | 2.50   | 2.00   | 1.75   | 1.50   | 7.13/10 |
+------+-----------------+--------+--------+--------+--------+--------+---------+
| 4    | Reliability     | 2.00   | 1.75   | 2.50   | 1.50   | 2.25   | 7.10/10 |
+------+-----------------+--------+--------+--------+--------+--------+---------+
| 5    | Equal           | 2.00   | 2.00   | 2.00   | 2.00   | 2.00   | 7.20/10 |
|      | Priorities      |        |        |        |        |        |         |
+------+-----------------+--------+--------+--------+--------+--------+---------+
Table 3.4-1 Sensitivity Analysis Summary Alternative C - Raspberry Pi 4B


**============================================================**
 *_______________________CHAPTER 04____________________________*
**============================================================**
*4.1 Final Design ___________________________________________________*
Chapter 4 covers the final design for the Child-Centric WiFi 
Monitoring and Control System. We looked at different alternatives in 
Chapter 3 and decided Alternative C works best. Alternative C is the 
Integrated Raspberry Pi 4B Access Point Design. Cost-effectiveness, 
security, reliability, environmental impact, and sustainability all 
matter, and this design addresses each one. The design also meets all 
project requirements and constraints. 


All system functions run on a single Raspberry Pi 4B device. The same 
device acts as a WiFi access point, web server, firewall, captive 
portal, and monitoring system. By putting everything in one device, we 
avoid needing extra hardware components. Power consumption stays low. 
All data storage happens locally, which keeps things private and 
secure. The entire system runs on open-source software. This means no 
recurring licensing costs, and we can maintain the system long-term.


*4.1.1 Hardware Design  _____________________________________________*
We designed the hardware based on what the system needs to run 
reliably. Each component was picked to balance performance, cost, and 
energy efficiency. Since the system runs continuously 24/7, every part 
had to support that requirement. 


Core Compute, we went with the Raspberry Pi 4B with 4 GB RAM running 
Raspberry Pi OS Lite (64-bit). The reason we chose this is its 
affordability, plus it has enough processing power to run both a web 
server and network operations at the same time. GPIO pins are 
available for possible future enhancements like status LEDs. The 
Raspberry Pi 4B runs Laravel 12 application processing, MariaDB 
database operations, Nginx web server tasks, and network routing 
functions. The Pi provides enough computational resources, so we don't 
need expensive dedicated server hardware. 


Networking, the Raspberry Pi 4B uses a dual-mode network
configuration. It connects to the existing home network using a LAN 
cable for internet access. At the same time, its onboard 802.11ac 
Wi-Fi interface works as an access point to create the child device's 
network. The Ethernet interface (eth0) gets its IP address
automatically from the ISP router through DHCP. This serves as the WAN 
connection for internet access. The WiFi interface (wlan0) is set up 
as an access point with a static IP address of 192.168.4.1. It 
broadcasts the network with SSID: Parental_WiFi. With this dual-mode 
setup, the Pi receives internet connectivity from the home router 
while also providing a separate, isolated WiFi network for child 
devices.


Storage, we're using a Kingston A400 2.5" SATA Internal SSD with 480GB 
capacity. We connect the SSD to the Raspberry Pi 4B using a 
USB-to-SATA adapter. This provides reliable external storage that 
performs better than microSD cards for continuous database and video 
operations. We partitioned the storage to hold the operating system, 
Laravel 12 application, MariaDB database, video files, and log files. 
The SSD is much more reliable for continuous write operations compared 
to a microSD card. Logging and database transactions happen 
constantly, and video streaming needs better performance. The SSD's 
wear-leveling technology manages the continuous write operations that 
system logs, database transactions, and video file storage need.


Power and Cooling, A 5V/3A USB-C power supply powers the Raspberry Pi 
4B. It connects with a standard USB-C cable (Vention USB 2.0 TYPE-C, 
3A rated). We added fitted low-profile heat sinks to keep temperatures 
stable during continuous operation. The Pi runs firewall operations 
and video streaming 24/7, so proper cooling matters. A stable power 
supply and good cooling are essential for keeping the system reliable 
during continuous operation. If we don't have them, thermal throttling 
could hurt performance or cause system instability.


Peripheral Support, HDMI and USB ports stay available for local 
debugging and maintenance. The GPIO header is reserved for potential 
future features, like status LEDs that show which devices are active 
or when alerts happen. These peripheral connections let us access the 
system directly during initial setup, troubleshooting, and 
maintenance. We don't need network connectivity for these tasks.

+---------------------+
| ISP Modem/Router    |
| (LAN Cable - Eth)   |
+----------+----------+
           |
           v
+-----------------------------------------------------+
| Raspberry Pi 4B                                     |
+-----------------------------------------------------+
  ▲           |               ▲
  |           v               |
+-------+   +---------+   +---------+
| SSD   |   | WiFi AP |   | Power   |
| (Stor)|   | (WiFi)  |   | (5V/3A) |
+-------+   +---------+   +---------+
                |
                v
+----------------------------------+
| Child Devices (WiFi Only)        |
| (Smartphones, Tablets, Laptops)  |
+----------------------------------+

        Figure 4.1-1 Hardware Components Flowchart


All the hardware components work together to create a complete, 
self-contained system. We need minimal external dependencies. The 
Raspberry Pi 4B acts as the central processing unit. The SSD stores 
all system data and media files reliably. The dual-mode network 
configuration gives us both internet connectivity and WiFi access 
point functionality. This integrated hardware design supports all 
software functions while keeping power consumption and operating costs 
low. 



*4.1.2 Software Design ______________________________________________*
The software design builds on Laravel 12. This framework provides a 
Model-ViewController (MVC) structure along with queued jobs and custom 
services. The complete Laravel application runs directly on the 
Raspberry Pi. Nginx works as the web server. PHP FPM processes PHP 
code. The software architecture takes a secure, layered approach for 
network control, process monitoring, and media handling. We maintain 
separation of concerns and follow security best practices throughout.


Network Control Architecture, the network control system uses a 
three-tier architecture for secure and reliable device management. The 
NetworkService class provides high-level methods like blockDevice(), 
unblockDevice(), whitelistDevice(), getConnectedDevices(), 
getTrafficStats(), and isDeviceBlocked(). These methods validate 
devices, update the database when needed, and log errors. The 
ScriptExecutor service works as a secure intermediary. It validates 
script names against a whitelist. It checks script paths to prevent 
directory traversal attacks. It sanitizes all arguments using 
escapeshellarg(). It executes scripts with sudo privileges configured 
through the sudoers file. Bash scripts perform the actual network 
control: block_device.sh, unblock_device.sh, whitelist_device.sh, 
get_connected_devices.sh, and monitor_traffic.sh. These scripts run 
iptables commands to modify firewall rules on the INPUT and FORWARD 
chains based on MAC addresses. The layered approach keeps things 
secure, reliable, and maintainable. It separates high-level business 
logic from low-level system operations. We implement 
multiple security layers to prevent unauthorized command execution.


Command Execution Layer, we created service classes that safely 
execute shell commands and Python helper scripts. These manage WiFi 
services, configure firewall rules, and control the captive portal. 
These services create a secure layer between web controllers and 
system command execution, which prevents unauthorized access. The 
ScriptExecutor service implements several security measures. Whitelist 
validation means only approved scripts can run. Path validation 
prevents directory traversal attacks. Argument sanitization stops 
command injection. Comprehensive logging records all executions for 
audit trails. Python scripts handle complex operations that work 
better in Python than shell scripts. Examples include parsing network 
logs, processing ARP table data, and managing NoDogSplash client 
lists. This command execution layer makes sure all system-level 
operations happen securely and can be audited. It also prevents 
potential security vulnerabilities from direct command execution.


Process Monitoring, Laravel background jobs monitor device connections 
and track active sessions. They figure out which devices are connected 
and using the internet. The MonitorDeviceConnections job queries the 
ARP table to get currently connected devices. The TrackActiveSessions 
job reads active session records from the database to calculate time 
usage. We correlate MAC addresses with active sessions. This lets us 
accurately track how much time each device has spent online and deduct 
it from their allocation. Additional background jobs include 
CheckTimeExpiration, which detects when a device's time runs 
out and triggers the captive portal redirect. EnforceSchedules 
enforces time-based access rules. These background jobs run 
continuously to ensure accurate time tracking and automatic 
enforcement of parental control policies. Manual intervention isn't 
needed. 


Media Handling, Parents can upload educational videos through the web 
dashboard. The system validates uploaded files in MP4, WebM, and OGG 
formats, up to 512MB. It stores them in storage/app/public/videos and 
generates streaming-ready links. Laravel's filesystem features handle 
this, optimized for the Pi's storage capabilities. Video files are 
served directly from the local SSD storage, which ensures fast access 
and offline availability. The system also tracks video completion 
records and dictionary word displays during video playback. This 
enables the validation process that children must complete to 
earn additional internet time.

The software design brings all these components together into a 
cohesive system. It manages network access, tracks device usage, 
enforces time limits, and provides educational content through quizzes 
and videos. The layered architecture gives each component a clear 
responsibility while they work together to provide comprehensive 
parental control functionality. Security measures exist at multiple 
levels, from application-level input validation to network-level 
firewall rules. Even if one layer is compromised, 
the system stays secure.

                    ┌───────────────────────────┐
                    │  Raspberry Pi 4B System   │
                    └───────────────────────────┘
                              │
                              ▼
          ┌───────────────────────────────────────────────┐
          │                 Web Server Layer              │
          │                 - Nginx                       │
          │                 - PHP-FPM (PHP 8.x)           │
          ├───────────────────────────────────────────────┤
          │             Laravel 12 Application            │
          │             - Controllers, Models, Views      │
          │               (Blade + Alpine.js)             │
          │             - Services, Background,           │
          │               Job, Queues                     │
          ├───────────────────────────────────────────────┤
          │                 Database Layer                │
          │                 - MariaDB (Database)          │
          ├───────────────────────────────────────────────┤
          │             System Integration Layer          │
          │             - Shell Scripts (iptables,        │
          │               hostapd, dnsmasq)               │
          │             - Python, Helper, Scripts         │
          ├───────────────────────────────────────────────┤
          │             Real-time Communication           │
          │             - Laravel Broadcasting            │
          │               + WebSockets                    │
          └───────────────────────────────────────────────┘

        Figure 4.1-2 Software Component Overview



