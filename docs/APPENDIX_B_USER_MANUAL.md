APPENDIX B: USER MANUAL

This is the user manual for the Child-Centric WiFi Monitoring and Control System. The day-to-day screens use the product name Parental WiFi, so you will see that label in the browser. We wrote this guide for parents who want to monitor and control their children's internet access. You will find step-by-step instructions in plain language—no technical background needed. This manual describes the features as they exist in the current web application together with the Raspberry Pi network setup they depend on.


B.1	System Overview

The Child-Centric WiFi Monitoring and Control System functions as a parental control solution designed to help parents manage their children's internet usage. The system operates on a Raspberry Pi 4B. This device creates a dedicated WiFi network specifically intended for children's devices.

Children's devices connect only to this dedicated network. Parents have the ability to see how much time their children spend online, and they can set time limits as needed. Once a child's internet time runs out, a portal page automatically appears. This page gives children two options: they can take an educational quiz or watch an educational video. If children finish either activity successfully, they get more internet time added to their account. Parents also have the option to block websites and mobile apps they don't want children accessing. Visits and blocked attempts are recorded so parents can review them later.

B.1.1	System Setup

A Raspberry Pi 4B serves as the system's main computer. This single device handles the network-facing parts of the system. The Raspberry Pi creates a WiFi network that children's devices connect to. A dashboard website runs on the same device (or on the same local network, depending on how you deploy it), and that is where parents manage policies and review activity. The Raspberry Pi participates in website access control and time tracking. In practical terms, one small computer combines the idea of a filtered access point with a web server.

Storage comes from a 480GB solid-state drive, or SSD. Compared to traditional hard drives, SSDs run faster and tend to be more reliable. A USB adapter cable connects the SSD to the Raspberry Pi. The reason we went with SSDs is their ability to run continuously without breaking down. This matters a lot since the system is expected to run for long stretches while it logs internet usage, stores video files, and records which websites children visit.

Power comes from a standard phone charger—specifically a 5V/3A USB-C power supply connected with a USB-C cable. Since the device may run for extended periods, cooling becomes essential to prevent overheating. The system uses heat sinks (small metal components) plus a cooling fan. These components work together to maintain safe operating temperatures, following the same cooling approach used in desktop computers.

Internet connection happens through two methods. One Ethernet cable connects the Raspberry Pi to the home WiFi router, giving it internet access. A separate WiFi network called "Parental_WiFi" is created by the Raspberry Pi specifically for children's devices. When children connect their phones or tablets to this network, their internet traffic flows through the Raspberry Pi before reaching the wider internet. Because requests pass through the Raspberry Pi first, the system can monitor and control what children access online.

To set up the system, you'll need a home WiFi router with an available Ethernet port. You also need an active internet connection from your provider—PLDT is one example. The router must be configured to allow devices to connect via Ethernet cable. The system operates alongside your main home WiFi network. Children's devices receive internet access through the Parental WiFi path, which still uses your home internet connection. Parents keep their own phones and laptops on the regular home WiFi when they want unrestricted access, while using the Parental WiFi dashboard from the LAN (or remotely—see below) to manage children's access.

B.1.2	Accessing the dashboard

Parents can open the dashboard on computers, tablets, or smartphones. On your home network, start by opening a web browser—Chrome, Firefox, Safari, and Edge all work. Make sure the device is connected to the same home network that can reach the Raspberry Pi. Then enter the dashboard address your installer gave you. A typical local address looks like http://192.168.4.1 (your network may use a different number). Press Enter. The login page should load.

