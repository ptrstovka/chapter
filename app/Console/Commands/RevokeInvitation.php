<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RevokeInvitation extends Command
{
    protected $signature = 'invitation:revoke {code : Invitation code}';

    protected $description = 'Revoke given invitation';

    public function handle(): int
    {
        $invitation = Invitation::query()->where('code', Str::lower($this->argument('code')))->first();

        if (! $invitation) {
            $this->fail('The code is not valid');
        }

        $invitation->delete();

        $this->info('Invitation revoked');

        return Command::SUCCESS;
    }
}
