<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Script Executor Service
 * 
 * This service provides a secure wrapper for executing shell scripts from PHP.
 * It acts as a security layer that validates, sanitizes, and safely executes
 * only whitelisted scripts with proper error handling.
 * 
 * Why Do We Need This Service?
 * 
 * Executing shell commands from PHP is a security risk. Without proper controls,
 * an attacker could potentially execute arbitrary commands on the system. This
 * service provides multiple layers of security:
 * 
 * 1. **Whitelisting**: Only pre-approved scripts can be executed
 * 2. **Path Validation**: Prevents path traversal attacks (e.g., ../../../etc/passwd)
 * 3. **Input Sanitization**: Validates and sanitizes all arguments before passing to scripts
 * 4. **Error Handling**: Captures and logs all errors for security auditing
 * 5. **Return Code Checking**: Verifies script execution success/failure
 * 
 * Security Model:
 * 
 * - **Whitelist Approach**: We maintain a list of allowed scripts. Only scripts
 *   in this list can be executed. This prevents arbitrary command execution.
 * 
 * - **Path Validation**: We validate that script paths are within the allowed
 *   directory (scripts/) and don't contain path traversal sequences (../).
 * 
 * - **Input Sanitization**: All arguments passed to scripts are validated and
 *   sanitized. For example, MAC addresses are validated against a regex pattern
 *   before being passed to scripts.
 * 
 * - **Execution Isolation**: Scripts are executed with explicit paths and
 *   arguments, preventing command injection attacks.
 * 
 * How It Works:
 * 
 * 1. Script name is checked against whitelist
 * 2. Script path is validated (exists, is executable, within allowed directory)
 * 3. Arguments are sanitized and validated
 * 4. Script is executed with proper error handling
 * 5. Output and return code are captured
 * 6. Results are logged for audit trail
 * 
 * Integration with NetworkService:
 * 
 * NetworkService uses ScriptExecutor to execute network control scripts:
 * - block_device.sh: Blocks a device's MAC address
 * - unblock_device.sh: Unblocks a device's MAC address
 * - whitelist_device.sh: Whitelists a device (bypasses restrictions)
 * - get_connected_devices.sh: Gets list of connected devices
 * - monitor_traffic.sh: Gets traffic statistics
 * 
 * Usage Example:
 * ```php
 * $executor = new ScriptExecutor();
 * 
 * // Execute block_device.sh with MAC address argument
 * $result = $executor->execute('block_device.sh', ['AA:BB:CC:DD:EE:FF']);
 * 
 * if ($result['success']) {
 *     echo "Script executed successfully";
 *     echo "Output: " . $result['output'];
 * } else {
 *     echo "Script failed: " . $result['error'];
 * }
 * ```
 * 
 * Error Handling:
 * 
 * - All errors are logged for security auditing
 * - Script execution failures return false but don't throw exceptions
 * - This allows calling code to handle errors gracefully
 * - Detailed error messages help with debugging
 * 
 * Security Considerations:
 * 
 * - Never execute user input directly without validation
 * - Always use whitelist approach (not blacklist)
 * - Validate all paths to prevent directory traversal
 * - Sanitize all arguments before passing to scripts
 * - Log all executions for security audit trail
 * - Use sudoers configuration to limit script permissions
 */
class ScriptExecutor
{
    /**
     * Whitelist of allowed scripts that can be executed.
     * 
     * This is a security measure - only scripts in this list can be executed.
     * This prevents arbitrary command execution and command injection attacks.
     * 
     * Why Whitelist Instead of Blacklist?
     * - Whitelist: Only allow known-good scripts (secure)
     * - Blacklist: Block known-bad scripts (insecure - new attacks can bypass)
     * 
     * Adding New Scripts:
     * - Add script name to this array
     * - Ensure script exists in scripts/ directory
     * - Ensure script has proper permissions (executable)
     * - Test script execution manually before using in production
     * 
     * @var array<string>
     */
    protected array $allowedScripts = [
        'block_device.sh',
        'unblock_device.sh',
        'whitelist_device.sh',
        'get_connected_devices.sh',
        'monitor_traffic.sh',
        'redirect_device_portal.sh',
        'allow_device_through.sh',
        'check_device_redirected.sh',
        'block_domain.sh',
        'unblock_domain.sh',
        'update_dnsmasq_blocklist.sh',
    ];

