<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_unverified_user_cannot_access_application_routes(): void
    {
        $user = User::create([
            'name' => 'Belum Verifikasi',
            'email' => 'unverified@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666601',
            'password' => 'Secure123',
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/email/verify');

        $user->delete();
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::create([
            'name' => 'Password Test',
            'email' => 'password.test@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666602',
            'password' => 'OldPass123',
            'role' => 'mahasiswa',
        ]);
        $user->markEmailAsVerified();

        $response = $this->actingAs($user)->from('/profile')->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'nomor_induk' => $user->nomor_induk,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPass123', $user->fresh()->password));

        $user->delete();
    }

    public function test_nomor_induk_cannot_be_duplicated_from_profile(): void
    {
        $first = User::create([
            'name' => 'Identitas Pertama',
            'email' => 'identity.one@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666603',
            'password' => 'Secure123',
            'role' => 'mahasiswa',
        ]);
        $second = User::create([
            'name' => 'Identitas Kedua',
            'email' => 'identity.two@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666604',
            'password' => 'Secure123',
            'role' => 'mahasiswa',
        ]);
        $first->markEmailAsVerified();
        $second->markEmailAsVerified();

        $response = $this->actingAs($second)->from('/profile')->put('/profile', [
            'name' => $second->name,
            'email' => $second->email,
            'nomor_induk' => $first->nomor_induk,
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('nomor_induk');

        $first->delete();
        $second->delete();
    }

    public function test_sensitive_admin_change_is_audited(): void
    {
        $admin = User::where('email', 'admin@celoe.test')->firstOrFail();
        $target = User::create([
            'name' => 'Audit Target',
            'email' => 'audit.target@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666605',
            'password' => 'Secure123',
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$target->id}/role", ['role' => 'dosen'])
            ->assertRedirect();

        $this->assertTrue(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('event', 'updated')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->exists());

        $target->delete();
    }

    public function test_user_controlled_names_are_not_embedded_in_event_handlers(): void
    {
        $dosen = User::where('email', 'dosen@celoe.test')->firstOrFail();
        $student = User::create([
            'name' => "');alert(document.domain);//",
            'email' => 'xss.test@student.telkomuniversity.ac.id',
            'nomor_induk' => '1301666606',
            'password' => 'Secure123',
            'role' => 'mahasiswa',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'SEC-XSS',
            'nama_mk' => 'Security Test',
            'sks' => 2,
            'semester' => 'Ganjil 2026/2027',
            'dosen_id' => $dosen->id,
        ]);
        $course->students()->attach($student->id, ['status' => 'pending']);

        $this->actingAs($dosen)
            ->get("/courses/{$course->id}")
            ->assertOk()
            ->assertDontSee('onsubmit=', false);

        $course->students()->detach($student->id);
        $course->delete();
        $student->delete();
    }
}
