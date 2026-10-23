<?php

use App\Livewire\Store\Account\AccountManagement;
use App\Livewire\Store\Account\AddressBook;
use App\Mail\VerificationCodeMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Services\Messaging\SmsSender;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    $this->sms = new class implements SmsSender
    {
        public array $sent = [];

        public function send(string $phone, string $message): void
        {
            $this->sent[] = [$phone, $message];
        }
    };
    app()->instance(SmsSender::class, $this->sms);

    $this->user = User::create(['name' => 'Valentine Akin', 'email' => 'val@example.com', 'email_verified_at' => now()]);
    $this->actingAs($this->user);
});

/** The latest code emailed to an address. */
function codeMailedTo(string $email): string
{
    $code = null;
    Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use ($email, &$code) {
        if ($mail->hasTo($email)) {
            $code = $mail->code;
        }

        return true;
    });

    return $code;
}

test('account management shows the name and sign-in details', function () {
    $this->get('/account/manage')
        ->assertOk()
        ->assertSee('Account Management')
        ->assertSee('Valentine')
        ->assertSee('Akin')
        ->assertSee('val@example.com')
        ->assertSee('Verified');
});

test('the name can be edited', function () {
    Livewire::test(AccountManagement::class)
        ->call('editName')
        ->assertSet('firstName', 'Valentine')
        ->assertSet('lastName', 'Akin')
        ->set('firstName', '')
        ->call('saveName')
        ->assertHasErrors('firstName')
        ->set('firstName', 'Val')
        ->set('lastName', 'Akin-Obi')
        ->call('saveName')
        ->assertSet('modal', null);

    expect($this->user->fresh()->name)->toBe('Val Akin-Obi');
});

test('changing email needs a code from the current address, then the new one', function () {
    $component = Livewire::test(AccountManagement::class)
        ->call('change', 'email')
        ->assertSet('modal', 'verify-current');

    // The new address can't be submitted before the current one is verified.
    $component->set('newValue', 'new@example.com')->call('sendNewCode');
    Mail::assertNotSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('new@example.com'));

    $component->set('code', codeMailedTo('val@example.com'))
        ->call('verifyCurrent')
        ->assertSet('modal', 'enter-new')
        ->set('newValue', 'New@Example.com')
        ->call('sendNewCode')
        ->assertSet('modal', 'verify-new');

    expect($this->user->fresh()->email)->toBe('val@example.com');

    $component->set('code', '000000')->call('verifyNew')->assertHasErrors('code');

    $component->set('code', codeMailedTo('new@example.com'))
        ->call('verifyNew')
        ->assertSet('modal', null);

    expect($this->user->fresh()->email)->toBe('new@example.com');
});

test('an email already used by another account is refused', function () {
    User::create(['name' => 'Other', 'email' => 'taken@example.com']);

    Livewire::test(AccountManagement::class)
        ->call('change', 'email')
        ->set('code', codeMailedTo('val@example.com'))
        ->call('verifyCurrent')
        ->set('newValue', 'taken@example.com')
        ->call('sendNewCode')
        ->assertHasErrors('newValue')
        ->assertSet('modal', 'enter-new');
});

test('a phone can be added without a current one to verify', function () {
    $component = Livewire::test(AccountManagement::class)
        ->call('change', 'phone')
        ->assertSet('modal', 'enter-new')
        ->set('newValue', '506 555 0199')
        ->call('sendNewCode')
        ->assertSet('codeDestination', '+15065550199');

    preg_match('/\d{6}/', $this->sms->sent[0][1], $m);

    $component->set('code', $m[0])->call('verifyNew');

    expect($this->user->fresh())
        ->phone->toBe('+15065550199')
        ->phone_verified_at->not->toBeNull();
});

test('closing the account signs out and keeps orders for the store', function () {
    $order = Order::create([
        'number' => 'ISA-DONE01', 'user_id' => $this->user->id, 'status' => Order::STATUS_PAID,
        'fulfilment_status' => Order::FULFILMENT_COMPLETED, 'fulfilment' => 'pickup', 'contact_name' => 'Valentine Akin',
        'subtotal_cents' => 500, 'total_cents' => 500, 'payment_method' => 'card', 'placed_at' => now(),
    ]);

    Livewire::test(AccountManagement::class)
        ->call('confirmClose')
        ->call('closeAccount')
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect(User::find($this->user->id))->toBeNull()
        ->and($order->fresh()->user_id)->toBeNull();
});

test('an account with orders in progress cannot be closed', function () {
    Order::create([
        'number' => 'ISA-OPEN01', 'user_id' => $this->user->id, 'status' => Order::STATUS_PLACED,
        'fulfilment' => 'pickup', 'contact_name' => 'Valentine Akin',
        'subtotal_cents' => 500, 'total_cents' => 500, 'payment_method' => 'pay_at_pickup', 'placed_at' => now(),
    ]);

    Livewire::test(AccountManagement::class)
        ->call('closeAccount')
        ->assertHasErrors('close');

    expect(User::find($this->user->id))->not->toBeNull();
});

test('addresses can be added, edited, made default and deleted', function () {
    $this->get('/account/addresses')->assertOk()->assertSee('No address added yet');

    $book = Livewire::test(AddressBook::class)
        ->call('add')
        ->assertSet('makeDefault', true)
        ->set('recipientPhone', '506 555 0101')
        ->set('street', '24 Berkeley Road')
        ->set('city', 'Saint John')
        ->set('postalCode', 'not a code')
        ->call('save')
        ->assertHasErrors('postalCode')
        ->set('postalCode', 'e2l4l1')
        ->call('save')
        ->assertSet('modal', null);

    $first = Address::first();
    expect($first)->postal_code->toBe('E2L 4L1')->is_default->toBeTrue()->phone->toBe('+15065550101');

    $book->call('add')
        ->set('recipientPhone', '506 555 0102')
        ->set('street', '1 Water Street')
        ->set('city', 'Moncton')
        ->set('postalCode', 'E1C 1A1')
        ->call('save')
        ->assertSee('Outside our delivery area');

    $second = Address::where('street', '1 Water Street')->first();
    expect($second->is_default)->toBeFalse();

    $book->call('makeDefaultAddress', $second->id);
    expect($second->fresh()->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();

    $book->call('edit', $first->id)->set('street', '26 Berkeley Road')->call('save');
    expect($first->fresh()->street)->toBe('26 Berkeley Road');

    // Deleting the default hands it to the remaining address.
    $book->call('confirmDelete', $second->id)->call('delete');
    expect(Address::count())->toBe(1)->and($first->fresh()->is_default)->toBeTrue();
});

test("someone else's address cannot be edited", function () {
    $other = User::create(['name' => 'Other', 'email' => 'other@example.com']);
    $address = $other->addresses()->create([
        'recipient_name' => 'Other', 'phone' => '+15065550000', 'street' => '1 Main', 'city' => 'Saint John', 'postal_code' => 'E2L 4L1',
    ]);

    Livewire::test(AddressBook::class)->call('edit', $address->id)->assertNotFound();
});