    /**
     * Base directory where scripts are located.
     * 
     * This is the root directory for all executable scripts. Script paths
     * are validated to ensure they're within this directory to prevent
     * path traversal attacks (e.g., ../../../etc/passwd).
     * 
     * Why base_path('scripts')?
     * - base_path() returns the root directory of the Laravel application
     * - This ensures we always use the correct path regardless of where
     *   the code is executed from
     * - On Raspberry Pi, this will be /var/www/parental_wifi/scripts
     * 
     * @var string
     */
    protected string $scriptsDirectory;

    /**
     * Constructor - Initialize the ScriptExecutor.
     * 
     * Sets up the scripts directory path using Laravel's base_path() helper.
     * This ensures we always reference the correct scripts directory regardless
     * of the current working directory.
     * 
     * Why base_path()?
     * - Returns absolute path to Laravel root directory
     * - Works correctly even if PHP's current working directory changes
     * - Provides consistent path across different execution contexts
     * 
     * Example paths:
     * - Development: /home/user/projects/parental_wifi/scripts
     * - Production: /var/www/parental_wifi/scripts
     */
    public function __construct()
    {
        // Set scripts directory to base_path('scripts')
        // base_path() returns the root directory of the Laravel application
        // This ensures we always use the correct path
        $this->scriptsDirectory = base_path('scripts');
    }

