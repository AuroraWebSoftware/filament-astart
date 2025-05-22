TITLE: CSRF Token Form Implementation in Blade
DESCRIPTION: Example showing how to include CSRF token in HTML forms using Blade's @csrf directive or manual hidden input field.
SOURCE: https://github.com/laravel/docs/blob/12.x/csrf.md#2025-04-21_snippet_2

LANGUAGE: blade
CODE:
```
<form method="POST" action="/profile">
    @csrf

    <!-- Equivalent to... -->
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
</form>
```

----------------------------------------

TITLE: Assert JSON Validation Errors - Laravel PHP
DESCRIPTION: Asserts that the HTTP response has the given JSON validation errors for the specified `$data` keys, typically found under the `$responseKey` (default 'errors'). Use this for API responses returning errors in JSON.
SOURCE: https://github.com/laravel/docs/blob/12.x/http-tests.md#_snippet_73

LANGUAGE: php
CODE:
```
$response->assertJsonValidationErrors(array $data, $responseKey = 'errors');
```

----------------------------------------

TITLE: Handling Model Not Found Exception in Laravel Route (PHP)
DESCRIPTION: Illustrates how using `findOrFail` within a Laravel route closure automatically results in a 404 HTTP response if the model is not found. It shows a simple route definition that retrieves a Flight model by ID.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_45

LANGUAGE: php
CODE:
```
use App\Models\Flight;

Route::get('/api/flights/{id}', function (string $id) {
    return Flight::findOrFail($id);
});
```

----------------------------------------

TITLE: Creating Collections with collect() Helper - PHP
DESCRIPTION: The `collect` function creates a new `Illuminate\Support\Collection` instance from the provided value, making it easy to work with arrays and other iterable data.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_113

LANGUAGE: php
CODE:
```
$collection = collect(['taylor', 'abigail']);
```

----------------------------------------

TITLE: Defining a Route to a Controller Method
DESCRIPTION: Example of how to define a Laravel route that points to a specific controller method. This connects the /user/{id} URL pattern to the show method in the UserController.
SOURCE: https://github.com/laravel/docs/blob/12.x/controllers.md#2025-04-21_snippet_2

LANGUAGE: php
CODE:
```
use App\Http\Controllers\UserController;

Route::get('/user/{id}', [UserController::class, 'show']);
```

----------------------------------------

TITLE: Displaying Variable Data in Blade Template
DESCRIPTION: This Blade snippet demonstrates the basic syntax `{{ $variable }}` to display the value of a variable passed to the template. Blade automatically escapes the output using `htmlspecialchars` to prevent XSS attacks.
SOURCE: https://github.com/laravel/docs/blob/12.x/blade.md#_snippet_2

LANGUAGE: blade
CODE:
```
Hello, {{ $name }}.
```

----------------------------------------

TITLE: Example: Asserting Response Has No Validation Errors in Laravel PHP
DESCRIPTION: Assert that the response has no validation errors for the given keys. This method may be used for asserting against responses where the validation errors are returned as a JSON structure or where the validation errors have been flashed to the session. Assert that no validation errors are present...
SOURCE: https://github.com/laravel/docs/blob/12.x/http-tests.md#_snippet_115

LANGUAGE: php
CODE:
```
$response->assertValid();
```

----------------------------------------

TITLE: Generating URLs for Named Routes using route Helper PHP
DESCRIPTION: The `route` helper generates URLs for named routes. Pass the route name and an associative array of parameters. Parameters that match route segments are injected, and additional parameters are added as a query string. Eloquent models can also be passed for route key extraction.
SOURCE: https://github.com/laravel/docs/blob/12.x/urls.md#_snippet_5

LANGUAGE: php
CODE:
```
echo route('post.show', ['post' => 1]);

// http://example.com/post/1
```

LANGUAGE: php
CODE:
```
echo route('comment.show', ['post' => 1, 'comment' => 3]);

// http://example.com/post/1/comment/3
```

LANGUAGE: php
CODE:
```
echo route('post.show', ['post' => 1, 'search' => 'rocket']);

// http://example.com/post/1?search=rocket
```

LANGUAGE: php
CODE:
```
echo route('post.show', ['post' => $post]);
```

----------------------------------------

TITLE: Installing Laravel Installer via Composer
DESCRIPTION: Global Composer command to install the Laravel installer tool.
SOURCE: https://github.com/laravel/docs/blob/12.x/installation.md#2025-04-21_snippet_3

