<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class RowLock
{
    /**
     * Re-fetch the model with an exclusive lock held until the surrounding transaction ends.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel|null
     */
    public static function lock(Model $model): ?Model
    {
        $query = $model->newQuery()->whereKey($model->getKey());

        // SQLite ignores FOR UPDATE, so take its database write lock with a no-op write instead.
        if ($model->getConnection()->getDriverName() === 'sqlite') {
            $keyName = $model->getKeyName();

            (clone $query)->toBase()->update([$keyName => $model->getConnection()->raw($keyName)]);
        }

        return $query->lockForUpdate()->first();
    }
}
