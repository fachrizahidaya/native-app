<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'family_id', 
        'user_id', 
        'role', 
        'joined_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime'
    ];

    public function family()  {
        return $this->belongsTo(Family::class);
        
    }
    public function user()  {
        return $this->belongsTo(User::class);
        
    }
}
