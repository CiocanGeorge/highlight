<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $video_url = $_POST['youtube_link'];
    $output_path = 'videos/original_video.mp4';
    $highlight_path = 'videos/highlight_video.mp4';

    // Descarcă videoclipul folosind youtube-dl
    exec("youtube-dl -o $output_path $video_url");

    // Detectarea highlights
    $highlights = detect_highlights($output_path);

    // Crearea videoclipului cu highlights
    create_highlight_video($output_path, $highlights, $highlight_path);

    echo "Highlights generated: <a href='$highlight_path'>Download Here</a>";
}

function detect_highlights($video_path) {
    $frames_dir = 'videos/frames/';
    if (!is_dir($frames_dir)) {
        mkdir($frames_dir, 0777, true);
    }

    // Extrage cadrele folosind FFmpeg
    exec("ffmpeg -i $video_path -vf 'select=gt(scene,0.4)' -vsync vfr $frames_dir%04d.png");

    // Analizează cadrele pentru a detecta highlights (simplificat)
    $frames = glob("$frames_dir*.png");
    $highlights = [];
    foreach ($frames as $frame) {
        preg_match('/(\d+)\.png$/', $frame, $matches);
        $frame_number = intval($matches[1]);
        $time_in_seconds = $frame_number / 30; // Presupunând 30 FPS
        $highlights[] = $time_in_seconds;
    }

    return $highlights;
}

function create_highlight_video($video_path, $highlights, $output_path) {
    $segment_paths = [];
    foreach ($highlights as $key => $highlight) {
        $start = max(0, $highlight - 2); // 2 secunde înainte de highlight
        $end = $highlight + 2; // 2 secunde după highlight
        $segment_path = "videos/segment_$key.mp4";
        
        // Extrage segmentul folosind FFmpeg
        exec("ffmpeg -i $video_path -ss $start -to $end -c copy $segment_path");
        $segment_paths[] = $segment_path;
    }

    // Concatenează segmentele
    $concat_file = "videos/concat_list.txt";
    $file_list = "";
    foreach ($segment_paths as $segment_path) {
        $file_list .= "file '$segment_path'\n";
    }
    file_put_contents($concat_file, $file_list);

    // Creează videoclipul final cu highlights
    exec("ffmpeg -f concat -safe 0 -i $concat_file -c copy $output_path");
}
?>
