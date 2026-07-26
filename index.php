<?php
session_start();
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Araliya PMS — Login</title>
<style>

*{ margin:0; padding:0; box-sizing:border-box; }

body{
    font-family:'Segoe UI', system-ui, sans-serif;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #b8cfe0 0%, #d0e5f5 50%, #bdd0e2 100%);
    padding:20px;
}

/* ═══ CARD ═══ */
.card{
    width:1200px;
    max-width:100%;
    height:92vh;          /* fills screen nicely */
    max-height:780px;
    display:flex;
    border-radius:28px;
    overflow:hidden;
    box-shadow:
        0 2px 4px rgba(0,0,0,.05),
        0 12px 32px rgba(0,0,0,.12),
        0 40px 80px rgba(0,0,0,.16);
}

/* ═══ LEFT ═══ */
.left{
    width:52%;
    position:relative;
    /* ↓ CHANGE THIS PATH TO YOUR HOTEL PHOTO */
    background: url('img/img2.png') center center / cover no-repeat;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    padding:36px;
}

.left::after{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(
        to bottom,
        rgba(0,0,0,.15) 0%,
        rgba(0,0,0,.01) 35%,
        rgba(0,0,0,.50) 68%,
        rgba(0,0,0,.82) 100%
    );
}

/* hotel badge top-left */
.hotel-badge{
    position:relative;
    z-index:2;
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:rgba(255,255,255,.14);
    backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,.28);
    border-radius:50px;
    padding:8px 20px 8px 10px;
    width:fit-content;
}

