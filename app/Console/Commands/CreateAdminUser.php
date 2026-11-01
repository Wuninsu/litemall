<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-admin-user {--name= : Full name} {--email= : Email address} {--password= : Password (prompted securely if omitted)}')]
#[Description('Create an administrator account (use this to bootstrap a new installation)')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $data = [
            'name' => $this->option('name') ?? $this->ask('Name'),
            'email' => strtolower(trim((string) ($this->option('email') ?? $this->ask('Email')))),
            'password' => $this->option('password') ?? $this->secret('Password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $userService->create([...$validator->validated(), 'role' => UserRole::Admin]);

        $this->info("Administrator {$user->email} created.");

        return self::SUCCESS;
    }
}
