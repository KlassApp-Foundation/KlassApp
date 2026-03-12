<?php
// added for Ugandan Schools *Elicom Elijah*

namespace App\Models\Academics;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    use HasFactory;

    protected $fillable = ["comment", "school_id"];
    public function schoolId(){
        return $this->belongsTo(School::class, "school_id");
    }
}
