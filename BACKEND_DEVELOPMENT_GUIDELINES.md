# Backend Development Guidelines

## Purpose

These guidelines define the backend architecture standards for this project.

Every developer must follow these rules to keep the codebase:

- Clean
- Maintainable
- Testable
- Consistent
- Scalable

---

## 1. Controllers Must Stay Thin

### Don't

Never put business logic inside controllers.

```php
public function store(Request $request)
{
    // validation

    // business logic

    // database

    // notifications

    // response
}
```

Controllers should never become hundreds of lines long.

### Do

Controllers should only:

- Receive Request
- Call Action
- Return Response

Example:

```php
public function store(RegisterRequest $request, RegisterAction $action): JsonResponse
{
    $user = $action->execute($request->validated());

    return $this->success(
        message: 'Registered successfully.',
        data: UserResource::make($user),
    );
}
```

---

## 2. Validation Must Never Live Inside Controllers

### Don't

```php
$request->validate([
    'name' => 'required',
]);
```

### Do

Create a dedicated Form Request.

```
app/Http/Requests/
```

Example:

```php
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...
        ];
    }
}
```

Controller:

```php
public function store(RegisterRequest $request)
{
    ...
}
```

Benefits:

- Reusable
- Cleaner controller
- Easier testing

---

## 3. Business Logic Belongs to Action Classes

Business logic should never be placed inside:

- Controllers
- Models
- Routes

Instead, create an Action.

```
app/Actions/
```

Example:

```
RegisterUserAction
CreateOrderAction
ResetPasswordAction
VerifyOtpAction
```

Example:

```php
class RegisterUserAction
{
    public function execute(array $data): User
    {
        ...
    }
}
```

Controller:

```php
$user = $action->execute($request->validated());
```

---

## 4. Follow Single Responsibility Principle (SRP)

Each class should have one reason to change.

### Good

```
RegisterUserAction
SendOtpAction
CreateOrderAction
CalculateDiscountAction
```

### Bad

```
UserService
EverythingService
OrderHelper
Utils
```

If a class performs multiple responsibilities, split it into smaller classes.

---

## 5. Use API Resources

Never return models directly.

### Don't

```php
return $user;
```

### Do

```php
return UserResource::make($user);
```

Benefits:

- Hide internal fields
- Consistent response
- Easier API versioning

---

## 6. Use ApiResponse Trait

Every endpoint should return the same response structure.

Instead of:

```php
return response()->json([
    ...
]);
```

Use:

```php
return $this->success(
    message: 'Success',
    data: ...
);
```

Errors:

```php
return $this->error(
    message: 'Something went wrong.'
);
```

Response format:

```json
{
    "success": true,
    "message": "...",
    "data": {}
}
```

---

## 7. Keep Database Operations Inside Actions

Controllers should never interact directly with Eloquent.

### Don't

```php
User::create(...);
```

inside controller.

### Do

```php
RegisterUserAction
```

handles all database operations.

---

## 8. Wrap Multiple Database Operations Inside Transactions

Whenever multiple writes occur:

- create
- update
- delete

Use:

```php
DB::transaction(function () {

});
```

---

## 9. Never Duplicate Logic

If the same code appears twice, extract it into:

- Action
- Service
- Trait
- Helper

depending on its responsibility.

---

## 10. Dependency Injection Everywhere

Never instantiate dependencies manually.

### Don't

```php
$service = new TwilioOtpService();
```

### Do

```php
public function __construct(
    private readonly TwilioOtpService $otpService
) {}
```

---

## 11. Never Access Request Directly Inside Actions

### Don't

```php
$request->input('phone');
```

inside Action.

### Do

Pass only the required data.

```php
$action->execute($request->validated());
```

Actions should be framework-independent.

---

## 12. Use Constants or Enums

Avoid magic strings.

### Don't

```php
'register'
```

### Do

```php
OtpPurpose::REGISTER
```

or:

```php
TwilioOtpService::REGISTER
```

---

## 13. Fat Models Are Also Bad

Models should contain only:

- Relationships
- Scopes
- Accessors
- Mutators
- Small helper methods

Business logic belongs to Actions.

---

## 14. Use Resources for API Responses

Every API should return:

```
Resource
Collection
```

Never expose raw models.

---

## 15. Keep Methods Small

A method should ideally do one thing.

If a method exceeds roughly 30–40 lines, consider extracting logic into smaller private methods or dedicated classes.

---

## 16. Naming Conventions

Actions:

```
CreateUserAction
UpdateProfileAction
DeleteOrderAction
VerifyOtpAction
```

Requests:

```
LoginRequest
RegisterRequest
ResetPasswordRequest
```

Resources:

```
UserResource
OrderResource
```

Policies:

```
OrderPolicy
```

Events:

```
UserRegistered
```

Listeners:

```
SendWelcomeEmail
```

---

## 17. Never Catch Exceptions Without Handling Them

Avoid:

```php
catch(Exception $e){}
```

Always:

- log
- return meaningful response
- rethrow if needed

---

## 18. Keep Code DRY

Don't Repeat Yourself.

If you copy/paste code, you're probably missing an abstraction.

---

## 19. Prefer Constructor Injection

Dependencies belong in constructors.

Avoid passing services through methods unless necessary.

---

## 20. Write Readable Code

Prefer:

```php
$isPhoneVerified
```

over:

```php
$x
```

Code is read more often than it is written.

---

## 21. Follow SOLID Principles

Every new feature should respect:

- Single Responsibility
- Open/Closed
- Liskov Substitution
- Interface Segregation
- Dependency Inversion

---

## 22. Folder Structure

```
app
 ├── Actions
 ├── Http
 │    ├── Controllers
 │    ├── Requests
 │    └── Resources
 ├── Models
 ├── Services
 ├── Enums
 ├── Policies
 ├── Events
 ├── Listeners
 ├── Traits
 └── Exceptions
```

---

## Final Rule

If a controller starts growing, move code out immediately.

A controller should read like a story:

```
Validate →
Call Action →
Return Resource
```
