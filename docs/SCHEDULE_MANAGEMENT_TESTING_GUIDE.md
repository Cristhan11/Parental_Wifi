# Device Schedule Management - Local Testing Guide

**Date:** December 21, 2025  
**Feature:** Device Schedule Management (Time-based Access Control)  
**Status:** ✅ Ready for Testing

---

## Quick Start

1. **Start Laravel server**: `php artisan serve`
2. **Access application**: `http://localhost:8000`
3. **Log in** as a parent user
4. **Navigate to**: General Settings → Schedules
5. **Create a schedule** and test it

---

## Prerequisites

Before testing, ensure:

- [ ] Laravel application is running (`php artisan serve`)
- [ ] Database migrations are run (`php artisan migrate`)
- [ ] At least one child device is registered in the database
- [ ] You are logged in as a parent user
- [ ] Browser is accessible to `http://localhost:8000`

---

## Test 1: Access Schedules Page

**Objective:** Verify the schedules page is accessible and navigation works.

### Steps:

1. **Start Laravel server** (if not already running):
   ```bash
   php artisan serve
   ```

2. **Open browser** and go to: `http://localhost:8000`

3. **Log in** as a parent user

4. **Click "General Settings"** dropdown in navigation (top right)

5. **Click "Schedules"** from the dropdown

### Expected Results:

- ✅ Page loads without errors
- ✅ URL is: `http://localhost:8000/schedules`
- ✅ Page title shows "DEVICE SCHEDULES"
- ✅ "Create Schedule" button is visible (red button, top right)
- ✅ Info banner explains what schedules are
- ✅ Filter form is visible (Device, Day of Week, Status)
- ✅ Schedule list is visible (empty if no schedules exist)

---

## Test 2: Create Schedule

**Objective:** Verify creating a new schedule works correctly.

### Steps:

1. **Click "Create Schedule"** button

2. **Fill in the form**:
   - **Device**: Select a child device from dropdown
   - **Day of Week**: Select "Monday"
   - **Start Time**: Enter `15:00` (3:00 PM)
   - **End Time**: Enter `21:00` (9:00 PM)
   - **Daily Duration Limit**: Leave empty (no limit) OR enter `120` (2 hours)
   - **Active**: Check the checkbox (should be checked by default)

3. **Click "Create Schedule"** button

### Expected Results:

- ✅ Redirected to schedules list page
- ✅ Green success message: "Schedule created successfully."
- ✅ New schedule appears in the list
- ✅ Schedule shows correct device name
- ✅ Schedule shows "Monday" as day
- ✅ Schedule shows "3:00 PM - 9:00 PM" as time window
- ✅ Schedule shows duration limit (or "No limit")
- ✅ Schedule shows "Active" status badge (green)

---

## Test 3: View Schedule List

**Objective:** Verify the schedule list displays correctly.

### Steps:

1. **Navigate to schedules page**: `http://localhost:8000/schedules`

2. **Verify schedule list** shows:
   - Device name
   - Day of week (capitalized: "Monday", "Tuesday", etc.)
   - Time window (12-hour format: "3:00 PM - 9:00 PM")
   - Duration limit (e.g., "2h 0m" or "No limit")
   - Status badge ("Active" or "Inactive")
   - Edit and Delete buttons

### Expected Results:

- ✅ All schedules are displayed in a table
- ✅ Information is formatted correctly
- ✅ Time is shown in 12-hour format (AM/PM)
- ✅ Duration limit is formatted (hours and minutes)
- ✅ Status badges are color-coded (green for Active, gray for Inactive)

---

## Test 4: Filter Schedules

**Objective:** Verify filtering works correctly.

### Steps:

1. **Create multiple schedules** for different devices and days:
   - Schedule 1: Device A, Monday, 3:00 PM - 9:00 PM
   - Schedule 2: Device B, Tuesday, 2:00 PM - 8:00 PM
   - Schedule 3: Device A, Wednesday, 4:00 PM - 10:00 PM

2. **Filter by Device**:
   - Select "Device A" from device dropdown
   - Click "Filter"
   - **Expected**: Only schedules for Device A are shown

3. **Filter by Day**:
   - Select "Monday" from day dropdown
   - Click "Filter"
   - **Expected**: Only Monday schedules are shown

4. **Filter by Status**:
   - Select "Active" from status dropdown
   - Click "Filter"
   - **Expected**: Only active schedules are shown

