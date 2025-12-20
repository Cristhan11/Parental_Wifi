APPENDIX B: USER MANUAL

This is the user manual for the Child-Centric WiFi Monitoring and Control System. We've written this guide specifically for parents who want to monitor and control their children's internet access. You'll find step-by-step instructions written in plain language—no technical background needed. This manual covers all the features that are currently working and ready to use.


B.1	System Overview

The Child-Centric WiFi Monitoring and Control System functions as a parental control solution designed to help parents manage their children's internet usage. The system operates on a Raspberry Pi 4B. This device creates a dedicated WiFi network specifically intended for children's devices.

Children's devices connect only to this dedicated network. Parents have the ability to see exactly how much time their children spend online, and they can set time limits as needed. Once a child's internet time runs out, a portal page automatically appears. This page gives children two options: they can take an educational quiz or watch an educational video. If children finish either activity successfully, they get more internet time added to their account. Parents also have the option to block websites and mobile apps they don't want children accessing. Everything children visit gets logged, and parents can check this log whenever they want.

B.1.1	System Setup

A Raspberry Pi 4B serves as the system's main computer. This single device handles all the system functions. The Raspberry Pi creates a WiFi network that children's devices connect to. A dashboard website runs on the same device, which is where parents manage everything. The Raspberry Pi handles both website access control and time tracking. Essentially, one device combines the functions of a WiFi router and a web server.

Storage comes from a 480GB solid-state drive, or SSD. Compared to traditional hard drives, SSDs run faster and tend to be more reliable. A USB adapter cable connects the SSD to the Raspberry Pi. The reason we went with SSDs is their ability to run continuously without breaking down. This matters a lot since the system never shuts off—it's always logging internet usage, storing video files, and recording which websites children visit.

Power comes from a standard phone charger—specifically a 5V/3A USB-C power supply connected with a USB-C cable. Since the device never turns off, cooling becomes essential to prevent overheating. The system uses heat sinks (small metal components) plus a cooling fan. These components work together to maintain safe operating temperatures, following the same cooling approach used in desktop computers.

Internet connection happens through two methods. One Ethernet cable connects the Raspberry Pi to the home WiFi router, giving it internet access. A separate WiFi network called "Parental_WiFi" is created by the Raspberry Pi specifically for children's devices. When children connect their phones or tablets to this network, all their internet traffic flows through the Raspberry Pi before reaching the internet. Since every request must go through the Raspberry Pi first, the system can monitor and control what children access online.

To set up the system, you'll need a home WiFi router with an available Ethernet port. You also need an active internet connection from your provider—PLDT is one example. The router must be configured to allow devices to connect via Ethernet cable. The system operates independently from your main home WiFi network. Children's devices receive internet access through the system, which uses your home internet connection. Parents gain control over children's internet access with this configuration, while their own devices remain on the regular home WiFi network where they have full, unrestricted access.

B.1.2	Accessing the System

Parents can open the dashboard on computers, tablets, or smartphones. The device just needs to be connected to the home WiFi network. Start by opening a web browser—Chrome, Firefox, Safari, and Edge all work. Make sure the device is connected to the home WiFi network first. Then type http://192.168.4.1 into the browser's address bar and press Enter. This IP address points to where the parent dashboard is located. The dashboard should load right away after you press Enter.

   B.2	Initial Setup

   B.2.1	First Login

   Opening the system for the first time brings up a login page. A default account is already configured, so you can start immediately. Enter admin@parentalwifi.local as the email address and admin123 as the password.

Important: change that default password immediately after logging in. The default password is publicly known, so long-term use creates a security risk.

