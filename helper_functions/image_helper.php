<?php
// image_helper.php

/**
 * Big size picute compress + resize করে uploads/schools ফোল্ডারে JPEG হিসেবে save করবে
 * success হলে [relativePath, null]
 * error হলে   [null, errorMessage]
 */
function compress_school_image(array $file, int $maxWidth = 1200, int $quality = 70): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, "ছবি আপলোড করতে সমস্যা হয়েছে (error code: {$file['error']})."];
    }

    // valid image কিনা check
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [null, "ফাইলটি valid image না।"];
    }

    $mime = $info['mime'] ?? '';
    $width = $info[0];
    $height = $info[1];

    // source image create
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return [null, "শুধু JPG/PNG/WEBP ছবি সাপোর্টেড।"];
    }

    if (!$src) {
        return [null, "ছবি লোড করতে সমস্যা হয়েছে।"];
    }

    // resize ratio হিসাব
    $ratio = 1.0;
    if ($width > $maxWidth) {
        $ratio = $maxWidth / $width;
    }
    $newWidth = (int) round($width * $ratio);
    $newHeight = (int) round($height * $ratio);

    $dst = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // uploads/schools ফোল্ডার ensure
    $uploadDir = dirname(__DIR__) . '/uploads/schools';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = 'school_' . time() . '_' . mt_rand(1000, 9999) . '.jpg';
    $target = $uploadDir . '/' . $filename;

    // JPEG হিসেবে কমপ্রেস করে save
    $ok = imagejpeg($dst, $target, $quality);

    imagedestroy($src);
    imagedestroy($dst);

    if (!$ok) {
        return [null, "কমপ্রেস করা ছবি save করতে সমস্যা হয়েছে।"];
    }

    // DB তে রাখবো relative path
    $relativePath = 'uploads/schools/' . $filename;
    return [$relativePath, null];
}
