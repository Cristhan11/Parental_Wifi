<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid MAC Address Validation Rule
 * 
 * This custom validation rule validates MAC address format and ensures it
 * matches the standard format used by the system (XX:XX:XX:XX:XX:XX).
 * 
 * What is a MAC Address?
 * - MAC (Media Access Control) address is a unique identifier for network interfaces
 * - Format: 6 pairs of 2 hexadecimal characters, separated by colons or hyphens
 * - Example: "AA:BB:CC:DD:EE:FF" or "AA-BB-CC-DD-EE-FF"
 * - Each device has a unique MAC address (like a fingerprint)
 * 
 * Why Do We Need This Rule?
 * - MAC addresses are critical for device identification and network blocking
 * - Invalid MAC addresses would cause network operations to fail
 * - We need to ensure MAC addresses are in the correct format before storing
 * - Prevents errors when executing network scripts (block_device.sh, etc.)
 * 
 * Accepted Formats:
 * - Colon format: "AA:BB:CC:DD:EE:FF" (preferred, stored format)
 * - Hyphen format: "AA-BB-CC-DD-EE-FF" (accepted, will be normalized)
 * - Case insensitive: "aa:bb:cc:dd:ee:ff" is valid (will be normalized to uppercase)
 * 
 * Validation Logic:
 * 1. Check if MAC address matches pattern: 6 pairs of 2 hex characters
 * 2. Accept both colon (:) and hyphen (-) separators
 * 3. Accept uppercase and lowercase hexadecimal characters (0-9, A-F, a-f)
 * 4. Ensure exactly 6 pairs (12 characters total)
 * 
 * Normalization:
 * - This rule validates format but doesn't normalize
 * - Normalization (converting to uppercase, colons) should be done in the controller
 * - This ensures we validate before normalization
 * 
 * Usage Example:
 * ```php
 * // In StoreDeviceRequest
 * 'mac_address' => ['required', new ValidMacAddress, 'unique:devices,mac_address']
 * 
 * // The rule will validate:
 * // ✅ "AA:BB:CC:DD:EE:FF" - Valid (colon format)
 * // ✅ "AA-BB-CC-DD-EE-FF" - Valid (hyphen format)
 * // ✅ "aa:bb:cc:dd:ee:ff" - Valid (lowercase, will be normalized)
 * // ❌ "AA:BB:CC:DD:EE" - Invalid (only 5 pairs)
 * // ❌ "AA:BB:CC:DD:EE:FF:GG" - Invalid (7 pairs)
 * // ❌ "AA:BB:CC:DD:EE:GG" - Invalid (GG is not hexadecimal)
 * // ❌ "AA BB CC DD EE FF" - Invalid (space separator not accepted)
 * ```
 */
class ValidMacAddress implements ValidationRule
{
    /**
     * Run the validation rule.
     * 
     * This method is called automatically by Laravel when validating the attribute.
     * It checks if the MAC address matches the required format.
     * 
     * How It Works:
     * 1. Receives the attribute value (the MAC address string)
     * 2. Validates against regex pattern
     * 3. If valid: calls $fail closure with nothing (validation passes)
     * 4. If invalid: calls $fail closure with error message (validation fails)
     * 
     * Regex Pattern Explanation:
     * - `^` = Start of string
     * - `([0-9A-Fa-f]{2})` = Exactly 2 hexadecimal characters (0-9, A-F, a-f)
     * - `[:-]` = Separator (colon OR hyphen)
     * - `([0-9A-Fa-f]{2}[:-]){5}` = Repeat pattern 5 more times (for 6 total pairs)
     * - `([0-9A-Fa-f]{2})` = Final pair (no separator after last pair)
     * - `$` = End of string
     * 
     * This pattern ensures:
     * - Exactly 6 pairs of 2 hexadecimal characters
     * - Colon or hyphen separators between pairs
     * - No separator after the last pair
     * - Case insensitive (accepts both uppercase and lowercase)
     * 
     * @param string $attribute The name of the attribute being validated (e.g., "mac_address")
     * @param mixed $value The value of the attribute (the MAC address string)
     * @param Closure $fail Callback to call if validation fails (with error message)
     * @return void No return value - validation result is communicated via $fail callback
     * 
     * Usage:
     * This method is called automatically by Laravel. You don't call it directly.
     * Laravel calls it when validating attributes that use this rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Step 1: Check if value is a string
        // MAC address must be a string, not an array or object
        if (!is_string($value)) {
            // Value is not a string - validation fails
            // $fail() is called with error message
            // Laravel will display this message to the user
            $fail("The {$attribute} must be a valid MAC address format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX).");
            return; // Exit early - no need to continue validation
        }

        // Step 2: Validate MAC address format using regex pattern
        // Pattern matches: 6 pairs of 2 hexadecimal characters separated by colons or hyphens
        // 
        // Pattern breakdown:
        // - ^ = Start of string (ensures no leading characters)
        // - ([0-9A-Fa-f]{2}) = First pair of 2 hex characters (0-9, A-F, a-f)
        // - [:-] = Separator (colon OR hyphen)
        // - ([0-9A-Fa-f]{2}[:-]){5} = Repeat pattern 5 times (pairs 2-6 with separators)
        // - ([0-9A-Fa-f]{2}) = Final pair (no separator after last pair)
        // - $ = End of string (ensures no trailing characters)
        //
        // This ensures exactly 6 pairs of 2 hex characters with separators between them
        $pattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';

        // preg_match() returns 1 if pattern matches, 0 if no match, false on error
        // We check if result is exactly 1 (pattern matched)
        if (preg_match($pattern, $value) !== 1) {
            // Pattern didn't match - MAC address format is invalid
            // Call $fail() with error message
            // Laravel will display this message to the user in the form validation errors
            $fail("The {$attribute} must be a valid MAC address format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX).");
            return; // Exit - validation failed
        }

        // Step 3: Validation passed
        // If we reach here, MAC address format is valid
        // We don't call $fail(), so Laravel considers validation successful
        // The MAC address will be accepted and can be normalized in the controller
    }
}