Logging in involves typing admin@parentalwifi.local in the email field and admin123 in the password field, then clicking "Log In" or pressing Enter. The Parent Dashboard loads after login, showing an overview of your children's devices, their time usage, quiz results, and system status.

   B.2.2	Changing Your Password

   Changing passwords regularly improves security. After logging in, locate the left sidebar menu and click "ACCOUNTS". The "Change Password" section appears on that page. Type your current password first, then enter your new password. The system recommends at least 8 characters combining letters, numbers, and symbols for better security. Confirm the new password by typing it twice. Click "Update Password" or "Save Changes" to complete the process.


   B.3	Device Management

   B.3.1	Adding a New Device

   Before a child's device can access the WiFi network, you must add it to the system. Each device has a unique identifier called a MAC address, which works like a fingerprint for that specific device. MAC addresses remain constant—phones, tablets, and computers each have their own. MAC addresses appear as letters and numbers separated by colons, such as AA:BB:CC:DD:EE:FF. The system uses this address to identify individual devices and apply the appropriate rules to each child's device.

   Adding a child's device involves a few simple steps. Open the left sidebar menu and click "ACCOUNTS". Find the "+ New" button in the top right corner of the page. A registration form opens for entering several details. Assign the device a friendly name (like "John's iPhone" or "Sarah's Tablet"). Enter the device's MAC address. Select the device role (choose "CHILD" for time restrictions and monitoring features). Set the initial time allocation in minutes—30 or 60 minutes works well to start. Click "Save Device" or "Add Device" after completing all fields to finish registration. The device appears in your ACCOUNTS table. Devices with the CHILD role also appear on the CHILD DEVICES page, where statistics and activity monitoring are available.

   The method for finding a device's MAC address varies by device type. iPhone or iPad users should open Settings, tap General, then About. The Wi-Fi Address appears here—this is the MAC address. Android users need to go to Settings, then About Phone (or About Device), and locate Status or Hardware Information. The Wi-Fi MAC Address is listed there.

   Windows users must press the Windows key and R simultaneously, type cmd, and press Enter. This opens the command prompt. Type ipconfig /all and press Enter again. Under the WiFi adapter section, find "Physical Address"—this is your MAC address. Mac users should click the Apple menu, navigate to System Preferences, then Network. Select Wi-Fi from the list, click Advanced, then the Hardware tab. The MAC address is displayed here.

B.3.2	Viewing Device Status

All device management happens on the ACCOUNTS page, which serves as your control center. Click "ACCOUNTS" in the left sidebar menu to access it. A table displays all registered devices.

Devices can have one of three statuses. "Active" status indicates time remains and browsing functions normally. When status shows "Blocked", time has run out—children trying to visit any website see the portal page instead of their intended destination. "Whitelisted" devices receive unlimited internet access without restrictions, making this status ideal for parent devices requiring constant access.

Each device entry on the ACCOUNTS page displays multiple items: the MAC address (the unique identifier like "AA:BB:CC:DD:EE:FF"), the assigned role (CHILD, GUEST, or PARENT), the friendly name you chose (such as "John's iPhone"), the current status (Active, Blocked, or Whitelisted), along with buttons for editing, blocking, unblocking, or managing that device.

The left sidebar includes a CHILD DEVICES page, but it displays only devices with the "CHILD" role. This page features graphs tracking time usage, quiz scores and results, along with a history of websites visited. Parents can see a complete overview of their child's online activity and educational performance.

B.3.3	Managing Device Time

Internet time management works only for devices with the "CHILD" role. To change a child's device time, open the left sidebar menu and click "ACCOUNTS". Find the device in the list and click its "Edit" button. On the edit page, look for the "Remaining Time" field and enter the total number of minutes you want. If the device currently has 20 minutes and you want to add 30 more, enter 50 total minutes. Click "Save Changes" when you're done.



B.3.4	Blocking and Unblocking Devices

You can block a device manually. Go to the left sidebar, click "ACCOUNTS", find the device in the list, and click its "Block" button. Confirm the action and the device is blocked. The "Blocklist" button at the top of the ACCOUNTS page shows all blocked devices in one place for easy management.

Unblocking follows a similar process. Go to the ACCOUNTS page, find the blocked device, and click "Unblock". After confirming, internet access is restored.

