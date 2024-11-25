<?php

// src/Entity/Settings.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SettingsRepository")
 * @ORM\Table(name="settings")
 */
#[ORM\Entity(repositoryClass: "App\Repository\SettingsRepository")]
#[ORM\Table(name: "settings")]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $maintenanceMode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaintenanceMode(): ?bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(bool $maintenanceMode): self
    {
        $this->maintenanceMode = $maintenanceMode;

        return $this;
    }
}
