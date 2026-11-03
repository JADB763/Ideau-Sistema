<x-layouts.app titulo="Minha conta" pagina="conta">
<div class="page-heading"><div><div class="eyebrow">{{ mb_strtoupper($usuario->tipo_usuario->value) }}</div><h1>Minha conta</h1><p>{{ $usuario->nome_completo }} · {{ $usuario->email }}</p></div></div>
@if ($errors->any())
    <div class="alert error" role="alert">{{ $errors->first() }}</div>
@endif
<section class="panel form-panel">
    <h2>Seus dados</h2>
    <dl class="details">
        <div><dt>CPF</dt><dd>{{ \App\Support\Cpf::formatar($usuario->cpf) }}</dd></div>
        <div><dt>Data de nascimento</dt><dd>{{ $usuario->data_nascimento->format('d/m/Y') }}</dd></div>
        @if ($usuario->matricula)
            <div><dt>Matrícula</dt><dd>{{ $usuario->matricula }}</dd></div>
        @endif
        @if ($usuario->curso)
            <div><dt>Curso</dt><dd>{{ $usuario->curso }}</dd></div>
        @endif
    </dl>
    <p class="muted small">Para corrigir seus dados, procure a coordenação.</p>
</section>
<section class="panel form-panel section-end">
    <h2>Alterar senha</h2>
    <form method="post" action="{{ route('conta.senha') }}" class="stack">
        @csrf
        @method('put')
        <label>Senha atual<input type="password" name="senha_atual" required autocomplete="current-password"></label>
        <label>Nova senha<input type="password" name="senha" required minlength="10" maxlength="72" autocomplete="new-password"></label>
        <label>Repetir nova senha<input type="password" name="senha_confirmation" required minlength="10" maxlength="72" autocomplete="new-password"></label>
        <button>Atualizar senha</button>
    </form>
</section>
</x-layouts.app>