B.3.5	Whitelisting Devices

Whitelisted devices get unlimited internet access with no time limits or restrictions. To whitelist a device, go to the left sidebar, click "ACCOUNTS", find the device, and click "Whitelist". Confirm the action to finish. Clicking the "Whitelist" button at the top of the page shows all whitelisted devices together. This feature works well for parent devices, fully trusted devices, or any device that needs constant internet access for work or other important purposes.

B.3.6	Assigning Device Roles

Each device receives one of three roles: CHILD, GUEST, or PARENT. The assigned role determines how the system handles that device. To assign or change a role, go to the left sidebar, click "ACCOUNTS", locate the device, and click the role dropdown (which displays the current role). Select the desired role and it updates automatically.

CHILD role devices are intended for children—these devices have time limits, can be blocked, and all activity is monitored. Only CHILD devices appear on the CHILD DEVICES page with statistics and reports. GUEST role suits visitors who need limited access. PARENT role provides full, unlimited internet access, making it ideal for your own devices.

B.4	Website Management

B.4.1	Blocking Websites

Website blocking helps parents prevent children from accessing inappropriate content. Three blocking options are available.

URL-level blocking targets just one specific page. If you block https://facebook.com/page, only that page is blocked while other Facebook pages stay accessible. Choose this option when blocking something specific while keeping the rest of the site available.

Domain-level blocking covers the entire website—every page. Blocking facebook.com stops access to any part of Facebook, including www.facebook.com, m.facebook.com, or any other Facebook pages. Choose this method when you want a site completely off-limits.

App-level blocking targets mobile apps and all websites those apps connect to. Apps like Facebook connect to dozens of sites (sometimes 30 or more) to function properly. Blocking the Facebook app blocks facebook.com plus all related sites the app requires. This method works best when completely shutting down a mobile app.

Blocking a website takes just a few steps. Go to the left sidebar and click "CHILD DEVICES", then pick which child's device you want to block websites for. Click the "Add Blocked Website" or "Block Website" button. A form opens for entering the website address (such as facebook.com or https://www.facebook.com). Pick the blocking method you want. Select "URL" to block only one specific page, "Domain" to block the entire website, or "App" to block a mobile app and all its related websites. When you pick "App", the system shows you a list of all websites that will be blocked along with the app. Check the list, then click "Save" or "Block Website" to activate it.

B.4.2	Flagging Websites

Flagged websites stay accessible, and the system keeps an eye on them. If a child visits a flagged site, you'll get a notification so you can decide what to do. To flag a website, go to the left sidebar menu, select "CHILD DEVICES", pick the device you want to monitor, and click "Add Flagged Website". Enter the website URL and click "Save" to start monitoring.

B.4.3	Viewing Access Attempts

The system records each attempt a child makes to visit a blocked website. This shows you what restricted content they're trying to access. To view these attempts, open the left sidebar menu, click "LOGS", and pick the device you want to check. You'll see a list with which websites were blocked, when the attempts happened, and device details.

B.5	Quiz Management

B.5.1	Creating a Quiz

Educational quizzes allow children to earn additional internet time. After their time runs out, they can take a quiz. Passing the quiz grants them more time.

Quiz creation begins in the left sidebar—click "QUIZ", then "Create Quiz" or "New Quiz". Enter basic information: a title (such as "Math Quiz - Addition" or "Science Quiz - Planets"), an optional description covering the content, the passing score (70% means 7 out of 10 questions must be correct), and how many minutes of internet time they'll earn upon passing (perhaps 15 or 30 minutes).

Next, add your questions. Click "Add Question" or "New Question" and pick the question type. Multiple Choice gives kids several options (A, B, C, D, etc.) to choose from. True/False is just True or False. Fill-in-the-Blank means they need to type the answer. Type your question, and if it's multiple choice, enter all the answer options and mark which one is correct. Click "Save Question" to add it, then do the same for more questions. When you're done, click "Save Quiz" or "Create Quiz" to finish it.


