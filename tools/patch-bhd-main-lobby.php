<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BHD Main Lobby button patcher
|--------------------------------------------------------------------------
|
| Run once from the nova-lobby site root:
|
|   php tools/patch-bhd-main-lobby.php
|
| It creates a timestamped backup of bhd/index.php before changing it.
|
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this patcher from the command line only.\n");
}

$siteRoot =
    dirname(__DIR__);

$target =
    $siteRoot .
    '/bhd/index.php';

if (!is_file($target)) {
    fwrite(
        STDERR,
        "BHD index.php was not found at:\n{$target}\n"
    );

    exit(1);
}

$source =
    file_get_contents($target);

if ($source === false) {
    fwrite(
        STDERR,
        "Unable to read {$target}\n"
    );

    exit(1);
}

$marker =
    'DEVILISH_MAIN_LOBBY_BUTTON';

if (str_contains($source, $marker)) {

    echo
        "Main Lobby button is already installed in BHD index.php\n";

    exit(0);
}

$backup =
    $target .
    '.bak-' .
    date('Ymd-His');

if (!copy($target, $backup)) {
    fwrite(
        STDERR,
        "Unable to create backup:\n{$backup}\n"
    );

    exit(1);
}

$style = <<<'HTML'

<!-- DEVILISH_MAIN_LOBBY_BUTTON_STYLE -->
<style id="devilish-main-lobby-button-style">
.devilish-main-lobby-button{
    position:fixed;
    top:18px;
    left:18px;
    z-index:99999;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 13px;
    border:1px solid rgba(140,205,116,.5);
    border-radius:8px;
    background:rgba(8,18,12,.92);
    color:#c7efb7 !important;
    text-decoration:none !important;
    font:700 12px Arial,Helvetica,sans-serif;
    box-shadow:0 8px 24px rgba(0,0,0,.32);
    backdrop-filter:blur(5px);
}
.devilish-main-lobby-button:hover{
    border-color:#a6e28e;
    background:#17311b;
    color:#fff !important;
}
@media(max-width:700px){
    .devilish-main-lobby-button{
        top:10px;
        left:10px;
        padding:8px 10px;
        font-size:11px;
    }
}
</style>
HTML;

$button = <<<'HTML'

<!-- DEVILISH_MAIN_LOBBY_BUTTON -->
<a
    class="devilish-main-lobby-button"
    href="/"
    title="Return to the Devilish Services main lobby"
>
    ← Main Lobby
</a>
HTML;


/*
|--------------------------------------------------------------------------
| Inject CSS
|--------------------------------------------------------------------------
*/

if (
    stripos(
        $source,
        '</head>'
    ) !== false
) {
    $source =
        preg_replace(
            '/<\/head>/i',
            $style .
            "\n</head>",
            $source,
            1
        );
}
else {
    $source =
        $style .
        "\n" .
        $source;
}


/*
|--------------------------------------------------------------------------
| Inject the button immediately after <body>
|--------------------------------------------------------------------------
*/

if (
    preg_match(
        '/<body\b[^>]*>/i',
        $source
    )
) {
    $source =
        preg_replace(
            '/(<body\b[^>]*>)/i',
            '$1' .
            "\n" .
            $button,
            $source,
            1
        );
}
elseif (
    stripos(
        $source,
        '</body>'
    ) !== false
) {
    $source =
        preg_replace(
            '/<\/body>/i',
            $button .
            "\n</body>",
            $source,
            1
        );
}
else {
    $source .=
        "\n" .
        $button .
        "\n";
}


if (
    file_put_contents(
        $target,
        $source,
        LOCK_EX
    ) === false
) {
    fwrite(
        STDERR,
        "Unable to write updated BHD index.php\n" .
        "Backup remains at:\n{$backup}\n"
    );

    exit(1);
}


echo
    "BHD Main Lobby button installed.\n" .
    "Updated: {$target}\n" .
    "Backup:  {$backup}\n";
