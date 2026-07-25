<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'details',
        'proccess_status',
    ];


    public function setProccessStatus(string $status): void
    {
        $this->proccess_status = $status;
        $this->save();
    }
}
