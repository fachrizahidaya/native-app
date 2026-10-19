<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\FamilyMember;
use App\Models\User;
use App\Notifications\FamilyInvitationNotification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class FamilyInvitationService
{
    public function invite(Family $family, User $inviter, string $email): FamilyInvitation
    {
        abort_unless(
            $family->owner_id === $inviter->id || $inviter->isAdmin(),
            403,
            'You are not allowed to invite members to this family.'
        );

        $email = Str::lower(trim($email));
        abort_if(Str::lower($inviter->email) === $email, 422, 'You cannot invite yourself.');

        $invitedUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        abort_if(
            $invitedUser && (
                $family->owner_id === $invitedUser->id
                || $family->members()->where('user_id', $invitedUser->id)->exists()
            ),
            409,
            'This user is already a family member.'
        );

        abort_if(
            $family->invitations()
                ->pending()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists(),
            409,
            'An active invitation already exists for this email.'
        );

        $rawToken = Str::random(64);
        $invitation = $family->invitations()->create([
            'invited_by' => $inviter->id,
            'email' => $email,
            'token' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

       

        return $invitation;
    }

    public function resolve(string $token): FamilyInvitation
    {
        return $this->ensureAcceptable($this->findByRawToken($token));
    }

    public function accept(string $token, User $user): FamilyInvitation
    {
        return DB::transaction(function () use ($token, $user) {
            $invitation = $this->findForAcceptance($token);
            $invitation = FamilyInvitation::query()
                ->lockForUpdate()
                ->with(['family', 'inviter'])
                ->findOrFail($invitation->id);

            $this->ensureAcceptable($invitation);
            abort_unless(
                Str::lower($user->email) === Str::lower($invitation->email),
                403,
                'This invitation was sent to a different email address.'
            );

            FamilyMember::query()->firstOrCreate(
                [
                    'family_id' => $invitation->family_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            );

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return $invitation->fresh(['family', 'inviter']);
        });
    }

    public function cancel(FamilyInvitation $invitation, User $user): FamilyInvitation
    {
        $invitation->loadMissing('family');
        abort_unless(
            $invitation->invited_by === $user->id
                || $invitation->family->owner_id === $user->id
                || $user->isAdmin(),
            403,
            'You are not allowed to cancel this invitation.'
        );
        abort_unless($invitation->status === 'pending', 409, 'Only pending invitations can be cancelled.');

        $invitation->update(['status' => 'cancelled']);

        return $invitation;
    }

    public function pendingToken(FamilyInvitation $invitation): string
    {
        return 'pending.'.Crypt::encryptString((string) $invitation->id);
    }

    private function findByRawToken(string $token): FamilyInvitation
    {
        $invitation = FamilyInvitation::query()
            ->with(['family', 'inviter'])
            ->where('token', hash('sha256', $token))
            ->first();

        abort_if($invitation === null, 404, 'Invitation not found.');

        return $invitation;
    }

    private function findForAcceptance(string $token): FamilyInvitation
    {
        if (! Str::startsWith($token, 'pending.')) {
            return $this->findByRawToken($token);
        }

        try {
            $id = Crypt::decryptString(Str::after($token, 'pending.'));
        } catch (DecryptException) {
            abort(404, 'Invitation not found.');
        }

        $invitation = FamilyInvitation::query()->with(['family', 'inviter'])->find($id);
        abort_if($invitation === null, 404, 'Invitation not found.');

        return $invitation;
    }

    private function ensureAcceptable(FamilyInvitation $invitation): FamilyInvitation
    {
        if ($invitation->status === 'pending' && $invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            abort(410, 'Invitation has expired.');
        }

        abort_unless($invitation->status === 'pending', 409, 'Invitation is no longer pending.');

        return $invitation;
    }
}
