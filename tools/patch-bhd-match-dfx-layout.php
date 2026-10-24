<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Make BHD lobby match the DFX lobby layout
|--------------------------------------------------------------------------
|
| Run from the nova-lobby site root:
|
|   php tools/patch-bhd-match-dfx-layout.php
|
| The patch:
| - backs up bhd/index.php and bhd/partials.php
| - installs bhd/assets/dfx-layout.css
| - replaces only render_header() with the DFX-style BHD header
| - changes the old BHD hero wording to the DFX-style wording when found
| - preserves BHD server fetching, filters, table data, View/Join logic
|
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this patcher from the command line only.\n");
}

$siteRoot = dirname(__DIR__);
$bhdDir = $siteRoot . '/bhd';

$indexFile = $bhdDir . '/index.php';
$partialsFile = $bhdDir . '/partials.php';
$assetsDir = $bhdDir . '/assets';
$cssFile = $assetsDir . '/dfx-layout.css';

foreach ([$indexFile, $partialsFile] as $required) {
    if (!is_file($required)) {
        fwrite(
            STDERR,
            "Required BHD file not found:\n{$required}\n"
        );
        exit(1);
    }
}

if (!is_dir($assetsDir) && !mkdir($assetsDir, 0755, true)) {
    fwrite(
        STDERR,
        "Unable to create BHD assets directory:\n{$assetsDir}\n"
    );
    exit(1);
}

$stamp = date('Ymd-His');

$indexBackup = $indexFile . '.bak-' . $stamp;
$partialsBackup = $partialsFile . '.bak-' . $stamp;

if (!copy($indexFile, $indexBackup)) {
    fwrite(STDERR, "Unable to back up {$indexFile}\n");
    exit(1);
}

if (!copy($partialsFile, $partialsBackup)) {
    fwrite(STDERR, "Unable to back up {$partialsFile}\n");
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Copy the packaged DFX-match stylesheet into BHD/assets
|--------------------------------------------------------------------------
*/

$packagedCss =
    __DIR__ .
    '/../bhd/assets/dfx-layout.css';

if (!is_file($packagedCss)) {
    fwrite(
        STDERR,
        "Packaged CSS file not found:\n{$packagedCss}\n"
    );
    exit(1);
}

if (!copy($packagedCss, $cssFile)) {
    fwrite(
        STDERR,
        "Unable to install:\n{$cssFile}\n"
    );
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Replace render_header() only
|--------------------------------------------------------------------------
*/

$partials = file_get_contents($partialsFile);

if ($partials === false) {
    fwrite(STDERR, "Unable to read {$partialsFile}\n");
    exit(1);
}

$marker = 'DEVILISH_BHD_DFX_LAYOUT_HEADER_V3';

$functionPos = strpos($partials, 'function render_header');

if ($functionPos === false) {
    fwrite(
        STDERR,
        "Could not find render_header() in {$partialsFile}\n"
    );
    exit(1);
}

$openBrace = strpos($partials, '{', $functionPos);

if ($openBrace === false) {
    fwrite(
        STDERR,
        "Could not locate render_header() opening brace.\n"
    );
    exit(1);
}

$length = strlen($partials);
$depth = 0;
$inSingle = false;
$inDouble = false;
$escaped = false;
$endPos = null;

for ($i = $openBrace; $i < $length; $i++) {

    $ch = $partials[$i];

    if ($escaped) {
        $escaped = false;
        continue;
    }

    if (($inSingle || $inDouble) && $ch === '\\') {
        $escaped = true;
        continue;
    }

    if (!$inDouble && $ch === "'") {
        $inSingle = !$inSingle;
        continue;
    }

    if (!$inSingle && $ch === '"') {
        $inDouble = !$inDouble;
        continue;
    }

    if ($inSingle || $inDouble) {
        continue;
    }

    if ($ch === '{') {
        $depth++;
        continue;
    }

    if ($ch === '}') {
        $depth--;

        if ($depth === 0) {
            $endPos = $i;
            break;
        }
    }
}

if ($endPos === null) {
    fwrite(
        STDERR,
        "Could not determine the end of render_header().\n"
    );
    exit(1);
}

$newHeader = <<<'PHP'
/* DEVILISH_BHD_DFX_LAYOUT_HEADER_V3 */
function render_header(string $title = '', string $active = ''): void
{
    $pageTitle =
        $title !== ''
        ? $title . ' - Delta Force: Black Hawk Down'
        : 'Delta Force: Black Hawk Down';

    $mainLobbyUrl = '/';
    $bhdLobbyUrl = base_url();
    $aboutUrl = base_url('about.php');

    $bhdActive =
        in_array(
            $active,
            ['', 'servers', 'gametypes'],
            true
        )
        ? ' active'
        : '';

    $aboutActive =
        $active === 'about'
        ? ' active'
        : '';

    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>
<title><?=e($pageTitle)?></title>

<link
    rel="stylesheet"
    href="<?=e(base_url('assets/app.css'))?>"
>

<link
    rel="stylesheet"
    href="<?=e(base_url('assets/dfx-layout.css'))?>"
>
</head>

<body>

<header class="devilish-bhd-topbar">

    <div class="devilish-bhd-brand">

        <h1>
            Delta Force: Black Hawk Down
        </h1>

        <p>
            Devilish Services Nova Lobby
        </p>

    </div>

    <nav
        class="devilish-bhd-nav"
        aria-label="BHD lobby navigation"
    >

        <a href="<?=e($mainLobbyUrl)?>">
            Main Lobby
        </a>

        <a
            class="<?=trim($bhdActive)?>"
            href="<?=e($bhdLobbyUrl)?>"
        >
            BHD Lobby
        </a>

        <a
            class="<?=trim($aboutActive)?>"
            href="<?=e($aboutUrl)?>"
        >
            About
        </a>

    </nav>

</header>
<?php
}
PHP;

$partials =
    substr($partials, 0, $functionPos) .
    $newHeader .
    substr($partials, $endPos + 1);

if (
    file_put_contents(
        $partialsFile,
        $partials,
        LOCK_EX
    ) === false
) {
    fwrite(
        STDERR,
        "Unable to update {$partialsFile}\n"
    );
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Change the old BHD hero wording to match DFX where those exact strings
| exist. This is text-only; server logic is not touched.
|--------------------------------------------------------------------------
*/

$index = file_get_contents($indexFile);

if ($index === false) {
    fwrite(
        STDERR,
        "Unable to read {$indexFile}\n"
    );
    exit(1);
}

$replacements = [
    'MULTIPLAYER SERVER BROWSER'
        => 'LIVE SERVER LIST',

    'Find a BHD server'
        => 'Delta Force: Black Hawk Down',

    'Live Delta Force: Black Hawk Down servers with Nova-style metadata, country flags, server status and player counts.'
        => 'Delta Force: Black Hawk Down multiplayer servers',
];

$index = str_replace(
    array_keys($replacements),
    array_values($replacements),
    $index
);

if (
    file_put_contents(
        $indexFile,
        $index,
        LOCK_EX
    ) === false
) {
    fwrite(
        STDERR,
        "Unable to update {$indexFile}\n"
    );
    exit(1);
}

echo
    "BHD lobby DFX-style layout installed.\n\n" .
    "Updated:\n" .
    "  {$indexFile}\n" .
    "  {$partialsFile}\n" .
    "  {$cssFile}\n\n" .
    "Backups:\n" .
    "  {$indexBackup}\n" .
    "  {$partialsBackup}\n\n" .
    "BHD data sources and server logic were left intact.\n";
