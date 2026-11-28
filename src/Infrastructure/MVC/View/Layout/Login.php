<?php

namespace Framework\Infrastructure\MVC\View\Layout;

/**
 * Tela de login do Oasys ERP. Segue o login do Oasys Central (painel da marca em 2/3 e formulário num
 * cartão no 1/3 restante) com o tema do ERP das telas de login da plataforma. Fontes e imagens vêm de
 * /assets/central do ERP. Sem recuperação de senha de propósito.
 */
class Login implements ILayout
{
    public function render($aData = [])
    {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <?php
        $this->renderHead();
        $this->renderBody(isset($aData['bUnauthorized']), (string) ($aData['sUsuario'] ?? ''));
        ?></html><?php
    }

    public function renderHead()
    {
        ?>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
            <meta name="robots" content="noindex">
            <meta name="theme-color" content="#0066CC">
            <title>Entrar · Oasys ERP</title>
            <link rel="icon" href="/assets/icon.png" sizes="512x512" type="image/png">
            <link rel="preload" href="/assets/central/fonts/inter-400.woff2" as="font" type="font/woff2" crossorigin>
            <link rel="preload" href="/assets/central/fonts/montserrat-800.woff2" as="font" type="font/woff2" crossorigin>
        <?php
        $this->renderStyle();
        ?></head><?php
    }

