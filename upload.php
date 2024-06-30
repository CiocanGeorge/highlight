<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo '<pre>';
    var_dump($_FILES);
    echo '</pre>';

    if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["video"]["name"]);
        $uploadOk = 1;
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check file size
        if ($_FILES["video"]["size"] > 500000000) { // 500MB
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if ($videoFileType != "mp4" && $videoFileType != "avi" && $videoFileType != "mov" && $videoFileType != "mpeg") {
            echo "Sorry, only MP4, AVI, MOV & MPEG files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
                echo "The file ". htmlspecialchars(basename($_FILES["video"]["name"])). " has been uploaded.";
                createHighlights($target_file);
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        echo "No file uploaded or file upload error.";
    }
}

// Function to create highlights using FFmpeg
function createHighlights($filePath) {
    // Get video duration
    $command = "ffmpeg -i $filePath 2>&1";
    exec($command, $output, $return_var);
    $duration = 0;
    foreach ($output as $line) {
        if (preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $line, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (float)$matches[3];
            $duration = $hours * 3600 + $minutes * 60 + $seconds;
            break;
        }
    }

    // Create 1-minute clips
    $start = 0;
    $part = 1;
    while ($start < $duration) {
        $highlightPath = 'highlights/highlight_part' . $part . '_' . basename($filePath);
        $command = "ffmpeg -i $filePath -ss " . gmdate("H:i:s", $start) . " -t 00:01:00 -c copy $highlightPath 2>&1";
        exec($command, $output, $return_var);

        echo "<pre>";
        echo "Command: $command\n";
        echo "Return var: $return_var\n";
        echo "Output:\n";
        print_r($output);
        echo "</pre>";

        if ($return_var == 0) {
            echo "Highlight created successfully: $highlightPath";
        } else {
            echo "Error creating highlight.";
        }

        $start += 60; // move to the next minute
        $part++;
    }
}
?>
