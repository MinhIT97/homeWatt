<?php

$sourceFile = __DIR__.'/../public/favicon.png';
$targetDir = __DIR__.'/../public/icons';

if (! file_exists($sourceFile)) {
    echo "Lỗi: Không tìm thấy file favicon.png tại: $sourceFile\n";
    exit(1);
}

if (! file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "Đã tạo thư mục: $targetDir\n";
}

$sizes = [192, 512];

// Thử load ảnh gốc
$sourceData = file_get_contents($sourceFile);
$sourceImage = @imagecreatefromstring($sourceData);
if (! $sourceImage) {
    echo "Lỗi: Không thể đọc ảnh gốc favicon.png. Hãy chắc chắn đó là ảnh định dạng hợp lệ (PNG, JPEG, v.v.).\n";
    exit(1);
}

// Lấy thông số ảnh gốc
[$sourceWidth, $sourceHeight] = getimagesize($sourceFile);

foreach ($sizes as $size) {
    $targetFile = "$targetDir/icon-$size.png";

    // Tạo ảnh trống với kênh alpha (trong suốt)
    $targetImage = imagecreatetruecolor($size, $size);
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);

    // Tạo màu trong suốt và đổ vào ảnh mới
    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
    imagefill($targetImage, 0, 0, $transparent);

    // Thực hiện resize chất lượng cao
    imagecopyresampled(
        $targetImage,
        $sourceImage,
        0, 0, 0, 0,
        $size, $size,
        $sourceWidth, $sourceHeight
    );

    // Lưu ảnh
    if (imagepng($targetImage, $targetFile, 9)) {
        echo "Đã tạo thành công icon PWA: $targetFile ({$size}x{$size})\n";
    } else {
        echo "Lỗi: Không thể lưu icon PWA: $targetFile\n";
    }

    imagedestroy($targetImage);
}

imagedestroy($sourceImage);
echo "Hoàn tất việc sinh PWA Icons!\n";