    public function renderStyle()
    {
        ?>
        <style>
            html, body { margin: 0; height: 100%; }
            /* ==========================================================================
               Tela de login da plataforma Oasys (ERP, Chat, Finance e Sales App).
               Mesma estrutura do login do Oasys Central: painel da marca em 2/3 da tela e o
               formulário num cartão flutuante no 1/3 restante; no celular, painel compacto
               no topo e o formulário numa folha arredondada. O tema de cada produto vem da
               classe no contêiner (.login--erp, .login--chat, .login--finance, .login--sales).
               ========================================================================== */
            @font-face { font-family: "Inter"; src: url("/assets/central/fonts/inter-400.woff2") format("woff2"); font-weight: 400; font-display: swap; }
            @font-face { font-family: "Inter"; src: url("/assets/central/fonts/inter-500.woff2") format("woff2"); font-weight: 500; font-display: swap; }
            @font-face { font-family: "Inter"; src: url("/assets/central/fonts/inter-600.woff2") format("woff2"); font-weight: 600 700; font-display: swap; }
            @font-face { font-family: "Montserrat"; src: url("/assets/central/fonts/montserrat-700.woff2") format("woff2"); font-weight: 700; font-display: swap; }
            @font-face { font-family: "Montserrat"; src: url("/assets/central/fonts/montserrat-800.woff2") format("woff2"); font-weight: 800; font-display: swap; }

            .login {
              --noite: #0a2540; --profundo: #005182; --azul-oasys: #0094e9; --ceu: #30bbfd; --texto-azul: #0074b8;
              --cinza: #f1f5f9; --borda: #cbd5e1; --suave: #475569;
              min-height: 100vh; min-height: 100dvh; display: flex; background: var(--painel);
              color: var(--noite); font-family: "Inter", "Segoe UI", system-ui, sans-serif; font-size: 16px; line-height: 1.5;
              -webkit-font-smoothing: antialiased;
            }
            .login *, .login *::before, .login *::after { box-sizing: border-box; }
            .login h1, .login h2 { margin: 0; font-family: "Montserrat", "Segoe UI", system-ui, sans-serif; }
            .login p { margin: 0; }
            .login img { display: block; max-width: none; }
            .login :focus-visible { outline: 3px solid var(--anel-foco); outline-offset: 2px; }
            .login [hidden] { display: none !important; }

            /* ---------- painel da marca ---------- */
            .login__marca { position: relative; overflow: hidden; flex: 2 1 0; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; gap: 40px;
              padding: 48px clamp(32px, 5vw, 72px); color: #fff; background: var(--painel); }
            .login__arvore { position: absolute; right: -300px; bottom: -60px; width: 820px; opacity: .07; pointer-events: none; user-select: none; }
            .login__marca > *:not(.login__arvore) { position: relative; }
            .login__logo { height: 36px; width: auto; align-self: flex-start; }
            .login__produto { display: flex; align-items: center; gap: 14px; }
            .login__produto img { width: 48px; height: 48px; border-radius: 10px; box-shadow: 0 0 0 2px rgba(255, 255, 255, .35); }
            .login__produto span { font-family: "Montserrat", sans-serif; font-weight: 800; font-size: 1.5rem; letter-spacing: -.02em; }
            .login__mensagem { display: flex; flex-direction: column; gap: 16px; max-width: 34rem; }
            .login__barra { width: 56px; height: 4px; border-radius: 2px; background: var(--barra); }
            .login__mensagem h2 { color: #fff; font-weight: 800; font-size: clamp(2rem, 3.4vw, 3rem); line-height: 1.1; letter-spacing: -.02em; }
            .login__mensagem p { color: var(--painel-texto); font-size: 1.1rem; line-height: 1.55; }
            .login__rodape { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; font-size: .95rem; color: var(--painel-texto); }
            .login__apps { display: flex; gap: 10px; }
            .login__apps img { width: 44px; height: 44px; border-radius: 10px; box-shadow: 0 6px 16px -6px rgba(0, 0, 0, .45); }
            .login__rodape-logo { height: 20px; width: auto; }

            /* ---------- formulário ---------- */
            .login__acesso { flex: 1 1 0; min-width: 440px; display: flex; align-items: center; justify-content: center; padding: 40px 24px; background: var(--cinza); }
            .login__cartao { width: 100%; max-width: 400px; background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 36px 32px 24px;
              box-shadow: 0 24px 48px -20px rgba(10, 37, 64, .28); display: flex; flex-direction: column; gap: 22px; }
            .login__cabeca h1 { font-size: 1.9rem; font-weight: 800; color: var(--noite); letter-spacing: -.02em; line-height: 1.2; }
            .login__cabeca p { margin-top: 6px; color: var(--suave); }
            .login__erro { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; background: #fff4ec; border: 1px solid #f6c9a6; color: #9a3e0b; font-size: .92rem; font-weight: 500; line-height: 1.45; }
            .login__erro svg { flex: 0 0 auto; margin-top: 1px; }
            .login__form { display: flex; flex-direction: column; gap: 18px; }
            .login__campo { display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: .92rem; color: var(--noite); }
            .login__campo input { width: 100%; min-height: 50px; padding: 12px 14px; border: 1.5px solid var(--borda); border-radius: 10px; background: #fff; color: var(--noite);
              font: 400 16px/1.3 "Inter", "Segoe UI", system-ui, sans-serif; transition: border-color .15s, box-shadow .15s; }
            .login__campo input::placeholder { color: #8a99ab; }
            .login__campo input:focus { outline: none; border-color: var(--foco); box-shadow: 0 0 0 3px var(--anel); }
            .login__senha { position: relative; }
            .login__senha input { padding-right: 52px; }
            .login__senha button { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; border: 0; background: none; color: var(--suave);
              border-radius: 8px; cursor: pointer; display: grid; place-items: center; padding: 0; }
            .login__senha button:hover { color: var(--noite); }
            .login__icone { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
            .login__botao { width: 100%; min-height: 52px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 12px;
              background: var(--botao); color: #fff; font: 700 1rem "Montserrat", "Segoe UI", system-ui, sans-serif; cursor: pointer; transition: background .15s; }
            .login__botao:hover { background: var(--botao-hover); }
            .login__botao:disabled { opacity: .75; cursor: progress; }
            .login__botao svg { animation: login-gira .9s linear infinite; }
            @keyframes login-gira { to { transform: rotate(360deg); } }
            .login__links { display: flex; flex-direction: column; align-items: center; gap: 10px; font-size: .95rem; color: var(--suave); text-align: center; }
            .login__links a { color: var(--link); font-weight: 600; text-decoration: none; }
            .login__links a:hover { text-decoration: underline; }
            .login__seguro { display: flex; align-items: center; justify-content: center; gap: 6px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: .85rem; color: #64748b; }
            .login__seguro .login__icone { width: 15px; height: 15px; }

            /* ---------- celular ---------- */
            @media (max-width: 860px) {
              .login { flex-direction: column; }
              .login__marca { flex: 0 0 auto; gap: 18px; padding: 36px max(24px, env(safe-area-inset-left, 0px)) 56px; }
              .login__arvore { right: -170px; bottom: -20px; width: 360px; }
              .login__logo { height: 28px; }
              .login__produto img { width: 40px; height: 40px; border-radius: 9px; }
              .login__produto span { font-size: 1.25rem; }
              .login__mensagem { gap: 10px; }
              .login__mensagem h2 { font-size: 1.7rem; }
              .login__mensagem p { font-size: 1rem; }
              .login__barra, .login__rodape { display: none; }
              .login__acesso { position: relative; flex: 1 0 auto; width: 100%; min-width: 0; align-items: flex-start; margin-top: -24px; border-radius: 24px 24px 0 0;
                padding: 28px max(16px, env(safe-area-inset-left, 0px)) 40px; background: #fff; }
              .login__cartao { border: 0; box-shadow: none; padding: 8px 8px 0; background: transparent; }
            }
            @media (prefers-reduced-motion: reduce) {
              .login * { transition: none !important; }
              .login__botao svg { animation-duration: 2s; }
            }

            /* ---------- temas por produto (cores das telas aprovadas) ---------- */
            .login--erp {
              --painel: #0066cc; --painel-texto: #e6f2ff; --barra: #30bbfd;
              --botao: #0066cc; --botao-hover: #004b99; --link: #0066cc;
              --foco: #0066cc; --anel: rgba(0, 102, 204, .18); --anel-foco: rgba(0, 102, 204, .45);
            }
            .login--chat {
              --painel: #0094e9; --painel-texto: #eaf6fe; --barra: #fff;
              --botao: #0074b8; --botao-hover: #005182; --link: #0074b8;
              --foco: #0074b8; --anel: rgba(0, 116, 184, .18); --anel-foco: rgba(0, 116, 184, .45);
            }
            .login--finance {
              --painel: #0a2540; --painel-texto: #d3e0ec; --barra: #30bbfd;
              --botao: #0074b8; --botao-hover: #005182; --link: #0074b8;
              --foco: #0094e9; --anel: rgba(0, 148, 233, .2); --anel-foco: rgba(0, 148, 233, .45);
            }
            .login--sales {
              --painel: #d2691e; --painel-texto: #fff1e6; --barra: #fff;
              --botao: #b4570f; --botao-hover: #8f440b; --link: #a84f0c;
              --foco: #b4570f; --anel: rgba(180, 87, 15, .18); --anel-foco: rgba(180, 87, 15, .45);
            }
        </style>
        <?php
    }

    public function renderBody($bUnauthorized, string $sUsuario = '')
    {
        $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        ?>
        <body>
            <div class="login login--erp">
                <section class="login__marca" aria-label="Oasys ERP">
                    <img class="login__arvore" src="/assets/central/img/arvore-marca-dagua.svg" alt="" aria-hidden="true">
                    <img class="login__logo" src="/assets/central/img/oasys-horizontal-branco.svg" alt="Oasys" width="168" height="36">
                    <div class="login__mensagem">
                        <div class="login__barra" aria-hidden="true"></div>
                        <h2>Tudo sob controle, do pedido ao caixa.</h2>
                        <p>Gestão completa para distribuição e comércio.</p>
                    </div>
                    <div class="login__rodape">
                        <div class="login__apps">
                            <img src="/assets/central/img/oasys-chat.svg" alt="Oasys Chat" width="44" height="44">
                            <img src="/assets/central/img/oasys-finance.svg" alt="Oasys Finance" width="44" height="44">
                            <img src="/assets/central/img/oasys-sales-app.svg" alt="Sales App" width="44" height="44">
                        </div>
                        <span>Um acesso para toda a plataforma</span>
                    </div>
                </section>
                <main class="login__acesso">
                    <div class="login__cartao">
                        <header class="login__cabeca">
                            <h1>Entrar</h1>
                            <p>Acesse sua conta Oasys com e-mail e senha.</p>
                        </header>
                        <?php if ($bUnauthorized): ?>
                            <div class="login__erro" role="alert">
                                <svg class="login__icone" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                <span>E-mail ou senha incorretos. Confira os dados e tente novamente.</span>
                            </div>
                        <?php endif; ?>
                        <form class="login__form" action="index.php" method="POST" data-login>
                            <label class="login__campo" for="login-email">E-mail
                                <input id="login-email" type="email" name="usuario" inputmode="email" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="voce@empresa.com.br" required <?= $sUsuario === '' ? 'autofocus' : '' ?> value="<?= $e($sUsuario) ?>"></label>
                            <div class="login__campo">
                                <label for="login-senha">Senha</label>
                                <div class="login__senha">
                                    <input id="login-senha" type="password" name="senha" autocomplete="current-password" placeholder="Sua senha" required <?= $sUsuario !== '' ? 'autofocus' : '' ?>>
                                    <button type="button" data-mostrar-senha aria-controls="login-senha" aria-pressed="false" aria-label="Mostrar senha">
                                        <svg class="login__icone" data-olho viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="login__icone" data-olho hidden viewBox="0 0 24 24" aria-hidden="true"><path d="M10.7 5.1A10 10 0 0 1 22 12a13 13 0 0 1-1.7 2.7M6.6 6.6A13.5 13.5 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6M2 2l20 20M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                    </button>
                                </div>
                            </div>
                            <button class="login__botao" type="submit">Entrar</button>
                        </form>
                        <div class="login__links">
                            <span>Ainda não tem acesso? <a href="https://wa.me/554731804931" target="_blank" rel="noopener">Fale com a Oasys</a></span>
                        </div>
                        <p class="login__seguro">
                            <svg class="login__icone" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Conexão segura · © <?= date('Y') ?> Oasys Tecnologia
                        </p>
                    </div>
                </main>
            </div>
            <script>
                // Mostrar ou ocultar a senha.
                document.querySelectorAll('[data-mostrar-senha]').forEach(function (botao) {
                    botao.addEventListener('click', function () {
                        var campo = document.getElementById(botao.getAttribute('aria-controls'));
                        var mostrar = campo.type === 'password';
                        campo.type = mostrar ? 'text' : 'password';
                        botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
                        botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
                        botao.querySelectorAll('[data-olho]').forEach(function (icone) { icone.hidden = !icone.hidden; });
                        campo.focus();
                    });
                });
                // Durante o envio, o botão fica desabilitado com "Entrando…" (evita duplo envio).
                document.querySelectorAll('[data-login]').forEach(function (form) {
                    form.addEventListener('submit', function () {
                        var botao = form.querySelector('[type="submit"]');
                        botao.disabled = true;
                        botao.innerHTML = '<svg class="login__icone" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.6"/></svg>Entrando…';
                    });
                });
            </script>
        </body>
        <?php
    }
}
