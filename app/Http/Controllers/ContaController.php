<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContaController extends Controller
{
    public function edit(Request $request): View
    {
        return view('conta', ['usuario' => $request->user()]);
    }

    /**
     * Troca a senha. O middleware auth.session encerra as outras sessões da conta,
     * porque o hash guardado nelas deixa de conferir.
     */
    public function updateSenha(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'senha_atual' => ['required', 'string', 'current_password'],
            'senha' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        $request->user()->update(['senha' => $dados['senha']]);

        $request->session()->regenerate();

        return redirect()->route('conta')->with('status', 'Senha atualizada. As outras sessões foram encerradas.');
    }
}