    /**
     * Execute a shell script with the given arguments.
     * 
     * This is the main method for executing scripts. It performs all security
     * checks, executes the script, and returns the results.
     * 
     * Security Flow:
     * 1. Check if script is in whitelist (isScriptAllowed)
     * 2. Validate script path (validateScriptPath)
     * 3. Sanitize and validate arguments
     * 4. Build command with proper escaping
     * 5. Execute script and capture output
     * 6. Check return code
     * 7. Log execution for audit trail
     * 8. Return results
     * 
     * Command Building:
     * - Scripts are executed with sudo (required for iptables commands)
     * - Arguments are properly escaped to prevent command injection
     * - Full path to script is used (prevents PATH manipulation attacks)
     * 
     * Output Handling:
     * - stdout (standard output) is captured
     * - stderr (standard error) is captured separately
     * - Both are included in the result for debugging
     * 
     * Return Code:
     * - 0 = Success (script executed successfully)
     * - Non-zero = Error (script failed or validation failed)
     * - We check return code to determine success/failure
     * 
     * @param string $script The script name (e.g., 'block_device.sh')
     * @param array $args Array of arguments to pass to the script
     * @return array{
     *     success: bool,
     *     output: string,
     *     error: string,
     *     return_code: int,
     *     command: string
     * } Result array with success status, output, error, return code, and executed command
     * 
     * Usage Example:
     * ```php
     * $executor = new ScriptExecutor();
     * 
     * // Execute block_device.sh with MAC address
     * $result = $executor->execute('block_device.sh', ['AA:BB:CC:DD:EE:FF']);
     * 
     * if ($result['success']) {
     *     // Script executed successfully
     *     Log::info('Device blocked', ['output' => $result['output']]);
     * } else {
     *     // Script failed
     *     Log::error('Blocking failed', [
     *         'error' => $result['error'],
     *         'return_code' => $result['return_code']
     *     ]);
     * }
     * ```
     */
    public function execute(string $script, array $args = []): array
    {
        // Step 1: Check if script is in whitelist (security check)
        // This prevents execution of arbitrary scripts
        // If script is not allowed, we return error immediately
        if (!$this->isScriptAllowed($script)) {
            // Script is not in whitelist - this is a security violation
            // Log the attempt for security auditing
            Log::warning('Attempted to execute non-whitelisted script', [
                'script' => $script,
                'allowed_scripts' => $this->allowedScripts,
            ]);

            // Return error result
            // We don't throw exception - caller can check success flag
            return [
                'success' => false,
                'output' => '',
                'error' => "Script '{$script}' is not in the allowed whitelist",
                'return_code' => 1,
                'command' => '',
            ];
        }

        // Step 2: Validate script path (security check)
        // This ensures script exists, is executable, and is within allowed directory
        // Prevents path traversal attacks (e.g., ../../../etc/passwd)
        if (!$this->validateScriptPath($script)) {
            // Script path validation failed
            // This could mean: script doesn't exist, not executable, or path traversal attempt
            Log::error('Script path validation failed', [
                'script' => $script,
                'scripts_directory' => $this->scriptsDirectory,
            ]);

            // Return error result
            return [
                'success' => false,
                'output' => '',
                'error' => "Script path validation failed for '{$script}'",
                'return_code' => 1,
                'command' => '',
            ];
        }

        // Step 3: Build full path to script
        // We use the validated script name and combine with scripts directory
        // This gives us the absolute path to the script
        // Example: /var/www/parental_wifi/scripts/block_device.sh
        $scriptPath = $this->scriptsDirectory . DIRECTORY_SEPARATOR . $script;

        // Step 4: Sanitize and escape arguments
        // This prevents command injection attacks
        // We escape each argument using escapeshellarg() which:
        // - Wraps arguments in quotes
        // - Escapes special characters
        // - Prevents command injection
        $escapedArgs = array_map(function ($arg) {
            // escapeshellarg() wraps the argument in single quotes and escapes
            // any single quotes within the argument
            // This prevents command injection attacks
            // Example: "AA:BB:CC" becomes "'AA:BB:CC'"
            return escapeshellarg($arg);
        }, $args);

        // Step 5: Build the command to execute
        // Format: sudo /full/path/to/script.sh 'arg1' 'arg2' ...
        // 
        // Why sudo?
        // - Scripts need sudo privileges to modify iptables rules
        // - iptables commands require root/administrator privileges
        // - Sudoers configuration allows www-data to run these scripts without password
        // 
        // Why full path?
        // - Prevents PATH manipulation attacks
        // - Ensures we execute the correct script
        // - More reliable than relying on PATH environment variable
        $command = 'sudo ' . escapeshellarg($scriptPath);
        
        // Add arguments if any were provided
        // Arguments are already escaped, so we can safely concatenate
        if (!empty($escapedArgs)) {
            $command .= ' ' . implode(' ', $escapedArgs);
        }

        // Step 6: Execute the script and capture output
        // We use exec() with output array and return code
        // 
        // exec() parameters:
        // - $command: The command to execute
        // - $output: Array to store output lines (passed by reference)
        // - $returnCode: Variable to store return code (passed by reference)
        // 
        // Why capture output?
        // - Scripts may output useful information (success messages, errors)
        // - We can parse JSON output from some scripts (get_connected_devices.sh)
        // - Helps with debugging when scripts fail
        // 
        // Why capture return code?
        // - Scripts return 0 on success, non-zero on error
        // - We check return code to determine if execution was successful
        // - Different return codes may indicate different error types
        $output = [];
        $returnCode = 0;
        
        // Execute the command
        // exec() returns the last line of output (or false on failure)
        // All output lines are stored in $output array
        // Return code is stored in $returnCode
        $lastLine = exec($command, $output, $returnCode);

        // Step 7: Combine output lines into a single string
        // Scripts may output multiple lines (especially JSON output)
        // We combine them with newlines for easier handling
        $outputString = implode("\n", $output);

        // Step 8: Determine success based on return code
        // Return code 0 = success (standard Unix convention)
        // Non-zero return code = error
        $success = $returnCode === 0;

        // Step 9: Log execution for audit trail
        // This is important for security - we need to know what scripts were executed
        // Logs include: script name, arguments, return code, success status
        // This helps with debugging and security auditing
        if ($success) {
            // Log successful execution (info level - not too verbose)
            Log::info('Script executed successfully', [
                'script' => $script,
                'arguments' => $args,
                'return_code' => $returnCode,
                'output_length' => strlen($outputString),
            ]);
        } else {
            // Log failed execution (error level - important to track)
            // Include output in case it contains error messages
            Log::error('Script execution failed', [
                'script' => $script,
                'arguments' => $args,
                'return_code' => $returnCode,
                'output' => $outputString,
                'command' => $command,
            ]);
        }

        // Step 10: Return result array
        // This provides all information about the execution:
        // - success: Boolean indicating if script succeeded
        // - output: All output from script (stdout)
        // - error: Error message if failed (empty if succeeded)
        // - return_code: Exit code from script
        // - command: The actual command that was executed (for debugging)
        return [
            'success' => $success,
            'output' => $outputString,
            'error' => $success ? '' : "Script '{$script}' returned exit code {$returnCode}",
            'return_code' => $returnCode,
            'command' => $command,
        ];
    }

