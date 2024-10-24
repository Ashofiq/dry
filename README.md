## Laravel Standard Coading

## Installation process

- composer update
- .env.example edit to .env
- .env file set database connection credantial
- php artisan migrate

# Don't repeat yourself (DRY)

Reuse code when you can. DRY is helping you to avoid duplication. Also, reuse Blade templates, use Eloquent scopes etc.

Bad:

```php
public function getActive(): Collection
{
    return $this->where('verified', 1)->whereNotNull('deleted_at')->get();
}

public function getArticles(): Collection
{
    return $this->whereHas('user', function ($query) {
            $query->where('verified', 1)->whereNotNull('deleted_at');
        })->get();
}

```

Good:

```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('verified', 1)->whereNotNull('deleted_at');
}

public function getActive(): Collection
{
    return $this->active()->get();
}

public function getArticles(): Collection
{
    return $this->whereHas('user', function ($query) {
            $query->active();
        })->get();
}
```


---
layout: default
title: Do not get data from the .env file directly
---

## Do not get data from the .env file directly

Pass the data to config files instead and then use the `config()` helper function to use the data in an application.

Bad:

```php
$apiKey = env('API_KEY');
```

Good:

```php
// config/api.php
'key' => env('API_KEY'),

// Use the data
$apiKey = config('api.key');
```

  
# Avoid fat controllers and write frequent queries in model.

Put all DB related logic into Eloquent models or into Repository classes if you're using Query Builder or raw SQL queries.

Bad:

```php
public function index(): View
{
    $clients = Client::verified()
        ->with(['orders' => function ($q) {
            $q->where('created_at', '>', Carbon::today()->subWeek());
        }])
        ->get();

    return view('index', ['clients' => $clients]);
}
```

Good:

```php
public function index(): View
{
    return view('index', ['clients' => $this->client->getWithNewOrders()]);
}

class Client extends Model
{
    public function getWithNewOrders(): Collection
    {
        return $this->verified()
            ->with(['orders' => function ($q) {
                $q->where('created_at', '>', Carbon::today()->subWeek());
            }])
            ->get();
    }
}
```

# Do not put JS and CSS in Blade templates and do not put any HTML in PHP classes

Bad:

```php
let article = `{% raw %}{{ json_encode($article) }}{% endraw %}`;
```

Better:

```php
<input id="article" type="hidden" value="@json($article)">

Or

<button class="js-fav-article" data-article="@json($article)">{% raw %}{{ $article->name }}{% endraw %}<button>
```

In a Javascript file:

```javascript
let article = $('#article').val();
```

The best way is to use specialized PHP to JS package to transfer the data.

# Avoid queries in Blade templates

### Do not execute queries in Blade templates and use eager loading (N + 1 problem)

Bad (for 100 users, 101 DB queries will be executed):

```php
@foreach (User::all() as $user)
{% raw %}   {{ $user->profile->name }}{% endraw %}
@endforeach
```

Good (for 100 users, 2 DB queries will be executed):

```php
$users = User::with('profile')->get();

...

@foreach ($users as $user)
{% raw %}   {{ $user->profile->name }}{% endraw %}
@endforeach
```

# Business logic should be in Service class

A controller must have only one responsibility, so move business logic from controllers to Service classes.

Bad:

```php
public function store(Request $request): Redirect
{
    if ($request->hasFile('image')) {
        $request->file('image')->move(public_path('images') . 'temp');
    }
    
    ....
}
```

Good:

```php
public function store(Request $request): Redirect
{
    $this->articleService->handleUploadedImage($request->file('image'));

    ....
}

class ArticleService
{
    public function handleUploadedImage($image): void
    {
        if (!is_null($image)) {
            $image->move(public_path('images') . 'temp');
        }
    }
}
```

# Use config helper

### Do not get data from the .env file directly

Pass the data to config files instead and then use the config() helper function to use the data in an application.

Bad:

```php
$apiKey = env('API_KEY');
```

Good:

```php
// config/api.php
'key' => env('API_KEY'),

// Use the data
$apiKey = config('api.key');
```


# Use IoC container for long term projects

new Class syntax creates tight coupling between classes and complicates testing. Use IoC container or facades instead.

Bad:

```php
$user = new User;
$user->create($request->validated());
```

Good:

```php
public function __construct(User $user)
{
    $this->user = $user;
}

....

$this->user->create($request->validated());
```


# Mass assignment

Bad:

```php
$article = new Article;
$article->title = $request->title;
$article->content = $request->content;
$article->verified = $request->verified;
// Add category to article
$article->category_id = $category->id;
$article->save();
```

Good:

```php
$category->article()->create($request->validated());
```

---
aside: false
---

