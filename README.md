# Magento 2 WebP Image Converter

A Magento 2 module that adds full WebP support across all upload points and provides both a CLI command and a scheduled cron job to bulk-convert existing JPG/PNG images to WebP format.

---

## Features

- **Native WebP upload support** — allows WebP files in every Magento 2 upload area: product images, category images, CMS content, logo/favicon, Media Gallery, PageBuilder, and the generic file uploader.
- **Bulk conversion via CLI** — convert entire directories on demand with a single command.
- **Scheduled cron conversion** — automatically convert images in the background on a configurable schedule.
- **Two converter drivers** — GD (built-in PHP extension, recommended) or Imagick.
- **Configurable quality** — set WebP quality from 0 to 100 (default: 80).
- **Optional original deletion** — delete source JPG/PNG files after a successful conversion.
- **Recursive directory scan** — optionally scan all subdirectories.
- **Database reference update** — after conversion, automatically updates image references in `cms_block`, `cms_page`, and `catalog_category_entity_varchar`.
- **Dry-run mode** — preview which files would be converted without touching anything.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.1` |
| Magento Framework | `*` |
| `magento/module-catalog` | `*` |
| `magento/module-cms` | `*` |
| `magento/module-media-storage` | `*` |
| `magento/module-cron` | `*` |
| PHP extension `ext-gd` | Required by the **GD** driver |
| PHP extension `ext-imagick` | Required by the **Imagick** driver |

At least one of `ext-gd` or `ext-imagick` must be available on the server.

---

## Installation

### Via Composer

Add the repository to your project's `composer.json` before requiring the package:

```json
"repositories": {
    "spdivn-webp": {
        "type": "git",
        "url": "https://github.com/spdivn/magento2-webp.git"
    }
}
```

Then install the module:

```bash
composer require spdivn/module-webp
bin/magento module:enable Spdivn_WebP
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual

1. Copy the module to `app/code/Spdivn/WebP`.
2. Run:

```bash
bin/magento module:enable Spdivn_WebP
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Configuration

Navigate to **Stores → Configuration → Spdivn → WebP / Image Converter**.

### General

| Field | Description | Default |
|---|---|---|
| **Enable Cron Conversion** | Enables the scheduled background conversion job. | No |
| **Cron Schedule** | Standard cron expression for the conversion schedule. | `0 2 * * *` (daily at 02:00) |

### Conversion Settings

| Field | Description | Default |
|---|---|---|
| **Images Directory Path** | Absolute or Magento-root-relative path to scan (e.g. `pub/media/wysiwyg`). | `pub/media/wysiwyg` |
| **Converter Driver** | `gd` or `imagick`. | `gd` |
| **WebP Quality** | Integer 0–100 (lower = smaller file, higher = better quality). | `80` |
| **Keep Original Files** | When disabled, source JPG/PNG files are deleted after successful conversion. | No (delete originals) |
| **Scan Subdirectories Recursively** | When enabled, all nested subdirectories are included in the scan. | Yes |

---

## CLI Command

```
bin/magento spdivn:images:convert-to-webp <path> [options]
```

### Arguments

| Argument | Description |
|---|---|
| `path` | Directory to convert (absolute or relative to Magento root). **Required.** |

### Options

| Option | Description | Default |
|---|---|---|
| `--driver=<gd\|imagick>` | Converter driver to use. | `gd` |
| `--quality=<0-100>` | WebP output quality. | `80` |
| `--keep-originals` | Keep original files after conversion. | (originals deleted) |
| `--recursive` | Scan subdirectories recursively. | (top-level only) |
| `--dry-run` | List files that would be converted without modifying anything. | (disabled) |

### Examples

```bash
# Convert all images in pub/media/catalog/product using GD at quality 80
bin/magento spdivn:images:convert-to-webp pub/media/catalog/product

# Use Imagick driver at quality 85, scan recursively
bin/magento spdivn:images:convert-to-webp pub/media/catalog --driver=imagick --quality=85 --recursive

# Dry run: preview what would be converted
bin/magento spdivn:images:convert-to-webp pub/media/catalog --dry-run

# Convert and keep original files
bin/magento spdivn:images:convert-to-webp pub/media/wysiwyg --recursive --keep-originals
```

---

## Cron Job

When **Enable Cron Conversion** is set to **Yes**, the job `spdivn_webp_convert_to_webp` runs automatically inside the `spdivn_webp` cron group, using the schedule and conversion settings configured in the admin panel.

Conversion results and any errors are written to `var/log/spdivn_webp_converter.log`.

To run the cron group manually:

```bash
bin/magento cron:run --group spdivn_webp
```

---

## How It Works

### WebP Upload Support

A set of plugins intercepts Magento's file upload validation layer and adds `webp` to the allowed MIME types and extensions. This covers:

- `Magento\Framework\File\Uploader` / `Magento\MediaStorage\Model\File\Uploader` — extension whitelist
- `Magento\Theme\Model\Design\Backend\Image` / `Favicon` / `Logo` — design config backends
- `Magento\MediaGalleryUi` image uploader
- `Magento\MediaStorage` image validator
- Storage resize handler
- PageBuilder image uploader (via RequireJS mixin)

### Conversion Pipeline

1. The service (`ConvertToWebpService`) scans the target directory for `.jpg`, `.jpeg`, and `.png` files.
2. Each file is converted to `.webp` by the selected driver (GD or Imagick).
3. If **Keep Original Files** is disabled, the source file is deleted.
4. After all conversions, `DbReferenceUpdater` updates filename references in:
   - `cms_block.content`
   - `cms_page.content`
   - `catalog_category_entity_varchar.value`

### Drivers

| Driver | PHP Extension | Notes |
|---|---|---|
| `gd` | `ext-gd` | Built-in on most PHP installations. Supports JPEG and PNG (with alpha). |
| `imagick` | `ext-imagick` | Requires the ImageMagick PHP extension. Broader format support. |

---

## Module Structure

```
Console/Command/ConvertToWebpCommand.php   CLI command
Cron/ConvertToWebp.php                     Scheduled cron job
Logger/WebpConverterLogger.php             Dedicated PSR logger (var/log/spdivn_webp_converter.log)
Model/
  Adapter/Gd2.php                          GD2 adapter override
  Config/ImageConverter.php                Admin config reader
  Config/Source/ConverterDriver.php        Driver dropdown source model
  Converter/GdConverter.php                GD-based WebP converter
  Converter/ImagickConverter.php           Imagick-based WebP converter
  DbReferenceUpdater.php                   DB filename reference updater
  Service/ConvertToWebpService.php         Core conversion service
Plugin/
  Design/Backend/AllowWebpPlugin.php       Allows WebP in design backends
  FileUploader/AllowWebpExtensionPlugin.php Allows WebP extension in uploader
  FileUploader/CheckMimeTypePlugin.php     Allows WebP MIME type
  MediaGalleryUi/ImageUploaderPlugin.php   Allows WebP in Media Gallery
  MediaStorage/ImageValidatorPlugin.php    Allows WebP in media storage
  Storage/ResizeFilePlugin.php             Allows WebP in resize handler
view/adminhtml/
  web/js/form/element/file-uploader-mixin.js  PageBuilder uploader mixin
  web/css/pagebuilder-image-uploader.css      PageBuilder uploader styles
```

---

## License

[MIT](LICENSE) — © 2026 Ivan Spada
