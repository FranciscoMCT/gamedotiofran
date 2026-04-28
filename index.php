<?php
session_start();

// ── Constantes do jogo ──────────────────────────────────────────────────────
define('MIN', 1);
define('MAX', 100);
define('MAX_TENTATIVAS', 7);

// ── Inicializar / resetar jogo ───────────────────────────────────────────────
function iniciarJogo(): void {
    $_SESSION['numero']     = random_int(MIN, MAX);
    $_SESSION['tentativas'] = 0;
    $_SESSION['historico']  = [];
    $_SESSION['status']     = 'jogando'; // jogando | ganhou | perdeu
    $_SESSION['inicio']     = time();
}

function resetarJogo(): void {
    session_unset();
    iniciarJogo();
}

if (!isset($_SESSION['numero'])) {
    iniciarJogo();
}

if (isset($_POST['reset'])) {
    resetarJogo();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Lógica do chute ──────────────────────────────────────────────────────────
$mensagem    = '';
$classe_msg  = '';
$chute_atual = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chute']) && $_SESSION['status'] === 'jogando') {
    $chute = filter_input(INPUT_POST, 'chute', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => MIN, 'max_range' => MAX]
    ]);

    if ($chute === false || $chute === null) {
        $mensagem   = '[ ERRO ] Digite um número inteiro entre ' . MIN . ' e ' . MAX . '.';
        $classe_msg = 'erro';
    } else {
        $chute_atual = $chute;
        $_SESSION['tentativas']++;
        $restantes = MAX_TENTATIVAS - $_SESSION['tentativas'];

        if ($chute === $_SESSION['numero']) {
            $_SESSION['status']  = 'ganhou';
            $tempo = time() - $_SESSION['inicio'];
            $mensagem   = '[ ACESSO CONCEDIDO ] Número correto: ' . $chute . ' — Tempo: ' . $tempo . 's';
            $classe_msg = 'ganhou';
            $_SESSION['historico'][] = ['valor' => $chute, 'dica' => 'CORRETO ✓', 'classe' => 'correto'];
        } else {
            $diff = abs($chute - $_SESSION['numero']);
            $quente = $diff <= 10 ? '🔥 QUENTE' : ($diff <= 25 ? '〰 MORNO' : '❄ FRIO');

            if ($chute < $_SESSION['numero']) {
                $dica = '▲ MAIOR · ' . $quente;
            } else {
                $dica = '▼ MENOR · ' . $quente;
            }

            $_SESSION['historico'][] = [
                'valor'  => $chute,
                'dica'   => $dica,
                'classe' => $diff <= 10 ? 'quente' : ($diff <= 25 ? 'morno' : 'frio')
            ];

            if ($_SESSION['tentativas'] >= MAX_TENTATIVAS) {
                $_SESSION['status']  = 'perdeu';
                $mensagem   = '[ ACESSO NEGADO ] O número era: ' . $_SESSION['numero'] . '. Tente novamente.';
                $classe_msg = 'perdeu';
            } else {
                $mensagem   = 'Tentativa ' . $_SESSION['tentativas'] . '/' . MAX_TENTATIVAS . ' · ' . $restantes . ' restante(s)';
                $classe_msg = 'info';
            }
        }
    }
}

$tentativas_usadas = $_SESSION['tentativas'] ?? 0;
$status            = $_SESSION['status'] ?? 'jogando';
$historico         = array_reverse($_SESSION['historico'] ?? []);
$barras            = [];
for ($i = 1; $i <= MAX_TENTATIVAS; $i++) {
    $barras[] = $i <= $tentativas_usadas ? 'usado' : 'livre';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NÚMERO SECRETO :: SISTEMA v8.2</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
/* ── Variáveis ── */
:root {
    --verde:   #00ff88;
    --verde2:  #00cc66;
    --azul:    #00aaff;
    --vermelho:#ff3355;
    --amarelo: #ffcc00;
    --laranja: #ff7700;
    --fundo:   #070d0a;
    --painel:  #0d1a12;
    --borda:   #1a3326;
    --texto:   #99ffcc;
    --mono:    'Share Tech Mono', monospace;
    --display: 'Orbitron', sans-serif;
}

/* ── Reset & base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    height: 100%;
    background: var(--fundo);
    color: var(--texto);
    font-family: var(--mono);
    overflow-x: hidden;
}

body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 100vh;
    padding: 20px 12px 40px;
    background-image:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,255,136,0.08) 0%, transparent 70%),
        repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(0,255,136,0.03) 40px),
        repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(0,255,136,0.03) 40px);
}

/* ── Scanlines ── */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(0,0,0,0.12) 2px,
        rgba(0,0,0,0.12) 4px
    );
    pointer-events: none;
    z-index: 999;
}