If you use Tailscale or another secure tunnel so the Pi has a virtual address (often starting with 100.), you can open the same dashboard while away from home by using that address instead, still using http or https exactly the way you do on the LAN. The first time you connect from a new network, double-check you are signing in on the real Parental WiFi login screen and not a look-alike site.


   B.2	Initial setup and accounts

   B.2.1	System administrator versus parent accounts

   Two kinds of people use this software, and they do not see the same menus.

   A system administrator signs in with the seeded administrator account (created when the database is first populated—commonly admin@parentalwifi.local with password admin123 until you change it). That account is meant for approving new parents, handling password-reset requests, and other housekeeping. It does not show the full parent dashboard for managing children; it opens the Administration area instead.

   Parents create their own accounts through Create parent account on the login page. After email verification and administrator approval, a parent lands on the Parental WiFi parent dashboard with Devices, Quizzes, Videos, Reports, Logs, and the rest of the tools described in this manual.

   Some households also use a household operator account. That role behaves like a parent who can additionally open the Administration screens. Only a system administrator should grant or remove that extra access.

   B.2.2	Registering and verifying your email

   If you are a parent, tap Create parent account on the login page. Fill in your name, email, and password, then submit the form. The application sends a six-digit verification code to your email. Open the message, return to the browser, type the code where the site asks for it, and submit. Codes expire after a period shown on the verification screen; if yours expires, use the option to resend a fresh code (subject to reasonable rate limits so the mailbox is not flooded).

   B.2.3	Waiting for administrator approval

   After your email is verified, your account sits in a pending queue until a system administrator approves it. You will see a clear pending-approval page if you try to open the parent dashboard too early. When an administrator rejects an application, the site explains that outcome as well.

   B.2.4	Signing in as an approved parent

   Once approved, return to the login page, enter the email and password you chose at registration, and choose Log in. You should arrive at the Dashboard entry point for Parental WiFi. From there, use the left sidebar to reach every other feature.

   B.2.5	Changing your password and profile details

   Password changes no longer live under Accounts. Instead, open your name menu at the bottom of the sidebar (or the top bar on small screens) and choose Profile. On the Profile page you can update the name shown in the app, change your password, and—if your account type allows it—delete your own account. Pick a strong password you have not used elsewhere.

   B.2.6	If you forgot your password

   The Forgot password? link does not email you a magic reset link. Instead, it notifies administrators that you asked for help. When they finish resetting your credentials to a temporary password, sign in, go straight to Profile, and set a new password you alone know.


   B.3	Device management

   B.3.1	Adding a new device

   Before a child's device can access the WiFi network, you must add it to the system. Each device has a unique identifier called a MAC address, which works like a fingerprint for that specific device. MAC addresses remain constant—phones, tablets, and computers each have their own. MAC addresses appear as letters and numbers separated by colons, such as AA:BB:CC:DD:EE:FF. The system uses this address to identify individual devices and apply the appropriate rules to each child's device.

   Adding a child's device involves a few simple steps. Open the left sidebar and click Accounts. Find the add-device action (for example a + New control) near the top of the page. A registration form opens for entering several details. Assign the device a friendly name (like "John's iPhone" or "Sarah's Tablet"). Enter the device's MAC address. Select the device role (choose Child for time restrictions and monitoring features). Set the initial time allocation in minutes—30 or 60 minutes works well to start. Save the form when you are finished. The device appears in your Accounts table. Devices with the Child role also appear when you open Child Devices, where statistics and activity monitoring are available.

   The method for finding a device's MAC address varies by device type. iPhone or iPad users should open Settings, tap General, then About. The Wi-Fi Address appears here—this is the MAC address. Android users need to go to Settings, then About Phone (or About Device), and locate Status or Hardware Information. The Wi-Fi MAC Address is listed there.

   Windows users must press the Windows key and R simultaneously, type cmd, and press Enter. This opens the command prompt. Type ipconfig /all and press Enter again. Under the WiFi adapter section, find "Physical Address"—this is your MAC address. Mac users should click the Apple menu, navigate to System Preferences, then Network. Select Wi-Fi from the list, click Advanced, then the Hardware tab. The MAC address is displayed here.

B.3.2	Viewing device status

All device management happens on the Accounts page, which serves as your control center. Click Accounts in the left sidebar to access it. A table displays all registered devices.

Devices can have one of three statuses. "Active" status indicates time remains and browsing functions normally. When status shows "Blocked", time has run out—children trying to visit many sites see the portal page instead of their intended destination. "Whitelisted" devices receive unlimited internet access without restrictions, making this status ideal for parent devices requiring constant access.

Each device entry on the Accounts page displays multiple items: the MAC address (the unique identifier like "AA:BB:CC:DD:EE:FF"), the assigned role (Child, Guest, or Parent), the friendly name you chose (such as "John's iPhone"), the current status (Active, Blocked, or Whitelisted), along with controls for editing, blocking, unblocking, or managing that device.

The Child Devices page focuses on devices with the Child role. It shows time-usage charts, quiz score summaries, and a short WEBSITE HISTORY list for the child you pick in the dropdown. Use the buttons in the header to jump to the full Browsing History or Access Attempts screens for the same child when you need more rows than the preview card shows.

B.3.3	Managing device time

Internet time management works only for devices with the Child role. To change a child's device time, open the left sidebar and click Accounts. Find the device in the list and open its edit screen. Adjust the remaining time field to the total number of minutes you want. If the device currently has 20 minutes and you want to add 30 more, enter 50 total minutes. Save when you are done.



