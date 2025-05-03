<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Auth;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KcToken extends Model
{
    //use HasFactory;
    use HasUuids;

    protected $attributes = [
        'id' => '',
        'slug' => '',
        'system' => false
    ];
}