<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$stmt = $pdo->prepare('SELECT image_blob, image_mime, image_path FROM products WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (!empty($row['image_blob'])) {
    $mime = $row['image_mime'] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($row['image_blob']));
    echo $row['image_blob'];
    exit;
}

if (!empty($row['image_path']) && file_exists(__DIR__ . '/' . $row['image_path'])) {
    $full = __DIR__ . '/' . $row['image_path'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $full) ?: 'application/octet-stream';
    finfo_close($finfo);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($full));
    readfile($full);
    exit;
}

header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-family="Arial,Helvetica,sans-serif">Bez obrázku</text></svg>';
exit;

?>