B.3.4	Blocking and unblocking devices

You can block a device manually. Go to the sidebar, click Accounts, find the device in the list, and use its Block control. Confirm the action and the device is blocked. The Blocklist shortcut at the top of the Accounts page shows all blocked devices in one place for easy management.

Unblocking follows a similar process. Go to the Accounts page, find the blocked device, and choose Unblock. After confirming, internet access is restored.

B.3.5	Whitelisting devices

Whitelisted devices get unlimited internet access with no time limits or restrictions. To whitelist a device, open Accounts, find the device, and choose Whitelist. Confirm the action to finish. A Whitelist shortcut at the top of the page shows all whitelisted devices together. This feature works well for parent devices, fully trusted devices, or any device that needs constant internet access for work or other important purposes.

B.3.6	Assigning device roles

Each device receives one of three roles: Child, Guest, or Parent. The assigned role determines how the system handles that device. To assign or change a role, open Accounts, locate the device, and use the role control (often a dropdown) that displays the current role. Select the desired role and save if the interface asks you to confirm.

Child role devices are intended for children—these devices have time limits, can be blocked, and activity is monitored. Only Child devices appear on the Child Devices analytics page. Guest role suits visitors who need limited access. Parent role provides full, unlimited internet access, making it ideal for your own devices.

B.4	Website management

B.4.1	Blocking websites

Website blocking helps parents prevent children from accessing inappropriate content. In the current interface you choose between two blocking types.

Domain-level blocking covers the entire website—every page under the hostname you enter. Blocking example.com stops access to that host and, if you enable the option, common subdomains such as www or m. Choose this method when you want a site completely off-limits.

App-level blocking targets a mobile app and the cluster of internet hosts that app needs. After you enter a primary domain (and optionally an app label), the form can suggest related domains; review the list before saving so you understand what will be blocked. This method works best when you want to shut down a mobile app altogether.

Blocking rules are stored per parent account but apply household-wide to every Child device you manage—you do not pick a single child on the block form. To add a rule, open General Settings in the sidebar, expand it if needed, and click Blocked Websites. Use the create action, enter the domain, pick Domain or App, optionally adjust subdomain coverage and related domains, add a short reason if you want, then save. The index page also supports bulk import and export when you need to move a long list between environments.

B.4.2	Flagging websites

Flagged websites stay reachable, while the system records that a child visited them so you can review the event later. Flagged entries are also household-wide. To maintain the list, open General Settings and choose Flagged Websites, then use the add form to capture the host or URL you want to watch.

B.4.3	Viewing access attempts

The system records each attempt a child makes to visit a blocked website. This shows you what restricted content they're trying to access. The detailed table lives under Access Attempts—you can open it from the red Access Attempts button while viewing a child on the Child Devices page, or reach the same tool through your bookmarks if you prefer. Filter by device when you only want one child's rows.

B.5	Quiz management

B.5.1	Creating a quiz

Educational quizzes allow children to earn additional internet time. After their time runs out, they can take a quiz. Passing the quiz grants them more time.

Quiz creation begins in the sidebar under Quiz (the list page heading reads QUIZZES). Choose + New to start a fresh quiz. Enter basic information: a title (such as "Math Quiz - Addition" or "Science Quiz - Planets"), an optional description covering the content, the passing percentage (for example 70 means seventy percent of questions must be answered correctly), and how many minutes of internet time they'll earn upon passing (perhaps 15 or 30 minutes). The same form may offer optional guardrails such as a daily pass limit per child or a cooldown between attempts—set those if you want tighter control.

Next, add your questions inside the builder. Multiple Choice gives kids several options to choose from. True/False is just True or False. Fill-in-the-Blank means they need to type the answer. Type your question, and if it's multiple choice, enter all the answer options and mark which one is correct. Save the quiz when you are satisfied.

You can also import many quizzes at once from a spreadsheet: on the QUIZZES page choose Import Excel, follow the template instructions, and upload the file.

B.5.2	Assigning quizzes to devices

Quizzes are linked to hardware on the create and edit screens. Near the bottom of those forms you will see a checklist of every Child device on your account. Tick the devices that should see this quiz in the portal. Guest and Parent devices never appear in that list because they are outside the child workflow.

B.5.3	Viewing quiz results

The Child Devices page shows a concise QUIZ SCORE card for the selected child—each line states how many questions were answered correctly and whether the attempt passed. For a chronological audit across devices, open Logs in the sidebar, stay on the Child Activity stream, and filter by device or keyword as needed.

