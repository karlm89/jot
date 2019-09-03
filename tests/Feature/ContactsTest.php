<?php

namespace Tests\Feature;

use App\Contact;
use App\User;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;
    protected $user;
    
    public function setUp(): void
    {
        parent::setup();
        $this->user = factory(User::class)->create();
    }
    
    /** @test */
    public function an_unauthenticated_user_should_redirected_to_login()
    {
         $response = $this->post('/api/contacts', 
            array_merge($this->data(), ['api_token'=>''])
        );
         $response->assertRedirect('/login');
         $this->assertCount(0, Contact::all());
    }

    /** @test */
    public function an_authenticated_user_can_add_a_contact()
    {
        $this->post('/api/contacts',$this->data());

        $contact = Contact::first();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('test@email.com', $contact->email);
        $this->assertEquals('07/25/1989', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC Company', $contact->company);
    }

    /** @test */
    public function fields_are_required(){
        collect(['name', 'email', 'birthday', 'company'])
        ->each(function ($field) {
            $response = $this->post('/api/contacts', array_merge($this->data(), [$field => '']));
            $response->assertSessionHasErrors($field);
            $this->assertCount(0, Contact::all());
        });
    }

    /** @test */
    public function email_must_be_valid_email(){
        $response = $this->post('/api/contacts', array_merge($this->data(), ['email' => 'NOT AN EMAIL']));
        $response->assertSessionHasErrors('email');
        $this->assertCount(0, Contact::all());
    }

    /** @test */
    public function birthdays_are_stored_as_dates(){
        $response = $this->post('/api/contacts', array_merge($this->data()));

        $this->assertCount(1, Contact::all());
        // Checks to see if birthday is an instance of Carbon
        $this->assertInstanceOf(Carbon::class, Contact::first()->birthday);
        // Checks to see if date was parsed properly
        $this->assertEquals('07-25-1989', Contact::first()->birthday->format('m-d-Y'));
    }

    /** @test */
    public function a_contact_can_be_retrieved(){
        $contact = factory(Contact::class)->create();

        $response = $this->get('/api/contacts/'.$contact->id. '?api_token='. $this->user->api_token);
        $response->assertJson([
            'name'      => $contact->name,
            'email'     => $contact->email,
            'birthday'  => $contact->birthday,
            'company'   => $contact->company,
        ]);
    }

    /** @test */
    public function a_contact_can_be_patched(){
        $contact = factory(Contact::class)->create();

        $response = $this->patch('/api/contacts/' . $contact->id, $this->data());
        $contact = $contact->fresh();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('test@email.com', $contact->email);
        $this->assertEquals('07/25/1989', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC Company', $contact->company);
    }

    /** @test */
    public function a_contact_can_be_deleted(){
        $contact = factory(Contact::class)->create();

        $response = $this->delete('/api/contacts/'.
            $contact->id, 
            ['api_token'=>$this->user->api_token]);

        $this->assertCount(0, Contact::all());
    }

    private function data(){
        return [
            'name' => 'Test Name',
            'email' => 'test@email.com',
            'birthday' => "07/25/1989",
            'company' => 'ABC Company',
            'api_token' => $this->user->api_token
        ];
    }
}
