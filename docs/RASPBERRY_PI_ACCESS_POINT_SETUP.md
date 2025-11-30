# Raspberry Pi WiFi Access Point Setup Guide

## Overview

This guide will help you configure your Raspberry Pi 4B as a WiFi access point. This is a **required prerequisite** before the shell scripts (TODO #12) can function. The access point allows child devices to connect to the Pi's WiFi network, which is then controlled by our Laravel application.

## What is a WiFi Access Point?

A **WiFi Access Point (AP)** is a device that creates a wireless network that other devices can connect to. Think of it like a WiFi router - it broadcasts a network name (SSID) that devices can see and connect to.

**In our system:**
- The Raspberry Pi creates a WiFi network (e.g., "Parental_WiFi")
- Child devices (phones, tablets, laptops) connect to this network
- The Pi controls all internet access for connected devices
- This allows us to block/unblock devices, track usage, and redirect to the captive portal

## Networking Fundamentals (For Beginners)

Before we start configuring, let's understand the key networking concepts you'll encounter:

### What is an IP Address?

**IP Address (Internet Protocol Address)** is like a home address for devices on a network. Just like your house has a street address, every device on a network needs an IP address so other devices know where to send data.

**Example:** `192.168.4.1`
- This is the Pi's IP address on the WiFi network
- Format: Four numbers separated by dots (each number is 0-255)
- Think of it as: "Device number 1 on network 192.168.4"

**Why we need it:**
- Devices use IP addresses to find and communicate with each other
- When your phone wants to send data to the Pi, it uses the IP address `192.168.4.1`
- Without IP addresses, devices wouldn't know where to send information

### What is a MAC Address?

**MAC Address (Media Access Control Address)** is like a device's permanent ID card. Unlike IP addresses (which can change), MAC addresses are unique to each device's network adapter and never change.

**Example:** `AA:BB:CC:DD:EE:FF`
- Format: Six pairs of hexadecimal characters (0-9, A-F) separated by colons
- Every WiFi adapter, Ethernet card, etc. has a unique MAC address
- Think of it as: "This device's fingerprint"

**Why we need it:**
- We use MAC addresses to identify and control specific devices
- Even if a device changes its IP address, we can still identify it by MAC address
- Our blocking scripts use MAC addresses to block/unblock devices

### What is DHCP?

**DHCP (Dynamic Host Configuration Protocol)** is like an automatic address assignment system. When a device connects to a network, DHCP automatically gives it an IP address, so you don't have to manually configure each device.

**Real-world analogy:** Think of DHCP as a hotel receptionist. When you check in, they automatically assign you a room number. You don't have to pick one yourself.

**Why we need it:**
- Without DHCP, you'd have to manually set an IP address on every device that connects
- DHCP automatically assigns IP addresses from a pool (like 192.168.4.2 to 192.168.4.51)
- Makes it easy for devices to connect - they just connect and get an IP automatically

**How it works:**
1. Device connects to WiFi network
2. Device asks: "Hey, I need an IP address!"
3. DHCP server (dnsmasq) responds: "Here's your IP: 192.168.4.2"
4. Device uses that IP address to communicate

### What is DNS?

**DNS (Domain Name System)** is like a phone book for the internet. It translates human-readable website names (like "google.com") into IP addresses (like "142.250.191.14") that computers understand.

**Real-world analogy:** You know your friend's name, but to call them, you need their phone number. DNS is like looking up their name in a phone book to find their number.

**Why we need it:**
- Humans remember "google.com" better than "142.250.191.14"
- DNS automatically converts website names to IP addresses
- Without DNS, you'd have to type IP addresses instead of website names

**How it works:**
1. You type "google.com" in your browser
2. Your device asks DNS server: "What's the IP address for google.com?"
3. DNS server responds: "It's 142.250.191.14"
4. Your device connects to that IP address

### What is NAT?

**NAT (Network Address Translation)** is like a translator that allows multiple devices to share one internet connection. It hides all the internal device IP addresses behind the Pi's IP address.

**Real-world analogy:** Think of an office building with one main phone number. When someone calls, the receptionist (NAT) routes the call to the right person (device) inside the building.

**Why we need it:**
- Your home router typically has one public IP address from your ISP
- But you have many devices that need internet access
- NAT allows all devices to share that one IP address
- It also provides security by hiding internal IP addresses from the internet

**How it works:**
1. Device (192.168.4.2) wants to visit google.com
2. Request goes to Pi (192.168.4.1)
3. Pi translates the request: "This is from 192.168.4.2, but I'll send it as if it's from me"
4. Internet sees the request coming from Pi's public IP
5. Response comes back to Pi
6. Pi translates it back: "This response is for 192.168.4.2"
7. Pi forwards response to the correct device

### What is IP Forwarding?

**IP Forwarding** allows the Raspberry Pi to act as a router - it can receive network packets on one interface (like WiFi) and forward them to another interface (like Ethernet).

**Real-world analogy:** Think of a mail sorting facility. Mail comes in, gets sorted, and forwarded to the correct destination. IP forwarding does the same for network data.

**Why we need it:**
- By default, Linux only processes packets meant for itself
- We need the Pi to forward packets between WiFi (wlan0) and Ethernet (eth0)
- Without IP forwarding, devices on WiFi can't access the internet through Ethernet

**What happens without it:**
- Device connects to WiFi ✓
- Device gets IP address ✓
- Device tries to access internet ✗ (packets aren't forwarded)

### What is a Subnet Mask?

**Subnet Mask** defines which part of an IP address is the "network" and which part is the "device". It's like defining which houses are on the same street.

**Example:** `255.255.255.0` (or `/24` in shorthand)
- This means the first 3 numbers (192.168.4) identify the network
- The last number (like .1, .2, .3) identifies individual devices
- So 192.168.4.1, 192.168.4.2, 192.168.4.3 are all on the same network

**Why we need it:**
- Tells devices which IP addresses are on the same local network
- Devices on the same network can talk directly to each other
- Devices on different networks need to go through a router

**Example breakdown:**
- IP: `192.168.4.1`
- Subnet: `255.255.255.0` (or `/24`)
- Network part: `192.168.4` (first 24 bits)
- Device part: `.1` (last 8 bits)
- This means devices from 192.168.4.1 to 192.168.4.254 are on the same network

### What is a Gateway?

**Gateway** is the device that connects your local network to other networks (like the internet). It's like the front door of your house - all traffic going outside goes through it.

**In our setup:** The Pi (192.168.4.1) is the gateway
- Devices send internet requests to the Pi
- Pi forwards them to the internet (via Ethernet)
- Responses come back through the Pi to the devices

**Why we need it:**
- Devices need to know where to send traffic that's not on the local network
- When a device wants to visit "google.com", it sends the request to the gateway (Pi)
- Gateway forwards it to the internet

### What is SSID?

**SSID (Service Set Identifier)** is the name of your WiFi network that appears when devices scan for networks. It's what you see in the list of available WiFi networks.

**Example:** "Parental_WiFi"
- This is the name users will see when looking for WiFi networks
- You can change it to anything you want
- It's like naming your WiFi network

**Why we need it:**
- Helps users identify which network to connect to
- Multiple WiFi networks can exist in the same area
- SSID distinguishes your network from others

### What is WPA2?

**WPA2 (WiFi Protected Access 2)** is a security protocol that encrypts WiFi traffic. It prevents unauthorized people from accessing your network or seeing your data.

**Real-world analogy:** Like a lock on your front door. Only people with the key (password) can enter.

**Why we need it:**
- Without encryption, anyone nearby could connect to your network
- Without encryption, anyone could see all the data being transmitted
- WPA2 encrypts all WiFi traffic so only authorized devices can access it

**How it works:**
1. Device tries to connect to WiFi network
2. Network asks: "What's the password?"
3. Device provides password
4. Network verifies password is correct
5. If correct, device is allowed to connect and all traffic is encrypted

### What are iptables?

**iptables** is Linux's built-in firewall tool. It controls what network traffic is allowed or blocked. Think of it as a security guard that checks every packet of data.

**Why we need it:**
- Controls which devices can access the internet
- Blocks unauthorized access
- Our blocking scripts use iptables to block/unblock devices
- Provides security by filtering network traffic

**How it works:**
- iptables has "chains" (like checkpoints)
- Each packet of data goes through these chains
- Rules in chains decide: Allow, Block, or Forward the packet
- Our scripts add/remove rules to control device access

## Prerequisites

Before starting, ensure you have:
- ✅ Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit) installed
- ✅ SSH access to the Pi (or direct keyboard/monitor access)
- ✅ Internet connection (for downloading packages)
- ✅ Basic Linux command line knowledge (we'll explain everything!)

## Step-by-Step Setup

### Step 1: Update System Packages

**What we're doing:** Updating the package list to get the latest software versions.

**Why it's important:** Ensures we install the latest, most secure versions of all software.

```bash
# Update package list (refreshes list of available software)
sudo apt update

# Upgrade existing packages to latest versions
sudo apt upgrade -y
```

**Explanation:**
- `sudo` = Run command as administrator (needed for system changes)
- `apt` = Advanced Package Tool (software package manager for Debian/Ubuntu)
- `update` = Refresh the list of available packages from repositories
- `upgrade` = Install newer versions of already-installed packages
- `-y` = Automatically answer "yes" to all prompts (non-interactive)

### Step 2: Install Required Software

**What we're doing:** Installing three essential packages for the access point.

**Why each package is needed:**
1. **hostapd** - Creates and manages the WiFi access point
2. **dnsmasq** - Provides DHCP (assigns IP addresses) and DNS (domain name resolution)
3. **iptables-persistent** - Saves firewall rules so they survive reboots

```bash
# Install all required packages at once
sudo apt install -y hostapd dnsmasq iptables-persistent
```

**Explanation:**
- `install` = Download and install software packages
- `hostapd` = Host Access Point Daemon (creates WiFi network)
- `dnsmasq` = Lightweight DHCP and DNS server
- `iptables-persistent` = Saves iptables firewall rules permanently
- `-y` = Auto-confirm installation

### Step 3: Stop Services (Temporary)

**What we're doing:** Stopping the services so we can configure them properly.

**Why it's important:** Services must be stopped before we modify their configuration files.

```bash
# Stop hostapd service (if running)
sudo systemctl stop hostapd

# Stop dnsmasq service (if running)
sudo systemctl stop dnsmasq
```

**Explanation:**
- `systemctl` = System control (manages system services)
- `stop` = Stop a running service
- We stop them now because we need to configure them first before starting

### Step 4: Configure Static IP for wlan0

**What we're doing:** Setting a fixed IP address for the WiFi interface (wlan0).

**Why it's important:** 
- The access point needs a consistent IP address (like 192.168.4.1) so devices know where to connect
- If the IP address changed randomly, devices wouldn't know where to send their requests
- Think of it like a store that keeps changing its address - customers wouldn't know where to find it!

**What is a Static IP?**
- **Static IP** = An IP address that never changes (unlike dynamic IPs which can change)
- We set the Pi's WiFi interface to always be `192.168.4.1`
- This is the "address" that devices will use to communicate with the Pi

**What is wlan0?**
- `wlan0` = The name of the WiFi interface on the Raspberry Pi
- `wlan` = Wireless Local Area Network
- `0` = First WiFi adapter (if you had multiple, they'd be wlan1, wlan2, etc.)
- This is the interface that will broadcast the WiFi network

**Why 192.168.4.1?**
- `192.168.x.x` is a "private IP range" - used for local networks, not the internet
- We chose `.4` to avoid conflicts with common router IPs (like 192.168.1.1)
- `.1` is the standard gateway/router address (first device on the network)
- This IP will be the "gateway" that devices use to access the internet

```bash
# Edit network interfaces configuration file
sudo nano /etc/dhcpcd.conf
```

**What is /etc/dhcpcd.conf?**
- This is the configuration file for `dhcpcd` (DHCP Client Daemon)
- It controls how the Pi gets its own IP address
- We're telling it: "Don't get an IP from DHCP, use this static IP instead"

**Add these lines at the end of the file:**

```
# WiFi Access Point Configuration
interface wlan0
static ip_address=192.168.4.1/24
nohook wpa_supplicant
```

**Line-by-line explanation:**

1. **`# WiFi Access Point Configuration`**
   - This is a comment (starts with #)
   - Comments are notes for humans, ignored by the computer
   - Helps you remember what this section does

2. **`interface wlan0`**
   - Tells dhcpcd: "Apply the following settings to the wlan0 interface"
   - This is like saying "These rules are for the WiFi adapter"
   - Without this, the settings might apply to the wrong interface

3. **`static ip_address=192.168.4.1/24`**
   - `static` = Use a fixed IP address (don't try to get one from DHCP)
   - `ip_address=` = The IP address to use
   - `192.168.4.1` = The actual IP address
   - `/24` = Subnet mask in CIDR notation (equivalent to 255.255.255.0)
     - This means: "Network is 192.168.4, devices are .1 through .254"
     - The `/24` tells the system: "First 24 bits are network, last 8 bits are devices"
   - **Why /24?** This allows 254 devices (192.168.4.2 to 192.168.4.254)
     - .1 is the Pi (gateway)
     - .255 is reserved (broadcast address)
     - .2 to .254 are available for devices

4. **`nohook wpa_supplicant`**
   - `nohook` = Don't run this hook/script
   - `wpa_supplicant` = Software that connects to WiFi networks (as a client)
   - **Why disable it?** We're creating an access point (server), not connecting to one (client)
   - If wpa_supplicant runs, it might interfere with hostapd (our access point software)
   - Think of it like: "Don't try to connect to WiFi, we're providing WiFi"

**What happens if we skip this step?**
- The Pi might try to get an IP address from DHCP (but there's no DHCP server yet!)
- The IP address might be random or not set at all
- Devices wouldn't know where to find the Pi
- The access point wouldn't work properly

**Save and exit:**
- Press `Ctrl+X` to exit
- Press `Y` to confirm save
- Press `Enter` to confirm filename

**After saving, you may need to restart the network:**
```bash
# Restart the network interface to apply changes
sudo systemctl restart dhcpcd
```

### Step 5: Configure hostapd (WiFi Access Point)

**What we're doing:** Configuring the WiFi access point settings (network name, password, etc.).

**Why it's important:** 
- This creates the actual WiFi network that devices will connect to
- Without this configuration, the Pi won't broadcast a WiFi network
- This is like setting up a radio station - you need to configure what frequency, name, and security it uses

**What is hostapd?**
- **hostapd** = Host Access Point Daemon
- **Daemon** = A background service that runs continuously
- It's the software that makes the Pi act as a WiFi access point
- It handles all the WiFi protocol details so devices can connect

**What does it do?**
- Broadcasts the WiFi network name (SSID)
- Handles device authentication (password checking)
- Manages WiFi encryption (WPA2)
- Coordinates communication between the Pi and connected devices

```bash
# Create hostapd configuration file
sudo nano /etc/hostapd/hostapd.conf
```

**What is /etc/hostapd/hostapd.conf?**
- This is the configuration file that tells hostapd how to set up the WiFi network
- `/etc/` = System configuration directory (where system-wide settings are stored)
- `hostapd.conf` = Configuration file for hostapd
- We're creating this file because it doesn't exist by default

**Add this configuration:**

```
# WiFi Interface
interface=wlan0

# Driver (nl80211 is standard for most WiFi adapters)
driver=nl80211

# Network Name (SSID) - Change this to your desired WiFi name
ssid=Parental_WiFi

# WiFi Mode (g = 2.4GHz, a = 5GHz, n = both)
hw_mode=g

# WiFi Channel (1-11 for 2.4GHz, avoid channels used by nearby networks)
channel=7

# Enable WiFi Protected Access version 2 (security)
wpa=2

# WiFi Password - CHANGE THIS to a strong password!
wpa_passphrase=YourSecurePassword123

# Encryption method (CCMP is more secure than TKIP)
wpa_key_mgmt=WPA-PSK
wpa_pairwise=TKIP
rsn_pairwise=CCMP

# Allow all devices to connect (we'll control access via iptables)
macaddr_acl=0

# Authentication mode (0 = open, 1 = shared key)
auth_algs=1

# Ignore broadcast SSID (0 = visible, 1 = hidden)
ignore_broadcast_ssid=0
```

**Detailed explanation of each setting:**

1. **`interface=wlan0`**
   - **What it does:** Tells hostapd which network interface to use
   - **Why needed:** The Pi might have multiple network interfaces (wlan0, wlan1, eth0, etc.)
   - **Think of it as:** "Use the WiFi adapter called wlan0"
   - **What happens if wrong:** hostapd might try to use the wrong interface and fail

2. **`driver=nl80211`**
   - **What it does:** Specifies the WiFi driver to use
   - **Why needed:** Different WiFi chips use different drivers
   - **nl80211** = Modern Linux WiFi driver (works with most modern WiFi adapters)
   - **Think of it as:** The "language" the software uses to talk to the WiFi hardware
   - **What happens if wrong:** hostapd might not be able to control the WiFi adapter

3. **`ssid=Parental_WiFi`**
   - **What it does:** Sets the network name that appears when devices scan for WiFi
   - **SSID** = Service Set Identifier (the WiFi network name)
   - **Why needed:** Users need to see a name to know which network to connect to
   - **You can change this** to anything you want (e.g., "Kids_WiFi", "Family_Network")
   - **Think of it as:** The "store sign" that people see
   - **What happens if missing:** Network might not have a name (shows as "unnamed network")

4. **`hw_mode=g`**
   - **What it does:** Sets the WiFi frequency band
   - **Options:**
     - `g` = 2.4GHz band (most compatible, works with older devices)
     - `a` = 5GHz band (faster, but not all devices support it)
     - `n` = Both bands (requires compatible hardware)
   - **Why we use `g`:** Maximum compatibility - almost all devices support 2.4GHz
   - **Think of it as:** Choosing AM radio (2.4GHz) vs FM radio (5GHz)
   - **What happens if wrong:** Some devices might not be able to connect

5. **`channel=7`**
   - **What it does:** Sets the WiFi channel (like a radio frequency)
   - **Range:** 1-11 for 2.4GHz band
   - **Why channel 7?** It's in the middle, often has less interference
   - **Why it matters:** If nearby networks use the same channel, they interfere with each other
   - **Think of it as:** Choosing which radio frequency to broadcast on
   - **How to choose:** Use a WiFi analyzer app to see which channels are less crowded
   - **What happens if wrong:** Network might be slow due to interference

6. **`wpa=2`**
   - **What it does:** Enables WPA2 security (WiFi Protected Access version 2)
   - **Why needed:** Without this, your network would be open (anyone could connect)
   - **WPA2** = Modern, secure encryption standard
   - **Think of it as:** Locking your front door
   - **What happens if missing:** Network would be unsecured (very bad for security!)

7. **`wpa_passphrase=YourSecurePassword123`**
   - **What it does:** Sets the WiFi password
   - **⚠️ IMPORTANT:** **CHANGE THIS** to a strong, unique password!
   - **Why needed:** Prevents unauthorized access to your network
   - **Password requirements:**
     - At least 8 characters (longer is better)
     - Mix of letters, numbers, and symbols
     - Don't use common words or personal information
   - **Think of it as:** The key to your locked door
   - **What happens if weak:** Hackers could guess it and access your network

8. **`wpa_key_mgmt=WPA-PSK`**
   - **What it does:** Sets the key management method
   - **WPA-PSK** = WPA Pre-Shared Key (uses a password, not certificates)
   - **Why this:** Simple and works for home networks
   - **Think of it as:** "Use password-based authentication"
   - **Alternative:** Enterprise mode (more complex, needs a server)

9. **`wpa_pairwise=TKIP` and `rsn_pairwise=CCMP`**
   - **What it does:** Sets encryption algorithms
   - **TKIP** = Temporal Key Integrity Protocol (older, less secure)
   - **CCMP** = Counter Mode with Cipher Block Chaining (newer, more secure)
   - **Why both?** Some old devices only support TKIP, but CCMP is preferred
   - **Think of it as:** Having two types of locks (old and new style)
   - **What happens:** System uses CCMP when possible, falls back to TKIP if needed

10. **`macaddr_acl=0`**
    - **What it does:** Controls MAC address access control
    - **0** = Allow all devices to connect (no MAC filtering)
    - **1** = Only allow devices in the MAC address list
    - **Why we use 0:** We control access via iptables (more flexible)
    - **Think of it as:** "Let anyone try to connect, we'll block them later if needed"
    - **Alternative:** Could set to 1 and maintain a whitelist (more restrictive)

11. **`auth_algs=1`**
    - **What it does:** Sets authentication algorithm
    - **0** = Open system (no authentication)
    - **1** = Shared key authentication (uses password)
    - **Why we use 1:** We want password protection
    - **Think of it as:** "Require password to connect"

12. **`ignore_broadcast_ssid=0`**
    - **What it does:** Controls whether the network name is visible
    - **0** = Network name is visible (normal)
    - **1** = Hidden network (name doesn't appear in scan)
    - **Why we use 0:** Easier for users to find and connect
    - **Think of it as:** "Show the store sign" vs "Hide the store sign"
    - **Note:** Hidden networks aren't really more secure (just harder to find)

**What happens if we skip this step?**
- No WiFi network would be created
- Devices wouldn't see any network to connect to
- The Pi wouldn't act as an access point

**Save and exit:** `Ctrl+X`, `Y`, `Enter`

**Save and exit:** `Ctrl+X`, `Y`, `Enter`

**Tell hostapd where to find the config file:**

```bash
# Edit hostapd default configuration
sudo nano /etc/default/hostapd
```

**Find this line:**
```
#DAEMON_CONF=""
```

**Change it to:**
```
DAEMON_CONF="/etc/hostapd/hostapd.conf"
```

**Explanation:**
- This tells hostapd service where to find its configuration file
- The `#` at the start means the line is commented out (disabled)
- We remove the `#` and add the path to our config file

**Save and exit:** `Ctrl+X`, `Y`, `Enter`

### Step 6: Configure dnsmasq (DHCP Server)

**What we're doing:** Configuring dnsmasq to assign IP addresses to connected devices and handle DNS queries.

**Why it's important:** 
- When a device connects, it needs an IP address. dnsmasq automatically assigns one.
- Devices also need DNS to resolve website names to IP addresses
- Without dnsmasq, devices would connect but couldn't get an IP or access websites by name

**What is dnsmasq?**
- **dnsmasq** = DNS and DHCP server combined in one lightweight program
- **DHCP part:** Automatically assigns IP addresses to devices
- **DNS part:** Resolves domain names (like google.com) to IP addresses
- It's lightweight and perfect for small networks like ours

**What does DHCP do?**
- When a device connects to WiFi, it doesn't have an IP address yet
- Device sends a "DHCP request": "Hey, I need an IP address!"
- dnsmasq responds: "Here's your IP: 192.168.4.2, gateway: 192.168.4.1, DNS: 192.168.4.1"
- Device configures itself with these settings
- Now device can communicate on the network

**What does DNS do?**
- When you type "google.com" in a browser, the device needs to know the IP address
- Device asks dnsmasq: "What's the IP for google.com?"
- dnsmasq either knows (cached) or asks upstream DNS servers (8.8.8.8)
- Returns the IP address to the device
- Device can now connect to that IP

**Why backup the original config?**
- The original `/etc/dnsmasq.conf` has default settings that might conflict
- We backup it so we can restore if something goes wrong
- It's a safety measure - always good to backup before making changes

```bash
# Backup original dnsmasq config (safety measure)
sudo mv /etc/dnsmasq.conf /etc/dnsmasq.conf.orig

# Create new dnsmasq configuration
sudo nano /etc/dnsmasq.conf
```

**What is /etc/dnsmasq.conf?**
- Configuration file for dnsmasq
- Tells dnsmasq: which interface to serve, what IP range to use, which DNS servers to use
- We're replacing the default config with our custom one

**Add this configuration:**

```
# WiFi Interface
interface=wlan0

# Don't use /etc/resolv.conf for DNS (we'll set our own)
no-resolv

# Use Google DNS servers (or your preferred DNS)
server=8.8.8.8
server=8.8.4.4

# IP Address Range for DHCP (assigns IPs to connected devices)
# Format: start-address,end-address,lease-time
# This allows 50 devices (192.168.4.2 to 192.168.4.51)
dhcp-range=192.168.4.2,192.168.4.51,255.255.255.0,24h

# Gateway (router) IP address (the Pi itself)
dhcp-option=3,192.168.4.1

# DNS server IP address (the Pi itself)
dhcp-option=6,192.168.4.1
```

**Detailed explanation of each setting:**

1. **`interface=wlan0`**
   - **What it does:** Tells dnsmasq to only serve DHCP/DNS on the wlan0 interface
   - **Why needed:** The Pi might have multiple interfaces (eth0, wlan0, etc.)
   - **Without this:** dnsmasq might try to serve DHCP on Ethernet too, causing conflicts
   - **Think of it as:** "Only provide services on the WiFi interface"
   - **What happens if wrong:** Might interfere with other network interfaces

2. **`no-resolv`**
   - **What it does:** Tells dnsmasq not to read `/etc/resolv.conf` for DNS servers
   - **Why needed:** We want to specify our own DNS servers (8.8.8.8, 8.8.4.4)
   - **What is /etc/resolv.conf?** System file that lists DNS servers
   - **Without this:** dnsmasq might use system DNS settings, which could be wrong
   - **Think of it as:** "Don't use the system's DNS list, use mine instead"

3. **`server=8.8.8.8` and `server=8.8.4.4`**
   - **What it does:** Sets upstream DNS servers (where dnsmasq gets DNS answers from)
   - **8.8.8.8 and 8.8.4.4** = Google's public DNS servers
   - **Why Google DNS?** Reliable, fast, and widely used
   - **How it works:**
     - Device asks dnsmasq: "What's the IP for google.com?"
     - dnsmasq doesn't know, so it asks 8.8.8.8
     - 8.8.8.8 responds with the IP address
     - dnsmasq caches the answer and tells the device
   - **Alternative DNS servers:**
     - Cloudflare: `1.1.1.1` and `1.0.0.1`
     - OpenDNS: `208.67.222.222` and `208.67.220.220`
   - **Think of it as:** "When I don't know an answer, ask Google DNS"
   - **What happens if wrong:** DNS might not work, devices can't access websites by name

4. **`dhcp-range=192.168.4.2,192.168.4.51,255.255.255.0,24h`**
   - **What it does:** Defines the pool of IP addresses to assign to devices
   - **Breaking it down:**
     - `192.168.4.2` = **Start address** (first IP to assign)
       - Why .2? Because .1 is the Pi itself (gateway)
     - `192.168.4.51` = **End address** (last IP to assign)
       - This gives us 50 IP addresses (192.168.4.2 through 192.168.4.51)
       - Enough for 50 devices to connect simultaneously
     - `255.255.255.0` = **Subnet mask**
       - Tells devices which IPs are on the same network
       - Same as `/24` notation
     - `24h` = **Lease time** (24 hours)
       - How long a device keeps its assigned IP address
       - After 24 hours, device must renew the lease
       - If device disconnects, IP can be reused after lease expires
   - **Why 50 devices?** Reasonable limit for a home network
   - **Can you change it?** Yes! Change .51 to .254 for up to 252 devices
   - **Think of it as:** "I have 50 room numbers (IPs) to assign to guests (devices)"
   - **What happens if pool is full:** New devices can't get an IP address

5. **`dhcp-option=3,192.168.4.1`**
   - **What it does:** Tells devices what their gateway (router) IP address is
   - **Format:** `dhcp-option=3,<IP>` where 3 is the option code for gateway
   - **192.168.4.1** = The Pi's IP address (the gateway)
   - **Why needed:** Devices need to know where to send internet traffic
   - **How it works:**
     - Device wants to visit google.com
     - Device doesn't know where google.com is (it's on the internet)
     - Device sends request to gateway (192.168.4.1 - the Pi)
     - Pi forwards it to the internet
   - **Think of it as:** "When you want to go outside, go through this door (gateway)"
   - **What happens if wrong:** Devices won't know where to send internet traffic

6. **`dhcp-option=6,192.168.4.1`**
   - **What it does:** Tells devices what DNS server to use
   - **Format:** `dhcp-option=6,<IP>` where 6 is the option code for DNS server
   - **192.168.4.1** = The Pi's IP address (dnsmasq runs on the Pi)
   - **Why the Pi?** Because dnsmasq is running on the Pi, so devices should ask the Pi for DNS
   - **How it works:**
     - Device wants to visit google.com
     - Device asks DNS server (192.168.4.1 - the Pi) for the IP address
     - Pi's dnsmasq looks it up (or asks 8.8.8.8) and responds
   - **Think of it as:** "When you need to look up an address, ask this phone book (DNS server)"
   - **What happens if wrong:** Devices can't resolve domain names to IP addresses

**What happens if we skip this step?**
- Devices would connect to WiFi but wouldn't get an IP address
- Devices couldn't communicate on the network
- Devices couldn't access the internet
- The network would be unusable

**Why do we need both DHCP and DNS?**
- **DHCP:** Gets devices onto the network (gives them an IP address)
- **DNS:** Lets devices find websites (converts names to IP addresses)
- Both are essential for a working network

**Save and exit:** `Ctrl+X`, `Y`, `Enter`

### Step 7: Enable IP Forwarding

**What we're doing:** Allowing the Pi to forward network traffic between interfaces (WiFi to Ethernet and vice versa).

**Why it's important:** 
- By default, Linux only processes packets meant for itself
- We need the Pi to act as a router - receiving packets on one interface and forwarding them to another
- Without IP forwarding, devices on WiFi can't access the internet through Ethernet

**What is IP Forwarding?**
- **IP Forwarding** = The ability to receive a network packet on one interface and send it out another interface
- **Normal behavior:** Linux receives a packet, checks if it's for this computer, if not, discards it
- **With forwarding:** Linux receives a packet, checks if it's for this computer, if not, forwards it to the correct interface
- **Think of it as:** A mail sorting facility - receives mail, checks the address, forwards it to the right destination

**Real-world example:**
1. Device on WiFi (192.168.4.2) wants to visit google.com
2. Device sends packet to gateway (192.168.4.1 - the Pi) on wlan0 interface
3. **Without forwarding:** Pi receives packet, sees it's not for the Pi, discards it ✗
4. **With forwarding:** Pi receives packet, sees it's not for the Pi, forwards it to eth0 (internet) ✓
5. Response comes back through eth0, Pi forwards it to wlan0, device receives it ✓

**Why two commands?**
- First command: Makes the change permanent (survives reboot)
- Second command: Applies the change immediately (without reboot)

```bash
# Enable IP forwarding (allows traffic to pass through Pi)
sudo sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf

# Apply the change immediately (without reboot)
sudo sh -c "echo 1 > /proc/sys/net/ipv4/ip_forward"
```

**Detailed explanation:**

**Command 1: `sudo sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf`**
- **What it does:** Edits `/etc/sysctl.conf` to enable IP forwarding permanently
- **Breaking it down:**
  - `sed` = Stream editor (text manipulation tool)
  - `-i` = Edit file in-place (modify the file directly)
  - `'s/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/'` = Substitute command
    - `s/` = Substitute (find and replace)
    - `#net.ipv4.ip_forward=1` = Find this line (commented out with #)
    - `net.ipv4.ip_forward=1` = Replace with this (uncommented, enabled)
  - `/etc/sysctl.conf` = System configuration file for kernel parameters
- **What the file contains:**
  - By default: `#net.ipv4.ip_forward=1` (commented out, disabled)
  - After command: `net.ipv4.ip_forward=1` (enabled)
- **Why permanent?** This file is read on boot, so the setting persists after reboot

**Command 2: `sudo sh -c "echo 1 > /proc/sys/net/ipv4/ip_forward"`**
- **What it does:** Immediately enables IP forwarding (without waiting for reboot)
- **Breaking it down:**
  - `sh -c` = Run command in shell
  - `echo 1` = Output the number 1
  - `>` = Redirect output to file (overwrite file with "1")
  - `/proc/sys/net/ipv4/ip_forward` = Kernel parameter file
    - `/proc/` = Virtual filesystem (not real files, interface to kernel)
    - Reading this file shows current setting (0 = disabled, 1 = enabled)
    - Writing "1" to it enables forwarding immediately
- **Why needed?** The first command only changes the config file, doesn't apply it
- **Think of it as:** Config file = recipe, this command = actually cooking

**Verify it worked:**
```bash
# Check if IP forwarding is enabled (should output: 1)
cat /proc/sys/net/ipv4/ip_forward
```

**What happens if we skip this step?**
- Devices can connect to WiFi ✓
- Devices get IP addresses ✓
- Devices try to access internet ✗ (packets aren't forwarded)
- Network would be isolated (devices can't reach internet)

**Why is this critical?**
- This is what makes the Pi act as a router
- Without it, the Pi is just a WiFi access point with no internet connection
- This is the bridge between your WiFi network and the internet

### Step 8: Configure iptables (Firewall Rules)

**What we're doing:** Setting up firewall rules to route traffic and enable NAT (Network Address Translation).

**Why it's important:** 
- NAT allows multiple devices to share one internet connection
- Firewall rules control what traffic is allowed/blocked
- This is the foundation for our device blocking scripts
- Without these rules, devices can't access the internet even with IP forwarding enabled

**What is iptables?**
- **iptables** = Linux's built-in firewall and packet filtering tool
- It controls what network traffic is allowed, blocked, or modified
- Think of it as a security guard that checks every packet of data
- It's what our blocking scripts use to block/unblock devices

**What is NAT (Network Address Translation)?**
- **NAT** = Allows multiple devices with private IPs to share one public IP
- **How it works:**
  1. Device (192.168.4.2) wants to visit google.com
  2. Packet has source IP: 192.168.4.2 (private, not routable on internet)
  3. NAT translates it: Changes source IP to Pi's public IP (from ISP)
  4. Internet sees request coming from Pi's public IP
  5. Response comes back to Pi's public IP
  6. NAT translates it back: Changes destination to 192.168.4.2
  7. Pi forwards packet to the correct device
- **Why needed:** Your ISP gives you one public IP, but you have many devices
- **Think of it as:** A mail forwarding service - all mail comes to one address, then gets forwarded to individual apartments

**What are iptables chains?**
- **Chains** = Checkpoints where packets are examined
- **INPUT chain:** Packets coming TO the Pi itself
- **OUTPUT chain:** Packets going FROM the Pi itself
- **FORWARD chain:** Packets being forwarded THROUGH the Pi (WiFi ↔ Ethernet)
- **PREROUTING chain:** Packets before routing decision (NAT table)
- **POSTROUTING chain:** Packets after routing decision (NAT table)
- **Think of it as:** Different checkpoints in a security checkpoint system

**Why flush existing rules?**
- Start with a clean slate
- Remove any conflicting rules
- Ensure our rules are the only ones active

```bash
# Flush existing iptables rules (start fresh)
sudo iptables -t nat -F
sudo iptables -F

# Enable NAT (Network Address Translation)
# This allows WiFi devices to access internet through Ethernet
sudo iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE

# Allow forwarding between interfaces
sudo iptables -A FORWARD -i eth0 -o wlan0 -m state --state RELATED,ESTABLISHED -j ACCEPT
sudo iptables -A FORWARD -i wlan0 -o eth0 -j ACCEPT
```

**Detailed explanation of each command:**

**Command 1 & 2: Flush rules**
```bash
sudo iptables -t nat -F
sudo iptables -F
```
- **What it does:** Deletes all existing iptables rules
- **Breaking it down:**
  - `-t nat` = Target the NAT table (first command)
  - `-F` = Flush (delete all rules in the table)
- **Why needed:** Start clean, avoid conflicts with existing rules
- **Think of it as:** Clearing the whiteboard before writing new rules

**Command 3: Enable NAT (MASQUERADE)**
```bash
sudo iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
```
- **What it does:** Enables NAT for packets going out to the internet
- **Breaking it down:**
  - `-t nat` = Use NAT table (for address translation)
  - `-A POSTROUTING` = Add rule to POSTROUTING chain
    - **POSTROUTING** = After routing decision, before packet leaves
    - This is where we translate source IP addresses
  - `-o eth0` = Outgoing interface is eth0 (Ethernet - internet connection)
  - `-j MASQUERADE` = Jump to MASQUERADE target
    - **MASQUERADE** = Automatically use Pi's public IP as source
    - Hides all internal IPs (192.168.4.x) behind Pi's IP
    - Internet only sees Pi's IP, not individual device IPs
- **How it works:**
  1. Packet from device (192.168.4.2) arrives at Pi
  2. Pi routes it to eth0 (internet)
  3. POSTROUTING chain processes it
  4. MASQUERADE changes source IP from 192.168.4.2 to Pi's public IP
  5. Packet goes to internet with Pi's IP
- **Why POSTROUTING?** We need to translate AFTER routing decision (know which interface to use)
- **Think of it as:** Changing the return address on an envelope before mailing it

**Command 4: Allow incoming internet traffic**
```bash
sudo iptables -A FORWARD -i eth0 -o wlan0 -m state --state RELATED,ESTABLISHED -j ACCEPT
```
- **What it does:** Allows responses from internet to reach WiFi devices
- **Breaking it down:**
  - `-A FORWARD` = Add rule to FORWARD chain (for forwarded packets)
  - `-i eth0` = Input interface is eth0 (packet coming from internet)
  - `-o wlan0` = Output interface is wlan0 (packet going to WiFi)
  - `-m state` = Match by connection state
  - `--state RELATED,ESTABLISHED` = Only allow established connections
    - **ESTABLISHED** = Part of an existing connection (response to our request)
    - **RELATED** = Related to an existing connection (FTP data, etc.)
  - `-j ACCEPT` = Accept (allow) the packet
- **Why state matching?** Security - only allow responses to requests we made
- **How it works:**
  1. Device requests google.com (goes out through wlan0 → eth0)
  2. Response comes back (eth0 → wlan0)
  3. This rule checks: "Is this a response to a request we made?"
  4. If yes, allow it through
  5. If no (unsolicited traffic), block it (security)
- **Think of it as:** "Only accept mail if we sent a letter first"

**Command 5: Allow outgoing internet traffic**
```bash
sudo iptables -A FORWARD -i wlan0 -o eth0 -j ACCEPT
```
- **What it does:** Allows devices on WiFi to send traffic to the internet
- **Breaking it down:**
  - `-A FORWARD` = Add rule to FORWARD chain
  - `-i wlan0` = Input interface is wlan0 (packet from WiFi device)
  - `-o eth0` = Output interface is eth0 (packet going to internet)
  - `-j ACCEPT` = Accept (allow) the packet
- **Why no state matching?** This is for new connections (outgoing requests)
- **How it works:**
  1. Device on WiFi wants to visit google.com
  2. Device sends packet to Pi (wlan0 interface)
  3. Pi routes it to internet (eth0 interface)
  4. This rule allows the forwarding
  5. NAT (from previous rule) translates the IP address
  6. Packet goes to internet
- **Think of it as:** "Allow devices to send mail to the internet"

**Why both FORWARD rules?**
- **Rule 4 (eth0 → wlan0):** Allows responses FROM internet TO devices
- **Rule 5 (wlan0 → eth0):** Allows requests FROM devices TO internet
- Both directions are needed for two-way communication

**Save iptables rules permanently:**

```bash
# Save current iptables rules
sudo netfilter-persistent save
```

**Why save rules?**
- By default, iptables rules are lost after reboot
- This command saves them to disk
- Rules will be automatically restored on next boot

**Explanation:**
- `netfilter-persistent` = Tool to save iptables rules permanently
- `save` = Save current rules to disk
- Rules are stored in `/etc/iptables/` directory
- On boot, rules are automatically loaded

**What happens if we skip this step?**
- Devices can connect to WiFi ✓
- Devices get IP addresses ✓
- IP forwarding is enabled ✓
- But: Packets would be blocked by default firewall rules ✗
- Devices still couldn't access internet ✗

**Why is NAT critical?**
- Without NAT, devices with private IPs (192.168.4.x) can't access the internet
- Internet routers don't know how to route private IP addresses
- NAT translates private IPs to public IP (routable on internet)
- This is how home networks work - multiple devices share one public IP

### Step 9: Start and Enable Services

**What we're doing:** Starting the services and making them start automatically on boot.

**Why it's important:** Services must run for the access point to work, and should start automatically after reboot.

```bash
# Start dnsmasq service
sudo systemctl start dnsmasq

# Start hostapd service
sudo systemctl start hostapd

# Enable services to start on boot
sudo systemctl enable dnsmasq
sudo systemctl enable hostapd
```

**Explanation:**
- `systemctl start` = Start service immediately
- `systemctl enable` = Enable service to start automatically on boot
- We start dnsmasq first (DHCP), then hostapd (WiFi AP)

### Step 10: Verify Access Point is Working

**What we're doing:** Checking that everything is configured correctly.

**Check service status:**

```bash
# Check if hostapd is running
sudo systemctl status hostapd --no-pager

# Check if dnsmasq is running
sudo systemctl status dnsmasq --no-pager
```

**Expected output:** Both should show `active (running)`

**Check WiFi interface:**

```bash
# Check if wlan0 has the correct IP address
ip addr show wlan0
```

**Expected output:** Should show `inet 192.168.4.1/24`

**Check iptables rules:**

```bash
# Check NAT rules
sudo iptables -t nat -L -v

# Check FORWARD rules
sudo iptables -L FORWARD -v
```

**Test the access point:**

1. On a phone/tablet, look for WiFi networks
2. You should see "Parental_WiFi" (or your SSID)
3. Connect using the password you set
4. Device should get an IP address (192.168.4.2, 192.168.4.3, etc.)

## Troubleshooting

### Access Point Not Visible

**Problem:** Can't see the WiFi network on devices.

**Solutions:**
```bash
# Check if hostapd is running
sudo systemctl status hostapd

# Check hostapd logs for errors
sudo journalctl -u hostapd -n 50

# Restart hostapd
sudo systemctl restart hostapd
```

### Devices Can't Get IP Address

**Problem:** Device connects but doesn't get an IP address.

**Solutions:**
```bash
# Check if dnsmasq is running
sudo systemctl status dnsmasq

# Check dnsmasq logs
sudo journalctl -u dnsmasq -n 50

# Restart dnsmasq
sudo systemctl restart dnsmasq

# Check if wlan0 has correct IP
ip addr show wlan0
```

### No Internet Access

**Problem:** Devices connect but can't access internet.

**Solutions:**
```bash
# Check IP forwarding is enabled
cat /proc/sys/net/ipv4/ip_forward
# Should output: 1

# Check iptables NAT rules
sudo iptables -t nat -L -v

# Check Ethernet connection
ip addr show eth0
# Should show an IP address (from your router)

# Test internet connectivity from Pi
ping -c 3 8.8.8.8
```

### Services Won't Start

**Problem:** hostapd or dnsmasq fail to start.

**Solutions:**
```bash
# Check configuration file syntax
sudo hostapd -dd /etc/hostapd/hostapd.conf
# (Press Ctrl+C to stop, look for errors)

# Test dnsmasq configuration
sudo dnsmasq --test

# Check for conflicting services
sudo systemctl status wpa_supplicant
# If running, disable it: sudo systemctl disable wpa_supplicant
```

## Security Considerations

1. **Change Default Password:** Always change the `wpa_passphrase` in hostapd.conf
2. **Use Strong Password:** At least 12 characters, mix of letters, numbers, symbols
3. **Update Regularly:** Keep system updated: `sudo apt update && sudo apt upgrade`
4. **Firewall Rules:** The iptables rules we set are basic - additional security can be added later

## Next Steps

Once the access point is working:

1. ✅ Verify devices can connect
2. ✅ Verify devices get IP addresses
3. ✅ Verify devices can access internet (if applicable)
4. ✅ Proceed with shell scripts implementation (TODO #12)

## How Everything Works Together

Now that we've configured everything, let's understand how all the pieces work together when a device connects:

### Complete Flow: Device Connects and Accesses Internet

**Step 1: Device Scans for WiFi Networks**
- Device's WiFi adapter scans for available networks
- Sees "Parental_WiFi" (from hostapd configuration)
- User selects network and enters password

**Step 2: Device Authenticates**
- Device sends password to Pi (hostapd)
- hostapd checks password against `wpa_passphrase` in config
- If correct, device is allowed to connect
- WPA2 encryption is established (all traffic is encrypted)

**Step 3: Device Requests IP Address (DHCP)**
- Device sends DHCP request: "I need an IP address!"
- dnsmasq receives request on wlan0 interface
- dnsmasq assigns IP from pool: 192.168.4.2 (for example)
- dnsmasq also tells device:
  - Gateway: 192.168.4.1 (the Pi)
  - DNS server: 192.168.4.1 (the Pi)
  - Subnet mask: 255.255.255.0
- Device configures itself with these settings

**Step 4: Device Wants to Visit a Website**
- User types "google.com" in browser
- Device needs to know the IP address for google.com

**Step 5: DNS Resolution**
- Device asks DNS server (192.168.4.1 - the Pi): "What's the IP for google.com?"
- Pi's dnsmasq receives the request
- dnsmasq doesn't know, so it asks upstream DNS (8.8.8.8 - Google DNS)
- Google DNS responds: "google.com is 142.250.191.14"
- dnsmasq caches the answer and tells device: "142.250.191.14"
- Device now knows where to connect

**Step 6: Device Sends Request**
- Device creates packet:
  - Source IP: 192.168.4.2 (device's IP)
  - Destination IP: 142.250.191.14 (google.com)
  - Data: HTTP request for webpage
- Device sends packet to gateway (192.168.4.1 - the Pi)

**Step 7: Pi Receives Packet**
- Packet arrives on wlan0 interface
- Pi checks: "Is this for me?" No, it's for google.com
- IP forwarding is enabled, so Pi forwards the packet

**Step 8: iptables FORWARD Rule**
- Packet goes through FORWARD chain
- Rule: `-i wlan0 -o eth0 -j ACCEPT` matches
- Packet is allowed to be forwarded

**Step 9: NAT Translation (POSTROUTING)**
- Packet goes through POSTROUTING chain
- MASQUERADE rule matches: `-o eth0 -j MASQUERADE`
- NAT translates source IP:
  - From: 192.168.4.2 (device's private IP)
  - To: Pi's public IP (from ISP, e.g., 203.0.113.42)
- Internet now sees request coming from Pi's public IP

**Step 10: Packet Goes to Internet**
- Packet is sent out eth0 interface (Ethernet)
- Travels through your home router to ISP
- ISP routes it to google.com's servers
- Google receives the request

**Step 11: Response Comes Back**
- Google sends response packet:
  - Source IP: 142.250.191.14 (google.com)
  - Destination IP: Pi's public IP (203.0.113.42)
  - Data: Webpage content
- Packet travels back through internet to your home
- Arrives at Pi's eth0 interface

**Step 12: iptables FORWARD Rule (Response)**
- Packet goes through FORWARD chain
- Rule: `-i eth0 -o wlan0 -m state --state RELATED,ESTABLISHED -j ACCEPT` matches
- State check: "Is this a response to a request we made?" Yes (ESTABLISHED)
- Packet is allowed to be forwarded

**Step 13: NAT Translation (Response)**
- NAT remembers the original connection
- NAT translates destination IP:
  - From: Pi's public IP (203.0.113.42)
  - To: 192.168.4.2 (device's private IP)
- Packet is forwarded to wlan0

**Step 14: Device Receives Response**
- Packet arrives at device (192.168.4.2)
- Device receives the webpage content
- Browser displays google.com

### Key Components and Their Roles

**hostapd (WiFi Access Point)**
- Creates the WiFi network
- Handles device authentication (password checking)
- Manages WiFi encryption (WPA2)
- **Without it:** No WiFi network to connect to

**dnsmasq (DHCP & DNS Server)**
- Assigns IP addresses to devices (DHCP)
- Resolves domain names to IP addresses (DNS)
- **Without it:** Devices can't get IPs or access websites by name

**IP Forwarding**
- Allows Pi to forward packets between interfaces
- **Without it:** Packets are discarded, no internet access

**iptables (Firewall & NAT)**
- Controls what traffic is allowed (firewall)
- Translates IP addresses (NAT)
- **Without it:** Packets are blocked, no internet access

### Why Each Step is Critical

1. **Static IP (Step 4):** Devices need a consistent address to find the Pi
2. **hostapd (Step 5):** Creates the actual WiFi network
3. **dnsmasq (Step 6):** Gives devices IPs and resolves DNS
4. **IP Forwarding (Step 7):** Allows packet forwarding (router functionality)
5. **iptables (Step 8):** Enables NAT and controls traffic flow

**If any step is missing:** The network won't work properly!

## Summary

You've now configured your Raspberry Pi as a WiFi access point! The Pi will:
- Broadcast a WiFi network (SSID: Parental_WiFi)
- Assign IP addresses to connected devices (192.168.4.2-192.168.4.51)
- Route internet traffic (if Ethernet is connected)
- Be ready for the shell scripts to control device access

**What you've learned:**
- How WiFi access points work
- How DHCP assigns IP addresses
- How DNS resolves domain names
- How NAT allows multiple devices to share one IP
- How IP forwarding enables routing
- How iptables controls network traffic

**The complete picture:**
- Devices connect to WiFi → hostapd authenticates them
- Devices get IP addresses → dnsmasq assigns them
- Devices access internet → IP forwarding + iptables route traffic
- Our shell scripts will use iptables to block/unblock devices

**Key Files Created/Modified:**
- `/etc/dhcpcd.conf` - Static IP for wlan0
- `/etc/hostapd/hostapd.conf` - WiFi access point settings
- `/etc/default/hostapd` - hostapd service configuration
- `/etc/dnsmasq.conf` - DHCP and DNS settings
- `/etc/sysctl.conf` - IP forwarding enabled
- iptables rules - NAT and forwarding rules

**Services Running:**
- `hostapd` - WiFi access point
- `dnsmasq` - DHCP and DNS server

The access point is now ready for the shell scripts to manage device blocking and unblocking!

