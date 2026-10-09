<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Google OAuth tokens are now stored with Laravel's `encrypted` cast. Encrypt the tokens that were
 * saved as plain text before; values that already decrypt are left alone, so this is safe to rerun.
 */
return new class extends Migration
{
    private const COLUMNS = ['google_access_token', 'google_refresh_token'];

    public function up(): void
    {
        $this->transform(function (string $value): string {
            try {
                Crypt::decryptString($value);

                return $value;
            } catch (DecryptException) {
                return Crypt::encryptString($value);
            }
        });
    }

    public function down(): void
    {
        $this->transform(function (string $value): string {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $value;
            }
        });
    }

    private function transform(Closure $transform): void
    {
        $users = DB::table('users')
            ->where(fn ($query) => $query->whereNotNull(self::COLUMNS[0])->orWhereNotNull(self::COLUMNS[1]))
            ->get(['id', ...self::COLUMNS]);

        foreach ($users as $user) {
            $changes = [];

            foreach (self::COLUMNS as $column) {
                if ($user->{$column} !== null) {
                    $changes[$column] = $transform($user->{$column});
                }
            }

            DB::table('users')->where('id', $user->id)->update($changes);
        }
    }
};