B.6	Video management

B.6.1	Adding educational videos

Educational videos offer children another way to earn internet time. Children watch educational videos and, when dictionary mode is enabled, remember vocabulary words that appear on screen during playback.

Adding a video starts in the sidebar under Videos. Choose the create action. You'll enter several details: a title (such as "Introduction to Planets" or "Basic Math Concepts"), an optional description of what it teaches, and upload a video file from your computer. The system accepts MP4, WebM, or OGG files up to 512MB—compress files larger than this first. Enter the video length in seconds, and set how many minutes of internet time they'll earn after completing it (30 minutes works well as a starting point).

Dictionary Words are optional, but enabling them is still recommended when you want an extra attention check. Turn on dictionary words, set how many words should appear during playback, and save. When enabled, words are drawn from the system's built-in dictionary table. Children need to remember and type them all correctly at the end to earn time.

Here are some tips: pick age-appropriate educational videos, keep them short (5-15 minutes works well), make sure they teach relevant content, and always test the video to confirm it plays properly before assigning it.

B.6.2	Dictionary words

The application ships with a seeded dictionary of educational words. When dictionary words are enabled for a video, the player chooses random entries from that dictionary during playback. This helps children pick up new vocabulary while watching.

Here is how it works: during playback, words pop up on screen at intervals you configured. Each word shows for a few seconds before disappearing. Children need to pay attention and remember them all. At the end, they type the words they saw. Getting them all correct earns time. Missing any or getting them wrong means watching the whole video again—with different words appearing at different times.

There is no separate parent menu for editing the global dictionary inside the current build; if you need additional words, ask your technical contact to load them through the maintenance tools or database procedures your deployment uses.

B.6.3	Assigning videos to devices

Videos use the same pattern as quizzes. On the create and edit forms, select the Child devices that should see the video in the portal by ticking the checkboxes provided. Save the form when you are done.

B.7	Captive portal

B.7.1	How the portal works

A special webpage called the captive portal shows up automatically when a child's internet time runs out. It blocks ordinary browsing and displays a special page in its place. After their time expires, many plain HTTP requests will show the portal page instead of the website they wanted.

When a child's internet time runs out, the system stops treating their device as fully online. Any time the child tries to visit a site that gets intercepted by the portal layer, the system sends them to the portal page instead. The portal page displays two options: "Take a Quiz" (complete an educational quiz to earn more internet time) or "Watch a Video" (watch an educational video and remember words to earn more internet time).

After the child successfully completes either a quiz or video, the system awards them more internet time (the amount you set when creating the quiz or video), enables their internet access again, and they can browse websites normally again.

The portal only appears when time has run out—this is important to understand. Children with time remaining can browse the internet normally. There is no way to skip or avoid the portal while they remain out of time—every intercepted request shows the portal page until they complete an educational activity. The system makes sure children actually complete activities: videos cannot be fast-forwarded (children must watch the entire video), and quizzes must be passed according to the percentage you configured.

B.7.2	Portal interface for children

The portal interface is simple and kid-friendly. Children taking quizzes see clear, easy-to-read questions with big buttons for choosing answers. A progress indicator shows where they are (such as "Question 3 of 10"), and they get instant feedback after each answer. When they finish, a clear message tells them whether they passed or need to try again.

For videos, a simple player has play, pause, and volume controls. Children can't fast-forward or skip ahead—they must watch the entire video from start to finish. This makes sure they actually pay attention. Dictionary words pop up on the screen at random times during the video. When the video ends, children need to type in all the words they saw. The system immediately tells them whether they got them all correct or missed any.

Everything is designed for children: large buttons that are easy to tap on tablets and phones, simple instructions that are easy to understand, bright colors that keep things interesting, and encouraging messages when they earn time. If something goes wrong, helpful error messages explain what happened.

B.8	Monitoring and logs

B.8.1	Dashboard overview

After you sign in, the Dashboard page confirms that you are authenticated and shows your account type (for example Parent or Household operator). It is a lightweight home screen rather than a wall of charts. For rich analytics, move to Child Devices, Reports, or Logs using the left sidebar.

The sidebar is organized the way the current build ships:

Under Parent dashboard you will see Dashboard; an expandable General Settings group that contains Blocked Websites, Flagged Websites, and Schedules; Child Devices; Accounts; Reports; Logs; and under Educational Content the Quiz and Videos entries. At the bottom of the sidebar, your name opens Profile and Log Out.

If you have Administration privileges, an Administration section appears above the parent items with shortcuts such as Admin home, Pending parents, and Parent accounts.

