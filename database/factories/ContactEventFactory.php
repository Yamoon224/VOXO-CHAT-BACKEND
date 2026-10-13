<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactEvent> */
class ContactEventFactory extends Factory
{
    protected $model = ContactEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $contact = Contact::factory()->create();

        return [
            'workspace_id' => $contact->workspace_id,
            'contact_id' => $contact->id,
            'type' => 'note_added',
            'payload' => null,
            'occurred_at' => now(),
        ];
    }
}
