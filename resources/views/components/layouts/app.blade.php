@props(['titulo' => 'Visão geral', 'pagina' => 'inicio'])
@php
    $usuario = auth()->user();
    // Itens aparecem conforme as rotas forem criadas nas próximas fases.
    $menu = array_filter([
        ['inicio', 'inicio', '⌂', 'Visão geral'],
        ['estagio', 'estagios.index', '▣', 'Estágios'],
        ['documento', 'documentos.index', '▤', 'Documentos'],
        ['tcc', 'tccs.index', '◇', 'TCC e defesa'],
        $usuario->isCoordenacao() ? ['cadastros', 'cadastros', '⊞', 'Cadastros'] : null,
        ['historico', 'historico', '◷', 'Histórico'],
    ], fn ($item) => $item && Route::has($item[1]));
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $titulo }} · Campus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
<aside class="sidebar" id="navigation">
    <a class="brand" href="{{ route('inicio') }}">◈ <span>campus<span class="brand-dot">.</span></span></a>
    <div class="workspace-label">GESTÃO ACADÊMICA</div>
    <nav aria-label="Navegação principal">
        @foreach ($menu as [$chave, $rota, $icone, $rotulo])
            <a href="{{ route($rota) }}" @class(['active' => $pagina === $chave])><span aria-hidden="true">{{ $icone }}</span>{{ $rotulo }}</a>
        @endforeach
    </nav>
    @if (Route::has('modelos.index'))
        <nav aria-label="Modelos"><a href="{{ route('modelos.index') }}" @class(['active' => $pagina === 'modelos'])><span aria-hidden="true">▧</span>Modelos prontos</a></nav>
    @endif
    <div class="sidebar-bottom"><span class="online-dot"></span> {{ $usuario->tipo_usuario->value }}<br><small>Seus processos, em um só lugar.</small></div>
</aside>
<div class="app-main">
    <header class="topbar">
        <button class="menu-button secondary" type="button" aria-controls="navigation" aria-expanded="false">☰ <span class="sr-only">Abrir menu</span></button>
        <span>Campus <span class="muted">/ {{ $titulo }}</span></span>
        <div class="user-menu">
            <a href="{{ route('conta') }}">{{ $usuario->nome_completo }}</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sair</button></form>
        </div>
    </header>
    <main class="content">
        @if (session('status'))
            <div class="alert success" role="status">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
    <footer class="app-footer">Campus · Gestão de estágios e TCC <span>IDEAU</span></footer>
</div>
</body>
</html>
