<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
About the DFX2 Lobby
</title>

<link
    rel="stylesheet"
    href="/assets/lobby.css"
>

</head>

<body data-lobby-theme="dfx2">

<div class="wrap">


<div class="topbar">

    <div class="brand">

        <h1>
            Delta Force Xtreme 2
        </h1>

        <p>
            Devilish Services Nova Lobby
        </p>

    </div>


    <div class="nav">

        <a href="/">
            Main Lobby
        </a>

        <a href="./">
            DFX2 Lobby
        </a>

        <a
            class="active"
            href="about.php"
        >
            About
        </a>

    </div>

</div>


<div class="card">


<div class="hero">

    <div class="hero-brand">

        <img
            class="hero-logo"
            src="/images/DFX2.avif"
            alt="Delta Force Xtreme 2"
        >

        <div>

            <div class="hero-title">
                About this Lobby
            </div>

            <div class="hero-sub">
                Devilish Services Delta Force Xtreme 2 multiplayer lobby
            </div>

        </div>

    </div>

</div>


<div class="about">

<p>
This lobby provides the same BHD-style server browsing layout used by
the other Devilish Services Delta Force lobbies, with a separate DFX2
data source and DFX2 styling.
</p>


<h2>
Database setup
</h2>

<p>
The default configuration expects:
</p>

<pre>/home/devilishservices/connections/dfx2/db.php</pre>

<p>
and the tables:
</p>

<pre>dfx2_servers
dfx2_server_players</pre>

<p>
If your connection file or table names are different, edit:
</p>

<pre>DFX2/includes/config.php</pre>


<h2>
Server status
</h2>

<p>
A server is active when its status is <strong>online</strong> and its
last heartbeat is no more than 120 seconds old. An online empty server
uses a slow flashing green indicator. A server with players uses solid
green. Stale or offline servers use red.
</p>


<h2>
Images
</h2>

<p>
The DFX2 lobby uses:
</p>

<pre>/images/DFX2.avif</pre>


<p>

<a
    class="button"
    href="./"
>
    ← Back to DFX2 Lobby
</a>

</p>


</div>

</div>

</div>

</body>
</html>
