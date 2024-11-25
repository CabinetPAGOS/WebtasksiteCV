<?php
// src/Services/NotificationService.php
namespace App\Services;

use App\Entity\User;
use App\Entity\Webtask;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendNotification(User $user, Webtask $webtask)
    {
        $email = (new Email())
            ->from('cabinet-conseil@pagos.fr')
            ->to($user->getEmail())
            ->subject('Modification de la Webtask')
            ->text('Une Webtask a été modifiée : ' . $webtask->getTitle());

        $this->mailer->send($email);
    }
}