B.5.2	Assigning Quizzes to Devices

Assigning a quiz to a device is simple. Go to the left sidebar and click "QUIZ", find the quiz you want to use, then click "Assign to Devices". Pick which devices should have access to that quiz, and click "Save Assignments" to finish.

B.5.3	Viewing Quiz Results

You can view quiz results quickly. From the left sidebar, click "QUIZ" and pick the quiz you want to review. Click "View Attempts" to see all attempts. Each attempt displays the child's name or device, their score percentage, whether they passed or failed, when they completed it, and how much time they earned if they passed.

B.6	Video Management

B.6.1	Adding Educational Videos

Educational videos offer children another way to earn internet time. Children watch educational videos and need to remember vocabulary words that show up on screen during playback.

Adding a video starts in the left sidebar—click "VIDEOS", then "Add Video" or "Upload Video". You'll enter several details: a title (such as "Introduction to Planets" or "Basic Math Concepts"), an optional description of what it teaches, and upload a video file from your computer (click "Choose File" or "Upload"). The system accepts MP4, WebM, or OGG files up to 512MB—compress files larger than this first. Enter the video length in seconds (though the system might detect this automatically), and set how many minutes of internet time they'll earn after completing it (30 minutes works well as a starting point).

Dictionary Words are optional, but enabling them is recommended. Check the "Enable Dictionary Words" box to show vocabulary during playback, and set the "Word Count" (perhaps 5 words for a 10-minute video). When enabled, random dictionary words show up at various times during the video. Children need to remember and type them all correctly at the end to earn time. Click "Save Video" or "Upload Video" when you're done.

Here are some tips: pick age-appropriate educational videos, keep them short (5-15 minutes works well), make sure they teach relevant content, and always test the video to confirm it plays properly before assigning it.

B.6.2	Dictionary Words

The system includes a built-in dictionary of educational words. When you enable dictionary words for a video, random words from this dictionary show up during playback. This helps children pick up new vocabulary while watching.

Here's how it works: when a child watches a video with dictionary words enabled, words pop up on screen at random moments (perhaps at 2 minutes, 5 minutes, and 8 minutes). Each word shows for a few seconds before disappearing. Children need to pay attention and remember them all. At the end, they type in all the words they saw. Getting them all correct earns time. Missing any or getting them wrong means watching the whole video again—with different words appearing at different times.

Adding dictionary words starts in the left sidebar—click "VIDEOS" (where dictionary words are managed), then "Add Word" or "New Word". Enter the word (such as "adventure", "curious", or "discover"), its definition (such as "an exciting experience"), and pick a difficulty level (Easy, Medium, or Hard). Click "Save Word" to add it. For multiple words, use the "Import Words" feature to add several at once—check the system documentation for the exact format.

B.6.3	Assigning Videos to Devices

Assigning a video is straightforward. Open the left sidebar menu and click "VIDEOS", find the video you want to use, then click "Assign to Devices". Pick which devices should have access, and click "Save Assignments" to finish.

B.7	Captive Portal

B.7.1	How the Portal Works

A special webpage called the captive portal shows up automatically when a child's internet time runs out. It blocks all websites and displays a special page in their place. After their time expires, any website visit shows the portal page instead of the website they wanted.

When a child's internet time runs out, the system stops their device from accessing the internet. Any time the child tries to visit a website, the system sends them to the portal page instead. The portal page displays two options: "Take a Quiz" (complete an educational quiz to earn more internet time) or "Watch a Video" (watch an educational video and remember words to earn more internet time).

After the child successfully completes either a quiz or video, the system awards them more internet time (the amount you set when creating the quiz or video), enables their internet access again, and they can browse websites normally again.

