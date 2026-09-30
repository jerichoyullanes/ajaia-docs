<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['document_id', 'user_id', 'permission'])]
class DocumentShare extends Model {}