.badge-icon{
    width:36px;
    height:36px;
    border-radius:50%;
    background:linear-gradient(135deg,#ecd18c,#c9912a);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    font-weight:900;
    color:#4d3000;
    font-family:Georgia,serif;
    flex-shrink:0;
}

.badge-text{
    font-size:11px;
    font-weight:700;
    letter-spacing:2.5px;
    text-transform:uppercase;
    color:rgba(255,255,255,.92);
}

/* bottom text */
.left-bottom{
    position:relative;
    z-index:2;
}

.left-bottom .tag{
    display:inline-block;
    background:rgba(201,168,76,.22);
    border:1px solid rgba(201,168,76,.42);
    backdrop-filter:blur(6px);
    color:#f5d78d;
    font-size:10px;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    padding:5px 15px;
    border-radius:50px;
    margin-bottom:16px;
}

.left-bottom h2{
    font-size:40px;
    font-weight:800;
    color:#fff;
    line-height:1.15;
    margin-bottom:14px;
    letter-spacing:-.5px;
    text-shadow:0 2px 16px rgba(0,0,0,.5);
}

.left-bottom h2 em{
    font-style:italic;
    font-weight:600;
    color:#f5d78d;
}

.left-divider{
    width:44px;
    height:2px;
    background:linear-gradient(90deg,#f5d78d,transparent);
    margin:0 0 14px;
}

.left-bottom p{
    font-size:14px;
    color:rgba(255,255,255,.75);
    line-height:1.75;
    max-width:340px;
}

/* stars row */
.stars{
    display:flex;
    gap:4px;
    margin-top:18px;
}
.stars span{
    font-size:14px;
    color:#f5d78d;
}
.stars em{
    font-style:normal;
    font-size:11px;
    color:rgba(255,255,255,.55);
    letter-spacing:1px;
    margin-left:8px;
    align-self:center;
}

/* ═══ RIGHT ═══ */
.right{
    width:48%;
    display:flex;
    flex-direction:column;
    background:linear-gradient(170deg, #f2f8ff 0%, #e6f2fb 55%, #daedf8 100%);
    position:relative;
    overflow:hidden;
}

/* decorative blobs */
.right::before{
    content:'';
    position:absolute;
    width:380px; height:380px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(29,111,164,.06) 0%, transparent 68%);
    top:-100px; right:-100px;
    pointer-events:none;
}
.right::after{
    content:'';
    position:absolute;
    width:240px; height:240px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(29,111,164,.05) 0%, transparent 68%);
    bottom:-70px; left:-50px;
    pointer-events:none;
}

/* ── TOP BAR ── */
.top-bar{
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:36px 48px 0;
    flex-shrink:0;
    position:relative;
    z-index:1;
}

.logo-row{
    display:flex;
    align-items:center;
    gap:13px;
}

.logo-mark{
    width:48px; height:48px;
    border-radius:14px;
    background:linear-gradient(135deg,#1a5e8a,#2a8bd4);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    box-shadow:0 6px 18px rgba(29,111,164,.32);
    flex-shrink:0;
}

.logo-text-wrap{
    display:flex;
    flex-direction:column;
    gap:1px;
}

.logo-main{
    font-size:20px;
    font-weight:700;
    color:#0f2a44;
    letter-spacing:.2px;
    line-height:1.1;
}

.logo-sub{
    font-size:9px;
    font-weight:700;
    letter-spacing:2.8px;
    text-transform:uppercase;
    color:#6a94b8;
}

/* gold accent line under logo */
.logo-rule{
    width:52px;
    height:1.5px;
    background:linear-gradient(90deg,transparent,#c9a84c,transparent);
    margin-top:14px;
}

/* ── FORM WRAP ── */
.form-wrap{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    padding:0 52px 36px;
    position:relative;
    z-index:1;
}

.form-inner{
    width:100%;
    max-width:360px;
}

/* heading — centered */
.form-inner h1{
    font-size:32px;
    font-weight:700;
    color:#0f2a44;
    margin-bottom:6px;
    letter-spacing:-.4px;
    text-align:center;
}

.form-inner .sub{
    font-size:14px;
    color:#6a94b8;
    margin-bottom:32px;
    font-weight:400;
    text-align:center;
}

/* thin separator under subtitle */
.head-rule{
    width:36px;
    height:2px;
    background:linear-gradient(90deg,transparent,#1d6fa4,transparent);
    margin:0 auto 28px;
}

/* ── FIELDS ── */
.field{
    position:relative;
    margin-bottom:18px;
}

.field label{
    display:block;
    font-size:10.5px;
    font-weight:700;
    letter-spacing:1.2px;
    text-transform:uppercase;
    color:#3d6a8a;
    margin-bottom:7px;
    padding-left:3px;
}

.field input{
    width:100%;
    height:52px;
    border:1.5px solid #bad4e8;
    border-radius:12px;
    padding:0 56px 0 18px;
    font-size:15px;
    font-family:'Segoe UI',sans-serif;
    color:#0f2a44;
    background:rgba(255,255,255,.75);
    outline:none;
    transition:border-color .22s, box-shadow .22s, background .22s;
}

.field input::placeholder{
    color:#9ab8cf;
    font-size:14px;
}

.field input:focus{
    border-color:#1d6fa4;
    background:#fff;
    box-shadow:0 0 0 4px rgba(29,111,164,.10);
}

.field-icon{
    position:absolute;
    right:17px;
    bottom:15px;
    font-size:20px;
    color:#9ab8cf;
    pointer-events:none;
}

.show-btn{
    position:absolute;
    right:0; bottom:0;
    height:52px; width:68px;
    border:none;
    border-left:1px solid #c4daea;
    border-radius:0 12px 12px 0;
    background:rgba(235,246,255,.90);
    cursor:pointer;
    font-size:10.5px;
    font-weight:700;
    letter-spacing:1px;
    color:#3d6a8a;
    transition:background .2s, color .2s;
}
.show-btn:hover{ background:#cce2f4; color:#1d6fa4; }

/* ── LOGIN BUTTON ── */
.btn-login{
    width:100%;
    height:54px;
    border:none;
    border-radius:13px;
    margin-top:10px;
    font-size:13.5px;
    font-weight:700;
    letter-spacing:1.8px;
    text-transform:uppercase;
    color:#fff;
    cursor:pointer;
    background:linear-gradient(135deg, #1d6fa4 0%, #134f78 100%);
    box-shadow:
        0 4px 14px rgba(29,111,164,.35),
        inset 0 1px 0 rgba(255,255,255,.12);
    transition:transform .2s, box-shadow .2s;
    position:relative;
    overflow:hidden;
}

.btn-login::after{
    content:'';
    position:absolute; inset:0;
    background:linear-gradient(180deg,rgba(255,255,255,.10),transparent);
    pointer-events:none;
}

.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 28px rgba(29,111,164,.45);
}
.btn-login:active{ transform:scale(.98); }

/* ── BOTTOM LINKS ── */
.divider{
    display:flex;
    align-items:center;
    gap:10px;
    margin:20px 0 0;
}
.divider span{ flex:1; height:1px; background:#c4daea; }
.divider p{
    font-size:10px;
    color:#9ab8cf;
    white-space:nowrap;
    letter-spacing:.5px;
}

.forgot{
    display:block;
    text-align:center;
    margin-top:16px;
    font-size:13px;
    color:#3d6a8a;
    text-decoration:none;
    font-weight:500;
    transition:color .2s;
}
.forgot:hover{ color:#1d6fa4; text-decoration:underline; }

.footer{
    text-align:center;
    margin-top:18px;
    font-size:10px;
    letter-spacing:1.4px;
    text-transform:uppercase;
    color:#a8c4d8;
}

/* error */
.error{
    background:rgba(220,38,38,.07);
    border:1px solid rgba(220,38,38,.18);
    color:#b91c1c;
    padding:10px 14px;
    border-radius:10px;
    margin-bottom:16px;
    font-size:13px;
    text-align:center;
}

/* ═══ RESPONSIVE ═══ */
@media(max-width:900px){
    .card{ flex-direction:column; height:auto; max-height:none; }
    .left{ width:100%; height:300px; }
    .right{ width:100%; }
    .form-wrap{ padding:24px 32px 40px; }
    .top-bar{ padding:28px 32px 0; }
}

@media(max-width:480px){
    body{ padding:0; }
    .card{ border-radius:0; }
    .left{ height:230px; padding:24px; }
    .left-bottom h2{ font-size:28px; }
    .form-wrap{ padding:16px 22px 32px; }
    .top-bar{ padding:22px 22px 0; }
    .form-inner h1{ font-size:26px; }
}

</style>
</head>
<body>

<div class="card">

    <!-- ═══ LEFT ═══ -->
    <div class="left">

        <div class="hotel-badge">
            <div class="badge-icon">A</div>
            <span class="badge-text">Araliya Hotels</span>
        </div>

        <div class="left-bottom">
            <div class="tag">✦ Luxury Collection</div>
            <h2>Araliya Beach<br><em>Resort &amp; Spa</em></h2>
            <div class="left-divider"></div>
            <p>Experience luxury hospitality with our advanced Property Management System.</p>
            <div class="stars">
                <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                <em>5-Star Resort</em>
            </div>
        </div>

    </div>

    <!-- ═══ RIGHT ═══ -->
    <div class="right">

        <div class="top-bar">
            <div class="logo-row">
                <div class="logo-mark">🌺</div>
                <div class="logo-text-wrap">
                    <span class="logo-main">Araliya</span>
                    <span class="logo-sub">Property Management</span>
                </div>
            </div>
            <div class="logo-rule"></div>
        </div>

        <div class="form-wrap">
            <div class="form-inner">

                <h1>Welcome Back</h1>
                <p class="sub">Sign in to manage your hotel portfolio</p>
                <div class="head-rule"></div>

                <?php if(isset($_GET['error'])): ?>
                <div class="error">✕ Invalid username or password</div>
                <?php endif; ?>

                <form action="authenticate.php" method="POST">

                    <div class="field">
                        <label>Username</label>
                        <input type="text" name="username"
                               placeholder="Enter your username" required>
                        <span class="field-icon">👤</span>
                    </div>

                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" id="password"
                               placeholder="Enter your password" required>
                        <button type="button" class="show-btn"
                                onclick="togglePassword()">SHOW</button>
                    </div>

                    <button type="submit" class="btn-login">
                        Login to Dashboard
                    </button>

                </form>

                <div class="divider">
                    <span></span>
                    <p>Araliya Beach Resort &amp; Spa</p>
                    <span></span>
                </div>

                <a href="#" class="forgot">Forgot your password?</a>

                <div class="footer">© 2026 Araliya Beach Resort &amp; Spa</div>

            </div>
        </div>

    </div>

</div>

<script>
function togglePassword(){
    const p = document.getElementById('password');
    const b = document.querySelector('.show-btn');
    if(p.type === 'password'){ p.type='text'; b.textContent='HIDE'; }
    else { p.type='password'; b.textContent='SHOW'; }
}
</script>

</body>
</html>