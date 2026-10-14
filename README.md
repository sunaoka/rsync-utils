# Rsync Utilities for PHP

[![Latest](https://poser.pugx.org/sunaoka/rsync-utils/v)](https://packagist.org/packages/sunaoka/rsync-utils)
[![License](https://poser.pugx.org/sunaoka/rsync-utils/license)](https://packagist.org/packages/sunaoka/rsync-utils)
[![PHP](https://img.shields.io/packagist/php-v/sunaoka/rsync-utils)](https://packagist.org/packages/sunaoka/rsync-utils)
[![Test](https://github.com/sunaoka/rsync-utils/actions/workflows/test.yml/badge.svg)](https://github.com/sunaoka/rsync-utils/actions/workflows/test.yml)

A PHP library for parsing the output of rsync's `--itemize-changes` option.

Identify created, updated, and deleted files, and inspect changes to file size, modification time, permissions, and other attributes.

## Requirements

Requires PHP 8.0 or later.

## Installation

Install the library using Composer.

```bash
composer require sunaoka/rsync-utils
```

## Basic Usage

Run rsync with the `--itemize-changes` option.

```bash
rsync --archive --itemize-changes source/ destination/
```

Pass the output to `ItemizeChangesParser::parse()`. The method returns an array of `RsyncFileObject` instances.

```php
use Sunaoka\RsyncUtils\ItemizeChangesParser;

$output = <<<'OUTPUT'
>f+++++++++ new.txt
>f.st...... updated.txt
*deleting   old.txt
OUTPUT;

$parser = new ItemizeChangesParser();
$files = $parser->parse($output);

foreach ($files as $file) {
    echo $file->getType() . ': ' . $file->getPath() . PHP_EOL;
}
```

```text
created: new.txt
updated: updated.txt
deleted: old.txt
```

The library parses rsync output. Your application is responsible for running rsync and capturing its output.

## Checking Change Types

Use the following methods to identify created, updated, and deleted files.

```php
foreach ($files as $file) {
    if ($file->isCreated()) {
        echo 'Created: ', $file->getPath(), PHP_EOL;
    } elseif ($file->isUpdated()) {
        echo 'Updated: ', $file->getPath(), PHP_EOL;
    } elseif ($file->isDeleted()) {
        echo 'Deleted: ', $file->getPath(), PHP_EOL;
    }
}
```

`getUpdateType()` returns `sent`, `received`, `changed`, `hardlink`, `unchanged`, or `deleting`.

`getFileType()` returns `file`, `directory`, `symlink`, `device`, or `special`. The original file type cannot be determined from a deletion entry.

## Inspecting Changed Attributes

Use `toArray()` to retrieve the parsed result as an array.

```php
$attributes = $files[1]->toArray();

echo $attributes['path'];          // updated.txt
echo $attributes['type'];          // updated
echo $attributes['updateType'];    // received
echo $attributes['fileType'];      // file

var_dump($attributes['size']);      // bool(true)
var_dump($attributes['timestamp']); // bool(true)
```

The following keys indicate changed attributes. Each value is `true` when the corresponding change indicator appears in the output.

| Key           | Description                             |
| ------------- | --------------------------------------- |
| `checksum`    | Indicates a checksum change             |
| `size`        | Indicates a file size change            |
| `timestamp`   | Indicates a modification time change    |
| `permissions` | Indicates a permissions change          |
| `owner`       | Indicates an owner change               |
| `group`       | Indicates a group change                |
| `acl`         | Indicates an ACL change                 |
| `xattr`       | Indicates an extended attributes change |

The `+++++++++` marker for a newly created entry does not set the individual attribute change flags. Use `isCreated()` to identify newly created entries.

## JSON Serialization

`RsyncFileObject` implements `JsonSerializable`, so you can pass it directly to `json_encode()`.

```php
echo json_encode($files[1], JSON_PRETTY_PRINT);
```

```json
{
    "path": "updated.txt",
    "type": "updated",
    "updateType": "received",
    "fileType": "file",
    "checksum": false,
    "size": true,
    "timestamp": true,
    "permissions": false,
    "owner": false,
    "group": false,
    "acl": false,
    "xattr": false,
    "message": null
}
```

## Input Handling

- Supports LF, CRLF, and CR line endings and ignores empty lines.
- Preserves spaces in paths.
- Removes the ` -> target` suffix from symbolic link paths and the ` => target` suffix from hard link paths.
- Excludes entries marked as skipped from the array returned by `parse()`.

Provide lines containing an itemized change code followed by a path. The parser is not intended to process arbitrary rsync logs containing progress output or transfer summaries.

## Testing

Install the development dependencies and run the test suite.

```bash
composer install
vendor/bin/phpunit
```

## License

Released under the MIT license.
