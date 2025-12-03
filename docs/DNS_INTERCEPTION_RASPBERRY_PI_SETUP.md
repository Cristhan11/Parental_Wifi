# DNS Interception - Raspberry Pi Setup Instructions

## Quick Setup Checklist

Follow these steps on your Raspberry Pi to enable DNS interception for HTTPS support:

### Step 1: Configure dnsmasq to use config directory

```bash
# Edit dnsmasq config
sudo nano /etc/dnsmasq.conf

# Add this line (if not already present):
conf-dir=/etc/dnsmasq.d/,*.conf

# Save and exit (Ctrl+O, Enter, Ctrl+X)

# Create config directory (if it doesn't exist)
sudo mkdir -p /etc/dnsmasq.d

# Reload dnsmasq to apply changes
sudo systemctl reload dnsmasq
```

### Step 2: Make script executable

```bash
cd /var/www/parental_wifi
sudo chmod +x scripts/manage_dns_interception.sh
```

### Step 3: Configure sudoers permission

```bash
# Edit sudoers
sudo visudo

# Add this line:
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/manage_dns_interception.sh

# Save and exit (Ctrl+O, Enter, Ctrl+X)
```

### Step 4: Verify setup

```bash
# Check dnsmasq config
sudo grep conf-dir /etc/dnsmasq.conf

# Check script is executable
ls -la scripts/manage_dns_interception.sh

# Check sudoers permission
sudo grep "manage_dns_interception" /etc/sudoers /etc/sudoers.d/*

# Test script manually (with a connected device MAC)
sudo scripts/manage_dns_interception.sh AA:BB:CC:DD:EE:FF add
```

## What Happens Next

After setup, DNS interception will be automatically managed:

- **When device time expires:** DNS interception is enabled automatically
- **When device completes quiz/video:** DNS interception is disabled automatically (if no other Preauthenticated devices exist)
- **Whitelisted devices:** Never have DNS interception enabled

## Testing

1. Connect a device to WiFi
2. Let device time expire (or manually redirect)
3. Try accessing `https://google.com` on the device
4. **Expected:** Device should be redirected to portal (even for HTTPS)

## Troubleshooting

If DNS interception doesn't work:

1. **Check dnsmasq config:**
   ```bash
   sudo cat /etc/dnsmasq.d/captive-portal.conf
   ```
   Should show: `address=/#/192.168.4.1`

2. **Check dnsmasq is using conf-dir:**
   ```bash
   sudo grep conf-dir /etc/dnsmasq.conf
   ```

3. **Test DNS resolution:**
   ```bash
   nslookup google.com 192.168.4.1
   ```
   Should return: `192.168.4.1` (when interception is enabled)

4. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for DNS interception messages

For complete documentation, see `docs/DNS_INTERCEPTION_SETUP.md`.

