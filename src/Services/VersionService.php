<?php

// src/Services/VersionService.php
namespace App\Services; // Remarquez le "s" à la fin

use App\Repository\VersionRepository;

class VersionService
{
    private $versionRepository;

    public function __construct(VersionRepository $versionRepository)
    {
        $this->versionRepository = $versionRepository;
    }

    public function getLibelleById(string $id): ?string
    {
        $version = $this->versionRepository->find($id);
        return $version ? $version->getLibelle() : null;
    }
}