/* ── Container principal ── */
.terminal {
    width: 100%;
    max-width: 640px;
    background: var(--painel);
    border: 1px solid var(--borda);
    box-shadow:
        0 0 0 1px rgba(0,255,136,0.05),
        0 0 40px rgba(0,255,136,0.08),
        inset 0 0 60px rgba(0,0,0,0.6);
    animation: aparecer .4s ease;
}

@keyframes aparecer {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Barra de título ── */
.titlebar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: rgba(0,255,136,0.05);
    border-bottom: 1px solid var(--borda);
}

.dots { display: flex; gap: 6px; }
.dot  { width: 10px; height: 10px; border-radius: 50%; }
.dot.r { background: #ff3355; }
.dot.y { background: #ffcc00; }
.dot.g { background: #00ff88; animation: pulsar 2s infinite; }

@keyframes pulsar {
    0%, 100% { opacity: 1; }
    50%       { opacity: .4; }
}

.titlebar-text {
    flex: 1;
    text-align: center;
    font-family: var(--mono);
    font-size: .65rem;
    color: rgba(0,255,136,0.5);
    letter-spacing: .15em;
}

/* ── Conteúdo ── */
.content { padding: 24px 28px 28px; }

/* ── Header ── */
.header { text-align: center; margin-bottom: 28px; }

.logo {
    font-family: var(--display);
    font-size: clamp(1.4rem, 5vw, 2rem);
    font-weight: 900;
    color: var(--verde);
    letter-spacing: .08em;
    text-shadow: 0 0 20px rgba(0,255,136,.5), 0 0 60px rgba(0,255,136,.2);
    line-height: 1.1;
}

.logo span { color: var(--azul); }

.subtitulo {
    margin-top: 6px;
    font-size: .7rem;
    color: rgba(0,255,136,.4);
    letter-spacing: .2em;
}

/* ── Barra de vida (tentativas) ── */
.vida-container {
    margin-bottom: 20px;
}

.vida-label {
    font-size: .65rem;
    color: rgba(0,255,136,.5);
    letter-spacing: .15em;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
}

.vida-barras {
    display: flex;
    gap: 5px;
}

.barra {
    flex: 1;
    height: 8px;
    background: rgba(0,255,136,.1);
    border: 1px solid rgba(0,255,136,.2);
    transition: all .3s;
    position: relative;
    overflow: hidden;
}

.barra.usado {
    background: var(--vermelho);
    border-color: var(--vermelho);
    box-shadow: 0 0 8px rgba(255,51,85,.4);
}

.barra.usado::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.3) 50%, transparent 100%);
    animation: shimmer .8s ease forwards;
}

@keyframes shimmer {
    from { transform: translateX(-100%); }
    to   { transform: translateX(100%); }
}

/* ── Gauge do range ── */
.gauge {
    margin-bottom: 20px;
    background: rgba(0,255,136,.04);
    border: 1px solid var(--borda);
    padding: 12px 14px;
    font-size: .7rem;
}

.gauge-titulo {
    color: rgba(0,255,136,.4);
    letter-spacing: .15em;
    font-size: .6rem;
    margin-bottom: 8px;
}

.gauge-track {
    position: relative;
    height: 20px;
    background: rgba(0,255,136,.06);
    border: 1px solid rgba(0,255,136,.15);
}

