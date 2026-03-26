<?php
// added for Ugandan Schools *Elicom Elijah*

namespace App\Models\Academics;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remarks extends Model
{
    use HasFactory;
    // protected $table = 'comments';  // ← this line fixes everything

    protected $fillable = ["remark"];
   
}
