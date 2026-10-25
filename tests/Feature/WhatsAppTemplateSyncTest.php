<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.api_url' => 'https://graph.facebook.com/v25.0',
            'services.whatsapp.phone_number_id' => '1317524134784685',
            'services.whatsapp.business_account_id' => '1088196013963166',
            'services.whatsapp.access_token' => 'test-meta-token',
            'services.whatsapp.template_name' => 'wedding_invitation',
            'services.whatsapp.template_language' => 'en_US',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
            'can_send_whatsapp' => true,
        ]);

        $this->user = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
            'can_send_whatsapp' => true,
        ]);
    }

    public function test_whatsapp_service_syncs_templates_from_meta_api(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/1088196013963166/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => '1088196013963166',
                        'name' => 'wedding_invitation',
                        'language' => 'en_US',
                        'status' => 'APPROVED',
                        'category' => 'UTILITY',
                        'components' => [
                            [
                                'type' => 'HEADER',
                                'format' => 'IMAGE',
                            ],
                            [
                                'type' => 'BODY',
                                'text' => 'Hello {{1}}, you are invited! Guest ID: {{2}}, Date: {{3}}, Link: {{4}}',
                            ],
                        ],
                    ],
                    [
                        'id' => '2088196013963177',
                        'name' => 'reminder_sw',
                        'language' => 'sw',
                        'status' => 'APPROVED',
                        'category' => 'UTILITY',
                        'components' => [
                            [
                                'type' => 'BODY',
                                'text' => 'Habari {{1}}, kumbukumbu ya {{2}} kwa {{3}}. Malipo: {{4}}',
                            ],
                        ],
                    ],
                ],
                'paging' => [
                    'cursors' => ['before' => 'xxx', 'after' => 'yyy'],
                ],
            ], 200),
        ]);

        $result = app(WhatsAppService::class)->syncTemplates();

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['count']);

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'wedding_invitation',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
            'header_type' => 'IMAGE',
        ]);

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'reminder_sw',
            'language' => 'sw',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
        ]);
    }

    public function test_admin_can_trigger_template_sync_endpoint(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/1088196013963166/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => '999999999',
                        'name' => 'wedding_invitation_gold',
                        'language' => 'fr_FR',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'components' => [],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.whatsapp-templates.sync'));

        $response->assertRedirect(route('admin.whatsapp-templates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'wedding_invitation_gold',
            'language' => 'fr_FR',
        ]);
    }

    public function test_non_admin_cannot_trigger_template_sync(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.whatsapp-templates.sync'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_templates_index_page(): void
    {
        WhatsAppTemplate::create([
            'name' => 'wedding_invitation',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
            'header_type' => 'IMAGE',
            'body_text' => 'Hello {{1}}',
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.whatsapp-templates.index'));

        $response->assertOk();
        $response->assertSee('wedding_invitation');
        $response->assertSee('en_US');
        $response->assertSee('Sync from WhatsApp');
    }

    public function test_invitation_send_uses_the_language_associated_with_the_db_template(): void
    {
        WhatsAppTemplate::create([
            'name' => 'wedding_swahili',
            'language' => 'sw',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
            'header_type' => 'IMAGE',
        ]);

        $event = Event::create([
            'user_id' => $this->admin->id,
            'name' => 'Swahili Wedding',
            'slug' => 'swahili-wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
            'single_card_min_amount' => 50000,
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Amina Juma',
            'phone_number' => '+255750960863',
            'amount_contributed' => 100000,
            'paid_amount' => 50000,
        ]);

        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
            'send_status' => 'pending',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/1317524134784685/messages' => Http::response([
                'messages' => [['id' => 'wamid.HBgLMjU1NzUwOTYwODYzFQIAERgSRDFGNjFDRTFFM0UxQ0YyOQA=']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('events.invitations.send', [$event, $invitation]),
            [
                'template_name' => 'wedding_swahili',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk();

        Http::assertSent(function ($request) {
            $template = $request->data()['template'] ?? [];
            return ($template['name'] ?? null) === 'wedding_swahili'
                && ($template['language']['code'] ?? null) === 'sw';
        });
    }

    public function test_guest_send_message_uses_the_language_associated_with_the_db_template(): void
    {
        WhatsAppTemplate::create([
            'name' => 'custom_reminder_fr',
            'language' => 'fr',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
        ]);

        $event = Event::create([
            'user_id' => $this->admin->id,
            'name' => 'French Wedding',
            'slug' => 'french-wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Paris',
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Pierre Dupont',
            'phone_number' => '+255750960863',
            'amount_contributed' => 100000,
            'paid_amount' => 20000,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/1317524134784685/messages' => Http::response([
                'messages' => [['id' => 'wamid.HBgLMjU1NzUwOTYwODYzFQIAERgSRDFGNjFDRTFFM0UxQ0YyOQA=']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('events.guests.send-message', [$event, $guest]),
            [
                'template_name' => 'custom_reminder_fr',
                'template_language' => 'fr',
                'message_type' => 'reminder',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk();

        Http::assertSent(function ($request) {
            $template = $request->data()['template'] ?? [];
            return ($template['name'] ?? null) === 'custom_reminder_fr'
                && ($template['language']['code'] ?? null) === 'fr';
        });
    }
}