5. **Combine Filters**:
   - Select Device A + Monday + Active
   - Click "Filter"
   - **Expected**: Only matching schedules are shown

### Expected Results:

- ✅ Filters work independently
- ✅ Filters can be combined
- ✅ Filter results are accurate
- ✅ URL parameters update (e.g., `?device_id=1&day_of_week=monday`)

---

## Test 5: Edit Schedule

**Objective:** Verify editing an existing schedule works.

### Steps:

1. **Click "Edit"** on an existing schedule

2. **Modify the form**:
   - Change day of week to "Tuesday"
   - Change start time to `14:00` (2:00 PM)
   - Change end time to `20:00` (8:00 PM)
   - Change duration limit to `180` (3 hours)
   - Uncheck "Active" checkbox

3. **Click "Update Schedule"** button

### Expected Results:

- ✅ Redirected to schedules list
- ✅ Green success message: "Schedule updated successfully."
- ✅ Updated schedule shows new values in the list
- ✅ Day changed to "Tuesday"
- ✅ Time window changed to "2:00 PM - 8:00 PM"
- ✅ Duration limit changed to "3h 0m"
- ✅ Status changed to "Inactive" (gray badge)

---

## Test 6: Delete Schedule

**Objective:** Verify deleting a schedule works.

### Steps:

1. **Click "Delete"** on an existing schedule

2. **Confirm deletion** in the browser prompt

### Expected Results:

- ✅ Confirmation dialog appears
- ✅ After confirmation, schedule is deleted
- ✅ Redirected to schedules list
- ✅ Green success message: "Schedule removed successfully."
- ✅ Schedule no longer appears in the list

---

## Test 7: Validation Tests

**Objective:** Verify form validation works correctly.

### Test 7a: Required Fields

1. **Try to create schedule without device**:
   - Leave device dropdown empty
   - Fill other fields
   - Click "Create Schedule"
   - **Expected**: Error message "Please select a device."

2. **Try to create schedule without day**:
   - Select device, leave day empty
   - Fill other fields
   - Click "Create Schedule"
   - **Expected**: Error message "Please select a day of the week."

3. **Try to create schedule without start time**:
   - Fill device and day, leave start time empty
   - Click "Create Schedule"
   - **Expected**: Error message "Start time is required."

4. **Try to create schedule without end time**:
   - Fill device, day, start time, leave end time empty
   - Click "Create Schedule"
   - **Expected**: Error message "End time is required."

### Test 7b: Time Validation

1. **Try end time before start time**:
   - Start time: `21:00` (9:00 PM)
   - End time: `15:00` (3:00 PM)
   - Click "Create Schedule"
   - **Expected**: Error message "End time must be after start time."

2. **Try same start and end time**:
   - Start time: `15:00`
   - End time: `15:00`
   - Click "Create Schedule"
   - **Expected**: Error message "End time must be after start time."

### Test 7c: Duration Limit Validation

1. **Try negative duration**:
   - Duration limit: `-10`
   - Click "Create Schedule"
   - **Expected**: Error message about minimum value

2. **Try duration over 24 hours**:
   - Duration limit: `2000` (more than 1440 minutes)
   - Click "Create Schedule"
   - **Expected**: Error message "Duration limit cannot exceed 1440 minutes (24 hours)."

3. **Try zero duration**:
   - Duration limit: `0`
   - Click "Create Schedule"
   - **Expected**: Error message about minimum value

### Expected Results:

- ✅ All validation errors are displayed
- ✅ Form shows error messages next to invalid fields
- ✅ No schedule is created when validation fails
- ✅ Form retains entered values (except invalid ones)

---

## Test 8: Authorization Test

**Objective:** Verify users can only manage schedules for their own devices.

### Prerequisites:

- You need at least 2 parent users in the database
- Each user should have at least one device

### Steps:

1. **Log in as User A** (parent)

2. **Create a schedule** for User A's device

3. **Log out**

4. **Log in as User B** (different parent)

5. **Try to access User A's schedule**:
   - Go to schedules page
   - **Expected**: User A's schedule is NOT visible (filtered out)

6. **Try to edit User A's schedule directly** (if you know the ID):
   - Go to: `http://localhost:8000/schedules/{schedule_id}/edit`
   - **Expected**: 403 Forbidden error or redirect

