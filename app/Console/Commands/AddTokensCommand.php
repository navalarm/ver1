<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserToken;
use Illuminate\Console\Command;

class AddTokensCommand extends Command
{
    protected $signature = 'tokens:add {email} {amount}';
    protected $description = 'Add tokens to a user account';

    public function handle()
    {
        $email = $this->argument('email');
        $amount = (int) $this->argument('amount');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $userToken = UserToken::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $userToken->increment('balance', $amount);

        $this->info("Added {$amount} tokens to {$email}. New balance: {$userToken->fresh()->balance}");

        return 0;
    }
}
