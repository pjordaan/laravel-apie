## Sub actions
In Apie 3.3 sub actions were introduced. Laravel-apie has these
integrated as well.

Sub actions can be actions you can apply to an API resource and
could have any return value (which will be mapped in the OpenAPI spec).

As an example imagine an API that can modify/create users. We could have
a forget password sub action for users:

```php
<?php
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Queue\Job;

class PasswordResetAction
{
    private $userService;

    private $gate;

    public function __construct(UserService $userService, Gate $gate)
    {
        $this->userService = $userService;
        $this->gate = $gate;
    }

    public function handle(User $user): Job
    {
        $this->gate->authorize('resetPassword', $user);
        return $this->userService->requestPasswordReset($user);
    }
}
```

Now we need to add the sub actions in the laravel-apie config:
```php
<?php
//config/apie.php
return [
    'resources' => [User::class],
    'subactions' => [
        'reset_passWord' => [PasswordResetAction::class],
    ]
];
```
Now the swagger ui will add a route for POST /user/{id}/reset_password with {} as POST body.

If we would remove the typehint of User, all the api resources will retrieve a reset_password option.

## Adding arguments
We can add extra arguments in the handle. They will be added in the POST body. They will also be deserialized
and validated to the correct typehint. For example we could add an argument if we want to send the reset
password e-mail again.

```php
<?php
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Queue\Job;

class PasswordResetAction
{
    private $userService;

    private $gate;

    public function __construct(UserService $userService, Gate $gate)
    {
        $this->userService = $userService;
        $this->gate = $gate;
    }

    public function handle(User $user, bool $sendEmail): Job
    {
        $this->gate->authorize('resetPassword', $user);
        if ($sendEmail) {
            ResetPasswordMail::dispatch($user);
        }
        return $this->userService->requestPasswordReset($user);
    }
}
```

The OpenAPI spec wil show that you require to send a send_email boolean property in the POST body.
