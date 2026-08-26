<?php

declare(strict_types=1);

namespace Omega\Gettext\Scanner;

use Omega\Gettext\Translations;

interface ScannerInterface
{
    /**
     * Sets the domain used when a scanned function does not specify one.
     *
     * @param string $domain Domain receiving entries without an explicit one.
     */
    public function setDefaultDomain(string $domain): void;

    /**
     * @return string The current default domain name.
     */
    public function getDefaultDomain(): string;

    /**
     * @return Translations[]
      * @return array<string, Translations> Registered catalogs indexed by domain.
     */
    public function getTranslations(): array;

    /**
     * Reads a file and scans its content.
     *
     * @param string $filename Path of the file to read and scan.
     * @throws \Exception If the file cannot be read.
     */
    public function scanFile(string $filename): void;

    /**
     * Scans raw content, adding entries to the registered catalogs.
     *
     * @param string $string Content to scan.
     * @param string $filename Name reported in the references of found entries.
     */
    public function scanString(string $string, string $filename): void;
}
