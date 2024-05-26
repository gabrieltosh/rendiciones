<?php

namespace App\Notifications\Accountability;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateAccountabilityNotification extends Notification
{
    use Queueable;

    public $accountability;
    /**
     * Create a new notification instance.
     */
    public function __construct($accountability)
    {
        $this->accountability = $accountability;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/panel/accountability/authorization/'.$this->accountability->id.'/detail');
        return (new MailMessage)
                    ->subject('Rendición de Fondos a Autorizar')
                    ->greeting('Rendición de Fondos a Autorizar')
                    ->line('Una nueva solicitud de fondos ha sido enviada y está a la espera de autorización.')
                    ->action('Ver Rendición', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
