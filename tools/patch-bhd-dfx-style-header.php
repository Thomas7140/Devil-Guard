<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BHD DFX-style header patcher
|--------------------------------------------------------------------------
|
| Run once from the nova-lobby site root:
|
|   php tools/patch-bhd-dfx-style-header.php
|
| It backs up bhd/partials.php, then replaces only render_header().
| render_footer() and the rest of the BHD lobby remain untouched.
|
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this patcher from the command line only.\n");
}

$siteRoot = dirname(__DIR__);
$target = $siteRoot . '/bhd/partials.php';

if (!is_file($target)) {
    fwrite(
        STDERR,
        "BHD partials.php was not found at:\n{$target}\n"
    );
    exit(1);
}

$source = file_get_contents($target);

if ($source === false) {
    fwrite(
        STDERR,
        "Unable to read:\n{$target}\n"
    );
    exit(1);
}

$marker = 'DEVILISH_BHD_DFX_STYLE_HEADER_V2';

if (str_contains($source, $marker)) {
    echo "The DFX-style BHD header is already installed.\n";
    exit(0);
}

$functionPos = strpos($source, 'function render_header');

if ($functionPos === false) {
    fwrite(
        STDERR,
        "Could not find function render_header() in:\n{$target}\n"
    );
    exit(1);
}

$openBrace = strpos($source, '{', $functionPos);

if ($openBrace === false) {
    fwrite(
        STDERR,
        "Could not locate the opening brace for render_header().\n"
    );
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Find the exact end of render_header() with brace matching.
|--------------------------------------------------------------------------
*/

$length = strlen($source);
$depth = 0;
$inSingle = false;
$inDouble = false;
$escaped = false;
$endPos = null;

for ($i = $openBrace; $i < $length; $i++) {

    $ch = $source[$i];

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

$newFunction = <<<'PHP'
/* DEVILISH_BHD_DFX_STYLE_HEADER_V2 */
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

<?php
/*
 * Keep the lobby's existing stylesheet when present.
 * This mirrors the existing BHD partials behavior without requiring
 * changes to app.css.
 */
?>
<link
    rel="stylesheet"
    href="<?=e(base_url('assets/app.css'))?>"
>

<style>
:root{
    --devilish-bhd-bg:#07100b;
    --devilish-bhd-panel:#0d1c12;
    --devilish-bhd-line:#284931;
    --devilish-bhd-green:#76c86d;
    --devilish-bhd-green-soft:#b9e5b4;
    --devilish-bhd-text:#edf7ef;
    --devilish-bhd-muted:#8ca193;
}

.devilish-bhd-topbar{
    width:min(1500px,calc(100% - 28px));
    margin:0 auto 18px;
    padding-top:24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
}

.devilish-bhd-brand h1{
    margin:0;
    color:var(--devilish-bhd-text);
    font-size:30px;
    line-height:1.1;
}

.devilish-bhd-brand p{
    margin:6px 0 0;
    color:var(--devilish-bhd-muted);
    font-size:13px;
}

.devilish-bhd-nav{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.devilish-bhd-nav a{
    display:inline-flex;
    align-items:center;
    min-height:38px;
    padding:9px 13px;
    border:1px solid var(--devilish-bhd-line);
    border-radius:8px;
    background:#0c1d11;
    color:#c8e7ca;
    text-decoration:none;
    font-weight:700;
    font-size:12px;
}

.devilish-bhd-nav a:hover,
.devilish-bhd-nav a.active{
    border-color:var(--devilish-bhd-green);
    background:#17341e;
    color:#fff;
}

@media(max-width:760px){
    .devilish-bhd-topbar{
        align-items:flex-start;
        flex-direction:column;
        padding-top:16px;
    }

    .devilish-bhd-brand h1{
        font-size:26px;
    }
}
</style>
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

$updated =
    substr($source, 0, $functionPos) .
    $newFunction .
    substr($source, $endPos + 1);

if (
    file_put_contents(
        $target,
        $updated,
        LOCK_EX
    ) === false
) {
    fwrite(
        STDERR,
        "Unable to write the updated BHD partials.php.\n" .
        "Backup remains at:\n{$backup}\n"
    );
    exit(1);
}

echo
    "BHD DFX-style header installed.\n" .
    "Updated: {$target}\n" .
    "Backup:  {$backup}\n" .
    "\n" .
    "Header now shows:\n" .
    "  Delta Force: Black Hawk Down\n" .
    "  Devilish Services Nova Lobby\n" .
    "  Main Lobby | BHD Lobby | About\n";