LANGUAGE: shell
CODE:
```
composer global require laravel/installer
```

----------------------------------------

TITLE: Configuring MySQL Database Connection
DESCRIPTION: Environment configuration variables for setting up MySQL database connection in Laravel.
SOURCE: https://github.com/laravel/docs/blob/12.x/installation.md#2025-04-21_snippet_5

LANGUAGE: ini
CODE:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

----------------------------------------

TITLE: Retrieving or Creating/Instantiating Eloquent Models (PHP)
DESCRIPTION: Provides examples of using Eloquent's `firstOrCreate` and `firstOrNew` methods. `firstOrCreate` finds a record or creates and persists it, while `firstOrNew` finds a record or creates a new model instance without persisting it immediately.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_46

LANGUAGE: php
CODE:
```
use App\Models\Flight;

// Retrieve flight by name or create it if it doesn't exist...
$flight = Flight::firstOrCreate([
    'name' => 'London to Paris'
]);

// Retrieve flight by name or create it with the name, delayed, and arrival_time attributes...
$flight = Flight::firstOrCreate(
    ['name' => 'London to Paris'],
    ['delayed' => 1, 'arrival_time' => '11:30']
);

// Retrieve flight by name or instantiate a new Flight instance...
$flight = Flight::firstOrNew([
    'name' => 'London to Paris'
]);

// Retrieve flight by name or instantiate with the name, delayed, and arrival_time attributes...
$flight = Flight::firstOrNew(
    ['name' => 'Tokyo to Sydney'],
    ['delayed' => 1, 'arrival_time' => '11:30']
);
```

----------------------------------------

TITLE: Retrieving All Users using Laravel Query Builder (PHP)
DESCRIPTION: Demonstrates how to fetch all records from the 'users' table using the DB facade's table method and the get method within a Laravel controller. The results are passed to a view.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_0

LANGUAGE: php
CODE:
```
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Show a list of all of the application's users.
     */
    public function index(): View
    {
        $users = DB::table('users')->get();

        return view('user.index', ['users' => $users]);
    }
}
```

----------------------------------------

TITLE: Retrieving Value by Dot Notation - Arr::get - PHP
DESCRIPTION: The Arr::get method retrieves a value from a deeply nested array using dot notation.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_16

LANGUAGE: php
CODE:
```
use Illuminate\Support\Arr;

$array = ['products' => ['desk' => ['price' => 100]]];

$price = Arr::get($array, 'products.desk.price');

// 100
```

----------------------------------------

TITLE: Accessing HTTP Request in Laravel Route Closure
DESCRIPTION: Shows how to type-hint the Request class in a route closure to access the current HTTP request. The service container automatically injects the request object.
SOURCE: https://github.com/laravel/docs/blob/12.x/requests.md#2025-04-21_snippet_1

LANGUAGE: php
CODE:
```
use Illuminate\Http\Request;

Route::get('/', function (Request $request) {
    // ...
});
```

----------------------------------------

TITLE: Deleting Eloquent Model Instance (PHP)
DESCRIPTION: Call the `delete` method on a retrieved model instance to remove it from the database. This method dispatches the `deleting` and `deleted` model events.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_65

LANGUAGE: php
CODE:
```
use App\Models\Flight;

$flight = Flight::find(1);

$flight->delete();
```

----------------------------------------

TITLE: Retrieving and Caching Data with remember()
DESCRIPTION: Demonstrates using Cache::remember() to retrieve or store data in cache with expiration time. If item doesn't exist, retrieves from database and caches it.
SOURCE: https://github.com/laravel/docs/blob/12.x/cache.md#2025-04-21_snippet_11

LANGUAGE: php
CODE:
```
$value = Cache::remember('users', $seconds, function () {
    return DB::table('users')->get();
});
```

----------------------------------------

TITLE: Retrieving the Authenticated User in Laravel
DESCRIPTION: Shows how to access the currently authenticated user using the Auth facade. Demonstrates retrieving the user object and user ID from the authentication system.
SOURCE: https://github.com/laravel/docs/blob/12.x/authentication.md#2025-04-21_snippet_0

LANGUAGE: php
CODE:
```
use Illuminate\Support\Facades\Auth;

// Retrieve the currently authenticated user...
$user = Auth::user();

// Retrieve the currently authenticated user's ID...
$id = Auth::id();
```

----------------------------------------

