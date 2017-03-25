<?php

/**
 * Update to version 4.0.0
 */

use Lychee\Modules\Database;
use Lychee\Modules\Response;

// Change type of the id field

$fieldsToAdd = [
    "captured" => 20,
    "telescope" => 100,
    "lens" => 100,
    "mount" => 100,
    "focal_length" => 20,
    "sensor" => 100,
    "exposure" => 100,
    "accessories" => 100,
    "guiding" => 100,
];

foreach($fieldsToAdd as $field => $length) {

    $query  = Database::prepare($connection, "SELECT `?` FROM `?` LIMIT 1", array($field, LYCHEE_TABLE_PHOTOS));
    $result = Database::execute($connection, $query, 'update_040000', __LINE__);

    if ($result===false) {

        $query  = Database::prepare($connection, "ALTER TABLE `?` ADD `?` VARCHAR(?) NOT NULL DEFAULT ''", array(LYCHEE_TABLE_PHOTOS, $field, $length));
        $result = Database::execute($connection, $query, 'update_040000', __LINE__);

        if ($result===false) Response::error('Could not add ' . $field . ' to database!');

    }

}


// Set version
if (Database::setVersion($connection, '040000')===false) Response::error('Could not update version of database!');

?>