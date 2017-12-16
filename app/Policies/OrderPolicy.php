<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        if ($user->hasRole([User::ROLE_ADMINISTRATOR, User::ROLE_IT, User::ROLE_ADVISER])) {
            return true;
        }
    }

    public function show(User $user, Order $order)
    {
        return $this->update($user, $order) || $user->hasRole([
            User::ROLE_GIVER, User::ROLE_OPER, User::ROLE_PRODUCT, User::ROLE_MARKET, User::ROLE_FINANCE,
            User::ROLE_WE,
        ]);
    }

    public function update(User $user, Order $order)
    {
        if ($user->id === $order->userId) {
            return true;
        }

        return $this->updateOnlyUser($user, $order);
    }

    public function updateOnlyUser(User $user, Order $order)
    {
        if ($user->name === $order->name && (
            ($order->mobile !== null && $user->mobile === $order->mobile) ||
            ($order->passportNo !== null && $user->passportNo === $order->passportNo) ||
            ($order->idCardNo !== null && $user->idCardNo === $order->idCardNo)
        )) {
            return true;
        }

        return false;
    }
}