TITLE: Basic Laravel Controller Implementation
DESCRIPTION: Example of a basic Laravel controller with a show method that displays a user profile. The controller retrieves a user by ID and returns a view with the user data.
SOURCE: https://github.com/laravel/docs/blob/12.x/controllers.md#2025-04-21_snippet_1

LANGUAGE: php
CODE:
```
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Show the profile for a given user.
     */
    public function show(string $id): View
    {
        return view('user.profile', [
            'user' => User::findOrFail($id)
        ]);
    }
}
```

----------------------------------------

TITLE: Paginating Query Builder Results in Laravel Controller
DESCRIPTION: This PHP code demonstrates how to use the paginate method in a Laravel controller to paginate database query results and pass them to a view.
SOURCE: https://github.com/laravel/docs/blob/12.x/pagination.md#2025-04-21_snippet_1

LANGUAGE: php
CODE:
```
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Show all application users.
     */
    public function index(): View
    {
        return view('user.index', [
            'users' => DB::table('users')->paginate(15)
        ]);
    }
}
```

----------------------------------------

TITLE: Attaching Many To Many Relationship with attach in PHP
DESCRIPTION: Shows how to attach a related model to a parent model in a many-to-many relationship using the `attach` method. This inserts a record into the intermediate pivot table.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_149

LANGUAGE: php
CODE:
```
use App\Models\User;

$user = User::find(1);

$user->roles()->attach($roleId);
```

----------------------------------------

TITLE: Specifying Columns with Laravel DB Select
DESCRIPTION: Shows how to use the `select` method on the Laravel query builder to specify which columns should be retrieved from a table, including aliasing columns using the 'as' keyword.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_17

LANGUAGE: php
CODE:
```
use Illuminate\Support\Facades\DB;

$users = DB::table('users')
    ->select('name', 'email as user_email')
    ->get();
```

----------------------------------------

TITLE: Generating CSRF Hidden Input with csrf_field() Helper - Blade
DESCRIPTION: The `csrf_field` function generates an HTML hidden input field containing the current CSRF token value, commonly used within HTML forms to protect against cross-site request forgery.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_117

LANGUAGE: blade
CODE:
```
{{ csrf_field() }}
```

----------------------------------------

TITLE: Using Str::before for String Extraction in PHP
DESCRIPTION: The Str::before method returns everything before the given value in a string, allowing you to extract the beginning portion of a string.
SOURCE: https://github.com/laravel/docs/blob/12.x/strings.md#2025-04-21_snippet_8

LANGUAGE: php
CODE:
```
use Illuminate\Support\Str;

$slice = Str::before('This is my name', 'my name');

// 'This is '
```

----------------------------------------

TITLE: Redirecting Back with Input in Laravel PHP
DESCRIPTION: Shows how to redirect the user back to their previous location with input data, typically used when form validation fails. This requires the 'web' middleware group or session middleware.
SOURCE: https://github.com/laravel/docs/blob/12.x/redirects.md#2025-04-21_snippet_1

LANGUAGE: php
CODE:
```
Route::post('/user/profile', function () {
    // Validate the request...

    return back()->withInput();
});
```

----------------------------------------

TITLE: Database Reset Test Example - Pest
DESCRIPTION: Example showing how to use RefreshDatabase trait in Pest tests to reset database state between tests.
SOURCE: https://github.com/laravel/docs/blob/12.x/database-testing.md#2025-04-21_snippet_0

LANGUAGE: php
CODE:
```
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('basic example', function () {
    $response = $this->get('/');

    // ...
});
```

----------------------------------------

TITLE: Retrieving a Single Input Value in Laravel
DESCRIPTION: Using the input method to retrieve a specific input value from the request by its name.
SOURCE: https://github.com/laravel/docs/blob/12.x/requests.md#2025-04-21_snippet_7

LANGUAGE: php
CODE:
```
$name = $request->input('name');
```

----------------------------------------

TITLE: Running Laravel Scheduler via Cron Shell
DESCRIPTION: Sets up a cron job entry on the server to execute the Laravel `schedule:run` Artisan command every minute. This is the standard method for production deployment to trigger all defined scheduled tasks.
SOURCE: https://github.com/laravel/docs/blob/12.x/scheduling.md#_snippet_31

LANGUAGE: Shell
CODE:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

----------------------------------------

