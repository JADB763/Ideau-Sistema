<?php

namespace App\Http\Requests\Auth;

use App\Support\Cpf;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Tentativas permitidas por CPF + IP dentro da janela.
     */
    private const MAX_TENTATIVAS = 10;

    private const JANELA_SEGUNDOS = 900;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string'],
            'senha' => ['required', 'string', 'max:72'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cpf' => Cpf::normalizar($this->input('cpf'))]);
    }

    /**
     * Autentica por CPF e senha. Contas inativas recebem a mesma mensagem de credenciais erradas.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credenciais = ['cpf' => $this->string('cpf')->value(), 'password' => $this->string('senha')->value(), 'ativo' => true];

        if (! Auth::attempt($credenciais)) {
            RateLimiter::hit($this->throttleKey(), self::JANELA_SEGUNDOS);

            throw ValidationException::withMessages([
                'cpf' => 'CPF ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_TENTATIVAS)) {
            return;
        }

        event(new Lockout($this));

        $minutos = (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60);

        throw ValidationException::withMessages([
            'cpf' => "Muitas tentativas. Aguarde {$minutos} minuto(s) antes de tentar novamente.",
        ]);
    }

    public function throttleKey(): string
    {
        return $this->string('cpf')->value().'|'.$this->ip();
    }
}
