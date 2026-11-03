<?php

/*
| Mensagens de validação em português. Regras que não estiverem aqui usam o
| texto em inglês do framework (fallback_locale).
*/

return [

    'accepted' => 'O campo :attribute deve ser aceito.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'file' => 'O arquivo :attribute deve ter entre :min e :max kilobytes.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'current_password' => 'A senha atual não confere.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'distinct' => 'O campo :attribute tem um valor repetido.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'enum' => 'O valor selecionado em :attribute é inválido.',
    'exists' => 'O valor selecionado em :attribute é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'gte' => [
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
    ],
    'in' => 'O valor selecionado em :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'lt' => [
        'numeric' => 'O campo :attribute deve ser menor que :value.',
    ],
    'lte' => [
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'file' => 'O arquivo :attribute não pode ter mais que :max kilobytes.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
        'array' => 'O campo :attribute não pode ter mais que :max itens.',
    ],
    'mimes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'mimetypes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'file' => 'O arquivo :attribute deve ter no mínimo :min kilobytes.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
        'array' => 'O campo :attribute deve ter no mínimo :min itens.',
    ],
    'not_in' => 'O valor selecionado em :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'same' => 'Os campos :attribute e :other devem ser iguais.',
    'size' => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'file' => 'O arquivo :attribute deve ter :size kilobytes.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
        'array' => 'O campo :attribute deve ter :size itens.',
    ],
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Já existe um cadastro com este :attribute.',
    'uploaded' => 'Falha no envio do arquivo :attribute.',

    'attributes' => [
        'cpf' => 'CPF',
        'senha' => 'senha',
        'senha_atual' => 'senha atual',
        'nome_completo' => 'nome completo',
        'data_nascimento' => 'data de nascimento',
        'email' => 'e-mail',
    ],

];
