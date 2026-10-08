<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create a production administrator interactively';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Full name'), 'username' => $this->ask('Admin ID'), 'email' => $this->ask('Email'), 'password' => $this->secret('Password (10+ characters, letters and numbers)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:100', 'username' => 'required|alpha_dash:ascii|min:3|max:50|unique:users', 'email' => 'required|email|unique:users', 'password' => ['required', Password::min(10)->letters()->numbers()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        User::create($data + ['role' => 'admin', 'active' => true, 'locale' => 'en']);
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