.gauge-fill {
    height: 100%;
    background: linear-gradient(90deg, rgba(0,255,136,.15), rgba(0,170,255,.2));
    transition: width .4s ease;
}

.gauge-labels {
    display: flex;
    justify-content: space-between;
    font-size: .6rem;
    color: rgba(0,255,136,.4);
    margin-top: 4px;
}

/* ── Formulário ── */
.form-area { margin-bottom: 20px; }

.prompt-linha {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.prompt-sinal {
    color: var(--verde);
    font-size: 1rem;
    flex-shrink: 0;
    text-shadow: 0 0 8px var(--verde);
}

.input-numero {
    flex: 1;
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(0,255,136,.4);
    color: var(--verde);
    font-family: var(--mono);
    font-size: 1.2rem;
    padding: 4px 8px;
    outline: none;
    caret-color: var(--verde);
    transition: border-color .2s;
    width: 100%;
}

.input-numero:focus { border-color: var(--verde); box-shadow: 0 2px 0 rgba(0,255,136,.2); }
.input-numero:disabled { opacity: .35; cursor: not-allowed; }
.input-numero::placeholder { color: rgba(0,255,136,.2); }

.btn-row {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

.btn {
    flex: 1;
    padding: 10px;
    font-family: var(--mono);
    font-size: .75rem;
    letter-spacing: .1em;
    cursor: pointer;
    border: 1px solid;
    transition: all .15s;
    text-transform: uppercase;
    background: transparent;
}

.btn-executar {
    color: var(--verde);
    border-color: var(--verde);
}
.btn-executar:hover:not(:disabled) {
    background: rgba(0,255,136,.12);
    box-shadow: 0 0 12px rgba(0,255,136,.25);
}
.btn-executar:disabled { opacity: .3; cursor: not-allowed; }

.btn-reset {
    flex: 0 0 auto;
    padding: 10px 18px;
    color: rgba(0,255,136,.5);
    border-color: rgba(0,255,136,.2);
    font-size: .65rem;
}
.btn-reset:hover {
    color: var(--vermelho);
    border-color: var(--vermelho);
    background: rgba(255,51,85,.08);
}

/* ── Mensagem de status ── */
.status-msg {
    padding: 10px 14px;
    font-size: .8rem;
    border-left: 3px solid;
    margin-bottom: 20px;
    letter-spacing: .05em;
    animation: fadeIn .3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}

.status-msg.info    { border-color: var(--azul);    color: var(--azul);    background: rgba(0,170,255,.06); }
.status-msg.ganhou  { border-color: var(--verde);   color: var(--verde);   background: rgba(0,255,136,.08); box-shadow: 0 0 20px rgba(0,255,136,.1); }
.status-msg.perdeu  { border-color: var(--vermelho);color: var(--vermelho);background: rgba(255,51,85,.08); }
.status-msg.erro    { border-color: var(--amarelo); color: var(--amarelo); background: rgba(255,204,0,.06); }

/* ── Histórico ── */
.historico-titulo {
    font-size: .6rem;
    color: rgba(0,255,136,.3);
    letter-spacing: .2em;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--borda);
}

.historico-lista { display: flex; flex-direction: column; gap: 4px; }

.historico-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: .75rem;
    padding: 6px 10px;
    background: rgba(0,255,136,.02);
    border: 1px solid rgba(0,255,136,.06);
    animation: slideIn .25s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.h-num {
    font-family: var(--display);
    font-size: .85rem;
    font-weight: 700;
    min-width: 40px;
    text-align: right;
}

.h-dica { flex: 1; }

.historico-item.correto { border-color: rgba(0,255,136,.3); background: rgba(0,255,136,.06); }
.historico-item.correto .h-num  { color: var(--verde); }
.historico-item.correto .h-dica { color: var(--verde); }
.historico-item.quente  .h-num  { color: var(--laranja); }
.historico-item.quente  .h-dica { color: var(--laranja); }
.historico-item.morno   .h-num  { color: var(--amarelo); }
.historico-item.morno   .h-dica { color: var(--amarelo); }
.historico-item.frio    .h-num  { color: var(--azul); }
.historico-item.frio    .h-dica { color: rgba(0,170,255,.7); }

/* ── Footer ── */
.footer {
    margin-top: 28px;
    text-align: center;
    font-size: .6rem;
    color: rgba(0,255,136,.2);
    letter-spacing: .15em;
}

/* ── Responsivo ── */
@media (max-width: 480px) {
    .content { padding: 18px 16px 22px; }
    .logo { font-size: 1.3rem; }
}
</style>
</head>
<body>

<div class="terminal">
    <!-- Barra de título -->
    <div class="titlebar">
        <div class="dots">
            <span class="dot r"></span>
            <span class="dot y"></span>
            <span class="dot g"></span>
        </div>
        <span class="titlebar-text">SISTEMA DE DECRIPTAÇÃO v8.2 · SESSION:<?= substr(session_id(), 0, 8) ?></span>
    </div>

    <div class="content">

        <!-- Header -->
        <div class="header">
            <div class="logo">NÚMERO<br><span>SECRETO</span></div>
            <div class="subtitulo">PROTOCOLO DE QUEBRA DE CÓDIGO · NÍVEL I</div>
        </div>

        <!-- Barra de tentativas -->
        <div class="vida-container">
            <div class="vida-label">
                <span>TENTATIVAS USADAS</span>
                <span><?= $tentativas_usadas ?> / <?= MAX_TENTATIVAS ?></span>
            </div>
            <div class="vida-barras">
                <?php foreach ($barras as $b): ?>
                    <div class="barra <?= $b ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Gauge do range -->
        <div class="gauge">
            <div class="gauge-titulo">INTERVALO DE BUSCA</div>
            <div class="gauge-track">
                <div class="gauge-fill" style="width: 100%"></div>
            </div>
            <div class="gauge-labels">
                <span><?= MIN ?></span>
                <span>Adivinhe o número entre <?= MIN ?> e <?= MAX ?></span>
                <span><?= MAX ?></span>
            </div>
        </div>

        <!-- Mensagem de feedback -->
        <?php if ($mensagem): ?>
            <div class="status-msg <?= htmlspecialchars($classe_msg) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="form-area">
            <form method="POST" action="">
                <div class="prompt-linha">
                    <span class="prompt-sinal">&gt;_</span>
                    <input
                        type="number"
                        name="chute"
                        class="input-numero"
                        placeholder="Digite seu palpite..."
                        min="<?= MIN ?>"
                        max="<?= MAX ?>"
                        autofocus
                        <?= $status !== 'jogando' ? 'disabled' : '' ?>
                        autocomplete="off"
                    >
                </div>
                <div class="btn-row">
                    <button
                        type="submit"
                        class="btn btn-executar"
                        <?= $status !== 'jogando' ? 'disabled' : '' ?>
                    >[ EXECUTAR CHUTE ]</button>
                    <button type="submit" name="reset" value="1" class="btn btn-reset">↺ RESET</button>
                </div>
            </form>
        </div>

        <!-- Histórico -->
        <?php if (!empty($historico)): ?>
            <div class="historico-titulo">// LOG DE TENTATIVAS (mais recente primeiro)</div>
            <div class="historico-lista">
                <?php foreach ($historico as $i => $h): ?>
                    <div class="historico-item <?= htmlspecialchars($h['classe']) ?>">
                        <span class="h-num"><?= intval($h['valor']) ?></span>
                        <span class="h-dica"><?= htmlspecialchars($h['dica']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /content -->
</div><!-- /terminal -->

<div class="footer">
    PHP <?= PHP_VERSION ?> · Azure App Services · <?= date('Y') ?>
</div>

<script>
// Foco automático no input ao carregar
document.addEventListener('DOMContentLoaded', () => {
    const inp = document.querySelector('.input-numero:not([disabled])');
    if (inp) inp.focus();

    // Envio por Enter já funciona nativamente, mas garantimos submit no enter numérico
    inp?.addEventListener('keydown', e => {
        if (e.key === 'Enter') e.target.closest('form').submit();
    });
});
</script>

</body>
</html>
