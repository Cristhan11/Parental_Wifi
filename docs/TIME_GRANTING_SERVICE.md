# Time Granting Service Summary

## Purpose
Grants additional internet time to devices after successful quiz completion or video completion with dictionary word validation. Centralizes validation, logging, and device unblocking.

## Responsibilities
- Validate quiz attempts (must be passed)
- Validate video completions (must pass dictionary word validation)
- Extract time rewards from quiz/video configurations
- Grant time to devices via Device model
- Unblock devices after time grant (if previously blocked)
- Log all operations for audit trail

## Core Methods

### 1. `grantTimeFromQuiz(Device $device, QuizAttempt $quizAttempt): ?DeviceTimeGrant`
- Validates quiz attempt belongs to the device
- Validates quiz was passed (`passed === true`)
- Validates attempt is completed (`completed_at` exists)
- Gets time reward from `quiz->time_reward_minutes`
- Calls generic `grantTime()` method
- Returns `DeviceTimeGrant` or `null` if validation fails

### 2. `grantTimeFromVideo(Device $device, VideoCompletion $videoCompletion): ?DeviceTimeGrant`
- Validates video completion belongs to the device
- Validates dictionary word validation passed (`passed_validation === true`)
- Extra safety check: ensures all displayed words were answered correctly (`words_correct >= words_shown_count`)
- Validates completion timestamp exists
- Gets time reward from `video->time_reward_minutes`
- Calls generic `grantTime()` method
- Returns `DeviceTimeGrant` or `null` if validation fails

### 3. `grantTime(Device $device, int $minutes, string $source, ?int $sourceId = null): DeviceTimeGrant`
- Generic method for granting time
- Validates minutes > 0 (throws exception if invalid)
- Calls `Device::grantTime()` (handles database operations)
- Refreshes device to get latest data
- Checks if device should be unblocked
- Logs the grant operation
- Returns the created `DeviceTimeGrant`

### 4. `shouldUnblockDevice(Device $device): bool`
- Helper method to determine if device should be unblocked
- Returns true if device status is `'blocked'` **and** has remaining time > 0
- Used before calling unblocking logic

### 5. `unblockDevice(Device $device): void`
- Unblocks device after time grant
- Updates device status from `'blocked'` to `'active'`
- Logs unblocking operation
- **TODO:** Will integrate with `NetworkService::unblockDevice()` for network-level unblocking (iptables/nftables)

## Features
- **Validation:** Ensures quiz passed and video dictionary words validated before granting
- **Safety checks:** Device ownership, completion timestamps, time reward configuration
- **Automatic unblocking:** Unblocks devices that were blocked when time expires
- **Logging:** All operations logged (success, failure, warnings)
- **Error handling:** Returns `null` on validation failure (doesn't throw exceptions for business logic failures)
- **Future-ready:** Placeholder for NetworkService integration

## Design Pattern
- **Service layer:** Business logic separated from HTTP layer
- **Reusable:** Can be called by controllers, jobs, and commands
- **No HTTP dependencies:** Works in any context
- **Defensive programming:** Validates all inputs before processing

## Integration Points
- Called by `PortalController` after quiz/video completion
- Uses Device model's `grantTime()` method (handles database operations)
- Creates `DeviceTimeGrant` records (audit trail)
- Prepares for `NetworkService` integration (unblocking placeholder)

## Workflow Example
1. Child completes quiz → `QuizAttempt` created with `passed = true`
2. `PortalController` calls `grantTimeFromQuiz($device, $quizAttempt)`
3. Service validates: quiz passed, completed, has time reward
4. Service calls `grantTime()` which:
   - Adds time to device's `remaining_time_minutes`
   - Creates `DeviceTimeGrant` record
   - Unblocks device if it was blocked
5. Device can now browse again with newly granted time

## Key Differences from TimeTrackingService
- **TimeTrackingService:** Deducts time (tracks usage)
- **TimeGrantingService:** Grants time (rewards completion)
- They work together: TimeTracking deducts, TimeGranting adds

This service completes the time management system by handling the reward side of the captive portal flow.


