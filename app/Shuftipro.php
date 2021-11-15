<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shuftipro extends Model
{
	protected $table = 'shuftipro';

	/**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [
		"id",
		"user_id",
		// "reference_id",
		"is_successful",
		"data",
		// "document_proof",
		// "address_proof",
		"document_result",
		"address_result",
		"background_checks_result",
		"status",
		"reviewed",
		"created_at",
		"updated_at",
		"manual_approved_at",
		"manual_reviewer",
    ];
}
