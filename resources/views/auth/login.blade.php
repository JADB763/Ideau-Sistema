<x-layouts.auth titulo="Entrar">
<main class="auth-card">
    <div class="brand dark">◈ <span>campus<span class="brand-dot">.</span></span></div>
    <div class="eyebrow">GESTÃO ACADÊMICA</div>
    <h1>Bom ter você por aqui.</h1>
    <p>Entre para acompanhar estágios, documentos e TCCs.</p>
    @if ($errors->any())
        <div class="alert error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('login') }}" class="stack">
        @csrf
        <label>CPF<input type="text" name="cpf" required inputmode="numeric" autocomplete="username" maxlength="14" placeholder="000.000.000-00" value="{{ old('cpf') }}"></label>
        <label>Senha<input type="password" name="senha" required autocomplete="current-password" maxlength="72"></label>
        <button>Entrar no Campus →</button>
    </form>
    <p class="muted small">As contas são criadas pela coordenação.</p>
</main>
</x-layouts.auth>
