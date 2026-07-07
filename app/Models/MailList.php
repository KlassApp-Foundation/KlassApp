<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailList extends Model
{
    protected $table = 'mail_list';

    protected $fillable = ['email', 'name', 'subscribed'];
}
