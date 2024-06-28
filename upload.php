<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["video"]["name"]);
    $uploadOk = 1;
    $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if video file is a actual video or fake video
    $check = getimagesize($_FILES["video"]["tmp_name"]);
    if ($check !== false) {
        echo "File is a video - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not a video.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["video"]["size"] > 500000000) { // 500MB
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($videoFileType != "mp4" && $videoFileType != "avi" && $videoFileType != "mov"
    && $videoFileType != "mpeg") {
        echo "Sorry, only MP4, AVI, MOV & MPEG files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
            echo "The file ". htmlspecialchars( basename( $_FILES["video"]["name"])). " has been uploaded.";
            // Call the function to create highlight
            createHighlight($target_file);
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Function to create highlight using FFmpeg
function createHighlight($filePath) {
    $highlightPath = 'highlights/highlight_' . basename($filePath);

    // Example command to extract highlights (first 30 seconds)
    $command = "ffmpeg -i $filePath -ss 00:00:00 -t 00:00:30 -c copy $highlightPath";

    // Execute the command
    exec($command, $output, $return_var);

    if ($return_var == 0) {
        echo "Highlight created successfully: $highlightPath";
    } else {
        echo "Error creating highlight.";
    }
}
?>
