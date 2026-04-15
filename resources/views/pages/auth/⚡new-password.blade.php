<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;

new class extends Component {

    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $success = false;
    public bool $tokenValid = true;
    public bool $showPassword = false;
    public bool $showConfirmation = false;

    public function mount(): void
    {
        $this->token = request()->query('token', '');
        $this->email = request()->query('email', '');

        if (empty($this->token) || empty($this->email)) {
            $this->tokenValid = false;
            return;
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $this->email)
            ->first();

        if (! $record || ! Hash::check($this->token, $record->token)) {
            $this->tokenValid = false;
        }
    }

    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function toggleConfirmation(): void
    {
        $this->showConfirmation = ! $this->showConfirmation;
    }

    public function submit(): void
    {
        $this->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.required'              => 'A senha é obrigatória.',
            'password.min'                   => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'             => 'As senhas não coincidem.',
            'password_confirmation.required' => 'Confirme a sua senha.',
        ]);

        $status = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->success = true;
        } else {
            $this->addError('password', __($status));
        }
    }

}; ?>

<div class="min-h-screen flex items-center justify-center bg-300-gray px-4 py-12 relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full bg-[#00c896] opacity-[0.06] blur-[120px]"></div>
        <div class="absolute -bottom-32 -right-32 w-[400px] h-[400px] rounded-full bg-[#0ea5e9] opacity-[0.07] blur-[100px]"></div>
    </div>

    <div class="relative w-full max-w-md">
        <div class="rounded-2xl border border-white/10 bg-white backdrop-blur-xl shadow-2xl shadow-black/40 px-8 py-10">
            <div class="mb-8 text-center">
                <span class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    FinanceFlow
                </span>
            </div>

            {{-- Token inválido --}}
            @if (! $tokenValid)
                <div class="text-center space-y-5">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 border border-red-500/30">
                        <i class="scaletext-red-500 fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h2 class="text-xl font-semibold" style="font-family:'Sora',sans-serif;">
                        Link inválido ou expirado
                    </h2>
                    <p class="text-sm text-gray-900 leading-relaxed">
                        Este link de redefinição não é mais válido.<br>Solicite um novo para continuar.
                    </p>
                    <a href="{{ route('reset-password') }}"
                       class="inline-block w-full rounded-xl bg-[#00c896] py-3 text-sm font-semibold text-[#0d0f14] text-center transition hover:bg-[#00b386] active:scale-[.98]">
                        Solicitar novo link
                    </a>
                </div>

            {{-- Sucesso --}}
            @elseif ($success)
                <div class="text-center space-y-5">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00c896]/10 border border-[#00c896]/30">
                        <svg class="w-7 h-7 text-[#00c896]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-white" style="font-family:'Sora',sans-serif;">
                        Senha redefinida!
                    </h2>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Sua senha foi alterada com sucesso.<br>Acesse sua conta com as novas credenciais.
                    </p>
                    <a href="{{ route('login') }}"
                       class="inline-block w-full rounded-xl bg-[#00c896] py-3 text-sm font-semibold text-[#0d0f14] text-center transition hover:bg-[#00b386] active:scale-[.98]">
                        Ir para o login
                    </a>
                </div>

            {{-- Formulário --}}
            @else
                <div class="mb-7">
                    <h1 class="text-2xl font-bold text-white" style="font-family:'Sora',sans-serif;">Nova senha</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Definindo senha para <span class="text-[#00c896] font-medium">{{ $email }}</span>
                    </p>
                </div>

                <form wire:submit.prevent="submit" novalidate class="space-y-5">

                    {{-- Nova senha --}}
                    <div>
                        <label for="password" class="block mb-1.5 text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Nova senha
                        </label>
                        <div class="relative">
                            <input wire:model.defer="password"
                                   id="password"
                                   type="{{ $showPassword ? 'text' : 'password' }}"
                                   autocomplete="new-password"
                                   placeholder="Mínimo 8 caracteres"
                                   class="w-full rounded-xl border bg-white/5 px-4 py-3 pr-12 text-sm text-white placeholder-gray-600 outline-none transition focus:ring-2 focus:ring-[#00c896]/50 @error('password') border-red-500/60 @else border-white/10 focus:border-[#00c896]/50 @enderror">
                            <button type="button" wire:click="togglePassword" tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                @if ($showPassword)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 flex items-center gap-1 text-xs text-red-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Confirmar senha --}}
                    <div>
                        <label for="password_confirmation" class="block mb-1.5 text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Confirmar senha
                        </label>
                        <div class="relative">
                            <input wire:model.defer="password_confirmation"
                                   id="password_confirmation"
                                   type="{{ $showConfirmation ? 'text' : 'password' }}"
                                   autocomplete="new-password"
                                   placeholder="Repita a senha"
                                   class="w-full rounded-xl border bg-white/5 px-4 py-3 pr-12 text-sm text-white placeholder-gray-600 outline-none transition focus:ring-2 focus:ring-[#00c896]/50 @error('password_confirmation') border-red-500/60 @else border-white/10 focus:border-[#00c896]/50 @enderror">
                            <button type="button" wire:click="toggleConfirmation" tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                @if ($showConfirmation)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="mt-1.5 flex items-center gap-1 text-xs text-red-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Dicas de senha --}}
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-500">
                        <li class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-gray-600"></span>Mínimo 8 caracteres</li>
                        <li class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-gray-600"></span>Maiúsculas e minúsculas</li>
                        <li class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-gray-600"></span>Ao menos um número</li>
                        <li class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-gray-600"></span>Ao menos um símbolo</li>
                    </ul>

                    {{-- Submit --}}
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-[#00c896] py-3 text-sm font-semibold text-[#0d0f14] transition hover:bg-[#00b386] active:scale-[.98] disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="submit">Redefinir senha</span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Aguarde...
                        </span>
                    </button>

                    <p class="text-center text-sm text-gray-500">
                        Lembrou a senha?
                        <a href="{{ route('login') }}" class="text-[#00c896] font-medium hover:underline">Entrar</a>
                    </p>

                </form>
            @endif

        </div>
    </div>

</div>