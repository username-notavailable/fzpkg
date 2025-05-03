<?php

namespace App\Events;

use Illuminate\Http\Request;

class LoginRequest
{
    /**
     * The throttled request.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Result: 'admin-login-success', 'admin-login-fail', 'admin-login-lockout'
     *
     * @var string $result
     */
    public string $result;

    /**
     * UserId.
     *
     * @var string $userId
     */
    public string $userId;

    /**
     * UserId.
     *
     * @var string $userIdFrom
     */
    public string $userIdFrom;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @var string $result
     * @var string $userId
     * @return void
     */
    public function __construct(Request $request, string $result, string $userId, string $userIdFrom)
    {
        $this->request = $request;
        $this->result = $result;
        $this->userId = $userId;
        $this->userIdFrom = $userIdFrom;
    }

    public static function successResult(Request $request, string $userId, string $userIdFrom)
    {
        return new self($request, 'login-success', $userId, $userIdFrom);
    }

    public static function failResult(Request $request, string $userId, string $userIdFrom)
    {
        return new self($request, 'login-fail', $userId, $userIdFrom);
    }

    public static function lockoutResult(Request $request, string $userId, string $userIdFrom)
    {
        return new self($request, 'login-lockout', $userId, $userIdFrom);
    }

    public function __toString()
    {
        return $this->result . '-' . $this->userId . '-' . $this->userIdFrom . '-' . $this->request;
    }
}
