<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateInvitation extends Command
{
    protected $signature = 'invitation:create {--expiration= : Number of hours from now, when the invitation code expires}';

    protected $description = 'Create invitation code';

    public function handle(): int
    {
        $code = Str::lower(Str::random(12));

        $expiresAt = null;

        if ($expiration = $this->option('expiration')) {
            if (is_numeric($expiration) && $expiration > 0) {
                $expiresAt = now()->addHours((int) $expiration);
            } else {
                $this->fail('The expiration option must be positive number');
            }
        }

        Invitation::create([
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $this->info("Invitation created: {$code}");

        return Command::SUCCESS;
    }
}