B.8.2	Browsing logs

Browsing logs show which websites your children have visited. To check history quickly, open Child Devices, pick a child, and read the WEBSITE HISTORY card. For the full filterable grid, click Browsing History in the yellow header or open the Browsing Logs screen directly from your navigation history—the table lets you filter by device, time range, and text.

Browsing logs help parents in several ways: checking the logs regularly helps parents understand what websites their child visits, this information helps parents decide which websites to block or monitor, looking at patterns over time shows if children are visiting new or concerning websites, and the website history updates as new visits are parsed, so parents see information that is as current as the logging pipeline allows.

B.8.3	Access attempts

Access attempt logs show when children try to visit blocked websites. Use these logs to check that blocking rules work correctly and see attempts to access restricted sites. Open Access Attempts from the Child Devices header after you select a child, or navigate to the standalone Access Attempts page the same way you do for browsing logs. The list shows which hosts were blocked, when the attempts happened, and whether the rule was domain-based or app-based.

B.8.4	Reports and email digests

Open Reports in the sidebar to configure automated email summaries. You can choose the timezone that makes sense for your family (the form defaults to Philippines — Asia/Manila for new accounts), toggle immediate alerts versus daily, weekly, or monthly digests, add one or more recipient addresses, and send a test message to confirm your mail server is working. Recent delivery attempts appear in a read-only list so you can confirm messages left the system.

Remember that digests only send if background workers and SMTP credentials are configured on the server; if emails never arrive, ask your technical contact to verify those services.

B.8.5	Unified log streams

Logs opens a single workspace with two tabs. Child Activity combines browsing, blocked attempts, portal completions, and similar child-facing signals. Parent/Admin Changes collects configuration edits, reporting preference updates, and security-audit rows (such as logins or sensitive saves). Use the shared filters for date range, device, event type, and free-text keywords. When you need a spreadsheet for a school meeting or court packet, use Export Excel on that page.

B.9	Scheduling

The scheduling feature helps parents set rules about when children can use the internet. Set specific times of day when internet access is allowed, along with a maximum daily duration such as no more than two hours per day.

B.9.1	Creating schedules

Schedules provide control over both when children can go online and how long they can stay. Creating a schedule takes a few steps. Open General Settings in the sidebar, expand it if needed, and click Schedules. Choose Create Schedule, pick the child's device you want to set up, and configure the fields shown on the form: which days apply, start and end times, and any daily duration cap. Save to activate.

B.9.2	Managing schedules

Schedules can be edited or deleted anytime as needs change. To edit a schedule, return to Schedules under General Settings, locate the schedule you want to modify, and open it. Make your changes, then save. Deleting a schedule follows the same path: open Schedules, choose Delete on the entry you no longer need, and confirm the removal.

B.10	Troubleshooting

This section provides solutions for common problems that may arise while using the system.

B.10.1	Device cannot connect to WiFi

If a child's device cannot connect to the WiFi network, try these steps in order. Make sure the device's MAC address is entered correctly in the system. Go to the Accounts page from the sidebar and see if the device is blocked—if it shows "Blocked", try unblocking it. Make sure the Raspberry Pi is turned on. The device should try to connect to the correct WiFi network. Make sure you're entering the correct WiFi password. Try turning the child's device off and on again, then try connecting again. The device should be close enough to the Raspberry Pi.

B.10.2	Device not redirected to portal

When a child's device is not showing the portal page after their time runs out, try these steps. Go to the Accounts page from the sidebar and see if the device status shows "Blocked". If it still shows "Active", the system might not have detected that time ran out yet. On the child's device, try refreshing the browser or close the browser completely and open it again. Check the Child Devices charts to see if the device really has zero minutes remaining, or go to the Accounts page for more detailed time information. Try manually blocking the device from the Accounts page, then have the child try to visit a website to test if the portal redirect is working. If nothing works, restart the Raspberry Pi and try again.

B.10.3	Time not being deducted

If you notice that a device's time is not decreasing even though the child is using the internet, check these things. Make sure the device status is "Active" and not "Whitelisted". Make sure the device is connected to WiFi—time decreases when the device is connected and pauses when it's disconnected. Wait a few minutes, so if the child just started browsing, wait a few minutes and check again. Refresh the Child Devices or Accounts page to see the most up-to-date time information.

B.10.4	Quiz/Video not granting time

When a child finishes a quiz or video but doesn't get internet time, check different things depending on what they did. For quizzes, make sure the child's score was high enough to pass. The quiz must have a time reward set. Make sure the device is not whitelisted.