# Follow Laravel naming conventions

 Follow [PSR standards](http://www.php-fig.org/psr/psr-2/).
 
 Also, follow naming conventions accepted by Laravel community:

| What                             | How                                                                       | Good                                    | Bad                                                 |
|----------------------------------|---------------------------------------------------------------------------|-----------------------------------------|-----------------------------------------------------|
| Controller                       | singular                                                                  | ArticleController                       | ~~ArticlesController~~                              |
| Route                            | plural                                                                    | articles/1                              | ~~article/1~~                                       |
| Named route                      | snake_case with dot notation                                              | users.show_active                       | ~~users.show-active, show-active-users~~            |
| Model                            | singular                                                                  | User                                    | ~~Users~~                                           |
| hasOne or belongsTo relationship | singular                                                                  | articleComment                          | ~~articleComments, article_comment~~                |
| All other relationships          | plural                                                                    | articleComments                         | ~~articleComment, article_comments~~                |
| Table                            | plural                                                                    | article_comments                        | ~~article_comment, articleComments~~                |
| Pivot table                      | singular model names in alphabetical order                                | article_user                            | ~~user_article, articles_users~~                    |
| Table column                     | snake_case without model name                                             | meta_title                              | ~~MetaTitle; article_meta_title~~                   |
| Model property                   | snake_case                                                                | $model->created_at                      | ~~$model->createdAt~~                               |
| Foreign key                      | singular model name with _id suffix                                       | article_id                              | ~~ArticleId, id_article, articles_id~~              |
| Primary key                      | -                                                                         | id                                      | ~~custom_id~~                                       |
| Migration                        | -                                                                         | 2017_01_01_000000_create_articles_table | ~~2017_01_01_000000_articles~~                      |
| Method                           | camelCase                                                                 | getAll                                  | ~~get_all~~                                         |
| Method in resource controller    | [table](https://laravel.com/docs/master/controllers#resource-controllers) | store                                   | ~~saveArticle~~                                     |
| Method in test class             | camelCase                                                                 | testGuestCannotSeeArticle               | ~~test_guest_cannot_see_article~~                   |
| Variable                         | camelCase                                                                 | $articlesWithAuthor                     | ~~$articles_with_author~~                           |
| Collection                       | descriptive, plural                                                       | $activeUsers = User::active()->get()    | ~~$active, $data~~                                  |
| Object                           | descriptive, singular                                                     | $activeUser = User::active()->first()   | ~~$users, $obj~~                                    |
| Config and language files index  | snake_case                                                                | articles_enabled                        | ~~ArticlesEnabled; articles-enabled~~               |
| View                             | snake_case                                                                | show_filtered.blade.php                 | ~~showFiltered.blade.php, show-filtered.blade.php~~ |
| Config                           | snake_case                                                                | google_calendar.php                     | ~~googleCalendar.php, google-calendar.php~~         |
| Contract (interface)             | adjective or noun                                                         | Authenticatable                         | ~~AuthenticationInterface, IAuthentication~~        |
| Trait                            | adjective                                                                 | Notifiable                              | ~~NotificationTrait~~                               |


# Use PHP Type declaration
PHP supports type declarations since PHP 7, and it's a good practice to utilize them when defining types in Laravel.

Bad:

```php
public function calculateTotal($quantity, $price)
{
    return $quantity * $price;
}
```

Good:

```php
public function calculateTotal(int $quantity, float $price): float
{
    return $quantity * $price;
}
```

**Class type declaration**
```php
public function saveUser(User $user): void
{
    ...
}
```

**DocBlocks as type declaration**
```php
/**
 * Calculate the total.
 *
 * @param int $quantity
 * @param float $price
 * @return float
 */
public function calculateTotal(int $quantity, float $price): float
{
    return $quantity * $price;
}

```

# Prefer Eloquent and Laravel Collections

## Prefer to use Eloquent over using Query Builder and raw SQL queries.

Eloquent allows you to write readable and maintainable code. Also, Eloquent has great built-in tools like soft deletes, events, scopes etc.

Bad:

```sql
SELECT *
FROM `articles`
WHERE EXISTS (SELECT *
              FROM `users`
              WHERE `articles`.`user_id` = `users`.`id`
              AND EXISTS (SELECT *
                          FROM `profiles`
                          WHERE `profiles`.`user_id` = `users`.`id`) 
              AND `users`.`deleted_at` IS NULL)
AND `verified` = '1'
AND `active` = '1'
ORDER BY `created_at` DESC
```

Good:

```php
Article::has('user.profile')->verified()->latest()->get();
```

## Prefer collections to arrays
Using collection methods, we abstract away the iteration and filtering logic, making the code more expressive and easier to understand. Laravel collections offer numerous methods, such as map, pluck, groupBy, sum, etc., which makes it easy to perform various data manipulations without having to write custom code.

Bad:

```php
$products = ['pin', 'pen', 'pencil', 'paper'];

$isProductEmpty = count($products) === 0
```

Good:

```php
$products = collect(['pin', 'pen', 'pencil', 'paper']);

$isProductEmpty = $products->isEmpty();
```

# Readable and descriptive variable names
When declaring variable names in Laravel (or any programming language), it's essential to prioritize readability and clarity to make your code easier to understand. Meaningful and descriptive variable names enhance the maintainability and readability of your codebase.

## Use descriptive variable names
Choose names that clearly convey the purpose or content of the variable. Avoid using single-letter or abbreviated names, unless they are widely accepted and universally understood, like $i for a loop counter.

Bad:

```php
$un = 'john_doe'; // unclear abbreviation
$tic = 10;        // unclear abbreviation
```

Good:

```php
$username = 'john_doe';
$totalItemCount = 10;
```

## Be consistent
Maintain consistency in naming conventions throughout your codebase. If you use camelCase for variables, stick with it consistently.

Bad:

```php
$first_name = 'John'; // Mixing camelCase and snake_case
$LASTNAME = 'Doe';    // Inconsistent casing
```

Good:

```php
$firstName = 'John';
$lastName = 'Doe';
```

## Use meaningful variable names
If a variable's purpose might not be immediately apparent from its name, provide additional context or comments to clarify its role.

Bad:

```php
$val = 123; // What does this represent?
$flag = true; // What is this flag for?
```

Good:

```php
$currentPostId = 123; // ID of the currently displayed blog post
$isActive = true;     // Flag indicating whether the user is active
```

## Avoid Using Reserved Words
Be cautious not to use PHP reserved words or names of built-in functions as variable names.

```php
// Bad - 'unset' is a reserved word in PHP
$unset = 'Some value';
```

---
aside: false
---

# Use shorter and more readable syntax where possible

Bad:

```php
$request->session()->get('cart');
$request->input('name');
```

Good:

```php
session('cart');
$request->name;
```

### More examples:

| Common syntax                                                          | Shorter and more readable syntax                   |
|------------------------------------------------------------------------|----------------------------------------------------|
| `Session::get('cart')`                                                 | `session('cart')`                                  |
| `$request->session()->get('cart')`                                     | `session('cart')`                                  |
| `Session::put('cart', $data)`                                          | `session(['cart' => $data])`                       |
| `$request->input('name'), Request::get('name')`                        | `$request->name, request('name')`                  |
| `return Redirect::back()`                                              | `return back()`                                    |
| `is_null($object->relation) ? null : $object->relation->id`            | `optional($object->relation)->id`                  |
| `return view('index')->with('title', $title)->with('client', $client)` | `return view('index', compact('title', 'client'))` |
| `$request->has('value') ? $request->value : 'default';`                | `$request->get('value', 'default')`                |
| `Carbon::now(), Carbon::today()`                                       | `now(), today()`                                   |
| `App::make('Class')`                                                   | `app('Class')`                                     |
| `->where('column', '=', 1)`                                            | `->where('column', 1)`                             |
| `->orderBy('created_at', 'desc')`                                      | `->latest()`                                       |
| `->orderBy('age', 'desc')`                                             | `->latest('age')`                                  |
| `->orderBy('created_at', 'asc')`                                       | `->oldest()`                                       |
| `->select('id', 'name')->get()`                                        | `->get(['id', 'name'])`                            |
| `->first()->name`                                                      | `->value('name')`                                  |


# Single responsibility principle

A class and a method should have only one responsibility.

Example:

```php
class User
{
    public function create($userData)
    {
        // Code to create a user
    }

    public function sendWelcomeEmail($userEmail)
    {
        // Code to send a welcome email
    }
}
```

Class :

```php
class UserService
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function create($userData)
    {
        // Code to create a user
        $this->emailService->sendWelcomeEmail($userData['email']);
    }
}

class EmailService
{
    public function sendWelcomeEmail($userEmail)
    {
        // Code to send a welcome email
    }
}

```


# Use Accessors and Mutators

Use Accessors and Mutators instead of mutating in controllers and blade

Accessor:

```php
// Accessor for the 'name' attribute
public function getNameAttribute($value)
{
    return ucfirst($value);
}

$user = User::find(1);
echo $user->name; // Outputs: "John" if the name in the database is "john"

```
Mutator:

```php
// Mutator for the 'email' attribute
public function setEmailAttribute($value)
{
    $this->attributes['email'] = strtolower($value);
}

$user = new User();
$user->email = 'JohnDoe@Example.com';
$user->save();
```

# Use constants and language helper

Use config and language files, constants instead of text in the code

Bad:

```php
public function isNormal(): bool
{
    return $article->type === 'normal';
}

return back()->with('message', 'Your article has been added!');
```

Good:

```php
public function isNormal(): bool
{
    return $article->type === Article::TYPE_NORMAL;
}

return back()->with('message', __('app.article_added'));
```

# Use Request class for validations

### Move validation from controllers to Request classes.

Bad:

```php
public function store(Request $request): Redirect
{
    $request->validate([
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
        'publish_at' => 'nullable|date',
    ]);

    ...
}
```

Good:

```php
public function store(PostRequest $request): Redirect
{
    ...
}

class PostRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => 'required|unique:posts|max:255',
            'body' => 'required',
            'publish_at' => 'nullable|date',
        ];
    }
}
```

## Issues
If you come across any issues please report them https://github.com/Ashofiq/dry/issues.

## Contributing
Thank you for considering contributing to the Laravel Boilerplate project! Please feel free to make any pull requests, or e-mail me a feature request you would like to see in the future to Ahmad Shafik at ahmadshofiq3@gmail.com.


### Author
<a href="https://bd.linkedin.com/in/ahmad-shafik-392a71109">Ahmad shofiq</a>

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