TITLE: Inserting New Eloquent Model with Create Method (PHP)
DESCRIPTION: Shows how to use the static `create` method on an Eloquent model to insert a new record using mass assignment. This method requires the model to have `fillable` or `guarded` properties configured to prevent mass assignment vulnerabilities. It returns the newly created model instance.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_49

LANGUAGE: php
CODE:
```
use App\Models\Flight;

$flight = Flight::create([
    'name' => 'London to Paris',
]);
```

----------------------------------------

TITLE: Redirecting Back with Input (PHP)
DESCRIPTION: Redirects the user back to their previous location using the global `back` helper function, typically used after form submissions, and flashes the current request's input to the session. Requires the `web` middleware group.
SOURCE: https://github.com/laravel/docs/blob/12.x/responses.md#_snippet_15

LANGUAGE: php
CODE:
```
Route::post('/user/profile', function () {
    // Validate the request...

    return back()->withInput();
});
```

----------------------------------------

TITLE: Adding Soft Deletes Column to Database Table (PHP)
DESCRIPTION: Use the Laravel schema builder's `softDeletes` helper method within a migration to add the `deleted_at` timestamp column required for soft deletion.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_71

LANGUAGE: php
CODE:
```
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('flights', function (Blueprint $table) {
    $table->softDeletes();
});
```

----------------------------------------

TITLE: Defining Mailable Envelope with Sender and Subject in Laravel PHP
DESCRIPTION: Implements the `envelope` method within a mailable class to return an `Illuminate\Mail\Mailables\Envelope` object. This object is configured with the email's sender using an `Address` object and sets the subject line, defining basic email metadata.
SOURCE: https://github.com/laravel/docs/blob/12.x/mail.md#_snippet_18

LANGUAGE: php
CODE:
```
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Get the message envelope.
 */
public function envelope(): Envelope
{
    return new Envelope(
        from: new Address('jeffrey@example.com', 'Jeffrey Way'),
        subject: 'Order Shipped',
    );
}
```

----------------------------------------

TITLE: Generating Redirect Response PHP
DESCRIPTION: Shows various ways to use the `redirect` helper function to generate HTTP redirect responses. It can return the redirector instance or a response object configured with a target URL, status code, headers, and HTTPS setting.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_142

LANGUAGE: php
CODE:
```
return redirect($to = null, $status = 302, $headers = [], $https = null);

return redirect('/home');

return redirect()->route('route.name');
```

----------------------------------------

TITLE: Accessing Parent Model via BelongsTo Relationship in Laravel PHP
DESCRIPTION: Demonstrates how to retrieve the parent model (`Post`) from a child model instance (`Comment`) by accessing the relationship method as a dynamic property. Eloquent automatically loads the related model.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_9

LANGUAGE: php
CODE:
```
use App\Models\Comment;

$comment = Comment::find(1);

return $comment->post->title;
```

----------------------------------------

TITLE: Eager Loading Multiple Eloquent Relationships (PHP)
DESCRIPTION: Demonstrates how to eager load multiple relationships (`author` and `publisher`) on a model by passing an array of relationship names to the `with` method.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_117

LANGUAGE: php
CODE:
```
$books = Book::with(['author', 'publisher'])->get();
```

----------------------------------------

TITLE: Defining Basic Route in Laravel
DESCRIPTION: Demonstrates how to define a simple route in Laravel that responds to a GET request. The route accepts a URI and a closure, providing a simple method of defining routes and behavior.
SOURCE: https://github.com/laravel/docs/blob/12.x/routing.md#2025-04-23_snippet_0

LANGUAGE: php
CODE:
```
use Illuminate\Support\Facades\Route;

Route::get('/greeting', function () {
    return 'Hello World';
});
```

----------------------------------------

TITLE: Verifying Password Matches Hash in Laravel
DESCRIPTION: Using the Hash facade's check method to verify that a plain-text password matches a hashed password. This is commonly used in authentication systems to validate user credentials.
SOURCE: https://github.com/laravel/docs/blob/12.x/hashing.md#2025-04-21_snippet_4

LANGUAGE: php
CODE:
```
if (Hash::check('plain-text', $hashedPassword)) {
    // The passwords match...
}
```

----------------------------------------

TITLE: Retrieving All Input Data in Laravel Request
DESCRIPTION: Methods to retrieve all input data from an HTTP request as either an array or collection.
SOURCE: https://github.com/laravel/docs/blob/12.x/requests.md#2025-04-21_snippet_4

LANGUAGE: php
CODE:
```
$input = $request->all();
```

