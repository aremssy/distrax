<?php

/*
|--------------------------------------------------------------------------
| Property Amenities
|--------------------------------------------------------------------------
|
| The amenity checklist an owner or agent picks from when listing a property.
| Selected keys are stored on the listing's `utility_flags` JSON column and
| rendered on the public listing page. Keys are what get persisted, so renaming
| one orphans it on existing listings — add new keys rather than editing old ones.
|
*/

return [
    'lift' => ['label' => 'Lift / Elevator', 'icon' => 'chevrons-up'],
    'security_staff' => ['label' => 'Security Staff', 'icon' => 'shield'],
    'cctv' => ['label' => 'CCTV Security', 'icon' => 'cctv'],
    'parking_space' => ['label' => 'Parking Space', 'icon' => 'car'],
    'generator_backup' => ['label' => 'Generator Backup', 'icon' => 'zap'],
    'electricity_backup' => ['label' => 'Electricity Backup', 'icon' => 'plug'],
    'water_supply' => ['label' => 'Water Supply', 'icon' => 'droplets'],
    'fire_safety' => ['label' => 'Fire Safety', 'icon' => 'flame'],
    'gas_line' => ['label' => 'Gas Line', 'icon' => 'flame-kindling'],
    'internet' => ['label' => 'Internet / Wi-Fi', 'icon' => 'wifi'],
    'air_conditioning' => ['label' => 'Air Conditioning', 'icon' => 'air-vent'],
    'balcony' => ['label' => 'Balcony', 'icon' => 'fence'],
    'rooftop_access' => ['label' => 'Rooftop Access', 'icon' => 'building'],
    'gym' => ['label' => 'Gym', 'icon' => 'dumbbell'],
    'swimming_pool' => ['label' => 'Swimming Pool', 'icon' => 'waves'],
    'prayer_room' => ['label' => 'Prayer Room', 'icon' => 'moon-star'],
];
