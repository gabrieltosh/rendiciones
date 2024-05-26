<?php

namespace App\Notifications\Accountability;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusAccountabilityNotification extends Notification
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
        $url = url('/panel/accountability/'.$this->accountability->profile_id.'/manage/'.$this->accountability->id.'/detail');
        return (new MailMessage)
                    ->subject('Rendición de Fondos - Estado: '.$this->accountability->status)
                    ->greeting('Rendición de Fondos - Estado: '.$this->accountability->status)
                    ->line('El estado de la rendición de fondos fue actualizada a: '.$this->accountability->status)
                    ->lineIf($this->accountability->status=='Anulado'||$this->accountability->status=='Rechazado','Motivo: '.$this->accountability->comments)
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
