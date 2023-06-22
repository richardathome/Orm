[![RichBuilds Components](/docs/richbuilds_logo.png)](https://richbuilds.com)

# 🔗 ORM
A wireless, typesafe ORM for PHP 8.1. 

Currently only MySql data sources are supported, but Sqlite and Postgres are planned.

The ORM takes all it's instruction from the database. Make sure your primary and foreign keys are set up correctly.

## Example usage:

```php
$orm = new Orm(new PDO('dsn','username','password'));
```

# Model
- represent a row in a table

## Create an empty model
- fails if table doesn't exist

```php
$post = $orm->Model('posts');  
```

## Set a column value
- fails if column doesn't exit
- fails if value is not a compatible datatype for the column

```php
$post->set('title', 'My Post Title'); 
```

## Set multiple column values
 - no columns are changed if any fail

```php
$post->set([
    'title'='My Post Title',
    'body'=>'My post body.',
    'created'=>new Datetime()
]);
```

## Set a foreign key column to an existing record
 - fails if the parent record doesn't exist

```php
$post->set('author_id', 1); 
```

## Set a foreign key column to a new Model
- fails if Model is not of correct type

```php
$user = $orm->Model('user');
$post->set('author_id', $user);
```

## Set a foreign key column to n array of parent model values

```php
$user = ['name'=>'foo','password'=>'password'];
$post->set('author_id', $user);
```

# Set the children of a model
- accepts arrays of fields or models

```php
$user->set('comments',[
    ['comment'=>'foo', 'visible'=>false],
    $orm->Model('comments')->set('comment', 'bar')
]);
```

# Save the model, it's parents and it's children
- inside transaction, rolls back on error
- all keys are updated

```php
$post->save();
echo $post->getPk();
```

# Fetch a parent model
- fails if column name is not a foreign key or parent doesn't exist

```php
$author = $post->fetchParent('author_id');
// SELECT * FROM users WHERE users.id = posts.author_id
echo $author->get('name');
```

# Fetch children lazily
- fails if child table name is not a child of the parent

```php
$posts = $user->fetchChildrent('posts');
// SELECT * FROM posts WHERE posts.author_id = user.id
```

# Query

## Create a query

```php
$posts = $orm->Query('posts', 
    [
        'author_id'=>1
    ],
    [
        'per_page'=>10
    ]
)

// SELECT * FROM database.posts WHERE database.posts.author_id = :1 LIMIT 10 OFFSET 0;

foreach($posts as $post) {
  echo $post->get('title');
  $comments = $post->fetchChildren('comments');
}
```