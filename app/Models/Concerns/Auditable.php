<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat setiap create/update/delete ke tabel audit_logs.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => static::writeAudit($model, 'created', null, $model->getAttributes()));

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if ($changes === []) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $changes);
            static::writeAudit($model, 'updated', $old, $changes);
        });

        static::deleted(fn (Model $model) => static::writeAudit($model, 'deleted', $model->getOriginal(), null));
    }

    protected static function writeAudit(Model $model, string $action, ?array $old, ?array $new): void
    {
        $hidden = array_flip($model->getHidden());

        AuditLog::record(
            action: $action,
            auditable: $model,
            old: $old === null ? null : array_diff_key($old, $hidden),
            new: $new === null ? null : array_diff_key($new, $hidden),
        );
    }
}
