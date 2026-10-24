<?php
declare(strict_types=1);

function dfx_h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dfx_db_handle(): PDO {
    return function_exists('dfxDb') ? dfxDb() : dfx_db();
}

function dfx_nova_name(string $value): string {
    $colors=[
        '0'=>'#FFFFFF','1'=>'#00FF00','2'=>'#FF4040','3'=>'#008000','4'=>'#FFFF00',
        '5'=>'#00FFFF','6'=>'#A0A0A0','7'=>'#008080','8'=>'#B04040','9'=>'#80FFFF'
    ];
    $parts=preg_split('/(~[0-9])/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) return dfx_h($value);
    $color='#FFFFFF';
    $html='';
    foreach ($parts as $part) {
        if (preg_match('/^~([0-9])$/', $part, $m)) {
            $color=$colors[$m[1]];
            continue;
        }
        if ($part!=='') {
            $html.='<span style="color:'.dfx_h($color).'">'.dfx_h($part).'</span>';
        }
    }
    return $html!=='' ? $html : dfx_h($value);
}

function dfx_clean_name(string $value): string {
    return trim((string)preg_replace('/~[0-9]/', '', $value));
}

function dfx_heartbeat_age(?string $value): int {
    if (!$value) return 999999;
    $time=strtotime($value);
    return $time===false ? 999999 : max(0, time()-$time);
}

function dfx_is_online(array $server): bool {
    return ($server['status'] ?? '')==='online'
        && dfx_heartbeat_age($server['last_heartbeat_at'] ?? null)<=120;
}

function dfx_age_text(int $seconds): string {
    if ($seconds<5) return 'just now';
    if ($seconds<60) return $seconds.' sec ago';
    if ($seconds<3600) return floor($seconds/60).' min ago';
    if ($seconds<86400) return floor($seconds/3600).' hr ago';
    return floor($seconds/86400).' days ago';
}

function dfx_country(?string $code): string {
    $code=strtoupper(trim((string)$code));
    return preg_match('/^[A-Z]{2}$/', $code) ? $code : '--';
}