The portal only appears when time has run out—this is important to understand. Children with time remaining can browse the internet normally. There's no way to skip or avoid the portal—every website they try to visit shows the portal page until they complete an educational activity. The system makes sure children actually complete activities: videos cannot be fast-forwarded (children must watch the entire video), and quizzes must be passed (children must answer enough questions correctly based on the passing score you set).

B.7.2	Portal Interface for Children

The portal interface is simple and kid-friendly. Children taking quizzes see clear, easy-to-read questions with big buttons for choosing answers. A progress indicator shows where they are (such as "Question 3 of 10"), and they get instant feedback after each answer. When they finish, a clear message tells them whether they passed or need to try again.

For videos, a simple player has play, pause, and volume controls. Children can't fast-forward or skip ahead—they must watch the entire video from start to finish. This makes sure they actually pay attention. Dictionary words pop up on the screen at random times during the video. When the video ends, children need to type in all the words they saw. The system immediately tells them whether they got them all correct or missed any.

Everything is designed for children: large buttons that are easy to tap on tablets and phones, simple instructions that are easy to understand, bright colors that keep things interesting, and encouraging messages when they earn time. If something goes wrong, helpful error messages explain what happened.

B.8	Monitoring and Logs

B.8.1	Dashboard Overview

After logging in, the Parent Dashboard is your main page. It shows a quick overview of everything happening with your children's devices, their internet usage, quiz results, and system status. The dashboard puts information into four main sections, each shown in its own card or box.

The TIME USAGE card (top-left) shows a list of all your children's devices. Each child's name shows up (such as "HONEY MAY" or "CRISTHAN") along with how much time they've used today, shown in a yellow badge (like "1h46m" meaning 1 hour and 46 minutes). The current date shows at the top. This card helps you quickly see how much time each child has spent online today.

The QUIZ RESULTS card (top-right) shows how well your children did on quizzes. Quiz names show up (such as "QUIZ 1" or "QUIZ 2") with each child's score shown as a fraction (like "1/5" means they got 1 out of 5 questions correct, "4/5" means they got 4 out of 5 correct). Scores show up in yellow badges next to each child's name. When there are multiple quizzes, scrolling down shows them all. This card helps you track how well your children are learning.

The GRAPHICAL REPRESENTATION card (bottom-left) shows a line graph of internet usage over time. The bottom of the graph shows months from January to December, while the side shows how much time was used. A yellow line shows the usage pattern throughout the year, helping you spot patterns in how much your children use the internet.

The Progress Bars card (bottom-right) shows two progress bars: QUIZ REMAINING shows how many quizzes are still available for children to complete (shown as a green progress bar), and VIDEO REMAINING shows how many videos are still available for children to watch (also shown as a green progress bar). This card helps you quickly see how much educational content is still available.

