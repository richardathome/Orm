[![RichBuilds Components](/src/richbuilds_logo.png)](https://richbuilds.com)
![ORM](/src/orm_logo.png)

# ORM
A Wireless, typesafe ORM for PHP 8.1. 

Currently only MySql data sources are supported, but Sqlite and Postgres are planned.

## Quickstart
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

## Set a foreign key column to a an array of parent values

```php
$user = ['name'=>'foo','password'=>'password'];
$post->set('author_id', $user);
```

# Set the children of a model
- accepts arrays of fields
- accepts array of models

```php
$user->set('comments',[
    ['comment'=>'foo', 'visible'=>false],
    $orm->Model('comments')->set('comment', 'bar')]
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
$author = $post->fetchPatent('author_id');
echo $author->get('name');
```

# Fetch children lazily
- fails if child table name is not a child of the parent

```php
$posts = $user->fetchChildrent('posts');
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