For videos, make sure the child typed all the dictionary words correctly. The child must watch the entire video from beginning to end without skipping or fast-forwarding. The video must have a time reward set.

If time still isn't granted, try waiting a moment and refreshing the Child Devices page. See if the device status changed from "Blocked" to "Active". If the problem continues, restart the Raspberry Pi and try having the child complete another quiz or video.

B.10.5	Website blocking not working

When a blocked website is still accessible, try these steps. Start by verifying you selected the correct blocking type: use Domain blocking to block an entire hostname, and use App blocking to cover a mobile app and its related hosts. Wait a few minutes after adding a blocked website. On the child's device, try clearing the browser's cache or use a different browser. For mobile apps, close the app completely and open it again. Some apps save content on the device. Old pictures or videos might still be visible, but the app won't be able to load new content or connect to the internet. Have the child try to access the website again after waiting a few minutes. Remember that rules apply to Child devices—double-check the device role.

For mobile apps, blocking stops new internet connections but cannot remove content already saved on the device. Even when some old pictures or videos are still visible, the app should not work normally because it cannot load new content or connect to the internet.

B.11	Best practices

B.11.1	Time management

Start small when managing time. Give children 15-30 minutes at first, then adjust based on how they handle it and what they actually need. The scheduling features help set boundaries—such as no internet after bedtime or during study hours. Watching their usage patterns over time helps you figure out what limits work best for your family.

B.11.2	Content filtering

For content filtering, these approaches work well: use domain-level blocking when you want to block an entire website completely. For mobile apps, app-level blocking works best since it blocks all related hosts too. When you're not sure about a site, flag it first and watch it before permanently blocking. Checking those access attempt logs regularly helps identify attempts to visit sites they shouldn't, helping you spot new problems early.

B.11.3	Educational content

Educational content works best when it matches your child. Create quizzes that fit their learning level—not too easy, not too hard. Pick videos that are right for their age. Consider the time rewards: balance learning activities with fun internet time so children stay motivated. Most importantly, checking those quiz results regularly shows how your child is doing and whether they're actually engaging with the educational content.

B.11.4	Security

Security needs attention. Change any installer-supplied administrator password right away after first login—don't wait. Parents should also replace temporary passwords immediately. Create a strong password: at least eight characters, mixing letters, numbers, and symbols. Make it something you'll remember but others can't guess. Review the Parent/Admin Changes log stream now and then for unexpected edits. Keep your software updated when new security patches come out. Watch which devices are connected—when you see something you don't recognize, that's a red flag.

B.12	Support and maintenance

B.12.1	System logs

System logs record what the system is doing and can help identify problems. Technical support people usually use logs to fix problems. The interactive Logs page merges several sources into the two streams described earlier. Reading every technical column still rewards patience—when problems keep happening, contact someone with technical knowledge or technical support for help.


B.13	Glossary

This glossary defines technical terms used throughout this manual.

Active Session: A period when a device is actively using the internet. The system tracks this time and subtracts it from the device's remaining time.

Blocked Website: A website that children cannot visit because you've blocked it. The current form focuses on domain-wide blocking or app-style blocking with related hosts.

Captive Portal: A special webpage that shows up when a child's internet time runs out. The system shows the portal page instead of the website the child wanted, with options to take a quiz or watch a video.

Dictionary Words: Educational vocabulary words shown on screen during video playback when that mode is enabled. Children must remember these words and type them correctly at the end of the video to earn internet time.

Domain-Level Blocking: A method to block an entire website and its pages under a hostname. Blocking "example.com" prevents children from reaching that host, and you may include subdomains when the checkbox is enabled.

Flagged Website: A website that is monitored but not blocked. Visits still appear in logs so you can review them and decide whether to block later.

MAC Address: A unique code that every device has, like a fingerprint. MAC addresses look like AA:BB:CC:DD:EE:FF. The system uses this address to tell devices apart and control them individually.

NoDogSplash: The software on the Raspberry Pi that enforces many captive-portal redirects. It captures eligible HTTP requests and sends them to the portal page instead.

Time Allocation: The amount of internet time you assign to a device, measured in minutes. Typical amounts are 30 minutes, 60 minutes, or 120 minutes.

Time Grant: Additional internet time that a device receives after a child successfully completes a quiz or video. You set how much time to grant when you create the quiz or video.

Whitelisted Device: A device that has unlimited internet access with no time limits. This is useful for parent devices that should always have internet access.
