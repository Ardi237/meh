<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('email', [
                'admin@celoe.test',
                'dosen@celoe.test',
                'mahasiswa@celoe.test',
            ])
            ->orderBy('id')
            ->each(function (object $user): void {
                $updates = [];

                if (Hash::check('password', $user->password)) {
                    $updates['password'] = Hash::make(Str::random(64));
                    $updates['remember_token'] = null;
                }

                if ($user->email_verified_at === null) {
                    $updates['email_verified_at'] = now();
                }

                if ($updates !== []) {
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
    }
};
