<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactNote> */
class ContactNoteFactory extends Factory
{
    protected $model = ContactNote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $contact = Contact::factory()->create();

        return [
            'workspace_id' => $contact->workspace_id,
            'contact_id' => $contact->id,
            'author_user_id' => null,
            'body' => $this->faker->sentence(),
        ];
    }
}
