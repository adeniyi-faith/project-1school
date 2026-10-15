<?php

namespace App\Support;

use Illuminate\Filesystem\LocalFilesystemAdapter as LaravelLocalDisk;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

/**
 * Laravel's file storage asks PHP's "fileinfo" add-on to name a file's type the moment any disk is opened.
 * Some servers (a cPanel host with fileinfo switched off) do not have it, so every upload then fails with
 * `Class "finfo" not found`. When that add-on is missing, the local disks are built here instead, naming
 * the type from the file's ending (.png, .jpg ...). Nothing changes for servers that do have fileinfo.
 */
class SafeLocalDisk
{
    /** Switch this on only when the server has no fileinfo (or when a test asks for it) */
    public static function register(bool $force = false): void
    {
        if (! $force && class_exists(\finfo::class)) {
            return;
        }

        Storage::extend('local', fn ($app, array $config) => SafeLocalDisk::make($config));
    }

    public static function make(array $config, string $name = 'local'): LaravelLocalDisk
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );

        $adapter = new LocalFilesystemAdapter(
            $config['root'],
            $visibility,
            $config['lock'] ?? LOCK_EX,
            ($config['links'] ?? null) === 'skip' ? LocalFilesystemAdapter::SKIP_LINKS : LocalFilesystemAdapter::DISALLOW_LINKS,
            new ExtensionMimeTypeDetector(),
        );

        $flysystem = new Filesystem($adapter, Arr::only($config, [
            'directory_visibility', 'disable_asserts', 'retain_visibility', 'temporary_url', 'url', 'visibility',
        ]));

        return (new LaravelLocalDisk($flysystem, $adapter, $config))
            ->diskName($name)
            ->shouldServeSignedUrls($config['serve'] ?? false, fn () => app('url'));
    }
}
