<?php

namespace App\Actions;

use App\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateBrandingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?UploadedFile $logoFile, bool $removeLogo): void
    {
        AppSetting::setValue('app_name', $data['app_name']);
        AppSetting::setValue('primary_color', $data['primary_color']);
        AppSetting::setValue('footer_text', $data['footer_text'] ?? '');

        if ($removeLogo) {
            $this->removeExistingBrandingAssets();
        } elseif ($logoFile) {
            $this->removeExistingBrandingAssets();
            $this->processLogoUpload($logoFile, $data['primary_color']);
        }
    }

    private function removeExistingBrandingAssets(): void
    {
        foreach (['app_logo', 'app_favicon', 'app_og_image'] as $key) {
            $oldFile = AppSetting::getValue($key);
            if ($oldFile && Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
            AppSetting::setValue($key, null);
        }
    }

    private function processLogoUpload(UploadedFile $file, string $primaryColor): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $unique = uniqid();

        if ($extension === 'svg') {
            $path = $file->storeAs('branding', 'logo_'.$unique.'.svg', 'public');
            AppSetting::setValue('app_logo', $path);
            AppSetting::setValue('app_favicon', $path);
            AppSetting::setValue('app_og_image', $path);

            return;
        }

        $imageContent = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($imageContent);

        if ($image === false) {
            $path = $file->storeAs('branding', 'logo_'.$unique.'.'.$extension, 'public');
            AppSetting::setValue('app_logo', $path);
            AppSetting::setValue('app_favicon', $path);
            AppSetting::setValue('app_og_image', $path);

            return;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $this->generateLogo($image, $origWidth, $origHeight, $unique);
        $this->generateFavicon($image, $origWidth, $origHeight, $unique);
        $this->generateOgImage($image, $origWidth, $origHeight, $unique, $primaryColor);

        imagedestroy($image);
    }

    /**
     * @param  \GdImage  $source
     */
    private function generateLogo($source, int $origWidth, int $origHeight, string $unique): void
    {
        $logoWidth = $origWidth;
        $logoHeight = $origHeight;

        if ($logoWidth > 512 || $logoHeight > 512) {
            if ($logoWidth > $logoHeight) {
                $logoHeight = (int) floor($logoHeight * (512 / $logoWidth));
                $logoWidth = 512;
            } else {
                $logoWidth = (int) floor($logoWidth * (512 / $logoHeight));
                $logoHeight = 512;
            }
        }

        $logoImage = imagecreatetruecolor($logoWidth, $logoHeight);
        imagealphablending($logoImage, false);
        imagesavealpha($logoImage, true);
        $transparent = imagecolorallocatealpha($logoImage, 255, 255, 255, 127);
        imagefilledrectangle($logoImage, 0, 0, $logoWidth, $logoHeight, $transparent);
        imagecopyresampled($logoImage, $source, 0, 0, 0, 0, $logoWidth, $logoHeight, $origWidth, $origHeight);

        $logoPath = 'branding/logo_'.$unique.'.webp';
        $fullLogoPath = Storage::disk('public')->path($logoPath);
        if (! is_dir(dirname($fullLogoPath))) {
            mkdir(dirname($fullLogoPath), 0755, true);
        }
        imagewebp($logoImage, $fullLogoPath, 85);
        imagedestroy($logoImage);
        AppSetting::setValue('app_logo', $logoPath);
    }

    /**
     * @param  \GdImage  $source
     */
    private function generateFavicon($source, int $origWidth, int $origHeight, string $unique): void
    {
        $favWidth = 96;
        $favHeight = 96;
        $favImage = imagecreatetruecolor($favWidth, $favHeight);
        imagealphablending($favImage, false);
        imagesavealpha($favImage, true);
        $favTransparent = imagecolorallocatealpha($favImage, 255, 255, 255, 127);
        imagefilledrectangle($favImage, 0, 0, $favWidth, $favHeight, $favTransparent);

        $ratio = min($favWidth / $origWidth, $favHeight / $origHeight);
        $newW = $origWidth * $ratio;
        $newH = $origHeight * $ratio;
        $x = ($favWidth - $newW) / 2;
        $y = ($favHeight - $newH) / 2;

        imagecopyresampled($favImage, $source, (int) $x, (int) $y, 0, 0, (int) $newW, (int) $newH, $origWidth, $origHeight);
        $favPath = 'branding/favicon_'.$unique.'.webp';
        imagewebp($favImage, Storage::disk('public')->path($favPath), 85);
        imagedestroy($favImage);
        AppSetting::setValue('app_favicon', $favPath);
    }

    /**
     * @param  \GdImage  $source
     */
    private function generateOgImage($source, int $origWidth, int $origHeight, string $unique, string $primaryColor): void
    {
        $ogWidth = 1200;
        $ogHeight = 630;
        $ogImage = imagecreatetruecolor($ogWidth, $ogHeight);

        $hex = ltrim($primaryColor, '#');
        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $bgColor = imagecolorallocate($ogImage, $r, $g, $b);
        imagefilledrectangle($ogImage, 0, 0, $ogWidth, $ogHeight, $bgColor);

        $maxOgLogoW = 600;
        $maxOgLogoH = 400;
        $ogRatio = min($maxOgLogoW / $origWidth, $maxOgLogoH / $origHeight);
        $ogLogoW = $origWidth * $ogRatio;
        $ogLogoH = $origHeight * $ogRatio;
        $ogX = ($ogWidth - $ogLogoW) / 2;
        $ogY = ($ogHeight - $ogLogoH) / 2;

        imagecopyresampled($ogImage, $source, (int) $ogX, (int) $ogY, 0, 0, (int) $ogLogoW, (int) $ogLogoH, $origWidth, $origHeight);
        $ogPath = 'branding/og_'.$unique.'.webp';
        imagewebp($ogImage, Storage::disk('public')->path($ogPath), 85);
        imagedestroy($ogImage);
        AppSetting::setValue('app_og_image', $ogPath);
    }
}
