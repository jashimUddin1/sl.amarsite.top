<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
</head>
<body>

<script>
    // যদি আগের পেজ থাকে → back
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // fallback যদি history না থাকে
        window.location.href = "/pages/dashboard.php";
    }
</script>

</body>
</html>
