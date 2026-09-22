<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    use LogsActivity;

    protected static $logName = 'user';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('user')
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_id',
        'account_type',
        'username',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /**
     * Get the user who created this user
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'user_id');
    }

    public function businessUnits()
    {
        return $this->belongsToMany(BusinessUnit::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function hasExplicitBusinessUnitAccess(): bool
    {
        return $this->businessUnits()->exists();
    }

    public function canAccessBusinessUnit(BusinessUnit|int $businessUnit): bool
    {
        $id = $businessUnit instanceof BusinessUnit ? $businessUnit->id : $businessUnit;

        if (! $this->hasExplicitBusinessUnitAccess()) {
            return true;
        }

        return $this->businessUnits()->whereKey($id)->exists();
    }

    public function isOnline(): bool
    {
        $lastSeen = Cache::get('user-last-seen-' . $this->id);

        if (!$lastSeen) return false;

        // Consider user offline if inactive for more than 10 minutes
        return now()->diffInMinutes($lastSeen) <= 10;
    }

    public function lastSeen()
    {
        return Cache::get('user-last-seen-' . $this->id);
    }

    public function onlineStatus(): string
    {
        $lastSeen = Cache::get('user-last-seen-' . $this->id);
        if (!$lastSeen) return 'offline';

        $minutes = now()->diffInMinutes($lastSeen);

        if ($minutes <= 15) return 'online';          // was 10
        if ($minutes <= 1440) return 'recent';        // within 24 hrs
        return 'offline';
    }


    public function lastSeenHuman()
    {
        $lastSeen = Cache::get('user-last-seen-' . $this->id);
        return $lastSeen ? Carbon::parse($lastSeen)->diffForHumans() : null;
    }
}
