<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Emergency rollback to a previous deployed commit.
 *
 * Usage on production:
 *   php artisan deploy:rollback                # roll back one commit (HEAD~1)
 *   php artisan deploy:rollback --to=<sha>     # roll back to a specific commit
 *   php artisan deploy:rollback --list         # list last 10 deployed commits
 *
 * What it does:
 *   1. git fetch (refresh remote refs)
 *   2. git checkout <target> (detached HEAD — safe, no branch moved)
 *   3. composer install --no-dev --optimize-autoloader (lockfile sync)
 *   4. php artisan migrate:rollback --step=1 only if migrations ran on HEAD
 *      (skipped by default — pass --rollback-migrations to enable)
 *   5. php artisan optimize:clear && optimize
 *
 * Leaves the branch on main so the next `git pull` re-applies main
 * after you investigate. To *permanently* revert, push an actual
 * revert commit instead.
 *
 * Migrations are NOT rolled back by default because backward-incompatible
 * column drops are common — that's a manual decision.
 */
class RollbackCommand extends Command
{
    protected $signature = 'deploy:rollback
                            {--to= : Target commit SHA (default: HEAD~1)}
                            {--list : Show last 10 commits on the current branch}
                            {--rollback-migrations : Also run migrate:rollback --step=1}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Emergency rollback to a previous commit (detached HEAD, leaves main intact)';

    public function handle(): int
    {
        if (app()->environment('local')) {
            $this->warn('Running in local environment — remote production is unaffected.');
        }

        if ($this->option('list')) {
            return $this->listRecent();
        }

        // Abort if working tree is dirty (unexpected state on a cPanel host
        // usually means a manual edit — better to stop and investigate).
        $status = $this->runGit(['status', '--porcelain']);
        if (trim($status) !== '') {
            $this->error('Working tree is not clean — refusing to rollback.');
            $this->line('Uncommitted changes:');
            $this->line($status);
            return self::FAILURE;
        }

        $target = $this->option('to') ?: $this->runGit(['rev-parse', 'HEAD~1']);
        $target = trim($target);
        if (! preg_match('/^[0-9a-f]{7,40}$/', $target)) {
            $this->error("Refusing to rollback to '{$target}' — doesn't look like a commit SHA.");
            return self::FAILURE;
        }

        $currentSha = trim($this->runGit(['rev-parse', '--short', 'HEAD']));
        $targetShow = trim($this->runGit(['show', '--no-patch', '--format=%h %s', $target]));

        $this->line('');
        $this->warn('Rollback plan:');
        $this->line("  From: {$currentSha}");
        $this->line("  To:   {$targetShow}");
        $this->line('');

        if (! $this->option('force') && ! $this->confirm('Proceed with rollback?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->info('→ git fetch origin');
        $this->execShell('git fetch origin --prune');

        $this->info("→ git checkout {$target}");
        $this->execShell("git checkout {$target}");

        $this->info('→ composer install --no-dev --optimize-autoloader');
        $this->execShell('composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist');

        if ($this->option('rollback-migrations')) {
            $this->info('→ migrate:rollback --step=1');
            $this->call('migrate:rollback', ['--step' => 1, '--force' => true]);
        } else {
            $this->warn('Skipping migration rollback (use --rollback-migrations if the target needs it).');
        }

        $this->info('→ optimize:clear + optimize');
        $this->call('optimize:clear');
        $this->call('optimize');

        $this->info('');
        $this->info("✓ Rolled back to {$targetShow}");
        $this->warn('You are now on a detached HEAD. To return to main: git checkout main && git pull');

        return self::SUCCESS;
    }

    private function listRecent(): int
    {
        $log = $this->runGit(['log', '--oneline', '-n', '10']);
        $this->info('Recent commits:');
        $this->line($log);
        return self::SUCCESS;
    }

    private function runGit(array $args): string
    {
        $p = new Process(array_merge(['git'], $args), base_path());
        $p->run();
        return $p->isSuccessful() ? $p->getOutput() : $p->getErrorOutput();
    }

    private function execShell(string $cmd): void
    {
        $p = Process::fromShellCommandline($cmd, base_path());
        $p->setTimeout(300);
        $p->run(function ($type, $buffer) {
            $this->getOutput()->write($buffer);
        });
        if (! $p->isSuccessful()) {
            throw new \RuntimeException("Command failed: {$cmd}");
        }
    }
}
