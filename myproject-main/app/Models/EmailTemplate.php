<?php
namespace App\Models;

#use Illuminate\Notifications\Notifiable;
#use Kyslik\ColumnSortable\Sortable;
#use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    //use Notifiable;
	//use Sortable;
	use HasFactory;
	
	
	
	protected $fillable = [
        'name',
        'subject',
        'description'
    ];

	/*protected $fillable = [
        'id', 'title', 'subject', 'variables', 'alias', 'email_from', 'description', 'created_at', 'updated_at'
    ];
	
	public $sortable = ['id', 'title'];*/
}