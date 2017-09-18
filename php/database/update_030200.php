<?php

/**
 * Update to version 3.2.0
 */

use Lychee\Modules\Database;
use Lychee\Modules\Response;

$version = 'update_030200';

$query  = Database::prepare($connection, "SELECT `key` FROM `?` WHERE `key` = 'googleMapsApiKey' LIMIT 1", array(LYCHEE_TABLE_SETTINGS));
$result = Database::execute($connection, $query, $version, __LINE__);

if ($result===false) Response::error('Could not get api key from database!');

if ($result->num_rows===0) {
    $query = Database::prepare($connection,
        "INSERT INTO `?` (`key`, `value`) VALUES ('googleMapsApiKey', '')",
        array(LYCHEE_TABLE_SETTINGS));
    $result = Database::execute($connection, $query, $version, __LINE__);
}

$fieldsToAdd = [
    "gps_latitude",
    "gps_longitude",
];

foreach($fieldsToAdd as $field) {
    $query  = Database::prepare($connection, "SELECT `?` FROM `?` LIMIT 1", array($field, LYCHEE_TABLE_PHOTOS));
    $result = Database::execute($connection, $query, $version, __LINE__);

    if ($result===false) {

        $query  = Database::prepare($connection, "ALTER TABLE `?` ADD `?` NUMERIC(9,6)", array(LYCHEE_TABLE_PHOTOS, $field));
        $result = Database::execute($connection, $query, $version, __LINE__);

        if ($result===false) Response::error('Could not add ' . $field . ' to database!');

    }
}

$query  = Database::prepare($connection, "SELECT `id`, `url`, `type` FROM `?` WHERE `gps_latitude` IS NULL OR `gps_longitude` IS NULL", array(LYCHEE_TABLE_PHOTOS));
$result = Database::execute($connection, $query, $version, __LINE__);

if ($result===false) Response::error('Could not fetch photos from database!');

while ($row = ($result->fetch_assoc())) {
    $exif = null;
    $path = LYCHEE_UPLOADS_BIG . $row['url'];

    if ($row['type']=='image/jpeg') $exif = @exif_read_data($path, 'EXIF', false, false);

    $info = [
        "latitude" => null,
        "longitude" => null,
    ];

    if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLatitudeRef'])) $info['latitude'] = getGPSCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
    if (!empty($exif['GPSLongitude']) && !empty($exif['GPSLongitudeRef'])) $info['longitude'] = getGPSCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

    if (!empty($info['latitude']) && !empty($info['longitude'])) {
        $query  = Database::prepare(
            $connection,
            "UPDATE ? SET gps_latitude = {$info['latitude']}, gps_longitude = {$info['longitude']} WHERE id = ?",
            array(LYCHEE_TABLE_PHOTOS, $row['id'])
        );
        $updateResult = Database::execute($connection, $query, $version, __LINE__);
        if ($updateResult === false) Response::error('Could not update gps coordinates of photo id = ' . $row['id'] . ' in database!');
    } else {
        $query  = Database::prepare(
            $connection,
            "UPDATE ? SET gps_latitude = NULL, gps_longitude = NULL WHERE id = ?",
            array(LYCHEE_TABLE_PHOTOS, $row['id'])
        );
        $updateResult = Database::execute($connection, $query, $version, __LINE__);
        if ($updateResult === false) Response::error('Could not reset gps coordinates of photo id = ' . $row['id'] . ' in database!');
    }
}


// Set version
if (Database::setVersion($connection, '030200')===false) Response::error('Could not update version of database!');
