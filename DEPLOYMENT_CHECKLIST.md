# Deployment Checklist for Raspberry Pi

## ✅ Functionality Verification

All portal functionality has been preserved with the new design:

### Portal Pages
- ✅ **Landing Page** (`/portal?mac=...`) - Shows quizzes and videos
- ✅ **Quiz Page** (`/portal/quiz/{id}?mac=...`) - Question navigation, timer, form submission
- ✅ **Quiz Result Page** - Pass/fail display, time granted popup, auto-redirect
- ✅ **Video Page** (`/portal/video/{id}?mac=...`) - Video player, word overlays, form submission
- ✅ **Video Result Page** - Pass/fail display, time granted popup, auto-redirect

### Key Features Preserved
- ✅ Quiz timer (10 minutes countdown)
- ✅ Question navigation (Previous/Next buttons)
- ✅ Form submissions with CSRF protection
- ✅ Video word overlay system
- ✅ Time granting functionality
- ✅ Auto-redirect after success (3 seconds)
- ✅ MAC address tracking in all routes
- ✅ Session management for quiz attempts and video completions

### Routes Verified
All routes are using Laravel's `route()` helper (no hardcoded URLs):
- `portal.landing`
- `portal.quiz.show`
- `portal.quiz.submit`
- `portal.quiz.result`
- `portal.video.show`
- `portal.video.submit`
- `portal.video.result`

## 🎨 Design Updates Applied
- Yellow color scheme (#FFDE15) throughout
- Montserrat font family
- Card patterns with border-4 border-[#FFDE15]
- Rounded corners (rounded-xl)
- Modern shadows and transitions
- Responsive design

## 📋 Pre-Deployment Checklist

### 1. Environment Configuration
- [ ] Update `.env` file with production settings
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure database connection
- [ ] Set proper `APP_URL` (Raspberry Pi IP address)

### 2. Database
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed default data if needed: `php artisan db:seed`
- [ ] Verify devices exist with correct MAC addresses

### 3. Permissions
- [ ] Storage permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Ensure `ndsctl` has proper sudo permissions for MAC address lookup

### 4. Cache & Optimization
- [ ] Clear all caches: `php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear`
- [ ] Optimize for production: `php artisan config:cache && php artisan route:cache && php artisan view:cache`

### 5. NoDogSplash Integration
- [ ] Verify NoDogSplash is running
- [ ] Test MAC address lookup via `ndsctl clients`
- [ ] Ensure portal routes are accessible without authentication

### 6. Testing
- [ ] Test portal landing page with device MAC address
- [ ] Test quiz taking flow (start → answer → submit → result)
- [ ] Test video watching flow (play → words appear → submit → result)
- [ ] Verify time granting works correctly
- [ ] Test on mobile devices (children's devices)

## 🔗 Test Links

After deployment, test with:
```
http://YOUR_RASPBERRY_PI_IP/portal?mac=AA:BB:CC:DD:EE:FF
```

Replace `YOUR_RASPBERRY_PI_IP` with your Raspberry Pi's IP address.

## 🐛 Common Issues & Solutions

### Issue: Portal not loading
- **Solution**: Check `.env` `APP_URL` matches Raspberry Pi IP
- **Solution**: Verify web server (Apache/Nginx) is running
- **Solution**: Check Laravel routes are accessible

### Issue: MAC address not detected
- **Solution**: Verify device exists in database
- **Solution**: Check `ndsctl` permissions (sudo access)
- **Solution**: Test MAC lookup manually: `sudo ndsctl clients`

### Issue: Forms not submitting
- **Solution**: Verify CSRF token is included (should be automatic)
- **Solution**: Check JavaScript console for errors
- **Solution**: Ensure routes are cached: `php artisan route:cache`

### Issue: Time not granting
- **Solution**: Check `TimeGrantingService` logs
- **Solution**: Verify device status is 'active'
- **Solution**: Check database for time allocation records

## 📝 Notes

- All portal pages use CDN resources (Tailwind CSS, Google Fonts) - ensure internet connection
- Video files should be stored in `storage/app/public/videos/`
- Quiz questions are stored in JSON format in database
- Session storage is used for quiz attempts and video completions

## ✅ Post-Deployment Verification

1. Access portal landing page
2. Click on a quiz → answer questions → submit
3. Verify time granted popup appears
4. Access portal again → verify time was added
5. Click on a video → watch → enter words → submit
6. Verify time granted popup appears
7. Check dashboard to verify time tracking works

---

**All functionality has been preserved. The design updates are purely visual and do not affect any backend logic or JavaScript functionality.**

