<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateInitialSuperadmin extends Command
{
    protected $signature = 'app:superadmin:create';

    protected $description = 'Crea el primer superadministrador mediante preguntas interactivas seguras';

    public function handle(): int
    {
        if (User::query()->where('rol', 'superadmin')->exists()) {
            $this->error('Ya existe un superadministrador. El aprovisionamiento inicial solo puede ejecutarse una vez.');

            return self::FAILURE;
        }

        $name = $this->ask('Nombre del superadministrador');
        $email = $this->ask('Correo del superadministrador');
        $validator = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmation = $this->secret('Confirma la contraseña');
        if (! is_string($password) || strlen($password) < 12 || ! hash_equals($password, (string) $confirmation)) {
            $this->error('La contraseña no cumple el mínimo o la confirmación no coincide.');

            return self::FAILURE;
        }

        $created = DB::transaction(function () use ($name, $email, $password): bool {
            if (User::query()->where('rol', 'superadmin')->exists()) {
                return false;
            }

            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'rol' => 'superadmin',
                'residente_id' => null,
                'activo' => true,
            ]);

            return true;
        });

        if (! $created) {
            $this->error('Ya existe un superadministrador. No se creó otra cuenta.');

            return self::FAILURE;
        }

        $this->info('Cuenta superadmin creada. La contraseña no se mostró ni se registró.');

        return self::SUCCESS;
    }
}
