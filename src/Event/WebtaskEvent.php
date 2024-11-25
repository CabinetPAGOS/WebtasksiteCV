<?php 
// src/Event/WebtaskEvent.php
namespace App\Event;

use App\Entity\Webtask;
use Symfony\Contracts\EventDispatcher\Event;

class WebtaskEvent extends Event
{
    public const NAME = 'webtask.modified';

    private $webtask;

    public function __construct(Webtask $webtask)
    {
        $this->webtask = $webtask;
    }

    public function getWebtask(): Webtask
    {
        return $this->webtask;
    }
}