    /**
     * Check if a script is in the allowed whitelist.
     * 
     * This is a security check that ensures only pre-approved scripts can be
     * executed. This prevents arbitrary command execution attacks.
     * 
     * How It Works:
     * - Checks if the script name exists in the $allowedScripts array
     * - Uses in_array() for case-sensitive matching
     * - Returns true only if script is explicitly whitelisted
     * 
     * Why Whitelist?
     * - Security best practice: "deny by default, allow explicitly"
     * - Prevents execution of malicious or unintended scripts
     * - Makes it clear which scripts are allowed
     * - Easy to audit and maintain
     * 
     * Adding New Scripts:
     * - Add script name to $allowedScripts array in constructor
     * - Ensure script is properly tested and secure
     * - Document why the script is needed
     * 
     * @param string $script The script name to check (e.g., 'block_device.sh')
     * @return bool True if script is allowed, false otherwise
     * 
     * Usage Example:
     * ```php
     * $executor = new ScriptExecutor();
     * 
     * if ($executor->isScriptAllowed('block_device.sh')) {
     *     // Script is allowed, safe to execute
     * } else {
     *     // Script is not allowed, reject execution
     * }
     * ```
     */
    public function isScriptAllowed(string $script): bool
    {
        // Check if script name exists in the allowed scripts whitelist
        // in_array() performs case-sensitive matching
        // This means 'block_device.sh' is different from 'Block_Device.sh'
        // We use exact matching for security (prevents case-based bypass attempts)
        return in_array($script, $this->allowedScripts, true);
    }