A menu on the left side of the screen includes these options: GENERAL SETTING (for system settings), CHILD DEVICES (to manage children's devices and view statistics), ACCOUNTS (to manage devices and passwords), QUIZ (to create and manage quizzes), VIDEOS (to upload and manage videos), REPORTS (to view reports), LOGS (to view browsing history and access attempts), and LOG-OUT (to sign out of the system).



B.8.2	Browsing Logs

Browsing logs show exactly which websites your children have visited. This gives you a helpful way to track their internet activity. To check the browsing history, go to the left sidebar menu and click "CHILD DEVICES". Use the dropdown at the top to pick which child's device you want to review, then scroll down to find the "WEBSITE HISTORY" card. A list shows up with all websites that device has visited—each entry shows the website name, such as "facebook.com" or "youtube.com".

Browsing logs help parents in several ways: checking the logs regularly helps parents understand what websites their child visits, this information helps parents decide which websites to block or monitor, looking at patterns over time shows if children are visiting new or concerning websites, and the website history updates automatically as children browse the internet, so parents always see current information.

B.8.3	Access Attempts

Access attempt logs show when children try to visit blocked websites. Use these logs to check that blocking rules work correctly and see attempts to access restricted sites. To view these attempts, open the left sidebar menu, click "LOGS", then pick "Access Attempts" or "Blocked Attempts". Pick the device you want to check. The list shows which blocked websites they tried to visit, when they tried, and what type of blocking stopped them URL, Domain, or App.

B.9	Scheduling

The scheduling feature helps parents set rules about when children can use the internet. Set specific times of day when internet access is allowed, along with a maximum daily time limit like no more than 2 hours per day.

B.9.1	Creating Schedules

Schedules provide control over both when children can go online and how long they can stay. Creating a schedule takes a few steps. Go to the left sidebar menu, click "GENERAL SETTING", then select "Create Schedule" or "New Schedule". Pick the child's device you want to set up. Configure several settings. Day of Week determines which days this applies. Start Time defines when they can begin. End Time sets when they must stop. Duration Limit caps how much time they can use per day. After completing these settings, click "Save Schedule" to activate it.

B.9.2	Managing Schedules

Schedules can be edited or deleted anytime as needs change. To edit a schedule, go to the left sidebar menu, click "GENERAL SETTING", find the schedule you want to modify, and click "Edit". Make your changes, then click "Save" or "Update Schedule" to apply them. Deleting a schedule follows the same process, go to "GENERAL SETTING", find the schedule you want to remove, click "Delete", and confirm the removal.

B.10	Troubleshooting

This section provides solutions for common problems that may arise while using the system.

B.10.1	Device Cannot Connect to WiFi

If a child's device cannot connect to the WiFi network, try these steps in order. Make sure the device's MAC address is entered correctly in the system. Go to the ACCOUNTS page from the left sidebar menu and see if the device is blocked, if it shows "Blocked", try unblocking it. Make sure the Raspberry Pi is turned on. The device should try to connect to the correct WiFi network. Make sure you're entering the correct WiFi password. Try turning the child's device off and on again, then try connecting again. The device should be close enough to the Raspberry Pi.

B.10.2	Device Not Redirected to Portal

When a child's device is not showing the portal page after their time runs out, try these steps. Go to the ACCOUNTS page from the left sidebar menu and see if the device status shows "Blocked". If it still shows "Active", the system might not have detected that time ran out yet. On the child's device, try refreshing the browser or close the browser completely and open it again. The Parent Dashboard TIME USAGE card shows if the device really has 0 minutes remaining, or go to the ACCOUNTS page for more detailed time information. Try manually blocking the device from the ACCOUNTS page, then have the child try to visit a website to test if the portal redirect is working. If nothing works, there might be a problem with the system and needs restart.

B.10.3	Time Not Being Deducted

If you notice that a device's time is not decreasing even though the child is using the internet, check these things. Make sure the device status is "Active" and not "Whitelisted". Make sure the device is connected to WiFi—time decreases when the device is connected and pauses when it's disconnected. Wait a few minutes, so if the child just started browsing, wait a few minutes and check again. Refresh the Parent Dashboard or the CHILD DEVICES page to see the most up-to-date time information.

B.10.4	Quiz/Video Not Granting Time

When a child finishes a quiz or video but doesn't get internet time, check different things depending on what they did. For quizzes, make sure the child's score was high enough to pass. The quiz must have a time reward set. Make sure the device is not whitelisted.

For videos, make sure the child typed all the dictionary words correctly. The child must watch the entire video from beginning to end without skipping or fast-forwarding. The video must have a time reward set.

If time still isn't granted, try waiting a moment and refreshing the Parent Dashboard or CHILD DEVICES page. See if the device status changed from "Blocked" to "Active" in the CHILD DEVICES page. If the problem continues restart the raspberyy pi and try having the child complete another quiz or video.

B.10.5	Website Blocking Not Working

When a blocked website is still accessible, try these steps. Start by verifying you selected the correct blocking type: use "Domain" blocking (not "URL" blocking) to block an entire website, and use "App" blocking to block a mobile app. Wait a few minutes after adding a blocked website. On the child's device, try clearing the browser's cache or use a different browser. For mobile apps, close the app completely and open it again. Some apps save content on the device. Old pictures or videos might still be visible, but the app won't be able to load new content or connect to the internet. Have the child try to access the website again after waiting a few minutes. Verify that you blocked the website for the correct device.

For mobile apps, blocking stops new internet connections but cannot remove content already saved on the device. Even when some old pictures or videos are still visible, the app should not work normally because it cannot load new content or connect to the internet.

B.11	Best Practices

B.11.1	Time Management

Start small when managing time. Give children 15-30 minutes at first, then adjust based on how they handle it and what they actually need. The scheduling features help set boundaries—such as no internet after bedtime or during study hours. Watching their usage patterns over time helps you figure out what limits work best for your family.

B.11.2	Content Filtering

For content filtering, these approaches work well: use domain-level blocking when you want to block an entire website completely. For mobile apps, app-level blocking works best since it blocks all related websites too. When you're not sure about a site, flag it first and watch it before permanently blocking. Checking those access attempt logs regularly helps identify attempts to visit sites they shouldn't, helping you spot new problems early.

B.11.3	Educational Content

Educational content works best when it matches your child. Create quizzes that fit their learning level—not too easy, not too hard. Pick videos that are right for their age. Consider the time rewards: balance learning activities with fun internet time so children stay motivated. Most importantly, checking those quiz results regularly shows how your child is doing and whether they're actually engaging with the educational content.

B.11.4	Security

Security needs attention. Change that default password right away after logging in for the first time—don't wait. Create a strong password: at least 8 characters, mixing letters, numbers, and symbols. Make it something you'll remember but others can't guess. Check your system logs now and then for anything that looks suspicious. Keep your software updated when new security patches come out. Watch which devices are connected—when you see something you don't recognize, that's a red flag.

B.12	Support and Maintenance

B.12.1	System Logs

System logs record what the system is doing and can help identify problems. Technical support people usually use logs to fix problems. The system keeps different types of logs: Application Logs record what the system is doing in general, Access Logs record when children try to visit websites, and Error Logs record problems or warnings that need attention. Reading and understanding system logs usually requires technical knowledge—this is important to know. When problems keep happening, contact someone with technical knowledge or technical support for help.


B.13	Glossary

This glossary defines technical terms used throughout this manual.

Active Session: A period when a device is actively using the internet. The system tracks this time and subtracts it from the device's remaining time.

Blocked Website: A website that children cannot visit because you've blocked it. The system prevents the device from loading the website. Three blocking methods are available: blocking just one specific page (URL blocking), blocking an entire website and all its pages (Domain blocking), or blocking a mobile app and all the websites it uses (App blocking).

Captive Portal: A special webpage that shows up when a child's internet time runs out. The system shows the portal page instead of the website the child wanted, with options to take a quiz or watch a video.

Dictionary Words: Educational vocabulary words shown on screen during video playback. Children must remember these words and type them correctly at the end of the video to earn internet time.

Domain-Level Blocking: A method to block an entire website and all its pages. Blocking "facebook.com" prevents children from accessing any part of Facebook, such as www.facebook.com, m.facebook.com, and other Facebook pages.

Flagged Website: A website that is monitored but not blocked. You receive a notification when a child visits a flagged website, then you can review it and decide whether to block it.

MAC Address: A unique code that every device has, like a fingerprint. MAC addresses look like AA:BB:CC:DD:EE:FF. The system uses this address to tell devices apart and control them individually.

NoDogSplash: The software that makes the portal page work. It captures all website requests and sends them to the portal page instead.

Time Allocation: The amount of internet time you assign to a device, measured in minutes. Typical amounts are 30 minutes, 60 minutes, or 120 minutes.

Time Grant: Additional internet time that a device receives after a child successfully completes a quiz or video. You set how much time to grant when you create the quiz or video.

Whitelisted Device: A device that has unlimited internet access with no time limits. This is useful for parent devices that should always have internet access.

