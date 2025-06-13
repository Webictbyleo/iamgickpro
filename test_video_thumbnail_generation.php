<?php

declare(strict_types=1);

require_once __DIR__ . '/backend/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Service\MediaProcessing\Processor\FfmpegProcessor;
use Psr\Log\NullLogger;

// Load environment variables
$dotenv = new Dotenv();
if (file_exists(__DIR__ . '/backend/.env')) {
    $dotenv->load(__DIR__ . '/backend/.env');
}

// Test video thumbnail generation
echo "🎬 Testing Video Thumbnail Generation (Direct FFmpeg Test)\n";
echo "=======================================================\n\n";

try {
    // Create FFmpeg processor directly
    $ffmpegPath = '/usr/bin/ffmpeg';
    $ffprobePath = '/usr/bin/ffprobe';
    
    // Check if FFmpeg is available
    if (!file_exists($ffmpegPath)) {
        echo "❌ FFmpeg not found at {$ffmpegPath}\n";
        echo "   Trying to locate FFmpeg...\n";
        $ffmpegPath = trim(shell_exec('which ffmpeg') ?: '');
        if (!$ffmpegPath) {
            echo "❌ FFmpeg not found in PATH. Please install FFmpeg.\n";
            exit(1);
        }
        echo "✅ Found FFmpeg at: {$ffmpegPath}\n";
    }
    
    if (!file_exists($ffprobePath)) {
        echo "❌ FFprobe not found at {$ffprobePath}\n";
        echo "   Trying to locate FFprobe...\n";
        $ffprobePath = trim(shell_exec('which ffprobe') ?: '');
        if (!$ffprobePath) {
            echo "❌ FFprobe not found in PATH. Please install FFmpeg.\n";
            exit(1);
        }
        echo "✅ Found FFprobe at: {$ffprobePath}\n";
    }

    $logger = new NullLogger();
    $ffmpegProcessor = new FfmpegProcessor($ffmpegPath, $ffprobePath, $logger);

    // Check for existing video files in uploads directory
    $mediaDirectory = __DIR__ . '/backend/public/uploads/media';
    $videoExtensions = ['mp4', 'avi', 'mov', 'mkv', 'webm', 'flv', 'm4v'];
    
    $videoFiles = [];
    if (is_dir($mediaDirectory)) {
        $files = scandir($mediaDirectory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $videoExtensions)) {
                $videoFiles[] = $file;
            }
        }
    }

    if (empty($videoFiles)) {
        echo "⚠️  No video files found in {$mediaDirectory}\n";
        echo "   Let's create a test video file using FFmpeg...\n\n";
        
        // Create a simple test video (3 seconds, colored rectangles)
        $testVideoPath = $mediaDirectory . '/test_video.mp4';
        $createVideoCommand = [
            escapeshellarg($ffmpegPath),
            '-f', 'lavfi',
            '-i', 'testsrc2=duration=5:size=640x480:rate=30',
            '-c:v', 'libx264',
            '-preset', 'ultrafast',
            '-t', '5',
            '-y',
            escapeshellarg($testVideoPath)
        ];
        
        echo "🎬 Creating test video...\n";
        echo "Command: " . implode(' ', $createVideoCommand) . "\n\n";
        
        $createResult = shell_exec(implode(' ', $createVideoCommand) . ' 2>&1');
        
        if (file_exists($testVideoPath)) {
            echo "✅ Test video created: {$testVideoPath}\n";
            echo "   File size: " . formatBytes(filesize($testVideoPath)) . "\n\n";
            $videoFiles[] = basename($testVideoPath);
        } else {
            echo "❌ Failed to create test video.\n";
            echo "Output: {$createResult}\n";
            exit(1);
        }
    }

    echo "📹 Found " . count($videoFiles) . " video file(s):\n";
    foreach ($videoFiles as $file) {
        echo "   - {$file}\n";
    }
    echo "\n";

    // Test with the first video file
    $testVideoFile = $videoFiles[0];
    $testVideoPath = $mediaDirectory . '/' . $testVideoFile;
    
    echo "🧪 Testing with: {$testVideoFile}\n";
    echo "   File size: " . formatBytes(filesize($testVideoPath)) . "\n";
    echo "   Full path: {$testVideoPath}\n\n";

    // Create output directory for thumbnails
    $thumbnailDirectory = __DIR__ . '/backend/public/uploads/thumbnails';
    if (!is_dir($thumbnailDirectory)) {
        mkdir($thumbnailDirectory, 0755, true);
        echo "📁 Created thumbnail directory: {$thumbnailDirectory}\n";
    }

    $thumbnailPath = $thumbnailDirectory . '/test_video_thumb.gif';
    
    echo "🔄 Generating GIF thumbnail...\n";
    echo "   Output: {$thumbnailPath}\n\n";
    
    // Test the new generateVideoGif method
    $startTime = microtime(true);
    $result = $ffmpegProcessor->generateVideoGif(
        $testVideoPath,
        $thumbnailPath,
        startTime: 1.0,
        duration: 3.0,
        width: 300,
        height: 300,
        fps: 10
    );
    $processingTime = microtime(true) - $startTime;
    
    echo "⏱️  Processing time: " . round($processingTime, 2) . " seconds\n\n";

    if ($result->isSuccess()) {
        echo "✅ GIF thumbnail generated successfully!\n";
        echo "   📁 Path: {$thumbnailPath}\n";
        echo "   📏 Size: " . formatBytes(filesize($thumbnailPath)) . "\n";
        
        // Check if it's a valid GIF file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $thumbnailPath);
        finfo_close($finfo);
        
        echo "   🎭 MIME type: {$mimeType}\n";
        
        if ($mimeType === 'image/gif') {
            echo "   ✅ Valid GIF file confirmed\n";
        } else {
            echo "   ⚠️  Expected GIF but got: {$mimeType}\n";
        }
        
        // Display metadata
        $metadata = $result->getMetadata();
        if ($metadata) {
            echo "   📊 Metadata:\n";
            foreach ($metadata as $key => $value) {
                echo "      {$key}: {$value}\n";
            }
        }
        
    } else {
        echo "❌ GIF thumbnail generation failed!\n";
        echo "   Error: " . $result->getErrorMessage() . "\n";
    }

    echo "\n📊 Summary:\n";
    echo "   Video file: {$testVideoFile}\n";
    echo "   Thumbnail path: {$thumbnailPath}\n";
    echo "   Success: " . ($result->isSuccess() ? 'Yes' : 'No') . "\n";
    echo "   Processing time: " . round($processingTime, 2) . "s\n";

} catch (\Exception $e) {
    echo "❌ Error during testing: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

function formatBytes(int $bytes, int $precision = 2): string 
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "\n🎉 Test completed!\n";
