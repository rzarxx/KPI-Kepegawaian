<?php

namespace App\Notifications;

use App\Models\EmployeeIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmployeeIncidentResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(private EmployeeIncident $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'employee_incident_resolved', 'incident_id' => $this->incident->id, 'employee_id' => $this->incident->employee_id, 'message' => 'Catatan masalah karyawan telah diselesaikan.'];
    }
}
