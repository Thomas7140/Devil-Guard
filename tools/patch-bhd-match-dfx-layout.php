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
| - removes the old BHD intro/branding panels from bhd/index.php
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
| Remove the old BHD intro / branding panels from the lobby index.
|
| We only remove complete section/div blocks when they contain one of the
| known legacy BHD headings. The server list/data PHP below is left intact.
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

$legacyNeedles = [
    'MULTIPLAYER SERVER BROWSER',
    'Find a BHD server',
    'Live Delta Force: Black Hawk Down servers with Nova-style metadata',
    'NOVA BHD LOBBY',
    'Delta Force: Black Hawk Down Multiplayer Network',
    'LIVE NETWORK',
];

$removedBlocks = 0;

$removeLegacyBlock =
    static function (
        string $html,
        string $tag
    ) use (
        $legacyNeedles,
        &$removedBlocks
    ): string {

        $pattern =
            '~<' .
            $tag .
            '\\b[^>]*>.*?</' .
            $tag .
            '>~is';

        return (string)preg_replace_callback(
            $pattern,
            static function (
                array $match
            ) use (
                $legacyNeedles,
                &$removedBlocks
            ): string {

                $plain =
                    html_entity_decode(
                        strip_tags($match[0]),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    );

                foreach ($legacyNeedles as $needle) {

                    if (
                        stripos(
                            $plain,
                            $needle
                        ) !== false
                    ) {
                        $removedBlocks++;
                        return '';
                    }
                }

                return $match[0];
            },
            $html
        );
    };

/*
 * First remove section-based intro panels, then known wrapper divs.
 * This handles both the current hero-panel markup and older BHD builds.
 */
$index =
    $removeLegacyBlock(
        $index,
        'section'
    );

$index =
    $removeLegacyBlock(
        $index,
        'header'
    );

/*
 * If a legacy hero was built with a div instead of section/header,
 * remove only divs whose class name clearly identifies them as a hero,
 * intro or network panel and that contain one of the old headings.
 */
$index =
    (string)preg_replace_callback(
        '~<div\\b([^>]*)>.*?</div>~is',
        static function (
            array $match
        ) use (
            $legacyNeedles,
            &$removedBlocks
        ): string {

            $attrs =
                strtolower(
                    (string)(
                        $match[1]
                        ?? ''
                    )
                );

            if (
                !preg_match(
                    '/class\\s*=\\s*["\\\'][^"\\\']*(hero|intro|network)[^"\\\']*["\\\']/i',
                    $attrs
                )
            ) {
                return $match[0];
            }

            $plain =
                html_entity_decode(
                    strip_tags($match[0]),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );

            foreach ($legacyNeedles as $needle) {

                if (
                    stripos(
                        $plain,
                        $needle
                    ) !== false
                ) {
                    $removedBlocks++;
                    return '';
                }
            }

            return $match[0];
        },
        $index
    );

/*
 * Remove empty whitespace left behind by removed panels.
 */
$index =
    preg_replace(
        "/(?:\\r?\\n){3,}/",
        "\n\n",
        $index
    )
    ?? $index;

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
    "Removed legacy BHD intro/branding panels: {$removedBlocks}\n" .
    "BHD data sources and server logic were left intact.\n";
