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

                // Check if checkbox for adding text is checked
                if (isset($_POST['addText']) && $_POST['addText'] == 'yes') {
                    createHighlightsWithText($target_file);
                } else {
                    createHighlights($target_file);
                }
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
    $command = "ffmpeg -i \"$filePath\" 2>&1";
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

    // Start creating highlights sequentially
    createNextHighlight($filePath, 0, $duration, 1, false); // false indicates no text
}

// Function to create highlights with text using FFmpeg
function createHighlightsWithText($filePath) {
    // Get video duration
    $command = "ffmpeg -i \"$filePath\" 2>&1";
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

    // Start creating highlights with text sequentially
    createNextHighlight($filePath, 0, $duration, 1, true); // true indicates adding text
}

// Function to create highlights sequentially
function createNextHighlight($filePath, $start, $duration, $part, $addText) {
    if ($start >= $duration) {
        echo "All highlights created successfully.";
        return;
    }

    $highlightPath = 'highlights/highlight_part' . $part . '_' . basename($filePath);
    $command = "ffmpeg -i \"$filePath\" -ss " . gmdate("H:i:s", $start) . " -t 00:01:00 -c copy \"$highlightPath\" 2>&1";
    exec($command, $output, $return_var);

    echo "<pre>";
    echo "Command: $command\n";
    echo "Return var: $return_var\n";
    echo "Output:\n";
    print_r($output);
    echo "</pre>";

    if ($return_var == 0) {
        echo "Highlight created successfully: $highlightPath";
        if ($addText) {
            addTextToHighlight($highlightPath, $part, function () use ($filePath, $start, $duration, $part, $addText) {
                createNextHighlight($filePath, $start + 60, $duration, $part + 1, $addText);
            });
        } else {
            createNextHighlight($filePath, $start + 60, $duration, $part + 1, $addText);
        }
    } else {
        echo "Error creating highlight.";
    }
}

// Function to add text to highlight
function addTextToHighlight($filePath, $part, $callback) {
    $text = "Partea $part";
    $outputPath = str_replace('highlight_part', 'highlight_with_text_part', $filePath);
    
    // Check if the font file exists
    $fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    if (!file_exists($fontFile)) {
        echo "Font file does not exist: $fontFile";
        return;
    }

    $command = "ffmpeg -i \"$filePath\" -vf \"drawtext=text='$text':fontfile=$fontFile:fontcolor=white:fontsize=24:x=10:y=10\" -c:a copy \"$outputPath\" 2>&1";
    exec($command, $output, $return_var);

    echo "<pre>";
    echo "Command: $command\n";
    echo "Return var: $return_var\n";
    echo "Output:\n";
    print_r($output);
    echo "</pre>";

    if ($return_var == 0) {
        echo "Text added successfully: $outputPath";
        // Execute the callback function
        $callback();
    } else {
        echo "Error adding text to highlight.";
    }
}
?>
