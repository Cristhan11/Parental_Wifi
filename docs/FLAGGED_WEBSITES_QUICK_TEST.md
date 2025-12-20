# Flagged Websites - Quick Manual Testing Guide

**Quick Reference for Manual Testing**

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Access the Application

1. Open your browser
2. Go to: `http://[YOUR_PI_IP]/flagged-websites`
   - Replace `[YOUR_PI_IP]` with your Raspberry Pi's IP (e.g., `192.168.1.100`)
   - Or use `http://localhost/flagged-websites` if testing locally

3. **Make sure you're logged in** as a parent user

### Step 2: Create Your First Flagged Website

1. Click the **"Flag Website"** button (red button, top right)

2. Fill in the form:
   ```
   Device: [Select a child device from dropdown]
   URL: https://example.com
   Reason: Test monitoring (optional)
   ```

3. Click **"Flag Website"** button

4. ✅ **Expected**: Success message "Website flagged successfully."

### Step 3: Verify It Works

1. You should see the flagged website in the list
2. Check that:
   - ✅ URL is displayed correctly
   - ✅ Domain is extracted (e.g., `example.com`)
   - ✅ Device name is shown
   - ✅ Created date is displayed

---

## 📋 Complete Testing Checklist

### ✅ Test 1: Create Flagged Website
- [ ] Navigate to `/flagged-websites/create`
- [ ] Fill form with valid data
- [ ] Submit form
- [ ] See success message
- [ ] Verify in database (optional)

### ✅ Test 2: View List
- [ ] Navigate to `/flagged-websites`
- [ ] See flagged websites list
- [ ] Verify all columns display correctly

### ✅ Test 3: Filter by Device
- [ ] Select device from filter dropdown
- [ ] Click "Filter"
- [ ] See only that device's flagged websites

### ✅ Test 4: Search
- [ ] Enter search term (domain or URL)
- [ ] Click "Filter"
- [ ] See matching results

### ✅ Test 5: Edit Flagged Website
- [ ] Click "Edit" on a flagged website
- [ ] Change URL
- [ ] Change reason
- [ ] Submit
- [ ] Verify domain re-extracted

### ✅ Test 6: Delete Flagged Website
- [ ] Click "Delete" on a flagged website
- [ ] Confirm deletion
- [ ] Verify removed from list

### ✅ Test 7: Validation Tests
- [ ] Try to create without device → Should show error
- [ ] Try to create without URL → Should show error
- [ ] Try invalid URL format → Should show error
- [ ] Try to flag same domain twice for same device → Should show error

### ✅ Test 8: Authorization Test
- [ ] Create flagged website for Device A (User A)
- [ ] Log in as User B
- [ ] Try to edit/delete → Should be denied

---

## 🔍 Database Verification (Optional)

### Quick Check with Tinker

```bash
php artisan tinker
```

```php
// View all flagged websites
App\Models\FlaggedWebsite::with('device')->get();

// View latest
$flagged = App\Models\FlaggedWebsite::latest()->first();
echo "URL: " . $flagged->url . "\n";
echo "Domain: " . $flagged->domain . "\n";
echo "Device: " . $flagged->device->name . "\n";
```

### Check with SQL

```bash
mysql -u root -p parental_wifi
```

```sql
SELECT * FROM flagged_websites ORDER BY created_at DESC LIMIT 5;
```

---

## 🧪 Test Scenarios

### Scenario 1: Basic CRUD
1. Create → View → Edit → Delete
2. Verify each step works

### Scenario 2: Domain Extraction
Create flagged websites with different URLs:
- `https://example.com` → Domain: `example.com`
- `http://www.facebook.com` → Domain: `facebook.com`
- `https://subdomain.example.com/page` → Domain: `subdomain.example.com`

### Scenario 3: Unique Constraint
1. Flag `example.com` for Device A
2. Try to flag `example.com` again for Device A → Should fail
3. Flag `example.com` for Device B → Should work

### Scenario 4: Multiple Devices
1. Create flagged websites for different devices
2. Filter by each device
3. Verify separation

---

## ⚠️ Common Issues & Solutions

### Issue: "No devices available"
**Solution**: Create a device first via `/accounts/create`

### Issue: "Validation error"
**Solution**: 
- Check URL format (must start with `http://` or `https://`)
- Make sure device is selected
- Check URL length (max 500 characters)

### Issue: "Domain already flagged"
**Solution**: 
- This is expected - same domain can't be flagged twice for same device
- Use different domain or different device

### Issue: "Access denied"
**Solution**: 
- Make sure you're logged in
- Make sure you own the device
- Check authorization policy

---

## 📊 Expected Results

### Create Success
- ✅ Redirect to list page
- ✅ Green success message
- ✅ New entry in list

### Edit Success
- ✅ Redirect to list page
- ✅ Green success message
- ✅ Updated values in list

### Delete Success
- ✅ Redirect to list page
- ✅ Green success message
- ✅ Entry removed from list

### Validation Errors
- ✅ Red error messages
- ✅ Form shows error fields
- ✅ No database changes

---

## 🎯 Key Points to Verify

1. **Domain Extraction**: Domain should be extracted from URL automatically
2. **Not Blocked**: Flagged websites should be accessible (not blocked like blocked websites)
3. **Authorization**: Users can only manage their own devices' flagged websites
4. **Unique Constraint**: Same domain can't be flagged twice for same device
5. **Filtering**: Device filter and search work correctly

---

## 📝 Notes

- **Flagged websites are NOT blocked** - they're just monitored
- **Reason is optional** - can be left blank
- **Domain is auto-extracted** - you only enter the URL
- **Pagination**: 20 items per page
- **Authorization**: Enforced at controller level

---

## 🔗 Related Documentation

- Full testing guide: `docs/FLAGGED_WEBSITES_TESTING_GUIDE.md`
- Test results: `docs/TEST_FLAGGED_WEBSITE_RESULTS.md`
- Implementation: `docs/WEBSITE_MANAGEMENT_IMPLEMENTATION.md`

---

**Happy Testing! 🎉**

