<?php

namespace App\Events;

use Illuminate\Http\Request;

class AdminLoginRequest
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
     * Create a new event instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @var string $result
     * @var string $userId
     * @return void
     */
    public function __construct(Request $request, string $result, string $userId)
    {
        $this->request = $request;
        $this->result = $result;
        $this->userId = $userId;
    }

    public static function successResult(Request $request, string $userId)
    {
        return new self($request, 'admin-login-success', $userId);
    }

    public static function failResult(Request $request, string $userId)
    {
        return new self($request, 'admin-login-fail', $userId);
    }

    public static function lockoutResult(Request $request, string $userId)
    {
        return new self($request, 'admin-login-lockout', $userId);
    }

    public function __toString()
    {
        return $this->result . $this->userId . $this->request;
    }
}
