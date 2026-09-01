<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Local display preferences, and a read-only view of the shared account.
 *
 * Name is editable because it is this site's own display preference. Email is
 * not: it arrives as a claim from the identity provider and is refreshed on
 * every sign-in, so an edit here would be silently reverted the next time the
 * person logged in. Changing it is done where it is owned — with the provider.
 *
 * Verification is gone for the same reason. The provider verifies the address
 * and asserts it; this application has no token to send and nothing to check.
 */
class Profile extends Component
{
    public string $name = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
    }

    public function email(): string
    {
        return (string) Auth::user()->email;
    }

    public function accountId(): string
    {
        return (string) Auth::user()->zahir_account_id;
    }

    public function updateProfileInformation(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }
}
