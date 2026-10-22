<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuestImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_with_a_utf8_bom_imports_guests(): void
    {
        [$user, $event] = $this->event();

        $csv = "\xEF\xBB\xBFFull Name,Phone Number,Amount Contributed,Card Type,Email,Table Number,Category,Notes\n"
            ."Jane Doe,0712345678,\"1,000,000\",double,jane@example.com,Table 1,Family,VIP\n"
            ."John Doe,0767654321,50000,single,,,,\n";

        $file = $this->uploadedCsv($csv);

        $this->actingAs($user)
            ->post(route('events.guests.import.store', $event), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guest = Guest::where('full_name', 'Jane Doe')->firstOrFail();

        $this->assertSame('0712345678', $guest->phone_number);
        $this->assertSame('double', $guest->card_type);
        $this->assertSame(1000000.0, (float) $guest->amount_contributed);
        $this->assertSame(2, $event->guests()->count());
    }

    public function test_semicolon_separated_csv_is_supported(): void
    {
        [$user, $event] = $this->event();

        $csv = "Full Name;Phone Number;Amount Contributed\nJane Doe;0712345678;250000\n";

        $this->actingAs($user)
            ->post(route('events.guests.import.store', $event), ['file' => $this->uploadedCsv($csv)])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('guests', [
            'event_id' => $event->id,
            'full_name' => 'Jane Doe',
        ]);
    }

    public function test_paid_amount_is_imported_and_saved_as_payment_history(): void
    {
        [$user, $event] = $this->event();

        $csv = "Full Name,Phone Number,Pledged Amount,Amount Paid\n"
            ."Jane Doe,0712345678,100000,25000\n";

        $this->actingAs($user)
            ->post(route('events.guests.import.store', $event), ['file' => $this->uploadedCsv($csv)])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guest = Guest::where('full_name', 'Jane Doe')->firstOrFail();

        $this->assertEquals(100000, (float) $guest->amount_contributed);
        $this->assertEquals(25000, (float) $guest->paid_amount);
        $this->assertSame('partial', $guest->payment_status);
        $this->assertDatabaseHas('guest_payments', [
            'guest_id' => $guest->id,
            'amount' => 25000,
            'method' => 'import',
        ]);
    }

    public function test_guests_can_share_the_same_phone_number(): void
    {
        [$user, $event] = $this->event();

        $csv = "Full Name,Phone Number\n"
            ."Eng Nassoro Masiku,255750960863\n"
            ."Mr & Mrs Masiku,255750960863\n";

        $this->actingAs($user)
            ->post(route('events.guests.import.store', $event), ['file' => $this->uploadedCsv($csv)])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, $event->guests()->where('phone_number', '255750960863')->count());
    }

    public function test_reimporting_the_same_guest_is_skipped(): void
    {
        [$user, $event] = $this->event();

        $csv = "Full Name,Phone Number\nJane Doe,0712345678\n";

        $this->actingAs($user)->post(
            route('events.guests.import.store', $event),
            ['file' => $this->uploadedCsv($csv)]
        );

        $this->actingAs($user)
            ->post(route('events.guests.import.store', $event), ['file' => $this->uploadedCsv($csv)])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertSame(1, $event->guests()->count());
    }

    private function uploadedCsv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'guests_');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'guests.csv', 'text/csv', null, true);
    }

    private function event(): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host@example.com',
            'password' => bcrypt('password123'),
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'slug' => 'wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        return [$user, $event];
    }
}
