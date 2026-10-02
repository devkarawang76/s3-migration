<?php

namespace App\Models\Traits;

trait ReadOnly
{
    public static function bootReadOnly(): void
    {
        static::saving(function ($model) {
            throw new \RuntimeException('This model is read-only in Phase 1.');
        });
    }

    public function save(array $options = [])
    {
        throw new \RuntimeException('This model is read-only in Phase 1.');
    }

    public function delete()
    {
        throw new \RuntimeException('This model is read-only in Phase 1.');
    }
}
