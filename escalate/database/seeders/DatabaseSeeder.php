<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * Deliberately empty.
         *
         * The stock seeder creates test@example.com with the password
         * "password", and Database\Factories is in composer's non-dev
         * autoload block — so `php artisan migrate --seed` works on a
         * production host and plants a known-credential account with a full
         * journal behind it. There is nothing this app needs seeded; make an
         * account through /register and grant admin with escalate:make-admin.
         */
        if (app()->isProduction()) {
            return;
        }
    }
}