    /**
     * Validate that a script path is safe and executable.
     * 
     * This method performs multiple security checks to ensure the script
     * can be safely executed:
     * 
     * 1. **Path Traversal Check**: Prevents directory traversal attacks
     *    - Checks for '../' sequences that could escape the scripts directory
     *    - Example: '../../../etc/passwd' would be rejected
     * 
     * 2. **Directory Validation**: Ensures script is within allowed directory
     *    - Builds full path and checks it starts with scripts directory
     *    - Prevents accessing scripts outside the allowed directory
     * 
     * 3. **File Existence**: Checks if script file actually exists
     *    - Uses realpath() to resolve symlinks and get absolute path
     *    - Returns false if file doesn't exist
     * 
     * 4. **Executability Check**: Verifies script is executable
     *    - Uses is_executable() to check file permissions
     *    - Script must have execute permission to run
     * 
     * 5. **Path Verification**: Double-checks resolved path is still in scripts directory
     *    - realpath() may resolve symlinks, so we verify again
     *    - Prevents symlink attacks that could point outside scripts directory
     * 
     * Security Considerations:
     * 
     * - **Path Traversal**: We check for '../' to prevent escaping the scripts directory
     * - **Symlink Attacks**: We verify the resolved path is still within scripts directory
     * - **File Permissions**: We check that file is executable
     * - **Absolute Paths**: We use realpath() to get absolute path and verify it
     * 
     * @param string $script The script name to validate (e.g., 'block_device.sh')
     * @return bool True if script path is valid and executable, false otherwise
     * 
     * Usage Example:
     * ```php
     * $executor = new ScriptExecutor();
     * 
     * if ($executor->validateScriptPath('block_device.sh')) {
     *     // Script path is valid, safe to execute
     * } else {
     *     // Script path validation failed
     *     // Could mean: doesn't exist, not executable, or path traversal attempt
     * }
     * ```
     */
    public function validateScriptPath(string $script): bool
    {
        // Step 1: Check for path traversal attempts (security)
        // Path traversal attacks try to escape the allowed directory using '../'
        // Example: '../../../etc/passwd' would try to access /etc/passwd
        // We reject any script name containing '../' or '..\\' (Windows)
        if (strpos($script, '../') !== false || strpos($script, '..\\') !== false) {
            // Path traversal attempt detected - this is a security violation
            Log::warning('Path traversal attempt detected in script name', [
                'script' => $script,
            ]);

            return false; // Reject path traversal attempts
        }

        // Step 2: Build full path to script
        // Combine scripts directory with script name
        // Example: /var/www/parental_wifi/scripts/block_device.sh
        $fullPath = $this->scriptsDirectory . DIRECTORY_SEPARATOR . $script;

        // Step 3: Resolve path to absolute path
        // realpath() resolves symlinks and returns absolute path
        // Returns false if file doesn't exist
        // This gives us the actual file path (not a symlink)
        $resolvedPath = realpath($fullPath);

        // Step 4: Check if file exists
        // realpath() returns false if file doesn't exist
        // If file doesn't exist, we can't execute it
        if ($resolvedPath === false) {
            // Script file doesn't exist
            Log::debug('Script file does not exist', [
                'script' => $script,
                'full_path' => $fullPath,
            ]);

            return false; // File doesn't exist
        }

        // Step 5: Verify resolved path is still within scripts directory
        // This prevents symlink attacks where a symlink points outside the scripts directory
        // Example: scripts/malicious.sh -> /etc/passwd (would be rejected)
        // 
        // We check that the resolved path starts with the scripts directory path
        // This ensures the actual file is within the allowed directory
        $scriptsDirRealPath = realpath($this->scriptsDirectory);
        
        // If scripts directory doesn't exist, something is very wrong
        if ($scriptsDirRealPath === false) {
            Log::error('Scripts directory does not exist', [
                'scripts_directory' => $this->scriptsDirectory,
            ]);

            return false; // Scripts directory doesn't exist
        }

        // Check if resolved path starts with scripts directory path
        // strpos() returns 0 if path starts with scripts directory (correct)
        // Returns false if path is outside scripts directory (security violation)
        if (strpos($resolvedPath, $scriptsDirRealPath) !== 0) {
            // Resolved path is outside scripts directory - security violation
            // This could happen if a symlink points outside the directory
            Log::warning('Script path resolved outside scripts directory', [
                'script' => $script,
                'resolved_path' => $resolvedPath,
                'scripts_directory' => $scriptsDirRealPath,
            ]);

            return false; // Reject paths outside scripts directory
        }

        // Step 6: Check if file is executable
        // is_executable() checks if the file has execute permission
        // Scripts must be executable to run
        if (!is_executable($resolvedPath)) {
            // Script file is not executable
            Log::debug('Script file is not executable', [
                'script' => $script,
                'resolved_path' => $resolvedPath,
            ]);

            return false; // File is not executable
        }

        // All validation checks passed
        // Script path is valid, exists, is executable, and is within allowed directory
        return true;
    }

    /**
     * Get the list of allowed scripts.
     * 
     * This method returns the whitelist of scripts that can be executed.
     * Useful for debugging, logging, or displaying allowed scripts.
     * 
     * @return array<string> Array of allowed script names
     */
    public function getAllowedScripts(): array
    {
        return $this->allowedScripts;
    }

    /**
     * Get the scripts directory path.
     * 
     * This method returns the base directory where scripts are located.
     * Useful for debugging or building paths manually.
     * 
     * @return string The scripts directory path
     */
    public function getScriptsDirectory(): string
    {
        return $this->scriptsDirectory;
    }
}

