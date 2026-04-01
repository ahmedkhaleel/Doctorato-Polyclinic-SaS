<?php

namespace Tests\Feature\Webmaster;

use App\Models\Faq;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebmasterFaqTest extends TestCase
{
    use RefreshDatabase;

    private User $webmaster;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'webmaster'],
            ['display_name_en' => 'Webmaster', 'display_name_ar' => 'مدير الموقع', 'permissions' => [
                'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
            ], 'is_system' => true]
        );

        $this->webmaster = User::create([
            'name' => 'Webmaster', 'email' => 'wm-faq@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_faq_index(): void
    {
        $this->actingAs($this->webmaster)->get('/webmaster/faqs')->assertOk();
    }

    public function test_can_create_faq(): void
    {
        $this->actingAs($this->webmaster)->post('/webmaster/faqs', [
            'category' => 'General',
            'question_ar' => 'ما هي ساعات العمل؟',
            'question_en' => 'What are the working hours?',
            'answer_ar' => 'من 9 صباحاً حتى 9 مساءً',
            'answer_en' => 'From 9 AM to 9 PM',
        ])->assertRedirect();

        $this->assertDatabaseHas('faqs', ['question_en' => 'What are the working hours?']);
    }

    public function test_faq_requires_question(): void
    {
        $this->actingAs($this->webmaster)->post('/webmaster/faqs', [
            'category' => 'General',
            'answer_ar' => 'إجابة',
            'answer_en' => 'Answer',
        ])->assertSessionHasErrors(['question_ar', 'question_en']);
    }

    public function test_can_update_faq(): void
    {
        $faq = Faq::create([
            'category' => 'General',
            'question_ar' => 'سؤال قديم', 'question_en' => 'Old Question',
            'answer_ar' => 'إجابة قديمة', 'answer_en' => 'Old Answer',
        ]);

        $this->actingAs($this->webmaster)->put("/webmaster/faqs/{$faq->id}", [
            'category' => 'General',
            'question_ar' => 'سؤال محدث', 'question_en' => 'Updated Question',
            'answer_ar' => 'إجابة محدثة', 'answer_en' => 'Updated Answer',
        ])->assertRedirect();

        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'question_en' => 'Updated Question']);
    }

    public function test_can_delete_faq(): void
    {
        $faq = Faq::create([
            'category' => 'General',
            'question_ar' => 'حذف', 'question_en' => 'Delete Me',
            'answer_ar' => 'إجابة', 'answer_en' => 'Answer',
        ]);

        $this->actingAs($this->webmaster)->delete("/webmaster/faqs/{$faq->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
