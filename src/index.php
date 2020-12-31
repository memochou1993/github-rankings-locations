<?php

require 'vendor/autoload.php';

$raw = json_decode(file_get_contents(__DIR__.'/files/raw.json'), true);

$new = collect($raw)->mapWithKeys(function ($country, $index) {
    if ($country['states'] ?? null) {
        $states = collect($country['states'])->pluck('name')->toArray();
        $cities = collect($country['states'])->pluck('cities')->collapse()->pluck('name')->toArray();
        $cities = collect($states)->merge($cities)->unique()->map(function($name) {
            return [
                'name' => $name,
            ];
        })->values()->toArray();

        return [
            $index => [
                'name' => $country['name'],
                'cities' => $cities,
            ],
        ];
    }

    if ($country['cities'] ?? null) {
        $cities = collect($country['cities'])->unique('name')->values()->toArray();

        return [
            $index => [
                'name' => $country['name'],
                'cities' => $cities,
            ],
        ];
    }

    return [
        $index => [
            'name' => $country['name'],
            'cities' => [],
        ],
    ];
});

$duplicates = collect($new)->pluck('cities')->collapse()->duplicates()->pluck('name');

$new = collect($new)->map(function ($country) use ($duplicates) {
    $cities = collect($country['cities'])->map(function ($city) use ($duplicates) {
        $unique = !$duplicates->contains($city['name']);

        return [
            'name' => $city['name'],
            'unique' => $unique,
        ];
    })->toArray();

    return [
        'name' => $country['name'],
        'cities' => $cities,
    ];
})->toArray();

file_put_contents(__DIR__.'/files/new.json', json_encode($new, JSON_PRETTY_PRINT));
