<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Query;
use App\Models\User;

class ContactPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any queries.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can view the query.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Query  $query
     * @return mixed
     */
    public function view(User $user, Query $query)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create queries.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the query.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Query  $query
     * @return mixed
     */
    public function update(User $user, Query $query)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the query.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Query  $query
     * @return mixed
     */
    public function delete(User $user, Query $query)
    {
        //
    }

    /**
     * Determine whether the user can restore the contact.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Query  $contact
     * @return mixed
     */
    public function restore(User $user, Query $contact)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the contact.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Query  $contact
     * @return mixed
     */
    public function forceDelete(User $user, Query $contact)
    {
        //
    }
}