----------------------------------------

TITLE: Creating Model with Mass Assignment (PHP)
DESCRIPTION: Demonstrates using the `create` method to save a new model instance using mass assignment. This method returns the newly created model.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_57

LANGUAGE: php
CODE:
```
use App\Models\Flight;

$flight = Flight::create([
    'name' => 'London to Paris',
]);
```

----------------------------------------

TITLE: Trimming Whitespace from a String in Laravel
DESCRIPTION: The trim method removes whitespace from both ends of a string. It can also remove specified characters. Unlike PHP's native trim function, Laravel's trim method also removes unicode whitespace characters.
SOURCE: https://github.com/laravel/docs/blob/12.x/strings.md#2025-04-21_snippet_149

LANGUAGE: php
CODE:
```
use Illuminate\Support\Str;

$string = Str::of('  Laravel  ')->trim();

// 'Laravel'

$string = Str::of('/Laravel/')->trim('/');

// 'Laravel'
```

----------------------------------------

TITLE: Querying Eloquent Relationship with Constraints (PHP)
DESCRIPTION: Demonstrates how to chain additional query constraints onto an Eloquent relationship method to filter related models. This example filters a user's posts to only include active ones.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_0

LANGUAGE: php
CODE:
```
$user->posts()->where('active', 1)->get();
```

----------------------------------------

TITLE: Filtering Records with Implicit '=' Operator (PHP)
DESCRIPTION: Shows the convenient syntax for the `where` method when checking for equality. By passing only the column name and value, Laravel assumes the `=` operator. It retrieves users where votes equal 100.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_35

LANGUAGE: php
CODE:
```
$users = DB::table('users')->where('votes', 100)->get();
```

----------------------------------------

TITLE: Defining Many-to-Many Relationship in Laravel Eloquent (PHP)
DESCRIPTION: Defines a many-to-many relationship from the `User` model to the `Role` model using the `belongsToMany` method. This method is provided by the Eloquent Model base class and establishes the link between the two models via an intermediate table.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_37

LANGUAGE: php
CODE:
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

----------------------------------------

TITLE: Filtering Records with Multiple 'where' Clauses (PHP)
DESCRIPTION: Demonstrates how to chain multiple `where` clauses using the `=` and `>` operators to filter users based on votes and age. It retrieves users where votes equal 100 AND age is greater than 35.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_34

LANGUAGE: php
CODE:
```
$users = DB::table('users')
    ->where('votes', '=', 100)
    ->where('age', '>', 35)
    ->get();
```

----------------------------------------

TITLE: Applying Reusable Query Component with Laravel tap (PHP)
DESCRIPTION: This snippet demonstrates how to use the `tap` method on Laravel's query builder to apply the reusable `DestinationFilter` component. Instead of repeating the `when` clause, an instance of the filter class is passed to `tap`, which invokes the `__invoke` method on the query builder instance, applying the encapsulated filtering logic.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_108

LANGUAGE: php
CODE:
```
use App\Scopes\DestinationFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

DB::table('flights')
    ->when($destination, function (Builder $query, string $destination) { // [tl! remove]
        $query->where('destination', $destination); // [tl! remove]
    }) // [tl! remove]
    ->tap(new DestinationFilter($destination)) // [tl! add]
    ->orderByDesc('price')
    ->get();

// ...

DB::table('flights')
    ->when($destination, function (Builder $query, string $destination) { // [tl! remove]
        $query->where('destination', $destination); // [tl! remove]
    }) // [tl! remove]
    ->tap(new DestinationFilter($destination)) // [tl! add]
    ->where('user', $request->user()->id)
    ->orderBy('destination')
    ->get();
```

----------------------------------------

TITLE: Creating Carbon Instance with Global Helper (PHP)
DESCRIPTION: Demonstrates using the globally available `now()` helper function to create a new `Carbon` instance representing the current date and time.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_176

LANGUAGE: php
CODE:
```
$now = now();
```

----------------------------------------

TITLE: Defining Fillable Attributes on Model (PHP)
DESCRIPTION: Shows how to define the `$fillable` property on an Eloquent model to specify which attributes are allowed for mass assignment, protecting against vulnerabilities.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent.md#_snippet_58

LANGUAGE: php
CODE:
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name'];
}
```

----------------------------------------

TITLE: Dumping Variables and Exiting with dd() Helper - PHP
DESCRIPTION: The `dd` function (dump and die) outputs the given variables to the browser or console and then halts script execution. It's useful for debugging.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_120

LANGUAGE: php
CODE:
```
dd($value);

