<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', array_keys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', array_keys($model->getChanges()));
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', array_keys($model->getOriginal()));
    }

    private function record(Model $model, string $event, array $fields): void
    {
        $fields = array_values(array_diff($fields, [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
        ]));

        AuditLog::record($event, $model, ['changed_fields' => $fields]);
    }
}
