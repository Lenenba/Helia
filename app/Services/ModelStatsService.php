<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class ModelStatsService
{
    /**
     * Calcule des statistiques génériques pour une source de données.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Support\Collection  $source
     * @param  string  $filtre La colonne principale pour les statistiques.
     * @return array
     */
    public static function compute(Builder|Collection $source, string $filtre): array
    {
        $items = $source instanceof Builder ? $source->get() : $source;

        if (! $items instanceof Collection) {
            throw new InvalidArgumentException('La source doit être une Collection ou un Eloquent Builder.');
        }

        $totalStat = [
            'title'      => 'Total',
            'value'      => $items->count(),
            'change'     => '',
            'changeText' => '',
            'icon'       => 'File',
        ];

        $columnsToAnalyze = array_unique([$filtre, 'status']);

        $columnStats = self::generateStatsForColumns($items, $columnsToAnalyze);

        return array_merge([$totalStat], $columnStats);
    }

    /**
     * Génère des statistiques groupées pour une liste de colonnes.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  array  $columns
     * @return array
     */
    private static function generateStatsForColumns(Collection $items, array $columns): array
    {
        return collect($columns)
            ->flatMap(function (string $column) use ($items) {

                if ($items->isEmpty() || !isset($items->first()->{$column})) {
                    return [];
                }

                return $items->groupBy($column)
                    ->map->count()
                    ->map(function (int $count, string $statusValue) {
                        return [
                            'title'      => ucfirst($statusValue),
                            'value'      => $count,
                            'change'     => '',
                            'changeText' => 'au total',
                            'icon'       => 'FileText',
                        ];
                    });
            })
            ->values()
            ->all();
    }
}
