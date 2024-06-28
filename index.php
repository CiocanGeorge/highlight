<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Video</title>
</head>
<body>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="video">Select video to upload:</label>
        <input type="file" name="video" id="video" accept="video/*">
        <input type="submit" value="Upload Video" name="submit">
    </form>
</body>
</html>
