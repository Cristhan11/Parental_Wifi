<?php

/**
 * Test Phase 3 Verification Command
 * 
 * This command verifies all Test Phase 3 requirements for Raspberry Pi deployment:
 * - Storage directory permissions
 * - Symlink functionality
 * - PHP upload limits
 * - Web server status
 * - Storage space availability
 * - File size limit enforcement
 * 
 * Usage:
 * php artisan test:phase3
 * 
 * This is part of TODO #9: Test file system operations and video storage on Raspberry Pi
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class TestPhase3Verification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:phase3 {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Test Phase 3 requirements: storage permissions, symlinks, PHP limits, web server status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Test Phase 3 Verification - Raspberry Pi File System & Storage Tests');
        $this->newLine();
        $this->info('This command verifies critical file system operations required for video storage on Raspberry Pi.');
        $this->newLine();

        $allPassed = true;
        $verbose = $this->option('verbose');

        // Test 1: Storage Directory Permissions
        $this->info('📁 Test 1: Storage Directory Permissions');
        $test1Result = $this->testStoragePermissions($verbose);
        if (!$test1Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: Symlink Functionality
        $this->info('🔗 Test 2: Symlink Functionality');
        $test2Result = $this->testSymlink($verbose);
        if (!$test2Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 3: PHP Upload Limits
        $this->info('⚙️  Test 3: PHP Upload Limits');
        $test3Result = $this->testPhpUploadLimits($verbose);
        if (!$test3Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 4: Storage Space
        $this->info('💾 Test 4: Storage Space Availability');
        $test4Result = $this->testStorageSpace($verbose);
        if (!$test4Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 5: File Size Limit Validation
        $this->info('📏 Test 5: File Size Limit Enforcement');
        $test5Result = $this->testFileSizeLimit($verbose);
        if (!$test5Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 6: Web Server Status (if on Linux)
        if (PHP_OS_FAMILY === 'Linux') {
            $this->info('🌐 Test 6: Web Server Status');
            $test6Result = $this->testWebServerStatus($verbose);
            if (!$test6Result) {
                $allPassed = false;
            }
            $this->newLine();
        }

        // Summary
        $this->newLine();
        if ($allPassed) {
            $this->info('✅ All Test Phase 3 checks passed!');
            $this->info('The system is ready for video storage operations on Raspberry Pi.');
        } else {
            $this->error('❌ Some checks failed. Please review the issues above.');
            $this->warn('Refer to docs/VIDEO_SYSTEM_TESTING.md for troubleshooting steps.');
        }
        $this->newLine();

        return $allPassed ? 0 : 1;
    }

    /**
     * Test 1: Storage Directory Permissions
     */
    private function testStoragePermissions(bool $verbose): bool
    {
        $storagePath = storage_path('app/public/videos');
        $passed = true;

        // Check if directory exists
        if (!is_dir($storagePath)) {
            $this->error("   ❌ Directory does not exist: {$storagePath}");
            $this->line("   💡 Run: mkdir -p {$storagePath}");
            return false;
        }
        $this->line("   ✅ Directory exists: {$storagePath}");

        // Check permissions
        $perms = substr(sprintf('%o', fileperms($storagePath)), -4);
        if ($verbose) {
            $this->line("   📋 Current permissions: {$perms}");
        }

        // Check if writable
        if (!is_writable($storagePath)) {
            $this->error("   ❌ Directory is not writable");
            $this->line("   💡 Run: chmod -R 775 {$storagePath}");
            $this->line("   💡 Run: chown -R www-data:www-data {$storagePath}");
            $passed = false;
        } else {
            $this->line("   ✅ Directory is writable");
        }

        // Test write access
        $testFile = $storagePath . '/.test_write';
        if (@file_put_contents($testFile, 'test') === false) {
            $this->error("   ❌ Cannot write test file");
            $this->line("   💡 Check permissions and ownership");
            $passed = false;
        } else {
            $this->line("   ✅ Write test successful");
            @unlink($testFile);
        }

        return $passed;
    }

    /**
     * Test 2: Symlink Functionality
     */
    private function testSymlink(bool $verbose): bool
    {
        $symlinkPath = public_path('storage');
        $targetPath = storage_path('app/public');
        $passed = true;

        // Check if symlink exists
        if (!is_link($symlinkPath) && !is_dir($symlinkPath)) {
            $this->error("   ❌ Symlink does not exist: {$symlinkPath}");
            $this->line("   💡 Run: php artisan storage:link");
            return false;
        }

        // Check if it's actually a symlink
        if (is_link($symlinkPath)) {
            $this->line("   ✅ Symlink exists: {$symlinkPath}");
            
            // Check target
            $actualTarget = readlink($symlinkPath);
            $expectedTarget = '../storage/app/public';
            
            if ($verbose) {
                $this->line("   📋 Symlink points to: {$actualTarget}");
                $this->line("   📋 Expected: {$expectedTarget}");
            }

            // Verify target exists
            $resolvedPath = realpath($symlinkPath);
            if ($resolvedPath && is_dir($resolvedPath)) {
                $this->line("   ✅ Symlink target is valid and accessible");
            } else {
                $this->error("   ❌ Symlink target is invalid or inaccessible");
                $passed = false;
            }
        } else {
            $this->warn("   ⚠️  Path exists but is not a symlink (may be a directory)");
            $this->line("   💡 Remove directory and run: php artisan storage:link");
            $passed = false;
        }

        // Test file access via symlink
        $testFile = 'videos/.symlink_test';
        $testContent = 'symlink test';
        
        if (Storage::disk('public')->put($testFile, $testContent)) {
            $this->line("   ✅ Can write files via Storage facade");
            
            // Check if file is accessible via symlink
            $publicPath = public_path('storage/' . $testFile);
            if (file_exists($publicPath)) {
                $this->line("   ✅ File accessible via public/storage symlink");
            } else {
                $this->error("   ❌ File not accessible via symlink");
                $passed = false;
            }
            
            Storage::disk('public')->delete($testFile);
        } else {
            $this->error("   ❌ Cannot write files via Storage facade");
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 3: PHP Upload Limits
     */
    private function testPhpUploadLimits(bool $verbose): bool
    {
        $passed = true;
        
        // Get PHP configuration
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');

        if ($verbose) {
            $this->line("   📋 PHP Configuration:");
            $this->line("      upload_max_filesize: {$uploadMaxFilesize}");
            $this->line("      post_max_size: {$postMaxSize}");
            $this->line("      max_execution_time: {$maxExecutionTime}");
            $this->line("      memory_limit: {$memoryLimit}");
        }

        // Convert to bytes for comparison
        $uploadMaxBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxBytes = $this->convertToBytes($postMaxSize);
        $requiredBytes = 512 * 1024 * 1024; // 512MB

        // Check upload_max_filesize
        if ($uploadMaxBytes >= $requiredBytes) {
            $this->line("   ✅ upload_max_filesize is sufficient (>= 512M)");
        } else {
            $this->error("   ❌ upload_max_filesize is too small: {$uploadMaxFilesize}");
            $this->line("   💡 Update php.ini: upload_max_filesize = 512M");
            $this->line("   💡 Restart PHP-FPM: sudo systemctl restart php8.2-fpm");
            $passed = false;
        }

        // Check post_max_size
        if ($postMaxBytes >= $requiredBytes) {
            $this->line("   ✅ post_max_size is sufficient (>= 512M)");
        } else {
            $this->error("   ❌ post_max_size is too small: {$postMaxSize}");
            $this->line("   💡 Update php.ini: post_max_size = 512M");
            $this->line("   💡 Restart PHP-FPM: sudo systemctl restart php8.2-fpm");
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 4: Storage Space
     */
    private function testStorageSpace(bool $verbose): bool
    {
        $passed = true;
        $requiredSpace = 100 * 1024 * 1024; // 100MB

        // Get disk free space
        $storagePath = storage_path('app/public/videos');
        $freeSpace = disk_free_space($storagePath);

        if ($freeSpace === false) {
            $this->error("   ❌ Cannot determine available storage space");
            return false;
        }

        $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);
        $requiredSpaceMB = round($requiredSpace / 1024 / 1024, 2);

        if ($verbose) {
            $totalSpace = disk_total_space($storagePath);
            $totalSpaceMB = round($totalSpace / 1024 / 1024, 2);
            $usedSpaceMB = round(($totalSpace - $freeSpace) / 1024 / 1024, 2);
            $this->line("   📋 Storage Information:");
            $this->line("      Total space: {$totalSpaceMB} MB");
            $this->line("      Used space: {$usedSpaceMB} MB");
            $this->line("      Free space: {$freeSpaceMB} MB");
        }

        if ($freeSpace >= $requiredSpace) {
            $this->line("   ✅ Sufficient storage space available ({$freeSpaceMB} MB free)");
        } else {
            $this->error("   ❌ Insufficient storage space: {$freeSpaceMB} MB free (need {$requiredSpaceMB} MB)");
            $this->line("   💡 Free up space or expand storage");
            $passed = false;
        }

        return $passed;
    }

    /**
     * Test 5: File Size Limit Enforcement
     */
    private function testFileSizeLimit(bool $verbose): bool
    {
        $passed = true;

        // Check validation rule in StoreVideoRequest
        $requestPath = app_path('Http/Requests/StoreVideoRequest.php');
        if (!file_exists($requestPath)) {
            $this->error("   ❌ StoreVideoRequest.php not found");
            return false;
        }

        $content = file_get_contents($requestPath);
        
        // Check for max:512000 rule (512MB in KB)
        if (strpos($content, 'max:512000') !== false || strpos($content, 'max:524288') !== false) {
            $this->line("   ✅ File size validation rule exists in StoreVideoRequest");
            if ($verbose) {
                $this->line("      Validation rule: max:512000 (512MB in KB)");
            }
        } else {
            $this->error("   ❌ File size validation rule not found");
            $this->line("   💡 Add 'max:512000' rule to video_file validation in StoreVideoRequest");
            $passed = false;
        }

        // Check for error message
        if (strpos($content, '512MB') !== false || strpos($content, '512 MB') !== false) {
            $this->line("   ✅ Error message for file size limit exists");
        } else {
            $this->warn("   ⚠️  Error message for 512MB limit not found (optional but recommended)");
        }

        return $passed;
    }

    /**
     * Test 6: Web Server Status (Linux only)
     */
    private function testWebServerStatus(bool $verbose): bool
    {
        $passed = true;

        // Check for Nginx
        $nginxStatus = shell_exec('systemctl is-active nginx 2>/dev/null');
        if (trim($nginxStatus) === 'active') {
            $this->line("   ✅ Nginx is running");
            if ($verbose) {
                $nginxVersion = shell_exec('nginx -v 2>&1');
                $this->line("      {$nginxVersion}");
            }
        } else {
            // Check for Apache
            $apacheStatus = shell_exec('systemctl is-active apache2 2>/dev/null');
            if (trim($apacheStatus) === 'active') {
                $this->line("   ✅ Apache is running");
                if ($verbose) {
                    $apacheVersion = shell_exec('apache2 -v 2>&1 | head -1');
                    $this->line("      {$apacheVersion}");
                }
            } else {
                $this->warn("   ⚠️  No web server detected (Nginx/Apache not running)");
                $this->line("   💡 Start web server: sudo systemctl start nginx");
                $passed = false;
            }
        }

        // Check PHP-FPM
        $phpFpmStatus = shell_exec('systemctl is-active php8.2-fpm 2>/dev/null');
        if (trim($phpFpmStatus) === 'active') {
            $this->line("   ✅ PHP-FPM is running");
        } else {
            // Try other PHP versions
            $phpVersions = ['php8.1-fpm', 'php8.0-fpm', 'php-fpm'];
            $found = false;
            foreach ($phpVersions as $version) {
                $status = shell_exec("systemctl is-active {$version} 2>/dev/null");
                if (trim($status) === 'active') {
                    $this->line("   ✅ {$version} is running");
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->warn("   ⚠️  PHP-FPM not detected");
                $this->line("   💡 Start PHP-FPM: sudo systemctl start php8.2-fpm");
                $passed = false;
            }
        }

        return $passed;
    }

    /**
     * Convert PHP ini size string to bytes
     */
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}

