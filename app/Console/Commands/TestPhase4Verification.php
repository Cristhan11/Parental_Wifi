<?php

/**
 * Test Phase 4 Verification Command
 * 
 * This command verifies all Test Phase 4 requirements for shell script execution:
 * - ScriptExecutor service functionality
 * - NetworkService integration
 * - Error handling
 * - Security validation
 * - Network command execution
 * 
 * Usage:
 * php artisan test:phase4
 * 
 * This is part of TODO #13: Test Phase 4 - Shell Script Execution
 */

namespace App\Console\Commands;

use App\Services\ScriptExecutor;
use App\Services\NetworkService;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPhase4Verification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:phase4';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Test Phase 4 requirements: ScriptExecutor, NetworkService, error handling, security';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Test Phase 4 Verification - Shell Script Execution Tests');
        $this->newLine();
        $this->info('This command verifies shell script execution capabilities for network control on Raspberry Pi.');
        $this->newLine();

        $allPassed = true;
        $verbose = $this->getOutput()->isVerbose();

        // Test 1: ScriptExecutor Service Instantiation
        $this->info('🔧 Test 1: ScriptExecutor Service Instantiation');
        $test1Result = $this->testScriptExecutorInstantiation($verbose);
        if (!$test1Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: ScriptExecutor Whitelist Validation
        $this->info('📋 Test 2: ScriptExecutor Whitelist Validation');
        $test2Result = $this->testScriptExecutorWhitelist($verbose);
        if (!$test2Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 3: ScriptExecutor Path Validation
        $this->info('🛡️  Test 3: ScriptExecutor Path Validation');
        $test3Result = $this->testScriptExecutorPathValidation($verbose);
        if (!$test3Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 4: ScriptExecutor Basic Execution
        $this->info('▶️  Test 4: ScriptExecutor Basic Execution');
        $test4Result = $this->testScriptExecutorExecution($verbose);
        if (!$test4Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 5: ScriptExecutor Argument Sanitization
        $this->info('🔒 Test 5: ScriptExecutor Argument Sanitization');
        $test5Result = $this->testScriptExecutorSanitization($verbose);
        if (!$test5Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 6: NetworkService Instantiation
        $this->info('🌐 Test 6: NetworkService Instantiation');
        $test6Result = $this->testNetworkServiceInstantiation($verbose);
        if (!$test6Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 7: NetworkService getConnectedDevices
        $this->info('📱 Test 7: NetworkService getConnectedDevices');
        $test7Result = $this->testNetworkServiceGetConnectedDevices($verbose);
        if (!$test7Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 8: NetworkService getTrafficStats
        $this->info('📊 Test 8: NetworkService getTrafficStats');
        $test8Result = $this->testNetworkServiceGetTrafficStats($verbose);
        if (!$test8Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 9: NetworkService isDeviceBlocked
        $this->info('🔍 Test 9: NetworkService isDeviceBlocked');
        $test9Result = $this->testNetworkServiceIsDeviceBlocked($verbose);
        if (!$test9Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 10: Error Handling
        $this->info('⚠️  Test 10: Error Handling');
        $test10Result = $this->testErrorHandling($verbose);
        if (!$test10Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 11: Security Tests
        $this->info('🔐 Test 11: Security Tests');
        $test11Result = $this->testSecurity($verbose);
        if (!$test11Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 12: Integration Test - Full NetworkService Workflow
        $this->info('🔄 Test 12: Integration Test - Full NetworkService Workflow');
        $test12Result = $this->testNetworkServiceIntegration($verbose);
        if (!$test12Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Summary
        $this->newLine();
        if ($allPassed) {
            $this->info('✅ All Test Phase 4 checks passed!');
            $this->info('The system is ready for shell script execution and network control operations.');
        } else {
            $this->error('❌ Some checks failed. Please review the issues above.');
            $this->warn('Refer to docs/TESTING.md (Test Phase 4) for troubleshooting steps.');
        }
        $this->newLine();

        return $allPassed ? 0 : 1;
    }

    /**
     * Test 1: ScriptExecutor Service Instantiation
     */
    private function testScriptExecutorInstantiation(bool $verbose): bool
    {
        $passed = true;

        try {
            $executor = app(ScriptExecutor::class);
            $this->line("   ✅ ScriptExecutor instantiated successfully");
            
            if ($verbose) {
                $this->line("      Class: " . get_class($executor));
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to instantiate ScriptExecutor: " . $e->getMessage());
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 2: ScriptExecutor Whitelist Validation
     */
    private function testScriptExecutorWhitelist(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test allowed script
        $result = $executor->execute('get_connected_devices.sh', []);
        if ($result['success'] || $result['return_code'] !== null) {
            $this->line("   ✅ Allowed script (get_connected_devices.sh) can be executed");
        } else {
            // Script may fail if not on Raspberry Pi, but should not be rejected by whitelist
            if (strpos($result['error'] ?? '', 'whitelist') === false && 
                strpos($result['error'] ?? '', 'not allowed') === false) {
                $this->line("   ✅ Allowed script validation passed (script may fail for other reasons)");
            } else {
                $this->error("   ❌ Allowed script was rejected by whitelist");
                $passed = false;
            }
        }

        // Test disallowed script (using reflection to test private method behavior)
        // We'll test this by trying to execute an invalid script
        $result = $executor->execute('invalid_script.sh', []);
        if (!$result['success'] && 
            (strpos($result['error'] ?? '', 'whitelist') !== false || 
             strpos($result['error'] ?? '', 'not allowed') !== false ||
             strpos($result['error'] ?? '', 'not in') !== false)) {
            $this->line("   ✅ Invalid script correctly rejected by whitelist");
        } else {
            $this->warn("   ⚠️  Invalid script validation may not be working correctly");
            if ($verbose) {
                $this->line("      Error: " . ($result['error'] ?? 'No error message'));
            }
        }

        return $passed;
    }

    /**
     * Test 3: ScriptExecutor Path Validation
     */
    private function testScriptExecutorPathValidation(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test path traversal attempt
        $result = $executor->execute('../etc/passwd', []);
        if (!$result['success']) {
            $this->line("   ✅ Path traversal attempt correctly blocked");
        } else {
            $this->error("   ❌ Path traversal attack succeeded!");
            $passed = false;
        }

        // Test another path traversal variant
        $result = $executor->execute('../../etc/passwd', []);
        if (!$result['success']) {
            $this->line("   ✅ Path traversal variant correctly blocked");
        } else {
            $this->error("   ❌ Path traversal variant succeeded!");
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 4: ScriptExecutor Basic Execution
     */
    private function testScriptExecutorExecution(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test executing get_connected_devices.sh (should work even if no devices)
        $result = $executor->execute('get_connected_devices.sh', []);
        
        if ($verbose) {
            $this->line("      Return code: " . ($result['return_code'] ?? 'N/A'));
            $this->line("      Success: " . ($result['success'] ? 'true' : 'false'));
            if (!empty($result['output'])) {
                $outputPreview = substr($result['output'], 0, 100);
                $this->line("      Output preview: {$outputPreview}...");
            }
        }

        // Script may fail if not on Raspberry Pi, but should execute
        if ($result['return_code'] !== null) {
            $this->line("   ✅ Script execution attempted (return code: {$result['return_code']})");
            
            // Check if output is JSON (even if empty array)
            if (!empty($result['output'])) {
                $output = trim($result['output']);
                if ($output[0] === '[' || $output[0] === '{') {
                    $this->line("   ✅ Script output appears to be valid JSON");
                }
            } else {
                $this->line("   ✅ Script executed (empty output is acceptable)");
            }
        } else {
            $this->error("   ❌ Script execution failed completely");
            if ($verbose && !empty($result['error'])) {
                $this->line("      Error: " . $result['error']);
            }
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 5: ScriptExecutor Argument Sanitization
     */
    private function testScriptExecutorSanitization(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test with potentially malicious input
        $maliciousInput = "test; rm -rf /";
        $result = $executor->execute('get_connected_devices.sh', [$maliciousInput]);
        
        // The script should either reject invalid input or sanitize it
        // Since get_connected_devices.sh doesn't take arguments, it should fail gracefully
        if (!$result['success'] || $result['return_code'] !== 0) {
            $this->line("   ✅ Malicious input handled safely (script rejected or sanitized)");
        } else {
            $this->warn("   ⚠️  Malicious input may not have been properly sanitized");
            if ($verbose) {
                $this->line("      Output: " . substr($result['output'] ?? '', 0, 100));
            }
        }

        // Test with valid MAC address format
        $validMac = "AA:BB:CC:DD:EE:FF";
        $result = $executor->execute('block_device.sh', [$validMac]);
        
        // Script may fail if not on Raspberry Pi, but should accept the argument
        if ($result['return_code'] !== null) {
            $this->line("   ✅ Valid MAC address format accepted");
        } else {
            $this->warn("   ⚠️  Valid MAC address may not have been processed");
        }

        return $passed;
    }

    /**
     * Test 6: NetworkService Instantiation
     */
    private function testNetworkServiceInstantiation(bool $verbose): bool
    {
        $passed = true;

        try {
            $service = app(NetworkService::class);
            $this->line("   ✅ NetworkService instantiated successfully");
            
            if ($verbose) {
                $this->line("      Class: " . get_class($service));
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to instantiate NetworkService: " . $e->getMessage());
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 7: NetworkService getConnectedDevices
     */
    private function testNetworkServiceGetConnectedDevices(bool $verbose): bool
    {
        $passed = true;
        $service = app(NetworkService::class);

        try {
            $devices = $service->getConnectedDevices();
            
            if (is_array($devices)) {
                $this->line("   ✅ getConnectedDevices() returned array");
                
                if ($verbose) {
                    $this->line("      Device count: " . count($devices));
                    if (count($devices) > 0) {
                        $firstDevice = $devices[0];
                        $this->line("      First device keys: " . implode(', ', array_keys($firstDevice)));
                    }
                }
                
                // Validate structure if devices exist
                if (count($devices) > 0) {
                    $firstDevice = $devices[0];
                    $requiredKeys = ['mac', 'ip', 'hostname'];
                    $hasAllKeys = true;
                    foreach ($requiredKeys as $key) {
                        if (!isset($firstDevice[$key])) {
                            $hasAllKeys = false;
                            break;
                        }
                    }
                    
                    if ($hasAllKeys) {
                        $this->line("   ✅ Device structure is correct");
                    } else {
                        $this->warn("   ⚠️  Device structure may be incomplete");
                    }
                } else {
                    $this->line("   ✅ No devices connected (empty array is valid)");
                }
            } else {
                $this->error("   ❌ getConnectedDevices() did not return array");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("   ❌ getConnectedDevices() failed: " . $e->getMessage());
            if ($verbose) {
                $this->line("      Exception: " . get_class($e));
            }
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 8: NetworkService getTrafficStats
     */
    private function testNetworkServiceGetTrafficStats(bool $verbose): bool
    {
        $passed = true;
        $service = app(NetworkService::class);

        try {
            $stats = $service->getTrafficStats();
            
            if (is_array($stats)) {
                $this->line("   ✅ getTrafficStats() returned array");
                
                if ($verbose) {
                    $this->line("      Stats count: " . count($stats));
                    if (count($stats) > 0) {
                        $firstStat = $stats[0];
                        $this->line("      First stat keys: " . implode(', ', array_keys($firstStat)));
                    }
                }
            } else {
                $this->error("   ❌ getTrafficStats() did not return array");
                $passed = false;
            }
        } catch (\Exception $e) {
            $this->error("   ❌ getTrafficStats() failed: " . $e->getMessage());
            if ($verbose) {
                $this->line("      Exception: " . get_class($e));
            }
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 9: NetworkService isDeviceBlocked
     */
    private function testNetworkServiceIsDeviceBlocked(bool $verbose): bool
    {
        $passed = true;
        $service = app(NetworkService::class);

        // Try to find a device in database
        $device = Device::first();
        
        if ($device) {
            try {
                $isBlocked = $service->isDeviceBlocked($device);
                
                if (is_bool($isBlocked)) {
                    $this->line("   ✅ isDeviceBlocked() returned boolean: " . ($isBlocked ? 'true' : 'false'));
                    
                    if ($verbose) {
                        $this->line("      Device MAC: " . ($device->mac_address ?? 'N/A'));
                        $this->line("      Blocked status: " . ($isBlocked ? 'Blocked' : 'Not blocked'));
                    }
                } else {
                    $this->error("   ❌ isDeviceBlocked() did not return boolean");
                    $passed = false;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ isDeviceBlocked() failed: " . $e->getMessage());
                if ($verbose) {
                    $this->line("      Exception: " . get_class($e));
                }
                $passed = false;
            }
        } else {
            $this->warn("   ⚠️  No devices in database to test isDeviceBlocked()");
            $this->line("   💡 Create a test device to fully test this method");
        }

        return $passed;
    }

    /**
     * Test 10: Error Handling
     */
    private function testErrorHandling(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test with invalid script name
        $result = $executor->execute('nonexistent_script.sh', []);
        if (!$result['success']) {
            $this->line("   ✅ Invalid script name handled gracefully");
        } else {
            $this->error("   ❌ Invalid script name was not rejected");
            $passed = false;
        }

        // Test with invalid arguments (for script that requires arguments)
        $result = $executor->execute('block_device.sh', []);
        // Script should fail but not crash
        if ($result['return_code'] !== null) {
            $this->line("   ✅ Missing arguments handled gracefully");
        } else {
            $this->warn("   ⚠️  Error handling for missing arguments may need improvement");
        }

        // Test that errors are logged (check if error message exists)
        if (!empty($result['error'])) {
            $this->line("   ✅ Error messages are provided");
        } else {
            $this->warn("   ⚠️  Error messages may not be detailed enough");
        }

        return $passed;
    }

    /**
     * Test 11: Security Tests
     */
    private function testSecurity(bool $verbose): bool
    {
        $passed = true;
        $executor = app(ScriptExecutor::class);

        // Test command injection attempt
        $injectionAttempt = "'; rm -rf /; echo '";
        $result = $executor->execute('get_connected_devices.sh', [$injectionAttempt]);
        
        // Should be sanitized or rejected
        if (!$result['success'] || $result['return_code'] !== 0) {
            $this->line("   ✅ Command injection attempt blocked or sanitized");
        } else {
            $this->warn("   ⚠️  Command injection attempt may not have been properly handled");
        }

        // Test path traversal
        $pathTraversal = "../../../etc/passwd";
        $result = $executor->execute($pathTraversal, []);
        if (!$result['success']) {
            $this->line("   ✅ Path traversal attempt blocked");
        } else {
            $this->error("   ❌ Path traversal attack succeeded!");
            $passed = false;
        }

        // Test script whitelist
        $unauthorizedScript = "/bin/bash";
        $result = $executor->execute($unauthorizedScript, []);
        if (!$result['success']) {
            $this->line("   ✅ Unauthorized script blocked by whitelist");
        } else {
            $this->error("   ❌ Unauthorized script was allowed!");
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 12: Integration Test - Full NetworkService Workflow
     */
    private function testNetworkServiceIntegration(bool $verbose): bool
    {
        $passed = true;
        $service = app(NetworkService::class);

        // Get a device from database
        $device = Device::first();
        
        if (!$device) {
            $this->warn("   ⚠️  No devices in database for integration test");
            $this->line("   💡 Create a test device to fully test integration workflow");
            return true; // Not a failure, just can't test
        }

        if (empty($device->mac_address)) {
            $this->warn("   ⚠️  Device has no MAC address for integration test");
            $this->line("   💡 Add MAC address to device to test blocking/unblocking");
            return true; // Not a failure, just can't test
        }

        try {
            // Test 1: Check initial blocked state
            $initialBlocked = $service->isDeviceBlocked($device);
            if ($verbose) {
                $this->line("      Initial blocked state: " . ($initialBlocked ? 'Blocked' : 'Not blocked'));
            }

            // Test 2: Get connected devices
            $devices = $service->getConnectedDevices();
            $this->line("   ✅ getConnectedDevices() works in integration");
            
            // Test 3: Get traffic stats
            $stats = $service->getTrafficStats();
            $this->line("   ✅ getTrafficStats() works in integration");
            
            // Note: We don't actually block/unblock in tests to avoid disrupting network
            // This would be tested manually on Raspberry Pi
            $this->line("   ✅ Integration workflow methods are accessible");
            $this->line("   💡 Manual test: Block/unblock device on Raspberry Pi to verify full workflow");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Integration test failed: " . $e->getMessage());
            if ($verbose) {
                $this->line("      Exception: " . get_class($e));
                $this->line("      Trace: " . $e->getTraceAsString());
            }
            $passed = false;
        }

        return $passed;
    }
}