dd($value1, $value2, $value3, ...);
```

----------------------------------------

TITLE: Checking User Authentication Status in Laravel
DESCRIPTION: Shows how to determine if the current user is authenticated using the Auth facade's check method. This method returns true if a user is logged in.
SOURCE: https://github.com/laravel/docs/blob/12.x/authentication.md#2025-04-21_snippet_2

LANGUAGE: php
CODE:
```
use Illuminate\Support\Facades\Auth;

if (Auth::check()) {
    // The user is logged in...
}
```

----------------------------------------

TITLE: Make Eloquent Model Searchable in PHP
DESCRIPTION: To make an Eloquent model searchable with Laravel Scout, you must add the `Laravel\Scout\Searchable` trait to the model class. This trait registers a model observer that automatically keeps the search index in sync with model changes.
SOURCE: https://github.com/laravel/docs/blob/12.x/scout.md#_snippet_2

LANGUAGE: PHP
CODE:
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;
}
```

----------------------------------------

TITLE: Protecting Routes with Authentication Middleware
DESCRIPTION: Demonstrates how to use Laravel's auth middleware to restrict route access to only authenticated users. This is the recommended way to protect routes that require authentication.
SOURCE: https://github.com/laravel/docs/blob/12.x/authentication.md#2025-04-21_snippet_3

LANGUAGE: php
CODE:
```
Route::get('/flights', function () {
    // Only authenticated users may access this route...
})->middleware('auth');
```

----------------------------------------

TITLE: Accessing HasMany Relationship as a Property in Laravel
DESCRIPTION: Demonstrates how to access the collection of related models (comments) by treating the relationship method as a dynamic property on the parent model instance.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_2

LANGUAGE: php
CODE:
```
use App\Models\Post;

$comments = Post::find(1)->comments;

foreach ($comments as $comment) {
    // ...
}
```

----------------------------------------

TITLE: Defining Controller Route in Laravel
DESCRIPTION: Shows how to define a route that points to a controller method in Laravel. This example maps the /user URI to the index method of the UserController.
SOURCE: https://github.com/laravel/docs/blob/12.x/routing.md#2025-04-23_snippet_1

LANGUAGE: php
CODE:
```
use App\Http\Controllers\UserController;

Route::get('/user', [UserController::class, 'index']);
```

----------------------------------------

TITLE: Conditionally Applying CSS Class based on Validation Error (Blade)
DESCRIPTION: Shows how to use the `@error` directive with an `@else` clause to conditionally apply different CSS classes (`is-invalid` or `is-valid`) to an input field based on whether validation errors exist for that specific attribute.
SOURCE: https://github.com/laravel/docs/blob/12.x/blade.md#_snippet_114

LANGUAGE: Blade
CODE:
```
<!-- /resources/views/auth.blade.php -->

<label for="email">Email address</label>

<input
    id="email"
    type="email"
    class="@error('email') is-invalid @else is-valid @enderror"
/>
```

----------------------------------------

TITLE: Creating Foreign Key Constraints in Laravel Migrations
DESCRIPTION: Demonstrates the verbose syntax for creating a foreign key constraint in Laravel migrations. Creates a user_id column that references the id column on the users table.
SOURCE: https://github.com/laravel/docs/blob/12.x/migrations.md#2025-04-23_snippet_59

LANGUAGE: php
CODE:
```
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('posts', function (Blueprint $table) {
    $table->unsignedBigInteger('user_id');

    $table->foreign('user_id')->references('id')->on('users');
});
```

----------------------------------------

TITLE: Updating Records with Laravel Query Builder
DESCRIPTION: The `update` method modifies existing records in a table. It accepts an array of column-value pairs to update and returns the number of affected rows. You can use `where` clauses to constrain which records are updated.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_95

LANGUAGE: php
CODE:
```
$affected = DB::table('users')
    ->where('id', 1)
    ->update(['votes' => 1]);
```

----------------------------------------

TITLE: Asserting HTTP Status Code in Laravel PHP
DESCRIPTION: Assert that the response has a given HTTP status code.
SOURCE: https://github.com/laravel/docs/blob/12.x/http-tests.md#_snippet_109

LANGUAGE: php
CODE:
```
$response->assertStatus($code);
```

----------------------------------------

