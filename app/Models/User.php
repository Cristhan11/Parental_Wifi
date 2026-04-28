<?php

namespace App\Models;

use App\Mail\VerifyEmailCodeMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PARENT = 'parent';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PARENT_ADMIN = 'parent_admin';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'requires_email_setup',
        'force_password_change',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'requires_email_setup' => 'boolean',
            'force_password_change' => 'boolean',
        ];
    }

    /**
     * Send a numeric verification code by email (styled like reporting messages), not a signed URL.
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(60),
        ])->save();

        Mail::to($this->email)->send(new VerifyEmailCodeMail($this, $code));
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function blockedWebsites(): HasMany
    {
        return $this->hasMany(BlockedWebsite::class);
    }

    public function flaggedWebsites(): HasMany
    {
        return $this->hasMany(FlaggedWebsite::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function dictionaryWords(): HasMany
    {
        return $this->hasMany(DictionaryWord::class);
    }

    public function reportingPreference(): HasOne
    {
        return $this->hasOne(ReportingPreference::class);
    }

    public function reportingRecipients(): HasMany
    {
        return $this->hasMany(ReportingRecipient::class);
    }

    public function reportingRecipientEvents(): HasMany
    {
        return $this->hasMany(ReportingRecipientEvent::class);
    }

    public function reportDispatchLogs(): HasMany
    {
        return $this->hasMany(ReportDispatchLog::class);
    }

    /**
     * Parent-scoped dashboard: role parent or household operator (parent + admin capabilities).
     */
    public function hasParentCapability(): bool
    {
        return in_array($this->role, [self::ROLE_PARENT, self::ROLE_PARENT_ADMIN, self::ROLE_ADMIN], true);
    }

    /**
     * Admin / operator dashboard: system admin or household operator.
     */
    public function hasAdminCapability(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_PARENT_ADMIN], true);
    }

    /**
     * @deprecated Prefer hasParentCapability(); kept for readability in views.
     */
    public function isParent(): bool
    {
        return $this->hasParentCapability();
    }

    /**
     * Strict `parent` role only (registration queue, not yet promoted to parent_admin).
     */
    public function isStrictParentRole(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    public function isAdmin(): bool
    {
        return $this->hasAdminCapability();
    }

    public function isParentAdmin(): bool
    {
        return $this->role === self::ROLE_PARENT_ADMIN;
    }

    /**
     * Accounts with admin/operator capability cannot self-delete from profile
     * to prevent lockout of household/system administration access.
     */
    public function canDeleteOwnAccount(): bool
    {
        return ! $this->hasAdminCapability();
    }

    public function isAwaitingAdminApproval(): bool
    {
        return $this->isStrictParentRole()
            && $this->approved_at === null
            && $this->rejected_at === null;
    }

    public function isPendingApproval(): bool
    {
        return $this->isAwaitingAdminApproval();
    }

    public function isApprovedParentAccount(): bool
    {
        if (! $this->hasParentCapability()) {
            return false;
        }

        if ($this->role === self::ROLE_ADMIN) {
            return ! $this->requires_email_setup
                && ! $this->force_password_change
                && $this->hasVerifiedEmail()
                && $this->rejected_at === null;
        }

        return $this->approved_at !== null && $this->rejected_at === null;
    }

    /**
     * Parent / household-operator accounts may use /forgot-password to queue an admin-led reset (no email link).
     */
    public function isEligibleForSelfServicePasswordResetRequest(): bool
    {
        if ($this->rejected_at !== null) {
            return false;
        }

        return in_array($this->role, [self::ROLE_PARENT, self::ROLE_PARENT_ADMIN], true);
    }

    /**
     * Full parent dashboard access: verified email + approved + not rejected.
     */
    public function canAccessParentDashboard(): bool
    {
        if (! $this->isApprovedParentAccount()) {
            return false;
        }

        if (! $this->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function canAccessAdminDashboard(): bool
    {
        return $this->hasAdminCapability();
    }

    /**
     * Backward compatibility: older seeded owners stayed as ROLE_ADMIN after setup.
     * Promote them to ROLE_PARENT_ADMIN once setup is complete and email is verified.
     */
    public function upgradeLegacyOwnerToParentAdminIfEligible(): bool
    {
        if (
            $this->role !== self::ROLE_ADMIN
            || $this->requires_email_setup
            || $this->force_password_change
            || ! $this->hasVerifiedEmail()
        ) {
            return false;
        }

        $this->forceFill([
            'role' => self::ROLE_PARENT_ADMIN,
            'approved_at' => $this->approved_at ?? now(),
            'rejected_at' => null,
            'approval_rejection_note' => null,
        ])->save();

        return true;
    }

    public function accountTypeLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Parent Owner',
            self::ROLE_PARENT_ADMIN => 'Household operator (parent + admin)',
            self::ROLE_PARENT => 'Parent',
            default => 'User',
        };
    }
}
