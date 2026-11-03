<x-layouts.app titulo="Visão geral" pagina="inicio">
<div class="page-heading"><div><div class="eyebrow">SEU PAINEL ACADÊMICO</div><h1>Olá, {{ strtok(auth()->user()->nome_completo, ' ') }}.</h1><p>Acompanhe cada etapa. Deixe a papelada organizada.</p></div></div>
<section class="welcome-card">
    <div>
        <span class="eyebrow">TUDO CONECTADO</span>
        <h2>Da primeira orientação<br>à última assinatura.</h2>
        <p>Cadastros, documentos e solicitações<br>em um único espaço de acompanhamento.</p>
    </div>
    <div class="welcome-art" aria-hidden="true"><div class="paper paper-back"></div><div class="paper"><span>campus.</span><i></i><i></i><i></i><b>✓</b></div></div>
</section>
</x-layouts.app>