TITLE: Checking Input Presence in Laravel with has Method
DESCRIPTION: Determining if a specific input value exists in the request using the has method, which returns a boolean.
SOURCE: https://github.com/laravel/docs/blob/12.x/requests.md#2025-04-21_snippet_25

LANGUAGE: php
CODE:
```
if ($request->has('name')) {
    // ...
}
```

----------------------------------------

TITLE: Redirecting Back with Input in Laravel PHP
DESCRIPTION: Redirect the user back to the previous page while flashing the current request's input data to the session using `back()->withInput()`. Useful for repopulating forms after validation errors.
SOURCE: https://github.com/laravel/docs/blob/12.x/responses.md#_snippet_25

LANGUAGE: php
CODE:
```
return back()->withInput();
```

----------------------------------------

TITLE: Creating Carbon Instance for Current Time with now() - PHP
DESCRIPTION: The `now` function creates and returns a new `Illuminate\Support\Carbon` instance representing the current date and time.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_134

LANGUAGE: php
CODE:
```
$now = now();
```

----------------------------------------

TITLE: Removing Suffix from String in Laravel PHP
DESCRIPTION: The chopEnd method removes the last occurrence of a given value only if it appears at the end of the string.
SOURCE: https://github.com/laravel/docs/blob/12.x/strings.md#2025-04-21_snippet_93

LANGUAGE: php
CODE:
```
use Illuminate\Support\Str;

$url = Str::of('https://laravel.com')->chopEnd('.com');

// 'https://laravel'
```

----------------------------------------

TITLE: Filtering Records with Grouped 'orWhere' Clause (PHP)
DESCRIPTION: Explains how to use a closure with `orWhere` to group conditions within parentheses, ensuring correct logical grouping (e.g., `OR (condition1 AND condition2)`). It retrieves users where votes are greater than 100 OR (name is 'Abigail' AND votes are greater than 50). Requires importing `Illuminate\Database\Query\Builder`.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_39

LANGUAGE: php
CODE:
```
use Illuminate\Database\Query\Builder; 

$users = DB::table('users')
    ->where('votes', '>', 100)
    ->orWhere(function (Builder $query) {
        $query->where('name', 'Abigail')
            ->where('votes', '>', 50);
        })
    ->get();
```

----------------------------------------

TITLE: Storing Uploaded Files in Laravel
DESCRIPTION: Demonstrates storing uploaded files using Laravel's filesystem with both automatic and custom filenames.
SOURCE: https://github.com/laravel/docs/blob/12.x/requests.md#2025-04-21_snippet_46

LANGUAGE: php
CODE:
```
$path = $request->photo->store('images');

$path = $request->photo->store('images', 's3');

$path = $request->photo->storeAs('images', 'filename.jpg');

$path = $request->photo->storeAs('images', 'filename.jpg', 's3');
```

----------------------------------------

TITLE: Saving Related Model using Eloquent save in PHP
DESCRIPTION: Demonstrates how to associate and persist a new related model (Comment) with a parent model (Post) using the relationship's `save` method. This automatically sets the foreign key on the related model.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_139

LANGUAGE: php
CODE:
```
use App\Models\Comment;
use App\Models\Post;

$comment = new Comment(['message' => 'A new comment.']);

$post = Post::find(1);

$post->comments()->save($comment);
```

----------------------------------------

TITLE: Retrieving Single Row or Throwing Exception (PHP)
DESCRIPTION: Demonstrates using the firstOrFail method to retrieve a single record based on a condition. If no record is found, it automatically throws an Illuminate\Database\RecordNotFoundException.
SOURCE: https://github.com/laravel/docs/blob/12.x/queries.md#_snippet_3

LANGUAGE: php
CODE:
```
$user = DB::table('users')->where('name', 'John')->firstOrFail();
```

----------------------------------------

TITLE: Defining Eloquent Book Model with BelongsTo Relationship (PHP)
DESCRIPTION: Defines a simple Eloquent `Book` model with a `belongsTo` relationship method named `author` that returns the related `Author` model. This model is used to illustrate the N+1 query problem.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_113