7. **Try to delete User A's schedule directly**:
   - Use DELETE request to: `http://localhost:8000/schedules/{schedule_id}`
   - **Expected**: 403 Forbidden error

### Expected Results:

- ✅ Users can only see schedules for their own devices
- ✅ Users cannot edit schedules for other users' devices
- ✅ Users cannot delete schedules for other users' devices
- ✅ Authorization errors are handled gracefully

---

## Test 9: Database Verification

**Objective:** Verify schedules are stored correctly in the database.

### Steps:

1. **Create a schedule** via the web interface

2. **Verify in database** using tinker:
   ```bash
   php artisan tinker
   ```

   ```php
   // View all schedules
   App\Models\DeviceSchedule::with('device')->get();

   // View latest schedule
   $schedule = App\Models\DeviceSchedule::latest()->first();
   echo "Device: " . $schedule->device->name . "\n";
   echo "Day: " . $schedule->day_of_week . "\n";
   echo "Start: " . $schedule->start_time . "\n";
   echo "End: " . $schedule->end_time . "\n";
   echo "Duration Limit: " . ($schedule->duration_limit_minutes ?? 'NULL') . "\n";
   echo "Active: " . ($schedule->is_active ? 'true' : 'false') . "\n";
   exit
   ```

### Expected Results:

- ✅ Schedule is stored in `device_schedules` table
- ✅ All fields are saved correctly
- ✅ Device relationship works (can access `$schedule->device`)
- ✅ Time values are stored correctly (TIME format)

---

## Test 10: Multiple Schedules Per Device

**Objective:** Verify multiple schedules can be created for the same device.

### Steps:

1. **Create Schedule 1**:
   - Device: Device A
   - Day: Monday
   - Time: 3:00 PM - 9:00 PM

2. **Create Schedule 2**:
   - Device: Device A
   - Day: Monday
   - Time: 10:00 AM - 12:00 PM

3. **Create Schedule 3**:
   - Device: Device A
   - Day: Tuesday
   - Time: 2:00 PM - 8:00 PM

### Expected Results:

- ✅ All three schedules are created successfully
- ✅ All schedules appear in the list
- ✅ Multiple schedules per device per day are allowed
- ✅ Schedules can have overlapping or non-overlapping time windows

---

## Test 11: Time Format Display

**Objective:** Verify time is displayed correctly in 12-hour format.

### Steps:

1. **Create schedules with different times**:
   - Morning: 8:00 AM - 12:00 PM
   - Afternoon: 1:00 PM - 5:00 PM
   - Evening: 6:00 PM - 10:00 PM
   - Night: 10:00 PM - 11:59 PM

2. **Verify display** in the schedules list

### Expected Results:

- ✅ Times are displayed in 12-hour format (AM/PM)
- ✅ Morning times show "AM"
- ✅ Afternoon/evening times show "PM"
- ✅ Format is consistent: "g:i A" (e.g., "3:00 PM", "10:30 AM")

---

## Test 12: Duration Limit Display

**Objective:** Verify duration limits are displayed correctly.

### Steps:

1. **Create schedules with different duration limits**:
   - No limit (leave empty)
   - 30 minutes
   - 60 minutes (1 hour)
   - 90 minutes (1.5 hours)
   - 120 minutes (2 hours)
   - 180 minutes (3 hours)

2. **Verify display** in the schedules list

### Expected Results:

- ✅ Duration limits are formatted as "Xh Ym" (e.g., "2h 0m", "1h 30m")
- ✅ Schedules with no limit show "No limit" (gray text)
- ✅ Formatting is consistent and readable

---

## Test 13: Active/Inactive Toggle

**Objective:** Verify active/inactive status works correctly.

### Steps:

1. **Create a schedule** with Active checked

2. **Edit the schedule** and uncheck Active

3. **Verify status** in the list

4. **Filter by "Inactive"** status

5. **Edit again** and check Active

6. **Verify status** changes back to Active

### Expected Results:

- ✅ Active schedules show green "Active" badge
- ✅ Inactive schedules show gray "Inactive" badge
- ✅ Status can be toggled via edit form
- ✅ Filter by status works correctly

---

## Quick Testing Checklist

Use this checklist for quick testing:

