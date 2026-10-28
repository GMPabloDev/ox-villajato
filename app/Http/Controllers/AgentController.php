<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreAgentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class AgentController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('panel/agents/create');
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated): void {
                $agent = new User([
                    'name' => (string) $validated['name'],
                    'email' => (string) $validated['email'],
                    'password' => Str::password(32),
                ]);

                $agent->role = UserRole::Agent;
                $agent->email_verified_at = now();

                $agent->save();

                // The invitation is sent inside the transaction so a failed send rolls back the account.
                $status = Password::sendResetLink(['email' => $agent->email]);

                if ($status !== Password::RESET_LINK_SENT) {
                    throw new RuntimeException('No se pudo enviar el enlace de invitación.');
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No pudimos crear la cuenta. Inténtalo de nuevo.',
            ]);

            return to_route('panel.agents.create');
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cuenta creada. Enviamos un enlace al correo del agente.',
        ]);

        return to_route('panel.agents.create');
    }
}
