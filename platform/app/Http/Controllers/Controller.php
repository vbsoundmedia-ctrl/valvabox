<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Allow the owner of a record (or an admin) through; everyone else gets 404. */
    protected function authorizeOwner(object $model): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || (int) $model->user_id === (int) $user->id), 404);
    }
}
