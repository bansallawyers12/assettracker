<?php
namespace App\Models;

#use Illuminate\Notifications\Notifiable;
#use Kyslik\ColumnSortable\Sortable;
#use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Email extends Model
{
    //use Notifiable;
	//use Sortable;
	
	use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	 
	protected $fillable = [
        'email',
        'password',
        'email_signature',
        'display_name',
        'status',
        'user_id',
        'type',
        'error_message'
    ];

	//public $sortable = ['id', 'created_at', 'updated_at'];

}
