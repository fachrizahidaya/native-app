<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyInvitation extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'family_id', 
        'invited_by',
        'email',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime', 
        'accepted_at' => 'datetime'
    ];

    public function family() {

    return $this->belongsTo(
        Family::class
    );
        
    }

    public function inviter()  {
        return $this->belongsTo(User::class, 'invited_by');
        
    }
}
