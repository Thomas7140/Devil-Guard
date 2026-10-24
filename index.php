<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Devilish Services Nova Lobby</title>
<style>
:root{
    --bg:#050b11;
    --panel:#0b1620;
    --line:#1e3547;
    --text:#f2f7fb;
    --muted:#8799a7;
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%}
body{
    min-height:100vh;
    color:var(--text);
    font:14px Arial,Helvetica,sans-serif;
    background:
        radial-gradient(circle at 50% 0,#17324a 0,#09131d 42%,#03070b 100%);
}
.wrap{
    width:min(1320px,calc(100% - 30px));
    margin:auto;
    padding:46px 0 60px;
}
.header{
    text-align:center;
    margin-bottom:30px;
}
.header h1{
    margin:0;
    font-size:clamp(32px,5vw,54px);
    letter-spacing:.01em;
}
.header h1 span{color:#73c8ff}
.header p{
    margin:10px 0 0;
    color:#9eb0be;
    font-size:15px;
}
.games{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:20px;
}
.game-card{
    position:relative;
    display:flex;
    flex-direction:column;
    min-height:430px;
    overflow:hidden;
    border:1px solid rgba(255,255,255,.11);
    border-radius:18px;
    color:#fff;
    text-decoration:none;
    background:#0d1822;
    box-shadow:0 22px 55px rgba(0,0,0,.34);
    transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
}
.game-card:hover{
    transform:translateY(-6px);
    box-shadow:0 30px 70px rgba(0,0,0,.48);
}
.game-card.bhd:hover{border-color:#79ba73}
.game-card.dfx:hover{border-color:#60c7ff}
.game-card.dfx2:hover{border-color:#ef9c51}
.game-image{
    position:relative;
    flex:0 0 245px;
    height:245px;
    display:grid;
    place-items:center;
    padding:12px;
    background:
        radial-gradient(circle at center,#162a3a 0,#09131d 72%,#050b10 100%);
    border-bottom:1px solid rgba(255,255,255,.08);
}
.game-image img{
    display:block;
    width:100%;
    height:100%;
    object-fit:contain;
    object-position:center;
    border-radius:12px;
    transition:transform .25s ease;
}
.game-card:hover .game-image img{transform:scale(1.015)}
.game-fallback{
    display:none;
    width:100%;
    height:100%;
    place-items:center;
    padding:30px;
    text-align:center;
    font-size:29px;
    font-weight:900;
    background:linear-gradient(145deg,#1a3042,#08131d);
}
.overlay{
    position:absolute;
    inset:0;
    pointer-events:none;
    background:
        linear-gradient(to bottom,
            rgba(0,0,0,.02) 0%,
            rgba(0,0,0,.00) 48%,
            rgba(3,8,12,.22) 58%,
            rgba(3,8,12,.82) 100%);
}
.badge{
    position:absolute;
    top:17px;
    right:17px;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 10px;
    border:1px solid rgba(255,255,255,.12);
    border-radius:20px;
    background:rgba(4,10,15,.76);
    backdrop-filter:blur(5px);
    font-size:10px;
    font-weight:800;
    letter-spacing:.05em;
}
.badge i{
    width:8px;
    height:8px;
    border-radius:50%;
    background:#27d77b;
    box-shadow:0 0 9px rgba(39,215,123,.7);
}
.content{
    position:relative;
    z-index:2;
    flex:1;
    padding:23px 25px 25px;
    background:
        linear-gradient(180deg,
            rgba(12,22,31,.97) 0%,
            rgba(7,14,20,.99) 100%);
}
.kicker{
    margin-bottom:7px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.11em;
}
.bhd .kicker{color:#91ce7a}
.dfx .kicker{color:#68caff}
.dfx2 .kicker{color:#f1a45d}
.content h2{
    margin:0 0 10px;
    font-size:26px;
    line-height:1.12;
}
.content p{
    min-height:44px;
    margin:0 0 18px;
    color:#b5c3cf;
    line-height:1.5;
}
.open{
    display:inline-flex;
    align-items:center;
    padding:10px 14px;
    border:1px solid rgba(255,255,255,.16);
    border-radius:8px;
    background:rgba(255,255,255,.06);
    font-weight:800;
}
.bhd .open{color:#b7e5a4;border-color:rgba(128,190,110,.36)}
.dfx .open{color:#a0dcff;border-color:rgba(82,182,243,.4)}
.dfx2 .open{color:#ffc087;border-color:rgba(236,144,70,.42)}
.footer{
    margin-top:30px;
    text-align:center;
    color:#637786;
    font-size:12px;
}
@media(max-width:980px){
    .games{grid-template-columns:1fr}
    .game-card{min-height:390px}
    .game-image{flex-basis:230px;height:230px}
    .content p{min-height:0}
}
</style>
</head>
<body>
<div class="wrap">

<header class="header">
    <h1>Devilish Services <span>Nova Lobby</span></h1>
    <p>Select a game to view its multiplayer lobby.</p>
</header>

<main class="games">

    <a class="game-card bhd" href="/bhd/">
        <div class="game-image">
            <img
                src="/images/DFBHD.avif"
                alt="Delta Force Black Hawk Down"
                onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
            >
            <div class="game-fallback">DELTA FORCE<br>BLACK HAWK DOWN</div>
        </div>
        <div class="overlay"></div>
        <div class="badge"><i></i> LOBBY</div>
        <div class="content">
            <div class="kicker">Delta Force</div>
            <h2>Black Hawk Down</h2>
            <p>View live DFBHD servers, players, missions and server information.</p>
            <span class="open">Open BHD Lobby&nbsp; →</span>
        </div>
    </a>

    <a class="game-card dfx" href="/DFX/">
        <div class="game-image">
            <img
                src="/images/DFX.avif"
                alt="Delta Force Xtreme"
                onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
            >
            <div class="game-fallback">DELTA FORCE<br>XTREME</div>
        </div>
        <div class="overlay"></div>
        <div class="badge"><i></i> LOBBY</div>
        <div class="content">
            <div class="kicker">Delta Force</div>
            <h2>Xtreme</h2>
            <p>View the Devilish Services DFX multiplayer server lobby.</p>
            <span class="open">Open DFX Lobby&nbsp; →</span>
        </div>
    </a>

    <a class="game-card dfx2" href="/dfx2/">
        <div class="game-image">
            <img
                src="/images/DFX2.avif"
                alt="Delta Force Xtreme 2"
                onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"
            >
            <div class="game-fallback">DELTA FORCE<br>XTREME 2</div>
        </div>
        <div class="overlay"></div>
        <div class="badge"><i></i> LOBBY</div>
        <div class="content">
            <div class="kicker">Delta Force</div>
            <h2>Xtreme 2</h2>
            <p>View the Devilish Services DFX2 multiplayer server lobby.</p>
            <span class="open">Open DFX2 Lobby&nbsp; →</span>
        </div>
    </a>

</main>

<footer class="footer">
    Devilish Services • Delta Force Multiplayer
</footer>

</div>
</body>
</html>