- [ ] Schedules page is accessible via General Settings dropdown
- [ ] Create schedule form loads correctly
- [ ] Can create a schedule with all fields
- [ ] Schedule appears in list after creation
- [ ] Can edit an existing schedule
- [ ] Can delete an existing schedule
- [ ] Filter by device works
- [ ] Filter by day of week works
- [ ] Filter by status works
- [ ] Validation errors display correctly
- [ ] Time format is correct (12-hour with AM/PM)
- [ ] Duration limit displays correctly
- [ ] Active/Inactive status works
- [ ] Authorization prevents accessing other users' schedules

---

## Common Issues & Solutions

### Issue: "Route [schedules.index] not defined"

**Solution:**
- Clear route cache: `php artisan route:clear`
- Verify routes are in `routes/web.php`
- Check route names match exactly

### Issue: "Policy [DeviceSchedulePolicy] does not exist"

**Solution:**
- Verify policy file exists: `app/Policies/DeviceSchedulePolicy.php`
- Clear config cache: `php artisan config:clear`
- Laravel 11 auto-discovers policies, but you may need to clear cache

### Issue: Time fields show as strings instead of formatted

**Solution:**
- Verify DeviceSchedule model has accessors for start_time and end_time
- Clear model cache: `php artisan optimize:clear`
- Check that accessors return Carbon instances

### Issue: "Call to a member function format() on string"

**Solution:**
- This means the accessor isn't working
- Verify DeviceSchedule model has `getStartTimeAttribute()` and `getEndTimeAttribute()` methods
- Check that Carbon is imported in the model

### Issue: Validation errors not showing

**Solution:**
- Check that form has `@error` directives
- Verify form requests are being used (StoreDeviceScheduleRequest, UpdateDeviceScheduleRequest)
- Check browser console for JavaScript errors

---

## Database Verification Commands

### View All Schedules

```bash
php artisan tinker
```

```php
App\Models\DeviceSchedule::with('device')->get()->map(function($s) {
    return [
        'id' => $s->id,
        'device' => $s->device->name,
        'day' => $s->day_of_week,
        'start' => $s->start_time->format('H:i'),
        'end' => $s->end_time->format('H:i'),
        'limit' => $s->duration_limit_minutes,
        'active' => $s->is_active,
    ];
});
exit
```

### Count Schedules by Device

```php
App\Models\DeviceSchedule::with('device')
    ->get()
    ->groupBy('device.name')
    ->map->count();
```

### Check Schedule Enforcement

```php
// Check if EnforceSchedules job would find this schedule
$schedule = App\Models\DeviceSchedule::first();
$currentDay = strtolower(now()->format('l'));
echo "Current day: $currentDay\n";
echo "Schedule day: {$schedule->day_of_week}\n";
echo "Matches: " . ($currentDay === $schedule->day_of_week ? 'YES' : 'NO') . "\n";
```

---

## Expected Behavior Summary

### Schedule Creation
- ✅ Form validates all required fields
- ✅ Time window validation (end > start)
- ✅ Duration limit validation (1-1440 minutes)
- ✅ Schedule is saved to database
- ✅ Success message is displayed

### Schedule Display
- ✅ List shows all schedules for user's devices
- ✅ Time in 12-hour format (AM/PM)
- ✅ Duration formatted as "Xh Ym"
- ✅ Status badges are color-coded
- ✅ Pagination works (20 per page)

### Schedule Filtering
- ✅ Filter by device works
- ✅ Filter by day works
- ✅ Filter by status works
- ✅ Filters can be combined
- ✅ URL parameters persist

### Schedule Editing
- ✅ Form pre-fills with existing values
- ✅ Validation works same as create
- ✅ Updates are saved correctly
- ✅ Success message is displayed

### Schedule Deletion
- ✅ Confirmation dialog appears
- ✅ Schedule is removed from database
- ✅ Success message is displayed
- ✅ List updates immediately

---

## Next Steps After Testing

Once local testing is complete:

1. **Test on Raspberry Pi** (if available)
2. **Verify EnforceSchedules job** respects the schedules
3. **Test schedule enforcement** with actual device connections
4. **Verify time-based blocking/unblocking** works correctly

---

## Related Documentation

- Implementation: See plan file for architecture details
- EnforceSchedules Job: `app/Jobs/EnforceSchedules.php`
- DeviceSchedule Model: `app/Models/DeviceSchedule.php`
- Scope: `docs/scope.md` (TODO #20)

---

**Happy Testing! 🎉**

