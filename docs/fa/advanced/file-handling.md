# کار با فایل‌ها (File Handling)

کار با فایل‌ها یکی از بخش‌های مهم در توسعه وب اپلیکیشن‌ها است. در این بخش، با روش‌های مختلف مدیریت فایل‌ها در
فلسک‌پی‌اچ‌پی آشنا می‌شوید.

## آپلود فایل

آپلود فایل یکی از عملیات رایج در وب اپلیکیشن‌ها است. فلسک‌پی‌اچ‌پی روش‌های ساده و امنی برای مدیریت فایل‌های آپلود شده
ارائه می‌دهد.

### دریافت فایل‌های آپلود شده در درخواست

کلاس `Request` در فلسک‌پی‌اچ‌پی امکان دسترسی به فایل‌های آپلود شده را فراهم می‌کند:

```php
// مسیر آپلود فایل
$app->post('/upload', function(Request $request, Response $response) {
    // دریافت فایل‌های آپلود شده
    $files = $request->getUploadedFiles();
    
    // اگر فایلی آپلود نشده باشد
    if (empty($files['file'])) {
        return $response->withStatus(400)->json([
            'error' => 'هیچ فایلی آپلود نشده است'
        ]);
    }
    
    $uploadedFile = $files['file'];
    
    // بررسی خطاهای آپلود
    if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
        return $response->withStatus(400)->json([
            'error' => 'خطا در آپلود فایل: ' . $uploadedFile->getError()
        ]);
    }
    
    $filename = $uploadedFile->getClientFilename();
    $fileSize = $uploadedFile->getSize();
    $fileType = $uploadedFile->getClientMediaType();
    
    // ذخیره فایل در سرور
    $uploadDir = __DIR__ . '/uploads/';
    $newFilename = uniqid() . '_' . $filename;
    
    // اطمینان از وجود دایرکتوری آپلود
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFile->moveTo($uploadDir . $newFilename);
    
    return $response->json([
        'success' => true,
        'message' => 'فایل با موفقیت آپلود شد',
        'file' => [
            'name' => $filename,
            'size' => $fileSize,
            'type' => $fileType,
            'path' => '/uploads/' . $newFilename
        ]
    ]);
});
```