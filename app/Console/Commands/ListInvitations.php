<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ListInvitations extends Command
{
    protected $signature = 'invitation:list {--all : List used and expired invitations as well}';

    protected $description = 'List all invitations';

    public function handle(): int
    {
        $invitations = Invitation::query()
            ->with(['usedBy'])
            ->unless($this->option('all'), function (Builder $builder) {
                $builder->whereNull('used_at')->where(function (Builder $builder) {
                    $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            })
            ->latest('expires_at')
            ->get();

        if ($invitations->isEmpty()) {
            if ($this->option('all')) {
                $this->info('No invitations have been created yet');
            } else {
                $this->info('No valid invitations available');
            }

            return Command::SUCCESS;
        }

        $this->table(['Code', 'Valid', 'Expiration', 'Used', 'User'], $invitations->map(fn (Invitation $invitation) => [
            $invitation->code,
            $invitation->isValid() ? 'Yes' : 'No',
            $invitation->used_at ? '-' : ($invitation->expires_at?->format('d.m.Y H:i:s') ?: '-'),
            $invitation->used_at?->format('d.m.Y H:i:s') ?: '-',
            $invitation->usedBy?->email ?: '-',
        ]));

        return Command::SUCCESS;
    }
}