LANGUAGE: php
CODE:
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    /**
     *
     * Get the author that wrote the book.
     *
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
```

----------------------------------------

TITLE: Solving N+1 Problem with Eager Loading Relationship (PHP)
DESCRIPTION: Resolves the N+1 query problem by eager loading the `author` relationship when retrieving `Book` models using the `with` method. This reduces the number of queries to two: one for books and one for all related authors.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_115

LANGUAGE: php
CODE:
```
$books = Book::with('author')->get();

foreach ($books as $book) {
    echo $book->author->name;
}
```

----------------------------------------

TITLE: Sending Laravel Notification via Notifiable Trait (PHP)
DESCRIPTION: Once a model uses the `Notifiable` trait, you can send a notification to an instance of that model by calling the `notify` method and passing a notification instance.
SOURCE: https://github.com/laravel/docs/blob/12.x/notifications.md#_snippet_2

LANGUAGE: php
CODE:
```
use App\Notifications\InvoicePaid;

$user->notify(new InvoicePaid($invoice));
```

----------------------------------------

TITLE: Using Blade Directives for Method Spoofing and CSRF Protection
DESCRIPTION: This snippet demonstrates how to use Blade's @method and @csrf directives to simplify HTTP method spoofing and CSRF protection in forms. These directives generate the necessary hidden input fields.
SOURCE: https://github.com/laravel/docs/blob/12.x/routing.md#2025-04-23_snippet_43

LANGUAGE: blade
CODE:
```
<form action="/example" method="POST">
    @method('PUT')
    @csrf
</form>
```

----------------------------------------

TITLE: Accessing JSON Response Data (Pest/PHPUnit)
DESCRIPTION: Shows how to access individual values within the JSON response data returned by an API request. The response object allows accessing JSON properties as array elements for easy inspection and assertion.
SOURCE: https://github.com/laravel/docs/blob/12.x/http-tests.md#_snippet_17

LANGUAGE: php
CODE:
```
expect($response['created'])->toBeTrue();
```

LANGUAGE: php
CODE:
```
$this->assertTrue($response['created']);
```

----------------------------------------

TITLE: Generate Asset URL in Laravel PHP
DESCRIPTION: Illustrates using the `asset()` helper function to generate a URL for an application asset, respecting the current request scheme (HTTP/HTTPS). Mentions configuration via `ASSET_URL`.
SOURCE: https://github.com/laravel/docs/blob/12.x/helpers.md#_snippet_95

LANGUAGE: php
CODE:
```
$url = asset('img/photo.jpg');
```

LANGUAGE: php
CODE:
```
// ASSET_URL=http://example.com/assets

$url = asset('img/photo.jpg'); // http://example.com/assets/img/photo.jpg
```

----------------------------------------

TITLE: Querying Many-to-Many Relationship in Laravel Eloquent (PHP)
DESCRIPTION: Shows how to treat the relationship method (`$user->roles()`) as a query builder to add constraints (like `orderBy`) before retrieving the related models. This allows filtering or ordering the related data.
SOURCE: https://github.com/laravel/docs/blob/12.x/eloquent-relationships.md#_snippet_39

LANGUAGE: php
CODE:
```
$roles = User::find(1)->roles()->orderBy('name')->get();
```

----------------------------------------

TITLE: Checking for Guest User with Blade @guest Directive
DESCRIPTION: This Blade snippet shows the `@guest` directive. The content between `@guest` and `@endguest` is displayed only if there is no currently logged-in user (the user is a guest) using the default authentication guard.
SOURCE: https://github.com/laravel/docs/blob/12.x/blade.md#_snippet_17

LANGUAGE: blade
CODE:
```
@guest
    // The user is not authenticated...
@endguest
```

----------------------------------------

TITLE: Blade Component Standard Attribute Binding Syntax
DESCRIPTION: Shows the standard, explicit syntax for binding PHP variables (`$userId`, `$name`) to component attributes (`user-id`, `name`) using the colon prefix, providing the full mapping between variable and attribute name. This is equivalent to the short attribute syntax.
SOURCE: https://github.com/laravel/docs/blob/12.x/blade.md#_snippet_76

LANGUAGE: blade
CODE:
```
{{-- Is equivalent to... --}}
<x-profile :user-id="$userId" :name="$name" />
```

----------------------------------------

TITLE: Using Shorthand Foreign Key Syntax in Laravel Migrations
DESCRIPTION: Shows Laravel's terser syntax for creating foreign keys using the foreignId method with constrained(). This approach uses conventions to determine the referenced table and column.
SOURCE: https://github.com/laravel/docs/blob/12.x/migrations.md#2025-04-23_snippet_60

LANGUAGE: php
CODE:
```
Schema::table('posts', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained();
});
```