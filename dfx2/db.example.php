<?php
declare(strict_types=1);

/*
 * Example only.
 *
 * Copy to:
 * /home/devilishservices/connections/dfx2/db.php
 *
 * Then replace the placeholders below with the DFX2 database credentials.
 */

function dfx2_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=localhost;dbname=devilishservices_dfx2;charset=utf8mb4',
        'YOUR_DATABASE_USER',
        'YOUR_DATABASE_PASSWORD',
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false,
        ]
    );

    return $pdo;
}
